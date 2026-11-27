<?php
/*
   Copyright 2014-2026 Eric Vyncke

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

$today_month = date('m') ;
$today_day = date('d') ;
// Use || (and not "or") as "or" has a lower precedence than "="
$christmas_theme = (($today_month == '12' and $today_day >= '15') || ($today_month == '01' and $today_day <= '08')) ;
// Pages located in a sub-directory (e.g., voldecouverte/) set $flight_url_prefix = '../' before including this file
$flight_url_prefix = $flight_url_prefix ?? '' ;
// Highlight the current page in the menu
$flight_current_page = basename($_SERVER['SCRIPT_NAME']) ;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Vols découvertes et d'initiation</title>
<link href="<?=htmlspecialchars($favicon)?>" rel="shortcut icon" type="image/vnd.microsoft.icon">
<!-- Bootstrap 5 (pinned version + SRI) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<!-- Glyphicon replacement -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" integrity="sha384-4LISF5TTJX/fLmGSxO53rV4miRxdg84mZsxmO8Rx5jGtp/LbrixFETvWa5a6sESd" crossorigin="anonymous">
<!-- jQuery is not needed by Bootstrap 5 but still used by some pages -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" integrity="sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs" crossorigin="anonymous"></script>
<!-- TODO trim the CSS -->
<link rel="stylesheet" type="text/css" href="<?=$flight_url_prefix?>css/flight.css">
<?php
if ($christmas_theme) {
?>
<!-- Christmas theme -->
<link rel="stylesheet" href="<?=$flight_url_prefix?>css/mobile_christmas.css">
<?php
} // $christmas theme
?>
<script>
// preset Javascript constants filled with the right data from the dbi.php PHP variables
var userFullName = <?=json_encode($userFullName, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?> ;
var userName = <?=json_encode($userName, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?> ;
var userId = <?=(int) $userId?> ;
var userIsPilot = <?=($userIsPilot)? 'true' : 'false'?> ;
var userIsAdmin = <?=($userIsAdmin)? 'true' : 'false'?> ;
var userIsInstructor = <?=($userIsInstructor)? 'true' : 'false'?> ;
var userIsMechanic = <?=($userIsMechanic)? 'true' : 'false'?> ;
var userIsFlightPilot = <?=($userIsFlightPilot)? 'true' : 'false'?> ;
var userIsFlightManager = <?=($userIsFlightManager)? 'true' : 'false'?> ;
</script>
<!-- Matomo -->
<script>
  var _paq = window._paq = window._paq || [];
  /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
  _paq.push(['setUserId', userName]);
  _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
  _paq.push(["setDomains", ["*.spa-aviation.be","*.ebsp.be","*.m.ebsp.be","*.m.spa-aviation.be","*.resa.spa-aviation.be"]]);
  _paq.push(['enableHeartBeatTimer']);
  _paq.push(['setCustomVariable', 1, "userID", userId, "visit"]);
  _paq.push(["setCookieDomain", "*.spa-aviation.be"]);
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
<?php  // Output any page specific header
if (isset($header_postamble))
	print($header_postamble) ;
	
$body_attributes = (isset($body_attributes)) ? $body_attributes : '' ; 
?>
</head>
<body <?=$body_attributes?>>
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
<!-- end of Christmas theme -->
<?php
} // $christmas theme
?>
<nav class="navbar navbar-expand-lg bg-black d-print-none" data-bs-theme="dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?=$flight_url_prefix?>flight_home.php">Vols découvertes/initiations</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#myNavbar" aria-controls="myNavbar" aria-expanded="false" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="myNavbar">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link<?=($flight_current_page == 'flight_home.php') ? ' active' : ''?>" href="<?=$flight_url_prefix?>flight_home.php">Home</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Vols</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?=$flight_url_prefix?>flight_create.php">Nouveau Vol</a></li>
            <li><a class="dropdown-item" href="<?=$flight_url_prefix?>flight_list.php?completed=false">Vols prévus</a></li>
            <li><a class="dropdown-item" href="<?=$flight_url_prefix?>flight_list.php?completed=true">Vols terminés</a></li>
            <li><a class="dropdown-item" href="<?=$flight_url_prefix?>flight_stats.php">Statistiques mensuelles</a></li>
            <li><a class="dropdown-item" href="https://www.tripadvisor.fr/Attraction_Review-g230026-d26689532-Reviews-Spa_Aviation-Spa_Liege_Province_The_Ardennes_Wallonia.html" target="_blank" rel="noopener"><img src="https://static.tacdn.com/img2/brand_refresh/Tripadvisor_lockup_horizontal_secondary_registered.svg" height="15" alt="Tripadvisor"> <i class="bi bi-box-arrow-up-right"></i></a></li>
          </ul><!-- dropdown-menu -->
        </li><!-- dropdown -->

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Comptabilité</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?=$flight_url_prefix?>odoo_checkboncadeau.php">Bons cadeaux payés</a></li>
            <li><a class="dropdown-item" href="<?=$flight_url_prefix?>if_init_folio.php">Folio vols IF-INIT</a></li>
            <li><a class="dropdown-item" href="<?=$flight_url_prefix?>flight_bon_management.php">Désactiver bons</a></li>
            <li><a class="dropdown-item" href="<?=$flight_url_prefix?>flight_odoo.php">Export des bons vers Odoo</a></li>
            <li><a class="dropdown-item" href="<?=$flight_url_prefix?>voldecouverte/page_tarifs.php">Tarifs</a></li>
          </ul><!-- dropdown-menu -->
        </li><!-- dropdown -->

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Pilotes</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?=$flight_url_prefix?>flight_pilot_rating.php">Qualifications</a></li>
            <li><a class="dropdown-item" href="<?=$flight_url_prefix?>flight_pilot_assign.php">Dernières assignations</a></li>
            <li><a class="dropdown-item" href="<?=$flight_url_prefix?>flight_list_mine.php">Mes vols à venir</a></li>
          </ul><!-- dropdown-menu -->
        </li><!-- dropdown -->
        <li class="nav-item"><a class="nav-link" href="<?=$flight_url_prefix?>mobile_reservation.php">Réservations</a></li>
        <li class="nav-item"><a class="nav-link" href="<?=$flight_url_prefix?>flight_help.php">Aide</a></li>
      </ul><!-- navbar-nav -->
    </div><!-- myNavbar -->
  </div><!-- container fluid -->
</nav>

<div class="container-fluid">