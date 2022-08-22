/*
   Copyright 2014-2022 Eric Vyncke, Patrick Reginster

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
	checkEngineCounter = true,
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

function redirectMobileDelete(id, auth, auditTime) {
	window.location.href = 'mobile_logbook.php?id=' + id + '&auth=' + auth + '&audit_time=' + auditTime;
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
	// Update the landing time based on new flight duration if compteurVol=0 (engineCompteur)
	landingDate = new Date(takeoffDate.valueOf() + 1000 * 60 * (durationMinute + 60 * durationHour)) ;
	document.getElementsByName("endHoursUTC")[0].value = landingDate.getUTCHours() ;
	document.getElementsByName("endMinutesUTC")[0].value = landingDate.getUTCMinutes() ;
	document.getElementsByName("UTCDurationHour")[0].value = durationHour ;
	document.getElementsByName("UTCDurationMinute")[0].value = durationMinute ;
	// Make the Save button appear and the Cancel button disappear
	toggleButtons(true) ;
	UTCTimeChanged(false);
}

function landingTimeChanged() {
	landingDate.setUTCHours(document.getElementsByName("endHoursUTC")[0].value) ;
	landingDate.setUTCMinutes(document.getElementsByName("endMinutesUTC")[0].value) ;
	// Update the landing time based on new flight duration if compteurVol=0 (engineCompteur)
	takeoffDate = new Date(landingDate.valueOf() - 1000 * 60 * (durationMinute + 60 * durationHour)) ;
	document.getElementsByName("startHoursUTC")[0].value = takeoffDate.getUTCHours() ;
	document.getElementsByName("startMinutesUTC")[0].value = takeoffDate.getUTCMinutes() ;
	document.getElementsByName("UTCDurationHour")[0].value = durationHour ;
	document.getElementsByName("UTCDurationMinute")[0].value = durationMinute ;
	// Make the Save button appear and the Cancel button disappear
	UTCTimeChanged(false);
	toggleButtons(true) ;
}

function engineTimeChanged(onInit) {
	var inputEngineStartHour = parseInt(document.getElementsByName('engineStartHour')[0].value) ;
	var inputEngineEndHour = parseInt(document.getElementsByName('engineEndHour')[0].value) ;
	
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
	document.getElementsByName('engineDurationHour')[0].value = durationHour ;
	document.getElementsByName('engineDurationMinute')[0].value = durationMinute ;
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
		document.getElementsByName('engineDurationHour')[0].style.backgroundColor = 'pink' ;
		document.getElementsByName('engineDurationMinute')[0].style.backgroundColor = 'pink' ;
	} else if (!onInit) {
		var engineStartHourMax= engineStartHourInit+50;
		var engineStartHourMin= engineStartHourInit-50;
		if(checkEngineCounter && (engineStartHour > engineStartHourMax || engineStartHour < engineStartHourMin)) {
			bsAlert('Compteur Moteur trop different du compteur introduit par le pilote précédent. Vérifiez la valeur.') ;
			toggleButtons(false) ;
			document.getElementsByName('engineStartHour')[0].style.backgroundColor = 'pink';			
		}
		else {
			toggleButtons(true) ;
			document.getElementById('flightSchedule').style.opacity = 1.0 ;
			document.getElementsByName('startHoursUTC')[0].disabled = false ;
			document.getElementsByName('startMinutesUTC')[0].disabled = false ;
			document.getElementsByName('endHoursUTC')[0].disabled = false ;
			document.getElementsByName('endMinutesUTC')[0].disabled = false ;
			document.getElementsByName('engineDurationHour')[0].style.backgroundColor = 'lightgray' ;
			document.getElementsByName('engineDurationMinute')[0].style.backgroundColor = 'lightgray' ;
			document.getElementsByName('engineStartHour')[0].style.backgroundColor = 'white';			
			UTCTimeChanged(false);
		}
	}
	else { // onInit
		engineStartHourInit=engineStartHour;
		document.getElementsByName('engineDurationHour')[0].style.backgroundColor = 'lightgray' ;
		document.getElementsByName('engineDurationMinute')[0].style.backgroundColor = 'lightgray' ;		
		UTCTimeChanged(true);
	}
}


function flightTimeChanged(onInit) {
	flightStartHour =  parseInt(document.getElementsByName('flightStartHour')[0].value) ;
	flightStartMinute =  parseInt(document.getElementsByName('flightStartMinute')[0].value) ;
	flightEndHour =  parseInt(document.getElementsByName('flightEndHour')[0].value) ;
	flightEndMinute =  parseInt(document.getElementsByName('flightEndMinute')[0].value) ;
	var minutes = (flightEndHour - flightStartHour) * 60 + (flightEndMinute - flightStartMinute) ;
	flightDurationMinute = minutes % 60 ;
	flightDurationHour = (minutes - flightDurationMinute) / 60 ;
	var minutesEngine=durationHour*60+durationMinute;
	document.getElementsByName('flightDurationHour')[0].value = flightDurationHour ;
	document.getElementsByName('flightDurationMinute')[0].value = flightDurationMinute ;	
	if (minutes <= 0) {
		bsAlert('Temps vol fin doit être plus grand que le temps vol début') ;
		toggleButtons(false) ;
		document.getElementById('flightSchedule').style.opacity = 0.7 ;
		document.getElementsByName('startHoursUTC')[0].disabled = true ;
		document.getElementsByName('startMinutesUTC')[0].disabled = true ;
		document.getElementsByName('endHoursUTC')[0].disabled = true ;
		document.getElementsByName('endMinutesUTC')[0].disabled = true ;
		document.getElementsByName('flightDurationHour')[0].style.backgroundColor = 'pink' ;
		document.getElementsByName('flightDurationMinute')[0].style.backgroundColor = 'pink' ;
	} 
	else if(minutes>minutesEngine) {
		bsAlert('Temps moteur fin doit être plus grand que le temps vol') ;
		toggleButtons(false) ;
		document.getElementsByName('flightDurationHour')[0].style.backgroundColor = 'pink' ;
		document.getElementsByName('flightDurationMinute')[0].style.backgroundColor = 'pink' ;
	} else if (!onInit) {
		var flightStartHourMax= flightStartHourInit+50;
		var flightStartHourMin= flightStartHourInit-50;
		if(flightStartHour > flightStartHourMax || flightStartHour < flightStartHourMin) {
			bsAlert('Compteur Vol trop different du compteur introduit par le pilote précédent. Vérifiez la valeur.') ;
			toggleButtons(false) ;
			document.getElementsByName('flightStartHour')[0].style.backgroundColor = 'pink';			
		}
		else {
			toggleButtons(true) ;
			document.getElementsByName('startHoursUTC')[0].style.backgroundColor = 'white';
			document.getElementById('flightSchedule').style.opacity = 1.0 ;
			document.getElementsByName('startHoursUTC')[0].disabled = false ;
			document.getElementsByName('startMinutesUTC')[0].disabled = false ;
			document.getElementsByName('endHoursUTC')[0].disabled = false ;
			document.getElementsByName('endMinutesUTC')[0].disabled = false ;
			document.getElementsByName('flightDurationHour')[0].style.backgroundColor = 'lightgray' ;
			document.getElementsByName('flightDurationMinute')[0].style.backgroundColor = 'lightgray' ;
			document.getElementsByName('flightStartHour')[0].style.backgroundColor = 'white';			
		}
	}
	else {
		document.getElementsByName('flightDurationHour')[0].style.backgroundColor = 'lightgray' ;
		document.getElementsByName('flightDurationMinute')[0].style.backgroundColor = 'lightgray' ;		
		document.getElementsByName('flightDurationHour')[0].style.color = 'black' ;
		document.getElementsByName('flightDurationMinute')[0].stylecolor = 'black' ;		
		//document.getElementsByName('flightDurationHour')[0].disabled = true ;
		//document.getElementsByName('flightDurationMinute')[0].disabled = true ;		
		flightStartHourInit=flightStartHour;
	}
}

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
		document.getElementsByName('UTCDurationHour')[0].style.backgroundColor = 'pink' ;
		document.getElementsByName('UTCDurationMinute')[0].style.backgroundColor = 'pink' ;
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
		//document.getElementsByName('UTCDurationHour')[0].disabled = true ;
		//document.getElementsByName('UTCDurationMinute')[0].disabled = true ;
		document.getElementsByName('UTCDurationHour')[0].style.backgroundColor = 'lightgray' ;
		document.getElementsByName('UTCDurationMinute')[0].style.backgroundColor = 'lightgray' ;
		document.getElementsByName('UTCDurationHour')[0].style.color = 'black' ;
		document.getElementsByName('UTCDurationMinute')[0].style.color = 'black' ;
	}
}

function planeChanged() {
	// Let's clear the engine index
	document.getElementsByName('engineStartHour')[0].value = null ;
	document.getElementsByName('engineStartHour')[0].min = null ;
	document.getElementsByName('engineStartHour')[0].max = null ;
	document.getElementsByName('engineStartMinute')[0].value = null ;
	document.getElementsByName('engineEndHour')[0].value = null ;
	document.getElementsByName('engineEndHour')[0].min = null ;
	document.getElementsByName('engineEndHour')[0].max = null ;
	document.getElementsByName('engineEndMinute')[0].value = null ;
	document.getElementsByName('engineDurationHour')[0].value = 0 ;
	document.getElementsByName('engineDurationMinute')[0].value = 0 ;
	// Let's clear the flight time index (... issue is of course it is not always set... so not applicable to PH-AML @ RAPCS...
	
	// Let's clear the end of block time
	document.getElementsByName('startHoursUTC')[0].value = null ;
	document.getElementsByName('startMinutesUTC')[0].value = null ;
	document.getElementsByName('endHoursUTC')[0].value = null ;
	document.getElementsByName('endMinutesUTC')[0].value = null ;
	document.getElementsByName('UTCDurationHour')[0].value = 0 ;
	document.getElementsByName('UTCDurationMinute')[0].value = 0 ;
	// No more engine checks
	checkEngineCounter = false ;
}

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

function findMember(a, m) {
        for (let i = 0 ; i < a.length ; i++)
                if (a[i].id == m)
                        return a[i].name ;
        return null ;
}

function initLogbook() {
	console.log("starting initLogbook()") ;
//	document.getElementById('logDiv').style.top = browserHeight - 100 ;
//	document.getElementById('logDiv').innerHTML = "Optimized for smartphones<br/>Browser dimensions: " + browserWidth + ' x ' + browserHeight + ", orientation: " + browserOrientation + '<br/>' ;
//	document.getElementById('logDiv').innerHTML += (isMobile) ? 'Using a mobile device<br/>' : 'Using a desktop<br/>' ;
// Should also prepare the CSS for logDiv
// which was #logDiv { color: gray; font-size: 8; border-top-style: solid ; border-width: 1px; position: relative;}

	// As too many pilots do not fill the engine time, let's focus on the start time...
//	document.getElementsByName("engineEndHour")[0].focus();
	document.getElementsByName("startHoursUTC")[0].focus();
	document.getElementsByName("startHoursUTC")[0].value = takeoffDate.getUTCHours() ;
	document.getElementsByName("startMinutesUTC")[0].value = takeoffDate.getUTCMinutes() ;
	document.getElementsByName("endHoursUTC")[0].value = takeoffDate.getUTCHours() +1;
	document.getElementsByName("endMinutesUTC")[0].value = takeoffDate.getUTCMinutes() ;
	// Compute the landing time based on estimated flight duration...
	compteurVol=1;
	if(document.getElementsByName('flightStartHour').length==0) {
		console.log('initLogbook, No flight UI BOX') ;
		compteurVol=0;
	}
	
	engineTimeChanged(true) ;
	UTCTimeChanged(true);
	if(compteurVol==1) {
		flightTimeChanged(true);
	}
	// Prefill the select drop-down
	prefillDropdownMenus('plane', planes, planeId) ;
	prefillDropdownMenus('pilot', pilots, pilotId) ;
	prefillDropdownMenus('instructor', instructors, instructorId) ;
	prefillDropdownMenus('share_member', shareCodes, 0) ;
	prefillDropdownMenus('share_member', members, 0) ;

	// Convert all share codes into strings
        var collection = document.getElementsByClassName("shareCodeClass") ;
        for (let i = 0; i < collection.length ; i++) {
                var spanElem = collection[i] ;
                var member = spanElem.innerText ;
                memberText = findMember(shareCodes, member) ;
                if (memberText == null)
                        memberText = findMember(members, member) ;
                if (memberText != null)
                        spanElem.innerText = ' (' + memberText + ')';
        }
	console.log("end of initLogbook()") ;
}
