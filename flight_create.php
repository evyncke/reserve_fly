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
$modify = (isset($_REQUEST['modify']) and $_REQUEST['modify'] != '') ? TRUE : FALSE ;
$create = (isset($_REQUEST['create']) and $_REQUEST['create'] != '') ? TRUE : FALSE ;
$assign_pilot = (isset($_REQUEST['assign_pilot']) and $_REQUEST['assign_pilot'] != '') ? TRUE : FALSE ;
$add_pax = (isset($_REQUEST['add_pax']) and $_REQUEST['add_pax'] != '') ? TRUE : FALSE ;
$delete_pax = (isset($_REQUEST['delete_pax']) and $_REQUEST['delete_pax'] != '') ? TRUE : FALSE ;
$modify_pax = (isset($_REQUEST['modify_pax']) and $_REQUEST['modify_pax'] != '') ? TRUE : FALSE ;
$flight_id = (isset($_REQUEST['flight_id'])) ? trim($_REQUEST['flight_id']) : 0 ;
if (!is_numeric($flight_id)) die("Invalid ID: $flight_id") ;
$title = ($flight_id) ? "Modification d'une réservation de vol" : "Création d'une réservation de vol" ;
// TODO be ready to pre-load when asking for modification/cancellation
// and of course add 'modify' 'cancel' button


if ($create or $modify) {
	if ($_REQUEST['discovery_flight'] == 'on')
		$flight_type = 'D' ;
	elseif ($_REQUEST['initiation_flight'] == 'on')
		$flight_type = 'I' ;
	else 
		die("Vous devez choisir le type de vol (initiation ou découverte)") ;
	$pax_cnt = $_REQUEST['pax_cnt'] ;
	if (!is_numeric($pax_cnt)) die("Invalid pax_cnt: $pax_cnt") ;
	if ($_REQUEST['pax'] == 'yes')
		$role = 'P' ;
	elseif ($_REQUEST['student'] == 'yes')
		$role = 'S' ;
	else
		$role = 'C' ;
	if (strtoupper($_REQUEST['gender']) == 'M')
		$gender = 'M' ;
	elseif (strtoupper($_REQUEST['gender']) == 'F')
		$gender = 'F' ;
	elseif (strtoupper($_REQUEST['gender']) == 'L')
		$gender = 'L' ;
	else
		die("Gender $_REQUEST[gender] is not correct") ;
	$lname = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['lname'])) ;
	if ($lname == '') die("Last name cannot be empty") ;
	$fname = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['fname'])) ;
	$email = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['email'])) ;
	if ($email == '') die("Email address cannot be empty") ;
	$phone = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['phone'])) ;
	if ($phone == '') die("Phone number cannot be empty") ;
	$weight = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['weight'])) ;
	if (!is_numeric($weight)) die("Invalid weight: $weight") ;
	$birthdate = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['birthdate'])) ;
	$comment = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['comment'])) ;
}

