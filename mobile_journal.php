<?php
/*
   Copyright 2014-2026 Eric Vyncke

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
/**
 * System Journal Display
 * 
 * Displays system journal entries with pagination and filtering capabilities.
 * Only accessible by administrators and board members.
 * 
 * @package RAPCS
 * @author Eric Vyncke
 * @license Apache-2.0
 */

require_once "dbi.php" ;
MustBeLoggedIn() ;
$need_swiped_events = true ; // Allow swipe events on this page
$header_postamble = '<link href="https://cdn.jsdelivr.net/npm/simple-datatables@10.3.0/dist/style.css" rel="stylesheet" type="text/css" integrity="sha384-CinX6s1UC1GpaQwL9eKshF7JhtC8H2xchDD0dWqlCgZ8t+GCCcjluxCgmsbVpdEh" crossorigin="anonymous">' ;
require_once 'mobile_header5.php' ;

if (!($userIsAdmin or $userIsBoardMember))
	journalise($userId, "F", "This admin page is reserved to administrators") ;

?><div class="container-fluid">
<h2>Journal système</h2>

<div class="table-responsive">
<table class="table table-striped table-hover" id="journal-table">
<thead>
<tr>
<th scope='col'>Date</th>
<th scope='col'>User</th>
<th scope='col' class="d-none d-lg-block">IP Address</th>
<th scope='col'>Message</th>
<th scope='col' class="d-none d-lg-block">URI</th>
</tr>
</thead>
<tbody>
<?php
// Only digits are accepted and the value is cast to an integer: it is used unquoted in the SQL query
$start = (isset($_GET['start']) and is_string($_GET['start']) and ctype_digit($_GET['start']) and (int) $_GET['start'] > 0) ? (int) $_GET['start'] : DB_MAX_UINT ;
$sql_filter = '' ;
$filter_params = array() ; // The active filter, to be kept in the pagination and swipe links
if (isset($_REQUEST['id']) and ctype_digit($_REQUEST['id'])) {
	$sql_filter = " AND j_jom_id = $_REQUEST[id]" ;
	$filter_params['id'] = $_REQUEST['id'] ;
} else if (isset($_REQUEST['usr'])) {
	$sql_filter = " AND name like '%" . web2db(mysqli_real_escape_string($mysqli_link, $_REQUEST['usr'])) . "%'" ;
	$filter_params['usr'] = $_REQUEST['usr'] ;
} else if (isset($_REQUEST['msg'])) {
	$sql_filter = " AND j_message like '%" . web2db(mysqli_real_escape_string($mysqli_link, $_REQUEST['msg'])) . "%'" ;
	$filter_params['msg'] = $_REQUEST['msg'] ;
} else if (isset($_REQUEST['ip'])) {
	$sql_filter = " AND j_address like '%" . web2db(mysqli_real_escape_string($mysqli_link, $_REQUEST['ip'])) . "%'" ;
	$filter_params['ip'] = $_REQUEST['ip'] ;
}
$sql = "SELECT * FROM $table_journal
			LEFT JOIN $table_person p ON j_jom_id = p.jom_id
		WHERE j_id <= $start $sql_filter
		ORDER BY j_id desc
		LIMIT 0, 25" ;
$result = mysqli_query($mysqli_link, $sql) 
	or journalise($userId, "F", "Erreur systeme a propos de l'access au journal: " . mysqli_error($mysqli_link)) ;
