<?php
/*
   Copyright 2013-2023 Eric Vyncke

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
require_once 'mobile_header5.php' ;

$station = (isset($_REQUEST['station']) and $_REQUEST['station'] != '') ? trim(strtoupper($_REQUEST['station'])) : $default_metar_station ;
?> 
<div class="container-fluid">

<h2><?=$station?> METAR</h2>

<div class="row">
	<div id="metarMessage" class="col-12 bg-muted">... fetching data over the Internet ...</div> 
</div> <!-- row -->

<div class="row d-sm-none d-md-block">
	<footer class="blockquote-footer">Source <cite title="Source du METAR" id="sourceId"></cite></footer>
</div> <!-- row -->

<?php if ($userId > 0) {
?>
<div class="row">
<form class="form-inline" action="<?=$_SERVER['PHP_SELF']?>" method="GET">
	<div class="form-group">
		<label class="control-label col-xs-4 col-md-4" for="stationMETARInput">Station METAR:</label>
		<div class="col-xs-3 col-md-4">
			<input type="text" size="5" maxlength="4" class="form-control" id="stationMETARInput" placeholder="<?=$station?>" name="station">
		</div>
	</div>

	<div class="form-group">
		<div class="col-xs-3 col-md-4">
	      <input type="submit" class="btn btn-primary" value="Changer">
   		</div>
	</div><!-- formgroup-->
</form>
</div> <!-- row -->
<?php
} // $userId > 0
?>
<script>
	displayMETAR('<?=$station?>') ;
</script>

</div> <!-- container-->
</body>
</html>