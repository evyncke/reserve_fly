<?php
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

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "dbi.php" ;
$need_swiped_events = true ;
require_once 'mobile_header5.php' ;

$id = (isset($_REQUEST['id'])) ? $_REQUEST['id'] : '' ; // Direct access to a booking by id
$me = (isset($_REQUEST['me'])) ? $_REQUEST['me'] : '' ; // Access to the closest booking for pilot/instructor 'me'
$auth = (isset($_REQUEST['auth'])) ? $_REQUEST['auth'] : '';

if (isset($userId) and $userId > 0)
	$me = $userId ;
	
if ($id) {
	if ($auth != md5($id . $shared_secret)) die("Wrong key for booking#$id: $auth ") ;
	if (! is_numeric($id)) die("Wrong booking id: $id") ;
	$result = mysqli_query($mysqli_link, "select r_id, r_plane, r_start, r_stop, r_type, r_pilot, r_who, r_date, 
		convert(r_comment using utf8) as r_comment, r_from, r_to,
		p.username as username, convert(p.name using utf8) as pilot_name,
		convert(i.name using utf8) as instructor_name, w.username as username2, convert(w.name using utf8) as booker_name,
		p.email as email, home_phone, work_phone, cell_phone,
		if (date(r_start) = current_date(), 1, 0) as today,
		if(r_stop >= sysdate(), 1, 0) as can_cancel, if (r_start < sysdate(), 1, 0) as can_log
		from $table_bookings join $table_users p on r_pilot = p.id left join $table_users i on r_instructor = i.id,
		$table_users as w, $table_person
		where r_id = $id and r_who = w.id and r_cancel_date is null") or die("Cannot access the booking #$id: " . mysqli_error($mysqli_link)) ;
} elseif ($me) {
	if ($userId <= 0) {
		if ($auth != md5($me . $shared_secret)) die("Wrong key for booking#$me: $auth ") ;
		if (! is_numeric($me)) die("Wrong booking me: $me") ;
	}
	$result = mysqli_query($mysqli_link, "select r_id, r_plane, r_start, r_stop, r_type, r_pilot, r_who, r_date, 
		convert(r_comment using utf8) as r_comment, r_from, r_to,
		p.username as username, convert(p.name using utf8) as pilot_name, convert(i.name using utf8) as instructor_name,
		w.username as username2, convert(w.name using utf8) as booker_name,
		p.email as email, home_phone, work_phone, cell_phone,
		if (date(r_start) = current_date(), 1, 0) as today,
		abs(date(r_start) - current_date()) as today_distance,
		if(r_stop >= sysdate(), 1, 0) as can_cancel, if (r_start < sysdate(), 1, 0) as can_log
		from $table_bookings join $table_users p on r_pilot = p.id left join $table_users i on r_instructor = i.id,
		$table_users as w, $table_person
		where (r_pilot = $me or r_instructor = $me) and r_who = w.id and r_cancel_date is null and r_type <> " . BOOKING_MAINTENANCE . "
		order by today_distance asc limit 0,1") or die("Cannot access the booking #$me 2: " . mysqli_error($mysqli_link)) ;
		$auth != md5($id . $shared_secret) ;
} 

if (isset($result) and $result) {
	$booking = mysqli_fetch_array($result) ;

	if (! $booking) {
		print('<br/><br/><br/><br/><br/>
<div class="row text-center">
	<div class="col-xs-12 col-md-6 mt-4 p-5 text-bg-warning rounded">
		Vous n\'avez aucune réservation.
	</div>
</div>') ;
	} else {
		if ($id)
			$condition = "(r_pilot = $booking[r_pilot])" ;
		else {
			$condition = "(r_pilot = $me or r_instructor = $me)" ;
			$id = $booking['r_id'] ;
			$auth = md5($id . $shared_secret) ;
		}
		
		// Find the previous/next booking
		$result = mysqli_query($mysqli_link, "select * from $table_bookings JOIN $table_planes ON r_plane = $table_planes.id
			where r_cancel_date is null and r_stop <= '$booking[r_start]' and r_type <> " . BOOKING_MAINTENANCE . " and ressource = 0
			and $condition 
			order by r_start desc limit 0,1")
			or die("Cannot access previous booking: ".mysqli_error($mysqli_link)) ;
		$row = mysqli_fetch_array($result) ;
		$previous_id = $row['r_id'] ;
		$previous_auth = md5($previous_id . $shared_secret) ;
		$previous_date = $row['r_start'] ;
		$result = mysqli_query($mysqli_link, "select * from $table_bookings JOIN $table_planes ON r_plane = $table_planes.id
			where r_cancel_date is null and r_start >= '$booking[r_stop]' and r_type <> " . BOOKING_MAINTENANCE . " and ressource = 0
			and $condition 
			order by r_start asc limit 0,1")
			or die("Cannot access previous booking: ".mysqli_error($mysqli_link)) ;
		$row = mysqli_fetch_array($result) ;
		if ($row) {
			$next_id = $row['r_id'] ;
			$next_auth = md5($next_id . $shared_secret) ;
			$next_date = $row['r_start'] ;
		} else
			$next_id = false ;
			
		# fix the character set issue...
		$booking['pilot_name'] = db2web($booking['pilot_name']) ;
		$booking['booker_name'] = db2web($booking['booker_name']) ;
		$booking['instructor_name'] = db2web($booking['instructor_name']) ;
		$booking['r_comment'] = nl2br(db2web($booking['r_comment'])) ;
	} // if ! booking
} // $result was set (== user is logged in)


?> 
<div class="container">

<?php
if ($userId <= 0 and isset($_REQUEST['logout'])) {
?>
<br/><br/><br/><br/><br/>
<div class="row text-center">
	<div class="col-xs-12 col-md-6 mt-4 p-5 text-bg-primary rounded">
		Vous êtes maintenant déconnecté(e).<br/>Utilisez le bouton "Se connecter" en haut à droite.
	</div>
</div>
<?php
exit ;
} # if ($userId <= 0 and logout)

if ($userId <= 0) {
?>
<br/><br/><br/><br/><br/>
<div class="row text-center">
	<div class="col-xs-12 col-md-6 mt-4 p-5 text-bg-primary rounded">
		Vous devez être connecté(e) pour voir vos réservations.<br/>Utilisez le bouton "Se connecter" en haut à droite.
	</div>
</div>
<?php
exit ;
} # if ($userId <= 0)

// Do we need to display the news ?

if (isset($_REQUEST['news'])) {
	$result_news = mysqli_query($mysqli_link, "SELECT * FROM $table_news
		WHERE n_stop >= CURRENT_DATE() and n_start <= CURRENT_DATE()
		ORDER BY n_id DESC
		LIMIT 0,3") or die("Cannot fetch news: " . mysqli_error($mysqli_link)) ;
	
	if (mysqli_num_rows($result_news)) {
		print('<div class="row"><div class="col-xs-12 col-md-6 mt-1 p-2 text-bg-primary rounded"><ul>') ;
		while ($row_news = mysqli_fetch_array($result_news)) {
			$subject = db2web($row_news['n_subject']) ;
			$text = db2web(nl2br($row_news['n_text'])) ;
			print("<li><b>$subject</b>: $text</li>\n") ;
		}
		print('</ul></div></div>') ;
	}
	mysqli_free_result($result_news) ;
	if ($userIsAdmin or $userIsInstructor) {
		print('<div class="row"><div class="col-xs-12 col-md-6 mt-1 p-2 text-bg-info rounded">') ;
		print('Les nouvelles du club sont visibles comme sur la page réservation:') ;
		print('<ul>
			<li>Nouveau site in Progress: <a href="https://new.spa-aviation.be">https://new.spa-aviation.be</a></li>
			<li>Safety Day 2026 – Mars 2026</li>
			</ul>') ;
		print('</div></div>') ;
		print('<div class="row"><div class="col-xs-12 col-md-6 mt-1 p-2 text-bg-secondary rounded">') ;
		print('Nouvelles de la flotte:
			<ul>
				<li>OO-ALD: Saumon réparé à partir du jeudi 19/11 (10 jours US)</li>
				<li>OO-JRB: Gauge essence droite réparée lors de sa prochaine 200h</li>
			</ul>') ;
		print('</div></div>') ;
	}
}
?>
<div class="page-header text-center">
	<h2><?=(isset($_REQUEST['id'])) ? "Mes réservations" : "Ma réservation la plus proche"?></h2>
</div> <!-- page header -->

<div class="row">

<!-- This div is for cancellation confirmation, not displayed by default -->
<div class="col-sm-12">
	<div id="confirmCancellation" style="visibility: hidden; display: none;" class="text-center">
		<h3>Annulation d'une réservation: <?=$booking['r_plane']?></h3>
		<br/>
		<button class="btn btn-danger" onclick="cancelConfirm(<?=$id?>, '<?=$auth?>');">Je confirme l'annulation</button>
		<br/>
		<br/>
		<button class="btn btn-primary btn-default" onclick="abandonCancel();">Ne pas annuler la réservation</button>
	</div> <!-- confirmCancellation -->
	
<table class="table table-sm table-striped rounded shadow col-sm-12 col-md-4 col-lg-3">
	<tr><td>Avion:</td><td><?=$booking['r_plane']?></td><tr>
	<tr><td>Début:</td><td><?=$booking['r_start']?></td><tr>
	<tr><td>Fin:</td><td><?=$booking['r_stop']?></td><tr>
	<tr><td>Pilote:</td><td><?=$booking['pilot_name']?></td><tr>
<?php
if ($booking['instructor_name'] != '') {
?>
	<tr><td>Instructeur:</td><td><?=$booking['instructor_name']?></td><tr>
<?php
} // end instructor present
?>
	<tr><td>Commentaire:</td><td><?=$booking['r_comment']?></td><tr>
	<tr><td>Effectu&eacute;e par:</td><td><?=$booking['booker_name']?></td><tr>
</table>
</div> <!-- col-->

</div> <!-- row -->

<!-- Display previous / next -->
<div class="row">
<ul class="pagination justify-content-center">
<?php
if ($previous_id != '') {
	print("<li  class=\"page-item\"><a class=\"page-link\" href=\"$_SERVER[PHP_SELF]?id=$previous_id&auth=$previous_auth\"><i class=\"bi bi-caret-left-fill\"></i> Ma réservation précédente<br>$previous_date</a></li>\n") ;
	print("<script>
		// Swipe to change to previous booking
		document.addEventListener('swiped-right', function(e) {location.href='$_SERVER[PHP_SELF]?id=$previous_id&auth=$previous_auth' ; }) ;
		</script>\n") ;

}
if ($next_id) {
	print("<li class=\"page-item\"><a class=\"page-link\" href=\"$_SERVER[PHP_SELF]?id=$next_id&auth=$next_auth\">Ma réservation suivante<i class=\"bi bi-caret-right-fill\"></i><br/>$next_date</a></li>\n") ;
	print("<script>
		// Swipe to change to next booking
		document.addEventListener('swiped-left', function(e) {location.href='$_SERVER[PHP_SELF]?id=$next_id&auth=$next_auth' ; }) ;
		</script>\n") ;
} 
?>
</ul>
</div> <!-- row -->

<?php
// Need to display the cancel button only for future (including today) reservations
//
if ($booking['can_cancel']) {
?>
<div class="row">
	<br/>
	<div class="col-xs-6 col-md-6 text-center ">
		<button id="cancelButton" class="btn btn-danger" onclick="cancelFirstClick();">Annuler la réservation</button>
	</div><!-- col-->
	<div class="col-xs-6 col-md-6 text-center ">
		<button id="modifyButton" class="btn btn-primary" onclick="modifyClick(<?=$id?>, '<?=$auth?>');">Modifier la réservation</button>
	</div><!-- col-->
</div> <!-- row -->
<?php
} 
if ($booking['can_log']) { // Check whether something has been logged
	$result2 = mysqli_query($mysqli_link, "SELECT COUNT(*) AS log_count
		FROM $table_logbook
		WHERE l_booking = $id") or die("Cannot retrieve booking entries... " . mysqli_error($mysqli_link)) ;
	$row2 = mysqli_fetch_array($result2) ;
	if ($row2['log_count'] == 0) {
?>
<div class="row">
	<div class="col-xs-12 text-center jumbotron">
Vous n'avez pas encore encodé les index moteurs.
	</div><!-- col-->
</div> <!-- row -->
<?
	}
?>
<div class="row">
	<br/>
	<div class="col-xs-12 text-center ">
		<button id="newLogbookButton" class="btn btn-success" onclick="newLogbookClick(<?=$id?>, '<?=$auth?>');">Introduction du vol dans carnet de routes</button>
	</div><!-- col-->
</div> <!-- row -->
<?php
}

// If logged in, display the passkey prompt
// Include WebAuthn library
require_once 'vendor/autoload.php';
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialSource;


if ($userId == 62 and $userId > 0) { // Eric only
// Initialize WebAuthn Relying Party
	$rp = PublicKeyCredentialRpEntity::create(
		'SPA Aviation', // Relying Party name
		'spa-aviation.be', // Optional ID (defaults to the origin's host)
		// TODO 'data:image/png;base64,iVBORw0KGgoAA...' // Optional icon URI as inline data image/png;base64
);
?>
<!-- Add WebAuthn buttons to the login form -->
<div class="text-center">
	<button id="webauthn-register" class="btn btn-outline-secondary"><i class="bi bi-fingerprint"></i> Register Passkey</button><br/>
	<div id="feedback" class="mt-2"></div>
</div>

<script>
function base64urlToBuffer(s) {
  console.log('Decoding base64urlToBuffer:', s);	
  s = s.replace(/-/g, '+').replace(/_/g, '/');
  // Pad with '='
  while (s.length % 4) {
	s += '=';
  }
  return Uint8Array.from(atob(s), c => c.charCodeAt(0));
}

// WebAuthn Registration
const feedback = document.getElementById('feedback');
const registerButton = document.getElementById('webauthn-register');
registerButton.addEventListener('click', async () => {
	console.log('Button clicked: starting WebAuthn registration...');
	feedback.innerHTML = '<div class="alert alert-info">Contacting server...</div>';
	try {
		const response = await fetch('passkey_handler.php?action=webauthn_register');
		const options = await response.json();
		feedback.innerHTML = '<div class="alert alert-info">Server response received.</div>';
//		console.log('Response received for registration :-)') ;
		// console.log('response:', response);
		// console.log('options:', options);
		// console.log('typeof(options):', typeof(options));
		// console.log('Before decode, options.challenge: ', options.challenge);
		// Decode Base64 from PHP to Buffer
		options.challenge = base64urlToBuffer(options.challenge);
		options.user.id = base64urlToBuffer(options.user.id);
		console.log('After decode, options: ',options);
		// Let's fetch the credential method using publicKey options
		const credential = await navigator.credentials.create({ publicKey: options });
		console.log('After credentials.create:', credential);
		console.log('typeof(credential):', typeof(credential));
// 		After credentials.create:
// PublicKeyCredential
// authenticatorAttachment: "platform"
// id: "g9IVsShjL5mDTUnDkgh297pMWrs"
// rawId: ArrayBuffer {byteLength: 20, resizable: false, maxByteLength: 20, detached: false}
// response: AuthenticatorAttestationResponse {attestationObject: ArrayBuffer, getTransports: function, getAuthenticatorData: function, getPublicKey: function, getPublicKeyAlgorithm: function, …}
// type: "public-key"
// PublicKeyCredential Prototype

		feedback.innerHTML = '<div class="alert alert-info">Got navigator credentials.</div>';
		// Send back to handler
		const verify = await fetch('passkey_handler.php?action=verify-registration', {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify({
				id: credential.id,
				rawId: btoa(String.fromCharCode(...new Uint8Array((credential.rawId)))),
				// rawId: base64urlToBuffer(credential.rawId),
				type: credential.type,
				response: {
					attestationObject: btoa(String.fromCharCode(...new Uint8Array(credential.response.attestationObject))),
					// attestationObject: base64urlToBuffer(credential.response.attestationObject),
					// clientDataJSON: base64urlToBuffer(credential.response.clientDataJSON),
					clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(credential.response.clientDataJSON)))
				}
			})
		});
		console.log('After sending to verify-registration:', verify);
		if (verify.ok) 
			feedback.innerHTML = '<div class="alert alert-success">Passkey Saved!</div>';
		else 
			feedback.innerHTML = '<div class="alert alert-danger">Passkey Registration Failed!</div>';
	} catch (e) {
		feedback.innerHTML = `<div class="alert alert-danger">Error: ${e.message}</div>`;
		console.error('Error during WebAuthn registration:', e);
	}
});
</script>
<?php
}
?>

</div> <!-- container-->
</body>
</html>