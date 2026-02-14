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

function PDF_createNoteDeFrais($notedefraislines, $theRemboursable, $expenseReport_date, $partner, $attachedFileName, &$theUploadFolder, &$theFactureMailTo)
{
	//print("<br>PDF_createNoteDeFrais: PDF Version<br>");
	$notedefraisFolder="uploads/notedefrais";
	$theUploadFolder=$notedefraisFolder;
	$nextExpenseReport=PDF_getNoteDeFraisNumber();
	$communication="communication ???";
	$entrepriseName="";
	$bce="";
	$date=date_create();
	$year=date_format($date,"Y");
	$remboursable="REMBOURSABLE SUR COMPTE PILOTE";
	$facturesMail="note_de_frais_nr@spa-aviation.odoo.com";
	if($theRemboursable==1) {
		// Note de frais remboursable
		$remboursable="REMBOURSABLE SUR COMPTE BANCAIRE";
		$nextExpenseReport="BILL-NF-".$year."-".$nextExpenseReport;
		$facturesMail="note_de_frais_r@spa-aviation.odoo.com";
	}
	else {
		$nextExpenseReport="RINV-NF-".$year."-".$nextExpenseReport;
	}
	$theFactureMailTo=$facturesMail;
	$notedefraisFile=$nextExpenseReport.".pdf";
	$aNewAttachedFileName="";
	if($attachedFileName!="") {
		$aNewAttachedFileName=$nextExpenseReport."_".$attachedFileName;
		str_replace($aNewAttachedFileName," ","");
		rename($notedefraisFolder."/".$attachedFileName,$notedefraisFolder."/".$aNewAttachedFileName);
	}
	//PDF
	$pdf = new notedefraisPDF('P','mm','A4');
	$pdf->SetDate($expenseReport_date);
	$pdf->SetExpenseReportNumber($nextExpenseReport);
	$pdf->SetExpenseReportCommunication($communication);
	$pdf->SetAttachedFile($aNewAttachedFileName);
	$pdf->SetUploadFolder("https://www.spa-aviation.be/resa/".$notedefraisFolder);
	$pdf->SetRemboursable($remboursable);
	$pdf->AddPage();
	$pdf->AliasNbPages();
	$pdf->AddAddress($partner['name'], $entrepriseName, $partner['address'], $partner['city'], $partner['country'], $bce) ;
	$pdf->SetColumnsWidth(array(12, 25, 70, 15, 15, 15,15,15),array("C","C", "L","C","R","R","C","C")) ;
	$pdf->TableHeader(array("Date","Type", "Description", "Quantité", "Prix unit.","Montant","Imput.","Analyt.")) ; 
	$count=-1;
	$total=0;
	$notedefraisSize=sizeof($notedefraislines);
    for($i=0;$i<$notedefraisSize;$i++) {
        $nodedefraisLine=$notedefraislines[$i];
        $date=$nodedefraisLine["date"];
        $name=$nodedefraisLine["name"];
		$pos = strpos($name, " - ");
		if ($pos !== false) {
			$name= str_replace(" - ", "\n", $name);
		}
        $description=$nodedefraisLine["description"];
        $type=$nodedefraisLine["type"];
        $quantity=$nodedefraisLine["quantity"];
        $unitaryprice=$nodedefraisLine["unitary"];
        $montant=$nodedefraisLine["total"];
		$total+=$montant;
        $odooreference=$nodedefraisLine["odoo"];
        $odooanalytic=$nodedefraisLine["analytic"];
        //print("name=$name, type=$type, description=$description, quantity=$quantity, unitaryprice=$unitaryprice, montant=$montant, odooreference=$odooreference, odooanalytic=$odooanalytic<br>");
	
		$pdf->TableRow(array($date,$name, $description, $quantity, number_format($unitaryprice,2,".","")."€", number_format($montant,2,".","")."€", $odooreference, $odooanalytic));		
	}	

    $pdf->TableTotal(number_format($total,2,".","")."€");
	$pdf->AddAttachedFile();

    PDF_AddAttchedPicture($pdf, $notedefraisFolder, $aNewAttachedFileName);


    $pdf->Output('F',$notedefraisFolder."/".$notedefraisFile);
	
	PDF_MergeAttachedPDFFile($notedefraisFolder, $notedefraisFile, $aNewAttachedFileName);
	
	// Send Mail to factures@spa-aviation.be
	$theMemberMail="patrick.reginster@gmail.com";
	PDF_sendMail($nextExpenseReport, $notedefraisFolder."/".$notedefraisFile, $aNewAttachedFileName, $facturesMail, $theMemberMail, $partner['name']);

	//print("<br>PDF_createNoteDeFrais: PDF Version: End<br>");
 	return $notedefraisFile;
}

