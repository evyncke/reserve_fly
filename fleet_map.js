// Some global variables for the mapBox
var map ;

var airportLayer = {
	id : 'airports',
	type: 'symbol',
	source : {
		type : 'geojson',
		data : {
			type : 'FeatureCollection',
			features : []
		}
	},
	layout: { // Only applicable to type: symbol
		"icon-image": "{icon}-11", // used when type: symbol
		"text-field": "{title}",
		"text-font": ["Open Sans Semibold", "Arial Unicode MS Bold"],
		"text-offset": [0, 0.6],
//		"text-anchor": "top-left",
	        "text-variable-anchor": ["top-left", "top-right", "bottom-right", "bottom-left", "left"],
		"text-justify": "auto",
		"text-ignore-placement": true,
		"icon-ignore-placement": true,
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
	        "text-variable-anchor": ["top-left", "top-right", "bottom-right", "bottom-left", "left"],
		"text-justify": "auto",
		"text-ignore-placement": true,
		"icon-ignore-placement": true,
		"icon-allow-overlap": true,
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
    while ( i < 20 && i < len) { // Limit to 20 to avoid changing color for flights in the air
        hash  = ((hash << 5) - hash + str.charCodeAt(i++)) << 0;
    }
    return trackColors[Math.abs(hash) % trackColors.length] ;
}

function insertTrackPoints(flights) {
	var currentId = '' ;
	var currentFeature ;
	var legendDiv = document.getElementById('flightLegend') ;
	var legendItems = [] ;
	var northCorner = -90, southCorner = +90, westCorner = 180, eastCorner = -180 ; // The box containing all markers

	flightFeatureCollection = [] ;
	locationFeatureCollection = [] ;
	var flightCount = 0 ;
	if (flights.len == 0) return ;
	for (var flight in flights) {
		if (flight == 'sql' || flight == 'log') continue ;
		if (flight == 'error') {
			console.log(flights['error']) ;
			continue ;
		}
		flightCount ++ ;
		thisFlight = flights[flight] ;
		planeColor = tailNumber2Color(flight) ;
		if (legendDiv) {
			legendItems.push('<tr><td><i class="bi bi-airplane-fill" style="color:' + planeColor + ';"></i> ' + thisFlight.plane + '</td><td>' +
				thisFlight.last + ' UTC</td><td>' + thisFlight.first + ' UTC</td><td>' + thisFlight.pilot + '</td></tr>') ;
		}
		currentFeature = {type : 'Feature',
			properties : {title : '',comment : '', color: ''},
			geometry : {type : 'LineString', coordinates : [] } } ;
		currentFeature.properties.title = flight ;
		currentFeature.properties.comment = "Plane: " + thisFlight.plane + '</br>First seen: ' + thisFlight.first + ' UTC</br>Last seen: ' + thisFlight.last + ' UTC';
		if (thisFlight.last_altitude)
			currentFeature.properties.comment += '<br>Altitude: ' + thisFlight.last_altitude + ' ft' ;
		if (thisFlight.last_velocity)
			currentFeature.properties.comment += '<br>Ground speed: ' + thisFlight.last_velocity + ' kts' ;
		currentFeature.properties.color = planeColor ;
		currentFeature.geometry.coordinates = [] ;

		thisTrack = thisFlight.track ;
		var lastLongitude, lastLatitude ;
		for (trackPosition in thisTrack) {
			if (trackPosition < 2 || (Math.abs(lastLongitude-thisTrack[trackPosition][0]) <= 0.3 && Math.abs(lastLatitude-thisTrack[trackPosition][1]) <= 0.3)) {
				currentFeature.geometry.coordinates.push([parseFloat(thisTrack[trackPosition][0]), parseFloat(thisTrack[trackPosition][1])]) ;
				lastLongitude = thisTrack[trackPosition][0] ;
				lastLatitude = thisTrack[trackPosition][1] ;
				// Let's update the box of all tracks
				if (thisTrack[trackPosition][0] < westCorner) westCorner = thisTrack[trackPosition][0]  ;
				if (thisTrack[trackPosition][0] > eastCorner) eastCorner = thisTrack[trackPosition][0]  ;
				if (thisTrack[trackPosition][1] < southCorner) southCorner = thisTrack[trackPosition][1]  ;
				if (thisTrack[trackPosition][1] > northCorner) northCorner = thisTrack[trackPosition][1]  ;
			} else
				console.log("Skipping position #" + trackPosition + '=<' + thisTrack[trackPosition][0] + ', ' + thisTrack[trackPosition][1] + '>, delta=<' + Math.abs(lastLongitude-thisTrack[trackPosition][0]) + ', ' + Math.abs(lastLatitude-thisTrack[trackPosition][1])) ;
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
			// Adding current position
			currentPositionFeature = {type : 'Feature',
				properties : {title : '',comment : '', color: ''},
				geometry : {type : 'LineString', coordinates : [] } } ;
			currentPositionFeature.properties.title = flight ;
			currentPositionFeature.properties.comment = currentFeature.properties.comment ;
			currentPositionFeature.geometry.type = 'Point' ;
			currentPositionFeature.properties.icon = 'airfield' ;
			currentPositionFeature.properties['marker-symbol'] = 'airfield' ;
			currentPositionFeature.properties['marker-size'] = 'medium' ;
			currentPositionFeature.properties['marker-color'] = currentFeature.properties.color ;
			currentPositionFeature.geometry.coordinates = ([parseFloat(thisTrack[trackPosition][0]), parseFloat(thisTrack[trackPosition][1])]) ;
			locationFeatureCollection.push(currentPositionFeature) ;
		}
	}
	if (flightCount == 0) return ; // Else the map is zoomed out to the whole Earth!
	
	if (legendDiv) {
		var x = legendItems.sort(function (a,b) {
				var firstA = a.match(/.*\/(.+)\/.*\/.*/)[1] ;
				var firstB = b.match(/.*\/(.+)\/.*\/.*/)[1] ;
				if (firstA > firstB) {
						return +1 ;
				} else {
						return -1 ;
				}
			})
 		legendDiv.innerHTML = '<table class="table table-bordered table-striped">' +
		 '<thead><tr><th>Plane</th><th>Last seen</th><th>First seen</th><th>Pilot</th></tr></thead>' +
		 '<tbody>'  + x.join('') + '</tbody></table>';
	}
		
	map.getSource('flights').setData({
			type : 'FeatureCollection',
			features : flightFeatureCollection
		}) ;		
	map.getSource('locations').setData({
			type : 'FeatureCollection',
			features : locationFeatureCollection,
		}) ;	
	// bound the map to fit all flights if there were flights (else the display is whole Earth!)
	map.fitBounds([[westCorner, southCorner],
		[eastCorner, northCorner]],
		{padding: {top: 20, bottom: 20, left: 20, right: 20}}
	);
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
				insertTrackPoints(response) ;
			} // == 200
		} // == 4
	} // onreadystatechange
	XHR.open("GET", ajaxURL, true) ; // Send asynchronous request
	XHR.send(null) ;
}

