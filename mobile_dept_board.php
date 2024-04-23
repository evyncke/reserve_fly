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

ob_start("ob_gzhandler");

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
// $sql_date = '2023-09-09' ; // Just for testing

// Dynamic flip departure board https://codepen.io/tomgiddings/pen/yLyExxo
// Using https://codepen.io/chonz0/pen/NGRbWj for now

function boardPrint($s, $width, $margin, $color = "#fff") {
    $chars = mb_str_split($s) ; // Need to support UTF-8 strings that do not support $s[$i]
    for ($i = 0; $i < $width; $i++)
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
        WHERE  p.actif = 1 AND p.ressource = 0 AND r_cancel_date IS NULL AND DATE(r_start) = '$sql_date'
        ORDER BY r_start, r_plane ASC LIMIT 0,20" ;
	$result = mysqli_query($mysqli_link, $sql)
		or die("Cannot retrieve bookings: " . mysqli_error($mysqli_link)) ;
    // TODO only retrieve flights in the future...
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
        $plane = substr($row['r_plane'], 0, 2) . substr($row['r_plane'], 3, 3) ;
        print('<div class="row mx-0 px-0 flex-nowrap">') ; // Set boostrap margin/padding left-right to 0 to align board characters with the black backgound div
        boardPrint($time, 4, 1) ;
        boardPrint($plane, 5, 1) ;
        boardPrint($name, 10, 1, "yellow") ;
        boardPrint($description, 10, 1) ;
        print("<br/>") ;
        print('</div><!--row-->') ;
	}
?>
<br/>
</div><!-- black background -->
</div> <!-- container-->

</body>
</html>
