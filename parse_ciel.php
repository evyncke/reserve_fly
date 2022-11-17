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

function comma2dot($s) {
	if ($s == '') return 'NULL' ;
	return str_replace([',', ' '], ['.', ''], $s) ;
}

function date2sql($s) {
	$tokens = explode('-', $s) ;
	return "20$tokens[2]-$tokens[1]-$tokens[0]" ;
}

$lines = file('GrandLivre.txt', FILE_IGNORE_NEW_LINES || FILE_SKIP_EMPTY_LINES) ;

// Alas, using the direct export as .txt rather than .pdf does not have headers...
$last_line = array_pop($lines) ;
$last_line = array_pop($lines) ;
$last_line = array_pop($lines) ; // Dossier : RAPCS - Royal Aéro Para Club de Spa asbl Grand livre Le 31-08-22
print("Last line = $last_line") ;
if (preg_match('/^Dossier : .+ Grand livre Le (.+)$/', trim($last_line), $matches)) {
	$balance_date = $matches[1] ;
	$tokens = explode('-', $balance_date) ;
	$balance_date = "20$tokens[2]-$tokens[1]-$tokens[0]" ;
} else { // Let's use the file date
	$balance_date = date('Y-m-d', filemtime('GrandLivre.txt')) ;
}

// Try to find all balances
// Lines look like TOTAL COMPTE 400VUJE Solde compte débiteur 6 179,08 3 447,76 2 731,32 
// but also for large negative Found TOTAL COMPTE 400R005 Solde compte créditeur 150,00 2 640,00 -2 490,00
// TODO does not work yet on large negative numbers

$balances = array() ;

foreach ($lines as $line) {
	$columns = explode("\t", $line) ; // TAB separated file
	if (strpos($columns[0], "TOTAL COMPTE 400") !== false) {
		if (preg_match('/TOTAL COMPTE (\S+)/', trim($columns[0]), $matches)) {
			$account = $matches[1] ;
			if (trim($columns[12]) == '')
				$amount = 0.0 ;
			else
				$amount = str_replace([',', ' '], ['.', ''], trim($columns[12])) ;
			$balances[$account] = $amount ;	
		}
	}
}

var_dump($balances) ;

$result = mysqli_query($mysqli_link, "SELECT ciel_code, first_name, last_name
		FROM $table_person
		WHERE ciel_code IS NOT NULL")
	or die("Cannot read members: " . mysqli_error($mysqli_link)) ;
$known_clients = array() ;
while ($row = mysqli_fetch_array($result)) {
	$first_name = db2web($row['first_name']) ;
	$last_name = db2web($row['last_name']) ;
	$known_clients[$row['ciel_code']] = true ;
	print("Processing $first_name $last_name...") ;
	if (isset($balances["400$row[ciel_code]"])) {
		$balance = $balances["400$row[ciel_code]"] ;
		print("$balance\n") ;
		mysqli_query($mysqli_link, "REPLACE INTO $table_bk_balance (bkb_account, bkb_date, bkb_amount)
			VALUES('400$row[ciel_code]', '$balance_date', $balance)")
			or die("Cannot replace values('400$row[ciel_code]', '$balance_date', $balance) in $table_bk_balance: " . mysqli_error($mysqli_link)) ;
		unset($balances["400$row[ciel_code]"]) ;
	} else
		print(" no balance\n") ;
}

print("<h2>Comptes clients inconnus</h2>\n") ;

foreach ($balances as $client => $balance)
	print("$client => $balance\n") ;

// Souci général: trouver une clé unique... ni le numéro de mouvement, ni le numéro de pièce sont uniques
print("<h2>Analyse de toutes les lignes du grand livre</h2>\n") ;
mysqli_query($mysqli_link, "DELETE FROM $table_bk_ledger")
	or die("Cannot erase content of $table_bk_ledger: " . mysqli_error($mysqli_link)) ;

$currentClient = false ;
foreach($lines as $line) {
	$line = trim($line) ; // Just to avoid any space characters, if any...
	$columns = explode("\t", $line) ; // TAB separated file
	$journal = $columns[1] ;
	$date = date2sql($columns[2]) ;
	$reference = $columns[3] ;
	$label = mysqli_real_escape_string($mysqli_link, web2db($columns[4])) ;
	$debit = comma2dot($columns[8]) ;
	$credit = comma2dot($columns[10]) ;
	if (preg_match('/^400(\S+) (.+)/', $columns[0], $matches)) {
		$codeClient = $matches[1] ;
		if (isset($known_clients[$codeClient])) {
			print("Processing $matches[2]\n") ;
			$currentClient = $matches[1] ;
		} else {
			print("$matches[2] not interesting ($codeClient not found)\n") ;
			$currentClient = false ;
		}
	} else if ($currentClient) { // Is it useful information ?
		if ($journal == 'ANX' or $journal == 'F01' or $journal == 'F08' or $journal == 'VEN' or $journal == 'VNC' or $journal == 'OPD') {
			print("Journal $journal $label en date du $date lettre '$columns[9]' <$line>\n") ;
			$sql = "REPLACE INTO $table_bk_ledger (bkl_posting, bkl_client, bkl_journal, bkl_date, bkl_reference, bkl_label, bkl_debit, bkl_letter, bkl_credit)
				VALUES ($columns[0], '$currentClient', '$journal', '$date', '$reference',  '$label', $debit, '$columns[9]', $credit)";
			print("$sql\n") ;
			mysqli_query($mysqli_link, $sql)
				or die("Cannot replace into $table_bk_ledger " . mysqli_error($mysqli_link)) ;
		} else
			print("Cannot process: $line\n") ;
	}
}
?>

</pre>
