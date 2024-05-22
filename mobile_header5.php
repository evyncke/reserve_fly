<?php
/*
   Copyright 2013-2023 Eric Vyncke

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

# HTTP/2 push of CSS via header()
header('Link: </resa/mobile.js>;rel=preload;as=script,</logo_rapcs_256x256_white.png>;rel=preload;as=image,</logo_rapcs_256x256.png>;rel=preload;as=image') ;
	
?><!DOCTYPE html>
<html lang="fr">
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

<!-- Allow the swipe events on phones & tablets -->
<!-- TODO should only be loaded when required -->
<script src="swiped-events.js"></script>
<script>
var
		runwaysQFU = [ <?php print(implode(', ', $runways_qfu)) ; ?> ],
		nowTimestamp = <?=time()?>,
		utcOffset = Number(<?=date('Z')/3600?>),
		userId = <?=$userId?>,
    selectedUserId = <?=(isset($_REQUEST['user']) and $_REQUEST['user'] != '') ? $_REQUEST['user'] : $userId?> ;
		userName = '<?=$userName?>',
		userFullName = '<?=$userFullName?>',
		userIsPilot = <?= ($userIsPilot) ? 'true' : 'false' ?>,
		userIsInstructor = <?= ($userIsInstructor) ? 'true' : 'false' ?>,
		userIsAdmin = <?= ($userIsAdmin) ? 'true' : 'false' ?>,
		userIsMechanic = <?=($userIsMechanic)? 'true' : 'false'?>,
		userIsStudent = <?=($userIsStudent)? 'true' : 'false'?>,
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
<script src="mobile.js"></script>
<script src="planes.js"></script>
<script src="members.js"></script> <!-- TODO load only if pilotSelect is active -->
<!-- Matomo -->
<script type="text/javascript">
  var _paq = window._paq = window._paq || [];
<?php
// If user is logged-in then call 'setUserId'
// $userId variable must be set by the server when the user has successfully authenticated to your app.
	if (isset($userId) and $userId > 0) {
     print("_paq.push(['setUserId', '$userName']);\n");
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
    _paq.push(['setSiteId', '5']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Code -->
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
  <hr>
</div><!-- row -->
</div><!-- print only -->
<nav class="navbar navbar-expand-md bg-success text-bg-success d-print-none" id="navBarId"><!-- do not print the menu... Add fixed-top w/o destroying the layout -->
  <div class="container-fluid">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target=".multi-collapse">
        <span class="navbar-toggler-icon"></span>
      </button>
      <!-- below in the <a the navbar-collapse widen the space by adding right margin !!! -->
      <a class="navbar-brand multi-collapse hidden-md collapse navbar-collapse" href="mobile.php?news" style="max-width: 40px;"><img src="https://www.spa-aviation.be/logo_rapcs_256x256_white.png" width="32px" height="32px"></a>
      <ul class="navbar-nav multi-collapse collapse navbar-collapse"><!-- nav-bar left with most of the dropdown -->
        <li class="navbar-item">
          <a class="nav-link text-white" href="mobile.php?news">Home</a>
        </li>
<?php
if ($userIsAdmin or $userIsInstructor or $userIsBoardMember) {
?>
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle text-warning" href="#" role="button" data-bs-toggle="dropdown">Administration<span class="caret"></span></a>
    <ul class="dropdown-menu">
      <li><h6 class="dropdown-header class-primary">Réservé aux administrateurs/comptables</h6></li>
      <li><a class="dropdown-item" href="mobile_journal.php">Journal système</a></li>
      <li><a class="dropdown-item" href="gestionMembres.php">Gestion membres</a></li>
      <li><a class="dropdown-item" href="mobile_members_map.php">Localisation de nos membres</a></li>
      <li><a class="dropdown-item" href="mobile_email_lists.php">Adresses emails des membres</a></li>
      <li><a class="dropdown-item" href="mobile_tilea.php">Taxe TILEA</a></li>
      <li><a class="dropdown-item" href="flight_home.php">Vols découvertes <i class="bi bi-box-arrow-up-right"></i></a></li>
    </ul>
  </li> <!-- dropdown administration-->

<?php
  if ($userId == 62 or $userId == 66 or $userId == 92 or $userId == 348 or $userId == 306) { // Odoo users
?>
  <li class="nav-item dropdown">
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
  <li class="nav-item dropdown">
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
?>         <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">Réservations<span class="caret"></span></a>
          <ul class="dropdown-menu">
<?php
if ($userId > 0) {
?>
            <li><a class="dropdown-item" href="mobile_book.php">Nouvelle réservation</a></li>
            <li><a class="dropdown-item" href="reservation.php">Réservations (plein écran) <i class="bi bi-box-arrow-up-right"></i></a></li>
            <li><a class="dropdown-item" href="mobile.php">Mes réservations</a></li>
<?php
}
?>
            <li><a class="dropdown-item" href="mobile_today.php">Réservations de ce jour</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">Avions<span class="caret"></span></a>
          <ul class="dropdown-menu" id="planesDropdown">
            <li><a class="dropdown-item" href="mobile_wnb.php">Masse et centrage</a></li>
<?php
if ($userId > 0) {
?>
<?php
      if ($userIsAdmin or $userIsInstructor or $userIsBoardMember) {
?>
            <li><h6 class="dropdown-header">Réservé aux FIs & admins</h6></li>
            <li><i><a class="dropdown-item" href="mobile_incidents.php">Tech Log</a></i></li>
<?php
      }
?>
<?php
}
if ($userIsAdmin or $userIsInstructor or $userIsBoardMember) {
?>
          <li><i><a class="dropdown-item" href="mobile_planelog.php?plane=OO-FMX">Carnets de routes</a></i></li>
          <li><i><a class="dropdown-item" href="mobile_plane_planning.php">Echéances des avions</a></i></li>
          <li><i><a class="dropdown-item" href="mobile_shared_flights.php">Vols en codes partagés</a></i></li>
          <li><h6 class="dropdown-header">Disponible pour tous les membres</h6></li>
<?php
}
?>

            <li><a class="dropdown-item" href="IntroCarnetVol.php">Encodage compteurs <i class="bi bi-box-arrow-up-right"></i></span></a></li>
            <li><a class="dropdown-item" href="mobile_fleet_map.php">Ces dernières 24 heures</a></li>
            <li><a class="dropdown-item" href="mobile_fleet_map.php?latest">Dernières localisations</a></li>
            <!-- init() in mobile.js will insert all active planes -->
            </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">Aéroport<span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="mobile_ephemerides.php">Ephémérides</a></li>
<?php
if ($userId > 0) {
?> 
            <li><a class="dropdown-item" href="mobile_team.php">Équipe SPW</a></li>

<?php
}
?>
            <li><a class="dropdown-item" href="mobile_local_flights.php">Vols proches</a></li>
            <li><a class="dropdown-item" href="mobile_metar.php">METAR</a></li>
            <li><a class="dropdown-item" href="mobile_wx_map.php">Météo des environs</a></li>
            <li><a class="dropdown-item" href="mobile_dept_board.php?cam=0">Départs</a></li> 
            <li><a class="dropdown-item" href="mobile_webcam.php?cam=0">Webcam Apron</a></li>
            <!--li><a class="dropdown-item" href="mobile_webcam.php?cam=1">Webcam Fuel</a></li-->
          </ul>
        </li>
      </ul><!-- navbar left-->
      <ul class="nav navbar-nav"><!-- navbar right -->
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
              <a class="dropdown-item" href="mobile_mylog.php">Mon carnet de vols</a>
              <li><hr class="dropdown-divider"></hr></li>
              <li><h6 class="dropdown-header">Situation comptable</h6></li>
              <a class="dropdown-item" href="mobile_folio.php">Mon folio</i></a>
              <a class="dropdown-item" href="mobile_invoices.php">Mes factures</a>
              <a class="dropdown-item" href="mobile_ledger.php">Mes opérations comptables</a>
              <li><h6 class="dropdown-header">Données personnelles</h6></li>
              <a class="dropdown-item" href="mobile_groups.php">Mes groupes</a>
              <a class="dropdown-item" href="mobile_profile.php">Mon profil</a>
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
        <li class="navbar-item">
          <a class="nav-link text-white" href="mobile_metar.php?kiosk" title="Lancer un kiosque des pages publiques"><i class="bi bi-play-fill"></i></a>
        </li>
      </ul><!-- navbar right -->
  </div>
</nav>
