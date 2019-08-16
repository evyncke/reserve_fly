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

require_once 'db.php' ;

// returns different times as Unix timestamp in UTC time zone

// Parameter sanitization
$day = $_REQUEST['day'] ;
if (!is_numeric($day) or $day < 1 or 31 < $day) die("Bien essaye...") ;
$month = $_REQUEST['month'] ;
if (!is_numeric($month) or $month < 1 or 12 < $month) die("Bien essaye...") ;
$year = $_REQUEST['year'] ;
if (!is_numeric($year) or $year < 2014 or 3000 < $year) die("Bien essaye...") ;
$date = "$year-$month-$day" ;
$timestamp = $date ;
$timestamp = strtotime("$year-$month-$day") ;

$response = array() ;

$response['comment'] = "latitude = $latitude, longitude = $longitude, zenith=$zenith" ;

$response['sunrise'] = date_sunrise($timestamp, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, $zenith) ;
$response['sunset'] = date_sunset($timestamp, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, $zenith) ;
$response['aero_sunrise'] = $response['sunrise'] - 30 * 60;
$response['aero_sunset'] = $response['sunset'] + 30 * 60;
$response['airport_open'] = airport_opening_local_time($year, $month, $day) ;
$response['airport_close'] = airport_closing_local_time($year, $month, $day) ;

$response['timezone'] = date('e', $timestamp) ;
$response['timezone_offset'] = date('Z', $timestamp) ;

// Let's send the data back
header('Content-type: application/json');
// This data is cacheable of course as it nearly never changes
header('Cache-Control: public, max-age=86400') ; 
header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + 24 * (60 * 60))); 
print(json_encode($response)) ;
?>
