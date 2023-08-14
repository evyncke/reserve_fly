<?php
/*
   Copyright 2014-2023 Eric Vyncke, Patrick Reginster

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

ob_start("ob_gzhandler");
require_once "dbi.php" ;
require_once 'facebook.php' ;

MustBeLoggedIn() ;

$plane="OO-ALD";
$since = mysqli_real_escape_string($mysqli_link, $_REQUEST['since']) ;
if ($since == '')
	$since = date('Y-m-01') ;

$sinceDate = new DateTime($since) ;
$monthAfter = new DateTime($since) ;
$monthAfterForTitle = new DateTime($since) ;
$monthBefore = new DateTime($since) ;
$monthInterval = new DateInterval('P1M') ; // One month
$monthBefore = $monthBefore->sub($monthInterval) ;
$monthBeforeString = $monthBefore->format('Y-m-d') ;
$monthAfter = $monthAfter->add($monthInterval) ; // Then request is from 01-01-2023 0h00 to 01-02-2023 0h00
//$monthAfter = $monthAfter->sub(new DateInterval('P1D')) ; 
$monthAfterString = $monthAfter->format('Y-m-d') ;
$monthAfterForTitle = $monthAfterForTitle->add($monthInterval) ;
$monthAfterForTitle = $monthAfterForTitle->sub(new DateInterval('P1D')) ;
$monthAfterForTitleString = $monthAfterForTitle->format('Y-m-d') ; // Then Title is 31-01-2023 and not 01-02-2023
$mounthName=$sinceDate->format('F') ;

?><html>
<head>
<link rel="stylesheet" type="text/css" href="log.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<title>Folio du mois vols IF-INIT</title>
<script src="members.js"></script>
<script src="shareCodes.js"></script>
<script>
const
	// preset Javascript constant fill with the right data from db.php PHP variables
	userFullName = '<?=$userFullName?>' ;
	userName = '<?=$userName?>' ;
	userId = <?=$userId?> ;
	userIsPilot = <?=($userIsPilot)? 'true' : 'false'?> ;
	userIsAdmin = <?=($userIsAdmin)? 'true' : 'false'?> ;
	userIsInstructor = <?=($userIsInstructor)? 'true' : 'false'?> ;
	userIsMechanic = <?=($userIsMechanic)? 'true' : 'false'?> ;

function planeChanged(elem) {
	window.location.href = '<?=$_SERVER['PHP_SELF']?>?plane=' + elem.value + '&since=<?=$_REQUEST['since']?>';
}

function findMember(a, m) {
	for (let i = 0 ; i < a.length ; i++)
		if (a[i].id == m)
			return a[i].name ;
	return null ;
}

function init() {
	var planeSelect = document.getElementById('planeSelect') ;
	if (planeSelect) planeSelect.value = '<?=$plane?>' ;
	var collection = document.getElementsByClassName("shareCodeClass") ;
	for (let i = 0; i < collection.length ; i++) {
		var spanElem = collection[i] ;
		var member = spanElem.innerText ;
		memberText = findMember(shareCodes, member) ;
		if (memberText == null)
			memberText = findMember(members, member) ;
		if (memberText != null)
			spanElem.innerText = ' (' + memberText + ')';
	}
}
</script>
<!-- Matomo -->
<script type="text/javascript">
  var _paq = window._paq = window._paq || [];
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
<body onload="init();">
<center><h2>Folio du mois: vols IF-INIT du <?=$since?> au <?=$monthAfterForTitleString?></h2></center>
<?php
print("Mois: <b><a href=$_SERVER[PHP_SELF]?plane=$plane&since=$monthBeforeString>&lt;</a>&nbsp; $mounthName &nbsp;<a href=$_SERVER[PHP_SELF]?since=$monthAfterString>&gt;</a></b></br>\n") ;
?>
<br/>
<p><b>Paiement effectués mois <?=$mounthName?></b></p>
<table class="logTable">
<thead>
<tr>
<th class="logHeader">Date Paiement</th>
<th class="logHeader">Référence</th>
<th class="logHeader">Type</th>
<th class="logHeader">Moyen Paiement</th>
<th class="logHeader">Montant</th>
<th class="logHeader">Référence du Paiement</th>
</tr>
</thead>
<tbody>
	<?php
	$filterType = '' ;
	$filter = ' ' ;
	$date_filter = " AND f_date_paid >= '" . $since . "' AND f_date_paid <= '" . $monthAfterString. "'";
	/*
	$result = mysqli_query($mysqli_link, "SELECT *, SYSDATE() as today 
		FROM $table_flight JOIN $table_pax_role ON f_id = pr_flight JOIN $table_pax ON pr_pax = p_id 
		WHERE  pr_role = 'C' $filterType $filter $date_filter 
		ORDER BY f_date_paid") 
		or journalise($userId, "F", "Impossible de lister les vols: " . mysqli_error($mysqli_link));
		
	while ($row = mysqli_fetch_array($result)) {
		$reference = db2web($row['f_reference'])."<a href=\"https://www.spa-aviation.be/resa/flight_create.php?flight_id=$row[f_id]\" title=\"Go to reservation $row[f_reference]\" target=\"_blank\">&boxbox;</a>";
		$date=$row['f_date_paid'];
		$datePaiement=$date." 12:00:00";
		$datePaiement=gmdate('d/m/Y', strtotime($datePaiement)) ;
		$type=$row['f_type'];
		$gift=$row['f_gift'];
		$dateFlown="";
		if(isset($row['f_date_flown'])) {
			$dateFlown=gmdate('Y-m-d', strtotime("$row[f_date_flown]")) ;
		}
		$typeVol="";
		$moyenPaiement="";
		if($gift==1) {
			$typeVol=$typeVol."Bon ";
		}
		else {
			$typeVol=$typeVol."Vol ";
		}
		if($type=="I") {
			$typeVol=$typeVol."INIT ";
		}
		else if($type=="D") {
			$typeVol=$typeVol."IF ";
		}
		else {
			$typeVol=$typeVol."? ";
		}
		
		if($dateFlown == $date) {
			$moyenPaiement="Bancontact";
		}
		else {
			$moyenPaiement="Virement";			
		}
		//$moyenPaiement=$moyenPaiement." ".$dateFlown."---".$date;
		$montant=$row['f_amount_payment'];
		$referencePaiement=db2web($row['f_reference_payment']);		
		if($referencePaiement=="Facture DHF") {
			$montant=0.00;
			$moyenPaiement="Facture";
			$referencePaiement="Facture DHF";
		}
		
		print("<tr><td class=\"logCell\">$datePaiement</td><td class=\"logCell\">$reference</td class=\"logCell\"><td class=\"logCell\">$typeVol</td><td class=\"logCell\">$moyenPaiement</td><td class=\"logCell\">$montant</td><td class=\"logCell\">$referencePaiement</td></tr>");
	}
	*/
	$date_filter_ledger = " fl_date >= '" . $since . "' AND fl_date <= '" . $monthAfterString. "'";

	$resultLedger = mysqli_query($mysqli_link, "SELECT *, SYSDATE() as today 
				FROM $table_flight JOIN $table_flights_ledger ON f_id = fl_flight 
				WHERE $date_filter_ledger 
				ORDER BY fl_date") 
			or journalise($userId, "F", "Impossible de lister les vols avec ledger: " . mysqli_error($mysqli_link));
	
	$totalMontant=0.0;
	while ($row = mysqli_fetch_array($resultLedger)) {
		$reference = db2web($row['f_reference'])."<a href=\"https://www.spa-aviation.be/resa/flight_create.php?flight_id=$row[f_id]\" title=\"Go to reservation $row[f_reference]\" target=\"_blank\">&boxbox;</a>";
		$date=$row['fl_date'];
		$datePaiement=$date." 12:00:00";
		$datePaiement=gmdate('d/m/Y', strtotime($datePaiement)) ;
		$type=$row['f_type'];
		$gift=$row['f_gift'];
		$dateFlown="";
		if(isset($row['f_date_flown'])) {
			$dateFlown=gmdate('Y-m-d', strtotime("$row[f_date_flown]")) ;
		}
		$typeVol="";
		$moyenPaiement="";
		if($gift==1) {
			$typeVol=$typeVol."Bon ";
		}
		else {
			$typeVol=$typeVol."Vol ";
		}
		if($type=="I") {
			$typeVol=$typeVol."INIT ";
		}
		else if($type=="D") {
			$typeVol=$typeVol."IF ";
		}
		else {
			$typeVol=$typeVol."? ";
		}
		$referencePaiement=db2web($row['fl_reference']);	
		$pos = strpos(strtoupper($referencePaiement), "FACTURE");
		if($pos===false) {
			if($dateFlown == $date) {
				$moyenPaiement="Bancontact";
			}
			else {
				$moyenPaiement="Virement";			
			}
		}
		else {
			$moyenPaiement="Facture";
		}
		$montant=$row['fl_amount'];
		$totalMontant+=$montant;
	
		
		print("<tr><td class=\"logCell\">$datePaiement</td><td class=\"logCell\">$reference</td class=\"logCell\"><td class=\"logCell\">$typeVol</td><td class=\"logCell\">$moyenPaiement</td><td class=\"logCell\">$montant</td><td class=\"logCell\">$referencePaiement</td></tr>");
	}
	$totalMontantText=number_format($totalMontant, 2, '.', ' ') ;
	print("<tr><td class=\"logCell\"><b>Total</b></td><td class=\"logCell\"></td class=\"logCell\"><td class=\"logCell\"></td><td class=\"logCell\"></td><td class=\"logCell\"><b>$totalMontantText</b></td><td class=\"logCell\"></td></tr>");
	
	?>
</tbody>
</table>
<!-- Vols INIT -->
<br>
<p><b>Vols INIT effectués mois <?=$mounthName?></b></p>
<table class="logTable">
<thead>
<tr>
<th class="logHeader">Date Vol</th>
<th class="logHeader">Référence</th>
<th class="logHeader">Avion</th>
<th class="logHeader">Pilote</th>
<th class="logHeader">Prix du vol</th>
<th class="logHeader">Référence Ciel</th>
</tr>
</thead>
<tbody>
	<?php
	// JOIN $table_bookings AS b ON f.f_booking = b.r_id
	$filterType = ' AND f_type ="I"' ;
	$filter = ' AND f_date_flown IS NOT NULL' ;
	$date_filter = " AND f_date_flown >= '" . $since . "' AND f_date_flown <= '" . $monthAfterString. "'";
	/*
	$result = mysqli_query($mysqli_link, "SELECT *, SYSDATE() as today 
		FROM $table_flight AS f  
		JOIN $table_person ON f.f_pilot = jom_id
		JOIN $table_logbook AS l ON f.f_booking = l.l_booking
		WHERE true $filterType $filter $date_filter 
		GROUP BY f_id
		ORDER BY f_date_flown") 
		or journalise($userId, "F", "Impossible de lister les vols: " . mysqli_error($mysqli_link));
	while ($row = mysqli_fetch_array($result)) {
		$reference = db2web($row['f_reference'])."<a href=\"https://www.spa-aviation.be/resa/flight_create.php?flight_id=$row[f_id]\" title=\"Go to reservation $row[f_reference]\" target=\"_blank\">&boxbox;</a>";
		$date=$row['f_date_flown'];
		$date=gmdate('d/m/Y', strtotime("$row[f_date_flown]")) ;
		$plane=$row['l_plane'];
		$pilote=db2web($row['first_name'])." ".db2web($row['last_name']) ;
		$montant=$row['f_amount_payment'];
		$referenceCiel="400R008 (INIT)";
		print("<tr><td class=\"logCell\">$date</td><td class=\"logCell\">$reference</td><td class=\"logCell\">$plane</td><td class=\"logCell\">$pilote</td><td class=\"logCell\">$montant</td><td class=\"logCell\">$referenceCiel</td></tr>");
	}
	*/
	$result = mysqli_query($mysqli_link, "SELECT DISTINCT f_reference, f_date_flown, first_name, last_name, l_plane, fl_amount as prix 
		FROM $table_flight AS f  
		JOIN $table_person ON f.f_pilot = jom_id
		JOIN $table_logbook AS l ON f.f_booking = l.l_booking
		JOIN $table_flights_ledger ON f_id = fl_flight
		WHERE true $filterType $filter $date_filter 
		ORDER BY f_date_flown") 
		or journalise($userId, "F", "Impossible de lister les vols: " . mysqli_error($mysqli_link));
	$montantTotal=0.0;
	while ($row = mysqli_fetch_array($result)) {
			$reference = db2web($row['f_reference'])."<a href=\"https://www.spa-aviation.be/resa/flight_create.php?flight_id=$row[f_id]\" title=\"Go to reservation $row[f_reference]\" target=\"_blank\">&boxbox;</a>";
			$date=$row['f_date_flown'];
			$date=gmdate('d/m/Y', strtotime("$row[f_date_flown]")) ;
			$plane=$row['l_plane'];
			$pilote=db2web($row['first_name'])." ".db2web($row['last_name']) ;
			$montant=$row['prix'];
			$montantTotal+=$montant;
			$referenceCiel="400R008 (INIT)";
			print("<tr><td class=\"logCell\">$date</td><td class=\"logCell\">$reference</td><td class=\"logCell\">$plane</td><td class=\"logCell\">$pilote</td><td class=\"logCell\">$montant</td><td class=\"logCell\">$referenceCiel</td></tr>");
	}
	$montantTotalText=number_format($montantTotal, 2, '.', ' ') ;
	print("<tr><td class=\"logCell\"><b>Total</b></td><td class=\"logCell\"></td><td class=\"logCell\"></td><td class=\"logCell\"></td><td class=\"logCell\"><b>$montantTotalText</b></td><td class=\"logCell\"></td></tr>");
	?>
</tbody>
</table>
<!-- Vols IF -->
<br>
<p><b>Vols IF effectués mois <?=$mounthName?></b></p>
<table class="logTable">
<thead>
<tr>
<th class="logHeader">Date Vol</th>
<th class="logHeader">Référence</th>
<th class="logHeader">Avion</th>
<th class="logHeader">Pilote</th>
<th class="logHeader">Prix du vol</th>
<th class="logHeader">Référence Ciel</th>
</tr>
</thead>
<tbody>
	<?php
	//WHERE pr_role = 'C'$deleted_filter $completed_filter $other_filter 
	//print("Filter=".$other_filter.",/br>");

	$filterType = ' AND f_type ="D"' ;
	$filter = ' AND f_date_flown IS NOT NULL' ;
	$date_filter = " AND f_date_flown >= '" . $since . "' AND f_date_flown <= '" . $monthAfterString. "'";
	/*
	$result = mysqli_query($mysqli_link, "SELECT *, SYSDATE() as today 
		FROM $table_flight AS f  
		JOIN $table_person ON f.f_pilot = jom_id
		JOIN $table_logbook AS l ON f.f_booking = l.l_booking
		WHERE true $filterType $filter $date_filter 
		GROUP BY f_id
		ORDER BY f_date_flown") 
		or journalise($userId, "F", "Impossible de lister les vols: " . mysqli_error($mysqli_link));
	while ($row = mysqli_fetch_array($result)) {
		$reference = db2web($row['f_reference'])."<a href=\"https://www.spa-aviation.be/resa/flight_create.php?flight_id=$row[f_id]\" title=\"Go to reservation $row[f_reference]\" target=\"_blank\">&boxbox;</a>";
		$date=$row['f_date_flown'];
		$date=gmdate('d/m/Y', strtotime("$row[f_date_flown]")) ;
		$plane=$row['l_plane'];
		$pilote=db2web($row['first_name'])." ".db2web($row['last_name']) ;
		$montant=$row['f_amount_payment'];
		$referenceCiel="400R006 (IF)";
		$referencePaiement=db2web($row['f_reference_payment']);
		if($referencePaiement=="Facture DHF") {
			$referenceCiel="400DHF (IF)";
		}
		print("<tr><td class=\"logCell\">$date</td><td class=\"logCell\">$reference</td><td class=\"logCell\">$plane</td><td class=\"logCell\">$pilote</td><td class=\"logCell\">$montant</td><td class=\"logCell\">$referenceCiel</td></tr>");
	}
	*/
	$result = mysqli_query($mysqli_link, "SELECT *, SUM(fl_amount) as prix, SYSDATE() as today 
		FROM $table_flight AS f  
		JOIN $table_person ON f.f_pilot = jom_id
		JOIN $table_logbook AS l ON f.f_booking = l.l_booking
		JOIN $table_flights_ledger ON f_id = fl_flight
		WHERE true $filterType $filter $date_filter 
		GROUP BY f_id
		ORDER BY f_date_flown") 
		or journalise($userId, "F", "Impossible de lister les vols: " . mysqli_error($mysqli_link));
	$montantTotal=0.0;
	while ($row = mysqli_fetch_array($result)) {
			$reference = db2web($row['f_reference'])."<a href=\"https://www.spa-aviation.be/resa/flight_create.php?flight_id=$row[f_id]\" title=\"Go to reservation $row[f_reference]\" target=\"_blank\">&boxbox;</a>";
			$date=$row['f_date_flown'];
			$date=gmdate('d/m/Y', strtotime("$row[f_date_flown]")) ;
			$plane=$row['l_plane'];
			$pilote=db2web($row['first_name'])." ".db2web($row['last_name']) ;
			$montant=$row['prix'];
			$montantTotal+=$montant;
			$referencePaiement=db2web($row['fl_reference']);	
			$pos = strpos(strtoupper($referencePaiement), "FACTURE DHF");
			if($pos===false) {
				$referenceCiel="400R006 (IF)";
			}
			else {
				$referenceCiel="400DHF (IF)";
			}			
			print("<tr><td class=\"logCell\">$date</td><td class=\"logCell\">$reference</td><td class=\"logCell\">$plane</td><td class=\"logCell\">$pilote</td><td class=\"logCell\">$montant</td><td class=\"logCell\">$referenceCiel</td></tr>");
	}
	$montantTotalText=number_format($montantTotal, 2, '.', ' ') ;
	print("<tr><td class=\"logCell\"><b>Total</b></td><td class=\"logCell\"></td><td class=\"logCell\"></td><td class=\"logCell\"></td><td class=\"logCell\"><b>$montantTotalText</b></td><td class=\"logCell\"></td></tr>");
	
	?>
	
</tbody>
</table>
<?php
$version_php = date ("Y-m-d H:i:s.", filemtime('planelog.php')) ;
$version_css = date ("Y-m-d H:i:s.", filemtime('log.css')) ;
?>
<hr>
<div class="copyright">R&eacute;alisation: Eric Vyncke, janvier 2015 - mai 2023, pour RAPCS, Royal A&eacute;ro Para Club de Spa<br>
Versions: PHP=<?=$version_php?>, CSS=<?=$version_css?></div>
</body>
</html>

