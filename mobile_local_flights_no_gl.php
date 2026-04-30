<?php
/*
   Copyright 2021-2025 Eric Vyncke

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

require_once "dbi.php" ;

$zoom_level = 11 ;
$local_longitude_bound /= 1.5 ;
$local_latitude_bound /= 1.5 ;

$header_postamble = "<!-- Load the MAP BOX scripts & CSS -->
<link rel='stylesheet' href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css' />
<script src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'></script>
<script src='https://cdn.jsdelivr.net/npm/leaflet-rotatedmarker@0.2.0/leaflet.rotatedMarker.min.js'></script>
<script type='text/javascript' src='js/local_flights_no_gl.js'></script>
" ;
$body_attributes = "onload=\"init();initLocalFlights($apt_longitude, $local_longitude_bound, $apt_latitude, $local_latitude_bound, $local_altimeter_bound, '$mapbox_token', $zoom_level, 'get_local_tracks.php?mult=0.5');\"" ;
# HTTP/2 push of some JS scripts via header()
$additional_preload = '</resa/js/local_flights_no_gl.js>;rel=preload;as=script' ;
require_once 'mobile_header5.php' ;
?> 

<div class="container-fluid">

<div class="page-header">
<h2>Vols proches de l'aéroport ces <?=$local_delay?> dernières minutes</h2>
</div> <!-- row -->

<div class="row">
<div id='mapContainer' style='position: relative;'>
	<div id='map' style='width: 100%; height: 90vh;'></div>
	<div id='flightLegend' style='display: block; position: absolute; bottom: 30px; right: 5px; z-index: 1000; margin: 0px auto; padding: 10px; text-align: left; color: black; background: white; opacity: 0.8;'></div>
</div> <!-- mapContainer -->

<div id='flightInfo' style='display: none; position: absolute; margin: 0px auto; padding: 10px; text-align: left; color: black; background: white; opacity: 0.7;'></div>

</div><!-- row -->

<div class="row text-center small">
Réalisation: Eric Vyncke, mars 2021-avril 2026, pour RAPCS, Royal Aéro Para Club de Spa, ASBL. <b>Ne pas utiliser comme outil en vol</b><br/>
Données via Flight Aware (avec maximum 15 minutes de délai), via quelques récepteurs ADS-B / MLAT (avec maximum 10 secondes de délai), via SafeSky, et via glidernet.org (planeurs FLARM et intégration SafeSky et un délai d'une minute).
</div><!-- row -->

</div> <!-- container-->

</body>
</html>
