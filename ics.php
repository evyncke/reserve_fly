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

$user_id = $_REQUEST['user'] ;
$auth = $_REQUEST['auth'] ;

if ($auth != md5($user_id . $shared_secret)) die("Wrong key for calendar#$user_id: $auth ") ;
if (! is_numeric($user_id)) die("Wrong user id: $user_id") ;

header('Content-Type: text/calendar; charset="UTF-8"') ;
header("Content-Disposition: inline; filename=rapcs-${user_id}.ics") ;
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate') ;
header('Pragma: no-cache') ;
header('Expires: Mon, 01 Jan 1990 00:00:00 GMT') ; // force automatic expiration ;-)

$eol = "\r\n" ;


// Emit a single/multiple line(s) guaranteed to be shorter than 75 chararters
function emit($line) {
	global $content, $eol ;
	
	if (substr($line, -strlen($eol)) != $eol)
		$line .= $eol ;
	$content .= $line ;
}

// Emit a single header, cutting it in piece shorter than 75 characters
function emit_long($line) {
	global $content, $eol ;
	
	if (substr($line, -strlen($eol)) != $eol)
		$line .= $eol ;
	$content .= wordwrap($line, 75, $eol . "\t", true) ;
}

function emit_header() {
	global $eol, $test_mode, $user_id, $auth, $favicon, $ical_name, $ical_organizer ;

	emit("BEGIN:VCALENDAR" . $eol .
		"PRODID:-//$_SERVER[HTTP_HOST]//Fly-Reserve//FR" . $eol .
		"VERSION:2.0" . $eol .
		"CALSCALE:GREGORIAN" . $eol .
		"METHOD:PUBLISH" . $eol .
		"X-WR-CALNAME:$ical_name" . (($test_mode) ? ' test' : '') . $eol . 
		"ORGANIZER:$ical_organizer" . $eol .
		"DESCRIPTION:$ical_name" . $eol .
		"X-WR-CALDESC:$ical_name" . $eol .
		"IMAGE;VALUE=URI;DISPLAY=BADGE:$favicon" . $eol .
//		"X-WR-TIMEZONE:Europe/Brussels" . $eol .
	// If this iCalendar is being automatically published to a remote location at regular intervals,
	// this property SHOULD<33> be set to that interval with a minimum granularity of minutes.
	// X-PUBLISHED-TTL::PT15M for a 15- minute refresh
		"X-PUBLISHED-TTL:PT15M" . $eol .
		"REFRESH-INTERVAL;VALUE=DURATION:P15M" . $eol .
		"SOURCE;VALUE=URI:https://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]/" . $eol . 
			"\t$_SERVER[PHP_SELF]?user=$user_id&auth=$auth" . $eol ) ;
}

function emit_booking($booking) {
	global $eol, $default_timezone, $shared_secret, $mysqli_link, $ical_name, $ical_organizer, $table_users ;

	$date_flight_start = gmdate('Ymd\THis\Z', strtotime("$booking[r_start] $default_timezone")) ;
	$date_flight_end =   gmdate('Ymd\THis\Z', strtotime("$booking[r_stop] $default_timezone")) ;
	$date_time_booking = gmdate('Ymd\THis\Z', strtotime("$booking[r_date] $default_timezone")) ;
	$date_alert =        gmdate('Ymd\THis\Z', strtotime("$booking[alert] $default_timezone")) ;
	if ($cancellation)
		$date_cancel = gmdate('Ymd\THis\Z', strtotime("$booking[r_cancel_date] $default_timezone")) ;
	$auth = md5($booking['r_id'] . $shared_secret) ;
	emit("BEGIN:VEVENT" . $eol) ;
	emit("METHOD:PUBLISH" . $eol .
		"STATUS:CONFIRMED" . $eol .
		"X-MICROSOFT-CDO-BUSYSTATUS:BUSY" . $eol .
		"DTSTAMP:$date_time_booking" . $eol .
	    "DTSTART:$date_flight_start" . $eol .
		"DTEND:$date_flight_end" . $eol .
		"ORGANIZER:$ical_organizer" . $eol .
		"UID:booking-$booking[r_id]@$_SERVER[HTTP_HOST]" . $eol .
		// DESCRIPTION: the details in the description
		"DESCRIPTION:Réservation du $booking[r_plane] du " . $eol .
			"\t$booking[r_start] au $booking[r_stop]." . $eol . 
			"\tPilote: " . db2web($booking['full_name']) . '. ' . $eol ) ;
	if ($booking['r_instructor'] > 0) {
		$result = mysqli_query($mysqli_link, "select name, email from $table_users where id = $booking[r_instructor]") ;
		$instructor = mysqli_fetch_array($result) ;
		emit("\tInstructeur: " . db2web($instructor['name']) . '. ' . $eol) ;
	}
	if ($booking['r_comment'] != '')
		emit("\tCommentaire: " . db2web($booking['r_comment']) . $eol) ;

	emit("SEQUENCE:$booking[r_sequence]" . $eol .
		"URL:" . ((isset($_SERVER['HTTPS'])) ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]/resa/booking.php?" . $eol . "\tid=$booking[r_id]&auth=$auth" . $eol .
		"SUMMARY:Vol sur $booking[r_plane]" . $eol ) ; // SUMMARY is the main visible thing in the calendar
	emit('TRANSP:OPAQUE' . $eol) ;
// generate also an alarm one hour before, per RFC 5545 it MUST be included in the VEVENT
// it MUST also include ACTION & TRIGGER
	emit("BEGIN:VALARM" . $eol) ;
	emit("ACTION:DISPLAY" . $eol) ; // ACTION is mandatory... ACTION:DISPLAY MUST include a DESCRIPTION
	emit("DESCRIPTION:Vol sur $booking[r_plane]" . $eol) ;
	emit(
//		"TRIGGER;RELATED=start:-PT1H" . $eol .
		"X-WR-ALARMUID:alert-$booking[r_id]@$_SERVER[HTTP_HOST]" . $eol .
		"UID:alert-$booking[r_id]@$_SERVER[HTTP_HOST]" . $eol .
		"TRIGGER:$date_alert" . $eol .
		"X-APPLE-DEFAULT-ALARM:TRUE" . $eol .
		"END:VALARM" . $eol);
// End of event
	emit("END:VEVENT" . $eol ) ;
}

