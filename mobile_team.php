<?php
/*
   Copyright 2013-2019 Eric Vyncke

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

ob_start("ob_gzhandler");

require_once "dbi.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}

require_once 'mobile_header5.php' ;

?> 
<div class="container-fluid">

<div class="row">
<h3>Equipe du SPW</h3>
</div> <!-- row -->

<div class="row">
	<img class="img-fluid" src="https://www.spa-aviation.be/attachments/article/314/SPW_team.png" width="1070" height="533"/>
</div> <!-- row -->


</div> <!-- container-->
</body>
</html>
