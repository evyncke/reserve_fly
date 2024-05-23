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
		'line-width' : 2,
	},
	source : {
		type : 'geojson',
		data : {
			type : 'FeatureCollection',
			features : []
		}
	}
} ;

var locationLayer = {
	id : 'locations',
	type: 'symbol',
	source : {
		type : 'geojson',
		data : {
			type : 'FeatureCollection',
			features : []
		}
	},
	layout: { // Only applicable to type: symbol
		"icon-image": "{icon}-15", // used when type: symbol
		"text-field": "{title}",
		"text-font": ["Open Sans Semibold", "Arial Unicode MS Bold"],
		"text-offset": [0, 0.6],
		"text-anchor": "top-left",
		"text-ignore-placement": true,
	}
} ;

var locationFeatureCollection = [] ;

var longitude, latitude, maxAltitude, ajaxURL ;

function insertMemberLocation(members) {
	var currentId = '' ;
	var currentFeature ;

	locationFeatureCollection = [] ;
 	for (var i in members) {
        currentFeature = {type : 'Feature',
            properties : {title : '',comment : '', color: ''},
            geometry : {type : 'LineString', coordinates : [] } } ;
	    currentFeature.properties.title = members[i].name ;
		currentFeature.properties.comment = "Name: " + members[i].name + '</br>City: ' + members[i].city;
		currentFeature.geometry.coordinates =[members[i].longitude, members[i].latitude] ;
		currentFeature.geometry.type = 'Point' ;
		currentFeature.properties.icon = 'marker' ;
		currentFeature.properties['marker-symbol'] = 'marker' ;
		currentFeature.properties['marker-size'] = 'small' ;
		currentFeature.properties['marker-color'] = currentFeature.properties.color ;
		locationFeatureCollection.push(currentFeature) ;
	} 
			
	map.getSource('locations').setData({
			type : 'FeatureCollection',
			features : locationFeatureCollection
		}) ;	
}

function getTrackPoints() {
	var XHR = new XMLHttpRequest();
	XHR.onreadystatechange = function() {
		if(this.readyState  == 4) {
			if(this.status  == 200) {
				try {
					var response = eval('(' + this.responseText.trim() + ')') ;
				} catch(err) {
					console.log("Cannot eval: " + this.responseText.trim()) ;
					return ;
				}
				insertMemberLocation(response) ;
			} // == 200
		} // == 4
	} // onreadystatechange
	XHR.open("GET", ajaxURL, true) ; // Send asynchronous request
	XHR.send(null) ;
}

function mapAddLayers() {
	// Display the last known locations
	locationLayer.source.data.features = locationFeatureCollection ;
	map.addLayer(locationLayer) ;

	// Change the cursor to a pointer when the it enters a feature in the 'flights' layer.
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

	// Try to do it asynchronously
	map.addLayer(flightLayer) ;
	// Build the track points
	getTrackPoints(ajaxURL) ;
}

function initMap(longitudeArg, latitudeArg, mapBoxToken, ajaxURLArg) {

	// Save all parameters for later use
	longitude = longitudeArg ;
	latitude = latitudeArg ; 
	ajaxURL = ajaxURLArg ;
	
	mapboxgl.accessToken = mapBoxToken;
	map = new mapboxgl.Map({
	    container: 'map', // container id
	    style: 'mapbox://styles/mapbox/outdoors-v10', // stylesheet location
	    center: [longitude, latitude], // starting position [lng, lat]
	    zoom: 8 // starting zoom
	});

	// Add zoom and rotation controls to the map.
	map.addControl(new mapboxgl.NavigationControl());
	
	map.on('load', mapAddLayers) ;
}