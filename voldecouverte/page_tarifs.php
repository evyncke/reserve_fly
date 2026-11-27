<?php
// This PHP script is fully integrated as a component of Joomla
// Developped by Patrick Reginster in 2019
require_once 'action_tools.php' ;
$flight_url_prefix = '../' ; // the header is located in the parent directory
require_once '../flight_header.php' ;
?>
	<script src="offrir_bons.js"></script>
	<script src="offrir_circuits.js"></script>
	<script src="tarifs.js"></script>
	<script src="tarif_tools.js"></script>
	<script>
	<?php
	 	function printJavaScriptVariableFromFile($theVarName,$theURL) {
			$text=file_get_contents($theURL) 
				or journalise(0, "E", "Cannot access file $theURL");
			print("var $theVarName=$text;\n");
		}
	  // Add in the file: var myTarifs = {content of the file};
	  printJavaScriptVariableFromFile("myTarifs","tarifs.json");
	?>

// Include fs module
//const fs = require('FS');
	</script>
	
	<!-- TODO: is the hardcoded 2024 always valid ? -->
    <h2 class="text-center">Summary of all tarifs (2024)</h2>
	

	<!-- Table des tarifs de base (Initiation, Prix a la minute vol decouverte, ...)-->
	<h2>Basic Tarifs</h2>
	<div class="table-responsive">
		<table class="table table-bordered table-striped align-middle w-auto">
	<thead class="table-info">
		<tr class="text-center">
		<th>Type</th>
		<th>Name</th>
		<th>Number of Passengers</th>
		<th>Tarif</th>
		</tr>
	</thead>
	<tbody>
	<?php 
	$tarifs = json_decode(file_get_contents("tarifs.json"), true)
		or journalise(0, "E", "Invalid JSON file: tarifs.json");
	foreach ($tarifs as $tarif) {
		//var_dump($tarif );
		//print("tarif:$tarif[name]=$tarif[tarif]<br>");
		print("<tr>");
		$aType= cleanHTML($tarif["type"]); 
		print("<td>$aType</td>");
		$aName=cleanHTML($tarif["name"]);
		print("<td>$aName</td>");
		if(isset($tarif["passenger"])) {
			$aNumberOfPassenger =  (float) $tarif["passenger"] ;
			print("<td class=\"text-end\">$aNumberOfPassenger</td>");
		}
		else {
			print("<td class=\"text-end\">-</td>");
		}
		$aTarif =  (float) $tarif["tarif"];
		print("<td class=\"text-end\">$aTarif €</td>");
		print("</tr>");
	  }
	?>
	</tbody>
	</table>
	</div><!-- table responsive -->
	
	<!-- Table des circuits )-->
	<h2>Circuits</h2>
	<div class="table-responsive">
		<table class="table table-bordered table-striped align-middle w-auto">
		<thead class="table-info">
		<tr class="text-center">
			<th>Circuit</th>
			<th>Time (Min)</th>
		</tr>
		</thead>
		<tbody>
	<?php 
	$circuits = json_decode(file_get_contents("circuits.json"), true)
		or journalise(0, "E", "Invalid JSON file: circuits.json");
	foreach ($circuits as $circuit) {
		$aName=cleanHTML($circuit["name"]);
		if($aName!="") {
			print("<tr>");
			print("<td>$aName</td>");
			$aTime =  (int) $circuit["duree"];
			print("<td class=\"text-end\">$aTime</td>");
			print("</tr>");
		}
	  }
	?>
	</tbody>
	</table>
</div><!-- table responsive-->
	
	
	<!-- Table des bons a valoirs )-->
	<h2>Vouchers</h2>
	<div class="table-responsive">
		<table class="table table-bordered table-striped align-middle w-auto">
		<thead class="table-info">
		<tr class="text-center">
		<th>Voucher names</th>
		<th>Tarif</th>
		</tr>
		</thead>
		<tbody>
	<?php 
	$bons = json_decode(file_get_contents("bons.json"), true)
		or journalise(0, "E", "Invalid JSON file: bons.json");
	foreach ($bons as $bon) {
		$aName=cleanHTML($bon["name"]);
		if($aName!="") {
			print("<tr>");
			print("<td>$aName</td>");
			$aTarif =  (float) $bon["tarif"];
			print("<td class=\"text-end\">$aTarif</td>");
			print("</tr>");
		}
	  }
	?>
	</tbody>
	</table>
	</div><!-- responsive table -->		
	
	<!-- Table tarifs des circuits )-->
	<h2>Circuits</h2>
 	<script src="table_vol_decouverte.js"></script>
	
	<!-- Table tarifs Vol libre )-->
	<h2>Vols libres</h2>
 	<script src="table_vol_libre.js"></script>

	<!-- Table tarifs Vol libre par minute)-->
	<h2>Vols libres Tarifs par minutes</h2>
 	<script src="table_vol_libre_par_minute.js"></script>
<?php require_once '../flight_trailer.php' ; ?>
