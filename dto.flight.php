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

// TODO remove weather items per David Gaspard's request

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
    if (! $flight->id) {
        journalise($userId, "F", "The flight #$flight_id does not exist") ;
    }
} else {
    journalise($userId, 'F', "Invalid or missing parameter flight=$_REQUEST[flight].") ;
}

if (! ($userIsAdmin or $userIsBoardMember or $userIsInstructor or $userId == $flight->student))
    journalise($userId, "F", "Vous devez être administrateur ou instructeur pour voir cette page.") ;

if (isset($_REQUEST['action']))
    $action = $_REQUEST['action'] ;
else
    $action = NULL ;

// Check if header data needs to be updated
if ($action == 'header') {
    $flight->remark = $_REQUEST['remark'] ;
    $flight->flightType = $_REQUEST['type'] ;
    $flight->sessionGrade = $_REQUEST['grading'] ;
    $flight->save() ;
    journalise($userId, "I", "Flight #$flight_id for $flight->studentLastName updated.") ;
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
        case 'yes': $grade = 'yes' ; break ;
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

<?php
require_once('dto.print.flight.php') ;
printDtoFlight($flight) ;
?>
</body>
</html>