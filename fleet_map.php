<?php
/*
   Copyright 2014-2021 Eric Vyncke

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


if ($userId <= 0 && (!isset($_REQUEST['auth'])))
	die("Vous devez &ecirc;tre connect&eacute; pour visualiser les vols de la journée.") ;
	
if (isset($_REQUEST['auth']))
	if ($_REQUEST['auth'] != md5($_REQUEST['pilot'] . $_REQUEST['period'] . $shared_secret))
		die("Vous n'&ecric;tes pas autoris&eacute;.") ;

journalise($userId, 'I', "Fleet map displayed") ;

/* Let's retrieve the default airport coordinates */
$result = mysqli_query($mysqli_link, "select * from $table_airports where a_code = '$default_airport'") or die("Erreur systeme a propos de l'accès à l'aéroport: " . mysqli_error($mysqli_link)) ;
$row = mysqli_fetch_array($result) ;
if ($row) {
	$default_longitude = $row['a_longitude'] ;
	$default_latitude = $row['a_latitude'] ;
} else {
	$default_longitude = 5 ;
	$default_latitude = 50.5 ;
}
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="log.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<!-- Load the MAP BOX scripts & CSS -->
<script src='https://api.mapbox.com/mapbox-gl-js/v0.42.0/mapbox-gl.js'></script>
<link href='https://api.mapbox.com/mapbox-gl-js/v0.42.0/mapbox-gl.css' rel='stylesheet' />
<title>Vols de la flotte ces dernières 24 heures</title>
<script src="arc.js"></script> <!-- GreatCircles for geodesic lines -->
<script>
var
	// preset Javascript constant fill with the right data from db.php PHP variables
	userFullName = '<?=$userFullName?>',
	userName = '<?=$userName?>',
	userId = <?=$userId?>,
	userIsPilot = <?=($userIsPilot)? 'true' : 'false'?>,
	userIsAdmin = <?=($userIsAdmin)? 'true' : 'false'?>,
	userIsInstructor = <?=($userIsInstructor)? 'true' : 'false'?>,
	userIsMechanic = <?=($userIsMechanic)? 'true' : 'false'?> ;

// Some global variables for the mapBox
var map ;
var flightLayer = {
	id : 'flights',
	type : 'line', 
	paint : {
		// 'line-color' : '#F88',
		// Use a get expression (https://docs.mapbox.com/mapbox-gl-js/style-spec/#expressions-get)
		// to set the line-color to a feature property value.
		'line-color': ['get', 'color'],
		'line-width' : 2
	},
	source : {
		type : 'geojson',
		data : {
			type : 'FeatureCollection',
			features : {}
		}
	}
} ;

// The flights coordinates
var flightPoints = [
<?php
// TODO check whether user could select the source of data ? AND t_source = 'FA-evyncke' or 'FlightAware' ?
$sql = "SELECT *
	FROM $table_planes JOIN $table_tracks ON t_icao24 = icao24
	WHERE t_time >= DATE_SUB(SYSDATE(), INTERVAL 24 HOUR) 
	ORDER BY id, t_time
	" ;

$result = mysqli_query($mysqli_link, $sql) or die("Erreur systeme a propos de l'access aux traces: " . mysqli_error($mysqli_link)) ;
$first = TRUE ;
while ($row = mysqli_fetch_array($result)) {
	if ($first)
		$first = FALSE ;
	else
		print(",\n") ;
	$plane = strtoupper($row['id']) ;
	print("['$plane', $row[t_longitude], $row[t_latitude]]") ;
}
?>
] ;

var flightFeatureCollection = [] ;

var trackColors = [ '#33C9EB', // blue, 
	'#F7455D', // red
'#2c7fb8',
'#253494',
'#fed976',
'#feb24c',
'#ffffcc',
'#a1dab4',
] ;

