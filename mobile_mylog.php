<?php
/*
   Copyright 2014-2024 Eric Vyncke

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

require_once "dbi.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}

$body_attributes=' onload="initMyLog();init();" ' ;

require_once 'mobile_header5.php' ;

if (isset($_REQUEST['user']) && $_REQUEST['user'] != '' && is_numeric($_REQUEST['user']) && ($userIsAdmin || $userIsInstructor)) {
	$owner = $_REQUEST['user'] ;
} else {
	$owner = $userId ;
}

// If validity == 1 (SEP), then we can show the SEP expiry date
$result = mysqli_query($mysqli_link, "SELECT * FROM $table_person p 
	LEFT JOIN $table_validity v ON v.jom_id =p.jom_id AND validity_type_id = 1
	WHERE p.jom_id = $owner")
	or journalise($userId, "F", "Cannot read user data:" . mysqli_error($mysqli_link)) ;
$row_owner = mysqli_fetch_array($result) or journalise($userId, "F", "User $owner not found");
$owner_name = db2web("$row_owner[last_name], $row_owner[first_name]") ;
mysqli_free_result($result) ;

$sql_filter = [] ;

if (!isset($_REQUEST['period']) or $_REQUEST['period'] == '') $_REQUEST['period'] = '1y' ; // Default to one year
if (isset($_REQUEST['period']) and $_REQUEST['period'] != 'always') {
	$period = $_REQUEST['period'] ;
	if ($period == 'SEP') {
		$sep_limit = date_create($row_owner['expire_date']) ; // Next SEP expiry date
		$date_from = date_format(date_sub($sep_limit, date_interval_create_from_date_string('12 months - 1 day')), 'd/m/Y') . 
		   " (12 mois avant la date limite de prorogation SEP, $row_owner[expire_date])" ;	
		$sep_limit = date_create($row_owner['expire_date']) ; // Next SEP expiry date
		$date_from_sql = date_format(date_sub($sep_limit, date_interval_create_from_date_string('12 months - 1 day')), 'Y-m-d') ;
	} else {
		switch ($_REQUEST['period']) {
			case '2y': $interval = '2 years' ; break ;
			case '1y': $interval = '1 year' ; break ;
			case '3m': $interval = '3 months' ; break ;
			case '1m': 	$interval = '1 month' ; break ;
		}
		$today = date_create("now") ;
		$date_from = date_format(date_sub($today, date_interval_create_from_date_string($interval)), 'd/m/Y') . 
		   ' (12 mois avant la date limite de prorogation SEP)' ;	
		$today = date_create("now") ; // As $today has changed above...
		$date_from_sql = date_format(date_sub($today, date_interval_create_from_date_string($interval)), 'Y-m-d') ;	
	}
} else {
	$period = 'always' ; 
	$date_from = '17/12/1903 (1er vol des frères Wright)' ; // All flights should be done after this date, Wright brothers ;-)
	$date_from_sql = '1903-12-17' ; // All flights should be done after this date, Wright brothers ;-)
}

// $sql_filters = implode(' and ', $sql_filter) ;
// if ($sql_filters != '') $sql_filters = " and $sql_filters" ;

// TODO
// - if admin then allow the choice of pilot
// - allow adding previous flight hours (or outside of RAPCS) at the top?
// - allow adding any other entries manually (at the bottom?)

function ShowTableHeader() {
?>
<thead>
<tr>
<th>Action</th>
<th>Date</th>
<th class="text-center" colspan="2">Departure</th>
<th class="text-center" colspan="2">Arrival</th>
<th class="text-center" colspan="2">Aircraft</th>
<th class="text-center" colspan="2">Total time</th>
<th >Name</th>
<th class="text-center" colspan="2">Landings</th>
<th class="text-center" colspan="6">Pilot function time</th>
</tr>
<tr>
<th></th>
<th>(dd/mm/yy)</th>
<th>Place</th>
<th>Time UTC</th>
<th>Place</th>
<th>Time UTC</th>
<th>Model</th>
<th>Registration</th>
<th class="text-center" colspan="2">of flight</th>
<th>PIC</th>
<th>Day</th>
<th>Night</th>
<th class="text-center" colspan="2">PIC</th>
<th class="text-center" colspan="2">Dual</th>
<th class="text-center" colspan="2">Instructor</th>
</thead>
<?php
}

function ShowEntryCell($line, $action, $dom_id, $col_name, $default_value, $input_type, $size) {
	global $joomla_instructor_group, $table_person, $mysqli_link ;

	$value = (isset($line[$col_name])) ? $line[$col_name] : $default_value ;
	$min_max = ($input_type == 'number') ? ' min="0" max="99" ' : '' ;
	if ($input_type != 'fi') 
		print("<td><input type=\"$input_type\" id=\"$dom_id" . ucfirst($action). "\" value=\"$value\" size=\"$size\"$min_max></td>\n") ;
	else {
		print("<td><select id=\"$dom_id" . ucfirst($action). "\">\n") ;
		print("<option value=\"0\"" . (($default_value == '0') ? ' selected' : '') . ">SELF</option>\n") ;
		$result = mysqli_query($mysqli_link, "select jom_id, last_name from $table_person join jom_user_usergroup_map on jom_id = user_id
			where group_id = $joomla_instructor_group
			order by name") or print(mysqli_error($mysqli_link)) ;
		while ($row = mysqli_fetch_array($result)) {
			$instructor_name = db2web($row['last_name']) ;
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
//		$l_start = gmdate('H:i', strtotime("$line[l_start] $default_timezone")) ;
//		$l_end = gmdate('H:i', strtotime("$line[l_end] $default_timezone")) ;
		// DB contains UTC daytime
		$l_start = gmdate('H:i', strtotime("$line[l_start] +0000")) ;
		$l_end = gmdate('H:i', strtotime("$line[l_end] +0000")) ;
		print("<hr>l_start=$l_start   line[l_start] $line[l_start]<br/>") ;
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
	<i class=\"bi bi-plus-circle-fill text-primary\" onclick=\"saveButton('$action', $owner, $id);\"></i></td>\n") ;
	ShowEntryCell($line, $action, 'date', 'date', date('Y-m-d'), 'date', 8) ; // Default to today
	ShowEntryCell($line, $action, 'from', 'l_from', '', 'text', 4) ;
	ShowEntryCell($line, $action, 'startTime', 'start', $l_start, 'time', 5) ;
	ShowEntryCell($line, $action, 'to', 'l_to', '', 'text', 4) ;
	ShowEntryCell($line, $action, 'endTime', 'end', $l_end, 'time', 5) ;
	ShowEntryCell($line, $action, 'model', 'l_model', '', 'text', 5) ;
	ShowEntryCell($line, $action, 'plane', 'l_plane', '', 'text', 7) ;
	print("<td colspan=\"2\">... auto...</td>\n") ;
	ShowEntryCell($line, $action, 'pic', 'pic', $pic, 'fi', 10) ;
	ShowEntryCell($line, $action, 'dayLanding', 'l_day_landing', '1', 'number', 2) ;
	ShowEntryCell($line, $action, 'nightLanding', 'l_night_landing', '0', 'number', 2) ;
	print("<td colspan=\"6\">... calculé automatiquement...</td>\n") ;
	print("</tr>\n") ;
}
?>
<script>
var
	clubPlanes = [] ; // The list of club tail numbers as they cannot be entered by this page

function valueOfField(suffix, name) {
	return name + '=' + document.getElementById(name + suffix.charAt(0).toUpperCase() + suffix.slice(1)).value ;
}

function saveButton(action, owner, id) {
	var formURL = '<?=$_SERVER['PHP_SELF']?>' ;
	var plane = document.getElementById('plane' + action.charAt(0).toUpperCase() + action.slice(1)).value ;
	for (var i = 0; i < clubPlanes.length; i++) {
		if (clubPlanes[i] == plane.trim().replace('-', '').toUpperCase()) {
			alert('Pour les avions du club, il y a une page spécifique, vous allez y être redirigé.') ;
			window.location.href = 'IntroCarnetVol.php?cdv_aircraft=' + plane ;
			return ;
		}
	}
	
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
	window.location.href = formURL ;
}

function selectChanged() {
	window.location.href = '<?=$_SERVER['PHP_SELF']?>?user=' + document.getElementById('pilotSelect').value + '&period=' + document.getElementById('periodSelect').value  ;
}

function initMyLog() {
	// Prepare a list of club tail numbers as they cannot be entered by this page
	for (var i = 0; i < planes.length; i++) {
		var tailNumber = planes[i].id.replace('-', '').toUpperCase() ;
		clubPlanes.push(tailNumber) ;
	}
	prefillDropdownMenus('periodName', [{id: 'always', name: 'depuis toujours'},
		{id: '2y', name: '2 ans'},
		{id: '1y', name: '1 an'},
		{id: '3m', name: '3 mois'},
		{id: '1m', name: '1 mois'},
		{id: 'SEP', name: '1 an avant prorogation SEP'}
	], '<?=$period?>') ;
}
</script>
<!--body onload="init();"-->
<div class="container-fluid">

<h2>Carnet de vols de <?=$owner_name?> depuis le <?=$date_from?></h2>
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
			print("Vous pouvez apporter les changements sur la ligne ci-dessous. Remarque: le carnet de route de l'avion n'est pas mis à jour.
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
		$l_start = date("Y-m-d H:i:s", strtotime("$tokens[0]-$tokens[1]-$tokens[2] $_REQUEST[startTime]")) ;
		$l_end = date("Y-m-d H:i:s", strtotime("$tokens[0]-$tokens[1]-$tokens[2] $_REQUEST[endTime]")) ;
		$l_from = mysqli_real_escape_string($mysqli_link, $_REQUEST['from']) ;
		$l_to = mysqli_real_escape_string($mysqli_link, $_REQUEST['to']) ;
		$l_model = mysqli_real_escape_string($mysqli_link, $_REQUEST['model']) ;
		$l_plane = mysqli_real_escape_string($mysqli_link, $_REQUEST['plane']) ;
		$owner = mysqli_real_escape_string($mysqli_link, $_REQUEST['owner']) ;
		$l_day_landing = mysqli_real_escape_string($mysqli_link, $_REQUEST['dayLanding']) ;
		$l_night_landing = mysqli_real_escape_string($mysqli_link, $_REQUEST['nightLanding']) ;
		$pic = mysqli_real_escape_string($mysqli_link, $_REQUEST['pic']) ;
		if ($pic == 0) {
			$pic = 'NULL' ;
			$pilotIsPIC = 1 ;
			$instructorPaid = 0 ;
		} else {
			$pilotIsPIC = 0 ;
			$instructorPaid = 1 ;
		}
		if (isset($_REQUEST['id'])) $id = mysqli_real_escape_string($mysqli_link, $_REQUEST['id']) ;
		if ($_REQUEST['action'] == 'new') {
			$sql = "insert into $table_logbook(l_start, l_end, l_from, l_to, l_model, l_plane, l_pilot, l_is_pic, l_instructor, l_instructor_paid, l_day_landing, l_night_landing,
					l_audit_who, l_audit_time, l_audit_ip)
				values('$l_start', '$l_end', '$l_from', '$l_to', '$l_model', '$l_plane', $owner, $pilotIsPIC, $pic, $instructorPaid, $l_day_landing, $l_night_landing,
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
	default: $error_message = "Action pas encore implémentée." ;
}
if (isset($error_message))
	print("<span class=\"logError\">$error_message</span><br/>") ;
}
?>
<div class="row mb-3">
<label for="periodSelect" class="col-2 col-md-1 col-form-label text-end">Période:</label>
<div class="col-2 col-md-1">
<select id="periodSelect" class="form-control" name="periodName" onchange="selectChanged();">
</select>
</div><!-- col -->
</div><!-- row -->

<?php
$sql = "select l_id, date_format(l_start, '%d/%m/%y') as date, date_format(l_start, '%Y-%m-%d') as date_sql,
	l_model, l_plane, l_pilot, l_is_pic, l_instructor, p.last_name as instructor_name,
	upper(l_from) as l_from, upper(l_to) as l_to, 
	l_start, l_end, timediff(l_end, l_start) as duration,
	timediff(addtime(l_end, '24:00:00'), l_start) as duration_rollover,
	l_day_landing, l_night_landing
	from $table_logbook l 
	left join $table_person p on p.jom_id = l_instructor
	where (l_pilot = $owner or l_instructor = $owner)
	order by l.l_start asc" ;
$result = mysqli_query($mysqli_link, $sql) or journalise($userId, "F", "Erreur systeme a propos de l'access au carnet de route: " . mysqli_error($mysqli_link)) ;
$rows_count = mysqli_num_rows($result) ;

if ($userIsInstructor or $userIsAdmin) {
	print("<p class=\"d-print-none\">En tant qu'instructeur/administrateur, vous pouvez consulter les carnets de vol des autres pilotes: <select id=\"pilotSelect\" onchange=\"selectChanged();\">" ) ;
	print("</select></p>") ;
} else { // ($userIsInstructor or $userIsAdmin)
	print("Carnet de vol de: <select id=\"pilotSelect\" onchange=\"selectChanged();\">
	<option value=\"owner\" selected>$userFullName</option>
	</select><br/><br/>") ;
}

print("<p class=\"d-print-none\">Cette table reprend tous vos vols y compris des vols en dehors des avions de notre aéroclub. Utilisez uniquement la page <a href=\"IntroCarnetVol.php\">IntroCarnetVol.php</a> 
	pour entrer des vols sur les avions de l'aéroclub.</p>") ;
print("<table class=\"table table-responsive table-striped table-bordered w-auto\">\n") ;
ShowTableHeader() ;
print("<tbody claass=\"table-group-divider\">\n") ;

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
$duration_grand_total_hour = 0 ;
$duration_grand_total_minute = $row_owner['pic_minutes'] + $row_owner['dc_minutes'] + $row_owner['fi_minutes'];
$day_grand_landing_total = $row_owner['day_landings'] ;
$night_grand_landing_total = $row_owner['night_landings'] ;
$pic_grand_total_hour = 0 ;
$pic_grand_total_minute =  $row_owner['pic_minutes'];
$dual_grand_total_hour = 0 ;
$dual_grand_total_minute =  $row_owner['dc_minutes'];
$fi_grand_total_hour = 0 ;
$fi_grand_total_minute =  $row_owner['fi_minutes'];
while ($row = mysqli_fetch_array($result)) {
	if (substr($row['duration'], 0, 1) == '-')
		$duration = explode(':', $row['duration_rollover']) ; // Looking like 01:33:00 (in case of over rolling the 24:00:00 mark)
	else
		$duration = explode(':', $row['duration']) ; // Looking like 01:33:00
	$visible = $row['date_sql'] >= $date_from_sql ;
	$duration_grand_total_hour += $duration[0] ;
	$duration_grand_total_minute += $duration[1] ;
	$day_grand_landing_total += $row['l_day_landing'] ;
	$night_grand_landing_total += $row['l_night_landing'] ;
	if ($row['l_instructor'] == '' or $row['l_is_pic']) { // Solo, no instructor
		$pic_grand_total_hour += $duration[0] ;
		$pic_grand_total_minute += $duration[1] ;
	} else 
		if ($row['l_pilot'] == $owner) { // Dual command as student
			$dual_grand_total_hour += $duration[0] ;
			$dual_grand_total_minute += $duration[1] ;
		} else { // Dual command as instructor
			$fi_grand_total_hour += $duration[0] ;
			$fi_grand_total_minute += $duration[1] ;
		}
	if ($visible) {
		$line_count ++ ;
		$duration_total_hour += $duration[0] ;
		$duration_total_minute += $duration[1] ;
		$day_landing_total += $row['l_day_landing'] ;
		$night_landing_total += $row['l_night_landing'] ;
		// DB contains UTC time
		$l_start = gmdate('H:i', strtotime("$row[l_start] UTC")) ;
		$l_end = gmdate('H:i', strtotime("$row[l_end] UTC")) ;
		if ($row['l_instructor'] < 0) $row['instructor_name'] = 'Autre FI' ;
		print("<tr>
			<td class=\"logCell\">
				<a href=\"$_SERVER[PHP_SELF]?action=edit&id=$row[l_id]&owner=$owner\" title=\"Modifier cette ligne\"><i class=\"bi bi-pencil-fill\"></i></a>
				<a href=\"$_SERVER[PHP_SELF]?action=delete&id=$row[l_id]&owner=$owner\" title=\"Effacer cette ligne\"><i class=\"bi bi-trash-fill\"></i></a>
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
		if ($row['l_instructor'] == '' or $row['l_is_pic']) { // Solo, no instructor
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
	}
	print("</tr>\n") ;
}
showEntryRow("new", NULL, -1) ;
// Beautify the total
$duration_total_hour += floor($duration_total_minute / 60) ;
$duration_total_minute = $duration_total_minute % 60 ;
$pic_total_hour += floor($pic_total_minute / 60) ;
$pic_total_minute = $pic_total_minute % 60 ;
$dual_total_hour += floor($dual_total_minute / 60) ;
$dual_total_minute = $dual_total_minute % 60 ;
$fi_total_hour += floor($fi_total_minute / 60) ;
$fi_total_minute = $fi_total_minute % 60 ;
// Beautify the grand total
$duration_grand_total_hour += floor($duration_grand_total_minute / 60) ;
$duration_grand_total_minute = $duration_grand_total_minute % 60 ;
$pic_grand_total_hour += floor($pic_grand_total_minute / 60) ;
$pic_grand_total_minute = $pic_grand_total_minute % 60 ;
$dual_grand_total_hour += floor($dual_grand_total_minute / 60) ;
$dual_grand_total_minute = $dual_grand_total_minute % 60 ;
$fi_grand_total_hour += floor($fi_grand_total_minute / 60) ;
$fi_grand_total_minute = $fi_grand_total_minute % 60 ;
?>
</tbody>
<tfoot class="table-group-divider">
<tr><td colspan="8" class="bg-info">Table Total (for this period)</td>
<td class="bg-info"><?=$duration_total_hour?></td>
<td class="bg-info"><?=$duration_total_minute?></td>
<td class="bg-info"></td>
<td class="bg-info"><?=$day_landing_total?></td>
<td class="bg-info"><?=$night_landing_total?></td>
<td class="bg-info"><?=$pic_total_hour?></td>
<td class="bg-info"><?=$pic_total_minute?></td>
<td class="bg-info"><?=$dual_total_hour?></td>
<td class="bg-info"><?=$dual_total_minute?></td>
<td class="bg-info"><?=$fi_total_hour?></td>
<td class="bg-info"><?=$fi_total_minute?></td>
</tr>
<tr><td  class="bg-info"colspan="8">Grand Total (all known flights)</td>
<td class="bg-info"><?=$duration_grand_total_hour?></td>
<td class="bg-info"><?=$duration_grand_total_minute?></td>
<td class="bg-info"></td>
<td class="bg-info"><?=$day_grand_landing_total?></td>
<td class="bg-info"><?=$night_grand_landing_total?></td>
<td class="bg-info"><?=$pic_grand_total_hour?></td>
<td class="bg-info"><?=$pic_grand_total_minute?></td>
<td class="bg-info"><?=$dual_grand_total_hour?></td>
<td class="bg-info"><?=$dual_grand_total_minute?></td>
<td class="bg-info"><?=$fi_grand_total_hour?></td>
<td class="bg-info"><?=$fi_grand_total_minute?></td>
</tr>

</tfoot>
</table>
<br/>
<div class="text-bg-light p-1">
Sur base des données que vous avez entrées après les vols dans le
carnet de route des avions (à préférer pour avoir les heures moteur) ou celles que vous avez entrées via la dernière ligne de la
table.
Soit <?=$line_count?> par vous-même.
Les heures sont les heures UTC.</div>
<br/>

</div> <!-- container-->
</body>
</html>