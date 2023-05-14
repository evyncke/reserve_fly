// JavaScript used by IntroCarnetVol.php to manage the page
//
function carnetdevol_page_loaded() {

  //document.getElementById("id_valeur_virement").style.display="none";
  //document.getElementById("id_cdv_bookingid_row").style.display="none";
  document.getElementById("id_cdv_nombre_crew_row").style.display="none"; 
   
  document.getElementById("id_cdv_bookingid").readOnly=true;;
  document.getElementById("id_cdv_bookingid").style.backgroundColor = ReadOnlyColor;

  document.getElementById("id_cdv_logbookid").readOnly=true;
  document.getElementById("id_cdv_logbookid").style.backgroundColor = ReadOnlyColor;
  if(default_logbookid == 0) {
	  document.getElementById("id_cdv_logbookid_row").style.display="none";
  }
  else {
	  document.getElementById("id_cdv_logbookid_row").style.display="";  	
  }

  document.getElementById("id_cdv_flightreferenceid").readOnly=true;
  document.getElementById("id_cdv_flightreferenceid").style.backgroundColor = ReadOnlyColor;
  //document.getElementById("id_cdv_flightreferenceid_row").style.display="none";
  
  if(default_logbookid!=0) {
	  document.getElementById("id_submitButton").disabled=false;
  }

  //document.getElementById("id_cdv_segment_count_row").style.display="none";
  document.getElementById("id_cdv_segment_count").readOnly=true;
  document.getElementById("id_cdv_segment_count").style.backgroundColor = ReadOnlyColor;
  document.getElementById("id_cdv_segment_count").value=default_segment;
  
  document.getElementById("id_cdv_aircraft").onchange = function() {
	  compute_aircraft("id_cdv_aircraft");
	  compute_prix();
  };

  //document.getElementById("id_cdv_aircraft_model_row").style.display="none";
  
  document.getElementById("id_cdv_flight_date").onchange = function() {
	  check_date();
  };
  document.getElementById("id_cdv_flight_date").value=getTodayAsString();
  
  document.getElementById("id_cdv_pilot_function").onchange = function() {
	  compute_hideshow_instructor();
	  compute_prix();
  };
  document.getElementById("id_cdv_nombre_crew").onchange = function() {
	  check_passager_crew();
  };
  document.getElementById("id_cdv_nombre_passager").onchange = function() {
	  check_passager_crew();
	  compute_prix();
  };
  document.getElementById("id_cdv_frais_CP").onchange = function() {
	  compute_frais_CP();
	  compute_prix();
  };
  document.getElementById("id_cdv_frais_CP_type").onchange = function() {
	  compute_frais_CP();
  };
  document.getElementById("id_cdv_frais_CP_type").style.display="none";

  document.getElementById("id_cdv_frais_remarque").onchange = function() {
	  check_remark();
  };
  //no more used
  //document.getElementById("id_cdv_frais_CP_PAX").onchange = function() {
	//  compute_frais_CP();
  //};
  
  document.getElementById("id_cdv_frais_CP_PAX").style.display="none";

  document.getElementById("id_cdv_frais_numero_vol").onchange = function() {
	  check_numero_vol();
  };
  document.getElementById("id_cdv_frais_numero_vol").style.display="none";
  
  document.getElementById("id_cdv_frais_DC").onchange = function() {
	  compute_prix();
  };
  document.getElementById("id_cdv_departure_airport").onchange = function() {
	  check_airport("id_cdv_departure_airport");
	  compute_prix();
  	  if(document.getElementById("id_cdv_departure_airport").value == 
		  document.getElementById("id_cdv_arrival_airport").value) {
		document.getElementById("id_cdv_nature_vol").value="Local";
	  }
	  else {
		document.getElementById("id_cdv_nature_vol").value="Nav";		  	
	  }
  };
  
  document.getElementById("id_cdv_arrival_airport").onchange = function() {
	  check_airport("id_cdv_arrival_airport");
	  compute_prix();
  	  if(document.getElementById("id_cdv_departure_airport").value == 
		  document.getElementById("id_cdv_arrival_airport").value) {
		document.getElementById("id_cdv_nature_vol").value="Local";
	  }
	  else {
		document.getElementById("id_cdv_nature_vol").value="Nav";		  	
	 }
  };
 
  document.getElementById("id_cdv_heure_depart").onchange = function() {
	  check_heure("id_cdv_heure_depart");
	  check_coherence_heure();
  };
  document.getElementById("id_cdv_heure_depart").inputMode="decimal";
 
  document.getElementById("id_cdv_heure_arrivee").onchange = function() {
	  check_heure("id_cdv_heure_arrivee");
	  check_coherence_heure();
  };
  var anInput=document.getElementById("id_cdv_heure_arrivee");
  document.getElementById("id_cdv_heure_arrivee").inputMode="decimal";

  document.getElementById("id_cdv_duree").onchange = function() {
	  check_duree("id_cdv_duree");
	  check_coherence_heure();
  };
  document.getElementById("id_cdv_duree").inputMode="decimal";
  document.getElementById("id_cdv_duree").readOnly=true;
  document.getElementById("id_cdv_duree").style.backgroundColor = ReadOnlyColor;
  
  document.getElementById("id_cdv_compteur_vol_depart").onchange = function() {
	  check_compteur("id_cdv_compteur_vol_depart");
	  check_coherence_compteur_vol();
	  compute_prix();
  };
  document.getElementById("id_cdv_compteur_vol_depart").inputMode="decimal";

  document.getElementById("id_cdv_compteur_vol_arrivee").onchange = function() {
	  check_compteur("id_cdv_compteur_vol_arrivee");
	  check_coherence_compteur_vol();
	  compute_prix();
  };
  document.getElementById("id_cdv_compteur_vol_arrivee").inputMode="decimal";

  document.getElementById("id_cdv_compteur_vol_duree").onchange = function() {
	  check_duree("id_cdv_compteur_vol_duree");
	  check_coherence_compteur_vol();
	  compute_prix();
  };
  document.getElementById("id_cdv_compteur_vol_duree").inputMode="decimal";
  document.getElementById("id_cdv_compteur_vol_duree").readOnly=true;
  document.getElementById("id_cdv_compteur_vol_duree").style.backgroundColor = ReadOnlyColor;

  document.getElementById("id_cdv_compteur_depart").onchange = function() {
	  check_compteur("id_cdv_compteur_depart");
	  check_coherence_compteur();
	  compute_prix();
  };
  document.getElementById("id_cdv_compteur_depart").inputMode="decimal";
  
  document.getElementById("id_cdv_compteur_arrivee").onchange = function() {
	  check_compteur("id_cdv_compteur_arrivee");
	  check_coherence_compteur();
	  compute_prix();
  };
  document.getElementById("id_cdv_compteur_arrivee").inputMode="decimal";

  document.getElementById("id_cdv_compteur_duree").onchange = function() {
	  check_duree("id_cdv_compteur_duree");
	  check_coherence_compteur();
	  compute_prix();
  };
  document.getElementById("id_cdv_compteur_duree").inputMode="decimal"; 
  document.getElementById("id_cdv_compteur_duree").readOnly=true;
  document.getElementById("id_cdv_compteur_duree").style.backgroundColor = ReadOnlyColor;

  document.getElementById("id_cdv_prix_solde_row").style.display="none";
  document.getElementById("id_cdv_prix_reference_row").style.display="none";

  compute_defaultValues();
  compute_aircraft("id_cdv_aircraft");
  compute_hideshow_instructor();
  compute_frais_CP();
  compute_prix();

  var aSegmentCountText=document.getElementById("id_cdv_segment_count").value;
  if(default_logbookid==0) {
     document.getElementById("id_submitButton").value="Enregistrez segment "+aSegmentCountText;
     document.getElementById("id_submitButton").disabled=true;
     document.getElementById("id_submitButton").style.backgroundColor = 'GhostWhite';
  }
  else {
      document.getElementById("id_submitButton").value="Modifiez segment "+aSegmentCountText;	
  }

}

