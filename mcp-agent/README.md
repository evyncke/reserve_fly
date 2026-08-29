# RAPCS MCP Agent

This lightweight MCP agent exposes JSON endpoints to access past and present bookings, user profiles, invoices, folios (flights) and student records.

Authentication
- The agent relies on the existing Joomla session/bootstrap via `dbi.php`.
- It enforces access by checking `$userIsMember != 0` (user must be logged in).

Endpoints
- `api.php?resource=bookings` — list bookings
- `api.php?resource=users` — list users
- `api.php?resource=invoices` — list invoices
- `api.php?resource=folios` — list flights/folios
- `api.php?resource=students` — list students

Authentication options
- HTTP Basic Auth: send username/password in the `Authorization: Basic ...` header (recommended for scripts).
- Request params: `username` and `password` can be provided as query or form parameters (less secure).

The agent attempts to reuse an existing Joomla session. If no session exists it will try to authenticate
against Joomla using the provided credentials. A failed attempt returns HTTP 401 with `WWW-Authenticate`.

Usage
- Place the `mcp-agent` directory under the webroot (already located at `resa/mcp-agent`).
- Authenticate via the main site (Joomla) before calling the API.

Notes
- This is a minimal starter implementation intended to be extended with pagination, filtering, security hardening, and access control per resource.
