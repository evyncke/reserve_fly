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
$header_postamble = '<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>' ;
require_once 'mobile_header5.php' ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}
if (isset($_REQUEST['webcam']))
  $webcam = '-' . $_REQUEST['webcam'] ;
else // Use the default
  $webcam = '-apron' ;
if ($userId != 62) journalise($userId, "D", "Start of live streaming #$webcam") ;
?> 
<div class="container-fluid">
<h2 style="display: none;">Live Webcam<?=$webcam?></h2>
<?php
if ($webcam == '-both') {
?>
<div class="row">
  <div class="col-lg-6 col-sm-12">
    <video id="hls-video-1" controls autoplay width="100%"></video>
  </div>
  <div class="col-lg-6 col-sm-12">
    <video id="hls-video-2" controls autoplay width="100%"></video>
  </div>
</div>  
<script>
  if (Hls.isSupported()) {
    var video1 = document.getElementById('hls-video-1');
    var video2 = document.getElementById('hls-video-2');
    var hls1 = new Hls();
    var hls2 = new Hls();
    hls1.loadSource('https://nav.vyncke.org/rapcs/stream-apron.m3u8?nhls=<?=$userId?>');
    hls2.loadSource('https://nav.vyncke.org/rapcs/stream-hangars.m3u8?nhls=<?=$userId?>');
    hls1.attachMedia(video1);
    hls2.attachMedia(video2);
  } else if (video1.canPlayType('application/vnd.apple.mpegurl')) {
    // Fallback pour Safari
    video1.src = 'https://nav.vyncke.org/rapcs/stream-apron.m3u8?hls=<?=$userId?>';
    video2.src = 'https://nav.vyncke.org/rapcs/stream-hangars.m3u8?hls=<?=$userId?>';
  } else {
    alert("Ce navigateur ne supporte pas HLS");
  }
</script>
<?php
} elseif ($webcam == '-sowaer') {
?>
<iframe src="https://g0.ipcamlive.com/player/player.php?alias=camebspairside&autoplay=1&mute=1&disableautofullscreen=1&disablezoombutton=p;disableframecapture=1&disabletimelapseplayer=1&disablestorageplayer=1&disabledownloadbutton=1&disableplaybackspeedbutton=1&disablenavigation=1&disableuserpause=1" 
  width="800px" height="450px" frameborder="0" allowfullscreen="allowfullscreen"></iframe>
  <p>Courtesy of SOWAER and Spa/EBSP airfield (hosted by ipcamlive.com.)</p>
<?php
} else {
?>
<video id="hls-video" controls autoplay width="1280" height="720"></video>
<script>
  if (Hls.isSupported()) {
    var video = document.getElementById('hls-video');
    var hls = new Hls();
    hls.loadSource('https://nav.vyncke.org/rapcs/stream<?=$webcam?>.m3u8?nhls=<?=$userId?>');
    hls.attachMedia(video);
  } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
    // Fallback pour Safari
    video.src = 'https://nav.vyncke.org/rapcs/stream<?=$webcam?>.m3u8?hls=<?=$userId?>';
  } else {
    alert("Ce navigateur ne supporte pas HLS");
  }
</script>
<?php
}
?>
</div> <!-- container-->
</body>
</html>