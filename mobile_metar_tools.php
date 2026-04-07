<?php

// ===================================================================
// Fonction principale d’affichage et de décodage METAR
// ===================================================================
function rapcs_display_metar($station, $displayType) {
?>
   <!---Line 1 : Just the raw METAR -->
    <strong><span id="id_rapcs_metar"> METAR</span></strong>
<?php
    if($displayType=="picture") {
?>
        <!--- Display with a picture   -->   
    
        <!--- line 2 : 2 blocks -->
        <div style="display:flex;width:100%;-webkit-box-align:center!important;-ms-flex-align:center!important;align-items:center!important;">

        <!--- Column 1: Rose vent -->

        <div style="width: calc(100% - 100px); max-width: 215px; margin: 0 auto">
                <div style="position:relative;padding-bottom: 100%">
                <div style="position:absolute;width:100%;overflow:hidden">
        <img src="https://www.spa-aviation.be/resa/images/metar_rose.png" style="width:100%" alt="Compass">                                
        <img  id="id_rapcs_runway"src="https://www.spa-aviation.be/resa/images/metar_runway.png" style="position:absolute;left:0;top:0;width:100%;transform: rotate(0deg);" alt="Runway">

        <img  id="id_rapcs_wind_direction" src="https://www.spa-aviation.be/resa/images/metar_wind_direction.svg" style="left:0;top:0;position:absolute;width: 100%;transform: rotate(0deg)" alt="Wind direction 220°">      

        <img  id="id_rapcs_cross_direction" src="https://www.spa-aviation.be/resa/images/metar_crosswind_direction.png" style="left:0;top:0;position:absolute;width: 100%;transform: rotate(0deg)" alt="Wind direction 220°">      
        </div>
            </div>
        <div style="text-align:center; position: relative; margin-top: 0">
                        <div style="display: inline-block;padding: 0px 12px;font-size: 15px;text-align: center;position: relative;border-radius: 0.5rem !important;border: 1px solid #000; background-color: #fff">
                        <span id="id_rapcs_wind_direction_text" style="color: #00bbffff;">220°</span>
                        <span id="id_rapcs_wind_speed_text" style="color: #00bbffff;">10 kt</span>
                        <span id="id_rapcs_wind_speed_cross_text" style="color: #ff00fbff;">(5 kt< cross)</span>
                        </div>
                        </div>       
        </div>


        <!---Column 2 : Some details -->

        <div style="height:248px;-webkit-box-orient: vertical!important;-webkit-box-direction: normal!important;flex-direction: column!important;">
    
        <!--- Button: VFR-IMC -->
        <div id="id_rapcs_condition_button" class="metar-pp-code-cx" style="margin-top: .5rem;color:#fff;font-size: .9em;text-align:center;width:66px;border-radius:12px;padding:2px 10px;background-color: #28a745">
        <b><span id="id_rapcs_condition">VFR</span></b></div>

        <!--- Visibility-->
        <div style="margin-top: .5rem; display:flex;-webkit-box-align:center!important;-ms-flex-align:center!important;align-items:center!important;overflow:hidden">
            <div id="id_rapcs_visibility_button" style="margin-right:.25rem;width:10px;height:10px;border-radius:100%;background-color:#28a745">
            </div>
            <div style="font-size: .9em;white-space: nowrap;overflow:hidden;font-weight:300;text-overflow:ellipsis"><b><span id="id_rapcs_visibility">10 km+</span></b>
            </div>
        </div>


        <!--- Cloud Base-->
        <div style="display:flex;-webkit-box-align: center!important;-ms-flex-align: center!important;align-items: center!important;overflow:hidden">
            <div id="id_rapcs_clouds_base_button" style="width:10px;height:10px;border-radius:100%;margin-right:.25rem;background-color: #28a745">
            </div>
            <div style="font-size: .9em;white-space: nowrap;font-weight:300;overflow:hidden;text-overflow:ellipsis"><b><span id="id_rapcs_clouds_base">None</span></b>
            </div>
        </div>
        <!--- QNH -->
        <div style="margin-bottom:.25rem;font-size:.9em;font-weight:300">QNH: <b><span id="id_rapcs_qnh">1013 hPa</span></b></div>
        <!--- Altitude Density -->
        <div style="margin-bottom:.25rem;font-size:.9em;font-weight:300">DA: <b><span id="id_rapcs_density_altitude">1500 ft</span></b></div>
                
        <!--- Temperature-->
        <div style="margin-bottom:.25rem;font-size:.9em;font-weight:300">T: <b><span id="id_rapcs_temperature">10/8</span>°C</b>
        </div>
        <!---<div style="display:flex;font-weight: 300;width:100%;-webkit-box-align:center!important;-ms-flex-align:center!important;align-items:center!important;">
            <img src="https://metar-taf.com/wx/ani/lte/sunny-n.svg" alt="" style="width: 60px;height: 60px;display:block;margin-right:.6rem" loading="lazy"> 6 °C
                </div>
        -->

        <!--- % d'humidité -->
        <div style="font-size:.9em;font-weight:300">HR: <b><span id="id_rapcs_humidity">87% RH</span></b></div>
                    
        </div>


        <!---En utilisant la température et le point de rosée (Tr) :\(HR=100\times \frac{\exp (17.625\times Tr/(243.04+Tr))}{\exp (17.625\times T/(243.04+T))}\).  -->

        </div>
        <script>   
            var metar_rapcs_station="<?=$station?>";
            var metar_rapcs_displayType="<?=$displayType?>";
        </script>
        <script src="js/mobile_metar_tools.js"></script>
<?php
    }
}
?>