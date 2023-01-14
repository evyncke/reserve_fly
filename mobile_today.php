<?php
/*
   Copyright 2013-2023 Eric Vyncke

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

require_once 'mobile_header.php' ;

$displayTimestamp = (isset($_REQUEST['time'])) ? intval($_REQUEST['time']) : time() ;

setlocale(LC_TIME, "fr_BE.utf8");
$today = date("l j F", $displayTimestamp) ;
$sql_date = date('Y-m-d', $displayTimestamp) ;

?> 
<div class="container">

<div class="page-header">
<h3>Réservations du <?=$today?></h3>
</div> <!-- row -->

<div class="row">
<table class="col-sm-12 table table-responsive table-striped">
	<tr><th>Avion</th><th>De</th><th>A</th><th>Pilote</th><th>Commentaire</th></tr>
<?php
	$result = mysqli_query($mysqli_link, "SELECT *, i.last_name as ilast_name, i.first_name as ifirst_name, i.cell_phone as icell_phone, i.jom_id as iid,
		pi.last_name as plast_name, pi.first_name as pfirst_name, pi.name as pname, pi.cell_phone as pcell_phone, pi.jom_id as pid
		FROM $table_bookings 
		JOIN $table_person pi ON pi.jom_id = r_pilot
		LEFT JOIN $table_person i ON i.jom_id = r_instructor		
		JOIN $table_planes p ON r_plane = p.id
		WHERE  p.actif = 1 AND p.ressource = 0 AND r_cancel_date IS NULL AND (DATE(r_stop) = '$sql_date' OR (DATE(r_start) <= '$sql_date' and '$sql_date' <= DATE(r_stop)))
		ORDER BY r_start, r_plane ASC LIMIT 0,20")
		or die("Cannot retrieve bookings($plane): " . mysqli_error($mysqli_link)) ;
	while ($row = mysqli_fetch_array($result)) {
		$ptelephone = ($row['pcell_phone'] and ($userId > 0)) ? " <a href=\"tel:$row[pcell_phone]\"><span class=\"glyphicon glyphicon-earphone\"></span></a>" : '' ;
		$pname = ($row['pfirst_name'] == '') ? $row['pname'] : 
			'<span class="hidden-xs">' . db2web($row['pfirst_name']) . ' </span><b>' . db2web($row['plast_name']) . '</b>' ;
		$itelephone = ($row['icell_phone'] and ($userId > 0)) ? " <a href=\"tel:$row[icell_phone]\"><span class=\"glyphicon glyphicon-earphone\"></span></a>" : '' ;
		$instructor = ($row['ilast_name'] and $row['pid'] != $row['iid']) ? ' <i><span data-toggle="tooltip" data-placement="right" title="' .
			db2web($row['ifirst_name']) . ' ' . db2web($row['ilast_name']) . '">' .
			substr($row['ifirst_name'], 0, 1) . "." . substr($row['ilast_name'], 0, 1) . '. </span></i>' . $itelephone : '' ; 
		$class = ($row['r_type'] == BOOKING_MAINTENANCE) ? ' class="danger"' : '' ;
		if (strpos($row['r_start'], $sql_date) === 0) 
			$row['r_start'] = substr($row['r_start'], 11) ;
		else
			$row['r_start'] = substr($row['r_start'], 0, 10) ;
		if (strpos($row['r_stop'], $sql_date) === 0) 
			$row['r_stop'] = substr($row['r_stop'], 11) ;
		else
			$row['r_stop'] = substr($row['r_stop'], 0, 10) ;
		print("<tr$class><td>$row[r_plane]</td><td>$row[r_start]</td><td>$row[r_stop]</td><td>$pname$ptelephone$instructor</td><td>". nl2br(db2web($row['r_comment'])) . "</td></tr>\n") ;
	}
?>
</table>
</div><!-- row -->

<!-- Display previous / next -->
<?php
if ($userId > 0) { // Only members can see all bookings
?>
<div class="row">
<ul class="pager col-xs-12">
<li class="previous"><a href="<?=$_SERVER['PHP_SELF'] . '?time=' . ($displayTimestamp - 24 * 3600)?>">Jour précédent</a></li>
<li class="next"><a href="<?=$_SERVER['PHP_SELF'] . '?time=' . ($displayTimestamp + 24 * 3600)?>">Jour suivant</a></li>
</ul>
</div> <!-- row -->
<script>
document.addEventListener('swiped-left', function(e) {location.href='<?=$_SERVER['PHP_SELF'] . '?time=' . ($displayTimestamp + 24 * 3600)?>' }) ;
document.addEventListener('swiped-right', function(e) {location.href='<?=$_SERVER['PHP_SELF'] . '?time=' . ($displayTimestamp - 24 * 3600)?>' }) ;
</script>
<?php
} // $userId > 0
?>
</div> <!-- container-->

<!-- for the tooltip -->
<script>
$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip();   
});
</script>

</body>
</html>
