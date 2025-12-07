<?php
/*
   Copyright 2023-2024 Eric Vyncke

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
ini_set('display_errors', 1) ; // extensive error reporting for debugging
require __DIR__ . '/vendor/autoload.php' ;
require_once __DIR__ .'/dbi.php' ;
require_once __DIR__ .'/odoo.class.php' ;
require_once __DIR__.'/mobile_tools.php';
require_once __DIR__.'/notedefraisPDF.php';
require_once __DIR__.'/bondecommandePDF.php';

class OdooFlight {

    function __construct() {
    }

    # Read return all records from one model based on their IDs
    function Read() {
    }
}
//============================================
// Function: OF_GetOdooClient
// Purpose: Returns the odooClient
//============================================
function OF_GetOdooClient()
{
    global $odooClient;
    global $odoo_host, $odoo_db, $odoo_username, $odoo_password;
    if(!isset($odooClient)) {
        $odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
    }
    return $odooClient;
}
//============================================
// Function: OF_LinkOdooLedger
// Purpose: Change the value of fl_odoo_payment_id of table_flights_ledger with the InvoiceOdooID (invoice.move.line of compte 799001 or 799002)
//============================================
function OF_LinkOdooLedger($theLedgerID,$theInvoiceOdooID) {
    global $mysqli_link, $table_flights_ledger,$userId;
    //print("<h1 style=\"color: red;\"><b>Function OF_LinkOdooLedger theInvoiceOdooID=$theInvoiceOdooID,theLedgerID=$theLedgerID</b></h1>");
    //print("UPDATE $table_flights_ledger SET fl_odoo_payment_id=$theInvoiceOdooID WHERE fl_id=$theLedgerID<br>");
	mysqli_query($mysqli_link, "UPDATE $table_flights_ledger SET fl_odoo_payment_id=$theInvoiceOdooID WHERE fl_id=$theLedgerID")
    or 
		journalise($userId, "F", "Impossible de mettre à jour le flights_ledger: " . mysqli_error($mysqli_link)) ;	
        //print("Error DB:".mysqli_error($mysqli_link));
    return true;
}
//============================================
// Function: OF_createPayment
// Purpose: Create paiement fl_odoo_payment_id of table_flights_ledger 
//============================================
function OF_createPayment($theFlightID, $theOdooID, $theAmount, $thePaymentDate, $thePaymentReference) {
    
    global $mysqli_link, $table_flights_ledger,$userId;
    //print("<h1 style=\"color: red;\"><b>Function OF_createPayment theFlightID=$theFlightID,theOdooID=$theOdooID,theAmount=$theAmount,thePaymentDate=$thePaymentDate, thePaymentReference=$thePaymentReference</b></h1>");
	if ($thePaymentDate == 'today')
		$date = "SYSDATE()";
	else
		$date = $thePaymentDate;
	$reference = $thePaymentReference;
	$odooreference = $theOdooID;
	$amount = $theAmount;
    $flight_id = $theFlightID;
    if($odooreference!="") {
	    mysqli_query($mysqli_link, "INSERT INTO $table_flights_ledger(fl_flight, fl_date, fl_who, fl_amount, fl_reference, fl_odoo_payment_id)
		    VALUES($flight_id, '$date', $userId, $amount, '$reference', $odooreference)")
		    or journalise($userId, "F", "Impossible d'ajouter un paiement: " . mysqli_error($mysqli_link)) ;
    }
    else {
	    mysqli_query($mysqli_link, "INSERT INTO $table_flights_ledger(fl_flight, fl_date, fl_who, fl_amount, fl_reference)
		    VALUES($flight_id, $date, $userId, $amount, '$reference')")
		    or journalise($userId, "F", "Impossible d'ajouter un paiement: " . mysqli_error($mysqli_link)) ;
    }
	journalise($userId, "I", "Flight $flight_id payment information updated $amount") ;
    return true;
}

//============================================
// Function: OF_FillFlightOdooMaps
// Purpose: Fill Map between table_flights, table_flights_ledger and InvoiceOdooID (invoice.move.line of compte 799001 or 799002)
//      theOdooPaymentMap[$fl_odoo_payment_id]=$f_reference;
//      thePaymentFlightMap[$f_reference]=$row['fl_amount'];
//      theLedgerIdMap[$f_reference]=$row['fl_id'];
//      theReferenceIDMap[$f_reference]=$row['f_id'];
//      theGiftFlagMap[$f_reference]=$row['f_gift'];
//============================================
function OF_FillFlightOdooMaps(&$theOdooPaymentMap,&$thePaymentFlightMap,&$theLedgerIdMap,&$theReferenceIDMap,&$theGiftFlagMap) {
    global $mysqli_link, $table_flights_ledger,$table_flights,$userId;

    $result = mysqli_query($mysqli_link, "SELECT * FROM $table_flights_ledger JOIN $table_flights ON fl_flight=f_id")
    		or journalise($userId, "E", "Cannot read ledger: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        $fl_odoo_payment_id=$row['fl_odoo_payment_id'];
        $f_reference=$row['f_reference'];
        if($fl_odoo_payment_id!="") {
            $theOdooPaymentMap[$fl_odoo_payment_id]=$f_reference;
         }
         $theReferenceIDMap[$f_reference]=$row['f_id'];
         if(array_key_exists($f_reference,$thePaymentFlightMap)) {
            // More than one payment associated to a flight
            $thePaymentFlightMap[$f_reference]=$thePaymentFlightMap[$f_reference]+$row['fl_amount'];
         }
         else {
            $thePaymentFlightMap[$f_reference]=$row['fl_amount'];
         }
         $theLedgerIdMap[$f_reference]=$row['fl_id'];
         $theGiftFlagMap[$f_reference]=$row['f_gift'];
    }
}
//============================================
// Function: OF_FillFlightMaps
// Purpose: Fill Map between f_reference and f_id in table_flights
//      theReferenceIDMap[$f_reference]=$f_id;
//============================================
function OF_FillFlightMaps(&$theReferenceIDMap) {
    global $mysqli_link, $table_flights,$userId;

    $result = mysqli_query($mysqli_link, "SELECT * FROM $table_flights")
    		or journalise($userId, "E", "Cannot read ledger: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        $f_reference=$row['f_reference'];
        $theReferenceIDMap[$f_reference]=$row['f_id'];
    }
    //echo var_dump($theReferenceIDMap);
}

//============================================
// Function: OF_CreateFactureCotisation
// Purpose: Create an invoice for a member cotisation 
//           CotisationType="naviguant","nonnaviguant"
//============================================
function OF_CreateFactureCotisation($thePersonID, $theCotisationType, $theMembership_year) {
    global $mysqli_link, $table_membership_fees, $userId, $userFullName;
    global $non_nav_membership_product,$non_nav_membership_price,$membership_analytic_account;
    global $nav_membership_product,$nav_membership_price,$membership_analytic_account;
    //print("OF_createFactureCotisation($thePersonID, $theCotisationType):started<br>");

    $invoice_date =  date("Y-m-d") ;
    $invoice_date_due =  date("Y-m-d", strtotime("+1 week")) ;
     // Retrieve the odooid from joom_id
    $odoo_id=OF_GetPartnerIDFromJomID($thePersonID);
    if( $odoo_id==0) {
        //print("OF_createFactureCotisation 3 ($thePersonID, $theCotisationType)<br>");
        return false;
    }
    // Cotisation after 1 july is % of the year
    $month=date("m");
    $non_nav_membership_price_corrected=$non_nav_membership_price;
    $nav_membership_price_corrected=$nav_membership_price;
    if($month>6) {
        $non_nav_membership_price_corrected=$non_nav_membership_price_corrected*(12-$month)/12.0;
        $nav_membership_price_corrected=$nav_membership_price_corrected*(12-$month)/12.0;

    }
    $odooClient = OF_GetOdooClient();
    $invoice_lines = array() ;
    $invoice_lines[] = array(0, 0,
        array(
            'name' => "Cotisation club pour l'année $theMembership_year",
            'product_id' => $non_nav_membership_product, 
            'quantity' => 1,
            'price_unit' => $non_nav_membership_price_corrected,
            'analytic_distribution' => array($membership_analytic_account => 100)
    )) ;
    $membership_price=$non_nav_membership_price_corrected;
    if($theCotisationType=="naviguant") {
        $invoice_lines[] = array(0, 0,
            array(
                'name' => "Cotisation membre naviguant pour l'année $theMembership_year",
                'product_id' => $nav_membership_product, 
                'quantity' => 1,
                'price_unit' => $nav_membership_price_corrected,
                'analytic_distribution' => array($membership_analytic_account => 100)
            )) ;
        $membership_price+=$nav_membership_price_corrected;
    }
    $params =  array(array('partner_id' => intval($odoo_id), // Must be of INT type else Odoo does not accept
    // Should the state set to 'posted' rather than 'draft' which is the default it seems?
    //                'state' => 'posted', // Returns Vous ne pouvez pas créer une écriture déjà dans l'état comptabilisé. Veuillez créer un brouillon d'écriture et l'enregistrer après.
                    'ref' => 'Cotisation club '.$theMembership_year,
                    'move_type' => 'out_invoice',
                    'invoice_date' => $invoice_date,
                    'invoice_date_due' => $invoice_date_due,
                    'invoice_origin' => "Manuellement par $userFullName " . date("Y-m-d"),
                    'invoice_line_ids' => $invoice_lines)) ;
    if(1) {
        $result = $odooClient->Create('account.move', $params) ;
        print("Invoicing result for #$odoo_id: $membership_price &euro;, " . implode(', ', $result) . "<br/>\n") ;
        mysqli_query($mysqli_link, "INSERT INTO $table_membership_fees(bkf_user, bkf_year, bkf_amount, bkf_invoice_id, bkf_invoice_date)
         VALUES($thePersonID, '$theMembership_year', $membership_price, $result[0], '$invoice_date')")
         or journalise($userId, "F", "Cannot insert into $table_membership_fees: " . mysqli_error($mysqli_link)) ;
        journalise($userId, "I", "Odoo membership invoice generated for $thePersonID by $userId ") ;		
    }
    else {
        var_dump($params);
        print("<br>");
    }
    return true;
}

//============================================
// Function: OF_createFactureIF
// Purpose: Create an invoice for an IF flight
//============================================
function OF_createFactureIF($theFlightReference, $theDate, $theLogbookid, $theMontant, $theFlyID) {
    global $mysqli_link, $table_logbook,$userId;
    global $odoo_host, $odoo_db, $odoo_username, $odoo_password;
    //print("OF_createFactureIF($theFlightReference, $theDate, $theLogbookid, $theMontant, $theFlyID):started<br>");
    
    $result = mysqli_query($mysqli_link, "SELECT * FROM $table_logbook WHERE l_id=$theLogbookid")
    		or journalise($userId, "E", "Cannot read logbook: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        $plane=$row['l_plane'];
        $planeTableRow=OF_GetPlaneTableRow($plane);
        $date=$row['l_start'];
        $date=substr($date,0,16);
        $flyReference=$theFlightReference;
        $duration=OF_ComputeDurationToBeInvoiced($row);
        $cost_plane_minute=OF_ComputeCostPerMinute($plane);
        //print("plane=$plane, date=$date,flyReference=$flyReference,duration=$duration,cost_plane_minute=$cost_plane_minute<br>");
        if($cost_plane_minute<=0.0) {
            return false;
        }
        if($duration<=0.0) {
            print("<h2 style=\"color: red;\">ERROR:OF_createFactureIF: Flight duration < 0: $duration</h2>");
            return false;
        }
    }
    $code_700102=OF_GetAccountID(700102);
    $code_499002=OF_GetAccountID(499002);
    $code_499003=OF_GetAccountID(499003);
    $code_400000=OF_GetAccountID(400000);
    $edgerReference=OF_GetPaymentReference($theFlyID);
    //print("edgerReference=$edgerReference<br>");
	$partner_customer_id =  OF_GetPartnerID($theFlightReference,$theFlyID);
    //print("partner_customer_id=$partner_customer_id<br>");
    $partnerName=OF_GetPartnerNameFromReference($theFlightReference);
    $plane_analytic=OF_GetAnalyticAccountID($plane);
    $analytic_club_init_if=OF_GetAnalyticAccountID("club_init_if");
    $journal_if=OF_GetJournalID("if");
    $invoice_date_due = date("Y-m-d", strtotime("+1 week")) ;
	//print("</br>Création facture V-IF</br>");
	// Reference facture : V-IF-24xxxx + 50€ par carte
	$libelle_name=$flyReference." ".$date;
    $pos = strpos(strtoupper($theFlightReference), "V-");
    if($pos===false || $pos!=0) {
        // IF-xxxxxx 
    }
    else {
        // V-IF : Add partner name
        $libelle_name=$libelle_name." (".$partnerName.")";
        //print("V-IF: libelle_name=$libelle_name<br>");
    }

    ////journalise($userId, "I", "Odoo invoices generation started ($odoo_host)") ;			
    ini_set('display_errors', 1) ; // extensive error reporting for debugging
    $odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
    
    
	// Partie Avion
    $invoice_lines = array() ;
    $invoice_lines[] = array(0, 0,
		array(
			'name' => db2web($libelle_name),
			//'product_id' => $plane_if_product_id,
			'account_id' => $code_700102,  
			'quantity' => $duration,
			'price_unit' => $cost_plane_minute,
            'analytic_distribution' => array($plane_analytic => 100)
		)) ;
		
	// Partie Club
	$benefice_club= $theMontant - $cost_plane_minute * $duration;
    $invoice_lines[] = array(0, 0,
		array(
			'name' => db2web($libelle_name),
			//'product_id' => $plane_if_product_id, 
			'account_id' => $code_700102, 
			'quantity' => 1,
			'price_unit' => $benefice_club,
            'analytic_distribution' => array($analytic_club_init_if => 100)
		)) ;
	
	// Invoice creation	
    if($edgerReference!="" && strpos(strtoupper($flyReference), "V-")===false) {
        // Fly IF Bancontact or Via Compte
        $flyReference.=" (".$edgerReference.")";
    }
    
    $params =  array(array('partner_id' => intval($partner_customer_id), // 37: Reginster (Must be of INT type else Odoo does not accept)
                    'ref' => db2web($flyReference),
					'payment_reference' => db2web($flyReference),
                    'move_type' => 'out_invoice',
					'journal_id'=> $journal_if,
                    'invoice_date_due' => $invoice_date_due,
                    'invoice_origin' => 'Carnets de routes',
                    'invoice_line_ids' => $invoice_lines)) ;
	//print("</br> 1 Création facture IF invoice_date_due=$invoice_date_due</br>");
	//print("params=$params");
	//echo var_dump($params);
    //$invoiceID=0;
    if(1) {
        $invoiceID = $odooClient->Create('account.move', $params) ;
        //echo var_dump($invoiceID);
        //print("<br>Facture IF pour " . implode(', ', $invoiceID) . "<br>") ;
        //print("<br>Facture IF pour " . $invoiceID[0] . "<br>") ;
        OF_SetFlightInvoiceFromFlyID($theFlyID, $invoiceID[0]);
    }
    
    //***************************************************************************
    // Creation une OD pour le transfert 499002 -> 400000 (Ou 499003 -> 400000)
    // Not exact: Must be managed by the type of payment
    $pos = strpos(strtoupper($theFlightReference), "V-");
    if($pos===false || $pos!=0) {
        // IF-xxxxxx : Not OD to create
    }
    else {
        // Flight V-IF- -> 499002 -> 400000
        $accountCodeID=$code_499002;
    	$reference_account499="Transfert de 4990002 ".$flyReference." (".$partnerName.")";

        $valeur_compte_attente=floatval($theMontant);
        $valeur_compte_attente=OF_GetPaymentAmount($theFlyID);
        $invoice_lines_OD = array() ;
    	// Partie debit compte attente Bon cadeau
    	$reference_account="Transfert vers 4000000 ".$flyReference." (".$partnerName.")";
    	$invoice_lines_OD[] = array(0, 0,
    				array(
    					'name' => db2web($reference_account),
    					'account_id' => $accountCodeID, 
    					'debit' => $valeur_compte_attente
    				)) ;
    	// Partie credit compte client Bon cadeau
    	$invoice_lines_OD[] = array(0, 0,
    				array(
    					'name' => db2web($reference_account499),
    					'account_id' => $code_400000, 
    					'credit' => $valeur_compte_attente
    				)) ;
			
    	// Invoice creation	account.move
        $name='VIF/2024/04/'.$flyReference;
        $journal_transfert_init_if=OF_GetJournalID("trf");
        $params_OD =  array(array('partner_id' => intval($partner_customer_id),
                        'ref' => db2web($flyReference),
//    					'name'=> $name,
                        'move_type' => 'entry',
    					'journal_id'=> $journal_transfert_init_if,
                        'invoice_date_due' => $invoice_date_due,
                        'invoice_origin' => 'Carnets de routes',
                        'invoice_line_ids' => $invoice_lines_OD)) ;
    	print("</br> 2 Création OD V-IF</br>");
    	//print("params=$params");
        //echo var_dump($params);
        //print("<br>Pousser ****** dans odoo<br>");
        if(1) {
            $result_OD = $odooClient->Create('account.move', $params_OD) ;
            //print("<br>OD V-IF **** pour " . implode(', ', $result_OD) . " Name=".$name." Prix=".$valeur_compte_attente."<br>") ;
        }
    }
    return true;
}
//============================================
// Function: OF_createFactureINIT
// Purpose: Create an invoice for an INIT flight
//============================================
function OF_createFactureINIT($theFlightReference, $theDate, $theLogbookid, $theMontant, $theFlyID) {
    global $mysqli_link, $table_logbook,$userId;
    global $odoo_host, $odoo_db, $odoo_username, $odoo_password;
    //print("OF_createFactureINIT($theFlightReference, $theDate, $theLogbookid, $theMontant, $theFlyID):started<br>");
    $result = mysqli_query($mysqli_link, "SELECT * FROM $table_logbook WHERE l_id=$theLogbookid")
    		or journalise($userId, "E", "Cannot read logbook: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        $plane=$row['l_plane'];
        $planeTableRow=OF_GetPlaneTableRow($plane);
        $pilot=$row['l_pilot'];
        $date=$row['l_start'];
        $date=substr($date,0,16);
        $flyReference=$theFlightReference;
        $duration=OF_ComputeDurationToBeInvoiced($row);
        $cost_plane_minute=OF_ComputeCostPerMinute($plane);
        //print("plane=$plane, date=$date,flyReference=$flyReference,duration=$duration,cost_plane_minute=$cost_plane_minute<br>");
        if($cost_plane_minute<=0.0) {
            return false;
        }
        if($duration<=0.0) {
            print("<h2 style=\"color: red;\">ERROR:OF_createFactureINIT: Flight duration < 0: $duration</h2>");
            return false;
        }
    }
    $code_700101=OF_GetAccountID(700101);
    $code_499001=OF_GetAccountID(499001);
    $code_499003=OF_GetAccountID(499003);
    $code_400000=OF_GetAccountID(400000);
    $edgerReference=OF_GetPaymentReference($theFlyID);
    $cost_FI = 60.;
	$partner_customer_id =  OF_GetPartnerID($theFlightReference,$theFlyID);
    $partnerName=OF_GetPartnerNameFromReference($theFlightReference);
    //print("partnerName=$partnerName<br>");
    $plane_analytic=OF_GetAnalyticAccountID($plane);
    $analytic_club_init_if=OF_GetAnalyticAccountID("club_init_if");
    $journal_if=OF_GetJournalID("init");
    $invoice_date_due = date("Y-m-d", strtotime("+1 week")) ;
	//print("</br>Création facture V-INIT</br>");
	// Reference facture : V-INIT-24xxxx 
	$libelle_name=$flyReference." ".$date;

    $pos = strpos(strtoupper($theFlightReference), "V-");
    if($pos===false || $pos!=0) {
        // INIT-xxxxxx 
    }
    else {
        // V-INIT : Add partner name
        $libelle_name=$libelle_name." (".$partnerName.")";
    }

    $FI_analytic=OF_GetAnalyticPilotID($pilot);
        
    ////journalise($userId, "I", "Odoo invoices generation started ($odoo_host)") ;			
    ini_set('display_errors', 1) ; // extensive error reporting for debugging
    $odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
    
    
	// Partie Avion
    $invoice_lines = array() ;
    $invoice_lines[] = array(0, 0,
		array(
			'name' => db2web($libelle_name),
			'account_id' => $code_700101,  
			'quantity' => $duration,
			'price_unit' => $cost_plane_minute,
            'analytic_distribution' => array($plane_analytic => 100)
		)) ;
		
	// Partie FI
    $invoice_lines[] = array(0, 0,
		array(
			'name' => db2web($libelle_name),
			'account_id' => $code_700101,  
			'quantity' => 1,
			'price_unit' => $cost_FI,
            'analytic_distribution' => array($FI_analytic => 100)
		)) ;
		
	// Partie Club
	$benefice_club= $theMontant - $cost_FI - $cost_plane_minute * $duration;
    $invoice_lines[] = array(0, 0,
		array(
			'name' => $libelle_name,
			//'product_id' => $plane_if_product_id, 
			'account_id' => $code_700101, 
			'quantity' => 1,
			'price_unit' => $benefice_club,
            'analytic_distribution' => array($analytic_club_init_if => 100)
		)) ;
	
	// Invoice creation	
    if($edgerReference!="" && strpos(strtoupper($flyReference), "V-")===false) {
        // Fly INIT Bancontact or Via Compte
        $flyReference.=" (".$edgerReference.")";
    }
    $params =  array(array('partner_id' => intval($partner_customer_id), // 37: Reginster (Must be of INT type else Odoo does not accept)
                    'ref' => db2web($flyReference),
					'payment_reference' => db2web($flyReference),
                    'move_type' => 'out_invoice',
					'journal_id'=> $journal_if,
                    'invoice_date_due' => $invoice_date_due,
                    'invoice_origin' => 'Carnets de routes',
                    'invoice_line_ids' => $invoice_lines)) ;
	print("</br> 1 Création facture IF invoice_date_due=$invoice_date_due</br>");
	//print("params=$params");
	//echo var_dump($params);
    if(1) {
        $invoiceID = $odooClient->Create('account.move', $params) ;
        //echo var_dump($invoiceID);
        //print("<br>Facture IF pour " . implode(', ', $invoiceID) . "<br>") ;
        //print("<br>Facture IF pour " . $invoiceID[0] . "<br>") ;
        OF_SetFlightInvoiceFromFlyID($theFlyID, $invoiceID[0]);
    }
    //***************************************************************************
    // Creation une OD pour le transfert 499001 -> 400000 (Ou 499003 -> 400000)
    // Not exact: Must be managed by the type of payment
    $pos = strpos(strtoupper($theFlightReference), "V-");
    if($pos===false || $pos!=0) {
        // INIT-xxxxxx : Not OD to create
    }
    else {
        // Flight V-INIT- -> 499001 -> 400000
        $accountCodeID=$code_499001;
    	$reference_account499="Transfert de 4990001 ".$flyReference." (".$partnerName.")";

        $valeur_compte_attente=floatval($theMontant);
        $valeur_compte_attente=OF_GetPaymentAmount($theFlyID);
        $invoice_lines_OD = array() ;
    	// Partie debit compte attente Bon cadeau
    	$reference_account="Transfert vers 4000000 ".$flyReference." (".$partnerName.")";
    	$invoice_lines_OD[] = array(0, 0,
    				array(
    					'name' => db2web($reference_account),
    					'account_id' => $accountCodeID, 
    					'debit' => $valeur_compte_attente
    				)) ;
    	// Partie credit compte client Bon cadeau
    	$invoice_lines_OD[] = array(0, 0,
    				array(
    					'name' => db2web($reference_account499),
    					'account_id' => $code_400000, 
    					'credit' => $valeur_compte_attente
    				)) ;
			
    	// Invoice creation	account.move
        $name='VINI/2024/04/'.$flyReference;
        $journal_transfert_init_if=OF_GetJournalID("trf");
        $params_OD =  array(array('partner_id' => intval($partner_customer_id),
                        'ref' => db2web($flyReference),
//    					'name'=> $name,
                        'move_type' => 'entry',
    					'journal_id'=> $journal_transfert_init_if,
                        'invoice_date_due' => $invoice_date_due,
                        'invoice_origin' => 'Carnets de routes',
                        'invoice_line_ids' => $invoice_lines_OD)) ;
    	print("</br> 2 Création OD V-INIT</br>");
        //echo var_dump($params_OD);
        //print("<br>Pousser dans odoo<br>");
        if(1) {
            $result_OD = $odooClient->Create('account.move', $params_OD) ;
            //print("<br>OD V-IF **** pour " . implode(', ', $result_OD) . " Name=".$name." Prix=".$valeur_compte_attente."<br>") ;
        }
    }
    return true;
}
//============================================
// Function: OF_createFactureDHF
// Purpose: Create an invoice for an DHF flight
//============================================
function OF_createFactureDHF($theFlightReferences, $theDate, $thelogbookids) {
    global $mysqli_link, $table_logbook,$userId;
    global $odoo_host, $odoo_db, $odoo_username, $odoo_password;
    global $non_nav_membership_product,$non_nav_membership_price,$membership_analytic_account;
    //print("OF_createFactureDHF($theFlightReferences, $theDate, $thelogbookids):started<br>");
    $referencesMap= array();

    $code_700102=OF_GetAccountID(700102);
    $code_700000=OF_GetAccountID(700000);
    $code_499002=OF_GetAccountID(499002);
    $code_499003=OF_GetAccountID(499003);
    $code_400000=OF_GetAccountID(400000);
	$partner_customer_id =  OF_GetPartnerID("DHF-",0);
    $analytic_club_init_if=OF_GetAnalyticAccountID("club_init_if");
    $journal_if=OF_GetJournalID("if");
    $invoice_date_due = date("Y-m-d", strtotime("+1 week")) ;
	//print("</br>Création facture DHF</br>");
    ////journalise($userId, "I", "Odoo invoices generation started ($odoo_host)") ;			
    ini_set('display_errors', 1) ; // extensive error reporting for debugging
    $odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
    $invoice_lines = array() ;

    OF_FillFlightMaps($referencesMap);
    $referencesArray=explode(";",$theFlightReferences);
    $logbookidsArray=explode(";",$thelogbookids);
    $count=-1;
    foreach ($referencesArray as $reference) {
        $count++;
        $logbookid=$logbookidsArray[$count];
        //echo "reference=$reference, $referencesMap[$reference] logbookid=$logbookid<br>"; 

        $result = mysqli_query($mysqli_link, "SELECT * FROM $table_logbook WHERE l_id=$logbookid")
        		or journalise($userId, "E", "Cannot read logbook: " . mysqli_error($mysqli_link)) ;
        while ($row = mysqli_fetch_array($result)) {
            $partnerName=OF_GetPartnerNameFromReference($reference);
            $plane=$row['l_plane'];
            $planeTableRow=OF_GetPlaneTableRow($plane);
            $date=$row['l_start'];
            $date=substr($date,0,16);
            $flyReference=$reference;
            $duration=OF_ComputeDurationToBeInvoiced($row);
            $cost_plane_minute=OF_ComputeCostPerMinute($plane);
            //print("plane=$plane, date=$date,flyReference=$flyReference,duration=$duration,cost_plane_minute=$cost_plane_minute<br>");
            if($cost_plane_minute<=0.0) {
                return false;
            }
            if($duration<=0.0) {
                print("<h2 style=\"color: red;\">ERROR:OF_createFactureDHF: Flight duration < 0: $duration</h2>");
                return false;
            }

            $plane_analytic=OF_GetAnalyticAccountID($plane);
        	$libelle_name="Vol ".$flyReference." ".substr($date,0,10)." ".$partnerName;
            $libelle_name_cotisation= "Cotisation membre VIP ".$partnerName;
            
            $cost_plane_dhf = 100.0;
            $cotisation = 70.0;
            
        	// Partie Avion
            $invoice_lines[] = array(0, 0,
        		array(
        			'name' => db2web($libelle_name),
        			//'product_id' => $plane_dhf_product_id,
        			'account_id' => $code_700102,  
        			'quantity' => 1,
        			'price_unit' => $cost_plane_dhf,
                    'analytic_distribution' => array($plane_analytic => 100)
        		)) ;
		
        	// Partie Club
            $invoice_lines[] = array(0, 0,
        		array(
        			'name' => db2web($libelle_name_cotisation),
        			//'product_id' => $plane_dhf_product_id, 
        			'account_id' => $code_700000, 
        			'quantity' => 1,
        			'price_unit' => $cotisation,
                    'analytic_distribution' => array($membership_analytic_account => 100)
        		)) ;
        }
    }

	// Invoice creation	
    $DHFReference="Facture DHF ".$theDate;
    $params =  array(array('partner_id' => intval($partner_customer_id), //(Must be of INT type else Odoo does not accept)
                    'ref' => db2web($DHFReference),
					'payment_reference' => db2web($DHFReference),
                    'move_type' => 'out_invoice',
					'journal_id'=> $journal_if,
                    'invoice_date_due' => $invoice_date_due,
                    'invoice_origin' => 'Carnets de routes',
                    'invoice_line_ids' => $invoice_lines)) ;
	//print("</br> 1 Création facture DHF invoice_date_due=$invoice_date_due</br>");
	//print("params=$params");
	//echo var_dump($params);
    
    $invoiceID = $odooClient->Create('account.move', $params) ;
    
    // Store the invoice reference into each fly
    foreach ($referencesArray as $reference) {
        //print("OF_SetFlightInvoiceFromFlyReference($reference, invoiceID[0])<br>");
        OF_SetFlightInvoiceFromFlyReference($reference, $invoiceID[0]);
        //OF_SetFlightInvoiceFromFlyReference($reference, 1);
    }
    //echo var_dump($invoiceID);
    //print("<br>Facture DHF pour " . implode(', ', $invoiceID) . "<br>") ;
    //print("<br>Facture DHF pour " . $invoiceID[0] . "<br>") ;
    //OF_SetFlightInvoiceFromFlyID($theFlyID, $invoiceID[0]);
    
    return true;
}
//============================================
// Function: OF_createNoteDeFrais
// Purpose: Creation d'une note de frais
//============================================
function OF_createBonDeCommande($theMemberID, $theBonDeCommandeJSON, &$theUploadFolder, &$theFactureMailTo)
{
   //print("<br>OF_createBonDeCommande: Start theMemberID=$theMemberID theBonDeCommandeJSON=$theBonDeCommandeJSON<br>");

    global $mysqli_link, $table_person,$userId;

    if($theMemberID=="") {
        print("<h2 style=\"color: red;\">ERROR:OF_createNoteDeFrais: Pas de membre sélectionné</h2>");
        return "";
    }

    $bonDeCommandelines = json_decode($theBonDeCommandeJSON, true) ;
    $bonDeCommandeSize=sizeof($bonDeCommandelines);

    /*
        {"name":"Carnet de vol Avn/hel", "type":"Carnet de vol Avn/hel", "reference": "B-CV", "quantity": 1, "unitaryprice": 28.0},
        {"name":"Computer papier E6B", "type":"Computer papier E6B", "reference": "B-E6B", "quantity": 1, "unitaryprice": 17.00},
        {"name":"Jeppesen rotating plotter", "type":"Jeppesen rotating plotter", "reference": "B-JRP", "quantity": 1, "unitaryprice": 20.00},
    */

    $partner_customer_id=0;
    $partnerName="";
    $partner= array();
    $result = mysqli_query($mysqli_link, "SELECT * FROM $table_person WHERE jom_id=$theMemberID")
        		or journalise($userId, "E", "Cannot read table_person: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
            $partnerName=$row['first_name']." ".$row['last_name'];
            $partner["name"]=$row['first_name']." ".$row['last_name'];
            $partner["address"]=$row['address'];
            $partner["city"]=$row['zipcode']." ".$row['city'];;
            $partner["country"]=$row['country'];
            $partner["email"]=$row['email'];
            $partner_customer_id=$row['odoo_id'];
    }
    if($partner_customer_id==0) {
           print("<h2 style=\"color: red;\">ERROR:OF_createBonDeCommande: Unknown partner</h2>");
           journalise($userId, "E", "OF_createBonDeCommande: Unknown partner for member $theMemberID") ;
        return "";
    }
    $journal_ndf=OF_GetJournalID("client");
    $moveType="out_refund";
    $uploadFolder="uploads/bondecommande";
    $invoice_date= date("Y-m-d") ;
    $invoice_date_due = date("Y-m-d", strtotime("+1 week")) ;
	
    // Version Bon De Commande PDF
 
    //print("<br>OF_createNBonDeCommande: PDF Version 2<br>");
    return PDF_createBonDeCommande($bonDeCommandelines, $invoice_date, $partner, $theUploadFolder, $theFactureMailTo);

}

