<?php
/*
   Copyright 2014-2020 Eric Vyncke

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

/* Reference is https://tools.ietf.org/html/rfc5545
Charset is UTF-8
https://icalendar.org/validator.html
TODO
The "charset" Content-Type parameter MUST be used in MIME transports
   to specify the charset being used. */

ob_start("ob_gzhandler");

require_once "dbi.php" ;
require_once 'facebook.php' ;
require_once 'ics_utils.php' ;

$user_id = $_REQUEST['user'] ;
$auth = $_REQUEST['auth'] ;

if ($auth != md5($user_id . $shared_secret)) die("Wrong key for calendar#$user_id: $auth ") ;
if (! is_numeric($user_id)) die("Wrong user id: $user_id") ;

header('Content-Type: text/calendar; charset=UTF-8') ;
header("Content-Disposition: inline; filename=rapcs-${user_id}.ics") ;
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate') ;
header('Pragma: no-cache') ;
header('Expires: Mon, 01 Jan 1990 00:00:00 GMT') ; // force automatic expiration ;-)

$content = '' ;
emit_header() ;

// Start with the specific user bookings
$result = mysqli_query($mysqli_link, "SELECT *,u.name AS full_name, DATE_SUB(r_start, INTERVAL 1 HOUR) AS alert
		FROM $table_bookings b JOIN $table_users u ON b.r_pilot = u.id, $table_person p
		WHERE p.jom_id=u.id AND (b.r_pilot = $user_id OR b.r_instructor = $user_id) AND r_start >= DATE_SUB(SYSDATE(), INTERVAL 2 MONTH) AND r_cancel_who is null
		ORDER BY r_start 
		LIMIT 0,150") or die("impossible de lire les reservations: " . mysqli_error($mysqli_link));
while ($row = mysqli_fetch_array($result)) {
	if ($row['r_cancel_who'] == '') // Do not generate VCALENDAR entries for cancelled bookings, they will 'disappear' automagically
		emit_booking($row) ;
}

// Then the generic agenda
$result = mysqli_query($mysqli_link, "SELECT *, DATE_SUB(ag_start, INTERVAL 1 DAY) AS alert
		FROM $table_agenda a 
		WHERE ag_start >= DATE_SUB(SYSDATE(), INTERVAL 6 MONTH) AND ag_active != 0
		ORDER BY ag_start LIMIT 0,50") or die("impossible de lire l'agenda: " . mysqli_error($mysqli_link));
while ($row = mysqli_fetch_array($result)) {
	emit_agenda($row) ;
}

emit_trailer() ;
print($content) ;

if (false and $user_id == 62) 
	@smtp_mail('eric@vyncke.org', "$_SERVER[PHP_SELF]", "La page s'est executée
HTTP request scheme: $_SERVER[REQUEST_SCHEME]
HTTP request URI: $_SERVER[REQUEST_URI]
HTTP query: $_SERVER[QUERY_STRING]
Script name: $_SERVER[SCRIPT_NAME]
Path info: $_SERVER[PATH_INFO]
User-Agent: $_SERVER[HTTP_USER_AGENT]
IP: " . getClientAddress() . "
userid: $user_id/$userName/$userFullName (FI $userIsInstructor, Admin $userIsAdmin, mecano $userIsMechanic)

---
$content
---
", 'Content-Type: text/plain; charset="UTF-8"') ;

//journalise($user_id, "I", "ICS download: $_SERVER[HTTP_USER_AGENT]") ;
?>
