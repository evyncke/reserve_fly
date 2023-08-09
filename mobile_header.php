<?php
/*
   Copyright 2013-2022 Eric Vyncke

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
if ($userId == 0) {
  header("Location: https://www.spa-aviation.be/resa/connect.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
  exit ;
}

# HTTP/2 push of CSS via header()
header('Link: </resa/mobile.css>;rel=preload;as=style, </resa/swiped-events.js>;rel=preload;as=script,</resa/mobile.js>;rel=preload;as=script,</logo_rapcs_256x256_white.png>;rel=preload;as=image') ;
	
?><!DOCTYPE html>
<html lang="fr">
<head>
<link rel="stylesheet" type="text/css" href="mobile.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<meta charset="utf-8">
<!--meta name="viewport" content="width=320"-->
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- http://www.alsacreations.com/article/lire/1490-comprendre-le-viewport-dans-le-web-mobile.html -->
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<title>Mobile RAPCS ASBL</title>
<!-- http://www.w3schools.com/bootstrap/ -->
<!-- Latest compiled and minified CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  /* Modify the background color */
  .navbar-custom {
    background-color: DodgerBlue;
  }
  /* Modify brand and text color */
  .navbar-custom .navbar-text {
            color: white;
  }
</style>
<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<!-- Allow the swipe events on phones & tablets -->
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
<nav class="navbar navbar-inverse bg-primary>
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>                        
      </button>
      <a class="navbar-brand hidden-sm" href="#"><img src="https://www.spa-aviation.be/logo_rapcs_256x256_white.png" width="32px" height="32px"></a>
    </div><!-- navbar-header-->
    <div class="collapse navbar-collapse" id="myNavbar">
      <ul class="nav navbar-nav">
        <li class="active"><a href="mobile.php?news">Home</a></li>
        <li class="dropdown">
          <a class="dropdown-toggle" data-toggle="dropdown" href="#">Réservations<span class="caret"></span></a>
          <ul class="dropdown-menu">
            <!--li><a href="mobile.php">Ma prochaine</a></li-->
<?php
if ($userId > 0) {
?>
            <li><a href="mobile_book.php">Nouvelle réservation</a></li>
<?php
}
if ($userId > 0) {
?>
            <li><a href="mobile.php">Mes réservations</a></li>
<?php
}
?>
            <li><a href="mobile_today.php">Réservations de ce jour</a></li>
<?php
	if ($userIsAdmin or $userIsInstructor or $userIsFlightPilot or $userIsFlightManager)
		print("<li><a href=\"flight_home.php\">Vols découvertes</a></li>\n") ;
?>
          </ul>
        </li>
        <li class="dropdown">
          <a class="dropdown-toggle" data-toggle="dropdown" href="#">Avions<span class="caret"></span></a>
          <ul class="dropdown-menu" id="planesDropdown">
<?php
if ($userId > 0) {
?>
            <!--li><a href="mobile_logbook.php">Mon carnet de routes</a></li-->
            <li><a href="IntroCarnetVol.php">Encodage compteurs</a></li>
            <li><a href="mobile_fleet_map.php">Ces dernières 24 heures</a></li>
<?php
}
?>
          </ul>
        </li>
        <li class="dropdown">
          <a class="dropdown-toggle" data-toggle="dropdown" href="#">Aéroport<span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="mobile_ephemerides.php">Ephémérides</a></li>
<?php
if ($userId > 0) {
?> 
            <li><a href="mobile_team.php">Équipe SPW</a></li>

<?php
}
?>
            <li><a href="mobile_local_flights.php">Vols proches</a></li>
            <li><a href="mobile_metar.php">METAR</a></li>
            <li><a href="mobile_webcam.php?cam=0">Webcam Apron</a></li>
            <li><a href="mobile_webcam.php?cam=1">Webcam Fuel</a></li>
          </ul>
        </li>
      </ul><!-- myNavbar -->
      <ul class="nav navbar-nav navbar-right">
        <!--li><a href="#"><span class="glyphicon glyphicon-user"></span> Sign Up</a></li-->
        <li id="userNameElem" class="hidden-sm"><span id="userNameSpan" class="navbar-text"></span></li>
        <li id="loginElem"><a href="https://resa.spa-aviation.be/mobile_login.php"><span class="glyphicon glyphicon-log-in"></span> Se connecter</a></li>
        <li id="logoutElem"><a href="mobile_logout.php"><span class="glyphicon glyphicon-log-out"></span> Se déconnecter</a></li>
      </ul><!-- nabvar-right -->
    </div>
  </div>
</nav>