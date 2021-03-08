<?php
/*
   Copyright 2014-2021 Eric Vyncke

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
require_once 'facebook.php' ;


if ($userId <= 0 && (!isset($_REQUEST['auth'])))
	die("Vous devez &ecirc;tre connect&eacute; pour visualiser les vols de la journée.") ;
	
if (isset($_REQUEST['auth']))
	if ($_REQUEST['auth'] != md5($_REQUEST['pilot'] . $_REQUEST['period'] . $shared_secret))
		die("Vous n'&ecric;tes pas autoris&eacute;.") ;

if ($userId != 62) journalise($userId, 'I', "Fleet map displayed") ;

/* Let's retrieve the default airport coordinates */
$result = mysqli_query($mysqli_link, "select * from $table_airports where a_code = '$default_airport'") or die("Erreur systeme a propos de l'accès à l'aéroport: " . mysqli_error($mysqli_link)) ;
$row = mysqli_fetch_array($result) ;
if ($row) {
	$default_longitude = $row['a_longitude'] ;
	$default_latitude = $row['a_latitude'] ;
} else {
	$default_longitude = 5 ;
	$default_latitude = 50.5 ;
}
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="log.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<!-- Load the MAP BOX scripts & CSS -->
<script src='https://api.mapbox.com/mapbox-gl-js/v0.42.0/mapbox-gl.js'></script>
<link href='https://api.mapbox.com/mapbox-gl-js/v0.42.0/mapbox-gl.css' rel='stylesheet' />
<!-- Reusing bootstrap icons -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">

<title>Vols de la flotte ces dernières 24 heures</title>
<script type="text/javascript">
var
	// preset Javascript constant fill with the right data from db.php PHP variables
	userFullName = '<?=$userFullName?>',
	userName = '<?=$userName?>',
	userId = <?=$userId?>,
	userIsPilot = <?=($userIsPilot)? 'true' : 'false'?>,
	userIsAdmin = <?=($userIsAdmin)? 'true' : 'false'?>,
	userIsInstructor = <?=($userIsInstructor)? 'true' : 'false'?>,
	userIsMechanic = <?=($userIsMechanic)? 'true' : 'false'?>;
</script>
<script type="text/javascript" src="fleet_map.js">
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
</head>
<body onload="initFleet(<?=$default_longitude?>, <?=$default_latitude?>, '<?=$mapbox_token?>', 'get_tracks.php?');">
<center><h2>Vols de la flotte ces dernières 24 heures</h2></center>

<div id='container' style='position: relative;'>
	<div id='map' style='width: 100%; height: 90%;'></div>
	<div id='flightLegend' style='display: block; position: absolute; bottom: 30px; right: 5px; margin: 0px auto; padding: 10px; text-align: left; color: black; background: white; opacity: 0.8;'></div>
</div> <!-- container -->

<div id='flightInfo' style='display: none; position: absolute; margin: 0px auto; padding: 10px; text-align: left; color: black; background: white; opacity: 0.7;'></div>
<?php
$version_php = date ("Y-m-d H:i:s.", filemtime('fleet_map.php')) ;
$version_js = date ("Y-m-d H:i:s.", filemtime('fleet_map.js')) ;
$version_ajax = date ("Y-m-d H:i:s.", filemtime('get_tracks.php')) ;
?>
<hr>
<div class="copyright">R&eacute;alisation: Eric Vyncke, mars 2021, pour RAPCS, Royal A&eacute;ro Para Club de Spa, ASBL<br/>
Données via Flight Aware (avec maximum une heure de délai) et via quelques récepteurs ADS-B / MLAT (avec maximum 1 minute de délai).</br>
Versions: PHP=<?=$version_php?>, JS=<?=$version_js?>, AJAX=<?=$version_ajax?></div>
</body>
</html>
