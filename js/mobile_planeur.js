// JavaScript used by mobile_planeur.php to manage the page
//
function mobile_planeur_page_loaded() {

  //document.getElementById("id_notedefrais_input_total").readOnly=true;;
  //document.getElementById("id_notedefrais_input_total").style.backgroundColor = ReadOnlyColor;
  //document.getElementById("id_notedefrais_input_odooreference").readOnly=true;;
  //document.getElementById("id_notedefrais_input_odooreference").style.backgroundColor = ReadOnlyColor;
 
    document.getElementById("id_glider_i_station").onchange = function() {
        stationChanged();
    };

    document.getElementById("id_glider_i_qnh").onchange = function() {
        QNHChanged();
    };

    document.getElementById("id_glider_i_altitude").onchange = function() {
        altitudeChanged();
    };

    document.getElementById("id_glider_i_temperature").onchange = function() {
        temperatureChanged();
    };

    
    //document.getElementById("id_notedefrais_rowinput").style.display="none";
    //document.getElementById("id_submit_notedefrais").disabled=true;
    initGlider();
    stationChanged()
}

//==============================================
// Function: init
// Purpose: 
//==============================================
function initGlider()
{
    updateDisplayInputs();
    updateDisplayOuputs();
    updateAll();
    /*
    var plane=document.getElementById("id_plane_select").value;
    performance_plane_takeoffJSON="";
    gliderInputsDefault="";
    performance_plane_landingJSON="";
    landingInputsDefault="";
    if(!performanceJSON.performance[plane].hasOwnProperty("takeoff")) {
        alert("Error:planeChanged: No takeoff info for the plane "+ plane);
    }
    else {
        if(!performanceJSON.performance[plane].takeoff.hasOwnProperty("outputs")) {
            alert("Error:planeChanged: No takeoff.outputs info for the plane "+ plane);  
        }
        else {
            performance_plane_takeoffJSON=performanceJSON.performance[plane].takeoff.outputs
        }
        if(performanceJSON.performance[plane].takeoff.hasOwnProperty("inputs")) {
            gliderInputsDefault=performanceJSON.performance[plane].takeoff.inputs;
        }
    }
    if(!performanceJSON.performance[plane].hasOwnProperty("landing")) {
        alert("Error:planeChanged: No landing info for the plane "+ plane);
    }
    else {
        if(!performanceJSON.performance[plane].landing.hasOwnProperty("outputs")) {
            alert("Error:planeChanged: No landing.outputs info for the plane "+ plane);  
        }
        else {
            performance_plane_landingJSON=performanceJSON.performance[plane].landing.outputs;
        }
        if(performanceJSON.performance[plane].landing.hasOwnProperty("inputs")) {
            landingInputsDefault=performanceJSON.performance[plane].landing.inputs;
        }
    }
    Inputs["plane"]=plane;
    document.getElementById("id_glider_plane").innerHTML=plane;
    document.getElementById("id_landing_plane").innerHTML=plane;
    updateDisplayInputs(gliderInputsDefault,landingInputsDefault);
    updateAll();
    setToolTip();
    // update the POH
    var urlPOH="mobile_plane.php?plane="+plane;
    if(performanceJSON.performance[plane].hasOwnProperty("POH")) {
        urlPOH=performanceJSON.performance[plane].POH;
    }
    document.getElementById("id_plane_poh").innerHTML="<a href=\""+urlPOH+"\" target=\"_blank\"><i class=\"bi bi-file-earmark-pdf\"></i></a>";
    */
}
//==============================================
// Function: stationChanged
// Purpose: Warning: This change is not synchronised
//==============================================
function stationChanged()
{
    Inputs["station"]=document.getElementById("id_glider_i_station").value.toUpperCase();
    document.getElementById("id_glider_i_station").value=Inputs["station"];
    setMETAR(Inputs["station"]);
}
//==============================================
// Function: QNHChanged
// Purpose: 
//==============================================
function QNHChanged()
{
    Inputs["qnh"]=Number(document.getElementById("id_glider_i_qnh").value);
    updateAll();
}

//==============================================
// Function: altitudeChanged
// Purpose: 
//==============================================
function altitudeChanged()
{
    Inputs["altitude"]=convertUnit(Number(document.getElementById("id_glider_i_altitude").value),
            "length",
            "m", Inputs["altitude/unit"]);
    updateAll();
}
//==============================================
// Function: temperatureChanged
// Purpose: 
//==============================================
function temperatureChanged(perfoType)
{
    Inputs["temperature"]=Number(document.getElementById("id_glider_i_temperature").value);
    updateAll();
}

