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
		'line-width' : 4,
	},
	source : {
		type : 'geojson',
//		attribution: 'FlightAware',
		data : {
			type : 'FeatureCollection',
			features : {}
		}
	}
} ;

var locationLayer = {
	id : 'locations',
	type: 'symbol',
//	type : 'circle', 
//	paint : {  // For line & circle
		// Use a get expression (https://docs.mapbox.com/mapbox-gl-js/style-spec/#expressions-get)
		// to set the line-color to a feature property value.
//		'circle-radius' : 20,
//		'circle-color': '#B42222',
//		visibility: 'visible',
		// 'circle-color': ['get', 'color']
//	},
	source : {
		type : 'geojson',
		data : {
			type : 'FeatureCollection',
			features : {}
		}
	},
//	layout: { // Only applicable to type: symbol
//		"icon-image": "{icon}-15", // used when type: symbol
//		"text-field": "{title}",
//		"text-font": ["Open Sans Semibold", "Arial Unicode MS Bold"],
//		"text-offset": [0, 0.6],
//		"text-anchor": "top"
//	}
} ;

var flightFeatureCollection = [] ;
var locationFeatureCollection = [] ;

var trackColors = [ '#33C9EB', // blue, 
'#F7455D', // red
'#2c7fb8',
'#253494',
'#fed976',
'#feb24c',
'#ffffcc',
'#a1dab4',
] ;

function insertTrackPoints (flights) {
	var planeCount = 0 , currentId = '' ;
	var currentFeature ;

	flightFeatureCollection = [] ;
	for (var flight in flights) {
		console.log('Top level of the loop for ' + flight) ;
		if (flight == 'sql') continue ;
		if (flight == 'error') {
			console.log(flights[error]) ;
			continue ;
		}
		thisFlight = flights[flight] ;
		// TODO add time of the first point in the comment
		currentFeature = {type : 'Feature',
			properties : {title : '',comment : '', color: ''},
			geometry : {type : 'LineString', coordinates : [] } } ;
		currentFeature.properties.title = flight ;
		currentFeature.properties.comment = "Plane: " + thisFlight.plane + '</br>First seen: ' + thisFlight.first + ' UTC</br>Last seen: ' + thisFlight.last + ' UTC';
		currentFeature.properties.color = trackColors[planeCount++] ;
			currentFeature.geometry.coordinates = [] ;

		thisTrack = thisFlight.track ;
		for (trackPosition in thisTrack) {
			currentFeature.geometry.coordinates.push([parseFloat(thisTrack[trackPosition][0]), parseFloat(thisTrack[trackPosition][1])]) ;
		}
		// If there is only one point, change type to a marker
		if (currentFeature.geometry.coordinates.length == 1) {
			console.log("Only one coordinate, changing to marker") ;
			currentFeature.geometry.type = 'Point' ;
			currentFeature.properties.icon = 'airfield' ;
			currentFeature.properties['marker-symbol'] = 'airfield' ;
			currentFeature.properties['marker-size'] = 'large' ;
			locationFeatureCollection.push(currentFeature) ;
		} else {
			// Add this flight to the collection
			flightFeatureCollection.push(currentFeature) ;
		}
	}
		
	// Add the flights layers
	map.on('load', mapAddLayers) ;
}

function getTrackPoints(ajaxURL) {
	var XHR = new XMLHttpRequest();
	XHR.onreadystatechange = function() {
		if(XHR.readyState  == 4) {
			if(XHR.status  == 200) {
				try {
					var response = eval('(' + XHR.responseText.trim() + ')') ;
				} catch(err) {
					console.log("Cannot eval: " + XHR.responseText.trim()) ;
					return ;
				}
				insertTrackPoints(response) ;
			} // == 200
		} // == 4
	} // onreadystatechange
	XHR.open("GET", ajaxURL, true) ; // Send asynchronous request
	XHR.send(null) ;
}

function mapAddLayers() {
	// Display the locations
	locationLayer.source.data.features = locationFeatureCollection ;
	console.log("locationLayer is ") ;
	console.log(locationLayer) ;
	map.addLayer(locationLayer) ;
	
	// Display the flights
	flightLayer.source.data.features = flightFeatureCollection ;
	console.log("flightLayer is ") ;
	console.log(flightLayer) ;
	map.addLayer(flightLayer) ;
	// Change the cursor to a pointer when the it enters a feature in the 'airports' layer.
	map.on('mouseenter', 'flights', function (e) {
		map.getCanvas().style.cursor = 'pointer';
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
		map.getCanvas().style.cursor = '';
		document.getElementById('flightInfo').style.display = 'none' ;
	});

}

function initFleet(longitude, latitude, mapBoxToken, ajaxURL) {
	mapboxgl.accessToken = mapBoxToken;
	map = new mapboxgl.Map({
	    container: 'map', // container id
	    style: 'mapbox://styles/mapbox/outdoors-v10', // stylesheet location
	    center: [longitude, latitude], // starting position [lng, lat]
	    zoom: 8 // starting zoom
	});

	// Add zoom and rotation controls to the map.
	map.addControl(new mapboxgl.NavigationControl());
	
	// Build the track points
	getTrackPoints(ajaxURL) ;
	
	// When run in bootstrap per https://stackoverflow.com/questions/54681826/mapbox-gl-js-canvas-not-displaying-properly-in-bootstrap-modal
	// TODO to be done only in bootstrap mode ?
	// TODO seems that $ does not work at all
//	$('#map').on('shown.bs.modal', function() {
//    	map.resize();
//	});
}