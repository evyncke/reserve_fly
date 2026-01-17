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
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

// Initialize WebAuthn Relying Party
$rpEntity = PublicKeyCredentialRpEntity::create(
    'Spa Aviation', // Relying Party name
    'spa-aviation.be', // Optional ID (defaults to the origin's host)
    'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAAXNSR0IArs4c6QAAAFBlWElmTU0AKgAAAAgAAgESAAMAAAABAAEAAIdpAAQAAAABAAAAJgAAAAAAA6ABAAMAAAABAAEAAKACAAQAAAABAAAAIKADAAQAAAABAAAAIAAAAAC+W0ztAAABWWlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNi4wLjAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyI+CiAgICAgICAgIDx0aWZmOk9yaWVudGF0aW9uPjE8L3RpZmY6T3JpZW50YXRpb24+CiAgICAgIDwvcmRmOkRlc2NyaXB0aW9uPgogICA8L3JkZjpSREY+CjwveDp4bXBtZXRhPgoZXuEHAAAH1klEQVRYCZVXS2ydRxWeMzP/475s5zpOncRp7aY0pZJJoW5LSkpRpFK1jQpsskGquoQNKzagbmCFhNQNCIkKEKIrzAJRhMIiRCK0CiqqUJoWtUoax3WCc+04cfy693/MHL7zX//GuTbEPvLcmf/Mec85Z8ZG7R40WFjYrLUnvPc5lksYgt812F1zKOXXeWrW+hNam/40dTPAlfhdiaRdUAstw+vjZPwEOzUcxOrfyikEQTnvaTUz7m3VUVdAV9DuRPZOw1aG/VF4/RyRGmg06G2XqgNQEkURLWvNR2NlTuDbYBRHtBMDNh+BKLlXGL1j1WdIJVmm6jj1O7lXByjnttZq3jm1BhnuHorLqBdGirUllFaX3pZ4mWVPGG/CgmlF9EUmfZCYQ00qZ1L1TqLY5f5nQgwolXS/ur+C2yy7oCkNCLB5H8YKRqlsMzHQBQhuHonXUZoT9jTPTHPeKetJv4NsmMZ+L58oEj0SXZHdxKhjrGJsWNoMY/2dwOh3Vlfzy8BPySZAhImAkllwIux/hVloRYmA8MqQMhVoBNXgEcvu2TSllnPuTeC0CBOiNYJcG/mj1VjXldEPuYzbwC9ibBaIz8IYyR0YZb6G7TtYS+REjtCWs6zF8BCV81SlQsdDy03kyZ4s878GPsMovJFZamlBa5oILM1BwrAx9CScf9p7AyE+AUkRMsxlBOo21D8grTP2fAF4UVxGSww8bIx5KQjoZBjysIVc73nvWkKXFfMH6/QswgSEOXE5f+AVvQwjWnGsLyWperVS406tavIsoyeZ+QroCsvjuv1SaHjNWGWylGeAX8aA18EXKjV6dt+QXWq3+XthqD6MIv1+mvJjSaI/9M79CXQbR1UaIOGSdQdG3EAkjqGslpBoM3lOx8dG3ZvNJs+srJhTec7vgW40jGgiS+kJVIG3VvcBD6/sMwMDfHBl2U2iMk55R2FYob/CwM/CgYU8d78Dr4AYUEBpgHyIEVIN83muh6B8IrQ0zTjj2Vl6/aGv8E/NKi/cXraPgv9bkPB4/173Y851uNrm1xTrTxsNVVtacr+vVvUPOx16ulJTv+Wcx9AzhnHuP4fsFKPMESw35UDx1U0aLPkjReZ5o7kTBjSDcqu3Lpmn5ub8ZBTQ51xOF5tNOre6rE7ijD8mRecRqRsDA7QyOurz1pz+fq2qfglNBkn3YKejz0H0vyBYcuOuChJreqHojrnji+hyI+jzNW35smdujo+rPc7RWhwjETLeB+FVzGMm7JZpGOY3Z+ftUdLqGoQSePsRevSX/B/rSu5SLrjtDJCjgJ10HZ4PenQ6XLhNZtU8dkytMPGpTsavQXg4uN//olbz55K2OomovY5Qg441e/VAzqqCrN/DiqR6bhUyu8e8vuxOhbd3YdY/dOCPGK1vutQ/kHsaMVpFshWH/BFK6iyq5NOFFn27GvOZakW95SM1iBQi7TnF1Yiy0YcDw/M4xhCd6BBYpVI2sl9kCWwXgWID1+sNeG1xxqgKtQyP4zfeUFm9bs5nRWi5j8gv5rnaj9sR1wBXWq3s7/B6iDQ5Q34Oggz4JOzS0LaF7Qwoj6CFxJF9MsSzyImDk5PKIMSoezpSreZTjZo+MzTk/oxcaKJyxkRDkvERMN2BUR2MhMALdNnEurKFcB026rFEYBal0kIPI8yvaotHh1ce/fvr1qpriIpDUn4iOCTkOBHd0tZP+5w+ozStoo982Ro+jagtQEYTebGIEvwJ1lvCD9y2RyDKxYhP0lzdJiSTCakVBHw6y9UoDNiPTQmrr9XUZKXC56S/W8vX08y/iDN/Vwd0PQzpGgw8BOVnQSuwnbP/vQu6NBu/YgCjx0+jK74A1sQEYoS6gKt3b5rR5xsN/YfVtnoZnXAR+3atQ9+oxOo36BuXhD5J+Xjm1EXIOA1Z0vDEsS2wuROWm2JpSbzsnF3AOT5jNbWhjG1M7+FREqO9okrUHF5I6Ad0APfCu3FEl6T2sf9wmii8E4ruJ9HacvablZVrmctzkkeDtGUkYgF7gki/Ehh1HxLrFqwTOl2v6vOdDo+hAoalYiRs3YSks7jv5dIRqGAMY0zJRy/0RkC+5eX7eBSpk8bofmRcDXf1PLz6G7qevIAOo/WOoc6rYVVdTTs8giN5EAUTJLm64DL/K1TKRcgZg5yj1SpN4Gp/DJfV+8CVj5MNO3oTQ5zwQVVNGKVfQAfMjFEpMn0JYwq9YaXdzq6Cpg9jJYrME8zuapoW/5is4erdG0V2AAaghfMgotJHlubh1a3lZXdGeDDugm07YbYWGBO7CMoznPEQBE9z7sfFGCj9ZpK4H0HKLOa3Smna6u8iSa1zPkQFXEBezODwh60kZMKHQCcv5i3QewTryeKv453f0nh9IvHw3FWDnYTGwb0IJS20+/uNsbDSjBjjR7S2jwSW70d+3EYSVpEH/cpQBuVzMARRc38Br3jfG/GtiB4ThWEE/wSOhNp/FRHosDQemI0/udtRq1xDu55GyTXwjtwvNKmjO2nb/RPb8xg3hQ4gsrZUQ28ECsr1n6IXYL2EJMT1qvtxCVnU9RBuuz60qhRzjHBP4T4AjofhfYR1kGr/R6TbFfBK2EXxtsqBv2cEhEYMEZDecCiO7Ri8HkAI5G1okzW1D++BFYQbF5ZuZVkmD055xEp+/d8egP1dgXixU9gx7X8Aju+ZPP70/eIAAAAASUVORK5CYII='
 // Optional icon URI as inline data image/png;base64);
);

