<?php
/*
   Copyright 2022-2025 Patrick Reginster (and partially Eric Vyncke)

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.


   Gestion des membres deactives
*/
require_once 'dbi.php';
require_once 'odooFlight.class.php';
require_once 'mobile_tools.php';

// Cotisation computed for year 
if (! $userIsAdmin and ! $userIsBoardMember and !$userIsInstructor) 
	journalise($userId, "F", "Vous n'avez pas le droit de consulter cette page") ; // journalise with Fatal error class also stop execution

// In the mobile_header.php, $additional_preload is use to force a HTTP/2 preload of specific resources (faster load time)
$additional_preload = 'rel=preload;as=script,</' . SITE_PATH . '/css/gestionMembres.css>;rel=preload;as=style' ;
// In the mobile_header.php, $header_postamble will be inserted in the actual <head>...</head> section
$header_postamble ='
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<link rel="stylesheet" type="text/css" href="' . SITE_URL . 'css/gestionMembres.css">
' ;
require_once 'mobile_header5.php' ;

$searchText=""; 	
if (isset($_REQUEST['search']) and $_REQUEST['search'] != '') {
	$searchText=$_REQUEST['search'];
}

?>
<script type="text/javascript">

var dirSort="asc";
var columnSort=-1;
// Manage Search when keyup

// Manage Search when document loaded
$(document).ready(function() {
   $("#id_SearchInput").on("keyup", function() {
      var value = $(this).val().toLowerCase();
      $("#myTable tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().normalize('NFD').replace(/([\u0300-\u036f]|[^0-9a-zA-Z])/g, '').indexOf(value) > -1)
     });
    });
    var value = $("#id_SearchInput").val().toLowerCase();
      $("#myTable tr").filter(function() {
      $(this).toggle($(this).text().toLowerCase().normalize('NFD').replace(/([\u0300-\u036f]|[^0-9a-zA-Z])/g, '').indexOf(value) > -1)
      });
});

// Add onclick event to checkboxes after DOM loaded
// Could possible be included (or include) the above code
document.addEventListener("DOMContentLoaded", function () {
    // Select all checkboxes with an id starting with "check-"
    const checkboxes = document.querySelectorAll('input[type="checkbox"][id^="check-"]');

    // Add the onclick event to each checkbox whose id starts with "check-"
    checkboxes.forEach(function (checkbox) {
        checkbox.onclick = function () {
			var values=this.id.split("-");
			if(confirm("Confirmez que vous voulez changer le statut \""+values[2]+"\" de ce membre." +
				"\nRappel: le membre doit être élève ou pilote sinon il est non-navigant." +
				"\nNe pas oublier de prévenir les personnes responsables et de mettre à jour le fichier membres.xls sur OneDrive (Toujours utilisé - Seul endroit ou on gere l'historique des membres).")) {
				var aSearchText=document.getElementById("id_SearchInput").value;
				// Reload the same page, keeping the current query string (e.g., unactivated=true)
				// and adding/replacing the checkbox ID, its state and the search text (URLSearchParams encodes them)
				var aParams = new URLSearchParams(window.location.search);
				aParams.set('checkboxId', this.id);
				aParams.set('checked', this.checked);
				if (aSearchText != "")
					aParams.set('search', aSearchText);
				else
					aParams.delete('search');
				// Only change the query string: the host and path stay as they are (a path starting with '//' would be taken as a host name)
				window.location.search = aParams.toString();
			}
			else { // Revert to previous checked state
				// TODO can probably simply do this.checked = !this.checked; ;-)
				if(this.checked){
					this.checked=false;
				} else {
					this.checked=true;
				}
			}
        };
    });
});
</script>
<h2>Gestion des membres supprimés</h2>
  <p>&nbsp;&nbsp;Type something to search the table for first names, last names , ref, ...</p>  