//==============================================

function carnetdevol_page_after_loaded() {
   //("Page loaded !");
}

//==============================================
// Function: check_coherence_compteur
// Purpose: Check the compteur. 
//==============================================

function check_coherence_compteur()
{
	var aCount=0;
    var aDepartText=document.getElementById("id_cdv_compteur_depart").value;
	var aDepart=convertCompteurEnMinute(aDepartText);
	if(aDepart!=0) aCount+=1;
    var anArrivalText=document.getElementById("id_cdv_compteur_arrivee").value
	var anArrival=convertCompteurEnMinute(anArrivalText);
	if(anArrival!=0) aCount+=1;
    var aDureeText=document.getElementById("id_cdv_compteur_duree").value;
	var aDuree=convertDureeEnMinute(aDureeText);
	var aDeltaHour=anArrival-aDepart;
	
	//if(aDuree!=0) aCount+=1;
	
	if(aCount <2) {
		return;
	}
	/*
	else if(aCount == 3) {
		if(aDeltaHour != aDuree) {
			document.getElementById("id_cdv_compteur_duree").style.backgroundColor = 'orange';
			return false;
		}
		else {
			document.getElementById("id_cdv_compteur_duree").style.backgroundColor = 'white';			
		}
	}
	*/
	else {
		if(aDeltaHour < 0) {
			document.getElementById("id_cdv_compteur_arrivee").style.backgroundColor = 'orange';
			document.getElementById("id_cdv_compteur_depart").style.backgroundColor = 'white';
		}
		else if(aDeltaHour >0) {
			document.getElementById("id_cdv_compteur_duree").value=convertMinutesEnDureeText(aDeltaHour);
			//document.getElementById("id_cdv_compteur_duree").style.backgroundColor = ReadOnlyColor;
			document.getElementById("id_cdv_compteur_arrivee").style.backgroundColor = 'white';
			document.getElementById("id_cdv_compteur_depart").style.backgroundColor = 'white';
			
			//var aDureeHeure=document.getElementById("id_cdv_duree").value;
			//if(aDureeHeure=="") {
			document.getElementById("id_cdv_duree").value=convertMinutesEnDureeText(aDeltaHour);
				//document.getElementById("id_cdv_duree").style.backgroundColor = 'white';
				//}
				
			check_coherence_heure();
		}
		/*
		else if(anArrival==0) {
			anArrival=aDepart+aDuree;
			document.getElementById("id_cdv_compteur_arrivee").value=convertMinutesEnCompteurText(anArrival);
			document.getElementById("id_cdv_compteur_duree").style.backgroundColor = 'white';
			document.getElementById("id_cdv_compteur_arrivee").style.backgroundColor = 'white';
			document.getElementById("id_cdv_compteur_depart").style.backgroundColor = 'white';
		}					
		else if(aDepart==0) {
			aDepart=anArrival-aDuree;
			document.getElementById("id_cdv_compteur_depart").value=convertMinutesEnCompteurText(aDepart);
			document.getElementById("id_cdv_compteur_duree").style.backgroundColor = 'white';
			document.getElementById("id_cdv_compteur_arrivee").style.backgroundColor = 'white';
			document.getElementById("id_cdv_compteur_depart").style.backgroundColor = 'white';
		}
		*/					
	}	
	return true;	
}

//==============================================
// Function: check_coherence_compteur_vol
// Purpose: Check the compteur. 
//==============================================

function check_coherence_compteur_vol()
{
	var aCount=0;
    var aDepartText=document.getElementById("id_cdv_compteur_vol_depart").value;
	var aDepart=convertCompteurEnMinute(aDepartText);
	if(aDepart!=0) aCount+=1;
    var anArrivalText=document.getElementById("id_cdv_compteur_vol_arrivee").value
	var anArrival=convertCompteurEnMinute(anArrivalText);
	if(anArrival!=0) aCount+=1;
    var aDureeText=document.getElementById("id_cdv_compteur_vol_duree").value;
	var aDuree=convertDureeEnMinute(aDureeText);
	var aDeltaHour=anArrival-aDepart;
	
	if(aDuree!=0) aCount+=1;
	
	if(aCount <2) {
		return;
	}
	/*
	else if(aCount == 3) {
		if(aDeltaHour != aDuree) {
			document.getElementById("id_cdv_compteur_vol_duree").style.backgroundColor = 'orange';
			return false;
		}
		else {
			document.getElementById("id_cdv_compteur_vol_duree").style.backgroundColor = 'white';			
		}
	}
	*/
	else {
		if(aDeltaHour < 0) {
			document.getElementById("id_cdv_compteur_vol_arrivee").style.backgroundColor = 'orange';
			document.getElementById("id_cdv_compteur_vol_depart").style.backgroundColor = 'white';
		}
		else if(aDeltaHour >0) {
			document.getElementById("id_cdv_compteur_vol_duree").value=convertMinutesEnDureeText(aDeltaHour);
			//document.getElementById("id_cdv_compteur_vol_duree").style.backgroundColor = 'white';
			document.getElementById("id_cdv_compteur_vol_arrivee").style.backgroundColor = 'white';
			document.getElementById("id_cdv_compteur_vol_depart").style.backgroundColor = 'white';			
		}
		/*
		else if(anArrival==0) {
			anArrival=aDepart+aDuree;
			document.getElementById("id_cdv_compteur_vol_arrivee").value=convertMinutesEnCompteurText(anArrival);
			//document.getElementById("id_cdv_compteur_vol_duree").style.backgroundColor = 'white';
			document.getElementById("id_cdv_compteur_vol_arrivee").style.backgroundColor = 'white';
			document.getElementById("id_cdv_compteur_vol_depart").style.backgroundColor = 'white';
		}					
		else if(aDepart==0) {
			aDepart=anArrival-aDuree;
			document.getElementById("id_cdv_compteur_vol_depart").value=convertMinutesEnCompteurText(aDepart);
			//document.getElementById("id_cdv_compteur_vol_duree").style.backgroundColor = 'white';
			document.getElementById("id_cdv_compteur_vol_arrivee").style.backgroundColor = 'white';
			document.getElementById("id_cdv_compteur_vol_depart").style.backgroundColor = 'white';
		}
		*/					
	}	
	return true;	
}
//==============================================
// Function: check_date
// Purpose: Check the date. Not allowed to introduce a flight in the future
//==============================================
function check_date()
{
	var aDateText=document.getElementById("id_cdv_flight_date").value;
	var aDate=Date.parse(aDateText);
	var aToday= Date.now();
	if(aDate>aToday) {
		document.getElementById("id_cdv_flight_date").style.backgroundColor = 'orange';
		return false;
	}
	else {
		document.getElementById("id_cdv_flight_date").style.backgroundColor = 'white';		
	}
	return true;
}
//==============================================
// Function: getTodayAsString
// Purpose: Get the today date in string format
//==============================================
function getTodayAsString()
{
	var aToday= new Date();
	//var aTodayString=aToday.toString();
	var aYear=aToday.getFullYear();
	var aYearText=aYear.toString();
	var aMonth=aToday.getMonth()+1;
	var aMonthText=aMonth.toString();
	if(aMonth<10){
		aMonthText="0"+aMonthText;
	}
	var aDate=aToday.getDate();
	var aDateText=aDate.toString();
	if(aDate<10) {
		aDateText="0"+aDateText;
	}
	return aYearText+"-"+aMonthText+"-"+aDateText;
}

//==============================================
// Function: check_coherence_heure
// Purpose: Check the coherence between Heure Depart - Heure arrivee - Duree
//==============================================

