<?php
/*
   Copyright 2022 Eric Vyncke

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

require_once 'dbi.php' ;
require_once 'SimpleXLSX.php' ;

use Shuchkin\SimpleXLSX;

 $xlsx = SimpleXLSX::parse('ciel.xlsx') ;

if (! $xlsx)
	die(SimpleXLSX::parseError()) ;


print("<pre>") ;
$header_values = $rows = [];
foreach ( $xlsx->rows() as $k => $r ) {
	if ( $k === 0 ) {
		$header_values = $r;
		continue;
	}
	$rows[] = array_combine( $header_values, $r );
}
print_r( $rows[3] );
print("</pre>") ;

print("<h2>Checking whether Ciel client exists in $table_person based on e-mail</h2>") ;

foreach ($rows as $client) {
	$email = $client['E-mail'] ;
	if ($email == '') continue ;
	$result = mysqli_query($mysqli_link, "SELECT * FROM $table_person WHERE email='$email'") 
		or die("Cannot read $table_person for $email: " . mysqli_error($mysqli_link));
	$person = mysqli_fetch_array($result) ;
	if (! $person) continue ; // Not found
	if ($person['ciel_code'] == $client['Code'] ) continue ; // Already existing
	print("... No ciel_code for $person[name]/$client[Nom], adding $client[Code]<br/>\n") ;
	$return_code = mysqli_query($mysqli_link, "UPDATE $table_person SET ciel_code = '$client[Code]' WHERE id = $person[id]") ;
	if ($return_code)
		 Journalise($person['jom_id'], "I", "Ciel_code $client[Code] ajouté pour $person[id]/$person[name]/$client[Nom]") ;
	else
		 Journalise($person['jom_id'], "E", "Cannot add ciel_code $client[Code] for $person[id]/$person[name]/$client[Nom]") ;
}

print("<hr><pre>") ; print_r($rows) ; print("</pre>") ;
?>