//==============================================
// Function: updateAll
// Purpose: 
//==============================================
function updateAll()
{
    updateTemperature();
    //updatePressureAltitude();
    updateDensityAltitude();
    update4500ft();
    updateFL55();
    updateFL65();
    updateFL75();
    updateFL95();
    updateDisplay();
}
//==============================================
// Function: updateTemperature
// Purpose: 
//==============================================
function updateTemperature()
{
    var temperature=Inputs["temperature"];
    var altitude=Inputs["altitude"];
    //var temperatureISA=computeTemperatureISA(altitude);
    //var temperatureDeltaISA=temperature - temperatureISA
    //Outputs["temperature_isa"]=temperatureISA;
    //Outputs["temperature_delta_isa"]=temperatureDeltaISA;
    //document.getElementById("id_glider_o_temperature_isa").innerHTML=temperatureISA.toFixed(0);
    //document.getElementById("id_glider_o_temperature_delta_isa").innerHTML=temperatureDeltaISA.toFixed(0);
}
//==============================================
// Function: updatePressureAltitude
// Purpose: 
//==============================================
function updatePressureAltitude()
{
    var qnh=Inputs["qnh"];
    var altitude=Inputs["altitude"];
    var altitudePressure=computePressureAltitude(altitude, qnh);
    Outputs["pressure_altitude"]=altitudePressure;
    //document.getElementById("id_glider_o_pressure_altitude").innerHTML=altitudePressure.toFixed(0);
}

//==============================================
// Function: updateDensityAltitude
// Purpose: 
//==============================================
function updateDensityAltitude()
{
    var qnh=Inputs["qnh"];
    var altitude=Inputs["altitude"];
    var temperature=Inputs["temperature"];
    var altitudeDensity=computeDensityAltitude(altitude, qnh, temperature);
    Outputs["density_altitude"]=altitudeDensity;
 
    document.getElementById("id_glider_o_density_altitude").innerHTML=altitudeDensity.toFixed(0);
 }
//==============================================
// Function: update4500ft
// Purpose: 
//==============================================
function update4500ft()
{
    var qnh=Inputs["qnh"];
    var altitude=Inputs["altitude"];
    var altitude4500ftQNH=4500;
    Outputs["altitude_4500ft_qnh"]=altitude4500ftQNH;
    var altitude4500ftQFE=altitude4500ftQNH-altitude;
    Outputs["altitude_4500ft_qfe"]=altitude4500ftQFE;

    document.getElementById("id_glider_o_altitude_4500ft_qnh").innerHTML=getDisplayedValue(Outputs,"altitude_4500ft_qnh").toFixed(0);
    document.getElementById("id_glider_o_altitude_4500ft_qfe").innerHTML=getDisplayedValue(Outputs,"altitude_4500ft_qfe").toFixed(0);
}
//==============================================
// Function: updateFL55
// Purpose: 
//==============================================
function updateFL55()
{
    var qnh=Inputs["qnh"];
    var altitude=Inputs["altitude"];
    var altitudeFL55QNH=computeFLToQNH( 55, qnh);
    Outputs["altitude_fl55_qnh"]=altitudeFL55QNH;
    var altitudeFL55QFE=computeFLToQFE(altitude, 55, qnh);
    Outputs["altitude_fl55_qfe"]=altitudeFL55QFE;

    document.getElementById("id_glider_o_altitude_fl55_qnh").innerHTML=getDisplayedValue(Outputs,"altitude_fl55_qnh").toFixed(0);
    document.getElementById("id_glider_o_altitude_fl55_qfe").innerHTML=getDisplayedValue(Outputs,"altitude_fl55_qfe").toFixed(0);
}
//==============================================
// Function: updateFL65
// Purpose: 
//==============================================
function updateFL65()
{
    var qnh=Inputs["qnh"];
    var altitude=Inputs["altitude"];
    var altitudeFL65QNH=computeFLToQNH( 65, qnh);
    Outputs["altitude_fl65_qnh"]=altitudeFL65QNH;
    var altitudeFL65QFE=computeFLToQFE(altitude, 65, qnh);
    Outputs["altitude_fl65_qfe"]=altitudeFL65QFE;

    document.getElementById("id_glider_o_altitude_fl65_qnh").innerHTML=getDisplayedValue(Outputs,"altitude_fl65_qnh").toFixed(0);
    document.getElementById("id_glider_o_altitude_fl65_qfe").innerHTML=getDisplayedValue(Outputs,"altitude_fl65_qfe").toFixed(0);
}
//==============================================
// Function: updateFL75
// Purpose: 
//==============================================
function updateFL75()
{
    var qnh=Inputs["qnh"];
    var altitude=Inputs["altitude"];
    var altitudeFL75QNH=computeFLToQNH( 75, qnh);
    Outputs["altitude_fl75_qnh"]=altitudeFL75QNH;
    var altitudeFL75QFE=computeFLToQFE(altitude, 75, qnh);
    Outputs["altitude_fl75_qfe"]=altitudeFL75QFE;

    document.getElementById("id_glider_o_altitude_fl75_qnh").innerHTML=getDisplayedValue(Outputs,"altitude_fl75_qnh").toFixed(0);
    document.getElementById("id_glider_o_altitude_fl75_qfe").innerHTML=getDisplayedValue(Outputs,"altitude_fl75_qfe").toFixed(0);
}
//==============================================
// Function: updateFL95
// Purpose: 
//==============================================
function updateFL95()
{
    var qnh=Inputs["qnh"];
    var altitude=Inputs["altitude"];
    var altitudeFL95QNH=computeFLToQNH(95, qnh);
    Outputs["altitude_fl95_qnh"]=altitudeFL95QNH;
    var altitudeFL95QFE=computeFLToQFE(altitude, 95, qnh);
    Outputs["altitude_fl95_qfe"]=altitudeFL95QFE;

    document.getElementById("id_glider_o_altitude_fl95_qnh").innerHTML=getDisplayedValue(Outputs,"altitude_fl95_qnh").toFixed(0);
    document.getElementById("id_glider_o_altitude_fl95_qfe").innerHTML=getDisplayedValue(Outputs,"altitude_fl95_qfe").toFixed(0);
}

