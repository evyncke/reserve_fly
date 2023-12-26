<html>
	<body>
<?php
ini_set('display_errors', 1) ; // extensive error reporting for debugging
require_once('dbi.php') ;
require_once('odoo.class.php') ;
print("<pre>\n") ;
print("Library loaded\n") ;

$odoo_host = 'rapcs2.odoo.com' ;
$odoo_password = '3a6b53d48867453eedcd274ccc3bdfb887b08071' ;
$odoo_db = 'rapcs2' ;

$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;

# For dirty attempts...
$common = $odooClient->common;
$models = $odooClient->models ;
$uid = $odooClient->uid ;
$encoder = $odooClient->encoder ;

print("Connect with UID: $uid\n") ;

#Account #427: FX Engineering, 400FX, 400FX FX Engineering, asset_receivable, asset, 400 Customers, 400 Customers
#Account #426: Reginster Patrick, 400REGP, 400REGP Reginster Patrick, asset_receivable, asset, 400 Customers, 400 Customers
$result = $odooClient->SearchRead('account.account', array(array(array('account_type', '=', 'asset_receivable'))), array()) ; 

print("\nAccounts (account_type == asset_receivable)\n") ;
foreach($result as $account) {
	print("Account #$account[id]: $account[name], $account[code], $account[display_name], $account[account_type], $account[internal_group], " . 
		$account['group_id'][1] . ", " . $account['group_id'][1] . "\n") ;
	if ($account['display_name'] == '400REGP REGINSTER Patrick') {
		$account_id = $account['id'] ;
		print("Found it: $account_id\n") ;
	}
}

# Test de modification d'un client
# in partner.py
#     property_account_receivable_id = fields.Many2one('account.account', company_dependent=True,
# string="Account Receivable",
# domain="[('account_type', '=', 'asset_receivable'), ('deprecated', '=', False)]",
# help="This account will be used instead of the default one as the receivable account for the current partner",
# required=True)
#
# https://www.odoo.com/documentation/16.0/developer/reference/backend/orm.html#reference-orm-model

$response = $odooClient->Update('res.partner', array(2186, 2058), array('property_account_receivable_id' => $account_id)) ;

// print("\nZoom sur customer\n") ;
// $result = $odooClient->SearchRead('res.partner', array(array(array('id', '=', 2186))), array()) ; 
// var_dump($result) ;

# Client #998: Borauke Maxime, , , maxborauke@outlook.fr, 400000 Membres 
$result = $odooClient->SearchRead('res.partner', array(), array('fields'=>array('id', 
	'name',
	'vat',
	'property_account_receivable_id',
	'complete_name',
	'email',
	'mobile',
	'commercial_company_name'))) ; 


print("\nCustomers\n") ;
foreach($result as $client) {
	print("Client #$client[id]: $client[complete_name], $client[commercial_company_name], $client[vat], $client[email], $client[mobile], " . 
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

# Display all out_invoices accounting moves
print("\nAccounting moves (restricted to out_invoices)\n") ;
$result = $odooClient->SearchRead('account.move', array(array(array('move_type','=','out_invoice'))), 
	array('fields' => array('id', 'sequence_prefix', 'sequence_number', 'activity_state', 'name', 'ref',
		'state', 'type_name', 'journal_id', 'company_id', 'amount_total', 'display_name',
		'access_url', 'access_token', 'move_type'))) ;
foreach($result as $account) {
	print("$account[id]: $account[sequence_prefix] $account[sequence_number], $account[activity_state], $account[name], $account[ref],
	$account[state],$account[type_name], " . $account['journal_id'][1] . ", " .
	$account['company_id'][1] . ", $account[amount_total], $account[display_name],
	$account[access_url], $account[access_token], $account[move_type],\n") ;
}

# Display the products
# Alas, the fields MUST be listed...

$result = $odooClient->SearchRead('product.product', array(), array('fields' => array('id', 'name', 'detailed_type', 'lst_price', 'standard_price', 'default_code', 'categ_id', 'property_account_income_id'))) ;
	 
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
# - move_type: 'out_invoice'
# - invoice_line_ids: for the invoice lines ;-), each line is an array of 3 elements ?
#	1st and 2nd are simply 0
#	3rd one is {
#		'product_id': xyz (from product.product model)
#		'quantity': xyz (integer)
#		'price_unit': xyz (float)

exit ;

print("\nCreating invoice\n") ;

$params = $encoder->encode(array($odoo_db, $uid, $odoo_password, 'account.move', 'create',
	array(array('partner_id' => 728,
		'ref' => 'Test invoice generated from PHP',
		'move_type' => 'out_invoice',
		'invoice_origin' => 'Spa Aviation Bookings',
		'invoice_line_ids' => array(
			array(0, 0,
					array(
						'name' => 'Vol Benoît du 32 décembre',
						'product_id' => 3,
						'quantity' => 60,
						'price_unit' => 0.4
					)
					),
			array(0, 0,
				array(
					'name' => 'Vol OO-FMX du 32 décembre',
					'product_id' => 2,
					'quantity' => 60,
					'price_unit' => 2.5
				)
		)

		)
		)))
) ;
$response = $models->send(new PhpXmlRpc\Request('execute_kw', $params));
if ($response->faultCode()) {
	print("Error...\n") ;
	print("Code: " . htmlentities($response->faultCode()) . "\n" . "Reason: '" .
		htmlentities($response->faultString()));
	exit ;
}
$result = $response->value() ;
var_dump($result) ;
print("Invoicing result: $result\n") ;

?>
</pre>
</body>
</html>