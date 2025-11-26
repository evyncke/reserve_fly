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
# HTTP/2 push of some JS scripts via header()
header('Link: </resa/js/mobile.js>;rel=preload;as=script,</resa/data/members.js>;rel=preload;as=script,</resa/data/planes.js>;rel=preload;as=script,' .
  '</logo_rapcs_256x256_white.png>;rel=preload;as=image,</logo_rapcs_256x256.png>;rel=preload;as=image' . 
  $additional_preload) ;
$need_swiped_events = true ; // Allow swipe events on this page
$header_postamble = '
<script src="data/instructors.js"></script>
<script src="js/mobile_modal_reservation.js"></script>
' ;
require_once 'mobile_header5.php' ;

$displayDate = isset($_REQUEST['date']) ? mysqli_real_escape_string($mysqli_link, $_REQUEST['date']) : date('Y-m-d') ;
$dt = new DateTime($displayDate);

// Day before
$dt_before = clone $dt;
$dt_before->modify('-1 day');
$day_before = $dt_before->format('Y-m-d');

// Day after
$dt_after = clone $dt;
$dt_after->modify('+1 day');
$day_after = $dt_after->format('Y-m-d');

// Display today in the local language in human language
$fmt = datefmt_create(
    'fr_BE',
    IntlDateFormatter::FULL,
    IntlDateFormatter::FULL,
    'Europe/Brussels',
    IntlDateFormatter::GREGORIAN,
    'EEEE d MMMM yyyy' // See https://unicode-org.github.io/icu/userguide/format_parse/datetime/ !
) ;

$short_fmt = datefmt_create(
    'fr_BE',
    IntlDateFormatter::FULL,
    IntlDateFormatter::FULL,
    'Europe/Brussels',
    IntlDateFormatter::GREGORIAN,
    'EEEE d MMMM' // See https://unicode-org.github.io/icu/userguide/format_parse/datetime/ !
) ;

$today_nice = datefmt_format($fmt, $dt) ; // Nicely locally formatted date
$day_before_nice = datefmt_format($short_fmt, $dt_before) ; // Nicely locally formatted date
$day_after_nice = datefmt_format($short_fmt, $dt_after) ; // Nicely locally formatted date
$sql_today = date('Y-m-d') ;
$sql_now = date('Y-m-d H:i:s') ;
if ($userId != 62) journalise($userId, "D", "Using smartphone booking page for $today_nice") ;
?> 
<div class="container-fluid">

<div class="page-header">
<h2>&#128241; Réservations</h2>
<ul class="pagination justify-content-center">
	<li class="page-item"><a class="page-link" href="<?=$_SERVER['PHP_SELF'] . '?date=' . $day_before?>"><?=$day_before_nice?></a></li>
	<li class="page-item"><a class="page-link active" href="#"><?=$today_nice?></a></li>
	<li class="page-item"><a class="page-link" href="<?=$_SERVER['PHP_SELF'] . '?date=' . $day_after?>"><?=$day_after_nice?></a></li>
</ul> <!-- pagination -->
</div> <!-- page-header -->

<div class="row">
<table class="table table-striped table-hover">
	<thead>
	<tr><th>Avion</th><th>Début</th><th>Fin</th><th>Pilote</th><th>Remarque</th></tr>
	</thead>
	<tbody>
