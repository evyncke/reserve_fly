
<?php
/*
   Copyright 2023-2025 Eric Vyncke

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
function printDtoFlight($flight) {
?>
<h2>Flight N° <?=$flight->flightId?> of <?=$flight->studentLastName?> <?=$flight->studentFirstName?>
    <a href="dto.student.php?student=<?=$flight->student?>"><i class="bi bi-folder" title="Back to the list of all flights"></i></a>
    <i class="bi bi-printer link-primary" onclick="window.print();" title="Print this page"></i>
</h2>
<div class="row">
<div class="col-sm-12 col-md-9 col-lg-7">
<form method="POST" action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" name="flight" value="<?=$flight->id?>">
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
<tr><td>Remark</td><td><input class="form-control" type="text" name="remark" size="80" value="<?="$flight->remark"?>"></td></tr>
<tr><td>Session Grading</td><td>
    <select class="form-select" name="grading">
        <option value="unsatisfactory"<?=($flight->sessionGrade == 'unsatisfactory') ? 'selected' : ''?>>Unsatisfactory</option>
        <option value="satisfactory"<?=($flight->sessionGrade == 'satisfactory') ? 'selected' : ''?>>Satisfactory</option>
        <option value="verygood"<?=($flight->sessionGrade == 'verygood') ? 'selected' : ''?>>Very Good</option>
    </select>    
</td></tr>
</tbody>
</table>
</div><!-- table-responsive-->
<p class="font-ligher fst-italic">Last modified by <?="$flight->whoLastName $flight->whoFirstName"?> on <?=$flight->when?>.</p>
<button type="submit" class="btn btn-primary d-print-none">Save the above changes</button>
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
    $exercices = new StudentExercices($flight->student, $flight->id) ;
    foreach ($exercices as $exercice) {
        if ($exercice->grading) { // Multiple choice
?>
     <tr><td><?=$exercice->reference?></td>
     <td><?=$exercice->description?></td>
     <td><div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" value="yes" 
                onChange="gradeChanged(this, '<?=$exercice->reference?>', 'demo')"
            <?=((isset($exercice->grade['demo']) and $exercice->grade['demo'] == 'demo') ? 'checked' : '')?>>
        </div></td>
    <td><div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" value="yes" 
                onChange="gradeChanged(this, '<?=$exercice->reference?>', 'trained')"
            <?=((isset($exercice->grade['trained']) and $exercice->grade['trained'] == 'trained') ? 'checked' : '')?>>
        </div></td>
    <td><div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" value="yes" 
                onChange="gradeChanged(this, '<?=$exercice->reference?>', 'acquired')"
            <?=((isset($exercice->grade['acquired']) and $exercice->grade['acquired'] == 'acquired') ? 'checked' : '')?>>
        </div></td>
    </tr>
<?php
        } else { // Single choice
?>
     <tr><td><b><?=$exercice->reference?></b></td>
     <td><b><?=$exercice->description?></b></td>
     <td colspan="3"><div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" value="yes" 
                onChange="gradeChanged(this, '<?=$exercice->reference?>', 'yes')"
            <?=((isset($exercice->grade['yes']) and $exercice->grade['yes'] == 'yes') ? 'checked' : '')?>> <b>Successful</b>
        </div></td>
     </tr>
<?php
        } // Grading
    } // Foreach
?>
</tbody>
</table>
</div><!-- table-responsive-->
</div><!-- col --> 
</div><!-- row -->
<?php
} // printDtoFligh()
?>