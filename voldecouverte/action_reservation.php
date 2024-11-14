<!DOCTYPE html>
<?php
require_once 'action_tools.php' ;

// This PHP script is fully integrated as a component of Joomla
// Developped by Patrick Reginster in 2019-2023
// Eric Vyncke 2022-2023

$language=$_POST["form_language"];
$typeofflight=$_POST["typeofflight"];
//print("typeofflight=$typeofflight</br>");
$numberofpassagers=$_POST["numberofpassagers"];
if (!is_numeric($numberofpassagers) or $numberofpassagers < 0 or $numberofpassagers > 4)
	journalise(0, 'F', "SQL injection detected numberofpassagers='$numberofpassagers'") ;

$firstname1=$_POST["firstname1"];
$lastname1=$_POST["lastname1"];
$age1="";
if(isset($_POST["age1"])) {
	$age1=$_POST["age1"];
}
$weight1=$_POST["weight1"];
if ($weight1 != '' and !is_numeric($weight1))
	journalise(0, 'F', "SQL injection detected weight1='$weight1'") ;

if($firstname1=="James" && $lastname1=="Smith") {
	exit;
}

$firstname2=$_POST["firstname2"];
$lastname2=$_POST["lastname2"];
$age2="";
if(isset($_POST["age2"])) {
	$age2=$_POST["age2"];
}
$weight2=$_POST["weight2"];
if ($weight2 != '' and !is_numeric($weight2))
	journalise(0, 'F', "SQL injection detected weight2='$weight2'") ;

$firstname3=$_POST["firstname3"];
$lastname3=$_POST["lastname3"];
$age3="";
if(isset($_POST["age3"])) {
	$age3=$_POST["age3"];
}
$weight3=$_POST["weight3"];
if ($weight3 != '' and !is_numeric($weight3))
	journalise(0, 'F', "SQL injection detected weight3='$weight3'") ;

$contactmail=$_POST["contactmail"];
$contactphone=$_POST["contactphone"];
$circuitnumber="";
if(isset($_POST["circuit"])) {
	$circuitnumber=$_POST["circuit"];
}
if ($circuitnumber==NULL or $circuitnumber == '') $circuitnumber=0;
if (!is_numeric($circuitnumber) or $circuitnumber < 0 or $circuitnumber > 100)
	journalise(0, 'F', "SQL injection detected circuitnumber='$circuitnumber'") ;

$circuit=circuit_name($circuitnumber);
$flightdate=compute_date($_POST["flightdate"]);
$flightdate2=compute_date($_POST["flightdate2"]);

if($flightdate=="") {
	// No date for Vol_Initiatiation
	//$flightdate=date('j-n-Y',time()+(1 * 24 * 60 * 60));
}
if($flightdate2=="") {
	// No date for Vol_Initiatiation
	//$flightdate2=date('j-n-Y',time()+(1 * 24 * 60 * 60));
}

$heure=plages_horaire(intval($_POST["heure"]));
$voldansles12mois=$_POST["voldansles12mois"];
$remarque=$_POST["remarque"];
$rapcsmember="non";
if($voldansles12mois=="2") {
	$rapcsmember="oui";
}
$remarques=explode ( "\n" ,$remarque);

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
  $to = $testingto;
  $replyto=$testingreplyto;
  $contactmail=substr($contactmail,5);
  $to = 'eric.vyncke@uliege.be' ;
  $replyto = $to ;
}

if($testingflag) {
  $falsemail=" (Warning: This is not a true reservation)";
}
if($language == "french") {
	$subject = 'Demande de '.$typeofflight .$falsemail;
}
else {
	$subject = 'Request for a '.$typeofflight .$falsemail;
}

$today = date("j-n-Y"); 

