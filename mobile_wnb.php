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
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}

$plane = (isset($_REQUEST['plane'])) ? mysqli_real_escape_string($mysqli_link, strtoupper($_REQUEST['plane'])) : '' ;
$body_attributes = "onload=\"init(); prefillDropdownMenus('plane', planes, '$plane');\"" ;
$header_postamble = "<script type=\"text/javascript\" src=\"https://www.gstatic.com/charts/loader.js\"></script>
<script type=\"text/javascript\">
    var gChartLoaded = false ;
    function markGChartLoaded() {
        gChartLoaded = true ;
        drawChart() ;
    }
    google.charts.load('current', {packages:['corechart', 'line']});
    google.charts.setOnLoadCallback(markGChartLoaded);
</script>" ;

require_once 'mobile_header5.php' ;
?>
<div class="container-fluid">
<h2>Devis masse et centrage</h2>
<p class="bg-warning text-bg-warning">Ceci est un outil informatique, le pilote doit toujours vérifier le POH ainsi que le certificat W&B officiel de l'avion.</p>

<div class="row">
<form action="<?=$_SERVER['PHP_SELF']?>" method="GET" role="form" class="form-horizontal">
<div class="row mb-3">
	<label for="planeSelect" class="col-form-label col-sm-4 col-md-2">Plane:</label>
	<div class="col-sm-4 col-md-1">
        <select id="planeSelect" class="form-select" name="plane" onchange="document.location.href='<?=$_SERVER['PHP_SELF']?>?plane=' + this.value ;"></select>
	</div> <!-- col -->
</div> <!-- row -->

