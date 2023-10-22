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

    function __construct($row) {
        $this->firstName = db2web($row['first_name']) ;
        $this->lastName = db2web($row['last_name']) ;
        $this->jom_id = $row['jom_id'] ;
        $this->email = $row['email'] ;
        $this->mobilePhone = $row['cell_phone'] ;
    }
}

class FI extends DTOMember {

    function __construct($row) {
        parent::__construct($row) ;     
    }
}

class TKI extends DTOMember {

}

class Student extends DTOMember {
    public $firstFlight ;
    public $lastFlight ;

    function __construct($row) {
        parent::__construct($row) ; 
        $this->firstFlight = $row['first_flight'] ;
        $this->lastFlight = $row['last_flight'] ;   
    }

    function Flights() {

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
    public $student ;
    public $FI ;
    public $weather ;
    public $remark ;
    public $who ;
    public $when ;
    public $sessionGrading ;

}

class Flights implements Iterator {
    public $studentId ;
    private $result ;
    private $row ;

    function __construct ($studentId) {
        $this->studentId = $studentId ; 
    }

    function __destruct() {
        mysqli_free_result($this->result) ;
    }

    public function current() {
        return new Flight($this->row);
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