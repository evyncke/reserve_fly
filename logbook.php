<?php
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
// TODO
// Ne pas afficher 'annuler' lorsqu'un segment est déjà entré
// 'Enregistrer le X-ème segment'
// Pour l'AML, confusion entre temps de vol / engine
//
ob_start("ob_gzhandler");

require_once "dbi.php" ;
require_once 'facebook.php' ;

$id = (isset($_REQUEST['id'])) ? mysqli_real_escape_string($mysqli_link, trim($_REQUEST['id'])) : '' ;
$auth = (isset($_REQUEST['auth'])) ? $_REQUEST['auth'] : '' ;

// Basic parameters sanitization
if (! is_numeric($id)) die("Logbook: wrong booking id: $id") ;

// Retrieve the booking
$result = mysqli_query($mysqli_link, "select r_id, r_plane, r_start, r_stop, r_type, r_pilot, r_instructor, r_who, r_date, 
	r_from, r_to, compteur_type, compteur_vol, model, compteur, compteur_date, 
	r_duration,date_add(r_start, interval 15 minute) as r_takeoff, date(r_start) as r_day
	from $table_bookings join jom_users as p on r_pilot = p.id, $table_planes as a,
	jom_users as w
	where r_id = $id and a.id = r_plane and a.ressource = 0") or die("Cannot access the booking #$id: " . mysqli_error($mysqli_link)) ;
$booking = mysqli_fetch_array($result) ;
if (! $booking) die("D&eacute;sol&eacute; cette r&eacute;servation n'existe pas") ;
$engine_flight_label = ($booking['r_plane'] == 'PH-AML') ? 'vol' : 'moteur' ;

// Check authorization
if ($auth == '') {
	if (! ($userId == $booking['r_pilot'] or $userId == $booking['r_who'] or $userId == $booking['r_instructor']))
		die("Logbook: you ($userId) are not authorized") ;
} else if ($auth != md5($id . $shared_secret)) die("logbook: wrong key for booking#$id: $auth ") ;

$booking['r_takeoff'] = str_replace('-', '/', $booking['r_takeoff']) ;
if (($booking['r_from'] == '') && ($booking['r_to'] == '')) {
	$booking['r_from'] = $default_airport ;
	$booking['r_to'] = $default_airport ;
}

// Do we need to save into the logbook?
if (isset($_REQUEST['action']) and $_REQUEST['action'] != '') {
	$planeId = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['plane'])) ;
	$planeModel = $booking['model'] ;
	$engineStartHour = trim($_REQUEST['engineStartHour']) ; if (!is_numeric($engineStartHour)) die("engineStartHour $engineStartHour is not numeric") ;
	$engineStartMinute = trim($_REQUEST['engineStartMinute']) ; if (!is_numeric($engineStartMinute)) die("engineStartMinute $engineStartMinute is not numeric") ;
	$engineStartMinute *= $booking['compteur_type'] ;
	$engineEndHour = trim($_REQUEST['engineEndHour']) ; if (!is_numeric($engineEndHour)) die("engineEndHour $engineEndHour is not numeric") ;
	$engineEndMinute = trim($_REQUEST['engineEndMinute']) ; if (!is_numeric($engineEndMinute)) die("engineEndMinute $engineEndMinute is not numeric") ;
	$engineEndMinute *= $booking['compteur_type'] ;
	if (isset($_REQUEST['flightStartHour']) and $_REQUEST['flightStartHour'] != '') {
		$flightStartHour = trim($_REQUEST['flightStartHour']) ; if (!is_numeric($flightStartHour)) die("flightStartHour $flightStartHour is not numeric") ;
		$flightStartMinute = trim($_REQUEST['flightStartMinute']) ; if (!is_numeric($flightStartMinute)) die("flightStartMinute $flightStartMinute is not numeric") ;
		$flightEndHour = trim($_REQUEST['flightEndHour']) ; if (!is_numeric($flightEndHour)) die("flightEndHour $flightEndHour is not numeric") ;
		$flightEndMinute = trim($_REQUEST['flightEndMinute']) ; if (!is_numeric($flightEndMinute)) die("flightEndMinute $flightEndMinute is not numeric") ;
	} else {
		$flightStartHour = 'NULL';
		$flightStartMinute = 'NULL';
		$flightEndHour = 'NULL';
		$flightEndMinute = 'NULL';
	}
	$fromAirport = mysqli_real_escape_string($mysqli_link, strtoupper(trim($_REQUEST['fromAirport']))) ;
	$toAirport = mysqli_real_escape_string($mysqli_link, strtoupper(trim($_REQUEST['toAirport']))) ;
	$flightType = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['flightType'])) ;
	$startHours = trim($_REQUEST['startHours']) ; if (!is_numeric($startHours)) die("$startHours is not numeric") ;
	$startMinutes = trim($_REQUEST['startMinutes']) ; if (!is_numeric($startMinutes)) die("$startMinutes is not numeric") ;
	$startDayTime = "$booking[r_day] " . substr("0" . $startHours, -2) . ":" . substr("0" . $startMinutes, -2) ;
	$endHours = trim($_REQUEST['endHours']) ; if (!is_numeric($endHours)) die("$endHours is not numeric") ;
	$endMinutes = trim($_REQUEST['endMinutes']) ; if (!is_numeric($endMinutes)) die("$endMinutes is not numeric") ;
	$endDayTime = "$booking[r_day] " . substr("0" . $endHours, -2) . ":" . substr("0" . $endMinutes, -2) ;
	$pilotId = trim($_REQUEST['pilot']) ; if (!is_numeric($pilotId)) die("$pilotId is not numeric") ;
	$instructorId = trim($_REQUEST['instructor']) ; if (!is_numeric($instructorId)) die("$instructorId is not numeric") ;
	if ($instructorId <= 0) $instructorId = "NULL" ;
	$dayLandings = trim($_REQUEST['dayLandings']) ; if (!is_numeric($dayLandings) or $dayLandings < 0) die("$dayLandings is not numeric or is not valid") ;
	$nightLandings = trim($_REQUEST['nightLandings']) ; if (!is_numeric($nightLandings) or $nightLandings < 0) die("$nightLandings is not numeric or is not valid") ;
	// Do some checks
	if ($endDayTime <= $startDayTime)  
		$insert_message = "Le temps d'arriv&eacute;e=$endDayTime doit &ecirc;tre plus grand que le temps de d&eacute;part= $startDayTime" ;
	elseif ($endHours < $startHours) 
		$insert_message = "Le temps moteur de fin =$endHours doit &ecirc;tre plus grand que le temps de d&eacute;part= $startHours" ;
	elseif ($endHours <= 0) 
		$insert_message = "Le temps moteur ($endHours) doit &ecirc;tre plus grand que 0" ;
	else {
		mysqli_query($mysqli_link, "insert into $table_logbook(l_plane, l_model, l_booking, l_from, l_to,
			l_start_hour, l_start_minute, l_end_hour, l_end_minute,
			l_flight_start_hour, l_flight_start_minute, l_flight_end_hour, l_flight_end_minute,
			l_start, l_end, l_flight_type,
			l_pilot, l_instructor, l_day_landing, l_night_landing,
			l_audit_who, l_audit_ip, l_audit_time) values
			('$planeId', '$planeModel', $id, '$fromAirport', '$toAirport',
			$engineStartHour, $engineStartMinute, $engineEndHour, $engineEndMinute,
			$flightStartHour, $flightStartMinute, $flightEndHour, $flightEndMinute,
			'$startDayTime', '$endDayTime', '$flightType',
			$pilotId, $instructorId, $dayLandings, $nightLandings,
			$userId, '" . getClientAddress() . "', sysdate())") or die("Impossible d'ajouter dans le logbook: " . mysqli_error($mysqli_link)) ;
		if (mysqli_affected_rows($mysqli_link) > 0) {
			$insert_message = "Carnet de route mis &agrave; jour" ;
			if ($test_mode) $insert_message .= " " . mysqli_error($mysqli_link) ;
			journalise($booking['r_pilot'], 'I', "Logbook entry added for $planeId/$planeModel, engine from $engineStartHour:$engineStartMinute to $engineEndHour:$engineEndMinute, flight $startDayTime@$fromAirport to $endDayTime@$toAirport") ;
			// Now, if this was for a customer flight, also update this
			if ($booking['r_type'] == BOOKING_CUSTOMER) {
				mysqli_query($mysqli_link, "UPDATE $table_flights SET f_date_flown='$startDayTime' WHERE f_booking=$id")
					or journalise($userId, "E", "Unable to update $table_flights upon logbook entry: " . mysqli_error($mysqli_link)) ;
			}
		} else {
			$insert_message = "Impossible de mettre &agrave; jour le carnet de route" ;
			journalise($userId, 'W', "Cannot add entry in logbook for $planeId, engine from $engineStartHour:$engineStartMinute to $engineEndHour:$engineEndMinute, flight $startDayTime@$fromAirport to $endDayTime@$toAirport") ;
		}
	}	
}

