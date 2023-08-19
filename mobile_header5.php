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

# HTTP/2 push of CSS via header()
header('Link: </resa/mobile.js>;rel=preload;as=script,</resa/swiped-events.js>;rel=preload;as=script,</logo_rapcs_256x256_white.png>;rel=preload;as=image') ;
	
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- http://www.alsacreations.com/article/lire/1490-comprendre-le-viewport-dans-le-web-mobile.html -->
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<title>Mobile RAPCS ASBL</title>

<!-- Using latest bootstrap 5 -->
<!-- Latest compiled and minified CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Latest compiled JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>

<style>
@viewport {
	width: device-width; /* largeur du viewport */
	zoom: 1; /* zoom initial à 1.0  */
}

#bookingTable { background-color: lightgray; border-style: solid; border-width: 2px; border-radius: 10px; margin-left:auto; margin-right: auto;
	box-shadow: 3px 3px 10px gray;
}
</style>
<!-- Glyphicon equivalent -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<!-- Allow the swipe events on phones & tablets -->
<!-- TODO should only be loaded when required -->
<script src="swiped-events.js"></script>
<script>
var
		runwaysQFU = [ <?php print(implode(', ', $runways_qfu)) ; ?> ],
		nowTimestamp = <?=time()?>,
		utcOffset = Number(<?=date('Z')/3600?>),
		userId = <?=$userId?>,
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

</script>
<script src="mobile.js"></script>
<script src="planes.js"></script>
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
<nav class="navbar navbar-expand-sm bg-success"> <!-- fixed-top should prevent scrolling but invade bottom part... -->
  <div class="container-fluid">
      <a class="navbar-brand hidden-sm" href="mobile.php?news"><img src="https://www.spa-aviation.be/logo_rapcs_256x256_white.png" width="32px" height="32px"></a>
    <!--div class="collapse navbar-collapse" id="myNavbar"-->
      <ul class="navbar-nav"><!-- nav-bar left -->
        <li class="navbar-item">
          <a class="nav-link text-white" href="mobile.php?news">Home</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">Réservations<span class="caret"></span></a>
          <ul class="dropdown-menu">
<?php
if ($userId > 0) {
?>
            <li><a class="dropdown-item" href="mobile_book.php">Nouvelle réservation</a></li>
            <li><a class="dropdown-item" href="reservation.php">Réservations (plein écran) <i class="bi bi-box-arrow-up-right"></i></a></li>
<?php
}
if ($userId > 0) {
?>
            <li><a class="dropdown-item" href="mobile.php">Mes réservations</a></li>
<?php
}
?>
            <li><a class="dropdown-item" href="mobile_today.php">Réservations de ce jour</a></li>
<?php
	if ($userIsAdmin or $userIsInstructor or $userIsFlightPilot or $userIsFlightManager)
		print("<li><a class=\"dropdown-item\" href=\"flight_home.php\">Vols découvertes <i class=\"bi bi-box-arrow-up-right\"></i></a></li>\n") ;
?>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">Avions<span class="caret"></span></a>
          <ul class="dropdown-menu" id="planesDropdown">
<?php
if ($userId > 0) {
?>
            <!--li><a href="mobile_logbook.php">Mon carnet de routes</a></li-->
            <li><a class="dropdown-item" href="IntroCarnetVol.php">Encodage compteurs <i class="bi bi-box-arrow-up-right"></i></span></a></li>
            <li><a class="dropdown-item" href="mobile_fleet_map.php">Ces dernières 24 heures</a></li>
            <!-- init() in mobile.js will insert all active planes -->
<?php
}
?>
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
            <li><a class="dropdown-item" href="mobile_webcam.php?cam=0">Webcam Apron</a></li>
            <!--li><a class="dropdown-item" href="mobile_webcam.php?cam=1">Webcam Fuel</a></li-->
          </ul>
        </li>
      </ul><!-- navbar left-->
      <ul class="nav navbar-nav ms-auto">
<?php
if ($userId <= 0) {
?>
        <li class="navbar-item" id="loginElem">
          <a class="nav-link text-white" href="mobile_login.php?news"><i class="bi bi-box-arrow-in-right"></i> Se connecter</a>
        </li>
<?php
} else {
?>  
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown"><span id="userNameSpan"><?="$userFullName ($userName)"?></span><span class="caret"></span></a>
          <ul class="dropdown-menu">
              <li id="logoutElem">
              <a class="dropdown-item" href="mobile_logout.php"><i class="bi bi-box-arrow-right"></i> Se déconnecter</a>
              <li><hr class="dropdown-divider"></hr></li>
              <a class="dropdown-item" href="mobile_ledger.php">Mon compte</a>
              <a class="dropdown-item" href="mobile_invoices.php">Mes factures</a>
              </li>
          </ul>
        </li> <!-- dropdown -->
<?php
}
?>
      </ul><!-- nabvar-right -->
    </div>
  </div>
</nav>