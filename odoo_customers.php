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

if (!$userIsAdmin and !$userIsBoardMember and !$userIsInstructor) journalise($userId, "F", "This admin page is reserved to administrators") ;
?>
<h2>Liste de nos membres et leurs configurations Odoo</h2>
<p>Les informations venant du site réservation/Joomle et d'Odoo sont croisées par l'adresse email de nos membres actifs, 
    le compte Odoo est associé au compte du site réservation si ce compte Odoo n'était pas lié. La vue Odoo 
    est disponible: <a href="https://<?=$odoo_host?>/web?debug=1#action=286&model=res.partner&view_type=kanban&cids=1&menu_id=127">ici</a>.
</p>
<?php
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

// Let's get all Odoo customers
$params = $encoder->encode(array($odoo_db, $uid, $odoo_password, 'res.partner', 'search_read', array(), 
    array('fields'=>array('id', 'name', 'vat', 'property_account_receivable_id', 'total_due',
        'street', 'street2', 'zip', 'city', 'country_id', 
        'complete_name', 'email', 'mobile', 'commercial_company_name')))) ;
$response = $models->send(new PhpXmlRpc\Request('execute_kw', $params));
if ($response->faultCode()) {
    journalise($userId, "F", "Cannot list all Odoo customers in $odoo_host: " . 
        htmlentities($response->faultCode()) . "\n" . "Reason: '" . htmlentities($response->faultString()));
}
$result = $response->value() ;
$odoo_customers = array() ;
foreach($result as $client) {
    $email =  strtolower($client['email']) ;
    $odoo_customers[$email] = $client ; // Let's build a dict indexed by the email addresses
}

// Let's look at all our members
$result = mysqli_query($mysqli_link, "SELECT * 
    FROM $table_person AS p JOIN $table_users AS u ON u.id = p.jom_id
    WHERE jom_id IS NOT NULL AND u.block = 0
    ORDER BY last_name, first_name") 
    or journalise($userId, "F", "Cannot list all members: " . mysqli_error($mysqli_link)) ;
?>
<table class="table table-striped table-hover table-bordered">
    <thead>
        <tr><th colspan="3" class="text-center">Joomla (site réservations)</th><th>Jointure</th><th colspan="4" class="text-center">Odoo</th></tr>
        <tr><th>Nom</th><th>Joomla ID</th><th>Compte Client</th><th>Email</tH><th>Compte Client</th><th>Solde</th><th>Rue</th><th>Zip/City</th></tr>
    </thead>
    <tbody>
<?php
while ($row = mysqli_fetch_array($result)) {
    $email = strtolower($row['email']) ;
    print("<tr><td>" . db2web("<b>$row[last_name]</b> $row[first_name]") . "</td><td>$row[jom_id]</td><td>$row[ciel_code400]</td><td>$row[email]</td>") ;
    if (isset($odoo_customers[$email])) {
        $odoo_customer = $odoo_customers[$email] ;
        $property_account_receivable_id = $odoo_customer['property_account_receivable_id'][1] ;
        if ($row['odoo_id'] != $odoo_customer['id']) {
            mysqli_query($mysqli_link, "UPDATE $table_person SET odoo_id = $odoo_customer[id] WHERE jom_id = $row[jom_id]") 
                or journalise($userId, "E", "Cannot set Odoo customer for user #$row[jom_id]") ;
            $msg = "<em>Updated</em>" ;
        } else
            $msg = '' ;
        print("<td>$msg $property_account_receivable_id</td><td>$odoo_customer[total_due]</td><td>$odoo_customer[street]<br/>$odoo_customer[street2]</td><td>$odoo_customer[zip] $odoo_customer[city]</td>") ;
    }
    print("</tr>\n") ;
}
?>        
    </tbody>
</table>
</body>
</html>