<?php
/*
   Copyright 2022-2024 Eric Vyncke, Patrick Reginster

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
require_once "folio.php" ;

MustBeLoggedIn() ;

$originalUserId = $userId ;

if (isset($_REQUEST['user']) and ($userIsAdmin or $userIsBoardMember or $userIsInstructor)) {
	if ($userId != 62) journalise($userId, "I", "Start of myfolio, setting user to $_REQUEST[user]") ;
	$userId = $_REQUEST['user'] ;
	if (! is_numeric($userId)) die("Invalid user ID") ;
}

// Check whether the user has specified a start date...
if (isset($_REQUEST['start']))
	$first_date = $_REQUEST['start'] ; // No need to escape to prevent SQL injection as it is only used in DateTime methods
else
	$first_date = date('Y-m-01') ;

$folio_start = new DateTime($first_date, new DateTimeZone('UTC')) ;
$folio_end = new DateTime($first_date, new DateTimeZone('UTC')) ;
$folio_end_title = new DateTime($first_date, new DateTimeZone('UTC')) ;
$previous_month = new DateTime($first_date, new DateTimeZone('UTC')) ;
$next_month = new DateTime($first_date, new DateTimeZone('UTC')) ;

if (isset($_REQUEST['previous'])) {
	$folio_start = new DateTime($first_date, new DateTimeZone('UTC')) ;
	$folio_start = $folio_start->sub(new DateInterval('P1M')) ;
	$folio_end = new DateTime($first_date, new DateTimeZone('UTC')) ;
	$folio_end = $folio_end->sub(new DateInterval('P1M')) ;
	$folio_end_title = new DateTime($first_date, new DateTimeZone('UTC')) ;
	$folio_end_title = $folio_end_title->sub(new DateInterval('P1M')) ;
	$next_month = new DateTime($first_date, new DateTimeZone('UTC')) ;
	$previous_month = null ;
	// Pager active ?
	$previous_active = " active" ;
	$current_active = '' ;
} else {
	$previous_month = $previous_month->sub(new DateInterval('P1M')) ;
	$next_month = null ;
	// Pager active ?
	$current_active = " active" ;
	$previous_active = '' ;
}
$this_month_pager = new DateTime($first_date, new DateTimeZone('UTC')) ;
$previous_month_pager = new DateTime($first_date, new DateTimeZone('UTC')) ;
$previous_month_pager = $previous_month_pager->sub(new DateInterval('P1M')) ;
$folio_end->add(new DateInterval('P1M'));
$folio_end_title->add(new DateInterval('P1M'));
$folio_end_title->sub(new DateInterval('P1D'));

$result = mysqli_query($mysqli_link, "SELECT * 
	FROM $table_person LEFT JOIN $table_blocked on jom_id=b_jom_id
	WHERE jom_id = $userId")
	or journalise($originalUserId, 'F', "Impossible de lire le pilote $userId: " . mysqli_error($mysqli_link)) ;
$pilot = mysqli_fetch_array($result) or journalise($originalUserId, 'F', "Pilote $userId inconnu") ;
$userName = db2web("$pilot[first_name] $pilot[last_name]") ;
$userLastName = substr(db2web($pilot['last_name']), 0, 5) ;
$blocked_reason = db2web($pilot['b_reason']) ;
$blocked_when = $pilot['b_when'] ;
mysqli_free_result($result) ;

function numberFormat($n, $decimals = 2, $decimal_separator = ',', $thousand_separator = ' ', $empty_if_null = TRUE) {
	if ($n == 0) 
		return ($empty_if_null) ? '' : '0,0&nbsp;&euro;' ;
	return number_format($n, $decimals, $decimal_separator, $thousand_separator) . '&nbsp;&euro;';
}

// Is a CSV file request ?
if (isset($_REQUEST['csv']) and $_REQUEST['csv'] != '') {
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment;filename="folio-' . $folio_start->format('Y-m-d') . '.csv"');
	header('Cache-Control: max-age=0');

	print("Date;From;Start;To;End;Model;Plane;Hours;Minutes;PIC;Pax;\"Cost Sharing\";\"Plane Cost\";\"FI Cost\";\"Tax Cost\"\n") ;

	$folio = new Folio($userId, $folio_start->format('Y-m-d'), $folio_end->format('Y-m-d')) 
		or journalise($originalUserId, "F", "Cannot get access to the folio");
	foreach ($folio as $line)	{
		print("$line->date;$line->from;$line->time_start;$line->to;$line->time_end;$line->model;$line->plane;$line->duration_hh;$line->duration_mm;") ;
		if ($line->instructor_code != $userId  and  $line->is_pic) { // PIC 
			print("SELF;") ; //Pilot Point of View. A PIC-Recheck is SELF
		} else  // Dual command
			if ($userId == $line->instructor_code)
				print("\"$line->pilot_name\";") ; //Point of view of the Instructor. A PIC Recheck is a DC
			else
				print("\"$line->instructor_name\";") ;// DC 
		print("$line->pax_count;") ;
		if ($line->share_type)
			print("\"$line->share_type ($line->share_member_fname $line->share_member_name)\";") ;
		else
			print(";") ;
		print(number_format($line->cost_plane, 2, ',', '') . ";" . 
			number_format($line->cost_fi, 2, ',', '') . ";" . 
			number_format($line->cost_taxes, 2, ',', '') . "\n") ;
	}
	exit ;
} // CSV output


// Is a PILOT Log file request ?
if (isset($_REQUEST['pilotlog']) and $_REQUEST['pilotlog'] != '') {
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment;filename="pilotlog-' . $folio_start->format('Y-m-d') . '.csv"');
	header('Cache-Control: max-age=0');
	print("PILOTLOG_DATE;AF_DEP;TIME_DEP;AF_ARR;TIME_ARR;AC_MODEL;AC_REG;TIME_TOTAL;PILOT1_NAME;PILOT2_NAME;TIME_PIC;TIME_INSTRUCTOR\n") ;

	//print("Date;From;Start;To;End;Model;Plane;Hours;Minutes;PIC;Pax;\"Cost Sharing\";\"Plane Cost\";\"FI Cost\";\"Tax Cost\"\n") ;

	$folio = new Folio($userId, $folio_start->format('Y-m-d'), $folio_end->format('Y-m-d')) 
		or journalise($originalUserId, "F", "Cannot get access to the folio");
	foreach ($folio as $line)	{
		//01-02-25
		$date=$line->date;
		$date="20".substr($date,6,2)."-".substr($date,3,2)."-".substr($date,0,2);
		$duration=$line->duration_hh.":";
		if(intval($line->duration_mm)<10)
			$duration.="0".$line->duration_mm;
		else
			$duration.=$line->duration_mm;

		print("$date;$line->from;$line->time_start;$line->to;$line->time_end;$line->model;$line->plane;$duration;") ;
		$pilotName="SELF";
		$pilotDC="";
		if($userId != $line->pilot_code) {
			$pilotName=$line->pilot_name." ".$line->pilot_fname;
			$pilotDC="SELF";
		}
		print("\"$pilotName\";\"$pilotDC\";");
		$durationDC="";
		if($pilotName=="SELF" && $line->share_member==-3) {
			$durationDC=$duration;
		}
		if($pilotName!="SELF" && $line->share_member==0) {
			$durationDC=$duration;
		}
		
		PRINT("$duration;$durationDC");
		print("\n") ;
	}
	exit ;
} // pilotlog output

// Normally, this php is only called for the 2 above actions.
journalise($originalUserId, "F", "Aborting myfolio.php referer = $_SERVER[HTTP_REFERER]") ;
exit ;
?>