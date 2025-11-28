<?php
/*
   Copyright 2013-2025 Eric Vyncke & Patrick Reginster

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

   TODO:
   ChatGPT: génère le code PHP et Javascript pour lier un compte Facebook à un utilisateur déjà authentifié par l'application
*/

ob_start("ob_gzhandler");

if (!isset($additional_preload))
  $additional_preload = '' ;
elseif (! str_starts_with($additional_preload, ',')) // Ensure it starts with a comma
  $additional_preload = ',' . $additional_preload ; 

if (isset($need_swiped_events) and $need_swiped_events) {
    $additional_preload .= ',</resa/js/swiped-events.js>;rel=preload;as=script' ;
}

# HTTP/2 push of some JS scripts via header()
header('Link: </resa/js/mobile.js>;rel=preload;as=script,</resa/data/members.js>;rel=preload;as=script,</resa/data/planes.js>;rel=preload;as=script,' .
  '</logo_rapcs_256x256_white.png>;rel=preload;as=image,</logo_rapcs_256x256.png>;rel=preload;as=image' . 
  $additional_preload) ;

# Handle the toggle between dark/light themes
if (isset($_GET['theme']) and $_GET['theme'] != '') {
  $theme = $_GET['theme'] ;
  journalise($userId, "D", "Switching to theme $theme") ;
} else
  $theme = (isset($_COOKIE['theme'])) ? $_COOKIE['theme'] : 'light' ;
setcookie('theme', $theme, time()+60*60*24*30, '/', $_SERVER['HTTP_HOST'], true) ;

# Check the value of user
if (isset($_REQUEST['user']) and $_REQUEST['user'] != '') {
  $_REQUEST['user'] = intval($_REQUEST['user']) ;
} 

$today_month = date('m') ;
$today_day = date('d') ;

$base_url = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/' ;

$christmas_theme = ($today_month == '12' and $today_day >= '15') or ($today_month == '01' and $today_day <= '08') ;

?><!DOCTYPE html>
<html lang="fr" data-bs-theme="<?=$theme?>">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<meta charset="utf-8">
<!-- Facebook Open graph data -->
<meta property="og:url"           content="https://www.spa-aviation.be/resa/mobile.php" />
<meta property="og:type"          content="website" />
<meta property="og:title"         content="Royal Aero Para Club de Spa ASBL" />
<meta property="og:description"   content="Page réservée aux membres RAPCS" />
<meta property="og:image"         content="https://www.spa-aviation.be/logo_rapcs_256x256.png" />
<meta property="og:image:width"	  content="256" />
<meta property="og:image:height"  content="256" />
<meta property="og:image:type"    content="image/png" />

<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- http://www.alsacreations.com/article/lire/1490-comprendre-le-viewport-dans-le-web-mobile.html -->
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<title>Mobile RAPCS ASBL</title>

