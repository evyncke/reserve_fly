<?php
/*
   Copyright 2022-2025 Patrick Reginster (and Eric Vyncke)

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

ob_start("ob_gzhandler"); // Enable gzip compression over HTTP

require_once 'dbi.php';
require_once 'dto.class.php';
require_once 'IntroCarnetVol_tools.php' ;

# HTTP/2 push of JS & CSS via header() to go faster
header('Link: </resa/js/script_carnetdevol.js>;rel=preload;as=script,</resa/data/members.js>;rel=preload;as=script,</resa/data/pilots.js>;rel=preload;as=script,' .
	'</resa/data/instructors.js>;rel=preload;as=script,' .
	'</resa/data/planes.js>;rel=preload;as=script,</resa/data/shareCodes.js>;rel=preload;as=script,</resa/data/prix.js>;rel=preload;as=script,</resa/css/IntroCarnetVol.css>;rel=preload;as=style') ;

if ($userIsAdmin or $userIsInstructor) { // Let' trust this browser for one year 
	// TODO only send it when not received
	setcookie('trusted_booker', 1, time() + 365 * 24 * 60 * 60, '/', '', true, true) ;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <title>Introduction carnet de routes</title>
  <link rel="stylesheet" type="text/css" href="css/IntroCarnetVol.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://www.spa-aviation.be/favicon32x32.ico" rel="shortcut icon" type="image/vnd.microsoft.icon">
<script>
	
<?php
//---------------------------------------------------------------------------
// Initialiation of default variables
// Define all variables used by the javascript
//---------------------------------------------------------------------------

// Define all variables used by the javascript
print("var default_editflag=0;\n");
print("var default_segment=0;\n");
print("var default_logbookid=0;\n");
print("var default_plane=\"\";\n");
print("var default_pilot=0;\n");
print("var default_instructor=0;\n");
print("var default_isstudent=0;\n");
print("var default_date_heure_depart=\"\";\n");
print("var default_date_heure_arrivee=\"\";\n");
print("var default_day_landing=1;\n");
print("var default_crew_count=1;\n");
print("var default_pax_count=0;\n");
print("var default_flight_type=\"Local\";\n");	
print("var default_from=\"EBSP\";\n");	
print("var default_to=\"EBSP\";\n");
print("var default_is_pic=0;\n");
print("var default_instructor_paid=1;\n");
print("var default_share_type=\"NoCP\";\n");
print("var default_share_member=0;\n");
print("var default_remark=\"\";\n");
print("var default_compteur_moteur_start=\"\";\n");			 	
print("var default_compteur_moteur_end=\"\";\n");		 	
print("var default_compteur_flight_start=\"\";\n");				 	
print("var default_compteur_flight_end=\"\";\n");	
print("var default_flight_reference=\"\";\n");	
print("var default_flight_id=0;\n");
print("var default_ATL_level=\"select\";\n");
print("var default_ATL_description=\"\";\n");
print("var default_qrcode_communication_pilote=\"\";\n");
print("var default_fqrcode_montant_total_pilote=0;\n");	

// bookingid is defined by the key "id" (coming from the booking) or by the key "cdv_booking" (coming from this page)
$auth="";
$logid="";
if (isset($_REQUEST['auth']) and $_REQUEST['auth'] != '') {
	$auth=$_REQUEST['auth'];
	print("var default_auth='$auth';\n");
	if ($userId <= 0) journalise($userId, 'D', "IntroCarnetVol.php called with auth=$auth without being logged in") ;
}
if (isset($_REQUEST['id']) and $_REQUEST['id'] != '') {
	$bookingid=$_REQUEST['id'];
	if (isset($_REQUEST['auth']) and $_REQUEST['auth'] != '') {
		$auth=$_REQUEST['auth'];
		print("var default_auth='$auth';\n");
	} else {
		print("var default_auth='';\n");		
	}
}
else if (isset($_REQUEST['cdv_bookingid']) and $_REQUEST['cdv_bookingid'] != '') {
	$bookingid=$_REQUEST['cdv_bookingid'];
	print("var default_auth='';\n");	
} else {
	$bookingid = 0 ;
	print("var default_auth='';\n");		
}

// Prevent SQL injection
if (! is_numeric($bookingid)) journalise($userId, "F", "Paramètre bookingid ($bookingid) n'est pas numérique") ;

// Check whether user is logged in
if ($userId == 0) {
	if (!isset($_REQUEST['auth']) or $_REQUEST['auth'] != md5($bookingid . $shared_secret)) {
		print("</script><p style=\"color: red;\"><b>Impossible to proceed to this request.</br>You must be connected to the spa-aviation.be site or use a valid auth code and not \"$_REQUEST[auth]\"</b></p>") ;
		journalise($userId, "F", "You must be connected or use a valid auth code and not \"$_REQUEST[auth]\" for bookingid $bookingid") ;
	}
}

print("var default_bookingid=$bookingid;\n");
print("var default_pilot=$userId;\n");
$aircraft_techLog=GetJSONIncidentByPlanes();
print("var default_aircrafttechlog='$aircraft_techLog';\n");
?>
</script>
<!-- Matomo -->
<script type="text/javascript">
  var _paq = window._paq = window._paq || [];
  /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
  _paq.push(['setUserId', '<?=$userName?>']);
  _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
  _paq.push(["setDomains", ["*.spa-aviation.be","*.ebsp.be","*.m.ebsp.be","*.m.spa-aviation.be","*.resa.spa-aviation.be"]]);
  _paq.push(['enableHeartBeatTimer']);
  _paq.push(['setCustomVariable', 1, "userID", <?=$userId?>, "visit"]);
  _paq.push(["setCookieDomain", "*.spa-aviation.be"]);
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="//analytics.vyncke.org/";
    _paq.push(['setTrackerUrl', u+'matomo.php']);
    _paq.push(['setSiteId', '8']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Code -->
</head>
<body>
  <h1 style="text-align: center;">Introduction vol</h1>
<?php
//---------------------------------------------------------------------------
// For FI (or when the trusted_booker cookie is set), display all existing reservations of today w/o any log entries
//---------------------------------------------------------------------------

$trustedBooker = (isset($_COOKIE['trusted_booker']) and $_COOKIE['trusted_booker'] == 1) ;
$trustedBookerMsg = ($trustedBooker) ? " Ce browser est trusted." : "" ;

if ($userIsAdmin or $userIsInstructor) {
	$result = mysqli_query($mysqli_link, "SELECT r_plane, r_id, r_start, r_stop, r_comment, p.name AS pilot_name, i.name AS instructor_name
			FROM $table_bookings r JOIN $table_planes pp on r_plane = pp.id JOIN $table_users AS p ON r_pilot = p.id LEFT JOIN $table_users AS i ON r_instructor = i.id
			WHERE r_cancel_date IS NULL AND r_type != " . BOOKING_MAINTENANCE . " AND DATE(r_start) = CURRENT_DATE() AND pp.ressource = 0
			ORDER BY r_start")
			or journalise($userId, "F", "Cannot get all today bookings: " . mysqli_error($mysqli_link)) ;
	if ($result->num_rows == 0) {
		print("<p>Aucune réservation prévue ce jour.</p>\n") ;
	} else {

		// Table des reservations du jours
		print("<center><h2>Choisir une réservation de ce jour</h2>
		<p>Liste des réservations de ce jour (cliquez sur la date pour préremplir les champs ci-dessous).</br><em>Visible uniquement par les FIs et administrateurs.$trustedBookerMsg</em></p>
		<table width=\"100%\">
		<tr><th>Heure locale</th><th>Avion</th><th>Pilote</th><th>Remarque</th><th>Action</th></tr>\n") ;
		while ($row = $result->fetch_assoc()) {
 			$pilot = db2web($row['pilot_name']) ;
			$instructor = db2web($row['instructor_name']) ;
			$crew = ($instructor == '') ? $pilot : "$pilot/$instructor" ;
			$comment = db2web($row['r_comment']) ;
			$auth = md5($row['r_id'] . $shared_secret) ;
			print("<tr><td><a href=\"$_SERVER[PHP_SELF]?id=$row[r_id]&auth=$auth\">$row[r_start]</a></td><td>$row[r_plane]</td><td>$crew</td><td>$comment</td>
				<td>&nbsp;<button type=\"button\" value=\"Fill\" onclick=\"window.location.href='$_SERVER[PHP_SELF]?id=$row[r_id]&auth=$auth';\">&#9998; Ajouter des segments</button>&nbsp;
				&nbsp;<button style=\"background-color: salmon; color: white;\" value=\"Del\" onclick=\"window.location.href='$_SERVER[PHP_SELF]?id=$row[r_id]&auth=$auth&bookingtable=1';\">&#128465; Annuler la réservation</button>&nbsp;</td>
				</tr>\n") ;
	
		}
		print("</table></center><p></p>\n") ;
	}
}
$compteurTypeByPlane= array();
$cars["color"] = "Red";
print("<script>\n");
print("var planes_properties=[\n");	
	$result=mysqli_query($mysqli_link,"SELECT upper(id) as name,  cout, l_end_hour, l_end_minute, l_flight_end_hour, l_flight_end_minute, compteur_vol, compteur_type, model
	FROM rapcs_planes p JOIN rapcs_logbook l ON p.id = l_plane 
	WHERE l_id = (SELECT MAX(ll.l_id) FROM rapcs_logbook ll WHERE ll.l_plane = l.l_plane)
	and ressource = 0 and actif=1");
	
	while($row=mysqli_fetch_array($result)) {
		print("{ id: \"$row[name]\" , name: \"$row[name]\" , compteur_type: \"$row[compteur_type]\" , 
			compteur: \"$row[l_end_hour].$row[l_end_minute]\", 
		compteur_vol: \"$row[compteur_vol]\", compteur_vol_valeur: \"$row[l_flight_end_hour].$row[l_flight_end_minute]\", prix: \"$row[cout]\" , model: \"$row[model]\"},\n");
        $compteurTypeByPlane[$row["name"]]=$row["compteur_type"];
	}
print("];\n");
print("</script>\n");

//---------------------------------------------------------------------------
// Management of previous and next booking
// Find the previous/next booking
//---------------------------------------------------------------------------

// Find the previous/next booking
// Retrieve the booking
$bookingidForPrevious=$bookingid;
if ($bookingidForPrevious) {
	$result = mysqli_query($mysqli_link, "select username, r_id, r_plane, r_start, r_stop, r_type, r_pilot, r_instructor, r_who, r_date, 
		r_from, r_to, compteur_type, compteur_vol, model, compteur, compteur_date, 
		date_add(r_start, interval 15 minute) as r_takeoff, date(r_start) as r_day
		from $table_bookings join $table_users as p on r_pilot = p.id, $table_planes as a
		where r_id = $bookingidForPrevious and a.id = r_plane") 
		or journalise($userId, "F", "Cannot access the booking #$bookingidForPrevious: " . mysqli_error($mysqli_link)) ;
} else { // Retrieve the nearest one
	if ($userId <= 0) journalise($userId, "F", "Vous devez être connecté") ;
	$result = mysqli_query($mysqli_link, "select username, r_id, r_plane, r_start, r_stop, r_type, r_pilot, r_instructor, r_who, r_date, 
		r_from, r_to, compteur_type, compteur_vol, model, compteur, compteur_date, 
		date_add(r_start, interval 15 minute) as r_takeoff, date(r_start) as r_day
		from $table_bookings join $table_users as p on r_pilot = p.id, $table_planes as a
		where r_pilot = $userId and r_start < sysdate() and r_cancel_date is null and a.ressource = 0
		order by r_start desc
		limit 0,1") 
		or journalise($userId, "F", "Cannot access closest booking in the past: " . mysqli_error($mysqli_link)) ;
}
$booking = mysqli_fetch_array($result) ;
if ($bookingidForPrevious) {
	$condition = "(r_pilot = $booking[r_pilot])" ;
} else {
	$condition = "(r_pilot = $userId or r_instructor = $userId)" ;
	$bookingidForPrevious = $booking['r_id'] ;
	$auth = md5($bookingidForPrevious . $shared_secret) ;
}

$result = mysqli_query($mysqli_link, "select * from $table_bookings where r_cancel_date is null and r_stop < '$booking[r_start]' and r_start <= sysdate() and $condition order by r_start desc limit 0,1")
	or journalise($userId, "F", "Cannot access previous booking: ".mysqli_error($mysqli_link)) ;
$row = mysqli_fetch_array($result) ;
$previous_id='';
if(!is_null($row ) && isset($row['r_id'])) {
    $previous_id = $row['r_id'] ;
}
$previous_auth = md5($previous_id . $shared_secret) ;
$result = mysqli_query($mysqli_link, "select * from $table_bookings where r_cancel_date is null and r_start > '$booking[r_stop]' and r_start <= sysdate() and $condition order by r_start asc limit 0,1")
	or journalise($userId, "F", "Cannot access next booking: ".mysqli_error($mysqli_link)) ;
$row = mysqli_fetch_array($result) ;
$next_id='';
if(!is_null($row )) {
    $next_id = $row['r_id'] ;
}
else {
    $next_id='';
}
$next_auth = md5($next_id . $shared_secret) ;
print('<table width="100%" border-spacing="0px">');
print('<tr><td width="33%" style="text-align: left;">');
if ($previous_id != '') {
	print("&lt;&lt;&lt; <a href=\"$_SERVER[PHP_SELF]?id=$previous_id&auth=$previous_auth\">R&eacute;servation pr&eacute;c&eacute;dente</a>\n") ;
}
print("</td><td width=\"33%\" style=\"text-align: center;\"><a href=\"$_SERVER[PHP_SELF]\">Vol sans r&eacute;servation</a>");
print('</td><td style="text-align: right;">');
if ($next_id != '') {
	print("<a href=\"$_SERVER[PHP_SELF]?id=$next_id&auth=$next_auth\">R&eacute;servation suivante</a> &gt;&gt;&gt;\n") ;
} 
else {
	if(!$bookingid) {
		$next_id = $bookingidForPrevious ;
		$next_auth = md5($bookingidForPrevious . $shared_secret) ;
		print("<a href=\"$_SERVER[PHP_SELF]?id=$next_id&auth=$next_auth\">Dernière R&eacute;servation</a>\n");
	}
}
print('</td></tr>');
print('</table>');

//-----------------------------------------------------------------------------------------------------
// Do we need to delete a segment in the logbook?
//-----------------------------------------------------------------------------------------------------

if (isset($_REQUEST['audit_time']) and $_REQUEST['audit_time'] != '') {
	$logid=$_REQUEST['logid'];
	
	// Remove a DTO flight associated to this logid
	//print("before RemoveDTOFlight: logid=$logid</br>");
	RemoveDTOFlight($logid);
		
	// Remove the entry in the LogBook
	$audit_time = mysqli_real_escape_string($mysqli_link, $_REQUEST['audit_time']) ;
	mysqli_query($mysqli_link, "delete from $table_logbook where l_id=$logid and l_audit_time='$audit_time'") 
		or journalise($userId, "F", "Cannot delete: " . mysqli_error($mysqli_link)) ;
	if (mysqli_affected_rows($mysqli_link) > 0) {
		$insert_message = "Carnet de routes mis à jour" ;
		journalise($userId, 'I', "Logbook entry deleted for booking $bookingid (done at $audit_time).") ;
	} else {
		$insert_message = "Impossible d'effacer la ligne dans le carnet de routes" ;
		journalise($userId, 'E', "Error (" . mysqli_error($mysqli_link). ") while deleting logbook entry for booking $bookingid (done at $audit_time).") ;
	}
}

//-----------------------------------------------------------------------------------------------------
// Do we need to delete a reservation in booking table?
// 1. Delete all segments associated to this bookingid
// 2. Delete the entry in the bookings table
//-----------------------------------------------------------------------------------------------------

if (isset($_REQUEST['bookingtable']) and $_REQUEST['bookingtable'] == '1') {
	
	// Delete all DTO flights associated to this bookingid
	// All DTO flight associated to all segments behing this bookingid
	RemoveAllDTOFlightBehindBooking($bookingid);
	
	// Delete all already logged segments associated to this bookingid
	mysqli_query($mysqli_link, "delete from $table_logbook where l_booking=$bookingid") 
		or journalise($userId, "F", "Cannot delete: " . mysqli_error($mysqli_link)) ;
	if (mysqli_affected_rows($mysqli_link) > 0) {
		$insert_message = "Carnet de routes mis à jour" ;
		journalise($userId, 'I', mysqli_affected_rows($mysqli_link) . " logbook entri(es) deleted for booking $bookingid.") ;
	}
	
	// Delete the entry in the bookings table
	$remote_address = getClientAddress() ;
	mysqli_query($mysqli_link, "update $table_bookings set r_cancel_date=sysdate(), r_cancel_who=$userId, r_cancel_reason='IntroCarnetVol', r_cancel_address='$remote_address' 
			where r_id=$bookingid") 
		or journalise($userId, "F", "Cannot cancel booking: " . mysqli_error($mysqli_link)) ;
	if (mysqli_affected_rows($mysqli_link) > 0) {
		$insert_message = "Réservation mise à jour (annulée)" ;
		journalise($userId, 'I', "Booking table entry cancelled for booking $bookingid.") ;
		$bookingid=0;
		$id=0;
		print("<script>var default_bookingid=$bookingid;</script>\n");
	} else {
		$insert_message = "Impossible d'effacer la ligne dans les réservations" ;
		journalise($userId, 'E', "Error (" . mysqli_error($mysqli_link). ") while cancelling booking entry for booking $bookingid.") ;
	}
}

//-----------------------------------------------------------------------------------------------------
// Summary of the Reservation
//-----------------------------------------------------------------------------------------------------
print('<p></p>');
if($bookingid) {
	$result=mysqli_query($mysqli_link,"SELECT r_id, r_plane, r_type, r_from, r_to, r_start, r_stop, r_pilot, p.last_name as pilotName, i.last_name as instructorName
	FROM $table_bookings r join $table_person p on r.r_pilot = p.jom_id left join $table_person i on r.r_instructor = i.jom_id
	WHERE r_id = $bookingid") 
		or journalise($userId, "F", "Impossible de retrouver le bookingid dans booking: " . mysqli_error($mysqli_link)) ;
	$row=mysqli_fetch_array($result);

	$start_UTC = gmdate('H:i', strtotime("$row[r_start] UTC")) ;
	$end_UTC = gmdate('H:i', strtotime("$row[r_stop] UTC")) ;
	$dateFlight=gmdate('l j-m-Y', strtotime("$row[r_start] UTC")) ;
	if ($row['instructorName'] == '' )
		$crew = db2web($row['pilotName']) ;
	else
		$crew = db2web($row['pilotName'] . '/' . $row['instructorName']) ;
	if($row['r_type'] == BOOKING_MAINTENANCE) {
		$crew= $crew." (Maintenance)";
	}
	$crew = db2web($crew) ; // As DB is latin and web is UTF-8
	print('<p></p>');
	//print(" 1. BookingId=$bookingid (r_id)</br>");
	
	// Table Resume de la reservation selectionnee
	print("<center><table width=\"100%\" border-spacing=\"0px\">
		<thead>
		<tr><th style=\"background-color: Gainsboro; text-align: center;\" colspan=\"8\">Résumé de la réservation (Heure locale)</th></tr>
		<tr><th>Date</th><th>Avion</th><th>Pilote</th><th>De</th><th>Départ</th><th>À</th><th>Arrivée</th><th>Action</th></tr>
		</thead>
		<tbody>
		<tr>
		<td>$dateFlight</td>
		<td>$row[r_plane]</td>
		<td>$crew</td>
		<td>$row[r_from]</td>
		<td>$start_UTC</td>
		<td>$row[r_to]</td>
		<td>$end_UTC</td>
		<td>&nbsp;<button type=\"button\" value=\"Del\" onclick=\"redirectBookingDelete('$_SERVER[PHP_SELF]',$bookingid,'$auth');\">&#128465; Annuler la réservation</button>
		</td>
		</tr>
		</tbody>
		</table></center>");
	// Select the default pilot
	print("<script>var default_pilot=$row[r_pilot];</script>\n") ;
}

//---------------------------------------------------------------------------
// Manage l'enregistrement du vol introduit dans la form.
//---------------------------------------------------------------------------
$numeroVol="";
if (isset($_REQUEST['action']) and $_REQUEST['action'] != '') {
	// TODO sanitize all fields to prevent SQL injection
	$cdv_bookingid=$_REQUEST['cdv_bookingid'];
	$cdv_logbookid=$_REQUEST['cdv_logbookid'];
	$cdv_flightreferenceid=$_REQUEST['cdv_flightreferenceid'];
	$cdv_segment=$_REQUEST['cdv_segment_count'];
	$cdv_aircraft=$_REQUEST['cdv_aircraft'];
	$cdv_aircraft_model=$_REQUEST['cdv_aircraft_model'];

	$cdv_flight_date=$_REQUEST['cdv_flight_date'];
	$cdv_pilot_name=$_REQUEST['cdv_pilot_name'];
	$cdv_pilot_function=$_REQUEST['cdv_pilot_function'];
    $cdv_flight_instructor="";
    if(isset($_REQUEST['cdv_flight_instructor'])) {
	    $cdv_flight_instructor=$_REQUEST['cdv_flight_instructor'];
    }
	$cdv_departure_airport=$_REQUEST['cdv_departure_airport'];
	$cdv_arrival_airport=$_REQUEST['cdv_arrival_airport'];
	$cdv_heure_depart=$_REQUEST['cdv_heure_depart'];
	$cdv_heure_arrivee=$_REQUEST['cdv_heure_arrivee'];
	$cdv_duree=$_REQUEST['cdv_duree'];
	$cdv_compteur_depart=$_REQUEST['cdv_compteur_depart'];
	$cdv_compteur_arrivee=$_REQUEST['cdv_compteur_arrivee'];
	$cdv_compteur_duree=$_REQUEST['cdv_compteur_duree'];
	$cdv_compteur_vol_depart=$_REQUEST['cdv_compteur_vol_depart'];
	$cdv_compteur_vol_arrivee=$_REQUEST['cdv_compteur_vol_arrivee'];
	$cdv_compteur_vol_duree=$_REQUEST['cdv_compteur_vol_duree'];
	$cdv_nombre_atterrissage=$_REQUEST['cdv_nombre_atterrissage'];
	$cdv_nombre_crew=$_REQUEST['cdv_nombre_crew'];
	$cdv_nombre_passager=$_REQUEST['cdv_nombre_passager'];
	$cdv_nature_vol=$_REQUEST['cdv_nature_vol'];
	$cdv_frais_CP=$_REQUEST['cdv_frais_CP'];
	$cdv_frais_CP_type=$_REQUEST['cdv_frais_CP_type'];
	$cdv_frais_numero_vol=$_REQUEST['cdv_frais_numero_vol'];
    $cdv_frais_CP_PAX="";
    if(isset($_REQUEST['cdv_frais_CP_PAX'])) {
	    $cdv_frais_CP_PAX=$_REQUEST['cdv_frais_CP_PAX'];
    }
	$cdv_frais_remarque=$_REQUEST['cdv_frais_remarque'];
	$cdv_frais_DC=$_REQUEST['cdv_frais_DC'];
	$cdv_ATL_level=$_REQUEST['cdv_ATL_level'];
	$cdv_ATL_description=$_REQUEST['cdv_ATL_description']; 
	$cdv_qrcode_montant_total_pilote=$_REQUEST['cdv_qrcode_montant_total_pilote'];
	$cdv_qrcode_communication_pilote=$_REQUEST['cdv_qrcode_communication_pilote'];
	print("<script>\n");
	print("var default_qrcode_communication_pilote=\"$cdv_qrcode_communication_pilote\";\n");
	print("var default_fqrcode_montant_total_pilote=$cdv_qrcode_montant_total_pilote;\n");	
	print("</script>\n");
	
    $planeId=$cdv_aircraft;
	$planeModel=$cdv_aircraft_model;
	$bookingidPage=CheckVar($cdv_bookingid);
	$fromAirport=$cdv_departure_airport;
	$toAirport=$cdv_arrival_airport;
	$engineStartHour=GetCompteurHour($cdv_compteur_depart);
	$engineStartMinute=GetCompteurMinute($cdv_compteur_depart);
	$engineEndHour=GetCompteurHour($cdv_compteur_arrivee);
	$engineEndMinute=GetCompteurMinute($cdv_compteur_arrivee);
	$flightStartHour=CheckVar(GetCompteurHour($cdv_compteur_vol_depart));
	$flightStartMinute=CheckVar(GetCompteurMinute($cdv_compteur_vol_depart));
	$flightEndHour=CheckVar(GetCompteurHour($cdv_compteur_vol_arrivee));
	$flightEndMinute=CheckVar(GetCompteurMinute($cdv_compteur_vol_arrivee));
	$startDayTime=GetDayTime($cdv_flight_date,$cdv_heure_depart);
	$endDayTime= GetDayTime($cdv_flight_date,$cdv_heure_arrivee);
	$flightType=CheckVar($cdv_nature_vol);
	$isPICFunction=1;
	if($cdv_pilot_function=="DC") {
		$isPICFunction=0;
	}
	else if($cdv_pilot_function=="PIC") {
		$isPICFunction=1;
		$cdv_flight_instructor=0;
	}
	else if($cdv_pilot_function=="PICSupervise") {
		$isPICFunction=2;
	}
	else {
		// PICRecheck
		$isPICFunction=1;
	}
	$remark=CheckVar($cdv_frais_remarque); // May return the string NULL
    
    // Aircraft Technical Log
	$ATLLevel=$cdv_ATL_level;
	$ATLDescription=$cdv_ATL_description;
    //print("PRE1 ATLLevel=$ATLLevel; ATLDescription=$ATLDescription<br>");
    
    // Vol IF or INIT
	$numeroVol=CheckVar($cdv_frais_numero_vol); // May return the string NULL
	//print("numeroVol1:$numeroVol</br>\n");
	if ($numeroVol != 'NULL') {
		$numeroVol = "$numeroVol" ; // As $remark is not quoted in the SQL statement
		// Transform a ReferenceId into a Reference (String)
		//print("SELECT f_id, f_reference FROM $table_flight WHERE f_id = '$numeroVol';</br>") ;
		$flightResult=mysqli_query($mysqli_link,"SELECT f_id, f_reference FROM $table_flight WHERE f_id = '$numeroVol';") 
			or journalise($userId, "F", "Impossible de retrouver le f_id=$numeroVol dans table_flight: " . mysqli_error($mysqli_link)) ;
		if ($flightResult->num_rows > 0) {
			$flightRow=mysqli_fetch_array($flightResult);
			$numeroVol=$flightRow['f_reference'];	
		}
		//print("numeroVol2:$numeroVol</br>\n");
		if ($remark != 'NULL') {
		   $remark="#$numeroVol $remark";
   		   //print("remark1:$remark</br>\n");
	   }
	   else {
		   $remark="#$numeroVol";	   	
      		//print("remark2:$remark</br>\n");
	   }
	}
	if ($remark != 'NULL') {
		$remark = "'$remark'" ; // As $remark is not quoted in the SQL statement
	}
	//print("remark3:$remark</br>\n");
		
	$paxCount=$cdv_nombre_passager;
	$crewCount=$cdv_nombre_crew;
	$pilotId=$cdv_pilot_name;
	$instructorId=CheckVar($cdv_flight_instructor);
	$dayLandings= $cdv_nombre_atterrissage;
	$nightLandings=0;
	$shareType=CheckVar($cdv_frais_CP);
	$shareType=$cdv_frais_CP;
	if(strcmp($shareType,"NoCP")==0) {
		$shareType='';
	}
	$shareMember=$cdv_frais_CP_type;
	$isInstructorPaid=1;
	if($cdv_frais_DC == "No DC") {
		$isInstructorPaid=0;
	}
	$flightStartHour = mysqli_real_escape_string($mysqli_link, $flightStartHour) ;
	$instructorId = mysqli_real_escape_string($mysqli_link, $instructorId) ;
	//if($planeId=="OO-APV") {

	if (array_key_exists($planeId,$compteurTypeByPlane)) {
		if($compteurTypeByPlane[$planeId]=="6") {
			print("Compteur decimal<br>");
			$engineStartMinute=strval(intval($engineStartMinute)*6);
			$engineEndMinute=strval(intval($engineEndMinute)*6);
		}
	}
	if($bookingid == 0){
		// -----------------------------------------
		// Vol sans reservation: Creation d'une entrée dans la table des bookings
		// =====================
		// -------------------------------------------
		// Special time handling as bookings are in local time
		$dt = new DateTime($startDayTime, new DateTimeZone('UTC'));
		$dt->setTimezone(new DateTimeZone($default_timezone));
		$rStart = $dt->format('Y-m-d H:i');
		$dt = new DateTime($endDayTime, new DateTimeZone('UTC'));
		$dt->setTimezone(new DateTimeZone($default_timezone));
		$rStop = $dt->format('Y-m-d H:i');
		$rType = ($instructorId != '' and $instructorId > 0) ? BOOKING_INSTRUCTOR : BOOKING_PILOT;
		mysqli_query($mysqli_link, "insert into $table_bookings(r_plane, r_start, r_stop, r_pilot, r_instructor, r_type,
					r_from, r_to, r_who, r_address,  r_date)
					values ('$planeId', '$rStart', '$rStop', $pilotId, $instructorId, $rType,
					'$fromAirport', '$toAirport', $pilotId, '" . getClientAddress() . "',sysdate());")
				or journalise($userId, "F", "Impossible d'ajouter dans mes reservations: " . mysqli_error($mysqli_link)) ;
			$r_id = mysqli_insert_id($mysqli_link) ; 
			$bookingid=$r_id;
			$id=$r_id;
			$bookingidPage=$r_id;
		print("<script>var default_bookingid=$bookingid;</script>\n");
		journalise($userId, "I", "Synthetic booking entry added for $planeId, flight $rStart@$fromAirport to $rStop@$toAirport");
	}
	
	if($cdv_logbookid==0) {
		// -----------------------------------------
		// Insert a new segment
		// =====================
		// -------------------------------------------
		//print("Insert a segment</br>");
		//print("Insert a segment</br>");
		if(IsSegmentAlreadyIntroduced($planeId,$startDayTime,$pilotId)) {
			print("<script>alert('Ce vol semble déjà introduit. Un segment commencant à $startDayTime existe déjà. Vous devez l éditer s il n est pas correct.');</script>");
			$logid=0;
			$l_id=0;
		}
		else {
			mysqli_query($mysqli_link, "insert into $table_logbook(l_plane, l_model, l_booking, l_from, l_to,
					l_start_hour, l_start_minute, l_end_hour, l_end_minute, l_flight_start_hour, l_flight_start_minute, l_flight_end_hour, l_flight_end_minute,
					l_start, l_end, l_flight_type, l_remark, l_pax_count, l_crew_count, l_pilot, l_is_pic, l_instructor, l_instructor_paid, l_day_landing, l_night_landing, 
					l_share_type, l_share_member, l_audit_who, l_audit_ip, l_audit_time)
					values ('$planeId', '$planeModel', $bookingidPage, '$fromAirport', '$toAirport',
					$engineStartHour, $engineStartMinute, $engineEndHour, $engineEndMinute, $flightStartHour, $flightStartMinute, $flightEndHour, $flightEndMinute,
					'$startDayTime', '$endDayTime', '$flightType', $remark, $paxCount, $crewCount, $pilotId, $isPICFunction, $instructorId, $isInstructorPaid, $dayLandings, $nightLandings,
					'$shareType', $shareMember, $userId, '" . getClientAddress() . "',sysdate());")
				or journalise($userId, 'F', "<p style=\"color: red;\"><b>Impossible d'ajouter le segment dans le logbook:Vol déjà introduit.</br>Erreur SQL=" . mysqli_error($mysqli_link)."</br>9 fois sur 10, cela signifie que vous avez déjà introduit un vol ou un segment qui démarre au même moment $startDayTime.</br>Faite un Back avec votre Browser et corrigé l'heure de départ.</b></p>") ;			
			$l_id = mysqli_insert_id($mysqli_link) ; 
			$logid=$l_id;
		
			journalise($userId, "I", "New Logbook entry added for $planeId, engine from $engineStartHour: $engineStartMinute to $engineEndHour:$engineEndMinute flight $startDayTime@$fromAirport to $endDayTime@$toAirport");
			
			// Table resume ajoute
			print('<p></p><center><table width=100%" border-spacing="0px"><tbody>
			<tr><td style="background-color: LightSalmon; text-align: center;" colspan="8">Un vol enregistré: Résumé (Heure UTC)</td></tr>
			<tr><td>Avion</td><td>Pilote</td><td>De</td><td>Heure</td><td>A</td><td>Heure</td><td>Durée</td></tr>') ;
			
		}
	}
   else {
		// -----------------------------------------
		// Edit a  segment
		// ===============
		// -------------------------------------------
	    //print("Edit a segment $cdv_logbookid </br>");
//		mysqli_query($mysqli_link, "replace into $table_logbook(l_id, l_plane, l_model, l_booking, l_from, l_to,
//				l_start_hour, l_start_minute, l_end_hour, l_end_minute, l_flight_start_hour, l_flight_start_minute, l_flight_end_hour, l_flight_end_minute,
//				l_start, l_end, l_flight_type, l_remark, l_pax_count, l_crew_count, l_pilot, l_is_pic, l_instructor, l_instructor_paid, l_day_landing, l_night_landing, 
//				l_share_type, l_share_member, l_audit_who, l_audit_ip, l_audit_time)
//				values ('$cdv_logbookid', '$planeId', '$planeModel', $bookingidPage, '$fromAirport', '$toAirport',
//				$engineStartHour, $engineStartMinute, $engineEndHour, $engineEndMinute, $flightStartHour, $flightStartMinute, $flightEndHour, $flightEndMinute,'$startDayTime', '$endDayTime', '$flightType', $remark, $paxCount, $crewCount, $pilotId, $isPICFunction, $instructorId, $isInstructorPaid, $dayLandings, $nightLandings,
//			'$shareType', $shareMember, $userId, '" . getClientAddress() . "',sysdate());")
	
		// As l_id is a foreign key, let's use an UPDATE rather than a REPLACE
		mysqli_query($mysqli_link, "UPDATE $table_logbook
			SET l_plane='$planeId', l_model='$planeModel', l_booking=$bookingidPage, l_from='$fromAirport', l_to='$toAirport',
				l_start_hour=$engineStartHour, l_start_minute=$engineStartMinute, l_end_hour=$engineEndHour, l_end_minute=$engineEndMinute, 
				l_flight_start_hour=$flightStartHour, l_flight_start_minute=$flightStartMinute, l_flight_end_hour=$flightEndHour, l_flight_end_minute=$flightEndMinute,
				l_start='$startDayTime', l_end='$endDayTime', l_flight_type='$flightType', l_remark=$remark, l_pax_count=$paxCount, l_crew_count=$crewCount, 
				l_pilot=$pilotId, l_is_pic= $isPICFunction, l_instructor=$instructorId, l_instructor_paid=$isInstructorPaid, l_day_landing=$dayLandings, l_night_landing=$nightLandings, 
				l_share_type='$shareType', l_share_member= $shareMember, l_audit_who=$userId, l_audit_ip='" . getClientAddress() . "', l_audit_time=sysdate()
				WHERE l_id=$cdv_logbookid")
		or 
				journalise($userId, "F", "(2) l_audit_who=$userId, l_audit_ip='" . getClientAddress() . "', l_audit_time=sysdate() Impossible de mettre à jour le logbook: " . mysqli_error($mysqli_link)) ;		
//		$l_id = mysqli_insert_id($mysqli_link) ; 
		$l_id = $cdv_logbookid ;

	    journalise($userId, "I", "Logbook entry updated for $planeId, engine from $engineStartHour: $engineStartMinute to $engineEndHour:$engineEndMinute flight $startDayTime@$fromAirport to $endDayTime@$toAirport");	
		
				
		// Table resume du vol édité
	    print('<p></p><center><table width=100%" border-spacing="0px"><tbody>
	   <tr><td style="background-color: LightSalmon; text-align: center;" colspan="8">Un vol édité: Résumé (Heure UTC)</td></tr>
	   <tr><td>Avion</td><td>Pilote</td><td>De</td><td>Heure</td><td>A</td><td>Heure</td><td>Durée</td></tr>') ;		
    }
	print("<tr>");
	print("<td>$planeId</td>");
	if($instructorId != 'NULL' && $instructorId != 0) {
		print("<td>$pilotId/$instructorId</td>");
	} else {
		print("<td>$pilotId</td>");
	}
	print("<td>$fromAirport</td>");
	print("<td>$cdv_heure_depart</td>");
	print("<td>$toAirport</td>");
	print("<td>$cdv_heure_arrivee</td>");
	print("<td>$cdv_duree</td>");
	print("</tbody></table></center>");
			
	//----------------------------------------------------
    //Manage Aircraft Technical Log
	//----------------------------------------------------
    if($ATLLevel!="" && $ATLLevel!="select") {
		$ATLId=AddATLIncident($l_id, $planeId, $ATLLevel, $ATLDescription);
        if($ATLId>0) {
			print("<p style=\"color: red;\"><b>An entry $ATLId is introduced in the Technical Log: $ATLLevel </b></p>") ;
			if(SentIncidentMail($ATLId, $planeId, $ATLLevel, $ATLDescription)) {
				print("<p style=\"color: red;\"><b>A mail is send to FI and the fleet team</b></p>") ;
			}
    	}
    }
    
		
	//----------------------------------------------------
	// Associate the flight with a DTO fligth
	// Only if pilot is a student
	//----------------------------------------------------
	//print("</br>Check if the pilot is a student pilotId=$pilotId </br>");
	//print("SELECT user_id FROM $table_user_usergroup_map WHERE user_id = '$pilotId' and group_id='$joomla_student_group';</br>");
	// Look in the $table_user_usergroup_map if the pilot is a student
	$studentResult=mysqli_query($mysqli_link,"SELECT user_id FROM $table_user_usergroup_map WHERE user_id = '$pilotId' and group_id='$joomla_student_group';") 
		or journalise($userId, "F", "Impossible de retrouver le user_id=$pilotId dans table_user_usergroup_map: " . mysqli_error($mysqli_link)) ;
	if ($studentResult->num_rows != 0) {
        if($logid!="") {
            $l_id=$logid;
        }
        if(!HasDTOFlight($l_id)) {
    		//print("C'est aussi un eleve logid=$logid</br>");
    		// Create a flight in the DTO table rapcs_dto_flight
    		$flight = new Flight() ;
    		$flight->flightLog = $l_id;
    		$flight->student= $pilotId;
    		if($isPICFunction>0) {
    			$flight->flightType="Solo";
                //print("Creating Flight DTO flightType Solo</br>");
    		}
    		else {
    			$flight->flightType="DC";			
                //print("Creating Flight DTO flightType DC</br>");
    		}
    		$flight->save();
    		//print("FlightLog saved</br>");
        }
	}
	else {
		//print("Ce n'est pas un eleve</br");
	}
}
// QRCode apres enregistrement
$epcString="";
print("<center><span id=\"id_payment_after\">");
print("<h3>Paiement du vol - Montant: <span id=\"id_payment_amount_after\"></span> &euro;</br>");
print("Communication : \"<span id=\"id_payment_communication_after\"></span>\"</br>Compte : BE64 7320 3842 1852</h3>\n");
// evyncke: as the $epcString is still empty, no need to specify the src as it is invalid... was  src=\"https://chart.googleapis.com/chart?cht=qr&chs=300x300&&chl=" . urlencode($epcString) . "\"
print("<img style=\"text-align: right;\" id=\"id_payment_qr_code_after\" width=\"150\" height=\"150\" ></center>");
print("</span>");

	//----------------------------------------------------
	// Associate the flight with an IF or an INIT flight
	//----------------------------------------------------
    //print("cdv_flightreferenceid=$cdv_flightreferenceid;numeroVol=$numeroVol;shareType=$shareType;shareMember=$shareMember</br>");	
if($numeroVol!="" && $shareType=="CP1" && ($shareMember==-3 ||$shareMember==-4 || $shareMember==-6)) {
		//print("Associate the flight with an IF or an INIT flight</br>");	
		$f_date_flown=$startDayTime;
		$cdv_flightreferenceid=0;
		if($cdv_flightreferenceid==0) {
			//print("SELECT f_id, f_reference, f_type, f_date_flown , f_booking FROM $table_flight WHERE f_reference = '$numeroVol';</br>") ;
			$flightResult=mysqli_query($mysqli_link,"SELECT f_id, f_reference, f_type, f_date_flown, f_booking  FROM $table_flight WHERE f_reference = '$numeroVol';") 
				or journalise($userId, "F", "Impossible de retrouver le f_reference=$numeroVol dans table_flight: " . mysqli_error($mysqli_link)) ;
			if ($flightResult->num_rows == 0) {
				print("<script>alert('La reference du vol IF/INI $numeroVol n existe pas. Ce référence de vol IF/INI n\'est pas fermée.');</script>");
    			print("<p style=\"color: red;\"><b>The flight $numeroVol is NOT correctly closed</b></p>") ;
			}
			else {
				$flightRow=mysqli_fetch_array($flightResult);
				$cdv_flightreferenceid=	$flightRow['f_id'];	
                $associatedBookingID=$flightRow['f_booking'];
                if($associatedBookingID != $bookingid) {
    				print("<script>alert('La reference du vol IF/INI $numeroVol n est pas associé à cette réservation. Il faut corriger dans l outil de gestion des vols découvertes ou utiliser le numéro de vol correct. Cette référence de vol IF/INI n est pas fermée.');</script>");
                    $cdv_flightreferenceid=0;
        			print("<p style=\"color: red;\"><b>The flight $numeroVol is NOT correctly closed</b></p>") ;
                }
			}
		}
		if($cdv_flightreferenceid!=0) {
			mysqli_query($mysqli_link,"update $table_flight set f_date_flown='$f_date_flown', f_pilot=$pilotId, f_booking=$bookingidPage where f_id=$cdv_flightreferenceid;") 
				or journalise($userId, "F", "Impossible d'ajouter dans le table_flight:" . mysqli_error($mysqli_link)) ;
			//$flightRow=mysqli_fetch_array($flightResult);
			//$f_id = mysqli_insert_id($mysqli_link) ; 
			print("<p style=\"color: red;\"><b>The flight $numeroVol is correctly closed</b></p>") ;
		}	
}

//-----------------------------------------------------------------------------------------------------
// display any previous entries related to this booking
//-----------------------------------------------------------------------------------------------------

print('<p></p>');
$end_Time='';
$toAirport='';
if($bookingid) {
	//print(" 2. bookingid=$bookingid</br>");
	// Now, display any previous entries related to this booking
	$result = mysqli_query($mysqli_link, "select l_id, l_start, l_end, l_plane, l_from, l_to, l_flight_type, 
	l_start_hour, l_start_minute, l_end_hour, l_end_minute, l_day_landing,l_pax_count, l_crew_count, l_flight_type,l_is_pic, l_share_type, l_share_member, l_remark, l_instructor_paid,
	l_audit_time, p.last_name as pilotName, i.last_name as instructorName
			from $table_logbook l join $table_person p on l.l_pilot = p.jom_id left join $table_person i on l.l_instructor = i.jom_id
			where l_booking = $bookingid order by l_start")
		or journalise($userId, "F", "Impossible de lire les entrees pour réservation $bookingid: " . mysqli_error($mysqli_link)) ;

	$this_segment_id = mysqli_num_rows($result) + 1 ;

	if ($this_segment_id > 1) {

        // Table Segments deja introduits
		print('<center><table width="100%" border-spacing="0px"><tbody>
		<tr><td style="background-color: GreenYellow; text-align: center;" colspan="13">Segments déjà introduits pour cette réservation. (Heure UTC)</td></tr>
			<tr><th>#</th><th>Avion</th><th>Pilote</th><th>De</th><th>Départ</th><th>A</th><th>Arrivée</th><th>Type</th><th>Compteur</th><th>Atterr</th><th>Crew/Pass</th><th>Remarques</th><th>Action</th></tr>') ;
		$aSegment=0;
		while ($row = mysqli_fetch_array($result)) {
			$logid=$row['l_id'];
			// As the OVH MySQL server does not have the timezone support, needs to be done in PHP
			$start_UTC = gmdate('H:i', strtotime("$row[l_start] UTC")) ;
			$end_UTC = gmdate('H:i', strtotime("$row[l_end] UTC")) ;
			$end_Time=$row['l_end'];
			//$MOStart=$row['l_start_hour'].'.'.$row['l_start_minute'];
			//$MOEnd=$row['l_end_hour'].'.'.$row['l_end_minute'];
		
			$MO=$row['l_start_hour'].'.'.$row['l_start_minute'];
			$MO=$MO.'->';
			$MO=$MO.$row['l_end_hour'].'.'.$row['l_end_minute'];
            $Min=$row['l_end_hour']*60+$row['l_end_minute']-$row['l_start_hour']*60-$row['l_start_minute'];
            $MO=$MO." (".$Min."min)";
			if (array_key_exists($row["l_plane"],$compteurTypeByPlane)) {
				if($compteurTypeByPlane[$row["l_plane"]]=="6") {	
					$minValue=	$row['l_start_minute']/6;
					$MO=$row['l_start_hour'].'.'.$minValue;
					$MO=$MO.'->';
					$minValue=	$row['l_end_minute']/6;
					$MO=$MO.$row['l_end_hour'].'.'.$minValue;
					$Min=$row['l_end_hour']*60+$row['l_end_minute']-$row['l_start_hour']*60-$row['l_start_minute'];
					$MO=$MO." (".$Min."min)";
				}
			}
			$aSegment+=1;
			$instructorPaid="";
			$crew_Count=$row['l_crew_count'];
			if($crew_Count==0) $crew_Count=1;
			if ($row['instructorName'] == '')
				$crew = $row['pilotName'];
			else {
				$crew = $row['pilotName'] . '/' . $row['instructorName'] ;
				if($row['l_instructor_paid']==0) {
					$instructorPaid="No DC";
				}
			}
			$crew=db2web($crew);
			if ($row['l_is_pic']==1) {
				if ($row['instructorName'] == '')
					$crew=$crew.' (PIC)';
				else {
					$crew=$crew.' (PIC-Recheck)';
					if($crew_Count==1) $crew_Count=2;
				}
			}
			else {
				$crew=$crew.' (DC)';
				if($crew_Count==1) $crew_Count=2;
			}

			$remark="";
			$remark=GetFullRemarks($row['l_share_type'],$row['l_share_member'], $row['l_remark'], $instructorPaid );
			$toAirport=$row['l_to'];
			
			// retrieve the Flight Id from the LogId
			$dtoFlightId=GetDTOFlightIdFromLogId($logid);
			//print("dtoFlightId=$dtoFlightId</br>");
			
			print("<tr style='text-align: center;'>
				<td>$aSegment ($logid)</td>
				<td>$row[l_plane]</td>
				<td>$crew</td>
				<td>$row[l_from]</td>
				<td>$start_UTC</td>
				<td>$row[l_to]</td>
				<td>$end_UTC</td>
				<td>$row[l_flight_type]</td>
				<td>$MO</td>
				<td>$row[l_day_landing]</td>
				<td>$crew_Count / $row[l_pax_count]</td>
				<td>$remark</td>
				<td><button type=\"button\" value=\"Del\" onclick=\"redirectLogbookDelete('$_SERVER[PHP_SELF]',$bookingid,$logid,'$auth','$row[l_audit_time]');\">&#128465; Effacer</button>&nbsp;	
				<button type=\"button\" value=\"Edit\" onclick=\"window.location.href='$_SERVER[PHP_SELF]?edit=1&id=$bookingid&logid=$logid';\">&#9998;&nbsp; Editer</button>&nbsp;");
			//<button type=\"button\" value=\"QRCode\" onclick=\"displayQRCode();\">&#xf029; QRCode</button>"
				if($dtoFlightId>0) {
					print("&nbsp;<button type=\"button\" value=\"DTO\" onclick=\"window.location.href='https://www.spa-aviation.be//resa/dto.flight.php?flight=$dtoFlightId';\">DTO</button>");
				}
				print("
				</td>
				</tr>\n") ;
		}
		print('</tbody></table></center>');
	}
	else {
			//print('xxxxx2this_segment_id=$this_segment_id</br>');
		//print('<br/>Aucun Segment introduit pour cette réservation.</br>');
		print('<center><table border-spacing="0px"><tbody>
		<tr><td style="background-color: GreenYellow; text-align: center;" colspan="8">Aucun Segment introduit pour cette réservation</td></tr></tbody></table></center>');	
	}
}
else {
	print('<center><table border-spacing="0px"><tbody>
	<tr><td style="background-color: GreenYellow; text-align: center;" colspan="8">Introduction vol sans  réservation</td></tr></tbody></table></center>');		
}

$editFlag=0;
//---------------------------------------------------------------------------
// Edit a segment
if (isset($_REQUEST['edit']) and $_REQUEST['edit'] != '') {
	$logid=$_REQUEST['logid'];
	$editFlag=1;
	//print("logid=$logid</br>\n");
   // Load N entries related to this logid
   $result = mysqli_query($mysqli_link, "select l_id, l_instructor, l_pilot, l_start, l_end, l_plane, l_from, l_to, l_flight_type, 
	 l_start_hour, l_start_minute, l_end_hour, l_end_minute, l_flight_start_hour, l_flight_start_minute, l_flight_end_hour, l_flight_end_minute,
     l_day_landing,l_pax_count, l_crew_count, l_flight_type,l_is_pic, l_share_type, l_share_member, 
	 l_remark, l_instructor_paid,
     l_audit_time, p.last_name as pilotName, i.last_name as instructorName
		from $table_logbook l join $table_person p on l.l_pilot = p.jom_id left join $table_person i on l.l_instructor = i.jom_id
		where l_booking = $bookingid order by l_start")
	or journalise($userId, "F", "Impossible de lire les entrees pour segment $logid: " . mysqli_error($mysqli_link)) ;

   $this_segment_id = mysqli_num_rows($result) + 1 ;
   //print("this_segment_id=$this_segment_id</br>\n");
   print("<script>\n");
   if ($this_segment_id > 1) {
	 $aSegment=0;
	 while ($row = mysqli_fetch_array($result)) {
	 	 $aSegment=$aSegment+1;
		 if($logid==$row['l_id']) {
		 	//print("Found Segment=$aSegment</br>\n");	
			print("var default_editflag=$editFlag;\n");
			print("var default_segment=$aSegment;\n");
			print("var default_logbookid=$logid;\n");
			print("var default_plane=\"$row[l_plane]\";\n");
			print("var default_pilot=$row[l_pilot];\n");
			$anInstructor=0;
			if($row['l_instructor']!= NULL){
				$anInstructor=$row['l_instructor'];
			}
			print("var default_instructor=$anInstructor;\n");
			$start_UTC = gmdate('Y-m-d H:i', strtotime("$row[l_start] UTC")) ;
			print("var default_date_heure_depart=\"$start_UTC\";\n");
			$end_UTC = gmdate('Y-m-d H:i', strtotime("$row[l_end] UTC")) ;
			print("var default_date_heure_arrivee=\"$end_UTC\";\n");
			print("var default_day_landing=$row[l_day_landing];\n");
			print("var default_pax_count=$row[l_pax_count];\n");
			if($row['l_crew_count'] != NULL){
				print("var default_crew_count=$row[l_crew_count];\n");
			}
			else {
				print("var default_crew_count=1;\n");				
			}
			print("var default_flight_type=\"$row[l_flight_type]\";\n");	
			print("var default_from=\"$row[l_from]\";\n");	
			print("var default_to=\"$row[l_to]\";\n");
			print("var default_is_pic=$row[l_is_pic];\n");
			print("var default_instructor_paid=$row[l_instructor_paid];\n");
			$shareType=$row['l_share_type'];
			if($shareType=="") {
				$shareType="NoCP";
			}
			print("var default_share_type=\"$shareType\";\n");
			print("var default_share_member=$row[l_share_member];\n");
			print("var default_remark=\"$row[l_remark]\";\n");
			$compteurStart="$row[l_start_hour].";
			if($row['l_start_minute']<10) {
				$compteurStart=$compteurStart."0$row[l_start_minute]";
			}
			else {
				$compteurStart=$compteurStart."$row[l_start_minute]";
			}
			print("var default_compteur_moteur_start=\"$compteurStart\";\n");
			$compteurEnd="$row[l_end_hour].";
			if($row['l_end_minute']<10) {
				$compteurEnd=$compteurEnd."0$row[l_end_minute]";
			}
			else {
				$compteurEnd=$compteurEnd."$row[l_end_minute]";
			}				 	
			print("var default_compteur_moteur_end=\"$compteurEnd\";\n");		 	
			$compteurFlightStart="$row[l_flight_start_hour].";
			if($row['l_flight_start_minute']<10) {
				$compteurFlightStart=$compteurFlightStart."0$row[l_flight_start_minute]";
			}
			else {
				$compteurFlightStart=$compteurFlightStart."$row[l_flight_start_minute]";
			}
			print("var default_compteur_flight_start=\"$compteurFlightStart\";\n");
			$compteurFlightEnd="$row[l_flight_end_hour].";
			if($row['l_flight_end_minute']<10) {
				$compteurFlightEnd=$compteurFlightEnd."0$row[l_flight_end_minute]";
			}
			else {
				$compteurFlightEnd=$compteurFlightEnd."$row[l_flight_end_minute]";
			}				 	
			print("var default_compteur_flight_end=\"$compteurFlightEnd\";\n");	

            //Edit Aircraft Techical log
            $ATLIncidentId=GetATLIncidentID($logid);
			//print("(1) GetATLIncidentID($logid) ATLIncidentId=$ATLIncidentId<br>");
            if( $ATLIncidentId != 0)	{
                // An incident was associated to the segment
                $ATLSeverity=GetATLIncidentSeverity($ATLIncidentId);
                $ATLDescription=GetATLIncidentDescription($ATLIncidentId);
                print("var default_ATL_level=\"$ATLSeverity\";\n");
                print("var default_ATL_description=\"$ATLDescription\";\n");                
            }
            else {
                // no incident  associated to the segment
                print("var default_ATL_level=\"nothing\";\n");
                print("var default_ATL_description=\"\";\n");
            }
		 }
	 }
   }
}
else {
	print("<script>\n");
//---------------------------------------------------------------------------
// create a new segment
	if($bookingid != '0') {
		// Retrieve information in the Table_Booking for the bookingid
		//printf("SELECT r_id, r_plane, r_start FROM $table_bookings WHERE r_id = $bookingid</br>");
		$result=mysqli_query($mysqli_link,"SELECT r_id, r_plane, r_start, r_stop, r_pilot, r_instructor FROM $table_bookings WHERE r_id = $bookingid") 
			or journalise($userId, "F", "Impossible de retrouver le bookingid dans booking pour $bookingid: " . mysqli_error($mysqli_link)) ;
		$row=mysqli_fetch_array($result);
		
		// Retrieve the flight reference for IF and INIT flight
		//print("</br>SELECT f_id, f_reference FROM $table_flight WHERE f_booking = $bookingid</br>");
		$flightResult=mysqli_query($mysqli_link,"SELECT f_id, f_reference, f_type, f_date_flown FROM $table_flight WHERE f_booking = $bookingid") 
			or journalise($userId, "F", "Impossible de retrouver le f_reference dans table_flight pour $bookingid: " . mysqli_error($mysqli_link)) ;
		$flightRow=mysqli_fetch_array($flightResult);
	
		//if ($row['r_instructor']== NULL or $row['r_instructor'] == '' or $row['r_instructor'] == $row['r_pilot']) {
		if ($row['r_instructor']== NULL or $row['r_instructor'] == '') {
			$rinstructor=0;
		}
		else {
			$rinstructor=$row['r_instructor'];		
		}
		
		print("var default_plane=\"$row[r_plane]\";\n");
		print("var default_instructor=$rinstructor;\n");
		$start_UTC = gmdate('Y-m-d H:i', strtotime("$row[r_start] $default_timezone")) ;
		print("var default_date_heure_depart=\"$start_UTC\";\n");
		if($end_Time != NULL && $end_Time != '') {
			print("var default_date_heure_depart=\"$end_Time\";\n");
		}
        $fReference='';
        if (isset($flightRow['f_reference'])) {
            $fReference=$flightRow['f_reference'];
        }
		print("var default_flight_reference=\"$fReference\"\n");
        $fType='';
        if (isset($flightRow['f_type'])) {
            $fType=$flightRow['f_type'];
        }
		print("var default_flight_type=\"f_type\"\n");
		if($fReference!='') {
			print("var default_flight_id=$flightRow[f_id]\n");
			print("var default_share_type=\"CP1\";\n");
			if($flightRow['f_type']=='D') {
				print("var default_share_member=-4;\n");
			} 
			else {
				print("var default_share_member=-3;\n");
			}	
		}
		else {
			print("var default_flight_id=0\n");
		}
		if($toAirport!="") {
			print("var default_from=\"$toAirport\";\n");	
		}
		//printf("r_plane=$row[r_plane]</br>");
		//printf("r_start=$row[r_start]</br>");
	}
	else {
		//print("</br>1111 SELECT f_id, f_reference FROM $table_flight WHERE f_id = $bookingid</br>");
		print("var default_plane=\"\";\n");
		print("var default_instructor=0;\n");
		print("var default_date_heure_depart=\"\";\n");
		print("var default_flight_reference=\"\"\n");
		if (isset($flightRow['f_id'])) {
    	   print("var default_flight_id=\"$flightRow[f_id]\"\n");
		}
		else{
			print("var default_flight_id=\"\"\n");
		}
		if (isset($flightRow['f_type'])) {
			print("var default_flight_type=\"$flightRow[f_type]\"\n");
		}
		else {
			print("var default_flight_type=\"\"\n");
		}
	}

	if($bookingid!='0') {		
		// Retrieve information in the Table_LogBook for the bookingid
		// Allow to retrieve previous segment
		$result=mysqli_query($mysqli_link,"SELECT l_booking FROM $table_logbook WHERE l_booking = $bookingid") 
			or journalise($userId, "F", "Impossible de retrouver le bookingid dans logbook pour $bookingid: " . mysqli_error($mysqli_link)) ;
		$segmentcount=1;
		while($row=mysqli_fetch_array($result)) {
			$segmentcount+=1;
			print("var default_segment=$segmentcount;\n");	
		} ;	
		print("var default_segment=$segmentcount;\n");

	}
	else {
		print("var default_segment=1;\n");	
	}
}
print("</script>\n");
?>
<form action="<?=$_SERVER['PHP_SELF']?>" method="GET" onkeydown="return event.key != 'Enter';" autocomplete="off">
<p> </p>
<table width="100%" style="margin-left: auto; margin-right: auto;" > 
<tbody>
<tr>
<?php
if($editFlag==0) {
	print('<td style="background-color: #13d8f2; text-align: Center;" colspan="2">');
	print("<b>Introduction nouveau segment</b>");
}
else {
	print('<td style="background-color: #90EE90; text-align: Center;" colspan="2">');
	print("<b>Edition segment</b>");	
}
?>
</td>
</tr>
<tr id="id_cdv_auth_row" hidden>
<td class="segmentLabel">auth</td>
<td class="segmentInput"><input id="id_cdv_auth" name="auth" size="8" type="text" value="<?=$auth?>" /></td>
</tr>
<tr id="id_cdv_bookingid_row">
<td class="segmentLabel">booking id</td>
<td class="segmentInput"><input id="id_cdv_bookingid" name="cdv_bookingid" size="8" type="text" value="" /></td>
</tr>
<tr id="id_cdv_logbookid_row">
<td class="segmentLabel">logbook id</td>
<td class="segmentInput"><input id="id_cdv_logbookid" name="cdv_logbookid" size="8" type="text" value="" /></td>
</tr>
<tr id="id_cdv_flightreferenceid_row">
<td class="segmentLabel">flight reference id</td>
<td class="segmentInput"><input id="id_cdv_flightreferenceid" name="cdv_flightreferenceid" size="8" type="text" value="" /></td>
</tr>
<tr id="id_cdv_segment_count_row">
<td class="segmentLabel">Segment</td>
<td class="segmentInput"><input id="id_cdv_segment_count" name="cdv_segment_count" size="8" type="text" value="0" /></td>
</tr>
<tr>
<td class="segmentLabel">Avion</td>
<td class="segmentInput"><select id="id_cdv_aircraft" name="cdv_aircraft"></select></td>
</tr>
<tr id="id_cdv_aircraft_model_row">
<td class="segmentLabel">Model</td>
<td class="segmentInput"><input id="id_cdv_aircraft_model" name="cdv_aircraft_model" size="8" type="text" value="" /></td>
</tr>
<tr>
<td class="segmentLabel">Date</td>
<td class="segmentInput"><input id="id_cdv_flight_date" name="cdv_flight_date" type="date" placeholder="jj/mm/aaaa" /></td>
</tr>
<tr>
<td class="segmentLabel">Nom</td>
<td class="segmentInput"><select id="id_cdv_pilot_name" name="cdv_pilot_name">
<option selected="selected" value=""></option>
</select></td>
</tr>
<tr>
<td class="segmentLabel">Fonction</td>
<td class="segmentInput"><select id="id_cdv_pilot_function" name="cdv_pilot_function">
<option selected="selected" value="PIC">PIC</option>
<option value="DC">DC</option>
<option value="PICRecheck">PIC-Recheck</option>
<option value="PICSupervise">PIC-Supervis&eacute;</option>
</select></td>
</tr>
<tr id="id_cdv_flight_instructor_row">
<td class="segmentLabel">Flight Instructor</td>
<td class="segmentInput"><select id="id_cdv_flight_instructor" name="cdv_flight_instructor">
<option selected="selected" value=""></option>
</select></td>
</tr>
<tr>
<td class="segmentLabel">Lieu Départ</td>
<td class="segmentInput"><input id="id_cdv_departure_airport" name="cdv_departure_airport" size="6" type="text" value="EBSP" placeholder="EBSP" autocomplete="off"/></td>
</tr>
<tr>
<td class="segmentLabel">Lieu Destination</td>
<td class="segmentInput"><input id="id_cdv_arrival_airport" name="cdv_arrival_airport" size="6" type="text" value="EBSP" placeholder="EBSP" autocomplete="off"/></td>
</tr>
<tr id="id_cdv_compteur_depart_vol_row">
<td class="segmentLabel">Compteur Vol Départ</td>
<td class="segmentInput"><input id="id_cdv_compteur_vol_depart" name="cdv_compteur_vol_depart" size="8" type="text" placeholder="6605.10" autocomplete="off"/></td>
</tr>
<tr id="id_cdv_compteur_arrivee_vol_row">
<td class="segmentLabel">Compteur Vol Arrivée</td>
<td class="segmentInput"><input id="id_cdv_compteur_vol_arrivee" name="cdv_compteur_vol_arrivee" size="8" type="text" placeholder="6605.40" autocomplete="off"/></td>
</tr>
<tr id="id_cdv_compteur_duree_vol_row">
<td class="segmentLabel">Durée Vol (Airborne)</td>
<td class="segmentInput"><input id="id_cdv_compteur_vol_duree" name="cdv_compteur_vol_duree" size="6" type="text" placeholder="00:30" autocomplete="off"/></td>
</tr>
<tr>
<td class="segmentLabel">Compteur Moteur Départ</td>
<td class="segmentInput"><input id="id_cdv_compteur_depart" name="cdv_compteur_depart"   size="8" type="text" placeholder="7705.05" autocomplete="off"/></td>
</tr>
<tr>
<td class="segmentLabel">Compteur Moteur Destination</td>
<td class="segmentInput"><input id="id_cdv_compteur_arrivee" name="cdv_compteur_arrivee"   size="8" type="text" placeholder="7705.45" autocomplete="off"/></td>
</tr>
<tr>
<td class="segmentLabel">Durée Moteur</td>
<td class="segmentInput"><input id="id_cdv_compteur_duree" name="cdv_compteur_duree" size="6" type="text" placeholder="00:40" autocomplete="off"/></td>
</tr>
<tr>
<td class="segmentLabel">Heure Départ (UTC)</td>
<td class="segmentInput"><input id="id_cdv_heure_depart" name="cdv_heure_depart" size="6" type="text" placeholder="16:00" autocomplete="off" /></td>
</tr>
<tr>
<td class="segmentLabel">Heure Destination (UTC)</td>
<td class="segmentInput"><input id="id_cdv_heure_arrivee" name="cdv_heure_arrivee" size="6" type="text" placeholder="16:40" autocomplete="off"/></td>
</tr>
<tr>
<td class="segmentLabel">Durée (OBT)</td>
<td class="segmentInput"><input id="id_cdv_duree" name="cdv_duree" size="6" type="text" placeholder="00:40" autocomplete="off"/></td>
</tr>
<tr>
<td class="segmentLabel">Nombre atterrissages</td>
<td class="segmentInput"><select id="id_cdv_nombre_atterrissage" name="cdv_nombre_atterrissage">
<option selected="selected" value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>
<option value="4">4</option>
<option value="5">5</option>
<option value="6">6</option>
<option value="7">7</option>
<option value="8">8</option>
<option value="9">9</option>
<option value="10">10</option>
</select></td>
</tr>
<tr>
<td class="segmentLabel">Nature du vol</td>
<td class="segmentInput"><select id="id_cdv_nature_vol" name="cdv_nature_vol">
<option selected="selected" value="Local">Local</option>
<option value="Nav">Nav</option>
</select></td>
</tr>
<tr id="id_cdv_nombre_crew_row" style="height: 14px;">
<td class="segmentLabel">Nombre de crews</td>
<td class="segmentInput"><select id="id_cdv_nombre_crew" name="cdv_nombre_crew">
<option selected="selected" value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>
<option value="4">4</option>
</select></td>
</tr>
<tr>
<td class="segmentLabel">Nombre de passagers</td>
<td class="segmentInput"><select id="id_cdv_nombre_passager" name="cdv_nombre_passager">
<option selected="selected" value="0">0</option>
<option value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>
</select></td>
</tr>
<tr>
<td class="segmentLabel"style="vertical-align: top;">Partage des frais</td>
<td class="segmentInput"><select id="id_cdv_frais_CP" name="cdv_frais_CP">
<option selected="selected" value="NoCP">Pas de partage</option>
<option value="CP1">CP1(100% payé par)</option>
<option value="CP2">CP2(50% payé par)</option>
</select><select id="id_cdv_frais_CP_type" name="cdv_frais_CP_type">
<option selected="selected" value="0">Select ...</option>
</select>
<input id="id_cdv_frais_numero_vol" name="cdv_frais_numero_vol" size="15" type="text" placeholder="V-INIT-231xxx" autocomplete="off" />
<select id="id_cdv_frais_CP_PAX" name="cdv_frais_CP_PAX">
<option selected="selected" value=""></option>
</select><br /><input id="id_cdv_frais_remarque" name="cdv_frais_remarque" size="20" type="text" placeholder="Remarques" autocomplete="off"/></td>
</tr>
<tr id="id_cdv_frais_DC_row" style="height: 14px;">
<td class="segmentLabel">Frais Instructeur</td>
<td class="segmentInput"><select id="id_cdv_frais_DC" name="cdv_frais_DC">
<option selected="selected" value="DC">DC</option>
<option value="No DC">No DC</option>
</select></td>
</tr>

<tr id="id_cdv_ATL_row">
<td class="segmentLabel"style="vertical-align: top;">Aircraft Technical Log<br><i>(ne pas introduire 2 fois le même problème)</i>
</td>
<td class="segmentInput"><span class="tooltip">
<table style="border: 0px solid black;width: 100%; margin-left: auto; margin-right: auto;" >
<tbody>
<tr><td class="segmentInput">
<select id="id_cdv_ATL_level" name="cdv_ATL_level">
<option selected="select" value="select">Select</option>
<option value="nothing">Nothing to Declare</option>
<option value="nohazard">No Hazard to flight safety</option>
<option value="hazard">Hazard to fly safety</option>
</select><span class="tooltiptext" id="id_atltooltip">Log1<br>log2<br>log3</span></span>
</td></tr>
<tr id="id_cdv_ATL_description_row"><td class="segmentInput">
<input id="id_cdv_ATL_description" name="cdv_ATL_description" size="35" type="text" placeholder="Description du problème" autocomplete="off" />
</td></tr>
</tbody></table>
</td>
</tr>

<tr id="id_cdv_qrcode_montant_total_pilote_row">
<td class="segmentLabel">Montant Total Pilote</td>
<td class="segmentInput"><input id="id_cdv_qrcode_montant_total_pilote" name="cdv_qrcode_montant_total_pilote"  type="text" value="0" /></td>
</tr>
<tr id="id_cdv_qrcode_communication_pilote_row">
<td class="segmentLabel">Communication Pilote</td>
<td class="segmentInput"><input id="id_cdv_qrcode_communication_pilote" name="cdv_qrcode_communication_pilote"  type="text" value="communication" /></td>
</tr>
</tbody>
</table>
<p><center><input type="submit" id="id_submitButton" value="Enregistrer le vol" name="action"/>&nbsp;
<?php
print("<button type=\"button\" value=\"Fill\" onclick=\"window.location.href='$_SERVER[PHP_SELF]?id=$bookingid&auth=$auth';\">Annuler</button>");
?>
</center></p>
</form>
<p></p>
<table style="width: 100%; margin-left: auto; margin-right: auto;" >
<tbody>
<tr id="id_cdv_prix_row" style="height: 14px;">
<td style="background-color: #edc4f5; text-align: center;" colspan="2">
<b>Prix du vol</b>
</td>
</tr>
<tr id="id_cdv_prix_avion_row">
<td width="50%" style="background-color: #edc4f5; text-align: right;">Avion</td>
<td><input id="id_cdv_prix_avion" name="cdv_prix_avion" size="20" type="text" value="- €" /></td>
</tr>
<tr id="id_cdv_prix_fi_row">
<td style="background-color: #edc4f5; text-align: right;">FI</td>
<td><input id="id_cdv_prix_fi" name="cdv_prix_fi" size="20" type="text" value="- €" /></td>
</tr>
<tr id="id_cdv_prix_passager_row">
<td style="background-color: #edc4f5; text-align: right;">Taxe Passagers</td>
<td><input id="id_cdv_prix_passager" name="cdv_prix_passager" size="10" type="text" value="- €" /></td>
</tr>
<tr id="id_cdv_prix_total_pilote_row">
<td style="background-color: #edc4f5; text-align: right;">Total Pilote</td>
<td><input id="id_cdv_prix_total_pilote" name="cdv_prix_total" size="10" type="text" value="- €" /></td>
</tr>
<tr id="id_cdv_prix_reference_row">
<td style="background-color: #edc4f5; text-align: right;">Référence payement</td>
<td><input id="id_cdv_prix_reference" name="cdv_prix_reference" size="20" type="text" value=".../.../..." /></td>
</tr>
<tr id="id_cdv_prix_solde_row">
<td style="background-color: #edc4f5; text-align: right;">Solde sur  compte pilote</td>
<td><input id="id_cdv_prix_solde" name="cdv_prix_solde" size="10" type="text" value="- €" /></td>
</tr>
<tr id="id_cdv_prix_total_cp1_row">
<td style="background-color: #edc4f5; text-align: right;">Total CP1</td>
<td><input id="id_cdv_prix_total_cp1" name="cdv_prix_total_cp1" size="10" type="text" value="- €" /></td>
</tr>
<tr id="id_cdv_prix_total_cp2_row">
<td style="background-color: #edc4f5; text-align: right;">Total CP2</td>
<td><input id="id_cdv_prix_total_cp2" name="cdv_prix_total_cp2" size="10" type="text" value="- €" /></td>
</tr>
</tbody>
</table>

<center><span id="id_payment">
<h3>Paiement du vol - Montant: <span id="id_payment_amount"></span> &euro;</br>
Communication : "<span id="id_payment_communication"></span>"</br>Compte : BE64 7320 3842 1852</h3>
<!-- evyncke the src URL is invalid as it contains 'url', no need to specify the src=, was  src="https://chart.googleapis.com/chart?cht=qr&chs=300x300&&chl=url>"-->
<img style="text-align: right;" id="id_payment_qr_code" width="150" height="150"></center>
</span>

<p></p>
<p><center><input class="button" type="button" style="background-color: green; color: white;" value="Mon Folio" onclick="javascript:document.location.href='../../resa/mobile_folio.php';"></input></center>
<center><input class="button" type="button" style="background-color: green; color: white;" value="Accès au TechLog" onclick="javascript:document.location.href='../../resa/mobile_incidents.php';"></input></center>
<center><input class="button" type="button" style="background-color: green; color: white;" value="Mon carnet de vol" onclick="javascript:document.location.href='../../resa/mobile_mylog.php';"></input></center>
<center><input class="button" type="button" style="background-color: green; color: white;" value="Retour à la page de réservation" onclick="javascript:document.location.href='https://www.spa-aviation.be/index.php/fr/resa-full/reserver';"></input></center></p>
<p></p>
<p></p>
<p><b>Syntaxe:</b></p>
<p>Heure: "09:30", "09 30", "9:30", "9 30", "930"</p>
<p>Durée: "2:20", "2h20", "140"</p>
<p>Compteur : xxxxyy (OO-APV xxxxy)  xxxx.yy (OO-APV xxxx.y) ou xxxx,yy  (OO-APV xxxx,y)</p>
<!-- Those scripts must be at the end and not in the header as some prefil operations are not done in the onLoad() :-( ...-->
<script src="data/planes.js" ></script>
<script src="data/instructors.js"></script>
<script src="data/members.js"></script>
<script src="data/shareCodes.js"></script>
<script src="data/pilots.js"></script>
<!---<script src="https://www.spa-aviation.be/resa/CP_frais_type.js"\></script>-->
<script src="https://www.spa-aviation.be/resa/data/prix.js"></script>
<!---<script src="https://www.spa-aviation.be/resa/script_carnetdevol_InProgress.js"></script>-->
<script src="https://www.spa-aviation.be/resa/js/script_carnetdevol.js"></script>
</body>
</html>