<?php
// TODO
// warn the bookers/pilots just before/after
// add pilot/booker's name in error messages...

/*
   Copyright 2013 Eric Vyncke

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

require_once 'dbi.php' ;
require_once 'facebook.php' ;

$response = array() ;
$response['error'] = '' ; // Until now: no error :-)
$response['message'] = '' ; // Until now: no error :-)

// Parameter sanitization
$id = trim($_REQUEST['id']) ;
if ($id == '') die("Missing parameter: id") ;
if (!is_numeric($id)) die("Bien essaye... $id") ;

$response['id'] = $id ;


$result = mysqli_query($mysqli_link, "select * from $table_fi_agenda where f_id = $id") ;
if ((!$result) || (mysqli_num_rows($result) == 0))
	$response['error'] .= "Cet item agenda, $id, n'existe pas, " . mysqli_error($mysqli_link) . '<br/>' ;
else {
	$item = mysqli_fetch_array($result) ;
	if (!($userIsAdmin || $userIsInstructor)) {
		$response['error'] .= "Il faut etre admin ou instructeur.<br/>" ;
	}
}

if ($response['error'] == '') {
	$result = mysqli_query($mysqli_link, "delete from $table_fi_agenda where f_id = $id") ;
	if ($result && mysqli_affected_rows($mysqli_link) == 1) {
		$response['message'] = 'Agenda mis &agrave; jour.' ;
		journalise($userId, 'I', "Cancellation of agenda for $item[f_instructor] $item[f_start] => $item[f_end]") ;
	} else
		$response['error'] .= "Un probl&egrave;me technique s'est produit, annulation non effectu&eacute;e..." . mysqli_error($mysqli_link) . "<br/>" ;
}

// Let's send the data back
header('Content-type: application/json');
print(json_encode($response)) ;

if ($response['error'])
	journalise($userId, 'E', "Error ($response[error]) while cancelling agenda item") ;
?>
