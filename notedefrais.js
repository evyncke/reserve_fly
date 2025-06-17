// JavaScript used by nodedefrais.php to manage the page
//
function notedefrais_page_loaded() {

  document.getElementById("id_notedefrais_input_total").readOnly=true;;
  document.getElementById("id_notedefrais_input_total").style.backgroundColor = ReadOnlyColor;
  document.getElementById("id_notedefrais_input_odooreference").readOnly=true;;
  document.getElementById("id_notedefrais_input_odooreference").style.backgroundColor = ReadOnlyColor;
  document.getElementById("id_notedefrais_input_odooanalytic").readOnly=true;;
  document.getElementById("id_notedefrais_input_odooanalytic").style.backgroundColor = ReadOnlyColor;

  //document.getElementById("id_cdv_segment_count").readOnly=true;
  //document.getElementById("id_cdv_segment_count").style.backgroundColor = ReadOnlyColor;
  //document.getElementById("id_cdv_segment_count").value=default_segment;
  
    document.getElementById("id_member_name").onchange = function() {
        updateSubmitButton();
    };
    document.getElementById("id_notedefrais_input_date").onchange = function() {
	    changeDate();
        updateNotedefraisToJSON();
        updateSubmitButton();
    };
    document.getElementById("id_notedefrais_input_type").onchange = function() {
	    changeType();
        updateTotal();
        updateNotedefraisToJSON();
        updateSubmitButton();
    };
    document.getElementById("id_notedefrais_input_description").onchange = function() {
	    changeDescription();
        updateNotedefraisToJSON();
        updateSubmitButton();
    };
    document.getElementById("id_notedefrais_input_quantity").onchange = function() {
	    changeQuantity();
        updateTotal();
        updateNotedefraisToJSON();
        updateSubmitButton();
    };
    document.getElementById("id_notedefrais_input_unitaryprice").onchange = function() {
	    changeUnitaryPrice();
        updateTotal();
        updateNotedefraisToJSON();
        updateSubmitButton();
    };

    document.getElementById("id_notedefrais_input_remboursable").onchange = function() {
	    changeRemboursable();
        updateSubmitButton();
    };
    /*
    document.getElementById("id_notedefrais_input_odooreference").onchange = function() {
	    changeOdooReference();
    };
    */
   document.getElementById("id_add_row").onclick = function() {
		initNewRow();
        updateNotedefraisToJSON();
        updateSubmitButton();
    };
 
   document.getElementById("id_notedefrais_input_justificatif").onchange = function() {
        changeImage();
        updateSubmitButton();
    };

    document.getElementById("id_notedefrais_rowinput").style.display="none";
    document.getElementById("id_submit_notedefrais").disabled=true;

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
    document.getElementById("id_submit_notedefrais").disabled=true;
    // Check Member
    if(document.getElementById("id_member_name").value=="") {
        return;
    }
    // Check Remboursable
    if(document.getElementById("id_notedefrais_input_remboursable").value=="") {
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
    if(document.getElementById("id_notedefrais_input_justificatif").value=="") {
        // Check if type = TKI => pas besoin de fiches justificatives
        for(var i=0;i<nodedefrais_size;i++){
            if(notedefrais_type[i]!="TKI") {
                return;
            }
        }
    }
    document.getElementById("id_submit_notedefrais").disabled=false;
}
//==============================================
// Function: deleteNoteDeFraisLine
// Purpose: 
//==============================================
function deleteNoteDeFraisLine(theRowIndex)
{
    var aLineIndex=-1;
    if(theRowIndex==-1) {
        //Delete sur la row INPUT: hide + activate "Create new line"
        document.getElementById("id_notedefrais_rowinput").style.display="none";
        document.getElementById("id_add_row").disabled=false;       
        document.getElementById("id_add_row").style.display="";
        aLineIndex=nodedefrais_size-1;
    }
    else {
        for(var i=0; i<nodedefrais_size; i++) {
            if(notedefrais_index[i] == theRowIndex) {
                aLineIndex=i;
                break;
            }
        }
        if(aLineIndex>=0) {
            // Hide input row
            var table = document.getElementById("id_table_notedefrais");
            var rows = table.rows;
            var row = rows[theRowIndex+1];
            row.hidden=true;
        }
    }
    if(aLineIndex>=0) {
        // delete a table row
        notedefrais_date.splice(aLineIndex, 1);
        notedefrais_type.splice(aLineIndex, 1);
        notedefrais_description.splice(aLineIndex, 1);
        notedefrais_unitary.splice(aLineIndex, 1);
        notedefrais_quantity.splice(aLineIndex, 1);
        notedefrais_total.splice(aLineIndex, 1);
        notedefrais_odooreference.splice(aLineIndex, 1);
        notedefrais_odooanalytic.splice(aLineIndex, 1);
        notedefrais_index.splice(aLineIndex, 1);
        nodedefrais_size--;
    }
    updateSubmitButton();
}
//==============================================
// Function: deleteNoteDeFraisFiles
// Purpose: 
//==============================================

function deleteNoteDeFraisFiles(PHP_Self, theNotedeFrais)
{
	if (confirm("Confirmer que vous voulez supprimer les fichiers associés à la notre de frais " + theNotedeFrais + "?") == true) {			
   		 	var aCommand=PHP_Self+"?delete="+theNotedeFrais;	
   		 	window.location.href = encodeURI(aCommand);
	}
}
//==============================================
// Function: checkLastInput
// Purpose: 
//==============================================
function checkLastInput()
{
    if(document.getElementById("id_notedefrais_rowinput").style.display!="none") {
        // Check Type
        if(document.getElementById("id_notedefrais_input_type").value=="") {
            return false;
        }
        // Check Description
        if(document.getElementById("id_notedefrais_input_description").value=="") {
            return false;
        }
        // Check Montant
        var aValue=document.getElementById("id_notedefrais_input_total").value;
        if(document.getElementById("id_notedefrais_input_total").value=="0" ||
            document.getElementById("id_notedefrais_input_total").value=="0.00") {
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
    var aDate=document.getElementById("id_notedefrais_input_date").value;
    notedefrais_date[nodedefrais_size-1]=aDate;
}

//==============================================
// Function: changeImage
// Purpose: 
//==============================================
function changeImage()
{
    /*
    var anImages=document.getElementById("id_notedefrais_input_justificatif").files;
    if(anImages.length>0) {
        document.getElementById("id_notedefrais_image").src=anImages[0];
    }
    else {
        document.getElementById("id_notedefrais_image").src="";
    }
        */
}

//==============================================
// Function: changeType
// Purpose: 
//==============================================
function changeType()
{
    var aType=document.getElementById("id_notedefrais_input_type").value;
    notedefrais_type[nodedefrais_size-1]=aType;

    var aDescription=notedefraisValueFromType(aType,"description");
    document.getElementById("id_notedefrais_input_description").placeholder=aDescription;
    document.getElementById("id_notedefrais_input_description").value="";
    notedefrais_description[nodedefrais_size-1]="";

    var aQuantity=notedefraisValueFromType(aType,"quantity");
    document.getElementById("id_notedefrais_input_quantity").value=aQuantity;
    notedefrais_quantity[nodedefrais_size-1]=aQuantity;

    var aUnitaryPrice=notedefraisValueFromType(aType,"unitaryprice");
    document.getElementById("id_notedefrais_input_unitaryprice").value=aUnitaryPrice;
    notedefrais_unitary[nodedefrais_size-1]=aUnitaryPrice;

    var aOdooReference=notedefraisValueFromType(aType,"odooreference");
    document.getElementById("id_notedefrais_input_odooreference").value=aOdooReference;
    notedefrais_odooreference[nodedefrais_size-1]=aOdooReference;

    var aOdooAnalytic=notedefraisValueFromType(aType,"analytic");
    document.getElementById("id_notedefrais_input_odooanalytic").value=aOdooAnalytic;
    notedefrais_odooanalytic[nodedefrais_size-1]=aOdooAnalytic;

}

//==============================================
// Function: changeDescription
// Purpose: 
//==============================================
function changeDescription()
{
    var aDescription=document.getElementById("id_notedefrais_input_description").value;
    notedefrais_description[nodedefrais_size-1]=aDescription;

}

 //==============================================
// Function: changeQuantity
// Purpose: 
//==============================================
function changeQuantity()
{
    var aQuantity=document.getElementById("id_notedefrais_input_quantity").value;
      notedefrais_quantity[nodedefrais_size-1]=aQuantity;

}

//==============================================
// Function: changeUnitaryPrice
// Purpose: 
//==============================================
function changeUnitaryPrice()
{
    var aPrice=document.getElementById("id_notedefrais_input_unitaryprice").value;
    notedefrais_unitary[nodedefrais_size-1]=aPrice;

}
//==============================================
// Function: changeRemboursable
// Purpose: 
//==============================================
function changeRemboursable()
{
}
//==============================================
// Function: updateTotal
// Purpose: =
//==============================================
function updateTotal()
{
    var aQuantity=document.getElementById("id_notedefrais_input_quantity").value;
    var aUnitaryPrice=document.getElementById("id_notedefrais_input_unitaryprice").value;
    var aTotal=aQuantity*aUnitaryPrice;
    document.getElementById("id_notedefrais_input_total").value=aTotal;
    notedefrais_total[nodedefrais_size-1]=aTotal;
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
         grandTotal+=notedefrais_total[i]; 
    }
    document.getElementById("id_notedefrais_input_grandtotal").innerHTML=grandTotal.toFixed(2)+"€";
    return grandTotal;
}
//==============================================
// Function: submitNodeDeFrais
// Purpose: 
//==============================================
function submitNodeDeFrais(PHP_Self)
{
    var grandTotal=updateGrandTotal();
 	if (confirm("Voulez-vous vraiment créer une note de frais de  "+grandTotal.toFixed(2)+"€ ?") == true) {
        var aRemboursable=document.getElementById("id_notedefrais_input_remboursable").value;
		var aCommand=PHP_Self+"?remboursable="+aRemboursable+"&ndf="+updateNotedefraisToJSON();
		//var aCommand=PHP_Self+"?ndf="+JSON.stringify(notedefraisToJSON());
		window.location.href = aCommand;
	}
}
//==============================================
// Function: initNewRow
// Purpose: Add a row in the notedefrais table
//==============================================
function initNewRow()
{
    if(nodedefrais_size>0) {
        var index=nodedefrais_size-1;
        var currentIndex=notedefrais_index[index] ;
        var inputDate=notedefrais_date[index] ;
        var inputType=notedefraisValueFromType(notedefrais_type[index],"name");
        var inputDescription=notedefrais_description[index]
        var inputQuantity=notedefrais_quantity[index];
        var inputPrice=notedefrais_unitary[index]; 
        var inputTotal=notedefrais_total[index]; 
        var inputOdooCode=notedefrais_odooreference[index];
        var inputOdooAnalytic=notedefrais_odooanalytic[index];
        addRow(currentIndex,inputDate, inputType, inputDescription, inputQuantity, inputPrice, inputTotal, inputOdooCode, inputOdooAnalytic);
    }
    else {
        var select = document.getElementById("id_notedefrais_input_type");
 
        var size=notedefraisTypesSize();
        for(var i=0;i<size;i++) {
		    var option = document.createElement("option");
		    option.text = notedefraisValueFromIndex(i,"name");
		    option.value = notedefraisValueFromIndex(i,"type");;
		    select.add(option) ;
	    }
    }

    document.getElementById("id_notedefrais_rowinput").style.display="";
    nodedefrais_size++;
    index_max++;

    // initialization
    var index=nodedefrais_size-1;
    const today = new Date();
    notedefrais_date[index]=today.toISOString().substring(0,10);
    document.getElementById("id_notedefrais_input_date").value=notedefrais_date[index];
    notedefrais_index[index]=index_max;
    notedefrais_type[index]="";
    document.getElementById("id_notedefrais_input_type").value=notedefrais_type[index];
    notedefrais_description[index]="";
    document.getElementById("id_notedefrais_input_description").value=notedefrais_description[index];
    notedefrais_quantity[index]=0;
    document.getElementById("id_notedefrais_input_quantity").value=notedefrais_quantity[index];
    notedefrais_unitary[index]=0.;
    document.getElementById("id_notedefrais_input_unitaryprice").value=notedefrais_unitary[index].toFixed(2);
    notedefrais_total[index]=0.;
    document.getElementById("id_notedefrais_input_total").value=notedefrais_total[index].toFixed(2);
    notedefrais_odooreference[index]="";
    notedefrais_odooanalytic[index]="";
}

//==============================================
// Function: addRow
// Purpose: Add a row in the notedefrais table
//==============================================
function addRow(theIndex, theDate, theType, theDescription, theQuantity, thePrice, 
    theTotal, theOdooCode, theOdooAnalytic)
{

    // Find a <table> element with id="myTable":
    var table = document.getElementById("id_table_notedefrais");
    var sizeTable=table.rows.length;

    // Create an empty <tr> element and add it to the 1st position of the table:
    var row = table.insertRow(sizeTable-2);

    // Insert new cells (<td> elements) at the 1st and 2nd position of the "new" <tr> element:
    // Action
    var cellAction = row.insertCell(0);
    cellAction.innerHTML = "<a href=\"javascript:void(0);\" onclick=\"deleteNoteDeFraisLine("+theIndex+")\" title=\"Effacer cette ligne\"><i class=\"bi bi-trash-fill\"></i></a>";
   // Date
    var cellDate = row.insertCell(1);
     cellDate.innerHTML = theDate;
   // Type
    var cellType = row.insertCell(2);
    cellType.innerHTML = theType;
    // Description
    var cellDescription = row.insertCell(3);
    cellDescription.innerHTML = theDescription;
    // Quantity
    var cellQuantity = row.insertCell(4);
     cellQuantity.innerHTML = theQuantity;
   // Price
    var cellPrice = row.insertCell(5);
    cellPrice.innerHTML = thePrice;
    // Total
    var cellTotal = row.insertCell(6);
    cellTotal.innerHTML = theTotal;
    // OdooCode
    var cellOdooCode = row.insertCell(7);
    cellOdooCode.innerHTML = theOdooCode;
    // OdooAnalytic
    var cellOdooAnalytic= row.insertCell(8);
    cellOdooAnalytic.innerHTML = theOdooAnalytic;
}

//==============================================
// Function: updateNotedefraisToJSON
// Purpose: code in JSON the note de frais
//==============================================

function updateNotedefraisToJSON()
{
    var json="[";
    for(var i=0;i<nodedefrais_size;i++) {
        if(i>0) {
            json+=',';            
        }
        json+='{';
        json+='"date":"'+ notedefrais_date[i]+'"';
        json+=',"name":"'+ notedefraisValueFromType(notedefrais_type[i],"name")+'"';
        json+=',"type":"'+ notedefrais_type[i]+'"';
        json+=',"description":"'+notedefrais_description[i]+'"';
        json+=',"unitary":"'+ notedefrais_unitary[i]+'"';
        json+=',"quantity":"'+notedefrais_quantity[i]+'"';
        json+=',"total":"'+notedefrais_total[i]+'"';
        json+=',"odoo":"'+notedefrais_odooreference[i]+'"';
        json+=',"analytic":"'+notedefrais_odooanalytic[i]+'"';
        json+='}';
    }
    json+="]";
    document.getElementById("id_notedefrais_json").value=json;
    return json;
}

//==============================================
// Function: notedefraisTypesSize
// Purpose: returns the number of types
//==============================================

function notedefraisTypesSize() {

    return notedefraisSize=notedefraisTypes.length;
}
//==============================================
// Function: notedefraisTypesValue
// Purpose: returns the value associate to a key of types
//==============================================
function notedefraisValueFromIndex(theIndex, theKey) {
    var notedefraisObject= notedefraisTypes[theIndex];
    var keyValue="";
    if(notedefraisObject.hasOwnProperty(theKey)) {
        keyValue=notedefraisObject[theKey];
    }
    return keyValue;
}

//==============================================
// Function: notedefraisValueFromType
// Purpose: returns the value associate to a key of types
//==============================================

function notedefraisValueFromType(theType, theKey) {
    var keyValue="";
    for(var i=0;i<notedefraisTypes.length;i++) {
        var notedefraisObject= notedefraisTypes[i];
        if(notedefraisObject.type==theType) {
            if(notedefraisObject.hasOwnProperty(theKey)) {
                keyValue=notedefraisObject[theKey];
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

//prefillDropdownMenus('id_cdv_aircraft', planes) ;
const notedefrais_date=[];
const notedefrais_type=[];
const notedefrais_description=[];
const notedefrais_unitary=[];
const notedefrais_quantity=[];
const notedefrais_total=[];
const notedefrais_odooreference=[];
const notedefrais_odooanalytic=[];
const notedefrais_index=[];

// Decode notedefrais json file
var nodedefrais_size=0;
var index_max=-1;
var notedefraisJSON=JSON.parse(notedefraisJSONString);
var notedefraisTypes=notedefraisJSON.notedefrais;

prefillDropdownMenus('id_member_name', members, default_member) ;
window.onload=notedefrais_page_loaded();