<!-- Using latest bootstrap 5 -->
<!-- Latest compiled and minified CSS add media="screen" would reserve it only for screen and not printers -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Latest compiled JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Glyphicon equivalent -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<?php
if ($christmas_theme) {
?>
<!-- Christmas theme -->
<link rel="stylesheet" href="css/mobile_christmas.css">
<?php
} // $christmas theme
if (isset($need_swiped_events) and $need_swiped_events) {
?>
<!-- Allow the swipe events on phones & tablets -->
<!-- only be loaded when required -->
<script src="js/swiped-events.js" defer></script>
<script>
  var swipeMinWidth = 0 ; // Default Minimum width to activate swipe events, i.e., by default always
</script>
<?php
} // $need_swiped_events
if (isset($need_swiped_events) and is_numeric($need_swiped_events)) {
?>
<script>
  swipeMinWidth = <?=$need_swiped_events?> ; // Minimum width to activate swipe events
</script>
<?php
} /// $need_swiped_events is numeric
?>  
<script>
var
		runwaysQFU = [ <?php print(implode(', ', $runways_qfu)) ; ?> ],
		nowTimestamp = <?=time()?>,
		utcOffset = Number(<?=date('Z')/3600?>),
		userId = <?=$userId?>,
		selectedUserId = <?=(isset($_REQUEST['user']) and $_REQUEST['user'] != '') ? $_REQUEST['user'] : $userId?>,
		userName = <?=json_encode($userName)?>,
		userFullName = <?=json_encode($userFullName)?>,
		userIsPilot = <?= ($userIsPilot) ? 'true' : 'false' ?>,
		userIsInstructor = <?= ($userIsInstructor) ? 'true' : 'false' ?>,
		userIsAdmin = <?= ($userIsAdmin) ? 'true' : 'false' ?>,
		userIsBoardMember = <?= ($userIsBoardMember) ? 'true' : 'false' ?>,
		userIsMechanic = <?=($userIsMechanic)? 'true' : 'false'?>,
		userIsStudent = <?=($userIsStudent)? 'true' : 'false'?>,
		userIsNoFlight = <?=($userNoFlight)? 'true' : 'false'?> ,
		bookingTypePilot = <?= BOOKING_PILOT?>,
		bookingTypeInstructor = <?= BOOKING_INSTRUCTOR?>,
		bookingTypeAdmin = <?= BOOKING_ADMIN?>,
		bookingTypeMaintenance = <?= BOOKING_MAINTENANCE ?>,
		bookingTypeCustomer = <?= BOOKING_CUSTOMER ?> ,
		bookingTypeOnHold = <?= BOOKING_ON_HOLD ?> ;

