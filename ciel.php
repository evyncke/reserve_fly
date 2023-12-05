<?php
/*
   Copyright 2023 Eric Vyncke & Patrick Reginster

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

if (! $userIsAdmin && ! $userIsBoardMember && $userId != 306) // Bernard Penders
    journalise($userId, "F", "Vous n'avez pas le droit de consulter cette page ou vous n'êtes pas connecté.") ; 

?><!DOCTYPE html>
<html lang="fr">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<meta charset="utf-8">
<!--meta name="viewport" content="width=320"-->
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- http://www.alsacreations.com/article/lire/1490-comprendre-le-viewport-dans-le-web-mobile.html -->
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<!-- Using latest bootstrap 5 -->
<!-- Latest compiled and minified CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Use bootstrap icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<!-- Latest compiled JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<title>Ciel</title>
<!-- Matomo -->
<script type="text/javascript">
  var _paq = window._paq = window._paq || [];
  _paq.push(['setUserId', '<?=$userName?>']);
  _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
  _paq.push(["setDomains", ["*.spa-aviation.be","*.ebsp.be","*.m.ebsp.be","*.m.spa-aviation.be","*.resa.spa-aviation.be"]]);
  _paq.push(['enableHeartBeatTimer']);
  _paq.push(['setCustomVariable', 1, "userID", <?=$userId?>, "visit"]);
  _paq.push(["setCookieDomain", "*.spa-aviation.be"]);
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="//analytics.vyncke.org/";
    _paq.push(['setTrackerUrl', u+'matomo.php']);
    _paq.push(['setSiteId', '5']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Code -->
<script>
function toggleCielRows() {
    rows = document.getElementsByClassName('ciel-row') ;
    toggle = document.getElementById('hideUnknown') ;
    for (var i = 0; i < rows.length; i++) {
        if (rows[i].cells[1].getElementsByTagName('input')[0].value != '') {
            // un/hide the row
            rows[i].style.display = toggle.checked ? 'none' : '' ;
        }    
    }
}
</script>
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
                or journalise($userId, "F", "Cannot assign Ciel code" . mysqli_error($mysqli_link)) ;
            if (mysqli_affected_rows($mysqli_link) > 0) {    
                print("Ciel code $code/$code_400 assigné à l'utilisateur $id.<br>\n") ;
                journalise($userId, "I", "Ciel codes $code/$code_400 assignés à l'utilisateur $id.") ;
            }   
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
        <li>Cliquer sur <i>Fichier</i> et être sûr d'exporter <b>TOUS</b> les comptes, avec les bonnes dates, écritures en brouillon <b>et</b> en simulation, dans le tab <i>+ de critères</i>: aucune option sélectionnée, et <i>les deux</i> pour les écritures</i>
        <li>Format de fichier: <i>Texte (tab/return+line feed)</i></li>
        <li>Sauver le fichier quelque part sur la VM ;-) (y compris le <i>One-Drive</i>)</li>
    </ol>
</p>
<p>Une fois le grand livre clients exporté, il faut alors le sélectionner ci-dessous et cliquer sur le bouton <kbd>Import</kbd>.</p>
<form action="parse_ledger.php" method="post" enctype="multipart/form-data">
  Grand livre à importer dans le site: <input type="file" name="ledgerFile" id="ledgerFile">
    <input type="submit" value="Import" class="btn btn-primary" name="submit">
</form>

<h2>Code Ciel des membres</h2>
<p>Liste des membres du club avec leur numéro de compte Ciel.</p>
<div class="form-check">
    <input class="form-check-input" type="checkbox" id="hideUnknown" checked onChange="toggleCielRows();"> 
    <label for="hideUnknown" class="form-check-label"> Cacher les comptes Ciel connus.</label>
</div><!-- form-check -->
<form action="<?=$_SERVER['PHP_SELF']?>" id="ciel_form">
    <input type="hidden" name="save_ciel" value="true">
<table class="table table-hover table-responsive">
    <thead>
        <tr><th>Nom utilisateur</th><th>Compte Ciel</th><th>Nom</th><th>Prénom</th><th>email</th><th>Date création</th></tr>
    </thead>
    <tbody class="table-group-divider">
<?php
$result = mysqli_query($mysqli_link, "SELECT * FROM $table_person JOIN jom_users AS j ON jom_id = j.id
    WHERE ciel_code400 IS NULL OR TRUE
    ORDER BY j.registerDate DESC")
    or journalise($userId, "F", "Cannot read members: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
    $name = db2web($row['name']) ;
    $first_name = db2web($row['first_name']) ;
    $last_name = db2web($row['last_name']) ;
    $hidden_style = ($row['ciel_code400'] != '') ? ' style="display: none;"' : '' ;
    print("<tr class=\"ciel-row\"$hidden_style><td>$row[username]</td><td>
        <input type=\"text\" name=\"ciel$row[jom_id]\" value=\"$row[ciel_code400]\"> 
        <i class=\"bi-check-circle-fill\" style=\"color: blue;\" title=\"Enregistrer le code Ciel\" onClick=\"document.getElementById('ciel_form').submit();\"></i>
        </td><td>$last_name</td><td>$first_name</td><td>$row[email]</td><td>$row[registerDate]</td></tr>\n") ;
}
?>
</tbody>
</table>
</form>

<h2>Sociétés des membres</h2>
<p>Liste des membres du club ayant une entreprise à laquelle facturer.</p>

<form action="<?=$_SERVER['PHP_SELF']?>" id="company_form">
    <input type="hidden" name="save_company" value="true">
<table class="table table-hover table-responsive">
    <thead>
        <tr><th>Nom, prénom <i>(Code ciel)</i></th><th>Entreprise</th><th>Code BCE</th><th>Adresse</th><th>Code postal</th><th>Ville</th><th>Pays</th></tr>
    </thead>
    <tbody class="table-group-divider">
<?php
$result = mysqli_query($mysqli_link, "SELECT * 
    FROM $table_person
        LEFT JOIN $table_company_member ON jom_id = cm_member
        JOIN $table_company ON cm_company = c_id
    ORDER BY last_name, first_name DESC")
    or journalise($userId, "F", "Cannot read companies: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
    $name = db2web($row['c_name']) ;
    $address = db2web($row['c_address']) ;
    $city = db2web($row['c_city']) ;
    $country = db2web($row['c_country']) ;
    $first_name = db2web($row['first_name']) ;
    $last_name = db2web($row['last_name']) ;
    print("<tr class=\"ciel-row\">
        <td><b>$last_name</b>, $first_name <i>($row[ciel_code400])</i></td>
        <td>$name</td><td>$row[c_bce]</td><td>$address</td><td>$row[c_zipcode]</td><td>$city</td><td>$country</td>
        </tr>\n") ;
}
?>
</tbody>
</table>
</form>

<h2 class="text-danger">!!! TEST !!! Générations des factures</h2>
<p>Cette opération va générer les factures (sur base du folio, 
c-à-d sur base des carnets de routes des avions) et va générer un fichier <i>ximport.txt</i> qu'il faudra
alors importer dans <i>Ciel Premium Account</i>.</p>
<p class="bg-danger">Ceci est en mode test réservé à Eric Vyncke et Patrick Reginster.</p>

<div class=""></div>
<form action="ximport.php">
<div class="mb-3">
    <label for="nextInvoice" class="form-label">Préfixe des factures: (exemple V<?=date('ym', time() - 7 * 24 * 60 * 60)?>)</label>
    <input type="text" class="form-control" id="prefixInvoice" name="prefixInvoice" value="V<?=date('ym', time() - 7 * 24 * 60 * 60)?>">
</div>
<div class="mb-3">
<p>Factures générées dans le folder <a href="https://www.spa-aviation.be/resa/data/PDFInvoices/">https://www.spa-aviation.be/resa/data/PDFInvoices/</a> (en mode test)</p>
<p>Fichier d'import ciel : <a href="https://www.spa-aviation.be/resa/data/ximport.txt">ximport.txt</a></p>
</div>
<button type="submit" class="btn btn-primary">Générer les factures</button>
</form>

<h2 class="text-danger">!!! TEST !!! Envoi des factures par email</h2>
<p>Cette opération va envoyer des emails aux membres ayant une facture générée par l'opération ci-dessus. Il est possible (voire parfois nécessaire)
    d'effectuer plusieurs fois cette opération.
</p>
<p class="bg-danger">Ceci est en mode test réservé à Eric Vyncke et Patrick Reginster.</p>

<div class=""></div>
<form action="email_invoices.php">
<div class="mb-3">
    <label for="invoiceDate" class="form-label">Date des factures:</label>
    <input type="date" class="form-control" id="dateInvoice" name="dateInvoice" value="<?=date('Y-m-d')?>">
</div>
<div class="mb-3">
<button type="submit" class="btn btn-primary">Envoyer les emails</button> (Attention cette opération prend beaucoup de temps, plusieurs minutes)
</form>

<hr>
<p class="small">Réalisation: Eric Vyncke & Patrick Reginster, 2023-2023, pour RAPCS, Royal Aéro Para Club de Spa, ASBL</p>
</body>
</html>
