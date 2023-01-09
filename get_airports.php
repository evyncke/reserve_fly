<?php
ob_start("ob_gzhandler");

require_once 'dbi.php' ;

$error_message = '' ;
$airports = array() ;

// Parameter sanitization
$code = trim($_REQUEST['code']) ;
if ($code == '') $error_message = "Missing parameter" ;
$code = mysqli_real_escape_string($mysqli_link, $code) ;
$airports['Code'] = $code ;

if ($error_message != '') {
	$airports['errorMessage'] = $error_message ;
} else {
	$sql = "select upper(a_code) as a_code, a_name
		from $table_airports
		where (a_code like '%$code%') or (a_name like '%$code%')
		order by a_code
		limit 0,50" ;
	$airports['sql'] = $sql ;
	$result = mysqli_query($mysqli_link, $sql) ;
	if ($result) 
		while ($row = mysqli_fetch_array($result)) {
			$airport = array() ;
			$airport['code'] = $row['a_code'] ;
			$airport['name'] = db2web($row['a_name']) ;
			$airports[] = $airport ;
		}
	else
		$airports['errorMessage'] =  "Cannot read airports: ".mysqli_error($mysqli_link);
}
// Let's send the data back
header('Content-type: application/json');
print(json_encode($airports)) ;
?>
