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

// TODO: every month, if no connection in the last month & if pilot/student/instructor => send reminder about the userid
// TODO: every month, end email about maintenance of the plane + inactive planes
// TODO: every month, statistics on WE ?

require_once 'dbi.php' ;

$test_mode = false ; // Only send to eric@vyncke.org when test_mode is true
$debug = true ;
$bccTo = 'eric@vyncke.org' ;

$mime_preferences = array(
	"input-charset" => "UTF-8",
	"output-charset" => "UTF-8",
	"scheme" => "Q") ;

$max_profile_count = 10 ;

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

 
print(date('Y-m-d H:i:s').": preparing lists of plane bookings & logbook entries.\n") ;

$email_body = "<p>Voici la liste mensuelle des diverses r&eacute;servations des avions du RAPCS. <i><span style='color: blue;'>
	Cette liste est bas&eacute;e sur les entr&eacute;es volontaires des pilotes, instructeurs et &eacute;l&egrave;ves via
	le site web.</span></i></p>" ;

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
}

if (strpos($actions, 'b') !== FALSE) {
$sql = "select r_plane, count(*), sum(r_duration)  
	from $table_planes p left join $table_bookings on p.id = r_plane
	where p.actif != 0 and p.ressource = 0 and r_cancel_date is null and r_start > date_sub(sysdate(), interval 1 month) and r_type != " . BOOKING_MAINTENANCE . "
	group by r_plane" ;

print_plane_table("R&eacute;servations du dernier mois", $sql, ['Avion', 'Nbr r&eacute;servations', 'Dur&eacute;e pr&eacute;vue<br/>en heures']) ;

$sql = "select name, count(*), sum(r_duration) as total_duration  
	from jom_users p left join $table_bookings on p.id = r_pilot
	where r_plane = 'PH-AML' and r_cancel_date is null and r_start > date_sub(sysdate(), interval 1 month) and r_type != " . BOOKING_MAINTENANCE . "
	group by r_pilot
	order by total_duration desc
	limit 0,10" ;

print_plane_table("R&eacute;servations PH-AML top-10 du dernier mois", $sql, ['Pilote', 'Nbr r&eacute;servations', 'Dur&eacute;e pr&eacute;vue<br/>en heures']) ;

$sql = "select name, count(*), sum(r_duration) as total_duration  
	from jom_users p left join $table_bookings on p.id = r_pilot
	where r_plane = 'OO-SPQ' and r_cancel_date is null and r_start > date_sub(sysdate(), interval 1 month) and r_type != " . BOOKING_MAINTENANCE . "
	group by r_pilot
	order by total_duration desc
	limit 0,10" ;

print_plane_table("R&eacute;servations OO-SPQ top-10 du dernier mois", $sql, ['Pilote', 'Nbr r&eacute;servations', 'Dur&eacute;e pr&eacute;vue<br/>en heures']) ;

}

if (strpos($actions, 'l') !== FALSE) {
$sql = "select r_plane, count(l_id), min(l_start_hour), max(l_end_hour), max(l_end_hour * 60 + l_end_minute) - min(l_start_hour * 60 + l_start_minute) 
	from $table_planes p left join $table_bookings on p.id = r_plane join $table_logbook on r_id = l_booking
	where p.actif != 0 and p.ressource = 0 and r_cancel_date is null and r_start > date_sub(sysdate(), interval 1 month) and r_type != " . BOOKING_MAINTENANCE . "
	group by r_plane" ;

print_plane_table("Entr&eacute;es dans les carnets de route du dernier mois", $sql, ['Avion', 'Nbr de vols', 'D&eacute;but', 'Fin', 'Minutes moteur']) ;
}

if (strpos($actions, 'm') !== FALSE) {
$sql = "select r_plane, r_start, r_stop, r_comment  
	from $table_planes p left join $table_bookings on p.id = r_plane
	where p.ressource = 0 and r_cancel_date is null and r_start > date_sub(sysdate(), interval 1 month) and r_type = " . BOOKING_MAINTENANCE . "
	order by r_plane, r_start" ;

print_plane_table("Avions en maintenance", $sql, ['Avion', 'D&eacute;but', 'Fin', 'Commentaire']) ;
}

