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
ini_set('display_errors', 1) ; // extensive error reporting for debugging
require __DIR__ . '/vendor/autoload.php' ;

class OdooClient {
    public $common ; // Should probably be private
    public $models ; // Should probably be private
    public $uid ;
    public $encoder ;
    public $host ;
    public $db ;
    public $user ;
    public $password ;
    public $debug ;

    function __construct($host, $db, $user, $password) {
        global $originUserId ;

        $this->host = $host ;
        $this->db = $db ;
        $this->user = $user ;
        $this->password = $password ;
        $this->encoder = new PhpXmlRpc\Encoder() ;
        $this->debug = false ;

        $this->common = new PhpXmlRpc\Client("https://$host/xmlrpc/2/common");
        $this->common->setOption(PhpXmlRpc\Client::OPT_RETURN_TYPE, PhpXmlRpc\Helper\XMLParser::RETURN_PHP);
        $params = array(new PhpXmlRpc\Value($db), 
            new PhpXmlRpc\Value($user), 
            new PhpXmlRpc\Value($password),
            new PhpXmlRpc\Value(array(), 'array')) ;
        $response = $this->common->send(new PhpXmlRpc\Request('authenticate', $params)) ;
        if (!$response->faultCode()) {
            $this->uid = $response->value() ;
        } else {
                if ($this->debug)
                    die("<hr><b>Cannot connect to Odoo $host as $user:</b><br/>
                        Reason: '" . nl2br($response->faultString()) . "'<hr>") ;
                else
                    journalise($originUserId, "F", "Cannot connect to Odoo $host as $user. " . 
                        "Reason: '" . htmlentities($response->faultString()));
        }
        $this->models = new PhpXmlRpc\Client("https://$host/xmlrpc/2/object");
        $this->models->setOption(PhpXmlRpc\Client::OPT_RETURN_TYPE, PhpXmlRpc\Helper\XMLParser::RETURN_PHP);
    }

    # Read return all records from one model based on their IDs
    function Read($model, $ids, $display) {
        global $originUserId ;

        $params = $this->encoder->encode(array($this->db, $this->uid, $this->password, $model, 'read', array($ids), $display)) ;
        $response = $this->models->send(new PhpXmlRpc\Request('execute_kw', $params));
        if ($response->faultCode()) {
            if ($this->debug)
                die("<hr><b>Cannot read in Odoo model $model @ $this->host</b><br/>
                    Faultcode: " . htmlentities($response->faultCode()) . "<br/>
                    Reason: '" . nl2br($response->faultString()) . "'<hr>") ;
            else
                journalise($originUserId, "F", "Cannot read in Odoo model $model @ $this->host: " . 
                    htmlentities($response->faultCode()) . "\n" . "Reason: '" . htmlentities($response->faultString()));
        }
        return $response->value() ;
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

        $params = $this->encoder->encode(array($this->db, $this->uid, $this->password, $model, 'search_read', $domain_filter, $display)) ;
        $response = $this->models->send(new PhpXmlRpc\Request('execute_kw', $params));
        if ($response->faultCode()) {
            if ($this->debug)
                die("<hr><b>Cannot search_read in Odoo model $model @ $this->host</b><br/>
                    Faultcode: " . htmlentities($response->faultCode()) . "<br/>
                    Reason: '" . nl2br($response->faultString()) . "'<hr>") ;
            else
                journalise($originUserId, "F", "Cannot search_read in Odoo model $model @ $this->host: " . 
                    htmlentities($response->faultCode()) . "\n" . "Reason: '" . htmlentities($response->faultString()));
        }
        
        return $response->value() ;
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

        $params = $this->encoder->encode(array($this->db, $this->uid, $this->password, $model, 'write', array($ids, $mapping))) ;
        $response = $this->models->send(new PhpXmlRpc\Request('execute_kw', $params));
        if ($response->faultCode()) {
            $ids_string = implode(',', $ids) ;
            if ($this->debug) {
                die("<hr><b>Cannot update($model, [$ids_string], " . var_export($mapping) . ") in Odoo</b><br/>
                    Faultcode: " . htmlentities($response->faultCode()) . "<br/>
                    Reason: '" . nl2br($response->faultString()) . "'<hr>") ;
            } else
                journalise($originUserId, "F", "Cannot update(model, [ids_string]) in Odoo: " .
                    htmlentities($response->faultCode()) . "\n" . "Reason: '" . htmlentities($response->faultString()));
                return null ;
        }
        return $response->value() ;
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

        $params = $this->encoder->encode(array($this->db, $this->uid, $this->password, $model, 'create', array($mapping))) ;
        $response = $this->models->send(new PhpXmlRpc\Request('execute_kw', $params));
        if ($response->faultCode()) {
            journalise($originUserId, "F", "Cannot create in Odoo model $model @ $this->host: " . 
                htmlentities($response->faultCode()) . "\n" . "Reason: '" . htmlentities($response->faultString()));
        }
        return $response->value() ;
    }

    # Get all fields from a model
    # Example:
    # var_dump($odooClient->GetFields('account.move')) ;
    function GetFields($model, $keys = array('string', 'help', 'type', 'description')) {
        global $originUserId ;
        
        $params = $this->encoder->encode(array($this->db, $this->uid, $this->password, $model, 'fields_get', array(), array('attributes' => $keys))) ;
        $response = $this->models->send(new PhpXmlRpc\Request('execute_kw', $params));
        if ($response->faultCode()) {
            journalise($originUserId, "F", "Cannot get all fields of Odoo model $model @ $this->host: " . 
                htmlentities($response->faultCode()) . "\n" . "Reason: '" . htmlentities($response->faultString()));
        }
        return $response->value();
    }
}
?>