$first_id = -1 ;
$last_id = -1 ;
$row_count = 0 ;
while ($row = mysqli_fetch_array($result)) {
	$row_count++ ;
	if ($first_id < 0) $first_id = $row['j_id'] ;
	$last_id = $row['j_id'] ;
	$nameStyle = ($row['j_trusted_booker'] == 1) ? ' style="font-weight: bold;"' : '' ;
	switch (strtoupper($row['j_severity'])) {
		case 'F': $specialClass = ' table-danger' ; break ;
		case 'E': $specialClass = ' table-warning' ; break ;
		case 'W': $specialClass = ' table-info' ; break ;
		default: $specialClass = '' ;
	}
	$date = $row['j_datetime']	;
	if (strpos($date, date('Y-m-d')) === 0)
		$date = substr($date, 11) ; // Don't display today date
	else 
		$date = substr($date,0, 10) . '<br>' . substr($date, 11) ; // Nice break
	if (isset($row['last_name']) and $row['last_name'] != '')
		$name = db2web('<strong>' . cleanHTML($row['last_name']) . '</strong><span class="d-none d-md-inline"> ' . cleanHTML($row['first_name']) . '</span>') ;
	else
		$name = cleanHTML(db2web($row['name'] ?? '')) ;
	// The URI is the logged request URI, i.e., controlled by the visitor: escape it and link only local paths
	$uri = db2web($row['j_uri'] ?? '') ;
	$uri_html = cleanHTML($uri) ;
	$uri_cell = (preg_match('#^/(?![/\\\\])#', $uri)) ? "<a href=\"$uri_html\">$uri_html</a>" : $uri_html ;
	print("<tr>
		<td class=\"text-nowrap$specialClass\">$date</td>
		<td$nameStyle class=\"text-nowrap$specialClass\">$name</td>
		<td class=\"d-none d-lg-table-cell$specialClass\">" . cleanHTML($row['j_address']) . "</td>
		<td class=\"text-start$specialClass\">" . cleanHTML(db2web($row['j_message'])) . "</td>
		<td class=\"text-start d-none d-lg-table-cell$specialClass\">$uri_cell</td>
		</tr>\n") ;
}
// Pagination start ids (null when there is no such page). The journal ids have gaps when a filter is active,
// so do not compute them arithmetically but ask the database
// Older entries: a full page means that there may be more, and "j_id <= start" copes with gaps
$older_start = ($row_count == 25 and $last_id > 1) ? $last_id - 1 : null ;
// Newer entries: the 25 entries immediately after the first displayed one (with the same filter), start is the newest of them
$newer_start = null ;
if ($row_count > 0) {
	$result_newer = mysqli_query($mysqli_link, "SELECT MAX(j_id) AS newer_start FROM
			(SELECT j_id FROM $table_journal
				LEFT JOIN $table_person p ON j_jom_id = p.jom_id
			WHERE j_id > $first_id $sql_filter
			ORDER BY j_id ASC
			LIMIT 25) AS newer")
		or journalise($userId, "F", "Erreur systeme a propos de l'access au journal (entrees plus recentes): " . mysqli_error($mysqli_link)) ;
	$row_newer = mysqli_fetch_array($result_newer) ;
	$newer_start = $row_newer['newer_start'] ; // NULL when we are already on the newest page
	mysqli_free_result($result_newer) ;
}

// URL of this page for a given start id, keeping the active filter
function journalUrl($theFilterParams, $theStart = null) {
	$params = ($theStart === null) ? $theFilterParams : array('start' => $theStart) + $theFilterParams ;
	return $_SERVER['SCRIPT_NAME'] . ((count($params) > 0) ? '?' . http_build_query($params) : '') ;
}
?>
</tbody>
</table>
<ul class="pagination justify-content-center">
  <li class="page-item<?=($older_start === null) ? ' disabled' : ''?>"><a class="page-link" href="<?=($older_start === null) ? '#' : htmlspecialchars(journalUrl($filter_params, $older_start))?>"<?=($older_start === null) ? ' aria-disabled="true" tabindex="-1"' : ''?>><i class="bi bi-caret-left-fill"></i> Entrées précédentes</a></li>
  <li class="page-item<?=($newer_start === null) ? ' disabled' : ''?>"><a class="page-link" href="<?=($newer_start === null) ? '#' : htmlspecialchars(journalUrl($filter_params, $newer_start))?>"<?=($newer_start === null) ? ' aria-disabled="true" tabindex="-1"' : ''?>><i class="bi bi-caret-right-fill"></i> Entrées suivantes</a></li>
  <li  class="page-item"><a class="page-link" href="<?=htmlspecialchars(journalUrl($filter_params))?>"><i class="bi bi-chevron-double-right"></i></i> Dernières entrées</a></li>
</ul><!-- pagination -->
</div> <!-- table responsive -->
<p class="text-muted small">Les heures sont en heures locales.</p>
</div><!-- container -->
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@10.3.0/dist/umd/simple-datatables.min.js" integrity="sha384-BEpG7sFvcL57u3OEtietm61i9CEIOFR3zR2mlbaAtw+CyHWKNNMEJ7lb852F1iNb" crossorigin="anonymous"></script>
<script>
	// Swipe to change to next webcam
<?php if ($older_start !== null) { ?>
	document.addEventListener('swiped-right', function(e) {location.href=<?=json_encode(journalUrl($filter_params, $older_start))?> }) ;
<?php } if ($newer_start !== null) { ?>
	document.addEventListener('swiped-left', function(e) {location.href=<?=json_encode(journalUrl($filter_params, $newer_start))?> }) ;
<?php } ?>
    new window.simpleDatatables.DataTable("#journal-table", {
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