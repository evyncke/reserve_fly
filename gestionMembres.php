<?php
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
require_once 'dbi.php';
require_once 'odooFlight.class.php';

// Cotisation computed for year 
$cotisationYear=$membership_year; // Set in dbi.php
if (! $userIsAdmin and ! $userIsBoardMember and !$userIsInstructor) 
	journalise($userId, "F", "Vous n'avez pas le droit de consulter cette page") ; // journalise with Fatal error class also stop execution

// In the mobile_header.php, $additional_preload is use to force a HTTP/2 preload of specific resources (faster load time)
$additional_preload = '</resa/js/gestionMembres.js>;rel=preload;as=script,</resa/css/gestionMembres.css>;rel=preload;as=style' ;
// In the mobile_header.php, $header_postamble will be inserted in the actual <head>...</head> section
$header_postamble ='
<script type="text/javascript" src="js/gestionMembres.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<link rel="stylesheet" type="text/css" href="css/gestionMembres.css">
' ;
require_once 'mobile_header5.php' ;

// Display or not Web deActicated member (Must be managed by a toggle button)
$displayWebDeactivated=true;
$searchText=""; 	
if (isset($_REQUEST['search']) and $_REQUEST['search'] != '') {
	$searchText=$_REQUEST['search'];
}
if (isset($_REQUEST['block']) or isset($_REQUEST['unblock'])) {
	if(!$userIsBoardMember) {
		print("<p class=\"text-danger\"><b>Vous n'êtes pas autorisés à débloquer un membre.<br/>Seuls les membres de l'OA peuvent débloquer un membre !</b></p>");
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
					journalise($userId, "W", "Member $personid is now blocked, $reasonWeb") ;
				}
				else {
				    print("<b>Le membre $personid est déjà bloqué !</b></br>");	
				}
			}
		}
	}
}

//Create Cotisation invoice
if (isset($_REQUEST['createcotisation'])) {
	$personid="";
	if (isset($_REQUEST['personid']) and $_REQUEST['personid'] != '') {
		$personid=$_REQUEST['personid'];
		if (isset($_REQUEST['cotisationtype']) and $_REQUEST['cotisationtype'] != '') {
			$cotisationtype=$_REQUEST['cotisationtype'];
			if(OF_CreateFactureCotisation($personid, $cotisationtype, $membership_year)) {
				print("<div class=\"alert alert-info\" role=\"alert\"><b>La facture de cotisation pour $personid de type $cotisationtype pour $membership_year a été créée dans ODOO!</b></div>");	
			}
			else {
				print("<div class=\"alert alert-warning\" role=\"alert\"><b style='color: red;'>La facture de cotisation pour $personid de type $cotisationtype pour $membership_year  n'a pas été créée dans ODOO!</b></div>");	
				journalise($userId, "E", "La facture de cotisation pour $personid de type $cotisationtype pour $membership_year  n'a pas été créée dans ODOO!") ;
			}
		}
		else {
			print("<div class=\"alert alert-warning\" role=\"alert\"><b style='color: red;'>Impossible de créer une cotisation: Pas de cotisation type sélectionné!</b></div");	
		}
	}
	else  {
		print("<div class=\"alert alert-warning\" role=\"alert\"><b style='color: red;'>Impossible de créer une cotisation: Personne n'est sélectionné!</b></div>");	
	}
}

//Create Cours theorique invoice
if (isset($_REQUEST['createcourstheorique'])) {
	$personid="";
	if (isset($_REQUEST['personid']) and $_REQUEST['personid'] != '') {
		$personid=$_REQUEST['personid'];
		if(OF_CreateFactureCoursTheorique($personid, $membership_year)) {
			print("<div class=\"alert alert-info\" role=\"alert\"><b>La facture pour cours theorique pour $personid pour $membership_year a été créée dans ODOO!</b></div>");	
		}
		else {
			print("<div class=\"alert alert-warning\" role=\"alert\"><b style='color: red;'>La facture des cours theorique pour $personid  pour $membership_year  n'a pas été créée dans ODOO!</b></div>");	
			journalise($userId, "E", "La facture de cotisation pour $personid  pour $membership_year  n'a pas été créée dans ODOO!") ;
		}
	}
	else  {
		print("<div class=\"alert alert-warning\" role=\"alert\"><b style='color: red;'>Impossible de créer une facture cours theorique: Personne n'est sélectionné!</b></div>");	
	}
}

