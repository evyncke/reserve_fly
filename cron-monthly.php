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

// TODO: every month, if no connection in the last month & if pilot/student/instructor => send reminder about the userid
// TODO: every month, send email about maintenance of the plane + inactive planes
// TODO: every month, statistics on WE ?

require_once 'dbi.php' ;
ob_start() ; // To allow the ob_flush()

// SMTP email debuging & optimization
//$smtp_info['debug'] = True;
$smtp_info['persist'] = True;
$managerEmail = $smtp_from ; // Allow more debugging

$test_mode = false ; // Only send to eric@vyncke.org when test_mode is true
$debug = true ;
$bccTo = "eric.vyncke@edpnet.be" ;

$mime_preferences = array(
	"input-charset" => "UTF-8",
	"output-charset" => "UTF-8",
	"scheme" => "Q") ;

$max_profile_count = 7 ;

$actions = (isset($_REQUEST['actions']) and $_REQUEST['actions'] != '') ? trim(strtolower($_REQUEST['actions'])) : 'pblme' ;
// Values for $actions
// p = profile of members
// b = booking
// l = log book
// m = maintenance
// e = email lists
// t = mode test

if (strpos($actions, 't') !== FALSE) $test_mode = true ;

print(date('Y-m-d H:i:s').": starting for actions = {$actions}.\n") ;
journalise(0, 'I', "Cron-monthly: starting for actions = {$actions}") ;

 
print(date('Y-m-d H:i:s').": preparing lists of plane bookings & logbook entries.\n") ;

$email_body = "<p>Voici la liste mensuelle des diverses r&eacute;servations des avions du RAPCS.</p>" ;

function print_plane_table($title, $sql, $columns) {
	global $email_body, $mysqli_link, $convertToUtf8 ;

	$email_body .= "<h2>$title</h2>\n<table border='1'><tr>" ;
	foreach ($columns as $column) 
		$email_body .= "<th>$column</th>" ;
	$email_body .= "</tr>\n" ;
	print(date('Y-m-d H:i:s') . ": ($title) executing: $sql\n") ;
	$result = mysqli_query($mysqli_link, $sql) or die(date('Y-m-d H:i:s') . ": Erreur systeme lors de la lecture des profils: " . mysqli_error($mysqli_link)) ;
	$n = 0 ;
	while ($row = mysqli_fetch_array($result)) {
		$email_body .= "<tr>" ;
		for ($i = 0; $i < count($columns) ; $i++) {
			$style = (is_numeric($row[$i])) ? ' style="text-align:right"' : '' ;
			$email_body .= "<td$style>" . db2web($row[$i]) . "</td>\n" ;
		}
		$email_body .= "</tr>\n" ;
		$n ++ ;
	}
	$email_body .= "</tr>\n" ;
	mysqli_free_result($result) ;
	$email_body .= "</table>\n$n ligne(s).<br/>\n" ;
	print(date('Y-m-d H:i:s') . ": ($title) $n lines\n") ;
	ob_flush() ;
}

if (strpos($actions, 'b') !== FALSE) {
$sql = "select r_plane, count(*), sum(r_duration)  
	from $table_planes p left join $table_bookings on p.id = r_plane
	where p.actif != 0 and p.ressource = 0 and r_cancel_date is null and r_start > date_sub(sysdate(), interval 1 month) and r_type != " . BOOKING_MAINTENANCE . "
	group by r_plane" ;

print_plane_table("R&eacute;servations du dernier mois", $sql, ['Avion', 'Nbr r&eacute;servations', 'Dur&eacute;e pr&eacute;vue<br/>en heures']) ;

$sql = "select name, count(*), sum(r_duration) as total_duration  
	from $table_users p left join $table_bookings on p.id = r_pilot
	where r_plane = 'OO-SPQ' and r_cancel_date is null and r_start > date_sub(sysdate(), interval 1 month) and r_type != " . BOOKING_MAINTENANCE . "
	group by r_pilot
	order by total_duration desc
	limit 0,10" ;

print_plane_table("R&eacute;servations OO-SPQ top-10 du dernier mois", $sql, ['Pilote', 'Nbr r&eacute;servations', 'Dur&eacute;e pr&eacute;vue<br/>en heures']) ;

}

