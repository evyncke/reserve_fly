<?php
/*
   Copyright 2026 Eric Vyncke

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
require_once 'dbi.php' ;

MustBeLoggedIn() ;

if (!($userIsAdmin or $userIsBoardMember))
	journalise($userId, "F", "This admin page is reserved to administrators") ;

// Capture the output of phpinfo() to embed it in the page layout
ob_start() ;
phpinfo() ;
$phpinfo = ob_get_clean() ;

// Keep only the body content, and scope the phpinfo() CSS to its container
$phpinfo_style = '' ;
if (preg_match('#<style[^>]*>(.*?)</style>#s', $phpinfo, $matches)) {
	$phpinfo_style = preg_replace('#(^|\})\s*([^{}]+)\{#', '$1 #phpinfo $2{', $matches[1]) ;
	$phpinfo_style = preg_replace('#\#phpinfo\s+(body|html)\s*\{[^}]*\}#', '', $phpinfo_style) ;
}
$phpinfo_body = $phpinfo ;
if (preg_match('#<body[^>]*>(.*)</body>#s', $phpinfo, $matches))
	$phpinfo_body = $matches[1] ;

require_once 'mobile_header5.php' ;
?>
<style>
<?= $phpinfo_style ?>
#phpinfo { overflow-x: auto; }
#phpinfo table { max-width: 100%; }
/* phpinfo() hard-codes light backgrounds: keep the text dark on them, and use dark ones in the dark theme */
#phpinfo { color: #222; }
[data-bs-theme="dark"] #phpinfo { color: var(--bs-body-color); }
[data-bs-theme="dark"] #phpinfo .h,
[data-bs-theme="dark"] #phpinfo .h td,
[data-bs-theme="dark"] #phpinfo th { background-color: #2c3e66; color: #e6e9f2; }
[data-bs-theme="dark"] #phpinfo .e { background-color: #343a46; color: #e6e9f2; }
[data-bs-theme="dark"] #phpinfo .v,
[data-bs-theme="dark"] #phpinfo .vr { background-color: #22262e; color: #e6e9f2; }
[data-bs-theme="dark"] #phpinfo td,
[data-bs-theme="dark"] #phpinfo th { border-color: #5a6272; }
[data-bs-theme="dark"] #phpinfo a:link,
[data-bs-theme="dark"] #phpinfo a:visited { color: #8ab4ff; }
</style>
<div class="container-fluid">
<h2>PHP info</h2>
<div id="phpinfo">
<?= $phpinfo_body ?>
</div><!-- phpinfo -->
</div><!-- container -->
<?php
journalise($userId, "I", "Page mobile_admin_phpinfo.php (PHP info) displayed") ;
?>
</body>
</html>