//==============================================
// Function: updateDisplay
// Purpose:  update the display
//==============================================
function updateDisplay()
{
    /*
    var canvas = document.getElementById("id_glider_o_canvas");
    var ctx = canvas.getContext("2d");
    var canvasWidth=canvas.width;
    var canvasHeight=canvas.height;
    ctx.clearRect(0, 0, canvasWidth, canvasHeight);
 // RunwayLength=799m
    var runwayLength=getDisplayedValue(Inputs,"runway_length");
    if(runwayLength<500) runwayLength=500;
     var runwayWidth=50.0;
    var runwayInclinaison=25.0;
    var treeDistance=1500.;// 1700 m
    var treeHeight=100.0; // 60 ft
    var xInfo=10.; // Where to put additional info
    var yInfo=0.0;
    var xSpeedInfo=xBegin;

// Tree 23: 1700m
// Tree 05: 1130m
    var sizeX=1800.0;//m
    var sizeY=500.0; //ft
    var scaleX=canvasWidth/sizeX; //Pixel by m
    var scaleY=canvasHeight/sizeY; //Pixel by ft
    ctx.setLineDash([]);
    var yFont=15.0;
    ctx.font = "15px Arial";
    var xBegin=30.0;
    var yBegin=47.0;
    var xSpeedInfo=xBegin;
    var ySpeedInfo=canvasHeight-yFont/2.0;

    var yTree=treeHeight*scaleY;
    //Draw runway
    var xRunway=xBegin;
    var yRunway=canvasHeight-yBegin;
    var x2=xRunway+runwayLength*scaleX;
    var y2=yRunway;
    var x3=x2 + runwayInclinaison;
    var y3=yRunway-runwayWidth*scaleY;
    var x4=xRunway + runwayInclinaison;
    var y4=y3;
    var x1CenterLine=xRunway+runwayInclinaison/2.0;
    var y1CenterLine=yRunway-runwayWidth*scaleY/2.0;
    var x2CenterLine=x2+runwayInclinaison/2.0;
    var y2CenterLine=y2-runwayWidth*scaleY/2.0;
    var centerLineDashLength=50.0*scaleX;// 50m
    var xTree=x1CenterLine+treeDistance*scaleX;
    ctx.beginPath();
    ctx.fillStyle = "LightGrey";
    ctx.moveTo(xRunway,yRunway);
    ctx.lineTo(x2,y2);
    ctx.lineTo(x3,y3);
    ctx.lineTo(x4,y4);
    ctx.lineTo(xRunway,yRunway);
    ctx.fill();
    //ctx.stroke();
    ctx.beginPath();
    ctx.setLineDash([centerLineDashLength, centerLineDashLength]);
    ctx.moveTo(x1CenterLine,y1CenterLine);
    ctx.lineTo(x2CenterLine,y2CenterLine);
    ctx.stroke();


    // Roll Distance 
    // 50ft Distance
    var rollDistance=convertUnit(Outputs["distance_roll"],"length",Outputs["distance_roll/unit"],"m");
    var distance50ft=convertUnit(Outputs["distance_50ft"],"length",Outputs["distance_50ft/unit"],"m");
    var xRollDistance=rollDistance*scaleX;
    var x50ftDistance=distance50ft*scaleX;
    var y50ftDistance=50.0*scaleY;
    ctx.beginPath();
    ctx.setLineDash([]);
    ctx.moveTo(x1CenterLine+x50ftDistance,y2CenterLine);
    ctx.lineTo(x1CenterLine+x50ftDistance,y2CenterLine-y50ftDistance);
    ctx.lineTo(x1CenterLine+xRollDistance,y2CenterLine);
    ctx.stroke();

    ctx.setLineDash([]);
    ctx.fillStyle = "red";

    fillTextCentered(ctx,rollDistance.toFixed(0)+"m",x1CenterLine+xRollDistance/2.0,y2CenterLine+20-2);
    //ctx.fillText("Roll "+iasRoll+"MPH",x1CenterLine+xRollDistance,y2CenterLine-2);
    drawArrow(ctx,x1CenterLine,y2CenterLine+20,x1CenterLine+xRollDistance,y2CenterLine+20,1,"red");
 
    ctx.fillStyle = "green";
    fillTextCentered(ctx,distance50ft.toFixed(0)+"m",x1CenterLine+x50ftDistance/2.0,y2CenterLine-y50ftDistance-10-2);
    ctx.fillText("50ft",x1CenterLine+x50ftDistance,y2CenterLine-2);
    drawArrow(ctx,x1CenterLine,y2CenterLine-y50ftDistance-12,x1CenterLine+x50ftDistance,y2CenterLine-y50ftDistance-12,1,"green");

    // Draw Tree
    const image = new Image(); // Create new img element
    image.onload = () => {
      ctx.imageSmoothingEnabled = false;
      ctx.drawImage(image, xTree-yTree/2.0, y2CenterLine-yTree,yTree,yTree);
    };
    image.src = "images/mobile_performance_sapin.png"; // Set source path

    // Display main info
    ctx.fillStyle = "black";
    var density_altitude=convertUnit(Outputs["density_altitude"],"pressure","hPa","hPa");
    var head_wind_speed=convertUnit(Outputs["head_wind_speed"],"speed","kt","kt");
    var ias_rollDisplayedUnit=Outputs["ias_roll/displayedunit"];
    var ias_roll=getDisplayedValue(Outputs,"ias_roll");
    var ias_50ft=getDisplayedValue(Outputs,"ias_50ft");
    var ias_50ftDisplayedUnit=Outputs["ias_50ft/displayedunit"];
    var ias_best_angle=getDisplayedValue(Outputs,"ias_best_angle");
    var ias_best_angleDisplayedUnit=Outputs["ias_roll/displayedunit"];
    var max_roc=getDisplayedValue(Outputs,"max_roc");
    var ias_max_rocDisplayedUnit=Outputs["ias_max_roc/displayedunit"];
    var ias_max_roc= getDisplayedValue(Outputs,"ias_max_roc");
 
    // Height over tree (ft)= 50ft + MaxRoc*time(min)= MaxROC (ft/min)* distanceToTree/speed (ft/min)
    // Height = 50ft +MaxRoc+ (DistanceTree(m)-Distance50ft(m))*3.281/(Speed MPH * 5279.987/60.0)
    //PRE - todo
    var heightOverTree= 50.0+max_roc*convertUnit(treeDistance-distance50ft,"length","m","ft")/convertUnit(ias_max_roc,"speed","MPH","ft/min");

    // Additional info
    var yInfo=5;
    yInfo+=yFont;
    ctx.fillText("Density Altitude:"+density_altitude.toFixed(0)+"hPa",xInfo,yInfo);
    yInfo+=yFont;
    ctx.fillText("Max RoC:"+max_roc.toFixed(0)+"ft/min",xInfo,yInfo);
    yInfo+=yFont;
    ctx.fillText("Head wind speed:"+head_wind_speed.toFixed(0)+"kt",xInfo,yInfo);
 
    yInfo=5;
    yInfo+=yFont;
    var xInfo=225;
    ctx.fillText("Take-off perfo: "+Inputs["plane"],xInfo,yInfo);

    //Speed
    var text="IAS: Roll="+ias_roll.toFixed(0)+ias_rollDisplayedUnit;
    //ctx.fillText(text,xSpeedInfo,ySpeedInfo);
    //xSpeedInfo+=text.length*yFont*0.6;

    text+=", 50ft="+ias_50ft.toFixed(0)+ias_50ftDisplayedUnit;
    //ctx.fillText(text,xSpeedInfo,ySpeedInfo);
    //xSpeedInfo+=text.length*yFont*0.6;

    text+=", Max RoC="+ias_max_roc.toFixed(0)+ias_max_rocDisplayedUnit
    //ctx.fillText(text,xSpeedInfo,ySpeedInfo);
    //xSpeedInfo+=text.length*yFont*0.6+10.0;

    text+=", Best Angle RoC="+ias_best_angle.toFixed(0)+ias_best_angleDisplayedUnit;
    ctx.fillText(text,xSpeedInfo,ySpeedInfo);
    //xSpeedInfo+=text.length*yFont*0.6;
    
    // Display from 50ft to Tree
    ctx.beginPath();
    ctx.setLineDash([]);
    ctx.moveTo(x1CenterLine+x50ftDistance,y2CenterLine-y50ftDistance);
    ctx.lineTo(xTree,y2CenterLine-heightOverTree*scaleY);
    ctx.lineTo(xTree,y2CenterLine-yTree);
    ctx.stroke();
    ctx.fillText(heightOverTree.toFixed(0)+"ft",xTree-4.0*yFont*0.6,y2CenterLine-yTree-20.0);
*/
}