<?php
if ($plane == '') {
    print('<div class="row text-warning">Veuillez choisir un avion dans la liste ci-dessus.</div>') ;
    exit ;
}
?>
<table class="table table-stripped table-bordered">
<thead>
<tr><th>Item</th><th>Weight</th><th class="text-end">Weight (lbs)</th><th class="text-end">Arm (inch)</th><th class="text-end">Moment (inch-pound)</th></tr>
</thead>
<tbody class="table-divider">
<?php
$result = mysqli_query($mysqli_link, "SELECT *
    FROM tp_aircraft AS a JOIN tp_aircraft_weights AS w ON a.id = w.tailnumber
    WHERE a.tailnumber = '$plane'
    ORDER BY w.order ASC")
    or journalise($userId, "F", "Cannot read W&B data: " . mysqli_error($mysqli_link)) ;
$rowCount = 0 ;
$density = array() ;
while ($row = mysqli_fetch_array($result)) {
    print("<tr><td>$row[item]</td>") ;
    if ($row['emptyweight'] == 'true') {
        print("<td></td><td  class=\"text-end\"><span id=\"wlb_$row[order]\">$row[weight]</span></td>") ;
        $weight_lbs = $row['weight'] ;
        $density[$row['order']] = 1.0 ; // Empty weight is in pounds
        // Save some aircraft-related values
        $maxWeight = $row['maxwt'] ;
        $cgAft = $row['cgwarnaft'] ;
        $cgFwd = $row['cgwarnfwd'] ;
    } else {
        $readonly = ($row['weigth'] > 0) ? ' readonly' : 'onkeyup="processWnB();"' ;
        print("<td><input type=\text\" id=\"w_$row[order]\" class=\"text-end\" value=\"$row[weight]\" $readonly>") ;
        if ($row['fuel'] == 'true') {
            print("&nbsp;l</td>") ;
            $weight_lbs = round($row['weight'] * $row['fuelwt'], 1) ;
            $density[$row['order']] = $row['fuelwt'] ;
        } else {
            print("&nbsp;kg</td>") ;
            $weight_lbs = round($row['weight'] * 2.20462, 1) ;
            $density[$row['order']] = 2.20462 ;
        }
        print("<td  class=\"text-end\"><span id=\"wlb_$row[order]\">$weight_lbs</span></td>") ;
    }
    print("<td class=\"text-end\"><span id=\"arm_$row[order]\">$row[arm]</span></td>") ;
    print("<td class=\"text-end\"><span id=\"moment_$row[order]\">" . round($weight_lbs * $row['arm'], 1) . "</span></td>") ;
    print("</tr>\n") ;
    $rowCount ++ ;
}
?>
</tbody>
<tfoot class="table-divider">
 <tr><th colspan="2" class="table-info text-end">Totals at take-off</th><td class="table-info text-end"><span id="wlb_total"></span></td><td class="table-info text-end"><span id="arm_total"></span></td><td class="table-info text-end"><span id="moment_total"></span></td></tr>
</tfoot>
</table>

<div class="mt-4 p-5 bg-danger text-bg-danger rounded" style="visibility: hidden; display: none;" id="warningsDiv">
</div>

<div id="chart_div" style="width: 750px; height: 500px;"></div>

</div><!-- container -->

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
    for (var i = 1; i <= rowCount ; i++) { // Unusual loop by order start at 1 in the SQL table...
       var elem = document.getElementById('w_' + i) ;
        if (elem) {
            var weight = parseFloat(elem.value) * density[i];
            document.getElementById('wlb_' + i).innerText = weight.toFixed(2) ;
        }
        totalWeight += parseFloat(document.getElementById('wlb_' + i).innerText) ;
        document.getElementById('arm_' + i).innerText = parseFloat(document.getElementById('arm_' + i).innerText).toFixed(2) ;
        var moment = parseFloat(document.getElementById('wlb_' + i).innerText) * parseFloat(document.getElementById('arm_' + i).innerText) ;
        document.getElementById('moment_' + i).innerText = moment.toFixed(2) ;
        totalMoment += moment ;
    }
    totalArm = totalMoment / totalWeight ;
    document.getElementById('wlb_total').innerText = totalWeight.toFixed(2) ;
    document.getElementById('arm_total').innerText = totalArm.toFixed(2) ;
    document.getElementById('moment_total').innerText = totalMoment.toFixed(2) ;
    document.getElementById('warningsDiv').innerHTML = '' ;
    if (totalWeight > maxWeight) {
        document.getElementById('warningsDiv').innerHTML += "Total weight, " + Math.round(totalWeight) + " pounds, exceeds the maximum weight of " + maxWeight + " pounds.<br/>" ;
        document.getElementById('warningsDiv').style.visibility = 'visible' ; 
        document.getElementById('warningsDiv').style.display = 'block' ;
    }
    if (cgFwd > totalArm) {
        document.getElementById('warningsDiv').innerHTML += "Global arm, " + totalArm.toFixed(2) + " in, is below the minimum Fwd moment of " + cgFwd + " in.<br/>" ;
        document.getElementById('warningsDiv').style.visibility = 'visible' ;
        document.getElementById('warningsDiv').style.display = 'block' ;
    }
    if (totalArm > cgAft) {
        document.getElementById('warningsDiv').innerHTML += "Global arm, " + totalArm.toFixed(2) + " in, is beyond the maximum Aft moment of " + cgAft + " in.<br/>" ;
        document.getElementById('warningsDiv').style.visibility = 'visible' ;
        document.getElementById('warningsDiv').style.display = 'block' ;
    }
    if (gChartLoaded) {
        drawChart() ;
    }
}

// https://developers.google.com/chart/interactive/docs/gallery/scatterchart
// https://stackoverflow.com/questions/42891409/draw-a-line-on-google-charts-scatterchart
var data ;

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
        data.addRow([parseFloat(document.getElementById('arm_total').innerText), null, parseFloat(document.getElementById('wlb_total').innerText)]) ;
        var options = {
          title: 'Flight envelope',
          hAxis: {title: 'Inches From Reference Datum', minValue: <?=$minArmValue?>, maxValue: <?=$maxArmValue?>},
          vAxis: {title: 'Weight (pounds)', minValue: <?=$minWeightValue?>, maxValue: <?=$maxWeightValue?>},
          series: {
            0: {lineWidth: 5, pointSize: 0} ,
            1: {lineWidth: 0, pointSize: 15}
          },
          legend: 'Flight envelope'
        };

        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }

processWnB() ;
</script>

</body>
</html>