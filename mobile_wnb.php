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
$body_attributes = "style=\"height: 100%; min-height: 100%; width:100%;\" onload=\"init(); prefillDropdownMenus('plane', planes, '$plane');\"" ;
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
?>

<div class="container-fluid">
<!--div class="container-fluid" style="height: 100%!important;overflow:auto;top:0;bottom:0;left:0;right:0;position:fixed;"-->
<!--div class="container-fluid" style="height: 100%!important;"-->
<!--div class="container vh-90" style="height: 80vh!important;width: 100vw!important;bottom:0!important;left:0!important;right:0!important;"-->

<div class="row">
<div class="col-xs-12 col-sm-12 col-lg-6">
<h2 class="d-none d-md-block">Devis masse et centrage <?=$plane?></h2>
<form action="<?=$_SERVER['PHP_SELF']?>" method="GET" role="form" class="form-horizontal">
<!--div class="row m-0-xs"-->
	<label for="planeSelect" class="col-form-label col-xs-1 col-md-1">Plane:</label>
    <select id="planeSelect" class="col-form-select col-xs-1" name="plane" onchange="document.location.href='<?=$_SERVER['PHP_SELF']?>?displayallcolumns=<?print($displayAllColumns);?>&plane=' + this.value ;"></select>
<!--/div> <!-- row -->

<?php
if ($plane == '') {
    print('<div class="row text-warning">Veuillez choisir un avion dans la liste ci-dessus.</div>') ;
    exit ;
}
?>
<table class="table table-striped table-hover table-bordered table-condensed w-auto" style="margin-bottom: 0rem;">
<thead>
<tr><th <?php print("$itemWidth"); ?> class="text-end py-0 py-md-1">Item</th>
    <th class="py-0 py-md-1 py-md-1" >Weight</th>
    <th <?php print("$hiddenTag"); ?> class="py-md-1">Weight (pound)</th>
    <th <?php print("$hiddenTag"); ?> class="py-md-1">Arm (inch)</th>
    <th <?php print("$hiddenTag"); ?> class="py-md-1">Moment (inch-pound)</th>
