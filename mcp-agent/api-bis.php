<?php

/*
   Copyright 2026-2026 Eric Vyncke

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/

// MCP Agent API
//
// Provides access to:
//   - bookings
//   - users
//   - invoices
//   - folios
//   - students
//   - logbooks
//   - planes
//   - incidents
//   - groups
//   - weather
//
// Authentication:
//   Joomla user authentication through HTTP Basic authentication
//   or username/password request parameters.
//
// MCP transport:
//   JSON-RPC 2.0 over HTTP POST
//
// Important:
//   structuredContent MUST be a JSON object, not a top-level JSON array.


require_once '../dbi.php';

header('Content-Type: application/json; charset=utf-8');


/*
 * -------------------------------------------------------------------------
 * Authentication
 * -------------------------------------------------------------------------
 */

function getBasicAuthCredentials()
{
    if (isset($_SERVER['PHP_AUTH_USER'])) {
        return [
            $_SERVER['PHP_AUTH_USER'],
            $_SERVER['PHP_AUTH_PW'] ?? ''
        ];
    }

    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $h = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $h = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } else {
        $h = null;
    }

    if ($h && stripos($h, 'basic ') === 0) {
        $decoded = base64_decode(substr($h, 6));

        if ($decoded !== false) {
            $parts = explode(':', $decoded, 2);

            return [
                $parts[0] ?? '',
                $parts[1] ?? ''
            ];
        }
    }

    return [null, null];
}


function tryJoomlaLogin($username, $password)
{
    if (!$username || !$password) {
        return false;
    }

    try {
        $app = JFactory::getApplication();

        $credentials = [
            'username' => $username,
            'password' => $password
        ];

        $options = [
            'silent' => true,
            'remember' => false
        ];

        $result = $app->login($credentials, $options);

        if ($result) {
            global $joomla_user, $userId;

            $joomla_user = JFactory::getUser();

            CheckJoomlaUser($joomla_user);

            return true;
        }

    } catch (Exception $e) {
        // Do not expose Joomla authentication errors to the client.
    }

    return false;
}


/*
 * -------------------------------------------------------------------------
 * JSON response
 * -------------------------------------------------------------------------
 */

function respondJson($payload, $statusCode = 200)
{
    global $userId;

    http_response_code($statusCode);

    $body = json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    if ($body === false) {
        $body = json_encode([
            'error' => 'json_encode_failed',
            'message' => json_last_error_msg()
        ]);
    }

    echo $body;

    journalise(
        $userId,
        'D',
        "respondJson(): status=$statusCode, body=$body"
    );

    exit;
}


/*
 * -------------------------------------------------------------------------
 * Database helper
 * -------------------------------------------------------------------------
 */

function qFetchAll($sql)
{
    global $mysqli_link, $userId;

    journalise(
        $userId,
        'D',
        "qFetchAll: executing SQL: $sql"
    );

    $res = mysqli_query($mysqli_link, $sql);

    if ($res === false) {
        journalise(
            $userId,
            'E',
            "qFetchAll: SQL error: " .
            mysqli_error($mysqli_link) .
            " for query: $sql"
        );

        return [
            'error' => mysqli_error($mysqli_link)
        ];
    }

    $rows = [];

    while ($r = mysqli_fetch_assoc($res)) {
        $rows[] = $r;
    }

    mysqli_free_result($res);

    return $rows;
}


/*
 * -------------------------------------------------------------------------
 * Validation helpers
 * -------------------------------------------------------------------------
 */

function sanitizeLimit($value, $default = 200, $maximum = 1000)
{
    $limit = intval($value);

    if ($limit <= 0) {
        $limit = $default;
    }

    if ($limit > $maximum) {
        $limit = $maximum;
    }

    return $limit;
}


function validateDateTime($value)
{
    if ($value === null || $value === '') {
        return null;
    }

    $value = trim($value);

    $formats = [
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'Y-m-d'
    ];

    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $value);

        if (
            $date !== false &&
            $date->format($format) === $value
        ) {
            return $date->format('Y-m-d H:i:s');
        }
    }

    return false;
}


/*
 * -------------------------------------------------------------------------
 * Resource data
 * -------------------------------------------------------------------------
 */

