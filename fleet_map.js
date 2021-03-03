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

function insertTrackPoints () {
	var plane = 0, currentId = '' ;
	var currentFeature ;

	flightFeatureCollection = [] ;
	for (var pointIndex = 0; pointIndex < flightPoints.length; pointIndex++) {
		// TODO add time of the first point in the comment
		// TODO separate in two flights if the timestamp difference is > 30 minutes or if booking-id is different
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

function init(longitude, latitude, mapBoxToken) {
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
	insertTrackPoints() ;
	// Add the flights layers
	map.on('load', mapAddLayers) ;
}