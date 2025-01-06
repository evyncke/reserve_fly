<?php
// This PHP script is fully integrated as a component of Joomla
// Developped by Patrick Reginster in 2019
require_once 'action_tools.php' ;
?>
<!DOCTYPE html>
<html>
<body>
	<script src="https://www.spa-aviation.be/resa/voldecouverte/offrir_bons.js"></script>
	<script src="https://www.spa-aviation.be/resa/voldecouverte/offrir_circuits.js"></script>
	<script src="https://www.spa-aviation.be/resa/voldecouverte/tarifs.js"></script>
	<!---<script src="https://www.spa-aviation.be/resa/voldecouverte/table_tools.js"></script>-->
	<script src="https://www.spa-aviation.be/resa/voldecouverte/tarif_tools.js"></script>
	<script>
	<?php
	 	function printJavaScriptVariableFromFile($theVarName,$theURL) {
			$text=file_get_contents($theURL);
			print("var $theVarName=$text;\n");
		}
	  // Add in the file: var myTarifs = {content of the file};
	  printJavaScriptVariableFromFile("myTarifs","https://www.spa-aviation.be/resa/voldecouverte/tarifs.json");
	?>

// Include fs module
//const fs = require('FS');

// Calling the readFileSync() method
// to read 'input.txt' file
//const data = fs.readFileSync('https://www.spa-aviation.be/resa/voldecouverte/tarifs.json',
//    { encoding: 'utf8', flag: 'r' });
//	var aJSON=  JSON.parse(data);

// Display the file data
//console.log(data);
/*
	var myTarifs={};
	var myTarifsReady=false;
	getFileAsJSON("https://www.spa-aviation.be/resa/voldecouverte/tarifs.json").then(
		function(tarifs) {
			myTarifs=tarifs;
			myTarifsReady=true;
	        //document.write("JSON Read<br>");
		},
		function(error) {
			var anError=error;
		}
	);
	*/
	</script>
	
    <h1 style='text-align: center;'>Summary of all tarifs (2024)</h1>
	

	<!-- Table des tarifs de base (Initiation, Prix a la minute vol decouverte, ...)-->
	<h2>Basic Tarifs</h2>
    <table style="width: 100%; margin-left: auto; margin-right: auto;" border="2" cellspacing="3">
	<tbody>
		<tr style='background-color: #23ccdb; text-align: center; width: 10%;'>
		<td>Type</td>
		<td>Name</td>
		<td>Number of Passengers</td>
		<td>Tarif</td>
		</tr>
	<?php 
	$tarifs = json_decode(file_get_contents("tarifs.json"), true);
	foreach ($tarifs as $tarif) {
		//var_dump($tarif );
		//print("tarif:$tarif[name]=$tarif[tarif]<br>");
		print("<tr>");
		$aType= $tarif["type"]; 
		print("<td>$aType</td>");
		$aName=$tarif["name"];
		print("<td>$aName</td>");
		if(isset($tarif["passenger"])) {
			$aNumberOfPassenger =  $tarif["passenger"] ;
			print("<td style='text-align: right;'>$aNumberOfPassenger</td>");
		}
		else {
			print("<td style='text-align: right;'>-</td>");
		}
		$aTarif =  $tarif["tarif"];
		print("<td style='text-align: right;'>$aTarif €</td>");
		print("</tr>");

	  }
	?>
	</tbody>
	</table>
	
	
	<!-- Table des circuits )-->
	<h2>Circuits</h2>
    <table style="width: 100%; margin-left: auto; margin-right: auto;" border="2" cellspacing="3">
	<tbody>
	<tr style='background-color: #23ccdb; text-align: center; width: 10%;'>
		<td>Circuit</td>
		<td>Time (Min)</td>
		</tr>
	<?php 
	$circuits = json_decode(file_get_contents("circuits.json"), true);
	foreach ($circuits as $circuit) {
		//var_dump($circuit );
		//print("tarif:$tarif[name]=$tarif[tarif]<br>");
		$aName=$circuit["name"];
		if($aName!="") {
			print("<tr>");
			print("<td>$aName</td>");
			$aTime =  $circuit["duree"];
			print("<td style='text-align: right;'>$aTime</td>");
			print("</tr>");
		}
	  }
	?>
	</tbody>
	</table>
	
	
	<!-- Table des bons a valoirs )-->
	<h2>Vouchers</h2>
    <table style="width: 100%; margin-left: auto; margin-right: auto;" border="2" cellspacing="3">
	<tbody>
		<tr style='background-color: #23ccdb; text-align: center; width: 10%;'>
		<td>Voucher names</td>
		<td>Tarif</td>
		</tr>
	<?php 
	$bons = json_decode(file_get_contents("bons.json"), true);
	foreach ($bons as $bon) {
		$aName=$bon["name"];
		if($aName!="") {
			print("<tr>");
			print("<td>$aName</td>");
			$aTarif =  $bon["tarif"];
			print("<td style='text-align: right;'>$aTarif</td>");
			print("</tr>");
		}
	  }
	?>
	</tbody>
	</table>
		
	
	<!-- Table tarifs des circuits )-->
	<h2>Circuits</h2>
 	<script src="https://www.spa-aviation.be/resa/voldecouverte/table_vol_decouverte.js"></script>
	
	<!-- Table tarifs Vol libre )-->
	<h2>Vols libres</h2>
 	<script src="https://www.spa-aviation.be/resa/voldecouverte/table_vol_libre.js"></script>

	<!-- Table tarifs Vol libre par minute)-->
	<h2>Vols libres Tarifs par minutes</h2>
 	<script src="https://www.spa-aviation.be/resa/voldecouverte/table_vol_libre_par_minute.js"></script>
</body>
</html>