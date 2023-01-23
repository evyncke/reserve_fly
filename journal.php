<?php
/*
   Copyright 2014-2019 Eric Vyncke

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
require_once 'facebook.php' ;

if ($userId <= 0) {
	print('<img src="facebook.jpg">') ;
	print('<a href="' . htmlspecialchars($fb_loginUrl) .'">Se connecter via votre compte Facebook.</a><br/>') ;
	die() ;
}

if (!$userIsAdmin) die("This page is reserved to administrators") ;

?><html>
<head>
<link rel="stylesheet" type="text/css" href="log.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<title>Journal syst&egrave;me</title>
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
<center><h2>Journal syst&egrave;me</h2></center>

<table class="logTable">
<thead>
<tr>
<th class="logLastHeader">Date</th>
<th class="logLastHeader">User</th>
<th class="logLastHeader">IP Address</th>
<th class="logLastHeader">Message</th>
<th class="logLastHeader">URI</th>
</tr>
</thead>
<tbody>
<?php
$start = (isset($_REQUEST['start'])) ? $_REQUEST['start'] : 99999999 ;
$sql_filter = '' ;
if (isset($_REQUEST['id']) and is_numeric($_REQUEST['id']))
	$sql_filter = " AND j_jom_id = $_REQUEST[id]" ;
  else if (isset($_REQUEST['user']))
	$sql_filter = " AND name like '%" . web2db($_REQUEST['user']) . "%'" ;
 else if (isset($_REQUEST['msg']))
	$sql_filter = " AND j_message like '%" . web2db($_REQUEST['msg']) . "%'" ;
$sql = "SELECT * FROM $table_journal LEFT JOIN $table_users u ON j_jom_id = u.id
		WHERE j_id <= $start $sql_filter
		ORDER BY j_id desc
		LIMIT 0, 50" ;
$result = mysqli_query($mysqli_link, $sql) or die("Erreur systeme a propos de l'access au journal: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
	if (!isset($first_id)) $first_id = $row['j_id'] ;
	$line_count ++ ;
	$last_id = $row['j_id'] ;
	$nameStyle = ($row['j_trusted_booker'] == 1) ? ' style="font-weight: bold;"' : '' ;
	switch (strtoupper($row['j_severity'])) {
		case 'F': $rowStyle = ' style="background-color: red; color: white;"' ; break ;
		case 'E': $rowStyle = ' style="background-color: pink;"' ; break ;
		case 'W': $rowStyle = ' style="background-color: lightyellow;"' ; break ;
		default: $rowStyle = '' ;
	}
	print("<tr$rowStyle>
		<td class=\"logCell\">$row[j_datetime]</td>
		<td$nameStyle class=\"logCell\">" . db2web($row['name']) . "</td>
		<td class=\"logCell\">$row[j_address]</td>
		<td class=\"logCellLeft\">" . db2web($row['j_message']) . "</td>
		<td class=\"logCellLeft\">" . db2web($row['j_uri']) . "</td>
		</tr>\n") ;
}
?>
</tbody>
</table>
Les heures sont les heures locales.<br/>
<?php
$first_id += 50 ;
$last_id -= 1 ;
print("<a href=\"?start=$first_id\">&lt;&lt</a> <a href=\"?start=$last_id\">&gt;&gt;</a><br/>\n") ;
$version_php = date ("Y-m-d H:i:s.", filemtime('journal.php')) ;
$version_css = date ("Y-m-d H:i:s.", filemtime('log.css')) ;
?>
<hr>
<div class="copyright">R&eacute;alisation: Eric Vyncke, octobre 2017-2020, pour RAPCS, Royal A&eacute;ro Para Club de Spa<br>
Versions: PHP=<?=$version_php?>, CSS=<?=$version_css?></div>
</body>
</html>
