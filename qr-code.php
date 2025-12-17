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
qr-code.php?cht=qr&chs=300x300&&chl=string
*/
if (! isset($_REQUEST['chs'])) {
    // Default phpqrcode values
    $size = 3 ;
    $margin = 4 ;
} else {
    $chs = trim($_REQUEST['chs']) ;
    $parts = explode('x', $chs) ;
    if ($parts[0] != $parts[1]) die('Incorrect value for chs') ;
    // margin increase dimension by 2 x size * margin
    // size = 1 -> 53x53 (no margin)
    // size = 2 -> 106x106 (no margin)
    // size = 3 -> 159x159 (no margin), 171x171 (margin = 2)
    // size = 4 -> 212x212 (no margin), 228x228 (margin = 2)
    // size = 5 -> 265x265 (no margin)
    // size should be max(1, chs / 53)
    // margin should be (chs - size * 53)
    $size = max(1, round($parts[0] / 53)) ;
    $margin = 5 ;
}   
if (! isset($_REQUEST['chl'])) die('Missing paramater chl') ;
$chl = $_REQUEST['chl'] ;

header('Content-type: image/png'); // Unsure whether required though
include('phpqrcode.php');
// Parameters, see https://phpqrcode.sourceforge.net/docs/html/class_q_rcode.html
QRcode::png($chl, false, QR_ECLEVEL_Q, $size, $margin);
?>