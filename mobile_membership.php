<?php
/*
   Copyright 2024-2024 Eric Vyncke

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

// Needs to be done before anything is sent...
if (isset($_REQUEST['invoice']) and $_REQUEST['invoice'] == 'delay') {
    setcookie('membership', 'ignore', time() + (1 * 60), "/"); 
    header("Location: https://$_SERVER[HTTP_HOST]/$_REQUEST[cb]") ;
}

require_once "dbi.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}

require_once 'mobile_header5.php' ;
?>
<div class="container-fluid">
<h2>Cotisation pour l'année <?=$membership_year?></h2>
<p>Il est temps de renouveler votre cotisation au sein de notre club, sinon à partir du 1 janvier <?=$membership_year?>, il vous sera impossible de voler
avec un de nos avions.</p>
<form action="<?=$_SERVER['PHP_SELF']?>">
<div class="form-check">
  <input class="form-check-input" type="radio" name="radioMember" id="radioMemberId">
  <label class="form-check-label" for="radioMemberId">
    Membre non-naviguant et instructeurs (70 €)
  </label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="radioFullMember" id="radioFullMemberId">
  <label class="form-check-label" for="radioFullMemberId">
    Membre naviguant (élèves et pilotes) (70 € + 200 €)
  </label>
</div>
<input type="hidden" name="cb" value="<?=$_REQUEST['cb']?>">
<button type="submit" class="btn btn-primary" name="invoice" value="yes">Confirmer</button>
<button type="submit" class="btn btn-secondary" name="invoice" value="delay">Ignorer pendant une heure</button>
</form>
</div><!-- container-fluid-->
</body>
</html>