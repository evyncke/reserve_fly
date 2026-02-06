<?php
/*
   Copyright 2023-2026 Eric Vyncke

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

require_once 'odoo.class.php' ;

if (!isset($odooClient))
    $odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;

function odooSynchronize() {
    global $odooClient, $mysqli_link, $table_person, $table_users, $table_user_usergroup_map, 
        $table_blocked, $table_membership_fees,
        $joomla_instructor_group, $joomla_pilot_group, $joomla_flying_student_group, $joomla_theory_student_group, 
        $joomla_member_group, $joomla_board_group, $userId ;
        
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
    $theory_student_tag = $odooClient -> GetOrCreateCategory('Theory Student') ;
    $pilot_tag = $odooClient -> GetOrCreateCategory('Pilot') ;
    $member_tag = $odooClient -> GetOrCreateCategory('Member') ;
    $board_member_tag = $odooClient -> GetOrCreateCategory('Board Member') ;

    // Let's look at all our current and past members
    $result = mysqli_query($mysqli_link, "SELECT *, GROUP_CONCAT(m.group_id) AS allgroups 
        FROM $table_person AS p JOIN $table_users AS u ON u.id = p.jom_id
            LEFT JOIN $table_user_usergroup_map m ON u.id = m.user_id
            LEFT JOIN $table_blocked b ON b.b_jom_id = p.jom_id
            LEFT JOIN $table_membership_fees ON bkf_user = jom_id AND bkf_year = YEAR(CURDATE())
        WHERE jom_id IS NOT NULL
        GROUP BY jom_id
        ORDER BY last_name, first_name") 
        or journalise($userId, "F", "Cannot list all members: " . mysqli_error($mysqli_link)) ;

    while ($row = mysqli_fetch_array($result)) {
        $email = strtolower($row['email']) ;
        if (isset($odoo_customers[$email]) or ($row['odoo_id'] != '' and isset($odoo_customers[$row['odoo_id']]))) {
            $odoo_customer = (isset($odoo_customers[$email])) ? $odoo_customers[$email] : $odoo_customers[$row['odoo_id']] ;
            $property_account_receivable_id = strtok($odoo_customer['property_account_receivable_id'][1], ' ') ;
            $name_from_db= db2web("$row[last_name] $row[first_name]") ;
            $groups = explode(',', $row['allgroups']) ;
            $updates = array() ; 
            // TODO should also copy first_name and last_name in complete_name ?
            if ($row['address'] == '' or $row['city'] == '') {
                if ($row['block'] == 0) {
                    journalise($userId, "W", "Member $name_from_db (#$row[jom_id]) has no address or city set in Ciel, but is active in Joomla/Odoo") ;
                }
            } else {  
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
//            if ($odoo_customer['mobile'] != db2web($row['cell_phone']) and $row['cell_phone'] != '')
//                $updates['mobile'] = db2web($row['cell_phone']) ;
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
            if (in_array($joomla_flying_student_group, $groups) and $row['block'] == 0)
                $tags[] = $student_tag ;
            if (in_array($joomla_theory_student_group, $groups) and $row['block'] == 0)
                $tags[] = $theory_student_tag ;
            if (in_array($joomla_member_group, $groups) and $row['block'] == 0)
                $tags[] = $member_tag ;
            if (in_array($joomla_board_group, $groups)  and $row['block'] == 0)
                $tags[] = $board_member_tag ;
            if (count(array_diff($tags, $odoo_customer['category_id'])) > 0 or count(array_diff($odoo_customer['category_id'], $tags)) > 0) // Compare arrays of Odoo and Ciel tags/groups
                // cfr https://stackoverflow.com/questions/29643834/how-to-add-tags-category-id-while-creating-customer-res-partner-in-odoo 
                // and https://www.odoo.com/documentation/12.0/developer/reference/orm.html#openerp-models-relationals-format 
                // (5, _, _)
                // removes all records from the set, equivalent to using the command 3 on every record explicitly. Can not be used in create().
                // (6, _, ids)
                // replaces all existing records in the set by the ids list, equivalent to using the command 5 followed by a command 4 for each id in ids.
                $updates['category_id'] = (count($tags) > 0) ? array(array(6, 0, $tags)) : array(array(5, 0, 0)); 
            if (count($updates) > 0) { // There were some changes, let's update the Odoo record
                $response = $odooClient->Update('res.partner', array($odoo_customer['id']), $updates) ;
                journalise($userId, "I", "Odoo partner " . $odoo_customer['id'] . " (" . $odoo_customer['email'] . ") updated for member $name_from_db: " . json_encode($updates)) ;
            } 
        } // Join over email is possible
    } // While all members
} // odooSynchronize
?>