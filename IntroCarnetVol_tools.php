<?php
require_once 'incident.class.php' ;

function GetCompteurHour($Compteur) {
	$aPos=strpos($Compteur,".");
	if(!$aPos) {
		return NULL;
	}
	return substr($Compteur,0,$aPos);
}

function GetCompteurMinute($Compteur) {
	$aPos=strpos($Compteur,".");
	if(!$aPos) {
		return NULL;
	}
	return substr($Compteur,$aPos+1,strlen($Compteur)-$aPos-1);
}

function GetDayTime($Date, $Time) {
	return $Date." ".$Time;
}

function CheckVar($var) {
	if(!isset($var)) {
		$var='NULL';
	}
	else if(empty($var)) {
		$var='NULL';
	}
	return $var;
}
// Compute the remark associated to the CP
function GetFullRemarks( $theFraisCP,  $thePAX, $theRemarque, $theFraisDC) {
	$aRemark="";
	if($theFraisCP != "") {
		$aRemark = $theFraisCP;
		if($thePAX == -1) {
			$aRemark = $aRemark . " Ferry";				
		}
		else if($thePAX == -2) {
			$aRemark = $aRemark . " Club";				
		}
		else if($thePAX == -3) {
			$aRemark = $aRemark . " Initiation";				
		}
		else if($thePAX == -4) {
			$aRemark = $aRemark . " Vol IF";				
		}
		else if($thePAX == -5) {
			$aRemark = $aRemark . " Vol membre";				
		}
		else if($thePAX == -6) {
			$aRemark = $aRemark . " Vol D.H.F.";				
		}
		else if($thePAX == -7) {
			$aRemark = $aRemark . " Vol PR";				
		}
		else if($thePAX == -8) {
			$aRemark = $aRemark . " Mécano";				
		}
		else  {
			$aRemark = $aRemark . " PAX ".strval($thePAX);				
		}
	}
	
	if($theRemarque != "") {
		if($aRemark != "") {
			$aRemark = $aRemark." ";
		}
		$aRemark = $aRemark.$theRemarque;
	}
	if($theFraisDC != "DC") {
		if($aRemark != "") {
			$aRemark = $aRemark." ";
		}
		$aRemark = $aRemark.$theFraisDC;
	}
	return $aRemark;
}

// Add an incident in the Aircraft Technical Log 
function AddATLIncident($theLogId, $thePlane, $theSeverity, $theRemark) {
    global $mysqli_link, $table_incident_history, $userId ;

    if(GetATLIncidentID($theLogId) == 0) {
        if($theSeverity != "nothing") {
            // No incident associated to the logid $$ severity = hazard or nohazard
            $incident = new Incident() ;
            $incident->logId = $theLogId;
            $incident->plane = $thePlane ;
            $incident->severity = $theSeverity ;
            $incident->save() ;
            $event = new IncidentEvent() ;
            $event->incident = $incident ;
            $event->status = 'opened' ;
            $event->text = $theRemark ;
            $event->save() ;
            return true;
        }
    }
    else {
          // An incident is already associated to the logid then edit the incident
          $incident = new Incident() ; 
          $incident->getByLogId($theLogId) ;
          $incident->plane = $thePlane ;
          $aPreviousSeverity=$incident->severity;
          $incident->severity = $theSeverity ;
          $firstId=$incident->firstId;
          $incident->save() ;
          
          $event = new IncidentEvent() ;
          $event->getById($firstId) ;
          $status=$event->status;
          if($theSeverity != "nothing") {
              $event->text = $theRemark ;
          }
          else {
              $aPreviousRemark=$event->text;
              $event->text = "Event removed by the pilot: ".$aPreviousRemark." (".$aPreviousSeverity.")";
              $status="closed";
          }
          //$event->save() ;
          $text=web2db($event->text);
          mysqli_query($mysqli_link, "UPDATE $table_incident_history
             SET ih_text = '$text', ih_status = '$status', ih_who = $userId, ih_when=CURRENT_TIMESTAMP()
                 WHERE ih_id = $firstId")
             or journalise($userId, "F", "Cannot update $table_incident_history: " . mysqli_error($mysqli_link)) ;
          
          return true;
    }
    return false;
}

// Retrieve the IncidentId associated to the logID
// Returns 0 if no incident associated to the loggID
function GetATLIncidentID($theLogid) {
    $incident = new Incident() ;
    $incident->getByLogId($theLogid) ;
    if($incident!=NULL) {
        return $incident->id;
    }
    return 0;
}

// Retrieve the severity associated to an incidentId
function GetATLIncidentSeverity($theIncidentId) {
    
    $incident = new Incident() ;
    $incident->getById($theIncidentId) ;
    if($incident!=NULL) {
        return $incident->severity;
    }
    return "select";
}
// Retrieve the description associated to an incidentId
function GetATLIncidentDescription($theIncidentId) {
    
    $incident = new Incident() ;
    $incident->getById($theIncidentId) ;
    if($incident!=NULL) {
        return $incident->firstText;
    }
    return "";
}

// Check if a DTO flight is already associated to the logbook
function HasDTOFlight($theLogId) { 
    global $mysqli_link, $table_dto_flight;
    //print("PRE HasDTOFlight: theLogId=$theLogId<br>");
    if(!isset($theLogId)) {
        //print("PRE1 HasDTOFlight: theLogId=$theLogId<br>");
        return true;
    }
    $result = mysqli_query($mysqli_link, "SELECT *
            FROM $table_dto_flight 
            WHERE df_flight_log = $theLogId")
        or journalise($userId, "F", "Cannot read from $table_dto_flight for df_flight_log $theLogId: " . mysqli_error($mysqli_link)) ;
    $row = mysqli_fetch_array($result) ;
    if (! $row) {
        //print("PRE2 HasDTOFlight: theLogId=$theLogId<br>");
        return false ;
    }
    //print("PRE3 HasDTOFlight: theLogId=$theLogId<br>");
    return true;
}

// Returns true is the thePilotID is a student
function IsStudent($thePilotID) {
    global $mysqli_link, $table_user_usergroup_map,$joomla_student_group;
	$studentResult=mysqli_query($mysqli_link,"SELECT user_id FROM $table_user_usergroup_map WHERE user_id = '$thePilotID' and group_id='$joomla_student_group';") or die("Impossible de retrouver le user_id dans table_user_usergroup_map: " . mysqli_error($mysqli_link)) ;
	if ($studentResult->num_rows != 0) {
        return true;
    }
    return false;
}
?>