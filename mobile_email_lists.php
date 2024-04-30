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

ob_start("ob_gzhandler");
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
$fullName = (isset($_REQUEST['fullName']) and $_REQUEST['fullName'] != '') ? true : false ;
?>
<h2>Liste des adresses email</h2>
<div class="row ms-3">
<?php
$group = 22 ; // test with CA
if ($group) {
    $result = mysqli_query($mysqli_link, "SELECT * FROM $table_person p JOIN jom_user_usergroup_map m ON m.user_id = p.jom_id
        WHERE group_id = $group")
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
} else { // display the form
} // End of if ($group)
?>
</div><!-- row --> 
</body>
</html>
