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

require_once 'flight_header.php' ;
require_once 'odoo.class.php' ;

if (!$userIsAdmin and !$userIsBoardMember and !$userIsInstructor) journalise($userId, "F", "This admin page is reserved to administrators") ;

$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <link href="https://www.spa-aviation.be/favicon32x32.ico" rel="shortcut icon" type="image/vnd.microsoft.icon">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
	
<script type="text/javascript">
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
</script>
</head>
<body>
<h2>Bons cadeaux IF et INIT payés sur le compte CBC @<?=$odoo_host?></h2>
<p>Virements contenant la communication V-IF- et V-INIT-</p>
<p>Filtre : Exemple 242203</p>  
<?php	
$searchText="";
print("<input class=\"form-control\" id=\"id_SearchInput\" type=\"text\" placeholder=\"Search..\" value=\"$searchText\">");
?>

<form action="<?=$_SERVER['PHP_SELF']?>" id="company_form">
    <input type="hidden" name="save_company" value="true">
<table class="table table-hover table-responsive table-bordered">
    <thead>
        <tr><th>id</th><th>Date</th><th>Communication</th><th>Valeur</th></tr>
    </thead>
    <tbody class="table-group-divider" id="myTable">
<?php
$result = $odooClient->SearchRead('account.move.line', array(), array('fields' => array('id', 'name', 'move_type','account_id','debit', 'create_date'))) ;
$ids = array() ;
foreach($result as $f=>$desc) {
	//echo "f=";
	//echo var_dump($f);
	$id = (isset($desc['id'])) ? $desc['id'] : '' ;
	$communication = (isset($desc['name'])) ? $desc['name'] : '' ;
	$communicationUppercase = strtoupper($communication);
	$debit = (isset($desc['debit'])) ? $desc['debit'] : '' ;
	$date = (isset($desc['create_date'])) ? $desc['create_date'] : '' ;
	$date = substr($date,0,10);
	if(str_contains($communicationUppercase,"INIT-") || str_contains($communicationUppercase,"IF-")){
		if($debit>0) {
			//echo "desc=";
			//echo var_dump($desc)."<br/>";
			//print("id=$id;name=$name<br/>");
    		print("<tr class=\"ciel-row\">
      	  	  <td>$id</td>
			   <td>$date</td>
     	  	   <td>$communication</td>
     	  	   <td>$debit €</td>
      	  	   </tr>\n") ;
	  	}
	}
}
?>
</tbody>
</table>
</form>
</body>
</html>