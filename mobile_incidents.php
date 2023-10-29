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

if (isset($_REQUEST['plane']) and $_REQUEST['plane'] != '') {
    $plane = trim($_REQUEST['plane']) ;
} else {
    $plane = NULL ;
}

if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'create') {
    $incident = new Incident() ;
    $incident->plane = strtoupper($_REQUEST['plane']) ;
    $incident->save() ;
    $event = new IncidentEvent() ;
    $event->incident = $incident ;
    $event->status = 'opened' ;
    $event->text = $_REQUEST['remark'] ;
    $event->save() ;
}
?>
<p class="lead text-danger mb-5">En cours de développement, ne pas utiliser.</p>

<h2>Ajouter un nouvel incident</h2>

<div class="row">
<form action="<?=$_SERVER['PHP_SELF']?>" method="get" role="form" class="form-horizontal">
<div class="row mb-3">
	<label class="col-form-label col-sm-4 col-md-2">Avion:</label>
	<div class="col-sm-4 col-md-1"> <!-- should be a drop-down -->
		<input type="text" class="form-control" name="plane">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label class="col-form-label col-sm-4 col-md-2">Description:</label>
	<div class="col-sm-12 col-md-6">
		<input type="text" class="form-control" name="remark" placeholder="Description courte de l'incident">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
	<label class="col-form-label col-sm-4 col-md-2">Importance/urgence:</label>
	<div class="col-sm-12 col-md-6">
		<input type="text" class="form-control" name="remark" placeholder="Peut être laissé vide, sinon, 'important', 'pas urgent', 'bloquant', ...">
	</div> <!-- col -->
</div> <!-- row -->
<div class="row mb-3">
        <button type="submit" name="action" value="create" class="col-sm-offset-2 col-md-offset-1 col-sm-3 col-md-2 btn btn-primary" >
            Ajouter l'incident
        </button></div>
</form>
</div><!-- row -->

<h2>Liste des incidents</h2>

<div class="row">
<div class="col-sm-12 col-md-12 col-lg-7">
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<tr><th>#Incident</th><th>Avion</th><th>Importance/Urgence</th><th class="align-center" colspan="4">Dernier Statut</th></tr>
<tr><th></th><th></th><th></th><th>Statut</th><th>Description</th><th>Date</th><th>Par</th></tr>
</thead>
<tbody>

<?php
    $incidents = new Incidents($plane) ;
    foreach($incidents as $incident) {
        print("<tr>
            <td>
                <a href=\"mobile_incident.php?student=$incident->id\" title=\"Edit incident\">$incident->id<i class=\"bi bi-pen-fill\"></i></a>
            </td>
            <td><a href=\"mobile_incidents.php?plane=$incident->plane\">$incident->plane</a></td>
            <td>$incident->importance</td>
            <td>$incident->lastStatus</td>
            <td>$incident->lastText</td>
            <td>$incident->lastDate</td>
            <td>$incident->lastLastName $incident->lastFirstName</td>
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