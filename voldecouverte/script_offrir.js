

function offrir_page_loaded() {
    document.getElementById("id_pleaseWait").innerHTML  = "";
    document.getElementById("id_submit").onclick = function() {submitFunction()};

 	document.getElementById("id_valeur_virement").style.display="none";

	document.getElementById("id_valeur_bon").onchange = function() {compute_tarifs("id_valeur_bon")};

	document.getElementById("id_numberofpassagers").oninput = function() {compute_tarifs("id_numberofpassagers")};

	document.getElementById("id_valeur_bon_libre").oninput = function() {compute_tarifs("id_valeur_bon_libre")};

	document.getElementById("id_valeur_bon_libre").onchange = function() {compute_tarifs("id_valeur_bon_libre")};

 compute_tarifs("id_typeofgift");

}
//========================================================
function submitFunction() {
	//alert("The form was submitted. Press OK and wait.");
	//document.write("<!DOCTYPE html><html><body><p>Please wait</p></body></html>");
	    document.getElementById("id_pleaseWait").innerHTML  = "Please Wait. It can take one minute.";
}

//========================================================

function retrieve_tarif(theTypeOfGift, theNumberOfPassenger)  {
    for (var i = 0;  i < my_tarifs.length; i++) {
      var aType=  my_tarifs[i].type ; 
      var aNumberOfPassenger =  my_tarifs[i].passenger ;
      if(aType==theTypeOfGift && theNumberOfPassenger == aNumberOfPassenger) {
          return my_tarifs[i].tarif ; 
      }
    }
  return 0;
}

//==============================================

function offrir_page_after_loaded() {
   var x = document.getElementById("id_typeofgift_vol_initiation");
   alert("Page loaded !");
   document.getElementById("id_firstname1").value = "form is loaded 5";
}

//=============================================

function myPrint(val) 
{

//var anElement=document.getElementById("id_valeur_virement");

//anElement.value=111;

//var x=document.getElementById(val).value;
//document.getElementById("id_valeur_virement1").innerHTML = "Paragraph changed: "+x;
}

//==============================================

function compute_hideshow(theTypeOfGift) 
{

   if(theTypeOfGift=="") {

     document.getElementById("id_numberofpassagers_row").style.display="none";

     document.getElementById("id_circuit_row").style.display="none";

     document.getElementById('id_valeur_bon_row').style.display="none";

     document.getElementById("id_voldansles12mois_row").style.display="none";

     document.getElementById('id_valeur_virement_row').style.display="none";

    }

   if(theTypeOfGift=="vol_initiation") {

       document.getElementById("id_numberofpassagers_row").style.display="";

       //document.getElementById("id_numberofpassagers").style.display="";

      document.getElementById("id_circuit_row").style.display="none";

       document.getElementById('id_valeur_bon_row').style.display="none";

       //document.getElementById("id_valeur_bon_libre").style.display="none";

       document.getElementById("id_voldansles12mois_row").style.display="none";

      document.getElementById('id_valeur_virement_row').style.display="";

   }

   if(theTypeOfGift=="vol_decouverte") {

       document.getElementById("id_numberofpassagers_row").style.display="";

       //document.getElementById("id_numberofpassagers").style.display="";

       document.getElementById("id_circuit_row").style.display="";

       document.getElementById('id_valeur_bon_row').style.display="none";

      // document.getElementById("id_valeur_bon_libre").style.display="none";

       document.getElementById("id_voldansles12mois_row").style.display="";

      document.getElementById('id_valeur_virement_row').style.display="";

   }

   if(theTypeOfGift=="bon_valeur") {

       document.getElementById("id_numberofpassagers_row").style.display="none";

       //document.getElementById("id_numberofpassagers").style.display="none";

       document.getElementById("id_circuit_row").style.display="none";

       document.getElementById('id_valeur_bon_row').style.display="";

       //document.getElementById("id_valeur_bon_libre").style.display="" ;

       document.getElementById("id_voldansles12mois_row").style.display="";

      document.getElementById('id_valeur_virement_row').style.display="";

   }

}

