var
// $_SERVER['HTTP_USER_AGENT'] contains also "Android" or "iPad"
	browserOrientation = window.orientation,
	ephemerides = new Array(),
	isMobile = navigator.userAgent.match(/(iPhone|iPod|iPad|Android|BlackBerry)/); 
var browserWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth ;
var	browserHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;

function leadingZero(n) {
	if (n < 10)
		return '0' + n ;
	else
		return n ;
}

// Those functions are required because Internet Explorer does not give out a nice result for toLocalTimeString() by embedding unicode meta-character :-(
// Alas, getHours is giving the time in the local time zone, hence, it does not work outside European timezone...
function myDateGetHours(d) {
	return leadingZero(d.getHours()) ;
}

function myDateGetMinutes(d) {
	return leadingZero(d.getMinutes()) ;
}

function myDateGetHoursMinutes(d) {
	return leadingZero(d.getHours()) + ':' + leadingZero(d.getMinutes()) ;
}

function displayMETAR(station) {
	var XHR=new XMLHttpRequest();
	XHR.onreadystatechange = function() {
		if(XHR.readyState  == 4) {
			if(XHR.status  == 200) {
				var elem = document.getElementById('metarMessage') ;
				try {
					var response = eval('(' + XHR.responseText.trim() + ')') ;
				} catch(err) {
					return ;
				}
				if (response.error != '') {
					elem.innerHTML = response.error ;
				} else {
					setTimeout(function () { displayMETAR(station);} , 1000 * 60 * 5) ; // Refresh every 5 minutes
					if (response.condition != null && response.condition == 'VMC')
						elem.style.backgroundColor =  'paleGreen' ;
					else if (response.condition != null && response.condition == 'MMC')
						elem.style.backgroundColor = 'orange' ;
					else if (response.condition != null && response.condition == 'IMC')
						elem.style.backgroundColor = 'pink' ;
					else
						elem.style.backgroundColor = 'lightGray' ;
					elem.innerHTML = '<b>' + response.METAR + '</b>' ;
					if (station == 'EBSP') {
						elem.innerHTML += '<br/>Density altitude at ' + response.station + ': ' +
							response.density_altitude + ' ft, elevation: ' + response.elevation + ' ft';
						if (response.wind_velocity != null && response.wind_direction != null && response.wind_direction != 'VRB' && runwaysQFU.length > 0) {
							elem.innerHTML += '<br/><b>Wind components</b>' ;
							var ul = document.createElement('ul');
							elem.appendChild(ul);
							ul.style.display = 'inherit' ; // CSS magic to avoid empty space before first list item
	
							for (i = 0; i < runwaysQFU.length ; i++) {
								var qfuWindAngle = (runwaysQFU[i] - response.wind_direction) *  2 * Math.PI / 360 ; // In radians
								var qfuComponent =  Math.round(response.wind_velocity * Math.cos(qfuWindAngle)) ;
								var crossComponent =  Math.round(response.wind_velocity * Math.sin(qfuWindAngle)) ;
								if (response.wind_gust) {
									var qfuGustComponent =  Math.round(response.wind_gust * Math.cos(qfuWindAngle)) ;
									var crossGustComponent =  Math.round(response.wind_gust * Math.sin(qfuWindAngle)) ;
								}
								var li = document.createElement('li') ; 
								ul.appendChild(li) ;
								li.innerHTML = 'Runway ' + Math.round(runwaysQFU[i]/10) + ': ' ;
								if (qfuComponent >= 0) {
									li.innerHTML += 'headwind = ' + qfuComponent ;
									if (response.wind_gust) li.innerHTML +=  ' g' + qfuGustComponent ;
									li.innerHTML +=  ', ' ;
								} else {
									li.innerHTML += 'tailwind = ' + (-qfuComponent) ;
									if (response.wind_gust) li.innerHTML +=  ' g' + (-qfuGustComponent) ;
									li.innerHTML +=  ', ' ;
								}
								if (crossComponent >= 0) {
									li.innerHTML += 'left crosswind = ' + crossComponent  ;
									if (response.wind_gust) li.innerHTML +=  ' g' + crossGustComponent ;
									li.innerHTML +=  ' kt' ;
								} else {
									li.innerHTML += 'right crosswind = ' + (-crossComponent) ;
									if (response.wind_gust) li.innerHTML +=  ' g' + (-crossGustComponent) ;
									li.innerHTML +=  ' kt' ;
								}
								if (response.wind_gust && Math.abs(crossGustComponent) > 15)
									li.innerHTML += ' <b>!Caution!</b>' ;
								else if (Math.abs(crossComponent) > 15)
									li.innerHTML += ' <b>!Caution!</b>' ;
							}
						}
					}
				}
			}
		}
	}
	var requestUrl = 'metar.php?station=' + station ;
	XHR.open("GET", requestUrl, true) ;
	XHR.send(null) ;
}

