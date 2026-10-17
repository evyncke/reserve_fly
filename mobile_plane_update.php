<?php
/*
   Copyright 2014-2026 Eric Vyncke (Claude Sonnet 5.5)

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

// Edit the plane data shown in mobile_plane_planning.php (reserved to mechanics and administrators)
// The fields and their labels are the ones of the table of mobile_plane_planning.php (see its <th> headers)

require_once 'dbi.php' ;
MustBeLoggedIn() ;

if (!($userIsMechanic))
	journalise($userId, "E", "Cette page est réservée aux mécaniciens.") ;

$plane_id = trim($_REQUEST['id'] ?? '') ;
if ($plane_id === '')
	journalise($userId, "F", "Aucun avion indiqué") ;

// Editable columns of the planes table: column => array(label = <th> group + <th> column of mobile_plane_planning.php, kind, help)
// kinds: number (-1 means N/A), date (AAAA-MM-JJ, empty = 0000-00-00), text, cn (DD-MM)
$fields = array(
	'type_entretien' => array('label' => 'Inspections: type', 'kind' => 'text', 'maxlength' => 30, 'list' => array('50h', '100h', '200h'),
		'help' => 'Commence par 50h, 100h ou 200h: détermine la colonne 50h / 100h / 200h du tableau'),
	'entretien' => array('label' => 'Inspections: 50h / 100h / 200h', 'kind' => 'number', 'help' => 'Prochaine échéance (heures moteur)'),
	'limite_moteur_heure' => array('label' => 'Time limit: Eng (heures)', 'kind' => 'number', 'help' => 'Heures moteur limites, -1 si N/A'),
	'limite_moteur_12ans' => array('label' => 'Time limit: Eng (12 ans, date)', 'kind' => 'date'),
	'limite_helice' => array('label' => 'Time limit: Prop (heures)', 'kind' => 'number', 'help' => 'Heures hélice limites, -1 si N/A'),
	'batterie_date' => array('label' => 'Batterie: Date installation', 'kind' => 'date'),
	'cn' => array('label' => 'Certificat Navigabilité', 'kind' => 'cn', 'help' => 'Jour et mois du certificat de navigabilité, format JJ-MM'),
	'limite_magnetos' => array('label' => 'Mag.: 500h (heures)', 'kind' => 'number', 'help' => 'Heures limites des magnétos, -1 si N/A'),
	'pesage' => array('label' => 'Pesage: 10 y (date)', 'kind' => 'date'),
	'plb_date_limite' => array('label' => 'PLB: Date', 'kind' => 'date'),
	'instrument_date_limite' => array('label' => 'Instruments: Date', 'kind' => 'date'),
) ;

function fetchPlane(string $plane_id) : array {
	global $mysqli_link, $table_planes, $userId ;

	$stmt = mysqli_prepare($mysqli_link, "SELECT * FROM $table_planes WHERE id = ? AND ressource = 0") ;
	if (!$stmt) journalise($userId, "F", "Cannot read the plane: " . mysqli_error($mysqli_link)) ;
	mysqli_stmt_bind_param($stmt, 's', $plane_id) ;
	mysqli_stmt_execute($stmt) ;
	$plane = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ;
	mysqli_stmt_close($stmt) ;
	return $plane ;
}

$plane = fetchPlane($plane_id) ;
if ($plane === null or $plane === false)
	journalise($userId, "F", "Avion inconnu: " . htmlspecialchars($plane_id)) ;

// Value of a column as a string for the HTML form
function formValue($value, string $kind) {
	if ($value === null) return '' ;
	if ($kind == 'date' and ($value == '0000-00-00' or $value == '')) return '' ;
	if ($kind == 'text' or $kind == 'cn') return db2web($value) ;
	return (string) $value ;
}

$errors = array() ;
$posted = array() ; // Values to re-display in case of error

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$changes = array() ; // column => new database value
	$changes_log = array() ;
	foreach ($fields as $name => $field) {
		$raw = $_POST['f_' . $name] ?? '' ;
		if (!is_string($raw)) continue ;
		$raw = trim($raw) ;
		$posted[$name] = $raw ;
		$old = $plane[$name] ;
		$new = $raw ;

		if ($raw === '') {
			if ($field['kind'] == 'number') {
				$errors[$name] = "Une valeur est obligatoire (-1 si non applicable)" ;
				continue ;
			}
			if ($old === null) continue ; // Was empty (NULL) and stays so
			$new = ($field['kind'] == 'date') ? '0000-00-00' : '' ;
		} else switch ($field['kind']) {
			case 'number':
				$new = str_replace(',', '.', $raw) ;
				if (!is_numeric($new)) $errors[$name] = "Nombre attendu" ;
				break ;
			case 'date':
				$d = DateTime::createFromFormat('Y-m-d', $raw) ;
				if ($d === false or $d->format('Y-m-d') != $raw) $errors[$name] = "Date attendue (AAAA-MM-JJ)" ;
				break ;
			case 'cn':
				if (!preg_match('/^(0[1-9]|[12]\d|3[01])-(0[1-9]|1[0-2])$/', $raw)) $errors[$name] = "Format JJ-MM attendu" ;
				break ;
			case 'text':
				if (mb_strlen($raw) > ($field['maxlength'] ?? 255)) $errors[$name] = "Maximum " . ($field['maxlength'] ?? 255) . " caractères" ;
				break ;
		}
		if (isset($errors[$name])) continue ;
		if ($field['kind'] == 'text' or $field['kind'] == 'cn') $new = web2db($new) ;

		// Only write the columns that changed
		$same = ($old === null) ? false : (($field['kind'] == 'number') ? (float) $old == (float) $new : (string) $old === (string) $new) ;
		if (!$same) {
			$changes[$name] = $new ;
			$changes_log[] = "$name: '" . mb_substr((string) $old, 0, 60) . "' -> '" . mb_substr((string) $new, 0, 60) . "'" ;
		}
	}

	if (!$errors and $changes) {
		$sets = array() ;
		$values = array() ;
		foreach ($changes as $name => $new) {
			$sets[] = "`$name` = ?" ; // $name comes from the $fields list above, never from the request
			$values[] = $new ;
		}
		$stmt = mysqli_prepare($mysqli_link, "UPDATE $table_planes SET " . implode(', ', $sets) . " WHERE id = ? AND ressource = 0") ;
		if (!$stmt) journalise($userId, "F", "Cannot prepare the plane update: " . mysqli_error($mysqli_link)) ;
		$values[] = $plane_id ;
		mysqli_stmt_bind_param($stmt, str_repeat('s', count($values)), ...$values) ;
		if (!mysqli_stmt_execute($stmt)) {
			$errors['_'] = "Erreur de base de données, aucune modification enregistrée." ;
			journalise($userId, "E", "Cannot update plane $plane_id: " . mysqli_stmt_error($stmt)) ;
		} else {
			journalise($userId, "I", "Avion $plane_id modifié: " . implode('; ', $changes_log)) ;
		}
		mysqli_stmt_close($stmt) ;
	}

	if (!$errors) {
		header("Location: " . SITE_URL . "mobile_plane_update.php?id=" . urlencode($plane_id) . "&saved=" . ($changes ? '1' : '0'), TRUE, 303) ;
		exit ;
	}
}

require_once 'mobile_header5.php' ;

$plane_name = htmlspecialchars(strtoupper($plane_id)) ;
?>
<div class="container-fluid">
<h2>Modification de l'avion <?=$plane_name?></h2>
<?php
if (isset($_GET['saved']))
	print($_GET['saved'] == '1' ? '<div class="alert alert-success">Les modifications ont été enregistrées.</div>' : '<div class="alert alert-info">Aucune modification.</div>') ;
if (isset($errors['_']))
	print('<div class="alert alert-danger">' . htmlspecialchars($errors['_']) . '</div>') ;
else if ($errors)
	print('<div class="alert alert-danger">Veuillez corriger les champs en erreur: rien n\'a été enregistré.</div>') ;
?>
<form method="post" action="mobile_plane_update.php?id=<?=htmlspecialchars(urlencode($plane_id))?>" class="col-sm-12 col-lg-8">
<div class="mb-3"><label class="form-label fw-bold" for="plane_id">Avion</label>
<input type="text" class="form-control" id="plane_id" value="<?=$plane_name?>" readonly disabled></div>
<?php
foreach ($fields as $name => $field) {
	$field_name = htmlspecialchars('f_' . $name) ;
	$label = htmlspecialchars($field['label']) ;
	$value = htmlspecialchars($posted[$name] ?? formValue($plane[$name], $field['kind'])) ;
	$invalid = isset($errors[$name]) ? ' is-invalid' : '' ;
	print("<div class=\"mb-3\"><label class=\"form-label fw-bold\" for=\"$field_name\">$label</label>") ;
	if (isset($field['help']))
		print(' <small class="text-muted">' . htmlspecialchars($field['help']) . '</small>') ;
	switch ($field['kind']) {
		case 'number':
			print("<input type=\"text\" inputmode=\"decimal\" class=\"form-control$invalid\" id=\"$field_name\" name=\"$field_name\" value=\"$value\" required>") ;
			break ;
		case 'date':
			print("<input type=\"date\" class=\"form-control$invalid\" id=\"$field_name\" name=\"$field_name\" value=\"$value\">") ;
			break ;
		case 'cn':
			print("<input type=\"text\" class=\"form-control$invalid\" id=\"$field_name\" name=\"$field_name\" value=\"$value\" maxlength=\"5\" placeholder=\"JJ-MM\" pattern=\"(0[1-9]|[12][0-9]|3[01])-(0[1-9]|1[0-2])\">") ;
			break ;
		default: // text
			$list = '' ;
			if (isset($field['list'])) {
				$list = " list=\"{$field_name}_list\"" ;
				print("<datalist id=\"{$field_name}_list\">") ;
				foreach ($field['list'] as $option) print('<option value="' . htmlspecialchars($option) . '">') ;
				print('</datalist>') ;
			}
			print("<input type=\"text\" class=\"form-control$invalid\" id=\"$field_name\" name=\"$field_name\" value=\"$value\" maxlength=\"" . intval($field['maxlength'] ?? 255) . "\"$list>") ;
	}
	if (isset($errors[$name]))
		print('<div class="invalid-feedback d-block">' . htmlspecialchars($errors[$name]) . '</div>') ;
	print("</div>\n") ;
}
?>
<button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Enregistrer</button>
<a href="mobile_plane_planning.php" class="btn btn-secondary">Retour aux échéances</a>
</form>
</div><!-- container-fluid -->
</body>
</html>
