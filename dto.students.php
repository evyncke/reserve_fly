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

if (isset($_REQUEST['fi']) and is_numeric($_REQUEST['fi']) and $_REQUEST['fi'] != '') {
    $fi = $_REQUEST['fi'] ;
} else {
    $fi = NULL ;
}

?>

<h2>Liste des élèves en cours de formation</h2>
<div class="row">
<div class="col-sm-12 col-md-9 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<th>Nom</th><th>Prénom</th><th>Premier/dernier vol</th><th>Email</th><th>Mobile</th>
</thead>
<tbody>

<?php
    $dto = new DTO() ;
    $students = $dto->Students($fi) ;
    foreach($students as $student) {
//        var_dump($student) ;
        print("<tr><td>$student->lastName</td><td>$student->firstName</td><td>$student->firstFlight<br/>$student->lastFlight
            <td><a href=\"mailto:$student->email\">$student->email</a></td>
            <td><a href=\"tel:$student->mobilePhone\">$student->mobilePhone</a></td></tr>\n") ;
    }
?>
</tbody>
</table>
</div><!-- table responsive -->
</div><!-- col -->
</div><!-- row --> 
</body>
</html>