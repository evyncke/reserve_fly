// Some global variables for the mapBox
var map ;

var boxLayer = {
	id : 'box',
	type : 'line', 
	paint : {
		'line-color': 'black',
		'line-width' : 3,
	},
	source : {
		type : 'geojson',
		data : {
			type : 'FeatureCollection',
			features: [{
				type : 'Feature',
				properties : {title : 'Local Zone'},
				geometry : {
					type : 'LineString', 
					coordinates : []
				} 
			}]

		}
	}
} ;

var flightLayer = {
	id : 'flights',
	type : 'line', 
	paint : {
		// 'line-color' : '#F88',
		// Use a get expression (https://docs.mapbox.com/mapbox-gl-js/style-spec/#expressions-get)
		// to set the line-color to a feature property value.
		'line-color': ['get', 'color'],
		'line-width' : 2,
	},
	source : {
		type : 'geojson',
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
//		"icon-color": ['get', 'color'],
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
	layout: { // Only applicable to type: symbol
		"icon-image": "{icon}-15", // used when type: symbol
		"text-field": "{title}",
		"text-font": ["Open Sans Semibold", "Arial Unicode MS Bold"],
		"text-offset": [0, 0.6],
		"text-anchor": "top",
	}
} ;

var flightFeatureCollection = [] ;
var locationFeatureCollection = [] ;

var trackColors = [ '#33C9EB', // blue, 
'MediumBlue', // red
'cyan',
'darkblue',
'crimson',
'black',
'orange',
'brown',
'chartreuse',
'DeepPink',
'LawnGreen',
'LightCoral',
'Magenta',
] ;

function insertTrackPoints (flights) {
	var planeCount = 0 , currentId = '' ;
	var currentFeature ;
	var legendDiv = document.getElementById('flightLegend') ;

	flightFeatureCollection = [] ;
//	if (typeof legendDiv != 'undefined') {
	if (legendDiv) {
		legendDiv.innerHTML = '' ;
		// TODO position the div
	}
	for (var flight in flights) {
		console.log('Top level of the loop for ' + flight) ;
		if (flight == 'sql') continue ;
		if (flight == 'error') {
			console.log(flights[error]) ;
			continue ;
		}
		thisFlight = flights[flight] ;
//		if (typeof legendDiv != 'undefined') {
		if (legendDiv) {
			legendDiv.innerHTML += '<span class="glyphicon glyphicon-plane" style="color:' + trackColors[planeCount] + '"></span> ' + thisFlight.tail_number + '<br/>' ;
		}
		// TODO add time of the first point in the comment
		currentFeature = {type : 'Feature',
			properties : {title : '',comment : '', color: ''},
			geometry : {type : 'LineString', coordinates : [] } } ;
		currentFeature.properties.title = flight ;
		currentFeature.properties.comment = "Plane: " + thisFlight.tail_number + '</br>First seen: ' + thisFlight.first + ' UTC</br>Last seen: ' + thisFlight.last + ' UTC';
		currentFeature.properties.color = trackColors[planeCount++] ;
		currentFeature.geometry.coordinates = [] ;

		thisTrack = thisFlight.track ;
		for (trackPosition in thisTrack) {
			currentFeature.geometry.coordinates.push([parseFloat(thisTrack[trackPosition][0]), parseFloat(thisTrack[trackPosition][1])]) ;
		}
		// Add this flight to the collection
		flightFeatureCollection.push(currentFeature) ;		// If there is only one point, change type to a marker
		locationFeature = {type : 'Feature',
			properties : {title : '',comment : '', color: ''},
			geometry : {type : 'LineString', coordinates : [] } } ;
		locationFeature.geometry.coordinates = currentFeature.geometry.coordinates[currentFeature.geometry.coordinates.length - 1] ; // a Point feature has only one coordinate and not an array of coordinates
		locationFeature.properties.title = thisFlight.tail_number  + '\n' + thisFlight.last_altitude + ' ft' ;
		locationFeature.geometry.type = 'Point' ;
		locationFeature.properties.icon = 'airfield' ;
		locationFeature.properties['marker-symbol'] = 'airfield' ;
		locationFeature.properties['marker-size'] = 'large' ;
		locationFeature.properties['marker-color'] = locationFeature.properties.color ;
		// Add this icon to the collection
		locationFeatureCollection.push(locationFeature) ;
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
	// Display a rectangle of 'local zone'
	
	// Display the last known locations
	locationLayer.source.data.features = locationFeatureCollection ;
	map.addLayer(locationLayer) ;
	
	// Display the flights
	flightLayer.source.data.features = flightFeatureCollection ;
	map.addLayer(flightLayer) ;
	// Change the cursor to a pointer when the it enters a feature in the 'airports' layer.
	map.on('mouseenter', 'flights', function (e) {
		map.getCanvas().style.cursor = 'pointer';
		document.getElementById('flightInfo').innerHTML = e.features[0].properties.comment ;
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

	// Display the box layer
	map.addLayer(boxLayer) ;
	// Change the cursor to a pointer when the it enters a feature in the 'airports' layer.
	map.on('mouseenter', 'box', function (e) {
		map.getCanvas().style.cursor = 'pointer';
		document.getElementById('flightInfo').innerHTML = e.features[0].properties.comment ;
		document.getElementById('flightInfo').style.left = ' ' + (20 + e.originalEvent.clientX) + 'px'  ;
		document.getElementById('flightInfo').style.top = ' ' + e.originalEvent.clientY + 'px'  ;
		document.getElementById('flightInfo').style.display = 'block' ;
		document.getElementById('flightInfo').style.zIndex = '10' ;
	});
	// Change it back to a pointer when it leaves.
	map.on('mouseleave', 'box', function (e) {
		map.getCanvas().style.cursor = '';
		document.getElementById('flightInfo').style.display = 'none' ;
	});
}

function initLocalFlights(longitude, longitudeDelta, latitude, latitudeDelta, maxAltitude, mapBoxToken, ajaxURL) {
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
		
	// Build the box around the local zone
	boxLayer.source.data.features[0].geometry.coordinates = [] ;
	boxLayer.source.data.features[0].geometry.coordinates.push([longitude+longitudeDelta, latitude+latitudeDelta]) ;
	boxLayer.source.data.features[0].geometry.coordinates.push([longitude+longitudeDelta, latitude-latitudeDelta]) ;
	boxLayer.source.data.features[0].geometry.coordinates.push([longitude-longitudeDelta, latitude-latitudeDelta]) ;
	boxLayer.source.data.features[0].geometry.coordinates.push([longitude-longitudeDelta, latitude+latitudeDelta]) ;
	boxLayer.source.data.features[0].geometry.coordinates.push([longitude+longitudeDelta, latitude+latitudeDelta]) ;
	boxLayer.source.data.features[0].properties.comment = 'Box around the airport<br/>Lat: ' + (latitude-latitudeDelta) + '-' + (latitude+latitudeDelta) + '<br/>Lon: ' + (longitude-longitudeDelta) + '-' + (longitude+longitudeDelta) + '<br/>Alt: max ' + maxAltitude + ' ft';
	console.log(boxLayer) ;


	
	// When run in bootstrap per https://stackoverflow.com/questions/54681826/mapbox-gl-js-canvas-not-displaying-properly-in-bootstrap-modal
	// TODO to be done only in bootstrap mode ?
	// TODO seems that $ does not work at all
//	$('#map').on('shown.bs.modal', function() {
//    	map.resize();
//	});
}