function refreshEphemerides(planningYear, planningMonth, planningDay) {
	var XHR=new XMLHttpRequest();
	XHR.onreadystatechange = function() {
		if(XHR.readyState  == 4) {
			if(XHR.status  == 200) {
				try {
					var response = eval('(' + XHR.responseText.trim() + ')') ;
				} catch(err) {
					return ;
				}
				// Received data in AJAX is in seconds since 1-1-1970 while Date() is in msec
				// Issue is that Date() objects are created in the browser time zone...
				// response.timezone_offset is the UTC offset in seconds (positive is EAST, e.g. Spa CEST = 7200
				// Date().getTimezoneOffset() is the UTC offset in minute (positive is WEST, e.g. PST = 420)
				offsetBrowserAirfield = (0 + parseInt(response.timezone_offset) + 60 * (new Date().getTimezoneOffset())) * 1000 ;
				ephemerides.aero_sunrise = new Date(response.aero_sunrise * 1000 + offsetBrowserAirfield) ;
				ephemerides.sunrise = new Date(response.sunrise * 1000 + offsetBrowserAirfield) ;
				ephemerides.airport_open = new Date(response.airport_open * 1000 + offsetBrowserAirfield) ;
				ephemerides.airport_close = new Date(response.airport_close * 1000 + offsetBrowserAirfield) ;
				ephemerides.aero_sunset = new Date(response.aero_sunset * 1000 + offsetBrowserAirfield) ;
				ephemerides.sunset = new Date(response.sunset * 1000 + offsetBrowserAirfield) ;
				var ephemeridesTable = document.getElementById('ephemeridesTable') ;
				document.getElementById('aeroDay').innerHTML = myDateGetHoursMinutes(ephemerides.aero_sunrise) ;
				document.getElementById('aeroNight').innerHTML = myDateGetHoursMinutes(ephemerides.aero_sunset) ;
				document.getElementById('civilDay').innerHTML = myDateGetHoursMinutes(ephemerides.sunrise) ;
				document.getElementById('civilNight').innerHTML = myDateGetHoursMinutes(ephemerides.sunset) ;
				document.getElementById('airportDay').innerHTML = myDateGetHoursMinutes(ephemerides.airport_open) ;
				document.getElementById('airportNight').innerHTML = myDateGetHoursMinutes(ephemerides.airport_close) ;
			}
		}
	}
	document.getElementById('displayDate').innerHTML = planningYear + '/' + planningMonth + '/' + planningDay ;
	try {
		XHR.open("GET", "get_ephemerides.php?year=" + planningYear + '&month=' + planningMonth + '&day=' + planningDay, true) ;
		XHR.send(null) ;
	} catch(err) {
		console.log('Cannot open() or send() in refreshEphemerides()...') ;
	}
}

function displayClock() {
	nowTimestamp = nowTimestamp + 60 ; // Do not trust browser time but the server time
	var nowDate = new Date(nowTimestamp * 1000) ;
	var nowHour = nowDate.getHours() ;	
	var	nowMinute = nowDate.getMinutes() ;
	
	document.getElementById('hhmmLocal').innerHTML = ('0' + nowHour).substr(-2, 2) + ':' + ('0' + nowMinute).substr(-2, 2) ;
	if (nowHour - utcOffset > 0)
		document.getElementById('hhmmUTC').innerHTML = ('0' + (nowHour - utcOffset)).substr(-2, 2) + ':' + ('0' + nowMinute).substr(-2, 2) + 'Z';
	else
		document.getElementById('hhmmUTC').innerHTML = ('0' + (24 + nowHour - utcOffset)).substr(-2, 2) + ':' + ('0' + nowMinute).substr(-2, 2) + 'Z' ;
	setTimeout(displayClock, 1000 * 60) ; // Refresh every 60 seconds
}


function redirect(id, auth) {
	window.location.href = 'mobile.php?id=' + id + '&auth=' + auth ;
}

function logbookClick (id, auth) {
	window.location.href = 'mobile_logbook.php?id=' + id + '&auth=' + auth ;
}

function newLogbookClick (id, auth) {
	window.location.href = 'IntroCarnetVol.php?id=' + id + '&auth=' + auth ;
}

function modifyClick(id, auth) {
	window.location.href = 'mobile_book.php?id=' + id + '&auth=' + auth ;
}

