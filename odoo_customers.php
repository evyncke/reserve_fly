<?php
/*
   Copyright 2014-2026 Eric Vyncke

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

$body_attributes = 'onload="filterRows(); init(); "' ;
require_once 'mobile_header5.php' ;
require_once 'odoo.class.php' ;
$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;

if (!($userIsAdmin or $userIsBoardMember or $userIsInstructor or $userId == 348)) journalise($userId, "F", "This admin page is reserved to administrators") ;

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
        'property_account_receivable_id' => $odooClient->GetOrCreateAccount('400100', db2web("$row[last_name] $row[first_name]")) ,
        'street' => db2web($row['address']),
        'zip' => db2web($row['zipcode']),
        'city' => db2web($row['city']),
        'email' => db2web($row['email']),
        'phone' => db2web($row['cell_phone'])
    )) ;
    // TODO also copy the Joomla groups into Odoo categories !!!
}
?>
<h2>Liste de nos membres et leurs configurations Odoo@<?=$odoo_host?></h2>
<p>Les informations venant du site réservation/Joomla et d'Odoo sont croisées par l'adresse email de nos membres actifs, 
    le compte Odoo est associé au compte du site réservation si ce compte Odoo n'était pas lié. La vue Odoo 
    est disponible: <a href="https://<?=$odoo_host?>/web?debug=1#action=286&model=res.partner&view_type=kanban&cids=1&menu_id=127">ici</a>.
</p>

<script language="javascript">

function isDimmed(id) {
    var elem = document.getElementById(id) ;
    return elem.className.search("text-bg-secondary") >= 0 ;
}

function filterRows() {
    if (userId != 62) return ;
    var table = document.getElementById('memberTable') ;
    var hidePilot = isDimmed('spanPilot'), hideStudent = isDimmed('spanStudent'), hideBlocked = isDimmed('spanBlocked'), hideDesactivated = isDimmed('spanDesactivated') ;
    var rows = table.rows ;
    for (var i = 0; i < rows.length; i++) {
        var td = rows[i].getElementsByTagName("TD")[0];
        if (! td) continue ; // Some rows only have TH ;-)
        rows[i].style.display = 'none' ;
        if (td && td.innerText.search("Pilote") >= 0 && !hidePilot)
            rows[i].style.display = '' ;
        if (td && td.innerText.search("Élève") >= 0 && !hideStudent)
            rows[i].style.display = '' ;
        if (td && td.innerText.search("Bloqué") >= 0 && !hideBlocked)
            rows[i].style.display = '' ;
        if (td && td.innerText.search("Désactivé") >= 0 && !hideDesactivated)
            rows[i].style.display = '' ;
    }
}

function filterClick(elem, bg) {
    var currentClass = elem.className ;
    if (currentClass.search("text-bg-secondary") < 0) {
        elem.className = "badge rounded-pill text-bg-secondary" ;
    } else {
        elem.className = "badge rounded-pill text-bg-" + bg ;
    }
    // Now, let's do the actual filtering !
    filterRows() ;
}
</script>

<p>
Cliquez sur un des badges pour activer/désactiver l'affichage: 
<span class="badge rounded-pill text-bg-warning" onClick="filterClick(this, 'warning');" id="spanPilot">Pilote</span>
<span class="badge rounded-pill text-bg-success" onClick="filterClick(this, 'success');" id="spanStudent">Élève</span>
<span class="badge rounded-pill text-bg-danger" onClick="filterClick(this, 'danger');" id="spanBlocked">Bloqué</span>
<span class="badge rounded-pill text-bg-secondary" onClick="filterClick(this, 'info');" id="spanDesactivated">Désactivé</span>
</p>

<?php
if ($account == 'joomla') {
?>
<p>Copie des données du site réservation vers Odoo... Cela inclut l'adresse, les groupes, les numéros de téléphone, nom et prénom.</p>
<?php
} else { # ($account == 'joomla') 
?>

<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" name="account" value="joomla">
<button type="submit" class="btn btn-primary">Copier les infos du site réservations vers Odoo</button>
</form>
<?php
} 

// Let's get all Odoo customers
$result = $odooClient->SearchRead('res.partner', array(), 
    array('fields'=>array('id', 'name', 'vat', 'property_account_receivable_id', 'total_due',
        'street', 'street2', 'zip', 'city', 'country_id', 'country_code', 'category_id',
        'complete_name', 'email', 'phone', 'commercial_company_name'))) ;
$odoo_customers = array() ;
foreach($result as $client) {
    $email =  strtolower($client['email']) ;
    $odoo_customers[$email] = $client ; // Let's build a dict indexed by the email addresses
    $odoo_customers[$client['id']] = $client ; // Let's be dirty as associative PHP arrays allow is... let's also index by odoo id
}

$fi_tag = $odooClient -> GetOrCreateCategory('FI') ;
$student_tag = $odooClient -> GetOrCreateCategory('Student') ;
$pilot_tag = $odooClient -> GetOrCreateCategory('Pilot') ;
$member_tag = $odooClient -> GetOrCreateCategory('Member') ;
$board_member_tag = $odooClient -> GetOrCreateCategory('Board Member') ;

// Let's look at all our members
$result = mysqli_query($mysqli_link, "SELECT *, GROUP_CONCAT(m.group_id) AS allgroups 
    FROM $table_person AS p JOIN $table_users AS u ON u.id = p.jom_id
        LEFT JOIN $table_user_usergroup_map m ON u.id = m.user_id
        LEFT JOIN $table_blocked b ON b.b_jom_id = p.jom_id
        LEFT JOIN $table_membership_fees ON bkf_user = jom_id AND bkf_year = YEAR(CURDATE())
    WHERE jom_id IS NOT NULL AND u.block = 0
    GROUP BY jom_id
    ORDER BY last_name, first_name") 
    or journalise($userId, "F", "Cannot list all members: " . mysqli_error($mysqli_link)) ;
?>
<table class="table table-striped table-hover table-bordered" id="memberTable">
    <thead>
        <tr><th colspan="2" class="text-center">Joomla (site réservations)</th><th class="text-center">Jointure</th><th colspan="4" class="text-center">Odoo</th></tr>
        <tr><th>Nom</th><th>Joomla ID</th><th class="text-center">Email</tH><th>Odoo ID</th><th class="text-end">Solde</th><th>Rue</th><th>Zip/City</th></tr>
    </thead>
    <tbody>
<?php
while ($row = mysqli_fetch_array($result)) {
    $email = strtolower($row['email']) ;
    $active_msg = ($row['block'] == 0) ? '' : ' <span class="badge rounded-pill text-bg-info">Désactivé</span>' ;
    $blocked_msg = ($row['b_reason'] == '') ? '' : ' <span class="badge rounded-pill text-bg-danger">Bloqué</span>' ;

    $groups_msg = '' ;
    $groups = explode(',', $row['allgroups']) ;
    if (in_array($joomla_pilot_group, $groups) and $row['block'] == 0)
        $groups_msg .= ' <span class="badge rounded-pill text-bg-warning">Pilote</span>' ;
    if (in_array($joomla_student_group, $groups) and $row['block'] == 0)
        $groups_msg .= ' <span class="badge rounded-pill text-bg-success">Élève</span>' ;
    if ($row['bkf_payment_date'] != '')
        $membership_msg = '<i class="bi bi-person-check-fill text-success" title="Membership paid"><i>' ;
    else
        $membership_msg = '<i class="bi bi-person-fill-exclamation text-danger" title="Membership NOT paid"><i>' ;
    print("<tr><td>" . db2web("<b>$row[last_name]</b> $row[first_name]") . "$active_msg$blocked_msg$groups_msg$membership_msg</td>
        <td><a href=\"mobile_profile.php?displayed_id=$row[jom_id]\">$row[jom_id]</a></td>
        <td class=\"text-center\"><a href=\"mailto:$row[email]\">$row[email]</a></td>") ;
    if (isset($odoo_customers[$email]) or ($row['odoo_id'] != '' and isset($odoo_customers[$row['odoo_id']]))) {
        $odoo_customer = (isset($odoo_customers[$email])) ? $odoo_customers[$email] : $odoo_customers[$row['odoo_id']] ;
        $property_account_receivable_id = strtok($odoo_customer['property_account_receivable_id'][1], ' ') ;
        $name_from_db= db2web("$row[last_name] $row[first_name]") ;
        if ($account == "joomla") { // Master is Joomla
            $updates = array() ; 
            // TODO should also copy first_name and last_name in complete_name ?
            if ($row['address'] == '' or $row['city'] == '')
                journalise($userId, "W", "No address/city for $name_from_db") ;
            else {  
                if ($odoo_customer['street'] != db2web($row['address']) and $row['address'] != '') {
                    $updates['street'] = db2web($row['address']) ;
                }
                if ($odoo_customer['zip'] != db2web($row['zipcode']) and $row['zipcode'] != '') {
                    $updates['zip'] = db2web($row['zipcode']) ;
                }
                if ($odoo_customer['city'] != db2web($row['city']) and $row['city'] != '') {
                    $updates['city'] = db2web($row['city']) ;
                } 
            }
            if ($odoo_customer['email'] != $row['email'] and $row['email'] != '')
                $updates['email'] = $row['email'] ;    
            if ($odoo_customer['phone'] != db2web($row['home_phone']) and $row['home_phone'] != '')
                $updates['phone'] = db2web($row['home_phone']) ;
            // Odoo (since v19) only supports mobile phones.  
            if ($odoo_customer['phone'] != db2web($row['cell_phone']) and $row['cell_phone'] != '')
                $updates['phone'] = canonicalizePhone(db2web($row['cell_phone'])) ;
            elseif ($odoo_customer['phone'] != db2web($row['cell_phone']) and $row['home_phone'] != '')
                $updates['phone'] = canonicalizePhone(db2web($row['home_phone'])) ;
            if ($odoo_customer['name'] != $name_from_db and $name_from_db != '')
                $updates['name'] = $name_from_db ;
            if ($odoo_customer['complete_name'] != $name_from_db and $name_from_db != '')
                $updates['complete_name'] = $name_from_db ;
            // Code below is to ensure that all members are using the same 400100 account
            if ($property_account_receivable_id  != '400100') {
                $updates['property_account_receivable_id'] = $odooClient->GetOrCreateAccount('400100', db2web("$row[last_name] $row[first_name]")) ;
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
                // cfr https://stackoverflow.com/questions/29643834/how-to-add-tags-category-id-while-creating-customer-res-partner-in-odoo 
                // and https://www.odoo.com/documentation/12.0/developer/reference/orm.html#openerp-models-relationals-format 
                $updates['category_id'] = (count($tags) > 0) ? array(array(6, 0, $tags)) : array(array(5, 0, 0)); 
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
        print("<td>$msg<a href=\"https://spa-aviation.odoo.com/web#id=$odoo_customer[id]&cids=1&menu_id=122&action=275&model=res.partner&view_type=form\">$odoo_customer[id]</a></td>
            <td " . 
            (($odoo_customer['total_due'] > 0) ? 'class="text-danger text-end"' : 'class="text-end"') .
             ">$total_due</td><td>$odoo_customer[street]<br/>$odoo_customer[street2]</td><td>$odoo_customer[country_code] $odoo_customer[zip] $odoo_customer[city]</td>") ;
    } else { // if (isset($odoo_customers[$email])) 
        if ($row['block'] == 0)
            print("<td class=\"text-info\" colspan=\"5\">Ce membre est inconnu dans Odoo <a href=\"$_SERVER[PHP_SELF]?create=$row[jom_id]\">ajouter</a></td>") ;
        else
            print("<td colspan=\"5\"></td>") ;
    }
    print("</tr>\n") ;
}
?>        
    </tbody>
</table>

</body>
</html>