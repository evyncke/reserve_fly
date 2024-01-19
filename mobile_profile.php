<?php
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

// TODO
// - ensure consistency of email in rapcs_person and jom_user

ob_start("ob_gzhandler");
require_once "dbi.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}
if (isset($_REQUEST['displayed_id']) and $_REQUEST['displayed_id'] != '') {
	$displayed_id = $_REQUEST['displayed_id'] ;
	if (! is_numeric($displayed_id)) journalise($userId, "F", "Numero d'utilisateur invalide: $displayed_id") ;
	$read_only = ! ($userIsAdmin or $userIsInstructor or $displayed_id == $userId) ;
	if ($displayed_id != $userId)
		mysqli_query($mysqli_link, "update jom_kunena_users set uhits = uhits + 1 where userid = $displayed_id")
			or journalise($userId, "E", "Erreur systeme lors de la mise a jour de jom_kunena_users (uhits): " . mysqli_error($mysqli_link)) ;
} else {
	$displayed_id = $userId ;
	$read_only = false ;
}
$body_attributes = "onload=\"initProfile($displayed_id);\"" ;
$header_postamble = '<script data-cfasync="true" src="profile.js"></script>' ;
require_once 'mobile_header5.php' ;

$change_profile_message = '' ;

// Fetch all information about the user
$result = mysqli_query($mysqli_link, "SELECT *,u.username as username,u.email as email, date(p.birthdate) as birthdate
	FROM $table_person p 
		JOIN $table_users u on p.jom_id = u.id 
		LEFT JOIN jom_kunena_users k on k.userid=u.id
		LEFT JOIN $table_company_member AS cm ON cm.cm_member = $displayed_id
        LEFT JOIN $table_company AS c ON c.c_id = cm.cm_company
	WHERE u.id = $displayed_id") or journalise($userId, "F", "Erreur interne: " . mysqli_error($mysqli_link)) ;
$me = mysqli_fetch_array($result) or journalise($userId, "F", "Utilisateur inconnu") ;
$me['name'] = db2web($me['name']) ; 
$me['first_name'] = db2web($me['first_name']) ; 
$me['last_name'] = db2web($me['last_name']) ; 
$me['address'] = db2web($me['address']) ; 
$me['zipcode'] = db2web($me['zipcode']) ; 
$me['city'] = db2web($me['city']) ; 
$me['country'] = db2web($me['country']) ;
$me['company_name'] = db2web($me['c_name']) ; 
$me['company_address'] = db2web($me['c_address']) ; 
$me['company_zipcode'] = db2web($me['c_zipcode']) ; 
$me['company_city'] = db2web($me['c_city']) ; 
$me['company_country'] = db2web($me['c_country']) ;
$me['company_bce'] = db2web($me['c_bce']) ;

// Be paranoid
foreach($me as $key => $value)
	$me[$key] = htmlspecialchars($value, ENT_QUOTES) ;

if (isset($_REQUEST['action'])) journalise($userId, 'I', "Profile is called with action= $_REQUEST[action] for displayed_id=$displayed_id") ;

// Apply any change on the photo tab before fetching all displayed_id information
if (isset($_POST['action']) and $_POST['action'] == 'photo' and !$read_only) {
	if (!isset($_FILES['photoFile']) or !isset($_FILES['photoFile']['name']) or $_FILES['photoFile']['size'] == 0)
		die('Ne pas oublier pas de sélectionner un fichier d\'abord. <button onclick="window.history.back();">Essayer à nouveau</button>') ;
	$source_file = $_FILES['photoFile']['tmp_name'] ;
	$image_size = getimagesize($source_file) ;
	if ($image_size === FALSE) {
		journalise($userId, 'E', "Ce fichier n'est pas une photo($displayed_id): " . $_FILES['photoFile']['name'] . '/' . $source_file) ;
		die("Ce fichier ne semble pas être une image, veuillez l'envoyer par email à webmaster@spa-aviation.be") ;
	}
	$image_width = $image_size[0] ;
	$image_height = $image_size[1] ;
	$image_type = $image_size[2] ;
	$image_basename = basename($source_file) ;
	$image_filetype = pathinfo($source_file, PATHINFO_EXTENSION) ;
	$avatar_filename = "$_SERVER[DOCUMENT_ROOT]/$avatar_root_directory/users/avatar$displayed_id.$image_filetype" ;
	// Do we need to resize the image ?
	if ($image_width <= 200 and $image_height <= 200) {
		if (!move_uploaded_file($source_file, $avatar_filename)) {
			journalise($userId, 'E', "Impossible de déplacer le fichier photo ($displayed_id) to $avatar_filename: $source_file") ;
			$change_profile_message .= "Impossible de mettre à jour la photo.<br/>" ;
		}
	} else {
		$resize_ratio = ($image_width > $image_height) ? 200 / $image_width  : 200 / $image_height;
		switch ($image_type) {
			case IMAGETYPE_GIF: $upload_image = imagecreatefromgif($source_file) ; break ;
			case IMAGETYPE_JPEG: $upload_image = imagecreatefromjpeg($source_file) ; break ;
			case IMAGETYPE_PNG: $upload_image = imagecreatefrompng($source_file) ; break ;
			default:
				journalise($userId, 'E', "Format photo non supporté ($image_type) for $displayed_id: " . $_FILES['photoFile']['name'] . " == $source_file") ;
				$change_profile_message .= "Impossible de mettre à jour la photo (format non supporté).<br/>" ;
		}
		if (! $upload_image) journalise($userId, 'E', "Impossible de lire la photo pour $displayed_id: " . $_FILES['photoFile']['name']) ;
// print("<hr>" . ($image_width * $resize_ratio) . " x " . ($image_height * $resize_ratio) . " => $_SERVER[DOCUMENT_ROOT]/${avatar_root_directory}/users/avatar${displayed_id}.gif") ;
		$new_image = imagescale($upload_image, $image_width * $resize_ratio, $image_height * $resize_ratio) ;
		if (! $new_image) journalise($userId, 'E', "Impossible de scaler ($resize_ratio) for $displayed_id: " . $_FILES['photoFile']['name']) ;
		imagedestroy($upload_image) ;
		switch ($image_type) {
			case IMAGETYPE_GIF: imagegif($new_image, "$_SERVER[DOCUMENT_ROOT]/$avatar_root_directory/users/avatar$displayed_id.gif") ; $image_filetype = 'gif' ; break ;
			case IMAGETYPE_JPEG: imagejpeg($new_image, "$_SERVER[DOCUMENT_ROOT]/$avatar_root_directory/users/avatar$displayed_id.jpg") ; $image_filetype = 'jpg' ; break ;
			case IMAGETYPE_PNG: imagepng($new_image, "$_SERVER[DOCUMENT_ROOT]/$avatar_root_directory/users/avatar$displayed_id.png") ; $image_filetype = 'png' ; break ;
		}
	}
	// Update the kunena line for this user
	mysqli_query($mysqli_link, "update jom_kunena_users set avatar = 'users/avatar$displayed_id.$image_filetype' where userid = $displayed_id")
                        or die("Erreur systeme lors de la mise a jour de jom_kunena_users (avatar): " . mysqli_error($mysqli_link)) ;
	if ($affected_rows > 0) 
		journalise($userId, 'I', "Changement de photo($me[username]/$displayed_id)") ;
}

// Apply any change on the log book tab before fetching all displayed_id information
if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'log' and !$read_only) {
	$pic_minutes = trim($_REQUEST['pic_minutes']) ;
	if (!is_numeric($pic_minutes)) journalise($userId, "F", "Invalid value for pic_minutes $pic_minutes") ;
	$dc_minutes = trim($_REQUEST['dc_minutes']) ;
	if (!is_numeric($dc_minutes)) journalise($userId, "F", "Invalid value for dc_minutes $dc_minutes") ;
	$fi_minutes = trim($_REQUEST['fi_minutes']) ;
	if (!is_numeric($fi_minutes)) journalise($userId, "F", "Invalid value for fi_minutes $fi_minutes") ;
	$day_landings = trim($_REQUEST['day_landings']) ;
	if (!is_numeric($day_landings)) journalise($userId, "F", "Invalid value for day_landings $day_landings") ;
	$night_landings = trim($_REQUEST['night_landings']) ;
	if (!is_numeric($night_landings)) journalise($userId, "F", "Invalid value for night_landings $night_landings") ;

	mysqli_query($mysqli_link, "UPDATE $table_person SET pic_minutes=$pic_minutes, dc_minutes=$dc_minutes, fi_minutes=$fi_minutes,
		day_landings=$day_landings, night_landings=$night_landings
		WHERE jom_id = $displayed_id")
		or die("Erreur systeme lors de la mise a jour de $table_person: " . mysqli_error($mysqli_link)) ;
	$affected_rows += mysqli_affected_rows($mysqli_link) ;
	$change_profile_message .= ($affected_rows > 0) ? "Changement(s) effectu&eacute;(s).<br/>" : "Aucun changement effectu&eacute;.<br/>" ;
	if ($affected_rows > 0) 
		journalise($userId, 'I', "Changement de profil($me[username]/$displayed_id): carnet de vols") ;
}

// Apply any change on the social tab before fetching all displayed_id information
if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'social' and !$read_only) {
	$facebook = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['facebook'])) ;
	$linkedin = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['linkedin'])) ;
	$twitter = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['twitter'])) ;
	$skype = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['skype'])) ;
	// Do some sanity checks on the URL
	mysqli_query($mysqli_link, "update jom_kunena_users set facebook='$facebook', twitter='$twitter', linkedin='$linkedin', skype='$skype'
		where userid = $displayed_id")
		or die("Erreur systeme lors de la mise a jour de jom_kunena_users: " . mysqli_error($mysqli_link)) ;
	$affected_rows += mysqli_affected_rows($mysqli_link) ;
	$change_profile_message .= ($affected_rows > 0) ? "Changement(s) effectu&eacute;(s).<br/>" : "Aucun changement effectu&eacute;.<br/>" ;
	if ($affected_rows > 0) 
		journalise($userId, 'I', "Changement de profil($me[username]/$displayed_id): facebook: $facebook, twitter: $twitter, linkedin: $linkedin, skype: $skype") ;
}

