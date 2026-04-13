// JavaScript used by display_metar.php to manage the page
//
function display_metar_page_loaded(station, displayType) {
    //station="EBLG";
    const metar = {};
    metar.station=station;
    metar.display_type=displayType; 
    metar.wind_velocity=0.0;
    metar.wind_gust=0.0;
    metar.wind_direction=0.0;
    metar.runway=0;
    metar.runway2=-1;
    metar.QNH=0;
    metar.condition="";
    metar.clouds_base=0;
    metar.dew_point=0.;
    metar.temperature=0.;
    metar.visibility=0.;
    metar.density_altitude=0.;
    metar.ceiling=0.;
    metar.clouds=0.;;
    metar.aero_ceiling=0.;
    metar.type=""; // FG
    metar.METAR="METAR of "+station;
    wprapcs_displayMETAR(metar);
    wprapcs_updateyMETAR(metar);
}

//==============================================
// Function: wprapcs_stationChanged
// Purpose: Warning: This change is not synchronised
//==============================================
function wprapcs_stationChanged(station)
{
    setTimeout(displayMETAR, 1000 * 60 * 5) ; // Refresh every 5 minutes
}
//==============================================
// Function: wprapcs_updateyMETAR
// Purpose: Warning: This change is not synchronised
//==============================================
function wprapcs_updateyMETAR(metar)
{
    wprapcs_setMETAR(metar);
    setTimeout(wprapcs_updateyMETAR, 1000 * 60 * 5, metar) ; // Refresh every 5 minutes
}
//==============================================
// Function: wprapcs_displayMETAR
// Purpose: Warning: This change is not synchronised
//==============================================
function wprapcs_displayMETAR(metar)
{
    document.getElementById("id_rapcs_metar").innerHTML=metar.METAR;
    if(metar.display_type=="text") {
        return;
    }
    var colorGreen="#4b9910ff";
    var colorOrange="#FE9A37";//orange
    var colorRed="#C11007";
    var runwayDirection=metar.runway*10;
    var windDirection=metar.wind_direction;
    var crossWindDirection=runwayDirection+90.0;
    var crossWindSpeed=metar.wind_velocity*Math.abs(Math.cos(Math.PI*(crossWindDirection+360.0-windDirection)/180.0));
    if(Math.sin(Math.PI*(runwayDirection+360.0-windDirection)/180.0)>0.) {
      crossWindDirection+=180.;  
    }
    var windSpeedColor=colorGreen;
    var windCrossColor=colorGreen;
    if(metar.wind_velocity> 13.0 ) {
        windSpeedColor=colorRed;
    }
    else if(metar.wind_velocity> 9.0 ) {
        windSpeedColor=colorOrange;
    }
    if(crossWindSpeed> 13.0 ) {
        windCrossColor=colorRed;
    }
    else if(crossWindSpeed> 9.0 ) {
        windCrossColor=colorOrange;
    }
    
    var WingDirectionSVG="https://www.spa-aviation.be/resa/images/metar_wind_direction.svg";
    if(metar.wind_gust> 0.){
        windSpeedDuration=6.0*10.0/metar.wind_velocity; // 6s at 10kt
        WingDirectionSVG="https://www.spa-aviation.be/resa/images/metar_wind_direction_gust.svg";
    }
   else if(metar.wind_velocity> 13.){
        windSpeedDuration=6.0*10.0/metar.wind_velocity; // 6s at 10kt
        WingDirectionSVG="https://www.spa-aviation.be/resa/images/metar_wind_direction_fast.svg";
    }
    if(metar.wind_velocity< 9.){
        windSpeedDuration=6.0*10.0/metar.wind_velocity; // 6s at 10kt
        WingDirectionSVG="https://www.spa-aviation.be/resa/images/metar_wind_direction_slow.svg";
    }
    document.getElementById("id_rapcs_wind_direction").src=WingDirectionSVG;

     document.getElementById("id_rapcs_runway").style.transform = "rotate("+runwayDirection.toFixed(0)+"deg)";
    if(metar.wind_direction!=-99.) {
        document.getElementById("id_rapcs_wind_direction").style.transform = "rotate("+metar.wind_direction.toFixed(0)+"deg)";
        document.getElementById("id_rapcs_wind_direction").style.display="";   
        document.getElementById("id_rapcs_wind_direction_text").innerHTML=metar.wind_direction.toFixed(0)+"°";
        document.getElementById("id_rapcs_cross_direction").style.transform = "rotate("+crossWindDirection.toFixed(0)+"deg)";
        document.getElementById("id_rapcs_cross_direction").style.display=""; 
        document.getElementById("id_rapcs_wind_speed_cross_text").innerHTML="("+crossWindSpeed.toFixed(0)+"kt"+" cross)";
    }
    else {
        document.getElementById("id_rapcs_wind_direction").style.display="none"; 
        document.getElementById("id_rapcs_wind_direction_text").innerHTML="VRB";
        document.getElementById("id_rapcs_cross_direction").style.display="none"; 
        document.getElementById("id_rapcs_wind_speed_cross_text").innerHTML="";
    }
   // Gust
    windGustText="";
    if(metar.wind_gust>0.) {
         windGustText=" G"+metar.wind_gust.toFixed(0)+"kt<br>";
         windSpeedColor="red";
         windCrossColor="red";
         
    }

    document.getElementById("id_rapcs_wind_direction_text").style.color=windSpeedColor;
    document.getElementById("id_rapcs_wind_speed_text").innerHTML=metar.wind_velocity.toFixed(0)+"kt"+windGustText;
    document.getElementById("id_rapcs_wind_speed_text").style.color=windSpeedColor;

    document.getElementById("id_rapcs_wind_speed_cross_text").style.color=windCrossColor;

    document.getElementById("id_rapcs_qnh").innerHTML=metar.QNH.toString()+" hPa";
    if(metar.density_altitude!=-99) {
        document.getElementById("id_rapcs_density_altitude").innerHTML=metar.density_altitude.toString()+" ft";
    }
    else {
        document.getElementById("id_rapcs_density_altitude").innerHTML="- ft";       
    }
    if(metar.temperature!=-99) {
        document.getElementById("id_rapcs_temperature").innerHTML=metar.temperature.toString()+"/"+metar.dew_point.toString();
    }
    else {
       document.getElementById("id_rapcs_temperature").innerHTML="-/-";        
    }
    document.getElementById("id_rapcs_condition").innerHTML=metar.condition;
    if(metar.condition=="VFR") {
       //document.getElementById("id_rapcs_condition_button").style.backgroundColor = "#28a745";//green
       document.getElementById("id_rapcs_condition_button").style.backgroundColor = colorGreen;//green
    }
    else if(metar.condition=="MVFR") {
       document.getElementById("id_rapcs_condition_button").style.backgroundColor = colorOrange;//orange
    }
    else {
        document.getElementById("id_rapcs_condition_button").style.backgroundColor = colorRed;
    }
    var metarType=metar.type;
    if(metarType!="") metarType=" ("+metarType+")";
    if(metar.visibility>=9999) {
        document.getElementById("id_rapcs_visibility").innerHTML="10km+"+metarType;
        document.getElementById("id_rapcs_visibility_button").style.backgroundColor = colorGreen;//green
    }
    else if(metar.visibility==-99.) {
        document.getElementById("id_rapcs_visibility").innerHTML="? m"+metarType;
        document.getElementById("id_rapcs_visibility_button").style.backgroundColor = colorRed;//red
    }
    else if(metar.visibility<5000) {
        document.getElementById("id_rapcs_visibility").innerHTML=metar.visibility.toString()+"m"+metarType;
        document.getElementById("id_rapcs_visibility_button").style.backgroundColor = colorRed;//red
    }
    else {
        document.getElementById("id_rapcs_visibility").innerHTML=metar.visibility.toString()+"m"+metarType;       
         document.getElementById("id_rapcs_visibility_button").style.backgroundColor = colorOrange;//orange
   }
    document.getElementById("id_rapcs_clouds_base").innerHTML=metar.clouds_base.toString()+" ft";
    if(metar.clouds_base>1500) {
        document.getElementById("id_rapcs_clouds_base_button").style.backgroundColor = colorGreen;//green
    }
    else if(metar.clouds_base>1000) {
       document.getElementById("id_rapcs_clouds_base_button").style.backgroundColor = colorOrange;//orange
    }
    else {
        document.getElementById("id_rapcs_clouds_base_button").style.backgroundColor = colorRed;
    }
    var tauxHumidite=100*Math.exp(17.625*metar.dew_point/(243.04+metar.dew_point))/Math.exp(17.625*metar.temperature/(243.04+metar.temperature));
    document.getElementById("id_rapcs_humidity").innerHTML=tauxHumidite.toFixed(0)+" %";
 
}
//==============================================
// Function: wprapcs_setMETAR
// Purpose: set the metar for an airport
// Airport info : https://aviationweather.gov/api/data/airport?ids=KMCI&format=json
// https://airportdb.io/#howtouse
// https://airportdb.io/api/v1/airport/KJFK?apiToken=e24a7eb5f31c2072b2a0ef318468849fb7faaf9ff931fb4c4b1ca7a76c989707bef0d6db0c355fe5585f1c32cc2289c0
//
// https://www.weatherapi.com/ for EBTX ...
// https://api.met.no
// https://api.met.no/weatherapi/nowcast/2.0/complete?lat=59.9333&lon=10.7166
//==============================================
function wprapcs_setMETAR(metar) 
{
    var station=metar.station.toUpperCase();
    if(station=="") {
        // We keep inputs
        return;
    }
    if(station.length!=4) {
        alert("Error:setMetar: le nom de la station doit comporter 4 lettres \""+station+"\"");
        return;
    }
	var XHR=new XMLHttpRequest();
	XHR.onreadystatechange = function() {

		if(this.readyState  == 4) {

			if(this.status  == 200 || this.status == 304) { // OK or not modified
				try {
					var response = eval('(' + this.responseText.trim() + ')') ;
				} catch(err) {
                    alert("ERROR:setMETAR: Impossible to retrieve the METAR: "+err);
					return ;
				}
				if (response.error != '') {
                    alert("ERROR:setMETAR: Impossible to retrieve the METAR of "+station+":\n "+response.error);
                    setRunway(station);
                    return;
                }
                else {
                    //document.getElementById("id_takeoff_i_qnh").value=response.QNH;

                    //document.getElementById("id_takeoff_i_temperature").value=response.temperature;                   

                    //document.getElementById("id_takeoff_i_altitude").value=response.elevation;                   
                    if(response.hasOwnProperty("QNH")) {
                        metar.QNH=response.QNH;
                    }
                    else {
                       metar.QNH=0; 
                    }
                    if(response.hasOwnProperty("temperature")) {
                        metar.temperature=response.temperature;
                    }
                    else {
                        metar.temperature=-99;
                    }

                    
                    if(response.hasOwnProperty("dew_point")) {
                        metar.dew_point=response.dew_point;
                    }
                    else {
                        metar.dew_point=metar.temperature;
                    }
                    if(response.hasOwnProperty("type")) {
                        // FG DZ
                        metar.type=response.type;
                        if(metar.type=="//") metar.type="";
                    }
                    else {
                        metar.type="";
                    }
                    if(response.visibility!="?") {
                        metar.visibility=response.visibility;
                    }
                    else {
                        metar.visibility=-99;
                    }                   

                    if(response.hasOwnProperty("density_altitude")) {
                        metar.density_altitude=response.density_altitude;
                    }
                    else {
                        metar.density_altitude=-99.;
                    }
                  
                   if(response.hasOwnProperty("clouds_base")) {
                        if(response.clouds_base==0 ) {
                            metar.clouds_base=response.ceiling;
                        }
                        else {
                            metar.clouds_base=response.clouds_base;
                        }
                    }
                    else {
                        metar.clouds_base=response.ceiling;
                        if(metar.clouds_base==999999) metar.clouds_base=0;
                    }
                    if(response.hasOwnProperty("ceiling")&&response.ceiling!="?") {
                        metar.clouds_base=response.ceiling;
                    }
                    metar.clouds=response.clouds;
                    metar.aero_ceiling=response.aero_ceiling;
                    metar.METAR=response.METAR;
                    metar.condition=response.condition;
                    if(response.condition=="MMC") {
                        metar.condition="MVFR";
                    }
                    else if(response.condition=="VMC") {
                        metar.condition="VFR";
                    }
                    else if(response.condition=="IMC") {
                        metar.condition="IFR";
                    }    
                    else if(response.condition=="?") {
                        if(metar.visibility==9999 && metar.clouds_base>999) {
                            metar.condition="VFR";
                        }
                        else if(metar.visibility>5000 && metar.clouds_base>999) {
                            metar.condition="MVFR";
                        }
                        else {
                            metar.condition="IFR";
                        }
                    }
                    if(response.hasOwnProperty("wind_velocity")) {
                        metar.wind_velocity=response.wind_velocity;
                    }
                    else {
                        metar.wind_velocity=0.;
                    }
                    metar.wind_direction=-99.;
                   if(response.hasOwnProperty("wind_direction")) {
                        if(response.wind_direction!="VRB") {
                            metar.wind_direction=Number(response.wind_direction);
                        }  
                    }
                    if(response.hasOwnProperty("wind_gust")) {
                        metar.wind_gust=response.wind_gust;
                    }
                    else {
                        metar.wind_gust=0.;
                    }

               //response.wind_gust
               //response.wind_vrb_from
               //response.wind_vrb_to
                    //wprapcs_setRunway(station,metar);
				}
            wprapcs_displayMETAR(metar);
            wprapcs_setRunway(station,metar);
			}
		}
	}
	var requestUrl = 'https://www.spa-aviation.be/resa/metar.php?station=' + station ;
	XHR.open("GET", requestUrl, true) ;
	XHR.send(null) ;
}


