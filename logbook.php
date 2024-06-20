<?php
/*
   Copyright 2014-2024 Eric Vyncke, Patrick Reginster

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
// TODO
// Ne pas afficher 'annuler' lorsqu'un segment est déjà entré
// 'Enregistrer le X-ème segment'
// Pour l'AML, confusion entre temps de vol / engine
//
ob_start("ob_gzhandler");

require_once "dbi.php" ;

$id = (isset($_REQUEST['id'])) ? mysqli_real_escape_string($mysqli_link, trim($_REQUEST['id'])) : '' ;
$auth = (isset($_REQUEST['auth'])) ? $_REQUEST['auth'] : '' ;

// Basic parameters sanitization
if (! is_numeric($id)) journalise($userId, 'F', "Logbook: wrong booking id: $id") ;

journalise($userId, "I", "Utilisation de l'ancienne page, referer = $_SERVER[HTTP_REFERER]") ;
header("Location: https://" .  $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/IntroCarnetVol.php?auth=$auth&id=$id") ;
?>