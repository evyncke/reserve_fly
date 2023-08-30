<?php
/*
   Copyright 2014-2023 Eric Vyncke

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
require_once 'mobile_header5.php' ;

if (!$userIsAdmin) journalise($userId, "F", "This admin page is reserved to administrators") ;

?><div class="container-fluid">
<h2>Journal système</h2>

<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<tr>
<th>Date</th>
<th>User</th>
<th class="d-none d-lg-block">IP Address</th>
<th>Message</th>
<th class="d-none d-lg-block">URI</th>
</tr>
</thead>
<tbody>
<?php
$start = (isset($_REQUEST['start'])) ? $_REQUEST['start'] : 99999999 ;
$sql_filter = '' ;
if (isset($_REQUEST['id']) and is_numeric($_REQUEST['id']))
	$sql_filter = " AND j_jom_id = $_REQUEST[id]" ;
  else if (isset($_REQUEST['user']))
	$sql_filter = " AND name like '%" . web2db($_REQUEST['user']) . "%'" ;
 else if (isset($_REQUEST['msg']))
	$sql_filter = " AND j_message like '%" . web2db($_REQUEST['msg']) . "%'" ;
$sql = "SELECT * FROM $table_journal LEFT JOIN $table_users u ON j_jom_id = u.id
		WHERE j_id <= $start $sql_filter
		ORDER BY j_id desc
		LIMIT 0, 50" ;
$result = mysqli_query($mysqli_link, $sql) or die("Erreur systeme a propos de l'access au journal: " . mysqli_error($mysqli_link)) ;
$first_id = -1 ;
while ($row = mysqli_fetch_array($result)) {
	if ($first_id < 0) $first_id = $row['j_id'] ;
	$line_count ++ ;
	$last_id = $row['j_id'] ;
	$nameStyle = ($row['j_trusted_booker'] == 1) ? ' style="font-weight: bold;"' : '' ;
	switch (strtoupper($row['j_severity'])) {
		case 'F': $specialClass = ' bg-danger text-bg-danger' ; break ;
		case 'E': $specialClass = ' bg-warning text-bg-warning' ; break ;
		case 'W': $specialClass = ' text-info' ; break ;
		default: $specialClass = '' ;
	}
	$date = $row['j_datetime']	;
	if (strpos($date, date('Y-m-d')) === 0)
		$date = substr($date, 11) ; // Don't display today date
	else 
		$date = substr($date,0, 10) . '<br/>' . substr($date, 11) ; // Nice break
	print("<tr>
		<td class=\"text-nowrap$specialClass\">$date</td>
		<td$nameStyle class=\"text-nowrap$specialClass\">" . db2web($row['name']) . "</td>
		<td class=\"d-none d-lg-table-cell$specialClass\">$row[j_address]</td>
		<td class=\"text-align$specialClass\">" . db2web($row['j_message']) . "</td>
		<td class=\"text-align d-none d-lg-table-cell$specialClass\">" . db2web($row['j_uri']) . "</td>
		</tr>\n") ;
}
$first_id += 50 ;
$last_id -= 1 ;
?>
</tbody>
</table>
<ul class="pagination justify-content-center">
  <li  class="page-item"><a class="page-link" href="<?="$_SERVER[PHP_SELF]?start=$last_id"?>"><i class="bi bi-caret-left-fill"></i> Entrées précédentes</a></li>
  <li  class="page-item"><a class="page-link" href="<?="$_SERVER[PHP_SELF]?start=$first_id"?>"><i class="bi bi-caret-right-fill"></i> Entrées suivantes</a></li>
  <li  class="page-item"><a class="page-link" href="<?="$_SERVER[PHP_SELF]"?>"><i class="bi bi-caret-right-fill"></i><i class="bi bi-caret-right-fill"></i> Dernières entrées</a></li>
</ul><!-- pagination -->
</div> <!-- table responsive -->
Les heures sont les heures locales.<br/>
</div><!-- container -->
</body>
</html>
