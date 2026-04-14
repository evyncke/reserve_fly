<?php
/*
   Copyright 2026 Eric Vyncke

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

$header_postamble = '<link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">' ;
require_once 'mobile_header5.php' ;
require_once 'dto.class.php' ;

?>
<h2>Liste des anciens élèves</h2>
<div class="row">
    <p>Cette liste reprend les membres (actuels et anciens) qui ont effectué au moins un vol en tant qu'élèves. L'année des cours théoriques
        n'existe que depuis 2024 environ. <i class="bi bi-person-check-fill text-success"></i> indique un membre en règle de cotisation pour
        cette année calendrier.
    </p>
</div>
<div class="row">
<div class="col-sm-12 col-md-12 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover" id="students-table">
<thead>
<th>Élève</th><th>Cours théoriques</th><th>Premier/dernier vols</th><th class="d-none d-md-table-cell">Email</th><th class="d-none d-md-table-cell">Mobile</th></tr>
</thead>
<tbody class="table-group-divider">

<?php
// Find all previous students, i.e. all users that are not in the flying student or theory student groups
$sql = "SELECT *, DATE(MIN(df_when)) AS first_flight, DATE(MAX(df_when)) AS last_flight
	FROM $table_person AS p
    JOIN $table_dto_flight ON p.jom_id = df_student
    LEFT JOIN $table_dto_student ON p.jom_id = ds_jom_id
    LEFT JOIN $table_membership_fees ON bkf_user = p.jom_id AND bkf_year = YEAR(CURDATE())
    WHERE NOT EXISTS (SELECT 1 FROM $table_user_usergroup_map 
            WHERE user_id = p.jom_id AND group_id IN ($joomla_flying_student_group, $joomla_theory_student_group))
    GROUP BY p.jom_id
    ORDER BY last_name, first_name" ;
$result = mysqli_query($mysqli_link, $sql)
	or journalise($userId, "F", "Cannot retrieve all previous students: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
    if ($row['bkf_payment_date'] != '')
        $membership_filled = '<i class="bi bi-person-check-fill text-success" title="Membership paid"><i>' ;
    else
        $membership_filled = '<i class="bi bi-person-fill-exclamation text-danger" title="Membership NOT paid"><i>' ;
    print("<tr><td><a href=\"dto.student.php?student=$row[jom_id]&previous=y\"><b>$row[last_name]</b>, $row[first_name]</a> $membership_filled</td>
        <td>$row[ds_year]</td>
        <td>$row[first_flight]<br>$row[last_flight]</td>
        <td><a href=\"mailto:$row[email]\">$row[email]</a></td>
        <td><a href=\"tel:$row[cell_phone]\">$row[cell_phone]</a></td></tr>") ;
}
mysqli_free_result($result) ;
?>
</tbody>
</table>
</div><!-- table responsive -->
</div><!-- col -->
</div><!-- row --> 
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest"></script>
<script>
    new window.simpleDatatables.DataTable("#students-table", {
        searchable: true,
        fixedHeight: false,
        paging: false,
        labels: {
            placeholder: "Rechercher...",
            noRows: "Aucune entrée trouvée",
            info: "Affichage de {start} à {end} sur {rows} entrées",
        }
    });
</script>
</body>
</html>