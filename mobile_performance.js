// JavaScript used by nodedefrais.php to manage the page
//
function mobile_performance_page_loaded() {

  //document.getElementById("id_notedefrais_input_total").readOnly=true;;
  //document.getElementById("id_notedefrais_input_total").style.backgroundColor = ReadOnlyColor;
  //document.getElementById("id_notedefrais_input_odooreference").readOnly=true;;
  //document.getElementById("id_notedefrais_input_odooreference").style.backgroundColor = ReadOnlyColor;
 

  
    document.getElementById("id_takeoff_i_qnh").onchange = function() {
        QNHChanged();
    };
    document.getElementById("id_takeoff_i_altitude").onchange = function() {
        altitudeChanged();
    };
    document.getElementById("id_takeoff_i_temperature").onchange = function() {
        temperatureChanged();
    };
     document.getElementById("id_takeoff_i_wind_direction").onchange = function() {
        windChanged();
    };
    document.getElementById("id_takeoff_i_wind_speed").onchange = function() {
        windChanged();
    };
    document.getElementById("id_takeoff_i_runway_number").onchange = function() {
        windChanged();
    };
    document.getElementById("id_takeoff_i_runway_type").onchange = function() {
        runwayTypeChanged();
    };
    document.getElementById("id_takeoff_i_pilot_skill").onchange = function() {
        pilotSkillChanged();
    };
    document.getElementById("id_takeoff_i_aircraft_coefficiant").onchange = function() {
        aircraftCoefficiantChanged();
    };
    document.getElementById("id_takeoff_i_weight").onchange = function() {
        weightChanged();
    };
    document.getElementById("id_takeoff_i_flaps").onchange = function() {
        flapsChanged();
    };
    document.getElementById("id_plane_select").onchange = function() {
        planeChanged();
    };
    
    //document.getElementById("id_notedefrais_rowinput").style.display="none";
    //document.getElementById("id_submit_notedefrais").disabled=true;
    planeChanged();
    setToolTip();
}

//==============================================
// Function: planeChanged
// Purpose: 
//==============================================
function planeChanged()
{
    var plane=document.getElementById("id_plane_select").value;
    performance_plane_takeoffJSON=performanceJSON.performance[plane].takeoff;
    document.getElementById("id_takeoff_plane").innerHTML=plane;
    updateAll();
}
//==============================================
// Function: QNHChanged
// Purpose: 
//==============================================
function QNHChanged()
{
    takeoffInputs["qnh"]=Number(document.getElementById("id_takeoff_i_qnh").value);
    updateAll();
}

//==============================================
// Function: altitudeChanged
// Purpose: 
//==============================================
function altitudeChanged()
{
    takeoffInputs["altitude"]=Number(document.getElementById("id_takeoff_i_altitude").value);
    updateAll();
}
//==============================================
// Function: temperatureChanged
// Purpose: 
//==============================================
function temperatureChanged()
{
    takeoffInputs["temperature"]=Number(document.getElementById("id_takeoff_i_temperature").value);
    updateAll();
}

//==============================================
// Function: windChanged
// Purpose: 
//==============================================
function windChanged()
{
    takeoffInputs["runway_number"]=Number(document.getElementById("id_takeoff_i_runway_number").value);
    takeoffInputs["wind_direction"]=Number(document.getElementById("id_takeoff_i_wind_direction").value);
    takeoffInputs["wind_speed"]=Number(document.getElementById("id_takeoff_i_wind_speed").value);
    updateAll();
}
//==============================================
// Function: runwayTypeChanged
// Purpose: 
//==============================================
function runwayTypeChanged()
{
    takeoffInputs["runway_type"]=document.getElementById("id_takeoff_i_runway_type").value;
    updateAll();
}
//==============================================
// Function: pilotSkillChanged
// Purpose: 
//==============================================
function pilotSkillChanged()
{
    takeoffInputs["pilot_skill"]=document.getElementById("id_takeoff_i_pilot_skill").value;
    updateAll();
}
//==============================================
// Function: pilotSkillChanged
// Purpose: 
//==============================================
function aircraftCoefficiantChanged()
{
    takeoffInputs["aircraft_coefficiant"]=document.getElementById("id_takeoff_i_aircraft_coefficiant").value;
    updateAll();
}
//==============================================
// Function: weightChanged
// Purpose: 
//==============================================
function weightChanged()
{
    takeoffInputs["weight"]=Number(document.getElementById("id_takeoff_i_weight").value);
    updateAll();
}
//==============================================
// Function: flapsChanged
// Purpose: 
//==============================================
function flapsChanged()
{
    takeoffInputs["flaps"]=Number(document.getElementById("id_takeoff_i_flaps").value);
    updateAll();
}
//==============================================
// Function: updateAll
// Purpose: 
//==============================================
function updateAll()
{
    updateTemperature();
    updatePressureAltitude();
    updateDensityAltitude();
    updateWind();
    updateIASRoll();
    updateDistanceRoll();
    updateIAS50ft();
    updateDistance50ft();
    updateIASBestAngle();
    updateMaxRoC();
    updateTakeoffDisplay();
}
//==============================================
// Function: updateTemperature
// Purpose: 
//==============================================
function updateTemperature()
{
    var temperature=takeoffInputs["temperature"];
    var altitude=takeoffInputs["altitude"];
    var temperatureISA=computeTemperatureISA(altitude);
    var temperatureDeltaISA=temperature - temperatureISA
    takeoffOutputs["temperature_isa"]=temperatureISA;
    takeoffOutputs["temperature_delta_isa"]=temperatureDeltaISA;
    document.getElementById("id_takeoff_o_temperature_isa").innerHTML=temperatureISA.toFixed(0);
    document.getElementById("id_takeoff_o_temperature_delta_isa").innerHTML=temperatureDeltaISA.toFixed(0);
}
//==============================================
// Function: updatePressureAltitude
// Purpose: 
//==============================================
function updatePressureAltitude()
{
    var qnh=takeoffInputs["qnh"];
    var altitude=takeoffInputs["altitude"];
    var altitudePressure=computePressureAltitude(altitude, qnh);
    takeoffOutputs["pressure_altitude"]=altitudePressure;
    //document.getElementById("id_takeoff_o_pressure_altitude").value=altitudePressure.toFixed(0);
    document.getElementById("id_takeoff_o_pressure_altitude").innerHTML=altitudePressure.toFixed(0);
}

