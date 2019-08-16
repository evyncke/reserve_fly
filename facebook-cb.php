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

require_once "dbi.php" ; // Connection to the SQL DB and get FB parameters

// Connect to Facebook
require_once __DIR__ . '/facebook-sdk-v5/autoload.php';

$fb = new Facebook\Facebook([
	'app_id' => $fb_app_id,
	'app_secret' => $fb_app_secret,
	'default_graph_version' => 'v2.10'
]);
$fb_redirect_helper = $fb->getRedirectLoginHelper(); // Or FacebookJavaScriptHelper see https://benmarshall.me/facebook-php-sdk/2/

// Typically called as https://www.spa-aviation.be/resa/facebook-cb.php?code=AQCIOP-efT3g24QhqFmy93EEs-S9SzJnk29EBQvqiagyZVpLUwPjGlEMzAMeiZwc_lNwmdGxoYa9khGU_Hw8ZDbQIYB8CIiM8KHw-WApSVMnqcYNLLje5d5ZDf_szonPpQziicz_8F-wcmNMbdn0vQM2LOq7szT3UEc5fsWhP141C_8dtiTX0tJOTGxU8b9F8xmNH-ORm8vOB0HCiEVXr4jlZJ6xiZngeLulGXLs9_Qodehj846f04FlEk2je-1i_FWT15UZhnaJ5veVKw7u86gB9zKspHy2CWRtUomgRTOGSfdHZTm652fkxDkpf8IxGAY&state=6f607b671b18191f7378691f4095bc9b#_=_

$fb_log = '' ;

try {
	$fb_log .= 'About to getAccessToken()<br/>' ;
	$accessToken = $fb_redirect_helper->getAccessToken($fb_login_cb_url);
	if ($accessToken) $fb_log .= "Got a token via the getRedirectLoginHelper<br/>" ;
} catch(Facebook\Exceptions\FacebookResponseException $e) {
	// When Graph returns an error
	// Such as "This authorization code has expired"
	$fb_log .= 'Graph returned an error: ' . $e->getMessage();
} catch(Facebook\Exceptions\FacebookSDKException $e) {
	// When validation fails or other local issues
	$fb_log .= 'Facebook SDK returned an error: ' . $e->getMessage();
} catch (Exception $e) {
	// General catch everything else
	$fb_log .= 'Other exception: ' . $e->getMessage() ;
}

journalise($userId, "I", "Facebook call-back: $fb_log") ;

if (! isset($accessToken)) {
  if ($fb_redirect_helper->getError()) {
    header('HTTP/1.0 401 Unauthorized');
    echo "Error: " . $fb_redirect_helper->getError() . "\n";
    echo "Error Code: " . $fb_redirect_helper->getErrorCode() . "\n";
    echo "Error Reason: " . $fb_redirect_helper->getErrorReason() . "\n";
    echo "Error Description: " . $fb_redirect_helper->getErrorDescription() . "\n";
  } else {
    header('HTTP/1.0 400 Bad Request');
    print("Votre connexion a trop tard&eacute;... Veuillez retourner &agrave; la page pr&eacute;c&eacute;dente et la rafra&icirc;chir.") ;
    echo "Bad request: $fb_log<br/>(PHP_SELF=$_SERVER[PHP_SELF]<br/>(SERVER_NAME=$_SERVER[SERVER_NAME]<br/><br/>(HTTP_HOST=$_SERVER[HTTP_HOST])<br/>";
//    print("<h2>_REQUEST</h2>") ;
//    print_r($_REQUEST) ;
//    print("<h2>_SERVER</h2>") ;
//    print_r($_SERVER) ;
//    print("<h2>_SESSION</h2>") ;
//    print_r($_SESSION) ;
    echo "<hr>Error: " . $fb_redirect_helper->getError() . "<br/>";
    echo "Error Code: " . $fb_redirect_helper->getErrorCode() . "<br/>";
    echo "Error Reason: " . $fb_redirect_helper->getErrorReason() . "<br/>";
    echo "Error Description: " . $fb_redirect_helper->getErrorDescription() . "<br/>";
    print("<hr>") ;
  }
  exit;
}

// OK, we got a valid token
if (! $accessToken -> isLongLived()) {
	// Exchanges a short-lived access token for a long-lived one
	$accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
	$fb->setDefaultAccessToken($longLivedAccessToken) ;
} 
// Save the access token in the PGP session
$_SESSION['fb_access_token'] = (string) $accessToken;

// Grab public information from Facebook to feed into $table_person if the user is logged into Joomla
if (isset($userId) and $userId > 0) {
	try {
		$response = $fb->get('/me?fields=gender,id,name,link,picture', $accessToken);
		$userNode = $response->getGraphUser();
		$fb_app_userID = $userNode->getId() ;
		$fb_profile = $userNode->getLink() ;
		$sex = ($userNode->getGender() == 'female') ? 2 : 1 ;
		mysqli_query($mysqli_link, "UPDATE $table_person SET fb_app_uid=$fb_app_userID, sex=$sex WHERE jom_id = $userId")
			or die("Cannot update person with FB info: " . mysqli_error($mysqli_link)) ;
		if (mysqli_affected_rows($mysqli_link) > 0)
			journalise($userId, "I", "Profile information updated with FB information") ;
		// TODO also upload picture if not yet one
	} catch (Exception $e) {
		journalise($userId, 'E', "Cannot get Facebook information: " . $e->getMessage()) ;
	}
}

header("Location: https://www.spa-aviation.be/resa/reservation.php") ;
die() ;

// Logged in
echo "<h3>User Node</h3>" ;

$response = $fb->get('/me?fields=gender,id,name,link', $accessToken);
$userNode = $response->getGraphUser();
var_dump($userNode) ;

echo "<h3>Picture</h3>";
$response = $fb->get('/me/picture?redirect=false&width=200', $accessToken);
$pictureNode = $response->getGraphNode();
var_dump($pictureNode) ;
// object(Facebook\GraphNodes\GraphNode)#76 (1) { ["items":protected]=> array(4) { ["height"]=> int(200) ["is_silhouette"]=> bool(false) ["url"]=> string(121) "https://lookaside.facebook.com/platform/profilepic/?asid=10154716346282833&width=200&ext=1524726459&hash=AeTkgO13Fz7oFbqR" ["width"]=> int(200) } }

echo '<h3>Access Token</h3>';
var_dump($accessToken->getValue());

// The OAuth 2.0 client handler helps us manage access tokens
$oAuth2Client = $fb->getOAuth2Client();

// Get the access token metadata from /debug_token
$tokenMetadata = $oAuth2Client->debugToken($accessToken);
echo '<h3>Metadata</h3>';
var_dump($tokenMetadata);

// Validation (these will throw FacebookSDKException's when they fail)
$tokenMetadata->validateAppId($fb_app_id); // Replace {app-id} with your app id
// If you know the user ID this access token belongs to, you can validate it here
//$tokenMetadata->validateUserId('123');
$tokenMetadata->validateExpiration();

if (! $accessToken->isLongLived()) {
  // Exchanges a short-lived access token for a long-lived one
  try {
    $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
  } catch (Facebook\Exceptions\FacebookSDKException $e) {
    echo "<p>Error getting long-lived access token: " . $fb_redirect_helper->getMessage() . "</p>\n\n";
    exit;
  }

  echo '<h3>Long-lived</h3>';
  var_dump($accessToken->getValue());
}

?>