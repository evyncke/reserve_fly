<?php
// This PHP script is fully integrated as a component of Joomla
require_once "dbi.php" ;

// Function CheckFileSize : Check all files size before upload 
// =====================
// $theFilesRequest: often $_Files
// $theInputFileName: the input type=file name
// $theMaxFileSize: the max size in bytes
// returns true if size of all files are OK

function CheckFileSize($theFilesRequest, $theInputFileName, $theMaxFileSize) 
{
	$totalSize=0;
	foreach ($theFilesRequest[$theInputFileName]["error"] as $key => $error) {
		if ($error == UPLOAD_ERR_OK) {
			$tmp_name = $theFilesRequest[$theInputFileName]["tmp_name"][$key];
			$size=filesize($tmp_name);
			//print("CheckFileSize: theMaxFileSize=$theMaxFileSize size=$size");
			if($size>$theMaxFileSize) {
				return false;
			}
			$totalSize+=$size;
		}
	}
	if($totalSize>$theMaxFileSize) {
		return false;
	}
	return true;
}

// Function UploadFiles : Upload files in a upload folder 
// =====================
// $theFilesRequest: often $_Files
// $theInputFileName: the input type=file name
// $theUploadFolder: the upload folder name
// $theFilePrefix: the uploaded file name starts with the prefix
function UploadFiles($theFilesRequest, $theInputFileName, $theUploadFolder, $theFilePrefix, $theMaxNumberOfPixels) 
{
	$returnValue=false;
	/* $ERROR values
	0 => 'There is no error, the file uploaded with success',
	1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
	2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
	3 => 'The uploaded file was only partially uploaded',
	4 => 'No file was uploaded',
	6 => 'Missing a temporary folder',
	7 => 'Failed to write file to disk.',
	8 => 'A PHP extension stopped the file upload.'
	*/
	foreach ($theFilesRequest[$theInputFileName]["error"] as $key => $error) {
		if ($error == UPLOAD_ERR_OK) {
			$tmp_name = $theFilesRequest[$theInputFileName]["tmp_name"][$key];
			//print_r($tmp_name);
			$name=basename($theFilesRequest[$theInputFileName]["name"][$key]);
			//moveAndResizePicture($tmp_file, $upload_fileName, $maxImageSize)
			//if(move_uploaded_file($tmp_name, $theUploadFolder."/".$theFilePrefix.$name)) {
			if(moveAndResizePicture($tmp_name, $theUploadFolder."/".$theFilePrefix.$name, $theMaxNumberOfPixels)) {
					if(!$returnValue) {
					$returnValue=true;
				}
			}
		}
		else if($error == UPLOAD_ERR_NO_FILE) {
			//print_r("UploadFiles: No file to upload!");
		}
		else {
			$errorString=UploadErrorString($error);
			print_r("UploadFiles: Upload Error: $errorString !!");
		}
	}
	return $returnValue;
}

// Function UploadErrorString : returns the description of the upload error
// ==========================
function UploadErrorString($theError) 
{
	$error="";
	switch ($theError) {
		case 0:
			$error=  "There is no error, the file uploaded with success";
			break;
		case 1:
			$error=  "The uploaded file exceeds the upload_max_filesize directive in php.ini";
			break;
		case 2:
			$error=  "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
			break;
		case 3:
			$error=  "The uploaded file was only partially uploaded";
			break;
		case 4:
			$error=  "No file was uploaded";
			break;
		case 6:
			$error=  "Missing a temporary folder";
			break;
		case 7:
			$error=  "Failed to write file to disk.";
			break;
		case 8:
			$error=  "A PHP extension stopped the file upload.";
			break;
		default:
			$error=  "Unknown Upload error !";
	}
	return $error;
}

// Function HasUploadedFiles : returns true if we have files in the upload folder starting by a prefix
// =====================
// $theUploadFolder: the upload folder name
// $theFilePrefix: the uploaded file name starts with the prefix
function HasUploadedFiles($theUploadFolder, $theFilePrefix) {
	$files = scandir($theUploadFolder);
	foreach ($files as $file) {
		$pos=strpos($file,$theFilePrefix);
		if( $pos !== false && $pos==0) {
			return true;
		}
	}
	return false;
}

// Function GetUploadedFileNames : returns an array of file names, we have files in the upload folder starting by a prefix
// =====================
// $theUploadFolder: the upload folder name
// $theFilePrefix: the uploaded file name starts with the prefix
function GetUploadedFileNames($theUploadFolder, $theFilePrefix) 
{
	$files = scandir($theUploadFolder);
	$uploadFileNames=array();
	foreach ($files as $file) {
		$pos=strpos($file,$theFilePrefix);
		if( $pos !== false && $pos==0) {
			$prefixLen=strlen($theFilePrefix);
            $fileName=substr($file,$prefixLen);
			array_push($uploadFileNames, $fileName);
		}
	}
	return $uploadFileNames;
}

