<?php
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

//ob_start("ob_gzhandler");

require_once "dbi.php" ;
require_once 'fpdf186/fpdf.php';

if (! ($userIsAdmin or $userIsInstructor or $userIsFlightPilot or $userIsFlightManager))
	die("Vous devez être pilote ou gestionnaire des vols découvertes ou instructeur ou administrateur pour utiliser cette page.") ;

class InvoicePDF extends FPDF {
// Column widths
public $column_width ;
public $column_align ;
public $column_header;
public $table_max_row;
public $table_row_count;
public $date;
public $invoiceNumber;

	// En-tête
	function Header() {
	    // Logo
	    $this->Image('../logo_rapcs_256x256.png',10,5,40);
	    $this->Ln(25);
	    $this->SetFont('Arial','',8);
		$this->SetXY(5, 45);
		$this->MulticellUtf8(50, 5, "Royal Aéro Para Club de Spa asbl\n"."Rue de la sauvenière, 122\n"."4900 Spa", 0, 'C', false);
		$this->AddDate();
		$this->AddInvoiceNumber();
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
	
	// Set InvoiceNumber
	function SetInvoiceNumber($invoiceNumber) 
	{
		$this->invoiceNumber=$invoiceNumber;
	}
	
	// Add Date
	function AddDate() 
	{
	    // Positionnement à 10 cm 
	    $this->SetXY(100,10);
	    $this->SetFont('Arial','',10);
		$this->MulticellUtf8(35, 5, "Spa le\n".$this->date, 1, 'C', false);
	}
	// Add Invoice Number
	function AddInvoiceNumber() 
	{
	    // Positionnement à 10 cm 
	    $this->SetXY(150,10);
	    $this->SetFont('Arial','',10);
		$this->MulticellUtf8(35, 5, "Facture Numéro\n".$this->invoiceNumber, 1, 'C', false);
	}
	// Add Invoice Number
	function AddAddress($name, $address, $city, $country) 
	{
	    // Positionnement à 10 cm 
		$text=web2db($name."\n".$address."\n".$city."\n".$country);
	    $this->SetXY(100,35);
	    $this->SetFont('Arial','',13);
		$this->Multicell(90, 7, $text, 1, 'L', false);
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
		$this->table_max_row=28;
		$this->table_row_count=0;
		
		$this->SetXY(15, 80);
	    // Header
		$this->SetX(15);
	    $this->SetFont('Arial','B',10);
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
	    $this->SetX(15);
		$this->SetFont('Arial','',8);
	    for($i=0; $i<count($row); $i++)
	        $this->CellUtf8($this->column_width[$i], 5, $row[$i], 1, 0, $this->column_align[$i]);
	    $this->Ln();

	}
	
	function TableTotal($totalAmount) 
	{
	    // Data
	    $this->SetX(140);
		$this->SetFont('Arial','B',10);
		$this->SetFillColor(240);
        $this->CellUtf8(50, 7, "Total : $totalAmount", 1, 0, 'C', true);
	    $this->Ln();
	}
	function AddProlog() 
	{
	    // Data
		$infoGeneral=
			"CBC IBAN BE64 7320 3842 1852 CREGBEBB"."\n".
			"Entreprise n° 0406 620 535 RPM Verviers Ecole BE.DTO.118"."\n".
			"Mobile : +32.473.531374 Mail : finances@spa-aviation.be";
		$paiementInfo="Veuillez indiquer le numéro de facture dans la communication du virement ou utiliser le QR-code à scanner dans votre app bancaire mobile.";
		$conditionGeneral=
			"Conditions générales"."\n"."\n".
			"Nos factures sont payables au comptant, sauf accord particulier. La réception de la facture sauf contestation notifiée par lettre recommandée endéans les 8 jours entraine de plein droit réception et agréation complète des services et fournitures y mentionnés. Aucune réclamation ne sera admise passé ce délai. Les prix des fournitures sont ceux en vigueur au moment de la facturation. *** Tout retard de paiement, sans qu'il soit nécessaire d'une mise en demeure, entraîne automatiquement l'interdiction de vol sur les avions du club *** une indemnité forfaitaire de retard de 10% du montant de la facture, avec un minimum de 37 € à titre de dommages et intérêts, et un intérêt de retard de 12% l'an à dater de l'envoi de la facture, conformément à l'article 1152 du Code Civil."."\n".
"De convention expresse, quels que soient les divers modes de paiement, acceptation de règlement ou lieu de livraison, seront seuls compétents les Tribunaux de Verviers. Il est stipulé que les conditions générales prévalent sur celles de nos clients.";
	    $this->SetXY(10,-55);
		$this->SetFont('Arial','',9);
        $this->cellUtf8(100, 5, "Exemption de la TVA - ASBL non assujettie", 0, 0, 'L');
	    $this->Ln();
		$this->SetFont('Arial','',9);
        $this->MulticellUtf8(100, 4, $infoGeneral, 0, 'L',false);
	    $this->Ln(2);
		$this->SetFont('Arial','',9);
        $this->MulticellUtf8(190, 3, $paiementInfo, 0, 'L',false);
	    $this->Ln(2);
		$this->SetFont('Arial','',7);
		$this->MulticellUtf8(190, 2, $conditionGeneral,0, 'L',false);
 	}
}
?>