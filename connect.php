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

require_once 'dbi.php' ;

// https://gist.github.com/alexandreelise/2fa2c5ce2a823bc2f08abbb91cd44274

$callback = $_REQUEST['cb'] ;

if ($userId > 0) {
    header("Location: https://www.spa-aviation.be/$callback", TRUE, 307) ;
    exit ;
}

$connect_msg = '' ;

if (isset($_REQUEST['username']) and isset($_REQUEST['password'])) {
    $result_login = JFactory::getApplication()->login(
        [
            'username' => $_REQUEST['username'],
            'password' => $_REQUEST['password']
        ],
        [
            'remember' => true,
            'silent'   => true
        ]
    );
    if ($result_login) {
        header("Location: https://www.spa-aviation.be/$callback", TRUE, 307) ;
        $joomla_user = JFactory::getUser() ;
        journalise($joomla_user, "I", "Connection of $_REQUEST[username]/$joomla_user from $callback") ;
        exit ;
    } else {
        $connect_msg = "Utilisateur inconnu ou mauvais mot de passe." ;
        journalise(0, "W", "Invalid password for $_REQUEST[username] from $callback") ;
    }
}
?><!DOCTYPE html>
<html lang="fr">
<head>
<link rel="stylesheet" type="text/css" href="mobile.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<meta charset="utf-8">
<!--meta name="viewport" content="width=320"-->
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- http://www.alsacreations.com/article/lire/1490-comprendre-le-viewport-dans-le-web-mobile.html -->
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<!-- http://www.w3schools.com/bootstrap/ -->
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script><html>
	<title>Connexion</title>
</head>
<body>
    <h1>Connexion</h1>
    <p class="bg-danger"><?=$connect_msg?></p>
    <p class="bg-info">Pour accéder au site vous devez vous connecter.</p>

<form method="post" action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" name="cb" value="<?=$callback?>">
Identifiant: <input type="text" name="username" value="<?=$_REQUEST['username']?>"><br/>
Mot de passe: <input type="password" name="password"><br/>
<input type="submit" value="Connexion">
</form>
</body>
</html>