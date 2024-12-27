<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
/*
   Copyright 2014-2021 Eric Vyncke

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
require_once('auth.php') ;
$db_name = 'spaaviation' ;
$db_user = 'spaaviation' ;
// MySQL Credentials are in auth.php

$convertToUtf8 = false ;
//	$db_host = 'spaaviation.mysql.db' ;
$convertToUtf8 = true ;
$joomla_session = true ;
$joomla_connection_page = "https://www.spa-aviation.be/index.php/fr/" ;

$test_mode = false ;
$managerEmail = "info@spa-aviation.be" ;
$managerName = "Réservation RAPCS" ;
$fleetEmail = "fleet@spa-aviation.be" ;
$fleetName = "Fleet Management" ;

// CBC
$iban = "BE64732038421852" ;
$bic = "CREGBEBB" ;

$bank_account_name = "Royal Aero Para Club Spa" ;

// Payconiq information
$pcnq_api_key = '37a89b34-3931-400a-8858-c8c8e2da4e21' ;
$pcnq_merchant_id = '64e3577b9ba65103267cdee6' ;
// $pcnq_endpoint = see auth.php ;

// Some costs
$cost_fi_minute = 0.83 ;
$revenue_fi_minute = 0.75 ;
$revenue_fi_initiation = 50.0 ; // Initiation flight when shareCode = -3
$code_initiation = -3 ; // Hard coded :-(
$tax_per_pax = 10.0 ;

// Odoo integration
// Analytic accounts and products are harcoded
$non_nav_membership_product = 7 ;
$nav_membership_product = 8 ;
$non_nav_membership_price = 70.0 ;
$nav_membership_price = 200.0 ;
$membership_analytic_account = 25  ;

$tracked_planes = array('OOALD', 'OOALE', 'OOAPV', 'OOJRB', 'OOFMX', 'OOSPQ', 'PHAML') ;
// array_push($tracked_planes, 'OOFUN', 'OOMAT', 'FHABQ', 'OOD35', 'OOG85', 'DELZA', 'DELZB', 'FJXRL') ; // For Air Spa Rallye
// array_push($tracked_planes, 'OOCEK', 'FAZMX', 'FAYAC', 'GIIIG') ; // For 75 ans avions externes

$bccTo = "eric.vyncke@edpnet.be" ;
//	$bccTo = "evyncke@gmail.com" ;
$bccTo = "" ;
$cache_directory = getcwd() ;
$rapcs_metar = 'rapcs_metar' ;
// SMTP local parameters
$smtp_host = 'vyncke.org' ;
$smtp_port = 785 ; // Unusual TCP port as OVH blocks the usual 587 :(
// SMTP credentials are in auth.php
//$smtp_user = 'xxx' ; 
//$smtp_password = 'xxx' ;

// The shared secret for direct access to booking is in auth.php
//$shared_secret = "XXX" ;

// Credential for email account sending invoices is in auth.php
//$invoice_imap = "xxx" ;
//$invoice_folder = "xxx" ;
//$invoice_user = "xxxx" ;
//$invoice_psw = "xxx" ;

// OVH credentials for (in auth.php)
//$finances_smtp_user = 'xxx@spa-aviation.be' ;
//$finances_smtp_password = 'xxx' ;

// End of Vyncke.org server configuration
$smtp_from = 'no-reply@spa-aviation.be' ;
$smtp_localhost = 'spa-aviation.be' ;


// Need to change when jom_usergroups content changes!!!!!
//$joomla_member_group = 2 ; // "enregistrés"
$joomla_member_group = 18 ; // "membres"
$joomla_admin_group = 7 ; // Web admin
$joomla_board_group = 22 ; // Board member
$joomla_sysadmin_group = 6 ;
$joomla_superuser_group = 8 ;
$joomla_pilot_group = 13 ;
$joomla_student_group = 16 ;
$joomla_instructor_group = 14 ;
$joomla_instructor_group2 = 15 ;
$joomla_mechanic_group = 17 ;
$joomla_flight_group = 21 ;
$joomla_flight_pilot_group = 19 ;
$joomla_flight_manager_group = 20 ;
$joomla_no_flight = 23 ;
$joomla_effectif_group = 25 ;

// Get information from Joomla
define( '_JEXEC', 1 );
define( 'JPATH_BASE', realpath(dirname(__FILE__) . '/..' ));
require_once ( JPATH_BASE . '/includes/defines.php' );
require_once ( JPATH_BASE . '/includes/framework.php' );
$mainframe = JFactory::getApplication('site');
$mainframe->initialise();
$joomla_user = JFactory::getUser() ;
if ($joomla_user->guest and isset($_SESSION['jom_id'])) { // User is not logged in via Joomla but via the mobile app TODO ensure that session is started !
	$userId = intval($_SESSION['jom_id']) ;
	$originUserId = $userId ;
	$joomla_user = JFactory::getUser($userId) ;
}
CheckJoomlaUser($joomla_user) ;
if ($userId == 62 or $userId == 66)
	ini_set('display_errors', 1) ; // extensive error reporting for debugging
$joomla_session = JFactory::getSession() ;
$joomla_session->start() ; // Keep alive?

function CheckJoomlaUser($joomla_user) {
	global $userIsPilot, $userIsAdmin, $userIsBoardMember, $userIsInstructor, $userIsMechanic,$userIsStudent, $userIsFlightPilot, $userIsFlightManager, $userNoFlight ;
	global $userName, $userFullName, $userId, $originUserId ;
	global $joomla_member_group, $joomla_admin_group, $joomla_sysadmin_group, $joomla_superuser_group, $joomla_board_group ;
	global $joomla_pilot_group, $joomla_student_group, $joomla_instructor_group, $joomla_instructor_group2, $joomla_mechanic_group ;
	global $joomla_flight_group, $joomla_flight_pilot_group, $joomla_flight_manager_group, $joomla_no_flight ;

	// And now use this information
	if ($joomla_user->guest) {
		$userName = 'guest' ;
		$userFullName = 'invité' ;
		$userId = 0 ;
		$originUserId = $userId ;
	} else {
		$userId = $joomla_user->id ;
		$originUserId = $userId ;
		$userFullName = $joomla_user->name ;
		$userName = $joomla_user->username ;
		$joomla_user->setLastVisit() ;
//		if ($userId == 62) { $userId = 296 ; print("Forcing userId = $userId") ; }
	}
	$joomla_groups = $joomla_user->groups ;
	// User privileges
	$userIsPilot = array_key_exists($joomla_pilot_group, $joomla_groups)  ;
	$userIsAdmin = array_key_exists($joomla_admin_group, $joomla_groups) 
		|| array_key_exists($joomla_sysadmin_group, $joomla_groups) 
		|| array_key_exists($joomla_superuser_group, $joomla_groups) ;
	$userIsInstructor = array_key_exists($joomla_instructor_group, $joomla_groups) ;
	$userIsBoardMember = array_key_exists($joomla_board_group, $joomla_groups) ;
	$userIsMechanic = array_key_exists($joomla_mechanic_group, $joomla_groups) ;
	$userIsStudent = array_key_exists($joomla_student_group, $joomla_groups) ;
	$userIsFlightPilot = array_key_exists($joomla_flight_pilot_group, $joomla_groups) || array_key_exists($joomla_flight_group, $joomla_groups);
	$userIsFlightManager = array_key_exists($joomla_flight_manager_group, $joomla_groups) ;
	$userNoFlight = array_key_exists($joomla_no_flight, $joomla_groups) ;
}

function MustBeLoggedIn() {
	global $userId ;

	if ($userId == 0) {
		header("Location: https://www.spa-aviation.be/resa/connect.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
		exit ;
	}
}

// Opening / closing hour of airports vary... hence the use of a PHP function
// From AIP in UTC as Unix time stamp
// 01 FEB - 31 OCT: every day: 0800 - SS + 30 without exceeding 1900
// 01 NOV - 31 JAN: every day: 0800 - 1600
//
// Tricky stuff for daylight saving and AIP:
// The times specified in the AIP and AICs are expressed in UTC and relate to the wintertime period.
// In summertime period, one HR is to be substracted from the published UTC times. The new obtained UTC time + two HR gives the local time.
function airport_opening_local_time($year, $month, $day) {
	$today = new Datetime("$year-$month-$day 08:00:00 GMT") ;
	$today_local = new Datetime("$year-$month-$day 08:00:00") ;
	if ($today_local->format('O') == '+0200')
		return $today->format('U') -3600 ;
	else
		return $today->format('U') ;
}

function airport_closing_local_time($year, $month, $day) {
	if ($month >= 2 and $month <= 10)
		$today = new Datetime("$year-$month-$day 19:00:00 GMT") ;
	else
		$today = new Datetime("$year-$month-$day 16:00:00 GMT") ;
	$today_local = new Datetime("$year-$month-$day 08:00:00") ;
	if ($today_local->format('O') == '+0200')
		return $today->format('U') -3600 ;
	else
		return $today->format('U') ;
	return $today->format('U') ; // as ->getTimeStamp() is not widely supported ....
}

$validity_warning = 30 ; // Number of days before validity expiration to flag as warning

// List of webcam to display... should be at least two (can repeat one of course)
//$webcam_uris = array("https://www.skydivespa.be/webcam/cam2/image.jpg", "https://www.spa-aviation.be/webcam.php") ;
$webcam_uris = array("https://www.spa-aviation.be/webcam.php") ;
$favicon = "https://www.spa-aviation.be/favicon32x32.ico" ;// The usual web browser favicon + also used in Calendar
$ical_name = "Calendrier réservations et événements RAPCS" ; // Name of the iCAL calendar
$ical_organizer = "RAPCS asbl" ; // Name of the organizer of the iCAL calendar

// More Joomla dependencies
$avatar_root_resized_uri = "https://www.spa-aviation.be/media/kunena/avatars/resized/size144" ;
$avatar_root_resized_directory = "media/kunena/avatars/resized/size144" ;
$avatar_root_uri = "https://www.spa-aviation.be/media/kunena/avatars" ;
$avatar_root_directory = "media/kunena/avatars" ;

// Aircraft Technical Log variables
$atl_uploadfiles_path = "ATL/upload";
$atl_maxFileSize = 1024*1024*10; // 10 Mb
$atl_maxNumberOfPixels = 2000; // Max number of pixels in a picture (Used to resize)

// Table names

// Application specific table names
$table_planes = 'rapcs_planes' ;
$table_planes_history = 'rapcs_planes_history' ;
$table_planes_validity = 'rapcs_planes_validity' ;
$table_bookings = 'rapcs_bookings' ;
$table_airports = 'rapcs_airports' ;
$table_person = 'rapcs_person' ;
$table_validity = 'rapcs_validity' ;
$table_validity_type = 'rapcs_validity_type' ;
$table_logbook = 'rapcs_logbook' ;
$table_journal = 'rapcs_journal' ;
$table_fi_agenda = 'rapcs_fi_agenda' ;
$table_agenda = 'rapcs_agenda' ;
$table_metar_history = 'rapcs_metar_history' ;
$table_webcam = 'rapcs_webcam' ;
$table_flight = 'rapcs_flight' ;
$table_flights = 'rapcs_flight' ;
$table_flights_pilots = 'rapcs_flights_pilots' ;
$table_flights_ledger = 'rapcs_flights_ledger' ;
$table_pax = 'rapcs_pax' ;
$table_pax_role = 'rapcs_pax_role' ;
$table_news = 'rapcs_news' ;
$table_tracks = 'rapcs_tracks' ;
$table_local_tracks = 'rapcs_local_tracks' ;
$table_bk_balance = 'rapcs_bk_balance' ;
$table_bk_invoices = 'rapcs_bk_invoices' ;
$table_bk_ledger = 'rapcs_bk_ledger' ;
$table_blocked = 'rapcs_blocked' ;
$table_payconiq = 'rapcs_payconiq' ;
$table_dto_flight = 'rapcs_dto_flight' ;
$table_dto_exercice = 'rapcs_dto_exercice' ;
$table_dto_student_exercice = 'rapcs_dto_student_exercice' ;
$table_dto_student = 'rapcs_dto_student' ;
$table_dto_attachment = 'rapcs_dto_attachment' ;
$table_incident = 'rapcs_incident' ;
$table_incident_history = 'rapcs_incident_history' ;
$table_company = 'rapcs_company' ;
$table_company_member = 'rapcs_company_member' ;
$table_membership_fees = 'rapcs_bk_fees' ;

// Joomla specific table names
$table_user_usergroup_map = 'jom_user_usergroup_map' ;
$table_users = 'jom_users' ;
$table_session = 'jom_session' ;

// Some constants
// type of booking
define('BOOKING_PILOT', 1) ;
define('BOOKING_INSTRUCTOR', 2) ;
define('BOOKING_ADMIN', 3) ;
define('BOOKING_MAINTENANCE', 4) ;
define('BOOKING_CUSTOMER', 5) ;
define('BOOKING_ON_HOLD', 6) ;
// type of plane engine/Hobbs meter
define('METER_TENTH', 6) ; // 1 'tick' means one tenth of hour == 6 minutes
define('METER_MINUTE', 1) ; // 1 'tick' means one minute

$default_airport = 'EBSP' ;
$default_metar_station = 'EBSP' ;
$default_metar_altitude = 1542 ; // in feet AMSL for EBSP
$default_timezone = 'Europe/Brussels' ;
$apt_latitude = 50.47851 ;
$apt_longitude = 5.90990 ;
$runways_qfu = array(48, 228) ;
$local_altimeter_bound = 10000 ; // Local flight is up to this AMSL
$local_longitude_bound = 0.375 ; // +/- degree from $apt_longitude
$local_latitude_bound = 0.25 ; // +/- degree from $apt_longitude
$local_delay = 5 ; // Maximum duration in minutes

//$local_longitude_bound = 1.5 ; // +/- degree from $apt_longitude
//$local_latitude_bound = 1.0 ; // +/- degree from $apt_longitude

// Used to compute the Sunset/Sunrise
// IRM, Uccle, Belgique
$latitude = 50.798015 ;
$longitude = 4.358991 ;
// `Zenith' is the angle that the centre of the Sun makes to a line perpendicular to the Earth's surface. 
// The best Overall figure for zenith is 90+(50/60) degrees for true sunrise/sunset
// Civil twilight 96 degrees - Conventionally used to signify twilight
// Nautical twilight 102 degrees - the point at which the horizon stops being visible at sea.
// Astronical twilight at 108 degrees - the point when Sun stops being a source of any illumination.
$zenith = 90.5 ;

// Some common initializations

$mysqli_link = mysqli_connect($db_host, $db_user, $db_password) ; 
if (! $mysqli_link) die("Impossible de se connecter a MySQL:" . mysqli_connect_error()) ;
if (! mysqli_select_db($mysqli_link, $db_name)) die("Impossible d'ouvrir la base de donnees:" . mysqli_error($mysqli_link)) ;

// Do we need to redirect to the membership renewal page ?
if ($userId > 0 and $userId != 294) { // Only for logged-in users and not for SPW
	$membership_year = date('Y') ;
	if (date('m') == 12 && date('d') >= 15)
		$membership_year ++ ; // ask for renewal from 15th of December on
	$result_fee = mysqli_query($mysqli_link, "SELECT * FROM $table_membership_fees 
		WHERE bkf_user=$userId AND bkf_year = '$membership_year'")
		or journalise($userId, 'E', "Cannot check whether user has paid membership fee: " . mysqli_error($mysqli_link)) ;
	$row_fee = mysqli_fetch_array($result_fee) ;
	if (!$row_fee) {
		$cb = urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) ;
		if ($_SERVER['PHP_SELF'] != '/resa/mobile_journal.php' && $_SERVER['PHP_SELF'] != '/resa/mobile_membership.php')
			if (!isset($_COOKIE['membership'])) {
					journalise($userId, "I", "Unpaid membership, redirecting to membership page") ;
					header("Location: https://www.spa-aviation.be/resa/mobile_membership.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
			}
	}
} else $row_fee = NULL ;// $userId > 0

// IP addresses are fetched from the X-Forwarded-For HTTP header
function getClientAddress() {
	$headers = (function_exists( 'apache_request_headers')) ? apache_request_headers(): $_SERVER;
	$remote_address = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] ;
	$remote_address = (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $remote_address ;
	return $remote_address ;
}

// $message must be UTF-8
function journalise($userId, $severity, $message) {
	global $table_journal, $mysqli_link, $db_host, $db_user, $db_password, $db_name ;
	
	if ($table_journal == '') $table_journal = 'rapcs_journal' ;

	if (! is_resource($mysqli_link)) { // Naive ? attempt to reconnect in case of lost connection...
		$mysqli_link = mysqli_connect($db_host, $db_user, $db_password) ;
		if (! $mysqli_link) die("Impossible de se connecter a MySQL: " . mysqli_connect_error()) ;
		if (! mysqli_select_db($mysqli_link, $db_name)) die("Impossible d'ouvrir la base de donnees: " . mysqli_error($mysqli_link)) ;
	}
	
	$remote_address = getClientAddress() ;
	$message = web2db($message) ;
	$message = mysqli_real_escape_string($mysqli_link, $message) ;
	$uri = $_SERVER['REQUEST_URI'] ;
	$trusted_booker = (isset($_COOKIE['trusted_booker']) and $_COOKIE['trusted_booker'] == 1) ? 1 : 0 ;
	$params = '' ;
	foreach($_POST as $var => $value) {
		if ($var != "password")	$params .= "$var=$value," ;
	}
	$params =  mysqli_real_escape_string($mysqli_link, web2db($params)) ;
	$result = mysqli_query($mysqli_link, "INSERT INTO $table_journal(j_datetime, j_address, j_jom_id, j_trusted_booker, j_severity, j_message, j_uri)
		VALUES(SYSDATE(),'$remote_address', $userId, $trusted_booker, '$severity', '$message', '$uri $params')") ;
	if (! $result) {
		$sql_error_message = mysqli_error($mysqli_link) ;
		print("Cannot journalise($userId, $severity, $message): $sql_error_message for table $table_journal\n\n") ;
		@mail('eric@vyncke.org', "Cannot journalise in $table_journal", "Error message: $sql_error_message\nremote address = '$remote_address'\nuserId='$userId'\nserverity=$severity\nmessage=$message\nuri=$uri\nparams=$params") ;
	}
	if ($severity == 'F') // Fatal
		die("Une erreur fatale a eu lieu. Impossible de continuer l'execution de $_SERVER[PHP_SELF]. Veuillez contacter eric@vyncke.org avec ce message:\n $message \n") ;
}

// same function exists in reservation.js
function planeClassIsMember($member, $group) {
        if ($member == $group) return true ;
        switch ($group) {
        case 'C182':
                return $member == 'C150' || $member == 'C172' || $member == 'C172VP' ;
                break ;
        case 'C172VP':
                return $member == 'C150' || $member == 'C172' ;
                break ;
        case 'C172':
                return $member == 'C150' ;
                break ;
        }
        return false ;
}

function web2db($msg) {
	global $convertToUtf8 ;
	if (false & $convertToUtf8 )
		return mb_convert_encoding($msg, "ISO-8859-15", mb_detect_encoding($msg, "UTF-8, ISO-8859-15, ISO-8859-1", false)) ;
	else
		return $msg ;
}

function db2web($msg) {
	global $convertToUtf8 ;
	if ($convertToUtf8 ) {
		return mb_convert_encoding($msg, "UTF-8", mb_detect_encoding($msg, "ISO-8859-15, UTF-8, ISO-8859-1", false)) ;
	} else
		return $msg ;
}

// SMTP server name, port, user/passwd
$smtp_info['host'] = $smtp_host ;
$smtp_info['localhost'] = $smtp_localhost ;
$smtp_info['port'] = $smtp_port ;
$smtp_info['auth'] = True;
$smtp_info['username'] = $smtp_user ;
$smtp_info['password'] = $smtp_password ;
$smtp_info['debug'] = False;
$smtp_info['persist'] = False;
$mail = NULL ;

$mime_preferences = array(
    "input-charset" => "UTF-8",
    "output-charset" => "UTF-8",
    "scheme" => "Q",
    "line-length" => 76,
    "line-break-chars" => "\n"
);

function smtp_mail($smtp_to, $smtp_subject, $smtp_body, $str_headers  = NULL) {
	require_once 'PEAR.php';
	require_once 'Mail.php';

	global $smtp_from, $smtp_return_path, $smtp_info, $mime_preferences ;
	global $mail, $userId, $originUserId ;
	
	if (! isset($mail) or $mail == NULL or $smtp_info['persist'] == False) 
		$mail = Mail::factory('smtp', $smtp_info); // Create the mail object using the Mail::factory method
	PEAR::setErrorHandling(PEAR_ERROR_EXCEPTION) ; // Force an exception to be trapped

	// $smtp_to could be an array, so, let flatten it in to ',' separated email address
    // $smtp_to = implode(',', $smtp_to) ;
	// Let's build the default SMTP headers, they could be overwritten by $str_headers
	if (isset($smtp_from) and $smtp_from != '') $headers['From'] = $smtp_from ;
	$headers['To'] = $smtp_to ;
	if (mb_check_encoding($smtp_subject, 'ASCII')) // Text needs to be encoded as it is not pure ASCII
		$headers['Subject'] = $smtp_subject ;
	else {
		$header_value = '' ;
		foreach (explode(" ", $smtp_subject) as $word)
			if (mb_check_encoding($word, 'ASCII'))
				$header_value .= " $word" ;
			else {
				$word = '=?utf-8?Q?' . quoted_printable_encode($word) . '_?=' ; // Adding a '_' at the end to replace a word-encoded space
				$header_value .= "\r\n $word" ; // split on multiple lines to ensure lines are not longer than 76 characters
			}
		$headers['Subject'] = trim($header_value) ;
	}
	$headers['MIME-Version'] = '1.0' ;
	$headers['Message-ID'] = '<' . sha1(microtime()) . '@' . $smtp_info['localhost'] . '>' ;
	$headers['Date'] = date('r') ;
	// Override the default headers generated above by the ones passed as parameters
	// Just beware of character case in headers...
	foreach (explode( "\r\n", $str_headers) as $header_line) {
			$token = explode(':', $header_line, 2) ;
			if ($token[0] != '' and $token[1] != '') {
				// Ensure that only US-ASCII is actually used in the SMTP headers cfr RFC 2047 else use Q-encoding
				// https://dogmamix.com/MimeHeadersDecoder/
				$header_value = '' ;
				foreach (explode(" ", $token[1]) as $word)
					if (mb_check_encoding($word, 'ASCII'))
						$header_value .= " $word" ;
					else {
						$word = '=?utf-8?Q?' . quoted_printable_encode($word) . '_?=' ; // Adding a '_' at the end to replace a word-encoded space
						$header_value .= "\r\n $word" ; // split on multiple lines to ensure lines are not longer than 76 characters
					}
				$headers[$token[0]] = trim($header_value) ;
			}
	}
	// This handling must be done after processing headers as parameters as guessing MIME type is just a 2nd guess and we should change the HTML content only when unsure
	if (!isset($headers['Content-Type']) or $headers['Content-Type'] == '') {
		// Ensure the body and its type are canonical
		$plain_text_body = strip_tags($smtp_body) ;
		$is_HTML = $smtp_body != $pain_text_body ;
		$MIME_subtype = ($is_HTML) ? 'html' : 'plain' ;
		$headers['Content-Type'] = "text/$MIME_subtype; charset=UTF-8" ;
//		$headers['X-EVY-Debug'] = "Unspecified content-type, guessing $MIME_subtype" ;
	}
	if (stripos($headers['Content-Type'], 'html') !== 0) { // Let's even be more canonical for HTML
		if (stripos($smtp_body, '<body') === FALSE)
			$smtp_body = "<body lang=\"fr\">\n$smtp_body\n</body>" ;
		if (stripos($smtp_body, '<html') === FALSE)
			$smtp_body = "<html>\n$smtp_body\n</html>" ;
	}
	if ($smtp_info['debug']) {
			print_r($headers) ;
			print("Enveloppe To: $smtp_to") ;
	}
	
	try {
		# $smtp_to an array or a string with comma separated recipients.
		$mail->send($smtp_to, $headers, $smtp_body);
	} 
	catch(Exception $e) {
  		Journalise($originUserId, 'E', "Cannot send mail to '$smtp_to': " . $e->getMessage() . '(' . $e->getCode() . ')');
  		return False ;
	}
//	Journalise($userId, "D", "Email sent to '$smtp_to' with subject: '" . $headers['Subject'] . "'") ;
	return True ;
}

//Try to sanitize input
foreach($_REQUEST as $key=>$value)
        if (!is_array($_REQUEST[$key]))
		$_REQUEST[$key] = strip_tags($value) ;
	else
		foreach($_REQUEST[$key] as $sub_key => $sub_value)
			$_REQUEST[$key][$sub_key] = strip_tags($sub_value) ;
foreach($_GET as $key=>$value)
        if (!is_array($_GET[$key]))
		$_GET[$key] = strip_tags($value) ;
	else
		foreach($_GET[$key] as $sub_key => $sub_value)
			$_GET[$key][$sub_key] = strip_tags($sub_value) ;
foreach($_POST as $key=>$value)
        if (!is_array($_POST[$key]))
		$_POST[$key] = strip_tags($value) ;
	else
		foreach($_POST[$key] as $sub_key => $sub_value)
			$_POST[$key][$sub_key] = strip_tags($sub_value) ;

// Refresh session table & co, as long as this file is included (by Ajax requests or plain pages)
if ($userId > 0) {
	mysqli_query($mysqli_link, "update jom_session set time = unix_timestamp() where userid = $userId") or die("Cannot update jom_session: " . mysqli_error($mysqli_link)) ;
	mysqli_query($mysqli_link, "update jom_users set lastvisitDate = utc_timestamp() where id = $userId") or die("Cannot update jom_user: " . mysqli_error($mysqli_link)) ;
}
?>