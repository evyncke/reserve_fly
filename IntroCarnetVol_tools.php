<?php

function GetCompteurHour($Compteur) {
	$aPos=strpos($Compteur,".");
	if(!$aPos) {
		return NULL;
	}
	return substr($Compteur,0,$aPos);
}

function GetCompteurMinute($Compteur) {
	$aPos=strpos($Compteur,".");
	if(!$aPos) {
		return NULL;
	}
	return substr($Compteur,$aPos+1,strlen($Compteur)-$aPos-1);
}

function GetDayTime($Date, $Time) {
	return $Date." ".$Time;
}

function CheckVar($var) {
	if(!isset($var)) {
		$var='NULL';
	}
	else if(empty($var)) {
		$var='NULL';
	}
	return $var;
}
// Compute the remark associated to the CP
function GetFullRemarks( $theFraisCP,  $thePAX, $theRemarque, $theFraisDC) {
	$aRemark="";
	if($theFraisCP != "") {
		$aRemark = $theFraisCP;
		if($thePAX == -1) {
			$aRemark = $aRemark . " Ferry";				
		}
		else if($thePAX == -2) {
			$aRemark = $aRemark . " Club";				
		}
		else if($thePAX == -3) {
			$aRemark = $aRemark . " Initiation";				
		}
		else if($thePAX == -4) {
			$aRemark = $aRemark . " Vol IF";				
		}
		else if($thePAX == -5) {
			$aRemark = $aRemark . " Vol membre";				
		}
		else if($thePAX == -6) {
			$aRemark = $aRemark . " Vol D.H.F.";				
		}
		else if($thePAX == -7) {
			$aRemark = $aRemark . " Vol PR";				
		}
		else if($thePAX == -8) {
			$aRemark = $aRemark . " Mécano";				
		}
		else  {
			$aRemark = $aRemark . " PAX ".strval($thePAX);				
		}
	}
	
	if($theRemarque != "") {
		if($aRemark != "") {
			$aRemark = $aRemark." ";
		}
		$aRemark = $aRemark.$theRemarque;
	}
	if($theFraisDC != "DC") {
		if($aRemark != "") {
			$aRemark = $aRemark." ";
		}
		$aRemark = $aRemark.$theFraisDC;
	}
	return $aRemark;
}
?>