function fetchResourceData(
    $resource,
    $id = 0,
    $limit = 200,
    $options = []
) {
    global
        $table_bookings,
        $table_person,
        $table_bk_invoices,
        $table_flights,
        $table_dto_student,
        $table_logbook,
        $table_planes,
        $table_incident,
        $table_incident_history,
        $table_user_usergroup_map;

    global
        $userId,
        $userIsInstructor,
        $userIsBoardMember,
        $odoo_host,
        $odoo_db,
        $odoo_username,
        $odoo_password,
        $mysqli_link;


    /*
     * ---------------------------------------------------------------------
     * BOOKINGS
     * ---------------------------------------------------------------------
     */

    case_bookings:

    if ($resource === 'bookings') {

        /*
         * Get one booking by ID.
         */
        if ($id > 0) {

            $sql = "
                SELECT
                    r_id AS id,
                    r_plane,
                    r_pilot,
                    r_instructor,
                    r_start,
                    r_stop,
                    r_type,
                    CONVERT(r_comment USING UTF8) AS comment,
                    r_date
                FROM $table_bookings
                WHERE r_id = $id
                LIMIT 1
            ";

            return qFetchAll($sql);
        }


        /*
         * Optional date/time range.
         *
         * A booking overlaps the requested interval when:
         *
         *     booking.stop  > requested.start
         * AND booking.start < requested.end
         *
         * This correctly includes bookings that start before the
         * requested period but continue into it.
         */

        $where = [];

        $start = validateDateTime($options['start'] ?? null);
        $end   = validateDateTime($options['end'] ?? null);


        if ($start === false) {
            return [
                'error' => 'invalid_start',
                'message' =>
                    'Invalid start date. Expected YYYY-MM-DD or ' .
                    'YYYY-MM-DD HH:MM[:SS].'
            ];
        }


        if ($end === false) {
            return [
                'error' => 'invalid_end',
                'message' =>
                    'Invalid end date. Expected YYYY-MM-DD or ' .
                    'YYYY-MM-DD HH:MM[:SS].'
            ];
        }


        if ($start !== null) {
            $startEscaped = mysqli_real_escape_string(
                $mysqli_link,
                $start
            );

            $where[] = "r_stop > '$startEscaped'";
        }


        if ($end !== null) {
            $endEscaped = mysqli_real_escape_string(
                $mysqli_link,
                $end
            );

            $where[] = "r_start < '$endEscaped'";
        }


        if (
            $start !== null &&
            $end !== null &&
            strtotime($start) >= strtotime($end)
        ) {
            return [
                'error' => 'invalid_range',
                'message' => 'start must be before end'
            ];
        }


        $whereSql = '';

        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }


        $sql = "
            SELECT
                r_id AS id,
                r_plane,
                r_pilot,
                r_instructor,
                r_start,
                r_stop,
                r_type,
                CONVERT(r_comment USING UTF8) AS comment,
                r_date
            FROM $table_bookings
            $whereSql
            ORDER BY r_start ASC
            LIMIT $limit
        ";

        return qFetchAll($sql);
    }


    /*
     * ---------------------------------------------------------------------
     * USERS
     * ---------------------------------------------------------------------
     */

    case_users:

    if ($resource === 'users') {

        if ($id > 0) {

            $sql = "
                SELECT
                    jom_id AS id,
                    odoo_id,
                    name AS username,
                    CONVERT(first_name USING UTF8) AS first_name,
                    CONVERT(last_name USING UTF8) AS last_name,
                    email,
                    cell_phone,
                    CONVERT(address USING UTF8) AS address,
                    CONVERT(city USING UTF8) AS city,
                    zipcode,
                    country,
                    GROUP_CONCAT(
                        $table_user_usergroup_map.group_id
                    ) AS group_ids
                FROM $table_person
                JOIN $table_user_usergroup_map
                    ON $table_person.jom_id =
                       $table_user_usergroup_map.user_id
                WHERE jom_id = $id
                GROUP BY jom_id
            ";

            return qFetchAll($sql);
        }


        $sql = "
            SELECT
                jom_id AS id,
                odoo_id,
                name AS username,
                CONVERT(first_name USING UTF8) AS first_name,
                CONVERT(last_name USING UTF8) AS last_name,
                email,
                cell_phone,
                CONVERT(address USING UTF8) AS address,
                CONVERT(city USING UTF8) AS city,
                zipcode,
                country,
                GROUP_CONCAT(
                    $table_user_usergroup_map.group_id
                ) AS group_ids
            FROM $table_person
            JOIN $table_user_usergroup_map
                ON $table_person.jom_id =
                   $table_user_usergroup_map.user_id
            GROUP BY jom_id
            ORDER BY last_name, first_name
            LIMIT $limit
        ";

        return qFetchAll($sql);
    }


    /*
     * ---------------------------------------------------------------------
     * INVOICES
     * ---------------------------------------------------------------------
     */

    case_invoices:

    if ($resource === 'invoices') {

        if ($id > 0) {

            $res = mysqli_query(
                $mysqli_link,
                "
                SELECT odoo_id
                FROM $table_person
                WHERE jom_id = $userId
                LIMIT 1
                "
            );

            if ($res === false) {
                return [
                    'error' => 'database_error',
                    'message' => mysqli_error($mysqli_link)
                ];
            }

            $row = mysqli_fetch_assoc($res);

            if ($row === null) {
                return [
                    'error' => 'no_jom_id',
                    'message' => 'Unknown logged-in user'
                ];
            }

            $odooId = $row['odoo_id'];

            if (
                $odooId != $id &&
                !($userIsInstructor || $userIsBoardMember)
            ) {
                journalise(
                    $userId,
                    'E',
                    "Requested Odoo partner id ($id) does not match " .
                    "logged-in user's Odoo partner id ($odooId)"
                );

                return [
                    'error' => 'odoo_no_access',
                    'message' =>
                        "Requested Odoo partner id ($id) does not " .
                        "match logged-in user's Odoo partner id ($odooId)"
                ];
            }


            require_once __DIR__ . '/../odoo.class.php';

            $odooClient = new OdooClient(
                $odoo_host,
                $odoo_db,
                $odoo_username,
                $odoo_password,
                false
            );

            $domain = [
                [
                    [
                        'partner_id',
                        '=',
                        $id
                    ]
                ]
            ];

            $display = [
                'fields' => [
                    'id',
                    'name',
                    'move_type',
                    'invoice_date',
                    'amount_total',
                    'state',
                    'payment_state'
                ],
                'limit' => $limit,
                'order' => 'invoice_date desc'
            ];

            $res = $odooClient->SearchRead(
                'account.move',
                $domain,
                $display
            );

            if ($res === null) {
                return [
                    'error' => 'odoo_error',
                    'message' =>
                        $odooClient->errorMessage ?? 'unknown'
                ];
            }

            return $res;
        }


        journalise(
            $userId,
            'E',
            'fetchResourceData(): invoices resource requires ' .
            'an Odoo partner id'
        );

        return [
            'error' => 'missing_odoo_id',
            'message' =>
                'invoices resource requires an Odoo partner id'
        ];
    }


    /*
     * ---------------------------------------------------------------------
     * FOLIOS
     * ---------------------------------------------------------------------
     */

    case_folios:

    if ($resource === 'folios') {

        $sql = "
            SELECT
                f_id AS id,
                f_reference AS reference,
                f_date AS date,
                f_booking AS booking
            FROM $table_flights
            ORDER BY f_date DESC
            LIMIT $limit
        ";

        return qFetchAll($sql);
    }


    /*
     * ---------------------------------------------------------------------
     * STUDENTS
     * ---------------------------------------------------------------------
     */

    case_students:

    if ($resource === 'students') {

        if ($id > 0) {

            $sql = "
                SELECT
                    ds_jom_id AS id,
                    ds_year AS year
                FROM $table_dto_student
                WHERE ds_jom_id = $id
            ";

            return qFetchAll($sql);
        }


        $sql = "
            SELECT
                ds_jom_id AS id,
                ds_year AS year
            FROM $table_dto_student
            LIMIT $limit
        ";

        $res = qFetchAll($sql);

        if (isset($res['error'])) {
            return qFetchAll(
                "SELECT * FROM $table_dto_student LIMIT $limit"
            );
        }

        return $res;
    }


    /*
     * ---------------------------------------------------------------------
     * LOGBOOKS
     * ---------------------------------------------------------------------
     */

    case_logbooks:

    if ($resource === 'logbooks') {

        if ($id > 0) {

            $sql = "
                SELECT
                    l_id AS id,
                    l_plane,
                    l_model,
                    l_pilot,
                    l_instructor,
                    l_start,
                    l_end,
                    CONVERT(
                        l_flight_type USING UTF8
                    ) AS flight_type,
                    l_from,
                    l_to
                FROM $table_logbook
                WHERE l_id = $id
            ";

            return qFetchAll($sql);
        }


        $sql = "
            SELECT
                l_id AS id,
                l_plane,
                l_model,
                l_pilot,
                l_instructor,
                l_start,
                l_end,
                CONVERT(
                    l_flight_type USING UTF8
                ) AS flight_type,
                l_from,
                l_to
            FROM $table_logbook
            ORDER BY l_start DESC
            LIMIT $limit
        ";

        return qFetchAll($sql);
    }


    /*
     * ---------------------------------------------------------------------
     * PLANES
     * ---------------------------------------------------------------------
     */

    case_planes:

    if ($resource === 'planes') {

        if ($id !== 0 && $id !== '') {

            $planeId = mysqli_real_escape_string(
                $mysqli_link,
                (string)$id
            );

            return qFetchAll(
                "
                SELECT *
                FROM $table_planes
                WHERE id = '$planeId'
                LIMIT 1
                "
            );
        }


        return qFetchAll(
            "
            SELECT *
            FROM $table_planes
            ORDER BY id
            LIMIT $limit
            "
        );
    }


    /*
     * ---------------------------------------------------------------------
     * INCIDENTS
     * ---------------------------------------------------------------------
     */

    case_incidents:

    if ($resource === 'incidents') {

        $baseFields = "
            i.*,

            (
                SELECT CONVERT(ih_text USING UTF8)
                FROM $table_incident_history h
                WHERE h.ih_incident = i.i_id
                  AND h.ih_status = 'opened'
                ORDER BY h.ih_id ASC
                LIMIT 1
            ) AS description,

            (
                SELECT ih.ih_when
                FROM $table_incident_history ih
                WHERE ih.ih_incident = i.i_id
                  AND ih.ih_status = 'opened'
                ORDER BY ih.ih_id ASC
                LIMIT 1
            ) AS created_date,

            (
                SELECT CONVERT(ih_text USING UTF8)
                FROM $table_incident_history h
                WHERE h.ih_incident = i.i_id
                ORDER BY h.ih_id DESC
                LIMIT 1
            ) AS latest_description,

            (
                SELECT ih.ih_when
                FROM $table_incident_history ih
                WHERE ih.ih_incident = i.i_id
                ORDER BY ih.ih_id DESC
                LIMIT 1
            ) AS latest_date,

            (
                SELECT CONVERT(ih_status USING UTF8)
                FROM $table_incident_history h
                WHERE h.ih_incident = i.i_id
                ORDER BY h.ih_id DESC
                LIMIT 1
            ) AS latest_status
        ";


        if ($id > 0) {

            $sql = "
                SELECT
                    $baseFields
                FROM $table_incident AS i
                WHERE i.i_id = $id
                LIMIT 1
            ";

            return qFetchAll($sql);
        }


        $sql = "
            SELECT
                $baseFields
            FROM $table_incident AS i
            ORDER BY i.i_id DESC
            LIMIT $limit
        ";

        return qFetchAll($sql);
    }


    /*
     * ---------------------------------------------------------------------
     * WEATHER
     * ---------------------------------------------------------------------
     */

    case_weather:

    if ($resource === 'weather') {

        $url = 'https://nav.vyncke.org/EBSP.TXT';

        $opts = [
            'http' => [
                'method' => 'GET',
                'timeout' => 5,
                'header' =>
                    "User-Agent: RAPCS-MCP-Agent/1.0\r\n"
            ]
        ];

        $context = stream_context_create($opts);

        $content = @file_get_contents(
            $url,
            false,
            $context
        );

        if ($content === false) {
            return [
                'error' => 'fetch_failed',
                'message' => "Cannot fetch $url"
            ];
        }

        return [
            'METAR' => trim($content),
            'source' => $url
        ];
    }


    /*
     * ---------------------------------------------------------------------
     * GROUPS
     * ---------------------------------------------------------------------
     */

    case_groups:

    if ($resource === 'groups') {

        $groups = [];

        foreach ($GLOBALS as $name => $value) {

            if (
                preg_match(
                    '/^joomla_.*_group$/',
                    $name
                ) &&
                is_numeric($value)
            ) {
                $groups[$name] = (int)$value;
            }
        }

        ksort($groups);

        return $groups;
    }


    /*
     * ---------------------------------------------------------------------
     * Unknown resource
     * ---------------------------------------------------------------------
     */

    return [
        'error' => 'unknown_resource',
        'message' => 'resource not found',
        'available' => [
            'bookings',
            'users',
            'invoices',
            'folios',
            'students',
            'logbooks',
            'planes',
            'incidents',
            'groups',
            'weather'
        ]
    ];
}


