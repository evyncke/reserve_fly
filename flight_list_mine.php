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

?>

<div class="page-header hidden-xs">
<h3>Tous mes vols non terminés</h3>
</div><!-- page header -->

<table class="table table-striped table-responsive">
<thead>
<tr><th>Créé le</th><th>Etat</th><th>Depuis</th><th>Vol</th><th>Type</th><th>Contact</th><th>Description</th></tr>
</thead>
<tbody>
<?php
$result = mysqli_query($mysqli_link, "SELECT *, SYSDATE() as today 
	FROM $table_flight JOIN $table_pax_role ON f_id = pr_flight JOIN $table_pax ON pr_pax = p_id
	LEFT JOIN $table_bookings AS b ON f_booking = b.r_id
	WHERE pr_role = 'C' AND f_pilot = $userId AND f_date_flown IS NULL
	ORDER BY f_id DESC") 
	or die("Impossible de lister les vols: " . mysqli_error($mysqli_link));
while ($row = mysqli_fetch_array($result)) {
	$email = ($row['p_email']) ? " <a href=\"mailto:$row[p_email]\"><span class=\"glyphicon glyphicon-envelope\"></span></a>" : "" ; 
	$telephone = ($row['p_tel']) ? " <a href=\"tel:$row[p_tel]\"><span class=\"glyphicon glyphicon-earphone\"></span></a>" : "" ; 
	$edit =  " <a href=\"flight_create.php?flight_id=$row[f_id]\"><span class=\"glyphicon glyphicon-pencil\"></span></a> " ;
	$print =  " <a href=\"flight_pdf.php?flight_id=$row[f_id]\"><span class=\"glyphicon glyphicon-print\"></span></a> " ;
	$type = ($row['f_type'] == 'D') ? 'découverte' : 'initiation' ;
	$description = nl2br(db2web($row['f_description'])) ;
	if ($row['f_date_cancelled'])
		$status = "Annulé</td><td>$row[f_date_cancelled]" ;
	else if ($row['f_date_flown'])
		$status = "Accompli</td><td>$row[f_date_flown]" ;
	else if ($row['f_date_linked'])
		$status = "Avion réservé</td><td>$row[f_date_linked]" ;
	else if ($row['f_date_assigned'])
		$status = "Pilote sélectionné</td><td>$row[f_date_assigned]" ;
	else
		$status = "Attente pilote</td><td>" ;
	if ($row['f_date_flown'])
		$date_vol = "$row[f_date_flown] ($row[r_plane])" ;
	else if ($row['r_start'])
		$date_vol = "$row[r_start] ($row[r_plane])"  ;
	else
		$date_vol = "à déterminer" ;
	print("<tr><td>$edit$print$row[f_date_created]</td><td>$status</td><td>$date_vol</td><td>$type</td><td>$row[p_fname] <b>$row[p_lname]$email$telephone</b></td><td>$description</td></tr>\n") ;
}
?>
</tbody>
</table>

<?php
require_once 'flight_trailer.php' ;
?>
