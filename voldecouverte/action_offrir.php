<!DOCTYPE html>
<html>
<head>
		   <script>
		    function goBack() {
		      window.history.back();
		    }
		   </script>
<title>Résumé de votre demande (InProgress)</title>
<!-- Matomo -->
<script type="text/javascript">
  var _paq = window._paq = window._paq || [];
  /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
  _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
  _paq.push(["setDomains", ["*.spa-aviation.be","*.ebsp.be","*.m.ebsp.be","*.m.spa-aviation.be","*.resa.spa-aviation.be"]]);
  _paq.push(['enableHeartBeatTimer']);
  _paq.push(["setCookieDomain", "*.spa-aviation.be"]);
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="//analytics.vyncke.org/";
    _paq.push(['setTrackerUrl', u+'matomo.php']);
    _paq.push(['setSiteId', '5']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Code -->
</head>
<body>
<?php
// This PHP script is fully integrated as a component of Joomla
// Developped by Patrick Reginster in 2019
// Updated Eric Vyncke 2022
//
require_once 'flight_tools.php' ; // it also includes the load of dbi.php

$language=$_POST["form_language"];
$typeofgift=$_POST["typeofgift"];
$numberofpassagers=$_POST["numberofpassagers"];

$firstname1=$_POST["firstname1"];
$lastname1=$_POST["lastname1"];
if($firstname1=="James" && $lastname1=="Smith") {
	exit;
}
$firstname2=$_POST["firstname2"];
$lastname2=$_POST["lastname2"];
$circuitnumber=$_POST["circuit"]+1;
$circuit=circuit_name($circuitnumber);
$valeur_bon=$_POST["valeur_bon"];
$valeur_bon_libre=$_POST["valeur_bon_libre"];
$valeur_versement=$_POST["valeur_virement"];
$destinataire_option_name=$_POST["nom_destinataire"];
$rue=$_POST["rue"];
$boitelettre=$_POST["boitelettre"];
$codepostal=$_POST["codepostal"];
$ville=$_POST["ville"];
$pays=$_POST["pays"];

$contactmail=$_POST["contactmail"];
$contactphone=$_POST["contactphone"];
$destinatairemail=$_POST["destinatairemail"];
$destinatairephone=$_POST["destinatairephone"];
$voldansles12mois=$_POST["voldansles12mois"];
$remarque=$_POST["remarque"];
$messageforgift=$_POST["message"];
$rapcsmember="non";
if($voldansles12mois=="oui") {
	$rapcsmember="oui";
}
$remarques=explode ( "\n" ,$remarque);
$messagesforgift=explode ( "\n" ,$messageforgift);

$from='"Reservation RAPCS asbl" <reservation@spa-aviation.be>';
$to = '"Reservation RAPCS asbl" <reservation@spa-aviation.be>';
$replyto='"Reservation RAPCS asbl" <reservation@spa-aviation.be>';
$sender='"Reservation RAPCS asbl" <reservation@spa-aviation.be>';

$testingto='"Reservation RAPCS asbl" <patrick.reginster@me.com>';
$testingreplyto='"Reservation RAPCS asbl" <patrick.reginster@me.com>';

$testingflag=false;

$falsemail="";
if(substr($contactmail,0,8)=="testpat$") {
  $to = $testingto;
  $replyto=$testingreplyto;
  $contactmail=substr($contactmail,8); 
}
if(substr($contactmail,0,5)=="test$") {
  $contactmail=substr($contactmail,5);
  $to = 'eric.vyncke@uliege.be' ;
  $replyto = $to ; 
}
if($testingflag) {
  $falsemail=" (Warning: This is not a true gift)";
}
if($language=="french") {
	$subject = 'Cadeau: Type '.$typeofgift .$falsemail;
}
else {
	$subject = 'Gift Type ';
	if($typeofgift =="bon_valeur") {
		$subject .="voucher";
	} else if($typeofgift =="vol_initiation") {
		$subject .="InitiatingFlight";		
	}
	else {
		$subject .="Introductory Flight (circuit)";		
	}
	$subject .=$falsemail;	
}
$today = date("j-n-Y"); 
/* Date= tommorow */
$errormessage=check_data($contactmail,$firstname1,$lastname1,$contactphone,date('j-n-Y',time()+(1 * 24 * 60 * 60)));
if(!empty($errormessage) || empty($valeur_versement)) {
	if(empty($errormessage)) {
		if($language=="french") {
		  $errormessage="Vous avez oublié d'introduire une valeur pour le bon. Complétez le formulaire";
	    } else {
  		  $errormessage="You forget to introduce a value for the voucher. Introduce a value in the form.";			
		}
	}
	$MessageAnswer=$errormessage;
} else {	
	// Now let's try to insert the data in the data base
	journalise(0, 'D', "Discovery flight ($typeofgift) about to be created for $_REQUEST[contactmail] $lastname1 $firstname1 ($remarque)") ;
	if ($typeofgift == "vol_decouverte") 
		$flight_type = 'D' ;
	else if ($typeofgift == "vol_initiation") 
		$flight_type = 'I' ; 
	else 
		$flight_type = '?' ;
	mysqli_query($mysqli_link, "INSERT INTO $table_pax (p_lname, p_fname, p_email, p_tel, p_street, p_zip, p_city)
			VALUES(
			'" . mysqli_real_escape_string($mysqli_link, web2db($lastname1)) . "',
			'" . mysqli_real_escape_string($mysqli_link, web2db($firstname1)) . "',
			'" . mysqli_real_escape_string($mysqli_link, $_REQUEST['contactmail']) . "',
			'" . mysqli_real_escape_string($mysqli_link, $contactphone) . "', 
			'" . mysqli_real_escape_string($mysqli_link, web2db("$rue $boitelettre")) . "', 
			'" . mysqli_real_escape_string($mysqli_link, $codepostal) . "',
			'" . mysqli_real_escape_string($mysqli_link, web2db("$ville $pays")) . "')")
			or journalise(0, "E", "Cannot add contact, system error: " . mysqli_error($mysqli_link)) ;
	$contact_id = mysqli_insert_id($mysqli_link) ;
	mysqli_query($mysqli_link, "INSERT INTO $table_pax (p_lname, p_fname, p_email, p_tel)
			VALUES(
			'" . mysqli_real_escape_string($mysqli_link, web2db($lastname2)) . "',
			'" . mysqli_real_escape_string($mysqli_link, web2db($firstname2)) . "',
			'" . mysqli_real_escape_string($mysqli_link, $destinatairemail) . "',
			'" . mysqli_real_escape_string($mysqli_link, $destinatairephone) . "')")
			or journalise(0, "E", "Cannot add pilot, system error: " . mysqli_error($mysqli_link)) ;
	$pax_id = mysqli_insert_id($mysqli_link) ;
	mysqli_query($mysqli_link, "INSERT INTO $table_flight (f_date_created, f_who_created, f_type, f_gift, f_pax_cnt, f_circuit, f_date_1, f_date_2, f_schedule, f_description, f_pilot)
			VALUES(SYSDATE(), 0, '$flight_type', 1, $numberofpassagers, $circuitnumber, NULL, NULL, NULL,
			'" . mysqli_real_escape_string($mysqli_link, web2db("$remarque\n$messageforgift")) . "',
			NULL)")
			or journalise(0, "E", "Cannot add flight, system error: " . mysqli_error($mysqli_link)) ;
	$flight_id = mysqli_insert_id($mysqli_link) ;
	mysqli_query($mysqli_link, "INSERT INTO $table_pax_role(pr_flight, pr_pax, pr_role)
			VALUES($flight_id, $contact_id, 'C')")
			or journalise(0, "E", "Cannot add contact role C, system error: " . mysqli_error($mysqli_link)) ;
	mysqli_query($mysqli_link, "INSERT INTO $table_pax_role(pr_flight, pr_pax, pr_role)
		VALUES($flight_id, $pax_id, 'S')")
		or journalise(0, "E", "Cannot add student role $role, system error: " . mysqli_error($mysqli_link)) ;
	$prefix = 'V-'  ; // As it is a voucher
	$type = ($flight_type == 'D') ? 'IF-' : 'INIT-' ;
	$flight_reference = $prefix . $type . sprintf("%06d", $flight_id) ;
	mysqli_query($mysqli_link, "UPDATE $table_flight 
							SET f_reference='$flight_reference' 
							WHERE f_id=$flight_id")
				or journalise(0, 'E', "Cannot update reference in $table_flight to $flight_reference: " . mysqli_error($mysqli_link)) ;
	journalise(0, 'I', "Discovery flight ($flight_reference) created for $lastname1 $firstname1 ($remarque)") ;
	if($language=="french") {
		$message = "Date demande: ".$today."<br/>";
		$message.="<br/>";
		$message .= "Demande d'un bon cadeau de type: ";
		if ($typeofgift == "vol_decouverte") {
			$message.="vol d&eacute;couverte";
		}
		elseif ($typeofgift == "vol_initiation"){
			$message.="vol d'initiation";
		}
		else {
			$message.="valeur libre";
		}
		$message.=$falsemail ."<br/>";
		$message.="<br/>";

		$message.="<b>Information sur le demandeur du cadeau</b><br/>";		
		$message.="Nom: ".htmlentities($firstname1) ." " . htmlentities($lastname1) . "<br/>";
		$message.="Mail: ".$contactmail ."<br/>";
		$message.="T&eacute;l&eacute;phone: ".$contactphone ."<br/>";
		$message.="<br/>";

		$message.="<b>Information sur le destinataire du cadeau</b><br/>";		
		$message.="Nom: ".htmlentities($firstname2) ." " . htmlentities($lastname2) . "<br/>";
		$message.="Mail: ".$destinatairemail ."<br/>";
		$message.="T&eacute;l&eacute;phone: ".$destinatairephone ."<br/>";
		$message.="<br/>";
		$message.="<b>Adresse de livraison du bon cadeau</b><br/>";
		$message.="Destinataire du courrier: ".$destinataire_option_name."<br/>";
		$message.="Adresse: ". $rue.", ".$boitelettre." - ". $codepostal." ". $ville." - ".$pays."<br/>";
		$message.="<br/>";
		$message.="<b>Information sur le vol $flight_reference</b><br/>";
		if ($typeofgift == "vol_decouverte") {
		    $message.="Nombre de passagers: ".$numberofpassagers."<br/>";
			$message.="Circuit demand&eacute;: ".htmlentities($circuit) ."<br/>";
			$message.="Le destinataire du cadeau est-il d&eacute;j&agrave; membre du club RAPCS: ".$rapcsmember;
			$message.="<br/>";
		
		}
		elseif ($typeofgift == "vol_initiation"){
		    $message.="Nombre de passagers: ".$numberofpassagers."<br/>";
		}
		else {
		    /*$message.="Valeur du bon: ".htmlentities($valeur_bon) ."<br/>";*/
			$message.="Valeur du bon: ".htmlentities($valeur_versement) ." &euro;<br/>";
			$message.="Le destinataire du cadeau est d&eacute;j&agrave; membre du club RAPCS: ".$rapcsmember;
			$message.="<br/>";
		}
		$message.="R&eacute;f&eacute;rence club: $flight_reference<br/>" ;
		$message.="<br/>";
	
		$message.="Valeur &agrave; verser sur le compte BE64 7320 3842 1852 BIC CREGBEBB au nom de RAPCS asbl: ".htmlentities($valeur_versement) ." &euro;<br/>";
		$message.="<p>Communication &agrave; associer au virement: Bon cadeau $flight_reference de ".htmlentities($firstname1) ." " . htmlentities($lastname1)." - Date: ".$today."</p>";
        $epcURI = "BCD\n001\n1\nSCT\nCREGBEBB\nRAPCS asbl\nBE64732038421852\nEUR$valeur_versement\nBon $flight_reference $lastname1 $today\nBon $flight_reference $lastname1 $today" ;
        $message .= "<p>Ou utilisez le QR-code ci-dessous et votre application bancaire (<b>pas</b> payconiq):<br/><img width=300 height=300 src=\"https://chart.googleapis.com/chart?cht=qr&chs=300x300&chld=M&chl=" . urlencode($epcURI) . "\"></p>\n" ;
		if(count($remarques)==0 || $remarques[0]=="") {
			$message.="Pas de Remarques<br/>";
		} else {
			for($i = 0; $i < count($remarques); ++$i) { // Suggestion par Eric: utiliser nl2br()
				if($i==0) {
					$message.="Remarques:<br/>"; 
				}
				$message.=htmlentities($remarques[$i])."<br/>";
			}
		}
		if(count($messagesforgift)==0 || $messagesforgift[0]=="") {
			$message.="Pas de message associ&eacute; au bon cadeau<br/>";
		} else {
			for($i = 0; $i < count($messagesforgift); ++$i) {
				if($i==0) {
					$message.="Message associ&eacute; au bon cadeau:<br/>";
				}
				$message.=htmlentities($messagesforgift[$i])."<br/>";
			}
		}
    } else { // $language != 'french'
		$message = "Request date: ".$today."<br/>";
		$message.="<br/>";
		
		$message .= "Type of gift: ";
		if ($typeofgift == "vol_decouverte") {
			$message.="Introductory Flight (Air trip)";
		} elseif ($typeofgift == "vol_initiation"){
			$message.="Initiating Flight";
		} else {
			$message.="Voucher";
		}
		if ($rapcsmember == "oui") {
			$rapcsmember="yes";
		} else {
			$rapcsmember="no";
		}
		
		$message.=$falsemail ."<br/>";
		$message.="<br/>";
		$message.="<b>Information about the gift donor</b><br/>";				
		$message.="Name: ".htmlentities($firstname1) ." " . htmlentities($lastname1) . "<br/>";

		$message.="<br/>";
		$message.="<b>Information about the gift recipient</b><br/>";				
		$message.="name: ".htmlentities($firstname2) ." " . htmlentities($lastname2) . "<br/>";
		$message.="Mail: ".$destinatairemail ."<br/>";
		$message.="Phone: ".$destinatairephone ."<br/>";
		$message.="<br/>";

		$message.="<b>Delivery address of the voucher</b><br/>";	
		if($destinataire_option_name=="destinataire cadeau") {		
			$message.="Mail recipient: gift recipient<br/>";
		} else {
			$message.="Mail recipient: gift applicant<br/>";		
		}
		$message.="Address: ". $rue.", ".$boitelettre." - ". $codepostal." ". $ville." - ".$pays."<br/>";
		$message.="<br/>";

		$message.="<b>Flight Information $flight_reference</b><br/>";				
		if ($typeofgift == "vol_decouverte") {
		    $message.="Number of passenger: ".$numberofpassagers."<br/>";
			$message.="Circuit: ".htmlentities($circuit) ."<br/>";
			$message.="The recipient of the gift is already a member of the club RAPCS: ".$rapcsmember;
			$message.="<br/>";
		
		} elseif ($typeofgift == "vol_initiation"){
		    $message.="Number of passenger: ".$numberofpassagers."<br/>";
		} else {
			$message.="Voucher value: ".htmlentities($valeur_versement) ." &euro;<br/>";
			$message.="The recipient of the gift is already a member of the club RAPCS: ".$rapcsmember;
			$message.="<br/>";
		}
		$message.="<br/>";
		$message.="Value to transfer by bank. Name RAPCS asbl, IBAN BE64 7320 3842 1852, BIC CREGBEBB: ".htmlentities($valeur_versement) ." &euro;<br/>";
		$message.="Communication on bank transfer: <b>Gift voucher $flight_reference for  ".htmlentities($firstname1) ." " . htmlentities($lastname1)." - Date: ".$today."</b><br/><br/>";
		if(count($remarques)==0 || $remarques[0]=="") {
			$message.="No remark<br/>";
		} else {
			for($i = 0; $i < count($remarques); ++$i) {
				if($i==0) {
					$message.="Remarks:<br/>";
				}
				$message.=htmlentities($remarques[$i])."<br/>";
			}
		}
		if(count($messagesforgift)==0 || $messagesforgift[0]=="") {
			$message.="No message associated to the voucher gift<br/>";
		} else {	
			for($i = 0; $i < count($messagesforgift); ++$i) {
				if($i==0) {
					$message.="Message associated to the voucher:<br/>";
				}
				$message.=htmlentities($messagesforgift[$i])."<br/>";
			}
		}
    }
	$contactmail='"'.$firstname1.' '.$lastname1.'" <'.$contactmail.'>';
	
	$MessageBody='<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>'.$message."</body></html>";
	
	mail_attachment($to, $sender, "reservationspaaviation", $from, "$subject ($flight_reference)", $MessageBody, "","", "");
	if($language == "french") {
	   $MessageAnswer=file_get_contents("./answer_offrir_prolog.html");
	   $MessageAnswer.=$message;
	   $MessageAnswer.=file_get_contents("./answer_offrir_epilog.html");
    }
	else {
	   $MessageAnswer=file_get_contents("./answer_offrir_prolog_english.html");
 	   $MessageAnswer.=$message;
 	   $MessageAnswer.=file_get_contents("./answer_offrir_epilog_english.html");		
	}
	
	$MessageAnswerBody='<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>'.$MessageAnswer."</body></html>";
    mail_attachment($contactmail, $sender, "", $replyto, "Confirmation ($flight_reference): ".$subject, $MessageAnswerBody,"", "", "");
	journalise(0, "I", "Vol decouverte $flight_reference: New reservation from $contactmail") ;
}
?>
        <p>
		<h2><?php if($language=="french") {
			echo("R&eacute;sum&eacute; de votre demande $flight_reference");
		}
		else {
			echo("Request summary $flight_reference");			
		} ?></h2>
	    </p>
		<p>
			<?php 
			if(empty($errormessage)) {
			echo $MessageAnswer; 
		}
		else{
			echo "<b><font color=\"red\">".$errormessage."</font></b><br/>";
	     }
			?>
		</p>
        <p>
		<br/><br/><?php if($language=="french") {
			echo("<i>Un mail contenant ce r&eacute;sum&eacute;, vous a &eacute;t&eacute; envoy&eacute;.<br/>");
            echo("Si vous ne le recevez pas ou pour tout renseignement compl&eacute;mentaire, contactez nous aux adresses ci-dessous.<br/>");
            echo("Adresse mail <a href='mailto:reservation@spa-aviation.be'>reservation@spa-aviation.be</a> ou num&eacute;ro de t&eacute;l&eacute;phone <a href='tel:+32470646828'>+32 (0)470 64 68 28</a></i>.");
		}
		else {
			echo("<i>A mail was sent to your e-mail address.<br/> If you don't receive this mail or if you have any question, don't hesitate to contact us");
			echo("by mail at <a href='mailto:reservation@spa-aviation.be'>reservation@spa-aviation.be</a> or by phone at <a href='tel:+32470646828'>+32 (0)470 64 68 28</a></i>.");			
		} ?>
	    </p>
		<br/>
		<h2>
		<button onclick="goBack()">Retour &agrave; la page de r&eacute;servation</button>
	    </h2>
    	</body>
</html>