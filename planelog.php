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

ob_start("ob_gzhandler");
require_once "dbi.php" ;
require_once 'facebook.php' ;

MustBeLoggedIn() ;

$plane = strtoupper(mysqli_real_escape_string($mysqli_link, $_REQUEST['plane'])) ;
if ($plane == '') die("Missing parameter plane") ;
$since = mysqli_real_escape_string($mysqli_link, $_REQUEST['since']) ;
if ($since == '')
	$since = date('Y-m-01') ;

$sinceDate = new DateTime($since) ;
$monthAfter = new DateTime($since) ;
$monthAfterForTitle = new DateTime($since) ;
$monthBefore = new DateTime($since) ;
$monthInterval = new DateInterval('P1M') ; // One month
$monthBefore = $monthBefore->sub($monthInterval) ;
$monthBeforeString = $monthBefore->format('Y-m-d') ;
$monthAfter = $monthAfter->add($monthInterval) ; // Then request is from 01-01-2023 0h00 to 01-02-2023 0h00
//$monthAfter = $monthAfter->sub(new DateInterval('P1D')) ; 
$monthAfterString = $monthAfter->format('Y-m-d') ;
$monthAfterForTitle = $monthAfterForTitle->add($monthInterval) ;
$monthAfterForTitle = $monthAfterForTitle->sub(new DateInterval('P1D')) ;
$monthAfterForTitleString = $monthAfterForTitle->format('Y-m-d') ; // Then Title is 31-01-2023 and not 01-02-2023
$mounthName=$sinceDate->format('F') ;

?><html>
<head>
<link rel="stylesheet" type="text/css" href="log.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<title>Carnet de route <?=$plane?></title>
<script src="members.js"></script>
<script src="shareCodes.js"></script>
<script>
const
	// preset Javascript constant fill with the right data from db.php PHP variables
	userFullName = '<?=$userFullName?>' ;
	userName = '<?=$userName?>' ;
	userId = <?=$userId?> ;
	userIsPilot = <?=($userIsPilot)? 'true' : 'false'?> ;
	userIsAdmin = <?=($userIsAdmin)? 'true' : 'false'?> ;
	userIsInstructor = <?=($userIsInstructor)? 'true' : 'false'?> ;
	userIsMechanic = <?=($userIsMechanic)? 'true' : 'false'?> ;

function planeChanged(elem) {
	window.location.href = '<?=$_SERVER['PHP_SELF']?>?plane=' + elem.value + '&since=<?=$_REQUEST['since']?>';
}

function findMember(a, m) {
	for (let i = 0 ; i < a.length ; i++)
		if (a[i].id == m)
			return a[i].name ;
	return null ;
}

