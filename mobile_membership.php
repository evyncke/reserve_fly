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
    setcookie('membership', 'ignore', time() + (1 * 60 * 60), "/"); 
    header("Location: https://$_SERVER[HTTP_HOST]/$_REQUEST[cb]") ;
}

if (isset($_REQUEST['radioMember']) and $_REQUEST['radioMember'] == 'quit') {
    // Ugly handling as bookkeepers wanted to have a 3 choice radio control...
    setcookie('membership', 'ignore', time() + (365 * 24 * 60 * 60), "/"); 
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
<?php
if (isset($_REQUEST['invoice']) and $_REQUEST['invoice'] == 'pay' and isset($_REQUEST['radioMember'])) {
    $result = mysqli_query($mysqli_link, "SELECT * FROM $table_person WHERE jom_id=$userId")
        or journalise($userId, "F", "Cannot retrieve member information: " . mysqli_error($mysqli_link)) ;
    $row = mysqli_fetch_array($result) or journalise($userId, "F", "User not found") ;
    $userFirstName = db2web($row['first_name']) ;
    $userLastName = db2web($row['last_name']) ;
    // Leaving member via a radio check box...
    if ($_REQUEST['radioMember'] == 'quit') {
        journalise($userId, "I", "Ne veut pas renouveler sa cotisation") ;
        $smpt_headers[] = 'MIME-Version: 1.0';
        $smpt_headers[] = 'Content-type: text/html; charset=utf-8';
        mail('info@spa-aviation.be,ca@spa-aviation.be', "Membre $userLastName #$userId demissionaire", 
            "Ce membre ($userFirstName $userLastName) a indiqué vouloir quitter notre club. Veuillez lui rembourser son solde.",
            implode("\r\n", $smpt_headers)) ;
?>
<p>Vous avez fait le choix de ne plus être membre du Royal Aero Para Club de
Spa ASBL.</p>
<p>Vous allez recevoir un email confirmant votre résiliation et notre
service comptable effectuera le paiement du solde sur votre compte.</p>
<p>Encore merci pour ces années passées parmi nous, et nous vous
souhaitons plein de succès dans vos projets à venir.</p>
<p>Bien cordialement.</p>
<?php
        exit ;
    } // Quitting member
    // Let create an invoice and display a confirmation message then redirect to original page via the call back
    journalise($userId, "D", "About to generate a membership invoice") ;

    $invoice_date = date("Y-m-d") ;
    $invoice_date_due = date("Y-m-d", strtotime("+1 week")) ;
    require_once 'odoo.class.php' ;
    $odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
    $invoice_lines = array() ;
    $invoice_lines[] = array(0, 0,
        array(
            'name' => "Cotisation club pour l'année $membership_year",
            'product_id' => $non_nav_membership_product, 
            'quantity' => 1,
            'price_unit' => $non_nav_membership_price,
            'analytic_distribution' => array($membership_analytic_account => 100)
    )) ;
    $membership_price = $non_nav_membership_price ;
    // Check whether student/pilot for membership dues
    if (isset($_REQUEST['radioMember']) and $_REQUEST['radioMember'] == 'flyingMember') {
        $invoice_lines[] = array(0, 0,
            array(
                'name' => "Cotisation membre naviguant pour l'année $membership_year",
                'product_id' => $nav_membership_product, 
                'quantity' => 1,
                'price_unit' => $nav_membership_price,
                'analytic_distribution' => array($membership_analytic_account => 100)
            )) ;
            $membership_price += $nav_membership_price ;
    }
    $params =  array(array('partner_id' => intval($row['odoo_id']), // Must be of INT type else Odoo does not accept
                    'ref' => 'Cotisation club '.$membership_year,
                    'move_type' => 'out_invoice',
                    'invoice_date' => $invoice_date,
                    'invoice_date_due' => $invoice_date_due,
                    'invoice_origin' => 'Liste des membres',
                    'invoice_line_ids' => $invoice_lines)) ;
    $result = $odooClient->Create('account.move', $params) ;
    journalise($userId, "I", "Membership invoice created for odoo#$row[odoo_id]: $membership_price &euro;, invoice_id: $result[0]") ;
    mysqli_query($mysqli_link, "INSERT INTO $table_membership_fees(bkf_user, bkf_year, bkf_amount, bkf_invoice_id, bkf_invoice_date)
        VALUES($userId, '$membership_year', $membership_price, $result[0], '$invoice_date')")
        or journalise($userId, "F", "Cannot insert into $table_membership_fees: " . mysqli_error($mysqli_link)) ;
    // Display continue to the callback
?>
<p>Merci pour votre inscription pour <?=$membership_year?>, vous allez recevoir rapidement une facture par email.
Vous pouvez prépayer cette facture via le QR-code ci-dessous (ce code ne fonctionne peut-être pas 
suite à un souci informatique, celui de la facture fonctionnera bien par contre).</p>
<img width="200" height="200" src="qr-code.php?chs=200x200&chl=<?=urlencode("BCD\r\n001\r\n1\r\nSCT\r\n$bic\r\n$bank_account_name\r\n$iban\r\nEUR$membership_price\r\n\r\n\r\nCotisation $membership_year $userLastName\r\n")?>">
<p>Le club vous remercie pour votre fidélité.</p>
<a href="<?=$_REQUEST['cb']?>"><button type="button" class="btn btn-primary">Continuer vers le site</button></a>
<?php
    exit ;
}
// Prepare the radio button state...
$fullMembershipState = '' ;
$membershipState = '' ;
if (!$userIsInstructor and ($userIsStudent or $userIsPilot)) 
    $fullMembershipState = ' checked' ;
else
    $membershipState = ' checked' ;
?>
<p>Il est temps de renouveler votre cotisation au sein de notre club, sinon à partir du 1 janvier <?=$membership_year?>, il vous sera impossible de voler
avec un de nos avions. Veuillez choisir une des deux cotisations possibles ci-dessous:</p>
<form action="<?=$_SERVER['PHP_SELF']?>">
<!-- Ugly handling as bookkeepers wanted to have a 3 choice radio control... rather than a quit button -->
<div class="form-check">
  <input class="form-check-input" type="radio" name="radioMember" value="groundMember" id="radioMemberId"<?=$membershipState?>>
  <label class="form-check-label" for="radioMemberId">
    Membre non-naviguant et instructeurs (<?=$non_nav_membership_price?> €)
  </label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="radioMember" value="flyingMember" id="radioFullMemberId"<?=$fullMembershipState?>>
  <label class="form-check-label" for="radioFullMemberId">
    Membre naviguant (élèves et pilotes) (<?=$non_nav_membership_price?> € + <?=$nav_membership_price?> €)
  </label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="radioMember" value="quit" id="radioNoMemberId">
  <label class="form-check-label" for="radioNoMemberId">
    Je ne désire plus être membre
  </label>
</div>
<input type="hidden" name="cb" value="<?=$_REQUEST['cb']?>">
<button type="submit" class="btn btn-primary" name="invoice" value="pay">Confirmer votre choix</button>
<button type="submit" class="btn btn-secondary" name="invoice" value="delay">Ignorer pendant une heure</button>
</form>
</div><!-- container-fluid-->
</body>
</html>