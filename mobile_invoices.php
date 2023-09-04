<?php
/*
   Copyright 2023-2023 Eric Vyncke

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

$originalUserId = $userId ;

if (isset($_REQUEST['user']) and ($userIsAdmin or $userIsBoardMember)) {
	if ($userId != 62) journalise($userId, "I", "Start of myinvoices, setting user to $_REQUEST[user]") ;
	$userId = $_REQUEST['user'] ;
	if (! is_numeric($userId)) die("Invalid user ID") ;
} else
	if ($userId != 62) journalise($userId, "I", "Start of myinvoices") ;

$result = mysqli_query($mysqli_link, "SELECT * FROM $table_person WHERE jom_id = $userId")
	or journalise(0, 'F', "Impossible de lire le membre $userId: " . mysqli_error($mysqli_link)) ;
$pilot = mysqli_fetch_array($result) or journalise(0, 'F', "Membre $userId inconnu") ;
$userName = db2web("$pilot[first_name] $pilot[last_name]") ;
$userLastName = substr(db2web($pilot['last_name']), 0, 5) ;
$codeCiel = $pilot['ciel_code'] ;
mysqli_free_result($result) ;

function numberFormat($n, $decimals = 2, $decimal_separator = ',', $thousand_separator = ' ') {
	if ($n == 0) return '' ;
	return substr('        ' + number_format($n, $decimals, $decimal_separator, $thousand_separator) . '&nbsp;&euro;', -10, 10);
}

print("<div class=\"container-fluid\">") ;

if ($userIsInstructor or $userIsAdmin) {
        print("<p>En tant qu'instructeur/administrateur, vous pouvez consulter les situations comptables des autres membres: <select id=\"pilotSelect\" onchange=\"pilotSelectChanged();\">" ) ;
        print("</select></p>") ;
}
?>
<h2>Factures récentes de <?=$userName?></h2>
<p class="lead">Voici quelques pièces comptables récentes (mises à jour une fois par semaine environ par nos bénévoles).</p>
<p class="small">Accès au folio et opérations comptables via le menu déroulant en cliquant sur votre nom en haut à droite ou via les onglets ci-dessous.</p>

<!-- using tabs -->
<ul class="nav nav-tabs">
	<li class="nav-item">
  		<a class="nav-link" aria-current="page" href="mobile_ledger.php?user=<?=$userId?>">Opérations comptables</a>
	</li>
	<li class="nav-item">
		<a class="nav-link active" aria-current="page" href="<?="mobile_invoices.php?user=$userId"?>">Factures récentes</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" aria-current="page" href="<?="myfolio.php?previous&user=$userId"?>">Folio du mois précédent</a>
  	</li>
	  <li class="nav-item">
		<a class="nav-link" aria-current="page" href="<?="myfolio.php?user=$userId"?>">Folio de ce mois</a>
  	</li>
</ul> <!-- tabs -->

<div class="row">
<div class="col-sm-12 col-md-6 col-lg-4">
<div class="table-responsive">
<table class="table table-striped table-hover">
	<thead>
		<tr><th>Date</th><th>N° pièce</th><th>Type</th><th style="text-align: right;">Montant</th><th>Action</th></tr>
	</thead>
<?php
$sql = "SELECT *, DATE(bki_date) AS bki_date 
		FROM $table_person JOIN $table_bk_invoices ON bki_email = email 
		LEFT JOIN $table_bk_ledger ON ciel_code = bkl_client AND bki_id = bkl_reference
        WHERE jom_id = $userId" ;

$result = mysqli_query($mysqli_link, $sql) or journalise($originalUserId, "F", "Erreur systeme a propos de l'access factures: " . mysqli_error($mysqli_link)) ;
$count = 0 ;
while ($row = mysqli_fetch_array($result)) {
	// Using the invoice date from the email import as the general ledger is in the future
	$action = "<a href=\"$row[bki_file_name]\" target=\"_blank\"> <i class=\"bi bi-box-arrow-up-right\" title=\"Ouvrir la pièce comptable dans une autre fenêtre\"></i></a>" ;
    print("<tr><td>$row[bki_date]</td><td>$row[bki_id]</td>") ;
	if ($row['bkl_debit'] != '') print("<td>Facture</td><td style=\"text-align: right;\">$row[bkl_debit] &euro;</td><td>$action <a href=\"#\"  
		onClick=\"pay('$row[bki_id] 400$codeCiel $userLastName', $row[bkl_debit]);\"><i class=\"bi bi-qr-code-scan\" title=\"Payer la facture\"></i></a></td>") ;
	else if ($row['bki_amount'] != '') print("<td>Facture</td><td style=\"text-align: right;\">$row[bki_amount] &euro;</td><td>$action <a href=\"#\" 
		 onClick=\"pay('$row[bki_id] 400$codeCiel $userLastName', $row[bki_amount]);\"><i class=\"bi bi-qr-code-scan\" title=\"Payer la facture\"></i></a></td>") ;
	else if ($row['bkl_credit'] != '') print("<td>Note de crédit</td><td  style=\"text-align: right;\">" . (0.0 - $row['bkl_credit']) . " &euro;</td><td>$action</td>") ;
	print("</tr>\n") ;
    $count ++ ;
}
?>
</table>
</div><!-- table responsive -->
</div><!-- col -->
</div><!-- row -->
<?php
if ($count == 0) print("<p class=\"alter-info\">Hélas, pas encore de facture à votre nom dans le système.</p>\n") ;
?>
<span id="payment" style="display: none;">
<h2>QR-code pour payer <span id="payment_reason"></span> de <span id="payment_amount"></span> &euro;</h3>
<p>Le QR-code contient votre identifiant au niveau de la comptabilité
RAPCS (<em><?=$codeCiel?></em>). Le QR-code est à utiliser avec une application bancaire
et pas Payconiq (ce dernier étant payant pour le commerçant).</p>
<img id="payment_qr_code" width="200" height="200" src="https://chart.googleapis.com/chart?cht=qr&chs=300x300&&chl=<?=urlencode($epcString)?>">
</span id="payment">
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
	document.getElementById('payment_qr_code').src = "https://chart.googleapis.com/chart?cht=qr&chs=300x300&&chl=" + encodeURI(epcURI) ;
}

</script>
</div> <!-- container fluid -->
</body>
</html>
