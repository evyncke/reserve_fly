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
$need_swiped_events = true ; // Allow swipe events on this page
$header_postamble = '
<script src="data/instructors.js"></script>
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
<h2>&#128241; Réservations des avions</h2>
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
		$ptelephone = ($row['pcell_phone'] and ($userId > 0)) ? " <a href=\"tel:$row[pcell_phone]\"><i class=\"bi bi-telephone-fill\"></i></span></a>" : '' ;
		$pname = ($row['pfirst_name'] == '') ? $row['pname'] : 
			'<span class="hidden-xs">' . db2web($row['pfirst_name']) . ' </span><b>' . db2web($row['plast_name']) . '</b>' ;
		$itelephone = ($row['icell_phone'] and ($userId > 0)) ? " <a href=\"tel:$row[icell_phone]\"><i class=\"bi bi-telephone-fill\"></i></span></a>" : '' ;
		$instructor = ($row['ilast_name'] and $row['pid'] != $row['iid']) ? ' <i><span data-bs-toggle="tooltip" data-bs-placement="right" title="' .
			db2web($row['ifirst_name']) . ' ' . db2web($row['ilast_name']) . '">' .
			substr($row['ifirst_name'], 0, 1) . "." . substr($row['ilast_name'], 0, 1) . '. </span></i>' . $itelephone : '' ; 
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
		$onclick = " onclick=\"showDetails($row[r_id]);\" data-bs-target=\"#detailModal\"" ;
		print("<tr$onclick><td$class>$row[r_plane]</td><td$class>$display_start</td><td$class>$display_stop</td><td$class>$pname$ptelephone$instructor</td><td$class>". nl2br(db2web($row['r_comment'])) . "</td></tr>\n") ;
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

 <!-- Single Dynamic Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content text-body">
            	<div class="modal-header">
                  <h5 class="modal-title" id="detailModalLabel"></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              	</div>
              	<div class="modal-body" id="detailModalContent">
					<div id="modalSpinner" class="d-none"
			           style="position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.7); z-index:1051; display:flex; align-items:center; justify-content:center;">
        				<div class="spinner-border" style="width:4rem; height:4rem;" role="status"><span class="visually-hidden">En cours...</span></div>
					</div>
					<img id="pilotDetailsImage"><span id="pilotDetailsSpan"></span>
					Avion: <select id="planeSelect"></select>
					<span id="planeComment"></span>
					<span id="pilotType"><br/></span>
					Pilote/élève: <select id="pilotSelect" data-paid-membership="true"> </select><br/>
					Mobile pilote: <span id="pilotPhone"></span><br/>
					Instructeur: <select id="instructorSelect"></select><br/>
					Mobile instructeur: <span id="instructorPhone"></span><br/>
					Pilotes RAPCS: <input type="checkbox" id="crewWantedInput" value="true"> bienvenus en tant que co-pilotes.<br/>
					Membres RAPCS: <input type="checkbox" id="paxWantedInput" value="true"> bienvenus en tant que passagers.<br/>
					<span id="commentSpan" class="text-bg-info"></span>
					Début: <input type="datetime-local" id="start"><br/>
					Fin: <input type="datetime-local" id="stop"><br/>
					Route: <input type="text" id="fromInput" class="form-control d-inline-block" style="width: 4em;" minlength="3" maxlength="3" placeholder="de" required> -
						<input type="text" id="via1Input" class="form-control d-inline-block" style="width: 4em;" minlength="3" maxlength="3" placeholder="via"> -
						<input type="text" id="via2Input" class="form-control d-inline-block" style="width: 4em;" minlength="3" maxlength="3" placeholder="via"> -
						<input type="text" id="toInput" class="form-control d-inline-block" style="width: 4em;" minlength="3" maxlength="3" placeholder="à">
              	</div>
              	<div class="modal-footer">
					<button type="button" class="btn btn-info" id="indexButton"><i class="bi bi-stopwatch-fill"></i> Compteur</button>
					<button type="button" class="btn btn-danger" id="cancelButton"><i class="bi bi-trash3-fill"></i> Annuler la réservation</button>
                  	<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
              	</div>
        </div>
    </div>
</div>

<button type="button" class="btn btn-primary" onclick="window.location.href='mobile_book.php?date=<?=$displayDate?>';">
  <i class="bi bi-plus"></i> Nouvelle réservation
</button>