//==============================================
// Function: updateDensityAltitude
// Purpose: 
//==============================================
function updateDensityAltitude()
{
    var qnh=takeoffInputs["qnh"];
    var altitude=takeoffInputs["altitude"];
    var temperature=takeoffInputs["temperature"];
    var altitudeDensity=computeDensityAltitude(altitude, qnh, temperature);
    takeoffOutputs["density_altitude"]=altitudeDensity;
 
    document.getElementById("id_takeoff_o_density_altitude").innerHTML=altitudeDensity.toFixed(0);
}
//==============================================
// Function: updateWind
// Purpose: 
//==============================================
function updateWind()
{
    var runway_direction=takeoffInputs["runway_number"]*10;
    var windDirection=takeoffInputs["wind_direction"];
    var wind_speed=takeoffInputs["wind_speed"];

    var windHeadSpeed=computeWindHeadSpeed(runway_direction, wind_speed, windDirection);
    var windCrossSpeed=computeWindCrossSpeed(runway_direction, wind_speed, windDirection);
    takeoffOutputs["head_wind_speed"]=windHeadSpeed;
    takeoffOutputs["cross_wind_speed"]=windCrossSpeed;
    document.getElementById("id_takeoff_o_head_wind_speed").innerHTML=windHeadSpeed.toFixed(0);
    document.getElementById("id_takeoff_o_cross_wind_speed").innerHTML=windCrossSpeed.toFixed(0);
}
//==============================================
// Function: updateIASRoll
// Purpose:  use foncion IASRoll
//==============================================
function updateIASRoll()
{
    var rollSpeed=computeIASRoll(takeoffInputs);
    takeoffOutputs["ias_roll"]=rollSpeed;
    document.getElementById("id_takeoff_o_ias_roll").innerHTML=rollSpeed.toFixed(0);   
}
//==============================================
// Function: updateDistanceRoll
// Purpose:  use foncion distance_roll
//==============================================
function updateDistanceRoll()
{
    var rollDistance=computeDistanceRoll(takeoffInputs, takeoffOutputs);
    takeoffOutputs["distance_roll"]=rollDistance;
    document.getElementById("id_takeoff_o_distance_roll").innerHTML=convertUnit(rollDistance,"length","ft","m").toFixed(0);   
}
//==============================================
// Function: updateIAS50ft
// Purpose:  use foncion IAS50ft
//==============================================
function updateIAS50ft()
{
    var ias50ftSpeed=computeIAS50ft(takeoffInputs);
    takeoffOutputs["ias_50ft"]=ias50ftSpeed;
    document.getElementById("id_takeoff_o_ias_50ft").innerHTML=ias50ftSpeed.toFixed(0);   
}
//==============================================
// Function: updateDistance50ft
// Purpose:  use foncion distance_50ft
//==============================================
function updateDistance50ft()
{
    var distance50ft=computeDistance50ft(takeoffInputs, takeoffOutputs);
    takeoffOutputs["distance_50ft"]=distance50ft;
    document.getElementById("id_takeoff_o_distance_50ft").innerHTML=convertUnit(distance50ft,"length","ft","m").toFixed(0);   
}

