/*
   Copyright 2014-2020 Eric Vyncke

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

*/

var
	userAgentMobile = navigator.userAgent.match(/(iPhone|iPod|iPad|Android|BlackBerry)/),
	isMobile = (userAgentMobile && userAgentMobile.length > 0),
	hideOptionalFields,
	browserWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth,
	browserHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight,
	browserOrientation = window.orientation ;

window.onorientationchange = function() {
  /*window.orientation returns a value that indicates whether iPhone is in portrait mode, landscape mode with the screen turned to the
left, or landscape mode with the screen turned to the right. */
	var orientation = window.orientation;
//	document.getElementById('logDiv').innerHTML += "New orientation: " + orientation + "<br/>" ;
}

function showTab(anchor) {
	console.log("showTab(" + anchor + ")") ;
	$('.nav-tabs a[href="' + anchor + '"]').tab('show');
}

function toggleOptionalFields() {
console.log('toggleOptionalFields(), hideOptionalFields = ' + hideOptionalFields) ;
return ;
	var allOptionalFields = document.getElementsByClassName("logbook-optional") ;
	for (var i = 0; i < allOptionalFields.length; i++) {
		if (hideOptionalFields) {
			allOptionalFields[i].style.visibility = 'hidden' ;
			allOptionalFields[i].style.display = 'none' ;
			document.getElementById('toggleOptionalButton').value = "Plus d'options" ;
		} else {
			allOptionalFields[i].style.visibility = 'visible' ;
			allOptionalFields[i].style.display = 'inline' ;
			document.getElementById('toggleOptionalButton').value = "Moins d'options" ;
		}
	}
	hideOptionalFields = ! hideOptionalFields ;
}

function bsAlert(message) {
	// alert-danger (red) or alert-warning (yellow) or alert-info (blue)
	var placeHolder = document.getElementById('alertPlaceHolder') ;
	// Remove any previous alert
	childNodes = placeHolder.childNodes ;
	if (childNodes[0]) placeHolder.removeChild(childNodes[0]) ;
	// Create a whole new alert
	var alertDiv = document.createElement("div");
	alertDiv.className = "alert alert-danger alert-dismissible col-sm-12 col-md-6 text-center" ;
	alertDiv.innerHTML = '<button type="button" class="close" data-dismiss="alert">&times;</button>' + message ;
//	placeHolder.replaceChild(alertDiv) ;
	// And put the new alert at the right place
	placeHolder.appendChild(alertDiv) ;
	console.log('bsAlert: ' + message) ;
}

function redirectDelete(id, auth, auditTime) {
	window.location.href = 'logbook.php?id=' + id + '&auth=' + auth + '&audit_time=' + auditTime;
}

function toggleButtons(hideCancel) {
	if (hideCancel) {
		// Make the Save button appear and the Cancel button disappear
		document.getElementById('logbookButton').disabled = false ;
		$('#logbookButton').collapse('show') ;
		if (document.getElementById('logbookCancelButton')) { // Cancel button not always displayed, e.g. when there are already segments on this booking
			document.getElementById('logbookCancelButton').disabled = true ;
			$('#logbookCancelButton').collapse('hide') ;
		}
	} else {
		// Make the Save button disappear and the Cancel button appear
		document.getElementById('logbookButton').disabled = true ;
		$('#logbookButton').collapse('hide') ;
		if (document.getElementById('logbookCancelButton')) { // Cancel button not always displayed, e.g. when there are already segments on this booking
			document.getElementById('logbookCancelButton').disabled = false ;
			$('#logbookCancelButton').collapse('show') ;
		}
	}
}

function takeoffTimeChanged() {
	takeoffDate.setUTCHours(document.getElementsByName("startHoursUTC")[0].value) ;
	takeoffDate.setUTCMinutes(document.getElementsByName("startMinutesUTC")[0].value) ;
	//PRE
	// Update the landing time based on new flight duration if compteurVol=0 (engineCompteur)
	if(compteurVol==0) {
		landingDate = new Date(takeoffDate.valueOf() + 1000 * 60 * (durationMinute + 60 * durationHour)) ;
		document.getElementsByName("endHoursUTC")[0].value = landingDate.getUTCHours() ;
		document.getElementsByName("endMinutesUTC")[0].value = landingDate.getUTCMinutes() ;
		document.getElementsByName("UTCDurationHour")[0].value = durationHour ;
		document.getElementsByName("UTCDurationMinute")[0].value = durationMinute ;
		// Make the Save button appear and the Cancel button disappear
		toggleButtons(true) ;
	}
	UTCTimeChanged(false);
	//PRE
}

