<?php
/*
   Copyright 2021 Eric Vyncke

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

require_once "dbi.php" ;
require_once 'facebook.php' ;

if ($userId <= 0)
	die("Vous devez &ecirc;tre connect&eacute; pour visualiser les vols de la journée.") ;
	
if ($userId != 62) journalise($userId, 'I', "Mobile local flights map displayed") ;

$zoom_level = 10 ;
$local_longitude_bound /= 2.0 ;
$local_latitude_bound /= 2.0 ;

$header_postamble = "<!-- Load the MAP BOX scripts & CSS -->
<script src='https://api.mapbox.com/mapbox-gl-js/v0.42.0/mapbox-gl.js'></script>
<link href='https://api.mapbox.com/mapbox-gl-js/v0.42.0/mapbox-gl.css' rel='stylesheet' />
<script type='text/javascript' src='local_flights.js'></script>
" ;
$body_attributes = "onload=\"init();initLocalFlights($apt_longitude, $local_longitude_bound, $apt_latitude, $local_latitude_bound, $local_altimeter_bound, '$mapbox_token', $zoom_level, 'get_local_tracks.php?mult=0.5');\"" ;

require_once 'mobile_header.php' ;
?> 


<div class="container" style="height: 100%; width: 100%">

<div class="page-header">
<h3>Vols proches de l'aéroport ces <?=$local_delay?> dernières minutes</h3>
</div> <!-- row -->

<div class="row">
<div id='mapContainer' style='position: relative;'>
	<div id='map' style='width: 100%; height: 800px;'></div>
	<div id='flightLegend' style='display: block; position: absolute; bottom: 30px; right: 5px; margin: 0px auto; padding: 10px; text-align: left; color: black; background: white; opacity: 0.8;'></div>
</div> <!-- mapContainer -->

<div id='flightInfo' style='display: none; position: absolute; margin: 0px auto; padding: 10px; text-align: left; color: black; background: white; opacity: 0.7;'></div>

</div><!-- row -->

</div> <!-- container-->

</body>
</html>
