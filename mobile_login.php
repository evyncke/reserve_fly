<?php
/*
   Copyright 2013-2020 Eric Vyncke

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

// TODO
// initial login is at  https://www.spa-aviation.be/resa/mobile_login.php
// Which then redirects to https://resa.spa-aviation.be/mobile.php?news
// Of course forcing a 2nd login :(
//
// $_SERVER[SERVER_NAME] www.spa-aviation.be ou resa.spa-aviation.be
// $_SERVER[PHP_SELF] /resa/mobile_login.php ou /mobile_login.php

// Start the session
session_start(['cookie_lifetime' => 86400, 'cookie_httponly' => TRUE]) ;

require_once "dbi.php" ;
require_once 'facebook.php' ;

if (isset($_REQUEST['username']) and isset($_REQUEST['password'])) {
	// If your script is in a spot to import the Joomla libraries, just use JUserHelper::verifyPassword() and that'll take care of it for you.
	// If not, use PHP's `password_verify()` function to validate the password (native to PHP 5.5+, 
	$username = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['username'])) ;
	$password = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['password'])) ;
	$result = mysqli_query($mysqli_link, "SELECT * FROM $table_users
		WHERE username='$username'")
		or die("Error retrieving user: " . mysqli_error($mysqli_link)) ;
	if (!$result)
		die("Cannot login, wrong username/password combination") ;
	$row = mysqli_fetch_array($result) ;
	if (! password_verify($password, $row['password']))
		die("Cannot login, wrong username/password combination") ;
	$_SESSION['jom_id'] = $row['id'] ;
	header("Location: https://resa.spa-aviation.be/mobile.php?news") ;
	journalise($row['id'], 'I', "$username is connected on the mobile web.") ;
	exit() ;
}

require_once 'mobile_header.php' ;

?> 
<div class="container">
<br/><br/><br/><br/><br/><br/><br/>
<div class="row">
<div class="col-xs-12">
<form action="<?=$_SERVER['PHP_SELF']?>" method="POST" class="form-horizontal" >
	<div class="form-group">
		<label class="control-label col-sm-2" for="username">Identifiant: </label>
		<div class="col-sm-10">
			<input name="username" type="text" class="form-control" id="username" size="10">
		</div> <!-- col -->
	</div> <!-- form-group -->
	<div class="form-group">
		<label class="control-label col-sm-2" for="password">Mot de passe: </label>
		<div class="col-sm-10">
			<input name="password" type="password" class="form-control" id="password" size="10"><br/>
		</div> <!-- col -->
	</div> <!-- form-group -->
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">
			<button type="submit" class="btn btn-default value="Se connecter">Se connecter</button>
		</div> <!-- col -->
	</div> <!-- form-group -->
</form>
</div> <!-- col -->
</div> <!-- row -->

<div class="row">
	<a href="<?= htmlspecialchars($fb_loginUrl)?>"> <img src="facebook.jpg"> Se connecter via votre compte Facebook.</a><br/>(vous devez avoir lié vos comptes RAPCS/Facebook auparavant)
</div> <!-- row -->

</div> <!-- container-->
</body>
</html>