// Handle checkboxes toggling, i.e., add or remove from group
if (isset($_REQUEST['checkboxId']) and $_REQUEST['checkboxId'] != '' and isset($_REQUEST['checked']) and $_REQUEST['checked'] != '') {
	$checked=$_REQUEST['checked'];
	$parts = explode('-', $_REQUEST['checkboxId']);
	$personId = $parts[1];
	if (! is_numeric($personId))
		journalise($userId, "F", "Strange personId for checkbox toggling: " . $personId) ;
	GM_SetMemberGroup($personId, $parts[2], $checked) ;
	
}
$displayWebDeactivated=false;
if (isset($_REQUEST['unactivated']) and $_REQUEST['unactivated'] != '') {
	if($_REQUEST['unactivated']=="true") {
		$displayWebDeactivated=true;
	}
}
?>
<script type="text/javascript">

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
			if(confirm("Confirmez que vous voulez changer le statut \""+values[2]+"\" de ce membre." +
				"\nRappel: le membre doit être élève ou pilote sinon il est non-navigant." +
				"\nNe pas oublier de prévenir les personnes responsables et de mettre à jour le fichier membres.xls sur OneDrive (Toujours utilisé - Seul endroit ou on gere l'historique des membres).")) {
				var aSearchText=document.getElementById("id_SearchInput").value;
				var aCommand='?checkboxId=' + this.id + '&checked=' + this.checked;
				if(aCommand != "") {
					aCommand+="&search="+aSearchText;
				}
					// Redirect to the same URL with the checkbox ID in the query string
            	window.location.href = window.location.pathname + aCommand;
			}
			else { // Revert to previous checked state
				// TODO can probably simply do this.checked = !this.checked; ;-)
				if(this.checked){
					this.checked=false;
				} else {
					this.checked=true;
				}
			}
        };
    });
});
</script>
<?php
// Let's get some data from Odoo
require_once 'odoo.class.php' ;
$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
// Find all Odoo IDs
$sql = "SELECT odoo_id FROM $table_person" ;
$result = mysqli_query($mysqli_link, $sql)
	or journalise($userId, "F", "Cannot retrieve all Odoo ids: " . mysqli_error($mysqli_link)) ;
$ids = array() ;
while ($row = mysqli_fetch_array($result)) {
	$ids[] = intval($row['odoo_id']) ;
}
mysqli_free_result($result) ;
$members = $odooClient->Read('res.partner', 
	[$ids], 
	['fields' => ['email', 'total_due']]) ;
$odoo_customers = array() ;
foreach($members as $member) {
	$email =  strtolower($member['email']) ;
	$odoo_customers[$email] = $member ; // Let's build a dict indexed by the email addresses
}
?>
<h2>Gestion des membres</h2>
<?php
print("<b>Cotisation pour l'année $cotisationYear</b><br>");
?>
  <p>&nbsp;&nbsp;Type something to search the table for first names, last names , ref, ...</p>  
<?php	
  print("<input class=\"form-control\" id=\"id_SearchInput\" type=\"text\" placeholder=\"Search..\" value=\"$searchText\">");