function cancelConfirm(id, auth) {
	var XHR=new XMLHttpRequest();
	XHR.onreadystatechange = function() {
		if(XHR.readyState  == 4) {
			if(XHR.status  == 200) {
				try {
					var response = eval('(' + XHR.responseText.trim() + ')') ;
				} catch(err) {
					return ;
				}
				if (response.error != '') {
					document.getElementById('confirmCancellation').innerHTML = response.error ;
				} else {
					document.getElementById('confirmCancellation').innerHTML = response.message ;
				}
			}
		}
	}
	var requestUrl = "cancel_booking.php?id=" + id + "&auth=" + auth + "&reason=mobile" ;
	XHR.open("GET", requestUrl, false) ;
	XHR.send(null) ;

}

function abandonCancel() {
	document.getElementById('confirmCancellation').style.visibility = 'hidden' ;
	document.getElementById('confirmCancellation').style.display = 'none' ;
	document.getElementById('bookingTable').style.visibility = 'visible' ;
	document.getElementById('bookingTable').style.display = 'table' ;
	document.getElementById('cancelButton').style.visibility = 'visible' ;
	document.getElementById('cancelButton').style.display = 'inline' ;
}

function cancelFirstClick () {
	document.getElementById('confirmCancellation').style.visibility = 'visible' ;
	document.getElementById('confirmCancellation').style.display = 'block' ;
	document.getElementById('bookingTable').style.visibility = 'hidden' ;
	document.getElementById('bookingTable').style.display = 'none' ;
	document.getElementById('cancelButton').style.visibility = 'hidden' ;
	document.getElementById('cancelButton').style.display = 'none' ;
}

function createBooking() {
	var plane = document.getElementById("planeSelect").value ;
	var bookingStart = document.getElementById("startDayInput").value ;
	bookingStart += ' ' + document.getElementById("startHourInput").value + ":00";
	var bookingEnd = document.getElementById("endDayInput").value ;
	bookingEnd += ' ' + document.getElementById("endHourInput").value + ":00";
	var comment = document.getElementById("commentTextArea").value ;
	var pilotId = document.getElementById("pilotSelect").value ;
	var instructorId = document.getElementById("instructorSelect").value ;
	var departingAirport = document.getElementById("departingAirport").value ;
	var destinationAirport = document.getElementById("destinationAirport").value  ;
	var via1Airport = document.getElementById("via1Airport").value  ;
	var via2Airport = document.getElementById("via2Airport").value  ;
	var flightDuration = document.getElementById("flightDuration").value  ;

	var XHR=new XMLHttpRequest();

if (false) {
	// Check whether a solo flight is allowed on this plane
	if (!userIsInstructor && instructorId <= 0) {
		for (var i = 0; i < allPlanes.length; i++)
			if (allPlanes[i].id == plane) {
				if (!allPlanes[i].qualifications_requises)
					if (!confirm("Au vu de votre carnet de vol et de vos validités/annotations club, vous n'avez pas la possibilité de réserver cet avion.\nVoulez-vous malgré tout réserver?\n\n" +
						"OK pour continuer (les instructeurs seront prévenus), Cancel pour ne pas réserver")) {
						hideEditBookingDetails() ;
						return ; 
						}
			}
	}
	
	// Check whether a flight duration has been set
	if (flightDuration == '') {
		alert("Vous devez entrer une estimation de la dur&eacute;e du vol") ;
		return ;
	}
}
	XHR.onreadystatechange = function() {
		if(XHR.readyState  == 4) {
			if(XHR.status  == 200) {
				try {
					var response = eval('(' + XHR.responseText.trim() + ')') ;
				} catch(err) {
					return ;
				}
				if (response.error != '') {
					document.getElementById("bookingMessageSpan").innerHTML = response.error ;
					document.getElementById("bookingMessageDiv").className = 'alert alert-warning alert-dismissible' ;
				} else {
					document.getElementById("bookingMessageSpan").innerHTML = response.message ;
					document.getElementById("bookingMessageDiv").className = 'alert alert-success  alert-dismissible' ;
				}
				document.getElementById("bookingMessageDiv").style.visibility = 'visible' ;
				document.getElementById("bookingMessageDiv").style.display = 'block' ;
			}
		}
	}
	if (userIsInstructor)
		bookingType = bookingTypeInstructor ;
	else if (userIsAdmin)
		bookingType = bookingTypeAdmin ;
	else
		bookingType = bookingTypePilot ;
	var requestUrl = "create_booking.php?plane=" + plane + '&pilotId=' + pilotId +  '&instructorId=' + instructorId +
		'&start=' + bookingStart + '&end=' + bookingEnd +
		'&type=' + bookingType + '&comment=' + comment + '&fromApt=' + departingAirport + '&toApt=' + destinationAirport +
		'&via1Apt=' + via1Airport + '&via2Apt=' + via2Airport +
		'&duration=' + flightDuration ;
	XHR.open("GET", requestUrl, false) ;
	XHR.send(null) ;
}

