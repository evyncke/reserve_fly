<?php
/*
/*
   Copyright 2024 Eric Vyncke

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

This script emulates the obsolete Google chart code
qr-code.php?cht=qr&chs=300x300&&chl
*/
if (! isset($_REQUEST['chs'])) die('Missing paramater chs') ;
$chs = trim($_REQUEST['chs']) ;
if (! isset($_REQUEST['chl'])) die('Missing paramater chl') ;
$chl = trim($_REQUEST['chl']) ;

header('Content-type: image/png'); // Unsure whether required though
include('phpqrcode.php');
// Parameters, see https://phpqrcode.sourceforge.net/docs/html/class_q_rcode.html
QRcode::png($chl, false, QR_ECLEVEL_Q);
?>