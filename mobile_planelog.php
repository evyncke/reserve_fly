<?php
/*
   Copyright 2014-2025 Eric Vyncke

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

$header_postamble = '<script src="data/shareCodes.js"></script>' ;
$body_attributes=' onload="initPlaneLog();init();" ' ;
$need_swiped_events = true ; // Allow swipe events on this page
require_once 'mobile_header5.php' ;

if (isset($_REQUEST['plane']) and $_REQUEST['plane'] != '')
	$plane = strtoupper(mysqli_real_escape_string($mysqli_link, $_REQUEST['plane'])) ;
else
	journalise($userId, "F", "missing plane parameter") ;
if (isset($_REQUEST['since']) and $_REQUEST['since'] != '')
	$since = mysqli_real_escape_string($mysqli_link, $_REQUEST['since']) ;
else
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
// Display today in the local language in human language
$fmt = datefmt_create(
    'fr_BE',
    IntlDateFormatter::FULL,
    IntlDateFormatter::FULL,
    'Europe/Brussels',
    IntlDateFormatter::GREGORIAN,
    'MMMM yyyy' // See https://unicode-org.github.io/icu/userguide/format_parse/datetime/ !
) ;
$monthName = datefmt_format($fmt, $sinceDate) ;

$validAirports = array() ; // A cache of valid airports to avoid multiple SQL queries
$students = array() ; // List of all students' jom_id
$result = mysqli_query($mysqli_link, "SELECT DISTINCT jom_id 
		FROM $table_person 
		JOIN $table_user_usergroup_map on jom_id=user_id
		WHERE group_id = $joomla_student_group")
	or journalise($userId, "F", "Cannot read students: " . mysqli_error($mysli_link)) ;
while ($row = mysqli_fetch_array($result))
	$students[$row['jom_id']] = true ;

function isValidAirport($apt) {
	global $validAirports, $mysqli_link, $userId, $table_airports ;

	if (isset($validAirports[$apt])) return true ;
	$result = mysqli_query($mysqli_link, "SELECT * FROM $table_airports WHERE a_code = '$apt'")
		or journalise($userId, "F", "Cannot verify airport: " . mysqli_error($mysqli_link)) ;
	if (mysqli_num_rows($result) == 0) return false ;
	$validAirports[$apt] = true ;
	return true ;
}

?><script>

function planeChanged(elem) {
	window.location.href = '<?=$_SERVER['PHP_SELF']?>?plane=' + elem.value + 
		'&since=<?=(isset($_REQUEST['since'])) ? $_REQUEST['since'] : ''?>';
}

function findMember(a, m) {
        for (let i = 0 ; i < a.length ; i++)
                if (a[i].id == m)
                        return a[i].name ;
        return null ;
}

function initPlaneLog() {
	var planeSelect = document.getElementById('planeSelect') ;
	if (planeSelect) planeSelect.value = '<?=$plane?>' ;
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
}

</script>
<div class="container-fluid">
<h2>Carnet de routes de <?=$plane?> du <?=$since?> au <?=$monthAfterForTitleString?></h2>
<?php
print("Carnet de route de: <select id=\"planeSelect\" onchange=\"planeChanged(this);\">" ) ;
$result = mysqli_query($mysqli_link, "SELECT *
	FROM $table_planes
	WHERE ressource = 0
	ORDER BY id") ;
$plane_details = array() ;
while ($row = mysqli_fetch_array($result)) {
	$row['id'] = strtoupper($row['id']) ;
	print("<option value=\"$row[id]\">$row[id]</option>\n") ;
	$plane_details[$row['id']] = $row ;
}
print("<option value=\"TOUS\">Tous</option>
</select> ") ;
?>
<div class="row">
	<ul class="pagination">
		<li class="page-item">
			<a class="page-link" href="<?="$_SERVER[PHP_SELF]?plane=$plane&since=$monthBeforeString"?>">
				<i class="bi bi-caret-left-fill"></i>  <?=datefmt_format($fmt, $monthBefore)?>
			</a></li>
		<li class="page-item active"><a class="page-link" href="<?="$_SERVER[PHP_SELF]?plane=$plane&since=$since"?>"><?=$monthName?></a></li>
		<li class="page-item"><a class="page-link" href="<?="$_SERVER[PHP_SELF]?plane=$plane&since=$monthAfterString"?>">
			<?=datefmt_format($fmt, $monthAfter)?> <i class="bi bi-caret-right-fill"></i></a></li>
	</ul><!-- pagination -->
</div><!-- row -->
<table class="table table-bordered table-hover table-sm">
<thead>
<tr>
<?php
if ($plane == 'TOUS') 
	print("<th class=\"text-center border-bottom-0\">Plane</th>") ;
?>
<th class="text-center border-bottom-0">Date</th>
<th class="text-center border-bottom-0">Pilot(s)</th>
<th class="text-center border-bottom-0" colspan="2">Airports</th>
<th class="text-center border-bottom-0" colspan="2">Time (UTC)</th>
<th class="text-center border-bottom-0">Engine time</th>
<?php 
if ($plane == 'TOUS' or $plane_details[$plane]['compteur_vol'] != 0)
	print("<th class=\"text-center border-bottom-0\">Flight Time</th>\n") ;
?>
<th class="text-center border-bottom-0">Pax/crew</th>
<th class="text-center border-bottom-0">Type of</th>
<th class="text-center border-bottom-0" colspan="2">Engine index</th>
<?php 
if ($plane == 'TOUS' or $plane_details[$plane]['compteur_vol'] != 0)
	print("<th class=\"text-center border-bottom-0\" colspan=\"2\">Flight index</th>\n") ;
?>
<th class="text-center border-bottom-0">Remark</th>
</tr>
<tr>
<?php
if ($plane == 'TOUS') 
	print("<th class=\"text-center border-bottom-0\"></th>") ;
?>
<th class="text-center border-top-0">(dd/mm/yy)</th>
<th class="text-center border-top-0"></th>
<th class="text-center border-top-0">Origin</th>
<th class="text-center border-top-0">Destination</th>
<th class="text-center border-top-0">Takeoff</th>
<th class="text-center border-top-0">Landing</th>
<th class="text-center border-top-0">minutes</th>
<?php 
if ($plane == 'TOUS' or $plane_details[$plane]['compteur_vol'] != 0)
	print("<th class=\"text-center border-top-0\">minutes</th>\n") ;
?>
<th class="text-center border-top-0">Count</th>
<th class="text-center border-top-0">flight</th>
<th class="text-center border-top-0">Begin</th>
<th class="text-center border-top-0">End</th>
<?php 
if ($plane == 'TOUS' or $plane_details[$plane]['compteur_vol'] != 0)
	print("<th class=\"text-center border-top-0\">Begin</th>\n
		<th class=\"text-centerborder-top-0 \">End</th>\n") ;
?>
<th class="text-center border-top-0">(CP, ...)</th>
</tr>
</thead>
<?php
$plane_sql_filter = ($plane == 'TOUS') ? '' : " l_plane = '$plane' AND " ;
$sql = "SELECT l_plane, DATE_FORMAT(l_start, '%d/%m/%y') as date, l_start, l_end, l_end_hour, l_end_minute, l_start_hour, l_start_minute,
			TIMEDIFF(l_end, l_start) AS duration,
			l_flight_end_hour, l_flight_end_minute, l_flight_start_hour, l_flight_start_minute,
			UPPER(l_from) as l_from, UPPER(l_to) as l_to, l_flight_type, p.name as pilot_name, i.name as instructor_name, l_remark, l_pax_count, l_crew_count, 
			l_share_type, l_share_member,
			l_booking, l_pilot, l_instructor
	FROM $table_logbook l 
		JOIN $table_users p ON l_pilot=p.id
		LEFT JOIN $table_users i ON l_instructor = i.id
	WHERE $plane_sql_filter
		'$since' <= l_start AND l_start < '$monthAfterString'
		AND l_start_hour != 0
	ORDER BY l_plane ASC, l_start ASC" ;
$result = mysqli_query($mysqli_link, $sql) 
	or journalise($userId, "F", "Erreur système à propos de l'accès au carnet de routes: " . mysqli_error($mysqli_link)) ;
$duration_total_hour = 0 ;
$duration_total_minute = 0 ;
$pic_total_hour = 0 ;
$pic_total_minute =  0;
$dual_total_hour = 0 ;
$dual_total_minute =  0;
$fi_total_hour = 0 ;
$fi_total_minute =  0;
$line_count = 0 ;
$engine_total_minute = 0 ;
$flight_total_minute = 0 ;
$previous_airport = false ;
$previous_plane = '' ;
$col_span = ($plane == 'TOUS') ? 16 : (($plane_details[$plane]['compteur_vol'] != 0) ? 15 : 13) ;
while ($row = mysqli_fetch_array($result)) {
	if ($previous_plane != $row['l_plane']) {
		if ($previous_plane != '')
			print('</tbody>') ;
		print('<tbody class="table-group-divider">') ;
		$previous_plane = $row['l_plane'] ;
		$previous_airport = false ;
		$previous_end_hour = false ;
		$previous_end_minute = false ;
		$previous_end_utc = false ;
		$previous_end_lt = false ;
	}
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
			print("<tr><td class=\"bg-danger text-bg-danger text-center\" colspan=\"$col_span\">Missing entries for $gap minutes..." . implode('<br/>', $missingPilots) . "</td></tr>\n") ;
		} else if ($gap < 0)
			print("<tr><td class=\"bg-danger text-bg-danger text-center\" colspan=\"$col_span\">Overlapping / duplicate entries for $gap minutes...</td></tr>\n") ;
		if ($previous_end_lt > $this_start_lt)
			print("<tr><td class=\"bg-danger text-bg-danger text-center\" colspan=\"$col_span\">Overlapping entries (previous end time after next start)...</td></tr>\n") ;
	}
	// Emit a red line if previous arrival apt and this departure airport do not match
	if ($previous_airport and $previous_airport != $row['l_from'])
		print("<tr><td class=\"bg-danger text-bg-danger text-center\" colspan=\"$col_span\">Departure airport below($row[l_from]) does not match previous arrival airport ($previous_airport)... taxes are probably invalid.</td></tr>\n") ;
	// Emit a red line if airports are unknown/invalid
	if (!isValidAirport($row['l_from']))
		print("<tr><td class=\"bg-danger text-bg-danger text-center\" colspan=\"$col_span\">Departure airport below($row[l_from]) is not valid or is unknown... taxes are probably invalid.</td></tr>\n") ;
	if (!isValidAirport($row['l_to']))
		print("<tr><td class=\"bg-danger text-bg-danger text-center\" colspan=\"$col_span\">Arrival airport below($row[l_to]) is not valid or is unknown... taxes are probably invalid.</td></tr>\n") ;
	// Emit a red line if pilot and instructor are the same
	if ($row['l_pilot'] == $row['l_instructor'])
		print("<tr><td class=\"bg-danger text-bg-danger text-center\" colspan=\"$col_span\">The pilot is the instructor on the line below...</td></tr>\n") ;
	// Emit a red line instructor is 'other FI'
	if ($row['l_instructor'] == -1)
		print("<tr><td class=\"bg-danger text-bg-danger text-center\" colspan=\"$col_span\">The FI is 'other FI' on the line below...</td></tr>\n") ;
	// Emit a warning line if no instructor for students
	if ($row['l_instructor'] == 0 and isset($students[$row['l_pilot']]))
		print("<tr><td class=\"bg-warning text-bg-warning text-center\" colspan=\"$col_span\">Student flight has no FI on the line below... perhaps a solo flight?</td></tr>\n") ;
	// Emit a red line if cost is shared with nobody
	if (($row['l_share_type'] == 'CP1' or $row['l_share_type'] == 'CP2') and $row['l_share_member'] == 0)
		print("<tr><td class=\"bg-danger text-bg-danger text-center\" colspan=\"$col_span\">Shared flight with nobody on the line below...</td></tr>\n") ;
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
	if ($row['l_instructor'] == -1)
		$instructor_name = "Autre FI" ;
	else
		$instructor_name = db2web($row['instructor_name']) ;
	// Time in $table_logbook is already in UTC
	$l_start = substr($row['l_start'], 11, 5) ;
	$l_end = substr($row['l_end'], 11, 5) ;
	$instructor = ($instructor_name != '') ? " /<br/>$instructor_name" : '' ;
	$bookingLink = ($userIsAdmin) ? " <a href=\"https://www.spa-aviation.be/resa/IntroCarnetVol.php?id=$row[l_booking]\" title=\"Go to booking $row[l_booking]\" target=\"_blank\"> <i class=\"bi bi-box-arrow-up-right\"></a>" : '' ;
	if ($row['l_start_minute'] < 10)
			$row['l_start_minute'] = "0$row[l_start_minute]" ;
	if ($row['l_end_minute'] < 10)
			$row['l_end_minute'] = "0$row[l_end_minute]" ;
	if ($row['l_flight_start_minute'] < 10)
			$row['l_flight_start_minute'] = "0$row[l_flight_start_minute]" ;
	if ($row['l_flight_end_minute'] < 10)
			$row['l_flight_end_minute'] = "0$row[l_flight_end_minute]" ;
	if ($row['l_share_type'] != '') $row['l_remark'] = '<b>' . $row['l_share_type'] . '<span class="shareCodeClass">' . $row['l_share_member'] . '</span></b> ' . $row['l_remark'] ;
	print("<tr>") ;
	if ($plane == 'TOUS')
		print("<td class=\"text-center\">$row[l_plane]</td>") ;
	print("<td class=\"text-center\">$row[date]</td>
		<td class=\"text-center\">$pilot_name$instructor$bookingLink</td>
		<td class=\"text-center\">$row[l_from]</td>
		<td class=\"text-center\">$row[l_to]</td>
		<td class=\"text-center\">$l_start</td>
		<td class=\"text-center\">$l_end</td>
		<td class=\"text-center\">$duration</td>\n") ;
		if ($plane == 'TOUS' or $plane_details[$plane]['compteur_vol'] != 0) {
			$flight_duration = 60 * ($row['l_flight_end_hour'] - $row['l_flight_start_hour']) + $row['l_flight_end_minute'] - $row['l_flight_start_minute'] ;
			$flight_total_minute += $flight_duration ;
			print("<td class=\"text-center\">$flight_duration</td>\n") ;
		}
		print("<td class=\"text-center\">$row[l_pax_count]/$row[l_crew_count]</td>
		<td class=\"text-center\">$row[l_flight_type]</td>
		<td class=\"text-center\">$row[l_start_hour]:$row[l_start_minute]</td>
		<td class=\"text-center\">$row[l_end_hour]:$row[l_end_minute]</td>\n") ;
	if ($plane != 'TOUS' and $plane_details[$plane]['compteur_vol'] != 0) {
		print("<td class=\"text-center\">$row[l_flight_start_hour]:$row[l_flight_start_minute]</td>\n") ;
		print("<td class=\"text-center\">$row[l_flight_end_hour]:$row[l_flight_end_minute]</td>\n") ;
	} else 	if ($plane == 'TOUS') {
		print("<td class=\"text-center\"></td>\n") ;
		print("<td class=\"text-center\"></td>\n") ;
	}
	print("<td class=\"text-start\">$row[l_remark]</td>") ;
	print("</tr>\n") ;
}

// Missing logbook entries until now
if (! isset($previous_end_lt)) {
	$previous_end_lt = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
}
$missingPilots = array() ;
// A little tricky as the data in $table_logbook is in UTC and in $table_bookings in local time :-O
// Moreover, the MySQL server at OVH does not support timezone... I.e., everything must be done in PHP
// I.e., the logging data must be converted into local time
$result2 = mysqli_query($mysqli_link, "SELECT last_name, r_start, r_stop, r_type, r_id
	FROM $table_bookings AS b JOIN $table_person ON b.r_pilot = jom_id
	WHERE r_plane = '$plane' AND r_cancel_date IS NULL
		AND '" . $previous_end_lt->format('Y-m-d H:i') . "' <= r_start
		AND r_stop < SYSDATE()
		AND r_start < '$monthAfterString'
		AND NOT EXISTS (SELECT * FROM $table_logbook AS l WHERE b.r_id = l.l_booking)
	ORDER by b.r_start ASC") or die("Erreur système à propos de l'accès aux réservations manquantes après: " . mysqli_error($mysqli_link));
while ($row2 = mysqli_fetch_array($result2)) {
	if ($row2['r_type'] == BOOKING_MAINTENANCE)
		$missingPilots[] = 'Maintenance (' . substr($row2['r_start'], 0, 10) . ') #' . $row2['r_id'] ;
	else
		$missingPilots[] = db2web($row2['last_name']) . ' (' . substr($row2['r_start'], 0, 10) . ') #' . $row2['r_id'] ;
}
if (count($missingPilots) > 0)
	print("<tr><td class=\"bg-danger text-bg-danger text-center\" colspan=12>Missing entries..." . implode('<br/>', $missingPilots) . "</td></tr>\n") ;

$engine_total_hour = floor($engine_total_minute / 60) ;
$engine_total_minute = $engine_total_minute % 60 ;
if ($engine_total_minute < 10)
	$engine_total_minute = "0$engine_total_minute" ;
if ($plane == 'TOUS' or $plane_details[$plane]['compteur_vol'] != 0) {
	$flight_total_hour = floor($flight_total_minute / 60) ;
	$flight_total_minute = $flight_total_minute % 60 ;
	if ($flight_total_minute < 10)
		$flight_total_minute = "0$flight_total_minute" ;
}
?>
</tbody>
<tfoot  class="table-group-divider">

<?php
if ($plane == 'TOUS') {
	print('<tr class="table-info"><td colspan="7" class="text-end"><strong>Logged total</strong></td>') ;
	print('<td class="text-center"><strong>' . "$engine_total_hour:$engine_total_minute" . '</strong></td>') ;
	print("<td class=\"text-center\"><strong>$flight_total_hour:$flight_total_minute</strong></td>") ;
	print('<td colspan="7" class="text-center"></td>') ;
} else if ($plane != 'TOUS' and $plane_details[$plane]['compteur_vol'] != 0) {
		print('<tr class="table-info"><td colspan="6" class="text-end"><strong>Logged total</strong></td>') ;
		print('<td class="text-center"><strong>' . "$engine_total_hour:$engine_total_minute" . '</strong></td>') ;
		print("<td class=\"text-center\"><strong>$flight_total_hour:$flight_total_minute</strong></td>") ;
		print('<td colspan="7" class="text-center"></td>') ;
} else {
	print('<tr class="table-info"><td colspan="6" class="text-end"><strong>Logged total</strong></td>') ;
	print('<td class="text-center"><strong>' . "$engine_total_hour:$engine_total_minute" . '</strong></td>') ;
	print('<td colspan="5" class="text-center"></td>') ;
}
?>
</tfoot>
</table>
<br>
<p><em>Sur base des données entrées après les vols dans le
carnet de route des avions. Heure affichée en heure universelle.</em></p>
</div><!-- container -->
<script>
// Swipe to change to next month/next plane
document.addEventListener('swiped-left', function(e) {location.href='<?="$_SERVER[PHP_SELF]?plane=$plane&since=$monthAfterString"?>' }) ;
document.addEventListener('swiped-right', function(e) {location.href='<?="$_SERVER[PHP_SELF]?plane=$plane&since=$monthBeforeString"?>' }) ;
</script>
</div> <!-- container-fluid -->
</body>
</html>

