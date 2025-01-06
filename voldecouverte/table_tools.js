// Tools used by tables script

function round_tarif(theTarif) {
	var aTarif=Math.round(theTarif/5)*5;
	return aTarif;
}

function retrieve_tarif(theTypeOfGift, theNumberOfPassenger)  {
    for (var i = 0;  i < myTarifs.length; i++) {
      var aType=  myTarifs[i].type ; 
      var aNumberOfPassenger =  myTarifs[i].passenger ;
      if(aType==theTypeOfGift && theNumberOfPassenger == aNumberOfPassenger) {
          return myTarifs[i].tarif ; 
      }
    }
  return 0;
}
function compute_tarif(theTypeOfGift, theCircuit, theTime, theNumberOfPassenger)  {
	var aTarif=retrieve_tarif(theTypeOfGift, theNumberOfPassenger);
    aTarif=aTarif*theTime;
    aTarif=Math.round(aTarif/5)*5;
    return aTarif; 
}
		