<?php
// TODO use ajax per day ?
	$result = mysqli_query($mysqli_link, "SELECT *, i.last_name as ilast_name, i.first_name as ifirst_name, i.name as iname, i.cell_phone as icell_phone, i.jom_id as iid,
		pi.last_name as plast_name, pi.first_name as pfirst_name, pi.name as pname, pi.cell_phone as pcell_phone, pi.jom_id as pid
		FROM $table_bookings b
		JOIN $table_person pi ON pi.jom_id = r_pilot
		JOIN jom_kunena_users k ON k.userid = r_pilot
		LEFT JOIN $table_person i ON i.jom_id = r_instructor		
		JOIN $table_planes p ON r_plane = p.id
		LEFT JOIN $table_flights fl ON r_id = f_booking
		WHERE  p.actif = 1 AND p.ressource = 0 AND r_cancel_date IS NULL AND (DATE(r_stop) = '$displayDate' OR (DATE(r_start) <= '$displayDate' and '$displayDate' <= DATE(r_stop)))
		ORDER BY r_start, r_plane ASC LIMIT 0,20")
		or die("Cannot retrieve bookings($plane): " . mysqli_error($mysqli_link)) ;
	$rows = array() ;
	$now_divider_shown = false ;
	while ($row = mysqli_fetch_array($result)) {
		// No need for seconds in the timing...
		$row['r_start'] = substr($row['r_start'], 0, 16) ;
		$row['r_stop'] = substr($row['r_stop'], 0, 16) ;
		// Canonicalize phone numbers
		$row['pcell_phone'] = canonicalizePhone($row['pcell_phone']) ;
		$row['icell_phone'] = canonicalizePhone($row['icell_phone']) ;
		// Keep all rows in an array for further processing
	    $rows[$row['r_id']] = [
			'r_id' => intval($row['r_id']),
			'r_plane' => $row['r_plane'],
			'r_start' => $row['r_start'],
			'r_stop' => $row['r_stop'],
			'r_pilot' => intval($row['r_pilot']),
			'pname' => $row['pname'],
			'pfirst_name' => $row['pfirst_name'],
			'plast_name' => $row['plast_name'],
			'pcell_phone' => $row['pcell_phone'],
			'r_instructor' => intval($row['r_instructor']),
			'iname' => $row['iname'],
			'ifirst_name' => $row['ifirst_name'],
			'ilast_name' => $row['ilast_name'],
			'icell_phone' => $row['icell_phone'],
			'r_crew_wanted' => intval($row['r_crew_wanted']),
			'r_pax_wanted' => intval($row['r_pax_wanted']),
			'r_comment' => $row['r_comment'],
			'r_from' => $row['r_from'],
			'r_to' => $row['r_to'],
			'r_via1' => $row['r_via1'],
			'r_via2' => $row['r_via2'],
			'r_type' => intval($row['r_type']),
			'f_type' => intval($row['f_type']),
			'gravatar' => md5(strtolower(trim($row['email']))), // Hash for gravatar
			// add other fields as needed
		] ;
		if (is_file("$_SERVER[DOCUMENT_ROOT]/$avatar_root_resized_directory/$row[avatar]"))
			$rows[$row['r_id']]['avatar'] = $avatar_root_resized_uri . '/' . $row['avatar'] ;
		elseif (is_file("$_SERVER[DOCUMENT_ROOT]/$avatar_root_directory/$row[avatar]"))
			$rows[$row['r_id']]['avatar'] = $avatar_root_uri . '/' . $row['avatar'] ;
		$ptelephone = ($row['pcell_phone'] and ($userId > 0)) ? "&nbsp;<a href=\"tel:$row[pcell_phone]\"><i class=\"bi bi-telephone-fill\" title=\"Téléphoner\"></i></span></a>" . 
			"&nbsp;<a href=\"https://wa.me/$row[pcell_phone]\"><i class=\"bi bi-whatsapp\" title=\"Envoyer un message WhatsApp\"></i></a>" : '' ;
		$pname = ($row['pfirst_name'] == '') ? $row['pname'] : 
			'<span class="d-none d-md-inline">' . db2web($row['pfirst_name']) . ' </span><b>' . db2web($row['plast_name']) . '</b>' ;
		$itelephone = ($row['icell_phone'] and ($userId > 0)) ? "&nbsp;<a href=\"tel:$row[icell_phone]\"><i class=\"bi bi-telephone-fill\" title=\"Téléphoner\"></i></span></a>" : '' ;
		$instructor = ($row['ilast_name'] and $row['pid'] != $row['iid']) ? '&nbsp;<i><span data-bs-toggle="tooltip" data-bs-placement="right" title="' .
			db2web($row['ifirst_name']) . ' ' . db2web($row['ilast_name']) . '">' .
			substr($row['ifirst_name'], 0, 1) . "." . substr($row['ilast_name'], 0, 1) . '.</span></i>&nbsp;' . $itelephone : '' ; 
		// Add an orange divider representing 'now'
		if ($sql_today == $displayDate and !$now_divider_shown and $row['r_start'] >= $sql_now) {
			$now_divider_shown = true;
			print('</tbody><tbody class="table-group-divider" style="border-top: 4px solid #ffc107; "><!--tr><td colspan="5" class="text-bg-warning"></td></tr-->') ;
		}
		$class = ($row['r_type'] == BOOKING_MAINTENANCE) ? ' class="text-danger"' : '' ;
		if ($row['f_type'] != '')
			$class = ' class="text-warning"' ;
		// Display date if not today, else display time
		if (strpos($row['r_start'], $displayDate) === 0) 
			$display_start = substr($row['r_start'], 11) ;
		else
			$display_start = substr($row['r_start'], 0, 10) ;
		if (strpos($row['r_stop'], $displayDate) === 0) 
			$display_stop = substr($row['r_stop'], 11) ;
		else
			$display_stop = substr($row['r_stop'], 0, 10) ;
		// If the booking is for maintenance, show it in red and don't display the booker's name
		if ($row['r_type'] == BOOKING_MAINTENANCE) {
			$class = ' class="text-danger"' ;
			$pname = '<i class="bi bi-tools"></i> <i>Maintenance</i>' ;
			$ptelephone = '' ;
			$instructor = '' ;
		}
		// Make the row clickable to show the details in a modal dialog
		$onclick = ($row['r_type'] != BOOKING_MAINTENANCE) ? " onclick=\"showDetails($row[r_id]);\" data-bs-target=\"#detailModal\"" : '';
		print("<tr$onclick><td$class>$row[r_plane]</td><td$class>$display_start</td><td$class>$display_stop</td><td$class>$pname$ptelephone$instructor</td><td$class>". nl2br(htmlspecialchars(db2web($row['r_comment']))) . "</td></tr>\n") ;
	}
	// Add an orange divider representing 'now' if not yet done
	if ($sql_today == $displayDate and !$now_divider_shown and $result->num_rows > 0) {
		print('</tbody><tbody class="table-group-divider" style="border-top: 4px solid #ffc107; "><tr><td colspan="5">Plus de réservations pour aujourd\'hui</td></tr>') ;
		$now_divider_shown = true;
	}
	if ($result->num_rows == 0)
		print('<tr><td colspan="5" class="text-bg-info">Aucune réservation pour ce jour</td></tr>') ;
?>
</tbody>
</table>
</div><!-- row -->
<div class="row">
	<p>Cliquez sur une ligne pour voir/modifier les détails de la réservation.</p>
</div><!-- row -->

<button type="button" class="btn btn-primary" onclick="window.location.href='mobile_book.php?date=<?=$displayDate?>';">
  <i class="bi bi-plus"></i> Nouvelle réservation
</button>
<?php
// Modal dialog for reservation details
require_once 'mobile_reservation_modal.php' ;
?>
<script> 
// This array is used by mobile_modal_reservation.js showDetails() function
const bookings = <?=json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;

// 15 minute refresh
setInterval(function() {
  location.reload();
}, 900000); // 900,000 ms = 15 minutes

// Swipe previous / next
document.addEventListener('swiped-left', function(e) {location.href='<?=$_SERVER['PHP_SELF'] . '?date=' . $day_after?>'}) ;
document.addEventListener('swiped-right', function(e) {location.href='<?=$_SERVER['PHP_SELF'] . '?date=' . $day_before?>'}) ;
</script>
</div> <!-- container-->
</body>
</html>