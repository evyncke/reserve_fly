<?php
/*
   Copyright 2026 Eric Vyncke

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

require_once "dbi.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
} ;
require_once 'mobile_header5.php' ;
if ($userId != 62) journalise($userId, "I", "Accessing OAuth/WebAuthn registration page") ;

require_once 'vendor/autoload.php'; // Ensure you have the Google and Facebook SDKs installed via Composer
use Facebook\Facebook;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\LinkedIn;

// session_start(); // Probably already started in mobile_header5.php or via dbi.php

// Initialize Facebook Client
$facebook = new Facebook([
    'app_id' => $fb_app_id,
    'app_secret' => $fb_app_secret,
    'default_graph_version' => 'v19.0',
]);

// Initialize Google Client
$google = new Google([
    'clientId'     => $google_client_id,
    'clientSecret' => $google_client_secret,
    'redirectUri'  => 'https://www.spa-aviation.be/resa/mobile_login.php', // Doit être identique à la console Google
]);

// Initialize LinkedIn Client
$linkedin = new LinkedIn([
    'clientId' => $linkedin_client_id,
    'clientSecret' => $linkedin_client_secret,
    'redirectUri' => 'https://www.spa-aviation.be/resa/mobile_login.php',
]);

// Generate OAuth URLs
$facebookHelper = $facebook->getRedirectLoginHelper();
$facebookAuthUrl = $facebookHelper->getLoginUrl('https://www.spa-aviation.be/resa/mobile_web_o_auth.php', ['email','public_profile','user_link']);
// TODO find the equivalent $_SESSION['facebook_oauth2state'] = $facebook->getState(); // Unsure if used later...
$googleAuthUrl = $google->getAuthorizationUrl();
$_SESSION['google_oauth2state'] = $google->getState(); // Unsure if used later...
$linkedinAuthUrl = $linkedin->getAuthorizationUrl();
$_SESSION['linkedin_oauth2state'] = $linkedin->getState(); // Unsure if used later...

// Check for deactivation requests
if (isset($_REQUEST['deactivate'])) {
	$provider = $_REQUEST['deactivate'];
	if ($provider == 'facebook') {
		mysqli_query($mysqli_link, "UPDATE $table_person SET facebook_id = NULL, facebook_token = NULL WHERE jom_id = $userId")
			or journalise($userId, "F", "Cannot deactivate Facebook OAuth: " . mysqli_error($mysqli_link)) ;
		journalise($userId, "I", "Deactivated Facebook OAuth") ;
	}
}
?>
<h2>Connexion via OAuth et Webauthn</h2>
<p>Il est possile de se connecter en utilisant un fournisseur d'identité OAuth2 (Google, LinkedIn, Facebook, etc.) 
ou/et une clé de sécurité WebAuthn (Apple TouchID, FaceId, empreinte digitale, clé USB, clé NFC, clé Bluetooth 
ou Passkey intégrée au téléphone ou à l'ordinateur).</p>
<p>Ce sont des termes barbares mais qui permettent d'avoir une sécurité maximale sans avoir à retenir de mot de passe. Et vous pouvez évidemment combiner
	tous les systèmes pour plus de flexibilités.
</p>

<hr>
<h3>Fournisseur d'identité OAuth2 (Facebook, Google, LinkedIn, etc.)</h3>
<p>Il est possible de se connecter en utilisant un fournisseur d'identité OAuth2 (Google, LinkedIn, Facebook, etc.). 
	Cela vous permet de vous connecter sans mot de passe en utilisant votre compte existant chez l'un de ces fournisseurs.</p>
<p>Ces options doivent être activées via cette page ou dans l'onglet "Réseaux sociaux" de votre profil utilisateur. <mark>SAUF</mark> si l'adresse 
	email de votre compte sur Spa-Aviation est la même que celle de votre compte Google, LinkedIn ou Facebook, auqel cas, il n'y a rien à faire.
</p>
<?php
$result = mysqli_query($mysqli_link, "SELECT * 
	FROM $table_person 
	WHERE jom_id = $userId") 
	or journalise($userId, "E", "Cannot query OAuth providers: " . mysqli_error($mysqli_link)) ;
$row = mysqli_fetch_array($result) or journalise($userId, "F", "User not found...") ;
if (str_ends_with($row['email'], "@gmail.com") && ! $row['google_id']) {
	$gmail_msg = "Votre adresse email est une adresse Gmail. Vous pouvez vous connecter avec Google sans autre action de votre part." ;
} else {
	$gmail_msg = '' ;
}
?>
<table class="table table-striped table-bordered w-auto">
	<thead>
		<tr><th>Fournisseur d'identité</th><th>Activé</th><th>Note</th></tr>
	</thead>
	<tbody>
		<tr><td><i class="bi bi-facebook"></i> Facebook</td><td><?php print($row['facebook_id'] ? 
			"Oui<br/><a href='$_SERVER[PHP_SELF]?deactivate=facebook'>Désactiver</a>" : 
			"Non<br/><a href='$facebookAuthUrl'>Activer</a>") ; ?></td><td></td></tr>
		<tr><td><i class="bi bi-google"></i> Google</td><td><?php print($row['google_id'] ? "Oui" : "Non<br/><a href='$googleAuthUrl'>Activer</a>") ; ?></td><td><?php print($gmail_msg)?></td></tr>
		<tr><td><i class="bi bi-linkedin"></i> LinkedIn</td><td><?php print($row['linkedin_id'] ? "Oui" : "Non<br/><a href='$linkedinAuthUrl'>Activer</a>") ; ?></td><td></td></tr>
	</tbody>
</table>

<hr>
<h3>Clé de sécurité WebAuthn (Passkey, TouchID, FaceID, clé USB, clé NFC, clé Bluetooth)</h3>
<p>Il y a une clé par appareil (téléphone, tablette, ordinateur). Vous pouvez en enregistrer plusieurs. 
	Cela vous permet de vous connecter sans mot de passe en utilisant la reconnaissance faciale, l'empreinte digitale ou une clé physique.</p>
<table class="table table-striped table-bordered w-auto">
	<thead>
		<tr><th>Dernière utilisation</th><th>Appareil</th><th>Depuis le</th></tr>
	</thead>
	<tbody>
<?php
$result = mysqli_query($mysqli_link, "SELECT * 
	FROM $table_passkey 
	WHERE pk_userid = $userId 
	ORDER BY pk_last_use DESC") 
	or journalise($userId, "E", "Cannot query passkeys: " . mysqli_error($mysqli_link)) ;
if (mysqli_num_rows($result) == 0) {
	print("<tr><td colspan='3'>Aucune clé de sécurité enregistrée pour cet utilisateur.</td></tr>") ;
} else {
	while ($row = mysqli_fetch_array($result)) {
		print("<tr><td>$row[pk_last_use]</td><td>" . htmlspecialchars($row['pk_last_device']) . "</td>
			<td>$row[pk_registration]</td></tr>") ;
	}
} ;
?>
	</tbody>
</table>	
<!-- Add WebAuthn buttons to the login form -->
<div class="text-center">
	<button id="webauthn-register" class="btn btn-primary"><i class="bi bi-fingerprint"></i> Activer Passkey sur cet appareil/browser</button><br/>
    <div id="feedback" class="mt-2"></div>
</div>
<div class="mt-4 pt-2 border-top small text-body-secondary">Le site web ne voit aucune information de vos comptes Facebook, Googgle, ou LinkedIn en dehors de votre nom, 
    adresse email et photo de profil. Toutes les informations d'authentification sont gérées par votre fournisseur d'identité.
    Il en est de même pour les empreintes digitales, FaceID, TouchID ou Passkeys qui ne sont jamais transmises au site web.</div>
<script>
var helper = {
	atb: b => {
		let u = new Uint8Array(b), s = "";
		for (let i = 0; i < u.byteLength; i++) s += String.fromCharCode(u[i]);
			return btoa(s);
		},

	bta: o => {
		let pre = "=?BINARY?B?", suf = "?=";
		for (let k in o) {
			if (typeof o[k] == "string") {
				let s = o[k];
				if (s.startsWith(pre) && s.endsWith(suf)) {
					let raw = window.atob(s.slice(pre.length, -suf.length)),
					u = new Uint8Array(raw.length);
					for (let i = 0; i < raw.length; i++) u[i] = raw.charCodeAt(i);
					o[k] = u.buffer;
				}
			} else {
				helper.bta(o[k]);
			}
		}
	}
}

// WebAuthn Registration
const feedback = document.getElementById('feedback');
const registerButton = document.getElementById('webauthn-register');

registerButton.addEventListener('click', async () => {
	console.log('Button clicked: starting WebAuthn registration...');
	feedback.innerHTML = '<div class="alert alert-info">Contacting server...</div>';
	try {
		const response = await fetch('passkey_handler.php?action=webauthn_register');
		let options = await response.json();
		feedback.innerHTML = '<div class="alert alert-info">Server response received.</div>';
		console.log('Response received for registration :-)') ;
		console.log('response:', response);
		helper.bta(options);
		console.log('After decode, options: ', options);
		// Let's fetch the credential method using publicKey options
		const credential = await navigator.credentials.create(options);
		console.log('After credentials.create:', credential);
		console.log('typeof(credential):', typeof(credential));
		feedback.innerHTML = '<div class="alert alert-info">Got navigator credentials.</div>';
		// Send back to handler
		const browser = await getBrowser();
		const verify = await fetch('passkey_handler.php?action=verify-registration', {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify({ // TODO double-check all fields needed?
				browser: browser,
				id: credential.id,
				rawId: btoa(String.fromCharCode(...new Uint8Array((credential.rawId)))),
				type: credential.type,
				transport: credential.response.getTransports ? credential.response.getTransports() : null,
				client: helper.atb(credential.response.clientDataJSON),
				attest: helper.atb(credential.response.attestationObject)})
		});
		console.log('After sending to verify-registration:', verify);
		if (verify.ok) // TODO check whether success=true in the response
			feedback.innerHTML = '<div class="alert alert-success">Passkey Saved!</div>';
		else 
			feedback.innerHTML = '<div class="alert alert-danger">Passkey Registration Failed!</div>';
	} catch (e) {
		feedback.innerHTML = `<div class="alert alert-danger">Error: ${e.message}</div>`;
		console.error('Error during WebAuthn registration:', e);
	}
});

async function getBrowser() {
  // Modern path
  if ('userAgentData' in navigator) {
    const brands = navigator.userAgentData.brands;
    const main = brands.find(b => b.brand !== 'Not.A/Brand') ?? brands[0];
    return main.brand;
  }

  // Legacy fallback
  const ua = navigator.userAgent;
  if (/Edg\//.test(ua)) return 'Microsoft Edge';
  if (/OPR\//.test(ua)) return 'Opera';
  if (/Firefox\//.test(ua)) return 'Firefox';
  if (/Chrome\//.test(ua)) return 'Google Chrome';
  if (/Safari\//.test(ua)) return 'Safari';

  return 'Unknown';
}

</script>
</body>
</html>