<?php
/*
   Copyright 2022-2023 Eric Vyncke

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

if (! $userIsAdmin && ! $userIsBoardMember)
    journalise($userId, "F", "Vous n'avez pas le droit de consulter cette page ou vous n'êtes pas connecté.") ; 

if( ! isset($_POST["submit"]) or ! isset($_FILES["ledgerFile"]))
    journalise($userId, "F", "Cette page ne peut être utilisée en stand-alone.") ;

?><!DOCTYPE html>
<html lang="fr">
<head>
<link rel="stylesheet" type="text/css" href="mobile.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<meta charset="utf-8">
<!--meta name="viewport" content="width=320"-->
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- http://www.alsacreations.com/article/lire/1490-comprendre-le-viewport-dans-le-web-mobile.html -->
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<!-- http://www.w3schools.com/bootstrap/ -->
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script><html>
	<title>Import du grand livre client de Ciel</title>
</head>
<body>
<h1>Import du grand livre client de Ciel</h1>	
<?php

function comma2dot($s) {
	if ($s == '') return 'NULL' ;
	return str_replace([',', ' '], ['.', ''], $s) ;
}

function date2sql($s) {
	$tokens = explode('-', $s) ;
	return "20$tokens[2]-$tokens[1]-$tokens[0]" ;
}

// Now read the temporary file

$lines = file($_FILES["ledgerFile"]["tmp_name"], FILE_IGNORE_NEW_LINES || FILE_SKIP_EMPTY_LINES) ;

// Find the balance date
// Alas, using the direct export as .txt rather than .pdf does not have headers...
$last_line = array_pop($lines) ;
$last_line = array_pop($lines) ;
$last_line = array_pop($lines) ; // Dossier : RAPCS - Royal Aéro Para Club de Spa asbl Grand livre Le 31-08-22

if (preg_match('/^Dossier : .+ Grand livre Le (.+)$/', trim($last_line), $matches)) {
	$balance_date = $matches[1] ;
	$tokens = explode('-', $balance_date) ;
	$balance_date = "20$tokens[2]-$tokens[1]-$tokens[0]" ;
} else { // Let's use the file date
	$balance_date = date('Y-m-d', filemtime($_FILES["ledgerFile"]["tmp_name"])) ; // Even if meaningless for a file upload...
}

// As we need to have a yearly ledger, try to find the 1st post date as in:
// P<E9>riode du 01-01-22 au 31-12-23 (expected in the first lines)
// Fallback is current year
$legder_year = date('y') ;
foreach($lines as $line) {
	$line = trim($line) ; // Just to avoid any space characters, if any...
	if (preg_match('/^P.riode du ..-..-(..) au ..-..-../', $line, $matches)) {
		$ledger_year = $matches[1] ;
		break ;
	}
}
journalise($userId, "D", "Ledger version $ledger_year") ;

// Try to find all balances
// Lines look like TOTAL COMPTE 400VUJE Solde compte débiteur 6 179,08 3 447,76 2 731,32 
// but also for large negative Found TOTAL COMPTE 400R005 Solde compte créditeur 150,00 2 640,00 -2 490,00
// TODO does not work yet on large negative numbers

$balances = array() ;
$names = array() ;

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
	else // Is it a line like       400PETEL    PETERS LOTHAR^I^I^I^ 
		if (strlen($columns[0]) > 8 and str_starts_with(trim($columns[0]), '400')) {
			$tokens = explode(' ', trim($columns[0])) ;
			$client_code = $tokens[0] ;
			array_shift($tokens) ;
			$names[$client_code] = db2web(implode(' ', $tokens)) ;
		}
}

print("<h2>Comptes clients connus</h2>\n") ;

// Get all club members with their book keeping ID
$result = mysqli_query($mysqli_link, "SELECT ciel_code400, first_name, last_name
		FROM $table_person
		WHERE ciel_code400 IS NOT NULL")
	or journalise($userId, "F", "Cannot read members: " . mysqli_error($mysqli_link)) ;
$known_clients = array() ;
$known_client_balances = 0 ;
while ($row = mysqli_fetch_array($result)) {
	$first_name = db2web($row['first_name']) ;
	$last_name = db2web($row['last_name']) ;
	$known_clients[$row['ciel_code400']] = true ;
	print("Processing member: $first_name $last_name ($row[ciel_code400])...") ;
	if (isset($balances["$row[ciel_code400]"])) {
		$balance = $balances["$row[ciel_code400]"] ;
		print(" $balance &euro;<br/>\n") ;
		mysqli_query($mysqli_link, "REPLACE INTO $table_bk_balance (bkb_account, bkb_date, bkb_amount)
			VALUES('$row[ciel_code400]', '$balance_date', $balance)")
			or journalise($userId, "F", "Cannot replace values('$row[ciel_code400]', '$balance_date', $balance) in $table_bk_balance: " . mysqli_error($mysqli_link)) ;
		unset($balances[$row['ciel_code400']]) ;
		$known_client_balances ++ ;
	} else
		print(" no balance found.<br/>\n") ;
}
journalise($userId, "I", "$known_client_balances members' balances processed, " . sizeof($balances) . " unknow balances") ;

print("<h2>Comptes clients inconnus et non soldés</h2>\n") ;

foreach ($balances as $client => $balance)
	if ($balance != 0)
		print("$client ($names[$client]) => solde $balance &euro;<br/>\n") ;


// Souci général: trouver une clé unique... ni le numéro de mouvement, ni le numéro de pièce sont uniques
print("<h2>Analyse de toutes les lignes du grand livre client</h2>\n") ;
mysqli_query($mysqli_link, "DELETE FROM $table_bk_ledger WHERE bkl_year = $ledger_year")
	or journalise($userId, "F", "Cannot erase content of $table_bk_ledger for year $$ledger_year: " . mysqli_error($mysqli_link)) ;

$currentClient = false ;
foreach($lines as $line) {
	$line = trim($line) ; // Just to avoid any space characters, if any...
	$columns = explode("\t", $line) ; // TAB separated file
	$journal = $columns[1] ;
	$date = date2sql($columns[2]) ;
	$reference = $columns[3] ;
	// GrandLivre.txt charset seems to be Windows ISO-8859-1
	$label = mysqli_real_escape_string($mysqli_link, web2db($columns[4])) ;
	$debit = comma2dot($columns[8]) ;
	$credit = comma2dot($columns[10]) ;
	if (preg_match('/^400(\S+) (.+)/', $columns[0], $matches)) {
		$codeClient = $matches[1] ;
		if (isset($known_clients["400$codeClient"])) {
			print("Processing " . db2web($matches[2]) . "<br/>\n") ;
			$currentClient = $matches[1] ;
		} else {
			print(db2web("$matches[2] not interesting (account 400$codeClient not matching a member)<br/>\n")) ;
			$currentClient = false ;
		}
	} else if ($currentClient) { // Is it useful information ?
		if ($journal == 'ANX' or $journal == 'F01' or $journal == 'F08' or $journal == 'VEN' or $journal == 'VNC' or $journal == 'OPD') {
//			print("Journal $journal " . db2web($label) . " en date du $date lettre '$columns[9]' <$line>\n") ;
			$sql = "REPLACE INTO $table_bk_ledger (bkl_year, bkl_posting, bkl_client, bkl_journal, bkl_date, bkl_reference, bkl_label, bkl_debit, bkl_letter, bkl_credit)
				VALUES ($ledger_year, $columns[0], '$currentClient', '$journal', '$date', '$reference',  '$label', $debit, '$columns[9]', $credit)";
//			print("$sql\n") ;
			mysqli_query($mysqli_link, $sql)
				or journalise($userId, "F", "Cannot replace into $table_bk_ledger " . mysqli_error($mysqli_link)) ;
		} else if (strlen(trim($line)) != 0 and strpos($line, 'TOTAL COMPTE') === false)
			print('Cannot parse this ledger line: <span style="color: red;">' . db2web($line) . "</span><br/>\n") ;
	}
}
journalise($userId, "I", "Grand Livre parsed") ;

// Clean-up temporary file
unlink($_FILES["ledgerFile"]["tmp_name"]) ;
?>
</body>
</html>