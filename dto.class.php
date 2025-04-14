<?php
/*
   Copyright 2023-2025 Eric Vyncke

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
    public $address ;
    public $zipCode ;
    public $city ;
    public $country ;
    public $blocked ;
    public $blockedMessage ;
    public $membershipPaid ;
    public $groupMembership ;

    function __construct($row = NULL) {
        if ($row) {
            $this->firstName = db2web($row['first_name']) ;
            $this->lastName = db2web($row['last_name']) ;
            $this->jom_id = $row['jom_id'] ;
            $this->email = $row['email'] ;
            $this->mobilePhone = $row['cell_phone'] ;
            $this->address = db2web($row['address']) ;
            $this->zipCode = $row['zipcode'] ;
            $this->city = db2web($row['city']) ;
            $this->country = db2web($row['country']) ;
            $this->blocked = ($row['b_reason'] != '') ;
            $this->blockedMessage = db2web($row['b_reason']) ;
            $this->membershipPaid = ($row['bkf_payment_date'] != '') ;
            $this->groupMembership = $row['group_ids'] ;
        }
    }

    function getById($jom_id) {
        global $mysqli_link, $table_blocked, $table_person, $table_user_usergroup_map, $table_membership_fees, $userId, $membership_year;

        $result = mysqli_query($mysqli_link, "SELECT *, GROUP_CONCAT(group_id) AS group_ids 
                FROM $table_person
                LEFT JOIN $table_blocked ON b_jom_id = jom_id
                JOIN $table_user_usergroup_map ON jom_id = user_id  
                LEFT JOIN $table_membership_fees ON bkf_user = jom_id AND bkf_year = $membership_year
                WHERE jom_id = $jom_id")
            or journalise($userId, "F", "Cannot read from $table_person for $jom_id: " . mysqli_error($mysqli_link)) ;
        $row = mysqli_fetch_array($result) ;
        if (! $row) return NULL ;
        $this->__construct($row) ;
    }

    function isStudent() {
        // TODO: oversimplistic... should explode on ',' then look if exit in array
        global $joomla_student_group ;

        return strpos($this->groupMembership, $joomla_student_group) !== FALSE ;
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
    public $countFlights ;
    public $daysSinceLastFlight ;

    function __construct($row = NULL) {
        parent::__construct($row) ; 
        if ($row) {
            if (isset($row['first_flight'])) { // Depending on how the Student is created (listing all students or via a DTOMmember.getById() some columns are not present
                $this->firstFlight = $row['first_flight'] ;
                $this->lastFlight = $row['last_flight'] ;  
                $this->countFlights = $row['count_flights'] ; 
                $this->daysSinceLastFlight = $row['days_since_last_flight'] ;
            } 
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
        global $mysqli_link, $table_users, $table_person, $table_dto_flight, $table_user_usergroup_map, 
            $table_logbook, $table_blocked, $table_membership_fees, $membership_year, $userId ;

        $this->group = $group ; // FI, TKI, Student, ...
        if ($fi)
            $fi_condition = "AND l_instructor = $fi " ;
        else 
            $fi_condition = '' ;
        $sql = "SELECT *, MIN(DATE(l_start)) AS first_flight, MAX(DATE(l_start)) AS last_flight, COUNT(l_start) AS count_flights, 
                    MIN(DATEDIFF(SYSDATE(), DATE(l_start))) AS days_since_last_flight,
                    GROUP_CONCAT(group_id) AS group_ids 
                FROM $table_person 
                    JOIN $table_users AS u ON u.id = jom_id
                    JOIN $table_user_usergroup_map ON jom_id = user_id 
                    LEFT JOIN $table_dto_flight ON df_student = jom_id
                    LEFT JOIN $table_logbook ON df_flight_log = l_id
                    LEFT JOIN $table_blocked ON b_jom_id = jom_id
                    LEFT JOIN $table_membership_fees ON bkf_user = jom_id AND bkf_year = $membership_year
                WHERE group_id = $this->group AND block = 0  $fi_condition
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

    public function current(): mixed {
        global $joomla_instructor_group, $joomla_student_group ;

        switch ($this->group) {
            case $joomla_instructor_group: return new FI($this->row) ; break ;
            case $joomla_student_group: return new Student($this->row) ; break ;
            default: return new DTOMember($this->row);
        }
    }
    
    public function key(): mixed {
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
    public $flightLog ;
    public $studentLastName ;
    public $studentFirstName ;
    public $fiLastName ;
    public $fiFirstName ;
    public $flightType ;
    public $flightDuration ;
    public $remark ;
    public $who ;
    public $whoFirstName ;
    public $whoLastName ;
    public $when ;
    public $sessionGrade ;

    function __construct($row = NULL) {
        if (!$row)
            return ;
        $this->id = $row['df_id'] ;
        $this->student = $row['df_student'] ;
        $this->studentFirstName = db2web($row['s_first_name']) ;
        $this->studentLastName = db2web($row['s_last_name']) ;
        $this->flightId = $row['df_student_flight'] ;
        $this->FI = $row['l_instructor'] ;
        $this->date = $row['date'] ;
        $this->plane = $row['l_plane'] ;
        $this->planeModel = $row['l_model'] ;
        $this->flightLog = $row['df_flight_log'] ;
        $this->fiFirstName = db2web($row['fi_first_name']) ;
        $this->fiLastName = db2web($row['fi_last_name']) ;
        $this->flightType = $row['df_type'] ;
        $this->flightDuration = $row['duration'] ;
        $this->remark = db2web($row['df_remark']) ;
        if ($row['df_session_grade'] == '')
            $this->sessionGrade = 'satisfactory' ;
        else
            $this->sessionGrade = $row['df_session_grade'] ;
        $this->who = $row['df_who'] ;
        $this->whoFirstName = db2web($row['who_first_name']) ;
        $this->whoLastName = db2web($row['who_last_name']) ;
        $this->when = $row['df_when'] ;
    }

    function getById($id) {
        global $mysqli_link, $table_dto_flight, $table_logbook, $table_person, $userId ;

        $result = mysqli_query($mysqli_link, "SELECT *, DATE(l_start) AS date,
                    s.last_name AS s_last_name, s.first_name AS s_first_name, 
                    fi.last_name AS fi_last_name, fi.first_name AS fi_first_name,
                    who.last_name AS who_last_name, who.first_name AS who_first_name,
                    60 * (l_end_hour - l_start_hour) + l_end_minute - l_start_minute AS duration 
                FROM $table_dto_flight 
                    JOIN $table_person s ON df_student = s.jom_id
                    JOIN $table_logbook ON df_flight_log = l_id
                    LEFT JOIN $table_person fi ON l_instructor = fi.jom_id
                    LEFT JOIN $table_person who ON df_who = who.jom_id
                WHERE df_id = $id")
            or journalise($userId, "F", "Cannot read from $table_dto_flight for flight $id: " . mysqli_error($mysqli_link)) ;
        $row = mysqli_fetch_array($result) ;
        if (! $row) return NULL ;
        $this->__construct($row) ;
    }

    function getLastByFi($fi) {
        global $mysqli_link, $table_dto_flight, $table_logbook, $table_person, $userId ;

        $result = mysqli_query($mysqli_link, "SELECT *, DATE(l_start) AS date,
                    s.last_name AS s_last_name, s.first_name AS s_first_name, 
                    fi.last_name AS fi_last_name, fi.first_name AS fi_first_name, 
                    who.last_name AS who_last_name, who.first_name AS who_first_name,
                    60 * (l_end_hour - l_start_hour) + l_end_minute - l_start_minute AS duration 
                FROM $table_dto_flight 
                    JOIN $table_person s ON df_student = s.jom_id
                    JOIN $table_logbook ON df_flight_log = l_id
                    LEFT JOIN $table_person fi ON l_instructor = fi.jom_id
                    LEFT JOIN $table_person who ON df_who = who.jom_id
                WHERE l_instructor = $fi
                ORDER BY l_start DESC
                LIMIT 1")
            or journalise($userId, "F", "Cannot read from $table_dto_flight for fi $fi: " . mysqli_error($mysqli_link)) ;
        $row = mysqli_fetch_array($result) ;
        if (! $row) return NULL ;
        $this->__construct($row) ;
    }

    function save() {
        global $mysqli_link, $userId, $table_dto_flight ;

        $remark = web2db(mysqli_real_escape_string($mysqli_link, $this->remark)) ;

        if ($this->id) { // Already in the DB, let's update it
            mysqli_query($mysqli_link, "UPDATE $table_dto_flight 
                SET df_remark = '$remark', df_type = '$this->flightType',
                    df_flight_log = $this->flightLog, df_student = $this->student,
                    df_session_grade = '$this->sessionGrade',
                    df_who = $userId, df_when = CURRENT_TIMESTAMP()
                WHERE df_id = $this->id")
                or journalise($userId, "F", "Cannot update flight $this->id: " . mysqli_error($mysqli_link)) ;
        } else { // Not yet in the DB, let's create it
            // Need to find next student flight id...
            if (!$this->student or !$this->flightLog) journalise($userId, "F", "Cannot save a flight without student and flightLog") ;
            $result = mysqli_query($mysqli_link, "SELECT MAX(df_student_flight) AS last_id, COUNT(*) AS count
                FROM $table_dto_flight WHERE df_student=$this->student") ;
            $row = mysqli_fetch_array($result) ;
            if ($row['count'] == 0)
                $this->flightId = 1 ;
            else 
                $this->flightId = 1 + $row['last_id'] ;
            mysqli_query($mysqli_link, "INSERT INTO $table_dto_flight(df_remark, df_type, df_flight_log, df_student, df_student_flight, df_session_grade, 
                    df_who, df_when)
                VALUES('$remark', '$this->flightType',
                    $this->flightLog, $this->student, $this->flightId, '$this->sessionGrade',
                    $userId, CURRENT_TIMESTAMP())")
                or journalise($userId, "F", "Cannot insert new flight: " . mysqli_error($mysqli_link)) ;
        }
        return mysqli_insert_id($mysqli_link) ;
    }
}

class Flights implements Iterator {
    public $studentId ;
    public $count ;
    private $result ;
    private $row ;

    function __construct ($studentId = NULL) {
        global $mysqli_link, $table_person, $table_dto_flight, $table_user_usergroup_map, $table_logbook, $userId ;

        if (!$studentId) return ;
        $this->studentId = $studentId ; 
        $sql = "SELECT *, DATE(l_start) AS date,
                s.last_name AS s_last_name, s.first_name AS s_first_name,
                fi.last_name AS fi_last_name, fi.first_name AS fi_first_name,
                who.last_name AS who_last_name, who.first_name AS who_first_name,
                60 * (l_end_hour - l_start_hour) + l_end_minute - l_start_minute AS duration
            FROM $table_dto_flight
                JOIN $table_person s ON df_student = s.jom_id
                JOIN $table_logbook ON df_flight_log = l_id
                LEFT JOIN $table_person fi ON l_instructor = fi.jom_id
                LEFT JOIN $table_person who ON df_who = who.jom_id
            WHERE df_student = $this->studentId
            ORDER BY df_student_flight" ;
        $this->result = mysqli_query($mysqli_link, $sql) 
                or journalise($userId, "F", "Erreur systeme a propos de l'access aux vols école de $this->studentId: " . mysqli_error($mysqli_link)) ;
        $this->count = mysqli_num_rows($this->result) ;
        $this->row = mysqli_fetch_assoc($this->result) ;
    }

    function getUnprocessedByFI($fiId) {
        global $mysqli_link, $table_person, $table_dto_flight, $table_user_usergroup_map, $table_logbook, $userId ;

        $this->studentId = NULL; 
        if ($this->result) mysqli_free_result($this->result) ;
        $sql = "SELECT *, DATE(l_start) AS date,
                s.last_name AS s_last_name, s.first_name AS s_first_name,
                fi.last_name AS fi_last_name, fi.first_name AS fi_first_name,
                who.last_name AS who_last_name, who.first_name AS who_first_name,
                60 * (l_end_hour - l_start_hour) + l_end_minute - l_start_minute AS duration
            FROM $table_dto_flight
                JOIN $table_person s ON df_student = s.jom_id
                JOIN $table_logbook ON df_flight_log = l_id
                LEFT JOIN $table_person fi ON l_instructor = fi.jom_id
                LEFT JOIN $table_person who ON df_who = who.jom_id
            WHERE l_instructor = $fiId AND df_when = l_audit_time
            ORDER BY df_when DESC" ;
 //           print("<hr><pref>$sql</pre><hr>") ;
        $this->result = mysqli_query($mysqli_link, $sql) 
                or journalise($userId, "F", "Erreur systeme a propos de l'access aux vols école via l'instructeur $fiId: " . mysqli_error($mysqli_link)) ;
        $this->count = mysqli_num_rows($this->result) ;
        $this->row = mysqli_fetch_assoc($this->result) ;
    }

    function __destruct() {
        mysqli_free_result($this->result) ;
    }

    public function current():mixed {
        return new Flight($this->row);
    }
    
    public function key():mixed {
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
    public $grading ;
    public $id ;

    function __construct($row) {
        $this->id = $row['de_id'] ;
        $this->reference = $row['de_ref'] ;
        $this->description = db2web($row['de_description']) ; // Even if only in English for now...
        $this->grading = ($row['de_grading'] != 0) ;
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

    public function current():mixed {
        return new Exercice($this->row);
    }
    
    public function key():mixed {
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
    public $grading ;

    function __construct($row = NULL) {
        if (! $row) return ;
        $this->id = $row['dse_flight'] ;
        $this->studentId = $row['df_student'] ;
        $this->studentFlight = $row['df_student_flight'] ;
        $this->reference = $row['de_ref'] ;
        $this->description = db2web($row['de_description']) ; // Even if only English for now
        $this->grade = [] ;
        if (strpos($row['grade'], 'demo') !== false)
            $this->grade['demo'] = 'demo' ;
        if (strpos($row['grade'], 'trained') !== false)
            $this->grade['trained'] = 'trained' ;
        if (strpos($row['grade'], 'acquired') !== false)
            $this->grade['acquired'] = 'acquired' ;
        if ($row['grade'] == 'yes') // For the single check-box, it is not a real SET
            $this->grade['yes'] = 'yes' ;
        $this->grading = ($row['de_grading'] != 0) ;
     }

    function getByFlightExercice($flightId, $exerciceId) {
        global $mysqli_link, $table_dto_student_exercice, $table_dto_flight, $table_dto_exercice, $userId ;

        $result = mysqli_query($mysqli_link, "SELECT *, dse_grade AS grade
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
            $row['grade'] = '' ;
        } ;
        $this->__construct($row) ;
    }

    function save() {
        global $mysqli_link, $userId, $table_dto_student_exercice ;

        $imploded_grade = implode(',', $this->grade) ;
        mysqli_query($mysqli_link, "INSERT INTO $table_dto_student_exercice(dse_flight, dse_exercice, dse_grade, dse_who, dse_when)
            VALUES($this->id, '" . mysqli_real_escape_string($mysqli_link, $this->reference) . "', '$imploded_grade', $userId, SYSDATE())
            ON DUPLICATE KEY UPDATE dse_flight=$this->id, dse_exercice='" . mysqli_real_escape_string($mysqli_link, $this->reference) . "',
                dse_grade='$imploded_grade', dse_who=$userId, dse_when=SYSDATE()")
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
            $sql = "SELECT *, dse_grade AS grade
            FROM $table_dto_exercice 
                LEFT JOIN $table_dto_flight ON df_student = $student AND df_id = $flight
                LEFT JOIN $table_dto_student_exercice ON dse_flight = df_id AND dse_exercice = de_ref
            ORDER BY de_id" ;
        else
            $sql = "SELECT dse_flight, df_student, df_student_flight, de_ref, de_description, de_grading, GROUP_CONCAT(dse_grade) AS grade
                FROM $table_dto_exercice 
                    LEFT JOIN $table_dto_flight ON df_student = $student
                    LEFT JOIN $table_dto_student_exercice ON dse_flight = df_id AND dse_exercice = de_ref
                GROUP BY de_id
                ORDER BY de_id" ;
        $this->result = mysqli_query($mysqli_link, $sql) 
                or journalise($userId, "F", "Erreur systeme a propos de l'access aux exercices école: " . mysqli_error($mysqli_link)) ;
        $this->count = mysqli_num_rows($this->result) ;
        $this->row = mysqli_fetch_assoc($this->result) ;
    }

    function __destruct() {
        mysqli_free_result($this->result) ;
    }

    public function current():mixed {
        return new StudentExercice($this->row);
    }
    
    public function key():mixed {
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

class StudentDocument {
    public $id ;
    public $studentId ;
    public $originalFilename ;
    public $originalMIMEType ;
    public $hashedFilename ;
    public $size ;
    public $when ;
    public $who ;
    public $whoFirstName ;
    public $whoLastName ;

    function __construct($row = NULL) {
        if (! $row) return ;
        $this->id = $row['da_id'] ;
        $this->studentId = $row['da_student'] ;
        $this->originalFilename = db2web($row['da_original_filename']) ; 
        $this->originalMIMEType = $row['da_original_type'] ;
        $this->hashedFilename = $row['da_hashed_filename'] ;
        $this->size = $row['da_file_size'] ;
        $this->when = $row['da_when'] ;
        $this->who = $row['da_who'] ;
        $this->whoFirstName = db2web($row['first_name']) ;
        $this->whoLastName = db2web($row['last_name']) ;
     }

    function getByHashedFilename($f) {
        global $mysqli_link, $table_dto_attachment, $table_person, $userId ;

        $sql = "SELECT *
                FROM $table_dto_attachment 
                    JOIN $table_person ON jom_id = da_who
                WHERE da_hashed_filename='$f'
                ORDER BY da_id" ;
        $result = mysqli_query($mysqli_link, $sql) 
                or journalise($userId, "F", "Erreur systeme a propos de l'access aux documents via $f: " . mysqli_error($mysqli_link)) ;
        $row = mysqli_fetch_array($result) ;
        $this->__construct($row) ;
    }

    function save() {
        global $mysqli_link, $userId, $table_dto_attachment ;

        if ($this->id)
            journalise($userId, "F", "Cannot save an existing document") ;
        $originalFilename = mysqli_real_escape_string($mysqli_link, web2db($this->originalFilename)) ;
        $originalMIMEType = mysqli_real_escape_string($mysqli_link, web2db($this->originalMIMEType)) ;
        mysqli_query($mysqli_link, "INSERT INTO $table_dto_attachment(da_student, da_original_filename, da_original_type, 
                da_file_size, da_hashed_filename, da_who, da_when)
            VALUES($this->studentId, '$originalFilename', '$originalMIMEType',
                $this->size, '$this->hashedFilename', $userId, SYSDATE())")
            or journalise($userId, "F", "Cannot update $table_dto_attachment for student $this->studentId $this->originalFilename: " . mysqli_error($mysqli_link)) ;
    }

    function delete() {
        global $mysqli_link, $userId, $table_dto_attachment ;

        if ($this->id) {
            mysqli_query($mysqli_link, "DELETE FROM $table_dto_attachment WHERE da_id = $this->id")
                or journalise($userId, "E", "Cannot remove entry for $this->id ($this->originalFilename):" . mysqli_error($mysqli_link)) ;
            unlink("dto_files/$this->hashedFilename") ;
            journalise($userId, "I", "File $this->originalFilename ($this->id for $this->studentId) deleted") ;
        }
    }
}

class StudentDocuments implements Iterator {
    public $count ;
    private $result ;
    private $row ;

    function __construct ($student) {
        global $mysqli_link, $table_dto_attachment, $table_person, $userId ;

        $sql = "SELECT *
                FROM $table_dto_attachment 
                    JOIN $table_person ON jom_id = da_who
                WHERE da_student = $student
                ORDER BY da_id" ;
        $this->result = mysqli_query($mysqli_link, $sql) 
                or journalise($userId, "F", "Erreur systeme a propos de l'access aux documents: " . mysqli_error($mysqli_link)) ;
        $this->count = mysqli_num_rows($this->result) ;
        $this->row = mysqli_fetch_assoc($this->result) ;
    }

    function __destruct() {
        mysqli_free_result($this->result) ;
    }

    public function current():mixed {
        return new StudentDocument($this->row);
    }
    
    public function key():mixed {
        return $this->row['da_id'];
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

// Remove a Flight from the LogId
function RemoveDTOFlight($logid) {
	global $mysqli_link, $userId, $table_dto_flight, $table_dto_student_exercice;
	
	// retrieve the FlightId associated to the logId
	$flightId=GetDTOFlightIdFromLogId($logid);
		
	if($flightId > 0) {
		// Remove all entries in the table_dto_strudent_exercice
		mysqli_query($mysqli_link, "delete from $table_dto_student_exercice where dse_flight=$flightId") or die("Cannot delete from table_dto_strudent_exercice: " . mysqli_error($mysqli_link)) ;
		
		mysqli_query($mysqli_link, "delete from $table_dto_flight where df_flight_log=$logid") or die("Cannot delete from table_dto_flight: " . mysqli_error($mysqli_link)) ;
		if (mysqli_affected_rows($mysqli_link) > 0) {
			$insert_message = "DTO Flight mis &agrave; jour" ;
			journalise($userId, 'I', "table_dto_flight entry deleted for LogId $logid.") ;
		}
	}
}

// Remove all Flight associated to a BookId
function RemoveAllDTOFlightBehindBooking($bookingid) {
	global $mysqli_link, $table_logbook ;
	$result = mysqli_query($mysqli_link, "select l_id
			from $table_logbook where l_booking = $bookingid")
		or die("Impossible de lire les entrees pour reservation $bookingid: " . mysqli_error($mysqli_link)) ;

	while ($row = mysqli_fetch_array($result)) {
			$logid=$row['l_id'];
			GetDTOFlightIdFromLogId($logid);
	}
}

// Returns FlightId from the LogId
function GetDTOFlightIdFromLogId($logid) {

	global $mysqli_link, $userId, $table_dto_flight ;
	$result=mysqli_query($mysqli_link,"SELECT df_id from $table_dto_flight where df_flight_log=$logid") or die("Cannot get df_id from table_dto_flight: " . mysqli_error($mysqli_link)) ;
    $row = mysqli_fetch_array($result) ;
	if($row!=NULL) {
		return $row['df_id'];	
	}
	return 0;
}
?>