// Function DeleteUploadedFile : delete a file in the upload folder
// $theUploadFolder: the upload folder name
// $theFilePrefix: the uploaded file name starts with the prefix
// $theFileName: the file name (Without prefix)
// return true if the file is deleted

function DeleteUploadedFile($theUploadFolder, $theFilePrefix, $theFileName) 
{
	$file=$theUploadFolder."/".$theFilePrefix.$theFileName;
	//print("DeleteUploadedFile: file=$file<br>");
	return unlink($file);
}

// Function GetUploadedFileInfo : returns an array of file info
// $theUploadFolder: the upload folder name
// $theFilePrefix: the uploaded file name starts with the prefix
// $theFileName: the file name (Without prefix)
// Returned values: 
// Array["name"]
// Array["path"]
// Array["size"]
// Array["date"]
// Array["extension"]
// Array["type"] : "picture" or extension for others
function GetUploadedFileInfo($theUploadFolder, $theFilePrefix, $theFileName) 
{
	$fileInfo=array();
	$filePath=$theUploadFolder."/".$theFilePrefix.$theFileName;
	if(file_exists($filePath)) {
		//print("GetUploadedFileInfo: the file $filePath exists");
		$fileInfo["name"]=$theFileName;
		$fileInfo["path"]=$filePath;
		$fileInfo["size"]=filesize($filePath);
		$fileInfo["date"]=date("d-m-Y H:i:s", filemtime($filePath));
		$fileInfo["extension"]=strtolower(pathinfo($filePath)["extension"]);
		$fileInfo["type"]=$fileInfo["extension"];
	}
	else {
		//print("GetUploadedFileInfo: the file $filePath doesn't exist");
	}
	//print("GetUploadedFileInfo: ");
	//print_r($fileInfo);
	//print("<br>");

	return $fileInfo;
}

function IsPictureFile($theFileNameExtension) 
{
	$pictureExtensions=array("jpg", "jpeg", "gif", "png", "tiff");
	foreach($pictureExtensions as $extension) {
		if($extension==$theFileNameExtension) {
			return true;
		}
	}
	return false;
}

//  Function: GetATLPrefixName : Returns the prefix from a incident id
//  ========
function GetATLPrefixName($theIncidentId) 
{
	return "ATL_".$theIncidentId."_";
}

//  Function: MemoryToString : Returns the memory size as a string
//  ========
function MemoryToString($theMemorySize) 
{
	$size=$theMemorySize;
	if($size<1024) {
		return number_format($size, 0)."b";
	}
	$size/=1024;
	if($size<10) {
		return number_format($size, 3)."Kb";
	}
	if($size<100) {
		return number_format($size, 2)."Kb";
	}
	if($size<1024) {
		return number_format($size, 1)."Kb";
	}
	$size/=1024;
	if($size<10) {
		return number_format($size, 3)."Mb";
	}
	if($size<100) {
		return number_format($size, 2)."Mb";
	}
	if($size<1024) {
		return number_format($size, 1)."Mb";
	}
	$size/=1024;
	if($size<10) {
		return number_format($size, 3)."Gb";
	}
	if($size<100) {
		return number_format($size, 2)."Gb";
	}
	return number_format($size, 1)."Gb";
}

