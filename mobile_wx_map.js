var
	ifrMarker, vfrMarker, mfrMarker, ufrMarker, metarsCache = [], metarsMarkers = [];
var
	airportMarker, airportsMarkers = [] ;
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */

function loadWxMap() {
	var myOptions, i ;

	myOptions = {
		zoom: CenterZoom,
		center: new google.maps.LatLng(CenterLat, CenterLng),
		mapTypeControl: true,
		mapTypeControlOptions: {
			style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
		},
		zoomControl: true,
		zoomControlOptions: {
			style: google.maps.ZoomControlStyle.DEFAULT
		},
		panControl: false,
		scaleControl: true,
		overviewMapControl: false,
		mapTypeId: google.maps.MapTypeId.TERRAIN
	    };
	map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

	//
	// Diverses icones pour les markers
	// MarkerImage(url:string, size?:Size, origin?:Point, anchor?:Point, scaledSize?:Size)
	// 	Size = width, height
	ifrMarker = new google.maps.MarkerImage('images/meteo_i.png',
		new google.maps.Size(32, 32), // Size
		new google.maps.Point(0,0), // origin
		new google.maps.Point(16, 16)); // anchor
	mfrMarker = new google.maps.MarkerImage('images/meteo_m.png',
		new google.maps.Size(32, 32), // Size
		new google.maps.Point(0,0), // origin
		new google.maps.Point(16, 16)); // anchor
	ufrMarker = new google.maps.MarkerImage('images/meteo_d.png',
		new google.maps.Size(32, 32), // Size
		new google.maps.Point(0,0), // origin
		new google.maps.Point(16, 16)); // anchor
	vfrMarker = new google.maps.MarkerImage('images/meteo_v.png',
		new google.maps.Size(32, 32), // Size
		new google.maps.Point(0,0), // origin
		new google.maps.Point(16, 16)); // anchor
	airportMarker = new google.maps.MarkerImage('images/apt-marker.png',
		new google.maps.Size(20, 20), // Size
		new google.maps.Point(0,0), // origin
		new google.maps.Point(10, 10)); // anchor

	google.maps.event.addListener(map, 'idle', function() { // Everytime the map is panned or zoomed, also called when first displayed
			metarDisplayChanged() ;
			if (displayAirport) airportDisplayChanged() ;
		}) ;

	// Refresh all markers every 10 minutes
	setInterval(refreshMarkers, 10 * 60 * 1000) ;
    console.log('End of loadWxMap()') ;
}

function createMarker(pointLatLng, type, code, name, heading) {
	var marker, furtherActions ;

	switch (type) {
                case 'APT': marker = airportMarker; break ;
		case 'IFR': marker = ifrMarker; break ;
		case 'MFR': marker = mfrMarker; break ;
		case 'UFR': marker = ufrMarker; break ;
		case 'VFR': marker = vfrMarker; break ;
	}
	var markerObject = new google.maps.Marker({
		position: pointLatLng,
		map: map,
		flat: true,
		icon: marker,
		visible: true,
		draggable: false,
		clickable: true,
		title: code
		}) ;
	if (type == 'APT') {
		var infowindow = new google.maps.InfoWindow({
			content: '<div style="white-space: nowrap;">' + code + '</br>' + name + '</div>'
		 });
                google.maps.event.addListener(markerObject, 'mouseover', function() { infowindow.open(map,markerObject); }) ;
                google.maps.event.addListener(markerObject, 'mouseout', function() { infowindow.close(); }) ;
	}
	return markerObject ;
}

function removeMetarMarkers() {
	var i ;

	if (metarsMarkers) {
		for (i in metarsMarkers) {
			metarsMarkers[i].setMap(null);
		}
		metarsMarkers.length = 0;
	}
}

function updateMetarMarker(response) {
    console.log("Starting updateMetarMarker() for " + response.station) ;
	for (var i = 0; i < metarsMarkers.length; i++) {
		if (metarsMarkers[i].title == response.station) {
            console.log("Found it !!!!") ;
			switch(response.condition) {
				case 'IMC': metarsMarkers[i].setIcon(ifrMarker) ; break ;
				case 'MMC': metarsMarkers[i].setIcon(mfrMarker) ; break ;
				case 'VMC': metarsMarkers[i].setIcon(vfrMarker) ; break ;
				default: metarsMarkers[i].setIcon(ufrMarker) ; 
			}
			var metarAgeWarning = '' ;
			if (response.age == 'invalid') {
				metarAgeWarning = '<br/>Datetime group is invalid' ;
				metarsMarkers[i].setIcon(ufrMarker) ;
			} else if (response.age > 60) {
				metarAgeWarning = '<br/>Old METAR : ' + response.age + ' minutes' ;
				metarsMarkers[i].setIcon(ufrMarker) ;
			}
			var metarError = '' ;
			if (response.error)
				metarError = '<br/>Error : ' + response.error ;
			var cloudBase = (response.temperature && response.dew_point) ? '<br/>Computed cloud base: ' + 400 * (response.temperature - response.dew_point) + 'ft' : '';
			var infowindow = new google.maps.InfoWindow({
				content: '<div style="white-space: nowrap;">' + response.METAR + cloudBase + metarAgeWarning + metarError + '</div>' 
			});
			var marker = metarsMarkers[i] ;
			google.maps.event.clearListeners(marker, 'mouseover') ;
			google.maps.event.clearListeners(marker, 'mouseout') ;
			google.maps.event.addListener(marker, 'mouseover', function() {
				infowindow.open(map, marker);
				}) ;
			google.maps.event.addListener(marker, 'mouseout', function() {
				infowindow.close();
				}) ;
		}
	}
    console.log("Ending updateMetarMarker() for " + response.station) ;
}

