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

ob_start("ob_gzhandler");
require_once "dbi.php" ;

$plane = (isset($_REQUEST['plane']) && $_REQUEST['plane'] != '') ? mysqli_real_escape_string($mysqli_link, $_REQUEST['plane']) : '%' ;
if (strlen($plane) > 6) die("Invalid value for plane") ;
$plane_group = ($plane == '%') ? '' : ', l_plane' ;
$days = (isset($_REQUEST['days'])) ? $_REQUEST['days'] : 90 ;
if (!is_numeric($days)) die("Invalid value for days") ;
$n = (isset($_REQUEST['n'])) ? $_REQUEST['n'] : 10 ;
if (!is_numeric($n)) die("Invalid value for n") ;
$class = (isset($_REQUEST['class'])) ? mysqli_real_escape_string($mysqli_link, $_REQUEST['class']) : 'A' ;
switch($class) {
	case 'A': $class_filter = " and m.group_id in ($joomla_pilot_group, $joomla_student_group)" ; break ;
	case 'P': $class_filter = " and m.group_id = $joomla_pilot_group" ; break ;
	case 'S': $class_filter = " and m.group_id = $joomla_student_group" ; break ;
	default: die("Invalid class") ;
}

?><html>
<head>
<link rel="stylesheet" type="text/css" href="log.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<title>Le top-<?=$n?> des pilotes</title>
<script>
function onChange() {
	window.location.href = '<?=$_SERVER['PHP_SELF']?>?n=' + document.getElementsByName('n')[0].value +
		'&days=' + document.getElementsByName('days')[0].value +
		'&class=' + document.getElementsByName('class')[0].value +
		'&plane=' + document.getElementsByName('plane')[0].value ;
}
</script>
</head>
<!-- Matomo -->
<script type="text/javascript">
  var _paq = window._paq = window._paq || [];
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
<body>
<center><h2>Le Top-<?=$n?> des Pilotes</h2></center>
<?php
if ($userId == 0) {
	die("<br/><font color=red>Vous devez &ecirc;tre connect&eacute;(e) pour afficher le top des pilotes.</font> ") ;
}

print("Voici le top <select name=\"n\" onChange=\"onChange() ;\">\n") ;
foreach (array(3, 10, 25, 50) as $key) {
	if ($n == $key)
		print("<option value=\"$key\" selected>$key</option>\n") ;
	else
		print("<option value=\"$key\">$key</option>\n") ;
}
print("</select> des ") ;
print("<select name=\"class\" onChange=\"onChange() ;\">\n") ;
foreach (array('A' => 'pilotes et &eacute;l&egrave;ves', 'P' => 'des pilotes', 'S'=> 'des &eacute;l&egrave;ves') as $key => $name) {
	if ($class == $key)
		print("<option value=\"$key\" selected>$name</option>\n") ;
	else
		print("<option value=\"$key\">$name</option>\n") ;
}
print("</select>") ;
print("sur <select name=\"days\" onChange=\"onChange() ;\">\n") ;
foreach (array(1 => 'la journ&eacute;e', 7 => 'la semaine', 30 => 'le mois', 90 => 'le trimestre', 365 => 'l\'ann&eacute;e') as $key => $name) {
	if ($days == $key)
		print("<option value=\"$key\" selected>$name</option>\n") ;
	else
		print("<option value=\"$key\">$name</option>\n") ;
}
print("</select>") ;
print("sur <select name=\"plane\" onChange=\"onChange() ;\">\n") ;
if ($plane == '%')
	print("<option value=\"%\" selected>tous les avions</option>\n") ;
else
	print("<option value=\"%\">tous les avions</option>\n") ;
$result = mysqli_query($mysqli_link, "select upper(id) as plane from $table_planes where actif != 0 and ressource = 0 order by plane asc") or die("Cannot select planes: ".mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
	if ($plane == $row['plane'])
		print("<option value=\"$row[plane]\" selected>$row[plane]</option>\n") ;
	else
		print("<option value=\"$row[plane]\">$row[plane]</option>\n") ;
}
print("</select>.<br/>") ;
?>
<i>Sur base de vos entr&eacute;es dans le carnet de route des avions apr&egrave;s vos vols. Vous pouvez mettre votre 'compte heure' &agrave; jour en cliquant
sur une r&eacute;servation pass&eacute;e ou via le bouton 'carnet de vol'.</i>
<table class="logTable">
<thead>
<tr>
<th class="logLastHeader">Position</th>
<th class="logLastHeader">Pilote</th>
<th class="logLastHeader">Nbr de vols</th>
<th class="logLastHeader">Dur&eacute;e totale<br/>en heures</th>
</tr>
</thead>
<tbody>
<?php
$sql = "select l_pilot, u.name as name, count(*) as flight_count, round(sum(timestampdiff(minute, l_start, l_end))/60,1) as flight_duration, hide_flight_time
		from $table_logbook left join jom_users u on l_pilot = u.id left join jom_user_usergroup_map m on m.user_id = u.id
		join $table_person p on p.jom_id = l_pilot
		where l_end > subdate(sysdate(), $days) and l_plane like '$plane' $class_filter
		group by l_pilot $plane_group
		order by flight_duration desc
		limit 0, $n" ;
$result = mysqli_query($mysqli_link, $sql) or die("Erreur systeme a propos de l'access au journal: " . mysqli_error($mysqli_link)) ;
$line = 1 ;
while ($row = mysqli_fetch_array($result)) {
	print("<tr>
		<td class=\"logCell\">$line</td>\n") ;
	if ($row['hide_flight_time'] and $row['l_pilot'] != $userId)
		print("<td class=\"logCell\">...</td>\n") ;
	else {
		$row['name'] = db2web($row['name']) ; 
		print("<td class=\"logCellLeft\"><a href=\"profile.php?displayed_id=$row[l_pilot]\">$row[name]</a></td>\n") ;
	}
	print("<td class=\"logCell\">$row[flight_count]</td>
		<td class=\"logCell\">$row[flight_duration]</td>
		</tr>\n") ;
	$line++ ;
}
?>
</tbody>
</table>
<?php
$version_php = date ("Y-m-d H:i:s.", filemtime('top.php')) ;
$version_css = date ("Y-m-d H:i:s.", filemtime('log.css')) ;
?>
<hr>
<div class="copyright">R&eacute;alisation: Eric Vyncke, octobre 2015 - Janvier 2024, pour RAPCS, Royal A&eacute;ro Para Club de Spa, ASBL<br>
Versions: PHP=<?=$version_php?>, CSS=<?=$version_css?></div>
</body>
</html>
