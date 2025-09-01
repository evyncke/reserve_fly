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
<main class="container-fluid">
    <header class="row">
        <h2 class="h2">Ephémérides du <time id="displayDate"></time></h2>
    </header>

    <section class="row" style="font-size: <?=$fontSize?>">
        <dl class="row m-0 w-100">
            <dt class="col-md-4 col-xs-8">Jour aéronautique:</dt>
            <dd id="aeroDay" class="col-md-2 col-xs-4"></dd>
            
            <dt class="col-md-4 col-xs-8">Nuit aéronautique:</dt>
            <dd id="aeroNight" class="col-md-2 col-xs-4"></dd>
            
            <dt class="col-md-4 col-xs-8">Lever du soleil:</dt>
            <dd id="civilDay" class="col-md-2 col-xs-4"></dd>
            
            <dt class="col-md-4 col-xs-8">Coucher du soleil:</dt>
            <dd id="civilNight" class="col-md-2 col-xs-4"></dd>
            
            <dt class="col-md-4 col-xs-8">Ouverture aéroport:</dt>
            <dd id="airportDay" class="col-md-2 col-xs-4"></dd>
            
            <dt class="col-md-4 col-xs-8">Fermeture aéroport:</dt>
            <dd id="airportNight" class="col-md-2 col-xs-4"></dd>
        </dl>

        <aside class="col-sm-12">
            <em><strong>En heure locale de <?=$default_airport?> et pour info seulement.</strong></em>
        </aside>

        <dl class="row m-0 w-100">
            <dt class="col-md-4 col-xs-8">Heure locale à <?=$default_airport?>:</dt>
            <dd class="col-md-2 col-xs-4"><time id="hhmmLocal"></time></dd>
            
            <dt class="col-md-4 col-xs-8">Heure universelle:</dt>
            <dd class="col-md-2 col-xs-4"><time id="hhmmUTC"></time></dd>
        </dl>
    </section>

<script>
refreshEphemerides(<?=date('Y')?>, <?=date('m')?>, <?=date('d')?>) ;
displayClock() ;
</script>

</div> <!-- container-->
</body>
</html>