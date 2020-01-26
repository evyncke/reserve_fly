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
require_once 'facebook.php' ;

$mime_preferences = array(
	"input-charset" => "UTF-8",
	"output-charset" => "UTF-8",
	"scheme" => "Q") ;

$response = array() ;
$response['error'] = '' ; // Until now: no error :-)

// Parameter sanitization
$plane = strtoupper(trim($_REQUEST['plane'])) ;
if ($plane == '') {
		header('HTTP/1.1 500 Internal Server Error');
		die("Missing parameter: plane") ;
}
$plane = mysqli_real_escape_string($mysqli_link, $plane) ;
$pilot_id = $_REQUEST['pilotId'] ;
if (!is_numeric($pilot_id)) die("Bien essaye... pilot: $pilot_id") ;
$instructor_id = $_REQUEST['instructorId'] ;
if ($instructor_id) {
	if (!is_numeric($instructor_id)) die("Bien essaye... instructor: $instructor_id") ;
	if ($instructor_id == -1) $instructor_id = "NULL" ;
} else
	$instructor_id = "NULL" ;
$customer_id = (isset($_REQUEST['customerId'])) ? intval(trim($_REQUEST['customerId'])) : 0 ;
if (!is_numeric($customer_id)) die("Invalid customerId") ;
$booking_type = $_REQUEST['type'] ;
if (!is_numeric($booking_type)) die("Bien essaye... type: $booking_type") ;
$duration = str_replace(',', '.', $_REQUEST['duration']) ;
if ($duration != '' and !is_numeric($duration) or $duration < 0) die("Bien essaye... duree: $duration") ;
if ($duration == '') $duration = 0 ;
$start = mysqli_real_escape_string($mysqli_link, $_REQUEST['start']) ;
$end = mysqli_real_escape_string($mysqli_link, $_REQUEST['end']) ;
$comment = mysqli_real_escape_string($mysqli_link, $_REQUEST['comment']) ;
$comment_db = mysqli_real_escape_string($mysqli_link, web2db($_REQUEST['comment'])) ;
$crew_wanted = mysqli_real_escape_string($mysqli_link, $_REQUEST['crewWanted']) ;
$pax_wanted = mysqli_real_escape_string($mysqli_link, $_REQUEST['paxWanted']) ;
$from_apt = mysqli_real_escape_string($mysqli_link, $_REQUEST['fromApt']) ;
$via1_apt = mysqli_real_escape_string($mysqli_link, $_REQUEST['via1Apt']) ;
$via2_apt = mysqli_real_escape_string($mysqli_link, $_REQUEST['via2Apt']) ;
$to_apt = mysqli_real_escape_string($mysqli_link, $_REQUEST['toApt']) ;

// Basic checks on dates
$start_date = new DateTime($start) ;
if (!$start_date) $response['error'] .= "$start is not a valid date<br/>" ;
$end_date = new DateTime($end) ;
if (!$end_date) $response['error'] .= "$end is not a valid date<br/>" ;
if ($end_date <= $start_date) $response['error'] .= "La fin doit &ecirc;tre apr&egrave;s le d&eacute;but: $start -> $end.<br/>" ;

// Check on user ids
if ($userId == 0) $response['error'] .= "Vous devez &ecirc;tre connect&eacute; pour faire une r&eacute;servation.<br/>" ;
if ($booking_type == BOOKING_MAINTENANCE) {
	if (! ($userIsMechanic or $userIsInstructor or $userIsAdmin)) $response['error'] .= "Vous n'avez pas le droit de mettre en maintenance.<br/>" ;
} else {
	if ($pilot_id != $userId) {
		if (! ($userIsInstructor or $userIsAdmin)) $response['error'] .= "Vous n'avez pas le droit faire une r&eacute;servation pour un autre pilote.<br/>" ;
	} else {
		if (! ($userIsPilot or $userIsInstructor or $userIsAdmin)) $response['error'] .= "Vous n'avez pas le droit faire une r&eacute;servation.<br/>" ;
	}
}