// Apply any change on validity before fetching all displayed_id information
if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'validity' and !$read_only) {
	$result = mysqli_query($mysqli_link, "select * from $table_validity_type") or die("Erreur systeme lors du parcours des types de validites: ". mysqli_error($mysqli_link)) ;
	$affected_rows = 0 ;
	$log = '' ;
	while ($row =  mysqli_fetch_array($result)) {
		$type = $row['id'] ;
		$ident_value = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['ident_value'][$type])) ;
		$grant_date = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['grant_date'][$type])) ;
		$expire_date = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['expire_date'][$type])) ;
		if ($ident_value == '' and $grant_date == '' and $expire_date == '') continue ;
		$sql_statement = "replace into $table_validity(jom_id, validity_type_id, ident_value, grant_date, expire_date)
			values($displayed_id, $type, '$ident_value', '$grant_date', '$expire_date')"; 
		mysqli_query($mysqli_link, $sql_statement)
			or die("Impossible de mettre à jour les validités/annotations: " . mysqli_error($mysqli_link)) ;
		$affected_rows += mysqli_affected_rows($mysqli_link) ;
		$log .= "$row[name] ($ident_value): $grant_date -> $expire_date; \n" ;
	}
	$affected_rows += mysqli_affected_rows($mysqli_link) ;
	mysqli_free_result($result) ;
	// newValidityId=6&new_ident_value=test&new_grant_date=2000-01-01&new_expire_date=2011-01-01
	if (isset($_REQUEST['newValidityId'])) {
		$newValidityId = intval(trim($_REQUEST['newValidityId'])) ;
		if (! is_numeric($newValidityId) or $newValidityId <= 0) die("Invalid newValidityId") ;
		$new_ident_value = mysqli_real_escape_string($mysqli_link, web2db(trim($_REQUEST['new_ident_value']))) ;
		$new_grant_date = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['new_grant_date'])) ;
		$new_expire_date = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['new_expire_date'])) ;
		mysqli_query($mysqli_link, "INSERT INTO $table_validity(jom_id, validity_type_id, ident_value, grant_date, expire_date)
			values($displayed_id, $newValidityId, '$new_ident_value', '$new_grant_date', '$new_expire_date')")
			or die("Impossible d'ajouter la validité/annotation: " . mysqli_error($mysqli_link)) ;
		$affected_rows++ ;
		$log .= "new $newValidityId ($new_ident_value): $new_grant_date -> $new_expire_date; \n" ;
	}
	$change_profile_message .= ($affected_rows > 0) ? "Changement(s) effectu&eacute;(s).<br/>" : "Aucun changement effectu&eacute;.<br/>" ;
	if ($affected_rows > 0)
		journalise($userId, 'W', "$affected_rows validit&eacute;s chang&eacute;es pour $me[username]/$displayed_id: $log") ;
}

