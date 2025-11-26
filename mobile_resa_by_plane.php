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

header('Link: </resa/js/mobile_modal_reservation.js>;rel=preload;as=script') ; // HTTP/2 push of some JS scripts via header()

require_once "dbi.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}
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
$today_closing = date('Y-m-d H:i', airport_closing_local_time(substr($displayDate, 0, 4), substr($displayDate, 5, 2), substr($displayDate, 8, 2))) ; // format is $year, $month, $day....
// TODO convert in local TZ... if not yet done
if ($userId != 62) journalise($userId, "D", "Using smartphone per plane booking page for $today_nice") ;
?> 
<div class="container-fluid">

<div class="page-header">
<h2><i class="bi bi-airplane"></i> Réservations par avion</h2>
<ul class="pagination justify-content-center">
	<li class="page-item"><a class="page-link" href="<?=$_SERVER['PHP_SELF'] . '?date=' . $day_before?>"><?=$day_before_nice?></a></li>
	<li class="page-item"><a class="page-link active" href="#"><?=$today_nice?></a></li>
	<li class="page-item"><a class="page-link" href="<?=$_SERVER['PHP_SELF'] . '?date=' . $day_after?>"><?=$day_after_nice?></a></li>
</ul> <!-- pagination -->
</div> <!-- page-header -->

<div class="row">
<table class="table table-striped table-hover">
	<thead>
	<tr><th class="d-none d-lg-table-cell">Avion</th><th>Début</th><th>Fin</th><th>Pilote</th><th>Remarque</th></tr>
	</thead>
<?php