function emit_agenda($event) {
	global $eol, $default_timezone, $mysqli_link, $ical_name, $ical_organizer ;

	$date_event_start = gmdate('Ymd\THis\Z', strtotime("$event[ag_start] $default_timezone")) ;
	$date_event_end =   gmdate('Ymd\THis\Z', strtotime("$event[ag_end] $default_timezone")) ;
	$date_event_created = gmdate('Ymd\THis\Z', strtotime("$event[ag_date] $default_timezone")) ;
	$date_alert =        gmdate('Ymd\THis\Z', strtotime("$event[alert] $default_timezone")) ;
	emit("BEGIN:VEVENT" . $eol) ;
	emit("METHOD:PUBLISH" . $eol .
		"STATUS:TENTATIVE" . $eol .
		"X-MICROSOFT-CDO-BUSYSTATUS:TENTATIVE" . $eol .
		"DTSTAMP:$date_event_created" . $eol .
	    "DTSTART:$date_event_start" . $eol .
		"DTEND:$date_event_end" . $eol .
		"ORGANIZER:$ical_organizer" . $eol .
		"UID:event-$event[ag_id]@$_SERVER[HTTP_HOST]" . $eol) ;
	emit_long("DESCRIPTION:" . db2web($event['ag_description'])) ;
	emit("SEQUENCE:$event[ag_sequence]" . $eol) ;
	if ($event['ag_url'] != '')
		emit("URL:$event[ag_url]"  . $eol) ;
	emit_long("SUMMARY:" . db2web($event['ag_description'])) ;
	emit('TRANSP:OPAQUE' . $eol) ;
// End of event
	emit("END:VEVENT" . $eol ) ;
}

function emit_trailer() {
	global $eol ;

	emit("END:VCALENDAR" . $eol) ;
}

$content = '' ;
emit_header() ;

// Start with the specific user bookings
$result = mysqli_query($mysqli_link, "SELECT *,u.name AS full_name, DATE_SUB(r_start, INTERVAL 1 HOUR) AS alert
		FROM $table_bookings b JOIN $table_users u ON b.r_pilot = u.id, $table_person p
		WHERE p.jom_id=u.id AND (b.r_pilot = $user_id OR b.r_instructor = $user_id) AND r_start >= DATE_SUB(SYSDATE(), INTERVAL 6 MONTH) AND r_cancel_who is null
		ORDER BY r_start LIMIT 0,100") or die("impossible de lire les reservations: " . mysqli_error($mysqli_link));
while ($row = mysqli_fetch_array($result)) {
	if ($row['r_cancel_who'] == '') // Do not generate VCALENDAR entries for cancelled bookings, they will 'disappear' automagically
		emit_booking($row) ;
}

// Then the generic agenda
$result = mysqli_query($mysqli_link, "SELECT *, DATE_SUB(ag_start, INTERVAL 1 DAY) AS alert
		FROM $table_agenda a 
		WHERE ag_start >= DATE_SUB(SYSDATE(), INTERVAL 6 MONTH)
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
", 'Content-type: text/plain; charset="UTF-8"') ;

//journalise($user_id, "I", "ICS download: $_SERVER[HTTP_USER_AGENT]") ;
?>
