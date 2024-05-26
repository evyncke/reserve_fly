<?php
/*
   Copyright 2021-2023 Eric Vyncke

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

if ($userId == 0 and !isset($_REQUEST['kiosk'])) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}

if ($userId != 62 and ! isset($_REQUEST['kiosk'])) journalise($userId, 'I', "Fleet map displayed") ;

$latest = (isset($_REQUEST['latest'])) ? '?latest=y' : '' ;

$header_postamble = "<!-- Load the MAP BOX scripts & CSS -->
<script src='https://api.mapbox.com/mapbox-gl-js/v0.42.0/mapbox-gl.js'></script>
<link href='https://api.mapbox.com/mapbox-gl-js/v0.42.0/mapbox-gl.css' rel='stylesheet' />
<script type='text/javascript' src='fleet_map.js'></script>
" ;
$body_attributes = "onload=\"init();initFleet($apt_longitude, $apt_latitude, '$mapbox_token', 'get_tracks.php$latest');\"" ;

require_once 'mobile_header5.php' ;
?> 
<div class="container-fluid">

<div class="page-header">
<h2><?=($latest)? "Dernières positions connues de nos avions" :"Vols de nos avions ces dernières 24 heures"?></h2>
</div> <!-- row -->

<div class="row">
<div id='mapContainer' style='position: relative;'>
	<div id='map' style='width: 100%; height: 800px;'></div>
	<div id='flightLegend' style='display: block; position: relative; right: 5px; margin: 0px auto; padding: 10px; text-align: left; color: black; background: white; opacity: 0.8;'></div>
</div> <!-- mapContainer -->

<div id='flightInfo' style='display: none; position: absolute; margin: 0px auto; padding: 10px; text-align: left; color: black; background: white; opacity: 0.7;'></div>

</div><!-- row -->

</div> <!-- container-->

</body>
</html>