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

class IncidentEvent {
    public $eventId ;
    public $incident ;
    public $date;
    public $whoFirstName ;
    public $whoLastName ;
    public $text ;
    public $status ;

    function __construct($row = NULL) {
        if ($row) {
            $this->eventId = $row['ih_id'] ;
            $this->incident = new Incident($row) ;
            $this->date = $row['ih_when'] ;
            $this->whoFirstName = $row['first_name'] ;
            $this->whoLastName = $row['last_name'] ;
            $this->text = db2web($row['ih_text']) ;
            $this->status = $row['ih_status'] ;
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
            $remark = web2db($this->text) ;
            $id = $this->incident->id ;
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
            WHERE ih_id = $incidentId" ;
        $this->result = mysqli_query($mysqli_link, $sql) 
                or journalise($userId, "F", "Erreur systeme a propos de l'access aux événements liés à l'incident $this->incidentId: " . mysqli_error($mysqli_link)) ;
        $this->count = mysqli_num_rows($this->result) ;
        $this->row = mysqli_fetch_assoc($this->result) ;
    }

    function __destruct() {
        mysqli_free_result($this->result) ;
    }

    public function current() {
        return new IncidentEvent($this->row);
    }
    
    public function key() {
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
    public $importance ;
    public $lastDate ;
    public $lastWho ;
    public $lastFirstName ;
    public $lastLastName ;
    public $lastStatus;
    public $lastText ;

    function __construct($row = NULL) {
        if ($row) {
            $this->id = $row['i_id'] ;
            $this->plane = $row['i_plane'] ;
            $this->importance = db2web($row['i_importance']) ;
            $this->lastDate = $row['last_when'] ;
            $this->lastWho = $row['last_who'] ;
            $this->lastFirstName = db2web($row['last_first_name']) ;
            $this->lastLastName = db2web($row['last_last_name']) ;
            $this->lastStatus = $row['last_status'] ;
            $this->lastText = db2web($row['last_text']) ;
        }
    }

    function save() {
        global $mysqli_link, $table_incident, $userId ;

        $importance = web2db($this->importance) ;
        if ($this->id) {
            mysqli_query($mysqli_link, "REPLACE INTO $table_incident(i_id, i_plane, i_importance)
                VALUES($this->id, '$this->plane', '$importance')")
                or journalise($userId, "F", "Cannot replace into $table_incident: " . mysqli_error($mysqli_link)) ;
            return $this->id ;
        } else {
            mysqli_query($mysqli_link, "INSERT INTO $table_incident(i_plane, i_importance)
                VALUES('$this->plane', '$importance')")
                or journalise($userId, "F", "Cannot insert into $table_incident: " . mysqli_error($mysqli_link)) ;
            $this->id = mysqli_insert_id($mysqli_link) ;
            return $this->id ;
        } 
    }
}

class Incidents implements Iterator {
    public $plane ;
    private $row ;
    private $count ;
    private $result ;

    function __construct ($plane = NULL, $status = NULL) {
        global $mysqli_link, $table_incident_history, $table_incident, $table_person, $userId ;

        if ($plane == NULL) 
            $planeCondition = '' ;
        else 
            $planeCondition = " AND i_plane = '$plane'" ;
        $this->plane = $plane ;
        $sql = "SELECT *, 
                le.ih_when AS last_when, le.ih_text AS last_text, le.ih_status AS last_status, le.ih_who AS last_who, lep.first_name AS last_first_name, lep.last_name AS last_last_name
            FROM $table_incident AS i
            JOIN $table_incident_history AS le ON le.ih_incident = i.i_id
            JOIN $table_person AS lep ON lep.jom_id = le.ih_who
            WHERE le.ih_id = (SELECT MAX(h.ih_id) FROM $table_incident_history AS h WHERE h.ih_incident = i.i_id)
                $planeCondition
            ORDER BY le.ih_when DESC" ;
        $this->result = mysqli_query($mysqli_link, $sql) 
                or journalise($userId, "F", "Erreur systeme à propos de l'access aux incidents $table_incident pour l'avion $this->plane: " . mysqli_error($mysqli_link)) ;
        $this->count = mysqli_num_rows($this->result) ;
        $this->row = mysqli_fetch_assoc($this->result) ;
    }

    function __destruct() {
        mysqli_free_result($this->result) ;
    }

    public function current() {
        return new Incident($this->row);
    }
    
    public function key() {
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