function modifyBooking(id, auth) {
	var plane = document.getElementById("planeSelect").value ;
	var bookingStart = document.getElementById("startDayInput").value ;
	bookingStart += ' ' + document.getElementById("startHourInput").value + ":00";
	var bookingEnd = document.getElementById("endDayInput").value ;
	bookingEnd += ' ' + document.getElementById("endHourInput").value + ":00";
	var comment = document.getElementById("commentTextArea").value ;
	var pilotId = document.getElementById("pilotSelect").value ;
	var instructorId = document.getElementById("instructorSelect").value ;
	var departingAirport = document.getElementById("departingAirport").value ;
	var destinationAirport = document.getElementById("destinationAirport").value  ;
	var via1Airport = document.getElementById("via1Airport").value  ;
	var via2Airport = document.getElementById("via2Airport").value  ;
	var flightDuration = document.getElementById("flightDuration").value  ;

	var XHR=new XMLHttpRequest();
	XHR.onreadystatechange = function() {
		if(XHR.readyState  == 4) {
			if(XHR.status  == 200) {
				try {
					var response = eval('(' + XHR.responseText.trim() + ')') ;
				} catch(err) {
					return ;
				}
				if (response.error != '') {
					document.getElementById("bookingMessageSpan").innerHTML = response.error ;
					document.getElementById("bookingMessageDiv").className = 'alert alert-warning alert-dismissible' ;
				} else {
					document.getElementById("bookingMessageSpan").innerHTML = response.message ;
					document.getElementById("bookingMessageDiv").className = 'alert alert-success  alert-dismissible' ;
				}
				document.getElementById("bookingMessageDiv").style.visibility = 'visible' ;
				document.getElementById("bookingMessageDiv").style.display = 'block' ;
			}
		}
	}
	var requestUrl = "modify_booking.php?booking=" + id + "&plane=" + plane + '&pilotId=' + pilotId + '&instructorId=' + instructorId +
		'&start=' + bookingStart + '&end=' + bookingEnd +
		'&comment=' + comment + '&fromApt=' + departingAirport + '&toApt=' + destinationAirport +
		'&via1Apt=' + via1Airport + '&via2Apt=' + via2Airport +
		'&duration=' + flightDuration ;
	XHR.open("GET", requestUrl, false) ;
	XHR.send(null) ;
}

function prefillDropdownMenus(selectName, valuesArray, selectedValue) {
	var select = document.getElementsByName(selectName)[0] ;

	for (var i = 0; i < valuesArray.length; i++) {
		var option = document.createElement("option");
		if ('last_name' in valuesArray[i])
			option.innerHTML = valuesArray[i].last_name + ' ' + valuesArray[i].first_name ;
		else
			option.innerHTML = valuesArray[i].name ;
		option.value = valuesArray[i].id ;
		if (valuesArray[i].id == selectedValue) {
			option.selected = true ;
			option.innerHTML = option.innerHTML + '    &#8964;' ;
		} else {
			option.selected = false ;
		}
		select.add(option) ;
	}
}

function initBook() { // Only used by mobile_book
	prefillDropdownMenus('plane', planes, planeId) ;
	prefillDropdownMenus('pilot', pilots, pilotId) ;
	prefillDropdownMenus('instructor', instructors, instructorId) ;
}

function init() {
	// Fill in the Planes dropdown menu with the content of planes.js
	var planesDropdown = document.getElementById('planesDropdown') ;
	if (planesDropdown) {
		for (var plane = 0; plane < planes.length; plane++) {
			var option = document.createElement('li');
			option.innerHTML = '<a class="dropdown-item" href="mobile_plane.php?plane=' + planes[plane].name + '">' + planes[plane].name;
			planesDropdown.appendChild(option) ;
		}
	}

	var pilotSelect = document.getElementById('pilotSelect') ;
	if (pilotSelect) {
	// Dropdown selected the pilot
		if (userIsInstructor || userIsAdmin) {
				// Initiliaze pilotSelect from members.js
				for (var member = 0; member < members.length; member++) {
						var option = document.createElement("option");
						if (members[member].last_name == '')
								option.innerHTML = members[member].name ;
						else
								option.innerHTML = members[member].last_name + ', ' + members[member].first_name ;
						if (members[member].student) {  // Add a student icon
								option.innerHTML += ' &#x1f4da;' ;
						}
						option.value = members[member].id ;
						pilotSelect.add(option) ;
				}
		}
		pilotSelect.value = selectedUserId ;
	}
}