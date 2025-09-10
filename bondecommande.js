// JavaScript used by nodedefrais.php to manage the page 
//
function bondecommande_page_loaded() {

  document.getElementById("id_bondecommande_input_total").readOnly=true;;
  document.getElementById("id_bondecommande_input_total").style.backgroundColor = ReadOnlyColor;
  
    document.getElementById("id_member_name").onchange = function() {
        updateSubmitButton();
    };
    document.getElementById("id_bondecommande_input_date").onchange = function() {
	    changeDate();
        updatebondecommandeToJSON();
        updateSubmitButton();
    };
    document.getElementById("id_bondecommande_input_type").onchange = function() {
	    changeType();
        updateTotal();
        updatebondecommandeToJSON();
        updateSubmitButton();
    };

    document.getElementById("id_bondecommande_input_reference").readOnly=true;;
    document.getElementById("id_bondecommande_input_reference").style.backgroundColor = ReadOnlyColor;

    document.getElementById("id_bondecommande_input_quantity").onchange = function() {
	    changeQuantity();
        updateTotal();
        updatebondecommandeToJSON();
        updateSubmitButton();
    };

    document.getElementById("id_bondecommande_input_unitaryprice").readOnly=true;;
    document.getElementById("id_bondecommande_input_unitaryprice").style.backgroundColor = ReadOnlyColor;


   document.getElementById("id_add_row").onclick = function() {
		initNewRow();
        updatebondecommandeToJSON();
        updateSubmitButton();
    };

    document.getElementById("id_bondecommande_rowinput").style.display="none";
    document.getElementById("id_submit_bondecommande").disabled=true;

  //document.getElementById("id_cdv_compteur_duree").inputMode="decimal"; 
  //document.getElementById("id_cdv_compteur_duree").readOnly=true;
  //document.getElementById("id_cdv_compteur_duree").style.backgroundColor = ReadOnlyColor;

}

//==============================================
// Function: updateSubmitButton
// Purpose: 
//==============================================
function updateSubmitButton()
{
    document.getElementById("id_submit_bondecommande").disabled=true;
    // Check Member
    if(document.getElementById("id_member_name").value=="") {
        return;
    }

    if(!checkLastInput()) {
        document.getElementById("id_add_row").disabled=true;
        document.getElementById("id_add_row").style.display="none";
        return;
    }
    else {
        document.getElementById("id_add_row").disabled=false;       
        document.getElementById("id_add_row").style.display="";
    }
    if(nodedefrais_size==0) {
        return;
    }
 
    document.getElementById("id_submit_bondecommande").disabled=false;
}
//==============================================
// Function: deletebondecommandeLine
// Purpose: 
//==============================================
function deletebondecommandeLine(theRowIndex)
{
    var aLineIndex=-1;
    if(theRowIndex==-1) {
        //Delete sur la row INPUT: hide + activate "Create new line"
        document.getElementById("id_bondecommande_rowinput").style.display="none";
        document.getElementById("id_add_row").disabled=false;       
        document.getElementById("id_add_row").style.display="";
        aLineIndex=nodedefrais_size-1;
    }
    else {
        for(var i=0; i<nodedefrais_size; i++) {
            if(bondecommande_index[i] == theRowIndex) {
                aLineIndex=i;
                break;
            }
        }
        if(aLineIndex>=0) {
            // Hide input row
            var table = document.getElementById("id_table_bondecommande");
            var rows = table.rows;
            var row = rows[theRowIndex+1];
            row.hidden=true;
        }
    }
    if(aLineIndex>=0) {
        // delete a table row
        bondecommande_date.splice(aLineIndex, 1);
        bondecommande_type.splice(aLineIndex, 1);
        bondecommande_reference.splice(aLineIndex, 1);
        bondecommande_unitary.splice(aLineIndex, 1);
        bondecommande_quantity.splice(aLineIndex, 1);
        bondecommande_total.splice(aLineIndex, 1);
        bondecommande_index.splice(aLineIndex, 1);
        nodedefrais_size--;
    }
    updateGrandTotal();
    updateSubmitButton();
}
//==============================================
// Function: deletebondecommandeFiles
// Purpose: 
//==============================================

function deletebondecommandeFiles(PHP_Self, theBondecommande)
{
	if (confirm("Confirmer que vous voulez supprimer les fichiers associés au bon de commande " + theBondecommande + "?") == true) {			
   		 	var aCommand=PHP_Self+"?delete="+theBondecommande;	
   		 	window.location.href = encodeURI(aCommand);
	}
}
//==============================================
// Function: checkLastInput
// Purpose: 
//==============================================
function checkLastInput()
{
    if(document.getElementById("id_bondecommande_rowinput").style.display!="none") {
        // Check Type
        if(document.getElementById("id_bondecommande_input_type").value=="") {
            return false;
        }
        // Check Reference
        if(document.getElementById("id_bondecommande_input_reference").value=="") {
            return false;
        }
        // Check Montant
        var aValue=document.getElementById("id_bondecommande_input_total").value;
        if(document.getElementById("id_bondecommande_input_total").value=="0" ||
            document.getElementById("id_bondecommande_input_total").value=="0.00") {
            return false;
        }
    }
    return true;
}

