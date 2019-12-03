<html>
<body>
<?php
//readfile("https://www.vyncke.org/EBSP.TXT") ;
$metar_string = file_get_contents("https://www.vyncke.org/resa/metar.php?station=EBSP&format=json") ;
$metar = json_decode($metar_string, true) ;
switch ($metar['condition']) {
	case 'IMC': $color = 'pink'; break ;
	case 'VMC': $color = 'paleGreen' ; break ;
	default: $color = 'aliceBleu' ;
}
print("<div style=\"background-color: $color; border: 1px solid black;\">
$metar[METAR]
<br/>
<span style=\"font-size: x-small;\">Provided for your convenience only, do not trust the above METAR</span>
</div>") ;
?>
</body>
</html>
