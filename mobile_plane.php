<?php
/*
   Copyright 2013-2025 Eric Vyncke

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

// Param plane
$plane = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['plane'])) ;

$plane_result = mysqli_query($mysqli_link, "SELECT * FROM $table_planes WHERE id='$plane' AND ressource = 0")
	or die("Cannot retrieve plane ($id): " . mysqli_error($mysqli_link)) ;
$plane_row = mysqli_fetch_array($plane_result) ;
if (! $plane_row) die("No such plane: $plane") ;
if ($plane_row['compteur_vol']) $plane_row['compteur'] = $plane_row['compteur_vol_valeur'] ;

// Get all pictures of that plane
$plane_pics = array() ;
$result_pics = mysqli_query($mysqli_link, "SELECT * FROM $table_planes_pics WHERE pc_plane = '$plane' ORDER BY pc_id")
	or journalise($userId, "F", "Cannot retrieve $plane pictures: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_assoc($result_pics)) {
	$plane_pics[] = $row['pc_file'] ;
}

// Get the engine time from the last entry in the pilot log book
$index_column = ($plane_row['compteur_vol'] == 0) ? 'l_end_hour' : 'l_flight_end_hour' ;
$result2 = mysqli_query($mysqli_link, "select $index_column as compteur_pilote, l_end as compteur_pilote_date, concat(first_name, ' ', last_name) as compteur_pilote_nom 
	from $table_logbook  l join $table_bookings r on l_booking = r_id join $table_person p on jom_id = if(l_audit_who <= 0, if(l_instructor is null, l_pilot, l_instructor), l_audit_who)
	where l_plane = '$plane' and l_booking is not null and l_end_hour > 0
	order by compteur_pilote_date desc limit 0,1")
	or die("Cannot get pilote engine time:" . mysqli_error($mysqli_link)) ;
$row2 = mysqli_fetch_array($result2) ;

$additional_preload = '</resa/images/fa.ico>;rel=preload;as=image' ;

require_once 'mobile_header5.php' ;
?> 
<div class="container-fluid">

<h2><?=$plane?></h2>

<div class="row">

<!-- should be hidden on phones -->
<div class="col-sm-4">
	<!-- while trying caroussel
	<figure class="figure">
		<img class="figure-img img-fluid hidden-sm" src="<?=$plane_row['photo']?>">
	</figure-->
	<div id="carouselIndicators" class="carousel slide">
		<div class="carousel-indicators">
<?php
for ( $i = 0 ; $i < count($plane_pics) ; $i++) {
	if ($i == 0) 
		print('		<button type="button" data-bs-target="#carouselIndicators" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Photo 1"></button>' . "\n") ;
	else
		print("		<button type=\"button\" data-bs-target=\"#carouselIndicators\" data-bs-slide-to=\"$i\" aria-label=\"Photo " . ($i+1) . "\"></button>\n") ;
}
?>
		</div>
	<div class="carousel-inner">
<?php
$first = true ;
foreach ($plane_pics as $pic) {
	$class = ($first) ? ' active' : '' ;
	$first = false ;
	print("		<div class=\"carousel-item$class\">\n") ;
	print("			<img src=\"$pic\" class=\"d-block w-100\" alt=\"$plane\">\n") ;
	print("		</div><!-- carousel-item-->\n") ;
}
?>
	</div><!-- carousel-inner-->
	<button class="carousel-control-prev" type="button" data-bs-target="#carouselIndicators" data-bs-slide="prev">
		<span class="carousel-control-prev-icon" aria-hidden="true"></span>
		<span class="visually-hidden">Previous</span>
	</button>
	<button class="carousel-control-next" type="button" data-bs-target="#carouselIndicators" data-bs-slide="next">
		<span class="carousel-control-next-icon" aria-hidden="true"></span>
		<span class="visually-hidden">Next</span>
	</button>
	</div><!-- end caroussel-->
</div><!-- col-sm-4-->

<div class="col-md-8 col-sm-8">
<table class="table table-responsive table-striped">
	<tr><td>Consommation</td><td><?=$plane_row['consommation']?> litres/heure</td></tr>
	<tr><td>Prix de location</td><td><?=number_format($plane_row['cout'], 2, ',')?> &euro;/min (soit <?=number_format(60*$plane_row['cout'], 2, ',')?> &euro;/heure)</td></tr>
	<tr><td>Maintenance</td><td><?=$plane_row['entretien']?></td></tr>
<?php
function generateMaintenanceClass($entretien, $compteur) {
	$delta_maintenance = $entretien - $compteur ;
	if ($delta_maintenance <= 0)
		return ' class="danger"' ;
	else if ($delta_maintenance <= 5)
		return ' class="warning"' ;
	return '' ;
}
	$class = (isset($plane_row['entretien']) and isset($row2['compteur_pilote'])) ? generateMaintenanceClass($plane_row['entretien'], $row2['compteur_pilote']) : '';
	if (isset($row2['compteur_pilote_date']))
		print("<tr$class><td>Dernier compteur pilote<span class=\"hidden-xs\"> ($row2[compteur_pilote_date])</span></td><td>$row2[compteur_pilote]</td></tr>\n") ;
	if ($plane_row['poh'])
		print("<tr><td>POH </td><td><a href=\"$plane_row[poh]\"><i class=\"bi bi-file-earmark-pdf\"></i></a></td></tr>\n") ;
	if ($plane_row['checklist'])
		print("<tr><td>Checklist</td><td><a href=\"$plane_row[checklist]\"><i class=\"bi bi-file-earmark-pdf\"></i></a></td></tr>\n") ;
	print("<tr><td><a data-bs-toggle=\"collapse\" href=\"#collapseDoc\" title=\"Cliquez pour voir la liste et les manuels\" id=\"toggleEquiments\"><i class=\"bi bi-chevron-down\" id=\"toggleIcon\"></i></a>
		 Équipements</td><td>") ;
	$result_equipment = mysqli_query($mysqli_link, "SELECT *
		FROM $table_plane_device
			JOIN $table_device on d_name = pd_device
		WHERE pd_plane = '$plane' AND d_fpl_code IS NOT NULL
		ORDER BY d_fpl_code")
		or journalise($userId, "F", "Cannot read equipments list: " . mysqli_error($mysqli_link)) ;
	if (mysqli_num_rows($result_equipment) > 0) {
		while ($row_equipment = mysqli_fetch_array($result_equipment))
			print("<span title=\"$row_equipment[d_type]\">$row_equipment[d_fpl_code]</span> ") ;
		print(" (pour plan de vol OACI)\n") ;	
	} else
		print("Inconnue\n") ;
	mysqli_free_result($result_equipment) ;
	print("</td></tr>
	<tr class=\"collapse\" id=\"collapseDoc\">
		<td colspan=2>
			<table class=\"table table-responsive table-striped\">
				<thead><tr><th>Équipement</th><th>Type</th><th>Fabricant<th>Titre</th></tr></thead>
				<tbody>") ;
	$result_doc = mysqli_query($mysqli_link, "SELECT *
		FROM $table_plane_device
			JOIN $table_device ON d_name = pd_device
			JOIN $table_device_doc ON d_name = dd_model 
		WHERE pd_plane = '$plane'
		ORDER BY d_name, dd_title")
		or journalise($userId, "E", "Cannot fetch device doc: " . mysqli_error($mysqli_link)) ;
	while ($row_doc = mysqli_fetch_assoc($result_doc)) {
		print("<tr><td>$row_doc[d_name]</td><td>$row_doc[d_type]</td><td>$row_doc[d_manufacturer]</td>
			<td><a href=\"$row_doc[dd_url]\" target=\"_blank\">$row_doc[dd_title] <i class=\"bi bi-box-arrow-up-right\"></i></a></td>
			</tr>\n") ;
	}
	print("</tbody>
			</table>
		</td>
	</tr>\n");
	print("</tr>\n") ;
	print("<tr><td>Prise électrique</td><td>") ;
	foreach (explode(',', $plane_row['power']) as $prise) {
		switch (trim(strtolower($prise))) {
			case 'usb-a':
			case 'usba': print("<i class=\"bi bi-usb\"></i> USB-A ") ; break ;
			case 'usb-c':
			case 'usbc': print("<i class=\"bi bi-usb-c\"></i> USB-C ") ; break ;
			case '12v': print("<i class=\"bi bi-plug-fill\"></i> 12V ") ; break ;
			case '24v': print("<i class=\"bi bi-plug-fill\"></i> 24V ") ; break ;
			default: print("$prise") ;
		}
	}
	print("</td></tr>") ;
	print("<tr><td>Dernier vol sur FlightAware  <i class=\"bi bi-box-arrow-up-right\"></i></td><td><a href=\"https://flightaware.com/live/flight/" . strtoupper($plane_row['id']) . "\" target=\"_blank\"><img src=\"images/fa.ico\" border=\"0\" width=\"24\" height=\"24\"></a></td></tr>
	<tr><td>Carnet de routes</td><td><a href=\"mobile_planelog.php?plane=" . strtoupper($plane_row['id']) . "\"><i class=\"bi bi-journal\"></i></a></td></tr>
	<tr><td>Masse et centrage</i></td><td><a href=\"mobile_wnb.php?plane=" . strtoupper($plane_row['id']) . "\"><i class=\"bi bi-rulers\"></i></a></td></tr>
	<tr><td>Performances</i></td><td><a href=\"mobile_performance.php?plane=" . strtoupper($plane_row['id']) . "\"><i class=\"bi bi-calculator\"></i></a></td></tr>\n") ;
	print("<tr><td>Aircraft Technical Log</td><td><a href=\"mobile_incidents.php?plane=$plane\"><i class=\"bi bi-tools\"></i></a></td></tr>\n") ;
?>
</table>
</div><!-- col-->

</div><!-- row -->

<div class="row">
<h3 class="col-sm-12 hidden-xs">Réservation(s) à venir</h3>
</div><!-- row -->

<div class="row">
<table class="col-sm-12 table table-responsive table-striped">
	<thead>
	<tr><th>De</th><th>À</th><th>Pilote</th><th>Commentaire</th></tr>
</thead>
<tbody class="table-group-divider">
<?php
	$sql_date = date('Y-m-d') ;
	$result = mysqli_query($mysqli_link, "SELECT *, i.last_name as ilast_name, i.first_name as ifirst_name, i.cell_phone as icell_phone, i.jom_id as iid,
		pi.last_name as plast_name, pi.first_name as pfirst_name, pi.cell_phone as pcell_phone, pi.jom_id as pid
		FROM $table_bookings 
		JOIN $table_person pi ON pi.jom_id = r_pilot
		LEFT JOIN $table_person i ON i.jom_id = r_instructor		
		JOIN $table_planes p ON r_plane = p.id
		LEFT JOIN $table_flights fl ON r_id = f_booking		
		WHERE r_cancel_date IS NULL and r_plane = '$plane' AND DATE(r_stop) >= CURDATE()
		ORDER BY r_start ASC LIMIT 0,10")
		or die("Cannot retrieve bookings($plane): " . mysqli_error($mysqli_link)) ;
	while ($row = mysqli_fetch_array($result)) {
		$ptelephone = ($row['pcell_phone'] and ($userId > 0)) ? "&nbsp;<a href=\"tel:" . canonicalizePhone($row['pcell_phone']) . "\"><i class=\"bi bi-telephone-fill\"></i></a>" .
			"&nbsp;<a href=\"https://wa.me/" . canonicalizePhone($row['pcell_phone']) . "\"><i class=\"bi bi-whatsapp\" title=\"Envoyer un message WhatsApp\"></i></a>" : '' ;
		$itelephone = ($row['icell_phone'] and ($userId > 0)) ? "&nbsp;<a href=\"tel:" . canonicalizePhone($row['icell_phone']) . "\"><i class=\"bi bi-telephone-fill\"></i></a>" : '' ;
		$instructor = ($row['ilast_name'] and $row['pid'] != $row['iid']) ? ' <i><span data-toggle="tooltip" data-placement="right" title="' .
			db2web($row['ifirst_name']) . ' ' . db2web($row['ilast_name']) . '">' .
			substr($row['ifirst_name'], 0, 1) . "." . substr($row['ilast_name'], 0, 1) . '. </span></i>' . $itelephone : '' ; 
		$class = ($row['r_type'] == BOOKING_MAINTENANCE) ? ' class="text-danger"' : '' ;
		if ($row['f_type'] != '')
			$class = ' class="text-warning"' ;
		if (strpos($row['r_start'], $sql_date) === 0) 
			$row['r_start'] = substr($row['r_start'], 11) ;
		if (strpos($row['r_stop'], $sql_date) === 0) 
			$row['r_stop'] = substr($row['r_stop'], 11) ;
		print("<tr><td$class>$row[r_start]</td><td$class>$row[r_stop]</td><td$class><span class=\"hidden-xs\">" . db2web($row['pfirst_name']) . " </span><b>" . 
			db2web($row['plast_name']) . "</b>$ptelephone$instructor</td><td$class>". nl2br(db2web($row['r_comment'])) . "</td></tr>\n") ;
	}
?>
</tbody>
</table>
</div><!-- row -->
</div> <!-- container-->
<script>
    document.getElementById('toggleEquiments').addEventListener('click', function () {
        const icon = document.getElementById('toggleIcon');
        if (icon.classList.contains('bi-chevron-down')) {
            icon.classList.remove('bi-chevron-down');
            icon.classList.add('bi-chevron-up');
        } else {
            icon.classList.remove('bi-chevron-up');
            icon.classList.add('bi-chevron-down');
        }
    });
</script>
</body>
</html>