function moveAndResizePicture($tmp_file, $upload_fileName, $maxImageSize)
{
	$image_size = getimagesize($tmp_file) ;
	if ($image_size === FALSE) {
		// The file is not a picture
		if (!move_uploaded_file($tmp_file, $upload_fileName)) {
			print("Impossible de déplacer le fichier $tmp_file to $upload_fileName") ;
			return false;
		}
		return true;
	}
	$image_width = $image_size[0] ;
	$image_height = $image_size[1] ;
	$image_type = $image_size[2] ;
	$image_basename = basename($tmp_file) ;
	$image_filetype = pathinfo($tmp_file, PATHINFO_EXTENSION) ;
	//$avatar_filename = "$_SERVER[DOCUMENT_ROOT]/$avatar_root_directory/users/avatar$displayed_id.$image_filetype" ;
	// Do we need to resize the image ?
	if ($image_width <= $maxImageSize and $image_height <= $maxImageSize) {
		if (!move_uploaded_file($tmp_file, $upload_fileName)) {
			print("Impossible de déplacer le fichier $tmp_file to $upload_fileName") ;
			return false;
		}
		return true;
	} 
	$resize_ratio = ($image_width > $image_height) ? $maxImageSize / $image_width  : $maxImageSize / $image_height;
	//print("moveAndResizePicture: image_width=$image_width  image_height=$image_height resize_ratio=$resize_ratio");
	switch ($image_type) {
		case IMAGETYPE_GIF: $upload_image = imagecreatefromgif($tmp_file) ; break ;
		case IMAGETYPE_JPEG: $upload_image = imagecreatefromjpeg($tmp_file) ; break ;
		case IMAGETYPE_PNG: $upload_image = imagecreatefrompng($tmp_file) ; break ;
		default:
			if (!move_uploaded_file($tmp_file, $upload_fileName)) {
				print("Impossible de déplacer le fichier $tmp_file to $upload_fileName") ;
				return false;
			}
			return true;
		}
	if (! $upload_image) {
		if (!move_uploaded_file($tmp_file, $upload_fileName)) {
			print("Impossible de déplacer le fichier $tmp_file to $upload_fileName") ;
			return false;
		}
		return true;
	}
	// print("<hr>" . ($image_width * $resize_ratio) . " x " . ($image_height * $resize_ratio) . " => $_SERVER[DOCUMENT_ROOT]/${avatar_root_directory}/users/avatar${displayed_id}.gif") ;
	$new_image = imagescale($upload_image, $image_width * $resize_ratio, $image_height * $resize_ratio) ;
	if (! $new_image) {
		if (!move_uploaded_file($tmp_file, $upload_fileName)) {
			print("Impossible de déplacer le fichier $tmp_file to $upload_fileName") ;
			return false;
		}
		return true;
	}
	imagedestroy($upload_image) ;
	switch ($image_type) {
		case IMAGETYPE_GIF: imagegif($new_image, $upload_fileName) ; $image_filetype = 'gif' ; break ;
		case IMAGETYPE_JPEG: imagejpeg($new_image, $upload_fileName) ; $image_filetype = 'jpg' ; break ;
		case IMAGETYPE_PNG: imagepng($new_image, $upload_fileName) ; $image_filetype = 'png' ; break ;
	}
	return true;
}
//  Function: getCompteurValueInMinute : Returns the compteur time of a aircraft for a date filter
//  ======== 
//  $thePlane: ex "OO-ALD"
//  $theDateFilter: ex "2024-%" all flights of 2024 of the plane
//                     "2024-01-%" all flights of janauary 2024
function getCompteurValueInMinute($thePlane, $theDateFilter)
{
	global $mysqli_link, $table_logbook;
	//print("getCompteurValueInMinute thePlane=$thePlane theDateFiltert=$theDateFilter<br>");
	$minHour=0;
	$minMinute=0;
	$maxHour=0;
	$maxMinute=0;
	$sql="SELECT l_plane,l_start, l_start_hour , l_start_minute FROM $table_logbook WHERE l_start like '$theDateFilter' and l_plane='$thePlane' ORDER BY l_start asc LIMIT 1";
	$result = mysqli_query($mysqli_link, $sql) ;
	while ($row = mysqli_fetch_array($result)) {
		$minHour=$row['l_start_hour'];
		$minMinute=$row['l_start_minute'];
		//print("$plane $minHour : $minMinute  startdate=$row[l_start]<br>\n") ;
	}
	$sql="SELECT l_plane,l_end, l_end_hour , l_end_minute FROM $table_logbook WHERE l_end like '$theDateFilter' and l_plane='$thePlane' ORDER BY l_end desc LIMIT 1";
	$result = mysqli_query($mysqli_link, $sql) ;
	while ($row = mysqli_fetch_array($result)) {
		$maxHour=$row['l_end_hour'];
		$maxMinute=$row['l_end_minute'];
		//print("$plane $maxHour : $maxMinute  startdate=$row[l_end]<br>\n") ;
	}
	
	$timeInMinute=($maxHour-$minHour)*60+$maxMinute-$minMinute;
	$timeHour=intval($timeInMinute/60);
	$timeMinute=$timeInMinute-$timeHour*60;
	//print("timeInMinute=$timeInMinute  timeHour=$timeHour:$timeMinute <br>");
	return $timeInMinute;
}

//  Function: convertMinuteToHour : Returns the time in minutes into time in hours:Minutes
//  ======== 
//  $theTimeInMinute: ex 135
//  returns 2:15
function convertMinuteToHour($theTimeInMinute)
{
	$timeHour=intval(abs($theTimeInMinute)/60);
	$timeMinute=abs($theTimeInMinute)-$timeHour*60;
	$timeString="";
	if($timeMinute<10) {
		$timeString= "$timeHour:0$timeMinute";
	}
	else {
		$timeString= "$timeHour:$timeMinute";
	}
	if($timeHour<10) {
		$timeString="0".$timeString;
	}
	if($theTimeInMinute<0) {
		$timeString="-".$timeString;
	}
	return $timeString;
}