//==============================================
// Function: drawArrow
// Purpose: Draw an arrow
//==============================================

function drawArrow(ctx, fromx, fromy, tox, toy, arrowWidth, color)
{
    //variables to be used when creating the arrow
    var headlen = 10;
    var angle = Math.atan2(toy-fromy,tox-fromx);
 
    ctx.save();
    ctx.strokeStyle = color;
 
    //starting path of the arrow from the start square to the end square
    //and drawing the stroke
    ctx.beginPath();
    ctx.moveTo(fromx, fromy);
    ctx.lineTo(tox, toy);
    ctx.lineWidth = arrowWidth;
    ctx.stroke();
 
    //starting a new path from the head of the arrow to one of the sides of
    //the point
    ctx.beginPath();
    ctx.moveTo(fromx, toy);
    ctx.lineTo(fromx+headlen*Math.cos(angle-Math.PI/7),
               fromy+headlen*Math.sin(angle-Math.PI/7));
    ctx.stroke();
    ctx.fillStyle = color;
    //path from the side point of the arrow, to the other side point
    ctx.lineTo(fromx+headlen*Math.cos(angle+Math.PI/7),
               fromy+headlen*Math.sin(angle+Math.PI/7));
    ctx.fill();
    //path from the side point back to the tip of the arrow, and then
    //again to the opposite side point
    ctx.beginPath();
    ctx.lineTo(tox, toy);
    ctx.lineTo(tox-headlen*Math.cos(angle-Math.PI/7),
               toy-headlen*Math.sin(angle-Math.PI/7));
 
    //path from the other side point of the arrow, to the other side point
    ctx.lineTo(tox-headlen*Math.cos(angle+Math.PI/7),
               toy-headlen*Math.sin(angle+Math.PI/7));
 
    //path from the side point back to the tip of the arrow, and then
    //again to the opposite side point
    ctx.lineTo(tox, toy);
    ctx.lineTo(tox-headlen*Math.cos(angle-Math.PI/7),
               toy-headlen*Math.sin(angle-Math.PI/7));
 
    //draws the paths created above
    ctx.fill();
    ctx.restore();
}

