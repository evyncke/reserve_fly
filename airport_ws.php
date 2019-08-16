<?php
// Let's do the work now
include_once 'dbi.php' ;
ob_start("ob_gzhandler");

$apt_code = mysqli_real_escape_string($mysqli_link, web2db(trim($_REQUEST['apt_code']))) ;
if ($apt_code == '')
	die("1 parameter is requested: apt_code") ;

// Prepare all parameters and request string

$result = mysqli_query($mysqli_link, "select * from $table_airports where a_code like '%$apt_code%' or a_name like '%$apt_code%' or a_municipality like '%$apt_code%' order by a_code limit 0,20") 
	or die("Cannot fetch airport: " . mysqli_error($mysqli_link)) ;
$result_array = array() ;
while ($line = mysqli_fetch_array($result)) {
	// Data from DB is already in UTF-8, no need to translate character set
	$result_array[] = array('code' => $line['a_code'], 'name' => $line['a_name'], 'country' => $line['a_country'], 'municipality' => $line['a_municipality']) ;
}
// Let's send the data back

header('Content-type: application/json');
print(json_encode($result_array));
?>
