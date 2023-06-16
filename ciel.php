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

MustBeLoggedIn() ;

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
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script><html>
	<title>Ciel</title>
</head>
<body>
    <h1>Gestion Ciel</h1>

    <p class="bg-info">Cette page est réservée aux administrateurs, aucun autre membre n'y a accès.</p>

<?php
if ($_REQUEST['save_ciel'] == 'true') {
    foreach($_REQUEST as $name=>$code) {
        if ($code == '') continue ;
        if (str_starts_with($name, 'ciel')) {
            $id = substr($name, strlen('ciel')) ;
            if (str_starts_with($code, '400')) {
                $code_400 = $code ;
                $code = substr($code, strlen('400')) ;
            } else {
                $code_400 = '400' . $code ;
            }
            mysqli_query($mysqli_link, "UPDATE $table_person SET ciel_code='$code', ciel_code400='$code_400' where jom_id=$id")
                or journalise($userId, "F", "Cannot assigne Ciel code" . mysqli_error($mysqli_link)) ;
            print("Ciel code $code/$code_400 assigné à l'utilisateur $id.<br>\n") ;
            journalise($userId, "I", "Ciel codes $code/$code_400 assignés à l'utilisateur $id.<br>\n") ;
        }
    }   
}
?>
    <h2>Import des factures Ciel dans le site</h2>
    <p>Cet import peut être effectué autant de fois que souhaité (les factures ne vont pas s'accumuler) mais il ne fonctionne que pour: <ul>
        <li>les factures envoyées par email via le compte finances@spa-aviation.be</li>
        <li>envoyées maximum 7 jours avant aujourd'hui</li>
        <li>encore dans le répertoire <i>envoyés</i> du compte finances.</li>
    </ul>
</p>
<p>Cette opération peut être effectuée depuis n'importe quel browser, pas besoin d'être sur la VM.</p>

<a href="parse_invoices_imap.php" class="btn btn-primary" role="button">Import des factures</a>

<h2>Import du grand livre clients Ciel dans le site</h2>
<p>Cette opération permet d'importer les mouvements comptables (factures, notes de crédit, paiements, ...) et les soldes de Ciel dans les "comptes membres". 
    Elle peut se faire autant de fois que souhaité (les mouvements précédents sont alors remplacés par les nouveaux sur le site).</p>
    <p>Avant de lancer cet import, il est nécesssaire d'avoir exporté le grand livre client depuis <i>Ciel Account Premium</i>:<ol>
        <li>menu: <i>Etats -> Etats comptables -> Grand Livre</i></li>
        <li>Choisir "grand livre clients"</li>
        <li>Cliquer sur <i>Fichier</i> et accepter les défauts, être sûr d'exporter <b>TOUS</b> les comptes</i>
        <li>Format de fichier: <i>Texte (tab/return+line feed)</i></li>
        <li>Sauver le fichier quelque part sur la VM ;-) (y compris le <i>One-Drive</i>)</li>
    </ol>
</p>
<p>Une fois le grand livre clients exporté, il faut alors le sélectionner ci-dessous et cliquer sur le bouton <kbd>Import</kbd>.</p>
<form action="parse_ledger.php" method="post" enctype="multipart/form-data">
  Grand livre à importer dans le site: <input type="file" name="ledgerFile" id="ledgerFile">
    <input type="submit" value="Import" class="btn btn-primary" name="submit">
</form>

<h2>Membres inexistants dans Ciel</h2>
<p>Liste des membres du club dont le numéro de compte Ciel est inconnu.</p>
<form action="<?=$_SERVER['PHP_SELF']?>" id="ciel_form">
    <input type="hidden" name="save_ciel" value="true">
<table class="table table-striped table-hover table-responsive">
    <thead>
        <tr><th>Nom utilisateur</th><th>Compte Ciel</th><th>Nom</th><th>Prénom</th><th>email</th><th>Date création</th></tr>
    </thead>
    <tbody>
<?php
$result = mysqli_query($mysqli_link, "SELECT * FROM $table_person JOIN jom_users AS j ON jom_id = j.id
    WHERE ciel_code400 IS NULL
    ORDER BY j.registerDate DESC")
    or journalise($userId, "F", "Cannot read members: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
    $name = db2web($row['name']) ;
    $first_name = db2web($row['first_name']) ;
    $last_name = db2web($row['last_name']) ;
    print("<tr><td>$row[username]</td><td>
        <input type=\"text\" name=\"ciel$row[jom_id]\"> 
        <span class=\"glyphicon glyphicon glyphicon-floppy-saved\" style=\"color: blue;\" title=\"Enregistrer le code Ciel\" onClick=\"document.getElementById('ciel_form').submit();\"></span>
        </td><td>$last_name</td><td>$first_name</td><td>$row[email]</td><td>$row[registerDate]</td></tr>\n") ;
}
?>
</tbody>
</table>
</form>
</body>
</html>