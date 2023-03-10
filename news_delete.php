<?php
/*
   Copyright 2014-2019 Eric Vyncke

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

// TODO

require_once "dbi.php" ;
require_once 'facebook.php' ;

MustBeLoggedIn() ;


if (! $userIsAdmin)
	die("Vous devez &ecirc;tre connect&eacute; et administrateur pour effacer une nouvelle.") ;

$id = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['id'])) ;
if (! is_numeric($id)) die("Invalid value for id ($id)") ;

mysqli_query($mysqli_link, "UPDATE $table_news SET n_stop = DATE_SUB(sysdate(), INTERVAL 1 DAY) WHERE n_id = $id")
		or journalise($userId, "E", "Cannot delete news $id: " . mysqli_error($mysqli_link)) ;
	// So far so good, redirect to the reservation page
journalise($userId, 'W', "News $id deleted") ;
header('Location: ' . 'https://www.spa-aviation.be/resa/') ;
die() ; 

?>