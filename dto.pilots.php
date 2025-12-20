<?php
/*
   Copyright 2023-2025 Eric Vyncke

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
if (! ($userIsBoardMember or $userIsInstructor))
    journalise($userId, "F", "Vous devez être administrateur ou instructeur pour voir cette page.") ;
require_once 'mobile_header5.php' ;
require_once 'dto.class.php' ;

// Let's get some data from Odoo
require_once 'odoo.class.php' ;
$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;

// Find all Odoo IDs
$sql = "SELECT odoo_id
	FROM $table_person
    JOIN $table_user_usergroup_map ON jom_id = user_id 
    WHERE group_id = $joomla_pilot_group
    GROUP BY jom_id" ;
$result = mysqli_query($mysqli_link, $sql)
	or journalise($userId, "F", "Cannot retrieve all Odoo ids: " . mysqli_error($mysqli_link)) ;
$ids = array() ;
while ($row = mysqli_fetch_array($result)) {
	$ids[] = intval($row['odoo_id']) ;
}
mysqli_free_result($result) ;
$members = $odooClient->Read('res.partner', 
	[$ids], 
	['fields' => ['email', 'total_due']]) ;
$odoo_customers = array() ;
foreach($members as $member) {
	$email =  strtolower($member['email']) ;
	$odoo_customers[$email] = $member ; // Let's build a dict indexed by the email addresses
}

$sql_now = date('Y-m-d') ;
?>

<h2>Liste des pilotes</h2>
<div class="row">
<div class="col-sm-12 col-md-12 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<th>Pilotes</th><th>Premier&nbsp;/&nbsp;dernier vols</th><th>Médical&nbsp;/&nbsp;ELP&nbsp;/&nbsp;SEP</th><th class="d-none d-xl-table-cell">Email</th><th class="d-none d-xl-table-cell">Mobile</th></tr>
</thead>
<tbody class="table-group-divider">

<?php
    $dto = new DTO() ;
    $pilots = $dto->Pilots( ) ;
    foreach($pilots as $pilot) {
        if (isset($odoo_customers[strtolower($pilot->email)])) {
            $odoo_customer = $odoo_customers[strtolower($pilot->email)] ;
            $blocked = ($pilot->blocked) ? ' <i class="bi bi-sign-stop-fill text-danger" title="This member is blocked (' . $odoo_customer['total_due'] . '€ due)"></i>' : '' ;
            $bank_filled = ($odoo_customer['total_due'] < 0) ? ' <i class="bi bi-piggy-bank-fill text-success" title="This member has paid ' .
                (-$odoo_customer['total_due']) . '€ for future flights"></i>' : '' ;
        } else {
            $blocked = '<span class="text-danger">Ce membre n\'est pas lié à un compte dans la comptabilité</span>' ;
            $bank_filled = '' ;
        }
        if ($pilot->membershipPaid)
            $membership_filled = '<i class="bi bi-person-check-fill text-success" title="Membership paid"><i>' ;
        else
            $membership_filled = '<i class="bi bi-person-fill-exclamation text-danger" title="Membership NOT paid"><i>' ;
        if ($pilot->mobilePhone == '')
            $mobile_phone = "<i class=\"bi bi-telephone-fill text-danger\" title=\"Pas de téléphone mobile spécifié\">" ;
        else
            $mobile_phone = "<a href=\"tel:$pilot->mobilePhone\"><i class=\"bi bi-telephone-fill\" title=\"Call on mobile\"></i></a>" ;
        if ($pilot->daysSinceLastFlight <= 60)
            $daysColor = 'text-bg-info' ;
        else if ($pilot->daysSinceLastFlight <= 120)
            $daysColor = 'text-bg-warning' ;
        else
            $daysColor = 'text-bg-danger' ;
        if (isset($pilot->validitiesDates[2]) and $pilot->validitiesDates[2] != '0000-00-00') {
            $medical_date = $pilot->validitiesDates[2] ;
            if ($medical_date <= $sql_now)
                $medical = "<span class=\"text-danger\">Médical expiré le $medical_date</span>
                    <span class=\"badge text-bg-danger\" title=\"Jours depuis expiration\"><i class=\"bi bi-calendar3\"></i> " . $pilot->validitiesDaysLeft[2] . "</span>";
            else
                $medical = "Médical valide jusqu'au $medical_date
                    <span class=\"badge text-bg-info\" title=\"Jours avant expiration\"><i class=\"bi bi-calendar3\"></i> " . $pilot->validitiesDaysLeft[2] . "</span>";
        } else  {
            $medical = '<span class="text-warning">Médical non-spécifié</span>' ;
        }
        if (isset($pilot->validitiesDates[4]) and $pilot->validitiesDates[4] != '0000-00-00') {
            $elp_date = $pilot->validitiesDates[4] ;
            if ($elp_date <= $sql_now)
                $elp = "<span class=\"text-danger\">ELP expiré le $elp_date</span>
                    <span class=\"badge text-bg-danger\" title=\"Jours depuis expiration\"><i class=\"bi bi-calendar3\"></i> " . $pilot->validitiesDaysLeft[4] . "</span>" ;
            else
                $elp = "ELP valide jusqu'au $elp_date
                    <span class=\"badge text-bg-info\" title=\"Jours avant expiration\"><i class=\"bi bi-calendar3\"></i> " . $pilot->validitiesDaysLeft[4] . "</span>" ;
        } else  {
            $elp = '<span class="text-warning">ELP non-spécifié</span>' ;
        }
        if (isset($pilot->validitiesDates[1]) and $pilot->validitiesDates[1] != '0000-00-00') {
            $sep_date = $pilot->validitiesDates[1] ;
            if ($sep_date <= $sql_now)
                $sep = "<span class=\"text-danger\">SEP expiré le $sep_date</span>
                    <span class=\"badge text-bg-danger\" title=\"Jours depuis expiration\"><i class=\"bi bi-calendar3\"></i> " . $pilot->validitiesDaysLeft[1] . "</span>" ;
            else
                $sep = "SEP valide jusqu'au $sep_date
                    <span class=\"badge text-bg-info\" title=\"Jours avant expiration\"><i class=\"bi bi-calendar3\"></i> " . $pilot->validitiesDaysLeft[1] . "</span>" ;
        } else  {
            $sep = '<span class="text-warning">SEP non-spécifié</span>' ;
        }
        print("<tr>
            <td>
                $pilot->lastName, $pilot->firstName
                    <a href=\"mobile_mylog.php?user=$pilot->jom_id&period=always\" title=\"Flights Log\"><i class=\"bi bi-journals\"></i></a>
                    <a href=\"mailto:$pilot->email\"><i class=\"bi bi-envelope-fill\" title=\"Send email\"></i></a>
                    $mobile_phone $membership_filled $blocked $bank_filled
            </td>
            <td>$pilot->firstFlight <a href=\"mobile_mylog.php?user=$pilot->jom_id&period=always\" class=\"badge text-bg-info\" title=\"Number of flights\"><i class=\"bi bi-airplane-fill\"></i> $pilot->countFlights</a><br/>
                $pilot->lastFlight <span class=\"badge $daysColor\" title=\"Days since last flight\"><i class=\"bi bi-calendar3\"></i> $pilot->daysSinceLastFlight</span></td>
            <td>$medical<br/>$elp<br/>$sep</td>
            <td class=\"d-none d-xl-table-cell\"><a href=\"mailto:$pilot->email\">$pilot->email</a></td>
            <td class=\"d-none d-xl-table-cell\"><a href=\"tel:$pilot->mobilePhone\">$pilot->mobilePhone</a></td>
            </tr>\n") ;
    }
?>
</tbody>
</table>
</div><!-- table responsive -->
</div><!-- col -->
</div><!-- row --> 
</body>
</html>