function landingTimeChanged() {
	landingDate.setUTCHours(document.getElementsByName("endHoursUTC")[0].value) ;
	landingDate.setUTCMinutes(document.getElementsByName("endMinutesUTC")[0].value) ;
	//PRE
	// Update the landing time based on new flight duration if compteurVol=0 (engineCompteur)
	if(compteurVol==0) {
		takeoffDate = new Date(landingDate.valueOf() - 1000 * 60 * (durationMinute + 60 * durationHour)) ;
		document.getElementsByName("startHoursUTC")[0].value = takeoffDate.getUTCHours() ;
		document.getElementsByName("startMinutesUTC")[0].value = takeoffDate.getUTCMinutes() ;
		document.getElementsByName("UTCDurationHour")[0].value = durationHour ;
		document.getElementsByName("UTCDurationMinute")[0].value = durationMinute ;
		// Make the Save button appear and the Cancel button disappear
	}
	UTCTimeChanged(false);
	//PRE
	toggleButtons(true) ;
}

function engineTimeChanged(onInit) {
	//PRE
	if(compteurVol==1) {
		landingDate = new Date(takeoffDate.valueOf()) ;
		return;
	}
	//PRE
	var inputEngineStartHour = parseInt(document.getElementsByName('engineStartHour')[0].value) ;
	var inputEngineEndHour = parseInt(document.getElementsByName('engineEndHour')[0].value) ;
	
	console.log('engineTimeChanged, inputEngineStartHour=' + inputEngineStartHour) ;
	console.log('engineStartHour=' + engineStartHour) ;
	console.log('engineTimeChanged, inputEngineEndHour=' + inputEngineEndHour) ;
	console.log('engineEndtHour=' + engineEndHour) ;
	if (engineStartHour > 10) { // Need to have a sensible value
//		if (inputEngineStartHour + 10 < engineStartHour) {
//			bsAlert('Temps moteur début doit être supérieur à ' + engineStartHour ) ;
//			toggleButtons(false) ;
//			return ;
//		}
		if (inputEngineEndHour > engineStartHour + 50) {
			bsAlert('Temps moteur fin ne peut être supérieur à ' + (engineStartHour + 50) ) ;
		}
		if (inputEngineEndHour - inputEngineStartHour > 15) {
			bsAlert('Le durée de vol ne peut être supérieure à 15 heures') ;
		}
	}
	engineStartHour = inputEngineStartHour ;
	engineStartMinute = engineCounterType * parseInt(document.getElementsByName('engineStartMinute')[0].value) ;
	engineEndHour = inputEngineEndHour ;
	engineEndMinute = engineCounterType * parseInt(document.getElementsByName('engineEndMinute')[0].value) ;
	var minutes = (engineEndHour - engineStartHour) * 60 + (engineEndMinute - engineStartMinute) ;
	durationMinute = minutes % 60 ;
	durationHour = (minutes - durationMinute) / 60 ;
	//PRE
	//Before
	//document.getElementsByName('engineDurationHour')[0].innerHTML = durationHour ;
	//document.getElementsByName('engineDurationMinute')[0].innerHTML = durationMinute ;
	//After
	document.getElementsByName('engineDurationHour')[0].value = durationHour ;
	document.getElementsByName('engineDurationMinute')[0].value = durationMinute ;
	//PRE
	// Update the landing time based on new flight duration
	landingDate = new Date(takeoffDate.valueOf() + 1000 * 60 * (durationMinute + 60 * durationHour)) ;
	document.getElementsByName("endHoursUTC")[0].value = landingDate.getUTCHours() ;
	document.getElementsByName("endMinutesUTC")[0].value = landingDate.getUTCMinutes() ;
	if (minutes <= 0) {
		bsAlert('Temps moteur fin doit être plus grand que le temps moteur début') ;
		toggleButtons(false) ;
		document.getElementById('flightSchedule').style.opacity = 0.7 ;
		document.getElementsByName('startHoursUTC')[0].disabled = true ;
		document.getElementsByName('startMinutesUTC')[0].disabled = true ;
		document.getElementsByName('endHoursUTC')[0].disabled = true ;
		document.getElementsByName('endMinutesUTC')[0].disabled = true ;
		document.getElementsByName('engineDurationHour')[0].style.backgroundColor = 'red' ;
		document.getElementsByName('engineDurationMinute')[0].style.backgroundColor = 'red' ;
	} else if (!onInit) {
		toggleButtons(true) ;
		document.getElementById('flightSchedule').style.opacity = 1.0 ;
		document.getElementsByName('startHoursUTC')[0].disabled = false ;
		document.getElementsByName('startMinutesUTC')[0].disabled = false ;
		document.getElementsByName('endHoursUTC')[0].disabled = false ;
		document.getElementsByName('endMinutesUTC')[0].disabled = false ;
		document.getElementsByName('engineDurationHour')[0].style.backgroundColor = 'lightgray' ;
		document.getElementsByName('engineDurationMinute')[0].style.backgroundColor = 'lightgray' ;
	}
	//PRE
	else {
		document.getElementsByName('engineDurationHour')[0].style.backgroundColor = 'lightgray' ;
		document.getElementsByName('engineDurationMinute')[0].style.backgroundColor = 'lightgray' ;		
	}
	//PRE
}