function check_coherence_heure()
{
	var aCount=0;
    var aDepartText=document.getElementById("id_cdv_heure_depart").value;
	var aDepart=convertDureeEnMinute(aDepartText);
	if(aDepart!=0) aCount+=1;
    var anArrivalText=document.getElementById("id_cdv_heure_arrivee").value;
	var anArrival=convertDureeEnMinute(anArrivalText);
	if(anArrival!=0) aCount+=1;
    var aDureeText=document.getElementById("id_cdv_duree").value;
	var aDuree=convertDureeEnMinute(aDureeText);
	var aDeltaHour=anArrival-aDepart;
	if(aDeltaHour<0.) {
		aDeltaHour+=24*60.;
	}
	
	if(aDuree!=0) aCount+=1;
	
	if(aCount <2) {
		return;
	}
	else if(aCount == 3) {
		if(aDeltaHour != aDuree) {
			document.getElementById("id_cdv_duree").style.backgroundColor = 'orange';
			anArrival=0;
		}
		else {
			document.getElementById("id_cdv_duree").style.backgroundColor = 'white';			
		}
	}

		if(aDuree==0. && aDeltaHour < 360.) {
			document.getElementById("id_cdv_duree").value=convertMinutesEnDureeText(aDeltaHour);
			document.getElementById("id_cdv_duree").style.backgroundColor = 'white';
			document.getElementById("id_cdv_heure_arrivee").style.backgroundColor = 'white';
			document.getElementById("id_cdv_heure_depart").style.backgroundColor = 'white';
		}
		else if(anArrival==0) {
			anArrival=aDepart+aDuree;
			document.getElementById("id_cdv_heure_arrivee").value=convertMinutesEnHeureText(anArrival);
			document.getElementById("id_cdv_duree").style.backgroundColor = 'white';
			document.getElementById("id_cdv_heure_arrivee").style.backgroundColor = 'white';
			document.getElementById("id_cdv_heure_depart").style.backgroundColor = 'white';
		}					
		else if(aDepart==0) {
			aDepart=anArrival-aDuree;
			document.getElementById("id_cdv_heure_depart").value=convertMinutesEnHeureText(aDepart);
			document.getElementById("id_cdv_duree").style.backgroundColor = 'white';
			document.getElementById("id_cdv_heure_arrivee").style.backgroundColor = 'white';
			document.getElementById("id_cdv_heure_depart").style.backgroundColor = 'white';
		}					

	return true;	
}

//==============================================

function compute_prix_avion()
{
    var anAircraftName=document.getElementById("id_cdv_aircraft").value;
	var aPrixAvion=Number(GetPropertyFromId(anAircraftName, "prix", planes_properties));
    var aCompteurVol=Number(GetPropertyFromId(anAircraftName, "compteur_vol", planes_properties));
	var aDureeText="";
	if(aCompteurVol==0) {
		aDureeText=document.getElementById("id_cdv_compteur_duree").value;
	}
	else {
		//PH-AML
		aDureeText=document.getElementById("id_cdv_compteur_vol_duree").value;		
	}
	var aDuree=convertDureeEnMinute(aDureeText);
 	return aDuree*aPrixAvion;
}

//==============================================
function convertDureeEnMinute(theDureeText)
{
	var aPosition=theDureeText.indexOf(":")
	if(aPosition==-1) {
		return 0;
	}
	var aHour=Number(theDureeText.substr(0,aPosition));
	var aMinute=Number(theDureeText.substr(aPosition+1,2))
	return aHour*60+aMinute;
}

//==============================================
function InitDecimalCompteur(thePlaneName, theCompteur)
{
	var aCompteurType=Number(GetPropertyFromId(thePlaneName, "compteur_type", planes_properties));
	if(aCompteurType=="6") {
		var aPosition=theCompteur.indexOf(".");
		var aHourText="";
		var aMinute=0;
		// Compteur en dizième	
		aHourText=theCompteur.substr(0,aPosition);		
		aMinute=Number(theCompteur.substr(aPosition+1,2));
		aMinute/=6;
		return aHourText+"."+aMinute.toString();
	}
	return theCompteur;
}

//==============================================
function convertCompteurEnMinute(theCompteurText)
{
	if(theCompteurText=="") {
		return 0;
	}
    var anAircraftName=document.getElementById("id_cdv_aircraft").value;
	var aCompteurType=Number(GetPropertyFromId(anAircraftName, "compteur_type", planes_properties));
	
	var aPosition=theCompteurText.indexOf(".");
	if(aPosition==-1) {
		alert("%%%Programming Error: The counter doesn't contain a dot!");
		return 0;
	}
	var aHour=0;
	var aMinute=0;
	if(aCompteurType=="6") {
		// Compteur en dizième	
		aHour=Number(theCompteurText.substr(0,aPosition));
		aMinute=Number(theCompteurText.substr(aPosition+1,1));
		return aHour*60+aMinute*6;
	}
	else {
		aHour=Number(theCompteurText.substr(0,aPosition));
		aMinute=Number(theCompteurText.substr(aPosition+1,2));
	}
	return aHour*60+aMinute;
}
//==============================================
function convertMinutesEnDureeText(theDuree)
{
	var aHour=Math.floor(theDuree/60.);
	var aMinute=theDuree-60*aHour;
	var aHourText=aHour.toString();
	var aMinuteText=aMinute.toString();
	if(aMinuteText.length==1) {
		aMinuteText="0"+aMinuteText;
	}
	var aText=aHourText+":"+aMinuteText;
	return aText;
}
//==============================================
function convertMinutesEnHeureText(theMinutes)
{
	var aHour=Math.floor(theMinutes/60.);
	var aMinute=theMinutes-60*aHour;
	var aMinuteText=aMinute.toString();
	if(aMinuteText.length==1) {
		aMinuteText="0"+aMinuteText;
	}
	if(aHour>23.) {
		aHour-=24.;
	}
	else if(aHour<0.) {
		aHour+=24.;
	}
	var aHourText=aHour.toString();
	if(aHourText.length==1) {
		aHourText="0"+aHourText;
	}
	
	var aText=aHourText+":"+aMinuteText;
	return aText;
}
//==============================================
function convertMinutesEnCompteurText(theMinutes)
{
    var anAircraftName=document.getElementById("id_cdv_aircraft").value;
	var aCompteurType=GetPropertyFromId(anAircraftName,"compteur_type", planes_properties);	

	var aHour=Math.floor(theMinutes/60.);
	var aMinute=theMinutes-60*aHour;
	if(aCompteurType=="6") {
		//APV : decimal
		aMinute/=6;
		aMinute=parseInt(aMinute,10);
	}
	var aMinuteText=aMinute.toString();
	if(aMinuteText.length==1 && aCompteurType!="6") {
		aMinuteText="0"+aMinuteText;
	}
	var aHourText=aHour.toString();
	
	var aText=aHourText+"."+aMinuteText;
	return aText;
}

//==============================================

function compute_prix_fi()
{
	if(document.getElementById("id_cdv_pilot_function").value == "PIC") {
		// Pas de frais de dual command en PIC
		return 0.;
	}
 	if(document.getElementById("id_cdv_frais_DC").value.indexOf("No DC")==0) {
		// Pas de frais de dual command (FI ne se fait pas payer)
		return 0.;
	}
    var anAircraftName=document.getElementById("id_cdv_aircraft").value;
	var aPrixFI=Number(GetPropertyFromId("FI", "prix", prix));
    var aCompteurVol=Number(GetPropertyFromId(anAircraftName, "compteur_vol", planes_properties));
	var aDureeText="";
	if(aCompteurVol==0) {
		aDureeText=document.getElementById("id_cdv_compteur_duree").value;
	}
	else {
		//PH-AML
		aDureeText=document.getElementById("id_cdv_compteur_vol_duree").value;		
	}
	var aDuree=convertDureeEnMinute(aDureeText);
 	return aDuree*aPrixFI;
}

//==============================================

