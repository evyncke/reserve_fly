<?php
/*
   Copyright 2022-2024 Patrick Reginster (and partially Eric Vyncke)

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
require_once 'dbi.php';

if (! $userIsAdmin and ! $userIsBoardMember and !$userIsInstructor) 
	journalise($userId, "F", "Vous n'avez pas le droit de consulter cette page") ; // journalise with Fatal error class also stop execution
ob_start("ob_gzhandler"); // Enable gzip compression over HTTP

// In the mobile_header.php, $header_postamble will be inserted in the actual <head>...</head> section
$header_postamble ='
<style>
	.tooltip {
	  position: relative;
	  display: inline-block;
	  border-bottom: 1px dotted black;
	  opacity: 1;
	}

	.tooltip .tooltiptext {
	  visibility: hidden;
	  width: 120px;
	  background-color: #555;
	  color: #fff;
	  text-align: center;
	  border-radius: 6px;
	  padding: 5px 0;
	  position: absolute;
	  z-index: 1;
	  bottom: 125%;
	  left: 50%;
	  margin-left: -60px;
	  opacity: 0;
	  transition: opacity 0.3s;
	}

	.tooltip .tooltiptext::after {
	  content: "";
	  position: absolute;
	  top: 100%;
	  left: 50%;
	  margin-left: -5px;
	  border-width: 5px;
	  border-style: solid;
	  border-color: #555 transparent transparent transparent;
	}

	.tooltip:hover .tooltiptext {
	  visibility: visible;
	  opacity: 1;
	}
	</style>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
' ;

require_once 'mobile_header5.php' ;

?>  
<!-- Eric's suggestion: move all the JS code in gestionMembres.js and include this file, it will be cached on the client browser and
and the page load will be faster -->
<script type="text/javascript">
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
//.normalize('NFD').replace(/([\u0300-\u036f]|[^0-9a-zA-Z])/g, '');
//        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
function parseFloatEU(s) {
	if (s == '') return 0 ;
	return parseFloat(s.replace(/\./g, "").replace(/\,/g, ".")) ;
}

// Based on https://www.w3schools.com/howto/howto_js_sort_table.asp
function sortTable(n, isNumeric) {
  var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
  table = document.getElementById("myTable");
  switching = true;
  // Set the sorting direction to ascending:
  dir = "asc";
  /* Make a loop that will continue until
  no switching has been done: */
  while (switching) {
    // Start by saying: no switching is done:
    switching = false;
    rows = table.rows;
    /* Loop through all table rows (except the
    first, which contains table headers): 
	Eric: actually, the table headers are not in table.rows()
	Eric: last row is for total, do not sort*/
    for (i = 0; i < (rows.length - 2); i++) {
      // Start by saying there should be no switching:
      shouldSwitch = false;
      /* Get the two elements you want to compare,
      one from current row and one from the next: */
      x = rows[i].getElementsByTagName("TD")[n];
      y = rows[i + 1].getElementsByTagName("TD")[n];
      /* Check if the two rows should switch place,
      based on the direction, asc or desc: */
      if (dir == "asc") {
		if (isNumeric) {
			if (parseFloatEU(x.innerText) > parseFloatEU(y.innerText)) {
				// If so, mark as a switch and break the loop:
				shouldSwitch = true;
				break;
			}
	  	} else {
			if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
          		// If so, mark as a switch and break the loop:
          		shouldSwitch = true;
          		break;
        	}
		}	
       } else if (dir == "desc") {
		if (isNumeric) {
			if (parseFloatEU(x.innerText) < parseFloatEU(y.innerText)) {
				// If so, mark as a switch and break the loop:
				shouldSwitch = true;
				break;
			}
	  	} else {
			if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
          		// If so, mark as a switch and break the loop:
          		shouldSwitch = true;
          		break;
        	}
		}	
      }
    }
    if (shouldSwitch) {
      /* If a switch has been marked, make the switch
      and mark that a switch has been done: */
      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
      switching = true;
      // Each time a switch is done, increase this count by 1:
      switchcount ++;
    } else {
      /* If no switching has been done AND the direction is "asc",
      set the direction to "desc" and run the while loop again. */
      if (switchcount == 0 && dir == "asc") {
        dir = "desc";
        switching = true;
      }
    }
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
	for(i=1;i<=4;i++){
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
		aSelectedToggle.checked=false;
	}
}

