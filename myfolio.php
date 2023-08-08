<?php
/*
   Copyright 2022-2022 Eric Vyncke, Patrick Reginster

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
require_once "folio.php" ;

MustBeLoggedIn() ;

$originalUserId = $userId ;

if (isset($_REQUEST['user']) and ($userIsAdmin or $userIsBoardMember)) {
	if ($userId != 62) journalise($userId, "I", "Start of folio, setting user to $_REQUEST[user]") ;
	$userId = $_REQUEST['user'] ;
	if (! is_numeric($userId)) die("Invalid user ID") ;
} else
	if ($userId != 62) journalise($userId, "I", "Start of folio") ;

if (isset($_REQUEST['start']))  {
	$folio_start = new DateTime($_REQUEST['start'], new DateTimeZone('UTC')) ;
	$folio_end = new DateTime($_REQUEST['start'], new DateTimeZone('UTC')) ;
	$folio_end_title = new DateTime($_REQUEST['start'], new DateTimeZone('UTC')) ;
	$previous_month = new DateTime($_REQUEST['start'], new DateTimeZone('UTC')) ;
	$next_month = new DateTime($_REQUEST['start'], new DateTimeZone('UTC')) ;
} else {
	$folio_start = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
	$folio_end = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
	$folio_end_title = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
	$previous_month = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
	$next_month = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
}
$folio_end->add(new DateInterval('P1M'));
//$folio_end->sub(new DateInterval('P1D'));
$folio_end_title->add(new DateInterval('P1M'));
$folio_end_title->sub(new DateInterval('P1D'));
$previous_month = $previous_month->sub(new DateInterval('P1M')) ;
$next_month = $next_month->add(new DateInterval('P1M')) ;

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

function ShowTableHeader() {
?>
<thead>
<tr>
<th class="logHeader">Date</th>
<th class="logHeader" colspan="2">Departure</th>
<th class="logHeader" colspan="2">Arrival</th>
<th class="logHeader" colspan="2">Aircraft</th>
<th class="logHeader" colspan="2">Total time</th>
<th class="logHeader">Name</th>
<th class="logHeader">Pax</th>
<th class="logHeader">Cost</th>
<th class="logHeader" colspan="4">Cost</th>
</tr>
<tr>
<th class="logLastHeader">(dd/mm/yy)</th>
<th class="logLastHeader">Place</th>
<th class="logLastHeader">Time UTC</th>
<th class="logLastHeader">Place</th>
<th class="logLastHeader">Time UTC</th>
<th class="logLastHeader">Model</th>
<th class="logLastHeader">Registration</th>
<th class="logLastHeader">hh</th>
<th class="logLastHeader">mm</th>
<th class="logLastHeader">PIC</th>
<th class="logLastHeader">Number</th>
<th class="logLastHeader">Sharing</th>
<th class="logLastHeader">Plane</th>
<th class="logLastHeader">FI<?=($userIsInstructor)? ' &spades' : ''?></th>
<th class="logLastHeader">Taxes <!--&ddagger;--></th>
<th class="logLastHeader">Total</th>
</thead>
<?php
}
?><html>
<head>
<link rel="stylesheet" type="text/css" href="log.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<title>Folio</title>
<script src="members.js"></script>
<script src="shareCodes.js"></script>
<script>
var
	// preset Javascript constants filled with the right data from db.php PHP variables
	userFullName = '<?=$userFullName?>' ;
	userName = '<?=$userName?>' ;
	userId = <?=$userId?> ;
        userIsAdmin = <?=($userIsAdmin)? 'true' : 'false'?> ;
        userIsInstructor = <?=($userIsInstructor)? 'true' : 'false'?> ;

function valueOfField(suffix, name) {
	return name + '=' + document.getElementById(name + suffix.charAt(0).toUpperCase() + suffix.slice(1)).value ;
}

function findMember(a, m) {
        for (let i = 0 ; i < a.length ; i++)
                if (a[i].id == m)
                        return a[i].name ;
        return null ;
}

function init() {
        var pilotSelect = document.getElementById('pilotSelect') ;
	// Add names for shared cost members
        var collection = document.getElementsByClassName("shareCodeClass") ;
        for (let i = 0; i < collection.length ; i++) {
                var spanElem = collection[i] ;
                var member = spanElem.innerText ;
                memberText = findMember(shareCodes, member) ;
                if (memberText == null)
                        memberText = findMember(members, member) ;
                if (memberText != null)
                        spanElem.innerText = ' (' + memberText + ')';
        }
	// Dropdown selected the pilot
        if (userIsInstructor || userIsAdmin) {
                // Initiliaze pilotSelect from members.js
               for (var member = 0; member < members.length; member++) {
                        var option = document.createElement("option");
                        if (members[member].last_name == '')
                                option.innerHTML = members[member].name ;
                        else
                                option.innerHTML = members[member].last_name + ', ' + members[member].first_name ;
                        if (members[member].student) {  // Add a student icon
                                option.innerHTML += ' &#x1f4da;' ;
                        }
                        option.value = members[member].id ;
                        document.getElementById('pilotSelect').add(option) ;
                }
        }
        if (pilotSelect) pilotSelect.value = <?=$userId?> ;
}

function selectChanged() {
        window.location.href = '<?=$_SERVER['PHP_SELF']?>?user=' + document.getElementById('pilotSelect').value ;
}
</script>
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
<body onload="init();">
<center><h2>Folio (estimation de la facture provisoire du <?=$folio_start->format('d-m-Y')?> au <?=$folio_end_title->format('d-m-Y')?>) pour le membre <?=$userId?> <?=$userName?></h2></center>
Folio du mois <a href="<?=$_SERVER['PHP_SELF']?>?start=<?=$previous_month->format('Y-m-d')?>&user=<?=$userId?>">précédent</a> <a href="<?=$_SERVER['PHP_SELF']?>?start=<?=$next_month->format('Y-m-d')?>&user=<?=$userId?>">suivant.</a>
<?php
$folio = new Folio($userId, $folio_start->format('Y-m-d'), $folio_end->format('Y-m-d')) 
	or journalise($originalUserId, "F", "Cannot get access to the folio");

if ($userIsInstructor or $userIsAdmin) {
        print("En tant qu'instructeur/administrateur, vous pouvez consulter les folios des autres pilotes: <select id=\"pilotSelect\" onchange=\"selectChanged();\">" ) ;
        print("</select><br/><br/>") ;
} else { // ($userIsInstructor or $userIsAdmin)
        print("Folio de: <select id=\"pilotSelect\" onchange=\"selectChanged();\">
        <option value=\"$userId\" selected>$userName</option>
        </select><br/><br/>") ;
}

print("<table class=\"logTable\">\n") ;
ShowTableHeader() ;
print("<tbody>\n") ;

$duration_total_hour = 0 ;
$duration_total_minute = 0 ;
$cost_plane_total = 0 ;
$cost_fi_total = 0 ;
$cost_taxes_total = 0 ;
$cost_grand_total = 0 ;
$diams_explanation = false ; // Whether to display explanation about flight duration
foreach ($folio as $line)	{
	$duration_hh = $line->duration_hh ;
	$duration_mm = $line->duration_mm ;
	$duration_total_hour += $duration_hh ;
	$duration_total_minute += $duration_mm ;
	if ($line->compteur_vol) {
		$diams_explanation = true ;
		$plane_token = ' &diams;' ;
	} else
		$plane_token = '' ;
	// DB contains UTC time
	$time_start = gmdate('H:i', strtotime("$line->l_start UTC")) ;
	$time_end = gmdate('H:i', strtotime("$line->l_end UTC")) ;
	print("<tr>
		<td class=\"logCell\">$line->date</td>
		<td class=\"logCell\">$line->from</td>
		<td class=\"logCell\">$time_start</td>
		<td class=\"logCell\">$line->to</td>
		<td class=\"logCell\">$time_end</td>
		<td class=\"logCell\">$line->model</td>
		<td class=\"logCell\">$line->plane $plane_token</td>
		<td class=\"logCell\">$duration_hh</td>
		<td class=\"logCell\">$duration_mm</td>\n") ;

	if ($line->instructor_code != $userId  and  $line->is_pic) { // PIC 
		print("<td class=\"logCell\">SELF</td>\n") ; //Pilot Point of View. A PIC-Recheck is SELF
	}
	else  // Dual command
		if ($userId == $line->instructor_name)
			print("<td class=\"logCell\">$line->pilot_name</td>\n") ; //Point of view of the Instructore. A PIC Recheck is a DC
		else
			print("<td class=\"logCell\">$line->instructor_name</td>\n") ;// DC 
	print("<td class=\"logCell\">$line->pax_count</td>\n") ;
	if ($line->share_type)
		print("<td class=\"logCell\">$line->share_type <span class=\"shareCodeClass\">$line->share_member</span></td>\n") ;
	else
		print("<td class=\"logCell\"></td>\n") ;

	$cost_total = $line->cost_plane + $line->cost_fi + $line->cost_taxes ;
	// Prepare the bottom line for grand total
	$cost_plane_total += $line->cost_plane ;
	$cost_fi_total += $line->cost_fi ;
	$cost_taxes_total += $line->cost_taxes ;
	$cost_grand_total += $cost_total ;
	// Let's have a nice format
	$cost_plane = numberFormat($line->cost_plane, 2, ',', ' ') ;
	$cost_fi = numberFormat($line->cost_fi, 2, ',', ' ') ;
	$cost_taxes = numberFormat($line->cost_taxes, 2, ',', ' ') ;
	$cost_total = numberFormat($cost_total, 2, ',', ' ') ;
	print("<td class=\"logCellRight\">$cost_plane</td>\n") ;
	print("<td class=\"logCellRight\">$cost_fi</td>\n") ;
	print("<td class=\"logCellRight\">$cost_taxes</td>\n") ;
	print("<td class=\"logCellRight\">$cost_total</td>\n") ;
	print("</tr>\n") ;
}
$duration_total_hour += floor($duration_total_minute / 60) ;
$duration_total_minute = $duration_total_minute % 60 ;
$cost_plane_total = numberFormat($cost_plane_total, 2, ',', ' ') ;
$cost_fi_total = numberFormat($cost_fi_total, 2, ',', ' ') ;
$cost_taxes_total = numberFormat($cost_taxes_total, 2, ',', ' ') ;
$invoice_total = $cost_grand_total;
$cost_grand_total = numberFormat($cost_grand_total, 2, ',', ' ') ;
?>
<tr><td colspan="7" class="logTotal">Total</td>
<td class="logTotal"><?=$duration_total_hour?></td>
<td class="logTotal"><?=$duration_total_minute?></td>
<td class="logTotal" colspan="3"></td>
<td class="logTotalRight"><?=$cost_plane_total?></td>
<td class="logTotalRight"><?=$cost_fi_total?></td>
<td class="logTotalRight"><?=$cost_taxes_total?></td>
<td class="logTotalRight"><?=$cost_grand_total?></td>
</tr>
</tbody>
</table>
<p>
<div style="border-style: inset;background-color: AntiqueWhite;">
Sur base des donn&eacute;es que vous avez entr&eacute;es apr&egrave;s les vols dans le
carnet de route des avions et en utilisant le prix des avions/instructeurs/taxes d'aujourd'hui (<?=date('D, j-m-Y H:i e')?>).
Le montant n'inclut aucune note de frais (par exemple carburant), note de crédit, ainsi que d'autres frais (par exemple, cotisations, ou taxes d'atterrissage).
Les heures sont les heures UTC.</div>
</p >
<!-- p>&ddagger;: depuis le 1er novembre 2022, le CA a décidé de ne plus faire payer les taxes en avance.</p-->
<?php

if ($userIsInstructor)
	print("<p>&spades; Veuillez noter qu'en tant qu'instructeur les montants négatifs de la colonne FI sont en fait des montants à facturer au club.</p>") ;

if ($diams_explanation)
	print("<p>&diams;: pour cet avion, la facture se fait sur le temps de vol et pas l'index moteur.</p>") ;

$invoice_reason = 'folio' ;
// Check the bookkeeping balance

$result = mysqli_query($mysqli_link, "SELECT *
		FROM $table_bk_balance JOIN $table_person ON bkb_account = CONCAT('400', ciel_code)
		WHERE jom_id = $userId
		ORDER BY bkb_date DESC
		LIMIT 0,1")
	or journalise($originalUserId, "F", "Cannot read booking keeping balance: " . mysqli_error($mysqli_link)) ;
$row = mysqli_fetch_array($result) ;
if ($row) {
	$balance = -1 * $row['bkb_amount'] ;
	if ($balance < 0)
		$balance = '<span style="color: red;">' . $balance . '</span>' ;
	print("<p>Le solde de votre compte en date du $row[bkb_date] est de $balance &euro; (si négatif vous devez de l'argent au RAPCS ASBL).</br>Il faut plusieurs jours avant que vos paiements soient pris en compte.</p>") ;
	if ($row['bkb_amount'] > 0) {
		$invoice_total = $row['bkb_amount'] ; // Only for positive balance of course
		$invoice_reason = 'solde' ;
	}
} else 
	print("<p>Le solde de votre compte n'est pas disponible.</p>") ;
?>
<h2>Factures r&eacute;centes</h2>
<p><b>Voici quelques pièces comptables r&eacute;centes:
<ul>
<?php
$sql = "SELECT * FROM $table_person JOIN $table_bk_invoices ON bki_email = email LEFT JOIN $table_bk_ledger ON ciel_code = bkl_client AND bki_id = bkl_reference
        WHERE jom_id = $userId" ;

$result = mysqli_query($mysqli_link, $sql) or journalise($originalUserId, "F", "Erreur systeme a propos de l'access factures: " . mysqli_error($mysqli_link)) ;
$count = 0 ;
while ($row = mysqli_fetch_array($result)) {
	// Using the invoice date from the email import as the general ledger is in the future
    print("<li><a href=\"$row[bki_file_name]\" target=\"_blank\">$row[bki_date] #$row[bki_id] &boxbox;</a>") ;
	if ($row['bkl_debit'] != '') print(" facture pour un montant de $row[bkl_debit] &euro; <button onClick=\"pay('Facture $row[bki_id]', $row[bkl_debit]);\">Payer par QR-code</button>") ;
	if ($row['bkl_credit'] != '') print(" note de crédit pour un montant de " . (0.0 - $row['bkl_credit']) . " &euro;") ;
	print("</li>\n") ;
    $count ++ ;
}

if ($count == 0) print("<li>Hélas, pas encore de facture à votre nom dans le système.</li>\n") ;

print("</ul>\n</p>\n") ;
?>
<?php
$version_php = date ("Y-m-d H:i:s.", filemtime('myfolio.php')) ;
$version_css = date ("Y-m-d H:i:s.", filemtime('log.css')) ;

// Banque de la poste
$iban = "BE14000078161283" ;
$bic = "BPOTBEB1" ;
// CBC
$iban = "BE64732038421852" ;
$bic = "CREGBEBB" ;

$name = "Royal Aero Para Club Spa" ;

/*
as Google Charts API is about to be deprecated, alternatives could be:
http://image-charts.com/
https://github.com/typpo/quickchart
*/
?>
<span id="payment">
<h2>QR-code pour payer <span id="payment_reason"></span> de <span id="payment_amount"></span> &euro;</h3>
<p>Le QR-code contient votre identifiant au niveau de la comptabilité
RAPCS (<em><?=$codeCiel?></em>). Le QR-code est à utiliser avec une application bancaire
et pas Payconiq (ce dernier étant payant pour le commerçant).</p>
<img id="payment_qr_code" width="200" height="200" src="https://chart.googleapis.com/chart?cht=qr&chs=300x300&&chl=<?=urlencode($epcString)?>">
</span id="payment">
<script>
var 
	invoice_reason = '<?=$invoice_reason?>' ;
	invoice_total = <?=$invoice_total?> ;
	epcBic = '<?=$bic?>' ;
	epcName = '<?=$name?>' ;
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