function check_passager_crew()
{
	var anAircraftName=document.getElementById("id_cdv_aircraft").value;
	var aCrews=Number(document.getElementById("id_cdv_nombre_crew").value);
	var aPassagers=Number(document.getElementById("id_cdv_nombre_passager").value);
	var anAircraftModel=GetPropertyFromId(anAircraftName, "model", planes_properties);
	var aPOB=aCrews+aPassagers;
	if(anAircraftModel.indexOf("C172")==0 || anAircraftModel.indexOf("C182")==0) {
		if(aPOB>4) {
			document.getElementById("id_cdv_nombre_crew").style.backgroundColor = 'orange';
			document.getElementById("id_cdv_nombre_passager").style.backgroundColor = 'orange';
			alert("Le nombre de crew + nombre de passagers ne peut pas exceder 4");
		}
		else {
			document.getElementById("id_cdv_nombre_crew").style.backgroundColor = 'white';
			document.getElementById("id_cdv_nombre_passager").style.backgroundColor = 'white';
		}
	}
	else {
		if(aPOB>2) {
			document.getElementById("id_cdv_nombre_crew").style.backgroundColor = 'orange';
			document.getElementById("id_cdv_nombre_passager").style.backgroundColor = 'orange';
			alert("Le nombre de crew + nombre de passagers ne peut pas exceder 2");
		}
		else {
			document.getElementById("id_cdv_nombre_crew").style.backgroundColor = 'white';
			document.getElementById("id_cdv_nombre_passager").style.backgroundColor = 'white';
		}
	}
}
//==============================================

function compute_prix_passager()
{
	// Plus de taxes passager après 1/11/2022
	var aDateText=document.getElementById("id_cdv_flight_date").value;
	var aDate=Date.parse(aDateText);
	var aOneNovember= Date.parse("2022-11-01");
	// Decision de garder la taxe passager le 28 novembre 2022. Donc suppression du test.
	// Tester garder pour une future utilisation
	if(0 && aDate>aOneNovember) {
		return 0.;
	}
	
	// Taxe passagers pour tous vols au depart d'un aerodrome belge
	// 10 euros jusque 270N a partir de Bruxelles 
	// 2 euros > 270 nm (A developper)
	var aDeparture=document.getElementById("id_cdv_departure_airport").value;
	var anArrival=document.getElementById("id_cdv_arrival_airport").value;
	if(aDeparture.indexOf(anArrival)==0) {
		// Pas de Taxes en local
		return 0.;
	}
	if(aDeparture.indexOf("EB")!=0) {
		// Pas de taxes passager a partir de l'etranger
		return 0.;
	}
	
	var aPassagerNumberText=document.getElementById("id_cdv_nombre_passager").value;
	var aPassagerNumber=parseInt(aPassagerNumberText,10);
	var aPrix=Number(GetPropertyFromId("Passager", "prix", prix)); 
	return aPassagerNumber*aPrix;
}

//==============================================

function getPiloteSolde()
{
	return 1000.;
}

//==============================================

function compute_prix()
{
	var aPrixAvion=compute_prix_avion();
	if(aPrixAvion>0.) {
		 document.getElementById("id_submitButton").disabled=false;
		 document.getElementById("id_submitButton").style.backgroundColor = 'LightGreen';
	}
	else {
	 	document.getElementById("id_submitButton").disabled=true;
		document.getElementById("id_submitButton").style.backgroundColor = 'GhostWhite';		
	}
	var anAircraftName=document.getElementById("id_cdv_aircraft").value;
	var aPrixUnitaireAvion=Number(GetPropertyFromId(anAircraftName, "prix", planes_properties));
	var aPrixFI=compute_prix_fi();
	var aPrixUnitaireFI=Number(GetPropertyFromId("FI", "prix", prix));
	var aPrixPassager=compute_prix_passager();
	var aCPType=document.getElementById("id_cdv_frais_CP").value;
	var aPrixTotal=aPrixAvion + aPrixFI + aPrixPassager;
	var aPrixTotalPilote=aPrixTotal;
	var aPrixTotalCP1=0.;
	var aPrixTotalCP2=0.;
	if(aCPType.indexOf("CP1")==0) {
		aPrixTotalPilote=aPrixFI + aPrixPassager;
		aPrixTotalCP1=aPrixAvion;
		aPrixTotalCP2=0.;
        document.getElementById("id_cdv_prix_total_cp1_row").style.display="";		
        document.getElementById("id_cdv_prix_total_cp2_row").style.display="none";		
 	}
	else if(aCPType.indexOf("CP2")==0) {
		aPrixTotalPilote=aPrixFI + aPrixPassager + aPrixAvion/2.;
		aPrixTotalCP1=0.;
		aPrixTotalCP2=aPrixAvion/2.;
        document.getElementById("id_cdv_prix_total_cp1_row").style.display="none";		
        document.getElementById("id_cdv_prix_total_cp2_row").style.display="";		
 	}
	else {
        document.getElementById("id_cdv_prix_total_cp1_row").style.display="none";		
        document.getElementById("id_cdv_prix_total_cp2_row").style.display="none";		
 	}
	
	var aSolde=getPiloteSolde();
	aSolde=aSolde - aPrixTotalPilote;
	document.getElementById("id_cdv_prix_avion").value=aPrixAvion.toFixed(2)+" € (P.U.: "+aPrixUnitaireAvion.toFixed(2)+"€)";
	document.getElementById("id_cdv_prix_fi").value=aPrixFI.toFixed(2)+" € (P.U.: "+aPrixUnitaireFI.toFixed(2)+"€)";
	document.getElementById("id_cdv_prix_passager").value=aPrixPassager.toFixed(2)+" €";
	document.getElementById("id_cdv_prix_total_pilote").value=aPrixTotalPilote.toFixed(2)+" €";
	document.getElementById("id_cdv_prix_total_cp1").value=aPrixTotalCP1.toFixed(2)+" €";
	document.getElementById("id_cdv_prix_total_cp2").value=aPrixTotalCP2.toFixed(2)+" €";
	document.getElementById("id_cdv_prix_reference").value="Date/PilotID/reference";
	document.getElementById("id_cdv_prix_solde").value=aSolde.toFixed(2)+" €";
}

//==============================================

function compute_defaultHourValues(theDepartHour, theArrivalHour)
{

	var aDuree=40;
	document.getElementById("id_cdv_heure_depart").placeholder=theDepartHour;
	if(theDepartHour!="") {
		document.getElementById("id_cdv_heure_depart").value=theDepartHour;
	}
	if(theArrivalHour == "") {
		// insert
		var anHourMinute=convertDureeEnMinute(theDepartHour);
		anHourMinute+=aDuree;
		document.getElementById("id_cdv_heure_arrivee").placeholder=convertMinutesEnHeureText(anHourMinute);
		document.getElementById("id_cdv_duree").placeholder=(aDuree).toString();
	}
	else {
		// Edit
		document.getElementById("id_cdv_heure_arrivee").value=theArrivalHour;	
		aDuree=convertDureeEnMinute(theArrivalHour)-convertDureeEnMinute(theDepartHour);	
		document.getElementById("id_cdv_duree").value=convertMinutesEnDureeText(aDuree);
	}

}
//==============================================

