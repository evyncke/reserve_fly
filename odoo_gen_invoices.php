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

if (! $userIsAdmin && ! $userIsBoardMember)
    journalise($userId, "F", "Vous n'avez pas le droit de consulter cette page ou vous n'êtes pas connecté.") ; 

$invoice_date_due = (isset($_REQUEST['dueDate'])) ? $_REQUEST['dueDate'] : date("Y-m-d", strtotime("+1 week")) ;
$invoice_jom_id = (isset($_REQUEST['jomId'])) ? $_REQUEST['jomId'] : '' ;
$folio_start = (isset($_REQUEST['start'] )) ? 
    new DateTime($_REQUEST['start'], new DateTimeZone('UTC')) :
    new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
$folio_end = (isset($_REQUEST['end'] )) ? 
    new DateTime($_REQUEST['end'], new DateTimeZone('UTC')) :
    new DateTime(date('Y-m-01'), new DateTimeZone('UTC')) ;
$invoice_date =  $folio_end->sub(new DateInterval('P1D'))->format('Y-m-d') ;

$sql_filter = ($invoice_jom_id != '' and is_numeric($invoice_jom_id)) ? "AND jom_id = $invoice_jom_id" : '' ;
?>
<h2>Génération des factures dans Odoo sur base des carnets de vol@<?=$odoo_host?></h2>
<?php
if (! isset($_REQUEST['confirm'])) {
    $first_date = date('Y-m-01') ;
    $folio_start = new DateTime($first_date, new DateTimeZone('UTC')) ;
    $folio_start = $folio_start->sub(new DateInterval('P1M')) ;
    $folio_end = new DateTime($first_date, new DateTimeZone('UTC')) ;
?>
<form action="<?=$_SERVER['PHP_SELF']?>" class="row g-3">
<input type="hidden" name="confirm" value="y">
<div class="col-md-3">
    <label for="invoiceDueDateId" class="form-label">Date d'échéance:</label>
    <input type="date" name="dueDate" id="invoiceDueDateId" class="form-control" value="<?=$invoice_date_due?>">
</div>
<div class="col-md-3">
    <label for="start" class="form-label">Date 1er vol</label>
    <input type="date" class="form-control" id="start" name="start" value="<?=$folio_start->format('Y-m-d')?>">
</div>
<div class="col-md-3">
    <label for="end" class="form-label">Date 1er vol facture suivante</label>
    <input type="date" class="form-control" id="end" name="end" value="<?=$folio_end->format('Y-m-d')?>">
</div>
<div class="col-md-3">
    <label for="jomId" class="form-label">Uniquement pour le membre</label>
    <input type="number" class="form-control" id="jomId" name="jomId" placeholder="66 ou 62 (jom_id) !!! avec précaution">
</div>
<button type="submit" class="btn btn-primary col-xs-12 col-md-2">Confirmer la génération</button> sur base des carnets de routes des avions.
</form>
<?php    
    exit ;
} // (! isset($_REQUEST['confirm']))
journalise($userId, "I", "Odoo invoices generation started ($odoo_host)") ;			
ini_set('display_errors', 1) ; // extensive error reporting for debugging
require_once 'odoo.class.php' ;
$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;

# Analytic accounts and products are harcoded
$plane_product_id = 6 ;
$tax_product_id = 53; // Hard coded TILEA taxes
// Before there was one product per FI, now all the same but let's keep the code here
$fi_product_id = array(46 => 5, // Benoît Mendes
    50 => 5, // Luc Wynand
    59 => 5, // Nicolas Claessen
    118 => 5) ; // David Gaspar
// Plane analytic accounts could be dynamically built (like in odoo_customers.php) as the 'name' property is set to the call sign
$plane_analytic = array('OO-ALD' => 26, 
    'OO-ALE' => 27, 
    'OO-APV' => 28, 
    'OO-FMX' => 29, 
    'OO-JRB' => 30, 
    'OO-SPQ' => 31, 
    'PH-AML' => 32) ;
$fi_analytic = array(46 => 36, // Benoît Mendes
    50 => 34, // Luc Wynand
    59 => 35, // Nicolas Claessen
    118 => 33) ; // David Gaspar

$pax_telia_analytic = 40; // Centre de cout telia taxe passager

// Eric = 62, Patrick = 66, Dominique = 348, Alain = 92, Bernard= 306,  Davin/élève 439, Gobron 198
if (false) {
    $jom_ids = "66";
//    $jom_ids = "62, 66" ;
    $sql = "SELECT u.id AS id, last_name, first_name, odoo_id
        FROM $table_users AS u JOIN $table_user_usergroup_map ON u.id=user_id 
        JOIN $table_person AS p ON u.id=p.jom_id
        WHERE p.jom_id IN ($jom_ids)
        GROUP BY id";
} else {
    $sql = "SELECT u.id AS id, last_name, first_name, odoo_id
            FROM $table_users AS u JOIN $table_user_usergroup_map ON u.id=user_id 
            JOIN $table_person AS p ON u.id=p.jom_id
            WHERE group_id IN ($joomla_member_group, $joomla_student_group, $joomla_pilot_group, $joomla_effectif_group)
            $sql_filter
            GROUP BY id";
}				
$result_members = mysqli_query($mysqli_link, $sql)
			or journalise(0, "F", "Cannot read members: " . mysqli_error($mysqli_link)) ;