$errormessage=check_data($contactmail,$firstname1,$lastname1,$contactphone,$flightdate,$typeofflight);
if(!empty($errormessage)) {
	$MessageAnswer=$errormessage;
}
else {	
	if($language == "french") {
		$message = "Date demande: ".$today."<br/>";
		$message = "Demande de r&eacute;servation d'";
		if ($typeofflight == "vol_decouverte") {
			$message.="une promenade a&eacute;rienne";
			$flight_type = 'D' ;
		}
		else {
			$message.="un vol d'initiation";
			$flight_type = 'I' ;
		}
		$message.=$falsemail ."<br/>";
		$message.="Nombre de passagers: ".$numberofpassagers."<br/>";
		$message.="Passager principal: ".htmlentities($firstname1) ." " . htmlentities($lastname1) . " (Age: ". $age1." , Poids: ". $weight1. "kg)". "<br/>";
		if($numberofpassagers > 1) {
		   $message.="Passager 2: ".htmlentities($firstname2) ." " . htmlentities($lastname2) . " (Age: ". $age2." , Poids: ". $weight2. "kg)". "<br/>";
	    }
		if($numberofpassagers > 2) {
		   $message.="Passager 3: ".htmlentities($firstname3) ." " . htmlentities($lastname3) . " (Age: ". $age3." , Poids: ". $weight3. "kg)". "<br/>";
	    }
		$message.="Mail: ".$contactmail ."<br/>";
		$message.="T&eacute;l&eacute;phone: ".$contactphone ."<br/>";
		if ($typeofflight == "vol_decouverte") {
			$message.="Circuit demand&eacute;: ".htmlentities($circuit) ."<br/>";
		}
		if($typeofflight=="vol_decouverte") {
			$message.="Date souhait&eacute;e: ".$flightdate ." ou ".$flightdate2."<br/>";
			$message.="Heure souhait&eacute;e: ".$heure."<br/>";
			$message.="Vous souhaitez devenir membre du RAPCS : ".$rapcsmember;
			$message.="<br/>";
		}
		if(count($remarques)==0) {
			$message.="Remarques<br/>";
		}
		else {
			for($i = 0; $i < count($remarques); ++$i) {
				if($i==0) {
					$message.="Remarques:<br/>";
				}
				$message.=htmlentities($remarques[$i])."<br/>";
			}
		}
	}
	else {
		$message = "Request date: ".$today."<br/>";
		$message = "Request for ";
		if ($typeofflight == "vol_decouverte") {
			$message.="an introductory flight (Air trip)";
			$flight_type = 'D' ;
		}
		else {
			$message.="an initiating flight";
			$flight_type = 'I' ;
		}
		$message.=$falsemail ."<br/>";
		$message.="Number of passengers: ".$numberofpassagers."<br/>";
		$message.="Main passenger: ".htmlentities($firstname1) ." " . htmlentities($lastname1) . " (Age: ". $age1." , Weight: ". $weight1. "kg)". "<br/>";
		if($numberofpassagers > 1) {
		   $message.="Passenger 2: ".htmlentities($firstname2) ." " . htmlentities($lastname2) . " (Age: ". $age2." , Weight: ". $weight2. "kg)". "<br/>";
	    }
		if($numberofpassagers > 2) {
		   $message.="Passenger 3: ".htmlentities($firstname3) ." " . htmlentities($lastname3) . " (Age: ". $age3." , Weight: ". $weight3. "kg)". "<br/>";
	    }
		$message.="Mail: ".$contactmail ."<br/>";
		$message.="Phone: ".$contactphone ."<br/>";
		if ($typeofflight == "vol_decouverte") {
			$message.="Requested Circuit: ".htmlentities($circuit) ."<br/>";
		}
		$message.="Desired Date: ".$flightdate ." or ".$flightdate2."<br/>";
		$message.="Desired time: ".$heure."<br/>";
		$message.="You wish to become member: ";
		if($rapcsmember=="non") {
			$message.="no<br/>";			
		}
		else {
			$message.="yes<br/>";						
		}
		if(count($remarques)==0) {
			$message.="No remarks<br/>";
		}
		else {
			for($i = 0; $i < count($remarques); ++$i) {
				if($i==0) {
					$message.="Remarks:<br/>";
				}
				$message.=htmlentities($remarques[$i])."<br/>";
			}
		}		
	}
	//$message=htmlentities($message);
	$contactmail='"'.$firstname1.' '.$lastname1.'" <'.$contactmail.'>';
	
	$filecontent= create_csvfile($contactmail,$contactphone, 
	$typeofflight,$flightdate,$flightdate2,$heure,$numberofpassagers,$circuit,
	$firstname1,$lastname1,$age1,$weight1,$rapcsmember,
	$firstname2,$lastname2,$age2,$weight2,
	$firstname3,$lastname3,$age3,$weight3,
	$remarques,$language);
	//echo("Filecontent=".$filecontent."<br/>end<br/>");
	//$filecontent="cell11,cell12,cell13\r\n";
	//$filecontent.="cell21,cell22,cell23\r\n";
	//$filecontent.="cell31,cell32,cell33\r\n";
	$filename="Reservation.csv";
	$filepath="./";
	//$message.="EOF";
	//mail($to, $subject, $message, $headers); 
	$messageBody='<html><head><meta http-equiv="Content-Type" content="text/html; charset=us-ascii"></head><body>'.$message."</body></html>";
	

	if($language == "french") {
	   $MessageAnswer=file_get_contents("./answer_reservation_prolog.html");
	   $MessageAnswer.=$message;
	   $MessageAnswer.=file_get_contents("./answer_reservation_epilog.html");
    }
	else {
	   $MessageAnswer=file_get_contents("./answer_reservation_prolog_english.html");
 	   $MessageAnswer.=$message;
 	   $MessageAnswer.=file_get_contents("./answer_reservation_epilog_english.html");		
	}
	
	$MessageAnswerBody='<html><head><meta http-equiv="Content-Type" content="text/html; charset=us-ascii"></head><body>'.$MessageAnswer."</body></html>";
}
?>
		<html>
		<head>
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
        <p>
		<h2><?php if($language=="french") {
			echo("R&eacute;sum&eacute; de votre demande");
		}
		else {
			echo("Request summary");			
		} ?></h2>
	    </p>
		<p>
			<?php 
			if(empty($errormessage)) {
			echo $MessageAnswer; 
		}
		else{
			echo "<b><font color=\"red\">".$errormessage."</font></b><br/>";
			journalise(0, "E", $errormessage) ;
	     }
			?>
		</p>
        <p>
		<br/><br/><?php  	if(empty($errormessage)) {
			if($language=="french") {
			echo("<i>Un mail contenant ce r&eacute;sum&eacute;, vous a &eacute;t&eacute; envoy&eacute;.<br/>");
            echo("Si vous ne le recevez pas ou pour tout renseignement compl&eacute;mentaire, contactez nous aux adresses ci-dessous.<br/>");
            echo("Adresse  mail <a href='mailto:reservation@spa-aviation.be'>reservation@spa-aviation.be</a> ou num&eacute;ro de t&eacute;l&eacute;phone <a href='tel:+32470646828'>+32 (0)470 64 68 28</a></i>");
		}
		else {
			echo("<i>A mail was sent to your e-mail address.<br/> If you don't receive this mail  or if you have any question, don't hesitate to contact us");
			echo("by mail at <a href='mailto:reservation@spa-aviation.be'>reservation@spa-aviation.be</a> or by phone at <a href='tel:+32470646828'>+32 (0)470 64 68 28</a></i>");			
		} } ?>
	    </p>
				<br/>
		<h2>
		<button onclick="goBack()">Retour &agrave; la page de r&eacute;servaton</button>
	    </h2>
		   <script>
		    function goBack() {
		      window.history.back();
		    }
		   </script>
   	</body>
		</html>