function compute_defaultPlaneValues(thePlaneName)
{
	// ====Compteur Moteur===
	var aDureeMo=40;
	var aCompteurMO="";
	var aCompteurMOArrivee="";
	if(default_compteur_moteur_start == "") {
		// Insert
		aCompteurMO=GetPropertyFromId(thePlaneName, "compteur", planes_properties); 
	}
	else {
		//Edition
		aCompteurMO=default_compteur_moteur_start;	
	}
	aCompteurMO=InitDecimalCompteur(thePlaneName,aCompteurMO);
	var aCompteurDepartInput=document.getElementById("id_cdv_compteur_depart");
	aCompteurDepartInput.value="";
	var aCompteurMOText=convertMinutesEnCompteurText(convertCompteurEnMinute(aCompteurMO));
	aCompteurDepartInput.placeholder=aCompteurMOText;
	aCompteurDepartInput.value=aCompteurMOText;
	// Set focus at the end of the string
	//aCompteurDepartInput.focus();
	aCompteurDepartInput.setSelectionRange(aCompteurMOText*2, aCompteurMOText*2);
	
	var aCompteurArriveeInput=document.getElementById("id_cdv_compteur_arrivee");	
	if(default_compteur_moteur_end == "") {
		// Insert
		aCompteurMOArrivee=convertCompteurEnMinute(aCompteurMO)+aDureeMo;
		aCompteurArriveeInput.placeholder=convertMinutesEnCompteurText(aCompteurMOArrivee);
		aCompteurArriveeInput.value="";
		document.getElementById("id_cdv_compteur_duree").value="";
		document.getElementById("id_cdv_compteur_duree").placeholder=aDureeMo.toString();
	}
	else {
		//Edition
		aCompteurMOArrivee=default_compteur_moteur_end;
		aCompteurMOArrivee=InitDecimalCompteur(thePlaneName,aCompteurMOArrivee);
		aCompteurArriveeInput.value=aCompteurMOArrivee;
		aDureeMO=convertCompteurEnMinute(aCompteurMOArrivee)-convertCompteurEnMinute(aCompteurMO);
		document.getElementById("id_cdv_compteur_duree").value=aDureeMO;
		check_duree("id_cdv_compteur_duree");
	}
	
	// ===Compteur Vol===
	var aDureeVol=30;
	var aCompteurVol="";
	if(default_compteur_flight_start == "") {
		// Insert
		aCompteurVol=GetPropertyFromId(thePlaneName, "compteur_vol_valeur", planes_properties);
	}
	else {
		//Edition
		aCompteurVol=default_compteur_flight_start;	
	}	
	var aCompteurVolDepartInput=document.getElementById("id_cdv_compteur_vol_depart");
	aCompteurVolDepartInput.value="";
	aCompteurVolDepartInput.placeholder=convertMinutesEnCompteurText(convertCompteurEnMinute(aCompteurVol));
	aCompteurVolDepartInput.value=convertMinutesEnCompteurText(convertCompteurEnMinute(aCompteurVol));
	var aCompteurVolArriveeInput=document.getElementById("id_cdv_compteur_vol_arrivee");
	if(default_compteur_flight_end == "") {
		// Insert
		var aCompteurVolArrivee=convertCompteurEnMinute(aCompteurVol)+aDureeVol;
		aCompteurVolArriveeInput.value="";
		aCompteurVolArriveeInput.placeholder=convertMinutesEnCompteurText(aCompteurVolArrivee);
		document.getElementById("id_cdv_compteur_vol_duree").value="";
		document.getElementById("id_cdv_compteur_vol_duree").placeholder=aDureeVol.toString();
		
	}
	else {
		//Edition
		var aCompteurVolArriveeText=default_compteur_flight_end;
		aCompteurVolArriveeInput.value=aCompteurVolArriveeText;
		aDureeVol=convertCompteurEnMinute(aCompteurVolArriveeText)-convertCompteurEnMinute(aCompteurVol);
		document.getElementById("id_cdv_compteur_vol_duree").value=aDureeVol;
		check_duree("id_cdv_compteur_vol_duree");
	}

	var aACModel=GetPropertyFromId(thePlaneName, "model", planes_properties); 
	var aACModelInput=document.getElementById("id_cdv_aircraft_model");
	aACModelInput.value=aACModel;

}
//==============================================

function compute_defaultPilotValues(theUserIdText)
{
	var aPilotNameInput=document.getElementById("id_cdv_pilot_name");
    for (var i = 0; i < aPilotNameInput.length; i++) {
		var aPilotNameOption=aPilotNameInput[i];
		
		if(theUserIdText==aPilotNameOption.value) {
			aPilotNameOption.selected=true;
		}
		else {
			aPilotNameOption.selected=false;
		}
     }
}	
//==============================================

function compute_defaultInstructorValues(theInstructorId)
{
	 // Default Instructor
    var aFunctionInput=document.getElementById("id_cdv_pilot_function");
	if(theInstructorId==0) {
		aFunctionInput.value="PIC";
	}
	else {
		if(default_is_pic == 1) {
			aFunctionInput.value="PICRecheck";				
		}
		else {		
			aFunctionInput.value="DC";	
		}	
	}
	var anInstructorInput=document.getElementById("id_cdv_flight_instructor");
	anInstructorInput.value=theInstructorId;	
	compute_hideshow_instructor(); 
}
//==============================================

function compute_defaultPlane(thePlaneId)
{
	var aPlaneInput=document.getElementById("id_cdv_aircraft");
    for (var i = 0; i < aPlaneInput.length; i++) {
		var aPlaneOption=aPlaneInput[i];
		
		if(thePlaneId==aPlaneOption.value) {
			aPlaneOption.selected=true;
		}
		else {
			aPlaneOption.selected=false;
		}
     }
}

//==============================================

function compute_defaultPartageFrais()
{
	if(default_instructor_paid == 1) {
		document.getElementById("id_cdv_frais_DC").value="DC";		
	}
	else {
		document.getElementById("id_cdv_frais_DC").value="No DC";
	}	
	var aRemark=default_remark;
	var aNumeroVol=default_flight_reference;
	// Extract the flight number (#xxx) from the remark
	if(aRemark.length>1) {
		if(aRemark.substr(0,1) == "#") {
			var aPos=aRemark.indexOf(" ");
			if(aPos==-1 && aRemark.length>1) {
				aNumeroVol=aRemark.substr(1,aRemark.length-1);
			}
			else {
				aNumeroVol=aRemark.substr(1,aPos-1);			
			}
			if(aRemark.length>aNumeroVol.length+1) {
				aRemark=aRemark.substr(aNumeroVol.length+2);
			}
			else {
				aRemark="";
			}
		}
	}
	document.getElementById("id_cdv_frais_CP_type").value=default_share_member;
	document.getElementById("id_cdv_frais_numero_vol").value=aNumeroVol;
	document.getElementById("id_cdv_frais_remarque").value=aRemark;
	document.getElementById("id_cdv_frais_CP").value=default_share_type;
	//check_remark();
}
//==============================================

function compute_defaultValues()
{
	if(default_pilot == default_instructor) {
		// Instructor which reserved for himself as an instructor
		default_instructor=0;
	}
	compute_defaultPilotValues(default_pilot);
	compute_defaultInstructorValues(default_instructor);
	compute_defaultPlane(default_plane);
	if(default_date_heure_depart != "") {
		var aPosition = default_date_heure_depart.indexOf(" ");
		if(aPosition!=-1) {
			var aDate=default_date_heure_depart.substr(0, aPosition);
    		document.getElementById("id_cdv_flight_date").value=aDate;
			var aDepartureHour=default_date_heure_depart.substr( aPosition+1, 5);
			var anArrivalHour = "";
			if(default_date_heure_arrivee != "") {
				// Insert
				aPosition = default_date_heure_arrivee.indexOf(" ");
				if(aPosition!=-1) {
					var anArrivalHour=default_date_heure_arrivee.substr( aPosition+1, 5);
				}
			}			
			compute_defaultHourValues(aDepartureHour, anArrivalHour);
		}
	}
	document.getElementById("id_cdv_departure_airport").value=default_from;
	document.getElementById("id_cdv_arrival_airport").value=default_to;
	document.getElementById("id_cdv_bookingid").value=default_bookingid;
	document.getElementById("id_cdv_logbookid").value=default_logbookid;
	document.getElementById("id_cdv_flightreferenceid").value=default_flight_id;
	document.getElementById("id_cdv_nombre_atterrissage").value=default_day_landing;
	if(default_flight_type!="Local" || default_flight_type!= "Nav") {
		var aFlightType=default_flight_type.toUpperCase().trim();
		if(aFlightType.substr(0,3)=="NAV") {
			default_flight_type="Nav";
		}
		else if(aFlightType.substr(0,3)=="LOC") {
			default_flight_type="Local";
		}
		else {
		 default_flight_type="Local";
	   } 
	}
	document.getElementById("id_cdv_nature_vol").value=default_flight_type;
	document.getElementById("id_cdv_nombre_crew").value=default_crew_count;
	document.getElementById("id_cdv_nombre_passager").value=default_pax_count;

	compute_defaultPartageFrais();
	
    var aSegmentCountText=document.getElementById("id_cdv_segment_count").value;
	if(aSegmentCountText=='' || aSegmentCountText== '0') {
		document.getElementById("id_cdv_segment_count").value='1';
	}
}

