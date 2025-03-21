<?php
/*
   Copyright 2014-2024 Eric Vyncke

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

// Everything is now moved to mobile_profile
include('dbi.php') ;
journalise($userId, "D", "Using old link for profile.php, refererer: $_SERVER[HTTP_REFERER]") ;

if (isset($_REQUEST['displayed_id']) and $_REQUEST['displayed_id'] != '') {
	$displayed_id = $_REQUEST['displayed_id'] ;
	header("Location: https://" .  $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/mobile_profile.php?displayed_id=$displayed_id") ;
} else
	header("Location: https://" .  $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/mobile_profile.php") ;
?>