$invoiceCount = 0 ;
while ($row = mysqli_fetch_array($result_members)) {
	$member=$row['id'];
    if ($row['odoo_id'] == '') continue ; 
    // TODO obviously need to be dynamic
    $folio = new Folio($member,  $folio_start->format('Y-m-d'), $folio_end->format('Y-m-d')) ;
    if ($folio->count == 0) continue ; // Skip empty folios
    print("Traitement de " . db2web("#$member (odoo=$row[odoo_id]): $row[last_name] $row[first_name]") . "... \n" );
    $invoice_lines = array() ;
    $total_folio = 0 ;
    foreach($folio as $line) {
        if ($line->cost_fi < 0) {
			// This is a DC flight for a FI. Line skipped. Not to be added in the invoice
			continue;
		}
		$shareInfo="";
        $code_plane = substr($line->plane, 3) ;
        $plane = $line->plane ;
		$date=substr($line->date,6,2).substr($line->date,3,2).substr($line->date,0,2).":".substr($line->time_start,0,2).substr($line->time_start,3,2);
		$DC="";
		if($line->instructor_name!="") {
			$DC="DC";
		}
		if($line->share_type!="") {
            switch ($line->share_member) { // TODO this part is probably not required as folio class is fixed
                case -1: $shareInfo=$line->share_type." "."Ferry"; break ; 
                case -2: $shareInfo=$line->share_type." "."Club"; break ; 
                case -3: $shareInfo=$line->share_type." "."INIT"; break ; 
                case -4: $shareInfo=$line->share_type." "."IF"; break ; 
                case -5: $shareInfo=$line->share_type." "."Membre"; break ; 
                case -6: $shareInfo=$line->share_type." "."DHF"; break ; 
                case -7: $shareInfo=$line->share_type." "."Club"; break ; 
                case -8: $shareInfo=$line->share_type." "."Mecano"; break ; 
				default: $shareInfo=$line->share_type." ".$line->share_member_name." ".$line-> share_member_fname; break;
            }
		}
		$picName = "PIC ".$line->pic_name;
		if($line->pic_name == "SELF") $picName= "";
	    if ($line->cost_plane > 0) {
            $invoice_lines[] = array(0, 0,
				array(
					'name' => "$line->date {$line->time_start}Z $line->plane $shareInfo",
					'product_id' => $plane_product_id, 
					'quantity' => $line->duration,
					'price_unit' => $line->cost_plane_minute,
                    'analytic_distribution' => array($plane_analytic[$plane] => 100)
				)) ;
		}
        // Special line if there are taxes (Passanger)
        if ($line->cost_taxes > 0) {
			$taxPerPax=$line->cost_taxes/$line->pax_count;
            $invoice_lines[] = array(0, 0,
				array(
					'name' => "$line->date {$line->time_start}Z $line->plane Redevance Pax $line->from > $line->to",
					'product_id' => $tax_product_id,
					'quantity' => $line->pax_count,
					'price_unit' => $taxPerPax,
					'analytic_distribution' => array($pax_telia_analytic => 100)
				)) ;
        }
        // Special line if there is an instructor
        if ($line->cost_fi > 0) {
            $invoice_lines[] = array(0, 0, // See https://www.odoo.com/documentation/16.0/developer/reference/backend/orm.html#relational-fields for the 0, 0
				array(
					'name' => "$line->date {$line->time_start}Z $line->plane DC $line->instructor_name",
					'product_id' => $fi_product_id[$line->instructor_code],
					'quantity' => $line->duration,
					'price_unit' => "$cost_fi_minute", // Forcing string format
                    'analytic_distribution' => array($fi_analytic[$line->instructor_code] => 100)
				)) ;
        }
        $total_folio += $line->cost_plane + $line->cost_fi + $line->cost_taxes ; 
    } // foreach($folio as $line) 
    
	if ($total_folio > 0) {
        $params =  array(array('partner_id' => intval($row['odoo_id']), // Must be of INT type else Odoo does not accept
                    'ref' => db2web("Vols de $row[last_name] $row[first_name]"),
                    'move_type' => 'out_invoice',
                    'invoice_date' => $invoice_date,
                    'invoice_date_due' => $invoice_date_due,
                    'invoice_origin' => 'Carnets de routes',
                    'invoice_line_ids' => $invoice_lines)) ;
        if(1) {
            $result = $odooClient->Create('account.move', $params) ;
            print("Facture pour Odoo ID #$row[odoo_id] $total_folio &euro;: facture n° " . implode(', ', $result) . "<br/>\n") ;
        }
        else {
            print("Facture pour Odoo ID #$row[odoo_id] $total_folio &euro;: facture n° xxx <br/>\n") ;
            var_dump($params);
            print("<br>");
        }
        $invoiceCount++ ;
	} else
        print("Total de la facture: $total_folio, aucune facture générée.<br/>\n") ;
}
journalise($userId, "I", "Successful generation of $invoiceCount invoices in Odoo@$odoo_host.") ;					
?>
</body>
</html>