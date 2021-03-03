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

function insertTrackPoints (flights) {
	var planeCount = 0 , currentId = '' ;
	var currentFeature ;

	console.log("Start of insertTrackPoints") ;
	flightFeatureCollection = [] ;
	for (var flight in flights) {
		console.log('Top level of the loop for ' + flight) ;
		thisFlight = flights[flight] ;
		// TODO add time of the first point in the comment
		currentFeature = {type : 'Feature',
			properties : {title : '',comment : '', color: ''},
			geometry : {type : 'LineString', coordinates : [] } } ;
		currentFeature.properties.title = flight ;
		currentFeature.properties.comment = "Plane: " + thisFlight.plane + '</br>First seen: ' + thisFlight.first + ' UTC</br>Last seen: ' + thisFlight.last + ' UTC';
		currentFeature.properties.color = trackColors[planeCount++] ;
		currentFeature.geometry.type = 'LineString' ;
		currentFeature.geometry.coordinates = [] ;

		thisTrack = thisFlight.track ;
		for (trackPosition in thisTrack) {
			currentFeature.geometry.coordinates.push([thisTrack[trackPosition][0], thisTrack[trackPosition][1]]) ;
		}
		// Add this flight to the collection
		console.log('end of top level of the loop for ' + flight) ;
		flightFeatureCollection.push(currentFeature) ;
	}
		
	// Add the flights layers
	map.on('load', mapAddLayers) ;
	console.log("flightFeatureCollection is ") ;
	console.log(flightFeatureCollection) ;
}

function getTrackPoints(ajaxURL) {
	var XHR = new XMLHttpRequest();
	XHR.onreadystatechange = function() {
		if(XHR.readyState  == 4) {
			if(XHR.status  == 200) {
				console.log("getTrackPoints() call-back") ;
				try {
					var response = eval('(' + XHR.responseText.trim() + ')') ;
				} catch(err) {
					console.log("Cannot eval: " + XHR.responseText.trim()) ;
					return ;
				}
				insertTrackPoints(response) ;
				console.log("end of getTrackPoints() call-back") ;
			} // == 200
		} // == 4
	} // onreadystatechange
	XHR.open("GET", ajaxURL, true) ; // Send asynchronous request
	XHR.send(null) ;
}

function mapAddLayers() {
	// Display the flights
	flightLayer.source.data.features = flightFeatureCollection ;
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

function init(longitude, latitude, mapBoxToken, ajaxURL) {
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
}