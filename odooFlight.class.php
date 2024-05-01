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
function OF_FillFlightOdooMaps(&$theOdooPaymentMap,&$thePaymentFlightMap,&$theLedgerIdMap,&$theReferenceIDMap) {
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
?>