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

$map_rallye = (isset($_REQUEST['rallye']) and $_REQUEST['rallye'] != '') ;

if (! $map_rallye) { 
	if ($userId <= 0 && (!isset($_REQUEST['auth'])))
		die("Vous devez &ecirc;tre connect&eacute; pour visualiser les vols de la journée.") ;
	
	if (isset($_REQUEST['auth']))
		if ($_REQUEST['auth'] != md5($_REQUEST['pilot'] . $_REQUEST['period'] . $shared_secret))
			die("Vous n'&ecric;tes pas autoris&eacute;.") ;
}
if ($userId != 62) journalise($userId, 'I', "Fleet map displayed") ;
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="log.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<!-- Load the MAP BOX scripts & CSS -->
<script src='https://api.mapbox.com/mapbox-gl-js/v0.42.0/mapbox-gl.js'></script>
<link href='https://api.mapbox.com/mapbox-gl-js/v0.42.0/mapbox-gl.css' rel='stylesheet' />

<!-- Using latest bootstrap 5 -->
<!-- Latest compiled and minified CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Latest compiled JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Glyphicon equivalent -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<!-- Reusing bootstrap icons -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">

<?php
if ($map_rallye)
	print("<title>Equipages du rallye Air Spa</title>") ;
else 
	print("<title>Vols de la flotte ces dernières 24 heures</title>") ;
?>
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
<script type="text/javascript" src="fleet_map.js"></script>
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
<body onload="initFleet(<?=$apt_longitude?>, <?=$apt_latitude?>, '<?=$mapbox_token?>', <?=($map_rallye) ? "'get_local_tracks.php?rallye=y'" : "'get_tracks.php?'" ?>);">
<center><h2><?=($map_rallye) ? "Participants au rallye cette dernière heure" : "Vols de la flotte ces dernières 24 heures"?></h2></center>

<div id='container' style='position: relative;'>
	<div id='map' style='width: 100%; height: 85%;'></div>
	<div id='flightLegend' style='display: block; position: absolute; bottom: 30px; right: 5px; margin: 0px auto; padding: 10px; text-align: left; color: black; background: white; opacity: 0.8;'></div>
</div> <!-- container -->

<div id='flightInfo' style='display: none; position: absolute; margin: 0px auto; padding: 10px; text-align: left; color: black; background: white; opacity: 0.7;'></div>
<hr>
<div class="copyright">R&eacute;alisation: Eric Vyncke, mars 2021 - septembre 2023, pour RAPCS, Royal A&eacute;ro Para Club de Spa, ASBL<br/>
Données via Flight Aware (avec maximum 15 minutes de délai), via quelques récepteurs ADS-B / MLAT (avec maximum 10 secondes de délai), via Open Sky, et via glidernet.org (planeurs FLARM et intégration SafeSky et un délai d'une minute).</br>
</div>
</body>
</html>
