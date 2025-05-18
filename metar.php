<?php
/*
   Copyright 2014-2024 Eric Vyncke

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

// Should we play with: http://aviationweather.gov/adds/dataserver_current/httpparam?datasource=metars&requestType=retrieve&format=xml&mostRecentForEachStation=constraint&hoursBeforeNow=1.25&stationString=EBBR

require_once 'dbi.php' ; // Mainly to have access to $default_metar_station...

$reply = array() ;
$reply['error'] = '' ;

$metar_station = (isset($_REQUEST['station'])) ? mysqli_real_escape_string($mysqli_link, $_REQUEST['station']) : $default_metar_station ;
$metar_station = strtoupper($metar_station) ;
$reply['station'] = $metar_station ;
if (strlen($metar_station) != 4) $reply['error'] = 'invalid station name' ;
$format = (isset($_REQUEST['format'])) ? $_REQUEST['format'] : 'json' ;

if (isset($_REQUEST['altitude']))
	$metar_altitude = $_REQUEST['altitude'] ;
else if ($metar_station == $default_metar_station)
	$metar_altitude = $default_metar_altitude ;
else {
	$sql_result = mysqli_query($mysqli_link, "select * from $rapcs_metar where m_station = '$metar_station'") or die("Cannot retrieve altitude (user=$db_user, host=$db_host): " . mysqli_error($mysqli_link));
	if ($sql_result) {
		$row = mysqli_fetch_array($sql_result) ;
		if ($row) {
			$metar_altitude = $row['m_elevation'] ;
			$reply['name'] = $row['m_name'] ;
		} else
			$reply['error'] = "No such METAR station name: $metar_station" ;
	}
}

if (isset($_REQUEST['callback']))
	header('Content-type: script/javascript') ;
elseif ($format == 'json')
	header('Content-type: application/json'); // Header must be emitted as header and not in the cached data !
elseif ($format == 'html')
	header('Content-type: text/html'); // Header must be emitted as header and not in the cached data !

// METAR are usually updated every 30 minutes, so, caching 3 minutes should be OK
header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', time())) ;
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + (3 * 60))); // 3 minutes
header('Cache-Control: public, max-age=180') ; // 3 Minutes

function fix_temperature($t) {
	if (preg_match('@M(.+)@', $t, $matches))
		return -1 * $matches[1] ;
	else
		return +1 * $t ;
}

$belgocontrol_automated_stations = array('EBSP', 'EBSH', 'EBHH', 'EBCT', 'EBSZ') ;

if (in_array($metar_station, $belgocontrol_automated_stations)) { // Special cases for some automated station in Belgocontrol
	$reply['source'] = 'Skeyes' ;
	$opts = array('https' => array(
		'timeout' => 1.0,
		'user_agent' => 'Mozilla')) ;
	$context = stream_context_create($opts) ;
	$f = @fopen("https://nav.vyncke.org/$metar_station.TXT", "r", false, $context) ;
	if (! $f) {
		$error_message = error_get_last() ;
		$reply['error'] = "Cannot access METAR for $metar_station on the Internet: " . $error_message['message'] ;
	} else {
		$line = @fgets($f) ; // First line is  the date
		if (! $line)
			$reply['error'] = 'METAR format is invalid: empty reply' ;
		else {
			$reply['datetime'] = trim($line) ;
			$line = @fgets($f) ; // Second line is the actual METAR
			if (! $line)
				$reply['error'] = 'METAR format is invalid: no second line' ;
			else
				$reply['METAR'] = trim($line) ;
			if (trim($line) == 'NOT AVAILABLE')
				$reply['error'] = 'METAR was not available over the Internet' ;
		}
	}
	$reply['METAR'] = trim($line) ;
	@fclose($f) ;
} else { // not Belgocontrol stations
//	$f = @fopen("http://weather.noaa.gov/pub/data/observations/metar/stations/$metar_station.TXT", "r") ;
//	$f = @fopen("http://tgftp.nws.noaa.gov/data/observations/metar/stations/$metar_station.TXT", "r") ;
//	$url = 'https://www.aviationweather.gov/adds/dataserver_current/httpparam?' .
//		'dataSource=metars&requestType=retrieve&format=xml&hoursBeforeNow=3&mostRecent=true&fields=raw_text&stationString=' . $metar_station ;
// https://aviationweather.gov/cgi-bin/data/metar.php?ids=EBBR&hours=0&order=id%2C-obs&sep=true
	$url = "https://aviationweather.gov/cgi-bin/data/metar.php?ids=$metar_station&hours=0&order=id%2C-obs&sep=true" ;
	$reply['source'] = $url ;
	$xml_string = @file_get_contents($url, "r") ;
	$reply['http_response'] = $xml_string ;
	if ($xml_string === false) {
		$error_message = error_get_last() ;
		$reply['error'] = "Cannot access METAR for $metar_station on the Internet: " . $error_message['message'] ;
	} else { // Try using the XML format... without throwing error
		libxml_use_internal_errors(true) ;
		$xml = simplexml_load_string($xml_string);
		if ($xml === false) {
			// $reply['error'] = 'Cannot parse the NOAA XML response' ;
			// Normally only a one-line response witht this URL
			$line = trim($xml_string) ;
		} else
			$line = $xml->data[0]->METAR->raw_text ;
		$reply['METAR'] = trim($line) ;
//		$reply['xml_errors'] = serialize($xml->errors) ;
//		$reply['xml_warnings'] = serialize($xml->warnings) ;
	} // End of XML parsing
} // not Belgocontrol stations

if ($line == '')
	$reply['error'] = "Unable to retrieve the METAR from the Internet" ;
if ($reply['error'] == '') {
	// Now let's try to parse lines such as 
	// EBLB 020703Z 28011KT 9000 -SHSN FEW002 BKN010 BKN020 00/M00 Q1003 GRN
	// EBCI 290820Z 19008KT 150V210 9000 FEW036 10/08 Q1017 NOSIG= 
	// EBLB 021725Z AUTO 27010KT 9999 FEW050/// BKN070/// 01/M02 Q1010 BLU
	// EBSP 290820Z AUTO 17006KT 8000 // NCD 09/07 Q1018= 
	// ELLX 301520Z 10005KT 050V140 9999 BKN015 11\/08 Q1025 NOSIG
	// EBSP 301650Z AUTO VRB02KT 5000 // NCD 10/07 Q1025
	// KIAD 301752Z 30012G19KT 10SM SCT045 14\/03 A3005
	// EBSH 212050Z AUTO 29004KT 250V320 5000NDV SCT003\/\/\/ BKN004\/\/\/ OVC009\/\/\/ 07\/06 Q1016
	// EBSP 302320Z AUTO 15017KT //// // NCD 07/05 Q1023 <<<< only //// ?
	// EBSP 201650Z AUTO 27007KT 240V300 //// // OVC002/// 04/03 Q1002 <<<< ????

	// TODO
	// EBBR 151050Z 20018G28KT 9999 FEW030 11/04 Q1009 TEMPO 19018G30KT
	// KHAF 031935Z AUTO 6SM OVC060 14/07 A2987 RMK AO2 PWINO PNO (missing wind)
	// LPAR 181000Z 00000KT FEW010 BKN018 15/13 Q1028 (missing visibility)
	// CWRA 051500Z AUTO 33017G23KT M07/M11 RMK AO1 PK WND 33026/1447 SLP127 T10671112 53016
	// ETAD 061808Z COR 21004KT 9999 SCT004 OVC030 04/04 A2953 RMK SCT004 V BKN $ COR 1812 <<< COR ???
	// EBSH 070850Z AUTO VRB10G23KT 9999 // SCT004/// BKN005/// OVC007/// 03/02 Q0990
	// EBBE 152222Z AUTO //////KT //// /// ///// Q////
	// EBSP 211250Z AUTO 23018G29KT 190V260 8000 -DZ VV/// 09/08 Q1014
	// EBSP 020720Z NIL

	$tokens = explode(' ', trim($line)) ;
	// Try to compute METAR age...
	$day_utc = gmdate('j') ;
	$previous_day_utc = gmdate('j', time() - 24 * 60 * 60) ;
	$hour_utc = gmdate('G') ;
	$minute_utc = intval(gmdate('i')) ;
	$day_metar = intval(substr($tokens[1], 0, 2)) ;
	$hour_metar = intval(substr($tokens[1], 2, 2)) ;
	$minute_metar = intval(substr($tokens[1], 4, 2)) ;
	$reply['datetime'] = gmdate('Y-m-') . "$day_metar $hour_metar:$minute_metar:00" ;
	if ($day_utc == $day_metar)
		$metar_age = 0 ;
	elseif ($previous_day_utc == $day_metar)
		$metar_age = 24 * 60 ;
	else
		$metar_age = 'invalid' ;
	if (is_numeric($metar_age)) {
		$metar_age = $metar_age + 60 * ($hour_utc - $hour_metar) + $minute_utc - $minute_metar ;
	}
	$reply['age'] = $metar_age ;
	
	// Now let's parse the METAR itself
	$index = 2 ;
	if ($tokens[$index] == 'AUTO') $index++ ; // Let's skip the AUTO
	if ($tokens[$index] == 'NIL') { // Automated station having a problem...
		$reply['condition'] = '?' ;
		goto emit ; // Ugly...
	}

// WIND
	if (preg_match('@^(\d\d\d)(\d\d)KT$@', $tokens[$index], $matches)) {
		$reply['wind'] = $tokens[$index++] ;
		$reply['wind_direction'] = $matches[1] ;
		$reply['wind_velocity'] = 0 + $matches[2] ;
	} elseif (preg_match('@^(\d\d\d)(\d\d)G(\d\d)KT$@', $tokens[$index], $matches)) {
		$reply['wind'] = $tokens[$index++] ;
		$reply['wind_direction'] = $matches[1] ;
		$reply['wind_velocity'] = 0 + $matches[2] ;
		$reply['wind_gust'] = 0 + $matches[3] ;
	} elseif (preg_match('@^VRB(\d\d)KT$@', $tokens[$index], $matches)) {
		$reply['wind'] = $tokens[$index++] ;
		$reply['wind_direction'] = 'VRB' ;
		$reply['wind_velocity'] = 0 + $matches[1] ;
	} elseif (preg_match('@^VRB(\d\d)G(\d\d)KT$@', $tokens[$index], $matches)) {
		$reply['wind'] = $tokens[$index++] ;
		$reply['wind_direction'] = 'VRB' ;
		$reply['wind_velocity'] = 0 + $matches[1] ;
		$reply['wind_gust'] = 0 + $matches[2] ;
	} elseif ($tokens[$index] == '//////KT') {
		$reply['wind'] = $tokens[$index++] ;
	} elseif ($tokens[$index] == '/////KT') {
		$reply['wind'] = $tokens[$index++] ;
	} else
		$reply['error'] .= " Cannot parse the wind $tokens[$index] " ;
	if (preg_match('@^(\d+)V(\d+)$@', $tokens[$index], $matches)) { // If wind drection is variable
		$reply['wind'] .= ' ' . $tokens[$index++] ; 
		$reply['wind_vrb_from'] = $matches[1] ;
		$reply['wind_vrb_to'] = $matches[2] ;
	}

// VISIBILITY
	if ($tokens[$index] == 'CAVOK') {
		$reply['visibility'] = 10000 ;
		$reply['type'] = 'CAVOK' ;
		$reply['cloud'] = '' ;
		$reply['ceiling'] = 5000 ;
		$reply['aero_ceiling'] = 5000 ;
		$index++ ;
	} else {
		if (preg_match('@^(\d+)SM$@', $tokens[$index], $matches)) { // US uses statute miles rather than feet
			$reply['visibility'] = 5280 * $matches[1] ;
			$index ++ ;
		} elseif (preg_match('@^(\d+)NDV$@', $tokens[$index], $matches)) { // Non Directional Visibility
			$reply['visibility'] = $matches[1] ;
			$index ++ ;
		} elseif (preg_match('@^(\d+)$@', $tokens[$index])) { // Normal visibility: only digits
			$reply['visibility'] = $tokens[$index++] ;
		} elseif ($tokens[$index] == '////') { // Automatic station has no measurement
			$reply['visibility'] = '?' ;
			$index++ ;
		} else	{
			$reply['error'] .= "Cannot parse the visibility $tokens[$index] " ;
			$reply['visibility'] = '?' ;
		}
		$reply['type'] = '' ;
		$reply['cloud'] = '' ;
		$reply['ceiling'] = '' ;
		$reply['aero_ceiling'] = '' ;
	}

// REST
	while ($index < count($tokens)) {
		if ($tokens[$index] == 'SKC' or $tokens[$index] == 'NCD') { // Sky Clear or No Cloud Detected -- the latter for automated stations
			$reply['ceiling'] = 999999 ;
			$reply['aero_ceiling'] = 999999 ;
			$reply['cloud'] = $tokens[$index] ;
		} elseif ($tokens[$index] == 'NSC') { // No significant clouds below 5000
			$reply['ceiling'] = 5000 ;
			$reply['aero_ceiling'] = 5000 ;
			$reply['cloud'] = $tokens[$index] ;
		} elseif ($tokens[$index] == 'VV///') { // No significant clouds below 5000
			$reply['ceiling'] = '?' ;
			$reply['aero_ceiling'] = '?' ;
			$reply['cloud'] = $tokens[$index] ;
		} elseif (preg_match('@^FEW(\d\d\d)/*$@', $tokens[$index], $matches)) {
			if ($reply['ceiling'] == '' or $reply['ceiling'] > 100 * $matches[1]) $reply['ceiling'] = 100 * $matches[1]  ;
			$reply['cloud'] .= $tokens[$index] . ' ' ;
		} elseif (preg_match('@^SCT(\d\d\d)/*$@', $tokens[$index], $matches)) {
			if ($reply['ceiling'] == '' or $reply['ceiling'] > 100 * $matches[1]) $reply['ceiling'] = 100 * $matches[1] ;
			if ($reply['aero_ceiling'] == '' or $reply['aero_ceiling'] > 100 * $matches[1]) $reply['aero_ceiling'] = 100 * $matches[1] ;
			$reply['cloud'] .= $tokens[$index] . ' ' ;
		} elseif (preg_match('@^BKN(\d\d\d)/*$@', $tokens[$index], $matches)) {
			if ($reply['ceiling'] == '' or $reply['ceiling'] > 100 * $matches[1]) $reply['ceiling'] = 100 * $matches[1] ;
			if ($reply['aero_ceiling'] == '' or $reply['aero_ceiling'] > 100 * $matches[1]) $reply['aero_ceiling'] = 100 * $matches[1] ;
			$reply['cloud'] .= $tokens[$index] . ' ' ;
		} elseif (preg_match('@^OVC(\d\d\d)/*$@', $tokens[$index], $matches)) {
			if ($reply['ceiling'] == '' or $reply['ceiling'] > 100 * $matches[1]) $reply['ceiling'] = 100 * $matches[1] ;
			if ($reply['aero_ceiling'] == '' or $reply['aero_ceiling'] > 100 * $matches[1]) $reply['aero_ceiling'] = 100 * $matches[1] ;
			$reply['cloud'] .= $tokens[$index] . ' ' ;
		} elseif (preg_match('@^(M?\d+)/(M?\d+)$@', $tokens[$index], $matches)) {
			$reply['temperature'] = fix_temperature($matches[1]) ;
			$reply['dew_point'] = fix_temperature($matches[2]) ;
			$reply['clouds_base'] = 400 * ($reply['temperature'] - $reply['dew_point']) ;
		} elseif (preg_match('@^A(\d\d\d\d)$@', $tokens[$index], $matches)) {
			$reply['altimeter'] = 0 + $matches[1] ;
			$reply['QNH'] = round(1013.25 * $reply['altimeter'] / 2992) ;
		} elseif (preg_match('@^Q(\d\d\d\d)$@', $tokens[$index], $matches)) {
			$reply['QNH'] = 0 + $matches[1] ;
			$reply['altimeter'] = round(29.92 * $reply['QNH'] / 1013.25, 2) ;
		} elseif ($tokens[$index] == 'TEMPO' or $tokens[$index] == 'BECMG') { // all clouds after those words are handled if their are lower
			$reply['cloud'] .= $tokens[$index] . ' ' ;
		} elseif ($reply['type'] == '' and $reply['cloud'] == '') { // Else, it must be the type following immediately the visibility
			$reply['type'] = $tokens[$index] ;
		}
		$index ++ ;
	} // while parsing tokens
	
	$reply['cloud'] = trim($reply['cloud']) ; // Remove trailing spaces
	// Now that we have parse the METAR, let's process some more data
	if ($reply['aero_ceiling'] == '') $reply['aero_ceiling'] = 999999 ;
	if ($reply['ceiling'] == '') $reply['ceiling'] = 999999 ;
	if (isset($reply['temperature']) and isset($reply['QNH']) and isset($metar_altitude)) {
		require_once 'calc_da.php' ;
		$reply['formula'] = "round(compute_density_altitude($metar_altitude, $reply[temperature], $reply[dew_point], $reply[QNH])) " ;
		$reply['density_altitude'] = round(compute_density_altitude($metar_altitude, $reply['temperature'], $reply['dew_point'], $reply['QNH'])) ;
		$reply['elevation'] = $metar_altitude ;
	}
	if (isset($reply['QNH']) and isset($metar_altitude)) { // Based on http://www.experimentalaircraft.info/flight-planning/aircraft-performance-3.php
		$PLR = (isset($reply['temperature'])) ? 96.0 * ($reply['temperature'] + 273.15) / $reply['QNH'] : 27 ;
		$reply['pressure_altitude'] = round($metar_altitude + (1013.25 - $reply['QNH']) * $PLR) ;
	}
	// Check weather conditions
	if ($reply['visibility'] == '?' or $reply['ceiling'] == '?')
		$reply['condition'] = '?' ;
	elseif ($reply['visibility'] >= 9999 and $reply['aero_ceiling'] >= 1000)
		$reply['condition'] = 'VMC' ;
	elseif ($reply['visibility'] >= 1500 and $reply['aero_ceiling'] >= 500)
		$reply['condition'] = 'MMC' ;
	else
		$reply['condition'] = 'IMC' ;
	} else // no error in getting METAR
		$reply['condition'] = '?' ;

// Ugly GOTO target
emit:
 
// METAR are usually updated every 30 minutes, so, caching 3 minutes should be OK
// Prepare last-modified HTTP header based on METAR issue time (always in GMT) such as "2015/12/13 14:20"
$last_modified = strtotime($reply['datetime']) ;
header('Last-Modified: ' . date('D, d M Y H:i:s \G\M\T', $last_modified)) ;

// Let's send the data back
if (isset($_REQUEST['callback'])) {
	print($_REQUEST['callback'] . '(' . json_encode($reply) . ') ;') ;
} elseif ($format == 'json') {
	print(json_encode($reply)) ;
} elseif ($format == 'html') {
	print("<html><head><title>METAR for $metar_station</title></head><body>$reply[METAR]</body></html>") ;
}
?>