function metarCallback(response) {
console.log('metarCallback for station: ' + response.station + ', METAR: ' + response.METAR + ', error: ' + response.error) ;
	metarsCache[response.station] = response ;
	updateMetarMarker(response) ;
}

function displayMetar(station) {
	if (metarsCache[station]) {
		updateMetarMarker(metarsCache[station]) ;
	} else {
		var script = document.createElement('script');
		script.type = 'text/javascript';
		script.src = 'https://nav.vyncke.org/metar.php?station=' + station + '&callback=metarCallback';
		document.head.appendChild(script) ;
		document.head.removeChild(script) ;
	}
}

function displayMetarMarkers() {
	var bounds, neCorner, swCorner ;

	bounds = map.getBounds() ; 
	neCorner = bounds.getNorthEast() ;
	swCorner = bounds.getSouthWest() ;
	// Start AJAX to get all NAVAIDS
	var XHR=new XMLHttpRequest();
	XHR.onreadystatechange = function() {
		if(XHR.readyState  == 4) {
			if(XHR.status  == 200) {
				try {
					var response = eval('(' + XHR.responseText + ')') ;
				} catch(err) {
					return ;
				}
				var i ;
				for (i in response) {
					if (isNaN(i)) continue ;
					metarsMarkers.push(createMarker(new google.maps.LatLng(response[i].latitude, response[i].longitude),
						'UFR', response[i].station, response[i].station)) ;
					displayMetar(response[i].station) ;
				}
				if (response.length >= 200) document.getElementById('warningDisplayNavaids').innerHTML='!!! Too many METAR to display all of them!!!' ;
			}
		}
	}
	XHR.open("GET", 'https://nav.vyncke.org/metars_ws.php?json=y&lat1='+swCorner.lat()+'&lat2='+neCorner.lat()+'&lng1='+swCorner.lng()+'&lng2='+neCorner.lng(), true) ;
	XHR.send(null) ;
}

function metarDisplayChanged() {
	removeMetarMarkers() ;
	displayMetarMarkers() ;
}

function refreshMarkers() {
	// Reset the cache
	metarsCache = [] ;
	// Scan the chain of all markers
	for (i = 0; i < metarsMarkers.length; i++) {
		displayMetar(metarsMarkers[i].title) ;
	}
}

function removeAirportsMarkers() {
        var i ;

        if (airportsMarkers) {
                for (i in airportsMarkers) {
                        airportsMarkers[i].setMap(null);
                }
                airportsMarkers.length = 0;
        }
}

function displayAirportsMarkers() {
        var bounds, neCorner, swCorner ;

        bounds = map.getBounds() ;
        neCorner = bounds.getNorthEast() ;
        swCorner = bounds.getSouthWest() ;
	var XHR=new XMLHttpRequest();
        XHR.onreadystatechange = function() {
                if(XHR.readyState  == 4) {
                        if(XHR.status  == 200) {
                                try {
                                        var response = eval('(' + XHR.responseText + ')') ;
console.log(XHR.responseText) ;
                                } catch(err) {
                                        return ;
                                }
                                var i ;
                                for (i in response) {
                                        airportsMarkers.push(createMarker(new google.maps.LatLng(response[i].latitude, response[i].longitude),
                                                'APT', response[i].code, response[i].name)) ;
                                }
                        }
                }
        }
        XHR.open("GET", 'https://nav.vyncke.org/airports_ws.php?json=y&lat1='+swCorner.lat()+'&lat2='+neCorner.lat()+'&lng1='+swCorner.lng()+'&lng2='+neCorner.lng(), true) ;
        XHR.send(null) ;
}

function airportDisplayChanged() {
console.log('airportDisplayChanged') ;
	removeAirportsMarkers() ;
	displayAirportsMarkers() ;
}