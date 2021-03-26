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
//		attribution: 'FlightAware',
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

var flightFeatureCollection = [] ;
var locationFeatureCollection = [] ;

var longitude, latitude, maxAltitude, ajaxURL ;

var trackColors = [ 'Crimson',
'MediumBlue',
'Aquamarine', 
'Chocolate',
'Cyan',
'DarkBlue',
'Crimson',
'black',
'Orange',
'DarkMagenta',
'DarkOrchid',
'DarkOrange', 
'DarkGreen',
'DarkOliveGreen',
'DarkRed',
'DodgerBlue',
'ForestGreen',
'Fuchsia',
'Gold',
'Green',
'GreenYellow',
'Brown',
'Chartreuse',
'Indigo',
'Maroon',
'MediumOrchid',
'MidnightBlue',
'Navy',
'Olive',
'DeepPink',
'LawnGreen',
'LightCoral',
'Magenta',
'Plum',
'Sienna',
'SlateBlue',
'SaddleBrown',
'Red',
'Yellow',
'YellowGreen'
] ;

function tailNumber2Color(str) {
    var hash = 0, i = 0, len = str.length;
    while ( i < 20 ) { // Limit to 20 to avoid changing color for flights in the air
        hash  = ((hash << 5) - hash + str.charCodeAt(i++)) << 0;
    }
    return trackColors[Math.abs(hash) % trackColors.length] ;
}

function insertTrackPoints (flights) {
	var currentId = '' ;
	var currentFeature ;
	var legendDiv = document.getElementById('flightLegend') ;
	var legendItems = [] ;

	flightFeatureCollection = [] ;
	flightFeatureCollection = [] ;
	for (var flight in flights) {
		if (flight == 'sql') continue ;
		if (flight == 'error') {
			console.log(flights[error]) ;
			continue ;
		}
		thisFlight = flights[flight] ;
		if (legendDiv) {
			legendItems.push('<span class="glyphicon glyphicon-plane" style="color:' + tailNumber2Color(flight) + ';"></span> ' + flight + ' UTC<br/>') ;
		}
		// TODO add time of the first point in the comment
		currentFeature = {type : 'Feature',
			properties : {title : '',comment : '', color: ''},
			geometry : {type : 'LineString', coordinates : [] } } ;
		currentFeature.properties.title = flight ;
		currentFeature.properties.comment = "Plane: " + thisFlight.plane + '</br>First seen: ' + thisFlight.first + ' UTC</br>Last seen: ' + thisFlight.last + ' UTC';
		currentFeature.properties.color = tailNumber2Color(flight) ;
		currentFeature.geometry.coordinates = [] ;

		thisTrack = thisFlight.track ;
		var lastLongitude, lastLatitude ;
		for (trackPosition in thisTrack) {
			if (trackPosition == 0 || (Math.abs(lastLongitude-thisTrack[trackPosition][0]) <= 0.05 && Math.abs(lastLatitude-thisTrack[trackPosition][1]) <= 0.05)) {
				currentFeature.geometry.coordinates.push([parseFloat(thisTrack[trackPosition][0]), parseFloat(thisTrack[trackPosition][1])]) ;
				lastLongitude = thisTrack[trackPosition][0] ;
				lastLatitude = thisTrack[trackPosition][1] ;
			} else
				console.log("Skipping position #" + trackPosition + ' <' + thisTrack[trackPosition][0] + ', ' + thisTrack[trackPosition][1] + '>') ;
		}
		// If there is only one point, change type to a marker
		if (currentFeature.geometry.coordinates.length == 1) {
			currentFeature.geometry.coordinates = currentFeature.geometry.coordinates[0] ; // a Point feature has only one coordinate and not an array of coordinates
			currentFeature.properties.title = thisFlight.plane  + '\n' + thisFlight.last + ' UTC' ;
			currentFeature.geometry.type = 'Point' ;
			currentFeature.properties.icon = 'airfield' ;
			currentFeature.properties['marker-symbol'] = 'airfield' ;
			currentFeature.properties['marker-size'] = 'large' ;
			currentFeature.properties['marker-color'] = currentFeature.properties.color ;
			locationFeatureCollection.push(currentFeature) ;
		} else {
			// Add this flight to the collection
			flightFeatureCollection.push(currentFeature) ;
		}
	}

	if (legendDiv) {
		legendDiv.innerHTML = 'Plane/Last seen<br/>' ;
		legendItems.sort(function (a,b) {
				return (a.substr(a.length-28, 19) > b.substr(b.length-28, 19)) ? +1 : -1 ;
			})
		legendDiv.innerHTML += legendItems.join('') ;
		// TODO position the div
	}
		
	map.getSource('flights').setData({
			type : 'FeatureCollection',
			features : flightFeatureCollection
		}) ;		
	map.getSource('locations').setData({
			type : 'FeatureCollection',
			features : locationFeatureCollection
		}) ;	

}

function getTrackPoints() {
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
	console.log("in mapAddLayers(): map.addLayer(flightLayer)") ;
	map.addLayer(flightLayer) ;
	// Build the track points
	getTrackPoints(ajaxURL) ;
	setInterval(getTrackPoints, 10000) ;	
}

function initFleet(longitudeArg, latitudeArg, mapBoxToken, ajaxURLArg) {

	console.log("start of initFleet()") ;	
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
	console.log("end of initFleet()") ;
}