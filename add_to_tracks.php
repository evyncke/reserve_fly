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

// Added to debug a few things
// Notably the INSERT IGNORE for duplicate kets
ini_set('display_errors', 1) ; // extensive error reporting for debugging

include_once 'dbi.php' ;

// TODO add some authentication...

$icao24 = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['icao24'])) ;
if ($icao24 == 'None') $icao24 = "" ;
if (isset($_REQUEST['tail_number']))
	$tail_number = mysqli_real_escape_string($mysqli_link, str_replace('-', '', trim($_REQUEST['tail_number']))) ;
else
	$tail_number = $icao24 ;
$daytime = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['daytime'])) ;
$longitude = floatval(mysqli_real_escape_string($mysqli_link, trim($_REQUEST['longitude']))) ;
$latitude = floatval(mysqli_real_escape_string($mysqli_link, trim($_REQUEST['latitude']))) ;
$altitude = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['altitude'])) ;
if ($altitude == 'None') $altitude = "NULL" ;
$velocity = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['velocity'])) ;
if ($velocity == 'None') $velocity = "NULL" ;
$track = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['track'])) ;
if ($track == '' or $track == 'None') $track = "NULL" ;
$squawk = (isset($_REQUEST['squawk'])) ? mysqli_real_escape_string($mysqli_link, trim($_REQUEST['squawk'])) : '' ;
$sensor = (isset($_REQUEST['sensor'])) ? mysqli_real_escape_string($mysqli_link, trim($_REQUEST['sensor'])) : '' ;
$source = (isset($_REQUEST['source'])) ? mysqli_real_escape_string($mysqli_link, trim($_REQUEST['source'])) : '' ;

if (isset($_REQUEST['latest']) and $_REQUEST['latest'] == 'yes') {
	$rc = mysqli_query($mysqli_link, "UPDATE $table_planes SET last_seen = '$daytime', last_longitude = $longitude, last_latitude = $latitude
			WHERE icao24 = '$icao24'") ;
	if ($rc == 0)
		journalise(0, 'E', "Cannot update latest position for $icao24 (RC=" . mysqli_errno($mysqli_link) . "): " . mysqli_error($mysqli_link)) ; 
	exit(0) ;
}

if (! isset($_REQUEST['local']) or $_REQUEST['local'] != 'yes') {
	$rc = mysqli_query($mysqli_link, "INSERT IGNORE INTO $table_tracks (t_icao24, t_time, t_longitude, t_latitude, t_altitude, t_velocity, t_squawk, t_sensor, t_source)
			VALUES('$icao24', '$daytime', $longitude, $latitude, $altitude, $velocity, '$squawk', $sensor, '$source')") ;
	if ($rc == 0 and mysqli_errno($mysqli_link) != 1062) # Ignore duplicate entries
		journalise(0, 'E', "Cannot insert track for $icao24 (RC=" . mysqli_errno($mysqli_link) . "): " . mysqli_error($mysqli_link)) ; 
}

// If flight is near the default airport, then add the track to $table_local_tracks

if (abs($longitude - $apt_longitude) <= $local_longitude_bound*2.0 and abs($latitude - $apt_latitude) <= $local_latitude_bound*2.0 and $altitude <= $local_altimeter_bound) {
	$rc = mysqli_query($mysqli_link, "INSERT IGNORE INTO $table_local_tracks (lt_timestamp, lt_longitude, lt_latitude, lt_altitude, lt_velocity, lt_track, lt_icao24, lt_tail_number, lt_source)
		VALUES('$daytime', $longitude, $latitude, $altitude, $velocity, $track, '$icao24', '$tail_number', '$source')") ;
	if ($rc == 0 and mysqli_errno($mysqli_link) != 1062) # Ignore duplicate entries
		journalise(0, 'E', "Cannot insert local track for $icao24/$tail_number (RC=" . mysqli_errno($mysqli_link) . "): " . mysqli_error($mysqli_link)) ; 
} else if (in_array($tail_number, $tracked_planes)) { // Mainly for rallye Air Spa
	$rc = mysqli_query($mysqli_link, "INSERT IGNORE INTO $table_local_tracks (lt_timestamp, lt_longitude, lt_latitude, lt_altitude, lt_velocity, lt_track, lt_icao24, lt_tail_number, lt_source)
		VALUES('$daytime', $longitude, $latitude, $altitude, $velocity, $track, '$icao24', '$tail_number', '$source')") ;
	if ($rc == 0 and mysqli_errno($mysqli_link) != 1062) # Ignore duplicate entries
		journalise(0, 'E', "Cannot insert local track for tracked $icao24/$tail_number (RC=" . mysqli_errno($mysqli_link) . "): " . mysqli_error($mysqli_link)) ; 
}
?>