//==============================================
// Function: updateTakeoffInputs
// Purpose: temperatureISA= 15- 2* Altitude /1000 (en ft)
//==============================================
function updateTakeoffInputs()
{
    return 15.0 - 2.0* theAltitude/1000.;
}
//==============================================
// Function: updateIASRoll
// Purpose:  use foncion IASRoll
//==============================================
function updateIASBestAngle()
{
    var speed=computeIASBestAngle(takeoffInputs, takeoffOutputs);
    takeoffOutputs["ias_best_angle"]=speed;
    document.getElementById("id_takeoff_o_ias_best_angle").innerHTML=speed.toFixed(0);   
}
//==============================================
// Function: updateMaxRoC
// Purpose:  use foncion IASRoll
//==============================================
function updateMaxRoC()
{
    var speed=computeMaxRoC(takeoffInputs, takeoffOutputs);
    takeoffOutputs["max_roc"]=speed;
    document.getElementById("id_takeoff_o_max_roc").innerHTML=speed.toFixed(0);   
    speed=computeIASMaxRoC(takeoffInputs, takeoffOutputs);
    takeoffOutputs["ias_max_roc"]=speed;
    document.getElementById("id_takeoff_o_ias_max_roc").innerHTML=speed.toFixed(0);   
}
//==============================================
// Function: updateTakeoffDisplay
// Purpose:  update the display
//==============================================
function updateTakeoffDisplay()
{
    var canvas = document.getElementById("id_takeoff_o_canvas");
    var ctx = canvas.getContext("2d");
    var canvasWidth=canvas.width;
    var canvasHeight=canvas.height;
    ctx.clearRect(0, 0, canvasWidth, canvasHeight);
 // RunwayLength=799m
    var runwayLength=799.0;
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
    var yBegin=50.0;
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
    var rollDistance=convertUnit(takeoffOutputs["distance_roll"],"length","ft","m");
    var distance50ft=convertUnit(takeoffOutputs["distance_50ft"],"length","ft","m");
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

    ctx.fillText(rollDistance.toFixed(0)+"m",x1CenterLine+xRollDistance/2.0,y2CenterLine+20-2);
    //ctx.fillText("Roll "+iasRoll+"MPH",x1CenterLine+xRollDistance,y2CenterLine-2);
    drawArrow(ctx,x1CenterLine,y2CenterLine+20,x1CenterLine+xRollDistance,y2CenterLine+20,1,"red");
 
    ctx.fillStyle = "green";
    ctx.fillText(distance50ft.toFixed(0)+"m",x1CenterLine+x50ftDistance/2.0,y2CenterLine-y50ftDistance-10-2);
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
    var density_altitude=convertUnit(takeoffOutputs["density_altitude"],"pressure","hPa","hPa");
    var head_wind_speed=convertUnit(takeoffOutputs["head_wind_speed"],"speed","kt","kt");
    var ias_roll=convertUnit(takeoffOutputs["ias_roll"],"speed","MPH","MPH");
    var ias_50ft=convertUnit(takeoffOutputs["ias_50ft"],"speed","MPH","MPH");
    var ias_best_angle=convertUnit(takeoffOutputs["ias_best_angle"],"speed","MPH","MPH");
    var max_roc=convertUnit(takeoffOutputs["max_roc"],"speed","ft/min","ft/min");
    var ias_max_roc= convertUnit(takeoffOutputs["ias_max_roc"],"speed","MPH","MPH");
 
    // Height over tree (ft)= 50ft + MaxRoc*time(min)= MaxROC (ft/min)* distanceToTree/speed (ft/min)
    // Height = 50ft +MaxRoc+ (DistanceTree(m)-Distance50ft(m))*3.281/(Speed MPH * 5279.987/60.0)
    var heightOverTree= 50.0+max_roc*convertUnit(treeDistance-distance50ft,"length","m","ft")/convertUnit(ias_max_roc,"speed","MPH","ft/min");

    // Additional info
    var yInfo=5;
    yInfo+=yFont;
    ctx.fillText("Density Altitude:"+density_altitude.toFixed(0)+"hPa",xInfo,yInfo);
    yInfo+=yFont;
    ctx.fillText("Max RoC:"+max_roc.toFixed(0)+"ft/min",xInfo,yInfo);
 
    //Speed
    var text="IAS: Roll="+ias_roll.toFixed(0)+"MPH";
    ctx.fillText(text,xSpeedInfo,ySpeedInfo);
    xSpeedInfo+=text.length*yFont*0.6;

    text="50ft="+ias_50ft.toFixed(0)+"MPH";
    ctx.fillText(text,xSpeedInfo,ySpeedInfo);
    xSpeedInfo+=text.length*yFont*0.6;

    text="Max RoC="+ias_max_roc.toFixed(0)+"MPH";
    ctx.fillText(text,xSpeedInfo,ySpeedInfo);
    xSpeedInfo+=text.length*yFont*0.6+10.0;

    text="Best Angle RoC="+ias_best_angle.toFixed(0)+"MPH";
    ctx.fillText(text,xSpeedInfo,ySpeedInfo);
    xSpeedInfo+=text.length*yFont*0.6;
    
    // Display from 50ft to Tree
    ctx.beginPath();
    ctx.setLineDash([]);
    ctx.moveTo(x1CenterLine+x50ftDistance,y2CenterLine-y50ftDistance);
    ctx.lineTo(xTree,y2CenterLine-heightOverTree*scaleY);
    ctx.lineTo(xTree,y2CenterLine-yTree);
    ctx.stroke();
    ctx.fillText(heightOverTree.toFixed(0)+"ft",xTree-4.0*yFont*0.6,y2CenterLine-yTree-20.0);
}
//==============================================
// Function: updateMaxRoC
// Purpose:  use foncion IASRoll
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
// Function: computeWindHeadSpeed
// Purpose: headWindSpeed= cos(runway_direction-wind_speed)*windDirection
//==============================================
function computeWindHeadSpeed(runway_direction, wind_speed, windDirection) {
    return Math.cos(3.14159*(runway_direction-windDirection)/180.0)*wind_speed;
}

//==============================================
// Function: computeWindCrossSpeed
// Purpose: crossWindSpeed= sin(runway_direction-wind_speed)*windDirection
//==============================================
function computeWindCrossSpeed(runway_direction, wind_speed, windDirection) {
    return Math.abs(Math.sin(3.14159*(runway_direction-windDirection)/180.0)*wind_speed);
}
 
//==============================================
// Function: computeIASRoll
// Purpose: Compute Roll IAS from JSON Info ("Performance/plane/takeoff/IAS_roll")
//==============================================
function computeIASRoll(theTakeoffInputs, theTakeoffOutputs)
{
    if(!performance_plane_takeoffJSON.hasOwnProperty("IAS_roll")) {
        return 0.0;
    }
    var iasrollFct=performance_plane_takeoffJSON.IAS_roll;
    return computeValue(iasrollFct, theTakeoffInputs, theTakeoffOutputs);
}

