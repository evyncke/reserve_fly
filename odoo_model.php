<?php
/*
   Copyright 2014-2023 Eric Vyncke

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

require_once 'mobile_header5.php' ;

if (!$userIsAdmin and !$userIsBoardMember and !$userIsInstructor) journalise($userId, "F", "This admin page is reserved to administrators") ;
$model = (isset($_REQUEST['model'])) ? $_REQUEST['model'] : 'ir.model' ;
$name = (isset($_REQUEST['name'])) ? $_REQUEST['name'] : '' ;
$id = (isset($_REQUEST['id'])) ? $_REQUEST['id'] : '' ;
?>
<h2><?=$model?>@<?=$odoo_host?></h2>

<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<label for="modelId" class="form-label">Modèle</label>
<input type="text" class="form-control" id="modelId" name="model" value="<?=$model?>">
<label for="idId" class="form-label">ID</label>
<input type="text" class="form-control" id="idId" name="id" value="<?=$id?>">
<label for="nameId" class="form-label">Name</label>
<input type="text" class="form-control" id="nameId" name="name" value="<?=$name?>">
<button type="submit" class="btn btn-primary">Afficher ce modèle</button>
</form>
<table class="table table-striped table-hover">
<?php

require_once 'odoo.class.php' ;
$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;

if ($id != '') { // Display all field of this line
    print("<thead><tr><th>Field Name</th><th>Field Value</th></tr><tbody class=table-divider>\n") ;
    $result = $odooClient->SearchRead($model, array(array(array('id','=',$id))), array()) ;
    foreach($result[0] as $f=>$desc) {
        $value = (isset($desc)) ? $desc : '' ;
        if (is_array($value)) $value = '[' . implode(', ', $value) . ']';
        print("<tr><td><a href=\"?model=$model&name=$f\">$f</a></td><td>$value</td></tr>\n") ;
    }

} else if ($name != '') { // Display all lines with this field
    print("<thead><tr><th>Id</th><th>Name</th><th>Field Name</th><th>Field Value</th></tr><tbody class=table-divider>\n") ;
    $result = $odooClient->SearchRead($model, array(), array('fields' => array('id', 'name', $name))) ;
    foreach($result as $f=>$desc) {
        $value = (isset($desc[$name])) ? $desc[$name] : '' ;
        if (is_array($value)) $value = '[' . implode(', ', $value) . ']';
        print("<tr><td><a href=\"?model=$model&id=$desc[id]\">$desc[id]</a></td><td>$desc[name]</td><td>$name</td><td>$value</td></tr>\n") ;
    }

} else {
    // Let's get all Odoo fields from the model
    $result = $odooClient->GetFields($model, array('string', 'type','help', 'description', 'default', 'index', 'states', 'selection')) ;
 //   print("<pre>") ; var_dump($result) ; print("</pre>") ;
    print("<thead><tr><th>Field</th><th>Description<br/>Help</th><th>Type</th><th>Information</th></tr><tbody class=table-divider>\n") ;
    foreach($result as $f=>$desc) {
        $help = (isset($desc['help'])) ? $desc['help'] : '' ;
        print("<tr><td><a href=\"?model=$model&name=$f\">$f</a></td><td>$desc[string]</td><td>$desc[type]</td><td>$help</td></tr>\n") ;
    }
} // else
?>
</tbody>
</table>
</body>
</html>