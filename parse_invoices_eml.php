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
$filePrefix = "invoices/" ;
$lines = array() ;
$lastTo = false ;
$lastSubject = false ;
$lastDate = false ;

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

// parse the RFC 822 email headers and return an associative array (header -> value)
function parseHeaders($lines, &$iLine, $linesCount) {
	$headers = array() ;
	while ($line = readHeaderLine($lines, $iLine, $linesCount) and $line != '') {
		$delimiterPos = strpos($line, ':') ;
		if ($delimiterPos === false)
			die("Invalid header line, missing a ':', in <$line>") ;
		$header = strtolower(substr($line, 0, $delimiterPos)) ;
		$value = trim(substr($line, $delimiterPos+1)) ;
		$headers[$header] = $value ;
	}
	return $headers ;
}

// Process a multi-part body
function processMultipart($lines, &$iLine, $linesCount, $boundary) {
	// Find the first delimiter
	$firstLine = $iLine ;
	while ($firstLine < $linesCount and ($lines[$firstLine-1] != '' or $lines[$firstLine] != "--$boundary"))
		$firstLine ++ ;
	$firstLine ++ ; // Skip the boundary itself
	$lastLine = $firstLine + 1 ; // Let's find the next delimiter
	while ($lastLine < $linesCount) {
		while ($lastLine < $linesCount and ($lines[$lastLine-1] != '' or $lines[$lastLine] != "--$boundary"))
			$lastLine ++ ;
		$lastLine ++ ; // Skip the boundary itself
		processMessage($lines, $firstLine, $lastLine - 2) ; // Need to remove the delimiter
		$firstLine = $lastLine ;
		$lastLine = $firstLine + 1 ;
	}
}

// Process a application/pdf body part and save the file in $outFileName
function processPDF($lines, &$iLine, $linesCount, $MIMEEncoding, $outFileName) {
	global $sqlFile, $lastTo, $lastDate ;

	if ($MIMEEncoding != 'base64') die("Unsupported Content-Transfer-Encoding: $MIMEEncoding for $outFileName") ;
	$encoded = '' ;
	while ($iLine < $linesCount) {
		$encoded .= "\n" . $lines[$iLine++] ;
	}
	$decoded = base64_decode($encoded) ;
	$f = fopen($outFileName, 'w') or die("Cannot open $outFileName for writing") ;
	fwrite($f, $decoded) or die("Cannot write to $outFileName") ;
	fclose($f) ;
	// Let's have SQL parsing the date as "Tue, 29 Mar 2022 18:24:22 +0200"
	fwrite($sqlFile, "REPLACE INTO rapcs_bk_invoices(bki_email, bki_date, bki_file_name) VALUES ('$lastTo', DATE(STR_TO_DATE('$lastDate', '%a, %e %b %Y %H:%i:%s')), '$outFileName');\n") ;
}

// Read and process one single email message
function processMessage($lines, &$iLine, $linesCount) {
	global $lastTo, $lastSubject, $lastDate, $filePrefix ;
	$headers = parseHeaders($lines, $iLine, $linesCount) ;
	if (isset($headers['to'])) $lastTo = $headers['to'] ;
	if (isset($headers['subject'])) $lastSubject = $headers['subject'] ;
	if (isset($headers['date'])) $lastDate = $headers['date'] ;
	$contentType = $headers['content-type'] ;
	if ($contentType) {
		$semicolonPos = strpos($contentType, ';') ;
		if ($semicolonPos !== false)
			$contentMIMEType = substr($contentType, 0, $semicolonPos) ;
		else
			$contentMIMEType = $contentType ;
		switch ($contentMIMEType) {
			case 'multipart/mixed':
				if (preg_match('|multipart/mixed; boundary="(\S+)"|', $contentType, $matches)) {
					$boundary = $matches[1] ;
					processMultipart($lines, $iLine, $linesCount, $boundary) ;
				} ;
				break ;
			case 'message/rfc822':
				processMessage($lines, $iLine, $linesCount) ;
				break ;
			case 'text/plain':
				break ;
			case 'application/pdf':
				if (preg_match('/Facture (\d+)/', $lastSubject, $matches))
					$outFileName = $filePrefix . "$matches[1].pdf" ;
				else
					$outFineName = $filePrefix . "facture.pdf" ; // Should actually use Content-Disposition: attachment; filename="Facture RAPCS.pdf"
				processPDF($lines, $iLine, $linesCount, $headers['content-transfer-encoding'], $outFileName) ;
				break ;
			default: die("Unsupported MIME type: $contentMIMEType") ;
		}
	}
}

function processFile($fileName) {
	global $lines, $iLine, $linesCount ;

	$lines = file($fileName, FILE_IGNORE_NEW_LINES) or die("Cannot read file $fileName") ;
	$iLine = 0 ;
	$linesCount = count($lines) ;
	processMessage($lines, $iLine, $linesCount) ;
}

$sqlFile = fopen($filePrefix . "inject.sql", 'a') ;

foreach (glob($filePrefix . "*.eml") as $fileName) {
	print("Processing $fileName\n") ;
	processFile($fileName) ;
}
fclose($sqlFile) ;

?>
