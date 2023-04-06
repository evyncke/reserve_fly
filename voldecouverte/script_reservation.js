
function reservation_page_loaded() {

    document.getElementById("id_pleaseWait").innerHTML  = "";
    document.getElementById("id_submit").onclick = function() {submitFunction()};
    //document.getElementById("id_submit").onclick = function() {alert("The form was submitted - Please wait")};

    document.getElementById("id_numberofpassagers").oninput = function() {compute_reservation("id_numberofpassagers")};

     compute_reservation("id_typeoffligth");

}

//========================================================
function submitFunction() {
	//alert("The form was submitted. Press OK and wait.");
	//document.write("<!DOCTYPE html><html><body><p>Please wait</p></body></html>");
	    document.getElementById("id_pleaseWait").innerHTML  = "Please Wait. It can take one minute.";
}

//==============================================

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

   }

   if(theTypeOfFlight=="vol_decouverte") {

        document.getElementById("id_circuit_row").style.display="";

       document.getElementById("id_voldansles12mois_row").style.display="";

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

