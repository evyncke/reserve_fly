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

if (isset($_GET['odoo']) and $_GET['odoo'] != '') {
    setcookie('odoo', strtolower($_GET['odoo'])) ;
    $_COOKIE['odoo'] = strtolower($_GET['odoo']) ;
}

require_once "dbi.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}

require_once 'mobile_header5.php' ;

if (!$userIsAdmin and !$userIsBoardMember and !$userIsInstructor) journalise($userId, "F", "This admin page is reserved to administrators") ;
?>
<h2>Configuration de la base de données Odoo@<?=$odoo_host?></h2>
<p>Sur base du fichier <mark>dbi.php</mark> (modifiable par Éric ou Patrick) et du cookie 'odoo' (<b><?=$_COOKIE['odoo']?></b>).
<ul>
    <li>Hostname: <b><a href="https://  <?=$odoo_host?>"><?=$odoo_host?></a></b></li>
    <li>Data base: <b><?=$odoo_db?></b></li>
    <li>Username: <b><?=$odoo_username?></b></li>
    <li>Password: <b><?=$odoo_password?></b></li>
</ul>
</p>
<p>Autre bases de données disponibles:
    <ul>
    <li><a href="<?=$_SERVER['PHP_SELF']?>?odoo=default">par défaut</a>;</li>
    <li><a href="<?=$_SERVER['PHP_SELF']?>?odoo=spa-aviation">spa-aviation.odoo.com</a>;</li>
    <li><a href="<?=$_SERVER['PHP_SELF']?>?odoo=spa-aviation-eric">spa-aviation-eric.odoo.com</a> (tests février 2024);</li>
    </ul>
</body>
</html>