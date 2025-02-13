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
    //print("AddATLIncident:Started: theLogId=$theLogId, thePlane=$thePlane, theSeverity=$theSeverity, theRemark=$theRemark<br>");
    if($theLogId==0) return false;
    if(!CheckIncidentCoherency($theLogId)){
        // The incident table is corrected!
        print("<p style=\"color: red;\"><b>ERROR:AddATLIncident($theLogId): Incident table corrected.</b></p><br>");
        //return false;
    }
    /*
    if(1) {
        $toto=GetATLIncidentID($theLogId);
         print("AddATLIncident:GetATLIncidentID($theLogId)=$toto<br>");
         if($toto=="") {
            print("AddATLIncident:GetATLIncidentID($theLogId)=$toto no string<br>");
         }
         if($toto==0) {
            print("AddATLIncident:GetATLIncidentID($theLogId)=$toto O<br>");
         }
         if($toto==NULL) {
            print("AddATLIncident:GetATLIncidentID($theLogId)=$toto NULL<br>");
         }
    }
         */
    $incidentID=GetATLIncidentID($theLogId);
    if($incidentID == 0) {
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
        else {
            return false;
        }
    }
    else {
          // An incident is already associated to the logid then edit the incident
            $incident = new Incident() ; 
            $incident->getByLogId($theLogId) ;
            $aPreviousPlane=$incident->plane;
            $aPreviousSeverity=$incident->severity;
            $aPreviousRemark=$incident->lastText;

            if($aPreviousPlane==$thePlane && $aPreviousSeverity==$theSeverity && $aPreviousRemark==$theRemark) {
                // nothing changed for the ATL. Nothing to do.
                //print("AddATLIncident: Same ATL nothing to do!<br>");
                return false;
            }
            if($theSeverity == "nothing") {
                //print("AddATLIncident: The pilot removes its ATL Log -> Close the Event!<br>");
                // The pilot removes its ATL Log -> Close the Event

                $event = new IncidentEvent() ;
                $event->incident = $incident ;
                $event->status = "closed";
                $event->text = "Event removed by the pilot. Nothing more to declare!";
                $event->save() ;
                return true;
            }
            if($aPreviousRemark==$theRemark) {
                // Same remark but severity or plane changed ! No new event
                //print("AddATLIncident: Same remark but serverity or plane changed!<br>");
                $incident->plane = $thePlane ;
                $incident->severity = $theSeverity ;
                $incident->save();
                return true;
            }
            else {
                //print("AddATLIncident: the remark has changed!<br>");
 
                // the remark has changed. Create a new event with the new remark. 
                // We are not allowed to update the event. Only create a new one.
                $incident->plane = $thePlane ;
                $incident->severity = $theSeverity ;
                $incident->save();

                $event = new IncidentEvent() ;
                $event->incident = $incident ;
                $event->status = "opened";
                $event->text = trim(web2db($theRemark));
                $event->save() ;
                return true;
            }
    }
    return false;
}

// Check if the two table rapcs_incident and rapcs_incident_history are coherent
function CheckIncidentCoherency($theLogid)
{
    global $userId, $mysqli_link, $table_incident, $table_incident_history ;

    $sql = "SELECT * FROM $table_incident WHERE i_log = $theLogid";
    $result = mysqli_query($mysqli_link, $sql)
        or  journalise($userId, "F", "Cannot retrieve incident by log id ($theLogid): " . mysqli_error($mysqli_link)) ;
    $row = mysqli_fetch_array($result) ;
    if ($row) {
        $id=$row['i_id'];
        $sql = "SELECT * FROM $table_incident_history WHERE ih_incident = $id";
        $result = mysqli_query($mysqli_link, $sql)
            or  journalise($userId, "F", "Cannot retrieve incident history by id ($id): " . mysqli_error($mysqli_link)) ;
            $row = mysqli_fetch_array($result) ;
        if(!$row) {
            // Incoherent: no history associated to an incident
            print("<p style=\"color: red;\"><b>ERROR:CheckIncidentCoherency($theLogid): No history associated to incident id=$id. Deleting this entry.</b></p><br>");
            $sql = "delete from $table_incident where i_log = $theLogid";
            mysqli_query($mysqli_link, $sql) or die("Cannot delete: " . mysqli_error($mysqli_link)) ;
            return false;
        }
    }
    return true;
}

