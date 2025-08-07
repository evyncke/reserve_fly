// JavaScript used by nodedefrais.php to manage the page
//
function mobile_performance_page_loaded() {

  //document.getElementById("id_notedefrais_input_total").readOnly=true;;
  //document.getElementById("id_notedefrais_input_total").style.backgroundColor = ReadOnlyColor;
  //document.getElementById("id_notedefrais_input_odooreference").readOnly=true;;
  //document.getElementById("id_notedefrais_input_odooreference").style.backgroundColor = ReadOnlyColor;
 
    document.getElementById("id_takeoff_i_qnh").onchange = function() {
        QNHChanged("takeoff");
    };
    document.getElementById("id_landing_i_qnh").onchange = function() {
        QNHChanged("landing");
    };
    document.getElementById("id_takeoff_i_altitude").onchange = function() {
        altitudeChanged("takeoff");
    };
    document.getElementById("id_landing_i_altitude").onchange = function() {
        altitudeChanged("landing");
    };
    document.getElementById("id_takeoff_i_temperature").onchange = function() {
        temperatureChanged("takeoff");
    };
    document.getElementById("id_landing_i_temperature").onchange = function() {
        temperatureChanged("landing");
    };
    document.getElementById("id_takeoff_i_wind_direction").onchange = function() {
        windChanged("takeoff");
    };
    document.getElementById("id_landing_i_wind_direction").onchange = function() {
        windChanged("landing");
    };
    document.getElementById("id_takeoff_i_wind_speed").onchange = function() {
        windChanged("takeoff");
    };
    document.getElementById("id_landing_i_wind_speed").onchange = function() {
        windChanged("landing");
    };
    document.getElementById("id_takeoff_i_runway_number").onchange = function() {
        windChanged("takeoff");
    };
    document.getElementById("id_landing_i_runway_number").onchange = function() {
        windChanged("landing");
    };
    document.getElementById("id_takeoff_i_runway_type").onchange = function() {
        runwayTypeChanged("takeoff");
    };
    document.getElementById("id_landing_i_runway_type").onchange = function() {
        runwayTypeChanged("landing");
    };
    document.getElementById("id_takeoff_i_pilot_skill").onchange = function() {
        pilotSkillChanged("takeoff");
    };
   document.getElementById("id_landing_i_pilot_skill").onchange = function() {
        pilotSkillChanged("landing");
    };
    document.getElementById("id_takeoff_i_aircraft_coefficiant").onchange = function() {
        aircraftCoefficiantChanged("takeoff");
    };
    document.getElementById("id_landing_i_aircraft_coefficiant").onchange = function() {
        aircraftCoefficiantChanged("landing");
    };
    document.getElementById("id_takeoff_i_weight").onchange = function() {
        weightChanged("takeoff");
    };
    document.getElementById("id_landing_i_weight").onchange = function() {
        weightChanged("landing");
    };
    document.getElementById("id_takeoff_i_flaps").onchange = function() {
        flapsChanged("takeoff");
    };
    document.getElementById("id_landing_i_flaps").onchange = function() {
        flapsChanged("landing");
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
    performance_plane_takeoffJSON=performanceJSON.performance[plane].takeoff.outputs;
    document.getElementById("id_takeoff_plane").innerHTML=plane;
    document.getElementById("id_landing_plane").innerHTML=plane;
    updateAll();
}
//==============================================
// Function: QNHChanged
// Purpose: 
//==============================================
function QNHChanged(perfoType)
{
    Inputs["qnh"]=Number(document.getElementById("id_"+perfoType+"_i_qnh").value);
    var otherPerfoType="landing";
    if(perfoType=="landing") {
        otherPerfoType="takeoff";
    }
    document.getElementById("id_"+otherPerfoType+"_i_qnh").value=Inputs["qnh"];
    updateAll();
}

//==============================================
// Function: altitudeChanged
// Purpose: 
//==============================================
function altitudeChanged(perfoType)
{
    Inputs["altitude"]=Number(document.getElementById("id_"+perfoType+"_i_altitude").value);
    var otherPerfoType="landing";
    if(perfoType=="landing") {
        otherPerfoType="takeoff";
    }
    document.getElementById("id_"+otherPerfoType+"_i_altitude").value=Inputs["altitude"];
    updateAll();
}
//==============================================
// Function: temperatureChanged
// Purpose: 
//==============================================
function temperatureChanged(perfoType)
{
    Inputs["temperature"]=Number(document.getElementById("id_"+perfoType+"_i_temperature").value);
   var otherPerfoType="landing";
    if(perfoType=="landing") {
        otherPerfoType="takeoff";
    }
    document.getElementById("id_"+otherPerfoType+"_i_temperature").value=Inputs["temperature"];
    updateAll();
}

//==============================================
// Function: windChanged
// Purpose: 
//==============================================
function windChanged(perfoType)
{
    Inputs["runway_number"]=Number(document.getElementById("id_"+perfoType+"_i_runway_number").value);
    Inputs["wind_direction"]=Number(document.getElementById("id_"+perfoType+"_i_wind_direction").value);
    Inputs["wind_speed"]=Number(document.getElementById("id_"+perfoType+"_i_wind_speed").value);
   var otherPerfoType="landing";
    if(perfoType=="landing") {
        otherPerfoType="takeoff";
    }
    document.getElementById("id_"+otherPerfoType+"_i_runway_number").value=Inputs["runway_number"];
    document.getElementById("id_"+otherPerfoType+"_i_wind_direction").value=Inputs["wind_direction"];
    document.getElementById("id_"+otherPerfoType+"_i_wind_speed").value=Inputs["wind_speed"];
    updateAll();
}
//==============================================
// Function: runwayTypeChanged
// Purpose: 
//==============================================
function runwayTypeChanged(perfoType)
{
    Inputs["runway_type"]=document.getElementById("id_"+perfoType+"_i_runway_type").value;
    var otherPerfoType="landing";
    if(perfoType=="landing") {
        otherPerfoType="takeoff";
    }
    document.getElementById("id_"+otherPerfoType+"_i_runway_type").value=Inputs["runway_type"];
   updateAll();
}
//==============================================
// Function: pilotSkillChanged
// Purpose: 
//==============================================
function pilotSkillChanged(perfoType)
{
    Inputs["pilot_skill"]=document.getElementById("id_"+perfoType+"_i_pilot_skill").value;
   var otherPerfoType="landing";
    if(perfoType=="landing") {
        otherPerfoType="takeoff";
    }
    document.getElementById("id_"+otherPerfoType+"_i_pilot_skill").value=Inputs["pilot_skill"];
    updateAll();
}
//==============================================
// Function: pilotSkillChanged
// Purpose: 
//==============================================
function aircraftCoefficiantChanged(perfoType)
{
    Inputs["aircraft_coefficiant"]=document.getElementById("id_"+perfoType+"_i_aircraft_coefficiant").value;
   var otherPerfoType="landing";
    if(perfoType=="landing") {
        otherPerfoType="takeoff";
    }
    document.getElementById("id_"+otherPerfoType+"_i_aircraft_coefficiant").value=Inputs["aircraft_coefficiant"];
    updateAll();
}
//==============================================
// Function: weightChanged
// Purpose: 
//==============================================
function weightChanged(perfoType)
{
    Inputs["weight"]=Number(document.getElementById("id_"+perfoType+"_i_weight").value);
    var otherPerfoType="landing";
    if(perfoType=="landing") {
        otherPerfoType="takeoff";
    }
    document.getElementById("id_"+otherPerfoType+"_i_weight").value=Inputs["weight"];
   updateAll();
}
//==============================================
// Function: flapsChanged
// Purpose: 
//==============================================
function flapsChanged(perfoType)
{
    Inputs["flaps"]=Number(document.getElementById("id_"+perfoType+"_i_flaps").value);
    var otherPerfoType="landing";
    if(perfoType=="landing") {
        otherPerfoType="takeoff";
    }
    document.getElementById("id_"+otherPerfoType+"_i_flaps").value=Inputs["flaps"];
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
    updateLandingOutputs();
    updateTakeoffDisplay();
    updateLandingDisplay();
}
//==============================================
// Function: updateTemperature
// Purpose: 
//==============================================
function updateTemperature()
{
    var temperature=Inputs["temperature"];
    var altitude=Inputs["altitude"];
    var temperatureISA=computeTemperatureISA(altitude);
    var temperatureDeltaISA=temperature - temperatureISA
    Outputs["temperature_isa"]=temperatureISA;
    Outputs["temperature_delta_isa"]=temperatureDeltaISA;
    document.getElementById("id_takeoff_o_temperature_isa").innerHTML=temperatureISA.toFixed(0);
    document.getElementById("id_takeoff_o_temperature_delta_isa").innerHTML=temperatureDeltaISA.toFixed(0);
    document.getElementById("id_landing_o_temperature_isa").innerHTML=temperatureISA.toFixed(0);
    document.getElementById("id_landing_o_temperature_delta_isa").innerHTML=temperatureDeltaISA.toFixed(0);
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
    document.getElementById("id_takeoff_o_pressure_altitude").innerHTML=altitudePressure.toFixed(0);
    document.getElementById("id_landing_o_pressure_altitude").innerHTML=altitudePressure.toFixed(0);
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
 
    document.getElementById("id_takeoff_o_density_altitude").innerHTML=altitudeDensity.toFixed(0);
    document.getElementById("id_landing_o_density_altitude").innerHTML=altitudeDensity.toFixed(0);
}
//==============================================
// Function: updateWind
// Purpose: 
//==============================================
function updateWind()
{
    var runway_direction=Inputs["runway_number"]*10;
    var windDirection=Inputs["wind_direction"];
    var wind_speed=Inputs["wind_speed"];

    var windHeadSpeed=computeWindHeadSpeed(runway_direction, wind_speed, windDirection);
    var windCrossSpeed=computeWindCrossSpeed(runway_direction, wind_speed, windDirection);
    Outputs["head_wind_speed"]=windHeadSpeed;
    Outputs["cross_wind_speed"]=windCrossSpeed;
    document.getElementById("id_takeoff_o_head_wind_speed").innerHTML=windHeadSpeed.toFixed(0);
    document.getElementById("id_takeoff_o_cross_wind_speed").innerHTML=windCrossSpeed.toFixed(0);
    document.getElementById("id_landing_o_head_wind_speed").innerHTML=windHeadSpeed.toFixed(0);
    document.getElementById("id_landing_o_cross_wind_speed").innerHTML=windCrossSpeed.toFixed(0);
}
//==============================================
// Function: updateIASRoll
// Purpose:  use foncion IASRoll
//==============================================
function updateIASRoll()
{ 
    var unitType={name:""};
    var unit={name:""};
    var displayedUnit={name:""};

    var rollSpeed=computeIASRoll(Inputs, Outputs, unitType, unit, displayedUnit);
    Outputs["ias_roll"]=rollSpeed;
    Outputs["ias_roll/unitType"]=unitType.name;
    Outputs["ias_roll/unit"]=unit.name;
    Outputs["ias_roll/displayedunit"]=displayedUnit.name;
    document.getElementById("id_takeoff_o_ias_roll").innerHTML=rollSpeed.toFixed(0);   
    document.getElementById("id_takeoff_o_ias_roll/unit").innerHTML=displayedUnit.name; 
}
//==============================================
// Function: updateDistanceRoll
// Purpose:  use foncion distance_roll
//==============================================
function updateDistanceRoll()
{
    var unitType={name:""};
    var unit={name:""};
    var displayedUnit={name:""};
    var rollDistance=computeDistanceRoll(Inputs, Outputs, unitType, unit, displayedUnit);
    Outputs["distance_roll"]=rollDistance;
    Outputs["distance_roll/unitType"]=unitType.name;
    Outputs["distance_roll/unit"]=unit.name;
    Outputs["distance_roll/displayedunit"]=displayedUnit.name;
    document.getElementById("id_takeoff_o_distance_roll").innerHTML=convertUnit(rollDistance,"length","ft","m").toFixed(0);   
    document.getElementById("id_takeoff_o_distance_roll/unit").innerHTML=displayedUnit.name; 
}
//==============================================
// Function: updateIAS50ft
// Purpose:  use foncion IAS50ft
//==============================================
function updateIAS50ft()
{
    var unitType={name:""};
    var unit={name:""};
    var displayedUnit={name:""};

    var ias50ftSpeed=computeIAS50ft(Inputs, Outputs, unitType, unit, displayedUnit);
    Outputs["ias_50ft"]=ias50ftSpeed;
    Outputs["ias_50ft/unitType"]=unitType.name;
    Outputs["ias_50ft/unit"]=unit.name;
    Outputs["ias_50ft/displayedunit"]=displayedUnit.name;
    document.getElementById("id_takeoff_o_ias_50ft").innerHTML=getDisplayedValue(Outputs, "ias_50ft").toFixed(0);   
    document.getElementById("id_takeoff_o_ias_50ft/unit").innerHTML=displayedUnit.name; 
}
//==============================================
// Function: updateDistance50ft
// Purpose:  use foncion distance_50ft
//==============================================
function updateDistance50ft()
{
    var unitType={name:""};
    var unit={name:""};
    var displayedUnit={name:""};
    var distance50ft=computeDistance50ft(Inputs, Outputs, unitType, unit, displayedUnit);
    Outputs["distance_50ft"]=distance50ft;
    Outputs["distance_50ft/unitType"]=unitType.name;
    Outputs["distance_50ft/unit"]=unit.name;
    Outputs["distance_50ft/displayedunit"]=displayedUnit.name;
    document.getElementById("id_takeoff_o_distance_50ft").innerHTML=convertUnit(distance50ft,"length","ft","m").toFixed(0);   
    document.getElementById("id_takeoff_o_distance_50ft/unit").innerHTML=displayedUnit.name; 
}

//==============================================
// Function: updateIASBestAngle
// Purpose:  use foncion best_angle
//==============================================
function updateIASBestAngle()
{
    var unitType={name:""};
    var unit={name:""};
    var displayedUnit={name:""};
    var speed=computeIASBestAngle(Inputs, Outputs, unitType, unit, displayedUnit);
    Outputs["ias_best_angle"]=speed;
    Outputs["ias_best_angle/unitType"]=unitType.name;
    Outputs["ias_best_angle/unit"]=unit.name;
    Outputs["ias_best_angle/displayedunit"]=displayedUnit.name;
    document.getElementById("id_takeoff_o_ias_best_angle").innerHTML=speed.toFixed(0);   
    document.getElementById("id_takeoff_o_ias_best_angle/unit").innerHTML=displayedUnit.name; 
}
//==============================================
// Function: updateMaxRoC
// Purpose:  use foncion max_roc & ias_max_roc
//==============================================
function updateMaxRoC()
{
    var unitType={name:""};
    var unit={name:""};
    var displayedUnit={name:""};
    var speed=computeMaxRoC(Inputs, Outputs, unitType, unit, displayedUnit);
    Outputs["max_roc"]=speed;
    Outputs["max_roc/unitType"]=unitType.name;
    Outputs["max_roc/unit"]=unit.name;
    Outputs["max_roc/displayedunit"]=displayedUnit.name;
    document.getElementById("id_takeoff_o_max_roc").innerHTML=speed.toFixed(0);   
    document.getElementById("id_takeoff_o_max_roc/unit").innerHTML=displayedUnit.name; 
    speed=computeIASMaxRoC(Inputs, Outputs, unitType, unit, displayedUnit);
    Outputs["ias_max_roc"]=speed;
    Outputs["ias_max_roc/unitType"]=unitType.name;
    Outputs["ias_max_roc/unit"]=unit.name;
    Outputs["ias_max_roc/displayedunit"]=displayedUnit.name;
    document.getElementById("id_takeoff_o_ias_max_roc").innerHTML=speed.toFixed(0);   
    document.getElementById("id_takeoff_o_ias_max_roc/unit").innerHTML=displayedUnit.name; 
}
//==============================================
// Function: updateLandingOutputs
// Purpose:  use foncion IAS_50ft_ld distance_50ft_ld distance_ground_ld
//==============================================
function updateLandingOutputs()
{
    var unitType={name:""};
    var unit={name:""};
    var displayedUnit={name:""};
    var speed=computeLandingIAS50ft(Inputs, Outputs, unitType, unit, displayedUnit);
    Outputs["ias_50ft_ld"]=speed;
    Outputs["ias_50ft_ld/unitType"]=unitType.name;
    Outputs["ias_50ft_ld/unit"]=unit.name;
    Outputs["ias_50ft_ld/displayedunit"]=displayedUnit.name;
    document.getElementById("id_landing_o_ias_50ft_ld").innerHTML=getDisplayedValue(Outputs, "ias_50ft_ld").toFixed(0); 
    document.getElementById("id_landing_o_ias_50ft_ld/unit").innerHTML=displayedUnit.name; 

    var distance=computeLandingDistance50ft(Inputs, Outputs, unitType, unit, displayedUnit);
    Outputs["distance_50ft_ld"]=distance;
    Outputs["distance_50ft_ld/unitType"]=unitType.name;
    Outputs["distance_50ft_ld/unit"]=unit.name;
    Outputs["distance_50ft_ld/displayedunit"]=displayedUnit.name;
    document.getElementById("id_landing_o_distance_50ft_ld").innerHTML=getDisplayedValue(Outputs, "distance_50ft_ld").toFixed(0);  
    document.getElementById("id_landing_o_distance_50ft_ld/unit").innerHTML=displayedUnit.name; 

    distance=computeLandingDistanceGround(Inputs, Outputs, unitType, unit, displayedUnit);
    Outputs["distance_ground_ld"]=distance;
    Outputs["distance_ground_ld/unitType"]=unitType.name;
    Outputs["distance_ground_ld/unit"]=unit.name;
    Outputs["distance_ground_ld/displayedunit"]=displayedUnit.name;
    document.getElementById("id_landing_o_distance_ground_ld").innerHTML=getDisplayedValue(Outputs, "distance_ground_ld").toFixed(0); 
    document.getElementById("id_landing_o_distance_ground_ld/unit").innerHTML=displayedUnit.name; 
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
    var rollDistance=convertUnit(Outputs["distance_roll"],"length","ft","m");
    var distance50ft=convertUnit(Outputs["distance_50ft"],"length","ft","m");
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
    var density_altitude=convertUnit(Outputs["density_altitude"],"pressure","hPa","hPa");
    var head_wind_speed=convertUnit(Outputs["head_wind_speed"],"speed","kt","kt");
    var ias_roll=convertUnit(Outputs["ias_roll"],"speed","MPH","MPH");
    var ias_50ft=convertUnit(Outputs["ias_50ft"],"speed","MPH","MPH");
    var ias_best_angle=convertUnit(Outputs["ias_best_angle"],"speed","MPH","MPH");
    var max_roc=convertUnit(Outputs["max_roc"],"speed","ft/min","ft/min");
    var ias_max_roc= convertUnit(Outputs["ias_max_roc"],"speed","MPH","MPH");
 
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
// Function: updateLandingDisplay
// Purpose:  update the display
//==============================================
function updateLandingDisplay()
{
    var canvas = document.getElementById("id_landing_o_canvas");
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
    var xRunway=canvasWidth-runwayLength*scaleX-xBegin;
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
    var xTree=x2CenterLine-treeDistance*scaleX;
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


    // Landing Ground Distance 
    // Landing 50ft Distance
    var groundDistance=convertUnit(Outputs["distance_ground_ld"],"length","ft","m");
    var distance50ft=convertUnit(Outputs["distance_50ft_ld"],"length","ft","m");
    var x1Distance50ft=x1CenterLine;
    var x2Distance50ft=x1CenterLine+distance50ft*scaleX;
    var y50ftDistance=50.0*scaleY;
 
    var x1GroundDistance=x1CenterLine+ (distance50ft-groundDistance)*scaleX;
    var x2GroundDistance=x1CenterLine+ distance50ft*scaleX;

    ctx.setLineDash([]);
    ctx.beginPath();
    ctx.setLineDash([]);
    ctx.moveTo(x1CenterLine,y2CenterLine);
    ctx.lineTo(x1CenterLine,y2CenterLine-y50ftDistance);
    ctx.lineTo(x1GroundDistance,y2CenterLine);
    ctx.lineTo(x2GroundDistance,y2CenterLine);
    ctx.moveTo(x1CenterLine,y2CenterLine-y50ftDistance);
    ctx.lineTo(x1CenterLine-(distance50ft-groundDistance)*scaleX,y2CenterLine-2.*y50ftDistance);
    ctx.stroke();

    // Ground distance
    ctx.setLineDash([]);
    ctx.fillStyle = "red";
    ctx.fillText(groundDistance.toFixed(0)+"m",(x1GroundDistance+x2GroundDistance)/2.0,y2CenterLine+20-2);
    drawArrow(ctx,x1GroundDistance,y2CenterLine+20,x2GroundDistance,y2CenterLine+20,1,"red");
 
    // 50ft distance
    ctx.fillStyle = "green";
    var text="50ft";
    ctx.fillText(distance50ft.toFixed(0)+"m",(x1Distance50ft+x2Distance50ft)/2.0,y2CenterLine-y50ftDistance-10-2);
    ctx.fillText(text,x1Distance50ft-text.length*yFont*0.55,y2CenterLine-y50ftDistance/3.0);
    drawArrow(ctx,x1Distance50ft,y2CenterLine-y50ftDistance-12,x2Distance50ft,y2CenterLine-y50ftDistance-12,1,"green");

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
    var ias_50ft=convertUnit(Outputs["ias_50ft_ld"],"speed","MPH","MPH");
  
    // Additional info
    var yInfo=5;
    yInfo+=yFont;
    ctx.fillText("Density Altitude:"+density_altitude.toFixed(0)+"hPa",xInfo,yInfo);
    yInfo+=yFont;
    ctx.fillText("Head wind speed:"+head_wind_speed.toFixed(0)+"hPa",xInfo,yInfo);
  
    //Speed
    var text="IAS: 50ft="+ias_50ft.toFixed(0)+"MPH";
    ctx.fillText(text,xSpeedInfo,ySpeedInfo);
    xSpeedInfo+=text.length*yFont*0.6;
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
function computeIASRoll(theInputs, theOutputs, unitType, unit, displayedUnit)
{
    if(!performance_plane_takeoffJSON.hasOwnProperty("IAS_roll")) {
        return 0.0;
    }
    var iasrollFct=performance_plane_takeoffJSON.IAS_roll;
    unitType.name=getUnitTypeFromFunction(iasrollFct);
    unit.name=getUnitFromFunction(iasrollFct);
    displayedUnit.name=getDisplayedUnitFromFeature(iasrollFct);

    return computeValue(iasrollFct, theInputs, theOutputs);
}

//==============================================
// Function: computeDistanceRoll
// Purpose: Compute Roll Distance from JSON Info ("Performance/plane/takeoff/distance_roll")
//==============================================
function computeDistanceRoll(theInputs, theOutputs, unitType, unit, displayedUnit)
{
    if(!performance_plane_takeoffJSON.hasOwnProperty("distance_roll")) {
        return 0.0;
    }
    var distanceRollFct=performance_plane_takeoffJSON.distance_roll;
    unitType.name=getUnitTypeFromFunction(distanceRollFct);
    unit.name=getUnitFromFunction(distanceRollFct);
    displayedUnit.name=getDisplayedUnitFromFeature(distanceRollFct);
    return computeValue(distanceRollFct, theInputs, theOutputs);
}

//==============================================
// Function: computeIAS50ft
// Purpose: Compute Roll IAS from JSON Info ("Performance/plane/takeoff/IAS_50ft")
//==============================================
function computeIAS50ft(theInputs, theOutputs, unitType, unit, displayedUnit)
{
    if(!performance_plane_takeoffJSON.hasOwnProperty("IAS_50ft")) {
        return 0.0;
    }
    var ias50ftFct=performance_plane_takeoffJSON.IAS_50ft;
    unitType.name=getUnitTypeFromFunction(ias50ftFct);
    unit.name=getUnitFromFunction(ias50ftFct);
    displayedUnit.name=getDisplayedUnitFromFeature(ias50ftFct);
    return computeValue(ias50ftFct, theInputs, theOutputs);
}

//==============================================
// Function: computeDistance50ft
// Purpose: Compute Roll Distance from JSON Info ("Performance/plane/takeoff/distance_50ft")
//==============================================
function computeDistance50ft(theInputs, theOutputs, unitType, unit, displayedUnit)
{
    if(!performance_plane_takeoffJSON.hasOwnProperty("distance_50ft")) {
        return 0.0;
    }
    var distance50ftFct=performance_plane_takeoffJSON.distance_50ft;
    unitType.name=getUnitTypeFromFunction(distance50ftFct);
    unit.name=getUnitFromFunction(distance50ftFct);
    displayedUnit.name=getDisplayedUnitFromFeature(distance50ftFct);
   
    return computeValue(distance50ftFct, theInputs, theOutputs);
}

//==============================================
// Function: computeIASBestAngle
// Purpose: Compute Best Angle IAS from JSON Info ("Performance/plane/takeoff/IAS_best_angle")
//==============================================
function computeIASBestAngle(theInputs, theOutputs, unitType, unit, displayedUnit)
{
    if(!performance_plane_takeoffJSON.hasOwnProperty("IAS_best_angle")) {
        return 0.0;
    }
    var iasFct=performance_plane_takeoffJSON.IAS_best_angle;
    unitType.name=getUnitTypeFromFunction(iasFct);
    unit.name=getUnitFromFunction(iasFct);
    displayedUnit.name=getDisplayedUnitFromFeature(iasFct);

    return computeValue(iasFct, theInputs, theOutputs);
}

//==============================================
// Function: computeIASMaxRoC
// Purpose: Compute Max RoC IAS from JSON Info ("Performance/plane/takeoff/IAS_Max_ROC")
//==============================================
function computeIASMaxRoC(theInputs, theOutputs, unitType, unit, displayedUnit)
{
    if(!performance_plane_takeoffJSON.hasOwnProperty("IAS_Max_ROC")) {
        return 0.0;
    }
    var iasFct=performance_plane_takeoffJSON.IAS_Max_ROC
    unitType.name=getUnitTypeFromFunction(iasFct);
    unit.name=getUnitFromFunction(iasFct);
    displayedUnit.name=getDisplayedUnitFromFeature(iasFct);

    return computeValue(iasFct, theInputs, theOutputs);
}

//==============================================
// Function: computeMaxRoC
// Purpose: Compute max roc from JSON Info ("Performance/plane/takeoff/Max_ROC")
//==============================================
function computeMaxRoC(theInputs, theOutputs, unitType, unit, displayedUnit)
{
    if(!performance_plane_takeoffJSON.hasOwnProperty("Max_ROC")) {
        return 0.0;
    }
    var iasFct=performance_plane_takeoffJSON.Max_ROC
    unitType.name=getUnitTypeFromFunction(iasFct);
    unit.name=getUnitFromFunction(iasFct);
    displayedUnit.name=getDisplayedUnitFromFeature(iasFct);

    return computeValue(iasFct, theInputs, theOutputs);
}

//==============================================
// Function: computeLandingIAS50ft
// Purpose: Compute ias 50 ft from JSON Info ("Performance/plane/landing/IAS_50ft_ld")
//==============================================
function computeLandingIAS50ft(theInputs, theOutputs, unitType, unit, displayedUnit)
{
    if(!performance_plane_landingJSON.hasOwnProperty("IAS_50ft_ld")) {
        return 0.0;
    }
    var iasFct=performance_plane_landingJSON.IAS_50ft_ld
    unitType.name=getUnitTypeFromFunction(iasFct);
    unit.name=getUnitFromFunction(iasFct);
    displayedUnit.name=getDisplayedUnitFromFeature(iasFct);

    return computeValue(iasFct, theInputs, theOutputs);
}

//==============================================
// Function: computeLandingDistance50ft
// Purpose: Compute distance landing 50ft from JSON Info ("Performance/plane/landing/distance_50ft_ld")
//==============================================
function computeLandingDistance50ft(theInputs, theOutputs, unitType, unit, displayedUnit)
{
    if(!performance_plane_landingJSON.hasOwnProperty("distance_50ft_ld")) {
        return 0.0;
    }
    var iasFct=performance_plane_landingJSON.distance_50ft_ld
    unitType.name=getUnitTypeFromFunction(iasFct);
    unit.name=getUnitFromFunction(iasFct);
    displayedUnit.name=getDisplayedUnitFromFeature(iasFct);

    return computeValue(iasFct, theInputs, theOutputs);
}

//==============================================
// Function: computeLandingDistanceGround
// Purpose: Compute distance landing groundfrom JSON Info ("Performance/plane/landing/distance_ground_ld")
//==============================================
function computeLandingDistanceGround(theInputs, theOutputs, unitType, unit, displayedUnit)
{
    if(!performance_plane_landingJSON.hasOwnProperty("distance_ground_ld")) {
        return 0.0;
    }
    var iasFct=performance_plane_landingJSON.distance_ground_ld
    unitType.name=getUnitTypeFromFunction(iasFct);
    unit.name=getUnitFromFunction(iasFct);
    displayedUnit.name=getDisplayedUnitFromFeature(iasFct);

    return computeValue(iasFct, theInputs, theOutputs);
}

//==============================================
// Function: computeValue
// Purpose: Compute a value for a JSON feature
//==============================================
function computeValue(theFeature, theInputs, theOutputs)
{
    var aValueFct=theFeature.value;
    var value=0.0;
    value=computeFunction(aValueFct,theInputs, theOutputs);
    if(theFeature.hasOwnProperty("coefficiant")) {
        var coefficiants=theFeature.coefficiant;
        for (const [key, fct] of Object.entries(coefficiants)) {
            value*=computeFunction(fct, theInputs, theOutputs);
        }
    }
    if(theFeature.hasOwnProperty("additional")) {
        var additional=theFeature.additional;
        for (const [key, fct] of Object.entries(additional)) {
            value+=computeFunction(fct, theInputs, theOutputs);
        }
    }
    return value;
}
//==============================================
// Function: computeFunction
// Purpose: Compute a value from a function
//==============================================
function computeFunction(theFunction, theInputs, theOutputs)
{
    var functionType=theFunction.function_type;
    var value=0.0;
    if(functionType=="constant") {
        value=computeConstantFunction(theFunction, theInputs, theOutputs);
    }
    else if(functionType=="linear") {
        value=computeLinearFunction(theFunction, theInputs, theOutputs);
    }
    else if(functionType=="table") {
        value=computeTableFunction(theFunction, theInputs, theOutputs);
    }
    else if(functionType=="enumeration") {
        value=computeEnumerationFunction(theFunction, theInputs, theOutputs);
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
function computeConstantFunction(theFunction, theInputs, theOutputs)
{
    //{"function_type": "constant", "values": [64], "units": ["MPH"], "unittypes":["speed"]}
    return value=theFunction.values[0];
}

//==============================================
// Function: computeEnumerationFunction
// Purpose: Compute a value from a Enumeration function
//==============================================
function computeEnumerationFunction(theFunction, theInputs, theOutputs)
{
    //{"function_type": "enumeration", "columns": ["runway_type"], "enumerations":["Alphast", "Grass"], "values": [1.0, 1.07], "units": ["unitless"]}
    var anEnum="";
    var field=theFunction.columns[0];
    var value=0.0;
    var anEnums=theFunction.enumerations;
    if(theInputs.hasOwnProperty(field)) {
        anEnum=theInputs[field];
    }
    else if(theOutputs.hasOwnProperty(field)) {
        anEnum=theOutputs[field];
 
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
function computeLinearFunction(theFunction, theInputs, theOutputs)
{
    var x_name=theFunction.columns[0];
    var xValue=0;
    var xUnit="";
    var xUnitType="";
    var value=0.0;
    if(theInputs.hasOwnProperty(x_name)) {
        xValue=theInputs[x_name];
        xUnitType=theInputs[x_name+"/unittype"];
        xUnit=theInputs[x_name+"/unit"];
    }
    else if(theOutputs.hasOwnProperty(x_name)) {
        xValue=theOutputs[x_name];
        xUnitType=theOutputs[x_name+"/unittype"];
        xUnit=theOutputs[x_name+"/unit"];
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
function computeTableFunction(theFunction, theInputs, theOutputs)
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

        // 1. Retrieve the input values to be used from theInputs & theOutputs
        var columnInputValues=Array();
        for(var columnIndex=0;columnIndex<columnCount-1;columnIndex++) {
            columnInputValues[columnIndex]=0.0;
            if(theInputs.hasOwnProperty(columns[columnIndex])) {
                columnInputValues[columnIndex]=theInputs[columns[columnIndex]];
            }
            else if(theOutputs.hasOwnProperty(columns[columnIndex])) {
                columnInputValues[columnIndex]=theOutputs[columns[columnIndex]];
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
// Function: getDisplayedUnitFromFeature
// Purpose: returns the dispalayed unit associated to a takeoff or landing feature
//==============================================
function getDisplayedUnitFromFeature(theJSONFeature)
{
    if(!theJSONFeature.hasOwnProperty("unitdisplay")) {
        alert("Error:getDisplayedUnitFromFeature: no unitdisplay attribute defined in "+JSON.stringify(theJSONFeature));
        return "";
    }
    return theJSONFeature.unitdisplay;
}

//==============================================
// Function: getUnitTypeFromFunction
// Purpose: returns the unit type (Length, speed) unit associated to a takeoff or landing feature
//==============================================
function getUnitTypeFromFunction(theJSONFeature)
{   
    if(!theJSONFeature.hasOwnProperty("value")) {
        alert("Error:getUnitTypeFromFunction: no value attribute defined in "+JSON.stringify(theJSONFeature));
        return "";
    }
    var valueFeature=theJSONFeature.value;

    if(!valueFeature.hasOwnProperty("unittypes")) {
        alert("Error:getUnitTypeFromFunction: no unittypes attribute defined in value of "+JSON.stringify(theJSONFeature));
        return "";
    }
    var unitTypes=valueFeature.unittypes;
    return unitTypes[unitTypes.length-1];
}
//==============================================
// Function: getUnitFromFunction
// Purpose: returns the unit associated to a takeoff or landing feature
//==============================================
function getUnitFromFunction(theJSONFeature)
{
   if(!theJSONFeature.hasOwnProperty("value")) {
        alert("Error:getUnitFromFunction: no value attribute defined in "+JSON.stringify(theJSONFeature));
        return "";
    }
    var valueFeature=theJSONFeature.value;

    if(!valueFeature.hasOwnProperty("units")) {
        alert("Error:getUnitFromFunction: no unit attribute defined in value of "+JSON.stringify(theJSONFeature));
        return "";
    }
    var units=valueFeature.units;
    return units[units.length-1];
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
                        tooltipJSON=performance_plane_takeoffJSON[tooltip];   
                    } 
                    else {
                        tooltipJSON=performance_plane_landingJSON[tooltip];                          
                    }
                    tooltip=JSON.stringify(tooltipJSON);
                    tooltip=tooltip.replace("},", "},<br>");
                    tooltip=tooltip.replace(":{", ":<br>{");
                }
                if(outputTarget=="all" || outputTarget=="takeoff" ) {
                    document.getElementById("id_takeoff_o_"+key+"/tooltip").innerHTML=tooltip;
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
            var outputTarget=Outputs[key+"/output"];
            if(outputTarget=="all" || outputTarget=="takeoff" ) {
                document.getElementById("id_takeoff_o_"+key).innerHTML=convertUnit(value,
                    Outputs[key+"/unittype"],
                    Outputs[key+"/unit"],
                    Outputs[key+"/displayedunit"]).toFixed(0);  
                document.getElementById("id_takeoff_o_"+key+"/unit").innerHTML=Outputs[key+"/displayedunit"]; 
                document.getElementById("id_takeoff_o_"+key).readOnly=true;
                document.getElementById("id_takeoff_o_"+key).style.backgroundColor = ReadOnlyColor;
            }
            if(outputTarget=="all" || outputTarget=="landing" ) {
                document.getElementById("id_landing_o_"+key).innerHTML=convertUnit(value,
                    Outputs[key+"/unittype"],
                    Outputs[key+"/unit"],
                    Outputs[key+"/displayedunit"]).toFixed(0);  
                document.getElementById("id_landing_o_"+key+"/unit").innerHTML=Outputs[key+"/displayedunit"]; 
                document.getElementById("id_landing_o_"+key).readOnly=true;
                document.getElementById("id_landing_o_"+key).style.backgroundColor = ReadOnlyColor;
            }
       }
    }
}

//==============================================
// Function: updateDisplayInputs
// Purpose: update the display of inputs
//==============================================
function updateDisplayInputs(takeoffInputsDefault,landingInputsDefault) {
    // loop on all outputs
    for (var key in Inputs) {
        if(key.search("/")==-1) {
            var value=Inputs[key];
            var unitType=Inputs[key+"/unittype"];
            if(unitType=="string") {
                document.getElementById("id_takeoff_i_"+key).value=value;
                document.getElementById("id_takeoff_i_"+key+"/unit").innerHTML="";
                document.getElementById("id_landing_i_"+key).value=value;
                document.getElementById("id_landing_i_"+key+"/unit").innerHTML="";
            }
            else {
                document.getElementById("id_takeoff_i_"+key).value=convertUnit(value,
                    Inputs[key+"/unittype"],
                    Inputs[key+"/unit"],
                    Inputs[key+"/displayedunit"]).toFixed(0);  
                document.getElementById("id_takeoff_i_"+key+"/unit").innerHTML=Inputs[key+"/displayedunit"];           
                document.getElementById("id_landing_i_"+key).value=convertUnit(value,
                    Inputs[key+"/unittype"],
                    Inputs[key+"/unit"],
                    Inputs[key+"/displayedunit"]).toFixed(0);  
                document.getElementById("id_landing_i_"+key+"/unit").innerHTML=Inputs[key+"/displayedunit"];           
            }
         }
    }
    // Default values in takeoffInputsDefault
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
                Inputs[key+"/displayedunit"]).toFixed(0);  
            document.getElementById("id_takeoff_i_"+key+"/unit").innerHTML=Inputs[key+"/displayedunit"];                
        }
        if(readonly==1) {
            document.getElementById("id_takeoff_i_"+key).readOnly=true;
            document.getElementById("id_takeoff_i_"+key).style.backgroundColor = ReadOnlyColor;
        }
    }
    // Default values in landingInputsDefault
    for (var key in landingInputsDefault) {
        var value=landingInputsDefault[key].value;
        var unit=landingInputsDefault[key].unit;
        var unittype=landingInputsDefault[key].unittype;
        var readonly=landingInputsDefault[key].readonly;
        if(unitType=="string") {
            document.getElementById("id_landing_i_"+key).value=value;
            document.getElementById("id_landing_i_"+key+"/unit").innerHTML="";
        }
        else {
            document.getElementById("id_landing_i_"+key).value=convertUnit(value,
                unittype,
                unit,
                Inputs[key+"/displayedunit"]).toFixed(0);  
            document.getElementById("id_landing_i_"+key+"/unit").innerHTML=Inputs[key+"/displayedunit"];                
        }
        if(readonly==1) {
            document.getElementById("id_landing_i_"+key).readOnly=true;
            document.getElementById("id_landing_i_"+key).style.backgroundColor = ReadOnlyColor;
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
Inputs["qnh"]=1013;
Inputs["qnh/unit"]="hPa";
Inputs["qnh/displayedunit"]="hPa";
Inputs["qnh/unittype"]="pressure";
Inputs["altitude"]=1542;
Inputs["altitude/unit"]="ft";
Inputs["altitude/displayedunit"]="ft";
Inputs["altitude/unittype"]="length";
Inputs["temperature"]=12;
Inputs["temperature/unit"]="C";
Inputs["temperature/displayedunit"]="C";
Inputs["temperature/unittype"]="temperature";
Inputs["runway_number"]=23;
Inputs["runway_number/unittype"]="unitless";
Inputs["runway_number/unit"]="";
Inputs["runway_number/displayedunit"]="";
Inputs["runway_type"]="Asphalt";
Inputs["runway_type/unittype"]="string";
Inputs["runway_slope"]=0;
Inputs["runway_slope/unittype"]="unitless";
Inputs["runway_slope/unit"]="%";
Inputs["runway_slope/displayedunit"]="%";
Inputs["pilot_skill"]="Advanced";
Inputs["pilot_skill/unittype"]="string";
Inputs["aircraft_coefficiant"]="POH";
Inputs["aircraft_coefficiant/unittype"]="string";
Inputs["wind_direction"]=230;
Inputs["wind_direction/unit"]="degree";
Inputs["wind_direction/displayedunit"]="degree";
Inputs["wind_direction/unittype"]="planeangle";
Inputs["wind_speed"]=0.0;
Inputs["wind_speed/unit"]="kt";
Inputs["wind_speed/displayedunit"]="kt";
Inputs["wind_speed/unittype"]="speed";
Inputs["weight"]=0.0;
Inputs["weight/unit"]="lb";
Inputs["weight/displayedunit"]="kg";
Inputs["weight/unittype"]="mass";
Inputs["flaps"]=0;
Inputs["flaps/unit"]="degree";
Inputs["flaps/displayedunit"]="degree";
Inputs["flaps/unittype"]="planeangle";

//Init outputs 
var Outputs=Array();
// General outputs
Outputs["temperature_isa"]=0;
Outputs["temperature_isa/unit"]="C";
Outputs["temperature_isa/displayedunit"]="C";
Outputs["temperature_isa/unittype"]="temperature";
Outputs["temperature_isa/output"]="all";
Outputs["temperature_isa/tooltip"]="Temperature ISA(C)=15.(C) - 2.(C)* Altitude(ft)/1000.(ft)";
Outputs["temperature_delta_isa"]=0;
Outputs["temperature_delta_isa/unit"]="C";
Outputs["temperature_delta_isa/displayedunit"]="C";
Outputs["temperature_delta_isa/unittype"]="temperature_delta";
Outputs["temperature_delta_isa/output"]="all";
Outputs["temperature_delta_isa/tooltip"]="Delta Temperature=Temperature - Temperature ISA";
Outputs["pressure_altitude"]=0;
Outputs["pressure_altitude/unit"]="hPa";
Outputs["pressure_altitude/displayedunit"]="hPa";
Outputs["pressure_altitude/unittype"]="pressure";
Outputs["pressure_altitude/output"]="all";
Outputs["pressure_altitude/tooltip"]="Altitude Pression(ft)=Altitude Terrain(ft) + (1013-QNH)*30ft/hPa";
Outputs["density_altitude"]=0;
Outputs["density_altitude/unit"]="hPa";
Outputs["density_altitude/displayedunit"]="hPa";
Outputs["density_altitude/unittype"]="pressure";
Outputs["density_altitude/output"]="all";
Outputs["density_altitude/tooltip"]="Altitude Densité(ft) : Altitude pression(ft) + 118.8(ft/C) * (T(C) - T ISA(C))";
Outputs["head_wind_speed"]=0;
Outputs["head_wind_speed/unit"]="kt";
Outputs["head_wind_speed/displayedunit"]="kt";
Outputs["head_wind_speed/unittype"]="speed";
Outputs["head_wind_speed/output"]="all";
Outputs["head_wind_speed/tooltip"]="Head Wind=Wind Speeed*cos(Piste Number- Wind Direction)";
Outputs["cross_wind_speed"]=0;
Outputs["cross_wind_speed/unit"]="kt";
Outputs["cross_wind_speed/displayedunit"]="kt";
Outputs["cross_wind_speed/unittype"]="speed";
Outputs["cross_wind_speed/output"]="all";
Outputs["cross_wind_speed/tooltip"]="Head Wind=Wind Speeed*sin(Piste Number- Wind Direction)";
// Take-off outputs
Outputs["distance_roll"]=0;
Outputs["distance_roll/unit"]="m";
Outputs["distance_roll/displayedunit"]="m";
Outputs["distance_roll/unittype"]="length";
Outputs["distance_roll/output"]="takeoff";
Outputs["distance_roll/tooltip"]="JSON/distance_roll";
Outputs["distance_50ft"]=0;
Outputs["distance_50ft/unit"]="m";
Outputs["distance_50ft/displayedunit"]="m";
Outputs["distance_50ft/unittype"]="length";
Outputs["distance_50ft/output"]="takeoff";
Outputs["distance_50ft/tooltip"]="JSON/distance_50ft";
Outputs["ias_roll"]=0;
Outputs["ias_roll/unit"]="MPH";
Outputs["ias_roll/displayedunit"]="MPH";
Outputs["ias_roll/unittype"]="speed";
Outputs["ias_roll/output"]="takeoff";
Outputs["ias_roll/tooltip"]="JSON/IAS_roll";
Outputs["ias_50ft"]=0;
Outputs["ias_50ft/unit"]="MPH";
Outputs["ias_50ft/displayedunit"]="MPH";
Outputs["ias_50ft/unittype"]="speed";
Outputs["ias_50ft/output"]="takeoff";
Outputs["ias_50ft/tooltip"]="JSON/IAS_50ft";
Outputs["ias_best_angle"]=0;
Outputs["ias_best_angle/unit"]="MPH";
Outputs["ias_best_angle/displayedunit"]="MPH";
Outputs["ias_best_angle/unittype"]="speed";
Outputs["ias_best_angle/output"]="takeoff";
Outputs["ias_best_angle/tooltip"]="JSON/IAS_best_angle";
Outputs["max_roc"]=0;
Outputs["max_roc/unit"]="ft/min";
Outputs["max_roc/displayedunit"]="ft/min";
Outputs["max_roc/unittype"]="speed";
Outputs["max_roc/output"]="takeoff";
Outputs["max_roc/tooltip"]="JSON/Max_ROC";
Outputs["ias_max_roc"]=0;
Outputs["ias_max_roc/unit"]="MPH";
Outputs["ias_max_roc/displayedunit"]="MPH";
Outputs["ias_max_roc/unittype"]="speed";
Outputs["ias_max_roc/output"]="takeoff";
Outputs["ias_max_roc/tooltip"]="JSON/IAS_Max_ROC";
// Landing outputs
Outputs["ias_50ft_ld"]=0;
Outputs["ias_50ft_ld/unit"]="MPH";
Outputs["ias_50ft_ld/displayedunit"]="MPH";
Outputs["ias_50ft_ld/unittype"]="speed";
Outputs["ias_50ft_ld/output"]="landing";
Outputs["ias_50ft_ld/tooltip"]="JSON/IAS_50ft_ld";
Outputs["distance_50ft_ld"]=0;
Outputs["distance_50ft_ld/unit"]="m";
Outputs["distance_50ft_ld/displayedunit"]="m";
Outputs["distance_50ft_ld/unittype"]="length";
Outputs["distance_50ft_ld/output"]="landing";
Outputs["distance_50ft_ld/tooltip"]="JSON/distance_50ft_ld";
Outputs["distance_ground_ld"]=0;
Outputs["distance_ground_ld/unit"]="m";
Outputs["distance_ground_ld/displayedunit"]="m";
Outputs["distance_ground_ld/unittype"]="length";
Outputs["distance_ground_ld/output"]="landing";
Outputs["distance_ground_ld/tooltip"]="JSON/distance_50ft_ld";

// Decode notedefrais json file
//var performanceJSON=JSON.parse(performanceJSONcontent);
var performanceJSON="";
try {
    performanceJSON = JSON.parse(performanceJSONcontent);
} catch (error) {
    alert("Error when parsing the performance JSON file:\n"+error);
}
if(performanceJSON!="") {
    var planes=Array();
    for (const key in performanceJSON.performance) {
        planes.push(key);
    }
    prefillDropdownMenus("id_plane_select", planes, planes[0]);
    var pilotSkills=Array("Student","Normal","Advanced");
    prefillDropdownMenus("id_takeoff_i_pilot_skill", pilotSkills, Inputs["pilot_skill"]);
    prefillDropdownMenus("id_landing_i_pilot_skill", pilotSkills, Inputs["pilot_skill"]);
    var runwayType=Array("Asphalt","Grass");
    prefillDropdownMenus("id_takeoff_i_runway_type", runwayType, Inputs["runway_type"]);
    prefillDropdownMenus("id_landing_i_runway_type", runwayType, Inputs["runway_type"]);
    var aircraftCoefficiant=Array("POH","Measured");
    prefillDropdownMenus("id_takeoff_i_aircraft_coefficiant", aircraftCoefficiant, Inputs["aircraft_coefficiant"]);
    prefillDropdownMenus("id_landing_i_aircraft_coefficiant", aircraftCoefficiant, Inputs["aircraft_coefficiant"]);

    // Init plane
    document.getElementById("id_plane_select").value=default_plane;
    var performance_plane_takeoffJSON="";
    var performance_plane_landingJSON="";
    var takeoffInputsDefault="";
    var landingInputsDefault="";
    if(performanceJSON.performance.hasOwnProperty(default_plane)) {
        performance_plane_takeoffJSON=performanceJSON.performance[default_plane].takeoff.outputs;
        takeoffInputsDefault=performanceJSON.performance[default_plane].takeoff.inputs;
        performance_plane_landingJSON=performanceJSON.performance[default_plane].landing.outputs;
        landingInputsDefault=performanceJSON.performance[default_plane].landing.inputs;
    }
             
    updateDisplayInputs(takeoffInputsDefault,landingInputsDefault);
    updateDisplayOuputs();

    //jQuery("#bookingMessageModal").modal('show') ;

    window.onload=mobile_performance_page_loaded();

}