if (strpos($actions, 'l') !== FALSE) {
$sql = "SELECT r_plane, count(l_id), min(l_start_hour), max(l_end_hour), max(l_end_hour * 60 + l_end_minute) - min(l_start_hour * 60 + l_start_minute) 
	FROM $table_planes p LEFT JOIN $table_bookings ON p.id = r_plane JOIN $table_logbook ON r_id = l_booking
	WHERE p.actif != 0 and p.ressource = 0 AND r_cancel_date is null 
		AND r_start > date_sub(sysdate(), interval 1 month) AND r_start < sysdate()
		AND r_type != " . BOOKING_MAINTENANCE . "
	GROUP BY l_plane" ;

print_plane_table("Entr&eacute;es dans les carnets de routes informatiques du dernier mois", $sql, ['Avion', 'Nbr de vols', 'D&eacute;but', 'Fin', 'Minutes moteur']) ;
}

if (strpos($actions, 'm') !== FALSE) {
$sql = "select r_plane, r_start, r_stop, r_comment  
	from $table_planes p left join $table_bookings on p.id = r_plane
	where p.ressource = 0 and r_cancel_date is null and r_start > date_sub(sysdate(), interval 1 month) and r_type = " . BOOKING_MAINTENANCE . "
	order by r_plane, r_start" ;

print_plane_table("Avions en maintenance", $sql, ['Avion', 'D&eacute;but', 'Fin', 'Commentaire']) ;
}

$email_recipients = "info@spa-aviation.be, ca@spa-aviation.be, fis@spa-aviation.be" ;
$email_header = "From: $managerName <$smtp_from>\r\n" ;
$email_header .= "Return-Path: <bounce@spa-aviation.be>\r\n" ;  // Will set the MAIL FROM enveloppe by the Pear Mail send()
$email_header .= "To: info@spa-aviation.be, ca@spa-aviation.be\r\n" ;
$email_header .= "Cc: fis@spa-aviation.be\r\n" ;
if ($bccTo != '') {
	$email_header .= "Bcc: $bccTo\r\n" ;
	$email_recipients .= ", $bccTo" ;
}

// Only send if actions b l m are selected
if (strpos($actions, 'b') !== FALSE or strpos($actions, 'l') !== FALSE or strpos($actions, 'm') !== FALSE) {
	 if ($test_mode) {
		$smtp_info['debug'] = True ;
		smtp_mail("evyncke@cisco.com", "Statistiques utilisations des avions (test)", $email_body) ;
 	} else
		@smtp_mail($email_recipients, "Statistiques sur l'utilisation des avions", $email_body, $email_header) ;

	ob_flush() ;

	mysqli_close($mysqli_link) ; // Sometimes OVH times out ...
	$mysqli_link = mysqli_connect($db_host, $db_user, $db_password) ;
	if (! $mysqli_link) die("Impossible de se connecter a MySQL:" . mysqli_connect_error()) ;
	if (! mysqli_select_db($mysqli_link, $db_name)) die("Impossible d'ouvrir la base de donnees:" . mysqli_error($mysqli_link)) ;

	journalise(0, 'I', "Cron-monthly: statistics email sent") ;
}

