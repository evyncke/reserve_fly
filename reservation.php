<?php
// Some icons (fast forward & co) by Snowish Icon Pack by Alexander Moore 
/*
   Copyright 2014-2024 Eric Vyncke

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
// - add warning when javascript is not enabled

ob_start("ob_gzhandler");

# HTTP/2 push of CSS via header()
header('Link: </resa/reservation.css>;rel=preload;as=style, </resa/datepickr.css>;rel=preload;as=style,</resa/reservation.js>;rel=preload;as=script,</resa/datepickr.js>;rel=preload;as=script') ;
header('Link: </resa/gtk_media_play_rtl.png>;rel=preload;as=image, </resa/spinner.gif>;rel=preload;as=image,</resa/fa.ico>;rel=preload;as=image,</resa/members.js>;rel=preload;as=script') ;

$microtime_start = microtime(TRUE) ; // Get start time in floating seconds
require_once "dbi.php" ;

MustBeLoggedIn() ;

require_once 'facebook.php' ;
$month_names = array('N/A', 'Jan', 'F&eacute;v', 'Mars', 'Avril', 'Mai', 'Juin', 'Juil', 'Ao&ucirc;t', 'Sept', 'Oct', 'Nov', 'D&eacute;c') ;

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
		if ($row['mandatory'] != 0) {
			$userRatingValid = false ;
			$validity_msg .= "<span class=\"validityExpired\">Votre profil ne contient pas $row[name]. Impossible de r&eacute;server un avion. Veuillez modifier votre profil d'abord.</span><br/>" ;
		}
	} elseif ($row['delta'] > 0) {
		if ($row['mandatory'] != 0) {
			$userRatingValid = false ;
			$validity_msg .= "<span class=\"validityExpired\">Votre $row[name] n'est plus valable depuis le $row[expire_date]. Impossible de r&eacute;server un avion.</span><br/>" ;
		} else {
			$validity_msg .= "<span class=\"validityWarning\">Votre $row[name] n'est plus valable depuis le $row[expire_date].</span><br/>" ;
		}
	} elseif ($row['delta'] > - $validity_warning) 
		$validity_msg .= "<span class=\"validityWarning\">Votre $row[name] ne sera plus valable le $row[expire_date]; il vous sera alors impossible de r&eacute;server un avion.</span><br/>" ;
}

// Enabling below DOCTYPE has a major impact on the rendering... larger fonts, JS COM requires 'px' units for positioning, ... 
// Possibly because it forces HTML 5 ?
?><!DOCTYPE html>
<html lang="fr">
<head>
<?
print("\n<!--- PROFILE " .  date('H:i:s') . "-->\n") ; 
?>
<link rel="stylesheet" type="text/css" href="reservation.css">
<!--- the script below has been modified to explicitely call jumpPlanningDate() when a date is selected -->
<link rel="stylesheet" type="text/css" href="datepickr.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

<!-- Facebook Open graph data -->
<meta property="og:url"           content="https://www.spa-aviation.be/resa/reservation.php" />
<meta property="og:type"          content="website" />
<meta property="og:title"         content="Royal Aero Para Club de Spa ASBL" />
<meta property="og:description"   content="Page réservée aux members pour la réservation de nos avions" />
<meta property="og:image"         content="https://www.spa-aviation.be/logo_rapcs_256x256.png" />
<meta property="og:image:width"	  content="256" />
<meta property="og:image:height"  content="256" />
<meta property="og:image:type"    content="image/png" />
<meta property="fb:app_id"        content="<?=$fb_app_id?>" />

<title>Réservation des avions</title>
<script data-cfasync="true" src="datepickr.js"></script>
<script>
var // was 'const' but IE does not support it !
	// preset Javascript constant fill with the right data from db.php PHP variables
	userFullName = '<?=$userFullName?>' ,
	userName = '<?=$userName?>' ,
	userId = <?=$userId?> ,
	userIsPilot = <?=($userIsPilot)? 'true' : 'false'?> ,
	userIsAdmin = <?=($userIsAdmin)? 'true' : 'false'?> ,
	userIsInstructor = <?=($userIsInstructor)? 'true' : 'false'?> ,
	userIsMechanic = <?=($userIsMechanic)? 'true' : 'false'?> ,
	userIsStudent = <?=($userIsStudent)? 'true' : 'false'?> ,
	userIsNoFlight = <?=($userIsNoFlight)? 'true' : 'false'?> ,
	bookingTypePilot = <?= BOOKING_PILOT?> ,
	bookingTypeInstructor = <?= BOOKING_INSTRUCTOR?> ,
	bookingTypeAdmin = <?= BOOKING_ADMIN?> ,
	bookingTypeMaintenance = <?= BOOKING_MAINTENANCE ?> ,
	bookingTypeCustomer = <?= BOOKING_CUSTOMER ?> ,
	bookingTypeOnHold = <?= BOOKING_ON_HOLD ?> ,
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
<? print("// PROFILE " . date('H:i:s') . "\n") ; ?>
	allPlanes = [
<?
ob_flush() ; // Attempt to push as much as HTML to the browser

// Get the engine time from the mechanics  
$result = mysqli_query($mysqli_link, "SELECT upper(id) as id, classe, compteur, compteur_vol, compteur_vol_valeur, compteur_date, entretien, photo, sous_controle, delai_reservation, commentaire, actif, compteur_vol
	FROM $table_planes
	WHERE actif > 0 AND ressource = 0
	ORDER BY model ASC, id ASC") or die("Cannot get all active planes:".mysqli_error($mysqli_link)) 
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
// Old code: only looking in the actual 'carnet de route' entries
//	$result3 = mysqli_query($mysqli_link, "select l_end, datediff(sysdate(), l_end) as temps_dernier 
//		from $table_logbook l join $table_bookings r on l_booking = r_id
//		where r_plane = '$row[id]' and (r_pilot = $userId or (r_instructor is not null and r_instructor = $userId)) and l_booking is not null
//		order by l_end desc") or die("Cannot get last reservation: " . mysqli_error($mysqli_link)) ;

	// New code: look at pilot log book whether 'carnet de route' or not :-(
	$index_column = ($row['compteur_vol'] == 0) ? 'l_end_hour' : 'l_flight_end_hour' ;
	$result3 = mysqli_query($mysqli_link, "select $index_column, datediff(sysdate(), l_end) as temps_dernier 
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
	print("{ \"id\": \"$row[id]\", \"compteur\": $row[compteur], \"compteur_date\": new Date(\"" . str_replace('-', '/', $row['compteur_date']) . "\"), " .
		" \"compteur_pilote\": $row2[compteur_pilote], \"compteur_pilote_date\": new Date(\"" . str_replace('-', '/', $row2['compteur_pilote_date']) . "\"), " .
		" \"compteur_pilote_nom\": \"$row2[compteur_pilote_nom]\", " .
		" \"entretien\": $row[entretien], \"photo\": \"$row[photo]\", \"commentaire\": \"" . str_replace(array("\r\n", "\r", "\n"), '<br />', $row['commentaire']) . "\", " .
		" \"reservation_permise\": " . (($reservation_permise) ? 'true' : 'false') . ", " .
		" \"qualifications_requises\": " . (($qualifications_validated) ? 'true' : 'false') . ", " .
		" \"dernier_vol\": \"$l_end\", \"actif\": $row[actif], \"ressource\": 0, " .  
		" \"classe\": \"$row[classe]\", \"sous_controle\": " . (($row['sous_controle'] == 0) ? 'false' : 'true') . "}") ;
}
ob_flush() ;
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
<? print("// PROFILE " . date('H:i:s') . "\n") ; ?>
var
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

function imgStalled() {
	console.log('Image load is stalled') ;
}
</script>
<script src="planes.js"></script> <!--- cannot be loaded before as its initialization code use variable above... -->
<script src="ressources.js"></script> <!--- cannot be loaded before as its initialization code use variable above... -->
<script src="pilots.js"></script> <!--- cannot be loaded before as its initialization code use variable above... -->
<script src="members.js"></script> <!--- cannot be loaded before as its initialization code use variable above... -->
<script src="instructors.js"></script> <!--- cannot be loaded before as its initialization code use variable above... -->
<script src="reservation.js"></script> <!--- cannot be loaded before as its initialization code use variable above... -->
<!-- Matomo -->
<script type="text/javascript">
  var _paq = window._paq = window._paq || [];
  /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
  _paq.push(['setUserId', '<?=$userName?>']);
  _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
  _paq.push(["setCookieDomain", "*.spa-aviation.be"]);
  _paq.push(["setDomains", ["*.spa-aviation.be","*.ebsp.be","*.m.ebsp.be","*.m.spa-aviation.be","*.resa.spa-aviation.be"]]);
  _paq.push(['enableHeartBeatTimer']);
  _paq.push(['setCustomVariable', 1, "userID", <?=$userId?>, "visit"]);
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

<h2>Réservation des avions</h2>
<div id="logDiv" style="visibility: collapse; background-color: yellow;"></div>
<span id="noScript" style="color: red; font-size: x-large;">Pour avoir acc&egrave;s &agrave; la r&eacute;servation, <b>javascript</b> doit &ecirc;tre activ&eacute;. 
Ce n'est pas le cas avec votre navigateur Internet.</span>
<script>
	document.getElementById('noScript').innerHTML = '' ;
	document.getElementById('noScript').style.visibility = 'hidden' ;
</script>
<div class="userPrivileges">
<?php
if ($userId != 0) {
	$result = mysqli_query($mysqli_link, "select * from $table_users u left join $table_person p on u.id = p.jom_id where u.id = $userId")
		or die("Erreur systeme lors de la lecture de votre profil: " . mysqli_error($mysqli_link)) ;
	$row = mysqli_fetch_array($result) ;
}
?>
Vos droits d'acc&egrave;s (<?=$userFullName?>) sur cette page: 
<?php
if ($userIsStudent) print(" &eacute;l&egrave;ve ") ;
if ($userIsPilot) print(" pilote ") ;
if ($userIsMechanic) print(" mecano ") ;
if ($userIsInstructor) print(" instructor ") ;
if ($userIsAdmin) print(" gestionnaire-système ") ;
if ($userIsBoardMember) print(" administrateur-CA ") ;
if ($userNoFlight) print(" <span style=\"color: red;\">interdit de vol</span> ") ;
if (! ($userIsPilot || $userIsAdmin || $userIsInstructor || $userIsMechanic))
	print("<br/><font color=red>Vous devez &ecirc;tre au moins pilote pour r&eacute;server un avion.</font>") ;
// Check whether the user is blocked
$result_blocked = mysqli_query($mysqli_link, "SELECT * FROM $table_blocked WHERE b_jom_id=$userId")
	or journalise($userId, 'E', "Cannot checked whether user is blocked: " . mysqli_error($mysqli_link)) ;
$row_blocked = mysqli_fetch_array($result_blocked) ;
if ($row_blocked) {
	journalise($userId, "W", "This user is blocked: " . db2web($row_blocked['b_reason'])) ;
	$userNoFlight = true ;
	print("<div class=\"noFlyBox\">Vous &ecirc;tes interdit(e) de vol: <b>" . db2web($row_blocked['b_reason']) . "</b>. 
		Contactez <a href=\"mailto:info@spa-aviation.be\">l'a&eacute;roclub info@spa-aviation.be</a>.
		Un clic sur le bouton <i>Folio du mois</i> ci-dessous vous permet de visualiser votre situation comptable.</div>") ;
}
// Check whether users have paid theirs membership fees
$result_fee = mysqli_query($mysqli_link, "SELECT * FROM $table_membership_fees 
	WHERE bkf_user=$userId AND bkf_payment_date IS NOT NULL AND bkf_year = '" . date('Y') . "'")
	or journalise($userId, 'E', "Cannot checked whether user has paid membership fee: " . mysqli_error($mysqli_link)) ;
$row_fee = mysqli_fetch_array($result_fee) ;
if (! $row_fee and ! $userIsInstructor and $userId != 294) {
	journalise($userId, "W", "This user has yet to pay their membership fee") ;
//	$userNoFlight = false;
	print("<div class=\"noFlyBox\">Vous n'êtes pas en ordre de cotisation (nécessaire pour payer les assurances pilotes).
		Un clic sur le bouton <i>Folio du mois</i> ci-dessous vous permet de visualiser votre situation comptable.</div>") ;
}
if ($userNoFlight)
	print("<div class=\"noFlyBox\">Vous &ecirc;tes interdit(e) de vol (par exemple: factures non pay&eacute;es, 
		contactez <a href=\"mailto:info@spa-aviation.be\">info@spa-aviation.be</a>.
		Un clic sur le bouton <i>Folio du mois</i> ci-dessus vous permet de visualiser votre situation comptable.</div>") ;
if ($userId == 0) {
	print("<br/><font color=red>Vous devez &ecirc;tre connect&eacute;(e) pour r&eacute;server un avion.</font> ") ;
} else {
	// Check for profile settings
	$profile_count = 0 ;
	if ($row['email'] != '') $profile_count ++ ;
	if ($row['first_name'] != '') $profile_count ++ ;
	if ($row['last_name'] != '') $profile_count ++ ;
	if ($row['home_phone'] != '') $profile_count ++ ;
	if ($row['work_phone'] != '') $profile_count ++ ;
	if ($row['cell_phone'] != '') $profile_count ++ ;
	if ($row['city'] != '') $profile_count ++ ;
	if ($row['country'] != '') $profile_count ++ ;
	if ($row['sex'] != '' and $row['sex'] != 0) $profile_count ++ ;
	if ($row['birthdate'] != '') $profile_count ++ ;
	if ($profile_count != 10) print("<div class=\"validityBox\">Votre profil est compl&eacute;t&eacute; &agrave; " . round(100 * $profile_count / 10) . "% seulement, veuillez cliquer sur le bouton 'Mon Profil'.</div>") ;
	if ($row['cell_phone'] == '') {
		print("<div class=\"validityBox\">Il manque votre num&eacute;ro de GSM/mobile, impossible de r&eacute;server. Veuillez cliquer sur le bouton 'Mon Profil'.</div>") ;
		$userNoFlight = true ;
	}
	print('<input type="button" style="background-color: green; color: white;" value="Mon profil" onclick="javascript:document.location.href=\'mobile_profile.php\';"> ') ;
	print('<input type="button" style="background-color: green; color: white;"value="Mon carnet de vol" onclick="javascript:document.location.href=\'mobile_mylog.php\';"> ') ;
	print('<input type="button" value="Carte de mes vols" onclick="javascript:document.location.href=\'mymap.php\';"> ') ;
	print('<input type="button" style="background-color: green; color: white;" value="Site mobile" onclick="javascript:document.location.href=\'mobile.php?news\';"> ') ;
	print('<input type="button" style="background-color: green; color: white;" value="Folio du mois" onclick="javascript:document.location.href=\'mobile_folio.php\';"> ') ;
	if ($userIsAdmin) print('<input style="background-color: green; color: white;" type="button" value="Journal des opérations" onclick="javascript:document.location.href=\'mobile_journal.php\';"> ') ;
	if ($userIsAdmin || $userIsMechanic) print('<input type="button" value="Echéances des maintenances" style="background-color: green; color: white;" onclick="javascript:document.location.href=\'plane_planning.php\';"> ') ;
	print('<input type="button" value="No log" style="background-color: yellow; visibility: hidden;" id="logButton" onclick="javascript:toggleLogDisplay();"> ') ;
	print("<a href=\"webcal://$_SERVER[SERVER_NAME]/resa/ics.php?user=$userId&auth=" . md5($userId . $shared_secret) . "\">lier &agrave; mon calendrier (iCal)</a>") ;
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
		print("<p style=\"color: red;\">Vous avez une ou plusieurs r&eacute;servations sans entr&eacute;es dans les carnets de routes des avions, il est obligatoire de compl&eacute;ter
			ces carnets sous peine de frais administratifs en fin de mois.</p><ul>") ;
		while ($row = mysqli_fetch_array($result)) {
			print("<li>$row[r_start]: <a href=\"IntroCarnetVol.php?id=$row[r_id]\">remplir le carnet de routes de $row[r_plane] ou annuler la r&eacute;servation</a>;") ;
			if ($userIsInstructor) print(" <img src=\"gtk-delete.png\" onclick=\"javascript:document.getElementById('reasonTextArea').value='Old booking';cancelOldBooking($row[r_id]);\">" ) ;
			print("</li>\n") ;
		}
		print("</ul></p>\n") ;
	}
	} // Not $isInstructor
print("\n<!--- PROFILE " .  date('H:i:s') . "-->\n") ; 
} // ($userId == 0)
// Facebook log-in
?>
<!--div class="fb-login-button" data-max-rows="1" data-size="small" data-show-faces="true" data-button-type="continue_with" data-auto-logout-link="false" data-use-continue-as="true" scope="public_profile" onlogin="checkLoginState();"></div--> <span id="helloFBUser"></span>



<script>
	var userRatingValid = <?=($userRatingValid) ? 'true' : 'false' ?> ; // Was 'const' but IE does not support it
</script>
<!--- a href="<?=$_SERVER['PHP_SELF']?>">Passer en mode plein &eacute;cran.</a-->
</div>
<table border="0" width="100%">
	<tr style="vertical-align: top;">
		<td style="width: 370;">
			<table id="ephemeridesTable"  class="hidden-phone">
			<tr><td class="ephemeridesCell">Jour a&eacute;ronautique:</td><td class="ephemeridesCell"></td>
				<td class="ephemeridesCell">Coucher du soleil:</td><td class="ephemeridesCell"></td></tr>
			<tr><td class="ephemeridesCell">Lever du soleil:</td><td class="ephemeridesCell"></td>
				<td class="ephemeridesCell">Nuit a&eacute;ronautique:</td><td class="ephemeridesCell"></td></tr>
			<tr><td class="ephemeridesCell">Ouverture a&eacute;roport:</td><td class="ephemeridesCell"></td>
				<td class="ephemeridesCell">Fermeture a&eacute;roport:</td><td class="ephemeridesCell"></td></tr>
			<tr><td class="ephemeridesCell" colspan="4"><i><b>En heure locale de <?=$default_airport?> et pour info seulement.</b><br/>
					Heure locale &agrave; <?=$default_airport?>:  <span id="hhmmLocal"></span><br/>
					Heure universelle:  <span id="hhmmUTC"></span>Z</i></td></tr>
			</table>	
		</td>
<?php
$result_news = mysqli_query($mysqli_link, "SELECT * FROM $table_news
	WHERE n_stop >= CURRENT_DATE() and n_start <= CURRENT_DATE()
	ORDER BY n_id desc
	LIMIT 0,5") or die("Cannot fetch news: " . mysqli_error($mysqli_link)) ;

if (mysqli_num_rows($result_news) or $userIsAdmin) {
	print('<td class="hidden-phone" style="width: 25%;"><div id="newsDiv"><ul>') ;
	while ($row_news = mysqli_fetch_array($result_news)) {
		$subject = db2web($row_news['n_subject']) ;
		$text = db2web(nl2br($row_news['n_text'])) ;
		$delete_action = ($userIsAdmin) ? ' <a href="news_delete.php?id=' . $row_news['n_id'] . '"><img src="gtk-delete.png" alt="X" width="10" height="10"></a>' : '' ;
		print("<li><b>$subject</b>: $text$delete_action</li>\n") ;
	}
	if ($userIsAdmin) print('<li><a href="news_add.php">Ajouter une nouvelle</a></li>') ;
	print('</ul></div></td>') ;
}
mysqli_free_result($result_news) ;
?>
		<td id="reservationDetails" style="width: 30%;"></td>
		<td id="webcamCell" class="hidden-phone">
			<a href="" id="webcamURI" border="0"><img id="webcamImg" style="width: 256px; height: 192px;" alt="Webcam" onStalled="imgStalled();"></a>
		</td>
	</tr>
</table>
<br/>
<!--div style="color: red; font-size: large">Il y a probl&egrave;me technique chez notre h&eacute;bergeur (OVH) &agrave; propos des noms des FI et des pilotes. 
Donc, pas possible de faire de nouvelles r&eacute;servations en choissisant un instructeur ou un &eacute;l&egrave;ve mais pas de soucis pour un vol solo. 
D&egrave;s que le probl&eacute;me chez OVH est r&eacute;solu, tout refonctionnera.</div-->
<table class="planningRuler">
<tr stylex="vertical-align: top; background: white;">
	<td class="planningRulerCell"><a href="javascript:bumpPlanningBy(-7);">
		<img border="0" width="32" height="32" src="gtk_media_forward_rtl.png" alt="&lt;&lt;&lt;"  onStalled="imgStalled();"></a></td>
	<td class="planningRulerCell"><a href="javascript:bumpPlanningBy(-1);">
		<img border="0" width="32" height="32" src="gtk_media_play_rtl.png" alt="&lt;" onStalled="imgStalled();"></a></td>
	<td class="planningRulerCellLarge"><span id="planningDayOfWeek"></span><input type="tex" size="10" maxlength="10" id="planningDate" onchange="jumpPlanningDate();"></td>
	<td class="planningRulerCellCalendar"><img src="calendar.png" id="calendarIcon" alt="Calendar"  onStalled="imgStalled();"></td>
	<td class="planningRulerCell"><a href="javascript:bumpPlanningBy(+1);">
		<img border="0" width="32" height="32" src="gtk_media_play_ltr.png" alt="&gt;"  onStalled="imgStalled();"></a></td>
	<td class="planningRulerCell"><a href="javascript:bumpPlanningBy(+7);">
		<img border="0" width="32" height="32" src="gtk_media_forward_ltr.png" alt="&gt;&gt;&gt;"  onStalled="imgStalled();"></a></td>
</tr>
</table>
<table id="planePlanningTable" class="planningTable" border="0">
</table>
<span id="toggleInstructorAgendaSpan" class="toggleInstructorAgendaSpan" onClick="toggleInstructorAgenda();">+ Disponibilit&eacute; des instructeurs (MODE TEST)</span><br/>
<table id="instructorPlanningTable" class="planningTable" border="0">
</table>
<span class="planningLegend">
Indications pour un avion que nous n'&ecirc;tes probablement pas en droit de r&eacute;server (sauf avec un instructeur) car:<br/>
<img src="exclamation-icon.png" width="12" height="12" alt="!"  onStalled="imgStalled();">: vous n'avez pas vol&eacute; dessus r&eacute;cemment (sur
base de l'entr&eacute;e des heures de vol dans votre carnet de vol).<br/>
<img src="forbidden-icon.png" width="12" height="12" alt="X"  onStalled="imgStalled();">: vous n'avez pas les qualifications requises (sur base des validit&eacute;s de votre profil).<br/>
V&eacute;rifiez les r&egrave;gles de r&eacute;servation et si vous les respectez: r&eacute;servez :-)<br/>
<img src="fa.ico" border="0" width="12" height="12">: ouvre Flight Aware avec le dernier vol de cet avion.<br/>
</span>
<center><input type="button" id="roadBookButton" value="Carnet de route" onclick="roadBookClick();" disabled="true" style="display: none;"></center>
<p>
<div id="pilotDetailsDiv"><img id="pilotDetailsImage"><span id="pilotDetailsSpan"></span><hr><center><button onclick="hidePilotDetails();">OK</button></center></div>
<!-- div to display the plane/ressource booking confirmation message-->
<div id="bookingMessageDiv"><span id="bookingMessage"></span><hr><center><button onclick="hideBookingMessage();">OK</button></center></div>
<!-- div to display the plane/ressource booking window (create, modify, cancel) -->
<div id="bookingDiv">
<center><h3 id="bookingTitle">Effectuer une r&eacute;servation</h3></center>
<span id="ressourceSelectSpan">
Ressource: <select id="ressourceSelect" onchange="ressourceHasChanged(this);"></select><br/>
Réservation pour: <select id="memberSelect"></select><br/>
</span><!-- ressourceSelectSpan -->
<span id="planeSelectSpan">
Avion: <select id="planeSelect" onchange="ressourceHasChanged(this);"></select>
<span id="planeComment"></span><span id="pilotType"><br/>
<?php
if ($userIsAdmin || $userIsInstructor)
	print("Pilote/&eacute;l&egrave;ve: ") ;
else if ($userIsMechanic)
	print("M&eacute;cano: ") ;
else
	print("Pilote: ") ;
?></span><select id="pilotSelect"> </select><br/>
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
D&eacute;but: <select id="startDaySelect"><?=$all_day_options?></select> -
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
<span id="flightInfo1Span">
Temps de vol pr&eacute;vu: <input type="number" id="flightDuration" size="4" maxlength="4" min="0" max="100" oninput="javascript:durationChanged();" onchange="javascript:durationChanged();"> heure(s) <i>(arrondir &agrave; l'heure sup&eacute;rieure)</i>
<br/>
</span><!-- flightInfo1Span -->
<span style="vertical-align: top;">
Commentaire: <textarea id="commentTextArea" rows=4 cols=40></textarea>
</span>
<br/>
<span id="flightInfo2Span">
Lieu de d&eacute;part: <input type=text id="departingAirport" size="6" maxlength="4" onKeyUp="airportChanged(this);" value="EBSP">
d'arriv&eacute;e: <input type=text id="destinationAirport" size="6" maxlength="4" onKeyUp="airportChanged(this);"> <i>(codes OACI)</i>
<br/>
Via: <input type=text id="via1Airport" size="6" maxlength="4" onKeyUp="airportChanged(this);">
et: <input type=text id="via2Airport" size="6" maxlength="4" onKeyUp="airportChanged(this);"> <i>(codes OACI)</i>
<br/>
<hr>
<span style="color: blue;">N'oubliez pas de vérifier la validité de votre qualification SEP, certificat médical, de votre ELP, des
règles d'emport de passagers et les autres règles du club définies dans
<a href="https://www.spa-aviation.be/index.php/fr/avions/regles-pour-la-reservation">règles pour réservation</a>.
</span>
</span><!-- flightInfo2Span -->
<center>
<?php
if ($userIsMechanic || $userIsInstructor) {
	print('<button id="addMaintenanceButton" onclick="javascript:confirmBooking(false);">Immobiliser pour maintenance</button><br/>' . "\n") ;
	print('<button id="cancelMaintenanceButton" onclick="javascript:cancelBooking(false);">Annuler la maintenance</button><br/>' . "\n") ;
}
if (! $userNoFlight && ($userIsPilot || $userIsMechanic || $userIsInstructor || $userIsAdmin)) {
	print('<button id="addBookingButton" onclick="javascript:confirmBooking(true);">Je respecte les conditions et r&eacute;serve</button>' . "\n") ;
	print('<button id="modifyBookingButton" onclick="javascript:modifyBooking(true);">Modifier la r&eacute;servation</button>' . "\n") ;
}
if ($userIsPilot || $userIsMechanic || $userIsInstructor || $userIsAdmin) {
	print('<button id="cancelBookingButton" onclick="javascript:confirmCancelBooking();">Annuler la r&eacute;servation</button>' . "\n") ;
	print('<button id="engineHoursButton" onclick="javascript:engineHoursClicked();">Encoder les heures moteur</button>' . "\n") ;
}
?>
<button onclick="javascript:hideEditBookingDetails();">Fermer la fen&ecirc;tre</button>
</center>
</div>
<!-- end of div for the plane/ressource booking window-->
<!-- div to display the cancel booking window -->
<div id="cancelBookingDiv">
<center><h3 id="cancelBookingTitle">Confirmer l'annulation d'une réservation</h3></center>
<span style="vertical-align: top;">
Raison de l'annulation (obligatoire):<br/>
<textarea id="reasonTextArea" rows=4 cols=40 oninput="javascript:cancelReasonChanged();" onchange="javascript:cancelReasonChanged();"></textarea>
</span>
<br/>
<center>
<?php
if ($userIsMechanic || $userIsInstructor || $userIsAdmin)
	print('<button id="cancelMaintenanceButton" onclick="javascript:document.getElementById(\'reasonTextArea\').value=\'Mise en maintenance\';cancelBooking(true);">Annuler pour mise en maintenance</button><br/>') ;
if ($userIsInstructor) {
	print('<button id="cancelStudentButton" onclick="javascript:document.getElementById(\'reasonTextArea\').value=\'Elève indisponible\';cancelBooking(true);">Elève indisponible</button><br/>') ;
	print('<button id="cancelSchedulingButton" onclick="javascript:document.getElementById(\'reasonTextArea\').value=\'Scheduling\';cancelBooking(true);">Scheduling</button><br/>') ;
}
?>
<button id="cancelPassengerButton" onclick="javascript:document.getElementById('reasonTextArea').value='Passager indisponible';cancelBooking(true);">Passager indisponible</button>
<button id="cancelInstructorButton" onclick="javascript:document.getElementById('reasonTextArea').value='Instructeur indisponible';cancelBooking(true);">Instructeur indisponible</button>
<button id="cancelPilotHealthButton" onclick="javascript:document.getElementById('reasonTextArea').value='Santé pilote';cancelBooking(true);">Santé pilote</button>
<button id="cancelWeatherButton" onclick="javascript:document.getElementById('reasonTextArea').value='Météo';cancelBooking(true);">Conditions météo</button>
<button id="cancelADClosedButton" onclick="javascript:document.getElementById('reasonTextArea').value='Aéroport fermé';cancelBooking(true);">Aéroport fermé</button><br/>
<button id="confirmCancelBookingButton" onclick="javascript:cancelBooking(true);">Confirmer l'annulation</button>
<button onclick="javascript:hideCancelBookingDetails();">Fermer la fen&ecirc;tre</button>
</center>
<div id="cancelBookingDivLog"></div>
</div>
<!-- end of div for the cancel booking window-->
<!-- div to display the agenda item window (create, modify, cancel) -->
<div id="agendaItemDiv">
<center><h3 id="agendaItemTitle">Disponibilités</h3></center>
Instructeur: <select id="agendaItemInstructorSelect"> </select><br/>
D&eacute;but: <input type='date' id="agendaItemDateStart"> <select id="agendaItemStartHourSelect"><?=$all_hour_options?></select> : <select id="agendaItemStartMinuteSelect"><?=$all_minute_options?></select><br/>
Fin: <input type='date' id="agendaItemDateEnd"> <select id="agendaItemEndHourSelect"><?=$all_hour_options?></select> : <select id="agendaItemEndMinuteSelect"><?=$all_minute_options?></select><br/>
<input type="radio" id="agendaItemAvailability" name="agendaItemAvailability" value="available" onchange="agendaItemChanged(false);" checked> Disponible <input type="radio" name="agendaItemAvailability" value="unavailable" onchange="agendaItemChanged(true);" > Indisponible<br/>
<input type="checkbox" id="agendaItemOnSite"> Sur site<br/>
<input type="checkbox" id="agendaItemEmail"> Contact par e-mail<br/>
<input type="checkbox" id="agendaItemPhone"> Contact par t&eacute;l&eacute;phone<br/>
<input type="checkbox" id="agendaItemSMS"> Contact par SMS<br/>
<input type="checkbox" id="agendaItemStudentOnly"> Uniquement pour &eacute;l&egrave;ves<br/>
<span style="vertical-align: top;">
Commentaire: <textarea id="agendaItemCommentTextArea" rows=4 cols=40></textarea>
</span><br/>
<center>
<?php
if ($userIsInstructor || $userIsAdmin) {
	print('<button id="addAgendaItemButton" onclick="javascript:confirmAgendaItem();">Ajouter</button>' . "\n") ;
	print('<button id="cancelAgendaItemButton" onclick="javascript:cancelAgendaItem();">Annuler la disponibilit&eacute;</button>' . "\n") ;
	print('<button id="modifyAgendaItemButton" onclick="javascript:modifyAgendaItem();">Modifier</button>' . "\n") ;
}
?>
<button onclick="javascript:hideEditAgendaItemDetails();">Fermer la fen&ecirc;tre</button>
</center>
</div>
<!-- end of div for the agenda item window-->
<?php
$version_php = date ("Y-m-d H:i:s.", filemtime('reservation.php')) ;
$version_js = date ("Y-m-d H:i:s.", filemtime('reservation.js')) ;
$version_css = date ("Y-m-d H:i:s.", filemtime('reservation.css')) ;
print("\n<!--- PROFILE " .  date('H:i:s') . "-->\n") ; 
$execution_time = round(microtime(TRUE) - $microtime_start, 3) ;
?>
<div class="copyright">R&eacute;alisation: Eric Vyncke, d&eacute;cembre 2014-2022 et Patrick Reginster 2020-2022, pour RAPCS, Royal A&eacute;ro Para Club de Spa, ASBL<br/>
Open Source code: <a href="https://github.com/evyncke/reserve_fly">on github</a><br/>
Versions: PHP=<?=$version_php?>, JS=<?=$version_js?>, CSS=<?=$version_css?>, ex&eacute;cut&eacute en <?=$execution_time?> sec</div>
<br/>
<div id="waitingDiv">Connecting to the server, please wait...<img src="spinner.gif" id="waitingImage" alt="Waiting..."  onStalled="imgStalled();" width="256px" height="256px"></div>
</body>
</html>
