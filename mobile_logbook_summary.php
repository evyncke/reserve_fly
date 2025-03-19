<?php
/*
   Copyright 2014-2025 Eric Vyncke - Patrick Reginster

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
require_once "mobile_tools.php" ;

if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}

//$header_postamble = '<script src="shareCodes.js"></script>' ;
//$body_attributes=' onload="initPlaneLog();init();" ' ;
require_once 'mobile_header5.php' ;

if (isset($_REQUEST['since']) and $_REQUEST['since'] != '')
	$since = mysqli_real_escape_string($mysqli_link, $_REQUEST['since']) ;
else
	$since = date('Y-m-01') ;

$sinceDate = new DateTime($since) ;
$yearAfter = new DateTime($since) ;
$fmt = datefmt_create(
    'fr_BE',
    IntlDateFormatter::FULL,
    IntlDateFormatter::FULL,
    'Europe/Brussels',
    IntlDateFormatter::GREGORIAN,
    'yyyy' // See https://unicode-org.github.io/icu/userguide/format_parse/datetime/ !
) ;
$yearName = datefmt_format($fmt, $sinceDate) ;
$thisYearName = datefmt_format($fmt, new DateTime()) ;

$today = new DateTime() ;
//$yearAfterForTitle = new DateTime($since) ;
//$yearBefore = new DateTime($since) ;
$since = $yearName."-01-01";
$yearAfterForTitle = new DateTime($yearName."-01-01") ;
$yearBefore = new DateTime($yearName."-01-01") ;
$yearInterval = new DateInterval('P1Y') ; // One year
$yearBefore = $yearBefore->sub($yearInterval) ;
$yearBeforeString = $yearBefore->format('Y-m-d') ;
if($yearBeforeString<"2023") {
	//Before 2023, the logbook table is not fill
	$yearBeforeString="";
}
$yearAfter = $yearAfter->add($yearInterval) ; // Then request is from 01-01-2024 0h00 to 01-01-2025 0h00
$yearAfterString = $yearAfter->format('Y-m-d') ;
$yearAfterForTitle = $yearAfterForTitle->add($yearInterval) ;
$yearAfterForTitle = $yearAfterForTitle->sub(new DateInterval('P1D')) ;
$yearAfterForTitleString = $yearAfterForTitle->format('Y-m-d') ; // Then Title is 31-12-2023 and not 01-01-2024
// Display today in the local language in human language

$comparaisonYearName=$thisYearName;
if($thisYearName==$yearName) {
	$comparaisonYearName=datefmt_format($fmt, $yearBefore);
}
?>
<div class="container-fluid">
<h2>Résumé du logbook du <?=$since?> au <?=$yearAfterForTitleString?></h2>
<?php
$SumTotalByMonthString="";
$planes=array("OO-ALD"=>0, "OO-JRB"=> 0, "OO-FUN"=> 0, "OO-APV" => 0, "OO-FMX" =>0, "OO-ALE" => 0, "PH-AML" => 0, "OO-SHC" => 0, "OO-SPQ" => 0, "DUMMY" => 0);
$planesFlight=array("OO-ALD"=>0, "OO-JRB"=> 0, "OO-FUN"=> 0, "OO-APV" => 0, "OO-FMX" =>0, "OO-ALE" => 0, "PH-AML" => 0, "OO-SHC" => 0, "OO-SPQ" => 0, "DUMMY" => 0);
$planesComparaison=array("OO-ALD"=>0, "OO-JRB"=> 0, "OO-FUN"=> 0, "OO-APV" => 0, "OO-FMX" =>0, "OO-ALE" => 0, "PH-AML" => 0, "OO-SHC" => 0, "OO-SPQ" => 0, "DUMMY" => 0);
//print("yearName=$yearName<br>");
$monthsFilter = array("01"=>$yearName."-01-%",
		"02"=>$yearName."-02-%",
		"03"=>$yearName."-03-%",
		"04"=>$yearName."-04-%",
		"05"=>$yearName."-05-%",
		"06"=>$yearName."-06-%",
		"07"=>$yearName."-07-%",
		"08"=>$yearName."-08-%", 
		"09"=>$yearName."-09-%",
		"10"=>$yearName."-10-%",
		"11"=>$yearName."-11-%",
		"12"=>$yearName."-12-%");
$monthsForecast = array("01"=>1.3,
		"02"=>3.3,
		"03"=>10.3,
		"04"=>19.8,
		"05"=>31.3,
		"06"=>46.7,
		"07"=>60.2,
		"08"=>75.7, 
		"09"=>88.2,
		"10"=>96.2,
		"11"=>98.7,
		"12"=>100.0);

$yearFilter=$yearName."-%";
$totalTimeInMinute=0;
$totalTimeInMinuteComparaison=0; // Year befor
$SumTotalByMonth=0;
foreach ($planes as $plane_id => &$planeTimeRef) {
	$timeInMinute=getCompteurValueInMinute($plane_id, $yearFilter);
	$timeString=convertMinuteToHour($timeInMinute);
	$totalTimeInMinute+=$timeInMinute;
	$planeTimeRef=$timeInMinute;

	$timeInMinute=getCompteurValueInMinute($plane_id, $comparaisonYearName."-%");
	$timeString=convertMinuteToHour($timeInMinute);
	$totalTimeInMinuteComparaison+=$timeInMinute;
	$planesComparaison[$plane_id]=$timeInMinute;
	//print("$plane_id: $comparaisonYearName  $planesComparaison[$plane_id]<br>");
}
?>
<div class="row">
	<ul class="pagination">
<?php
if($yearBeforeString!=""){
	    $yearString=datefmt_format($fmt, $yearBefore);
		print("<li class=\page-item\">
			<a class=\"page-link\" href=\"$_SERVER[PHP_SELF]?since=$yearBeforeString\">
				<i class=\"bi bi-caret-left-fill\"></i>$yearString
			</a></li>");
}
?>
		<li class="page-item active"><a class="page-link" href="<?="$_SERVER[PHP_SELF]?since=$since"?>"><?=$yearName?></a></li>
		<li class="page-item"><a class="page-link" href="<?="$_SERVER[PHP_SELF]?since=$yearAfterString"?>">
			<?=datefmt_format($fmt, $yearAfter)?> <i class="bi bi-caret-right-fill"></i></a></li>
	</ul><!-- pagination -->
</div><!-- row -->
<table class="table table-bordered table-striped table-sm">
<thead>
<tr>
<th class="text-center border-bottom-0">Mois</th>
<?php
foreach ($planes as $plane_id=>$planeTime) {
	if($planeTime==0) {
		continue;
	}
	print("<th class=\"text-center border-bottom-0\">$plane_id</th>");
}
?>
<th class="text-center border-bottom-0">Total<br><?=$yearName?></th>
<th class="text-center border-bottom-0">Total<br><?=$comparaisonYearName?></th>
<th class="text-center border-bottom-0">Total cumulé<br><?=$yearName?></th>
<th class="text-center border-bottom-0">Prévision année<br><?=$yearName?></th>
</tr>
</thead>
<tbody>
<?php
// loop on month
foreach ($monthsFilter as $month=>$monthFilter) {
	print("<tr>");
	print("<td class=\"text-center border-bottom-0\">$month</td>");
	$totalTimeMonthInMinute=0;
	// loop on plane
	foreach ($planes as $plane_id => $planeTime) {
		if($planeTime==0) {
			continue;
		}	
		$timeInMinute=getCompteurValueInMinute($plane_id, $monthFilter);
		if($timeInMinute>0) {
			$timeString=convertMinuteToHour($timeInMinute);
			$totalTimeMonthInMinute+=$timeInMinute;
			print("<td class=\"text-center border-bottom-0\">$timeString</td>");
		}
		else {
			print("<td class=\"text-center border-bottom-0\"></td>");
		}
	}
	// Total column
	if($totalTimeMonthInMinute>0) {
		$totalTimeMonthInString=convertMinuteToHour($totalTimeMonthInMinute);
		$TotalPercent=0;
		if($totalTimeInMinute>0) {
			$TotalPercent=number_format(100*$totalTimeMonthInMinute/$totalTimeInMinute,1);
		}
		print("<td class=\"text-center border-bottom-0\"><strong>$totalTimeMonthInString</strong> ($TotalPercent%)</td>");
	}
	else {
		print("<td class=\"text-center border-bottom-0\"></td>");
	}

	//Total column for comparaison
	// loop on plane
	$totalTimeMonthInMinuteComparaison=0;
	foreach ($planesComparaison as $plane_id => $planeTime) {
		if($planeTime==0) {
			continue;
		}	
		$timeInMinute=getCompteurValueInMinute($plane_id,  $comparaisonYearName.substr($monthFilter,4));
		$totalTimeMonthInMinuteComparaison+=$timeInMinute;
	}
	if($totalTimeMonthInMinuteComparaison>0) {
		$totalByMonthComparaisonString=convertMinuteToHour($totalTimeMonthInMinuteComparaison);
		print("<td class=\"text-center border-bottom-0\">$totalByMonthComparaisonString</td>");
	}
	else {
		print("<td class=\"text-center border-bottom-0\"></td>");
	}

	// Total cummule
	$SumTotalByMonth+=$totalTimeMonthInMinute;
	if($totalTimeMonthInMinute>0) {
		$SumTotalByMonthString=convertMinuteToHour($SumTotalByMonth);
		print("<td class=\"text-center border-bottom-0\">$SumTotalByMonthString</td>");
	}
	else {
		print("<td class=\"text-center border-bottom-0\"></td>");
	}




	// Forecast for the year
	$yearForecastString="";
	if($totalTimeMonthInMinute>0) {
		$yearDayOne=new DateTime($yearName."-01-01");
		$monthInterval = new DateInterval("P".$month."M") ; // some mounth
		$monthAfter = $yearDayOne->add($monthInterval) ;
		if($monthAfter<$today) {
			$yearForecast=$SumTotalByMonth/(0.01*$monthsForecast[$month]);
			$yearForecastString=convertMinuteToHour(intval($yearForecast));
			//$yearForecastString=$monthsForecast[$month];
		}
	}
	print("<td class=\"text-center border-bottom-0\">$yearForecastString</td>");

	print("</tr>");
}
?>
</tbody>
<tfoot  class="table-group-divider">

<?php
	print("<tr class=\"table-info\">");
	print("<td><strong>Total $yearName</strong></td>");
	// loop on plane
	foreach ($planes as $plane_id => $planeTime) {
		if($planeTime==0) {
			continue;
		}	
		$timeInMinute=$planeTime;
		$timeString=convertMinuteToHour($timeInMinute);
		print("<td class=\"text-center border-bottom-0\"><strong>$timeString</strong></td>");
	}
	$totalTimeInString=convertMinuteToHour($totalTimeInMinute);
	print("<td class=\"text-center border-bottom-0\"><strong>$totalTimeInString</strong></td>");
	print("<td class=\"text-center border-bottom-0\"><strong></strong></td>");
	print("<td class=\"text-center border-bottom-0\"><strong>$SumTotalByMonthString</strong></td>");
	print("<td class=\"text-center border-bottom-0\"><strong></strong></td>");
	print("</tr>");

	//Comparaison year
	$planesComparaison[$plane_id]=$timeInMinute;
	print("<tr class=\"table-info\">");
	print("<td><strong>Total $comparaisonYearName</strong></td>");
	// loop on plane
	foreach ($planes as $plane_id => $planeTime) {
		if($planeTime==0) {
			continue;
		}	
		$timeInMinute=$planesComparaison[$plane_id];
		$timeString=convertMinuteToHour($timeInMinute);
		print("<td class=\"text-center border-bottom-0\"><strong>$timeString</strong></td>");
	}
	$totalTimeInString=convertMinuteToHour($totalTimeInMinuteComparaison);
	print("<td class=\"text-center border-bottom-0\"><strong></strong></td>");
	print("<td class=\"text-center border-bottom-0\"><strong>$totalTimeInString</strong></td>");
	print("<td class=\"text-center border-bottom-0\"><strong></strong></td>");
	print("<td class=\"text-center border-bottom-0\"><strong></strong></td>");
	print("</tr>");

?>
</tfoot>
</table>
</div><!-- container -->


<!-- Vols IF ================================================================================= -->
<?php
$totalTimeInMinute=0;
$SumTotalByMonth=0;
foreach ($planes as $plane_id => $planeTime) {
	$timeInMinute=getCompteurIFValueInMinute($plane_id, $yearFilter);
	$timeString=convertMinuteToHour($timeInMinute);
	$totalTimeInMinute+=$timeInMinute;
	$planesFlight[$plane_id]=$timeInMinute;
	//$planes['$plane_id']=$timeInMinute;
	//print("$plane_id: $planes[$plane_id]<br>");
}
 ?>
<br>
<h2>Vols IF effectués en <?=$yearName?></h2>
<table class="table table-bordered table-striped table-sm">
<thead>
<tr>
<th class="text-center border-bottom-0">Mois</th>
<?php
foreach ($planes as $plane_id=>$planeTime) {
	if($planeTime==0) {
		continue;
	}
	print("<th class=\"text-center border-bottom-0\">$plane_id</th>");
}
?>
<th class="text-center border-bottom-0">Total<br><?=$yearName?></th>
<th class="text-center border-bottom-0">Total cumulé<br><?=$yearName?></th>
</tr>
</thead>
<tbody>
<?php
// loop on month
foreach ($monthsFilter as $month=>$monthFilter) {
	print("<tr>");
	print("<td class=\"text-center border-bottom-0\">$month</td>");
	$totalTimeMonthInMinute=0;
	// loop on plane
	foreach ($planes as $plane_id => $planeTime) {
		if($planeTime==0) {
			continue;
		}	
		$timeInMinute=getCompteurIFValueInMinute($plane_id, $monthFilter);
		if($timeInMinute>0) {
			$timeString=convertMinuteToHour($timeInMinute);
			$totalTimeMonthInMinute+=$timeInMinute;
			print("<td class=\"text-center border-bottom-0\">$timeString</td>");
		}
		else {
			print("<td class=\"text-center border-bottom-0\"></td>");
		}
	}
	// Total column
	if($totalTimeMonthInMinute>0) {
		$totalTimeMonthInString=convertMinuteToHour($totalTimeMonthInMinute);
		print("<td class=\"text-center border-bottom-0\"><strong>$totalTimeMonthInString</strong></td>");
	}
	else {
		print("<td class=\"text-center border-bottom-0\"><strong></strong></td>");
	}

	// Total cummule
	if($totalTimeMonthInMinute>0) {
		$SumTotalByMonth+=$totalTimeMonthInMinute;
		$SumTotalByMonthString=convertMinuteToHour($SumTotalByMonth);	
		print("<td class=\"text-center border-bottom-0\"><strong>$SumTotalByMonthString</strong></td>");
	}
	else {
		print("<td class=\"text-center border-bottom-0\"><strong></strong></td>");
	}
	print("</tr>");
}
?>
</tbody>
<tfoot  class="table-group-divider">

<?php
	print("<tr class=\"table-info\">");
	print("<td><strong>Total</strong></td>");
	// loop on plane
	foreach ($planes as $plane_id => $planeTime) {
		if($planeTime==0) {
			continue;
		}	
		//$timeInMinute=$planes['$plane_id'];
		$timeInMinute=$planesFlight[$plane_id];
		$timeString=convertMinuteToHour($timeInMinute);
		//$totalTimeInMinute+=$timeInMinute;
		print("<td class=\"text-center border-bottom-0\"><strong>$timeString</strong></td>");
	}
	$totalTimeInString=convertMinuteToHour($totalTimeInMinute);
	print("<td class=\"text-center border-bottom-0\"><strong>$totalTimeInString</strong></td>");
	print("<td class=\"text-center border-bottom-0\"><strong>$SumTotalByMonthString</strong></td>");
	print("</tr>");
?>
</tfoot>
</table>


<!-- Vols INIT ================================================================================= -->
<?php
$totalTimeInMinute=0;
$SumTotalByMonth=0;
foreach ($planes as $plane_id => $planeTime) {
	$timeInMinute=getCompteurINITValueInMinute($plane_id, $yearFilter);
	$timeString=convertMinuteToHour($timeInMinute);
	$totalTimeInMinute+=$timeInMinute;
	$planesFlight[$plane_id]=$timeInMinute;
	//$planes['$plane_id']=$timeInMinute;
	//print("$plane_id: $planes[$plane_id]<br>");
}
 ?>
<br>
<h2>Vols INIT effectués en <?=$yearName?></h2>
<table class="table table-bordered table-striped table-sm">
<thead>
<tr>
<th class="text-center border-bottom-0">Mois</th>
<?php
foreach ($planes as $plane_id=>$planeTime) {
	if($planeTime==0) {
		continue;
	}
	print("<th class=\"text-center border-bottom-0\">$plane_id</th>");
}
?>
<th class="text-center border-bottom-0">Total<br><?=$yearName?></th>
<th class="text-center border-bottom-0">Total cumulé<br><?=$yearName?></th>
</tr>
</thead>
<tbody>
<?php
// loop on month
foreach ($monthsFilter as $month=>$monthFilter) {
	print("<tr>");
	print("<td class=\"text-center border-bottom-0\">$month</td>");
	$totalTimeMonthInMinute=0;
	// loop on plane
	foreach ($planes as $plane_id => $planeTime) {
		if($planeTime==0) {
			continue;
		}	
		$timeInMinute=getCompteurINITValueInMinute($plane_id, $monthFilter);
		if($timeInMinute>0) {
			$timeString=convertMinuteToHour($timeInMinute);
			$totalTimeMonthInMinute+=$timeInMinute;
			print("<td class=\"text-center border-bottom-0\">$timeString</td>");
		}
		else {
			print("<td class=\"text-center border-bottom-0\"></td>");
		}
	}
	// Total column
	if($totalTimeMonthInMinute>0) {
		$totalTimeMonthInString=convertMinuteToHour($totalTimeMonthInMinute);
		print("<td class=\"text-center border-bottom-0\"><strong>$totalTimeMonthInString</strong></td>");
	}
	else {
		print("<td class=\"text-center border-bottom-0\"><strong></strong></td>");
	}

	// Total cummule
	if($totalTimeMonthInMinute>0) {
		$SumTotalByMonth+=$totalTimeMonthInMinute;
		$SumTotalByMonthString=convertMinuteToHour($SumTotalByMonth);	
		print("<td class=\"text-center border-bottom-0\"><strong>$SumTotalByMonthString</strong></td>");
	}
	else {
		print("<td class=\"text-center border-bottom-0\"><strong></strong></td>");
	}

	print("</tr>");
}
?>
</tbody>
<tfoot  class="table-group-divider">

<?php
	print("<tr class=\"table-info\">");
	print("<td><strong>Total</strong></td>");
	// loop on plane
	foreach ($planes as $plane_id => $planeTime) {
		if($planeTime==0) {
			continue;
		}	
		//$timeInMinute=$planes['$plane_id'];
		$timeInMinute=$planesFlight[$plane_id];
		$timeString=convertMinuteToHour($timeInMinute);
		//$totalTimeInMinute+=$timeInMinute;
		print("<td class=\"text-center border-bottom-0\"><strong>$timeString</strong></td>");
	}
	$totalTimeInString=convertMinuteToHour($totalTimeInMinute);
	print("<td class=\"text-center border-bottom-0\"><strong>$totalTimeInString</strong></td>");
	print("<td class=\"text-center border-bottom-0\"><strong>$SumTotalByMonthString</strong></td>");
	print("</tr>");
?>
</tfoot>
</table>



<!-- Vols DHF ================================================================================= -->
<?php
$totalTimeInMinute=0;
$SumTotalByMonth=0;
foreach ($planes as $plane_id => $planeTime) {
	$timeInMinute=getCompteurDHFValueInMinute($plane_id, $yearFilter);
	$timeString=convertMinuteToHour($timeInMinute);
	$totalTimeInMinute+=$timeInMinute;
	$planesFlight[$plane_id]=$timeInMinute;
	//$planes['$plane_id']=$timeInMinute;
	//print("$plane_id: $planes[$plane_id]<br>");
}
 ?>
<br>
<h2>Vols DHF effectués en <?=$yearName?></h2>
<table class="table table-bordered table-striped table-sm">
<thead>
<tr>
<th class="text-center border-bottom-0">Mois</th>
<?php
foreach ($planes as $plane_id=>$planeTime) {
	if($planeTime==0) {
		continue;
	}
	print("<th class=\"text-center border-bottom-0\">$plane_id</th>");
}
?>
<th class="text-center border-bottom-0">Total<br><?=$yearName?></th>
<th class="text-center border-bottom-0">Total cumulé<br><?=$yearName?></th>
</tr>
</thead>
<tbody>
<?php
// loop on month
$SumTotalByMonthString="";
foreach ($monthsFilter as $month=>$monthFilter) {
	print("<tr>");
	print("<td class=\"text-center border-bottom-0\">$month</td>");
	$totalTimeMonthInMinute=0;
	// loop on plane
	foreach ($planes as $plane_id => $planeTime) {
		if($planeTime==0) {
			continue;
		}	
		$timeInMinute=getCompteurDHFValueInMinute($plane_id, $monthFilter);
		if($timeInMinute>0) {
			$timeString=convertMinuteToHour($timeInMinute);
			$totalTimeMonthInMinute+=$timeInMinute;
			print("<td class=\"text-center border-bottom-0\">$timeString</td>");
		}
		else {
			print("<td class=\"text-center border-bottom-0\"></td>");
		}
	}
	// Total column
	if($totalTimeMonthInMinute>0) {
		$totalTimeMonthInString=convertMinuteToHour($totalTimeMonthInMinute);
		print("<td class=\"text-center border-bottom-0\"><strong>$totalTimeMonthInString</strong></td>");
	}
	else {
		print("<td class=\"text-center border-bottom-0\"><strong></strong></td>");
	}

	// Total cummule
	if($totalTimeMonthInMinute>0) {
		$SumTotalByMonth+=$totalTimeMonthInMinute;
		$SumTotalByMonthString=convertMinuteToHour($SumTotalByMonth);	
		print("<td class=\"text-center border-bottom-0\"><strong>$SumTotalByMonthString</strong></td>");
	}
	else {
		print("<td class=\"text-center border-bottom-0\"><strong></strong></td>");
	}

	print("</tr>");
}
?>
</tbody>
<tfoot  class="table-group-divider">

<?php
	print("<tr class=\"table-info\">");
	print("<td><strong>Total</strong></td>");
	// loop on plane
	foreach ($planes as $plane_id => $planeTime) {
		if($planeTime==0) {
			continue;
		}	
		//$timeInMinute=$planes['$plane_id'];
		$timeInMinute=$planesFlight[$plane_id];
		$timeString=convertMinuteToHour($timeInMinute);
		//$totalTimeInMinute+=$timeInMinute;
		print("<td class=\"text-center border-bottom-0\"><strong>$timeString</strong></td>");
	}
	$totalTimeInString=convertMinuteToHour($totalTimeInMinute);
	print("<td class=\"text-center border-bottom-0\"><strong>$totalTimeInString</strong></td>");
	print("<td class=\"text-center border-bottom-0\"><strong>$SumTotalByMonthString</strong></td>");
	print("</tr>");
?>
</tfoot>
</table>


</body>
</html>

