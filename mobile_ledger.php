<?php
/*
   Copyright 2022-2023 Eric Vyncke, Patrick Reginster

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

require_once "dbi.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}

require_once 'mobile_header5.php' ;
require_once "folio.php" ;

$originalUserId = $userId ;

if (isset($_REQUEST['user']) and ($userIsAdmin or $userIsBoardMember)) {
	if ($userId != 62) journalise($userId, "I", "Start of myfolio, setting user to $_REQUEST[user]") ;
	$userId = $_REQUEST['user'] ;
	if (! is_numeric($userId)) die("Invalid user ID") ;
} else
	if ($userId != 62) journalise($userId, "I", "Start of myfolio") ;

$result = mysqli_query($mysqli_link, "SELECT * FROM $table_person WHERE jom_id = $userId")
	or journalise(0, 'F', "Impossible de lire le pilote $userId: " . mysqli_error($mysqli_link)) ;
$pilot = mysqli_fetch_array($result) or journalise(0, 'F', "Pilote $userId inconnu") ;
$userName = db2web("$pilot[first_name] $pilot[last_name]") ;
$userLastName = substr(db2web($pilot['last_name']), 0, 5) ;
$codeCiel = $pilot['ciel_code'] ;
mysqli_free_result($result) ;

function numberFormat($n, $decimals = 2, $decimal_separator = ',', $thousand_separator = ' ') {
	if ($n == 0) return '' ;
	return number_format($n, $decimals, $decimal_separator, $thousand_separator) . '&nbsp;&euro;';
}

$version_php = date ("Y-m-d H:i:s.", filemtime('myledger.php')) ;
?>
<div class="container-fluid">
<h2>Grand livre comptable de <?=$userName?> (#<?=$userId?>)</h2>
<p class="lead">Voici une vue comptable de votre compte membre RAPCS (mis à jour chaque semaine par nos bénévoles).</p>
<p class="small">Accès au folio et aux factures via le menu déroulant en cliquant sur votre nom en haut à droite ou la pagination ci-dessous.</p>

<div class="row">
	<ul class="pagination">
		<li class="page-item active"><a class="page-link" href="mobile_ledger.php?user=<?=$userId?>">Opérations comptables</a></li>
		<li class="page-item"><a class="page-link" href="<?="mobile_invoices.php?user=$userId"?>">Factures récentes</a></li>
		<li class="page-item"><a class="page-link" href="<?="myfolio.php?previous&user=$userId"?>">
			<i class="bi bi-caret-left-fill"></i>Folio du mois précédent <!--?=datefmt_format($fmt, $previous_month_pager)?--></a></li>
		<li class="page-item"><a class="page-link" href="<?="myfolio.php?user=$userId"?>">
			Folio de ce mois <!--?=datefmt_format($fmt,$this_month_pager)?--> <i class="bi bi-caret-right-fill"></i></a></li>
	</ul><!-- pagination -->
</div><!-- row -->

<div class="row">
<div class="col-sm-12 col-md-9 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<th>Date</th><th>Opération</th><th>Pièce</th><th>Description</th><th style="text-align: right;">Débit</th><th style="text-align: right;">Crédit</th><th style="text-align: right;">Solde</th>
</thead>
<tbody>
<?php
$sql = "SELECT *
	FROM $table_person JOIN $table_bk_ledger ON ciel_code = bkl_client
		LEFT JOIN $table_bk_invoices ON bki_id = bkl_reference
	WHERE jom_id = $userId
	ORDER BY bkl_date ASC, bkl_posting ASC" ;
$result = mysqli_query($mysqli_link, $sql) or journalise($userId, "F", "Cannot read ledger: " . mysqli_error($mysqli_link)) ;
$total_debit = 0.0 ;
$total_credit = 0.0 ;
while ($row = mysqli_fetch_array($result)) {
	switch ($row['bkl_journal']) {
		case 'ANX': $journal = 'Report année précédente' ; break ;
		case 'F01': $journal = 'Banque de la Poste' ; break ;
		case 'F06': $journal = 'BNP Fortis' ; break ;
		case 'F08': $journal = 'CBC' ; break ;
		case 'OD':
		case 'OPD': $journal = 'Operations diverses' ; break ;
		case 'V':
		case 'VEN': $journal = 'Facture' ; break ;
		case 'VNC': $journal = 'Note de crédit' ; break ;
		default : $journal = $row['bkl_journal'] ;
	}
	if ($row['bki_file_name'])
		$reference = '<a href="' . $row['bki_file_name'] . '" target="_blank">' . $row['bki_id'] . ' <i class="bi bi-box-arrow-up-right" title="Ouvrir la pièce comptable dans une autre fenêtre"></i></a>' ;
	else
		$reference = $row['bkl_reference'] ;
	$debit="";
	if ($row['bkl_debit']) {
		$debit="-".$row['bkl_debit'];
		$total_debit += $row['bkl_debit'] ;
	}
	$credit="";
	if ($row['bkl_credit']){ 
		$credit="+".$row['bkl_credit'];
		$total_credit += $row['bkl_credit'] ;
	}
	$solde=$total_credit-$total_debit;
	$solde=number_format($solde,2,".","");
	print("<tr><td>$row[bkl_date]</td><td>$journal</td><td>$reference</td><td>" . db2web($row['bkl_label']) . "</td><td style=\"text-align: right;\">$debit</td><td style=\"text-align: right;\">$credit</td><td style=\"text-align: right;\">$solde&nbsp;&euro;</td></tr>\n") ;
}
?>
</tbody>
<tfoot>
	<?php
	$total_debit=-$total_debit;
	print("<tr class=\"bg-info\"><td colspan=4>Totaux</td><td style=\"text-align: right;\">$total_debit &euro;</td><td style=\"text-align: right;\">$total_credit&nbsp;&euro;</td><td style=\"text-align: right;\">$solde&nbsp;&euro;</td><tr>");
	?>
</tfoot>
</table>
</div><!-- table-responsive-->
</div><!-- col -->
</div><!-- row -->
</div><!-- container fluid-->
</body>
</html>
