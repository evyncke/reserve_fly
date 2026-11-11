<?php
function rapcs_offrir($reservationType, $circuit) {
//echo "<br>wpcode_offrir_init_if:started: $reservationType<br>";
$selectedDecouverte="";
$selectedInitiation="";
$selectedBon="";
if($reservationType=="init") {
    $selectedInitiation="selected";
}
else if($reservationType=="if") {
    $selectedDecouverte="selected";
}
else if($reservationType=="bon") {
    $selectedBon="selected";
}

$selectedcircuit="";
$selectedcircuit1="";
$selectedcircuit2="";
$selectedcircuit3="";
$selectedcircuit4="";
$selectedcircuit5="";
$selectedcircuit6="";
$selectedcircuit7="";
$selectedcircuit8="";
$selectedcircuit9="";
$selectedcircuit10="";
$selectedcircuit11="";
$selectedcircuit12="";
$selectedcircuit13="";
switch ($circuit) {
  case "1":
    $selectedcircuit1="selected";
   break;
  case "2":
    $selectedcircuit2="selected";
    break;
  case "3":
    $selectedcircuit3="selected";
    break;
  case "4":
    $selectedcircuit4="selected";
    break;
  case "5":
    $selectedcircuit5="selected";
    break;
  case "6":
    $selectedcircuit6="selected";
    break;
  case "7":
    $selectedcircuit7="selected";
    break;
  case "8":
    $selectedcircuit8="selected";
    break;
  case "9":
    $selectedcircuit9="selected";
    break;
  case "10":
    $selectedcircuit10="selected";
    break;
  case "11":
    $selectedcircuit11="selected";
    break;
  case "12":
    $selectedcircuit12="selected";
    break;
  case "13":
    $selectedcircuit13="selected";
    break;
  default:
    $selectedcircuit="selected";
}
?>
<form action="https://www.spa-aviation.be/resa/voldecouverte/action_offrir.php" method="POST">
<input id="id_form_language" style="width: 85%;" name="form_language" type="text" value="french" />
<table style="width: 100%; border-color: #000000; margin-left: auto; margin-right: auto;" cellspacing="2" cellpadding="2"><caption> </caption>
<tbody>
<tr>
<td style="width: 27%; vertical-align: middle;"> Je désire offrir</td>
<td style="width: 72%; vertical-align: middle;"> <select id="id_typeofgift" name="typeofgift" onchange="compute_tarifs('id_typeofgift')">
<option disabled="disabled" value="">Choisissez votre cadeau</option>
<option value="vol_initiation" <?=$selectedInitiation?>>vol d'initiation</option>
<option value="vol_decouverte" <?=$selectedDecouverte?>>Promenade aérienne</option>
<option value="bon_valeur" <?=$selectedBon?>>Bon valeur</option>
</select></td>
</tr>
<tr id="id_numberofpassagers_row">
<td style="width: 26.680234122712598%;">Nombre de passagers</td>
<td style="width: 38.12062701829925%;"> <input id="id_numberofpassagers" style="width: 25%;" max="3" min="1" name="numberofpassagers" size="3" type="number" value="1" /></td>
</tr>
<tr id="id_circuit_row">
<td style="width: 26.680234122712598%;">Circuit numéro  </td>
<td style="width: 38.12062701829925%;"><select id="id_circuit" name="circuit" onchange="compute_tarifs('id_circuit')">
<option disabled="disabled" selected="selected" value="">Choisissez votre circuit</option>
</select></td>
</tr>
<tr id="id_valeur_bon_row">
<td style="width: 26.680234122712598%;">Valeur du Bon </td>
<td style="width: 38.12062701829925%;"><select id="id_valeur_bon" name="valeur_bon" onchange="compute_tarifs('id_valeur_bon')">
<option disabled="disabled" selected="selected" value="">Choisissez valeur</option>
</select>
<input id="id_valeur_bon_libre" style="width: 100px;" max="250" min="20" name="valeur_bon_libre" size="5" type="number" value="" placeholder="valeur libre €" /></td>
<td style="width: 32.400430570505925%;">
</td>
</tr>
<tr id="id_voldansles12mois_row" hidden>
<td style="width: 26.680234122712598%;">Est ce que le destinataire du cadeau est déjà membre du club?</td>
<td style="width: 38.12062701829925%;"><select id="id_voldansles12mois" name="voldansles12mois" onchange="compute_tarifs('id_voldansles12mois')">
<option selected="selected" value="non">non</option>
<option value="oui">oui</option>
</select></td>
</tr>
<tr id="id_valeur_virement_row">
<td style="width: 26.680234122712598%;">Valeur à verser par virement</td>
<td style="width: 38.12062701829925%;">
<p id="id_valeur_virement1">0 €</p>
<input id="id_valeur_virement" name="valeur_virement" readonly="readonly" size="5" type="text" value="" placeholder="valeur virement €" />
</td>
</tr>
</tbody>
</table>
<p align="center"><strong>Informations du demandeur du cadeau</strong></p>
<table style="width: 100%; margin-left: auto; margin-right: auto;" cellspacing="2" cellpadding="2">
<tbody>
<tr>
<td style="width: 18.8137109795479%; vertical-align: top;">Votre prénom et nom</td>
<td style="width: 51.1862890204521%; vertical-align: top;"> <input id="id_firstname1" name="firstname1" required="" size="20" type="text" placeholder="Prénom" />  <input id="id_lastname1" name="lastname1" required="" size="20" type="text" placeholder="Nom" /></td>
</tr>
<tr>
<td style="width: 18.8137109795479%; vertical-align: top;">Votre Email </td>
<td style="width: 51.1862890204521%; vertical-align: top;"><input id="id_contactmail" style="width: 85%;" name="contactmail" required="" type="email" placeholder="votre email" /> </td>
</tr>
<tr>
<td style="width: 18.8137109795479%; vertical-align: top;">Votre téléphone</td>
<td style="width: 51.1862890204521%; vertical-align: top;"><input id="id_contactphone" style="width: 85%;" name="contactphone" type="tel" placeholder="+32 412 123 123" /></td>
</tr>
</tbody>
</table>
<p align="center"><strong>Informations du destinataire du cadeau</strong></p>
<table style="width: 100%; margin-left: auto; margin-right: auto;"  cellspacing="2" cellpadding="2">
<tbody>
<tr>
<td style="width: 18.8137109795479%; vertical-align: top;">Destinataire du cadeau</td>
<td style="width: 51.1862890204521%; vertical-align: top;"> <input id="id_firstname2" name="firstname2" size="20" type="text" placeholder="Prénom" />  <input id="id_lastname2" name="lastname2" size="20" type="text" placeholder="Nom" /></td>
</tr>
<tr>
<td style="width: 18.8137109795479%; vertical-align: top;">Email (Optionel)</td>
<td style="width: 51.1862890204521%; vertical-align: top;"><input id="id_destinatairemail" style="width: 85%;" name="destinatairemail" type="email" placeholder="Email destinataire" /> </td>
</tr>
<tr>
<td style="width: 18.8137109795479%; vertical-align: top;">Téléphone (Optionel)</td>
<td style="width: 51.1862890204521%; vertical-align: top;"><input id="id_destinatairephone" style="width: 85%;" name="destinatairephone" type="tel" placeholder="+32 412 123 123" /></td>
</tr>
</tbody>
</table>
<p align="center"><strong>Adresse livraison du bon cadeau</strong></p>
<table style="width: 100%;">
<tbody>
<tr>
<td style="width: 25.770293824701195%;">Envoyer à l’adresse du :</td>
<td style="width: 71.2297061752988%;"><select id="id_destinataire" name="nom_destinataire">
<option disabled="disabled" selected="selected" value="">Choisissez le destinataire</option>
<option value="demandeur cadeau">Demandeur du cadeau</option>
<option value="destinataire cadeau">Destinataire du cadeau</option>
</select></td>
</tr>
<tr>
<td style="width: 25.770293824701195%;">Rue et numéro:</td>
<td style="width: 71.2297061752988%;"><input id="id_rue" style="width: 70%;" name="rue" required="" size="30" type="text" placeholder="rue" /> <input id="id_boitelettre" style="width: 20%;" name="boitelettre" required="" size="10" type="text" placeholder="boite lettre" /></td>
</tr>
<tr>
<td style="width: 25.770293824701195%;">Code Postal:</td>
<td style="width: 71.2297061752988%;"><input id="id_codepostal" name="codepostal" required="" size="7" type="text" placeholder="code postal" /> </td>
</tr>
<tr>
<td style="width: 25.770293824701195%;">Ville:</td>
<td style="width: 71.2297061752988%;"><input id="id_ville" name="ville" required="" size="20" type="text" placeholder="Ville" /> </td>
</tr>
<tr>
<td style="width: 25.770293824701195%;">Pays:</td>
<td style="width: 71.2297061752988%;"><input id="id_pays" name="pays" size="20" type="text" value="Belgique" /> </td>
</tr>
</tbody>
</table>
<p> </p>
<table style="width: 100%; margin-left: auto; margin-right: auto;"  cellspacing="2" cellpadding="2">
<tbody>
<tr>
<td style="width: 18.8137109795479%; vertical-align: top;">Remarques</td>
<td style="width: 51.1862890204521%; vertical-align: top;"><textarea style="width: 85%;" cols="40" name="remarque" rows="5" placeholder="vos remarques"></textarea> </td>
</tr>
<tr>
<td style="width: 18.8137109795479%; vertical-align: top;">Message associé au bon cadeau</td>
<td style="width: 51.1862890204521%; vertical-align: top;"><textarea style="width: 85%;" cols="40" name="message" rows="5" placeholder="votre message au destinataire du cadeau"></textarea> </td>
</tr>
</tbody>
</table>
<p id="id_pleaseWait" style="font-size: 16pt; color: #ff0000;">Please wait</p>
<p align="center">      <input id="id_submit" type="submit" value="Envoyer la demande" />     Nous vous contacterons dans les plus brefs délais.</p>
</form>
<p><span style="font-family: courier new, courier, monospace;">
<script src="https://www.spa-aviation.be/resa/voldecouverte/offrir_bons.js"></script></span></p>
<script src="https://www.spa-aviation.be/resa/voldecouverte/offrir_circuits.js"></script>
<script src="https://www.spa-aviation.be/resa/voldecouverte/tarifs.js"></script>
<script src="https://www.spa-aviation.be/resa/voldecouverte/script_offrir.js"></script>
<?php
}
?>
