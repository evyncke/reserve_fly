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
require_once 'dbi.php' ;

MustBeLoggedIn() ;

if (!($userIsAdmin or $userIsBoardMember))
	journalise($userId, "F", "This admin page is reserved to administrators") ;

if ($_SERVER['REQUEST_METHOD'] == 'POST' and ($_POST['action'] ?? '') == 'export') {
	journalise($userId, "I", "Début de sauvegarde de la DB") ;
	$tables_result = mysqli_query($mysqli_link, 'SHOW FULL TABLES') ;
	if (!$tables_result) {
		journalise($userId, "F", "Cannot list database tables: " . htmlspecialchars(mysqli_error($mysqli_link))) ;
	}

	$tables = array() ;
	while ($table_row = mysqli_fetch_row($tables_result)) {
		$table_name = $table_row[0] ;
		if ($table_row[1] == 'BASE TABLE' and (str_starts_with($table_name, 'rapcs_') or str_starts_with($table_name, 'tp_') or $table_name == 'jom_users' or $table_name == 'jom_user_usergroup_map'  or $table_name == 'jom_usergroups'))
			$tables[] = $table_name ;
	}
	mysqli_free_result($tables_result) ;
	sort($tables) ;

	$definitions = array() ;
	foreach ($tables as $table_name) {
		$quoted_table = '`' . str_replace('`', '``', $table_name) . '`' ;
		$create_result = mysqli_query($mysqli_link, "SHOW CREATE TABLE $quoted_table") ;
		if (!$create_result) {
			journalise($userId, "F", "Cannot read table structure: " . htmlspecialchars(mysqli_error($mysqli_link))) ;
		}
		$create_row = mysqli_fetch_row($create_result) ;
		mysqli_free_result($create_result) ;
		$definitions[$table_name] = $create_row[1] ;
	}

	$timestamp = date('Y-m-d-His') ;
	$sql_filename = "rapcs-database-$timestamp.sql" ;
	$sql_path = tempnam(sys_get_temp_dir(), 'rapcs-sql-') ;
	$zip_path = tempnam(sys_get_temp_dir(), 'rapcs-zip-') ;
	$sql_file = fopen($sql_path, 'wb') ;
	if (!$sql_file) {
		@unlink($sql_path) ;
		@unlink($zip_path) ;
		journalise($userId, "F", 'Cannot create temporary SQL backup file: ' . $sql_path) ;
	}
	fwrite($sql_file, "-- RAPCS SQL backup v1\n") ;
	fwrite($sql_file, "SET FOREIGN_KEY_CHECKS=0;\n") ;
	foreach ($tables as $table_name) {
		$quoted_table = '`' . str_replace('`', '``', $table_name) . '`' ;
		$definition = $definitions[$table_name] ;
		$definition = preg_replace_callback("/('[^'\\\\]*(?:\\\\.[^'\\\\]*)*'|\"[^\"\\\\]*(?:\\\\.[^\"\\\\]*)*\"|`[^`]*`)|\\s+/s", function ($match) {
			return isset($match[1]) ? $match[1] : ' ' ;
		}, trim($definition)) ;
		fwrite($sql_file, "DROP TABLE IF EXISTS $quoted_table;\n") ;
		fwrite($sql_file, $definition . ";\n") ;

		$data_result = mysqli_query($mysqli_link, "SELECT * FROM $quoted_table", MYSQLI_USE_RESULT) ;
		if (!$data_result) {
			fclose($sql_file) ;
			@unlink($sql_path) ;
			@unlink($zip_path) ;
			exit ;
		}
		while ($data_row = mysqli_fetch_row($data_result)) {
			$values = array() ;
			foreach ($data_row as $value) {
				$values[] = ($value === null) ? 'NULL' : "'" . mysqli_real_escape_string($mysqli_link, $value) . "'" ;
			}
			fwrite($sql_file, "INSERT INTO $quoted_table VALUES (" . implode(', ', $values) . ");\n") ;
		}
		mysqli_free_result($data_result) ;
	}
	fwrite($sql_file, "SET FOREIGN_KEY_CHECKS=1;\n") ;
	fclose($sql_file) ;

	$zip = new ZipArchive() ;
	if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
		@unlink($sql_path) ;
		@unlink($zip_path) ;
		journalise($userId, "F", 'Cannot create ZIP backup archive.') ;
	}
	$zip_added = $zip->addFile($sql_path, $sql_filename) ;
	$zip_closed = $zip->close() ;
	if (!$zip_added or !$zip_closed) {
		@unlink($sql_path) ;
		@unlink($zip_path) ;
		journalise($userId, "F", 'Cannot create ZIP backup archive.') ;
	}
	header('Content-Type: application/zip') ;
	header('Content-Disposition: attachment; filename="rapcs-database-' . $timestamp . '.zip"') ;
	header('Content-Length: ' . filesize($zip_path)) ;
	readfile($zip_path) ;
	@unlink($sql_path) ;
	@unlink($zip_path) ;
	journalise($userId, "I", "Sauvegarde effectuée dans rapcs-database-$timestamp.zip") ;
	exit ;
}

require_once 'mobile_header5.php' ;
?>
<main class="container py-3">
  <h1 class="h3">Export de la base de données</h1>
	<p>Le fichier ZIP contiendra la structure et les données des tables <code>rapcs*</code>, <code>tp*</code>, <code>jom_users</code>, <code>jom_user_usergroup_map</code> et <code>jom_usergroups</code>.</p>
  <form method="post">
    <input type="hidden" name="action" value="export">
	<button type="submit" class="btn btn-primary"><i class="bi bi-download"></i> Télécharger l'export SQL ZIP</button>
  </form>
</main>
</body>
</html>