// Do we need to cancel the booking?
if (isset($_REQUEST['cancel']) and $_REQUEST['cancel'] != '') {
	$result = mysqli_query($mysqli_link, "update $table_bookings set r_cancel_date = sysdate(), r_cancel_who = $userId, r_cancel_address = '" . 
		getClientAddress() . "' where r_id = $id") ;
	if ($result && mysqli_affected_rows($mysqli_link) == 1) {
		journalise($userId, 'W', "Deleting an old booking ($id): $booking[r_plane] $booking[r_start] $booking[r_end]") ;
	}
}

// Do we need to delete an entry?
if (isset($_REQUEST['audit_time']) and $_REQUEST['audit_time'] != '') {
	$audit_time = mysqli_real_escape_string($mysqli_link, $_REQUEST['audit_time']) ;
	mysqli_query($mysqli_link, "delete from $table_logbook where l_booking=$id and l_audit_time='$audit_time'") or die("Cannot delete: " . mysql_error()) ;
	if (mysqli_affected_rows($mysqli_link) > 0) {
		$insert_message = "Carnet de route mis &agrave; jour" ;
		journalise($userId, 'I', "Logbook entry deleted for booking $id (done at $audit_time).") ;
	} else {
		$insert_message = "Impossible d'effacer la ligne dans le carnet de route" ;
		journalise($userId, 'E', "Error (" . mysqli_error($mysqli_link). ") while deleting logbook entry for booking $id (done at $audit_time).") ;
	}
}

