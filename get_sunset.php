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

// Parameter sanitization
$day = $_REQUEST['day'] ;
if (!is_numeric($day) or $day < 1 or 31 < $day) die("Bien essaye...") ;
$month = $_REQUEST['month'] ;
if (!is_numeric($month) or $month < 1 or 12 < $month) die("Bien essaye...") ;
$year = $_REQUEST['year'] ;
if (!is_numeric($year) or $year < 2014 or 3000 < $year) die("Bien essaye...") ;
$date = "$year-$month-$day" ;

$response = array() ;

$response['aero_sunrise'] = date_sunrise(time(), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude) - 30 * 60 ;
$response['sunrise'] = date_sunrise(time(), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, $zenith, $gmt_offset) ;
$response['sunset'] = date_sunset(time(), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, $zenith, $gmt_offset) ;
$response['aero_sunset'] = date_sunset(time(), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude) + 30 * 60;

// Let's send the data back
header('Content-type: application/json');
print(json_encode($response)) ;
?>
