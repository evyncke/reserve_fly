<?php      
/*
   Copyright 2014-2026 Eric Vyncke

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

require_once "auth.php" ;

switch ($_SERVER['SERVER_NAME']) {
	case 'm.ebsp.be':
	case 'm.spa-aviation.be':
		header('Location: ' . SITE_URL . 'mobile_welcome.php?news');
		break;
	case 'my.spa-aviation.be':
		header('Location: https://www.spa-aviation.be/index.php/fr/homepage');
		break;
	case 'resa.spa-aviation.be':
	case 'resa.ebsp.be':
		header('Location: ' . SITE_URL . 'mobile_welcome.php');
		break;
	default:
		header('Location: ' . SITE_URL . 'reservation.php');
}
exit ;
?>