function filterRows(count, blocked, sign)
{
	var blockedToggle = document.getElementById("id_FilterSelected");
	blockedToggle.checked=false;
	for(i=1;i<=4;i++){
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
   	var aValueColumn=15;
	var aCielColumn=2;
	var aHidden=false;
   	for (i = 0; i < rows.length; i++) {
        var row = rows[i];
		if(!aCheckedValue) {
   		  row.hidden=false;
		  continue;
		}
		var aColumn1Row = row.getElementsByTagName("TD")[aSelectToggleColumn];
		var aSelectedToggle = aColumn1Row.childNodes[0];
		
		var aCiel = row.getElementsByTagName("TD")[aCielColumn].textContent;
		if(aCiel.indexOf("xxxxx")!=-1) {
     		  row.hidden=true;	
			  continue;
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

</script>
<?php
// Display or not Web deActicated member (Must be managed by a toggle button)
$displayWebDeactivated=false;
//print("userId=$userId</br>");
$searchText=""; 	
if (isset($_REQUEST['search']) and $_REQUEST['search'] != '') {
	$searchText=$_REQUEST['search'];
}
if (isset($_REQUEST['block']) or isset($_REQUEST['unblock'])) {
	if(!$userIsBoardMember) {
		print("<p class=\"text-danger\"><b>Vous n'êtes pas autorisés à débloquer un membre.<br/>Seuls les membres du CA peuvent débloquer un membre !</b></p>");
		journalise($userId, "I", "One FI try to unblock a member $personid. Not allowed") ;
	}
	else {
		$personid="";
		if (isset($_REQUEST['personid']) and $_REQUEST['personid'] != '') {
			$listpersonid=$_REQUEST['personid'];
		}
		else if (isset($_REQUEST['listpersonid']) and $_REQUEST['listpersonid'] != '') {
			$listpersonid=$_REQUEST['listpersonid'];
		}
		$personids=explode(",",$listpersonid);
		
		if (isset($_REQUEST['unblock']) and $_REQUEST['unblock'] == 'true') {
			for($i = 0; $i < sizeof($personids) ; $i++) {
				$personid=$personids[$i];
				$result = mysqli_query($mysqli_link, "select b_jom_id from $table_blocked where b_jom_id=$personid")
					or journalise($userId, 'F', "Impossible to know is a persom is already blocked: " . mysqli_error($mysqli_link)) ;

				$row = mysqli_fetch_array($result);
				if($row) {
					//print("Unblock person $personids[$i]</br>\n");
					//print("delete from $table_blocked where b_jom_id=$personid</br>\n");
					mysqli_query($mysqli_link, "delete from $table_blocked where b_jom_id=$personid") 
						or journalise($userId, 'F', "Cannot delete: " . mysqli_error($mysqli_link)) ;
					if (mysqli_affected_rows($mysqli_link) > 0) {
						$insert_message = "Table blocked  mis &agrave; jour" ;
						journalise($userId, 'I', "$table_blocked entry deleted for person $personid.") ;
					} else {
						$insert_message = "Impossible d'effacer la ligne dans la $table_blocked" ;
						journalise($userId, 'E', "Error (" . mysqli_error($mysqli_link). ") while deleting person entry for person $personid.") ;
					}			
					print("<b>Le membre $personid a été débloqué</b></br>");
					journalise($userId, "I", "Member $personid is now unblocked") ;
				}
				else {
				    print("<b>Le membre $personid n'est pas bloqué !</b></br>");	
				}
			}
		}
		if (isset($_REQUEST['block']) and $_REQUEST['block'] == 'true') {
			$reason=urldecode($_REQUEST['reason']);
			$reason=web2db($reason);
			$reasonWeb=db2web($reason);
			for($i = 0; $i < sizeof($personids) ; $i++) {
				$personid=$personids[$i];
				
				// Check if already blocked
				//print("select b_jom_id from $table_blocked where b_jom_id=$personid</br>");

				$result = mysqli_query($mysqli_link, "select b_jom_id from $table_blocked where b_jom_id=$personid")
					or journalise($userId, 'F', "Impossible to know is a persom is already blocked: " . mysqli_error($mysqli_link)) ;

				$row = mysqli_fetch_array($result);
				if(!$row) {
					print("Block person $personids[$i]</br>\n");
					mysqli_query($mysqli_link, "insert into $table_blocked (b_jom_id, b_reason, b_who, b_when)
							values ('$personid', \"$reason\", '$userId', sysdate())")
						or journalise($userId, 'F', "Impossible d'ajouter dans les blocked: " . mysqli_error($mysqli_link)) ;

					print("<b>Le membre $personid a été bloqué :</b> Raison \"$reasonWeb\"</br>");
					journalise($userId, "W", "Member $persid is now blocked, $reasonWeb") ;
				}
				else {
				    print("<b>Le membre $personid est déjà bloqué !</b></br>");	
				}
			}
		}
	}
}

// Let's get some data from Odoo
require_once 'odoo.class.php' ;
$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
// Find all Odoo IDs
$sql = "SELECT odoo_id 
	FROM $table_person" ;
$result = mysqli_query($mysqli_link, $sql)
	or journalise($userId, "F", "Cannot retrieve all Odoo ids: " . mysqli_error($mysqli_link)) ;
$ids = array() ;
while ($row = mysqli_fetch_array($result)) {
	$ids[] = intval($row['odoo_id']) ;
}
mysqli_free_result($result) ;
$members = $odooClient->Read('res.partner', 
	$ids, 
	array('fields' => array('email', 'total_due'))) ;
$odoo_customers = array() ;
foreach($members as $member) {
	$email =  strtolower($member['email']) ;
	$odoo_customers[$email] = $member ; // Let's build a dict indexed by the email addresses
}
?>
<h2>Table des membres du RAPCS</h2>
  <p>&nbsp;&nbsp;Type something to search the table for first names, last names , ciel ref, ...</p>  
<?php	
  print("<input class=\"form-control\" id=\"id_SearchInput\" type=\"text\" placeholder=\"Search..\" value=\"$searchText\">");
?>
  <br>&nbsp;&nbsp;Display only
  &nbsp;&nbsp;<input type="checkbox" id="id_FilterSelected" name="name_FilterSelected" value="Selected" onclick="filterSelected();" ><label for="name_FilterSelected">&nbsp;Selected</label>
  &nbsp;&nbsp;<input type="checkbox" id="id_FilterRows1" name="name_FilterRows1" value="Blocked" onclick="filterRows(1,'Blocked','');" ><label for="name_Blocked">&nbsp;Blocked</label>
  &nbsp;&nbsp;<input type="checkbox" id="id_FilterRows2" name="name_FilterRows2" value="negativeValue" onclick="filterRows(2,'','<');" ><label for="name_negativeValue">&nbsp;Negative Value</label>
  &nbsp;&nbsp;<input type="checkbox" id="id_FilterRows3" name="name_FilterRows3" value="negativeValue" onclick="filterRows(3,'NotBlocked','<');" ><label for="name_negativeValue">&nbsp;Negative Value & Not Blocked</label>
    &nbsp;&nbsp;<input type="checkbox" id="id_FilterRows4" name="name_FilterRows4" value="negativeValue" onclick="filterRows(4,'Blocked','>');" ><label for="name_negativeValue">&nbsp;Positive Value & Blocked</label>
  <br>
     &nbsp;&nbsp;
<?php
print("&nbsp;&nbsp;<input type=\"submit\" value=\"Unselect all\" id=\"id_SubmitSelect\" onclick=\"submitSelect('Unselect')\")> &nbsp;&nbsp;
	<input type=\"submit\" value=\"Block\" id=\"id_SubmitBlocked\" onclick=\"submitBlocked('$_SERVER[PHP_SELF]','Block')\")> &nbsp;&nbsp;
    <input type=\"submit\" value=\"Unblock\" id=\"id_SubmitBlocked\" onclick=\"submitBlocked('$_SERVER[PHP_SELF]', 'NotBlock')\">");
?>
</br>
	<p></p>
<div class="table">
<table width="100%" style="margin-left: auto; margin-right: auto;" class="table table-striped table-hover table-bordered"> 
	<thead style="position: sticky;">
<tr style="text-align: Center;">
<th class="select-checkbox" onclick="sortTable(0, true)" style="text-align: right;">#</th>
<th onclick="sortTable(1, false)">Id</th>
<!--<th onclick="sortTable(2, false)">Ref. Ciel</th> -->
<th onclick="sortTable(2, false)">Ref. odoo</th>
<th onclick="sortTable(3, false)">Nom</th>
<th onclick="sortTable(4, false)">Prénom</th>
<th onclick="sortTable(5, false)">Adresse</th>
<th onclick="sortTable(6, false)">Code</th>
<th onclick="sortTable(7, false)">Ville</th>
<th onclick="sortTable(8, false)">Pays</th>
<th onclick="sortTable(9, false)">email</th>
<th onclick="sortTable(10, false)">Membre non-navigant</th>
<th onclick="sortTable(11, false)">Elève</th>
<th onclick="sortTable(12, false)">Pilote</th>
<th onclick="sortTable(13, false)">Membre Effectif</th>
<th onclick="sortTable(14, true)">Cotisation</th>
<th onclick="sortTable(15, true)">Solde</th>
<th onclick="sortTable(16, false)">Status</th>
<th onclick="sortTable(17, false)">Raison</th>
</tr>
</thead>
<tbody id="myTable">
<?php
// The subquery should retrieve the max date for this specific user...but it burns time
	$sql = "select distinct u.id as id, u.name as name, first_name, last_name, address, zipcode, city, country,
		ciel_code, odoo_id, block, bkb_amount, b_reason, u.email as email, 
		bkf_user, bkf_amount, bkf_payment_date, bkf_invoice_id,
		group_concat(group_id) as groups, sum(distinct bkl_debit) as invoice_total,
		datediff(current_date(), b_when) as days_blocked
			from $table_users as u join $table_user_usergroup_map on u.id=user_id 
			join $table_person as p on u.id=p.jom_id
			left join $table_bk_balance on ciel_code400=bkb_account
			left join $table_bk_ledger on bkl_client = ciel_code
			left join $table_membership_fees on bkf_user = p.jom_id
			left join $table_blocked on u.id = b_jom_id
			where group_id in ($joomla_member_group, $joomla_student_group, $joomla_pilot_group, $joomla_effectif_group)
			and (bkb_date is null or bkb_date=(select max(bkb_date) from $table_bk_balance))
			group by user_id
			order by last_name, first_name" ;
	//print($sql);
	$count=0;
	$result = mysqli_query($mysqli_link, $sql)
		or journalise(0, "F", "Cannot read members: " . mysqli_error($mysqli_link)) ;
	
	$memberCount=0;
	$studentCount=0;
	$effectifCount=0;
	$pilotCount=0;
	$cielCount=0;
	$blockedCount=0;
	$soldeTotal=0.0;
	$odooCount=0;
	$cotisationNonPayeCount=0;
	$cotisationPayeCount=0;
	
	$CheckMark="&#9989;";
	
	while ($row = mysqli_fetch_array($result)) {
		$personid=$row['id'];
		$row['name'] = db2web($row['name']) ;
		if ($row['name'] === FALSE) 
			journalise($userId, 'E', "There was an error while converting $row[name]") ; 
		$row['first_name'] = db2web($row['first_name']) ;
		$row['last_name'] = db2web($row['last_name']) ;
		//$address=db2web($row['address']." ".$row['zipcode']." ".$row['city']." ".$row['country'];
		$address=db2web($row['address']);
		$code=db2web($row['zipcode']);
		$ville=db2web($row['city']);
		$pays=db2web($row['country']);
		$email=db2web($row['email']);		
		$nom=$row['last_name'];
		if($nom == "") $nom=$row['name'];
		$prenom=$row['first_name'];	
		if($row['last_name']=="") {
			$row['last_name']="xxxxx";
		}
			
		$groups = explode(',', $row['groups']) ;
		$effectif = (in_array($joomla_effectif_group, $groups)) ? $CheckMark : '' ;
		$pilot = (in_array($joomla_pilot_group, $groups)) ? $CheckMark : '' ;
		$student = (in_array($joomla_student_group, $groups)) ? $CheckMark : '' ;
		$status=db2web($row['b_reason']);
		$blocked=$row['block'];
		$odoo = (isset($odoo_customers[strtolower($row['email'])])) ? $odoo_customers[strtolower($row['email'])] : null ;
		if($blocked==1) {
			$status="Web désactivé";
			$pilote="";
			$student="";
			$effectif="";
		}
		else if($status!="") {
			//$blocked='&#x26D4;';
			$blocked=2;
		}
		else {
			/*
			if($blocked!=0) {
				$blocked=1;
			}
			else {
				//$blocked='&#x2714;';
				$blocked=0;
			}
			*/
		}
		if($status=="") $status="OK";
		$member=$CheckMark;
		if($blocked==1 || $pilot == $CheckMark || $student== $CheckMark) {
			$member='';
		}
		$solde=0.;
		if($odoo) {
			$solde=-$odoo['total_due'];
		}
		$soldeCiel=$row['bkb_amount'];
		$soldeCiel=$soldeCiel*-1.00;
		if(abs($solde)<0.01) $solde=0.0;
		if(abs($soldeCiel)<0.01) $soldeCiel=0.0;
		
		//Don't display webdeactivated member if solde == 0
		if(!$displayWebDeactivated && $blocked == 1 && $solde == 0.0) {
		   continue;
		}
		//SELECT * FROM `rapcs_bk_balance`.   bkb_amount
		//SELECT * FROM `rapcs_bk_balance` ORDER BY `rapcs_bk_balance`.`bkb_date` DESC
		$count++;
		$ciel="xxxxxxx";
		if($row['ciel_code'] != "") {
			$ciel="400".$row['ciel_code'];
		}
		$odooReference=$row['odoo_id'];
		if ($odooReference == "") {
			$odooReference="xxxxx";
		}
		else {
			$odooCount+=1;			
		}
		$cotisation=$row['bkf_amount'];
		$cotisationPaymentDate=$row['bkf_payment_date'];
		if($cotisation!="") {
			if($cotisationPaymentDate!="") {
				$cotisationPayeCount+=1;	
			}
			else {
				$cotisationNonPayeCount+=1;
				$status.=" (&#10071;Cotisation non payée)";
			}
			$cotisation=$cotisation." €";
		}
		if($solde < 0.0) $soldeTotal+=$solde;
		// Let's do some checks on January invoice
		if ($row['invoice_total'] == 70) {
			if ($member != $CheckMark) $status .= '<br/> ! cotisation de 70 €' ;
	 	}  
		else if ($row['invoice_total'] == 255) {
			if ($member == $CheckMark) $status .= '<br/> ! cotisation de 255 €' ;
		}
		$soldeStyle='';
		$rowStyle="";
		if($solde<0.0) {
//			$soldeStyle="style='color: red;'";
			$rowStyle="class='text-warning'";
		}
		if($blocked==2) {
			$rowStyle="class='text-danger'";	
		}
		else if($blocked==1) {
			$rowStyle="class='text-info'";	
		}
		if($blocked!=1) {
			if($member == $CheckMark) $memberCount++;
			if($student == $CheckMark) $studentCount++;
			if($pilot == $CheckMark) $pilotCount++;
			if($effectif == $CheckMark) $effectifCount++;
	    	if($ciel != '') $cielCount++;
		}
		if($blocked == 2) $blockedCount++;
		print("<tr style='text-align: right'; $rowStyle>
			<td><input type=\"checkbox\"> $count</td>
		    <td style='text-align: right;'>$personid</td>");
			//<td style='text-align: left;'><a class=\"tooltip\" href=\"mobile_folio.php?user=$personid\">$ciel<span class='tooltiptext'>Click pour afficher le folio</span></a></td>
		print("<td style='text-align: left;'><a class=\"tooltip\" href=\"mobile_ledger.php?user=$personid\">$odooReference<span class='tooltiptext'>Click pour afficher les opérations comptables</span></a></td>
			<td style='text-align: left;'><a class=\"tooltip\" href=\"mobile_profile.php?displayed_id=$personid\">$row[last_name]<span class='tooltiptext'>Click pour editer le profile</span></a></td>
			<td style='text-align: left;'>$row[first_name]</td>
			<td style='text-align: left;'>$address</td>
			<td style='text-align: left;'>$code</td>
			<td style='text-align: left;'>$ville</td>
			<td style='text-align: left;'>$pays</td>
			<td style='text-align: left;'>$email</td>
			<td style='text-align: center;'>$member</td>
			<td style='text-align: center;'>$student</td>
			<td style='text-align: center;'>$pilot</td>
			<td style='text-align: center;'>$effectif</td>");
		if($cotisationPaymentDate!="") {
			print("<td style='text-align: center;'>$cotisation</td>");
		}
		else {
			if($cotisation!="") {
				$cotisation="[".$cotisation."]";
			}
			print("<td style='text-align: center;' class='text-danger'>$cotisation</td>");			
		}
		$soldeText="";
		$soldeStyle="";
		if($solde<0.0) {
			$soldeStyle=' class="text-danger" ';
		}
		if($odoo) {
			$soldeText=number_format($solde,2,",",".")." €";
		}
		if(0 && $row['ciel_code'] != '') {
			$soldeText .="</br>" . number_format($soldeCiel, 2, ",", ".") . "€(Ciel)";
		}
		print("<td $soldeStyle>$soldeText</td>");				
		if($blocked==2) {
			print("<td style='text-align: center;font-size: 17px;' class='text-danger'>
			<a class=\"tooltip\" href=\"javascript:void(0);\" onclick=\"blockFunction('$_SERVER[PHP_SELF]','Unblock','$nom $prenom','$personid','$solde')\">&#x26D4;<span class='tooltiptext'>Click pour DEBLOQUER</span>
				<span class=\"badge text-bg-info\">$row[days_blocked]</span></a></td>");
		}
		else if($blocked==1){
			print("<td style='text-align: center;font-size: 15px;' class='text-danger'>&#10060;</td>");		
		}
		else {
			print("<td style='text-align: center;font-size: 17px;' class='text-success'>
				<a class=\"tooltip\" href=\"javascript:void(0);\" onclick=\"blockFunction('$_SERVER[PHP_SELF]','Block','$nom $prenom','$personid','$solde')\">&#x2714;<span class='tooltiptext'>Click pour BLOQUER</span></a></td>");		
		}
		print("<td style='text-align: left;'>$status</td>");
		/*
		print("<td style='text-align: left;'><select id='id_blocked_$personid' name='blocked_$personid'>
			<option value='OK'>$status</option>
		<option value='B1'>Solde trop négatif</option>
		<option value='B2'>Cotisation non payée</option>
		</select></td>");
		*/
		print("</tr>\n");
	}
	print("<tr style='text-align: center;' class='text-bg-info'>
		<td>Total</td>
	    <td></td>");
		//<td>$cielCount</td>
	print("<td>$odooCount</td>
		<td>$count</td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td>$memberCount</td>
		<td>$studentCount</td>
		<td>$pilotCount</td>
		<td>$effectifCount</td>
		<td><div class='text-danger'>[$cotisationNonPayeCount]</div><div>/$cotisationPayeCount</div></td>");
	$soldeTotalText=number_format($soldeTotal,2,",",".");
	if($soldeTotal<0.0) {
		print("<td style='text-align: right;' class='text-danger'>$soldeTotalText €</td>");
	}
	else {
		print("<td style='text-align: right;'>$soldeTotalText €</td>");		
	}
	print("<td>$blockedCount</td>
		<td></td>
		</tr>\n");
?>
</tbody>
</table>
</div>
<?php
if(abs($cielCount-161)>15) {
	print("<p class='text-bg-danger'><b>La table ne semble pas correcte. Il semble manquer des comptes! A vérifier.</b></p>");
}
?>
</body>
</html>