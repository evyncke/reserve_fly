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

// Ciel Commercial -> Listes -> Clients... 
// Selection de toutes les lignes -> engrenage -> liste des membres -> XLSX
// Selection de toutes les lignes -> engrenage -> les colonnes de la liste -> TXT

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
	if ($person['id'] == '') continue ; // Not found
	if ($person['jom_id'] == '') $person['jom_id'] = -1 ;
	$client_nom = web2db($client['Nom']) ;
	if ($person['address'] == '' and $client['Adresse 1'] != '') {
		$address = web2db(mysqli_real_escape_string($mysqli_link, $client['Adresse 1'])) ;
		$return_code = mysqli_query($mysqli_link, "UPDATE $table_person SET address = '$address' WHERE id = $person[id]") ;
		if ($return_code)
			 Journalise($person['jom_id'], "I", "Address $address ajouté pour $person[id]/$person[name]/$client_nom") ;
		else
			 Journalise($person['jom_id'], "E", "Cannot add address $address for $person[id]/$person[name]/$client_nom") ;
	}
	if ($person['city'] == '' and $client['Ville'] != '') {
		$city = web2db($client['Ville']) ;
		$return_code = mysqli_query($mysqli_link, "UPDATE $table_person SET city = '$city' WHERE id = $person[id]") ;
		if ($return_code)
			 Journalise($person['jom_id'], "I", "City $city ajouté pour $person[id]/$person[name]/$client_nom") ;
		else
			 Journalise($person['jom_id'], "E", "Cannot add city $city for $person[id]/$person[name]/$client_nom") ;
	}
	if ($person['zipcode'] == '' and $client['C.P.'] != '') {
		$zipcode = web2db($client['C.P.']) ;
		$return_code = mysqli_query($mysqli_link, "UPDATE $table_person SET zipcode = '$zipcode' WHERE id = $person[id]") ;
		if ($return_code)
			 Journalise($person['jom_id'], "I", "Zipcode $zipcode ajouté pour $person[id]/$person[name]/$client_nom") ;
		else
			 Journalise($person['jom_id'], "E", "Cannot add zipcode $zipcode for $person[id]/$person[name]/$client_nom") ;
	}
	if ($person['cell_phone'] == '' and $client['Portable'] != '') {
		$return_code = mysqli_query($mysqli_link, "UPDATE $table_person SET cell_phone = '$client[Portable]' WHERE id = $person[id]") ;
		if ($return_code)
			 Journalise($person['jom_id'], "I", "Cell_number $client[Portable] ajouté pour $person[id]/$person[name]/$client_nom") ;
		else
			 Journalise($person['jom_id'], "E", "Cannot add cell_number $client[Portable] for $person[id]/$person[name]/$client_nom") ;
	}
	if ($person['ciel_code'] == $client['Code'] ) continue ; // Already existing
	print("... No ciel_code for $person[name]/$client[Nom], adding $client[Code]<br/>\n") ;
	$return_code = mysqli_query($mysqli_link, "UPDATE $table_person SET ciel_code = '$client[Code]' WHERE id = $person[id]") ;
	if ($return_code)
		 Journalise($person['jom_id'], "I", "Ciel_code $client[Code] ajouté pour $person[id]/$person[name]/$client_nom") ;
	else
		 Journalise($person['jom_id'], "E", "Cannot add ciel_code $client[Code] for $person[id]/$person[name]/$client_nom") ;
}

print("<hr><pre>") ; print_r($rows) ; print("</pre>") ;
?>
