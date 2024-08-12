<?php
/*
   Copyright 2013 Eric Vyncke

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

require_once "dbi.php" ;
require_once 'mobile_header5.php' ;

$fontSize = (isset($_REQUEST['kiosk'])) ? '3vw' : '1em' ;
?> 
<div class="container-fluid">

<div class="row">
<h3>Ephémérides du <span id="displayDate"><span></h3>
</div> <!-- row -->

<div class="row" style="font-size: <?=$fontSize?>">
			<div class="col-md-4 col-xs-8">Jour aéronautique:</div><div id="aeroDay" class="col-md-2 col-xs-4"></div>
			<div class="col-md-4 col-xs-8">Nuit aéronautique:</div><div id="aeroNight" class="col-md-2 col-xs-4"></div>
			<div class="col-md-4 col-xs-8">Lever du soleil:</div><div id="civilDay" class="col-md-2 col-xs-4"></div>
			<div class="col-md-4 col-xs-8">Coucher du soleil:</div><div id="civilNight" class="col-md-2 col-xs-4"></div>
			<div class="col-md-4 col-xs-8">Ouverture aéroport:</div><div id="airportDay" class="col-md-2 col-xs-4"></div>
			<div class="col-md-4 col-xs-8">Fermeture aéroport:</div><div id="airportNight" class="col-md-2 col-xs-4"></div>
			<div class="col-sm-12"><i><b>En heure locale de <?=$default_airport?> et pour info seulement.</b></i></div>
			<div class="col-md-4 col-xs-8">Heure locale à <?=$default_airport?>:</div><div class="col-md-2 col-xs-4"><span id="hhmmLocal"></span></div>
			<div class="col-md-4 col-xs-8">Heure universelle:</div><div class="col-md-2 col-xs-4"><span id="hhmmUTC"></span></div>
</div> <!-- row -->

<script>
refreshEphemerides(<?=date('Y')?>, <?=date('m')?>, <?=date('d')?>) ;
displayClock() ;
</script>

</div> <!-- container-->
</body>
</html>