//==============================================

function compute_tarifs(val) 
{

   var aVirement=document.getElementById("id_valeur_virement");

   var aTypeOfGiftElement=document.getElementById("id_typeofgift");

    var aTypeOfGift = aTypeOfGiftElement.value;

   var aNumberOfPassagers=document.getElementById("id_numberofpassagers").value;

   var aMembre=document.getElementById("id_voldansles12mois").value;

   var aTarif=0.;

   if(val=="id_typeofgift") {

       compute_hideshow(aTypeOfGift);

   }

   if(aTypeOfGift == "vol_initiation") {
      aTarif=215;
      aTarif=retrieve_tarif("initiation", aNumberOfPassagers);

      //if(aNumberOfPassagers==2) aTarif=255;

      //if(aNumberOfPassagers==3) aTarif=265;

   }

   if(aTypeOfGift == "vol_decouverte")  {

      var aCircuitElement=document.getElementById("id_circuit");

      var aCircuit=aCircuitElement.value;

      if(aCircuit!="") {

          //var aMinute=my_offrir_circuits[aCircuit].tarif;
          var aMinute=0;
		  var aCurrentID=Number(aCircuit)+1;
		   for (var i = 0; i < my_offrir_circuits.length; i++) {
			   var aID=Number(my_offrir_circuits[i].id);
			   if(aID==aCurrentID) {
			   		aMinute=my_offrir_circuits[i].tarif;
					break;
			   }
		   }
            //aTarif=aMinute*2.5;

            if(aMembre=="non") {

               aTarif=retrieve_tarif("decouverte", aNumberOfPassagers);

               aTarif=aMinute*aTarif;

            }

            else {

              //if(aNumberOfPassagers>1) aTarif=aMinute*3.80;

               aTarif=retrieve_tarif("membre", aNumberOfPassagers);

               aTarif=aMinute*aTarif;
			   //var aMembreTarif=retrieve_tarif("cotisation", 1);
               //aTarif=aTarif+aMembreTarif;
           }
		   // Round the tarif to  5 euros
		   aTarif= Math.round(aTarif/5)*5;
      }

  }

  if(aTypeOfGift == "bon_valeur") {

      var aValeurBon=document.getElementById('id_valeur_bon').value;

      if(aValeurBon!="") {

         aTarif=my_offrir_bons[aValeurBon].tarif;

         if(aTarif==0) {

            aTarif=document.getElementById('id_valeur_bon_libre').value;

            document.getElementById('id_valeur_bon_libre').readonly=false;

         }

         else {

             document.getElementById('id_valeur_bon_libre').value=aTarif;

            document.getElementById('id_valeur_bon_libre').readonly=true;

        }

      }

   }

   aVirement.value=aTarif.toString();

  document.getElementById('id_valeur_virement1').innerHTML ="   "+ aTarif.toString() +" €";

//document.getElementById("id_valeur_virement1").innerHTML = "Paragraph changed: "+x;
}

//===============================================

function prefillDropdownMenus(selectName, valuesArray, idName) {

   var select = document.getElementById(selectName);

   for (var i = 0; i < valuesArray.length; i++) {
      var option = document.createElement("option");
      option.text = valuesArray[i].name ;
      //option.value = valuesArray[i].id ;
	  if(idName=='') {
      	 option.value = i ;
  	  }
	  else {
       	 option.value = valuesArray[i].id-1 ; 	
	  }
      select.add(option) ;
   }
}

prefillDropdownMenus('id_valeur_bon', my_offrir_bons, '') ;

prefillDropdownMenus('id_circuit', my_offrir_circuits, 'id') ;

window.onload=offrir_page_loaded();

document.getElementById("id_form_language").style.display="none";

