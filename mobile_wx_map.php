<?php
/*
   Copyright 2021-2024 Eric Vyncke

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
header('Access-Control-Allow-Origin: *');


include 'dbi.php' ;

$center_lat=$apt_latitude ;
$center_long=$apt_longitude ;
$center_zoom=9 ; // Covers all of Belgium

if (isset($_REQUEST['apt'])) {
        $apt = strtoupper(mysqli_real_escape_string($mysqli_link, $_REQUEST['apt'])) ;
        if (strlen($apt) != 4) die("Invalid apt length") ;
        $result = mysqli_query($mysqli_link, "select * from airports where a_code = '$apt'")
                or die("Cannot search: " . mysqli_error($mysqli_link)) ;
        $row = mysqli_fetch_array($result) or die("Unknown airport = $apt") ;
        $center_lat = $row['a_latitude'] ;
        $center_long = $row['a_longitude'] ;
}

if (isset($_REQUEST['zoom']) and is_numeric($_REQUEST['zoom']))
        $center_zoom = $_REQUEST['zoom'] ;
$header_postamble = '
<style type="text/css">
 html, body { height: 100%; margin: 0; padding: 0 }
 #map_canvas { height: 100%;}
.chartWithOverlay {
        position: relative;
        min-width: 700px;
        height: 100%;
        width: 100%;
}
.map_canvas {
        width: 100%;
        height: 100%;
        min-width: 700px;
        min-height: 500px;
}
</style>
<script type="text/javascript" src="//www.google.com/jsapi"></script>
<script async defer type="text/javascript" src="//maps.googleapis.com/maps/api/js?libraries=geometry,marker&key=AIzaSyAN-Kv1_frzFl4gYutO9zsMEI_goTs3h4Y"></script>
<script language=javascript>
var CenterLat = ' . $center_lat .' ;
var CenterLng = ' . $center_long . ' ;
var CenterZoom = ' . $center_zoom . ' ;
var displayAirport =  ' . ((isset($_REQUEST['disp_apt']) && strtoupper($_REQUEST['disp_apt'] != 'N')) ? 'true' : 'false') . ' ;
</script>
<script type="text/javascript" src="mobile_wx_map.js"></script> ' ;
$body_attributes = 'onload="loadWxMap();init();"' ;
require_once 'mobile_header5.php' ;
?>
<h2>Conditions météo <?=$apt?></h2>
<div class="chartWithOverlay">
        <div id="map_canvas"></div>
</div>

<em>Copyright by Eric Vyncke, 2011-2024.
Credits to Google for maps, elevation data & spherical trigonometry.
Most information also is available through <a href=https://nav.vyncke.org/ws.php>web services</a></em>
</body>
</html>