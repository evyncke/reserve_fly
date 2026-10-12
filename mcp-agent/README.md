# RAPCS MCP Agent

This lightweight MCP agent exposes JSON endpoints to access past and present bookings, user profiles, invoices, students, logbooks, planes, incidents, groups and weather.
It can be used either as a plain REST-like API (`api.php?resource=...`) or as an MCP server speaking JSON-RPC 2.0 (`POST api.php`).

Authentication
- The agent relies on the existing Joomla session/bootstrap via `dbi.php`.
- It enforces access by checking that the user is logged in (`$userId > 0`).

Authentication options
- HTTP Basic Auth: send username/password in the `Authorization: Basic ...` header (recommended for scripts).
- Bearer token: `Authorization: Bearer <userId>:<epoch>:<mac>` where `mac` is the HMAC-SHA256 of `<userId><epoch>` keyed with `$mcp_secret` from `auth.php` (used by the MCP clients).
- Request params: `username` and `password` can be provided as query or form parameters (less secure).

The agent attempts to reuse an existing Joomla session. If no session exists it will try to authenticate
against Joomla using the provided credentials. A failed attempt returns HTTP 401 with `WWW-Authenticate`.

REST endpoints
- `api.php?resource=bookings` — list bookings
- `api.php?resource=users` — list users
- `api.php?resource=invoices&odoo_id=<partner id>` — list the invoices of an Odoo partner
- `api.php?resource=students` — list students
- `api.php?resource=logbooks` — list flights of the logbook
- `api.php?resource=planes` — list planes
- `api.php?resource=incidents` — list incidents (with their latest status)
- `api.php?resource=groups` — list the user group ids
- `api.php?resource=weather` — current METAR of EBSP

(The `folios` resource is currently disabled.)

Filters
All resources accept `limit` (maximum number of rows, default depends on the resource, e.g., 200). The following optional filters narrow the result; a filter that makes no sense for a resource is ignored.

| Filter   | Format                                       | bookings | logbooks | users | students | planes | incidents | invoices |
|----------|----------------------------------------------|:--------:|:--------:|:-----:|:--------:|:------:|:---------:|:--------:|
| `id`     | integer, primary key of the row (1)          | yes      | yes      | yes   | yes      |        | yes       | `odoo_id` (mandatory) |
| `date`   | `YYYY-MM-DD`                                 | bookings overlapping that day | day of the start of the flight | | | | | |
| `member` | (part of) the first and/or last name, any order, case-insensitive | pilot or instructor | pilot or instructor | yes | yes | | | |
| `plane`  | plane identifier, e.g., `OO-FMX`             | yes      | yes      |       |          | yes (2) |          |          |

1. For `users` and `students`, the id is the Joomla user id.
2. For `planes`, `id` is accepted as an alias of `plane`.

An invalid `date` or `plane` returns `{"error": "invalid_filter", "message": "..."}` instead of an unfiltered result.

Examples
- `api.php?resource=bookings&date=2026-10-12&plane=OO-FMX`
- `api.php?resource=logbooks&member=dupont&limit=50`
- `api.php?resource=users&member=eric`

Invoices are only returned for the Odoo partner of the logged-in user, unless the user is an instructor or a board member.

MCP tools (JSON-RPC 2.0 over `POST api.php`)
Supported methods: `initialize`, `ping`, `tools/list` (with optional `query`), `tools/search` and `tools/call`.

| Tool             | Arguments                                              |
|------------------|--------------------------------------------------------|
| `get_bookings`   | `id`, `date`, `member`, `plane`, `limit`               |
| `get_users`      | `id`, `member`, `limit`                                |
| `get_invoices`   | `odoo_id`, `limit`                                     |
| `get_students`   | `id`, `member`, `limit`                                |
| `get_logbooks`   | `id`, `date`, `member`, `plane`, `limit`               |
| `get_planes`     | `plane` (`id` still accepted), `limit`                 |
| `get_incidents`  | `id`, `limit`                                          |
| `get_groups`     | none                                                   |
| `get_weather`    | none                                                   |

The arguments have the same meaning as the REST filters above.

Internals
- `fetchResourceData($resource, array $filters, $limit)` in `api.php` returns the rows; `$filters` is the associative array `['id' => int, 'date' => 'YYYY-MM-DD', 'member' => string, 'plane' => string]` (all keys optional).
- `extractFilters($source, $idKey = 'id')` builds and validates that array from a request or from MCP tool arguments (`$idKey` is `odoo_id` for invoices).

Usage
- Place the `mcp-agent` directory under the webroot (already located at `resa/mcp-agent`).
- Authenticate via the main site (Joomla) before calling the API, or use one of the authentication options above.

Notes
- This is a minimal starter implementation intended to be extended with pagination, security hardening, and access control per resource.
