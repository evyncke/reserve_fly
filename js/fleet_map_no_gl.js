// Some global variables for the Leaflet map
var map ;

var airportLayerGroup = null ;
var flightLayerGroup = null ;
var locationLayerGroup = null ;

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
			console.log("Error: " + flights['error']) ;
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
			currentPositionFeature.geometry.coordinates = currentFeature.geometry.coordinates.at(-1) ;
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
		
	if (flightLayerGroup) map.removeLayer(flightLayerGroup) ;
	flightLayerGroup = L.geoJSON({
		type : 'FeatureCollection',
		features : flightFeatureCollection
	}, {
		style: function(feature) {
			return {color: feature.properties.color, weight: 2, opacity: 0.8} ;
		},
		onEachFeature: function(feature, layer) {
			layer.on('mouseover', function(event) {
				map.getContainer().style.cursor = 'pointer' ;
				var info = document.getElementById('flightInfo') ;
				info.innerHTML = feature.properties.comment ;
				info.style.left = (20 + event.originalEvent.clientX) + 'px' ;
				info.style.top = event.originalEvent.clientY + 'px' ;
				info.style.display = 'block' ;
				info.style.zIndex = '10' ;
			}) ;
			layer.on('mouseout', function() {
				map.getContainer().style.cursor = '' ;
				document.getElementById('flightInfo').style.display = 'none' ;
			}) ;
		}
	}).addTo(map) ;

	if (locationLayerGroup) map.removeLayer(locationLayerGroup) ;
	locationLayerGroup = L.geoJSON({
		type : 'FeatureCollection',
		features : locationFeatureCollection
	}, {
		pointToLayer: function(feature, latlng) {
			var html = '<div style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">' +
				'<i class="bi bi-airplane-fill" style="color: ' + feature.properties.color + '; font-size: 14px;"></i></div>' ;
			return L.marker(latlng, {
				icon: L.divIcon({html: html, iconSize: [24, 24], className: 'flight-marker'}),
				rotationAngle: feature.properties.bearing || 0,
				rotationOrigin: 'center'
			}) ;
		},
		onEachFeature: function(feature, layer) {
			layer.bindPopup(feature.properties.title) ;
		}
	}).addTo(map) ;
	// TODO dynamucally adjust the map view to fit all flights
	// bound the map to fit all flights if there were flights (else the display is whole Earth!)
	// map.fitBounds([[westCorner, southCorner],
	// 	[eastCorner, northCorner]],
	// 	{padding: [20, 20]}
	// );
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
			console.log("Airport error: " + airports[error]) ;
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
	
	if (airportLayerGroup) map.removeLayer(airportLayerGroup) ;
	airportLayerGroup = L.geoJSON({
		type : 'FeatureCollection',
		features : airportFeatureCollection
	}, {
		pointToLayer: function(feature, latlng) {
			return L.marker(latlng, {
				icon: L.divIcon({
					html: '<div style="width: 10px; height: 10px; border: 2px solid #333; border-radius: 50%; background: #fff;"></div>',
					iconSize: [12, 12],
					iconAnchor: [6, 6],
					className: 'airport-marker'
				})
			}) ;
		},
		onEachFeature: function(feature, layer) {
			layer.bindTooltip(feature.properties.title, {permanent: true, direction: 'top', offset: [0, -6]}) ;
			layer.bindPopup(feature.properties.comment) ;
		}
	}).addTo(map) ;
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
	// Airports are loaded after the initial map view is available.
	// airportLayerGroup = L.layerGroup().addTo(map) ;
	// TODO fix: getAirports('get_airports.php') ;
	// Build the track points
	getTrackPoints() ;
	// Redraw every 5 seconds
	setInterval(getTrackPoints, 5 * 1000) ;	
}

function initFleet(longitudeArg, latitudeArg, mapBoxToken, zoomLevel, ajaxURLArg) {

	// Save all parameters for later use
	longitude = longitudeArg ;
	latitude = latitudeArg ; 
	ajaxURL = ajaxURLArg ;
	
	// Check whether Cookie: contains theme=dark
	if (decodeURIComponent(document.cookie).search('theme=dark') >= 0)
		styleId = 'dark-v9' ;
	else
		styleId = 'outdoors-v10'; // stylesheet location
	
	// Initialize Leaflet map
	map = L.map('map').setView([latitude, longitude], zoomLevel) ;
	L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/' + styleId + '/tiles/{z}/{x}/{y}?access_token=' + mapBoxToken, {
            attribution: '© <a href="https://www.mapbox.com/about/maps/">Mapbox</a> © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            tileSize: 512,
            zoomOffset: -1,
            maxZoom: 18
	}).addTo(map);

	// Add zoom and rotation controls to the map.
	map.whenReady(mapAddLayers) ;
}