function insertTrackPoints () {
	var plane = 0, currentId = '' ;
	var currentFeature ;

	flightFeatureCollection = [] ;
	for (var pointIndex = 0; pointIndex < flightPoints.length; pointIndex++) {
		if (currentId != flightPoints[pointIndex][0]) {
			if (typeof currentFeature != 'undefined') // Let's add the previous place to the list of features
				flightFeatureCollection.push(currentFeature) ;
			currentFeature = {type : 'Feature',
				properties : {title : '',comment : '', color: ''},
				geometry : {type : 'LineString', coordinates : [] } } ;
			currentFeature.type = 'Feature' ;
			currentFeature.properties.title = flightPoints[pointIndex][0] ;
			currentFeature.properties.comment = flightPoints[pointIndex][0] ;
			currentFeature.properties.color = trackColors[plane] ;
			currentFeature.geometry.type = 'LineString' ;
			currentFeature.geometry.coordinates = [] ;
			currentId = flightPoints[pointIndex][0] ;
			plane = plane + 1 ;
		}
		currentFeature.geometry.coordinates.push([flightPoints[pointIndex][1], flightPoints[pointIndex][2]]) ;
	}
	if (typeof currentFeature != 'undefined') // Let's add the previous place to the list of features
		flightFeatureCollection.push(currentFeature) ;
}

function mapAddLayers() {
	// Display the flights
	flightLayer.source.data.features = flightFeatureCollection ;
	map.addLayer(flightLayer) ;
	// Change the cursor to a pointer when the it enters a feature in the 'airports' layer.
	map.on('mouseenter', 'flights', function (e) {
//		map.getCanvas().style.cursor = 'pointer';
		console.log(document.getElementById('flightInfo')) ;
		document.getElementById('flightInfo').innerHTML = e.features[0].properties.comment ;
		// e.originalEvent.Client[XY] e.originalEvent.offset[XY](== e.point.[xy])
		// top & left are absolute within browser window
		document.getElementById('flightInfo').style.left = ' ' + (20 + e.originalEvent.clientX) + 'px'  ;
		document.getElementById('flightInfo').style.top = ' ' + e.originalEvent.clientY + 'px'  ;
		document.getElementById('flightInfo').style.display = 'block' ;
		document.getElementById('flightInfo').style.zIndex = '10' ;
	});
	// Change it back to a pointer when it leaves.
	map.on('mouseleave', 'flights', function (e) {
//		map.getCanvas().style.cursor = '';
		document.getElementById('flightInfo').style.display = 'none' ;
	});
}

function init(longitude, latitude) {
	mapboxgl.accessToken = '<?=$mapbox_token?>';
	map = new mapboxgl.Map({
	    container: 'map', // container id
	    style: 'mapbox://styles/mapbox/outdoors-v10', // stylesheet location
	    center: [longitude, latitude], // starting position [lng, lat]
	    zoom: 7 // starting zoom
	});

	// Add zoom and rotation controls to the map.
	map.addControl(new mapboxgl.NavigationControl());

	// Build the track points
	insertTrackPoints() ;
	// Add the flights layers
	map.on('load', mapAddLayers) ;
}
</script>
<!-- Matomo -->
<script type="text/javascript">
  var _paq = window._paq = window._paq || [];
  /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
   _paq.push(['setUserId', '<?=$userName?>']);
  _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
  _paq.push(["setDomains", ["*.spa-aviation.be","*.ebsp.be","*.m.ebsp.be","*.m.spa-aviation.be","*.resa.spa-aviation.be"]]);
  _paq.push(['enableHeartBeatTimer']);
  _paq.push(['setCustomVariable', 1, "userID", <?=$userId?>, "visit"]);
  _paq.push(["setCookieDomain", "*.spa-aviation.be"]);
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="//analytics.vyncke.org/";
    _paq.push(['setTrackerUrl', u+'matomo.php']);
    _paq.push(['setSiteId', '5']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Code -->
</head>
<body onload="init(<?=$default_longitude?>, <?=$default_latitude?>);">
<center><h2>Vols de la flotte ces dernières 24 heures</h2></center>

<div id='map' style='width: 100%; height: 90%;'></div>
<div id='flightInfo' style='display: none; position: absolute; margin: 0px auto; padding: 10px; text-align: left; color: black; background: white; opacity: 0.7;'></div>

<?php if (isset($_REQUEST['auth'])) print('<div style="visibility: hidden;">') ; ?>

<br/>
<?php
$version_php = date ("Y-m-d H:i:s.", filemtime('fleet_map.php')) ;
?>
<?php if (isset($_REQUEST['auth'])) print('</div>') ; ?>
<hr>
<div class="copyright">R&eacute;alisation: Eric Vyncke, mars 2021, pour RAPCS, Royal A&eacute;ro Para Club de Spa, ASBL<br>
Versions: PHP=<?=$version_php?></div>
</body>
</html>