//==============================================
// Function: computeDistanceRoll
// Purpose: Compute Roll Distance from JSON Info ("Performance/plane/takeoff/distance_roll")
//==============================================
function computeDistanceRoll(theTakeoffInputs, theTakeoffOutputs)
{
    if(!performance_plane_takeoffJSON.hasOwnProperty("distance_roll")) {
        return 0.0;
    }
    var distanceRollFct=performance_plane_takeoffJSON.distance_roll;
    return computeValue(distanceRollFct, theTakeoffInputs, theTakeoffOutputs);
}

//==============================================
// Function: computeIAS50ft
// Purpose: Compute Roll IAS from JSON Info ("Performance/plane/takeoff/IAS_50ft")
//==============================================
function computeIAS50ft(theTakeoffInputs, theTakeoffOutputs)
{
    if(!performance_plane_takeoffJSON.hasOwnProperty("IAS_50ft")) {
        return 0.0;
    }
    var ias50ftFct=performance_plane_takeoffJSON.IAS_50ft;
    return computeValue(ias50ftFct, theTakeoffInputs, theTakeoffOutputs);
}

//==============================================
// Function: computeDistance50ft
// Purpose: Compute Roll Distance from JSON Info ("Performance/plane/takeoff/distance_50ft")
//==============================================
function computeDistance50ft(theTakeoffInputs, theTakeoffOutputs)
{
    if(!performance_plane_takeoffJSON.hasOwnProperty("distance_50ft")) {
        return 0.0;
    }
    var distance50ftFct=performance_plane_takeoffJSON.distance_50ft;
    return computeValue(distance50ftFct, theTakeoffInputs, theTakeoffOutputs);
}

//==============================================
// Function: computeIASBestAngle
// Purpose: Compute Best Angle IAS from JSON Info ("Performance/plane/takeoff/IAS_best_angle")
//==============================================
function computeIASBestAngle(theTakeoffInputs, theTakeoffOutputs)
{
    if(!performance_plane_takeoffJSON.hasOwnProperty("IAS_best_angle")) {
        return 0.0;
    }
    var iasFct=performance_plane_takeoffJSON.IAS_best_angle;
    return computeValue(iasFct, theTakeoffInputs, theTakeoffOutputs);
}

//==============================================
// Function: computeIASMaxRoC
// Purpose: Compute Max RoC IAS from JSON Info ("Performance/plane/takeoff/IAS_Max_ROC")
//==============================================
function computeIASMaxRoC(theTakeoffInputs, theTakeoffOutputs)
{
    if(!performance_plane_takeoffJSON.hasOwnProperty("IAS_Max_ROC")) {
        return 0.0;
    }
    var iasFct=performance_plane_takeoffJSON.IAS_Max_ROC
    return computeValue(iasFct, theTakeoffInputs, theTakeoffOutputs);
}

//==============================================
// Function: computeMaxRoC
// Purpose: Compute max roc from JSON Info ("Performance/plane/takeoff/Max_ROC")
//==============================================
function computeMaxRoC(theTakeoffInputs, theTakeoffOutputs)
{
    if(!performance_plane_takeoffJSON.hasOwnProperty("Max_ROC")) {
        return 0.0;
    }
    var iasFct=performance_plane_takeoffJSON.Max_ROC
    return computeValue(iasFct, theTakeoffInputs, theTakeoffOutputs);
}

//==============================================
// Function: computeValue
// Purpose: Compute a value for a JSON feature
//==============================================
function computeValue(theFeature, theTakeoffInputs, theTakeoffOutputs)
{
    var aValueFct=theFeature.value;
    var value=0.0;
    value=computeFunction(aValueFct,theTakeoffInputs, theTakeoffOutputs);
    if(theFeature.hasOwnProperty("coefficiant")) {
        var coefficiants=theFeature.coefficiant;
        for (const [key, fct] of Object.entries(coefficiants)) {
            value*=computeFunction(fct, theTakeoffInputs, theTakeoffOutputs);
        }
    }
    if(theFeature.hasOwnProperty("additional")) {
        var additional=theFeature.additional;
        for (const [key, fct] of Object.entries(additional)) {
            value+=computeFunction(fct, theTakeoffInputs, theTakeoffOutputs);
        }
    }
    return value;
}
//==============================================
// Function: computeFunction
// Purpose: Compute a value from a function
//==============================================
function computeFunction(theFunction, theTakeoffInputs, theTakeoffOutputs)
{
    var functionType=theFunction.function_type;
    var value=0.0;
    if(functionType=="constant") {
        value=computeConstantFunction(theFunction, theTakeoffInputs, theTakeoffOutputs);
    }
    else if(functionType=="linear") {
        value=computeLinearFunction(theFunction, theTakeoffInputs, theTakeoffOutputs);
    }
    else if(functionType=="table") {
        value=computeTableFunction(theFunction, theTakeoffInputs, theTakeoffOutputs);
    }
    else if(functionType=="enumeration") {
        value=computeEnumerationFunction(theFunction, theTakeoffInputs, theTakeoffOutputs);
    }
    else {
        alert("Error:computeFunction:Unknown function type "+functionType);
    }
    return value;
}

