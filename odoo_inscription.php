<?php
/*
   Copyright 2023-2025 Patrick Reginster

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.


   Introduction of a new member in DB

   Tables company: (tables rapcs_company et rapcs_company_member).
*/

require_once "dbi.php" ;
require_once __DIR__.'/odooFlight.class.php';

if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/odoo_inscription.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}

if (isset($_REQUEST['plane']) and $_REQUEST['plane'] != '') {
    $plane = strtoupper(trim($_REQUEST['plane'])) ;
} else {
    $plane = NULL ;
}
$header_postamble = "
<script>
  var default_member=$userId;
</script>
<script src=\"js/odoo_inscription.js\"></script>" ;
$body_attributes = 'onload=init();odooInscriptionMain();' ;
require_once 'mobile_header5.php' ;
//require_once 'mobile_tools.php' ;
$prenom="";
$nom="";
$username="";
$password="Rapcs123!";
$datenaissance="";
$email="";
$telephone="";
$adresse="";
$codepostal="";
$ville="";
$pays="Belgique";
$motivation="";
$typemembre="none";
$qualification="";
$licence="";
$validitemedicale="";
$validiteelp="";
$courstheorique="";
$cotisation="";
$caution="";
$dateinscription="";
$factureodoo="";
$societe="";
$nomsociete="";
$bcesociete="";
$adressesociete="";
$codepostalsociete="";
$villesociete="";
$payssociete="Belgique";
$contactnom="";
$contactlien="";
$contactphone="";
$contactmail="";
if (isset($_REQUEST['createmember']) and $_REQUEST['createmember'] == 'create') {
    $prenom=$_REQUEST['prenom'];
    $nom=$_REQUEST['nom'];
    $username=$_REQUEST['username'];
    $password=$_REQUEST['password'];
    $datenaissance=$_REQUEST['datenaissance'];
    $email=$_REQUEST['email'];
    $telephone=$_REQUEST['telephone'];
    $adresse=$_REQUEST['adresse'];
    $codepostal=$_REQUEST['codepostal'];
    $ville=$_REQUEST['ville'];
    $pays=$_REQUEST['pays'];
    $motivation=$_REQUEST['motivation'];
    $typemembre=$_REQUEST['typemembre'];
    if(isset($_REQUEST['qualification'])) $qualification=$_REQUEST['qualification'];
    if(isset($_REQUEST['licence'])) $licence=$_REQUEST['licence'];
    if(isset($_REQUEST['validitemedicale'])) $validitemedicale=$_REQUEST['validitemedicale'];
    if(isset($_REQUEST['validiteelp'])) $validiteelp=$_REQUEST['validiteelp'];
    if(isset($_REQUEST['courstheorique'])) $courstheorique=$_REQUEST['courstheorique'];
    if(isset($_REQUEST['caution'])) $caution=$_REQUEST['caution'];
    $cotisation=$_REQUEST['cotisation'];
    $dateinscription=$_REQUEST['dateinscription'];
    $factureodoo=$_REQUEST['factureodoo'];
    $societe=$_REQUEST['societe'];
    if(isset($_REQUEST['nomsociete'])) $nomsociete=$_REQUEST['nomsociete'];
    if(isset($_REQUEST['bcesociete'])) $bcesociete=$_REQUEST['bcesociete'];
    if(isset($_REQUEST['adressesociete'])) $adressesociete=$_REQUEST['adressesociete'];
    if(isset($_REQUEST['codepostalsociete'])) $codepostalsociete=$_REQUEST['codepostalsociete'];
    if(isset($_REQUEST['villesociete'])) $villesociete=$_REQUEST['villesociete'];
    if(isset($_REQUEST['payssociete'])) $payssociete=$_REQUEST['payssociete'];
    $contactnom=$_REQUEST['contactnom'];
    $contactlien=$_REQUEST['contactlien'];
    $contactphone=$_REQUEST['contactphone'];
    $contactmail=$_REQUEST['contactmail'];
 
    $error=OF_CreateNewMember( 
            $prenom,
            $nom,
            $username,
            $password,
            $datenaissance,
            $email,
            $telephone,
            $adresse,
            $codepostal,
            $ville,
            $pays,
            $motivation,
            $typemembre,
            $qualification,
            $licence,
            $validitemedicale,
            $validiteelp,
            $courstheorique,
            $cotisation,
            $caution,
            $dateinscription,
            $factureodoo,
            $societe,
            $nomsociete,
            $bcesociete,
            $adressesociete,
            $codepostalsociete,
            $villesociete,
            $payssociete,
            $contactnom,
            $contactlien,
            $contactphone,
            $contactmail
    );
    if($error!="") {
    		print("<div class=\"alert alert-warning\" role=\"alert\">
 				 Member $prenom $nom is not created. Error=$error</div>");
    }
    else {
   		print("<div class=\"alert alert-info\" role=\"alert\">
 				 Member $prenom $nom is added corretly in you DB and in ODOO.<br>
                 Don't forget to save the pdf in OneDrive and adapt the file members.xlsx in OneDrive</div>");

    }
}
?>

<h3>Create a new member in RAPCS tables and in Odoo</h3>
<h4>To edit a member: Use the page <a href="https://www.spa-aviation.be/resa/gestionMembres.php">GestionMembres</a></h4>

<div class="row">
<form action="<?=$_SERVER['PHP_SELF']?>" method="get" role="form" class="form-horizontal" enctype="multipart/form-data">
<div class="row mb-3">
	<label for="prenom" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Prénom nom:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="prenom" id="id_prenom" placeholder="Prénom" value="<?=$prenom?>">
		<input type="text" class="form-control" name="nom" id="id_nom" placeholder="Nom" value="<?=$nom?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="username" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Username & Password:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="username" id="id_username" placeholder="pnom" value="<?=$username?>">
		<input type="text" class="form-control" name="password" id="id_password" placeholder="Pnom123!" value="<?=$password?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="datenaissance" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Date naissance:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="date" class="form-control" name="datenaissance" id="id_datenaissance" placeholder="jj/mm/aaaa" value="<?=$datenaissance?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="email" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Email:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="email" id="id_email" placeholder="e-mail address" value="<?=$email?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="adresse" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Adresse:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="adresse" id="id_adresse" placeholder="rue xxxxx, 11/51" value="<?=$adresse?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="codepostal" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Code Postal:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="codepostal" id="id_codepostal" placeholder="4000" value="<?=$codepostal?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="ville" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Ville:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="ville" id="id_ville" placeholder="Spa" value="<?=$ville?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="pays" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Pays:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="pays" id="id_pays" placeholder="Belgique" value="<?=$pays?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="telephone" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Téléphone:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="telephone" id="id_telephone" placeholder="+324********" value="<?=$telephone?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="motivation" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Motivations:</label>
	<div class="col-sm-12 col-md-6">
          <textarea name="motivation" id="id_motivation" rows="5" cols="30" value="<?=$motivation?>">Décrire ses motivations</textarea>
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="typemembre" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Type de membre:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
        <select name="typemembre" id="id_typemembre" class="form-select">
            <option value="none">Choisir un type de membre</option>
            <option value="nonnaviguant" <?=($typemembre=="nonnaviguant") ? "selected" :""?>>Membre non-naviguant</option>
            <option value="eleve" <?=($typemembre=="eleve") ? "selected" :""?>>Elève</option>
            <option value="pilote"<?=($typemembre=="pilote") ? "selected" :""?>>Pilote</option>
        </select>
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3" id="id_qualification_row">
	<label for="qualification" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Qualification:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
        <select name="qualification" id="id_qualification" class="form-select">
            <option value="none">Choisir une qualification</option>
            <option value="PPL" <?=($qualification=="PPL") ? "selected" :""?>>PPL</option>
            <option value="LAPL"<?=($qualification=="LAPL") ? "selected" :""?>>LAPL</option>
            <option value="CPL" <?=($qualification=="CPL") ? "selected" :""?>>CPL</option>
            <option value="ATPL" <?=($qualification=="ATPL") ? "selected" :""?>>ATPL</option>
        </select>
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3" id="id_licence_row">
	<label for="licence" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Licence Nº:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="licence" id="id_licence" placeholder="BExxxxxxx" value="<?=$licence?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3" id="id_validitemedicale_row">
	<label for="validitemedicale" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Validité médicale:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="date" class="form-control" name="validitemedicale" id="id_validitemedicale" placeholder="jj/mm/aaaa" value="<?=$validitemedicale?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3" id="id_validiteelp_row">
	<label for="validiteelp" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Validite ELP:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="date" class="form-control" name="validiteelp" id="id_validiteelp" placeholder="jj/mm/aaaa" value="<?=$validiteelp?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3" id="id_courstheorique_row">
	<label for="courstheorique" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Participation cours théorique:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
        <select name="courstheorique" id="id_courstheorique" class="form-select">
            <option value="none">Choisir</option>
            <option value="oui" <?=($courstheorique=="oui") ? "selected" :""?>>Oui</option>
            <option value="non" <?=($courstheorique=="non") ? "selected" :""?>>Non</option>
        </select>
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="dateinscription" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Date d'inscription:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="date" class="form-control" name="dateinscription" id="id_dateinscription" placeholder="jj/mm/aaaa" value="<?=$dateinscription?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="cotisation" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Cotisation(€):</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="cotisation" id="id_cotisation" placeholder="270" value="<?=$cotisation?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3" id="id_caution_row">
	<label for="caution" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Caution:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
        <select name="caution" id="id_caution" class="form-select">
            <option value="none">Choisir</option>
            <option value="oui" <?=($caution=="oui") ? "selected" :""?>>Oui - 200€</option>
            <option value="non" <?=($caution=="non") ? "selected" :""?>>Non</option>
        </select>
	</div> <!-- col -->
</div> <!-- row -->


<!--- Société --->
<div class="row mb-3" id="id__row">
	<label for="societe" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Société associée au membre:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
        <select name="societe" id="id_societe" class="form-select">
            <option value="oui" <?=($societe=="oui") ? "selected" :""?>>Oui</option>
            <option value="non" <?=($societe=="non") ? "selected" :""?>>Non</option>
        </select>
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3" id="id_nomsociete_row">
	<label for="nomsociete" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Nom société:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="nomsociete" id="id_nomsociete" placeholder="nom société" value="<?=$nomsociete?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3" id="id_bcesociete_row">
	<label for="bcesociete" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;TVA société:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="bcesociete" id="id_bcesociete" placeholder="BE0XXXXXXXXXXXX" value="<?=$bcesociete?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3" id="id_adressesociete_row">
	<label for="adressesociete" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Adresse société:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="adressesociete" id="id_adressesociete" placeholder="rue xxxxx, 11/51" value="<?=$adressesociete?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3" id="id_codepostalsociete_row">
	<label for="codepostalsociete" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Code Postal société:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="codepostalsociete" id="id_codepostalsociete" placeholder="4000" value="<?=$codepostalsociete?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3" id="id_villesociete_row">
	<label for="villesociete" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Ville société:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="villesociete" id="id_villesociete" placeholder="Spa" value="<?=$villesociete?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3" id="id_payssociete_row">
	<label for="payssociete" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Pays société:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="payssociete" id="id_payssociete" placeholder="Belgique" value="<?=$payssociete?>">
	</div> <!-- col -->
</div> <!-- row -->

<div class="row mb-3">
	<label for="contactnom" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Personne de contact:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="contactnom" id="id_contactnom" placeholder="Prénon Nom" value="<?=$contactnom?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="contactlien" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Lien avec la personne de contact:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="contactlien" id="id_contactlien" placeholder="Epouse" value="<?=$contactlien?>">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="contactphone" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Téléphone de la personne de contact:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
 		<input type="text" class="form-control" name="contactphone" id="id_contactphone" placeholder="+324xxxxxxxxxx" value="<?=$contactphone?>">
	</div> <!-- col -->
</div> <!-- row --><div class="row mb-3">
	<label for="contactmail" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Lien avec la personne de contact:</label>
	<div class="col-sm-4 col-md-4 col-lg-4">
  		<input type="text" class="form-control" name="contactmail" id="id_contactmail" placeholder="xxxxx@gmail.com" value="<?=$contactmail?>">	</div> <!-- col -->
</div> <!-- row -->

<!--- Bouton envoie -->
<div class="row mb-3">
	<label for="factureodoo" class="col-form-label col-sm-4 col-md-4 col-lg-4">&nbsp;Génération facture odoo: <span id="id_valeurfacture>xxxx€</span></label>
	<div class="col-sm-4 col-md-4>
        <select name="factureodoo" id="id_factureodoo" class="form-select">
            <option value="none">Choisir</option>
            <option value="oui" <?=($factureodoo=="oui") ? "selected" :""?>>Oui</option>
            <option value="non" <?=($factureodoo=="non") ? "selected" :""?>>Non</option>
        </select>
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
        <button type="submit" id="id_createmember" name="createmember" value="create" class="col-sm-offset-2 col-md-offset-1 col-sm-3 col-md-4 btn btn-primary" >
            Create new Member
        </button></div>
</form>
</div><!-- row -->
</body>
</html>