//==============================================
// Function: drawArrow
// Purpose: Draw an arrow
//==============================================

function fillTextCentered(ctx,text,x,y)
{
    ctx.fillText(text,x-ctx.measureText(text).width/2.0,y);
}
 
//==============================================
// Function: getDisplayedValue
// Purpose: returns the displayed value from an Inputs or Outputs map
//==============================================
function getDisplayedValue(theMap, theKey)
{
    return convertUnit(
        theMap[theKey],
        theMap[theKey+"/unittype"],
        theMap[theKey+"/unit"],
        theMap[theKey+"/displayedunit"]);
}

//==============================================
// Function: setDisplayedValue
// Purpose: set the displayed value into an Inputs or Outputs map
//==============================================
function setDisplayedValue(theValue, theMap, theKey)
{
    var value=convertUnit(
        theValue,
        theMap[theKey+"/unittype"],
        theMap[theKey+"/displayedunit"],
        theMap[theKey+"/unit"]);
    theMap[theKey]=value;
}

//==============================================
// Function: convertUnit
// Purpose: convert value for a unit
//==============================================

function convertUnit(value, unitType, unitInput, unitOutput) 
{
    if(unitInput==unitOutput) {
        return value;
    }
    if(unitType=="length") {
        if(unitInput=="ft" && unitOutput=="m") {
            return value*0.3048;
        }
        if(unitInput=="m" && unitOutput=="ft") {
            return value*3.28084;
        }
    }
    else if(unitType=="speed") {
        if(unitInput=="MPH" && unitOutput=="ft/min") {
            return value*5279.98687656/60.0;
        }
       if(unitInput=="MPH" && unitOutput=="kt") {
            return value*0.868976;
        }
    }
   else if(unitType=="temperature") {
        if(unitInput=="C" && unitOutput=="F") {
            return value*9.0/5.0+32.0;
        }
        if(unitInput=="F" && unitOutput=="C") {
            return value*5./9.-32.0;
        }
    }
   else if(unitType=="temperature_delta") {
        if(unitInput=="C" && unitOutput=="F") {
            return value*9.0/5.0;
        }
        if(unitInput=="F" && unitOutput=="C") {
            return value*5./9.;
        }
    }
    else if(unitType=="mass") {
        if(unitInput=="lb" && unitOutput=="kg") {
            return value*0.453592;
        }
        if(unitInput=="kg" && unitOutput=="lb") {
            return value*2.20462;
        }
    }
    else {
        alert("ERROR:convertUnit: Unknown unitType="+unitType);
        return 99999.0
    }
    alert("ERROR:convertUnit: Impossible to convert unitType="+unitType+" unitInput="+unitInput+" into unitOutput="+unitOutput);
    return 99999.0;
}
//==============================================
// Function: computeTemperatureISA
// Purpose: temperatureISA= 15- 2* Altitude /1000 (en ft)
//==============================================
function computeTemperatureISA(theAltitude)
{
    return 15.0 - 2.0* theAltitude/1000.;
}
//==============================================
// Function: computeFLToQNH
// Purpose: 
//==============================================
function computeFLToQNH(theFL, theQNH)
{
    return 100.*theFL+(theQNH-1013)*30;
}
//==============================================
// Function: computeFLToQFE
// Purpose: 
//==============================================
function computeFLToQFE(theAltitude, theFL, theQNH)
{
    return computeFLToQNH( theFL, theQNH)-theAltitude;
}

