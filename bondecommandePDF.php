<?php
/*
   Copyright 2023-2024 Eric Vyncke - Patrick Reginster

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

//ob_start("ob_gzhandler");

require_once "dbi.php" ;
require_once 'fpdf186/fpdf.php';

function PDF_createBonDeCommande($BonDeCommandelines, $expenseReport_date, $partner, &$theUploadFolder, &$theFactureMailTo)
{
	//print("<br>PDF_createBonDeCommande: PDF Version<br>");
	$BonDeCommandeFolder="uploads/bondecommande";
	$theUploadFolder=$BonDeCommandeFolder;
	$nextExpenseReport=PDF_getBonDeCommandeNumber();
	$communication="communication ???";
	$entrepriseName="";
	$bce="";
	$nextExpenseReport="BC-2025-".$nextExpenseReport;
	$facturesMail="bon_de_commande@spa-aviation.odoo.com";
	$theFactureMailTo=$facturesMail;
	$BonDeCommandeFile=$nextExpenseReport.".pdf";
	$aNewAttachedFileName="";
	//PDF
	$pdf = new BonDeCommandePDF('P','mm','A4');
	$pdf->SetDate($expenseReport_date);
	$pdf->SetExpenseReportNumber($nextExpenseReport);
	$pdf->SetExpenseReportCommunication($communication);
	$pdf->SetUploadFolder("https://www.spa-aviation.be/resa/".$BonDeCommandeFolder);
	$pdf->AddPage();
	$pdf->AliasNbPages();
	$pdf->AddAddress($partner['name'], $entrepriseName, $partner['address'], $partner['city'], $partner['country'], $bce) ;
	$pdf->SetColumnsWidth(array(20, 70, 20, 20, 20,20),array("C","L", "C","R","R","R")) ;
	$pdf->TableHeader(array("Date","Type", "Reference", "Quantité", "Prix unit.","Montant")) ; 
	$total=0;
	$BonDeCommandeSize=sizeof($BonDeCommandelines);
    for($i=0;$i<$BonDeCommandeSize;$i++) {
        $nodedefraisLine=$BonDeCommandelines[$i];
        $date=$nodedefraisLine["date"];
        $name=$nodedefraisLine["name"];
		$pos = strpos($name, " - ");
		if ($pos !== false) {
			$name= str_replace(" - ", "\n", $name);
		}
        $reference=$nodedefraisLine["reference"];
        $type=$nodedefraisLine["type"];
        $quantity=$nodedefraisLine["quantity"];
        $unitaryprice=$nodedefraisLine["unitary"];
        $montant=$nodedefraisLine["total"];
		$total+=$montant;
        //print("name=$name, type=$type, reference=$reference, quantity=$quantity, unitaryprice=$unitaryprice, montant=$montant<br>");
	
		$pdf->TableRow(array($date,$name, $reference, $quantity, number_format($unitaryprice,2,".","")."€", number_format($montant,2,".","")."€"));		
	}	

    $pdf->TableTotal(number_format($total,2,".","")."€");

    $pdf->Output('F',$BonDeCommandeFolder."/".$BonDeCommandeFile);
	
	// Send Mail to factures@spa-aviation.be
	$theMemberMail="patrick.reginster@gmail.com";
	PDF_BCD_sendMail($nextExpenseReport, $BonDeCommandeFolder."/".$BonDeCommandeFile, $aNewAttachedFileName, $facturesMail, $theMemberMail, $partner['name']);

	//print("<br>PDF_createBonDeCommande: PDF Version: End<br>");
 	return $BonDeCommandeFile;
}

// Get the next bon de commande number
function PDF_getBonDeCommandeNumber()
{
	global $userId ;

	$myfile = fopen("uploads/bondecommande/bondecommande_number.txt", "r") or journalise($userId, "F", "Unable to open file uploads/bondedodmmande/dondedommande_number.txt!");
	$number=fgets($myfile);
	fclose($myfile);
	//print("PDF_getBonDeCommandeNumber():number=$number<br>");
	$number++;
	//print("PDF_getBonDeCommandeNumber()2:number=$number<br>");
	$numberString=$number;
	if($number<10) {
		$numberString="000".$number;
	}
	else if($number<100) {
		$numberString="00".$number;
	}
	else if($number<1000) {
		$numberString="0".$number;
	}
	else {
		$numberString=$number;
	}
	$myfile = fopen("uploads/bondecommande/bondecommande_number.txt", "w") or journalise($userId, "F", "Unable to open file uploads/bondecommande/bondecommande_number.txt!");
	fwrite($myfile, $number);
	fclose($myfile);
	return $numberString;
}
// Send mail to factures@spa-aviation.com
function PDF_BCD_sendMail($theBonDeCommandeReference, $theBonDeCommandePDF, $theAttachedFileName,  $theFacturesMail, $theMemberMail, $theMemberName)
{

	//print("PDF_sendMail: $theBonDeCommandeReference $theBonDeCommandePDF, $theAttachedFileName, $theMemberMail<br>");
		//$facturesMail="bondecommande@spa-aviation.odoo.com";
	$facturesMail=$theFacturesMail;
	$replyto=$theMemberMail;
    //$mailto="factures@spa-aviation.com";
    $mailto=$facturesMail;
    $from_mail=$theMemberMail;
    $subject="Bon de commande RAPCS: $theBonDeCommandeReference";
	$fullAttachedName="https://www.spa-aviation.be/resa/uploads/BonDeCommande/".$theAttachedFileName;
    $message="Bonjour\nVeuillez trouvez ci-joint le bon de commande $theBonDeCommandeReference.\n";
	$message.="Bien à vous.\n$theMemberName\n\n"; 
	//print("PDF_sendMail: message=$message");
	if(1) {
		return PDF_BDC_sendMail( $theBonDeCommandePDF, $message, $subject, $facturesMail,$from_mail); 
	}
	return true;
}

function PDF_BDC_sendMail(
    string $fileAttachment,
    string $mailMessage ,
    string $subject     ,
    string $toAddress   ,
    string $fromMail 
): bool {
  	$fileAttachment = trim($fileAttachment);
    $from           = $fromMail;
    $pathInfo       = pathinfo($fileAttachment);
    $attachment    = chunk_split(base64_encode(file_get_contents($fileAttachment)));
    $boundary      = "PHP-mixed-".md5(time());
    $boundWithPre  = "\n--".$boundary;
    
    $headers   = "From: $from";
    $headers  .= "\nReply-To: $from";
    $headers  .= "\nContent-Type: multipart/mixed; boundary=\"".$boundary."\"";
    
    $message   = $boundWithPre;
    $message  .= "\n Content-Type: text/plain; charset=UTF-8\n";
    $message  .= "\n $mailMessage";
    
    $message .= $boundWithPre;
    $message .= "\nContent-Type: application/pdf; name=\"".$pathInfo['basename']."\"";
    $message .= "\nContent-Transfer-Encoding: base64\n";
    $message .= "\nContent-Disposition: attachment\n";
    $message .= $attachment;
    $message .= $boundWithPre."--";
    //print("sendMail: toAddress=$toAddress, <br>subject=$subject, <br>message=$message,<br>headers=$headers<br>");
    return mail($toAddress, $subject, $message, $headers);
}


class BonDeCommandePDF extends FPDF {
// Column widths
public $column_width ;
public $column_align ;
public $column_header;
public $table_max_row;
public $table_row_count;
public $date;
public $expenseReportNumber;
public $expenseReportCommunication;
public $uploadFolder;

	// En-tête
	function Header() {
	    // Logo
	    $this->Image('../logo_rapcs_256x256.png',10,5,40);
	    $this->Ln(25);
	    $this->SetFont('Arial','',8);
		$this->SetXY(5, 45);
		$this->MulticellUtf8(50, 5, "Royal Aéro Para Club de Spa asbl\n"."Rue de la sauvenière, 122\n"."4900 Spa", 0, 'C', false);
		$this->AddDate();
		$this->AddExpenseReportNumber();
	}

	// Pied de page
	function Footer() 
	{
	    // Positionnement à 1 cm du bas
	    $this->SetY(-10);
	    // Police Arial italique 8
	    $this->SetFont('Arial','I',8);
	    // Numéro de page
	    $this->Cell(0,10,'Page '.$this->PageNo(),0,0,'C');
		$this->AddProlog();
	}
	// Set Date
	function SetDate($date) 
	{
		$this->date=$date;
	}
	
	// Set expenseReportNumber
	function SetExpenseReportNumber($expenseReportNumber) 
	{
		$this->expenseReportNumber=$expenseReportNumber;
	}
	// Set expenseReportCommunication
	function SetExpenseReportCommunication($communication) 
	{
		$this->expenseReportCommunication=$communication;
	}
	// Add Date
	function AddDate() 
	{
	    // Positionnement à 10 cm 
	    $this->SetXY(100,10);
	    $this->SetFont('Arial','',10);
		$this->MulticellUtf8(35, 5, "Spa le\n".$this->date, 1, 'C', false);
	}
	// Add Expense Report Number
	function AddExpenseReportNumber() 
	{
	    // Positionnement à 10 cm 
	    $this->SetXY(150,10);
	    $this->SetFont('Arial','',10);
		$this->MulticellUtf8(35, 5, "Bon de commande\nRéférence\n".$this->expenseReportNumber, 1, 'C', false);
	}
	// Set attached folder
	function SetUploadFolder($uploadFolder) 
	{
		$this->uploadFolder=$uploadFolder;
	}
	// Add Address
	function AddAddress($name, $company, $address, $city, $country, $bce) 
	{
	    // Positionnement à 10 cm 
		if($company == '') {
			$text=$name."\n".$address."\n".$city."\n".$country."\n";
			//$text=db2web($name."\n".$address."\n".$city."\n".$country."\n");
			//$text=web2db($name."\n".$address."\n".$city."\n".$country."\n");
		}
		else {		
			$text=db2web($name."\n".$company."\n".$address."\n".$city."\n".$country."\n".$bce);			
			//$text=web2db($name."\n".$company."\n".$address."\n".$city."\n".$country."\n".$bce);			
		}
	    $this->SetXY(100,35);
	    $this->SetFont('Arial','',13);
		$this->MulticellUtf8(90, 7, $text, 1, 'L', false);
		//$this->MulticellUtf8(100, 6, $name."\n".$address."\n".$city."\n".$country, 1, 'L', false);
	}

	function MulticellUtf8($w, $h, $txt = '', $border = 0, $align = 'L', $fill = false) 
	{
		$this->Multicell($w, $h, iconv('UTF-8', 'windows-1252', $txt), $border, $align, $fill) ;
	}

	function CellUtf8($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = 'L', $fill = false, $link = NULL) 
	{
		$this->Cell($w, $h, iconv('UTF-8', 'windows-1252', $txt), $border, $ln, $align, $fill, $link) ;
	}

	function SetColumnsWidth($arraySize, $arrayAlign) 
	{
		$this->column_width = $arraySize ;
		$this->column_align = $arrayAlign ;
	}

	// Better table
	function TableHeader($header) 
	{
		$this->column_header=$header;
		$this->table_max_row=26;
		$this->table_row_count=0;
		
		$this->SetXY(10, 90);
		$this->SetFont('Arial','B',10);
		$this->CellUtf8(60, 4, "Description du bon de commande: ");
		$this->Ln();
		$this->Ln();

	    // Header
	    $this->SetFont('Arial','B',8);
	    for($i=0; $i<count($header); $i++)
	 	   $this->CellUtf8($this->column_width[$i], 7, $header[$i], 1, 0, 'C');
	 	$this->SetFont('','');
		$this->Ln();
	}

	function TableRow($row) 
	{
		$this->table_row_count++;
		//print("table_row_count=".strval($this->table_row_count)." table_max_row=".strval($this->table_max_row)."</br>");
		if($this->table_row_count > $this->table_max_row) {
			$this->addPage();
			$this->TableHeader($this->column_header);
		}
	    // Data
	    $this->SetX(10);
		$this->SetFont('Arial','',7);
	    for($i=0; $i<count($row); $i++)
	        $this->CellUtf8($this->column_width[$i], 5, $row[$i], 1, 0, $this->column_align[$i]);
		//$pdf->MultiCell( 200, 40, $reportSubtitle, 1);
	    $this->Ln();
	}
	
	function TableTotal($totalAmount) 
	{
	    // Data
		$xAlign=10+$this->column_width[0]+$this->column_width[1]+$this->column_width[2];
		$wAlign=$this->column_width[3]+$this->column_width[4]+$this->column_width[5];
	    $this->SetX($xAlign);
		$this->SetFont('Arial','B',10);
		$this->SetFillColor(240);
        $this->CellUtf8($wAlign, 7, "Total : $totalAmount", 1, 0, 'C', true);
	    $this->Ln();
	}

	function AddProlog() 
	{
	    // Data
		$infoGeneral=
			"CBC IBAN BE64 7320 3842 1852 CREGBEBB"."\n".
			"Entreprise n° 0406 620 535 RPM Verviers Ecole BE.DTO.118"."\n".
			"Mobile : +32.473.531374 Mail : finances@spa-aviation.be";
		$paiementInfo="";
		$conditionGeneral=
			"Conditions générales"."\n"."\n".
			"Nos factures sont payables au comptant, sauf accord particulier. La réception de la facture sauf contestation notifiée par lettre recommandée endéans les 8 jours entraine de plein droit réception et agréation complète des services et fournitures y mentionnés. Aucune réclamation ne sera admise passé ce délai. Les prix des fournitures sont ceux en vigueur au moment de la facturation. *** Tout retard de paiement, sans qu'il soit nécessaire d'une mise en demeure, entraîne automatiquement l'interdiction de vol sur les avions du club *** une indemnité forfaitaire de retard de 10% du montant de la facture, avec un minimum de 37 € à titre de dommages et intérêts, et un intérêt de retard de 12% l'an à dater de l'envoi de la facture, conformément à l'article 1152 du Code Civil."."\n".
			"De convention expresse, quels que soient les divers modes de paiement, acceptation de règlement ou lieu de livraison, seront seuls compétents les Tribunaux de Verviers. Il est stipulé que les conditions générales prévalent sur celles de nos clients.";
	    $this->SetXY(10,-35);
		$this->SetFont('Arial','',9);
        $this->cellUtf8(100, 5, "Exemption de la TVA - ASBL non assujettie", 0, 0, 'L');
	    $this->Ln();
		$this->SetFont('Arial','',9);
        $this->MulticellUtf8(100, 4, $infoGeneral, 0, 'L',false);
	    $this->Ln(2);
		$this->SetFont('Arial','',9);
		$this->SetTextColor(0,0,255);
        $this->MulticellUtf8(190, 3, $paiementInfo, 0, 'L',false);
 	}
}
?>