<html>
	<body>
<?php
ini_set('display_errors', 1) ; // extensive error reporting for debugging
require_once('dbi.php') ;
require_once('odoo.class.php') ;
print("<pre>\n") ;
print("Library loaded\n") ;

$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;

# For dirty attempts...
$common = $odooClient->common;
$models = $odooClient->models ;
$uid = $odooClient->uid ;
$encoder = $odooClient->encoder ;

function resize($img, $width, $height, $size) {
	$width_resize = $width / $size ;
	$height_resize = $height / $size ;
	$actual_resize = max($width_resize, $height_resize) ;
	$new_width = $width / $actual_resize ;
	$new_height = $height / $actual_resize ;
	$new_img = imagecreatetruecolor($new_width, $new_height);
	imagecopyresized($new_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height) ;
	ob_flush() ;
	ob_start() ;
	imagejpeg($new_img, NULL) ;
	$bytes = ob_get_contents() ;
	ob_end_clean() ;
	return $bytes ;
}

$result = mysqli_query($mysqli_link, "SELECT * 
	FROM jom_kunena_users k 
		JOIN $table_person p ON p.jom_id = k.userid 
		JOIN jom_users j ON j.id = k.userid
	WHERE k.avatar IS NOT NULL AND p.odoo_id IS NOT NULL AND j.block = 0 AND k.avatar NOT LIKE '%.jp%g'")
	or print(mysqli_error($mysqli_link)) ;
while (false and $row = mysqli_fetch_array($result)) {
	print("Processing #$row[jom_id], " . db2web($row['name']) . ": $row[avatar]\n") ;
	$fname = '../media/kunena/avatars/' . $row['avatar'] ;
	// TODO process gravatars ! https://www.gravatar.com/avatar/" . md5(strtolower(trim($row['email']))) . "?s=200&d=blank&r=pg\"
	if (!file_exists($fname)) {
		print("Skipping, file $fname does not exist.\n") ;
		continue ;
	}
	list($width, $height, $type, $attr) = getimagesize($fname) ;
	print("  Image size W x H: $width x $height\n") ;
	// TODO also support PNG based on $type (both in code below but also in the above SELECT)
	if ($type == IMAGETYPE_JPEG )
		$image = imagecreatefromjpeg($fname) ;
	elseif ($type == IMAGETYPE_GIF)
		$image = imagecreatefromgif($fname) ;
	elseif ($type == IMAGETYPE_PNG)
		$image = imagecreatefrompng($fname) ;
	else {
		print("   Unknown image type ($type) for $fname\n") ;
		continue ;
	}
	if (!$image) {
		print("   $fname is not a valid image !! Skipping\n") ;
		continue ;
	}
	// Try resize to 128, 256, 512, 1024, 1920 ? 
	// Width https://www.php.net/manual/fr/function.imagecopyresized.php 
	// Update in Odoo
	$updates = array() ;
	$updates['image_128'] = base64_encode(resize($image, $width, $height, 128));
	$updates['image_256'] = base64_encode(resize($image, $width, $height, 256));
	$updates['image_512'] = base64_encode(resize($image, $width, $height, 512));
	$updates['image_1024'] = base64_encode(resize($image, $width, $height, 1024));
	$updates['image_1920'] = base64_encode(resize($image, $width, $height, 1920));
	print("   About to update Odoo #$row[odoo_id] with: \n") ;
//	var_dump($updates) ;
	$response = $odooClient->Update('res.partner', array(0+$row['odoo_id']), $updates) ;
	print("   Odoo response: $response\n") ;
}

$result = mysqli_query($mysqli_link, "SELECT * 
	FROM jom_kunena_users k 
		JOIN $table_person p ON p.jom_id = k.userid 
		JOIN jom_users j ON j.id = k.userid
	WHERE k.avatar IS NULL AND j.block = 0")
	or print(mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
	print("Processing #$row[jom_id], " . db2web($row['name']) . "\n") ;
	$hash = hash('sha256', strtolower(trim($row['email']))) ;
	// TODO process gravatars ! https://www.gravatar.com/avatar/" . md5(strtolower(trim($row['email']))) . "?s=200&d=blank&r=pg\"
	$url = "https://www.gravatar.com/avatar/$hash?s=128&d=404&r=pg" ;
	$image_128 = file_get_contents($url) ;
	if (! $image_128) {
		print("Skipping, url $url does not exist.\n") ;
		continue ;
	}
	print("   >>>>>>>> Found $url") ;
	// Try resize to 128, 256, 512, 1024, 1920 ? 
	// Width https://www.php.net/manual/fr/function.imagecopyresized.php 
	// Update in Odoo
	$updates = array() ;
	$updates['image_128'] = base64_encode($image_128);
	if (false) {
	$updates['image_256'] = base64_encode(file_get_contents("https://www.gravatar.com/avatar/$hash?s=256&d=404&r=pg"));
	$updates['image_512'] = base64_encode(file_get_contents("https://www.gravatar.com/avatar/$hash?s=512&d=404&r=pg"));
	$updates['image_1024'] = base64_encode(file_get_contents("https://www.gravatar.com/avatar/$hash?s=1024&d=404&r=pg"));
	$updates['image_1920'] = base64_encode(file_get_contents("https://www.gravatar.com/avatar/$hash?s=1920&d=404&r=pg"));
	}
	print("   About to update Odoo #$row[odoo_id] with: \n") ;
//	var_dump($updates) ;
	$response = $odooClient->Update('res.partner', array(0+$row['odoo_id']), $updates) ;
	print("   Odoo response: $response\n") ;
}

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
?>
</pre>
</body>
</html>