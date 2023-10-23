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
    public $planeModel ;
    public $studentLastName ;
    public $studentFirstName ;
    public $fiLastName ;
    public $fiFirstName ;
    public $flightType ;
    public $flightDuration ;
    public $weather ;
    public $remark ;
    public $who ;
    public $when ;
    public $sessionGrading ;

    function __construct($row = NULL) {
        if (!$row)
            return ;
        $this->id = $row['df_id'] ;
        $this->student = $row['df_student'] ;
        $this->studentFirstName = db2web($row['s_first_name']) ;
        $this->studentLastName = db2web($row['s_last_name']) ;
        $this->flightId = $row['df_student_flight'] ;
        $this->FI = $row['l_instructor'] ;
        $this->date = $row['l_start'] ;
        $this->plane = $row['l_plane'] ;
        $this->planeModel = $row['l_model'] ;
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

    function getById($id) {
        global $mysqli_link, $table_dto_flight, $table_logbook, $table_person, $userId ;

        $result = mysqli_query($mysqli_link, "SELECT *, 
                    s.last_name AS s_last_name, s.first_name AS s_first_name, 
                    fi.last_name AS fi_last_name, fi.first_name AS fi_first_name, 
                    60 * (l_end_hour - l_start_hour) + l_end_minute - l_start_minute AS duration 
                FROM $table_dto_flight 
                    JOIN $table_person s ON df_student = s.jom_id
                    JOIN $table_logbook ON df_flight_log = l_id
                    LEFT JOIN $table_person fi ON l_instructor = fi.jom_id
                WHERE df_id = $id")
            or journalise($userId, "F", "Cannot read from $table_dto_flight for flight $id: " . mysqli_error($mysqli_link)) ;
        $row = mysqli_fetch_array($result) ;
        if (! $row) return NULL ;
        $this->__construct($row) ;
    }

    function save() {
        global $mysqli_link, $userId, $table_dto_flight ;

        $remark = web2db(mysqli_real_escape_string($mysqli_link, $this->remark)) ;
        $weather = web2db(mysqli_real_escape_string($mysqli_link, $this->weather)) ;

        mysqli_query($mysqli_link, "UPDATE $table_dto_flight 
            SET df_remark = '$remark', df_weather = '$weather', df_type = '$this->flightType',
                df_who = $userId, df_when = CURRENT_TIMESTAMP()
            WHERE df_id = $this->id")
            or journalise($userId, "F", "Cannot update flight $this->id: " . mysqli_error($mysqli_link)) ;
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
        $sql = "SELECT *, 
                s.last_name AS s_last_name, s.first_name AS s_first_name, 
                fi.last_name AS fi_last_name, fi.first_name AS fi_first_name, 
                60 * (l_end_hour - l_start_hour) + l_end_minute - l_start_minute AS duration
            FROM $table_dto_flight
                JOIN $table_person s ON df_student = s.jom_id
                JOIN $table_logbook ON df_flight_log = l_id
                LEFT JOIN $table_person fi ON l_instructor = fi.jom_id
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

class StudentExercice {
    public $id ;
    public $studentId ;
    public $studentFlight ;
    public $reference ;
    public $description ;
    public $grade ;

    function __construct($row = NULL) {
        if (! $row) return ;
        $this->id = $row['dse_flight'] ;
        $this->studentId = $row['df_student'] ;
        $this->studentFlight = $row['dse_student_flight'] ;
        $this->reference = $row['de_ref'] ;
        $this->description = db2web($row['de_description']) ; // Even if only English for now
        $this->grade = [] ;
        if (strpos($row['dse_grade'], 'demo') !== false)
            $this->grade['demo'] = 'demo' ;
        if (strpos($row['dse_grade'], 'trained') !== false)
            $this->grade['trained'] = 'trained' ;
        if (strpos($row['dse_grade'], 'acquired') !== false)
            $this->grade['acquired'] = 'acquired' ;
    }

    function getByFlightExercice($flightId, $exerciceId) {
        global $mysqli_link, $table_dto_student_exercice, $table_dto_flight, $table_dto_exercice, $userId ;

        $result = mysqli_query($mysqli_link, "SELECT *
                FROM $table_dto_student_exercice 
                    JOIN $table_dto_flight ON dse_flight=df_id
                    JOIN $table_dto_exercice ON de_ref=dse_exercice
                WHERE dse_flight=$flightId AND dse_exercice='$exerciceId'")
            or journalise($userId, "F", "Cannot read from $table_dto_student_exercice for flight $flightId/$exerciceId: " . mysqli_error($mysqli_link)) ;
        $row = mysqli_fetch_array($result) ;
        if (! $row) { // Make a dummy exercice as it can be modified later
            // Fetch the exercice itself
            $result = mysqli_query($mysqli_link, "SELECT * FROM $table_dto_exercice WHERE de_ref='$exerciceId'")
                or journalise($userId, "F", "Cannot fetch exercise in getByFlightExercice($flightId, $exerciceId): " . mysqli_error($mysqli_link)) ;
            $row = mysqli_fetch_array($result) ;
            $row['dse_flight'] = $flightId ;
            $row['dse_grade'] = '' ;
        } ;
        $this->__construct($row) ;
    }

    function save() {
        global $mysqli_link, $userId, $table_dto_student_exercice ;

        $grade = implode(',', $this->grade) ;
        mysqli_query($mysqli_link, "REPLACE INTO $table_dto_student_exercice(dse_flight, dse_exercice, dse_grade, dse_who, dse_when)
            VALUES($this->id, '" . mysqli_real_escape_string($mysqli_link, $this->reference) . "', '$grade', $userId, SYSDATE())")
            or journalise($userId, "F", "Cannot update $table_dto_student_exercice for flight $this->id exercice $this->reference: " . mysqli_error($mysqli_link)) ;
    }
}

class StudentExercices implements Iterator {
    public $count ;
    private $result ;
    private $row ;

    function __construct ($student, $flight = NULL) {
        global $mysqli_link, $table_dto_exercice, $table_dto_student_exercice, $table_dto_flight, $userId ;

        if ($flight)
            $flightCondition = "AND df_id = $flight" ;
        else
            $flightCondition = '' ;
        $sql = "SELECT *
            FROM $table_dto_exercice 
                LEFT JOIN $table_dto_student_exercice ON dse_exercice = de_ref
                LEFT JOIN $table_dto_flight ON dse_flight = df_id AND df_student = $student $flightCondition
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
        return new StudentExercice($this->row);
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