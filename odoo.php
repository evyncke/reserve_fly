<html>
	<body>
<?php
ini_set('display_errors', 1) ; // extensive error reporting for debugging
require_once('dbi.php') ;
require_once('odoo-json.class.php') ;
print("<pre>\n") ;
print("Library loaded\n") ;

$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;

# For dirty attempts...
$uid = $odooClient->uid ;
$odooClient->debug = true ;

print("Connect with UID: $uid\n") ;

$result = $odooClient->Read('res.partner', array(array(32, 37)), array('fields' => array('complete_name', 'lang'))) ;

$result = $odooClient->Update('res.partner', array(32, 37), array('lang' => 'fr_BE')) ;

$result = $odooClient->Read('res.partner', array(array(32, 37)), array('fields' => array('complete_name', 'lang'))) ;

var_dump($odooClient->GetFields('account.move')) ;

exit ;

$result= $odooClient->SearchRead('account.move', 
	array(array(
//		array('partner_id.id', '=', 755),
//array('status_in_payment', '!=', 'paid'),
		array('invoice_date_due', '<', date('Y-m-d')),
		array('move_type', '=', 'out_invoice'),
		array('state', '=', 'posted'),
		'|', array('payment_state', '=', 'not_paid'), array('payment_state', '=', 'partial'),
)),  
	array('fields'=>array('partner_id', 'invoice_date_due', 'amount_total', 'payment_state'))); 

