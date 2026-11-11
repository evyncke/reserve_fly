<?php
function rapcs_reservation($reservationType, $circuit) {
//echo "<br>wpcode_reservation_if_init:started: $reservationType<br>";
$selectedDecouverte="";
$selectedInitiation="";
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

if($reservationType=="init") {
    $selectedInitiation="selected";
}
else if($reservationType=="if") {
    $selectedDecouverte="selected";
}
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
<form id="id_form" action="https://www.spa-aviation.be/resa/voldecouverte/action_reservation.php" method="POST">
<input id="id_form_language" style="width: 85%;" name="form_language" type="text" value="french" />
<table style="width: 100%; border-color: #000000; margin-left: auto; margin-right: auto;" cellspacing="2" cellpadding="2"><caption> </caption>
<tbody>
<tr>
<td style="width: 20%;">Type de réservation</td>
<td style="width: 80%;"><select id="id_typeofflight" style="width: 99%;" name="typeofflight" onchange="compute_reservation('id_typeofflight')">
<option disabled="disabled" value="">Choisissez</option>
<option value="vol_decouverte" <?=$selectedDecouverte?>>Promenade aérienne</option>
<option value="vol_initiation" <?=$selectedInitiation?>>Vol Initiation</option>
</select></td>
</tr>
<tr>
<td style="width: 20%;">Nombre de passagers:  </td>
<td style="width: 80%;"><input id="id_numberofpassagers" style="width: 20%;" max="3" min="1" name="numberofpassagers" size="3" type="number" value="1" /></td>
</tr>
<tr>
<td style="width: 20%;">Passagers</td>
<td style="width: 80%;">Prénom-Nom-Age-Poids</td>
</tr>
<tr>
<td style="width: 20%; vertical-align: top;">Passager principal</td>
<td style="width: 80%; vertical-align: top;">
    <input id="id_firstname1" name="firstname1" required="" size="20" type="text" placeholder="Prénom" /> 
    <input id="id_lastname1"  name="lastname1" required="" size="20" type="text" placeholder="Nom du contact" /><br>
&nbsp;<select name="age1">
<option disabled="disabled" selected="selected" value="">Age</option>
<option value="&lt; 12 ans">&lt; 12 ans</option>
<option value="&lt; 18 ans">&lt; 18 ans</option>
<option value="&gt;= 18 ans">&gt;= 18 ans</option>
</select>
<br><input max="120" min="20" name="weight1" size="3" type="number" value=""  placeholder="Poids" /></td>
</tr>
<tr id="id_passager2_row">
<td style="width: 20%; vertical-align: top;">Passager 2</td>
<td style="width: 80%; vertical-align: top;">
    <input name="firstname2" size="20" type="text" placeholder="Prénom" /> 
    <input name="lastname2" size="20" type="text" placeholder="Nom" /><br>
&nbsp;<select name="age2">
<option disabled="disabled" selected="selected" value="">Age</option>
<option value="&lt; 12 ans">&lt; 12 ans</option>
<option value="&lt; 18 ans">&lt; 18 ans</option>
<option value="&gt;= 18 ans">&gt;= 18 ans</option>
</select><br>
<input max="120" min="20" name="weight2" size="3" type="number" value=""  placeholder="Poids"/></td>
</tr>
<tr id="id_passager3_row">
<td style="width: 20%; vertical-align: top;">Passager 3</td>
<td style="width: 80%; vertical-align: top;">
    <input name="firstname3" size="20" type="text" placeholder="Prénom" /> 
    <input name="lastname3" size="20" type="text" placeholder="Nom" /><br>
&nbsp;<select  name="age3">
<option disabled="disabled" selected="selected" value="">Age</option>
<option value="&lt; 12 ans">&lt; 12 ans</option>
<option value="&lt; 18 ans">&lt; 18 ans</option>
<option value="&gt;= 18 ans">&gt;= 18 ans</option>
</select>
<input max="120" min="20" name="weight3" size="3" type="number" value="" placeholder="Poids"/></td>
</tr>
</tbody>
</table>
<p align="center"><strong>Informations de contact</strong></p>
<table style="width: 100%; margin-left: auto; margin-right: auto;" cellspacing="2" cellpadding="2">
<tbody>
<tr>
<td style="width: 30%; vertical-align: top;">Adresse email du contact:</td>
<td style="width: 70%; vertical-align: top;"><input id="id_contactmail" style="width: 85%;" name="contactmail" required="" type="email" placeholder="votre email" /> </td>
</tr>
<tr>
<td style="width: 30%; vertical-align: top;">Téléphone</td>
<td style="width: 70%; vertical-align: top;"><input id="id_contactphone" style="width: 85%;" name="contactphone" required="" type="tel" placeholder="+32 412 123 123" /></td>
</tr>
<tr id="id_circuit_row">
<td style="width: 30%; vertical-align: top;">Circuit souhaité:</td>
<td style="width: 70%; vertical-align: top;"><select style="width: 70%;" name="circuit">
<option disabled="disabled"  <?=$selectedcircuit?> value="">Choisissez votre circuit</option>
<option value="1" <?=$selectedcircuit1?> >1. Circuit Francorchamps</option>
<option value="2" <?=$selectedcircuit2?> >2. Francorchamps - Coo</option>
<option value="3" <?=$selectedcircuit3?> >3. Lac Robertville - Francorchamps</option>
<option value="4" <?=$selectedcircuit4?> >4. Lac de la Gileppe</option>
<option value="5" <?=$selectedcircuit5?> >5. Aywaille - Remouchamps</option>
<option value="6" <?=$selectedcircuit6?> >6. Tour de Verviers</option>
<option value="7" <?=$selectedcircuit7?> >7. Pays de Herve</option>
<option value="8" <?=$selectedcircuit8?> >8. Tour des lacs</option>
<option value="9" <?=$selectedcircuit9?> >9. Liége City</option>
<option value="10" <?=$selectedcircuit10?> >10. Vielsalm-La Roche</option>
<option value="11" <?=$selectedcircuit11?> >11. Barvaux - Durbuy</option>
<option value="12" <?=$selectedcircuit12?> >12. Vol libre</option>
<option value="13" <?=$selectedcircuit13?> >13. Vol Piper Cub</option>
</select></td>
</tr>
<tr id="id_date_row">
<td style="width: 30%; vertical-align: top;">Date souhaitée du vol</td>
<td style="width: 70%; vertical-align: top;"><input id="id_flightdate" name="flightdate" type="date" width="60%" placeholder="jj/mm/aaaa" /> ou <input name="flightdate2"  width="60%" type="date" placeholder="jj/mm/aaaa" /></td>
</tr>
<tr id="id_heure_row">
<td style="width: 30%; vertical-align: top;">Plage horaire</td>
<td style="width: 70%; vertical-align: top;"><select style="width: 60%;" name="heure">
<option disabled="disabled" selected="selected" value="">Choisissez votre heure préférée</option>
<option value="1">9h-12h</option>
<option value="2">12h-14h</option>
<option value="3">14h-17h</option>
<option value="4">17h-20h</option>
</select></td>
</tr>
<tr id="id_voldansles12mois_row" hidden="true">
<td style="width: 30%; vertical-align: top;">Voulez vous devenir membre?</td>
<td style="width: 70%; vertical-align: top;"><select id="id_voldansles12mois" name="voldansles12mois">
<option disabled="disabled" selected="selected" value=""></option>
<option value="1">non</option>
<option value="2">oui</option>
</select></td>
</tr>
<tr>
<td style="width: 30%; vertical-align: top;"> Remarques</td>
<td style="width: 70%; vertical-align: top;"><textarea style="width: 85%;" cols="40" name="remarque" rows="5" placeholder="vos remarques"></textarea> </td>
</tr>
</tbody>
</table>
<p align="center"><input id="id_submit" type="submit" value="Envoyer la demande" /> </p>
<p id="id_pleaseWait" style="font-size: 16pt; color: #ff0000;">Please wait</p>
</form>
<script src="https://www.spa-aviation.be/resa/voldecouverte/script_reservation.js">
function submitFunction1() {
    alert("The form was submitted");
}
</script>
<?php
}
?>
