<?php
/*
   Copyright 2014-2024 Eric Vyncke

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

require_once 'flight_header.php' ;
require_once 'odoo.class.php' ;

$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;

if (!($userIsAdmin or $userIsBoardMember or $userIsInstructor or $userId == 348)) journalise($userId, "F", "This admin page is reserved to administrators") ;

?>
<h2>Liste des demandeurs de vols INIT/FI et Odoo@<?=$odoo_host?></h2>
<p>Copie des données des bons/vouchers du site vols IF/INIT (pas encore effectués) vers Odoo... Cela inclut l'adresse (y compris longitude & latitude), les numéros de téléphone, nom et prénom.</p>

<?php
// Let's get all Odoo customers
$result = $odooClient->SearchRead('res.partner', array(), 
    array('fields'=>array('id', 'name', 'vat', 'property_account_receivable_id', 'total_due',
        'street', 'street2', 'zip', 'city', 'country_id', 'country_code', 'category_id', 'partner_latitude', 'partner_longitude',
        'complete_name', 'email', 'phone', 'mobile', 'commercial_company_name'))) ;
$odoo_customers = array() ;
foreach($result as $client) {
    $email =  strtolower($client['email']) ;
    $odoo_customers[$email] = $client ; // Let's build a dict indexed by the email addresses
    $odoo_customers[$client['id']] = $client ; // Let's be dirty as associative PHP arrays allow is... let's also index by odoo id
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

// Role being 'student', 'member', ...
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

// Country being Belgium, France, ...
function GetOdooCountry($country) {
    global $odooClient ;
    static $cache = array() ;

    if ($country == "Belgique") $country = 'Belgium' ;
    if ($country == "Allemagne") $country = 'Germany' ;
    if ($country == "Pays-Bas") $country = 'Netherlands' ;
    if (isset($cache[$country])) return $cache[$country] ;
    $result = $odooClient->SearchRead('res.country', array(array(
		array('name', '=', $country))), 
	    array()) ; 
    if (count($result) > 0) {
        $cache[$country] = $result[0]['id'] ;
    	return $result[0]['id'] ;
    }
    return null ;
}


function geoCode($address) {
    global $gmap_api_key, $userId ;
    // https://maps.googleapis.com/maps/api/geocode/json?address=1600+Amphitheatre+Parkway,+Mountain+View,+CA&key=$gmap_api_key
    // https://developers.google.com/maps/documentation/geocoding/requests-geocoding?hl=fr
    $content = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address) . 
        '&key=' . urlencode($gmap_api_key)) ;
    // Could return { "error_message" : "This IP, site or mobile application is not authorized to use this API key. Request received from IP address 51.68.11.231, with empty referer", 
    //      "results" : [], 
    //      "status" : "REQUEST_DENIED" }
    $json = json_decode($content, true) ; // Get an associative array
    if ($json['status'] != 'OK') {
        journalise($userId, 'E', "GeoCode($address) return $json[error_message]") ;
        return false ;
    }
    $result = $json['results'][0]['geometry']['location'] ;
    return $result ;
}

$flight_tag = GetOdooCategory('Client-IF-INI') ;

// Let's look at all our flights customers
$result = mysqli_query($mysqli_link, "SELECT *
    FROM $table_flights JOIN $table_pax_role ON pr_flight = f_id
        JOIN $table_pax ON p_id = pr_pax
    WHERE f_date_flown IS NULL AND f_date_cancelled IS NULL AND p_odoo_cust_id IS NULL AND pr_role = 'C' AND f_gift <> 0
    ORDER BY p_email") 
    or journalise($userId, "F", "Cannot list all flights: " . mysqli_error($mysqli_link)) ;
?>
<table class="table table-striped table-hover table-bordered">
    <thead>
        <tr><th>Nom</th><th class="text-center">Email</tH><th>Adresse</th><th>Pays</th><th>Odoo action</th></tr>
    </thead>
    <tbody>
<?php
while ($row = mysqli_fetch_array($result)) {
    $email = strtolower($row['p_email']) ;
    // Canonicalize address/country as some entries are not too correct
    if (preg_match('/(.+) Belgique$/', $row['p_city'], $matches)) {
        $row['p_country'] = 'Belgique' ;
        $row['p_city'] = $matches[1] ;
        mysqli_query($mysqli_link, "UPDATE $table_pax SET p_city = '$matches[1]', p_country = 'Belgique'
            WHERE p_id = $row[p_id]")
        or journalise($userId, "E", "Cannot update country/city for $row[p_id] $email: " . mysqli_error($mysqli_link)) ;
    }
    print("<tr><td>" . db2web("<b>$row[p_lname]</b> $row[p_fname]") . 
        " <a href=\"https://www.spa-aviation.be/resa/flight_create.php?flight_id=$row[f_id]\">Vol</a></td>
        <td class=\"text-center\"><a href=\"mailto:$email\">$email</a></td>
        <td>" . db2web("$row[p_street]<br/>$row[p_zip] $row[p_city]") . "</td><td>$row[p_country]</td>") ;
    if (isset($odoo_customers[$email])) { // Pax already exists in Odoo... TODO should we update Odoo address ?
        $odoo_customer = $odoo_customers[$email] ;
        mysqli_query($mysqli_link, "UPDATE $table_pax SET p_odoo_cust_id = $odoo_customer[id] WHERE p_id = $row[p_id]")
            or journalise($userId, "E", "Cannot update p_odoo_cust_id for $row[p_id] $email: " . mysqli_error($mysqli_link)) ;
        print("<td>Odoo #$odoo_customer[id] ajouté au passager</td>") ;
    } else {
        $db_name = db2web("$row[p_lname] $row[p_fname]") ;
        $updates = array(
            'category_id' => array($flight_tag),
            'street' => db2web($row['p_street']),
            'zip' => db2web($row['p_zip']),
            'city' => db2web($row['p_city']),
            'country_id' => GetOdooCountry($row['p_country']), 
            'email' => $row['p_email'],  
            'phone' => db2web($row['p_tel']),
            'comment' => "Vol: <a href=\"https://www.spa-aviation.be/resa/flight_create.php?flight_id=$row[f_id]\">$row[f_reference]</a><br/>
                <h3>Note client</h3>" . nl2br(db2web($row['f_description'])) . "<h3>Note flight manager</h3>" . nl2br(db2web($row['f_notes'])),
            'ref' => $row['f_reference'],
            'name' => $db_name,
            'complete_name' => $db_name) ;  
        $coordinates = geoCode(db2web($row['p_street']) . "," . db2web($row['p_city']) . ', ' . db2web($row['p_country'])) ;
        if ($coordinates and count($coordinates) == 2) { 
            $updates['partner_latitude'] = $coordinates['lat'] ;
            $updates['partner_longitude'] = $coordinates['lng'] ;
        }
        //$updates['property_account_receivable_id'] = GetOdooAccount('400100', db2web("$row[p_lname] $row[p_fname]")) ;
        $id = $odooClient->Create('res.partner', $updates) ;
        print("<td>Odoo client $id created</td>") ;
        mysqli_query($mysqli_link, "UPDATE $table_pax SET p_odoo_cust_id = $id WHERE p_id = $row[p_id]")
            or journalise($userId, "E", "Cannot update p_odoo_cust_id for $id $email: " . mysqli_error($mysqli_link)) ;
        // Update the cache as some email have multiple pax
        $odoo_customers[$id] = $updates ;
        $odoo_customers[$email] = $updates ;
        exit ;
    }
    print("</tr>\n") ;
}
?>        
    </tbody>
</table>
</body>
</html>