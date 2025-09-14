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
$need_swiped_events = true ; // Allow swipe events on this page
$header_postamble = '
<script src="instructors.js"></script>
' ;
require_once 'mobile_header5.php' ;

$displayTimestamp = (isset($_REQUEST['time'])) ? intval($_REQUEST['time']) : time() ;
// Display today in the local language in human language
$fmt = datefmt_create(
    'fr_BE',
    IntlDateFormatter::FULL,
    IntlDateFormatter::FULL,
    'Europe/Brussels',
    IntlDateFormatter::GREGORIAN,
    'EEEE d MMMM yyyy' // See https://unicode-org.github.io/icu/userguide/format_parse/datetime/ !
) ;
$today = datefmt_format($fmt, $displayTimestamp) ;
$sql_date = date('Y-m-d', $displayTimestamp) ;

?> 
<div class="container-fluid">

<div class="page-header">
<h2>Réservations des avions</h2>
<?php
if ($userId > 0) { // Only members can see all bookings
?>
<ul class="pagination justify-content-center">
	<li class="page-item"><a class="page-link" href="<?=$_SERVER['PHP_SELF'] . '?time=' . ($displayTimestamp - 24 * 3600)?>">Jour précédent</a></li>
	<li class="page-item"><a class="page-link active" href="#"><?=$today?></a></li>
	<li class="page-item"><a class="page-link" href="<?=$_SERVER['PHP_SELF'] . '?time=' . ($displayTimestamp + 24 * 3600)?>">Jour suivant</a></li>
</ul> <!-- pagination -->
<?php
}
?>
</div> <!-- page-header -->

<div class="row">
<table class="col-sm-12 col-lg-10 table table-striped">
	<thead>
	<tr><th>Avion</th><th>De</th><th>A</th><th>Pilote</th><th>Commentaire</th></tr>
	</thead>
	<tbody>
<?php
// TODO use ajax per day ?
	$result = mysqli_query($mysqli_link, "SELECT *, i.last_name as ilast_name, i.first_name as ifirst_name, i.name as iname, i.cell_phone as icell_phone, i.jom_id as iid,
		pi.last_name as plast_name, pi.first_name as pfirst_name, pi.name as pname, pi.cell_phone as pcell_phone, pi.jom_id as pid
		FROM $table_bookings b
		JOIN $table_person pi ON pi.jom_id = r_pilot
		LEFT JOIN $table_person i ON i.jom_id = r_instructor		
		JOIN $table_planes p ON r_plane = p.id
		LEFT JOIN $table_flights fl ON r_id = f_booking
		WHERE  p.actif = 1 AND p.ressource = 0 AND r_cancel_date IS NULL AND (DATE(r_stop) = '$sql_date' OR (DATE(r_start) <= '$sql_date' and '$sql_date' <= DATE(r_stop)))
		ORDER BY r_start, r_plane ASC LIMIT 0,20")
		or die("Cannot retrieve bookings($plane): " . mysqli_error($mysqli_link)) ;
	$rows = array() ;
	while ($row = mysqli_fetch_array($result)) {
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
			'r_type' => intval($row['r_type']),
			'f_type' => intval($row['f_type']),
			// add other fields as needed
	    ];
		$ptelephone = ($row['pcell_phone'] and ($userId > 0)) ? " <a href=\"tel:$row[pcell_phone]\"><i class=\"bi bi-telephone-fill\"></i></span></a>" : '' ;
		$pname = ($row['pfirst_name'] == '') ? $row['pname'] : 
			'<span class="hidden-xs">' . db2web($row['pfirst_name']) . ' </span><b>' . db2web($row['plast_name']) . '</b>' ;
		$itelephone = ($row['icell_phone'] and ($userId > 0)) ? " <a href=\"tel:$row[icell_phone]\"><i class=\"bi bi-telephone-fill\"></i></span></a>" : '' ;
		$instructor = ($row['ilast_name'] and $row['pid'] != $row['iid']) ? ' <i><span data-bs-toggle="tooltip" data-bs-placement="right" title="' .
			db2web($row['ifirst_name']) . ' ' . db2web($row['ilast_name']) . '">' .
			substr($row['ifirst_name'], 0, 1) . "." . substr($row['ilast_name'], 0, 1) . '. </span></i>' . $itelephone : '' ; 
		$class = ($row['r_type'] == BOOKING_MAINTENANCE) ? ' class="text-danger"' : '' ;
		if ($row['f_type'] != '')
			$class = ' class="text-warning"' ;
		// Display date if not today, else display time
		if (strpos($row['r_start'], $sql_date) === 0) 
			$display_start = substr($row['r_start'], 11) ;
		else
			$display_start = substr($row['r_start'], 0, 10) ;
		if (strpos($row['r_stop'], $sql_date) === 0) 
			$display_stop = substr($row['r_stop'], 11) ;
		else
			$display_stop = substr($row['r_stop'], 0, 10) ;
		// If in the past and not the user, grey out
		if (strtotime($row['r_stop']) < time())
			$class = ' class="text-secondary"' ;
		// If in the past and user or instructor or board member, allow to click to enter counter
		if (strtotime($row['r_stop']) < time() and ($userId == $row['r_pilot'] or $userIsInstructor or $userIsBoardMember))
			$onclick = " onclick=\"window.location.href = 'IntroCarnetVol.php?id=$row[r_id]';\"" ;
		else
			$onclick = ($userId == $row['r_pilot'] or $userIsInstructor or $userIsBoardMember) ? " onclick=\"showDetails($row[r_id]);\" data-bs-target=\"#detailModal\"" 
				: " onclick=\"console.log('onclick unprivileged');\"" ;
		print("<tr$onclick><td$class>$row[r_plane]</td><td$class>$display_start</td><td$class>$display_stop</td><td$class>$pname$ptelephone$instructor</td><td$class>". nl2br(db2web($row['r_comment'])) . "</td></tr>\n") ;
	}
