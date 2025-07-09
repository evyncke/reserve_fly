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
    document.getElementById("id_takeoff_i_weight").onchange = function() {
        weightChanged();
    };
    document.getElementById("id_takeoff_i_flaps").onchange = function() {
        flapsChanged();
    };
 
    //document.getElementById("id_notedefrais_rowinput").style.display="none";
    //document.getElementById("id_submit_notedefrais").disabled=true;
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
    document.getElementById("id_takeoff_o_temperature_isa").value=temperatureISA.toFixed(0);
    document.getElementById("id_takeoff_o_temperature_delta_isa").value=temperatureDeltaISA.toFixed(0);
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
    document.getElementById("id_takeoff_o_pressure_altitude").value=altitudePressure.toFixed(0);
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
 
    document.getElementById("id_takeoff_o_density_altitude").value=altitudeDensity.toFixed(0);
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
    document.getElementById("id_takeoff_o_head_wind_speed").value=windHeadSpeed.toFixed(0);
    document.getElementById("id_takeoff_o_cross_wind_speed").value=windCrossSpeed.toFixed(0);
}
//==============================================
// Function: updateIASRoll
// Purpose:  use foncion IASRoll
//==============================================
function updateIASRoll()
{
    var rollSpeed=computeIASRoll(takeoffInputs);
    takeoffOutputs["ias_roll"]=rollSpeed;
    document.getElementById("id_takeoff_o_ias_roll").value=rollSpeed.toFixed(0);   
}
//==============================================
// Function: updateDistanceRoll
// Purpose:  use foncion distance_roll
//==============================================
function updateDistanceRoll()
{
    var rollDistance=computeDistanceRoll(takeoffInputs, takeoffOutputs);
    takeoffOutputs["distance_roll"]=rollDistance;
    document.getElementById("id_takeoff_o_distance_roll").value=convertUnit(rollDistance,"length","ft","m").toFixed(0);   
}
//==============================================
// Function: updateIAS50ft
// Purpose:  use foncion IAS50ft
//==============================================
function updateIAS50ft()
{
    var ias50ftSpeed=computeIAS50ft(takeoffInputs);
    takeoffOutputs["ias_50ft"]=ias50ftSpeed;
    document.getElementById("id_takeoff_o_ias_50ft").value=ias50ftSpeed.toFixed(0);   
}
//==============================================
// Function: updateDistance50ft
// Purpose:  use foncion distance_50ft
//==============================================
function updateDistance50ft()
{
    var distance50ft=computeDistance50ft(takeoffInputs, takeoffOutputs);
    takeoffOutputs["distance_roll"]=distance50ft;
    document.getElementById("id_takeoff_o_distance_50ft").value=convertUnit(distance50ft,"length","ft","m").toFixed(0);   
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
    document.getElementById("id_takeoff_o_ias_best_angle").value=speed.toFixed(0);   
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
    else if(unitType=="temperature_delta") {
        if(unitInput=="C" && unitOutput=="F") {
            return value*9.0/5.0;
        }
        if(unitInput=="F" && unitOutput=="C") {
            return value*5./9.;
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
    var iasrollFct=performance_plane_takeoffJSON.IAS_roll;
    return computeValue(iasrollFct, theTakeoffInputs, theTakeoffOutputs);
}

//==============================================
// Function: computeDistanceRoll
// Purpose: Compute Roll Distance from JSON Info ("Performance/plane/takeoff/distance_roll")
//==============================================
function computeDistanceRoll(theTakeoffInputs, theTakeoffOutputs)
{
    var distanceRollFct=performance_plane_takeoffJSON.distance_roll;
    return computeValue(distanceRollFct, theTakeoffInputs, theTakeoffOutputs);
}

//==============================================
// Function: computeIAS50ft
// Purpose: Compute Roll IAS from JSON Info ("Performance/plane/takeoff/IAS_50ft")
//==============================================
function computeIAS50ft(theTakeoffInputs, theTakeoffOutputs)
{
    var ias50ftFct=performance_plane_takeoffJSON.IAS_50ft;
    return computeValue(ias50ftFct, theTakeoffInputs, theTakeoffOutputs);
}

//==============================================
// Function: computeDistance50ft
// Purpose: Compute Roll Distance from JSON Info ("Performance/plane/takeoff/distance_50ft")
//==============================================
function computeDistance50ft(theTakeoffInputs, theTakeoffOutputs)
{
    var distance50ftFct=performance_plane_takeoffJSON.distance_50ft;
    return computeValue(distance50ftFct, theTakeoffInputs, theTakeoffOutputs);
}
//==============================================
// Function: computeIASBestAngle
// Purpose: Compute Best Angle IAS from JSON Info ("Performance/plane/takeoff/IAS_best_angle")
//==============================================
function computeIASBestAngle(theTakeoffInputs, theTakeoffOutputs)
{
    var iasFct=performance_plane_takeoffJSON.IAS_best_angle;
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
    if(theFeature.hasOwnProperty("runwaytype__coefficiant")) {
        var aRunwayTypeFct=theFeature.runwaytype__coefficiant;
        value*=computeFunction(aRunwayTypeFct, theTakeoffInputs, theTakeoffOutputs);
    }
    if(theFeature.hasOwnProperty("pilotskill__coefficiant")) {
        var aPilotSkillFct=theFeature.pilotskill__coefficiant;
        value*=computeFunction(aPilotSkillFct, theTakeoffInputs, theTakeoffOutputs);
    }
    if(theFeature.hasOwnProperty("temperature_coefficiant")) {
        var aTemperatureFct=theFeature.temperature_coefficiant;
        value*=computeFunction(aTemperatureFct, theTakeoffInputs, theTakeoffOutputs);
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
    /*
    {"function_type": "linear", "xvalue": [0, 35], 
                           "values": [0, 1.1],  
                           "columns": ["temperature_delta_isa","value"], 
                           "units":[ "delta_F", "none"],
                           "unit_types":[ "delta_temperature", "unitless"]}
    */
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
// Function: updateDisplayTakeoffOuputs
// Purpose: update the display of take-off outputs
//==============================================

function updateDisplayTakeoffOuputs() {
    // loop on all outputs
    for (var key in takeoffOutputs) {
        if(key.search("/")==-1) {
            var value=takeoffOutputs[key];
            document.getElementById("id_takeoff_o_"+key).value=value.toFixed(0);
            document.getElementById("id_takeoff_o_"+key).readOnly=true;
            document.getElementById("id_takeoff_o_"+key).style.backgroundColor = ReadOnlyColor;

        }
    }
}

//==============================================
// Function: updateDisplayTakeoffInputs
// Purpose: update the display of take-off outputs
//==============================================

function updateDisplayTakeoffInputs() {
    // loop on all outputs
    for (var key in takeoffInputs) {
        if(key.search("/")==-1) {
            var value=takeoffInputs[key];
            var unitType=takeoffInputs[key+"/unittype"];
            if(unitType=="string") {
                document.getElementById("id_takeoff_i_"+key).value=value;
            }
            else {
                document.getElementById("id_takeoff_i_"+key).value=value.toFixed(0);               
            }
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
takeoffInputs["altitude"]=3750;
takeoffInputs["altitude/unit"]="ft";
takeoffInputs["altitude/displayedunit"]="ft";
takeoffInputs["altitude/unittype"]="length";
takeoffInputs["temperature"]=12;
takeoffInputs["temperature/unit"]="celsius";
takeoffInputs["temperature/displayedunit"]="celsius";
takeoffInputs["temperature/unittype"]="temperature";
takeoffInputs["runway_number"]=23;
takeoffInputs["runway_number/unittype"]="none";
takeoffInputs["runway_type"]="Asphalt";
takeoffInputs["runway_type/unittype"]="string";
takeoffInputs["pilot_skill"]="Advanced";
takeoffInputs["pilot_skill/unittype"]="string";
takeoffInputs["wind_direction"]=230;
takeoffInputs["wind_direction/unit"]="degree";
takeoffInputs["wind_direction/displayedunit"]="degree";
takeoffInputs["wind_direction/unittype"]="planeangle";
takeoffInputs["wind_speed"]=7.5;
takeoffInputs["wind_speed/unit"]="kt";
takeoffInputs["wind_speed/displayedunit"]="kt";
takeoffInputs["wind_speed/unittype"]="speed";
takeoffInputs["weight"]=1600;
takeoffInputs["weight/unit"]="lb";
takeoffInputs["weight/displayedunit"]="kg";
takeoffInputs["weight/unittype"]="mass";
takeoffInputs["flaps"]=0;
takeoffInputs["flaps/unit"]="degree";
takeoffInputs["flaps/displayedunit"]="degree";
takeoffInputs["flaps/unittype"]="planeangle";

updateDisplayTakeoffInputs();
//Init outputs
var takeoffOutputs=Array();
takeoffOutputs["temperature_isa"]=0;
takeoffOutputs["temperature_isa/unit"]="C";
takeoffOutputs["temperature_isa/displayedunit"]="C";
takeoffOutputs["temperature_isa/unittype"]="temperature";
takeoffOutputs["temperature_delta_isa"]=0;
takeoffOutputs["temperature_delta_isa/unit"]="C";
takeoffOutputs["temperature_delta_isa/displayedunit"]="C";
takeoffOutputs["temperature_delta_isa/unittype"]="temperature_delta";
takeoffOutputs["pressure_altitude"]=0;
takeoffOutputs["pressure_altitude/unit"]="hPa";
takeoffOutputs["pressure_altitude/displayedunit"]="hPa";
takeoffOutputs["pressure_altitude/unittype"]="pressure";
takeoffOutputs["density_altitude"]=0;
takeoffOutputs["density_altitude/unit"]="hPa";
takeoffOutputs["density_altitude/displayedunit"]="hPa";
takeoffOutputs["density_altitude/unittype"]="pressure";
takeoffOutputs["head_wind_speed"]=0;
takeoffOutputs["head_wind_speed/unit"]="kt";
takeoffOutputs["temperature_isa/displayedunit"]="kt";
takeoffOutputs["head_wind_speed/unittype"]="speed";
takeoffOutputs["cross_wind_speed"]=0;
takeoffOutputs["cross_wind_speed/unit"]="kt";
takeoffOutputs["cross_wind_speed/displayedunit"]="kt";
takeoffOutputs["cross_wind_speed/unittype"]="speed";
takeoffOutputs["distance_roll"]=0;
takeoffOutputs["distance_roll/unit"]="m";
takeoffOutputs["distance_roll/displayedunit"]="m";
takeoffOutputs["distance_roll/unittype"]="length";
takeoffOutputs["distance_50ft"]=0;
takeoffOutputs["distance_50ft/unit"]="m";
takeoffOutputs["distance_50ft/displayedunit"]="m";
takeoffOutputs["distance_50ft/unittype"]="length";
takeoffOutputs["ias_roll"]=0;
takeoffOutputs["ias_roll/unit"]="MPH";
takeoffOutputs["ias_roll/displayedunit"]="MPH";
takeoffOutputs["ias_roll/unittype"]="speed";
takeoffOutputs["ias_50ft"]=0;
takeoffOutputs["ias_50ft/unit"]="MPH";
takeoffOutputs["ias_50ft/displayedunit"]="MPH";
takeoffOutputs["ias_50ft/unittype"]="speed";
takeoffOutputs["ias_best_angle"]=0;
takeoffOutputs["ias_best_angle/unit"]="MPH";
takeoffOutputs["ias_best_angle/displayedunit"]="MPH";
takeoffOutputs["ias_best_angle/unittype"]="speed";
takeoffOutputs["max_roc"]=0;
takeoffOutputs["max_roc/unit"]="ft/min";
takeoffOutputs["max_roc/displayedunit"]="ft/min";
takeoffOutputs["max_roc/unittype"]="speed";

updateDisplayTakeoffOuputs();

// Decode notedefrais json file
var performanceJSON=JSON.parse(performanceJSONcontent);
var planes=Array("OO-ALD","OO-JRB");
prefillDropdownMenus("id_plane_select", planes, planes[0]);
var pilotSkills=Array("Student","Normal","Advanced");
prefillDropdownMenus("id_takeoff_i_pilot_skill", pilotSkills, takeoffInputs["pilot_skill"]);
var runwayType=Array("Alphast","Grass");
prefillDropdownMenus("id_takeoff_i_runway_type", runwayType, takeoffInputs["runway_type"]);
var performance_plane_takeoffJSON=performanceJSON.performance["OO-ALD"].takeoff;
window.onload=mobile_performance_page_loaded();
