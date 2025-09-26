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
// SMTP email debuging & optimization
$smtp_info['debug'] = True;
$smtp_info['debug'] = False;
$smtp_info['persist'] = True;
$mime_preferences = array(
	"input-charset" => "UTF-8",
	"output-charset" => "UTF-8",
	"scheme" => "Q") ;
$managerName = "Finances du RAPCS" ;
$smtp_from = "finances@spa-aviation.be" ;

// Direct use of OVH servers (default setting is to use Eric Vyncke's own server)
// This may cause spam rejection due to poor OVH email reputation
$smtp_host = 'ssl0.ovh.net' ;
$smtp_port = 587 ;
$smtp_user = $finances_smtp_user ;
$smtp_password = $finances_smtp_password ;

MustBeLoggedIn() ;

if (! $userIsAdmin && ! $userIsBoardMember)
    journalise($userId, "F", "Vous n'avez pas le droit de consulter cette page ou vous n'êtes pas connecté.") ; 

if (!(isset($_REQUEST['dateInvoice'])))
    journalise($userId, "F", "Missing parameter: dateInvoice = $dateInvoice") ;
$dateInvoice = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['dateInvoice'])) ;
?><!DOCTYPE html>
<html lang="fr">
<head>
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
	<title>Envoi par email des factures</title>
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
    _paq.push(['setSiteId', '8']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Code -->
</head>
<body>
    <h1>Envoi des factures du <?=$dateInvoice?> par email</h1>
    <p class="bg-danger">Ceci est en mode test.</p>
<?php
$result = mysqli_query($mysqli_link, "SELECT *, DATE(bki_date) AS bki_date
    FROM $table_bk_invoices
        LEFT JOIN $table_person p ON p.email = bki_email
        WHERE DATE(bki_date) = '$dateInvoice'
        ORDER BY bki_id ASC")
        or journalise($userID, "F", "Cannot read $table_bk_invoices: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
    if ($row['bki_email_sent'] != '' and $row['jom_id'] != 62) {
        print("<small>Already sent on $row[bki_email_sent], skipping invoice $row[bki_id] for '$row[email]' (" . db2web($row['name']) . ")</small><br/>") ;
        continue ;
    }
    if ($row['email'] == '') {
        print("<span style=\"color: red;\">!!! La facture $row[bki_id] pour '$row[bki_email]' n'a pas de correspondance dans $table_person!!!</span><br/>\n") ;
        continue ;
    }
//   if (!in_array($row['jom_id'], array(62, 66, 92, 306, 348))) {
//    if (!in_array($row['jom_id'], array(62))) {
//        print("<small>Skipping invoice $row[bki_id] for '$row[email]' (" . db2web($row['name']) . ")</small><br/>") ;
//        continue ;
//    }
  print("Processing invoice $row[bki_id] for $row[email] (" . db2web($row['name']) . ") $row[bki_amount] &euro;<br/>") ;

  $row['first_name'] = db2web($row['first_name']) ;
  $row['full_name'] = db2web($row['full_name']) ;
  $email_subject = "Votre facture $row[bki_id]" ;
  $email_message = "<p>Bonjour $row[first_name],</p><p>Votre facture $row[bki_id] du $row[bki_date] est disponible sur Internet
        via ce <a href=\"https://spa-aviation.be/resa/$row[bki_file_name]\">lien</a>. Afin de ne pas gaspiller du papier, vous
        ne recevrez pas de facture sur papier par la poste.</p>" ;
  if ($row['bki_amount'] > 0) {
    $epcURI = "BCD\n001\n1\nSCT\n$bic\n$bank_account_name\n$iban\nEUR$row[bki_amount]\n$row[bki_id] $row[ciel_code400] $row[last_name]\n$row[bki_id] $row[ciel_code400] $row[last_name]" ;
    $qr = "https://chart.googleapis.com/chart?cht=qr&chs=150x150&chld=M&chl=" . urlencode($epcURI) ;
    $email_message .= "<p>Le montant de cette facture est de $row[bki_amount] &euro; payable via notamment par votre app e-banking et le QR-code ci-dessous:<br/>
        <img width=\"150\" height=\"150\" src=\"$qr\">
        </p>" ;
    $email_message .= "<p>Si votre compte est suffisamment approvisionné, cette facture sera appliquée contre vos avances. 
      Merci de consulter votre <a href=\"https://www.spa-aviation.be/resa/myfolio.php\">folio du mois</a> 
      sur le site pour alimenter votre compte en suffisance. </p>\n" ;
  }
  $email_message .= "<center><p>Meilleures salutations,<br/>Royal Aéro Para Club de Spa asbl</p></center>\n" ;
  $email_message .= "<p><em>Nos factures sont payables au grand comptant, sauf accord particulier. La réception de la facture sauf contestation notifiée par lettre recommandée 
    endéans les 8 jours entraine de plein droit réception et agréation complète des services et fournitures y mentionnés. 
    Aucune réclamation ne sera admise passé ce délai. Les prix des fournitures sont ceux en vigueur au moment de la facturation. 
    <b>*** Tout retard de paiement, sans qu'il soit nécessaire d'une mise en demeure, entraîne automatiquement l'interdiction de vol sur les avions du club ***</b> 
    une indemnité forfaitaire de retard de 10% du montant de la facture, avec un minimum de 37 € à titre de dommages et intérêts, 
    et un intérêt de retard de 12% l'an à dater de l'envoi de la facture, conformément à l'article 1152 du Code Civil.</em></p>
    <p><em>De convention expresse, quels que soient les divers modes de paiement, acceptation de règlement ou lieu de livraison, 
    seront seuls compétents les Tribunaux de Verviers. Il est stipulé que les conditions générales prévalent sur celles de nos clients.
    </em></p>" ;
  $email_message .= "<hr><p style=\"color: blue; font-size: smaller;\">Un nouveau système automatique est utilisé pour cette facture, ne pas hésiter à contacter
    <a href=\”mailto:finances@spa-aviation.be\">finances@spa-aviation.be</a> si une incohérence est détectée.</p>" ;
  $email_header = "From: $managerName <$smtp_from>\r\n" ;
  $email_header .= "To: $row[full_name] <$row[email]>\r\n" ;
  $email_recipients = $row['email'] ;
  $email_header .= "X-Comment: invoice is $row[bki_id]\r\n" ;
  $email_header .= "References: <invoice-$row[bki_id]@$smtp_localhost>\r\n" ;
  $email_header .= "In-Reply-To: <invoice-$row[bki_id]@$smtp_localhost>\r\n" ;
  $email_header .= "Thread-Topic: Facture RAPCS #$row[bki_id]\r\n" ; 
  $delimiteur = "Part=".md5(uniqid(rand())) ;
  $email_header .= "Content-Type: multipart/mixed; boundary=\"$delimiteur\"\r\n" ;
  $email_message = "Ce texte est envoye en format MIME et HTML donc peut-etre pas lisible sur cette plateforme.\r\n" .
      "--$delimiteur\r\n" .
      "Content-Type: text/html; charset=UTF-8\r\n" .
      "\r\n" . 
      "<html><body>$email_message</body></html>" .
      "\r\n\r\n" .
      "--$delimiteur--\r\n" ;
  $email_header .= "Return-Path: <bounce@spa-aviation.be>\r\n" ;  // Will set the MAIL FROM enveloppe by the Pear Mail send()
  smtp_mail($email_recipients, $email_subject, $email_message, $email_header) ;
  mysqli_query($mysqli_link, "UPDATE $table_bk_invoices SET bki_email_sent = CURRENT_TIME() WHERE bki_id = '$row[bki_id]'")
    or journalise($userId, "W", "Cannot update invoice sent date in $table_bk_invoices: " . mysqli_error($mysqli_link)) ;
  ob_flush() ; // Keep servers/browers happy
}
?>
<hr>
<p class="small">Réalisation: Eric Vyncke, 2023-2023, pour RAPCS, Royal Aéro Para Club de Spa, ASBL</p>
</body>
</html>