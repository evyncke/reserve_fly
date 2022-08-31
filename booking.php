<?php
/*
   Copyright 2013-2020 Eric Vyncke

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

ob_start("ob_gzhandler");

require_once "dbi.php" ;
require_once 'facebook.php' ;

//print_r($_SERVER) ;

$id = (isset($_REQUEST['id'])) ? $_REQUEST['id'] : '' ; // Direct access to a booking by id
$me = (isset($_REQUEST['me'])) ? $_REQUEST['me'] : '' ; // Access to the closest booking for pilot/instructor 'me'
$auth = (isset($_REQUEST['auth'])) ? $_REQUEST['auth'] : '';

if ($id) {
	if ($auth != md5($id . $shared_secret)) die("Wrong key for booking#$id: $auth ") ;
	if (! is_numeric($id)) die("Wrong booking id: $id") ;
	$result = mysqli_query($mysqli_link, "select r_id, r_plane, r_start, r_stop, r_type, r_pilot, r_who, r_date, 
		convert(r_comment using utf8) as r_comment, r_from, r_to, r_duration,
		p.username as username, convert(p.name using utf8) as pilot_name,
		convert(i.name using utf8) as instructor_name, w.username as username2, convert(w.name using utf8) as booker_name,
		p.email as email, home_phone, work_phone, cell_phone,
		if (date(r_start) = current_date(), 1, 0) as today,
		if(r_stop >= sysdate(), 1, 0) as can_cancel, if (r_start < sysdate(), 1, 0) as can_log
		from $table_bookings join $table_users p on r_pilot = p.id left join $table_users i on r_instructor = i.id,
		$table_users as w, $table_person
		where r_id = $id and r_who = w.id and r_cancel_date is null") or die("Cannot access the booking #$id: " . mysqli_error($mysqli_link)) ;
} elseif ($me) {
	if ($auth != md5($me . $shared_secret)) die("Wrong key for booking#$me: $auth ") ;
	if (! is_numeric($me)) die("Wrong booking me: $me") ;
	$result = mysqli_query($mysqli_link, "select r_id, r_plane, r_start, r_stop, r_type, r_pilot, r_who, r_date, 
		convert(r_comment using utf8) as r_comment, r_from, r_to, r_duration,
		p.username as username, convert(p.name using utf8) as pilot_name, convert(i.name using utf8) as instructor_name,
		w.username as username2, convert(w.name using utf8) as booker_name,
		p.email as email, home_phone, work_phone, cell_phone,
		if (date(r_start) = current_date(), 1, 0) as today,
		abs(date(r_start) - current_date()) as today_distance,
		if(r_stop >= sysdate(), 1, 0) as can_cancel, if (r_start < sysdate(), 1, 0) as can_log
		from $table_bookings join $table_users p on r_pilot = p.id left join $table_users i on r_instructor = i.id,
		$table_users as w, $table_person
		where (r_pilot = $me or r_instructor = $me) and r_who = w.id and r_cancel_date is null
		order by today_distance asc limit 0,1") or die("Cannot access the booking #$me 2: " . mysqli_error($mysqli_link)) ;
} else 
	die("Missing parameters") ;

$booking = mysqli_fetch_array($result) ;

if (! $booking) die("D&eacute;sol&eacute; cette r&eacute;servation n'existe pas") ;

if ($id)
	$condition = "(r_pilot = $booking[r_pilot])" ;
else {
	$condition = "(r_pilot = $me or r_instructor = $me)" ;
	$id = $booking['r_id'] ;
	$auth = md5($id . $shared_secret) ;
}

// Find the previous/next booking
$result = mysqli_query($mysqli_link, "select * from $table_bookings JOIN $table_planes on r_plane = $table_planes.id
		where ressource = 0 and r_cancel_date is null and r_stop < '$booking[r_start]' and $condition order by r_start desc limit 0,1")
	or die("Cannot access previous booking: ".mysqli_error()) ;
$row = mysqli_fetch_array($result) ;
$previous_id = $row['r_id'] ;
$previous_auth = md5($previous_id . $shared_secret) ;
$result = mysqli_query($mysqli_link, "select * from $table_bookings JOIN $table_planes ON r_plane = $table_planes.id
		where ressource = 0 and r_cancel_date is null and r_start > '$booking[r_stop]' and $condition order by r_start asc limit 0,1")
	or die("Cannot access previous booking: ".mysqli_error()) ;
$row = mysqli_fetch_array($result) ;
$next_id = $row['r_id'] ;
$next_auth = md5($next_id . $shared_secret) ;

# fix the character set issue...
$booking['pilot_name'] = db2web($booking['pilot_name']) ;
$booking['booker_name'] = db2web($booking['booker_name']) ;
$booking['instructor_name'] = db2web($booking['instructor_name']) ;
$booking['r_comment'] = db2web($booking['r_comment']) ;

?><!DOCTYPE html>
<html lang="fr">
<head>
<link rel="stylesheet" type="text/css" href="booking.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<meta charset="utf-8">
<!--meta name="viewport" content="width=320"-->
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- http://www.alsacreations.com/article/lire/1490-comprendre-le-viewport-dans-le-web-mobile.html -->
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<title>Mes r&eacute;servations</title>
<!-- http://www.w3schools.com/bootstrap/ -->
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
<script>
var
// $_SERVER['HTTP_USER_AGENT'] contains also "Android" or "iPad"
	isMobile = navigator.userAgent.match(/(iPhone|iPod|iPad|Android|BlackBerry)/); 
	browserWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth,
	browserHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
	browserOrientation = window.orientation ;
	runwaysQFU = [ <?php print(implode(', ', $runways_qfu)) ; ?> ] ;

window.onorientationchange = function() {
  /*window.orientation returns a value that indicates whether iPhone is in portrait mode, landscape mode with the screen turned to the
left, or landscape mode with the screen turned to the right. */
	var orientation = window.orientation;
	document.getElementById('logDiv').innerHTML += "New orientation: " + orientation + "<br/>" ;
}