//==============================================
function check_value(id_cdv_val)
{
	var aTextInput=document.getElementById(id_cdv_val);
	aTextInput.value=id_cdv_val;
}
//==============================================
function check_airport(id_cdv_val)
{
	var aTextInput=document.getElementById(id_cdv_val);
	var anAirportName=aTextInput.value.trim();
	if(anAirportName.length!=4) {
		aTextInput.style.backgroundColor = 'orange';
	}
	else {
	    aTextInput.value=anAirportName.toUpperCase();
	    aTextInput.style.backgroundColor = 'white';
    }
}
//==============================================
function check_numero_vol()
{
	
	// Check if the structure is something like V-INIT-xxxxx or INIT-xxxxx
	var aTextInput=document.getElementById("id_cdv_frais_numero_vol");
	var aNumeroVolText=aTextInput.value.toUpperCase().trim();
	aTextInput.value=aNumeroVolText;
	var anArray = aNumeroVolText.split("-");
	var aFlag=true;
	aTextInput.style.backgroundColor = 'white';	
	if(anArray.length==1){
		aTextInput.style.backgroundColor = 'orange';
		aFlag=false;
	}
	else if(anArray.length>3){
		aTextInput.style.backgroundColor = 'orange';
		aFlag=false;
	}
	var aFlightPresales="";
	var aFlightType="";
	var aFlightNumber="";
	if(anArray.length==3){
		aFlightPresales=anArray[0];
		aFlightType=anArray[1];
		aFlightNumber=anArray[2];
	}
	if(anArray.length==2){
		aFlightType=anArray[0];
		aFlightNumber=anArray[1];
	}
	if(anArray.length==1){
		aFlightNumber=anArray[0];
	}
	if(aFlightPresales !="V" && aFlightPresales !=""){
		aTextInput.style.backgroundColor = 'orange';
		aFlag=false;			
	}
	var aCPType=document.getElementById("id_cdv_frais_CP_type").value;
	// IF(-4), Initiation(-3), vol membre(-5),  vol D.H.F(-6),vol PR(-7),
	if(aCPType==-3 && aFlightType!="INIT") {
		aTextInput.style.backgroundColor = 'orange';
		aFlag=false;
		aFlightType="INI";	
	}
	else if(aCPType==-4 && aFlightType!="IF") {
		aTextInput.style.backgroundColor = 'orange';
		aFlag=false;
		aFlightType="IF";	
	}
	else if(aCPType==-5 && aFlightType!="MEM") {
		aTextInput.style.backgroundColor = 'orange';
		aFlag=false;
		aFlightType="MEM";	
	}
	else if(aCPType==-6 && aFlightType!="IF") {
		aTextInput.style.backgroundColor = 'orange';
		aFlag=false;
		aFlightType="IF";	
	}
	
	var aNumeroVol=parseInt(aFlightNumber,10);
	if(isNaN(aNumeroVol)) {
		aTextInput.style.backgroundColor = 'orange';
		aFlag=false;
	}
	else if(aNumeroVol < 230000 || aNumeroVol > 299999) {
		aTextInput.style.backgroundColor = 'orange';
		aFlag=false;
	}
	if(!aFlag) {
		alert("Le numéro du vol n'est pas correct. Il doit avoir une structure du genre.\n"+aFlightType+"-231234 ou V-"+aFlightType+"-231234");
	}
}
//==============================================