//============================================
// Function: OF_createNoteDeFrais
// Purpose: Creation d'une note de frais
//============================================
function OF_createNoteDeFrais($theMemberID, $theNoteDeFraisJSON, $theRemboursable, $theAttachedFiles , &$theUploadFolder, &$theFactureMailTo)
{
    //account.move RINV/2024/00023  6735
    // move_type	out_refund
    // type_name	Note de crédit


    // documents.document
    // 1803 Note de frais 250516.pdf
    //attachment_name	Note de frais 250516.pdf
    //url	Note de frais 250516.pdf
    //attachment_type	binary
    //type binary
    //file_size	148650
    // raw binary
    // name
    //access_url	https://spa-aviation.odoo.com/odoo/documents/AW8Aoj5iSliqf3kQ0MKbRQo70b
    //partner_id	[46, Mortier Alain]
    // attachment_id
    // voir odoo_avatar.php encripter base64

    //ir.attachment
    // id
    // res_id = documentid

    global $mysqli_link, $table_person,$userId;
    global $odoo_host, $odoo_db, $odoo_username, $odoo_password;
    global $atl_maxNumberOfPixels;
    //print("<br>OF_createNoteDeFrais: Start theMemberID=$theMemberID theRemboursable=$theRemboursable<br>");

   /*
    if(0) {
         print("<br>OF_createNoteDeFrais: Correction DB 1863<br>");
         // correction manuel de la BD odoo
          $odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
          // Dossier 1866 : "Touche pas a ca petit c**"
          $response = $odooClient->Update('documents.document', array(1863), array('folder_id' => 1866)) ;
          var_dump($response) ;

        return 0;
    }   
    */
    if($theMemberID=="") {
        print("<h2 style=\"color: red;\">ERROR:OF_createNoteDeFrais: Pas de membre sélectionné</h2>");
        return "";
    }
    // Create Note de Frais directly in ODOO
  
    //print("Attached Files;");
    //var_dump($theAttachedFiles);
    //print("<br>");

    $notedefraislines = json_decode($theNoteDeFraisJSON, true) ;
    $notedefraisSize=sizeof($notedefraislines);

    //print("OF_createFactureDHF($theFlightReferences, $theDate, $thelogbookids):started<br>");

    /*
        {"name":"Achat carburant", "type":"carburant", "description": "Décrire la destination et la raison", "quantity": 1, "unitaryprice": 0.0,"odooreference": "600000"},
        {"name":"Frais déplacement", "type":"deplacement", "description": "Decrire la destination et la raison", "quantity": 1, "unitaryprice": 0.45, "odooreference": "610300"},
        {"name":"Frais d expédition", "type":"expedition", "description": "Description de l expédition", "quantity": 1, "unitaryprice": 0.0,"odooreference": "610400"},
        {"name":"Manisfestation et repas", "type":"repas", "description": "Endroit de refuelling", "quantity": 1, "unitaryprice": 0.0,"odooreference": "610600"},
        {"name":"Achat accésoires", "type":"accessoire", "description": "Description achat et raison", "quantity": 1, "unitaryprice": 0.0,"odooreference": "600002"},
        {"name":"Achat Timbres poste", "type":"timbre", "description": "Description achat et raison", "quantity": 1, "unitaryprice": 0.0,"odooreference": "600106"},
        {"name":"Atterrissage et Parking", "type":"atterrissage", "description": "Description achat et raison", "quantity": 1, "unitaryprice": 0.0,"odooreference": "610100"},
        {"name":"Vol de nuit - contrôle aérien", "type":"vol_nuit", "description": "Description achat et raison", "quantity": 1, "unitaryprice": 0.0,"odooreference": "610101"}
    */

    $partner_customer_id=0;
    $partnerName="";
    $partner= array();
    $result = mysqli_query($mysqli_link, "SELECT * FROM $table_person WHERE jom_id=$theMemberID")
        		or journalise($userId, "E", "Cannot read table_person: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
            $partnerName=$row['first_name']." ".$row['last_name'];
            $partner["name"]=$row['first_name']." ".$row['last_name'];
            $partner["address"]=$row['address'];
            $partner["city"]=$row['zipcode']." ".$row['city'];;
            $partner["country"]=$row['country'];
            $partner["email"]=$row['email'];
            $partner_customer_id=$row['odoo_id'];
    }
    if($partner_customer_id==0) {
           print("<h2 style=\"color: red;\">ERROR:OF_createNoteDeFrais: Unknown partner</h2>");
           journalise($userId, "E", "OF_createNoteDeFrais: Unknown partner for member $theMemberID") ;
        return "";
    }
    $journal_ndf=OF_GetJournalID("client");
    $moveType="out_refund";
    if($theRemboursable=="") {
        print("<h2 style=\"color: red;\">ERROR:OF_createNoteDeFrais: Remboursable not defined</h2>");
        journalise($userId, "E", "OF_createNoteDeFrais: Remboursable not defined for member $theMemberID") ;
        return "";
   }
    if($theRemboursable=="1") {
        $journal_ndf=OF_GetJournalID("fournisseur");
        $moveType="in_invoice";
    }
    //print("moveType=$moveType, journal_ndf=$journal_ndf<br>");
    $uploadFolder="uploads/notedefrais";
    $attachedFileName=UploadFile($_FILES, "notedefrais_input_justificatif", $uploadFolder,"", $atl_maxNumberOfPixels);
    $invoice_date= date("Y-m-d") ;
    $invoice_date_due = date("Y-m-d", strtotime("+1 week")) ;
	
    // Version note de frais PDF
    if(1) {
        //print("<br>OF_createNoteDeFrais: PDF Version 2<br>");
        return PDF_createNoteDeFrais($notedefraislines, $theRemboursable, $invoice_date, $partner,$attachedFileName, $theUploadFolder, $theFactureMailTo);
    }
    // ======== DEACTIVATED ============================================================================================
    // Version creation directement dans ODOO
    // Deactive car cela crache les documents.
    // Il faudrait faire des essais sur une BD oddo de test et chercher le probleme

    $attachementID=OF_createAttachedDocument("Note de frais test patrick", $attachedFileName, $uploadFolder, $partner_customer_id);
    $attachementsID=array($attachementID);

    ini_set('display_errors', 1) ; // extensive error reporting for debugging
    $odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
    $invoice_lines = array() ;

    //$referencesMap= array();
    //OF_FillFlightMaps($referencesMap);

    $count=-1;
    for($i=0;$i<$notedefraisSize;$i++) {
        $nodedefraisLine=$notedefraislines[$i];
        $date=$nodedefraisLine["date"];
        $name=$nodedefraisLine["name"];
        $description=$nodedefraisLine["description"];
        $type=$nodedefraisLine["type"];
        $quantity=$nodedefraisLine["quantity"];
        $unitaryprice=$nodedefraisLine["unitary"];
        $montant=$nodedefraisLine["total"];
        $odooreference=$nodedefraisLine["odoo"];
        $odooanalytic=$nodedefraisLine["analytic"];
        print("name=$name, type=$type, description=$description, quantity=$quantity, unitaryprice=$unitaryprice, montant=$montant, odooreference=$odooreference, odooanalytic=$odooanalytic<br>");
 
        $count++;

        $code_600000=OF_GetAccountID($odooreference);
        if($code_600000==0) {
            return false;
        }
        $odoo_analytic=OF_GetAnalyticAccountID($odooanalytic) ;
        if($odoo_analytic==0) {
            return false;
        }
        //$plane_analytic=OF_GetAnalyticAccountID($plane);
        $libelle_name=$date."-".$description;
        print("libelle_name=$libelle_name, code_600000=$code_600000 , partner_customer_id=$partner_customer_id <br>");     
        // Partie Avion
        $invoice_lines[] = array(0, 0,
            array(
                'name' => db2web($libelle_name),
                //'product_id' => $plane_dhf_product_id,
                'account_id' => $code_600000,  
                'quantity' => $quantity,
                'price_unit' => $unitaryprice,
                'analytic_distribution' => array($odoo_analytic => 100)
            )) ;
    }

 	// Invoice creation	
    $nbfReference="Note de frais ". date("d-m-Y");
    $params =  array(array('partner_id' => intval($partner_customer_id), //(Must be of INT type else Odoo does not accept)
                    'ref' => db2web($nbfReference),
					'payment_reference' => db2web($nbfReference),
                    'move_type' => $moveType,
					'journal_id'=> $journal_ndf,
                    //cela bug la BD : 'attachment_ids'=>$attachementsID,
                    'invoice_date' => $invoice_date,
                    'invoice_origin' => 'Notes de frais',
                    'invoice_line_ids' => $invoice_lines)) ;
	print("</br> 1 Création node de frais invoice_date_due=$invoice_date_due</br>");
	echo var_dump($params);
    echo "</br>";
    if(1) {
        $invoiceID = $odooClient->Create('account.move', $params) ;
        echo var_dump($invoiceID);
        print("<br>Note de Frais pour " . implode(', ', $invoiceID) . "<br>") ;
    }
    else {
        print("<br>Note de Frais pas généerée dans ODOO <br>") ;
       
    }
    //print("<br>Note de Frais pour " . $invoiceID[0] . "<br>") ;

    return true;
}

