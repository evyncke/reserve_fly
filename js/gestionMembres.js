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
   
//.normalize('NFD').replace(/([\u0300-\u036f]|[^0-9a-zA-Z])/g, '');
//        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
function parseFloatEU(s) {
	if (s == '') return 0 ;
	return parseFloat(s.replace(/\./g, "").replace(/\,/g, ".")) ;
}

// Based on https://www.w3schools.com/howto/howto_js_sort_table.asp
function sortTable(n, isNumeric) {
	if(columnSort!=n) {
		dirSort="asc";
		columnSort=n;
	}
	var table, rows, i, k, x, y;
  	table = document.getElementById("myTable");
  	rows = table.rows;
  	const keyMap = new Map();
  	for (i = 0; i < (rows.length - 1); i++) {
		//Fill the Map to sort
    	x = rows[i].getElementsByTagName("TD")[n];
		var xText=x.innerText;
		if (isNumeric) {
			// add (i*0.0001 if 2 times the same key
			keyMap.set(parseFloatEU(xText)+(i*0.0001), i);
		} 
		else {
			// add _i if 2 times the same key
			keyMap.set(xText+"_"+i, i);
		}
	}
	var sorted;
	if(isNumeric) {
		if(dirSort=="asc") {
		 	sorted = [...keyMap].sort((a, b) => a[0] - b[0]);
		}
		else {
			sorted = [...keyMap].sort((a, b) => b[0] - a[0]);
		}
	}
	else {
		if(dirSort=="asc") {
		 	sorted = [...keyMap].sort();
		}
		else {
			sorted = [...keyMap].sort((a, b) => b[0].localeCompare(a[0]));			
		}
	}
	
	const sortedMap = new Map(sorted);
	let keys = Array.from(sortedMap.keys());
	i=0;
	for (let [key, value] of sortedMap) {
		if(value!=i) {
			//console.log("sortTable value="+value);
			rows[i].parentNode.insertBefore(rows[value], rows[i]);
			var aLen=keys.length;
			for (k = i+1; k < aLen; k++) {
				var aKey=keys[k];
				var aValue=sortedMap.get(aKey);
				if(aValue<value) {
					sortedMap.set(aKey,aValue+1);
					if(aValue+1>aLen-1) {
						console.log("sortTable value="+value+";aValue="+aValue);
					}
				}
			}
		}
		i++;
	}
	if(dirSort == "asc") {
		dirSort="desc";
	}
	else {
		dirSort="asc";
	}
}

function blockFunction(PHP_Self, theBlockedFlag, theNom, theUserId, theSolde)
{
	var aSearchText=document.getElementById("id_SearchInput").value;
	var aReason="";
	if(theBlockedFlag=="Block") {
		aReason=getReason(theSolde);
		if (confirm("Confirmer que vous voulez bloquer " + theNom + "?\nRaison: "+aReason) == true) {			
   		 	var aCommand=PHP_Self+"?block=true&personid="+theUserId+"&reason="+aReason;	
			if(aSearchText!="")	 {
				aCommand+="&search="+aSearchText;
			}
   		 	window.location.href = encodeURI(aCommand);
		}
	}
	else {
		if (confirm("Confirmer que vous voulez débloquer " + theNom + "?") == true) {
      		var aCommand=PHP_Self+"?unblock=true&personid="+theUserId;
 			if(aSearchText!="")	 {
 				aCommand+="&search="+aSearchText;
 			}
      		 window.location.href = aCommand;
		}		
	}
}

function getReason(theSolde)
{
	var aPredefinedReason="Votre solde est actuellement négatif pour un montant de ("+theSolde+" EUR). Merci de régulariser rapidement. Vos réservations seront débloquées une fois le paiement enregistré dans la comptabilité.";
	if(theSolde=="") {
		aPredefinedReason="Votre solde est actuellement négatif. Merci de régulariser rapidement. Vos réservations seront débloquées une fois le paiement enregistré dans la comptabilité.";
	}
	if(theSolde==-70 || theSolde==-255){
		aPredefinedReason="Vous n'êtes pas en ordre de cotisation. Vous êtes donc interdit(e)s de réservation tant que votre cotisation n'est pas réglée.";
	}
	var reason = prompt("Entrer la raison du blocage", aPredefinedReason);
	return reason;
}