function check_remark()
{
	var aRemark=document.getElementById("id_cdv_frais_remarque").value.toUpperCase().trim();
	document.getElementById("id_cdv_frais_remarque").style.backgroundColor = 'white';				
	if(aRemark.length>0) {
		if(aRemark.substr(0,3)=="INI") {
			if(document.getElementById("id_cdv_frais_CP").value=="NoCP") {
				document.getElementById("id_cdv_frais_remarque").style.backgroundColor = 'orange';	
				document.getElementById("id_submitButton").disabled=true;
				alert("Pour introduire un vol d'initiation, vous devez introduire un partage de frais de type CP1 Initiation");			
			}			
		}
		else if(aRemark.substr(0,3)=="CP1" || aRemark.substr(0,3)=="CP2" ) {
			if(document.getElementById("id_cdv_frais_CP").value=="NoCP") {
				document.getElementById("id_cdv_frais_remarque").style.backgroundColor = 'orange';	
				document.getElementById("id_submitButton").disabled=true;
				alert("Pour introduire un vol CP1 ou CP2, vous devez introduire un partage de frais de type CP1 ou CP2");			
			}			
		}
	}
}
//==============================================
function check_compteur(id_cdv_val)
{
	// Allowed Syntax: 1000.01 execpt 1000.1 for OO-APV
	var aTextInput=document.getElementById(id_cdv_val);
	var aCompteur=aTextInput.value.trim();
	var aHeureCompteurText="";
	var aMinuteCompteurText="";
	var aPosition = aCompteur.indexOf(".");
	if(aPosition==-1) {
		aPosition = aCompteur.indexOf(",");
	}
	if(aPosition!=-1) {
		aHeureCompteurText=aCompteur.substr(0, aPosition);
		aMinuteCompteurText=aCompteur.substr(aPosition+1, aCompteur.length-aPosition-1);
	}
	else if(GetPlaneCompteurType()=="6") {
		//Compteur decimal ((OO-APV)) 12345 -> 1234 + 5
		aHeureCompteurText=aCompteur.substr(0, aCompteur.length-1);
		aMinuteCompteurText=aCompteur.substr(aCompteur.length-1, 1);
	}
	else {
		//Compteur minute  123456 -> 1234 + 56
		aHeureCompteurText=aCompteur.substr(0, aCompteur.length-2);
		aMinuteCompteurText=aCompteur.substr(aCompteur.length-2, 2);		
	}
	var aHeureCompteur=parseInt(aHeureCompteurText,10);
	if(isNaN(aHeureCompteur) || aHeureCompteur<0) {
		aTextInput.style.backgroundColor = 'orange';
		return;				
	}
	var aMinuteCompteur=parseInt(aMinuteCompteurText,10);
	if(isNaN(aMinuteCompteur) || aMinuteCompteur<0) {
		aTextInput.style.backgroundColor = 'orange';
		return;				
	}
	if(GetPlaneCompteurType()=="6") {
		//Compteur decimal ((OO-APV))
		if(aMinuteCompteurText.length!=1) {
			aTextInput.style.backgroundColor = 'orange';
			return;				
		}
	}
	else {
		//Compteur minutes All except OO-APV
		if(aMinuteCompteurText.length!=2) {
			aTextInput.style.backgroundColor = 'orange';
			return;				
		}		
		if(aMinuteCompteur>59) {
			aTextInput.style.backgroundColor = 'orange';
			return;							
		}
	}
	aTextInput.value=aHeureCompteurText+"."+aMinuteCompteurText;
	aTextInput.style.backgroundColor = 'white';
	
	//Check if the value if not to far from the previous entered value
	var anAircraftName=document.getElementById("id_cdv_aircraft").value;
	var aPos=id_cdv_val.indexOf("_vol_");
	var aDelta=0;
	if(aPos==-1) {
		var aCompteurMOText=GetPropertyFromId(anAircraftName, "compteur", planes_properties);
		var aCompteurMO=parseInt(aCompteurMOText,10); 
		aDelta=Math.abs(aCompteurMO-aHeureCompteur);
	}
	else {
		var aCompteurVolText=GetPropertyFromId(anAircraftName, "compteur_vol_valeur", planes_properties);
		var aCompteurVol=parseInt(aCompteurVolText,10); 
		aDelta=Math.abs(aCompteurVol-aHeureCompteur);
	}
	if(aDelta > 100) {
		aTextInput.style.backgroundColor = 'orange';
		// Warning: Alert stop the script process
		if (confirm("La valeur du compteur ne semble pas correcte.\nValeur trop différente de la dernière valeur introduite ("+
			aTextInput.placeholder+
			").\nVoulez-vous vraiment introduire cette valeur?") == false) {
				aTextInput.value="";
		}
		//alert("Valeur compteur trop differente!/nValeur introduite: "+Compteur+"/nDernière valeur introduite: "+aCompteurMOText);
	}
}
//==============================================
function check_heure(id_cdv_val)
{
	// Allowed Syntax: 930, 9:30, 9 30, 0930, 09 30, 09:30
	var aTextInput=document.getElementById(id_cdv_val);
	var aTime=aTextInput.value.trim();
	if(aTime.length<3 || aTime.length>5) {
		aTextInput.style.backgroundColor = 'orange';
		return;
	}
	var aHourText="";
	var aMinuteText="";
	var aPosition = aTime.indexOf(":");
	if(aPosition==-1) {
		aPosition = aTime.indexOf(" ");
	}
	if(aPosition==-1) {
		if(aTime.length==3) {
			aHourText=aTime.substr(0, 1);
			aMinuteText=aTime.substr(1,2);
		}
		else if(aTime.length==4) {
			aHourText=aTime.substr(0, 2);
			aMinuteText=aTime.substr(2,2);	
		}
	}
	else if(aPosition==aTime.length-3) {
		aHourText=aTime.substr(0, aPosition);
		aMinuteText=aTime.substr(aPosition+1,2);		
	}
	else {
		aTextInput.style.backgroundColor = 'orange';
		return;
	}
	if(aHourText.length==1) {
		aHourText="0"+aHourText;
	}
	var aHour=parseInt(aHourText,10);
	var aMinute=parseInt(aMinuteText,10);
	if(isNaN(aHour) || aHour<0 ||aHour>23) {
		aTextInput.style.backgroundColor = 'orange';
		return;
	}
	if(isNaN(aMinute) || aMinute<0 ||aMinute>59) {
		aTextInput.style.backgroundColor = 'orange';
		return;
	}
	aTextInput.value=aHourText+":"+aMinuteText;
	aTextInput.style.backgroundColor = 'white';
}
//==============================================
function check_duree(id_cdv_val)
{
	// Allowed Syntax: 1h30, 1:30, 01h30, 01:30, 90
	//                 2h30, 2:30, 02h30, 02:30, 180
	var aTextInput=document.getElementById(id_cdv_val);
	var aTime=aTextInput.value.trim();
	if(aTime.length>5) {
		aTextInput.style.backgroundColor = 'orange';
		return;
	}
	var aHourText="";
	var aMinuteText="";
	var aPosition = aTime.indexOf(":");
	if(aPosition==-1) {
		aPosition = aTime.indexOf("h");
	}
	if(aPosition==-1) {
		aHourText="0";
		aMinuteText=aTime;
	}
	else if(aTime.length-aPosition-1 > 0 && aTime.length-aPosition-1 <3) {
		aHourText=aTime.substr(0, aPosition);
		aMinuteText=aTime.substr(aPosition+1,aTime.length-aPosition-1);		
	}
	else {
		aTextInput.style.backgroundColor = 'orange';
		return;
	}
	var aHour=parseInt(aHourText,10);
	var aMinute=parseInt(aMinuteText,10);
	if(isNaN(aHour) || aHour<0 ||aHour>10) {
		aTextInput.style.backgroundColor = 'orange';
		return;
	}
	if(isNaN(aMinute) || aMinute<0 ) {
		aTextInput.style.backgroundColor = 'orange';
		return;
	}
	if(aHour>0 && aMinute>60) {
		aTextInput.style.backgroundColor = 'orange';
		return;		
	}
	else if(aHour==0 && aMinute>59) {
		aHour=Math.floor(aMinute/60.);
		aMinute=aMinute-60*aHour;
		aHourText=aHour.toString();
		aMinuteText=aMinute.toString();
	}
	if(aMinuteText.length==1) {
		aMinuteText="0"+aMinuteText;
	}
	aTextInput.value=aHourText+":"+aMinuteText;
	aTextInput.style.backgroundColor = 'white';
}
//==============================================

function compute_hideshow(theAircraft) 
{
// Manage Hide/Show rows about  compteur vol
   if(theAircraft!="PH-AML") {
       document.getElementById("id_cdv_compteur_depart_vol_row").style.display="none";
       document.getElementById("id_cdv_compteur_arrivee_vol_row").style.display="none";
       document.getElementById("id_cdv_compteur_duree_vol_row").style.display="none";
    }

   else  {
       document.getElementById("id_cdv_compteur_depart_vol_row").style.display="";
       document.getElementById("id_cdv_compteur_arrivee_vol_row").style.display="";
       document.getElementById("id_cdv_compteur_duree_vol_row").style.display="";
   }
}
//==============================================

function compute_hideshow_instructor() 
{
    // Manage Instructor row
    var aPilotFunction=document.getElementById("id_cdv_pilot_function").value;
    if(aPilotFunction=="PIC") {
        document.getElementById("id_cdv_flight_instructor_row").style.display="none";
        document.getElementById("id_cdv_frais_DC_row").style.display="none";
		document.getElementById("id_cdv_nombre_crew").value=1;
    }
    else {
        document.getElementById("id_cdv_flight_instructor_row").style.display="";  	
        document.getElementById("id_cdv_frais_DC_row").style.display="";
		document.getElementById("id_cdv_nombre_crew").value=2;
     }
}
//==============================================

function compute_aircraft(val) 
{

   var anAircraftName=document.getElementById("id_cdv_aircraft").value;
 
   if(val=="id_cdv_aircraft") {

       compute_hideshow(anAircraftName);

   }
   compute_defaultPlaneValues(anAircraftName);
   //var aUserId=PHPRequest("userid");
   //document.getElementById("id_cdv_frais_remarque").value = aUserId;
}
//===============================================

function compute_frais_CP() 
{
    var aCP=document.getElementById("id_cdv_frais_CP").value;
	if(aCP=="NoCP") {
		document.getElementById("id_cdv_frais_numero_vol").style.display="none";
		document.getElementById("id_cdv_frais_CP_PAX").style.display="none";
		document.getElementById("id_cdv_frais_CP_type").style.value=0;
		document.getElementById("id_cdv_frais_CP_PAX").style.value=0;
	}
	else {
		document.getElementById("id_cdv_frais_CP_type").style.display="";
		var aCPType=document.getElementById("id_cdv_frais_CP_type").value;
		// Activer numero vol pour Vol IF(-4), Initiation(-3), vol membre(-5),  vol D.H.F(-6),vol PR(-7),
		if(aCPType==-3 || aCPType==-4|| aCPType==-5|| aCPType==-6|| aCPType==-7) {
			document.getElementById("id_cdv_frais_numero_vol").style.display="";
			if(aCPType==-3) {
				document.getElementById("id_cdv_frais_numero_vol").placeholder="V-INIT-231XXX";
			}
			else if(aCPType==-4) {
				document.getElementById("id_cdv_frais_numero_vol").placeholder="V-IF-231XXX";
			}
			else if(aCPType==-5) {
				document.getElementById("id_cdv_frais_numero_vol").placeholder="V-MEM-231XXX";
			}
			else if(aCPType==-6) {
				document.getElementById("id_cdv_frais_numero_vol").placeholder="V-DHF-231XXX";
			}
			else if(aCPType==-7) {
				document.getElementById("id_cdv_frais_numero_vol").placeholder="V-PR-231XXX";
			}
			else {
				document.getElementById("id_cdv_frais_numero_vol").placeholder="V-XXX-231XXX";
			}
		}	
		else {
			document.getElementById("id_cdv_frais_numero_vol").style.display="none";
			document.getElementById("id_cdv_frais_numero_vol").value=default_flight_reference;
		}
		// L'input PAX n'est plus utilise. Il est integre dans CP_Type
		document.getElementById("id_cdv_frais_CP_PAX").style.display="none";
	}	
}
//===============================================
function GetPlaneCompteurType()
{
    var anAircraftName=document.getElementById("id_cdv_aircraft").value;
	var aCompteurType=GetPropertyFromId(anAircraftName,"compteur_type", planes_properties);	
	return aCompteurType;
}
	