// Refresh the data from the DB
$result = mysqli_query($mysqli_link, "select * from $table_logbook 
		where l_plane = '$booking[r_plane]' and l_start_hour is not null and l_start_hour > 0
		order by l_end desc limit 0,1")
	or die("Cannot access the plan journey log: " . mysqli_error($mysqli_link)) ;
$logbook = mysqli_fetch_array($result) ;

// Humm even when l_end is < compteur_date, prendre le engine_time de logbook et être sûr que le flight_time de rapcs_plane est flight_time è

// Select the most recent entry for the engine hour counter

//print("logbook['l_end'] $logbook[l_end] > booking['compteur_date' $booking[compteur_date]<br/>") ;
//var_dump($logbook) ;
//logbook['l_end'] 2017-01-05 14:51:00 > booking['compteur_date' 2018-04-19 20:05:14, bernard penders

if ($logbook['l_end']  && ($logbook['l_end'] > $booking['compteur_date'])) { // entry in logbook is the most recent one
//print("Doing 1<br/>") ;
	$engineStartHour = ($logbook['l_end_hour'] == '') ? 0 : $logbook['l_end_hour'] ;
	$engineStartMinute = ($logbook['l_end_minute'] == '') ? 0 : $logbook['l_end_minute'] ;
	$flightStartHour = ($logbook['l_flight_end_hour'] == '') ? 0 : $logbook['l_flight_end_hour'] ;
	$flightStartMinute = ($logbook['l_flight_end_minute'] == '') ? 0 : $logbook['l_flight_end_minute'] ;
} else { // Use data from maintenance logs
// print("Doing 2<br/>") ;
	$engineStartHour = $booking['compteur'] ;
	$engineStartMinute = 0 ;
	$flightStartHour = ($booking['compteur_vol'] and $booking['compteur_vol_valeur'] != '') ? $booking['compteur_vol_valeur'] : 0 ;
	$flightStartMinute = 0 ;
}

// Now compute the expected end of engine run based on expected flight duration
if ($booking['r_duration'] == 0.0) $booking['r_duration'] = 0.5 ;
$engineEnd = $engineStartHour + $engineStartMinute / 60 + $booking['r_duration'] ;
$engineEndHour = floor($engineEnd) ;
$engineEndMinute = round(60.0 * ($engineEnd - $engineEndHour)) ;
$flightEnd = $flightStartHour + $flightStartMinute / 60 + $booking['r_duration'] ;
$flightEndHour = floor($flightEnd) ;
$flightEndMinute = round(60.0 * ($flightEnd - $flightEndHour)) ;

$durationHour = floor($booking['r_duration']) ;
$durationMinute = round(60.0 * ($booking['r_duration'] - $durationHour)) ;

// If latested logbook entry is from the same booking, then reuse airport & landing time to initialize values
if ($logbook['l_booking'] == $id) {
	$booking['r_from'] = $logbook['l_to'] ;
	$booking['r_takeoff'] = $logbook['l_end'] ;
}

?><!DOCTYPE html>
<html lang="fr">
<head>
<link rel="stylesheet" type="text/css" href="logbook.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<meta charset="utf-8">
<!--meta name="viewport" content="width=320"-->
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- http://www.alsacreations.com/article/lire/1490-comprendre-le-viewport-dans-le-web-mobile.html -->
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<title>Carnet de route pour <?=$booking['r_plane']?></title>
<!-- http://www.w3schools.com/bootstrap/ -->
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
<!-- cacheable data -->
<script src="logbook.js"></script>
<script src="planes.js"></script>
<script src="instructors.js"></script>
<script src="pilots.js"></script>
<script>
var
// $booking['compteur_type'] = <?=$booking['compteur_type']?> $logbook['l_end_minute'] = <?=$logbook['l_end_minute']?>, $engineStartMinute = <?=$engineStartMinute?> 
// $flightStartMinute = <?=$flightStartMinute?>
// $booking['compteur_vol'] = <?=$booking['compteur_vol']?>, $logbook['l_flight_end_hour'] = <?=$logbook['l_flight_end_hour']?>, $booking['compteur_vol_valeur'] = <?=$booking['compteur_vol_valeur']?>

	planeId = '<?=$booking['r_plane']?>',
	pilotId = <?=$booking['r_pilot']?>,
	instructorId = <?= ($booking['r_instructor'])? $booking['r_instructor'] : -1 ?>,
	engineCounterType = <?=$booking['compteur_type']?>,
	engineStartHour = <?=$engineStartHour?>, engineStartMinute = <?=$engineStartMinute?>,
	engineEndHour = <?=$engineEndHour?>, engineEndMinute = <?=$engineEndMinute?>,
	flightStartHour = <?=$flightStartHour?>, flightStartMinute = <?=$flightStartMinute?>,
	flightEndHour = <?=$flightEndHour?>, flightEndMinute = <?=$flightEndMinute?>,
	durationHour = Number(<?=$durationHour?>), durationMinute = Number(<?=$durationMinute?>),
	takeoffDate = new Date('<?=str_replace('-', '/', $booking['r_takeoff'])?>') ,
	landingDate ;
</script>
<!-- Matomo -->
<script type="text/javascript">
  var _paq = window._paq = window._paq || [];
  /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
  _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
  _paq.push(["setCookieDomain", "*.spa-aviation.be"]);
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="//analytics.vyncke.org/";
    _paq.push(['setTrackerUrl', u+'matomo.php']);
    _paq.push(['setSiteId', '5']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Code -->
</head>
<body onload="initLogbook();">
<div class="container-fluid">
<div class="row text-center">
<?php
if (isset($_REQUEST['cancel']) and $_REQUEST['cancel'] != '') {
?>
	<div class="col-sm-12">Cette réservation est maintenant annulée</div><!-- col -->
	</div><!-- row -->
<?php
	exit ;
}
?>
	<div class="col-sm-12 text-center"><h2>Carnet de route pour <?=$booking['r_plane']?></h2>
		Vol du <?=$booking['r_start']?>.<div class="hidden-xs"><mark><b>Cette page <!-- est optionelle (mais aide &agrave; la maintenance de l'avion et
		aux pr&eacute;visions de vols des autres pilotes), elle --> ne remplace pas l'entr&eacute;e &eacute;crite qui doit
		&ecirc;tre dans le carnet de route de l'avion.</b></mark><br/> Veuillez commencer par entrer les heures moteurs, puis l'horaire
		de votre vol et en option le nom du pilote, le nombre d'atterrissages, ...</div><!-- hidden -->
	<br/>
	</div><!-- col -->
</div> <!-- row -->

<?php
if (isset($insert_message) and $insert_message != '') {
?>
<div class="row">
	<div class="col-sm-4 hidden-xs"></div>
	<div class="col-sm-12 text-center">
	<i><mark><?=$insert_message?></mark></i>
    </div><!-- col -->
	<div class="col-sm-4 hidden-xs"></div>
</div> <!-- row -->
<?php
}
?>

<?php
// Now, display any previous entries related to this booking
$result = mysqli_query($mysqli_link, "select l_start, l_end, l_plane, l_from, l_to, l_flight_type, l_audit_time, p.last_name as pilotName, i.last_name as instructorName
		from $table_logbook l join $table_person p on l.l_pilot = p.jom_id left join $table_person i on l.l_instructor = i.jom_id
		where l_booking = $id order by l_start")
	or die("Impossible de lire les entrees pour reservation $id: " . mysqli_error($mysqli_link)) ;
$this_segment_id = mysqli_num_rows($result) + 1 ;
if ($this_segment_id > 1) {
	print('<div class="row">
		<div class="col-xs-12 text-center table-responsive">
		<br/>Ligne(s) du carnet de route relative(s) &agrave; cette r&eacute;servation (heure universelle)
		<table class="previousLogTable table table-striped table-condensed">') ;
	while ($row = mysqli_fetch_array($result)) {
		// As the OVH MySQL server does not have the timezone support, needs to be done in PHP
		$start_UTC = gmdate('H:i', strtotime("$row[l_start] $default_timezone")) ;
		$end_UTC = gmdate('H:i', strtotime("$row[l_end] $default_timezone")) ;
		if ($row['instructorName'] == '')
			$crew = $row['pilotName'] ;
		else
			$crew = $row['pilotName'] . '/' . $row['instructorName'] ;
		print("<tr>
			<td class=\"previousLogEntry\">$row[l_plane]</td>
			<td class=\"previousLogEntry\">$crew</td>
			<td class=\"previousLogEntry\">$row[l_from]</td>
			<td class=\"previousLogEntry\">$start_UTC</td>
			<td class=\"previousLogEntry\">$row[l_to]</td>
			<td class=\"previousLogEntry\">$end_UTC</td>
			<td class=\"previousLogEntry\">$row[l_flight_type]</td>
			<td class=\"previousLogEntry\">&nbsp;<button type=\"button\" class=\"btn btn-danger btn-xs\" onclick=\"redirectDelete($id, '$auth', '$row[l_audit_time]');\">EFFACER</button>&nbsp;</td></tr>\n") ;
	}
	print('</table>
	</div> <!--- col -->
	</div> <!-- row -->
	<br/>
	<div class="row">
		<div class="col-xs-12">
			Vous pouvez ajouter des lignes en plus en remplissant les tables ci-dessous et en cliquant sur le bouton vert "Enregistrer" tout en bas (qui appara&icirc;tra d&egrave;s que les heures moteur seront remplies).
		</div> <!-- col -->
		</div> <!-- row -->
') ;
} else { // Empty road book
	$this_segment_id = 1 ;
	print('<div class="row">
		<div class="col-xs-12">
			Le carnet de route est toujours vide pour cette r&eacute;servation. Veuillez ajouter au moins une ligne remplissant les tables ci-dessous et en cliquant sur le bouton vert "Enregistrer" tout en bas (qui appara&icirc;tra d&egrave;s que les heures moteur seront remplies).
		</div> <!-- col -->
		</div> <!-- row -->
') ;
}
?>

<form action="<?=$_SERVER['PHP_SELF']?>" method="POST">

<div class="row">
<div class="col-xs-12 col-md-6">

<table class="logbookTable">
	<tr><td class="logbookSeparator" colspan="2">Temps/index <?=$engine_flight_label?></td><tr>
	<tr><td class="logbookLabel">D&eacute;but:</td>
	<td class="logbookValue">
		<input type="number" size="7" name="engineStartHour" max="<?=$engineStartHour+50?>" Xvalue="<?=$engineStartHour?>" onchange="engineTimeChanged(false);"> H
<?php
if ($booking['compteur_type'] == 1) 
		print("<input type=\"number\" size=\"5\" maxlength=\"2\" min=\"0\" max=\"59\" name=\"engineStartMinute\" Xvalue=\"$engineStartMinute\" onchange=\"engineTimeChanged(false);\"> min.\n") ;
elseif ($booking['compteur_type'] == 6)
		print("<input type=\"number\" size=\"4\" maxlength=\"1\" min=\"0\" max=\"9\" name=\"engineStartMinute\" Xvalue=\"" . round($engineStartMinute/6) . "\" onchange=\"engineTimeChanged(false);\"> dixi&egrave;mes\n") ;
else
		print("Type de compteur moteur inconnu...") ;
?>
		</td></tr>
	<tr><td class="logbookLabel">Fin:</td>
	<td class="logbookValue">
		<input type="number" size="7" name="engineEndHour" max="<?=$engineStartHour+50?>" Xvalue="<?=$engineEndHour?>" onchange="engineTimeChanged(false);"> H
<?php
if ($booking['compteur_type'] == 1) 
		print("<input type=\"number\" size=\"5\" maxlength=\"2\" min=\"0\" max=\"59\" name=\"engineEndMinute\" value=\"$engineEndMinute\" onchange=\"engineTimeChanged(false);\"> min.\n") ;
elseif ($booking['compteur_type'] == 6)
		print("<input type=\"number\" size=\"4\" maxlength=\"1\" min=\"0\" max=\"9\" name=\"engineEndMinute\" Xvalue=\"" . round($engineEndMinute/6) . "\" onchange=\"engineTimeChanged(false);\"> dixi&egrave;mes\n") ;
else
		print("Type de compteur moteur inconnu...") ;
?>
		</td></tr>
	<tr><td class="logbookLabel">Dur&eacute;e:</td><td class="logbookValue"><input type="text" size="5" maxlength="5" name="engineDurationHour" value="<?=$durationHour?>" disabled> H
		<input type="text" size="3" maxlength="2" name="engineDurationMinute" value="<?=$durationMinute?>" disabled> min.</td><tr>
</table>

<?php 
if ($booking['compteur_vol'] != 0) {
?>
<table class="logbookTable">
	<tr><td class="logbookSeparator" colspan="2">Temps/index vol</td><tr>
	<tr><td class="logbookLabel">D&eacute;but:</td><td class="logbookValue"><input type="number" size="5" name="flightStartHour" XXXmin="<?=$flightStartHour-10?>" XXXmax="<?=$flightStartHour+30?>" value="<?=$flightStartHour?>" onchange="flightTimeChanged(false);"> H
		<input type="number" size="4" maxlength="2" min="0" max="59" name="flightStartMinute" value="<?=$flightStartMinute?>" onchange="flightTimeChanged(false);"> min
	</td></tr>
	<tr><td class="logbookLabel">Fin:</td><td class="logbookValue">
		<input type="number" size="6" name="flightEndHour" max="<?=$flightStartHour+50?>" value="<?=$flightEndHour?>" onchange="flightTimeChanged(false);"> H
		<input type="number" size="4" maxlength="2" min="0" max="59" name="flightEndMinute" value="<?=$flightEndMinute?>" onchange="flightTimeChanged(false);"> min
	</td></tr>
	<tr><td class="logbookLabel">Dur&eacute;e:</td><td class="logbookValue"><input type="text" size="5" maxlength="5" name="flightDurationHour" value="<?=$durationHour?>" disabled> H
		<input type="text" size="3" maxlength="2" name="flightDurationMinute" value="<?=$durationMinute?>" disabled> min.</td><tr>
</table>
<?php
} // End of if ($booking['compteur_vol'] != 0)
?> 
</div> <!-- col -->

<div class="col-xs-12 col-md-6">
<table class="logbookTable" id="flightSchedule" style="opacity: 0.5">
	<tr><td class="logbookSeparator" colspan="2">Horaire du vol</td><tr>
	<tr><td class="logbookLabel">D&eacute;but (heure locale):</td><td class="logbookValue">
		<input type="number" min="0" max="23" name="startHours" size="4" maxlength="2" onchange="takeoffTimeChanged();" disabled> :
		<input type="number" min="0" max="59" name="startMinutes" size="4" maxlength="2" onchange="takeoffTimeChanged();" disabled>
	</td><tr>
	<tr><td class="logbookLabel">D&eacute;but (heure universelle):</td><td class="logbookValue">
		<input type="text" name="startHoursUTC" size="3" maxlength="2" disabled> : <input type="text" name="startMinutesUTC" size="2" maxlength="2" disabled>
	</td><tr>
	<tr><td class="logbookLabel">Fin (heure locale):</td><td class="logbookValue">
		<input type="number" min="0" max="23" name="endHours" size="4" maxlength="2" onchange="landingTimeChanged();" disabled> :
		<input type="number" min="0" max="59" name="endMinutes" size="4" maxlength="2" onchange="landingTimeChanged();" disabled>
	</td><tr>
	<tr><td class="logbookLabel">Fin (heure universelle):</td><td class="logbookValue">
		<input type="text" name="endHoursUTC" size="2" maxlength="2" disabled>:<input type="text" name="endMinutesUTC" size="3" maxlength="2" disabled>
	</td><tr>
</table>
</div> <!-- col -->


<div class="col-xs-12 col-md-2">
<table class="logbookTable">
	<tr><td class="logbookSeparator" colspan="2">Type de vol (local, nav, ...)</td><tr>
	<tr><td class="logbookLabel">Type de vol:</td><td class="logbookValue"><input type="text" size="16" maxlength="32" name="flightType" value=""></td><tr>
</table>
</div> <!-- col-->

</div> <!-- row -->

<!-- Test for boostrap alerts -->
<div class="row">
<div id="alertPlaceHolder" class="text-center"></div>
</div><!-- row -->


<div class="row">
<div class="col-xs-12 text-center">
<h3>Donn&eacute;es facultatives</h3>
</div> <!-- col -->
</div> <!-- row -->

<div class="row">

<div class="col-xs-12 col-sm-6 col-md-3">
<table class="logbookTable">
	<tr><td class="logbookSeparator" colspan="2">A&eacute;roport(s)</td><tr>
	<tr><td class="logbookLabel">D&eacute;part:</td><td class="logbookValue"><input type="text" size="6" maxlength="4" name="fromAirport" value="<?=$booking['r_from']?>"></td><tr>
	<tr><td class="logbookLabel">Arriv&eacute;e:</td><td class="logbookValue"><input type="text" size="6" maxlength="4" name="toAirport" value="<?=$booking['r_to']?>"></td><tr>
</table>
</div> <!-- col-->


<div class="col-xs-12 col-sm-6 col-md-3">
<table class="logbookTable">
	<tr><td class="logbookSeparator" colspan="2">Atterrissage(s)</td><tr>
	<tr><td class="logbookLabel">Jour:</td><td class="logbookValue"><input type="number" size="4" maxlength="2" min="0" max="99" name="dayLandings" value="1"></td><tr>
	<tr><td class="logbookLabel">Nuit:</td><td class="logbookValue"><input type="number" size="4" maxlength="2" min="0" max="99" name="nightLandings" value="0"></td><tr>
</table>
</div> <!-- col-->

<div class="col-xs-12 col-sm-8 col-md-4">
<table class="logbookTable">
	<tr><td class="logbookSeparator" colspan="2">&Eacute;quipage</td><tr>
	<tr><td class="logbookLabel">Pilote:</td>
	<td class="logbookValue">
		<select name="pilot">
		</select>
	</td></tr>
	<tr><td class="logbookLabel">Instructeur:</td>
	<td class="logbookValue">
		<select name="instructor">
		</select>
	</td></tr>
</table>
</div> <!-- col-->

<div class="col-xs-12 col-sm-4 col-md-2">
<table class="logbookTable">
<tr><td class="logbookSeparator" colspan="2">Avion</td><tr>
<tr><td class="logbookLabel">Avion:</td>
<td class="logbookValue">
	<select name="plane">
	</select>
</td></tr>
</table>
</div> <!-- col-->

</div> <!-- row -->

<!-- the CANCEL, SAVE and BACK buttons -->
<div class="row">
	<div class="col-xs-6 col-sm-4 text-center">
	<input type="hidden" name="id" value="<?=$id?>">
	<input type="hidden" name="auth" value="<?=$auth?>">
<?php
	if ($this_segment_id == 1) {
?>
	<input type="submit" id="logbookCancelButton" class="col-xs-12 col-sm-6 btn btn-danger center collapse in"
		value="Annuler la r&eacute;servation" name="cancel" style="margin-left: auto; margin-right: auto;">
<?php
}
?>
	<input type="submit" id="logbookButton" class="col-xs-12 col-sm-6 btn btn-success center collapse"
		value="Enregistrer le segment #<?=$this_segment_id?>" name="action" disabled style="margin-left: auto; margin-right: auto;">
	</div> <!-- col-->
</form>

<?php
if ($auth != '') {
?>
	<div class="col-xs-6 col-sm-4 text-center">
		<form action="booking.php">
		<input type="hidden" name="id" value="<?=$id?>">
		<input type="hidden" name="auth" value="<?=$auth?>">
		<input type="submit" class="col-xs-12 col-sm-6 btn btn-success center" value="Retour &agrave; la r&eacute;servation" style="margin-left: auto; margin-right: auto;">
		</form>
	</div> <!-- col-->
<?php
}
?>
</div> <!-- row -->

<div class="row hidden-xs">
	<div id="logDiv" class="col-sm-12"></div>
</div> <!-- row -->

</div> <!-- container-->

</body>
</html>
