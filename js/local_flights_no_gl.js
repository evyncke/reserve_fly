// Some global variables for Leaflet map
var map ;

// Leaflet layer variables
var boxLayerGroup = null ;
var airportGeoJSONLayer = null ;
var flightGeoJSONLayer = null ;
var locationGeoJSONLayer = null ;

var airportFeatureCollection = [] ;
var flightFeatureCollection = [] ;
var locationFeatureCollection = [] ;

var longitude, longitudeDelta, latitude, latitudeDelta, maxAltitude, ajaxURL ;

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
    while ( i < len ) {
        hash  = ((hash << 5) - hash + str.charCodeAt(i++)) << 0;
    }
    return trackColors[Math.abs(hash) % trackColors.length] ;
}

/**
 * Calculates the bearing (heading) between two points in degrees.
 * 0° is North, 90° is East, etc.
 */
function getBearing(trackCoordinates) {

	if (trackCoordinates.length < 2) {
		return 0; // Not enough points to calculate bearing
	}
	const lng2 = trackCoordinates.at(-1)[0];
	const lat2 = trackCoordinates.at(-1)[1];
	var lng1, lat1 ;
	for (let i = trackCoordinates.length - 2; i >= 0; i--) {
		lng1 = trackCoordinates[i][0];
		lat1 = trackCoordinates[i][1];
		if (lng1 !== lng2 || lat1 !== lat2) {
			break
		}
	}
	if (lng1 == lng2 && lat1 == lat2) {
			return 0; // No movement, so no bearing
	}
	const dLng = (lng2 - lng1) * Math.PI / 180;
    const fLat1 = lat1 * Math.PI / 180;
    const fLat2 = lat2 * Math.PI / 180;

    const y = Math.sin(dLng) * Math.cos(fLat2);
    const x = Math.cos(fLat1) * Math.sin(fLat2) -
              Math.sin(fLat1) * Math.cos(fLat2) * Math.cos(dLng);

    let bearing = Math.atan2(y, x) * 180 / Math.PI;
    return Math.round((bearing + 360) % 360); // Normalize to 0-360
}

