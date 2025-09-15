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

$header_postamble = "<script>
function onChange() {
	window.location.href = '$_SERVER[PHP_SELF]?n=' + document.getElementsByName('n')[0].value +
		'&days=' + document.getElementsByName('days')[0].value +
		'&class=' + document.getElementsByName('class')[0].value +
		'&plane=' + document.getElementsByName('plane')[0].value ;
}
</script>" ;

require_once 'mobile_header5.php' ;
$plane = (isset($_REQUEST['plane']) && $_REQUEST['plane'] != '') ? mysqli_real_escape_string($mysqli_link, $_REQUEST['plane']) : '%' ;
if (strlen($plane) > 6) die("Invalid value for plane") ;
$plane_group = ($plane == '%') ? '' : ', l_plane' ;
$days = (isset($_REQUEST['days'])) ? $_REQUEST['days'] : 90 ;
if (!is_numeric($days)) die("Invalid value for days") ;
$n = (isset($_REQUEST['n'])) ? $_REQUEST['n'] : 10 ;
if (!is_numeric($n)) die("Invalid value for n") ;
$class = (isset($_REQUEST['class'])) ? mysqli_real_escape_string($mysqli_link, $_REQUEST['class']) : 'A' ;
switch($class) {
	case 'A': $class_filter = " and m.group_id in ($joomla_pilot_group, $joomla_student_group)" ; break ;
	case 'P': $class_filter = " and m.group_id = $joomla_pilot_group" ; break ;
	case 'S': $class_filter = " and m.group_id = $joomla_student_group" ; break ;
	default: journalise($userId, "F", "Invalid class") ;
}

?>
<div class="container-fluid">

<h2>Le top-<?=$n?> des membres</h2>

<p>Voici le top <select name="n" onChange="onChange() ;">
<?php
foreach (array(3, 10, 25, 50) as $key) {
	if ($n == $key)
		print("<option value=\"$key\" selected>$key</option>\n") ;
	else
		print("<option value=\"$key\">$key</option>\n") ;
}
print("</select> des ") ;
print("<select name=\"class\" onChange=\"onChange() ;\">\n") ;
foreach (array('A' => 'pilotes et élèves', 'P' => 'des pilotes', 'S'=> 'des élèves') as $key => $name) {
	if ($class == $key)
		print("<option value=\"$key\" selected>$name</option>\n") ;
	else
		print("<option value=\"$key\">$name</option>\n") ;
}
print("</select>") ;
print("sur <select name=\"days\" onChange=\"onChange() ;\">\n") ;
foreach (array(1 => 'la journée', 7 => 'la semaine', 30 => 'le mois', 90 => 'le trimestre', 365 => 'l\'année') as $key => $name) {
	if ($days == $key)
		print("<option value=\"$key\" selected>$name</option>\n") ;
	else
		print("<option value=\"$key\">$name</option>\n") ;
}
print("</select>") ;
print("sur <select name=\"plane\" onChange=\"onChange() ;\">\n") ;
if ($plane == '%')
	print("<option value=\"%\" selected>tous les avions</option>\n") ;
else
	print("<option value=\"%\">tous les avions</option>\n") ;
$result = mysqli_query($mysqli_link, "select upper(id) as plane from $table_planes where actif != 0 and ressource = 0 order by plane asc") 
    or journalise($userId, "F", "Cannot select planes: ".mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
	if ($plane == $row['plane'])
		print("<option value=\"$row[plane]\" selected>$row[plane]</option>\n") ;
	else
		print("<option value=\"$row[plane]\">$row[plane]</option>\n") ;
}
print("</select>.<br/>") ;
?>
<table class="table table-bordered table-hover table-striped table-sm w-auto">
<thead>
<tr>
<th class="text-start">Position</th>
<th class="text-start">Pilote</th>
<th class="text-end">Nbr de vols</th>
<th class="text-end">Durée totale (heures)</th>
</tr>
</thead>
<tbody class="table-group-divider">
<?php
$sql = "select l_pilot, u.name as name, count(*) as flight_count, round(sum(timestampdiff(minute, l_start, l_end))/60,1) as flight_duration, hide_flight_time
		from $table_logbook left join jom_users u on l_pilot = u.id left join jom_user_usergroup_map m on m.user_id = u.id
		join $table_person p on p.jom_id = l_pilot
		where l_end > subdate(sysdate(), $days) and l_plane like '$plane' $class_filter
		group by l_pilot $plane_group
		order by flight_duration desc
		limit 0, $n" ;
$result = mysqli_query($mysqli_link, $sql) or die("Erreur systeme a propos de l'access au journal: " . mysqli_error($mysqli_link)) ;
$line = 1 ;
while ($row = mysqli_fetch_array($result)) {
	print("<tr>
		<td class=\"text-start\">$line</td>\n") ;
	$row['name'] = db2web($row['name']) ; 
	$moreInfo = ($userIsBoardMember or $userIsInstructor) ? " (pour OA/FI: $row[name])" : "" ;
	if ($row['hide_flight_time'] and $row['l_pilot'] != $userId)
		print("<td class=\"text-start\" title=\"Le pilote ne veut pas être affiché(e)$moreInfo\"><i class=\"bi bi-shield-shaded\"></i>...</td>\n") ;
	else {
		print("<td class=\"text-start\"><a href=\"mobile_profile.php?displayed_id=$row[l_pilot]\">$row[name]</a></td>\n") ;
	}
	print("<td class=\"text-end\">$row[flight_count]</td>
		<td class=\"text-end\">$row[flight_duration]</td>
		</tr>\n") ;
	$line++ ;
}
?>
</tbody>
</table>
<p><em>Vous pouvez cacher votre temps de vol dans votre <a href="mobile_profile.php">profil</a> si vous ne souhaitez pas apparaître dans ce classement.</em></p>
</div><!-- container-fluid-->
</body>
</html>