?>
  <br>&nbsp;&nbsp;Display only:
  &nbsp;&nbsp;<input type="checkbox" class="form-check-input" id="id_FilterSelected" name="name_FilterSelected" value="Selected" onclick="filterSelected();" ><label for="name_FilterSelected">&nbsp;Selected</label>
  &nbsp;&nbsp;<input type="checkbox" class="form-check-input" id="id_FilterRows1" name="name_FilterRows1" value="Blocked" onclick="filterRows(1,'Blocked','');" ><label for="name_Blocked">&nbsp;Blocked</label>
  &nbsp;&nbsp;<input type="checkbox" class="form-check-input" id="id_FilterRows2" name="name_FilterRows2" value="negativeValue" onclick="filterRows(2,'','<');" ><label for="name_negativeValue">&nbsp;Negative Value</label>
  &nbsp;&nbsp;<input type="checkbox" class="form-check-input" id="id_FilterRows3" name="name_FilterRows3" value="negativeValue" onclick="filterRows(3,'NotBlocked','<');" ><label for="name_negativeValue">&nbsp;Negative Value & Not Blocked</label>
  &nbsp;&nbsp;<input type="checkbox" class="form-check-input" id="id_FilterRows4" name="name_FilterRows4" value="negativeValue" onclick="filterRows(4,'Blocked','>');" ><label for="name_negativeValue">&nbsp;Positive Value & Blocked</label>
  <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
  &nbsp;&nbsp;<input type="checkbox" class="form-check-input" id="id_FilterRows5" name="name_FilterRows5" value="sanscotisation" onclick="filterRows(5,'membre','sanscotisation');" ><label for="name_sanscotisation">&nbsp;Sans Cotisation</label>
  &nbsp;&nbsp;<input type="checkbox" class="form-check-input" id="id_FilterRows6" name="name_FilterRows6" value="membre" onclick="filterRows(6,'membre','nonnaviguant');" ><label for="name_nonnaviguant">&nbsp;Membres non naviguant</label>
  &nbsp;&nbsp;<input type="checkbox" class="form-check-input" id="id_FilterRows7" name="name_FilterRows7" value="membre" onclick="filterRows(7,'membre','eleve');" ><label for="name_eleve">&nbsp;Elèves</label>
  &nbsp;&nbsp;<input type="checkbox" class="form-check-input" id="id_FilterRows8" name="name_FilterRows8" value="membre" onclick="filterRows(8,'membre','pilote');" ><label for="name_pilote">&nbsp;Pilotes</label>
  &nbsp;&nbsp;<input type="checkbox" class="form-check-input" id="id_FilterRows9" name="name_FilterRows9" value="membre" onclick="filterRows(9,'membre','effectif');" ><label for="name_effectif">&nbsp;Membres effectifs</label>
  <br>
<?php
print("&nbsp;&nbsp;Actions:&nbsp;&nbsp;
	<input type=\"submit\" value=\"Select all visible\" id=\"id_SubmitSelect\" onclick=\"submitSelect('SelectVisible')\")> &nbsp;&nbsp;
	<input type=\"submit\" value=\"Unselect all\" id=\"id_SubmitSelect\" onclick=\"submitSelect('Unselect')\")> &nbsp;&nbsp;
	<input type=\"submit\" value=\"Block\" id=\"id_SubmitBlocked\" onclick=\"submitBlocked('$_SERVER[PHP_SELF]','Block')\")> &nbsp;&nbsp;
    <input type=\"submit\" value=\"Unblock\" id=\"id_SubmitBlocked\" onclick=\"submitBlocked('$_SERVER[PHP_SELF]', 'NotBlock')\">&nbsp;&nbsp;
	<input type=\"submit\" value=\"Copy Mails\" id=\"id_SubmitDownloadMail\" onclick=\"submitDownloadMail('$_SERVER[PHP_SELF]', 'CopyMail')\">&nbsp;&nbsp;");
	if(!$displayWebDeactivated) {
		print("<input type=\"submit\" value=\"Display Unactivated\" id=\"id_SubmitUnactivated\" onclick=\"submitUnactivated('$_SERVER[PHP_SELF]', 'Unactivated')\">");
	}
	else {
		print("<input type=\"submit\" value=\"Display Activated\" id=\"id_SubmitUnactivated\" onclick=\"submitUnactivated('$_SERVER[PHP_SELF]', 'Activated')\">");
	}
	 
	
?>
</br>
	<p></p>