//==============================================
// Function: computePressureAltitude
// Purpose: PressureAltitude= Altitude + (1013.5 - QNH) * 30 (en ft)
//==============================================
function computePressureAltitude(theAltitude, theQNH)
{
    return theAltitude+(1013-theQNH)*30;
}

//==============================================
// Function: computeDensityAltitude
// Purpose: DensityAltitude= Altitude pression (ft) + 118.8 * (T ° - T ISA °)
//==============================================
function computeDensityAltitude(theAltitude, theQNH, theTemperature)
{
    var pressureAltitude = computePressureAltitude(theAltitude,theQNH);
    var temperatureISA=computeTemperatureISA(theAltitude);
    return pressureAltitude + 118.8 * (theTemperature - temperatureISA);
}

//==============================================
// Function: setMETAR
// Purpose: set the metar for an airport
// Airport info : https://aviationweather.gov/api/data/airport?ids=KMCI&format=json
// https://airportdb.io/#howtouse
// https://airportdb.io/api/v1/airport/KJFK?apiToken=e24a7eb5f31c2072b2a0ef318468849fb7faaf9ff931fb4c4b1ca7a76c989707bef0d6db0c355fe5585f1c32cc2289c0
//
// https://www.weatherapi.com/ for EBTX ...
// https://api.met.no
// https://api.met.no/weatherapi/nowcast/2.0/complete?lat=59.9333&lon=10.7166
//==============================================
function setMETAR(station) 
{
    station=station.toUpperCase(); 
    var stationInput=station;
    if(station=="") {
        // We keep inputs
        return;
    }
    if(station.length!=4) {
        alert("Error:setMetar: le nom de la station doit comporter 4 lettres \""+station+"\"");
        return;
    }
    if(station=="EBTX") {
        station="EBSP";
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
                    return;
                }
                else {
                    document.getElementById("id_glider_i_qnh").value=response.QNH;
                    document.getElementById("id_glider_i_temperature").value=response.temperature;                   
                    document.getElementById("id_glider_i_altitude").value=convertUnit(response.elevation, "length",
                         "ft", Inputs["altitude/displayedunit"]).toFixed(0);
                    
                    if(stationInput=="EBTX") {
                        document.getElementById("id_glider_i_altitude").value=convertUnit(1091., "length",
                            "ft", Inputs["altitude/displayedunit"]).toFixed(0);  
                        document.getElementById("id_glider_i_temperature").value=response.temperature+1.;                   
                    }

                    QNHChanged();
                    temperatureChanged("takeoff");
                    altitudeChanged();
            
                    updateAll();
				}
			}
		}
	}
	var requestUrl = 'metar.php?station=' + station ;
	XHR.open("GET", requestUrl, true) ;
	XHR.send(null) ;
}


