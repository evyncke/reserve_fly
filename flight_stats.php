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
require_once 'flight_header.php' ;

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
?>
<div class="page-header hidden-xs">
<h3>Statistiques mensuelles des vols effectués du <?=$since?> au <?=$monthAfterForTitleString?></h3>
</div><!-- page header -->

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

<ul class="nav nav-tabs">
	<li class="active in"><a  data-toggle="tab" href="#summary">Résumé</a></li>
	<li><a data-toggle="tab" href="#initFlights">Vols initiations</a></li>
	<li><a data-toggle="tab" href="#ifFlights">Vols découvertes</a></li>
</ul>

<div class="tab-content">
<div id="summary" class="tab-pane fade active in">

<table class="table table-striped table-responsive col-md-6 col-xs-12">
<thead>
<tr><th></th><th>Vols découvertes (IF)</th><th>Vols d'initiations (INIT)</th></tr>
</thead>
<?php
$result = mysqli_query($mysqli_link, "SELECT *, 
		60 * (l_end_hour - l_start_hour) + l_end_minute - l_start_minute as duration,
		DATE(l_start) AS date,
		SUM(fl_amount) AS revenue
	FROM $table_flight AS f
		JOIN $table_logbook AS l ON f_booking = l.l_booking
		JOIN $table_person ON f.f_pilot = jom_id
		LEFT JOIN $table_flights_ledger ON fl_flight = f_id
	WHERE '$since' <= l_start AND l_start < '$monthAfterString'
	GROUP BY f_id
	ORDER BY l_start") 
	or die("Impossible de lister les vols: " . mysqli_error($mysqli_link));
$count_if = 0 ; $count_init = 0 ;
$minutes_if = 0 ; $minutes_init = 0 ;
$revenue_if = 0 ; $revenue_init = 0 ;
$all_inits = [] ;
$all_ifs = [] ;
while ($row = mysqli_fetch_array($result)) {
	$tr = "<tr><td>$row[date]</td>
		<td>$row[f_reference] <a href=\"flight_create.php?flight_id=$row[f_id]\" title=\"Go to flight $row[f_reference]\" target=\"_blank\">&boxbox;</a></td>
		<td>$row[l_plane] <a href=\"planelog.php?plane=$row[l_plane]&since=$since\" title=\"Go to plane log\" target=\"_blank\">&boxbox;</a></td><td>" . 
		db2web("<b>$row[last_name]</b> $row[first_name]") .
		"</td><td>$row[revenue] &euro;</td><td>$row[duration] min</td></tr>\n" ;
	switch ($row['f_type']) {
		case 'D': $count_if++ ;
			$minutes_if += $row['duration'] ;
			$revenue_if += $row['revenue'] ;
			$all_ifs[] = $tr ;
			break ;
		case 'I': $count_init++ ;
			$minutes_init += $row['duration'] ;
			$revenue_init += $row['revenue'] ;
			$all_inits[] = $tr ;
			break ;
		default:
			journalise($userId, "E", "Flight $row[f_id] has an invalid type $row[f_type]") ;
	}
}
?>
<tbody class="table-group-divider">
<tr><td>Nombres</td><td><?=$count_if?></td><td><?=$count_init?></td></tr>
<tr><td>Minutes</td><td><?=$minutes_if?></td><td><?=$minutes_init?></td></tr>
<tr><td>Chiffre d'affaires</td><td><?=$revenue_if?> &euro;</td><td><?=$revenue_init?> &euro;</td></tr>
</tbody>
</table>
</div><!-- id=summary-->

<div id="initFlights" class="tab-pane fade">
<h2>Vols d'initiations (INIT)</h2>
<table class="table table-striped table-responsive col-md-6 col-xs-12">
	<thead>
		<tr><th>Date</th><th>Référence</th><th>Avion</th><th>Pilote</th><th>Prix du vol</th><th>Durée</th></tr>
	</thead>
	<tbody class="table-group-divier">
<?php
	foreach($all_inits as $tr)
		print($tr) ;
?>
	</tbody>
</table>
</div><!-- id=initFlights-->

<div id="ifFlights" class="tab-pane fade">
<h2>Vols découvertes (IF)</h2>
<table class="table table-striped table-responsive col-md-6 col-xs-12">
	<thead>
		<tr><th>Date</th><th>Référence</th><th>Avion</th><th>Pilote</th><th>Prix du vol</th><th>Durée</th></tr>
	</thead>
	<tbody class="table-group-divier">
<?php
	foreach($all_ifs as $tr)
		print($tr) ;
?>
	</tbody>
</table>
</div><!-- id=ifFlights-->
</div><!-- tab-content--> 
<?php
require_once 'flight_trailer.php' ;
?>