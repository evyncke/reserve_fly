/*
   Copyright 2022-2025 Patrick Reginster (and partially Eric Vyncke)

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/
   
// JavaScript used by nodedefrais.php to manage the page 
//
function odooinscrition_page_loaded() {
  
    document.getElementById("id_prenom").onchange = function() {
        updateFields("id_prenom");
    };
    document.getElementById("id_nom").onchange = function() {
        updateFields("id_nom");
    };
   document.getElementById("id_username").onchange = function() {
        updateFields("id_username");
    };
   document.getElementById("id_password").onchange = function() {
        updateFields("id_password");
    };
   document.getElementById("id_datenaissance").onchange = function() {
        updateFields("id_datenaissance");
    };
   document.getElementById("id_email").onchange = function() {
        updateFields("id_email");
    };
   document.getElementById("id_telephone").onchange = function() {
        updateFields("id_telephone");
    };
   document.getElementById("id_adresse").onchange = function() {
        updateFields("id_adresse");
    };
   document.getElementById("id_codepostal").onchange = function() {
        updateFields("id_codepostal");
    };
   document.getElementById("id_ville").onchange = function() {
        updateFields("id_ville");
    };
   document.getElementById("id_pays").onchange = function() {
        updateFields("id_pays");
    };
   document.getElementById("id_motivation").onchange = function() {
        updateFields("id_motivation");
    };
   document.getElementById("id_typemembre").onchange = function() {
        updateFields("id_typemembre");
    };
   document.getElementById("id_qualification").onchange = function() {
        updateFields("id_qualification");
    };
   document.getElementById("id_licence").onchange = function() {
        updateFields("id_licence");
    };
   document.getElementById("id_validitemedicale").onchange = function() {
        updateFields("id_validitemedicale");
    };
   document.getElementById("id_validiteelp").onchange = function() {
        updateFields("id_validiteelp");
    };
   document.getElementById("id_courstheorique").onchange = function() {
        updateFields("id_courstheorique");
    };
   document.getElementById("id_cotisation").onchange = function() {
        updateFields("id_cotisation");
    };
   document.getElementById("id_caution").onchange = function() {
        updateFields("id_caution");
    };
   document.getElementById("id_dateinscription").onchange = function() {
        updateFields("id_dateinscription");
    };
   document.getElementById("id_factureodoo").onchange = function() {
        updateFields("id_factureodoo");
    };
   document.getElementById("id_societe").onchange = function() {
        updateFields("id_societe");
    };
   document.getElementById("id_nomsociete").onchange = function() {
        updateFields("id_nomsociete");
    };
   document.getElementById("id_bcesociete").onchange = function() {
        updateFields("id_bcesociete");
    };
   document.getElementById("id_adressesociete").onchange = function() {
        updateFields("id_adressesociete");
    };
   document.getElementById("id_codepostalsociete").onchange = function() {
        updateFields("id_codepostalsociete");
    };
   document.getElementById("id_villesociete").onchange = function() {
        updateFields("id_villesociete");
    };
   document.getElementById("id_payssociete").onchange = function() {
        updateFields("id_payssociete");
    };
   document.getElementById("id_contactnom").onchange = function() {
        updateFields("id_contactnom");
    };
   document.getElementById("id_contactlien").onchange = function() {
        updateFields("id_contactlien");
    };
   document.getElementById("id_contactphone").onchange = function() {
        updateFields("id_contactphone");
    };
   document.getElementById("id_contactmail").onchange = function() {
        updateFields("id_contactmail");
    };
   document.getElementById("id_factureodoo").onchange = function() {
        updateFields("id_contactmail");
    };
   document.getElementById("id_createmember").onclick = function() {
        generateMember();
    };

	initializeFields();
	updateFields("");
}

//===============================================
//Function: initializeFileds
//===============================================
function initializeFields() {
	const d = new Date();
	var mois=d.getMonth()+1;
	var today=d.getFullYear()+"-"+
	mois.toString().padStart(2, '0')+"-"+
	d.getDate().toString().padStart(2, '0');
	document.getElementById("id_dateinscription").value=today;
}
//==============================================
// Function: initializeForTest
// Purpose: 
//==============================================
function initializeForTest()
{
	document.getElementById("id_prenom").value="Philippe";
    document.getElementById("id_nom").value="Muller"
  	//document.getElementById("id_username").value="pdupont"
    //document.getElementById("id_password").value="Pdupont123!";
   	document.getElementById("id_datenaissance").value="1950-06-23";
   	document.getElementById("id_email").value="mistralwo@gmail.com";
   	document.getElementById("id_telephone").value="+32478482033";
    document.getElementById("id_adresse").value="Avenue du Mistral, 68";
    document.getElementById("id_codepostal").value="1200";
    document.getElementById("id_ville").value="Wolluwe Saint Lambert";
    document.getElementById("id_pays").value="Belgique";
    document.getElementById("id_motivation").value="Passionné aviation";
    document.getElementById("id_typemembre").value="nonnaviguant";
    document.getElementById("id_qualification").value=""
    document.getElementById("id_licence").value="";
    document.getElementById("id_validitemedicale").value="";
    document.getElementById("id_validiteelp").value="";
    document.getElementById("id_courstheorique").value="non"
    document.getElementById("id_cotisation").value="0";
    document.getElementById("id_caution").value="non";
    document.getElementById("id_dateinscription").value="2025-12-08";
    document.getElementById("id_factureodoo").value="non";
    document.getElementById("id_societe").value="non";
    document.getElementById("id_nomsociete").value="";
    document.getElementById("id_bcesociete").value="";
    document.getElementById("id_adressesociete").value="";
    document.getElementById("id_codepostalsociete").value="";
    document.getElementById("id_villesociete").value="";
    document.getElementById("id_payssociete").value="";
    document.getElementById("id_contactnom").value="";
    document.getElementById("id_contactlien").value="";
    document.getElementById("id_contactphone").value="";
    document.getElementById("id_contactmail").value="";
    document.getElementById("id_factureodoo").value="non";

/*
	document.getElementById("id_prenom").value="Pierre";
    document.getElementById("id_nom").value="Test"
  	//document.getElementById("id_username").value="pdupont"
    //document.getElementById("id_password").value="Pdupont123!";
   	document.getElementById("id_datenaissance").value="1964-04-10";
   	document.getElementById("id_email").value="marie.reginster@gmail.com";
   	document.getElementById("id_telephone").value="+32412345689";
    document.getElementById("id_adresse").value="Quai Mativa,69/51";
    document.getElementById("id_codepostal").value="4020";
    document.getElementById("id_ville").value="Liège";
    document.getElementById("id_pays").value="Belgique";
    document.getElementById("id_motivation").value="Pas vraiment motivé!";
    document.getElementById("id_typemembre").value="pilote";
    document.getElementById("id_qualification").value="PPL"
    document.getElementById("id_licence").value="BE123456";
    document.getElementById("id_validitemedicale").value="2026-01-01";
    document.getElementById("id_validiteelp").value="2026-01-02";
    document.getElementById("id_courstheorique").value="oui"
    document.getElementById("id_cotisation").value="270";
    document.getElementById("id_caution").value="oui";
    document.getElementById("id_dateinscription").value="2025-12-05";
    document.getElementById("id_factureodoo").value="oui";
    document.getElementById("id_societe").value="non";
    document.getElementById("id_nomsociete").value="rapcsAirlines";
    document.getElementById("id_bcesociete").value="BE987654321";
    document.getElementById("id_adressesociete").value="Bld du RAPCS,99";
    document.getElementById("id_codepostalsociete").value="4987";
    document.getElementById("id_villesociete").value="Spa";
    document.getElementById("id_payssociete").value="Wallonie";
    document.getElementById("id_contactnom").value="Gertrude Pontdu";
    document.getElementById("id_contactlien").value="Epouse";
    document.getElementById("id_contactphone").value="+324987654321";
    document.getElementById("id_contactmail").value="gertrude.pontdu@gmail.com";
    document.getElementById("id_factureodoo").value="oui";
*/
}

