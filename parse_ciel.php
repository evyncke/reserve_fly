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
	return str_replace(',', '.', $s) ;
}

function date2sql($s) {
	$tokens = explode('-', $s) ;
	return "20$tokens[2]-$tokens[1]-$tokens[0]" ;
}

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
			or die("Cannot replace value in $table_bk_balance: " . mysqli_error($mysqli_link)) ;
		unset($balances["400$row[ciel_code]"]) ;
	} else
		print(" no balance\n") ;
}

print("<h2>Comptes clients inconnus</h2>\n") ;

foreach ($balances as $client => $balance)
	print("$client => $balance\n") ;

// Souci général: trouver une clé unique... ni le numéro de mouvement, ni le numéro de pièce sont uniques
print("<h2>Analyse de toutes les lignes du grand livre</h2>\n") ;
$currentClient = false ;
foreach($lines as $line) {
	$line = trim($line) ; // Just to avoid any space characters, if any...
	if (preg_match('/^400(\S+) (.+)/', $line, $matches)) {
		$codeClient = $matches[1] ;
		if (isset($known_clients[$codeClient])) {
			print("Processing $matches[2]\n") ;
			$currentClient = $matches[1] ;
		} else {
			print("$matches[2] not interesting ($codeClient not found)\n") ;
			$currentClient = false ;
		}
	} else if ($currentClient) { // Is it useful information ?
		if (preg_match('/(\d+) ANX ([0-9,\-]+) (\d+)/', $line, $matches)) {
			print("Report $matches[3] on $matches[2] <$line>\n") ;

		} else if (preg_match('/(\d+) F01 ([0-9,\-]+) (\d+) (.+) B ([A-Z]+) ([0-9,\,]+)/', $line, $matches)) {
			print("Compte financier F01 $matches[3] en date du $matches[2] lettre '$matches[5]' <$line>\n") ;
			$credit = comma2dot($matches[6]) ;
			$date = date2sql($matches[2]) ;
			$label = mysqli_real_escape_string($mysqli_link, web2db($matches[4])) ;
			$sql = "REPLACE INTO $table_bk_ledger (bkl_id, bkl_client, bkl_journal, bkl_date, bkl_reference, bkl_label, bkl_debit, bkl_letter, bkl_credit)
				VALUES ($matches[1], '$currentClient', 'F01', '$date', '$matches[3]',  '$label', NULL, '$matches[5]', $credit)";
			print("$sql\n") ;
			mysqli_query($mysqli_link, $sql)
				or die("Cannot replace into $table_bk_ledger " . mysqli_error($mysqli_link)) ;

		} else if (preg_match('/(\d+) F01 ([0-9,\-]+) (\d+) (.+) B ([0-9,\,]+)/', $line, $matches)) {
			print("Compte financier F01 $matches[3] en date du $matches[2] pas de lettre <$line>\n") ;
			$credit = comma2dot($matches[5]) ;
			$date = date2sql($matches[2]) ;
			$label = mysqli_real_escape_string($mysqli_link, web2db($matches[4])) ;
			$sql = "REPLACE INTO $table_bk_ledger (bkl_id, bkl_client, bkl_journal, bkl_date, bkl_reference, bkl_label, bkl_debit, bkl_letter, bkl_credit)
				VALUES ($matches[1], '$currentClient', 'F01', '$date', '$matches[3]',  '$label', NULL, NULL, $credit)" ;
			print("$sql\n") ;
			mysqli_query($mysqli_link, $sql)
				or die("Cannot replace into $table_bk_ledger " . mysqli_error($mysqli_link)) ;

		} else if (preg_match('/(\d+) F08 ([0-9,\-]+) (\d+) (.+) B ([A-Z]+) ([0-9,\,]+)/', $line, $matches)) {
			print("Compte financier F08 $matches[3] en date du $matches[2] lettre '$matches[5]' <$line>\n") ;
			$credit = comma2dot($matches[6]) ;
			$date = date2sql($matches[2]) ;
			$label = mysqli_real_escape_string($mysqli_link, web2db($matches[4])) ;
			$sql = "REPLACE INTO $table_bk_ledger (bkl_id, bkl_client, bkl_journal, bkl_date, bkl_reference, bkl_label, bkl_debit, bkl_letter, bkl_credit)
				VALUES ($matches[1], '$currentClient', 'F08', '$date', '$matches[3]',  '$label', NULL, '$matches[5]', $credit)";
			print("$sql\n") ;
			mysqli_query($mysqli_link, $sql)
				or die("Cannot replace into $table_bk_ledger " . mysqli_error($mysqli_link)) ;

		} else if (preg_match('/(\d+) F08 ([0-9,\-]+) (\d+) (.+) B ([0-9,\,]+)/', $line, $matches)) {
			print("Compte financier F08 $matches[3] en date du $matches[2] pas de lettre <$line>\n") ;
			$credit = comma2dot($matches[5]) ;
			$date = date2sql($matches[2]) ;
			$label = mysqli_real_escape_string($mysqli_link, web2db($matches[4])) ;
			$sql = "REPLACE INTO $table_bk_ledger (bkl_id, bkl_client, bkl_journal, bkl_date, bkl_reference, bkl_label, bkl_debit, bkl_letter, bkl_credit)
				VALUES ($matches[1], '$currentClient', 'F08', '$date', '$matches[3]',  '$label', NULL, NULL, $credit)" ;
			print("$sql\n") ;
			mysqli_query($mysqli_link, $sql)
				or die("Cannot replace into $table_bk_ledger " . mysqli_error($mysqli_link)) ;

		} else if (preg_match('/(\d+) VEN ([0-9,\-]+) (\d+) (.+) B ([0-9,\,]+) ([A-Z]+) /', $line, $matches)) {
			print("Facture $matches[3] on $matches[2] pour $matches[5] lettre $matches[6] <$line>\n") ;
			$debit = comma2dot($matches[5]) ;
			$date = date2sql($matches[2]) ;
			$label = mysqli_real_escape_string($mysqli_link, web2db($matches[4])) ;
			mysqli_query($mysqli_link, "REPLACE INTO $table_bk_ledger (bkl_id, bkl_client, bkl_journal, bkl_date, bkl_reference, bkl_label, bkl_debit, bkl_letter, bkl_credit)
				VALUES ($matches[1], '$currentClient', 'VEN', '$date', '$matches[3]', '$label', $debit, '$matches[6]', NULL)")
				or die("Cannot replace into $table_bk_ledger " . mysqli_error($mysqli_link)) ;
		} else if (preg_match('/(\d+) VEN ([0-9,\-]+) (\d+) (.+) B ([0-9,\,]+) /', $line, $matches)) {
			print("Facture $matches[3] on $matches[2] pour $matches[5] pas de lettre <$line>\n") ;
			$debit = comma2dot($matches[5]) ;
			$date = date2sql($matches[2]) ;
			$label = mysqli_real_escape_string($mysqli_link, web2db($matches[4])) ;
			mysqli_query($mysqli_link, "REPLACE INTO $table_bk_ledger (bkl_id, bkl_client, bkl_journal, bkl_date, bkl_reference, bkl_label, bkl_debit, bkl_letter, bkl_credit)
				VALUES ($matches[1], '$codeClient', 'VEN', '$date', '$matches[3]', '$label',  $debit, NULL, NULL)")
				or die("Cannot replace into $table_bk_ledger " . mysqli_error($mysqli_link)) ;

		} else if (preg_match('/(\d+) VNC ([0-9,\-]+) (.+)/', $line, $matches)) {
			print("Note de crédit $matches[3] on $matches[2] <$line>\n") ;
		} else
			print("Cannot process: $line\n") ;
	}
}
?>

</pre>
