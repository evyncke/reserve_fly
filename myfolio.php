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

if ($userId <= 0) die("Vous devez être connecté") ;

if (isset($_REQUEST['user']) and ($userIsAdmin or $userIsBoardMember)) {
	if ($userId != 62) journalise($userId, "I", "Start of folio, setting user to $_REQUEST[user]") ;
	$userId = $_REQUEST['user'] ;
	if (! is_numeric($userId)) die("Invalid user ID") ;
} else
	if ($userId != 62) journalise($userId, "I", "Start of folio") ;

if (isset($_REQUEST['start']))  {
	$folio_start = new DateTime($_REQUEST['start'], new DateTimeZone('UTC')) ;
	$previous_month = new DateTime($_REQUEST['start'], new DateTimeZone('UTC')) ;
} else {
	$folio_start = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
	$previous_month = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
}
$previous_month = $previous_month->sub(new DateInterval('P1M')) ;

$result = mysqli_query($mysqli_link, "SELECT * FROM $table_person WHERE jom_id = $userId")
	or die("Impossible de lire le pilote $userId: " . mysqli_error($mysqli_link)) ;
$pilot = mysqli_fetch_array($result) or die("Pilote $userId inconnu") ;
$userName = db2web("$pilot[first_name] $pilot[last_name]") ;
$codeCiel = $pilot['ciel_code'] ;
mysqli_free_result($result) ;

$cost_fi_minute = 0.83 ;
$revenue_fi_minute = 0.75 ;
$revenue_fi_initiation = 30.0 ; // Initiation flight when shareCode = -3
$code_initiation = -3 ; // Hard coded :-(
$tax_per_pax = 10.0 ;

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
<th class="logLastHeader">FI</th>
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
  _paq.push(['setCustomVariable', 1, "userID", <?=$userId?>, "visit"]);
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
<center><h2>Folio (estimation de la facture provisoire du <?=$folio_start->format('d-m-Y')?> au <?=date('d-m-Y')?>) pour le membre <?=$userId?> <?=$userName?></h2></center>
Folio du mois <a href="<?=$_SERVER['PHP_SELF']?>?start=<?=$previous_month->format('Y-m-d')?>&user=<?=$userId?>">précédent.</a>
<?php
$sql = "SELECT l_id, date_format(l_start, '%d/%m/%y') AS date,
	l_model, l_plane, compteur_vol, l_pilot, l_is_pic, l_instructor, l_instructor_paid, i.last_name as instructor_name, p.last_name as pilot_name,
	UPPER(l_from) as l_from, UPPER(l_to) as l_to, 
	l_start, l_end, 60 * (l_end_hour - l_start_hour) + l_end_minute - l_start_minute as duration,
	60 * (l_flight_end_hour - l_flight_start_hour) + l_flight_end_minute - l_flight_start_minute as flight_duration,
	l_share_type, l_share_member, cout, l_pax_count
	FROM $table_logbook l JOIN $table_planes AS a ON l_plane = a.id
	LEFT JOIN $table_person p ON p.jom_id = l_pilot
	LEFT JOIN $table_person i ON i.jom_id = l_instructor
	WHERE (l_pilot = $userId OR l_share_member = $userId or l_instructor = $userId)
		AND l_start >= '" . $folio_start->format('Y-m-d') . "'
	ORDER by l.l_start ASC" ;

