<?php
// TODO fix the mysql interface !!
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
include 'Mail.php';

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

	$db_name = 'name' ;
	$db_user = 'user' ;
	$db_password = 'password' ;
	$db_host = 'host' ;
	$joomla_session = true ;
	$joomla_connection_page = "https://www.example.org/index.php/fr/" ;
	$convertToUtf8 = false ;
	$test_mode = false ;
	$managerEmail = "info@example.org" ;
	$managerName = "Joe Doe" ;
	$fleetEmail = "fleet@example.org" ;
	$fleetName = "Fleet Management" ;
	$bccTo = "foo@example.org" ;
	$bccTo = "" ;
	$cache_directory = getcwd() ;
	$rapcs_metar = 'rapcs_metar' ;
	// SMTP local parameters
	// The MTA where the email will be submitted. Beware MUST be IPv4 and NOT IPv6 :-(
	$smtp_host = 'xxx.net' ;
	$smtp_port = 587 ;
	$smtp_user = 'no-reply@xxxx' ;
	$smtp_password = 'xxxx' ;
//	$smtp_return_path = 'xxx@xxx.org' ;
	$smtp_from = 'xxx@xxx.org' ;
	$smtp_localhost = 'xxx.be' ;
	// Need to change when jom_usergroups content changes!!!!!
	$joomla_member_group = 2 ;
	$joomla_admin_group = 7 ; // Board member
	$joomla_sysadmin_group = 6 ;
	$joomla_superuser_group = 8 ;
	$joomla_pilot_group = 13 ;
	$joomla_student_group = 16 ;
	$joomla_instructor_group = 14 ;
	$joomla_instructor_group2 = 15 ;
	$joomla_mechanic_group = 17 ;

	// Get information from Joomla
	define( '_JEXEC', 1 );
	define( 'JPATH_BASE', realpath(dirname(__FILE__) . '/..' ));
	require_once ( JPATH_BASE . '/includes/defines.php' );
	require_once ( JPATH_BASE . '/includes/framework.php' );
	$mainframe = JFactory::getApplication('site');
	$mainframe->initialise();
	$joomla_user = JFactory::getUser() ;
	if ($joomla_user->guest and isset($_SESSION['jom_id'])) { // User is not logged in via Joomla but via the mobile app
		$userId = intval($_SESSION['jom_id']) ;
		$joomla_user = JFactory::getUser($userId) ;
	}
	CheckJoomlaUser($joomla_user) ;
	$joomla_session = JFactory::getSession() ;
	$joomla_session->start() ; // Keep alive?


function CheckJoomlaUser($joomla_user) {
	global $userIsPilot, $userIsAdmin, $userIsInstructor, $userIsMechanic,$userIsStudent ;
	global $userName, $userFullName, $userId ;
	global $joomla_member_group, $joomla_admin_group, $joomla_sysadmin_group, $joomla_superuser_group ;
	global $joomla_pilot_group, $joomla_student_group, $joomla_instructor_group, $joomla_instructor_group2, $joomla_mechanic_group ;

	// And now use this information
	if ($joomla_user->guest) {
		$userName = 'guest' ;
		$userFullName = 'invité' ;
		$userId = 0 ;
	} else {
		$userId = $joomla_user->id ;
		$userFullName = $joomla_user->name ;
		$userName = $joomla_user->username ;
		$joomla_user->setLastVisit() ;

	}
	
	$joomla_groups = $joomla_user->groups ;
	// User privileges
	$userIsPilot = array_key_exists($joomla_pilot_group, $joomla_groups)  ;
	$userIsAdmin = array_key_exists($joomla_admin_group, $joomla_groups) 
		|| array_key_exists($joomla_sysadmin_group, $joomla_groups) 
		|| array_key_exists($joomla_superuser_group, $joomla_groups) ;
	$userIsInstructor = array_key_exists($joomla_instructor_group, $joomla_groups) ;
	$userIsMechanic = array_key_exists($joomla_mechanic_group, $joomla_groups) ;
	$userIsStudent = array_key_exists($joomla_student_group, $joomla_groups) ;
	// Session
}

// Facebook integration
$fb_app_id = "xxxx" ;
$fb_app_secret = "xxxx" ;
$fb_login_cb_url = "https://example.org/facebook-cb.php" ;

