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

function computeGeodesicLine(coordinates) {
	var generator = new arc.GreatCircle({x: coordinates[0][0], y : coordinates[0][1]}, {x: coordinates[1][0], y : coordinates[1][1]}) ;
	var line = generator.Arc(10) ;
	return line.geometries[0].coords ;
}

function computeGeodesicFlights() {
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
}

function mymapSelectChanged() {
    // TODO replace PHP code
//	window.location.href = '<?=$_SERVER['PHP_SELF']?>?pilot=' + document.getElementById('pilotSelect').value + '&period=' + document.getElementById('periodSelect').value ;
	window.location.href = 'mobile_mymap.php?user=' + document.getElementById('pilotSelect').value + '&period=' + document.getElementById('periodSelect').value ;
}

function initMyMap(longitude, latitude, pilot, period, mapbox_token) {
	var pilotSelect = document.getElementById('pilotSelect') ;
	if (false) {
	// Initiliaze pilotSelect from member.js
    for (var member = 0; member < members.length; member++) {
		var option = document.createElement("option");
		option.text = members[member].name ;
		option.value = members[member].id ;
		document.getElementById('mymapPilotSelect').add(option) ;
	}
	} // false
	if (pilot == 'all') 
		pilotSelect.value = 'all' ;
	else
		pilotSelect.value = pilot ;
	var periodSelect = document.getElementById('periodSelect') ;
	if (periodSelect) periodSelect.value = period ;
	mapboxgl.accessToken = mapbox_token;
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