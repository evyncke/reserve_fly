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
?>

<h3>Aide</h3>

<div class="row">
<?php
$Parsedown = new Parsedown();
echo $Parsedown->text(file_get_contents('mobile_help.md')) ;
?>

</div><!-- row -->