function insertTrackPoints(flights) {
	var currentId = '' ;
	var currentFeature ;
	var legendDiv = document.getElementById('flightLegend') ;
	var legendItems = [] ;

	flightFeatureCollection = [] ;
	locationFeatureCollection = [] ;
	for (var flight in flights) {
		if (flight == 'sql') continue ;
		if (flight == 'error') {
			console.log(flights[error]) ;
			continue ;
		}
		thisFlight = flights[flight] ;
		var tailNumber = ((thisFlight.tail_number == '-') ? thisFlight.icao24 : thisFlight.tail_number) ;
		if (legendDiv) {
			legendItems.push('<i class="bi bi-airplane-fill" style="color:' + tailNumber2Color(tailNumber) + ';"></i> ' + tailNumber + ' ' + thisFlight.last_altitude + ' ft  (' + thisFlight.source + ')<br/>') ;
		}
		// TODO add time of the first point in the comment
		currentFeature = {type : 'Feature',
			properties : {title : '',comment : '', color: ''},
			geometry : {type : 'LineString', coordinates : [] } } ;
		currentFeature.properties.title = flight ;
		currentFeature.properties.comment = "Plane: " + thisFlight.tail_number + '</br>Last seen: ' + thisFlight.last + ' UTC';
		currentFeature.properties.color = tailNumber2Color(tailNumber) ;
		currentFeature.geometry.coordinates = [] ;

		thisTrack = thisFlight.track ;
		for (trackPosition in thisTrack) {
			currentFeature.geometry.coordinates.push([parseFloat(thisTrack[trackPosition][0]), parseFloat(thisTrack[trackPosition][1])]) ;
		}
		// Add this flight to the collection
		flightFeatureCollection.push(currentFeature) ;		
		// and the last track point as a marker
		locationFeature = {type : 'Feature',
			properties : {title : '',comment : '', 
				color: currentFeature.properties.color, 
				bearing: getBearing(currentFeature.geometry.coordinates)},
			geometry : {type : 'LineString', coordinates : [] } } ;
		locationFeature.geometry.coordinates = currentFeature.geometry.coordinates.at(-1) ; // a Point feature has only one coordinate and not an array of coordinates
		locationFeature.properties.title = thisFlight.tail_number  + '\n' + thisFlight.last_altitude + ' ft' ;
		locationFeature.geometry.type = 'Point' ;
		// Add this icon to the collection
		locationFeatureCollection.push(locationFeature) ;
	}

	if (legendDiv) {
		legendDiv.innerHTML = legendItems.sort().join('') ;
		// TODO position the div
	}
	
	// Update the flight tracks layer using Leaflet
	if (flightGeoJSONLayer) {
		map.removeLayer(flightGeoJSONLayer) ;
	}
	flightGeoJSONLayer = L.geoJSON({
		type : 'FeatureCollection',
		features : flightFeatureCollection
	}, {
		style: function(feature) {
			return {
				color: feature.properties.color,
				weight: 2,
				opacity: 0.8
			} ;
		},
		onEachFeature: function(feature, layer) {
			layer.on('mouseover', function() {
				map.getContainer().style.cursor = 'pointer' ;
				document.getElementById('flightInfo').innerHTML = feature.properties.comment ;
				document.getElementById('flightInfo').style.display = 'block' ;
				document.getElementById('flightInfo').style.zIndex = '10' ;
			}) ;
			layer.on('mouseout', function() {
				map.getContainer().style.cursor = '' ;
				document.getElementById('flightInfo').style.display = 'none' ;
			}) ;
		}
	}).addTo(map) ;
	
	// Update the location markers layer using Leaflet
	if (locationGeoJSONLayer) {
		map.removeLayer(locationGeoJSONLayer) ;
	}
	locationGeoJSONLayer = L.geoJSON({
		type : 'FeatureCollection',
		features : locationFeatureCollection
	}, {
		pointToLayer: function(feature, latlng) {
			console.log("Creating marker for flight " + feature.properties.title + " at location " + latlng.toString() + 
				" bearing: " + feature.properties.bearing + " with color " + feature.properties.color) ;
			var html = '<div style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">' +
						'<i class="bi bi-airplane-fill" style="color: ' + feature.properties.color + '; font-size: 14px;"></i></div>' ;
			return L.marker(latlng, {
				icon: L.divIcon({
					html: html,
					iconSize: [24, 24],
					className: 'flight-marker'
				}),
				rotationAngle: feature.properties.bearing, // Rotate the marker based on the bearing
				rotationOrigin: 'center' 
			}) ;
		},
		onEachFeature: function(feature, layer) {
			layer.bindPopup(feature.properties.title) ;
		}
	}).addTo(map) ;
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
			geometry : {type : 'Point', coordinates : [] } } ;
		airportFeature.geometry.coordinates = [parseFloat(thisAirport.longitude), parseFloat(thisAirport.latitude)] ;
		airportFeature.properties.title = thisAirport.code ;
		airportFeature.properties.comment = thisAirport.name ;
		// Add this icon to the collection
		airportFeatureCollection.push(airportFeature) ;
	}
	
	// Display the airports using Leaflet
	if (airportGeoJSONLayer) {
		map.removeLayer(airportGeoJSONLayer) ;
	}
	airportGeoJSONLayer = L.geoJSON({
		type : 'FeatureCollection',
		features : airportFeatureCollection
	}, {
		pointToLayer: function(feature, latlng) {
			return L.circleMarker(latlng, {
				radius: 6,
				fillColor: '#3388ff',
				color: '#333',
				weight: 2,
				opacity: 0.8,
				fillOpacity: 0.7
			}) ;
		},
		onEachFeature: function(feature, layer) {
			layer.bindPopup('<strong>' + feature.properties.title + '</strong><br/>' + feature.properties.comment) ;
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
	
	// Draw the local flight zone
	drawBox(longitude, longitudeDelta, latitude, latitudeDelta, maxAltitude) ;
	
	// Build the track points and refresh every 2 seconds
	getTrackPoints() ;
	setInterval(getTrackPoints, 2 * 1000) ;	
}

function drawBox() {
	
	if (boxLayerGroup) {
		map.removeLayer(boxLayerGroup) ;
	}
	boxLayerGroup = L.featureGroup().addTo(map) ;
	L.polyline([[latitude+latitudeDelta, longitude+longitudeDelta], 
			[latitude-latitudeDelta, longitude+longitudeDelta], 
			[latitude-latitudeDelta, longitude-longitudeDelta], 
			[latitude+latitudeDelta, longitude-longitudeDelta], 
			[latitude+latitudeDelta, longitude+longitudeDelta]], 
			{color: 'black', weight: 3, dashArray: '10 2'}).addTo(boxLayerGroup).
			bindPopup('Box around the airport<br/>Lat: ' + (latitude-latitudeDelta) + '-' + (latitude+latitudeDelta) + '<br/>Lon: ' + (longitude-longitudeDelta) + '-' + (longitude+longitudeDelta) + '<br/>Alt: max ' + maxAltitude + ' ft') ;
}

function initLocalFlights(longitudeArg, longitudeDeltaArg, latitudeArg, latitudeDeltaArg, maxAltitudeArg, mapBoxToken, zoomLevel, ajaxURLArg) {
	
	// Save all parameters for later use
	longitude = longitudeArg ;
	longitudeDelta = longitudeDeltaArg ;
	latitude = latitudeArg ; 
	latitudeDelta = latitudeDeltaArg ; 
	maxAltitude = maxAltitudeArg ;
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
		
	// Mark the airports
	getAirports('https://nav.vyncke.org/airports_ws.php?lat1=' + (latitude-latitudeDelta) + '&lat2=' + (latitude+latitudeDelta) + '&lng1=' + (longitude-longitudeDelta) + '&lng2=' + (longitude+longitudeDelta)) ;
}