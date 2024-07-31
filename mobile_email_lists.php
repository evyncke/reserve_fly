<?php
/*
   Copyright 2024 Eric Vyncke

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

require_once "dbi.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}
if (! ($userIsAdmin or $userIsBoardMember or $userIsInstructor))
    journalise($userId, "F", "Vous devez être administrateur ou instructeur pour voir cette page.") ;
require_once 'mobile_header5.php' ;

$group = (isset($_REQUEST['group']) and is_numeric($_REQUEST['group'])) ? $_REQUEST['group'] : false ; // Also used as indicator of action
$separator = (isset($_REQUEST['separator']) and $_REQUEST['separator'] != '') ? $_REQUEST['separator'] : ',' ;
if ($separator == 'nl') $separator = "<br/>" ;
$fullName = (isset($_REQUEST['fullName']) and $_REQUEST['fullName'] != '') ? true : false ;
?>
<h2>Liste des adresses email</h2>
<?php
if ($group) {
?>
<script>
const copyToClipboard = async () => {
  try {
    const element = document.getElementById('addresses') ;
    await navigator.clipboard.writeText(element.textContent);
  } catch (error) {
    console.error("Failed to copy to clipboard:", error);
  }
};
</script>
<button type="button" class="btn btn-outline-info btn-sm" onclick="copyToClipboard();"><i class="bi bi-copy"></i> Copier</button>
<div id="addresses" class="border border-info row m-3 text-secondary">
<?php
    $result = mysqli_query($mysqli_link, "SELECT * 
        FROM $table_person p 
            JOIN jom_user_usergroup_map m ON m.user_id = p.jom_id
            JOIN jom_users u ON u.id = p.jom_id 
        WHERE group_id = $group AND block = 0
        ORDER BY p.last_name, p.first_name")
        or journalise($userId, "F", "Cannot read members: " . mysqli_error($mysli_link)) ;
    $first = true ;
    while ($row = mysqli_fetch_array($result)) {
        if ($first)
            $first = false ;
        else
            print("$separator ") ;
        if ($fullName)
            print('"' . db2web("$row[first_name] $row[last_name]") . "\" &lt;$row[email]&gt;") ;
        else
            print($row['email']) ;
    }
?>
</div><!-- addresses-->
<button type="button" class="btn btn-outline-info btn-sm" onclick="copyToClipboard();"><i class="bi bi-copy"></i> Copier</button>
<?php
} else { // display the form
?>
<div class="row ms-3">
<form action="<?=$_SERVER['PHP_SELF']?>" method="GET" role="form">
<div class="mb-3">
<label for="groupId" class="form-label">Groupe</label>
<select id="groupId" class="col-form-select col-xs-1" name="group">
    <?php
    $result = mysqli_query($mysqli_link, "SELECT * FROM jom_usergroups ORDER BY title ASC")
        or journalise($userId, "F", "Cannot list user groups: " . mysqli_error($mysqli_link)) ;
    while ($row = mysqli_fetch_array($result)) {
        $selected = ($row['title'] == 'Membres') ? ' selected' : '' ;
        print("<option value=\"$row[id]\"$selected>" . db2web($row['title']) . "</option>\n") ;
    }
    ?>
</select>
</div>
<div class="mb-3">
<label for="separatorId" class="form-label">Separateur d'adresses</label>
<select id="separatorId" class="col-form-select col-xs-1" name="separator">
    <option value=";">; (Outlook)</option>
    <option value=",">, (GMail)</option>
    <option value="nl">Retour à la ligne</option>
    <option value=" ">Aucun</option>
</select>
<div class="form-check">
  <input class="form-check-input" type="checkbox" name="fullName" value="Y" id="fullNameId" checked>
  <label class="form-check-label" for="fullNameId">
    Inclure les noms et prénoms
  </label>
</div><!-- form-check-->
</div><!-- mb-3-->
<button type="submit" class="btn btn-primary">Générer la liste d'adresses email</button>
</form>
</div><!-- row --> 
<?php
} // End of if ($group)
?>
</body>
</html>
