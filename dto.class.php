<?php
/*
   Copyright 2023-2023 Eric Vyncke

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

require_once('dbi.php') ;

class DTO {
    function Students($fi) {
        global $joomla_student_group ;
        return new DTOMembers($joomla_student_group, $fi) ;
    }

    function FIs() {
        global $joomla_instructor_group ;
        return new DTOMembers($joomla_instructor_group) ;
    }
}

class DTOMember {
    public $firstName ;
    public $lastName ;
    public $jom_id ;
    public $email ;
    public $mobilePhone ;

    function __construct($row = NULL) {
        if ($row) {
            $this->firstName = db2web($row['first_name']) ;
            $this->lastName = db2web($row['last_name']) ;
            $this->jom_id = $row['jom_id'] ;
            $this->email = $row['email'] ;
            $this->mobilePhone = $row['cell_phone'] ;
        }
    }

    function getById($jom_id) {
        global $mysqli_link, $table_person, $userId ;

        $result = mysqli_query($mysqli_link, "SELECT * FROM $table_person WHERE jom_id = $jom_id")
            or journalise($userId, "F", "Cannot read from $table_person for $jom_id: " . mysqli_error($mysqli_link)) ;
        $row = mysqli_fetch_array($result) ;
        if (! $row) return NULL ;
        $this->__construct($row) ;
    }
}

class FI extends DTOMember {

    function __construct($row = NULL) {
        parent::__construct($row) ;     
    }
}

class TKI extends DTOMember {

}

class Student extends DTOMember {
    public $firstFlight ;
    public $lastFlight ;

    function __construct($row = NULL) {
        parent::__construct($row) ; 
        if ($row) {
            $this->firstFlight = $row['first_flight'] ;
            $this->lastFlight = $row['last_flight'] ;   
        }
    }

    function Flights() {
        return new Flights($this->jom_id) ;
    }
}

class DTOMembers implements Iterator {
    public $group ;
    public $count ;
    public $fi ; // SHould perhaps be private... a little ugle anyway
    private $result ;
    private $row ;

    function __construct ($group, $fi = NULL) {
        global $mysqli_link, $table_person, $table_dto_flight, $table_user_usergroup_map, $table_logbook, $userId ;

        $this->group = $group ; // FI, TKI, Student, ...
        if ($fi)
            $fi_condition = "AND l_instructor = $fi " ;
        else 
            $fi_condition = '' ;
        $sql = "SELECT *, MIN(l_start) AS first_flight, MAX(l_end) AS last_flight 
                FROM $table_person 
                    JOIN $table_user_usergroup_map ON jom_id = user_id 
                    LEFT JOIN $table_dto_flight ON df_student = jom_id
                    LEFT JOIN $table_logbook ON df_flight_log = l_id
                WHERE group_id = $this->group $fi_condition
                GROUP BY jom_id
                ORDER BY last_name, first_name" ;
        $this->result = mysqli_query($mysqli_link, $sql) 
                or journalise($userId, "F", "Erreur systeme a propos de l'access aux membres du groupe $this->group: " . mysqli_error($mysqli_link)) ;
        $this->count = mysqli_num_rows($this->result) ;
        $this->row = mysqli_fetch_assoc($this->result) ;
    }

    function __destruct() {
        mysqli_free_result($this->result) ;
    }

    public function current() {
        global $joomla_instructor_group, $joomla_student_group ;

        switch ($this->group) {
            case $joomla_instructor_group: return new FI($this->row) ; break ;
            case $joomla_student_group: return new Student($this->row) ; break ;
            default: return new DTOMember($this->row);
        }
    }
    
    public function key() {
        return $this->row['jom_id'];
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

class Flight {
    public $id ;
    public $student ;
    public $flightId ;
    public $FI ;
    public $date ;
    public $plane ;
    public $fiLastName ;
    public $fiFirstName ;
    public $flightType ;
    public $flightDuration ;
    public $weather ;
    public $remark ;
    public $who ;
    public $when ;
    public $sessionGrading ;

    function __construct($row) {
        $this->id = $row['df_id'] ;
        $this->student = $row['df_student'] ;
        $this->flightId = $row['df_student_flight'] ;
        $this->FI = $row['l_instructor'] ;
        $this->date = $row['l_start'] ;
        $this->plane = $row['l_plane'] ;
        $this->fiFirstName = db2web($row['fi_first_name']) ;
        $this->fiLastName = db2web($row['fi_last_name']) ;
        $this->flightType = $row['df_type'] ;
        $this->flightDuration = $row['duration'] ;
        $this->weather = db2web($row['df_weather']) ;
        $this->remark = db2web($row['df_remark']) ;
        $this->who = $row['df_who'] ;
        $this->when = $row['df_when'] ;
        if ($row['df_session_grade'] == '')
            $this->sessionGrading = NULL ;
        else
            $this->sessionGrading = $row['df_session_grade'] ;
    }
}

class Flights implements Iterator {
    public $studentId ;
    public $count ;
    private $result ;
    private $row ;

    function __construct ($studentId) {
        global $mysqli_link, $table_person, $table_dto_flight, $table_user_usergroup_map, $table_logbook, $userId ;

        $this->studentId = $studentId ; 
        $sql = "SELECT *, p.last_name AS fi_last_name, p.first_name AS fi_first_name, 60 * (l_end_hour - l_start_hour) + l_end_minute - l_start_minute AS duration
            FROM $table_dto_flight
                JOIN $table_logbook ON df_flight_log = l_id
                LEFT JOIN $table_person p ON l_instructor = jom_id
            WHERE df_student = $this->studentId
            ORDER BY df_student_flight" ;
        $this->result = mysqli_query($mysqli_link, $sql) 
                or journalise($userId, "F", "Erreur systeme a propos de l'access aux vols école de $this->studentId: " . mysqli_error($mysqli_link)) ;
        $this->count = mysqli_num_rows($this->result) ;
        $this->row = mysqli_fetch_assoc($this->result) ;
    }

    function __destruct() {
        mysqli_free_result($this->result) ;
    }

    public function current() {
        return new Flight($this->row);
    }
    
    public function key() {
        return $this->row['df_id'];
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

class Exercice {
    public $reference ;
    public $description ;
    public $id ;

    function __construct($row) {
        $this->id = $row['de_id'] ;
        $this->reference = $row['de_ref'] ;
        $this->description = db2web($row['de_description']) ; // Even if only in English for now...
    }
}
class Exercices implements Iterator {
    public $count ;
    private $result ;
    private $row ;

    function __construct () {
        global $mysqli_link, $table_dto_exercice, $userId ;

        $sql = "SELECT *
            FROM $table_dto_exercice
            ORDER BY de_id" ;
        $this->result = mysqli_query($mysqli_link, $sql) 
                or journalise($userId, "F", "Erreur systeme a propos de l'access aux exercices école: " . mysqli_error($mysqli_link)) ;
        $this->count = mysqli_num_rows($this->result) ;
        $this->row = mysqli_fetch_assoc($this->result) ;
    }

    function __destruct() {
        mysqli_free_result($this->result) ;
    }

    public function current() {
        return new Exercice($this->row);
    }
    
    public function key() {
        return $this->row['de_id'];
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