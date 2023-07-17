<?php
/*
   Copyright 2023 Eric Vyncke

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
class FolioLine{
 //   private $sqlArray ;
    public $date ;
    public $from ;
    public $to ;
    public $duration ;
    public $duration_hh ;
    public $duration_mm ;
    public $time_start ;
    public $time_end ;
    public $model ;
    public $plane;
    public $cost_plane ;
    public $cost_fi ;
    public $cost_taxes ;
    public $pax_count ;
    public $share_type ;
    public $share_member ;
    public $pic_name ;
    public $instructor_name ;
    public $instructor_code ;
    public $pilot_name ;
    public $pilot_fname ;
    public $pilot_code_ciel ;

    function __construct($row, $userId) {
        global $revenue_fi_minute, $cost_fi_minute, $code_initiation, $revenue_fi_initiation, $tax_per_pax ;
 //       $this->sqlArray = $row ;

        $this->date = $row['date'] ;
        $this->from = $row['l_from'] ;
        $this->to = $row['l_to'] ;
        $this->plane = $row['l_plane'] ;
        $this->model = $row['l_model'] ;
        $this->duration = ($row['compteur_vol']) ? $row['flight_duration'] : $row['duration'] ;
        $this->duration_hh = floor($this->duration / 60) ;
        $this->duration_mm = $this->duration % 60 ;
        // DB contains UTC time
	    $this->time_start = gmdate('H:i', strtotime("$row[l_start] UTC")) ;
	    $this->time_end = gmdate('H:i', strtotime("$row[l_end] UTC")) ;
        // Plane cost
        if ($row['l_instructor'] == $userId and $row['l_pilot'] != $userId and $row['l_share_member'] != $userId) // FI only pays the plane rental when they are the pilot
		    $this->cost_plane = 0 ;
        else
            if ($row['l_share_type'] == 'CP2') {
                $this->cost_plane = round($row['cout'] * 0.5, 2) * $this->duration ;
            } else if ($row['l_share_type'] == 'CP1' and $row['l_share_member'] != $userId) {
                $this->cost_plane = 0 ;
            } else
                $this->cost_plane = $row['cout'] * $this->duration ;	
        // Vol PIC- Recheck : 
        // Pour le Pilote -> SELF
        // Pour l'Instructeur -> Pilote_name    
        if ($row['l_instructor'] < 0) $row['instructor_name'] = 'Autre FI' ;
        if ($row['l_instructor'] != $userId  and  $row['l_is_pic']) { // PIC 
            $this->pic_name = "SELF" ; //Pilot Point of View. A PIC-Recheck is SELF
        } else  // Dual command
            if ($userId == $row['l_instructor'])
                $this->pic_name = db2web($row['pilot_name']) ; //Point of view of the Instructore. A PIC Recheck is a DC
            else
                $this->pic_name = db2web($row['instructor_name']) ;// DC 
        // Instructor cost
        if ($row['l_instructor'])
            if ($row['l_instructor'] != $userId) // The user is not the FI
                $this->cost_fi = $row['l_instructor_paid'] * $cost_fi_minute * $this->duration ;
            else
                $this->cost_fi = - $row['l_instructor_paid'] * $revenue_fi_minute * $this->duration ;
	    else
		    $this->cost_fi = 0 ;
        // Initiation flights
        if ($row['l_share_type'] == 'CP1' and $row['l_share_member'] == $code_initiation)
            $this->cost_fi -= $revenue_fi_initiation ;
        // Flights taking off Belgium have to pay taxes (distance depending but ignored for now)
        // Except Local flight
        // And only the pilot pays the taxes
        if (stripos($row['l_from'], 'EB') === 0 and $row['l_from'] != $row['l_to'] and $row['l_pilot'] == $userId) {
            $this->cost_taxes = $tax_per_pax * $row['l_pax_count'] ;
        } else {
            $this->cost_taxes = 0 ;
        }
        $this->pax_count = $row['pax_count'] ;
        $this->share_type = $row['share_type'] ;
        $this->share_member = $row['share_member'] ;
        $this->pilot_name = db2web($row['pilot_name']) ;
        $this->pilot_fname = db2web($row['pilot_fname']) ;
        $this->pilot_code_ciel = $row['code_ciel'] ;
        $this->instructor_name = db2web($row['instructor_name']) ;
        $this->instructor_code = $row['l_instructor'] ;
    }
} ;

class Folio implements Iterator {
    public $pilot ;
    public $start_date ;
    public $end_date ;
    public $count ;
    private $result ;
    private $row ;

    function __construct($pilot, $start_date, $end_date) {
        global $mysqli_link, $table_logbook, $table_planes, $table_planes, $table_person, $userId  ;

        $this->pilot = $pilot ;
        $this->start_date = $start_date ;
        $this->end_date = $end_date ;
        $sql = "SELECT l_id, date_format(l_start, '%d/%m/%y') AS date,
            l_model, l_plane, compteur_vol, l_pilot, l_is_pic, l_instructor, l_instructor_paid, 
            i.last_name as instructor_name, p.last_name as pilot_name, p.first_name as pilot_fname, p.ciel_code400 as code_ciel,
            UPPER(l_from) as l_from, UPPER(l_to) as l_to, 
            l_start, l_end, 60 * (l_end_hour - l_start_hour) + l_end_minute - l_start_minute as duration,
            60 * (l_flight_end_hour - l_flight_start_hour) + l_flight_end_minute - l_flight_start_minute as flight_duration,
            l_share_type, l_share_member, cout, l_pax_count
            FROM $table_logbook l JOIN $table_planes AS a ON l_plane = a.id
            LEFT JOIN $table_person p ON p.jom_id = l_pilot
            LEFT JOIN $table_person i ON i.jom_id = l_instructor
            WHERE (l_pilot = $pilot OR l_share_member = $pilot or l_instructor = $pilot)
                AND l_booking IS NOT NULL
                AND l_start >= '$start_date'
                AND l_start <= '$end_date'
            ORDER by l.l_start ASC" ;
        $this->result = mysqli_query($mysqli_link, $sql) 
            or journalise($userId, "F", "Erreur systeme a propos de l'access au carnet de route: " . mysqli_error($mysqli_link)) ;
        $this->count = mysqli_num_rows($this->result) ;
        $this->row = mysqli_fetch_assoc($this->result) ;
    }

    function __destruct() {
        mysqli_free_result($this->result) ;
    }

    public function current() {
        return new FolioLine($this->row, $this->pilot);
    }
    
    public function key() {
        return $this->row['l_id'];
    }
    
    public function next():void {
        $this->row = mysqli_fetch_assoc($this->result) ;
    }
    
    public function rewind():void {
        mysqli_data_seek($this->result, 0) ;
        $this->row = mysqli_fetch_assoc($this->result) ;
    }
    
    public function valid():bool {
        return $this->row != false;
    }
}
?>