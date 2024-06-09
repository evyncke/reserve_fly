
function reservation_page_loaded() {

    document.getElementById("id_pleaseWait").innerHTML  = "";
    document.getElementById("id_submit").onclick = function() {submitFunction()};
    //document.getElementById("id_submit").onclick = function() {alert("The form was submitted - Please wait")};

    document.getElementById("id_numberofpassagers").oninput = function() {compute_reservation("id_numberofpassagers")};

    document.getElementById("id_firstname1").onchange = function() {check_field("id_firstname1");check_submit();};
    check_field("id_firstname1");
    document.getElementById("id_lastname1").onchange = function() {check_field("id_lastname1");check_submit();};
    check_field("id_lastname1");
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
        document.getElementById(theFieldId).style.backgroundColor = 'orange';
        return false;
    }
    document.getElementById(theFieldId).style.backgroundColor = 'white';
    return true;
}
//========================================================
function check_fields() {
    if(!check_field("id_firstname1")) return false;
    if(!check_field("id_lastname1")) return false;
    if(!check_field("id_contactphone")) return false;
    if(!check_field("id_contactmail")) return false;
    var aTypeOfFlight=document.getElementById("id_typeofflight").value; 
    if(aTypeOfFlight=="vol_decouverte")  {
        if(!check_field("id_flightdate")) return false;
    }
    return true;
}
//========================================================
function check_submit() {
    if(!check_fields()) {
        document.getElementById("id_submit").disabled=true;
        document.getElementById("id_pleaseWait").innerHTML  = "";
        return false;
    }
    document.getElementById("id_submit").disabled=false;
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

