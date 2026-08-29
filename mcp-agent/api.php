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

// MCP Agent API - provides access to bookings, users, invoices, folios and students
// Authentication: require $userIsMember != 0

// Include existing app bootstrap (dbi.php sets Joomla user context)
require_once '../dbi.php';

header('Content-Type: application/json; charset=utf-8');

function getBearerToken(): ?string {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = $headers['Authorization']
        ?? $headers['authorization']
        ?? $_SERVER['HTTP_AUTHORIZATION']
        ?? '';

    if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
        return $m[1];
    }
    return null;
}

function authenticate(string $token): ?string {
    global $mcp_secret;

    [$userId, $epoch, $mac] = array_pad(explode(':', $token, 3), 3, null);
    if ($userId === null || $epoch === null || $mac === null) return null;

    $expected = hash_hmac('sha256', $userId . $epoch, $mcp_secret);
    journalise($userId, 'D', "authenticate(): userId=$userId, epoch=$epoch, mac=$mac, expected=$expected");
    if (!hash_equals($expected, $mac)) return null;

    // TODO check whether the epoch is still valid (not expired)

    return $userId; // authenticated identity
}

function getBasicAuthCredentials() {
    if (isset($_SERVER['PHP_AUTH_USER'])) return [$_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ?? ''];
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
            return [$parts[0] ?? '', $parts[1] ?? ''];
        }
    }
    return [null, null];
}

function tryJoomlaLogin($username, $password) {
    if (!$username || !$password) return false;
    try {
        $app = JFactory::getApplication();
        $credentials = ['username' => $username, 'password' => $password];
        $options = ['silent' => true, 'remember' => false];
        $result = $app->login($credentials, $options);
        if ($result) {
            global $joomla_user, $userId;
            $joomla_user = JFactory::getUser();
            CheckJoomlaUser($joomla_user);
            return true;
        }
    } catch (Exception $e) {
        // ignore and return false
    }
    return false;
}

function respondJson($payload, $statusCode = 200) {
    global $userId;

    http_response_code($statusCode);
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($body === false) {
       $body = json_encode([
            'error' => 'json_encode_failed',
            'message' => json_last_error_msg()
        ]);
        journalise($userId, 'E', "respondJson(): json_encode failed: " . json_last_error_msg());
    }
    echo $body;
//    journalise($userId, 'D', "respondJson(): status=$statusCode, body=$body");
    exit;
}

function qFetchAll($sql) {
    global $mysqli_link, $userId;
    //    journalise($userId, 'D', "qFetchAll: executing SQL: $sql");
    $res = mysqli_query($mysqli_link, $sql);
    if ($res === false) {
        journalise($userId, 'E', "qFetchAll: SQL error: " . mysqli_error($mysqli_link) . " for query: $sql");
        return ['error' => mysqli_error($mysqli_link)];
    }
    $rows = [];
    while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    mysqli_free_result($res);
    return $rows;
}

