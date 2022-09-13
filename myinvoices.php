<?php
/*
   Copyright 2022-2022 Eric Vyncke

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

if ($userId <= 0) die("Vous devez être connecté") ;

if (isset($_REQUEST['user']) and $userIsAdmin) {
	if ($userId != 62) journalise($userId, "I", "Start of invoices, setting user to $_REQUEST[user]") ;
	$userId = $_REQUEST['user'] ;
	if (! is_numeric($userId)) die("Invalid user ID") ;
} else
	if ($userId != 62) journalise($userId, "I", "Start of invoices") ;

$result = mysqli_query($mysqli_link, "SELECT * FROM $table_person WHERE jom_id = $userId") or die("Cannot read $table_person: " . mysqli_error($mysqli_link)) ;
$user = mysqli_fetch_array($result) ;
$userFullName = db2web("$user[first_name] $user[last_name]") ;
?><html>
<head>
<link rel="stylesheet" type="text/css" href="log.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<title>Factures pour <?=$userFullName?></title>
<script>
var
	// preset Javascript constants filled with the right data from db.php PHP variables
	userFullName = '<?=$userFullName?>' ;
	userName = '<?=$userName?>' ;
	userId = <?=$userId?> ;

</script>
<!-- Matomo -->
<script type="text/javascript">
  var _paq = window._paq = window._paq || [];
  /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
  _paq.push(['setUserId', '<?=$userName?>']);
  _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
  _paq.push(["setDomains", ["*.spa-aviation.be","*.ebsp.be","*.m.ebsp.be","*.m.spa-aviation.be","*.resa.spa-aviation.be"]]);
  _paq.push(['enableHeartBeatTimer']);
  _paq.push(['setCustomVariable', 1, "userID", <?=$userId?>, "visit"]);
  _paq.push(["setCookieDomain", "*.spa-aviation.be"]);
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="//analytics.vyncke.org/";
    _paq.push(['setTrackerUrl', u+'matomo.php']);
    _paq.push(['setSiteId', '5']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Code -->
</head>
<body>
<h2>Factures r&eacute;centes pour le membre <?=$userId?> <?=$userFullName?></h2>
<p>A titre exp&eacute;rimental, voici quelques factures r&eacute;centes:
<ul>
<?php
$sql = "SELECT * FROM $table_person LEFT JOIN $table_bk_invoices ON bki_email = email
	WHERE jom_id = $userId" ;

$result = mysqli_query($mysqli_link, $sql) or die("Erreur systeme a propos de l'access factures: " . mysqli_error($mysqli_link)) ;
$count = 0 ;
while ($row = mysqli_fetch_array($result)) {
	print("<li><a href=\"$row[bki_file_name]\">$row[bki_date] #$row[bki_id] &boxbox;</a></li>\n") ;
	$count ++ ;
}

if ($count == 0) print("<li>Hélas, pas encore de facture à votre nom dans le système.</li>\n") ;

$version_php = date ("Y-m-d H:i:s.", filemtime('myinvoices.php')) ;
$version_css = date ("Y-m-d H:i:s.", filemtime('log.css')) ;
?>
</ul>
</p>
<p>Il suffit de cliquer sur une date pour afficher la facture.</p>
<hr>
<div class="copyright">R&eacute;alisation: Eric Vyncke, septembre 2022, pour RAPCS, Royal A&eacute;ro Para Club de Spa, ASBL<br>
Versions: PHP=<?=$version_php?>, CSS=<?=$version_css?></div>
</body>
</html>
