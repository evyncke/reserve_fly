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
$header_postamble = "<script type=\"text/javascript\" src=\"https://www.gstatic.com/charts/loader.js\"></script>
<script type=\"text/javascript\">
    var gChartLoaded = false ;
    function markGChartLoaded() {
        gChartLoaded = true ;
        drawChart() ;
    }
    google.charts.load('current', {packages:['corechart', 'line']});
    google.charts.setOnLoadCallback(markGChartLoaded);
</script>
" ;

require_once 'mobile_header5.php' ;
$performanceJSONcontent = file_get_contents('https://www.spa-aviation.be/resa/mobile_performance.json') ;
$performanceJSONcontent = str_replace("\n","",$performanceJSONcontent);
print("<script>\nvar performanceJSONcontent='$performanceJSONcontent';");
print("var default_member=$userId;\n");
print("</script>\n");
?>

<div class="container-fluid">
<!--div class="container-fluid" style="height: 100%!important;overflow:auto;top:0;bottom:0;left:0;right:0;position:fixed;"-->
<!--div class="container-fluid" style="height: 100%!important;"-->
<!--div class="container vh-90" style="height: 80vh!important;width: 100vw!important;bottom:0!important;left:0!important;right:0!important;"-->

<div class="row">
<div class="col-xs-12 col-sm-12 col-lg-6">
<h2 class="d-none d-md-block">Calcul Performance <?=$plane?></h2>
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
<table class="table table-striped table-hover table-bordered table-condensed w-auto" style="margin-bottom: 0rem;">
<thead>
<tr><th width="25%"; class="text-end py-0 py-md-1">Input</th>
    <th width="75%"; class="py-0 py-md-1 py-md-1" >Value</th>
</tr>
</thead>
<tbody class="table-divider">
<?php
    //$readonly = 'oninput="processPerformance();"' ;
    $readonly = '' ;
    //QNH
    print("<tr><td class=\"text-end py-0 py-md-1\">QNH</td>") ;
    print("<td class=\"py-0\"><input type=\"number\" id=\"id_takeoff_i_qnh\" class=\"text-end py-0 py-md-1\" value=\"1013\" style=\"width: 80%;\" $readonly>") ;
    print("&nbsp;hPa</td>") ;
    //Altitude
    print("<tr><td class=\"text-end py-0 py-md-1\">Altitude</td>") ;
    print("<td class=\"py-0\"><input type=\"number\" id=\"id_takeoff_i_altitude\" class=\"text-end py-0 py-md-1\" value=\"1600\" style=\"width: 80%;\" $readonly>") ;
    print("&nbsp;ft</td>") ;
    //Température
    print("<tr><td class=\"text-end py-0 py-md-1\">Température</td>") ;
    print("<td class=\"py-0\"><input type=\"number\" id=\"id_takeoff_i_temperature\" class=\"text-end py-0 py-md-1\" value=\"20\" style=\"width: 80%;\" $readonly>") ;
    print("&nbsp;C</td>") ;
    //Piste
    print("<tr><td class=\"text-end py-0 py-md-1\">Piste</td>") ;
    print("<td class=\"py-0\"><input type=\"number\" id=\"id_takeoff_i_runway_number\" class=\"text-end py-0 py-md-1\" value=\"23\" style=\"width: 80%;\" $readonly>") ;
    print("&nbsp;</td>") ;
     //Type Piste
    print("<tr><td class=\"text-end py-0 py-md-1\">Type Piste</td>") ;
    print("<td class=\"py-0\"><select id=\"id_takeoff_i_runway_type\"  name=\"takeoff_i_runway_type\" $readonly>></select>");
    //print("<td class=\"py-0\"><input type=\"text\" id=\"id_takeoff_i_runway_type\" class=\"text-end py-0 py-md-1\" value=\"asphalt\" style=\"width: 80%;\" $readonly>") ;
    print("&nbsp;</td>") ;
     //Pente Piste
    print("<tr><td class=\"text-end py-0 py-md-1\">Pente Piste</td>") ;
    print("<td class=\"py-0\"><input type=\"number\" id=\"id_takeoff_i_runway_slope\" class=\"text-end py-0 py-md-1\" value=\"1\" style=\"width: 80%;\" $readonly>") ;
    print("&nbsp;%</td>") ;
    //Direction Vent
    print("<tr><td class=\"text-end py-0 py-md-1\">Direction vent</td>") ;
    print("<td class=\"py-0\"><input type=\"number\" id=\"id_takeoff_i_wind_direction\" class=\"text-end py-0 py-md-1\" value=\"230\" style=\"width: 80%;\" $readonly>") ;
    print("&nbsp;degré</td>") ;
    //Vitesse Vent
    print("<tr><td class=\"text-end py-0 py-md-1\">Vitesse vent</td>") ;
    print("<td class=\"py-0\"><input type=\"number\" id=\"id_takeoff_i_wind_speed\" class=\"text-end py-0 py-md-1\" value=\"10\" style=\"width: 80%;\" $readonly>") ;
    print("&nbsp;kt</td>") ;
   //Poids
    print("<tr><td class=\"text-end py-0 py-md-1\">Poids</td>") ;
    print("<td class=\"py-0\"><input type=\"number\" id=\"id_takeoff_i_weight\" class=\"text-end py-0 py-md-1\" value=\"720\" style=\"width: 80%;\" $readonly>") ;
    print("&nbsp;kg</td>") ;
  //Flaps
    print("<tr><td class=\"text-end py-0 py-md-1\">Flaps</td>") ;
    print("<td class=\"py-0\"><input type=\"number\" id=\"id_takeoff_i_flaps\" class=\"text-end py-0 py-md-1\" value=\"0\" style=\"width: 80%;\" $readonly>") ;
    print("&nbsp;degres</td>") ;
  //Pilot skill
    print("<tr><td class=\"text-end py-0 py-md-1\">Pilot Skill</td>") ;
    print("<td class=\"py-0\"><select id=\"id_takeoff_i_pilot_skill\"  name=\"id_takeoff_i_pilot_skill\ $readonly>></select>");
    print("</td>") ;
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
</form>