// Retrieve the IncidentId associated to the logID
// Returns 0 if no incident associated to the loggID
function GetATLIncidentID($theLogid) {
    //print("GetATLIncidentID($theLogid)<br>");
    $incident = new Incident() ;
    $incident->getByLogId($theLogid) ;
    if($incident!=NULL) {
        //print("GetATLIncidentID($theLogid) incident.id=$incident->id<br>");
        if($incident->id==NULL) {
            return 0;
        }
        return $incident->id;
    }
    //print("GetATLIncidentID($theLogid) return 0<br>");
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
        return $incident->lastText;
    }
    return "";
}
// Returns true is the incident is closed
function IsATLIncidentClosed($theIncidentId) {
    
    $incident = new Incident() ;
    $incident->getById($theIncidentId) ;
    if($incident!=NULL) {
        $status=$incident->lastStatus;
        if($status=="closed") {
            return true;
        }
    }
    return false;
}
// Retrieve the description associated of all open incidend for all planes
// JSON: '{"ATL": [{"plane": "OO-ALD", "logs":["log11","log12"]},{"plane": "OO-JRB", "logs":["log21","log22","log23"]}]}'
function GetJSONIncidentByPlanes() {
    $planes = array('OO-ALD', 'OO-ALE', 'OO-APV', 'OO-JRB', 'OO-FMX', 'OO-SPQ', 'PH-AML') ;
    $jsonString='{"ATL": [';
    //loop on planes
    $planeCount=0;
    foreach($planes as $plane) {
         //$plane="OO-JRB";    
        $incidents = new Incidents($plane, ['opened', 'inprogressnoaog', 'inprogressaog', 'camonoaog', 'camoaog']) ;
        $incidentCount=0;
        foreach($incidents as $incident) {     
           $incidentCount++;
           if($incidentCount==1) {
                $planeCount++;
                if($planeCount>1) {
                 $jsonString.=',';
                }
                $jsonString.='{"plane": "'.$plane.'", "logs": [';
            }
            else {
                $jsonString.=',';  
            }
            $jsonString.='"'.CleanATLLog($incident->firstText).'"';
        }
        if($incidentCount>0) {
          $jsonString.=']}'; 
        }  
    }
    //end plane
    $jsonString.=']}';
    return $jsonString;
    //return '{"ATL": [{"plane": "OO-ALD", "logs":["log11","log12"]},{"plane": "OO-JRB", "logs":["log21","log22","log23"]}]}';
}

// Remove all characters incompatible with javascript
function CleanATLLog($logText) {
    $text=str_replace("'"," ",$logText);
    $text=str_replace('"'," ",$text);
    return $text;
}
// Check if a DTO flight is already associated to the logbook
function HasDTOFlight($theLogId) { 
    global $userId;
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

// Is a segment flight already introduced?
function IsSegmentAlreadyIntroduced($planeId,$startDayTime,$pilotId)
{
    global $userId;
    global $mysqli_link, $table_logbook;

    //print("IsSegmentAlreadyIntroduced:start $planeId , $startDayTime , $pilotId<br>");
    $result = mysqli_query($mysqli_link, "SELECT l_plane
    FROM $table_logbook 
    WHERE l_plane = '$planeId' AND l_start = '$startDayTime' AND l_pilot= $pilotId")
    or journalise($userId, "F", "Cannot read from $table_logbook for l_start = $startDayTime: " . mysqli_error($mysqli_link)) ;
    $row = mysqli_fetch_array($result) ;
    if (! $row) {
       // print("IsSegmentAlreadyIntroduced n existe pas<br>");
        return false ;
    }
    //print("IsSegmentAlreadyIntroduced existe<br>");
    return true;
}
?>