//============================================
// Function: OF_createAttachedDocument
// Purpose: Creation d'un document dans ODOO et retourne le document ID
//============================================
function OF_createAttachedDocument($theLibelleName, $theAttachedFileName, $theUploadFolder, $thePartnerId)
{
    // documents.document
    // 1803 Note de frais 250516.pdf
    //attachment_name	Note de frais 250516.pdf
    //url	Note de frais 250516.pdf
    //attachment_type	binary
    //type binary
    //file_size	148650
    // raw binary
    // name
    //access_url	https://spa-aviation.odoo.com/odoo/documents/AW8Aoj5iSliqf3kQ0MKbRQo70b
    //partner_id	[46, Mortier Alain]
    // datas 
    // description
    // mimetype application/pdf image/jpeg application/o-spreadsheet
    // access_internal edit
    // voir odoo_avatar.php encripter base64

    global $mysqli_link, $userId;
    global $odoo_host, $odoo_db, $odoo_username, $odoo_password;


    print("<br>OF_createAttachedDocument: Start theLibelleName=$theLibelleName, theAttachedFileName=$theAttachedFileName, theUploadFolder=$theUploadFolder, thePartnerId=$thePartnerId<br>");
 
    $fname = $theUploadFolder.'/'.$theAttachedFileName;
	if (!file_exists($fname)) {
		print("OF_createAttachedDocument: Skipping, file $fname does not exist.<br>") ;
		return 0 ;
	}
    else {
  		print("OF_createAttachedDocument: file $fname exists.<br>") ;      
    }
    echo mime_content_type($fname)."<br>";
    $mimetype=mime_content_type($fname);
    if( $mimetype =="application/pdf") {
        $datas=base64_encode(file_get_contents($fname));
    }
    else {
        list($width, $height, $type, $attr) = getimagesize($fname) ;
        print("OF_createAttachedDocument: Image size W x H: $width x $height type=$type <br>") ;
        return 0;
        // TODO also support PNG based on $type (both in code below but also in the above SELECT)
        if ($type == IMAGETYPE_JPEG )
            $image = imagecreatefromjpeg($fname) ;
        elseif ($type == IMAGETYPE_GIF)
            $image = imagecreatefromgif($fname) ;
        elseif ($type == IMAGETYPE_PNG)
            $image = imagecreatefrompng($fname) ;
        else {
            print("OF_createAttachedDocument: Unknown image type ($type) for $fname<br>") ;
            return 0 ;
        }
        if (!$image) {
            print("OF_createAttachedDocument: $fname is not a valid image !! Skipping<br>") ;
            return 0 ;
        }
        $updates = array() ;
	    $updates['image_128'] = base64_encode(resize($image, $width, $height, 128));

        $datas=base64_encode(file_get_contents($fname));
	}
    // documents.document
    // 1803 Note de frais 250516.pdf
    //attachment_name	Note de frais 250516.pdf
    //url	Note de frais 250516.pdf
    //attachment_type	binary
    //type binary
    //file_size	148650
    // raw binary
    // datas 
    // name
    //access_url	https://spa-aviation.odoo.com/odoo/documents/AW8Aoj5iSliqf3kQ0MKbRQo70b
    //partner_id	[46, Mortier Alain]
    // description
    // mimetype application/pdf image/jpeg application/o-spreadsheet
    // access_internal edit
	// Document creation	
    $odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
    $nbfReference="Note de frais test patrick ". date("d-m-Y");
    $params =  array(array('partner_id' => intval($thePartnerId), //(Must be of INT type else Odoo does not accept)
                    'name' => db2web($nbfReference),
                    //'attachment_name' => $theAttachedFileName,  
                    'datas' => $datas,
                    'mimetype' => $mimetype,
                    // folder_if 1285 Note de credit membre, 1273 note de frais remboursable
                    'description' => db2web($theAttachedFileName)
                    )) ;
	print("</br> 1 Création node de frais Document $nbfReference</br>");
	echo var_dump($params);
    echo "</br>";
    if(1) {
        $documentID = $odooClient->Create('documents.document', $params) ;
        echo var_dump($documentID);
        print("<br>Note de Frais document pour " . implode(', ', $documentID) . "<br>") ;
        $attachementID=OF_GetAttachementIDFromDocumentID($documentID) ;
        print("attachementID=");
        var_dump($attachementID);
        print("<br>Note de Frais attachement $attachementID[0]<br>") ;
        return $attachementID[0];
    }
    else {
        print("<br>Note de Frais pas généerée dans ODOO <br>") ;
       
    }
    return 0;
}
//============================================
// Function: OF_GetAttachementIDFromDocumentID
// Purpose: Get the attachement number from document ID (8918596=>9976): see Model documents.document
//============================================
function OF_GetAttachementIDFromDocumentID($theDocumentID) 
{
    $odooClient=OF_GetOdooClient();
    $resultCode= $odooClient->SearchRead('documents.document', array(array(array('id', '=', $theDocumentID))),  array('fields'=>array('id', 'attachment_id'))); 
    $attachementID=0;
    foreach($resultCode as $fCode=>$desc) {
        $attachementID=$desc['attachment_id'];
        break;
    }
    return $attachementID;
}
//============================================
// Function: OF_DeactiveBon
// Purpose: Creation d'une OD pour transferer la valeur du compte d'attente 499001-2 -> 765000 (Produit exeptionel)
//============================================
function OF_DeactiveBon($theFlightID) {
    //print("OF_DeactiveBon: Start: $theFlightID");

    $code_765000=OF_GetAccountID(765000);
    $code_499001=OF_GetAccountID(499001);
    $code_499002=OF_GetAccountID(499002);
 
    //rapcs_flight : f_reference (f_id==t$heFlightID)
    $flyReference=OF_GetFlyReference($theFlightID);

    if(OF_IsExpiredFlight($theFlightID)) {
        return "This flight $flyReference is already expired!";
    }

    $partner_customer_id =  OF_GetPartnerID($flyReference,$theFlightID);
    $partnerName=OF_GetPartnerNameFromReference($flyReference);

    //Communication associated to the 499001-2 account
    $odooPaymentReference=OF_GetPaymentOdooReference($theFlightID);
    if($odooPaymentReference==0) {
        return "This flight $flyReference is unknown for ODOO (No ODOO reference) !";    
    }
    $communication49900x=OF_GetCommunicationFromOdooReference($odooPaymentReference);

    // table: rapcs_flight : f_type
    $flightType=OF_GetFlyType($theFlightID);
    $invoice_date_due = date("Y-m-d") ;
    if($flightType=="D") {
        // Flight V-IF-   -> 499002 -> 76500
        $accountCodeID=$code_499002;
        $reference_account499="Transfert de 4990002 ".$flyReference." (".$partnerName.")";

    }
    else if($flightType=="I") {
        // Flight V-INIT- -> 499001 -> 765000
        $accountCodeID=$code_499001;
        $reference_account499="Transfert de 4990001 ".$flyReference." (".$partnerName.")";
    }
    else if($flightType=="B") {
        // Flight V-INIT- -> 499002 -> 765000
        $accountCodeID=$code_499002;
        $reference_account499="Transfert de 4990002 (BON) ".$flyReference." (".$partnerName.")";
    }
    else {
        return "Type de vol inconnu: $flightType";
    }

    $valeur_compte_attente=OF_GetPaymentAmount($theFlightID);
    $invoice_lines_OD = array() ;
    // Partie debit compte attente Bon cadeau
    $reference_account=$communication49900x;
    $invoice_lines_OD[] = array(0, 0,
                array(
                    'name' => db2web($reference_account),
                    'account_id' => $accountCodeID, 
                    'debit' => $valeur_compte_attente
                )) ;
    // Partie credit compte client Bon cadeau
    $invoice_lines_OD[] = array(0, 0,
                array(
                    'name' => db2web($reference_account499),
                    'account_id' => $code_765000, 
                    'credit' => $valeur_compte_attente
                )) ;
        
    // Invoice creation	account.move
    $journal_transfert_init_if=OF_GetJournalID("trf");
    $params_OD =  array(array('partner_id' => intval($partner_customer_id),
                    'ref' => db2web($flyReference),
                    'move_type' => 'entry',
                    'journal_id'=> $journal_transfert_init_if,
                    'invoice_date_due' => $invoice_date_due,
                    'invoice_origin' => 'Gestion Bons',
                    'invoice_line_ids' => $invoice_lines_OD)) ;
    print("</br>Création OD V-INIT/If vers compte produit exceptionnel 765000</br>");
    //echo var_dump($params_OD);
    //print("<br>Pousser dans odoo<br>");
    if(1) {
        $odooClient=OF_GetOdooClient();
        $result_OD = $odooClient->Create('account.move', $params_OD) ;
        //print("<br>OD V-IF **** pour " . implode(', ', $result_OD) . " Name=".$name." Prix=".$valeur_compte_attente."<br>") ;
        // Rename the fly to "D-"+Reference and set as expired
        OF_SetFlightExpired($theFlightID,$flyReference);
    }

    return "";
}

