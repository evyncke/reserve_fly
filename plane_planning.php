<?php
/*
   Copyright 2020 Eric Vyncke

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

/* Reference is https://tools.ietf.org/html/rfc5545
Charset is UTF-8
https://icalendar.org/validator.html
TODO
The "charset" Content-Type parameter MUST be used in MIME transports
   to specify the charset being used. */

ob_start("ob_gzhandler");

require_once "dbi.php" ;

?>
<html lang="fr">
<head>
<style>
body { background: #f0f0f0; color: #363636; font-family: Arial, Helvetica, sans-serif; font-size: 12;}
table {background-color: white; margin-right: auto; margin-left: auto; border: 3px solid black;
	padding: 10px; border-collapse: collapse; font-size: 12;}
thead tr:nth-child(odd) {border-bottom: none;}
th {text-align: center; padding-right: 10px; padding-left: 10px; font-weight: bold; border-left: 1px solid gray;}
tbody tr:nth-child(even) {background-color: lightgray;}
td {text-align: center; border-left: 1px solid gray ; border-bottom: 1px dotted gray;}
.copyright {color: gray; font-size: smaller; font-style: oblique; text-align: center; border-top: 1px gray ;}
.orange {background-color: orange; }
.red {background-color: red; color: white; }
</style>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<title>Echéances des avions</title>
</head>
<body>
<h1>Echéances des avions</h1>
<table border="1" class="logTable">
<thead>
<tr><th>Plane</th> <th>Last mechanics /</th> <th colspan="3">Inspections</th>        <th colspan="2">Time limit</th> <th colspan="3">Circ. Equip 4 ed5</th>    <th>&lt; 30 days</th>            <th>Mag.</th> <th>Pesage</th></tr>
<tr><th>     </th> <th>pilots          </th> <th>50h</th><th>100h</th><th>200h</th>  <th>Eng</th><th>Prop</th>       <th>ATC</th><th>Enc.</th><th>Alti</th>    <th>CN</th>                      <th>500h</th> <th>10 y </th></tr>
</thead>
<tbody>
<?php
function GenCell($value) {
	global $current_value, $current_value_pilot ;
	
	if ($value < 0)
		return "<td>N/A</td>" ;
	if ($value - 5 < $current_value or $value - 5 < $current_value_pilot)
		return "<td class=\"red\">$value</td>" ;
	if ($value - 10 < $current_value or $value - 10 < $current_value_pilot)
		return "<td class=\"orange\">$value</td>" ;
	return "<td>$value</td>" ;
}
$result = mysqli_query($mysqli_link, "SELECT * from $table_planes WHERE ressource = 0 AND actif != 0 ORDER BY id")
	or die("Cannot read $tables_planes: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
	$current_value = ($row['compteur_vol'] == 0) ? $row['compteur'] : $row['compteur_vol_valeur'] ;
	$index_column = ($row['compteur_vol'] == 0) ? 'l_end_hour' : 'l_flight_end_hour' ;
	$result2 = mysqli_query($mysqli_link, "select $index_column as compteur_pilote, l_end as compteur_pilote_date, concat(first_name, ' ', last_name) as compteur_pilote_nom 
		from $table_logbook  l join $table_bookings r on l_booking = r_id join $table_person p on jom_id = if(l_audit_who <= 0, if(l_instructor is null, l_pilot, l_instructor), l_audit_who)
		where l_plane = '$row[id]' and l_booking is not null and l_end_hour > 0
		order by compteur_pilote_date desc limit 0,1")
		or die("Cannot get pilote engine time:" . mysqli_error($mysqli_link)) ;

	$row2 = mysqli_fetch_array($result2) ;
	$current_value_pilot = $row2['compteur_pilote'] ;

	print("<tr><td><a href=\"plane_chart.php?id=$row[id]\">" . strtoupper($row['id']) . "</a></td><td>$current_value / <br/>$current_value_pilot</td>") ;
	// Type_entretien... human encoding :-( 50h, 50h->200h, 100h
	if (stripos($row['type_entretien'], '50h') === 0)
		print("". GenCell($row['entretien']) . "<td></td><td></td>") ;
	else if (stripos($row['type_entretien'], '100h') === 0)
		print("<td></td>". GenCell($row['entretien']) . "<td></td>") ;
	else if (stripos($row['type_entretien'], '200h') === 0)
		print("<td></td><td></td>". GenCell($row['entretien'])) ;
	else // Assuming 50h
		print("<td>$row[entretien] ????</td><td></td><td></td>") ;
	print("<td>$row[limite_moteur_heure]<br/>$row[limite_moteur_12ans]</td>" . GenCell($row['limite_helice']) . "
		<td></td><td></td><td></td>
		<td>$row[cn]</td>" . 
		GenCell($row['limite_magnetos']) . "
		<td>$row[pesage]</td>
		</tr>\n") ;
}
?>
</tbody>
</table>
<?php
$version_php = date ("Y-m-d H:i:s.", filemtime('plane_planning.php')) ;
?>
<hr>
<div class="copyright">R&eacute;alisation: Eric Vyncke, février 2020, pour RAPCS, Royal A&eacute;ro Para Club de Spa<br>
Versions: PHP=<?=$version_php?></div>
</body>
</html>
</body>
</html>