function filterSelected()
{
	for(i=1;i<=9;i++){
	    var aToggleComponentId="id_FilterRows"+i.toString();
		var blockedToggle = document.getElementById(aToggleComponentId);
		blockedToggle.checked=false;
	}
	var aToggle = document.getElementById("id_FilterSelected");
	var aCheckedValue=aToggle.checked;
    var table = document.getElementById("myTable");
    var rows = table.rows;
	var aSelectToggleColumn=0;
   	for (i = 0; i < rows.length; i++) {
        var row = rows[i];
		if(!aCheckedValue) {
   		  row.hidden=false;
		  continue;
		}
		var aColumn1Row = row.getElementsByTagName("TD")[aSelectToggleColumn];
		var aSelectedToggle = aColumn1Row.childNodes[0];
		if(aSelectedToggle.checked) {
     		  row.hidden=false;
		}
		else {
   		  row.hidden=true;			
		}
	}
}

function submitSelect(theSelect)
{
    var table = document.getElementById("myTable");
    var rows = table.rows;
	var aSelectToggleColumn=0;
   	for (i = 0; i < rows.length; i++) {
		var row = rows[i];
		var aColumn1Row = row.getElementsByTagName("TD")[aSelectToggleColumn];
		var aSelectedToggle = aColumn1Row.childNodes[0];
		if(theSelect=="SelectVisible") {
			// Select all visible rows
			if(row.hidden) {
				aSelectedToggle.checked=false;
			}
			else {
				aSelectedToggle.checked=true;
			}
		}
		else {
			// Unselect all rows
			aSelectedToggle.checked=false;
		}
	}
}

function filterRows(count, blocked, sign)
{
	// Untoggle other checkboxs
	var blockedToggle = document.getElementById("id_FilterSelected");
	blockedToggle.checked=false;
	for(i=1;i<=9;i++){
		if(i!=count) {
	    	var aToggleComponentId="id_FilterRows"+i.toString();
			blockedToggle = document.getElementById(aToggleComponentId);
			blockedToggle.checked=false;
		}
	}
    var aNegativeValueComponentId="id_FilterRows"+count.toString();
	var blockedToggle = document.getElementById(aNegativeValueComponentId);
	var aCheckedValue=blockedToggle.checked;
    var table = document.getElementById("myTable");
    var rows = table.rows;
	var aSelectToggleColumn=0;
	var aStatusColumn=16;
	var aNonNaviguantColumn=10;
	var aEleveColumn=11;
	var aPiloteColumn=12;
	var aEffectifColumn=13;
	var aValueColumn=15;
  	var aCotisationColumn=14;
	var aOdooColumn=2;
	var aHidden=false;
   	for (i = 0; i < rows.length-1; i++) {
        var row = rows[i];
		if(!aCheckedValue) {
   		  row.hidden=false;
		  continue;
		}
		var aColumn1Row = row.getElementsByTagName("TD")[aSelectToggleColumn];
		var aSelectedToggle = aColumn1Row.childNodes[0];
		
		var aOdoo = row.getElementsByTagName("TD")[aOdooColumn].textContent;
		if(aOdoo.indexOf("xxxx")!=-1) {
     		  row.hidden=true;	
			  continue;
		}
		// Display rows without cotisation
		if(blocked=="membre") {
			if(sign=="sanscotisation") {
				var aCotisation=row.getElementsByTagName("TD")[aCotisationColumn].textContent;
				var aPos=aCotisation.search("[?]");
				if(aPos!=-1) {
					row.hidden=false;	
					continue;
				}
				else {
					row.hidden=true;	
					continue;
				}
			}
			else if(sign=="nonnaviguant") {
				// TODO rather than check for non empty, check for specific value, i.e., 'checked'
				var aCotisation=row.getElementsByTagName("TD")[aNonNaviguantColumn].innerHTML;
				if(aCotisation!="") {
					row.hidden=false;	
					continue;
				}
				else {
					row.hidden=true;	
					continue;
				}
			}
			else if(sign=="eleve") {
				var aMemberType=row.getElementsByTagName("TD")[aEleveColumn].innerHTML;
				if(aMemberType!="") {
					row.hidden=false;	
					continue;
				}
				else {
					row.hidden=true;	
					continue;
				}
			}
			else if(sign=="pilote") {
				var aMemberType=row.getElementsByTagName("TD")[aPiloteColumn].innerHTML;
				if(aMemberType!="") {
					row.hidden=false;	
					continue;
				}
				else {
					row.hidden=true;	
					continue;
				}
			}
			else if(sign=="effectif") {
				var aMemberType=row.getElementsByTagName("TD")[aEffectifColumn].innerHTML;
				if(aMemberType!="") {
					row.hidden=false;	
					continue;
				}
				else {
					row.hidden=true;	
					continue;
				}
			}
			else {
				alert("ERROR: unknown action "+sign);
				return;
			}
		}
	   	var aStatus=row.getElementsByTagName("TD")[aStatusColumn].textContent;
		var aBlockedRow=!(aStatus.indexOf("DEBLOQUER")==-1);		
		if(aStatus.indexOf("BLOQUER")==-1) {
			// Deactivated row
   		 	row.hidden=true;
			//aSelectedToggle.checked=false;
		 	continue;
		}
		if(blocked=="Blocked") {
			if(!aBlockedRow || aStatus=="") {
	   			row.hidden=true;
	  			//aSelectedToggle.checked=false;
			  continue;
			}
		} 
		else if(blocked=="NotBlocked") {
			if(aBlockedRow || aStatus=="") {
	   		  	row.hidden=true;
  				//aSelectedToggle.checked=false;
			  continue;
			}
		}
		 
   		var aValueText=row.getElementsByTagName("TD")[aValueColumn].textContent;	
		var aNegativeValue=(aValueText.indexOf("-")==0);
		if(sign=="<") {
			if(!aNegativeValue) {
		 		row.hidden=true;
	  			//aSelectedToggle.checked=false;
 				continue;
			}
		}
		else if(sign==">") {
			if(aNegativeValue) {
		 		row.hidden=true;
	  			//aSelectedToggle.checked=false;
 				continue;
			}
		}
 		row.hidden=false;
		//aSelectedToggle.checked=true;
 	}
}

