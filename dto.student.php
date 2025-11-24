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
if (! ($userIsAdmin or $userIsBoardMember or $userIsInstructor or $userId == $student_id))
    journalise($userId, "F", "Vous devez être administrateur ou instructeur pour voir cette page.") ;

if ($userId == $student_id)
	journalise($userId, "I", "Student looking his/her flights") ;

if (isset($_POST['action']) and $_POST['action'] == 'upload') {
    if (!isset($_FILES['file']) or !isset($_FILES['file']['name']) or $_FILES['file']['size'] == 0)
        die('Please select a file. <button onclick="window.history.back();">Try again</button>') ;
    $source_file = $_FILES['file']['tmp_name'] ;
    $path_info = pathinfo($_FILES['file']['name']) ;
    $extension = $path_info['extension'] ;
    $hashed_filename = sha1($source_file . $shared_secret) . ".$extension";
    if (!move_uploaded_file($source_file, "dto_files/$hashed_filename")) {
        journalise($userId, 'E', "Unable to move to dto_files/$hash_filename: $source_file") ;
    }
    $document = new StudentDocument() ;
    $document->hashedFilename = $hashed_filename ;
    $document->originalFilename = $_FILES['file']['name'] ;
    $document->originalMIMEType = $_FILES['file']['type'] ;
    $document->size = $_FILES['file']['size'] ;
    $document->studentId = $student_id ;
    $document->save() ;
    journalise($userId, "I", "File $document->originalFilename uploaded") ;
}
?>

<h2>Details for <?=$student->lastName?> <?=$student->firstName?></h2>

<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item">
  		<a class="nav-link active" role="presentation" data-bs-toggle="tab" data-bs-target="#summary" aria-current="page" href="#summary">Summary</a>
	</li>
    <li class="nav-item">
  		<a class="nav-link" role="presentation" data-bs-toggle="tab" data-bs-target="#documents" aria-current="page" href="#documents">Documents</a>
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
<p><a href="dto.flight.php?flight=all&student=<?=$student_id?>">Print</a> all flights of this student.</p>
<div class="col-sm-12 col-md-9 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<th>Flight#</th><th>Date</th><th>Duration</th><th>Type</th><th>Plane</th><th>Instructor</th>
</thead>
<tbody class="table-group-divider">

