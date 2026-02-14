<?php
/*
   Copyright 2013-2026 Eric Vyncke

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

$callback = $_REQUEST['cb'] ;
if ($callback == '') $callback = 'resa/mobile.php' ; // By default

if ($userId > 0) {
    header("Location: https://www.spa-aviation.be/$callback", TRUE, 307) ;
    exit ;
}

$connect_msg = '' ;

if (isset($_REQUEST['username']) and isset($_REQUEST['password'])) {
    $result_login = JFactory::getApplication()->login(
        [
            'username' => $_REQUEST['username'],
            'password' => $_REQUEST['password']
        ],
        [
            'remember' => true,
            'silent'   => true
        ]
    );
    if ($result_login) {
        header("Location: https://www.spa-aviation.be/$callback", TRUE, 307) ;
        $joomla_user = JFactory::getUser() ;
        $app = JFactory::getApplication('site');
        $joomla_user->lastvisitDate = JFactory::getDate()->toSql();
        $joomla_user->save();
        $options = array('remember' => true); // Vous pouvez mettre true si vous gérez les cookies
        $app->triggerEvent('onUserLogin', array(
            (array) $joomla_user,
            $options));
        journalise($joomla_user->id, "I", "Connection of $_REQUEST[username] from $callback") ;
        exit ;
    } else {
        $connect_msg = "Utilisateur inconnu ou mauvais mot de passe." ;
        journalise(0, "W", "Invalid password for $_REQUEST[username] from $callback") ;
    }
}

require_once 'mobile_header5.php' ;

// Google API Client Library for PHP
// GitHub Repository:
// GitHub Repository: https://github.com/googleapis/google-api-php-client
// composer require google/apiclient
// Facebook SDK for PHP:
// GitHub Repository: https://github.com/facebook/php-graph-sdk
// composer require facebook/graph-sdk
//
// Transfert from resa/vendor => scp -rp . spaaviat@ftp.cluster015.hosting.ovh.net:www/resa/vendor (+ password of course)
require_once 'vendor/autoload.php'; // Ensure you have the Google and Facebook SDKs installed via Composer

use League\OAuth2\Client\Provider\Google;
use Facebook\Facebook;
use League\OAuth2\Client\Provider\LinkedIn;

// session_start(); // Probably already started in mobile_header5.php or via dbi.php

// Initialize Google Client
// See https://console.cloud.google.com/apis/credentials?authuser=1&project=regal-throne-481610-u7
$google = new Google([
    'clientId'     => $google_client_id,
    'clientSecret' => $google_client_secret,
    'redirectUri'  => 'https://www.spa-aviation.be/resa/mobile_login.php', // Doit être identique à la console Google
]);

// Initialize Facebook Client
// See also https://developers.facebook.com/apps/1070912613042275/dashboard/?business_id=363522343467274
$facebook = new Facebook([
    'app_id' => $fb_app_id,
    'app_secret' => $fb_app_secret,
    'default_graph_version' => 'v18.0',
]);

// Initialize LinkedIn Client
$linkedin = new LinkedIn([
    'clientId' => $linkedin_client_id,
    'clientSecret' => $linkedin_client_secret,
    'redirectUri' => 'https://www.spa-aviation.be/resa/mobile_login.php',
]);

// Check whether  OAuth callback
if (isset($_GET['state']) and $_GET['state'] != '' and isset($_GET['code']) and $_GET['code'] != '') {
    // Is is Google or Facebook or LinkedIn?
    if (isset($_SESSION['google_oauth2state']) and $_GET['state'] === $_SESSION['google_oauth2state']) {
        try {
            $accessToken = $google->getAccessToken('authorization_code', ['code' => $_GET['code']]);
            if (isset($accessToken)) {
                $googleUser = $google->getResourceOwner($accessToken);
                journalise($userId, "I", "Google OAuth: user info obtained: google id = " . $googleUser->getId() . 
                    ", avatar = " . $googleUser->getAvatar() .
                    ", name = " . $googleUser->getName() . ", email = " . $googleUser->getEmail()) ;
                // Check if user exists with this google id or email
                $query = "SELECT jom_id 
                    FROM $table_person AS p JOIN $table_users AS u ON p.jom_id = u.id
                    WHERE u.block = 0 AND (google_id='" . mysqli_real_escape_string($mysqli_link, $googleUser->getId()) . "' 
                        OR p.email='" . mysqli_real_escape_string($mysqli_link, $googleUser->getEmail()) . "')";
                $result = mysqli_query($mysqli_link, $query)
                    or journalise(0, "F", "Error querying for google user: " . mysqli_error($mysqli_link));
                $row = mysqli_fetch_assoc($result);
                if ($row) {   
                    // User found, log them in
                    $userId = $row['jom_id'];
                    $joomla_user = JFactory::getUser($userId);
                    $app = JFactory::getApplication('site');
                    $session = JFactory::getSession();
                    $session->set('user', $joomla_user);
                    $joomla_user->lastvisitDate = JFactory::getDate()->toSql();
                    $joomla_user->save();
                    $options = array('remember' => true); // Vous pouvez mettre true si vous gérez les cookies
                    $app->triggerEvent('onUserLogin', array(
                        (array) $joomla_user,
                        $options));
                    journalise($userId, "I", "Google login for user id $userId (" . $googleUser->getEmail() . ") from $callback.") ;
                    mysqli_query($mysqli_link, "UPDATE $table_person SET google_id='" . mysqli_real_escape_string($mysqli_link, $googleUser->getId()) . "', " .
                        " google_token='" . mysqli_real_escape_string($mysqli_link, $accessToken) . "'
                        WHERE jom_id = $userId") 
                        or journalise($userId, "E", "Error updating google_id/token for user id $userId in $table_person: " . mysqli_error($mysqli_link)) ;
                    unset($_SESSION['google_oauth2state']); // Clear Google state
                    header("Location: https://www.spa-aviation.be/$callback", TRUE, 307);
                    exit;
                } else {
                    journalise(0, "W", "No user found for google id $googleUser->getId() or email $googleUser->getEmail()") ;
                    $connect_msg = "Aucun utilisateur n'a été trouvé pour votre compte Google via votre adresse email " . $googleUser->getEmail() . ". 
                        Veuillez utiliser une autre méthode ou lier votre profil à votre compte Google via votre profil sur ce site onglet Réseaux Sociaux." ;
                }
            }
        } catch (Exception $e) {
            journalise(0, "E", 'Google OAuth Error: ' . $e->getMessage());
        }
        unset($_SESSION['google_oauth2state']); // Clear Google state    
    } // Is is Google or Facebook or LinkedIn?
    else if (isset($_SESSION['linkedin_oauth2state']) and $_GET['state'] === $_SESSION['linkedin_oauth2state']) {
        try {
            $accessToken = $linkedin->getAccessToken('authorization_code', ['code' => $_GET['code']]);
            if (isset($accessToken)) {
                // Fetch the resource owner details w/o using the provider's built-in method as it does not work properly
                $headers = [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                ];
                $ch = curl_init('https://api.linkedin.com/v2/userinfo');
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                $linkedInUser = json_decode($response, true);
                journalise($userId, "I", "LinkedIn OAuth: user info obtained: linkedin id = " . $linkedInUser['sub'] .
                    ", avatar = " . $linkedInUser['picture'] .
                    ", email = " . $linkedInUser['email']) ;
                // Check if user exists with this linkedin id or email
                $query = "SELECT jom_id 
                    FROM $table_person AS p JOIN $table_users AS u ON p.jom_id = u.id
                    WHERE u.block = 0 AND (linkedin_id='" . mysqli_real_escape_string($mysqli_link, $linkedInUser['sub']) . "' 
                        OR p.email='" . mysqli_real_escape_string($mysqli_link, $linkedInUser['email']) . "')";
                $result = mysqli_query($mysqli_link, $query)
                    or journalise(0, "F", "Error querying for linkedin user: " . mysqli_error($mysqli_link));
                $row = mysqli_fetch_assoc($result);
                if ($row) {   
                    // User found, log them in
                    $userId = $row['jom_id'];
                    $joomla_user = JFactory::getUser($userId);
                    $app = JFactory::getApplication('site');
                    $session = JFactory::getSession();
                    $session->set('user', $joomla_user);
                    $joomla_user->lastvisitDate = JFactory::getDate()->toSql();
                    $joomla_user->save();
                    $options = array('remember' => true); // Vous pouvez mettre true si vous gérez les cookies
                    $app->triggerEvent('onUserLogin', array(
                        (array) $joomla_user,
                        $options));
                    journalise($userId, "I", "LinkedIn login for user id $userId (" . $linkedInUser['email'] . ") from $callback.") ;
                    mysqli_query($mysqli_link, "UPDATE $table_person SET linkedin_id='" . mysqli_real_escape_string($mysqli_link, $linkedInUser['sub']) . "', " .
                        " linkedin_token='" . mysqli_real_escape_string($mysqli_link, $accessToken) . "'
                        WHERE jom_id = $userId") 
                        or journalise($userId, "E", "Error updating linkedin_id/token for user id $userId in $table_person: " . mysqli_error($mysqli_link)) ;
                    unset($_SESSION['linkedin_oauth2state']); // Clear LinkedIn state
                    header("Location: https://www.spa-aviation.be/$callback", TRUE, 307);
                    exit;
                } else {
                    journalise(0, "W", "No user found for LinkedIn id $linkedInUser[sub] or email $linkedInUser[email]") ;
                    $connect_msg = "Aucun utilisateur n'a été trouvé pour votre compte LinkedIn via votre adresse email " . $linkedInUser['email'] . ". 
                        Veuillez utiliser une autre méthode ou lier votre profil à votre compte LinkedIn via votre profil sur ce site onglet Réseaux Sociaux." ;
                }
            }
        } catch (Exception $e) {
            journalise(0, "E", 'LinkedIn OAuth Error: ' . $e->getMessage());
        }
        unset($_SESSION['linkedin_oauth2state']); // Clear LinkedIn state
    } else { // Assume Facebook
        if (isset($_SESSION['google_oauth2state'])) unset($_SESSION['google_oauth2state']); // Clear Google state if any
        if (isset($_SESSION['linkedin_oauth2state'])) unset($_SESSION['linkedin_oauth2state']); // Clear LinkedIn state if any
        $helper = $facebook->getRedirectLoginHelper();
        try {
            $accessToken = $helper->getAccessToken();
            if (isset($accessToken)) {
                $response = $facebook->get('/me?fields=id,name,email', $accessToken);
                $facebookUser = $response->getGraphUser();
                journalise($userId, "I", "Facebook OAuth: user info obtained: facebook id = $facebookUser[id], name = $facebookUser[name], email = $facebookUser[email]") ;
                // Check if user exists with this facebook id or email
                $query = "SELECT jom_id 
                    FROM $table_person AS p JOIN $table_users AS u ON p.jom_id = u.id
                    WHERE u.block = 0 AND (facebook_id='" . mysqli_real_escape_string($mysqli_link, $facebookUser['id']) . "' 
                        OR p.email='" . mysqli_real_escape_string($mysqli_link, $facebookUser['email']) . "')";
                $result = mysqli_query($mysqli_link, $query)
                    or journalise(0, "F", "Error querying for facebook user: " . mysqli_error($mysqli_link));
                $row = mysqli_fetch_assoc($result);
                if ($row) {
                    // User found, log them in
                    $userId = $row['jom_id'];
                    $joomla_user = JFactory::getUser($userId);
                    $app = JFactory::getApplication('site');
                    $session = JFactory::getSession();
                    $session->set('user', $joomla_user);
                    $joomla_user->lastvisitDate = JFactory::getDate()->toSql();
                    $joomla_user->save();
                    $options = array('remember' => true); // Vous pouvez mettre true si vous gérez les cookies
                    $app->triggerEvent('onUserLogin', array(
                        (array) $joomla_user,
                        $options));
                    journalise($userId, "I", "Facebook login for user id $userId ($facebookUser[email]) from $callback.") ;
                    mysqli_query($mysqli_link, "UPDATE $table_person SET facebook_id='" . mysqli_real_escape_string($mysqli_link, $facebookUser['id']) . "', " .
                        " facebook_token='" . mysqli_real_escape_string($mysqli_link, $accessToken) . "'
                        WHERE jom_id = $userId") 
                        or journalise($userId, "E", "Error updating facebook_id/token for user id $userId in $table_person: " . mysqli_error($mysqli_link)) ;
                    header("Location: https://www.spa-aviation.be/$callback", TRUE, 307) ;
                    exit ;  
                } else {
                    journalise(0, "W", "No user found for Facebook id $facebookUser[id] or email $facebookUser[email]") ;
                    $connect_msg = "Aucun utilisateur n'a été trouvé pour votre compte Facebook. 
                        Veuillez utiliser une autre méthode ou lier votre profil à votre compte Facebook via votre profil sur ce site onglet Réseaux Sociaux." ;
                }
            }
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            journalise(0, "F", 'Facebook API Error: ' . $e->getMessage());
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            journalise(0, "F", 'Facebook SDK Error: ' . $e->getMessage());
        } catch (Exception $e) {
            journalise($userId, "E", 'General Error: ' . $e->getMessage());
        }
    }
}

// Generate OAuth URLs
$googleAuthUrl = $google->getAuthorizationUrl();
$_SESSION['google_oauth2state'] = $google->getState(); // Unsure if used later... could be useful to differentiate multiple OAuth providers
$facebookHelper = $facebook->getRedirectLoginHelper();
$facebookAuthUrl = $facebookHelper->getLoginUrl('https://www.spa-aviation.be/resa/mobile_login.php', ['email','public_profile','user_link']);
$linkedInAuthUrl = $linkedin->getAuthorizationUrl(['scope' => ['openid', 'profile', 'email']]);
//$linkedInAuthUrl = $linkedin->getAuthorizationUrl(['scope' => ['r_liteprofile', 'r_emailaddress']]);

$_SESSION['linkedin_oauth2state'] = $linkedin->getState(); // Unsure if used later... could be useful to differentiate multiple OAuth providers

?>

<div class="container">
    <h2>Connexion</h2>
    <p class="bg-danger"><?=$connect_msg?></p>
    <p class="bg-info">Pour accéder au site vous devez vous connecter soit via votre identifiant et mot de passe, ou les méthodes via un fournisseur d'identité ou une clé secrète (FaceID, TouchID, ...).</p>

    <form method="post" action="<?=$_SERVER['PHP_SELF']?>">
        <input type="hidden" name="cb" value="<?=$callback?>">

        <div class="d-flex align-items-center">
            <label for="username" class="form-label col-auto me-2">
                Identifiant:
            </label>
            <input type="text" class="form-control" id="username" name="username" placeholder="Votre nom d'utilisateur" autocomplete="username" value="<?=$_REQUEST['username']?>"><br/>
        </div> <!-- d-flex -->

        <div class="d-flex align-items-center">
            <label for="password" class="form-label col-auto me-2">
                Mot de passe:
            </label>
            <input class="form-control" type="password" id="password" placeholder="Votre mot de passe" name="password" autocomplete="current-password"><br/>
        </div> <!-- d-flex -->

        <input type="submit" class="btn btn-primary" value="Connexion">
    </form>

    <hr>

    <div class="text-center">
        <p><b>OU</b> via:</p>
        <a href="<?=$facebookAuthUrl?>&cb=<?=urlencode($callback)?>" class="btn btn-primary"><i class="bi bi-facebook"></i> Facebook</a>
        <a href="<?=$googleAuthUrl?>&cb=<?=urlencode($callback)?>" class="btn btn-outline-secondary"><img src="images/google.svg" width="20px" height="20px"> Google</a>
        <a href="<?=$googleAuthUrl?>&cb=<?=urlencode($callback)?>" class="btn btn-outline-secondary"><img src="images/google.svg" width="20px" height="20px"> Gmail</a>
        <a href="<?=$linkedInAuthUrl?>&cb=<?=urlencode($callback)?>" class="btn btn-outline-secondary"><i class="bi bi-linkedin"></i> LinkedIn</a>
    </div><!-- text-center -->
    <div class="row">
        <p class="text-muted mt-3">Les connexions via Google, Facebook, ou LinkedIn nécessitent que votre adresse email soit la même sur le système de réservation
            et sur Facebook ou Google (trivial si votre email est ...@gmail.com) ou LinkedIn.
            Si ce n'est pas le cas, veuillez utiliser la connexion via identifiant et mot de passe 
            et lier votre compte via votre profil sur ce site via le menu déroulant associé à votre nom et l'option
            <b><i class="bi bi-shield"></i> Mes connexions</b>.</p>
    </div><!-- row -->
    <div classs="row">
        <button id="webauthn-login" class="btn btn-outline-secondary"><i class="bi bi-fingerprint"></i> Clé secrète (FaceID, TouchID, ...)</button><br/>
        <div id="feedback" class="mt-2"></div>
    </div><!-- row -->   
</div> <!-- container -->
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

// WebAuthn Login
const feedback = document.getElementById('feedback');
const loginButton = document.getElementById('webauthn-login');

loginButton.addEventListener('click', async () => {
	console.log('Button clicked: starting WebAuthn login...');
	feedback.innerHTML = '<div class="alert alert-info">Contacting server...</div>';
	try {
		const response = await fetch('passkey_handler.php?action=get-login-options', {
			method: 'POST',
			headers: {'Content-Type': 'application/json'}
		});
		console.log('Response received for login :-)') ;
		console.log('response:', response);
        feedback.innerHTML = "<div class=\"alert alert-info\">Server response received: " + response + "</div>";
		let options = await response.json();
		helper.bta(options);
		console.log('After decode, options: ', options);
		// Let's fetch the credential method using publicKey options
		const credential = await navigator.credentials.get(options);
		console.log('After credentials.get:', credential);
		console.log('typeof(credential):', typeof(credential));
		console.log("id:", credential.id);
		console.log("rawId:", credential.rawId);
		console.log("helper.atb(credential.response.clientDataJSON),", helper.atb(credential.response.clientDataJSON));
		feedback.innerHTML = '<div class="alert alert-info">Got navigator credentials.</div>';
        const browser = await getBrowser();
		// Send back to handler
		const verify = await fetch('passkey_handler.php?action=verify-login', {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify({
                browser: browser,
				id: credential.id,
				rawId: helper.atb(credential.rawId),
				client: helper.atb(credential.response.clientDataJSON),
				auth: helper.atb(credential.response.authenticatorData),
				sig: helper.atb(credential.response.signature),
				user: credential.response.userHandle ? helper.atb(credential.response.userHandle) : null
			})
		});
		console.log('After sending to verify-login:', verify);
		if (verify.ok) { // TODO check content?
            const status = await verify.json();
			feedback.innerHTML += '<div class="alert alert-success">Passkey Logged In! ' + status.message + '</div>';
            // if (confirm("Connexion réussie via Passkey. Appuyez sur OK pour continuer vers vos réservations (<?=$callback?>).")) {
            //    // User pressed OK 
                window.location.href = "https://www.spa-aviation.be/<?=$callback?>";
            // } else {
            //     // User pressed Cancel
            //     console.log("User stayed on the page.");
            // }       
		} else {
			feedback.innerHTML += "<div class=\"alert alert-danger\">Passkey Login Failed! </div>";
		}
	} catch (e) {
		feedback.innerHTML += `<div class="alert alert-danger">Exception: ${e.message}</div>`;
		console.error('Error during WebAuthn login:', e.message, e.stack);
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