function displayMETAR() {
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
					document.getElementById('metarMessage').innerHTML = response.error ;
				} else {
					document.getElementById('metarMessage').innerHTML = '<b>' + response.METAR + '</b>' +
						'<br/>Density altitude at ' + response.station + ': ' +
						response.density_altitude + ' ft, elevation: ' + response.elevation + ' ft';
					setTimeout(displayMETAR, 1000 * 60 * 5) ; // Refresh every 5 minutes
					if (response.condition != null && response.condition == 'VMC')
                                                document.getElementById('metarMessage').style.backgroundColor =  'paleGreen' ;
					else if (response.condition != null && response.condition == 'IMC')
                                                document.getElementById('metarMessage').style.backgroundColor = 'pink' ;
					if (response.wind_velocity != null && response.wind_direction != null && response.wind_direction != 'VRB' && runwaysQFU.length > 0) {
						for (i = 0; i < runwaysQFU.length ; i += 1) {
							var qfuWindAngle = (runwaysQFU[i] - response.wind_direction) *  2 * Math.PI / 360 ; // In radians
							var crossComponent =  Math.abs(Math.round(response.wind_velocity * Math.sin(qfuWindAngle))) ;
							document.getElementById('metarMessage').innerHTML += '<br/>Runway ' + Math.round(runwaysQFU[i] / 10) + ': ' +
								'crosswind = ' + crossComponent + 'KT' ;
						}
					}
				}
			}
		}
	}
	var requestUrl = 'metar.php' ;
	XHR.open("GET", requestUrl, true) ;
	XHR.send(null) ;
}

function redirect(id, auth) {
	window.location.href = '<?=$_SERVER['PHP_SELF']?>' + '?id=' + id + '&auth=' + auth ;
}

function logbookClick () {
console.log("logbookClick() id=<?=$id?>") ;
	window.location.href = 'logbook.php?id=<?=$id?>&auth=<?=$auth?>' ;
}

