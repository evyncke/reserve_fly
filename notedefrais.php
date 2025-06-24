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

/*
note de frais: account.move
id : 11952
name: BILL/2025/04/0024
document attache: 9240 Note_de_frais_16-04-25.pdf
*/

require_once "dbi.php" ;
require_once "odooFlight.class.php" ;
require_once "mobile_tools.php" ;
if ($userId == 0) {
	header("Location: https://www.spa-aviation.be/resa/mobile_login.php?cb=" . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) , TRUE, 307) ;
	exit ;
}
require_once 'mobile_header5.php' ;
require_once 'dto.class.php' ;
//print("REQUEST=");
//var_dump($_REQUEST);
//print("<br>");
if (isset($_REQUEST['delete'])) {
    $deleteFile = $_REQUEST['delete'] ;
    $deleteAttachedFile=substr($deleteFile,0,strlen($deleteFile)-4);
    //print("deleteFile=$deleteFile deleteAttachedFile=$deleteAttachedFile<br>");
    $uploadNoteDeFraisFolder="uploads/notedefrais";
    $deleteFile=$uploadNoteDeFraisFolder."/".$deleteFile;
    $flag=false;
    if(file_exists($deleteFile)) {
        //print("1unlink($deleteFile)<br>");
        unlink($deleteFile);
        $flag=true;;
 
    }
    $files = scandir($uploadNoteDeFraisFolder);
    foreach($files as $file) {
        if(substr($file,0,strlen($deleteAttachedFile))==$deleteAttachedFile){
            //print("2unlink($uploadNoteDeFraisFolder/$file)<br>");
            unlink($uploadNoteDeFraisFolder."/".$file);
            $flag=true;;
        }
    }
    if($flag) {
   	    print("<h2 style=\"color: red;\"><b>La note de frais $_REQUEST[delete] est supprimée sur le serveur du club (Pas dans ODOO).</b></h2> <p></p>");
    }
    else {
   	    print("<h2 style=\"color: red;\"><b>ERREUR: La note de frais $_REQUEST[delete] n'est pas supprimée sur le serveur du club.</b></h2> <p></p>");       
    }
}
$remboursable=0;
if (isset($_REQUEST['notedefrais_input_remboursable'])) {
    $remboursable = $_REQUEST['notedefrais_input_remboursable'] ;
}
$memberID=$userId;
if (isset($_REQUEST['member_name'])) {
    $memberID = $_REQUEST['member_name'] ;
    //print("memberID=$memberID<br>");
}

if (isset($_REQUEST['notedefrais_json'])) {
    $ndfJSON = $_REQUEST['notedefrais_json'] ;
    $uploadFileFolder="";
    $factureMailTo="";
    //print("ndf in JSON=$ndfJSON<br>");
    $noteDeFraisPDF=OF_createNoteDeFrais($memberID, $ndfJSON, $remboursable,$_FILES, $uploadFileFolder, $factureMailTo);
    if($noteDeFraisPDF!="") {
    	print("<h2 style=\"color: red;\"><b>La note de frais <a href=\"$uploadFileFolder/$noteDeFraisPDF\">$noteDeFraisPDF</a> est créée et envoyée à $factureMailTo</b></h2>");
     	print("<h3>Download de la note de frais: <b><a href=\"$uploadFileFolder/$noteDeFraisPDF\" download>DOWNLOAD</a></b></h3><p></p>");
   }
    else {
	    print("<h2 style=\"color: red;\"><b>Erreur lors de la creation de la note de frais</b></h2>");
    }

} else {
    $ndf = NULL ;
}

$notedefraisJSONcontent = file_get_contents('https://www.spa-aviation.be/resa/notedefrais.json') ;
$notedefraisJSONcontent = str_replace("\n","",$notedefraisJSONcontent);
//print("Notedefrais json fileJSON=$notedefraisJSONcontent");
print("<script>\nvar notedefraisJSONString='$notedefraisJSONcontent';");
print("var default_member=$userId;\n");
print("</script>\n");

$selectorDisabled="disabled";
if ($userIsAdmin or $userIsBoardMember) $selectorDisabled="";


//print("<script>var notedefraisJSONString='toto';</script>");
?>
<h2>Introduction d'une note de frais</h2>
<br>
<form action="<?=$_SERVER['PHP_SELF']?>" method="post" role="form" class="form-horizontal" enctype="multipart/form-data">
<div class="row">
<div class="col-sm-12 col-md-12 col-lg-7">
Bénéficiaire: 
<select id="id_member_name" name="member_name" <?=$selectorDisabled?>>
<option selected="selected" value=""></option>
</select>
<p></p>
<div>
    <label class="form-label">Type de note de frais :</label>
    <select id="id_notedefrais_input_remboursable" name="notedefrais_input_remboursable">
            <option selected="selected" value=""></option>
            <option value="1">remboursable sur compte bancaire</option>
            <option value="0">remboursable sur compte pilote</option>
    </select>
 </div>
