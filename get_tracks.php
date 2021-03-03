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
$tracks = array() ;

// Parameter sanitization
$plane = mysqli_real_escape_string($mysqli_link, urldecode(trim($_REQUEST['plane']))) ;
$since = mysqli_real_escape_string($mysqli_link, urldecode(trim($_REQUEST['since']))) ;

// SQL filters
$sql_filter  = array() ;
if ($plane) $sql_filters[] = "id = '$plane'" ;
if ($since)
	$sql_filters[] = "t_time >= '$since'" ;
else
	$sql_filters[] = "t_time >= DATE_SUB(SYSDATE(), INTERVAL 24 HOUR)";

// TODO check whether user could select the source of data ? AND t_source = 'FA-evyncke' or 'FlightAware' ?
$sql = "SELECT *
	FROM $table_planes JOIN $table_tracks ON t_icao24 = icao24
	WHERE " . implode(' AND ', $sql_filters) . "
	ORDER BY id, t_time
	" ;
// $tracks['sql'] = $sql ;

$result = mysqli_query($mysqli_link, $sql) or die("Erreur systeme a propos de l'access aux traces: " . mysqli_error($mysqli_link)) ;
$current_plane = '' ;
$current_track = array() ;
while ($row = mysqli_fetch_array($result)) {
	$plane = strtoupper($row['id']) ;
	if ($plane == $current_plane)
		$current_track[] = [$row['t_longitude'], $row['t_latitude']] ;
	else {
		if ($current_plane != '') {
			$tracks[$current_plane] = $current_track ;
		} 
		$current_plane = $plane ;
		$current_track = array() ;
	}
}

if ($current_plane != '') {
	$tracks[$current_plane] = $current_track ;
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
