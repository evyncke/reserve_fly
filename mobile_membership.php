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
if (isset($_REQUEST['invoice']) and $_REQUEST['invoice'] == 'pay') {
    // Let create an invoice and display a confirmation message then redirect to original page via the call back
    journalise($userId, "D", "About to generate a membership invoice") ;
    $result = mysqli_query($mysqli_link, "SELECT * FROM $table_person WHERE jom_id=$userId")
        or journalise($userId, "F", "Cannot retrieve member information: " . mysqli_error($mysqli_link)) ;
    $row = mysqli_fetch_array($result) or journalise($userId, "F", "User not found") ;
    $userLastName = db2web($row['last_name']) ;
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
Vous pouvez prépayer cette facture via le QR-code ci-dessous.</p>
<img width="200" height="200" src="qr-code.php?chs=200x200&chl=<?=urlencode("BCD\n001\n1\nSCT\n$bic\n$bank_account_name\n$iban\nEUR$membership_price\nCotisation $membership_year\n\nCotisation $membership_year $userLastName\n")?>">
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
<input type="hidden" name="cb" value="<?=$_REQUEST['cb']?>">
<button type="submit" class="btn btn-primary" name="invoice" value="pay">Confirmer votre inscription</button>
<button type="submit" class="btn btn-secondary" name="invoice" value="delay">Ignorer pendant une heure</button>
</form>
</div><!-- container-fluid-->
</body>
</html>