//============================================
// Function: OF_ComputeDurationToBeInvoiced
// Purpose: Compute the duration of the flight to be invoiced (From logbook table)
//============================================
function OF_ComputeDurationToBeInvoiced($theLogBookRow) {
    
    $startHour=$theLogBookRow['l_start_hour'];
    $startMinute=$theLogBookRow['l_start_minute'];
    $endHour=$theLogBookRow['l_end_hour'];
    $endMinute=$theLogBookRow['l_end_minute'];

    $duration=($endHour-$startHour)*60+($endMinute-$startMinute);
    
    $plane=$theLogBookRow['l_plane'];
    $planeTableRow=OF_GetPlaneTableRow($plane);
    $coutMarge=$planeTableRow['cout_marge'];
    $duration-=$coutMarge;
    return $duration;
}

//============================================
// Function: OF_ComputeCostPerMinute
// Purpose: Compute the price/min for an aircraft
//============================================
function OF_ComputeCostPerMinute($thePlane) {
    $planeTableRow=OF_GetPlaneTableRow($thePlane);
    $cout=$planeTableRow['cout'];
    return  $cout;
}

//============================================
// Function: OF_GetPlaneTableRow
// Purpose: Get the row of a plane from the table RAPCS_Planes
//============================================
function OF_GetPlaneTableRow($thePlane) 
{
    global $mysqli_link, $table_planes,$userId;
    
    $plane=strtolower($thePlane);
    $result = mysqli_query($mysqli_link, "SELECT * FROM $table_planes WHERE id='$plane'")
    		or journalise($userId, "E", "Cannot read planes: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        return $row;
    }
    print("<h2 style=\"color: red;\">ERROR:OF_GetPlaneTableRow: Unknown Aircraft $thePlane</h2>");
    return array();
}
//============================================
// Function: OF_GetAccountID
// Purpose: Get the Account ID: see Model account.account 
//============================================
function OF_GetAccountID($theAccountNumber) 
{
    //account.account
    $codes=array(
        400000 => 158, //RAPCS - Clients
        499001 => 896, //RAPCS- Comptes d'attente - Initiations à réaliser
        499002 => 897, //RAPCS- Comptes d'attente - Vols decouvertes à réaliser
        499003 => 966, //RAPCS- Comptes d'attente - Paynovate paiements à identifier
        700000 => 315, //RAPCS - Club - Cotisation Club
        700101 => 942, //RAPCS - Avions - Ventes Heures de vols initiations
        700102 => 943, //RAPCS - Avions - Ventes Heures de vols decouvertes
        702002 => 949, //RAPCS - Instructions en vols - Initiations
        765000 => 957, //Produit exceptionnel

        600000 => 230,//RAPCS - Avion - Achat Carburant	
        600001 => 898,//RAPCS - Avion - Achat Lubrifiants
        600002 => 979,//RAPCS - Avions - Achats accessoires	
        600106 => 969,//RAPCS - Club - Achats de timbres poste	
        601000 => 231,//RAPCS - Club - Achats de fournitures de bureau	
        601001 => 899,//RAPCS - Club - Achats de fournitures Informatiques	
        601002 => 900,//RAPCS - Club - Achats de fournitures autres	
        601003 => 901,//RAPCS - Boutique - Achats de fournitures Boutique	
        601004 => 902,//RAPCS - Boutique - Achats des manuels	
        601005 => 903,//RAPCS - Boutique - Frais expédition manuels	
        602000 => 232,//RAPCS - Instructeurs au sol - Indemnités	
        602001 => 904,//RAPCS - Instructeurs en vol - Instruction	
        602002 => 905,//RAPCS - Instructeurs en vol - Initiations	
        602003 => 906,//RAPCS - Instructeurs en vol - Revalidation	
        602004 => 907,//RAPCS - Achats de services - Autres formations Aéronautiques	
        602005 => 908,//RAPCS - Achats de services - Autres formations Non Aéronautique	
        603000 => 233,//RAPCS - Avion - Maintenance Avion - Maintenance Périodique	
        603001 => 909,//RAPCS - Avion - Maintenance Avion - Non périodique Moteur	
        603002 => 910,//RAPCS - Avion - Maintenance Avion - Non périodique Avionique	
        603003 => 911,//RAPCS - Avion - Maintenance Avion - Non périodique Helice	
        603004 => 912,//RAPCS - Avion - Maintenance Avion - Non périodique Cellule	
        603005 => 913,//RAPCS - Avion - Maintenance Avion - Pesée et Centrage	
        603006 => 914,//RAPCS - Avion - Maintenance Avion - Redevance CAMO	
        603007 => 915,//RAPCS - Avion - Maintenance Avion - Redevance Certificat de Navigabilité	
        603008 => 916,//RAPCS - Avion - Maintenance Avion - Frais de convoyage	
        603009 => 972,//RAPCS - Avion - Maintenance Avion - AMP-Cardex	
        603010 => 990,//RAPCS - Avion - Maintenance Avion - Extension ARC	
        604000 => 234,//RAPCS - Avion - Achats de Pièces	
        604010 => 917,//RAPCS - Avion - Achats de Pneus	
        605000 => 35,//Purchases of Immovable Property for Resale	
        608000 => 36,//Discounts, Allowance and Rebates Received (-)	
        609000 => 37,//Decrease (Increase) in Stocks of Raw Materials	
        609100 => 38,//Decrease (Increase) in Stocks of Consumables	
        609400 => 39,//Decrease (Increase) in Stocks of Goods Purchased for Resale	
        609500 => 40,//Decrease (Increase) in Respect of Immovable Property for Resale	
        610000 => 41,//RAPCS - Locaux - Location Club House	
        610001 => 918,//RAPCS - Locaux - Hangar Avion	
        610002 => 919,//RAPCS - Locaux - Location de Salle	
        610003 => 920,//RAPCS - Locaux - Nettoyage des locaux, Réparation et Fournitures	
        610100 => 921,//RAPCS - Redevance à refacturer - Atterrissage et Parking	
        610101 => 922,//RAPCS - Redevances à refacturer - Vol de nuit et control aérien	
        610102 => 923,//RAPCS - Redevance à refacturer - TILEA	
        610103 => 967,//RAPCS - Avions - Redevance Radio Avion PH-AML	
        610104 => 989,//RAPCS - Club - Headset	
        610105 => 991,//RAPCS - Avions - Redevances Radio	
        610200 => 924,//RAPCS - Club - Assurance RC Club	
        610201 => 925,//RAPCS - Avions - Assurance	
        610202 => 926,//RAPCS - Club - Assurance RC Dirigeants	
        610203 => 927,//RAPCS - Club - Assurance RC Bénévoles	
        610300 => 928,//RAPCS - Club - Frais de déplacements	
        610400 => 929,//RAPCS - Club - Frais d'expédition (poste)	
        610500 => 930,//RAPCS - Club - Evacuation des déchets	
        610600 => 931,//RAPCS - Club - Manifestations et repas	
        610700 => 932,//RAPCS - Club - Cotisation federations	
        612100 => 933,//RAPCS - Club - Téléphonie et internet	
        612101 => 959,//RAPCS - Club - Services informatiques	
        612110 => 985,//RAPCS - Club - Publicités et Sponsoring	
        613200 => 971,//RAPCS - HONORAIRES COMPTABLES & EXPERTISES	
        615100 => 968,//RAPCS - Entretien réparation matériel informatique	
        615200 => 992,//RAPCS - Avions - Produits d'entretien	
        616000 => 963//RAPCS - Club - Publications légales, changements de status	

    );
    if (array_key_exists($theAccountNumber, $codes)) {
        return $codes["$theAccountNumber"];
    }
    print("<h2 style=\"color: red;\">ERROR:OF_GetAccountID: Unknown AccountNumber $theAccountNumber</h2>");
    return 0;
}
//============================================
// Function: OF_GetAccountNumberFromAccountID
// Purpose: Get the Account number from account ID (896=>499001): see Model account.account 
//============================================
function OF_GetAccountNumberFromAccountID($theAccountID) 
{
    global $of_accountIDNumberMap;
    if(is_null($of_accountIDNumberMap)) {
        $of_accountIDNumberMap=array();
    }
 
    if (array_key_exists($theAccountID, $of_accountIDNumberMap)) {
        return $of_accountIDNumberMap[$theAccountID];
    }

    $odooClient=OF_GetOdooClient();
    $resultCode= $odooClient->SearchRead('account.account', array(array(array('id', '=', $theAccountID))),  array('fields'=>array('id', 'code'))); 
    $accountNumber="";
    foreach($resultCode as $fCode=>$desc) {
        $accountNumber=$desc['code'];
        $of_accountIDNumberMap[$theAccountID]=$accountNumber;
        break;
    }
    return $accountNumber;
}

