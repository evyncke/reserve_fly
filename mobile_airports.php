<?php
/*
   Copyright 2025-2025 Patrick Reginster

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
if ($userId == 0) {
	//header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	//exit ;
}
$plane = (isset($_REQUEST['plane'])) ? mysqli_real_escape_string($mysqli_link, strtoupper($_REQUEST['plane'])) : 'OO-ALD' ;
$body_attributes = 'style="height: 100%; min-height: 100%; width:100%;" onload="init();"' ;
$header_postamble = "
" ;

require_once 'mobile_header5.php' ;
?>
    <h1 style='text-align: center;'>Airport Information</h1>
<?php
//Load the JSON feed
    $airportFolder='../airports/';
    $airportMapFolder='../airports/maps/';
    $file = 'mobile_airports.json';
    //Check if JSON file is actually found.
    if (file_exists($file)) {
    	//echo "The file $file exists";
    } else {
    	echo "The file $file does not exist";
    }
	
    $json = file_get_contents($file);
	//echo '<br>'.$json.'<br>Test<br>';
    $json_data = json_decode($json);
	//echo var_dump($json_data);
	
	//echo '<br><br>Airports:<br>';
    $airports = $json_data->Airports;
 	//echo var_dump($airports);
 ?>
   	<!-- Table des tarifs de base (Initiation, Prix a la minute vol decouverte, ...)-->
    <table style="width: 100%; margin-left: auto; margin-right: auto;" border="2" cellspacing="3">
  	<tbody>
  	<tr style='background-color: #23ccdb; text-align: center; width: 10%;'>
  	<td>Airport</td>
  	</tr>
	<?php
    foreach ($airports as $airport) {
        $name= $airport->Name;
		$icao=$airport->ICAO;
		$informations=$airport->Information;
		$phone="";
		if(property_exists($airport,"Phone")) {
			$phone=$airport->Phone;
		}
		$phoneName="";
		if(property_exists($airport,"PhoneName")) {
			$phoneName=$airport->PhoneName;
		}
		$position="";
		if(property_exists($airport,"Position")) {
			$position=$airport->Position;
		}
        echo "<tr style='background-color: #33FF3F; text-align: left; width: 10%;'>";
		$hasAirportMap=false;
		
		$airportFile=$airportMapFolder.$icao.'.pdf';
        if(file_exists($airportFile)) {
			echo "<td><a target='_blank' href='".$airportFile."'> ".$name . ": ".$icao."</a>";
			if(!empty($position)) {
				echo "&nbsp;&nbsp;&nbsp;<a target='_blank' href='mobile_airports_map.php?icao=".$icao."&position=".$position."'>(Satellite View)</a>";
			}
			echo "</td></tr>";			
			//echo "<td><a target='_blank' href='".$airportFile."'> ".$name . ": ".$icao."</a></td></tr>";			
			$hasAirportMap=true;
		}
		else {
		
			$airportFile=$airportMapFolder.$icao.'.jpg';
	        if(file_exists($airportFile)) {
				$hasAirportMap=true;
			}
	        if(!$hasAirportMap) {
			  for ($x = 1; $x <= 10; $x++) {
				  /*
	  	  		$airportFile=$airportMapFolder.$icao.'_'.strval($x).'.pdf';
	  	        if(file_exists($airportFile)) {
	  	  			$hasAirportMap=true;
	  				break;
	  	  		}
				  */
	  	  		$airportFile=$airportMapFolder.$icao.'_'.strval($x).'.jpg';
	  	        if(file_exists($airportFile)) {
	  	  			$hasAirportMap=true;
	  				break;
	  	  		}
		  		$airportFile=$airportMapFolder.$icao.'00'.strval($x).'.jpg';
		        if(file_exists($airportFile)) {
		  			$hasAirportMap=true;
					break;
		  		}
			  } 
		    }
	        if($hasAirportMap) {
				echo "<td><a href='page_airports_map.php?icao=".$icao."'> ".$name . ": ".$icao."</a></td></tr>";			
				//echo "<td><a href='https://www.spa-aviation.be/patrick/ebsp/airports/".$icao.".pdf'> ".$name . ": ".$icao."&ensp;(Map)</a></td></tr>";
			}
			else {
				echo "<td>".$name . ": ".$icao."</td></tr>";
			}
	    }
	    foreach ($informations as $information) {			
			echo '<tr><td>'.$information.'</td></tr>';
		}
		if(!empty($phone)) {
			if(empty($phoneName)) {
				$phoneName="Phone:";
			}
			echo "<tr><td>".$phoneName.": <a href='tel:".$phone."'>".$phone."</a></td></tr>";
		}
     }	
?>	
	</tbody>
  	</table>

</body>
</html>