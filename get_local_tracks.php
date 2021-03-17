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

if (isset($_REQUEST['mult'])) {
	$mult = floatval(mysqli_real_escape_string($mysqli_link, trim($_REQUEST['mult']))) ;
} else
	$mult = 1.0 ;
	
$error_message = '' ;
$tracks = array() ;

$sql = "SELECT *, UNIX_TIMESTAMP(lt_timestamp) AS ts
		FROM $table_local_tracks 
		WHERE lt_timestamp >= DATE_SUB(CONVERT_TZ(NOW(), 'Europe/Paris', 'UTC'), INTERVAL $local_delay MINUTE)
			AND ABS(lt_latitude - $apt_latitude) <= $local_latitude_bound * $mult
			AND ABS(lt_longitude - $apt_longitude) <= $local_longitude_bound * $mult
		ORDER BY lt_icao24, lt_timestamp" ;

$tracks['sql'] = $sql ;

$result = mysqli_query($mysqli_link, $sql) ;
if (! $result) {
	journalise($userId, "E", "Erreur systeme a propos de l'access aux traces: " . mysqli_error($mysqli_link) . " $sql") ;
	$tracks['error'] = mysqli_error($mysqli_link) ;
}

$current_plane = '' ;
$current_track = array() ;
$current_ts = -1 ;
while ($row = mysqli_fetch_array($result)) {
	$plane = strtoupper($row['lt_icao24']) ;
	// Should the previous flight be emitted ?
	if ($plane != $current_plane) {
		if ($current_plane != '') {
			$flight = array() ;
			$flight['icao24'] = $current_plane ;
			$flight['tail_number'] = $current_tail_number ;
			$flight['track'] = $current_track ;
			$flight['first'] = $first_seen ;
			$flight['last'] = $last_seen ;
			$flight['last_altitude'] = $last_altitude ;
			$flight['last_velocity'] = $last_velocity ;
			$flight['last_track'] = $last_track ;
			$tracks["$current_plane-$current_tail_number"] = $flight ;
		} 
		$current_plane = $plane ;
		$current_tail_number = $row['lt_tail_number'] ;
		$current_track = array() ;
		$first_seen = $row['lt_timestamp'] ;
	}
	$current_track[] = [$row['lt_longitude'], $row['lt_latitude']] ;
	$last_seen = $row['lt_timestamp'] ;
	$last_altitude = $row['lt_altitude'] ;
	$last_velocity = $row['lt_velocity'] ;
	$last_track = $row['lt_track'] ;
	$current_ts = $row['ts'] ;
	if ($current_tail_number == '-') $current_tail_number = $row['lt_tail_number'] ;
}

if ($current_plane != '') {
		$flight = array() ;
		$flight['icao24'] = $current_plane ;
		$flight['tail_number'] = $current_tail_number ;
		$flight['track'] = $current_track ;
		$flight['first'] = $first_seen ;
		$flight['last'] = $last_seen ;
		$flight['last_altitude'] = $last_altitude ;
		$flight['last_velocity'] = $last_velocity ;
		$flight['last_track'] = $last_track ;
		$tracks["$current_plane-$current_tail_number"] = $flight ;
}

// Let's send the data back
@header('Content-type: application/json');

$json_encoded = json_encode($tracks) ;
if ($json_encoded === FALSE) {
	journalise($userId, 'E', "Cannot JSON_ENCODE(), error code: " . json_last_error_msg()) ;
	print("{'errorMessage' : 'cannot json_encode(): " . json_last_error_msg() . "'}") ;
} else
	print($json_encoded) ;
?>
