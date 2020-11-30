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

if ($userId <= 0)
	die("Vous devez &ecirc;tre connect&eacute; pour acc&egrave;der &agrave; votre profil.") ;

// $userId = 345 ;

if (isset($_REQUEST['displayed_id']) and $_REQUEST['displayed_id'] != '') {
	$displayed_id = $_REQUEST['displayed_id'] ;
	if (! is_numeric($displayed_id)) die("Numero d'utilisateur invalide: $displayed_id") ;
	$read_only = ! ($userIsAdmin or $userIsInstructor or $displayed_id == $userId) ;
	if ($displayed_id != $userId)
		mysqli_query($mysqli_link, "update jom_kunena_users set uhits = uhits + 1 where userid = $displayed_id")
			or die("Erreur systeme lors de la mise a jour de jom_kunena_users (uhits): " . mysqli_error($mysqli_link)) ;
} else {
	$displayed_id = $userId ;
	$read_only = false ;
}

$change_profile_message = '' ;

// Fetch all information about the user
$result = mysqli_query($mysqli_link, "select *,u.username as username,u.email as email, date(p.birthdate) as birthdate
	from $table_person p join $table_users u on p.jom_id = u.id left join jom_kunena_users k on k.userid=u.id
	where u.id = $displayed_id") or die("Erreur interne: " . mysqli_error($mysqli_link)) ;
$me = mysqli_fetch_array($result) or die("Utilisateur inconnu") ;
$me['name'] = db2web($me['name']) ; 
$me['first_name'] = db2web($me['first_name']) ; 
$me['last_name'] = db2web($me['last_name']) ; 
$me['city'] = db2web($me['city']) ; 
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
	$avatar_filename = "$_SERVER[DOCUMENT_ROOT]/${avatar_root_directory}/users/avatar${displayed_id}.${image_filetype}" ;
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
			case IMAGETYPE_JPEG:
			case IMAGETYPE_JPG: $upload_image = imagecreatefromjpeg($source_file) ; break ;
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
			case IMAGETYPE_GIF: imagegif($new_image, "$_SERVER[DOCUMENT_ROOT]/${avatar_root_directory}/users/avatar${displayed_id}.gif") ; $image_filetype = 'gif' ; break ;
			case IMAGETYPE_JPEG:
			case IMAGETYPE_JPG: imagejpeg($new_image, "$_SERVER[DOCUMENT_ROOT]/${avatar_root_directory}/users/avatar${displayed_id}.jpg") ; $image_filetype = 'jpg' ; break ;
			case IMAGETYPE_PNG: imagepng($new_image, "$_SERVER[DOCUMENT_ROOT]/${avatar_root_directory}/users/avatar${displayed_id}.png") ; $image_filetype = 'png' ; break ;
		}
	}
	// Update the kunena line for this user
	mysqli_query($mysqli_link, "update jom_kunena_users set avatar = 'users/avatar${displayed_id}.${image_filetype}' where userid = $displayed_id")
                        or die("Erreur systeme lors de la mise a jour de jom_kunena_users (avatar): " . mysqli_error($mysqli_link)) ;
	if ($affected_rows > 0) 
		journalise($userId, 'I', "Changement de photo($me[username]/$displayed_id)") ;
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
			or die("Impossible de mettre à jour les validités: " . mysqli_error($mysqli_link)) ;
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
			or die("Impossible d'ajouter la validité: " . mysqli_error($mysqli_link)) ;
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
		journalise($userId, 'E', "Adresse email invalide pour $me[username]/$displayed_id: $email") ;
		die("L'adresse email modifi&eacute;e ($email) est invalide. Changements refus&eacute;s.") ;
	}
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
//	$total_flight_time = mysqli_real_escape_string(trim($_REQUEST['total_flight_time'])) ;
	mysqli_query($mysqli_link, "update $table_person set home_phone='$home_phone', work_phone='$work_phone', cell_phone='$cell_phone',
		city='$city', country='$country', birthdate='$birthdate', sex=$sex, email='$email',
		first_name='$first_name', last_name='$last_name', hide_flight_time=$hide_flight where jom_id = $displayed_id")
		or die("Erreur systeme lors de la mise a jour de $table_person: " . mysqli_error($mysqli_link)) ;
	$affected_rows = mysqli_affected_rows($mysqli_link) ;
	if ($first_name != '' and $last_name != '') {
		mysqli_query($mysqli_link, "update $table_users set name='$first_name $last_name' where id = $displayed_id")
			or die("Erreur systeme lors de la mise a jour de $table_users: " . mysqli_error($mysqli_link)) ;
		$affected_rows += mysqli_affected_rows($mysqli_link) ;
	}
	if ($email != '') {
		mysqli_query($mysqli_link, "update $table_users set email='$email' where id = $displayed_id")
			or die("Erreur systeme lors de la mise a jour de $table_users/email: " . mysqli_error($mysqli_link)) ;
		$affected_rows += mysqli_affected_rows($mysqli_link) ;
	}
	$change_profile_message = ($affected_rows > 0) ? "Changement(s) effectu&eacute;(s).<br/>" : "Aucun changement effectu&eacute;.<br/>" ;
	if ($affected_rows > 0) 
		journalise($userId, 'I', "Changement de profil($me[username]/$displayed_id): tel $cell_phone / $home_phone / $work_phone, naissance: $birthdate, email: $email, pr&eacute;nom: " .
			db2web($first_name) . ", nom: " . db2web($last_name)) ;
}