if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'delete_rating' and !$read_only) {
	$validity_id = intval(trim($_REQUEST['validity_id'])) ;
	if (! $validity_id or $validity_id <= 0) die("Rating type is invalid") ;
	mysqli_query($mysqli_link, "DELETE FROM $table_validity WHERE jom_id=$displayed_id AND validity_type_id=$validity_id")
		or die("Cannot delete rating: " . mysqli_error($mysqli_link)) ;
	journalise($userId, "W", "Rating $validity_id deleted") ;
}
// Apply any change before fetching all displayed_id information
if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'profile' and !$read_only) {
	$home_phone = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['home_phone'])) ;
	$cell_phone = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['cell_phone'])) ;
	$work_phone = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['work_phone'])) ;
	$email = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['email'])) ;
	if ((strlen($email) == 0) or ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
		journalise($userId, 'F', "Adresse email invalide pour $me[username]/$displayed_id: $email") ;
	}
	$address = web2db(mysqli_real_escape_string($mysqli_link, trim($_REQUEST['address']))) ;
	$zipcode = web2db(mysqli_real_escape_string($mysqli_link, trim($_REQUEST['zipcode']))) ;
	$city = web2db(mysqli_real_escape_string($mysqli_link, trim($_REQUEST['city']))) ;
	$country = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['country'])) ;
	$birthdate = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['birthdate'])) ;
	$sex = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['sex'])) ;
	if (!is_numeric($sex)) $sex = 0 ;
	$hide_flight = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['hide_flight'])) ;
	if (!is_numeric($hide_flight)) $hide_flight = 0 ;
	$first_name = web2db(mysqli_real_escape_string($mysqli_link, trim($_REQUEST['first_name']))) ;
	$last_name = web2db(mysqli_real_escape_string($mysqli_link, trim($_REQUEST['last_name']))) ;