<div>
<table style="width:100%;" class="table table-striped" name="table_notedefrais" id="id_table_notedefrais">
<thead style="text-align: Center;">
<th>Action</th>
<th>Date</th>
<th>Type</th>
<th>Description</th>
<th>Quantité</th>
<th>Prix unitaire €</th>
<th>Montant €</th>
<th>Imputation</th>
<th>Analytique</th>
</thead>
<tbody class="table-group-divider">
<tr id="id_notedefrais_rowinput">
    <td>
        <a href="javascript:void(0);" onclick="deleteNoteDeFraisLine(-1)" title="Effacer cette ligne"><i class="bi bi-trash-fill"></i></a>
    </td>
    <td><input type="date" id="id_notedefrais_input_date" name="notedefrais_input_date" value="2025-05-17" size="10"></input></td>
    <td><select id="id_notedefrais_input_type" name="notedefrais_input_type">
        <option selected="selected" value=""></option>
    </select></td>
    <td><input type="text" id="id_notedefrais_input_description" name="notedefrais_input_description" ></input></td>
    <td><input type="number" id="id_notedefrais_input_quantity" name="notedefrais_input_quantity" min="0.0" 
        max="5000.0" step="1.0"></input></td>
    <td><input type="number" id="id_notedefrais_input_unitaryprice" name="notedefrais_input_unitaryprice" min="0.00" 
        max="5000.00" step="1.00"></input></td>
    <td><input type="number" id="id_notedefrais_input_total" name="notedefrais_input_total"></input></td>
    <td><input type="text" id="id_notedefrais_input_odooreference" name="notedefrais_input_odooreference"></input></td>
    <td><input type="text" id="id_notedefrais_input_odooanalytic" name="notedefrais_input_odooanalytic"></input></td>
    </tr>
<tr><td></td>
<td colspan="3"><a role="button" href="#" name="add_row" id="id_add_row">Ajouter une ligne</a><td></td><td>Montant Total:</td><td><span id="id_notedefrais_input_grandtotal">0.00€</span></td><td></td><td></td></tr>
</tbody>
</table>
</div><!-- table responsive -->
<div class="mb-3">

    <label for="id_notedefrais_input_justificatif" class="form-label">Joindre un justificatif (PDF ou image) :</label>
    <input type="file" class="form-control" id="id_notedefrais_input_justificatif" name="notedefrais_input_justificatif" accept="application/pdf,image/jpeg,image/png">
    <!---<img id="id_notedefrais_image" src="" width="60">-->
 </div>
<br>
<!---<input type="hidden" id="id_notedefrais_json" name="notedefrais_json" value="[{}]">-->
<input type="hidden" id="id_notedefrais_json" name="notedefrais_json" value="[{}]">
<br>
<?php
//print("<button class=\"btn btn-primary\" onclick=\"submitNodeDeFrais('$_SERVER[PHP_SELF]');\">Créer la note de frais dans ODOO</button>");
print("<button id=\"id_submit_notedefrais\" name=\"submit_notedefrais\" class=\"btn btn-primary\">Envoyer la note de frais</button>");
?>
</form>
</div><!-- col -->
</div><!-- row --> 
<?php
    $hiddenGestion="hidden";
    if ($userIsAdmin or $userIsBoardMember) $hiddenGestion="";
    require_once "mobile_tools.php" ;
    $theNotesDeFrais=array();
    $theAttachedFiles=array();
    $theUploadFolder="";
    $nodeDeFraisCount=MT_GetNoteDeFraisFiles($theNotesDeFrais, $theAttachedFiles, $theUploadFolder);
?>
<div <?=$hiddenGestion?>>
<p></p>
<h2>Gestion des fichiers associés aux notes de frais sur le serveur du club</h2>
<br>
<div class="row">
<div class="col-sm-12 col-md-12 col-lg-7">
<div>
<table style="width:100%;" class="table table-striped" name="table_notedefrais" id="id_table_notedefrais">
<thead style="text-align: Center;">
<th>Action</th>
<th>Note de frais</th>
<th>Justificatif</th>
</thead>
<tbody class="table-group-divider">
<?php
    for($i=0;$i<$nodeDeFraisCount;$i++) {
        print("<tr>");
        print("<td><a href=\"javascript:void(0);\" onclick=\"deleteNoteDeFraisFiles('$_SERVER[PHP_SELF]','$theNotesDeFrais[$i]')\" title=\"Effacer les fichiers associés à la note de frais sur le serveur\"><i class=\"bi bi-trash-fill\"></i></a></td>");
        print("<td><a href=\"$theUploadFolder/$theNotesDeFrais[$i]\" target=\"_blank\"><i class=\"bi bi-file-earmark-pdf\" style=\"font-size:36px;\"></i>$theNotesDeFrais[$i]</a></td>");
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

<!-- already loaded in by mobile_header5.php script src="https://www.spa-aviation.be/resa/members.js"></script-->
<script src="https://www.spa-aviation.be/resa/notedefrais.js"></script>
</body>
</html>