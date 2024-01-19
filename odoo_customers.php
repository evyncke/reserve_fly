<?php
/*
   Copyright 2014-2023 Eric Vyncke

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
require_once 'odoo.class.php' ;
$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;

if (!$userIsAdmin and !$userIsBoardMember and !$userIsInstructor) journalise($userId, "F", "This admin page is reserved to administrators") ;
$account = (isset($_REQUEST['account'])) ? $_REQUEST['account'] : '' ;
$create = (isset($_REQUEST['create']) and is_numeric($_REQUEST['create'])) ? $_REQUEST['create'] : '' ;

// Let's create a Odoo partner/client on request
if ($create) {
    $result = mysqli_query($mysqli_link, "SELECT * FROM $table_person WHERE jom_id = $create")
        or journalise($userId, "F", "Cannot read $table_person for #$create: " . mysqli_error($mysqli_link)) ;
    $row = mysqli_fetch_array($result) 
        or journalise($userId, "F", "User $create not found") ;
    $id = $odooClient->Create('res.partner', array(
        'name' => db2web("$row[last_name] $row[first_name]"),
        'complete_name' => db2web("$row[last_name] $row[first_name]"),
        'property_account_receivable_id' => GetOdooAccount('400100', db2web("$row[last_name] $row[first_name]")) ,
        'street' => db2web($row['address']),
        'zip' => db2web($row['zipcode']),
        'city' => db2web($row['city']),
        'email' => db2web($row['email']),
        'phone' => db2web($row['home_phone']),
        'mobile' => db2web($row['cell_phone'])
    )) ;
}
?>
<h2>Liste de nos membres et leurs configurations Odoo@<?=$odoo_host?></h2>
<p>Les informations venant du site réservation/Joomle et d'Odoo sont croisées par l'adresse email de nos membres actifs, 
    le compte Odoo est associé au compte du site réservation si ce compte Odoo n'était pas lié. La vue Odoo 
    est disponible: <a href="https://<?=$odoo_host?>/web?debug=1#action=286&model=res.partner&view_type=kanban&cids=1&menu_id=127">ici</a>.
</p>
<?php
if ($account == 'ciel') {
?>
<p>Copie des données du site réservation vers Odoo... Cela inclut l'adresse, les numéros de téléphone, nom et prénom.</p>
<?php
} else { # ($account == 'ciel') 
?>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" name="account" value="joomla">
<button type="submit" class="btn btn-primary">Copier les infos de ciel/réservation vers Odoo</button>
</form>
<?php
} # ($account == 'ciel') 


// Let's get all Odoo customers
$result = $odooClient->SearchRead('res.partner', array(), 
    array('fields'=>array('id', 'name', 'vat', 'property_account_receivable_id', 'total_due',
        'street', 'street2', 'zip', 'city', 'country_id', 'country_code', 'category_id', 'partner_latitude', 'partner_longitude',
        'complete_name', 'email', 'phone', 'mobile', 'commercial_company_name'))) ;
$odoo_customers = array() ;
foreach($result as $client) {
    $email =  strtolower($client['email']) ;
    $odoo_customers[$email] = $client ; // Let's build a dict indexed by the email addresses
}

function GetOdooAccount($code, $fullName) {
    global $odooClient ;
    static $cache = array() ;

    if (isset($cache[$code])) return $cache[$code] ;
    $result = $odooClient->SearchRead('account.account', array(array(
		array('account_type', '=', 'asset_receivable'),
		array('code', '=', $code))), 
	array()) ; 
    if (count($result) > 0) {
        $cache[$code] = $result[0]['id'] ;
    	return $result[0]['id'] ;
    }
    // Customer account does not exist... Need to create one
    $id = $odooClient->Create('account.account', array(
        'name' => $fullName,
        'account_type' => 'asset_receivable',
        'internal_group' => 'asset',
        'code' => $code,
        'name' => "$fullName")) ;
    if ($id > 0) {
        $cache[$code] = $id ;
        return $id ;
    } else
        return 158 ; // Harcoded default 400000 in RAPCS2.odoo.com
}

function GetOdooCategory($role) {
    global $odooClient ;
    static $cache = array() ;

    if (isset($cache[$role])) return $cache[$role] ;
    $result = $odooClient->SearchRead('res.partner.category', array(array(
		array('name', '=', $role))), 
	array()) ; 
    if (count($result) > 0) {
        $cache[$role] = $result[0]['id'] ;
    	return $result[0]['id'] ;
    }
    // Category does not exist... Need to create one
    $id = $odooClient->Create('res.partner.category', array(
        'name' => $role, 'display_name' => $role)) ;
    if ($id > 0) {
        $cache[$role] = $id ;
        return $id ;
    }
}

$fi_tag = GetOdooCategory('FI') ;
$student_tag = GetOdooCategory('Student') ;
$pilot_tag = GetOdooCategory('Pilot') ;
$member_tag = GetOdooCategory('Member') ;
$board_member_tag = GetOdooCategory('Board Member') ;

// Let's look at all our members
$result = mysqli_query($mysqli_link, "SELECT *, GROUP_CONCAT(m.group_id) AS groups 
    FROM $table_person AS p JOIN $table_users AS u ON u.id = p.jom_id
        LEFT JOIN $table_user_usergroup_map m ON u.id = m.user_id
    WHERE jom_id IS NOT NULL
    GROUP BY jom_id
    ORDER BY last_name, first_name") 
    or journalise($userId, "F", "Cannot list all members: " . mysqli_error($mysqli_link)) ;
?>
<table class="table table-striped table-hover table-bordered">
    <thead>
        <tr><th colspan="3" class="text-center">Joomla (site réservations)</th><th class="text-center">Jointure</th><th colspan="5" class="text-center">Odoo</th></tr>
        <tr><th>Nom</th><th>Joomla ID</th><th>Compte Client</th><th class="text-center">Email</tH><th>Odoo ID</th><th>Compte Client</th><th class="text-end">Solde</th><th>Rue</th><th>Zip/City</th></tr>
    </thead>
    <tbody>
<?php
while ($row = mysqli_fetch_array($result)) {
    $email = strtolower($row['email']) ;
    $active_msg = ($row['block'] == 0) ? '' : ' <span class="badge rounded-pill text-bg-info">Désactivé</span>' ;
    print("<tr><td>" . db2web("<b>$row[last_name]</b> $row[first_name]") . "$active_msg</td><td>$row[jom_id]</td><td>$row[ciel_code400]</td><td class=\"text-center\">$row[email]</td>") ;
    if (isset($odoo_customers[$email])) {
        $odoo_customer = $odoo_customers[$email] ;
        $property_account_receivable_id = strtok($odoo_customer['property_account_receivable_id'][1], ' ') ;
        $db_name = db2web("$row[last_name] $row[first_name]") ;
        $groups = explode(',', $row['groups']) ;
        if ($account == "joomla") { // Master is Joomla
            $updates = array() ; 
            // TODO should also copy first_name and last_name in complete_name ?    
            if ($odoo_customer['street'] != db2web($row['address']) and $row['address'] != '')
                $updates['street'] = db2web($row['address']) ;
            if ($odoo_customer['zip'] != db2web($row['zipcode']) and $row['zipcode'] != '')
                $updates['zip'] = db2web($row['zipcode']) ;
            if ($odoo_customer['city'] != db2web($row['city']) and $row['city'] != '')
                $updates['city'] = db2web($row['city']) ;
            // Should also trigger setting partner_longitude & partner_latitude...
            if ($odoo_customer['phone'] != db2web($row['home_phone']) and $row['home_phone'] != '')
                $updates['phone'] = db2web($row['home_phone']) ;
            if ($odoo_customer['mobile'] != db2web($row['cell_phone']) and $row['cell_phone'] != '')
                $updates['mobile'] = db2web($row['cell_phone']) ;
            if ($odoo_customer['name'] != $db_name and $db_name != '')
                $updates['name'] = $db_name ;
            if ($odoo_customer['complete_name'] != $db_name and $db_name != '')
                $updates['complete_name'] = $db_name ;
            // Code below is to copy from Ciel to Odoo
            // Disabled based on Dominique Collette's feedback over WhatsApp on 2023-12-27    
            //if ($row['ciel_code400'] != '' and $property_account_receivable_id  != $row['ciel_code400']) {
            //    $updates['property_account_receivable_id'] = GetOdooAccount($row['ciel_code400'], db2web("$row[last_name] $row[first_name]")) ;
            //}
            // Code below is to ensure that all members are using the same 400100 account
            if ($property_account_receivable_id  != '400100') {
                $updates['property_account_receivable_id'] = GetOdooAccount('400100', db2web("$row[last_name] $row[first_name]")) ;
            }
            // TODO for FI, should do property_account_payable_id based on the code ? 400xxx to 700xxx ?
            $tags = array() ;
            if (in_array($joomla_instructor_group, $groups) and $row['block'] == 0)
                $tags[] = $fi_tag ;
            if (in_array($joomla_pilot_group, $groups) and $row['block'] == 0)
                $tags[] = $pilot_tag ;
            if (in_array($joomla_student_group, $groups) and $row['block'] == 0)
                $tags[] = $student_tag ;
            if (in_array($joomla_member_group, $groups) and $row['block'] == 0)
                $tags[] = $member_tag ;
            if (in_array($joomla_board_group, $groups)  and $row['block'] == 0)
                $tags[] = $board_member_tag ;
            if (count(array_diff($tags, $odoo_customer['category_id'])) > 0 or count(array_diff($odoo_customer['category_id'], $tags)) > 0) // Compare arrays of Odoo and Ciel tags/groups
                $updates['category_id'] = (count($tags) > 0) ? $tags : false ; // False is the only way to clean up existing values...
            if (count($updates) > 0) { // There were some changes, let's update the Odoo record
                $response = $odooClient->Update('res.partner', array($odoo_customer['id']), $updates) ;
                $msg = '<span class="text-warning">Odoo updated</span> ' ;
            } else 
                $msg = '' ;
        } else { // Master is Odoo
            if ($row['odoo_id'] != $odoo_customer['id']) {
                mysqli_query($mysqli_link, "UPDATE $table_person SET odoo_id = $odoo_customer[id] WHERE jom_id = $row[jom_id]") 
                    or journalise($userId, "E", "Cannot set Odoo customer for user #$row[jom_id]") ;
                $msg = '<span class="text-warning">Odoo_id updated</span>' ;
            } else
                $msg = '' ;
        }
        $total_due = number_format(-$odoo_customer['total_due'], 2, ",", ".") ;
        print("<td>$msg$odoo_customer[id]</td><td>$property_account_receivable_id</td><td " . 
            (($odoo_customer['total_due'] > 0) ? 'class="text-danger text-end"' : 'class="text-end"') .
             ">$total_due</td><td>$odoo_customer[street]<br/>$odoo_customer[street2]</td><td>$odoo_customer[country_code] $odoo_customer[zip] $odoo_customer[city]</td>") ;
    } else { // if (isset($odoo_customers[$email])) 
        print("<td class=\"text-info\" colspan=\"5\">Ce membre est inconnu dans Odoo <a href=\"$_SERVER[PHP_SELF]?create=$row[jom_id]\">ajouter</a></td>") ;

    }
    print("</tr>\n") ;
}
?>        
    </tbody>
</table>
</body>
</html>