//==============================================
// Function: computeConstantFunction
// Purpose: Compute a value from a constant function
//==============================================
function computeConstantFunction(theFunction, theTakeoffInputs, theTakeoffOutputs)
{
    //{"function_type": "constant", "values": [64], "units": ["MPH"], "unittypes":"speed"}
    return value=theFunction.values[0];
}
//==============================================
// Function: computeEnumerationFunction
// Purpose: Compute a value from a Enumeration function
//==============================================
function computeEnumerationFunction(theFunction, theTakeoffInputs, theTakeoffOutputs)
{
    //{"function_type": "enumeration", "columns": ["runway_type"], "enumerations":["Alphast", "Grass"], "values": [1.0, 1.07], "units": ["unitless"]}
    var anEnum="";
    var field=theFunction.columns[0];
    var value=0.0;
    var anEnums=theFunction.enumerations;
    if(theTakeoffInputs.hasOwnProperty(field)) {
        anEnum=theTakeoffInputs[field];
    }
    else if(theTakeoffOutputs.hasOwnProperty(field)) {
        anEnum=theTakeoffOutputs[field];
 
    }
    for(var i=0;i<anEnums.length;i++)
    {
        if(anEnums[i]==anEnum) {
            return theFunction.values[i];
        }
    }
    alert("ERROR:computeEnumerationFunction: unknown enum name "+anEnum+" in the enumerations for "+ field);
    return value;
}

//==============================================
// Function: computeLinearFunction
// Purpose: Compute a value from a linear function
//==============================================
function computeLinearFunction(theFunction, theTakeoffInputs, theTakeoffOutputs)
{
    var x_name=theFunction.columns[0];
    var xValue=0;
    var xUnit="";
    var xUnitType="";
    var value=0.0;
    if(theTakeoffInputs.hasOwnProperty(x_name)) {
        xValue=theTakeoffInputs[x_name];
        xUnitType=theTakeoffInputs[x_name+"/unittype"];
        xUnit=theTakeoffInputs[x_name+"/unit"];
    }
    else if(theTakeoffOutputs.hasOwnProperty(x_name)) {
        xValue=theTakeoffOutputs[x_name];
        xUnitType=theTakeoffOutputs[x_name+"/unittype"];
        xUnit=theTakeoffOutputs[x_name+"/unit"];
    }
    else {
        alert("ERROR:computeLinearFunction:unknown field "+x_name);
        return value;
    }
    xValue=convertUnit(xValue,
        xUnitType,
        xUnit,
        theFunction.units[0]);

    value=theFunction.values[0]+(
        xValue-theFunction.xvalues[0])*
        (theFunction.values[1]-theFunction.values[0])/(theFunction.xvalues[1]-theFunction.xvalues[0]);
    return value;
}

//==============================================
// Function: computeTableFunction
// Purpose: Compute a value from a function
//==============================================
function computeTableFunction(theFunction, theTakeoffInputs, theTakeoffOutputs)
{
    /*
    "distance_roll":{"function_type": "table", "value": 
    {"columns": ["weight", "head_wind_speed", "pressure_altitude", "value"],
        "units" : ["lb", "kt", "ft","ft"],
        "values": [
        1600,  0,    0,  735,  // rown  0
        1600,  0, 2500,  910,  // rown  1
        1600,  0, 5000, 1115,  // rown  2
        1600,  0, 7500, 1360,  // rown  3
        1600,  5,    0,  500,  // rown  4
        1600,  5, 2500,  630,  // rown  5
        1600,  5, 5000,  780,  // rown  6
        1600,  5, 7500,  970,  // rown  7
        1600, 10,    0,  305,  // rown  8
        1600, 10, 2500,  395,  // rown  9
        1600, 10, 5000,  505,  // rown 10
        1600, 10, 7500,  640], // rown 11
        "grass_coefficiant":{"function_type": "constant", "value": 1.07},
        "temperature_coefficiant": {"function_type": "linear", "x":[0, 35], "value": [0, 1.1],  "unit_x": "delta_F"}


         InterpolationArray for value 1600, 7.5, 3750
         interpolation 1 : 
            row 5 1600, 5, 2500, 630
            row 6 1600, 5, 5000, 780
            row 5-6 1600, 5, 3750, 705
         interpolation 2 :
            row 9  1600, 10, 2500,  395
            row 10 1600, 10, 5000,  505
            rown 9-10 1600, 10, 3750, 450
         interpolation 3 : 
            row 5-6 1600, 5, 3750, 705
            rown 9-10 1600, 10, 3750, 450
            rown (5-6)(9-10) 1600, 7.5, 3750, 777.5
 
    */
   
    var functionType=theFunction.function_type;
    if(functionType=="table") {
        var columns=theFunction.columns;
        var columnUnits=theFunction.units;
        var columnCount=columns.length;
        var tableValues=theFunction.values;
        var rowCount=tableValues.length/columnCount; 

        // 1. Retrieve the input values to be used from theTakeoffInputs & theTakeoffOutputs
        var columnInputValues=Array();
        for(var columnIndex=0;columnIndex<columnCount-1;columnIndex++) {
            columnInputValues[columnIndex]=0.0;
            if(theTakeoffInputs.hasOwnProperty(columns[columnIndex])) {
                columnInputValues[columnIndex]=theTakeoffInputs[columns[columnIndex]];
            }
            else if(theTakeoffOutputs.hasOwnProperty(columns[columnIndex])) {
                columnInputValues[columnIndex]=theTakeoffOutputs[columns[columnIndex]];
            }
            else {
                alert("ERROR:computeTableFunction: unknown column name "+columns[columnIndex]);
                return 0.0;
            }
        }

        // Compute values for each values
        // Structure MapTable{1600={0={0,2500,5000,7000},5={0,2500,5000,7000},10={0,2500,5000,7000}}}}
        // 2. Creation of the table converted into a Map
        // {{1600} {0,5,10} {0,2500,5000,7000,0,2500,...}}
        // Structure MapTable={[1600]={[0]={[0]=735,[2500]=910, [5000]=1115,[7500]=1360]},[5]={...}}}
        var value=0.0;
        var mapTable=new Map();
        var columnMap=new Map();
        //mapTable[tableValues[0]]=columnMap; 
        mapTable.set(tableValues[0], columnMap);
        for(var rowIndex=0; rowIndex<rowCount;rowIndex++) {
            for(var columnIndex=0;columnIndex<columnCount-1;columnIndex++) {
                var index=rowIndex*columnCount+columnIndex;
                var columnValue=tableValues[index];
                if(columnIndex==0) {
                    if(columnCount==2) {
                        mapTable.set(columnValue,tableValues[index+1]);
                    }
                    else {
                        if(mapTable.has(columnValue)) {
                            columnMap=mapTable.get(columnValue);
                        }
                        else {
                            columnMap=new Map();
                            mapTable.set(columnValue,columnMap); 
                        }
                    }
                }
                else if(columnIndex!=columnCount-2) {
                    if(columnMap.has(columnValue)) {
                        columnMap=columnMap.get(columnValue);
                    }
                    else {
                        columnMap.set(columnValue,new Map());
                        columnMap=columnMap.get(columnValue); 
                    }
                }
                else {
                    // Last column: We store the value
                    columnMap.set(columnValue,tableValues[index+1]);
                }
            }
        }

        // 3. look if the inputs are directly defined in the mapTable
        columnMap=mapTable;
        for(var columnIndex=0;columnIndex<columnCount-1;columnIndex++) {
            var columnInputValue=columnInputValues[columnIndex];
            var keyValue=-9999;
            // Look if the value are very close to a value in the table
            for (const key of columnMap.keys()) {
                 if(Math.abs(columnInputValue-key)<0.1) {
                    keyValue=key;
                    break;
                 }
            }
            if(columnMap.has(keyValue)) {
                if(columnIndex==columnCount-2) {
                    // Value found directly in the map without interpoilation
                    return columnMap.get(columnInputValue);
                }
                columnMap=columnMap.get(columnInputValue);
            }
            else {
                break;
            }
        }

        // 4. interpolate using the Map
        // 4.1 delete non used keys in the mapTable
        var columnIndex=0;
        purgeMapTable(columnIndex, columnInputValues, mapTable);

        // 4.2 Interpolate
        columnIndex=0;
        value=interpolateMapTable(columnIndex, columnInputValues, mapTable);

    }
    return value;

}
//==============================================
// Function: purgeMapTable
// Purpose: Remove non used part of the map
//==============================================