if (strpos($actions, 'p') !== FALSE) {
	journalise(0, 'I', "Cron-monthly: starting checks on users' profiles") ;

// Reminder of incomplete profile
//$joomla_admin_group = 7 ;
//$joomla_pilot_group = 13 ;
//$joomla_student_group = 16 ;
//$joomla_instructor_group = 14 ;
//$joomla_mechanic_group = 17 ;

$sql = "select *,u.name as full_name
	from $table_users u left join $table_person p on u.id = p.jom_id
	where block = 0 and exists (select * from $table_user_usergroup_map m
		where u.id = m.user_id and m.group_id in ($joomla_admin_group, $joomla_pilot_group, $joomla_student_group, $joomla_instructor_group))" ; 
print(date('Y-m-d H:i:s') . ": executing: $sql\n") ; ob_flush() ;
$result = mysqli_query($mysqli_link, $sql) or die(date('Y-m-d H:i:s') . ": Erreur systeme lors de la lecture des profils: " . mysqli_error($mysqli_link)) ;
$all_rows = mysqli_fetch_all($result, MYSQLI_ASSOC) ;
foreach ($all_rows as $row) {
        $profile_count = 0 ;
		$missing_items = array() ;
		$full_name = db2web($row['full_name']) ; // SQL DB is latin1 and the rest is in UTF-8
		$first_name = db2web($row['first_name']) ; // SQL DB is latin1 and the rest is in UTF-8
        if ($row['email'] != '') $profile_count ++ ;
        if ($row['first_name'] != '') $profile_count ++ ; else $missing_items[] = '<b>pr&eacute;nom</b>' ;
        if ($row['last_name'] != '') $profile_count ++ ; else $missing_items[] = '<b>nom de famille</b>' ;
        if ($row['home_phone'] == '')  $missing_items[] = 't&eacute;l&eacute;phone priv&eacute;' ;
        if ($row['work_phone'] == '') $missing_items[] = 't&eacute;l&eacute;phone travail' ;
        if ($row['cell_phone'] != '') $profile_count ++ ; else $missing_items[] = '<b>t&eacute;l&eacute;phone mobile</b>' ;
        if ($row['city'] != '') $profile_count ++ ; else $missing_items[] = 'ville' ;
        if ($row['country'] == '') $missing_items[] = 'pays' ; 
        if ($row['sex'] != '' and $row['sex'] != 0) $profile_count ++ ; else $missing_items[] = 'genre' ; 
        if ($row['birthdate'] != '' and $row['birthdate'] != '0000-00-00 00:00:00') $profile_count ++ ; else $missing_items[] = 'date de naissance' ; 
		$missing_items_string = implode(', ', $missing_items) ;
		if ($debug) print(date('Y-m-d H:i:s').": processing user#$row[jom_id] $row[name]/$row[username]/$full_name: profile items count $profile_count ($missing_items_string).\n") ;
		if ($profile_count + 2 >= $max_profile_count) continue ;
		if ($profile_count < $max_profile_count/2) 
			journalise($row['jom_id'], 'W', db2web("Incomplete profile for $row[name]/$row[username]/$row[full_name]: profile items count $profile_count ($missing_items_string)")) ;
		if ($row['email'] == '') {
			print(date('Y-m-d H:i:s').": no email address... skipping !!!!!\n") ; ob_flush() ;
			continue ;
	}
	// Need to warn the user...
	$email_subject = iconv_mime_encode('Subject',
		"Votre profil sur www.spa-aviation.be est incomplet", $mime_preferences) ;
	$email_message = '' ;
	if ($first_name != '')
		$email_message .= "<p>Bonjour $first_name,</p>" ;
	else
		$email_message .= "<p>Bonjour,</p>" ;
	$email_message .= "<p>&Agrave; titre informatif, votre profil sur le site de notre club est incomplet: seulement $profile_count informations sur $max_profile_count...<br/>
		Ces informations ne sont visibles que pour les autres membres RAPCS (+ le SPW et notre atelier).
		<b>Seules certaines donn&eacute;es sont obligatoires pour effectuer une r&eacute;servation: nom, pr&eacute;nom, email et num&eacute;ro
		de t&eacute;l&eacute;phone mobile</b> (ceci afin de vous contacter si n&eacute;cessaire); les autres informations sont simplement
		pour permettre de nous conna&icirc;tre au sein de notre club.</p>
		<p>Veuillez visiter le lien ci-dessous et compl&eacute;ter les donn&eacute;es manquantes ($missing_items_string):\n" ;
	$email_message .= "<a href=https://www.spa-aviation.be/resa/mobile_profile.php>profil r&eacute;servation</a>.</p>\n" ;
	$email_message .= "<p>Pour rappel, votre identifiant est <b>$row[username]</b> et vous devez &ecirc;tre connect&eacute;(e) pour changer votre profil.</p>\r\n" ;
	$email_message .= "<hr>Ceci est un message automatique envoy&eacute; tous les mois tant que votre profil n'est pas complet." ;
	if ($test_mode) $email_message .= "<hr><font color=red><B>Ceci est une version de test</b></font>" ;
	$email_header = "From: $managerName <$smtp_from>\r\n" ;
	$email_header .= "Return-Path: <bounce@spa-aviation.be>\r\n" ;  // Will set the MAIL FROM enveloppe by the Pear Mail send()
	$email_header .= "To: $full_name <$row[email]>\r\n" ;
	$email_recipients = $row['email'] ;
	if ($bccTo != '') {
		$email_header .= "Bcc: $bccTo\r\n" ;
		$email_recipients .= ", $bccTo" ;
	}
	$email_header .= "X-Comment: joomla user ID is $row[jom_id]\r\n" ;
	if ($test_mode)
		smtp_mail("eric.vyncke@ulg.ac.be", substr($email_subject, 9), $email_message, "Content-Type: text/html; charset=\"UTF-8\"\r\n") ;
	else
		@smtp_mail($email_recipients, substr($email_subject, 9), $email_message, $email_header) ;
}
mysqli_free_result($result) ;
print(date('Y-m-d H:i:s').": End of profile checks.\n") ; ob_flush() ;
mysqli_close($mysqli_link) ; // Sometimes OVH times out ...
$mysqli_link = mysqli_connect($db_host, $db_user, $db_password) ;
if (! $mysqli_link) die("Impossible de se connecter a MySQL:" . mysqli_connect_error()) ;
if (! mysqli_select_db($mysqli_link, $db_name)) die("Impossible d'ouvrir la base de donnees:" . mysqli_error($mysqli_link)) ;
journalise(0, 'I', "Cron-monthly: email reminders for missing profiles sent") ;
}

