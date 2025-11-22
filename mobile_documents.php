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
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}
require_once 'mobile_header5.php' ;
?>
<h2>Documents pour les membres</h2>
<p>En mode test, "quick and dirty" (uniquement pour les administrateurs et FIs): les documents PDF disponibles sont listés ci-dessous. Cliquez sur un lien pour télécharger le document.</p>
<ul>
<?php
$directory = $_SERVER['DOCUMENT_ROOT'] . '/www/images/pdf';
$directory = '../images/pdf'; # Need to exit the resa folder to reach the images folder
if (is_dir($directory)) {
    $files = scandir($directory);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
            $filenameWithoutExtension = pathinfo($file, PATHINFO_FILENAME);
            echo '<li><a href="/www/' . htmlspecialchars($directory . '/' . $file) . '">' . htmlspecialchars($filenameWithoutExtension) . '</a></li>';
        }
    }
} else {
    echo '<p>Le répertoire des documents est introuvable.</p>';
}
?>
</ul>
<div class="container-fluid">
</body>
</html>