pay(invoice_reason, invoice_total) ;
</script>
<hr>
<h2>Détails de votre compte pour l'année en cours</h2>
<p><b>Voici une vue de votre compte membre RAPCS depuis le 01/01/2022 (mise à jour chaque semaine).</p>
<table border="1">
<thead>
<th>Date</th><th>Opération</th><th>Pièce</th><th>Description</th><th>Débit</th><th>Crédit</th><th>Solde</th>
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
		$reference = '<a href="' . $row['bki_file_name'] . '" target="_blank">' . $row['bki_id'] . " &boxbox;</a>" ;
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
	//	print("<tr><td>$row[bkl_date]</td><td>$journal</td><td>$reference</td><td>" . db2web($row['bkl_label']) . "</td><td>-$row[bkl_debit]</td><td>$row[bkl_credit]</td><td style=\"text-align: right;\">$solde&nbsp;&euro;</td></tr>\n") ;
}
?>
</tbody>
<tfoot>
	<?php
	$total_debit=-$total_debit;
	print("<tr><td colspan=4>Totaux</td><td>$total_debit &euro;</td><td>$total_credit&nbsp;&euro;</td><td style=\"text-align: right;\">$solde&nbsp;&euro;</td><tr>");
	?>
</tfoot>
</table>
<hr>
<div class="copyright">R&eacute;alisation: Eric Vyncke, août-septembre 2022, pour RAPCS, Royal A&eacute;ro Para Club de Spa, ASBL<br>
Versions: PHP=<?=$version_php?>, CSS=<?=$version_css?></div>
</body>
</html>