/*
 * -------------------------------------------------------------------------
 * MCP tool definitions
 * -------------------------------------------------------------------------
 */

function getMcpTools()
{
    return [

        /*
         * BOOKINGS
         */

        [
            'name' => 'get_bookings',

            'description' =>
                'Return plane bookings. Optionally filter by booking ' .
                'id or by a date/time interval. A booking is returned ' .
                'when it overlaps the requested interval.',

            'inputSchema' => [
                'type' => 'object',

                'properties' => [

                    'id' => [
                        'type' => 'integer',
                        'description' =>
                            'Optional booking id'
                    ],

                    'start' => [
                        'type' => 'string',
                        'description' =>
                            'Optional beginning of date/time interval. ' .
                            'Format YYYY-MM-DD or YYYY-MM-DD HH:MM[:SS].'
                    ],

                    'end' => [
                        'type' => 'string',
                        'description' =>
                            'Optional end of date/time interval. ' .
                            'Format YYYY-MM-DD or YYYY-MM-DD HH:MM[:SS].'
                    ],

                    'limit' => [
                        'type' => 'integer',
                        'description' =>
                            'Maximum number of rows to return',
                        'default' => 200,
                        'minimum' => 1,
                        'maximum' => 1000
                    ]
                ],

                'additionalProperties' => false
            ]
        ],


        /*
         * USERS
         */

        [
            'name' => 'get_users',

            'description' =>
                'Return users/members from the plane booking ' .
                'system or a user by id',

            'inputSchema' => [
                'type' => 'object',

                'properties' => [

                    'id' => [
                        'type' => 'integer',
                        'description' =>
                            'Optional user id'
                    ],

                    'limit' => [
                        'type' => 'integer',
                        'description' =>
                            'Maximum number of rows to return',
                        'default' => 500,
                        'minimum' => 1,
                        'maximum' => 1000
                    ]
                ],

                'additionalProperties' => false
            ]
        ],


        /*
         * INVOICES
         */

        [
            'name' => 'get_invoices',

            'description' =>
                'Return invoices for a specific Odoo partner id',

            'inputSchema' => [
                'type' => 'object',

                'properties' => [

                    'odoo_id' => [
                        'type' => 'integer',
                        'description' =>
                            'Odoo partner id'
                    ],

                    'limit' => [
                        'type' => 'integer',
                        'description' =>
                            'Maximum number of rows to return',
                        'default' => 500,
                        'minimum' => 1,
                        'maximum' => 1000
                    ]
                ],

                'additionalProperties' => false
            ]
        ],


        /*
         * FOLIOS
         */

        [
            'name' => 'get_folios',

            'description' =>
                'Return folios of invoices from the booking system',

            'inputSchema' => [
                'type' => 'object',

                'properties' => [
                    'limit' => [
                        'type' => 'integer',
                        'description' =>
                            'Maximum number of rows to return',
                        'default' => 500,
                        'minimum' => 1,
                        'maximum' => 1000
                    ]
                ],

                'additionalProperties' => false
            ]
        ],


        /*
         * STUDENTS
         */

        [
            'name' => 'get_students',

            'description' =>
                'Return students from the RAPCS DTO system',

            'inputSchema' => [
                'type' => 'object',

                'properties' => [

                    'id' => [
                        'type' => 'integer',
                        'description' =>
                            'Optional student id'
                    ],

                    'limit' => [
                        'type' => 'integer',
                        'description' =>
                            'Maximum number of rows to return',
                        'default' => 500,
                        'minimum' => 1,
                        'maximum' => 1000
                    ]
                ],

                'additionalProperties' => false
            ]
        ],


        /*
         * LOGBOOKS
         */

        [
            'name' => 'get_logbooks',

            'description' =>
                'Return rows from the flight logs table',

            'inputSchema' => [
                'type' => 'object',

                'properties' => [

                    'id' => [
                        'type' => 'integer',
                        'description' =>
                            'Optional logbook id'
                    ],

                    'limit' => [
                        'type' => 'integer',
                        'description' =>
                            'Maximum number of rows to return',
                        'default' => 200,
                        'minimum' => 1,
                        'maximum' => 1000
                    ]
                ],

                'additionalProperties' => false
            ]
        ],


        /*
         * PLANES
         */

        [
            'name' => 'get_planes',

            'description' =>
                'Return aircraft/planes from the booking system',

            'inputSchema' => [
                'type' => 'object',

                'properties' => [

                    'id' => [
                        'type' => 'string',
                        'description' =>
                            'Optional plane identifier'
                    ],

                    'limit' => [
                        'type' => 'integer',
                        'description' =>
                            'Maximum number of rows to return',
                        'default' => 200,
                        'minimum' => 1,
                        'maximum' => 1000
                    ]
                ],

                'additionalProperties' => false
            ]
        ],


        /*
         * INCIDENTS
         */

        [
            'name' => 'get_incidents',

            'description' =>
                'Return plane incident records',

            'inputSchema' => [
                'type' => 'object',

                'properties' => [

                    'id' => [
                        'type' => 'integer',
                        'description' =>
                            'Optional incident id'
                    ],

                    'limit' => [
                        'type' => 'integer',
                        'description' =>
                            'Maximum number of rows to return',
                        'default' => 200,
                        'minimum' => 1,
                        'maximum' => 1000
                    ]
                ],

                'additionalProperties' => false
            ]
        ],


        /*
         * GROUPS
         */

        [
            'name' => 'get_groups',

            'description' =>
                'Return all user-group IDs defined in the booking system',

            'inputSchema' => [
                'type' => 'object',
                'properties' => new stdClass(),
                'additionalProperties' => false
            ]
        ],


        /*
         * WEATHER
         */

        [
            'name' => 'get_weather',

            'description' =>
                'Return current METAR text for EBSP airport',

            'inputSchema' => [
                'type' => 'object',
                'properties' => new stdClass(),
                'additionalProperties' => false
            ]
        ]
    ];
}


