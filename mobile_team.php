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
require_once 'facebook.php' ;

require_once 'mobile_header.php' ;

?> 
<div class="container">

<div class="row">
<h3>Equipe du SPW</h3>
</div> <!-- row -->

<div class="row">
	<img src="https://www.spa-aviation.be/attachments/article/314/SPW_team.png" width="1070" height="533"/>
</div> <!-- row -->


</div> <!-- container-->
</body>
</html>
