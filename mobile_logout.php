<?php
/*
   Copyright 2013 Eric Vyncke

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
require_once 'facebook.php' ;


session_start() ;
unset($_SESSION['fb_access_token']);
unset($_SESSION['jom_id']);
session_unset();
session_destroy();

	header("Location: https://resa.spa-aviation.be/mobile.php?logout") ;
journalise($userId, 'I', "$username is disconnected from the mobile web") ;

exit() ;

?> 
