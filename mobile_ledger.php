<?php
/*
   Copyright 2022-2025 Eric Vyncke, Patrick Reginster

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

require_once "dbi.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}

require_once 'mobile_header5.php' ;
require_once "folio.php" ;

$originalUserId = $userId ;

if (isset($_REQUEST['user']) and ($userIsAdmin or $userIsBoardMember)) {
	if ($originalUserId != 62) journalise($userId, "I", "Start of myfolio, setting user to $_REQUEST[user]") ;
	$userId = $_REQUEST['user'] ;
	if (! is_numeric($userId)) die("Invalid user ID") ;
} else
	if ($originalUserId != 62) journalise($originalUserId, "I", "Start of myfolio") ; // Eric Vyncke doing too many tests

$result = mysqli_query($mysqli_link, "SELECT * FROM $table_person 
		LEFT JOIN $table_company_member ON cm_member = jom_id
		LEFT JOIN $table_company ON cm_company = c_id
		WHERE jom_id = $userId")
	or journalise($originalUserId, 'F', "Impossible de lire le pilote $userId: " . mysqli_error($mysqli_link)) ;
$pilot = mysqli_fetch_array($result) or journalise($originalUserId, 'F', "Pilote $userId inconnu") ;
$userName = db2web("$pilot[first_name] $pilot[last_name]") ;
$userLastName = substr(db2web($pilot['last_name']), 0, 5) ;
$codeCiel = $pilot['ciel_code'] ;
$odooId = $pilot['odoo_id'] ;
$odooCommercialId = $pilot['c_odoo_id'] ;
$ledgerOwner = ($pilot['c_name']) ? db2web("$pilot[first_name] $pilot[last_name] (<i>$pilot[c_name]</i>)") : $userName ;
mysqli_free_result($result) ;

function numberFormat($n, $decimals = 2, $decimal_separator = ',', $thousand_separator = ' ') {
	if ($n == 0) return '' ;
	return number_format($n, $decimals, $decimal_separator, $thousand_separator) . '&nbsp;&euro;';
}
print("<div class=\"container-fluid\">") ;

if ($userIsInstructor or $userIsAdmin) {
        print("<p>En tant qu'instructeur/administrateur, vous pouvez consulter les situations comptables des autres membres: <select id=\"pilotSelect\" onchange=\"pilotSelectChanged();\">" ) ;
        print("</select></p>") ;
}
?>
<h2>Grand livre comptable de <?=$ledgerOwner?></h2>
<p class="lead">Voici une vue comptable de votre compte membre RAPCS (mise à jour plusieurs fois par semaine par nos bénévoles voire plusieurs
	fois par jour ouvrable en utilisant un virement immédiat avec la communication structurée des factures).</p>
<p class="small">Accès au folio et aux factures via le menu déroulant en cliquant sur votre nom en haut à droite ou les onglets ci-dessous.</p>

<!-- using tabs -->
<ul class="nav nav-tabs">
	<li class="nav-item">
  		<a class="nav-link active" aria-current="page" href="mobile_ledger.php?user=<?=$userId?>">Opérations comptables</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" aria-current="page" href="<?="mobile_invoices.php?user=$userId"?>">Factures récentes</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" aria-current="page" href="<?="mobile_folio.php?previous&user=$userId"?>">Folio du mois précédent</a>
  	</li>
	  <li class="nav-item">
		<a class="nav-link" aria-current="page" href="<?="mobile_folio.php?user=$userId"?>">Folio de ce mois</a>
  	</li>
</ul> <!-- tabs -->

<div class="row">
<div class="col-sm-12 col-md-9 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<th>Date</th><th>Opération (journal)</th><th>Pièce</th><th>Description</th><th style="text-align: right;">Débit</th><th style="text-align: right;">Crédit</th><th style="text-align: right;">Solde</th>
</thead>
<tbody class="table-group-divider">
<?php
$sql = "SELECT *
	FROM $table_person JOIN $table_bk_ledger ON ciel_code = bkl_client
		LEFT JOIN $table_bk_invoices ON bki_id = bkl_reference
	WHERE jom_id = $userId
	ORDER BY bkl_date ASC, bkl_posting ASC" ;
$result = mysqli_query($mysqli_link, $sql) or journalise($originalUserId, "F", "Cannot read ledger: " . mysqli_error($mysqli_link)) ;
$total_debit = 0.0 ;
$total_credit = 0.0 ;
$solde = 0.0 ;
while ($row = mysqli_fetch_array($result)) {
	switch ($row['bkl_journal']) {
		case 'ANX': $journal = 'Report année précédente' ; break ;
		case 'F01': $journal = 'Banque de la Poste' ; break ;
		case 'F06': $journal = 'BNP Fortis' ; break ;
		case 'F08': $journal = 'CBC' ; break ;
		case 'OD':
		case 'OPD': $journal = 'Opérations diverses' ; break ;
		case 'V':
		case 'VEN': $journal = 'Facture' ; break ;
		case 'VNC': $journal = 'Note de crédit' ; break ;
		default : $journal = $row['bkl_journal'] ;
	}
	$dummy_move = ($row['bkl_journal'] == 'OD' or $row['bkl_journal'] == 'OPD') ;
	$tr_class = ($dummy_move) ? ' class="fw-lighter fst-italic"' : '' ;
 	if ($row['bki_file_name'])
		$reference = '<a href="' . $row['bki_file_name'] . '" target="_blank">' . $row['bki_id'] . ' <i class="bi bi-box-arrow-up-right" title="Ouvrir la pièce comptable dans une autre fenêtre"></i></a>' ;
	else
		$reference = $row['bkl_reference'] ;
	$debit="";
	if ($row['bkl_debit']) {
		$debit="-".number_format($row['bkl_debit'], 2, ",", ".") ;
		if (!$dummy_move) $total_debit += $row['bkl_debit'] ;
	}
	$credit="";
	if ($row['bkl_credit']){ 
		$credit="+".number_format($row['bkl_credit'], 2, ",", ".") ;
		if (!$dummy_move) $total_credit += $row['bkl_credit'] ;
	}
	$solde=$total_credit-$total_debit;
	$solde=number_format($solde,2,",",".");
	print("<tr$tr_class><td>$row[bkl_date]</td><td>$journal</td><td>$reference</td><td>" . db2web($row['bkl_label']) . "</td><td style=\"text-align: right;\">$debit</td><td style=\"text-align: right;\">$credit</td><td style=\"text-align: right;\">$solde&nbsp;&euro;</td></tr>\n") ;
}

// Now let's access Odoo ledger moves
if ($odooId != '') {
	print("</tbody>
	<tbody class=\"table-group-divider\">") ;
	require_once 'odoo.class.php' ;
	$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
	if ($originalUserId == 62) $odooClient->debug= true ; //EVY
	print("</tbody>
	<tbody class=\"table-group-divider\">") ;
	$moves = $odooClient->SearchRead('account.move.line', array(
		array(
			array('date', '>' , '2023-12-31'),
			'|', // Reverse Polish notation for OR...
			array('partner_id', '=', intval($odooId)),
			array('partner_id', '=', intval($odooCommercialId)),
			'|', // Should include liability_payable (for incoming bills) and liability_payable (for payment of incoming bills)
			array('account_type', '=', 'liability_payable'),
			array('account_type', '=', 'asset_receivable'),
			)), 
		array(
			'fields' => array('id', 'date', 'move_type', 'journal_id','account_type',
			'move_id', 'parent_state', 'debit', 'credit'),
			'order' => 'date,id')) ;
		foreach ($moves as $move) {
			if ($move['parent_state'] == 'cancel') continue ;
			$dummy_move = ($move['journal_id'][1] == 'Miscellaneous Operations') ;
			$tr_class = ($dummy_move) ? ' class="fw-lighter fst-italic"' : '' ;
			print("<tr$tr_class><td>$move[date]</td><td>" . $move['journal_id'][1] . "</td>") ;
				print("<td>" . $move['move_id'][1] . '<br/>' . $move['move_id'][0] . "</td>") ;
				print("<td>$move[move_type]<br/>$move[account_type]<br>$move[id]<br/>$move[parent_state]</td>" ) ;
			if ($move['debit'] > 0) {
				$debit = number_format($move['debit'], 2, ",", ".") ;
				print("<td style=\"text-align: right;\">-$debit</td><td></td>") ;
				if (!$dummy_move) $total_debit += $move['debit'] ;
			} 
			if ($move['credit'] > 0) {
				$credit = number_format($move['credit'], 2, ",", ".") ;
				print("<td></td><td style=\"text-align: right;\">+$credit</td>") ;
				if (!$dummy_move) $total_credit += $move['credit'] ;
			} 
			$solde=$total_credit-$total_debit;
			$solde=number_format($solde,2,".",",");
			print("<td style=\"text-align: right;\">$solde&nbsp;&euro;</td></tr>\n") ;
	}
}
?>
</tbody>
<tfoot>
	<?php
	$total_debit = "-" . number_format($total_debit, 2, ",", ".") ;
	$total_credit = number_format($total_credit, 2, ",", ".") ;
	print("<tr class=\"bg-info\"><td colspan=4>Totaux</td><td style=\"text-align: right;\">$total_debit&nbsp;&euro;</td><td style=\"text-align: right;\">$total_credit&nbsp;&euro;</td><td style=\"text-align: right;\">$solde&nbsp;&euro;</td><tr>");
	?>
</tfoot>
</table>
</div><!-- table-responsive-->
</div><!-- col -->
</div><!-- row -->
</div><!-- container fluid-->
</body>
</html>