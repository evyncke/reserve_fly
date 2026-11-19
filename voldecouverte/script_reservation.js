
function reservation_page_loaded() {

    document.getElementById("id_pleaseWait").innerHTML  = "";
    document.getElementById("id_submit").onclick = function() {submitFunction()};
    //document.getElementById("id_submit").onclick = function() {alert("The form was submitted - Please wait")};

    document.getElementById("id_numberofpassagers").oninput = function() {compute_reservation("id_numberofpassagers")};

    document.getElementById("id_firstname1").onchange = function() {check_field("id_firstname1");check_submit();};
    check_field("id_firstname1");
    document.getElementById("id_lastname1").onchange = function() {check_field("id_lastname1");check_submit();};
    check_field("id_lastname1");
    document.getElementById("id_age1").onchange = function() {check_field("id_age1");check_submit();};
    check_field("id_age1");
    document.getElementById("id_weight1").onchange = function() {check_field("id_weight1");check_submit();};
    check_field("id_weight1");
    document.getElementById("id_firstname2").onchange = function() {check_field("id_firstname2");check_submit();};
    check_field("id_firstname2");
    document.getElementById("id_lastname2").onchange = function() {check_field("id_lastname2");check_submit();};
    check_field("id_lastname2");
    document.getElementById("id_age2").onchange = function() {check_field("id_age1");check_submit();};
    check_field("id_age2");
    document.getElementById("id_weight2").onchange = function() {check_field("id_weight1");check_submit();};
    check_field("id_weight2");
    document.getElementById("id_firstname3").onchange = function() {check_field("id_firstname3");check_submit();};
    check_field("id_firstname3");
    document.getElementById("id_lastname3").onchange = function() {check_field("id_lastname3");check_submit();};
    check_field("id_lastname3");
    document.getElementById("id_age3").onchange = function() {check_field("id_age1");check_submit();};
    check_field("id_age3");
    document.getElementById("id_weight3").onchange = function() {check_field("id_weight1");check_submit();};
    check_field("id_weight3");
    document.getElementById("id_contactphone").onchange = function() {check_field("id_contactphone");check_submit();};
    check_field("id_contactphone");
    document.getElementById("id_contactmail").onchange = function() {check_field("id_contactmail");check_submit();};
    check_field("id_contactmail");
    document.getElementById("id_flightdate").onchange = function() {check_field("id_flightdate");check_submit();};
    check_field("id_flightdate");
    check_submit();
    compute_reservation("id_typeofflight");
}
//========================================================
function check_field(theFieldId) {
    var aText=document.getElementById(theFieldId).value;
    if(aText=="") {
        document.getElementById(theFieldId).style.backgroundColor = 'LightPink';
        return false;
    }
    document.getElementById(theFieldId).style.backgroundColor = 'white';
    return true;
}
//========================================================
function check_fields() {
    var aTypeOfFlight=document.getElementById("id_typeofflight").value; 
    if(!check_field("id_typeofflight")) {
        sendErrorMessage("Vous devez choisir un type de vol!")
        return false;
    }
    if(!check_fields_passager("1")) return false;
    var nbrPassagers=document.getElementById("id_numberofpassagers").value;
    if(nbrPassagers>=2 && !check_fields_passager("2")) return false;
    if(nbrPassagers>=3 && !check_fields_passager("3")) return false;

    if(!check_field("id_contactmail")) {
        sendErrorMessage("Votre mail n'est pas introduit!")
        return false;
    }
    if(!check_field("id_contactphone")) {
        sendErrorMessage("Votre numéro de téléphone n'est pas introduit!")
        return false;
    }
    if(aTypeOfFlight=="vol_decouverte")  {
        if(!check_field("id_flightdate")) {
            sendErrorMessage("Votre date de réservation n'est pas introduite!")
            return false;
        }
    }
    sendErrorMessage("")
    return true;
}

