<?php
/*
   Copyright 2014-2019 Eric Vyncke

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
require_once 'flight_header.php' ;
require_once 'Parsedown.php' ;
?>

<div class="page-header hidden-xs">
<h3>Aide</h3>
</div><!-- page header -->

<div class="row">
<?php
$Parsedown = new Parsedown();
echo $Parsedown->text(file_get_contents('flight_help.md')) ;
?>

</div><!-- row -->

<?php
require_once 'flight_trailer.php' ;
?>