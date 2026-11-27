<?php
/*
   Copyright 2014-2026 Eric Vyncke

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

if (!($userIsFlightManager or $userIsInstructor or $userIsAdmin or $userIsBoardMember)) {
	journalise($userId, "F", "Vous n'êtes pas autorisé(e) sur cette page: il faut avoir les bons droits d'accès.") ;
}
	
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
	if ($pilot <= 0) die("ID Pilote incorrecte") ;
	$weight = intval($_REQUEST['weight'] ?? 0) ;
	if ($weight < 40 or $weight > 120) die("Poids Pilote incorrecte (entre 40 et 120 kg)") ;
	$discovery = (isset($_REQUEST['discovery']) and $_REQUEST['discovery'] == 'on') ? 1 : 0;
	$initiation = (isset($_REQUEST['initiation']) and $_REQUEST['initiation'] == 'on') ? 1 : 0 ;
	mysqli_query($mysqli_link, "UPDATE $table_flights_pilots SET p_weight = $weight, p_discovery = $discovery, p_initiation = $initiation WHERE p_id = $pilot")
		or die("Cannot update pilot: " . mysqli_error($mysqli_link)) ;
	journalise($userId, "I", "New pilot rating for $pilot: discovery=$discovery, initiation=$initiation, weight=$weight") ;
}
?>
<script src="data/pilots.js"></script>
<div class="pb-2 mb-3 border-bottom d-none d-sm-block">
<h3>Qualifications des pilotes</h3>
</div><!-- page header -->

<div class="row">
<div class="offset-11 col-1">
<a href="flight_pilot_pdf.php"><span class="bi bi-printer"></span></a>
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

<div class="table-responsive col-md-6 col-12"><table class="table table-striped">
<thead>
<tr><th>Pilote</th><th>Poids (kg)</th><th>Découverte</th><th>Initiation</th><th>Action</th></tr>
</thead>
<tbody>
<?php
$result = mysqli_query($mysqli_link, "SELECT * FROM $table_flights_pilots JOIN $table_person ON p_id = jom_id
	 ORDER BY last_name ASC, first_name ASC") 
	or die("Impossible de lister les pilotes: " . mysqli_error($mysqli_link));
while ($row = mysqli_fetch_array($result)) {
	$email = ($row['email']) ? " <a href=\"mailto:$row[email]\"><span class=\"bi bi-envelope\"></span></a>" : "" ; 
	$telephone = ($row['cell_phone']) ? " <a href=\"tel:$row[cell_phone]\"><span class=\"bi bi-telephone\"></span></a>" : "" ; 
	$edit =  " <span class=\"bi bi-floppy text-success\" onclick=\"submitForm('form_$row[p_id]');\"></span> " ;
	$delete = " <a href=\"$_SERVER[PHP_SELF]?pilot=$row[p_id]&delete=true\"><span class=\"bi bi-trash text-danger\"></span></a>" ;
	$poids = "<input type=\"number\" name=\"weight\" value=\"$row[p_weight]\" min=\"40\" max=\"120\">" ;
	$discovery = '<div class="form-check"><input class="form-check-input" name="discovery" id="discovery_' . (int) $row['p_id'] . '" type="checkbox"' . (($row['p_discovery']) ? ' checked' : '') . '><label class="form-check-label" for="discovery_' . (int) $row['p_id'] . '">Découverte</label></div>' ;
	$initiation = '<div class="form-check"><input class="form-check-input" name="initiation" id="initiation_' . (int) $row['p_id'] . '" type="checkbox"' . (($row['p_initiation']) ? ' checked' : '') . '><label class="form-check-label" for="initiation_' . (int) $row['p_id'] . '">Initiation</label></div>' ;
	print("<form id=\"form_$row[p_id]\" action=\"$_SERVER[PHP_SELF]\">
		<input type=\"hidden\" name=\"pilot\" value=\"$row[p_id]\">
		<input type=\"hidden\" name=\"edit_pilot\" value=\"edit_pilot\">
		<tr><td>" . db2web($row['last_name']) . ' ' . db2web($row['first_name']) . "$email$telephone</td><td>$poids<td>$discovery</td><td>$initiation</td><td>$edit$delete</td></tr></form>\n") ;
}
?>
</tbody>
</table></div>

<form class="row g-2 align-items-center mb-3" action="<?=$_SERVER['PHP_SELF']?>" method="GET">
	<div class="col-auto">
		<label class="col-form-label" for="pilotSelect">Pilote:</label>
	</div>
	<div class="col-6 col-md-2">
		<select class="form-select" id="pilotSelect" name="newPilot"></select>
	</div>
	<div class="col-auto">
		<input type="submit" class="btn btn-primary" name="add" value="Ajouter aux pilotes"/>
	</div><!-- col -->
</form>

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