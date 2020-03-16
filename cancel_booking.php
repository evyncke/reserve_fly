<?php
// TODO
// warn the bookers/pilots just before/after
// add pilot/booker's name in error messages...

/*
   Copyright 2013-2020 Eric Vyncke

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
require_once 'facebook.php' ;

$response = array() ;
$response['error'] = '' ; // Until now: no error :-)
$response['message'] = '' ; // Until now: no error :-)

// Parameter sanitization
$id = trim($_REQUEST['id']) ;
if ($id == '') die("Missing parameter: id") ;
if (!is_numeric($id)) die("Bien essaye... $id") ;
$auth = trim($_REQUEST['auth']) ;
if ($auth && ($auth != md5($id . $shared_secret)))
	die("Code ($auth) is not valid for booking $id") ;
$reason = mysqli_real_escape_string($mysqli_link, web2db(trim($_REQUEST['reason']))) ;

$response['id'] = $id ;

$result = mysqli_query($mysqli_link, "select * from $table_bookings where r_id = $id and r_cancel_date is null") ;
if ((!$result) || (mysqli_num_rows($result) == 0))
	$response['error'] .= "Cette réservation, $id, n'existe pas, " . mysqli_error($mysqli_link) . '<br/>' ;
else {
	$booking = mysqli_fetch_array($result) ;
	$pilot_id = $booking['r_pilot'] ;
	$instructor_id = $booking['r_instructor'] ;
    $booking_type = $booking['r_type'] ;
	$plane = $booking['r_plane'] ;
	$booking_start = $booking['r_start'] ;
	$booking_end = $booking['r_stop'] ;
	$response['pilot_id'] = $pilot_id ;
	$response['instructor_id'] = $instructor_id ;
	$response['plane'] = $plane ;
	$response['booking_start'] = $booking_start ;
	$response['booking_end'] = $booking_end ;
	if (!($userIsAdmin || $userIsMechanic || $userIsInstructor || ($auth != ''))) {
		if (($pilot_id != $userId) && ($booking['r_who'] != $userId))
			$response['error'] .= "Cette r&eacute;servation, $id, ne peut pas être modifi&eacute;e par vous ($userId), uniquement par le pilote $pilot_id ou l'instructeur/admin $booking[r_who]<br/>" ;
	}
}

if ($response['error'] == '') {
	$result = mysqli_query($mysqli_link, "update $table_bookings set r_cancel_date = sysdate(), r_cancel_reason = '$reason', 
		r_cancel_who = $userId, r_cancel_address = '" . getClientAddress() . "', r_sequence = r_sequence + 1 where r_id = $id") ;
	if ($result && mysqli_affected_rows($mysqli_link) == 1) {
		if ($booking_type == BOOKING_CUSTOMER) {
			mysqli_query($mysqli_link, "UPDATE $table_flights SET f_booking = NULL, f_date_scheduled = NULL WHERE f_booking = $id")
				or $response['error'] .= "Cannot delete customer booking: " . mysqli_error($mysqli_link) ;
		}
		$result = mysqli_query($mysqli_link, "select name, email from $table_users where id = $pilot_id") ;
		$pilot = mysqli_fetch_array($result) ;
		$pilot['name'] = db2web($pilot['name']) ; // SQL DB is latin1 and the rest is in UTF-8
		if (($auth != '') or ($pilot_id == $userId))
			$booker = $pilot ;
		else {
			$result = mysqli_query($mysqli_link, "select name, email from $table_users where id = $userId") ;
			$booker = mysqli_fetch_array($result) ;
			$booker['name'] = db2web($booker['name']) ; // SQL DB is latin1 and the rest is in UTF-8
		}
		if ($booking['r_instructor']) {
			$result = mysqli_query($mysqli_link, "select name, email from $table_users where id = $booking[r_instructor]") ;
			$instructor = mysqli_fetch_array($result) ;
			$instructor['name'] = db2web($instructor['name']) ; // SQL DB is latin1 and the rest is in UTF-8
		}
		$mime_preferences = array(
			"input-charset" => "UTF-8",
			"output-charset" => "UTF-8",
			"scheme" => "Q") ;
		if ($booking_type == BOOKING_MAINTENANCE) {
			$response['message'] = "La maintenance de $plane du $booking_start au $booking_end: est annul&eacute;e" ;
			$email_subject =  iconv_mime_encode('Subject',
				"Annulation de la mise en maintenance de $plane par $booker[name] [#$id]",
					$mime_preferences) ;
			$email_message = "La maintenance du $booking_start au $booking_end sur le $plane " ;
			$email_message .= "est annul&eacute;e.<br/>" ;
		} else {
			$response['message'] = "La r&eacute;servation de $plane du $booking_start au $booking_end: est annul&eacute;e" ;
			$email_subject = iconv_mime_encode('Subject',
				"Annulation d'une réservation de $plane par $booker[name] pour $pilot[name]  [#$id]",
					$mime_preferences) ;
			if ($email_subject === FALSE)
				$email_subject = "Cannot iconv(pilot/$pilot[name])" ;
			$email_message = "La r&eacute;servation du $booking_start au $booking_end sur le $plane " ;
			$email_message .= "avec $pilot[name] en pilote est annul&eacute;e.<br/>" ;
		}
		$email_message .= "Cette op&eacute;ration a &eacute;t&eacute; effectu&eacute;e par $booker[name]. " ;
		if ($reason) $email_message .= "La raison donn&eacute;e est: <i>" . trim($_REQUEST['reason']) . "</i>." ;
		if ($test_mode) $email_message .= "<hr><font color=red><B>Ceci est une version de test</b></font>" ;
		$email_header = "From: $managerName <$smtp_from>\r\n" ;
		if (!$test_mode) {
			$email_header .= "To: $pilot[name] <$pilot[email]>\r\n" ;
			$email_recipients = $pilot['email'] ;
			if ($pilot_id != $userId and $booker['email'] != '') {
				$email_header .= "Cc: $booker[name] <$booker[email]>\r\n" ;
				$email_recipients .= ", $booker[email]" ;
			}
			if ($booking['r_instructor']) {
				$email_header .= "Cc: $instructor[name] <$instructor[email]>\r\n" ;
				$email_recipients .= ", $instructor[email]" ;
			}
			if ($booking_type == BOOKING_MAINTENANCE) {
				$email_header_recipients .= "Cc: $fleetName <$fleetEmail>\r\n" ;
				$email_recipients .= ", $fleetEmail" ;
			}
			if ($bccTo != '') {
				$email_recipients .= ", $bccTo" ;
			}
		}
		$email_header .= "X-Comment: reservation is $id\r\n" ;
		$email_header .= "References: <booking-$id@$smtp_localhost>\r\n" ;
		$email_header .= "In-Reply-To: <booking-$id@$smtp_localhost>\r\n" ;
		$email_header .= "Thread-Topic: Réservation RAPCS #$id\r\n" ; 
		$email_header .= "Content-Type: text/html; charset=UTF-8\r\n" ;
		if ($test_mode)
			smtp_mail("eric.vyncke@ulg.ac.be", substr($email_subject, 9), $email_message, $email_header) ;
		else
			@smtp_mail($email_recipients, substr($email_subject, 9), $email_message, $email_header) ;
		if ($booking_type == BOOKING_MAINTENANCE)
			journalise($userId, 'W', "Cancellation of maintenance #$id of $plane. $booking_start => $booking_end") ;
		else
			journalise($userId, 'W', "Cancellation of booking #$id of $plane done for $pilot[name] by $booker[name]. $booking_start => $booking_end. Reason: " . trim($_REQUEST['reason'])) ;
	} else
		$response['error'] .= "Un probl&egrave;me technique s'est produit, annulation non effectu&eacute;e..." . mysqli_error($mysqli_link) . "<br/>" ;
}

// Let's send the data back
header('Content-type: application/json');
print(json_encode($response)) ;

if ($response['error'])
	journalise($userId, 'E', "Error ($response[error]) while cancelling booking #$id of $plane done for $pilot[name] by $booker[name]. $booking_start => $booking_end") ;
else {
	// Warn by email the previous and next bookings if any
	$result = mysqli_query($mysqli_link, "select * from $table_bookings, $table_users
		where r_plane = '$plane' and r_cancel_date is null and r_start = '$booking_end' and (id = r_pilot or id = r_instructor)") 
		or die("Cannot fetch previous booking: " . mysqli_error($mysqli_link)) ;
	email_adjacent($result, $booking, $booker) ;
	$result = mysqli_query($mysqli_link, "select * from $table_bookings, $table_users
		where r_plane = '$plane' and r_cancel_date is null and r_stop = '$booking_start' and (id = r_pilot or id = r_instructor)") 
		or die("Cannot fetch previous booking: " . mysqli_error($mysqli_link)) ;
	email_adjacent($result, $booking, $booker) ;
}

function email_adjacent($result, $booking, $booker) {
	global $managerName, $managerEmail, $userId, $convertToUtf8, $smtp_localhost, $smtp_from ;

	$row = mysqli_fetch_array($result) ;
	if (!$row) return ;
	// No need to warn if next is the same
	if ($row['r_instructor'] == $booking['r_instructor']) return ;
	if ($row['r_pilot'] == $booking['r_pilot']) return ;
	$row['name'] = db2web($row['name']) ; // SQL DB is latin1 and the rest is in UTF-8
	$id = $row['r_id'] ; // Get back the adjacent booking ID
	$email_header = "From: $managerName <$smtp_from>\r\n" ;
	$email_header .= "To: $row[name] <$row[email]>\r\n" ;
	$email_header .= "X-Comment: reservation is $id\r\n" ;
	$email_header .= "References: <booking-$id@$smtp_localhost>\r\n" ;
	$email_header .= "In-Reply-To: <booking-$id@$smtp_localhost>\r\n" ;
	$email_header .= "Thread-Topic: Réservation RAPCS #$id\r\n" ; 
	$email_header .= "Content-Type: text/html; charset=UTF-8\r\n" ;
	$email_message = "<p>Bonjour,</p><p>Pour votre information, suite &agrave; une annulation d'une autre r&eacute;servation par $booker[name], le $row[r_plane] 
		est maintenant disponible du $booking[r_start] au $booking[r_stop]. N'h&eacute;sitez donc pas &agrave; &eacute;tendre votre
		r&eacute;servation.</p>" ;
	@smtp_mail($row['email'], "$row[r_plane] est disponible du $booking[r_start] au $booking[r_stop]", $email_message, $email_header) ;
	journalise($userId, 'I', "Warning $row[name] by email that booking can be extended") ;
}
?>
