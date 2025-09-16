<?php
/*
   Copyright 2014-2023 Eric Vyncke

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
# TODO check whether data/members.js is already loaded by mobile_header5.php
$header_postamble = '<script src="data/shareCodes.js"></script>
<script src="data/members.js"></script>
' ;
$body_attributes = 'onload="init();initShareCodes();"' ;

require_once 'mobile_header5.php' ;

if (!$userIsAdmin and !$userIsBoardMember and !$userIsInstructor) journalise($userId, "F", "This admin page is reserved to administrators") ;

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

?><script>
function findMember(a, m) {
	for (let i = 0 ; i < a.length ; i++)
		if (a[i].id == m)
			return a[i].name ;
	return null ;
}

function initShareCodes() {
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
<h2 class="text-center">Vols en codes partagés du <?=$since?> au <?=$monthAfterForTitleString?></h2>
<div class="row">
	<ul class="pagination">
		<li class="page-item">
			<a class="page-link" href="<?="$_SERVER[PHP_SELF]?since=$monthBeforeString"?>">
				<i class="bi bi-caret-left-fill"></i>  <?=datefmt_format($fmt, $monthBefore)?>
			</a></li>
		<li class="page-item active"><a class="page-link" href="<?="$_SERVER[PHP_SELF]?since=$since"?>"><?=$monthName?></a></li>
		<li class="page-item"><a class="page-link" href="<?="$_SERVER[PHP_SELF]?since=$monthAfterString"?>">
			<?=datefmt_format($fmt, $monthAfter)?> <i class="bi bi-caret-right-fill"></i></a></li>
	</ul><!-- pagination -->
</div><!-- row -->
<table class="table table-bordered table-hover table-sm">
<thead>
<tr>
<th class="text-center border-bottom-0">Date</th>
<th class="text-center border-bottom-0">Plane</th>
<th class="text-center border-bottom-0">Pilot(s)</th>
<th class="text-center border-bottom-0" colspan="2">Airports</th>
<th class="text-center border-bottom-0" colspan="2">Time (UTC)</th>	
<th class="text-center border-bottom-0">Engine time</th>
<th class="text-center border-bottom-0">Pax/crew</th>
<th class="text-center border-bottom-0">Type of</th>
<th class="text-center border-bottom-0" colspan="2">Engine index</th>
<th class="text-center border-bottom-0">Remark</th>
</tr>
<tr>
<th class="text-center border-top-0">(dd/mm/yy)</th>
<th class="text-center border-top-0"></th>
<th class="text-center border-top-0"></th>
<th class="text-center border-top-0">Origin</th>
<th class="text-center border-top-0">Destination</th>
<th class="text-center border-top-0">Takeoff</th>
<th class="text-center border-top-0">Landing</th>
<th class="text-center border-top-0">minutes</th>
<th class="text-center border-top-0">Count</th>
<th class="text-center border-top-0">flight</th>
<th class="text-center border-top-0">Begin</th>
<th class="text-center border-top-0">End</th>
<th class="text-center border-top-0">(CP, ...)</th>
</tr>
</thead>
<tbody class="table-group-divider">
<?php
//print("</br>since=$since ; monthAfterString=$monthAfterString</br>");
$sql = "select l_plane, date_format(l_start, '%d/%m/%y') as date, l_start, l_end, l_end_hour, l_end_minute, l_start_hour, l_start_minute,
	timediff(l_end, l_start) as duration,
	l_flight_end_hour, l_flight_end_minute, l_flight_start_hour, l_flight_start_minute,
	upper(l_from) as l_from, upper(l_to) as l_to, l_flight_type, p.name as pilot_name, i.name as instructor_name, l_remark, l_pax_count, l_crew_count, 
	l_share_type, l_share_member,
	l_booking, l_pilot, l_instructor
	from $table_logbook l 
	join $table_users p on l_pilot=p.id
	left join $table_users i on l_instructor = i.id
	where '$since' <= l_start and l_start < '$monthAfterString'
		and l_share_type in ('CP1', 'CP2') and l_share_member <= 0
		and not exists (select * from $table_flight where f_booking = l.l_booking)
	order by l_start asc, l_plane asc" ;
// Before, all entries add a l_booking for the flight club planes...
//	where l_plane = '$plane' and l_booking is not null
$result = mysqli_query($mysqli_link, $sql) or die("Erreur système à propos de l'accès au carnet de route: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
	// Don't trust the row but the diff of engine index
	$duration = 60 * ($row['l_end_hour'] - $row['l_start_hour']) + $row['l_end_minute'] - $row['l_start_minute'] ;
	// Handling character sets...
	$pilot_name = db2web($row['pilot_name']) ;
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
	print("<tr>
		<td class=\"text-center\">$row[date]</td>
		<td class=\"text-center\">$row[l_plane]</td>
		<td class=\"text-center\">$pilot_name$instructor$bookingLink</td>
		<td class=\"text-center\">$row[l_from]</td>
		<td class=\"text-center\">$row[l_to]</td>
		<td class=\"text-center\">$l_start</td>
		<td class=\"text-center\">$l_end</td>
		<td class=\"text-center\">$duration</td>
		<td class=\"text-center\">$row[l_pax_count]/$row[l_crew_count]</td>
		<td class=\"text-center\">$row[l_flight_type]</td>
		<td class=\"text-center\">$row[l_start_hour]:$row[l_start_minute]</td>
		<td class=\"text-center\">$row[l_end_hour]:$row[l_end_minute]</td>
		<td class=\"text-start\">$row[l_remark]</td>") ;
	print("</tr>\n") ;
}
?>
</tbody>
</table>
<br>
<p class="small">Seuls les vols en code partage club et en dehors des vols découvertes et d'initiation sont affichés. Sur base des donn&eacute;es entr&eacute;es apr&egrave;s les vols dans le
carnet de route des avions. Heure affich&eacute;e en heure universelle.</p>
</div><!-- container -->
</body>
</html>

