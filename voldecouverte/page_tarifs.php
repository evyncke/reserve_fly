<?php
// This PHP script is fully integrated as a component of Joomla
// Developped by Patrick Reginster in 2019
require_once 'action_tools.php' ;
?>
<!DOCTYPE html>
<html>
<body>
	<script src="https://www.spa-aviation.be/voldecouverte/script/offrir_bons.js"></script>
	<script src="https://www.spa-aviation.be/voldecouverte/script/offrir_circuits.js"></script>
	<script src="https://www.spa-aviation.be/voldecouverte/script/tarifs.js"></script>
	<script src="https://www.spa-aviation.be/voldecouverte/script/table_tools.js"></script>
	
    <h1 style='text-align: center;'>Summary of all tarifs</h1>
	

	<!-- Table des tarifs de base (Initiation, Prix a la minute vol decouverte, ...)-->
	<h2>Basic Tarifs</h2>
    <table style="width: 100%; margin-left: auto; margin-right: auto;" border="2" cellspacing="3">
	<tbody>
	<script >
		document.write("<tr style='background-color: #23ccdb; text-align: center; width: 10%;'>");
		 document.write("<td>Type</td>");
		 document.write("<td>Name</td>");
		 document.write("<td>Number of Passengers</td>");
		 document.write("<td>Tarif</td>");
		document.write("</tr>");
	    for (var i = 0;  i < my_tarifs.length; i++) {
		  document.write("<tr>");
	      var aType=  my_tarifs[i].type; 
 		  document.write("<td>"+aType+"</td>");
		  var aName=my_tarifs[i].name;
  		  document.write("<td>"+aName+"</td>");
	      var aNumberOfPassenger =  my_tarifs[i].passenger ;
   		  document.write("<td style='text-align: right;'>"+aNumberOfPassenger+"</td>");
          var aTarif =  my_tarifs[i].tarif ;
  		  document.write("<td style='text-align: right;'>"+aTarif+" €</td>");
		  document.write("</tr>");
	    }		
	</script>
	</tbody>
	</table>
	
	
	<!-- Table des circuits )-->
	<h2>Circuits</h2>
    <table style="width: 100%; margin-left: auto; margin-right: auto;" border="2" cellspacing="3">
	<tbody>
	<script >
		document.write("<tr style='background-color: #23ccdb; text-align: center; width: 10%;'>");
		 document.write("<td>Circuits</td>");
		 document.write("<td>Time (min)</td>");
		document.write("</tr>");
	    for (var i = 0;  i < my_offrir_circuits.length; i++) {
		  document.write("<tr>");
		  var aName=my_offrir_circuits[i].name;
  		  document.write("<td>"+aName+"</td>");
	      var aTime =  my_offrir_circuits[i].tarif;
   		  document.write("<td style='text-align: right;'>"+aTime+"</td>");
 		  document.write("</tr>");
	    }		
	</script>
	</tbody>
	</table>
	
	
	<!-- Table des bons a valoirs )-->
	<h2>Vouchers</h2>
    <table style="width: 100%; margin-left: auto; margin-right: auto;" border="2" cellspacing="3">
	<tbody>
	<script >
		document.write("<tr style='background-color: #23ccdb; text-align: center; width: 10%;'>");
		 document.write("<td>Voucher names</td>");
		 document.write("<td>Tarif</td>");
		document.write("</tr>");
	    for (var i = 0;  i < my_offrir_bons.length; i++) {
		  document.write("<tr>");
		  var aName=my_offrir_bons[i].name;
  		  document.write("<td>"+aName+"</td>");
	      var aTarif =  my_offrir_bons[i].tarif;
   		  document.write("<td style='text-align: right;'>"+aTarif+"</td>");
 		  document.write("</tr>");
	    }		
	</script>
	</tbody>
	</table>
		
	
	<!-- Table tarifs des circuits )-->
	<h2>Circuits</h2>
 	<script src="https://www.spa-aviation.be/voldecouverte/script/table_vol_decouverte.js"></script>
	
	<!-- Table tarifs Vol libre )-->
	<h2>Vols libres</h2>
 	<script src="https://www.spa-aviation.be/voldecouverte/script/table_vol_libre.js"></script>

	<!-- Table tarifs Vol libre par minute)-->
	<h2>Vols libres Tarifs par minutes</h2>
 	<script src="https://www.spa-aviation.be/voldecouverte/script/table_vol_libre_par_minute.js"></script>
		
</body>
</html>