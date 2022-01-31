<?php
/*
   Copyright 2014-2021 Eric Vyncke

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
$log = '' ;
$tracks = array() ;

// Parameter sanitization
$plane = mysqli_real_escape_string($mysqli_link, urldecode(trim($_REQUEST['plane']))) ;
$since = mysqli_real_escape_string($mysqli_link, urldecode(trim($_REQUEST['since']))) ;
$latest = mysqli_real_escape_string($mysqli_link, urldecode(trim($_REQUEST['latest']))) ;

function pilot($plane, $start, $end) {
	global $mysqli_link, $table_bookings, $table_users, $userId ;
	
	$result_pilot = mysqli_query($mysqli_link, "SELECT * 
			FROM $table_bookings JOIN $table_users AS p ON r_pilot = p.id 
			WHERE r_plane = '$plane' 
				AND r_start <= CONVERT_TZ('$start', 'UTC', 'Europe/Brussels')
				AND r_stop >= CONVERT_TZ('$end', 'UTC', 'Europe/Brussels')
				AND r_cancel_who IS NULL
			") ;
	if ($result_pilot) {
		$row_pilot = mysqli_fetch_array($result_pilot) ;
		mysqli_free_result($result_pilot) ;
		if ($row_pilot) 
			return db2web($row_pilot['name'])  ;
		else
			return '' ;
	} else {
		journalise($userId, 'E', 'Cannot find the pilot for a track: ' . mysqli_error($mysqli_link)) ;
		return '' ;
	}
}

// Handle the specific case where only the latest flight location is requested
if ($latest) {
	$sql = "SELECT *, last_seen as t_time, last_longitude as t_longitude, last_latitude as t_latitude
		FROM $table_planes" ;
} else {// SQL filters
	$sql_filter  = array() ;
	if ($plane) $sql_filters[] = "id = '$plane'" ;
	if ($since)
		$sql_filters[] = "t_time >= '$since'" ;
	else
		$sql_filters[] = "t_time >= DATE_SUB(CONVERT_TZ(SYSDATE(), 'Europe/Brussels', 'UTC'), INTERVAL 24 HOUR)";
	$sql_filters = ($sql_filters) ? "WHERE " . implode(' AND ', $sql_filters) : '' ;

	// TODO check whether user could select the source of data ? AND t_source = 'FA-evyncke' or 'FlightAware' ?

		$sql = "SELECT *, UNIX_TIMESTAMP(t_time) AS ts
			FROM $table_planes AS p JOIN $table_tracks ON t_icao24 = p.icao24
			$sql_filters
			ORDER BY id, t_time" ;
}

$tracks['sql'] = $sql ;

$result = mysqli_query($mysqli_link, $sql) ;
if (! $result) {
	journalise($userId, "E", "Erreur systeme a propos de l'access aux traces: " . mysqli_error($mysqli_link) . " $sql") ;
	$tracks['error'] = mysqli_error($mysqli_link) ;
}

$current_plane = '' ;
$current_track = array() ;
$current_ts = -1 ;
$log .= "starting, " ;
while ($row = mysqli_fetch_array($result)) {
	$plane = db2web(strtoupper($row['id'])) ;
	// Should the previous flight be emitted ?
	if ($plane != $current_plane or $row['ts'] > $current_ts + 60 * 15) {
		if ($current_plane != '') {
			$flight = array() ;
			$flight['plane'] = $current_plane ;
			$flight['track'] = $current_track ;
			$flight['first'] = $first_seen ;
			$flight['last'] = $last_seen ;
			$flight['pilot'] = pilot($current_plane, $first_seen, $last_seen) ;
			$tracks["$current_plane/$last_seen"] = $flight ;
			$log .= "$current_plane/$last_seen/$pilot " ;
		} 
		$current_plane = $plane ;
		$current_track = array() ;
		$first_seen = $row['t_time'] ;
	}
	$current_track[] = [$row['t_longitude'], $row['t_latitude']] ;
	$last_seen = $row['t_time'] ;
	$current_ts = $row['ts'] ;
}

$log .= 'end of loop, ' ;

if ($current_plane != '') {
		$flight = array() ;
		$flight['plane'] = $current_plane ;
		$flight['track'] = $current_track ;
		$flight['first'] = $first_seen ;
		$flight['last'] = $last_seen ;
		$flight['pilot'] = pilot($current_plane, $first_seen, $last_seen) ;
		$tracks["$current_plane/$last_seen"] = $flight ;
		$log .= "$current_plane/$last_seen/$pilot" ;
}

// Let's send the data back
@header('Content-type: application/json');

$json_encoded = json_encode($tracks) ;
if ($json_encoded === FALSE) {
	journalise($userId, 'E', "Cannot JSON_ENCODE(), error code: " . json_last_error_msg()) ;
	print("{'errorMessage' : 'cannot json_encode(): " . json_last_error_msg() . "', 'log' : '$log'}") ;
} else
	print($json_encoded) ;
?>