print_r($result) ;
print("Length = " . count($result) . "\n") ;
$DueDate = '' ;
foreach($result as $f=>$desc) {
       var_dump($desc);
       print('<br>');
        $status_in_payment=(isset($desc['status_in_payment'])) ? $desc['status_in_payment'] : '' ;
        if($status_in_payment=="paid") {
            // Already paid => not_paid and partial
            continue;
        }
        $invoiceDueDate=(isset($desc['invoice_date_due'])) ? $desc['invoice_date_due'] : '' ;
        $invoiceDate=(isset($desc['invoice_date'])) ? $desc['invoice_date'] : '' ;
		print("invoideDueDate = $invoiceDueDate, invoiceDate = $invoiceDate
		") ;
        if($DueDate=="" || $invoiceDueDate<$DueDate) {
            $DueDate=$invoiceDueDate;
        }
}
print('</pre>') ;

# Display all members with coordinates

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


print("\nCustomers\n") ;
foreach($result as $client) {
	if (in_array($member_tag, $client['category_id']))
		print("Client #$client[id]: $client[complete_name], $client[partner_latitude], $client[partner_longitude], $client[email], $client[mobile],
		$client[street], $client[city] \n") ;
}

exit ;

# Display all out_invoices accounting moves 102 = françois, 223 = francois engineerin, 32 = eric, 44 = Mendes
print("\nAccounting moves\n") ;
$result = $odooClient->SearchRead('account.move', 
	array(array(
		array('state', '=', 'posted'),
		array('date', '>' , '2023-12-31'),
		'|', 
		array('commercial_partner_id', '=', 44), 
		array('partner_id', '=', 44))), 
	array('fields' => array('id', 'activity_state', 'name', 'ref',
		'state', 'type_name', 'journal_id', 'amount_total',  'partner_id', 'commercial_partner_id',
		'move_type'),
		'order' => 'id')) ;
// var_dump($result) ;
foreach($result as $account) {
	print("$account[id]: $account[name], $account[ref], $account[type_name], " . $account['journal_id'][1] . ", " .
		$account['partner_id'][0] . ", " . $account['commercial_partner_id'][0] . ", " .
		"$account[move_type], $account[amount_total] €\n") ;
}

print("\nAccounting move lines\n") ;
//$result = $odooClient->Read('account.move', 
//	array(5848, 6400), 
//	array()
//	) ;
$result = $odooClient->Read('account.move.line', 
//	array(14205, 14206, 16221, 16222, 15657, 15658), François Henquet
	array(14515, 14517, 14528, 14529),
	array('fields' => array('id', 'name', 'move_id', 'move_name', 'ref', 'move_type',
	'debit', 'credit', 'balance', // work as expected
	'partner_id', // always the same
//	'matched_debit_ids','matched_credit_ids', // not useful
'account_type', // Semble prometeur, ne prendre que asset_receivable ignorer asset_cash
'account_id' // aussi prometteur, ne prendre que les comptes 4xxxxx
	))
	) ;
var_dump($result) ;
exit ;
foreach($result as $account) {
	print("$account[id]: $account[name], $account[ref], $account[type_name], " . $account['journal_id'][1] . ", " .
		$account['partner_id'][0] . ", " . $account['commercial_partner_id'][0] . ", " .
		"$account[move_type], $account[amount_total] €\n") ;
}

exit ;

$result = $odooClient->SearchRead('account.bank.statement.line', array(
//	array('id', '=', '1416')
), 
	array('fields' => array('id', 'date', 'amount', 'name', 'move_id', 'statement_id', 'account_number', 'partner_name', 'statement_name', 'display_name',
	'payment_ref',
	'online_account_id', 'ref', 'move_type', 'journal_id', 'statement_line_id'))) ; 
// online_account_id [1, "BE64732038421852 EUR (1852)"] ou [3, "BE14000078161283 (1283)"]
// journal_id [13, "Banque CBC BE64 7320 3842 1852"] ou [15, "BPOST BE14 0000 7816 1283"]
// name, move_id (to many), display_name BNK2/2024/00029
// amount = float positive if paid to us, negative when we pay
// payment_ref = "MME CHRISTINE FOSTIEZ Virement GRAND MARAIS(OL),38/A 7866 LESSINES BE24 8601 1198 2438 BIC: NICABEBBXXX BON CADEAU V-INIT-242207 DE CHRISTINE FOSTIEZ 12-02-24"

print("\nSearching for ...\n") ;
$i = 0 ;
foreach($result as $line) {
	var_dump($line) ;
	if ($i++ > 2 ) break ;
}

print("account.bank.statement\n") ;

$result = $odooClient->SearchRead('account.bank.statement', array(
//	array('id', '=', '1416')
),
	array(
//	array('fields' => array('id', 'date', 'amount', 'name', 'move_id', 'statement_id', 'account_number', 'partner_name', 'statement_name', 'display_name',
//	'payment_ref',
//	'online_account_id', 'ref', 'move_type', 'journal_id', 'statement_line_id')
	)) ; 
// online_account_id [1, "BE64732038421852 EUR (1852)"] ou [3, "BE14000078161283 (1283)"]
// journal_id [13, "Banque CBC BE64 7320 3842 1852"] ou [15, "BPOST BE14 0000 7816 1283"]
// name, move_id (to many), display_name BNK2/2024/00029
// amount = float positive if paid to us, negative when we pay
// payment_ref = "MME CHRISTINE FOSTIEZ Virement GRAND MARAIS(OL),38/A 7866 LESSINES BE24 8601 1198 2438 BIC: NICABEBBXXX BON CADEAU V-INIT-242207 DE CHRISTINE FOSTIEZ 12-02-24"

print("\n\nSearching in account.bank.statement\n\n") ;
var_dump($result) ;
$i = 0 ;
foreach($result as $line) {
	var_dump($line) ;
	if ($i++ > 5 ) exit ;
}

print("====\n End of job\n</pre>") ;
exit ;

#Account #427: FX Engineering, 400FX, 400FX FX Engineering, asset_receivable, asset, 400 Customers, 400 Customers
#Account #426: Reginster Patrick, 400REGP, 400REGP Reginster Patrick, asset_receivable, asset, 400 Customers, 400 Customers
$result = $odooClient->SearchRead('account.account', array(array(
		array('account_type', '=', 'asset_receivable'),
		array('code', '=', '400REGP'))), 
	array()) ; 
print("\nSearching for Patrick...\n") ;
foreach($result as $account) {
	print("Account #$account[id]: $account[name], $account[code], $account[display_name], $account[account_type], $account[internal_group], " . 
		$account['group_id'][1] . "\n") ;
	$account_id = $account['id'] ;
}

$result = $odooClient->SearchRead('account.account', array(array(
	array('account_type', '=', 'asset_receivable'),
	array('code', '=', '400REGP'))), 
array()) ; 
print("\nSearching for Patrick...\n") ;
foreach($result as $account) {
print("Account #$account[id]: $account[name], $account[code], $account[display_name], $account[account_type], $account[internal_group], " . 
	$account['group_id'][1] . "\n") ;
$account_id = $account['id'] ;
var_dump($account) ;
}

if (true) {
$result = $odooClient->SearchRead('account.account', array(array(array('account_type', '=', 'asset_receivable'))), array()) ; 
print("\nAccounts (account_type == asset_receivable)\n") ;
foreach($result as $account) {
	print("Account #$account[id]: $account[name], $account[code], $account[display_name], $account[account_type], $account[internal_group], " . 
		$account['group_id'][1] . "\n") ;
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

# $response = $odooClient->Update('res.partner', array(2186, 2188, 2058), array('property_account_receivable_id' => $account_id)) ;

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

# var_dump($odooClient->GetFields('account.move')) ;

# Display the products
# Alas, the fields MUST be listed...

$result = $odooClient->SearchRead('product.product', array(), array('fields' => array('id', 'name', 'detailed_type', 'lst_price', 'standard_price', 'default_code', 'categ_id', 'property_account_income_id'))) ;
	 
print("\nProducts\n") ;
foreach($result as $product) {
	print("id: $product[id], name: $product[name], detailed_type: $product[detailed_type], default_code: $product[default_code],
		prices: $product[lst_price] / $product[standard_price] => " . (($product['property_account_income_id']) ? $product['property_account_income_id'][1] : '') . "\n") ;
}

# var_dump($odooClient->GetFields('product.product')) ;
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