function purgeMapTable(columnIndex, columnInputValues, columnMap) {

    var columnInputValue=columnInputValues[columnIndex];
    var previousKey=-9999;
    for (const key of columnMap.keys()) {
        if(previousKey==-9999) {
            previousKey=key;
        }
        else if(columnInputValue>previousKey && columnInputValue>key) {
            columnMap.delete(previousKey);
            previousKey=key;
        }
        else if(columnInputValue<previousKey && columnInputValue<key) {
            columnMap.delete(key);
        }
        else {
            previousKey=key; 
        }
    }
    columnIndex++;
    if(columnIndex>columnInputValues.length-1) {
        return;
    }
    for (const key of columnMap.keys()) {
        purgeMapTable(columnIndex, columnInputValues, columnMap.get(key));
    }
}

//==============================================
// Function: interpolateMapTable
// Purpose: interpolation between 2 lines
//==============================================
function interpolateMapTable(columnIndex, columnInputValues, columnMap)
{
    var localColumnIndex=columnIndex;
    if(columnIndex>columnInputValues.length-1) {
        return columnMap;
    }

    var count=-1;
    var values=new Array();
    var keyValues=new Array();
    var value=0.0;
    localColumnIndex++;
    for (const key of columnMap.keys()) {
        count++;
        keyValues[count]=key;
        values[count]=interpolateMapTable(localColumnIndex, columnInputValues, columnMap.get(key));
    }
    if(count>0) {
        value=values[0]+(values[1]-values[0])*(columnInputValues[columnIndex]-keyValues[0])/(keyValues[1]-keyValues[0]);
    }
    else {
       value=values[0]; 
    }
    return value;
}

//==============================================
// Function: setToolTip
// Purpose: Set the tooltip associated to an output
//==============================================
function setToolTip()
 {
    // loop on all outputs
    for (var key in takeoffOutputs) {
        if(key.search("/")==-1) {
            var tooltip=takeoffOutputs[key+"/tooltip"];
            if(tooltip!="") {
                if(tooltip.search("JSON/")==0) {
                    tooltip=tooltip.substring(5);
                    tooltipJSON=performance_plane_takeoffJSON[tooltip];    
                    tooltip=JSON.stringify(tooltipJSON);
                    tooltip=tooltip.replace("},", "},<br>");
                    tooltip=tooltip.replace(":{", ":<br>{");
                }
                document.getElementById("id_takeoff_o_"+key+"/tooltip").innerHTML=tooltip;
            }
        }
    }   
 }

//==============================================
// Function: updateDisplayTakeoffOuputs
// Purpose: update the display of take-off outputs
//==============================================

function updateDisplayTakeoffOuputs() {
    // loop on all outputs
    for (var key in takeoffOutputs) {
        if(key.search("/")==-1) {
            var value=takeoffOutputs[key];
            //document.getElementById("id_takeoff_o_"+key).value=value.toFixed(0);
            document.getElementById("id_takeoff_o_"+key).innerHTML=convertUnit(value,
                    takeoffOutputs[key+"/unittype"],
                    takeoffOutputs[key+"/unit"],
                    takeoffOutputs[key+"/displayedunit"]).toFixed(0);  
            document.getElementById("id_takeoff_o_"+key+"/unit").innerHTML=takeoffOutputs[key+"/displayedunit"]; 
            document.getElementById("id_takeoff_o_"+key).readOnly=true;
            document.getElementById("id_takeoff_o_"+key).style.backgroundColor = ReadOnlyColor;
        }
    }
}

