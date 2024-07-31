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
require_once 'dto.class.php' ;

if (isset($_REQUEST['file']) and $_REQUEST['file'] != '') {
    $hashed_filename = $_REQUEST['file'] ;
    $file = new StudentDocument() ;
    $file->getByHashedFilename($hashed_filename) ;
    if (!$file->id)
        journalise($userId, "F", "This file $hashed_filename does not exist") ;
} else {
    journalise($userId, 'F', "Invalid parameterfile=$_REQUEST[file].") ;
}
if (! ($userIsAdmin or $userIsInstructor or $userId == $file->studentId))
    journalise($userId, "F", "Vous devez être administrateur ou instructeur pour voir cette page.") ;

if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'delete') {
    if (! ($userIsAdmin or $userIsInstructor))
        journalise($userId, "F", "Vous n'avez pas l'autorisation pour effacer ce fichier") ;
    $file->delete() ;
}
header("Location: https://www.spa-aviation.be/resa/dto.student.php?student=$file->studentId") ;
?>