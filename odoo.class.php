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

    function __construct($host, $db, $user, $password) {
        global $userId ;

        $this->host = $host ;
        $this->db = $db ;
        $this->user = $user ;
        $this->password = $password ;
        $this->encoder = new PhpXmlRpc\Encoder() ;

        $this->common = new PhpXmlRpc\Client("https://$host/xmlrpc/2/common");
        $this->common->setOption(PhpXmlRpc\Client::OPT_RETURN_TYPE, PhpXmlRpc\Helper\XMLParser::RETURN_PHP);
        $params = array(new PhpXmlRpc\Value($db), 
            new PhpXmlRpc\Value($user), 
            new PhpXmlRpc\Value($password),
            new PhpXmlRpc\Value(array(), 'array')) ;
        $response = $this->common->send(new PhpXmlRpc\Request('authenticate', $params)) ;
        if (!$response->faultCode()) {
            $this->uid = $response->value() ;
            journalise($userId, "D", "Connected to Odoo $host as $user with UID $this->uid") ;
        } else {
            journalise($userId, "F", "Cannot connect to Odoo $host as $user: " . htmlentities($response->faultCode()) . "\n" . "Reason: '" .
                htmlentities($response->faultString()));
        }
        $this->models = new PhpXmlRpc\Client("https://$host/xmlrpc/2/object");
        $this->models->setOption(PhpXmlRpc\Client::OPT_RETURN_TYPE, PhpXmlRpc\Helper\XMLParser::RETURN_PHP);
    }

    # Search one model based on $filters (to select some rows) and $display (returned values)
    # Examples:
    # $result = $odooClient->SearchRead('res.partner', array(), 
    #    array('fields'=>array('id', 'name', 'vat', 'property_account_receivable_id', 'total_due',
    #    'street', 'street2', 'zip', 'city', 'country_id', 
    #   'complete_name', 'email', 'mobile', 'commercial_company_name'))) ;
    # $result = $odooClient->SearchRead('account.account', array(array(array('account_type', '=', 'asset_receivable'))), array()) ; 
    function SearchRead($model, $filters, $display) {
        global $userId ;

        $params = $this->encoder->encode(array($this->db, $this->uid, $this->password, $model, 'search_read', $filters, $display)) ;
        $response = $this->models->send(new PhpXmlRpc\Request('execute_kw', $params));
        if ($response->faultCode()) {
            journalise($userId, "F", "Cannot search_read in Odoo model $model @ $this->host: " . 
                htmlentities($response->faultCode()) . "\n" . "Reason: '" . htmlentities($response->faultString()));
        }
        return $response->value() ;
    }
}
?>