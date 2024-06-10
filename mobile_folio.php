<?php
/*
   Copyright 2022-2024 Eric Vyncke, Patrick Reginster

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

if (isset($_REQUEST['user']) and ($userIsAdmin or $userIsBoardMember or $userIsInstructor)) {
	if ($userId != 62) journalise($userId, "I", "Start of myfolio, setting user to $_REQUEST[user]") ;
	$userId = $_REQUEST['user'] ;
	if (! is_numeric($userId)) die("Invalid user ID") ;
}

// Check whether the user has specified a start date...
if (isset($_REQUEST['start']))
	$first_date = $_REQUEST['start'] ; // No need to escape to prevent SQL injection as it is only used in DateTime methods
else
	$first_date = date('Y-m-01') ;

$folio_start = new DateTime($first_date, new DateTimeZone('UTC')) ;
$folio_end = new DateTime($first_date, new DateTimeZone('UTC')) ;
$folio_end_title = new DateTime($first_date, new DateTimeZone('UTC')) ;
$previous_month = new DateTime($first_date, new DateTimeZone('UTC')) ;
$next_month = new DateTime($first_date, new DateTimeZone('UTC')) ;

if (isset($_REQUEST['previous'])) {
	$folio_start = new DateTime($first_date, new DateTimeZone('UTC')) ;
	$folio_start = $folio_start->sub(new DateInterval('P1M')) ;
	$folio_end = new DateTime($first_date, new DateTimeZone('UTC')) ;
	$folio_end = $folio_end->sub(new DateInterval('P1M')) ;
	$folio_end_title = new DateTime($first_date, new DateTimeZone('UTC')) ;
	$folio_end_title = $folio_end_title->sub(new DateInterval('P1M')) ;
	$next_month = new DateTime($first_date, new DateTimeZone('UTC')) ;
	$previous_month = null ;
	// Pager active ?
	$previous_active = " active" ;
	$current_active = '' ;
} else {
	$previous_month = $previous_month->sub(new DateInterval('P1M')) ;
	$next_month = null ;
	// Pager active ?
	$current_active = " active" ;
	$previous_active = '' ;
}
$this_month_pager = new DateTime($first_date, new DateTimeZone('UTC')) ;
$previous_month_pager = new DateTime($first_date, new DateTimeZone('UTC')) ;
$previous_month_pager = $previous_month_pager->sub(new DateInterval('P1M')) ;
$folio_end->add(new DateInterval('P1M'));
$folio_end_title->add(new DateInterval('P1M'));
$folio_end_title->sub(new DateInterval('P1D'));

$result = mysqli_query($mysqli_link, "SELECT * 
	FROM $table_person LEFT JOIN $table_blocked on jom_id=b_jom_id
	WHERE jom_id = $userId")
	or journalise($originalUserId, 'F', "Impossible de lire le pilote $userId: " . mysqli_error($mysqli_link)) ;
$pilot = mysqli_fetch_array($result) or journalise($originalUserId, 'F', "Pilote $userId inconnu") ;
$userName = db2web("$pilot[first_name] $pilot[last_name]") ;
$userLastName = db2web($pilot['last_name']) ;
$odooId = $pilot['odoo_id'] ;
$blocked_reason = db2web($pilot['b_reason']) ;
$blocked_when = $pilot['b_when'] ;
mysqli_free_result($result) ;

function numberFormat($n, $decimals = 2, $decimal_separator = ',', $thousand_separator = ' ', $empty_if_null = TRUE) {
	if ($n == 0) 
		return ($empty_if_null) ? '' : '0,0&nbsp;&euro;' ;
	return number_format($n, $decimals, $decimal_separator, $thousand_separator) . '&nbsp;&euro;';
}

// Is a CSV file request ?
if (isset($_REQUEST['csv']) and $_REQUEST['csv'] != '') {
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment;filename="folio-' . $folio_start->format('Y-m-d') . '.csv"');
	header('Cache-Control: max-age=0');

	print("Date;From;Start;To;End;Model;Plane;Hours;Minutes;PIC;Pax;\"Cost Sharing\";\"Plane Cost\";\"FI Cost\";\"Tax Cost\"\n") ;

	$folio = new Folio($userId, $folio_start->format('Y-m-d'), $folio_end->format('Y-m-d')) 
		or journalise($originalUserId, "F", "Cannot get access to the folio");
	foreach ($folio as $line)	{
		print("$line->date;$line->from;$line->time_start;$line->to;$line->time_end;$line->model;$line->plane;$line->duration_hh;$line->duration_mm;") ;
		if ($line->instructor_code != $userId  and  $line->is_pic) { // PIC 
			print("SELF;") ; //Pilot Point of View. A PIC-Recheck is SELF
		} else  // Dual command
			if ($userId == $line->instructor_code)
				print("\"$line->pilot_name\";") ; //Point of view of the Instructor. A PIC Recheck is a DC
			else
				print("\"$line->instructor_name\";") ;// DC 
		print("$line->pax_count;") ;
		if ($line->share_type)
			print("\"$line->share_type ($line->share_member_fname $line->share_member_name)\";") ;
		else
			print(";") ;
		print(number_format($line->cost_plane, 2, ',', '') . ";" . 
			number_format($line->cost_fi, 2, ',', '') . ";" . 
			number_format($line->cost_tax, 2, ',', '') . "\n") ;
	}
	exit ;
} // CSV output
?><script>

function valueOfField(suffix, name) {
	return name + '=' + document.getElementById(name + suffix.charAt(0).toUpperCase() + suffix.slice(1)).value ;
}

function findMember(a, m) {
        for (let i = 0 ; i < a.length ; i++)
                if (a[i].id == m)
                        return a[i].name ;
        return null ;
}

function selectChanged() {
        window.location.href = '<?=$_SERVER['PHP_SELF']?>?user=' + document.getElementById('pilotSelect').value + 
			'<?= ((isset($_REQUEST['previous'])) ? '&previous' : '')?>' ;
}
</script>
<?php

// Now, let's check whether Odoo has also a balance
if ($odooId != '') {
	require_once 'odoo.class.php' ;
	$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
	$accounts = $odooClient->SearchRead('res.partner', array(array(
				array('id', '=', intval($odooId))
			)), 
			array('fields' => array('id', 'total_due'))) ;
	$balance = -1.0 * $accounts[0]['total_due'] ;
	if ($balance < 0) {
		$balance_text = "<span class=\"text-danger\"> $balance &euro; (vous devez de l'argent au RAPCS ASBL)</span>" ;
		$invoice_total = -1.0 * $balance ;
		$invoice_reason = 'solde' ;
	} else
		$balance_text = "$balance &euro;" ;
} else { // Odoo account does not exist
	$balance = 0 ;
	$invoice_total = 0 ;
	$invoice_reason = '' ;
	journalise($userId, "E", "No Odoo id associated to $userName") ;
	print("<p class=\"text-danger\">Vous n'avez pas encore de compte dans la comptabilité.</p>\n") ;
}

// Let's warn the used if he is blocked
if ($blocked_reason != '') {
	print("<p class=\"mt-2 p-4 bg-danger text-bg-danger rounded\">$blocked_reason</p>") ;
//	if ($userIsBoardMember and $row['bkb_amount'] > 0) { // Test mode
//		print("<p>EN TEST !!!! Si vous êtes bloqué(e) pour un solde négatif ($row[bkb_amount] &euro;), vous pouvez payer ce solde via 
//			<a href=\"payconiq/pay.php?amount=$row[bkb_amount]&reference=Solde%20$codeCiel&description=Solde%20courant%20$codeCiel&cb=$_SERVER[PHP_SELF]\">
//			l'application mobile payconiq (ou votre app bancaire) <img src=\"payconiq/payconiq_by_Bancontact-logo-app-pos-shadow.png\" width=88 height=88>
//			</a> et votre compte membre sera débloqué endéans quelques secondes.</p>") ;
//	}	
}

if ($userIsInstructor or $userIsAdmin or $userIsBoardMember) {
        print("En tant qu'instructeur/administrateur, vous pouvez consulter les situations comptables des autres membres: <select id=\"pilotSelect\" onchange=\"selectChanged();\">" ) ;
        print("</select><br/><br/>") ;
} else { // ($userIsInstructor or $userIsAdmin)
        print("Folio de: <select id=\"pilotSelect\" onchange=\"selectChanged();\">
        <option value=\"$userId\" selected>$userName</option>
        </select><br/><br/>") ;
}

if ($previous_month)	
	$document_title = 'Folio (estimation de la facture provisoire)' ;
else
	$document_title = 'Reconstruction d\'une facture' ;
// Display today in the local language in human language
$fmt = datefmt_create(
    'fr_BE',
    IntlDateFormatter::FULL,
    IntlDateFormatter::FULL,
    'Europe/Brussels',
    IntlDateFormatter::GREGORIAN,
    'MMMM yyyy' // See https://unicode-org.github.io/icu/userguide/format_parse/datetime/ !
) ;
?>
<h2><?=$document_title?>  du <?=$folio_start->format('d-m-Y')?> au <?=$folio_end_title->format('d-m-Y')?></h2>
<p class="lead">Voici un folio (estimation de vos factures de vos vols). Le solde de votre compte membre est de <?=$balance?>&euro;.</p>
<p class="small">Accès aux factures et opérations comptables via les onglets ci-dessous.</p>

<!-- using tabs -->
<ul class="nav nav-tabs">
	<li class="nav-item">
  		<a class="nav-link" aria-current="page" href="mobile_ledger.php?user=<?=$userId?>">Opérations comptables</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" aria-current="page" href="<?="mobile_invoices.php?user=$userId"?>">Factures récentes</a>
	</li>
	<li class="nav-item">
		<a class="nav-link<?=$previous_active?>" aria-current="page" href="<?="$_SERVER[PHP_SELF]?previous&user=$userId"?>">Folio du mois précédent
			<br/><?=datefmt_format($fmt, $previous_month_pager)?></a>
  	</li>
	  <li class="nav-item">
		<a class="nav-link<?=$current_active?>" aria-current="page" href="<?="$_SERVER[PHP_SELF]?user=$userId"?>">Folio de ce mois
			<br/><?=datefmt_format($fmt,$this_month_pager)?></a>
  	</li>
</ul> <!-- tabs -->
<br/>

<div class="row">
<div class="col-sm-12 col-lg-10 col-xl-8">
<div class="table-responsive">
<table class="table table-hover table-bordered">
<thead>
<tr>
<th class="text-center">Date</th>
<th class="text-center" colspan="2">Departure</th>
<th class="text-center" colspan="2">Arrival</th>
<th class="text-center" colspan="2">Aircraft</th>
<th class="text-center" colspan="2">Total time</th>
<th class="text-center">Name</th>
<th class="text-center">Pax</th>
<th class="text-center">Cost</th>
<th class="text-center" colspan="4">Cost</th>
</tr>
<tr>
<th class="text-center">(dd/mm/yy)</th>
<th class="text-center">Place</th>
<th class="text-center">Time UTC</th>
<th class="text-center">Place</th>
<th class="text-center">Time UTC</th>
<th class="text-center">Model</th>
<th class="text-center">Registration</th>
<th class="text-center">hh</th>
<th class="text-center">mm</th>
<th class="text-center">PIC</th>
<th class="text-center">Number</th>
<th class="text-center">Sharing</th>
<th class="text-center">Plane</th>
<th class="text-center">FI<?=($userIsInstructor)? ' &spades' : ''?></th>
<th class="text-center">Taxes <!--&ddagger;--></th>
<th class="text-center">Total</th>
</thead>
<tbody class="table-group-divider">
<?php

if (!isset($_REQUEST['previous'])) {
	if ($balance < 0)
		$balance_class = "table-danger" ;
	else
		$balance_class = "table-success" ;
	print("<tr><td colspan=\"15\" class=\"$balance_class text-start\">Solde courant du compte membre</td><td class=\"$balance_class text-end\">" . 
		numberFormat($balance, 2, ',', ' ', FALSE) . "</td></tr>\n") ;
}

$duration_total_hour = 0 ;
$duration_total_minute = 0 ;
$cost_plane_total = 0 ;
$cost_fi_total = 0 ;
$cost_taxes_total = 0 ;
$cost_grand_total = 0 ;
$diams_explanation = false ; // Whether to display explanation about flight duration
$clubs_explanation = false ; // Whether to display explanation about flight cost
$folio = new Folio($userId, $folio_start->format('Y-m-d'), $folio_end->format('Y-m-d')) 
	or journalise($originalUserId, "F", "Cannot get access to the folio");
foreach ($folio as $line)	{
	$duration_hh = $line->duration_hh ;
	$duration_mm = $line->duration_mm ;
	$duration_total_hour += $duration_hh ;
	$duration_total_minute += $duration_mm ;
	if ($line->cost_plane_policy == 'F') {
		$diams_explanation = true ;
		$plane_token = ' &diams;' ;
	} else if ($line->cost_plane_margin > 0) {
		$clubs_explanation = true ;
		$plane_token = ' &clubs;' ;
	} else
		$plane_token = '' ;
	print("<tr>
		<td class=\"text-center\">$line->date</td>
		<td class=\"text-center\">$line->from</td>
		<td class=\"text-center\">$line->time_start</td>
		<td class=\"text-center\">$line->to</td>
		<td class=\"text-center\">$line->time_end</td>
		<td class=\"text-center\">$line->model</td>
		<td class=\"text-center\">$line->plane $plane_token</td>
		<td class=\"text-end\">$duration_hh</td>
		<td class=\"text-end\">$duration_mm</td>\n") ;

	if ($line->instructor_code != $userId  and  $line->is_pic) { // PIC 
		print("<td class=\"text-center\">SELF</td>\n") ; //Pilot Point of View. A PIC-Recheck is SELF
	} else  // Dual command
		if ($userId == $line->instructor_code)
			print("<td class=\"text-center\">$line->pilot_name</td>\n") ; //Point of view of the Instructor. A PIC Recheck is a DC
		else
			print("<td class=\"text-center\">$line->instructor_name</td>\n") ;// DC 
	print("<td class=\"text-end\">$line->pax_count</td>\n") ;
	if ($line->share_type)
		print("<td class=\"text-center\">$line->share_type ($line->share_member_fname $line->share_member_name)</td>\n") ;
	else
		print("<td class=\"text-center\"></td>\n") ;

	$cost_total = $line->cost_plane + $line->cost_fi + $line->cost_taxes ;
	// Prepare the bottom line for grand total
	$cost_plane_total += $line->cost_plane ;
	$cost_fi_total += $line->cost_fi ;
	$cost_taxes_total += $line->cost_taxes ;
	$cost_grand_total += $cost_total ;
	// Explain taxes if not zero
	$distance_msg = ($line->cost_taxes > 0) ? "<br/>(" . $line->distance_km . " km)" : '' ;
	// Let's have a nice format
	$cost_plane = numberFormat($line->cost_plane, 2, ',', ' ') ;
	$cost_fi = numberFormat($line->cost_fi, 2, ',', ' ') ;
	$cost_taxes = numberFormat($line->cost_taxes, 2, ',', ' ') ;
	$cost_total = numberFormat($cost_total, 2, ',', ' ') ;
	print("<td class=\"text-end\">$cost_plane</td>\n") ;
	print("<td class=\"text-end\">$cost_fi</td>\n") ;
	print("<td class=\"text-end\">$cost_taxes$distance_msg</td>\n") ;
	print("<td class=\"text-end text-danger\">$cost_total</td>\n") ;
	print("</tr>\n") ;
}
$duration_total_hour += floor($duration_total_minute / 60) ;
$duration_total_minute = $duration_total_minute % 60 ;
$cost_plane_total = numberFormat($cost_plane_total, 2, ',', ' ') ;
$cost_fi_total = numberFormat($cost_fi_total, 2, ',', ' ') ;
$cost_taxes_total = numberFormat($cost_taxes_total, 2, ',', ' ') ;
$invoice_total = $cost_grand_total;
$cost_grand_total_text = numberFormat($cost_grand_total, 2, ',', ' ', FALSE) ;
$final_balance_class = ($balance - $cost_grand_total >= 0) ? "table-warning" : "table-danger" ;
$final_balance_message = ($balance - $cost_grand_total >= 0) ? "" : "<br/>(vous devrez donc de l'argent au club à la prochaine facture)" ;
?>
</tbody>
<tfoot  class="table-group-divider">
<tr><td colspan="7" class="table-info">Total du folio <a href="myfolio.php?csv=true&<?=$_SERVER['QUERY_STRING']?>"><i class="bi bi-filetype-csv" title="Télécharger au format CSV"></i></a></td>
<td class="table-info text-end"><?=$duration_total_hour?></td>
<td class="table-info text-end"><?=$duration_total_minute?></td>
<td class="table-info" colspan="3"></td>
<td class="table-info text-end"><?=$cost_plane_total?></td>
<td class="table-info text-end"><?=$cost_fi_total?></td>
<td class="table-info text-end"><?=$cost_taxes_total?></td>
<td class="table-info text-end text-danger"><?=$cost_grand_total_text?></td>
</tr>
<?php
if (!isset($_REQUEST['previous'])) {
?>
<tr><td colspan="15" class="<?=$final_balance_class?>">Solde du compte membre en tenant compte de ce folio<?=$final_balance_message?></td>
<td class="<?=$final_balance_class?> text-end"> <?= numberFormat($balance - $cost_grand_total, 2, ',' , ' ', FALSE)?></td>
</tr>
<?php
}
?>
</tfoot>
</table>
</div><!-- table responsive -->
</div><!-- col -->
</div><!-- row -->
<p>Sur base des données que vous avez entrées après les vols dans le
carnet de routes des avions et en utilisant le prix des avions/instructeurs/taxes d'aujourd'hui (<?=date('D, j-m-Y H:i e')?>),
donc il peut y avoir une différence si les prix par minute ont changé depuis vos vols.
Le montant n'inclut aucune note de frais (par exemple carburant), note de crédit, ainsi que d'autres frais (par exemple, cotisations, ou taxes d'atterrissage).</p>
<p>Les heures sont les heures UTC. </p>
<p>Ces informations sont mises à jour environ une fois par semaine par nos bénévoles.
</p >
<!-- p>&ddagger;: depuis le 1er novembre 2022, le CA a décidé de ne plus faire payer les taxes en avance.</p-->
<?php

if ($userIsInstructor)
	print("<p>&spades; Veuillez noter qu'en tant qu'instructeur les montants négatifs de la colonne FI sont en fait des montants à facturer au club.</p>") ;

if ($clubs_explanation)
	print("<p><mark>&clubs;: pour cet avion, la facture sur fait sur l'index moteur mais avec une déduction de plusieurs minutes, la durée indiquée est donc la durée facturée et pas celle du carnet de routes.</mark></p>") ;
if ($diams_explanation)
	print("<p><mark>&diams;: pour cet avion, la facture se fait sur le temps de vol et pas l'index moteur.</mark></p>") ;
?>

<?php
if (isset($_REQUEST['previous']))  {
	$invoice_reason = 'folio de ' . datefmt_format($fmt, $previous_month_pager) ;
	$invoice_total = round($cost_grand_total, 2)  ;
	
} else {
	$invoice_reason = 'solde selon folio' ;
	$invoice_total = round($cost_grand_total - $balance, 2) ;
	
}

/*
as Google Charts API is about to be deprecated, alternatives could be:
http://image-charts.com/
https://github.com/typpo/quickchart
*/
?>
<span id="payment">
<h3>QR-code pour payer <span id="payment_reason"></span> de <span id="payment_amount"></span> &euro;</h3>
<p>Le QR-code est à utiliser avec une application bancaire
et pas encore Payconiq (ce dernier étant payant pour le commerçant).</p>
<img id="payment_qr_code" width="200" height="200" src="qr-code.php?chs=200x200&&chl=<?=urlencode($epcString)?>">
</span id="payment">
<script>
var 
	invoice_reason = '<?=$invoice_reason?>' ;
	invoice_total = <?=$invoice_total?> ;
	epcBic = '<?=$bic?>' ;
	epcName = '<?=$bank_account_name?>' ;
	epcIban = '<?=$iban?>' ;
	userLastName = '<?=$userLastName?>' ;

function pay(reason, amount) {
	if (amount <= 0.0 || amount <= 0) {
		document.getElementById('payment').style.display = 'none' ;
		return ;
	}
	document.getElementById('payment').style.display = 'block' ;
	document.getElementById('payment_reason').innerText = reason ;
	document.getElementById('payment_amount').innerText = amount ;
	// Should update to version 002 (rather than 001), https://www.europeanpaymentscouncil.eu/document-library/guidance-documents/quick-response-code-guidelines-enable-data-capture-initiation
	// There should be 2 reasons, first one is structured, the second one is free text
	var epcPurpose = '' ; // No clue what to put in this 4-char field
	var epcURI = "BCD\n001\n1\nSCT\n" + epcBic + "\n" + epcName + "\n" + epcIban + "\nEUR" + amount + "\n" + epcPurpose + "\n" + "\n" + reason + " " + userLastName + "\n";
	document.getElementById('payment_qr_code').src = "qr-code.php?chs=200x200&&chl=" + encodeURI(epcURI) ;
}

pay(invoice_reason, invoice_total) ;
</script>
</div><!-- container fluid-->
</body>
</html>
