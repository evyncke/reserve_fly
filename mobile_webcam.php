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

$cam = (isset($_REQUEST['cam'])) ? $_REQUEST['cam'] : '' ;
if (! is_numeric($cam)) die("Invalid camera ID") ;
if ($cam >= count($webcam_uris)) die("Invalid camera ID") ;

require_once 'mobile_header.php' ;

?> 
<div class="container">

<a href="<?=$webcam_uris[$cam]?>">
<img class="img-responsive" src="<?=$webcam_uris[$cam]?>" id="webcamImg">
</a>
<script>
function refreshWebcam() {
	document.getElementById('webcamImg').src = "<?=$webcam_uris[$cam]?>" + "?random=" + new Date().getTime() ;
	console.log(document.getElementById('webcamImg').src) ;
}
refreshWebcam() ;
setInterval(refreshWebcam, 1000 * 30) ; // Refresh every 30 seconds
</script>

</div> <!-- container-->
</body>
</html>