function flightTimeChanged(onInit) {
	flightStartHour = document.getElementsByName('flightStartHour')[0].value ;
	flightStartMinute = document.getElementsByName('flightStartMinute')[0].value ;
	flightEndHour = document.getElementsByName('flightEndHour')[0].value ;
	flightEndMinute = document.getElementsByName('flightEndMinute')[0].value ;
	var minutes = (flightEndHour - flightStartHour) * 60 + (flightEndMinute - flightStartMinute) ;
	flightDurationMinute = minutes % 60 ;
	flightDurationHour = (minutes - flightDurationMinute) / 60 ;
	//PRE
	//document.getElementById('flightDurationHour').innerHTML = flightDurationHour ;
	//document.getElementById('flightDurationMinute').innerHTML = flightDurationMinute ;
	document.getElementsByName('flightDurationHour')[0].value = flightDurationHour ;
	document.getElementsByName('flightDurationMinute')[0].value = flightDurationMinute ;	
	//PRE
	if (minutes <= 0) {
		toggleButtons(false) ;
		document.getElementById('flightSchedule').style.opacity = 0.7 ;
		document.getElementsByName('startHoursUTC')[0].disabled = true ;
		document.getElementsByName('startMinutesUTC')[0].disabled = true ;
		document.getElementsByName('endHoursUTC')[0].disabled = true ;
		document.getElementsByName('endMinutesUTC')[0].disabled = true ;
		//PRE
		//document.getElementById('flightDurationHour').style.backgroundColor = 'red' ;
		//document.getElementById('flightDurationMinute').style.backgroundColor = 'red' ;
		document.getElementsByName('flightDurationHour')[0].style.backgroundColor = 'red' ;
		document.getElementsByName('flightDurationMinute')[0].style.backgroundColor = 'red' ;
		//PRE
	} else if (!onInit) {
		toggleButtons(true) ;
		document.getElementById('flightSchedule').style.opacity = 1.0 ;
		document.getElementsByName('startHoursUTC')[0].disabled = false ;
		document.getElementsByName('startMinutesUTC')[0].disabled = false ;
		document.getElementsByName('endHoursUTC')[0].disabled = false ;
		document.getElementsByName('endMinutesUTC')[0].disabled = false ;
		//PRE
		//document.getElementById('flightDurationHour').style.backgroundColor = 'lightgray' ;
		//document.getElementById('flightDurationMinute').style.backgroundColor = 'lightgray' ;
		document.getElementsByName('flightDurationHour')[0].style.backgroundColor = 'lightgray' ;
		document.getElementsByName('flightDurationMinute')[0].style.backgroundColor = 'lightgray' ;
		//PRE
	}
}

