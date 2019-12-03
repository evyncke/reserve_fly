<?php
ob_start("ob_gzhandler");

require_once 'dbi.php' ;

$response = array() ;
$response['error'] = '' ; // Until now: no error :-)

// Parameter sanitization
$instructor_id = $_REQUEST['fi'] ;
if (! isset($_REQUEST['fi'])) die('Missing parameter') ;
if (!is_numeric($instructor_id)) die("Bien essaye... instructor: $instructor_id") ;
$start = mysqli_real_escape_string($mysqli_link, $_REQUEST['start']) ;
$end = mysqli_real_escape_string($mysqli_link, $_REQUEST['end']) ;
$comment = mysqli_real_escape_string($mysqli_link, $_REQUEST['comment']) ;
$item_id = trim($_REQUEST['item']) ;
if ($item_id == '') die("Missing parameter: item") ;
if (!is_numeric($item_id)) die("Bien essaye... item: $item_id") ;
$call_type = $_REQUEST['callType'] ;
if (!is_numeric($call_type)) die("Bien essaye... type: $call_type") ;
$student_only = $_REQUEST['studentOnly'] ;
if (!is_numeric($student_only)) die("Bien essaye... student: $student_only") ;

$response['item'] = $item_id ;

// TODO check that:
// - add copyright / disclaimer

$result = mysqli_query($mysqli_link, "select * from $table_fi_agenda where f_id = $item_id") ;
if ((!$result) || (mysqli_num_rows($result) == 0))
        $response['error'] .= "Cette agenda item, $item, n'existe pas, " . mysqli_error($mysqli_link) . '<br/>' ;
else {
        $item = mysqli_fetch_array($result) ;
        if (!($userIsAdmin or $userIsInstructor)) 
		$response['error'] .= "Cette agenda item, $item, ne peut être modifi&eacute; par vous ($userId), uniquement par des instructeurs ou administrateurs<br/>" ;
}

// Basic checks on dates
$start_date = new DateTime($start) ;
if (!$start_date) $response['error'] .= "$start is not a valid date<br/>" ;
$end_date = new DateTime($end) ;
if (!$end_date) $response['error'] .= "$end is not a valid date<br/>" ;
if ($end_date <= $start_date) $response['error'] .= "La fin doit &ecirc;tre apr&egrave;s le d&eacute;but: $start -> $end.<br/>" ;

if ($response['error'] == '') {
	$sql = "replace into $table_fi_agenda(f_id, f_start, f_end, f_instructor, f_comment, f_call_type, f_student_only)
		values($item_id, '$start', '$end', $instructor_id, '$comment', $call_type, $student_only)" ;
	$response['sql'] = $sql ;
	$result = mysqli_query($mysqli_link, $sql) ;

	if (mysqli_affected_rows($mysqli_link) == 2) { // a REPLACE is actually a DELETE followed by INSERT
		$response['message'] = "L'agenda du $start au $end: est modifi&eacute;" ;
		journalise($userId, 'I', "Modification of agenda item ($item_id), now $start to $end") ;
	} else
		$response['error'] .= "Un probl&egrave;me technique s'est produit... modification non effectu&eacute;e..." . mysqli_error($mysqli_link) . "<br/>" ;
}

// Let's send the data back
header('Content-type: application/json');
print(json_encode($response)) ;

if ($response['error'])
	journalise($userId, 'E', "Error ($response[error]) while modifying agenda item $$item_id") ;
?>
