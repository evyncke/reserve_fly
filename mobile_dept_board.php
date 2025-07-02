<?php
/*
   Copyright 2023-2025 Eric Vyncke

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

require_once "dbi.php" ;
// font-size Should be based on actual width of display (% ?) rather than the original 80px changed into 40px and finally to 3vw (% of view port width)
$header_postamble = '<style>
.pane {
    width: 1em;
    height: 1em;
    display: inline-block;
    border-radius: .05em;
    border: .01em solid #444;
    position: relative;
    background: #222;
    text-align: center;
    line-height: 1;
    font-size: 3vw; 
    color: #fff;
    font-family: monospace;
    box-shadow: 
                0px .02em 0 #ccc,
                0px .05em 0 #000;
    text-shadow: -.01em -.02em .02em rgba(0,0,0,1);
    z-index: 50;
}
.pane:after {
    position: absolute;
    top: 50%;
    left: 0;
    content: "";
    border-top: 2px solid #000;
    border-bottom: 2px solid rgba(255,255,255,.3);
    width: 100%;
    height: 0px;
    opacity: .8;
    z-index: 10;
    margin-top: -1px
}
.space {
    width: 1em;
    display: inline-block;
    position: relative;
    background: black;
    z-index: 50;
}
</style>
' ;

require_once 'mobile_header5.php' ;

$sql_date = date('Y-m-d') ;
$special_date = '2025-05-17' ;
$rows = [
    [ '0910', 'OOJRB', 'NIHOUL', 'GASPAR'],
    [ '0920', 'OOAPV', 'REGINSTER', 'VINTENS'],
    [ '1030', 'OOALE', 'HENDRICKX', 'POLAT'],
    [ '1040', 'OOFUN', 'MOREAU', 'LALLEMANT'],
    [ '1150', 'DELZB', 'ROSANT', 'LIEVENS'],
    [ '1200', 'OOG85', 'MALAISE', 'VAN HEES'],
    [ '1210', 'OOFUN', 'DELFOSSE', 'BARBIER'],
    [ '1220', 'OOJRB', 'LENOM', 'FERNANDEZ'],
    [ '1230', 'OOALD', 'DOPPAGNE', 'MENDES'],
    [ '1310', 'OOADO', 'HARTMANN', 'FRAIKIN'],
    [ '1320', 'OOALE', 'PENDERS', 'ALBRECHT'],
    [ '1330', 'OOFMX', 'MICHOTTE', 'EVERAETS'],
    [ '1340', 'OOPEG', 'MATHIEU', 'RAOUT'],
    [ '1350', 'OOVMS', 'SAUVAGE', 'LEFIN'],
    [ '1400', 'FJXRL', 'GUILLOU', ''],
    [ '1410', 'OOALD', 'HANNAY', 'MENDES'],
    [ '1420', 'OOJRB', 'PACHOLIK', '& SON'],
    [ '1510', 'OOALE', 'VYNCKE', 'DEHOUSSE'],
    [ '1530', 'DELZB', 'VANHEYSTE', 'ROBA'],
    [ '1540', 'OOFMX', 'WARNOTTE', 'SMAL'],
    [ '1550', 'OOFUN', 'CANADAS', 'ERNST'],
    [ '1200', 'OOSPQ', 'GASPAR', ''],
    [ '0900', 'OOALD', 'SONKES', 'MENDES'],
    [ '1545', 'OOJRB', 'MAGHFOUL', 'MENDES'],
    [ '1700', 'OOALE', 'GADZJIEV', 'MENDES'],
    [ '', '', '', ''],
    [ '', '', '', ''],
    [ '', '', '', ''],
] ;

if (isset($_REQUEST['kiosk']) and (date('i') == '23' or date('i') == '24')) journalise($userId, 'D', "In kiosk mode: $_REQUEST[kiosk]") ; // Log liveness on everyhour and 23 minutes

// Dynamic flip departure board https://codepen.io/tomgiddings/pen/yLyExxo
// Using https://codepen.io/chonz0/pen/NGRbWj for now

function boardPrint($s, $width, $margin, $color = "#fff") {
    $chars = mb_str_split($s) ; // Need to support UTF-8 strings that do not support $s[$i]
    for ($i = 0; ($i < $width) and ($i < sizeof($chars)); $i++)
        print('<div class="pane" style="color: ' . $color . ';">' . mb_strtoupper($chars[$i]) . '</div>') ;
    // TODO insert white space rather blank character for the padding ?
    while ($i < $width) {
        $i++ ;
        print('<div class="pane"> </div>') ;
    }
    for ($i = 0; $i < $margin; $i++) {
        print('<div class="space"> </div>') ;
    }
}
?> 
<script type="text/javascript">
  	if (window.location.search.search('kiosk') >= 0) {
        console.log("Kiosk mode, no need to refresh") ;
    } else {
        console.log("Non kiosk mode, setting a auto-refresh") ;
        setTimeout(function () { 
            console.log("Non kiosk mode, time to refresh") ;
            window.location.href = '<?=$_SERVER['PHP_SELF']?>' ;
        },
        5 * 60000) ; // Refresh time in minutes
    }
</script>

<div class="container-fluid">

<div class="page-header">
<h2>Departures</h2>
</div> <!-- page-header -->

<div style="background: black;">
<br/>
<?php

// Ancilliary function to sort based on departure time
function cmp($a, $b) {
    return strcmp($a[0], $b[0]) ;
}

if ($sql_date == $special_date) {
    $now = date('Gi') ;
    usort($rows, 'cmp') ; // ensure that the array is sorted by time of departure
    foreach($rows as $row) {
        if ($row[0] < $now) continue ; // Only display future departures
        print('<div class="row mx-0 my-4 px-0 flex-nowrap">') ; // Set boostrap margin/padding left-right to 0 to align board characters with the black backgound div
        boardPrint($row[0], 4, 1) ;
        boardPrint($row[1], 5, 1) ;
        boardPrint($row[2], 10, 1, "yellow") ;
        boardPrint($row[3], 10, 1) ;
        print("<br/>") ;
        print('</div><!--row-->') ;
    }
} else {
    $sql = "SELECT *, i.last_name as ilast_name, i.jom_id as iid,
        pi.last_name as plast_name, pi.jom_id as pid,
        pax.p_lname as clast_name
        FROM $table_bookings b
        JOIN $table_person pi ON pi.jom_id = r_pilot
        LEFT JOIN $table_person i ON i.jom_id = r_instructor		
        JOIN $table_planes p ON r_plane = p.id
        LEFT JOIN $table_flights fl ON r_id = f_booking
        LEFT JOIN $table_pax_role pr ON fl.f_id = pr.pr_flight AND pr.pr_role = 'C'
        LEFT JOIN $table_pax pax ON pax.p_id = pr.pr_pax
        WHERE  p.actif = 1 AND p.ressource = 0 AND r_cancel_date IS NULL AND DATE(r_start) = '$sql_date' AND r_start >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ORDER BY r_start, r_plane ASC LIMIT 0,20" ;
	$result = mysqli_query($mysqli_link, $sql)
		or journalise($userId, "F", "Cannot retrieve bookings: " . mysqli_error($mysqli_link)) ;
	while ($row = mysqli_fetch_array($result)) {
        if ($row['r_type'] == BOOKING_MAINTENANCE) continue ;
		if ($row['f_type'] != '') { // INIT or IF flight
            $name = db2web($row['clast_name']) ;
            $description = db2web($row['plast_name']);
        } else if ($row['ilast_name'] and $row['pid'] != $row['iid']) {
            $name = db2web($row['plast_name']) ;
            $description = db2web($row['ilast_name']) ;
        } else {
            $name = db2web($row['plast_name']) ;
            $description = '' ;
            if ($row['r_via1'] != '')
                $description .= "$row[r_via1] " ;
            if ($row['r_via2'] != '')
                $description .= "$row[r_via2] " ;
            if ($row['r_to'] != 'EBSP')
                $description .= "$row[r_to]" ;
            $description = db2web($description) ;
        }
		// Display time only
		$time = substr($row['r_start'], 11, 2) .  substr($row['r_start'], 14, 2);  
        $plane = substr($row['r_plane'], 0, 2) . substr($row['r_plane'], 3, 3) ; // TODO actually remove the '-'
        print('<div class="row mx-0 my-4 px-0 flex-nowrap">') ; // Set boostrap margin/padding left-right to 0 to align board characters with the black backgound div
        boardPrint($time, 4, 1) ;
        boardPrint($plane, 5, 1) ;
        boardPrint($name, 10, 1, "yellow") ;
        boardPrint($description, 10, 1) ;
        print("<br/>") ;
        print('</div><!--row-->') ;
	}
}
?>
<br/>
</div><!-- black background -->
</div> <!-- container-->

</body>
</html>