<script> // This could should probably be moved to a js/mobile_today.js file
	const bookings = <?=json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
	const modalContent = document.getElementById('detailModalContent');
	const modalElement = document.getElementById('detailModal');
	const modalInstance = new bootstrap.Modal(modalElement);

	// On button click: fetch content and show modal
	function showDetails(bookingId) {
			// Fill the form with the booking data
			var bookingPilot = bookings[bookingId].r_pilot ;
			var readonly = (userIsBoardMember || userIsInstructor || bookingPilot == userId) ? false : true ;

			hideSpinner() ; // Just to be sure...

			// Let's disable all controls if not allowed to change them
			const div = document.getElementById('detailModalContent');
			const inputs = div.querySelectorAll('input');
			inputs.forEach(function(input) {
				input.readOnly = readonly;
				input.disabled = readonly ;
			});
			const selects = div.querySelectorAll('select');
			selects.forEach(function(select) {
				select.disabled = readonly ;
			});

			document.getElementById("detailModalLabel").innerHTML = 'Réservation #' + bookingId ;
			document.getElementById("planeSelect").value = bookings[bookingId].r_plane ;
			document.getElementById("pilotSelect").value = bookings[bookingId].r_pilot ;
			document.getElementById("pilotPhone").innerHTML = '<a href="tel:' + bookings[bookingId].pcell_phone + '">' + bookings[bookingId].pcell_phone + ' <i class="bi bi-telephone-fill"></i></a>' ;
			if (bookings[bookingId].r_instructor <= 0)
				document.getElementById("instructorSelect").value = '-1' ;
			else
				document.getElementById("instructorSelect").value = bookings[bookingId].r_instructor ;
			document.getElementById("instructorPhone").innerHTML = '<a href="tel:' + bookings[bookingId].icell_phone + '">' + bookings[bookingId].icell_phone + ' <i class="bi bi-telephone-fill"></i></a>' ;
			document.getElementById("crewWantedInput").checked = bookings[bookingId].r_crew_wanted ;
			document.getElementById("paxWantedInput").checked = bookings[bookingId].r_pax_wanted ;
			if (bookings[bookingId].r_comment !== null && bookings[bookingId].r_comment != '')
				document.getElementById("commentSpan").innerHTML = bookings[bookingId].r_comment.replace(/\n/g, '<br/>') + '<br/>';
			else
				document.getElementById("commentSpan").innerHTML = '';
			document.getElementById("start").value = bookings[bookingId].r_start ;
			document.getElementById("stop").value = bookings[bookingId].r_stop ;
			document.getElementById("fromInput").value = bookings[bookingId].r_from ;
			document.getElementById("via1Input").value = bookings[bookingId].r_via1 ;
			document.getElementById("via2Input").value = bookings[bookingId].r_via2 ;
			document.getElementById("toInput").value = bookings[bookingId].r_to ;
			// Reset the picture in the div
			document.getElementById("pilotDetailsImage").src = '' ;
			document.getElementById("pilotDetailsImage").style.display = 'none' ;
			if (bookings[bookingId].avatar) {
				document.getElementById("pilotDetailsImage").src = bookings[bookingId].avatar ;
				document.getElementById("pilotDetailsImage").style.visibility = 'inherited' ;
				document.getElementById("pilotDetailsImage").style.display = 'inline' ;
			} else {
				document.getElementById("pilotDetailsImage").src = 'https://www.gravatar.com/avatar/' + bookings[bookingId].gravatar + '?s=80&d=blank&r=pg' ;
				document.getElementById("pilotDetailsImage").style.visibility = 'inherited' ;
				document.getElementById("pilotDetailsImage").style.display = 'inline' ;
			}
			if (!readonly) {
				document.getElementById("cancelButton").style.display = 'block' ;
				document.getElementById("cancelButton").onclick = cancelBooking.bind(this, bookingId) ;
				if (isSqlDateInPast(bookings[bookingId].r_start)) {
					document.getElementById("indexButton").style.display = 'block' ;
					document.getElementById("indexButton").onclick = indexBooking.bind(this, bookingId) ;
				} else {
					document.getElementById("indexButton").style.display = 'none' ;
					document.getElementById("indexButton").onclick = null ;
				}
			} else {
				document.getElementById("cancelButton").style.display = 'none' ;
				document.getElementById("cancelButton").onclick = null ;
				document.getElementById("indexButton").style.display = 'none' ;
				document.getElementById("indexButton").onclick = null ;
			}
			modalInstance.show();
	}

	function showSpinner() {
		document.getElementById('modalSpinner').classList.remove('d-none');
	}

	function hideSpinner() {
		document.getElementById('modalSpinner').classList.add('d-none');
	}

	function isSqlDateInPast(sqlDateString) {
		// Replace space with 'T' for ISO format if time is present
		const isoString = sqlDateString.replace(' ', 'T');
		const date = new Date(isoString);
		return date < new Date();
	}

	function indexBooking(bookingId) {
		showSpinner() ; // As this is a huge page taking seconds to load
		window.location.href = 'IntroCarnetVol.php?id=' + bookingId ;
	}

	function cancelBooking(bookingId) {
		var reason = prompt("Raison de l'annulation (optionnelle):", "") ;
		if (reason !== null) { // Not cancelled
			// User clicked OK
			// Send AJAX request to cancel the booking
			showSpinner() ;
			if (reason == '') reason = 'mobile_today.php' ; // Avoid null
			fetch('cancel_booking.php?id=' + encodeURIComponent(bookingId) + '&reason=' + encodeURIComponent(reason))
			.then(response => {
				if (!response.ok) throw new Error('Network error');
				return response.json(); // Parse response as JSON
			})
			.then(data => {
				// Use the JSON data here
				console.log(data);
				alert(data.message) ;
				modalInstance.hide();
			})
			.catch(error => {
				console.error('Error:', error);
			})
			.finally(() => {
				hideSpinner(); // Possibly useless if we reload the page ;-)
				location.reload() ; // Refresh the page to show the updated bookings
			});
		}
	}
</script>

<!-- Swipe previous / next -->
<script>
document.addEventListener('swiped-left', function(e) {location.href='<?=$_SERVER['PHP_SELF'] . '?date=' . $day_before?>' }) ;
document.addEventListener('swiped-right', function(e) {location.href='<?=$_SERVER['PHP_SELF'] . '?date=' . $day_after?>' }) ;
</script>
</div> <!-- container-->
</body>
</html>