//==============================================
// Function: setToolTip
// Purpose: Set the tooltip associated to an output
//==============================================
function setToolTip()
 {
    // loop on all outputs
    for (var key in Outputs) {
        if(key.search("/")==-1) {
            if(!Outputs.hasOwnProperty(key+"/tooltip")) {
                alert("Error: the key :"+ key+"/tooltip"+ " doesn't exist in the array Outputs");
                return;
            }
            var tooltip=Outputs[key+"/tooltip"];
            if(!Outputs.hasOwnProperty(key+"/output")) {
                alert("Error: the key :"+ key+"/output"+ " doesn't exist in the array Outputs");
                return;
            }
            var outputTarget=Outputs[key+"/output"];
            if(tooltip!="") {
                if(tooltip.search("JSON/")==0) {
                    tooltip=tooltip.substring(5);
                    var tooltipJSON="";
                    if(outputTarget=="takeoff" ) {
                        if(performance_plane_takeoffJSON.hasOwnProperty(tooltip)) {
                            tooltipJSON=performance_plane_takeoffJSON[tooltip];   
                        }
                    } 
                    else {
                        if(performance_plane_landingJSON.hasOwnProperty(tooltip)) {
                            tooltipJSON=performance_plane_landingJSON[tooltip]; 
                        }                         
                    }
                    tooltip=JSON.stringify(tooltipJSON);
                    tooltip=tooltip.replace("},", "},<br>");
                    tooltip=tooltip.replace(":{", ":<br>{");
                }
                if(outputTarget=="all" || outputTarget=="takeoff" ) {
                    document.getElementById("id_glider_o_"+key+"/tooltip").innerHTML=tooltip;
                }
                if(outputTarget=="all" || outputTarget=="landing" ) {
                    document.getElementById("id_landing_o_"+key+"/tooltip").innerHTML=tooltip;
                }
            }
        }
    }   
 }

//==============================================
// Function: updateDisplayOuputs
// Purpose: update the display of  outputs
//==============================================

function updateDisplayOuputs() {
    // loop on all outputs
    for (var key in Outputs) {
        if(key.search("/")==-1) {
            var value=Outputs[key];
var keyInner="id_glider_o_"+key;
            document.getElementById("id_glider_o_"+key).innerHTML=getDisplayedValue(Outputs,key).toFixed(0);  
            document.getElementById("id_glider_o_"+key+"/unit").innerHTML=Outputs[key+"/displayedunit"]; 
            document.getElementById("id_glider_o_"+key).readOnly=true;
            document.getElementById("id_glider_o_"+key).style.backgroundColor = ReadOnlyColor;
       }
    }
}

//==============================================
// Function: updateDisplayInputs
// Purpose: update the display of inputs
//==============================================
function updateDisplayInputs() 
{
    // loop on all Inputs
    for (var key in Inputs) {
        if(key.search("/")==-1) {
            var value=Inputs[key];
            var unitType=Inputs[key+"/unittype"];
            if(unitType=="string") {
                document.getElementById("id_glider_i_"+key).value=value;
                document.getElementById("id_glider_i_"+key+"/unit").innerHTML="";
            }
            else {
                document.getElementById("id_glider_i_"+key).value=getDisplayedValue(Inputs,key).toFixed(0);  
                document.getElementById("id_glider_i_"+key+"/unit").innerHTML=Inputs[key+"/displayedunit"];           
             }
            document.getElementById("id_glider_i_"+key).readOnly=false;
            document.getElementById("id_glider_i_"+key).style.backgroundColor = "white"; 
         }
    }
}
//==============================================
// Function: prefillDropdownMenus
// Purpose: Prefill a Menu
//==============================================

function prefillDropdownMenus(selectName, valuesArray, theDefault) {

	var select = document.getElementById(selectName);
 
	for (var i = 0; i < valuesArray.length; i++) {
		 var option = document.createElement("option");
		 option.text = valuesArray[i];
		 option.value = valuesArray[i];
         if(theDefault==option.value) {
            option.selected=true;
         }
		 select.add(option) ;
	}
}
//===============================================
// Main
var ReadOnlyColor="AliceBlue";

