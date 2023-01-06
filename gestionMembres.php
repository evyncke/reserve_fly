<?php
require_once 'dbi.php';

print("userIsBoardMember=$userIsBoardMember ; userIsAdmin=$userIsAdmin; userId=$userId</br>");
if (! $userIsAdmin && ! $userIsBoardMember) journalise($userId, "F", "Vous n'avez pas le droit de consulter cette page") ; // journalise with Fatal error class also stop execution
// ob_start("ob_gzhandler"); // Enable gzip compression over HTTP

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <title>Gestion des utilisateurs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://www.spa-aviation.be/favicon32x32.ico" rel="shortcut icon" type="image/vnd.microsoft.icon">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
<script>
</script>
<p> </p>
<table width="100%" style="margin-left: auto; margin-right: auto;" class="table table-striped table-hover table-bordered"> 
<tbody>
<!--><tr style="background-color: #13d8f2; text-align: Center;">-->
<tr style="text-align: Center;">
<th style="text-align: right;">#</th>
<th>Id</th>
<th>Nom</th>
<th>Ref. Ciel</th>
<th>Membre actif</th>
<th>Elève</th>
<th>Pilote</th>
<th>Effectif</th>
<th>Solde</th>
<th>Bloqué</th>
<th>Status</th>
</tr>

<?php
// ajouter block (Pour les pilotes bloque) + inverser les soldes.
	$sql = "select distinct u.id as id, u.name as name, first_name, last_name,ciel_code, block, bkb_amount, b_reason, u.email as email, group_concat(group_id) as groups
		from $table_users as u join $table_user_usergroup_map on u.id=user_id 
		join $table_person as p on u.id=p.jom_id
		left join $table_bk_balance on concat('400',ciel_code)=bkb_account
		left join $table_blocked on u.id = b_jom_id
		where group_id in ($joomla_member_group, $joomla_student_group, $joomla_pilot_group, $joomla_effectif_group)
		and (bkb_date is null or bkb_date=(select max(bkb_date) from $table_bk_balance))
		group by user_id
		order by last_name, first_name" ;
		$count=0;
	$result = mysqli_query($mysqli_link, $sql)
		or journalise(0, "E", "Cannot read members: " . mysqli_error($mysqli_link)) ;
	
	$memberCount=0;
	$studentCount=0;
	$effectifCount=0;
	$pilotCount=0;
	$cielCount=0;
	$blockedCount=0;
	$soldeTotal=0.0;
	
	$CheckMark="&#9989";
	
	while ($row = mysqli_fetch_array($result)) {
		$count++;
		$userid=$row[id];
		$row['name'] = db2web($row['name']) ;
		if ($row['name'] === FALSE) 
			journalise(0, 'E', "There was an error while converting\n") ; 
		$row['first_name'] = db2web($row['first_name']) ;
		$row['last_name'] = db2web($row['last_name']) ;
		$groups = explode(',', $row['groups']) ;
		$effectif = (in_array($joomla_effectif_group, $groups)) ? $CheckMark : '' ;
		$pilot = (in_array($joomla_pilot_group, $groups)) ? $CheckMark : '' ;
		$student = (in_array($joomla_student_group, $groups)) ? $CheckMark : '' ;
		$blocked=$row['block'];
		if($blocked==0) {
			$blocked='';
		}
		else {
			$blocked='&#x26D4;';
		}
		$member=$CheckMark;
		if($pilot == $CheckMark || $student== $CheckMark) {
			$member='';
		}
		$status=db2web($row['b_reason']);
		$solde=$row['bkb_amount'];
		$solde=$solde*-1.00;
		if(abs($solde)<0.01) $solde=0.0;
		
		//SELECT * FROM `rapcs_bk_balance`.   bkb_amount
		//SELECT * FROM `rapcs_bk_balance` ORDER BY `rapcs_bk_balance`.`bkb_date` DESC
		
		$ciel="";
		if($row['ciel_code'] != "") {
			$ciel="400".$row['ciel_code'];
		}

		$soldeTotal+=$solde;
		if($member == $CheckMark) $memberCount++;
		if($student == $CheckMark) $studentCount++;
		if($pilot == $CheckMark) $pilotCount++;
		if($effectif == $CheckMark) $effectifCount++;
	    if($ciel != '') $cielCount++;
		if($blocked != '') $blockedCount++;
		$soldeStyle='';
		$dangerRowStyle="";
		if($solde<0.0) {
			$soldeStyle="style='color: red';";
			$dangerRowStyle="class='danger'";
		}
		print("<tr style='text-align: right; $dangerRow'>
			<td>$count</td>
		    <td style='text-align: right;'>$userid</td>
			<td style='text-align: left;'>$row[last_name] $row[first_name]</td>
			<td style='text-align: left;'>$ciel</td>
			<td style='text-align: center;'>$member</td>
			<td style='text-align: center;'>$student</td>
			<td style='text-align: center;'>$pilot</td>
			<td style='text-align: center;'>$effectif</td>");
		if($row['ciel_code'] != '') {
				print("<td $soldeStyle>$solde €</td>");				
		}
		else {			
			print("<td></td>");
		}
		print("<td style='text-align: center;font-size: 20px;'>$blocked</td>");
		/*
		if($solde<0.00) {
			print("<td style='text-align: left;'>&#x2757;$status</td>");
		}
		else {
			print("<td>$status</td>");			
		}
		*/
		print("<td style='text-align: left;'><select id='id_blocked_$userid' name='blocked_$userid'>
			<option value='OK''>OK</option>
		<option value='B1'>Solde trop négatif</option>
		<option value='B2'>Cotisation non payée</option>
		</select></td>");
		print("</tr>\n");
	}
	print("<tr style='background-color: #13d8f2; text-align: center;'>
		<td>Total</td>
	    <td></td>
		<td>$count</td>
		<td>$cielCount</td>
		<td>$memberCount</td>
		<td>$studentCount</td>
		<td>$pilotCount</td>
		<td>$effectifCount</td>");
	if($soldeTotal<0.0) {
		print("<td style='color: red;text-align: right;'>$soldeTotal €</td>");
	}
	else {
		print("<td style='text-align: right;'>$soldeTotal €</td>");		
	}
	print("<td>$blockedCount</td>
		<td></td>
		</tr>\n");
	print('</tbody></table></center>\n');
?>
</body>
</html>