<?php
//
// THIS FILE IS A TEMPLATE: copy it to auth.php and tailor it to your installation.
// auth.php contains CONFIDENTIAL information: never commit it, never save it in a public space.
//
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

// Mysqli credentials
$db_name = '*your database name*' ;
$db_user = '*your username*' ;
$db_password = '*your secret*' ;
$db_host = 'db.example.org' ;

DEFINE('SITE_HOST', 'www.example.org') ;
DEFINE('SITE_PATH', 'resa') ;
DEFINE('SITE_URL', 'https://' . SITE_HOST . '/'. SITE_PATH . '/') ;
DEFINE('SITE_ICON', '<i class="bi bi-lightning-charge"></i>') ;
DEFINE('SITE_MAINTENANCE', false) ; // Whether the site is under maintenance, reserved to IT guys

// Some specific paths per deployment
DEFINE('DOCUMENTS_DIRECTORY', '../Documents_example') ;

// Payconiq information
$pcnq_api_key = '*your secret*' ;
$pcnq_merchant_id = '*your secret*' ;

// MapBox integration
$mapbox_token = '*your secret*' ;

// Google Map geocoding API
$gmap_api_key = '*your secret*' ;

// Facebook API credentials
$fb_app_id = '*your secret*' ;
$fb_app_secret = '*your secret*' ;

// Google OAuth credentials
$google_client_id = '*your secret*' ;
$google_client_secret = '*your secret*' ;

// LinkedIn OAuth credentials
$linkedin_client_id = '*your secret*' ;
$linkedin_client_secret = '*your secret*' ;

// SMTP credentials
$smtp_user = '*your username*' ;
$smtp_password = '*your secret*' ;
// SMTP local parameters
$smtp_host = 'smtp.example.org' ;
$smtp_port = 587 ; // Some hosters (e.g., OVH) block 587: use another port such as 465 (TLS) if needed
$smtp_secure = 'tls' ;
$smtp_from = 'no-reply@example.org' ;
$smtp_localhost = 'example.org' ;

// Credential for email account sending invoices
$invoice_imap = 'imap.example.org' ;
$invoice_folder = 'INBOX.Sent' ;
$invoice_user = '*your username*' ;
$invoice_psw = '*your secret*' ;

// Credential for the finances SMTP account
$finances_smtp_user = '*your username*' ;
$finances_smtp_password = '*your secret*' ;

// The shared secret for direct access to booking
$shared_secret = '*your secret*' ;

// The secret key for OAuth-light MCP agent authentication
$mcp_secret = '*your secret*' ;

// Odoo instance (a cookie allows switching to a test instance)
if (!isset($_COOKIE['odoo']) or $_COOKIE['odoo'] == 'default') {
	$odoo_host = 'example.odoo.com' ;
	$odoo_db = 'example' ;
} else if ($_COOKIE['odoo'] == 'example-test') {
	$odoo_host = 'example-test.odoo.com' ;
	$odoo_db = 'example-test' ;
} else if ($_COOKIE['odoo'] == 'example') {
	$odoo_host = 'example.odoo.com' ;
	$odoo_db = 'example' ;
}
$odoo_username = '*your username*' ;
$odoo_password = '*your secret*' ; // Odoo API key
?>
