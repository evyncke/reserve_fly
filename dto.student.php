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

if (isset($_REQUEST['student']) and is_numeric($_REQUEST['student']) and $_REQUEST['student'] != '') {
    $student_id = $_REQUEST['student'] ;
    $student = new Student() ;
    $student->getById($student_id) ;
} else {
    journalise($userId, 'F', "Invalid parameter student=$_REQUEST[student].") ;
}
if (! ($userIsAdmin or $userIsInstructor or $userId == $student))
    journalise($userId, "F", "Vous devez être administrateur ou instructeur pour voir cette page.") ;
?>

<h2>Details for <?=$student->lastName?> <?=$student->firstName?></h2>

<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item">
  		<a class="nav-link active" role="presentation" data-bs-toggle="tab" data-bs-target="#summary" aria-current="page" href="#summary">Summary</a>
	</li>
    <li class="nav-item">
  		<a class="nav-link" role="presentation" data-bs-toggle="tab" data-bs-target="#flights" aria-current="page" href="#flights">Flights</a>
	</li>
    <li class="nav-item">
  		<a class="nav-link" role="presentation" data-bs-toggle="tab" data-bs-target="#exercices" aria-current="page" href="#exercices">Exercices</a>
	</li>
</ul>

<div class="tab-content">

<div class="tab-pane fade" id="flights" role="tabpanel">
<div class="row">
<div class="col-sm-12 col-md-9 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<th>Flight#</th><th>Date</th><th>Duration</th><th>Type</th><th>Plane</th><th>Instructor</th>
</thead>
<tbody>

<?php
    $flights = new Flights($student_id) ;
    $dc_minutes = 0 ;
    $solo_minutes = 0 ;
    $xcountry_minutes = 0 ;
    $total_minutes = 0 ;
    foreach($flights as $flight) {
        switch ($flight->sessionGrade) {
            case 'unsatisfactory': $session_grade_message = '<i class="bi bi-hand-thumbs-down-fill text-danger" title="Unsatisfactory flight"></i>' ; break ; 
            case 'verygood': $session_grade_message = '<i class="bi bi-hand-thumbs-up-fill text-success" title="Very good flight"></i>' ; break ; 
            default: $session_grade_message = '' ;
        }
        print("<tr><td>$flight->flightId  $session_grade_message <a href=\"dto.flight.php?flight=$flight->id\">
            <i class=\"bi bi-pencil-fill\" data-bs-toggle=\"tooltip\" title=\"See/edit the student flight report\"></i></a></td><td>$flight->date</td>
            <td>$flight->flightDuration</td><td>$flight->flightType</td><td>$flight->plane</td><td>$flight->fiLastName $flight->fiFirstName</td></tr>\n") ;
        // Sum up the duration
        switch ($flight->flightType) {
            case 'DC': $dc_minutes += $flight->flightDuration ; break ;
            case 'solo': $solo_minutes += $flight->flightDuration ; break ;
            case 'XCountry': $xcountry_minutes += $flight->flightDuration ; break ;
        }
        $total_minutes += $flight->flightDuration ;
    }
?>
</tbody>
</table>
</div><!-- table responsive -->
</div><!-- col -->
</div><!-- row --> 
</div><!-- tab-pane--> 

<div class="tab-pane fade show active" id="summary" role="tabpanel">
<?php
// Let's convert minutes into hours
$dc_hours = intdiv($dc_minutes, 60) ;
$dc_minutes = $dc_minutes % 60 ;
$solo_hours = intdiv($solo_minutes, 60) ;
$solo_minutes = $solo_minutes % 60 ;
$xcountry_hours = intdiv($xcountry_minutes, 60) ;
$xcountry_minutes = $xcountry_minutes % 60 ;
$total_hours = intdiv($total_minutes, 60) ;
$total_minutes = $total_minutes % 60 ;
?>
<div class="row">
<div class="col-sm-12 col-md-4">
<p><ul>
    <li>Flight count: <?=$flights->count?></li>
    <li>DC: <?="$dc_hours H $dc_minutes min"?></li>
    <li>Solo: <?="$solo_hours H $solo_minutes min"?></li>
    <li>X-country: <?="$xcountry_hours H $xcountry_minutes min"?></li>
    <li>Total: <?="$total_hours H $total_minutes min"?></li>
</ul>
</p>
</div><!-- col -->
<div class="col-sm-12 col-md-4">
<p>Placeholder for email, phone, postal address, picture.</p>
<?php
if ($student->blocked)
    print("<p class=\"mt-2 p-4 bg-danger text-bg-danger rounded\">$student->blockedMessage</p>") ;
?>
<p><ul>
<li>Email: <a href="mailto:<?=$student->email?>"  title="Send email"><?=$student->email?> <i class="bi bi-envelope-fill"></i></a></li>
<li>Mobile Phone: <a href="phone:<?=$student->mobilePhone?>"  title="Call mobile phone"><?=$student->mobilePhone?> <i class="bi bi-telephone-fill"></i></a></li>
</ul>
</p>
<p>
    <?=$student->address?><br/>
    <?="$student->zipCode $student->city"?><br/>
    <?="$student->country"?>
</p>
</div><!-- col -->
</div><!-- row -->
</div><!-- tab-pane --> 

<div class="tab-pane fade" id="exercices" role="tabpanel">
<div class="row">
    <p>The table below shows the aggregated evaluation for all exercices. It is read-only, to modify the evaluation of an exercice,
        the FI must go to an individual flight report and change it there.
    </p>
<div class="col-sm-12 col-md-9 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<tr><th>Ref</th><th>Description</th><th>Demo</th><th>Trained</th><th>Acquired</th></tr>
</thead>
<tbody>
<?php
// Let's display the aggregated exercises
$exercices = new StudentExercices($student_id) ;
foreach ($exercices as $exercice) {
    if ($exercice->grading) { // Multiple choice
?>
 <tr><td><?=$exercice->reference?></td>
 <td><?=$exercice->description?></td>
 <td><div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" value="yes" disabled
            onChange="gradeChanged(this, '<?=$exercice->reference?>', 'demo')"
        <?=(($exercice->grade['demo'] == 'demo') ? 'checked' : '')?>>
    </div></td>
<td><div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" value="yes" disabled
            onChange="gradeChanged(this, '<?=$exercice->reference?>', 'trained')"
        <?=(($exercice->grade['trained'] == 'trained') ? 'checked' : '')?>>
    </div></td>
<td><div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" value="yes" disabled
            onChange="gradeChanged(this, '<?=$exercice->reference?>', 'acquired')"
        <?=(($exercice->grade['acquired'] == 'acquired') ? 'checked' : '')?>>
    </div></td>
</tr>
<?php
    } else { // Single choice
?>
 <tr><td><b><?=$exercice->reference?></b></td>
 <td><b><?=$exercice->description?></b></td>
 <td colspan="3"><div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" value="yes" disabled
            onChange="gradeChanged(this, '<?=$exercice->reference?>', 'yes')"
        <?=(($exercice->grade['yes'] == 'yes') ? 'checked' : '')?>> <b>Successful</b>
    </div></td>
 </tr>
<?php
    } // Grading
} // Foreach
?>
</tbody>
</table>
</div><!-- table responsive -->
</div><!-- col -->
</div><!-- row --> 

</div><!-- tab-pane -->

</div><!-- tab-content -->
</body>
</html>