//   Copyright 2014-2021 Eric Vyncke
//
//   Licensed under the Apache License, Version 2.0 (the "License");
//  you may not use this file except in compliance with the License.
//   You may obtain a copy of the License at
//
//       http://www.apache.org/licenses/LICENSE-2.0
//
//   Unless required by applicable law or agreed to in writing, software
//   distributed under the License is distributed on an "AS IS" BASIS,
//   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//   See the License for the specific language governing permissions and
//   limitations under the License.

// TODO:
// - copyright & disclaimer
// - AJAX pour aéroport départ/destination
//
// l1610 allow logbook as soon as booking has started ?

var
	planePlanningTable, instructorPlanningTable, planningDate, columnCount, allBookings, allFIAgendas, ephemerides = new Array(), nightColumn,
	aeroSunsetHours, aeroSunsetMinutes, currentlyDisplayedBooking, currentlyDisplayedAgendaItem, timeZoneChecked = false,
	planningByPlane = false, planningPlaneIndex, planningStartHour, planningStopHour, timestampTicks = 0,
	planningDay = nowDay, planningMonth = nowMonth, planningYear = nowYear, id2Name = new Array(),
	metarHTML, metarTime = 0, metarBackgroundColor, offsetBrowserAirfield,
	dayMessagesHTML = '' ;

var // Was 'const' but IE does not support it
	weekdays = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
	browserWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth, // This is the iframe width 950 px
	browserHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight; // This is the iframe height 700 px

// Same function exists for PHP code
function planeClassIsMember(member, group) {
        if (member == group)
		return true ;
        switch (group) {
        case 'C182':
                return member == 'C150' || member == 'C172' || member == 'C172VP' ;
                break ;
        case 'C172VP':
                return member == 'C150' || member == 'C172' ;
                break ;
        case 'C172':
                return member == 'C150' ;
                break ;
        }
        return false ;
}

function isInThePast(planningYear, planningMonth, planningDay, planningHour, planningMinute) {
	if (planningYear < nowYear) return true ;
	if (planningYear > nowYear) return false ;
	if (Number(planningMonth) < Number(nowMonth)) return true ;
	if (Number(planningMonth) > Number(nowMonth)) return false ;
	if (Number(planningDay) < Number(nowDay)) return true ;
	if (Number(planningDay) > Number(nowDay)) return false ;
	var planningMinutes = planningHour * 60 + planningMinute ;
	var nowMinutes = nowHour * 60 + nowMinute ;
	return planningMinutes <= nowMinutes ;
}

function isToday() {
	return (planningDay == nowDay) && (planningMonth == nowMonth) && (planningYear == nowYear) ;
}

function isNow(planningYear, planningMonth, planningDay, planningHour, planningMinute) {
	if (Number(planningDay) != Number(nowDay)) return false ;
	if (Number(planningMonth) != Number(nowMonth)) return false ;
	if (planningYear != nowYear) return false ;
	var planningMinutes = planningHour * 60 + planningMinute ;
	var nowMinutes = nowHour * 60 + nowMinute ;
	if (nowMinutes < planningMinutes) return false ;
	if (nowMinutes < planningMinutes + 15) return true ;
	return false
}

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

// Functions to split an booking composite ID into booking number and logging number
function bookingFromID(id) {
	var elems = id.split('-') ;
	return elems[0] ;
}

function loggingFromID(id) {
	if (id.includes('-')) {
		var elems = id.split('-') ;
		return elems[1] ;
	} else {
		console.log('loggingFromID(' + id + ') has no hyphen') ;
		return 0 ;
	}
}

function buttonHide(id) {
	if (document.getElementById(id) === null) return ; // Some buttons are not enabled for some classes of users
        document.getElementById(id).disabled = true ;
        document.getElementById(id).style.display = 'none' ;
}

function buttonDisplay(id) {
	if (document.getElementById(id) === null) return ; // Some buttons are not enabled for some classes of users
        document.getElementById(id).disabled = false ;
        document.getElementById(id).style.display = 'inline' ;
}

function buttonDisplayIf(id, cond) {
	if (cond)
		buttonDisplay(id) ;
	else
		buttonHide(id) ;
}

function hideBookingMessage() {
	// Clear any booking message
	document.getElementById("bookingMessageDiv").style.visibility = 'hidden' ;
}

function refreshWebcam() {
	if (document.getElementById('webcamImg')) // Sometimes when content cannot be fetched, this element disappears!
		document.getElementById('webcamImg').src = webcamUris[nowMinute % webcamUris.length] ;
	document.getElementById('webcamURI').href = webcamUris[nowMinute % webcamUris.length] ;
	setTimeout(refreshWebcam, 1000 * 60) ; // Refresh every 60 seconds
}

function displayClock() {
	document.getElementById('hhmmLocal').innerHTML = ('0' + nowHour).substr(-2, 2) + ':' + ('0' + nowMinute).substr(-2, 2) ;
	if (nowHour - utcOffset > 0)
		document.getElementById('hhmmUTC').innerHTML = ('0' + (nowHour - utcOffset)).substr(-2, 2) + ':' + ('0' + nowMinute).substr(-2, 2) ;
	else
		document.getElementById('hhmmUTC').innerHTML = ('0' + (24 + nowHour - utcOffset)).substr(-2, 2) + ':' + ('0' + nowMinute).substr(-2, 2) ;
}

function refreshPlanningTableHeader() {
	var rowHeader, i, hour, minute ;

	// Build the top row with cells per 15 minutes
        rowHeader = planePlanningTable.rows[0];
        for (i = 1, hour = planningStartHour, minute = 0 ; hour < planningStopHour; i++, minute += 15) {
		if (minute >= 60) {
			minute = 0 ;
			hour ++ ;
		}
		if (minute == 0) {
			if (hour >= planningStopHour) {
				rowHeader.cells[i].className = 'header_last_hour' ;
				break ;
			}
		}
		if (isNow(planningYear, planningMonth, planningDay, hour, minute))
			rowHeader.cells[i].style.backgroundColor = 'orange' ;
		else
			rowHeader.cells[i].style.backgroundColor = 'white' ;
        }
}

var waitingCount = 0 ;

function displayWaiting() {
	if (waitingCount++ == 0) {
		document.getElementById('waitingDiv').style.visibility = 'visible' ;
		document.getElementById('waitingDiv').style.display = 'block' ;
		document.getElementById('waitingDiv').style.position = 'absolute' ; 
		document.getElementById('waitingDiv').style.top = browserHeight / 2 - 128; 
		document.getElementById('waitingDiv').style.left = browserWidth / 2 - 128; 
	}
}

function hideWaiting() {
	if (--waitingCount == 0) {
		document.getElementById('waitingDiv').style.visibility = 'hidden' ;
	}
}

function refreshTimestamp() {
	setTimeout(refreshTimestamp, 1000 * 60) ; // Refresh every 60 seconds
	nowTimestamp += 60 ;
	var nowDate = new Date(nowTimestamp * 1000) ;
	nowHour = nowDate.getHours() ;
	nowMinute = nowDate.getMinutes() ;
	displayClock() ;
	timestampTicks ++ ;
	if (nowMinute % 15 == 0) {
		refreshPlanningTableHeader() ;
		timestampTicks = 0 ; // Synchronize the top row and bottom refresh
		return ;
	}
	if (timestampTicks % 5 == 0) refreshPlanningTable() ;
}

function roadBookClick() {
	window.location.href = 'planelog.php?plane=' + allPlanes[planningPlaneIndex].id ;
}

function toggleLogDisplay () {
	var button = document.getElementById('logButton') ;

	if (button.value == 'No log') {
		button.value = 'Log' ;
		document.getElementById('logDiv').style.visibility = 'hidden' ;
		document.getElementById('logDiv').style.display = 'none' ;
	} else {
		button.value = 'No log' ;
		document.getElementById('logDiv').style.visibility = 'visible' ;
		document.getElementById('logDiv').style.display = 'block' ;
	}
}

function toggleInstructorAgenda() {
	var span = document.getElementById('toggleInstructorAgendaSpan') ;

	if (span.innerHTML[0] == '-') {
		span.innerHTML = '+' + span.innerHTML.substr(1) ;
               // hide instructorPlanningTable
                instructorPlanningTable.style.visibility = 'hidden' ;
                instructorPlanningTable.style.display = 'none' ;
	} else {
		span.innerHTML = '-' + span.innerHTML.substr(1) ;
               // hide instructorPlanningTable
                instructorPlanningTable.style.visibility = 'visible' ;
                instructorPlanningTable.style.display = 'table' ;
	}
}

function myLog(msg) {
	var currentDate = new Date();
	var timeString = currentDate.getHours() + ':' + currentDate.getMinutes() + ":" + currentDate.getSeconds() + '.' + currentDate.getMilliseconds() + ': ' ;

	// userId == 62 pour Eric Vyncke
	// userId == 46 pour Benoit Mendes
	// userId == 67 pour Pierre-François Lefebvre
	// userId == 115 pour J Ph Delhez
//	if (userId == 62 || userId == 46) {
//	if (userId == 67 || userId == 62) {
	if (false) {
i//	if (userId == 62) { // Eric Vyncke
		document.getElementById('logButton').style.visibility = 'visible' ;
		var logDiv = document.getElementById('logDiv') ;
		if (document.getElementById('logButton').value == 'No log')
			logDiv.style.visibility = 'visible' ;
		logDiv.innerHTML += timeString + msg + '<br/>' ;
	}
}

function fillSelectHourOptions(id) {
	var selectElem = document.getElementById(id) ;
	var optionCount = selectElem.options.length ;
	while(optionCount)
		selectElem.remove(--optionCount);
	for (var hour = planningStartHour; hour <= planningStopHour ; hour++) {
		var option = document.createElement('option') ;
		option.text = hour ;
		option.value = hour ;
		selectElem.add(option) ;
	}
}

// Called when the plane/ressource is changed during the booking
function ressourceHasChanged(thisSelect) {
	var plane = thisSelect.value ; // Get the new plane...
	document.getElementById('planeComment').innerHTML = "" ;
	for (var i = 0; i < allPlanes.length; i++) {
		if (allPlanes[i].id == plane) {
			if (allPlanes[i].photo != '') {
				document.getElementById('webcamImg').src = allPlanes[i].photo ;
				document.getElementById('webcamURI').href = allPlanes[i].photo ;
			}
			if (allPlanes[i].actif == 2)
				document.getElementById('planeComment').innerHTML += "<br/><span style=\"color: red\"><b>\nCet avion est r&eacute;serv&eacute; aux instructeurs et &agrave; leurs &eacute;l&egrave;ves.\n</b></span>\n" ;
			if (allPlanes[i].commentaire)
				document.getElementById('planeComment').innerHTML += "<br/><fspan style=\"color: red\">\n" + allPlanes[i].commentaire + "</span>\n" ;
		}
	}
}

function displayDayMessages() {
	if (isToday())
		displayMETAR() ;
	else if (dayMessagesHTML.length == 0) {
		document.getElementById('reservationDetails').innerHTML = '' ;
		document.getElementById('reservationDetails').style.visibility = 'hidden' ;
	} else {
		document.getElementById('reservationDetails').innerHTML = dayMessagesHTML ;
		document.getElementById('reservationDetails').style.backgroundColor = 'lightBlue' ;
		document.getElementById('reservationDetails').style.visibility = 'visible' ;
	}
}

