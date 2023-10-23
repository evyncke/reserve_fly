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

if (isset($_REQUEST['flight']) and is_numeric($_REQUEST['flight']) and $_REQUEST['flight'] != '') {
    $flight_id = $_REQUEST['flight'] ;
    $flight = new Flight() ;
    $flight->getById($flight_id) ;
} else {
    journalise($userId, 'F', "Invalid parameter flight=$_REQUEST[flight].") ;
}
if (! ($userIsAdmin or $userIsInstructor or $userId == $student))
    journalise($userId, "F", "Vous devez être administrateur ou instructeur pour voir cette page.") ;
?>
<h2>Flight N° <?=$flight->flightId?> of <?=$flight->studentLastName?> <?=$flight->studentFirstName?></h2>
<div class="row">
<div class="col-sm-12 col-md-9 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<tbody>
<tr><td>Date</td><td><?=$flight->date?></td></tr>
<tr><td>Instructor</td><td><?="$flight->fiLastName $flight->fiFirstName"?></td></tr>
<tr><td>Plane</td><td><?="$flight->plane ($flight->planeModel)"?></td></tr>
<tr><td>Weather</td><td><?="$flight->weather"?></td></tr>
</tbody>
</table>
</div><!-- table-responsive-->
</div><!-- col --> 
</div><!-- row -->

<h2>Exercices</h2>
<div class="row">
<div class="col-sm-12 col-md-9 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<tr><th>Ref</th><th>Description</th><th>Demo</th><th>Trained</th><th>Acquired</th></tr>
</thead>
<tbody>
</tbody>
</table>
</div><!-- table-responsive-->
</div><!-- col --> 
</div><!-- row -->
</body>
</html>