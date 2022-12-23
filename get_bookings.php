<?php
/*
   Copyright 2014-2022 Eric Vyncke

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

$error_message = '' ;
$bookings = array() ;

// Parameter sanitization
$plane = trim($_REQUEST['plane']) ;
if ($plane == '') $error_message = "Missing parameter" ;
$plane = mysqli_real_escape_string($mysqli_link, urldecode($plane)) ;
$day = $_REQUEST['day'] ;
if (!is_numeric($day) or $day < 1 or 31 < $day) $error_message = "Bien essaye... (day)" ;
$month = $_REQUEST['month'] ;
if (!is_numeric($month) or $month < 1 or 12 < $month) $error_message = "Bien essaye... (month)" ;
$year = $_REQUEST['year'] ;
if (!is_numeric($year) or $year < 2014 or 3000 < $year) $error_message = "Bien essaye...(year)" ;
$date = date_format(date_create("$year-$month-$day"), 'Y-m-d H:i:s') ; // Let's make a nice SQL format date


if ($error_message != '') {
	$bookings['errorMessage'] = $error_message ;
} else {
	// TODO sometime the booked plane is replaced on the field by another one...
	$result = mysqli_query($mysqli_link, "SELECT r_id, r_plane, r_start, r_stop, r_type, r_pilot, r_instructor, r_who, r_date, 
		CONVERT(r_comment USING UTF8) AS r_comment, r_from, r_via1, r_via2, r_to, r_duration, r_crew_wanted, r_pax_wanted,
		p.username as username, p.name as name, w.username AS username2, w.name AS name2,
		p.email as email, home_phone, work_phone, cell_phone, avatar, ressource, r.id AS plane_id,
		CONVERT_TZ(l_start, 'UTC', 'Europe/Brussels') as log_start, CONVERT_TZ(l_end, 'UTC', 'Europe/Brussels') as log_end, 
		l_from as log_from, l_to as log_to, l_id as log_id, l.l_pilot as log_pilot, l.l_plane as log_plane
		FROM $table_bookings JOIN $table_planes AS r ON r_plane = r.id JOIN $table_users AS p ON r_pilot = p.id JOIN jom_kunena_users k ON k.userid = r_pilot
		LEFT JOIN $table_logbook AS l ON l.l_booking = r_id,
		$table_users AS w, $table_person
		WHERE r_plane = '$plane' AND DATE(r_start) <= '$date' AND '$date' <= DATE(r_stop) AND
		r_who = w.id AND jom_id = p.id AND r_cancel_date IS NULL
		ORDER BY r_plane, r_start") ;
	if ($result)  {
		while ($row = mysqli_fetch_array($result)) {
			$booking = array() ;
			$booking['id'] = $row['r_id'] ;
			$booking['date'] = $date ;
			$booking['ressource'] = $row['ressource'] ;
			if ($row['ressource'] == 0) // Normal plane
				$booking['plane'] = $row['r_plane'] ; // This one is in upper-case matching the javascript case
			else
				$booking['plane'] = $row['plane_id'] ; // This one is capitalized
			$booking['start'] = str_replace('-', '/', $row['r_start']) ; // Safari javascript does not like - in dates !!!
			$booking['end'] = str_replace('-', '/', $row['r_stop']) ; // Safari javascript does not like - in dates !!!
			$booking['user'] = $row['r_pilot'] ;
			$booking['username'] = $row['username'] ;
			$booking['name'] = ($convertToUtf8 ) ? iconv( "ISO-8859-1", "UTF-8", $row['name']) : $row['name'] ;
			$booking['comment'] = nl2br($row['r_comment']) ; 
			$booking['comment'] = ($convertToUtf8 ) ? iconv( "ISO-8859-1", "UTF-8", $booking['comment']) : $booking['comment'] ;
			$booking['crew_wanted'] = $row['r_crew_wanted'] ;
			$booking['pax_wanted'] = $row['r_pax_wanted'] ;
			$booking['type'] = $row['r_type'] ;
			if ($row['r_type'] == BOOKING_CUSTOMER) { // Should not be too frequent, so, let's use another request...
				$customer_result = mysqli_query($mysqli_link, "SELECT * 
					FROM $table_flights JOIN $table_pax_role ON pr_flight = f_id AND pr_role = 'C' JOIN $table_pax ON p_id = pr_pax
					WHERE f_booking = $booking[id]")
					or journalise($userId, "E", "Cannot retrieve customer details: " . mysqli_error($mysqli_link)) ;
				$customer_row = mysqli_fetch_array($customer_result) ;
				mysqli_free_result($customer_result) ;
				if ($customer_row)
					$booking['customerName'] = db2web($customer_row['p_fname']) . ' ' . db2web($customer_row['p_lname']) ;
					$booking['customerPhone'] = "$customer_row[p_tel]" ;
					$type_vol = ($customer_row['f_type'] == 'D') ? 'découverte' : 'initiation' ;
					if ($customer_row['f_reference'] == '') {
						$prefix = ($customer_row['f_gift'] != 0) ? 'V-' : '' ;
						$type = ($customer_row['f_type'] == 'D') ? 'IF-' : 'INIT-' ;
						$flight_reference = $prefix . $type . sprintf("%03d", $customer_row['f_id']) ;
					} else
						$flight_reference = db2web($customer_row['f_reference']) ;
					$booking['comment'] = $booking['comment'] . "\n (Vol $type_vol $flight_reference pour $customer_row[p_fname] $customer_row[p_lname])." ;
			}
			if ($row['log_from'])
				$booking['from'] = $row['log_from'] ;
			else if ($row['r_from'])
				$booking['from'] = $row['r_from'] ;
			if ($row['r_via1'])
				$booking['via1'] = $row['r_via1'] ;
			if ($row['r_via2'])
				$booking['via2'] = $row['r_via2'] ;
			if ($row['log_to'])
				$booking['to'] = $row['log_to'] ;
			else if ($row['r_to'])
				$booking['to'] = $row['r_to'] ;
			$booking['duration'] = $row['r_duration'] ;
			if ($row['r_instructor']) {
				$booking['instructorId'] = $row['r_instructor']  ;
				$result_fi = mysqli_query($mysqli_link, "select u.name as name, u.email as email, home_phone, work_phone, cell_phone
					 from $table_users u left join $table_person p on u.id=p.jom_id where u.id=$row[r_instructor]") ;
				if ($result_fi) {
					$row_fi = mysqli_fetch_array($result_fi) ;
					$booking['instructorName'] = db2web($row_fi['name']) ;
					if ($userId > 0) {
						$booking['instructorEmail'] = $row_fi['email'] ;
						$booking['instructorWorkPhone'] = $row_fi['work_phone'] ;
						$booking['instructorCellPhone'] = $row_fi['cell_phone'] ;
						$booking['instructorHomePhone'] = $row_fi['home_phone'] ;
// TODO fetch avatar from kunena?
//						$booking['instructorAvatar'] = $row_fi['avatar'] ;
					}
					mysqli_free_result($result_fi) ;
				} else
					$bookings['errorMessage'] = "Impossible de lire les details pour $row[r_instructor] : " . mysqli_error($mysqli_link) ;
			} else {
				$booking['instructorId'] = -1 ;
				$booking['instructorName'] = 'solo' ;
			}
			$booking['bookedById'] = $row['r_who'] ;
			$booking['bookedByUsername'] = $row['username2'] ;
			$booking['bookedByName'] = db2web($row['name2']) ;
			$booking['bookedDate'] = str_replace('-', '/', $row['r_date']) ;  // Safari javascript does not like - in dates !!!
			if ($userId > 0) {
				$booking['email'] = $row['email'] ;
				$booking['gravatar'] = md5(strtolower(trim($row['email']))) ; // Hash for gravatar
				$booking['home_phone'] = $row['home_phone'] ;
				$booking['work_phone'] = $row['work_phone'] ;
				$booking['cell_phone'] = $row['cell_phone'] ;
				if (is_file("$_SERVER[DOCUMENT_ROOT]/$avatar_root_resized_directory/$row[avatar]"))
					$booking['avatar'] = $avatar_root_resized_uri . '/' . $row['avatar'] ;
				elseif (is_file("$_SERVER[DOCUMENT_ROOT]/$avatar_root_directory/$row[avatar]"))
					$booking['avatar'] = $avatar_root_uri . '/' . $row['avatar'] ;
			}
			// Now the logbook entries that may be before or after the current date when booking is for multiple days...
			if ($row['log_id']) {
				// If logbook entry ends before the current day, then don't specify the logbook entry but let's keep the normal booking dates
				if ($row['log_end'] >= $date) {
					// TODO ? Need to convert date time to local timezone...
					$booking['log_start'] = str_replace('-', '/', $row['log_start']) ;  // Safari javascript does not like - in dates !!!
					$booking['log_end'] = str_replace('-', '/', $row['log_end']) ;  // Safari javascript does not like - in dates !!!
					$booking['log_id'] = $row['log_id'] ;
					$booking['log_pilot'] = $row['log_pilot'] ;
					$booking['log_plane'] = $row['log_plane'] ;
				}
			}
			// To allow asynchronous AJAX calls, we need to pass back an argument...
			if ($_REQUEST['arg'] != '') $booking['arg'] = $_REQUEST['arg'] ;
			// Be paranoid and prevent XSS
			foreach($booking as $key => $value)
				$booking[$key] = htmlspecialchars($value) ;
			$bookings[] = $booking ;
		}
	} else
		$bookings['errorMessage'] =  "Cannot read bookings: " . mysqli_error($mysqli_link);
}
// Let's send the data back
@header('Content-type: application/json');
$json_encoded = json_encode($bookings) ;
if ($json_encoded === FALSE) {
	journalise($userId, 'E', "Cannot JSON_ENCODE(), error code: " . json_last_error_msg()) ;
	print("{'errorMessage' : 'cannot json_encode(): " . json_last_error_msg() . "'}") ;
} else
	print($json_encoded) ;
?>
