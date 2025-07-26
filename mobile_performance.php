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

require_once "dbi.php" ;
if ($userId == 0) {
	//header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	//exit ;
}
$displayAllColumns=false;
$hiddenTag="hidden";
$itemWidth="width=\"50%\"";
$plane = (isset($_REQUEST['plane'])) ? mysqli_real_escape_string($mysqli_link, strtoupper($_REQUEST['plane'])) : 'OO-ALD' ;
$displayAllColumns = (isset($_REQUEST['displayallcolumns'])) ? $_REQUEST['displayallcolumns']: 'false' ;
if($displayAllColumns=='true') {
    $hiddenTag="";
    $itemWidth="width=\"25%\"";
}
$body_attributes = "style=\"height: 100%; min-height: 100%; width:100%;\" onload=\"init();\"" ;
$header_postamble = "
<script type=\"text/javascript\" src=\"https://www.gstatic.com/charts/loader.js\"></script>
<link rel=\"stylesheet\" type=\"text/css\" href=\"mobile_performance.css\">
</script>
" ;

require_once 'mobile_header5.php' ;
$performanceJSONcontent = file_get_contents('https://www.spa-aviation.be/resa/mobile_performance.json') ;
$performanceJSONcontent = str_replace("\n","",$performanceJSONcontent);
print("<script>\nvar performanceJSONcontent='$performanceJSONcontent';");
print("var default_member=$userId;\n");
print("</script>\n");
?>
<h2 class="d-none d-md-block">Aircraft Performance</h2>
<form action="<?=$_SERVER['PHP_SELF']?>" method="GET" role="form" class="form-horizontal">
<!--div class="row m-0-xs"-->
    <label for="id_plane_select" class="col-form-label col-xs-1 col-md-1">Plane:</label>
    <select id="id_plane_select" class="col-form-select col-xs-1" name="plane_select"></select>
<!--/div> <!-- row -->

<?php
if ($plane == '') {
    print('<div class="row text-warning">Veuillez choisir un avion dans la liste ci-dessus.</div>') ;
    exit ;
}
?>
</form>
<div class="tab">
  <button class="tablinks" onclick="openPerformance(event, 'W&B')" >W&B</button>
  <button class="tablinks" onclick="openPerformance(event, 'Take-off')" id="defaultOpen">Take-off</button>
  <button class="tablinks" onclick="openPerformance(event, 'Landing')">Landing</button>
</div>

<div id="W&B" class="tabcontent">
    <h2 class="d-none d-md-block">Performance Weight & Balance:  <?=$plane?></h2>
</div> <!---tabcontent W&B-->

