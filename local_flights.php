<?php
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

ob_start("ob_gzhandler");

require_once "dbi.php" ;

if (false) {
if ($userId <= 0 && (!isset($_REQUEST['auth'])))
	die("Vous devez &ecirc;tre connect&eacute; pour visualiser les vols de la journée.") ;
	
if (isset($_REQUEST['auth']))
	if ($_REQUEST['auth'] != md5($_REQUEST['pilot'] . $_REQUEST['period'] . $shared_secret))
		die("Vous n'&ecric;tes pas autoris&eacute;.") ;

}

if (isset($_REQUEST['large'])) {
	$local_longitude_bound *= 2.0 ;
	$local_latitude_bound *= 2.0 ;
	$mult = 2.0 ;
	$zoom_level = 9 ;
} else {
	$mult = 1.0 ;
	$zoom_level = 10 ;
}

if ($userId != 62) journalise($userId, 'I', "Fleet map displayed") ;

?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<!-- Load the MAP BOX scripts & CSS -->
<script src='https://api.mapbox.com/mapbox-gl-js/v0.42.0/mapbox-gl.js'></script>
<link href='https://api.mapbox.com/mapbox-gl-js/v0.42.0/mapbox-gl.css' rel='stylesheet' />
<!-- Reusing bootstrap icons -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">

<title>Vols à proximité de l'aéroport ces <?=$local_delay?> dernières minutes</title>
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
<script type="text/javascript" src="local_flights.js"></script>
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
<body onload="initLocalFlights(<?=$apt_longitude?>, <?=$local_longitude_bound?>, <?=$apt_latitude?>, <?=$local_latitude_bound?>, <?=$local_altimeter_bound?>, '<?=$mapbox_token?>', <?=$zoom_level?>, 'get_local_tracks.php?mult=<?=$mult?>');">
<center><h2>Vols à proximité de l'aéroport ces <?=$local_delay?> dernières minutes</h2></center>

<div id='container' style='position: relative;'>
	<div id='map' style='width: 100%; height: 80%;'></div>
	<div id='flightLegend' style='display: block; position: absolute; bottom: 30px; right: 5px; margin: 0px auto; padding: 10px; text-align: left; color: black; background: white; opacity: 0.8;'></div>
</div> <!-- container -->

<div id='flightInfo' style='display: none; position: absolute; margin: 0px auto; padding: 10px; text-align: left; color: black; background: white; opacity: 0.7;'></div>
<?php
$version_php = date ("Y-m-d H:i:s.", filemtime('local_flights.php')) ;
$version_js = date ("Y-m-d H:i:s.", filemtime('local_flights.js')) ;
$version_ajax = date ("Y-m-d H:i:s.", filemtime('get_local_tracks.php')) ;
?>
<hr>
<div class="copyright">R&eacute;alisation: Eric Vyncke, mars 2021, pour RAPCS, Royal A&eacute;ro Para Club de Spa, ASBL. <b>Ne pas utiliser comme outil en vol</b><br/>
Données via Flight Aware (avec maximum 15 minutes de délai), via quelques récepteurs ADS-B / MLAT (avec maximum 10 secondes de délai), via Safe Sky (1 minute of delay), via Open Sky (1 minute of delay), et via glidernet.org (planeurs FLARM et un délai d'une minute).</br>
Versions: PHP=<?=$version_php?>, JS=<?=$version_js?>, AJAX=<?=$version_ajax?></div>
</body>
</html>
