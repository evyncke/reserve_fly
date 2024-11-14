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

require_once "dbi.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}
$header_postamble ='
<style>
	.tooltip {
	  position: relative;
	  display: inline-block;
	  border-bottom: 1px dotted black;
	  opacity: 1;
	}

	.tooltip .tooltiptext {
	  visibility: hidden;
	  width: 120px;
	  background-color: #555;
	  color: #fff;
	  text-align: center;
	  border-radius: 6px;
	  padding: 5px 0;
	  position: absolute;
	  z-index: 1;
	  bottom: 125%;
	  left: 50%;
	  margin-left: -60px;
	  opacity: 0;
	  transition: opacity 0.3s;
	}

	.tooltip .tooltiptext::after {
	  content: "";
	  position: absolute;
	  top: 100%;
	  left: 50%;
	  margin-left: -5px;
	  border-width: 5px;
	  border-style: solid;
	  border-color: #555 transparent transparent transparent;
	}

	.tooltip:hover .tooltiptext {
	  visibility: visible;
	  opacity: 1;
	}
	</style>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
' ;
require_once 'mobile_header5.php' ;
require_once 'incident.class.php' ;
require_once __DIR__.'/mobile_tools.php' ;

$maxFileSizeString=MemoryToString($atl_maxFileSize);

if (isset($_REQUEST['incident']) and $_REQUEST['incident'] != '' and is_numeric($_REQUEST['incident'])) {
    $incident_id = trim($_REQUEST['incident']) ;
    $incident = new Incident() ;
    $incident->getById($incident_id) ;
} else {
    if(isset($_REQUEST['incident'])) {
        journalise($userId, "F", "Bad value fir  parameter incident='$incident'") ;
    }
    else {
        journalise($userId, "F", "missing parameter incident") ;
    }
}       
 

if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'add' and isset($_REQUEST['status']) and $_REQUEST['status'] != '' and isset($_REQUEST['remark']) and $_REQUEST['remark'] != '') {
    if (! ($userIsBoardMember or $userIsInstructor or $userIsMechanic))
        journalise($userId, "F", "You do not have the permission for this action") ;
    $event = new IncidentEvent() ;
    $event->incident = $incident ;
    switch ($_REQUEST['status']) {
        case 'opened':
        case 'inprogressnoaog':
        case 'inprogressaog':
        case 'camonoaog':
        case 'camoaog':
        case 'closed':
        case 'duplicate':
        case 'rejected': $event->status = $_REQUEST['status'] ; break ;
        default: journalise($userId, "F", "Missing or wrong value for status=$_REQUEST[staus]") ;
    } ;
    $event->text = trim($_REQUEST['remark']) ;
    $event->save() ;
}

if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'addFiles' ) {
    if(CheckFileSize($_FILES, "associatedATLFiles",  $atl_maxFileSize)) {
        $ATLId=$_REQUEST['incident'];
        //print("Action addfile: $ATLId");
        UploadFiles($_FILES, "associatedATLFiles", $atl_uploadfiles_path,GetATLPrefixName($ATLId));
    }
    else {
        $maxFileSizeString=MemoryToString($atl_maxFileSize);
        print("<h1 style=\"color: red;\"> An uploaded file is too big ! Max Size=$maxFileSizeString.<br>No file uploaded</h1>");
    }
}
if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'deleteFile' ) {
    $ATLId=$_REQUEST['incident']; 
    $fileName=$_REQUEST['file'];
    //print("Action deletefile: incident $ATLId file=$fileName");
    if(DeleteUploadedFile($atl_uploadfiles_path,GetATLPrefixName($ATLId),$fileName)) {
        print("<span style=\"color: red;\"> The file $fileName is deleted !</span><br>");
    }
}
?>

<h2><?=$incident->plane?> Aircraft Technical Log entry#<?=$incident_id?></h2>

<p>Severity: <b><?=$incident->severity?></b></p>

