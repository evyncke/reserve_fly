<?php
/*
   Copyright 2021-2024 Eric Vyncke

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
require_once('dbi.php') ;
require_once('odoo.class.php') ;

if ($userId == 0) {
	journalise(0, "F", "Need to be logged in") ;
	exit ;
}
$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;


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

$member_tag = GetOdooCategory('Member') ;

$result = $odooClient->SearchRead('res.partner', array(), array('fields'=>array('id', 
	'name',
	'partner_longitude',
	'partner_latitude',
	'complete_name',
	'email',
	'mobile',
	'street',
	'category_id',
	'city'))) ; 

$members = [] ;
foreach($result as $client) {
	if (in_array($member_tag, $client['category_id'])) {
        $member = array('name' => $client['name'], 'latitude' => $client['partner_latitude'], 'longitude' => $client['partner_longitude'], 'city' => $client['city']) ;
        $members[] = $member ;
    }
}

@header('Content-type: application/json');
$json_encoded = json_encode($members) ;
if ($json_encoded === FALSE) {
	journalise($userId, 'E', "Cannot JSON_ENCODE(), error code: " . json_last_error_msg()) ;
	print("{'errorMessage' : 'cannot json_encode(): " . json_last_error_msg() . "'}") ;
} else
	print($json_encoded) ;
?>