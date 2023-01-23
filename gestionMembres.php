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
$(document).ready(function(){
  $("#myInput").on("keyup", function() {
    var value = $(this).val().toLowerCase();
    $("#myTable tr").filter(function() {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
  });
});
function blockFunction(PHP_Self, theBlockedFlag, theNom, theUserId, theSolde)
{
	var aReason="";
	if(theBlockedFlag=="Block") {
		aReason=getReason(theSolde);
		if (confirm("Confirmer que vous voulez bloquer " + theNom + "?\nRaison: "+aReason) == true) {			
   		 	var aCommand=PHP_Self+"?block=true&personid="+theUserId+"&reason="+aReason;		 
   		 	window.location.href = encodeURI(aCommand);
		}
	}
	else {
		if (confirm("Confirmer que vous voulez débloquer " + theNom + "?") == true) {
			aText="Débloquer";
      		 var aCommand=PHP_Self+"?unblock=true&personid="+theUserId;
      		 window.location.href = aCommand;
		}		
	}
	//alert("Action="+aText);
}
function getReason(theSolde)
{
	var reason = prompt("Entrer la raison du blocage", "Votre solde est négatif ("+theSolde+"€). Vous êtes donc interdit(e)s de réservation tant que le solde n'est pas réglé.");
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
if (isset($_REQUEST['block']) or isset($_REQUEST['unblock'])) {
	$personid=$_REQUEST['personid'];
	if (isset($_REQUEST['unblock']) and $_REQUEST['unblock'] == 'true') {
		//print("Unblock $personid</br>\n");
		//print("delete from $table_blocked where b_jom_id=$personid</br>\n");
		$audit_time = mysqli_real_escape_string($mysqli_link, $_REQUEST['audit_time']) ;
		mysqli_query($mysqli_link, "delete from $table_blocked where b_jom_id=$personid") or die("Cannot delete: " . mysqli_error($mysqli_link)) ;
		if (mysqli_affected_rows($mysqli_link) > 0) {
			$insert_message = "Table blocked  mis &agrave; jour" ;
			journalise($userId, 'I', "Table_blocked entry deleted for person $personid (done at $audit_time).") ;
		} else {
			$insert_message = "Impossible d'effacer la ligne dans la table_blocked" ;
			journalise($userId, 'E', "Error (" . mysqli_error($mysqli_link). ") while deleting person entry for person $personid (done at $audit_time).") ;
		}			
		print("<p><h2><b>Le membre $personid a été débloqué</b></h2><p>");
	}
	if (isset($_REQUEST['block']) and $_REQUEST['block'] == 'true') {
		$reason=urldecode($_REQUEST['reason']);
		$reason=web2db($reason);
		//print("Block $personid Reason=$reason</br>\n");
		print("insert into $table_blocked (b_jom_id, b_reason, b_who, b_when)
				values ('$personid', \"$reason\", '$userId', sysdate());</br>");

		mysqli_query($mysqli_link, "insert into $table_blocked (b_jom_id, b_reason, b_who, b_when)
				values ('$personid', \"$reason\", '$userId', sysdate());")
			or die("Impossible d'ajouter dans les blocked: " . mysqli_error($mysqli_link)) ;
		$reason=db2web($reason);
		print("<p><h2><b>Le membre $personid a été bloqué :</b> Raison \"$reason\"</h2><p>");
	}
}
?>
<h1>Table des membres du RAPCS</h1>
  <p>Type something to search the table for first names, last names , ciel ref, ...</p>  
  <input class="form-control" id="myInput" type="text" placeholder="Search..">
  <br>
  
<div class="table">
<table width="100%" style="margin-left: auto; margin-right: auto; stickyHeader: true" class="table table-striped table-hover table-bordered"> 
	<thead style="position: sticky;">
<tr style="text-align: Center;">
<th style="text-align: right;">#</th>
<th>Id</th>
<th>Ref. Ciel</th>
<th>Nom</th>
<th>Prénom</th>
<th>Adresse</th>
<th>Code</th>
<th>Ville</th>
<th>Pays</th>
<th>Membre non-navigant</th>
<th>Elève</th>
<th>Pilote</th>
<th>Membre Effectif</th>
<th>Solde</th>
<th>Status</th>
<th>Raison</th>
</tr>
</thead>
<tbody id="myTable">
<?php
// ajouter block (Pour les pilotes bloque) + inverser les soldes.
	$sql = "select distinct u.id as id, u.name as name, first_name, last_name, address, zipcode, city, country,
	ciel_code, block, bkb_amount, b_reason, u.email as email, group_concat(group_id) as groups
		from $table_users as u join $table_user_usergroup_map on u.id=user_id 
		join $table_person as p on u.id=p.jom_id
		left join $table_bk_balance on concat('400',ciel_code)=bkb_account
		left join $table_blocked on u.id = b_jom_id
		where group_id in ($joomla_member_group, $joomla_student_group, $joomla_pilot_group, $joomla_effectif_group)
		and (bkb_date is null or bkb_date=(select max(bkb_date) from $table_bk_balance))
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
		if($status=="") {
			if($blocked!=0) {
				$status="Web blocked";	
				$blocked=2;
			}
			else {
				//$blocked='&#x2714;';
				$blocked=0;
			}
		}
		else {
			//$blocked='&#x26D4;';
			$blocked=1;
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
		
		$ciel="000000";
		if($row['ciel_code'] != "") {
			$ciel="400".$row['ciel_code'];
		}

		$soldeTotal+=$solde;
		if($member == $CheckMark) $memberCount++;
		if($student == $CheckMark) $studentCount++;
		if($pilot == $CheckMark) $pilotCount++;
		if($effectif == $CheckMark) $effectifCount++;
	    if($ciel != '') $cielCount++;
		if($blocked == 1) $blockedCount++;
		$soldeStyle='';
		$rowStyle="";
		if($solde<0.0) {
			$soldeStyle="style='color: red';";
			$rowStyle="class='warning'";
		}
		if($blocked==1) {
			$rowStyle="class='danger'";	
		}
		else if($blocked==2) {
			$rowStyle="class='table-primary'";	
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
				print("<td $soldeStyle>$solde €</td>");				
		}
		else {			
			print("<td></td>");
		}
		if($blocked==1) {
			print("<td style='text-align: center;font-size: 17px;color: green;'>
			<a class=\"tooltip\" href=\"javascript:void(0);\" onclick=\"blockFunction('$_SERVER[PHP_SELF]','Unblock','$nom $prenom','$personid','$solde')\">&#x26D4;<span class='tooltiptext'>Click pour DEBLOQUER</span></a></td>");
		}
		else if($blocked==2){
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
	if($soldeTotal<0.0) {
		print("<td style='color: red;text-align: right;'>$soldeTotal €</td>");
	}
	else {
		print("<td style='text-align: right;'>$soldeTotal €</td>");		
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