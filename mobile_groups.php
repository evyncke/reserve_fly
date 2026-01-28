<?php
/*
   Copyright 2023-2026 Eric Vyncke

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

$original_userId = $userId ;

if (isset($_REQUEST['user']) and ($userIsAdmin or $userIsBoardMember)) {
	$userId = $_REQUEST['user'] ;
	if (! is_numeric($userId)) die("Invalid user ID") ;
} 

// As $userId may not be the orinal, let's recompute the groups set
$result = mysqli_query($mysqli_link, "SELECT group_id FROM jom_user_usergroup_map WHERE user_id = $userId")
    or journalise($original_userId, "F", "Cannot read groups: " . mysqli_error($mysqli_link)) ;
$joomla_groups = array() ;
while ($row = mysqli_fetch_array($result)) {
    $joomla_groups[$row[0]] = $row[0] ;
}
// User privileges
$userIsPilot = array_key_exists($joomla_pilot_group, $joomla_groups)  ;
$userIsAdmin = array_key_exists($joomla_admin_group, $joomla_groups) 
    || array_key_exists($joomla_sysadmin_group, $joomla_groups) 
    || array_key_exists($joomla_superuser_group, $joomla_groups) ;
$userIsInstructor = array_key_exists($joomla_instructor_group, $joomla_groups) ;
$userIsBoardMember = array_key_exists($joomla_board_group, $joomla_groups) ;
$userIsEffectiveMember = array_key_exists($joomla_effectif_group, $joomla_groups) ;
$userIsMechanic = array_key_exists($joomla_mechanic_group, $joomla_groups) ;
$userIsFlyingStudent = array_key_exists($joomla_flying_student_group, $joomla_groups) ;
$userIsTheoryStudent = array_key_exists($joomla_theory_student_group, $joomla_groups) ;
$userIsFlightPilot = array_key_exists($joomla_flight_pilot_group, $joomla_groups) || array_key_exists($joomla_flight_group, $joomla_groups);
$userIsFlightManager = array_key_exists($joomla_flight_manager_group, $joomla_groups) ;
$userNoFlight = array_key_exists($joomla_no_flight, $joomla_groups) ;

// Check whether the user is blocked
$result = mysqli_query($mysqli_link, "SELECT * 
	FROM $table_person LEFT JOIN $table_blocked on jom_id=b_jom_id
	WHERE jom_id = $userId")
	or journalise($originalUserId, 'F', "Impossible de lire le pilote $userId: " . mysqli_error($mysqli_link)) ;
$pilot = mysqli_fetch_array($result) or journalise($originalUserId, 'F', "Pilote $userId inconnu") ;
$userName = db2web("$pilot[first_name] $pilot[last_name]") ;
$userLastName = substr(db2web($pilot['last_name']), 0, 5) ;
$blocked_reason = db2web($pilot['b_reason']) ;
$blocked_when = $pilot['b_when'] ;

?> 
<div class="container">

<div class="page-header">
<h2>Les groupes de <?=$userName?></h2>
</div> <!-- row -->

<div class="row">

<p>Voici les divers groupes auxquels <?=$userName?> appartient:
    <ul>
<?php
if ($userIsFlyingStudent) print("<li>élève navigant</li>") ;
if ($userIsTheoryStudent) print("<li>élève théorique</li>") ;
if ($userIsPilot) print("<li>pilote</li>") ;
if ($userIsMechanic) print("<li>mécano</li>") ;
if ($userIsInstructor) print("<li>instructeur</li>") ;
if ($userIsEffectiveMember) print("<li>membre effectif (droit de vote lors des AG)</li>") ;
if ($userIsAdmin) print("<li>gestionnaire-système, les options des menus en italique vous sont réservées</li>") ;
if ($userIsBoardMember) print("<li>administrateur (membre de l'OA), les options des menus en italique vous sont réservées</li>") ;
?>
</ul>
</p>
<?php
if (! ($userIsPilot || $userIsAdmin || $userIsInstructor || $userIsMechanic))
	print("<p class=\"text-warning\">Vous devez être au moins pilote pour réserver un avion.</p>") ;

if ($userNoFlight)
	print("<p class=\"mt-4 p-5 bg-danger text-bg-danger rounded\">Vous êtes interdit(e) de vol (par exemple: factures non payées, 
		contactez <a href=\"mailto:info@spa-aviation.be\">info@spa-aviation.be</a>.
		Choisissez l'option 'mon folio' dans le menu déroulant à droite afin de visualiser votre situation comptable.</p>") ;

if ($blocked_when) {
	print("<p class=\"mt-4 p-5 bg-danger text-bg-danger rounded\"> <b>$blocked_reason</b>. 
		Contactez <a href=\"mailto:info@spa-aviation.be\">l'a&eacute;roclub info@spa-aviation.be</a>.
        Choisissez l'option 'mon folio' dans le menu déroulant à droite afin de visualiser votre situation comptable.</p>") ;
}
?>
</div><!-- row -->

</div> <!-- container-->

</body>
</html>