function submitBlocked(PHP_Self, blocked) {
	var aSearchText=document.getElementById("id_SearchInput").value;
    var table = document.getElementById("myTable");
    var rows = table.rows;
	var aSelectToggleColumn=0;
	var aListOfId="";
	var aCount=0;
   	for (i = 0; i < rows.length; i++) {
        var row = rows[i];
		if(!row.hidden) {
			var aColumn1Row = row.getElementsByTagName("TD")[aSelectToggleColumn];
			var aSelectedToggle = aColumn1Row.childNodes[0];
			if(aSelectedToggle.checked) {
				aCount++;
				var aValueText=row.getElementsByTagName("TD")[1].textContent;
				if(aListOfId!="") {
					aListOfId+=",";
				}
				aListOfId+=aValueText;
				aColumn1Row.style.backgroundColor="orange";
			}
			else {
				aColumn1Row.style.backgroundColor="white";
			}
		}
	}
	if(aCount==0) {
		alert("Pour Bloquer ou Débloquer, vous devez d'abord selectionner des lignes dans la table!");
		return;
	}
	var aReason="";
	if(blocked=="Block") {
		aReason=getReason("");
		if (confirm("Confirmer que vous voulez bloquer " + aCount.toString() +" personne(s)" + "?\nRaison: "+aReason) == true) {			
   		 	var aCommand=PHP_Self+"?block=true&listpersonid="+aListOfId+"&reason="+aReason;	
			if(aSearchText!="")	 {
				aCommand+="&search="+aSearchText;
			}
   		 	window.location.href = encodeURI(aCommand);
		}
	}
	else {
		if (confirm("Confirmer que vous voulez débloquer "+ aCount.toString() +" personne(s)" + "?") == true) {
      		var aCommand=PHP_Self+"?unblock=true&listpersonid="+aListOfId+"&reason="+aReason;
 			if(aSearchText!="")	 {
 				aCommand+="&search="+aSearchText;
 			}
      		 window.location.href = aCommand;
		}		
	}
}
function submitDownloadMail(PHP_Self, action) {
	var table = document.getElementById("myTable");
    var rows = table.rows;
	var aSelectToggleColumn=0;
	var aMailColumn=9;
	var aListOfMails="";
	var aCount=0;
   	for (i = 0; i < rows.length-1; i++) {
        var row = rows[i];
		if(!row.hidden) {
			var aColumn1Row = row.getElementsByTagName("TD")[aSelectToggleColumn];
			var aSelectedToggle = aColumn1Row.childNodes[0];
			if(aSelectedToggle.checked) {
				aCount++;
				var aValueText=row.getElementsByTagName("TD")[aMailColumn].textContent;
				if(aListOfMails!="") {
					aListOfMails+=",";
				}
				aListOfMails+=aValueText;
			}
		}
	}
	if(aCount==0) {
		alert("Pour copier des mails, vous devez d'abord selectionner des lignes dans la table!");
		return;
	}
	if(action=="CopyMail") {
		navigator.clipboard.writeText(aListOfMails);
		alert(aCount+" adresses mails sont copiées dans le clipboard. Utiliser le Paste (Cmd+V) pour le copier dans un document !");
	}
}