// MapBox integration
$mapbox_token = 'pk.xxxx' ;

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
$webcam_uris = array("https://www.example.org/webcam.php", "https://example.org/mjpg/video.mjpg") ;
$favicon = "https://www.example.org/favicon32x32.ico" ; // The usual web browser favicon + also used in Calendar
$ical_name = "My club calendar" ; // Name of the iCAL calendar
$ical_organizer = "My flighht club" ; // Name of the organizer for iCAL calendar

// More Joomla dependencies
$avatar_root_resized_uri = "https://www.example.org/media/kunena/avatars/resized/size144" ;
$avatar_root_resized_directory = "media/kunena/avatars/resized/size144" ;
$avatar_root_uri = "https://www.example.org/media/kunena/avatars" ;
$avatar_root_directory = "media/kunena/avatars" ;

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
$table_pax = 'rapcs_pax' ;
$table_pax_role = 'rapcs_pax_role' ;
$table_news = 'rapcs_news' ;

// Joomla specific table names
$table_user_usergroup_map = 'jom_user_usergroup_map' ;
$table_users = 'jom_users' ;
$table_session = 'jom_session' ;

// The shared secret for direct access to booking
$shared_secret = "xxxx" ;

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
$runways_qfu = array(48, 228) ;

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

// IP addresses are fetched from the X-Forwarded-For HTTP header
function getClientAddress() {
	$headers = (function_exists( 'apache_request_headers')) ? apache_request_headers(): $_SERVER;
	$remote_address = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] ;
	$remote_address = (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $remote_address ;
	return $remote_address ;
}

// $message must be UTF-8
function journalise($userId, $severity, $message) {
	global $table_journal, $mysqli_link ;
	$remote_address = getClientAddress() ;
	$message = web2db($message) ;
	$message = mysqli_real_escape_string($mysqli_link, $message) ;
	$uri = $_SERVER['REQUEST_URI'] ;
	mysqli_query($mysqli_link, "insert into $table_journal(j_datetime, j_address, j_jom_id, j_severity, j_message, j_uri)
		values(sysdate(),'$remote_address', $userId, '$severity', '$message', '$uri')") 
		or print("Cannot journalise: " . mysqli_error($mysqli_link)) ;
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
	if ($convertToUtf8 )
		return(iconv("UTF-8", "ISO-8859-1//TRANSLIT", $msg)) ; 
	else
		return $msg ;
}


function db2web($msg) {
	global $convertToUtf8 ;
	if ($convertToUtf8 )
		return(iconv("ISO-8859-1", "UTF-8//TRANSLIT", $msg)) ; 
	else
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

$mail = NULL;

function smtp_mail($smtp_to, $smtp_subject, $smtp_body, $str_headers  = NULL) {
	require_once 'PEAR.php';
	require_once 'Mail.php';

	global $smtp_from, $smtp_return_path, $smtp_info, $mime_preferences ;
	global $mail, $userId ;
	
	// Ensure the body and its type are canonical
	$plain_text_body = strip_tags($smtp_body) ;
	$is_HTML = $smtp_body != $pain_text_body ;
	$MIME_subtype = ($is_HTML) ? 'html' : 'plain' ;
	if ($is_HTML) { // Let's even be more canonical for HTML
		if (stripos($smtp_body, '<body') === FALSE)
			$smtp_body = "<body>\n$smtp_body\n</body>" ;
		if (stripos($smtp_body, '<html') === FALSE)
			$smtp_body = "<html lang=\"fr\">\n$smtp_body\n</html>" ;
	}

	if (! isset($mail) or $mail == NULL or $smtp_info['persist'] == False) 
		$mail = & Mail::factory('smtp', $smtp_info); // Create the mail object using the Mail::factory method
	PEAR::setErrorHandling(PEAR_ERROR_EXCEPTION) ; // Force an exception to be trapped
	if (isset($smtp_from) and $smtp_from != '') $headers['From'] = $smtp_from ;
	$headers['To'] = $smtp_to ;
	$headers['Subject'] = $smtp_subject ;
	$headers['MIME-Version'] = '1.0' ;
	$headers['Content-Type'] = 'text/html; charset="UTF-8"' ;
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
	if ($smtp_info['debug']) print_r($headers) ;
	try {
		$mail->send($smtp_to, $headers, $smtp_body);
	} 
	catch(Exception $e) {
  		Journalise($userId, 'E', "Cannot send mail to '$smtp_to': " . $e->getMessage() . '(' . $e->getCode() . ')');
  		return False ;
	}
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
