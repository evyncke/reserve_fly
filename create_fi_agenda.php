<?php
/*
   Copyright 2014-2024 Eric Vyncke

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

$response = array() ;
$response['error'] = '' ; // Until now: no error :-)

// Parameter sanitization
$instructor_id = $_REQUEST['fi'] ;
if ($instructor_id) {
	if (!is_numeric($instructor_id)) die("Bien essaye... instructor: $instructor_id") ;
	if ($instructor_id == -1) $instructor_id = "NULL" ;
} else
	die("Missing instructor ID") ;
$call_type = $_REQUEST['callType'] ;
if (!is_numeric($call_type)) die("Bien essaye... type: $call_type") ;
$student_only = $_REQUEST['studentOnly'] ;
if (!is_numeric($student_only)) die("Bien essaye... student: $student_only") ;
$start = mysqli_real_escape_string($mysqli_link, $_REQUEST['start']) ;
$end = mysqli_real_escape_string($mysqli_link, $_REQUEST['end']) ;
$comment = mysqli_real_escape_string($mysqli_link, $_REQUEST['comment']) ;

// Basic checks on dates
$start_date = new DateTime($start) ;
if (!$start_date) $response['error'] .= "$start is not a valid date<br/>" ;
$end_date = new DateTime($end) ;
if (!$end_date) $response['error'] .= "$end is not a valid date<br/>" ;
if ($end_date <= $start_date) $response['error'] .= "La fin doit &ecirc;tre apr&egrave;s le d&eacute;but: $start -> $end.<br/>" ;

// Check on user ids
if ($userId == 0) $response['error'] .= "Vous devez &ecirc;tre connect&eacute; pour faire une r&eacute;servation.<br/>" ;
if (! ($userIsInstructor or $userIsAdmin)) $response['error'] .= "Vous n'avez pas le droit d'&eacute;diter l'agenda.<br>" ;

// TODO checked whether $fi is really an instructor...

if ($response['error'] == '') {
	$result = mysqli_query($mysqli_link, "insert into $table_fi_agenda(f_start, f_end, f_instructor, f_comment, 
			f_call_type, f_student_only)
		values('$start', '$end', $instructor_id, '$comment', $call_type, $student_only)") ;

	if ($result and mysqli_affected_rows($mysqli_link) == 1) {
		$item_id = mysqli_insert_id($mysqli_link) ;
		journalise($userId, 'I', "Agenda of FI ($instructor_id) done ($comment). $start => $end") ;
		$response['message'] = "Agenda mis &agrave; jour." ;
	} else {
		$response['error'] .= "Un probl&egrave;me technique s'est produit, mise &agrave; jour de l'agenda non effectu&eacute;e..." . mysqli_error($mysqli_link) . "<br/>" ;
	}
}

// Let's send the data back
header('Content-type: application/json');
print(json_encode($response)) ;

if ($response['error'] != '') {
	journalise($userId, 'E', "Error ($response[error]) while trying to add into the agenda for fi=$instructor_id ($comment). $start => $end") ;
	@mail('eric@vyncke.org', "Execution de $_SERVER[PHP_SELF]", "Le script a ete execute:
HTTP request scheme: $_SERVER[REQUEST_SCHEME]
HTTP request URI: $_SERVER[REQUEST_URI]
HTTP query: $_SERVER[QUERY_STRING]
HTTP address: $_SERVER[REMOTE_ADDR]
error: $response[error]
userid: $userId/$userName/$userFullName (FI $userIsInstructor, Admin $userIsAdmin, mécano: $userIsMechanic)
test_mode = $test_mode
pilot = $pilot[name] <$pilot[email]>
instructor_id = $instructor_id
") ;
}
?>
