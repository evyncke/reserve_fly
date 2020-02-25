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

require_once "dbi.php" ;
require_once 'facebook.php' ;

if ($_REQUEST['owner'] != '' && is_numeric($_REQUEST['owner']) && ($userIsAdmin || $userIsInstructor)) {
	$owner = $_REQUEST['owner'] ;
	$owner_name = "membre $owner" ;
} else {
	$owner = $userId ;
	$owner_name = $userFullName ;
}

$sql_filter = [] ;

if (isset($_REQUEST['period'])) {
	$period = $_REQUEST['period'] ;
	switch ($_REQUEST['period']) {
		case '2y': $sql_filter[] = 'l_start > date_sub(now(), interval 2 year)' ; break ;
		case '1y': $sql_filter[] = 'l_start > date_sub(now(), interval 1 year)' ; break ;
		case '3m': $sql_filter[] = 'l_start > date_sub(now(), interval 3 month)' ; break ;
		case '1m': $sql_filter[] = 'l_start > date_sub(now(), interval 1 month)' ; break ;
	}	
} else {
	$period = 'always' ; 
}

if (isset($_REQUEST['items'])) {
	$items = $_REQUEST['items'] ;
	switch ($items) {
		case '10': $items = 10 ; break ;
		case '20': $items = 20 ; break ;
		case '50': $items = 50 ; break ;
		case '100': $items = 100 ; break ;
		default: $items = 20 ;
	}
} else {
	$items = 20 ;
}

if (isset($_REQUEST['page']) and is_numeric($_REQUEST['page'])) {
	$page = $_REQUEST['page'] ;
} else {
	$page = 9999 ;
}

$sql_filters = implode(' and ', $sql_filter) ;
if ($sql_filters != '') $sql_filters = " and $sql_filters" ;

// TODO
// - HTML5 header
// - if admin then allow the choice of pilot
// - allow adding previous flight hours (or outside of RAPCS) at the top?
// - allow adding any other entries manually (at the bottom?)

function ShowTableHeader() {
?>
<thead>
<tr>
<th class="logHeader">Action</th>
<th class="logHeader">Date</th>
<th class="logHeader" colspan="2">Departure</th>
<th class="logHeader" colspan="2">Arrival</th>
<th class="logHeader" colspan="2">Aircraft</th>
<th class="logHeader" colspan="2">Total time</th>
<th class="logHeader">Name</th>
<th class="logHeader" colspan="2">Landings</th>
<th class="logHeader" colspan="6">Pilot function time</th>
</tr>
<tr>
<th class="logLastHeader"></th>
<th class="logLastHeader">(dd/mm/yy)</th>
<th class="logLastHeader">Place</th>
<th class="logLastHeader">Time</th>
<th class="logLastHeader">Place</th>
<th class="logLastHeader">Time</th>
<th class="logLastHeader">Model</th>
<th class="logLastHeader">Registration</th>
<th class="logLastHeader" colspan="2">of flight</th>
<th class="logLastHeader">PIC</th>
<th class="logLastHeader">Day</th>
<th class="logLastHeader">Night</th>
<th class="logLastHeader" colspan="2">PIC</th>
<th class="logLastHeader" colspan="2">Dual</th>
<th class="logLastHeader" colspan="2">Instructor</th>
</thead>
<?php
}