<div class="mt-2 p-2 bg-danger text-bg-danger rounded" style="visibility: hidden; display: none;" id="warningsDiv">
</div>

</div><!--col-->

<!-- should try to use fixed aspect ration with CSS: aspect-ration: 4 / 3 or padding-top: 75% to replace the height setting 
using aspect-ratio makes printing over two pages... 
using padding-top also prints over 2 pages and makes the display ultra small-->
<div class="col-xs-12 col-sm-12 col-lg-6" style="margin: auto;">
<table class="table table-striped table-hover table-bordered table-condensed w-auto" style="margin-bottom: 0rem;">
<thead>
<tr><th width="25%"; class="text-end py-0 py-md-1">Result</th>
    <th width="75%"; class="py-0 py-md-1 py-md-1" >Value</th>
</tr>
</thead>
<tbody class="table-divider">
</tbody>
<!--Pressure Altitude-->
<tr><td class="text-end py-0 py-md-1">Pressure Altitude</td>
    <td class="py-0\"><input type="number" id="id_takeoff_o_pressure_altitude" class="text-end py-0 py-md-1" value="2345" >
    &nbsp;ft&nbsp;&#9432;</td>
</tr>
<!--Density Altitude-->
<tr><td class="text-end py-0 py-md-1">Density Altitude</td>
    <td class="py-0\"><input type="number" id="id_takeoff_o_density_altitude" class="text-end py-0 py-md-1" value="2345" >
    &nbsp;ft&nbsp;&#9432;</td>
</tr>
<!--Temperature ISA-->
<tr><td class="text-end py-0 py-md-1">Temperature ISA</td>
    <td class="py-0\"><input type="number" id="id_takeoff_o_temperature_isa" class="text-end py-0 py-md-1" value="2345" >
    &nbsp;C&nbsp;&#9432;</td>
<!--Delta Temperature ISA-->
<tr><td class="text-end py-0 py-md-1">Delta Temperature ISA</td>
    <td class="py-0\"><input type="number" id="id_takeoff_o_temperature_delta_isa" class="text-end py-0 py-md-1" value="2345" >
    &nbsp;C&nbsp;&#9432;</td>
