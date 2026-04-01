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
$header_postamble = '<link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">' ;
require_once 'mobile_header5.php' ;

if (! ($userIsAdmin or $userIsBoardMember or $userIsInstructor))
    journalise($userId, "F", "Vous devez être administrateur ou instructeur pour voir cette page.") ;

$this_year = 0 + date('Y') ;
$validity = 13 ; // Hard coded :-()
?>

<h2>Safety Day <?=$this_year?> Attendance</h2>

<div class="row text-info px-3">
The table below only lists members/students who entered the code or clicked on the QR-code in the slides. <span id="search-count"></span>
</div>
<div class="row">
<div class="col-sm-12 col-md-9 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover" id="attestation-table">
<thead>
<tr><th>Member</th><th>Date attested</th><th>Valid until</th></tr>
</thead>
<tbody class="table-group-divider">

<?php
//     WHERE expire_date > CURRENT_DATE()
$result = mysqli_query($mysqli_link, "SELECT *, if(expire_date < CURRENT_DATE(), 1, 0) as expired, 
        if(expire_date < date_add(CURRENT_DATE(), interval 1 month), 1, 0) as expire_soon 
    FROM $table_person p JOIN jom_users u ON u.id = p.jom_id
        LEFT JOIN $table_validity v ON p.jom_id = v.jom_id and validity_type_id = 13 
        LEFT JOIN jom_user_usergroup_map g ON g.user_id = p.jom_id AND g.group_id = $joomla_student_group
    WHERE u.block = 0
    ORDER by last_name, first_name")
    or journalise($userId, "F", "Cannot read validity: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
    $icon = ($row['group_id']== $joomla_student_group) ? ' <i class="bi bi-mortarboard-fill"></i>' : '' ;
    if ($row['expire_soon'])
        $classText = ' class="text-warning"' ;
    else if ($row['expired'] or $row['expire_date'] == '')
        $classText = ' class="text-danger"' ;
    else
        $classText = '' ;
    print("<tr><td$classText>" . db2web("<b>$row[last_name]</b> $row[first_name]$icon</td><td>$row[grant_date]</td><td>$row[expire_date]</td></tr>\n")) ;
}
?>
</tbody>
</table>
</div><!-- table responsive -->
</div><!-- col -->
</div><!-- row --> 
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest"></script>
<script>
    const dataTable = new window.simpleDatatables.DataTable("#attestation-table", {
        searchable: true,
        fixedHeight: false,
        paging: false,
        labels: {
            placeholder: "Rechercher...",
            noRows: "Aucune entrée trouvée",
            info: "Affichage de {start} à {end} sur {rows} entrées",
        }
    });
    // 1. Initial count on load
    const countElement = document.querySelector("#search-count");
    countElement.innerHTML = `Showing ${dataTable.data.data.length} total entries.` ;

    // 2. Update count on search
    dataTable.on("datatable.search", (query, matched) => {
        // 'matched' is an array of the rows that fit the search criteria
        const total = dataTable.data.data.length;
        const count = matched.length;
        
        countElement.innerHTML = `Showing ${count} of ${total} total entries`;
    });
</script>
</body>
</html>