// init Inputs
var Inputs=Array();
Inputs["station"]="EBTX";
Inputs["station/unittype"]="string";
Inputs["qnh"]=1013;
Inputs["qnh/unit"]="hPa";
Inputs["qnh/displayedunit"]="hPa";
Inputs["qnh/unittype"]="pressure";
Inputs["altitude"]=1091;
Inputs["altitude/unit"]="ft";
Inputs["altitude/displayedunit"]="m";
Inputs["altitude/unittype"]="length";
Inputs["temperature"]=12;
Inputs["temperature/unit"]="C";
Inputs["temperature/displayedunit"]="C";
Inputs["temperature/unittype"]="temperature";


//Init outputs 
var Outputs=Array();
// General outputs
Outputs["density_altitude"]=0;
Outputs["density_altitude/unit"]="ft";
Outputs["density_altitude/displayedunit"]="ft";
Outputs["density_altitude/unittype"]="length";
Outputs["density_altitude/tooltip"]="Altitude Densité(ft) : Altitude pression(ft) + 118.8(ft/C) * (T(C) - T ISA(C))";

Outputs["altitude_4500ft_qfe"]=0;
Outputs["altitude_4500ft_qfe/unit"]="ft";
Outputs["altitude_4500ft_qfe/displayedunit"]="m";
Outputs["altitude_4500ft_qfe/unittype"]="length";
Outputs["altitude_4500ft_qfe/tooltip"]="Formule a mettre a jour";
Outputs["altitude_4500ft_qnh"]=0;
Outputs["altitude_4500ft_qnh/unit"]="ft";
Outputs["altitude_4500ft_qnh/displayedunit"]="m";
Outputs["altitude_4500ft_qnh/unittype"]="length";
Outputs["altitude_4500ft_qnh/tooltip"]="Formule a mettre a jour";

Outputs["altitude_fl55_qfe"]=0;
Outputs["altitude_fl55_qfe/unit"]="ft";
Outputs["altitude_fl55_qfe/displayedunit"]="m";
Outputs["altitude_fl55_qfe/unittype"]="length";
Outputs["altitude_fl55_qfe/tooltip"]="Formule a mettre a jour";
Outputs["altitude_fl55_qnh"]=0;
Outputs["altitude_fl55_qnh/unit"]="ft";
Outputs["altitude_fl55_qnh/displayedunit"]="m";
Outputs["altitude_fl55_qnh/unittype"]="length";
Outputs["altitude_fl55_qnh/tooltip"]="Formule a mettre a jour";

Outputs["altitude_fl65_qfe"]=0;
Outputs["altitude_fl65_qfe/unit"]="ft";
Outputs["altitude_fl65_qfe/displayedunit"]="m";
Outputs["altitude_fl65_qfe/unittype"]="length";
Outputs["altitude_fl65_qfe/tooltip"]="Formule a mettre a jour";
Outputs["altitude_fl65_qnh"]=0;
Outputs["altitude_fl65_qnh/unit"]="ft";
Outputs["altitude_fl65_qnh/displayedunit"]="m";
Outputs["altitude_fl65_qnh/unittype"]="length";
Outputs["altitude_fl65_qnh/tooltip"]="Formule a mettre a jour";

Outputs["altitude_fl75_qfe"]=0;
Outputs["altitude_fl75_qfe/unit"]="ft";
Outputs["altitude_fl75_qfe/displayedunit"]="m";
Outputs["altitude_fl75_qfe/unittype"]="length";
Outputs["altitude_fl75_qfe/tooltip"]="Formule a mettre a jour";
Outputs["altitude_fl75_qnh"]=0;
Outputs["altitude_fl75_qnh/unit"]="ft";
Outputs["altitude_fl75_qnh/displayedunit"]="m";
Outputs["altitude_fl75_qnh/unittype"]="length";
Outputs["altitude_fl75_qnh/tooltip"]="Formule a mettre a jour";

Outputs["altitude_fl95_qfe"]=0;
Outputs["altitude_fl95_qfe/unit"]="ft";
Outputs["altitude_fl95_qfe/displayedunit"]="m";
Outputs["altitude_fl95_qfe/unittype"]="length";
Outputs["altitude_fl95_qfe/tooltip"]="Formule a mettre a jour";
Outputs["altitude_fl95_qnh"]=0;
Outputs["altitude_fl95_qnh/unit"]="ft";
Outputs["altitude_fl95_qnh/displayedunit"]="m";
Outputs["altitude_fl95_qnh/unittype"]="length";
Outputs["altitude_fl95_qnh/tooltip"]="Formule a mettre a jour";



    //jQuery("#bookingMessageModal").modal('show') ;

    // Moved to the $body_preamble to allow normal mobile page initialization
    // window.onload=mobile_performance_page_loaded();

