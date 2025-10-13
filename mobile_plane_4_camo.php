<?php
/*
   Copyright 2020-2024 Eric Vyncke

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

/* Reference is https://tools.ietf.org/html/rfc5545
Charset is UTF-8
https://icalendar.org/validator.html
TODO
The "charset" Content-Type parameter MUST be used in MIME transports
   to specify the charset being used. */

require_once "dbi.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}

require_once 'mobile_header5.php' ;
require_once 'incident.class.php' ;
if (!($userIsAdmin or $userIsInstructor or $userIsMechanic)) journalise($userId, "F", "Vous devez être admin ou FI ou mecano pour voir cette page") ;

$first_day = date('Y-m-d', time() - 7 * 24 * 60 * 60) ; // Go back 7 days
$last_day = date('Y-m-d') ;
$first_day_year = date('Y') . '-01-01' ;

function Cell($m) {
    if ($m == '' or $m == 0)
        print("<td></td>") ;
    else {
        $hours = floor($m / 60) ;
        $minutes = $m % 60 ;
        print("<td>$hours:$minutes</td>") ;
    }
}

function delta2BSClass($n) {
    if ($n > 5)
        return '' ;
    else if ($n < 0)
        return 'text-bg-danger' ;
    return 'text-bg-warning' ;
}

function toHourMinute($totalMimutes) {
    $hours=intval($totalMimutes/60);
    $minutes=$totalMimutes-60*$hours;
    return $hours.":".$minutes;
}

?>
<div class="container-fluid">
<h2>Rapport hebdomadaire de la flotte du RAPCS pour le  CAMO</h2>
<table class="col-sm-12 col-lg-8 table table-hover table-bordered table-striped">
<thead>
<tr class="text-center"><th>Avion</th><th>Compteur Moteur</th><th>Compteur limite</th><th>Delta</th><th>Prochaine maintenance</th><th>Problèmes techniques - ATL</th></tr>
</thead>
<tbody>
<?php

// Per plane: weekly counters (engine, flight, landings)
$result = mysqli_query($mysqli_link, "SELECT UPPER(p.id) AS id, SUM(l_day_landing+l_night_landing) AS landings,
        SUM(60*(l_end_hour-l_start_hour) + l_end_minute-l_start_minute) AS engine_minutes,
        SUM(60*(l_flight_end_hour-l_flight_start_hour) + l_flight_end_minute-l_flight_start_minute) AS flight_minutes
    FROM $table_planes p JOIN $table_logbook ON p.id = l_plane
    WHERE p.ressource = 0 AND p.actif != 0 AND '$first_day' <= date(l_start) AND date(l_start) <= '$last_day'
    GROUP BY p.id
    ORDER BY p.id")
	or die("Cannot read weekly $tables_planes: " . mysqli_error($mysqli_link)) ;
$weekly = array() ;
while ($row = mysqli_fetch_array($result)) {
    $weekly[$row['id']] = $row ;
}
// Per plane: YTD counters (engine, flight, landings)
$result = mysqli_query($mysqli_link, "SELECT UPPER(p.id) AS id, type_entretien, entretien, compteur_vol,
        MAX(60*l_end_hour+l_end_minute) AS latest_engine,
        MAX(l_flight_end_hour) AS latest_flight,
        SUM(l_day_landing+l_night_landing) AS landings,
        SUM(60*(l_end_hour-l_start_hour) + l_end_minute-l_start_minute) AS engine_minutes,
        SUM(60*(l_flight_end_hour-l_flight_start_hour) + l_flight_end_minute-l_flight_start_minute) AS flight_minutes
    FROM $table_planes p JOIN $table_logbook ON p.id = l_plane
    WHERE p.ressource = 0 AND p.actif != 0 AND '$first_day_year' <= date(l_start) AND date(l_start) <= '$last_day'
    GROUP BY p.id
    ORDER BY p.id")
	or die("Cannot read weekly $tables_planes: " . mysqli_error($mysqli_link)) ;
$ytd = array() ;
while ($row = mysqli_fetch_array($result)) {
    $ytd[$row['id']] = $row ;
}
// Per plane: ATL
$atl = array() ;
$incidents = new Incidents(null, ['opened', 'inprogressnoaog', 'inprogressaog', 'camonoaog', 'camoaog']) ;
foreach($incidents as $incident) {
    $description = "<li class=\"text-start\"><a href=\"mobile_incident.php?incident=$incident->id\">#$incident->id</a> <span class=\"badge bg-primary\"><i class=\"bi bi-clock-fill\"></i> $incident->daysPending</span>
        $incident->severity: $incident->firstText</li>" ;
    if (isset($atl[$incident->plane]))
        $atl[$incident->plane] .= $description ;
    else
        $atl[$incident->plane] = $description ;
}


foreach($ytd as $id => $ytd_row) {
    if (isset($weekly[$id])) // Some aircfrafts have not flown this week...
        $weekly_row = $weekly[$id] ;
    else
        $weekly_row = array('engine_minutes' => '', 'flight_minutes' => '', 'landings' => '') ;
    print("<tr class=\"text-center\"><td class=\"text-nowrap\">$id</td>") ;
    if ($ytd_row['compteur_vol'] != 0)
        print("<td>".toHourMinute($ytd_row['latest_flight'])."</td>") ;
    else
        print("<td>".toHourMinute($ytd_row['latest_engine'])."</td>") ;
    print("<td>$ytd_row[entretien]:00</td>") ;
    if ($ytd_row['compteur_vol'] != 0) {
        print("<td class=\"" . delta2BSClass(60*$ytd_row['entretien'] - $ytd_row['latest_flight']). "\">" . toHourMinute((60*$ytd_row['entretien'] - $ytd_row['latest_flight'])) . "</td>") ;
    } else {
        print("<td class=\"" . delta2BSClass(60*$ytd_row['entretien'] - $ytd_row['latest_engine']). "\">" . toHourMinute((60*$ytd_row['entretien'] - $ytd_row['latest_engine'])) . "</td>") ;
    }
    print("<td>$ytd_row[type_entretien]</td>") ;
    if (isset($atl[$id]))
        print("<td>$atl[$id]</td>") ;
    else
        print("<td></td>") ;

    print("</tr>") ;
}
?>
</tbody>
</table>
</div><!-- container-fluid -->
</body>
</html>