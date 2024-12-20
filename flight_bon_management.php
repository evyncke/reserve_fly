<?php
/*
   Copyright 2014-2025 Eric Vyncke Patrick Reginster

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
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
' ;
require_once 'flight_header.php' ;
require_once "odooFlight.class.php" ;

$pattern="";
if (isset($_REQUEST['pattern'])) {
	$pattern = mysqli_real_escape_string($mysqli_link, web2db(trim($_REQUEST['pattern']))) ;
	$other_filter = " AND (p_lname LIKE '%$pattern%' OR p_fname LIKE '%$pattern%' or f_description LIKE '%$pattern%' or f_reference LIKE '%$pattern%' or f_notes LIKE '%$pattern%' or p_email LIKE '%$pattern%' OR first_name LIKE '%$pattern%' OR last_name LIKE '%$pattern%') " ;
} else
	$other_filter = '' ;

// Should check exclusive choices
if (isset($_REQUEST['if_only'])) {
	$if_only = ' checked' ;
	$if_only_filter = " AND f_type = 'D'" ;
} else {
	$if_only = '' ;
	$if_only_filter = '' ;
}
if (isset($_REQUEST['init_only'])) {
	$init_only = ' checked' ;
	$init_only_filter = " AND f_type = 'I'" ;
} else {
	$init_only = '' ;
	$init_only_filter = '' ;
}
$title = '(Expirés)' ;

if (isset($_REQUEST['action'])) {
    if($_REQUEST['action']=='deactivebon') {
        if($userIsBoardMember) {
            if(isset($_REQUEST['id'])) {
                $bonFlightID = $_REQUEST['id'];
                //print("action deactivebon $bonFlightID<br>");
                $flightReference=OF_GetFlyReference($bonFlightID);
                // Creation d'une OD pour transferer la valeur du compte d'attente 499001-2 -> 765000 (Produit exeptionel)
                $error=OF_DeactiveBon($bonFlightID);
                $newFlightReference=OF_GetFlyReference($bonFlightID);
                if($error=="") {
                    print("<p class=\"text-danger\"><b>Le Bon $flightReference est déactivé. Il est renommé $newFlightReference</b></p>");
                }
                else {
                    print("<p class=\"text-danger\"><b>Le Bon $flightReference n'est pas déactivé. Erreur: $error</b></p>");
                }
            }
            if(isset($_REQUEST['reference'])) {
                $bonFlightReferences = explode(",",$_REQUEST['reference']);
                foreach($bonFlightReferences as $bonFlightReference) {
                    $bonFlightID =OF_GetFlightIdFromReference($bonFlightReference);
                    // Creation d'une OD pour transferer la valeur du compte d'attente 499001-2 -> 765000 (Produit exeptionel)
                    $error=OF_DeactiveBon($bonFlightID);
                    $newFlightReference=OF_GetFlyReference($bonFlightID);
                    if($error=="") {
                        print("<p class=\"text-danger\"><b>Le Bon $bonFlightReference est déactivé. Il est renommé $newFlightReference</b></p>");
                    }
                    else {
                        print("<p class=\"text-danger\"><b>Le Bon $bonFlightReference n'est pas déactivé. Erreur: $error</b></p>");
                    }
                }
            }
        }
        else {
            print("<p class=\"text-danger\"><b>Vous n'êtes pas autorisé à effectué cette opération</b></p>");
        }
    }
}

?>
<script type="text/javascript">
function deactiveBonFunction(PHP_Self, theAction, theReferenceID, theFlightReference)
{
	if(theAction=="deactive") {
		if (confirm("Confirmer que vous voulez déactiver le bon  " + theFlightReference) == true) {			
   		 	var aCommand=PHP_Self+"?action=deactivebon&id="+theReferenceID;	
   		 	window.location.href = encodeURI(aCommand);
		}
	}
}

function submitDisableBon(PHP_Self, action) {
	//var aSearchText=document.getElementById("id_SearchInput").value;
    var table = document.getElementById("allFlights");
    var rows = table.rows;
	var aSelectToggleColumn=0;
	var aListOfId="";
	var aCount=0;
   	for (i = 1; i < rows.length; i++) {
        var row = rows[i];
		if(!row.hidden) {
			var aColumn1Row = row.getElementsByTagName("TD")[aSelectToggleColumn];
			var aSelectedToggle = aColumn1Row.childNodes[0];
			if(aSelectedToggle.checked) {
				aCount++;
				var aValueText=row.getElementsByTagName("TD")[1].textContent;
				if(aListOfId!="") {
					aListOfId+=",";
				}
				aListOfId+=aValueText;
				aColumn1Row.style.backgroundColor="orange";
			}
			else {
				aColumn1Row.style.backgroundColor="white";
			}
		}
	}
	if(aCount==0) {
		alert("Pour déactiver , vous devez d'abord selectionner des lignes dans la table!");
		return;
	}
	if(action=="disable") {
		if (confirm("Confirmer que vous voulez déactiver " + aCount.toString() +" bon(s) ?") == true) {			
   		 	var aCommand=PHP_Self+"?action=deactivebon&reference="+aListOfId;	
   		 	window.location.href = encodeURI(aCommand);
		}
	}
}
</script>

<div class="page-header hidden-xs">
<h3>Gestion des bons cadeaux <?=$title?></h3>
</div><!-- page header -->

<div class="row">
<form class="" action="<?=$_SERVER['PHP_SELF']?>">
	<input type="hidden" name="completed" value="<?=$completed?>"/>
<!--div class="form-group"-->
    <div class="form-group">
        <!-- checkbox-->
	    <div class="checkbox col-xs-4 col-md-2">
			<label><input type="checkbox" name="init_only"<?=$init_only?> onchange="this.form.submit();">Initiations seulement</label>
	    </div><!-- checkbox-->
	    <div class="checkbox col-xs-4 col-md-2">
				<label><input type="checkbox" name="if_only"<?=$if_only?> onchange="this.form.submit();">Découvertes seulement</label>
	    </div><!-- checkbox-->
    </div> <!-- formgroup-->
	<div class="form-group">
			<div class="col-xs-6 col-md-offset-1 col-md-4">
				<input type="text" class="form-control" name="pattern" value="<?=db2web($pattern)?>"/>
			</div>
	</div> <!-- formgroup-->
	<div class="form-group">
				<div class="col-xs-3 col-md-1">
					<input type="submit" class="btn btn-primary" name="add" value="Chercher"/>
				</div><!-- col -->
	</div><!-- formgroup-->
	</form>
</div><!-- row -->
<?php
print("<input type=\"submit\" value=\"Désactiver bons\" id=\"id_SubmitDisableBon\" onclick=\"submitDisableBon('$_SERVER[PHP_SELF]','disable')\")>
    <br>");
?>
<table class="table table-striped table-responsive table-hover" id="allFlights">
<thead>
<tr><th>#</th><th>Réf</th><th>Actions</th><th>Date Paiement</th><th>Ancienneté</th><th>Compte attente</th><th>Etat</th><th>Type</th><th>Client</th><th>Passager</th><th>Notes club</th></tr>
</thead>
<tbody>
<?php
$deleted_filter = " AND f_date_cancelled IS NULL" ;
$result = mysqli_query($mysqli_link, "SELECT *, SYSDATE() AS today, SUM(fl_amount) AS payment 
	FROM $table_flight JOIN $table_pax_role ON f_id = pr_flight JOIN $table_pax ON pr_pax = p_id LEFT JOIN $table_person ON f_pilot = jom_id
	LEFT JOIN $table_bookings AS b ON f_booking = b.r_id
	LEFT JOIN $table_flights_ledger AS fl on fl_flight = f_id
	WHERE pr_role = 'C' and f_gift=1 $other_filter $deleted_filter $if_only_filter $init_only_filter
	GROUP BY f_id
	ORDER BY f_id DESC") 
	or journalise($userId, "F", "Impossible de lister les vols: " . mysqli_error($mysqli_link));
	$count=0;
while ($row = mysqli_fetch_array($result)) {
    //if($count>10) break;
    $referenceID=$row['f_id']; // Ex 241234
	$reference = db2web($row['f_reference']) ; // Ex: V-INIT-241234
	$email = ($row['p_email']) ? " <a href=\"mailto:$row[p_email]\"><span class=\"glyphicon glyphicon-envelope\" title=\"Envoyer un email\"></span></a>" : "" ; 
	$telephone = ($row['p_tel']) ? " <a href=\"tel:$row[p_tel]\"><span class=\"glyphicon glyphicon-earphone\" title=\"Téléphoner\"></span></a>" : "" ; 
	$edit =  " <a href=\"flight_create.php?flight_id=$row[f_id]\"><span class=\"glyphicon glyphicon-pencil\" title=\"Modifier/Annuler\"></span></a> " ;
	$print =  " <a href=\"flight_pdf.php?flight_id=$row[f_id]\" target=\"_blank\"><span class=\"glyphicon glyphicon-print\" title=\"Imprimer sous format PDF\"></span></a> " ;
	$pay =  ($row['payment'] > 0) ? "<span class=\"glyphicon glyphicon-euro\" style=\"color: green;\" title=\"Vol déjà payé\"></span>" :
		" <a href=\"flight_create.php?flight_id=$row[f_id]&pay_open=true\"><span class=\"glyphicon glyphicon-euro\" style=\"color: red;\" title=\"Indiquer le paiement\"></span></a> " ;
    $expired=$row['f_expired'];
    $deactiveAction="<a class=\"tooltip\" href=\"javascript:void(0);\" onclick=\"deactiveBonFunction('$_SERVER[PHP_SELF]','deactive','$referenceID','$reference')\">&#x2714;Déactiver Bon<span class='tooltiptext'>Click pour déactiver le bon</span></a>";

	$is_gift = ($row['f_gift'] != 0) ? '&nbsp;<span class="glyphicon glyphicon-gift" style="color: red;" title="Bon cadeau"></span>' : '' ;
	$type = ($row['f_type'] == 'D') ? 'découverte' : 'initiation' ;
	$description = nl2br(db2web($row['f_description'])) ;
	$notes = nl2br(db2web($row['f_notes'])) ;
	$row_style = '' ;
	if ($row['f_date_cancelled']) {
		$status = "Annulé";
		$row_style = ' style="color: lightgray;"' ;
	} else if($expired==1) {
        $status = "Expiré";
    }
    else if ($row['f_date_flown'])
		$status = "Accompli" ;
	else if ($row['f_date_linked'])
		$status = "Avion réservé" ;
	else if ($row['f_date_assigned'])
		$status = "Pilote sélectionné" ;
	else if ($row['fl_date'])
		$status = "Paiement effectué" ;
	else
		$status = "Attente paiement" ;
	if ($row['f_date_flown'])
		$date_vol = "ATD $row[f_date_flown] ($row[r_plane])" ;
	else if ($row['r_start'])
		$date_vol = "ETD $row[r_start] ($row[r_plane])"  ;
	else
		$date_vol = "à déterminer" ;

	$row_style = '';
    if($expired) {
        $row_style = ' style="color: lightgray;"' ;
        $deactiveAction="";
    }
	$passenger="";
    $compteAttente="49900x";

    $datePaiementString=$row["fl_date"];
    $dateLimitBonString=$row["fl_date"];
    //$dateLimitBonString=date('Y-m-01') ;
    $dateLimitBon=new DateTime($dateLimitBonString);
    $today=new DateTime();
    $datePaiement=date_create($datePaiementString);

    $diff=date_diff($datePaiement,$today);
    // %a outputs the total number of days
    $dureeBonString=$diff->format("%y ans %m mois");
    $yearInterval = new DateInterval('P3Y');
    $dateLimitBon=$dateLimitBon->add($yearInterval);
    $dateLimitBonString=$dateLimitBon->format('Y-m-d') ;
    $dateLimitBonStyle="";
    if($dateLimitBon<$today) {
        // Add warning symbol
        $dateLimitBonString="<i class=\"material-icons\">&#xe002;</i>".$dateLimitBonString;
        //$dateLimitBonString=$dateLimitBonString;
        $dateLimitBonStyle= "style=\"color: red;\"";
    }

    $OdooReference=OF_GetPaymentOdooReference($referenceID);
    if($OdooReference>0) {
        $compteAttente=$OdooReference;
        // 499001, 499002, ...
        //$compteAttente=OF_GetAccountNumberFromPayment($OdooReference); 
        $typeFlight=$row['f_type'];
        if($typeFlight=="D") {
            $compteAttente="499002";
        }
        else if($typeFlight=="I") {
            $compteAttente="499001";
        }
        else {
            $compteAttente="49900?";           
        }
    }
    else {
        $compteAttente="----" ;
    }

	//print("SELECT *, SYSDATE() AS today FROM rapcs_pax_role join rapcs_pax ON p_id= pr_pax WHERE pr_flight=$row[f_id] and (pr_role='P' or pr_role='S')</br>");

	$resultPassenger= mysqli_query($mysqli_link, "SELECT *, SYSDATE() AS today FROM rapcs_pax_role join rapcs_pax ON p_id= pr_pax 
	WHERE pr_flight=$row[f_id] and (pr_role='P'or pr_role='S')")
	or journalise($userId, "F", "Impossible de loader les passagers: " . mysqli_error($mysqli_link));
	while ($rowPassenger = mysqli_fetch_array($resultPassenger)) {
		$passenger=db2web($rowPassenger['p_fname']) . " <b>" . db2web($rowPassenger['p_lname']). "</b>";
		break;
	}

	$count++;
	print("<tr $row_style><td><input type=\"checkbox\"> $count</td><td>$reference</td><td>$edit$pay $deactiveAction</td>
    <td>$datePaiementString</td><td $dateLimitBonStyle>$dureeBonString</td><td>$compteAttente</td><td>$status</td>
		<td>$type$is_gift</td>
		<td>" . db2web($row['p_fname']) . " <b>" . db2web($row['p_lname']) . "$email$telephone</b></td>
		<td>$passenger</td>
		<td>$notes</td></tr>\n") ;
}
?>
</tbody>
</table>

<script>
	// Let sort the row by clicking on the header https://stackoverflow.com/questions/3160277/jquery-table-sort/19947532#19947532
	$('th').click(function(){
    var table = $(this).parents('table').eq(0)
    var rows = table.find('tr:gt(0)').toArray().sort(comparer($(this).index()))
    this.asc = !this.asc
    if (!this.asc){rows = rows.reverse()}
    for (var i = 0; i < rows.length; i++){table.append(rows[i])}
})
function comparer(index) {
    return function(a, b) {
        var valA = getCellValue(a, index), valB = getCellValue(b, index)
		return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.toString().localeCompare(valB)
    }
}
function getCellValue(row, index){ return $(row).children('td').eq(index).text() }
</script>
<?php
require_once 'flight_trailer.php' ;
?>
