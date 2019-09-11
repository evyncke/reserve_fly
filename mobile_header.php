<?php
/*
   Copyright 2013-2019 Eric Vyncke

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

if ($userId <= 0 and isset($_SESSION['jom_id']) and is_numeric($_SESSION['jom_id'])) {
	$joomla_user = JFactory::getUser($_SESSION['jom_id']) ;
	CheckJoomlaUser($joomla_user) ;
}	
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
if ($userId > 0) {
?>
            <li><a href="mobile_book.php">Nouvelle réservation</a></li>
            <li><a href="mobile.php">Mes réservations</a></li>
<?php
}
?>
            <li><a href="mobile_today.php">Réservations de ce jour</a></li>
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
 