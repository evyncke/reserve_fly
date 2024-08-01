<?php
/*
   Copyright 2014-2024 Eric Vyncke

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

MustBeLoggedIn() ;

if (! $userIsAdmin)
	die("Vous devez &ecirc;tre connect&eacute; et administrateur pour ajouter une nouvelle.") ;

if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'news_add') {
	$start = mysqli_real_escape_string($mysqli_link, web2db(trim($_REQUEST['start']))) ;	
	$stop = mysqli_real_escape_string($mysqli_link, web2db(trim($_REQUEST['stop']))) ;	
	$subject = mysqli_real_escape_string($mysqli_link, web2db(trim($_REQUEST['subject']))) ;	
	$text = mysqli_real_escape_string($mysqli_link, web2db(trim($_REQUEST['text']))) ;	
	mysqli_query($mysqli_link, "INSERT INTO $table_news(n_start, n_stop, n_who, n_date, n_subject, n_text)
		VALUES('$start', '$stop', $userId, sysdate(), '$subject', '$text')")
		or die("Cannot add news about '$_REQUEST[subject]': " . mysqli_error($mysqli_link)) ;
	// So far so good, redirect to the reservation page
	journalise($userId, 'I', "News about $_REQUEST[subject] added") ;
	header('Location: ' . 'https://www.spa-aviation.be/resa/') ;
	die() ; 
}

?><html>
<head>
<!-- TODO trim the CSS -->
<link rel="stylesheet" type="text/css" href="profile.css">
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
<title>Ajout d'une nouvelle</title>
<script>
const
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
<div class="container-fluid">
<div class="row">
<h3>Ajout d'une nouvelle</h3>
</div><!-- row -->

<div class="row">

<form action="<?=$_SERVER['PHP_SELF']?>" method="post" role="form" class="form-horizontal">
<input type="hidden" name="action" value="news_add">
<div class="form-group">
	<label class="control-label col-sm-2 col-md-1">Date début affichage:</label>
	<div class="col-sm-4 col-md-2">
		<input type="date" class="form-control" name="start" value="<?=date('Y-m-d')?>">
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group">
	<label class="control-label col-sm-2 col-md-1">Date fin d'affichage:</label>
	<div class="col-sm-4 col-md-2">
		<input type="date" class="form-control" name="stop" value="<?=date('Y-m-d', strtotime('+1 month'))?>">
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group">
	<label class="control-label col-sm-2 col-md-1">Titre:</label>
	<div class="col-sm-4 col-md-2">
		<input type="text" class="form-control" name="subject">
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group">
	<label class="control-label col-sm-2 col-md-1">Texte:</label>
	<div class="col-sm-4 col-md-2">
		<textarea class="form-control" name="text" rows="3"></textarea>
	</div> <!-- col -->
</div> <!-- form-group -->
<div class="form-group"><button type="submit" class="col-sm-offset-2 col-md-offset-1 col-sm-3 col-md-2 btn btn-primary">Ajouter la nouvelle</button></div>
</form>

</div> <!-- row -->


<div class="row hidden-xs">
<hr>
<?php
$version_php = date ("Y-m-d H:i:s.", filemtime('news_add.php')) ;
?>
<div class="copyright">R&eacute;alisation: Eric Vyncke, avril 2018, pour RAPCS, Royal A&eacute;ro Para Club de Spa, ASBL<br>
Versions: PHP=<?=$version_php?></div>
</div> <!-- row-->

</div> <!-- container -->
</body>
</html>