//========================================================
function check_fields_passager(theNumber) {
    if(!check_field("id_firstname"+theNumber)) {
        sendErrorMessage("Le prénom du passager "+theNumber+" n'est pas introduit!")
        return false;
    }
    if(!check_field("id_lastname"+theNumber)) {
        sendErrorMessage("Le nom du passager "+theNumber+" n'est pas introduit!")
        return false;
    }
    if(!check_field("id_age"+theNumber)) {
        sendErrorMessage("L'âge du passager "+theNumber+" n'est pas introduit!")
        return false;
    }
    if(!check_field("id_weight"+theNumber)) {
        sendErrorMessage("Le poids du passager "+theNumber+" n'est pas introduit!")
        return false;
    }
    return true;
}

//========================================================
function sendErrorMessage(theMessage) {
    document.getElementById("id_errormessage").innerHTML  = theMessage;
}
//========================================================
function check_submit() {
    if(!check_fields()) {
        document.getElementById("id_submit").disabled=true;
        document.getElementById("id_submit").style.backgroundColor = 'LightPink';
        document.getElementById("id_pleaseWait").innerHTML  = "";
        return false;
    }
    document.getElementById("id_submit").disabled=false;
    document.getElementById("id_submit").style.backgroundColor = 'MediumSeaGreen';
	document.getElementById("id_pleaseWait").innerHTML  = "";
    return true;
}

//========================================================
function submitFunction() {
	//alert("The form was submitted. Press OK and wait.");
	//document.write("<!DOCTYPE html><html><body><p>Please wait</p></body></html>");
	document.getElementById("id_pleaseWait").innerHTML  = "Please Wait. It can take one minute.";
}

//==============================================

function compute_hideshow(theTypeOfFlight) 
{

   document.getElementById("id_form_language").style.display="none";

   if(theTypeOfFlight=="") {
     document.getElementById("id_circuit_row").style.display="none";
     document.getElementById("id_voldansles12mois_row").style.display="none";
    }

   if(theTypeOfFlight=="vol_initiation") {
       document.getElementById("id_circuit_row").style.display="none";
       document.getElementById("id_voldansles12mois_row").style.display="none";
       document.getElementById("id_date_row").style.display="none";
       document.getElementById("id_heure_row").style.display="none";
   }

   if(theTypeOfFlight=="vol_decouverte") {
       document.getElementById("id_circuit_row").style.display="";
       document.getElementById("id_voldansles12mois_row").style.display="";
       document.getElementById("id_date_row").style.display="";
       document.getElementById("id_heure_row").style.display="";
   }

   var aNumberOfPassagers=document.getElementById("id_numberofpassagers").value;

    if(aNumberOfPassagers == 1) {
       document.getElementById("id_passager2_row").style.display="none";
       document.getElementById("id_passager3_row").style.display="none";
    }

    if(aNumberOfPassagers > 1) {
       document.getElementById("id_passager2_row").style.display="";
       document.getElementById("id_passager3_row").style.display="none";
   }

    if(aNumberOfPassagers > 2) {
        document.getElementById("id_passager3_row").style.display="";
   }

}

//==============================================

function compute_reservation(val) 
{

   var aTypeOfFlightElement=document.getElementById("id_typeofflight");
   var aTypeOfFlight= aTypeOfFlightElement.value;
   if(aTypeOfFlight=="vol_initiation") {
    // Only 2 passagers for initiation flight
    document.getElementById("id_numberofpassagers").max=2;
    if(document.getElementById("id_numberofpassagers").value==3) {
        document.getElementById("id_numberofpassagers").value=2;
    }
   }
   else {
    document.getElementById("id_numberofpassagers").max=3;   
   }

   var aMembre=document.getElementById("id_voldansles12mois").value;

    compute_hideshow(aTypeOfFlight);

 

}

//===============================================

function prefillDropdownMenus(selectName, valuesArray) {

   var select = document.getElementById(selectName);

   for (var i = 0; i < valuesArray.length; i++) {
      var option = document.createElement("option");
      option.text = valuesArray[i].name ;
     //option.value = valuesArray[i].id ;

      option.value = i ;
      select.add(option) ;
   }

}

//prefillDropdownMenus('id_circuit', my_offrir_circuits) ;

window.onload=reservation_page_loaded("");

document.getElementById("id_form_language").style.display="none";

