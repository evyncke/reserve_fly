<?php
// Failing on https://www.spa-aviation.be/resa/mobile_incidents.php?plane=OO-ALE&remark=Passengers+headset+bolt+on+the+jack+is+loose.+Gave+the+bolt+and+ring+to+Ren%C3%A9+V.&severity=mineure&action=create
// apparently because of +AND+ triggering a Web Application Firewall rule...
// Passengers headset bolt on the jack is loose. Gave the bolt and ring to René V
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

if (isset($_REQUEST['plane']) and $_REQUEST['plane'] != '') {
    $plane = strtoupper(trim($_REQUEST['plane'])) ;
} else {
    $plane = NULL ;
}

$body_attributes = "onLoad=\"prefillDropdownMenus('plane', planes, '$plane');init()\"";
require_once 'mobile_header5.php' ;
require_once 'incident.class.php' ;


if (isset($_REQUEST['closed']) and $_REQUEST['closed'] != '')
    $closed = " checked" ;
else
    $closed = NULL ;

if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'create') {
    $incident = new Incident() ;
    $incident->plane = strtoupper($_REQUEST['plane']) ;
    $incident->severity = strtoupper($_REQUEST['severity']) ;
    $incident->save() ;
    $event = new IncidentEvent() ;
    $event->incident = $incident ;
    $event->status = 'opened' ;
    $event->text = $_REQUEST['remark'] ;
    $event->save() ;
    $rPlane=strtoupper($_REQUEST['plane']) ;
    $rRemark= $_REQUEST['remark'] ;
    print("<h2 style=\"color: red;\">Incident $incident->id created: Plane: $rPlane Remark:$rRemark</h2>");
}
?>

<h3>Create an New Aircraft Technical Log (ATL) entry</h3>

<div class="row">
<form action="<?=$_SERVER['PHP_SELF']?>" method="POST" role="form" class="form-horizontal">
<div class="row mb-3">
	<label for="planeSelect" class="col-form-label col-sm-4 col-md-2">Aircraft:</label>
	<div class="col-sm-4 col-md-2">
        <select id="planeSelect" class="form-select" name="plane">
        </select>
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="remarkId" class="col-form-label col-sm-4 col-md-2">Description:</label>
	<div class="col-sm-12 col-md-6">
		<input type="text" class="form-control" name="remark" id="remarkId" placeholder="Short description of the techlog entry" >
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="severityId" class="col-form-label col-sm-4 col-md-2">Severity:</label>
	<div class="col-sm-4 col-md-2">
        <select name="severity" id="severityId" class="form-select" onchange="selectSeverity();">
            <option value="nohazard">No hazard to fly</option>
            <option value="hazard">Hazard to fly</option>
            <option value="" selected>-- unknown --</option>
        </select>
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
        <button type="submit" id="id_createatlentry" name="action" value="create" class="col-sm-offset-2 col-md-offset-1 col-sm-3 col-md-2 btn btn-primary" disabled>
            Create ATL entry
        </button></div>
</form>
</div><!-- row -->

<hr>

<h2>Aircraft Technical Log (ATL)</h2>

<div class="row">
    <form action="<?=$_SERVER['PHP_SELF']?>" method="get" role="form" class="form-horizontal">
        <input type="hidden" name="plane" value="<?=$plane?>">
        <label><input type="checkbox" name="closed"<?=$closed?> onchange="this.form.submit();"> Include closed entries</label>
    </form>
</div><!-- row -->

<div class="row">
<div class="col-sm-12 col-md-12 col-lg-12">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<tr><th class="text-center" colspan="6">Report</th><th class="text-center border-start" colspan="4">Latest status</th></tr>
<tr><th>#Entry</th><th>Aircraft</th><th>Severity</th><th>Date</th><th>Description</th><th>By</th><th class="border-start">Status</th><th>Action</th><th>Date</th><th>By</th></tr>
</thead>
<tbody class="table-group-divider">
<script type="text/javascript">
    //var anEntry=document.getElementById("id_createatlentry");
    //anEntry.disabled=true;        
    function selectSeverity() {
        var anEntry=document.getElementById("id_createatlentry");
        
		var severity=document.getElementById("severityId").value;
        if(severity=="hazard" || severity =="nohazard") {
            document.getElementById("id_createatlentry").disabled=false;
        }
        else {
            document.getElementById("id_createatlentry").disabled=true;            
        }
    }
</script>
<?php
    if ($closed == NULL)
        $incidents = new Incidents($plane, ['opened', 'inprogressnoaog', 'inprogressaog', 'camonoaog', 'camoaog']) ;
    else
        $incidents = new Incidents($plane) ;
    foreach($incidents as $incident) {
        print("<tr>
            <td>
                <a href=\"mobile_incident.php?incident=$incident->id\" title=\"Edit ATL\">$incident->id <i class=\"bi bi-pen-fill\"></i></a>
            </td>
            <td class=\"text-nowrap\"><a href=\"mobile_incidents.php?plane=$incident->plane\">$incident->plane</a><br/>$incident->planeType</td>
            <td>$incident->severity</td>
            <td>$incident->firstDate&nbsp;&nbsp;<span class=\"badge bg-primary\"><i class=\"bi bi-clock-fill\"></i> $incident->daysPending</span></td>   
            <td>$incident->firstText</td>   
            <td><b>$incident->firstLastName</b> $incident->firstFirstName</td>\n") ;
        if ($incident->firstId == $incident->lastId)
            print("<td class=\"border-start\" colspan=\"4\"></td></tr>\n") ;
        else 
            print("<td class=\"border-start\">$incident->lastStatus</td>
            <td>$incident->lastText</td>
            <td>$incident->lastDate</td>
            <td><b>$incident->lastLastName</b> $incident->lastFirstName</td>
            </tr>\n") ;
    }
?>
</tbody>
</table>
</div><!-- table responsive -->
</div><!-- col -->
</div><!-- row --> 
<p class="fw-light">Cliquer sur un numéro d'ATL (ou sur l'icône <i class="bi bi-pen-fill"></i>) pour consulter/modifier l'historique de cet ATL, y compris changer le statut.<br>
Cliquer sur un avion, pour afficher uniquement les ATL de cet avion.<br><span class="badge bg-primary"><i class="bi bi-clock-fill"></i> 9</span> indique le nombre de jours depuis
l'ouverture de l'ATL.</p>
<?php
if($plane != "") {
    print("<p><input class=\"button\" type=\"button\" value=\"Display all Aircrafts\" onclick=\"javascript:document.location.href='mobile_incidents.php';\"></input>");
}
?>
</body>
</html>