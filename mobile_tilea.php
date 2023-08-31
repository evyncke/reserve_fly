<?php
/*
   Copyright 2014-2023 Eric Vyncke

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
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}

require_once 'mobile_header5.php' ;

if (!$userIsAdmin and !$userIsBoardMember and !$userIsInstructor) journalise($userId, "F", "This admin page is reserved to administrators") ;

$since = mysqli_real_escape_string($mysqli_link, $_REQUEST['since']) ;
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
// Display today in the local language in human language
$fmt = datefmt_create(
    'fr_BE',
    IntlDateFormatter::FULL,
    IntlDateFormatter::FULL,
    'Europe/Brussels',
    IntlDateFormatter::GREGORIAN,
    'MMMM yyyy' // See https://unicode-org.github.io/icu/userguide/format_parse/datetime/ !
) ;
$monthName = datefmt_format($fmt, $sinceDate) ;

function distance($apt) {
        // Return the distance in km from EBBR airport
        // See https://stackoverflow.com/questions/10053358/measuring-the-distance-between-two-coordinates-in-php
        // https://eservices.minfin.fgov.be/myminfin-web/pages/public/fisconet/document/d259e472-19d1-4e3a-8d62-120e66049b23#_Toc105562091
        global $mysqli_link, $userId, $table_airports ;

        $result = mysqli_query($mysqli_link, "SELECT * FROM $table_airports WHERE a_code = '$apt'")
            or journalise($userId, "F", "Cannot read airport from $table_airports for $apt: " . mysqli_error($mysqli_link)) ;
        $row = mysqli_fetch_array($result) ;
        if (! $row) {
            journalise($userId, "E", "Airport '$apt' is unknown... returning short distance for tax purposes") ;
            return 50 ;
        }
        // convert from degrees to radians
        $earthRadius = 6371000 ; // in meters 
        $latFrom = deg2rad(50.90140);
        $lonFrom = deg2rad(4.48444);
        $latTo = deg2rad($row['a_latitude']);
        $lonTo = deg2rad($row['a_longitude']);

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
        pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);
        return round($angle * $earthRadius / 1000, 0) ; // return in km
}

$sql = "select upper(l_from) as l_from, upper(l_to) as l_to, l_pax_count, l_crew_count
	from $table_logbook l 
	where '$since' <= l_start and l_start < '$monthAfterString'" ;
$result = mysqli_query($mysqli_link, $sql)
	or journalise($userId, "F", "Cannot read $table_logbook to compute taxes: " . mysqli_error($mysqli_link)) ;
$not_taxed_flights = 0 ;
$taxed_flights = 0 ;
$pax_10_eur = 0 ;
$pax_4_eur = 0 ;
$pax_2_eur = 0 ;
while ($row = mysqli_fetch_array($result)) {
	if (stripos($row['l_from'], 'EB') === 0 and $row['l_from'] != $row['l_to']) {
		$taxed_flights ++;
		$distance_km = distance($row['l_to']) ;
		if ($distance_km <= 500)
			$pax_10_eur += $row['l_pax_count'] ;
		else 
			$pax_2_eur +=  $row['l_pax_count'] ; // Assuming EU, UK, or CH withinh our flight reach
	} else {
		$not_taxed_flights ++;
	}
}
?><div class="container-fluid">
<h2 class="text-center">Résumé des taxes TILEA du <?=$since?> au <?=$monthAfterForTitleString?></h2>
<div class="row">
	<ul class="pagination">
		<li class="page-item">
			<a class="page-link" href="<?="$_SERVER[PHP_SELF]?since=$monthBeforeString"?>">
				<i class="bi bi-caret-left-fill"></i>  <?=datefmt_format($fmt, $monthBefore)?>
			</a></li>
		<li class="page-item active"><a class="page-link" href="<?="$_SERVER[PHP_SELF]?since=$since"?>"><?=$monthName?></a></li>
		<li class="page-item"><a class="page-link" href="<?="$_SERVER[PHP_SELF]?since=$monthAfterString"?>">
			<?=datefmt_format($fmt, $monthAfter)?> <i class="bi bi-caret-right-fill"></i></a></li>
	</ul><!-- pagination -->
</div><!-- row -->

<div class="row">
<table class="table table-bordered table-hover table-sm col-md-4">
<tbody>
	<tr><td>Nombre de vols pour lesquels la taxe n'est PAS applicable</td><td><?=$not_taxed_flights?></td></tr>
	<tr><td>Nombre de vols pour lesquels la taxe est applicable</td><td><?=$taxed_flights?></td></tr>
</tbody>
</table>

<table class="table table-bordered table-hover table-sm col-md-6">
<thead>
	<tr><th></th><th>Nombre de passagers</th><th></th><th>Montant de la taxe due</th></tr>
</thead>
<tbody class="table-group-divider">
	<tr><td>Taxe de 10 EUR (<= 500 km depuis EBBR)</td><td><?=$pax_10_eur?></td><td>x 10</td><td><?=(10*$pax_10_eur)?></td></tr>
	<tr><td>Taxe de 4 EUR (> 500 km depuis EBBR et en dehors EEE/CH/UK, impossible à EBSP)</td><td><?=$pax_4_eur?></td><td>x 4</td><td><?=(4*$pax_4_eur)?></td></tr>
	<tr><td>Taxe de 2 EUR (> 500 km depuis EBBR et dans EEE/CH/UK)</td><td><?=$pax_2_eur?></td><td>x 2</td><td><?=(2*$pax_2_eur)?></td></tr>
</tbody>
<tfoot class="table-group-divider">
	<tr><td colspan="3"></td><td class="bg-info"><?=(10*$pax_10_eur + 4*$pax_4_eur + 2*$pax_2_eur)?></td></tr>
</tfoot>
</table>
</div><!-- row -->

</div><!-- container -->
</body>
</html>

