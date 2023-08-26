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
    public $date ;
    public $from ;
    public $to ;
    public $compteur_vol ;
    public $duration ;
    public $duration_hh ;
    public $duration_mm ;
    public $time_start ;
    public $time_end ;
    public $model ;
    public $plane;
    public $cost_plane_minute ;
    public $cost_plane ;
    public $cost_fi ;
    public $cost_taxes ;
    public $item_plane ; // From Ciel Commercial
    public $item_tax ; // From Ciel Commercial
    public $item_fi ; // From Ciel Commercial
    public $pax_count ;
    public $pic_name ;
    public $is_pic ;
    public $instructor_name ;
    public $instructor_fname ;
    public $instructor_code ;
    public $pilot_name ;
    public $pilot_fname ;
    public $pilot_code ;
    public $pilot_code_ciel ;
    public $share_type ;
    public $share_member ;
    public $share_member_name ;
    public $share_member_fname ;
    public $share_member_code_ciel ;

    function __construct($row, $userId) {
        global $revenue_fi_minute, $cost_fi_minute, $code_initiation, $revenue_fi_initiation, $tax_per_pax ;

        $this->date = $row['date'] ;
        $this->from = $row['l_from'] ;
        $this->to = $row['l_to'] ;
        $this->plane = $row['l_plane'] ;
        switch ($this->plane) {
            case 'OO-ALD': $reference2 = '01' ; break ;
            case 'OO-JRB': $reference2 = '02' ; break ;
            case 'OO-APV': $reference2 = '03' ; break ;
            case 'OO-SPP': $reference2 = '04' ; break ;
            case 'OO-ALE': $reference2 = '05' ; break ;
            case 'OO-FMX': $reference2 = '07' ; break ;
            case 'OO-SPQ': $reference2 = '08' ; break ;
            case 'PH-AML': $reference2 = '09' ; break ;
            case 'OO-HBR': $reference2 = '11' ; break ;
            case 'OO-MUA': $reference2 = '12' ; break ;
            case 'OO-SHC': $reference2 = '17' ; break ; // ????
            default: $reference2 = 'XX' ;
        }
        $this->model = $row['l_model'] ;
        $this->compteur_vol = $row['compteur_vol'] ;
        $this->duration = ($row['compteur_vol']) ? $row['flight_duration'] : $row['duration'] ;
        $this->duration_hh = floor($this->duration / 60) ;
        $this->duration_mm = $this->duration % 60 ;
        // DB contains UTC time
	    $this->time_start = gmdate('H:i', strtotime("$row[l_start] UTC")) ;
	    $this->time_end = gmdate('H:i', strtotime("$row[l_end] UTC")) ;
        // Plane cost
        $this->cost_plane_minute = $row['cout'] ;
        if ($row['l_instructor'] == $userId and $row['l_pilot'] != $userId and $row['l_share_member'] != $userId) // FI only pays the plane rental when they are the pilot
		    $this->cost_plane = 0 ;
        else
            if ($row['l_share_type'] == 'CP2') {
                $this->cost_plane = round($row['cout'] * 0.5, 2) * $this->duration ;
                $this->cost_plane_minute = round($row['cout'] * 0.5, 2) ;
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
            if ($row['l_instructor'] != $userId) { // The user is not the FI
                $this->cost_fi = $row['l_instructor_paid'] * $cost_fi_minute * $this->duration ;
            } else {
                $this->cost_fi = - $row['l_instructor_paid'] * $revenue_fi_minute * $this->duration ;
        } else {
		    $this->cost_fi = 0 ;
        }
        // Initiation flights
        if ($row['l_share_type'] == 'CP1' and $row['l_share_member'] == $code_initiation)
            $this->cost_fi -= $revenue_fi_initiation ;
        // Let's generate the book keeping item reference
        switch ($row['l_share_type']) {
            case 'CP1': $reference3plane = '01' ; break ;
            case 'CP2': $reference3plane = '02' ; break ;
            case '': $reference3plane = '00' ; break ;
            default: $reference3plane = '__' ;
        }
        switch ($row['l_instructor']) {
            case  46: $reference3fi = '01' ; break ; // Benoît Mendes
            case  50: $reference3fi = '05' ; break ; // Luc Wynand
            case  59: $reference3fi = '06' ; break ; // Nicolas Claessen
            case 118: $reference3fi = '07' ; break ; // David Gaspar
            case '': $reference3fi = '00' ; break ;
            default: $reference3fi = '__' ;
        }
        $this->item_plane = "1$reference2" . "0$reference3plane" ;
        $this->item_fi = "2$reference2" . "0$reference3fi" ;
        // Flights taking off Belgium have to pay taxes (distance depending but ignored for now)
        // Except Local flight
        // And only the pilot pays the taxes
        if (stripos($row['l_from'], 'EB') === 0 and $row['l_from'] != $row['l_to'] and $row['l_pilot'] == $userId) {
            $this->cost_taxes = $tax_per_pax * $row['l_pax_count'] ;
            $this->item_tax = '402005' ;
        } else {
            $this->cost_taxes = 0 ;
            $this->item_tax = NULL ;
        }
        $this->pax_count = $row['l_pax_count'] ;
        $this->share_type = $row['l_share_type'] ;
        $this->share_member = $row['l_share_member'] ;
	switch ($this->share_member) {
	// Should reflect the content of shareCodes.js 
		case -1: $this->share_member_name = 'Club (Ferry)'; $this->share_member_fname = ''; break ;
		case -2: $this->share_member_name = 'Club (Autres)'; $this->share_member_fname = ''; break ;
		case -3: $this->share_member_name = 'Initiation'; $this->share_member_fname = ''; break ;
		case -4: $this->share_member_name = 'Vol IF'; $this->share_member_fname = ''; break ;
		case -5: $this->share_member_name = 'Vol membre'; $this->share_member_fname = ''; break ;
		case -6: $this->share_member_name = 'Vol D.H.F.'; $this->share_member_fname = ''; break ;
		case -7: $this->share_member_name = 'Club (Vol Président)'; $this->share_member_fname = ''; break ;
		case -8: $this->share_member_name = 'Club (Mécano)'; $this->share_member_fname = ''; break ;
		default:
			$this->share_member_name = db2web($row['share_member_name']) ; 
			$this->share_member_fname = db2web($row['share_member_fname']) ;
	}
        $this->share_member_code_ciel = $row['share_member_code_ciel'] ;
        $this->pilot_name = db2web($row['pilot_name']) ;
        $this->pilot_fname = db2web($row['pilot_fname']) ;
        $this->pilot_code_ciel = $row['pilot_code_ciel'] ;
        $this->pilot_code = $row['l_pilot'] ;
        $this->is_pic = $row['l_is_pic'] ;
        $this->instructor_name = db2web($row['instructor_name']) ; 
        $this->instructor_fname = db2web($row['instructor_fname']) ;
        $this->instructor_code = $row['l_instructor'] ;
    }
} ;

