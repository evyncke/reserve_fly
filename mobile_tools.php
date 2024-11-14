<?php
// This PHP script is fully integrated as a component of Joomla
require_once "dbi.php" ;

// Function CheckFileSize : Check all files size before upload 
// =====================
// $theFilesRequest: often $_Files
// $theInputFileName: the input type=file name
// $theMaxFileSize: the max size in bytes
// returns true if size of all files are OK

function CheckFileSize($theFilesRequest, $theInputFileName, $theMaxFileSize) {
	foreach ($theFilesRequest[$theInputFileName]["error"] as $key => $error) {
		if ($error == UPLOAD_ERR_OK) {
			$tmp_name = $theFilesRequest[$theInputFileName]["tmp_name"][$key];
			$size=filesize($tmp_name);
			//print("CheckFileSize: theMaxFileSize=$theMaxFileSize size=$size");
			if($size>$theMaxFileSize) {
				return false;
			}
		}
	}
	return true;
}

// Function UploadFiles : Upload files in a upload folder 
// =====================
// $theFilesRequest: often $_Files
// $theInputFileName: the input type=file name
// $theUploadFolder: the upload folder name
// $theFilePrefix: the uploaded file name starts with the prefix
function UploadFiles($theFilesRequest, $theInputFileName, $theUploadFolder, $theFilePrefix) {
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

			if(move_uploaded_file($tmp_name, $theUploadFolder."/".$theFilePrefix.$name)) {
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
function UploadErrorString($theError) {
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
function GetUploadedFileNames($theUploadFolder, $theFilePrefix) {
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

function DeleteUploadedFile($theUploadFolder, $theFilePrefix, $theFileName) {
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
function GetUploadedFileInfo($theUploadFolder, $theFilePrefix, $theFileName) {
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

function IsPictureFile($theFileNameExtension) {
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
function GetATLPrefixName($theIncidentId) {
	return "ATL_".$theIncidentId."_";
}

//  Function: MemoryToString : Returns the memory size as a string
//  ========
function MemoryToString($theMemorySize) {
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
?>