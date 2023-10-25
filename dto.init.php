<?php
require_once('dbi.php') ;

mysqli_query($mysqli_link, "DELETE FROM $table_dto_flight") or die("Cannot delete previous entries: " . mysqli_error($mysqli_link)) ;
mysqli_query($mysqli_link, "ALTER TABLE $table_dto_flight AUTO_INCREMENT = 1") or die("Cannot reset AI: " . mysqli_error($mysqli_link)) ;

$result = mysqli_query($mysqli_link, "SELECT *
    FROM $table_logbook
        JOIN $table_person ON l_pilot = jom_id
        JOIN  $table_user_usergroup_map ON jom_id = user_id 
    WHERE group_id = $joomla_student_group
    ORDER BY l_pilot, l_start ASC")
    or die("Cannot select flights: " . mysqli_error($mysqli_link)) ;

$previous_pilot = -1 ;
while ($row = mysqli_fetch_array($result)) {
    if ($previous_pilot != $row['l_pilot']) {
        $previous_pilot = $row['l_pilot'] ;
        $this_flight = 1 ;
    } else
        $this_flight ++ ;
    if ($row['from'] != $row['to'])
        $type = 'Xcountry' ;
    else if ($row['l_instructor'] == '')
        $type = 'solo' ;
    else
        $type = 'DC' ;
    mysqli_query($mysqli_link, "INSERT INTO $table_dto_flight(df_student, df_student_flight, df_flight_log, df_type, df_session_grade, df_who, df_when)
            VALUES ($row[l_pilot], $this_flight, $row[l_id], '$type', 'satisfactory', $row[l_audit_who], '$row[l_audit_time]')")
        or die("Cannot create flight: " . mysqli_error($mysqli_link)) ;
}
?>