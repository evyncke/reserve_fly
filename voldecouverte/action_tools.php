<?php
// This PHP script is fully integrated as a component of Joomla
// Developped by Patrick Reginster in 2019
//include "../../resa/dbi.php" ;
require_once "../dbi.php" ;

function remove_accents($text) {
	$from = explode(" ",""
		." À Á Â Ã Ä Å Ç È É Ê Ë Ì Í Î Ï Ñ Ò Ó Ô Õ Ö Ø Ù Ú Û Ü Ý à á â"
		." ã ä å ç è é ê ë ì í î ï ñ ò ó ô õ ö ø ù ú û ü ý ÿ Ā ā Ă ă Ą"
		." ą Ć ć Ĉ ĉ Ċ ċ Č č Ď ď Đ đ Ē ē Ĕ ĕ Ė ė Ę ę Ě ě Ĝ ĝ Ğ ğ Ġ ġ Ģ");
	$to = explode(" ",""
		." A A A A A A C E E E E I I I I N O O O O O O U U U U Y a a a"
		." a a a c e e e e i i i i n o o o o o o u u u u y y A a A a A"
		." a C c C c C c C c D d D d E e E e E e E e E e G g G g G g G");
	return str_replace( $from, $to, $text);
}

function compute_date($date)
{
	// On some browser: date = yyyy-mm-dd and other dd-mm-yyyy
	//echo("compute_date:fligtdate=".$date."<br/>");
	$computeddate=$date;
	if(!empty($date)) {
		$separator=substr($date,2,1);
		//echo("compute_date:separator=".$separator."<br/>");
		if($separator!="-" && $separator!="/") {
	       $computeddate=substr($date,8,2)."-".substr($date,5,2)."-".substr($date,0,4);
	    }
		else {
 	       $computeddate=substr($date,0,2)."-".substr($date,3,2)."-".substr($date,6,4);			
		}
	}
	//echo("compute_date:2fligtdate=".$computeddate."<br/>");
	return $computeddate;
}

function circuit_name($circuitnumber)
{
	// Get the contents of the JSON file 
	$strJsonFileContents = file_get_contents("./circuits.js");
	// Convert to array 
	$array = json_decode($strJsonFileContents, true);
	//var_dump($array); // print array
	//echo("circuit_name:len=".count($array)."<br/>");
	//echo("circuit_name:name=".$array[$circuitnumber]."<br/>");	
	$name=$array[$circuitnumber];
	return $name;
}

function plages_horaire($plageshorairenumber)
{
	// Get the contents of the JSON file 
	$strJsonFileContents = file_get_contents("./plageshoraire.js");
	// Convert to array 
	$array = json_decode($strJsonFileContents, true);
	//var_dump($array); // print array
	//echo("circuit_name:len=".count($array)."<br/>");
	//echo("circuit_name:name=".$array[$circuitnumber]."<br/>");	
	$name=$array[$plageshorairenumber];
	return $name;
}

function check_data($contactmail,$firstname1,$lastname1,$contactphone,$flightdate,$typeofflight)
{
	if(empty($contactmail)) {
		return "Erreur: Introduisez une adresse email";
	}
	if (!filter_var($contactmail, FILTER_VALIDATE_EMAIL)) {
	  return "Erreur : votre adresse email est incorrecte"; 
	}
	if(empty($contactphone)) {
		return "Erreur: Introduisez un num&eacute;ro de t&eacute;l&eacute;phone";
	}
	if(empty($firstname1) or empty($lastname1)) {
		return "Erreur: Vous n'avez pas introduit un nom et un prenom";
	}
	if($typeofflight=="vol_decouverte") {
		if(empty($flightdate)) {
			return "Erreur: Introduisez une date souhait&eacute;e pour votre vol";
		}
		$date = strtotime($flightdate)+(1 * 24 * 60 * 60)-1;
		//echo ("Date=".date('d/M/Y H:i:s', $date)."<br/>");
		if($date<time()) {
			return "Erreur: Introduisez une date dans le futur";
		}
	}
	return "";
}


function create_csvfile($contactmail,$contactphone, 
$flighttype,$flightdate,$flightdate2,$flighttime,$numberofpassagers,$circuit,
$firstname1,$lastname1,$age1,$weight1,$rapcsmember,
$firstname2,$lastname2,$age2,$weight2,
$firstname3,$lastname3,$age3,$weight3,
$remarques,$language)
{
	
	$csv="Type de vol,".$flighttype."\r\n";
	$csv.="Mail,".$contactmail."\r\n";
	$csv.="Langue,".$language."\r\n";
	$csv.="Telephone,'".$contactphone ."'\r\n";
	$csv.="Nombre de passagers,".$numberofpassagers."\r\n";
	$csv.="Passager 1,".$firstname1 ."," . $lastname1 . ",". $age1.",".$weight1."\r\n";
	$csv.="Passager 2,".$firstname2 ."," . $lastname2 . ",". $age2.",".$weight2. "\r\n";
	$csv.="Passager 3,".$firstname3 ."," . $lastname3 . ",". $age3.",".$weight3."\r\n";
	$csv.="Circuit,".$circuit ."\r\n";
	$csv.="Date,".$flightdate .",".$flightdate2."\r\n";
	$csv.="Heure,".$flighttime."\r\n";
	$csv.="Membre,".$rapcsmember."\r\n";
	if(count($remarques)==0) {
		$csv.="Remarques\r\n";
	}
	else {
	   for($i = 0; $i < count($remarques); ++$i) {
		   if($i==0) {
		      $csv.="Remarques,".$remarques[$i]."\r\n";
		   }
		   else {
 		      $csv.=",".$remarques[$i]."\r\n";
	       }
	   }
	}
	$csv=remove_accents($csv);
	return $csv;
}

