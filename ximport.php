<?php
/*
   Copyright 2023 Eric Vyncke

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
require_once 'facebook.php' ;
require_once 'invoicePDF_pilot.php';
require_once 'folio.php' ;

MustBeLoggedIn() ;

if (! $userIsAdmin && ! $userIsBoardMember)
    journalise($userId, "F", "Vous n'avez pas le droit de consulter cette page ou vous n'êtes pas connecté.") ; 

//if (!(isset($_REQUEST['nextMove']) and is_numeric($_REQUEST['nextMove'])))
//    journalise($userId, "F", "Invalid parameter: nextMove = $nextMove") ;
$nextMove = 10001 ;
if (!(isset($_REQUEST['prefixInvoice'])))
    journalise($userId, "F", "Invalid parameter: prefixInvoice = $prefixInvoice") ;
$prefixInvoice = $_REQUEST['prefixInvoice'] ;
$invoiceCount = 0;

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
</head>
<body>
    <h1>Génération des factures et du fichier XIMPORT.TXT sur base des carnets de vol</h1>
    <p class="bg-danger">Ceci est en mode test</p>
    <p>Le fichier <a href="data/ximport.txt">ximport.txt</a> doit être copié dans le répertoire de liaison comptable.</p>
<?php
// Clean the Invoice folder
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


$sql = "select u.id as id
		from $table_users as u join $table_user_usergroup_map on u.id=user_id 
		join $table_person as p on u.id=p.jom_id
		where group_id in ($joomla_member_group, $joomla_student_group, $joomla_pilot_group, $joomla_effectif_group)
		group by user_id";
//print($sql."</br>");
$result = mysqli_query($mysqli_link, $sql)
			or journalise(0, "F", "Cannot read members: " . mysqli_error($mysqli_link)) ;
$members=array();


$f = fopen('data/ximport.txt', 'w')
    or journalise($userId, "F", "Cannot open data/ximport.txt for writing") ;

// Eric = 62, Patrick = 66, Dominique = 348, Alain = 92, Bernard= 306,  Davin/élève 439, Gobron 198
//$members = [62, 66, 92, 198, 306, 348, 439] ;
$members = [66, 348, 353, 46, 114, 181, 160, 62, 86] ;

//foreach($members as $member) {
while ($row = mysqli_fetch_array($result)) {
	$member=$row['id'];
	//print("member=$member</br>");
	
	/*
	$sql = "select jom_id, ciel_code, ciel_code400, last_name, first_name
			from $table_person 
			where jom_id= $member";
	//print("sql=$sql</br>");
	$result = mysqli_query($mysqli_link, $sql)
		or journalise(0, "F", "Cannot read members: " . mysqli_error($mysqli_link)) ;
    $row = mysqli_fetch_array($result);
	$cielCode=$row['ciel_code'];
	$cielCode400=$row['ciel_code400'];
	$firstName=db2web($row['first_name']);
	$lastName=db2web($row['last_name']);
	//print("cielCode=$cielCode</br>");
	*/
	
    $folio = new Folio($member, '2023-08-01', '2023-08-31') ;
    if ($folio->count == 0) continue ;
	//("1 $folio->count $folio->pilot  $folio->start_date  $folio->end_date   $folio->count  $folio->fname  $folio->name  $folio->email  $folio->address  $folio->zip_code  $folio->city  $folio->country  $folio->code_ciel  </br>");
	$invoiceCount++ ;
	$nextInvoice=$prefixInvoice."-".str_pad($invoiceCount,4,"0",STR_PAD_LEFT);
	
	$cielCode=$folio->code_ciel;
	$cielCode400=$cielCode;
	$firstName=$folio->fname;
	$lastName=$folio->name;
	//print("cielCode=$cielCode $cielCode400</br>");
	
    print("<h3>Facture $nextInvoice pour $firstName $lastName</h3>\n") ;
	
	//print("1 $folio->pilot  $folio->start_date  $folio->end_date   $folio->count  $folio->fname  $folio->name  $folio->email  $folio->address  $folio->zip_code  $folio->city  $folio->country  $folio->code_ciel  </br>");
    //private $result ;
    //private $row ;
	
	
    $total_folio = 0 ;
	//PDF
	$pdf = new InvoicePDF('P','mm','A4');
	$pdf->SetDate("31-08-2023");
	$pdf->SetInvoiceNumber($nextInvoice);
	$pdf->AddPage();
	$pdf->AliasNbPages();
	//$pdf->AddDate("31-08-2023");
	//$pdf->AddInvoiceNumber($nextInvoice);
	//print("AddAddress($folio->fname.\" \".$folio->name, $folio->address, \"$folio->zip_code $folio->city\", $folio->country)</br>");
	$pdf->AddAddress($folio->fname." ".$folio->name, $folio->address, "$folio->zip_code $folio->city", $folio->country) ;
	//$pdf->SetXY(20, 80);
	$pdf->SetColumnsWidth(array(20, 85, 20, 25, 25),array("C","L","C","R","R")) ;
	$pdf->TableHeader(array("Référence", "Désignation", "Quantité", "Prix unitaire","Montant")) ; 
	//print("2 $folio->pilot  $folio->start_date  $folio->end_date   $folio->count  $folio->fname  $folio->name  $folio->email  $folio->address  $folio->zip_code  $folio->city  $folio->country  $folio->code_ciel  </br>");
	
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
            switch ($line->share_member) {
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
            $ximportLine = new XimportLine($nextMove, 'VEN', $line->date, '', $nextInvoice, 700100, remove_accents($libelle), $line->cost_plane, 'C', $nextInvoice,
            $code_plane, 0.0, 0.0, '0', $line->date, 0) ;
            fprintf($f, "$ximportLine\n") ;
		}
		$costPlaneText=number_format($line->cost_plane,2,",",".")." €";
		$costPlaneMinuteText=number_format($line->cost_plane_minute,2,",",".")." €";
		$pdf->TableRow(array($line->item_plane, "$line->date $line->plane $picName $shareInfo",$line->duration, $costPlaneMinuteText, $costPlaneText));		
        print("<tr><td>$line->item_plane</td><td>$line->date $line->plane $picName $shareInfo</td><td>$line->duration</td><td>$costPlaneMinuteText</td><td>$costPlaneText</td></tr>\n") ;         

        // Special line if there are taxes
        if ($line->cost_taxes > 0) {
			$libelle=$code_plane.$date.".TaxesPass";
            $ximportLine = new XimportLine($nextMove, 'VEN', $line->date, '', $nextInvoice, 744103, remove_accents($libelle), $line->cost_taxes, 'C', $nextInvoice,
                'TI', 0.0, 0.0, '0', $line->date, 0) ;
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
            $ximportLine = new XimportLine($nextMove, 'VEN', $line->date, '', $nextInvoice, $fi_code, remove_accents($libelle), $line->cost_fi, 'C', $nextInvoice,
                $fi_analytique, 0.0, 0.0, '0', $line->date, 0) ;
            fprintf($f, "$ximportLine\n") ; 
			
			$costFIText=number_format($line->cost_fi,2,",",".")." €";
			$costFIMinText=number_format($cost_fi_minute,2,",",".")." €";   
            print("<tr><td>$line->item_fi</td><td>$line->date $line->plane DC $line->instructor_name </td><td>$line->duration</td><td>$costFIMinText</td><td>$costFIText</td></tr>\n") ; 
			  
			$pdf->TableRow(array($line->item_fi, "$line->date $line->plane DC $line->instructor_name",$line->duration, $costFIMinText, $costFIText));
			      
        } else 
            $fi_suffix = 0 ;
        $total_folio += $line->cost_plane + $line->cost_fi + $line->cost_taxes ;
    }
    // Write to the member account
	$libelle= "Fac.".$nextInvoice." ".$lastName." ".$firstName;
    $ximportLine = new XimportLine($nextMove, 'VEN', $line->date, '', $nextInvoice, $cielCode400, remove_accents($libelle) , $total_folio, 'D', $nextInvoice,
        '', 0.0, 0.0, ' ', '', 0) ;
    fprintf($f, "$ximportLine\n") ;
	
	$totalFolioText=number_format($total_folio,2,",",".")." €";
	
    print("<tr><td></td><td></td><td></td><td><b>Total</b></td><td><b>$totalFolioText</b></td></tr>\n") ; 
	$pdf->TableTotal($totalFolioText);
	
    $nextMove++ ;
    //$nextInvoice++ ;
	
    print("</tbody>\n</table>\n") ;
	$invoiceFile=$invoiceFolder.$nextInvoice."_".$lastName."_".$firstName.".pdf";
	$invoiceFile=remove_accents($invoiceFile);
	$pdf->Output('F', $invoiceFile);
}

fclose($f) ;
					
?>
<h2>Paramètres à injecter dans Ciel</h2>
<p>Voici les paramètres à modifier dans <i>Ciel Premium Account</i>:
    <ul>
        <li>Numéro de facture suivant: <?=$nextInvoice?></li>
        <li>Numéro de mouvement suivant: <?=$nextMove?></li>
    </ul>
</p>
</body>
</html>