$email_header = "From: $managerName <$managerEmail>\r\n" ;
$email_header .= "To: info@spa-aviation.be, ca@spa-aviation.be\r\n" ;
$email_header .= "Cc: fis@spa-aviation.be\r\n" ;
if ($bccTo != '') $email_header .= "Bcc: $bccTo\r\n" ;
$email_header .= "Return-Path: $managerName <$managerEmail>\r\n" ;
$email_header .= "Content-Type: text/html; charset=\"UTF-8\"\r\n" ;
$email_header .= "MIME-Version: 1.0\r\n" ;

if ($test_mode)
	@mail("eric.vyncke@ulg.ac.be", "Statistiques utilisations des avions", $email_body, "Content-Type: text/html; charset=\"UTF-8\"\r\n") ;
else
	@mail("info@spa-aviation.be, ca@spa-aviation.be, fis@spa-aviation.be", "Statistiques utilisations des avions", $email_body, $email_header) ;

if (strpos($actions, 'p') !== FALSE) {

// Reminder of incomplete profile
//$joomla_admin_group = 7 ;
//$joomla_pilot_group = 13 ;
//$joomla_student_group = 16 ;
//$joomla_instructor_group = 14 ;
//$joomla_mechanic_group = 17 ;

$sql = "select *,u.name as full_name
	from jom_users u left join $table_person p on u.id = p.jom_id
	where block = 0 and exists (select * from jom_user_usergroup_map m
		where u.id = m.user_id and m.group_id in ($joomla_admin_group, $joomla_pilot_group, $joomla_student_group, $joomla_instructor_group))" ; 
print(date('Y-m-d H:i:s') . ": executing: $sql\n") ;
$result = mysqli_query($mysqli_link, $sql) or die(date('Y-m-d H:i:s') . ": Erreur systeme lors de la lecture des profils: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
        $profile_count = 0 ;
		$missing_items = array() ;
		$full_name = db2web($row['full_name']) ; // SQL DB is latin1 and the rest is in UTF-8
		$first_name = db2web($row['first_name']) ; // SQL DB is latin1 and the rest is in UTF-8
        if ($row['email'] != '') $profile_count ++ ;
        if ($row['first_name'] != '') $profile_count ++ ; else $missing_items[] = '<b>pr&eacute;nom</b>' ;
        if ($row['last_name'] != '') $profile_count ++ ; else $missing_items[] = '<b>nom de famille</b>' ;
        if ($row['home_phone'] != '') $profile_count ++ ; else $missing_items[] = 't&eacute;l&eacute;phone priv&eacute;' ;
        if ($row['work_phone'] != '') $profile_count ++ ; else $missing_items[] = 't&eacute;l&eacute;phone travail' ;
        if ($row['cell_phone'] != '') $profile_count ++ ; else $missing_items[] = '<b>t&eacute;l&eacute;phone mobile</b>' ;
        if ($row['city'] != '') $profile_count ++ ; else $missing_items[] = 'ville' ;
        if ($row['country'] != '') $profile_count ++ ; else $missing_items[] = 'pays' ; 
        if ($row['sex'] != '' and $row['sex'] != 0) $profile_count ++ ; else $missing_items[] = 'genre' ; 
        if ($row['birthdate'] != '' and $row['birthdate'] != '0000-00-00 00:00:00') $profile_count ++ ; else $missing_items[] = 'date de naissance' ; 
		$missing_items_string = implode(', ', $missing_items) ;
		if ($debug) print(date('Y-m-d H:i:s').": processing user#$row[jom_id] $row[name]/$row[username]/$full_name: profile items count $profile_count ($missing_items_string).\n") ;
		if ($profile_count + 2 >= $max_profile_count) continue ;
		if ($profile_count < $max_profile_count/2) 
			journalise($row['jom_id'], 'W', db2web("Incomplete profile for $row[name]/$row[username]/$row[full_name]: profile items count $profile_count ($missing_items_string)")) ;
		if ($row['email'] == '') {
			print(date('Y-m-d H:i:s').": no email address... skipping !!!!!\n") ;
			continue ;
	}
	// Need to warn the user...
	$email_subject = iconv_mime_encode('Subject',
		"Votre profil sur www.spa-aviation.be est incomplet", $mime_preferences) ;
	$email_message = '' ;
	if ($first_name != '')
		$email_message .= "$first_name,<br/>" ;
	else
		$email_message .= "<p>Bonjour,<br/>" ;
	$email_message .= "&Agrave; titre informatif, votre profil sur le site de notre club est incomplet (seulement $profile_count informations sur $max_profile_count)...<br/>Ces informations ne sont visibles que pour les autres membres RAPCS (+ le SPW et notre atelier) connect&eacute;s.
		<b>Seules certaines donn&eacute;es sont obligatoires pour effectuer une r&eacute;servation: nom, pr&eacute;nom, email et num&eacute;ro
		de t&eacute;l&eacute;phone mobile</b> (ceci afin de vous contacter si n&eacute;cessaire); les autres informations sont simplement
		pour permettre de nous conna&icirc;tre au sein de notre club.
		\nVeuillez visiter le lien ci-dessous et compl&eacute;ter les donn&eacute;es manquantes ($missing_items_string):\n" ;
	$email_message .= "<a href=https://www.spa-aviation.be/resa/profile.php>profil r&eacute;servation</a>.</p>\n" ;
	$email_message .= "<p>Pour rappel, votre identifiant est <b>$row[username]</b>.</p>\r\n" ;
	$email_message .= "<hr>Ceci est un message automatique envoy&eacute; tous les mois tant que votre profil n'est pas complet." ;
	if ($test_mode) $email_message .= "<hr><font color=red><B>Ceci est une version de test</b></font>" ;
	$email_header = "From: $managerName <$managerEmail>\r\n" ;
	$email_header .= "To: $full_name <$row[email]>\r\n" ;
	if ($bccTo != '') $email_header .= "Bcc: $bccTo\r\n" ;
	$email_header .= "Return-Path: $managerName <$managerEmail>\r\n" ;
	$email_header .= "Content-Type: text/html; charset=\"UTF-8\"\r\n" ;
	$email_header .= "MIME-Version: 1.0\r\n" ;
	$email_header .= "X-Comment: joomla user is $row[jom_id]\r\n" ;
	if ($test_mode)
		mail("eric.vyncke@ulg.ac.be", substr($email_subject, 9), $email_message, "Content-Type: text/html; charset=\"UTF-8\"\r\n") ;
	else
		@mail("$row[full_name] <$row[email]>", substr($email_subject, 9), $email_message, $email_header) ;
}
mysqli_free_result($result) ;

}

