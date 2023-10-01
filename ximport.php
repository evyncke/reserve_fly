<?php
/*
   Copyright 2023 Eric Vyncke - Patrick Reginster

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

$production = false ; // when production is set to true, invoices are inserted in $table_invoices and are shown to end-users

require_once 'dbi.php' ;
require_once 'invoicePDF_pilot.php';
require_once 'folio.php' ;

MustBeLoggedIn() ;

if (! $userIsAdmin && ! $userIsBoardMember)
    journalise($userId, "F", "Vous n'avez pas le droit de consulter cette page ou vous n'êtes pas connecté.") ; 

if (!(isset($_REQUEST['prefixInvoice'])))
    journalise($userId, "F", "Missing parameter: prefixInvoice = $prefixInvoice") ;
$prefixInvoice = trim($_REQUEST['prefixInvoice']) ;
$nextMove = 10001 ;
$invoiceCount = 0;
$invoiceDateCompta = date ('d/m/y') ;

journalise($userId, "I", "Invoices generation started with prefix $prefixInvoice") ;					

class XimportLine {
    public $mouvement ;
    public $journal ;
    public $date_ecriture ;
    public $date_echeance ;
    public $num_piece ;
    public $num_compte ;
    public $libelle ;
    public $montant ;
    public $type_montant ;
    public $pointage ;
    public $analytique ;
    public $tva ;
    public $code_tva ;
    public $x1 ;
    public $x2 ;
    public $x3 ;

    // Convert a data from Folio 14/12/23 to a CIel date 20231214
    private function datelog2ciel($date) {
        if ($date == '') return '        ' ;
        $tokens = explode('/', $date) ;
        return "20$tokens[2]$tokens[1]$tokens[0]" ;
    }

    public function __construct($mouvement, $journal, $date_ecriture, $date_echeance, $num_piece, $num_compte, $libelle, $montant, $type_montant, $pointage,
            $analytique, $tva, $code_tva, $x1, $x2, $x3)
    {
        $this->mouvement = $mouvement ;    
        $this->journal = $journal ;
        $this->date_ecriture = $this->datelog2ciel($date_ecriture) ;
        $this->date_echeance = $this->datelog2ciel($date_echeance) ;
        $this->num_piece = $num_piece ;
        $this->num_compte = $num_compte ;
        $this->libelle = $libelle ;
        $this->montant = $montant ;
        $this->type_montant = $type_montant ;
        $this->pointage = $pointage ;
        $this->analytique = $analytique ;
        $this->tva = $tva ;
        $this->code_tva = $code_tva ;
        $this->x1 = $x1 ;
        $this->x2 = $this->datelog2ciel($x2) ;
        $this->x3 = $x3 ;
    }

    public function __toString() {
//        print_r($this) ;
        $s = '' ;
        $s .= str_pad($this->mouvement, 5) ; // numéro de mouvement sur 5 chars
        $s .= str_pad($this->journal, 4) ; // Journal sur 4 chars cadrés à gauche
        $s .= $this->date_ecriture ; // Date d'écriture sur 8 chars
        $s .= $this->date_echeance ; // Date d'écheance sur 8 chars
        $s .= str_pad($this->num_piece, 12) ; // Numéro de pièce sur 12 chars cadrés à gauche
        $s .= str_pad($this->num_compte, 11) ; // Numéro de compte sur 11 chars cadrés à gauche
        $s .= substr(str_pad($this->libelle, 25), 0, 25) ; // Libellé 25 chars cadrés à gauche
        $s .= sprintf("%13.2F", $this->montant) ; // Montant 13 chars avec 2 décimales
        $s .= $this->type_montant ; // Type de montant 1 char
        $s .= str_pad($this->pointage, 12) ; // Pointage sur 12 chars
        $s .= str_pad($this->analytique, 6) ; // Code analytique sur 6 chars
        $s .= sprintf("%15.2F", $this->tva) ; // Montant TVA sur 15 chars
        $s .= sprintf("%5.2F", $this->code_tva) ; // Montant TVA sur 5 chars
        $s .= $this->x1 ; // Inconnu 1 sur 1 char
        $s .= str_pad($this->x2, 11, ' ', STR_PAD_LEFT) ; // Inconnu 2 sur 11 chars
        $s .= str_pad($this->x3, 3, ' ', STR_PAD_LEFT) ; // Inconnu 3 sur 3 chars
        return $s ;
    }
}

function remove_accents($text) {
	$from = explode(" ",""
		." À Á Â Ã Ä Å Ç È É Ê Ë Ì Í Î Ï Ñ Ò Ó Ô Õ Ö Ø Ù Ú Û Ü Ý à á â"
		." ã ä å ç è é ê ë ì í î ï ñ ò ó ô õ ö ø ù ú û ü ý ÿ Ā ā Ă ă Ą"
		." ą Ć ć Ĉ ĉ Ċ ċ Č č Ď ď Đ đ Ē ē Ĕ ĕ Ė ė Ę ę Ě ě Ĝ ĝ Ğ ğ Ġ ġ Ģ");
	$to = explode(" ",""
		." A A A A A A C E E E E I I I I N O O O O O O U U U U Y a a a"
		." a a a c e e e e i i i i n o o o o o o u u u u y y A a A a A"
		." a C c C c C c C c D d D d E e E e E e E e E e G g G g G g G");
	return str_replace( $from, $to, $text);
}

?><!DOCTYPE html>
<html lang="fr">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<meta charset="utf-8">
<!--meta name="viewport" content="width=320"-->
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- http://www.alsacreations.com/article/lire/1490-comprendre-le-viewport-dans-le-web-mobile.html -->
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<!-- http://www.w3schools.com/bootstrap/ -->
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script><html>
	<title>Génération des factures</title>
<!-- Matomo -->
<script type="text/javascript">
  var _paq = window._paq = window._paq || [];
  _paq.push(['setUserId', '<?=$userName?>']);
  _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
  _paq.push(["setDomains", ["*.spa-aviation.be","*.ebsp.be","*.m.ebsp.be","*.m.spa-aviation.be","*.resa.spa-aviation.be"]]);
  _paq.push(['enableHeartBeatTimer']);
  _paq.push(['setCustomVariable', 1, "userID", <?=$userId?>, "visit"]);
  _paq.push(["setCookieDomain", "*.spa-aviation.be"]);
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="//analytics.vyncke.org/";
    _paq.push(['setTrackerUrl', u+'matomo.php']);
    _paq.push(['setSiteId', '5']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Code -->
</head>
<body>
    <h1>Génération des factures et du fichier XIMPORT.TXT sur base des carnets de vol</h1>
    <p class="bg-danger">Ceci est en mode test</p>
    <p>Le fichier <a href="data/ximport.txt">ximport.txt</a> doit être copié dans le répertoire de liaison comptable.</p>
<?php
// Clean the Invoice folder
if(!$production) {
	// TODO probably useless to do in $production mode
	$invoiceFolder="data/PDFInvoices/";
	$invoiceFiles = scandir($invoiceFolder);
	foreach($invoiceFiles as $invoiceFile) {
		//print("InvoiceFile= $invoiceFile</br>");
		//if(strpos($invoiceFile,"Invoice_")==false && strpos($invoiceFile,"Invoice_")==0) {
		if(substr($invoiceFile,0,strlen($prefixInvoice))==$prefixInvoice) {
			$filePath=$invoiceFolder.$invoiceFile;
			if(file_exists($filePath)){
				//print("Deleting:  InvoiceFile= $filePath</br>");
				if(unlink($filePath)==FALSE) {
					print("Fail to delete:  InvoiceFile= $filePath</br>");				
				}
			}	
		}
	}
}

$invoiceDateTime = new DateTime(date('Y-m-d'), new DateTimeZone('UTC'));
$invoiceDate = date_format($invoiceDateTime,"d-m-Y");
$invoiceDateSQLTime = new DateTime(date('Y-m-d'), new DateTimeZone('UTC'));
$invoiceDateSQL = date_format($invoiceDateSQLTime,"Y-m-d");

$sql = "select u.id as id, last_name, bce
				from $table_users as u join $table_user_usergroup_map on u.id=user_id 
				join $table_person as p on u.id=p.jom_id
				where group_id in ($joomla_member_group, $joomla_student_group, $joomla_pilot_group, $joomla_effectif_group)
				group by last_name";
				
$result = mysqli_query($mysqli_link, $sql)
			or journalise(0, "F", "Cannot read members: " . mysqli_error($mysqli_link)) ;

$f = fopen('data/ximport.txt', 'w')
    or journalise($userId, "F", "Cannot open data/ximport.txt for writing") ;

// Eric = 62, Patrick = 66, Dominique = 348, Alain = 92, Bernard= 306,  Davin/élève 439, Gobron 198
$members = [62, 66, 348, 92] ;
//$members = [66, 348, 353, 46, 114, 181, 160, 62, 86, 402] ;

//foreach($members as $member) {
while ($row = mysqli_fetch_array($result)) {
	$member=$row['id'];	
	$bce=$row['bce'];
    $folio = new Folio($member, '2023-09-01', '2023-10-01') ;
    if ($folio->count == 0) continue ; // Skip empty folios
	$invoiceCount++ ;
	$nextInvoice=$prefixInvoice."-".str_pad($invoiceCount,4,"0",STR_PAD_LEFT);
	
	$cielCode=$folio->code_ciel;
	$cielCode400=$cielCode;
	$firstName=$folio->fname;
	$lastName=$folio->name;
	//print("cielCode=$cielCode $cielCode400</br>");
	$communication=$nextInvoice." ".$cielCode400." ".$lastName." ".$firstName;
	
    print("<h3>Facture $nextInvoice pour $lastName $firstName ($member)</h3>\n") ;
		
    $total_folio = 0 ;
	//PDF
	$pdf = new InvoicePDF('P','mm','A4');
	$pdf->SetDate($invoiceDate);
	$pdf->SetInvoiceNumber($nextInvoice);
	$pdf->SetInvoiceCommunication($communication);
	$pdf->AddPage();
	$pdf->AliasNbPages();
	$pdf->AddAddress($folio->fname." ".$folio->name, $folio->address, "$folio->zip_code $folio->city", $folio->country, $bce) ;
	//$pdf->SetXY(20, 80);
	$pdf->SetColumnsWidth(array(20, 85, 20, 25, 25),array("C","L","C","R","R")) ;
	$pdf->TableHeader(array("Référence", "Désignation", "Quantité", "Prix unitaire","Montant")) ; 	
?>
<table class="table table-striped table-responsive table-hover">
    <thead>
        <tr><th>Référence</th><th>Désignation</th><th>Quantité</th><th>Prix unitaire</th><th>Montant</th></tr>
    </thead>
    <tbody>
<?php
    foreach($folio as $line) {
		if ($line->cost_fi < 0) {
			// This is a DC flight for a FI. Line skipped. Not to be added in the invoice
			continue;
		}
 
		$shareInfo="";
        $code_plane = substr($line->plane, 3) ;
		$date=substr($line->date,6,2).substr($line->date,3,2).substr($line->date,0,2).":".substr($line->time_start,0,2).substr($line->time_start,3,2);
		$DC="";
		if($line->instructor_name!="") {
			$DC="DC";
		}
		if($line->share_type!="") {
            switch ($line->share_member) { // TOOD this part is probably not required as folio class is fixed
                case -1: $shareInfo=$line->share_type." "."Ferry"; break ; 
                case -2: $shareInfo=$line->share_type." "."Club"; break ; 
                case -3: $shareInfo=$line->share_type." "."INIT"; break ; 
                case -4: $shareInfo=$line->share_type." "."IF"; break ; 
                case -5: $shareInfo=$line->share_type." "."Membre"; break ; 
                case -6: $shareInfo=$line->share_type." "."DHF"; break ; 
                case -7: $shareInfo=$line->share_type." "."Club"; break ; 
                case -8: $shareInfo=$line->share_type." "."Mecano"; break ; 
				default: $shareInfo=$line->share_type." ".$line->share_member_name." ".$line-> share_member_fname; break;
            }
		}
		$libelle=$code_plane.$date;
		if($DC!="") {
			$libelle=$libelle.".".$DC;
		}
		if($shareInfo!="") {
			$libelle=$libelle.".".$shareInfo;
		}
		$picName="PIC ".$line->pic_name;
		if($line->pic_name=="SELF") $picName="";
		//$libelle="$line->pilot_name $line->pilot_fname";
	    if ($line->cost_plane > 0) {
            //$ximportLine = new XimportLine($nextMove, 'VEN', $line->date, '', $nextInvoice, 700100, remove_accents($libelle), $line->cost_plane, 'C', $nextInvoice,
            $ximportLine = new XimportLine($nextMove, 'VEN', $invoiceDateCompta, '', $nextInvoice, 700100, remove_accents($libelle), $line->cost_plane, 'C', $nextInvoice,
            $code_plane, 0.0, 0.0, '0', $invoiceDateCompta, 0) ;
            fprintf($f, "$ximportLine\n") ;
		}
		$costPlaneText=number_format($line->cost_plane,2,",",".")." €";
		$costPlaneMinuteText=number_format($line->cost_plane_minute,2,",",".")." €";
		$pdf->TableRow(array($line->item_plane, "$line->date $line->plane $picName $shareInfo",$line->duration, $costPlaneMinuteText, $costPlaneText));		
        print("<tr><td>$line->item_plane</td><td>$line->date $line->plane $picName $shareInfo</td><td>$line->duration</td><td>$costPlaneMinuteText</td><td>$costPlaneText</td></tr>\n") ;         

        // Special line if there are taxes
        if ($line->cost_taxes > 0) {
			$libelle=$code_plane.$date.".TaxesPass";
            //$ximportLine = new XimportLine($nextMove, 'VEN', $line->date, '', $nextInvoice, 744103, remove_accents($libelle), $line->cost_taxes, 'C', $nextInvoice,
            //    'TI', 0.0, 0.0, '0', $line->date, 0) ;
	        $ximportLine = new XimportLine($nextMove, 'VEN', $invoiceDateCompta, '', $nextInvoice, 744103, remove_accents($libelle), $line->cost_taxes, 'C', $nextInvoice,
	                'TI', 0.0, 0.0, '0', $invoiceDateCompta, 0) ;
            fprintf($f, "$ximportLine\n") ;        
			$taxPerPax=$line->cost_taxes/$line->pax_count;
			$taxPerPaxText=number_format($taxPerPax,2,",",".")." €";
			$costTaxText=number_format($line->cost_taxes,2,",",".")." €";   
            print("<tr><td>$line->item_tax</td><td>$line->date $line->plane Redevance Pax $line->from > $line->to</td><td>$line->pax_count</td><td>$taxPerPaxText</td><td>$costTaxText</td></tr>\n") ; 
			$pdf->TableRow(array($line->item_tax, "$line->date $line->plane Redevance Pax $line->from > $line->to", $line->pax_count, $taxPerPaxText, $costTaxText));
        }
        // Special line if there is an instructor
        if ($line->cost_fi > 0) {
            switch ($line->instructor_code) {
                case  46: $fi_code = 700202 ; $fi_analytique = 'EC' ; break ; // Benoît Mendes, EC pour école ?
                case  50: $fi_code = 700205 ; $fi_analytique = 'WL' ; break ; // Luc Wynand
                case  59: $fi_code = 700206 ; $fi_analytique = 'NC' ; break ; // Nicolas Claessen
                case 118: $fi_code = 700208 ; $fi_analytique = 'EC' ; break ; // David Gaspar, EC pour école ?
            }
			$DC="DC".$line->instructor_name;
			$libelle=$code_plane.$date.".".$DC;
            $ximportLine = new XimportLine($nextMove, 'VEN', $invoiceDateCompta, '', $nextInvoice, $fi_code, remove_accents($libelle), $line->cost_fi, 'C', $nextInvoice,
                $fi_analytique, 0.0, 0.0, '0', $invoiceDateCompta, 0) ;
            fprintf($f, "$ximportLine\n") ; 
			
			$costFIText=number_format($line->cost_fi,2,",",".")." €";
			$costFIMinText=number_format($cost_fi_minute,2,",",".")." €";   
            print("<tr><td>$line->item_fi</td><td>$line->date $line->plane DC $line->instructor_name </td><td>$line->duration</td><td>$costFIMinText</td><td>$costFIText</td></tr>\n") ; 
			  
			$pdf->TableRow(array($line->item_fi, "$line->date $line->plane DC $line->instructor_name",$line->duration, $costFIMinText, $costFIText));
			      
        } else 
            $fi_suffix = 0 ;
        $total_folio += $line->cost_plane + $line->cost_fi + $line->cost_taxes ;
    }
	if($total_folio > 0) {
    	// Write to the member account
		$libelle= "Fac.".$nextInvoice." ".$lastName." ".$firstName;
    	$ximportLine = new XimportLine($nextMove, 'VEN', $invoiceDateCompta, '', $nextInvoice, $cielCode400, remove_accents($libelle) , $total_folio, 'D', $nextInvoice,
       	 '', 0.0, 0.0, ' ', '', 0) ;
    	 fprintf($f, "$ximportLine\n") ;
	 }
	$totalFolioText=number_format($total_folio,2,",",".")." €";
	
    print("<tr><td></td><td></td><td></td><td><b>Total</b></td><td><b>$totalFolioText</b></td></tr>\n") ; 
	$pdf->TableTotal($totalFolioText);
	if($total_folio > 0) {
		$totalFolioText=number_format($total_folio,2,".","");
		//$communication=$nextInvoice." ".$cielCode400." ".$lastName." ".$firstName;
		$pdf->AddQRCode($totalFolioText,$communication);
	}
    $nextMove++ ;
	
    print("</tbody>\n</table>\n") ;
    if ($production) {
        // Let's generate a filename that is not guessable (so nobody can see others' invoices) but still always the same (to be idempotent)
        $invoiceFile = "invoices/" . sha1($nextInvoice . $shared_secret) . '.pdf' ;
        // TODO should use NOW() rather than $invoiceDateSQL
        mysqli_query($mysqli_link, "REPLACE 
            INTO rapcs_bk_invoices(bki_email, bki_email_sent, bki_date, bki_amount, bki_id, bki_file_name) 
            VALUES('$folio->email', NULL, '$invoiceDateSQL', $total_folio, '$nextInvoice', '$invoiceFile')")
            or journalise($userId, "E", "Cannot insert into rapcs_bk_invoices: " . mysqli_error($mysqli_link)) ;
        journalise($userId, "I", "Invoice $nextInvoice for $folio->email dated $invoiceDateSQL saved as $invoiceFile") ;
    } else {
    	$invoiceFile=$invoiceFolder.$nextInvoice."_".$lastName."_".$firstName.".pdf";
        $invoiceFile=remove_accents($invoiceFile);
    }
    $pdf->Output('F', $invoiceFile);
}

fclose($f) ;
journalise($userId, "I", "Successful termination of invoices generation") ;					
?>
</body>
</html>
