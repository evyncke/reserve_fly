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

if (isset($_REQUEST['add']) and isset($_REQUEST['newPilot'])){
	$pilot = intval($_REQUEST['newPilot']) ;
	if (! is_numeric($pilot) or ($pilot <= 0)) die("ID Pilote incorrecte") ;
	mysqli_query($mysqli_link, "REPLACE INTO $table_flights_pilots(p_id, p_who, p_date, p_weight, p_discovery, p_initiation)
		VALUES($pilot, $userId, SYSDATE(), 80, 0, 0)")
		or die("Cannot add pilot: " . mysqli_error($mysqli_link)) ;
	journalise($userId, "I", "New pilot added to the flight roster $pilot") ;
}

if (isset($_REQUEST['delete']) and isset($_REQUEST['pilot'])){
	$pilot = intval($_REQUEST['pilot']) ;
	if (! is_numeric($pilot) or ($pilot <= 0)) die("ID Pilote incorrecte") ;
	mysqli_query($mysqli_link, "DELETE FROM $table_flights_pilots WHERE p_id = $pilot")
		or die("Cannot delete pilot: " . mysqli_error($mysqli_link)) ;
	journalise($userId, "W", "Pilot deleted from the flight roster $pilot") ;
}

if (isset($_REQUEST['edit_pilot']) and isset($_REQUEST['pilot'])){
	$pilot = intval($_REQUEST['pilot']) ;
	if (! is_numeric($pilot) or ($pilot <= 0)) die("ID Pilote incorrecte") ;
	$weight = intval($_REQUEST['weight']) ;
	if (! is_numeric($weight) or ($weight <= 40)) die("Poids Pilote incorrecte") ;
	$discovery = (isset($_REQUEST['discovery']) and $_REQUEST['discovery'] == 'on') ? 1 : 0;
	$initiation = (isset($_REQUEST['initiation']) and $_REQUEST['initiation'] == 'on') ? 1 : 0 ;
	mysqli_query($mysqli_link, "UPDATE $table_flights_pilots SET p_weight = $weight, p_discovery = $discovery, p_initiation = $initiation WHERE p_id = $pilot")
		or die("Cannot update pilot: " . mysqli_error($mysqli_link)) ;
	journalise($userId, "I", "New pilot rating for $pilot: discovery=$_REQUEST[discovery], initiation=$_REQUEST[initiation]") ;
}
?>
<script src="data/pilots.js"></script>
<div class="page-header hidden-xs">
<h3>Qualifications des pilotes</h3>
</div><!-- page header -->

<div class="row">
<div class="col-xs-offset-11 col-xs-1">
<a href="flight_pilot_pdf.php"><span class="glyphicon glyphicon-print"></span></a>
</div><!-- class=col -->
</div><!-- class=row -->

<div class="row text-info">
Voici la liste des pilotes qualifiés pour les divers types de vol:
<ul>
<li><b>découvertes</b>: au moins 100 heures en tant que PIC;</li>
<li><b>initiation</b>: licence Flight Instructeur PPL SEP(A).</li>
</ul>
En plus, le pilote doit avoir son certificat médical ainsi qu'avoir effectué 3 décolages/atterrissage lors des derniers 90 jours.
</div><!-- row -->

<table class="table table-striped table-responsive col-md-6 col-xs-12">
<thead>
<tr><th>Pilote</th><th>Poids (kg)</th><th>Découverte</th><th>Initiation</th><th>Action</th></tr>
</thead>
<tbody>
<?php
$result = mysqli_query($mysqli_link, "SELECT * FROM $table_flights_pilots JOIN $table_person ON p_id = jom_id
	 ORDER BY last_name ASC, first_name ASC") 
	or die("Impossible de lister les pilotes: " . mysqli_error($mysqli_link));
while ($row = mysqli_fetch_array($result)) {
	$email = ($row['email']) ? " <a href=\"mailto:$row[email]\"><span class=\"glyphicon glyphicon-envelope\"></span></a>" : "" ; 
	$telephone = ($row['cell_phone']) ? " <a href=\"tel:$row[cell_phone]\"><span class=\"glyphicon glyphicon-earphone\"></span></a>" : "" ; 
	$edit =  " <span class=\"glyphicon glyphicon-floppy-disk text-success\" onclick=\"submitForm('form_$row[p_id]');\"></span> " ;
	$delete = " <a href=\"$_SERVER[PHP_SELF]?pilot=$row[p_id]&delete=true\"><span class=\"glyphicon glyphicon-trash text-danger\"></span></a>" ;
	$poids = "<input type=\"number\" name=\"weight\" value=\"$row[p_weight]\" min=\"40\" max=\"120\">" ;
	$discovery = '<div class="checkbox"><label><input name="discovery" type="checkbox"' . (($row['p_discovery']) ? ' checked' : '') . '/>Découverte</label></div>' ;
	$initiation = '<div class="checkbox"><label><input name="initiation" type="checkbox"' . (($row['p_initiation']) ? ' checked' : '') . '/>Initiation</label></div>' ;
	print("<form id=\"form_$row[p_id]\" action=\"$_SERVER[PHP_SELF]\">
		<input type=\"hidden\" name=\"pilot\" value=\"$row[p_id]\">
		<input type=\"hidden\" name=\"edit_pilot\" value=\"edit_pilot\">
		<tr><td>" . db2web($row['last_name']) . ' ' . db2web($row['first_name']) . "$email$telephone</td><td>$poids<td>$discovery</td><td>$initiation</td><td>$edit$delete</td></tr></form>\n") ;
}
?>
</tbody>
</table>

<div class="row">
<form action="<?=$_SERVER['PHP_SELF']?>" method="GET">

	<div class="form-group">
		<label class="control-label col-xs-6 col-md-1" for="pilotSelect">Pilote:</label>
		<div class="col-xs-6 col-md-2">
			<select class="form-control" id="pilotSelect" name="newPilot"></select>
		</div>
	</div>
	<div class="form-group">
		<div class="col-xs-3 col-md-2">
			<input type="submit" class="btn btn-primary" name="add" value="Ajouter aux pilotes"/>
   		</div><!-- col -->
	</div><!-- formgroup-->
</form>
</div><!-- row -->

<script>
function submitForm(id) {
	document.getElementById(id).submit() ;
}

function prefillDropdownMenus(selectName, valuesArray) {
	var select = document.getElementsByName(selectName)[0] ;

	for (var i = 0; i < valuesArray.length; i++) {
		var option = document.createElement("option");
		option.text = valuesArray[i].name ;
		option.value = valuesArray[i].id ;
		select.add(option) ;
	}
}

prefillDropdownMenus('newPilot', pilots) ;

</script>
<?php
require_once 'flight_trailer.php' ;
?>