//==============================================
// Function: changeDate
// Purpose: 
//==============================================
function changeDate()
{
    var aDate=document.getElementById("id_bondecommande_input_date").value;
    bondecommande_date[nodedefrais_size-1]=aDate;
}

//==============================================
// Function: changeType
// Purpose: 
//==============================================
function changeType()
{
    var aType=document.getElementById("id_bondecommande_input_type").value;
    bondecommande_type[nodedefrais_size-1]=aType;

    var aReference=bondecommandeValueFromType(aType,"reference");
    document.getElementById("id_bondecommande_input_reference").value=aReference;
    bondecommande_reference[nodedefrais_size-1]=aReference;

    var aQuantity=bondecommandeValueFromType(aType,"quantity");
    document.getElementById("id_bondecommande_input_quantity").value=aQuantity;
    bondecommande_quantity[nodedefrais_size-1]=aQuantity;

    var aUnitaryPrice=bondecommandeValueFromType(aType,"unitaryprice");
    document.getElementById("id_bondecommande_input_unitaryprice").value=Number(aUnitaryPrice).toFixed(2);;
    bondecommande_unitary[nodedefrais_size-1]=aUnitaryPrice;
}

 //==============================================
// Function: changeQuantity
// Purpose: 
//==============================================
function changeQuantity()
{
    var aQuantity=document.getElementById("id_bondecommande_input_quantity").value;
      bondecommande_quantity[nodedefrais_size-1]=aQuantity;

}

//==============================================
// Function: updateTotal
// Purpose: =
//==============================================
function updateTotal()
{
    var aQuantity=document.getElementById("id_bondecommande_input_quantity").value;
    var aUnitaryPrice=document.getElementById("id_bondecommande_input_unitaryprice").value;
    var aTotal=aQuantity*aUnitaryPrice;
    document.getElementById("id_bondecommande_input_total").value=aTotal.toFixed(2);
    bondecommande_total[nodedefrais_size-1]=aTotal;
    updateGrandTotal();
}

//==============================================
// Function: updateGrandTotal
// Purpose: 
//==============================================
function updateGrandTotal()
{
    var grandTotal=0.0;
    for(var i=0; i<nodedefrais_size; i++) {
         grandTotal+=bondecommande_total[i]; 
    }
    document.getElementById("id_bondecommande_input_grandtotal").innerHTML=grandTotal.toFixed(2)+"€";
    return grandTotal;
}
//==============================================
// Function: submitNodeDeFrais
// Purpose: 
//==============================================
function submitNodeDeFrais(PHP_Self)
{
    var grandTotal=updateGrandTotal();
 	if (confirm("Voulez-vous vraiment créer un bon de commande de  "+grandTotal.toFixed(2)+"€ ?") == true) {
		var aCommand=PHP_Self+"?bdc="+updatebondecommandeToJSON();
		window.location.href = aCommand;
	}
}
//==============================================
// Function: initNewRow
// Purpose: Add a row in the bondecommande table
//==============================================
function initNewRow()
{
    if(nodedefrais_size>0) {
        var index=nodedefrais_size-1;
        var currentIndex=bondecommande_index[index] ;
        var inputDate=bondecommande_date[index] ;
        var inputType=bondecommandeValueFromType(bondecommande_type[index],"name");
        var inputReference=bondecommande_reference[index]
        var inputQuantity=bondecommande_quantity[index];
        var inputPrice=bondecommande_unitary[index]; 
        var inputTotal=bondecommande_total[index]; 
        addRow(currentIndex,inputDate, inputType, inputReference, inputQuantity, inputPrice, inputTotal);
    }
    else {
        var select = document.getElementById("id_bondecommande_input_type");
 
        var size=bondecommandeTypesSize();
        for(var i=0;i<size;i++) {
		    var option = document.createElement("option");
		    option.text = bondecommandeValueFromIndex(i,"name");
		    option.value = bondecommandeValueFromIndex(i,"type");;
		    select.add(option) ;
	    }
    }

    document.getElementById("id_bondecommande_rowinput").style.display="";
    nodedefrais_size++;
    index_max++;

    // initialization
    var index=nodedefrais_size-1;
    const today = new Date();
    bondecommande_date[index]=today.toISOString().substring(0,10);
    document.getElementById("id_bondecommande_input_date").value=bondecommande_date[index];
    bondecommande_index[index]=index_max;
    bondecommande_type[index]="";
    document.getElementById("id_bondecommande_input_type").value=bondecommande_type[index];
    bondecommande_reference[index]="";
    document.getElementById("id_bondecommande_input_reference").value=bondecommande_reference[index];
    bondecommande_quantity[index]=0;
    document.getElementById("id_bondecommande_input_quantity").value=bondecommande_quantity[index];
    bondecommande_unitary[index]=0.;
    document.getElementById("id_bondecommande_input_unitaryprice").value=bondecommande_unitary[index].toFixed(2);
    bondecommande_total[index]=0.;
    document.getElementById("id_bondecommande_input_total").value=bondecommande_total[index].toFixed(2);
}

