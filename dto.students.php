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
if (! ($userIsAdmin or $userIsInstructor))
    journalise($userId, "F", "Vous devez être adminstrateur ou instructeur pour voir cette page.") ;
require_once 'mobile_header5.php' ;
require_once 'dto.class.php' ;

if (isset($_REQUEST['fi']) and is_numeric($_REQUEST['fi']) and $_REQUEST['fi'] != '') {
    $fi = $_REQUEST['fi'] ;
} else {
    $fi = NULL ;
}

?>

<h2>Liste des élèves en cours de formation</h2>
<div class="row">
<div class="col-sm-12 col-md-12 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<th>Elèves</th><th>Premier/dernier vols</th><th class="d-none d-md-table-cell">Email</th><th class="d-none d-md-table-cell">Mobile</th></tr>
</thead>
<tbody class="table-group-divider">

<?php
    $dto = new DTO() ;
    $students = $dto->Students($fi) ;
    foreach($students as $student) {
        $blocked = ($student->blocked) ? ' <i class="bi bi-sign-stop-fill text-danger" title="This member is blocked"></i>' : '' ;
        print("<tr>
            <td>
                <a href=\"dto.student.php?student=$student->jom_id\" title=\"Display all flights\">$student->lastName $student->firstName<i class=\"bi bi-binoculars-fill\"></i></a>
                    $blocked
                    <a href=\"mailto:$student->email\"><i class=\"bi bi-envelope-fill\" title=\"Send email\"></i></a>
                    <a href=\"tel:$student->mobilePhone\"><i class=\"bi bi-telephone-fill\" title=\"Call on mobile\"></i></a>
            </td>
            <td>$student->firstFlight <span class=\"badge bg-primary\" title=\"Number of flights\">$student->countFlights</span><br/>$student->lastFlight</td>
            <td class=\"d-none d-md-table-cell\"><a href=\"mailto:$student->email\">$student->email</a></td>
            <td class=\"d-none d-md-table-cell\"><a href=\"tel:$student->mobilePhone\">$student->mobilePhone</a></td>
            </tr>\n") ;
    }
?>
</tbody>
</table>
</div><!-- table responsive -->
</div><!-- col -->
</div><!-- row --> 
</body>
</html>