class Folio implements Iterator {
    public $pilot ;
    public $start_date ;
    public $end_date ;
    public $count ;
    public $fname ;
    public $name ;
    public $email ;
    public $address ;
    public $zip_code ;
    public $city ;
    public $country ;
    public $code_ciel ;
    private $result ;
    private $row ;

    function __construct($pilot, $start_date, $end_date) {
        global $mysqli_link, $table_logbook, $table_planes, $table_planes, $table_person, $userId  ;

        $this->pilot = $pilot ;
        $this->start_date = $start_date ;
        $this->end_date = $end_date ;
        $sql = "SELECT l_id, date_format(l_start, '%d/%m/%y') AS date,
            l_model, l_plane, compteur_vol, l_pilot, l_is_pic, l_instructor, l_instructor_paid, 
            i.last_name as instructor_name, i.first_name as instructor_fname, i.ciel_code400 as instructor_code_ciel,
            i.email as instructor_email, i.address as instructor_address, i.zipcode as instructor_zip_code, i.country as instructor_country,
            p.last_name as pilot_name, p.first_name as pilot_fname, p.ciel_code400 as pilot_code_ciel,
            p.email as pilot_email, p.address as pilot_address, p.zipcode as pilot_zip_code, p.country as pilot_country,
            m.last_name as share_member_name, m.first_name as share_member_fname, m.ciel_code400 as share_member_code_ciel,
            m.email as share_member_email, m.address as share_member_address, m.zipcode as share_member_zip_code, m.country as share_member_country,
            UPPER(l_from) as l_from, UPPER(l_to) as l_to, 
            l_start, l_end, 60 * (l_end_hour - l_start_hour) + l_end_minute - l_start_minute as duration,
            60 * (l_flight_end_hour - l_flight_start_hour) + l_flight_end_minute - l_flight_start_minute as flight_duration,
            l_share_type, l_share_member, cout, l_pax_count
            FROM $table_logbook l JOIN $table_planes AS a ON l_plane = a.id
            LEFT JOIN $table_person p ON p.jom_id = l_pilot
            LEFT JOIN $table_person i ON i.jom_id = l_instructor
            LEFT JOIN $table_person m ON m.jom_id = l_share_member
            WHERE (l_pilot = $pilot OR l_share_member = $pilot or l_instructor = $pilot)
                AND l_booking IS NOT NULL
                AND l_start >= '$start_date'
                AND l_start <= '$end_date'
            ORDER by l.l_start ASC" ;
        $this->result = mysqli_query($mysqli_link, $sql) 
            or journalise($userId, "F", "Erreur systeme a propos de l'access au carnet de route: " . mysqli_error($mysqli_link)) ;
        $this->count = mysqli_num_rows($this->result) ;
        $this->row = mysqli_fetch_assoc($this->result) ;
	if ($this->count > 0) {
		if ($pilot == $this->row['l_pilot']) {
			$this->fname = db2web($this->row['pilot_fname']) ;
			$this->name = db2web($this->row['pilot_name']) ;
			$this->code_ciel = $this->row['pilot_code_ciel'] ;
            $this->email = $this->row['pilot_email'] ;
            $this->address = db2web($this->row['pilot_address']) ;
            $this->zip_code = $this->row['pilot_zip_code'] ;
            $this->city = db2web($this->row['pilot_cityl']) ;
            $this->country = db2web($this->row['pilot_country']) ;
		} else if ($pilot == $this->row['l_instructor']) {
			$this->fname = db2web($this->row['instructor_fname']) ;
			$this->name = db2web($this->row['instructor_name']) ;
			$this->code_ciel = $this->row['instructor_code_ciel'] ;
            $this->email = $this->row['instructor_email'] ;
            $this->address = db2web($this->row['instructor_address']) ;
            $this->zip_code = $this->row['instructor_zip_code'] ;
            $this->city = db2web($this->row['instructor_cityl']) ;
            $this->country = db2web($this->row['instructor_country']) ;
        } else if ($pilot == $this->row['l_share_member']) {
			$this->fname = db2web($this->row['share_member_fname']) ;
			$this->name = db2web($this->row['share_member_name']) ;
			$this->code_ciel = $this->row['share_member_code_ciel'] ;
            $this->email = $this->row['share_member_email'] ;
            $this->address = db2web($this->row['share_member_address']) ;
            $this->zip_code = $this->row['share_member_zip_code'] ;
            $this->city = db2web($this->row['share_member_cityl']) ;
            $this->country = db2web($this->row['share_member_country']) ;
        } else
			journalise($userId, "F", "UserId $pilot is neither pilot " . $this->row['l_pilot'] . ", nor instructor " . $this->row['l_instructor'] . ", nor share member " . $this->row['l_share_member']) ;
	}
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