<?php
    $flights = new Flights($student_id) ;
    $dc_minutes = 0 ;
    $supervised_minutes = 0 ;
    $solo_minutes = 0 ;
    $xcountry_minutes = 0 ;
    $total_minutes = 0 ;
    $last_flight = 'never' ;
    foreach($flights as $flight) {
        switch ($flight->sessionGrade) {
            case 'unsatisfactory': $session_grade_message = '<i class="bi bi-hand-thumbs-down-fill text-danger" title="Unsatisfactory flight"></i>' ; break ; 
            case 'verygood': $session_grade_message = '<i class="bi bi-hand-thumbs-up-fill text-success" title="Very good flight"></i>' ; break ; 
            default: $session_grade_message = '' ;
        }
        $route = ($flight->flightFrom != '' and $flight->flightTo != '' and ($flight->flightFrom != 'EBSP' or $flight->flightTo != 'EBSP')) ? 
            " ($flight->flightFrom - $flight->flightTo)" : '' ;
        print("<tr><td>$flight->flightId  $session_grade_message <a href=\"dto.flight.php?flight=$flight->id\">
            <i class=\"bi bi-pencil-fill\" data-bs-toggle=\"tooltip\" title=\"See/edit the student flight report\"></i></a></td><td>$flight->date</td>
            <td>$flight->flightDuration</td><td>$flight->flightType$route</td><td>$flight->plane</td><td>$flight->fiLastName $flight->fiFirstName</td></tr>\n") ;
        // Sum up the duration
        switch ($flight->flightType) {
            case 'DC': $dc_minutes += $flight->flightDuration ; break ;
            case 'solo': $solo_minutes += $flight->flightDuration ; $supervised_minutes += $flight->flightDuration ; break ;
            case 'Xcountry': $xcountry_minutes += $flight->flightDuration ; $supervised_minutes += $flight->flightDuration ; break ;
            default: journalise($userId, 'E', "Unknown flight type '$flight->flightType' for flight id $flight->id") ; break ;
        }
        $total_minutes += $flight->flightDuration ;
        $last_flight = $flight->date ;
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
$dc_pct = round(($dc_hours * 60 + $dc_minutes) * 100 / (25 * 60)) ;
$solo_hours = intdiv($solo_minutes, 60) ;
$solo_minutes = $solo_minutes % 60 ;
$xcountry_hours = intdiv($xcountry_minutes, 60) ;
$xcountry_minutes = $xcountry_minutes % 60 ;
$xcountry_pct = round(($xcountry_hours * 60 + $xcountry_minutes) * 100 / (5 * 60)) ;
$supervised_hours = intdiv($supervised_minutes, 60) ;
$supervised_minutes = $supervised_minutes % 60 ;
$supervised_pct = round(($supervised_hours * 60 + $supervised_minutes) * 100 / (10 * 60)) ;
$total_hours = intdiv($total_minutes, 60) ;
$total_minutes = $total_minutes % 60 ;
$total_pct = round(($total_hours * 60 + $total_minutes) * 100 / (45 * 60)) ;

// TODO add line bar/progress bar ?
// 45: of flight hours required for PPL
// 25 of DC
// 10 of supervised solo
// 5 of solo cross country
?>
<div class="row">
<div class="col-sm-12 col-md-4">
<p><ul>
    <li>Flight count: <?=$flights->count?></li>
    <li>Last flight: <?=$last_flight?></li>
    <li>DC: <?="$dc_hours H $dc_minutes min"?><br/>
        <div class="progress" role="progressbar">
            <div class="progress-bar<?=($dc_pct>=100)?' bg-success' : ''?>" style="width: <?=$dc_pct?>%"><?=$dc_pct?>%</div>
        </div>
    </li>
    <ul>
        <li>Supervised solo: <?="$solo_hours H $solo_minutes min"?></li>
        <li>Solo X-country: <?="$xcountry_hours H $xcountry_minutes min"?><br/>
            <div class="progress" role="progressbar">
                <div class="progress-bar<?=($xcountry_pct>=100)?' bg-success' : ''?>" style="width: <?=$xcountry_pct?>%"><?=$xcountry_pct?>%</div>
            </div>
        </li>
        <li><b>Total solo: <?="$supervised_hours H $supervised_minutes min"?></b><br/></li>
            <div class="progress" role="progressbar">
                <div class="progress-bar<?=($supervised_pct>=100)?' bg-success' : ''?>" style="width: <?=$supervised_pct?>%"><?=$supervised_pct?>%</div>
            </div>
        </li>
    </ul>
    <li><b>Grand total: <?="$total_hours H $total_minutes min"?></b><br/>
        <div class="progress" role="progressbar">
            <div class="progress-bar<?=($total_pct>=100)?' bg-success' : ''?>" style="width: <?=$total_pct?>%"><?=$total_pct?>%</div>
        </div>
    </li>
</ul>
</p>
</div><!-- col -->
<div class="col-sm-12 col-md-4">
<img src="<?=$student->picture?>" width="80">
</div><!-- col -->
<div class="col-sm-12 col-md-4">
<?php
if ($student->blocked)
    print("<p class=\"mt-2 p-4 bg-danger text-bg-danger rounded\">$student->blockedMessage</p>") ;
if (! $student->isStudent())
    print("<p class=\"mt-2 p-4 bg-warning text-bg-warning rounded\">$student->lastName $student->firstName is no more registered as a student.</p>") ;
if (!$student->membershipPaid)
    print("<p class=\"mt-2 p-4 bg-danger text-bg-danger rounded\">Membership fee unpaid.</p>") ;
if ($student->mobilePhone == '')
    print("<p class=\"mt-2 p-4 bg-danger text-bg-danger rounded\">Mobile phone number unknown.</p>") ;
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
        <?=((isset($exercice->grade['demo']) and $exercice->grade['demo'] == 'demo') ? 'checked' : '')?>>
    </div></td>
<td><div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" value="yes" disabled
            onChange="gradeChanged(this, '<?=$exercice->reference?>', 'trained')"
        <?=((isset($exercice->grade['trained']) and $exercice->grade['trained'] == 'trained') ? 'checked' : '')?>>
    </div></td>
<td><div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" value="yes" disabled
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
    <input class="form-check-input" type="checkbox" value="yes" disabled
            onChange="gradeChanged(this, '<?=$exercice->reference?>', 'yes')"
        <?=((isset($exercice->grade['yes']) and $exercice->grade['yes'] == 'yes') ? 'checked' : '')?>> 
        <?=((isset($exercice->grade['yes']) and $exercice->grade['yes'] == 'yes') ? '<b>Successful</b>' : 'Sucessful')?>
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

<div class="tab-pane fade" id="documents" role="tabpanel">

<div class="row">
    <p><a href="dto.flight.php?flight=all&student=<?=$student_id?>">Print</a> all flights of this student.</p>
    <p>Here are all documents linked to this student.</p>
</div><!--"row -->    
<div class="col-sm-12 col-md-9 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<tr><th>Filename</th><th class="d-none d-lg-table-cell">Size</th><th class="d-none d-lg-table-cell">Date</th><th class="d-none d-lg-table-cell">Uploaded by</th></tr>
</thead>
<tbody class="table-group-divider">
<?php
$documents = new StudentDocuments($student_id) ;
foreach ($documents as $document) {
?>
<tr>
    <td><?=$document->originalFilename?>
        <a href="dto_files/<?=$document->hashedFilename?>" download="<?=$document->originalFilename?>" type="<?=$document->originalMIMEType?>">
        <i class="bi bi-file-earmark-arrow-down-fill"></i></a>
        <!-- should also use the dto.file.php to have the right MIME type and filename -->
        <a href="dto.file.php?action=delete&file=<?=$document->hashedFilename?>" rel="noopener" target="_blank"><i class="bi bi-trash3-fill text-danger"></i></a></td>
    <td class="d-none d-lg-table-cell"><?=$document->size?></td>
    <td class="d-none d-lg-table-cell"><?=$document->when?></td>
    <td class="d-none d-lg-table-cell"><?="<b>$document->whoLastName</b> $document->whoFirstName"?></td>
</tr>
<?php
} // Foreach
?>
</tbody>
</table>
</div><!-- table responsive -->
</div><!-- col -->
</div><!-- row --> 

<div class="row">
    <hr>
<form method="POST" action="<?=$_SERVER['PHP_SELF']?>" enctype="multipart/form-data" role="form" class="form-inline">
<input type="hidden" name="student" value="<?=$student->jom_id?>">
<input type="hidden" name="action" value="upload">
<div class="mb-3">
  <label for="file" class="form-label">Add a new document</label>
  <input class="form-control" type="file" id="file" name="file">
</div>
<button class="btn btn-primary" type="submit">Upload the file</button>
</form>
</div><!-- row --> 
</div><!-- tab-pane --> 

</div><!-- tab-content -->
</body>
</html>