function pilotSelectChanged() {
        window.location.href = '<?=$_SERVER['PHP_SELF']?>?user=' + document.getElementById('pilotSelect').value + 
			'<?= ((isset($_REQUEST['previous'])) ? '&previous' : '')?>' ;
}
</script>
<script src="js/mobile.js"></script>
<script src="data/planes.js"></script>
<script src="data/members.js"></script> <!-- TODO load only if pilotSelect is active -->
<?php
if (!isset($_REQUEST['kiosk'])) { // No matomo analytics in kiosk mode
?>
<!-- Matomo -->
<script type="text/javascript">
  var _paq = window._paq = window._paq || [];
<?php
// If user is logged-in then call 'setUserId'
// $userId variable must be set by the server when the user has successfully authenticated to your app.
	if (isset($userId) and $userId > 0) {
     print("  _paq.push(['setUserId', '" . json_encode($userName) . "']);\n");
}
?>
  /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
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
    _paq.push(['setSiteId', '8']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Code -->
<?php
 } // No matomo code if in kiosk mode
?>
<?php  // Output any page specific header
if (isset($header_postamble))
	print($header_postamble) ;
	
$body_attributes = (isset($body_attributes)) ? $body_attributes : 'onload="init();"' ; 
?>
</head>
<body <?=$body_attributes?>>
<?php
if (isset($_REQUEST['user']) and ($_REQUEST['user'] != '')) // Let's try to keep this value
  print("<input type=\"hidden\" name=\"user\" value\"$_REQUEST[user]\">\n") ;
// Add a black overlay box on the top to mimick a screen saving when airport is closed to save power
  if (isset($_REQUEST['kiosk'])) {
    if (time() <= airport_opening_local_time(date('Y'), date('n'), date('j')) or airport_closing_local_time(date('Y'), date('n'), date('j')) <= time()) {
      print('<div style="background: black; position: absolute; top: 0px; bottom: 0px; left: 0px; right: 0px; height: 100vh; width: 100vw; z-index: 99;"></div>') ;
      $_REQUEST['kiosk'] = 'powersave' ;
    } else 
      $_REQUEST['kiosk'] = 'display active' ;
}
?>
<div class="d-none d-print-block"><!-- Show a header on printed documents TODO use js to have the current print date and not the first display date-->
<div class="row"> 
<div class="col-sm-3">
    <img class="img-fluid" src="https://www.spa-aviation.be/logo_rapcs_256x256.png">
  </div><!-- col -->
  <div class="col-sm-9">
    <h2>Royal Aéro Para Club de Spa ASBL</h2>
    <p class="fw-light">N° entreprise: BE 0406 620 535  E-mail: info@spa-aviation.be<br/>
      Aérodrome de la Spa-La Sauvenière (ICAO: EBSP)<br/>
      Rue de la Sauvenière, 122<br/>
      B-4900 SPA</p>
    <p class="fw-light">This page was printed by <?="$userFullName ($userName)"?> on <?=date('l jS \o\f F Y')?>.</p>
    <p class="fw-light">URL: <?="https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"?></p>
  </div><!-- col -->
  <hr/>
</div><!-- row -->
</div><!-- print only -->
<?php
if ($christmas_theme) {
?>
<!-- Christmas theme to be removed outside of season of course -->
<div class="snowflakes" aria-hidden="true">
		<div class="snowflake">
			❅
		</div>
		<div class="snowflake">
			❆
		</div>
		<div class="snowflake">
			❅
		</div>
		<div class="snowflake">
			❆
		</div>
		<div class="snowflake">
			❅
		</div>
		<div class="snowflake">
			❆
		</div>
		<div class="snowflake">
			❅
		</div>
		<div class="snowflake">
			❆
		</div>
		<div class="snowflake">
			❅
		</div>
		<div class="snowflake">
			❆
		</div>
		<div class="snowflake">
			❅
		</div>
		<div class="snowflake">
			❆
		</div>
	</div>
  <?php
  if (date('i') == '00') { // Sound only one minute per hour
  ?>
  <audio autoplay>
    <!-- free sound from https://orangefreesounds.com/santa-claus-sleigh-flyby-sound/ -->
    <source src="Santa-claus-sleigh-flyby-sound.mp3" type="audio/mpeg">
  </audio>
  <?php
  } // Sound only one minute per hour
  ?>
<!-- end of Christmas theme -->
<?php
} // $christmas theme
?>
<nav class="navbar navbar-expand-lg text-bg-success d-print-none" id="navBarId"><!-- do not print the menu... 
   TODO:  add 'fixed-top' w/o destroying the layout, e.g., do not eat METAR box -->
  <div class="container-fluid">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target=".multi-collapse">
        <i class="navbar-toggler-icon"></i>
      </button>
      <!-- below in the <a the navbar-collapse widen the space by adding right margin !!! -->
      <a class="navbar-brand multi-collapse hidden-md collapse navbar-collapse" href="mobile.php?news" style="max-width: 40px;">
        <img src="/logo_rapcs_256x256_white.png" width="24px" height="24px">
      </a>
      <ul class="nav navbar-nav multi-collapse collapse navbar-collapse"><!-- nav-bar left with most of the dropdown -->
        <li class="navbar-item me-auto me-md-0">
          <a class="nav-link text-white" href="mobile.php?news">Home</a>
        </li>
<?php
if ($userIsAdmin or $userIsInstructor or $userIsBoardMember) {
?>
  <li class="nav-item dropdown me-auto me-md-0">
    <a class="nav-link dropdown-toggle text-warning" href="#" role="button" data-bs-toggle="dropdown">Administration<span class="caret"></span></a>
    <ul class="dropdown-menu">
      <li><h6 class="dropdown-header class-primary">Réservé aux administrateurs/comptables</h6></li>
      <li><a class="dropdown-item" href="mobile_journal.php">Journal système</a></li>
      <li><a class="dropdown-item" href="gestionMembres.php">Gestion membres</a></li>
      <li><a class="dropdown-item" href="mobile_members_map.php">Localisation de nos membres</a></li>
      <li><a class="dropdown-item" href="mobile_email_lists.php">Adresses emails des membres</a></li>
      <li><a class="dropdown-item" href="mobile_members_list.php">Listes spécifiques des membres</a></li>
      <li><a class="dropdown-item" href="mobile_tilea.php">Taxe TILEA</a></li>
      <li><a class="dropdown-item" href="flight_home.php">Vols découvertes <i class="bi bi-box-arrow-up-right"></i></a></li>
      <li><a class="dropdown-item" href="http://www.spa-aviation.ovh">Essai nouveau site<i class="bi bi-box-arrow-up-right"></i></a></li>
    </ul>
  </li> <!-- dropdown administration-->

<?php
  if ($userId == 62 or $userId == 66 or $userId == 92 or $userId == 348 or $userId == 306) { // Odoo users
?>
  <li class="nav-item dropdown me-auto me-md-0">
    <a class="nav-link dropdown-toggle text-warning" href="#" role="button" data-bs-toggle="dropdown">Compta<span class="caret"></span></a>
    <ul class="dropdown-menu">
      <li><h6 class="dropdown-header">Intégration Odoo</h6></li>
      <li><a class="dropdown-item" href="https://<?=$odoo_host?>/">Connexion au site Odoo <i class="bi bi-box-arrow-up-right"></i></a></li>
      <li><a class="dropdown-item" href="odoo_customers.php">Liaison membres<->clients Odoo</a></li>
      <li><a class="dropdown-item" href="odoo_company.php">Sociétés des membres</a></li>
      <li><a class="dropdown-item" href="odoo_gen_invoices.php">Génération des factures vols membres</a></li>
      <li><a class="dropdown-item" href="odoo_gen_membership.php">Cotisations des membres</a></li>
      <li><a class="dropdown-item" href="odoo_checkboncadeau.php">Bons cadeaux payés</a></li>
      <li><h6 class="dropdown-header">Réservé aux gars IT</h6></li>
      <li><i><a class="dropdown-item" href="odoo_model.php">Exploration des modèles</a></i></li>
      <li><a class="dropdown-item" href="odoo_config.php">Configuration (choix DB)</a></li>
    </ul>
  </li> <!-- dropdown compta-->
<?php    
  }// Odoo users
?> 
<?php
} // $userIsAdmin or $userIsInstructor or $userIsBoardMember
if ($userIsAdmin or $userIsInstructor or $userIsBoardMember) {
?>
  <li class="nav-item dropdown me-auto me-md-0">
    <a class="nav-link dropdown-toggle text-warning" href="#" role="button" data-bs-toggle="dropdown">École<span class="caret"></span></a>
    <ul class="dropdown-menu">
      <li><h6 class="dropdown-header">Réservé aux FIs & admins</h6></li>
      <li><a class="dropdown-item" href="dto.students.php">Liste des élèves</a></li>
      <li><a class="dropdown-item" href="dto.exercices.php">Liste des exercices</a></li>
      <li><a class="dropdown-item" href="dto.safetyday.php">Participation au Safety Day</a></li>

<?php
  if ($userIsInstructor) {
?>
      <li><h6 class="dropdown-header">Réservé aux FIs</h6></li>
      <li><i><a class="dropdown-item" href="dto.students.php?fi=<?=$userId?>">Mes élèves</a></i></li>
      <li><i><a class="dropdown-item" href="dto.flights.php?fi=<?=$userId?>">Mes derniers vols</a></i></li>
<?php
}
?>
    </ul>
  </li> <!-- dropdown Ecole-->
<?php
}
if ($userIsFlightManager && $userId == 62) {
?>
  <li class="nav-item dropdown me-auto me-md-0">
    <a class="nav-link dropdown-toggle text-warning" href="#" role="button" data-bs-toggle="dropdown">Vols IF/INIT<span class="caret"></span></a>
    <ul class="dropdown-menu">
    <li><h6 class="dropdown-header">Réservé admins des vols IF/INIT</h6></li>
    <li><h6 class="dropdown-header">En cours de migration par Eric NE PAS UTILISER</h6></li>
    <li><a class="dropdown-item" href="flight_create.php">Nouveau</a></li>
    <li><a class="dropdown-item" href="flight_list.php?completed=false">Vols prévus</a></li>
    <li><a class="dropdown-item" href="flight_list.php?completed=true">Vols terminés</a></li>
    <li><a class="dropdown-item" href="odoo_checkboncadeau.php">Bons cadeaux payés</a></li>
    <li><a class="dropdown-item" href="if_init_folio.php">Folio vols IF-INIT</a></li>
    <li><a class="dropdown-item" href="flight_bon_management.php">Désactiver bons</a></li>
    <li><a class="dropdown-item" href="flight_odoo.php">Export des bons vers Odoo</a></li>
    <li><a class="dropdown-item" href="flight_stats.php">Statistiques mensuelles</a></li>
    <li><a class="dropdown-item" href="https://www.tripadvisor.fr/Attraction_Review-g230026-d26689532-Reviews-Spa_Aviation-Spa_Liege_Province_The_Ardennes_Wallonia.html"><img src="https://static.tacdn.com/img2/brand_refresh/Tripadvisor_lockup_horizontal_secondary_registered.svg" height="15px"> &boxbox;</a></li>
    </ul>
  </li> <!-- dropdown vols IF/INIT-->
<?php  
}
?>         <li class="nav-item dropdown me-auto me-md-0">
          <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">Réservations<span class="caret"></span></a>
          <ul class="dropdown-menu">
<?php
if ($userId > 0) {
?>
            <li><a class="dropdown-item" href="mobile_reservation.php"><i class="bi bi-display"></i> Toutes les réservations (calendrier)</a></i></li>
            <li><a class="dropdown-item" href="mobile_today.php">&#128241; Toutes les réservations par heure (smartphone)</a></li>
            <li><a class="dropdown-item" href="mobile_resa_by_plane.php"><i class="bi bi-airplane"></i> Toutes les réservations par avion (smartphone)</a></li>
            <li><a class="dropdown-item" href="mobile.php"><i class="bi bi-file-person-fill"></i> Mes réservations</a></li>
            <li><a class="dropdown-item" href="mobile_book.php"><i class="bi bi-plus-square-fill"></i> Nouvelle réservation</a></li>
            <li><a class="dropdown-item" href="webcal://ics.php?user=<?=$userId?>&auth=<?=md5($userId . $shared_secret)?>"><i class="bi bi-calendar3"></i> lier à mon calendrier (iCal)</a></li>
<?php
}
?>
            <li><a class="dropdown-item" href="mobile_top.php"><i class="bi bi-award-fill"></i> Top-10 des vols</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown me-auto me-md-0">
          <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">Avions<span class="caret"></span></a>
          <ul class="dropdown-menu" id="planesDropdown">
            <li><a class="dropdown-item" href="mobile_wnb.php">Masse et centrage</a></li>
            <li><i><a class="dropdown-item" href="mobile_performance.php">Performances</a></i></li>

<?php
if ($userIsAdmin or $userIsInstructor or $userIsBoardMember) {
?>
          <li><h6 class="dropdown-header">Disponible pour les FIs et administrateurs</h6></li>
          <li><i><a class="dropdown-item text-warning" href="mobile_plane_planning.php">Echéances des avions</a></i></li>
          <li><i><a class="dropdown-item text-warning" href="mobile_plane_4_camo.php">Rapport hebdomadaire des avions pour CAMO</a></i></li>
          <li><i><a class="dropdown-item text-warning" href="mobile_shared_flights.php">Vols en codes partagés</a></i></li>
<?php
}
if ($userId > 0) {
?>
            <li><h6 class="dropdown-header">Disponible pour tous les membres</h6></li>
            <li><a class="dropdown-item" href="mobile_planelog.php?plane=OO-FMX">Carnets de routes</a></li>
            <li><a class="dropdown-item" href="mobile_incidents.php">Aircraft Tech Logs</a></li>
            <li><a class="dropdown-item" href="IntroCarnetVol.php">Encodage compteurs <i class="bi bi-box-arrow-up-right"></i></span></a></li>
            <li><a class="dropdown-item" href="mobile_logbook_summary.php">Résumé vols club</a></li>
            <li><a class="dropdown-item" href="mobile_fleet_map.php">Ces dernières 24 heures</a></li>
            <li><a class="dropdown-item" href="mobile_fleet_map.php?latest">Dernières localisations</a></li>
            <!-- init() in js/mobile.js will insert all active planes -->
<?php
}
?>
            </ul>
        </li>
        <li class="nav-item dropdown me-auto me-md-0">
          <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">Aéroport<span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="mobile_ephemerides.php">Ephémérides</a></li>
<?php
if ($userId > 0) {
?> 
            <li><a class="dropdown-item" href="mobile_team.php">Équipe SPW</a></li>
            <li><a class="dropdown-item" href="mobile_dept_board.php">Départs</a></li> 
<?php
}
?>
            <li><a class="dropdown-item" href="mobile_local_flights.php">Vols proches</a></li>
            <li><a class="dropdown-item" href="mobile_metar.php">METAR</a></li>
            <li><a class="dropdown-item" href="mobile_wx_map.php">Météo des environs</a></li>
            <li><a class="dropdown-item" href="mobile_webcam.php?cam=0">Webcam RAPCS (Apron)</a></li>
            <li><a class="dropdown-item" href="mobile_webcam.php?cam=1">Webcam SOWAER (station météo)</a></li>
<?php
if ($userId > 0) {
?>
            <li><hr class="dropdown-divider"/></li>
            <li><h6 class="dropdown-header">Réservé aux membres</h6></li>
            <li><a class="dropdown-item" href="mobile_streaming.php?webcam=apron">Webcam RAPCS (Apron) streaming</a></li>
            <li><a class="dropdown-item" href="mobile_streaming.php?webcam=sowaer">Webcam SOWAER (station météo) streaming</a></li>
            <li><a class="dropdown-item" href="mobile_webcam.php?cam=2">Webcam RAPCS (Hangars)</a></li>
            <li><a class="dropdown-item" href="mobile_streaming.php?webcam=hangars">Webcam RAPCS (Hangars) streaming</a></li>
<?php
}
if ($userIsAdmin or $userIsInstructor) {
?>
            <li><hr class="dropdown-divider"/></li>
            <li><h6 class="dropdown-header">Pour les admins & FI</h6></li>
            <li><a class="dropdown-item text-warning" href="mobile_streaming.php?webcam=both">Webcams RAPCS (Apron and hangars) streaming</a></li>
            <li><a class="dropdown-item text-warning" href="http://kiosk.spa-aviation.be:8001/hls/index-lowres.html" target="_blank">Webcams RAPCS 24 heures basse résolution <i class="bi bi-box-arrow-up-right"></i></a></li>
            <li><a class="dropdown-item text-warning" href="http://192.168.1.37:8001/hls/index-highres.html" target="_blank">Webcams RAPCS 24 heures depuis le club <i class="bi bi-box-arrow-up-right"></i></a></li>
<?php
}
if ($userId > 0) {
?>
            <li><hr class="dropdown-divider"/></li>
            <li><h6 class="dropdown-header">Cartes Jeppessen</h6></li>
            <li><a class="dropdown-item" href="https://www.spa-aviation.be/airports/tripkits/JeppesenBelgium_31-08-2025.pdf">Jeppessen Charts</a></li>
            <li><a class="dropdown-item" href="mobile_airports.php">Résumé airports</a></li>
<?php
}
?>

          </ul>
        </li>
<?php
if ($userId > 0) {
?>
        <li class="nav-item me-auto me-md-0"><a class="nav-link text-white" href="mobile_documents.php">Documents<span class="bi bi-download"></span></a></li>
<?php
}
?>
        <li class="nav-item dropdown me-auto">
            <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">À propos<span class="caret"></span></a>
            <ul class="dropdown-menu">
              <li>
                <a class="dropdown-item" href="mobile_help.php"> 
                  <i class="bi bi-question-circle"></i> Aide</a>
              </li>
              <li>
                <a class="dropdown-item" href="mobile_help.php?topic=news"> 
                  <i class="bi bi-newspaper"></i> Nouveautés</a>
              </li>
              <li>
                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#dynamicModal" 
                  data-modal-title="Rapporter un Bug" data-content-url="<?=$base_url?>mobile_modal_bug_report.html">
                   <i class="bi bi-bug me-2"></i> Bug</a>
              </li>
              <li>
                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#dynamicModal" 
                  data-modal-title="Politique de confidentialité" data-content-url="<?=$base_url?>mobile_modal_privacy_policy.html">
                  <i class="bi bi-lock me-2"></i> Vie privée</a>
              </li>
            </ul>
        </li>
      </ul><!-- navbar left-->
  <!-- Single Dynamic Modal -->
  <div class="modal fade" id="dynamicModal" tabindex="-1" aria-labelledby="dynamicModalLabel" aria-hidden="true">
      <div class="modal-dialog">
          <div class="modal-content text-body">
              <div class="modal-header">
                  <h5 class="modal-title" id="dynamicModalLabel"></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                  <p>Chargement du contenu...</p> <!-- Initial loading message -->
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
              </div>
          </div>
      </div>
  </div>
<!-- navbar right -->
<ul class="nav navbar-nav">
<?php
if ($userId <= 0) {
?>
        <li class="navbar-item" id="loginElem">
          <a class="nav-link text-white" href="mobile_login.php?news"><i class="bi bi-box-arrow-in-right"></i> Se connecter</a>
        </li>
<?php
} else {
?>  
        <li class="nav-item dropdown multi-collapse collapse navbar-collapse">
          <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown"><i class="bi bi-person"></i> <span id="userNameSpan"><?="$userFullName ($userName)"?></span><span class="caret"></span></a>
          <ul class="dropdown-menu">
              <li id="logoutElem">
              <a class="dropdown-item" href="mobile_logout.php"><i class="bi bi-box-arrow-right"></i> Se déconnecter</a>
              <a class="dropdown-item" href="?theme=dark"><i class="bi bi-moon-stars-fill"></i> Mode nuit</a>
              <a class="dropdown-item" href="?theme=light"><i class="bi bi-sun-fill"></i> Mode jour</a>
              <a class="dropdown-item" href="mobile_mylog.php"><i class="bi bi-journals"></i> Mon carnet de vols</a>
              <a class="dropdown-item" href="mobile_mymap.php"><i class="bi bi-globe-europe-africa"></i> Mes vols sur une carte</a>
              <li><hr class="dropdown-divider"/></li>
              <li><h6 class="dropdown-header">Situation comptable</h6></li>
              <a class="dropdown-item" href="mobile_folio.php">Mon folio</a>
              <a class="dropdown-item" href="mobile_invoices.php">Mes factures</a>
              <a class="dropdown-item" href="mobile_ledger.php">Mes opérations comptables</a>
              <a class="dropdown-item" href="notedefrais.php">Note de frais</a>
<?php
if ($userIsAdmin or $userIsInstructor) {
?>
              <a class="dropdown-item text-warning" href="bondecommande.php">Bon de Commande</a>
<?php
}
?>
              <li><hr class="dropdown-divider"/></li>
              <li><h6 class="dropdown-header">Données personnelles</h6></li>
              <a class="dropdown-item" href="mobile_groups.php"><i class="bi bi-people-fill"></i> Mes groupes</a>
              <a class="dropdown-item" href="mobile_profile.php"><i class="bi bi-person-circle"></i> Mon profil</a>
<?php
  if ($userIsStudent)
      print("<a class=\"dropdown-item\" href=\"dto.student.php?student=$userId\">Ma progression</a>") ;
?>
              </li>
          </ul>
        </li> <!-- dropdown -->
<?php
}
?>
        <li class="navbar-item d-none d-md-block">
          <a class="nav-link text-white" href="mobile_metar.php?kiosk" title="Lancer un kiosque des pages publiques"><i class="bi bi-file-slides"></i></a>
        </li>
      </ul><!-- navbar right -->
  </div>
</nav>