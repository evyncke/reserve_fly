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
$title= (isset($_REQUEST['title']) and $_REQUEST['title'] != '') ? $_REQUEST['title'] : false ;
$email = (isset($_REQUEST['email']) and $_REQUEST['email'] != '') ? true : false ;
$mobile = (isset($_REQUEST['mobile']) and $_REQUEST['mobile'] != '') ? true : false ;
$qualite = (isset($_REQUEST['qualite']) and $_REQUEST['qualite'] == 'Y') ? true : false ;
$procuration = (isset($_REQUEST['procuration']) and $_REQUEST['procuration'] == 'Y') ? true : false ;
$signature = (isset($_REQUEST['signature']) and $_REQUEST['signature'] == 'Y') ? true : false ;
$emptyLines = (isset($_REQUEST['emptyLines']) and is_numeric($_REQUEST['emptyLines'])) ? $_REQUEST['emptyLines'] : 0 ;

if ($title)
    print("<h2>$title</h2>\n") ;
else
    print("<h2>Liste des membres</h2>\n") ;
if ($group) {
    $result = mysqli_query($mysqli_link, "SELECT * FROM jom_usergroups WHERE $group = id")
        or journalise($userId, "F", "Cannot find group $id: " . mysqli_error($mysqli_link)) ;
    $row = mysqli_fetch_array($result)
        or journalise($userId, "F", "Group $id not found") ;
    print("<h3>Membres du groupe " . db2web($row['title']) . "</h3>") ;
 ?>
<table class="table table-striped table-bordered table-hover">
    <thead>
        <tr><th>NUM</th><th>NOM, prénom</th>
 <?php
 if ($email) print("<th>Email</th>") ;
 if ($mobile) print("<th>Mobile</th>") ;
 if ($qualite) print("<th>Qualité(s)</th>") ;
 if ($procuration) print("<th>Procuration</th>") ;
 if ($signature) print("<th>Signature</th>") ;
 ?>
    </tr>
    </thead>
    <tbody>
<?php
    $result = mysqli_query($mysqli_link, "SELECT last_name, first_name, p.email as email, cell_phone, CONCAT(g.title) AS titles 
        FROM $table_person p JOIN jom_user_usergroup_map m ON m.user_id = p.jom_id
            JOIN jom_usergroups g ON g.id = m.group_id
            JOIN jom_users u ON u.id = p.jom_id 
        WHERE group_id = $group AND u.block = 0
        GROUP by p.jom_id
        ORDER BY p.last_name, p.first_name")
        or journalise($userId, "F", "Cannot read members: " . mysqli_error($mysli_link)) ;
    $line = 1 ;
    while ($row = mysqli_fetch_array($result)) {
        print("<tr><td>$line</td>") ;
        $line++ ;
        print('<td>' . db2web(strtoupper("<b>$row[last_name]</b>")) . db2web(", $row[first_name]") . "</td>") ;
        if ($email) print("<td>$row[email]</td>") ;
        if ($mobile) print("<td>$row[cell_phone]</td>") ;
        if ($qualite) print("<td>$row[titles]</td>") ;
        if ($procuration) print("<td></td>") ;
        if ($signature) print("<td></td>") ;
        print("</tr>\n") ;
    }
    while ($emptyLines-- > 0) {
        print("<tr><td>$line</td>") ;
        $line++ ;
        print('<td></td>') ;
        if ($email) print("<td></td>") ;
        if ($mobile) print("<td></td>") ;
        if ($qualite) print("<td></td>") ;
        if ($procuration) print("<td></td>") ;
        if ($signature) print("<td></td>") ;
        print("</tr>\n") ;
    }
?>
</tbody>
</table>
<?php
} else { // display the form
?>
</div><!-- mb-3-->

<div class="row ms-3">
<form action="<?=$_SERVER['PHP_SELF']?>" method="GET" role="form">
<div class="mb-3">
<div class="form-check">
    <label class="form-check-label" for="titleId">Titre de la liste</label>
    <input type="text" class="form-control" name="title" id="titleId" placeholder="Titre de la liste">
    <div id="titleHelp" class="form-text">Ce titre va apparaître en haut de la liste.</div>
</div><!-- form-check-->
</div><!-- mb-3-->
<div class="mb-3">
<label for="groupId" class="form-label">Sélectionner les membre du groupe</label>
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
<div class="form-check">
  <input class="form-check-input" type="checkbox" name="email" value="Y" id="emailId">
  <label class="form-check-label" for="emailId">
    Ajouter une colonne <b>email</b>
  </label>
</div><!-- form-check-->
</div><!-- mb-3-->
<div class="mb-3">
<div class="form-check">
  <input class="form-check-input" type="checkbox" name="mobile" value="Y" id="mobileId">
  <label class="form-check-label" for="mobileId">
    Ajouter une colonne <b>mobile</b>
  </label>
</div><!-- form-check-->
</div><!-- mb-3-->
<div class="mb-3">
<div class="form-check">
  <input class="form-check-input" type="checkbox" name="procuration" value="Y" id="procurationId" checked>
  <label class="form-check-label" for="procurationId">
    Ajouter une colonne <b>procuration</b>
  </label>
</div><!-- form-check-->
</div><!-- mb-3-->
<div class="mb-3">
<div class="form-check">
  <input class="form-check-input" type="checkbox" name="qualite" value="Y" id="qualiteId" checked>
  <label class="form-check-label" for="qualiteId">
    Ajouter une colonne <b>qualité</b>
  </label>
</div><!-- form-check-->
</div><!-- mb-3-->
<div class="mb-3">
<div class="form-check">
  <input class="form-check-input" type="checkbox" name="signature" value="Y" id="signatureId" checked>
  <label class="form-check-label" for="signatureId">
  Ajouter une colonne <b>signature</b>
  </label>
</div><!-- form-check-->
</div><!-- mb-3-->
<div class="mb-3">
<div class="form-check">
  <input class="form-control" type="number" name="emptyLines" value="0" id="emptyLinesId" checked>
  <label class="form-check-label" for="emptyLinesId">
  Ajouter des lignes vides
  </label>
</div><!-- form-check-->
</div><!-- mb-3-->
<button type="submit" class="btn btn-primary">Générer la liste</button>
</form>
</div><!-- row --> 
<?php
} // End of if ($group)
?>
</body>
</html>
