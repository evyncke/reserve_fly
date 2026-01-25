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
?>
<h2>Connexion via OAuth et Webauthn</h2>
<p>Il est possile de se connecter en utilisant un fournisseur d'identité OAuth2 (Google, LinkedIn, Facebook, etc.) 
ou/et une clé de sécurité WebAuthn (Apple TouchID, FaceId, empreinte digitale, clé USB, clé NFC, clé Bluetooth 
ou Passkey intégrée au téléphone ou à l'ordinateur).</p>
<p>Ce sont des termes barbares mais qui permettent d'avoir une sécurité maximale sans avoir à retenir de mot de passe.</p>
<p>Ces options doivent être activées via cette page ou dans l'onglet "Réseaux sociaux" de votre profil utilisateur. <mark>SAUF</mark> si l'adresse 
	email de votre compte sur Spa-Aviation est la même que celle de votre compte Google, LinkedIn ou Facebook, auqel cas, il n'y a rien à faire.
</p>

<!-- Add WebAuthn buttons to the login form -->
<div class="text-center">
	<button id="webauthn-register" class="btn btn-outline-secondary"><i class="bi bi-fingerprint"></i> Register Passkey (par exemple, Apple FaceId)</button><br/>
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
		const verify = await fetch('passkey_handler.php?action=verify-registration', {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify({ // TODO double-check all fields needed?
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
</script>
</body>
</html>