<div class="table">
<table width="100%" style="margin-left: auto; margin-right: auto;" class="table table-striped table-hover table-bordered"> 
	<thead style="position: sticky;">
<tr style="text-align: Center;">
<th class="select-checkbox" onclick="sortTable(0, true)" style="text-align: right;">#</th>
<th onclick="sortTable(1, true)">Jom Id</th>
<th onclick="sortTable(2, true)">Ré	f. Odoo</th>
<th onclick="sortTable(3, false)">Nom</th>
<th onclick="sortTable(4, false)">Prénom</th>
<th onclick="sortTable(5, false)">Adresse</th>
<th onclick="sortTable(6, false)">Code</th>
<th onclick="sortTable(7, false)">Ville</th>
<th onclick="sortTable(8, false)">Pays</th>
<th onclick="sortTable(9, false)">Email</th>
<th onclick="sortTable(10, false)">Membre non-navigant</th>
<th onclick="sortTable(11, false)">Élève</th>
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
// TODO as now Odoo is well in full force, probably need to only process Odoo balance
$sql = "select distinct u.id as id, u.name as name, first_name, last_name, address, zipcode, city, country,
odoo_id, block, b_reason, u.email as email, 
bkf_user, bkf_amount, bkf_payment_date, bkf_invoice_date, bkf_invoice_id, ds_year,
group_concat(group_id) as allGroups,
datediff(current_date(), b_when) as days_blocked
	from $table_users as u 
	join $table_user_usergroup_map on u.id=user_id 
	join $table_person as p on u.id=p.jom_id
    left join $table_dto_student on u.id=ds_jom_id
	left join $table_membership_fees on bkf_user = p.jom_id and bkf_year = $cotisationYear
	left join $table_blocked on u.id = b_jom_id
	where group_id in ($joomla_member_group, $joomla_student_group, $joomla_pilot_group, $joomla_effectif_group)
	group by user_id
	order by last_name, first_name" ;