// add a page in the PDF File with a picture
function PDF_AddAttchedPicture($pdf, $notedefraisFolder, $aNewAttachedFileName)
{
	//print("PDF_AddAttchedPicture:  $notedefraisFolder, $aNewAttachedFileName <br>");
	$file=$notedefraisFolder."/".$aNewAttachedFileName;
	$image_filetype = pathinfo($file, PATHINFO_EXTENSION) ;

	if(IsPictureFile($image_filetype)) {
		$image_size = getimagesize($file) ;
		if ($image_size === FALSE) {
			// The file is not a picture
			return;
		}
		$image_width = $image_size[0] ;
		$image_height = $image_size[1] ;
		$image_type = $image_size[2] ;

		// Insert a logo in the top-left corner at 300 dpi
		$pdf->addPage();
	    // Positionnement à 2-50 cm 
		$pdf->SetXY(20,60);
	    $pdf->SetFont('Arial','',10);
		if($file!="") {
			$pdf->CellUtf8(100, 5, "Fichier attaché: "."http://www.spa-aviation.be/resa/".$file);
		}
		else {
			$pdf->CellUtf8(35, 5, "Pas de fichier attaché.", 0, 'L', false);
		}

		if($image_width > $image_height) {
			$pdf->Image($notedefraisFolder."/".$aNewAttachedFileName, 20, 66, 170, 0);
		}
		else {
			$pdf->Image($notedefraisFolder."/".$aNewAttachedFileName, 20, 66, 0, 180);
		}
	}
}

// Merge the report pdf file with the pdf attached file.
function PDF_MergeAttachedPDFFile($notedefraisFolder, $theNotedefraisPDFFile, $theAttachedPDFFileName)
{
	//print("PDF_MergeAttachedPDFFile($notedefraisFolder, $theNotedefraisPDFFile, $theAttachedPDFFileName)<br>");
	$file2=$notedefraisFolder."/".$theAttachedPDFFileName;
	$image_filetype = pathinfo($file2, PATHINFO_EXTENSION) ;
	if(strtolower($image_filetype)=="pdf") {
		if(1) {
			$file1=$notedefraisFolder."/".$theNotedefraisPDFFile;
			require_once('FPDI-2.6.3/src/autoload.php');

			$pdf1 = new \setasign\Fpdi\Fpdi();
			$files = array($file1, $file2);
			foreach ($files as $file) {

				$pageCount = $pdf1->setSourceFile($file);

				for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
					$pageId = $pdf1->importPage($pageNo);
					$s = $pdf1->getTemplateSize($pageId);
					$pdf1->AddPage($s['orientation'], $s);
					$pdf1->useImportedPage($pageId);
				}
			}
			$pdf1->Output('F', $file1);
		}
	}

	return true;}


// Get the next note de frais number
function PDF_getNoteDeFraisNumber()
{
	global $userId ;
	$myfile = fopen("uploads/notedefrais/notedefrais_number.txt", "r") or journalise($userId, "F", "Unable to open file uploads/notedefrais/notedefrais_number.txt!");
	$number=fgets($myfile);
	fclose($myfile);
	//print("PDF_getNoteDeFraisNumber():number=$number<br>");
	$number++;
	//print("PDF_getNoteDeFraisNumber()2:number=$number<br>");
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
	$myfile = fopen("uploads/notedefrais/notedefrais_number.txt", "w") or journalise($userId, "F", "Unable to open file uploads/notedefrais/notedefrais_number.txt!");
	fwrite($myfile, $number);
	fclose($myfile);
	return $numberString;
}
// Send mail to factures@spa-aviation.com
function PDF_sendMail($theNoteDeFraisReference, $theNoteDeFraisPDF, $theAttachedFileName,  $theFacturesMail, $theMemberMail, $theMemberName)
{

	//print("PDF_sendMail: $theNoteDeFraisReference $theNoteDeFraisPDF, $theAttachedFileName, $theMemberMail<br>");
		//Si non remboursable -> dans le dossier odoo "note de frais non remboursable"
		//$facturesMail="note_de_frais_nr@spa-aviation.odoo.com";
		//Si remboursable -> le dossier odoo "note de frais remboursable"
		//$facturesMail="note_de_frais_r@spa-aviation.odoo.com";
	$facturesMail=$theFacturesMail;
	//Si remboursable
	$replyto=$theMemberMail;
    //$mailto="factures@spa-aviation.com";
    $mailto=$facturesMail;
    $from_mail=$theMemberMail;
    $subject="Note de frais RAPCS: $theNoteDeFraisReference";
	$fullAttachedName="https://www.spa-aviation.be/resa/uploads/notedefrais/".$theAttachedFileName;
    $message="Bonjour\nVeuillez trouvez ci-joint la note de frais $theNoteDeFraisReference.\n";
	if($theAttachedFileName!="") {
		//$message.="Justificatif associé à la note de frais: ".$fullAttachedName."\n"; 
	}
	else {
		//$message.="Pas de Justificatiff associé à la note de frais\n"; 

	}
	$message.="Bien à vous.\n$theMemberName\n\n"; 
	//print("PDF_sendMail: message=$message");
	if(1) {
		return sendMail( $theNoteDeFraisPDF, $message, $subject, $facturesMail,$from_mail); 
	}
	return true;
}

