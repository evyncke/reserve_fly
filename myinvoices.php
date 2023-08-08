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

MustBeLoggedIn() ;

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
$version_php = date ("Y-m-d H:i:s.", filemtime('myinvoices.php')) ;
?><html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<meta charset="utf-8">
<!--meta name="viewport" content="width=320"-->
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- http://www.alsacreations.com/article/lire/1490-comprendre-le-viewport-dans-le-web-mobile.html -->
<!-- http://www.w3schools.com/bootstrap/ -->
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<title>Factures récentes</title>
<!-- Matomo -->
<script type="text/javascript">
  var _paq = window._paq = window._paq || [];
  /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
  _paq.push(['setUserId', '<?=$userName?>']);
  _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
  _paq.push(["setDomains", ["*.spa-aviation.be","*.ebsp.be","*.m.ebsp.be","*.m.spa-aviation.be","*.resa.spa-aviation.be"]]);
  _paq.push(['enableHeartBeatTimer']);
  _paq.push(['setCustomVariable', 1, "userID", <?=$originalUserId?>, "visit"]);
  _paq.push(["setCookieDomain", "*.spa-aviation.be"]);
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="//analytics.vyncke.org/";
    _paq.push(['setTrackerUrl', u+'matomo.php']);
    _paq.push(['setSiteId', '5']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Code -->
</head>
<body>
<h2>Factures récentes</h2>
<p>Voici quelques pièces comptables r&eacute;centes (mises à jour une fois par semaine environ par nos bénévoles).</p>
<table class="table table-striped table-responsive table-hover" >
	<thead>
		<tr><th>Date</th><th>N° pièce</th><th>Type</th><th>Montant</th><th>Action</th></tr>
	</thead>
<?php
$sql = "SELECT * FROM $table_person JOIN $table_bk_invoices ON bki_email = email LEFT JOIN $table_bk_ledger ON ciel_code = bkl_client AND bki_id = bkl_reference
        WHERE jom_id = $userId" ;

$result = mysqli_query($mysqli_link, $sql) or journalise($originalUserId, "F", "Erreur systeme a propos de l'access factures: " . mysqli_error($mysqli_link)) ;
$count = 0 ;
while ($row = mysqli_fetch_array($result)) {
	// Using the invoice date from the email import as the general ledger is in the future
	$action = "<a href=\"$row[bki_file_name]\" target=\"_blank\"><span class=\"glyphicon glyphicon-new-window\" title=\"Ouvrir la pièce dans une autre fenêtre\"></span></a>" ;
    print("<tr><td>$row[bki_date]</td><td>$row[bki_id]</td>") ;
	if ($row['bkl_debit'] != '') print("<td>Facture</td><td>$row[bkl_debit] &euro;</td><td>$action <a href=\"#\"  onClick=\"pay('facture $row[bki_id]', $row[bkl_debit]);\"><span class=\"glyphicon glyphicon-qrcode\" title=\"Payer la facture\"></span></a></td>") ;
	if ($row['bkl_credit'] != '') print("<td>Note de crédit</td><td>" . (0.0 - $row['bkl_credit']) . " &euro;</td><td>$action</td>") ;
	print("</tr>\n") ;
    $count ++ ;
}

if ($count == 0) print("<li>Hélas, pas encore de facture à votre nom dans le système.</li>\n") ;
?>
</table>
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
	var epcURI = "BCD\n001\n1\nSCT\n" + epcBic + "\n" + epcName + "\n" + epcIban + "\nEUR" + amount + "\n" + reason + " client " + compteCiel + "\n" + reason + " client " + compteCiel + '/' + userLastName ;
	document.getElementById('payment_qr_code').src = "https://chart.googleapis.com/chart?cht=qr&chs=300x300&&chl=" + encodeURI(epcURI) ;
}

</script>
<hr>
<div class="copyright">R&eacute;alisation: Eric Vyncke, 2022-2023, pour RAPCS, Royal A&eacute;ro Para Club de Spa, ASBL<br>
Version: PHP=<?=$version_php?></div>
</body>
</html>
