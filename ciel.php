<?php
/*
   Copyright 2023 Eric Vyncke

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

if (! $userIsAdmin && ! $userIsBoardMember)
    journalise($userId, "F", "Vous n'avez pas le droit de consulter cette page ou vous n'êtes pas connecté.") ; 

?><!DOCTYPE html>
<html lang="fr">
<head>
<link rel="stylesheet" type="text/css" href="mobile.css">
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<meta charset="utf-8">
<!--meta name="viewport" content="width=320"-->
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- http://www.alsacreations.com/article/lire/1490-comprendre-le-viewport-dans-le-web-mobile.html -->
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<!-- http://www.w3schools.com/bootstrap/ -->
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script><html>
	<title>Ciel</title>
</head>
<body>
    <h1>Gestion Ciel</h1>

    <h2>Import des factures Ciel dans le site</h2>
    <p>Cet import peut être effectué autant de fois que souhaité (les factures ne vont pas s'accumuler) mais il ne fonctionne que pour: <ul>
        <li>les factures envoyées par email via le compte finances@spa-aviation.be</li>
        <li>envoyées maximum 7 jours avant aujourd'hui</li>
        <li>encore dans le répertoire <i>envoyés</i> du compte finances.</li>
    </ul>
</p>
<p>Cette opération peut être effectuée depuis n'importe quel browser, pas besoin d'être sur la VM.</p>

<a href="#" class="btn btn-primary" role="button">Import des factures</a>

<h2>Import du grand livre client Ciel dans le site</h2>
<p>Cette opération permet d'importe les mouvements comptables (factures, notes de crédit, paiements, ...) de Ciel dans les "comptes membres". 
    Elle peut se faire autant de fois que souhaité (les mouvements précédents sont alors remplacés par les nouveaux sur le site).</p>
    <p>Avant de lancer cet import, il est nécesssaire d'avoir exporter le grand livre client depuis Ciel Account Premium:<ol>
        <li>menu: <i>Etats -> Etats comptables -> Grand Livre</i></li>
        <li>Choisir "grand livre clients"</li>
        <li>Cliquer sur <i>Fichier</i> et accepter les défauts, être sûr d'exporter <b>TOUS</b> les comptes</i>
        <li>Format de fichier: Texte (tab/return+line feed)</li>
        <li>Sauver le fichier quelque part sur la VM ;-) (y compris le <i>One-Drive</i></li>
    </ol>
</p>
<p>Une fois le grand livre clients exporté, il faut alors le sélectionner ci-dessous et cliquer sur "import".</p>
<form action="parse_ledger.php" method="post" enctype="multipart/form-data">
  Grand livre à importer dans le site: <input type="file" name="ledgerFile" id="ledgerFile">
    <input type="submit" value="Import" class="btn btn-primary" name="submit">
</form>
</body>
</html>