function displayMETAR() {
// Let's try to have a little cache
	var timeNow ;
	var elem = document.getElementById('reservationDetails') ;
	
	timeNow = new Date().getTime() ; // in msec
//	console.log("Start of displayMETAR(), timeNow=" + timeNow + ", metarTime=" + metarTime) ;
//	console.trace() ;
	if (metarTime < 0) { // Another request is pending
//		console.log("End of displayMETAR() aborting as another request is pending") ;
		return ;
	}
	if (timeNow < metarTime + 1000 * 60) { // Allow the caching for 1 minute
		elem.innerHTML = metarHTML ;
		elem.style.backgroundColor = metarBackgroundColor ;
//		console.log("End of displayMETAR() re-using cache") ;
		return ;
	}
// TODO as this is asynchronous...
//	displayWaiting() ;
	var XHR=new XMLHttpRequest();
//	XHR.timeout = 2000 ; // 2000 msec, cannot set time on synchronous requests
	XHR.onreadystatechange = function() {
		if(XHR.readyState  == 4) {
			if(XHR.status  == 200) {
				console.log("displayMETAR() call-back") ;
				try {
					var metar_response = eval('(' + XHR.responseText.trim() + ')') ;
				} catch(err) {
					return ;
				}
				var elem = document.getElementById('reservationDetails') ;
				if (metar_response.error != '') {
					elem.innerHTML =  metar_response.error ;
				} else {
					elem.innerHTML = '<b>' +  metar_response.METAR + '</b>' ;
					if (metar_response.temperature &&  metar_response.dew_point && (( metar_response.temperature -  metar_response.dew_point) <= 10))
						elem.innerHTML += '<br/>Relative humidity: ' +
							Math.round(100.0 - 5.0 * ( metar_response.temperature - metar_response.dew_point)) + '%' ;
					if (metar_response.clouds_base) {
						elem.innerHTML += '<br/>Theorical clouds base at ' +  metar_response.station + ': ' +  metar_response.clouds_base + ' ft' ;
					}
					if (metar_response.density_altitude) {
						elem.innerHTML += '<br/>Density altitude at ' +  metar_response.station + ': ' +  metar_response.density_altitude +
							' ft, elevation: ' +  metar_response.elevation + ' ft';
						if ( metar_response.density_altitude >  metar_response.elevation + 1500)
							elem.innerHTML += '<br/><span style="color: red; font-size: x-large;">Caution, high density altitude!!!</span>' ;
					}
					if (metar_response.pressure_altitude) {
						elem.innerHTML += '<br/>Pressure altitude at ' +  metar_response.station + ': ' +  metar_response.pressure_altitude + ' ft' ;
					}
					if (metar_response.condition != null &&  metar_response.condition == 'VMC')
						elem.style.backgroundColor =  'paleGreen' ;
					else if ( metar_response.condition != null &&  metar_response.condition == 'MMC')
						elem.style.backgroundColor = 'orange' ;
					else if ( metar_response.condition != null &&  metar_response.condition == 'IMC')
						elem.style.backgroundColor = 'pink' ;
					else
						elem.style.backgroundColor = 'lightGray' ;
					if (metar_response.wind_velocity != null &&  metar_response.wind_direction != null &&  metar_response.wind_direction != 'VRB' && runwaysQFU.length > 0) {
						elem.innerHTML += '<br/><b>Wind components</b>' ;
						var ul = document.createElement('ul');
						elem.appendChild(ul);
						ul.style.display = 'inherit' ; // CSS magic to avoid empty space before first list item
						for (i = 0; i < runwaysQFU.length ; i++) {
							var qfuWindAngle = (runwaysQFU[i] -  metar_response.wind_direction) *  2 * Math.PI / 360 ; // In radians
							var qfuComponent =  Math.round(metar_response.wind_velocity * Math.cos(qfuWindAngle)) ;
							var crossComponent =  Math.round(metar_response.wind_velocity * Math.sin(qfuWindAngle)) ;
							if (metar_response.wind_gust) {
								var qfuGustComponent =  Math.round(metar_response.wind_gust * Math.cos(qfuWindAngle)) ;
								var crossGustComponent =  Math.round(metar_response.wind_gust * Math.sin(qfuWindAngle)) ;
							}
							var li = document.createElement('li') ; 
							ul.appendChild(li) ;
							li.innerHTML = 'Runway ' + Math.round(runwaysQFU[i]/10) + ': ' ;
							if (qfuComponent >= 0) {
								li.innerHTML += 'headwind = ' + qfuComponent ;
								if (metar_response.wind_gust) li.innerHTML +=  ' g' + qfuGustComponent ;
								li.innerHTML +=  ' kt, ' ;
							} else {
								li.innerHTML += 'tailwind = ' + (-qfuComponent) ;
								if (metar_response.wind_gust) li.innerHTML +=  ' g' + (-qfuGustComponent) ;
								li.innerHTML +=  ' kt, ' ;
							}
							if (crossComponent >= 0) {
								li.innerHTML += 'left crosswind = ' + crossComponent  ;
								if (metar_response.wind_gust) li.innerHTML +=  ' g' + crossGustComponent ;
								li.innerHTML +=  ' kt' ;
							} else {
								li.innerHTML += 'right crosswind = ' + (-crossComponent) ;
								if (metar_response.wind_gust) li.innerHTML +=  ' g' + (-crossGustComponent) ;
								li.innerHTML +=  ' kt' ;
							}
							if (metar_response.wind_gust && Math.abs(crossGustComponent) > 15)
								li.innerHTML += ' <b>!Caution!</b>' ;
							else if (Math.abs(crossComponent) > 15)
								li.innerHTML += ' <b>!Caution!</b>' ;
						}
					}
					if (isToday()) 
						setTimeout(displayMETAR, 1000 * 60 * 5) ; // Refresh every 5 minutes
					metarTime = timeNow ;
					metarHTML = elem.innerHTML ;
					metarBackgroundColor = elem.style.backgroundColor ;
				}
			} // status == 200
//			hideWaiting() ;
		} // readyState == 4
	} // function
	var requestUrl = 'metar/' + defaultMetarStation ;
	XHR.open("GET", requestUrl, true) ;
	metarTime = -1 ; // Used as a flag to signal a request is pending
//	XHR.open("GET", requestUrl, false) ; // We need to be have a synchronous request else METAR competes with detailed booking information :-(
	// TODO try/catch to handle exceptions
	XHR.send(null) ;
//	console.log("End of displayMETAR()") ;
}

function airportChanged(elem) {
	var code = elem.value ;

	if (code.length < 3) return ;

return ; // Waiting for the AJAX service to be added and finding a way to display/use the information...
	var XHR=new XMLHttpRequest();
	XHR.onreadystatechange = function() {
		if(XHR.readyState  == 4) {
			if(XHR.status  == 200) {
				try {
					var response = eval('(' + XHR.responseText.trim() + ')') ;
				} catch(err) {
					return ;
				}
			}
		}
	}
	XHR.open("GET", "get_airports.php?code=" + code, true) ;
        XHR.send(null) ;

}

function showPilotDetails(id) {
	document.getElementById("pilotDetailsDiv").style.visibility = 'visible' ;
	document.getElementById("pilotDetailsDiv").style.top = 300 ;
	document.getElementById("pilotDetailsDiv").style.left = 300 ;
	var span = document.getElementById("pilotDetailsSpan") ;
	// Reset the picture in the div
	document.getElementById("pilotDetailsImage").src = '' ;
	document.getElementById("pilotDetailsImage").style.display = 'none' ;

	var booking = allBookings[bookingFromID(id)][loggingFromID(id)] ;
	if (booking.log_pilot != 0 && booking.log_pilot != booking.user) { // The booked pilot is not the same as in logbook and there is a logbook entry
		span.innerHTML = '<b>' + booking.log_pilotName + "</b>" ;
		span.innerHTML += '<a href="vcard.php?id=' + booking.log_pilot + '"><i class="material-icons" style="font-size:18px;color:blue;">cloud_download</i></a>' ;
		span.innerHTML += '<br>Vol réserve par: ' + booking.name ;
	} else { // No logbook information (possibly future reservation)
		span.innerHTML = '<b>' + booking.name + "</b>" ;
		if (booking.user)
			span.innerHTML += '<a href="vcard.php?id=' + booking.user + '"><i class="material-icons" style="font-size:18px;color:blue;">cloud_download</i></a>' ;
		if (booking.cell_phone)
			span.innerHTML += '<br/>Mobile: <a href="tel:' + booking.cell_phone + '">' + booking.cell_phone + '</a>';
		if (booking.home_phone)
			span.innerHTML += '<br/>Priv&eacute;: <a href="tel:' + booking.home_phone + '">' + booking.home_phone + '</a>';
		if (booking.work_phone)
			span.innerHTML += '<br/>Prof.: <a href="tel:' + booking.work_phone + '">' + booking.work_phone + '</a>';
		if (booking.email)
			span.innerHTML += '<br/>Email: <a href="mailto:' + booking.email + '">' + booking.email + '</a>' ;
		if (booking.avatar) {
			document.getElementById("pilotDetailsImage").src = booking.avatar ;
			document.getElementById("pilotDetailsImage").style.visibility = 'inherited' ;
			document.getElementById("pilotDetailsImage").style.display = 'inline' ;
		} else {
			document.getElementById("pilotDetailsImage").src = 'https://www.gravatar.com/avatar/' + booking.gravatar + '?s=80&d=blank&r=pg' ;
			document.getElementById("pilotDetailsImage").style.visibility = 'inherited' ;
			document.getElementById("pilotDetailsImage").style.display = 'inline' ;
		}
	}
	if (booking.instructorId > 0) {
		span.innerHTML += '<hr><b>' + booking.instructorName + "</b>" ;
		span.innerHTML += '<a href="vcard.php?id=' + booking.instructorId + '"><i class="material-icons" style="font-size:18px;color:blue;">cloud_download</i></a>' ;
		if (booking.instructorCellPhone)
			span.innerHTML += '<br/>Mobile: <a href="tel:' + booking.instructorCellPhone + '">' + booking.instructorCellPhone + '</a>';
		if (booking.instructorHomePhone)
			span.innerHTML += '<br/>Priv&eacute;: ' + booking.instructorHomePhone ;
		if (booking.instructorWorkPhone)
			span.innerHTML += '<br/>Prof.: ' + booking.instructorWorkPhone ;
		if (booking.instructorEmail)
			span.innerHTML += '<br/>Email: <a href="mailto:' + booking.instructorEmail + '">' + booking.instructorEmail + '</a>' ;
	}
}

function hidePilotDetails() {
	// Clear any booking message
	document.getElementById("pilotDetailsDiv").style.visibility = 'hidden' ;
}

function createPlanningTableHeader(planningTable) {
	var hour, minute, i ;

	// Build the top row with cells per 15 minutes
    rowHeader = planningTable.insertRow(0);
	// Add the first column for the header
	rowHeader.insertCell(0) ;
	rowHeader.cells[0].style.display= '' ;
	rowHeader.cells[0].innerHTML= '' ;
    // Add enough 'time' cells to this new row
	// TODO create a tbody element???
	nightColumn = 1 ;
    for (i = 1, hour = planningStartHour, minute = 0 ; hour < planningStopHour; i++, minute += 15) {
               rowHeader.insertCell(i) ;
		if (minute >= 60) {
			minute = 0 ;
			hour ++ ;
		}
		if (minute == 0) {
			rowHeader.cells[i].innerHTML = hour ;
			if (hour >= planningStopHour) {
				rowHeader.cells[i].className = 'header_last_hour' ;
				break ;
			}
			rowHeader.cells[i].className = 'header_hour' ;
		} else {
			rowHeader.cells[i].innerHTML = '' ; // minute ;
			rowHeader.cells[i].className = 'header_not_hour' ;
		}
		if (hour <= aeroSunsetHours && minute < aeroSunsetMinutes)
			nightColumn = i ;
    }
	return i ; // Save how many time cells
}

function initPlanningTable() {
	var header, rowHeader, hour, minute, i, newRow;

	myLog("start initPlanningTable() planningHours = " + planningStartHour + '-' + planningStopHour) ;
	planePlanningTable = document.getElementById('planePlanningTable') ;
	instructorPlanningTable = document.getElementById('instructorPlanningTable') ;
	// As there could be a previous version on the screen with a different layout
	var rowCount = planePlanningTable.rows.length ;
	while(rowCount)
		planePlanningTable.deleteRow(--rowCount);
	rowCount = instructorPlanningTable.rows.length ;
	while(rowCount)
		instructorPlanningTable.deleteRow(--rowCount);
//        header = planePlanningTable.createTHead() ;

	columnCount = createPlanningTableHeader(planePlanningTable) ;
	createPlanningTableHeader(instructorPlanningTable) ;

	// Fill the body of the table
	if (planningByPlane) { // Only one plane is displayed but for several days
		// For 7 days, create empty cells
		for (var day = 0; day < 7;  day++) {
			newRow = planePlanningTable.insertRow(-1) ;
			newRow.insertCell(0) ;
			newRow.cells[0].className = 'plane_cell' ;
			for (i = 1; i < columnCount; i ++) {
				newRow.insertCell(i).className = 'available' ; // Would be nice to care for available_for_fi as well...
			}
		}
		// hide instructorPlanningTable
		instructorPlanningTable.style.visibility = 'hidden' ;
		instructorPlanningTable.style.display = 'none' ;
	} else { // All planes are displayed but for a single day
		// For all active planes & ressources, create empty cells
		for (var plane = 0; plane < allPlanes.length; plane++) {
			newRow = planePlanningTable.insertRow(-1) ;
			if (allPlanes[plane].actif == 0) { // Plane is not active => hide this row
				newRow.style.visibility = 'hidden' ;
				newRow.style.display = 'none' ;
			}
			newRow.insertCell(0) ;
			newRow.cells[0].className = 'plane_cell' ;
			for (i = 1; i < columnCount; i ++) {
				switch (allPlanes[plane].actif) {
					case 0: newRow.insertCell(i).className = 'maintenance' ; break ; // Anyway, this row is hidden
					case 1: newRow.insertCell(i).className = (allPlanes[plane].ressource != 0) ? 'ressource_available' : 'available' ; break ;
					case 2: newRow.insertCell(i).className = 'available_for_fi' ; break ;
				}
			}
		}
		// For all instructors, create empty cells
		instructorPlanningTable.style.visibility = 'visible' ;
		instructorPlanningTable.style.display = 'table' ;
		for (var instructor = 1; instructor < instructors.length; instructor++) { // Instructors[0] is always 'solo'
			newRow = instructorPlanningTable.insertRow(-1) ;
			newRow.insertCell(0) ;
			newRow.cells[0].className = 'plane_cell' ;
			for (i = 1; i < columnCount; i ++) {
				newRow.insertCell(i).className = 'available' ;
			}
		}
	}
	refreshPlanningTableHeader() ;
	myLog("end initPlanningTable()") ;
}

