<?php
//require_once 'xlsxwriter.class.php' ;
require_once 'PHPExcel.php' ;
require_once 'dbi.php' ;
require_once 'facebook.php' ;

$this_year = date('Y') ;

function AddHeaderRow() {
	global $planes, $objPHPExcel ;

	$objPHPExcel -> getActiveSheet() -> setCellValueByColumnAndRow(0, 1, 'Date') ;
	$col = 1 ;
	foreach ($planes as $plane) {
		$objPHPExcel -> getActiveSheet() -> setCellValueByColumnAndRow($col, 1, $plane) ;
		$col ++ ;
	}
}

function AddDates($last_day) {
	global $objPHPExcel, $this_year ;

	$first_day = "$this_year-01-01" ;
	$one_day = new DateInterval('P1D') ;
	$last_date = new DateTime($last_day) ;
	for ($date = new DateTime($first_day), $row = 2; $date <= $last_date; $date->add($one_day), $row++) {
		$objPHPExcel -> getActiveSheet() -> setCellValueByColumnAndRow(0, $row, $date->format('Y-m-d')) ;
	}
}

// Get all planes (active and non active)
$result = mysqli_query($mysqli_link, "select upper(id) as id from $table_planes order by id") or
	die("Cannot read $table_planes: " . mysqli_error($mysqli_link)) ;
$planes = array() ;
while ($row = mysqli_fetch_array($result)) {
	$planes[] = $row['id'] ;
}

// Get all bookings...
$bookings = array() ;
$maintenances = array () ;
$last_day = '0000-00-00' ;
$result = mysqli_query($mysqli_link, "select r_start, r_plane, r_type, timestampdiff(minute, r_start, r_stop) as duration,
	date(r_start) as start_day, date(r_stop) as end_day
	from $table_bookings where r_start like '$this_year%' order by r_start, r_plane") 
	or die("Cannot read booking: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
	$date = $row['start_day'] ;
	$plane = $row['r_plane'] ;
	$duration =  $row['duration'] ;
	if ($row['r_type'] == BOOKING_MAINTENANCE) {
	// TODO handle multiple days...
		$maintenances[$plane][$date] = (isset($maintenances[$plane][$date])) ? $maintenances[$plane][$date] + $duration : $duration ;
	} else {
		$bookings[$plane][$date] = (isset($bookings[$plane][$date])) ? $bookings[$plane][$date] + $duration : $duration ;
	}
	if ($last_day < $row['end_day']) $last_day = $row['end_day'] ;
}

// Get all engine times based on logbook
$engine_times = array () ;
$result = mysqli_query($mysqli_link, "select l_start, l_plane, l_end_hour, l_end_minute,
	date(l_start) as start_day, date(l_end) as end_day
	from $table_logbook where l_start like '$this_year%' and l_booking is not null and l_end_hour != 0
	order by l_end, l_plane") 
	or die("Cannot read logbook: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
	$date = $row['end_day'] ;
	$plane = $row['l_plane'] ;
	$duration = $row['l_end_hour'] * 60 + $row['l_end_minute'] ;
	if (!isset($engine_times[$plane][$date]) or $duration > $engine_times[$plane][$date])
		$engine_times[$plane][$date] = $duration ;
}

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set document properties
$objPHPExcel->getProperties()->setCreator("Eric Vyncke")
		 ->setLastModifiedBy("Eric Vyncke")
		 ->setTitle("Planes calendar for year $this_year")
		 ->setDescription("Blabla") ;

// Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle("Maintenance $this_year");
// Add some data
$objPHPExcel->setActiveSheetIndex(0) ;
AddHeaderRow() ;
AddDates($last_day) ;
$col = 1 ;
foreach ($planes as $plane) {
	$first_day = "$this_year-01-01" ;
	$one_day = new DateInterval('P1D') ;
	$last_date = new DateTime($last_day) ;
	for ($date = new DateTime($first_day), $row = 2; $date <= $last_date; $date->add($one_day), $row++) {
		$day = $date->format('Y-m-d') ;
		if (isset($maintenances[$plane][$day]))
			$objPHPExcel -> getActiveSheet() -> setCellValueByColumnAndRow($col, $row, $maintenances[$plane][$day]) ;
	}
	$col ++ ;
}

// Do the bookings
$objPHPExcel->createSheet();
$objPHPExcel->setActiveSheetIndex(1) ;
$objPHPExcel->getActiveSheet()->setTitle("Booking $this_year");
AddHeaderRow() ;
AddDates($last_day) ;
$col = 1 ;
foreach ($planes as $plane) {
	$first_day = "$this_year-01-01" ;
	$one_day = new DateInterval('P1D') ;
	$last_date = new DateTime($last_day) ;
	for ($date = new DateTime($first_day), $row = 2; $date <= $last_date; $date->add($one_day), $row++) {
		$day = $date->format('Y-m-d') ;
		if (isset($bookings[$plane][$day]))
			$objPHPExcel -> getActiveSheet() -> setCellValueByColumnAndRow($col, $row, $bookings[$plane][$day]) ;
	}
	$col ++ ;
}

// Do the engine hours
$objPHPExcel->createSheet();
$objPHPExcel->setActiveSheetIndex(2) ;
$objPHPExcel->getActiveSheet()->setTitle("Engine $this_year (pilote)");
AddHeaderRow() ;
AddDates($last_day) ;
$col = 1 ;
foreach ($planes as $plane) {
	$first_day = "$this_year-01-01" ;
	$one_day = new DateInterval('P1D') ;
	$last_date = new DateTime($last_day) ;
	for ($date = new DateTime($first_day), $row = 2; $date <= $last_date; $date->add($one_day), $row++) {
		$day = $date->format('Y-m-d') ;
		if (isset($engine_times[$plane][$day]))
			$objPHPExcel -> getActiveSheet() -> setCellValueByColumnAndRow($col, $row, $engine_times[$plane][$day]) ;
	}
	$col ++ ;
}

// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);

if (true) {
// redirect output to client browser
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="planes.xlsx"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
} else {
	print("<hr>End of script") ;
}

?>
