<?php
/*
   Copyright 2014-2023 Eric Vyncke

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

if ($userId <= 0 && (!isset($_REQUEST['auth'])))
	die("Vous devez &ecirc;tre connect&eacute; pour visualiser les cartes de nos pilotes.") ;
	
if (isset($_REQUEST['auth']))
	if ($_REQUEST['auth'] != md5($_REQUEST['pilot'] . $_REQUEST['period'] . $shared_secret))
		die("Vous n'&ecric;tes pas autoris&eacute;.") ;

	
$sql_filter = [] ;

if (isset($_REQUEST['pilot']) && is_numeric($_REQUEST['pilot'])) {
	$pilot = $_REQUEST['pilot'] ;
	$result = mysqli_query($mysqli_link, "select name from $table_users where id = $pilot") 
		or die("Cannot read the pilot name " . mysqli_error($mysqli_link)) ;
	$row = mysqli_fetch_array($result) 
		or die("Unknown pilot") ;
	$pilot_name = db2web($row['name']) ;
	$sql_filter[] = "(l_pilot = $pilot or l_instructor = $pilot)" ;
} elseif (isset($_REQUEST['pilot']) && ($_REQUEST['pilot'] == 'all')) {
	$pilot =  $_REQUEST['pilot'] ;
	$pilot_name = "tous les pilotes" ;
} else {
	$pilot = $userId ;
	$pilot_name = $userFullName ;
	$sql_filter[] = "(l_pilot = $pilot or l_instructor = $pilot)" ;
}

if (isset($_REQUEST['period'])) {
	$period = $_REQUEST['period'] ;
	switch ($_REQUEST['period']) {
		case '2y': $sql_filter[] = 'l_start > date_sub(now(), interval 2 year)' ; break ;
		case '1y': $sql_filter[] = 'l_start > date_sub(now(), interval 1 year)' ; break ;
		case '3m': $sql_filter[] = 'l_start > date_sub(now(), interval 3 month)' ; break ;
		case '1m': $sql_filter[] = 'l_start > date_sub(now(), interval 1 month)' ; break ;
	}	
} else {
	$period = 'always' ; 
}

journalise($userId, 'I', "Map displayed for $pilot_name ($period)") ;

$sql_filters = implode(' and ', $sql_filter) ;
if ($sql_filters != '') $sql_filters = "where $sql_filters" ;

?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<!-- Load the MAP BOX scripts & CSS -->
<script src='https://api.mapbox.com/mapbox-gl-js/v0.42.0/mapbox-gl.js'></script>
<link href='https://api.mapbox.com/mapbox-gl-js/v0.42.0/mapbox-gl.css' rel='stylesheet' />
<title>Carte des vols de <?=$pilot_name?></title>
<script src="js/arc.js"></script> <!-- GreatCircles for geodesic lines -->
<script src="data/members.js"></script> <!--- cannot be loaded before as its initialization code use variable above... -->
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
		'line-color' : '#888',
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

var airportLayer = {
	id : 'airports',
	type : 'symbol', 
	source : {
		type : 'geojson',
		data : {
			type : 'FeatureCollection',
			features : {}
		}
	},
	layout: {
		"icon-image": "{icon}-15",
		"text-field": "{title}",
		"text-font": ["Open Sans Semibold", "Arial Unicode MS Bold"],
		"text-offset": [0, 0.6],
		"text-anchor": "top"
	}
} ;

// The flights coordinates
var flightFeatureCollection = [
<?php
$sql = "select upper(l_from) as l_from, upper(l_to) as l_to,
	f.a_name as f_name, f.a_longitude as f_longitude, f.a_latitude as f_latitude,
	t.a_name as t_name, t.a_longitude as t_longitude, t.a_latitude as t_latitude
	from $table_logbook l 
	left join $table_airports as f on l_from = f.a_code
	left join $table_airports as t on l_to = t.a_code
	$sql_filters
	" ;

$result = mysqli_query($mysqli_link, $sql) or die("Erreur systeme a propos de l'access au carnet de route: " . mysqli_error($mysqli_link)) ;
$first = TRUE ;
$featured_airports = array() ;
while ($row = mysqli_fetch_array($result)) {
	$l_from = $row['l_from'] ;
	if (! array_key_exists($l_from, $featured_airports) && isset($row['f_longitude'])) {
		$featured_airports[$l_from] = array('longitude' => $row['f_longitude'], 'latitude' => $row['f_latitude'], 'name' => $row['f_name'], 'takeoff' => 1, 'landing' => 0) ;
	} else
		if (isset($row['f_longitude'])) 
			$featured_airports[$l_from]['takeoff']++ ;
	$l_to = $row['l_to'] ;
	if (! array_key_exists($l_to, $featured_airports) && isset($row['t_longitude'])) {
		$featured_airports[$l_to] = array('longitude' => $row['t_longitude'], 'latitude' => $row['t_latitude'], 'name' => $row['t_name'], 'takeoff' => 0, 'landing' => 1) ;
	} else
		if (isset($row['t_longitude'])) 
			$featured_airports[$l_to]['landing']++ ;
	if ($row['l_to'] == $row['l_from']) continue ; // No need to draw a line when takeoff landing are in the same airfield
	if ($row['f_longitude'] == '' or $row['t_longitude'] == '') continue ; // Some log entries do not have any valid coordinates
	print("\t// from $row[l_from] ($row[f_name]) to $row[l_to] ($row[t_name])\n") ;
	if ($first)
		$first = FALSE ;
	else
		print(",\n") ;
	print("\t{ type : 'Feature',
		properties : {},
		geometry : {
			type : 'LineString',
			coordinates : [[$row[f_longitude], $row[f_latitude] ], [$row[t_longitude], $row[t_latitude] ]]
		}
	}\n") ;
}
print("];\n") ;

// Now, let create another collection for the airports...
print("var airportFeatureCollection = [\n") ;
$first = TRUE ;
foreach ($featured_airports as $code => $airport) {
	if ($first)
		$first = FALSE ;
	else
		print(",\n") ;
	print("\t{ type : 'Feature',
		properties : {
			title : '$code',
			icon : 'airport',
			takeoff : $airport[takeoff],
			landing : $airport[landing],
			comment : '" . addslashes($airport['name']) . "'
		},
		geometry : {
			type : 'Point', 
			coordinates : [$airport[longitude], $airport[latitude]]
		}\n\t}") ;
}

?>

] ;

function computeGeodesicLine(coordinates) {
	var generator = new arc.GreatCircle({x: coordinates[0][0], y : coordinates[0][1]}, {x: coordinates[1][0], y : coordinates[1][1]}) ;
	var line = generator.Arc(10) ;
//	return coordinates ;
	return line.geometries[0].coords ;
}

function computeGeodesicFlights() {
//     for (var member = 0; member < members.length; member++) {
	for (var flightIndex = 0; flightIndex < flightFeatureCollection.length; flightIndex++) {
		flightFeatureCollection[flightIndex].geometry.coordinates = computeGeodesicLine(flightFeatureCollection[flightIndex].geometry.coordinates) ;
	}
}

function mapAddLayers() {
	// Display the flights
	flightLayer.source.data.features = flightFeatureCollection ;
	map.addLayer(flightLayer) ;
	// Display the airports
	airportLayer.source.data.features = airportFeatureCollection ;
	map.addLayer(airportLayer) ;
	// Change the cursor to a pointer when the it enters a feature in the 'airports' layer.
	map.on('mouseenter', 'airports', function (e) {
//		map.getCanvas().style.cursor = 'pointer';
		document.getElementById('airportInfo').innerHTML = e.features[0].properties.comment + ' (' + e.features[0].properties.title + ')<br/>' +
			e.features[0].properties.landing + ' landing(s)<br/>' + e.features[0].properties.takeoff + ' take-off(s)';
		// e.originalEvent.Client[XY] e.originalEvent.offset[XY](== e.point.[xy])
		// top & left are absolute within browser window
		document.getElementById('airportInfo').style.left = ' ' + (20 + e.originalEvent.clientX) + 'px'  ;
		document.getElementById('airportInfo').style.top = ' ' + e.originalEvent.clientY + 'px'  ;
		document.getElementById('airportInfo').style.display = 'block' ;
		document.getElementById('airportInfo').style.zIndex = '10' ;
	});
	// Change it back to a pointer when it leaves.
	map.on('mouseleave', 'airports', function (e) {
//		map.getCanvas().style.cursor = '';
		document.getElementById('airportInfo').style.display = 'none' ;
	});
	// Click on airports
//	map.on('click', 'airports', function (e) {
//        map.flyTo({center: e.features[0].geometry.coordinates});
//		console.log(e) ;
//    });
}

function selectChanged() {
	window.location.href = '<?=$_SERVER['PHP_SELF']?>?pilot=' + document.getElementById('pilotSelect').value + '&period=' + document.getElementById('periodSelect').value ;
}

function init(longitude, latitude) {
	var pilotSelect = document.getElementById('pilotSelect') ;
	// Initiliaze pilotSelect from member.js
    for (var member = 0; member < members.length; member++) {
		var option = document.createElement("option");
		option.text = members[member].name ;
		option.value = members[member].id ;
		document.getElementById('pilotSelect').add(option) ;
	}
<?php
	if ($pilot == 'all') 
		print("\tpilotSelect.value = 'all' ;\n") ;
	else
		print("\tpilotSelect.value = $pilot ;\n") ;
?>
	var periodSelect = document.getElementById('periodSelect') ;
	if (periodSelect) periodSelect.value = '<?=$period?>' ;
	mapboxgl.accessToken = '<?=$mapbox_token?>';
	map = new mapboxgl.Map({
	    container: 'map', // container id
	    style: 'mapbox://styles/mapbox/outdoors-v10', // stylesheet location
	    center: [longitude, latitude], // starting position [lng, lat]
	    zoom: 7 // starting zoom
	});

	// Add zoom and rotation controls to the map.
	map.addControl(new mapboxgl.NavigationControl());

	// Compute Geodesic lines
	computeGeodesicFlights() ;
	// Add the flights & airports layers
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
    _paq.push(['setSiteId', '8']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Code -->
</head>
<body onload="init(<?=$apt_longitude?>, <?=$apt_latitude?>);">
<center><h2>Les vols de <?=$pilot_name?> sur une carte</h2></center>

<?php if (isset($_REQUEST['auth'])) print('<div style="visibility: hidden;">') ; ?>
P&eacute;riode: <select id="periodSelect" onchange="selectChanged();">
	<option value="always">depuis toujours</option>
	<option value="2y">2 ans</option>
	<option value="1y">1 an</option>
	<option value="3m">3 mois</option>
	<option value="1m">1 mois</option>
</select>.


En tant que membre, vous pouvez voir les vols des autres pilotes: 
<select id="pilotSelect" onchange="selectChanged();">
<option value="all">Tous les pilotes</option>
</select>. A partager via ce <a href=<?=$_SERVER['PHP_SELF'] . "?pilot=$pilot&period=$period&auth=" . md5($pilot . $period . $shared_secret)?>>lien</a>.
<!--input type="button" value="Partager" onclick="javascript:document.location.href='<?=$_SERVER['PHP_SELF'] . "?pilot=$pilot&period=$period&auth=" . md5($pilot . $period . $shared_secret)?>';"-->

<?php if (isset($_REQUEST['auth'])) print('</div>') ; ?>

<div id='map' style='width: 100%; height: 90%;'></div>
<div id='airportInfo' style='display: none; position: absolute; margin: 0px auto; padding: 10px; text-align: left; color: black; background: white; opacity: 0.7;'></div>

<?php if (isset($_REQUEST['auth'])) print('<div style="visibility: hidden;">') ; ?>
<br/>
<div style="border-style: inset;background-color: AntiqueWhite;">
Sur base des donn&eacute;es que vous avez entr&eacute;es apr&egrave;s les vols dans le
carnet de route des avions (&agrave; pr&eacute;f&eacute;rer pour avoir les heures moteur) ou celles que vous avez entr&eacute;e via votre carnet de vols (&agrave; utiliser
pour des vols sur des avions hors RAPCS).
</div>
<br/>
<?php
$version_css = date ("Y-m-d H:i:s.", filemtime('css/log.css')) ;
?>
<?php if (isset($_REQUEST['auth'])) print('</div>') ; ?>
<hr>
<div class="copyright">R&eacute;alisation: Eric Vyncke, décembre 2017, pour RAPCS, Royal A&eacute;ro Para Club de Spa<br>
Versions: PHP=<?=$version_php?>, CSS=<?=$version_css?></div>
</body>
</html>