$result = mysqli_query($mysqli_link, $sql) or die("Erreur systeme a propos de l'access au carnet de route: " . mysqli_error($mysqli_link)) ;

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
while ($row = mysqli_fetch_array($result)) {
	// On which index is the invoice based ?
	$duration = ($row['compteur_vol']) ? $row['flight_duration'] : $row['duration'] ;
	$duration_hh = floor($duration / 60) ;
	$duration_mm = $duration % 60 ;
	$duration_total_hour += $duration_hh ;
	$duration_total_minute += $duration_mm ;
	if ($row['compteur_vol']) {
		$diams_explanation = true ;
		$plane_token = ' &diams;' ;
	} else
		$plane_token = '' ;
	// DB contains UTC time
	$time_start = gmdate('H:i', strtotime("$row[l_start] UTC")) ;
	$time_end = gmdate('H:i', strtotime("$row[l_end] UTC")) ;
	if ($row['l_instructor'] < 0) $row['instructor_name'] = 'Autre FI' ;
	print("<tr>
		<td class=\"logCell\">$row[date]</td>
		<td class=\"logCell\">$row[l_from]</td>
		<td class=\"logCell\">$time_start</td>
		<td class=\"logCell\">$row[l_to]</td>
		<td class=\"logCell\">$time_end</td>
		<td class=\"logCell\">$row[l_model]</td>
		<td class=\"logCell\">$row[l_plane]$plane_token</td>
		<td class=\"logCell\">$duration_hh</td>
		<td class=\"logCell\">$duration_mm</td>\n") ;
	if ($row['l_instructor'] == $userId and $row['l_pilot'] != $userId and $row['l_share_member'] != $userId) // FI only pays the plane rental when they are the pilot
		$cost_plane = 0 ;
	else
		if ($row['l_share_type'] == 'CP2') {
			$cost_plane = round($row['cout'] * 0.5, 2) * $duration ;
		} else if ($row['l_share_type'] == 'CP1' and $row['l_share_member'] != $userId) {
			$cost_plane = 0 ;
		} else
			$cost_plane = $row['cout'] * $duration ;	

	if ($row['l_instructor'] == '' or $row['l_is_pic']) { // Solo, no instructor
		print("<td class=\"logCell\">SELF</td>\n") ;
	} else  // Dual command
		if ($userId == $row['l_instructor'])
			print("<td class=\"logCell\">" . db2web($row['pilot_name']) . "</td>\n") ;
		else
			print("<td class=\"logCell\">" . db2web($row['instructor_name']) . "</td>\n") ;
	if ($row['l_instructor'])
		if ($row['l_instructor'] != $userId) // The user is not the FI
			$cost_fi = $row['l_instructor_paid'] * $cost_fi_minute * $duration ;
		else
			$cost_fi = - $row['l_instructor_paid'] * $revenue_fi_minute * $duration ;
	else
		$cost_fi = 0 ;
	// Initiation flights
	if ($row['l_share_type'] == 'CP1' and $row['l_share_member'] == $code_initiation)
		$cost_fi -= $revenue_fi_initiation ;
	// Flights taking off Belgium have to pay taxes (distance depending but ignored for now)
	// Except Local flight
	// And only the pilot pays the taxes
	if (stripos($row['l_from'], 'EB') === 0 and $row['l_from'] != $row['l_to'] and $row['l_pilot'] == $userId) {
		$cost_taxes = $tax_per_pax * $row['l_pax_count'] ;
	} else {
		$cost_taxes = 0 ;
	}
	// From 2022-11-01 no more taxes, actually they are back !
	// if ($row['l_start'] >= '2022-11-01')
	//	$cost_taxes = 0 ;
	print("<td class=\"logCell\">$row[l_pax_count]</td>\n") ;
	if ($row['l_share_type'])
		print("<td class=\"logCell\">$row[l_share_type] <span class=\"shareCodeClass\">$row[l_share_member]</span></td>\n") ;
	else
		print("<td class=\"logCell\"></td>\n") ;

	$cost_total = $cost_plane + $cost_fi + $cost_taxes ;
	// Prepare the bottom line for grand total
	$cost_plane_total += $cost_plane ;
	$cost_fi_total += $cost_fi ;
	$cost_taxes_total += $cost_taxes ;
	$cost_grand_total += $cost_total ;
	// Let's have a nice format
	$cost_plane = numberFormat($cost_plane, 2, ',', ' ') ;
	$cost_fi = numberFormat($cost_fi, 2, ',', ' ') ;
	$cost_taxes = numberFormat($cost_taxes, 2, ',', ' ') ;
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
if ($diams_explanation)
	print("<p>&diams;: pour cet avion, la facture se fait sur le temps de vol et pas l'index moteur.</p>") ;

$invoice_reason = 'folio' ;
// Check the bookkeeping balance

$result = mysqli_query($mysqli_link, "SELECT *
		FROM $table_bk_balance JOIN $table_person ON bkb_account = CONCAT('400', ciel_code)
		WHERE jom_id = $userId
		ORDER BY bkb_date DESC
		LIMIT 0,1")
	or die("Cannot read booking keeping balance: " . mysqli_error($mysqli_link)) ;
$row = mysqli_fetch_array($result) ;
if ($row) {
	print("<p>Le solde de votre compte en date du $row[bkb_date] est de $row[bkb_amount] &euro; (si positif vous devez de l'argent au RAPCS ASBL). Il faut plusieurs jours avant que vos paiements soient pris en compte.</p>") ;
	if ($row['bkb_amount'] > 0) {
		$invoice_total = $row['bkb_amount'] ; // Only for positive balance of course
		$invoice_reason = 'solde' ;
	}
} else 
	print("<p>Le solde de votre compte n'est pas disponible.</p>") ;
?>
<h2>Factures r&eacute;centes</h2>
<p><b>De manière expérimentale</b>, voici quelques pièces comptables r&eacute;centes:
<ul>
<?php
$sql = "SELECT * FROM $table_person JOIN $table_bk_invoices ON bki_email = email LEFT JOIN $table_bk_ledger ON ciel_code = bkl_client AND bki_id = bkl_reference
        WHERE jom_id = $userId" ;

$result = mysqli_query($mysqli_link, $sql) or die("Erreur systeme a propos de l'access factures: " . mysqli_error($mysqli_link)) ;
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

<h2>Détails de votre compte pour l'année en cours</h2>
<p><b>De manière expérimentale</b>, voici une vue de votre compte membre RAPCS pour l'année en cours (mise à jour de temps en temps).</p>
<table border=1>
<thead>
<th>Date</th><th>Opération</th><th>Pièce</th><th>Description</th><th>Débit</th><th>Crédit</th>
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
	if ($row['bkl_debit']) $total_debit += $row['bkl_debit'] ;
	if ($row['bkl_credit']) $total_credit += $row['bkl_credit'] ;
	print("<tr><td>$row[bkl_date]</td><td>$journal</td><td>$reference</td><td>$row[bkl_label]</td><td>$row[bkl_debit]</td><td>$row[bkl_credit]</td></tr>\n") ;
}
?>
</tbody>
<tfoot>
<tr><td colspan=4>Totaux</td><td><?=$total_debit?>&nbsp;&euro;</td><td><?=$total_credit?>&nbsp;&euro;</td><tr>
</tfoot>
</table>
<?php
$version_php = date ("Y-m-d H:i:s.", filemtime('myfolio.php')) ;
$version_css = date ("Y-m-d H:i:s.", filemtime('log.css')) ;

$iban = "BE64732038421852" ;
$bic = "CREGBEBB" ;
$name = "Royal Aero Para Club Spa" ;
?>
<span id="payment">
<h2>Test QR-code pour payer <span id="payment_reason"></span> de <span id="payment_amount"></span> &euro;</h3>
<p>Ceci est simplement un test pour les informaticiens, ne pas l'utiliser car notre trésorier ne saura pas comment faire pour
associer ce virement à votre compte membre RAPCS (<em><?=$codeCiel?></em>). Le QR-code est à utiliser avec une application bancaire
et pas Payconiq (ce dernier étant payant).</p>
<img id="payment_qr_code" width="400" height="400" src="https://chart.googleapis.com/chart?cht=qr&chs=400x400&&chl=<?=urlencode($epcString)?>">
</span id="payment">
<hr>
<div class="copyright">R&eacute;alisation: Eric Vyncke, août-septembre 2022, pour RAPCS, Royal A&eacute;ro Para Club de Spa, ASBL<br>
Versions: PHP=<?=$version_php?>, CSS=<?=$version_css?></div>
<script>
var 
	invoice_reason = '<?=$invoice_reason?>' ;
	invoice_total = <?=$invoice_total?> ;
	epcBic = '<?=$bic?>' ;
	epcName = '<?=$name?>' ;
	epcIban = '<?=$iban?>' ;
	compteCiel = '400<?=$codeCiel?>' ;

function pay(reason, amount) {
	if (amount <= 0.0 || amount <= 0) {
		document.getElementById('payment').style.display = 'none' ;
		return ;
	}
	document.getElementById('payment').style.display = 'block' ;
	document.getElementById('payment_reason').innerText = reason ;
	document.getElementById('payment_amount').innerText = amount ;
	// Should uptdate to version 002 (rather than 001), https://www.europeanpaymentscouncil.eu/document-library/guidance-documents/quick-response-code-guidelines-enable-data-capture-initiation
	// There should be 2 reasons, first one is structued, the second one is free text
	var epcURI = "BCD\n001\n1\nSCT\n" + epcBic + "\n" + epcName + "\n" + epcIban + "\nEUR" + amount + "\n" + reason + " client " + compteCiel + "\n" + reason + " client " + compteCiel ;
	document.getElementById('payment_qr_code').src = "https://chart.googleapis.com/chart?cht=qr&chs=400x400&&chl=" + encodeURI(epcURI) ;
}

pay(invoice_reason, invoice_total) ;
</script>

</body>
</html>
