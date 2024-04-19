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

//ob_start("ob_gzhandler");

require_once "dbi.php" ;
require_once 'fpdf.php';

if (! ($userIsAdmin or $userIsInstructor or $userIsFlightPilot or $userIsFlightManager))
	die("Vous devez être pilote ou gestionnaire des vols découvertes ou instructeur ou administrateur pour utiliser cette page.") ;

class PDF extends FPDF {
// Column widths
public $column_width ;

// En-tête
function Header() {
	global $flight_id ;
    // Logo
    $this->Image('../logo_rapcs_256x256.png',10,6,30);
    // Police Arial gras 15
    $this->SetFont('Arial','B',15);
    // Décalage à droite
    $this->Cell(80);
    // Titre
    $this->Cell(90,10,"RAPCS ASBL, Liste des pilotes",1,0,'C');
    // Saut de ligne
    $this->Ln(30);
}

// Pied de page
function Footer() {
	global $userFullName ;
    // Positionnement à 1,5 cm du bas
    $this->SetY(-15);
    // Police Arial italique 8
    $this->SetFont('Arial','I',8);
    // Numéro de page
    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb} ' . "(Printed on " . date('Y-m-d H:i:s') . " by $userFullName)",0,0,'C');
}

function NouveauChapitre($libelle) {
	$this->AddPage();    
	// Arial 12
    $this->SetFont('Arial','',16);
    // Couleur de fond
    $this->SetFillColor(200,220,255);
    // Titre
    $this->CellUtf8(0,6, $libelle,0,1,'C',true);
    // Saut de ligne
    $this->Ln(4);
    $this->SetFont('Arial','',12);
}

function MulticellUtf8($w, $h, $txt = '', $border = 0, $align = 'L', $fill = false) {
	$this->Multicell($w, $h, iconv('UTF-8', 'windows-1252', $txt), $border, $align, $fill) ;
}

function CellUtf8($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = 'L', $fill = false, $link = NULL) {
	$this->Cell($w, $h, iconv('UTF-8', 'windows-1252', $txt), $border, $ln, $align, $fill, $link) ;
}

function SetColumnsWidth($array) {
	$this->column_width = $array ;
}

// Better table
function ImprovedTableHeader($header) {
    // Header
    
    $this->SetFont('','B');
    for($i=0; $i<count($header); $i++)
 	   $this->CellUtf8($this->column_width[$i], 7, $header[$i], 1, 0, 'C');
 	$this->SetFont('','');
	$this->Ln();
}

function ImprovedTableRow($row) {
    // Data
    for($i=0; $i<count($row); $i++)
        $this->CellUtf8($this->column_width[$i], 7, $row[$i], 1, 0, 'C');
    $this->Ln();
    // Closing line
 //   $this->CellUtf8(array_sum($this->column_width), 0, '', 'T');
}

function getSpecificPilotLicence($pilot, $rating) {
	global $mysqli_link, $table_validity_type, $table_validity ;

	$result = mysqli_query($mysqli_link, "SELECT * 
		FROM $table_validity_type t JOIN $table_validity v ON validity_type_id = t.id
		WHERE jom_id = $pilot AND name like '%$rating%'") or die("Cannot read ratings: " . mysqli_error($mysqli_link)) ;
	$row = mysqli_fetch_array($result) ;
	mysqli_free_result($result) ;
	if ($row)
		return [checked=>true, name=>$row['name'], ident=>$row['ident_value'], expiration=> $row['expire_date']] ;
	return ['name' => null, 'checked' => false] ;
}

function pilotLicence($pilot) {
	if ($pilot == '' or $pilot <= 0)
		return ['licence' => null, 'checked' => false] ;
	$info = $this->getSpecificPilotLicence($pilot, "Instructor") ;
	if ($info->checked) return $info ;
	$info = $this->getSpecificPilotLicence($pilot, "CPL") ;
	if ($info->checked) return $info ;
	$info = $this->getSpecificPilotLicence($pilot, "SEP") ;
	return $info ;
}

function listPilot($flight_type) {
	global $mysqli_link,  $table_flights_pilots, $table_person ;
	
	$this->SetColumnsWidth(array(40, 30, 40, 30, 30, 35)) ;
	$this->ImprovedTableHeader(array("Nom", "Prénom", "Date de\nnaissance", "Licence", "Exp date", "Mobile")) ; 
	$result = mysqli_query($mysqli_link, "SELECT *, date(birthdate) as birthdate 
		FROM  $table_flights_pilots JOIN $table_person ON p_id = jom_id 
		WHERE $flight_type <> 0")
		or die("Cannot list discovery flight: " . mysqli_error($mysqli_link)) ; 
	while ($row = mysqli_fetch_array($result)) {
		$info_licence = $this->pilotLicence($row['jom_id']) ;
		$this->ImprovedTableRow(array(db2web($row['last_name']), db2web($row['first_name']), $row['birthdate'], 
			"$info_licence[name] $info_licence[ident_value]", $info_licence['expiration'], $row['cell_phone'])) ;
	}
	mysqli_free_result($result) ;
}

}

$pdf = new PDF('L','mm','A4');
$pdf->AliasNbPages(); // Prepare the page numbering

$pdf->SetFont('Arial','B',16);

$pdf->NouveauChapitre("Liste des pilotes pour les vols découverte") ;
$pdf->listPilot('p_discovery') ;

$pdf->NouveauChapitre("Liste des pilotes pour les vols d'initiation") ;
$pdf->listPilot('p_initiation') ;

$pdf->Output();

if ($userId != 62) journalise($userId, "I", "PDF generated for all pilots") ;
?>