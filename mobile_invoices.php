<?php
/*
   Copyright 2023-2025 Eric Vyncke

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

$originalUserId = $userId ;

if (isset($_REQUEST['user']) and ($userIsAdmin or $userIsBoardMember)) {
	if ($userId != 62) journalise($userId, "I", "Start of myinvoices, setting user to $_REQUEST[user]") ;
	$userId = intval($_REQUEST['user']) ;
	if (! is_numeric($userId)) die("Invalid user ID") ;
} else
	if ($userId != 62) journalise($userId, "I", "Start of myinvoices") ;

$result = mysqli_query($mysqli_link, "SELECT * FROM $table_person WHERE jom_id = $userId")
	or journalise(0, 'F', "Impossible de lire le membre $userId: " . mysqli_error($mysqli_link)) ;
$pilot = mysqli_fetch_array($result) or journalise(0, 'F', "Membre $userId inconnu") ;
$userName = db2web("$pilot[first_name] $pilot[last_name]") ;
$userLastName = substr(db2web($pilot['last_name']), 0, 5) ;
$codeCiel = $pilot['ciel_code'] ;
$odooId = $pilot['odoo_id'] ;
mysqli_free_result($result) ;

function numberFormat($n, $decimals = 2, $decimal_separator = ',', $thousand_separator = ' ') {
	if ($n == 0) return '' ;
	return substr('        ' . number_format($n, $decimals, $decimal_separator, $thousand_separator) . '&nbsp;&euro;', -10, 10);
}

print("<div class=\"container-fluid\">") ;

if ($userIsInstructor or $userIsAdmin) {
        print("<p>En tant qu'instructeur/administrateur, vous pouvez consulter les situations comptables des autres membres: <select id=\"pilotSelect\" onchange=\"pilotSelectChanged();\">" ) ;
        print("</select></p>") ;
}
?>
<h2>Factures de <?=$userName?></h2>
<p class="lead">Voici vos factures (leur état payé/à payer est mis à jour plusieurs fois par jour si vous utilisez la communication
	structurée ou le QR-code de la facture).</p>

<!-- using tabs -->
<ul class="nav nav-tabs">
	<li class="nav-item">
  		<a class="nav-link" aria-current="page" href="mobile_ledger.php?user=<?=$userId?>">Opérations comptables</a>
	</li>
	<li class="nav-item">
		<a class="nav-link active" aria-current="page" href="<?="mobile_invoices.php?user=$userId"?>">Factures récentes</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" aria-current="page" href="<?="mobile_folio.php?previous&user=$userId"?>">Folio du mois précédent</a>
  	</li>
	  <li class="nav-item">
		<a class="nav-link" aria-current="page" href="<?="mobile_folio.php?user=$userId"?>">Folio de ce mois</a>
  	</li>
</ul> <!-- tabs -->

<div class="row">
<div class="col-sm-12 col-md-8 col-lg-6">
<div class="table-responsive">
<table class="table table-striped table-hover">
	<thead>
		<tr><th>Date</th><th>N° pièce</th><th>Type</th><th style="text-align: right;">Montant</th></tr>
	</thead>
<tbody class="table-group-divider">
<?php
$count = 0 ;
$total = 0.0 ;
$first_date = null ;
// Now let's access Odoo invoices
if ($odooId != '') {
	print("</tbody>
	<tbody class=\"table-group-divider\">") ;
	require_once 'odoo.class.php' ;
	$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
	$invoices = $odooClient->SearchRead('account.move', array(array(
		// TODO also list out_refund for credit notes ? like this without any move_type filter https://www.spa-aviation.be/resa/mobile_ledger.php?user=182
				'|',
				array('move_type','=','out_invoice'),
				array('move_type','=','out_refund'),
				array('state', '=', 'posted'),
				array('date', '>' , '2023-12-31'),
				array('partner_id', '=', intval($odooId))
			)), 
			array('fields' => array('id', 'invoice_date', 'move_type', 'type_name', 'amount_total', 'name', 'payment_reference', 'payment_state', 'access_url', 'access_token'),
				'order' => 'date')) ;
	foreach ($invoices as $invoice) {
		if (!$first_date) $first_date = $invoice['invoice_date'] ;
		switch ($invoice['payment_state']) {
			case 'paid': $paid_msg = '<span class="badge rounded-pill text-bg-success">Payé</span>'; $total += $invoice['amount_total'] ; break ;
			case 'reversed': $paid_msg = '<span class="badge rounded-pill text-bg-info">Extourné</span>' ; $total -= $invoice['amount_total'] ; break ;
			// state 'draft'
			default: $paid_msg = '<span class="badge rounded-pill text-bg-warning">Non payé</span>'; $total += $invoice['amount_total'] ;
		}
		$amount = number_format($invoice['amount_total'], 2, ',', '.') ;
		// TODO QR code BCD/002/1/SCT//Royal Aéro Para Club de Spa asbl/BE647.../EUR70.00//++000....+++
		$invoice_name = ($invoice['access_token']) ? "<a href=\"https://$odoo_host$invoice[access_url]?access_token=$invoice[access_token]\"target=\"_blank\">$invoice[name]
				<i class=\"bi bi-box-arrow-up-right\" title=\"Ouvrir la pièce comptable dans une autre fenêtre\"></i></a>" : $invoice['name'] ;
		print("<tr><td>$invoice[invoice_date]</td>
			<td>$invoice_name</td>
			<td>$invoice[type_name]</td>
			<td style=\"text-align: right;\">$paid_msg$amount&nbsp;&euro;</td>
			</tr>\n") ;
		$count++ ;		
	}
} // if ($odooId != '')
?>
</tbody>
<?php
if ($count > 0) {
	print('<tfoot class="table-group-divider">
		<tr class="bg-info"><td colspan="3">Total facturé depuis le ' . $first_date . 
		'</td><td class="text-end">' . number_format($total, 2, ',', '.')  . ' &euro;</td></tr>
	</tfoot>') ;	
}
?>
</table>
</div><!-- table responsive -->
</div><!-- col -->
</div><!-- row -->
<?php
if ($count == 0) print("<p class=\"alert-info\">Hélas, pas encore de facture à votre nom dans le système.</p>\n") ;
?>
<span id="payment" style="display: none;">
<h2>QR-code pour payer <span id="payment_reason"></span> de <span id="payment_amount"></span> &euro;</h3>
<p>Le QR-code contient votre identifiant au niveau de la comptabilité
RAPCS (<em><?=$codeCiel?></em>). Le QR-code est à utiliser avec une application bancaire
et pas Payconiq (ce dernier étant payant pour le commerçant).</p>
<img id="payment_qr_code" width="300" height="300" src="qr-code.php?chs=300x300&chl=<?=urlencode($epcString)?>">
</span>
<script>
var 
	epcBic = '<?=$bic?>' ;
	epcName = '<?=$bank_account_name?>' ;
	epcIban = '<?=$iban?>' ;
	compteCiel = '400<?=$codeCiel?>' ;
	userLastName = '<?=$userLastName?>' ;

function pay(reason, amount) {
	if (amount <= 0.0 || amount <= 0) {
		document.getElementById('payment').style.display = 'none' ;
		return ;
	}
	document.getElementById('payment').style.display = 'block' ;
	document.getElementById('payment_reason').innerText = reason ;
	document.getElementById('payment_amount').innerText = amount ;
	// Should uptdate to version 002 (rather than 001), https://www.europeanpaymentscouncil.eu/document-library/guidance-documents/quick-response-code-guidelines-enable-data-capture-initiation
	// There should be 2 reasons, first one is structured, the second one is free text
	var epcURI = "BCD\n001\n1\nSCT\n" + epcBic + "\n" + epcName + "\n" + epcIban + "\nEUR" + amount + "\n" + reason + "\n" + reason ;
	document.getElementById('payment_qr_code').src = "qr-code.php?chs=300x300&&chl=" + encodeURI(epcURI) ;
}

</script>
</div> <!-- container fluid -->
</body>
</html>