function jumpPlanningDate() {
	var tokens = document.getElementById('planningDate').value.split('/') ;
	var workDate = new Date(tokens[2], tokens[1] - 1 , tokens[0], 0, 0, 0, 0) ;
	planningDay = workDate.getDate() ;
	planningDayOfWeek = workDate.getDay() ;
	planningMonth = workDate.getMonth() + 1 ; // Stupid JS starts month at 0 !
	planningYear = workDate.getFullYear() ;
    if (isToday()) { // If today, leave the DIV open for METAR or open it
        displayMETAR() ;
		document.getElementById('reservationDetails').style.visibility = 'visible' ;
     } else {
		hideEditBookingDetails() ;
     }
	hideBookingMessage() ;
	hidePilotDetails() ;
	refreshEphemerides() ;
	initPlanningTable() ; // Need to rebuild the table as opening/closing hours may have changed
	refreshPlanningTable() ;
}


function bumpPlanningBy(n) {
	var workDate = new Date(planningYear, planningMonth - 1, planningDay, 0, 0, 0, 0) ;
	workDate.setDate(workDate.getDate() + n) ;
	planningDay = workDate.getDate() ;
	planningDayOfWeek = workDate.getDay() ;
	planningMonth = workDate.getMonth() + 1 ; // Stupid JS starts month at 0 !
	planningYear = workDate.getFullYear() ;
	hideBookingMessage() ;
    if (isToday()) { // If today, leave the DIV open for METAR or open it
        displayMETAR() ;
		document.getElementById('reservationDetails').style.visibility = 'visible' ;
    } else {
		hideEditBookingDetails() ;
	}
	document.body.style.backgroundImage = 'none' ;
	hidePilotDetails() ;
	refreshEphemerides() ;
	initPlanningTable() ; // Need to rebuild the table as opening/closing hours may have changed
	refreshPlanningTable() ;
}

