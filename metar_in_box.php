<html>
<body>
<?php
/*
   Copyright 2014-2019 Eric Vyncke

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
