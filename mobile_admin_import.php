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

if (session_status() !== PHP_SESSION_ACTIVE)
	session_start() ;
if (!isset($_SESSION['admin_import_token']))
	$_SESSION['admin_import_token'] = bin2hex(random_bytes(32)) ;

$message = '' ;
$message_type = 'danger' ;

function sqlBackupTablePattern(): string {
	return '(?:rapcs|tp)[A-Za-z0-9_$]*|jom_user|jom_users|jom_user_usergroup_map|jom_usergroups' ;
}

function sqlBackupArchiveEntry(ZipArchive $zip, string &$error_message): string|false {
	if ($zip->numFiles != 1) {
		$error_message = 'L’archive doit contenir exactement un fichier SQL.' ;
		return false ;
	}
	$entry_name = $zip->getNameIndex(0) ;
	if (!is_string($entry_name) or basename($entry_name) != $entry_name or str_contains($entry_name, '\\') or !str_ends_with(strtolower($entry_name), '.sql')) {
		$error_message = 'L’archive ne contient pas un fichier SQL valide.' ;
		return false ;
	}
	return $entry_name ;
}

function validateSqlBackup(string $filename, string &$error_message): bool {
	$zip = new ZipArchive() ;
	if (@$zip->open($filename) !== true) {
		$error_message = 'Le fichier n’est pas une archive ZIP lisible.' ;
		return false ;
	}
	$entry_name = sqlBackupArchiveEntry($zip, $error_message) ;
	if ($entry_name === false) {
		$zip->close() ;
		return false ;
	}
	$sql_file = $zip->getStream($entry_name) ;
	if (!$sql_file) {
		$zip->close() ;
		$error_message = 'Impossible de lire le fichier SQL de l’archive.' ;
		return false ;
	}

	$first_line = fgets($sql_file) ;
	if (trim((string) $first_line) != '-- RAPCS SQL backup v1') {
		fclose($sql_file) ;
		$zip->close() ;
		$error_message = 'Ce fichier ne correspond pas à un export RAPCS.' ;
		return false ;
	}

	$dropped_tables = array() ;
	$created_tables = array() ;
	$table_pattern = sqlBackupTablePattern() ;
	while (($line = fgets($sql_file)) !== false) {
		$statement = trim($line) ;
		if ($statement == '' or str_starts_with($statement, '--'))
			continue ;
		if ($statement == 'SET FOREIGN_KEY_CHECKS=0;' or $statement == 'SET FOREIGN_KEY_CHECKS=1;')
			continue ;
		if (preg_match('/^DROP TABLE IF EXISTS `(' . $table_pattern . ')`;$/i', $statement, $matches)) {
			if (isset($dropped_tables[$matches[1]])) {
				fclose($sql_file) ;
				$zip->close() ;
				$error_message = 'Le fichier contient plusieurs suppressions de la même table.' ;
				return false ;
			}
			$dropped_tables[$matches[1]] = true ;
			continue ;
		}
		if (preg_match('/^CREATE TABLE `(' . $table_pattern . ')` \(/i', $statement, $matches)
			and isset($dropped_tables[$matches[1]]) and !isset($created_tables[$matches[1]])) {
			$created_tables[$matches[1]] = true ;
			continue ;
		}
		if (preg_match('/^INSERT INTO `(' . $table_pattern . ')` VALUES \(.+\);$/i', $statement, $matches)
			and isset($created_tables[$matches[1]]))
			continue ;

		fclose($sql_file) ;
		$zip->close() ;
		$error_message = 'Le fichier contient une instruction SQL inattendue ou une table non autorisée.' ;
		return false ;
	}
	$archive_ok = fclose($sql_file) and $zip->close() ;
	if (!$created_tables or count($created_tables) != count($dropped_tables) or !$archive_ok) {
		$error_message = 'L’archive ZIP est incomplète ou ne contient aucune table.' ;
		return false ;
	}
	return true ;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' and ($_POST['action'] ?? '') == 'import') {
	$token = $_POST['csrf_token'] ?? '' ;
	if (!is_string($token) or !hash_equals($_SESSION['admin_import_token'], $token)) {
		$message = 'La vérification de sécurité a échoué. Rechargez la page et réessayez.' ;
	} elseif (($_POST['confirm'] ?? '') != 'yes') {
		$message = 'Confirmez explicitement le remplacement des tables avant de continuer.' ;
	} elseif (!isset($_FILES['backup']) or $_FILES['backup']['error'] != UPLOAD_ERR_OK or !is_uploaded_file($_FILES['backup']['tmp_name'])) {
		$message = 'Le fichier n’a pas été reçu correctement.' ;
	} else {
		$error_message = '' ;
		if (!validateSqlBackup($_FILES['backup']['tmp_name'], $error_message)) {
			$message = $error_message ;
		} else {
			$zip = new ZipArchive() ;
			$zip->open($_FILES['backup']['tmp_name']) ;
			$entry_name = sqlBackupArchiveEntry($zip, $error_message) ;
			$sql_file = $zip->getStream($entry_name) ;
			fgets($sql_file) ;
			$imported_tables = array() ;
			$import_error = '' ;
			$table_pattern = sqlBackupTablePattern() ;
			try {
				if (!mysqli_query($mysqli_link, 'SET FOREIGN_KEY_CHECKS=0'))
					$import_error = mysqli_error($mysqli_link) ;
				while ($import_error == '' and ($line = fgets($sql_file)) !== false) {
					$statement = trim($line) ;
					if ($statement == '' or str_starts_with($statement, '--'))
						continue ;
					if ($statement == 'SET FOREIGN_KEY_CHECKS=0;' or $statement == 'SET FOREIGN_KEY_CHECKS=1;')
						continue ;
					if (preg_match('/^(?:DROP TABLE IF EXISTS|CREATE TABLE) `(' . $table_pattern . ')`/i', $statement, $matches))
						$imported_tables[$matches[1]] = true ;

					if (!mysqli_query($mysqli_link, $statement)) {
						$import_error = mysqli_error($mysqli_link) ;
						break ;
					}
				}
			} catch (mysqli_sql_exception $exception) {
				$import_error = $exception->getMessage() ;
			} finally {
				fclose($sql_file) ;
				$zip->close() ;
				try {
					mysqli_query($mysqli_link, 'SET FOREIGN_KEY_CHECKS=1') ;
				} catch (mysqli_sql_exception $exception) {
					if ($import_error == '')
						$import_error = $exception->getMessage() ;
				}
			}
			if ($import_error != '') {
				$message = 'Import interrompu après une erreur SQL : ' . $import_error ;
			} else {
				$message_type = 'success' ;
				$message = 'Import terminé. Tables traitées : ' . implode(', ', array_keys($imported_tables)) ;
			}
		}
	}
}

require_once 'mobile_header5.php' ;
?>
<main class="container py-3">
  <h1 class="h3">Import de la base de données</h1>
  <div class="alert alert-warning" role="alert">
    L’import remplace les tables incluses dans l’export. Cette opération peut laisser la base partiellement restaurée si une erreur survient.
  </div>
<?php if ($message != '') { ?>
  <div class="alert alert-<?=$message_type?>" role="alert"><?=htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?></div>
<?php } ?>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="import">
    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['admin_import_token'], ENT_QUOTES, 'UTF-8')?>">
    <div class="mb-3">
	<label for="backup" class="form-label">Fichier SQL ZIP</label>
	<input type="file" class="form-control" id="backup" name="backup" accept=".zip,application/zip" required>
    </div>
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" id="confirm" name="confirm" value="yes" required>
      <label class="form-check-label" for="confirm">Je confirme le remplacement des tables importées.</label>
    </div>
    <button type="submit" class="btn btn-danger"><i class="bi bi-upload"></i> Importer le fichier</button>
  </form>
</main>
</body>
</html>