if (strpos($actions, 'e') !== FALSE) {

print(date('Y-m-d H:i:s').": preparing lists of pilots/students/members.\n") ;

$email_body = "<p>Voici la liste mensuelle des divers utilisateurs du site RAPCS.</p>" ;

function print_table($title, $sql) {
	global $email_body, $mysqli_link, $convertToUtf8 ;

	$email_body .= "<h2>$title</h2>\n<table border='1'><tr><th>Username</th><th>Nom</th><th>Email</th></tr>\n" ;
	print(date('Y-m-d H:i:s') . ": ($title) executing: $sql\n") ;
	$result = mysqli_query($mysqli_link, $sql) or die(date('Y-m-d H:i:s') . ": Erreur systeme lors de la lecture des profils: " . mysqli_error($mysqli_link)) ;
	$n = 0 ;
	while ($row = mysqli_fetch_array($result)) {
		if ($convertToUtf8 ) $row['name'] = iconv("ISO-8859-1", "UTF-8", $row['name']) ; // SQL DB is latin1 and the rest is in UTF-8
		if ($convertToUtf8 ) $row['username'] = iconv("ISO-8859-1", "UTF-8", $row['username']) ; // SQL DB is latin1 and the rest is in UTF-8
		$email_body .= "<tr><td>$row[username]</td><td>$row[name]</td><td>$row[email]</td></tr>\n" ;
		$n ++ ;
	}
	mysqli_free_result($result) ;
	$email_body .= "</table>\n$n ligne(s).<br/>\n" ;
	print(date('Y-m-d H:i:s') . ": ($title) $n lines\n") ;
}

$sql = "select *,u.name as full_name
	from jom_users u 
	where block = 0 and not exists (select * from jom_user_usergroup_map m
		where u.id = m.user_id and m.group_id in ($joomla_admin_group, $joomla_pilot_group, $joomla_student_group, $joomla_instructor_group))
	order by name" ; 
print_table("Utilisateurs qui ne sont ni pilotes ni &eacute;l&egrave;ves", $sql) ;

$sql = "select *,u.name as full_name
	from jom_users u 
	where block = 0 and exists (select * from jom_user_usergroup_map m
		where u.id = m.user_id and m.group_id in ($joomla_student_group)) 
	order by name" ; 
print_table("Utilisateurs &eacute;l&egrave;ves", $sql) ;

$sql = "select *,u.name as full_name
	from jom_users u 
	where block = 0 and exists (select * from jom_user_usergroup_map m
		where u.id = m.user_id and m.group_id in ($joomla_pilot_group)) 
	order by name" ; 
print_table("Utilisateurs pilotes (pilotes@spa-aviation.be)", $sql) ;

$sql = "select *,u.name as full_name
	from jom_users u 
	where block = 0 and not exists (select * from $table_bookings b
		where b.r_start > date_sub(sysdate(), interval 1 year) and b.r_pilot = u.id and b.r_cancel_date is null
		)
	order by name" ; 
print_table("Utilisateurs sans aucune r&eacute;servation dans les 12 derniers mois", $sql) ;

$sql = "select *,u.name as full_name
	from jom_users u 
	where block = 0 and exists (select * from jom_user_usergroup_map m
		where u.id = m.user_id and m.group_id in ($joomla_admin_group)) 
	order by name" ; 
print_table("Membres du Conseil d'Administration (ca@spa-aviation.be)", $sql) ;

$sql = "select *,u.name as full_name
	from jom_users u 
	where block = 0 and u.username != 'admin' and exists (select * from jom_user_usergroup_map m
		where u.id = m.user_id and m.group_id in ($joomla_sysadmin_group, $joomla_superuser_group, $joomla_admin_group)) 
	order by name" ; 
print_table("Administrateurs syst&egrave;me du site (webmaster@spa-aviation.be)", $sql) ;

$email_header = "From: $managerName <$managerEmail>\r\n" ;
$email_header .= "To: info@spa-aviation.be, ca@spa-aviation.be\r\n" ;
$email_header .= "Cc: fis@spa-aviation.be, webmaster@spa-aviation.be\r\n" ;
if ($bccTo != '') $email_header .= "Bcc: $bccTo\r\n" ;
$email_header .= "Return-Path: $managerName <$managerEmail>\r\n" ;
$email_header .= "Content-Type: text/html; charset=\"UTF-8\"\r\n" ;
$email_header .= "MIME-Version: 1.0\r\n" ;
if ($test_mode)
	@mail("eric.vyncke@ulg.ac.be", "Listes diverses", $email_body, "Content-Type: text/html; charset=\"UTF-8\"\r\n") ;
else
	@mail("info@spa-aviation.be, ca@spa-aviation.be, fis@spa-aviation.be, webmaster@spa-aviation.be", "Listes diverses", $email_body, $email_header) ;
}

print(date('Y-m-d H:i:s').": end of job.\n") ;
journalise(0, "I", "End of monthly cron job (actions=$actions)") ;
?>