//==============================================
// Function: updateDisplayTakeoffInputs
// Purpose: update the display of take-off outputs
//==============================================

function updateDisplayTakeoffInputs(takeoffInputsDefault) {
    // loop on all outputs
    for (var key in takeoffInputs) {
        if(key.search("/")==-1) {
            var value=takeoffInputs[key];
            var unitType=takeoffInputs[key+"/unittype"];
            if(unitType=="string") {
                document.getElementById("id_takeoff_i_"+key).value=value;
                document.getElementById("id_takeoff_i_"+key+"/unit").innerHTML="";
            }
            else {
                document.getElementById("id_takeoff_i_"+key).value=convertUnit(value,
                    takeoffInputs[key+"/unittype"],
                    takeoffInputs[key+"/unit"],
                    takeoffInputs[key+"/displayedunit"]).toFixed(0);  
                document.getElementById("id_takeoff_i_"+key+"/unit").innerHTML=takeoffInputs[key+"/displayedunit"];           
            }
         }
    }
    // Default values in Inputs
    for (var key in takeoffInputsDefault) {
        var value=takeoffInputsDefault[key].value;
        var unit=takeoffInputsDefault[key].unit;
        var unittype=takeoffInputsDefault[key].unittype;
        var readonly=takeoffInputsDefault[key].readonly;
        if(unitType=="string") {
            document.getElementById("id_takeoff_i_"+key).value=value;
            document.getElementById("id_takeoff_i_"+key+"/unit").innerHTML="";
        }
        else {
            document.getElementById("id_takeoff_i_"+key).value=convertUnit(value,
                unittype,
                unit,
                takeoffInputs[key+"/displayedunit"]).toFixed(0);  
            document.getElementById("id_takeoff_i_"+key+"/unit").innerHTML=takeoffInputs[key+"/displayedunit"];           
        }
        if(readonly==1) {
            document.getElementById("id_takeoff_i_"+key).readOnly=true;
            document.getElementById("id_takeoff_i_"+key).style.backgroundColor = ReadOnlyColor;
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

// init takeOffInputs
var takeoffInputs=Array();
takeoffInputs["qnh"]=1013;
takeoffInputs["qnh/unit"]="hPa";
takeoffInputs["qnh/displayedunit"]="hPa";
takeoffInputs["qnh/unittype"]="pressure";
takeoffInputs["altitude"]=1542;
takeoffInputs["altitude/unit"]="ft";
takeoffInputs["altitude/displayedunit"]="ft";
takeoffInputs["altitude/unittype"]="length";
takeoffInputs["temperature"]=12;
takeoffInputs["temperature/unit"]="C";
takeoffInputs["temperature/displayedunit"]="C";
takeoffInputs["temperature/unittype"]="temperature";
takeoffInputs["runway_number"]=23;
takeoffInputs["runway_number/unittype"]="unitless";
takeoffInputs["runway_number/unit"]="";
takeoffInputs["runway_number/displayedunit"]="";
takeoffInputs["runway_type"]="Asphalt";
takeoffInputs["runway_type/unittype"]="string";
takeoffInputs["runway_slope"]=0;
takeoffInputs["runway_slope/unittype"]="unitless";
takeoffInputs["runway_slope/unit"]="%";
takeoffInputs["runway_slope/displayedunit"]="%";
takeoffInputs["pilot_skill"]="Advanced";
takeoffInputs["pilot_skill/unittype"]="string";
takeoffInputs["aircraft_coefficiant"]="POH";
takeoffInputs["aircraft_coefficiant/unittype"]="string";
takeoffInputs["wind_direction"]=230;
takeoffInputs["wind_direction/unit"]="degree";
takeoffInputs["wind_direction/displayedunit"]="degree";
takeoffInputs["wind_direction/unittype"]="planeangle";
takeoffInputs["wind_speed"]=0.0;
takeoffInputs["wind_speed/unit"]="kt";
takeoffInputs["wind_speed/displayedunit"]="kt";
takeoffInputs["wind_speed/unittype"]="speed";
takeoffInputs["weight"]=0.0;
takeoffInputs["weight/unit"]="lb";
takeoffInputs["weight/displayedunit"]="kg";
takeoffInputs["weight/unittype"]="mass";
takeoffInputs["flaps"]=0;
takeoffInputs["flaps/unit"]="degree";
takeoffInputs["flaps/displayedunit"]="degree";
takeoffInputs["flaps/unittype"]="planeangle";

//Init outputs 
var takeoffOutputs=Array();
takeoffOutputs["temperature_isa"]=0;
takeoffOutputs["temperature_isa/unit"]="C";
takeoffOutputs["temperature_isa/displayedunit"]="C";
takeoffOutputs["temperature_isa/unittype"]="temperature";
takeoffOutputs["temperature_isa/tooltip"]="Temperature ISA(C)=15.(C) - 2.(C)* Altitude(ft)/1000.(ft)";
takeoffOutputs["temperature_delta_isa"]=0;
takeoffOutputs["temperature_delta_isa/unit"]="C";
takeoffOutputs["temperature_delta_isa/displayedunit"]="C";
takeoffOutputs["temperature_delta_isa/unittype"]="temperature_delta";
takeoffOutputs["temperature_delta_isa/tooltip"]="Delta Temperature=Temperature ISA - Temperature";
takeoffOutputs["pressure_altitude"]=0;
takeoffOutputs["pressure_altitude/unit"]="hPa";
takeoffOutputs["pressure_altitude/displayedunit"]="hPa";
takeoffOutputs["pressure_altitude/unittype"]="pressure";
takeoffOutputs["pressure_altitude/tooltip"]="Altitude Pression(ft)=Altitude Terrain(ft) + (1013-QNH)*30ft/hPa";
takeoffOutputs["density_altitude"]=0;
takeoffOutputs["density_altitude/unit"]="hPa";
takeoffOutputs["density_altitude/displayedunit"]="hPa";
takeoffOutputs["density_altitude/unittype"]="pressure";
takeoffOutputs["density_altitude/tooltip"]="Altitude Densité(ft) : Altitude pression(ft) + 118.8(ft/C) * (T(C) - T ISA(C))";
takeoffOutputs["head_wind_speed"]=0;
takeoffOutputs["head_wind_speed/unit"]="kt";
takeoffOutputs["head_wind_speed/displayedunit"]="kt";
takeoffOutputs["head_wind_speed/unittype"]="speed";
takeoffOutputs["head_wind_speed/tooltip"]="Head Wind=Wind Speeed*cos(Piste Number- Wind Direction)";
takeoffOutputs["cross_wind_speed"]=0;
takeoffOutputs["cross_wind_speed/unit"]="kt";
takeoffOutputs["cross_wind_speed/displayedunit"]="kt";
takeoffOutputs["cross_wind_speed/unittype"]="speed";
takeoffOutputs["cross_wind_speed/tooltip"]="Head Wind=Wind Speeed*sin(Piste Number- Wind Direction)";
takeoffOutputs["distance_roll"]=0;
takeoffOutputs["distance_roll/unit"]="m";
takeoffOutputs["distance_roll/displayedunit"]="m";
takeoffOutputs["distance_roll/unittype"]="length";
takeoffOutputs["distance_roll/tooltip"]="JSON/distance_roll";
takeoffOutputs["distance_50ft"]=0;
takeoffOutputs["distance_50ft/unit"]="m";
takeoffOutputs["distance_50ft/displayedunit"]="m";
takeoffOutputs["distance_50ft/unittype"]="length";
takeoffOutputs["distance_50ft/tooltip"]="JSON/distance_50ft";
takeoffOutputs["ias_roll"]=0;
takeoffOutputs["ias_roll/unit"]="MPH";
takeoffOutputs["ias_roll/displayedunit"]="MPH";
takeoffOutputs["ias_roll/unittype"]="speed";
takeoffOutputs["ias_roll/tooltip"]="JSON/IAS_roll";
takeoffOutputs["ias_50ft"]=0;
takeoffOutputs["ias_50ft/unit"]="MPH";
takeoffOutputs["ias_50ft/displayedunit"]="MPH";
takeoffOutputs["ias_50ft/unittype"]="speed";
takeoffOutputs["ias_50ft/tooltip"]="JSON/IAS_50ft";
takeoffOutputs["ias_best_angle"]=0;
takeoffOutputs["ias_best_angle/unit"]="MPH";
takeoffOutputs["ias_best_angle/displayedunit"]="MPH";
takeoffOutputs["ias_best_angle/unittype"]="speed";
takeoffOutputs["ias_best_angle/tooltip"]="JSON/IAS_best_angle";
takeoffOutputs["max_roc"]=0;
takeoffOutputs["max_roc/unit"]="ft/min";
takeoffOutputs["max_roc/displayedunit"]="ft/min";
takeoffOutputs["max_roc/unittype"]="speed";
takeoffOutputs["max_roc/tooltip"]="JSON/Max_ROC";
takeoffOutputs["ias_max_roc"]=0;
takeoffOutputs["ias_max_roc/unit"]="MPH";
takeoffOutputs["ias_max_roc/displayedunit"]="MPH";
takeoffOutputs["ias_max_roc/unittype"]="speed";
takeoffOutputs["ias_max_roc/tooltip"]="JSON/IAS_Max_ROC";

// Decode notedefrais json file
var performanceJSON=JSON.parse(performanceJSONcontent);
var planes=Array();
for (const key in performanceJSON.performance) {
    planes.push(key);
}
prefillDropdownMenus("id_plane_select", planes, planes[0]);
var pilotSkills=Array("Student","Normal","Advanced");
prefillDropdownMenus("id_takeoff_i_pilot_skill", pilotSkills, takeoffInputs["pilot_skill"]);
var runwayType=Array("Asphalt","Grass");
prefillDropdownMenus("id_takeoff_i_runway_type", runwayType, takeoffInputs["runway_type"]);
var aircraftCoefficiant=Array("POH","Measured");
prefillDropdownMenus("id_takeoff_i_aircraft_coefficiant", aircraftCoefficiant, takeoffInputs["aircraft_coefficiant"]);

// Init plane
document.getElementById("id_plane_select").value=default_plane;
var performance_plane_takeoffJSON="";
var takeoffInputsDefault="";
if(performanceJSON.performance.hasOwnProperty(default_plane)) {
    performance_plane_takeoffJSON=performanceJSON.performance[default_plane].takeoff;
    takeoffInputsDefault=performanceJSON.performance[default_plane].takeoff.inputs;
}
        
updateDisplayTakeoffInputs(takeoffInputsDefault);
updateDisplayTakeoffOuputs();

//jQuery("#bookingMessageModal").modal('show') ;

// window.onload=mobile_performance_page_loaded(); // Added to <body onload="mobile_performance_page_loaded();">
