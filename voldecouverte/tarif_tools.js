// Tools used to retrieve tarifs script

//============================================
//Function: getJSONFile
//Purpose: Load a text file and returns the string
//============================================
async function getFile(theFile) {
  let anObject = await fetch(theFile);
  let aText = await anObject.text();
  return aText;
}
//============================================
//Function: getFileAsJSON
//Purpose: Load the JSON file and returns the json string
//============================================
 async function getFileAsJSON(theJSONFile) {
  var aText = await getFile(theJSONFile);
  var aJSON= await JSON.parse(aText);
  return aJSON;
}

//============================================
//Function: getJSONFile
//Purpose: Load the JSON file and returns the json string
//============================================
function round_tarif(theTarif) {
	var aTarif=Math.round(theTarif/5)*5;
	return aTarif;
}
//============================================
//Function: retrieve_tarif
//Purpose: 
//============================================
function retrieve_tarif(theJSONTarif)  {

  if(myTarifs.hasOwnProperty(theJSONTarif)) {
    var aTarif=myTarifs[theJSONTarif];
    return aTarif.tarif;
  }
  return 0.;
}
//============================================
//Function: compute_tarif
//Purpose: 
//============================================
function compute_tarif(theJSONTarif, theTime)  {
	var aTarif=retrieve_tarif(theJSONTarif);
    aTarif=aTarif*theTime;
    aTarif=Math.round(aTarif/5)*5;
    return aTarif; 
}
		
