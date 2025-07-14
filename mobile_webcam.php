<?php
/*
   Copyright 2013-2025 Eric Vyncke

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

$cam = (isset($_REQUEST['cam'])) ? $_REQUEST['cam'] : 0 ;
if (! is_numeric($cam)) die("Invalid camera ID") ;
if ($cam >= count($webcam_uris)) die("Invalid camera ID") ;
$previous_cam = ($cam-1 < 0) ? count($webcam_uris) - 1 : $cam - 1 ;
$next_cam = ($cam+1 >= count($webcam_uris)) ? 0 : $cam + 1 ;

$need_swiped_events = true ; // Allow swipe events on this pag
require_once 'mobile_header5.php' ;

?> 
<div class="container-fluid">
<h2 style="display: none;">Webcam #<?=$cam?></h2>
<figure class="figure">
    <a href="<?=$webcam_uris[$cam]?>">
    <img class="figure-img img-fluid" style="width: 80vw;" src="<?=$webcam_uris[$cam]?>" id="webcamImg">
    </a>
</figure>
</div> <!-- container-->

<script>
function refreshWebcam() {
    var webCamURI = '<?=$webcam_uris[$cam]?>' ;

    // Add a random number to force a refresh
    if (webCamURI.indexOf('?') > -1)
        document.getElementById('webcamImg').src = "<?=$webcam_uris[$cam]?>" + "&random=" + new Date().getTime() ;
    else
        document.getElementById('webcamImg').src = "<?=$webcam_uris[$cam]?>" + "?random=" + new Date().getTime() ;
}
refreshWebcam() ;
setInterval(refreshWebcam, 1000 * 30) ; // Refresh every 30 seconds
// Swipe to change to next webcam
document.addEventListener('swiped-left', function(e) {location.href='<?=$_SERVER['PHP_SELF'] . '?cam=' . $previous_cam?>' }) ;
document.addEventListener('swiped-right', function(e) {location.href='<?=$_SERVER['PHP_SELF'] . '?cam=' . $next_cam?>' }) ;

</script>
</body>
</html>
