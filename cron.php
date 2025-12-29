<pre>
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

// This script should be run once per hour between two rental intervals, such as H+07 when the rental starts at 00, 15, 30, and 45
//
// It sends:
// - a rental reminder 24 hours before with a link to modify the reservation
// - a post-flight to enter the engine hours, at the first opportinity AFTER booking start

// TODO: every month, if no connection in the last month & if pilot/student/instructor => send reminder about the userid
// TODO: send the list of other pilots on the plane on the same day with mobile and email

putenv('LANG=') ;

require_once 'dbi.php' ;

// Let's also get some data from Odoo
require_once 'odoo.class.php' ;
$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;

//$test_mode =true ;
$debug = true ;
// SMTP email debuging & optimization
$smtp_info['debug'] = True;
$smtp_info['persist'] = True;
$managerEmail = $smtp_from ; // Allow more debugging

print(date('Y-m-d H:i:s').": starting.\n") ;
$load = sys_getloadavg(); 
// TODO also use http://php.net/manual/fr/function.getrusage.php
//journalise(0, "I", "Start of hourly cron; CPU load $load[0]/$load[1]/$load[2].") ;

print(date('Y-m-d H:i:s').": locale = " . setlocale(LC_ALL, 'fr_BE', 'fr_FR', 'fr') . "\n"); // Test for iconv
$mime_preferences = array(
	"input-charset" => "UTF-8",
	"output-charset" => "UTF-8",
	"scheme" => "Q") ;