function mail_attachment($mailto, $from_mail, $from_name, $replyto, $subject, $message,
     $filename, $path, $filecontent) 
{
	//echo("mail_attachment:From=".$from_mail."<br/>");
	if(false) {
	    //echo("mail_attachment Starting<br/>"); // or use booleans here
		if($filecontent == "") {
		   $headers="";
		   $headers .= 'From: "reservation"<no-reply@spa-aviation.be>\r\n';
	  	   //$headers .= "Reply-To: ".$replyto."\r\n";
	  	   $headers .= "CC: ".$replyto."\r\n";
		   $headers .= "MIME-Version: 1.0\r\n";
		   $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
		   //echo("Replyto=".$replyto."<br/>");
		   //if(mail($mailto, $subject, $message, $headers)) {
		   if(smtp_mail($mailto, $subject, $message, $headers)) {
		   //if(mail($mailto, $subject, $message, $headers)) {
	         //echo("mail 1 send ... OK<br/>"); // or use booleans here
	       } else {
	         echo("mail 1 send ... ERROR!<br/>");
	       }
	    }
	    else {
			//echo("mail_attachment 3 : filecontent=".$filecontent."<br/>");
			$headers="";
		//$headers = "From: vol.decouverte \r\n";
	       $headers .= 'From: "reservation"<no-reply@spa-aviation.be>\r\n';
	  	   //$headers .= "Reply-To: ".$replyto."\r\n";
	  	   $headers .= "CC: ".$replyto."\r\n";
		   $headers .= "MIME-Version: 1.0\r\n";
	       $headers .= "Content-Type: text/csv; name=".$filename."\r\n"; 
	       $headers .= "Content-Disposition: attachment; filename=".$filename."\r\n"; 
		   //$headers .= "Content-Type: text/txt; charset=ISO-8859-1\r\n";
		   //echo($headers);
		   //echo("<br/>ENDOF HEADERS <br/>");
		   //echo("Replyto=".$replyto."<br/>");
		   //if(mail($mailto, $subject, $filecontent, $headers)) {
		   if(smtp_mail($mailto, $subject, $filecontent, $headers)) {
	         //echo("mail 1 send ... OK<br/>"); // or use booleans here
	       } else {
	         echo("mail (2) send with ERROR! Please contact vols.decouvertes.spa@gmail.com<br/>");
	       }
	    }
	}
	else {
	    //echo("mail_attachment Starting<br/>"); // or use booleans here
		//$From='"Reservation Spa-Aviation"<reservation@spa-aviation.be>';
		//$From='"'.$from_name.'"<'.$from_mail.'>';
		//echo("Mail:From=".$from_mail."<br/>");
		//echo("Mail:Mailto=".$mailto."<br/>");
        if($filecontent == "") {
		   $headers="";
		   if($from_mail != "") {
		      $headers .= "From: ".$from_mail."\r\n";
	       }
	  	   //$headers .= "Reply-To: ".$replyto."\r\n";
	  	   $headers .= "Cc: ".$replyto."\r\n";
	  	   //$headers .= "Reply-To: ".$replyto."\r\n";
		   $headers .= "MIME-Version: 1.0\r\n";
		   $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
		   //echo("Replyto=".$replyto."<br/>");
		   //if(mail($mailto, $subject, $message, $headers)) {
		   if(smtp_mail($mailto, $subject, $message, $headers)) {
		   //if(mail($mailto, $subject, $message, $headers)) {
	         //echo("mail 1 send ... OK<br/>"); // or use booleans here
	       } else {
	         echo("mail 1 send ... ERROR!<br/>");
	       }
	    }
	    else {
			//echo("mail_attachment 3 : filecontent=".$filecontent."<br/>");
			$headers="";
		    //$headers = "From: vol.decouverte \r\n";
	        if($from_mail != "") {
	          $headers .= "From: ".$from_mail."\r\n";
            }		
	       //$headers .= "From: ".$from_mail."\r\n";
	  	   //$headers .= "Reply-To: ".$replyto."\r\n";
	  	   $headers .= "Cc: ".$replyto."\r\n";
	  	   //$headers .= "Reply-To: ".$replyto."\r\n";
		   $headers .= "MIME-Version: 1.0\r\n";
	       $headers .= "Content-Type: text/csv; name=".$filename."\r\n"; 
	       $headers .= "Content-Disposition: attachment; filename=".$filename."\r\n"; 
		   //$headers .= "Content-Type: text/txt; charset=ISO-8859-1\r\n";
		   //echo($headers);
		   //echo("<br/>ENDOF HEADERS <br/>");
		   //echo("Replyto=".$replyto."<br/>");
		   //if(mail($mailto, $subject, $filecontent, $headers)) {
		   if(smtp_mail($mailto, $subject, $filecontent, $headers)) {
	         //echo("mail 1 send ... OK<br/>"); // or use booleans here
	       } else {
	         echo("mail (2) send with ERROR! Please contact vols.decouvertes.spa@gmail.com<br/>");
	       }
	    }
	}
	//echo("end of mail_attachement<br/>");
    return;
}
?>