/*
 * -------------------------------------------------------------------------
 * MCP response helper
 * -------------------------------------------------------------------------
 */

function makeStructuredContent($toolResult)
{
    /*
     * IMPORTANT:
     *
     * ChatGPT expects structuredContent to be a JSON object.
     *
     * A PHP numeric array becomes a JSON array:
     *
     *     [ {...}, {...} ]
     *
     * which causes:
     *
     *     Input should be a valid dictionary
     *
     * Therefore always wrap the returned data.
     */

    return [
        'data' => $toolResult
    ];
}


/*
 * -------------------------------------------------------------------------
 * MCP request handling
 * -------------------------------------------------------------------------
 */

function handleMcpRequest($payload)
{
    global $userId;

    if (
        !is_array($payload) ||
        ($payload['jsonrpc'] ?? null) !== '2.0'
    ) {
        return [
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32600,
                'message' => 'Invalid Request'
            ],
            'id' => null
        ];
    }


    $method = $payload['method'] ?? '';
    $id     = $payload['id'] ?? null;
    $params = $payload['params'] ?? [];


    journalise(
        $userId,
        'I',
        "handleMcpRequest(): method=$method, id=$id"
    );


    /*
     * ---------------------------------------------------------------------
     * initialize
     * ---------------------------------------------------------------------
     */

    if ($method === 'initialize') {

        return [
            'jsonrpc' => '2.0',
            'id' => $id,

            'result' => [

                'protocolVersion' => '2024-11-05',

                'capabilities' => [
                    'tools' => [
                        'listChanged' => false
                    ]
                ],

                'serverInfo' => [
                    'name' =>
                        'RAPCS Flight Club and School MCP Agent',

                    'version' => '1.3.0',

                    'description' =>
                        'Provides access to RAPCS bookings, ' .
                        'users, invoices, folios, students, ' .
                        'logbooks, planes, incidents and weather'
                ],

                '_meta' => [
                    'cacheScope' => 'public',
                    'ttlMs' => 3600000
                ]
            ]
        ];
    }


    /*
     * ---------------------------------------------------------------------
     * notifications/initialized
     * ---------------------------------------------------------------------
     */

    if ($method === 'notifications/initialized') {
        return null;
    }


    /*
     * ---------------------------------------------------------------------
     * ping
     * ---------------------------------------------------------------------
     */

    if ($method === 'ping') {

        return [
            'jsonrpc' => '2.0',
            'id' => $id,

            'result' => [
                'status' => 'ok'
            ]
        ];
    }


    /*
     * ---------------------------------------------------------------------
     * tools/list
     * ---------------------------------------------------------------------
     */

    if ($method === 'tools/list') {

        return [
            'jsonrpc' => '2.0',
            'id' => $id,

            'result' => [

                'tools' => getMcpTools(),

                '_meta' => [
                    'cacheScope' => 'public',
                    'ttlMs' => 3600000
                ]
            ]
        ];
    }


    /*
     * ---------------------------------------------------------------------
     * tools/call
     * ---------------------------------------------------------------------
     */

    if ($method === 'tools/call') {

        $name = $params['name'] ?? '';

        $arguments =
            is_array($params['arguments'] ?? null)
            ? $params['arguments']
            : [];


        $toolResult = null;


        switch ($name) {

            case 'get_bookings':

                $toolResult = fetchResourceData(
                    'bookings',

                    intval(
                        $arguments['id'] ?? 0
                    ),

                    sanitizeLimit(
                        $arguments['limit'] ?? 200,
                        200,
                        1000
                    ),

                    [
                        'start' =>
                            $arguments['start'] ?? null,

                        'end' =>
                            $arguments['end'] ?? null
                    ]
                );

                break;


            case 'get_users':

                $toolResult = fetchResourceData(
                    'users',

                    intval(
                        $arguments['id'] ?? 0
                    ),

                    sanitizeLimit(
                        $arguments['limit'] ?? 500,
                        500,
                        1000
                    )
                );

                break;


            case 'get_invoices':

                $toolResult = fetchResourceData(
                    'invoices',

                    intval(
                        $arguments['odoo_id'] ?? 0
                    ),

                    sanitizeLimit(
                        $arguments['limit'] ?? 500,
                        500,
                        1000
                    )
                );

                break;


            case 'get_folios':

                $toolResult = fetchResourceData(
                    'folios',
                    0,
                    sanitizeLimit(
                        $arguments['limit'] ?? 500,
                        500,
                        1000
                    )
                );

                break;


            case 'get_students':

                $toolResult = fetchResourceData(
                    'students',

                    intval(
                        $arguments['id'] ?? 0
                    ),

                    sanitizeLimit(
                        $arguments['limit'] ?? 500,
                        500,
                        1000
                    )
                );

                break;


            case 'get_logbooks':

                $toolResult = fetchResourceData(
                    'logbooks',

                    intval(
                        $arguments['id'] ?? 0
                    ),

                    sanitizeLimit(
                        $arguments['limit'] ?? 200,
                        200,
                        1000
                    )
                );

                break;


            case 'get_planes':

                $planeId =
                    $arguments['id'] ?? '';

                $toolResult = fetchResourceData(
                    'planes',
                    $planeId,
                    sanitizeLimit(
                        $arguments['limit'] ?? 200,
                        200,
                        1000
                    )
                );

                break;


            case 'get_incidents':

                $toolResult = fetchResourceData(
                    'incidents',

                    intval(
                        $arguments['id'] ?? 0
                    ),

                    sanitizeLimit(
                        $arguments['limit'] ?? 200,
                        200,
                        1000
                    )
                );

                break;


            case 'get_groups':

                $toolResult = fetchResourceData(
                    'groups'
                );

                break;


            case 'get_weather':

                $toolResult = fetchResourceData(
                    'weather'
                );

                break;


            default:

                return [
                    'jsonrpc' => '2.0',
                    'id' => $id,

                    'error' => [
                        'code' => -32601,
                        'message' =>
                            "Unknown tool: $name"
                    ]
                ];
        }


        /*
         * MCP tool result.
         *
         * content contains human-readable JSON text.
         *
         * structuredContent contains structured JSON.
         *
         * structuredContent MUST be an object.
         */

        $structuredContent =
            makeStructuredContent($toolResult);


        $text = json_encode(
            $toolResult,

            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE |
            JSON_PRETTY_PRINT
        );


        if ($text === false) {
            $text = json_encode([
                'error' => 'json_encode_failed',
                'message' => json_last_error_msg()
            ]);
        }


        return [
            'jsonrpc' => '2.0',
            'id' => $id,

            'result' => [

                'content' => [
                    [
                        'type' => 'text',
                        'text' => $text
                    ]
                ],

                'structuredContent' =>
                    $structuredContent
            ]
        ];
    }


    /*
     * ---------------------------------------------------------------------
     * Unknown method
     * ---------------------------------------------------------------------
     */

    return [
        'jsonrpc' => '2.0',
        'id' => $id,

        'error' => [
            'code' => -32601,
            'message' =>
                "Method not found: $method"
        ]
    ];
}


