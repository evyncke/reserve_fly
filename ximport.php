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

MustBeLoggedIn() ;

if (! $userIsAdmin && ! $userIsBoardMember)
    journalise($userId, "F", "Vous n'avez pas le droit de consulter cette page ou vous n'êtes pas connecté.") ; 

if (!(isset($_REQUEST['nextMove']) and is_numeric($_REQUEST['nextMove'])))
    journalise($userId, "F", "Invalid parameter: nextMove = $nextMove") ;
$nextMove = $_REQUEST['nextMove'] ;
if (!(isset($_REQUEST['nextInvoice']) and is_numeric($_REQUEST['nextInvoice'])))
    journalise($userId, "F", "Invalid parameter: nextInvoice = $nextInvoice") ;
$nextInvoice = $_REQUEST['nextInvoice'] ;

require_once 'folio.php' ;

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
?><!DOCTYPE html>
<html lang="fr">
<head>
<link rel="stylesheet" type="text/css" href="mobile.css">
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
    <h1>Génération des factures</h1>
    <p class="bg-danger">Ceci est en mode test réservé à Eric Vyncke et ne va générer les factures que pour Alain, Dominique, Éric, Patrick.</p>

<?php

$f = fopen('data/ximport.txt', 'w')
    or journalise($userId, "F", "Cannot open data/ximport.txt for writing") ;
// Eric = 62, Patrick = 66, Dominique = 348, Alain 92, Davin/élève 439

$members = [62, 66, 92, 348, 439] ;

print("<pre>\n") ;
foreach($members as $member) {
    $folio = new Folio($member, '2023-07-01', '2023-07-31') ;
    if ($folio->count == 0) continue ;
    $total_folio = 0 ;
    foreach($folio as $line) {
//        print_r($line) ;
        print("<br/>\n") ;
        $code_plane = substr($line->plane, 3) ;
        $ximportLine = new XimportLine($nextMove, 'VEN', $line->date, '', $nextInvoice, 700100, "$line->pilot_name $line->pilot_fname", $line->cost_plane, 'C', $nextInvoice,
            $code_plane, 0.0, 0.0, '0', $line->date, 0) ;
        print("$ximportLine<br/>\n") ;
        fprintf($f, "$ximportLine\n") ;
        // Special line if there are taxes
        if ($line->cost_taxes) {
            $ximportLine = new XimportLine($nextMove, 'VEN', $line->date, '', $nextInvoice, 744103, "$line->pilot_name $line->pilot_fname", $line->cost_taxes, 'C', $nextInvoice,
                'TI', 0.0, 0.0, '0', $line->date, 0) ;
            print("$ximportLine<br/>\n") ;
            fprintf($f, "$ximportLine\n") ;           
        }
        // Special line if there is an inscructor
        if ($line->cost_fi) {
            switch ($line->instructor_code) {
                case  46: $fi_code = 700202 ; $fi_analytique = 'MB' ; break ;
                case  50: $fi_code = 700205 ; $fi_analytique = 'WL' ; break ;
                case  59: $fi_code = 700206 ; $fi_analytique = 'CN' ; break ;
                case 118: $fi_code = 700208 ; $fi_analytique = 'GD' ; break ;
            }
            $ximportLine = new XimportLine($nextMove, 'VEN', $line->date, '', $nextInvoice, $fi_code, "$line->pilot_name $line->pilot_fname", $line->cost_fi, 'C', $nextInvoice,
                $fi_analytique, 0.0, 0.0, '0', $line->date, 0) ;
            print("$ximportLine<br/>\n") ;
            fprintf($f, "$ximportLine\n") ;           
        }
        $total_folio += $line->cost_plane + $line->cost_fi + $line->cost_taxes ;
    }
    // Write to the member account
    $ximportLine = new XimportLine($nextMove, 'VEN', $line->date, '', $nextInvoice, $line->pilot_code_ciel, "Centr. $line->pilot_code_ciel,1 L deb", $total_folio, 'D', $nextInvoice,
        '', 0.0, 0.0, '0', $line->date, 0) ;
    print("$ximportLine<br/>\n") ;
    fprintf($f, "$ximportLine\n") ;
    $nextMove++ ;
    $nextInvoice++ ;
}

print("</pre>\n") ;

fclose($f) ;
?>
<p>Voici les paramètres à modifier dans <i>Ciel Premium Account</i>:
    <ul>
        <li>Numéro de facture suivant: <?=$nextInvoice?></li>
        <li>Numéro de mouvement suivant: <?=$nextMove?></li>
    </ul>
</p>
</body>
</html>
