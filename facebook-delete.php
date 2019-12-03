<?php
require_once __DIR__ . '/facebook-sdk-v5/autoload.php';

require_once "dbi.php" ; // Connection to the SQL DB and get FB parameters


// Typically called as https://www.spa-aviation.be/resa/facebook-cb.php?code=AQCIOP-efT3g24QhqFmy93EEs-S9SzJnk29EBQvqiagyZVpLUwPjGlEMzAMeiZwc_lNwmdGxoYa9khGU_Hw8ZDbQIYB8CIiM8KHw-WApSVMnqcYNLLje5d5ZDf_szonPpQziicz_8F-wcmNMbdn0vQM2LOq7szT3UEc5fsWhP141C_8dtiTX0tJOTGxU8b9F8xmNH-ORm8vOB0HCiEVXr4jlZJ6xiZngeLulGXLs9_Qodehj846f04FlEk2je-1i_FWT15UZhnaJ5veVKw7u86gB9zKspHy2CWRtUomgRTOGSfdHZTm652fkxDkpf8IxGAY&state=6f607b671b18191f7378691f4095bc9b#_=_

$fb_log = '' ;

# Connect to Facebook
$fb = new Facebook\Facebook([
	'app_id' => $fb_app_id,
	'app_secret' => $fb_app_secret,
	'default_graph_version' => 'v2.10',
]);

$helper = $fb->getRedirectLoginHelper();

try {
	$accessToken = $helper->getAccessToken();
	if ($accessToken) $fb_log .= "Got a token via the JS helper<br/>" ;
} catch(Facebook\Exceptions\FacebookResponseException $e) {
	// When Graph returns an error
	// Such as "This authorization code has expired"
	$fb_log .= 'Graph returned an error: ' . $e->getMessage();
} catch(Facebook\Exceptions\FacebookSDKException $e) {
	// When validation fails or other local issues
	$fb_log .= 'Facebook SDK returned an error: ' . $e->getMessage();
}

if (! isset($accessToken)) {
  if ($helper->getError()) {
    header('HTTP/1.0 401 Unauthorized');
    echo "Error: " . $helper->getError() . "\n";
    echo "Error Code: " . $helper->getErrorCode() . "\n";
    echo "Error Reason: " . $helper->getErrorReason() . "\n";
    echo "Error Description: " . $helper->getErrorDescription() . "\n";
  } else {
    header('HTTP/1.0 400 Bad Request');
    echo "Bad request: $fb_log";
  }
  exit;
}

?>
Not implemented yet
