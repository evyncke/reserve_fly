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
require_once "folio.php" ;

MustBeLoggedIn() ;

$originalUserId = $userId ;

if (isset($_REQUEST['user']) and ($userIsAdmin or $userIsBoardMember)) {
	if ($userId != 62) journalise($userId, "I", "Start of myfolio, setting user to $_REQUEST[user]") ;
	$userId = $_REQUEST['user'] ;
	if (! is_numeric($userId)) die("Invalid user ID") ;
} else
	if ($userId != 62) journalise($userId, "I", "Start of myfolio") ;

$folio_start = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
$folio_end = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
$folio_end_title = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
$previous_month = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
$next_month = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;

if (isset($_REQUEST['previous'])) {
	$folio_start = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
	$folio_start = $folio_start->sub(new DateInterval('P1M')) ;
	$folio_end = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
	$folio_end = $folio_end->sub(new DateInterval('P1M')) ;
	$folio_end_title = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
	$folio_end_title = $folio_end_title->sub(new DateInterval('P1M')) ;
	$next_month = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
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
$this_month_pager = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
$previous_month_pager = new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
$previous_month_pager = $previous_month_pager->sub(new DateInterval('P1M')) ;
$folio_end->add(new DateInterval('P1M'));
$folio_end_title->add(new DateInterval('P1M'));
$folio_end_title->sub(new DateInterval('P1D'));

$result = mysqli_query($mysqli_link, "SELECT * FROM $table_person WHERE jom_id = $userId")
	or journalise($originalUserId, 'F', "Impossible de lire le pilote $userId: " . mysqli_error($mysqli_link)) ;
$pilot = mysqli_fetch_array($result) or journalise($originalUserId, 'F', "Pilote $userId inconnu") ;
$userName = db2web("$pilot[first_name] $pilot[last_name]") ;
$userLastName = substr(db2web($pilot['last_name']), 0, 5) ;
$codeCiel = $pilot['ciel_code'] ;
mysqli_free_result($result) ;

function numberFormat($n, $decimals = 2, $decimal_separator = ',', $thousand_separator = ' ') {
	if ($n == 0) return '' ;
	return number_format($n, $decimals, $decimal_separator, $thousand_separator) . '&nbsp;&euro;';
}
?><!doctype html><html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- http://www.alsacreations.com/article/lire/1490-comprendre-le-viewport-dans-le-web-mobile.html -->
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<!-- Using latest bootstrap 5 -->
<!-- Latest compiled and minified CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Latest compiled JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<title>Situation comptable de <?=$userName?> (#<?=$userId?>)</title>
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
<body onload="init();" lang="fr">
<div class="container-fluid">
<h2>Situation comptable de <?=$userName?> (#<?=$userId?>)</h2>
<?php
// Check the bookkeeping balance

$result = mysqli_query($mysqli_link, "SELECT *
		FROM $table_bk_balance JOIN $table_person ON bkb_account = CONCAT('400', ciel_code)
		WHERE jom_id = $userId
		ORDER BY bkb_date DESC
		LIMIT 0,1")
	or journalise($originalUserId, "F", "Cannot read booking keeping balance: " . mysqli_error($mysqli_link)) ;
$row = mysqli_fetch_array($result) ;
if ($row) {
	if ($row['bkb_amount'] != 0) 
		$balance = -1 * $row['bkb_amount'] ;
	else
		$balance = 0 ;
	if ($balance < 0)
		$balance_text = "<span class=\"text-danger\"> $balance &euro; (vous devez de l'argent au RAPCS ASBL)</span>" ;
	else
		$balance_text = "$balance &euro;" ;
	print("<p class=\"lead\">Le solde de votre compte en date du $row[bkb_date] est de $balance_text.</p><p>Ces informations sont mises à jour environ une fois par semaine par nos bénévoles.</p>") ;
	if ($row['bkb_amount'] > 0) {
		$invoice_total = $row['bkb_amount'] ; // Only for positive balance of course
		$invoice_reason = 'solde' ;
	}
} else 
	print("<p class=\"lead\">Le solde de votre compte n'est pas disponible.</p>") ;

print("<a href=\"myledger.php?user=$userId\" class=\"btn btn-primary\">Mon compte</a><br/>") ;

if ($userIsInstructor or $userIsAdmin) {
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
?>
<h2><?=$document_title?>  du <?=$folio_start->format('d-m-Y')?> au <?=$folio_end_title->format('d-m-Y')?></h2>

<div class="row">
	<ul class="pagination">
		<li class="page-item"><a class="page-link" href="<?="mobile_invoices.php?user=$userId"?>"><i class="bi bi-caret-left-fill"></i> Factures précédentes</a></li>
		<li class="page-item<?=$previous_active?>"><a class="page-link" href="<?="$_SERVER[PHP_SELF]?previous&user=$userId"?>">
			<i class="bi bi-caret-left-fill"></i> Folio du mois précédent <?=$previous_month_pager->format('M-Y')?></a></li>
		<li class="page-item<?=$current_active?>"><a class="page-link" href="<?="$_SERVER[PHP_SELF]?user=$userId"?>">
			<i class="bi bi-caret-left-fill"></i> Folio de ce mois <?=$this_month_pager->format('M-Y')?></a></li>
	</ul><!-- pagination -->
</div><!-- row -->

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
<tbody>
<?php

if ($balance < 0)
	$balance_class = "table-danger" ;
else
	$balance_class = "table-success" ;
print("<tr><td colspan=\"15\" class=\"$balance_class text-start\">Solde courant du compte membre</td><td class=\"$balance_class text-end\">" . 
	(($balance == 0) ? '0,00 &euro;' : numberFormat($balance, 2, ',', ' ')) . "</td></tr>\n") ;

$duration_total_hour = 0 ;
$duration_total_minute = 0 ;
$cost_plane_total = 0 ;
$cost_fi_total = 0 ;
$cost_taxes_total = 0 ;
$cost_grand_total = 0 ;
$diams_explanation = false ; // Whether to display explanation about flight duration
$folio = new Folio($userId, $folio_start->format('Y-m-d'), $folio_end->format('Y-m-d')) 
	or journalise($originalUserId, "F", "Cannot get access to the folio");
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
	print("<td class=\"text-end\">$cost_total</td>\n") ;
	print("</tr>\n") ;
}
$duration_total_hour += floor($duration_total_minute / 60) ;
$duration_total_minute = $duration_total_minute % 60 ;
$cost_plane_total = numberFormat($cost_plane_total, 2, ',', ' ') ;
$cost_fi_total = numberFormat($cost_fi_total, 2, ',', ' ') ;
$cost_taxes_total = numberFormat($cost_taxes_total, 2, ',', ' ') ;
$invoice_total = $cost_grand_total;
$cost_grand_total_text = numberFormat($cost_grand_total, 2, ',', ' ') ;
$final_balance_class = ($balance - $cost_grand_total >= 0) ? "table-warning" : "table-danger" ;
?>
<tr><td colspan="7" class="table-info">Total du folio</td>
<td class="table-info text-end"><?=$duration_total_hour?></td>
<td class="table-info text-end"><?=$duration_total_minute?></td>
<td class="table-info" colspan="3"></td>
<td class="table-info text-end"><?=$cost_plane_total?></td>
<td class="table-info text-end"><?=$cost_fi_total?></td>
<td class="table-info text-end"><?=$cost_taxes_total?></td>
<td class="table-info text-end"><?=$cost_grand_total_text?></td>
</tr>
<tr><td colspan="15" class="<?=$final_balance_class?>">Solde du compte membre avec le folio</td>
<td class="<?=$final_balance_class?> text-end"> <?= numberFormat($balance - $cost_grand_total, 2, ',' , ' ')?></td>
</tr>
</tbody>
</table>
</div><!-- table responsive -->
</div><!-- col -->
</div><!-- row -->
<p>
Sur base des données que vous avez entrées après les vols dans le
carnet de route des avions et en utilisant le prix des avions/instructeurs/taxes d'aujourd'hui (<?=date('D, j-m-Y H:i e')?>),
donc il peut y avoir une différence si les coûts par minute ont changé depuis lors.
Le montant n'inclut aucune note de frais (par exemple carburant), note de crédit, ainsi que d'autres frais (par exemple, cotisations, ou taxes d'atterrissage).
Les heures sont les heures UTC.
</p >
<!-- p>&ddagger;: depuis le 1er novembre 2022, le CA a décidé de ne plus faire payer les taxes en avance.</p-->
<?php

if ($userIsInstructor)
	print("<p>&spades; Veuillez noter qu'en tant qu'instructeur les montants négatifs de la colonne FI sont en fait des montants à facturer au club.</p>") ;

if ($diams_explanation)
	print("<p><mark>&diams;: pour cet avion, la facture se fait sur le temps de vol et pas l'index moteur.</mark></p>") ;
?>

<?php
	$invoice_reason = 'folio' ;

$version_php = date ("Y-m-d H:i:s.", filemtime('myfolio.php')) ;
$version_css = date ("Y-m-d H:i:s.", filemtime('log.css')) ;

/*
as Google Charts API is about to be deprecated, alternatives could be:
http://image-charts.com/
https://github.com/typpo/quickchart
*/
?>
<span id="payment">
<h3>QR-code pour payer <span id="payment_reason"></span> de <span id="payment_amount"></span> &euro;</h3>
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

pay(invoice_reason, invoice_total) ;
</script>
<hr>
<p><small>R&eacute;alisation: Eric Vyncke, 2022-2023, pour RAPCS, Royal A&eacute;ro Para Club de Spa, ASBL<br>
Versions: PHP=<?=$version_php?></small></p>
</div><!-- container fluid-->
</body>
</html>
