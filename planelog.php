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

if ($userId <= 0) die("Vous devez être connecté ($userIsAdmin/$userId)") ;

$plane = strtoupper(mysqli_real_escape_string($mysqli_link, $_REQUEST['plane'])) ;
if ($plane == '') die("Missing parameter plane") ;
$page = mysqli_real_escape_string($mysqli_link, $_REQUEST['page']) ;
if (!is_numeric($page) and $page != '') die("Invalid page number") ;
$rows_per_page = 20 ;


?><html>
<head>
<link rel="stylesheet" type="text/css" href="log.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<title>Carnet de route <?=$plane?></title>
<script>
const
	// preset Javascript constant fill with the right data from db.php PHP variables
	userFullName = '<?=$userFullName?>' ;
	userName = '<?=$userName?>' ;
	userId = <?=$userId?> ;
	userIsPilot = <?=($userIsPilot)? 'true' : 'false'?> ;
	userIsAdmin = <?=($userIsAdmin)? 'true' : 'false'?> ;
	userIsInstructor = <?=($userIsInstructor)? 'true' : 'false'?> ;
	userIsMechanic = <?=($userIsMechanic)? 'true' : 'false'?> ;

function planeChanged(elem) {
	window.location.href = '<?=$_SERVER['PHP_SELF']?>?plane=' + elem.value ;
}

