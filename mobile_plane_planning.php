<?php
/*
   Copyright 2020-2024 Eric Vyncke

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

require_once "dbi.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}

require_once 'mobile_header5.php' ;
if (!($userIsAdmin or $userIsInstructor)) journalise($userId, "F", "Vous devez être admin ou FI pour voir cette page") ;

?>
<div class="container-fluid">
<h2>Echéances des avions</h2>
<table class="col-sm-12 col-lg-8 table table-hover table-bordered table-striped">
<thead>
<tr class="text-center"><th>Plane</th> <th>Last index</th> <th colspan="3">Inspections</th>        <th colspan="2">Time limit</th> <th>Circ. Equip 4 ed5</th>    <th>&lt; 30 days</th>            <th>Mag.</th> <th>Pesage</th><th>PLB</th><th>Instruments</th></tr>
<tr class="text-center"><th>     </th> <th>                       </th> <th>50h</th><th>100h</th><th>200h</th>  <th>Eng</th><th>Prop</th>       <th>ATC -Enc. - Alti</th>    <th>CN</th>                      <th>500h</th> <th>10 y </th><th>Date</th><th>Date</th></tr>
</thead>
<tbody>
<?php
function GenCell($value) {
	global $current_value, $current_value_pilot ;
	
	if ($value < 0)
		return "<td>N/A</td>" ;
	if ($value - 5 < $current_value or $value - 5 < $current_value_pilot)
		return "<td class=\"bg-danger text-bg-danger\">$value</td>" ;
	if ($value - 10 < $current_value or $value - 10 < $current_value_pilot)
		return "<td class=\"bg-warning text-bg-warning\">$value</td>" ;
	return "<td>$value</td>" ;
}
function GenCellDate($value, $orangeDays, $redDays) {	
	if ($value == "0000-00-00")
		return "<td class=\"bg-warning text-bg-warning\">$value</td>" ;
	$today=time();
	$date=strtotime($value);
	$day_diff = $date - $today;
    $day_diff = floor($day_diff/(60*60*24));
	if($day_diff<$redDays)
		return "<td class=\"bg-danger text-bg-danger\">$value</td>" ;
	if($day_diff<$orangeDays)
		return "<td class=\"bg-warning text-bg-warning\">$value</td>" ;	
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
	$current_value_pilot=0;
	if (isset($row2['compteur_pilote'])) {
		$current_value_pilot = $row2['compteur_pilote'] ;
	}

	$cnDateString=$row['cn'];
	$year=date('Y');
	if(strlen($cnDateString)==5) {
		$today=date('Y-m-d');
		$cnDateString=$year."-".substr($row['cn'],3,2)."-".substr($row['cn'],0,2);
		if($today>$cnDateString) {
			$cnDate=new DateTime($cnDateString);
			$yearInterval = new DateInterval('P1Y');
			$cnDate=$cnDate->add($yearInterval);
    		$cnDateString=$cnDate->format('Y-m-d') ;
		}
	}
	else {
		$cnDateString="?".$cnDateString."?";
	}

	print("<tr class=\"text-center\"><td><a href=\"plane_chart.php?id=$row[id]\">" . strtoupper($row['id']) . "</a></td><td>$current_value_pilot</td>") ;
	// Type_entretien... human encoding :-( 50h, 50h->200h, 100h
	if (stripos($row['type_entretien'], '50h') === 0)
		print("". GenCell($row['entretien']) . "<td></td><td></td>") ;
	else if (stripos($row['type_entretien'], '100h') === 0)
		print("<td></td>". GenCell($row['entretien']) . "<td></td>") ;
	else if (stripos($row['type_entretien'], '200h') === 0)
		print("<td></td><td></td>". GenCell($row['entretien'])) ;
	else // Assuming 50h
		print("<td>$row[entretien] ????</td><td></td><td></td>") ;
	print("<td><table><tbody><tr><td>".GenCell($row['limite_moteur_heure'])."</td></tr><tr><td>".GenCellDate($row['limite_moteur_12ans'],100,30)."</td></tr></tbody></table></td>" .
		GenCell($row['limite_helice']) . 
		GenCellDate($row['instrument_date_limite'], 100, 30). 
		GenCellDate($cnDateString, 100, 30).
		GenCell($row['limite_magnetos']) .
	    GenCellDate($row['pesage'], 100, 30).
		GenCellDate($row['plb_date_limite'], 100, 30).
		GenCellDate($row['instrument_date_limite'], 100, 30).
		"</tr>\n") ;
}
?>
</tbody>
</table>
</div><!-- container-fluid -->
</body>
</html>