//===============================================
//Function: updateFields
//===============================================
function updateFields(theField) {
	if(theField=="id_prenom" && document.getElementById("id_prenom").value=="test") {
		initializeForTest();
	}

	if(theField=="id_prenom" || theField=="id_nom") {
		var prenom=document.getElementById("id_prenom").value;
		var nom=document.getElementById("id_nom").value;
		if(nom!="" && prenom!="") {
			var username=prenom.substring(0,1)+nom;
			username=username.toLowerCase();
			document.getElementById("id_username").value=username;
			//document.getElementById("id_password").value=username.substring(0,1).toUpperCase()+username.substring(1)+"123!";
			document.getElementById("id_password").value="Rapcs123!";
		}
	}



	document.getElementById("id_createmember").disabled=true;
	if(checkFields()){
		document.getElementById("id_createmember").disabled=false;
	}
}

//===============================================
//Function: checkFields
//===============================================
function checkFields() {
	var value;
	value=document.getElementById("id_prenom").value;
    if(value=="") return false;

    value=document.getElementById("id_nom").value;
    if(value=="") return false;

  	value=document.getElementById("id_username").value;
    if(value=="") return false;

   	value=document.getElementById("id_password").value;
    if(value=="") return false;

   	value=document.getElementById("id_datenaissance").value;
    if(value=="") return false;

   	value=document.getElementById("id_email").value;
    if(value=="") return false;

   	value=document.getElementById("id_telephone").value;
    if(value=="") return false;

    value=document.getElementById("id_adresse").value;
    if(value=="") return false;

    value=document.getElementById("id_codepostal").value;
    if(value=="") return false;

    value=document.getElementById("id_ville").value;
    if(value=="") return false;

    value=document.getElementById("id_pays").value;
    if(value=="") return false;
  
    value=document.getElementById("id_motivation").value;
    if(value=="") return false;

    value=document.getElementById("id_typemembre").value;
    if(value=="") return false;
	var typemembre=value;
	if(typemembre=="pilote") {
		document.getElementById("id_qualification_row").style.display="";
		value=document.getElementById("id_qualification").style.value;
    	if(value=="") return false;

 		document.getElementById("id_licence_row").style.display="";
 		value=document.getElementById("id_licence").value;
     	if(value=="") return false;

 		document.getElementById("id_validitemedicale_row").style.display="";
   		value=document.getElementById("id_validitemedicale").value;
    	if(value=="") return false;

 		document.getElementById("id_validiteelp_row").style.display="";
   		value=document.getElementById("id_validiteelp").value;
    	if(value=="") return false;

 		document.getElementById("id_courstheorique_row").style.display="";
    	value=document.getElementById("id_courstheorique").value;
    	if(value=="") return false;
	}
	else {
		document.getElementById("id_qualification_row").style.display="none";
		document.getElementById("id_licence_row").style.display="none";
 		document.getElementById("id_validitemedicale_row").style.display="none";
		document.getElementById("id_validiteelp_row").style.display="none";
 		document.getElementById("id_courstheorique_row").style.display="none";
	}

    value=document.getElementById("id_cotisation").value;
    if(value=="") return false;

 	if(typemembre!="nonnaviguant") {
		document.getElementById("id_caution_row").style.display="none";
		value=document.getElementById("id_caution").value;
    	if(value=="") return false;
	}
	else {
		document.getElementById("id_caution_row").style.display="none";
	}

    value=document.getElementById("id_dateinscription").value;
    if(value=="") return false;

   	value=document.getElementById("id_factureodoo").value;
    if(value=="") return false;

   	value=document.getElementById("id_societe").value;
    if(value=="") return false;
	var societe=value;
	if(societe=="oui") {
		document.getElementById("id_nomsociete_row").style.display="";
   		value=document.getElementById("id_nomsociete").value;
     	if(value=="") return false;

		document.getElementById("id_bcesociete_row").style.display="";
   		value=document.getElementById("id_bcesociete").value;
     	if(value=="") return false;

  		document.getElementById("id_adressesociete_row").style.display="";
  		value=document.getElementById("id_adressesociete").value;
     	if(value=="") return false;

		document.getElementById("id_codepostalsociete_row").style.display="";
   		value=document.getElementById("id_codepostalsociete").value;
     	if(value=="") return false;

  		document.getElementById("id_villesociete_row").style.display="";
  		value=document.getElementById("id_villesociete").value;
    	if(value=="") return false;

 	    document.getElementById("id_payssociete_row").style.display="";
  		value=document.getElementById("id_payssociete").value;
     	if(value=="") return false;
		}
	else {
		document.getElementById("id_nomsociete_row").style.display="none";
		document.getElementById("id_bcesociete_row").style.display="none";
 		document.getElementById("id_adressesociete_row").style.display="none";
		document.getElementById("id_codepostalsociete_row").style.display="none";
 		document.getElementById("id_villesociete_row").style.display="none";
	    document.getElementById("id_payssociete_row").style.display="none";
	}

	if(0) {
   		value=document.getElementById("id_contactnom").value;
     	if(value=="") return false;

   		value=document.getElementById("id_contactlien").value;
     	if(value=="") return false;

  		value=document.getElementById("id_contactphone").value;
     	if(value=="") return false;

   		value=document.getElementById("id_contactmail").value;
    	if(value=="") return false;
	}
	value=document.getElementById("id_factureodoo").value;
    if(value=="none") return false;

	return true;
}

//===============================================
//Function: generateMember
//===============================================
function generateMember() {
		if (confirm("Confirmez que vous voulez introduire un nouveau membre.?") == true) {			
   		 	var aCommand=PHP_Self+"?action=create";	
   		 	window.location.href = encodeURI(aCommand);
	}
	return true;
}
//===============================================
// Main
var ReadOnlyColor="AliceBlue";



function odooInscriptionMain() {
    odooinscrition_page_loaded();
}
// Add onclick event to checkboxes after DOM loaded
// Could possible be included (or include) the above code
document.addEventListener("DOMContentLoaded", function () {

});
