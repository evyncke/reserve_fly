<?php
/*
   Copyright 2023-2024 Eric Vyncke

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
require_once 'folio.php' ;
ini_set('display_errors', 1) ; // extensive error reporting for debugging

if (! $userIsAdmin && ! $userIsBoardMember)
    journalise($userId, "F", "Vous n'avez pas le droit de consulter cette page ou vous n'êtes pas connecté.") ; 

$membership_year = (isset($_REQUEST['year'])) ? $_REQUEST['year'] : ((date('m') == '12') ? date('Y', strtotime("+1 month")) : date('Y')) ;
$invoice_date = (isset($_REQUEST['date'])) ? $_REQUEST['date'] : date("Y-m-d") ;
$invoice_date_due = (isset($_REQUEST['dueDate'] )) ? $_REQUEST['dueDate'] : date("Y-m-d", strtotime("+1 week")) ;
?>
<h2>Cotisations <?=$membership_year?> @<?=$odoo_host?></h2>
<form action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" name="confirm" value="y">
<label for="yearId" class="form-label">Cotisation pour l'année:</label>
<input type="number" name="year" id="yearId" class="form-control" value="<?=$membership_year?>">
<label for="invoiceDateId" class="form-label">Date de la facture:</label>
<input type="date" name="date" id="invoiceDateId" class="form-control" value="<?=$invoice_date?>">
<label for="invoiceDueDateId" class="form-label">Date d'échéance:</label>
<input type="date" name="dueDate" id="invoiceDueDateId" class="form-control" value="<?=$invoice_date_due?>">
<button type="submit" class="btn btn-primary">Générer les factures</button> pour les cotisations sur 
la base des membres Joomla n'ayant pas encore reçu de factures pour l'année <?=$membership_year?>.
</form>

<h3 class="my-5">Paiements des cotisation <?=$membership_year?></h3>

<table class="w-auto table-responsive table table-striped table-bordered table-sm">
    <thead>
        <tr><th>Date paiement</th><th class="text-end">Cotisation(s) payée(s)</th><th>Montant payé</th></tr>
</thead>
<tbody class="table-divider">
<?php    
$sql = "SELECT bkf_payment_date, COUNT(*) AS n, SUM(bkf_amount) AS s
    FROM $table_membership_fees
    WHERE bkf_year = '$membership_year'
    GROUP by bkf_payment_date
    ORDER BY bkf_payment_date ASC" ;
$fees_count = 0 ;
$fees_total = 0 ;
$result = mysqli_query($mysqli_link, $sql) or journalise($userId, "E", "Cannot get paid fees: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
    if ($row['bkf_payment_date'] == '') 
        $row['bkf_payment_date'] = 'Pas payé' ;
    else {
        $fees_count += $row['n'] ;
        $fees_total += $row['s'] ;
    }
    print("<tr><td>$row[bkf_payment_date]</td><td class=\"text-end\">$row[n]</td><td class=\"text-end\">" .
        number_format($row['s'], 0, ',', '.') . " &euro;</td></tr>\n") ;
}
?>
</tbody>
<tfoot class="table-divider">
    <tr class="table-info"><td>Total payé</td><td class="text-end"><?=$fees_count?></td><td class="text-end"><?=number_format($fees_total, 0, ',', '.')?> &euro;</td></tr>
</tfoot>
</table>
<?php
if (!isset($_REQUEST['confirm']) or $_REQUEST['confirm'] != 'y') exit ; // Nothing to do

journalise($userId, "I", "Odoo membership invoices generation started ") ;			
ini_set('display_errors', 1) ; // extensive error reporting for debugging
require_once 'odoo.class.php' ;
$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;


// TODO
// Don't invoice Joomla desactivated account
// Partial fee after June
// Eric = 62, Patrick = 66, Dominique = 348, Alain = 92, Bernard= 306,  Davin/élève 439, Gobron 198, René 353
if (false) {
    $jom_ids = "62, 66, 348, 92, 353, 439";
    $jom_ids = "62, 66" ;
    $jom_ids = "66" ;
    $sql = "SELECT u.id AS id, last_name, first_name, odoo_id, GROUP_CONCAT(group_id) AS allgroups
        FROM $table_users AS u JOIN $table_user_usergroup_map ON u.id=user_id
        JOIN $table_person AS p ON u.id=p.jom_id
        WHERE p.jom_id IN ($jom_ids) 
        AND u.block = 0
        AND NOT EXISTS (SELECT * FROM $table_membership_fees AS f WHERE f.bkf_user = p.jom_id and f.bkf_year = '$membership_year')
        GROUP BY id";
} else {
    $sql = "SELECT u.id AS id, last_name, first_name, odoo_id, GROUP_CONCAT(group_id) AS allgroups
            FROM $table_users AS u JOIN $table_user_usergroup_map ON u.id=user_id 
            JOIN $table_person AS p ON u.id=p.jom_id
            WHERE group_id IN ($joomla_member_group, $joomla_student_group, $joomla_pilot_group, $joomla_effectif_group)
            AND u.block = 0
            AND NOT EXISTS (SELECT * FROM $table_membership_fees AS f WHERE f.bkf_user = p.jom_id and f.bkf_year = '$membership_year')
            GROUP BY id";
}				

$result_members = mysqli_query($mysqli_link, $sql)
			or journalise(0, "F", "Cannot read members: " . mysqli_error($mysqli_link)) ;
$invoiceCount = 0 ;
while ($row = mysqli_fetch_array($result_members)) {
    if ($row['odoo_id'] == '') continue ; 
	$member=$row['id'];
    $groups = explode(',', $row['allgroups']) ;
    print("Processing " . db2web("#$member (odoo=$row[odoo_id]): $row[last_name] $row[first_name]") . " in Joomla groups $row[allgroups]...<br/\n" );
    $invoice_lines = array() ;
    $invoice_lines[] = array(0, 0,
        array(
            'name' => "Cotisation club pour l'année $membership_year",
            'product_id' => $non_nav_membership_product, 
            'quantity' => 1,
            'price_unit' => $non_nav_membership_price,
            'analytic_distribution' => array($membership_analytic_account => 100)
    )) ;
    $membership_price = $non_nav_membership_price ;
    // Check whether student/pilot for membership dues
    if (in_array($joomla_student_group, $groups) or in_array($joomla_pilot_group, $groups)) {
        $invoice_lines[] = array(0, 0,
            array(
                'name' => "Cotisation membre naviguant pour l'année $membership_year",
                'product_id' => $nav_membership_product, 
                'quantity' => 1,
                'price_unit' => $nav_membership_price,
                'analytic_distribution' => array($membership_analytic_account => 100)
            )) ;
            $membership_price += $nav_membership_price ;
    }
    $params =  array(array('partner_id' => intval($row['odoo_id']), // Must be of INT type else Odoo does not accept
    // Should the state set to 'posted' rather than 'draft' which is the default it seems?
    //                'state' => 'posted', // Returns Vous ne pouvez pas créer une écriture déjà dans l'état comptabilisé. Veuillez créer un brouillon d'écriture et l'enregistrer après.
                    'ref' => 'Cotisation club '.$membership_year,
                    'move_type' => 'out_invoice',
                    'invoice_date' => $invoice_date,
                    'invoice_date_due' => $invoice_date_due,
                    'invoice_origin' => 'Liste des membres',
                    'invoice_line_ids' => $invoice_lines)) ;
    $result = $odooClient->Create('account.move', $params) ;
    print("Invoicing result for #$row[odoo_id]: $membership_price &euro;, " . implode(', ', $result) . "<br/>\n") ;
    mysqli_query($mysqli_link, "INSERT INTO $table_membership_fees(bkf_user, bkf_year, bkf_amount, bkf_invoice_id, bkf_invoice_date)
        VALUES($member, '$membership_year', $membership_price, $result[0], '$invoice_date')")
        or journalise($userId, "F", "Cannot insert into $table_membership_fees: " . mysqli_error($mysqli_link)) ;
    $invoiceCount++ ;
}
journalise($userId, "I", "Successful generation of $invoiceCount membership invoices in Odoo.") ;					
?>
</body>
</html>