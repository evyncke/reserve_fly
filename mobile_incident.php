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
<p class="lead text-danger mb-5">En cours de développement, ne pas utiliser.</p>

<h2>Ajouter un nouveau status/observation</h2>

<div class="row">
<form action="<?=$_SERVER['PHP_SELF']?>" method="get" role="form" class="form-horizontal">
<input type="hidden" name="incident" value="<?=$incident_id?>">
<div class="row mb-3">
	<label for="statusSelect" class="col-form-label col-sm-4 col-md-2">Nouveau statut:</label>
	<div class="col-sm-4 col-md-1">
        <select id="statusSelect" class="form-select" name="status">
            <option value="opened" <?=($incident->lastStatus == 'opened') ? 'selected':''?>>Ouvert</option>
            <option value="inprogress" <?=($incident->lastStatus == 'inprogress') ? 'selected':''?>>En progrès</option>
            <option value="closed" <?=($incident->lastStatus == 'closed') ? 'selected':''?>>Clôturé</option>
            <option value="rejected" <?=($incident->lastStatus == 'rejected') ? 'selected':''?>>Rejeté</option>
        </select>
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label class="col-form-label col-sm-4 col-md-2">Description:</label>
	<div class="col-sm-12 col-md-6">
		<input type="text" class="form-control" name="remark" placeholder="Description courte de l'action/question/réponse">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
        <button type="submit" name="action" value="add" class="col-sm-offset-2 col-md-offset-1 col-sm-3 col-md-2 btn btn-primary" >
            Modifier l'incident
        </button></div>
</form>
</div><!-- row -->

<h2>Historique de l'incident #<?=$incident_id?> (<?=$incident->plane?>)</h2>

<div class="row">
<div class="col-sm-12 col-md-12 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<tr><th>Date</th><th>Statut</th><th>Description</th><th>Nom</th></tr>
</thead>
<tbody>

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