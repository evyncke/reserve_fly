<?php
/*
   Copyright 2014-2020 Eric Vyncke

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

//ob_start("ob_gzhandler");

require_once "dbi.php" ;
require_once 'facebook.php' ;

if (! ($userIsAdmin or $userIsInstructor or $userIsFlightPilot or $userIsFlightManager))
	die("Vous devez être pilote ou gestionnaire des vols découvertes ou instructeur ou administrateur pour utiliser cette page.") ;
?>
<html>
<head>
<!-- TODO trim the CSS -->
<link rel="stylesheet" type="text/css" href="flight.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<meta charset="utf-8">
<!--meta name="viewport" content="width=320"-->
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- http://www.alsacreations.com/article/lire/1490-comprendre-le-viewport-dans-le-web-mobile.html -->
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<!-- http://www.w3schools.com/bootstrap/ -->
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
<title>Vols découvertes et d'initiation</title>
<script>
var
	// preset Javascript constant fill with the right data from db.php PHP variables
	userFullName = '<?=$userFullName?>' ;
	userName = '<?=$userName?>' ;
	userId = <?=$userId?> ;
	userIsPilot = <?=($userIsPilot)? 'true' : 'false'?> ;
	userIsAdmin = <?=($userIsAdmin)? 'true' : 'false'?> ;
	userIsInstructor = <?=($userIsInstructor)? 'true' : 'false'?> ;
	userIsMechanic = <?=($userIsMechanic)? 'true' : 'false'?> ;

</script>
</head>
<body>

<nav class="navbar navbar-inverse">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>                        
      </button>
      <a class="navbar-brand" href="flight_home.php">Vols découvertes/initiations <span style="color:red; font-weight: bold; font-style: italic;">EN TEST !!!</span></a>
    </div><!-- navbar-header -->
    <div class="collapse navbar-collapse" id="myNavbar">
      <ul class="nav navbar-nav">
        <li class="active"><a href="flight_home.php">Home</a></li>
        <li class="dropdown">
          <a class="dropdown-toggle" data-toggle="dropdown" href="#">Vols<span class="caret"></span></a>
          <ul class="dropdown-menu">
            <!--li><a href="mobile.php">Ma prochaine</a></li-->
            <li><a href="flight_create.php">Nouveau</a></li>
            <li><a href="flight_list.php">Tous les vols</a></li>
            <li><a href="flight_list_mine.php">Mes vols à venir</a></li>
          </ul><!-- dropdown-menu -->
        </li><!-- dropdown -->
        <li class="dropdown">
          <a class="dropdown-toggle" data-toggle="dropdown" href="#">Pilotes<span class="caret"></span></a>
          <ul class="dropdown-menu">
            <!--li><a href="mobile.php">Ma prochaine</a></li-->
            <li><a href="flight_pilot_rating.php">Qualifications</a></li>
            <li><a href="flight_pilot_assign.php">Dernières assignations</a></li>
          </ul><!-- dropdown-menu -->
        </li><!-- dropdown -->
        <li><a href="flight_help.php">Aide</a></li>
      </ul><!-- av navbar-nav -->
    </div><!-- myNavbar -->
  </div><!-- container fluid -->
</nav>

<div class="container-fluid">

 