//============================================
// Function: OF_GetJournalID
// Purpose: Get the Journal ID: see Model account.journal
//============================================
function OF_GetJournalID($theJournal) 
{
    $journals=array(
        "if" => 17,   //Factures Clients (Vols Découvertes)
        "init" => 18, //Factures Clients (Vols Initiations)
        "trf" => 16,  //Trf 499001/499002 vers 400000
        "client" => 8, // Clients
        "fournisseur" => 9 // Fournisseur
    );
    if (array_key_exists($theJournal, $journals)) {
        return $journals["$theJournal"];
    }
    print("<h2 style=\"color: red;\">ERROR:OF_GetJournalID: Unknown Journal $theJournal</h2>");
    return 0;
}

//============================================
// Function: OF_GetAnalyticPilotID
// Purpose: Get the Analystic account ID: see Model account.analytic.account
//============================================
function OF_GetAnalyticPilotID($thePilotID) 
{
    $pilots= array(
        46 => "Benoît Mendes",    // FI Benoît Mendes
        50 => "Luc Wynand",       // FI Luc Wynand
        59 => "Nicolas Claessen", // FI Nicolas Claessen
        118 => "David Gaspar"     // FI David Gaspar
    );
    if (array_key_exists($thePilotID, $pilots)) {
        return OF_GetAnalyticAccountID($pilots[$thePilotID]);
    }
    print("<h2 style=\"color: red;\">ERROR:OF_GetAnalyticPilotID: Unknown FI $thePilotID</h2>");

    return 0;
} 
//============================================
// Function: OF_GetAnalyticAccountID
// Purpose: Get the Analystic account ID: see Model account.analytic.account
//============================================
function OF_GetAnalyticAccountID($theAnalyticAccount) 
{
    //account.analytic.account
    $accounts= array(
        'OO-ALD' => 26, 
        'OO-ALE' => 27, 
        'OO-APV' => 28, 
        'OO-FMX' => 29, 
        'OO-JRB' => 30, 
        'OO-SPQ' => 31, 
        'PH-AML' => 32,
        'OO-FUN' => 46, 
        "Benoît Mendes" => 36,    // FI Benoît Mendes
        "Luc Wynand" => 34,       // FI Luc Wynand
        "Nicolas Claessen" => 35, // FI Nicolas Claessen
        "David Gaspar" => 33,     // FI David Gaspar
        "club"=> 25,              // Aeroclub Cotisation
        "club_init_if" => 41.,    // INIT-IF
        "ecole" => 43.,           // Aeroclub Ecole
        "cotisation" => 25.,      // Aeroclub Cotisation
        "gestion" => 44.          // Aeroclub Gestion
    ); 
    if (array_key_exists($theAnalyticAccount, $accounts)) {
        return $accounts["$theAnalyticAccount"];
    }
    print("<h2 style=\"color: red;\">ERROR:OF_GetJournalID: Unknown Journal $theAnalyticAccount</h2>");
    return 0;
}  
    
//============================================
// Function: OF_GetPartnerID
// Purpose: Get the partner odoo ID: see Model (Client) res.partner
//.         Retrieve the partner from the odoo reference. If 0, it means "Bancontact"
//============================================
function OF_GetPartnerID($theFlightReference,$theFlyID) 
{
    //DHF = 347
    $pos = strpos(strtoupper($theFlightReference), "DHF-");
    if($pos!==false && $pos==0) {
        // Flight DHF
        // Partner 347	DHF Le Domaine Haute Fagne
        return 347; 
    }
    $pos = strpos(strtoupper($theFlightReference), "V-");
    if($pos===false || $pos!=0) {
        // Flight IF- or INIT-
        // Partner 345	Ventes comptoirs de Ini et découverte par carte
        return 345; 
    }
    else {
        $odooPaymentReferemce=OF_GetPaymentOdooReference($theFlyID);
        //print("OF_GetPartnerID: theFlyID=$theFlyID,odooPaymentReferemce=$odooPaymentReferemce<br>");
        if($odooPaymentReferemce!=0) {
            $partnerID=OF_GetPartnerIDFromPayment($odooPaymentReferemce);
            //print("OF_GetPartnerID: odooPaymentReferemce=$odooPaymentReferemce,partnerID=$partnerID<br>");
            if($partnerID!=0) {
                return $partnerID;
            }
        }
        // Client IF-INIT ciel avant 2024
        return 487;
    }
    print("<h2 style=\"color: red;\">ERROR:OF_GetPartnerID: Unknown Partner (Client) from odoo reference $theOdooReference</h2>");
    return 0;
}
    