//===============================================
function computeRenamedPilots(pilots_renamed, thePilots)
{
	// Rename pilote names from "Patrick Reginster" -> "Reginster P."
	// To be closer to the real "Logbook" and shorter (Better for smart phone)
    for (var i = 0; i < thePilots.length; i++) {
		//if(i>150) {
 		//   var aName="thePilots[i].name";			
		//}
 	   if(thePilots[i].id != -1) {
		   var aName=thePilots[i].name.trim();
		   var aPosition=aName.indexOf(" ");
		   var aPilotName=aName.substr(aPosition+1,aName.length-aPosition-1)+" "+aName.substr(0,1)+".";
		   var aKeesPosition=aPilotName.indexOf(")");
		   if(aKeesPosition!=-1) {
		   	// Remove (Kees) use case
			   aPilotName=aPilotName.substr(aKeesPosition+2,aPilotName.length-aKeesPosition-1);
		   }
		   var aPilot = {id: thePilots[i].id, name: aPilotName, originalName:aName};
		   pilots_renamed.push(aPilot);
       }
   }
   pilots_renamed.sort(compareName);
}

//===============================================
function computePAX(PAX)
{
    for (var i = 1; i < shareCodes.length; i++) {
	   var aName=shareCodes[i].name;
	   var anId=shareCodes[i].id;
	   var aPAX = {id: anId, name: aName, originalName:aName};
	   PAX.push(aPAX);
	}
	var members_renamed=[];
	computeRenamedPilots(members_renamed, members);
    for (var i = 0; i < members_renamed.length; i++) {
	   var aName=members_renamed[i].name;
	   var aOriginalName=members_renamed[i].originalName;
	   var anId=members_renamed[i].id;
	   var aPAX = {id: anId, name: aName, originalName:aOriginalName};
	   PAX.push(aPAX);
	}
}
//===============================================
function compareName(a, b) 
{
    // converting to uppercase to have case-insensitive comparison
    const name1 = a.name.toUpperCase();
    const name2 = b.name.toUpperCase();

    let comparison = 0;

    if (name1 > name2) {
        comparison = 1;
    } else if (name1 < name2) {
        comparison = -1;
    }
    return comparison;
}

//===============================================

function GetPropertyFromId(theValueId, theProperty, theValuesArray) 
{

   for (var i = 0; i < theValuesArray.length; i++) {
      var id=theValuesArray[i].id ;
	  if(id==theValueId) {
		  var aRow=theValuesArray[i];
		  return aRow[theProperty];
	  }
    }
	return "";
}
//===============================================

function GetInstructors() 
{
	var anInstructors=[];
    for (var i = 0; i < instructors.length; i++) {
		var anId=instructors[i].id;
		if(anId>0){
	   		var aName=instructors[i].name;
	    	var anInstructor = {id: anId, name: aName};
	   	 	anInstructors.push(anInstructor);
		}
	}
	var anInstructor = {id: -1, name: "Examinateur externe"};	
 	anInstructors.push(anInstructor);
 	return anInstructors;
}

//===============================================

function prefillDropdownMenus(selectName, valuesArray) {

   var select = document.getElementById(selectName);

   for (var i = 0; i < valuesArray.length; i++) {
        var option = document.createElement("option");
        option.text = valuesArray[i].name ;
        option.value = valuesArray[i].id ;
        select.add(option) ;
   }
}
//===============================================

function PHPRequest(theRequest) {
    const xmlhttp = new XMLHttpRequest();

    xmlhttp.onload = function() {
		var anOutput=this.responseText;
        //document.getElementById("id_cdv_frais_remarque").value = anOutput;
 		if(anOutput.indexOf("userid=")==0){
			compute_defaultPilotValues(anOutput.substr(7,anOutput.length-7));
		}
    }
  xmlhttp.open("GET", "https://www.spa-aviation.be/scripts/carnetdevol/php_carnetdevol.php?action=" + theRequest);
  xmlhttp.send();
}
//===============================================
// Acces a l'ancien carnet de vol
function AncienlogbookClick(id, auth) {
	console.log('logbookClick() id=' + id) ;
	window.location.href = '../../resa/mobile_logbook.php?id=' + id + '&auth=' + auth ;
}

//===============================================
// Delete one segment in the logbook
function redirectDelete(id, auth, auditTime) {
 	if (confirm("Voulez-vous vraiment supprimer ce segment de vol?") == true) {
		window.location.href = 'IntroCarnetVol.php?id=' + id + '&auth=' + auth + '&audit_time=' + auditTime;
	}
}
//===============================================
// Delete one reservation in the booking table
function redirectBookingDelete(PHP_Self,id, auth) {
	 if (confirm("Voulez-vous vraiment supprimer cette réservation\n et tous les vols (Segments) éventuellement associés à cette réservation?") == true) {
		 var aCommand=PHP_Self+"?id="+id+"&auth="+ auth + '&bookingtable=1';
		 window.location.href = aCommand;
	 }
}
//===============================================
// Delete one segment in the logbook table
//window.location.href='$_SERVER[PHP_SELF]?id=$bookingid&logid=$logid&auth=$auth&audit_time=$row[l_audit_time]';
function redirectLogbookDelete(PHP_Self,id, logid, auth, audit_time) {
	 if (confirm("Voulez-vous vraiment supprimer ce segment?") == true) {
		 var aCommand=PHP_Self+"?id="+id+"&logid="+logid+"&auth="+auth+"&audit_time="+audit_time;
		 window.location.href=aCommand; 
	 }
}

//===============================================
// Main
var ReadOnlyColor="AliceBlue";
//var default_editflag=0;
//var default_segment=0;
//var default_bookingid="";
//var default_logbookid="";
//var default_auth="";
//var default_logid=0;
//var default_plane="";
//var default_date="";
//var default_pilot=0;
//var default_heure_depart="";
//var default_instructor=0;
//var default_date_heure_depart=;
//var default_date_heure_arrivee="";
//var default_day_landing=0;
//var default_crew_count=1;
//var default_pax_count=0;
//var default_flight_type="";
//var default_from="";
//var default_to="";
//var default_is_pic=1;
//var default_instructor_paid=1;
//var default_share_type="";
//var default_share_member=0;
//var default_flight_reference="";
//var default_remark="";
//var default_compteur_moteur_start="";
//var default_compteur_moteur_end="";	 	
//var default_compteur_flight_start="";
//var default_compteur_flight_end="";	 	

var pilots_renamed=[];
computeRenamedPilots(pilots_renamed, pilots);
var PAX=[];
computePAX(PAX);
prefillDropdownMenus('id_cdv_aircraft', planes) ;
prefillDropdownMenus('id_cdv_pilot_name', pilots_renamed) ;
prefillDropdownMenus('id_cdv_flight_instructor', GetInstructors()) ;
prefillDropdownMenus('id_cdv_frais_CP_type', PAX) ;
//prefillDropdownMenus('id_cdv_frais_CP_PAX', members) ;

window.onload=carnetdevol_page_loaded();
//PHPRequest("userid");
//document.getElementById("id_form_language").style.display="none";

