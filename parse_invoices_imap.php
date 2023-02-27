<?php
/*
   Copyright 2022-2023 Eric Vyncke

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

require_once 'dbi.php' ;

ini_set("auto_detect_line_endings", true); // process CR CR/LF or LF as line separator

$filePrefix = "invoices/" ;
$shared_secret = "124.75=EBSP" ;

// Ciel invoices are sent to different email addresses for those people...
$ciel2profiles = array( // Invoice email => web site email 
	'xavier@ffxconstruction.be' => 'xh@ffx-toiture.be',
	'wynands.l@yahoo.com' => 'flyingluc@gmail.com',
	'charlysix@skynet.be' => 'charlysix@gmail.com',
	'dakota77vfr@gmail.com' => 'mario.barp@telenet.be',
	'flaviengrandjean@hotmail.com' => 'grandjeanflavien@gmail.com',
	'jhu@live.be' => 'eg.lukeschova@gmail.com',
	'michel.mos@skynet.be' => 'mickey.moes@gmail.com',
	'eg.lukeschova@gmail.com' => 'jhu@live.be',
	'lionel.martin@skynet.be' => 'lm-architecte@outlook.com'
) ;

$lines = array() ;
$lastTo = false ;
$lastSubject = false ;
$lastDate = false ;

// Return a single RFC 822 header line (unfolded)
// Return FALSE at the end of the header (empty line)
//Unused with IMAP folders
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
// Unused with IMAP folders
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
function processPDF($lines, &$iLine, $linesCount, $MIMEEncoding, $invoiceId, $outFileName) {
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
	fwrite($sqlFile, "REPLACE INTO rapcs_bk_invoices(bki_email, bki_date, bki_id, bki_file_name) VALUES ('$lastTo', DATE(STR_TO_DATE('$lastDate', '%a, %e %b %Y %H:%i:%s')), '$invoiceId', '$outFileName');\n") ;
}

// Read and process one single email message
function processMessage($lines, &$iLine, $linesCount) {
	global $lastTo, $lastSubject, $lastDate, $filePrefix, $shared_secret ;
	$contentType = false ;
	if ($contentType) {
		$semicolonPos = strpos($contentType, ';') ;
		if ($semicolonPos !== false)
			$contentMIMEType = substr($contentType, 0, $semicolonPos) ;
		else
			$contentMIMEType = $contentType ;
		switch ($contentMIMEType) {
			case 'multipart/alternative': // It should include HTML and plain text subparts
				if (preg_match('|multipart/alternative; boundary="(\S+)"|', $contentType, $matches)) {
					$boundary = $matches[1] ;
					processMultipart($lines, $iLine, $linesCount, $boundary) ;
				} ;
				break ;
			case 'multipart/mixed':
				if (preg_match('|multipart/mixed; boundary="(\S+)"|', $contentType, $matches)) {
					$boundary = $matches[1] ;
					processMultipart($lines, $iLine, $linesCount, $boundary) ;
				} ;
				break ;
			case 'message/rfc822':
				processMessage($lines, $iLine, $linesCount) ;
				break ;
			case 'text/html':
			case 'text/plain':
				break ;
			case 'application/pdf':
				if (preg_match('/Facture (\d+)/', $lastSubject, $matches)) {
					$invoiceId = $matches[1] ;
				// =?UTF-8?Q?Note_de_cr=c3=a9dit_NC_221740?=
				} else if (preg_match('/=\?UTF-8\?Q\?Note_de_cr=c3=a9dit_NC_(\d+)\?=/', $lastSubject, $matches)) {
					$invoiceId = "NC $matches[1]" ;
				} else {
					$invoiceId = substr(sha1($lastTo . $lastDate), 0, 8) ; // Hopefully a unique ID !
				}
				$outFileName = $filePrefix . sha1($invoiceId . $shared_secret) . '.pdf' ;
				processPDF($lines, $iLine, $linesCount, $headers['content-transfer-encoding'], $invoiceId, $outFileName) ;
				break ;
			default: die("Unsupported MIME type: $contentMIMEType") ;
		}
	}
}

function processEmail($overview, $header) {
	global $mbox ;
	global $lines, $iLine, $linesCount, $lastTo, $lastSubject, $lastDate ;

	var_dump(imap_fetchstructure($mbox, $overview->msgno)) ;
	foreach(imap_fetchstructure($mbox, $overview->msgno)->parts as $part_id => $part) {
		var_dump($part) ;
		if ($part->type != TYPEAPPLICATION or $part->subtype != 'PDF') continue ;
		print("Part #$part_id is interesting\n") ;
		var_dump(imap_fetchbody($mbox, $overview->msgno, $part_id+1, FT_PEEK)) ; 
	}
	exit ;
	$lines = imap_body($mbox, $overview->uid, FT_PEEK || FT_UID) ; // FT_PEEK keep unread flag ;
	var_dump($lines) ; exit ;
	$iLine = 0 ;
	$linesCount = count($lines) ;
	$lastTo = $header->toaddress ;
	$lastSubject = $header->Subject ;
	$lastDate = $header->Date ;
	processMessage($lines, $iLine, $linesCount) ;
}

//$sqlFile = fopen($filePrefix . "inject.sql", 'w') ;
// Converting email addresses...
//foreach ($ciel2profiles as $ciel => $rapcs) 
//	fwrite($sqlFile, "UPDATE rapcs_bk_invoices SET bki_email = '$rapcs' WHERE bki_email = '$ciel';\n") ;
//	
//fclose($sqlFile) ;

$mbox = imap_open ("{" . $invoice_imap . ":993/imap/ssl}" . $invoice_folder, $invoice_user, $invoice_psw, OP_READONLY)
	or journalise($userId, "F", "Cannot open mailbox for $invoice_imap user $invoice_user: " . imap_last_error()) ;

$folders = imap_listmailbox($mbox, "{" . $invoice_imap . ":993}" . $invoice_folder, "*");

print("<h1>imap_headers() in $invoice_folder</h1>\n") ;
foreach (imap_headers($mbox) as $val) 
        print("$val<br/>\n");

/*
print("<h1>imap_check($invoice_folder)</h1>\n") ;
print("<pre>") ;
print_r(imap_check($mbox)) ;
print("</pre>") ;
*/

$checks = imap_check($mbox) ;
$nmsgs = $checks->Nmsgs ;
print("$nmsgs messages") ;

$first_msg = max(1, $nmsgs-200) ; // 200 invoices max... just a guess
$last_msg = $nmsgs ;

print("<h1>imap_fetch_overview($first_msg:$last_msg)</h1>\n<pre>\n") ;

$now = new DateTimeImmutable('now') ;
foreach(imap_fetch_overview($mbox, "$first_msg:$last_msg") as $overview) {
	print_r($overview) ;
	$date =  new DateTimeImmutable($overview->date) ;
	$diff = $date->diff($now) ;
	$days_old = $diff->days ;
	if ($days_old > 7) {
		print("Message $overview->msgno to $overview->to dated $overview->date is $days_old days old, skipping.\n") ;
		continue ;
	}
	$header = imap_headerinfo($mbox, $overview->msgno) ; // TODO use UUID rather than sequence numbers... but does not see obvious...
	if (!str_starts_with($header->Subject, 'Facture ') and !str_starts_with($header->Subject, '=?UTF-8?Q?Note_de_cr=c3=a9dit')) {
		print("Message $overview->msgno to $overview->to dated $overview->date about $header->Subject is not interesting, skipping.\n") ;
		continue ;
	}
	processEmail($overview, $header) ;
}

print("</pre>\n") ;

// Let's close nicely
imap_close($mbox) ;

?>