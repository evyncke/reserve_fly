<?php
/*
   Copyright 2025-2026 Patrick Reginster

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
$body_attributes = 'style="height: 100%; min-height: 100%; width:100%;" onload="init();mobile_planeur_page_loaded();"' ;
$header_postamble = "
<script type=\"text/javascript\" src=\"https://www.gstatic.com/charts/loader.js\"></script>
<link rel=\"stylesheet\" type=\"text/css\" href=\"css/mobile_performance.css\">
</script>
" ;

require_once 'mobile_header5.php' ;

print("<script>\n");
print("var default_member=$userId;\n");
print("</script>\n");
?>
<h2 class="d-none d-md-block">Glider Info</h2>
<div class="tab">
  <button class="tablinks" onclick="openPerformance(event, 'Altitude')" id="defaultOpen">Altitude</button>
  <button class="tablinks" onclick="openPerformance(event, 'Position')">Position</button>
</div>

<!---tabcontent Altitude-->
<div id="Altitude" class="tabcontent">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 col-sm-12 col-lg-6"> <!--- col -->
                <h2 class="d-none d-md-block">Altitudes QNH et QFE</h2>
                <table class="table table-striped table-hover table-bordered table-condensed w-auto" style="margin-bottom: 0rem;">
                <thead>
                <tr><th class="text-end py-0 py-md-1">Input</th>
                    <th class="py-0 py-md-1 py-md-1">Value</th>
                </tr>
                </thead>
                <tbody class="table-divider">
                <?php
                    $readonly = '' ;
                    //Airport
                    print("<tr><td class=\"text-end py-0 py-md-1\">Airport</td>") ;
                    print("<td class=\"py-0\"><input type=\"text\" id=\"id_glider_i_station\" class=\"text-end py-0 py-md-1\" value=\"EBSP\" style=\"width: 60%;\" $readonly>") ;
                    print("&nbsp;<span id=\"id_glider_i_station/unit\">xx</span></td>") ;
                   //QNH
                    print("<tr><td class=\"text-end py-0 py-md-1\">QNH</td>") ;
                    print("<td class=\"py-0\"><input type=\"number\" id=\"id_glider_i_qnh\" class=\"text-end py-0 py-md-1\" value=\"1013\" style=\"width: 60%;\" $readonly>") ;
                    print("&nbsp;<span id=\"id_glider_i_qnh/unit\">xx</span></td>") ;
                    //Altitude
                    print("<tr><td class=\"text-end py-0 py-md-1\">Altitude</td>") ;
                    print("<td class=\"py-0\"><input type=\"number\" id=\"id_glider_i_altitude\" class=\"text-end py-0 py-md-1\" value=\"1600\" style=\"width: 60%;\" $readonly>") ;
                    print("&nbsp;<span id=\"id_glider_i_altitude/unit\">xx</span></td>") ;
                    //Température
                    print("<tr><td class=\"text-end py-0 py-md-1\">Température</td>") ;
                    print("<td class=\"py-0\"><input type=\"number\" id=\"id_glider_i_temperature\" class=\"text-end py-0 py-md-1\" value=\"20\" style=\"width: 60%;\" $readonly>") ;
                    print("&nbsp;<span id=\"id_glider_i_temperature/unit\">xx</span></td>") ;
                 ?>
                </tbody>
                <tfoot class="table-divider">
                    <tr>
                    </tr>
                </tfoot>
                </table>
                <p></p>
                <div class="mt-2 p-2 bg-danger text-bg-danger rounded" style="visibility: hidden; display: none;" id="warningsDiv">
                </div>

            </div><!--col-->

            <!-- should try to use fixed aspect ration with CSS: aspect-ration: 4 / 3 or padding-top: 75% to replace the height setting 
            using aspect-ratio makes printing over two pages... 
            using padding-top also prints over 2 pages and makes the display ultra small-->
            <div class="col-12 col-sm-12 col-lg-6"> <!--- Row COL-->
                <table class="table table-striped table-hover table-bordered table-condensed w-auto" style="margin-bottom: 0rem;">
                <thead>
                <tr><th class="text-end py-0 py-md-1">Result</th>
                    <th class="py-0 py-md-1 py-md-1" >Value</th>
                </tr>
                </thead>
                <tbody class="table-divider">
                <!--Density Altitude-->
                <tr><td class="text-end py-0 py-md-1">Density Altitude</td>
                    <td class="py-0\"><span id="id_glider_o_density_altitude">xx</span>
                    &nbsp;<span id="id_glider_o_density_altitude/unit">xx</span>&nbsp;<span class="tooltip">&#9432;<span id="id_glider_o_density_altitude/tooltip" class='tooltiptext'>tooltip</span></span></td>
                </tr>
                </tbody>
                </table>
                <p></p>
                <table class="table table-striped table-hover table-bordered table-condensed w-auto" style="margin-bottom: 0rem;">
                    <thead>
                        <tr><th class="text-end py-0 py-md-1">Result</th>
                            <th class="py-0 py-md-1 py-md-1" >QFE</th>
                            <th class="py-0 py-md-1 py-md-1" >QNH</th>
                        </tr>
                    </thead>
                    <tbody class="table-divider">
                        <!--4500ft-->
                        <tr><td class="text-end py-0 py-md-1">4500ft</td>
                            <td class="py-0\"><span id="id_glider_o_altitude_4500ft_qfe">xx</span>
                            &nbsp;<span id="id_glider_o_altitude_4500ft_qfe/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_glider_o_4500ft_qfe/tooltip" class='tooltiptext'>tooltip</span></span></td>
                            <td class="py-0\"><span id="id_glider_o_altitude_4500ft_qnh">xx</span>
                            &nbsp;<span id="id_glider_o_altitude_4500ft_qnh/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_glider_o_4500ft_qnh/tooltip" class='tooltiptext'>tooltip</span></span></td>
                        </tr>
                        <!--FL65 -->
                        <tr><td class="text-end py-0 py-md-1">G3-GA FL65</td>
                            <td class="py-0\"><span id="id_glider_o_altitude_fl65_qfe">xx</span>
                            &nbsp;<span id="id_glider_o_altitude_fl65_qfe/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_glider_o_fl65_qfe/tooltip" class='tooltiptext'>tooltip</span></span></td>
                            <td class="py-0\"><span id="id_glider_o_altitude_fl65_qnh">xx</span>
                            &nbsp;<span id="id_glider_o_altitude_fl65_qnh/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_glider_o_fl65_qnh/tooltip" class='tooltiptext'>tooltip</span></span></td>
                        </tr>
                        <!-- FL55 -->
                        <tr><td class="text-end py-0 py-md-1">G1 FL55</td>
                            <td class="py-0\"><span id="id_glider_o_altitude_fl55_qfe">xx</span>
                            &nbsp;<span id="id_glider_o_altitude_fl55_qfe/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_glider_o_fl55_qfe/tooltip" class='tooltiptext'>tooltip</span></span></td>
                            <td class="py-0\"><span id="id_glider_o_altitude_fl55_qnh">xx</span>
                            &nbsp;<span id="id_glider_o_altitude_fl55_qnh/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_glider_o_fl55_qnh/tooltip" class='tooltiptext'>tooltip</span></span></td>
                        </tr>
                        <!--FL75-->
                        <tr><td class="text-end py-0 py-md-1">G2 FL75</td>
                            <td class="py-0\"><span id="id_glider_o_altitude_fl75_qfe">xx</span>
                            &nbsp;<span id="id_glider_o_altitude_fl75_qfe/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_glider_o_fl75_qfe/tooltip" class='tooltiptext'>tooltip</span></span></td>
                            <td class="py-0\"><span id="id_glider_o_altitude_fl75_qnh">xx</span>
                            &nbsp;<span id="id_glider_o_altitude_fl75_qnh/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_glider_o_fl75_qnh/tooltip" class='tooltiptext'>tooltip</span></span></td>
                        </tr>
                        <!--FL95-->
                        <tr><td class="text-end py-0 py-md-1">G5 FL95</td>
                            <td class="py-0\"><span id="id_glider_o_altitude_fl95_qfe">xx</span>
                            &nbsp;<span id="id_glider_o_altitude_fl95_qfe/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_glider_o_fl95_qfe/tooltip" class='tooltiptext'>tooltip</span></span></td>
                            <td class="py-0\"><span id="id_glider_o_altitude_fl95_qnh">xx</span>
                            &nbsp;<span id="id_glider_o_altitude_fl95_qnh/unit">xx</span>&nbsp; <span class="tooltip">&#9432;<span id="id_glider_o_fl95_qnh/tooltip" class='tooltiptext'>tooltip</span></span></td>
                        </tr>
                    </tbody>
                </table>
            </div> <!-- col -->
        </div><!--row-->

        <p class="d-none d-md-block text-bg-warning mx-auto fs-6" style="height: 20px; position: fixed; margin:0; bottom: 0px;">
            <small>Ceci est un simple outil informatique, le pilote doit toujours vérifier le calcul.
        </small></p>

    </div><!-- container-fluid -->
</div> <!---tabcontent TakeOff-->

<div id="Position" class="tabcontent">
   <div class="container-fluid">
   </div> 
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
<script src="<?= SITE_URL ?>js/mobile_planeur.js"></script>
</body>
</html>