function fetchResourceData($resource, $id = 0, $limit = 200) {
    global $table_bookings, $table_person, $table_bk_invoices, $table_flights, $table_dto_student, $table_logbook, $table_planes, $table_incident, $table_incident_history, $table_user_usergroup_map;
    global $userId, $userIsInstructor, $userIsBoardMember, $odoo_host, $odoo_db, $odoo_username, $odoo_password, $mysqli_link ;

    switch ($resource) {
        case 'bookings':
            if ($id > 0) {
                $sql = "SELECT r_id AS id, r_plane, r_pilot, r_instructor, r_start, r_stop, r_type, CONVERT(r_comment USING UTF8) AS comment, r_date 
                    FROM $table_bookings 
                    WHERE r_id = $id";
                return qFetchAll($sql);
            }
            $sql = "SELECT r_id AS id, r_plane, r_pilot, r_instructor, r_start, r_stop, r_type, CONVERT(r_comment USING UTF8) AS comment, r_date 
                FROM $table_bookings ORDER BY r_date DESC LIMIT $limit";
            return qFetchAll($sql);

        case 'users':
            if ($id > 0) {
                $sql = "SELECT jom_id as id, odoo_id, name AS username, CONVERT(first_name USING UTF8) AS first_name, CONVERT(last_name USING UTF8) AS last_name, 
                    email, cell_phone, 
                    CONVERT(address USING UTF8) AS address, CONVERT(city USING UTF8) AS city, zipcode, country,
                    GROUP_CONCAT($table_user_usergroup_map.group_id) AS group_ids
                    FROM $table_person JOIN $table_user_usergroup_map ON $table_person.jom_id = $table_user_usergroup_map.user_id
                    WHERE jom_id = $id" ;
                return qFetchAll($sql);
            }
            $sql = "SELECT jom_id as id, odoo_id, name AS username, CONVERT(first_name USING UTF8) AS first_name, CONVERT(last_name USING UTF8) AS last_name, 
                email, cell_phone, 
                CONVERT(address USING UTF8) AS address, CONVERT(city USING UTF8) AS city, zipcode, country,
                GROUP_CONCAT($table_user_usergroup_map.group_id) AS group_ids
                FROM $table_person JOIN $table_user_usergroup_map ON $table_person.jom_id = $table_user_usergroup_map.user_id
                GROUP BY jom_id
                ORDER BY last_name, first_name 
                LIMIT $limit";
            return qFetchAll($sql);

        case 'invoices':
            // If an Odoo partner id is provided (passed as $id), fetch invoices from Odoo
            if ($id > 0) {
                $res = mysqli_query($mysqli_link, "SELECT odoo_id FROM $table_person WHERE jom_id = $userId LIMIT 1");
                if ($res === NULL) return ['error' => 'no_jom_id', 'message' => 'Unknown logged-in user'];
                $row = mysqli_fetch_assoc($res);
                if ($row === NULL) return ['error' => 'no_odoo_id', 'message' => 'Cannot fetch Odoo partner id for logged-in user'];
                $odooId = $row['odoo_id'];
                if ($odooId != $id and ! ($userIsInstructor || $userIsBoardMember)) {
                    journalise($userId, 'E', "Requested Odoo partner id ($id) does not match logged-in user's Odoo partner id ($odooId)");
                    return ['error' => 'odoo_no_access', 'message' => "Requested Odoo partner id ($id) does not match logged-in user's Odoo partner id ($odooId)"];
                }
                require_once __DIR__ . '/../odoo.class.php';
                $odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password, false);
                $domain = array(array(array('partner_id', '=', $id)));
                $display = array('fields' => array('id', 'name', 'move_type', 'invoice_date', 'amount_total', 'state', 'payment_state'), 'limit' => $limit, 'order' => 'invoice_date desc');
                $res = $odooClient->SearchRead('account.move', $domain, $display);
                if ($res === NULL) return ['error' => 'odoo_error', 'message' => $odooClient->errorMessage ?? 'unknown'];
                return $res;
            }
            journalise($userId, 'E', "fetchResourceData(): invoices resource requires an Odoo partner id (odoo_id) to be provided");
            return ['error' => 'missing_odoo_id', 'message' => 'invoices resource requires an Odoo partner id (odoo_id) to be provided'] ;

        case 'folios':
            $sql = "SELECT f_id AS id, f_reference AS reference, f_date AS date, f_booking AS booking FROM $table_flights ORDER BY f_date DESC LIMIT $limit";
            return qFetchAll($sql);

        case 'students':
            if ($id > 0) {
                $sql = "SELECT ds_jom_id AS id, ds_year AS year  
                    FROM $table_dto_student 
                    WHERE ds_jom_id = $id";
                return qFetchAll($sql);
            }
            $sql = "SELECT ds_jom_id AS id, ds_year AS year
                FROM $table_dto_student LIMIT $limit";
            $res = qFetchAll($sql);
            if (isset($res['error'])) {
                return qFetchAll("SELECT * FROM $table_dto_student LIMIT $limit");
            }
            return $res;

        case 'logbooks':
            if ($id > 0) {
                $sql = "SELECT l_id AS id, l_plane, l_model, l_pilot, l_instructor, l_start, l_end, CONVERT(l_flight_type USING UTF8) AS flight_type, l_from, l_to, l_pilot, l_instructor
                FROM $table_logbook WHERE l_id = $id";
                journalise($userId, 'D', "fetchResourceData(): logbooks resource fetch for id=$id SQL: $sql");
                return qFetchAll($sql);
            }
            $sql = "SELECT l_id AS id, l_plane, l_model, l_pilot, l_instructor, l_start, l_end, CONVERT(l_flight_type USING UTF8) AS flight_type, l_from, l_to, l_pilot, l_instructor
                FROM $table_logbook ORDER BY l_start DESC LIMIT $limit";
            journalise($userId, 'D', "fetchResourceData(): logbooks resource fetch for id=$id SQL: $sql");
            return qFetchAll($sql);

        case 'planes':
            if ($id > 0) {
                return qFetchAll("SELECT * FROM $table_planes WHERE id = '$id' LIMIT 1");
            }
            return qFetchAll("SELECT * FROM $table_planes ORDER BY id LIMIT $limit");

        case 'incidents':
            // Return incident rows enriched with history-based fields:
            // - description: the ih_text for the record where ih_status='opened' (first opened text)
            // - latest_description: ih_text of the most recent history entry
            // - status: ih_status of the most recent history entry
            if ($id > 0) {
                $sql = "SELECT i.*, 
                    (SELECT CONVERT(ih_text USING UTF8) FROM $table_incident_history h WHERE h.ih_incident = i.i_id AND h.ih_status = 'opened' ORDER BY h.ih_id ASC LIMIT 1) AS description,
                    (SELECT ih.ih_when FROM $table_incident_history ih WHERE ih.ih_incident = i.i_id AND ih.ih_status = 'opened' ORDER BY ih.ih_id ASC LIMIT 1) AS created_date,
                    (SELECT CONVERT(ih_text USING UTF8) FROM $table_incident_history h WHERE h.ih_incident = i.i_id ORDER BY h.ih_id DESC LIMIT 1) AS latest_description,
                    (SELECT ih.ih_when FROM $table_incident_history ih WHERE ih.ih_incident = i.i_id ORDER BY ih.ih_id DESC LIMIT 1) AS latest_date,
                    (SELECT CONVERT(ih_status USING UTF8) FROM $table_incident_history h WHERE h.ih_incident = i.i_id ORDER BY h.ih_id DESC LIMIT 1) AS latest_status
                    FROM $table_incident AS i
                    WHERE i.i_id = $id LIMIT 1";
                return qFetchAll($sql);
            }
            $sql = "SELECT i.*, 
                (SELECT CONVERT(ih_text USING UTF8) FROM $table_incident_history h WHERE h.ih_incident = i.i_id AND h.ih_status = 'opened' ORDER BY h.ih_id ASC LIMIT 1) AS description,
                (SELECT ih.ih_when FROM $table_incident_history ih WHERE ih.ih_incident = i.i_id AND ih.ih_status = 'opened' ORDER BY ih.ih_id ASC LIMIT 1) AS created_date,
                (SELECT CONVERT(ih_text USING UTF8) FROM $table_incident_history h WHERE h.ih_incident = i.i_id ORDER BY h.ih_id DESC LIMIT 1) AS latest_description,
                (SELECT ih.ih_when FROM $table_incident_history ih WHERE ih.ih_incident = i.i_id ORDER BY ih.ih_id DESC LIMIT 1) AS latest_date,
                (SELECT CONVERT(ih_status USING UTF8) FROM $table_incident_history h WHERE h.ih_incident = i.i_id ORDER BY h.ih_id DESC LIMIT 1) AS latest_status
                FROM $table_incident AS i
                ORDER BY i.i_id DESC LIMIT $limit";
            return qFetchAll($sql);

        case 'weather':
            // Fetch METAR text for EBSP and return it as 'METAR'
            $url = 'https://nav.vyncke.org/EBSP.TXT';
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'timeout' => 5,
                    'header' => "User-Agent: RAPCS-MCP-Agent/1.0\r\n"
                ]
            ];
            $context = stream_context_create($opts);
            $content = @file_get_contents($url, false, $context);
            if ($content === false) {
                return ['error' => 'fetch_failed', 'message' => "Cannot fetch $url"];
            }
            return ['METAR' => $content, 'source' => $url];

        case 'groups':
            $groups = [];
            foreach ($GLOBALS as $name => $value) {
                if (preg_match('/^joomla_.*_group$/', $name) && is_numeric($value)) {
                    $groups[$name] = (int)$value;
                }
            }
            ksort($groups);
            return $groups;

        default:
            return ['error' => 'unknown_resource', 'message' => 'resource not found', 'available' => ['bookings', 'users', 'invoices', 'folios', 'students', 'logs', 'planes', 'incidents', 'groups', 'weather']];
    }
}

