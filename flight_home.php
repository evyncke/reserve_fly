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
require_once 'flight_header.php' ;
if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'delete' and isset($_REQUEST['flight_id']) and is_numeric($_REQUEST['flight_id'])) {
	$flight_id = trim($_REQUEST['flight_id']) ;
	if ($flight_id <= 0) die("Invalid flight_id ($flight_id)") ;
	$result = mysqli_query($mysqli_link, "UPDATE $table_flight SET f_date_cancelled = SYSDATE() WHERE f_id = $flight_id")
		or die("Cannot cancel flight $flight_id: " . mysqli_error($mysqli_link)) ;
	journalise($userId, "W", "Flight $flight_id cancelled") ;

}
?>

<div class="page-header hidden-xs">
<h3>Vols découvertes et d'initiation</h3>
</div><!-- page header -->

<div class="page-header hidden-xs">
<h4>Vols futurs à assigner</h4>
</div><!-- page header -->

<table class="table table-striped table-responsive">
<thead>
<tr><th>Créé le</th><th>Type</th><th>Circuit</th><th>Dates</th><th>Heures</th><th>Contact</th><th>Description</th></tr>
</thead>
<tbody>
<?php
$circuits = json_decode(file_get_contents("../voldecouverte/script/circuits.js"), true);
$result = mysqli_query($mysqli_link, "SELECT * FROM $table_flight JOIN $table_pax_role ON f_id = pr_flight JOIN $table_pax ON pr_pax = p_id 
	WHERE pr_role = 'C' AND f_date_cancelled IS NULL AND f_pilot IS NULL AND (f_date_1 >= CURRENT_DATE() or f_date_2 >= CURRENT_DATE())
	ORDER BY f_id ASC") 
	or die("Impossible de lister les vols: " . mysqli_error($mysqli_link));
while ($row = mysqli_fetch_array($result)) {
	$email = ($row['p_email']) ? " <a href=\"mailto:$row[p_email]\"><span class=\"glyphicon glyphicon-envelope\"></span></a>" : "" ; 
	$telephone = ($row['p_tel']) ? " <a href=\"tel:$row[p_tel]\"><span class=\"glyphicon glyphicon-earphone\"></span></a>" : "" ; 
	$edit =  " <a href=\"flight_create.php?flight_id=$row[f_id]\"><span class=\"glyphicon glyphicon-pencil\"></span></a> " ;
	$print =  " <a href=\"flight_pdf.php?flight_id=$row[f_id]\"><span class=\"glyphicon glyphicon-print\"></span></a> " ;
	$cancel =  " <a href=\"flight_home.php?action=delete&flight_id=$row[f_id]\"><span class=\"glyphicon glyphicon-trash\"></span></a> " ;
	$type = ($row['f_type'] == 'D') ? 'découverte' : 'initiation' ;
	if ($row['f_type'] == 'D')
		$circuit_name = (isset($circuits[$row['f_circuit']])) ? $circuits[$row['f_circuit']] : "Circuit #$row[f_circuit] inconnu" ;
	else
		$circuit_name = '' ;
	$description = db2web($row['f_description']) ;
	print("<tr><td>$edit$print$cancel$row[f_date_created]</td><td>$type</td><td>$circuit_name</td><td>$row[f_date_1]<br/>$row[f_date_2]</td><td>$row[f_schedule]</td><td>$row[p_fname] <b>$row[p_lname]$email$telephone</b></td><td>$description</td></tr>\n") ;
}
?>
</tbody>
</table>

<div class="page-header hidden-xs">
<h4>Vols prévus ce jour</h4>
</div><!-- page header -->

<table class="table table-striped table-responsive">
<thead>
<tr><th>Vol (LT)</th><th>Pilote</th><th>Type</th><th>Contact</th><th>Description</th></tr>
</thead>
<tbody>
<?php
$result = mysqli_query($mysqli_link, "SELECT *, SYSDATE() as today 
	FROM $table_flight JOIN $table_pax_role ON f_id = pr_flight JOIN $table_pax ON pr_pax = p_id
	JOIN $table_bookings AS b ON f_booking = b.r_id JOIN $table_person on f_pilot = jom_id
	WHERE pr_role = 'C' AND DATE(b.r_start) = CURDATE()
	ORDER BY f_id DESC") 
	or die("Impossible de lister les vols: " . mysqli_error($mysqli_link));
while ($row = mysqli_fetch_array($result)) {
	$email = ($row['p_email']) ? " <a href=\"mailto:$row[p_email]\"><span class=\"glyphicon glyphicon-envelope\"></span></a>" : "" ; 
	$telephone = ($row['p_tel']) ? " <a href=\"tel:$row[p_tel]\"><span class=\"glyphicon glyphicon-earphone\"></span></a>" : "" ; 
	$edit =  " <a href=\"flight_create.php?flight_id=$row[f_id]\"><span class=\"glyphicon glyphicon-pencil\"></span></a> " ;
	$print =  " <a href=\"flight_pdf.php?flight_id=$row[f_id]\"><span class=\"glyphicon glyphicon-print\"></span></a> " ;
	$type = ($row['f_type'] == 'D') ? 'découverte' : 'initiation' ;
	$description = nl2br(db2web($row['f_description'])) ;
	if ($row['f_date_flown'])
		$date_vol = "ATD $row[f_date_flown] ($row[r_plane])" ;
	else if ($row['r_start'])
		$date_vol = "ETD $row[r_start] ($row[r_plane])"  ;
	else
		$date_vol = "à déterminer" ;
	$pilote = db2web("$row[first_name] <b>$row[last_name]</b>") ;
	$pilote .= " <a href=\"mailto:$row[email]\"><span class=\"glyphicon glyphicon-envelope\"></span></a> " ;
	$pilote .= " <a href=\"tel:$row[cell_phone]\"><span class=\"glyphicon glyphicon-earphone\"></span></a> " ;
	print("<tr><td>$edit$print$date_vol</td><td>$pilote</td><td>$type</td><td>$row[p_fname] <b>$row[p_lname]$email$telephone</b></td><td>$description</td></tr>\n") ;
}
?>
</tbody>
</table>


<?php
require_once 'flight_trailer.php' ;
?>