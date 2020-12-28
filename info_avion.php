<?php
// This PHP script is fully integrated as a component of Joomla
// Developped by Joseph La Chine in 2013, Eric Vyncke 2014/2020
/*
   Copyright Joseph La China 2013-2014
   Copyright 2014-2020 Eric Vyncke
   Copyright 2020 Patrick Reginster

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

require_once 'dbi.php' ; // In order to get the journalise() function as well as the groups ID

$savesuccess = '' ;

function getAvion2() {
	global $language ;
	
	$path = &JFactory::getURI()->getPath();
	if (strpos($path, 'index.php/en') !== FALSE)
		$language = 'en' ;
	else
		$language = 'fr' ;	
	$length = strlen($path);
	$alias = $path;   
	for ($i = $length - 1; $i >= 0 ; $i--)
	{
		if ($path[$i] == '/') 
		{
			$alias =  substr($path, $i + 1, $length - $i - 1);
		}
	}

	$exp = explode('/', $alias);

	return strtoupper($exp[count($exp)-1]);
}

function update_aircraft($avion, $cout, $compteur, $compteur_vol_valeur, $entretien, $type_entretien, 
	$consommation, $fabrication, $cn, $limite_moteur_12ans, $limite_moteur_heure,
	$limite_helice, $limite_magnetos, $pesage, $commentaire, $poids, $bras, $wb_date) {
	global $db, $savesuccess, $user ;

	try {
		// Tous les avions n'ont pas un compteur_vol
		if (! isset($compteur_vol_valeur) or $compteur_vol_valeur == '')
			$compteur_vol_valeur = 'NULL' ;
		// Tout d'abord, les renseignements généraux
		$query = $db->getQuery(true);
		$fields = array(
			$db->quoteName('cout') . "= $cout",
			$db->quoteName('compteur'). "= $compteur",
			$db->quoteName('compteur_vol_valeur'). "= $compteur_vol_valeur",
			$db->quoteName('entretien'). "= $entretien",
			$db->quoteName('type_entretien'). "= " . $db->quote(web2db($type_entretien))
		);
		$fields[] =	$db->quoteName('consommation'). "= " . $db->quote(web2db($consommation)) ;
		$fields[] =	$db->quoteName('fabrication'). "= " . $db->quote(web2db($fabrication)) ;
		$fields[] =	$db->quoteName('cn'). "= " . $db->quote(web2db($cn)) ;
		$fields[] =	$db->quoteName('limite_moteur_12ans'). "= " . $db->quote(web2db($limite_moteur_12ans)) ;
		$fields[] =	$db->quoteName('limite_moteur_heure'). "= " . $db->quote(web2db($limite_moteur_heure)) ;
		$fields[] =	$db->quoteName('limite_helice'). "= " . $db->quote(web2db($limite_helice)) ;
		$fields[] =	$db->quoteName('limite_magnetos'). "= " . $db->quote(web2db($limite_magnetos)) ;
		$fields[] =	$db->quoteName('pesage'). "= " . $db->quote(web2db($pesage)) ;
		if ($commentaire !== FALSE)
			$fields[] =	$db->quoteName('commentaire'). "= " . $db->quote(web2db($commentaire)) ;
		$conditions = array(
			$db->quoteName('id') . 'like \'' . $avion . '\''
		);
		$query->update($db->quoteName('rapcs_planes'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$result = $db->query();
		if ($db->getAffectedRows() > 0) { // Si changement, faisons un audit
			$savesuccess .= "<font color='green'>Modification enregistr&eacute;e pour $avion.</font><br/>";
			$query->clear();
			$fields = array(
				$db->quoteName('compteur_date'). '= \'' . date('Y-m-d H:i:s') . '\'',
				$db->quoteName('compteur_qui'). '= \'' . $user->name . '\'',
				$db->quoteName('compteur_ip'). '= \'' . getClientAddress() . '\''
			);
			$conditions = array(
				$db->quoteName('id') . 'like \'' . $avion . '\''
			);
			$query->update($db->quoteName('rapcs_planes'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$result = $db->query();
			// Et gardons l'historique à toutes fins utiles
			$query->clear();
			$columns = array('plane', 'compteur', 'compteur_date', 'compteur_qui') ;
			$values = array("'$avion'", (! isset($compteur_vol_valeur) or $compteur_vol_valeur == '' or $compteur_vol_valeur == 0) ? $compteur : $compteur_vol_valeur, "'" . date('Y-m-d') . "'", "'$user->name'");
			$query->insert($db->quoteName('rapcs_planes_history'))->columns($db->quoteName($columns))->values(implode(',', $values));
			$db->setQuery($query);
			$result = $db->query();
			if($db->getErrorNum())
				$savesuccess .= "<font color=red>AUDIT history FAILED for $avion: " . $db->getErrorMsg() . "</font><br/>";
			// log a la facon de journalise()
			$query->clear() ;
			$columns = array('j_datetime', 'j_address', 'j_jom_id', 'j_severity', 'j_message') ;
			$values = array('sysdate()', "'" . getClientAddress() . "'", $user->id, "'I'", 
				"'Information about $avion has been changed cout: $cout, compteur: $compteur/$compteur_vol_valeur entretien: $entretien, type: $type_entretien'") ;
//				"'Information about $avion has been changed cout: $cout, compteur: $compteur/$compteur_vol_valeur entretien: $entretien, type: $type_entretien, comment: " . $db->quote(web2db($commentaire)) . "'") ;
			$query->insert($db->quoteName('rapcs_journal'))->columns($db->quoteName($columns))->values(implode(',', $values));
			$db->setQuery($query);
			$result = $db->query();
			if($db->getErrorNum())
				$savesuccess .= "<font color=red>JOURNALING FAILED for $avion: " . $db->getErrorMsg() . "</font><br/>";
		} 		
		// Puis, la partie masse et centrage
		$wb_dates = explode('-', $wb_date) ;
		$query = $query->clear();
		$fields = array(
			$db->quoteName('poids'). " = $poids",
			$db->quoteName('bras'). " = $bras",
			$db->quoteName('wb_date'). " = '$wb_dates[2]-$wb_dates[1]-$wb_dates[0]'"
		);
		$conditions = array(
			$db->quoteName('id') . 'like \'' . $avion . '\''
		);
		$query->update($db->quoteName('rapcs_planes'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$result = $db->query();
		
		if ($db->getAffectedRows() > 0) { // Si changement, faisons un audit
			$savesuccess .= "<font color='green'>Modification W&B enregistr&eacute;e pour $avion</font><br/>";
			$query = $db->getQuery(true);
			$fields = array(
				$db->quoteName('wb_qui'). '= \'' . $user->name . '\''
			);
			$conditions = array(
				$db->quoteName('id') . 'like \'' . $avion . '\''
			);
			$query->update($db->quoteName('rapcs_planes'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$result = $db->query();
		}
		

		// Reste encore la partie Tipping Point ...
		// Tout d'abord, il nous faut l'ID dans le système Tipping Point
		$query->clear();
		$query->select("*");
		$query->from('tp_aircraft');
		$query->where("tailnumber like '$avion'");
		$db->setQuery($query);
		$info = $db->loadObjectList();
		$tp_aircraft_id = $info[0]->id ;
		
		// Puis, modifions les renseignements généraux	
		$query->clear();
		$fields = array(
			"emptywt = $poids",
			"emptycg = $bras"
		);
		$conditions = array(
			"id = $tp_aircraft_id"
		);
		$query->update($db->quoteName('tp_aircraft'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$result = $db->query();

		// Ensuite, le tableau des chargement	
		$query->clear();
		$fields = array(
			"weight = $poids",
			"arm = $bras"
		);
		$conditions = array(
			"tailnumber = $tp_aircraft_id",
			"emptyweight = true"
		);
		$query->update($db->quoteName('tp_aircraft_weights'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$result = $db->query();

		// Finalement, un peu d'audit...	
		$query->clear();
		$columns = array('id', 'timestamp', 'who', 'what') ;
		$values = array('NULL', 'CURRENT_TIMESTAMP', "'$user->name'", 
			"'$avion: UPDATE tp_aircraft_weights SET weight = $poids, arm = $bras WHERE id = $tp_aircraft_id and emptyweight = true'"
		);
		$query->insert($db->quoteName('tp_audit'))->columns($db->quoteName($columns))->values(implode(',', $values));
		$db->setQuery($query);
		$result = $db->query();
		if($db->getErrorNum())
			$savesuccess .= "<font color=red>AUDIT tipping point FAILED for $avion: " . $db->getErrorMsg() . "</font><br/>";
				
	} catch(Exception $e) {
print("EVY: exception " .  $e->getMessage()) ;
		$savesuccess .= "<font color='red'>$avion: Erreur lors de l'enregistrement des donn&eacute;es, veuillez r&eacute;essayer ou contact l'administrateur du site.</font><BR/>" . $e->getMessage();
	}
}

global $user ;

$user = &JFactory::getUser(); //gets user object

$canview = FALSE ;
$canedit = FALSE ;

foreach ($user->groups as $key => $value) {
	if (($key == $joomla_pilot_group) or ($key == $joomla_student_group) or ($key == $joomla_member_group)) 
		$canview = TRUE  ;
	if (($key == $joomla_instructor_group) or ($key == $joomla_mechanic_group) or ($key == $joomla_admin_group)) { 
		$canedit = TRUE ;  
		$canview = TRUE ;
	}
}

if ($user->username == 'evyncke') $canedit = TRUE ; // For testing purposes

// Should use a full set of  JRequest::getString() to handle '
if($canedit and isset($_POST["Enregistrer"]) and $_POST["Enregistrer"] == "Enregistrer") {
	global $db, $savesuccess ;
	$db = &JFactory::getDBO();
	$savesuccess = '' ;
	update_aircraft(getAvion2(), $_POST['cout'], $_POST['compteur'], $_POST['compteur_vol_valeur'], $_POST['entretien'], $_POST['type_entretien'], 
		$_POST['consommation'], $_POST['fabrication'], $_POST['cn'], $_POST['limite_moteur_12ans'], $_POST['limite_moteur_heure'], $_POST['limite_helice'],
		$_POST['limite_magnetos'], $_POST['pesage'], 
		$_POST['commentaire'], $_POST['poids'], $_POST['bras'], $_POST['wb_date']) ;
} else if ($canedit and isset($_POST["Enregistrer_tout"]) and $_POST["Enregistrer_tout"] != "") {
	global $db, $savesuccess ;
	$db = &JFactory::getDBO();
	$savesuccess = '' ;
	foreach($_POST[plane] as $i=>$id) {
		$cout = $_POST['cout'][$i] ;
		$compteur = $_POST['compteur'][$i] ;
		$compteur_vol_valeur = $_POST['compteur_vol_valeur'][$i] ;
		$entretien = $_POST['entretien'][$i] ;
		$type_entretien = $_POST['type_entretien'][$i] ;
		$consommation = $_POST['consommation'][$i] ;
		$fabrication = $_POST['fabrication'][$i] ;
		$cn = $_POST['cn'][$i] ;
		$limite_moteur_12ans = $_POST['limite_moteur_12ans'][$i] ;
		$limite_moteur_heure = $_POST['limite_moteur_heure'][$i] ;
		$limite_helice = $_POST['limite_helice'][$i] ;
		$limite_magnetos = $_POST['limite_magnetos'][$i] ;
		$pesage = $_POST['pesage'][$i] ;
		$poids = $_POST['poids'][$i] ;
		$bras = $_POST['bras'][$i] ;
		$wb_date = $_POST['wb_date'][$i] ;
		update_aircraft($id, $cout, $compteur, $compteur_vol_valeur, $entretien, $type_entretien, 
			$consommation, $fabrication, $cn, $limite_moteur_12ans, $limite_moteur_heure,
	 		$limite_helice, $limite_magnetos, $pesage, FALSE, $poids, $bras, $wb_date) ;
	}
}

if ($canview) {
	$avion = getAvion2();

       // Access the DB to get the official CT values
	$db = &JFactory::getDBO();
	$query = $db->getQuery(true);
	$query->select("*, date_format(compteur_date,'%e-%c-%Y') as date, date_format(wb_date,'%e-%c-%Y') as wb_date2");
	$query->from('rapcs_planes');
	$query->where("id like '$avion'");
	$db->setQuery($query);
	if($db->getErrorNum())
	{
		echo $db->getErrorMsg();
		exit;
	}
	$info = $db->loadObjectList();

      // Access the DB to get the non-official CT values from logbook
	$query->clear();
	$query->select("*,date_format(l_audit_time,'%e-%c-%Y') as date");
	$query->from('rapcs_logbook');
	$query->join('left', 'jom_users as u on l_pilot = u.id');
	$query->where("l_plane like '$avion' and l_end_hour is not null and l_end_hour != ''");
        // $query->order("l_end_hour desc, l_end_minute desc") ;
        $query->order("l_audit_time desc") ;
        $query->setLimit('1') ; // Only the first row is meaningful
        $db->setQuery($query);
	if($db->getErrorNum())
	{
		echo $db->getErrorMsg();
		exit;
	}
	$info_logbook = $db->loadObjectList();
	
	if ($canedit and isset($_POST["edit"]) and $_POST['edit'] == 'true') {
		?>
		<FORM METHOD="post">
		<TABLE CELLSPACING=0 CELLPADDING=0 BORDER=0>
		<TR><TD><B>Prix/Minute:</B></TD><TD><INPUT type="text" name="cout" value="<?=$info[0]->cout?>"> &euro;/min (<i>Mettre un '.' d&eacute;cimal dans le prix</i>)</TD></TR>
		<TR><TD><B>Dernier relev&eacute; moteur CT (admin):</B></TD><TD><INPUT type="number" name="compteur" value="<?=$info[0]->compteur?>"> </TD></TR>
		<TR><TD><B>Dernier relev&eacute; vol (admin):</B></TD><TD><INPUT type="number" name="compteur_vol_valeur" value="<?=$info[0]->compteur_vol_valeur?>"> </TD></TR>
		<TR><TD><B>Prochaine immobilisation:</B></TD><TD><INPUT type="number" name="entretien" value="<?=$info[0]->entretien?>"> pour
		      <INPUT type="text" name="type_entretien" value="<?=db2web($info[0]->type_entretien)?>"></TD></TR>
		<TR><TD><B>Consommation:</B></TD><TD><INPUT type="number" step="0.1" name="consommation" value="<?=$info[0]->consommation?>"> litres/heure </TD></TR>
		<TR><TD><B>Certificat de navigabilité:</B></TD><TD><INPUT type="text" name="cn" value="<?=$info[0]->cn?>"> </TD></TR>
		<TR><TD><B>Ann&eacute;e de fabrication:</B></TD><TD><INPUT type="text" name="fabrication" value="<?=$info[0]->fabrication?>"> </TD></TR>
		<TR><TD><B>Limite moteur 12 ans:</B></TD><TD><INPUT type="date" name="limite_moteur_12ans" value="<?=$info[0]->limite_moteur_12ans?>"> </TD></TR>
		<TR><TD><B>Limite moteur:</B></TD><TD><INPUT type="number" name="limite_moteur_heure" value="<?=$info[0]->limite_moteur_heure?>"> </TD></TR>
		<TR><TD><B>Limite h&eacute;lice:</B></TD><TD><INPUT type="text" name="limite_helice" value="<?=$info[0]->limite_helice?>"> </TD></TR>
		<TR><TD><B>Limite magn&eacute;tos:</B></TD><TD><INPUT type="text" name="limite_magnetos" value="<?=$info[0]->limite_magnetos?>"> </TD></TR>	
		<TR><TD><B>Prochain pesage:</B></TD><TD><INPUT type="date" name="pesage" value="<?=$info[0]->pesage?>"> </TD></TR>
		<TR><TD><B>Weight and Balance:</B></TD><TD> poids &agrave; vide <INPUT type="number" step="0.01" name="poids" value="<?=$info[0]->poids?>">  livres, 
		      bras: <INPUT type="number" step="0.01" name="bras" value="<?=$info[0]->bras?>"> pouces,<br/>
		      date modif: <input type="date" name="wb_date" value="<?=$info[0]->wb_date2?>"> <i>(format JJ-MM-AAAA)</i></TD></TR>
		<TR><TD colspan="2"><B>Commentaire:</B></TD></TR>
		<TR><TD colspan="2"><textarea name="commentaire" rows="4" cols="50"><?=db2web($info[0]->commentaire)?></textarea></TD></TR>
		</TABLE>
		<input type="submit" name="Enregistrer" value="Enregistrer">
		</FORM>
		<?PHP
	} elseif ($canedit and isset($_POST["edit_all"]) and $_POST["edit_all"] == 'true') {
        	$query = $db->getQuery(true) ;
	        $query->select("*, upper(id) as id, date_format(wb_date,'%e-%c-%Y') as wb_date2") ;
	        $query->from('rapcs_planes') ;
            $query->where('actif != 0') ;
            $query->order('id') ;
	        $db->setQuery($query);
	        if($db->getErrorNum()) {
		       echo $db->getErrorMsg();
		       exit;
	        }
	        $all_planes = $db->loadObjectList();
		?>
		<FORM METHOD="post">
		<TABLE  BORDER="1">
	        <tr style="background-color: lightblue;"><td style="">Avion</td>
	        	<td>Co&ucirc;t</td>
	        	<td>Dernier CT moteur</td><td>Dernier index vol</td><td>Prochaine immobilisation</td><td>Type<br/>entretien</td>
	        	<td>Consommation</td><td>Fabrication</td><td>CN</td><td>Limite moteur<br/>12 ans</td><td>Limite moteur<br/>heure</td><td>Limite<br/>h&eacute;lice</td><td>Pesage</td>
	        	<td>Poids &agrave; vide<br/>(pounds)</td><td>Bras<br/>(inches)</td><td>Date<br/>(JJ-MM-AAAA)</td>
	        	</tr>
	        <?php
	            foreach($all_planes as $i=>$plane) {
	            	$style = ($i % 2 == 0) ? '' : 'style="background-color: white;"' ;
	                print("<tr $style><td style=\"white-space:nowrap;\"><b>". strtoupper($plane->id) . 
	                	"</b><input type=hidden name=\"plane[$i]\" value=\"$plane->id\"></td>
	                	<td><input type=text name=\"cout[$i]\" value=\"$plane->cout\" size=5 maxlength=5></td>
		                <td><input type=number name=\"compteur[$i]\" value=\"$plane->compteur\" size=5 maxlength=5></td>
		                <td><input type=number name=\"compteur_vol_valeur[$i]\" value=\"$plane->compteur_vol_valeur\" size=5 maxlength=5></td>
		                <td><input type=number name=\"entretien[$i]\" value=\"$plane->entretien\" size=5 maxlength=5></td>
		                <td><input type=text name=\"type_entretien[$i]\" value=\"" . db2web($plane->type_entretien) . "\"></td>
		                <td><input type=number step=\"0.1\" name=\"consommation[$i]\" value=\"$plane->consommation\" size=3></td>
		                <td><input type=number name=\"fabrication[$i]\" value=\"$plane->fabrication\" size=5></td>
		                <td><input type=text name=\"cn[$i]\" value=\"$plane->cn\" size=4></td>
		                <td><input type=date name=\"limite_moteur_12ans[$i]\" value=\"$plane->limite_moteur_12ans\" size=8></td>
		                <td><input type=number name=\"limite_moteur_heure[$i]\" value=\"$plane->limite_moteur_heure\" size=8></td>
		                <td><input type=number name=\"limite_helice[$i]\" value=\"$plane->limite_helice\" size=5></td>
						<td><input type=number name=\"limite_magnetos[$i]\" value=\"$plane->limite_magnetos\" size=5></td>
		                <td><input type=date  name=\"pesage[$i]\" value=\"$plane->pesage\" size=5></td>
		                <td><input type=number step=\"0.01\" name=\"poids[$i]\" value=\"$plane->poids\" size=8></td>
		                <td><input type=number step=\"0.01\" name=\"bras[$i]\" value=\"$plane->bras\" size=8></td>
		                <td><input type=date name=\"wb_date[$i]\" value=\"$plane->wb_date2\" size=10></td>
		                </tr>\n") ;
	            }
	         ?>
		</TABLE>
		<input type="submit" name="Enregistrer_tout" value="Enregistrer tous les changements">
		</FORM>
		<?PHP
	} else {
		switch ($info[0]->actif) {
			case 0: print('<span style="color: red; font-weight:bold;">Cet avion n\'est pas actif pour l\'instant (par exemple
				hors assurance...) INTERDICTION DE VOLER AVEC</span>') ;
				break ;
			case 1: break ; // Normal case ;-)
			case 2: print('<span style="color: red; font-weight:bold;">Cet avion ne peut &ecirc;tre r&eacute;serv&eacute; que
				par les instructeurs.</span>') ;
				break ;
		}
		?>
		<UL>
		  <LI><B>Prix/Minute (Euro):</B> <?= $info[0]->cout . " &euro;/min (" . $info[0]->cout*60 ." &euro;/heure)"?></LI>
		  <?php if ($info[0]->sous_controle) { ?>
			<LI><B>Dernier relev&eacute; index moteur (admin):</B> <?= $info[0]->compteur?>h <i> mis &agrave; jour le <?=$info[0]->date?> par <?=$info[0]->compteur_qui?></i></LI>
			<LI><B>Dernier relev&eacute; index moteur (pilote):</B> <?= $info_logbook[0]->l_end_hour . "h" .$info_logbook[0]->l_end_minute ?>m <i> mis &agrave; jour le <?=$info_logbook[0]->date?> par <?=$info_logbook[0]->name?></i></LI>
			<?php if ($info[0]->compteur_vol) { ?>
			<LI><B>Dernier relev&eacute; index vol (admin):</B> <?= $info[0]->compteur_vol_valeur?>h <i> mis &agrave; jour le <?=$info[0]->date?> par <?=$info[0]->compteur_qui?></i></LI>
			<LI><B>Dernier relev&eacute; index vol (pilote):</B> <?= $info_logbook[0]->l_flight_end_hour . "h" .$info_logbook[0]->l_flight_end_minute ?>m <i> mis &agrave; jour le <?=$info_logbook[0]->date?> par <?=$info_logbook[0]->name?></i></LI>
			<?php } ?>
			<LI><a style="text-decoration: underline;color: blue;" href="/resa/plane_chart.php?id=<?=$avion?>">Graphe</A> de l'&eacute;volution des compteurs</LI>
			<LI><B>Prochaine immobilisation:</B> <?=db2web($info[0]->entretien)?>h pour <?=db2web($info[0]->type_entretien)?>
				<span style="color: red; font-weight: bold;">Interdiction de d&eacute;passer l'&eacute;ch&eacute;ance (sauf autorisation de l'atelier).</span></LI>
		  <?php } else { ?>
		  	<li><span style="color: red;">Cet avion n'est pas sous le contr&ocirc;le de notre atelier. A vous d'assurer de ne 
		  		pas d&eacute;passer les heures.</span></li>
		  <?php } ?>
			<LI><B>Consommation:</B> <?=$info[0]->consommation?> litres/heure</LI>
			<LI><B>Certificat de navigabilit&eacute;:</B> <?=$info[0]->cn?></LI>
			<LI><B>Ann&eacute;e de fabrication:</B> <?=$info[0]->fabrication?></LI>
			<LI><B>Limite moteur 12 ans:</B> <?=$info[0]->limite_moteur_12ans?></LI>
			<LI><B>Limite moteur:</B> <?=$info[0]->limite_moteur_heure?></LI>
			<LI><B>Limite h&eacute;lice:</B> <?=$info[0]->limite_helice?></LI>
			<LI><B>Limite magn&eacute;tos:</B> <?=$info[0]->limite_magnetos?></LI>
			<LI><B>Prochain pesage:</B> <?=$info[0]->pesage?></LI>
			<LI><B>Masse et centrage (<a style="text-decoration: underline;color: blue;" 
				href="/TippingPoint/index.php?tailnumber=<?=$avion?>">Calcul en ligne</a>):</B>
				 poids &agrave; vide <?= $info[0]->poids; ?> livres, bras <?= $info[0]->bras?> pouces
				<i> date: <?=$info[0]->wb_date2?></i></LI>
			<LI><B>Commentaire:</B><BR> <?= nl2br(db2web($info[0]->commentaire))?></LI>
		</UL>
                
		<?
		if($canedit)
		{
		?>
		<form id='update' method="post">
			<input type="hidden" name="edit" value="true"/>
			<A style="text-decoration: underline;color: blue;" HREF="#" onclick='document.getElementById("update").submit()'><IMG SRC="/media/system/images/edit.png">
			Mettre &agrave; jour cet avion </A>
		</form>
		<form id='update_all' method="post">
			<input type="hidden" name="edit_all" value="true"/>
			<A style="text-decoration: underline;color: blue;" HREF="#" onclick='document.getElementById("update_all").submit()'>
				<IMG SRC="/media/system/images/edit.png"><IMG SRC="/media/system/images/edit.png"><IMG SRC="/media/system/images/edit.png">
				Mettre &agrave; jour tous les avions <IMG SRC="/media/system/images/edit.png"><IMG SRC="/media/system/images/edit.png"></A>
		</form>
			<a style="text-decoration: underline;color: blue;" 
				href="/TippingPoint/admin.php">Mettre à jour l'enveloppe 'masse et centrage'</a>
		<?
		}
	}
	echo "</P>";
	
	echo $savesuccess;
}

?>
