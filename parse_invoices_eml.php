<pre>
<?php
/*
   Copyright 2022 Eric Vyncke

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

// require_once 'dbi.php' ;
ini_set("auto_detect_line_endings", true); // process CR CR/LF or LF as line separator


$fileName = "/Users/evyncke/Temp/factures.eml" ;
$lines = array() ;

//$parser = mailparse_msg_parse_file('~/Temp/factures.eml') or die("Cannot read file") ;
//$parser = mailparse_msg_parse_file('factures.eml') or die("Cannot read file") ;
//print_r(mailparse_msg_get_structure($parser));
//mailparse_msg_free($parser) ;

// Read one char taking into account the read-ahead ungetChar
// Return FALSE at the end of the file
function readChar($f) {
	global $ungetChar ;

	if ($ungetChar === false)
		return fgetc($f) ;
	$tmp = $ungetChar ;
	$ungetChar = false ;
	return $tmp ;
}

function ungetChar($c) {
	global $ungetChar ;

	if ($ungetChar !== false)
		die("Cannot ungetChar twice") ;
	$ungetChar = $c ;
}

// Return a single RFC 822 header line (unfolded)
// Return FALSE at the end of the header (empty line)
function readHeaderLine($lines, &$iLine, $linesCount) {
	
	if ($iLine >= $linesCount) return false ; // end of file
	$line = $lines[$iLine++] ;
	if ($line == '') return false ; // Empty line => end of header
	while ($iLine < $linesCount and (substr($lines[$iLine], 0, 1) == ' ' or substr($lines[$iLine], 0, 1) == "\t")) {
		$line .= " " . trim($lines[$iLine]) ;
		$iLine++ ;
	}
	return $line ;
}

// Return a single RFC 822 header line (unfolded)
// Return FALSE at the end of the header (empty line)
function XreadHeaderLine($f) {
	$line = '' ;
	while (true) {
		$c = readChar($f) ;
		if ($c == "\r" or $c == "\n") { // End of line, need to check for folded line
			$nextC = readChar($f) ;
			if ($nextC == "\n" or $nextC == "\r")
				$nextC = readChar($f) ;
			if ($nextC == ' ' or $nextC == "\t") { // Need to skip leading space
				while ($nextC = readChar($f) and ($nextC == "\t" or $nextC == ' ')) ;
				$line .= " $nextC" ;
			} else { // Next line as no leading space, time to return the line
				ungetChar($nextC) ;
				return $line ;
			}
		} else { // Normal char
			$line .= $c ;
		}
	}
}

// parse the RFC 822 email headers and return an associative array (header -> value)
function parseHeaders($lines, &$iLine, $linesCount) {
	$headers = array() ;
	while ($line = readHeaderLine($lines, $iLine, $linesCount) and $line != '') {
//		print("\n\nJust read $line\n") ;
		$delimiterPos = strpos($line, ':') ;
		if ($delimiterPos === false)
			die("Invalid header line, missing a ':', in <$line>") ;
		$header = strtolower(substr($line, 0, $delimiterPos)) ;
		$value = trim(substr($line, $delimiterPos+1)) ;
		$headers[$header] = $value ;
	}
	return $headers ;
}

// Read and process one single email message
function processMessage($lines, &$iLine, $linesCount) {
	$headers = parseHeaders($lines, $iLine, $linesCount) ;
//	print_r($headers) ;
print("To: $headers[to]\n") ;
print("Subject: $headers[subject]\n") ;
print("Content-type: " . $headers['content-type'] . "\n") ;
	$contentType = $headers['content-type'] ;
	if ($contentType) {
		if (preg_match('|multipart/mixed; boundary="(\S+)"|', $contentType, $matches)) {
			$boundary = $matches[1] ;
			print("Multipart $boundary\n") ;
		}
	}
}


//$f = fopen($fileName, 'r') or die("Cannot open email archive: $fileName\n") ;

$lines = file($fileName, FILE_IGNORE_NEW_LINES) ;
$iLine = 0 ;
$linesCount = count($lines) ;

processMessage($lines, $iLine, $linesCount) ;

?>

</pre>
