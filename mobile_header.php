<?php
/*
   Copyright 2013-2021 Eric Vyncke

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
$session_name = session_name('RAPCS') ;
$cookie_lifetime = 3600 * 24 * 7 ;
session_start(['cookie_lifetime' => $cookie_lifetime, 'cookie_httponly' => '1', 'cookie_domain' => '.spa-aviation.be', 'cookie_path' => '/resa', 'use_cookies' => '1']) 
	or journalise($userId, "E", "Cannot start session in mobile header") ;
// As it seems that session_start() parameters do not influence the cookie, here we go again...
// setcookie ( string $name , string $value = "" , int $expires = 0 , string $path = "" , string $domain = "" , bool $secure = false , bool $httponly = false ) : bool
setcookie(session_name(),session_id(),time() + $cookie_lifetime, '/resa', '.spa-aviation.be', true, true)
	or journalise($userId, "E", "Cannot modify setcookie() in mobile_header") ;

if (!session_id()) {
	journalise($userId, 'W', "session_id() does not return any value") ; 
} 
if ($userId <= 0 and isset($_SESSION['jom_id']) and is_numeric($_SESSION['jom_id']) and $_SESSION['jom_id'] > 0) {
	$joomla_user = JFactory::getUser($_SESSION['jom_id']) ;
	CheckJoomlaUser($joomla_user) ;
	journalise($userId, 'I', "Using _SESSION['jom_id']=$_SESSION[jom_id] for authentication") ;
} else
	$_SESSION['jom_id'] = $userId ;
$_SESSION['truc'] = 'muche' ;
session_commit() ;
	
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
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
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
</head>
<body onload="init();<?=($_SERVER['PHP_SELF'] == '/resa/mobile_logbook.php' or $_SERVER['PHP_SELF'] == '/mobile_logbook.php') ? 'initLogbook();' : ''?><?=($_SERVER['PHP_SELF'] == '/resa/mobile_book.php'or $_SERVER['PHP_SELF'] == '/mobile_book.php') ? 'initBook();' : ''?>">

<nav class="navbar navbar-inverse">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>                        
      </button>
      <a class="navbar-brand hidden-sm" href="#"><img src="https://www.spa-aviation.be/logo_rapcs_256x256_white.png" width="32px" height="32px"></a>
    </div>
    <div class="collapse navbar-collapse" id="myNavbar">
      <ul class="nav navbar-nav">
        <li class="active"><a href="mobile.php?news">Home</a></li>
        <li class="dropdown">
          <a class="dropdown-toggle" data-toggle="dropdown" href="#">Réservations<span class="caret"></span></a>
          <ul class="dropdown-menu">
            <!--li><a href="mobile.php">Ma prochaine</a></li-->
<?php
// Temporary COVID-19
//if ($userId > 0) {
if (($userId > 0) and ($userIsInstructor or $userIsMechanic)) {
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
		print("<li><a href=\"flight_home.php\">Vols découvertes (!!! TEST !!!)</a></li>\n") ;
?>
          </ul>
        </li>
        <li class="dropdown">
          <a class="dropdown-toggle" data-toggle="dropdown" href="#">Avions<span class="caret"></span></a>
          <ul class="dropdown-menu" id="planesDropdown">
<?php
if ($userId > 0) {
?>
            <li><a href="mobile_logbook.php">Mon carnet de routes</a></li>
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
            <li><a href="mobile_team.php">Equipe SPW</a></li>
<?php
}
?>
            <li><a href="mobile_metar.php">METAR</a></li>
            <li><a href="mobile_webcam.php?cam=0">Webcam Apron</a></li>
            <li><a href="mobile_webcam.php?cam=1">Webcam Fuel</a></li>
          </ul>
        </li>
      </ul><!-- myNavbar -->
      <ul class="nav navbar-nav navbar-right">
        <!--li><a href="#"><span class="glyphicon glyphicon-user"></span> Sign Up</a></li-->
        <li id="userNameElem" class="hidden-sm"><span id="userNameSpan" class="navbar-text"></span></li>
        <li id="FBloginElem"><a href="<?= htmlspecialchars($fb_loginUrl)?>"><img src="facebook.jpg"/> Lier à Facebook</a></li>
        <li id="loginElem"><a href="https://resa.spa-aviation.be/mobile_login.php"><span class="glyphicon glyphicon-log-in"></span> Se connecter</a></li>
        <li id="logoutElem"><a href="mobile_logout.php"><span class="glyphicon glyphicon-log-out"></span> Se déconnecter</a></li>
      </ul><!-- nabvar-right -->
    </div>
  </div>
</nav>
 