//==============================================
// Function: wprapcs_setRunway
// Purpose: set the runway for an airport
// Airport info : 
// https://airportdb.io/#howtouse
// https://airportdb.io/api/v1/airport/KJFK?apiToken=e24a7eb5f31c2072b2a0ef318468849fb7faaf9ff931fb4c4b1ca7a76c989707bef0d6db0c355fe5585f1c32cc2289c0
//==============================================
function wprapcs_setRunway(station, metar) 
{
    station=station.toUpperCase();
    if(station=="EBSP") {
        metar.runway_type="Asphalt";
        metar.runway=5;
        wprapcs_displayMETAR(metar);
        return;
    }
    if(station=="") {
        // We keep inputs
        return;
    }
    if(station.length!=4) {
        alert("Error:setRunway: le nom de la station doit comporter 4 lettres \""+station+"\"");
        return;
    }
	var XHR=new XMLHttpRequest();
	XHR.onreadystatechange = function() {
		if(this.readyState  == 4) {
			if(this.status  == 200 || this.status == 304) { // OK or not modified
				try {
					var response = eval('(' + this.responseText.trim() + ')') ;
				} catch(err) {
                    alert("ERROR:setMETAR: Impossible to retrieve the AirportInfo: "+err);
					return ;
				}
				if (response.hasOwnProperty("statusCode")) {
                    alert("ERROR:setRunway: Impossible to retrieve the Airport Info of "+station+":\n "+response.message);
                    return;
                }
                else {
                    // runway: response.runways[].he_heading_degT (en degree) + le_heading_degT (en degree) + length_ft + he_ident (23) + le_indent (04)
                    // type de piste
                    var runways=response.runways;

                    var runwayLength=0;
                    var runwayNumber=0;

                    for(var i=0;i<runways.length;i++) {
                        var runway=runways[i];
                        var nameRunway1=runway.he_ident.substr(0,2);
                        var nameRunway2=runway.le_ident.substr(0,2);
                        var typeRunway=runway.surface; // ASP GRS
                        var runway_direction=Number(nameRunway1)*10;
                        runwayNumber=Number(nameRunway1);
                        //Inputs["runway_type"]="Asphalt";
                        if(typeRunway=="GRS") {
                            typeRunway="Grass";
                        }
                        metar.runway_type=typeRunway;
                        var runway_direction=Number(nameRunway1)*10;
                        runway_direction=Number(nameRunway2)*10;
                    }
                    if(runwayNumber<10) {
                        runwayNumber="0"+runwayNumber;
                    }
                    metar.runway=runwayNumber;
 				}
                wprapcs_displayMETAR(metar);
			}
		}
	}
	var requestUrl = 'https://airportdb.io/api/v1/airport/'+station+'?apiToken=e24a7eb5f31c2072b2a0ef318468849fb7faaf9ff931fb4c4b1ca7a76c989707bef0d6db0c355fe5585f1c32cc2289c0' ;
	XHR.open("GET", requestUrl, true) ;
	XHR.send(null) ;
}

//===============================================
// Main
// Use the modern onload event to display the METAR page and avoid conflict with the BODY onload=
//===============================================
window.addEventListener('load', (event) => {
    display_metar_page_loaded(metar_rapcs_station, metar_rapcs_displayType);
}) ;