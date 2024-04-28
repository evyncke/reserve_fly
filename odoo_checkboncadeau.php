<?php
/*
   Copyright 2024 Eric Vyncke

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
require_once 'flight_header.php' ;
require_once "dbi.php" ;
require_once "odooFlight.class.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}
/*
$header_postamble = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<link href="https://www.spa-aviation.be/favicon32x32.ico" rel="shortcut icon" type="image/vnd.microsoft.icon">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script type="text/javascript">
// Manage Search when keyup

// Manage Search when document loaded
$(document).ready(function() {
   $("#id_SearchInput").on("keyup", function() {
      var value = $(this).val().toLowerCase();
      $("#myTable tr").filter(function() {
		  var aText=$(this).text().toLowerCase().normalize("NFD");
        $(this).toggle(aText.indexOf(value) > -1)
     });
    });
    var value = $("#id_SearchInput").val().toLowerCase();
      $("#myTable tr").filter(function() {
      $(this).toggle($(this).text().toLowerCase().normalize("NFD").indexOf(value) > -1)
      });
});
</script>' ;
*/

//require_once 'flight_header.php' ;
require_once 'odoo.class.php' ;

$CheckMark="&#9989;";

if (!$userIsAdmin and !$userIsBoardMember and !$userIsInstructor and !$userIsFlightManager) journalise($userId, "F", "This admin page is reserved to administrators") ;

// ACTION: Link odoo account.move.line and flight_ledger
if (isset($_REQUEST['action']) and $_REQUEST['action'] == "linkledgerodoo") {
	$ledgerID= $_REQUEST['ledgerid'] ;
	$odooID = $_REQUEST['odooid'] ;
    if(OF_LinkOdooLedger($ledgerID,$odooID)) {
    	print("<h1 style=\"color: red;\"><b>Link ODOO Invoice:$odooID -> FLIGHT ledgerID=$ledgerID: DONE</b></h1>");
    }
    else {
	    print("<h1 style=\"color: red;\"><b>Error to Link ODOO Invoive:$odooID -> FLIGHT ledgerID=$ledgerID</b></h1>");
    }
}
$odooPaymentMap=array();
$paymentFlightMap=array();
$ledgerIdMap=array();
$referenceIDMap=array();
OF_FillFlightOdooMaps($odooPaymentMap,$paymentFlightMap,$ledgerIdMap,$referenceIDMap);
/*
$result = mysqli_query($mysqli_link, "SELECT * FROM $table_flights_ledger JOIN $table_flights ON fl_flight=f_id")
		or journalise($userId, "E", "Cannot read ledger: " . mysqli_error($mysqli_link)) ;
while ($row = mysqli_fetch_array($result)) {
    $fl_odoo_payment_id=$row['fl_odoo_payment_id'];
    $f_reference=$row['f_reference'];
    if($fl_odoo_payment_id!="") {
        $odooPaymentMap[$fl_odoo_payment_id]=$f_reference;
        //print("fl_id=$row[fl_id],f_id=$row[f_id],fl_flight=$row[fl_flight],f_reference=$f_reference,fl_odoo_payment_id=$fl_odoo_payment_id<br>");
     }
     $referenceIDMap[$f_reference]=$row['f_id'];
     $paymentFlightMap[$f_reference]=$row['fl_amount'];
     $ledgerIdMap[$f_reference]=$row['fl_id'];
}
//var_dump($odooPaymentMap);
*/

$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
?>
<script type="text/javascript">
    function linkFunction(PHP_Self, theFlightID, theOdooID, theLedgerID) {
		if (confirm("Confirmer que vous voulez lier le paiement odoo avec la table flight "+theFlightID+","+ theOdooID+","+ theLedgerID ) == true) {			
   		 	var aCommand=PHP_Self+"?action=linkledgerodoo&ledgerid="+theLedgerID+"&odooid="+theOdooID;
   		 	window.location.href = encodeURI(aCommand);
		}
    }

</script>
<h2><p><b>Bons cadeaux non encore utilisés vus par ODOO.<br/>Comptes d'attente 499001 INIT et 499002 IF.</b></p></h2>
<p id="id_nombre_bons_cadeaux">#INIT: ... - #IF: ... </p>
<?php	
$searchText="";
print("<input class=\"form-control\" id=\"id_SearchInput\" type=\"text\" placeholder=\"Search..\" value=\"$searchText\">");
?>
<p></p>
<form action="<?=$_SERVER['PHP_SELF']?>" id="checkboncadeau_form">
<table class="table table-striped table-responsive table-hover" id="allFlights">
    <thead>
       <tr><th>#</th><th>Odoo Id</th><th>Date</th><th>Compte</th><th>Communication</th><th>Ref. in Flight</th><th>Ref. in Odoo</th><th>Client in Odoo</th><th>Valeur Flight</th><th>Valeur Odoo</th></tr>
    </thead>
    <tbody class="table-group-divider" id="myTable">
