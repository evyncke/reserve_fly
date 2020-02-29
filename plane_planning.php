<?php
/*
   Copyright 2020 Eric Vyncke

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

ob_start("ob_gzhandler");

require_once "dbi.php" ;

?><html lang="fr">
<head>
<link rel="stylesheet" type="text/css" href="log.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<title>Echéances des avions</title>
</head>
<body>
<h1>Echéances des avions</h1>
<table border="1">
<thead>
<tr><th>Plane</th>   <th colspan="3">Inspections</th>        <th colspan="2">Time limit</th> <th colspan="3">Circ. Equip. 4ed5</th>    <th>&lt; 30 days</th>            <th>Mag.</th> <th>Pesage</th></tr>
<tr><th>Current</th> <th>50h</th><th>100h</th><th>200h</th>  <th>Eng</th><th>Prop</th>       <th>ATC</th><th>Enc.</th><th>Alti</th>    <th>CN</th>                      <th>500h</th> <th>10 y </th></tr>
</thead>
<?php
$result = mysqli_query($mysqli_link, "SELECT * from $table_planes WHERE ressource = 0 AND actif != 0 ORDER BY id")
	or die("Cannot read $tables_planes: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
	print("<tr><td>" . strtoupper($row['id']) . "<br/>$row[compteur]h</td>") ;
	// Type_entretien... human encoding :-( 50h, 50h->200h, 100h
	if (stripos($row['type_entretien'], '200h') !== FALSE)
		print("<td></td><td></td><td>$row[entretien]</td>") ;
	else if (stripos($row['type_entretien'], '100h') !== FALSE)
		print("<td></td><td>$row[entretien]</td><td></td>") ;
	else if (stripos($row['type_entretien'], '50h') !== FALSE)
		print("<td>$row[entretien]</td><td></td><td></td>") ;
	else // Assuming 50h
		print("<td>$row[entretien] ????</td><td></td><td></td>") ;
	print("<td>$row[limite_moteur_heure]h<br/>$row[limite_moteur_12ans]</td><td>$row[limite_helice]</td>
		<td></td><td></td><td></td>
		<td>$row[cn]</td>
		<td>$row[limite_magnetos]</td>
		<td>$row[pesage]</td>
		</tr>\n") ;
}
?>
<tbody>
</tbody>
</body>
</html>