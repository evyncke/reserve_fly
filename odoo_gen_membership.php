<?php
/*
   Copyright 2023 Eric Vyncke

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
require_once 'folio.php' ;
ini_set('display_errors', 1) ; // extensive error reporting for debugging

if (! $userIsAdmin && ! $userIsBoardMember)
    journalise($userId, "F", "Vous n'avez pas le droit de consulter cette page ou vous n'êtes pas connecté.") ; 
?>
<h2>Génération des factures dans Odoo pour les cotisations</h2>
<?php
if (!isset($_REQUEST['confirm']) or $_REQUEST['year'] == '' or $_REQUEST['date'] == '' or $_REQUEST['dueDate'] == '') {
?>
<form action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" name="confirm" value="y">
<label for="yearId" class="form-label">Cotisation pour l'anné:</label>
<input type="number" name="year" id="yearId" class="form-control" value="<?=date("Y")?>">
<label for="invoiceDateId" class="form-label">Date de la facture:</label>
<input type="date" name="date" id="invoiceDateId" class="form-control" value="<?=date("Y-m-d")?>">
<label for="invoiceDueDateId" class="form-label">Date d'échéance:</label>
<input type="date" name="dueDate" id="invoiceDueDateId" class="form-control" value="<?=date("Y-m-d", strtotime("+1 week"))?>">
<button type="submit" class="btn btn-primary">Confirmer la génération</button> des cotisations sur la base des membres Joomla.
</form>
<?php    
    exit ;
} // (! isset($_REQUEST['confirm']))
journalise($userId, "I", "Odoo membership invoices generation started ") ;			
ini_set('display_errors', 1) ; // extensive error reporting for debugging
require_once 'odoo.class.php' ;
$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;

$membership_year = $_REQUEST['year'] ;
$invoice_date = $_REQUEST['date'] ;
$invoice_date_due = $_REQUEST['dueDate'] ;

# Analytic accounts and products are harcoded
$non_nav_membership_product = 24 ;
$nav_membership_product = 25 ;
$non_nav_membership_price = 70.0 ;
$nav_membership_price = 225.0 ;
$membership_analytic_account = 42  ;

// Eric = 62, Patrick = 66, Dominique = 348, Alain = 92, Bernard= 306,  Davin/élève 439, Gobron 198, René 353
if (true) {
    $jom_ids = "62, 66, 348, 92, 353, 439";
//    $jom_ids = "62, 66" ;
    $sql = "SELECT u.id AS id, last_name, first_name, odoo_id, GROUP_CONCAT(group_id) AS groups
        FROM $table_users AS u JOIN $table_user_usergroup_map ON u.id=user_id 
        JOIN $table_person AS p ON u.id=p.jom_id
        WHERE p.jom_id IN ($jom_ids)
        GROUP BY id";
} else {
    $sql = "SELECT u.id AS id, last_name, first_name, odoo_id, GROUP_CONCAT(group_id) AS groups
            FROM $table_users AS u JOIN $table_user_usergroup_map ON u.id=user_id 
            JOIN $table_person AS p ON u.id=p.jom_id
            WHERE group_id IN ($joomla_member_group, $joomla_student_group, $joomla_pilot_group, $joomla_effectif_group)
            GROUP BY id";
}				
$result_members = mysqli_query($mysqli_link, $sql)
			or journalise(0, "F", "Cannot read members: " . mysqli_error($mysqli_link)) ;
$invoiceCount = 0 ;
while ($row = mysqli_fetch_array($result_members)) {
    if ($row['odoo_id'] == '') continue ; 
	$member=$row['id'];
    $groups = explode(',', $row['groups']) ;
    print("Processing " . db2web("#$member (odoo=$row[odoo_id]): $row[last_name] $row[first_name]") . " in Joomla groups $row[groups]...<br/\n" );
    $invoice_lines = array() ;
    // Check whether student/pilot for membership dues
    if (in_array($joomla_student_group, $groups) or in_array($joomla_pilot_group, $groups)) {
        $membership_product_id = $nav_membership_product ;
        $membership_price = $nav_membership_price ;
        $libelle = "Cotisation membre naviguant" ;
        print("$libelle<br/>") ;
    } else {
        $membership_product_id = $non_nav_membership_product ;
        $membership_price = $non_nav_membership_price ;
        $libelle = "Cotisation pour membre non-naviguant" ;
        print("$libelle<br/>") ;
    }
    $invoice_lines[] = array(0, 0,
        array(
            'name' => "$libelle pour l'année $membership_year",
            'product_id' => $membership_product_id, 
            'quantity' => 1,
            'price_unit' => $membership_price,
            'analytic_distribution' => array($membership_analytic_account => 100)
    )) ;
    $params =  array(array('partner_id' => intval($row['odoo_id']), // Must be of INT type else Odoo does not accept
                    'ref' => 'Test membership invoice generated from PHP',
                    'move_type' => 'out_invoice',
                    'invoice_date_due' => $invoice_date_due,
                    'invoice_origin' => 'Liste des membres',
                    'invoice_line_ids' => $invoice_lines)) ;
    $result = $odooClient->Create('account.move', $params) ;
    print("Invoicing result for #$row[odoo_id]: $membership_price &euro;, " . implode(', ', $result) . "<br/>\n") ;
    $invoiceCount++ ;
}
journalise($userId, "I", "Successful generation of $invoiceCount membership invoices in Odoo.") ;					
?>
</body>
</html>