//============================================
// Function: OF_GetPaymentOdooReference
// Purpose: Get the odoo reference to a payment from the flight id
//============================================
 
function OF_GetPaymentOdooReference($theFlyID)
{
    global $mysqli_link, $table_flights_ledger,$userId;
    //print("<br>OF_GetPaymentOdooReference:start<br>");
    $result = mysqli_query($mysqli_link, "SELECT * FROM $table_flights_ledger WHERE fl_flight=$theFlyID")
    		or journalise($userId, "E", "Cannot read ledger: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        $odooReference=$row['fl_odoo_payment_id'];
        //print("<br>OF_GetPaymentOdooReference:odooReference=$odooReference<br>");
        if($odooReference!=NULL && $odooReference>0) {
            return $odooReference;
        }
    }
    return 0;
}

//============================================
// Function: OF_GetPaymentAmount
// Purpose: Get the value associated a payment from the flight id referenced to a ODOO Reference
//============================================
 
function OF_GetPaymentAmount($theFlyID)
{
    global $mysqli_link, $table_flights_ledger,$userId;
    //print("<br>OF_GetPaymentValue:start<br>");
    $result = mysqli_query($mysqli_link, "SELECT * FROM $table_flights_ledger WHERE fl_flight=$theFlyID")
    		or journalise($userId, "E", "Cannot read ledger: " . mysqli_error($mysqli_link)) ;
    $amount=0.0;
    while ($row = mysqli_fetch_array($result)) {
        $odooReference=$row['fl_odoo_payment_id'];
        //print("<br>OF_GetPaymentOdooReference:odooReference=$odooReference<br>");
        if($odooReference!=NULL && $odooReference>0) {
            $amount=$amount+$row['fl_amount'];
            //print("OF_GetPaymentAmount:amount=$amount<br>");
        }
    }
    return $amount;
}
//============================================
// Function: OF_GetPaymentReference
// Purpose: Get the reference to a payment from the flight id
//============================================
 
function OF_GetPaymentReference($theFlyID)
{
    global $mysqli_link, $table_flights_ledger,$userId;
    //print("<br>OF_GetPaymentReference:start<br>");
    $result = mysqli_query($mysqli_link, "SELECT fl_reference FROM $table_flights_ledger WHERE fl_flight=$theFlyID")
    		or journalise($userId, "E", "Cannot read ledger: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        $edgerReference=$row['fl_reference'];
        //print("<br>OF_GetPaymentReference:odooReference=$$edgerReference<br>");
        if($edgerReference!=NULL) {
            return $edgerReference;
        }
    }
    return "";
}  

//============================================
// Function: OF_GetFlyReference
// Purpose: Get the reference of a flight from the flight id (241234 -> "V-IF-241234")
//============================================
 
function OF_GetFlyReference($theFlyID)
{
    global $mysqli_link, $table_flights,$userId;
    //print("<br>OF_GetPaymentReference:start<br>");
    $result = mysqli_query($mysqli_link, "SELECT f_reference FROM $table_flights WHERE f_id=$theFlyID")
    		or journalise($userId, "E", "Cannot read flight: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        $reference=$row['f_reference'];
        //print("<br>OF_GetPaymentReference:odooReference=$$edgerReference<br>");
        if($reference!=NULL) {
            return $reference;
        }
    }
    return "";
}

//============================================
// Function: OF_GetFlightIdFromReference
// Purpose: returns the flight id (241234) from flight reference ("V-IF-241234")
//============================================
 
function OF_GetFlightIdFromReference($theFlightReference)
{
    global $mysqli_link, $table_flights,$userId;
    //print("<br>OF_GetFlightIdFromReference:start<br>");
    $id=0;
    $result = mysqli_query($mysqli_link, "SELECT f_id FROM $table_flights WHERE f_reference='$theFlightReference'")
    		or journalise($userId, "E", "Cannot read flight: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        $id=$row['f_id'];
        break;
    }
    return $id;
}

//============================================
// Function: OF_GetFlownDateReference
// Purpose: returns the flown date from flight reference ("V-IF-241234")
//============================================
 
function OF_GetFlownDateReference($theFlightReference)
{
    global $mysqli_link, $table_flights,$userId;
    //print("<br>OF_GetFlownDateReference:start $theFlightReference<br>");
    $date="";
    $result = mysqli_query($mysqli_link, "SELECT f_date_flown FROM $table_flights WHERE f_reference='$theFlightReference'")
    		or journalise($userId, "E", "Cannot read flight: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        $date=$row['f_date_flown'];
        //print("<br>OF_GetFlownDateReference:date- $date<br>");
        break;
    }
    return $date;
}
//============================================
// Function: OF_GetFlyType
// Purpose: Get the type of a flight from the flight id (241234 -> "D")
//============================================
 
function OF_GetFlyType($theFlyID)
{
    global $mysqli_link, $table_flights,$userId;
    //print("<br>OF_GetPaymentReference:start<br>");
    $result = mysqli_query($mysqli_link, "SELECT f_type FROM $table_flights WHERE f_id=$theFlyID")
    		or journalise($userId, "E", "Cannot read flight: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        $type=$row['f_type'];
        //print("<br>OF_GetPaymentReference:odooReference=$$edgerReference<br>");
        if($type!=NULL) {
            return $type;
        }
    }
    return "";
}
//============================================
// Function: OF_IsExpiredFlight
// Purpose: returns true if the flight is expired
//============================================
 
function OF_IsExpiredFlight($theFlightID)
{
    global $mysqli_link, $table_flights,$userId;
    //print("<br>OF_GetPaymentReference:start<br>");
    $result = mysqli_query($mysqli_link, "SELECT f_expired FROM $table_flights WHERE f_id=$theFlightID")
    		or journalise($userId, "E", "Cannot read flight: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        $expired=$row['f_expired'];
        return $expired;
    }
    return 0;
}

//============================================
// Function: OF_GetAccountNumberFromPayment
// Purpose: Get the account Number from the odoo payment (499001, 499002, ...)
//============================================
function OF_GetAccountNumberFromPayment($odooPaymentReference)
{
    //global $odooClient;
    //global $odoo_host, $odoo_db, $odoo_username, $odoo_password;
    //print("<br>OF_GetAccountIDFromPayment:start $odooPaymentReference<br>");

    $accountNumber='';
    $odooIdString=strval($odooPaymentReference);
    $odooClient=OF_GetOdooClient();
    //if(!isset($odooClient)) {
    //    print("OF_GetAccountIDFromPayment:INIT odooClient<br>");
    //    $odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
    //}
    //if(1) return "xxxxxx";
       //$odooPaymentReference=11865;

    $result = $odooClient->SearchRead('account.move.line', array(array(array('id', '=', $odooPaymentReference))),  array('fields'=>array('id', 'name', 'move_type','account_id','debit', 'credit', 'partner_id', 'create_date'))); 
    foreach($result as $f=>$desc) {
        //print("OF_GetPartnerIDFromPayment: Account #$desc[id]: $desc[name]<br>");
    	//print("Account #$desc[id]: $desc[name], $desc[move_type], $desc[account_id], $desc[debit], $desc[credit], ".$desc['partner_id'][1] . "<br>\n") ;
        //echo var_dump($f);
        //echo "<br>";
        //echo var_dump($desc['account_id']);
    	$account_id = (isset($desc['account_id'])) ? $desc['account_id'] : '' ;
        //print("<br>OF_GetAccountIDFromPayment:account_id=$account_id<br>");
    	if(!is_bool($account_id)) {
    		$accountID=$account_id[0];
            $accountNumber=OF_GetAccountNumberFromAccountID($accountID);
    	}
    }
    //print("Account= $<br>");
    return $accountNumber;
}

//============================================
// Function: OF_GetCommunicationFromOdooReference
// Purpose: Get the communication (Libellé - Field name) from the odoo payment reference
//============================================
function OF_GetCommunicationFromOdooReference($odooPaymentReference)
{
    $communication="";
    $odooIdString=strval($odooPaymentReference);
    $odooClient=OF_GetOdooClient();
    $result = $odooClient->SearchRead('account.move.line', array(array(array('id', '=', $odooPaymentReference))),  array('fields'=>array('id', 'name'))); 
    foreach($result as $f=>$desc) {
     	$communication= (isset($desc['name'])) ? $desc['name'] : '';
        //print("<br>OF_GetCommunicationFromOdooReference:communication=$communication<br>");
        break;
    }
     return $communication;
}
//============================================
// Function: OF_GetPartnerIDFromJomID
// Purpose: Get the partner odoo ID from the joom_id (Table_Person)
//          returns 0 if the JoomID doesn't exist
//============================================
function OF_GetPartnerIDFromJomID($jomID)
{
    global $mysqli_link, $table_person,$userId;
    //print("<br>OF_GetPartnerIDFromJoomID:start jomID=$jomID<br>");
    $odoo_id=0;
    $result = mysqli_query($mysqli_link, "SELECT odoo_id FROM $table_person WHERE jom_id='$jomID'")
    		or journalise($userId, "E", "Cannot read person: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        $odoo_id=$row['odoo_id'];
    }
    return $odoo_id;
}

//============================================
// Function: OF_GetPartnerIDFromPayment
// Purpose: Get the partner odoo ID from the odoo payment
//============================================
function OF_GetPartnerIDFromPayment($odooPaymentReference)
{
    //global $odooClient;
    //global $odoo_host, $odoo_db, $odoo_username, $odoo_password;
    //print("<br>OF_GetPartnerIDFromPayment:start $odooPaymentReference<br>");
    $partnerID=0;
    $odooIdString=strval($odooPaymentReference);
    $odooClient=OF_GetOdooClient();
    //$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
    //$odooPaymentReference=11865;
    $result = $odooClient->SearchRead('account.move.line', array(array(array('id', '=', $odooPaymentReference))),  array('fields'=>array('id', 'name', 'move_type','account_id','debit', 'credit', 'partner_id', 'create_date'))); 
    foreach($result as $f=>$desc) {
        //print("OF_GetPartnerIDFromPayment: Account #$desc[id]: $desc[name]<br>");
    	//print("Account #$desc[id]: $desc[name], $desc[move_type], $desc[account_id], $desc[debit], $desc[credit], ".$desc['partner_id'][1] . "<br>\n") ;
        //echo var_dump($desc);
        //echo var_dump($desc['partner_id']);
    	$partner_id = (isset($desc['partner_id'])) ? $desc['partner_id'] : '' ;
        //print("<br>OF_GetPartnerIDFromPayment:partner_id=$partner_id<br>");
    	if(!is_bool($partner_id)) {
    		$partnerID=$partner_id[0];
            //print("<br>OF_GetPartnerIDFromPayment:partner=$partnerID<br>");
            
    	}
    }
    //print("Partner= $partner<br>");

    /*    $result = $odooClient->SearchRead('account.move.line', array(array('id', '=', '$odooPaymentReferemce')), 
        array('fields'=>array('id', 'name', 'move_type','account_id','debit', 'credit', 'partner_id', 'create_date'))) ;
            */
    return $partnerID;
}
//============================================
// Function: OF_GetPartnerNameFromReference
// Purpose: Get the passager Name from Reference (DHF-245678)
//============================================
function OF_GetPartnerNameFromReference($theReference)
{
    global $mysqli_link, $table_pax_role, $table_pax, $table_flight,$userId;
    //print("<br>OF_GetPartnerNameFromReference:start theReference=$theReference<br>");
    $result = mysqli_query($mysqli_link, "SELECT f_id FROM $table_flight WHERE f_reference='$theReference'")
    		or journalise($userId, "E", "Cannot read flight: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        $referenceID=$row['f_id'];
        //print("<br>OF_GetPartnerNameFromReference:referenceID=$referenceID<br>");
        $result1 = mysqli_query($mysqli_link, "SELECT * FROM $table_pax_role WHERE pr_flight=$referenceID AND pr_role='C'")
        		or journalise($userId, "E", "Cannot read pax_role: " . mysqli_error($mysqli_link)) ;
        while ($row1 = mysqli_fetch_array($result1)) {
            $paxID=$row1['pr_pax'];
            //print("<br>OF_GetPartnerNameFromReference:paxID=$paxID<br>");
            if($paxID>0) {
                $result2 = mysqli_query($mysqli_link, "SELECT * FROM $table_pax WHERE p_id=$paxID")
                		or journalise($userId, "E", "Cannot read pax: " . mysqli_error($mysqli_link)) ;
                while ($row2 = mysqli_fetch_array($result2)) {
                    $partnerName=$row2['p_lname']." ".$row2['p_fname'];
                    //print("<br>OF_GetPartnerNameFromReference:partnerName=$partnerName<br>");
                    return $partnerName;
                }
            }
        }
    }
    return "";
}

