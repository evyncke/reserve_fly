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
require_once "dbi.php" ;
require_once 'odoo.class.php' ;

class OdooFlight {

    function __construct() {
    }

    # Read return all records from one model based on their IDs
    function Read() {
    }
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
//============================================
function OF_FillFlightOdooMaps(&$theOdooPaymentMap,&$thePaymentFlightMap,&$theLedgerIdMap,&$theReferenceIDMap,&$theGiftFlagMap) {
    global $mysqli_link, $table_flights_ledger,$table_flights;

    $result = mysqli_query($mysqli_link, "SELECT * FROM $table_flights_ledger JOIN $table_flights ON fl_flight=f_id")
    		or journalise($userId, "E", "Cannot read ledger: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        $fl_odoo_payment_id=$row['fl_odoo_payment_id'];
        $f_reference=$row['f_reference'];
        if($fl_odoo_payment_id!="") {
            $theOdooPaymentMap[$fl_odoo_payment_id]=$f_reference;
         }
         $theReferenceIDMap[$f_reference]=$row['f_id'];
         $thePaymentFlightMap[$f_reference]=$row['fl_amount'];
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
    global $mysqli_link, $table_flights;

    $result = mysqli_query($mysqli_link, "SELECT * FROM $table_flights")
    		or journalise($userId, "E", "Cannot read ledger: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        $f_reference=$row['f_reference'];
        $theReferenceIDMap[$f_reference]=$row['f_id'];
    }
    //echo var_dump($theReferenceIDMap);
}

//============================================
// Function: OF_createFactureIF
// Purpose: Create an invoice for an IF flight
//============================================
function OF_createFactureIF($theFlightReference, $theDate, $theLogbookid, $theMontant, $theFlyID) {
    global $mysqli_link, $table_logbook;
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
    $plane_analytic=OF_GetAnalyticAccountID($plane);
    $analytic_club_init_if=OF_GetAnalyticAccountID("club_init_if");
    $journal_if=OF_GetJournalID("if");
    $invoice_date_due = date("Y-m-d", strtotime("+1 week")) ;
	//print("</br>Création facture V-IF</br>");
	// Reference facture : V-IF-24xxxx + 50€ par carte
	$libelle_name=$flyReference." ".$date;
	
    ////journalise($userId, "I", "Odoo invoices generation started ($odoo_host)") ;			
    ini_set('display_errors', 1) ; // extensive error reporting for debugging
    $odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
    
    
	// Partie Avion
    $invoice_lines = array() ;
    $invoice_lines[] = array(0, 0,
		array(
			'name' => $libelle_name,
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
			'name' => $libelle_name,
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
    $invoiceID = $odooClient->Create('account.move', $params) ;
    //echo var_dump($invoiceID);
    //print("<br>Facture IF pour " . implode(', ', $invoiceID) . "<br>") ;
    //print("<br>Facture IF pour " . $invoiceID[0] . "<br>") ;
    OF_SetFlightInvoiceRef($theFlyID, $invoiceID[0]);
    
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
    	$reference_account499="Transfert de 4990002 ".$flyReference;

        $valeur_compte_attente=floatval($theMontant);
        $invoice_lines_OD = array() ;
    	// Partie debit compte attente Bon cadeau
    	$reference_account="Transfert vers 4000000 ".$flyReference;
    	$invoice_lines_OD[] = array(0, 0,
    				array(
    					'name' => $reference_account,
    					'account_id' => $accountCodeID, 
    					'debit' => $valeur_compte_attente
    				)) ;
    	// Partie credit compte client Bon cadeau
    	$invoice_lines_OD[] = array(0, 0,
    				array(
    					'name' => $reference_account499,
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
        echo var_dump($params);
        //print("<br>Pousser ****** dans odoo<br>");
        $result_OD = $odooClient->Create('account.move', $params_OD) ;
        //print("<br>OD V-IF **** pour " . implode(', ', $result_OD) . " Name=".$name." Prix=".$valeur_compte_attente."<br>") ;
    }
    return true;
}
//============================================
// Function: OF_createFactureINIT
// Purpose: Create an invoice for an INIT flight
//============================================
function OF_createFactureINIT($theFlightReference, $theDate, $theLogbookid, $theMontant, $theFlyID) {
    global $mysqli_link, $table_logbook;
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
    $cost_FI = 50.;
	$partner_customer_id =  OF_GetPartnerID($theFlightReference,$theFlyID);
    $plane_analytic=OF_GetAnalyticAccountID($plane);
    $analytic_club_init_if=OF_GetAnalyticAccountID("club_init_if");
    $journal_if=OF_GetJournalID("init");
    $invoice_date_due = date("Y-m-d", strtotime("+1 week")) ;
	//print("</br>Création facture V-INIT</br>");
	// Reference facture : V-INIT-24xxxx 
	$libelle_name=$flyReference." ".$date;
	
    $FI_analytic=OF_GetAnalyticPilotID($pilot);
        
    ////journalise($userId, "I", "Odoo invoices generation started ($odoo_host)") ;			
    ini_set('display_errors', 1) ; // extensive error reporting for debugging
    $odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
    
    
	// Partie Avion
    $invoice_lines = array() ;
    $invoice_lines[] = array(0, 0,
		array(
			'name' => $libelle_name,
			'account_id' => $code_700101,  
			'quantity' => $duration,
			'price_unit' => $cost_plane_minute,
            'analytic_distribution' => array($plane_analytic => 100)
		)) ;
		
	// Partie FI
    $invoice_lines[] = array(0, 0,
		array(
			'name' => $libelle_name,
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
	//print("</br> 1 Création facture IF invoice_date_due=$invoice_date_due</br>");
	//print("params=$params");
	//echo var_dump($params);
    $invoiceID = $odooClient->Create('account.move', $params) ;
    //echo var_dump($invoiceID);
    //print("<br>Facture IF pour " . implode(', ', $invoiceID) . "<br>") ;
    //print("<br>Facture IF pour " . $invoiceID[0] . "<br>") ;
    OF_SetFlightInvoiceRef($theFlyID, $invoiceID[0]);
    
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
    	$reference_account499="Transfert de 4990001 ".$flyReference;

        $valeur_compte_attente=floatval($theMontant);
        $invoice_lines_OD = array() ;
    	// Partie debit compte attente Bon cadeau
    	$reference_account="Transfert vers 4000000 ".$flyReference;
    	$invoice_lines_OD[] = array(0, 0,
    				array(
    					'name' => $reference_account,
    					'account_id' => $accountCodeID, 
    					'debit' => $valeur_compte_attente
    				)) ;
    	// Partie credit compte client Bon cadeau
    	$invoice_lines_OD[] = array(0, 0,
    				array(
    					'name' => $reference_account499,
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
    	//print("</br> 2 Création OD V-INIT</br>");
        //echo var_dump($params);
        //print("<br>Pousser dans odoo<br>");
        $result_OD = $odooClient->Create('account.move', $params_OD) ;
        //print("<br>OD V-IF **** pour " . implode(', ', $result_OD) . " Name=".$name." Prix=".$valeur_compte_attente."<br>") ;
    }
    return true;
}
//============================================
// Function: OF_createFactureDHF
// Purpose: Create an invoice for an DHF flight
//============================================
function OF_createFactureDHF($theFlightReferences, $theDate, $thelogbookids) {
    global $mysqli_link, $table_logbook;
    global $odoo_host, $odoo_db, $odoo_username, $odoo_password;
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
                    'analytic_distribution' => array($analytic_club_init_if => 100)
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
    //echo var_dump($invoiceID);
    //print("<br>Facture DHF pour " . implode(', ', $invoiceID) . "<br>") ;
    //print("<br>Facture DHF pour " . $invoiceID[0] . "<br>") ;
    //OF_SetFlightInvoiceRef($theFlyID, $invoiceID[0]);
    
    return true;
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
    global $mysqli_link, $table_planes;
    
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
    $codes=array(
        400000 => 158, //RAPCS - Clients
        499001 => 896, //RAPCS- Comptes d'attente - Initiations à réaliser
        499002 => 897, //RAPCS- Comptes d'attente - Vols decouvertes à réaliser
        499003 => 966, //RAPCS- Comptes d'attente - Paynovate paiements à identifier
        700000 => 315, //RAPCS - Club - Cotisation Club
        700101 => 942, //RAPCS - Avions - Ventes Heures de vols initiations
        700102 => 943, //RAPCS - Avions - Ventes Heures de vols decouvertes
        702002 => 949  //RAPCS - Instructions en vols - Initiations
    );
    if (array_key_exists($theAccountNumber, $codes)) {
        return $codes["$theAccountNumber"];
    }
    print("<h2 style=\"color: red;\">ERROR:OF_GetAccountID: Unknown AccountNumber $theAccountNumber</h2>");
    return 0;
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
        "trf" => 16   //Trf 499001/499002 vers 400000
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
    $accounts= array(
        'OO-ALD' => 26, 
        'OO-ALE' => 27, 
        'OO-APV' => 28, 
        'OO-FMX' => 29, 
        'OO-JRB' => 30, 
        'OO-SPQ' => 31, 
        'PH-AML' => 32,
        "Benoît Mendes" => 36,    // FI Benoît Mendes
        "Luc Wynand" => 34,       // FI Luc Wynand
        "Nicolas Claessen" => 35, // FI Nicolas Claessen
        "David Gaspar" => 33,     // FI David Gaspar
        "club"=> 25,              // Aeroclub
        "club_init_if" => 41.     // INIT-IF
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
        if($odooPaymentReferemce!=0) {
            $partnerID=OF_GetPartnerFromPayment($odooPaymentReferemce);
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
    global $mysqli_link, $table_flights_ledger;
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
// Function: OF_GetPaymentReference
// Purpose: Get the reference to a payment from the flight id
//============================================
 
function OF_GetPaymentReference($theFlyID)
{
    global $mysqli_link, $table_flights_ledger;
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
// Function: OF_GetPartnerFromPayment
// Purpose: Get the partner odoo ID from the odoo payment
//============================================
function OF_GetPartnerFromPayment($odooPaymentReference)
{
    global $odooClient;
    global $odoo_host, $odoo_db, $odoo_username, $odoo_password;
    //print("<br>OF_GetPartnerFromPayment:start $odooPaymentReference<br>");
    $partner=0;
    $odooIdString=strval($odooPaymentReference);
    $odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
    $odooPaymentReference=11865;
    $result = $odooClient->SearchRead('account.move.line', array(array(array('id', '=', $odooPaymentReference))),  array('fields'=>array('id', 'name', 'move_type','account_id','debit', 'credit', 'partner_id', 'create_date'))); 
    foreach($result as $f=>$desc) {
        //print("Account #$desc[id]: $desc[name]<br>");
    	//print("Account #$desc[id]: $desc[name], $desc[move_type], $desc[account_id], $desc[debit], $desc[credit], " . 
    	//	$desc['partner_id'][1] . "<br>\n") ;
        //echo var_dump($desc['partner_id']);
    	$partner_id = (isset($desc['partner_id'])) ? $desc['partner_id'] : '' ;
    	if(!is_bool($partner_id)) {
    		$partner=$partner_id[1];
            //print("<br>OF_GetPartnerFromPayment:partner=$partner<br>");
            
    	}
    }
    //print("Partner= $partner<br>");

    /*    $result = $odooClient->SearchRead('account.move.line', array(array('id', '=', '$odooPaymentReferemce')), 
        array('fields'=>array('id', 'name', 'move_type','account_id','debit', 'credit', 'partner_id', 'create_date'))) ;
            */
    return $partner;
}
//============================================
// Function: OF_GetPartnerNameFromReference
// Purpose: Get the passager Name from Reference (DHF-245678)
//============================================
function OF_GetPartnerNameFromReference($theReference)
{
    global $mysqli_link, $table_pax_role, $table_pax, $table_flight;
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
// Function: OF_SetFlightInvoiceRef
// Purpose: Set the column f_invoice_ref in the table flight for a flightID
//============================================
function OF_SetFlightInvoiceRef($theFlightID,$theInvoiceID)
{
    global $mysqli_link, $table_flights,$userId;
	mysqli_query($mysqli_link, "UPDATE $table_flights SET f_invoice_ref=$theInvoiceID WHERE f_id=$theFlightID")
    or 
		journalise($userId, "F", "Impossible de mettre à jour le flights_ledger: " . mysqli_error($mysqli_link)) ;	
    //print("OF_SetFlightInvoiceRef theFlightID=$theFlightID,theInvoiceID=$theInvoiceID");
    return true;
}
    
?>