function insertAirports(airports) {
	
	airportFeatureCollection = [] ;
	for (var airport in airports) {
		if (airport == 'sql') continue ;
		if (airport == 'status') continue ;
		if (airport == 'error') {
			console.log(airports[error]) ;
			continue ;
		}
		thisAirport = airports[airport] ;
		airportFeature = {type : 'Feature',
			properties : {title : '', comment : ''},
			geometry : {type : 'LineString', coordinates : [] } } ; // Is this really LineString ????? TODO
		airportFeature.geometry.coordinates = [parseFloat(thisAirport.longitude), parseFloat(thisAirport.latitude)] ; // a Point feature has only one coordinate and not an array of coordinates
		airportFeature.properties.title = thisAirport.code ;
		airportFeature.properties.comment = thisAirport.name ;
		airportFeature.geometry.type = 'Point' ;
		airportFeature.properties.icon = 'circle' ;
		airportFeature.properties['marker-symbol'] = 'circle' ;
		airportFeature.properties['marker-size'] = 'small' ;
		// Add this icon to the collection
		airportFeatureCollection.push(airportFeature) ;
	}
	
	map.getSource('airports').setData({
		type : 'FeatureCollection',
		features : airportFeatureCollection
	}) ;	
}

function getAirports(ajaxURL) {
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
				insertAirports(response) ;
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

	// Display the airports (currently empty as we need the map to move)
	airportLayer.source.data.features = [] ;
	map.addLayer(airportLayer) ;

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
	// Redraw every 5 seconds
	setInterval(getTrackPoints, 5 * 1000) ;	
}

function initFleet(longitudeArg, latitudeArg, mapBoxToken, ajaxURLArg) {

	// Save all parameters for later use
	longitude = longitudeArg ;
	latitude = latitudeArg ; 
	ajaxURL = ajaxURLArg ;
	
	mapboxgl.accessToken = mapBoxToken;
	options = {
	    container: 'map', // container id
	    center: [longitude, latitude], // starting position [lng, lat]
	    zoom: 8 // starting zoom was 8 then 9
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