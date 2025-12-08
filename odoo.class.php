<?php
/*
   Copyright 2023-2025 Eric Vyncke

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
ini_set('display_errors', 1) ; // extensive error reporting for debugging

class OdooClient {
    public $uid ;
    public $host ;
    public $db ;
    public $user ;
    public $password ;
    public $url ;
    public $debug ;
    public $exitOnError ; // if true, then die/journalise(, 'F') on PhpXmlRpc error 
    public $errorMessage ;

    // Ancilliary function
    function rpc($service, $method, $args) {
        global $userId ;

        $payload = [
            "jsonrpc" => "2.0",
            "method"  => "call",
            "params"  => [
                "service" => $service,
                "method"  => $method,
                "args"    => $args
            ],
            "id" => rand(1, 100000)
        ];

        $options = [
            "http" => [
                "header"  => "Content-Type: application/json",
                "method"  => "POST",
                "content" => json_encode($payload)
            ]
        ];

        $context  = stream_context_create($options);
        $result = file_get_contents($this->url, false, $context);
        if ($result === FALSE) {
            journalise($userId, "F", "Cannot connect to $this->url") ;
        }
        if ($this->debug) {
            print("<h3>After $service/$method") ;
            if (isset($args[4])) print(" $args[4]") ;
            if (isset($args[3])) print(" on $args[3]") ;
            print("</h3><pre>$result</pre>") ;
        }
        return json_decode($result, true);
}

    function __construct($host, $db, $user, $password, $exitOnError = TRUE) {
        global $originUserId ;

        $this->host = $host ;
        $this->url = "https://$host/jsonrpc" ;
        $this->db = $db ;
        $this->user = $user ;
        $this->password = $password ;
        $this->debug = false ;
        $this->exitOnError = $exitOnError ;

        $auth = $this->rpc('common', 'authenticate', [$db, $user, $password, []]);

        $this->uid = $auth['result'];
        if (! $this->uid) {
            journalise($originUserId, "F", "Odoo authentication failure for $user on $db @ $host");
        }
    }

    # Read return all records from one model based on their IDs
    function Read($model, $ids, $display) {
        global $originUserId ;

        $result = $this->rpc("object", "execute_kw", [
            $this->db, $this->uid, $this->password,
            $model, "read", $ids, $display]);
        if (isset($result['error'])) {
            $this->errorMessage = "Cannot read in Odoo model $model @ $this->host: " . 
                htmlentities($result['error']['message']) . "Reason: '" . htmlentities($result['error']['data']['debug']) ;
            if ($this->debug)
                die("<hr><b>$this->errorMessage</b><hr>") ;
            else if ($this->exitOnError)
                journalise($originUserId, "F", $this->errorMessage);
            else { // ! $exitOnError
                journalise($originUserId, "E", $this->errorMessage);
                return NULL ;
            }       
        }
        
        return $result['result'] ;
    }

    # Search one model based on $domain_filter (to select some rows) and $display (returned values)
    # Examples:
    # $result = $odooClient->SearchRead('res.partner', array(), 
    #    array('fields'=>array('id', 'name', 'vat', 'property_account_receivable_id', 'total_due',
    #    'street', 'street2', 'zip', 'city', 'country_id', 
    #   'complete_name', 'email', 'mobile', 'commercial_company_name'))) ;
    # $result = $odooClient->SearchRead('account.account', array(array(array('account_type', '=', 'asset_receivable'))), array()) ; 
    #
    # Could also contain: array('offset'=>10, 'limit'=>5, 'order=>col1,col2')
    function SearchRead($model, $domain_filter, $display) {
        global $originUserId ;

        $result = $this->rpc("object", "execute_kw", [
            $this->db, $this->uid, $this->password,
            $model, "search_read", $domain_filter, $display]);
        if (isset($result['error'])) {
            $this->errorMessage = "Cannot search_read in Odoo model $model @ $this->host: " . 
                htmlentities($result['error']['message']) . "Reason: '" . htmlentities($result['error']['data']['debug']) ;
            if ($this->debug)
                die("<hr><b>$this->errorMessage</b><hr>") ;
            else if ($this->exitOnError)
                journalise($originUserId, "F", $this->errorMessage);
            else { // ! $exitOnError
                journalise($originUserId, "E", $this->errorMessage);
                return NULL ;
            }       
        }
        
        return $result['result'] ;
    }

    # Updating existing $model rows whose ids are in $ids with mapping $mapping
    # Example:
    # $response = $odooClient->Update('res.partner', array(2186, 2058), array('property_account_receivable_id' => $account_id)) ;
    #
    # Many2Many requires an array of triplets whose first part is the action
    # See https://www.odoo.com/documentation/12.0/developer/reference/orm.html#openerp-models-relationals-format 
    # E.g.,  $mapping['category_id'] = array(array(6, 0, $tags))
    function Update($model, $ids, $mapping) {
        global $originUserId ;

        $result = $this->rpc("object", "execute_kw", [
            $this->db, $this->uid, $this->password,
            $model, "write", [$ids, $mapping]]);
        if (isset($result['error'])) {
            $this->errorMessage = "Cannot write in Odoo model $model @ $this->host: " . 
                htmlentities($result['error']['message']) . "Reason: '" . htmlentities($result['error']['data']['debug']) ;
            if ($this->debug)
                die("<hr><b>$this->errorMessage</b><hr>") ;
            else if ($this->exitOnError)
                journalise($originUserId, "F", $this->errorMessage);
            else { // ! $exitOnError
                journalise($originUserId, "E", $this->errorMessage);
                return NULL ;
            }       
        }
        
        return $result['result'] ;
    }

    # Create a new row in $model  with mapping $mapping
    # Example:
    #  $odooClient->Create('account.account', array(
    # 'name' => $fullName,
    #    'account_type' => 'asset_receivable',
    #    'internal_group' => 'asset',
    #    'code' => $code,
    #    'name' => "$code $fullName")) ;
    # Many2Many requires an array of triplets whose first part is the action
    # See https://www.odoo.com/documentation/12.0/developer/reference/orm.html#openerp-models-relationals-format 
    function Create($model, $mapping) {
        global $originUserId ;

        $result = $this->rpc("object", "execute_kw", [
            $this->db, $this->uid, $this->password,
            $model, "create", [$mapping]]);
        if (isset($result['error'])) {
            $this->errorMessage = "Cannot search_read in Odoo model $model @ $this->host: " . 
                htmlentities($result['error']['message']) . "Reason: '" . htmlentities($result['error']['data']['debug']) ;
            if ($this->debug)
                die("<hr><b>$this->errorMessage</b><hr>") ;
            else if ($this->exitOnError)
                journalise($originUserId, "F", $this->errorMessage);
            else { // ! $exitOnError
                journalise($originUserId, "E", $this->errorMessage);
                return NULL ;
            }       
        }
        
        return $result['result'] ;
    }

    # Get all fields from a model
    # Example:
    # var_dump($odooClient->GetFields('account.move')) ;
    function GetFields($model, $keys = array('string', 'help', 'type', 'description')) {
        global $originUserId ;
        
        $result = $this->rpc("object", "execute_kw", [
            $this->db, $this->uid, $this->password,
            $model, "fields_get",  [], ['attributes' => $keys]]);
        if (isset($result['error'])) {
            $this->errorMessage = "Cannot fields_get in Odoo model $model @ $this->host: " . 
                htmlentities($result['error']['message']) . "Reason: '" . htmlentities($result['error']['data']['debug']) ;
            if ($this->debug)
                die("<hr><b>$this->errorMessage</b><hr>") ;
            else if ($this->exitOnError)
                journalise($originUserId, "F", $this->errorMessage);
            else { // ! $exitOnError
                journalise($originUserId, "E", $this->errorMessage);
                return NULL ;
            }       
        }
        
        return $result['result'] ;
    }

    # Get the category ID from its roleName, create it if not existing
    # Role being 'student', 'member', ...
    function GetOrCreateCategory($roleName) {
        static $cache = array() ;

        if (isset($cache[$roleName])) 
            return $cache[$roleName] ;
        $categories = $this->SearchRead('res.partner.category', 
            array(array(array('name', '=', $roleName))), 
            array('fields'=>array('id', 'name'))) ;
        if (count($categories) > 0) {
            $cache[$roleName] = $categories[0]['id'] ;
            return $categories[0]['id'] ;
        } else {
            die("Creating Odoo category '$roleName'") ;
            $newCategoryId = $this->Create('res.partner.category', 
                array('name' => $roleName)) ;
            $cache[$roleName] = $newCategoryId ;
            return $newCategoryId ;
        }
    }
}
?>