//print("$sql<br>");
//print("<br>");
	$count=0;
	$result = mysqli_query($mysqli_link, $sql)
		or journalise(0, "F", "Cannot read members: " . mysqli_error($mysqli_link)) ;
	
	$memberCount=0;
	$studentCount=0;
	$effectifCount=0;
	$pilotCount=0;
	$blockedCount=0;
	$soldeTotalPositif=0.0;
	$soldeTotalNegatif=0.0;
	$odooCount=0;
	$cotisationNonPayeCount=0;
	$cotisationPayeCount=0;
	$cotisationNonRenouveleeCount=0;
	$cotisationRenouveleeCount=0;
	
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
			
		$groups = explode(',', $row['allGroups']) ;
		$effectif = (in_array($joomla_effectif_group, $groups)) ? 
			'<input class="form-check-input" type="checkbox" id="check-' . $personid . '-Effectif" checked>' 
			: '<input class="form-check-input" type="checkbox" id="check-' . $personid . '-Effectif">'  ;
		$pilot = (in_array($joomla_pilot_group, $groups)) ? 
			'<input class="form-check-input" type="checkbox" id="check-' . $personid . '-Pilot" checked>' 
			: '<input class="form-check-input" type="checkbox" id="check-' . $personid . '-Pilot">'  ;
		$student = (in_array($joomla_student_group, $groups)) ? 
			'<input class="form-check-input" type="checkbox" id="check-' . $personid . '-Student" checked>' 
			: '<input class="form-check-input" type="checkbox" id="check-' . $personid . '-Student">'  ;
		$status=db2web($row['b_reason']);
		$blocked=$row['block'];
		$promotionEleve="";
		if(in_array($joomla_student_group, $groups)) {
			if(isset($row['ds_year']) && $row['ds_year']>2000) {
				$promotionEleve="<br>P".$row['ds_year'];
			}
			else {
				$promotionEleve="<a class=\"tooltip\" href=\"javascript:void(0);\" 
					onclick=\"createCoursTheoriqueFunction('$_SERVER[PHP_SELF]','CoursTheorique','" .
					str_replace("'", "\\'","$nom $prenom") . "','$personid',
					'2026')\">
					[2026?]<span class='tooltiptext'>Click pour facturer un cours theorique 2026</span>
				</a>";
			}
		}
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
		$member='<input class="form-check-input" type="checkbox" id="check-' . $personid . '-Member" checked disabled>' ;
		if($blocked==1 || strpos($pilot, "checked") !== false || strpos($student, "checked") !== false) {
			$member='';
		}
		$solde=0.;
		if($odoo) {
			$solde=-$odoo['total_due'];
		}
		if(abs($solde)<0.01) $solde=0.0;
		
		//Don't display webdeactivated member 
		if(!$displayWebDeactivated && $blocked == 1) {
		   continue;
		}
		else if($displayWebDeactivated && $blocked != 1) {
		   continue;
		}
		$count++;
		$odooReference=$row['odoo_id'];
		if ($odooReference == "") {
			$odooReference="xxxxx";
		}
		else {
			$odooCount+=1;			
		}
		// If solde <0, number of day after the last invoice due date
		$dueDays=-1;
		$invoiceDueDate="";
		$dueDaysText="";
	
		if($solde<0.0) {
			$dueDays=OF_InvoiceDueDays($odooReference);	
			if($dueDays>0) {
				$dueDaysText="<span class=\"badge bg-danger\">$dueDays</span>";
				$invoiceDueDate="&#10071;Echéance facture dépassée: ".OF_LastInvoiceDueDate($odooReference)."$dueDaysText<br>";
			}
			else {
				$dueDaysText="<span class=\"badge bg-warning text-dark\">$dueDays</span>";
				$invoiceDueDate="Echéance facture: ".OF_LastInvoiceDueDate($odooReference)."$dueDaysText<br>";
			}

			//print("nom=$nom odooReference=$odooReference dueDayTime=$dueDays<br>");
		}

		$cotisation=$row['bkf_amount'];
		//if($personid==328) print("personid=$personid, cotisation=$cotisation<br>");
		$cotisationInvoiceDate="";
		if(isset($row['bkf_invoice_date'])) $cotisationInvoiceDate=$row['bkf_invoice_date'];
		$cotisationPaymentDate="";
		if(isset($row['bkf_payment_date'])) $cotisationPaymentDate=$row['bkf_payment_date'];
		if($cotisation!="") {
			if($cotisationPaymentDate!="") {
				$cotisationPayeCount+=1;	
			}
			else {
				$cotisationNonPayeCount+=1;
				$status.=" (&#10071;Cotisation non payée)";
			}
			$cotisation=$cotisation." €";
			$cotisationRenouveleeCount+=1;
		}
		else {
			if($blocked!=1) {
				// Web non désactivé
				$cotisation="?";
				$status.=" (&#10071;Cotisation non renouvelée)";
				$cotisationNonRenouveleeCount+=1;
			}
		}
		if($solde < 0.0) {
			$soldeTotalNegatif+=$solde;
		}
		else {
			$soldeTotalPositif+=$solde;
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
			if(strpos($member, "checked") !== false) $memberCount++;
			if(strpos($student, "checked") !== false) $studentCount++;
			if(strpos($pilot, "checked") !== false) $pilotCount++;
			if(strpos($effectif, "checked") !== false) $effectifCount++;
		}
		if($blocked == 2) $blockedCount++;
		print("<tr style='text-align: right'; $rowStyle>
			<td><input type=\"checkbox\"> $count</td>
		    <td style='text-align: right;'>$personid</td>");
		print("<td style='text-align: left;'><a class=\"tooltip\" href=\"mobile_ledger.php?user=$personid\">$odooReference<span class='tooltiptext'>Click pour afficher les opérations comptables</span></a></td>
			<td style='text-align: left;'><a class=\"tooltip\" href=\"mobile_profile.php?displayed_id=$personid\">$row[last_name]<span class='tooltiptext'>Click pour editer le profile</span></a></td>
			<td style='text-align: left;'>$row[first_name]</td>
			<td style='text-align: left;'>$address</td>
			<td style='text-align: left;'>$code</td>
			<td style='text-align: left;'>$ville</td>
			<td style='text-align: left;'>$pays</td>
			<td style='text-align: left;'>$email</td>
			<td style='text-align: center;'>$member</td>
			<td style='text-align: center;'>$student$promotionEleve</td>
			<td style='text-align: center;'>$pilot</td>
			<td style='text-align: center;'>$effectif</td>");

		// Cotisation column. Nothing if web déactivé
		if($blocked!=1) {
			if($cotisationInvoiceDate!="") {
				print("<td style='text-align: center;'>$cotisation</td>");
			}
			else {
				if($cotisation!="") {
					$cotisation="[".$cotisation."]";
				}
				$isMember = (strpos($member, "checked") !== false) ? 'true' : 'false';
				print("<td style=\"text-align: center;\" class=\"text-danger\">
				<a class=\"tooltip\" href=\"javascript:void(0);\" 
					onclick=\"createCotisationFunction('$_SERVER[PHP_SELF]','Cotisation','" .
					str_replace("'", "\\'","$nom $prenom") . "','$personid',
					$isMember)\">
					$cotisation<span class='tooltiptext'>Click pour facturer une cotisation</span>
				</a>
				</td>");			
			}
		}
		else {
			// Nothing if web déactivé
			print("<td></td>");
		}
		$soldeText="";
		$soldeStyle="";
	
		if($solde<0.0) {
			$soldeStyle=' class="text-danger" ';
		}
		if($odoo) {
			$soldeText=number_format($solde,2,",",".")." €";
		}
		print("<td $soldeStyle>$soldeText</td>");				
		if($blocked==2) {
			print("<td style='text-align: center;font-size: 17px;' class='text-danger'>
			<a class=\"tooltip\" href=\"javascript:void(0);\" onclick=\"blockFunction('$_SERVER[PHP_SELF]','Unblock','" .
				str_replace("'", "\\'","$nom $prenom") . "','$personid','$solde')\">&#x26D4;<span class='tooltiptext'>Click pour DEBLOQUER</span>
				<span class=\"badge text-bg-info\"><i class=\"bi bi-calendar3\"></i>&nbsp;$row[days_blocked]</span></a></td>");
		}
		else if($blocked==1){
			// X rouge
			print("<td style='text-align: center;font-size: 15px;' class='text-danger'>&#10060;</td>");		
		}
		else {
			print("<td style='text-align: center;font-size: 17px;' class='text-success'>
				<a class=\"tooltip\" href=\"javascript:void(0);\" onclick=\"blockFunction('$_SERVER[PHP_SELF]','Block','" .
				str_replace("'", "\\'","$nom $prenom") . "','$personid','$solde')\">&#x2714;<span class='tooltiptext'>Click pour BLOQUER</span></a></td>");
		}
		print("<td style='text-align: left;'>$invoiceDueDate$status</td>");
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
		<td><div class='text-danger'>[$cotisationNonRenouveleeCount]</div><div>/$cotisationRenouveleeCount</div></td>");
		$soldeTotalPositifText=number_format($soldeTotalPositif,2,",",".");
		$soldeTotalNegatifText=number_format($soldeTotalNegatif,2,",",".");
		print("<td style='text-align: right;'><div>+$soldeTotalPositifText €/</div><div class='text-danger'>$soldeTotalNegatifText €<div></td>");
	print("<td>$blockedCount</td>
		<td></td>
		</tr>\n");
?>
</tbody>
</table>
</div>
<?php
// Tools in PHP

// Change a user of group
function GM_SetMemberGroup($personId, $memberType, $checked) 
{
	global  $joomla_member_group,$joomla_student_group,$joomla_pilot_group,$joomla_effectif_group;
	global $userId;
	global $mysqli_link,$table_user_usergroup_map;
	//print("SetMemberGroup($personId, $memberType, $checked): Started");
	switch($memberType) {
		case 'Member':
			$groupId = $joomla_member_group;
			break;
		case 'Student':
			$groupId = $joomla_student_group;
			break;
		case 'Pilot':
			$groupId = $joomla_pilot_group;
			break;
		case 'Effectif':
			$groupId = $joomla_effectif_group;
			break;
		default:
			journalise($userId, "E", "Unknown group for checkbox toggling: " . $_REQUEST['checkboxId']) ;
			break;
	}
	if ($checked == 'true') {
		// Add to group
		mysqli_report(MYSQLI_REPORT_OFF); // Disable exceptions (could also use try-catch)
		$rc = mysqli_query($mysqli_link, "INSERT INTO $table_user_usergroup_map (user_id, group_id) 
			VALUES ($personId, $groupId)") ;
		if ($rc === FALSE)
			if (mysqli_errno($mysqli_link) == 1062) {
				// Duplicate entry, i.e., already in group
				journalise($userId, 'W', "User $personId already in group $groupId/$memberType") ;
				print("<div class=\"alert alert-warning\" role=\"alert\">
 				 User $personId is already in group $groupId/$memberType.</div>");
			} else 
				journalise($userId, 'F', "Cannot add user $personId to group $groupId/$memberType: " . mysqli_error($mysqli_link)) ;
		else {
			journalise($userId, 'I', "User $personId added to group $groupId/$memberType") ;
			print("<div class=\"alert alert-info\" role=\"alert\">
 				 User $personId added to group $groupId/$memberType.</div>");
		}
	} else if ($checked == 'false') {
		// Remove from group
		mysqli_query($mysqli_link, "DELETE FROM $table_user_usergroup_map 
			WHERE user_id = $personId AND group_id = $groupId")
			or journalise($userId, 'F', "Cannot remove user $personId from group $groupId: " . mysqli_error($mysqli_link)) ;
		journalise($userId, 'I', "User $personId removed from group $groupId/$memberType") ;
		print("<div class=\"alert alert-info\" role=\"alert\">
 			 User $personId removed from group $groupId/$memberType.</div>");
	} else {
		journalise($userId, "E", "Strange value for checkbox toggling: " . $checked) ;
	}

	// Specific use case
	//If a member become Pilot it is no more Student
	if($memberType=="Pilot" && $checked=='true') {
		if(GM_IsMemberType($personId, "Student")) {
    		GM_SetMemberGroup($personId, "Student", 'false') ;
		}
    }
	if($memberType=="Student" && $checked=='true') {
		if(GM_IsMemberType($personId, "Pilot")) {
    		GM_SetMemberGroup($personId, "Pilot", 'false') ;
		}
    }
	// TODO send an email to personId to inform him of the change
}

// Check the type of a person
function GM_IsMemberType($personId, $memberType)
{

	global $joomla_member_group,$joomla_student_group,$joomla_pilot_group,$joomla_effectif_group;
	global $userId;
	global $mysqli_link,$table_user_usergroup_map;
	//print("GM_IsMemberType($personId, $memberType): Started<br>");
	switch($memberType) {
		case 'Member':
			$groupId = $joomla_member_group;
			break;
		case 'Student':
			$groupId = $joomla_student_group;
			break;
		case 'Pilot':
			$groupId = $joomla_pilot_group;
			break;
		case 'Effectif':
			$groupId = $joomla_effectif_group;
			break;
		default:
			print("%%%ERROR:GM_IsMemberType: Unknown member type $memberType") ;
			return false;
	}
	
	$result = mysqli_query($mysqli_link, "SELECT * FROM $table_user_usergroup_map WHERE user_id=$personId AND group_id=$groupId")
    		or journalise($userId, "E", "Cannot read $table_user_usergroup_map: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
		//print("GM_IsMemberType:return true<br>");
		return true;
	}
	//print("GM_IsMemberType:return false<br>");
	return false;
}
?>
</body>
</html>