$attestationStatementSupportManager = AttestationStatementSupportManager::create();
$attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());
$factory = new WebauthnSerializerFactory($attestationStatementSupportManager);
$serializer = $factory->create();


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
    $userEntity = PublicKeyCredentialUserEntity::create(
        $userName, // Unique user ID (OR base64encoded of $userId ?)
        $userName, // Username
        $userFullName // Display name
    );
    $challenge = random_bytes(16);
    $_SESSION['challenge'] = base64_encode($challenge);
    // Force platform authenticator with user verification (e.g., fingerprint)
    $authenticatorSelectionCriteria = AuthenticatorSelectionCriteria::create(
        authenticatorAttachment: AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM,
        userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
        residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED
    );
    $publicKeyCredentialCreationOptions = PublicKeyCredentialCreationOptions::create(
        $rpEntity,
        $userEntity,
        $challenge,
        [], // No specific pubKeyCredParams, use defaults
        authenticatorSelection: $authenticatorSelectionCriteria
    );
    $jsonObject = $serializer->serialize(
        $publicKeyCredentialCreationOptions,
            'json',
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true, // Highly recommended!
                JsonEncode::OPTIONS => JSON_THROW_ON_ERROR, // Optional
            ]
        );

    header('Content-Type: application/json', true);
    echo $jsonObject;
    journalise($userId, "I", "WebAuthn registration options generated for user id $userId-$userName-$userFullName, jsonObject=$jsonObject") ;
    exit;

} elseif ($action === 'verify-registration') {

    $data = json_decode(file_get_contents('php://input'), true);
    journalise($userId, "I", "Verifying WebAuthn registration for user id $userId-$userName, type=$data[type], data=" . json_encode($data)) ;

    // Check if user is connected
    if ($userId == 0) {
        journalise($user, "E", "Not connected for action=$action") ;
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Not connected']);
        exit;
    }

    $clientDataJSON = base64url_decode($data['response']['clientDataJSON']);
    $clientData = json_decode($clientDataJSON, true);
    // For initial registration: clientDataJSON={"type":"webauthn.create","challenge":"3GIMvSUzzEAgJ5gNW2_vHw","origin":"https://www.spa-aviation.be","crossOrigin":false}

    $attestationObject = base64url_decode($data['response']['attestationObject']);
    // journalise($userId, "D", "attestationObject=$attestationObject") ;
    // attestationObject=?cfmtdnonegattStmt?hauthDataX?shM? a lot of binary things (CBOR encoded probably)

    // Check the challenge/origin/type    
    $expected_challenge = base64_decode($_SESSION['challenge']) ;
    $received_challenge = base64url_decode($clientData['challenge']) ;
    if ($expected_challenge != $received_challenge) {
        http_response_code(400);
        journalise($userId, "E", "webauthn-verify-registration, challenges do not match") ;
        exit('Challenge mismatch');
    }
    if ($clientData['origin'] != "https://www.spa-aviation.be") {
        http_response_code(400);
        journalise($userId, "E", "webauthn-verify-registration, invalid origin: $clientData[origin]") ;
        exit('Invalid origin');
    }
    if ($clientData['type'] == 'webauthn.create') {
        // In a real scenario with web-auth/webauthn-lib, you would use 
        // AuthenticatorAttestationResponseValidator here.
        // For "No PSR-7", we simulate the storage of the credential data.
        mysqli_query($mysqli_link, "INSERT INTO $table_passkey(pk_username, pk_credential_id, pk_data, pk_registration) 
            VALUES ($userId, '" . mysqli_real_escape_string($mysqli_link, $data['id']) . "', '" 
            . mysqli_real_escape_string($mysqli_link, json_encode($data)) . "', NOW())") 
            or journalise($userId, "E", "Failed to store WebAuthn credential for user id $userId-$userName-$userFullName: " . mysqli_error($mysqli_link)) ;
        header('Content-Type: application/json', true);
        echo json_encode(['success' => true, 'message' => 'WebAuthn registration verification not fully implemented in this example.']);
        exit;
    } else {
        journalise($userId, "F", "Unexpected type=$clientData[type]") ;
    }

} elseif ($action === 'get-login-options') {

    $challenge = random_bytes(16);
    $_SESSION['challenge'] = base64_encode($challenge);

    $options = PublicKeyCredentialRequestOptions::create($challenge)
        ->setRpId("spa-aviation.be")
        ->setUserVerification(PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED);

    header('Content-Type: json/application');
    echo json_encode($options);
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