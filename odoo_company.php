<?php
/*
   Copyright 2024 Eric Vyncke

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
require_once 'odoo.class.php' ;

if (!$userIsAdmin and !$userIsBoardMember and !$userIsInstructor) journalise($userId, "F", "This admin page is reserved to administrators") ;

$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;

// Get all companies Odoo_id to collect all information
$result = mysqli_query($mysqli_link, "SELECT c_odoo_id
	FROM $table_company")
	or journalise($userId, "E", "Cannot retrievecompanies: " . mysqli_error($mysqli_link)) ;
$ids = array() ;
while ($row = mysqli_fetch_array($result)) {
	$ids[] = intval($row['c_odoo_id']) ;
}

$result = $odooClient->Read('res.partner', 
    $ids, 
    array('fields' => array('id', 'name', 'email', 'street', 'zip', 'city', 'country_id', 'total_due'))) ; 
$companies = [] ;
foreach($result as $record)
    $companies[$record['id']] = $record ;
?>
<h2>Sociétés des membres @<?=$odoo_host?></h2>
<p>Liste des membres actifs du club ayant une entreprise à laquelle facturer. Pour changer, demander à Éric ou Patrick.</p>

<form action="<?=$_SERVER['PHP_SELF']?>" id="company_form">
    <input type="hidden" name="save_company" value="true">
<table class="table table-hover table-responsive table-bordered">
    <thead>
        <tr><th>Nom, prénom <i>(Codes ciel/Odoo)</i></th><th>Entreprise</th><th>Odoo ID</th></th><th>Code BCE</th><th>Balance</th><th>Adresse</th><th>Code postal</th><th>Ville</th><th>Pays</th></tr>
    </thead>
    <tbody class="table-group-divider">
<?php
$result = mysqli_query($mysqli_link, "SELECT * 
        FROM $table_person
        JOIN jom_users AS u ON u.id = jom_id
        LEFT JOIN $table_company_member ON jom_id = cm_member
        JOIN $table_company ON cm_company = c_id
        WHERE block = 0
        ORDER BY last_name, first_name DESC")
    or journalise($userId, "F", "Cannot read companies: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
    if (isset($row['c_odoo_id'])) {
        $company = $companies[$row['c_odoo_id']] ;
        $name = $company['name'] ;
        $address = $company['street'] ;
        $city = $company['city'] ;
        $zipcode = $company['zip'] ;
        $country = $company['country_id'][1] ;
    } else {
        $name = "Aucune compagnie dans Odoo pour #$row[c_odoo_id]" ;
        $address = '?' ;
        $city = '?' ;
        $zipcode = '' ;
        $country = '?' ;
    }
    $first_name = db2web($row['first_name']) ;
    $last_name = db2web($row['last_name']) ;
    print("<tr class=\"ciel-row\">
        <td><b>$last_name</b>, $first_name <i>($row[ciel_code400] / $row[odoo_id])</i></td>
        <td>$name</td>
        <td><a href=\"https://$odoo_host/web#id=$row[c_odoo_id]&cids=1&menu_id=122&action=275&model=res.partner&view_type=form\" target=\"_blank\">$row[c_odoo_id] <i class=\"bi bi-box-arrow-up-right\"></a></td>
        <td>$row[c_bce]</td><td>$company[total_due]&nbsp;&euro;</td><td>$address</td><td>$zipcode</td><td>$city</td><td>$country</td>
        </tr>\n") ;
}
?>
</tbody>
</table>
</form>
</body>
</html>