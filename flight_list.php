<?php
/*
   Copyright 2014-2023 Eric Vyncke

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

if (isset($_REQUEST['pattern'])) {
	$pattern = mysqli_real_escape_string($mysqli_link, web2db(trim($_REQUEST['pattern']))) ;
	$other_filter = " AND (p_lname LIKE '%$pattern%' OR p_fname LIKE '%$pattern%' or f_description LIKE '%$pattern%' or f_reference LIKE '%$pattern%' or f_notes LIKE '%$pattern%' or p_email LIKE '%$pattern%' OR first_name LIKE '%$pattern%' OR last_name LIKE '%$pattern%') " ;
} else
	$other_filter = '' ;
if (isset($_REQUEST['deleted'])) {
	$deleted = ' checked' ;
	$deleted_filter = '' ;
} else {
	$deleted = '' ;
	$deleted_filter = " AND f_date_cancelled IS NULL" ;
}
// Should check exclusive choices
if (isset($_REQUEST['if_only'])) {
	$if_only = ' checked' ;
	$if_only_filter = " AND f_type = 'D'" ;
} else {
	$if_only = '' ;
	$if_only_filter = '' ;
}
if (isset($_REQUEST['init_only'])) {
	$init_only = ' checked' ;
	$init_only_filter = " AND f_type = 'I'" ;
} else {
	$init_only = '' ;
	$init_only_filter = '' ;
}

if (isset($_REQUEST['completed']) and $_REQUEST['completed'] == "true") {
	$completed = "true" ;
	$completed_filter = ' AND f_date_flown IS NOT NULL' ;
	$title = 'terminés' ;
} else {
	$completed = "false" ;
	$completed_filter = ' AND f_date_flown IS NULL' ;
	$title = 'non terminés' ;
}
?>

<div class="page-header hidden-xs">
<h3>Tous les vols <?=$title?></h3>
</div><!-- page header -->

<div class="row">
<form class="" action="<?=$_SERVER['PHP_SELF']?>">
	<input type="hidden" name="completed" value="<?=$completed?>"/>
<!--div class="form-group"-->
	<div class="checkbox col-md-offset-1 col-xs-6 col-md-2">
			<label><input type="checkbox" name="deleted"<?=$deleted?> onchange="this.form.submit();">Inclure les vols annulés</label>
		</div><!-- checkbox-->
		<div class="checkbox col-xs-4 col-md-2">
			<label><input type="checkbox" name="init_only"<?=$init_only?> onchange="this.form.submit();">Initiations seulement</label>
		</div><!-- checkbox-->
		<div class="checkbox col-xs-4 col-md-2">
				<label><input type="checkbox" name="if_only"<?=$if_only?> onchange="this.form.submit();">Découvertes seulement</label>
		</div><!-- checkbox-->
		<div class="form-group">
			<div class="col-xs-6 col-md-offset-1 col-md-4">
				<input type="text" class="form-control" name="pattern" value="<?=db2web($pattern)?>"/>
				</div>
			</div> <!-- formgroup-->
			<div class="form-group">
				<div class="col-xs-3 col-md-1">
					<input type="submit" class="btn btn-primary" name="add" value="Chercher"/>
				</div><!-- col -->
		</div><!-- formgroup-->
	</form>
</div><!-- row -->

<table class="table table-striped table-responsive table-hover" id="allFlights">
<thead>
<tr><th>Réf</th><th>Actions</th><th>Créé le</th><th>Etat</th><th>Depuis</th><th>Vol</th><th id="pilots">Pilote</th><th>Type</th><th>Client</th><th>Remarque client</th><th>Notes club</th></tr>
</thead>
<tbody>
<?php
$result = mysqli_query($mysqli_link, "SELECT *, SYSDATE() AS today, SUM(fl_amount) AS payment 
	FROM $table_flight JOIN $table_pax_role ON f_id = pr_flight JOIN $table_pax ON pr_pax = p_id LEFT JOIN $table_person ON f_pilot = jom_id
	LEFT JOIN $table_bookings AS b ON f_booking = b.r_id
	LEFT JOIN $table_flights_ledger AS fl on fl_flight = f_id
	WHERE pr_role = 'C' $other_filter $deleted_filter $completed_filter $if_only_filter $init_only_filter
	GROUP BY f_id
	ORDER BY f_id DESC") 
	or journalise($userId, "F", "Impossible de lister les vols: " . mysqli_error($mysqli_link));
while ($row = mysqli_fetch_array($result)) {
	$reference = db2web($row['f_reference']) ;
	$email = ($row['p_email']) ? " <a href=\"mailto:$row[p_email]\"><span class=\"glyphicon glyphicon-envelope\" title=\"Envoyer un email\"></span></a>" : "" ; 
	$telephone = ($row['p_tel']) ? " <a href=\"tel:$row[p_tel]\"><span class=\"glyphicon glyphicon-earphone\" title=\"Téléphoner\"></span></a>" : "" ; 
	$edit =  " <a href=\"flight_create.php?flight_id=$row[f_id]\"><span class=\"glyphicon glyphicon-pencil\" title=\"Modifier/Annuler\"></span></a> " ;
	$print =  " <a href=\"flight_pdf.php?flight_id=$row[f_id]\" target=\"_blank\"><span class=\"glyphicon glyphicon-print\" title=\"Imprimer sous format PDF\"></span></a> " ;
	$pay =  ($row['payment'] > 0) ? "<span class=\"glyphicon glyphicon-euro\" style=\"color: green;\" title=\"Vol déjà payé\"></span>" :
		" <a href=\"flight_create.php?flight_id=$row[f_id]&pay_open=true\"><span class=\"glyphicon glyphicon-euro\" style=\"color: red;\" title=\"Indiquer le paiement\"></span></a> " ;
	$is_gift = ($row['f_gift'] != 0) ? '&nbsp;<span class="glyphicon glyphicon-gift" style="color: red;" title="Bon cadeau"></span>' : '' ;
	$type = ($row['f_type'] == 'D') ? 'découverte' : 'initiation' ;
	$description = nl2br(db2web($row['f_description'])) ;
	$notes = nl2br(db2web($row['f_notes'])) ;
	$row_style = '' ;
	if ($row['f_date_cancelled']) {
		$status = "Annulé</td><td>$row[f_date_cancelled]" ;
		$row_style = ' style="color: lightgray;"' ;
	} else if ($row['f_date_flown'])
		$status = "Accompli</td><td>$row[f_date_flown]" ;
	else if ($row['f_date_linked'])
		$status = "Avion réservé</td><td>$row[f_date_linked]" ;
	else if ($row['f_date_assigned'])
		$status = "Pilote sélectionné</td><td>$row[f_date_assigned]" ;
	else if ($row['fl_date'])
		$status = "Paiement effectué</td><td>$row[fl_date]" ;
	else
		$status = "Attente paiement</td><td>" ;
	if ($row['f_date_flown'])
		$date_vol = "ATD $row[f_date_flown] ($row[r_plane])" ;
	else if ($row['r_start'])
		$date_vol = "ETD $row[r_start] ($row[r_plane])"  ;
	else
		$date_vol = "à déterminer" ;
	print("<tr$row_style><td>$reference</td><td>$edit$print$pay</td><td>$row[f_date_created]</td><td>$status</td><td>$date_vol</td>
		<td>" . db2web($row['first_name']) . " <b>" . db2web($row['last_name']) . "</b></td>
		<td>$type$is_gift</td>
		<td>" . db2web($row['p_fname']) . " <b>" . db2web($row['p_lname']) . "$email$telephone</b></td>
		<td>$description</td>
		<td>$notes</td></tr>\n") ;
}
?>
</tbody>
</table>

<script>
	// Let sort the row by clicking on the header https://stackoverflow.com/questions/3160277/jquery-table-sort/19947532#19947532
	$('th').click(function(){
    var table = $(this).parents('table').eq(0)
    var rows = table.find('tr:gt(0)').toArray().sort(comparer($(this).index()))
    this.asc = !this.asc
    if (!this.asc){rows = rows.reverse()}
    for (var i = 0; i < rows.length; i++){table.append(rows[i])}
})
function comparer(index) {
    return function(a, b) {
        var valA = getCellValue(a, index), valB = getCellValue(b, index)
		return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.toString().localeCompare(valB)
    }
}
function getCellValue(row, index){ return $(row).children('td').eq(index).text() }
</script>
<?php
require_once 'flight_trailer.php' ;
?>