// Check whether userId is the assigned pilot !
if ($customer_id > 0) {
	$result_customer = mysqli_query($mysqli_link, "SELECT * FROM $table_flights 
		WHERE f_pilot = $pilot_id AND f_id = $customer_id AND f_date_cancelled IS NULL") 
		or die("Cannot check customer: " . mysqli_error($mysqli_link)) ;
	$row_customer = mysqli_fetch_array($result_customer) ;
	mysqli_free_result($result_customer) ;
	if ($row_customer)
		$booking_type = BOOKING_CUSTOMER ; // Override for initiation/discovery flights
	else
		$response['error'] .= "Vol $customer_id n'existe pas.<br/>" ;
}

// Check whether the plane exists and is active
$result = mysqli_query($mysqli_link, "select * from $table_planes where id = '$plane'") or die("Cannot check the plane status:".mysqli_error($mysqli_link)) ;
$plane_row = mysqli_fetch_array($result) ;
if (!$plane_row or $plane_row['actif'] == 0)
	$response['error'] .= "Cet avion ($plane) n'est pas disponible, r&eacute;servation non effectu&eacute;e...<br/>" ;
elseif ($plane_row['actif'] == 2 and !($userIsAdmin || $userIsInstructor))
	$response['error'] .= "Cet avion ($plane) n'est disponible que pour les instructeurs, r&eacute;servation non effectu&eacute;e...<br/>" ;
elseif ($plane_row['ressource'] != 0 and !($userIsAdmin || $userIsInstructor))
	$response['error'] .= "Cette ressource ($plane) n'est disponible que pour les instructeurs, r&eacute;servation non effectu&eacute;e...<br/>" ;
mysqli_free_result($result) ;

function RecentBooking($plane, $userId, $delai_reservation) {
	global $mysqli_link, $table_logbook, $table_bookings ;
	global $message ;

$message .= "<ul>\n" ;
	// First: only look entries with a relevant 'carnet de route' (linked to a booking)
	$result = mysqli_query($mysqli_link, "select l_end, datediff(sysdate(), l_end) as temps_dernier from $table_logbook l join $table_bookings r on l_booking = r_id
		where r_plane = '$plane' and (r_pilot = $userId or (r_instructor is not null and r_instructor = $userId)) and l_booking is not null
		order by l_end desc") or die("Cannot get last reservation: " . mysqli_error($mysqli_link)) ;
	$row = mysqli_fetch_array($result) ;
	if (! $row) {
$message .= "<li>Aucune entr&eacute;e dans le carnet de routes de l'avion $plane pour ce pilote.</li>\n" ;
		mysqli_free_result($result) ;
	} else {
$message .= "<li>Entrée dans le carnet de routes de l'avion $plane pour ce pilote, la dernière réservation date de $row[temps_dernier] jours et la limite club réservation: $delai_reservation jours.</li>\n" ;
		mysqli_free_result($result) ;
		if ($delai_reservation >= $row['temps_dernier']) {
$message .= "</ul>\n" ;
			return TRUE ;
		}
	}

	// Then, look also at all entries in the pilot log book even without any link to carnet de route
	$plane_alt = str_replace('-', '', $plane) ; // alternate form of plane ID with the '-'
	$result = mysqli_query($mysqli_link, "select l_end, datediff(sysdate(), l_end) as temps_dernier 
		from $table_logbook l
		where (l_plane = '$plane' or l_plane = '$plane_alt') and (l_pilot = $userId or (l_instructor is not null and l_instructor = $userId))
		order by l_end desc") or die("Cannot get last reservation in pilot logbook: " . mysqli_error($mysqli_link)) ;
	$row = mysqli_fetch_array($result) ;
	if (! $row) {
$message .= "<li>Aucune entr&eacute;e dans le logbook du pilote pour $plane.</li>\n" ;
		mysqli_free_result($result) ;
$message .= "</ul>\n" ;
		return FALSE ;
	} else {
$message .= "<li>Entrée dans le logbook du pilote pour $plane, la dernière réservation date de $row[temps_dernier] jours et la limite club réservation: $delai_reservation jours.</li>\n" ;
		mysqli_free_result($result) ;
$message .= "</ul>\n" ;
		return $delai_reservation >= $row['temps_dernier'] ;
	}
}

$message="<p>De manière expérimentale, chaque réservation est vérifiée quant aux règles du re-check RAPCS.<p>
	<p><i>Pour l'instant, le pilote ne voit pas cet email: c'est un test. Corrections et améliorations à eric@vyncke.org ;-)</i></p>
	<p>Vérification de $plane (de type $plane_row[classe]) pour $userFullName ($userId), commentaire de la réservation: <i>$comment</i>.</p>\n" ;

// TODO
// Check validity...

// More checks on user
if ($plane_row['ressource'] == 0 and ! ($userIsMechanic or $userIsInstructor or $instructor_id != "NULL")) {
//if (false) {
	// Check all validity ratings
	$result = mysqli_query($mysqli_link, "select *,datediff(sysdate(), expire_date) as delta
		from $table_validity_type t left join $table_validity v on validity_type_id = t.id and jom_id = $userId")
		or die("Erreur systeme lors de la lecture de vos validites: " . mysqli_error($mysqli_link)) ;
	$userValidities = array() ;
	while ($row = mysqli_fetch_array($result)) {
		$userValidities[$row['validity_type_id']] = true ;
	}
	mysqli_free_result($result) ;
	// Not too distant reservation?
	$reservation_permise = RecentBooking($plane, $userId, $plane_row['delai_reservation']) ;
	mysqli_free_result($result) ;
	if (!$reservation_permise) {
$message .= "<p><span style='color: blue;'>Aucune entrée récente dans le logbook pour $plane, regardons l'historique...</span></p>\n" ;
		// If pilot did not book this exact plane, let's try to find whether he/she flew a plane from a 'larger' group...
		$result = mysqli_query($mysqli_link, "select upper(id) as id, classe, delai_reservation
			from $table_planes where id <> '$plane' and ressource = 0
			order by id") or die("Cannot get all active planes:".mysqli_error($mysqli_link)) ;
		while ($row = mysqli_fetch_array($result) and !$reservation_permise) {
			if ($row['id'] == $plane_row['id']) continue ;
$message .= "Vérification de $row[id] (type $row[classe]): \n" ;
			if (planeClassIsMember($plane_row['classe'], $row['classe']))
				$reservation_permise = RecentBooking($row['id'], $userId, $plane_row['delai_reservation']) ; // Only if recent flight !!!
			else
$message .= "&nbsp;&nbsp;<i>Cet avion ($row[classe]) n'entre pas en compte pour le type $plane_row[classe].</i><br/>\n" ;
		}
		mysqli_free_result($result) ;
	}
	$message .= '</p>' ;
	$email_header = "From: $managerName <$smtp_from>\r\n" ;
	if (!$reservation_permise) {
		$message .= "<p style='color: red;'>Cette r&eacute;servation devrait &ecirc;tre refus&eacute;e, mais, accept&eacute;e en phase de test.</p>" ;
		$email_header .= "To: $fleetName <$fleetEmail>\r\n" ;
		@smtp_mail($fleetEmail, substr(iconv_mime_encode('Subject',"Réservation $plane refusée pour $userFullName"), 9), $message, $email_header) ;
	}
} // End of checks for normal pilot
 
// Check whether this period overlaps with other ones
// TODO should give more information about other reservations => do not count(*) but mysqli_num_rows()
$sql = "select * from $table_bookings b join jom_users u on b.r_pilot = u.id
        where r_plane = '$plane' and r_cancel_date is null and
                ('$start' between r_start and date_sub(r_stop, interval 1 minute) or
                date_sub('$end', interval 1 minute) between r_start and r_stop or
		r_start between '$start' and date_sub('$end', interval 1 minute) or
		date_sub(r_stop, interval 1 minute) between '$start' and '$end')
        order by r_start asc limit 0, 1" ;


$response['sql'] = $sql ;
$result = mysqli_query($mysqli_link, $sql) ;
if ($result) {
	$row = mysqli_fetch_array($result) ;
	if ($row) {  
		$row['name'] = db2web($row['name']) ; // SQL DB is latin1 and the rest is in UTF-8
		$response['error'] .= "Cette r&eacute;servation ($plane) est en conflit avec une r&eacute;servation de $row[name] d&eacute;butant le $row[r_start]!<br/>R&eacute;servation non effectu&eacute;e...<br/>" ;
	}
} else
	$response['error'] .= "Cannot check booking ($start ... $stop):" . mysqli_error($mysqli_link) . "<br/>" ;

if ($response['error'] == '') {
	$result = mysqli_query($mysqli_link, "INSERT INTO $table_bookings(r_plane, r_start, r_stop, r_duration, r_pilot, r_instructor, r_comment, r_crew_wanted, r_pax_wanted,
			r_from, r_via1, r_via2, r_to, r_type, r_date, r_address, r_who, r_sequence)
		VALUES('$plane', '$start', '$end', $duration, $pilot_id, $instructor_id, '$comment_db', $crew_wanted, $pax_wanted,
			'$from_apt', '$via1_apt', '$via2_apt', '$to_apt', $booking_type, sysdate(), '" . getClientAddress() . "', $userId, 0)") or die(mysqli_error($mysqli_link));

	if (mysqli_affected_rows($mysqli_link) == 1) {
		$booking_id = mysqli_insert_id($mysqli_link) ;
		$auth = md5($booking_id . $shared_secret) ;
		// If for a customer flight, then let's modify the flight
		if ($customer_id > 0) {
			mysqli_query($mysqli_link, "UPDATE $table_flights SET f_booking = $booking_id, f_date_scheduled = SYSDATE() WHERE f_id = $customer_id")
				or die("Cannot update flight: " . mysqli_error($mysqli_link)) ;
		}
		// Get information abour pilot
		$result = mysqli_query($mysqli_link, "select name, email from jom_users where id = $pilot_id") ;
		$pilot = mysqli_fetch_array($result) ;
		$pilot['name'] = db2web($pilot['name']) ; // SQL DB is latin1 and the rest is in UTF-8
		// If instructor is on board, then get information about instructor
		if ($instructor_id != 'NULL') {
			$result = mysqli_query($mysqli_link, "select name, email from jom_users where id = $instructor_id") ;
			$instructor = mysqli_fetch_array($result) ;
			$instructor['name'] = db2web($instructor['name']) ; // SQL DB is latin1 and the rest is in UTF-8
		}
		// Get information about booker
		$result = mysqli_query($mysqli_link, "select name, email from jom_users where id = $userId") ;
		$booker = mysqli_fetch_array($result) ;
		$booker_quality = 'pilote' ;
		if ($useIsInstructeur)
			$booker_quality = 'instructeur' ;
		elseif ($useIsMechanic)
			$booker_quality = 'm&eacute;cano' ;
		elseif ($useIsAdmin)
			$booker_quality = 'administrateur web' ;
		$booker['name'] = db2web($booker['name']) ; // SQL DB is latin1 and the rest is in UTF-8
		if ($booking_type == BOOKING_MAINTENANCE) {
			$response['message'] = "La maintenance de $plane du $start au $end: est confirm&eacute;e" ;
			$email_subject = "Subject: Confirmation de la mise en maintenance de $plane par $booker[name] [#$booking_id]" ;
			$email_message = "La maintenance du $start au $end sur le $plane avec comme commentaires: <i>$comment</i> " ;
			$email_message .= "est confirm&eacute;e.<br/>" ;
		} else {
			$response['message'] = "La r&eacute;servation de $plane du $start au $end: est confirm&eacute;e" ;
			if ($pilot_id == $userId)
				$email_subject = iconv_mime_encode('Subject',
					"Confirmation d'une nouvelle réservation de $plane pour $pilot[name] [#$booking_id]",
						$mime_preferences) ;
			else
				$email_subject = iconv_mime_encode('Subject',
					"Confirmation d'une nouvelle réservation de $plane par $booker[name] pour $pilot[name]  [#$booking_id]",
						$mime_preferences) ;
			if ($email_subject === FALSE)
				$email_subject = "Subject: Cannot iconv(pilot/$pilot[name])" ;
			$email_message = "La r&eacute;servation du $start au $end sur le <b>$plane</b> " ;
			$email_message .= "avec $pilot[name] en pilote est confirm&eacute;e.<br/>\n" ;
			if ($comment) $email_message .= "Commentaires: <i>$comment</i>.<br/>\n" ;
			if ($instructor_id != 'NULL') $email_message .= "Instructeur vol: $instructor[name] (<a href=\"mailto:$instructor[email]\">$instructor[email]</a>).<br/>\n" ;
		}
		if ($pilot_id != $userId)
			$email_message .= "Cette op&eacute;ration a &eacute;t&eacute; effectu&eacute;e par $booker[name] ($booker_quality)." ;
		$directory_prefix = dirname($_SERVER['REQUEST_URI']) ;
		$request_scheme = ($_SERVER['REQUEST_SCHEME'] != '') ? $_SERVER['REQUEST_SCHEME'] : 'http' ; // TODO pourquoi cela ne fonctionne pas???
		$request_scheme = 'http' ;
		$directory_prefix = '/resa' ;
		$email_message .= "<br/>Vous pouvez g&eacute;rer cette r&eacute;servation via le site ou via ce lien "  .
			"<a href=\"$request_scheme://$_SERVER[SERVER_NAME]$directory_prefix/booking.php?id=$booking_id&auth=$auth\">direct</a> (&agrave; conserver si souhait&eacute;)." ;
		if ($test_mode) $email_message .= "<hr><font color=red><B>Ceci est une version de test</b></font>" ;
//		$email_header = '' ; // Let's use the default From -- currently defined as no-reply
		$email_header = "From: $managerName <$smtp_from>\r\n" ;
		if ($test_mode) {
			$email_header .= "To: eric-test <eric@vyncke.org>\r\n" ;
		} else {
			$email_header .= "To: $pilot[name] <$pilot[email]>\r\n" ;
			$email_recipients = $pilot['email'] ;
			if ($pilot_id != $userId and $booker['email'] != '') { // If booked by somebody else
				$email_header .= "Cc: $booker[name] <$booker[email]>\r\n" ;
				$email_recipients .= ", $booker[email]" ;
			}
			if ($instructor_id != 'NULL') {
				$email_header .= "Cc: $instructor[name] <$instructor[email]>\r\n" ;
				$email_recipients .= ", $instructor[email]" ;
			}
			if ($booking_type == BOOKING_MAINTENANCE) {
				$email_header .= "Cc: $fleetName <$fleetEmail>\r\n" ;
				$email_recipients .= ", $fleetEmail" ;
			}
			if ($bccTo != '') {
					$email_recipients .= ", $bccTo" ;
			}
		}
		$email_header .= "Message-ID: <booking-$booking_id@$smtp_localhost>\r\n" ;
		$email_header .= "Thread-Topic: Réservation RAPCS #$booking_id\r\n" ; 
//		$smtp_info['debug'] = True;
		if ($test_mode)
			@smtp_mail("eric.vyncke@ulg.ac.be", substr($email_subject, 9), $email_message, $email_header) ;
		else
			@smtp_mail($email_recipients, substr($email_subject, 9), $email_message, $email_header) ;
		if ($booking_type == BOOKING_MAINTENANCE)
			journalise($userId, 'W', "$plane is out for maintenance #$booking_ido by $booker[name] ($comment). $start => $end") ;
		else {
			journalise($userId, 'I', "Booking #$booking_id of $plane done for $pilot[name] by $booker[name] ($comment). $start => $end") ;
			// Check for long booking...
			date_default_timezone_set('Europe/Brussels') ;
			$interval = date_diff(date_create($end), date_create($start)) ;
			if ($interval and ($interval->d > 0 or $interval->m > 0)) {
				$email_header = "From: $managerName <$smtp_from>\r\n" ;
				$email_header .= "To: $fleetEmail, $managerName <$managerEmail>\r\n" ;
				$email_header .= "References: <booking-$booking_id@$smtp_localhost>\r\n" ;
				$email_header .= "Thread-Topic: Réservation RAPCS #$booking_id\r\n" ; 
				@smtp_mail("$managerEmail, $fleetEmail", "!!! Longue reservation ($start / $end): " . substr($email_subject, 9), $email_message, "To: $managerName <$managerEmail>, $fleetName <$fleetEmail>\r\n" . $email_header) ;
			}
		}
	} else {
		$response['error'] .= "Un probl&egrave;me technique s'est produit, r&eacute;servation non effectu&eacute;e..." . mysqli_error($mysqli_link) . "<br/>" ;
	}
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

if ($response['error'] != '') {
	journalise($userId, 'E', "Error ($response[error]) while trying to book $plane done for $pilot[name] by $booker[name] ($comment). $start => $end") ;
}
?>
