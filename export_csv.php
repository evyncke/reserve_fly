<?php
/*
   Copyright 2014-2022 Eric Vyncke

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

function exportSQL($query, $headers, $fileName) {
	global $mysqli_link ;

	$result = mysqli_query($mysqli_link, $query)
			or journalise($userId, "E", "Cannot execute query for $fileNale" . mysqli_error($mysqli_link)) ;
	$file = fopen($fileName, "w") ;
	if (! $file) journalise($userId, "E", "Cannot open $fileName") ;
	fputcsv($file, $headers) ;
	while ($row = mysqli_fetch_row($result)) {
		fputcsv($file, $row) ;
	}
	fclose($file) ;
}

$startDate = date('Y-m') . '-01' ;

exportSQL("SELECT id, model, actif, cout, compteur, compteur_vol_valeur, compteur_type, compteur_vol, entretien, ressource
                FROM $table_planes WHERE ressource = 0",
	array('id', 'model', 'actif', 'cout', 'compteur', 'compteur_vol_valeur', 'compteur_type', 'compteur_vol', 'entretien', 'ressource'),
	"data/$table_planes.csv") ;

exportSQL("SELECT id, jom_id, name, first_name, last_name, email, home_phone, cell_phone, activated, ciel_code
		FROM $table_person",
	array('id', 'jom_id', 'name', 'first_name', 'last_name', 'email', 'home_phone', 'cell_phone', 'activated', 'ciel_code'),
	"data/$table_person.csv") ;

exportSQL("SELECT l_id, l_plane, l_start, l_end, l_start_hour,
			l_start_minute, l_end_hour, l_end_minute,
			l_flight_start_hour, l_flight_start_minute, l_flight_end_hour, l_flight_end_minute,
			l_flight_type, l_booking, l_from, l_to, l_pilot, l_is_pic, l_instructor, l_instructor_paid,
			l_pax_count, l_remark, l_share_type, l_share_member
		FROM $table_logbook
		WHERE l_start >= SUBDATE('$startDate', INTERVAL 1 MONTH)",
	array('l_id', 'l_plane', 'l_start', 'l_end', 'l_start_hour',
                        'l_start_minute', 'l_end_hour', 'l_end_minute',
                        'l_flight_start_hour', 'l_flight_start_minute', 'l_flight_end_hour', 'l_flight_end_minute',
			'l_flight_type', 'l_booking', 'l_from', 'l_to', 'l_pilot', 'l_is_pic', 'l_instructor', 'l_instructor_paid',
			'l_pax_count', 'l_remark', 'l_share_type', 'l_share_member'),
	"data/$table_logbook.csv") ;

exportSQL("SELECT r_id, r_plane, r_start, r_stop, r_pilot, r_instructor, r_comment, r_who, r_from, r_to, r_cancel_date, r_cancel_who, r_cancel_reason
		FROM $table_bookings
		WHERE r_start >= SUBDATE('$startDate', INTERVAL 1 MONTH)",
	array('r_id', 'r_plane', 'r_start', 'r_stop', 'r_pilot', 'r_instructor', 'r_comment', 'r_who', 'r_from', 'r_to', 'r_cancel_date', 'r_cancel_who', 'r_cancel_reason'),
	"data/$table_bookings.csv") ;
?>
