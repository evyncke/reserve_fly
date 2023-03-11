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

MustBeLoggedIn() ;

if (! $userIsBoardMember) 
	journalise($userId, "F", "Vous n'avez pas le droit de consulter cette page") ; // journalise with Fatal error class also stop execution

// var_dump($_SERVER) ;
//$filePrefix = dirname($_SERVER['SCRIPT_FILENAME']) . "/invoices/" ;
$filePrefix =  "/home/spaaviat/www/resa/invoices/" ;
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

function processEmail($overview, $header) {
	global $mbox, $userId, $mysqli_link, $filePrefix, $shared_secret ;

	foreach(imap_fetchstructure($mbox, $overview->uid, FT_UID)->parts as $part_id => $part) {
		// TODO recurse if content is yet another email
		if ($part->type == TYPEAPPLICATION and $part->subtype == 'PDF') {
			if (preg_match('/Facture (\d+)/', $header->Subject, $matches)) {
				$invoiceId = $matches[1] ;
			// =?UTF-8?Q?Note_de_cr=c3=a9dit_NC_221740?=
			} else if (preg_match('/=\?UTF-8\?Q\?Note_de_cr=c3=a9dit_NC_(\d+)\?=/', $header->Subject, $matches)) {
				$invoiceId = "NC $matches[1]" ;
			} else {
				$invoiceId = substr(sha1($overview->to . $overview->date), 0, 8) ; // Hopefully a unique ID !
			}
			$outFileName = sha1($invoiceId . $shared_secret) . '.pdf' ;
			print("Saving $invoiceId of $overview->date as $outFileName for $overview->to\n") ;
			$bodyPart = imap_base64(imap_fetchbody($mbox, $overview->uid, $part_id+1, FT_UID || FT_PEEK)) ; // FT_PEEK keep unread flag ;
			if (!$bodyPart) {
				journalise($userId, 'E', "Cannot get attachemenet in email to $overview->to dated $overview->date") ;
				continue ;
			} 
			$f = fopen($filePrefix . $outFileName, 'w') ;
			if (!$f) {
				print_r(error_get_last());
				journalise($userId, "F", "Cannot open $outFileName for writing") ;
			} ;
			if (!fwrite($f, $bodyPart)){
				print_r(error_get_last());
				journalise($userId, "F", "Cannot write to $outFileName") ;
			};
			fclose($f) ;
			// Let's have SQL parsing the date as "Tue, 29 Mar 2022 18:24:22 +0200"
			mysqli_query($mysqli_link, "REPLACE INTO rapcs_bk_invoices(bki_email, bki_date, bki_id, bki_file_name) VALUES('$overview->to', DATE(STR_TO_DATE('$overview->date', '%a, %e %b %Y %H:%i:%s')), '$invoiceId', 'invoices/$outFileName')")
				or journalise($userId, "E", "Cannot insert into rapcs_bk_invoices: " . mysqli_error($mysqli_link)) ;
			journalise($userId, "I", "Invoice $invoiceId for $overview->to dated $overview->date saved as $outFileName") ;
		}
	}
}

$mbox = imap_open ("{" . $invoice_imap . ":993/imap/ssl}" . $invoice_folder, $invoice_user, $invoice_psw, OP_READONLY)
	or journalise($userId, "F", "Cannot open mailbox for $invoice_imap user $invoice_user: " . imap_last_error()) ;

// TODO: scan all folders recursively ?

/*
$folders = imap_listmailbox($mbox, "{" . $invoice_imap . ":993}" . $invoice_folder, "*");

print("<h1>imap_headers() in $invoice_folder</h1>\n") ;
foreach (imap_headers($mbox) as $val) 
        print("$val<br/>\n");

print("<h1>imap_check($invoice_folder)</h1>\n") ;
print("<pre>") ;
print_r(imap_check($mbox)) ;
print("</pre>") ;
*/

$checks = imap_check($mbox) ;
$nmsgs = $checks->Nmsgs ;
print("There are $nmsgs message(s) in the $invoice_user $invoice_folder mailbox<br>\n") ;

$first_msg = max(1, $nmsgs-200) ; // 200 invoices max... just a guess
$last_msg = $nmsgs ;

print("<h1>imap_fetch_overview($first_msg:$last_msg)</h1>\n<pre>\n") ;

$now = new DateTimeImmutable('now') ;
foreach(imap_fetch_overview($mbox, "$first_msg:$last_msg") as $overview) {
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

// Let's close nicely
imap_close($mbox) ;

// Converting email addresses...
foreach ($ciel2profiles as $ciel => $rapcs) 
        mysqli_query($mysqli_link, "UPDATE rapcs_bk_invoices SET bki_email = '$rapcs' WHERE bki_email = '$ciel'")
			or journalise($userId, "E", "Cannot update email address $ciel -> $rapcs: " . mysqli_error($mysqli_link)) ;

journalise($userId, "I", "Job done") ;
?>