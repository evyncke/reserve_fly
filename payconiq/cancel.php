<?php
/*
   Copyright 2023 Eric Vyncke

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

require '../dbi.php' ;
require 'payconiq.php' ;

$error_message = '' ;
$status = array() ;

// Parameter sanitization
$paymentId = trim($_REQUEST['paymentId']) ;
if ($paymentId == '') $error_message = "Missing parameter" ;

if ($error_message != '') {
    $status['errorMessage'] = $error_message ;
} else {
    $pcnq = new Payconiq() ;
    $payment = $pcnq->loadPayment($paymentId) ;
    if ($payment) {
        if ($pcnq->cancel()) {
            $status['message'] = "Payment cancelled" ;
            $status['status'] = 'OK' ;
        } else
            $status['errorMessage'] = 'Cannot cancel' ;
    } else
        $status['errorMessage'] = "Cannot find the payment" ;
}

if (isset($status['errorMessage']))
    $status['status'] = "NOK" ;

// Let's send the data back
@header('Content-type: application/json');
$json_encoded = json_encode($status) ;
if ($json_encoded === FALSE) {
        journalise($userId, 'E', "Cannot JSON_ENCODE(), error code: " . json_last_error_msg()) ;
        print("{'errorMessage' : 'cannot json_encode(): " . json_last_error_msg() . "'}") ;
} else
        print($json_encoded) ;
?>