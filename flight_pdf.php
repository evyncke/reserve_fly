<?php
/*
   Copyright 2014-2022 Eric Vyncke

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
require_once 'facebook.php' ;
require_once 'fpdf.php';
//require_once 'mem_image.php' ;

if (! ($userIsAdmin or $userIsInstructor or $userIsFlightPilot or $userIsFlightManager))
	die("Vous devez être pilote ou gestionnaire des vols découvertes ou instructeur ou administrateur pour utiliser cette page.") ;
	
$flight_id = (isset($_REQUEST['flight_id'])) ? trim($_REQUEST['flight_id']) : 0 ;
if (!is_numeric($flight_id) or $flight_id <= 0) die("Invalid ID: $flight_id") ;

class PDF extends FPDF {
// Column widths
public $column_width ;

// En-tête
function Header() {
	global $flight_reference, $userFullName ;
    // Logo
    $this->Image('../logo_rapcs_256x256.png',10,6,30);
//    $this->Image('http://www.spa-aviation.be/logo_rapcs_256x256.png',10,6,30);
    // Police Arial gras 15
    $this->SetFont('Arial','B',12);
    // Décalage à droite
    $this->CellUtf8(70);
    // Titre
    $this->CellUtf8(90,9,"RAPCS ASBL, Vol $flight_reference",1,0,'C');
    // Saut de ligne
    $this->Ln(25);
}

// Pied de page
function Footer() {
	global $userFullName ;
    // Positionnement à 1,5 cm du bas
    $this->SetY(-15);
    // Police Arial italique 8
    $this->SetFont('Arial','I',6);
    // Numéro de page
    $this->CellUtf8(0,10,'Page '.$this->PageNo().'/{nb} ' . "(Imprimé le " . date('Y-m-d H:i:s') . " par $userFullName)",0,0,'C');
}

function NouveauChapitre($libelle) {
	$this->AddPage();    
	// Arial 12
    $this->SetFont('Arial','',16);
    // Couleur de fond
    $this->SetFillColor(200,220,255);
    // Titre
    $this->MultiCellUtf8(0,6,"$libelle",0,1,'C',true);
    // Saut de ligne
    $this->Ln(4);
    $this->SetFont('Arial','',8);
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
 	   $this->CellUtf8($this->column_width[$i], 4, $header[$i], 1, 0, 'C');
 	$this->SetFont('','');
	$this->Ln();
}

function ImprovedTableRow($row) {
    // Data
    for($i=0; $i<count($row); $i++)
        $this->CellUtf8($this->column_width[$i], 4, $row[$i], 1, 0, 'C');
    $this->Ln();
}

function ImprovedTableMultiLineRow($line_count, $row) {
    // Data
	if ($line_count == 1) {
		ImprovedTableRow($row)  ;
		return ;
	}
	// Emit first line with top lines
	for($i=0; $i<count($row); $i++) {
		$lines = explode("\n", $row[$i]) ;
		$this->CellUtf8($this->column_width[$i], 4, $lines[0], 'LTR', 0, 'C');
	}
	$this->Ln();
	// Emit middle lines
	for ($line = 1; $line < $line_count-1; $line++) {
		for($i=0; $i<count($row); $i++) {
			$lines = explode("\n", $row[$i]) ;
			$this->CellUtf8($this->column_width[$i], 4, $lines[$line], 'LR', 0, 'C');
		}
		$this->Ln();
	}
	// Emit first line with top lines
	for($i=0; $i<count($row); $i++) {
		$lines = explode("\n", $row[$i]) ;
		$this->CellUtf8($this->column_width[$i], 4, $lines[$line_count-1], 'LBR', 0, 'C');
	}
	$this->Ln();
}

}

function getSpecificPilotLicence($pilot, $rating) {
	global $mysqli_link, $table_validity_type, $table_validity ;

	$result = mysqli_query($mysqli_link, "SELECT * 
		FROM $table_validity_type t JOIN $table_validity v ON validity_type_id = t.id
		WHERE jom_id = $pilot AND name like '%$rating%'") or die("Cannot read ratings: " . mysqli_error($mysqli_link)) ;
	$row = mysqli_fetch_array($result) ;
	mysqli_free_result($result) ;
	if ($row)
		return array('checked'=>true, 'name'=>$row['name'], 'ident'=>$row['ident_value'], 'expiration'=> $row['expire_date']) ;
	return array('name' => null, 'checked' => false) ;
}

function pilotLicence($pilot) {
	if ($pilot == '' or $pilot <= 0)
		return array('licence' => null, 'checked' => false, 'ident' => null, 'expiration' => null) ;
	$info = getSpecificPilotLicence($pilot, "Instructor") ;
	if ($info['checked']) return $info ;
	$info = getSpecificPilotLicence($pilot, "CPL") ;
	if ($info['checked']) return $info ;
	$info = getSpecificPilotLicence($pilot, "SEP") ;
	return $info ;
}

function getPilotELP($pilot) {
	global $mysqli_link, $table_validity_type, $table_validity ;

	$result = mysqli_query($mysqli_link, "SELECT * 
		FROM $table_validity_type t JOIN $table_validity v ON validity_type_id = t.id
		WHERE jom_id = $pilot AND name like '%ELP%'") or die("Cannot read ELP: " . mysqli_error($mysqli_link)) ;
	$row = mysqli_fetch_array($result) ;
	mysqli_free_result($result) ;
	if ($row)
		return array('checked'=>true, 'name'=>$row['name'], 'expiration'=> $row['expire_date']) ;
	return array('name' => null, 'checked' => false) ;
}

function getPilotMedical($pilot) {
	global $mysqli_link, $table_validity_type, $table_validity ;

	$result = mysqli_query($mysqli_link, "SELECT * 
		FROM $table_validity_type t JOIN $table_validity v ON validity_type_id = t.id
		WHERE jom_id = $pilot AND name like 'M%dical%'") or die("Cannot read medical: " . mysqli_error($mysqli_link)) ;
	$row = mysqli_fetch_array($result) ;
	mysqli_free_result($result) ;
	if ($row)
		return array('checked'=>true, 'name'=>$row['name'], 'expiration'=> $row['expire_date']) ;
	return array('name' => null, 'checked' => false) ;
}

function getPilot100h($pilot) {
	global $mysqli_link, $table_validity_type, $table_validity ;

	$result = mysqli_query($mysqli_link, "SELECT * 
		FROM $table_validity_type t JOIN $table_validity v ON validity_type_id = t.id
		WHERE jom_id = $pilot AND name like '%100%'") or die("Cannot read 100hours: " . mysqli_error($mysqli_link)) ;
	$row = mysqli_fetch_array($result) ;
	mysqli_free_result($result) ;
	if ($row)
		return array('checked'=>true, 'name'=>$row['name'], 'since'=>$row['grant_date']);
	return array('name' => null, 'checked' => false, 'since' => '???') ;
}

function getPilotIF($pilot, $flight_type) {
	global $mysqli_link, $table_flights_pilots ;

	$column = ($flight_type == 'initiation') ? 'p_initiation' : 'p_discovery' ;
	$result = mysqli_query($mysqli_link, "SELECT * 
		FROM $table_flights_pilots
		WHERE p_id = $pilot") or die("Cannot read $table_flights_pilots: " . mysqli_error($mysqli_link)) ;
	$row = mysqli_fetch_array($result) ;
	mysqli_free_result($result) ;
	if ($row)
		return $row[$column] != 0 ;
	return false ;
}

function pilotFlightTimeInfo($pilot) {
	global $mysqli_link, $table_logbook, $table_planes ;
	
	if ($pilot == '' or $pilot <= 0)
		return ['hours' => 0, 'checked' => false] ;
	$result = mysqli_query($mysqli_link, "SELECT *, compteur_vol, 
			if(compteur_vol IS NULL, 
				TIMESTAMPDIFF(MINUTE, l_start, l_end),
				if(compteur_vol = 0, 
					l_end_hour * 60 + l_end_minute - l_start_hour * 60 - l_start_minute, 
					l_flight_end_hour * 60 + l_flight_end_minute - l_flight_start_hour * 60 - l_flight_start_minute))
				 as minutes 
		FROM $table_logbook LEFT JOIN $table_planes ON l_plane = id
		WHERE l_pilot = $pilot AND l_end > DATE_SUB(CURDATE(), INTERVAL 1 YEAR)") or die("Cannot read loggings: " . mysqli_error($mysqli_link)) ;
	$minutes = 0 ;
	while ($row = mysqli_fetch_array($result)) {
		$minutes += $row['minutes'] ;
	}
	mysqli_free_result($result) ;
	return ['hours' => round($minutes / 60, 1), 'checked' => true] ;
}

function RecentBooking($plane, $userId, $delai_reservation) {
	global $mysqli_link, $table_logbook, $table_bookings ;
	global $message ;

	$return_value = new stdClass() ;
	$result = mysqli_query($mysqli_link, "select l_end, datediff(sysdate(), l_end) as temps_dernier 
		from $table_logbook l
		where l_plane = '$plane' and (l_pilot = $userId or (l_instructor is not null and l_instructor = $userId))
		order by l_end desc") or die("Cannot get last reservation: " . mysqli_error($mysqli_link)) ;
	$row = mysqli_fetch_array($result) ;
	if (! $row) {
		mysqli_free_result($result) ;
		$return_value->result = FALSE ;
		$return_value->explanation = 'No booking found' ;
		return $return_value ;
	} else {
		mysqli_free_result($result) ;
		$return_value->explanation = "$plane " . substr($row['l_end'], 0, 10) . "\n$row[temps_dernier] days ago" ;
		$return_value->result = ($row['temps_dernier'] <= $delai_reservation) ;
		return $return_value ;
	}
}

function RecentLandings($plane, $userId) {
	global $mysqli_link, $table_logbook, $table_bookings ;
	global $message ;

	$result = mysqli_query($mysqli_link, "SELECT SUM(l_day_landing) AS landings
		FROM $table_logbook l
		WHERE l_plane = '$plane' and (l_pilot = $userId or (l_instructor is not null and l_instructor = $userId))
		AND l_end >= DATE_SUB(SYSDATE(), INTERVAL 90 DAY)") or die("Cannot get landings count: " . mysqli_error($mysqli_link)) ;
	$row = mysqli_fetch_array($result) ;
	mysqli_free_result($result) ;
	if (! $row) {
		return 0 ;
	} else {
		return $row['landings'] ;
	}
}

$pdf = new PDF('P','mm','A4');
$pdf->AliasNbPages(); // Prepare the page numbering

$pdf->SetFont('Arial','B',16);

$result = mysqli_query($mysqli_link, "SELECT *, SYSDATE() as today 
	FROM $table_flight JOIN $table_pax_role ON f_id = pr_flight JOIN $table_pax ON pr_pax = p_id
	LEFT JOIN $table_person ON f_pilot = jom_id
	LEFT JOIN $table_bookings ON f_booking = r_id 
	WHERE pr_role = 'C' AND f_id = $flight_id ")
	or journalise($userId, "F", "Cannot retrieve flight: " . mysqli_error($mysqli_link)) ;
$row_flight = mysqli_fetch_array($result) ;
if (! $row_flight) {
	$pdf->NouveauChapitre(0, 5, "Ce vol $flight_id n'existe pas.") ;
	$pdf->CellUtf8(0, 5, "Ce vol $flight_id n'existe pas.") ;
	$pdf->Output();
	exit ;
}
mysqli_free_result($result) ;
$scheduled_date = (isset($row_flight['f_date_linked']) and $row_flight['f_date_linked']) ? " $row_flight[f_date_linked]" : '' ;
$flight_type = ($row_flight['f_type'] == 'D') ? 'découverte' : "d'initiation" ;
$flight_reference = ($row_flight['f_gift']) ? 'V-' : '' ;
$flight_reference .= ($row_flight['f_type'] == 'D') ? 'IF-' : "INIT-" ;
// in 2022, numbers from 0 to 80 were used, since 2023 it is more like 23xxxx
$flight_reference .= ($fligh_id < 1000) ? sprintf("%03d", $flight_id) : sprintf("%06d", $flight_id) ;

// Unsure why the manager insists on using his manual numbers...
if ($row_flight['f_reference'] != '')
	$flight_reference = db2web($row_flight['f_reference']) . ' (' . sprintf("#%06d", $flight_id) . ')';

	// Get the circuit names
$circuits = json_decode(file_get_contents("../voldecouverte/script/circuits.js"), true);
$circuit_name = (isset($circuits[$row_flight['f_circuit']])) ? $circuits[$row_flight['f_circuit']] : "Circuit #$row_flight[f_circuit] inconnu" ;


// Get information about booked plane
if ($row_flight['r_plane']) {
	$result_plane = mysqli_query($mysqli_link, "SELECT * FROM $table_planes WHERE id = '$row_flight[r_plane]' and ressource = 0")
		or die("Cannot retrieve information about plane: " . mysqli_error($mysqli_link)) ;
	$row_plane = mysqli_fetch_array($result_plane) ;
	mysqli_free_result($result_plane) ;
} else
	$row_plane = false ;
//
// The most important part...
//
$pdf->NouveauChapitre("Dossier sécurité pour le vol $flight_type $flight_reference ($row_flight[p_lname]$scheduled_date)") ;

if ($row_flight['f_type'] == 'D') {
	$pdf->CellUtf8(0, 3, "Circuit: $circuit_name") ;
	$pdf->Ln() ;
}

if ($row_flight['first_name'])
	$pdf->CellUtf8(0, 3, "Pilote: " . db2web("$row_flight[first_name] $row_flight[last_name] ($row_flight[cell_phone] $row_flight[email])")) ;
else
	$pdf->CellUtf8(0, 3, "Pilote:") ;
$pdf->Ln() ;

if ($row_flight['r_plane'])
	$pdf->CellUtf8(0, 3, "Avion: $row_flight[r_plane]") ;
else
	$pdf->CellUtf8(0, 3, "Avion: ") ;
$pdf->Ln() ;

if ($row_flight['r_start'])
	$pdf->CellUtf8(0, 3, "Début du vol (heure locale): $row_flight[r_start]") ;
else
	$pdf->CellUtf8(0, 3, "Ce vol n'est pas encore planifié.") ;
$pdf->Ln() ;

if ($row_flight['f_date_paid'])
	$pdf->CellUtf8(0, 3, "Vol payé le $row_flight[f_date_paid]: " . db2web($row_flight['f_reference_payment'])) ;
else
	$pdf->CellUtf8(0, 3, "Ce vol doit encore être payé.") ;
$pdf->Ln() ;
$pdf->Ln() ;

//
// Check-list
//
// o ET avoir effectué au moins 100 heures de vol en tant que PIC sur un avion de classe SEP, dont
// 10 heures au cours des 12 derniers mois
// o ET avoir effectué au moins 10 heures sur un aéronef du même type que celui utilisé pour le vol
// découverte. Dans ce cadre, on distingue 4 types : C150-152 / C172 / C182 / PA18
// o ET avoir effectué au moins 3 vols complets (atterrissages et décollages) en tant que PIC sur un
// aéronef du même type au cours des 90 derniers jours.
// o ET avoir la certification ELP.

// Voire page 14 de https://drive.google.com/file/d/1XYZWORUndRYdqAqUmnbHWXKOB7IotbO4/view

$pdf->CellUtf8(0, 3, "En signant ci-dessous, je certifie sur l’honneur que (parapher les différents points dans la colonne 'paraphe') :") ;
$pdf->Ln() ;
$pdf->SetColumnsWidth(array(110, 50, 10, 20)) ;
$pdf->ImprovedTableHeader(array("Point", "Information", "Check", "Paraphe")) ; 
if ($row_flight['f_pilot']) {
	$licence_info = pilotLicence($row_flight['f_pilot']) ;
	$pdf->ImprovedTableRow(array("Licence: $licence_info[name]", "Expiration: $licence_info[expiration]", (!$licence_info['checked']) ? '' : (($licence_info['expiration'] < date('Y-m-d')) ? 'NON' : 'OUI'), '' )) ;
	$medical_info = getPilotMedical($row_flight['f_pilot']) ;
	$pdf->ImprovedTableRow(array("Certificat médical" , "Expiration: $medical_info[expiration]", (!$medical_info['checked']) ? '' : (($medical_info['expiration'] < date('Y-m-d')) ? 'NON' : 'OUI'), '' )) ;
	$elp_info = getPilotELP($row_flight['f_pilot']) ;
	$pdf->ImprovedTableRow(array("ELP" , "Expiration: $elp_info[expiration]", (!$elp_info['checked']) ? '' : (($elp_info['expiration'] < date('Y-m-d')) ? 'NON' : 'OUI'), '' )) ;
	if ($row_flight['r_plane']) {
		$result_booking = mysqli_query($mysqli_link, "select upper(id) as id, classe, delai_reservation
			from $table_planes where ressource = 0
			order by id") or die("Cannot get all active planes:".mysqli_error($mysqli_link)) ;
		$check_club = false ;
		$landings_count = 0 ;
		while ($row_booking = mysqli_fetch_array($result_booking) and !$check_club) {
			if (planeClassIsMember($row_plane['classe'], $row_booking['classe'])) {
				$check_club = RecentBooking($row_flight['r_plane'], $row_flight['f_pilot'], $row_booking['delai_reservation']) ; // Only if recent flight !!!
				$landings_count += RecentLandings($row_flight['r_plane'], $row_flight['f_pilot']) ; 
			}
		}
		mysqli_free_result($result_booking) ;
		$pdf->ImprovedTableMultiLineRow(2, array("Check club (90 days / 6 weeks)\nClass: $row_plane[classe]" , $check_club->explanation,($check_club->result) ? 'OUI' : 'NON', '')) ;
		$pdf->ImprovedTableMultiLineRow(2, array("Vols complets (atterrissages et décollages)\nderniers 90 jours sur classe: $row_plane[classe]" , $landings_count,
			($landings_count >= 3) ? 'OUI' : 'NON', '')) ;
	} else {// $row_flight['r_plane']
		$pdf->ImprovedTableRow(array("Check club (90 days / 6 weeks)" , "Avion non spécifié", "", "")) ;
		$pdf->ImprovedTableRow(array("Vols complets (atterrissages et décollages)" , "Avion non spécifié", "", "")) ;
	}
	$pic100_info = getPilot100h($row_flight['f_pilot']) ;
	$pdf->ImprovedTableRow(array("100 heures de vol en tant que PIC", "Depuis: $pic100_info[since]", ($pic100_info['checked']) ? 'OUI' : 'NON', '')) ;
	$flight_time_info = pilotFlightTimeInfo($row_flight['f_pilot']) ;
	$pdf->ImprovedTableRow(array("Heures de vol sur les 12 derniers mois (SEP)" , "$flight_time_info[hours] h.", ($flight_time_info['hours'] >= 10.0) ? "OUI" : "NON", '')) ;
// TODO check 10 heures sur classe C150/152 ou C172 ou C182 ou PA18

	$pdf->ImprovedTableRow(array("Je suis toujours agré(e) pour le vol $flight_type", '', (getPilotIF($row_flight['f_pilot'], $flight_type)) ? 'OUI' : 'NON', '')) ;
	$pdf->ImprovedTableRow(array("J'ai vérifié toutes les assurances et certificats nécessaires à l'avion", '', '', '')) ;
	$pdf->ImprovedTableRow(array("Les passagers ne montrent aucun signe apparent de contre-indication à ce vol", '', '', '')) ;
} else {
	$pdf->ImprovedTableRow(array("Licence" , '', '', '')) ;
	$pdf->ImprovedTableRow(array("ELP" , '', '', '')) ;
	$pdf->ImprovedTableRow(array("Check club (90 days / 6 weeks)" , "", "", '')) ;
	$pdf->ImprovedTableRow(array("Heures de vol sur l'année (PPL)" , '', '', '')) ;
}
$pdf->ImprovedTableMultiLineRow(2, array("Repos observé (1 heure de repos après 2 heures de vol,\nmax 4 heures de vol par 24 heures)" , "", "", '')) ;
$pdf->ImprovedTableMultiLineRow(3, array("J'accompagne les passagers sur les parkings, je leur donne le briefing
d'avant vol (en particulier toutes les conditions de sécurité),
et je les aide à monter et descendre de l'avion." , "", "", '')) ;
$pdf->ImprovedTableRow(array("Vérification de la quantité de carburant emportée" , " litres", "", '')) ;
$pdf->ImprovedTableRow(array("Masse et centrage" , (($row_flight['r_plane'])) ? "Voir page suivante" : "Avion non spécifié", "", '')) ;
//if ($licence_info['checked']) {
//	$pdf->MultiCellUtf8(0, 8, "(*) $licence_info[name]: $licence_info[ident] jusqu'au $licence_info[expiration].") ;
//}
$pdf->Ln(10) ;

//
// Liste des passagers
//
$pdf->CellUtf8(0, 5, "Liste des passagers") ;
$pdf->Ln() ;
$pdf->SetColumnsWidth(array(60, 60, 20, 20)) ;
$pdf->ImprovedTableHeader(array("Nom", "Prénom", "Age", "Poids")) ; 
$result = mysqli_query($mysqli_link, "SELECT * 
	FROM $table_flight JOIN $table_pax_role ON f_id = pr_flight JOIN $table_pax ON pr_pax = p_id
	WHERE pr_role <> 'C' AND f_id = $flight_id 
	ORDER BY pr_role DESC, p_lname ASC, p_fname ASC")
	or die("Cannot retrieve flight: " . mysqli_error($mysqli_link)) ;
$all_pax = array() ;
while ($row_pax = mysqli_fetch_array($result)) {
	switch ($row_pax['p_age']) {
		case 'C': $age = "< 12 ans" ; break ;
		case 'T': $age = "< 18 ans" ; break ;
		case 'A':
		default: $age = ">= 18 ans" ;
	}
	$pdf->ImprovedTableRow(array(db2web($row_pax['p_lname']), db2web($row_pax['p_fname']), $age, "$row_pax[p_weight] kg")) ;
	$all_pax[] = $row_pax ;
}
mysqli_free_result($result) ;

//
// Bottom approval and signature
//
$pdf->Ln() ;
$pdf->CellUtf8(50, 5, "Date: " . date('Y-m-d'), 0, 0, 'L') ;
$pdf->CellUtf8(70, 5, " Pilote:", 0, 0, 'C') ;
$pdf->CellUtf8(70, 5, " Responsable sécurité:", 0, 0, 'C') ;
$pdf->SetY(-50); // Position absolue au dessus de la marge du bas
$pdf->MultiCellUtf8(0, 4, "Le non-respect de ces règles pourra entraîner votre retrait de la liste des pilotes autorisés à effectuer
des vols découverte, et votre entière responsabilité en cas d’accident ou d’incident.", 0, 1, 'C', true);
$pdf->Ln() ;
$pdf->MultiCellUtf8(0, 4, "Cette page doit rester à terre et être conservée tant que tous les passagers n'ont pas débarqué sans incidents de l'aéronef. " .
	"En cas d'incident, cette liste sera transmise au secrétariat ou au Président (info@spa-aviation.be). " .
	"Le secrétariat conservera cette liste et la transmettra sur demande aux autorités compétentes.", 0, 1, 'C', true);

function passengerWB($i) {
	global $row, $all_pax, $item_weight, $item_moment ;
	
	if (isset($all_pax[$i])) {
		$fname = db2web($all_pax[$i]['p_fname']) ;
		$lname = db2web($all_pax[$i]['p_lname']) ;
		$pax_weight = $all_pax[$i]['p_weight'] ;
		$row['item'] .= " ($fname $lname $pax_weight kg)" ;
		$item_weight = round($pax_weight * 2.20462, 2) ;
		$item_moment = $row['arm'] * $item_weight ;
	} else {
		$row['item'] .= " (absent)" ;
		$item_weight = 0 ;
		$item_moment = 0 ;
	}
}

//
// Weight and balance
//
if ($row_flight['r_plane']) { // No W&B when plane is unknown 
$pdf->NouveauChapitre("Masse et centrage $row_flight[r_plane] pour le vol $flight_reference ($row_flight[p_lname]$scheduled)") ;
$pdf->SetColumnsWidth(array(80, 30, 30, 30)) ;
$pdf->ImprovedTableHeader(array("Poste", "Poids (lbs.)", "Bras (in.)", "Moment")) ; 
$totmoment_to = 0 ;
$totwt_to = 0 ;
$totmoment_ldg = 0 ;
$totwt_ldg = 0 ;
$result = mysqli_query($mysqli_link, "SELECT * 
	FROM tp_aircraft_weights AS w JOIN tp_aircraft AS a ON a.id = w.tailnumber
	WHERE a.tailnumber = '$row_flight[r_plane]' 
	ORDER BY w.order")
	or journalise($userId, "F", "Cannot access W&B: " . mysqli_error($mysqli_link)) ;
$url_params = array() ;
while ($row = mysqli_fetch_array($result)) {
	// Let's copy the plane enveloppe gross bounds
	$maxwt = $row['maxwt'] ;
	$cgwarnfwd = $row['cgwarnfwd'] ;
	$cgwarnaft = $row['cgwarnaft'] ;
	switch ($row['item']) {
		case 'Pilot':
			$result_pilot = mysqli_query($mysqli_link, "SELECT * 
				FROM $table_person JOIN $table_flights_pilots ON jom_id = p_id
				WHERE $row_flight[f_pilot] = p_id")
				or die("Cannot get pilot details: " . mysqli_error($mysqli_link)) ;
			$row_pilot = mysqli_fetch_array($result_pilot) ;
			$item_weight = round($row_pilot['p_weight'] * 2.20462, 2) ; // kg to lbs
			$item_moment = $row['arm'] * $item_weight;
			$row['item'] .= db2web(" ($row_pilot[first_name] $row_pilot[last_name] $row_pilot[p_weight] kg)") ; 
			$url_params["line$row[id]_wt"] = $item_weight  ;
			break ;
		case 'Co-Pilot' : passengerWB(0) ; break ;
		case 'Passenger 1' : passengerWB(1) ; break ;
		case 'Passenger 2' : passengerWB(2) ; break ;
		case 'Empty aircraft': $item_weight = $row['weight'] ; $item_moment = $row['arm'] * $item_weight; break ;
		case 'Fuel': $row['item'] .= " (" . ($row['weight'] + 35) . " litres)" ; //TODO hardcoded as 35 liters
			$item_weight = round(($row['weight'] + 35) * $row['fuelwt'], 2) ; $item_moment = $row['arm'] * $item_weight; 
			$item_weight_ldg = round($row['weight'] * $row['fuelwt'], 2) ; $item_moment_ldg = $row['arm'] * $item_weight_ldg; 
			break ;
		case 'Oil': $row['item'] .= " ($row[weight] litres)" ; 
			$item_weight = round($row['weight'] * $row['fuelwt'], 2) ; $item_moment = $row['arm'] * $item_weight; 
			$item_weight_ldg = $item_weight ; $item_moment_ldg =  $item_moment ;
			break ;
		default: $item_weight = 0 ; $item_moment = 0 ;
	}
	
	// $item_moment = $row['arm'] * $item_weight ;
	$pdf->ImprovedTableRow(array($row['item'], $item_weight, $row['arm'], $item_moment)) ;
	$totwt_to += $item_weight ;
	$totmoment_to += $item_moment ;
	if ($row['fuel'] == 'true') {
		$totwt_ldg += $item_weight_ldg ;
		$totmoment_ldg += $item_moment_ldg ;
	} else { // simple case...
		$totwt_ldg += $item_weight ;
		$totmoment_ldg += $item_moment ;
	}
}
mysqli_free_result($result) ;
$totarm_to = round($totmoment_to/$totwt_to, 2) ;
$totarm_ldg = round($totmoment_ldg/$totwt_ldg, 2) ;
$checked = ($totwt_to <= $maxwt and $cgarnfwd <= $totarm_to and $totarm_to <= $cgwarnaft) ? ', checked' : ' !!! WARNING !!!' ;
$pdf->ImprovedTableRow(array("Total décollage$checked", $totwt_to, $totarm_to, $totmoment_to)) ;
$pdf->ImprovedTableRow(array("Total atterrissage$checked", $totwt_ldg, $totarm_ldg, $totmoment_ldg)) ;
$pdf->Ln(30) ;

// Also before of web application firewall in cloudflare or mod_security on the server as the request is not built with user-agent or accept...
//ini_set("user_agent","RAPCS - flight engine");
// Annoying as the output is no more a PNG...
// No longer a PNG but a javascript...
// $pdf->Image("https://www.spa-aviation.be/TippingPoint/scatter.php?tailnumber=1&totarm_to=$totarm_to&totwt_to=$totwt_to&totarm_ldg=$totarm_ldg&totwt_ldg=$totwt_ldg", null, null, 0, 0, 'PNG') ;

$pdf->MultiCellUtf8(0, 4, "Enveloppe masse et centrage: ") ;
$url = "https://www.spa-aviation.be/TippingPoint/scatter.php?tailnumber=1&totarm_to=$totarm_to&totwt_to=$totwt_to&totarm_ldg=$totarm_ldg&totwt_ldg=$totwt_ldg" ;
$pdf->Write(4, $url, $url) ;
} // $row_flight[r_plane]) No W&B when plane is unknown 

//
// Description 
//
$pdf->NouveauChapitre("Description du vol $flight_type $flight_reference ($row_flight[p_lname]$scheduled)") ;
$pdf->MulticellUtf8(0, 5, "La personne de contact pour ce vol est: $row_flight[p_fname] $row_flight[p_lname], numéro de téléphone: $row_flight[p_tel], email: $row_flight[p_email].") ;
if ($row_flight['f_description'] != '') {
	$pdf->Ln(5) ;
	$pdf->SetFont(null, 'B', null) ;
	$pdf->MulticellUtf8(0, 4, "Description de la demande:") ;
	$pdf->SetFont(null, 'I', null) ;
	$pdf->MulticellUtf8(0, 4, db2web($row_flight['f_description'])) ;
	$pdf->SetFont(null, null, null) ;
}
if ($row_flight['f_notes'] != '') {
	$pdf->Ln(5) ;
	$pdf->SetFont(null, 'B', null) ;
	$pdf->MulticellUtf8(0, 4, "Notes club:") ;
	$pdf->SetFont(null, 'I', null) ;
	$pdf->MulticellUtf8(0, 4, db2web($row_flight['f_notes'])) ;
	$pdf->SetFont(null, null, null) ;
}

//
// Responsability waivers
//

foreach($all_pax as $pax_row) {
	if ($pax_row['p_age'] == 'A') { // For an adult per René's "let's not overdo it", do not print... code kept because "who knows"
		if (false) {
			$pdf->NouveauChapitre("Décharge de " . db2web($pax_row['p_fname']) . " " . db2web($pax_row['p_lname']) . " pour le vol $flight_type $flight_reference ($row_flight[p_lname]$scheduled)") ;
			$pdf->CellUtf8(0, 30, "Je, soussigné:") ;
			$pdf->Ln() ;
			$pdf->CellUtf8(30, 10 ) ;
			$pdf->CellUtf8(0, 10, "nom: " . db2web($pax_row['p_lname'])) ;
			$pdf->Ln() ;
			$pdf->CellUtf8(30, 10 ) ;
			$pdf->CellUtf8(0, 10, "prénom: " . db2web($pax_row['p_fname'])) ;
			$pdf->Ln() ;
			$pdf->CellUtf8(30, 10 ) ;
			$pdf->CellUtf8(0, 10, "carte d'identité: ...............................................,") ;
			$pdf->Ln() ;
			$pdf->CellUtf8(0, 10, "décharge le pilote et le club RAPCS ASBL de toute responsabilité en cas d’accident.") ;
			$pdf->Ln() ;
			$pdf->CellUtf8(0, 30, "Fait à Spa, le _ _ / _ _ / 2 0 _ _") ;
			$pdf->Ln() ;
			$pdf->CellUtf8(0, 60, "(signature)", 0, 1, 'C') ;
		} else {
			//NOOP			
		}
	} else { // For a minor or children
		$pdf->NouveauChapitre("Vol $flight_type de " . db2web($pax_row['p_fname']) . " " . db2web($pax_row['p_lname']) . " à bord d'un avion du Royal Aéro Para Club de Spa RAPCS ASBL.") ;
		$pdf->SetFont(null, '', 10) ;
		$pdf->CellUtf8(0, 30, "Je, soussigné:") ;
		$pdf->Ln() ;
		$pdf->CellUtf8(30, 10 ) ;
		$pdf->CellUtf8(0, 10, "nom: ..................................................................................................................................") ;
		$pdf->Ln() ;
		$pdf->CellUtf8(30, 10 ) ;
		$pdf->CellUtf8(0, 10, "prénom: ...............................................................................................................................") ;
		$pdf->Ln() ;
		$pdf->CellUtf8(30, 10 ) ;
		$pdf->CellUtf8(0, 10, "adresse: ..............................................................................................................................") ;
		$pdf->Ln() ;
		$pdf->CellUtf8(30, 10 ) ;
		$pdf->CellUtf8(0, 10, "........................................................................................................................................") ;
		$pdf->Ln() ;
		$pdf->CellUtf8(0, 10, "Pour un(e) mineur d'âge (biffer les mentions inutiles);") ;
		$pdf->Ln() ;
		$pdf->CellUtf8(30, 10 ) ;
		$pdf->CellUtf8(0, 10, "* exerçant l'autorité parentale en tant que : PÈRE – MÈRE – TUTEUR – TUTRICE") ;
		$pdf->Ln() ;
		$pdf->CellUtf8(30, 10 ) ;
		$pdf->CellUtf8(0, 10, "* autorise MON FILS - MA FILLE (". db2web($pax_row['p_lname']) . " " . db2web($pax_row['p_fname']) .") à participer à ce vol") ;
		$pdf->Ln() ;
		$pdf->CellUtf8(0, 10, "Je décharge le pilote et le club RAPCS ASBL de toute responsabilité en cas d’accident.") ;
		$pdf->Ln() ;
		$pdf->CellUtf8(0, 30, "Fait à Spa, le _ _ / _ _ / 2 0 _ _") ;
		$pdf->Ln() ;
		$pdf->CellUtf8(0, 60, "(signature)", 0, 1, 'C') ;
	}
}

$pdf->Output();

if ($userId != 62) journalise($userId, "I", "PDF generated for flight $flight_id") ;
?>
