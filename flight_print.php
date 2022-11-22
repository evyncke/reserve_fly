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

$flight_id = (isset($_REQUEST['flight_id'])) ? trim($_REQUEST['flight_id']) : 0 ;
if (!is_numeric($flight_id)) die("Invalid ID: $flight_id") ;

require_once 'flight_header.php' ;
?>

<div class="page-header hidden-xs">
<h3>Détails du vol</h3>
</div><!-- page header -->

<table class="table table-striped table-responsive">
<thead>
<tr><th>Créé le</th><th>Etat</th><th>Depuis</th><th>Type</th><th>Contact</th><th>Description</th></tr>
</thead>
<tbody>
<?php
$result = mysqli_query($mysqli_link, "SELECT *, SYSDATE() as today 
	FROM $table_flight JOIN $table_pax_role ON f_id = pr_flight JOIN $table_pax ON pr_pax = p_id 
	WHERE pr_role = 'C' AND f_id = $flight_id ORDER BY f_id DESC") 
	or die("Impossible de lister les vols: " . mysqli_error($mysqli_link));
while ($row = mysqli_fetch_array($result)) {
	$email = ($row['p_email']) ? " <a href=\"mailto:$row[p_email]\"><span class=\"glyphicon glyphicon-envelope\"></span>" : "" ; 
	$telephone = ($row['p_tel']) ? " <a href=\"tel:$row[p_tel]\"><span class=\"glyphicon glyphicon-earphone\"></span>" : "" ; 
	$edit =  " <a href=\"flight_create.php?flight_id=$row[f_id]\"><span class=\"glyphicon glyphicon-pencil\"></span> " ;
	$edit =  " <a href=\"flight_print.php?flight_id=$row[f_id]\"><span class=\"glyphicon glyphicon-print\"></span> " ;
	$type = ($row['f_type'] == 'D') ? 'découverte' : 'initiation' ;
	$description = db2web($row['f_description']) ;
	if ($row['f_date_cancelled'])
		$status = "Annulé</td><td>$row[f_date_cancelled]" ;
	else if ($row['f_date_flown'])
		$status = "Accompli</td><td>$row[f_date_flown]" ;
	else if ($row['f_date_linked'])
		$status = "Avion</td><td>$row[f_date_linked]" ;
	else if ($row['f_date_assigned'])
		$status = "Pilote sélectionné</td><td>$row[f_date_assigned]" ;
	else
		$status = "Attente pilote</td><td>" ;
	print("<tr><td>$edit$print$row[f_date_created]</td><td>$status</td><td>$type</td><td>$row[p_fname] <b>$row[p_lname]$email$telephone</b></td><td>$description</td></tr>\n") ;
}
?>
</tbody>
</table>

Impression PDF <a href="flight_pdf.php?flight_id=<?=$flight_id?>">à réaliser</a>.
<?php
require_once 'flight_trailer.php' ;
?>