function getMcpTools() {
    return [
        [
            'name' => 'get_bookings',
            'description' => 'Return recent plane bookings or a booking by id',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'description' => 'Optional booking id'],
                    'limit' => ['type' => 'integer', 'description' => 'Maximum number of rows to return', 'default' => 200]
                ],
                'additionalProperties' => false
            ]
        ],
        [
            'name' => 'get_users',
            'description' => 'Return users/members from the plane booking system or a user by id',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'description' => 'Optional user id'],
                    'limit' => ['type' => 'integer', 'description' => 'Maximum number of rows to return', 'default' => 500]
                ],
                'additionalProperties' => false
            ]
        ],
        [
            'name' => 'get_invoices',
            'description' => 'Return invoices for a specific Odoo partner id',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'odoo_id' => ['type' => 'integer', 'description' => 'Odoo partner id'],
                    'limit' => ['type' => 'integer', 'description' => 'Maximum number of rows to return', 'default' => 500]
                ],
                'additionalProperties' => false
            ]
        ],
        [
            'name' => 'get_folios',
            'description' => 'Return folios of invoices from the booking system',
            'inputSchema' => [
                'type' => 'object',
                'properties' => ['limit' => ['type' => 'integer', 'description' => 'Maximum number of rows to return', 'default' => 500]],
                'additionalProperties' => false
            ]
        ],
        [
            'name' => 'get_students',
            'description' => 'Return students from the RAPCS DTO system',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'description' => 'Optional student id'],
                    'limit' => ['type' => 'integer', 'description' => 'Maximum number of rows to return', 'default' => 500]
                ],
                'additionalProperties' => false
            ]
        ],
        [
            'name' => 'get_logbooks',
            'description' => 'Return rows from the flight logs table',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'description' => 'Optional logbook id'],
                    'limit' => ['type' => 'integer', 'description' => 'Maximum number of rows to return', 'default' => 200]
                ],
                'additionalProperties' => false
            ]
        ],
        [
            'name' => 'get_planes',
            'description' => 'Return aircraft/planes from the booking system',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'description' => 'Optional plane identifier'],
                    'limit' => ['type' => 'integer', 'description' => 'Maximum number of rows to return', 'default' => 200]
                ],
                'additionalProperties' => false
            ]
        ],
        [
            'name' => 'get_incidents',
            'description' => 'Return planes incident records',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'description' => 'Optional incident id'],
                    'limit' => ['type' => 'integer', 'description' => 'Maximum number of rows to return', 'default' => 200]
                ],
                'additionalProperties' => false
            ]
        ],
        [
            'name' => 'get_groups',
            'description' => 'Return all user-group IDs defined in the booking system',
            'inputSchema' => [
                'type' => 'object',
                'properties' => new stdClass(),
                'additionalProperties' => false
            ]
        ],
        [
            'name' => 'get_weather',
            'description' => 'Return current METAR text for EBSP airport (from nav.vyncke.org)',
            'inputSchema' => [
                'type' => 'object',
                'properties' => new stdClass(),
                'additionalProperties' => false
            ]
        ]
    ];
}