//==============================================
// Function: addRow
// Purpose: Add a row in the bondecommande table
//==============================================
function addRow(theIndex, theDate, theType, theReference, theQuantity, thePrice,  theTotal)
{

    // Find a <table> element with id="myTable":
    var table = document.getElementById("id_table_bondecommande");
    var sizeTable=table.rows.length;

    // Create an empty <tr> element and add it to the 1st position of the table:
    var row = table.insertRow(sizeTable-2);

    // Insert new cells (<td> elements) at the 1st and 2nd position of the "new" <tr> element:
    // Action
    var cellAction = row.insertCell(0);
    cellAction.innerHTML = "<a href=\"javascript:void(0);\" onclick=\"deletebondecommandeLine("+theIndex+")\" title=\"Effacer cette ligne\"><i class=\"bi bi-trash-fill\"></i></a>";
    // Date
    var cellDate = row.insertCell(1);
     cellDate.innerHTML = theDate;
    // Type
    var cellType = row.insertCell(2);
    cellType.innerHTML = theType;
    // Reference
    var cellreference = row.insertCell(3);
    cellreference.innerHTML = theReference;
    // Quantity
    var cellQuantity = row.insertCell(4);
     cellQuantity.innerHTML = theQuantity;
    // Price
    var cellPrice = row.insertCell(5);
    cellPrice.innerHTML = thePrice;
    // Total
    var cellTotal = row.insertCell(6);
    cellTotal.innerHTML = theTotal;
}

//==============================================
// Function: updatebondecommandeToJSON
// Purpose: code in JSON the bon de commande
//==============================================

function updatebondecommandeToJSON()
{
    var json="[";
    for(var i=0;i<nodedefrais_size;i++) {
        if(i>0) {
            json+=',';            
        }
        json+='{';
        json+='"date":"'+ bondecommande_date[i]+'"';
        json+=',"name":"'+ bondecommandeValueFromType(bondecommande_type[i],"name")+'"';
        json+=',"type":"'+ bondecommande_type[i]+'"';
        json+=',"reference":"'+bondecommande_reference[i]+'"';
        json+=',"unitary":"'+ bondecommande_unitary[i]+'"';
        json+=',"quantity":"'+bondecommande_quantity[i]+'"';
        json+=',"total":"'+bondecommande_total[i]+'"';
        json+='}';
    }
    json+="]";
    document.getElementById("id_bondecommande_json").value=json;
    return json;
}

//==============================================
// Function: bondecommandeTypesSize
// Purpose: returns the number of types
//==============================================

function bondecommandeTypesSize() {

    return bondecommandeSize=bondecommandeTypes.length;
}
//==============================================
// Function: bondecommandeTypesValue
// Purpose: returns the value associate to a key of types
//==============================================
function bondecommandeValueFromIndex(theIndex, theKey) {
    var bondecommandeObject= bondecommandeTypes[theIndex];
    var keyValue="";
    if(bondecommandeObject.hasOwnProperty(theKey)) {
        keyValue=bondecommandeObject[theKey];
    }
    return keyValue;
}

//==============================================
// Function: bondecommandeValueFromType
// Purpose: returns the value associate to a key of types
//==============================================

function bondecommandeValueFromType(theType, theKey) {
    var keyValue="";
    for(var i=0;i<bondecommandeTypes.length;i++) {
        var bondecommandeObject= bondecommandeTypes[i];
        if(bondecommandeObject.type==theType) {
            if(bondecommandeObject.hasOwnProperty(theKey)) {
                keyValue=bondecommandeObject[theKey];
            }
            return keyValue;
        }
    }
    return keyValue;
}

//==============================================
// Function: prefillDropdownMenus
// Purpose: Prefill a Menu
//==============================================

function prefillDropdownMenus(selectName, valuesArray, theDefaultId) {

	var select = document.getElementById(selectName);
 
	for (var i = 0; i < valuesArray.length; i++) {
		 var option = document.createElement("option");
		 option.text = valuesArray[i].last_name + " " + valuesArray[i].first_name;
		 option.value = valuesArray[i].id ;
         if(theDefaultId==option.value) {
            option.selected=true;
         }
		 select.add(option) ;
         
	}
}
//===============================================
// Main
var ReadOnlyColor="AliceBlue";
const bondecommande_date=[];
const bondecommande_type=[];
const bondecommande_reference=[];
const bondecommande_unitary=[];
const bondecommande_quantity=[];
const bondecommande_total=[];
const bondecommande_index=[];

// Decode bondecommande json file
var nodedefrais_size=0;
var index_max=-1;
var bondecommandeJSON=JSON.parse(bondecommandeJSONString);
var bondecommandeTypes=bondecommandeJSON.bondecommande;


function bondecommandeMain() {
    prefillDropdownMenus('id_member_name', members, default_member) ;
    bondecommande_page_loaded();
}