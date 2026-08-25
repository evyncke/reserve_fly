<?php
/*
   Copyright 2025 Eric Vyncke

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
require_once 'Parsedown.php' ;
if (isset($_REQUEST['topic']) and $_REQUEST['topic'] != '') {
    $topic = $_REQUEST['topic'] ;
    // Disallow directory traversal and only allow safe characters
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $topic) or // only safe chars
            strpos($topic, '..') !== false or            // no directory traversal
            strpos($topic, '/') !== false)                // no slashes
        journalise($userId, "F", "Invalid help topic: " . htmlspecialchars($topic, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ;
} else 
    $topic = 'main' ;

if ($userId != 62) journalise($userId, "I", "Help topic: " . htmlspecialchars($topic, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . 
    ", referer: " . htmlspecialchars($referer_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ;
// TODO use referer to get to specific help page if the relevant help/*.md exists and no topic is specified
?>
<div class="container">
<div class="row">
<?php
$Parsedown = new Parsedown();
// $Parsedown->setSafeMode(true);
$text = file_get_contents("help/$topic.md") 
    or journalise($userId, "F", "Cannot read help file help/" . htmlspecialchars($topic, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ".md") ;
echo $Parsedown->text($text) ;
?>
</div><!-- row -->
</div><!-- container -->
</body>
</html>