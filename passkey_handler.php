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

// Heavily inspired by https://www.twilio.com/en-us/blog/developers/community/build-passwordless-login-system-using-webauthn-php

require_once "dbi.php" ;

if (!isset($_GET['action'])) {
    journalise($user, "E", "No action specified in WebAuthn request") ;
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No action specified']);
    exit;
}

$action = $_GET['action'];
journalise($userId, "I", "WebAuthn request received for action '$action' for user id $userId-$userName-$userFullName") ;

// Include WebAuthn library
require_once 'vendor/autoload.php';
$WebAuthn = new lbuchs\WebAuthn\WebAuthn('Spa Aviation', 'spa-aviation.be', array('android-key', 'android-safetynet', 'apple', 'fido-u2f', 'none', 'packed', 'tpm'));

function fullDebug() {
    // Clear any existing exception handlers to show the raw PHP backtrace
    restore_exception_handler();
    restore_error_handler();

    // Enable all possible errors, warnings, and notices
    error_reporting(E_ALL);

    // Force errors to be printed to the browser
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');

    // Optional: Ensure errors are also written to a log file in your directory
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/php_error.log');
}

// Initialize WebAuthn Relying Party
// $rpEntity = PublicKeyCredentialRpEntity::create(
//     'Spa Aviation', // Relying Party name
//     'spa-aviation.be', // Optional ID (defaults to the origin's host)
//     'data:image/png;base64,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'
//  // Optional icon URI as inline data image/png;base64);
// );

// Handle WebAuthn registration
if ($action == 'webauthn_register') {
    journalise($userId, "I", "Starting WebAuthn registration for user id $userId-$userName-$userFullName") ;
    // Check if user is connected
    if ($userId == 0) {
        journalise($user, "E", "Not connected for action=$action") ;
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Not connected']);
        exit;
    }
    // Force platform authenticator with user verification (e.g., fingerprint)
    $createArgs = $WebAuthn->getCreateArgs($userId, $userName, $userFullName, 30, 'preferred', 'required', false);
    $_SESSION['challenge'] = base64_encode($WebAuthn->getChallenge()->getBinaryString());
    header('Content-Type: application/json', true);
    echo json_encode($createArgs);
    journalise($userId, "I", "WebAuthn registration options generated for user id $userId-$userName-$userFullName, body=" . json_encode($createArgs)) ;
    exit;

} elseif ($action === 'verify-registration') {

    $response = json_decode(file_get_contents('php://input'), true);
    journalise($userId, "I", "Verifying WebAuthn registration for user id $userId-$userName, data=" . json_encode($response)) ;

    // Check if user is connected
    if ($userId == 0) {
        journalise($user, "E", "Not connected for action=$action") ;
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Not connected']);
        exit;
    }

    try {
        $data = $WebAuthn->processCreate(
            base64_decode($response["client"]),
            base64_decode($response["attest"]),
            base64_decode($_SESSION["challenge"]),
            'required', true, false
        );
        @journalise($userId, "D", "after processCreate, data=" . print_r($data, true)) ;
        @journalise($userId, "D", "after processCreate, credentialPublicKey=" . $data->credentialPublicKey) ;
    //   $save = [
    //     "uid" => $_SESSION["uid"],
    //     "email" => $_SESSION["email"],
    //     "name" => $_SESSION["name"],
    //     "passkey" => $data
    //   ];
    //   if (! is_dir("users")) {
    //     mkdir("users");
    //   }
    //   file_put_contents($userFile, serialize($save));
        } catch (Exception $e) {
            header('Content-Type: application/json', true);
            echo json_encode(['success' => false, 'message' => 'WebAuthn registration verification exception:' . $e->getMessage()]);
            journalise($userId, "E", 'WebAuthn registration verification exception:' . $e->getMessage()) ;
            exit ;
        } ;
    mysqli_query($mysqli_link, "INSERT INTO $table_passkey(pk_username, pk_credential_id, pk_data, pk_registration) 
        VALUES ($userId, '" . mysqli_real_escape_string($mysqli_link, $response['id']) . "', '" 
        . mysqli_real_escape_string($mysqli_link, serialize($data)) . "', NOW())") 
        or journalise($userId, "E", "Failed to store WebAuthn credential for user id $userId-$userName-$userFullName: " . mysqli_error($mysqli_link)) ;
    header('Content-Type: application/json', true);
    echo json_encode(['success' => true, 'message' => 'WebAuthn registration verification not fully implemented in this example.']);
    exit;

} elseif ($action === 'get-login-options') {

    header('Content-Type: json/application');
    echo $jsonObject;
    exit;

} else {
    journalise($userId, "E", "Unknown action '$action' in WebAuthn request for user id $userId-$userName-$userFullName") ;
    header('Content-Type: application/json', true);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
    exit;
}

function base64url_decode(string $data): string {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}
?>