/*
 * -------------------------------------------------------------------------
 * Authenticate request
 * -------------------------------------------------------------------------
 */

$authenticated = false;

global $userId, $userIsMember;


if (isset($userId) && $userId > 0) {

    $authenticated = true;

    $userIsMember = 1;

} else {

    /*
     * Try HTTP Basic authentication.
     */

    list($buser, $bpass) =
        getBasicAuthCredentials();


    if ($buser) {

        if (
            tryJoomlaLogin(
                $buser,
                $bpass
            )
        ) {
            $authenticated = true;
        }
    }


    /*
     * Legacy username/password request parameters.
     */

    if (
        !$authenticated &&
        isset($_REQUEST['username']) &&
        isset($_REQUEST['password'])
    ) {

        $u = trim(
            $_REQUEST['username']
        );

        $p = trim(
            $_REQUEST['password']
        );


        if (
            tryJoomlaLogin(
                $u,
                $p
            )
        ) {
            $authenticated = true;
        }
    }
}


if (!$authenticated) {

    header(
        'WWW-Authenticate: Basic realm="RAPCS MCP Agent"'
    );

    respondJson(
        [
            'error' => 'unauthorized',

            'message' =>
                'Joomla authentication required'
        ],
        401
    );
}


/*
 * -------------------------------------------------------------------------
 * HTTP request handling
 * -------------------------------------------------------------------------
 */

