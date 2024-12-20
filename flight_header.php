<?php
/*
   Copyright 2014-2023 Eric Vyncke

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

require_once "dbi.php" ;

MustBeLoggedIn() ;

if (! ($userIsFlightPilot or $userIsFlightManager)) 
	journalise($userId, "F", "Vous n'êtes pas autorisé(e) sur cette page: il faut avoir les bons droits d'accès.") ;
?>
<html>
<head>
<!-- TODO trim the CSS -->
<link rel="stylesheet" type="text/css" href="flight.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<meta charset="utf-8">
<!--meta name="viewport" content="width=320"-->
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- http://www.alsacreations.com/article/lire/1490-comprendre-le-viewport-dans-le-web-mobile.html -->
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<!-- http://www.w3schools.com/bootstrap/ -->
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<title>Vols découvertes et d'initiation</title>
<script>
var
	// preset Javascript constant fill with the right data from db.php PHP variables
	userFullName = '<?=$userFullName?>' ;
	userName = '<?=$userName?>' ;
	userId = <?=$userId?> ;
	userIsPilot = <?=($userIsPilot)? 'true' : 'false'?> ;
	userIsAdmin = <?=($userIsAdmin)? 'true' : 'false'?> ;
	userIsInstructor = <?=($userIsInstructor)? 'true' : 'false'?> ;
	userIsMechanic = <?=($userIsMechanic)? 'true' : 'false'?> ;
	userIsFlightPilot = <?=($userIsFlightPilot)? 'true' : 'false'?> ;
	userIsFlightManager = <?=($userIsFlightManager)? 'true' : 'false'?> ;

</script>
<!-- Matomo -->
<script type="text/javascript">
  var _paq = window._paq = window._paq || [];
  /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
  _paq.push(['setUserId', '<?=$userName?>']);
  _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
  _paq.push(["setDomains", ["*.spa-aviation.be","*.ebsp.be","*.m.ebsp.be","*.m.spa-aviation.be","*.resa.spa-aviation.be"]]);
  _paq.push(['enableHeartBeatTimer']);
  _paq.push(['setCustomVariable', 1, "userID", <?=$userId?>, "visit"]);
  _paq.push(["setCookieDomain", "*.spa-aviation.be"]);
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
	
$body_attributes = (isset($body_attributes)) ? $body_attributes : '' ; 
?>
</head>
<body <?=$body_attributes?>>

<nav class="navbar navbar-inverse bg-light navbar-light">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>                        
      </button>
      <a class="navbar-brand" href="flight_home.php">Vols découvertes/initiations <!--span style="color:red; font-weight: bold; font-style: italic;">EN TEST !!!</span--></a>
    </div><!-- navbar-header -->
    <div class="collapse navbar-collapse" id="myNavbar">
      <ul class="nav navbar-nav">
        <li class="active"><a href="flight_home.php">Home</a></li>
        <li class="dropdown">
          <a class="dropdown-toggle" data-toggle="dropdown" href="#">Vols<span class="caret"></span></a>
          <ul class="dropdown-menu">
            <!--li><a href="mobile.php">Ma prochaine</a></li-->
            <li><a href="flight_create.php">Nouveau</a></li>
            <li><a href="flight_list.php?completed=false">Vols prévus</a></li>
            <li><a href="flight_list.php?completed=true">Vols terminés</a></li>
            <li><a href="odoo_checkboncadeau.php">Bons cadeaux payés</a></li>
            <li><a href="if_init_folio.php">Folio vols IF-INIT</a></li>
            <li><a href="flight_bon_management.php">Désactiver bons</a></li>
            <li><a href="flight_odoo.php">Export des bons vers Odoo</a></li>
            <li><a href="flight_stats.php">Statistiques mensuelles</a></li>
            <li><a href="https://www.tripadvisor.fr/Attraction_Review-g230026-d26689532-Reviews-Spa_Aviation-Spa_Liege_Province_The_Ardennes_Wallonia.html"><img src="https://static.tacdn.com/img2/brand_refresh/Tripadvisor_lockup_horizontal_secondary_registered.svg" height="15px"> &boxbox;</a></li>
          </ul><!-- dropdown-menu -->
        </li><!-- dropdown -->
        <li class="dropdown">
          <a class="dropdown-toggle" data-toggle="dropdown" href="#">Pilotes<span class="caret"></span></a>
          <ul class="dropdown-menu">
            <!--li><a href="mobile.php">Ma prochaine</a></li-->
            <li><a href="flight_pilot_rating.php">Qualifications</a></li>
            <li><a href="flight_pilot_assign.php">Dernières assignations</a></li>
            <li><a href="flight_list_mine.php">Mes vols à venir</a></li>
          </ul><!-- dropdown-menu -->
        </li><!-- dropdown -->
        <li><a href="flight_help.php">Aide</a></li>
      </ul><!-- av navbar-nav -->
    </div><!-- myNavbar -->
  </div><!-- container fluid -->
</nav>

<div class="container-fluid">