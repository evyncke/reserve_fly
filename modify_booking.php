<?php
/*
   Copyright 2014-2019 Eric Vyncke

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
ob_start("ob_gzhandler");

require_once 'dbi.php' ;

$response = array() ;
$response['error'] = '' ; // Until now: no error :-)

// Parameter sanitization
$plane = trim($_REQUEST['plane']) ;
if ($plane == '') die("Missing parameter: plane") ;
$plane = mysqli_real_escape_string($mysqli_link, $plane) ;
$pilot_id = $_REQUEST['pilotId'] ;
if (!is_numeric($pilot_id)) die("Bien essaye... $pilot_id") ;
$instructor_id = $_REQUEST['instructorId'] ;
if ($instructor_id) {
        if (!is_numeric($instructor_id)) die("Bien essaye... instructor: $instructor_id") ;
        if ($instructor_id == -1) $instructor_id = "NULL" ;
} else
        $instructor_id = "NULL" ;
$duration = str_replace(',', '.', $_REQUEST['duration']) ;
if (!is_numeric($duration) or $duration < 0) die("Bien essaye... $duration") ;
$start = mysqli_real_escape_string($mysqli_link, $_REQUEST['start']) ;
$end = mysqli_real_escape_string($mysqli_link, $_REQUEST['end']) ;
$comment = mysqli_real_escape_string($mysqli_link, $_REQUEST['comment']) ;
$comment_db = web2db($comment) ;
$crew_wanted = mysqli_real_escape_string($mysqli_link, $_REQUEST['crewWanted']) ;
$pax_wanted = mysqli_real_escape_string($mysqli_link, $_REQUEST['paxWanted']) ;
$from_apt = mysqli_real_escape_string($mysqli_link, $_REQUEST['fromApt']) ;
$via1_apt = mysqli_real_escape_string($mysqli_link, $_REQUEST['via1Apt']) ;
$via2_apt = mysqli_real_escape_string($mysqli_link, $_REQUEST['via2Apt']) ;
$to_apt = mysqli_real_escape_string($mysqli_link, $_REQUEST['toApt']) ;
// booking ID
$id = trim($_REQUEST['booking']) ;
if ($id == '') die("Missing parameter: booking") ;
if (!is_numeric($id)) die("Bien essaye... booking: $id") ;

$response['booking'] = $id ;

// TODO check that:
// - add copyright / disclaimer

$result = mysqli_query($mysqli_link, "select * from $table_bookings where r_id = $id") ;
if ((!$result) || (mysqli_num_rows($result) == 0))
        $response['error'] .= "Cette reservation, $id, n'existe pas, " . mysqli_error($mysqli_link) . '<br/>' ;
else {
        $booking = mysqli_fetch_array($result) ;
//        $pilot_id = $booking['r_pilot'] ;
        $booking_type = $booking['r_type'] ;
		$response['previous_pilot_id'] = $booking['r_pilot'] ;
		$response['previous_instructor_id'] = $booking['r_instructor'] ;
		$response['previous_plane'] = $booking['r_plane'] ;
		$response['previous_booking_start'] = $booking['r_start'] ;
		$response['previous_booking_stop'] = $booking['r_stop'] ;
		$response['previous_comment'] = db2web($booking['r_comment']) ;
        if (!($userIsAdmin or $userIsInstructor)) {
               if (($booking['r_pilot'] != $userId) && ($booking['r_who'] != $userId) && !($userIsMechanic && ($booking['r_type'] == BOOKING_TYPE_MAINTENANCE)))
                       $response['error'] .= "Cette réservation, $id, ne peut être modifiée par vous ($userId), uniquement par $pilot_id ou $booking[r_who]<br/>" ;
        }
}

// Basic checks on dates
$start_date = new DateTime($start) ;
if (!$start_date) $response['error'] .= "$start is not a valid date<br/>" ;
$end_date = new DateTime($end) ;
if (!$end_date) $response['error'] .= "$end is not a valid date<br/>" ;
if ($end_date <= $start_date) $response['error'] .= "La fin doit &ecirc;tre apr&egrave;s le d&eacute;but: $start -> $end.<br/>" ;

// Check on user ids
if ($userId == 0) $response['error'] .= "Vous devez &ecirc;tre connect&eacute; pour faire une r&eacute;servation.<br/>" ;
if ($booking_type == BOOKING_MAINTENANCE) {
	if (! ($userIsMechanic or $userIsInstructor or $userIsAdmin)) $response['error'] .= "Impossible de modifier une mise en maintenance.<br/>" ;
} else {
	if ($pilot_id != $userId) {
		if (! ($userIsInstructor or $userIsAdmin)) $response['error'] .= "Vous n'avez pas le droit de modifier une r&eacute;servation d'un autre pilote.<br/>" ;
	} else {
		if (! ($userIsPilot or $userIsInstructor or $userIsAdmin)) $response['error'] .= "Vous n'avez pas le droit faire une modification de r&eacute;servation.<br/>" ;
	}
}


// TODO check that:
// - copyright & disclaimer
// BUG cannot have to consecutive bookings!

// Check whether the plane exists and is active
$result = mysqli_query($mysqli_link, "select * from $table_planes where id = '$plane'") or die("Cannot check the plane status:".mysqli_error($mysqli_link)) ;
$row = mysqli_fetch_array($result) ;
if (!$row or $row['actif'] == 0)
	$response['error'] .= "Cet avion ($plane) n'est pas disponible, r&eacute;servation non effectu&eacute;e...<br/>" ;

// Check whether this period overlaps with other ones
// TODO should give more information about other reservations => do not count(*) but mysqli_num_rows()
$sql = "select count(*) from $table_bookings
        where r_plane = '$plane' and r_id != $id and r_cancel_date is null and
                ('$start' between r_start and date_sub(r_stop, interval 1 minute) or
                date_sub('$end', interval 1 minute) between r_start and r_stop or
		r_start between '$start' and date_sub('$end', interval 1 minute) or
		date_sub(r_stop, interval 1 minute) between '$start' and '$end')
        " ;

$response['sql'] = $sql ;
$result = mysqli_query($mysqli_link, $sql) ;
if ($result) {
	$row = mysqli_fetch_array($result) ;
	$response['result'] = $row[0] ;
	if ($row[0] > 0)
		$response['error'] .= "Cette r&eacute;servation ($plane) est en conflit avec une autre! Modification non effectu&eacute;e...<br/>" ;
} else
	$response['error'] .= "Cannot check booking ($start ... $stop):" . mysqli_error($mysqli_link) . "<br/>" ;

if ($response['error'] == '') {
	$sql = "REPLACE INTO $table_bookings(r_id, r_plane, r_start, r_stop, r_duration, r_pilot, r_instructor, r_comment, r_crew_wanted, r_pax_wanted,
			r_from, r_via1, r_via2, r_to, r_type, r_date, r_address, r_who)
		VALUES($id, '$plane', '$start', '$end', $duration, $pilot_id, $instructor_id, '$comment_db', $crew_wanted, $pax_wanted,
			'$from_apt', '$via1_apt', '$via2_apt', '$to_apt', $booking_type, sysdate(), '" . getClientAddress() . "', $userId)" ;
	$response['sql'] = $sql ; // Danger zone as the SQL string is not UTF-8...
	$result = mysqli_query($mysqli_link, $sql) ;

	if ($result and mysqli_affected_rows($mysqli_link) == 2) { // a REPLACE is actually a DELETE followed by INSERT
		$booking_id = $id ;
		$result = mysqli_query($mysqli_link, "select name, email from jom_users where id = $pilot_id") ;
		$pilot = mysqli_fetch_array($result) ;
		$pilot['name'] = db2web($pilot['name']) ; // SQL DB is latin1 and the rest is in UTF-8
		if ($instructor_id != 'NULL') {
			$result = mysqli_query($mysqli_link, "select name, email from jom_users where id = $instructor_id") ;
			$instructor = mysqli_fetch_array($result) ;
			$instructor['name'] = db2web($instructor['name']) ; // SQL DB is latin1 and the rest is in UTF-8
		}
		$result = mysqli_query($mysqli_link, "select name, email from jom_users where id = $userId") ;
		$booker = mysqli_fetch_array($result) ;
		$booker['name'] = db2web($booker['name']) ; // SQL DB is latin1 and the rest is in UTF-8
		$booker_quality = 'pilote' ;
		if ($useIsInstructeur)
			$booker_quality = 'instructeur' ;
		elseif ($useIsMechanic)
			$booker_quality = 'm&eacute;cano' ;
		elseif ($useIsAdmin)
			$booker_quality = 'administrateur web' ;
		$mime_preferences = array(
			"input-charset" => "UTF-8",
			"output-charset" => "UTF-8",
			"scheme" => "Q") ;
		$response['message'] = "La r&eacute;servation de $plane du $start au $end: est modifi&eacute;e" ;
		if ($pilot_id == $userId)
			$email_subject = iconv_mime_encode('Subject',
				"Modification d'une réservation de $plane pour $pilot[name]",
					$mime_preferences) ;
		else
			$email_subject = iconv_mime_encode('Subject',
				"Modification d'une réservation de $plane par $booker[name] pour $pilot[name]",
					$mime_preferences) ;
		if ($email_subject === FALSE)
			$email_subject = "Cannot iconv(pilot/$pilot[name])" ;
		$email_message = "La r&eacute;servation du $start au $end sur le $plane " ;
		$email_message .= "avec $pilot[name] en pilote est modifi&eacute;e.<br/>\n" ;
		if ($comment != '')
			$email_message .= "Commentaires: <i>$comment</i>.<br/>\n" ;
		if ($pilot_id != $userId)
			$email_message .= "Cette op&eacute;ration a &eacute;t&eacute; effectu&eacute;e par $booker[name] ($booker_quality).\n" ;
		if ($instructor_id != 'NULL')
			$email_message .= "Instructeur vol: $instructor[name] (<a href=\"mailto:$instructor[email]\">$instructor[email]</a>).\n" ;
		$email_message .= "<hr>La r&eacute;servation pr&eacute;c&eacute;dente &eacute;tait pour:<ul>\n" ;
		$email_message .= "<li>Avion: $response[previous_plane];</li>\n" ;
		$email_message .= "<li>De: $response[previous_booking_start] &agrave; $response[previous_booking_stop];</li>\n" ;
		$email_message .= "<li>Commentaire: $response[previous_comment];</li>\n" ;
		$email_message .= "</ul>\n" ;
		if ($test_mode) $email_message .= "<hr><font color=red><B>Ceci est une version de test</b></font>" ;
		$email_header = "From: $managerName <$managerEmail>\r\n" ;
		if (!$test_mode) {
			$email_header .= "To: $pilot[name] <$pilot[email]>\r\n" ;
			if ($pilot_id != $userId)
				$email_header .= "Cc: $booker[name] <$booker[email]>\r\n" ;
			if ($instructor_id != $userId)
				$email_header .= "Cc: $instructor[name] <$instructor[email]>\r\n" ;
			if ($booking_type == BOOKING_MAINTENANCE)
				$email_header_recipients .= "Cc: $fleetName <$fleetEmail>\r\n" ;
			if ($bccTo != '') $email_header .= "Bcc: $bccTo\r\n" ;
		}
		$email_header .= "Return-Path: $managerName <$managerEmail>\r\n" ;
		$email_header .= "Content-Type: text/html; charset=\"UTF-8\"\r\n" ;
		$email_header .= "MIME-Version: 1.0\r\n" ;
		$email_header .= "X-Comment: reservation is $booking_id\r\n" ;
		if ($test_mode)
			@mail("eric.vyncke@ulg.ac.be", substr($email_subject, 9), $email_message, $email_header) ;
		else
			@mail("$pilot[name] <$pilot[email]>", substr($email_subject, 9), $email_message, $email_header) ;
		$modif_log = '' ;
		if ($plane != $response['previous_plane']) $modif_log .= "$response[previous_plane]=>$plane " ;
		if (intval($pilot_id) != intval($response['previous_pilot_id'])) $modif_log .= "pilot: $response[previous_pilot_id]=>$pilot_id. " . 
			'(' . intval($response['previous_pilot_id']) . '/' . intval($pilot_id) . ')' ;
		if (intval($instructor_id) != intval($response['previous_instructor_id'])) $modif_log .= "instructor: $response[previous_instructor_id]=>$instructor_id " .
				'(' . intval($response['previous_instructor_id']) . '/' . intval($intructor_id) . ')' ;
	if (strtotime($start) != strtotime($response['previous_booking_start'])) $modif_log .= "start: $response[previous_booking_start]=>$start " ; 
		if (strtotime($end) != strtotime($response['previous_booking_stop'])) $modif_log .= "end: $response[previous_booking_stop]=>$end " ;

		if ($booking_type == BOOKING_MAINTENANCE)
			journalise($userId, 'W', "Modification of maintenance booking by $booker[name] ($modif_log)") ;
		else
			journalise($userId, 'W', "Modification of booking for $pilot[name] by $booker[name] ($modif_log)") ;
	} else
		$response['error'] .= "Un probl&egrave;me technique s'est produite... modification non effectu&eacute;e..." . mysqli_error($mysqli_link) . "<br/>" ;
}

// Let's send the data back
header('Content-type: application/json');
unset($response['sql']) ;
$json_encoded = json_encode($response) ;
if ($json_encoded === FALSE) {
	journalise($userId, 'E', "Cannot JSON_ENCODE(), error code: " . json_last_error_msg()) ;
	print("{'errorMessage' : 'cannot json_encode(): " . json_last_error_msg() . "'}") ;
} else
	print($json_encoded) ;

if ($response['error'])
	journalise($userId, 'E', "Error ($response[error]) while modifying booking of $response[previous_plane]=>$plane done for $pilot[name] by $booker[name] ($comment). $start => $end") ;
?>