function displayPlane($id) {
	global $rows, $mysqli_link, $displayDate, $userId, $sql_today, $sql_now, $today_closing,
		$table_planes, $table_bookings, $table_person, $table_flights,
		$avatar_root_directory, $avatar_root_uri,
		$avatar_root_resized_directory, $avatar_root_resized_uri ;
	print("<tr><td colspan=\"5\" class=\"table-active text-center text-bg-success\"><b>$id</b></td></tr>
		</tbody><tbody class=\"table-group-divider\">\n") ;
	$result = mysqli_query($mysqli_link, "SELECT *, i.last_name as ilast_name, i.first_name as ifirst_name, i.name as iname, i.cell_phone as icell_phone, i.jom_id as iid,
		pi.last_name as plast_name, pi.first_name as pfirst_name, pi.name as pname, pi.cell_phone as pcell_phone, pi.jom_id as pid
		FROM $table_planes p 
        LEFT JOIN $table_bookings b ON b.r_plane = p.id
		LEFT JOIN $table_person pi ON pi.jom_id = b.r_pilot
		LEFT JOIN jom_kunena_users k ON k.userid = b.r_pilot
		LEFT JOIN $table_person i ON i.jom_id = b.r_instructor		
		LEFT JOIN $table_flights fl ON b.r_id = f_booking
		WHERE  p.id = '$id' AND r_cancel_date IS NULL AND 
            (r_stop IS NULL 
                OR r_start IS NULL 
                OR (DATE(r_stop) = '$displayDate' OR (DATE(r_start) <= '$displayDate' and '$displayDate' <= DATE(r_stop))))
		ORDER BY p.id, r_start ASC LIMIT 0,20")
		or die("Cannot retrieve bookings($id): " . mysqli_error($mysqli_link)) ;
	$previous_booking = $displayDate . " 09:00" ; // Start of today  TODO should be the airport opening time
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
		if ($previous_booking < $row['r_start'] and $displayDate >= $sql_today) {
			print('<tr><td colspan="5"><a href="mobile_book.php?plane=' . $id . '&date=' . $displayDate . 
				'&start=' . $previous_booking . '&end=' . $row['r_start'] . 
				'" class="btn btn-outline-primary btn-sm py-0" title="Créer une réservation"><i class="bi bi-plus"></i> Réserver ' . $id . '</a></td></tr>') ;
		}
		$previous_booking = $row['r_stop'] ;
		if (is_file("$_SERVER[DOCUMENT_ROOT]/$avatar_root_resized_directory/$row[avatar]"))
			$rows[$row['r_id']]['avatar'] = $avatar_root_resized_uri . '/' . $row['avatar'] ;
		elseif (is_file("$_SERVER[DOCUMENT_ROOT]/$avatar_root_directory/$row[avatar]"))
			$rows[$row['r_id']]['avatar'] = $avatar_root_uri . '/' . $row['avatar'] ;
		$ptelephone = ($row['pcell_phone'] and ($userId > 0)) ? "&nbsp;<a href=\"tel:$row[pcell_phone]\"><i class=\"bi bi-telephone-fill\" title=\"Téléphoner\"></i></span></a>" .
			"&nbsp;<a href=\"https://wa.me/$row[pcell_phone]\"><i class=\"bi bi-whatsapp\" title=\"Envoyer un message WhatsApp\"></i></a>" : '' ;
		$pname = ($row['pfirst_name'] == '') ? $row['pname'] : 
			'<b>' . db2web($row['plast_name']) . '</b><span class="d-none d-md-inline"> ' . db2web($row['pfirst_name']) . '</span>' ;
		$itelephone = ($row['icell_phone'] and ($userId > 0)) ? "&nbsp;<a href=\"tel:$row[icell_phone]\"><i class=\"bi bi-telephone-fill\" title=\"Téléphoner\"></i></span></a>" : '' ;
		$instructor = ($row['ilast_name'] and $row['pid'] != $row['iid']) ? '&nbsp;<i><span data-bs-toggle="tooltip" data-bs-placement="right" title="' .
			db2web($row['ifirst_name']) . ' ' . db2web($row['ilast_name']) . '">' .
			substr($row['ilast_name'], 0, 1) . "." . substr($row['ifirst_name'], 0, 1) . '. </span></i>' . $itelephone : '' ; 
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
			$planeClass = ' class="text-danger d-none d-lg-table-cell"' ;
			$pname = '<i class="bi bi-tools"></i> <i>Maintenance</i>' ;
			$ptelephone = '' ;
			$itelephone = '' ;
			$instructor = '' ;
		} else {
			$planeClass = ' class="d-none d-lg-table-cell"' ;
		}
		// Make the row clickable to show the details in a modal dialog
		$onclick = ($row['r_type'] != BOOKING_MAINTENANCE) ? " onclick=\"showDetails($row[r_id]);\" data-bs-target=\"#detailModal\"" : '';
		print("<tr$onclick><td$planeClass>$row[r_plane]</td><td$class>$display_start</td><td$class>$display_stop</td><td$class>$pname$ptelephone$instructor</td><td$class>". nl2br(htmlspecialchars(db2web($row['r_comment']))) . "</td></tr>\n") ;
	}
	if ($result->num_rows == 0) {
		$bookMessage = ($displayDate >= $sql_today) ? ' <a href="mobile_book.php?plane=' . $id . '&date=' . $displayDate . '" class="btn btn-outline-primary btn-sm py-0" title="Créer une réservation"><i class="bi bi-plus"></i> Réserver ' . $id . '</a>' : '' ;
		print('<tr><td colspan="5"><span class="d-none d-lg-inline">Aucune réservation pour ce jour.</span>' . $bookMessage . '</td></tr>') ;
	} else if ($previous_booking < $today_closing and $displayDate >= $sql_today) {
		print('<tr><td colspan="5"><a href="mobile_book.php?plane=' . $id . '&date=' . $displayDate .
			'&start=' . $previous_booking . '&end=' . $today_closing . 
			 '" class="btn btn-outline-primary btn-sm py-0" title="Créer une réservation"><i class="bi bi-plus"></i> Réserver ' . $id . '</a></td></tr>') ;
	}	
}

	$result_planes = mysqli_query($mysqli_link, "SELECT id
						FROM $table_planes 
						WHERE actif = 1 AND ressource = 0 ORDER BY id ASC")
		or journalise($userId, "F", "Cannot retrieve planes: " . mysqli_error($mysqli_link)) ;
	$rows = array() ;
	while ($plane = mysqli_fetch_array($result_planes)) {
		print('<tbody class="table-group-divider">') ;
		displayPlane(strtoupper($plane['id'])) ;
		print("</tbody>") ;
	}
?>
</tbody>
</table>
</div><!-- row -->

<div class="row">
	<p>Cliquez sur une ligne pour voir/modifier les détails de la réservation.</p>
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
					Pilote/élève: <select id="pilotSelect" data-paid-membership="true"> </select>
						 <a id="vcardLink"><i class="bi bi-person-vcard-fill" title="Ajouter le contact"></i></a><br/>
					Mobile pilote: <span id="pilotPhone"></span><br/>
					<span id="instructorSpan">
						Instructeur: <select id="instructorSelect"></select><br/>
						Mobile instructeur: <span id="instructorPhone"></span><br/>
					</span>
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

<?php
if ($displayDate >= $sql_today) {
?>
<button type="button" class="btn btn-primary" onclick="window.location.href='mobile_book.php?date=<?=$displayDate?>';">
  <i class="bi bi-plus"></i> Nouvelle réservation
</button>
<?php
}
?>
<script> // This array is used by mobile_modal_reservation.js showDetails() function
const bookings = <?=json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;

// Swipe previous / next & 15 minute refresh-->
setInterval(function() {
  location.reload();
}, 900000); // 900,000 ms = 15 minutes

// Swipe previous / next & 15 minute refresh-->
document.addEventListener('swiped-left', function(e) {location.href='<?=$_SERVER['PHP_SELF'] . '?date=' . $day_after?>'}) ;
document.addEventListener('swiped-right', function(e) {location.href='<?=$_SERVER['PHP_SELF'] . '?date=' . $day_before?>'}) ;
</script>
</div> <!-- container-->
</body>
</html>