<?php
/*
   Copyright 2023-2024 Eric Vyncke

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

class IncidentEvent {
    public $eventId ;
    public $incident ;
    public $date;
    public $whoFirstName ;
    public $whoLastName ;
    public $text ;
    public $status ;
    public $statusFrench ;

    function __construct($row = NULL) {
        if ($row) {
            $this->eventId = $row['ih_id'] ;
//            $this->incident = new Incident($row) ; // This means that the row should have most of the Incident class properties... :-(
            $this->date = $row['ih_when'] ;
            $this->whoFirstName = db2web($row['first_name']) ;
            $this->whoLastName = db2web($row['last_name']) ;
            $this->text = db2web($row['ih_text']) ;
            $this->status = strtolower($row['ih_status']) ;
            switch($this->status) {
                case 'opened': $this->statusFrench = 'Ouvert' ; break ;
                case 'inprogressnoaog': $this->statusFrench = 'En progrès: Avion peut voler' ; break ;
                case 'inprogressaog': $this->statusFrench = 'En progrès: Avion au sol' ; break ;
                case 'camonoaog': $this->statusFrench = 'CAMO: Avion peut voler' ; break ;
                case 'camoaog': $this->statusFrench = 'CAMO: Avion au sol' ; break ;
                case 'closed': $this->statusFrench = 'Clôturé' ; break ;
                case 'duplicate': $this->statusFrench = 'Doublon' ; break ;
                case 'rejected': $this->statusFrench = 'Rejeté' ; break ;
                default: $this->statusFrench = $this->status ;
            }
        }
    }

    function getById($id) {
        global $mysqli_link, $table_incident, $table_incident_history, $table_person, $userId ;
        $result = mysqli_query($mysqli_link, "SELECT * 
                FROM $table_incident_history
                JOIN $table_incident ON ih_incident = i_id
                JOIN $table_person ON ih_who = jom_id 
                WHERE ih_id = $id")
            or journalise($userId, "F", "Cannot read from $table_incident_history for id: " . mysqli_error($mysqli_link)) ;
        $row = mysqli_fetch_array($result) ;
        if (! $row) return NULL ;
        $this->__construct($row) ;
    }

    function save() {
        global $mysqli_link, $table_incident_history, $userId ;
        if ($this->eventId) {
            journalise($userId, "F", "Saving an existing incident event is not allowed.") ;
        } else {
            $remark = mysqli_real_escape_string($mysqli_link, web2db($this->text)) ;
            $id = $this->incident->id ;
            $this->status = strtolower($this->status) ;
            mysqli_query($mysqli_link, "INSERT INTO $table_incident_history(ih_incident, ih_text, ih_status, ih_who, ih_when)
                VALUES($id, '$remark','$this->status', $userId, CURRENT_TIMESTAMP())")
                or journalise($userId, "F", "Cannot insert into $table_incident_history: " . mysqli_error($mysqli_link)) ;
            $this->eventId = mysqli_insert_id($mysqli_link) ;
            return $this->eventId ;
        } 
    }
}

class IncidentEvents implements Iterator {
    public $plane ;
    public $incidentId ;
    private $row ;
    private $count ;
    private $result ;

    function __construct ($incidentId) {
        global $mysqli_link, $table_incident_history, $table_incident, $table_person, $userId ;

        $this->incidentId = $incidentId ; 
        $sql = "SELECT * 
            FROM $table_incident_history
            JOIN $table_incident ON ih_incident = i_id
            JOIN $table_person ON ih_who = jom_id 
            WHERE i_id = $incidentId
            ORDER BY ih_when ASC" ;
        $this->result = mysqli_query($mysqli_link, $sql) 
                or journalise($userId, "F", "Erreur systeme a propos de l'access aux événements liés à l'incident $this->incidentId: " . mysqli_error($mysqli_link)) ;
        $this->count = mysqli_num_rows($this->result) ;
        $this->row = mysqli_fetch_assoc($this->result) ;
    }

    function __destruct() {
        mysqli_free_result($this->result) ;
    }

    public function current():mixed {
        return new IncidentEvent($this->row);
    }
    
    public function key():mixed {
        return $this->row['ih_id'];
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


class Incident {
    public $id ;
    public $plane ;
    public $planeType ;
    public $logId ; 
    public $severity ;
    public $severityFrench ;
    public $firstId ;
    public $firstDate ;
    public $firstWho ;
    public $firstFirstName ;
    public $firstLastName ;
    public $firstStatus;
    public $firstStatusFrench ;
    public $firstText ;
    public $lastId ;
    public $lastDate ;
    public $lastWho ;
    public $lastFirstName ;
    public $lastLastName ;
    public $lastStatus; // Somehow useless as all first states are 'opened'....
    public $lastStatusFrench ;
    public $lastText ;
    public $daysPending ;

    function __construct($row = NULL) {
        if ($row) {
            $this->id = $row['i_id'] ;
            $this->plane = strtoupper($row['i_plane']) ;
            $this->planeType = strtoupper($row['model']) ;
            $this->logId = $row['i_log'] ;
            $this->severityFrench = db2web(strtolower($row['i_severity'])) ;
            switch(strtolower($row['i_severity'])) {
                case 'nohazard': 
                case 'hazard':
                    $this->severity = strtolower($row['i_severity']) ; break ;
                default: $this->severity = 'unknown/unsupported' ;
            }
            $this->firstId = $row['first_id'] ;
            $this->firstDate = $row['first_when'] ;
            $this->firstWho = $row['first_who'] ;
            $this->firstFirstName = db2web($row['first_first_name']) ;
            $this->firstLastName = db2web($row['first_last_name']) ;
            $this->firstStatus = strtolower($row['first_status']) ;
            switch($this->firstStatus) {
                case 'opened': $this->firstStatusFrench = 'Ouvert' ; break ;
                case 'camook': $this->firstStatusFrench = 'CAMO: l\'avion peut voler' ; break ;
                case 'accepted': $this->firstStatusFrench = 'Accepté' ; break ;
                case 'inprogress': $this->firstStatusFrench = 'En progrès' ; break ;
                case 'closed': $this->firstStatusFrench = 'Clôturé' ; break ;
                case 'duplicate': $this->firstStatusFrench = 'Doublon' ; break ;
                case 'rejected': $this->firstStatusFrench = 'Rejeté' ; break ;
                default: $this->lastStatusFrench = $this->firstStatus ;
            }
            $this->firstText = db2web($row['first_text']) ;
            $this->lastId = $row['last_id'] ;
            $this->lastDate = $row['last_when'] ;
            $this->lastWho = $row['last_who'] ;
            $this->lastFirstName = db2web($row['last_first_name']) ;
            $this->lastLastName = db2web($row['last_last_name']) ;
            $this->lastStatus = strtolower($row['last_status']) ;
            switch($this->lastStatus) {
                case 'opened': $this->lastStatusFrench = 'Ouvert' ; break ;
                case 'camook':
                case 'camonoaog': 
                    $this->firstStatusFrench = 'CAMO: l\'avion peut voler' ; $this->severity = 'nohazard' ; break ;
                case 'camoaog': $this->firstStatusFrench = 'CAMO: l\'avion ne peut pas voler' ; $this->severity = 'hazard' ; break ;
                case 'accepted': $this->lastStatusFrench = 'Accepté' ; break ;
                case 'inprogress': $this->lastStatusFrench = 'En progrès' ; break ;
                case 'inprogressnoaog': $this->lastStatusFrench = 'En progrès: l\'avion peut voler' ;  $this->severity = 'nohazard' ; break ;
                case 'inprogressaog': $this->lastStatusFrench = 'En progrès: l\'avion ne peut pas voler' ;  $this->severity = 'hazard' ; break ;
                case 'closed': $this->lastStatusFrench = 'Clôturé' ; break ;
                case 'duplicate': $this->lastStatusFrench = 'Doublon' ; break ;
                case 'rejected': $this->lastStatusFrench = 'Rejeté' ; break ;
                default: $this->lastStatusFrench = $this->lastStatus ;
            }
            $this->lastText = db2web($row['last_text']) ;
            $this->daysPending = $row['days_pending'] ;
        }
    }

    function getById($id) {
        global $userId, $mysqli_link, $table_incident, $table_incident_history, $table_person, $table_planes ;
        
        $sql = "SELECT *, DATEDIFF(CURRENT_TIMESTAMP(), fe.ih_when) AS days_pending, i.i_log,
                fe.ih_id AS first_id, fe.ih_when AS first_when, fe.ih_text AS first_text, fe.ih_status AS first_status, fe.ih_who AS first_who, fep.first_name AS first_first_name, fep.last_name AS first_last_name,
                le.ih_id AS last_id, le.ih_when AS last_when, le.ih_text AS last_text, le.ih_status AS last_status, le.ih_who AS last_who, lep.first_name AS last_first_name, lep.last_name AS last_last_name
            FROM $table_incident AS i
            JOIN $table_planes AS p ON p.id = i.i_plane
            JOIN $table_incident_history AS fe ON fe.ih_incident = i.i_id
            JOIN $table_person AS fep ON fep.jom_id = fe.ih_who
            JOIN $table_incident_history AS le ON le.ih_incident = i.i_id
            JOIN $table_person AS lep ON lep.jom_id = le.ih_who
            WHERE i.i_id = $id AND
                fe.ih_id = (SELECT MIN(h.ih_id) FROM $table_incident_history AS h WHERE h.ih_incident = i.i_id) AND
                le.ih_id = (SELECT MAX(h.ih_id) FROM $table_incident_history AS h WHERE h.ih_incident = i.i_id)
            " ;
        
        $result = mysqli_query($mysqli_link, $sql)
            or  journalise($userId, "F", "Cannot retrieve incident by id ($id): " . mysqli_error($mysqli_link)) ;
        $row = mysqli_fetch_array($result) ;
        if (! $row) return NULL ;
        $this->__construct($row) ;
    }

    function getByLogId($logId) {
        global $userId, $mysqli_link, $table_incident, $table_incident_history, $table_person, $table_planes ;

        $sql = "SELECT *, DATEDIFF(CURRENT_TIMESTAMP(), fe.ih_when) AS days_pending, i.i_log,
                fe.ih_id AS first_id, fe.ih_when AS first_when, fe.ih_text AS first_text, fe.ih_status AS first_status, fe.ih_who AS first_who, fep.first_name AS first_first_name, fep.last_name AS first_last_name,
                le.ih_id AS last_id, le.ih_when AS last_when, le.ih_text AS last_text, le.ih_status AS last_status, le.ih_who AS last_who, lep.first_name AS last_first_name, lep.last_name AS last_last_name
            FROM $table_incident AS i
            JOIN $table_planes as p ON p.id = i.i_plane
            JOIN $table_incident_history AS fe ON fe.ih_incident = i.i_id
            JOIN $table_person AS fep ON fep.jom_id = fe.ih_who
            JOIN $table_incident_history AS le ON le.ih_incident = i.i_id
            JOIN $table_person AS lep ON lep.jom_id = le.ih_who
            WHERE i.i_log = $logId AND
                fe.ih_id = (SELECT MIN(h.ih_id) FROM $table_incident_history AS h WHERE h.ih_incident = i.i_id) AND
                le.ih_id = (SELECT MAX(h.ih_id) FROM $table_incident_history AS h WHERE h.ih_incident = i.i_id)
            " ;
        $result = mysqli_query($mysqli_link, $sql)
            or  journalise($userId, "F", "Cannot retrieve incident by log id ($logId): " . mysqli_error($mysqli_link)) ;
        $row = mysqli_fetch_array($result) ;
        if (! $row) return NULL ;
        $this->__construct($row) ;
    }

    function save() {
        global $mysqli_link, $table_incident, $userId ;
        $severity = mysqli_real_escape_string($mysqli_link, web2db($this->severity)) ;
        $logId = ($this->logId > 0) ? $this->logId : 'NULL' ;
        if ($this->id) {
             mysqli_query($mysqli_link, "UPDATE $table_incident
                SET i_plane = '$this->plane', i_severity = '$severity', i_log = $logId
                    WHERE i_id = $this->id")
                or journalise($userId, "F", "Cannot update $table_incident: " . mysqli_error($mysqli_link)) ;
            journalise($userId, "I", "Incident $this->id updated: severity = $severity") ;
            return $this->id ;
        } else {
             mysqli_query($mysqli_link, "INSERT INTO $table_incident(i_plane, i_severity, i_log)
                VALUES('$this->plane', '$severity', $logId)")
                or journalise($userId, "F", "Cannot insert into $table_incident: " . mysqli_error($mysqli_link)) ;
            $this->id = mysqli_insert_id($mysqli_link) ;
            journalise($userId, "I", "Incident #$this->id created: severity = $severity") ;
            return $this->id ;
        } 
    }
}

class Incidents implements Iterator {
    public $plane ;
    public $planeType ;
    private $row ;
    private $count ;
    private $result ;

    function __construct ($plane = NULL, $status = NULL) {
        global $mysqli_link, $table_incident_history, $table_incident, $table_person, $table_planes, $userId ;

        if ($plane == NULL) 
            $planeCondition = '' ;
        else 
            $planeCondition = " AND i_plane = '$plane' " ;
        $this->plane = $plane ;
        if ($status == NULL)
            $statusCondition = '' ;
        else
            $statusCondition = " AND le.ih_status IN ('" . implode("','", $status) . "') " ;
        $sql = "SELECT *, DATEDIFF(CURRENT_TIMESTAMP(), fe.ih_when) AS days_pending, i.i_log,
                fe.ih_id AS first_id, DATE(fe.ih_when) AS first_when, fe.ih_text AS first_text, fe.ih_status AS first_status, fe.ih_who AS first_who, fep.first_name AS first_first_name, fep.last_name AS first_last_name,
                le.ih_id AS last_id, DATE(le.ih_when) AS last_when, le.ih_text AS last_text, le.ih_status AS last_status, le.ih_who AS last_who, lep.first_name AS last_first_name, lep.last_name AS last_last_name
            FROM $table_incident AS i
            JOIN $table_planes AS p ON i.i_plane = p.id
            JOIN $table_incident_history AS fe ON fe.ih_incident = i.i_id
            JOIN $table_person AS fep ON fep.jom_id = fe.ih_who
            JOIN $table_incident_history AS le ON le.ih_incident = i.i_id
            JOIN $table_person AS lep ON lep.jom_id = le.ih_who
            WHERE
                fe.ih_id = (SELECT MIN(h.ih_id) FROM $table_incident_history AS h WHERE h.ih_incident = i.i_id) AND
                le.ih_id = (SELECT MAX(h.ih_id) FROM $table_incident_history AS h WHERE h.ih_incident = i.i_id)
                $planeCondition $statusCondition
            ORDER BY fe.ih_when DESC" ;
        $this->result = mysqli_query($mysqli_link, $sql) 
                or journalise($userId, "F", "Erreur systeme à propos de l'access aux incidents $table_incident pour l'avion $this->plane: " . mysqli_error($mysqli_link)) ;
        $this->count = mysqli_num_rows($this->result) ;
        $this->row = mysqli_fetch_assoc($this->result) ;
    }

    function __destruct() {
        mysqli_free_result($this->result) ;
    }

    public function current():mixed {
        return new Incident($this->row);
    }
    
    public function key():mixed {
        return $this->row['i_id'];
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