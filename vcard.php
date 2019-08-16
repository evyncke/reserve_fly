<?php
/*
   Copyright 2014-2019 Eric Vyncke

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
	from $table_person p join jom_users u on p.jom_id = u.id left join jom_kunena_users k on k.userid=u.id
	where u.id = $displayed_id") or die("Erreur interne: " . mysqli_error($mysqli_link)) ;
$me = mysqli_fetch_array($result) ;
$name_db = $me['name'] ;
if ($convertToUtf8 ) $me['name'] = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $me['name']) ; 
if ($convertToUtf8 ) $me['first_name'] = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $me['first_name']) ; 
if ($convertToUtf8 ) $me['last_name'] = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $me['last_name']) ; 
if ($convertToUtf8 ) $me['city'] = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $me['city']) ; 
// Be paranoid
foreach($me as $key => $value)
	$me[$key] = htmlspecialchars($value, ENT_QUOTES) ;

header("Content-Type: text/vcard;charset=UTF-8") ;
if ($me['first_name'] != '' && $me['last_name'] != '')
	header("Content-Disposition: attachment; filename=\"$me[first_name] $me[last_name].vcf\"") ;
else
	header("Content-Disposition: attachment; filename=\"$me[name].vcf\"") ;

print("BEGIN:VCARD
VERSION:3.0
PRODID:aeroclub management by Eric Vyncke
N;charset=utf-8:$me[last_name];$me[first_name];;;
FN;charset=utf-8:$me[name]
LOGO;MEDIATYPE=image/x-icon:https://www.spa-aviation.be/favicon32x32.ico
ORG;charset=utf-8:RAPCS\n") ;
if ($me['birthdate'] && $me['birthdate'] != '0000-00-00') print("BDAY:$me[birthdate]\n") ;
if ($me['avatar'] != '') {
	$file_name = '../media/kunena/avatars/' . $me['avatar'] ;
	$f = fopen($file_name, 'rb') ;
	$binary_photo = fread($f, filesize($file_name)) ;
	fclose($f) ;
	$base64_photo = base64_encode($binary_photo) ;
	$base64_photo = chunk_split($base64_photo, 76, "\n ") ;
	print("PHOTO;ENCODING=b;TYPE=jpeg:\n $base64_photo\n") ;
}
if ($me['home_phone'] != '') print("TEL;TYPE=HOME,VOICE:$me[home_phone]\n") ;
if ($me['work_phone'] != '') print("TEL;TYPE=WORK,VOICE:$me[work_phone]\n") ;
if ($me['cell_phone'] != '') print("TEL;TYPE=CELL,VOICE:$me[cell_phone]\n") ;
if ($me['skype'] != '') print("IMPP:skype:$me[skype]\n") ;
if ($me['msn'] != '') print("IMPP:msn:$me[msn]\n") ;
if ($me['aim'] != '') print("IMPP:aim:$me[aim]\n") ;
if ($me['twitter'] != '') print("IMPP:twitter:$me[twitter]\n") ;
if ($me['sex'] == 1) print("GENDER:M\n") ;
if ($me['sex'] == 2) print("GENDER:F\n") ;
print("TITLE:Membre
EMAIL;TYPE=PREF,INTERNET:$me[email]
REV:" . date('Y-m-d') . "
END:VCARD") ;
journalise($userId, 'I', "Downloading of vcard for $me[name]") ;
?>
