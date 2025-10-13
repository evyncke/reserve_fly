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

if (($_SERVER['SERVER_NAME'] == 'm.ebsp.be') or ($_SERVER['SERVER_NAME'] == 'm.spa-aviation.be')) 
	header('Location: https://www.spa-aviation.be/resa/mobile.php?news');      
else if ($_SERVER['SERVER_NAME'] == 'my.spa-aviation.be') 
    header('Location: https://www.spa-aviation.be/index.php/fr/homepage');      
else if (($_SERVER['SERVER_NAME'] == 'resa.spa-aviation.be') or ($_SERVER['SERVER_NAME'] == 'resa.ebsp.be'))
	header('Location: https://www.spa-aviation.be/resa/reservation.php');      
else
	header('Location: https://www.spa-aviation.be/resa/reservation.php');      
?>