function createCotisationFunction(PHP_Self,action,theName,thePersonid,isMember) {
	var aSearchText=document.getElementById("id_SearchInput").value;

	if (isMember) {
		aCotisationValue=70.0;
		aCotisationTypeString="membre non naviguant";
		aCotisationType="nonnaviguant";
	} else {
		aCotisationValue=270.0;
		aCotisationTypeString="membre naviguant";
		aCotisationType="naviguant";
	}
	// After 1 July: Proportional to the numer of mounth
	aDate= new Date();
	aMonth=aDate.getMonth()+1;
	if(aMonth>6){
		aCotisationValue=aCotisationValue*(12-aMonth)/12.0;
	}
	if (confirm("Confirmer que vous voulez créer une facture de cotisation " + aCotisationTypeString + " de " + aCotisationValue.toString() + " € à " + theName + " (id="+thePersonid+")?") == true) {
      		var aCommand=PHP_Self+"?createcotisation=true&personid="+thePersonid+"&cotisationtype="+aCotisationType;
 			if(aSearchText!="")	 {
 				aCommand+="&search="+aSearchText;
 			}
      		 window.location.href = aCommand;
	}
}

var dirSort="asc";
var columnSort=-1;
// Manage Search when keyup

// Manage Search when document loaded
$(document).ready(function() {
   $("#id_SearchInput").on("keyup", function() {
      var value = $(this).val().toLowerCase();
      $("#myTable tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().normalize('NFD').replace(/([\u0300-\u036f]|[^0-9a-zA-Z])/g, '').indexOf(value) > -1)
     });
    });
    var value = $("#id_SearchInput").val().toLowerCase();
      $("#myTable tr").filter(function() {
      $(this).toggle($(this).text().toLowerCase().normalize('NFD').replace(/([\u0300-\u036f]|[^0-9a-zA-Z])/g, '').indexOf(value) > -1)
      });
});

// Add onclick event to checkboxes after DOM loaded
// Could possible be included (or include) the above code
document.addEventListener("DOMContentLoaded", function () {
    // Select all checkboxes with an id starting with "check-"
    const checkboxes = document.querySelectorAll('input[type="checkbox"][id^="check-"]');

    // Add the onclick event to each checkbox whose id starts with "check-"
    checkboxes.forEach(function (checkbox) {
        checkbox.onclick = function () {
			var values=this.id.split("-");
			if(confirm("Confirmez que vous voulez changer le statut \""+values[2]+"\" de ce membre!"+
				"\nRappel: le membre doit au moins être non-naviguant ou élève ou pilote."+
				"\nNe pas oublier de prévenir les personnes responsables et de mettre à jour le fichier membres.xls sur OneDribe.")) {
            	// Redirect to the same URL with the checkbox ID in the query string
            	window.location.href = window.location.pathname + '?checkboxId=' + this.id + '&checked=' + this.checked;
			}
			else {
				var checked=this.checked;
				if(checked){
					this.checked=false;
				}
				else {
					this.checked=true;
				}
			}
        };
    });
});