function allBookings($plane, $day, $me) {
	global $table_bookings, $table_person, $table_users, $mysqli_link, $userId ;

	$result = mysqli_query($mysqli_link, "select *, time(r_start) as start, time(r_stop) as stop, u.name as full_name
		from $table_bookings b join $table_users u on b.r_pilot = u.id, $table_person p
		where p.jom_id = u.id and date(r_start) = '$day' and r_plane = '$plane' and r_pilot != $me and r_type != " . BOOKING_MAINTENANCE . "
		and r_cancel_date is null
		order by r_start") or journalise($userId, 'E', "allBookings($plane, $day, $me) : " . mysqli_error($mysqli_link)) ;
	if (mysqli_num_rows($result) == 0)
		return "\n<p><i>Pour votre information, vous &ecirc;tes le/la seul(e) &agrave; avoir r&eacute;serv&eacute; cet avion ce jour-l&agrave;.</i></p>\n" ;
	else {
		$msg = "\n<p><i>Pour votre information, d'autres pilotes ont r&eacute;serv&eacute; cet avion le m&ecirc;me jour (utile en cas de retard par exemple):
<table border=\"1\">
<tr><td><b>De</b></td><td><b>Fin</b></td><td><b>Nom</b></td><td><b>Mobile</b></td><td><b>E-mail</b></td><td><b>Commentaire</b></td></tr>\n" ;
		while ($row = mysqli_fetch_array($result)) {
			$row['full_name'] = db2web($row['full_name']) ; // SQL DB is latin1 and the rest is in UTF-8
			$row['r_comment'] = db2web($row['r_comment']) ; // SQL DB is latin1 and the rest is in UTF-8
			$msg .= "<tr><td>$row[start]</td><td>$row[stop]</td><td>$row[full_name]</td><td><a href=\"tel:$row[cell_phone]\">$row[cell_phone]</a></td><td><a href=\"mailto:$row[email]\">$row[email]</a></td><td>$row[r_comment]</tr>\n" ;
		}
		$msg .= "\n</table>\n</p>\n" ;
		return $msg ;
	}
}

// Reminder 24 hours before the flight

print(date('Y-m-d H:i:s').": start of booking reminder(s).\n") ;
$flight_reminders = 0 ;
$result = mysqli_query($mysqli_link, "select *,u.name as full_name
	from $table_bookings b join $table_users u on b.r_pilot = u.id join $table_planes a on r_plane = a.id, $table_person p
	where a.actif = 1 and a.ressource = 0 and p.jom_id = u.id and date_add(sysdate(), interval 23 hour) < r_start
	and date_add(sysdate(), interval 24 hour) > r_start and r_cancel_date is null")
	or die(date('Y-m-d H:i:s').": cannot find next day bookings, " . mysqli_error($mysqli_link)) ;
$tomorrow = date('Y-m-d', strtotime('tomorrow')) ;
while ($row = mysqli_fetch_array($result)) {
	$booking_id = $row['r_id'] ;
	if ($debug) print(date('Y-m-d H:i:s').": processing booking $booking_id from $row[r_start] on $row[r_plane] for $row[full_name].\n") ;
	if ($row['r_type'] == BOOKING_MAINTENANCE) {
		if ($debug) print(date('Y-m-d H:i:s').": ignoring booking $booking_id as it is a maintenance.\n") ;
		continue ;
	}
	$auth = md5($booking_id . $shared_secret) ;
	$row['full_name'] = db2web($row['full_name']) ; // SQL DB is latin1 and the rest is in UTF-8
	$row['first_name'] = db2web($row['first_name']) ; // SQL DB is latin1 and the rest is in UTF-8
	$row['r_comment'] = db2web($row['r_comment']) ; // SQL DB is latin1 and the rest is in UTF-8
	$result_booker = mysqli_query($mysqli_link, "select name, email from $table_users where id = $row[r_who]") ;
	$booker = mysqli_fetch_array($result_booker) ;
	$booker['name'] = db2web($booker['name']) ; // SQL DB is latin1 and the rest is in UTF-8
	if ($row['r_instructor'] != '') {
		if ($row['r_instructor'] == $row['r_pilot']) continue ; // Don't send a reminder when FI prebook a flight
		$result_instructor = mysqli_query($mysqli_link, "select name, email from $table_users where id = $row[r_instructor]") ;
		$instructor = mysqli_fetch_array($result_instructor) ;
		$instructor['name'] = db2web($instructor['name']) ; // SQL DB is latin1 and the rest is in UTF-8
	}
	if ($row['r_pilot'] == $row['r_who'])
		$email_subject = "Rappel ⏰ de la réservation de $row[r_plane] pour $row[full_name] [#$booking_id]" ;
	else
		$email_subject = "Rappel ⏰ de la réservation de $row[r_plane] par $booker[name] pour $row[full_name] [#$booking_id]" ;
	if ($email_subject === FALSE)
		$email_subject = "Cannot iconv(pilot/$row[name])" ;
	$email_message = "<p>$row[first_name],</p>" ;
	$email_message .= "<p>&agrave; titre informatif, voici un rappel de la r&eacute;servation du $row[r_start] au $row[r_stop] sur le $row[r_plane] " ;
	if ($row['comment'] != '')
		$email_message .= "avec comme commentaire: <i>$row[r_comment]</i> " ;
	$email_message .= "avec $row[full_name] en pilote.<br/>" ;
	if ($row['r_instructeur'] != '')
		$email_message .= "Votre instructeur est: $instructor[name].<br/>" ;
	if ($row['r_pilot'] != $row['r_who'])
		$email_message .= "Cette op&eacute;ration a &eacute;t&eacute; effectu&eacute;e par $booker[name]." ;
	$directory_prefix = dirname($_SERVER['REQUEST_URI']) ;
	$email_message .= "</p>\n<p>Vous pouvez g&eacute;rer voire annuler cette r&eacute;servation et remplir le carnet de routes via le site ou via ce lien \n"  .
		"<a href=\"http://$_SERVER[SERVER_NAME]$directory_prefix/booking.php?id=$booking_id&auth=$auth\">direct</a> \n" .
		"(&agrave; conserver si souhait&eacute; et pr&eacute;vu pour smartphones et tablettes).</p>\n" ;
	$email_message .= allBookings($row['r_plane'], $tomorrow, $row['r_pilot']) ;
	if ($test_mode) $email_message .= "<hr><p><font color=red><B>Ceci est une version de test</b></font></p>" ;
	$email_header = "From: $managerName <$smtp_from>\r\n" ;
	if (!$test_mode) {
		$email_header .= "To: $row[full_name] <$row[email]>\r\n" ;
		$email_recipients = $row['email'] ;
		// FIs complains to have too many emails...
//		if ($row['r_instructor'] != '') {
//			$email_header .= "Cc: $instructor[name] <$instructor[email]>\r\n" ;
//			$email_recipients .= ", $instructor[email]" ;
//		}
// Instructors are complaining to have too many emails... so do not copy them when they booked a DC flight
		if ($row['r_pilot'] != $row['r_who'] and $row['r_instructor'] != $row['r_who']) {
			$email_header .= "Cc: $booker[name] <$booker[email]>\r\n" ;
			$email_recipients .= ", $booker[email]" ;
		}
		if ($bccTo != '') {
			$email_recipients .= ", $bccTo" ;
		}
	}
	$email_header .= "X-Comment: reservation is $booking_id\r\n" ;
	$email_header .= "References: <booking-$booking_id@$smtp_localhost>\r\n" ;
	$email_header .= "In-Reply-To: <booking-$booking_id@$smtp_localhost>\r\n" ;
	$email_header .= "Thread-Topic: Réservation RAPCS #$booking_id\r\n" ; 
	$delimiteur = "Part=".md5(uniqid(rand())) ;
	$email_header .= "Content-Type: multipart/mixed; boundary=\"$delimiteur\"\r\n" ;
	$email_message = "Ce texte est envoye en format MIME et HTML donc peut-etre pas lisible sur cette plateforme.\r\n" .
		"--$delimiteur\r\n" .
		"Content-Type: text/html; charset=UTF-8\r\n" .
		"\r\n" . 
		"<html><body>$email_message</body></html>" .
		"\r\n\r\n" .
		"--$delimiteur--\r\n" ;
//	$email_header .= "Content-Type: text/html; charset=UTF-8\r\n" ;
	$email_header .= "Return-Path: <bounce@spa-aviation.be>\r\n" ;  // Will set the MAIL FROM enveloppe by the Pear Mail send()
	if ($test_mode)
		smtp_mail("eric.vyncke@ulg.ac.be", $email_subject, $email_message, $email_header) ;
	else
		@smtp_mail($email_recipients, $email_subject, $email_message, $email_header) ;
	$flight_reminders ++ ;
	print(date('Y-m-d H:i:s').": $flight_reminders flight reminder(s) sent.\n") ;
}

// Reminder after start to enter engine time
print(date('Y-m-d H:i:s').": start of log book reminder before the flight(s).\n") ;

$engine_reminders = 0 ;
$sql ="select *,u.name as full_name
from $table_bookings b join $table_users u on b.r_pilot = u.id join $table_planes a on r_plane = a.id, $table_person p
where a.actif = 1 and a.ressource = 0 and p.jom_id = u.id and date_sub(sysdate(), interval 1 hour) <= r_start
and r_start < sysdate() and r_cancel_date is null" ;
print(date('Y-m-d H:i:s').": running $sql.\n") ;
$result = mysqli_query($mysqli_link, $sql)
	or die(date('Y-m-d H:i:s').": cannot find current bookings, " . mysqli_error($mysqli_link)) ;
$today = date('Y-m-d') ;
while ($row = mysqli_fetch_array($result)) {
	$booking_id = $row['r_id'] ;
	if ($debug) print(date('Y-m-d H:i:s').": processing booking $booking_id from $row[r_start] on $row[r_plane] for $row[full_name].\n") ;
	if ($row['r_type'] == BOOKING_MAINTENANCE) {
		if ($debug) print(date('Y-m-d H:i:s').": ignoring booking $booking_id as it is a maintenance.\n") ;
		continue ;
	}
	$auth = md5($booking_id . $shared_secret) ;
	$row['full_name'] = db2web($row['full_name']) ; // SQL DB is latin1 and the rest is in UTF-8
	$row['first_name'] = db2web($row['first_name']) ; // SQL DB is latin1 and the rest is in UTF-8
	if ($row['first_name'] == '') $row['first_name'] = '<i>[Votre profil est incomplet et votre pr&eacute;nom est inconnu]</i>' ;
	$row['r_comment'] = db2web($row['r_comment']) ; // SQL DB is latin1 and the rest is in UTF-8
	$result_booker = mysqli_query($mysqli_link, "select name, email from $table_users where id = $row[r_who]") 
		or journalise($row['r_who'], 'E', "Cannot find user $row[r_rwho] in $table_users: " . mysqli_error($mysqli_link)) ;
	$booker = mysqli_fetch_array($result_booker) ;
	$booker['name'] = db2web($booker['name']) ; // SQL DB is latin1 and the rest is in UTF-8
	$email_subject = "Ne pas oublier d'entrer les heures ⏱ moteur du $row[r_plane] pour $row[full_name] [#$booking_id]" ;
	$email_message = "<p>$row[first_name],</p>" ;
	$email_message .= "<p>L'encodage des heures moteur, des heures de vol, des a&eacute;roports de d&eacute;part et de destination ainsi " .
		"que du nombre de passagers est devenu <b>OBLIGATOIRE</b> apr&egrave;s chaque vol.<p>" .
		"Cette d&eacute;marche est indispensable pour une gestion efficace de la flotte (planification de la maintenance des avions, " .
		"aide &agrave; la facturation des heures de vol, ...).</p>" .
		"<p>Merci d’entrer ces données en utilisant ce lien : " . 
		"<a href=\"https://www.spa-aviation.be/resa/IntroCarnetVol.php?id=$booking_id&auth=$auth\">encoder les index</a>.</p>" .
		"<p>Dans le cas contraire, le système vous bloquera et il ne vous sera plus possible d'effectuer votre prochaine r&eacute;servation.</p>" .
		"<p>Merci pour votre compréhension et votre collaboration.</p>" .
		"<p>Le conseil d'administration</p>" .
		"<hr><p>Cet email concerne la r&eacute;servation du $row[r_start] au $row[r_stop] sur le $row[r_plane] \n" .
		"avec $row[full_name] en tant que pilote.</p>\n" .
		"<hr><p>Il est &agrave; noter que l'entr&eacute;e par informatique ne remplace pas l'entr&eacute;e manuelle dans le carnet de route!</p>\n" ;
	$email_message .= allBookings($row['r_plane'], $today, $row['r_pilot']) ;
	if ($test_mode) $email_message .= "<hr><p><font color=red><B>Ceci est une version de test</b></font>.</p>" ;
	$email_header = "From: $managerName <$smtp_from>\r\n" ;
	if (! $test_mode) {
		$email_header .= "To: $row[full_name] <$row[email]>\r\n" ;
		$email_recipients = $row['email'] ;
// Instructors are complaining to have too many emails...
		if ($row['r_pilot'] != $row['r_who'] and $row['r_who'] != $row['r_instructor']) {
			$email_header .= "Cc: $booker[name] <$booker[email]>\r\n" ;
			$email_recipients .= ", $booker[email]" ;
		}
		if ($bccTo != '') {
			$email_header .= "Bcc: $bccTo\r\n" ;
			$email_recipients .= ", $bccTo" ;
		}
	}
	$email_header .= "X-Comment: reservation is $booking_id\r\n" ;
	$email_header .= "References: <booking-$booking_id@$smtp_localhost>\r\n" ;
	$email_header .= "In-Reply-To: <booking-$booking_id@$smtp_localhost>\r\n" ;
	$email_header .= "Thread-Topic: Réservation RAPCS #$booking_id\r\n" ; 
	$email_header .= "Content-Type: text/html; charset=UTF-8\r\n" ;
	$email_header .= "Return-Path: <bounce@spa-aviation.be>\r\n" ;  // Will set the MAIL FROM enveloppe by the Pear Mail send()
	if ($test_mode)
		smtp_mail("eric.vyncke@ulg.ac.be", $email_subject, $email_message, $email_header) ;
	else
		@smtp_mail($email_recipients, $email_subject, $email_message, $email_header) ;
	$engine_reminders ++ ;
	print(date('Y-m-d H:i:s').": before flight engine reminder sent by email to $email_recipients.\n") ;
}
print(date('Y-m-d H:i:s').": total of $engine_reminders engine reminders sent.\n") ;

// Reminder after start to enter engine time

if (date('G') == 6) { // Send reminders _AFTER_ the flight, once a day in early morning
print(date('Y-m-d H:i:s').": start of log book reminder(s) after 3 days after the flight(s).\n") ;

$engine_reminders = 0 ;
$sql ="SELECT *,u.name AS full_name
FROM $table_bookings b JOIN $table_users u ON b.r_pilot = u.id JOIN $table_planes a ON r_plane = a.id
LEFT JOIN $table_logbook AS l ON l.l_booking = r_id, $table_person p
WHERE a.actif = 1 AND a.ressource = 0 AND p.jom_id = u.id AND DATE_SUB(SYSDATE(), INTERVAL 3 DAY) <= r_start
AND r_start <= DATE_SUB(SYSDATE(), INTERVAL 1 DAY) AND r_cancel_date IS NULL AND l.l_start IS NULL" ;
print(date('Y-m-d H:i:s').": running $sql.\n") ;
$result = mysqli_query($mysqli_link, $sql)
	or die(date('Y-m-d H:i:s').": cannot find current bookings without any logs, " . mysqli_error($mysqli_link)) ;
$today = date('Y-m-d') ;
while ($row = mysqli_fetch_array($result)) {
	$booking_id = $row['r_id'] ;
	if ($debug) print(date('Y-m-d H:i:s').": processing booking $booking_id from $row[r_start] on $row[r_plane] for $row[full_name].\n") ;
	if ($row['r_type'] == BOOKING_MAINTENANCE) {
		if ($debug) print(date('Y-m-d H:i:s').": ignoring booking $booking_id as it is a maintenance.\n") ;
		continue ;
	}
	$auth = md5($booking_id . $shared_secret) ;
	$row['full_name'] = db2web($row['full_name']) ; // SQL DB is latin1 and the rest is in UTF-8
	$row['first_name'] = db2web($row['first_name']) ; // SQL DB is latin1 and the rest is in UTF-8
	if ($row['first_name'] == '') $row['first_name'] = '<i>[Votre profil est incomplet et votre pr&eacute;nom est inconnu]</i>' ;
	$row['r_comment'] = db2web($row['r_comment']) ; // SQL DB is latin1 and the rest is in UTF-8
	$result_booker = mysqli_query($mysqli_link, "select name, email from $table_users where id = $row[r_who]") 
		or journalise($row['r_who'], 'E', "Cannot find user $row[r_rwho] in $table_users: " . mysqli_error($mysqli_link)) ;
	$booker = mysqli_fetch_array($result_booker) ;
	$booker['name'] = db2web($booker['name']) ; // SQL DB is latin1 and the rest is in UTF-8
	$email_subject = "Rappel: entrer les heures ⏱ moteur de votre vol sur $row[r_plane] du $row[r_start] [#$booking_id]" ;
	$email_message = "<p>$row[first_name],</p>\n" ;
	$email_message .= "<p>Il est important que tous les pilotes et &eacute;l&egrave;ves respectent le r&egrave;glement d'ordre int&eacute;rieur du RAPCS.</p>\n" .
		"<p>Afin de garder une trace des compteurs moteur des avions et de planifier les maintenances, et de v&eacute;rifier si les pilotes respectent \n" .
		"les conditions <i>check club</i> avant de prendre un avion, le RAPCS demande\n" .
		"&agrave; tous les pilotes et &eacute;l&egrave;ves d'entrer les heures moteur (et en option les heures de vol ainsi que les a&eacute;roports de d&eacute;part et de destination).\n" .
		" <b>Nous comptons tous sur vous</b>.</p>\n" .
		"<p>Cet email concerne la r&eacute;servation du $row[r_start] au $row[r_stop] sur le $row[r_plane] \n" .
		"avec $row[full_name] en tant que pilote.</p>\n" ;
	$directory_prefix = dirname($_SERVER['REQUEST_URI']) ;
	$email_message .= "<p>Vous pouvez entrer les donn&eacute;es dans le carnet de route de cette r&eacute;servation via ce lien \n"  .
		"<a href=\"https://$_SERVER[SERVER_NAME]$directory_prefix/booking.php?id=$booking_id&auth=$auth\">direct</a> \n" .
		"(&agrave; conserver si souhait&eacute; ou  ce lien pr&eacute;vu \n" .
		"<a href=\"https://resa.spa-aviation.be/mobile_logbook.php?id=$booking_id&auth=$auth\">pour smartphones et tablettes</a>). Vous pouvez aussi cliquer sur n'importe quelle \n" .
		"r&eacute;servation du pass&eacute; afin de mettre &agrave; jour le carnet de route et vos heures voire d'annuler a posteriori une r&eacute;servation.</p>\n" .
		"<hr><p>Il est &agrave; noter que l'entr&eacute;e par informatique ne remplace pas l'entr&eacute;e manuelle dans le carnet de route!</p>\n" ;
	if ($test_mode) $email_message .= "<hr><font color=red><B>Ceci est une version de test</b></font>" ;
	$email_header = "From: $managerName <$smtp_from>\r\n" ;
	if (! $test_mode or TRUE) {
		$email_header .= "To: $row[full_name] <$row[email]>\r\n" ;
		$email_recipients = $row['email'] ;
// Instructors (the only COVID-19 bookers) are complaining to have too many emails...
//		if ($row['r_pilot'] != $row['r_who']) {
//			$email_header .= "Cc: $booker[name] <$booker[email]>\r\n" ;
//			$email_recipients .= ", $booker[email]" ;
//		}
		if ($bccTo != '') {
			$email_header .= "Bcc: $bccTo\r\n" ;
			$email_recipients .= ", $bccTo" ;
		}
	}
	$email_header .= "X-Comment: reservation is $booking_id\r\n" ;
	$email_header .= "References: <booking-$booking_id@$smtp_localhost>\r\n" ;
	$email_header .= "In-Reply-To: <booking-$booking_id@$smtp_localhost>\r\n" ;
	$email_header .= "Thread-Topic: Réservation RAPCS #$booking_id\r\n" ; 
	$email_header .= "Content-Type: text/html; charset=UTF-8\r\n" ;
	$email_header .= "Return-Path: <bounce@spa-aviation.be>\r\n" ;  // Will set the MAIL FROM enveloppe by the Pear Mail send()
	if ($test_mode)
		smtp_mail("eric.vyncke@uliege.be", $email_subject, $email_message, $email_header) ;
	else
		@smtp_mail($email_recipients, $email_subject, $email_message, $email_header) ;
	$engine_reminders ++ ;
	print(date('Y-m-d H:i:s').": after flight engine reminder(s) (after flight) sent by email to $email_recipients.\n") ;
	if (! is_resource($mysqli_link)) { // Naive ? attempt to reconnect in case of lost connection...
		$mysqli_link = mysqli_connect($db_host, $db_user, $db_password) ;
		if (! $mysqli_link) die("Impossible de se connecter a MySQL:" . mysqli_connect_error()) ;
		if (! mysqli_select_db($mysqli_link, $db_name)) die("Impossible d'ouvrir la base de donnees:" . mysqli_error($mysqli_link)) ;
	}
}
print(date('Y-m-d H:i:s').": total of $engine_reminders engine reminders (after flight) sent.\n") ;
}

if (! is_resource($mysqli_link)) { // Naive ? attempt to reconnect in case of lost connection...
		$mysqli_link = mysqli_connect($db_host, $db_user, $db_password) ;
		if (! $mysqli_link) die("Impossible de se connecter a MySQL:" . mysqli_connect_error()) ;
		if (! mysqli_select_db($mysqli_link, $db_name)) die("Impossible d'ouvrir la base de donnees:" . mysqli_error($mysqli_link)) ;}
// Vérifier si tous les pilotes/élèves/membres ont bel et bien une entrée dans la table $rapcs_person (ex OpenFlyers)
// Ajouter/enlever si nécessaire
print(date('Y-m-d H:i:s').": checking entries in $table_person.\n") ;
$result = mysqli_query($mysqli_link, "select id,name,email,username
	from $table_users u join $table_user_usergroup_map g on u.id = g.user_id and g.group_id in ($joomla_member_group, $joomla_student_group, $joomla_pilot_group, $joomla_instructor_group, $joomla_admin_group)
	where not exists (select * from $table_person where u.id = jom_id)
	group by id")
	or die(date('Y-m-d H:i:s').": cannot read $table_person, " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
	print(date('Y-m-d H:i:s').": $row[name]/$row[email]/$row[id] has no entry in $table_person.\n") ;
	journalise($row['id'], 'W', "$row[name]/$row[email]/$row[id] has no entry in $table_person.") ;
	// Let's re-use an existing entry for the same users
	mysqli_query($mysqli_link, "UPDATE $table_person SET jom_id = $row[id] WHERE name='$row[name]' AND email='$row[email]'")
		or journalise($row['id'], 'E', " cannot replace jom_id=$row[id] into $table_person($row[name], $row[email]): " . mysqli_error($mysqli_link)) ;
	$status = mysqli_affected_rows($mysqli_link) > 0 ; // Check whether some rows were updated
	if (!$status) // Else, create a new row
		$status = mysqli_query($mysqli_link, "INSERT INTO $table_person(jom_id, name, email) 
				VALUES($row[id], '$row[name]', '$row[email]')") ;
	if (!$status) {
			print(date('Y-m-d H:i:s').": cannot insert into $table_person($row[id], $row[name], $row[email]): " . mysqli_error($mysqli_link) . "\n") ;
			journalise($row['id'], 'E', " cannot insert into $table_person($row[id], $row[name], $row[email]): " . mysqli_error($mysqli_link)) ;
	} else {
		$email_subject = "Bienvenue sur le site des réservations du RAPCS" ;
        $email_message = "<p>Bonjour,</p><p>Vous avez d&eacute;j&agrave; re&ccedil;u un email avec votre identifiant et votre mot de passe pour le site du RAPCS:\n" ;
		$email_message .= "<a href=https://www.spa-aviation.be>www.spa-aviation.be</a>. Mais, ce m&ecirc;me identifiant vous permet aussi de visualiser toutes les r&eacute;servations des avions\n" ;
		$email_message .= "(en tant qu'&eacute;l&egrave;ve ou membre non navigant vous ne pouvez pas en r&eacute;server un)" ;
		$email_message .= ". La partie r&eacute;servation est accessible via le menu <b>Avions</b> ou\n" ;
		$email_message .= "directement via <a href=https://resa.spa-aviation.be/>resa.spa-aviation.be/</a> (voire <a href=https://m.spa-aviation.be>m.spa-aviation.be</a> pour mobile).\n" ;
		$email_message .= " Afin de conna&icirc;tre les autres membres de notre club\n" ;
		$email_message .= "il vous est conseill&eacute; de compl&egrave;ter votre profil: " ;
		$email_message .= "<a href=https://www.spa-aviation.be/resa/mobile_profile.php>profil r&eacute;servation</a>; profitez-en pour mettre une photo de vous afin de vous faire reconna&icirc;tre ;-).</p>\n" ;
		$email_message .= "<p>Pour rappel, votre identifiant est <b>$row[username]</b> (vous pouvez le changer en contactant <a href=mailto:webmaster@spa-aviation.be>webmaster@spa-aviation.be</a>).</p>\r\n" ;
		$email_message .= "<hr>Ceci est un message automatique. En cas de soucis, veuillez contacter <a href=mailto:webmaster@spa-aviation.be>webmaster@spa-aviation.be</a>." ;
		if ($test_mode) $email_message .= "<hr><font color=red><B>Ceci est une version de test</b></font>" ;
		$email_header = "From: Webmaster RAPCS <webmaster@spa-aviation.be>\r\n" ;
		$email_header .= "To: $row[name] <$row[email]>\r\n" ;
		$email_header .= "Return-Path: <bounce@spa-aviation.be>\r\n" ;  // Will set the MAIL FROM enveloppe by the Pear Mail send()
		if ($bccTo != '') $email_header .= "Bcc: $bccTo\r\n" ;
		$email_header .= "Bcc: eric.vyncke@ulg.ac.be\r\n" ; // Test 2023-10-16 to see the email content
		$email_header .= "X-Comment: joomla user is $row[jom_id]\r\n" ;
		if ($test_mode)
			smtp_mail("eric.vyncke@ulg.ac.be", $email_subject, $email_message, "Content-Type: text/html; charset=\"UTF-8\"\r\n") ;
		else
			@smtp_mail("$row[email]", $email_subject, $email_message, $email_header) ;
	}
}

// Vérifier si tous les pilotes/élèves (club) existent en $table_users (Joomla)
// enlever si nécessaire
print(date('Y-m-d H:i:s').": checking whether entries $table_person exist in $table_users.\n") ;
$result = mysqli_query($mysqli_link, "SELECT *
	FROM $table_person p
	WHERE p.jom_id IS NOT NULL AND NOT EXISTS ( SELECT * FROM $table_users u WHERE u.id = p.jom_id)")
	or die(date('Y-m-d H:i:s').": cannot read $table_users and $table_person, " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
	print(date('Y-m-d H:i:s').": $row[name]/$row[email]/$row[id] does not exist in Joomla (previously known as $row[jom_id]") ;
	journalise($row['jom_id'], 'W', "Member $row[name]/$row[email]/$row[id] does not exist in Joomla (previously known as $row[jom_id]), setting it to NULL.") ;
	mysqli_query($mysqli_link, "UPDATE $table_person SET jom_id = NULL WHERE id = $row[id]")
		or journalise(0, "E", "Cannot nullify joomla ID($row[jom_id]/$row[id]: " . mysqli_error($mysqli_link)) ;
}

// Vérifier si tous les pilotes/élèves ont des informations équivalentes en $table_users and $rapcs_person (ex OpenFlyers)
// Ajouter/enlever si nécessaire
print(date('Y-m-d H:i:s').": checking email/name entries in $table_users and $table_person.\n") ;
// $result = mysqli_query($mysqli_link, "select *, u.id as j_id, u.email as j_email, p.email as p_email, u.name as j_name, p.user_name as p_name
$result = mysqli_query($mysqli_link, "select *, u.id as j_id, 
		u.email as j_email, p.email as p_email, u.name as j_name, p.name as p_name
		from $table_users u join $table_user_usergroup_map g on u.id = g.user_id and g.group_id in ($joomla_student_group, $joomla_pilot_group, $joomla_instructor_group, $joomla_admin_group, $joomla_member_group)
		join $table_person p on u.id = p.jom_id")
	or die(date('Y-m-d H:i:s').": cannot read $table_users and $table_person, " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
	if ($row['p_email'] != $row['j_email']) {
		print(date('Y-m-d H:i:s').": $row[name]/$row[j_id] '$row[p_email]' (RAPCS) != '$row[j_email]' (Joomla)\n") ;
		journalise($row['j_id'], 'W', "$row[name]/$row[j_id] '$row[p_email]' (RAPCS) != '$row[j_email]' (Joomla)") ;
		$status = mysqli_query($mysqli_link, "UPDATE $table_person SET email = '$row[j_email]' WHERE jom_id = $row[j_id]");
		if (!$status) {
			journalise($row['j_id'], 'E', mysqli_error($mysqli_link) . " for row[p_email]' (RAPCS) != '$row[j_email]' (Joomla)") ;
		}
	}
	if ($row['p_name'] != $row['j_name']) {
		print(date('Y-m-d H:i:s').": $row[j_name]/$row[j_id] '$row[p_name]' (RAPCS) != '$row[j_name]' (Joomla)\n") ; flush() ;
	}
}


// Check if unpaid membership fee is now paid
print(date('Y-m-d H:i:s').": checking membership fees for $membership_year.\n") ;
$result = mysqli_query($mysqli_link, "SELECT bkf_invoice_id, bkf_user
	FROM $table_membership_fees
	WHERE bkf_payment_date IS NULL") // No need to check for the year probably ? or TODO ?
	or journalise($userId, "E", "Cannot retrieve unpaid membership fees" . mysqli_error($mysqli_link)) ;
$ids = array() ;
while ($row = mysqli_fetch_array($result)) {
	$ids[] = intval($row['bkf_invoice_id']) ;
}

if (count($ids) > 0) { // If there are still unpaid membership fees
	$moves = $odooClient->Read('account.move', 
		[$ids], 
		array('fields' => array('id', 'name', 'state', 'payment_state', 'partner_id'))) ;
	$newly_paid = 0 ;
	$newly_paid_names = array() ;
	foreach($moves as $move) {
		if ($move['payment_state'] == 'paid' or $move['payment_state'] == 'reversed') {
			mysqli_query($mysqli_link, "UPDATE $table_membership_fees SET bkf_payment_date = SYSDATE() WHERE bkf_invoice_id = $move[id]")
				or journalise($userId, "E", "Cannot mark membership fees as paid: " . mysqli_error($mysqli_link)) ;
			$newly_paid ++ ;
			if (is_array($move['partner_id']))
				$newly_paid_names[] = $move['partner_id'][1] ;
		}
	}
	if ($newly_paid > 0)
		journalise($userId, "I", "There are $newly_paid newly paid membership fees: " . implode(", ", $newly_paid_names)) ;
}

$result = mysqli_query($mysqli_link, "SELECT bkf_user
	FROM $table_membership_fees
	WHERE bkf_payment_date IS NOT NULL and bkf_year = YEAR(CURDATE())") 
	or journalise($userId, "E", "Cannot retrieve paid membership fees for this year " . mysqli_error($mysqli_link)) ;
$paid_membership_users = array() ;
while ($row = mysqli_fetch_array($result)) {
	$paid_membership_users[$row['bkf_user']] = $row['bkf_user'] ;
}

// Synchronize the Odoo partners with profile information
print(date('Y-m-d H:i:s').": synchronizing Odoo partners with RAPCS profile information.\n") ;
require_once("odoo_synch.php") ;
odooSynchronize() ;

print(date('Y-m-d H:i:s').": preparing JS files.\n") ;

$f = fopen("data/planes.js", "w") ;
if (! $f) journalise($userId, "E", "Cannot open data/planes.js for writing") ;
else {
	fwrite($f, '// This file is generated automatically every hour from the SQL database' . "\n") ;
	fwrite($f, '// It is therefore useless to update it ;-)' . "\n") ;
	fwrite($f, '// Last update: ' . date('Y-m-d H:i:s') . "\n") ;
	fwrite($f,"var planes = [ ") ;
	$first = true ;
	$result = mysqli_query($mysqli_link, "select upper(id) from $table_planes where actif != 0 and ressource = 0 order by id")
		or journalise($userId, "E", "Cannot read planes: " . mysqli_error($mysqli_link)) ;
	while ($row = mysqli_fetch_array($result)) {
		if ($first)
			$first = false ;
		else
			fwrite($f, ",\n\t") ;
		fwrite($f, "{ id: \"$row[0]\", name: \"$row[0]\"}") ;
	}
	fwrite($f, "\n]; \n") ;
	fclose($f) ;
}

$f = fopen("data/allPlanes.js", "w") ;
if (! $f) journalise($userId, "E", "Cannot open data/allPlanes.js for writing") ;
else {
	fwrite($f, '// This file is generated automatically every hour from the SQL database' . "\n") ;
	fwrite($f, '// It is therefore useless to update it ;-)' . "\n") ;
	fwrite($f, '// Last update: ' . date('Y-m-d H:i:s') . "\n") ;
	fwrite($f,"var allPlanes = [ ") ;
	$first = true ;
	$result = mysqli_query($mysqli_link, "select upper(id) from $table_planes where ressource = 0 order by id")
		or journalise($userId, "E", "Cannot read allPlanes: " . mysqli_error($mysqli_link)) ;
	while ($row = mysqli_fetch_array($result)) {
		if ($first)
			$first = false ;
		else
			fwrite($f, ",\n\t") ;
		fwrite($f, "{ id: \"$row[0]\", name: \"$row[0]\"}") ;
	}
	fwrite($f, "\n]; \n") ;
	fclose($f) ;
}

$f = fopen("data/ressources.js", "w") ;
if (! $f) journalise($userId, "E", "Cannot open data/ressources.js for writing") ;
else {
	fwrite($f, '// This file is generated automatically every hour from the SQL database' . "\n") ;
	fwrite($f, '// It is therefore useless to update it ;-)' . "\n") ;
	fwrite($f, '// Last update: ' . date('Y-m-d H:i:s') . "\n") ;
	fwrite($f,"var ressources = [ ") ;
	$first = true ;
	$result = mysqli_query($mysqli_link, "select id from $table_planes where actif != 0 and ressource != 0 order by id")
		or journalise(0, "E", "Cannot read planes: " . mysqli_error($mysqli_link)) ;
	while ($row = mysqli_fetch_array($result)) {
		if ($first)
			$first = false ;
		else
			fwrite($f, ",\n\t") ;
		fwrite($f, "{ id: \"" . urlencode($row[0]) . "\", name: \"$row[0]\"}") ;
	}
	fwrite($f, "\n]; \n") ;
	fclose($f) ;
}

$f = fopen("data/pilots.js", "w") ;
if (! $f) journalise($userId, "E", "Cannot open data/pilots.js for writing") ;
else {
	fwrite($f, '// This file is generated automatically every hour from the SQL database' . "\n") ;
	fwrite($f, '// It is therefore useless to update it ;-)' . "\n") ;
	fwrite($f, '// Last update: ' . date('Y-m-d H:i:s') . "\n") ;
	fwrite($f,"var pilots = [ ") ;
	$first = true ;
	$result = mysqli_query($mysqli_link, "select u.id as id, first_name, last_name, u.name as name, group_concat(group_id) as allgroups
                from $table_users as u join $table_user_usergroup_map on u.id=user_id join $table_person as p on u.id=p.jom_id
                where block = 0 and group_id in ($joomla_student_group, $joomla_pilot_group, $joomla_instructor_group)
                group by user_id
				order by last_name") 
			or journalise(0, "E", "Cannot read pilots: " . mysqli_error($mysqli_link)) ;
	while ($row = mysqli_fetch_array($result)) {
		$row['name'] = db2web($row['name']) ;
		$row['first_name'] = db2web($row['first_name']) ;
		$row['last_name'] = db2web($row['last_name']) ;
		$groups = explode(',', $row['allgroups']) ;
		$student = (in_array($joomla_student_group, $groups)) ? 'true' : 'false' ;
		$membership = isset($paid_membership_users[$row['id']]) ? 'true' : 'false' ;
		if ($first)
			$first = false ;
		else
			fwrite($f, ",\n\t") ;
		fwrite($f, "{ id: $row[id], name: \"$row[name]\", first_name: \"$row[first_name]\", last_name: \"$row[last_name]\", student: $student, membership: $membership }") ;
	}
	fwrite($f, "\n]; \n") ;
	fclose($f) ;
}

$f = fopen("data/instructors.js", "w") ;
if (! $f) journalise($userId, "E", "Cannot open data/instructors.js for writing") ;
else {
	fwrite($f, '// This file is generated automatically every hour from the SQL database' . "\n") ;
	fwrite($f, '// It is therefore useless to update it ;-)' . "\n") ;
	fwrite($f, '// Last update: ' . date('Y-m-d H:i:s') . "\n") ;
	$e = fopen("email.fis", "w") ;
	fwrite($f,"var instructors = [ ") ;
	fwrite($f, "{ id : -1, name: \" - solo -\"}") ;
	$result = mysqli_query($mysqli_link, "select id, name, email from $table_users join $table_user_usergroup_map on id=user_id
		where block = 0 and group_id = $joomla_instructor_group
		order by name")
	or journalise(0, "E", "Cannot read instructors: " . mysqli_error($mysqli_link)) ;
	while ($row = mysqli_fetch_array($result)) {
		fwrite($e, "$row[email]\n") ;
		$row['name'] = db2web($row['name']) ;
		fwrite($f, ",\n\t") ;
		fwrite($f, "{ id: $row[id], name: \"$row[name]\"}") ;
	}
	fwrite($f, "\n]; \n") ;
	fclose($f) ;
	fclose($e) ;
}

// Let's prepare the email aliases

$e = fopen("email.tkis", "w") ;
if (! $e) journalise($userId, "E", "Cannot open email.tkis for writing") ;
else {
	$result = mysqli_query($mysqli_link, "select id, name, email from $table_users join $table_user_usergroup_map on id=user_id
		where block = 0 and group_id = $joomla_instructor_group2
		order by name") ;
	while ($row = mysqli_fetch_array($result)) {
		fwrite($e, "$row[email]\n") ;
	}
	fclose($e) ;
}

$f = fopen("data/members.js", "w") ;
if (! $f) journalise($userId, "E", "Cannot open data/members.js for writing") ;
else {
	$first = true ;
	fwrite($f,"var members = [ ") ;
	$sql = "select distinct u.id as id, u.name as name, first_name, last_name, u.email as email, group_concat(group_id) as allgroups
		from $table_users as u join $table_user_usergroup_map on u.id=user_id join $table_person as p on u.id=p.jom_id
		where block = 0 and group_id in ($joomla_member_group, $joomla_student_group, $joomla_pilot_group)
		group by user_id
		order by last_name, first_name" ;
	$result = mysqli_query($mysqli_link, $sql)
		or journalise(0, "E", "Cannot read members: " . mysqli_error($mysqli_link)) ;
	while ($row = mysqli_fetch_array($result)) {
		$row['name'] = db2web($row['name']) ;
		if ($row['name'] === FALSE) 
			journalise(0, 'E', "There was an error while converting\n") ; 
		$row['first_name'] = db2web($row['first_name']) ;
		$row['last_name'] = db2web($row['last_name']) ;
		if ($first)
			$first = false ;
		else
			fwrite($f, ",\n\t") ;
		$groups = explode(',', $row['allgroups']) ;
		$pilot = (in_array($joomla_pilot_group, $groups)) ? 'true' : 'false' ;
		$student = (in_array($joomla_student_group, $groups)) ? 'true' : 'false' ;
		$membership = isset($paid_membership_users[$row['id']]) ? 'true' : 'false' ;
		fwrite($f, "{ id: $row[id], name: \"$row[name]\", first_name: \"$row[first_name]\", last_name: \"$row[last_name]\", email: \"$row[email]\", pilot: $pilot, student: $student, membership: $membership}") ;
	}
	fwrite($f, "\n]; \n") ;
	fclose($f) ;
}

# Generate email aliases for pilots
$f = fopen("email.pilotes", "w") ;
if (! $f) journalise($userId, "E", "Cannot open email.pilotes for writing") ;
else {
	$result = mysqli_query($mysqli_link, "select distinct id, name, email from $table_users join $table_user_usergroup_map on id=user_id
		where block = 0 and group_id = $joomla_pilot_group
		order by name") ;
	while ($row = mysqli_fetch_array($result)) {
		fwrite($f, "$row[email]\n") ;
	}
	fwrite($f, "aeroclub.spa@gmail.com\n") ;
	fwrite($f, "dto118@spa-aviation.be\n") ;
	fwrite($f, "event@spa-aviation.be\n") ;
	fclose($f) ;
}

# Generate email aliases for students
$f = fopen("email.eleves", "w") ;
if (! $f) journalise($userId, "E", "Cannot open email.eleves for writing") ;
else {
	$result = mysqli_query($mysqli_link, "select distinct id, name, email from $table_users join $table_user_usergroup_map on id=user_id
		where block = 0 and group_id in ($joomla_student_group, $joomla_instructor_group, $joomla_instructor_group2)
		order by name") ;
	while ($row = mysqli_fetch_array($result)) {
		fwrite($f, "$row[email]\n") ;
	}
	fwrite($f, "aeroclub.spa@gmail.com\n") ;
	fwrite($f, "dto118@spa-aviation.be\n") ;
	fwrite($f, "event@spa-aviation.be\n") ;
	fclose($f) ;
}

# Generate email aliases for members
$f = fopen("email.membres", "w") ;
if (! $f) journalise($userId, "E", "Cannot open email.membres for writing") ;
else {
	$result = mysqli_query($mysqli_link, "select distinct id, name, email from $table_users join $table_user_usergroup_map on id=user_id
		where block = 0 and group_id in ($joomla_member_group, $joomla_student_group, $joomla_pilot_group)
		order by name") ;
	while ($row = mysqli_fetch_array($result)) {
		fwrite($f, "$row[email]\n") ;
	}
	fwrite($f, "aeroclub.spa@gmail.com\n") ;
	fwrite($f, "dto118@spa-aviation.be\n") ;
	fwrite($f, "event@spa-aviation.be\n") ;
	fclose($f) ;
}


# Generate email aliases for admin
$f = fopen("email.webmasters", "w") ;
if (! $f) journalise($userId, "E", "Cannot open email.webmasters for writing") ;
else {
	$result = mysqli_query($mysqli_link, "select distinct id, name, email from $table_users join $table_user_usergroup_map on id=user_id
		where block = 0 and group_id in ($joomla_sysadmin_group, $joomla_superuser_group)
		order by name") or die("Erreur SQL while creating webmasters: " . mysqli_error($mysqli_link));
	$first = true ;
	while ($row = mysqli_fetch_array($result)) {
		if ($row['email'] != 'webmaster@spa-aviation.be' and $row['email'] != 'rapcs-webmasters@lists.vyncke.org') {
			fwrite($f, "$row[email]\n") ;
		}
	}
	fclose($f) ;
}

# Generate email aliases for OA
$f = fopen("email.ca", "w") ;
if (! $f) journalise($userId, "E", "Cannot open email.ca for writing") ;
else {
	$result = mysqli_query($mysqli_link, "select distinct id, name, email from $table_users join $table_user_usergroup_map on id=user_id
		where block = 0 and group_id in ($joomla_board_group)
		order by name") or die("Erreur SQL while creating ca: " . mysqli_error($mysqli_link));
	$first = true ;
	while ($row = mysqli_fetch_array($result)) {
		if ($row['email'] != 'webmaster@spa-aviation.be' and $row['email'] != 'rapcs-webmasters@lists.vyncke.org') {
			fwrite($f, "$row[email]\n") ;
		}
	}
	fclose($f) ;
}

# Generate email aliases for Fleet
$f = fopen("email.fleet", "w") ;
if (! $f) journalise($userId, "E", "Cannot open email.fleet for writing") ;
else {
	$result = mysqli_query($mysqli_link, "select distinct id, name, email from $table_users join $table_user_usergroup_map on id=user_id
		where block = 0 and group_id in ($joomla_mechanic_group)
		order by name") or die("Erreur SQL while creating fleet: " . mysqli_error($mysqli_link));
	$first = true ;
	while ($row = mysqli_fetch_array($result)) {
		fwrite($f, "$row[email]\n") ;
	}
	fclose($f) ;
}

# Generate email aliases for eric
$f = fopen("email.eric", "w") ;
if (! $f) journalise(0, "E", "Cannot open email.eric for writing") ;
else {
	$result = mysqli_query($mysqli_link, "select distinct id, name, email from $table_users join $table_user_usergroup_map on id=user_id
		where block = 0 and id=62
		order by name") ;
	while ($row = mysqli_fetch_array($result)) {
		fwrite($f, "$row[email]\n") ;
	}
	fwrite($f, "eric.vyncke@uliege.be\n") ;
	fclose($f) ;
}

// Some SQL clean-up
print(date('Y-m-d H:i:s').": purging old journal entries.\n") ;
mysqli_query($mysqli_link, "DELETE FROM $table_journal WHERE j_datetime < DATE_SUB(NOW(), INTERVAL 12 MONTH)")
	or journalise($userId, "E", "Cannot purge old entries in journal: " . mysqli_error($mysqli_link)) ;
mysqli_query($mysqli_link, "OPTIMIZE TABLE $table_journal") and mysqli_query($mysqli_link, "ALTER TABLE $table_journal ENGINE=INNODB")
	or journalise($userId, "E", "Cannot optimize $table_journal: " . mysqli_error($mysqli_link)) ;

mysqli_query($mysqli_link, "DELETE FROM $table_local_tracks WHERE lt_timestamp < DATE_SUB(CONVERT_TZ(NOW(), 'Europe/Paris', 'UTC'), INTERVAL 30 MINUTE)")
	or journalise($userId, "E", "Cannot purge old entries in $table_local_tracks: " . mysqli_error($mysqli_link)) ;
mysqli_query($mysqli_link, "OPTIMIZE TABLE $table_local_tracks") and mysqli_query($mysqli_link, "ALTER TABLE $table_local_tracks ENGINE=INNODB")
	or journalise($userId, "E", "Cannot optimize $table_local_tracks: " . mysqli_error($mysqli_link)) ;
		
mysqli_query($mysqli_link, "DELETE FROM $table_tracks WHERE t_time < DATE_SUB(NOW(), INTERVAL 1 WEEK)")
	or journalise($userId, "E", "Cannot purge old entries in $table_tracks: " . mysqli_error($mysqli_link)) ;
mysqli_query($mysqli_link, "OPTIMIZE TABLE $table_tracks") and mysqli_query($mysqli_link, "ALTER TABLE $table_tracks ENGINE=INNODB")
	or journalise($userId, "E", "Cannot optimize $table_tracks: " . mysqli_error($mysqli_link)) ;

// Clean-up Joomla session table (growing for ever...)
print(date('Y-m-d H:i:s').": purging database.\n") ;
mysqli_query($mysqli_link, "DELETE FROM $table_session WHERE userid = 0")
	or journalise($userId, "E", "Cannot purge anonymous entries in $table_session: " . mysqli_error($mysqli_link)) ;
mysqli_query($mysqli_link, "OPTIMIZE TABLE $table_session") and mysqli_query($mysqli_link, "ALTER TABLE $table_session ENGINE=INNODB")
	or journalise($userId, "E", "Cannot optimize $table_session: " . mysqli_error($mysqli_link)) ;
mysqli_query($mysqli_link, "DELETE FROM jom_action_logs WHERE log_date < DATE_SUB(NOW(), INTERVAL 12 MONTH)")
	or journalise($userId, "E", "Cannot purge old entries in jom_action_logs: " . mysqli_error($mysqli_link)) ;
mysqli_query($mysqli_link, "DELETE FROM jom_action_logs 
		WHERE message_language_key = 'PLG_ACTIONLOG_JOOMLA_USER_LOGIN_FAILED' AND log_date < DATE_SUB(NOW(), INTERVAL 1 WEEK)") // Be more aggressive in case of dictionnary attacks
	or journalise($userId, "E", "Cannot purge old entries in jom_action_logs: " . mysqli_error($mysqli_link)) ;
mysqli_query($mysqli_link, "OPTIMIZE TABLE jom_action_logs") and mysqli_query($mysqli_link, "ALTER TABLE jom_action_logs ENGINE=INNODB")
	or journalise($userId, "E", "Cannot optimize jom_action_logs: " . mysqli_error($mysqli_link)) ;
mysqli_query($mysqli_link, "DELETE FROM jom_redirect_links WHERE published = 0")
	or journalise($userId, "E", "Cannot purge unpublished entries in jom_redirect_links: " . mysqli_error($mysqli_link)) ;
mysqli_query($mysqli_link, "OPTIMIZE TABLE jom_redirect_links")
	or journalise($userId, "E", "Cannot optimize jom_redirect_links: " . mysqli_error($mysqli_link)) ;
mysqli_query($mysqli_link, "DELETE FROM jom_ucm_history WHERE save_date < DATE_SUB(NOW(), INTERVAL 24 MONTH)")
	or journalise($userId, "E", "Cannot purge old revisions in jom_ucm_history: " . mysqli_error($mysqli_link)) ;
mysqli_query($mysqli_link, "OPTIMIZE TABLE jom_ucm_history") and mysqli_query($mysqli_link, "ALTER TABLE jom_ucm_history ENGINE=INNODB")
	or journalise($userId, "E", "Cannot optimize jom_ucm_history: " . mysqli_error($mysqli_link)) ;

// Get some system parameters

$load = sys_getloadavg(); 
// TODO also use http://php.net/manual/fr/function.getrusage.php

$result = mysqli_query($mysqli_link, "SELECT round(SUM(data_length + index_length) / 1024 / 1024, 0) 
	FROM information_schema.tables where table_schema = '$db_name';") 
	or journalise($userId, "E", "Cannot get DB size: " . mysqli_error($mysqli_link)) ;
$row = mysqli_fetch_row($result) ;
$db_size = $row[0] ;

// Historique des METAR (move to the end as vyncke.org tends to be too slow and cause a mySql disconnect
print(date('Y-m-d H:i:s').": building METAR history.\n") ;

$metar_unknown = 0 ;
$metar_vmc = 0 ;
$metar_mmc = 0 ;
$metar_imc = 0 ;
$metar_string = file_get_contents("https://www.vyncke.org/resa/metar.php?station=$default_metar_station&format=json") ;
if ($metar_string === false)
	$metar_unknown++ ;
else {
	$metar = json_decode($metar_string, true) ;
	if (json_last_error() === JSON_ERROR_NONE)
		switch ($metar['condition']) {
			case 'IMC': $metar_imc++ ; break ;
			case 'MMC': $metar_mmc++ ; break ;
			case 'VMC': $metar_vmc++ ; break ;
			default: $metar_unknown++ ; $metar['condition'] = 'UNKNOWN' ; break ;
		}
	else
		$metar_unknown++ ; $metar['condition'] = 'UNKNOWN' ;
}
$year = intval(date('Y')) ;
$month = intval(date('m')) ;
$day = intval(date('d')) ;
if (time() + 3600 >= airport_opening_local_time($year, $month, $day) and time() < airport_closing_local_time($year, $month, $day)) { // Using +3600 because cron is run just before the top of the hour
	mysqli_query($mysqli_link, "insert into $table_metar_history(mh_date, mh_airport, mh_unknown, mh_vmc, mh_mmc, mh_imc)
		values(current_date(), '$default_metar_station', $metar_unknown, $metar_vmc, $metar_mmc, $metar_imc)
		on duplicate key update mh_unknown=mh_unknown+$metar_unknown, mh_vmc=mh_vmc+$metar_vmc,
			mh_mmc=mh_mmc+$metar_mmc, mh_imc=mh_imc+$metar_imc")
		or journalise($userId, 'E', "Cannot update METAR history: " . mysqli_error($mysqli_link)) ;
	print(date('Y-m-d H:i:s').": Latest METAR for $default_metar_station: VMC=$metar_vmc, MMC=$metar_mmc, IMC=$metar_imc, UNKNOWN=$metar_unknown.\n") ;
}

print(date('Y-m-d H:i:s').": End of CRON.\n") ;
if ($metar_unknown != 0)
	journalise($userId, "I", "End of hourly cron; $flight_reminders flight, $engine_reminders engine reminder emails sent, $metar[condition], CPU load $load[0]/$load[1]/$load[2], DB size $db_size MB.") ;
else
	journalise($userId, "I", "End of hourly cron; $flight_reminders flight, $engine_reminders engine reminder emails sent, unknown METAR, CPU load $load[0]/$load[1]/$load[2], DB size $db_size MB.") ;
?>
</pre>