<?php	
  print("<input class=\"form-control\" id=\"id_SearchInput\" type=\"text\" placeholder=\"Search..\" value=\"$searchText\">");
  print("<p></p>");
  $styleButton='class="btn btn-info text-light border border-1 border-secondary"';
  print('<div class="d-flex"><a href="gestionMembres.php" '.$styleButton.' role="button">Display Active Members</a></div>');
?>
 </br>
	<p></p>
	<div class="table">
<table width="100%" style="margin-left: auto; margin-right: auto;" class="table table-striped table-hover table-bordered"> 
	<thead style="position: sticky;">
<tr style="text-align: Center;">
<th class="select-checkbox" onclick="sortTable(0, true)" style="text-align: right;">#</th>
<th onclick="sortTable(1, true)">Jom Id</th>
<th onclick="sortTable(2, true)">Ré	f. Odoo</th>
<th onclick="sortTable(3, false)">Nom</th>
<th onclick="sortTable(4, false)">Prénom</th>
<th onclick="sortTable(5, false)">Photo</th>
<th onclick="sortTable(6, false)">Adresse</th>
<th onclick="sortTable(7, false)">Code</th>
<th onclick="sortTable(8, false)">Ville</th>
<th onclick="sortTable(9, false)">Pays</th>
<th onclick="sortTable(10, false)">Email</th>
<th onclick="sortTable(11, false)">Membre non-navigant</th>
<th onclick="sortTable(12, false)">Élève</th>
<th onclick="sortTable(13, false)">Pilote</th>
<th onclick="sortTable(14, false)">Membre Effectif</th>
</tr>
</thead>
<tbody id="myTable">


