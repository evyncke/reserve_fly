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
require_once 'mobile_header5.php' ;
if ($userId != 62) journalise($userId, "D", "Start of live streaming") ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}
?> 
<div class="container-fluid">
<h2 style="display: none;">Live Apron Webcam</h2>
<video id="hls-video" controls autoplay width="1280" height="720"></video>
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script>
  if (Hls.isSupported()) {
    var video = document.getElementById('hls-video');
    var hls = new Hls();
    hls.loadSource('https://nav.vyncke.org/rapcs/stream.m3u8?nhls=<?=$userId?>');
    hls.attachMedia(video);
  } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
    // Fallback pour Safari
    video.src = 'https://nav.vyncke.org/rapcs/stream.m3u8?hls=<?=$userId?>';
  } else {
    alert("Ce navigateur ne supporte pas HLS");
  }
</script>
</div> <!-- container-->
</body>
</html>