//	if ($first_name == '' or $last_name == '') {
//		journalise($userId, 'E', "Nom ou pr&eacute;nom invalides pour $me[username]/$displayed_id: prenom=" . db2web($first_name) . ", nom: " . db2web($last_name)) ;
//		die("Le nom et/ou le pr&eacute;nom sont invalides. Changements refus&eacute;s.") ;
//	}
	mysqli_query($mysqli_link, "update $table_person set home_phone='$home_phone', work_phone='$work_phone', cell_phone='$cell_phone',
		address='$address', zipcode='$zipcode', city='$city', country='$country', birthdate='$birthdate', sex=$sex, email='$email',
		first_name='$first_name', last_name='$last_name', hide_flight_time=$hide_flight where jom_id = $displayed_id")
		or journalise($userId, "F", "Erreur systeme lors de la mise a jour de $table_person: " . mysqli_error($mysqli_link)) ;
	$affected_rows = mysqli_affected_rows($mysqli_link) ;
	if ($first_name != '' and $last_name != '') {
		mysqli_query($mysqli_link, "update $table_users set name='$first_name $last_name' where id = $displayed_id")
			or journalise($userId, "F", "Erreur systeme lors de la mise a jour de $table_users: " . mysqli_error($mysqli_link)) ;
		$affected_rows += mysqli_affected_rows($mysqli_link) ;
	}
	if ($email != '') {
		mysqli_query($mysqli_link, "update $table_users set email='$email' where id = $displayed_id")
			or journalise($userId, "F", "Erreur systeme lors de la mise a jour de $table_users/email: " . mysqli_error($mysqli_link)) ;
		$affected_rows += mysqli_affected_rows($mysqli_link) ;
	}
	$change_profile_message = ($affected_rows > 0) ? "Changement(s) effectu&eacute;(s).<br/>" : "Aucun changement effectu&eacute;.<br/>" ;
	if ($affected_rows > 0) 
		journalise($userId, 'I', "Changement de profil($me[username]/$displayed_id): tel $cell_phone / $home_phone / $work_phone, naissance: $birthdate, email: $email, pr&eacute;nom: " .
			db2web($first_name) . ", nom: " . db2web($last_name)) ;
}