function init() {
	var planeSelect = document.getElementById('planeSelect') ;
	if (planeSelect) planeSelect.value = '<?=$plane?>' ;
}
</script>
</head>
<body onload="init();">
<center><h2>Carnet de route de <?=$plane?></h2></center>
<?php
print("Carnet de route de: <select id=\"planeSelect\" onchange=\"planeChanged(this);\">" ) ;
$result = mysqli_query($mysqli_link, "select * from $table_planes
	where actif != 0 and ressource = 0
	order by id") ;
$plane_details = array() ;
while ($row = mysqli_fetch_array($result)) {
	$row['id'] = strtoupper($row['id']) ;
	print("<option value=\"$row[id]\">$row[id]</option>\n") ;
	if ($row['id'] == $plane)
		$plane_details = $row ;
}
print("</select> Page: ") ;
// Find how many rows in the whole logbook, it must be IDENTICAL to the real display SQL SELECT further down as some pilots are no more members...
$result = mysqli_query($mysqli_link, "select count(*)
	from $table_logbook l
        join jom_users p on l_pilot=p.id
	left join jom_users i on l_instructor = i.id
	where l_plane = '$plane' and l_booking is not null") ;
$row = mysqli_fetch_array($result) ;
$row_count = $row[0] ;
$page_count = ceil($row_count / $rows_per_page) ;
if ($page == '') $page = $page_count ; // Without page indication, the last page is displayed
$first_row = ($page - 1) * $rows_per_page ;
// Try to draw base page navigation...
if ($page > 1) 
	print("<a href=$_SERVER[PHP_SELF]?plane=$plane&page=1>&lt;&lt;</a> ") ;
if ($page > 2) 
	print("<a href=$_SERVER[PHP_SELF]?plane=$plane&page=" . ($page - 1) . ">&lt;</a> ") ;
print("$page ") ;
if ($page < $page_count) 
	print("<a href=$_SERVER[PHP_SELF]?plane=$plane&page=" . ($page + 1) . ">&gt;</a> ") ;
if ($page < $page_count) 
	print("<a href=$_SERVER[PHP_SELF]?plane=$plane&page=$page_count>&gt;&gt></a> ") ;

?>
<br/>
<table class="logTable">
<thead>
<tr>
<th class="logHeader">Date</th>
<th class="logHeader">Pilot(s)</th>
<th class="logHeader" colspan="2">Airports</th>
<th class="logHeader" colspan="2">Time</th>
<th class="logHeader">Total time</th>
<th class="logHeader">Type of</th>
<th class="logHeader">Engine index</th>
<?php 
if ($plane_details['compteur_vol'] != 0)
	print("<th class=\"logHeader\">Flight index</th>\n") ;
?>
</tr>
<tr>
<th class="logLastHeader">(dd/mm/yy)</th>
<th class="logLastHeader"></th>
<th class="logLastHeader">Origin</th>
<th class="logLastHeader">Destination</th>
<th class="logLastHeader">Takeoff</th>
<th class="logLastHeader">Landing</th>
<th class="logLastHeader">hh:mm</th>
<th class="logLastHeader">flight</th>
<th class="logLastHeader">(end)</th>
<?php 
if ($plane_details['compteur_vol'] != 0)
	print("<th class=\"logLastHeader\">(end)</th>\n") ;
?>
</tr>
</thead>
<tbody>
<?php
// Display up to $rows_per_page rows from $first_row
$sql = "select date_format(l_start, '%d/%m/%y') as date, l_start, l_end, l_end_hour, l_end_minute, 
	timediff(l_end, l_start) as duration,
	l_flight_end_hour, l_flight_end_minute,
	upper(l_from) as l_from, upper(l_to) as l_to, l_flight_type, p.name as pilot_name, i.name as instructor_name
	from $table_logbook l 
	join jom_users p on l_pilot=p.id
	left join jom_users i on l_instructor = i.id
	where l_plane = '$plane' and l_booking is not null
	order by l_start asc
	limit $first_row, $rows_per_page" ;
$result = mysqli_query($mysqli_link, $sql) or die("Erreur systeme a propos de l'access au carnet de route: " . mysqli_error($mysqli_link)) ;
$duration_total_hour = 0 ;
$duration_total_minute = 0 ;
$pic_total_hour = 0 ;
$pic_total_minute =  0;
$dual_total_hour = 0 ;
$dual_total_minute =  0;
$fi_total_hour = 0 ;
$fi_total_minute =  0;
$line_count = 0 ;
while ($row = mysqli_fetch_array($result)) {
	$line_count ++ ;
	// Need to change duration from HH:MM:SS into HH:MM
	$duration = explode(':', $row['duration']) ;
	$duration = "$duration[0]:$duration[1]" ;
	// Handling character sets...
	$pilot_name = ($convertToUtf8) ? iconv("ISO-8859-1", "UTF-8", $row['pilot_name']) : $row['pilot_name'] ; 
	$instructor_name = ($convertToUtf8) ? iconv("ISO-8859-1", "UTF-8", $row['instructor_name']) : $row['instructor_name'] ; 
	// As the OVH MySQL server does not have the timezone support, needs to be done in PHP
	$l_start = gmdate('H:i', strtotime("$row[l_start] $default_timezone")) ;
	$l_end = gmdate('H:i', strtotime("$row[l_end] $default_timezone")) ;
	$instructor = ($instructor_name != '') ? " /<br/>$instructor_name" : '' ;
	if ($row['l_end_minute'] < 10)
			$row['l_end_minute'] = "0$row[l_end_minute]" ;
	if ($row['l_flight_end_minute'] < 10)
			$row['l_flight_end_minute'] = "0$row[l_flight_end_minute]" ;
	print("<tr>
		<td class=\"logCell\">$row[date]</td>
		<td class=\"logCell\">$pilot_name$instructor</td>
		<td class=\"logCell\">$row[l_from]</td>
		<td class=\"logCell\">$row[l_to]</td>
		<td class=\"logCell\">$l_start</td>
		<td class=\"logCell\">$l_end</td>
		<td class=\"logCell\">$duration</td>
		<td class=\"logCell\">$row[l_flight_type]</td>
		<td class=\"logCell\">$row[l_end_hour]:$row[l_end_minute]</td>\n") ;
	if ($plane_details['compteur_vol'] != 0)
		print("<td class=\"logCell\">$row[l_flight_end_hour]:$row[l_flight_end_minute]</td>\n") ;
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
?>
<!-- tr><td colspan="7" class="logTotal">Total</td>
<td class="logTotal"><?=$duration_total_hour?></td>
<td class="logTotal"><?=$duration_total_minute?></td>
<td class="logTotal"></td>
<td class="logTotal"><?=$pic_total_hour?></td>
<td class="logTotal"><?=$pic_total_minute?></td>
<td class="logTotal"><?=$dual_total_hour?></td>
<td class="logTotal"><?=$dual_total_minute?></td>
<td class="logTotal"><?=$fi_total_hour?></td>
<td class="logTotal"><?=$fi_total_minute?></td>
</tr -->
</tbody>
</table>
<br>
Sur base des donn&eacute;es que vous avez entr&eacute;es apr&egrave;s les vols dans le
carnet de route des avions. Heure affich&eacute;e en heure universelle.
<?php
$version_php = date ("Y-m-d H:i:s.", filemtime('planelog.php')) ;
$version_css = date ("Y-m-d H:i:s.", filemtime('log.css')) ;
?>
<hr>
<div class="copyright">R&eacute;alisation: Eric Vyncke, janvier 2015, pour RAPCS, Royal A&eacute;ro Para Club de Spa<br>
Versions: PHP=<?=$version_php?>, CSS=<?=$version_css?></div>
</body>
</html>
