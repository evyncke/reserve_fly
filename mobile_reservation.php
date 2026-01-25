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

$microtime_start = microtime(TRUE) ; // Get start time in floating seconds
ini_set('display_errors', 1) ; // extensive error reporting for debugging
require_once "dbi.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}

require_once "odoo.class.php" ;
$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password, FALSE) ;

$month_names = array('N/A', 'Jan', 'Fév', 'Mars', 'Avril', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc') ;

for ($i = 1, $all_day_options = '' ; $i <= 31 ; $i++)
	$all_day_options .= '<option value="' . $i . '">' . $i . "</option>\n" ;
for ($i = 1, $all_month_options = '' ; $i <= 12 ; $i++)
	$all_month_options .= '<option value="' . $i . '">' . $month_names[$i] . "</option>\n" ;
for ($i = 0, $all_year_options = '' ; $i < 2 ; $i++)
	$all_year_options .= '<option value="' . (date('Y') + $i) . '">' . (date('Y') + $i) . "</option>\n" ;
for ($i = 0, $all_hour_options = '' ; $i < 24  ; $i++)
	$all_hour_options .= '<option value="' . $i . '">' . $i . "</option>\n" ;
for ($i = 0, $all_minute_options = '' ; $i <= 45  ; $i+=15)
	$all_minute_options .= '<option value="' . $i . '">' . $i . "</option>\n" ;

// Check all validity ratings
$validity_msg = '' ;
$userRatingValid = true ;
$userValidities = array() ;
$result = mysqli_query($mysqli_link, "select *,datediff(sysdate(), expire_date) as delta
	from $table_validity_type t left join $table_validity v on validity_type_id = t.id and jom_id = $userId")
	or die("Erreur systeme lors de la lecture de vos validites: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
	$userValidities[$row['validity_type_id']] = true ;
	$row['name'] = db2web($row['name']) ;
	if ($row['delta'] == '') { // This validity was not filled in
		if ($row['mandatory'] > 0) {
			$userRatingValid = false ;
			$validity_msg .= "<span class=\"text-danger\">Votre profil ne contient pas $row[name]. Impossible de réserver un avion. Veuillez modifier votre profil d'abord.</span><br/>" ;
		}
	} elseif ($row['delta'] > 0) {
		if ($row['mandatory'] > 0) {
			$userRatingValid = false ;
			$validity_msg .= "<span class=\"text-danger\">Votre $row[name] n'est plus valable depuis le $row[expire_date]. Impossible de réserver un avion.</span><br/>" ;
		} else {
			$validity_msg .= "<span class=\"text-warning\">Votre $row[name] n'est plus valable depuis le $row[expire_date].</span><br/>" ;
		}
	} elseif ($row['delta'] > - $validity_warning) 
		$validity_msg .= "<span class=\"text-warning\">Votre $row[name] ne sera plus valable le $row[expire_date]; il vous sera alors impossible de réserver un avion.</span><br/>" ;
}

# HTTP/2 push of some JS scripts via header()
$additional_preload = '</resa/js/mobile_reservation.js>;rel=preload;as=script,' . 
	'</resa/css/mobile_reservation.css>;rel=preload;as=style,' .
	'</resa/images/spinner.gif>;rel=preload;as=image,</resa/images/fa.ico>;rel=preload;as=image,' .
	'</resa/data/instructors.js>;rel=preload;as=script,</resa/data/ressources.js>;rel=preload;as=script,</resa/data/pilots.js>;rel=preload;as=script' ;

$header_postamble = '<link rel="stylesheet" type="text/css" href="css/mobile_reservation.css">
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="data/ressources.js"></script>
<script src="data/pilots.js"></script>
<script src="data/instructors.js"></script>
<script src="js/mobile_reservation.js"></script>
' ;
$body_attributes = 'onload="init(); initBooking();"' ;
$need_swiped_events = 1024 ; // Allow swipe events on this page only when view port width >= 1024px as responsive table scroll can be confusing
require_once 'mobile_header5.php' ;
?>
<div class="container-fluid">

<script>
var
	// preset Javascript constant fill with the right data from db.php PHP variables
	// This is bad practice to have this in the <body> though but too complex to pass it as $header_postamble 
	allPlanes = [
<?php
// Get the engine time from the mechanics  TODO no more useful as pilot logbook is now mandatory
$result = mysqli_query($mysqli_link, "SELECT upper(id) as id, classe, compteur, compteur_vol, compteur_vol_valeur, compteur_date, entretien, photo, 
		sous_controle, delai_reservation, commentaire, actif, compteur_vol
	FROM $table_planes
	WHERE actif > 0 AND ressource = 0
	ORDER BY model ASC, id ASC") 
		or journalise($userId, "F", "Cannot get plane info: " . mysqli_error($mysqli_link));
$first = true ;
while ($row = mysqli_fetch_array($result)) {
	if ($first)
		$first = false ;
	else
		print(",\n") ;
	if ($row['compteur_vol']) $row['compteur'] = ($row['compteur_vol_valeur'] != '') ? $row['compteur_vol_valeur'] : 'null' ;
	// Get the engine time from the last entry in the pilot log book
	$index_column = ($row['compteur_vol'] == 0) ? 'l_end_hour' : 'l_flight_end_hour' ;
	$result2 = mysqli_query($mysqli_link, "SELECT $index_column as compteur_pilote, l_end as compteur_pilote_date, concat(first_name, ' ', last_name) as compteur_pilote_nom, email, name 
		FROM $table_logbook  l 
			JOIN $table_bookings r ON l_booking = r_id 
			JOIN $table_person p ON jom_id = if(l_audit_who <= 0, if(l_instructor is null, l_pilot, l_instructor), l_audit_who)
		WHERE l_plane = '$row[id]' AND l_booking IS NOT NULL AND l_end_hour > 0
		ORDER BY l_start DESC
		LIMIT 0,1")
		or journalise($userId, "F", "Cannot get pilote engine time:" . mysqli_error($mysqli_link)) ;
	$row2 = mysqli_fetch_array($result2) ;
	if (! $row2 or $row2['compteur_pilote']  == '') {
		$row2['compteur_pilote'] = 'null' ;
		$row2['compteur_pilote_date'] = 'null' ;
		$row2['compteur_pilote_nom'] = 'null' ;
	} else {
		$row2['compteur_pilote_nom'] = db2web(($row2['compteur_pilote_nom'] == '') ? $row2['name']  : $row2['compteur_pilote_nom']) ;
	}
	// Not too distant reservation?
// Old code: only looking in the actual 'carnet de routes' entries
//	$result3 = mysqli_query($mysqli_link, "select l_end, datediff(sysdate(), l_end) as temps_dernier 
//		from $table_logbook l join $table_bookings r on l_booking = r_id
//		where r_plane = '$row[id]' and (r_pilot = $userId or (r_instructor is not null and r_instructor = $userId)) and l_booking is not null
//		order by l_end desc") or die("Cannot get last reservation: " . mysqli_error($mysqli_link)) ;

	// New code: look at pilot log book whether 'carnet de routes' or not :-(
	$index_column = ($row['compteur_vol'] == 0) ? 'l_end_hour' : 'l_flight_end_hour' ;
	$result3 = mysqli_query($mysqli_link, "select $index_column, l_end, datediff(sysdate(), l_end) as temps_dernier 
		from $table_logbook l
		where l_plane = '$row[id]' and (l_pilot = $userId or (l_instructor is not null and l_instructor = $userId))
		order by l_end desc") or die("Cannot get last reservation: " . mysqli_error($mysqli_link)) ;
	$row3 = mysqli_fetch_array($result3) ;
	if (! $row3) {
		$l_end = 'jamais' ;
		$reservation_permise = FALSE ;
		print("//No log entries found for  $row[id]...\n") ;
	} else {
		$l_end = $row3['l_end'] ;
		$reservation_permise = $row['delai_reservation'] >= $row3['temps_dernier'] ;
		print("// last booking $row3[temps_dernier] and delai_reservation = $row[delai_reservation]\n") ;
	}
	// Check for any opened Aircraft Tech Log entries for the plane
	$sql = "SELECT GROUP_CONCAT(DISTINCT UPPER(i_severity)) AS severities
		FROM $table_incident AS i
		JOIN $table_incident_history AS ih ON i_id = ih_incident
		WHERE i.i_plane = '$row[id]' AND NOT EXISTS 
			(SELECT * FROM $table_incident_history AS ih2 
			WHERE ih2.ih_incident = ih.ih_incident AND ih2.ih_status IN ('closed', 'rejected', 'duplicate', 'camonoaog', 'inprogressnoaog'))" ;

	$result_incident = mysqli_query($mysqli_link, $sql) or journalise($userId, "E", "Cannot read incident for $row[id]: " . mysqli_error($mysqli_link)) ;
	$row_incident = mysqli_fetch_assoc($result_incident) ;
	// if ($userId == 62) var_dump($row_incident) ;
	// if ($userId == 62) print("\n$sql\n") ;
	switch ($row_incident['severities']) {
		case NULL: $incidents = '' ; break ;
		case 'NOHAZARD':
		case 'HAZARD': $incidents = $row_incident['severities'] ; break ;
		case 'HAZARD,NOHAZARD': $incidents = 'HAZARD' ; break ;
		}
	// Check required qualifications
	$qualifications_validated = TRUE ;
	$result4 = mysqli_query($mysqli_link, "select * from $table_planes_validity where pv_plane='$row[id]'")
		or die("Cannot list all required qualifications: " . mysqli_error($mysqli_link)) ;
	while ($row4 = mysqli_fetch_array($result4)) {
		$validity = $row4['pv_validity'] ;
		if (!isset($userValidities[$validity])) {
			print("// Missing validity $row4[pv_validity]...\n") ;
			$qualifications_validated = FALSE ;
		}
	}
	// Prevent XSS from comments field and a few others
	$row['entretien'] = htmlspecialchars(db2web($row['entretien']), ENT_QUOTES) ;
	$row['commentaire'] = htmlspecialchars(db2web($row['commentaire']), ENT_QUOTES) ;
	$row['id'] = htmlspecialchars(db2web($row['id']), ENT_QUOTES) ;
	print("{ \"id\": \"$row[id]\", \"compteur\": $row[compteur], \"compteur_date\": new Date(\"" . str_replace('-', '/', $row['compteur_date']) . "\"),\n" .
		" \"compteur_pilote\": $row2[compteur_pilote], \"compteur_pilote_date\": new Date(\"" . str_replace('-', '/', $row2['compteur_pilote_date']) . "\"),\n" .
		" \"compteur_pilote_nom\": \"$row2[compteur_pilote_nom]\",\n" .
		" \"entretien\": $row[entretien], \"photo\": \"$row[photo]\", \"commentaire\": \"" . str_replace(array("\r\n", "\r", "\n"), '<br />', $row['commentaire']) . "\",\n" .
		" \"reservation_permise\": " . (($reservation_permise) ? 'true' : 'false') . ",\n" .
		" \"qualifications_requises\": " . (($qualifications_validated) ? 'true' : 'false') . ",\n" .
		" \"dernier_vol\": \"$l_end\", \"actif\": $row[actif], \"ressource\": 0, \"incidents\": '$incidents',\n" .
		" \"classe\": \"$row[classe]\", \"sous_controle\": " . (($row['sous_controle'] == 0) ? 'false' : 'true') . "}\n") ;
}
// Now process the ressources (rooms, ...)
$result = mysqli_query($mysqli_link, "SELECT id, sous_controle, commentaire, actif, photo, ressource
	FROM $table_planes
	WHERE ressource <> 0
	ORDER BY id") or die("Cannot get all active ressources:".mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
	if ($first)
		$first = false ;
	else
		print(",\n") ;
	$reservation_permise = ($userIsAdmin or $userIsInstructor) ;
	$row['commentaire'] = htmlspecialchars(db2web($row['commentaire']), ENT_QUOTES) ;
	$row['id'] = htmlspecialchars(db2web($row['id']), ENT_QUOTES) ;
	print("{ \"id\": \"$row[id]\", \"photo\": \"$row[photo]\", \"commentaire\": \"" . str_replace(array("\r\n", "\r", "\n"), '<br />', $row['commentaire']) . "\", " .
		" \"reservation_permise\": " . (($reservation_permise) ? 'true' : 'false') . ", \"qualifications_requises\": true, " .
		" \"actif\": $row[actif], \"ressource\": $row[ressource], " .  
		" \"sous_controle\": " . (($row['sous_controle'] == 0) ? 'false' : 'true') . "}") ;

}
?> ] ;
var
	nowDay = Number(<?=date('j')?>) ;
	nowMonth = Number(<?=date('n')?>) ;
	nowYear = Number(<?=date('Y')?>) ;
	utcOffset = Number(<?=date('Z')/3600?>) ;
	defaultMetarStation = '<?= $default_metar_station ?>' ;
	webcamUris = [ <?php
		for ($i = 0; $i < count($webcam_uris); $i++) {
			if ($i != 0) print(", ") ;
			print("'$webcam_uris[$i]'") ;
		} ?> ] ;
	avatarRootURI = '<?=$avatar_root_resized_uri?>' ;
	nowHour = <?=date('G')?> ;
	nowMinute = <?=0+date('i')?> ;  // Some PHP code to attempt to remove leading 0 as javascript does not like them...
	nowDate = new Date(nowYear, nowMonth - 1, nowDay, nowHour, nowMinute, <?=0+date('s')?>) ;
	planningDayOfWeek = nowDate.getDay() ;
	nowTimestamp = <?=time()?> ;
	runwaysQFU = [ <?php print(implode(', ', $runways_qfu)) ; ?> ] ;

function convertCharset(s) {
<?php
if (!$convertToUtf8) {
?>
	// By default, all data from all AJAX services are in the UTF-8 charset, so, if the web site is in ISO-8859-1
	// Then we need to convert the string
	try {
		s = decodeURIComponent(escape(s)) ; // What a trick!
	} catch(e) {
	}
<?php
}
?>
	return s ;
}

</script>

<h2>Réservation des avions</h2>
<div id="logDiv" style="visibility: collapse; background-color: yellow;"></div>
<div class="userPrivileges">
<?php
// Check for profile settings
$result = mysqli_query($mysqli_link, "select * from $table_users u left join $table_person p on u.id = p.jom_id where u.id = $userId")
	or die("Erreur systeme lors de la lecture de votre profil: " . mysqli_error($mysqli_link)) ;
$row = mysqli_fetch_array($result) ;
$profile_count = 0 ;
$missings = array() ;
if ($row['email'] != '') $profile_count ++ ; else $missings[] = 'email' ;
if ($row['first_name'] != '') $profile_count ++ ; else $missings[] = 'prénom' ;
if ($row['last_name'] != '') $profile_count ++ ; else $missings[] = 'nom de famille' ;
if ($row['cell_phone'] != '') $profile_count ++ ; else $missings[] = 'n° GSM/mobile' ;
if ($row['city'] != '') $profile_count ++ ; else $missings[] = 'ville' ;
if ($row['country'] != '') $profile_count ++ ; else $missings[] = 'pays' ;
if ($row['sex'] != '' and $row['sex'] != 0) $profile_count ++ ; else $missings[] = 'genre' ;
if ($row['birthdate'] != '') $profile_count ++ ; else $missings[] = 'date de naissance' ;
if ($profile_count != 8) print("<div class=\"text-bg-warning\">>Votre profil est complété à " . round(100 * $profile_count / 10) . "% seulement,
	vous pouvez visualiser et modifier votre profile en cliquant sur votre nom en haut à droite.</div>") ;
if ($row['cell_phone'] == '') {
	print("<div class=\"text-bg-danger\">Il manque votre numéro de GSM/mobile, impossible de réserver.
		Vous pouvez visualiser et modifier votre profile en cliquant sur votre nom en haut à droite.</div>") ;
	$userNoFlight = true ;
}
// Check whether the user is blocked
$result_blocked = mysqli_query($mysqli_link, "SELECT * FROM $table_blocked WHERE b_jom_id=$userId")
	or journalise($userId, 'E', "Cannot checked whether user is blocked: " . mysqli_error($mysqli_link)) ;
$row_blocked = mysqli_fetch_array($result_blocked) ;
if ($row_blocked) {
	journalise($userId, "W", "This user is blocked: " . db2web($row_blocked['b_reason'])) ;
	$userNoFlight = true ;
	print("<div class=\"text-bg-danger\">Vous êtes interdit(e) de vol: <b>" . db2web($row_blocked['b_reason']) . "</b>. 
		Contactez <a href=\"mailto:info@spa-aviation.be\">l'aéroclub info@spa-aviation.be</a>.
		Vous pouvez visualiser votre situation comptable en cliquant sur votre nom en haut à droite.</div>") ;
}
// Check whether users have paid theirs membership fees
// $row_fee is set in dbi.php
if (! $row_fee and ! $userIsInstructor and $userId != 294) // 294 = SPW
	if ($membership_year == date('Y')) {
		print("<div class=\"text-bg-danger\">Vous n'êtes pas en ordre de cotisation pour $membership_year (nécessaire pour payer les assurances pilotes).
			Il vous est donc impossible de réserver un avion.
			Vous pouvez visualiser votre situation comptable en cliquant sur votre nom en haut à droite.</div>") ;
		journalise($userId, "W", "This user has yet to pay their membership fee") ;
	} else {
			print("<div class=\"text-bg-warning\">Vous n'êtes pas en ordre de cotisation pour $membership_year.
			Il vous sera donc impossible de réserver un avion dès le 1er janvier $membership_year.
			<a href=\"mobile_membership.php\" class=\"btn btn-primary\">Payer votre cotisation</a></div>") ;
}

// Check whether there are unpaid due invoices
// TODO added by evyncke 2025-06-04 only check unpaid due balance if balance is < 0, else if balance >= 0, then all is good
$due_invoices = $odooClient->SearchRead('account.move', 
	array(array(
		array('partner_id.id', '=', intval($row['odoo_id'])),
		array('invoice_date_due', '<', date('Y-m-d')),
		array('move_type', '=', 'out_invoice'),
		array('state', '=', 'posted'),
		'|', array('payment_state', '=', 'not_paid'), array('payment_state', '=', 'partial'),
)),  
	array('fields'=>array('partner_id', 'invoice_date_due', 'amount_total'))); 

if (isset($due_invoices) and count($due_invoices) > 0) {
	$userNoFlight = true ;
	journalise($userId, "W", "This user has unpaid due invoices") ;
	print("<div class=\"text-bg-danger\">Vous avez des factures échues et non payées, par conséquent vous ne pouvez pas réserver un avion.</div>") ;
}

if ($userNoFlight)
	print("<div class=\"text-bg-danger\">Vous êtes interdit(e) de vol (par exemple: factures non payées), 
		contactez <a href=\"mailto:info@spa-aviation.be\">info@spa-aviation.be</a>.
		Vous pouvez visualiser votre situation comptable en cliquant sur votre nom en haut à droite.</div>") ;

// Display any validity message from above
if ($validity_msg != '') print('<div class="validityBox">' . $validity_msg . '</div>') ;

// Verify non-logged flights in the last week
if (! $userIsInstructor) {
$result = mysqli_query($mysqli_link, "select * from $table_bookings b join $table_planes p on r_plane = p.id  
	where p.actif = 1 and ressource = 0 and
		(b.r_pilot = $userId or b.r_who = $userId or b.r_instructor = $userId) and
		r_start > date_sub(curdate(), interval 1 month) and
		r_start < now() and
		r_cancel_date is null and
		r_type in (" . BOOKING_PILOT . ", " . BOOKING_INSTRUCTOR . ") and
		not exists (select * from $table_logbook l where l.l_booking = b.r_id)
	order by b.r_start desc limit 5") or die("Cannot select unlogged flights: " . mysqli_error($mysqli_link)) ;
if (mysqli_num_rows($result) > 0) {
	$missing_entries = mysqli_num_rows($result) ;
	print("<p style=\"color: red;\">Vous avez une ou plusieurs réservations sans entrées dans les carnets de routes des avions, il est obligatoire de compléter
		ces carnets sous peine de frais administratifs en fin de mois.</p><ul>") ;
	while ($row = mysqli_fetch_array($result)) {
		print("<li>$row[r_start]: <a href=\"IntroCarnetVol.php?id=$row[r_id]\">remplir le carnet de routes de $row[r_plane] ou annuler la réservation</a>;") ;
		if ($userIsInstructor) print(" <img src=\"gtk-delete.png\" onclick=\"javascript:document.getElementById('reasonTextArea').value='Old booking';cancelOldBooking($row[r_id]);\">" ) ;
		print("</li>\n") ;
	}
	print("</ul></p>\n") ;
}
} // Not $isInstructor
?>

<script>
	var userRatingValid = <?=($userRatingValid) ? 'true' : 'false' ?> ; // Was 'const' but IE does not support it
</script>
</div>

<div class="row">
	<div class="col d-none d-xl-block text-bg-light border rounded-3 mx-lg-3">
			<table id="ephemeridesTable">
			<tr><td>Jour aéronautique:</td><td></td>
				<td>Coucher du soleil:</td><td></td></tr>
			<tr><td>Lever du soleil:</td><td></td>
				<td>Nuit aéronautique:</td><td></td></tr>
			<tr><td>Ouverture aéroport:</td><td></td>
				<td>Fermeture aéroport:</td><td></td></tr>
			<tr><td colspan="4"><i><b>En heure locale de <?=$default_airport?> et pour info seulement.</b><br/>
					Heure locale à <?=$default_airport?>:  <span id="hhmmLocal"></span><br/>
					Heure universelle:  <span id="hhmmUTC"></span>Z</i></td></tr>
			</table>
	</div><!-- col -->	
<?php
$result_news = mysqli_query($mysqli_link, "SELECT * FROM $table_news
	WHERE n_stop >= CURRENT_DATE() and n_start <= CURRENT_DATE()
	ORDER BY n_id desc
	LIMIT 0,5") or die("Cannot fetch news: " . mysqli_error($mysqli_link)) ;

if (mysqli_num_rows($result_news) or $userIsAdmin) {
	print('<div class="col d-none d-lg-block text-bg-info border rounded-3  mx-lg-3">') ;
	while ($row_news = mysqli_fetch_array($result_news)) {
		$subject = db2web($row_news['n_subject']) ;
		$text = db2web(nl2br($row_news['n_text'])) ;
		$delete_action = ($userIsAdmin) ? ' <a href="news_delete.php?id=' . $row_news['n_id'] . '"><i class="bi bi-trash text-danger"></i></a>' : '' ;
		print("<li><b>$subject</b>: $text$delete_action</li>\n") ;
	}
	if ($userIsAdmin) print('<li><a href="news_add.php" class="text-bg-info"><i class="bi bi-plus-circle-fill"></i> Ajouter une nouvelle</a></li>') ;
	print('</ul>
	</div><!-- col -->') ;
}
mysqli_free_result($result_news) ;
?>
		<div class="col border rounded-3 mx-lg-3 text-bg-light" id="reservationDetails" style="width: 30%;"></div>
		<div class="col d-none d-md-block" id="webcamCell">
			<a href="" id="webcamURI" border="0"><img id="webcamImg" style="width: 256px; height: 192px;" alt="Webcam" onStalled="imgStalled();"></a>
		</div><!--col-->

</div><!-- row -->

<br/>

<!-- Planning ruler and planning tables using overflow-x-auto to have a sliding window on small screens 
 and left/start align on small screen and centered on wider ones -->
<?php
// The two tables are used to display the planning for the planes and instructors and are aligned on the right hand side
// specific width of the parent container is required to have a 'responsive' table via overflow-x-auto
// i.e., a sliding window at the bottom on small screens
//   NOTE: the container as justify-content-center, which is probably not good for narrow screen
//   NOTE: the actual width is computed based on the amount of airfield opening hours, e.g., 780px for Winter and 1024px or 984px for Summer
$planning_table_width =	(airport_closing_local_time(date('Y'), date('m'), date('d')) 
		- airport_opening_local_time(date('Y'), date('m'), date('d'))) / (60 * 15) * 18 ; // 17+1 px per 15 minutes
//		$planning_table_width = 721;
// One issue is that the left column (plane/instructor names) is not fixed width (proportional dynamic font plane_cell font-size: calc(0.5vw + 0.75vh))
// could use width: max-content; but not supported by all browsers yet
// or width: calc() ?
?>

 <div class="row d-flex justify-content-start justify-content-md-center overflow-x-auto">
	<div class="col">
		<table class="planningRuler mx-0" >
			<tr style="vertical-align: top;">
				<td class="planningRulerCell"><a href="javascript:bumpPlanningBy(-7);"><i class="bi bi-rewind-fill"></i></a></td>
				<td class="planningRulerCell"><a href="javascript:bumpPlanningBy(-1);"><i class="bi bi-caret-left-fill"></i></a></td>
				<td class="planningRulerCellLarge"><span id="planningDayOfWeek"></span><input type="date" sytyle="width: 100px;" id="planningDate" onchange="jumpPlanningDate();"></td>
				<td class="planningRulerCell"><a href="javascript:bumpPlanningBy(+1);"><i class="bi bi-caret-right-fill"></i></a></td>
				<td class="planningRulerCell"><a href="javascript:bumpPlanningBy(+7);"><i class="bi bi-fast-forward-fill"></i></a></td>
			</tr>
		</table>

		<div class="d-flex flex-column align-items-end overflow-x-auto" style="width: calc(<?=$planning_table_width?>px + 15ch);">
			<table id="planePlanningTable" class="planningTable" style="display: box;"></table>
			<table id="instructorPlanningTable" class="planningTable" style="display: box;"></table>
		</div><!-- d-flex justify-content-end -->
	</div><!-- col -->
</div><!-- row justify-content-center -->

<div class="text-center fw-light small">
<i class="bi bi-exclamation-triangle-fill text-danger" alt="!" width="12" height="12"></i>: vous n'avez pas volé dessus récemment (et le règlement d'ordre intérieur impose des vols récents).<br/>
<i class="bi bi-ban text-danger" alt="X" width="12" height="12"></i>: vous n'avez pas les qualifications requises (sur base des validités de votre profil).<br/>
<i class="bi bi-tools text-bg-warning" width="12" height="12"></i>: il existe un Aircraft Technical Log pour ce avion à consulter.<br/>
<img src="images/fa.ico" border="0" width="12" height="12">: ouvre Flight Aware avec le dernier vol de cet avion.<br/>
</div>
<center><input type="button" id="roadBookButton" value="Carnet de routes" onclick="roadBookClick();" disabled="true" style="display: none;"></center>
<p>

<div id="pilotDetailsDiv" class="text-bg-light">
	<img id="pilotDetailsImage"><span id="pilotDetailsSpan"></span>
	<hr><center><button class="btn btn-primary" onclick="hidePilotDetails();">OK</button></center>
</div><!-- pilotDetailsDiv -->

<!-- model window to display the plane/ressource booking confirmation message-->
<div class="modal fade" id="bookingMessageModal" tabindex="-1" aria-labelledby="bookingMessageLabel">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bookingMessageLabel">Statut de la réservation</h5>
        <button type="button" onclick="hideBookingMessage();" class="btn-close" data-bs-dismiss="modal" aria-label="OK"></button>
      </div>
      <div class="modal-body">
	  	<span id="bookingMessage"></span>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="hideBookingMessage();" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<!-- modal window to display the plane/ressource booking window (create, modify, cancel) -->
<div class="modal fade" id="bookingModal" tabindex="-1" role="dialog" aria-labelledby="bookingTitle">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bookingTitle">Effectuer une réservation</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Fermer">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
	  <span id="ressourceSelectSpan">
Ressource: <select id="ressourceSelect" onchange="ressourceHasChanged(this);"></select><br/>
Réservation pour: <select id="memberSelect"></select><br/>
</span><!-- ressourceSelectSpan -->
<span id="planeSelectSpan">
Avion: <select id="planeSelect" onchange="ressourceHasChanged(this);"></select>
<span id="planeComment"></span><span id="pilotType"><br/>
<?php
if ($userIsAdmin || $userIsInstructor)
	print("Pilote/élève: ") ;
else if ($userIsMechanic)
	print("Mécano: ") ;
else
	print("Pilote: ") ;
?></span><select id="pilotSelect" data-paid-membership="true"> </select><br/>
<?php
// If there is any pending customers list them here...
$result_customer = mysqli_query($mysqli_link, "SELECT * 
	FROM $table_flight JOIN $table_pax_role ON pr_role = 'C' and pr_flight = f_id JOIN $table_pax ON pr_pax = p_id
	WHERE f_pilot = $userId AND f_booking IS NULL AND f_date_cancelled IS NULL
	ORDER BY f_id")
	or die("Cannot retrieve customers: " . mysqli_error($mysqli_link)) ;
if (mysqli_num_rows($result_customer)) {
	print("Client vol découverte/initiation: <select id=\"customerSelect\">
		<option value=\"-1\"> - aucun - </option>\n") ;
	while ($row_customer = mysqli_fetch_array($result_customer)) {
			print("<option value=\"$row_customer[f_id]\">$row_customer[f_id]: " . db2web("$row_customer[p_lname] $row_customer[p_fname]") . "</option>\n") ;
	}
	mysqli_free_result($result_customer) ;
	print("</select><br/>\n") ;
}
?>
Instructeur: <select id="instructorSelect"> </select><br/>
Pilotes RAPCS: <input type="checkbox" id="crewWantedInput" value="true"> bienvenus en tant que co-pilotes.<br/>
Membres RAPCS: <input type="checkbox" id="paxWantedInput" value="true"> bienvenus en tant que passagers.<br/>
</span> <!-- planeSelectSpan -->
Début: <select id="startDaySelect"><?=$all_day_options?></select> -
<select id="startMonthSelect"><?=$all_month_options?></select> -
<select id="startYearSelect"><?=$all_year_options?></select>&nbsp;&nbsp;&nbsp;&nbsp;
<select id="startHourSelect"></select> : 
<select id="startMinuteSelect"><?=$all_minute_options?></select>
<br/>
Fin: <select id="endDaySelect"><?=$all_day_options?></select> -
<select id="endMonthSelect"><?=$all_month_options?></select> -
<select id="endYearSelect"><?=$all_year_options?></select>&nbsp;&nbsp;&nbsp;&nbsp;
<select id="endHourSelect"></select> : 
<select id="endMinuteSelect"><?=$all_minute_options?></select>
<br/>
<span style="vertical-align: top;">
Commentaire: <textarea class="form-control" id="commentTextArea" rows=4 cols=40></textarea>
</span>
<br/>
<span id="flightInfo2Span">
Lieu de départ: <input type="text" id="departingAirport" size="6" maxlength="4" onKeyUp="airportChanged(this);" value="EBSP">
d'arrivée: <input type="text" id="destinationAirport" size="6" maxlength="4" onKeyUp="airportChanged(this);"> <i>(codes OACI)</i>
<br/>
Via: <input type="text" id="via1Airport" size="6" maxlength="4" onKeyUp="airportChanged(this);">
et: <input type="text" id="via2Airport" size="6" maxlength="4" onKeyUp="airportChanged(this);"> <i>(codes OACI)</i>
<br/>

      </div><!-- class=modal-body-->
      <div class="modal-footer">
	  <?php
if ($userIsMechanic || $userIsInstructor) {
	print('<button type="button" class="btn btn-secondary" id="addMaintenanceButton" onclick="javascript:confirmBooking(false);" data-bs-dismiss="modal">Immobiliser pour maintenance</button><br/>' . "\n") ;
	print('<button type="button" class="btn btn-secondary" id="cancelMaintenanceButton" onclick="javascript:cancelBooking(false);" data-bs-dismiss="modal">Annuler la maintenance</button><br/>' . "\n") ;
}
if (! $userNoFlight && ($userIsPilot || $userIsMechanic || $userIsInstructor || $userIsAdmin)) {
	print('<button type="button" class="btn btn-primary" id="addBookingButton" onclick="javascript:confirmBooking(true);" data-bs-dismiss="modal">Je respecte les conditions et réserve</button>' . "\n") ;
	print('<button type="button" class="btn btn-primary" id="modifyBookingButton" onclick="javascript:modifyBooking(true);" data-bs-dismiss="modal">Modifier la réservation</button>' . "\n") ;
}
if ($userIsPilot || $userIsMechanic || $userIsInstructor || $userIsAdmin) {
	print('<button type="button" class="btn btn-danger" id="cancelBookingButton" onclick="javascript:confirmCancelBooking();" data-bs-dismiss="modal">Annuler la réservation</button>' . "\n") ;
	print('<button type="button" class="btn btn-primary" id="engineHoursButton" onclick="javascript:engineHoursClicked();" data-bs-dismiss="modal">Encoder les heures moteur</button>' . "\n") ;
}
?>
	    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div> 
<!-- end of div for the plane/ressource booking Modal window-->

<!-- div to display the cancel booking Modal window -->
<div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-labelledby="cancelBookingTitle" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="cancelBookingTitle">Confirmer l'annulation d'une réservation</h1>
        <button type="button" class="btn-close" onclick="javascript:hideCancelBookingDetails();" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
<span style="vertical-align: top;">
Raison de l'annulation (obligatoire):<br/>
<textarea id="reasonTextArea" class="form-control" rows="4" cols="40" oninput="javascript:cancelReasonChanged();" onchange="javascript:cancelReasonChanged();"></textarea>
</span>
<br/>
<div id="cancelBookingDivLog"></div>
</div>
<div class="modal-footer">
<?php
if ($userIsMechanic || $userIsInstructor || $userIsAdmin)
	print('<button id="cancelMaintenanceButton" class="btn btn-danger" onclick="javascript:document.getElementById(\'reasonTextArea\').value=\'Mise en maintenance\';cancelBooking(true);">Annuler pour mise en maintenance</button><br/>') ;
if ($userIsInstructor) {
	print('<button id="cancelStudentButton" class="btn btn-danger" onclick="javascript:document.getElementById(\'reasonTextArea\').value=\'Elève indisponible\';cancelBooking(true);">Elève indisponible</button><br/>') ;
	print('<button id="cancelSchedulingButton" class="btn btn-danger" onclick="javascript:document.getElementById(\'reasonTextArea\').value=\'Scheduling\';cancelBooking(true);">Scheduling</button><br/>') ;
}
?>
<button id="cancelPassengerButton" class="btn btn-danger" onclick="javascript:document.getElementById('reasonTextArea').value='Passager indisponible';cancelBooking(true);">Passager indisponible</button>
<button id="cancelInstructorButton" class="btn btn-danger" onclick="javascript:document.getElementById('reasonTextArea').value='Instructeur indisponible';cancelBooking(true);">Instructeur indisponible</button>
<button id="cancelPilotHealthButton" class="btn btn-danger" onclick="javascript:document.getElementById('reasonTextArea').value='Santé pilote';cancelBooking(true);">Santé pilote</button>
<button id="cancelWeatherButton" class="btn btn-danger" onclick="javascript:document.getElementById('reasonTextArea').value='Météo';cancelBooking(true);">Conditions météo</button>
<button id="cancelADClosedButton" class="btn btn-danger" onclick="javascript:document.getElementById('reasonTextArea').value='Aéroport fermé';cancelBooking(true);">Aéroport fermé</button><br/>
<button id="confirmCancelBookingButton" class="btn btn-danger" onclick="javascript:cancelBooking(true);">Confirmer l'annulation</button>
<button class="btn btn-secondary" class="btn btn-danger" onclick="javascript:hideCancelBookingDetails();">Fermer</button>
</div>
    </div>
  </div>
</div>
<!-- end of div for the cancel booking Modal window-->

<!-- div to display the FI agenda item modal (create/modify/cancel) -->
<div class="modal fade" id="agendaItemModal" tabindex="-1" role="dialog" aria-labelledby="agendaItemTitle">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="agendaItemTitle">Disponibiliés</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Fermer">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
Instructeur: <select id="agendaItemInstructorSelect"></select><br/>
Début: <input type='date' id="agendaItemDateStart"> <select id="agendaItemStartHourSelect"><?=$all_hour_options?></select> : <select id="agendaItemStartMinuteSelect"><?=$all_minute_options?></select><br/>
Fin: <input type='date' id="agendaItemDateEnd"> <select id="agendaItemEndHourSelect"><?=$all_hour_options?></select> : <select id="agendaItemEndMinuteSelect"><?=$all_minute_options?></select><br/>
<input type="radio" id="agendaItemAvailability" name="agendaItemAvailability" value="available" onchange="agendaItemChanged(false);" checked> Disponible <input type="radio" name="agendaItemAvailability" value="unavailable" onchange="agendaItemChanged(true);" > Indisponible<br/>
<input type="checkbox" id="agendaItemOnSite"> Sur site<br/>
<input type="checkbox" id="agendaItemEmail"> Contact par e-mail<br/>
<input type="checkbox" id="agendaItemPhone"> Contact par téléphone<br/>
<input type="checkbox" id="agendaItemSMS"> Contact par SMS<br/>
<input type="checkbox" id="agendaItemStudentOnly"> Uniquement pour élèves<br/>
<span style="vertical-align: top;">
Commentaire: <textarea id="agendaItemCommentTextArea" rows=4 cols=40></textarea>
	  </div><!-- class=modal-body-->
      <div class="modal-footer">
<?php
if ($userIsInstructor || $userIsAdmin) {
	print('<button class="btn btn-primary" id="addAgendaItemButton" onclick="javascript:confirmAgendaItem();">Ajouter</button>' . "\n") ;
	print('<button class="btn btn-danger"  id="cancelAgendaItemButton" onclick="javascript:cancelAgendaItem();">Annuler la disponibilité</button>' . "\n") ;
	print('<button class="btn btn-primary" id="modifyAgendaItemButton" onclick="javascript:modifyAgendaItem();">Modifier</button>' . "\n") ;
}
?>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
		</div><!-- modal footer-->
	</div><!-- model content -->
  </div><!-- modal-diaglog--> 
</div><!-- modal -->
<!-- end of modal for FI agenda item --> 

<?php
$version_php = date ("Y-m-d H:i:s.", filemtime('mobile_reservation.php')) ;
$version_js = date ("Y-m-d H:i:s.", filemtime('js/mobile_reservation.js')) ;
$version_css = date ("Y-m-d H:i:s.", filemtime('css/mobile_reservation.css')) ;
$execution_time = round(microtime(TRUE) - $microtime_start, 3) ;
?>
<div class="copyright">Réalisation: Eric Vyncke, 2014-2025 et Patrick Reginster 2020-2022, pour RAPCS, Royal Aéro Para Club de Spa, ASBL<br/>
Open Source code: <a href="https://github.com/evyncke/reserve_fly">on github</a><br/>
Versions: PHP=<?=$version_php?>, JS=<?=$version_js?>, CSS=<?=$version_css?>, exécuté en <?=$execution_time?> sec</div>
<br/>
<div id="waitingDiv">Connecting to the server, please wait...<img src="images/spinner.gif" id="waitingImage" alt="Waiting..."  onStalled="imgStalled();" width="256px" height="256px"></div>

</div><!-- container -->
</body>
</html>