<div id="Take-off" class="tabcontent">
    <div class="container-fluid">
        <!--div class="container-fluid" style="height: 100%!important;overflow:auto;top:0;bottom:0;left:0;right:0;position:fixed;"-->
        <!--div class="container-fluid" style="height: 100%!important;"-->
        <!--div class="container vh-90" style="height: 80vh!important;width: 100vw!important;bottom:0!important;left:0!important;right:0!important;"-->

        <div class="row">
            <div class="col-xs-12 col-sm-12 col-lg-6"> <!--- col -->
                <h2 class="d-none d-md-block">Performance Take-off:  <?=$plane?></h2>
                <table class="table table-striped table-hover table-bordered table-condensed w-auto" style="margin-bottom: 0rem;">
                <thead>
                <tr><th class="text-end py-0 py-md-1">Input</th>
                    <th class="py-0 py-md-1 py-md-1" >Value</th>
                </tr>
                </thead>
                <tbody class="table-divider">
                <?php
                    //$readonly = 'oninput="processPerformance();"' ;
                    $readonly = '' ;
                    //QNH
                    print("<tr><td class=\"text-end py-0 py-md-1\">QNH</td>") ;
                    print("<td class=\"py-0\"><input type=\"number\" id=\"id_takeoff_i_qnh\" class=\"text-end py-0 py-md-1\" value=\"1013\" style=\"width: 80%;\" $readonly>") ;
                    print("&nbsp;<span id=\"id_takeoff_i_qnh/unit\">xx</span></td>") ;
                    //Altitude
                    print("<tr><td class=\"text-end py-0 py-md-1\">Altitude</td>") ;
                    print("<td class=\"py-0\"><input type=\"number\" id=\"id_takeoff_i_altitude\" class=\"text-end py-0 py-md-1\" value=\"1600\" style=\"width: 80%;\" $readonly>") ;
                    print("&nbsp;<span id=\"id_takeoff_i_altitude/unit\">xx</span></td>") ;
                    //Température
                    print("<tr><td class=\"text-end py-0 py-md-1\">Température</td>") ;
                    print("<td class=\"py-0\"><input type=\"number\" id=\"id_takeoff_i_temperature\" class=\"text-end py-0 py-md-1\" value=\"20\" style=\"width: 80%;\" $readonly>") ;
                    print("&nbsp;<span id=\"id_takeoff_i_temperature/unit\">xx</span></td>") ;
                    //Piste
                    print("<tr><td class=\"text-end py-0 py-md-1\">Piste</td>") ;
                    print("<td class=\"py-0\"><input type=\"number\" id=\"id_takeoff_i_runway_number\" class=\"text-end py-0 py-md-1\" value=\"23\" style=\"width: 80%;\" $readonly>") ;
                    print("&nbsp;<span id=\"id_takeoff_i_runway_number/unit\">xx</span></td>") ;
                    //Type Piste
                    print("<tr><td class=\"text-end py-0 py-md-1\">Type Piste</td>") ;
                    print("<td class=\"py-0\"><select id=\"id_takeoff_i_runway_type\"  name=\"takeoff_i_runway_type\" $readonly></select>");
                    //print("<td class=\"py-0\"><input type=\"text\" id=\"id_takeoff_i_runway_type\" class=\"text-end py-0 py-md-1\" value=\"asphalt\" style=\"width: 80%;\" $readonly>") ;
                    print("&nbsp;<span id=\"id_takeoff_i_runway_type/unit\">xx</span></td>") ;
                    //Pente Piste
                    print("<tr><td class=\"text-end py-0 py-md-1\">Pente Piste</td>") ;
                    print("<td class=\"py-0\"><input type=\"number\" id=\"id_takeoff_i_runway_slope\" class=\"text-end py-0 py-md-1\" value=\"1\" style=\"width: 80%;\" $readonly>") ;
                    print("&nbsp;<span id=\"id_takeoff_i_runway_slope/unit\">xx</span></td>") ;
                    //Direction Vent
                    print("<tr><td class=\"text-end py-0 py-md-1\">Direction vent</td>") ;
                    print("<td class=\"py-0\"><input type=\"number\" id=\"id_takeoff_i_wind_direction\" class=\"text-end py-0 py-md-1\" value=\"230\" style=\"width: 80%;\" $readonly>") ;
                    print("&nbsp;<span id=\"id_takeoff_i_wind_direction/unit\">xx</span></td>") ;
                    //Vitesse Vent
                    print("<tr><td class=\"text-end py-0 py-md-1\">Vitesse vent</td>") ;
                    print("<td class=\"py-0\"><input type=\"number\" id=\"id_takeoff_i_wind_speed\" class=\"text-end py-0 py-md-1\" value=\"10\" style=\"width: 80%;\" $readonly>") ;
                    print("&nbsp;<span id=\"id_takeoff_i_wind_speed/unit\">xx</span></td>") ;
                //Poids
                    print("<tr><td class=\"text-end py-0 py-md-1\">Poids</td>") ;
                    print("<td class=\"py-0\"><input type=\"number\" id=\"id_takeoff_i_weight\" class=\"text-end py-0 py-md-1\" value=\"720\" style=\"width: 80%;\" $readonly>") ;
                    print("&nbsp;<span id=\"id_takeoff_i_weight/unit\">xx</span></td>") ;
                //Flaps
                    print("<tr><td class=\"text-end py-0 py-md-1\">Flaps</td>") ;
                    print("<td class=\"py-0\"><input type=\"number\" id=\"id_takeoff_i_flaps\" class=\"text-end py-0 py-md-1\" value=\"0\" style=\"width: 80%;\" $readonly>") ;
                    print("&nbsp;<span id=\"id_takeoff_i_flaps/unit\">xx</span></td>") ;
                //Pilot skill
                    print("<tr><td class=\"text-end py-0 py-md-1\">Pilot Skill</td>") ;
                    print("<td class=\"py-0\"><select id=\"id_takeoff_i_pilot_skill\"  name=\"id_takeoff_i_pilot_skill\" $readonly></select>");
                    print("&nbsp;<span id=\"id_takeoff_i_pilot_skill/unit\">xx</span></td>") ;
                ?>
                </tbody>
                <tfoot class="table-divider">
                    <tr>
                    </tr>
                </tfoot>
                </table>
                <span class="d-none d-md-block py-0" >
                <?php
                $checkedItem="";
                if($displayAllColumns=='true') {
                $checkedItem="checked";
                }
                ?>
                </span>

                <div id="chart_div">
                    <p></p>
                    <canvas id="id_takeoff_o_canvas" width="600" height="150" style="border:1px solid #686868ff;">
                    <!---<canvas id="id_takeoff_o_canvas" style="width: 60vw; height: 50vw; margin: auto;border:1px solid #000000;">-->
                    </canvas>
                    <p></p>
                </div>
                <div class="mt-2 p-2 bg-danger text-bg-danger rounded" style="visibility: hidden; display: none;" id="warningsDiv">
                </div>

            </div><!--col-->

            <!-- should try to use fixed aspect ration with CSS: aspect-ration: 4 / 3 or padding-top: 75% to replace the height setting 
            using aspect-ratio makes printing over two pages... 
            using padding-top also prints over 2 pages and makes the display ultra small-->
            <div class="col-xs-12 col-sm-12 col-lg-6"> <!--- Row COL-->
                <table class="table table-striped table-hover table-bordered table-condensed w-auto" style="margin-bottom: 0rem;">
                <thead>
                <tr><th class="text-end py-0 py-md-1">Result</th>
                    <th class="py-0 py-md-1 py-md-1" >Value</th>
                </tr>
                </thead>
                <tbody class="table-divider">
                </tbody>
                <!--Pressure Altitude-->
                <tr><td class="text-end py-0 py-md-1">Pressure Altitude</td>
                    <td class="py-0\"><span id="id_takeoff_o_pressure_altitude">xx</span>
                    <!---<input type="number" id="id_takeoff_o_pressure_altitude" class="text-end py-0 py-md-1" value="2345" >-->
                    &nbsp;<span id="id_takeoff_o_pressure_altitude/unit">xx</span>&nbsp;
                    <!--<a class="tooltip" href="mobile_performance.php">&#9432;<span id="id_takeoff_o_pressure_altitude/tooltip" class='tooltiptext'>tooltip</span></a>-->
                <span class="tooltip">&#9432;<span id="id_takeoff_o_pressure_altitude/tooltip" class='tooltiptext'>tooltip</span></span>
                    </td>

                </tr>
                <!--Density Altitude-->
                <tr><td class="text-end py-0 py-md-1">Density Altitude</td>
                    <td class="py-0\"><span id="id_takeoff_o_density_altitude">xx</span>
                    &nbsp;<span id="id_takeoff_o_density_altitude/unit">xx</span>&nbsp;<span class="tooltip">&#9432;<span id="id_takeoff_o_density_altitude/tooltip" class='tooltiptext'>tooltip</span></span></td>
                </tr>
                <!--Temperature ISA-->
                <tr><td class="text-end py-0 py-md-1">Temperature ISA</td>
                    <td class="py-0\"><span id="id_takeoff_o_temperature_isa">xx</span>
                    &nbsp;<span id="id_takeoff_o_temperature_isa/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_takeoff_o_temperature_isa/tooltip" class='tooltiptext'>tooltip</span></span></td>
                <!--Delta Temperature ISA-->
                <tr><td class="text-end py-0 py-md-1">Delta Temperature ISA</td>
                    <td class="py-0\"><span id="id_takeoff_o_temperature_delta_isa">xx</span>
                    &nbsp;<span id="id_takeoff_o_temperature_delta_isa/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_takeoff_o_temperature_delta_isa/tooltip" class='tooltiptext'>tooltip</span></span></td>
                </tr>
                <!--Head wind-->
                <tr><td class="text-end py-0 py-md-1">Head Wind</td>
                    <td class="py-0\"><span id="id_takeoff_o_head_wind_speed">xx</span>
                    &nbsp;<span id="id_takeoff_o_head_wind_speed/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_takeoff_o_head_wind_speed/tooltip" class='tooltiptext'>tooltip</span></span></td>
                </tr>
                <!--Cross wind-->
                <tr><td class="text-end py-0 py-md-1">Cross Wind</td>
                    <td class="py-0\"><span id="id_takeoff_o_cross_wind_speed">xx</span>
                    &nbsp;<span id="id_takeoff_o_cross_wind_speed/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_takeoff_o_cross_wind_speed/tooltip" class='tooltiptext'>tooltip</span></span></td>
                </tr>
                <!--Roll IAS-->
                <tr><td class="text-end py-0 py-md-1">Roll IAS</td>
                    <td class="py-0\"><span id="id_takeoff_o_ias_roll">xx</span>
                    &nbsp;<span id="id_takeoff_o_ias_roll/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_takeoff_o_ias_roll/tooltip" class='tooltiptext'>tooltip</span></span></td>
                </tr>
                <!--Roll-out Distance-->
                <tr><td class="text-end py-0 py-md-1">Roll Distance</td>
                    <td class="py-0\"><span id="id_takeoff_o_distance_roll">xx</span>
                    &nbsp;<span id="id_takeoff_o_distance_roll/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_takeoff_o_distance_roll/tooltip" class='tooltiptext'>tooltip</span></span></td>
                </tr>
                <!--50ft IAS-->
                <tr><td class="text-end py-0 py-md-1">50ft IAS</td>
                    <td class="py-0\"><span id="id_takeoff_o_ias_50ft">xx</span>
                    &nbsp;<span id="id_takeoff_o_ias_50ft/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_takeoff_o_ias_50ft/tooltip" class='tooltiptext'>tooltip</span></span></td>
                </tr>
                <!--50ft Distance-->
                <tr><td class="text-end py-0 py-md-1">50ft Distance</td>
                    <td class="py-0\"><span id="id_takeoff_o_distance_50ft">xx</span>
                    &nbsp;<span id="id_takeoff_o_distance_50ft/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_takeoff_o_distance_50ft/tooltip" class='tooltiptext'>tooltip</span></span></td>
                </tr>
                <!--Max RoC-->
                <tr><td class="text-end py-0 py-md-1">Max RoC</td>
                    <td class="py-0\"><span id="id_takeoff_o_max_roc">xx</span>
                    &nbsp;<span id="id_takeoff_o_max_roc/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_takeoff_o_max_roc/tooltip" class='tooltiptext'>tooltip</span></span></td>
                </tr>
                <!--Max RoC IAS-->
                <tr><td class="text-end py-0 py-md-1">Max RoC IAS</td>
                    <td class="py-0\"><span id="id_takeoff_o_ias_max_roc">xx</span>
                    &nbsp;<span id="id_takeoff_o_ias_max_roc/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_takeoff_o_ias_max_roc/tooltip" class='tooltiptext'>tooltip</span></span></td>
                </tr>
                <!--Best Angle IAS-->
                <tr><td class="text-end py-0 py-md-1">Best Angle IAS</td>
                    <td class="py-0\"><span id="id_takeoff_o_ias_best_angle">xx</span>
                    &nbsp;<span id="id_takeoff_o_ias_best_angle/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_takeoff_o_ias_best_angle/tooltip" class='tooltiptext'>tooltip</span></span></td>
                </tr>
                </table>
            </div> <!-- col -->
        </div><!--row-->

        <p class="d-none d-md-block text-bg-warning mx-auto fs-6" style="height: 20px; position: fixed; margin:0; bottom: 0px;">
            <small>Ceci est un simple outil informatique, le pilote doit toujours vérifier le POH ainsi que le certificat W&B officiel de l'avion.
        </small></p>

    </div><!-- container-fluid -->
