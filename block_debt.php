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

require_once 'dbi.php';

if (! $userIsBoardMember) journalise($userId, "F", "Vous n'avez pas le droit de consulter cette page") ; // journalise with Fatal error class also stop execution
ob_start("ob_gzhandler"); // Enable gzip compression over HTTP

$max_debt = 1050 ;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <title>Bloquer les mauvais payeurs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://www.spa-aviation.be/favicon32x32.ico" rel="shortcut icon" type="image/vnd.microsoft.icon">
</head>
<html>
    <h1>Blocage des mauvais payeurs (dette > <?=$max_debt?> @euro;)</h1>

<p>Voici la liste des mauvais payeurs qui sont dorénavant interdits de réservations. <ul>
<?php
	$sql = "select distinct u.id as id, u.name as name, first_name, last_name,ciel_code, block, bkb_amount, b_reason, u.email as email, group_concat(group_id) as groups
		from $table_users as u join $table_user_usergroup_map on u.id=user_id 
		join $table_person as p on u.id=p.jom_id
		left join $table_bk_balance on concat('400',ciel_code)=bkb_account
		left join $table_blocked on u.id = b_jom_id
		where group_id in ($joomla_member_group, $joomla_student_group, $joomla_pilot_group, $joomla_effectif_group)
		and (bkb_date is null or bkb_date=(select max(bkb_date) from $table_bk_balance))
        and bkb_amount > $max_debt
		group by user_id
		order by last_name, first_name" ;
		$count=0;
	$result = mysqli_query($mysqli_link, $sql)
		or journalise(0, "E", "Cannot read members: " . mysqli_error($mysqli_link)) ;
	
	
	while ($row = mysqli_fetch_array($result)) {
        print("<li>" . db2web("$row[last_name] $row[first_name]") . " ($row[email] #$row[id]): $row[bkb_amount] &euro;</li>\n") ;
        mysqli_query($mysqli_link, "INSERT INTO $table_blocked(b_jom_id, b_reason, b_who, b_when)
            VALUES($row[id], '" . web2db("Votre solde débiteur membre dépasse le montant de $max_debt, vous êtes interdit(e) de réservation tant que ce solde n\'est pas réglé.") . "',  
                $userId, SYSDATE())") ; // or print("Error SQL: " . db2web(mysqli_error($mysqli_link)));
        journalise($userId, "I", "Blocage de #$row[id]: " . db2web("$row[last_name] $row[first_name]")) ;
    }
?>
</ul>
</p>
</body>
</html>