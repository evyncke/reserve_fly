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
ob_start("ob_gzhandler");

require_once 'dbi.php' ;

//sleep(1) ;
$error_message = '' ;
$agenda = array() ;
//$agenda['error_message'] = '' ;

// Parameter sanitization
$fi = trim($_REQUEST['fi']) ;
if ($fi == '') $error_message = "Missing parameter" ;
if (!is_numeric($fi)) die("Good try") ;
$day = $_REQUEST['day'] ;
if (!is_numeric($day) or $day < 1 or 31 < $day) $error_message = "Bien essaye... (day)" ;
$month = $_REQUEST['month'] ;
if (!is_numeric($month) or $month < 1 or 12 < $month) $error_message = "Bien essaye... (month)" ;
$year = $_REQUEST['year'] ;
if (!is_numeric($year) or $year < 2014 or 3000 < $year) $error_message = "Bien essaye...(year)" ;
$date = "$year-$month-$day" ;


if ($error_message != '') {
	$agenda['errorMessage'] = $error_message ;
} else {
	// Have a look in the agenda table
	$result = mysqli_query($mysqli_link, "select *, timestampdiff(minute, f_start, f_end) as duration
		from $table_fi_agenda
		where date(f_start) <= '$date' and '$date' <= date(f_end) and $fi = f_instructor
		order by duration desc") ;
	if ($result)  {
		while ($row = mysqli_fetch_array($result)) {
			$item = array() ;
			$item['item'] = $row['f_id'] ; ;
			$item['fi'] = $fi ;
			$item['start'] = str_replace('-', '/', $row['f_start']) ; // Safari javascript does not like - in dates !!!
			$item['end'] = str_replace('-', '/', $row['f_end']) ; // Safari javascript does not like - in dates !!!
			$item['duration'] = $row['duration'] ;
			$item['comment'] = $row['f_comment'] ;
			$item['callType'] = $row['f_call_type'] ;
			$item['studentOnly'] = ($row['f_student_only'] == 1) ? TRUE : FALSE ;
			// To allow asynchronous AJAX calls, we need to pass back an argument...
			if ($_REQUEST['arg'] != '') $item['arg'] = $_REQUEST['arg'] ;
			// Be paranoid and prevent XSS
			foreach($item as $key => $value)
				$item[$key] = htmlspecialchars($value) ;
			$agenda[] = $item ;
		}
	} else
		$agenda['errorMessage'] =  "Cannot read FI_agenda: " . mysqli_error($mysqli_link);
	// All plane booking where the instructor is instructor or pilot is ovbviously also an item in the agenda ;-)
	$result = mysqli_query($mysqli_link, "SELECT r_id, r_plane, r_start, r_stop, r_comment, r_pilot, r_instructor, timestampdiff(minute, r_start, r_stop) AS duration, name
		FROM $table_bookings JOIN $table_users AS p ON r_pilot = p.id
		WHERE date(r_start) <= '$date' AND '$date' <= date(r_stop) AND ($fi = r_pilot OR $fi = r_instructor) AND r_cancel_date IS NULL AND r_type != " . BOOKING_MAINTENANCE . 
		" ORDER BY r_start") ;
	if ($result)  {
		while ($row = mysqli_fetch_array($result)) {
			$item = array() ;
			$item['fi'] = $fi ;
			$item['booking'] = $row['r_id'] ;
			$item['start'] = str_replace('-', '/', $row['r_start']) ; // Safari javascript does not like - in dates !!!
			$item['end'] = str_replace('-', '/', $row['r_stop']) ; // Safari javascript does not like - in dates !!!
			$item['duration'] = $row['duration'] ;
			$item['comment'] = $row['r_plane'] ;
			$item['name'] = db2web($row['name']) ;
			// To allow asynchronous AJAX calls, we need to pass back an argument...
			if ($_REQUEST['arg'] != '') $item['arg'] = $_REQUEST['arg'] ;
			// Be paranoid and prevent XSS
			foreach($item as $key => $value)
				$item[$key] = htmlspecialchars($value) ;
			$agenda[] = $item ;
		}
	} else
		$agenda['errorMessage'] .=  "Cannot read bookings: " . mysqli_error($mysqli_link);
}
// Let's send the data back
@header('Content-type: application/json');
$json_encoded = json_encode($agenda) ;
if ($json_encoded === FALSE) {
	journalise($userId, 'E', "Cannot JSON_ENCODE(), error code: " . json_last_error_msg()) ;
	print("{'errorMessage' : 'cannot json_encode(): " . json_last_error_msg() . "'}") ;
} else
	print($json_encoded) ;
?>
