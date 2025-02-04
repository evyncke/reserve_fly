<?php
/*
   Copyright 2014-2025 Eric Vyncke

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
if ($userId == 0 and !isset($_REQUEST['auth'])) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}

$sql_filter = [] ;

if (isset($_REQUEST['user']) && is_numeric($_REQUEST['user'])) {
	$pilot = $_REQUEST['user'] ;
	$result = mysqli_query($mysqli_link, "select name from $table_users where id = $pilot") 
		or die("Cannot read the pilot name " . mysqli_error($mysqli_link)) ;
	$row = mysqli_fetch_array($result) 
		or die("Unknown pilot") ;
	$pilot_name = db2web($row['name']) ;
	$sql_filter[] = "(l_pilot = $pilot or l_instructor = $pilot)" ;
} elseif (isset($_REQUEST['pilot']) && ($_REQUEST['pilot'] == 'all')) {
	$pilot =  $_REQUEST['pilot'] ;
	$pilot_name = "tous les pilotes" ;
} else {
	$pilot = $userId ;
	$pilot_name = $userFullName ;
	$sql_filter[] = "(l_pilot = $pilot or l_instructor = $pilot)" ;
}

if (isset($_REQUEST['period'])) {
	$period = $_REQUEST['period'] ;
	switch ($_REQUEST['period']) {
		case '2y': $sql_filter[] = 'l_start > date_sub(now(), interval 2 year)' ; break ;
		case '1y': $sql_filter[] = 'l_start > date_sub(now(), interval 1 year)' ; break ;
		case '3m': $sql_filter[] = 'l_start > date_sub(now(), interval 3 month)' ; break ;
		case '1m': $sql_filter[] = 'l_start > date_sub(now(), interval 1 month)' ; break ;
	}	
} else {
	$period = 'always' ; 
}

$header_postamble = '<!-- Load the MAP BOX scripts & CSS -->
<script src="https://api.mapbox.com/mapbox-gl-js/v0.42.0/mapbox-gl.js"></script>
<link href="https://api.mapbox.com/mapbox-gl-js/v0.42.0/mapbox-gl.css" rel="stylesheet"/>
<script src="arc.js"></script> <!-- GreatCircles for geodesic lines -->
<script src="mymap.js"></script>
' ;
$body_attributes=" onload=\"initMyMap($apt_longitude, $apt_latitude, $pilot, '$period', '$mapbox_token');init();\"" ;
require_once 'mobile_header5.php' ;

if ($userId != 62) journalise($userId, 'I', "Map displayed for $pilot_name ($period)") ;

$sql_filters = implode(' and ', $sql_filter) ;
if ($sql_filters != '') $sql_filters = "where $sql_filters" ;

?>

<script>
// The flights coordinates
var flightFeatureCollection = [
<?php
$sql = "select upper(l_from) as l_from, upper(l_to) as l_to,
	f.a_name as f_name, f.a_longitude as f_longitude, f.a_latitude as f_latitude,
	t.a_name as t_name, t.a_longitude as t_longitude, t.a_latitude as t_latitude
	from $table_logbook l 
	left join $table_airports as f on l_from = f.a_code
	left join $table_airports as t on l_to = t.a_code
	$sql_filters
	" ;

$result = mysqli_query($mysqli_link, $sql) or die("Erreur systeme a propos de l'access au carnet de route: " . mysqli_error($mysqli_link)) ;
$first = TRUE ;
$featured_airports = array() ;
while ($row = mysqli_fetch_array($result)) {
	$l_from = $row['l_from'] ;
	if (! array_key_exists($l_from, $featured_airports) && isset($row['f_longitude'])) {
		$featured_airports[$l_from] = array('longitude' => $row['f_longitude'], 'latitude' => $row['f_latitude'], 'name' => $row['f_name'], 'takeoff' => 1, 'landing' => 0) ;
	} else
		if (isset($row['f_longitude'])) 
			$featured_airports[$l_from]['takeoff']++ ;
	$l_to = $row['l_to'] ;
	if (! array_key_exists($l_to, $featured_airports) && isset($row['t_longitude'])) {
		$featured_airports[$l_to] = array('longitude' => $row['t_longitude'], 'latitude' => $row['t_latitude'], 'name' => $row['t_name'], 'takeoff' => 0, 'landing' => 1) ;
	} else
		if (isset($row['t_longitude'])) 
			$featured_airports[$l_to]['landing']++ ;
	if ($row['l_to'] == $row['l_from']) continue ; // No need to draw a line when takeoff landing are in the same airfield
	if ($row['f_longitude'] == '' or $row['t_longitude'] == '') continue ; // Some log entries do not have any valid coordinates
	print("\t// from $row[l_from] ($row[f_name]) to $row[l_to] ($row[t_name])\n") ;
	if ($first)
		$first = FALSE ;
	else
		print(",\n") ;
	print("\t{ type : 'Feature',
		properties : {},
		geometry : {
			type : 'LineString',
			coordinates : [[$row[f_longitude], $row[f_latitude] ], [$row[t_longitude], $row[t_latitude] ]]
		}
	}\n") ;
}
print("];\n") ;

// Now, let create another collection for the airports...
print("var airportFeatureCollection = [\n") ;
$first = TRUE ;
foreach ($featured_airports as $code => $airport) {
	if ($first)
		$first = FALSE ;
	else
		print(",\n") ;
	print("\t{ type : 'Feature',
		properties : {
			title : '$code',
			icon : 'airport',
			takeoff : $airport[takeoff],
			landing : $airport[landing],
			comment : '" . addslashes($airport['name']) . "'
		},
		geometry : {
			type : 'Point', 
			coordinates : [$airport[longitude], $airport[latitude]]
		}\n\t}") ;
}

?>

] ;

</script>
<div class="container-fluid">

<div class="page-header">
<h2>Les vols de <?=$pilot_name?> sur une carte</h2>
</div><!-- page-header -->

<?php
if ($userId != 0) { // Only logged-in user can change the options
?>
<div class="XXXrow"><!-- control are taking a full row in this case...-->
	Période: <select id="periodSelect" onchange="mymapSelectChanged();">
	<option value="always">depuis toujours</option>
	<option value="2y">2 ans</option>
	<option value="1y">1 an</option>
	<option value="3m">3 mois</option>
	<option value="1m">1 mois</option>
</select>.

En tant que membre connecté(e), vous pouvez voir les vols des autres membres: 
<select id="pilotSelect" onchange="mymapSelectChanged();">
<option value="all">Tous les pilotes</option>
</select>. A partager via ce <a href=<?=$_SERVER['PHP_SELF'] . "?user=$pilot&period=$period&auth=" . md5($pilot . $period . $shared_secret)?>>lien</a>
avec des non-membres y compris réseaux sociaux.
</div><!-- row -->
<?php
} // If (not logged in)
?>

<div class="row">
<div id='mapContainer' style='position: relative;'>
	<div id='map' style='width: 100vw; height: 90vh;'></div>
<!--div id='airportInfo' style='display: none; position: absolute; margin: 0px auto; padding: 10px; text-align: left; color: black; background: white; opacity: 0.7;'></div-->
</div> <!-- mapContainer -->
</div><!-- row -->

<br/>
<div class="row">
<p><small>Sur base des données que vous avez entrées après les vols dans le
carnet de route des avions RAPCS ou celles que vous avez entrées via votre carnet de vols (à utiliser
pour des vols sur des avions hors RAPCS). Ce ne sont pas les trajets exacts.</small></p>
</div><!-- row -->
</div> <!-- container-->
</body>
</html>