//PRE
function UTCTimeChanged(onInit) {
	var inputUTCStartHour = parseInt(document.getElementsByName('startHoursUTC')[0].value) ;
	var inputUTCEndHour = parseInt(document.getElementsByName('endHoursUTC')[0].value) ;
	var inputUTCStartMinute = parseInt(document.getElementsByName('startMinutesUTC')[0].value) ;
	var inputUTCEndMinute = parseInt(document.getElementsByName('endMinutesUTC')[0].value) ;
	var minutes = (inputUTCEndHour - inputUTCStartHour) * 60 + (inputUTCEndMinute - inputUTCStartMinute) ;
	durationUTCMinute = minutes % 60 ;
	durationUTCHour = (minutes - durationUTCMinute) / 60 ;
	document.getElementsByName('UTCDurationHour')[0].value = durationUTCHour ;
	document.getElementsByName('UTCDurationMinute')[0].value = durationUTCMinute ;
	if (minutes <= 0) {
		bsAlert('Temps UTC fin doit être plus grand que le temps UTC début') ;
		toggleButtons(false) ;
		document.getElementsByName('UTCDurationHour')[0].style.backgroundColor = 'red' ;
		document.getElementsByName('UTCDurationMinute')[0].style.backgroundColor = 'red' ;
	} else if (!onInit) {
		toggleButtons(true) ;
		document.getElementsByName('startHoursUTC')[0].disabled = false ;
		document.getElementsByName('startMinutesUTC')[0].disabled = false ;
		document.getElementsByName('endHoursUTC')[0].disabled = false ;
		document.getElementsByName('endMinutesUTC')[0].disabled = false ;
		document.getElementsByName('UTCDurationHour')[0].style.backgroundColor = 'lightgray' ;
		document.getElementsByName('UTCDurationMinute')[0].style.backgroundColor = 'lightgray' ;
	}
	else {
		document.getElementsByName('UTCDurationHour')[0].disabled = true ;
		document.getElementsByName('UTCDurationMinute')[0].disabled = true ;
		document.getElementsByName('UTCDurationHour')[0].style.backgroundColor = 'lightgray' ;
		document.getElementsByName('UTCDurationMinute')[0].style.backgroundColor = 'lightgray' ;
	}
}
//PRE

function prefillDropdownMenus(selectName, valuesArray, selectedValue) {
	var select = document.getElementsByName(selectName)[0] ;

	if (valuesArray.length <= 0)
		alert("Aucune valeur pour: " + selectName + ". Prévenir par email eric@vyncke.org avec si possible une capture d'écran. Essayez de rafraîchir la page.") ;
	for (var i = 0; i < valuesArray.length; i++) {
		var option = document.createElement("option");
		option.text = valuesArray[i].name ;
		option.value = valuesArray[i].id ;
		option.selected = valuesArray[i].id == selectedValue ;
		select.add(option) ;
	}
}

function initLogbook() {
	console.log("starting initLogbook()") ;
//	document.getElementById('logDiv').style.top = browserHeight - 100 ;
//	document.getElementById('logDiv').innerHTML = "Optimized for smartphones<br/>Browser dimensions: " + browserWidth + ' x ' + browserHeight + ", orientation: " + browserOrientation + '<br/>' ;
//	document.getElementById('logDiv').innerHTML += (isMobile) ? 'Using a mobile device<br/>' : 'Using a desktop<br/>' ;
// Should also prepare the CSS for logDiv
// which was #logDiv { color: gray; font-size: 8; border-top-style: solid ; border-width: 1px; position: relative;}
	//PRE
	compteurVol=0;
	if(document.getElementsByName('engineStartHour').length==0) {
		console.log('initLogbook, No engine UI BOX') ;
		compteurVol=1;
	}
	//PRE

	// As too many pilots do not fill the engine time, let's focus on the start time...
//	document.getElementsByName("engineEndHour")[0].focus();
	//PRE
	//document.getElementsByName("engineStartHour")[0].focus();
	document.getElementsByName("startHoursUTC")[0].focus();
	document.getElementsByName("startHoursUTC")[0].value = takeoffDate.getUTCHours() ;
	document.getElementsByName("startMinutesUTC")[0].value = takeoffDate.getUTCMinutes() ;
	document.getElementsByName("endHoursUTC")[0].value = takeoffDate.getUTCHours() +1;
	document.getElementsByName("endMinutesUTC")[0].value = takeoffDate.getUTCMinutes() ;
	//PRE
	// Compute the landing time based on estimated flight duration...
	engineTimeChanged(true) ;
	UTCTimeChanged(true);
	// Prefill the select drop-down
	prefillDropdownMenus('plane', planes, planeId) ;
	prefillDropdownMenus('pilot', pilots, pilotId) ;
	prefillDropdownMenus('instructor', instructors, instructorId) ;
	// Hide optional fields on mobile devices
//	hideOptionalFields = !isMobile ;
//	toggleOptionalFields() ;
	console.log("end of initLogbook()") ;
}
