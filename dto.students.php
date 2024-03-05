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

ob_start("ob_gzhandler");
require_once "dbi.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}
if (! ($userIsAdmin or $userIsBoardMember or $userIsInstructor or $userId == 348)) //Exception for Dominique
    journalise($userId, "F", "Vous devez être administrateur ou instructeur pour voir cette page.") ;
require_once 'mobile_header5.php' ;
require_once 'dto.class.php' ;

if (isset($_REQUEST['fi']) and is_numeric($_REQUEST['fi']) and $_REQUEST['fi'] != '') {
    $fi = $_REQUEST['fi'] ;
} else {
    $fi = NULL ;
}

// Let's get some data from Odoo
require_once 'odoo.class.php' ;
$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;

// Find all Odoo IDs
$sql = "SELECT odoo_id
	FROM $table_person
    JOIN $table_user_usergroup_map ON jom_id = user_id 
    WHERE group_id = $joomla_student_group
    GROUP BY jom_id" ;
$result = mysqli_query($mysqli_link, $sql)
	or journalise($userId, "F", "Cannot retrieve all Odoo ids: " . mysqli_error($mysqli_link)) ;
$ids = array() ;
while ($row = mysqli_fetch_array($result)) {
	$ids[] = intval($row['odoo_id']) ;
}
mysqli_free_result($result) ;
$members = $odooClient->Read('res.partner', 
	array($ids), 
	array('fields' => array('email', 'total_due'))) ;
$odoo_customers = array() ;
foreach($members as $member) {
	$email =  strtolower($member['email']) ;
	$odoo_customers[$email] = $member ; // Let's build a dict indexed by the email addresses
}
?>

<h2>Liste des élèves en cours de formation</h2>
<div class="row">
<div class="col-sm-12 col-md-12 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<th>Elèves</th><th>Premier/dernier vols</th><th class="d-none d-md-table-cell">Email</th><th class="d-none d-md-table-cell">Mobile</th></tr>
</thead>
<tbody class="table-group-divider">

<?php
    $dto = new DTO() ;
    $students = $dto->Students($fi) ;
    foreach($students as $student) {
        if (isset($odoo_customers[strtolower($student->email)])) {
            $odoo_customer = $odoo_customers[strtolower($student->email)] ;
            $blocked = ($student->blocked) ? ' <i class="bi bi-sign-stop-fill text-danger" title="This member is blocked (' . $odoo_customer['total_due'] . '€ due)"></i>' : '' ;
            $bank_filled = ($odoo_customer['total_due'] < 0) ? ' <i class="bi bi-piggy-bank-fill text-success" title="This member has paid ' .
                (-$odoo_customer['total_due']) . '€ for future flights"></i>' : '' ;
        } else {
            $blocked = '<span class="text-danger">Ce membre n\'est pas lié à un compte dans la comptabilité</span>' ;
            $bank_filled = '' ;
        }
        if ($student->mobilePhone == '')
            $mobile_phone = "<i class=\"bi bi-telephone-fill text-danger\" title=\"Pas de téléphone mobile spécifié\">" ;
        else
            $mobile_phone = "<a href=\"tel:$student->mobilePhone\"><i class=\"bi bi-telephone-fill\" title=\"Call on mobile\"></i></a>" ;
        print("<tr>
            <td>
                <a href=\"dto.student.php?student=$student->jom_id\" title=\"Display all flights\">$student->lastName, $student->firstName <i class=\"bi bi-binoculars-fill\"></i></a>
                    <a href=\"mailto:$student->email\"><i class=\"bi bi-envelope-fill\" title=\"Send email\"></i></a>
                    $mobile_phone $blocked $bank_filled
            </td>
            <td>$student->firstFlight <span class=\"badge text-bg-info\" title=\"Number of flights\"><i class=\"bi bi-airplane-fill\"></i> $student->countFlights</span><br/>
                $student->lastFlight <span class=\"badge text-bg-info\" title=\"Days since last flight\"><i class=\"bi bi-calendar3\"></i> $student->daysSinceLastFlight</span></td>
            <td class=\"d-none d-md-table-cell\"><a href=\"mailto:$student->email\">$student->email</a></td>
            <td class=\"d-none d-md-table-cell\"><a href=\"tel:$student->mobilePhone\">$student->mobilePhone</a></td>
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