function handleMcpRequest($payload) {
    global $userId;

    if (!is_array($payload) || ($payload['jsonrpc'] ?? null) !== '2.0') {
        return ['jsonrpc' => '2.0', 'error' => ['code' => -32600, 'message' => 'Invalid Request'], 'id' => null];
    }

    $method = $payload['method'] ?? '';
    $id = $payload['id'] ?? null;
    $params = $payload['params'] ?? [];

    journalise($userId, 'I', "handleMcpRequest(): method=$method, id=$id");

    if ($method === 'initialize') {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => [
            'protocolVersion' => '2024-11-05',
            'capabilities' => ['tools' => ['listChanged' => false]],
            'resources' => ['subscribe' => false, 'listChanged' => false],
            'serverInfo' => ['name' => '✈️ RAPCS Flight club and school MCP Agent', 
                'version' => '1.2.3', 
                'description' => '✈️ Provides access to bookings, users, invoices, folios, students, logbooks, planes, incidents and weather data'],
            '_meta' => ['cacheScope' => 'public', 'ttlMs' => 3600000]
        ]];
    }

    if ($method === 'notifications/initialized' || $method === 'ping') {
        if ($method === 'ping') {
            return ['jsonrpc' => '2.0', 'id' => $id, 'result' => ['status' => 'ok']];
        }
        return null;
    }

    if ($method === 'tools/list' || $method === 'tools_search') {
        $tools = getMcpTools();
        $query = strtolower((string)($params['query'] ?? $params['search'] ?? ''));

        if ($query !== '') {
            $tools = array_values(array_filter($tools, function ($tool) use ($query) {
                $name = strtolower((string)($tool['name'] ?? ''));
                $description = strtolower((string)($tool['description'] ?? ''));
                return str_contains($name, $query) || str_contains($description, $query);
            }));
        }

        return ['jsonrpc' => '2.0', 'id' => $id,
            'result' => [
                'tools' => $tools,
                '_meta' => [
                    'cacheScope' => 'public',
                    'ttlMs' => 3600000 // 1 hour in milliseconds
            ]]];
    }

    if ($method === 'tools/search') {
        $tools = getMcpTools();
        $query = strtolower((string)($params['query'] ?? $params['search'] ?? ''));

        if ($query !== '') {
            $tools = array_values(array_filter($tools, function ($tool) use ($query) {
                $name = strtolower((string)($tool['name'] ?? ''));
                $description = strtolower((string)($tool['description'] ?? ''));
                return str_contains($name, $query) || str_contains($description, $query);
            }));
        }

        return ['jsonrpc' => '2.0', 'id' => $id,
            'result' => [
                'tools' => $tools,
                '_meta' => [
                    'cacheScope' => 'public',
                    'ttlMs' => 3600000
                ]
            ]
        ];
    }

    if ($method === 'tools/call') {
        $name = $params['name'] ?? '';
        $arguments = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];
        $toolResult = null;

        switch ($name) {
            case 'get_bookings':
                $toolResult = fetchResourceData('bookings', intval($arguments['id'] ?? 0), intval($arguments['limit'] ?? 200));
                break;
            case 'get_users':
                $toolResult = fetchResourceData('users', intval($arguments['id'] ?? 0), intval($arguments['limit'] ?? 500));
                break;
            case 'get_invoices':
                $toolResult = fetchResourceData('invoices', intval($arguments['odoo_id'] ?? 0), intval($arguments['limit'] ?? 500));
                break;
            case 'get_folios':
                $toolResult = fetchResourceData('folios', 0, intval($arguments['limit'] ?? 500));
                break;
            case 'get_students':
                $toolResult = fetchResourceData('students', intval($arguments['id'] ?? 0), intval($arguments['limit'] ?? 500));
                break;
            case 'get_logbooks':
                $toolResult = fetchResourceData('logbooks', intval($arguments['id'] ?? 0), intval($arguments['limit'] ?? 200));
                break;
            case 'get_planes':
                $toolResult = fetchResourceData('planes', 0, intval($arguments['limit'] ?? 200));
                break;
            case 'get_incidents':
                $toolResult = fetchResourceData('incidents', intval($arguments['id'] ?? 0), intval($arguments['limit'] ?? 200));
                break;
            case 'get_groups':
                $toolResult = fetchResourceData('groups', 0, 0);
                break;
            case 'get_weather':
                $toolResult = fetchResourceData('weather', 0, 0);
                break;
            default:
                journalise($userId, 'E', "handleMcpRequest(): unknown tool method '$name'");
                return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => -32601, 'message' => 'Method not found']];
        }

        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => [
            'content' => [[
                'type' => 'text',
                'text' => json_encode($toolResult, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ]],
            'structuredContent' => ['data' => $toolResult]
            // Should probably add a 'cache' field here to indicate how long the result can be cached, 
            // but for now we leave it to the client to decide
        ]];
    }

    journalise($userId, 'W', "handleMcpRequest(): unknown method '$method'");
    return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => -32601, 'message' => 'Method not found']];
}

