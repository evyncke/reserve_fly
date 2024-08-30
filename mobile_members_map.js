// Some global variables for the mapBox
var map ;

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
            properties : {
				title : members[i].name, 
				comment : "Name: " + members[i].name + '</br>City: ' + members[i].city, 
				icon: 'marker',
				// color: ''
			},
            geometry : {
				type: 'Point',
	
				coordinates : [members[i].longitude, members[i].latitude] } 
		} ;
		currentFeature.properties['marker-symbol'] = 'marker' ;
		currentFeature.properties['marker-size'] = 'small' ;
		// currentFeature.properties['marker-color'] = currentFeature.properties.color ;
		locationFeatureCollection.push(currentFeature) ;
	} 
			
	map.getSource('locations').setData({
			type : 'FeatureCollection',
			features : locationFeatureCollection
		}) ;	
}

function getMembersPoints() {
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

	// Build the track points
	getMembersPoints(ajaxURL) ;
}

function initMap(longitudeArg, latitudeArg, mapBoxToken, ajaxURLArg) {

	// Save all parameters for later use
	longitude = longitudeArg ;
	latitude = latitudeArg ; 
	ajaxURL = ajaxURLArg ;
	
	mapboxgl.accessToken = mapBoxToken;
	options = {
	    container: 'map', // container id
	    center: [longitude, latitude], // starting position [lng, lat]
	    zoom: 8 // starting zoom 
	} ;
	// Check whether Cookie: contains theme=dark
	if (decodeURIComponent(document.cookie).search('theme=dark') >= 0)
		options.style = 'mapbox://styles/mapbox/dark-v9' ;
	else
		options.style = 'mapbox://styles/mapbox/outdoors-v10'; // stylesheet location
	map = new mapboxgl.Map(options);

	// Add zoom and rotation controls to the map.
	map.addControl(new mapboxgl.NavigationControl());
	
	map.on('load', mapAddLayers) ;
}