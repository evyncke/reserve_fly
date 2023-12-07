<?php
/*
   Copyright 2023 Eric Vyncke

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

ob_start("ob_gzhandler");
require_once "dbi.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}
require_once 'mobile_header5.php' ;
require_once 'incident.class.php' ;

if (isset($_REQUEST['incident']) and $_REQUEST['incident'] != '' and is_numeric($_REQUEST['incident'])) {
    $incident_id = trim($_REQUEST['incident']) ;
    $incident = new Incident() ;
    $incident->getById($incident_id) ;
} else {
    journalise($userId, "F", "Bad value or missing parameter incident='$incident'") ;
}

if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'add' and isset($_REQUEST['status']) and isset($_REQUEST['remark'])) {
    $event = new IncidentEvent() ;
    $event->incident = $incident ;
    switch ($_REQUEST['status']) {
        case 'rejected':
        case 'inprogress':
        case 'closed':
        case 'opened': $event->status = $_REQUEST['status'] ; break ;
        default: journalise($userId, "F", "Missing or wrong value for status=$_REQUEST[staus]") ;
    } ;
    $event->text = trim($_REQUEST['remark']) ;
    $event->save() ;
}
?>
<p class="lead text-danger mb-5">Under development, do not use yet beside tests by developpers, fleet managers, FIs. Data is just dumb fantasies often invented by Eric.</p>

<?php
if ($userIsBoardMember or $userIsInstructor or $userIsMechanic) {
?>
<h2>Add a new entry in the tech log</h2>

<p class="lead">This function is only availanle to fleet managers, mechanics, and FIs.</p>

<div class="row">
<form action="<?=$_SERVER['PHP_SELF']?>" method="get" role="form" class="form-horizontal">
<input type="hidden" name="incident" value="<?=$incident_id?>">
<div class="row mb-3">
	<label for="statusSelect" class="col-form-label col-sm-4 col-md-2">New status:</label>
	<div class="col-sm-4 col-md-1">
        <select id="statusSelect" class="form-select" name="status">
            <option value="opened" <?=($incident->lastStatus == 'opened') ? 'selected':''?>>Opened</option>
            <option value="accepted" <?=($incident->lastStatus == 'accepted') ? 'selected':''?>>Accepted</option>
            <option value="inprogress" <?=($incident->lastStatus == 'inprogress') ? 'selected':''?>>In progress</option>
            <option value="closed" <?=($incident->lastStatus == 'closed') ? 'selected':''?>>Closed</option>
            <option value="rejected" <?=($incident->lastStatus == 'rejected') ? 'selected':''?>>Rejected</option>
        </select>
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label for="remarkId" class="col-form-label col-sm-4 col-md-2">Description:</label>
	<div class="col-sm-12 col-md-6">
		<input type="text" class="form-control" name="remark" id="remarkId" placeholder="Short description of the action/question/answer">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
        <button type="submit" name="action" value="add" class="col-sm-offset-2 col-md-offset-1 col-sm-3 col-md-2 btn btn-primary" >
            Modify techlog entry
        </button></div>
</form>
</div><!-- row -->

<?php
} // if ($userIsBoardMember or $userIsInstructor or $userIsMechanic)
?>
<h2>History of tech long entry#<?=$incident_id?> (<?=$incident->plane?>)</h2>

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
</body>
</html>