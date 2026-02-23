<?php
/*
   Copyright 2025 Patrick Reginster

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
require_once "odooFlight.class.php" ;
require_once "mobile_tools.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}
$bondecommandeJSONcontent = file_get_contents('data/bondecommande.json') ;
$bondecommandeJSONcontent = str_replace("\n","",$bondecommandeJSONcontent);

$header_postamble = "
<script>
  var bondecommandeJSONString='$bondecommandeJSONcontent';
  var default_member=$userId;
</script>
<script src=\"js/bondecommande.js\"></script>" ;

$body_attributes = 'onload=init();bondecommandeMain();' ;

require_once 'mobile_header5.php' ;
require_once 'dto.class.php' ;
//print("REQUEST=");
//var_dump($_REQUEST);
//print("<br>");
if (isset($_REQUEST['delete'])) {
    $deleteFile = $_REQUEST['delete'] ; // TODO by EVY ;-) être sûr que le nom de fichier n'est pas un fichier système avec des ../../... 
    $deleteAttachedFile=substr($deleteFile,0,strlen($deleteFile)-4);
    //print("deleteFile=$deleteFile deleteAttachedFile=$deleteAttachedFile<br>");
    $uploadbondecommandeFolder="uploads/bondecommande";
    $deleteFile=$uploadbondecommandeFolder."/".$deleteFile;
    $flag=false;
    if(file_exists($deleteFile)) {
        //print("1unlink($deleteFile)<br>");
         unlink($deleteFile);
        journalise($userId, "I", "Suppression d'un fichier bon de commande $_REQUEST[delete]==$deleteFile");
        $flag=true;
     }
    $files = scandir($uploadbondecommandeFolder);
    foreach($files as $file) {
        if(substr($file,0,strlen($deleteAttachedFile))==$deleteAttachedFile){
            //print("2unlink($uploadbondecommandeFolder/$file)<br>");
            $extension=strtolower(pathinfo($uploadbondecommandeFolder."/".$file)["extension"]);
            if($extension!="pdf"&&$extension!="png"&&$extension!="jpg"&&$extension!="jpeg") {
                print("<div class=\"text-bg-danger\">Erreur lors de la suppression des fichiers associés à un bon de commande: extension non valide $extension</div>");
                journalise($userId, "E", "Erreur lors de la suppression des fichiers associés à un bon de commande: extension non valide $extension");
            }  
            else { 
                unlink($uploadbondecommandeFolder."/".$file);
                journalise($userId, "I", "Suppression d'un fichier bon de commande $uploadbondecommandeFolder/$file");
                $flag=true;
            }
        }
    }
    if($flag) {
   	    print("<div class=\"text-bg-warning\"><b>Le bon de commande $_REQUEST[delete] est supprimée sur le serveur du club (Pas dans ODOO).</b></div> <p></p>");
    } else {
   	    print("<div class=\"text-bg-danger\"><b>ERREUR: Le bon de commande $_REQUEST[delete] n'est pas supprimée sur le serveur du club.</b></div> <p></p>");
        journalise($userId, "E", "Erreur lors de la suppression d'un bon de commande $_REQUEST[delete], fichiers non trouvés");
    }
}
$memberID=$userId;
if (isset($_REQUEST['member_name'])) {
    $memberID = $_REQUEST['member_name'] ;
    //print("memberID=$memberID<br>");
}

if (isset($_REQUEST['bondecommande_json'])) {
    $bdcJSON = $_REQUEST['bondecommande_json'] ;
    $uploadFileFolder="";
    $factureMailTo="";
    //print("ndf in JSON=$ndfJSON<br>");
    $bondecommandePDF=OF_createBonDeCommande($memberID, $bdcJSON, $uploadFileFolder, $factureMailTo);
    if($bondecommandePDF!="") { // TODO proposed by eric: to check whether "" or false is returned in the call function.
    	print("<div class=\"text-bg-info\">La bon de commande <a href=\"$uploadFileFolder/$bondecommandePDF\">$bondecommandePDF</a> est créée et envoyée à $factureMailTo</div>");
     	print("<h3>Download du bon de commande: <b><a href=\"$uploadFileFolder/$bondecommandePDF\" download>DOWNLOAD</a></b></h3><p></p>");
   }
    else {
	    print("<div class=\"text-bg-danger\">Erreur lors de la creation du bon de commande</div>");
        journalise($userId, "E", "Erreur lors de la creation d'un bon de commande");
    }

} else {
    $ndf = NULL ;
}


$selectorDisabled="disabled";
if ($userIsAdmin or $userIsBoardMember) $selectorDisabled="";


//print("<script>var bondecommandeJSONString='toto';</script>");
?>
<h2>Introduction d'un bon de commande</h2>
<br>
<form action="<?=$_SERVER['PHP_SELF']?>" method="post" role="form" class="form-horizontal" enctype="multipart/form-data">
<div class="row">
<div class="col-sm-12 col-md-12 col-lg-7">
Client: 
<select id="id_member_name" name="member_name" <?=$selectorDisabled?>>
<option selected="selected" value=""></option>
</select>
<table style="width:100%;" class="table table-striped" name="table_bondecommande" id="id_table_bondecommande">
<thead style="text-align: Center;">
<th>Action</th>
<th>Date</th>
<th>Type</th>
<th>reference</th>
<th>Quantité</th>
<th>Prix unitaire €</th>
<th>Montant €</th>
</thead>
<tbody class="table-group-divider">
<tr id="id_bondecommande_rowinput">
    <td>
        <a href="javascript:void(0);" onclick="deletebondecommandeLine(-1)" title="Effacer cette ligne"><i class="bi bi-trash-fill"></i></a>
    </td>
    <td><input type="date" id="id_bondecommande_input_date" name="bondecommande_input_date" value="2025-05-17" size="10"></input></td>
    <td><select id="id_bondecommande_input_type" name="bondecommande_input_type">
        <option selected="selected" value=""></option>
    </select></td>
    <td><input type="text" id="id_bondecommande_input_reference" name="bondecommande_input_reference" ></input></td>
    <td><input type="number" id="id_bondecommande_input_quantity" name="bondecommande_input_quantity" min="0.0" 
        max="100.00" step="1.00"></input></td>
    <td><input type="number" id="id_bondecommande_input_unitaryprice" name="bondecommande_input_unitaryprice" min="0.00" 
        max="5000.00" step="0.01"></input></td>
    <td><input type="number" id="id_bondecommande_input_total" name="bondecommande_input_total"></input></td>
    </tr>
<tr><td></td>
<td colspan="3"><a role="button" href="#" name="add_row" id="id_add_row">Ajouter une ligne</a><td></td><td>Montant Total:</td><td><span id="id_bondecommande_input_grandtotal">0.00€</span></td><td style="visibility: collapse"></td><td style="visibility: collapse"></td></tr>
</tbody>
</table>
</div><!-- table responsive -->
<br>
<!---<input type="hidden" id="id_bondecommande_json" name="bondecommande_json" value="[{}]">-->
<input type="hidden" id="id_bondecommande_json" name="bondecommande_json" value="[{}]">
<br>
<div class="col-sm-12 col-md-12 col-lg-7">
<?php
print("<button id=\"id_submit_bondecommande\" name=\"submit_bondecommande\" class=\"btn btn-primary\" width=\"10%\">Envoyer le bon de commande</button>");
?>
</div>
</form>
</div><!-- col -->
</div><!-- row --> 
<?php
    $hiddenGestion='class="d-none"';
    if ($userIsAdmin or $userIsBoardMember) $hiddenGestion="";
    require_once "mobile_tools.php" ;
    $theBonsDeCommande=array();
    $theAttachedFiles=array();
    $theUploadFolder="";
    $bonDeCommandeCount=MT_GetbondecommandeFiles($theBonsDeCommande, $theAttachedFiles, $theUploadFolder);
?>
<div <?=$hiddenGestion?>>
<p></p>
<h2>Gestion des fichiers associés aux bons de commande sur le serveur du club</h2>
<br>
<div class="row">
<div class="col-sm-12 col-md-12 col-lg-7">
<div>
<table style="width:100%;" class="table table-striped" name="table_bondecommande" id="id_table_bondecommande2">
<thead style="text-align: Center;">
<th>Action</th>
<th>bon de commande</th>
<th>Justificatif</th>
</thead>
<tbody class="table-group-divider">
<?php
    for($i=$bonDeCommandeCount-1;$i>=0;$i--) {
        print("<tr>");
        print("<td><a href=\"javascript:void(0);\" onclick=\"deletebondecommandeFiles('$_SERVER[PHP_SELF]','$theBonsDeCommande[$i]')\" title=\"Effacer les fichiers associés au bon de commande sur le serveur\"><i class=\"bi bi-trash-fill\"></i></a></td>");
        print("<td><a href=\"$theUploadFolder/$theBonsDeCommande[$i]\" target=\"_blank\"><i class=\"bi bi-file-earmark-pdf\" style=\"font-size:36px;\"></i>$theBonsDeCommande[$i]</a></td>");
        if($theAttachedFiles[$i]==""){
            print("<td></td>");           
        }
        else {
            $fileInfo=GetUploadedFileInfo($theUploadFolder,"", $theAttachedFiles[$i]);
            $filePath=$fileInfo["path"];
            $fileName=$fileInfo["name"];
            $fileSize=MemoryToString($fileInfo["size"]);
            $fileDate=$fileInfo["date"];
            $fileType=$fileInfo["type"];
            if(IsPictureFile($fileInfo["extension"])) {
                print("<td><a href=\"$theUploadFolder/$theAttachedFiles[$i]\" target=\"_blank\"><img src=\"$theUploadFolder/$theAttachedFiles[$i]\" width=\"40\" >$theAttachedFiles[$i]</a></td>");           
            }
            else {
                print("<td><a href=\"$theUploadFolder/$theAttachedFiles[$i]\" target=\"_blank\">$theAttachedFiles[$i]</a></td>");           
            }
        }
        print("</tr>");
    }
?>
</tbody>
</table>
</div><!-- table responsive -->
</div>  <!--hidden-->
<div class="mb-3">
</body>
</html>