</tr>
<!--Head wind-->
<tr><td class="text-end py-0 py-md-1">Head Wind</td>
    <td class="py-0\"><input type="number" id="id_takeoff_o_head_wind_speed" class="text-end py-0 py-md-1" value="99" >
    &nbsp;kt&nbsp;&#9432;</td>
</tr>
<!--Cross wind-->
<tr><td class="text-end py-0 py-md-1">Cross Wind</td>
    <td class="py-0\"><input type="number" id="id_takeoff_o_cross_wind_speed" class="text-end py-0 py-md-1" value="99" >
    &nbsp;kt&nbsp;&#9432;</td>
</tr>
<!--Roll IAS-->
<tr><td class="text-end py-0 py-md-1">Roll IAS</td>
    <td class="py-0\"><input type="number" id="id_takeoff_o_ias_roll" class="text-end py-0 py-md-1" value="50">
    &nbsp;MPH&nbsp;&#9432;</td>
</tr>
<!--Roll-out Distance-->
<tr><td class="text-end py-0 py-md-1">Roll Distance</td>
    <td class="py-0\"><input type="number" id="id_takeoff_o_distance_roll" class="text-end py-0 py-md-1" value="234" >
    &nbsp;m&nbsp;&#9432;</td>
</tr>
<!--50ft IAS-->
<tr><td class="text-end py-0 py-md-1">50ft IAS</td>
    <td class="py-0\"><input type="number" id="id_takeoff_o_ias_50ft" class="text-end py-0 py-md-1" value="50" >
    &nbsp;MPH&nbsp;&#9432;</td>
</tr>
<!--50ft Distance-->
<tr><td class="text-end py-0 py-md-1">50ft Distance</td>
    <td class="py-0\"><input type="number" id="id_takeoff_o_distance_50ft" class="text-end py-0 py-md-1" value="567">
    &nbsp;m&nbsp;&#9432;</td>
</tr>
<!--Max RoC-->
<tr><td class="text-end py-0 py-md-1">Max RoC</td>
    <td class="py-0\"><input type="number" id="id_takeoff_o_max_roc" class="text-end py-0 py-md-1" value="200" >
    &nbsp;ft/min&nbsp;&#9432;</td>
</tr>
<!--Best Angle IAS-->
<tr><td class="text-end py-0 py-md-1">Best Angle IAS</td>
    <td class="py-0\"><input type="number" id="id_takeoff_o_ias_best_angle" class="text-end py-0 py-md-1" value="50" >
    &nbsp;MPH&nbsp;&#9432;</td>
</tr>
</table>
<div id="chart_div" style="width: 60vw; height: 50vw; margin: auto;">... loading ...</div>
</div><!--col-->

</div><!--row-->

<p class="d-none d-md-block text-bg-warning mx-auto fs-6" style="height: 20px; position: fixed; margin:0; bottom: 0px;">
    <small>Ceci est un simple outil informatique, le pilote doit toujours vérifier le POH ainsi que le certificat W&B officiel de l'avion.
</small></p>

</div><!-- container-fluid -->
<script type="text/javascript">
    var rowCount = 7, density = [], 
        darkMode = 	(decodeURIComponent(document.cookie).search('theme=dark') >= 0), displayDarkMode = darkMode ;

function processPerformance() {
    var totalWeight, totalMoment ;

    totalWeight = 0.0 ;
    totalMoment = 0.0 ;
   
    if (gChartLoaded) {
        drawChart() ;
    }
}

// https://developers.google.com/chart/interactive/docs/gallery/scatterchart
// https://stackoverflow.com/questions/42891409/draw-a-line-on-google-charts-scatterchart
var data, chart, options ;

