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

$pcnq = new Payconiq() ;

journalise(0, "D", "Payconiq callback starting") ;

if ($_SERVER['REQUEST_METHOD'] != 'POST')
    journalise(0, "F", "Wrong method $_SERVER[REQUEST_METHOD] in Payconiq callback") ;
if ($_SERVER['CONTENT_TYPE'] != 'application/json')
    journalise(0, "F", "Wrong content-type $_SERVER[HTTP_CONTENT_TYPE]/$_SERVER[CONTENT_TYPE] in Payconiq callback") ;
if ($_SERVER['HTTP_USER_AGENT'] != 'Payconiq Payments/v3')
    journalise(0, "F", "Wrong user-agent $_SERVER[HTTP_USER_AGENT] in Payconiq callback") ;

$body = file_get_contents('php://input') ;

$pcnq->processCallback($_SERVER['HTTP_SIGNATURE'], $body) ;

journalise(0, "D", "Payconiq callback terminated") ;
?>