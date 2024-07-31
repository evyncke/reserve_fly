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

require_once "dbi.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}
require_once 'mobile_header5.php' ;
require_once 'dto.class.php' ;

if (! ($userIsAdmin or $userIsBoardMember or $userIsInstructor))
    journalise($userId, "F", "Vous devez être administrateur ou instructeur pour voir cette page.") ;

if (isset($_REQUEST['fi']) and $_REQUEST['fi'] != '')
    $fi = $_REQUEST['fi'] ;
else
    $fi = $userId ;
?>
<h2>My last uncompleted flights</h2>

<p>This is the list of flights that were not modified/completed by the FI.</p>

<div class="row">
<div class="col-sm-12 col-md-9 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<th>Flight#</th><th>Date</th><th>Duration</th><th>Type</th><th>Plane</th><th>Student</th>
</thead>
<tbody class="table-group-divider">

<?php
    $flights = new Flights() ;
    $flights->getUnprocessedByFI($fi) ;
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
        print("<tr><td>#$flight->id  $session_grade_message <a href=\"dto.flight.php?flight=$flight->id\">
            <i class=\"bi bi-pencil-fill\" data-bs-toggle=\"tooltip\" title=\"See/edit the student flight report\"></i></a></td><td>$flight->date</td>
            <td>$flight->flightDuration</td><td>$flight->flightType</td><td>$flight->plane</td><td><b>$flight->studentLastName</b> $flight->studentFirstName</td></tr>\n") ;
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

</body>
</html>