function drawChart() {
    data = new google.visualization.DataTable() ;
    data.addColumn('number', 'Weight');
    data.addColumn('number', 'CG Envelope');
    data.addColumn('number', 'Takeoff');
    data.addRows([
        <?php
$result = mysqli_query($mysqli_link, "SELECT *
    FROM tp_aircraft AS a
    JOIN tp_aircraft_cg AS c ON c.tailnumber = a.id
    WHERE a.tailnumber = '$plane'
    ORDER BY c.id ASC")
    or journalise($userId, "F", "Cannot read CG envelope data: " . mysqli_error($mysqli_link)) ;
$minArmValue = PHP_INT_MAX ;
$maxArmValue = PHP_INT_MIN ;
$minWeightValue = PHP_INT_MAX ;
$maxWeightValue = PHP_INT_MIN ;
$firstArm = null ;
$firstWeight = null ;
while ($row = mysqli_fetch_array($result)) {
    // Convert in kg
    $row['weight'] = round($row['weight'] / 2.20462) ;
    if (!$firstArm) $firstArm = $row['arm'] ;
    if (!$firstWeight) $firstWeight = $row['weight'] ;
    if ($row['arm'] < $minArmValue) $minArmValue = $row['arm'] ;
    if ($row['arm'] > $minArmValue) $maxArmValue = $row['arm'] ;
    if ($row['weight'] < $minWeightValue) $minWeightValue = $row['weight'] ;
    if ($row['weight'] > $minWeightValue) $maxWeightValue = $row['weight'] ;
    print("\t[$row[arm], $row[weight], null],\n") ;
}
// Finish by going back to first point
print("\t[$firstArm, $firstWeight, null]\n") ;
?>
        ]);
    chart = new google.visualization.LineChart(document.getElementById('chart_div'));
    chart.draw(data, wnbOptions());
}

function wnbOptions(width, height) { // generate the chart options based on the darkTheme and the previously computed boundaries
    options = {
          title: 'Tak-off Result <?=$plane?>',
          hAxis: {title: 'Inches From Reference Datum', minValue: <?=$minArmValue?>, maxValue: <?=$maxArmValue?>},
          vAxes: { // Serie 1 was for weigth in pounds, but, cannot manage to them 
            0: { title: 'Weight (kg)', textPosition: 'out', viewWindowMode: 'maximized', minValue: <?=$minWeightValue?>, maxValue: <?=$maxWeightValue?>},
            1: { title: 'Weight (pounds)', textPosition: 'out', viewWindowMode: 'maximized', minValue: <?=round($minWeightValue/2.20462)?>, maxValue: <?=round($maxWeightValue/2.20462)?>}
          },
          series: {
            0: {lineWidth: 5, pointSize: 0, visibleInLegend: true} ,
            1: {lineWidth: 0, pointSize: 15, visibleInLegend: true}
          },
          legend: {position: 'bottom'},
          backgroundColor: {fill: 'white'},
        };
    if (darkMode) {
        options.backgroundColor.fill = '#212529' ;
        options.legend.textStyle = {color: '#dee2e6'} ;
        options.titleTextStyle = {color: '#dee2e6'} ;
        options.hAxis.titleTextStyle = {color: '#dee2e6'} ;
        options.hAxis.textStyle = {color: '#dee2e6'} ;
        options.vAxes[0].titleTextStyle = {color: '#dee2e6'} ;
        options.vAxes[0].textStyle = {color: '#dee2e6'} ;
    }
    if (typeof width !== 'undefined') options.width = width ;
    if (typeof height !== 'undefined') options.height = height ;
    return options ;
}

processPerformance() ;

window.addEventListener('beforeprint', (event) => {
    displayDarkMode = darkMode ; // Save the dark mode
    darkMode = false ;
    chart.clearChart();
// When printing, always use a fixed size for the chart
    chart.draw(data, wnbOptions(300, 200));
});

window.addEventListener('afterprint', (event) => {
// After printing, let's fall back to the screen options
    delete options.width ;
    delete options.height ;
    darkMode = displayDarkMode ;
    chart.clearChart();
    chart.draw(data, wnbOptions());
});

window.addEventListener("resize", (event) => {
    chart.clearChart();
    chart.draw(data, wnbOptions());
}) ;
</script>
<script src="https://www.spa-aviation.be/resa/mobile_performance.js"></script>
</body>
</html>