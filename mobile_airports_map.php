<?php
// This PHP script is fully integrated as a component of Joomla
// Developped by Patrick Reginster in 2021
?>
<!DOCTYPE html>
<html>
<body>
	
 	
<?php
  $icao=$_GET['icao'] ;
  $position=$_GET['position'] ;
  $airportMapsFolder='../airports/maps/';  

  
  echo "<h1 style='text-align: center;'>Airport Map ".$icao."</h1>";
  echo "<h1 style='text-align: center;'>Position ".$position."</h1>";
  /*
  $airportFile=$airportMapsFolder.$icao.'.pdf'; 
  if(file_exists($airportFile)) {
	  echo '<iframe src="'.$airportFile.'" width="100%" height="1000" style="max-width: 100%;max-height: 100%; border:1px solid black;">';
	  echo '</iframe>';
  }
  */
  $airportFile=$airportMapsFolder.$icao.'.jpg'; 
  //echo $airportFile.'<br>';
  if(file_exists($airportFile)) {
	  echo '<img src="'.$airportFile.'" width="100%"  style="max-width: 100%;max-height: 100%; border:1px solid black;">';
	  //echo '</iframe>';
  }
  for ($x = 1; $x <= 9; $x++) {
	 $hasAirportFile=false;
	 /*
   	 $airportFile=$airportMapsFolder.$icao.'_'.strval($x).'.pdf';
      if(file_exists($airportFile)) {
 	 	  echo '<iframe src="'.$airportFile.'" width="100%" height="1000" style="max-width: 100%;max-height: 100%; border:1px solid black;">';
 	 	  echo '</iframe>';
	 	  $hasAirportFile=true;
   	 }
	 */
  	 $airportFile=$airportMapsFolder.$icao.'_'.strval($x).'.jpg';
     if(file_exists($airportFile)) {
	 	  echo '<img src="'.$airportFile.'" width="100%" style="max-width: 100%;max-height: 100%; border:1px solid black;">';
		 
		  if($x==1) {
			  /*
			  echo '<br>Print<br>';
			  $handle = printer_open();
			  printer_write($handle, "Text to print:");
			  printer_close($handle);
	 	 	  echo '<iframe src=" https://www.sia.aviation-civile.gouv.fr/dvd/eAIP_31_DEC_2020/Atlas-VAC/PDF_AIPparSSection/VAC/AD/AD-2.LFGW.pdf" width="100%" height="1000" style="max-width: 100%;max-height: 100%; border:1px solid black;">';		
			  */
			   
	     }
		  
 	 	  $hasAirportFile=true;
 	 }
  	 $airportFile=$airportMapsFolder.$icao.'00'.strval($x).'.jpg';
     if(file_exists($airportFile)) {
	 	  echo '<img src="'.$airportFile.'" width="100%" style="max-width: 100%;max-height: 100%; border:1px solid black;">';
 	 	  $hasAirportFile=true;
 	 }
  	 if(!$hasAirportFile) {
		break;
	 }
   } 
  
  
  //echo '<iframe src="'.$airportFile.'" width="100%" height="1000" style="border:1px solid black;">';
  //echo '</iframe>';
 

  //echo 'Carte '.$icao. ' found:'.$airportFile."<br>Test<br>Test<br>";
  //echo '<img src="'.$airportFile.'">';
  echo '<div style="width: 100%"><iframe width="100%" height="600" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.com/maps?width=100%25&amp;height=600&amp;hl=en&amp;q='.$position.'+(PatrickMap)&amp;t=k&amp;z=15&amp;ie=UTF8&amp;iwloc=B&amp;output=embed"></iframe></div>';
 ?>

<!---
<div style="width: 100%"><iframe width="100%" height="600" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.com/maps?width=100%25&amp;height=600&amp;hl=en&amp;q=50.,5.0+(My%20Business%20Name)&amp;t=k&amp;z=14&amp;ie=UTF8&amp;iwloc=B&amp;output=embed"></iframe><a href="https://www.maps.ie/route-planner.htm">Plan A Journey</a></div>
	
<div style="width: 100%;"><iframe style="border: 0;" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d20000.0!2d5.0!3d50.0!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0000000000000000%3A0x6a4c2e92a3f390f6!2sRoyal+A%C3%A9ro+Para+Club+de+Spa!5e0!3m2!1sfr!2sbe!4v1445930704385" width="100%" height="300" frameborder="0" allowfullscreen="allowfullscreen"></iframe></div>

<div style="width: 100%;"><iframe style="border: 0;" src="https://www.google.com/maps/embed/v1/streetview?location=46.414382,10.013988&heading=210&pitch=10&fov=35"></iframe></div>
  -->
</body>
</html>