function sendMail(
    string $fileAttachment,
    string $mailMessage ,
    string $subject     ,
    string $toAddress   ,
    string $fromMail 
): bool {
  	$fileAttachment = trim($fileAttachment);
    $from           = $fromMail;
    $pathInfo       = pathinfo($fileAttachment);
    $attchmentName  = "attachment_".date("YmdHms").(
    (isset($pathInfo['extension']))? ".".$pathInfo['extension'] : ""
    );
    
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

function sendMailBackup(
    string $fileAttachment,
    string $mailMessage ,
    string $subject     ,
    string $toAddress   ,
    string $fromMail 
): bool {
    
    $fileAttachment = trim($fileAttachment);
    $from           = $fromMail;
    $pathInfo       = pathinfo($fileAttachment);
    $attchmentName  = "attachment_".date("YmdHms").(
    (isset($pathInfo['extension']))? ".".$pathInfo['extension'] : ""
    );
    
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
    $message .= "\nContent-Type: application/octet-stream; name=\"".$attchmentName."\"";
    $message .= "\nContent-Transfer-Encoding: base64\n";
    $message .= "\nContent-Disposition: attachment\n";
    $message .= $attachment;
    $message .= $boundWithPre."--";
    //print("sendMailBackup: toAddress=$toAddress, <br>subject=$subject, <br>message=$message,<br>headers=$headers<br>");
    return mail($toAddress, $subject, $message, $headers);
}
class notedefraisPDF extends FPDF {
// Column widths
public $column_width ;
public $column_align ;
public $column_header;
public $table_max_row;
public $table_row_count;
public $date;
public $expenseReportNumber;
public $expenseReportCommunication;
public $attachedFile;
public $uploadFolder;
public $remboursable;

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
	// Set attached fike
	function SetAttachedFile($attachedFile) 
	{
		$this->attachedFile=$attachedFile;
	}
	// Set attached fike
	function SetUploadFolder($uploadFolder) 
	{
		$this->uploadFolder=$uploadFolder;
	}
	// Set remboursable
	function SetRemboursable($remboursable) 
	{
		$this->remboursable=$remboursable;
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
		$this->MulticellUtf8(35, 5, "Note de frais\nRéférence\n".$this->expenseReportNumber, 1, 'C', false);
	}
	// Add AddAttachedFile
	function AddAttachedFile() 
	{
		$url=$this->uploadFolder."/".$this->attachedFile;
	    // Positionnement à 2-50 cm 
		$this->Ln();
		$this->SetX(10);
	    $this->SetFont('Arial','',10);
		if($this->attachedFile!="") {
			$this->CellUtf8(100, 5, "Fichier attaché: ".$this->uploadFolder."/".$this->attachedFile, 0, 'L', false, $url);
		}
		else {
			$this->CellUtf8(35, 5, "Pas de fichier attaché.", 0, 'L', false);
		}
		//print("AddAttachedFile: URL=$url<br>");
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
		$this->CellUtf8(60, 4, "Description de la note de frais: ".$this->remboursable);
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
		//$this->SetTextColor(0,0,0);
	    //$this->Ln(2);
		//$this->SetFont('Arial','',7);
		//$this->MulticellUtf8(190, 2, $conditionGeneral,0, 'L',false);
 	}
}
?>