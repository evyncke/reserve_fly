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

//header('Content-Type: text/calendar; charset=utf-8') ;
header('Content-Type: text/calendar; charset="UTF-8"') ;
header("Content-Disposition: inline; filename=rapcs-${user_id}.ics") ;
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
// header('Cache-Control: max-age=0, private, must-revalidate' ) ;

$eol = "\r\n" ;


function emit($line) {
	global $content ;
	
	$content .= $line ;
}

function emit_header() {
	global $eol, $test_mode ;

	emit("BEGIN:VCALENDAR" . $eol .
		"VERSION:2.0" . $eol .
		"METHOD:PUBLISH" . $eol .
		"ORGANIZER:RAPCS Réservation" . $eol .
		"PRODID:-//$_SERVER[HTTP_HOST]//FR" . $eol .
//		'PRODID:-//Apple Inc.//Mac OS X 10.9.4//EN' . $eol .
//		"CALSCALE:GREGORIAN" . $eol .
		"X-PUBLISHED-TTL:PT1H" . $eol .
		"X-WR-CALNAME:Réservations RAPCS" . (($test_mode) ? ' test' : '') . $eol . 
//		"X-WR-TIMEZONE:Europe/Brussels" . $eol .
	// If this iCalendar is being automatically published to a remote location at regular intervals,
	// this property SHOULD<33> be set to that interval with a minimum granularity of minutes.
	// X-PUBLISHED-TTL::PT20M for a 20 minute refresh
		"X-PUBLISHED-TTL:PT15M" . $eol) ;
}

function emit_booking($booking, $cancellation) {
	global $eol, $default_timezone, $shared_secret, $mysqli_link ;

	$date_flight_start = gmdate('Ymd\THis\Z', strtotime("$booking[r_start] $default_timezone")) ;
	$date_flight_end =   gmdate('Ymd\THis\Z', strtotime("$booking[r_stop] $default_timezone")) ;
	$date_time_booking = gmdate('Ymd\THis\Z', strtotime("$booking[r_date] $default_timezone")) ;
	$date_alert =        gmdate('Ymd\THis\Z', strtotime("$booking[alert] $default_timezone")) ;
	$auth = md5($booking['r_id'] . $shared_secret) ;
	emit("BEGIN:VEVENT" . $eol) ;
	if ($cancellation) 
		emit( //"METHOD:CANCEL" . $eol .
			"STATUS:CANCELLED" . $eol .
			"SEQUENCE:2" . $eol . // TODO, improve this as modification should bump up the sequence number...
			"X-MICROSOFT-CDO-BUSYSTATUS:FREE" . $eol ) ;
	else
		emit("STATUS:CONFIRMED" . $eol .
			"SEQUENCE:0" . $eol .
			"X-MICROSOFT-CDO-BUSYSTATUS:BUSY" . $eol ) ;
	emit("DTSTART:$date_flight_start" . $eol .
		"DTEND:$date_flight_end" . $eol .
		"DTSTAMP:$date_time_booking" . $eol .
		"UID:booking-$booking[r_id]@$_SERVER[HTTP_HOST]" . $eol .
		// DESCRIPTION: the details in the description
		"DESCRIPTION:Réservation du $booking[r_plane] du " . $eol .
			"\t$booking[r_start] au $booking[r_stop]. " . $eol . 
			"\tPilote: " . db2web($booking['full_name']) . '. ' . $eol ) ;
	if ($booking['r_instructor'] > 0) {
		$result = mysqli_query($mysqli_link, "select name, email from jom_users where id = $booking[r_instructor]") ;
		$instructor = mysqli_fetch_array($result) ;
		emit("\tInstructeur: " . db2web($instructor['name']) . '. ' . $eol) ;
	}
	emit("SEQUENCE:$bookin[r_sequence]" . $eol .
		"URL:" . ((isset($_SERVER['HTTPS'])) ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]/resa/booking.php?" . $eol . "\tid=$booking[r_id]&auth=$auth" . $eol .
		"SUMMARY:Vol sur $booking[r_plane]" . $eol ) ; // SUMMARY is the main visible thing in the calendar
// generate also an alarm one hour before, per RFC 5545 it MUST be included in the VEVENT
// it MUST also include ACTION & TRIGGER
	emit("BEGIN:VALARM" . $eol) ;
	if ($cancellation) 
		emit("METHOD:CANCEL" . $eol .
			"STATUS:CANCELLED" . $eol) ;
	else {
		emit("ACTION:DISPLAY" . $eol) ; // ACTION:DISPLAY MUST inclide a DESCRIPTION
		emit("DESCRIPTION:Vol sur $booking[r_plane]" . $eol) ;
	}
	emit(
//		"TRIGGER;RELATED=start:-PT1H" . $eol .
		"X-WR-ALARMUID:alert-$booking[r_id]@$_SERVER[HTTP_HOST]" . $eol .
		"UID:alert-$booking[r_id]-$_SERVER[HTTP_HOST]" . $eol .
		"TRIGGER:$date_alert" . $eol .
		"X-APPLE-DEFAULT-ALARM:TRUE" . $eol .
		"END:VALARM" . $eol);
// End of event
	emit("END:VEVENT" . $eol ) ;
}

function emit_trailer() {
	global $eol ;

	emit("END:VCALENDAR" . $eol) ;
}

$content = '' ;
emit_header() ;
// r_cancel_who is null removed so that we can emit a cancel operation
$result = mysqli_query($mysqli_link, "select *,u.name as full_name, date_sub(r_start, interval 1 hour) as alert
		from $table_bookings b join jom_users u on b.r_pilot = u.id, $table_person p
		where p.jom_id=u.id and (b.r_pilot = $user_id or b.r_instructor = $user_id) 
		order by r_start desc limit 0,20") or die("impossible de lire les reservations: " . mysqli_error($mysqli_link));
while ($row = mysqli_fetch_array($result)) {
	emit_booking($row, $row['r_cancel_who'] != '') ;
}
emit_trailer() ;
print($content) ;

if ($user_id == 62) 
	@smtp_mail('eric@vyncke.org', "$_SERVER[PHP_SELF]", "La page s'est executée
HTTP request scheme: $_SERVER[REQUEST_SCHEME]<br/>
HTTP request URI: $_SERVER[REQUEST_URI]<br/>
HTTP query: $_SERVER[QUERY_STRING]<br/>
Script name: $_SERVER[SCRIPT_NAME]<br/>
Path info: $_SERVER[PATH_INFO]<br/>
User-Agent: $_SERVER[HTTP_USER_AGENT]<br/>
IP: " . getClientAddress() . "<br/>
userid: $user_id/$userName/$userFullName (FI $userIsInstructor, Admin $userIsAdmin, mecano: $userIsMechanic)
Cancellation == " . ($row['r_cancel_who'] != '') . "

<hr>

$content
", 'Content-type: text/plain; charset="UTF-8"') ;

//journalise($user_id, "I", "ICS download: $content") ;
?>
