<?php
/*
   Copyright 2014-2023 Eric Vyncke

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

// TODO
// - add first and last name
// - ensure consistency of email in rapcs_person and jom_user
require_once "dbi.php" ;
require_once 'facebook.php' ;

if (isset($_REQUEST['id']) and $_REQUEST['id'] != '') {
	$displayed_id = $_REQUEST['id'] ;
	if (! is_numeric($displayed_id)) die("Numero d'utilisateur invalide: $displayed_id") ;
} else die("Unknown ID") ;

if ($userId < 0 or $userId == '') die("Vous devez être connecté") ;

// Fetch all information about the user
$result = mysqli_query($mysqli_link, "select *,u.username as username,u.email as email, date(p.birthdate) as birthdate
	from $table_person p join $table_users u on p.jom_id = u.id left join jom_kunena_users k on k.userid=u.id
	where u.id = $displayed_id") or die("Erreur interne: " . mysqli_error($mysqli_link)) ;
$me = mysqli_fetch_array($result) ;
$name_db = $me['name'] ;
$me['name'] = db2web($me['name']) ; 
$me['first_name'] = db2web($me['first_name']) ; 
$me['last_name'] = db2web($me['last_name']) ; 
$me['city'] = db2web($me['city']) ; 
// Be paranoid
foreach($me as $key => $value)
	$me[$key] = htmlspecialchars($value, ENT_QUOTES) ;



$s = "BEGIN:VCARD
VERSION:3.0
PRODID:aeroclub management by Eric Vyncke
N;charset=utf-8:$me[last_name];$me[first_name];;;
FN;charset=utf-8:$me[name]
LOGO;MEDIATYPE=image/x-icon:https://www.spa-aviation.be/favicon32x32.ico
ORG;charset=utf-8:RAPCS\n" ;
if ($me['birthdate'] && $me['birthdate'] != '0000-00-00') $s .= "BDAY:$me[birthdate]\n" ;
if (!isset($_REQUEST['qr']) and $me['avatar'] != '') {
	$file_name = '../media/kunena/avatars/' . $me['avatar'] ;
	$f = fopen($file_name, 'rb') ;
	$binary_photo = fread($f, filesize($file_name)) ;
	fclose($f) ;
	$base64_photo = base64_encode($binary_photo) ;
	$base64_photo = chunk_split($base64_photo, 76, "\n ") ;
	$s .= "PHOTO;ENCODING=b;TYPE=jpeg:\n $base64_photo" ;
}
if ($me['home_phone'] != '') $s .= "TEL;TYPE=HOME,VOICE:$me[home_phone]\n" ;
if ($me['work_phone'] != '') $s .= "TEL;TYPE=WORK,VOICE:$me[work_phone]\n" ;
if ($me['cell_phone'] != '') $s .= "TEL;TYPE=CELL,VOICE,TEXT:$me[cell_phone]\n" ;
if ($me['skype'] != '') $s .= "IMPP:skype:$me[skype]\n" ;
if ($me['msn'] != '') $s .= "IMPP:msn:$me[msn]\n" ;
if ($me['aim'] != '') $s .= "IMPP:aim:$me[aim]\n" ;
if ($me['twitter'] != '') $s .= "IMPP:twitter:$me[twitter]\n" ;
if ($me['sex'] == 1) $s .= "GENDER:M\n" ;
if ($me['sex'] == 2) $s .= "GENDER:F\n" ;
$s .= "TITLE:Membre
EMAIL;TYPE=PREF,INTERNET:$me[email]
REV:" . date('Ymd') . 'T' . date('His') . "Z
END:VCARD" ;

if (isset($_REQUEST['qr']) and $_REQUEST['qr'] != '') {
	$s = str_replace("\n", "\r\n", $s) ;
?>
<html>
<head>
<title>Contact QR-code pour <?=$me['first_name'] . ' ' . $me['last_name']?></title>
</head>
<body>
<h1>Contact QR-code pour <?=$me['first_name'] . ' ' . $me['last_name']?></h1>
<?php
	print("<img src=\"https://chart.googleapis.com/chart?cht=qr&chs=300x300&&chl=" . urlencode($s) . "\">") ;
	journalise($userId, 'I', "QR-code for $me[name] displayed") ;
} else {
		header("Content-Type: text/vcard;charset=UTF-8") ;
		if ($me['first_name'] != '' && $me['last_name'] != '')
			header("Content-Disposition: attachment; filename=\"$me[first_name] $me[last_name].vcf\"") ;
		else
			header("Content-Disposition: attachment; filename=\"$me[name].vcf\"") ;
        print($s) ;
		journalise($userId, 'I', "Downloading of vcard for $me[name]") ;
}


?>