//============================================
// Function: OF_SetFlightInvoiceFromFlyID
// Purpose: Set the column f_invoice_ref in the table flight for a flightID (2423456)
//============================================
function OF_SetFlightInvoiceFromFlyID($theFlightID,$theInvoiceID)
{
    global $mysqli_link, $table_flights,$userId;
	mysqli_query($mysqli_link, "UPDATE $table_flights SET f_invoice_ref=$theInvoiceID WHERE f_id=$theFlightID")
    or 
		journalise($userId, "F", "Impossible de mettre à jour le flights_ledger: " . mysqli_error($mysqli_link)) ;	
    //print("OF_SetFlightInvoiceFromFlyID theFlightID=$theFlightID,theInvoiceID=$theInvoiceID");
    return true;
}

//============================================
// Function: OF_SetFlightExpired
// Purpose: Set a flight as Expired: the column t_expired is set to 1 and f_reference in the table flight for a flightID (2423456) Ex: V-INIT2423456 -> D-INIT2423456
//============================================
function OF_SetFlightExpired($theFlightID,$theReferenceName)
{
    global $mysqli_link, $table_flights,$userId;
    //print("OF_SetFlightExpired($theFlightID,$theReferenceName)<br>");
    $newReferenceName=$theReferenceName;
    $pos = strpos($newReferenceName, "V-");
    if($pos==0) {
        $newReferenceName="D-".substr($newReferenceName,2);
    }
    //print("UPDATE $table_flights SET f_reference='$newReferenceName' , f_expired=1 WHERE f_id=$theFlightID<br>");
 
	mysqli_query($mysqli_link, "UPDATE $table_flights SET f_reference='$newReferenceName', f_expired=1 WHERE f_id=$theFlightID")
    or 
		journalise($userId, "F", "Impossible de mettre à jour le rapcs_flights: " . mysqli_error($mysqli_link)) ;	
 
    return true;
}
//============================================
// Function: OF_SetFlightReference
// Purpose: Set the column f_reference in the table flight for a flightID (2423456) Ex: V-INIT2423456 -> D-V-INIT2423456
//============================================
function OF_SetFlightReference($theFlightID,$theNewReferenceName)
{
    global $mysqli_link, $table_flights,$userId;
	mysqli_query($mysqli_link, "UPDATE $table_flights SET f_reference='$theNewReferenceName' WHERE f_id=$theFlightID")
    or 
		journalise($userId, "F", "Impossible de mettre à jour le flights_flights: " . mysqli_error($mysqli_link)) ;	
    return true;
}

//============================================
// Function: OF_SetFlightInvoiceFromFlyReference
// Purpose: Set the column f_invoice_ref in the table flight for a flight reference (IF-2423456)
//============================================
function OF_SetFlightInvoiceFromFlyReference($theFlightReference,$theInvoiceID)
{
    global $mysqli_link, $table_flights,$userId;
    //print("OF_SetFlightInvoiceFromFlyReference: UPDATE $table_flights SET f_invoice_ref=$theInvoiceID WHERE f_reference='$theFlightReference'<br>");

	mysqli_query($mysqli_link, "UPDATE $table_flights SET f_invoice_ref=$theInvoiceID WHERE f_reference='$theFlightReference'")
    or 
		journalise($userId, "F", "Impossible de mettre à jour le flights_ledger: " . mysqli_error($mysqli_link)) ;	

    //print("OF_SetFlightInvoiceFromFlyReference theFlightID=$theFlightID,theInvoiceID=$theInvoiceID");
    return true;
}

//============================================
// Function: correctInvoiceCommunication
// Purpose: Correct communication : To uppercase, "V INIT " -> "V-INIT-" , ...
//============================================
function correctInvoiceCommunication($theInvoiceCommunication)
{
	$communicationUppercase = strtoupper($theInvoiceCommunication);

    $pos = strpos($communicationUppercase, "VINIT");
    if ($pos !== false) {
        $communicationUppercase=str_replace("VINIT","V-INIT",$communicationUppercase); ;
    } 
    $pos = strpos($communicationUppercase, "VIF");
    if ($pos !== false) {
        $communicationUppercase= str_replace("VIF ","V-IF",$communicationUppercase); ;
    } 
    $pos = strpos($communicationUppercase, "V.INIT");
    if ($pos !== false) {
        $communicationUppercase=str_replace("V.INIT","V-INIT",$communicationUppercase); ;
    } 
    $pos = strpos($communicationUppercase, "V.IF");
    if ($pos !== false) {
        $communicationUppercase= str_replace("V.IF ","V-IF",$communicationUppercase); ;
    } 
    $pos = strpos($communicationUppercase, "V INIT");
    if ($pos !== false) {
        $communicationUppercase=str_replace("V INIT","V-INIT",$communicationUppercase); ;
    } 
    $pos = strpos($communicationUppercase, "V IF");
    if ($pos !== false) {
        $communicationUppercase= str_replace("V IF ","V-IF",$communicationUppercase); ;
    } 
    $pos = strpos($communicationUppercase, "V-INIT ");
    if ($pos !== false) {
        $communicationUppercase=str_replace("V-INIT ","V-INIT-",$communicationUppercase); ;
    } 
    $pos = strpos($communicationUppercase, "V-IF ");
    if ($pos !== false) {
        $communicationUppercase= str_replace("V-IF ","V-IF-",$communicationUppercase); ;
    } 
    $pos = strpos($communicationUppercase, "V-INIT- ");
    if ($pos !== false) {
        return str_replace("V-INIT- ","V-INIT-",$communicationUppercase); ;
    } 
    $pos = strpos($communicationUppercase, "V-IF- ");
    if ($pos !== false) {
        return str_replace("V-IF- ","V-IF-",$communicationUppercase); ;
    } 
    $pos = strpos($communicationUppercase, "V-INIT-");
    if ($pos !== false) {
        return $communicationUppercase;
    } 
    $pos = strpos($communicationUppercase, "V-IF-");
    if ($pos !== false) {
        return $communicationUppercase;
    } 
    $pos = strpos($communicationUppercase, "V-INIT");
    if ($pos !== false) {
        return str_replace("V-INIT","V-INIT-",$communicationUppercase); ;
    } 
    $pos = strpos($communicationUppercase, "V-IF");
    if ($pos !== false) {
        return str_replace("V-IF","V-IF-",$communicationUppercase); ;
    } 
    return $communicationUppercase;
}

//============================================
// Function: analyzeTypeOfFlightOnCommunication
// Purpose: returns "?", "INIT" or "IF" after invoice communication
//============================================
function analyzeTypeOfFlightOnCommunication($theInvoiceCommunication)
{
    $pos = strpos($theInvoiceCommunication, "V-INIT-");
    if ($pos !== false) {
        return "INIT";
    } 
    $pos = strpos($theInvoiceCommunication, "V-IF-");
    if ($pos !== false) {
        return "IF";
    } 
    return "?";
}

//============================================
// Function: OF_InvoiceDueDays
// Purpose: returns the number of days after the invoice due date for a partner id
//============================================
function OF_InvoiceDueDays($theOdooPartnerReference)
{
    $dueDays=-1;
    $dueDate=OF_LastInvoiceDueDate($theOdooPartnerReference);
    if($dueDate=="") {
        return $dueDays;
    }
    $dueDateTime = new DateTime($dueDate) ;
    $today = new DateTime() ;

    $diff=date_diff($dueDateTime,$today);
    $dueDays=number_format($diff->format("%a"));
    if($today<$dueDateTime) {
        $dueDays*=-1;
    }
    return $dueDays;
}
//============================================
// Function: OF_LastInvoiceDueDate
// Purpose: returns the invoice due date for a partner id
//============================================
function OF_LastInvoiceDueDate($theOdooPartnerReference)
{
    // TODO added by evyncke 2025-04-28: new Odoo seems to use invoice_payment_term_id rather than invoice_date_due (link to account.payment.term)
    //print("OF_LastInvoiceDueDate($theOdooPartnerReference)<br>");
    $odooClient=OF_GetOdooClient();
    $result= $odooClient->SearchRead('account.move', 
        array(array(
            array('partner_id.id', '=', $theOdooPartnerReference),
            array('move_type', '=', 'out_invoice'),
            array('state', '=', 'posted'),
            '|', array('payment_state', '=', 'not_paid'), array('payment_state', '=', 'partial'),
        )),  
        array('fields'=>array('id', 'partner_id', 'invoice_date_due', 'status_in_payment','payment_state'))); 
    $DueDate="";
    foreach($result as $f=>$desc) {
      // echo var_dump($desc);
        //print('<br>');

        $status_in_payment=(isset($desc['status_in_payment'])) ? $desc['status_in_payment'] : '' ;
        if($status_in_payment=="paid") {
            // Already paid => not_paid and partial
            continue;
        }
        $invoiceDueDate=(isset($desc['invoice_date_due'])) ? $desc['invoice_date_due'] : '' ;
        $invoiceDate=(isset($desc['invoice_date'])) ? $desc['invoice_date'] : '' ;
        if($DueDate=="" || $invoiceDueDate<$DueDate) {
            $DueDate=$invoiceDueDate;
        }
        //$payment_state=(isset($desc['payment_state'])) ? $desc['payment_state'] : '' ;
        //$id=(isset($desc['id'])) ? $desc['id'] : '' ; 
        //print("theOdooPartnerReference=$theOdooPartnerReference id=$id invoiceDueDate=$invoiceDueDate  DueDate=$DueDate move_type=$move_type payment_state=$payment_state status_in_payment=$status_in_payment<br>");
    }
    return $DueDate;
}