<h3>History</h3>
<div class="row">
<div class="col-sm-12 col-md-12 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<tr><th>Date</th><th>Status</th><th>Description</th><th>By</th></tr>
</thead>
<tbody class="table-group-divider">

<?php
    $events = new IncidentEvents($incident_id) ;
    foreach($events as $event) {
        print("<tr>
            <td>$event->date</td>
            <td>$event->statusFrench</td>
            <td>$event->text</td>
            <td><b>$event->whoLastName</b> $event->whoFirstName</td>
            </tr>\n") ;
    }
?>
</tbody>
</table>
</div><!-- table responsive -->
</div><!-- col -->
</div><!-- row --> 

<?php
if ($userIsBoardMember or $userIsInstructor or $userIsMechanic) {
?>
<hr>
<p></p>
<h3>Change state of an aircraft technical log entry</h3>

<p class="lead">This function is only available to fleet managers, CAMO, mechanics, and FIs.</p>

<div class="row">
<form action="<?=$_SERVER['PHP_SELF']?>" method="post" role="form" class="form-horizontal" enctype="multipart/form-data">
<input type="hidden" name="incident" value="<?=$incident_id?>">
<div class="row mb-3">
	<label for="statusSelect" class="col-form-label col-sm-4 col-md-2">New status:</label>
	<div class="col-sm-4 col-md-3">
        <select id="statusSelect" class="form-select" name="status" onchange="selectSeverity();">
            <option value="select" 'selected'>Select a status</option>
            <option value="opened">Opened</option>
            <option value="inprogressnoaog">In progress NO AOG</option>
            <option value="inprogressaog">In progress AOG</option>
            <option value="camonoaog">CAMO: Plane can be flown</option>
            <option value="camoaog">CAMO: Plane AOG</option>
            <option value="closed">Closed</option>
            <option value="duplicate">Duplicate</option>
            <option value="rejected">Rejected</option>
        </select>
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="remarkId" class="col-form-label col-sm-4 col-md-2">Description:</label>
	<div class="col-sm-12 col-md-6">
		<input type="text" class="form-control" name="remark" id="remarkId" placeholder="Short description of the action/question/answer" onchange="selectSeverity();">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
        <button type="submit" name="action" id="id_addentry" value="add" class="col-sm-offset-2 col-md-offset-1 col-sm-3 col-md-2 btn btn-primary" disabled>Add techlog entry
        </button></div>
</form>
</div><!-- row -->
<script type="text/javascript">       
    function selectSeverity() {
        var remark=document.getElementById("remarkId").value;
        if(remark=="") {
            document.getElementById("id_addentry").disabled=true;
            return;
        }        
		var status=document.getElementById("statusSelect").value;
        if(status=="select") {
            document.getElementById("id_addentry").disabled=true;
            return;
        }
        document.getElementById("id_addentry").disabled=false;            
    }
</script>
<hr>
<p></p>

<?php
    $hasUploadedFile=false;
    if(HasUploadedFiles($atl_uploadfiles_path, GetATLPrefixName($incident_id))) {
       $uploadedFileNames=GetUploadedFileNames($atl_uploadfiles_path, GetATLPrefixName($incident_id)) ;
       $totalFileSize=0;
       foreach ($uploadedFileNames as $fileName) {
            $fileInfo=GetUploadedFileInfo($atl_uploadfiles_path,GetATLPrefixName($incident_id), $fileName);
            $fileSize=$fileInfo["size"];
            $totalFileSize+=$fileSize;
            $hasUploadedFile=true;
        }
        $totalFileSizeString=MemoryToString($totalFileSize);
        print("<h3>Associated Files</h3>(Used Space=$totalFileSizeString)<br>");
    }
    else {
     print("<h3>No Associated Files</h3>");
    }
?>

<div class="row">
<div class="col-sm-12 col-md-12 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<?php
    if($hasUploadedFile) {
        print("<tr style=\"text-align: Center;\"><th>Preview</th><th>Name</th><th>Type</th><th>Date</th><th>Size</th><th>Action</th></tr>");
    }
?>
</thead>
<tbody class="table-group-divider">
<?php
    if($hasUploadedFile) {
       $uploadedFileNames=GetUploadedFileNames($atl_uploadfiles_path, GetATLPrefixName($incident_id)) ;
       foreach ($uploadedFileNames as $fileName) {
            $fileInfo=GetUploadedFileInfo($atl_uploadfiles_path,GetATLPrefixName($incident_id), $fileName);
            $filePath=$fileInfo["path"];
            $fileName=$fileInfo["name"];
            $fileSize=MemoryToString($fileInfo["size"]);
            $fileDate=$fileInfo["date"];
            $fileType=$fileInfo["type"];
            print("<tr>");
            if(IsPictureFile($fileInfo["extension"])) {
                print("<td style=\"text-align: Center;\"><a href=\"$filePath\"><img src=\"$filePath\" width=\"80\" ></a></td>");
            }
            else if($fileInfo["extension"]=="mp4") {
                print("<td style=\"text-align: Center;\"><a href=\"$filePath\"><video controls=\"\" muted name=\"media\" width=\"60\"><source src=\"$filePath\" type=\"video/mp4\"></video></a></td>");
            }
            else if($fileInfo["extension"]=="pdf") {
                print("<td style=\"text-align: Center;\"><a href=\"$filePath\"><i class=\"bi bi-file-earmark-pdf\" style=\"font-size:36px;\"></i></a></td>");
            }
            else {
                print("<td style=\"text-align: Center;\"><a href=\"$filePath\"><i class=\"bi bi-file-earmark\" style=\"font-size:36px;\"></i></a></td>");
            }
           print("<td><a href=\"$filePath\">$fileName</a></td>
           <td>$fileType</td>
           <td>$fileDate</td>
           <td>$fileSize</td>
           <td><a class=\"tooltip\" href=\"$_SERVER[PHP_SELF]?action=deleteFile&incident=$incident_id&file=$fileName\">&#128465;<span class='tooltiptext'>Click pour supprimer le fichier</span></a></td>
           </tr>\n") ; 
        }
   }
?>
</tbody>
</table>
</div><!-- table responsive -->
</div><!-- col -->
</div><!-- row --> 
<p></p>
<h3>Add Some associated files to an aircraft technical log entry</h3>
<div class="row">
<form action="<?=$_SERVER['PHP_SELF']?>" method="post" role="form" class="form-horizontal" enctype="multipart/form-data">
<input type="hidden" name="incident" value="<?=$incident_id?>">
<div class="row mb-3">
	<label for="associatedATLFilesId" class="col-form-label col-sm-4 col-md-2">Associated Files :<br>(Max: <?php print($maxFileSizeString);?>)</label>
	<div class="col-sm-12 col-md-6">
        <!---<input type="hidden" name="MAX_FILE_SIZE" value="3000000" />-->
        <input type="file" multiple id="associatedATLFilesId" name="associatedATLFiles[]" onchange="selectFiles();"/>
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
        <button type="submit" name="action" id="id_addAssociatedFiles" disabled value="addFiles" class="col-sm-offset-2 col-md-offset-1 col-sm-3 col-md-2 btn btn-primary" >Add Associated Files
        </button></div>
</form>
</div><!-- row -->
<script type="text/javascript">       
    function selectFiles() {
        var files=document.getElementById("id_addAssociatedFiles").value;
        if(files=="") {
            document.getElementById("id_addAssociatedFiles").disabled=true;
            return;
        }        
        document.getElementById("id_addAssociatedFiles").disabled=false;            
    }
</script>
<?php
} // if ($userIsBoardMember or $userIsInstructor or $userIsMechanic)
?>
<div class="row mb-3">
<p><input class="button" type="button" value="Back to all ATL" onclick="javascript:document.location.href='mobile_incidents.php';"></input>
</div>
</body>
</html>