if ($create) {
	mysqli_query($mysqli_link, "INSERT INTO $table_pax (p_lname, p_fname, p_email, p_tel, p_birthdate, p_weight, p_gender)
		VALUES('" . web2db($lname) . "', '" . web2db($fname) . "', '$email', '$phone', '$birthdate', $weight, '$gender')")
		or die("Cannot add contact, system error: " . mysqli_error($mysqli_link)) ;
	$pax_id = mysqli_insert_id($mysqli_link) ; 
	mysqli_query($mysqli_link, "INSERT INTO $table_flight (f_date_created, f_who_created, f_type, f_pax_cnt, f_description, f_pilot) 
		VALUES(SYSDATE(), $userId, '$flight_type', $pax_cnt, '" . web2db($comment) . "', NULL)")
		or die("Cannot add flight, system error: " . mysqli_error($mysqli_link)) ;
	$flight_id = mysqli_insert_id($mysqli_link) ; 
	mysqli_query($mysqli_link, "INSERT INTO $table_pax_role(pr_flight, pr_pax, pr_role)
		VALUES('$flight_id', '$pax_id', 'C')") 
		or die("Cannot add contact role C, system error: " . mysqli_error($mysqli_link)) ;
	if ($role != 'C')
		mysqli_query($mysqli_link, "INSERT INTO $table_pax_role(pr_flight, pr_pax, pr_role)
			VALUES('$flight_id', '$pax_id', '$role')") 
			or die("Cannot add contact role $role, system error: " . mysqli_error($mysqli_link)) ;
	journalise($userId, 'I', "$flight_type flight ($flight_id) created for $lname $fname ($comment)") ;
}

if ($modify) {
	if ($flight_id <= 0) die("Invalid flight_id ($flight_id)") ;
	$result = mysqli_query($mysqli_link, "SELECT * from $table_pax_role WHERE pr_flight = $flight_id and pr_role='C'")
		or die("Cannot retrieve contact for $flight_id: " . mysqli_error($mysqli_link)) ;
	$row_pax = mysqli_fetch_array($result) or die("Contact not found") ;
	mysqli_free_result($result) ;
	$pax_id = $row_pax['pr_pax'] ;
	mysqli_query($mysqli_link, "UPDATE $table_pax
			SET p_lname='" . web2db($lname) . "', p_fname='" . web2db($fname) . "', p_email='$email', p_tel='$phone', p_birthdate='$birthdate', p_weight=$weight, p_gender='$gender'
			WHERE p_id = $pax_id")
		or die("Cannot modify contact, system error: " . mysqli_error($mysqli_link)) ;
	mysqli_query($mysqli_link, "UPDATE $table_flight 
		SET f_type='$flight_type', f_pax_cnt=$pax_cnt, f_description='" . web2db($comment) . "'
		WHERE f_id = $flight_id")
		or die("Cannot modify flight, system error: " . mysqli_error($mysqli_link)) ;
	journalise($userId, "W", "Flight $flight_id modified") ;
}

if ($assign_pilot) {
	if ($flight_id <= 0) die("Invalid flight_id ($flight_id)") ;
	$assigned_pilot = (isset($_REQUEST['assignedPilot'])) ? intval($_REQUEST['assignedPilot']) : '' ;
	if (! $assigned_pilot or ! is_numeric($assigned_pilot)) die("Invalid pilot ($assigned_pilot)") ;
	mysqli_query($mysqli_link, "UPDATE $table_flights SET f_pilot=$assigned_pilot, f_date_assigned = SYSDATE() WHERE f_id = $flight_id")
		or die("Cannot assign pilot: " . mysqli_error($mysqli_link)) ;
	if (mysqli_affected_rows($mysqli_link) == 0)
		die("No change made") ;
	journalise($userId, "W", "Flight $flight_id has now a pilot $assigned_pilot") ;
}

if ($add_pax) {
	if ($flight_id <= 0) die("Invalid flight_id ($flight_id)") ;
	$lname = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['lname'])) ;
	if ($lname == '') die("Last name cannot be empty") ;
	$fname = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['fname'])) ;
	if ($fname == '') die("First name cannot be empty") ;
	$weight = intval(trim($_REQUEST['weight'])) ;
	// TODO also allow non P role (could be S)
	if ($weight == '' or $weight <= 10) die("Weight is invalid") ;
	// TODO check whether we are already max-ed out about passagers
	mysqli_query($mysqli_link, "INSERT INTO $table_pax(p_lname, p_fname, p_weight)
		VALUES ('" . web2db($lname) . "', '" . web2db($fname) . "', $weight)")
		or die("Cannot add passenger: " . mysqli_error($mysqli_error)) ;
	$pax_id = mysqli_insert_id($mysqli_link) ; 
	mysqli_query($mysqli_link, "INSERT INTO $table_pax_role(pr_flight, pr_pax, pr_role)
		VALUES('$flight_id', '$pax_id', 'P')") 
		or die("Cannot add passenger role , system error: " . mysqli_error($mysqli_link)) ;
	journalise($userId, "I", "Passenger $fname $lname added to flight $flight_id") ;
}

if ($modify_pax) {
	if ($flight_id <= 0) die("Invalid flight_id ($flight_id)") ;
	$lname = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['lname'])) ;
	if ($lname == '') die("Last name cannot be empty") ;
	$fname = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['fname'])) ;
	if ($fname == '') die("First name cannot be empty") ;
	$weight = intval(trim($_REQUEST['weight'])) ;
	if ($weight == '' or $weight <= 10) die("Weight is invalid") ;
	$pax_id = intval(trim($_REQUEST['pax_id'])) ;
	if ($pax_id == '' or $pax_id <= 0) die("Pax_id is invalid") ;
	// TODO check whether we are already max-ed out about passagers
	// TODO also allow non P role (could be S)
	mysqli_query($mysqli_link, "UPDATE $table_pax SET
		p_lname = '" . web2db($lname) . "', p_fname = '" . web2db($fname) . "', p_weight = $weight
		WHERE p_id = $pax_id")
		or die("Cannot modify passenger: " . mysqli_error($mysqli_link)) ;
}

if ($delete_pax) {
	if ($flight_id <= 0) die("Invalid flight_id ($flight_id)") ;
	$pax_id = intval(trim($_REQUEST['pax_id'])) ;
	if ($pax_id == '' or $pax_id <= 0) die("Pax_id is invalid") ;
	mysqli_query($mysqli_link, "DELETE FROM $table_pax_role WHERE pr_flight=$flight_id AND pr_role in ('P', 'S') AND pr_pax = $pax_id")
		or die("Cannot remove role for passenger $pax_id: " . mysqli_error($mysqli_link)) ;
	// Let's find it back and check how many roles he has, if only P or S then we can safely remove it
	// We should never remove the role Contact
	$result_delete = mysqli_query($mysqli_link, "SELECT * FROM $table_pax_role 
		WHERE pr_flight=$flight_id AND pr_pax = $pax_id")
		or die("Cannot check remaining role for passenger $pax_id: " . mysqli_error($mysqli_link)) ;
	if (mysqli_num_rows($result_delete) == 0) // We can safely remove passenger details
		mysqli_query($mysqli_link, "DELETE FROM $table_pax WHERE p_id = $pax_id")
			or die("Cannot delete passenger detail: " . mysqli_error($mysqli_link)) ;
	journalise($userId, "I", "Passenger $pax_id deleted from flight $flight_id") ;
}

if (isset($flight_id) and $flight_id != 0) {
	$result = mysqli_query($mysqli_link, "SELECT * 
			FROM $table_flight JOIN $table_pax_role ON pr_flight = f_id LEFT JOIN $table_pax ON pr_pax = p_id LEFT JOIN $table_person ON f_who_created = jom_id
			WHERE f_id = $flight_id and pr_role='C'")
		or die("Cannot retrieve flight $flight_id: " . mysqli_error($mysqli_link)) ;
	$row_flight = mysqli_fetch_array($result) ;
	if (!$row_flight) die("Vol #$flight_id inconnu!") ;
//	print_r($row_flight) ;
	mysqli_free_result($result) ;
}
?>

<div class="page-header">
<h3><?=$title?></h3>
</div><!-- page header -->

<ul class="nav nav-tabs">
  <li class="active"><a data-toggle="tab" href="#menuContact">Contact</a></li>
  <li><a data-toggle="tab" href="#menuPassenger">Passagers</a></li>
  <li><a data-toggle="tab" href="#menuPilot">Pilote</a></li>
  <li><a data-toggle="tab" href="#menuAudit">Historique</a></li>
</ul>

<div class="tab-content">

<div id="menuContact" class="tab-pane fade in active">

<form action="flight_create.php" method="get" autocomplete="off">

<div class="row">
	<div class="form-group col-xs-6 col-sm-2">
		<label class="radio control-label">Type de vol</label>
		<div class="radio">
			<label><input type="radio" name="discovery_flight">découverte</label>
		</div>
		<div class="radio">
			<label><input type="radio" name="initiation_flight">initiation</label>
		</div>
	</div> <!-- form-group -->
	<div class="form-group col-xs-6 col-sm-2">
		<label for="lname">Nombre de passagers (au total en dehors du pilote/FI):</label>
		<input type="number" min="1" max="3" class="form-control" name="pax_cnt" value="1">
	</div> <!-- form-group -->

</div><!-- row -->

<div class="row">
<h4>Contact principal</h4>
</div><!-- row -->

<div class="row">
	<div class="form-group col-xs-12 col-sm-1">
		<label for="lname">Salutations:</label>
		<select class="form-control" name="gender">
			<option value="F">Mme</option>
			<option value="L">Melle</option>
			<option value="M">M.</option>
		</select>
	</div><!-- form-group -->
	<div class="form-group col-xs-12 col-sm-6">
		<label for="lname">Nom:</label>
		<input type="text" class="form-control" name="lname">
	</div><!-- form-group -->
	<div class="form-group col-xs-12 col-sm-4">
		<label for="fname">Prénom:</label>
		<input type="text" class="form-control" name="fname">
	</div><!-- form-group -->
	<div class="form-group col-xs-12 col-sm-4">
		<label for="email">Adresse email:</label>
		<input type="email" class="form-control" name="email">
	</div><!-- form-group -->
	<div class="form-group col-xs-12 col-sm-4">
		<label for="phone">Téléphone:</label>
		<input type="tel" class="form-control" name="phone">
	</div><!-- form-group -->
	<div class="form-group col-xs-6 col-sm-2">
		<label for="weight">Poids:</label>
		<input type="number" min="10" max="150" class="form-control" name="weight" value="80">
	</div> <!-- form-group -->
	<div class="form-group col-xs-6 col-sm-3">
		<label for="birthdate">Date de naissance:</label>
		<input type="date" class="form-control" name="birthdate">
	</div> <!-- form-group -->
	<div class="form-group col-xs-6 col-sm-3">
		<label for="role">Ce contact est:</label>
		<div class="checkbox">
			<label><input type="checkbox" name="pax" value="yes">passager</label>
		</div><!-- checkbox-->
		<div class="checkbox">
			<label><input type="checkbox" name="student" value="yes">élève</label>
		</div><!-- checkbox-->
	</div> <!-- form-group -->
</div><!-- row -->

<div class="row">
	<div class="form-group col-xs-12">
		<label for="comment">Commentaires:</label>
		<textarea class="form-control" rows="5" name="comment"></textarea>
	</div><!-- form-group -->
</div><!-- row -->

<div class="row">
<?php
	if ($flight_id== 0)
		print('<button type="submit" class="btn btn-default" name="create" value="create">Créer la demande</button>') ;
	if (isset($flight_id) and $flight_id != 0) {
		print('<input type="hidden" name="flight_id" value="' . $flight_id . '">') ;
		print('<button type="submit" class="btn btn-default" name="modify" value="modify">Modifier la demande</button>') ;
		print('<button type="submit" class="btn btn-default" name="delete" value="delete">Annuler la demande</button>') ;
		$result = mysqli_query($mysqli_link, "SELECT * 
				FROM $table_flight JOIN $table_pax_role ON pr_flight = f_id LEFT JOIN $table_pax ON pr_pax = p_id
				WHERE f_id = $flight_id and pr_role!='C'")
			or die("Cannot retrieve contact role $flight_id: " . mysqli_error($mysqli_link)) ;
		$row_contact = mysqli_fetch_array($result) ;
		mysqli_free_result($result) ;
?>
</div class="row">
</form>

</div><!-- menu contact -->


<div id="menuPassenger" class="tab-pane fade">
<div class="page-header">
<h4>Liste des passagers</h4>
</div><!-- page-header -->

<!--div class="row">
<div class="col-sm-12"-->
<table class="table-responsive table-bordered table-striped col-xs-12 col-md-6">
<thead>
<tr><th>Passager n°</th><th>Rôle</th><th>Nom de famille</th><th>Prénom</th><th>Poids</th><th>Action(s)</th></tr>
</thead>
<tbody>
<?php
// Get all 'real' passengers, the ones being in the plane (this could also be the contact of course)
// Should use the pax_count data to display just the right amount of rows
$result_pax = mysqli_query($mysqli_link, "SELECT * FROM $table_pax_role JOIN $table_pax ON pr_pax = p_id
			WHERE pr_flight = $flight_id AND pr_role <> 'C'") 
			or die("Cannot retrieve passengers list: " . mysqli_error($mysqli_link)) ;
$known_pax_count = 0 ;
while ($row_pax = mysqli_fetch_array($result_pax)) {
	$delete = " <a href=\"flight_create.php?pax_id=$row_pax[p_id]&delete_pax=true&pax_role=$row_pax[pr_role]&flight_id=$flight_id\"><span class=\"glyphicon glyphicon-trash text-danger\"></span></a>" ;
	$known_pax_count ++ ;
	switch ($row_pax['pr_role']) {
		case 'C': $role = 'Contact' ; $delete = '' ; break ; // Cannot delete the contact
		case 'S': $role = 'Elève' ; break ;
		case 'P': $role = 'Simple passager' ; break ;
	}
	print("<form id=\"form_$row_pax[p_id]\" action=\"$_SERVER[PHP_SELF]\">
		<input type=\"hidden\" name=\"pax_id\" value=\"$row_pax[p_id]\">
		<input type=\"hidden\" name=\"flight_id\" value=\"$flight_id\">
		<input type=\"hidden\" name=\"modify_pax\" value=\"modify_pax\">
		<tr><td>$known_pax_count</td><td>$role</td>
		<td><input type=\"text\" name=\"lname\" value=\"" . db2web($row_pax['p_lname']) . "\"></td>
		<td><input type=\"text\" name=\"fname\" value=\"" . db2web($row_pax['p_fname']) . "\"></td>
		<td><input type=\"text\" name=\"weight\" size=\"3\" value=\"" . db2web($row_pax['p_weight']) . "\"> kg</td>
		<td>
		<span class=\"glyphicon glyphicon-floppy-disk text-primary\" onclick=\"submitForm('form_$row_pax[p_id]');\"></span>$delete</td></tr></form>\n") ;
}

for ($i = $known_pax_count+1; $i <= $row_flight['f_pax_cnt']; $i++) {
	print("<form id=\"form_add_$i\" action=\"$_SERVER[PHP_SELF]\">
			<input type=\"hidden\" name=\"flight_id\" value=\"$flight_id\">
			<input type=\"hidden\" name=\"add_pax\" value=\"add_pax\">
			<tr><td>$i</td><td>$role</td>
			<td><input type=\"text\" name=\"lname\"></td>
			<td><input type=\"text\" name=\"fname\"></td>
			<td><input type=\"text\" name=\"weight\" size=\"3\"> kg</td>
			<td><span class=\"glyphicon glyphicon-floppy-disk text-primary\" onclick=\"submitForm('form_add_$i');\"></span></td></tr></form>\n") ;
	} // for
} // (isset($flight_id) and $flight_id != 0)
?>
</tbody>
</table>
<!--/div><!-- col -->
<!--/div><!-- row -->

</div> <!-- menu passenger -->

<div id="menuPilot" class="tab-pane fade">

<div class="row text-info">
<?php
if ($row_flight['f_pilot'])
	print("Le pilote a déjà été choisi pour ce vol mais peut être changé.") ;
else
	print("Le pilote est à choisir parmi les pilotes qualifiés pour ce type de vol.") ;
?>
</div><!-- row -->

<form action="<?=$_SERVER['PHP_SELF']?>" method="GET">
<input type="hidden" name="flight_id" value="<?=$flight_id?>">
	<div class="form-group">
		<label class="control-label col-xs-6 col-md-1" for="pilotSelect">Pilote:</label>
		<div class="col-xs-6 col-md-2">
			<select class="form-control" id="pilotSelect" name="assignedPilot">
<?php
	if ($row_flight['f_type'] == 'D') 
		$condition = 'p_discovery <> 0' ;
	elseif ($row_flight['f_type'] == 'I') 
		$condition = 'p_initiation <> 0' ;
	if (isset($condition)) {
		$result_pilots = mysqli_query($mysqli_link, "SELECT * FROM $table_flights_pilots JOIN $table_person ON p_id=jom_id 
			WHERE $condition ORDER BY last_name ASC, first_name ASC")
			or die("Cannot retrieve pilots: " . mysqli_error($mysqli_link)) ;
		while ($row_pilot = mysqli_fetch_array($result_pilots)) {
			$selected = ($row_pilot['p_id'] == $row_flight['f_pilot']) ? ' selected' : '' ;
			print("<option value=\"$row_pilot[p_id]\"$selected>" . db2web("$row_pilot[first_name] $row_pilot[last_name]") . "</option>\n") ;
		}
		mysqli_free_result($result_pilots) ;
	}
?>
			</select>
		</div>
	</div>
	<div class="form-group">
		<div class="col-xs-3 col-md-2">
			<input type="submit" class="btn btn-primary" name="assign_pilot" value="Selectionner ce pilote"/>
   		</div><!-- col -->
	</div><!-- formgroup-->
</form>

</div><!-- menuPilot -->

<div id="menuAudit" class="tab-pane fade">

<div class="row">
<?php if (! isset($row_flight['first_name']) or $row_flight['first_name'] == '') $row_flight['first_name'] = 'client via la page web' ; ?>
Ce vol a été créé le <?=$row_flight['f_date_created']?> par <?=db2web("$row_flight[first_name] $row_flight[last_name]")?>.<br/>
<?php
if ($row_flight['f_date_cancelled']) print("Puis a été annulé le $row_flight[f_date_cancelled].<br/>") ;
if ($row_flight['f_date_assigned']) print("Le pilote a été sélectionné le $row_flight[f_date_assigned].<br/>") ;
if ($row_flight['f_date_scheduled']) print("Le pilote a réservé l'avion le $row_flight[f_date_scheduled].<br/>") ;
if ($row_flight['f_date_flown']) print("Le vol a eu lieu le $row_flight[f_date_flown].<br/>") ;
?>
</div>

</div><!-- menuAudit -->

</div> <!-- tab-content-->

<script>

function submitForm(id) {
	document.getElementById(id).submit() ;
}

function setValue(name, value) {
	document.getElementsByName(name)[0].value = value.replace(/<br\s*[\/]?>/gi, "\n") ;
}
document.getElementsByName('discovery_flight')[0].checked = ('<?=$row_flight['f_type']?>' == 'D') ;
document.getElementsByName('initiation_flight')[0].checked = ('<?=$row_flight['f_type']?>' == 'I') ;
document.getElementsByName('student')[0].checked = ('<?=$row_contact['pr_role']?>' == 'S') ;
document.getElementsByName('pax')[0].checked = ('<?=$row_contact['pr_role']?>' == 'P') ;
setValue('pax_cnt', '<?=db2web($row_flight['f_pax_cnt'])?>') ;
setValue('lname', '<?=db2web($row_flight['p_lname'])?>') ;
setValue('fname', '<?=db2web($row_flight['p_fname'])?>') ;
setValue('email', '<?=db2web($row_flight['p_email'])?>') ;
setValue('phone', '<?=db2web($row_flight['p_tel'])?>') ;
setValue('weight', '<?=db2web($row_flight['p_weight'])?>') ;
setValue('birthdate', '<?=db2web($row_flight['p_birthdate'])?>') ;
setValue('comment', '<?=db2web(str_replace(array("\r\n", "\n", "\r"), "<br/>", $row_flight['f_description']))?>') ;
for (var i = 0; i < document.getElementsByName("gender")[0].options.length; i++) {
	if (document.getElementsByName("gender")[0].options[i].value == '<?=$row_flight['p_gender']?>')
		document.getElementsByName("gender")[0].options.selectedIndex = i ;
}
</script>

<?php
require_once 'flight_trailer.php' ;
?>