if (strpos($actions, 'e') !== FALSE) {
mysqli_close($mysqli_link) ; // Sometimes OVH times out ...
$mysqli_link = mysqli_connect($db_host, $db_user, $db_password) ;
if (! $mysqli_link) die("Impossible de se connecter a MySQL:" . mysqli_connect_error()) ;
if (! mysqli_select_db($mysqli_link, $db_name)) die("Impossible d'ouvrir la base de donnees:" . mysqli_error($mysqli_link)) ;

print(date('Y-m-d H:i:s').": preparing lists of pilots/students/members.\n") ; ob_flush() ;

$email_body = "<p>Voici la liste mensuelle des divers utilisateurs du site RAPCS.</p>" ;

function print_table($title, $sql) {
	global $email_body, $mysqli_link, $convertToUtf8 ;

	$email_body .= "<h2>$title</h2>\n<table border='1'><tr><th>Username</th><th>Nom</th><th>Email</th></tr>\n" ;
	print(date('Y-m-d H:i:s') . ": ($title) executing: $sql\n") ; ob_flush() ;
	$result = mysqli_query($mysqli_link, $sql) or die(date('Y-m-d H:i:s') . ": Erreur systeme lors de la lecture des profils: " . mysqli_error($mysqli_link)) ;
	$n = 0 ;
	while ($row = mysqli_fetch_array($result)) {
		$row['full_name'] = db2web($row['full_name']) ; // SQL DB is latin1 and the rest is in UTF-8
		$row['username'] = db2web($row['username']) ; // SQL DB is latin1 and the rest is in UTF-8
		$email_body .= "<tr><td>$row[username]</td><td>$row[full_name]</td><td>$row[email]</td></tr>\n" ;
		$n ++ ;
	}
	mysqli_free_result($result) ;
	$email_body .= "</table>\n$n ligne(s).<br/>\n" ;
	print(date('Y-m-d H:i:s') . ": ($title) $n lines\n") ; ob_flush() ;
}

$sql = "select *,concat(u.name, ' - ', count(*), ' vol(s)') as full_name
	from $table_users u join $table_bookings b on b.r_pilot = u.id
	where u.block = 0 and b.r_start > date_sub(sysdate(), interval 1 month) and b.r_stop < sysdate() and b.r_cancel_date is null and b.r_type != " . BOOKING_MAINTENANCE . "
		and not exists (select * from $table_logbook l
				where l.l_booking = b.r_id)
	group by username
	order by full_name" ; 
print_table("<span style=\"color: red;\">Pilotes/&eacute;l&egrave;ves sans aucune entr&eacute;e dans les carnets de routes des avions pour des r&eacute;servations de ce dernier mois</span>", $sql) ;

$sql = "select *,u.name as full_name
	from $table_users u join $table_person p on u.id = p.jom_id
	where u.block = 0 and (p.cell_phone is null or p.cell_phone = '') and exists (select * from $table_user_usergroup_map m
		where u.id = m.user_id and m.group_id in ($joomla_admin_group, $joomla_pilot_group, $joomla_student_group, $joomla_instructor_group)) 
	order by full_name" ; 
print_table("<span style=\"color: red;\">Utilisateurs sans num&eacute;ro de mobile</span>", $sql) ;

$sql = "select *,u.name as full_name
	from $table_users u 
	where block = 0 and not exists (select * from $table_user_usergroup_map m
		where u.id = m.user_id and m.group_id in ($joomla_admin_group, $joomla_pilot_group, $joomla_student_group, $joomla_instructor_group))
	order by name" ; 
print_table("Utilisateurs qui ne sont ni pilotes ni &eacute;l&egrave;ves", $sql) ;

$sql = "select *,u.name as full_name
	from $table_users u 
	where block = 0 and exists (select * from $table_user_usergroup_map m
		where u.id = m.user_id and m.group_id in ($joomla_student_group)) 
	order by name" ; 
print_table("Utilisateurs &eacute;l&egrave;ves", $sql) ;

$sql = "select *,u.name as full_name
	from $table_users u 
	where block = 0 and exists (select * from $table_user_usergroup_map m
		where u.id = m.user_id and m.group_id in ($joomla_pilot_group)) 
	order by name" ; 
print_table("Utilisateurs pilotes (pilotes@spa-aviation.be)", $sql) ;

$sql = "select *,u.name as full_name
	from $table_users u 
	where block = 0 and not exists (select * from $table_bookings b
		where b.r_start > date_sub(sysdate(), interval 1 year) and b.r_pilot = u.id and b.r_cancel_date is null
		)
	order by name" ; 
print_table("Utilisateurs sans aucune r&eacute;servation dans les 12 derniers mois", $sql) ;

$sql = "select *,u.name as full_name
	from $table_users u 
	where block = 0 and exists (select * from $table_user_usergroup_map m
		where u.id = m.user_id and m.group_id in ($joomla_board_group)) 
	order by name" ; 
print_table("Membres du Conseil d'Administration (ca@spa-aviation.be)", $sql) ;

$sql = "select *,u.name as full_name
	from $table_users u 
	where block = 0 and u.username != 'admin' and exists (select * from $table_user_usergroup_map m
		where u.id = m.user_id and m.group_id in ($joomla_sysadmin_group, $joomla_superuser_group, $joomla_admin_group)) 
	order by name" ; 
print_table("Administrateurs syst&egrave;me du site (webmaster@spa-aviation.be)", $sql) ;

$sql = "select *,u.name as full_name
	from $table_users u 
	where block = 0 and exists (select * from $table_user_usergroup_map m
		where u.id = m.user_id and m.group_id in ($joomla_flight_pilot_group)) 
	order by name" ; 
print_table("Pilotes pour les vols d&eacute;couverte", $sql) ;


$sql = "select *,u.name as full_name
	from $table_users u 
	where block = 0 and exists (select * from $table_user_usergroup_map m
		where u.id = m.user_id and m.group_id in ($joomla_flight_manager_group)) 
	order by name" ; 
print_table("Gestionnaires des vols d&eacute;couverte", $sql) ;

$email_header = "From: $managerName <$smtp_from>\r\n" ;
$email_header .= "Return-Path: <bounce@spa-aviation.be>\r\n" ;  // Will set the MAIL FROM enveloppe by the Pear Mail send()
$email_header .= "To: info@spa-aviation.be, ca@spa-aviation.be\r\n" ;
$email_header .= "Cc: fis@spa-aviation.be, webmaster@spa-aviation.be\r\n" ;
$email_recipients = "info@spa-aviation.be, ca@spa-aviation.be, fis@spa-aviation.be, webmaster@spa-aviation.be" ;
if ($bccTo != '') {
	$email_header .= "Bcc: $bccTo\r\n" ;
	$email_recipients .= ", $bccTo" ;
}
if ($test_mode) {
	$smtp_info['debug'] = True;
	smtp_mail("eric.vyncke@ulg.ac.be", "Listes diverses (test)", $email_body, "Content-Type: text/html; charset=\"UTF-8\"\r\n") ;
} else
	smtp_mail($email_recipients, "Listes diverses", $email_body, $email_header) ;
	mysqli_close($mysqli_link) ; // Sometimes OVH times out ...
	$mysqli_link = mysqli_connect($db_host, $db_user, $db_password) ;
	if (! $mysqli_link) die("Impossible de se connecter a MySQL:" . mysqli_connect_error()) ;
	if (! mysqli_select_db($mysqli_link, $db_name)) die("Impossible d'ouvrir la base de donnees:" . mysqli_error($mysqli_link)) ;
	journalise(0, "I", "Cron-monthly: misc lists sent") ;
}

print(date('Y-m-d H:i:s').": end of job.\n") ; ob_flush() ;
journalise(0, "I", "End of monthly cron job (actions=$actions)") ;
?>
