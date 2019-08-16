<?php
/*
   Copyright 2014-2019 Eric Vyncke

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
			$airport['name'] = ($convertToUtf8) ? iconv("ISO-8859-1", "UTF-8", $row['a_name']) : $row['a_name'] ;
			$airports[] = $airport ;
		}
	else
		$airports['errorMessage'] =  "Cannot read airports: ".mysqli_error($mysqli_link);
}
// Let's send the data back
header('Content-type: application/json');
print(json_encode($airports)) ;
?>
