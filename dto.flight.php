<?php
/*
   Copyright 2023 Eric Vyncke

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
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}
require_once 'mobile_header5.php' ;
require_once 'dto.class.php' ;

// Check if the request is for the last_flight (last_flight is set) of one fi (fi has a value)
if (isset($_REQUEST['last']) and isset($_REQUEST['fi']) and $_REQUEST['fi'] != '' and is_numeric($_REQUEST['fi'])) {
                $fi = $_REQUEST['fi'] ;
    $flight = new Flight() ;
    $flight->getLastByFi($fi) ;
    if (! $flight->id) journalise($userId, "F", "No flight found for FI=$fi") ;
} else if (isset($_REQUEST['flight']) and is_numeric($_REQUEST['flight']) and $_REQUEST['flight'] != '') {
    $flight_id = $_REQUEST['flight'] ;
    $flight = new Flight() ;
    $flight->getById($flight_id) ;
} else {
    journalise($userId, 'F', "Invalid or missing parameter flight=$_REQUEST[flight].") ;
}

if (! ($userIsAdmin or $userIsInstructor or $userId == $flight->student))
    journalise($userId, "F", "Vous devez être administrateur ou instructeur pour voir cette page.") ;

if (isset($_REQUEST['action']))
    $action = $_REQUEST['action'] ;
else
    $action = NULL ;

// Check if header data needs to be updated
if ($action == 'header') {
    $flight->remark = $_REQUEST['remark'] ;
    $flight->weather = $_REQUEST['weather'] ;
    $flight->flightType = $_REQUEST['type'] ;
    $flight->save() ;
} else if ($action == 'exercice') {
    if (!isset($_REQUEST['exercice']))
        journalise($userId, "F", "Missing parameter exercice") ;
    $exercice = new StudentExercice() ;
    $exercice->getByFlightExercice($flight_id, $_REQUEST['exercice']) ;
    // apply changes
    if (!isset($_REQUEST['grade']))
        journalise($userId, "F", "Missing parameter grade") ;
    if (!isset($_REQUEST['value']))
        journalise($userId, "F", "Missing parameter grade") ;
    switch ($_REQUEST['grade']) {
        case 'demo': $grade = 'demo' ; break ;
        case 'trained': $grade = 'trained' ; break ;
        case 'acquired': $grade = 'acquired' ; break ;
        default: journalise($userId, "F", "Wrong value for grade=$_REQUEST[grade]") ;
    }
    switch ($_REQUEST['value']) {
        case 'set': $exercice->grade[$grade] = $grade ; break ;
        case 'unset': unset($exercice->grade[$grade]) ; break ;
        default: journalise($userId, "F", "Invalid value for value=$_REQUEST[value]") ;
    }
    $exercice->save() ;
}

?>
<script type="text/javascript">
function gradeChanged(object, reference, grade) {
    if (object.checked)
        value = 'set' ;
    else
        value = 'unset' ;
    window.location.href = "https://www.spa-aviation.be/resa/dto.flight.php?flight=<?=$flight->id?>&action=exercice&exercice=" + reference + "&grade=" + grade + "&value=" + value;
}
</script>
<h2>Flight N° <?=$flight->flightId?> of <?=$flight->studentLastName?> <?=$flight->studentFirstName?></h2>
<div class="row">
<div class="col-sm-12 col-md-9 col-lg-7">
<form method="GET" action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" name="flight" value="<?=$flight_id?>">
<input type="hidden" name="action" value="header">
<div class="table-responsive">
<table class="table table-striped table-hover">
<tbody>
<tr><td>Date</td><td><?=$flight->date?></td></tr>
<tr><td>Instructor</td><td><?="$flight->fiLastName $flight->fiFirstName"?></td></tr>
<tr><td>Plane</td><td><?="$flight->plane ($flight->planeModel)"?></td></tr>
<tr><td>Flight Type</td><td>
    <select class="form-select" name="type">
        <option value="DC"<?=($flight->flightType == 'DC') ? 'selected' : ''?>>DC</option>
        <option value="solo"<?=($flight->flightType == 'solo') ? 'selected' : ''?>>solo</option>
        <option value="Xcountry"<?=($flight->flightType == 'Xcountry') ? 'selected' : ''?>>Xcountry</option>
    </select>    
</td></tr>
<tr><td>Flight Duration</td><td><?="$flight->flightDuration"?> minutes</td></tr>
<tr><td>Weather</td><td><input class="form-control" type="text" name="weather" size="80" value="<?="$flight->weather"?>"></td></tr>
<tr><td>Remark</td><td><input class="form-control" type="text" name="remark" size="80" value="<?="$flight->remark"?>"></td></tr>
</tbody>
</table>
</div><!-- table-responsive-->
<button type="submit" class="btn btn-primary">Save the above changes</button>
</form>
</div><!-- col --> 
</div><!-- row -->

<hr>

<h2>Exercices</h2>
<div class="row">
<div class="col-sm-12 col-md-9 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<tr><th>Ref</th><th>Description</th><th>Demo</th><th>Trained</th><th>Acquired</th></tr>
</thead>
<tbody>
<?php
    $exercices = new StudentExercices($flight->student, $flight_id) ;
    foreach ($exercices as $exercice) {
?>
     <tr><td><?=$exercice->reference?></td>
     <td><?=$exercice->description?></td>
     <td><div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" value="yes" 
                onChange="gradeChanged(this, '<?=$exercice->reference?>', 'demo')"
            <?=(($exercice->grade['demo'] == 'demo') ? 'checked' : '')?>>
        </div></td>
    <td><div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" value="yes" 
                onChange="gradeChanged(this, '<?=$exercice->reference?>', 'trained')"
            <?=(($exercice->grade['trained'] == 'trained') ? 'checked' : '')?>>
        </div></td>
    <td><div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" value="yes" 
                onChange="gradeChanged(this, '<?=$exercice->reference?>', 'acquired')"
            <?=(($exercice->grade['acquired'] == 'acquired') ? 'checked' : '')?>>
        </div></td>
    </tr>
<?php
    } // Foreach
?>
</tbody>
</table>
</div><!-- table-responsive-->
</div><!-- col --> 
</div><!-- row -->
</body>
</html>
