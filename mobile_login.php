<?php
/*
   Copyright 2013-2023 Eric Vyncke

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

$callback = $_REQUEST['cb'] ;
if ($callback == '') $callback = 'resa/mobile.php' ; // By default

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
        journalise($joomla_user->id, "I", "Connection of $_REQUEST[username] from $callback") ;
        exit ;
    } else {
        $connect_msg = "Utilisateur inconnu ou mauvais mot de passe." ;
        journalise(0, "W", "Invalid password for $_REQUEST[username] from $callback") ;
    }
}

require_once 'mobile_header5.php' ;
?> 
<div class="container">
   <h1>Connexion</h1>
    <p class="bg-danger"><?=$connect_msg?></p>
    <p class="bg-info">Pour accéder au site vous devez vous connecter.</p>

<form method="post" action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" name="cb" value="<?=$callback?>">

<label for="username" class="form-label">
	Identifiant: 
</label>
<input type="text" class="form-control" id="username" name="username" placeholder="Votre nom d'utilisateur" autocomplete="username" value="<?=$_REQUEST['username']?>"><br/>

<label for="password" class="form-label">
	Mot de passe:
</label>
<input class="form-control" type="password" id="password" placeholder="Votre mot de passe" name="password" autocomplete="current-password"><br/>

<input type="submit" class="btn btn-primary" value="Connexion">

</form>
</div> <!-- container -->
</body>
</html>