// Fetch AGAIN all information about the user since they may have been modified by the above...
$result = mysqli_query($mysqli_link, "SELECT *,u.username as username,u.email as email, date(p.birthdate) as birthdate
	FROM $table_person p 
		JOIN $table_users u on p.jom_id = u.id 
		LEFT JOIN jom_kunena_users k on k.userid=u.id
		LEFT JOIN $table_company_member AS cm ON cm.cm_member = $displayed_id
        LEFT JOIN $table_company AS c ON c.c_id = cm.cm_company
	WHERE u.id = $displayed_id") or journalise($userId, "F", "Erreur interne: " . mysqli_error($mysqli_link)) ;
$me = mysqli_fetch_array($result) or die("Utilisateur inconnu") ;
$me['name'] = db2web($me['name']) ; 
$me['first_name'] = db2web($me['first_name']) ; 
$me['last_name'] = db2web($me['last_name']) ; 
$me['address'] = db2web($me['address']) ; 
$me['zipcode'] = db2web($me['zipcode']) ; 
$me['city'] = db2web($me['city']) ; 
$me['country'] = db2web($me['country']) ;
$me['company_name'] = db2web($me['c_name']) ; 
$me['company_address'] = db2web($me['c_address']) ; 
$me['company_zipcode'] = db2web($me['c_zipcode']) ; 
$me['company_city'] = db2web($me['c_city']) ; 
$me['company_country'] = db2web($me['c_country']) ;
$me['company_bce'] = db2web($me['c_bce']) ;

// Be paranoid
foreach($me as $key => $value)
	$me[$key] = htmlspecialchars($value, ENT_QUOTES) ;

?>
<div class="container-fluid">
<div class="row">
<h3>Données de contact de <?=$me['name']?></h3>
</div><!-- row -->

<form>
<div class="row">
		<label for="pilotSelect" class="col-form-label col-sm-6 col-md-4 col-lg-3">En tant que membre, vous pouvez choisir le membre à afficher:</label>
		<div class="col-sm-6 col-md-3 col-lg-2">
			<select id="pilotSelect" name="displayed_id" class="form-select" onchange="pilotChange('<?=$_SERVER['PHP_SELF']?>', this);">
			</select>
		</div> <!-- col -->
	<div class="col-sm-12 col-md-5">
		Ajouter <?=$me['name']?> à mes contacts: 
		<a href="vcard.php?id=<?=$displayed_id?>" title="Télécharge carte de visite VCF"><i class="bi bi-cloud-download"></i></a> 
		<a href="vcard.php?id=<?=$displayed_id?>&qr=yes" title="Affiche un QR code"><i class="bi bi-qr-code"></i></a> 
 	</div> <!--- col -->
</div> <!--- row -->
</form>

<div class="row">
<div class="col-12">
<?php
print($change_profile_message) ; // Display any error message
$read_only_attribute = ($read_only) ? ' readonly disabled' : '' ;
?>
</div> <!-- col -->
</div> <!-- row -->

<br/>
<ul class="nav nav-tabs" role="tablist">
	<li class="nav-item">
  		<a class="nav-link active" role="presentation" data-bs-toggle="tab" data-bs-target="#main" aria-current="page" href="#main">Contact</a>
	</li>
	<li class="nav-item">
  		<a class="nav-link" role="presentation" data-bs-toggle="tab" data-bs-target="#validity" aria-current="page" href="#validity">Validités / annotations club</a>
	</li>
	<li class="nav-item">
  		<a class="nav-link" role="presentation" data-bs-toggle="tab" data-bs-target="#log" aria-current="page" href="#log">Carnet de vols</a>
	</li>
	<li class="nav-item">
  		<a class="nav-link" role="presentation" data-bs-toggle="tab" data-bs-target="#photo" aria-current="page" href="#photo">Photo</a>
	</li>
	<li class="nav-item">
  		<a class="nav-link" role="presentation" data-bs-toggle="tab" data-bs-target="#social_network" aria-current="page" href="#social_network">Réseaux sociaux</a>
	</li>
	<li class="nav-item">
  		<a class="nav-link" role="presentation" data-bs-toggle="tab" data-bs-target="#company" aria-current="page" href="#company">Facturation</a>
	</li>
</ul>

<div class="tab-content">

<div id="main" class="tab-pane fade show active" role="tabpanel">

<form action="<?=$_SERVER['PHP_SELF']?>" method="get" role="form" class="Xform-horizontal">
<input type="hidden" name="action" value="profile">
<input type="hidden" name="displayed_id" value="<?=$displayed_id?>">
<div class="row mb-3">
	<label for="usernameId" class="col-form-label col-sm-4 col-md-3 col-lg-2">Nom d'utilisateur:</label>
	<div class="col-sm-4">
		<input type="text" class="form-control" id="usernameId" name="username" value="<?=$me['username']?>" readonly disabled>
		<span class="input-group-addon">(contacter webmaster@spa-aviation.be pour changer)</span>
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="nameId" class="col-form-label col-sm-4 col-md-3 col-lg-2">Nom complet:</label>
	<div class="col-sm-4">
		<input type="text" class="form-control" name="name" id="nameId" value="<?=$me['name']?>" readonly disabled>
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="first_nameId" class="col-form-label col-sm-4 col-md-3 col-lg-2">Prénom:</label>
	<div class="col-sm-4">
		<input type="text" class="form-control" name="first_name" id="first_nameId" value="<?=$me['first_name']?>" autocomplete="given-name" <?=$read_only_attribute?>>
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="last_nameId" class="col-form-label col-sm-4 col-md-3 col-lg-2">Nom:</label>
	<div class="col-sm-4">
			<input type="text" class="form-control" name="last_name" id="last_nameId" value="<?=$me['last_name']?>" autocomplete="family-name" <?=$read_only_attribute?>>
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
        <label for="emailId" class="col-form-label col-sm-4 col-md-2 col-lg-2">Adresse e-mail:</label>
        <div class="col-sm-4">
	       	<div class="input-group">
                <input type="email" class="form-control" name="email" id="emailId" value="<?=$me['email']?>" autocomplete="email" <?=$read_only_attribute?>>
                <span class="input-group-addon">(obligatoire)</span>
            </div><!-- input group -->
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
        <label for="cell_phoneId" class="col-form-label col-sm-4 col-md-2 col-lg-2">Téléphone mobile:</label>
        <div class="col-sm-4">
	       	<div class="input-group">
                <input type="tel" class="form-control" name="cell_phone" id="cell_phoneId" value="<?=$me['cell_phone']?>" autocomplete="mobile tel" <?=$read_only_attribute?>>
                <span class="input-group-addon">(obligatoire)</span>
            </div><!-- input group -->
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
        <label for="home_phoneId" class="col-form-label col-sm-4 col-md-2 col-lg-2">Téléphone priv&eacute;:</label>
        <div class="col-sm-4">
                <input type="tel" class="form-control" name="home_phone" id="home_phoneId" value="<?=$me['home_phone']?>" autocomplete="home tel" <?=$read_only_attribute?>>
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
        <label for="work_phoneId" class="col-form-label col-sm-4 col-md-2 col-lg-2">Téléphone travail:</label>
        <div class="col-sm-4">
                <input type="tel" class="form-control" name="work_phone" id="work_phoneId" value="<?=$me['work_phone']?>" autocomplete="work tel" <?=$read_only_attribute?>>
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
        <label for="addressId" class="col-form-label col-sm-4 col-md-2 col-lg-2">Rue:</label>
        <div class="col-sm-4">
        	<div class="input-group">
                <input type="text" class="form-control" name="address" id="addressId" value="<?=$me['address']?>" autocomplete="street-address" <?=$read_only_attribute?>>
            </div><!-- input group -->
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
        <label for="zipcodeId" class="col-form-label col-sm-4 col-md-2 col-lg-2">Code postal:</label>
        <div class="col-sm-4">
        	<div class="input-group">
                <input type="text" class="form-control" name="zipcode" id="zipcodeId" value="<?=$me['zipcode']?>" autocomplete="postal-code" <?=$read_only_attribute?>>
            </div><!-- input group -->
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
        <label for="cityId" class="col-form-label col-sm-4 col-md-2 col-lg-2">Commune:</label>
        <div class="col-sm-4">
        	<div class="input-group">
                <input type="text" class="form-control" name="city" id="cityId" value="<?=$me['city']?>" autocomplete="address-level2" <?=$read_only_attribute?>>
            </div><!-- input group -->
	</div> <!-- col -->
</div> <!-- row -->
<div for="countryId" class="row mb-3">
        <label class="col-form-label col-sm-4 col-md-2 col-lg-2">Pays:</label>
        <div class="col-sm-4">
        	<div class="input-group">
                <input type="text" class="form-control" name="country" id="countryId" value="<?=$me['country']?>" autocomplete="country-name" <?=$read_only_attribute?>>
            </div><!-- input group -->
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
        <label class="col-form-label col-sm-4 col-md-2 col-lg-2">Date de naissance:</label>
        <div class="col-sm-4">
        	<div class="input-group">
                <input type="date" class="form-control" name="birthdate" placeholder="AAAA-MM-JJ" value="<?=$me['birthdate']?>" autocomplete="bday" <?=$read_only_attribute?>>
                <span class="input-group-addon">(optionnel)</span>
            </div><!-- input group -->
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
        <label class="col-form-label col-sm-4 col-md-2 col-lg-2">Genre:</label>
        <div class="col-sm-4">
        	<div class="input-group">
				<select class="form-control" name="sex" <?=$read_only_attribute?>>
					<option value="0" <?=($me['sex'] == 0) ? 'selected':''?>>Inconnu</option>
					<option value="1" <?=($me['sex'] == 1) ? 'selected':''?>>Masculin</option>
					<option value="2" <?=($me['sex'] == 2) ? 'selected':''?>>Féminin</option>
				</select>
                <span class="input-group-addon">(optionnel)</span>
            </div><!-- input group -->
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
        <label class="col-form-label col-sm-4 col-md-2 col-lg-2">Heures de vol:</label>
        <div class="col-sm-4">
        	<div class="input-group">
				<select class="form-control" name="hide_flight" <?=$read_only_attribute?>>
					<option value="0" <?=($me['hide_flight_time'] == 0) ? 'selected':''?>>Montrer</option>
					<option value="1" <?=($me['hide_flight_time'] == 1) ? 'selected':''?>>Cacher</option>
				</select>
                <span class="input-group-addon">(optionnel)</span>
            </div><!-- input group -->
	</div> <!-- col -->
</div> <!-- row -->

<?php
if (! $read_only) {
	print('<div class="row"><button type="submit" class="col-sm-offset-2 col-md-offset-1 col-sm-3 col-md-2 btn btn-primary">Enregistrer les changements</button></div>') ;
}
?>
</form>
</div> <!-- id=main -->

<div id="log" class="tab-pane fade" role="tabpanel">
<div class="row mb-5">
    <div class="col">
	Configuration initiale de votre carnet de vols. Vous pouvez aussi <a href="mobile_mylog.php">visualiser votre carnet de vols</a> sur base des entrées
	dans les carnets de routes des avions RAPCS et autres.
    </div><!-- col -->
</div> <!-- row -->
<form action="<?=$_SERVER['PHP_SELF']?>" method="get" role="form" class="form-horizontal">
<input type="hidden" name="action" value="log">
<input type="hidden" name="displayed_id" value="<?=$displayed_id?>">
<div class="row mb-3">
	<label class="col-form-label col-sm-4 col-md-2">Minutes de vol en tant que PIC:</label>
	<div class="col-sm-4 col-md-1">
		<input type="text" class="form-control" name="pic_minutes" value="<?=$me['pic_minutes']?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label class="col-form-label col-sm-4 col-md-2">Minutes de vol en dual command:</label>
	<div class="col-sm-4 col-md-1">
		<input type="text" class="form-control" name="dc_minutes" value="<?=$me['dc_minutes']?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label class="col-form-label col-sm-4 col-md-2">Minutes de vol en tant qu'instructeur:</label>
	<div class="col-sm-4 col-md-1">
		<input type="text" class="form-control" name="fi_minutes" value="<?=$me['fi_minutes']?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label class="col-form-label col-sm-4 col-md-2">Atterrissage(s) de jour:</label>
	<div class="col-sm-4 col-md-1">
		<input type="text" class="form-control" name="day_landings" value="<?=$me['day_landings']?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label class="col-form-label col-sm-4 col-md-2">Atterrissage(s) de nuit:</label>
	<div class="col-sm-4 col-md-1">
		<input type="text" class="form-control" name="night_landings" value="<?=$me['night_landings']?>">
	</div> <!-- col -->
</div> <!-- row -->
<?php
if (! $read_only) {
	print('<div class="row mb-3">
        <button type="submit" class="col-sm-offset-2 col-md-offset-1 col-sm-3 col-md-2 btn btn-primary">Enregistrer les changements
        </button></div>') ;
}
?>
</form>
</div> <!-- tab id = log -->

<div id="photo" class="tab-pane fade" role="tabpanel">
<?php
print("<div class=\"row\">") ;
if ($me['avatar'] != '') {
		print("<img src=\"$avatar_root_uri/$me[avatar]\" class=\"col-xs-12 col-sm-6 col-md-2\">") ;
}
print("<img src=\"https://www.gravatar.com/avatar/" . md5(strtolower(trim($me['email']))) . "?s=200&d=blank&r=pg\" class=\"col-xs-12 col-sm-6 col-md-2\">
	</div> <!-- row -->\n") ;
if (! $read_only) {
	print('<div class="row">
		<form action="' . $_SERVER['PHP_SELF'] . '" method="post" enctype="multipart/form-data" role="form" class="form-inline">
			<input type="hidden" name="action" value="photo">
			<input type="hidden" name="displayed_id" value="' . $displayed_id . '">
		<div class="form-group">
			<label class="control-label col-sm-9 col-md-4">Fichier pour la nouvelle photo (200 x 200 pixels de pr&eacute;f&eacute;rence):</label>
			<input type="file" name="photoFile" class="form-control col-sm-6 col-md-2"/>
		</div> <!-- form-group -->
		<div class="form-group"-->
			<button type="submit" class="col-sm-offset-2 col-md-offset-1 col-sm-3 col-md-2 btn btn-primary">Mettre &agrave; jour la photo</button>
		</div> <!-- form-group -->
	</form>
	<p>Vous pouvez aussi utiliser le site gratuit <a href="https://gravatar.com/">Gravatar</a> pour y mettre votre photo et la lier ainsi à votre adresse email ' . $me['email'] . '.</p>
	</div> <!-- row -->') ;
}
?>
</div> <!-- id=photo -->

<div id="social_network" class="tab-pane fade" role="tabpanel">
<div class="row">
	Les adresses Internet des pages des r&eacute;seaux sociaux de ce membre. Les logos, si pr&eacute;sents, sont cliquables.
</div> <!-- row -->
<div class="row">
<form action="<?=$_SERVER['PHP_SELF']?>" method="get" role="form" class="form-horizontal">
<?php
if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'social' and !$read_only) {
}
$facebook_img = ($me['facebook'] != '') ? "<a href=\"https://www.facebook.com/$me[facebook]\" target=\"_blank\"><img src=\"facebook_blue_100.png\" width=\”15\" height=\"15\"></a>\n" : '' ;
$linkedin_img = ($me['linkedin'] != '') ? "<a href=\"https://www.linkedin.com/in/$me[linkedin]\" target=\"_blank\"><img src=\"linkedin.jpg\"></a>\n" : "" ;
$skype_img = ($me['skype'] != '') ? "<a href=\"skype:$me[skype]\"><img src=\"skype.png\"></a>\n" : "" ;
$twitter_img = ($me['twitter'] != '') ? "<a href=\"https://www.twitter.com/$me[twitter]\" target=\"_blank\"><img src=\"twitter.jpg\"></a>\n" : '' ;
?>
<input type="hidden" name="action" value="social">
<input type="hidden" name="displayed_id" value="<?=$displayed_id?>">
<div class="input-group mb-3">
	<label for="facebookId" class="col-form-label col-sm-2 col-md-1">Facebook <?=$facebook_img?>:</label>
	<span class="input-group-text">https://www.facebook.com/</span>
	<input type="text" class="form-control" name="facebook" id="facebookId" value="<?=$me['facebook']?>" placeholder="Ce qui suit https://www.facebook.com/ sur votre page personelle" <?=$read_only_attribute?>>
</div> <!-- input-group -->
<div class="input-group mb-3">
	<label for="linkedinId" class="col-form-label col-sm-2 col-md-1">LinkedIn <?=$linkedin_img?>:</label>
	<span class="input-group-text">https://www.linkedin.com/in/</span>
	<input type="text" class="form-control" name="linkedin" id="linkedinId" value="<?=$me['linkedin']?>" placeholder="Ce qui suit https://www.linkedin.com/in/ sur votre profil"<?=$read_only_attribute?>>
</div> <!-- input-group -->
<div class="input-group mb-3">
	<label for="skypeId" class="col-form-label col-sm-2 col-md-1">Skype <?=$skype_img?>:</label>
	<input type="text" class="form-control" name="skype" id="skypeId" value="<?=$me['skype']?>" <?=$read_only_attribute?>>
</div> <!-- input-group -->
<div class="input-group mb-3">
	<label for="twitterId" class="col-form-label col-sm-2 col-md-1">Twitter <?=$twitter_img?>:</label>
	<span class="input-group-text">@</span>
	<input type="text" class="form-control" name="twitter" id="twitterId" value="<?=$me['twitter']?>" <?=$read_only_attribute?>>
</div> <!-- input-group -->
<?php
if (! $read_only) {
	print('<div class="form-group"><button type="submit" class="col-sm-offset-2 col-md-offset-1 col-sm-3 col-md-2 btn btn-primary">Enregistrer les changements</button></div>') ;
}
?>
</form>
</div> <!-- row -->
</div> <!-- id=social_network -->

<div id="company" class="tab-pane fade" role="tabpanel">
<div class="row">
<?php
	if ($me['company_name'] == '')
		print("<p>Aucune société n'est associée à votre compte membre. Les factures seront donc envoyées à votre nom et adresse personnelle.</p>") ;
	else {
?>
	<p>Les factures seront établies au nom de:
		<ul>
		<li><b>Nom de la société:</b> <?=$me['company_name']?></li>
		<li><b>Adresse:</b> <?=$me['company_address']?></li>
		<li><b>Ville:</b>  <?=$me['company_zipcode']?> <?=$me['company_city']?></li>
		<li><b>Pays:</b> <?=$me['company_country']?></li>
		<li><b>Code Entreprise (BCE):</b> <?=$me['company_bce']?></li>
	</ul>
	</p>
<?php
	} // company_name != ''
?>
</div> <!-- row -->
<div class="row">
	<hr>
	<p>Pour changer ces données, veuillez contacter par email: <a href="mailto:eric.vyncke@spa-aviation.be">eric.vyncke@spa-aviation.be</a>.</p>
</div>
</div> <!-- id=social_network -->

<div id="validity" class="tab-pane fade">

<?php
if (! $read_only) {
?>
<div class="row">
<div class="jumbotron">ATTENTION, ces validit&eacute;s ne sont pas tenues en compte lors des r&eacute;servations dans le futur.
<br/><b>LE PILOTE DOIT TOUJOURS LES VERIFIER</b>
</div> <!-- col -->
</div> <!-- row -->

<div class="row">
<form action="<?=$_SERVER['PHP_SELF']?>" method="get" role="form">
<input type="hidden" name="action" value="validity">
<input type="hidden" name="displayed_id" value="<?=$displayed_id?>">
<div class="table-responsive">
<table class="table table-striped table-bordered">
<thead>
<tr>
<th class="validityHeader col-xs-3 col-md-1">Nom</th>
<th class="validityHeader col-xs-3 col-md-1">Code, identifiant, ...</th>
<th class="validityHeader col-xs-3 col-md-1">Date d'obtention</th>
<th class="validityHeader col-xs-3 col-md-1">Limite de validit&eacute;</th>
</tr>
</thead>
<tbody>
<?php
$result = mysqli_query($mysqli_link, "select *
	from $table_validity_type t left join $table_validity v on validity_type_id = t.id and jom_id = $displayed_id
	order by t.name") or journalise($userId, "F", "Erreur systeme a propos de l'access a validity: " . mysqli_error($mysqli_link)) ;
$options = '<option value="-1" disabled selected> -- validité/annotation à ajouter et remplir les cases sur cette ligne--</option>' ;
while ($row = mysqli_fetch_array($result)) {
	if ($row['grant_date']) {
		if ($userId == $displayed_id)
			$delete = " <a href=\"$_SERVER[PHP_SELF]?displayed_id=$displayed_id&validity_id=$row[id]&action=delete_rating\"><i class=\"bi bi-trash-fill text-danger\"></i></a>" ;
		else
			$delete = '' ;
		if ($row['mandatory_access_control'] == 0)
			$private_validity = '' ;
		else if ($userId == $displayed_id or $userIsInstructor)
			$private_validity = ' (seuls le pilote et les instructeurs voient cette ligne) ' ;
		else
			continue ;
		print("<tr><td class=\"validityNameCell\">" . db2web($row['name']) . "$private_validity$delete</td>\n") ;
		if ($row['ident_value_enable'])
			print("<td class=\"validityCell\"><input type=\"text\" name=\"ident_value[$row[id]]\" value=\"" . db2web($row['ident_value']) . "\"></td>\n") ;
		else	
			print("<td class=\"validityCell\"></td>\n") ;
		print("<td class=\"validityCell\"><input type=\"date\" name=\"grant_date[$row[id]]\" value=\"$row[grant_date]\"></td>\n") ;
		if ($row['time_limitation'])
			print("<td class=\"validityCell\"><input type=\"date\" name=\"expire_date[$row[id]]\" value=\"$row[expire_date]\"></td>\n") ;
		else	
			print("<td class=\"validityCell\"></td>\n") ;
		print("</tr>\n") ;
	} else
		$options = "$options<option value=\"$row[id]\">" . db2web($row['name']) . "</option>\n" ;
}
if ($userId == $displayed_id) {
	print("
	<tr>
	<td class=\"validityCell\">Nouvelle validit&eacute; ou annotation club: <select name=\"newValidityId\" class=\"form-control\">$options</select></td>
	<td class=\"validityCell\"><input type=\"text\" name=\"new_ident_value\"/></td>
	<td class=\"validityCell\"><input type=\"date\" name=\"new_grant_date\"/></td>
	<td class=\"validityCell\"><input type=\"date\" name=\"new_expire_date\"/></td>
	</tr>") ;
}
print("</tbody>
</table>
</div> <!-- table responsive-->
</div> <!-- row -->\n") ;
if ($userId == $displayed_id) {
?>
<div class="checkbox col-sd-offset-2">
	<label>
		<input type="checkbox" onchange="checkboxChanged(this);"> je confirme la validit&eacute; des informations
		ci-dessus. Toute fausse d&eacute;claration peut entra&icirc;ner l'arr&ecirc;t de mon appartenance au club.
	</label>
</div> <!-- checkbox -->
<div class="row col-sd-offset-2">
	<button type="submit" class="col-sm-offset-2 col-md-offset-1 col-sm-3 col-md-2 btn btn-primary" id="submitButton" disabled>Enregistrer les changements</button>
</div> <!-- row -->
<?php
} // if ($userId == $displayed_id)
?>
</form>
<?php
} // if (! $read_only)
?>
</div> <!-- validity -->

</div> <!-- content tab -->

</div> <!-- container -->
</body>
</html>