function init() {
	var planeSelect = document.getElementById('planeSelect') ;
	if (planeSelect) planeSelect.value = '<?=$plane?>' ;
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
}
</script>
<!-- Matomo -->
<script type="text/javascript">
  var _paq = window._paq = window._paq || [];
  _paq.push(['setUserId', '<?=$userName?>']);
  _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
  _paq.push(["setDomains", ["*.spa-aviation.be","*.ebsp.be","*.m.ebsp.be","*.m.spa-aviation.be","*.resa.spa-aviation.be"]]);
  _paq.push(['enableHeartBeatTimer']);
  _paq.push(['setCustomVariable', 1, "userID", <?=$userId?>, "visit"]);
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
<body onload="init();">
<center><h2>Carnet de route de <?=$plane?> du <?=$since?> au <?=$monthAfterForTitleString?></h2></center>
<?php
print("Carnet de route de: <select id=\"planeSelect\" onchange=\"planeChanged(this);\">" ) ;
$result = mysqli_query($mysqli_link, "select * from $table_planes
	where actif != 0 and ressource = 0
	order by id") ;
$plane_details = array() ;
while ($row = mysqli_fetch_array($result)) {
	$row['id'] = strtoupper($row['id']) ;
	print("<option value=\"$row[id]\">$row[id]</option>\n") ;
	if ($row['id'] == $plane)
		$plane_details = $row ;
}
print("</select> ") ;
print("Mois: <a href=$_SERVER[PHP_SELF]?plane=$plane&since=$monthBeforeString>&lt;</a><b>&nbsp; $mounthName &nbsp;</b><a href=$_SERVER[PHP_SELF]?plane=$plane&since=$monthAfterString>&gt;</a></br>\n") ;
//print("Mois: <a href=$_SERVER[PHP_SELF]?plane=$plane&since=$monthBeforeString>&lt;</a> $since <a href=$_SERVER[PHP_SELF]?plane=$plane&since=$monthAfterString>&gt;</a>\n") ;
?>
<br/>
<table class="logTable">
<thead>
<tr>
<th class="logHeader">Date</th>
<th class="logHeader">Pilot(s)</th>
<th class="logHeader" colspan="2">Airports</th>
<th class="logHeader" colspan="2">Time (UTC)</th>
<th class="logHeader">Engine time</th>
<?php 
if ($plane_details['compteur_vol'] != 0)
	print("<th class=\"logHeader\">Flight Time</th>\n") ;
?>
<th class="logHeader">Pax/crew</th>
<th class="logHeader">Type of</th>
<th class="logHeader" colspan="2">Engine index</th>
<?php 
if ($plane_details['compteur_vol'] != 0)
	print("<th class=\"logHeader\" colspan=\"2\">Flight index</th>\n") ;
?>
<th class="logHeader">Remark</th>
</tr>
<tr>
<th class="logLastHeader">(dd/mm/yy)</th>
<th class="logLastHeader"></th>
<th class="logLastHeader">Origin</th>
<th class="logLastHeader">Destination</th>
<th class="logLastHeader">Takeoff</th>
<th class="logLastHeader">Landing</th>
<th class="logLastHeader">minutes</th>
<?php 
if ($plane_details['compteur_vol'] != 0)
	print("<th class=\"logLastHeader\">minutes</th>\n") ;
?>
<th class="logLastHeader">Count</th>
<th class="logLastHeader">flight</th>
<th class="logLastHeader">Begin</th>
<th class="logLastHeader">End</th>
<?php 
if ($plane_details['compteur_vol'] != 0)
	print("<th class=\"logLastHeader\">Begin</th>\n
		<th class=\"logLastHeader\">End</th>\n") ;
?>
<th class="logLastHeader">(CP, ...)</th>
</tr>
</thead>
<tbody>
<?php
//print("</br>since=$since ; monthAfterString=$monthAfterString</br>");
$sql = "select date_format(l_start, '%d/%m/%y') as date, l_start, l_end, l_end_hour, l_end_minute, l_start_hour, l_start_minute,
	timediff(l_end, l_start) as duration,
	l_flight_end_hour, l_flight_end_minute, l_flight_start_hour, l_flight_start_minute,
	upper(l_from) as l_from, upper(l_to) as l_to, l_flight_type, p.name as pilot_name, i.name as instructor_name, l_remark, l_pax_count, l_crew_count, 
	l_share_type, l_share_member,
	l_booking, l_pilot, l_instructor
	from $table_logbook l 
	join $table_users p on l_pilot=p.id
	left join $table_users i on l_instructor = i.id
	where l_plane = '$plane'
		and '$since' <= l_start and l_start < '$monthAfterString'
	order by l_start asc" ;
// Before, all entries add a l_booking for the flight club planes...
//	where l_plane = '$plane' and l_booking is not null
$result = mysqli_query($mysqli_link, $sql) or die("Erreur système à propos de l'accès au carnet de route: " . mysqli_error($mysqli_link)) ;
$duration_total_hour = 0 ;
$duration_total_minute = 0 ;
$pic_total_hour = 0 ;
$pic_total_minute =  0;
$dual_total_hour = 0 ;
$dual_total_minute =  0;
$fi_total_hour = 0 ;
$fi_total_minute =  0;
$line_count = 0 ;
$previous_end_hour = false ;
$previous_end_minute = false ;
$previous_end_utc = false ;
$previous_end_lt = false ;
$engine_total_minute = 0 ;
$flight_total_minute = 0 ;
$previous_airport = false ;
while ($row = mysqli_fetch_array($result)) {
	// Emit a red line for missing entries...
	if ($previous_end_hour) {
		$gap = 60 * ($row['l_start_hour'] - $previous_end_hour) + $row['l_start_minute'] - $previous_end_minute ;
		// A little tricky as the data in $table_logbook is in UTC and in $table_bookings in local time :-O
		// Moreover, the MySQL server at OVH does not support timezone... I.e., everything must be done in PHP
		// I.e., the logging data must be converted into local time
		$previous_end_lt = new DateTime($previous_end_utc, new DateTimeZone('UTC')) ;
		$previous_end_lt->setTimezone(new DateTimeZone($default_timezone)) ;
		$this_start_lt = new DateTime($row['l_start'], new DateTimeZone('UTC')) ;
		$this_start_lt->setTimezone(new DateTimeZone($default_timezone)) ;
		if ($gap > 0) {
			$missingPilots = array() ;
			if (! $row['l_booking']) $row['l_booking'] = -1 ; // As logbook can now contain entries without a booking ... say bye bye to integrity
			$result2 = mysqli_query($mysqli_link, "SELECT last_name, r_start, r_stop, r_type, r_id
				FROM $table_bookings JOIN $table_person ON r_pilot = jom_id
				WHERE r_plane = '$plane' AND r_cancel_date IS NULL
					AND r_id != $row[l_booking]
					AND '" . $previous_end_lt->format('Y-m-d H:i') . "' <= r_start
					AND r_stop < '" . $this_start_lt->format('Y-m-d H:i') . "' 
					AND r_start < '$monthAfterString'
				ORDER by r_start ASC") or die("Erreur système à propos de l'accès aux réservations manquantes: " . mysqli_error($mysqli_link));
			while ($row2 = mysqli_fetch_array($result2)) {
				if ($row2['r_type'] == BOOKING_MAINTENANCE)
					$missingPilots[] = 'Maintenance (' . substr($row2['r_start'], 0, 10) . ') #' . $row2['r_id'] ;
				else
					$missingPilots[] = db2web($row2['last_name']) . ' (' . substr($row2['r_start'], 0, 10) . ') #' . $row2['r_id'] ;
			}
			print("<tr><td class=\"logCell\" colspan=12 style=\"color: red;\">Missing entries for $gap minutes..." . implode('<br/>', $missingPilots) . "</td></tr>\n") ;
		} else if ($gap < 0)
			print("<tr><td class=\"logCell\" colspan=12 style=\"color: red;\">Overlapping / duplicate entries for $gap minutes...</td></tr>\n") ;
		if ($previous_end_lt > $this_start_lt)
			print("<tr><td class=\"logCell\" colspan=12 style=\"color: red;\">Overlapping entries (previous end time after next start)...</td></tr>\n") ;
	}
	// Emit a red line if previous arrival apt and this departure airport do not match
	if ($previous_airport and $previous_airport != $row['l_from'])
		print("<tr><td class=\"logCell\" colspan=12 style=\"color: red;\">Departure airport below($row[l_from]) does not match previous arrival airport ($previous_airport)... taxes are probably invalid.</td></tr>\n") ;
	// Emit a red line if pilot and instructor are the same
	if ($row['l_pilot'] == $row['l_instructor'])
		print("<tr><td class=\"logCell\" colspan=12 style=\"color: red;\">The pilot is the instructor on the line below...</td></tr>\n") ;
	$previous_airport = $row['l_to'] ;
	$previous_end_hour = $row['l_end_hour'] ;
	$previous_end_minute = $row['l_end_minute'] ;
	$previous_end_utc = $row['l_end'] ;
	$previous_end_lt = new DateTime($previous_end_utc, new DateTimeZone('UTC')) ;
	$previous_end_lt->setTimezone(new DateTimeZone($default_timezone)) ;
	$line_count ++ ;
	// Don't trust the row but the diff of engine index
	$duration = 60 * ($row['l_end_hour'] - $row['l_start_hour']) + $row['l_end_minute'] - $row['l_start_minute'] ;
	$engine_total_minute += $duration ;
	// Handling character sets...
	$pilot_name = db2web($row['pilot_name']) ;
	$instructor_name = db2web($row['instructor_name']) ;
	// Time in $table_logbook is already in UTC
	$l_start = substr($row['l_start'], 11, 5) ;
	$l_end = substr($row['l_end'], 11, 5) ;
	$instructor = ($instructor_name != '') ? " /<br/>$instructor_name" : '' ;
	$bookingLink = ($userIsAdmin) ? " <a href=\"https://www.spa-aviation.be/resa/IntroCarnetVol.php?id=$row[l_booking]\" title=\"Go to booking $row[l_booking]\" target=\"_blank\">&boxbox;</a>" : '' ;
	if ($row['l_start_minute'] < 10)
			$row['l_start_minute'] = "0$row[l_start_minute]" ;
	if ($row['l_end_minute'] < 10)
			$row['l_end_minute'] = "0$row[l_end_minute]" ;
	if ($row['l_flight_start_minute'] < 10)
			$row['l_flight_start_minute'] = "0$row[l_flight_start_minute]" ;
	if ($row['l_flight_end_minute'] < 10)
			$row['l_flight_end_minute'] = "0$row[l_flight_end_minute]" ;
	if ($row['l_share_type'] != '') $row['l_remark'] = '<b>' . $row['l_share_type'] . '<span class="shareCodeClass">' . $row['l_share_member'] . '</span></b> ' . $row['l_remark'] ;
	print("<tr>
		<td class=\"logCell\">$row[date]</td>
		<td class=\"logCell\">$pilot_name$instructor$bookingLink</td>
		<td class=\"logCell\">$row[l_from]</td>
		<td class=\"logCell\">$row[l_to]</td>
		<td class=\"logCell\">$l_start</td>
		<td class=\"logCell\">$l_end</td>
		<td class=\"logCell\">$duration</td>\n") ;
		if ($plane_details['compteur_vol'] != 0) {
			$flight_duration = 60 * ($row['l_flight_end_hour'] - $row['l_flight_start_hour']) + $row['l_flight_end_minute'] - $row['l_flight_start_minute'] ;
			$flight_total_minute += $flight_duration ;
			print("<td class=\"logCell\">$flight_duration</td>\n") ;
		}
		print("<td class=\"logCell\">$row[l_pax_count]/$row[l_crew_count]</td>
		<td class=\"logCell\">$row[l_flight_type]</td>
		<td class=\"logCell\">$row[l_start_hour]:$row[l_start_minute]</td>
		<td class=\"logCell\">$row[l_end_hour]:$row[l_end_minute]</td>\n") ;
	if ($plane_details['compteur_vol'] != 0) {
		print("<td class=\"logCell\">$row[l_flight_start_hour]:$row[l_flight_start_minute]</td>\n") ;
		print("<td class=\"logCell\">$row[l_flight_end_hour]:$row[l_flight_end_minute]</td>\n") ;
	}
	print("<td class=\"logCell\">$row[l_remark]</td>") ;
	print("</tr>\n") ;
}

// Missing logbook entries until now
if (! $previous_end_lt) {
	$previous_end_lt = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
}
$missingPilots = array() ;
// A little tricky as the data in $table_logbook is in UTC and in $table_bookings in local time :-O
// Moreover, the MySQL server at OVH does not support timezone... I.e., everything must be done in PHP
// I.e., the logging data must be converted into local time
$result2 = mysqli_query($mysqli_link, "SELECT last_name, r_start, r_stop, r_type, r_id
	FROM $table_bookings JOIN $table_person ON r_pilot = jom_id
	WHERE r_plane = '$plane' AND r_cancel_date IS NULL
		AND '" . $previous_end_lt->format('Y-m-d H:i') . "' <= r_start
		AND r_stop < SYSDATE()
		AND r_start < '$monthAfterString'
	ORDER by r_start ASC") or die("Erreur système à propos de l'accès aux réservations manquantes après: " . mysqli_error($mysqli_link));
while ($row2 = mysqli_fetch_array($result2)) {
	if ($row2['r_type'] == BOOKING_MAINTENANCE)
		$missingPilots[] = 'Maintenance (' . substr($row2['r_start'], 0, 10) . ') #' . $row2['r_id'] ;
	else
		$missingPilots[] = db2web($row2['last_name']) . ' (' . substr($row2['r_start'], 0, 10) . ') #' . $row2['r_id'] ;
}
if (count($missingPilots) > 0)
	print("<tr><td class=\"logCell\" colspan=12 style=\"color: red;\">Missing entries..." . implode('<br/>', $missingPilots) . "</td></tr>\n") ;

$engine_total_hour = floor($engine_total_minute / 60) ;
$engine_total_minute = $engine_total_minute % 60 ;
if ($engine_total_minute < 10)
	$engine_total_minute = "0$engine_total_minute" ;
if ($plane_details['compteur_vol'] != 0) {
	$flight_total_hour = floor($flight_total_minute / 60) ;
	$flight_total_minute = $flight_total_minute % 60 ;
	if ($flight_total_minute < 10)
		$flight_total_minute = "0$flight_total_minute" ;
}
?>
<tr><td colspan="6" class="logTotal">Logged total</td>
<td class="logTotal"><?="$engine_total_hour:$engine_total_minute"?></td>
<?php
if ($plane_details['compteur_vol'] != 0) {
	print("<td class=\"logTotal\">$flight_total_hour:$flight_total_minute</td>") ;
	print('<td colspan="7" class="logTotal"></td>') ;
} else
	print('<td colspan="5" class="logTotal"></td>') ;
?>
</tbody>
</table>
<br>
Sur base des donn&eacute;es entr&eacute;es apr&egrave;s les vols dans le
carnet de route des avions. Heure affich&eacute;e en heure universelle.
<?php
$version_php = date ("Y-m-d H:i:s.", filemtime('planelog.php')) ;
$version_css = date ("Y-m-d H:i:s.", filemtime('log.css')) ;
?>
<hr>
<div class="copyright">R&eacute;alisation: Eric Vyncke, janvier 2015 - septembre 2022, pour RAPCS, Royal A&eacute;ro Para Club de Spa<br>
Versions: PHP=<?=$version_php?>, CSS=<?=$version_css?></div>
</body>
</html>

