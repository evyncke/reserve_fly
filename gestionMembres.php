<?php
require_once 'dbi.php';

if (! $userIsAdmin && ! $userIsBoardMember) journalise($userId, "F", "Vous n'avez pas le droit de consulter cette page") ; // journalise with Fatal error class also stop execution
// ob_start("ob_gzhandler"); // Enable gzip compression over HTTP

?><!DOCTYPE html>
<html lang="fr">
<head>
  <title>Gestion des utilisateurs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://www.spa-aviation.be/favicon32x32.ico" rel="shortcut icon" type="image/vnd.microsoft.icon">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
  
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
  
<script type="text/javascript">
// Manage Search when keyup

// Manage Search when document loaded
$(document).ready(function() {
   $("#id_SearchInput").on("keyup", function() {
      var value = $(this).val().toLowerCase();
      $("#myTable tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
     });
    });
    var value = $("#id_SearchInput").val().toLowerCase();
      $("#myTable tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
      });
});

function parseFloatEU(s) {
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
			if (parseFloatEU(x.innerHTML) > parseFloatEU(y.innerHTML)) {
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
			if (parseFloatEU(x.innerHTML) < parseFloatEU(y.innerHTML)) {
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
			aText="Débloquer";
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
	var aPredefinedReason="Votre solde est négatif ("+theSolde+"EUR). Vous êtes donc interdit(e)s de réservation tant que le solde n'est pas réglé.";
	if(theSolde==70 || theSolde==255){
		aPredefinedReason="Vous n'êtes pas en ordre de cotisation. Vous êtes donc interdit(e)s de réservation tant que votre cotisation n'est pas réglée.";
	}
	var reason = prompt("Entrer la raison du blocage", aPredefinedReason);
	return reason;
}
</script>
<!-- Matomo -->
<script type="text/javascript">
  var _paq = window._paq = window._paq || [];
  /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
  _paq.push(['setUserId', '<?=$userName?>']);
  _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
  _paq.push(["setDomains", ["*.spa-aviation.be","*.ebsp.be","*.m.ebsp.be","*.m.spa-aviation.be","*.resa.spa-aviation.be"]]);
  _paq.push(['enableHeartBeatTimer']);
  _paq.push(['setCustomVariable', 1, "userID", <?=$userId?>, "visit"]);
  _paq.push(["setCookieDomain", "*.spa-aviation.be"]);
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="//analytics.vyncke.org/";
    _paq.push(['setTrackerUrl', u+'matomo.php']);
    _paq.push(['setSiteId', '5']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Code -->
</head>
<body>
<?php
$searchText=""; 	
if (isset($_REQUEST['search']) and $_REQUEST['search'] != '') {
	$searchText=$_REQUEST['search'];
}
if (isset($_REQUEST['block']) or isset($_REQUEST['unblock'])) {
	$personid=$_REQUEST['personid'];
	if (isset($_REQUEST['unblock']) and $_REQUEST['unblock'] == 'true') {
		//print("Unblock $personid</br>\n");
		//print("delete from $table_blocked where b_jom_id=$personid</br>\n");
		$audit_time = mysqli_real_escape_string($mysqli_link, $_REQUEST['audit_time']) ;
		mysqli_query($mysqli_link, "delete from $table_blocked where b_jom_id=$personid") 
			or journalise($userId, 'F', "Cannot delete: " . mysqli_error($mysqli_link)) ;
		if (mysqli_affected_rows($mysqli_link) > 0) {
			$insert_message = "Table blocked  mis &agrave; jour" ;
			journalise($userId, 'I', "Table_blocked entry deleted for person $personid (done at $audit_time).") ;
		} else {
			$insert_message = "Impossible d'effacer la ligne dans la table_blocked" ;
			journalise($userId, 'E', "Error (" . mysqli_error($mysqli_link). ") while deleting person entry for person $personid (done at $audit_time).") ;
		}			
		print("<p><h2><b>Le membre $personid a été débloqué</b></h2><p>");
		journalise($userId, "I", "Member $personid is now unblocked") ;
	}
	if (isset($_REQUEST['block']) and $_REQUEST['block'] == 'true') {
		$reason=urldecode($_REQUEST['reason']);
		$reason=web2db($reason);
		//print("Block $personid Reason=$reason</br>\n");
		//print("insert into $table_blocked (b_jom_id, b_reason, b_who, b_when)
		//		values ('$personid', \"$reason\", '$userId', sysdate());</br>");

		mysqli_query($mysqli_link, "insert into $table_blocked (b_jom_id, b_reason, b_who, b_when)
				values ('$personid', \"$reason\", '$userId', sysdate())")
			or journalise($userID, 'F', "Impossible d'ajouter dans les blocked: " . mysqli_error($mysqli_link)) ;
		$reason=db2web($reason);
		print("<p><h2><b>Le membre $personid a été bloqué :</b> Raison \"$reason\"</h2><p>");
		journalise($userId, "W", "Member $persid is now blocked, $reason") ;
	}
}
?>
<h1>Table des membres du RAPCS</h1>
  <p>Type something to search the table for first names, last names , ciel ref, ...</p>  
<?php	
  print("<input class=\"form-control\" id=\"id_SearchInput\" type=\"text\" placeholder=\"Search..\" value=\"$searchText\">");
?>
  <br>
  
<div class="table">
<table width="100%" style="margin-left: auto; margin-right: auto; stickyHeader: true" class="table table-striped table-hover table-bordered"> 
	<thead style="position: sticky;">
<tr style="text-align: Center;">
<th onclick="sortTable(0, true)" style="text-align: right;">#</th>
<th onclick="sortTable(1, false)">Id</th>
<th onclick="sortTable(2, false)">Ref. Ciel</th>
<th onclick="sortTable(3, false)">Nom</th>
<th onclick="sortTable(4, false)">Prénom</th>
<th onclick="sortTable(5, false)">Adresse</th>
<th onclick="sortTable(6, false)">Code</th>
<th onclick="sortTable(7, false)">Ville</th>
<th onclick="sortTable(8, false)">Pays</th>
<th onclick="sortTable(9, false)">Membre non-navigant</th>
<th onclick="sortTable(10, false)">Elève</th>
<th onclick="sortTable(11, false)">Pilote</th>
<th onclick="sortTable(12, false)">Membre Effectif</th>
<th onclick="sortTable(13, true)">Solde</th>
<th onclick="sortTable(14, false)">Status</th>
<th onclick="sortTable(15
)">Raison</th>
</tr>
</thead>
<tbody id="myTable">
<?php
// ajouter block (Pour les pilotes bloque) + inverser les soldes.
	$sql = "select distinct u.id as id, u.name as name, first_name, last_name, address, zipcode, city, country,
	ciel_code, block, bkb_amount, b_reason, u.email as email, group_concat(group_id) as groups, sum(distinct bkl_debit) as invoice_total
		from $table_users as u join $table_user_usergroup_map on u.id=user_id 
		join $table_person as p on u.id=p.jom_id
		left join $table_bk_balance on concat('400',ciel_code)=bkb_account
		left join $table_bk_ledger on bkl_client = ciel_code
		left join $table_blocked on u.id = b_jom_id
		where group_id in ($joomla_member_group, $joomla_student_group, $joomla_pilot_group, $joomla_effectif_group)
		and (bkb_date is null or bkb_date=(select max(bkb_date) from $table_bk_balance))
		and bkl_journal = 'VEN' and bkl_date between '2023-01-01' and '2023-01-31'
		group by user_id
		order by last_name, first_name" ;
		$count=0;
	$result = mysqli_query($mysqli_link, $sql)
		or journalise(0, "E", "Cannot read members: " . mysqli_error($mysqli_link)) ;
	
	$memberCount=0;
	$studentCount=0;
	$effectifCount=0;
	$pilotCount=0;
	$cielCount=0;
	$blockedCount=0;
	$soldeTotal=0.0;
	
	$CheckMark="&#9989;";
	
	while ($row = mysqli_fetch_array($result)) {
		$count++;
		$personid=$row['id'];
		$row['name'] = db2web($row['name']) ;
		if ($row['name'] === FALSE) 
			journalise(0, 'E', "There was an error while converting\n") ; 
		$row['first_name'] = db2web($row['first_name']) ;
		$row['last_name'] = db2web($row['last_name']) ;
		//$address=db2web($row['address']." ".$row['zipcode']." ".$row['city']." ".$row['country'];
		$address=db2web($row['address']);
		$code=db2web($row['zipcode']);
		$ville=db2web($row['city']);
		$pays=db2web($row['country']);
		$nom=$row['last_name'];
		if($nom == "") $nom=$row['name'];
		$prenom=$row['first_name'];		
		$groups = explode(',', $row['groups']) ;
		$effectif = (in_array($joomla_effectif_group, $groups)) ? $CheckMark : '' ;
		$pilot = (in_array($joomla_pilot_group, $groups)) ? $CheckMark : '' ;
		$student = (in_array($joomla_student_group, $groups)) ? $CheckMark : '' ;
		$status=db2web($row['b_reason']);
		$blocked=$row['block'];
		if($blocked==1) {
			$status="Web déactivé";				
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
		if($pilot == $CheckMark || $student== $CheckMark) {
			$member='';
		}
		$solde=$row['bkb_amount'];
		$solde=$solde*-1.00;
		if(abs($solde)<0.01) $solde=0.0;
		
		//SELECT * FROM `rapcs_bk_balance`.   bkb_amount
		//SELECT * FROM `rapcs_bk_balance` ORDER BY `rapcs_bk_balance`.`bkb_date` DESC
		
		$ciel="xxxxxxx";
		if($row['ciel_code'] != "") {
			$ciel="400".$row['ciel_code'];
		}

		$soldeTotal+=$solde;
		if($member == $CheckMark) $memberCount++;
		// Let's do some checks on January invoice
		if ($row['invoice_total'] == 70) {
			if ($member != $CheckMark) $status .= '<br/> ! cotisation de 70 €' ;
	 	}  
		else if ($row['invoice_total'] == 255) {
			if ($member == $CheckMark) $status .= '<br/> ! cotisation de 255 €' ;
		}
		if($student == $CheckMark) $studentCount++;
		if($pilot == $CheckMark) $pilotCount++;
		if($effectif == $CheckMark) $effectifCount++;
	    if($ciel != '') $cielCount++;
		if($blocked == 2) $blockedCount++;
		$soldeStyle='';
		$rowStyle="";
		if($solde<0.0) {
			$soldeStyle="style='color: red';";
			$rowStyle="class='warning'";
		}
		if($blocked==2) {
			$rowStyle="class='danger'";	
		}
		else if($blocked==1) {
			$rowStyle="class='info'";	
		}
		print("<tr id='$personid_row' style='text-align: right'; $rowStyle>
			<td>$count</td>
		    <td style='text-align: right;'>id$personid</td>
			<td style='text-align: left;'>$ciel</td>
			<td style='text-align: left;'>$row[last_name]</td>
			<td style='text-align: left;'>$row[first_name]</td>
			<td style='text-align: left;'>$address</td>
			<td style='text-align: left;'>$code</td>
			<td style='text-align: left;'>$ville</td>
			<td style='text-align: left;'>$pays</td>
			<td style='text-align: center;'>$member</td>
			<td style='text-align: center;'>$student</td>
			<td style='text-align: center;'>$pilot</td>
			<td style='text-align: center;'>$effectif</td>");
		if($row['ciel_code'] != '') {
			$soldeText=number_format($solde,2,",",".");
			print("<td $soldeStyle>$soldeText €</td>");				
		}
		else {			
			print("<td></td>");
		}
		if($blocked==2) {
			print("<td style='text-align: center;font-size: 17px;color: green;'>
			<a class=\"tooltip\" href=\"javascript:void(0);\" onclick=\"blockFunction('$_SERVER[PHP_SELF]','Unblock','$nom $prenom','$personid','$solde')\">&#x26D4;<span class='tooltiptext'>Click pour DEBLOQUER</span></a></td>");
		}
		else if($blocked==1){
			print("<td style='text-align: center;font-size: 15px;color: red;'>&#10060;</td>");		
		}
		else {
			print("<td style='text-align: center;font-size: 17px;color: green;'>
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
	print("<tr style='background-color: #13d8f2; text-align: center;'>
		<td>Total</td>
	    <td></td>
		<td>$cielCount</td>
		<td>$count</td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td>$memberCount</td>
		<td>$studentCount</td>
		<td>$pilotCount</td>
		<td>$effectifCount</td>");
	$soldeTotalText=number_format($soldeTotal,2,",",".");
	if($soldeTotal<0.0) {
		print("<td style='color: red;text-align: right;'>$soldeTotalText €</td>");
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
</body>
</html>