<?php
$result = $odooClient->SearchRead('account.move.line', array(), array('fields' => array('id', 'name', 'move_type','account_id','debit', 'credit', 'partner_id', 'create_date'))) ;
$ids = array() ;
$rowNumber=0;
$accountINI=0;
$accountIF=0;
foreach($result as $f=>$desc) {
	//echo "f=";
	//echo var_dump($f);
	$id = (isset($desc['id'])) ? $desc['id'] : '' ;
	$communication = (isset($desc['name'])) ? $desc['name'] : '' ;
	$communicationUppercase = strtoupper($communication);
	$credit = (isset($desc['credit'])) ? $desc['credit'] : '' ;
	$account_id=(isset($desc['account_id'])) ? $desc['account_id'] : '' ;
	$account="";
	if(!is_bool($account_id)) {
		$account= substr($account_id[1],0,6);
	}
	$date = (isset($desc['create_date'])) ? $desc['create_date'] : '' ;
	$date = substr($date,0,10);
	$partner_id = (isset($desc['partner_id'])) ? $desc['partner_id'] : '' ;
	$partner="";
	if(!is_bool($partner_id)) {
		$partner=$partner_id[1];
	}
    $flightReference="????";
	if(($account=="499001" || $account=="499002") && $credit > 0.0) {
		if($account=="499001") {
			++$accountINI;
            $posFlightReference = strpos($communicationUppercase, "V-INIT-");
            if ($posFlightReference === false) {
                $flightReference="V-INIT-??????";
            } 
            else {
                $flightReference=substr($communicationUppercase, $posFlightReference, 13);
                $posBlank=strpos($flightReference, " ");
                if($posBlank === false) {
                }
                else {
                  $flightReference=substr($flightReference, 0, $posBlank); 
                }
            }
		}
		else {
			++$accountIF;
            $posFlightReference = strpos($communicationUppercase, "V-IF-");
            if ($posFlightReference === false) {
                $flightReference="V-IF-??????";
            }
            else {
                $flightReference=substr($communicationUppercase, $posFlightReference, 11);
                $posBlank=strpos($flightReference, " ");
                if($posBlank === false) {
                }
                else {
                  $flightReference=substr($flightReference, 0, $posBlank); 
                }
            }
		}
		$rowNumber++;
        $idText=$id;
        $referenceInFlight="";
        if (array_key_exists($id, $odooPaymentMap)) {
            $referenceInFlight=$odooPaymentMap[$id];
            $idText=$CheckMark." ".$idText;
        }
        else{
            //$idText="<a class=\"tooltip\" href=\"javascript:void(0);\" onclick=\"linkFunction('$_SERVER[PHP_SELF]','Block')\">&#x2714; ".$idText."<span class='tooltiptext'>Click pour LIER</span></a>";
            $idText=$idText;
        }
        $styleRed="";
        if($referenceInFlight!="" && ($referenceInFlight!=$flightReference)) {
            $styleRed="style='color: red;'";
        }
        if (array_key_exists($flightReference, $paymentFlightMap)) {
            $amountFlight=$paymentFlightMap[$flightReference];
        }
        else {
            $amountFlight="?";;
        }
        
    	print("<tr>
      	 	<td>$rowNumber</td>
		    <td>$idText</td>
   			<td>$date</td>
   			<td>$account</td>
     	  	<td>$communication</td>");
        if (array_key_exists($referenceInFlight, $referenceIDMap)) {
     	  	print("<td $styleRed><a href=\"https://www.spa-aviation.be//resa/flight_create.php?flight_id=$referenceIDMap[$referenceInFlight]\">$referenceInFlight<a></td>");
        }
        else {
            if($referenceInFlight!="") {
                // Le lien entre Flight et Odoo est OK
                print("<td $styleRed>$referenceInFlight</td>");
            }
            else {
                if (array_key_exists($flightReference, $referenceIDMap)) {
                    // Via Odoo on  peut retrouver le Flight et un paiement existe sans lien vers odoo
                    // On autorise l'utilisateur à lier le paiement à odoo
                    $var1=$referenceIDMap[$flightReference];
                    $var3=$ledgerIdMap[$flightReference];
                    print("<td $styleRed><a href=\"javascript:void(0);\" onclick=\"linkFunction('$_SERVER[PHP_SELF]', '$var1', '$id', '$var3')\">Lier Odoo&Flight</a></td>");
                 }
                else {
                    // On ne trouve pas en automatique un lien entre le paiement odoo et un vol dans Flight
                    print("<td $styleRed></td>");
                }
            }
        }
        if (array_key_exists($flightReference, $referenceIDMap)) {
     	  	print("<td $styleRed><a href=\"https://www.spa-aviation.be//resa/flight_create.php?flight_id=$referenceIDMap[$flightReference]\">$flightReference<a></td>");
        }
        else {
            print("<td $styleRed>$flightReference</td>");
        }
        $styleRed="";
        if($amountFlight!="?" && ($amountFlight!=$credit)) {
            $styleRed="style='color: red;'";
        }
        
     	print("<td>$partner</td>
     	  	<td $styleRed>$amountFlight €</td>
 	  	    <td $styleRed>$credit €</td>
      	  	 </tr>\n") ;
	}
}
?>
</tbody>
</table>

<?php
		//print("<b>#INIT: $accountINI - #IF: $accountIF</b><br/>");
		print("<script type='text/javascript'>
			document.getElementById('id_nombre_bons_cadeaux').innerHTML ='Nombre de bons cadeaux INIT: $accountINI - Nombre de bons cadeaux IF: $accountIF';
		</script>");
?>

</form>
</body>
</html>