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
<script src="pilots.js"></script>
<div class="page-header hidden-xs">
<h3>Assignation des pilotes ces 90 derniers jours</h3>
</div><!-- page header -->

<table class="table table-striped table-responsive col-md-6 col-xs-12">
<thead>
<tr><th>Assigné le</th><th>Date vol (LT)</th><th>Pilote</th><th>Type</th><th>Client</th></tr>
</thead>
<tbody>
<?php
// TODO partial list only and only the most recent 
$result = mysqli_query($mysqli_link, "SELECT * 
	FROM $table_flight JOIN $table_pax_role ON pr_flight = f_id AND pr_role='C' JOIN $table_pax AS x ON pr_pax = x.p_id
		LEFT JOIN $table_flights_pilots AS p ON f_pilot = p.p_id JOIN $table_person ON p.p_id = jom_id
		LEFT JOIN $table_bookings AS b ON f_booking = b.r_id
	ORDER BY f_date_assigned DESC") 
	or die("Impossible de lister les assignations: " . mysqli_error($mysqli_link));
while ($row = mysqli_fetch_array($result)) {
	if ($row['f_date_flown'])
		$date_vol = "ATD $row[f_date_flown] ($row[r_plane])" ;
	else if ($row['r_start'])
		$date_vol = "ETD $row[r_start] ($row[r_plane])"  ;
	else
		$date_vol = "à déterminer" ;
	$edit =  " <a href=\"flight_create.php?flight_id=$row[f_id]\"><span class=\"glyphicon glyphicon-pencil\"></span></a> " ;
	print("<tr><td>$edit$row[f_date_assigned]</td><td>$date_vol</td><td><b>" . db2web($row['last_name']) . '</b> ' . db2web($row['first_name']) . "</td><td>$row[f_type]</td><td>$row[p_fname] <b>$row[p_lname]</d></td></tr></form>\n") ;
}
?>
</tbody>
</table>


<?php
require_once 'flight_trailer.php' ;
?>