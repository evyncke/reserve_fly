<?php

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
			$velocity = (isset($state[9]) and $state[9] != 'null' and $state[9] != '') ? $state[9] : 'NULL' ;
			mysqli_query($mysqli_link, "INSERT INTO rapcs_tracks (t_icao24, t_time, t_longitude, t_latitude, t_altitude, t_velocity, t_squawk, t_source)
				VALUES('$state[0]', FROM_UNIXTIME($state[4]), $state[5], $state[6], $state[7], $velocity, $squawk, $state[16])")
				or journalise(0, 'E', "Cannot insert track for $state[0]: " . mysqli_error($mysqli_link)) ; 
		}
	}
}
?>