// Determine whether the request is authenticated
$authenticated = false;
global $userId, $userIsMember;
if (isset($userId) && $userId > 0) {
    $authenticated = true;
    $userIsMember = ($userId > 0) ? 1 : 0;
} else {
    list($buser, $bpass) = getBasicAuthCredentials();
    if ($buser) {
        if (tryJoomlaLogin($buser, $bpass)) $authenticated = true;
    }

    if (!$authenticated && isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
        $u = trim($_REQUEST['username']);
        $p = trim($_REQUEST['password']);
        if (tryJoomlaLogin($u, $p)) $authenticated = true;
    }
}

if (!$authenticated) {
    $token = getBearerToken();
    $userId = $token ? authenticate($token) : null;

    if ($userId === null) {
        http_response_code(401);
        header('WWW-Authenticate: Bearer');
        echo json_encode(['error' => 'unauthorized']);
        exit;
    }
    $authenticated = true;
}

$resource = isset($_REQUEST['resource']) ? $_REQUEST['resource'] : '';
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

$httpMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($httpMethod === 'POST') {
    $rawBody = file_get_contents('php://input');
    if ($rawBody !== false && trim($rawBody) !== '') {
        $payload = json_decode($rawBody, true);
        if (is_array($payload)) {
            $mcpResponse = handleMcpRequest($payload);
            if ($mcpResponse === null) {
                http_response_code(204);
                exit;
            }
            respondJson($mcpResponse);
        }
    }
}

if ($resource !== '') {
    $limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 200;
    if ($resource === 'bookings' && $id > 0) {
        respondJson(fetchResourceData($resource, $id, $limit));
    }
    respondJson(fetchResourceData($resource, 0, $limit));
}

respondJson(['message' => 'RAPCS MCP Agent', 'protocol' => 'json-rpc-2.0', 'resources' => ['bookings', 'users', 'invoices', 'folios', 'students', 'logbooks', 'planes', 'incidents', 'groups', 'weather']]);
?>