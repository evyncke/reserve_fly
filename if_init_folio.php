<?php
/*
   Copyright 2014-2026 Eric Vyncke, Patrick Reginster

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
require_once 'flight_header.php' ;
require_once "dbi.php" ;
require_once "odooFlight.class.php" ;

if (!($userIsFlightManager or $userIsAdmin or $userIsBoardMember)) {
	journalise($userId, "F", "Vous n'êtes pas autorisé(e) sur cette page: il faut avoir les bons droits d'accès.") ;
}

$since = "";
if (isset($_REQUEST['since'])) $since =$_REQUEST['since'];
$since = mysqli_real_escape_string($mysqli_link, $since) ;
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
// Month name in the local language (French) via the system locale
// Note: strftime() is deprecated since PHP 8.1 (and will be removed in PHP 9): the deprecation notice is silenced
setlocale(LC_TIME, 'fr_BE.UTF-8', 'fr_FR.UTF-8', 'fr_BE.utf8', 'fr_FR.utf8', 'fr_BE', 'fr_FR', 'fr') ;
$monthName = @strftime('%B', $sinceDate->getTimestamp()) ;
if ($monthName === false or $monthName === '')
	$monthName = $sinceDate->format('F') ; // Fallback to English
else if (!mb_check_encoding($monthName, 'UTF-8'))
	$monthName = mb_convert_encoding($monthName, 'UTF-8', 'ISO-8859-1') ; // Latin-1 locales
$monthYear = $monthName . ' ' . $sinceDate->format('Y') ;
$alreadyInvoicedDate=new DateTime(Date("2024-05-01"));
$alreadyInvoicedDateString = $alreadyInvoicedDate->format('Y-m-d') ;
//print("alreadyInvoicedDateString=$alreadyInvoicedDateString <br>");

// Actions
if (isset($_REQUEST['action']) and $_REQUEST['action'] == "createfactureif") {
	$flightReference = $_REQUEST['reference'] ;
    $date=$_REQUEST['date'] ;
    $logbookid=$_REQUEST['logbookid'] ;
    $montant=$_REQUEST['montant'] ;
    $flyid=$_REQUEST['flyid'] ;
    if(OF_createFactureIF($flightReference, $date, $logbookid, $montant, $flyid)) {
    	print("<div class=\"alert alert-success\" role=\"alert\">Create Facture IF Reference:" . cleanHTML($flightReference) . ": DONE</div>");
    }
    else {
	    print("<div class=\"alert alert-danger\" role=\"alert\">Error to create Facture IF Reference:" . cleanHTML($flightReference) . "</div>");
    }
}

if (isset($_REQUEST['action']) and $_REQUEST['action'] == "createfactureinit") {
	$flightReference = $_REQUEST['reference'] ;
    $date=$_REQUEST['date'] ;
    $logbookid=$_REQUEST['logbookid'] ;
    $montant=$_REQUEST['montant'] ;
    $flyid=$_REQUEST['flyid'] ;
    if(OF_createFactureINIT($flightReference, $date, $logbookid, $montant, $flyid)) {
    	print("<div class=\"alert alert-success\" role=\"alert\">Create Facture INIT Reference:" . cleanHTML($flightReference) . ": DONE</div>");
    }
    else {
	    print("<div class=\"alert alert-danger\" role=\"alert\">Error to create Facture INIT Reference:" . cleanHTML($flightReference) . "</div>");
    }
}

if (isset($_REQUEST['action']) and $_REQUEST['action'] == "createfacturedhf") {
    $sinceDate=$_REQUEST['since'] ;
    $references= $_REQUEST['references'] ;
    $logbookids= $_REQUEST['logbookids'] ;
    if(OF_createFactureDHF($references, $sinceDate, $logbookids)) {
    	print("<div class=\"alert alert-success\" role=\"alert\">Create Facture DHF References:" . cleanHTML($references) . ": DONE</div>");
    }
    else {
	    print("<div class=\"alert alert-danger\" role=\"alert\">Error to create Facture DHF References:" . cleanHTML($references) . "</div>");
    }
}

?>

<script>
function createFactureIFFunction(PHP_Self,theSince, theReferenceFlight, theDate, theLogbookID, theMontant, theFlightID) {
	if (confirm("Confirmer que vous voulez crér une facture pour le vol IF  "+theReferenceFlight ) == true) {			
	 	var aCommand=PHP_Self+"?since="+theSince+"&action=createfactureif&reference=" + theReferenceFlight + 
        "&date=" + theDate +
        "&logbookid=" + theLogbookID +
        "&montant=" + theMontant +
        "&flyid="+theFlightID;
	 	window.location.href = encodeURI(aCommand);
	}
}

function createFactureINITFunction(PHP_Self,theSince, theReferenceFlight, theDate, theLogbookID, theMontant, theFlightID) {
	if (confirm("Confirmer que vous voulez crér une facture pour le vol INIT  "+theReferenceFlight ) == true) {			
	 	var aCommand=PHP_Self+"?since="+theSince+"&action=createfactureinit&reference=" + theReferenceFlight + 
        "&date=" + theDate +
        "&logbookid=" + theLogbookID +
        "&montant=" + theMontant +
        "&flyid="+theFlightID;
	 	window.location.href = encodeURI(aCommand);
	}
}

function createFactureDHFFunction(PHP_Self,theSince, theReferences, theLogbookids) {
	if (confirm("Confirmer que vous voulez crér une facture mensuelle pour les vols DHF  "+ theReferences ) == true) {			
	 	var aCommand=PHP_Self+"?since="+theSince+"&action=createfacturedhf&references=" + theReferences+"&logbookids="+theLogbookids;
	 	window.location.href = encodeURI(aCommand);
	}
}
 
</script>

<div class="pb-2 mb-3 border-bottom">
<h3 class="text-center">Folio du mois: vols IF-INIT du <?=cleanHTML($since)?> au <?=$monthAfterForTitleString?></h3>
</div><!-- page header -->
<?php
$self = cleanHTML($_SERVER['SCRIPT_NAME']) ;
?>
<div class="d-flex align-items-center gap-2 mb-3">
	<strong>Mois:</strong>
	<a class="btn btn-outline-secondary btn-sm" href="<?=$self?>?since=<?=$monthBeforeString?>" aria-label="Mois précédent"><i class="bi bi-chevron-left"></i></a>
	<strong><?=cleanHTML($monthYear)?></strong>
	<a class="btn btn-outline-secondary btn-sm" href="<?=$self?>?since=<?=$monthAfterString?>" aria-label="Mois suivant"><i class="bi bi-chevron-right"></i></a>
</div>
<h4 class="mt-4">Paiements effectués mois <?=$monthName?></h4>
<div class="table-responsive"><table class="table table-bordered table-striped table-sm align-middle w-auto text-center">
<thead class="table-info">
<tr>
<th scope="col">Date Paiement</th>
<th scope="col">Référence</th>
<th scope="col">Type</th>
<th scope="col">Moyen Paiement</th>
<th scope="col">Montant</th>
<th scope="col">Référence du Paiement</th>
</tr>
</thead>
<tbody>
	<?php
	$filterType = '' ;
	$filter = ' ' ;
	$date_filter = " AND f_date_paid >= '" . $since . "' AND f_date_paid <= '" . $monthAfterString. "'";

	$date_filter_ledger = " fl_date >= '" . $since . "' AND fl_date <= '" . $monthAfterString. "'";

	$resultLedger = mysqli_query($mysqli_link, "SELECT *, SYSDATE() as today 
				FROM $table_flight JOIN $table_flights_ledger ON f_id = fl_flight 
				WHERE $date_filter_ledger 
				ORDER BY fl_date") 
			or journalise($userId, "F", "Impossible de lister les vols avec ledger: " . mysqli_error($mysqli_link));
	
	$totalMontant=0.0;
	while ($row = mysqli_fetch_array($resultLedger)) {

        $referenceText=$row['f_reference'];
		$reference = db2web($row['f_reference'])."<a href=\"" . SITE_URL . "flight_create.php?flight_id=$row[f_id]\" title=\"Go to reservation $row[f_reference]\" target=\"_blank\"><i class=\"bi bi-box-arrow-up-right\"></i></a>";
		$date=$row['fl_date'];
		$datePaiement=$date." 12:00:00";
		$datePaiement=gmdate('d/m/Y', strtotime($datePaiement)) ;
		$type=$row['f_type'];
		$gift=$row['f_gift'];
        $odooreference=$row['fl_odoo_payment_id'];
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
        $checkReferencePaiement="";
		$referencePaiement=db2web($row['fl_reference']);	
        if($odooreference!="") {
            $moyenPaiement="Virement CBC";
        }
        else {		
    	    $pos = strpos(strtoupper($referencePaiement), "TERMINAL");
    	    //if($pos===false) {
			if($typeVol=="Bon") {
				$moyenPaiement=$referencePaiement;
                $checkReferencePaiement="<br><b class=\"text-danger\">Pas encore de référence odoo</b>";
    	    }
    	    else {
    	      if($pos===false) {
				$moyenPaiement="";
			  }
			  else {
				$moyenPaiement="Bancontact";
			  }
    	    }
        }
		$montant=$row['fl_amount'];
		$totalMontant+=$montant;
        
        $checkDateFlown="";
		$pos = strpos(strtoupper($referenceText), "V-");
		if($pos===false) {
            if($dateFlown=="") {
               $checkDateFlown="<b class=\"text-danger\"><br>Le vol ne semble pas être correctement cloturé.<br>Pas de date de vol associé</b>" ;
            }
	    }
        		
		print("<tr><td>$datePaiement</td><td>$reference</td><td>$typeVol</td><td>$moyenPaiement</td><td>$montant</td><td>$referencePaiement $checkDateFlown $checkReferencePaiement</td></tr>");
	}
	$totalMontantText=number_format($totalMontant, 2, '.', ' ') ;
	print("<tr><td><b>Total</b></td><td></td><td></td><td></td><td><b>$totalMontantText</b></td><td></td></tr>");
	
	?>
</tbody>
</table></div>
<!-- Vols INIT -->
<h4 class="mt-4">Vols INIT effectués mois <?=$monthName?></h4>
<div class="table-responsive"><table class="table table-bordered table-striped table-sm align-middle w-auto text-center">
<thead class="table-info">
<tr>
<th scope="col">Date Vol</th>
<th scope="col">Référence</th>
<th scope="col">Avion</th>
<th scope="col">Pilote</th>
<th scope="col">Prix du vol</th>
<th scope="col">Facture Odoo</th>
</tr>
</thead>
<tbody>
<?php
	$filterType = ' AND f_type ="I"' ;
	$filter = ' AND f_date_flown IS NOT NULL' ;
	$date_filter = " AND f_date_flown >= '" . $since . "' AND f_date_flown <= '" . $monthAfterString. "'";

	$result = mysqli_query($mysqli_link, "SELECT DISTINCT l_id, f_invoice_ref, l_plane, f_reference, f_date_flown, first_name, last_name, r_plane, f_id, fl_reference, fl_odoo_payment_id, sum(fl_amount) as prix 
			FROM $table_flight AS f  
			JOIN $table_person ON f.f_pilot = jom_id
			JOIN $table_bookings AS b ON f.f_booking = b.r_id
			JOIN $table_flights_ledger ON f_id = fl_flight
			JOIN $table_logbook AS l ON f.f_booking = l.l_booking
            WHERE true $filterType $filter $date_filter 
			GROUP BY f_reference, f_id
			ORDER BY f_date_flown") 
			or journalise($userId, "F", "Impossible de lister les vols INIT: " . mysqli_error($mysqli_link));


	$montantTotal=0.0;
	while ($row = mysqli_fetch_array($result)) {
        $invoiceRef=$row['f_invoice_ref'];
        $referenceFlight=$row['f_reference'];
        
		$reference = db2web($row['f_reference'])."<a href=\"" . SITE_URL . "flight_create.php?flight_id=$row[f_id]\" title=\"Go to reservation $row[f_reference]\" target=\"_blank\"><i class=\"bi bi-box-arrow-up-right\"></i></a>";
		$date=$row['f_date_flown'];
		$date=gmdate('d/m/Y', strtotime("$row[f_date_flown]")) ;
		$plane=$row['l_plane'];
		$pilote=db2web($row['first_name'])." ".db2web($row['last_name']) ;
		$montant=$row['prix'];
		$montantTotal+=$montant;
		//$referenceOdoo="Créer Facture INIT";
        
		$referencePaiement=db2web($row['fl_reference']);	
		$referencePaiementOdoo=$row['fl_odoo_payment_id'];	
        $flyid=$row['f_id'];
        $logbookID=$row['l_id'];	
        
        
		print("<tr><td>$date</td><td>$reference</td><td>$plane</td><td>$pilote</td><td>$montant</td>");
        //****************************************************************************
            
        if($invoiceRef>0 || ($alreadyInvoicedDateString > $since)) {
            // Invoice already created
            print("<td>Déjà facturé</td>");
        }
        else {
            print("<td><a class=\"btn btn-sm btn-primary\" href=\"javascript:void(0);\" onclick=\"createFactureINITFunction('$_SERVER[PHP_SELF]', '$since', '$referenceFlight', '$date', '$logbookID', '$montant','$flyid')\">Créer Facture INIT</a></td>");
        }
        print("</tr>");
	}
	$montantTotalText=number_format($montantTotal, 2, '.', ' ') ;
	print("<tr><td><b>Total</b></td><td></td><td></td><td></td><td><b>$montantTotalText</b></td><td></td></tr>");
    
    
    
    /*
	$result = mysqli_query($mysqli_link, "SELECT DISTINCT f_reference, f_date_flown, first_name, last_name, r_plane, f_id, fl_reference, sum(fl_amount) as prix 
			FROM $table_flight AS f  
			JOIN $table_bookings AS b ON f.f_booking = b.r_id
			JOIN $table_person ON f.f_pilot = jom_id
			LEFT JOIN $table_flights_ledger ON f_id = fl_flight
*/
?>
</tbody>
</table></div>
<!-- Vols IF -->
<h4 class="mt-4">Vols IF effectués mois <?=$monthName?></h4>
<div class="table-responsive"><table class="table table-bordered table-striped table-sm align-middle w-auto text-center">
<thead class="table-info">
<tr>
<th scope="col">Date Vol</th>
<th scope="col">Référence</th>
<th scope="col">Avion</th>
<th scope="col">Pilote</th>
<th scope="col">Prix du vol</th>
<th scope="col">Facture odoo</th>
</tr>
</thead>
<tbody>
	<?php
	//WHERE pr_role = 'C'$deleted_filter $completed_filter $other_filter 
	//print("Filter=".$other_filter.",/br>");

	$filterType = ' AND f_type ="D"' ;
	$filter = ' AND f_date_flown IS NOT NULL' ;
	$date_filter = " AND f_date_flown >= '" . $since . "' AND f_date_flown <= '" . $monthAfterString. "'";
	
	$result = mysqli_query($mysqli_link, "SELECT DISTINCT l_id, f_invoice_ref, l_plane, f_reference, f_date_flown, first_name, last_name, r_plane, f_id, fl_reference, fl_odoo_payment_id, sum(fl_amount) as prix 
			FROM $table_flight AS f  
			JOIN $table_person ON f.f_pilot = jom_id
			JOIN $table_bookings AS b ON f.f_booking = b.r_id
			JOIN $table_flights_ledger ON f_id = fl_flight
			JOIN $table_logbook AS l ON f.f_booking = l.l_booking
			WHERE true $filterType $filter $date_filter 
			GROUP BY f_reference
			ORDER BY f_date_flown") 
			or journalise($userId, "F", "Impossible de lister les vols IF: " . mysqli_error($mysqli_link));

	$montantTotal=0.0;
	while ($row = mysqli_fetch_array($result)) {
        $invoiceRef=$row['f_invoice_ref'];
        $referenceFlight=$row['f_reference'];
		$pos = strpos(strtoupper($referenceFlight), "DHF-");
		if($pos===false) {
    		$reference = db2web($row['f_reference'])."<a href=\"" . SITE_URL . "flight_create.php?flight_id=$row[f_id]\" title=\"Go to reservation $row[f_reference]\" target=\"_blank\"><i class=\"bi bi-box-arrow-up-right\"></i></a>";
    		$date=$row['f_date_flown'];
    		$date=gmdate('d/m/Y', strtotime("$row[f_date_flown]")) ;
    		$plane=$row['l_plane'];
    		$pilote=db2web($row['first_name'])." ".db2web($row['last_name']) ;
    		$montant=$row['prix'];
    		$montantTotal+=$montant;
    		$referencePaiement=db2web($row['fl_reference']);	
    		$referencePaiementOdoo=$row['fl_odoo_payment_id'];	
    		$pos = strpos(strtoupper($referencePaiement), "FACTURE DHF");
            $flyid=$row['f_id'];
            $logbookID=$row['l_id'];	
    		if($pos===false) {
    			$referenceOdoo="Créer facture IF";
    		}
    		else {
    			$referenceOdoo="DHF";
    		}			
    		print("<tr><td>$date</td><td>$reference</td><td>$plane</td><td>$pilote</td><td>$montant</td>");
            //****************************************************************************
            if($invoiceRef>0 || ($alreadyInvoicedDateString > $since)) {
                // Invoice already created
                print("<td>Déjà facturé</td>");
            }
            else {
                print("<td><a class=\"btn btn-sm btn-primary\" href=\"javascript:void(0);\" onclick=\"createFactureIFFunction('$_SERVER[PHP_SELF]', '$since', '$referenceFlight', '$date', '$logbookID', '$montant','$flyid')\">Créer Facture IF</a></td>");
            }
            print("</tr>");
    	}
    }
	$montantTotalText=number_format($montantTotal, 2, '.', ' ') ;
	print("<tr><td><b>Total</b></td><td></td><td></td><td></td><td><b>$montantTotalText</b></td><td></td></tr>");
	
	?>
	
</tbody>
</table></div>

<!-- Vols DHF -->
<h4 class="mt-4">Vols DHF effectués mois <?=$monthName?></h4>
<div class="table-responsive"><table class="table table-bordered table-striped table-sm align-middle w-auto text-center">
<thead class="table-info">
<tr>
<th scope="col">Date Vol</th>
<th scope="col">Référence</th>
<th scope="col">Avion</th>
<th scope="col">Pilote</th>
<th scope="col">Prix du vol</th>
<th scope="col">Facture odoo</th>
</tr>
</thead>
<tbody>
	<?php
	//WHERE pr_role = 'C'$deleted_filter $completed_filter $other_filter 
	//print("Filter=".$other_filter.",/br>");

	$filterType = ' AND f_type ="D"' ;
	$filter = ' AND f_date_flown IS NOT NULL' ;
	$date_filter = " AND f_date_flown >= '" . $since . "' AND f_date_flown <= '" . $monthAfterString. "'";
	$result = mysqli_query($mysqli_link, "SELECT DISTINCT l_id, f_invoice_ref, l_plane, f_reference, f_date_flown, first_name, last_name, r_plane, f_id, fl_reference, fl_odoo_payment_id, sum(fl_amount) as prix 
			FROM $table_flight AS f  
			JOIN $table_person ON f.f_pilot = jom_id
			JOIN $table_bookings AS b ON f.f_booking = b.r_id
			JOIN $table_flights_ledger ON f_id = fl_flight
			JOIN $table_logbook AS l ON f.f_booking = l.l_booking
			WHERE true $filterType $filter $date_filter 
			GROUP BY f_reference
			ORDER BY f_date_flown") 
			or journalise($userId, "F", "Impossible de lister les vols IF: " . mysqli_error($mysqli_link));
    
	/*
	$result = mysqli_query($mysqli_link, "SELECT DISTINCT f_reference, f_invoice_ref, f_date_flown, first_name, last_name, r_plane, f_id, fl_reference, sum(fl_amount) as prix 
			FROM $table_flight AS f  
			JOIN $table_person ON f.f_pilot = jom_id
			JOIN $table_bookings AS b ON f.f_booking = b.r_id
			JOIN $table_flights_ledger ON f_id = fl_flight
			WHERE true $filterType $filter $date_filter 
			GROUP BY f_reference
			ORDER BY f_date_flown") 
			or journalise($userId, "F", "Impossible de lister les vols IF: " . mysqli_error($mysqli_link));
*/
	$montantTotal=0.0;
    $DHFCount=0;
    $references="";
    $logbookids="";
    $invoiceRefCount=0;
	while ($row = mysqli_fetch_array($result)) {
        $reference = $row['f_reference'];
		$pos = strpos(strtoupper($reference), "DHF-");
		if($pos!==false && $pos==0) {
            $DHFCount++;
            if($references!="") $references=$references.";";
            $reference = $row['f_reference'];
            $references=$references.$reference;
            if($logbookids!="") $logbookids=$logbookids.";";
            $logbookid=$row['l_id'];	
            $logbookids=$logbookids.$logbookid;
            //print("logbookid=$logbookid logbookids=$logbookids<br>");
            $invoiceRef=$row['f_invoice_ref'];
            if($invoiceRef>0) $invoiceRefCount+=1;
 			$reference = db2web($reference)."<a href=\"" . SITE_URL . "flight_create.php?flight_id=$row[f_id]\" title=\"Go to reservation $row[f_reference]\" target=\"_blank\"><i class=\"bi bi-box-arrow-up-right\"></i></a>";
			$date=$row['f_date_flown'];
			$date=gmdate('d/m/Y', strtotime("$row[f_date_flown]")) ;
			$plane=$row['l_plane'];
			$pilote=db2web($row['first_name'])." ".db2web($row['last_name']) ;
			$montant=$row['prix'];
			$montantTotal+=$montant;
			$referencePaiement=db2web($row['fl_reference']);	
			$pos = strpos(strtoupper($referencePaiement), "FACTURE DHF");
			if($pos===false) {
				$referenceOdoo="DHF";
			}			
			print("<tr><td>$date</td><td>$reference</td><td>$plane</td><td>$pilote</td><td>$montant</td><td>$referenceOdoo</td></tr>");
	    }
    }
	$montantTotalText=number_format($montantTotal, 2, '.', ' ') ;
    $factureDHF="";
    $alreadyInvoice=false;

    if($DHFCount>0 ) {
        if($DHFCount==$invoiceRefCount) $alreadyInvoice=true;        
        if(!$alreadyInvoice ) {
            $factureDHF="<a class=\"btn btn-sm btn-primary\" href=\"javascript:void(0);\" onclick=\"createFactureDHFFunction('$_SERVER[PHP_SELF]', '$since', '$references','$logbookids')\">Créer Facture Mensuelle DHF</a>";
        }
        else {
          $factureDHF="Déjà facturé";  
        }
	    print("<tr><td><b>Total</b></td><td></td><td></td><td></td><td><b>$montantTotalText</b></td><td>$factureDHF</td></tr>");
    }
	?>
	
</tbody>
</table></div>


<?php
require_once 'flight_trailer.php' ;
?>