</div> <!---tabcontent TakeOff-->

<div id="Landing" class="tabcontent">
    <h2 class="d-none d-md-block">Performance Landing:  <?=$plane?></h2>
</div> <!---tabcontent Landing-->



<script type="text/javascript">
    var rowCount = 7, density = [], 
        darkMode = 	(decodeURIComponent(document.cookie).search('theme=dark') >= 0), displayDarkMode = darkMode ;



window.addEventListener('beforeprint', (event) => {
    displayDarkMode = darkMode ; // Save the dark mode
    darkMode = false ;
    //chart.clearChart();
// When printing, always use a fixed size for the chart
    //chart.draw(data, wnbOptions(300, 200));
});

window.addEventListener('afterprint', (event) => {
// After printing, let's fall back to the screen options
    delete options.width ;
    delete options.height ;
    darkMode = displayDarkMode ;
    //chart.clearChart();
    //chart.draw(data, wnbOptions());
});

window.addEventListener("resize", (event) => {
    //chart.clearChart();
    //chart.draw(data, wnbOptions());
}) ;
</script>
<script>
function openPerformance(evt, cityName) {
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }
  document.getElementById(cityName).style.display = "block";
  evt.currentTarget.className += " active";
}

// Get the element with id="defaultOpen" and click on it
document.getElementById("defaultOpen").click();
</script>
<script src="https://www.spa-aviation.be/resa/mobile_performance.js"></script>
</body>
</html>