//============================================
// Function: OF_CreateNewMember
// Purpose: Create a new member in your tables and in odoo
//============================================
function OF_CreateNewMember( 
            $prenom,
            $nom,
            $username,
            $password,
            $datenaissance,
            $email,
            $telephone,
            $adresse,
            $codepostal,
            $ville,
            $pays,
            $motivation,
            $typemembre,
            $qualification,
            $licence,
            $validitemedicale,
            $validiteelp,
            $courstheorique,
            $cotisation,
            $caution,
            $dateinscription,
            $factureodoo,
            $societe,
            $nomsociete,
            $bcesociete,
            $adressesociete,
            $codepostalsociete,
            $villesociete,
            $payssociete,
            $contactnom,
            $contactlien,
            $contactphone,
            $contactmail
)
{
    // Group : INSERT INTO $table_user_usergroup_map (user_id, group_id)  Voir gestionmembre.php
    //   Tables company: (tables rapcs_company et rapcs_company_member).
    /*
Questions:
jom_users
- Suffit de remplir les champs pour être compatible joomla?
- id doit il être introduit ou fait automatiquement?
- password : comment faire?
- ne pas introduire block? sendmail?, lastvisitdate, activation, lasresettime, reset count, otep, requirereset, authprovider
params=mediumtext? {"admin_style":"","admin_language":"","language":"fr-FR","editor":"","timezone":""} == default
// Joomla specific table names
****$table_users = 'jom_users' ;
Fields:
id = rien ou id+1
name = "pierre dupont"
username= "pdupont"
RegisterDate = today()
lastvisitDate = -
activation = -
params = {"admin_style":"","admin_language":"","language":"fr-FR","editor":"","timezone":""}
lastResetTime = -
Date of last password reset = -
resetCount = -
lastResetTime = -
otpKey = -
otep = -
requireReset = -
authProvider = -

****$table_user_usergroup_map = 'jom_user_usergroup_map' ;
user_id =  id de joomla
group_id = (membre=18,  Eleves=16, pilote=14)

****jom_user_profiles : Utilisée???
****jom_user_keys : Utilisée???

****$table_person = 'rapcs_person' ;
has_passwod: meme que jom_users?
Profile?
view_type=?
notification?
$table_validity = 'rapcs_validity' ;
$table_validity_type = 'rapcs_validity_type' ;


$table_bk_balance = 'rapcs_bk_balance' ;
$table_bk_invoices = 'rapcs_bk_invoices' ;
$table_bk_ledger = 'rapcs_bk_ledger' ;
$table_blocked = 'rapcs_blocked' ;
$table_payconiq = 'rapcs_payconiq' ;

$table_company = 'rapcs_company' ;
$table_company_member = 'rapcs_company_member' ;
$table_membership_fees = 'rapcs_bk_fees' ;



*/
    global $table_users,$table_person,$table_user_usergroup_map,$table_company, $table_company_member;
    global $userId,$mysqli_link,$joomla_student_group,$joomla_pilote_group,$joomla_member_group;

    $insertFlag=true;
/*
    print("<br> OF_CreateNewMember:Started:<br>
        prenom=$prenom<br>
        nom=$nom<br>
        username=$username<br>
        password=$password<br>
        datenaissance=$datenaissance<br>
        email=$email<br>
        telephone=$telephone<br>
        adresse=$adresse<br>
        codepostal=$codepostal<br>
        ville=$ville<br>
        pays=$pays<br>
        motivation=$motivation<br>
        typemembre=$typemembre<br>
        qualification=$qualification<br>
        licence=$licence<br>
        validitemedicale=$validitemedicale<br>
        validiteelp=$validiteelp<br>
        courstheorique=$courstheorique<br>
        cotisation=$cotisation<br>
        caution=$caution<br>
        dateinscription=$dateinscription<br>
        factureodoo=$factureodoo<br>
        societe=$societe<br>
        nomsociete=$nomsociete<br>
        bcesociete=$bcesociete<br>
        adressesociete=$adressesociete<br>
        codepostalsociete=$codepostalsociete<br>
        villesociete=$villesociete<br>
        payssociete=$payssociete<br>
        contactnom= $contactnom<br>
        contactlien=$contactlien<br>
        contactphone=$contactphone<br>
        contactmail<br>
     ");
     */
    $fullName=$prenom." ".$nom;
    if($password!="Rapcs123!") {
        return "Today the password must be Rapcs123!. Other password not yet implemented!";
    }
    $hashPassword='$2y$10$cBt1zEWomm7NOKxtrQGES.skeauwcURaiEukb/IutAXS0hgbeB2AW';
    $registerDate=$dateinscription." 12:00:00";
    $params='{"admin_style":"","admin_language":"","language":"fr-FR","editor":"","timezone":""}';
 
    //1. Check if the e-mail already exists (Only one time) in $table_users = 'jom_users'
    $sql= "SELECT email FROM $table_users WHERE email='$email'";
    //print("OF_CreateNewMember:$sql<br>");
    $result = mysqli_query($mysqli_link, $sql)
    	or journalise($userId, "E", "Cannot read rapcs_person " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        // The email address already exist then stop
        return "The email address is aready used (jom_users): Use a new email. The new member is not created.";
    }
    if($societe=="oui") {
        return "Programmation avec societe pas terminee! The new member is not created!";
    }
    //2. Check if the e-mail already exists (Only one time) in $table_person = 'rapcs_person' ; (Should be the same but to be sure)
    $sql= "SELECT email FROM $table_person WHERE email='$email'";
    //print("OF_CreateNewMember:$sql<br>");
    $result = mysqli_query($mysqli_link, $sql)
    	or journalise($userId, "E", "Cannot read jom_users: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        // The email address already exist then stop
        return "The email address is aready used (rapcs_person): Use a new email. The new member is not created.";
    }

     //3. Insert the new member in the table $table_users = 'jom_users' (Name, username,...)
    $sql= "INSERT INTO $table_users (name, username, email, password, registerDate, params)
		    VALUES ('$fullName', '$username', '$email', '$hashPassword', '$registerDate', '$params')";
    print("OF_CreateNewMember:$sql<br>");
    if(0 && $insertFlag) {
       $result = mysqli_query($mysqli_link, $sql)
    	or journalise($userId, "E", "Cannot insert into jom_users: " . mysqli_error($mysqli_link)) ;
		$jom_id = mysqli_insert_id($mysqli_link) ; 
    }
    else {
        $jom_id=560;
    }

 	//	    or journalise($userId, "F", "Impossible d'ajouter un paiement: " . mysqli_error($mysqli_link)) ;
    //4. Insert the new member in the table $table_person = 'rapcs_person' (Name, address, contact, ...)
    $sql= "INSERT INTO $table_person (jom_id, name, first_name, last_name, email, address, zipcode, city, country, cell_phone, lang, birthdate, contact_name, contact_relationship, contact_phone,contact_email)
		    VALUES ($jom_id, '$username', '$prenom', '$nom', '$email','$adresse', '$codepostal', '$ville', '$pays', '$telephone', 'francais','$datenaissance', '$contactnom','$contactlien', '$contactphone' ,'$contactmail')";
    print("OF_CreateNewMember:$sql<br>");
    if($insertFlag) {
       $result = mysqli_query($mysqli_link, $sql)
    	or journalise($userId, "E", "Cannot insert into rapcs_person: " . mysqli_error($mysqli_link)) ;
    }

    //5. Insert the new member in the table  $table_user_usergroup_map = 'jom_user_usergroup_map' (Type of member)
    // Insert like a member
    $sql= "INSERT INTO $table_user_usergroup_map (user_id, group_id)
		    VALUES($jom_id, $joomla_member_group)";
    print("OF_CreateNewMember:$sql<br>");
    if($insertFlag) {
        $result = mysqli_query($mysqli_link, $sql)
        or journalise($userId, "E", "Cannot insert into jom_user_usergroup_map: " . mysqli_error($mysqli_link)) ;
    }
    if($typemembre!="nonnaviguant") {
        // Insert like a pilot or a student
        $groupId=$joomla_student_group;
        if($typemembre=="pilote") $groupId=$joomla_pilote_group;

        $sql= "INSERT INTO $table_user_usergroup_map (user_id, group_id)
                VALUES($jom_id, $groupId)";
        print("OF_CreateNewMember:$sql<br>");
        if($insertFlag) {
            $result = mysqli_query($mysqli_link, $sql)
            or journalise($userId, "E", "Cannot insert into rapcs_user_usergroup_map: " . mysqli_error($mysqli_link)) ;
        }
    }

    //6. Insert the company of new member in the table  $table_company = 'rapcs_company'+ $table_company_member = 'rapcs_company_member' ;
    if($societe=="oui") {
        $sql= "INSERT INTO $table_company(c_name, c_address, c_zipcode, c_city, c_country,c_bce)
        VALUES ( '$nomsociete', '$adressesociete', '$codepostalsociete', '$villesociete', '$payssociete', '$bcesociete)";
        print("OF_CreateNewMember:$sql<br>");
        if($insertFlag) {
            $result = mysqli_query($mysqli_link, $sql)
            or journalise($userId, "E", "Cannot insert into rapcs_company: " . mysqli_error($mysqli_link)) ;
 	        $societeId = mysqli_insert_id($mysqli_link) ; 
        }
        $sql= "INSERT INTO $table_company_member(cm_member, cm_company)
        VALUES ( $jom_id, $societeId)";
        print("OF_CreateNewMember:$sql<br>");
        if($insertFlag) {
            $result = mysqli_query($mysqli_link, $sql)
            or journalise($userId, "E", "Cannot insert into rapcs_company_member: " . mysqli_error($mysqli_link)) ;
       }
    }

    //7. Insert the validity of new member in the table  $table_validity = 'rapcs_validity' + $table_validity_type = 'rapcs_validity_type' ;
    //8. Insert the new member in Odoo + adapt rapcs_person
    if($insertFlag) {
        OF_AddPartnerInOdoo(
            $jom_id,
            $prenom,
            $nom,
            $email,
            $telephone,
            $adresse,
            $codepostal,
            $ville,
            $pays);
    }

    //9. Insert the company of the new member in Odoo+  adapt rapcs_person
    if(0) {
        // add the company in odoo
        $companyOdooId=OF_AddPartnerInOdoo(
            0, // Pas de jom_id pour une societe
            "",
            $nomsociete,
            "",
            "",
            $adressesociete,
            $codepostalsociete,
            $villesociete,
            $payssociete
        ) ;
    }
    //10. Insert the company of the new member in Odoo
    if(0) {
        // Add child_ids[osooIdMemeber] dans res_partener de la societe
    }
    //11. Create the invoice for caution + cotisation
    $cotisationType="naviguant";
    if($typemembre=="nonnaviguant") $cotisationType="nonnaviguant";
    if($insertFlag) {
        if(!OF_CreateFactureCotisation($jom_id, $cotisationType, substr($dateinscription,0,4))) {
            return "Impossible to create the invoice in Odoo!";
        }
    }
    //12. Send a mail
     print("<a href=\"mailto:$email?cc=benoitmendes@hotmail.com,pendersbernard@gmail.com,vintens.ch@gmail.com,eric@vyncke.org,patrick.reginster@gmail.com&subject=Acces%20au%20site%20du%20RAPCS&body=Bonjour%20$fullName,%0D%0Aline1<br>line2\" target=\"_top\">Send mail to the new member!</a><br>");
    // Reste cours theorique et motivation ?
 
    return "";
}

//============================================
// Function: OF_AddPartnerInOdoo
// Purpose: Create a new partner in odoo
//============================================

function OF_AddPartnerInOdoo(
            $jom_id,
            $prenom,
            $nom,
            $email,
            $telephone,
            $adresse,
            $codepostal,
            $ville,
            $pays
    ) 
{
    global $mysqli_link,$table_person,$userId;
    print("OF_AddPartnerInOdoo:started<br>");
    // Let's create a Odoo partner/client on request
    // Is the Jom_is defined?
    $result = mysqli_query($mysqli_link, "SELECT * FROM $table_person WHERE jom_id = $jom_id")
        or journalise($userId, "F", "Cannot read $table_person for #$jom_id: " . mysqli_error($mysqli_link)) ;
    $row = mysqli_fetch_array($result) 
        or journalise($userId, "F", "User $jom_id not found") ;

    $countryid=20; // Belgique
    if($pays=="Luxembourg") $countryid=133;
    if($pays=="France") $countryid=75;

    //Add the partner in Odoo

    $student_tag = GetOdooCategory('Student') ;
    $pilot_tag = GetOdooCategory('Pilot') ;
    $member_tag = GetOdooCategory('Member') ;
  //if(1) return;

    $odooClient=OF_GetOdooClient();
    $odooid = $odooClient->Create('res.partner', array(
        'name' => db2web("$nom $prenom"),
        'complete_name' => db2web("$nom $prenom"),
        'property_account_receivable_id' => GetOdooAccount('400100', db2web("$nom $prenom")) ,
        'street' => db2web($adresse),
        'zip' => db2web($codepostal),
        'city' => db2web($ville),
        'email' => db2web($email),
        'phone' => db2web($telephone),
        'mobile' => db2web($telephone),
        'country_id' => db2web($countryid)
        //'category_id' => db2web($categoryid);
    )) ;

    // Link the odooid in the rapcs_person table
    mysqli_query($mysqli_link, "UPDATE $table_person SET odoo_id = $odooid WHERE jom_id = $jom_id") 
    or journalise($userId, "E", "Cannot set Odoo customer for user #$row[jom_id]") ;
    print("OF_AddPartnerInOdoo: $jom_id inserted in odoo partner=$odooid<br>");
}
function GetOdooAccount($code, $fullName) {
    static $cache = array() ;
    print("GetOdooAccount:started<br>");
  //if(1) return;
   $odooClient=OF_GetOdooClient();
    if (isset($cache[$code])) return $cache[$code] ;
    $result = $odooClient->SearchRead('account.account', array(array(
		array('account_type', '=', 'asset_receivable'),
		array('code', '=', $code))), 
	array()) ; 
    if (count($result) > 0) {
        $cache[$code] = $result[0]['id'] ;
    	return $result[0]['id'] ;
    }
    // Customer account does not exist... Need to create one
    $id = $odooClient->Create('account.account', array(
        'name' => $fullName,
        'account_type' => 'asset_receivable',
        'internal_group' => 'asset',
        'code' => $code,
        'name' => "$fullName")) ;
    if ($id > 0) {
        $cache[$code] = $id ;
        return $id ;
    } else
        return 158 ; // Harcoded default 400000 in RAPCS2.odoo.com
}
// Role being 'student', 'member', ...
function GetOdooCategory($role) {

    static $cache = array() ;
    $odooClient=OF_GetOdooClient();
    if (isset($cache[$role])) return $cache[$role] ;
    $result = $odooClient->SearchRead('res.partner.category', array(array(
		array('name', '=', $role))), 
	array()) ; 
    if (count($result) > 0) {
        $cache[$role] = $result[0]['id'] ;
    	return $result[0]['id'] ;
    }
    // Category does not exist... Need to create one
    $id = $odooClient->Create('res.partner.category', array(
        'name' => $role, 'display_name' => $role)) ;
    if ($id > 0) {
        $cache[$role] = $id ;
        return $id ;
    }
}
?>