function refreshEphemerides() {
	displayWaiting() ;
	var XHR=new XMLHttpRequest();
	XHR.onreadystatechange = function() {
		if(XHR.readyState  == 4) {
			if(XHR.status  == 200) {
				try {
					var response = eval('(' + XHR.responseText.trim() + ')') ;
				} catch(err) {
					hideWaiting() ;
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
				ephemeridesTable.rows[0].cells[1].innerHTML = myDateGetHoursMinutes(ephemerides.aero_sunrise) ;
				ephemeridesTable.rows[1].cells[1].innerHTML = myDateGetHoursMinutes(ephemerides.sunrise) ;
				ephemeridesTable.rows[2].cells[1].innerHTML = myDateGetHoursMinutes(ephemerides.airport_open) ;
				ephemeridesTable.rows[0].cells[3].innerHTML = myDateGetHoursMinutes(ephemerides.sunset) ;
				ephemeridesTable.rows[1].cells[3].innerHTML = myDateGetHoursMinutes(ephemerides.aero_sunset) ;
				ephemeridesTable.rows[2].cells[3].innerHTML = myDateGetHoursMinutes(ephemerides.airport_close) ;
				// Update global variables
				planningStartHour = Number(myDateGetHours(ephemerides.airport_open)) ;
				planningStopHour = Number(myDateGetHours(ephemerides.airport_close)) ;
				aeroSunsetHours = Number(myDateGetHours(ephemerides.aero_sunset)) ;
				aeroSunsetMinutes = Number(myDateGetMinutes(ephemerides.aero_sunset)) ;
				// Update all select options required an hour
				fillSelectHourOptions('startHourSelect') ;
				fillSelectHourOptions('endHourSelect') ;
				// TODO handle the case when ephemerides.airport_open.getTimezoneOffset() is not the one received by AJAX
				// The one to be trusted is the one from AJAX of course...
//				if (! timeZoneChecked) {
//					if (parseInt(response.timezone_offset) != -60 * ephemerides.airport_open.getTimezoneOffset())
//						alert("Attention, vous n'etes pas dans le fuseau horaire de l'aeroport. " +
//							"Toutes les heures sont en heures locales de chez vous.") ;
//					timeZoneChecked = true ;
//				}
				hideWaiting() ;
			}
		}
	}
	// Cannot use an asynchronous call as the night painting in the planning section requires to have the right ephemerides...
	// TODO or should we redo the painting of the planning section in the callback?
	try {
		XHR.open("GET", "get_ephemerides/" + planningYear + '/' + planningMonth + '/' + planningDay, false) ;
		XHR.send(null) ;
	} catch(err) {
		myLog('Cannot open() or send() in refreshEphemerides()...') ;
	}
}

// Long life to Javascript and its closure of variables...
function makeDisplayPlaneDetails(planeIndex) {
	return function () {
		displayPlaneDetails(planeIndex) ;
	}
}

// Called when mouse is over a plane having some information to be displayed
function displayPlaneDetails(planeIndex) {
	var span ;
	var thisPlane = allPlanes[planeIndex] ;

	span = document.getElementById('reservationDetails') ;
	span.innerHTML = '<b>' + thisPlane.id + '</b><br/>' ;
	if (thisPlane.commentaire) span.innerHTML += '<i>' + thisPlane.commentaire + '</i><hr>' ;
	if (thisPlane.ressource == 0) { // Engine index only valid for planes
		span.innerHTML += 'Compteur relev&eacute; par ' + thisPlane.compteur_pilote_nom + ' en date du ' + thisPlane.compteur_pilote_date + ': ' + thisPlane.compteur_pilote + '<br/>' ;
		span.innerHTML += 'Compteur relev&eacute; par le club en date du ' + thisPlane.compteur_date + ': ' + thisPlane.compteur  ;
		if (thisPlane.actif == 2)
			span.innerHTML += "<br/><span style=\"color: red\">\nCet avion est r&eacute;serv&eacute; aux instructeurs et &agrave; leurs &eacute;l&egrave;ves.\n</span>\n" ;
	}
	span.style.backgroundColor = 'lightGray' ;
	span.style.visibility = 'visible' ;
}

// Called when mouse is over an existing reservation
function displayBookingDetails(id) {
	var span, booking = allBookings[bookingFromID(id)][loggingFromID(id)] ;

	span = document.getElementById('reservationDetails') ;
	span.style.backgroundColor = 'lightGray' ; // METAR displayed in the same span can change the color
	span.innerHTML = '<b>' + booking.plane + '</b><br/>' ;
	span.innerHTML += "R&eacute;servation pour: " + booking.name ;
	span.innerHTML += "<br/>Faite le " + booking.bookedDate + '.';
	if (booking.user != booking.bookedById) {
		switch (Number(booking.type)) {
			case bookingTypeAdmin: bookerQuality = "administrateur web" ; break ;
			case bookingTypeInstructor: bookerQuality = "instructeur" ; break ;
			case bookingTypeMaintenance: bookerQuality = "m&eacute;cano" ; break ;
			case bookingTypePilot: bookerQuality = "pilote" ; break ;
			case bookingTypeCustomer: bookerQuality = "administrateur vols" ; break ;
			case bookingTypeOnHold: bookerQuality = "pilote" ; break ;
			default: bookerQuality = "??? erreur system" ;
		}
		span.innerHTML += '<br/>Effectu&eacute;e par ' + booking.bookedByName + ' en tant que ' + bookerQuality + '.' ;
	}
	span.innerHTML += '<br/>' ;
	if (booking.instructorId != -1)
		span.innerHTML += 'Instructeur vol: ' + booking.instructorName + '<br/>' ;
	span.innerHTML += 'R&eacute;servation du: ' + booking.start + ", " ;
	span.innerHTML += '&agrave;: ' + booking.end + " <i>(" + booking.duration + " heure(s) de vol)</i><br/>" ;
	if (booking.log_start) {
		span.innerHTML += 'Carnet de route: ' + booking.log_start + ", " ;
		span.innerHTML += '&agrave;: ' + booking.log_end + ' (' + booking.log_pilotName + ')<br/>'  ;
	}
	if (booking.type == bookingTypeMaintenance) 
		span.innerHTML += '<span style="color: red;">Maintenance</span><br/>' ;
	else if (booking.from != '' || booking.to != '') {
		span.innerHTML += 'De: ' + booking.from + ' &agrave; ' + booking.to + '<br/>' ;
		if (booking.via1 != '' || booking.via2 != '') {
			span.innerHTML += 'Via: ' + booking.via1 + ' et ' + booking.via2 + '<br/>' ;
		}
	}
	if (booking.comment)
		span.innerHTML += '<i>' + booking.comment + '</i><br/>';
	if (booking.crew_wanted != 0)
		span.innerHTML += '<span style="color: blue;">Pilote(s) supplémentaire(s) bienvenu(s).</span><br/>' ;
	if (booking.pax_wanted != 0)
		span.innerHTML += '<span style="color: blue;">Passager(s) bienvenu(s).</span><br/>' ;
	span.style.visibility = 'visible' ;
	// Replace webcam by plane photo
	for (var i = 0; i < allPlanes.length; i++) {
		if (allPlanes[i].id == booking.plane) {
			if (document.getElementById('webcamImg')) // As this element can disappear...
				document.getElementById('webcamImg').src = allPlanes[i].photo ;
		}
	}
}

// Called when radio buttons are changed
function agendaItemChanged(availability) {
	document.getElementById("agendaItemOnSite").disabled = availability  ;
	document.getElementById("agendaItemEmail").disabled = availability  ;
	document.getElementById("agendaItemPhone").disabled = availability  ;
	document.getElementById("agendaItemSMS").disabled = availability  ;
	document.getElementById("agendaItemStudentOnly").disabled = availability  ;
}

// Called when mouse is over an existing reservation
function displayAgendaItemDetails(id) {
	var span, item = allFIAgendas[id] ;

	span = document.getElementById('reservationDetails') ;
	span.style.backgroundColor = 'lightGray' ; 
	span.innerHTML = '<b>' + id2Name[allFIAgendas[id].fi] + '</b><br/>' ;
	if (allFIAgendas[id].callType < 0) {
		span.innerHTML += 'Est indisponible du ' + allFIAgendas[id].start + ' au ' + allFIAgendas[id].end + '<br/>' ;
	} else {
		span.innerHTML += 'Est disponible du ' + allFIAgendas[id].start + ' au ' + allFIAgendas[id].end + '<br/>' ;
		if (allFIAgendas[id].comment !== undefined)
			span.innerHTML += 'Commentaire: <i>' + allFIAgendas[id].comment + '</i><br/>' ;
		if (allFIAgendas[id].studentOnly !== undefined && allFIAgendas[id].studentOnly)
			span.innerHTML += '<b>Uniquement pour les &eacute;l&egrave;ves.</b><br/>' ;
		if (allFIAgendas[id].callType == 0x01)
			span.innerHTML += 'Disponible au clubhouse.<br/>' ;
		else {
			span.innerHTML += 'Rendez-vous &agrave; prendre ' ;
			var callTypes = new Array() ;
			if (allFIAgendas[id].callType & 0x01) callTypes.push('au clubhouse');
			if (allFIAgendas[id].callType & 0x02) callTypes.push('par email');
			if (allFIAgendas[id].callType & 0x04) callTypes.push('par t&eacute;l&eacute;phone');
			if (allFIAgendas[id].callType & 0x08) callTypes.push('par SMS');
			span.innerHTML += callTypes.join(', ') ;
			span.innerHTML += '<br/>' ;
		}
	}
	span.style.visibility = 'visible' ;
}

// Called when mouse is leaving an existing reservation
function clearBookingDetails() {
	if (! isToday()) { // If today, leave the DIV open for METAR, else display any 'crew/pax wanted' list
		displayDayMessages() ;
	} else
		displayMETAR() ;
	if (document.getElementById('webcamImg')) // As this element can disappear...
		document.getElementById('webcamImg').src = webcamUris[nowMinute % webcamUris.length] ;
	document.getElementById('webcamURI').href = webcamUris[nowMinute % webcamUris.length] ;
}

function cancelBooking(bookingIsForFlying) {
	var XHR=new XMLHttpRequest();
	
	displayWaiting() ;
	var reason = document.getElementById("reasonTextArea").value ;
	if (reason == '') {
	}
	XHR.onreadystatechange = function() {
		var reason = document.getElementById("reasonTextArea").value ; //Using it for logging...
		reason.innerHTML = "<b>Suivi de la demande d'annulation. A envoyer à eric@vyncke.org si cela ne fonctionne pas.</b><br/>" ;
		reason.innerHTML += "readyState: " + XHR.readyState + "<br/>" ;
		if(XHR.readyState  == 4) {
			hideWaiting() ;
			reason.innerHTML += "status: " + XHR.status + "<br/>" ;
			if(XHR.status  == 200) {
				try {
					var response = eval('(' + XHR.responseText.trim() + ')') ;
				} catch(err) {
					alert("Impossible d'analyser la réponse d'annulation, contactez eric@vyncke.org") ;
					return ;
				}
				if (response.error != '') {
					document.getElementById("bookingMessage").innerHTML = response.error ;
					document.getElementById("bookingMessage").className = 'bookingErrorMessage' ;
				} else {
					document.getElementById("bookingMessage").innerHTML = response.message ;
					document.getElementById("bookingMessage").className = null ;
				}
				document.getElementById("bookingMessageDiv").style.top = document.getElementById('bookingDiv').style.top ;
				document.getElementById("bookingMessageDiv").style.left = document.getElementById('bookingDiv').style.left ;
				document.getElementById("bookingMessageDiv").style.visibility = 'visible' ;
				refreshPlanningTable() ;
			} else {
				alert("La requête d'annulation a échoué (statut " + XHR.statusText + ") contactez eric@vyncke.org") ;
			}
		}
	}
	reason.innerHTML = "<b>Suivi de la demande d'annulation. A envoyer à eric@vyncke.org si cela ne fonctionne pas.</b><br/>" ;
	var requestUrl = "cancel_booking.php?id=" + currentlyDisplayedBooking + '&reason=' + reason;
	XHR.open("GET", requestUrl, false) ;
	reason.innerHTML += "open()<br/>" ;
	XHR.send(null) ;
	reason.innerHTML += "send()<br/>" ;
	// Now, let's refresh the screen to display the new planning
	hideCancelBookingDetails() ;
}

function confirmCancelBooking() {
	var saveCurrentlyDisplayedBooking = currentlyDisplayedBooking ; // Need to save it as hideEditBookingDetails() resets it
	hideEditBookingDetails() ;
	currentlyDisplayedBooking = saveCurrentlyDisplayedBooking ;
	document.getElementById("reasonTextArea").style.borderColor = 'red' ;
	document.getElementById("reasonTextArea").value = '' ;
	document.getElementById("reasonTextArea").placeholder = "Raison de l'annulation (obligatoire)" ;
	document.getElementById('confirmCancelBookingButton').disabled = true ;
	document.getElementById("cancelBookingDiv").style.visibility = 'visible' ;
	document.getElementById("cancelBookingDiv").style.display = 'block' ;
	document.getElementById("cancelBookingDiv").style.position = 'absolute' ;
	document.getElementById("cancelBookingDiv").style.ZIndex = '10' ;
	document.getElementById("cancelBookingDiv").style.top = document.getElementById('bookingDiv').style.top ;
	document.getElementById("cancelBookingDiv").style.left = document.getElementById('bookingDiv').style.left ;
}

// Instructors can cancel bookings in the past
function cancelOldBooking(bookingId) {
	var XHR=new XMLHttpRequest();
	displayWaiting() ;
	XHR.onreadystatechange = function() {
		if(XHR.readyState  == 4) {
			hideWaiting() ;
			if(XHR.status  == 200) {
				try {
					var response = eval('(' + XHR.responseText.trim() + ')') ;
				} catch(err) {
					return ;
				}
				if (response.error != '') {
					alert("Il y a eu une erreur: " + response.error) ;
				} else {
					alert("R&eacute;servation effac&eacute;e: " + response.message + "\nVeuillez rafra&icirc;chir la page") ;
				}
			}
		}
	}
	var requestUrl = "cancel_booking.php?id=" + bookingId ;
	XHR.open("GET", requestUrl, false) ;
	XHR.send(null) ;
}


function cancelAgendaItem() {
	var XHR=new XMLHttpRequest();
	displayWaiting() ;
	XHR.onreadystatechange = function() {
		if(XHR.readyState  == 4) {
			hideWaiting() ;
			if(XHR.status  == 200) {
				try {
					var response = eval('(' + XHR.responseText.trim() + ')') ;
				} catch(err) {
					return ;
				}
				if (response.error != '') {
					document.getElementById("bookingMessage").innerHTML = response.error ;
					document.getElementById("bookingMessage").className = 'bookingErrorMessage' ;
				} else {
					document.getElementById("bookingMessage").innerHTML = response.message ;
					document.getElementById("bookingMessage").className = null ;
				}
				document.getElementById("bookingMessageDiv").style.top = document.getElementById('agendaItemDiv').style.top ;
				document.getElementById("bookingMessageDiv").style.left = document.getElementById('agendaItemDiv').style.left ;
				document.getElementById("bookingMessageDiv").style.visibility = 'visible' ;
				refreshPlanningTable() ;
			}
		}
	}
	var requestUrl = "cancel_fi_agenda.php?id=" + currentlyDisplayedAgendaItem ;
	XHR.open("GET", requestUrl, false) ;
	XHR.send(null) ;
	// Now, let's refresh the screen to display the new planning
	hideEditAgendaItemDetails() ;
}

function modifyBooking(id) {
	displayWaiting() ;
	console.log('modifyBooking(' + id + ') currentlyDisplayedBooking=' + currentlyDisplayedBooking + ', bookingFromID()=' + bookingFromID(currentlyDisplayedBooking) + ', loggingFromID()=' + loggingFromID(currentlyDisplayedBooking)) ;
	console.log('allBookings:') ;
	console.log(allBookings) ;
	if (allBookings[bookingFromID(currentlyDisplayedBooking)][loggingFromID(currentlyDisplayedBooking)].ressource == 0) {
		var plane = document.getElementById("planeSelect").value ;
		var pilotId = document.getElementById("pilotSelect").value ;
		var instructorId = document.getElementById("instructorSelect").value ;
		var clientId = 0 ;
		if (document.getElementById("customerSelect"))
			clientId = document.getElementById("customerSelect").value ;
		else
			customerId = 0 ;
		var crewWanted = (document.getElementById("crewWantedInput").checked) ? 1 : 0 ;
		var paxWanted = (document.getElementById("paxWantedInput").checked) ? 1 : 0 ;
		var departingAirport = document.getElementById("departingAirport").value ;
		var destinationAirport = document.getElementById("destinationAirport").value  ;
		var via1Airport = document.getElementById("via1Airport").value  ;
		var via2Airport = document.getElementById("via2Airport").value  ;
		var flightDuration = document.getElementById("flightDuration").value  ;
	} else {
		var plane = document.getElementById("ressourceSelect").value ;
		var pilotId = document.getElementById("memberSelect").value ;
		var instructorId = -1 ;
		var clientId = 0 ;
		var customerId = 0 ;
		var crewWanted = 0 ;
		var paxWanted = 0 ;
		var departingAirport = '' ;
		var destinationAirport = '' ;
		var via1Airport = '' ;
		var via2Airport = '' ;
		var flightDuration = 0 ;
	}
	var bookingStart = document.getElementById("startYearSelect").value ;
	bookingStart += '-' + document.getElementById("startMonthSelect").value ;
	bookingStart += '-' + document.getElementById("startDaySelect").value ;
	bookingStart += ' ' + document.getElementById("startHourSelect").value ;
	bookingStart += ':' + document.getElementById("startMinuteSelect").value + ":00";
	var bookingEnd = document.getElementById("endYearSelect").value ;
	bookingEnd += '-' + document.getElementById("endMonthSelect").value ;
	bookingEnd += '-' + document.getElementById("endDaySelect").value ;
	bookingEnd += ' ' + document.getElementById("endHourSelect").value ;
	bookingEnd += ':' + document.getElementById("endMinuteSelect").value + ":00";
	var comment = document.getElementById("commentTextArea").value ;
	var XHR=new XMLHttpRequest();
	XHR.onreadystatechange = function() {
		if(XHR.readyState  == 4) {
			hideWaiting() ;
			if(XHR.status  == 200) {
				try {
					var response = eval('(' + XHR.responseText.trim() + ')') ;
				} catch(err) {
					return ;
				}
				if (response.error != '') {
					document.getElementById("bookingMessage").innerHTML = response.error ;
					document.getElementById("bookingMessage").className = 'bookingErrorMessage' ;
				} else {
					document.getElementById("bookingMessage").innerHTML = response.message ;
					document.getElementById("bookingMessage").className = null ;
				}
				document.getElementById("bookingMessageDiv").style.visibility = 'visible' ;
				document.getElementById("bookingMessageDiv").style.top = document.getElementById('bookingDiv').style.top ;
				document.getElementById("bookingMessageDiv").style.left = document.getElementById('bookingDiv').style.left ;
				refreshPlanningTable() ;
			}
		}
	}
	var requestUrl = "modify_booking.php?booking=" + currentlyDisplayedBooking + "&plane=" + plane + '&pilotId=' + pilotId + '&instructorId=' + instructorId +
		'&customerId=' + customerId + '&start=' + bookingStart + '&end=' + bookingEnd +
		'&comment=' + comment + '&crewWanted=' + crewWanted + '&paxWanted=' + paxWanted + '&fromApt=' + departingAirport + '&toApt=' + destinationAirport +
		'&via1Apt=' + via1Airport + '&via2Apt=' + via2Airport +
		'&duration=' + flightDuration ;
	XHR.open("GET", requestUrl, false) ;
	XHR.send(null) ;
	// Now, let's refresh the screen to display the new booking
	hideEditBookingDetails() ;
}

function modifyAgendaItem(id) {
	displayWaiting() ;
	var itemStart = document.getElementById("agendaItemDateStart").value ;
	itemStart += ' ' + document.getElementById("agendaItemStartHourSelect").value ;
	itemStart += ':' + document.getElementById("agendaItemStartMinuteSelect").value + ":00";
	var itemEnd = document.getElementById("agendaItemDateEnd").value ;
	itemEnd += ' ' + document.getElementById("agendaItemEndHourSelect").value ;
	itemEnd += ':' + document.getElementById("agendaItemEndMinuteSelect").value + ":00";
	var comment = document.getElementById("agendaItemCommentTextArea").value ;
	var instructorId = document.getElementById("agendaItemInstructorSelect").value ;
	var itemStudentOnly = (document.getElementById("agendaItemStudentOnly").checked) ? 1 : 0 ;
	var itemCallType = 0 ;
	if (document.getElementById("agendaItemAvailability").checked) {
		if (document.getElementById("agendaItemOnSite").checked) itemCallType |= 0x01 ;
		if (document.getElementById("agendaItemEmail").checked) itemCallType |= 0x02 ;
		if (document.getElementById("agendaItemPhone").checked) itemCallType |= 0x04 ;
		if (document.getElementById("agendaItemSMS").checked) itemCallType |= 0x08 ;
	} else
		itemCallType = -1 ;
	var XHR=new XMLHttpRequest();
	XHR.onreadystatechange = function() {
		if(XHR.readyState  == 4) {
			hideWaiting() ;
			if(XHR.status  == 200) {
				try {
					var response = eval('(' + XHR.responseText.trim() + ')') ;
				} catch(err) {
					return ;
				}
				if (response.error != '') {
					document.getElementById("bookingMessage").innerHTML = response.error ;
					document.getElementById("bookingMessage").className = 'bookingErrorMessage' ;
				} else {
					document.getElementById("bookingMessage").innerHTML = response.message ;
					document.getElementById("bookingMessage").className = null ;
				}
				document.getElementById("bookingMessageDiv").style.visibility = 'visible' ;
				document.getElementById("bookingMessageDiv").style.top = document.getElementById('agendaItemDiv').style.top ;
				document.getElementById("bookingMessageDiv").style.left = document.getElementById('agendaItemDiv').style.left ;
				refreshPlanningTable() ;
			}
		}
	}
	var requestUrl = "modify_fi_agenda.php?item=" + currentlyDisplayedAgendaItem + '&fi=' + instructorId +
		'&callType=' + itemCallType + '&studentOnly=' + itemStudentOnly +
		'&start=' + itemStart + '&end=' + itemEnd + '&comment=' + comment ;
console.log(requestUrl) ;
	XHR.open("GET", requestUrl, false) ;
	XHR.send(null) ;
	// Now, let's refresh the screen to display the new booking
	hideEditAgendaItemDetails() ;
}

// On event: when the cancel reason is changed to unlock the 'confirm' button
function cancelReasonChanged() {
	var elem = document.getElementById("reasonTextArea") ;
	var confirmCancelButton = document.getElementById('confirmCancelBookingButton') ;

	if (elem.value != '') {
		elem.style.borderColor = 'gray' ;
		elem.style.backgroundColor = 'white' ;
		confirmCancelButton.disabled = false ;
	} else {
		elem.style.borderColor = 'red' ;
		elem.style.backgroundColor = 'red' ;
		confirmCancelButton.disabled = true ;
	}
}

// On event: when the flight duration is changed to unlock the 'add' button
function durationChanged() {
	var elem = document.getElementById("flightDuration") ;
	var addButton = document.getElementById('addBookingButton') ;

	if (elem.value != '' && !isNaN(parseFloat(elem.value))) {
		elem.style.borderColor = 'gray' ;
		elem.style.backgroundColor = 'white' ;
		addButton.disabled = false ;
	} else {
		elem.style.borderColor = 'red' ;
		elem.style.backgroundColor = 'red' ;
		addButton.disabled = true ;
	}
}

function confirmBooking(bookingIsForFlying) {
	var plane = (document.getElementById("flightInfo1Span").style.display == 'none') ?
		 document.getElementById("ressourceSelect").value : document.getElementById("planeSelect").value ;
	var bookingStart = document.getElementById("startYearSelect").value ;
	bookingStart += '-' + document.getElementById("startMonthSelect").value ;
	bookingStart += '-' + document.getElementById("startDaySelect").value ;
	bookingStart += ' ' + document.getElementById("startHourSelect").value ;
	bookingStart += ':' + document.getElementById("startMinuteSelect").value + ":00";
	var bookingEnd = document.getElementById("endYearSelect").value ;
	bookingEnd += '-' + document.getElementById("endMonthSelect").value ;
	bookingEnd += '-' + document.getElementById("endDaySelect").value ;
	bookingEnd += ' ' + document.getElementById("endHourSelect").value ;
	bookingEnd += ':' + document.getElementById("endMinuteSelect").value + ":00";
	var comment = document.getElementById("commentTextArea").value ;
	var pilotId = (document.getElementById("flightInfo1Span").style.display == 'none') ?
		document.getElementById("memberSelect").value : document.getElementById("pilotSelect").value ;
	var instructorId = document.getElementById("instructorSelect").value ;
	if (document.getElementById("customerSelect"))
		customerId = document.getElementById("customerSelect").value ;
	else
		customerId = 0 ;
	var crewWanted = (document.getElementById("crewWantedInput").checked) ? 1 : 0 ;
	var paxWanted = (document.getElementById("paxWantedInput").checked) ? 1 : 0 ;
	var departingAirport = document.getElementById("departingAirport").value ;
	var via1Airport = document.getElementById("via1Airport").value  ;
	var via2Airport = document.getElementById("via2Airport").value  ;
	var destinationAirport = document.getElementById("destinationAirport").value  ;
	var flightDuration = document.getElementById("flightDuration").value  ;

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
	if (document.getElementById("flightInfo1Span").style.display != 'none' && flightDuration == '') {
		alert("Vous devez entrer une estimation de la durée du vol") ;
		return ;
	}
	displayWaiting() ;
	var XHR=new XMLHttpRequest();
	XHR.onreadystatechange = function() {
		if(XHR.readyState  == 4) {
			hideWaiting() ;
			if(XHR.status  == 200) {
				try {
					var response = eval('(' + XHR.responseText.trim() + ')') ;
				} catch(err) {
					return ;
				}
				if (response.error != '') {
					document.getElementById("bookingMessage").innerHTML = response.error ;
					document.getElementById("bookingMessage").className = 'bookingErrorMessage' ;
				} else {
					document.getElementById("bookingMessage").innerHTML = response.message ;
					document.getElementById("bookingMessage").className = null ;
				}
				document.getElementById("bookingMessageDiv").style.visibility = 'visible' ;
				document.getElementById("bookingMessageDiv").style.top = document.getElementById('bookingDiv').style.top ;
				document.getElementById("bookingMessageDiv").style.left = document.getElementById('bookingDiv').style.left ;
				refreshPlanningTable() ;
			}
		}
	}
	if (bookingIsForFlying) {
		if (userIsInstructor)
			bookingType = bookingTypeInstructor ;
		else if (userIsAdmin)
			bookingType = bookingTypeAdmin ;
		else
			bookingType = bookingTypePilot ;
	} else
		bookingType = bookingTypeMaintenance ;
	var requestUrl = "create_booking.php?plane=" + plane + '&pilotId=' + pilotId +  '&instructorId=' + instructorId +
		'&customerId=' + customerId + '&start=' + bookingStart + '&end=' + bookingEnd +
		'&type=' + bookingType + '&comment=' + comment + '&crewWanted=' + crewWanted + '&paxWanted=' + paxWanted + '&fromApt=' + departingAirport + '&toApt=' + destinationAirport +
		'&via1Apt=' + via1Airport + '&via2Apt=' + via2Airport +
		'&duration=' + flightDuration ;
	XHR.open("GET", requestUrl, false) ;
	XHR.send(null) ;
	// Now, let's refresh the screen to display the new booking
	hideEditBookingDetails() ;
}

function confirmAgendaItem() {
	displayWaiting() ;
	var itemStart = document.getElementById("agendaItemDateStart").value ;
	itemStart += ' ' + document.getElementById("agendaItemStartHourSelect").value ;
	itemStart += ':' + document.getElementById("agendaItemStartMinuteSelect").value + ":00";
	var itemEnd = document.getElementById("agendaItemDateEnd").value ;
	itemEnd += ' ' + document.getElementById("agendaItemEndHourSelect").value ;
	itemEnd += ':' + document.getElementById("agendaItemEndMinuteSelect").value + ":00";
	var comment = document.getElementById("agendaItemCommentTextArea").value ;
	var instructorId = document.getElementById("agendaItemInstructorSelect").value ;
	var itemStudentOnly = (document.getElementById("agendaItemStudentOnly").checked) ? 1 : 0 ;
	var itemCallType = 0 ;
	if (document.getElementById("agendaItemAvailability").checked) {
		if (document.getElementById("agendaItemOnSite").checked) itemCallType |= 0x01 ;
		if (document.getElementById("agendaItemEmail").checked) itemCallType |= 0x02 ;
		if (document.getElementById("agendaItemPhone").checked) itemCallType |= 0x04 ;
		if (document.getElementById("agendaItemSMS").checked) itemCallType |= 0x08 ;
	} else
		itemCallType = -1 ;
	var XHR=new XMLHttpRequest();

	XHR.onreadystatechange = function() {
		if(XHR.readyState  == 4) {
			hideWaiting() ;
			if(XHR.status  == 200) {
				try {
					var response = eval('(' + XHR.responseText.trim() + ')') ;
				} catch(err) {
					return ;
				}
				if (response.error != '') {
					document.getElementById("bookingMessage").innerHTML = response.error ;
					document.getElementById("bookingMessage").className = 'bookingErrorMessage' ;
				} else {
					document.getElementById("bookingMessage").innerHTML = response.message ;
					document.getElementById("bookingMessage").className = null ;
				}
				document.getElementById("bookingMessageDiv").style.visibility = 'visible' ;
				document.getElementById("bookingMessageDiv").style.top = document.getElementById('agendaItemDiv').style.top ;
				document.getElementById("bookingMessageDiv").style.left = document.getElementById('agendaItemDiv').style.left ;
				refreshPlanningTable() ;
			}
		}
	}
	var requestUrl = "create_fi_agenda.php?fi=" + instructorId + '&start=' + itemStart + '&end=' + itemEnd +
		'&callType=' + itemCallType + '&studentOnly=' + itemStudentOnly + '&comment=' + comment ;
	XHR.open("GET", requestUrl, false) ;
	XHR.send(null) ;
	// Now, let's refresh the screen to display the new booking
	hideEditAgendaItemDetails() ;
}

function hideDivDetails(div) {
	document.getElementById(div).style.visibility = 'hidden' ;
	document.getElementById(div).style.display = 'none' ;
	if (document.getElementById('webcamImg')) // As this element can disappear...
		document.getElementById('webcamImg').src = webcamUris[nowMinute % webcamUris.length] ;
	document.getElementById('webcamURI').href = webcamUris[nowMinute % webcamUris.length] ;
}

function showDivDetails(div, event) {
	// Make the form appear
	var thisDiv = document.getElementById(div) ;

	thisDiv.style.visibility = 'visible' ;
	thisDiv.style.display = 'block' ;
	// Move the form near the mouse click
	thisDiv.style.position = 'absolute' ; 
	thisDiv.style.top = event.clientY + 10; 
	if (thisDiv.getBoundingClientRect().bottom > browserHeight) thisDiv.style.top = event.clientY - 300 ;
	thisDiv.style.left = event.clientX + 10 ; 
	if (thisDiv.getBoundingClientRect().right > browserWidth) 
		thisDiv.style.left = event.clientX - 400 ;
	thisDiv.style.ZIndex = '10' ; 
}

function hideEditBookingDetails() {
	hideDivDetails('bookingDiv') ;
	currentlyDisplayedBooking = null ;
}

function hideCancelBookingDetails() {
	hideDivDetails('cancelBookingDiv') ;
	currentlyDisplayedBooking = null ;
}

function hideEditAgendaItemDetails() {
	hideDivDetails('agendaItemDiv') ;
	currentlyDisplayedAgendaItem = null ;
}

function engineHoursClicked() {
	window.location.href = '../scripts/carnetdevol/IntroCarnetVol.php?id=' + currentlyDisplayedBooking ;
}

function redirectLogBook(event) {
	event.stopPropagation() ; // Avoid further processing the initial click as it removes the box :-)
	var id = event.target.id ;
	if (id == 0) return ; // When clicking on pilot details, this event is also triggered :-(
	window.location.href = '../scripts/carnetdevol/IntroCarnetVol.php?id=' + bookingFromID(id) ;
}

function editBookingDetails(event) {
	var ressource ; // Whether it is a plane (0) or another ressource (1)
	event.stopPropagation() ; // Avoid further processing the initial click as it removes the box :-)
	var id = event.target.id ;
	if (id == 0) return ; // When clicking on pilot details, this event is also triggered :-(
	currentlyDisplayedBooking = bookingFromID(id)  ;
	var booking = allBookings[bookingFromID(id)][loggingFromID(id)] ;
	document.getElementById('bookingTitle').innerHTML = "Annuler/modifier une r&eacute;servation" ; 
	// Replace webcam by plane photo, add any plane comment
	document.getElementById('planeComment').innerHTML = "" ;
	for (var i = 0; i < allPlanes.length; i++) {
		if (allPlanes[i].id == booking.plane) {
			if (document.getElementById('webcamImg')) // As this element can disappear...
				document.getElementById('webcamImg').src = allPlanes[i].photo ;
			if (allPlanes[i].commentaire)
				document.getElementById('planeComment').innerHTML = '<br/><span style="color: red;">' + allPlanes[i].commentaire + "</span>\n" ;
			ressource = allPlanes[i].ressource ;
			break ;
		}
	}
	// TODO handle non-plane ressource
	
	if (ressource != 0) {
		document.getElementById("planeSelectSpan").style.display = 'none' ;
		document.getElementById("ressourceSelectSpan").style.display = 'inline' ;
		// Pre-set resource, member
		document.getElementById("ressourceSelect").value = booking.plane ;
		document.getElementById("memberSelect").value = booking.user ;
		document.getElementById("memberSelect").disabled = ! (userIsAdmin || userIsInstructor) ;
	} else {
		document.getElementById("planeSelectSpan").style.display = 'inline' ;
		document.getElementById("ressourceSelectSpan").style.display = 'none' ;
		// Pre-set plane, pilot, instructor
		document.getElementById("planeSelect").value = booking.plane ;
		document.getElementById("pilotSelect").value = booking.user ;
		document.getElementById("pilotSelect").disabled = ! (userIsAdmin || userIsInstructor) ;
		document.getElementById("instructorSelect").value = booking.instructorId ;
		document.getElementById("instructorSelect").disabled = ! (userIsAdmin || userIsInstructor) ;
		document.getElementById("crewWantedInput").checked = (booking.crew_wanted == 1) ;
		document.getElementById("paxWantedInput").checked = (booking.pax_wanted == 1) ;
	}
	// Pre-set start time 
	var startDate = new Date(booking.start) ;
	document.getElementById("startYearSelect").value = startDate.getFullYear() ;
	document.getElementById("startMonthSelect").value = startDate.getMonth() + 1;
	document.getElementById("startDaySelect").value = startDate.getDate() ;
	document.getElementById("startHourSelect").value = startDate.getHours() ;
	document.getElementById("startMinuteSelect").value = startDate.getMinutes() ;
	// Pre-set end time 
	var endDate = new Date(booking.end) ;
	document.getElementById("endYearSelect").value = endDate.getFullYear() ;
	document.getElementById("endMonthSelect").value = endDate.getMonth() + 1;
	document.getElementById("endDaySelect").value = endDate.getDate() ;
	document.getElementById("endHourSelect").value = endDate.getHours() ;
	document.getElementById("endMinuteSelect").value = endDate.getMinutes() ;
	// Fill comment and airport fields
	document.getElementById("commentTextArea").value = booking.comment ;
	if (ressource == 0) {
		document.getElementById("flightInfo1Span").style.display = 'inline' ;
		document.getElementById("flightInfo2Span").style.display = 'inline' ;
		document.getElementById("flightDuration").value = booking.duration ;
		document.getElementById("departingAirport").value = booking.from ;
		document.getElementById("via1Airport").value = booking.via1 ;
		document.getElementById("via2Airport").value = booking.via2 ;
		document.getElementById("destinationAirport").value = booking.to ;
	} else { // This is not a plane
		document.getElementById("flightInfo1Span").style.display = 'none' ;
		document.getElementById("flightInfo2Span").style.display = 'none' ;
	}
	
	// Enabled the right set of buttons
	buttonHide('addMaintenanceButton') ;
	buttonHide('addBookingButton') ;
	buttonDisplayIf('modifyBookingButton', (booking.type != bookingTypeMaintenance) || userIsAdmin || userIsMechanic) ;
	buttonDisplayIf('cancelBookingButton', (booking.type != bookingTypeMaintenance) || userIsAdmin || userIsMechanic) ;
	if (ressource == 0) {
		buttonDisplayIf('cancelMaintenanceButton', booking.type == bookingTypeMaintenance && (userIsAdmin || userIsMechanic)) ;
	} else {
		buttonHide('cancelMaintenanceButton') ;
	}
	// Allow for engine hour entry if booking.start <= now
	buttonDisplayIf('engineHoursButton', startDate <= nowDate) ;
	// Make the form appear
	showDivDetails('bookingDiv', event) ;
}

function editAgendaItemDetails(event) {
	event.stopPropagation() ; // Avoid further processing the initial click as it removes the box :-)
	if (isNaN(event.target.id)) return ; // TODO Looks like the event is not always removed when an agenda is cancelled :-(
	var id = Number(event.target.id) ;
	if (id == 0) return ; // When clicking on pilot details, this event is also triggered :-(
	currentlyDisplayedAgendaItem = id ;
	var item = allFIAgendas[id] ;
	document.getElementById('agendaItemTitle').innerHTML = "Annuler/modifier une disponibilit&eacute;" ; 
	document.getElementById("agendaItemInstructorSelect").value = item.fi ;
	// Time
	document.getElementById("agendaItemDateStart").value = item.start.substr(0,10).replace(/\//g, '-') ;
	document.getElementById("agendaItemStartHourSelect").value = Number(item.start.substr(11,2)) ; // easy way to remove leading zero
	document.getElementById("agendaItemStartMinuteSelect").value = Number(item.start.substr(14,2)) ; // easy way to remove leading zero
	document.getElementById("agendaItemDateEnd").value = item.end.substr(0,10).replace(/\//g, '-') ; 
	document.getElementById("agendaItemEndHourSelect").value = Number(item.end.substr(11,2)) ; // easy way to remove leading zero
	document.getElementById("agendaItemEndMinuteSelect").value = Number(item.end.substr(14,2)) ; // easy way to remove leading zero
	// Call Types
	document.getElementById("agendaItemOnSite").checked = (item.callType & 0x01) ;
	document.getElementById("agendaItemEmail").checked = (item.callType & 0x02) ;
	document.getElementById("agendaItemPhone").checked = (item.callType & 0x04) ;
	document.getElementById("agendaItemSMS").checked = (item.callType & 0x08) ;
	document.getElementById("agendaItemStudentOnly").checked = (item.studentOnly != 0) ;
	// prefill the comment
	document.getElementById("agendaItemCommentTextArea").value = item.comment ;
	// Display the right set of buttons
	buttonHide('addAgendaItemButton') ;
	buttonDisplayIf('modifyAgendaItemButton', (userIsAdmin || userIsInstructor)) ;
	buttonDisplayIf('cancelAgendaItemButton', (userIsAdmin || userIsInstructor)) ;
	// Make the form appear
	showDivDetails('agendaItemDiv', event) ;
}

// TODO when selecting another plane, then refresh the planeComment
function newBookingDetails(event) {
	var ressourceType ;
	console.log('Start of newBookingDetails()') ;
	event.stopPropagation() ; // Avoid further processing the initial click as it removes the box :-)
	document.getElementById('bookingTitle').innerHTML = "Nouvelle r&eacute;servation" ; 
	// Pre-set the form fields based on the clicked cell, format: plane-tail-number/row/year/month/day/hour/minute
	cellDetails = event.target.id.split('/') ;
	// Replace webcam by plane photo and in the same loop, add any plane/ressource comment as well remember which kind of ressource
	cellDetails[0] = decodeURI(cellDetails[0]) ;
	document.getElementById('planeComment').innerHTML = "" ;
	for (var i = 0; i < allPlanes.length; i++) {
		if (allPlanes[i].id == cellDetails[0]) {
			if (document.getElementById('webcamImg'))
				document.getElementById('webcamImg').src = allPlanes[i].photo ;
			if (allPlanes[i].actif == 2)
				document.getElementById('planeComment').innerHTML += "<br/><span style=\"color: red;\">\n<b>Cet avion est r&eacute;serv&eacute; aux instructeurs et &agrave; leurs &eacute;l&egrave;ves.\n</b></span>\n" ;
			if (allPlanes[i].commentaire)
				document.getElementById('planeComment').innerHTML += "<br/><span style=\"color: red;\">\n" + allPlanes[i].commentaire + "</spa,>\n" ;
			ressourceType = allPlanes[i].ressource ;
			break ;
		}
	}
	// Pre-set plane, pilot
	if (ressourceType == 0) {
		document.getElementById("planeSelectSpan").style.display = 'inline' ;
		document.getElementById("ressourceSelectSpan").style.display = 'none' ;
		document.getElementById("planeSelect").value = cellDetails[0] ;
		document.getElementById("pilotSelect").value = userId ;
		document.getElementById("pilotSelect").disabled = ! (userIsAdmin || userIsInstructor) ;
		document.getElementById("instructorSelect").value = (userIsInstructor) ? userId : -1 ;
		document.getElementById("instructorSelect").disabled = ! (userIsAdmin || userIsInstructor) ;
		document.getElementById("crewWantedInput").checked = false;
		document.getElementById("paxWantedInput").checked = false;
	} else { // This is not a plane
		document.getElementById("planeSelectSpan").style.display = 'none' ;
		document.getElementById("ressourceSelectSpan").style.display = 'inline' ;
		document.getElementById("ressourceSelect").value = cellDetails[0] ;
		document.getElementById("memberSelect").value = userId ;
	}
	// Set start time based on the cell
	document.getElementById("startYearSelect").value = cellDetails[2] ;
	document.getElementById("startMonthSelect").value = cellDetails[3] ;
	document.getElementById("startDaySelect").value = cellDetails[4] ;
	document.getElementById("startHourSelect").value = cellDetails[5] ;
	document.getElementById("startMinuteSelect").value = cellDetails[6] ;
	// Set end time based on the cell
	document.getElementById("endYearSelect").value = cellDetails[2] ;
	document.getElementById("endMonthSelect").value = cellDetails[3] ;
	document.getElementById("endDaySelect").value = cellDetails[4] ;
	document.getElementById("endHourSelect").value = Number(cellDetails[5]) + 1 ; // Add one hour 
	if (Number(cellDetails[5]) + 1 == planningStopHour)
		document.getElementById("endMinuteSelect").value = 0 ; // Ensure that we cannot go further than airport closure
	else // Then let's simply assume one full hour
		document.getElementById("endMinuteSelect").value = cellDetails[6] ;
	// Empty comment and airport fields
	document.getElementById("commentTextArea").value = '' ;
	if (ressourceType == 0) {
		document.getElementById("flightInfo1Span").style.display = 'inline' ;
		document.getElementById("flightInfo2Span").style.display = 'inline' ;
		document.getElementById("flightDuration").value = '' ; // Rough estimate of flight time
		document.getElementById("flightDuration").style.borderColor = 'red' ; // Rough estimate of flight time
		document.getElementById("departingAirport").value = 'EBSP' ;
		document.getElementById("via1Airport").value = '' ;
		document.getElementById("via2Airport").value = '' ;
		document.getElementById("destinationAirport").value = 'EBSP' ;
	} else { // This is not a plane
		document.getElementById("flightInfo1Span").style.display = 'none' ;
		document.getElementById("flightInfo2Span").style.display = 'none' ;
	}
	// Enable the right set of buttons
	buttonDisplayIf('addMaintenanceButton', ressourceType == 0 && (userIsMechanic || userIsInstructor || userIsAdmin)) ;
	buttonDisplayIf('addBookingButton', (userIsPilot && allPlanes[i].actif == 1) || userIsInstructor || userIsAdmin) ;
	if (ressourceType == 0) { // Only plane needs additional flight duration value
		if (document.getElementById('addBookingButton') !== null)
			document.getElementById('addBookingButton').disabled = true ;
	} else {
		if (document.getElementById('addBookingButton') !== null)
			document.getElementById('addBookingButton').disabled = false ;
	}
	buttonHide('modifyBookingButton') ;
	buttonHide('cancelBookingButton') ;
	buttonHide('cancelMaintenanceButton') ;
	buttonHide('engineHoursButton') ;
	// Make the form appear
	showDivDetails('bookingDiv', event) ;
}

function newAgendaItemDetails(event) {
	myLog('Start of newAgendaItemDetails() Y-M-D = ' + planningYear + '-' + planningMonth + '-' + planningDay) ;
	event.stopPropagation() ; // Avoid further processing the initial click as it removes the box :-)
	document.getElementById('agendaItemTitle').innerHTML = "Ajout d'une disponibilit&eacute;" ; 
	// Pre-set the form fields based on the clicked cell, format: FI-id/row/year/month/day/hour/minute
	cellDetails = event.target.id.split('/') ;
	document.getElementById("agendaItemInstructorSelect").value = cellDetails[0] ;
	// Time
	if (cellDetails[3].length == 1) cellDetails[3] = "0" + cellDetails[3] ;
	if (cellDetails[4].length == 1) cellDetails[4] = "0" + cellDetails[4] ;
	document.getElementById("agendaItemDateStart").value = cellDetails[2] + '-' + cellDetails[3] + '-' + cellDetails[4] ;
	document.getElementById("agendaItemStartHourSelect").value = cellDetails[5] ;
	document.getElementById("agendaItemStartMinuteSelect").value = cellDetails[6] ;
	document.getElementById("agendaItemDateEnd").value = cellDetails[2] + '-' + cellDetails[3] + '-' + cellDetails[4] ;
	document.getElementById("agendaItemEndHourSelect").value = Number(cellDetails[5]) + 1;
	document.getElementById("agendaItemEndMinuteSelect").value = 0 ;
	// Empty comment fields
	document.getElementById("agendaItemCommentTextArea").value = '' ;
	// Enable the right set of buttons
	buttonDisplayIf('addAgendaItemButton', userIsInstructor || userIsAdmin) ;
	buttonHide('modifyAgendaItemButton') ;
	buttonHide('cancelAgendaItemButton') ;
	// Make the form appear
	showDivDetails('agendaItemDiv', event) ;
}

// Called when a new booking is received (by AJAX) in order to change the displayed table on specific row
function displayBooking(row, booking, displayDay, displayMonth, displayYear) {
	var workDate, startDate, endDate, nameDisplayed = false, firstColumn, widthInColumn ;
	var thisCell ;

	if (booking.log_end) { // Let's use the actual / logged time
		startDate = new Date(booking.log_start) ;
		endDate = new Date(booking.log_end) ;
	} else { // If not logged, then use the booking time
		startDate = new Date(booking.start) ;
		endDate = new Date(booking.end) ;
	}
	now = new Date() ;
	for (var i = 1, hour = planningStartHour, minute = 0 ; i < columnCount ; i ++, minute += 15) {
		if (minute >= 60) {
			minute = 0 ;
			hour ++ ;
		}
		workDate = new Date(displayYear, displayMonth - 1, displayDay, hour, minute, 0, 0) ;
		thisCell = planePlanningTable.rows[1+row].cells[i] ;
		if (startDate <= workDate && workDate < endDate) {
			if (nameDisplayed) {
				widthInColumn++ ;
				if (booking.type == bookingTypeMaintenance)
					thisCell.className = 'maintenance' ;
				else if (booking.type == bookingTypeCustomer)
					thisCell.className = 'customer' ;
				else if (booking.type == bookingTypeOnHold)
					thisCell.className = 'onhold' ;
				else if ((booking.ressource == 0) && (!booking.log_end) && (endDate <= now))
							thisCell.className = 'nolog' ;
						else
							thisCell.className = (booking.user == userId || booking.instructorId == userId) ? 'booked2_by_me' :
								(booking.instructorId > 0) ? 'booked2_dc' :'booked2' ;
			} else { // Name not yet displayed, need to display some more information
				nameDisplayed = true ;
				firstColumn = i ;
				widthInColumn = 1 ;
				if (booking.type == bookingTypeMaintenance) {
					thisCell.innerHTML = "maintenance jusqu'au " + booking.end ;
					thisCell.className = 'maintenance' ;
				} else if (booking.type == bookingTypeCustomer) {
					thisCell.innerHTML = booking.customerName ;
					thisCell.className = 'customer' ;
				} else {
					if (booking.log_pilot == 0)
						thisCell.innerHTML = booking.name ;
					else
						thisCell.innerHTML = booking.log_pilotName ;
					if (booking.type == bookingTypeOnHold)
						thisCell.className = 'onhold' 
					else if ((booking.ressource == 0) && (!booking.log_end) && (endDate <= now))
							thisCell.className = 'nolog' ;
						else
							thisCell.className = (booking.user == userId || booking.instructorId == userId) ? 'booked_by_me' :
								(booking.instructorId > 0) ? 'booked_dc' :'booked' ;
				}
			}
			thisCell.onmouseenter = function () { displayBookingDetails(booking.id + '-' + booking.log_id) ; } ;
			thisCell.onmouseleave = function () { clearBookingDetails() ; } ;
			if (!isInThePast(displayYear, displayMonth, displayDay, hour, minute) &&
					(userIsAdmin || userIsMechanic || userIsInstructor || userId == booking.user || userId == booking.bookedById)) {
				thisCell.id = booking.id + '-' + booking.log_id ;
				thisCell.removeEventListener('click', newBookingDetails) ;
				thisCell.addEventListener('click', editBookingDetails) ;
			} else if (isInThePast(displayYear, displayMonth, displayDay, hour, minute) &&
					(userIsAdmin || userId == booking.user || userId == booking.instructorId || userId == booking.bookedById)) {
				thisCell.id = booking.id + '-' + booking.log_id ;
				thisCell.removeEventListener('click', newBookingDetails) ;
				thisCell.addEventListener('click', redirectLogBook) ;
			}
		}
	}
	// Now, shrink the displayed name until it fits the booking cells
	if (firstColumn !== undefined) {
		thisCell = planePlanningTable.rows[1+row].cells[firstColumn] ;
		// First attemps, shrink first name to one letter
		if (thisCell && widthInColumn && thisCell.scrollWidth && thisCell.scrollWidth > widthInColumn * thisCell.offsetWidth) {
			thisCell.innerHTML = thisCell.innerHTML.charAt(0) + '. ' + thisCell.innerHTML.slice(thisCell.innerHTML.lastIndexOf(' ')) ;
		}
		// If still too long, then remove trailing characters
		if (thisCell && widthInColumn && thisCell.scrollWidth && thisCell.scrollWidth > widthInColumn * thisCell.offsetWidth) {
			thisCell.innerHTML[thisCell.innerHTML.length-1] = '.' ;
			while (thisCell.scrollWidth > widthInColumn * thisCell.offsetWidth) {
				thisCell.innerHTML = thisCell.innerHTML.substr(0, thisCell.innerHTML.length-2) + '.' ;
			}
		}
		// If booked by someelse, then also display the booker initials
		if (booking.instructorName != 'solo') {
			var fiInitials, fiTokens ;
			fiTokens = booking.instructorName.split(' ') ;
			for (var i = 0, fiInitials = '' ; i < fiTokens.length; i ++)
				fiInitials += fiTokens[i].charAt(0) + ' ' ;
			thisCell.innerHTML += '<br/><b><i>' + fiInitials + '</i></b>' ;
		}
		// If booked for a customer, then also display pilot initials
		if (booking.type == bookingTypeCustomer) {
			var pInitials, pTokens ;
			pTokens = booking.name.split(' ') ;
			for (var i = 0, pInitials = '' ; i < pTokens.length; i ++)
				pInitials += pTokens[i].charAt(0) + ' ' ;
			thisCell.innerHTML += '<br/><b><i>' + pInitials + '</i></b>' ;
		}
		// Add a clickable icons to display details
		thisCell.innerHTML += '<br/><a href="javascript:showPilotDetails(\'' + booking.id + '-' + booking.log_id + '\');"><img src="usl_search_icon.png" alt="?" title="D&eacute;tails"></a>' ;
	}
}

// Called when a new booking is received (by AJAX) in order to change the displayed table on specific row
function displayAgenda(row, item, displayDay, displayMonth, displayYear) {
	var workDate, startDate, endDate, nameDisplayed = false, firstColumn, widthInColumn ;
	var thisCell ;

	startDate = new Date(item.start) ;
	endDate = new Date(item.end) ;
	for (var i = 1, hour = planningStartHour, minute = 0 ; i < columnCount ; i ++, minute += 15) {
		if (minute >= 60) {
			minute = 0 ;
			hour ++ ;
		}
		workDate = new Date(displayYear, displayMonth - 1, displayDay, hour, minute, 0, 0) ;
		thisCell = instructorPlanningTable.rows[1+row].cells[i] ;
		if (startDate <= workDate && workDate < endDate) {
			thisCell.className = 'maintenance' ; // A default class, just in case...
			// Simple case: FI is not available because he is flying ;-)
			if (item.booking) { // A little tricky because the allBookings[] is built asynchronously... so we need to handle this case
				// TODO CHECK WITH LOGGING part
				var booking ;
				if (allBookings[item.booking] === undefined) {
					thisCell.className = 'booked' ;	
					booking = undefined ;				
				} else {
					booking = allBookings[item.booking][0] ;
					if (booking !== undefined && item.fi == booking.instructorId) 
						thisCell.className = 'booked_dc' ;
					else
						thisCell.className = 'booked' ;
				}
				if (nameDisplayed) {
					widthInColumn++ ;
					thisCell.className = (booking !== undefined && item.fi == booking.instructorId) ? 'booked2_dc' : 'booked2' ; 
				} else { // Name not yet displayed, need to display some more information
					nameDisplayed = true ;
					firstColumn = i ;
					widthInColumn = 1 ;
					thisCell.innerHTML = (booking !== undefined) ? booking.name : 'loading...' ;
					thisCell.className = (booking !== undefined && item.fi == booking.instructorId) ? 'booked_dc' : 'booked' ; 
				}
				// As it is linked to a plane booking, neither adding nor modifying on this cell
				thisCell.removeEventListener('click', newAgendaItemDetails) ;
				thisCell.onmouseenter = function () { displayBookingDetails(item.booking) ; } ;
				thisCell.onmouseleave = function () { clearBookingDetails() ; } ;
			} else { // Normal 'on call' duty
				if (nameDisplayed) {
					widthInColumn++ ;
					if (item.callType < 0)
						thisCell.className = 'unavailable2' ;
					else
						thisCell.className = (item.callType & 0x01) ? 'onsite2' : 'oncall2' ; 
				} else { // Name not yet displayed, need to display some more information
					nameDisplayed = true ;
					firstColumn = i ;
					widthInColumn = 1 ;
					thisCell.innerHTML = item.comment ;
					if (item.callType < 0)
						thisCell.className = 'unavailable' ;
					else
						thisCell.className = (item.callType & 0x01) ? 'onsite' : 'oncall' ; 
				}
				if (!isInThePast(displayYear, displayMonth, displayDay, hour, minute) &&
						(userIsAdmin || userId == item.fi)) {
					thisCell.id = item.item ;
					thisCell.removeEventListener('click', newAgendaItemDetails) ;
					thisCell.addEventListener('click', editAgendaItemDetails) ;
				}
				thisCell.onmouseenter = function () { displayAgendaItemDetails(item.item) ; } ;
				thisCell.onmouseleave = function () { clearBookingDetails() ; } ;
			}
		}
	}
	// Now, shrink the displayed name until it fits the booking cells
	if (firstColumn !== undefined) {
		thisCell = instructorPlanningTable.rows[1+row].cells[firstColumn] ;
		// First attemps, shrink first name to one letter
		if (thisCell && widthInColumn && thisCell.scrollWidth && thisCell.scrollWidth > widthInColumn * thisCell.offsetWidth) {
			thisCell.innerHTML = thisCell.innerHTML.charAt(0) + '. ' + thisCell.innerHTML.slice(thisCell.innerHTML.lastIndexOf(' ')) ;
		}
		// If still too long, then remove trailing characters
		if (thisCell && widthInColumn && thisCell.scrollWidth && thisCell.scrollWidth > widthInColumn * thisCell.offsetWidth) {
			thisCell.innerHTML[thisCell.innerHTML.length-1] = '.' ;
			while (thisCell.scrollWidth > widthInColumn * thisCell.offsetWidth) {
				thisCell.innerHTML = thisCell.innerHTML.substr(0, thisCell.innerHTML.length-2) + '.' ;
			}
		}
	}
}

// TODO allow booking in semainier mode
function refreshPlanePlanningRow(rowIndex, plane, displayDay, displayMonth, displayYear) {
	var row = planePlanningTable.rows[1 + rowIndex] ;
	displayWaiting() ;
	for (var i = 1, hour = planningStartHour, minute = 0; hour < planningStopHour ; i++, minute += 15) {
		if (minute >= 60) {
			minute = 0 ;
			hour ++ ;
			if (hour >= planningStopHour) break ;
		}
		row.cells[i].innerHTML = '' ;
		row.cells[i].onmouseenter = null ;
		row.cells[i].onmouseleave = null ;
		row.cells[i].onclick = null ;
		// TODO getHours does not work, use localtime for a strange reason
		if (hour > aeroSunsetHours ||
			(hour == aeroSunsetHours && minute >= aeroSunsetMinutes)) {
			row.cells[i].className = 'night' ;
			row.cells[i].removeEventListener('click', newBookingDetails) ;
		} else {
			if (isNow(displayYear, displayMonth, displayDay, hour, minute))
				row.cells[i].className = 'now' ;
			else {
				for (var j = 0; j < allPlanes.length; j++) {
					if (allPlanes[j].id == plane) 
						if (allPlanes[j].ressource == 0)
							switch (allPlanes[j].actif) {
								case 1: row.cells[i].className = 'available' ; break ;
								case 2: row.cells[i].className = 'available_for_fi' ; break ;
							}
						else
							row.cells[i].className = 'ressource_available' ;
				}
			}
			// Probably cannot pass 'plane' and 'i' as parameter for some javascript parameter binding
			// Need to set the TD cell id?
			// Should place the plane index?
			// encodeURIComponent(value).replace(/%20/g,'+');
			row.cells[i].id = encodeURIComponent(plane).replace(/%20/g,' ') + '/' + rowIndex + '/' + displayYear + '/' + displayMonth + '/' + displayDay + '/' + hour + '/' + minute ;
			if (!isInThePast(displayYear, displayMonth, displayDay, hour, minute) && userRatingValid && (userIsPilot || userIsInstructor || userIsMechanic || userIsAdmin)) 
				row.cells[i].addEventListener('click', newBookingDetails) ;
			else {
				row.cells[i].removeEventListener('click', newBookingDetails) ;
				row.cells[i].removeEventListener('click', editBookingDetails) ;
			}
		}
	}
	(function (plane) { // Introducing a javascript closure per http://stackoverflow.com/questions/24773307/sending-post-request-in-for-loop?lq=1
		var XHR = new XMLHttpRequest();
		XHR.onreadystatechange = function() {
			if(XHR.readyState  == 4) {
				if(XHR.status  == 200) {
					try {
						var bookings = eval('(' + convertCharset(XHR.responseText.trim()) + ')') ;
					} catch(err) {
						hideWaiting() ;
						return ;
					}
					for (var i = 0; i < bookings.length ; i++) {
						if (typeof bookings[i].from == 'undefined') bookings[i].from = '' ;
						if (typeof bookings[i].via1 == 'undefined') bookings[i].via1 = '' ;
						if (typeof bookings[i].via2 == 'undefined') bookings[i].via2 = '' ;
						if (typeof bookings[i].to == 'undefined') bookings[i].to = '' ;
						if (typeof bookings[i].log_id == 'undefined') {
							bookings[i].log_id = 0 ;
							bookings[i].log_pilot = 0 ;
							bookings[i].log_pilotName = '' ;
						} else {
							bookings[i].log_pilotName = 'inconnu' ;
							for (var j = 0; j < pilots.length ; j++) {
								if (pilots[j].id == bookings[i].log_pilot) {
									bookings[i].log_pilotName = pilots[j].name ;
									break ;
								}
							}	
						}	
						// should do it only when row does not exist yet... such as when there are multiple logging entries for the same booking						
						if (typeof allBookings[bookings[i].id] == 'undefined')
							allBookings[bookings[i].id] = new Array() ;
						allBookings[bookings[i].id][bookings[i].log_id] = bookings[i] ;
						displayBooking(Number(bookings[i].arg), bookings[i], displayDay, displayMonth, displayYear) ;
						if (bookings[i].crew_wanted != 0)
							dayMessagesHTML += 'Pilote(s) bienvenu(s) pour la réservation du ' + bookings[i].plane + ' par ' + bookings[i].name + '.<br/>' ;
						if (bookings[i].pax_wanted != 0)
							dayMessagesHTML += 'Passager(s) bienvenu(s) pour la réservation du ' + bookings[i].plane + ' par ' + bookings[i].name + '.<br/>' ;
					}
					displayDayMessages() ;
				}
				hideWaiting() ;
			}
		} ;
		// BEWARE at some point of time, it was mandatory to use synchronous (false) AJAX calls... seems that now it is OK to use asynchronous (true)
		// but we need to pass the row (plane) to the server script to play it back...
		var url = "get_bookings.php?plane=" + plane + '&arg=' + rowIndex +
			'&day=' + displayDay + '&month=' + displayMonth + '&year=' + displayYear ;
		XHR.open('GET', url, true) ;
		XHR.send(null) ;
	}) (plane) ;
}

// TODO allow booking in semainier mode
function refreshInstructorPlanningRow(rowIndex, instructor, displayDay, displayMonth, displayYear) {
	var row = instructorPlanningTable.rows[1 + rowIndex] ;
	displayWaiting() ;
	for (var i = 1, hour = planningStartHour, minute = 0; hour < planningStopHour ; i++, minute += 15) {
		if (minute >= 60) {
			minute = 0 ;
			hour ++ ;
			if (hour >= planningStopHour) break ;
		}
		row.cells[i].innerHTML = '' ;
		row.cells[i].onmouseenter = null ;
		row.cells[i].onmouseleave = null ;
		row.cells[i].onclick = null ;
		// TODO getHours does not work, use localtime for a strange reason
		if (hour > aeroSunsetHours ||
			(hour == aeroSunsetHours && minute >= aeroSunsetMinutes)) {
			row.cells[i].className = 'night' ;
		} else {
			if (isNow(displayYear, displayMonth, displayDay, hour, minute))
				row.cells[i].className = 'now' ;
			else
				row.cells[i].className = 'available' ;
			// Probably cannot pass 'plane' and 'i' as parameter for some javascript parameter binding
			// Need to set the TD cell id?
			// Should place the plane index?
			row.cells[i].id = instructor + '/' + rowIndex + '/' + displayYear + '/' + displayMonth + '/' + displayDay + '/' + hour + '/' + minute ;
			if (!isInThePast(displayYear, displayMonth, displayDay, hour, minute) && userRatingValid && (userIsInstructor || userIsAdmin)) 
				row.cells[i].addEventListener('click', newAgendaItemDetails) ;
			else {
				row.cells[i].removeEventListener('click', newAgendaItemDetails) ;
				row.cells[i].removeEventListener('click', editAgendaItemDetails) ;
			}
		}
	}
	(function (instructor) { // Introducing a javascript closure per http://stackoverflow.com/questions/24773307/sending-post-request-in-for-loop?lq=1
		var XHR = new XMLHttpRequest();
		XHR.onreadystatechange = function() {
			if(XHR.readyState  == 4) {
				if(XHR.status  == 200) {
					try {
						var agenda = eval('(' + convertCharset(XHR.responseText.trim()) + ')') ;
						// Sort it based on duration longuest first
						agenda.sort(function(a, b) { return b.duration - a.duration ;}) ;
					} catch(err) {
						hideWaiting() ;
						return ;
					}
					for (var i = 0; i < agenda.length ; i++) {
						if (agenda[i].item !== undefined) allFIAgendas[agenda[i].item] = agenda[i] ; // If it is based on a specific agenda item, save it for later
						displayAgenda(Number(agenda[i].arg), agenda[i], displayDay, displayMonth, displayYear) ;
					}
				}
				displayDayMessages() ;
				hideWaiting() ;
			}
		} ;
		var url = "get_fi_agenda.php?fi=" + instructor + '&arg=' + rowIndex +
			'&day=' + displayDay + '&month=' + displayMonth + '&year=' + displayYear ;
		XHR.open('GET', url, true) ;
		XHR.send(null) ;
	}) (instructor) ;
}

function refreshPlanningTable() {
	myLog("start refreshPlanningTable(), " + planningYear + "/" + planningMonth + "/" + planningDay + ', plane: ' + planningPlaneIndex) ;
	var hour, minute, compteur ;

	clearBookingDetails() ;
	allBookings = new Array(new Array()) ;
	allFIAgendas = new Array() ;
	dayMessagesHTML = '' ;
	if (planningByPlane) { // TODO probably need to refresh column 0 with all the new dates
		for (var day = 0; day < 7; day++) {
			// TODO make dates clickable to change mode to date
			var workDate = new Date(planningYear, planningMonth - 1, planningDay, 0, 0, 0, 0) ;
			workDate.setDate(workDate.getDate() + day) ;
			var rowDay = workDate.getDate() ;
			var rowDayOfWeek = workDate.getDay() ;
			var rowMonth = workDate.getMonth() + 1 ; // Stupid JS starts month at 0 !
			var rowYear = workDate.getFullYear() ;
			planePlanningTable.rows[1 + day].cells[0].innerHTML = weekdays[rowDayOfWeek] + '<br/>' + rowDay + '/' + rowMonth + '<br/>' + allPlanes[planningPlaneIndex].id;
			planePlanningTable.rows[1 + day].cells[0].id = rowYear + '/' + rowMonth + '/' + rowDay ;
			planePlanningTable.rows[1 + day].cells[0].onclick = function () { presentationByDay(event) ; } 
			refreshPlanePlanningRow(day, allPlanes[planningPlaneIndex].id, rowDay, rowMonth, rowYear) ;
		}
	} else { // Planning by day
		for (var plane = 0; plane < allPlanes.length; plane++) {
			planePlanningTable.rows[1 + plane].cells[0].innerHTML = allPlanes[plane].id ;
			// Always displayed now for engine clocks
			// if (allPlanes[plane].commentaire || allPlanes[plane].actif != 1) {
				planePlanningTable.rows[1 + plane].cells[0].onmouseenter = makeDisplayPlaneDetails(plane)  ;
				planePlanningTable.rows[1 + plane].cells[0].onmouseleave = function () { clearBookingDetails() ; } ;
			// }
			// Check for rating validity 
			if (!allPlanes[plane].qualifications_requises) {
				planePlanningTable.rows[1 + plane].cells[0].innerHTML += ' <img src="forbidden-icon.png" width="12" height="12" title="Manque la qualification" alt="X">' ;
			} else {
				// Ratings are valid, let's do further check whether plane can be booked
				var bookingAllowed = allPlanes[plane].reservation_permise ;
				if (!bookingAllowed) {
					myLog('Booking not allowed on that plane... need to look into other bookings for ' + allPlanes[plane].id) ;
					// If pilot did not book this exact plane, let's try to find whether he/she flew a plane from a 'larger' group...
					for (var plane2 = 0; plane2 < allPlanes.length; plane2++) {
						if (allPlanes[plane2].reservation_permise && planeClassIsMember(allPlanes[plane].classe, allPlanes[plane2].classe))
							bookingAllowed = true ;
					}
				}
				if (bookingAllowed && allPlanes[plane].actif == 2)
					bookingAllowed = false ;
				if (!bookingAllowed)
					planePlanningTable.rows[1 + plane].cells[0].innerHTML += ' <img src="exclamation-icon.png" width="12" height="12" title="Pas de vol r&eacute;cent" alt="!">' ;
			}
			// add for engine hours using the most recent data but not for ressources
			if (allPlanes[plane].ressource == 0) {
				// Add FlightAware link only for members
				if (userId > 0)
					planePlanningTable.rows[1 + plane].cells[0].innerHTML += ' <a href="https://flightaware.com/live/flight/' + allPlanes[plane].id.toUpperCase() + '" target="_blank"><img src="fa.ico" border="0" width="12" height="12"></a>' ;
				if (allPlanes[plane].compteur_pilote_date > allPlanes[plane].compteur_date)
					compteur = allPlanes[plane].compteur_pilote ;
				else
					compteur = allPlanes[plane].compteur ;
				planePlanningTable.rows[1 + plane].cells[0].innerHTML += '<br/>Compteur: ' + compteur + '<br/>Maint. &agrave;: ' + allPlanes[plane].entretien ;
				if (allPlanes[plane].entretien <= compteur)
					planePlanningTable.rows[1 + plane].cells[0].style.color = 'red' ;
				else if (allPlanes[plane].entretien <= compteur + 5)
					planePlanningTable.rows[1 + plane].cells[0].style.color = 'orange' ;
				else
					planePlanningTable.rows[1 + plane].cells[0].style.color = 'black' ;
			} else
				planePlanningTable.rows[1 + plane].cells[0].style.color = 'blue' ;
			planePlanningTable.rows[1 + plane].cells[0].id = plane ;
			planePlanningTable.rows[1 + plane].cells[0].onclick = function () { presentationByPlane(event) ; } 
			refreshPlanePlanningRow(plane, allPlanes[plane].id, planningDay, planningMonth, planningYear) ;
		}
		// Race condition here as when a plane is booked, we display the details hence we need to ensure that all bookings have been fetched
		// before attempting to display the instructors' agenda :-(
//		if (waitingCount) alert('Race condition') ;
//		while (waitingCount) xyzzy = 1 ;
		for (var instructor = 1; instructor < instructors.length; instructor++) { // always skip the first instructor which is "solo"
			instructorPlanningTable.rows[instructor].cells[0].innerHTML = instructors[instructor].name ;
			refreshInstructorPlanningRow(instructor - 1, instructors[instructor].id, planningDay, planningMonth, planningYear) ;
		}
	}
	document.getElementById('planningDayOfWeek').innerHTML = (planningDayOfWeek == -1) ? '' : weekdays[planningDayOfWeek] + ': ' ; 
	document.getElementById('planningDate').value = planningDay + '/' + planningMonth + '/' + planningYear ;
// TODO June 2020, do we really need it repeated ? It is heavy with displayMETAR() notably...
//	clearBookingDetails() ; // Repeated to unsure accurate (? but this is asynchronous) dayMessagesHTML display...
	myLog("end refreshPlanningTable()") ;
}

function presentationByPlane(event) {
	event.stopPropagation() ; // Avoid further processing the initial click as it removes the box :-)
	planningPlaneIndex = event.target.id ;
	planningByPlane = true ; 
    document.getElementById('roadBookButton').disabled = false ;
    document.getElementById('roadBookButton').style.display = 'inline' ;
	initPlanningTable() ;
	refreshPlanningTable() ;
}


function presentationByDay(event) {
	event.stopPropagation() ; // Avoid further processing the initial click as it removes the box :-)
	cellDetails = event.target.id.split('/') ;
	var year = cellDetails[0] ;
	var month = cellDetails[1] ;
	var day = cellDetails[2] ;
	planningDay = day ;
	planningMonth = month ;
	planningYear = year ;
	var workDate = new Date(year, month - 1 , day, 0, 0, 0, 0) ;
	planningDayOfWeek = workDate.getDay() ;
	refreshEphemerides() ; // As the date has changed
	planningByPlane = false ; 
        document.getElementById('roadBookButton').disabled = true ;
        document.getElementById('roadBookButton').style.display = 'none' ;
	initPlanningTable() ;
	refreshPlanningTable() ;
	displayMETAR() ; // As the date has changed, last action to be taken as it is the least important
}

function init() {
	myLog("start init(), userId=" + userId + ", userName=" + userName + ', ' + navigator.userAgent) ;
	if (userIsPilot) console.log("userIsPilot") ;
	if (userIsInstructor) console.log("userIsInstructor") ;
	if (userIsAdmin) console.log("userIsAdmin") ;
	if (userIsMechanic) console.log("userIsMechanic") ;
	document.onkeydown = function(evt) {
		evt = evt || window.event;
		if (evt.keyCode == 27) {
			hideEditBookingDetails() ;
			hideEditAgendaItemDetails() ;
			hideBookingMessage() ;
			hidePilotDetails() ;
		}
	}
// Hmmm even if stopPropagate() we cannot click on drop-down boxes...
//	document.onclick = function(event) {
//			hideEditBookingDetails() ;
//		}
	displayClock() ;
	refreshWebcam() ;
	setTimeout(refreshTimestamp, 1000 * 60) ; // Refresh every 60 seconds
	refreshEphemerides() ;
	if (planes.length == 0 || pilots.length == 0 || instructors.length <= 1) {
		console.log("Planes.js or pilots.js or instructors.js has a length of 0") ;
		alert("Erreur lors du chargement des avions, pilotes et instructeurs.\nPrevenez eric@vyncke.org et essayez de faire un refresh ou un autre browser.") ;
	}
	for (var plane = 0; plane < planes.length; plane++) {
		var option = document.createElement("option");
		option.text = planes[plane].name ;
		option.value = planes[plane].id ;
		document.getElementById('planeSelect').add(option) ;
	}
	for (var ressource = 0; ressource < ressources.length; ressource++) {
		var option = document.createElement("option");
		option.text = ressources[ressource].name ;
		option.value = ressources[ressource].id.replace(/\+/g,' ') ;
		option.disabled = (!userIsInstructor && !userIsAdmin) ;
		document.getElementById('ressourceSelect').add(option) ;
	}
	for (var member = 0; member < members.length; member++) {
		var option = document.createElement("option");
		option.text = members[member].name ;
		option.value = members[member].id ;
		document.getElementById('memberSelect').add(option) ;
	}
	for (var pilot = 0; pilot < pilots.length; pilot++) {
		var option = document.createElement("option");
		option.text = pilots[pilot].name ;
		option.value = pilots[pilot].id ;
		document.getElementById('pilotSelect').add(option) ;
		id2Name[pilots[pilot].id] = pilots[pilot].name ;
	}
	for (var instructor = 0; instructor < instructors.length; instructor++) {
		var option = document.createElement("option");
		option.text = instructors[instructor].name ;
		option.value = instructors[instructor].id ;
		document.getElementById('instructorSelect').add(option) ;
		if (instructors[instructor].id > 0) {
			var option = document.createElement("option");
			option.text = instructors[instructor].name ;
			option.value = instructors[instructor].id ;
			document.getElementById('agendaItemInstructorSelect').add(option) ; // Solo (id = -1) is not a valid option for the agenda :-)
		}
	}
	myLog('in init(): calling initPlanningTable()') ;
	planningByPlane = false ; // By default, let's start in one single day mode with one line per plane
	initPlanningTable() ;
	hideBookingMessage() ;
	hidePilotDetails() ;
	// Display/Hide instructors agenda
    var spanDisplayAgenda = document.getElementById('toggleInstructorAgendaSpan') ;
	if (userIsStudent || userIsInstructor)
                spanDisplayAgenda.innerHTML = '+' + spanDisplayAgenda.innerHTML.substr(1) ;
	else
                spanDisplayAgenda.innerHTML = '-' + spanDisplayAgenda.innerHTML.substr(1) ;
	toggleInstructorAgenda() ;
	planningDate = document.getElementById('planningDate') ;
	// Overwrite the global datepickr prototype
	// datepickr.prototype.l10n.weekdays.longhand = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
	datepickr('#calendarIcon', { altInput: document.getElementById('planningDate'), dateFormat: 'd/m/Y'});
	myLog('in init(): calling refreshPlanningTable()') ;
	refreshPlanningTable() ;
	myLog('end of refreshPlanningTable()') ;
	myLog('end init()') ;
}
