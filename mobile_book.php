<?php
/*
   Copyright 2013-2025 Eric Vyncke

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

require_once "dbi.php" ;

if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}

$id = (isset($_REQUEST['id'])) ? $_REQUEST['id'] : '' ; // Direct access to a booking by id
$auth = (isset($_REQUEST['auth'])) ? $_REQUEST['auth'] : '';

if ($id and is_numeric($id)) {
	if (($userId <= 0) and ($auth != md5($id . $shared_secret))) die("Wrong key for booking#$id: $auth ") ;
	if (! is_numeric($id)) die("Wrong booking id: $id") ;
	$result = mysqli_query($mysqli_link, "select r_id, r_plane, r_start, r_stop, r_type, r_pilot, r_who, r_date, 
		convert(r_comment using utf8) as r_comment, r_from, r_to,
		p.username as username, convert(p.name using utf8) as pilot_name,
		convert(i.name using utf8) as instructor_name, w.username as username2, convert(w.name using utf8) as booker_name,
		if (date(r_start) = current_date(), 1, 0) as today,
		if(r_stop >= sysdate(), 1, 0) as can_cancel, if (r_start < sysdate(), 1, 0) as can_log
		from $table_bookings join $table_users p on r_pilot = p.id left join $table_users i on r_instructor = i.id,
		$table_users as w, $table_person
		where r_id = $id and r_who = w.id and r_cancel_date is null") or die("Cannot access the booking #$id: " . mysqli_error($mysqli_link)) ;
	$booking = mysqli_fetch_array($result) or die("Réservation inconnue") ;
	$action = "Modifier" ;
	$startDay = substr($booking['r_start'], 0, 10) ;
	$startHour = substr($booking['r_start'], 11, 5) ;
	$endDay = substr($booking['r_stop'], 0, 10) ;
	$endHour = substr($booking['r_stop'], 11, 5) ;
	$selectedPlane = $booking['r_plane'] ;
	$comment = db2web($booking['r_comment']) ;
	$from = $booking['r_from'] ;
	$via1 = $booking['r_via1'] ;
	$via2 = $booking['r_via2'] ;
	$to = $booking['r_to'] ;
} else {
	$action = "Réserver" ;
	$startDay = (isset($_REQUEST['date'])) ? mysqli_real_escape_string($mysqli_link, $_REQUEST['date']) : date('Y-m-d') ;
	$endDay = $startDay ;
	if (isset($_REQUEST['start'])) {
		$startMinute = substr(mysqli_real_escape_string($mysqli_link, $_REQUEST['start']), 14, 2) ;
		$startHour = substr(mysqli_real_escape_string($mysqli_link, $_REQUEST['start']), 11, 5) ;
		if (isset($_REQUEST['end'])) {
			$endMinute = substr(mysqli_real_escape_string($mysqli_link, $_REQUEST['end']), 14, 2) ;
			$endHour = substr(mysqli_real_escape_string($mysqli_link, $_REQUEST['end']), 11, 5) ;
		} else {
			$endHour = (1 + intval(substr($startHour, 0, 2))) . ':' . $startMinute ;
		}
	} else {
		// Need to round up to next 15 minutes
		$startMinute = date('i') ;
		$startMinute = floor($startMinute / 15) ;
		$startMinute = 15 * ($startMinute + 1) ;
		if ($startMinute >= 60) $startMinute = '00' ;
		$startHour = date('H:') . $startMinute ;
		$endHour = (1 + date('H')) . ':' . $startMinute ;
	}
	$selectedPlane = (isset($_REQUEST['plane'])) ? mysqli_real_escape_string($mysqli_link, $_REQUEST['plane']) : '' ;
	$comment = '' ;
	$from = '' ;
	$via1 = '' ;
	$via2 = '' ;
	$to = '' ;
}

$header_postamble = "<script>
var
	selectedPlane = " . ((isset($selectedPlane) and $selectedPlane != '') ? "'$selectedPlane'" : "null") . ",
	pilotId = " . ((isset($booking)) ? $booking['r_pilot'] : $userId) . ",
	instructorId = " . ((isset($booking['r_instructor']) and $booking['r_instructor']) ? $booking['r_instructor'] : -1)  . " ;
</script>
<script src=\"data/instructors.js\"></script>
<script src=\"data/pilots.js\"></script>" ;

require_once 'mobile_header5.php' ;
?> 
<h2>Nouvelle réservation d'un avion</h2>

<div class="container-fluid">

<!-- Not a real form as js/mobile.js has the onclick code to submit the form -->
<!-- not even sure whether it is required -->
<input type="hidden" id="departingAirport" value="<?=$from?>">
<input type="hidden" id="destinationAirport" value="<?=$to?>">
<input type="hidden" id="via1Airport" value="<?=$via1?>">
<input type="hidden" id="via2Airport" value="<?=$via2?>">
<!--
2 boutons: annuler la modification / modifier la réservation
-->

<div class="alert alert-info alert-dismissible fade show" id="bookingMessageDiv" style="visibility: hidden; display: none;">
	<span id="bookingMessageSpan"></span>
	<br/>
	<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Retour aux réservations"></button>
	<hr>
	Cliquez sur la croix pour fermer ce message et vous allez être redirigé vers la page des réservations.
</div><!-- alert -->

<div class="row">
		<label class="form-label col-xs-6 col-md-3" for="pilotSelect">Pilote:</label>
		<div class="col-xs-6 col-md-3">
			<select class="form-control" id="pilotSelect" name="pilot"></select>
		</div>

		<label class="control-label col-xs-6 col-md-3" for="instructorSelect">Instructeur:</label>
		<div class="col-xs-6 col-md-3">
			<select class="form-control" id="instructorSelect" name="instructor"></select>
		</div>
</div><!-- row -->

<div class="row">
		<label class="control-label col-xs-6 col-md-3" for="planeSelect">Avion:</label>
		<div class="col-xs-6 col-md-3">
			<select class="form-control" id="planeSelect" name="plane"></select>
		</div>

</div><!-- row -->

<div class="row">
		<label class="control-label col-xs-6 col-md-4" for="startDayInput">Jour début:</label>
		<div class="col-xs-6 col-md-2">
			<input type="date" class="form-control" id="startDayInput" name="startDay" value="<?=$startDay?>" oninput="adjustEndDay();">
		</div>
		<label class="control-label col-xs-6 col-md-4" for="startHourInput">Heure début:</label>
		<div class="col-xs-6 col-md-2">
			<input type="time" class="form-control" id="startHourInput" name="startHour" value="<?=$startHour?>" min="09:00" max="20:00" step="900" oninput="adjustEndHour();">
		</div>
</div><!-- row -->

<div class="row">
		<label class="control-label col-xs-6 col-md-4" for="endDayInput">Jour fin:</label>
		<div class="col-xs-6 col-md-2">
			<input type="date" class="form-control" id="endDayInput" name="endDay" value="<?=$endDay?>">
		</div>
		<label class="control-label col-xs-6 col-md-4" for="endHourInput">Heure fin:</label>
		<div class="col-xs-6 col-md-2">
			<input type="time" class="form-control" id="endHourInput" name="endHour" value="<?=$endHour?>" min="09:00" max="20:00" step="900">
		</div>
</div><!-- row -->

<div class="row">
		<label class="control-label col-xs-6 col-md-4" for="commentTextArea">Remarque:</label>
	      <textarea class="form-control col-xs-12 col-md-12" name="comment" id="commentTextArea" width="100%" row="5" col="80"><?=$comment?></textarea>
</div><!-- row -->



<div class="row">
		<div class="col-xs-3 col-md-4">
<?php
if ($action == 'Modifier') {
?>
	      <button class="btn btn-primary" onclick="showSpinner();modifyBooking(<?=$id?>, '<?=$auth?>');hideSpinner();"><?=$action?></button>
<?php
} else {
?>
	      <button class="btn btn-primary" id="idBookButton" onclick="bookClicked();"><?=$action?></button>
<?php
}
?>
   		</div>
</div><!-- row -->

<div id="spinner" class="d-none"
	style="position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.7); z-index:1051; display:flex; align-items:center; justify-content:center;">
	<div class="spinner-border" style="width:4rem; height:4rem;" role="status"><span class="visually-hidden">En cours...</span></div>
</div>
<?php
if (isset($booking['r_type']) and $booking['r_type'] == BOOKING_MAINTENANCE)
	print("<br/><div class=\"alert alert-danger\">Cette réservation est une maintenance.</div>") ;
?>
</div> <!-- container-fluid-->
<script>

// When the alert is closed, redirect to the calling page $_SERVER['HTTP_REFERER']
document.getElementById('bookingMessageDiv').addEventListener('closed.bs.alert', function () {
	console.log("Redirecting to <?=$_SERVER['HTTP_REFERER']?> as closed.bs.alter event received"); ;
	window.location.href = "<?=$_SERVER['HTTP_REFERER']?>";
});

function adjustEndDay() {
	var startDay = document.getElementById('startDayInput').value ;
	var endDay = document.getElementById('endDayInput').value ;
	if (endDay < startDay) {
		document.getElementById('endDayInput').value = startDay ;
		document.getElementById('startHourInput').value = "09:00" ;
		document.getElementById('endHourInput').value = "10:00" ;
	}
}
function adjustEndHour() {
	var startHour = document.getElementById('startHourInput').value ;
	var endHour = document.getElementById('endHourInput').value ;
	var sh = parseInt(startHour.substr(0, 2)) ;
	var sm = parseInt(startHour.substr(3, 2)) ;
	sm += 60 ;
	while (sm >= 60) { sm -= 60 ; sh++ ; }
	if (sh >= 24) sh = 23 ;
	document.getElementById('endHourInput').value = (sh < 10 ? '0' : '') + sh + ':' + (sm < 10 ? '0' : '') + sm ;
}

function showSpinner() {
	document.getElementById('spinner').classList.remove('d-none');
}

function hideSpinner() {
	document.getElementById('spinner').classList.add('d-none');
}

function bookClicked() {
	document.getElementById('idBookButton').disabled = true ;
	// This function is defined in js/mobile.js and this file seems to be the only one using it...
	// It knows about the field names and the spinner div
	// It is also fully asynchronous
	createBooking() ;
}
</script>
</body>
</html>