<?php

	$sql = "select * , group_concat(group_id) as allGroups
		from $table_users as u 
		join $table_user_usergroup_map on u.id=user_id 
		join $table_person as p on u.id=p.jom_id
		where block=1 
		group by user_id
		order by last_name, first_name" ;
	print("$sql<br>");
	$count=0;
	$result = mysqli_query($mysqli_link, $sql)
		or journalise(0, "F", "Cannot read members: " . mysqli_error($mysqli_link)) ;
	
	$memberCount=0;
	$studentCount=0;
	$effectifCount=0;
	$pilotCount=0;
	$blockedCount=0;
	$soldeTotalPositif=0.0;
	$soldeTotalNegatif=0.0;
	$odooCount=0;
	$cotisationNonPayeCount=0;
	$cotisationPayeCount=0;
	$cotisationNonRenouveleeCount=0;
	$cotisationRenouveleeCount=0;
	$CheckMark="&#9989;";
	
	while ($row = mysqli_fetch_array($result)) {
		$rowStyle="";
		$personid=$row['id'];
		$odooReference=$row['odoo_id'];
		if ($odooReference == "") {
			$odooReference="xxxxx";
		}
		$row['name'] = db2web($row['name']) ;
		if ($row['name'] === FALSE) 
			journalise($userId, 'E', "There was an error while converting $row[name]") ; 
		$row['first_name'] = db2web($row['first_name']) ;
		$row['last_name'] = db2web($row['last_name']) ;
		$address=db2web($row['address']);
		$code=db2web($row['zipcode']);
		$ville=db2web($row['city']);
		$pays=db2web($row['country']);
		$email=db2web($row['email']);		
		$nom=$row['last_name'];
		if($nom == "") $nom=$row['name'];
		$prenom=$row['first_name'];	
		if($row['last_name']=="") {
			$row['last_name']="xxxxx";
		}
		$photo="images/avatar.jpg";
		if (isset($row['avatar'])) {
			if (file_exists("../$avatar_root_directory/$row[avatar]"))
				$photo=$avatar_root_uri."/".$row['avatar'];
			else
				journalise($personid, "W", "Photo for $nom ($row[avatar]) does not exist") ;
		}
		$photo='<a href="'.$photo.'"><img style="object-fit: cover;"src="'.$photo.'" alt="photo" width="50" height="66"></a>';
	
		$count++;
		//print("Name=$row[first_name],$row[last_name].<br>");
		print("<tr style='text-align: right'; $rowStyle>
			<td><input type=\"checkbox\"> $count</td>
		    <td style='text-align: right;'>$personid&nbsp;
				<a class=\"tooltip\" href=\"javascript:void(0);\" 
					onclick=\"activateMemberFunction('$_SERVER[PHP_SELF]','activate','" .
					str_replace("'", "\\'","$nom $prenom") . "','$personid')\">
					<i class=\"bi bi-person-add\"></i><span class='tooltiptext'>Click pour réactiver le membre</span>
					</a></td>");
		print("<td style='text-align: left;'><a class=\"tooltip\" href=\"https://spa-aviation.odoo.com/odoo/customers/$odooReference\">$odooReference<span class='tooltiptext'>Click pour afficher les opérations comptables</span></a></td>
			<td style='text-align: left;'>$row[last_name]</td>
			<td style='text-align: left;'>$row[first_name]</td>
			<td style='text-align: left;'>$photo</td>
			<td style='text-align: left;'>$address</td>
			<td style='text-align: left;'>$code</td>
			<td style='text-align: left;'>$ville</td>
			<td style='text-align: left;'>$pays</td>
			<td style='text-align: left;'>$email</td>");

		$groups = explode(',', $row['allGroups']) ;
		$effectif = (in_array($joomla_effectif_group, $groups)) ? 
			'<i class="bi bi-check-circle text-success"></i>' 
			: ''  ;
		$pilot = (in_array($joomla_pilot_group, $groups)) ? 
			'<i class="bi bi-check-circle text-success"></i>' 
			: '' ;
		$student = (in_array($joomla_student_group, $groups)) ? 
			'<i class="bi bi-check-circle text-success"></i>' 
			: ''   ;
		$member="";
		if($pilot=="" && $student=="") {
			$member='<i class="bi bi-check-circle text-success"></i>';
		}
		print("<td style='text-align: center;'>$member</td>
			<td style='text-align: center;'>$student</td>
			<td style='text-align: center;'>$pilot</td>
			<td style='text-align: center;'>$effectif</td>");
		print("</tr>\n");
	}
	print("<tr style='text-align: center;' class='text-bg-info'>
		<td>Total</td>
	    <td></td>");
	print("<td>$odooCount</td>
		<td>$count</td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		</tr>\n");
?>


</tbody>
</table>
</div>
<?php
// Tools in PHP

// Check the type of a person
function GM_IsMemberType($personId, $memberType)
{

	global $joomla_member_group,$joomla_student_group,$joomla_pilot_group,$joomla_effectif_group,$joomla_flying_student_group,$joomla_theory_student_group;
	global $userId;
	global $mysqli_link,$table_user_usergroup_map;

	//print("GM_IsMemberType($personId, $memberType): Started<br>");
	switch($memberType) {
		case 'Member':
			$groupId = $joomla_member_group;
			break;
		case 'Student':
			$groupId = $joomla_student_group;
			break;
		case 'TheoryStudent':
			$groupId = $joomla_theory_student_group;
			break;
		case 'FlyingStudent':
			$groupId = $joomla_flying_student_group;
			break;
		case 'Pilot':
			$groupId = $joomla_pilot_group;
			break;
		case 'Effectif':
			$groupId = $joomla_effectif_group;
			break;
		default:
			print("%%%ERROR:GM_IsMemberType: Unknown member type $memberType") ;
			return false;
	}
	$sql="SELECT * FROM $table_user_usergroup_map WHERE user_id=$personId AND group_id=$groupId";
	//print("GM_IsMemberType:sql=$sql<br>");
	$result = mysqli_query($mysqli_link, $sql)
    		or journalise($userId, "E", "Cannot read $table_user_usergroup_map: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
		//print("GM_IsMemberType:return true<br>");
		return true;
	}
	//print("GM_IsMemberType:return false<br>");
	return false;
}
?>

</body>
</html>