</tr>
</thead>
<tbody class="table-divider">
<?php
// Should use some  d-none d-md-block or similar...
$result = mysqli_query($mysqli_link, "SELECT *, MIN(w.order) AS line_order, GROUP_CONCAT(w.item SEPARATOR '+') AS line_item
    FROM tp_aircraft AS a JOIN tp_aircraft_weights AS w ON a.id = w.tailnumber
    WHERE a.tailnumber = '$plane'
    GROUP BY w.arm
    ORDER BY line_order, w.order ASC")
    or journalise($userId, "F", "Cannot read W&B data: " . mysqli_error($mysqli_link)) ;
$rowCount = 0 ;
$density = array() ;
while ($row = mysqli_fetch_array($result)) {
    $item=$row['line_item'];
    if($item=="Co-Pilot+Pilot") {
        $item="Pilot + Co-Pilot";
    }
    if($item=="Pilot+Co-Pilot") {
        $item="Pilot + Co-Pilot";
    }
    else if($item=="Passenger 1+Passenger 2") {
        $item="Rear Passengers";
    }
    else if($item=="Passenger 2+Passenger 1") {
        $item="Rear Passengers";
    }
    else if($item=="Basic empty weight") {
        $item="Empty Weight";
    }
    print("<tr><td class=\"text-end py-0 py-md-1\">$item</td>") ;
    if ($row['emptyweight'] == 'true') {
        print("<td class=\"py-0 py-md-1\">" . round($row['weight'] / 2.20462,1) . "&nbsp;kg</td>");
		print("<td $hiddenTag class=\"py-md-1\"><span id=\"wlb_$rowCount\">$row[weight]</span></td>") ;
        $weight_lbs = $row['weight'] ;
        $density[$rowCount] = 1.0 ; // Empty weight is in pounds
        // Save some aircraft-related values
        $maxWeight = $row['maxwt'] ;
        $cgAft = $row['cgwarnaft'] ;
        $cgFwd = $row['cgwarnfwd'] ;
    } else {
        $readonly = ($row['weight'] > 0) ? ' readonly' : 'oninput="processWnB();"' ;
        $readonly = 'oninput="processWnB();"' ;
        print("<td class=\"py-0\"><input type=\"number\" id=\"w_$rowCount\" class=\"text-end py-0 py-md-1\" value=\"$row[weight]\" style=\"width: 80%;\" $readonly>") ;
        if ($row['fuel'] == 'true') {
            print("&nbsp;l</td>") ;
            $weight_lbs = round($row['weight'] * $row['fuelwt'], 1) ;
            $density[$rowCount] = $row['fuelwt'] ;
        } else {
            print("&nbsp;kg</td>") ;
            $weight_lbs = round($row['weight'] * 2.20462, 1) ;
            $density[$rowCount] = 2.20462 ;
        }
        print("<td $hiddenTag class=\"py-md-1\"><span id=\"wlb_$rowCount\">$weight_lbs</span></td>") ;
    }
    print("<td $hiddenTag class=\"py-md-1\"><span id=\"arm_$rowCount\">$row[arm]</span></td>") ;
    print("<td $hiddenTag class=\"py-md-1 \"><span id=\"moment_$rowCount\">" . round($weight_lbs * $row['arm'], 1) . "</span></td>") ;
    print("</tr>\n") ;
    $rowCount ++ ;
}
?>
</tbody>
<tfoot class="table-divider">
    <tr>
        <th class="table-info text-end text-start py-0 py-md-1">Totals</th>
        <td class="table-info text-start py-0 py-md-1"><span id="w_total"></span>&nbsp;kg</td>
        <td <?php print("$hiddenTag"); ?> class="py-md-1"><span id="wlb_total"></span></td>
        <td <?php print("$hiddenTag"); ?> class="py-md-1"><span id="arm_total"></span></td>
        <td <?php print("$hiddenTag"); ?> class="py-md-1 table-info"><span id="moment_total"></span></td>
    </tr>
</tfoot>
</table>
<span class="d-none d-md-block py-0" >
<?php
$checkedItem="";
if($displayAllColumns=='true') {
 $checkedItem="checked";
}
print ("<input type=\"checkbox\" id=\"id_AllColumns\" name=\"name_AllColumns\" value=\"All columns\" onclick=\"document.location.href='$_SERVER[PHP_SELF]?plane=$plane&displayallcolumns=' + this.checked ;\" $checkedItem >");
?>
<label for="name_AllColumns">&nbsp;Optional columns</label>
</span>
</form>

<div class="mt-2 p-2 bg-danger text-bg-danger rounded" style="visibility: hidden; display: none;" id="warningsDiv">
</div>

</div><!--col-->

<!-- should try to use fixed aspect ration with CSS: aspect-ration: 4 / 3 or padding-top: 75% to replace the height setting 
using aspect-ratio makes printing over two pages... 
using padding-top also prints over 2 pages and makes the display ultra small-->
<div class="col-xs-12 col-sm-12 col-lg-6" style="margin: auto;">
<div id="chart_div" style="width: 60vw; height: 50vw; margin: auto; ">... loading ...</div>
</div><!--col-->

</div><!--row-->

<p class="d-none d-md-block bg-warning text-bg-warning mx-auto fs-6" style="height: 20px; position: fixed; margin:0; bottom: 0px;">
    <small>Ceci est un simple outil informatique, le pilote doit toujours vérifier le POH ainsi que le certificat W&B officiel de l'avion.
</small></p>

</div><!-- container-fluid -->
<script type="text/javascript">
    var rowCount = <?=$rowCount?>, maxWeight = <?=$maxWeight?>, cgAft = <?=$cgAft?>, cgFwd = <?=$cgFwd?>, density = [] ;
<?php
    foreach($density as $i=>$d)
        print("\tdensity[$i] = $d ;\n") ;
?>

function processWnB() {
    var totalWeight, totalMoment ;

    totalWeight = 0.0 ;
    totalMoment = 0.0 ;
    document.getElementById('warningsDiv').style.visibility = 'hidden' ;
    document.getElementById('warningsDiv').style.display = 'none' ;
    document.getElementById('wlb_total').parentNode.classList.remove('table-danger') ; 
    document.getElementById('wlb_total').parentNode.classList.add('table-info') ; 
    document.getElementById('arm_total').parentNode.classList.remove('table-danger') ; 
    document.getElementById('arm_total').parentNode.classList.add('table-info') ; 
    for (var i = 0; i < rowCount ; i++) { 
       var elem = document.getElementById('w_' + i) ;
        if (elem) {
            var weight = parseFloat(elem.value) * density[i];
            document.getElementById('wlb_' + i).innerText = weight.toFixed(1) ;
        }
        if (! document.getElementById('wlb_' + i)) continue ;
        totalWeight += parseFloat(document.getElementById('wlb_' + i).innerText) ;
        document.getElementById('arm_' + i).innerText = parseFloat(document.getElementById('arm_' + i).innerText).toFixed(1) ;
        var moment = parseFloat(document.getElementById('wlb_' + i).innerText) * parseFloat(document.getElementById('arm_' + i).innerText) ;
        document.getElementById('moment_' + i).innerText = moment.toFixed(1) ;
        totalMoment += moment ;
    }
    totalArm = totalMoment / totalWeight ;
    document.getElementById('w_total').innerText = (totalWeight / 2.20462).toFixed(1) ;
    document.getElementById('wlb_total').innerText = totalWeight.toFixed(1) ;
    document.getElementById('arm_total').innerText = totalArm.toFixed(1) ;
    document.getElementById('moment_total').innerText = totalMoment.toFixed(1) ;
    document.getElementById('warningsDiv').innerHTML = '' ;
    if (totalWeight > maxWeight) {
        document.getElementById('warningsDiv').innerHTML += "Total weight, " + Math.round(totalWeight/2.20462) + " kg = " + 
                Math.round(totalWeight) + " pounds, exceeds the maximum weight of " +  + Math.round(maxWeight/2.20462) + " kg = " + maxWeight + " pounds.<br/>" ;
        document.getElementById('warningsDiv').style.visibility = 'visible' ; 
        document.getElementById('warningsDiv').style.display = 'block' ;
        document.getElementById('wlb_total').parentNode.classList.remove('table-info') ; 
        document.getElementById('wlb_total').parentNode.classList.add('table-danger') ; 
    }
    if (cgFwd > totalArm) {
        document.getElementById('warningsDiv').innerHTML += "Global arm, " + totalArm.toFixed(1) + " in, is below the minimum Fwd moment of " + cgFwd + " in.<br/>" ;
        document.getElementById('warningsDiv').style.visibility = 'visible' ;
        document.getElementById('warningsDiv').style.display = 'block' ;
        document.getElementById('arm_total').parentNode.classList.remove('table-info') ; 
        document.getElementById('arm_total').parentNode.classList.add('table-danger') ; 
    }
    if (totalArm > cgAft) {
        document.getElementById('warningsDiv').innerHTML += "Global arm, " + totalArm.toFixed(1) + " in, is beyond the maximum Aft moment of " + cgAft + " in.<br/>" ;
        document.getElementById('warningsDiv').style.visibility = 'visible' ;
        document.getElementById('warningsDiv').style.display = 'block' ;
        document.getElementById('arm_total').parentNode.classList.remove('table-info') ; 
        document.getElementById('arm_total').parentNode.classList.add('table-danger') ; 
    }
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
        data.addRow([parseFloat(document.getElementById('arm_total').innerText), null, Math.round(parseFloat(document.getElementById('wlb_total').innerText) / 2.20462)]) ;
        options = {
          title: 'Flight envelope <?=$plane?>',
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
        };

        chart = new google.visualization.LineChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }

processWnB() ;

window.addEventListener('beforeprint', (event) => {
// When printing, always use a fixed size for the chart
  options.width = 1000 ;
  options.height = 600 ;
  chart.clearChart();
  chart.draw(data, options);
});

window.addEventListener('afterprint', (event) => {
// After printing, let's fall back to the screen options
    delete options.width ;
    delete options.height ;
    chart.clearChart();
    chart.draw(data, options);
});

window.addEventListener("resize", (event) => {
    chart.clearChart();
    chart.draw(data, options);
}) ;
</script>
</body>
</html>