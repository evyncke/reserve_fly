<html>
	<body>
<?php
ini_set('display_errors', 1) ; // extensive error reporting for debugging
require_once('dbi.php') ;
print("<pre>\n") ;
require __DIR__ . '/vendor/autoload.php' ;
print("Library loaded\n") ;

// Alas, OVH does not allow XMLRPC library...
// Should go via https://github.com/gggeek/phpxmlrpc/blob/master/doc/manual/phpxmlrpc_manual.adoc#client ?

/* This was the RIPCORD use, a little simple
if (false) {
	# Let's connect via the common end-point using rip cord
	$ripcord = new Ripcord\Ripcord ;
	print("Ripcord created\n") ;
	$common = $ripcord::client("https://$host/xmlrpc/2/common");
	print("Common client set\n") ;
//	$version = $common->version();
//	print("Running $version[server_serie]\n") ;
	$uid = $common->authenticate($db, $username, $password, array());
	print("Connected: uid='$uid'\n") ;
exit ;
	# Getting models & keys is somehow easy with debugging enabled via the URL (the ?debug=1 before the #)
	# https://rapcs-test.odoo.com/web?debug=1#id=9&cids=1&menu_id=369&action=286&model=res.partner&view_type=form

	$models = $ripcord::client("https://$host/xmlrpc/2/object");

	$result = $models->execute_kw($db, $uid, $password, 'res.partner', 'search_read', array(),
	array('fields'=>array('id', 
'name',
'vat',
'property_account_receivable_id',
'complete_name',
'email',
'mobile',
'commercial_company_name')));
*/
	$common = new PhpXmlRpc\Client("https://$odoo_host/xmlrpc/2/common");
	$common->setOption(PhpXmlRpc\Client::OPT_RETURN_TYPE, PhpXmlRpc\Helper\XMLParser::RETURN_PHP);
	$response = $common->send(new PhpXmlRpc\Request('version', array()));
	$val = $response->value() ;
	print("Version: $val[server_serie]\n") ;
	$params = array(new PhpXmlRpc\Value($odoo_db), 
		new PhpXmlRpc\Value($odoo_username), 
		new PhpXmlRpc\Value($odoo_password),
		new PhpXmlRpc\Value(array(), 'array')) ;
	$response = $common->send(new PhpXmlRpc\Request('authenticate', $params)) ;
	if (!$response->faultCode()) {
		print("Connect with UID: " . $response->value() . "\n") ;
		$uid = $response->value() ;
	} else {
		print("Error...\n") ;
		print("Code: " . htmlentities($response->faultCode()) . "\n" . "Reason: '" .
        	htmlentities($response->faultString()));
	}
	$models = new PhpXmlRpc\Client("https://$odoo_host/xmlrpc/2/object");
	$models->setOption(PhpXmlRpc\Client::OPT_RETURN_TYPE, PhpXmlRpc\Helper\XMLParser::RETURN_PHP);
	$params = array(new PhpXmlRpc\Value($odoo_db), 
		new PhpXmlRpc\Value($uid), 
		new PhpXmlRpc\Value($odoo_password),
		new PhpXmlRpc\Value('res.partner'),
		new PhpXmlRpc\Value('search_read'),
		new PhpXmlRpc\Value(array(), 'array'),
		new PhpXmlRpc\Value(array(), 'array')) ;
	$encoder = new PhpXmlRpc\Encoder() ;
	$params = $encoder->encode(array($odoo_db, $uid, $odoo_password, 'res.partner', 'search_read', array(), array('fields'=>array('id', 
			'name',
			'vat',
			'property_account_receivable_id',
			'complete_name',
			'email',
			'mobile',
			'commercial_company_name')))) ;
	$response = $models->send(new PhpXmlRpc\Request('execute_kw', $params));
	if ($response->faultCode()) {
		print("Error...\n") ;
		print("Code: " . htmlentities($response->faultCode()) . "\n" . "Reason: '" .
        	htmlentities($response->faultString()));
		exit ;
	}
	$result = $response->value() ;

print("\nCustomers\n") ;
foreach($result as $client) {
	print("Client #$client[id]: $client[complete_name], $client[commercial_company_name], $client[vat], $client[email], " . 
		$client['property_account_receivable_id'][1] . " \n") ;
}

# Below is working fine
# $id = $models->execute_kw($db, $uid, $password, 'res.partner', 'create', array(array('name'=>"Vintens", 'complete_name'=>'Christine Vintens', 'email'=>'vintens.ch@gmail.com', 'street'=>'Val de Somme 1')));
# print("Created client $id\n") ;

# In order to get all ODOO models
#$result = $models->execute_kw($db, $uid, $password,'ir.model', 'search_read',array(), array('fields' => array('name','model','state'))) ;
#foreach($result as $model) {
#	print("$model[name]: $model[model]\n") ;
#}

# Display all booking moves, including invoices

$params = $encoder->encode(array($odoo_db, $uid, $odoo_password, 'account.move', 'search_read', array(array(array('move_type','=','out_invoice'))), array())) ;
$response = $models->send(new PhpXmlRpc\Request('execute_kw', $params));
if ($response->faultCode()) {
	print("Error...\n") ;
	print("Code: " . htmlentities($response->faultCode()) . "\n" . "Reason: '" .
		htmlentities($response->faultString()));
	exit ;
}
$result = $response->value() ;
print("\nAccounting moves (restricted to out_invoices)\n") ;
foreach($result as $account) {
	print("$account[id]: $account[sequence_prefix] $account[sequence_number], $account[activity_state], $account[name], $account[ref],
	$account[state],$account[type_name], " . $account['journal_id'][1] . ", " .
	$account['company_id'][1] . ", $account[amount_total], $account[display_name],
	$account[access_url], $account[access_token], $account[move_type],\n") ;
}

# Display the products
# Alas, the fields MUST be listed...

$params = $encoder->encode(array($odoo_db, $uid, $odoo_password, 'product.product', 'search_read', array(), 
	array('fields' => array('id', 'name', 'detailed_type', 'lst_price', 'standard_price', 'default_code', 'categ_id', 'property_account_income_id')))) ;
$response = $models->send(new PhpXmlRpc\Request('execute_kw', $params));
if ($response->faultCode()) {
	print("Error...\n") ;
	print("Code: " . htmlentities($response->faultCode()) . "\n" . "Reason: '" .
		htmlentities($response->faultString()));
	exit ;
}
$result = $response->value() ;
	 
 print("\nProducts\n") ;
foreach($result as $product) {
	print("id: $product[id], name: $product[name], detailed_type: $product[detailed_type], default_code: $product[default_code],
	prices: $product[lst_price] / $product[standard_price] => " . (($product['property_account_income_id']) ? $product['property_account_income_id'][1] : '') . "\n") ;
}

# To send an invoice:
# https://www.odoo.com/fr_FR/forum/aide-1/create-a-customer-invoice-using-the-api-how-to-demo-this-via-a-scheduled-action-206177
# model is: account.move
# action is: create
# parameters:
# - partner_id (id from res.partner)
# - ref: free text as the invoice reference
# - type: 'out_invoice'
# - invoice_line_ids: for the invoice lines ;-), each line is an array of 3 elements ?
#	1st and 2nd are simply 0
#	3rd one is {
#		'product_id': xyz (from product.product model)
#		'quantity': xyz (integer)
#		'price_unit': xyz (float)


?>
</pre>
</body>
</html>