// Fetch AGAIN all information about the user since they may have been modified by the above...
$result = mysqli_query($mysqli_link, "select *,u.username as username,u.email as email, date(p.birthdate) as birthdate
	from $table_person p join $table_users u on p.jom_id = u.id left join jom_kunena_users k on k.userid=u.id
	where u.id = $displayed_id") or die("Erreur interne: " . mysqli_error($mysqli_link)) ;
$me = mysqli_fetch_array($result) or die("Utilisateur inconnu") ;
$me['name'] = db2web($me['name']) ; 
$me['first_name'] = db2web($me['first_name']) ; 
$me['last_name'] = db2web($me['last_name']) ; 
$me['city'] = db2web($me['city']) ; 
// Be paranoid
foreach($me as $key => $value)
	$me[$key] = htmlspecialchars($value, ENT_QUOTES) ;

?><html>
<head>
<!-- TODO trim the CSS -->
<link rel="stylesheet" type="text/css" href="profile.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<meta charset="utf-8">
<!--meta name="viewport" content="width=320"-->
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- http://www.alsacreations.com/article/lire/1490-comprendre-le-viewport-dans-le-web-mobile.html -->
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<!-- http://www.w3schools.com/bootstrap/ -->
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
<title>Profil de <?=$me['name']?></title>
<script>
const
	// preset Javascript constant fill with the right data from db.php PHP variables
	userFullName = '<?=$userFullName?>' ;
	userName = '<?=$userName?>' ;
	userId = <?=$userId?> ;
	userIsPilot = <?=($userIsPilot)? 'true' : 'false'?> ;
	userIsAdmin = <?=($userIsAdmin)? 'true' : 'false'?> ;
	userIsInstructor = <?=($userIsInstructor)? 'true' : 'false'?> ;
	userIsMechanic = <?=($userIsMechanic)? 'true' : 'false'?> ;

</script>
<!--- cacheable data -->
<script data-cfasync="true" src="profile.js"></script>
<script data-cfasync="true" src="members.js"></script>
<!-- Matomo -->
<script type="text/javascript">
  var _paq = window._paq = window._paq || [];
  /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
  _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
  _paq.push(["setCookieDomain", "*.spa-aviation.be"]);
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="//analytics.vyncke.org/";
    _paq.push(['setTrackerUrl', u+'matomo.php']);
    _paq.push(['setSiteId', '5']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Code -->
</head>
<body onload="init(<?=$displayed_id?>);">

<div class="container-fluid">
<div class="row">
<h3>Donn&eacute;es de contact de <?=$me['name']?></h3>
</div><!-- row -->

<div class="row">
<div class="form-group">
	<label class="control-label col-sm-6 col-md-3">En tant que membre, vous pouvez choisir le membre &agrave; afficher:</label>
	<div class="col-sm-6 col-md-3">
		<select id="pilotSelect" name="displayed_id" class="form-control" onchange="pilotChange('<?=$_SERVER['PHP_SELF']?>', this);">
		</select>
	</div> <!-- col -->
	</div> <!-- form-group -->
<div class="col-sm-12 col-md-3">
Ajouter <?=$me['name']?> &agrave; mes contacts: <a href="vcard.php?id=<?=$displayed_id?>">
          <span class="glyphicon glyphicon-cloud-download"></span>
        </a>
	</div> <!--- col -->
</div> <!--- row -->

<div class="row">
<div class="col-12">
<?php
print($change_profile_message) ; // Display any error message
$read_only_attribute = ($read_only) ? 'readonly' : '' ;
?>
</div> <!-- col -->
</div> <!-- row -->

<br/>
<ul class="nav nav-tabs nav-justified">
        <li class="active"><a data-toggle="tab" href="#main">Contact</a></li>
        <li><a data-toggle="tab" href="#validity">Validit&eacute;s</a></li>
        <li><a data-toggle="tab" href="#photo">Photo</a></li>
        <li><a data-toggle="tab" href="#social_network">R&eacute;seaux sociaux</a></li>
        <li><a data-toggle="tab" href="#groups">Groupes</a></li>
</ul>

<div class="tab-content">
<div id="main" class="tab-pane fade in active">

<form action="<?=$_SERVER['PHP_SELF']?>" method="get" role="form" class="form-horizontal">
<input type="hidden" name="action" value="profile">
<input type="hidden" name="displayed_id" value="<?=$displayed_id?>">
<div class="form-group">
	<label class="control-label col-sm-4 col-md-2">Nom d'utilisateur:</label>
	<div class="col-sm-4">
		<input type="text" class="form-control" name="username" value="<?=$me['username']?>" readonly>
		<span class="input-group-addon">(contacter webmaster@spa-aviation.be pour changer)</span>
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group">
	<label class="control-label col-sm-4 col-md-2">Nom complet:</label>
	<div class="col-sm-4">
		 <!-- p class="form-control-static">email@example.com</p -->
		<input type="text" class="form-control" name="name" value="<?=$me['name']?>" readonly>
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group">
	<label class="control-label col-sm-4 col-md-2">Pr&eacute;nom:</label>
	<div class="col-sm-4">
		<input type="text" class="form-control" name="first_name" value="<?=$me['first_name']?>" autocomplete="given-name" <?=$read_only_attribute?>>
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group">
	<label class="control-label col-sm-4 col-md-2">Nom:</label>
	<div class="col-sm-4">
			<input type="text" class="form-control" name="last_name" value="<?=$me['last_name']?>" autocomplete="family-name" <?=$read_only_attribute?>>
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group">
        <label class="control-label col-sm-4 col-md-2">Adresse e-mail:</label>
        <div class="col-sm-4">
	       	<div class="input-group">
                <input type="email" class="form-control" name="email" value="<?=$me['email']?>" autocomplete="email" <?=$read_only_attribute?>>
                <span class="input-group-addon">(obligatoire)</span>
            </div><!-- input group -->
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group">
        <label class="control-label col-sm-4 col-md-2">T&eacute;l&eacute;phone mobile:</label>
        <div class="col-sm-4">
	       	<div class="input-group">
                <input type="tel" class="form-control" name="cell_phone" value="<?=$me['cell_phone']?>" autocomplete="mobile tel" <?=$read_only_attribute?>>
                <span class="input-group-addon">(obligatoire)</span>
            </div><!-- input group -->
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group">
        <label class="control-label col-sm-4 col-md-2">T&eacute;l&eacute;phone priv&eacute;:</label>
        <div class="col-sm-4">
                <input type="tel" class="form-control" name="home_phone" value="<?=$me['home_phone']?>" autocomplete="home tel" <?=$read_only_attribute?>>
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group">
        <label class="control-label col-sm-4 col-md-2">T&eacute;l&eacute;phone travail:</label>
        <div class="col-sm-4">
                <input type="tel" class="form-control" name="work_phone" value="<?=$me['work_phone']?>" autocomplete="work tel" <?=$read_only_attribute?>>
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group">
        <label class="control-label col-sm-4 col-md-2">Ville:</label>
        <div class="col-sm-4">
        	<div class="input-group">
                <input type="text" class="form-control" name="city" value="<?=$me['city']?>" autocomplete="address-level2" <?=$read_only_attribute?>>
                <span class="input-group-addon">(optionnel)</span>
            </div><!-- input group -->
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group">
        <label class="control-label col-sm-4 col-md-2">Pays:</label>
        <div class="col-sm-4">
        	<div class="input-group">
                <input type="text" class="form-control" name="country" value="<?=$me['country']?>" autocomplete="country" <?=$read_only_attribute?>>
                <span class="input-group-addon">(optionnel)</span>
            </div><!-- input group -->
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group">
        <label class="control-label col-sm-4 col-md-2">Date de naissance:</label>
        <div class="col-sm-4">
        	<div class="input-group">
                <input type="date" class="form-control" name="birthdate" placeholder="AAAA-MM-JJ" value="<?=$me['birthdate']?>" autocomplete="bday" <?=$read_only_attribute?>>
                <span class="input-group-addon">(optionnel)</span>
            </div><!-- input group -->
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group">
        <label class="control-label col-sm-4 col-md-2">Genre:</label>
        <div class="col-sm-4">
        	<div class="input-group">
				<select class="form-control" name="sex" <?=$read_only_attribute?>>
					<option value="0" <?=($me['sex'] == 0) ? 'selected':''?>>Inconnu</option>
					<option value="1" <?=($me['sex'] == 1) ? 'selected':''?>>Masculin</option>
					<option value="2" <?=($me['sex'] == 2) ? 'selected':''?>>F&eacute;minin</option>
				</select>
                <span class="input-group-addon">(optionnel)</span>
            </div><!-- input group -->
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group">
        <label class="control-label col-sm-4 col-md-2">Heures de vol:</label>
        <div class="col-sm-4">
        	<div class="input-group">
				<select class="form-control" name="hide_flight" <?=$read_only_attribute?>>
					<option value="0" <?=($me['hide_flight_time'] == 0) ? 'selected':''?>>Montrer</option>
					<option value="1" <?=($me['hide_flight_time'] == 1) ? 'selected':''?>>Cacher</option>
				</select>
                <span class="input-group-addon">(optionnel)</span>
            </div><!-- input group -->
	</div> <!-- col -->
</div> <!-- form-group -->

<?php
if (! $read_only) {
	print('<div class="form-group"><button type="submit" class="col-sm-offset-2 col-md-offset-1 col-sm-3 col-md-2 btn btn-primary">Enregistrer les changements</button></div>') ;
}
?>
</form>
</div> <!-- id=main -->

<div id="photo" class="tab-pane fade">
<?php
if ($me['avatar'] != '') {
	print("<div class=\"row\">
		<img src=\"$avatar_root_uri/$me[avatar]\" class=\"col-xs-12 col-sm-6 col-md-2\">
		<img src=\"https://www.gravatar.com/avatar/" . md5(strtolower(trim($me['email']))) . "?s=200&d=blank&r=pg\" class=\"col-xs-12 col-sm-6 col-md-2\">
		</div> <!-- row -->\n") ;
}
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
			<button type="submit" class="form-control col-sm-4 col-md-2 btn btn-primary">Mettre &agrave; jour la photo</button>
		</div> <!-- form-group -->
	</form>
	</div> <!-- row -->') ;
}
?>
</div> <!-- id=photo -->

<div id="social_network" class="tab-pane fade">
<div class="row">
	Les adresses Internet des pages des r&eacute;seaux sociaux de ce membre. Les logos, si pr&eacute;sents, sont cliquables.
</div> <!-- row -->
<div class="row">
<form action="<?=$_SERVER['PHP_SELF']?>" method="get" role="form" class="form-horizontal">
<?php
if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'social' and !$read_only) {
}
$facebook_img = ($me['facebook'] != '') ? "<a href=\"https://www.facebook.com/$me[facebook]\" target=\"_blank\"><img src=\"facebook.jpg\"></a>\n" : '' ;
$linkedin_img = ($me['linkedin'] != '') ? "<a href=\"https://www.linkedin.com/in/$me[linkedin]\" target=\"_blank\"><img src=\"linkedin.jpg\"></a>\n" : "" ;
$skype_img = ($me['skype'] != '') ? "<a href=\"skype:$me[skype]\"><img src=\"skype.png\"></a>\n" : "" ;
$twitter_img = ($me['twitter'] != '') ? "<a href=\"https://www.twitter.com/$me[twitter]\" target=\"_blank\"><img src=\"twitter.jpg\"></a>\n" : '' ;
?>
<input type="hidden" name="action" value="social">
<input type="hidden" name="displayed_id" value="<?=$displayed_id?>">
<div class="form-group">
	<label class="control-label col-sm-2 col-md-1">Facebook <?=$facebook_img?>:</label>
	<div class="col-sm-4 col-md-2">
		<input type="text" class="form-control" name="facebook" value="<?=$me['facebook']?>" <?=$read_only_attribute?>>
		<span class="help-block">Ce qui suit https://www.facebook.com/ sur votre page personelle.</span>
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group">
	<label class="control-label col-sm-2 col-md-1">LinkedIn <?=$linkedin_img?>:</label>
	<div class="col-sm-4 col-md-2">
		<input type="text" class="form-control" name="linkedin" value="<?=$me['linkedin']?>" <?=$read_only_attribute?>>
		<span class="help-block">Ce qui suit https://www.linkedin.com/in/ sur votre profil.</span>
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group">
	<label class="control-label col-sm-2 col-md-1">Skype <?=$skype_img?>:</label>
	<div class="col-sm-4 col-md-2">
		<input type="text" class="form-control" name="skype" value="<?=$me['skype']?>" <?=$read_only_attribute?>>
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group">
	<label class="control-label col-sm-2 col-md-1">Twitter <?=$twitter_img?>:</label>
	<div class="col-sm-4 col-md-2">
		<input type="text" class="form-control" name="twitter" value="<?=$me['twitter']?>" <?=$read_only_attribute?>>
		<span class="help-block">Ce qui suit https://www.twitter.com/ sur votre page personelle.</span>
	</div> <!-- col -->
</div> <!-- form-group -->
<?php
if (! $read_only) {
	print('<div class="form-group"><button type="submit" class="col-sm-offset-2 col-md-offset-1 col-sm-3 col-md-2 btn btn-primary">Enregistrer les changements</button></div>') ;
}
?>
</form>
</div> <!-- row -->
</div> <!-- id=social_network -->

<div id="groups" class="tab-pane fade">
<div class="row">
<?
if ($displayed_id == $userId)
	print("Vous faites partie des groupes: ") ;
else
	print("Ce membre fait partie des groupes: ") ;
$joomla_groups = array() ;
$result = mysqli_query($mysqli_link, "select group_id from $table_user_usergroup_map where user_id = $displayed_id")
	or die("Cannot access groups: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) 
	$joomla_groups[$row['group_id']] = true ;
$groupes = array() ;
if (array_key_exists($joomla_member_group, $joomla_groups)) $groupes[] = "Membre" ;
if (array_key_exists($joomla_pilot_group, $joomla_groups)) $groupes[] = "Pilote" ;
if (array_key_exists($joomla_student_group, $joomla_groups)) $groupes[] = "&Eacute;l&egrave;ve" ;
if (array_key_exists($joomla_mechanic_group, $joomla_groups)) $groupes[] = "M&eacute;cano" ;
if (array_key_exists($joomla_instructor_group, $joomla_groups)) $groupes[] = "Instructeur" ;
if (array_key_exists($joomla_admin_group, $joomla_groups)) $groupes[] = "membre du Conseil d'Administration" ;
if (array_key_exists($joomla_admin_group, $joomla_groups) || array_key_exists($joomla_sysadmin_group, $joomla_groups) || array_key_exists($joomla_superuser_group, $joomla_groups)) $groupes[] = "Administrateur syst&egrave;me" ;
print("<i>" . implode(', ', $groupes) . "</i>. <br/>Vous ne pouvez pas changer vous-m&ecirc;mes vos groupes, c'est li&eacute; &agrave; au type d'inscription.") ;
?>
</div> <!-- row -->
</div> <!-- id=groups -->

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
	order by t.name") or die("Erreur systeme a propos de l'access a validity: " . mysqli_error($mysqli_link)) ;
$options = '<option value="-1" disabled selected> -- validité à ajouter et remplir les cases sur cette ligne--</option>' ;
while ($row = mysqli_fetch_array($result)) {
	if ($row['grant_date']) {
		if ($userId == $displayed_id)
			$delete = " <a href=\"$_SERVER[PHP_SELF]?displayed_id=$displayed_id&validity_id=$row[id]&action=delete_rating\"><span class=\"glyphicon glyphicon-trash text-danger\"></span></a>" ;
		else
			$delete = '' ;
		print("<tr><td class=\"validityNameCell\">" . db2web($row['name']) . "$delete</td>\n") ;
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
	<td class=\"validityCell\">Nouvelle validit&eacute;: <select name=\"newValidityId\" class=\"form-control\">$options</select></td>
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
	<button type="submit" class="btn btn-primary" id="submitButton" disabled>Enregistrer les changements</button>
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

<?php
$version_php = date ("Y-m-d H:i:s.", filemtime('profile.php')) ;
$version_js = date ("Y-m-d H:i:s.", filemtime('profile.js')) ;
$version_css = date ("Y-m-d H:i:s.", filemtime('profile.css')) ;
?>
<div class="row hidden-xs">
<hr>
<div class="copyright">R&eacute;alisation: Eric Vyncke, d&eacute;cembre 2014 - mars 2017, pour RAPCS, Royal A&eacute;ro Para Club de Spa, ASBL<br>
Versions: PHP=<?=$version_php?>, JS=<?=$version_js?>, CSS=<?=$version_css?></div>
</div> <!-- row-->
</div> <!-- container -->
</body>
</html>