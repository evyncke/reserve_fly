<?php
// Test with https://www.spa-aviation.be/resa/mobile_login_oauth.php
/*
   Copyright 2013-2025 Eric Vyncke

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

# use Google\Client as GoogleClient;
use Facebook\Facebook;

// Don't use Google Client for now as it requires too many dependencies
// Initialize Google Client
#$googleClient = new GoogleClient();
#$googleClient->setClientId($google_client_id);
#$googleClient->setClientSecret($google_client_secret);
#$googleClient->setRedirectUri('https://www.spa-aviation.be/resa/mobile_login_oauth.php');
#$googleClient->addScope('email');
#$googleClient->addScope('profile');

// Initialize Facebook Client
$facebook = new Facebook([
    'app_id' => $fb_app_id,
    'app_secret' => $fb_app_secret,
    'default_graph_version' => 'v12.0',
]);

// Handle OAuth Callbacks
/* if (isset($_GET['code']) && isset($_GET['state']) && $_GET['state'] === 'google') {
    $token = $googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
    if (!isset($token['error'])) {
        $googleClient->setAccessToken($token['access_token']);
        $googleOAuth = new Google_Service_Oauth2($googleClient);
        $googleUser = $googleOAuth->userinfo->get();

        // Store token in MariaDB
        $stmt = $mysqli_link->prepare("INSERT INTO oauth_tokens (provider, user_id, token) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?");
        $stmt->bind_param('ssss', $provider, $userId, $token['access_token'], $token['access_token']);
        $provider = 'google';
        $userId = $googleUser->getId();
        $stmt->execute();

        header("Location: https://www.spa-aviation.be/$callback", TRUE, 307);
        exit;
    }
} else
*/
// Check whether Facebook OAuth callback
if (isset($_GET['state']) and isset($_GET['code'])) {
    $helper = $facebook->getRedirectLoginHelper();
    try {
        $accessToken = $helper->getAccessToken();
        if (isset($accessToken)) {
            $response = $facebook->get('/me?fields=id,name,email', $accessToken);
            $facebookUser = $response->getGraphUser();
            journalise($userId, "I", "Facebook OAuth: user info obtained: facebook id = $facebookUser[id], name = $facebookUser[name], email = $facebookUser[email]") ;
            // Check if user exists with this facebook id or email
            $query = "SELECT jom_id 
                FROM $table_person 
                WHERE facebook_id='" . mysqli_real_escape_string($mysqli_link, $facebookUser['id']) . "' 
                    OR email='" . mysqli_real_escape_string($mysqli_link, $facebookUser['email']) . "'";
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
                journalise($userId, "I", "Facebook login for user id $userId ($facebookUser[email]) from $callback") ;
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

// Generate OAuth URLs
//$googleAuthUrl = $googleClient->createAuthUrl();
$facebookHelper = $facebook->getRedirectLoginHelper();
$facebookAuthUrl = $facebookHelper->getLoginUrl('https://www.spa-aviation.be/resa/mobile_login_oauth.php', ['email']);

?>

<div class="container">
    <h2>Connexion</h2>
    <p class="bg-danger"><?=$connect_msg?></p>
    <p class="bg-info">Pour accéder au site vous devez vous connecter.</p>

    <form method="post" action="<?=$_SERVER['PHP_SELF']?>">
        <input type="hidden" name="cb" value="<?=$callback?>">

        <label for="username" class="form-label">
            Identifiant:
        </label>
        <input type="text" class="form-control" id="username" name="username" placeholder="Votre nom d'utilisateur" autocomplete="username" value="<?=$_REQUEST['username']?>"><br/>

        <label for="password" class="form-label">
            Mot de passe:
        </label>
        <input class="form-control" type="password" id="password" placeholder="Votre mot de passe" name="password" autocomplete="current-password"><br/>

        <input type="submit" class="btn btn-primary" value="Connexion">
    </form>

    <hr>

    <!--a href="<?=$googleAuthUrl?>" class="btn btn-danger">Se connecter avec Google</a-->
    <a href="<?=$facebookAuthUrl?>" class="btn btn-primary text-center"><i class="bi bi-facebook"></i> Se connecter avec Facebook</a>
</div> <!-- container -->
</body>
</html>