//  Function: getCompteurIFValueInMinute : Returns the total compteur time for IF flight of a aircraft for a date filter
//  ======== 
//  $thePlane: ex "OO-ALD"
//  $theDateFilter: ex "2024-%" all IF flights of 2024 of the plane
//                     "2024-01-%" all IF flights of janauary 2024
function getCompteurIFValueInMinute($thePlane, $theDateFilter)
{
	return getCompteurIfInitDhfValueInMinute($thePlane, $theDateFilter, "IF");
}

//  Function: getCompteurDHFValueInMinute : Returns the total compteur time for DHF flight of a aircraft for a date filter
//  ======== 
//  $thePlane: ex "OO-ALD"
//  $theDateFilter: ex "2024-%" all IF flights of 2024 of the plane
//                     "2024-01-%" all IF flights of janauary 2024
function getCompteurDHFValueInMinute($thePlane, $theDateFilter)
{
	return getCompteurIfInitDhfValueInMinute($thePlane, $theDateFilter, "DHF");
}

//  Function: getCompteurINITValueInMinute : Returns the total compteur time for INIT flight of a aircraft for a date filter
//  ======== 
//  $thePlane: ex "OO-ALD"
//  $theDateFilter: ex "2024-%" all IF flights of 2024 of the plane
//                     "2024-01-%" all IF flights of janauary 2024
function getCompteurINITValueInMinute($thePlane, $theDateFilter)
{
	return getCompteurIfInitDhfValueInMinute($thePlane, $theDateFilter, "INIT");
}

//  Function: getCompteurIfInitDhfValueInMinute : Returns the total compteur time for IF-DHF-INIT flights of a aircraft for a date filter
//  ======== 
//  $thePlane: ex "OO-ALD"
//  $theDateFilter: ex "2024-%" all IF flights of 2024 of the plane
//                     "2024-01-%" all IF flights of janauary 2024
//  $theFlightType= {"IF", "DHF", "INIT"}
function getCompteurIfInitDhfValueInMinute($thePlane, $theDateFilter, $theFlightType)
{
		global $mysqli_link, $table_logbook,  $table_flight, $table_person, $table_flights_ledger,$table_bookings,$userId ;
	$filterType = 'f_type ="D"' ;
	if($theFlightType=="INIT") {
		$filterType = 'f_type ="I"' ;
	}
	$sql="SELECT DISTINCT l_start, l_start_hour, l_start_minute, l_end_hour, l_end_minute, f_invoice_ref, 
	l_plane, f_date_flown, first_name, last_name, r_plane, f_reference 
	FROM rapcs_flight AS f 
	JOIN rapcs_person ON f.f_pilot = jom_id 
	JOIN rapcs_bookings AS b ON f.f_booking = b.r_id 
	JOIN rapcs_logbook AS l ON f.f_booking = l.l_booking 
	WHERE l_plane='$thePlane' AND $filterType AND f_date_flown IS NOT NULL 
	AND f_date_flown like '$theDateFilter' ORDER BY f_date_flown";

	//print("getCompteurIFValueInMinute $thePlane, $theDateFilter sql=$sql<br>");

	//print("sql=$sql<br>");
	$montantTotal=0.0;

	$result = mysqli_query($mysqli_link, $sql) 
			or journalise($userId, "F", "Impossible de lister les vols IF: " . mysqli_error($mysqli_link));

	$timeTotal=0;
	while ($row = mysqli_fetch_array($result)) {
       $invoiceRef=$row['f_invoice_ref'];
        $referenceFlight=$row['f_reference'];
		$toBeUsed=true;
		$pos = strpos(strtoupper($referenceFlight), "DHF-");
		if($pos===false) {
			if($theFlightType=="DHF") {
				$toBeUsed=false;
			}
		}
		else {
			if($theFlightType!="DHF") {
				$toBeUsed=false;
			}
		}
		//print("getCompteurIfInitDhfValueInMinute $thePlane  $theDateFilter, $theFlightType $referenceFlight $pos=$pos toBeUsed=$toBeUsed<br>");
		if($toBeUsed) {
			$minHour=$row['l_start_hour'];
			$minMinute=$row['l_start_minute'];
			$maxHour=$row['l_end_hour'];
			$maxMinute=$row['l_end_minute'];
			$timeInMinute=($maxHour-$minHour)*60+$maxMinute-$minMinute;
			$timeHour=intval($timeInMinute/60);
			$timeMinute=$timeInMinute-$timeHour*60;
			$timeTotal+=$timeMinute;
		}
	}
	
	return $timeTotal;
}
?>