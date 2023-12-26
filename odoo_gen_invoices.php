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

if (! $userIsAdmin && ! $userIsBoardMember)
    journalise($userId, "F", "Vous n'avez pas le droit de consulter cette page ou vous n'êtes pas connecté.") ; 

?>

<h2>Génération des factures dans Odoo sur base des carnets de vol@<?=$odoo_host?></h2>
<?php
if (! isset($_REQUEST['confirm'])) {
?>
<form action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" name="confirm" value="y">
<button type="submit" class="btn btn-primary">Confirmer la génération</button> sur base des carnets de routes des avions.
</form>
<?php    
    exit ;
} // (! isset($_REQUEST['confirm']))
journalise($userId, "I", "Odoo invoices generation started ") ;			
ini_set('display_errors', 1) ; // extensive error reporting for debugging
require __DIR__ . '/vendor/autoload.php' ;

// Should probably move to a library / class
$common = new PhpXmlRpc\Client("https://$odoo_host/xmlrpc/2/common");
$common->setOption(PhpXmlRpc\Client::OPT_RETURN_TYPE, PhpXmlRpc\Helper\XMLParser::RETURN_PHP);
$params = array(new PhpXmlRpc\Value($odoo_db), 
    new PhpXmlRpc\Value($odoo_username), 
    new PhpXmlRpc\Value($odoo_password),
    new PhpXmlRpc\Value(array(), 'array')) ;
$response = $common->send(new PhpXmlRpc\Request('authenticate', $params)) ;
if (!$response->faultCode()) {
    $uid = $response->value() ;
    journalise($userId, "D", "Connected to Odoo $odoo_host as $odoo_username with UID $uid") ;
} else {
    journalise($userId, "F", "Cannot connect to Odoo $odoo_host as $odoo_username: " . htmlentities($response->faultCode()) . "\n" . "Reason: '" .
        htmlentities($response->faultString()));
}
$models = new PhpXmlRpc\Client("https://$odoo_host/xmlrpc/2/object");
$models->setOption(PhpXmlRpc\Client::OPT_RETURN_TYPE, PhpXmlRpc\Helper\XMLParser::RETURN_PHP);
$encoder = new PhpXmlRpc\Encoder() ;

// Eric = 62, Patrick = 66, Dominique = 348, Alain = 92, Bernard= 306,  Davin/élève 439, Gobron 198
if (false) {
    $jom_ids = "62, 66, 348, 92";
    $jom_ids = "62, 66" ;
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
            GROUP BY id";
}				
$result_members = mysqli_query($mysqli_link, $sql)
			or journalise(0, "F", "Cannot read members: " . mysqli_error($mysqli_link)) ;
$invoiceCount = 0 ;
while ($row = mysqli_fetch_array($result_members)) {
	$member=$row['id'];
    if ($row['odoo_id'] == '') continue ; 
    $folio = new Folio($member, '2023-12-01', '2024-01-01') ;
    if ($folio->count == 0) continue ; // Skip empty folios
    print("Processing " . db2web("#$member (odoo=$row[odoo_id]): $row[last_name] $row[first_name]") . "...<br/\n" );
    $invoice_lines = array() ;
    $total_folio = 0 ;
    foreach($folio as $line) {
        if ($line->cost_fi < 0) {
			// This is a DC flight for a FI. Line skipped. Not to be added in the invoice
			continue;
		}
		$shareInfo="";
        $code_plane = substr($line->plane, 3) ;
		$date=substr($line->date,6,2).substr($line->date,3,2).substr($line->date,0,2).":".substr($line->time_start,0,2).substr($line->time_start,3,2);
		$DC="";
		if($line->instructor_name!="") {
			$DC="DC";
		}
		if($line->share_type!="") {
            switch ($line->share_member) { // TOOD this part is probably not required as folio class is fixed
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
		$libelle = $code_plane.$date;
		if($DC != "") {
			$libelle = $libelle.".".$DC;
		}
		if($shareInfo != "") {
			$libelle = $libelle.".".$shareInfo;
		}
		$picName = "PIC ".$line->pic_name;
		if($line->pic_name == "SELF") $picName= "";
	    if ($line->cost_plane > 0) {
            $invoice_lines[] = array(0, 0,
				array(
					'name' => "$libelle $line->date $line->plane $shareInfo",
					'product_id' => 8, // Hard coded plane rental
					'quantity' => $line->duration,
					'price_unit' => $line->cost_plane_minute
				)) ;
		}
        // Special line if there are taxes
        if ($line->cost_taxes > 0) {
			$taxPerPax=$line->cost_taxes/$line->pax_count;
            $invoice_lines[] = array(0, 0,
				array(
					'name' => "$line->date $line->plane Redevance Pax $line->from > $line->to",
					'product_id' => 9,
					'quantity' => $line->pax_count,
					'price_unit' => $taxPerPax
				)) ;
        }
        // Special line if there is an instructor
        if ($line->cost_fi > 0) {
            switch ($line->instructor_code) {
                case  46: $odoo_product = 3 ; $fi_code = 700202 ; $fi_analytique = 'EC' ; break ; // Benoît Mendes, EC pour école ?
                case  50: $odoo_product = 5 ; $fi_code = 700205 ; $fi_analytique = 'WL' ; break ; // Luc Wynand
                case  59: $odoo_product = 6 ; $fi_code = 700206 ; $fi_analytique = 'NC' ; break ; // Nicolas Claessen
                case 118: $odoo_product = 7 ; $fi_code = 700208 ; $fi_analytique = 'EC' ; break ; // David Gaspar, EC pour école ?
            }
            $invoice_lines[] = array(0, 0,
				array(
					'name' => "$line->date $line->plane DC $line->instructor_name",
					'product_id' => $odoo_product,
					'quantity' => $line->duration,
					'price_unit' => "$cost_fi_minute" // Forcing string format
				)) ;
        } 
    }
    $total_folio += $line->cost_plane + $line->cost_fi + $line->cost_taxes ;
	if ($total_folio > 0) {
        $params = $encoder->encode(array($odoo_db, $uid, $odoo_password, 'account.move', 'create',
	        array(array('partner_id' => intval($row['odoo_id']), // Must be of INT type else Odoo does not accept
                'ref' => 'Test invoice generated from PHP',
                'move_type' => 'out_invoice',
                'invoice_origin' => 'Spa Aviation Bookings',
                'invoice_line_ids' => $invoice_lines)))) ;
        $response = $models->send(new PhpXmlRpc\Request('execute_kw', $params));
        if ($response->faultCode()) {
                print("Error...\n") ;
                print("Code: " . htmlentities($response->faultCode()) . "\n" . "Reason: '" .
                    htmlentities($response->faultString()));
                exit ;
        }
        $result = $response->value() ;
        print("Invoicing result for $row[odoo_id] $total_folio &euro;: $result<br/>\n") ;
        $invoiceCount++ ;
	}
}
journalise($userId, "I", "Successful generation of $invoiceCount invoices in Odoo.") ;					
?>
</body>
</html>