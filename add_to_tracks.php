<?php

include_once 'dbi.php' ;

// TODO add some authentication...

$icao24 = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['icao24'])) ;
$daytime = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['daytime'])) ;
$longitude = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['longitude'])) ;
$latitude = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['latitude'])) ;
$altitude = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['altitude'])) ;
$velocity = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['velocity'])) ;
$squawk = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['squawk'])) ;
$sensor = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['sensor'])) ;
$source = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['source'])) ;
mysqli_query($mysqli_link, "INSERT INTO rapcs_tracks (t_icao24, t_time, t_longitude, t_latitude, t_altitude, t_velocity, t_squawk, t_sensor, t_source)
		VALUES('$icao24', '$daytime', $longitude, $latitude, $altitude, $velocity, $squawk, $sensor, '$source')")
		or journalise(0, 'E', "Cannot insert track for $icao: " . mysqli_error($mysqli_link)) ; 
?>