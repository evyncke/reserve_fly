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

<h2>Liste des vols de <?=$student->lastName?> <?=$student->firstName?></h2>
<div class="row">
<div class="col-sm-12 col-md-9 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<th>Vol</th><th>Date</th><th>Durée</th><th>Type</th><th>Avion</th><th>Instructeur</th>
</thead>
<tbody>

<?php
    $flights = new Flights($student_id) ;
    $dc_minutes = 0 ;
    $solo_minutes = 0 ;
    $xcountry_minutes = 0 ;
    $total_minutes = 0 ;
    foreach($flights as $flight) {
        print("<tr><td><a href=\"dto.flight.php?flight=$flight->id\">$flight->flightId</a></td><td>$flight->date</td><td>$flight->flightDuration</td><td>$flight->flightType</td><td>$flight->plane</td><td>$flight->fiLastName $flight->fiFirstName</td></tr>\n") ;
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
<div class="row">
<h2>Résumé</h2>
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
<p><ul>
    <li>Nombre de vols: <?=$flights->count?></li>
    <li>DC: <?="$dc_hours H $dc_minutes min"?></li>
    <li>Solo: <?="$solo_hours H $solo_minutes min"?></li>
    <li>X-country: <?="$xcountry_hours H $xcountry_minutes min"?></li>
    <li>Total: <?="$total_hours H $total_minutes min"?></li>
    </li>
</ul>
</div><!-- row -->
</body>
</html>