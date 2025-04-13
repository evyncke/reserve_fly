<?php
/*
   Copyright 2014-2025 Eric Vyncke

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
if (!is_numeric($customer_id)) journalise($userId, "F", "Invalid customerId") ;
$booking_type = $_REQUEST['type'] ;
if (!is_numeric($booking_type)) journalise($userId, "F", "Bien essaye... type: $booking_type") ;
$duration = str_replace(',', '.', $_REQUEST['duration']) ;
if (isset($_REQUEST['duration']) and $duration != '' and (!is_numeric($duration) or $duration < 0)) journalise($userId, "F", "Bien essaye... duree: $duration") ;
if (! isset($_REQUEST['duration']) or $duration == '') $duration = 0 ;
$start = mysqli_real_escape_string($mysqli_link, $_REQUEST['start']) ;
$end = mysqli_real_escape_string($mysqli_link, $_REQUEST['end']) ;
$comment = mysqli_real_escape_string($mysqli_link, $_REQUEST['comment']) ;
$comment_db = mysqli_real_escape_string($mysqli_link, web2db($_REQUEST['comment'])) ;
$crew_wanted = (isset($_REQUEST['crewWanted'])) ? mysqli_real_escape_string($mysqli_link, $_REQUEST['crewWanted']) : 0 ;
$pax_wanted = (isset($_REQUEST['paxWanted'])) ? mysqli_real_escape_string($mysqli_link, $_REQUEST['paxWanted']) : 0 ;
$from_apt = (isset($_REQUEST['fromApt'])) ? mysqli_real_escape_string($mysqli_link, web2db(strtoupper($_REQUEST['fromApt']))) : $default_airport;
$via1_apt = (isset($_REQUEST['via1Apt'])) ? mysqli_real_escape_string($mysqli_link, web2db(strtoupper($_REQUEST['via1Apt']))) : '';
$via2_apt = (isset($_REQUEST['via2Apt'])) ? mysqli_real_escape_string($mysqli_link, web2db(strtoupper($_REQUEST['via2Apt']))) : '';
$to_apt = (isset($_REQUEST['toApt'])) ? mysqli_real_escape_string($mysqli_link, web2db(strtoupper($_REQUEST['toApt']))) : $default_airport;

// Basic checks on dates
$start_date = new DateTime($start) ;
if (!$start_date) $response['error'] .= "$start is not a valid date<br/>" ;
$end_date = new DateTime($end) ;
if (!$end_date) $response['error'] .= "$end is not a valid date<br/>" ;
if ($end_date <= $start_date) $response['error'] .= "La fin doit &ecirc;tre apr&egrave;s le d&eacute;but: $start -> $end.<br/>" ;

// Check on user ids
if ($userId == 0) $response['error'] .= "Vous devez &ecirc;tre connect&eacute; pour faire une r&eacute;servation.<br/>" ;
if ($booking_type == BOOKING_MAINTENANCE) {
	if (! ($userIsMechanic or $userIsInstructor or $userIsAdmin)) 
		$response['error'] .= "Vous n'avez pas le droit de mettre en maintenance.<br/>" ;
} else {
	if ($pilot_id != $userId) {
		if (! ($userIsInstructor or $userIsAdmin)) $response['error'] .= "Vous n'avez pas le droit de faire une r&eacute;servation pour un autre pilote.<br/>" ;
	} else {
		if (! ($userIsPilot or $userIsInstructor or $userIsAdmin)) $response['error'] .= "Vous n'avez pas le droit faire une r&eacute;servation.<br/>" ;
	}
}
// Is the user on the no flight list ?
$blocked_user = false ;
$blocked_msg = '' ;
if ($userNoFlight) {
	journalise($userId, "W", "This pilot $pilot_id is blocked: in userNoFlight group") ;
	$response['error'] .= "Vous &ecirc;tes interdit(e) de vol. Contactez l'a&eacute;roclub." ;
	$blocked_user = true ;
	$blocked_msg =  "<p>Vous &ecirc;tes interdit(e) de vol. Contactez l'a&eacute;roclub.</p>\n" ;
}
// Check whether the user is blocked for some specific reason
$result_blocked = mysqli_query($mysqli_link, "SELECT * FROM $table_blocked WHERE b_jom_id=$pilot_id")
	or journalise($userId, 'E', "Cannot checked whether pilot $pilot_id is blocked: " . mysqli_error($mysqli_link)) ;
$row_blocked = mysqli_fetch_array($result_blocked) ;
if ($row_blocked) {
	journalise($userId, "W", "This pilot $pilot_id is blocked: $row_blocked[b_reason]") ;
	$response['error'] .= "Vous &ecirc;tes interdit(e) de vol: " . db2web($row_blocked['b_reason']) . ". Contactez info@spa-aviation.be" ;
	$blocked_user = true ;
	$blocked_msg =  "<p>Vous &ecirc;tes interdit(e) de vol: <b>" . db2web($row_blocked['b_reason']) . "</b>. Contactez info@spa-aviation.be.</p>\n" ;
}

// Check whether membership fee is paid
if ($membership_year == date('Y') and (!isset($row_fee) or $row_fee['bkf_payment_date'] == '')) {
	$response['error'] .= "Vous n'&ecirc;tes pas en r&egrave;gle de cotisation." ;
	$blocked_user = true ;
	$blocked_msg = "<p>Vous n'&ecirc;tes pas en r&egrave;gle de cotisation." ;
	journalise($userId, "E", "Unpaid membership fee") ;
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

// Get information about pilot
$result = mysqli_query($mysqli_link, "select name, email from $table_users where id = $pilot_id")
	or journalise($userId, "E", "Cannot get info about pilot: " . mysqli_error($mysqli_link));
$pilot = mysqli_fetch_array($result) ;
$pilot['name'] = db2web($pilot['name']) ; // SQL DB is latin1 and the rest is in UTF-8
// Check whether the pilot is on the no fly list
$result = mysqli_query($mysqli_link, "SELECT * FROM jom_user_usergroup_map WHERE user_id = $pilot_id and group_id = $joomla_no_flight") 
	or journalise($userId, "E", "Cannot get info about pilot's group: " . mysqli_error($mysqli_link));
if (mysqli_num_rows($result) > 0) $response['error'] .= "Le pilote est interdit de vol (violation des r&egrave;gles du club (par exemple, non paiement))" ;

// If instructor is on board, then get information about instructor
if ($instructor_id != 'NULL') {
	$result = mysqli_query($mysqli_link, "select name, email from $table_users where id = $instructor_id") ;
	$instructor = mysqli_fetch_array($result) ;
	$instructor['name'] = db2web($instructor['name']) ; // SQL DB is latin1 and the rest is in UTF-8
}
// Get information about booker
$result = mysqli_query($mysqli_link, "select name, email from $table_users where id = $userId") ;
$booker = mysqli_fetch_array($result) ;
$booker_quality = 'pilote' ;
if ($userIsInstructor)
	$booker_quality = 'instructeur' ;
elseif ($userIsMechanic)
	$booker_quality = 'm&eacute;cano' ;
elseif ($userIsAdmin)
	$booker_quality = 'administrateur web' ;
$booker['name'] = db2web($booker['name']) ; // SQL DB is latin1 and the rest is in UTF-8

function RecentBooking($plane, $pilotId, $delai_reservation) {
	global $mysqli_link, $table_logbook, $table_bookings ;
	global $message, $start, $userId ;

$message .= "<ul>\n" ;
	// First: only look entries with a relevant 'carnet de route' (linked to a booking)
	$result = mysqli_query($mysqli_link, "SELECT l_end, DATEDIFF('$start', l_end) AS temps_dernier
		FROM $table_logbook l JOIN $table_bookings r ON l_booking = r_id
		where r_plane = '$plane' AND (r_pilot = $pilotId OR (r_instructor IS NOT NULL AND r_instructor = $pilotId)) AND l_booking IS NOT NULL
		ORDER BY l_end DESC
		LIMIT 1") or journalise($userId, "E", "Cannot get last reservation: " . mysqli_error($mysqli_link)) ;
	$row = mysqli_fetch_array($result) ;
	if (! $row) {
		$message .= "<li>Aucune entr&eacute;e dans le carnet de routes de l'avion $plane pour ce pilote.</li>\n" ;
		mysqli_free_result($result) ;
	} else {
		$message .= "<li>Entrée dans le carnet de routes de l'avion $plane pour ce pilote, le dernier vol date de $row[temps_dernier] jours et la limite club réservation: $delai_reservation jours.</li>\n" ;
		mysqli_free_result($result) ;
		if ($delai_reservation >= $row['temps_dernier']) {
			$message .= "</ul>\n" ;
			return TRUE ;
		}
	}

	// Then, look also at all entries in the pilot log book even without any link to carnet de route
	$plane_alt = str_replace('-', '', $plane) ; // alternate form of plane ID with the '-'
	$result = mysqli_query($mysqli_link, "SELECT l_end, DATEDIFF('$start', l_end) AS temps_dernier 
		FROM $table_logbook l
		WHERE (l_plane = '$plane' OR l_plane = '$plane_alt') AND (l_pilot = $pilotId OR (l_instructor IS NOT NULL AND l_instructor = $pilotId))
		ORDER BY l_end DESC") or journalise($userId, "E", "Cannot get last reservation in pilot logbook: " . mysqli_error($mysqli_link)) ;
	$row = mysqli_fetch_array($result) ;
	if (! $row) {
		$message .= "<li>Aucune entr&eacute;e dans le logbook du pilote pour $plane.</li>\n" ;
		mysqli_free_result($result) ;
		$message .= "</ul>\n" ;
		return FALSE ;
	} else {
		$message .= "<li>Entr&eacute;e dans le logbook du pilote pour $plane, le dernier vol date de $row[temps_dernier] jours et la limite club r&eacute;servation: $delai_reservation jours.</li>\n" ;
		mysqli_free_result($result) ;
		$message .= "</ul>\n" ;
		return $delai_reservation >= $row['temps_dernier'] ;
	}
}

// More checks on user when booking a plane and booked by an non-instructor/mechanic

if ($plane_row['ressource'] == 0 and ! (($userIsMechanic and $booking_type == BOOKING_MAINTENANCE) or $userIsInstructor or $instructor_id != "NULL")) {
//	journalise($userId, "D", "Check club is required: userIsMechanic = $userIsMechanic, userIsInstructor = $userIsInstructor, instructor_id = $instructor_id, pilot_id = $pilot[name]/$pilot_id") ;
//if (false) {
	$intro = "<p>De mani&egrave;re exp&eacute;rimentale, chaque r&eacute;servation est v&eacute;rifi&eacute;e quant au R&egrave;glement d'Ordre Intérieur (ROI) &agrave; propos du re-check RAPCS.<p>
		<p><i>Ce message est envoy&eacute; au pilote, aux instructeurs et aux gestionnaires de la flotte.</i></p>" ;
	if ($comment != '')
		$intro .= "<p>Commentaire de la r&eacute;servation: <i>$comment</i>.</p>\n" ;
	mysqli_free_result($result) ;
	$message = "<p>V&eacute;rification de la r&eacute;servation de $plane (de type $plane_row[classe]) effectu&eacute;e par $userFullName/$userId pour $pilot[name]/$pilot_id." ;
	// Not too distant reservation?
	$reservation_permise = RecentBooking($plane, /*$userId*/ $pilot_id, $plane_row['delai_reservation']) ;
	if (!$reservation_permise) {
		$message .= "<p><span style='color: blue;'>Aucune entr&eacute;e r&eacute;cente dans le logbook pour $plane, regardons l'historique...</span></p>\n" ;
		// If pilot did not book this exact plane, let's try to find whether he/she flew a plane from a 'larger' group...
		$result = mysqli_query($mysqli_link, "SELECT upper(id) AS id, classe, delai_reservation
			FROM $table_planes 
			WHERE id <> '$plane' AND ressource = 0
			ORDER BY id") or journalise($userId, "E", "Cannot get all planes:".mysqli_error($mysqli_link)) ;
		while ($row = mysqli_fetch_array($result) and !$reservation_permise) {
			if ($row['id'] == $plane_row['id']) continue ;
			$message .= "V&eacute;rification de $row[id] (type $row[classe]): \n" ;
			if (planeClassIsMember($plane_row['classe'], $row['classe']))
				$reservation_permise = RecentBooking($row['id'], /*$userId*/ $pilot_id, $plane_row['delai_reservation']) ; // Only if recent flight !!!
			else
				$message .= "&nbsp;&nbsp;<i>Cet avion ($row[classe]) n'entre pas en compte pour le type $plane_row[classe].</i><br/>\n" ;
		}
		mysqli_free_result($result) ;
	}
	$message .= '</p>' ;
// Next checks on validity ratings
	$validity_msg = '' ;
	$userRatingValid = true ;
	$userValidities = array() ;
	$result = mysqli_query($mysqli_link, "SELECT *,DATEDIFF('$end', expire_date) AS delta
		FROM $table_validity_type t 
		LEFT JOIN $table_validity v ON validity_type_id = t.id AND jom_id = $pilot_id")
		or journalise($pilot_id, "E", "Erreur systeme lors de la lecture de des validites: " . mysqli_error($mysqli_link)) ;
	while ($row = mysqli_fetch_array($result)) {
		$userValidities[$row['validity_type_id']] = true ;
		$row['name'] = db2web($row['name']) ;
		if ($row['delta'] == '') { // This validity was not filled in
			if ($row['mandatory'] != 0) {
				$userRatingValid = false ;
				$validity_msg .= "<span style=\"color: red;\">Le profil du pilote ne contient pas $row[name]. Le ROI interdit en ce cas de r&eacute;server un avion. Le pilote doit modifier son profil d'abord.</span><br/>" ;
			}
		} elseif ($row['delta'] > 0) {
			if ($row['mandatory'] != 0) {
				$userRatingValid = false ;
				$validity_msg .= "<span style=\"color: red;\">Le $row[name] du pilote n'est plus valable depuis le $row[expire_date]. Le ROI interdit en ce cas de r&eacute;server un avion.</span><br/>" ;
			} else {
				$validity_msg .= "<span style=\"color: blue;\">Le $row[name] du pilote n'est plus valable depuis le $row[expire_date].</span><br/>" ;
			}
		} elseif ($row['delta'] > - $validity_warning) 
			$validity_msg .= "<span style=\"color: blue;\">Le $row[name] du pilote ne sera plus valable le $row[expire_date]; il sera alors impossible de r&eacute;server un avion pour ce pilote.</span><br/>" ;
	}
	mysqli_free_result($result) ;
	if ($validity_msg == '') 
		$validity_msg = "<p style=\"color: blue;\">Toutes les validit&eacute;s du pilotes sont valables.</p>" ;
	else
		$validity_msg = "<h2>Certificats et ratings</h2><p>$validity_msg</p>" ;
//	if (!$userRatingValid) $reservation_permise = false ;
	if (!$reservation_permise or !$userRatingValid or $blocked_user) {
		journalise($pilot_id, "E", "Check club: Cette réservation pour $plane devrait être refusée...(P=$reservation_permise/R=$userRatingValid/B=$blocked_user") ;
		$email_header = "From: $managerName <$smtp_from>\r\n" ;
		$email_header .= "To: fis@spa-aviation.be, $pilot[name] <$pilot[email]>\r\n" ;
		$email_header .= "Return-Path: <bounce@spa-aviation.be>\r\n" ;  // Will set the MAIL FROM enveloppe by the Pear Mail send()
		$email_recipients = "fis@spa-aviation.be,$pilot[email]" ;
		if (!$userRatingValid) {
			$email_header .= "Cc: RAPCS FIs <fis@spa-aviation.be>, $managerName <$managerEmail>\r\n" ;
			$email_recipients .= ",$managerEmail" ;
			$subject =  "Validité expirée pour $pilot[name]/$userFullName... pour la réservation de $plane" ;
		} elseif ($blocked_user) {
			$email_header .= "Cc: RAPCS CA <ca@spa-aviation.be>\r\n" ;
			$subject = "$pilot[name]/$userFullName est bloqué: " . db2web($row_blocked['b_reason']) ;
		} else {
			$email_header .= "Cc: RAPCS FIs <fis@spa-aviation.be>\r\n" ;
			$subject = "La réservation de $plane devrait être refusée pour $pilot[name]/$userFullName" ;
		} 

		if ($bccTo)
			$email_recipients .= ",$bccTo" ;
		@smtp_mail($email_recipients, substr(iconv_mime_encode('Subject', $subject), 9), $intro  . $blocked_msg . $validity_msg . $message, $email_header) ;
		journalise($pilot_id, "D", "Email sent with $subject") ;
//		@smtp_mail('evyncke@cisco.com', substr(iconv_mime_encode('Subject',"Réservation $plane refusée pour $pilot[name]/$userFullName"), 9), $message, $email_header) ;
	} else if ($bccTo) {
		journalise($pilot_id, "I", "Check club: Cette réservation pour $plane est autorisée") ;
		$email_header = "From: $managerName <$smtp_from>\r\n" ;
		$email_header .= "To: $bccTo\r\n" ;
		@smtp_mail($bccTo, substr(iconv_mime_encode('Subject',"Réservation $plane autorisée pour $pilot[name]/$userFullName"), 9), $intro . $validity_msg . $message, $email_header) ;
	}
} ; // else // End of checks for normal pilot 
//	journalise($pilot_id, "D", "Check club is not required") ;
 
// Check whether this period overlaps with other ones
// TODO should give more information about other reservations => do not count(*) but mysqli_num_rows()
$sql = "select * from $table_bookings b join $table_users u on b.r_pilot = u.id
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
			'$from_apt', '$via1_apt', '$via2_apt', '$to_apt', $booking_type, sysdate(), '" . getClientAddress() . "', $userId, 0)") ;
	if (!$result) { // Failed insertion
		journalise($userId, 'E', 'Cannot insert into DB: ' . mysqli_error($mysqli_link)) ;
		$response['error'] = "Erreur systeme dans la base de donnees, reservation non effectuee" ;
	}
	if ($result and mysqli_affected_rows($mysqli_link) == 1) {
		$booking_id = mysqli_insert_id($mysqli_link) ;
		$auth = md5($booking_id . $shared_secret) ;
		// If for a customer flight, then let's modify the flight
		if ($customer_id > 0) {
			mysqli_query($mysqli_link, "UPDATE $table_flights SET f_booking = $booking_id, f_date_linked = SYSDATE() WHERE f_id = $customer_id")
				or journalise($userId, 'E', "Cannot update flight: " . mysqli_error($mysqli_link)) ;
		}
		if ($booking_type == BOOKING_MAINTENANCE) {
			$response['message'] = "La maintenance de $plane du $start au $end: est confirm&eacute;e" ;
			$email_subject = "🛠 Confirmation de la mise en maintenance de $plane par $booker[name] [#$booking_id]" ;
			$email_message = "<p>La maintenance du $start au $end sur le $plane avec comme commentaires: <i>$comment</i> " ;
			$email_message .= "est confirm&eacute;e.<br/>" ;
		} else {
			$response['message'] = "La r&eacute;servation de $plane du $start au $end: est confirm&eacute;e.<br/>" .
				"<b>Note</b>: apr&egrave;s votre vol, n'oubliez pas d'encoder les index du compteur d&eacute;part et arriv&eacute;e " .
				"et les autres informations demand&eacute;es. " .
				"Sans cela, votre prochaine r&eacute;servation sera impossible. " .
				"Merci de votre coop&eacute;ration." ;
			if ($pilot_id == $userId)
				$email_subject = "✈ Confirmation d'une nouvelle réservation de $plane pour $pilot[name] [#$booking_id]" ;
			else
				$email_subject = "✈ Confirmation d'une nouvelle réservation de $plane par $booker[name] pour $pilot[name]  [#$booking_id]" ;

			$email_message = "<p>La r&eacute;servation du $start au $end sur le <b>$plane</b> " ;
			$email_message .= "avec $pilot[name] en pilote est confirm&eacute;e.<br/>\n" ;
			$email_message .= "<em><b>Note</b>: n'oubliez pas d'encoder les index
                                du compteur d&eacute;part et arriv&eacute;e car toutes les r&eacute;servations futures seront bloqu&eacute;es (y compris pour les &eacute;l&egrave;ves).</em>" ;
			if ($comment) $email_message .= "Commentaires: <i>$comment</i>.<br/>\n" ;
			if ($instructor_id != 'NULL') $email_message .= "Instructeur vol: $instructor[name] (<a href=\"mailto:$instructor[email]\">$instructor[email]</a>).<br/>\n" ;
		}
		if ($pilot_id != $userId)
			$email_message .= "Cette op&eacute;ration a &eacute;t&eacute; effectu&eacute;e par $booker[name] ($booker_quality)." ;
		$email_message .= '</p>' ;
		$directory_prefix = dirname($_SERVER['REQUEST_URI']) ;
		$request_scheme = 'https' ;
		$directory_prefix = '/resa' ;
		$email_message .= "<p>Vous pouvez lier votre calendrier &agrave; cette r&eacute;servation via <a href=\"webcal://$_SERVER[SERVER_NAME]/resa/ics.php?user=$userId&auth=" . md5($userId . $shared_secret) . "\">ce calendrier (iCal)</a>.</p>\n" .
			"<p>Vous pouvez g&eacute;rer (modifier ou annuler) cette r&eacute;servation via le site ou via ce lien "  .
			"<a href=\"$request_scheme://$_SERVER[SERVER_NAME]$directory_prefix/booking.php?id=$booking_id&auth=$auth\">direct</a> (&agrave; conserver si souhait&eacute;).\n" .
			"Une invitation iCalendar est aussi jointe afin de l'importer dans votre calendrier.</p>" ;

		if ($test_mode) $email_message .= "<hr><font color=red><B>Ceci est une version de test</b></font>" ;
		$email_header = "From: $managerName <$smtp_from>\r\n" ;
		if ($test_mode) {
			$email_header .= "To: eric-test <eric@vyncke.org>\r\n" ;
		} else {
			$email_header .= "To: $pilot[name] <$pilot[email]>\r\n" ;
			$email_recipients = $pilot['email'] ;
			if ($pilot_id != $userId and $booker['email'] != '' and ! $userIsInstructor) { // If booked by somebody else who is not a FI (to avoid too many email)
				$email_header .= "Cc: $booker[name] <$booker[email]>\r\n" ;
				$email_recipients .= ", $booker[email]" ;
			}
			// Copy FI only when booking is not done by the FI
			if ($instructor_id != 'NULL' and $instructor_id != $userId) {
				$email_header .= "Cc: $instructor[name] <$instructor[email]>\r\n" ;
				$email_recipients .= ", $instructor[email]" ;
			}
			if ($booking_type == BOOKING_MAINTENANCE) {
				$email_header .= "Cc: $fleetName <$fleetEmail>\r\n" ;
				$email_recipients .= ", $fleetEmail" ;
			}
			if ($bccTo != '') {
					$email_header .= "Bcc: $bccTo\r\n" ;
					$email_recipients .= ", $bccTo" ;
			}
		}
		$email_header .= "Message-ID: <booking-$booking_id@$smtp_localhost>\r\n" ;
		$email_header .= "Thread-Topic: Réservation RAPCS #$booking_id\r\n" ; 
		//
//		$smtp_info['debug'] = True;
		$email_header .= "Return-Path: <bounce@spa-aviation.be>\r\n" ;  // Will set the MAIL FROM enveloppe by the Pear Mail send()
		// Multiple part body to be able to attach .ICS and other files/images
		$headers['MIME-Version'] = '1.0' ;
		$delimiteur = "Part-".md5(uniqid(rand())) ;
		$email_header .= "Content-Type: multipart/mixed; boundary=\"$delimiteur\"\r\n" ;
		$email_message = "Ce texte est envoye en format MIME et HTML donc peut ne pas etre lisible sur cette plateforme.\r\n\r\n" .
			"--$delimiteur\r\n" .
			"Content-Type: text/html; charset=UTF-8\r\n" .
			"Content-Disposition: inline\r\n" .
			"\r\n<html><body>" . 
			$email_message  .
			"</body></html>\r\n\r\n" ;
		// Prepare an ICS file to be attached
		// See also RFC 6047
		require_once('ics_utils.php') ;
		$content = '' ;
		emit_header('') ;
		// Should actually send the booking ! by reconstructing first a pseudo $booking_row from inputs
		$this_result = mysqli_query($mysqli_link, "SELECT *,u.name AS full_name, DATE_SUB(r_start, INTERVAL 1 HOUR) AS alert
			FROM $table_bookings b JOIN $table_users u ON b.r_pilot = u.id, $table_person p
			WHERE r_id = $booking_id") or journalise($userId, "E", "Impossible de lire les reservations: " . mysqli_error($mysqli_link));	
		emit_booking(mysqli_fetch_array($this_result)) ; // Should be there as it is just created	
		emit_trailer() ;
		$email_message .= "--$delimiteur\r\n" .
			"Content-Type: text/calendar; charset=\"utf-8\"; method=REQUEST; name=\"booking-$booking_id.ics\"\r\n" .
			"Content-Disposition: attachment; filename=\"booking-$booking_id.ics\"\r\n" .
			"Content-ID: <booking-$booking_id-ics@" . $smtp_info['localhost'] . ">\r\n" . 
			"Content-Transfer-Encoding: base64\r\n" .
			"\r\n" .
			chunk_split(base64_encode($content)) .
			"\r\n\r\n" .
			"--$delimiteur--\r\n"; // last delimiter must be followed by --
		// Now let's send it !
		if ($test_mode)
			@smtp_mail("eric.vyncke@ulg.ac.be", $email_subject, $email_message, $email_header) ;
		else
			@smtp_mail($email_recipients, $email_subject, $email_message, $email_header) ;
		if ($booking_type == BOOKING_MAINTENANCE)
			journalise($userId, 'W', "$plane is out for maintenance #$booking_id by $booker[name] ($comment). $start => $end") ;
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
				@smtp_mail("$managerEmail, $fleetEmail", "!!! Longue reservation ($start / $end): " . $email_subject, $email_message, "To: $managerName <$managerEmail>, $fleetName <$fleetEmail>\r\n" . $email_header) ;
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