function cancelConfirm() {
console.log("cancelConfirm") ;
	var XHR=new XMLHttpRequest();
	XHR.onreadystatechange = function() {
		if(XHR.readyState  == 4) {
			if(XHR.status  == 200) {
				try {
console.log('cancel_booking:' + XHR.responseText) ;
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
	var requestUrl = "cancel_booking.php?id=<?=$id?>&auth=<?=$auth?>" ;
console.log(requestUrl) ;
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
console.log("cancelFirstClick()") ;
	document.getElementById('confirmCancellation').style.visibility = 'visible' ;
	document.getElementById('confirmCancellation').style.display = 'block' ;
	document.getElementById('bookingTable').style.visibility = 'hidden' ;
	document.getElementById('bookingTable').style.display = 'none' ;
	document.getElementById('cancelButton').style.visibility = 'hidden' ;
	document.getElementById('cancelButton').style.display = 'none' ;
}

function init() {
	document.getElementById('logDiv').style.top = browserHeight - 100 ;
//	document.getElementById('logDiv').innerHTML = "Optimized for smartphones<br/>Browser dimensions: " + browserWidth + ' x ' + browserHeight + ", orientation: " + browserOrientation + '<br/>' ;
//	document.getElementById('logDiv').innerHTML += (isMobile) ? 'Using a mobile device<br/>' : 'Using a desktop<br/>' ;
}

</script>
</head>
<body onload="init();">
<div class="container">
<div class="row text-center">
	<div class="col-sm-12 text-center"><h2>Mes r&eacute;servations</h2></div>
</div> <!-- row -->
<div class="row">

<!-- div id="bookingDetails" -->
<!-- This div is for cancellation confirmation, not displayed by default -->
<div class="col-sm-4 col-md-6">
	<div id="confirmCancellation" class="jumbotron"><center>
		<h3>Annulation d'une r&eacute;servation: <?=$booking['r_plane']?></h3>
		<br/>
		<button class="btn btn-danger" onclick="cancelConfirm();">Je confirme l'annulation</button>
		<br/>
		<br/>
		<button class="btn btn-primary btn-default" onclick="abandonCancel();">Ne pas annuler la r&eacute;servation</button>
	</center></div> <!-- jumbotron -->
<table id="bookingTable">
	<tr><td class="bookingLabel">Avion:</td><td class="bookingValue"><?=$booking['r_plane']?></td><tr>
	<tr><td class="bookingLabel">D&eacute;but:</td><td class="bookingValue"><?=$booking['r_start']?></td><tr>
	<tr><td class="bookingLabel">Fin:</td><td class="bookingValue"><?=$booking['r_stop']?></td><tr>
	<tr><td class="bookingLabel">Pilote:</td><td class="bookingValue"><?=$booking['pilot_name']?></td><tr>
<?php
if ($booking['instructor_name'] != '') {
?>
	<tr><td class="bookingLabel">Instructeur:</td><td class="bookingValue"><?=$booking['instructor_name']?></td><tr>
<?php
} // end instructor present
?>
	<tr><td class="bookingLabel">Commentaire:</td><td class="bookingValue"><?=$booking['r_comment']?></td><tr>
	<tr><td class="bookingLabel">Effectu&eacute;e par:</td><td class="bookingValue"><?=$booking['booker_name']?></td><tr>
</table>
</div> <!-- col-->
<!-- /div-->

</div> <!-- row -->

<!-- Display previous / next -->
<div class="row">
<ul class="pager col-xs-12">
<?php
if ($previous_id != '') {
	print("<li class=\"previous\"><a href=\"$_SERVER[PHP_SELF]?id=$previous_id&auth=$previous_auth\">R&eacute;servation pr&eacute;c&eacute;dente</a></li>\n") ;
}
if ($next_id != '') {
	print("<li class=\"next\"><a href=\"$_SERVER[PHP_SELF]?id=$next_id&auth=$next_auth\">R&eacute;servation suivante</a></li>\n") ;
} 
?>
</ul>
</div> <!-- row -->


<?php
// If displayed booking is for today, display METAR
if ($booking['today']) {
?>
<div class="row">
	<br/>
	<div id="metarMessage" class="col bg-info">
	</div> <!-- col -->
</div> <!-- row -->
<script>
	displayMETAR() ;
</script>
<?php
}
// Need to display the cancel button only for future (including today) reservations
//
if ($booking['can_cancel']) {
?>
<div class="row">
	<br/>
	<div class="col-xs-12 text-center ">
		<button id="cancelButton" class="btn btn-primary btn-danger" onclick="cancelFirstClick();">Annuler la r&eacute;servation</button>
	</div><!-- col-->
</div> <!-- row -->
<?php
} 
if ($booking['can_log']) { // Should also be also checked when not already logged
?>
<div class="row">
	<br/>
	<div class="col-xs-12 text-center ">
		Pour entrer les heures moteurs pour le club et compléter votre carnet de vols, cliquez ci-dessous.
	</div><!-- col-->
	<div class="col-xs-12 text-center ">
		<button id="logbookButton" class="btn btn-success" onclick="logbookClick();">Carnet de routes</button>
	</div><!-- col-->
</div> <!-- row -->
<?php
}
?>
<div class="row">
	<div class="col hidden-xs">
	<div id="logDiv" class="col-sm-12"></div>
	</div> <!-- col -->
</div> <!-- row -->
</div> <!-- container-->
</body>
</html>