function ShowEntryCell($line, $action, $dom_id, $col_name, $default_value, $input_type, $size) {
	global $joomla_instructor_group, $table_person ;

	$value = (isset($line[$col_name])) ? $line[$col_name] : $default_value ;
	$min_max = ($input_type == 'number') ? ' min="0" max="99" ' : '' ;
	if ($input_type != 'fi') 
		print("<td><input type=\"$input_type\" class=\"logCellEntry\" id=\"$dom_id" . ucfirst($action). "\" value=\"$value\" size=\"$size\"$min_max></td>\n") ;
	else {
		print("<td><select class=\"logCellEntry\" id=\"$dom_id" . ucfirst($action). "\">\n") ;
		print("<option value=\"0\"" . (($default_value == '0') ? ' selected' : '') . ">SELF</option>\n") ;
		$result = mysqli_query($mysqli_link, "select jom_id, last_name from $table_person join $table_user_usergroup_map on jom_id = user_id
			where group_id = $joomla_instructor_group
			order by name") or print(mysqli_error($mysqli_link)) ;
		while ($row = mysqli_fetch_array($result)) {
			$instructor_name = ($convertToUtf8) ? iconv("ISO-8859-1", "UTF-8", $row['last_name']) : $row['last_name'] ;
			print("<option value=\"$row[jom_id]\"" . (($default_value == $row['jom_id']) ? 'selected' : '') . ">$instructor_name</option>\n") ;
		}
		print("<option value=\"-1\"" . (($default_value == '-1') ? ' selected' : '') . ">Autre FI</option>\n") ;
		print("</select>\n</td>\n") ;
	}
}

function ShowEntryRow($action, $line, $id) {
	global $owner ;

	if ($line) {
		// DB contains local daytime while display is in UTC
		$l_start = gmdate('H:i', strtotime("$line[l_start] $default_timezone")) ;
		$l_end = gmdate('H:i', strtotime("$line[l_end] $default_timezone")) ;
		if ($line['l_pilot'] == $line['r_who']) // Solo, no instructor
			$pic = 0 ;
		else
			$pic = $line['l_instructor'] ;
	} else {
		$l_start = '' ;
		$l_end = '' ;
		$pic = 0 ;
	}
	print("<tr>
	<td>
		<img src=\"gtk-save.png\" border=\"0\" width=\"15\" height=\"15\" onclick=\"saveButton('$action', $owner, $id);\">
	</td>\n") ;
	ShowEntryCell($line, $action, 'date', 'date', date('Y-m-d'), 'date', 8) ; // Default to today
	ShowEntryCell($line, $action, 'from', 'l_from', '', 'text', 4) ;
	ShowEntryCell($line, $action, 'startTime', 'start', $l_start, 'time', 5) ;
	ShowEntryCell($line, $action, 'to', 'l_to', '', 'text', 4) ;
	ShowEntryCell($line, $action, 'endTime', 'end', $l_end, 'time', 5) ;
	ShowEntryCell($line, $action, 'model', 'l_model', '', 'text', 5) ;
	ShowEntryCell($line, $action, 'plane', 'l_plane', '', 'text', 7) ;
	print("<td colspan=\"2\" class=\"logCell\">... auto...</td>\n") ;
	ShowEntryCell($line, $action, 'pic', 'pic', $pic, 'fi', 10) ;
	ShowEntryCell($line, $action, 'dayLanding', 'l_day_landing', '1', 'number', 2) ;
	ShowEntryCell($line, $action, 'nightLanding', 'l_night_landing', '0', 'number', 2) ;
	print("<td colspan=\"6\" class=\"logCell\">... calcul&eacute; automatiquement...</td>\n") ;
	print("</tr>\n") ;
}
?><html>
<head>
<link rel="stylesheet" type="text/css" href="log.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<title>Mon carnet de vol</title>
<script src="members.js"></script> <!--- cannot be loaded before as its initialization code use variable above... -->
<script>
var
	// preset Javascript constants filled with the right data from db.php PHP variables
	userFullName = '<?=$userFullName?>' ;
	userName = '<?=$userName?>' ;
	userId = <?=$userId?> ;
	userIsPilot = <?=($userIsPilot)? 'true' : 'false'?> ;
	userIsAdmin = <?=($userIsAdmin)? 'true' : 'false'?> ;
	userIsInstructor = <?=($userIsInstructor)? 'true' : 'false'?> ;
	userIsMechanic = <?=($userIsMechanic)? 'true' : 'false'?> ;
	page = <?=$page?> ;

function valueOfField(suffix, name) {
	return name + '=' + document.getElementById(name + suffix.charAt(0).toUpperCase() + suffix.slice(1)).value ;
}

function saveButton(action, owner, id) {
	var formURL = '<?=$_SERVER['PHP_SELF']?>' ;
	
	formURL += '?owner=' + owner + '&action=' + action ;
	if (id > 0) formURL += '&id=' + id ;
	formURL += '&' + valueOfField(action, 'date') ;
	formURL += '&' + valueOfField(action, 'from') ;
	formURL += '&' + valueOfField(action, 'startTime') ;
	formURL += '&' + valueOfField(action, 'to') ;
	formURL += '&' + valueOfField(action, 'endTime') ;
	formURL += '&' + valueOfField(action, 'model') ;
	formURL += '&' + valueOfField(action, 'plane') ;
	formURL += '&' + valueOfField(action, 'pic') ;
	formURL += '&' + valueOfField(action, 'dayLanding') ;
	formURL += '&' + valueOfField(action, 'nightLanding') ;
console.log("Redirecting to " + formURL) ;
	window.location.href = formURL ;
}

function selectChanged() {
	window.location.href = '<?=$_SERVER['PHP_SELF']?>?owner=' + document.getElementById('pilotSelect').value + '&period=' + document.getElementById('periodSelect').value + '&items='  + document.getElementById('itemsSelect').value + '&page=' + page ;
}

function init() {
	var pilotSelect = document.getElementById('pilotSelect') ;
	if (pilotSelect) pilotSelect.value = <?=$owner?> ;
	var periodSelect = document.getElementById('periodSelect') ;
	if (periodSelect) periodSelect.value = '<?=$period?>' ;
	var itemsSelect = document.getElementById('itemsSelect') ;
	if (itemsSelect) itemsSelect.value = '<?=$items?>' ;
	if (userIsInstructor || userIsAdmin) {
		// Initiliaze pilotSelect from member.js
	       for (var member = 0; member < members.length; member++) {
			var option = document.createElement("option");
			option.text = members[member].name ;
			option.value = members[member].id ;
			document.getElementById('pilotSelect').add(option) ;
		}
	}
	pilotSelect.value = <?=$owner?> ;
}
</script>
</head>
<body onload="init();">
<center><h2>Carnet de vol de <?=$owner_name?></h2></center>
<?php
// Actions first
if (isset($_REQUEST['action'])) {
switch ($_REQUEST['action']) {
	case 'delete':
		if (!isset($_REQUEST['id']) or !is_numeric($_REQUEST['id'])) die("Invalid id") ;
		mysqli_query($mysqli_link, "delete from $table_logbook where l_id = $_REQUEST[id] and l_pilot = $owner") 
			or $error_message = "Erreur interne: " . mysqli_error($mysqli_link) ;
		if (mysqli_affected_rows($mysqli_link) < 1) {
			$error_message = "Aucune ligne enlev&eacute;e" ;
			journalise($userId, 'E', "No pilot logbook deleted (id=$id).") ;
		} else
			journalise($userId, 'I', "Pilot logbook entry deleted (id=$id).") ;
		break ;
	case 'edit':
		if (!isset($_REQUEST['id']) or !is_numeric($_REQUEST['id'])) die("Invalid id") ;
		$result = mysqli_query($mysqli_link, "select *,date_format(l_start, '%Y-%m-%d') as date,p.last_name as pic_name,
			upper(l_from) as l_from, upper(l_to) as l_to
			from $table_logbook l
			left join $table_bookings b on b.r_id = l.l_booking
			left join $table_person p on p.jom_id = l_instructor
			where l_id = $_REQUEST[id] and (l_pilot = $owner or l_instructor = $owner)") 
			or $error_message = "Erreur interne: " . mysqli_error($mysqli_link) ;
		$line = mysqli_fetch_array($result) ;
		if ($line) {
			print("Vous pouvez apporter les changements sur la ligne ci-dessous. Remarque: le carnet de route de l'avion n'est pas mis &agrave; jour.
				<table class=\"logTable\">\n") ;
			ShowTableHeader() ;
			print("<tbody>\n") ;
			ShowEntryRow('saveedit', $line, $_REQUEST['id']) ;
			print("</tbody>\n</table>\n<br/><hr><br/>\n") ;
		} else
			if (!isset($error_message)) $error_message = "Ligne $_REQUEST[id] n'existe pas..." ;
		break ;
	case 'new':
	case 'saveedit':
		if (!is_numeric($_REQUEST['dayLanding'])) die("Invalid parameter for dayLanding") ;
		if (!is_numeric($_REQUEST['nightLanding'])) die("Invalid parameter for nightLanding") ;
		if (!is_numeric($_REQUEST['owner'])) die("Invalid parameter for owner '$_REQUEST[owner]'") ;
		if (!is_numeric($_REQUEST['pic'])) die("Invalid parameter for pic '$_REQUEST[pic]'") ;
		if (isset($_REQUEST['id']) and !is_numeric($_REQUEST['id'])) die("Invalid parameter for id") ;
		$tokens = preg_split(',[/-],', $_REQUEST['date']) ;
		// Time in DB is local time while time on screen is UTC...
		$l_start = date("Y-m-d H:i:s", strtotime("$tokens[0]-$tokens[1]-$tokens[2] $_REQUEST[startTime] UTC")) ;
		$l_from = mysqli_real_escape_string($mysqli_link, $_REQUEST['from']) ;
		$l_end = date("Y-m-d H:i:s", strtotime("$tokens[0]-$tokens[1]-$tokens[2] $_REQUEST[endTime] UTC")) ;
		$l_to = mysqli_real_escape_string($mysqli_link, $_REQUEST['to']) ;
		$l_model = mysqli_real_escape_string($mysqli_link, $_REQUEST['model']) ;
		$l_plane = mysqli_real_escape_string($mysqli_link, $_REQUEST['plane']) ;
		$owner = mysqli_real_escape_string($mysqli_link, $_REQUEST['owner']) ;
		$l_day_landing = mysqli_real_escape_string($mysqli_link, $_REQUEST['dayLanding']) ;
		$l_night_landing = mysqli_real_escape_string($mysqli_link, $_REQUEST['nightLanding']) ;
		$pic = mysqli_real_escape_string($mysqli_link, $_REQUEST['pic']) ;
		if ($pic == 0) $pic = 'NULL' ;
		if (isset($_REQUEST['id'])) $id = mysqli_real_escape_string($mysqli_link, $_REQUEST['id']) ;
		if ($_REQUEST['action'] == 'new') {
			$sql = "insert into $table_logbook(l_start, l_end, l_from, l_to, l_model, l_plane, l_pilot, l_instructor, l_day_landing, l_night_landing,
					l_audit_who, l_audit_time, l_audit_ip)
				values('$l_start', '$l_end', '$l_from', '$l_to', '$l_model', '$l_plane', $owner, $pic, $l_day_landing, $l_night_landing,
					$userId, sysdate(), '" . getClientAddress() . "')";
			if (!mysqli_query($mysqli_link, $sql)) {
				$error_message = "Impossible d'ajouter la ligne: " . mysqli_error($mysqli_link) ;
				journalise($userId, 'E', "Error (" . mysqli_error($mysqli_link) . ") when creating pilot logbook entry: $l_from, $l_start, $l_to, $l_end, $l_plane/$l_model") ;
			} else
				journalise($userId, 'I', "New pilot logbook entry: $l_from, $l_start, $l_to, $l_end, $l_plane/$l_model") ;
		} else {
		// Remettre les compteurs moteurs à 0 voire NULL
		// removed the SQL: 
		//		l_start_hour = 0, l_start_minute = 0, l_end_hour = 0, l_end_minute = 0,
		//
			$sql = "update $table_logbook set l_start = '$l_start', l_end = '$l_end', l_from = '$l_from', l_to =  '$l_to',
					l_model = '$l_model', l_plane = '$l_plane', l_instructor = $pic, l_day_landing = $l_day_landing, l_night_landing = $l_night_landing,
					l_audit_who = $userId, l_audit_time = sysdate(), l_audit_ip = '" . getClientAddress() . "'
				where (l_pilot = $owner or l_instructor = $owner) and l_id = $id " ;
			if (!mysqli_query($mysqli_link, $sql)){		
				$error_message = "Impossible de modifier la ligne: " . mysqli_error($mysqli_link) ;
				journalise($userId, 'E', "Error (" . mysqli_error($mysqli_link) . ") when modifying pilot logbook entry: $l_from, $l_start, $l_to, $l_end, $l_plane/$l_model") ;
			} else
				journalise($userId, 'I', "Modified pilot logbook entry: $l_from, $l_start, $l_to, $l_end, $l_plane/$l_model") ;
		}
		break ;
	default: $error_message = "Action pas encore impl&eacute;ment&eacute;e." ;
}
if (isset($error_message))
	print("<span class=\"logError\">$error_message</span><br/>") ;
}


print("P&eacute;riode: <select id=\"periodSelect\" onchange=\"selectChanged();\">
	<option value=\"always\">depuis toujours</option>
	<option value=\"2y\">2 ans</option>
	<option value=\"1y\">1 an</option>
	<option value=\"3m\">3 mois</option>
	<option value=\"1m\">1 mois</option>
</select>,
<select id=\"itemsSelect\" onchange=\"selectChanged();\">
	<option value=\"10\">10</option>
	<option value=\"20\">20</option>
	<option value=\"50\">50</option>
	<option value=\"100\">100</option>
</select> lignes/page.
<br/>") ;

$sql = "select l_id, date_format(l_start, '%d/%m/%y') as date,
	l_model, l_plane, l_pilot, l_instructor, p.last_name as instructor_name,
	upper(l_from) as l_from, upper(l_to) as l_to, 
	l_start, l_end, timediff(l_end, l_start) as duration,
	timediff(addtime(l_end, '24:00:00'), l_start) as duration_rollover,
	l_day_landing, l_night_landing
	from $table_logbook l 
	left join $table_person p on p.jom_id = l_instructor
	where (l_pilot = $owner or l_instructor = $owner) $sql_filters
	order by l.l_start asc" ;
// TODO get a more efficient way to count the rows...
$result = mysqli_query($mysqli_link, $sql) or die("Erreur systeme a propos de l'access au carnet de route: " . mysqli_error($mysqli_link)) ;
$rows_count = mysqli_num_rows($result) ;
if ($rows_count === FALSE) die("Cannot count rows (owner = $owner, $sql): " . mysqli_error($mysqli_link));
$page_count = ceil($rows_count / $items) ;
if ($page > $page_count -1) $page = $page_count -1 ;
if ($page < 0) $page = 0 ;
mysqli_data_seek($result, $page * $items) ; //or die("Cannot data_seek(page=$page, items=$items): " . mysql_error());

if ($userIsInstructor or $userIsAdmin) {
	print("En tant qu'instructeur/administrateur, vous pouvez consulter les carnets de vol des autres pilotes: <select id=\"pilotSelect\" onchange=\"selectChanged();\">" ) ;
	print("</select><br/><br/>") ;
} else { // ($userIsInstructor or $userIsAdmin)
	print("Carnet de vol de: <select id=\"pilotSelect\" onchange=\"selectChanged();\">
	<option value=\"owner\" selected>$userFullName</option>
	</select><br/><br/>") ;
}

print("<table class=\"logTable\">\n") ;
ShowTableHeader() ;
print("<tbody>\n") ;

$duration_total_hour = 0 ;
$duration_total_minute = 0 ;
$day_landing_total = 0 ;
$night_landing_total = 0 ;
$pic_total_hour = 0 ;
$pic_total_minute =  0;
$dual_total_hour = 0 ;
$dual_total_minute =  0;
$fi_total_hour = 0 ;
$fi_total_minute =  0;
$line_count = 0 ;
while (($row = mysqli_fetch_array($result)) && ($line_count < $items)) {
	$line_count ++ ;
	if (substr($row['duration'], 0, 1) == '-')
		$duration = explode(':', $row['duration_rollover']) ; // Looking like 01:33:00 (in case of over rolling the 24:00:00 mark)
	else
		$duration = explode(':', $row['duration']) ; // Looking like 01:33:00
	$duration_total_hour += $duration[0] ;
	$duration_total_minute += $duration[1] ;
	$day_landing_total += $row['l_day_landing'] ;
	$night_landing_total += $row['l_night_landing'] ;
	// DB contains local daytime while display is in UTC
	// As the OVH MySQL server does not have the timezone support, needs to be done in PHP
	$l_start = gmdate('H:i', strtotime("$row[l_start] $default_timezone")) ;
	$l_end = gmdate('H:i', strtotime("$row[l_end] $default_timezone")) ;
	if ($row['l_instructor'] < 0) $row['instructor_name'] = 'Autre FI' ;
// print("<br/>l_start is now $l_start raw $row[l_start] and UTC $row[l_start_utc]<br/>") ;
	print("<tr>
		<td class=\"logCell\">
			<a href=\"$_SERVER[PHP_SELF]?action=edit&id=$row[l_id]&owner=$owner\"><img src=\"gtk-edit.png\" border=\"0\" width=\"15\" height=\"15\"></a>
			<a href=\"$_SERVER[PHP_SELF]?action=delete&id=$row[l_id]&owner=$owner\"><img src=\"gtk-delete.png\" border=\"0\" width=\"15\" height=\"15\"></a>
		</td>
		<td class=\"logCell\">$row[date]</td>
		<td class=\"logCell\">$row[l_from]</td>
		<td class=\"logCell\">$l_start</td>
		<td class=\"logCell\">$row[l_to]</td>
		<td class=\"logCell\">$l_end</td>
		<td class=\"logCell\">$row[l_model]</td>
		<td class=\"logCell\">$row[l_plane]</td>
		<td class=\"logCell\">$duration[0]</td>
		<td class=\"logCell\">$duration[1]</td>\n") ;
	if ($row['l_instructor'] == '') { // Solo, no instructor
		print("<td class=\"logCell\">SELF</td>
			<td class=\"logCell\">$row[l_day_landing]</td>
			<td class=\"logCell\">$row[l_night_landing]</td>
			<td class=\"logCell\">$duration[0]</td>
			<td class=\"logCell\">$duration[1]</td>
			<td class=\"logCell\"></td><td class=\"logCell\"></td><td class=\"logCell\"></td><td class=\"logCell\"></td>
			\n") ;
		$pic_total_hour += $duration[0] ;
		$pic_total_minute += $duration[1] ;
	} else { // Dual command
		if ($row['l_pilot'] == $owner) { // Dual command as student
			print("<td class=\"logCell\">$row[instructor_name]</td>
				<td class=\"logCell\">$row[l_day_landing]</td>
				<td class=\"logCell\">$row[l_night_landing]</td>
				<td class=\"logCell\"></td><td class=\"logCell\"></td>
				<td class=\"logCell\">$duration[0]</td>
				<td class=\"logCell\">$duration[1]</td>
				<td class=\"logCell\"></td><td class=\"logCell\">\n") ;
			$dual_total_hour += $duration[0] ;
			$dual_total_minute += $duration[1] ;
		} else { // Dual command as instructor
			print("<td class=\"logCell\">$row[instructor_name]</td>
				<td class=\"logCell\">$row[l_day_landing]</td>
				<td class=\"logCell\">$row[l_night_landing]</td>
				<td class=\"logCell\"></td><td class=\"logCell\"></td><td class=\"logCell\"></td><td class=\"logCell\"></td>
				<td class=\"logCell\">$duration[0]</td>
				<td class=\"logCell\">$duration[1]</td>\n") ;
			$fi_total_hour += $duration[0] ;
			$fi_total_minute += $duration[1] ;
		}
	}
	print("</tr>\n") ;
}
$duration_total_hour += floor($duration_total_minute / 60) ;
$duration_total_minute = $duration_total_minute % 60 ;
$pic_total_hour += floor($pic_total_minute / 60) ;
$pic_total_minute = $pic_total_minute % 60 ;
$dual_total_hour += floor($dual_total_minute / 60) ;
$dual_total_minute = $dual_total_minute % 60 ;
$fi_total_hour += floor($fi_total_minute / 60) ;
$fi_total_minute = $fi_total_minute % 60 ;
showEntryRow("new", NULL, -1) ;
?>
<tr><td colspan="8" class="logTotal">Total</td>
<td class="logTotal"><?=$duration_total_hour?></td>
<td class="logTotal"><?=$duration_total_minute?></td>
<td class="logTotal"></td>
<td class="logTotal"><?=$day_landing_total?></td>
<td class="logTotal"><?=$night_landing_total?></td>
<td class="logTotal"><?=$pic_total_hour?></td>
<td class="logTotal"><?=$pic_total_minute?></td>
<td class="logTotal"><?=$dual_total_hour?></td>
<td class="logTotal"><?=$dual_total_minute?></td>
<td class="logTotal"><?=$fi_total_hour?></td>
<td class="logTotal"><?=$fi_total_minute?></td>
</tr>
</tbody>
</table>
<br/>
<div style="border-style: inset;background-color: AntiqueWhite;">
Sur base des donn&eacute;es que vous avez entr&eacute;es apr&egrave;s les vols dans le
carnet de route des avions (&agrave; pr&eacute;f&eacute;rer pour avoir les heures moteur) ou celles que vous avez entr&eacute;e via la derni&egrave;re ligne de la
table.
Soit <?=$line_count?> par vous-m&ecirc;me.
Les heures sont les heures UTC.</div>
<br/>
<a href="<?="$_SERVER[PHP_SELF]?owner=$owner&period=$period&items=$items&page=0"?>"><img width="64" height="64" border="1" src="gtk_media_forward_rtl.png"></a>
<a href="<?="$_SERVER[PHP_SELF]?owner=$owner&period=$period&items=$items&page=".($page-1)?>"><img width="64" height="64" border="1" src="gtk_media_play_rtl.png"></a>
<a href="<?="$_SERVER[PHP_SELF]?owner=$owner&period=$period&items=$items&page=".($page+1)?>"><img width="64" height="64" border="1" src="gtk_media_play_ltr.png"></a>
<a href="<?="$_SERVER[PHP_SELF]?owner=$owner&period=$period&items=$items&page=99999"?>"><img width="64" height="64" border="1" src="gtk_media_forward_ltr.png"></a>
<?php
$version_php = date ("Y-m-d H:i:s.", filemtime('mylog.php')) ;
$version_css = date ("Y-m-d H:i:s.", filemtime('log.css')) ;
?>
<hr>
<div class="copyright">R&eacute;alisation: Eric Vyncke, janvier 2015, avril 2018, pour RAPCS, Royal A&eacute;ro Para Club de Spa, ASBL<br>
Versions: PHP=<?=$version_php?>, CSS=<?=$version_css?></div>
</body>
</html>