$resource =
    isset($_REQUEST['resource'])
    ? $_REQUEST['resource']
    : '';


$id =
    isset($_REQUEST['id'])
    ? intval($_REQUEST['id'])
    : 0;


$httpMethod =
    strtoupper(
        $_SERVER['REQUEST_METHOD'] ?? 'GET'
    );


/*
 * -------------------------------------------------------------------------
 * MCP JSON-RPC POST
 * -------------------------------------------------------------------------
 */

if ($httpMethod === 'POST') {

    $rawBody =
        file_get_contents('php://input');


    if (
        $rawBody !== false &&
        trim($rawBody) !== ''
    ) {

        $payload =
            json_decode(
                $rawBody,
                true
            );


        if (
            json_last_error() !== JSON_ERROR_NONE
        ) {

            respondJson(
                [
                    'jsonrpc' => '2.0',

                    'error' => [
                        'code' => -32700,
                        'message' =>
                            'Parse error: ' .
                            json_last_error_msg()
                    ],

                    'id' => null
                ],
                400
            );
        }


        if (is_array($payload)) {

            $mcpResponse =
                handleMcpRequest(
                    $payload
                );


            /*
             * notifications/initialized has no response.
             */

            if ($mcpResponse === null) {

                http_response_code(204);

                exit;
            }


            respondJson(
                $mcpResponse
            );
        }
    }
}


/*
 * -------------------------------------------------------------------------
 * Legacy GET/REST resource interface
 * -------------------------------------------------------------------------
 */

if ($resource !== '') {

    $limit =
        sanitizeLimit(
            $_REQUEST['limit'] ?? 200,
            200,
            1000
        );


    /*
     * Keep the old REST interface working.
     */

    if (
        $resource === 'bookings' &&
        $id > 0
    ) {

        respondJson(
            fetchResourceData(
                $resource,
                $id,
                $limit
            )
        );
    }


    respondJson(
        fetchResourceData(
            $resource,
            0,
            $limit
        )
    );
}


/*
 * -------------------------------------------------------------------------
 * Default response
 * -------------------------------------------------------------------------
 */

respondJson(
    [
        'message' =>
            'RAPCS MCP Agent',

        'protocol' =>
            'json-rpc-2.0',

        'resources' => [
            'bookings',
            'users',
            'invoices',
            'folios',
            'students',
            'logbooks',
            'planes',
            'incidents',
            'groups',
            'weather'
        ]
    ]
);

?>