<?php
    ob_flush();
    flush();
	if(empty($errormessage)) {

		// Now let's try to insert the data in the data base
		// TODO also insert age after conversion from '> 18 ans' in A(dult), T(eenager), or C(hildren)
		if ($weight1 == '') $weight1 = 0 ;
		mysqli_query($mysqli_link, "INSERT INTO $table_pax (p_lname, p_fname, p_email, p_tel, p_weight)
			VALUES(
			'" . mysqli_real_escape_string($mysqli_link, web2db($lastname1)) . "',
			'" . mysqli_real_escape_string($mysqli_link, web2db($firstname1)) . "',
			'" . mysqli_real_escape_string($mysqli_link, $_REQUEST['contactmail']) . "',
			'" . mysqli_real_escape_string($mysqli_link, $contactphone) . "',
			$weight1)")
			or journalise(0, "E", "Cannot add contact, system error: " . mysqli_error($mysqli_link)) ;
		$contact_id = mysqli_insert_id($mysqli_link) ;
		$tokens = explode('-', $flightdate) ;
		$flightdate = "";
		if(sizeof($tokens)==2) {
			$flightdate = "$tokens[2]-$tokens[1]-$tokens[0]" ;
		}
		$tokens = explode('-', $flightdate2) ;
		$flightdate2 = "";
		if(sizeof($tokens)==2) {
			$flightdate2 = "$tokens[2]-$tokens[1]-$tokens[0]" ;
		}
		//print("INSERT INTO $table_flight (f_date_created, f_who_created, f_type, f_gift, f_pax_cnt, f_circuit, f_date_1, f_date_2, f_schedule, f_description, f_pilot)
		//	VALUES(SYSDATE(), 0, '$flight_type', 0, $numberofpassagers, $circuitnumber, '$flightdate', '$flightdate2', '$heure', '" .
		//	mysqli_real_escape_string($mysqli_link, web2db("$remarque")) . "', NULL)</br>");
		mysqli_query($mysqli_link, "INSERT INTO $table_flight (f_date_created, f_who_created, f_type, f_gift, f_pax_cnt, f_circuit, f_date_1, f_date_2, f_schedule, f_description, f_pilot)
			VALUES(SYSDATE(), 0, '$flight_type', 0, $numberofpassagers, $circuitnumber, '$flightdate', '$flightdate2', '$heure', '" .
			mysqli_real_escape_string($mysqli_link, web2db("$remarque")) . "', NULL)")
			or journalise(0, "E", "Cannot add flight, system error: " . mysqli_error($mysqli_link)) ;
		$flight_id = mysqli_insert_id($mysqli_link) ;
		//print("flight_id=$flight_id</br>");
		$type = ($flight_type == 'D') ? 'IF-' : 'INIT-' ;
		$flight_reference = $type . sprintf("%06d", $flight_id) ;
		//print("flight_reference=$flight_reference</br>");
		mysqli_query($mysqli_link, "UPDATE $table_flight SET f_reference='$flight_reference' WHERE f_id=$flight_id")
					or journalise(0, 'E', "Cannot update reference in $table_flight to $flight_reference: " . mysqli_error($mysqli_link)) ;
		mysqli_query($mysqli_link, "INSERT INTO $table_pax_role(pr_flight, pr_pax, pr_role)
			VALUES($flight_id, $contact_id, 'C')")
			or journalise(0, "E", "Cannot add contact role C, system error: " . mysqli_error($mysqli_link)) ;
		mysqli_query($mysqli_link, "INSERT INTO $table_pax_role(pr_flight, pr_pax, pr_role)
			VALUES($flight_id, $contact_id, 'P')")
			or journalise(0, "E", "Cannot add contact role P, system error: " . mysqli_error($mysqli_link)) ;
		// Let's add the passengers
		if ($numberofpassagers > 1) {
			if ($weight2 == '') $weight2 = 0 ;
			mysqli_query($mysqli_link, "INSERT INTO $table_pax (p_lname, p_fname, p_weight)
				VALUES(
				'" . mysqli_real_escape_string($mysqli_link, web2db($lastname2)) . "',
				'" . mysqli_real_escape_string($mysqli_link, web2db($firstname2)) . "', 
				$weight2)")
				or journalise(0, "E", "Cannot add pax2, system error: " . mysqli_error($mysqli_link)) ;
			$pax_id = mysqli_insert_id($mysqli_link) ;
			mysqli_query($mysqli_link, "INSERT INTO $table_pax_role(pr_flight, pr_pax, pr_role)
				VALUES($flight_id, $pax_id, 'P')")
				or journalise(0, "E", "Cannot add pax2 role P, system error: " . mysqli_error($mysqli_link)) ;
		}
		if ($numberofpassagers > 2) {
			if ($weight3 == '') $weight3 = 0 ;
			mysqli_query($mysqli_link, "INSERT INTO $table_pax (p_lname, p_fname, p_weight)
				VALUES(
				'" . mysqli_real_escape_string($mysqli_link, web2db($lastname3)) . "',
				'" . mysqli_real_escape_string($mysqli_link, web2db($firstname3)) . "', 
				$weight3)")
				or journalise(0, "E", "Cannot add pax3, system error: " . mysqli_error($mysqli_link)) ;
			$pax_id = mysqli_insert_id($mysqli_link) ;
			mysqli_query($mysqli_link, "INSERT INTO $table_pax_role(pr_flight, pr_pax, pr_role)
				VALUES($flight_id, $pax_id, 'P')")
				or journalise(0, "E", "Cannot add pax3 role P, system error: " . mysqli_error($mysqli_link)) ;
		}
		journalise(0, 'I', "Discovery flight ($flight_id) created for $lastname1 $firstname1 ($remarque)") ;
	} else
		journalise(0, "E", "Vol decouverte: $errormessage") ;
		
		mail_attachment($to, $sender, "", $contactmail, $subject." ".$flight_reference, $messageBody, "", "", "");
		mail_attachment($to, $sender, "", $contactmail, "Reservation.csv:".$subject." ".$flight_reference, $message,$filename, $filepath, $filecontent);
		mail_attachment($contactmail, $sender, "", $replyto, "Confirmation: ".$subject." ".$flight_reference, $MessageAnswerBody,"", "", "");
 		journalise(0, "I", "Vol decouverte: New reservation from $contactmail") ;
		
?>