?>
</tbody>
</table>
</div><!-- row -->

 <!-- Single Dynamic Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content text-body">
            	<div class="modal-header">
                  <h5 class="modal-title" id="detailModalLabel"></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              	</div>
              	<div class="modal-body" id="detailModalContent">
					Avion: <select id="planeSelect" onchange="ressourceHasChanged(this);"></select>
					<span id="planeComment"></span>
					<span id="pilotType"><br/>
					</span>
					Pilote/élève: <select id="pilotSelect" data-paid-membership="true"> </select><br/>
					Mobile pilote: <span id="pilotPhone"></span><br/>
					Instructeur: <select id="instructorSelect"></select><br/>
					Mobile instructeur: <span id="instructorPhone"></span><br/>
					Pilotes RAPCS: <input type="checkbox" id="crewWantedInput" value="true"> bienvenus en tant que co-pilotes.<br/>
					Membres RAPCS: <input type="checkbox" id="paxWantedInput" value="true"> bienvenus en tant que passagers.<br/>
					<span id="commentSpan"></span><br/>
					Début: <input type="datetime-local" id="start"><br/>
					Fin: <input type="datetime-local" id="stop"><br/>
					</span> <!-- planeSelectSpan -->
              	</div>
              	<div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
              	</div>
        </div>
    </div>
</div>

<script>
	const bookings = <?=json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
	const modalContent = document.getElementById('detailModalContent');
	const modalElement = document.getElementById('detailModal');

	// Initialize modal instance
	const modalInstance = new bootstrap.Modal(modalElement);

	// On button click: fetch content and show modal
	function showDetails(bookingId) {
			// Fill the form with the booking data
			var bookingPilot = bookings[bookingId].r_pilot ;
			var readonly = (userIsAdmin || userIsInstructor || bookingPilot == userId) ? false : true ;

			document.getElementById("detailModalLabel").innerHTML = 'Réservation #' + bookingId ;
			document.getElementById("planeSelect").value = bookings[bookingId].r_plane ;
			document.getElementById("planeSelect").disabled = readonly ;
			document.getElementById("pilotSelect").value = bookings[bookingId].r_pilot ;
			document.getElementById("pilotSelect").disabled = readonly ;
			document.getElementById("pilotPhone").innerHTML = '<a href="tel:' + bookings[bookingId].pcell_phone + '">' + bookings[bookingId].pcell_phone + ' <i class="bi bi-telephone-fill"></i></a>' ;
			if (bookings[bookingId].r_instructor <= 0)
				document.getElementById("instructorSelect").value = '-1' ;
			else
				document.getElementById("instructorSelect").value = bookings[bookingId].r_instructor ;
			document.getElementById("instructorSelect").disabled = readonly ;
			document.getElementById("instructorPhone").innerHTML = '<a href="tel:' + bookings[bookingId].icell_phone + '">' + bookings[bookingId].icell_phone + ' <i class="bi bi-telephone-fill"></i></a>' ;
			document.getElementById("crewWantedInput").checked = bookings[bookingId].r_crew_wanted ;
			document.getElementById("crewWantedInput").disabled = readonly ;
			document.getElementById("paxWantedInput").checked = bookings[bookingId].r_pax_wanted ;	
			document.getElementById("paxWantedInput").disabled = readonly ;
			document.getElementById("commentSpan").innerHTML = bookings[bookingId].r_comment.replace(/\n/g, '<br/>') ;
			document.getElementById("start").value = bookings[bookingId].r_start ;
			document.getElementById("start").readOnly = readonly ;    // Makes input read-only
			document.getElementById("start").disabled = readonly ;    // Disables
			document.getElementById("stop").value = bookings[bookingId].r_stop ;
			document.getElementById("stop").readOnly = readonly ;    // Makes input read-only
			document.getElementById("stop").disabled = readonly ;    // Disables
            modalInstance.show();
			console.log("modal shown") ;
	};
</script>

<!-- Swipe previous / next -->
<?php
if ($userId > 0) { // Only members can see all bookings
?>
<script>
document.addEventListener('swiped-left', function(e) {location.href='<?=$_SERVER['PHP_SELF'] . '?time=' . ($displayTimestamp + 24 * 3600)?>' }) ;
document.addEventListener('swiped-right', function(e) {location.href='<?=$_SERVER['PHP_SELF'] . '?time=' . ($displayTimestamp - 24 * 3600)?>' }) ;
</script>
<?php
} // $userId > 0
?>
</div> <!-- container-->

</body>
</html>