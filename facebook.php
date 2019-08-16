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

if (!session_id()) session_start() ;

require_once __DIR__ . '/facebook-sdk-v5/autoload.php';

$fb_log = '' ;
$fb_debug = FALSE ;

# Connect to Facebook
$fb = new Facebook\Facebook([
	'app_id' => $fb_app_id,
	'app_secret' => $fb_app_secret,
	'default_graph_version' => 'v2.10'
]);

$fb_redirect_helper = $fb->getRedirectLoginHelper(); // Or FacebookJavaScriptHelper see https://benmarshall.me/facebook-php-sdk/2/
$fb_permissions = ['public_profile']; // optional
$fb_loginUrl = $fb_redirect_helper->getLoginUrl($fb_login_cb_url, $fb_permissions);

if (isset($_SESSION['fb_access_token']) and $_SESSION['fb_access_token'] != '') {
	$fb_accessToken = new Facebook\Authentication\AccessToken($_SESSION['fb_access_token']) ;
	if ($fb_accessToken->isExpired()) {
		$fb_accessToken = '' ;
		unset($_SESSION['fb_access_token']) ;
		if ($fb_debug) print("PHP Session token has expired<br/>\n") ;
	} else {
		try {
			$response = $fb->get('/me?fields=id,name', $fb_accessToken);
			$userNode = $response->getGraphUser();
			$fb_userName = $userNode->getName() ;
			$fb_app_userID = $userNode->getId() ;
			$result_fb = mysqli_query($mysqli_link, "SELECT * FROM $table_person WHERE fb_app_uid = '$fb_app_userID'") 
				or die("Error while retrieving joomla_id " . mysqli_error($mysqli_link)) ;
			if ($result_fb) {
					$row_fb = mysqli_fetch_array($result_fb) ;
					if ($row_fb and $row_fb['jom_id']) {
						$userID = $row_fb['jom_id'] ;
						$joomla_user = JFactory::getUser($userID) ;
						CheckJoomlaUser($joomla_user) ;
						if ($fb_debug) print("Found the joomla userID = $userID<br/>\n") ;
					}
			}
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
			// When Graph returns an error
			// Such as "This authorization code has expired"
			print('Graph returned an error: ' . $e->getMessage());
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
			// When validation fails or other local issues
			print('Facebook SDK returned an error: ' . $e->getMessage());
		} catch (Exception $e) {
			print("Cannot access /me: " . $e>getError() . "<br/>") ;
		}
	}
} else { // $_SESSION does not have a FB access token
	$fb_accessToken = '' ;
	unset($_SESSION['fb_access_token']) ;
	if ($fb_debug) print("No token in PHP session<br/>\n") ;
	if ($fb_debug) var_dump($_SESSION) ;
}
?>