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

$code = '0hFuN' ;

require_once "dbi.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}
require_once 'mobile_header5.php' ;
$this_year = 0 + date('Y') ;
?>
<div class="container-fluid">
<div class="row">
<h2>Votre attestation de suivi du Safety Day de <?=$this_year?></h2>
<?php
if (isset($_REQUEST['code']) and  $_REQUEST['code'] != '' and $_REQUEST['code'] != $code) {
    journalise($userId, "E", "Mauvais code: $_REQUEST[code]") ;
    print('<div class="text-danger">Code invalide, veuillez recommencer.</div>') ;
}
if (! isset($_REQUEST['code']) or $_REQUEST['code'] != $code) {
?>
<form action="<?=$_SERVER['PHP_SELF']?>" method="GET">
<div class="mb-3">
<label for="codeId" class="form-label">Code</label>
<input type="text" name="code" id="codeId" class="form-control" placeholder="Le code reçu lors du Safety Day <?=$this_year?>">
</div>
<p><mark>En cliquant sur le bouton ci-dessous, vous signez numériquement que vous avez suivi (ou lu) et compris les 
    présentations du Safety Day <?=$this_year?> organisé par le RAPCS ASBL. Ceci est une obligation de la DGTA.
</mark></p>
<button type="submit" class="btn btn-primary">J'ai suivi (ou lu) et compris les présentations du Safety Day de <?=$this_year?></button>
</form>
<?php
} else {
// Either hardcoding the validity name or its numerical value, let's keep it simple
$validity = 13 ;

// Compute expiration date
$expiration_year = 1 + $this_year ;
$expiration_date = "$expiration_year-04-30" ; // 30th of April of next year

$result = mysqli_query($mysqli_link, "REPLACE INTO $table_validity(jom_id, validity_type_id, expire_date, grant_date)
    VALUES($userId, $validity, '$expiration_date', SYSDATE())")
    or journalise($userId, "F", "Impossible de mettre à jour les validités pour le Safety Day: " . mysqli_error($mysqli_link)) ;
journalise($userId, "I", "Safety Day attested") ;
?>
<p>Votre validité "Safety Day" a bien été mise à jour et restera valable jusqu'au <?=$expiration_date?>.</p>
<?php
} // Else code exist
if ($userIsAdmin or $userIsInstructor) {
    $url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?code=' . $code ;
    print('<br><br><hr><div class="row"><p>Uniquement visible pour les administrateurs et instructeurs, le code pour ce Safety Day est <b>' . $code . "</b>.
        Ou l'URL: <a href=\"$url\">$url</a> ou le QR code:</p>
        <p> <img width=\"300\" height=\"300\" src=\"qr-code.php?chs=300x300&chl=" . urlencode($url) . "\">") ;
    print("</p></div>") ;
}
?>
</div><!-- row -->
</body>
</html>