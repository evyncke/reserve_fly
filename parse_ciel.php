<pre>
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
ini_set("auto_detect_line_endings", true); // process CR CR/LF or LF as line separator

$lines = file('GrandLivre.txt', FILE_IGNORE_NEW_LINES || FILE_SKIP_EMPTY_LINES) ;


$last_line = array_pop($lines) ;
$last_line = array_pop($lines) ;
$last_line = array_pop($lines) ; // Dossier : RAPCS - Royal Aéro Para Club de Spa asbl Grand livre Le 31-08-22
print("Last line = $last_line") ;
if (preg_match('/^Dossier : .+ Grand livre Le (.+)$/', trim($last_line), $matches)) {
	$balance_date = $matches[1] ;
	$tokens = explode('-', $balance_date) ;
	$balance_date = "20$tokens[2]-$tokens[1]-$tokens[0]" ;
}

// Try to find all balances
// Lines look like TOTAL COMPTE 400VUJE Solde compte débiteur 6 179,08 3 447,76 2 731,32 
// but also for large negative Found TOTAL COMPTE 400R005 Solde compte créditeur 150,00 2 640,00 -2 490,00
// TODO does not work yet on large negative numbers

$balances = array() ;

foreach ($lines as $line) {
	if (preg_match('/^TOTAL COMPTE (\S+) Solde compte (\S+) (.+)/', trim($line), $matches)) {
		$tokens = preg_split('/\s+/', $matches[3]) ;
		$tokens = preg_split('/ /', $matches[3]) ;
		$amount = 0.0 + str_replace(',', '.', array_pop($tokens)) ;
		$token = array_pop($tokens) ;
		$multiplier = 1000 ;
		while (strpos($token, ',') === false) {
			$amount += $multiplier * $token ;
			$multiplier *= 1000 ;
			$token = array_pop($tokens) ;
		}
		$balances[$matches[1]] = $amount ;
	} else if (preg_match('/^TOTAL COMPTE (\S+) Compte soldé (.+)/', trim($line), $matches)) {
		$balances[$matches[1]] = 0.0 ;
	}
}

$result = mysqli_query($mysqli_link, "SELECT ciel_code, first_name, last_name
		FROM $table_person
		WHERE ciel_code IS NOT NULL")
	or die("Cannot read members: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
	$first_name = db2web($row['first_name']) ;
	$last_name = db2web($row['last_name']) ;
	print("Processing $first_name $last_name...") ;
	$balance = $balances["400$row[ciel_code]"] ;
	if (isset($balances["400$row[ciel_code]"])) {
		print("$balance\n") ;
		mysqli_query($mysqli_link, "REPLACE INTO $table_bk_balance (bkb_account, bkb_date, bkb_amount)
			VALUES('400$row[ciel_code]', '$balance_date', $balance)")
			or die("Cannot replace value in $table_bk_balance: " . mysqli_error($mysqli_link)) ;
		unset($balances["400$row[ciel_code]"]) ;
	} else
		print(" no balance\n") ;
}

print("<h2>Comptes clients inconnus</h2>\n") ;

foreach ($balances as $client => $balance)
	print("$client => $balance\n") ;

?>

</pre>
