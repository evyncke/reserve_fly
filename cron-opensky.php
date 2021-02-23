<?php

/*
   Copyright 2014-2020 Eric Vyncke

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

// Mostly useless as OpenSky Network only logs the ADS-B out and does not do multilateration...

include_once 'dbi.php' ;
$user = 'evyncke' ;
$password = 'ebsp12475' ;

$result = mysqli_query($mysqli_link, "SELECT * FROM $table_planes WHERE icao24 IS NOT NULL")
	or journalise(0, "E", "Cannot fetch active planes: " . mysqli_error($mysqli_link)) ;
$query_paramaters = ['44ce01'] ;
while ($row = mysqli_fetch_array($result)) {
	$query_parameters[] = "icao24=$row[icao24]" ;
}
$url = "https://$user:$password@opensky-network.org/api/states/all?" . implode('&', $query_parameters) ;
// $url = "https://opensky-network.org/api/states/all?lamin=50.0&lomin=5.0&lamax=51.0&lomax=6.0" ;

$ch = curl_init() ;
curl_setopt($ch, CURLOPT_URL, $url) ;
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
curl_setopt($ch, CURLOPT_HEADER, 0); 
$output = curl_exec($ch); 
$info = curl_getinfo($ch) ;
curl_close($ch) ;

if ($info['http_code'] != 200) {
	journalise(0, 'E', "HTTP GET failed, code = $info[http_code]") ;
	var_dump($info) ;
} else {
	var_dump($output) ;
	$result = json_decode($output) ;
	var_dump($result) ;
	if ($result->states) {
		journalise(0, 'I', "States: $output") ;
		mail('eric@vyncke.org', "OpenSky has data !!!", $output) ;
		foreach ($result->states as $state) {
			$squawk = (isset($state[14]) and $state[14] != 'null' and $state[14] != '') ? "'$state[14]'" : 'NULL' ;
			$velocity = (isset($state[9]) and $state[9] != 'null' and $state[9] != '') ? $state[9] * 1.94384 : 'NULL' ; // Given in m/s
			$baro_altitude = (isset($state[7]) and $state[7] != 'null' and $state[7] != '') ? $state[7] * 3.28084 : 'NULL' ; // Given in meters and DB wants in feet
			mysqli_query($mysqli_link, "INSERT INTO rapcs_tracks (t_icao24, t_time, t_longitude, t_latitude, t_altitude, t_velocity, t_squawk, t_sensor, t_source)
				VALUES('$state[0]', FROM_UNIXTIME($state[4]), $state[5], $state[6], $state[7], $velocity, '$squawk', $state[16], 'OpenSky')")
				or journalise(0, 'E', "Cannot insert track for $state[0]: " . mysqli_error($mysqli_link)) ; 
		}
	}
}
?>