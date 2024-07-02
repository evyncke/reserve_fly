<?php
/*
   Copyright 2014-2024 Eric Vyncke

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

require_once 'flight_header.php' ;
$modify = (isset($_REQUEST['modify']) and $_REQUEST['modify'] != '') ? TRUE : FALSE ;
$delete = (isset($_REQUEST['delete']) and $_REQUEST['delete'] != '') ? TRUE : FALSE ;
$create = (isset($_REQUEST['create']) and $_REQUEST['create'] != '') ? TRUE : FALSE ;
$pay_open = (isset($_REQUEST['pay_open']) and $_REQUEST['pay_open'] != '') ? TRUE : FALSE ;
$pay = (isset($_REQUEST['pay']) and $_REQUEST['pay'] != '') ? TRUE : FALSE ;
$addPayment = (isset($_REQUEST['addPayment']) and $_REQUEST['addPayment'] == 'Y') ? TRUE : FALSE ;
$deletePayment = (isset($_REQUEST['deletePayment']) and $_REQUEST['deletePayment'] != '') ? TRUE : FALSE ;
$assign_pilot = (isset($_REQUEST['assign_pilot']) and $_REQUEST['assign_pilot'] != '') ? TRUE : FALSE ;
$pilot_open = (isset($_REQUEST['pilot_open']) and $_REQUEST['pilot_open'] != '') ? TRUE : FALSE ;
$add_pax = (isset($_REQUEST['add_pax']) and $_REQUEST['add_pax'] != '') ? TRUE : FALSE ;
$delete_pax = (isset($_REQUEST['delete_pax']) and $_REQUEST['delete_pax'] != '') ? TRUE : FALSE ;
$modify_pax = (isset($_REQUEST['modify_pax']) and $_REQUEST['modify_pax'] != '') ? TRUE : FALSE ;
$pax_open = (isset($_REQUEST['pax_open']) and $_REQUEST['pax_open'] != '') ? TRUE : FALSE ;
$link_to = (isset($_REQUEST['link_to']) and $_REQUEST['link_to'] != '') ? $_REQUEST['link_to'] : FALSE ;
$unlink_from = (isset($_REQUEST['unlink_from']) and $_REQUEST['unlink_from'] != '') ? $_REQUEST['unlink_from'] : FALSE ;
$flight_id = (isset($_REQUEST['flight_id'])) ? trim($_REQUEST['flight_id']) : 0 ;
if (!is_numeric($flight_id) and $flight_id != '') die("Invalid ID: $flight_id") ;
// TODO be ready to pre-load when asking for modification/cancellation
// and of course add 'modify' 'cancel' button

// Prepare the active tab
$contact_active = 'active in' ;
$payment_active = '' ;
$pax_active = '' ;
$pilot_active = '' ;
if ($pay_open) {
	$contact_active = '' ;
	$payment_active = 'active in' ;
}
if ($pax_open) {
	$contact_active = '' ;
	$pax_active = 'active in' ;
}
if ($pilot_open) {
	$contact_active = '' ;
	$pilot_active = 'active in' ;
}

function id2name($id) {
	global $mysqli_link, $table_person, $userId ;

	if ($id == '' or $id == 0) return 'page web' ;
	$result = mysqli_query($mysqli_link, "SELECT * FROM $table_person WHERE jom_id = $id") ;
	if (! $result) {
		journalise($userId, "W", "User $id not found:" . mysqli_error($mysqli_link)) ;
		return 'inconnu' ;
	}
	$row = mysqli_fetch_array($result) ;
	if (! $row) {
		journalise($userId, "W", "User $id does not exist") ;
		return 'inconnu' ;
	}
	return db2web($row['first_name'] . ' ' . $row['last_name']) ;
}

// Clean-up input data and canonicalize
if ($create or $modify) {
    //print("%%%Modify:Start");
	//if ($_REQUEST['discovery_flight'] == 'on') {
    if ($_REQUEST['selectedFlightType'] == 'decouverte') {
		$flight_type = 'D' ;
		$circuit = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['selectedCircuit'])) ;
		if (!is_numeric($circuit)) die("Invalid circuit: $circuit") ;
        //} elseif ($_REQUEST['initiation_flight'] == 'on') {
    } elseif ($_REQUEST['selectedFlightType'] == 'initiation') {
    		$flight_type = 'I' ;
    		$circuit = -1 ;
	} elseif ($_REQUEST['selectedFlightType'] == 'bon') {
		$flight_type = 'B' ;
		$circuit = -1 ;
	} elseif ($_REQUEST['selectedFlightType'] == 'dhf') {
		$flight_type = 'D' ;
		$circuit = -1 ;
	} else 
		die("Vous devez choisir le type de vol (initiation, découverte, bon ou DHF)") ;
    $gift=0;
    if($_REQUEST['gift']=="on") {
        $gift=1;
    }
	$pax_cnt = $_REQUEST['pax_cnt'] ;
	if (!is_numeric($pax_cnt)) die("Invalid pax_cnt: $pax_cnt") ;
	if ($_REQUEST['pax'] == 'yes')
		$role = 'P' ;
	elseif ($_REQUEST['student'] == 'yes')
		$role = 'S' ;
	else
		$role = 'C' ;
//	if (strtoupper($_REQUEST['gender']) == 'M')
//		$gender = 'M' ;
//	elseif (strtoupper($_REQUEST['gender']) == 'F')
//		$gender = 'F' ;
//	elseif (strtoupper($_REQUEST['gender']) == 'L')
//		$gender = 'L' ;
//	else
//		die("Gender $_REQUEST[gender] is not correct") ;
	$gender = 'M' ; // As the current form does not collect the gender...
	$lname = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['lname'])) ;
	if ($lname == '') die("Last name cannot be empty") ;
	$fname = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['fname'])) ;
	$email = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['email'])) ;
	if ($email == '') die("Email address cannot be empty") ;
	$phone = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['phone'])) ;
	if ($phone == '') die("Phone number cannot be empty") ;
	$street = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['street'])) ;
	$zip = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['zip'])) ;
	$city = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['city'])) ;
	$country = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['country'])) ;
	if ($create) {
		$weight = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['weight'])) ;
		if ($weight == '') $weight = 0 ;
		if (!is_numeric($weight) or $weight < 0) die("Invalid weight: $weight") ;
		$birthdate = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['birthdate'])) ;
	}
	$schedule = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['selectedSchedule'])) ;
	$date1 = (trim($_REQUEST['date1']) != '') ? date("'Y-m-d'", strtotime(mysqli_real_escape_string($mysqli_link, trim($_REQUEST['date1'])))) : 'NULL';
	$date2 = (trim($_REQUEST['date2']) != '') ? date("'Y-m-d'", strtotime(mysqli_real_escape_string($mysqli_link, trim($_REQUEST['date2'])))) : 'NULL';
	$comment = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['comment'])) ;
	$reference = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['reference'])) ;
	$odooreference = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['odoopaymentreference'])) ;
	$notes = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['notes'])) ;
}

if ($create) {
	mysqli_query($mysqli_link, "INSERT INTO $table_pax (p_lname, p_fname, p_email, p_tel, p_birthdate, p_weight, p_gender, p_street, p_zip, p_city, p_country)
		VALUES('" . web2db($lname) . "', '" . web2db($fname) . "', '$email', '$phone', '$birthdate', $weight, '$gender', '" . web2db($street) . "', '$zip', '" . web2db($city) . "', '" . web2db($country) . "')")
		or journalise($userId, "F", "Cannot add contact, system error: " . mysqli_error($mysqli_link)) ;
	$pax_id = mysqli_insert_id($mysqli_link) ; 
	// As f_reference is a unique index, let's simply use a random value
	mysqli_query($mysqli_link, "INSERT INTO $table_flight (f_reference, f_date_created, f_who_created, f_type, f_gift, f_pax_cnt, f_circuit, f_date_1, f_date_2, f_schedule, f_description, f_notes, f_pilot) 
		VALUES(RAND()*10000, SYSDATE(), $userId, '$flight_type', $gift, $pax_cnt, $circuit, '$schedule', $date1, $date2, '" . web2db($comment) . "', '" . web2db($notes) . "', NULL)")
		or journalise($userId, "F", "Cannot add flight, system error: " . mysqli_error($mysqli_link)) ;
	$flight_id = mysqli_insert_id($mysqli_link) ; 
	// Now that we have the flight_id, let's update the flight reference
	$prefix = ''  ; // Currently cannot change it to 'voucher' with a 'V-' prefix :-(
	$type = ($flight_type == 'D') ? 'IF-' : 'INIT-' ;
	$flight_reference = $prefix . $type . sprintf("%06d", $flight_id) ;
	mysqli_query($mysqli_link, "UPDATE $table_flight 
							SET f_reference='$flight_reference' 
							WHERE f_id=$flight_id")
				or journalise(0, 'E', "Cannot update reference in $table_flight to $flight_reference: " . mysqli_error($mysqli_link)) ;
	mysqli_query($mysqli_link, "INSERT INTO $table_pax_role(pr_flight, pr_pax, pr_role)
		VALUES('$flight_id', '$pax_id', 'C')") 
		or journalise($userId, "F", "Cannot add contact role C, system error: " . mysqli_error($mysqli_link)) ;
	if ($role != 'C')
		mysqli_query($mysqli_link, "INSERT INTO $table_pax_role(pr_flight, pr_pax, pr_role)
			VALUES('$flight_id', '$pax_id', '$role')") 
			or journalise($userId, "F", "Cannot add contact role $role, system error: " . mysqli_error($mysqli_link)) ;
	journalise($userId, 'I', "$flight_type flight ($flight_id) created for $lname $fname ($comment)") ;
}

if ($modify) {
    //print("%%%Modify:SELECT * from $table_pax_role WHERE pr_flight = $flight_id and pr_role='C'<br>");
	if ($flight_id <= 0) die("Invalid flight_id ($flight_id)") ;
	$result = mysqli_query($mysqli_link, "SELECT * from $table_pax_role WHERE pr_flight = $flight_id and pr_role='C'")
		or journalise($userId, "F", "Cannot retrieve contact for $flight_id: " . mysqli_error($mysqli_link)) ;
	$row_pax = mysqli_fetch_array($result) or die("Contact not found") ;
	mysqli_free_result($result) ;
	$pax_id = $row_pax['pr_pax'] ;
    /*
    print("MODIFY: UPDATE $table_pax
			SET p_lname='" . web2db($lname) . "', p_fname='" . web2db($fname) . "', p_email='$email', p_tel='$phone', p_gender='$gender', p_street='" . web2db($street) . "', p_zip='$zip', p_city='" . web2db($city) . "', p_country='" . web2db($country) . "'
			WHERE p_id = $pax_id");  
    */  
	mysqli_query($mysqli_link, "UPDATE $table_pax
			SET p_lname='" . web2db($lname) . "', p_fname='" . web2db($fname) . "', p_email='$email', p_tel='$phone', p_gender='$gender', p_street='" . web2db($street) . "', p_zip='$zip', p_city='" . web2db($city) . "', p_country='" . web2db($country) . "'
			WHERE p_id = $pax_id")
		or journalise($userId, "F", "Cannot modify contact, system error: " . mysqli_error($mysqli_link)) ;
  
	$sql = "UPDATE $table_flight 
		SET f_type='$flight_type', f_gift=$gift, f_pax_cnt=$pax_cnt, f_circuit = $circuit, f_date_1 = $date1, f_date_2 = $date2, f_schedule = '$schedule', f_description='" . web2db($comment) . "', f_reference='" . web2db($reference) . "', f_notes='" . web2db($notes) . "'
		WHERE f_id = $flight_id" ;

    //print("%%%Modify:$sql<br>");

	if (!mysqli_query($mysqli_link, $sql))
		if (mysqli_errno($mysqli_link) == 1062)
			journalise($userId, "F", "***Impossible de modifier le vol, la reference est deja utilisee ou deux vols crees en meme temps***" . mysqli_error($mysqli_link)) ;
		else
			journalise($userId, "F", "Cannot modify flight, system error #" . mysqli_errno($mysqli_link) . ": " . mysqli_error($mysqli_link)) ;
	journalise($userId, "W", "Flight $flight_id modified") ;
}

if ($delete) {
	if ($flight_id <= 0) die("Invalid flight_id ($flight_id)") ;
	$result = mysqli_query($mysqli_link, "UPDATE $table_flight SET f_date_cancelled = SYSDATE(), f_who_cancelled = $userId, f_booking=NULL, f_pilot=NULL 
		WHERE f_id = $flight_id")
		or journalise($userId, "F", "Cannot cancel flight $flight_id: " . mysqli_error($mysqli_link)) ;
	journalise($userId, "W", "Flight $flight_id cancelled") ;
}

if ($assign_pilot) {
	if ($flight_id <= 0) die("Invalid flight_id ($flight_id)") ;
	$assigned_pilot = (isset($_REQUEST['assignedPilot'])) ? intval($_REQUEST['assignedPilot']) : '' ;
	if (! $assigned_pilot or ! is_numeric($assigned_pilot)) die("Invalid pilot ($assigned_pilot)") ;
	if ($assigned_pilot <= 0) $assigned_pilot = 'NULL' ;
	mysqli_query($mysqli_link, "UPDATE $table_flights
		SET f_pilot=$assigned_pilot, f_date_assigned = SYSDATE(), f_who_assigned = $userId
		WHERE f_id = $flight_id")
		or journalise($userId, "F", "Cannot assign pilot: " . mysqli_error($mysqli_link)) ;
	if (mysqli_affected_rows($mysqli_link) == 0)
		journalise($userId, "F", "No change made") ;
	journalise($userId, "W", "Flight $flight_id has now a pilot $assigned_pilot") ;
}

if ($add_pax) {
	if ($flight_id <= 0) die("Invalid flight_id ($flight_id)") ;
	$lname = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['lname'])) ;
	if ($lname == '') journalise($userId, "F", "Last name cannot be empty") ;
	$fname = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['fname'])) ;
	if ($fname == '') journalise($userId, "F", "First name cannot be empty") ;
	$weight = intval(trim($_REQUEST['weight'])) ;
	if ($weight == '' or $weight < 0) {
		journalise($userId, "W", "Weight ($weight) is invalid") ;
		$weight = 80 ;
	}
	if (! in_array($_REQUEST['age'], array('C', 'T', 'A'))) {
		journalise($userId, "W", "Pax age is invalid: $_REQUEST[age]") ;
		$_REQUEST['age'] = 'A' ;
	}
	// TODO also allow non P role (could be S)
	// TODO check whether we are already max-ed out about passagers
	mysqli_query($mysqli_link, "INSERT INTO $table_pax(p_lname, p_fname, p_weight, p_age)
		VALUES ('" . mysqli_real_escape_string($mysqli_link, web2db($lname)) . "', '" . mysqli_real_escape_string($mysqli_link, web2db($fname)) . "', $weight, '$_REQUEST[age]')")
		or journalise($userId, "F", "Cannot add passenger: " . mysqli_error($mysqli_error)) ;
	$pax_id = mysqli_insert_id($mysqli_link) ; 
	mysqli_query($mysqli_link, "INSERT INTO $table_pax_role(pr_flight, pr_pax, pr_role)
		VALUES('$flight_id', '$pax_id', 'P')") 
		or journalise($userId, "F", "Cannot add passenger role , system error: " . mysqli_error($mysqli_link)) ;
	journalise($userId, "I", "Passenger $fname $lname added to flight $flight_id") ;
}

if ($modify_pax) {
	if ($flight_id <= 0) die("Invalid flight_id ($flight_id)") ;
	$lname = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['lname'])) ;
	if ($lname == '') journalise($userId, "F", "Last name cannot be empty") ;
	$fname = mysqli_real_escape_string($mysqli_link, trim($_REQUEST['fname'])) ;
	if ($fname == '') journalise($userId, "F", "First name cannot be empty") ;
	$weight = intval(trim($_REQUEST['weight'])) ;
	if ($weight == '' or $weight < 0) {
		journalise($userId, "W", "Weight ($weight) is invalid") ;
		$weight = 0 ;
	}
	$pax_id = intval(trim($_REQUEST['pax_id'])) ;
	if ($pax_id == '' or $pax_id <= 0) journalise($userId, "F", "Pax_id is invalid") ;
	if (! in_array($_REQUEST['age'], array('C', 'T', 'A'))) {
		journalise($userId, "E", "Pax age is invalid: $_REQUEST[age]") ;
		$_REQUEST['age'] = 'A' ;
	}
	// TODO check whether we are already max-ed out about passagers
	// TODO also allow non P role (could be S)
	mysqli_query($mysqli_link, "UPDATE $table_pax SET
		p_lname = '" . mysqli_real_escape_string($mysqli_link, web2db($lname)) . "', p_fname = '" . mysqli_real_escape_string($mysqli_link, web2db($fname)) . "', p_weight = $weight, p_age = '$_REQUEST[age]'
		WHERE p_id = $pax_id")
		or journalise($userId, "F", "Cannot modify passenger: " . mysqli_error($mysqli_link)) ;
}

if ($delete_pax) {
	if ($flight_id <= 0) die("Invalid flight_id ($flight_id)") ;
	$pax_id = intval(trim($_REQUEST['pax_id'])) ;
	if ($pax_id == '' or $pax_id <= 0) journalise($userId, "F", "Pax_id is invalid") ;
	mysqli_query($mysqli_link, "DELETE FROM $table_pax_role WHERE pr_flight=$flight_id AND pr_role in ('P', 'S') AND pr_pax = $pax_id")
		or journalise($userId, "F", "Cannot remove role for passenger $pax_id: " . mysqli_error($mysqli_link)) ;
	// Let's find it back and check how many roles he has, if only P or S then we can safely remove it
	// We should never remove the role Contact
	$result_delete = mysqli_query($mysqli_link, "SELECT * FROM $table_pax_role 
		WHERE pr_flight=$flight_id AND pr_pax = $pax_id")
		or journalise($userId, "F", "Cannot check remaining role for passenger $pax_id: " . mysqli_error($mysqli_link)) ;
	if (mysqli_num_rows($result_delete) == 0) // We can safely remove passenger details
		mysqli_query($mysqli_link, "DELETE FROM $table_pax WHERE p_id = $pax_id")
			or journalise($userId, "F", "Cannot delete passenger detail: " . mysqli_error($mysqli_link)) ;
	mysqli_free_result($result_delete) ;
	journalise($userId, "I", "Passenger $pax_id deleted from flight $flight_id") ;
}

if ($link_to) {
	if (! is_numeric($link_to))
		die("Numéro de réservation invalide $link_to") ;
	// Unlink any previous link... (mainly set booking type back to pilot on any previous booking)
	mysqli_query($mysqli_link, "UPDATE $table_bookings, $table_flight 
		SET r_type = " . BOOKING_PILOT . "
		WHERE r_id = f_booking AND f_id=$flight_id") 
		or journalise($userId, "E", "Cannot unmark previous booking as customer flight: " . mysqli_error($mysqli_link)) ;
	// Do the actual job
	mysqli_query($mysqli_link, "UPDATE $table_flight, $table_bookings
			SET f_booking=$link_to, f_date_linked=SYSDATE(), f_who_linked = $userId, f_pilot = r_pilot, r_type = " . BOOKING_CUSTOMER . "
			WHERE f_id=$flight_id AND r_id = $link_to")
		or journalise($userId, "F", "Impossible de lier le vol: " . mysqli_error($mysqli_link)) ;
	// Check whether the flight log  entry already exist and mark the flight has flown
	$result_link = mysqli_query($mysqli_link, "SELECT * FROM $table_logbook WHERE l_booking = $link_to") 
		or journalise($userId, "F", "Cannot retrieve log entry for booking $link_to: " . mysqli_error($mysqli_link)) ;
	$row_link = mysqli_fetch_array($result_link) ;
	if ($row_link) {
		$date_flown = $row_link['l_start'] ;
		mysqli_query($mysqli_link, "UPDATE $table_flight SET f_date_flown = '$date_flown' WHERE f_id = $flight_id")
			or journalise($userId, "F", "Cannot update flight with $date_flown as the date flown: " . mysqli_error($mysqli_link)) ;
		journalise($userId, "I", "Flight $flight_id is linked to booking $link_to flown on $date_flown") ;
	} else
		journalise($userId, "I", "Flight $flight_id is linked to future booking $link_to") ;
}


if ($unlink_from) {
	if (! is_numeric($unlink_from))
		die("Numéro de réservation invalide $unlink_from") ;
	mysqli_query($mysqli_link, "UPDATE $table_flight
			SET f_booking=NULL, f_date_linked=NULL, f_who_linked = NULL, f_date_flown = NULL
			WHERE f_id=$flight_id")
		or journalise($userId, "F", "Impossible de délier le vol $flight_id: " . mysqli_error($mysqli_link)) ;
	mysqli_query($mysqli_link, "UPDATE $table_bookings
			SET r_type = " . BOOKING_PILOT . "
			WHERE r_id=$unlink_from")
		or journalise($userId, "F", "Impossible de délier le réservation $unlink_from: " . mysqli_error($mysqli_link)) ;
	journalise($userId, "I", "Flight $flight_id is unlinked from booking $unlink_from") ;
}

if ($pay) {
	if ($flight_id <= 0) die("Invalid flight_id ($flight_id)") ;
	if ($_REQUEST['paid'] == 'on') {
		if ($_REQUEST['paymentDate'] == '')
			$date = "SYSDATE()";
		else
			$date = "'" . mysqli_real_escape_string($mysqli_link, $_REQUEST['paymentDate']) . "'";
	    $reference = "'" . mysqli_real_escape_string($mysqli_link, web2db($_REQUEST['paymentReference'])) . "'";
	    $odooreference = "'" . mysqli_real_escape_string($mysqli_link, web2db($_REQUEST['odooPaymentReference'])) . "'";
		$amount = "'" . mysqli_real_escape_string($mysqli_link, web2db($_REQUEST['paymentAmount'])) . "'";
	} else {
		$date = 'NULL' ;
		$reference = 'NULL' ;
		$odooreference = 'NULL' ;
		$amount = 'NULL' ;
	}
	mysqli_query($mysqli_link, "UPDATE $table_flight
		SET f_date_paid=$date, f_reference_payment=$reference, f_who_paid=$userId, f_amount_payment=$amount
		WHERE f_id=$flight_id")
		or journalise($userId, "F", "Impossible de mettre à jour le paiement: " . mysqli_error($mysqli_link)) ;
	journalise($userId, "I", "Flight $flight_id payment information updated") ;
}

if ($addPayment) {
	if ($flight_id <= 0) die("Invalid flight_id ($flight_id)") ;
	if ($_REQUEST['paymentDate'] == '')
		$date = "SYSDATE()";
	else
		$date = "'" . mysqli_real_escape_string($mysqli_link, $_REQUEST['paymentDate']) . "'";
	$reference = "'" . mysqli_real_escape_string($mysqli_link, web2db($_REQUEST['paymentReference'])) . "'";
	$odooreference = "'" . mysqli_real_escape_string($mysqli_link, web2db($_REQUEST['odooPaymentReference'])) . "'";
	$amount = "'" . mysqli_real_escape_string($mysqli_link, web2db(str_replace(',', '.', $_REQUEST['paymentAmount']))) . "'";
    //print("odooreference=$odooreference<br>");
    if($odooreference!="''") {
        //print("1odooreference=$odooreference<br>");
	    mysqli_query($mysqli_link, "INSERT INTO $table_flights_ledger(fl_flight, fl_date, fl_who, fl_amount, fl_reference, fl_odoo_payment_id)
		    VALUES($flight_id, $date, $userId, $amount, $reference, $odooreference)")
		    or journalise($userId, "F", "Impossible d'ajouter un paiement: " . mysqli_error($mysqli_link)) ;
    }
    else {
        //print("2odooreference=$odooreference<br>");
	    mysqli_query($mysqli_link, "INSERT INTO $table_flights_ledger(fl_flight, fl_date, fl_who, fl_amount, fl_reference)
		    VALUES($flight_id, $date, $userId, $amount, $reference)")
		    or journalise($userId, "F", "Impossible d'ajouter un paiement: " . mysqli_error($mysqli_link)) ;
    }
	journalise($userId, "I", "Flight $flight_id payment information updated $amount") ;
}

if ($deletePayment) {
	if ($flight_id <= 0) journalise($userId, "F", "Invalid flight_id ($flight_id)") ;
	$ledge_id = $_REQUEST['deletePayment'] ;
	if (!is_numeric($ledge_id)) journalise($userId, "F", "Invalid ledge id ($ledge_id)") ;
	mysqli_query($mysqli_link, "DELETE FROM $table_flights_ledger
		WHERE fl_id = $ledge_id AND fl_flight = $flight_id")
		or journalise($userId, "F", "Impossible d'effacer un paiement: " . mysqli_error($mysqli_link)) ;
	journalise($userId, "I", "Flight $flight_id payment information deleted $amount") ;
}

if (isset($flight_id) and $flight_id != 0) {
	$result = mysqli_query($mysqli_link, "SELECT * 
			FROM $table_flight JOIN $table_pax_role ON pr_flight = f_id 
				LEFT JOIN $table_pax ON pr_pax = p_id 
				LEFT JOIN $table_person ON f_who_created = jom_id
				LEFT JOIN $table_bookings ON f_booking = r_id
			WHERE f_id = $flight_id and pr_role='C'")
		or journalise($userId, "F", "Cannot retrieve flight $flight_id: " . mysqli_error($mysqli_link)) ;
	$row_flight = mysqli_fetch_array($result) ;
	if (!$row_flight) journalise($userId, "F", "Vol #$flight_id inconnu!") ;
	if ($row_flight['f_reference'] != '')
		$flight_number = strtoupper($row_flight['f_reference']) ;
	else {
		$prefix = ($row_flight['f_gift'] != 0) ? 'V-' : '' ;
		$type = ($row_flight['f_type'] == 'D') ? 'IF-' : 'INIT-' ;
		$flight_number = $prefix . $type . sprintf("%03d", $flight_id) ;
		// As flight manager wants to use another manual process
		$flight_number = sprintf("#%03d", $flight_id) ;
	}
	$title = "Modification d'une réservation de vol $flight_number" ;
} else
	$title = "Création d'une réservation de vol" ;
?>

<div class="page-header">
<h3><?=$title?></h3>
</div><!-- page header -->

<ul class="nav nav-tabs">
  <li class="<?=$contact_active?>"><a data-toggle="tab" href="#menuContact">Résumé</a></li>
  <li class="<?=$pax_active?>"><a data-toggle="tab" href="#menuPassenger">Passagers</a></li>
  <li class="<?=$payment_active?>"><a data-toggle="tab" href="#menuPayment">Paiements</a></li>
  <li class="<?=$pilot_active?>"><a data-toggle="tab" href="#menuPilot">Pilote</a></li>
  <li><a data-toggle="tab" href="#menuPlane">Réservation</a></li>
  <li><a data-toggle="tab" href="#menuAudit">Historique</a></li>
</ul>

<div class="tab-content">

<div id="menuContact" class="tab-pane fade <?=$contact_active?>">

<form action="<?=$_SERVER['PHP_SELF']?>" method="post" autocomplete="off">

<div class="row">
	<div class="form-group col-xs-3 col-sm-2">
		<label class="radio control-label">Type de vol:</label>
		<div class="radio">
            <select class="form-control" id="idFlightTypeSelect" name="selectedFlightType" onchange="flightTypeChanged(<?=$flight_id?> , <?=$row_flight['f_gift']?>);">
                <option value="decouverte">Découverte</option>
                <option value="initiation">Initiation</option>
                <option value="bon">Bon Valeur</option>
                <option value="dhf">DHF</option>
           </select>
        </div>
		<div class="radio">
			<label><input type="checkbox" name="gift" id="idgift" onchange="giftChanged(<?=$flight_id?>);">&nbsp;Bon Valeur</label>
		</div>
<!--
		<div class="radio">
			<label><input type="radio" name="discovery_flight">découverte</label>
		</div>
		<div class="radio">
			<label><input type="radio" name="initiation_flight">initiation</label>
		</div>
        -->
	</div> <!-- form-group flight type -->
	<div class="form-group col-xs-6 col-sm-2">
		<label class="control-label" for="circuitSelect">Circuit:</label>
			<select class="form-control" id="circuitSelect" name="selectedCircuit">
<?php
	// Get the circuit names
	$circuits = json_decode(file_get_contents("../voldecouverte/script/circuits.js"), true);
	$circuit_name = (isset($circuits[$row_flight['f_circuit']])) ? $circuits[$row_flight['f_circuit']] : "Circuit #$row_flight[f_circuit] inconnu" ;
	foreach ($circuits as $circuit_id => $circuit_name) {
		if ($circuit_name == '') continue; // Some circuits have an empty name
		$selected = ($row_flight['f_circuit'] == $circuit_id) ? ' selected' : '' ;
		print("<option value=\"$circuit_id\"$selected>$circuit_name</option>\n") ;
	}
?>
			</select>
	</div> <!-- form-group circuit -->
	<div class="form-group col-xs-4 col-sm-2"">
		<label class="control-label" for="date1">Dates:</label>
		<input type="date" class="form-control" name="date1" value="<?=$row_flight['f_date_1']?>">
		<br/>
		<input type="date" class="form-control" name="date2" value="<?=$row_flight['f_date_2']?>">
	</div> <!-- form-group schedule -->
	<div class="form-group col-xs-3 col-sm-2"">
		<label class="control-label" for="scheduleSelect">Plage horaire:</label>
			<select class="form-control" id="scheduleSelect" name="selectedSchedule">
<?php
	// Get the possible schedules
	$schedules = json_decode(file_get_contents("../voldecouverte/script/plageshoraire.js"), true);
	foreach ($schedules as $schedule) {
		$selected = ($row_flight['f_schedule'] == $schedule) ? ' selected' : '' ;
		print("<option value=\"$schedule\"$selected>$schedule</option>\n") ;
	}
?>
			</select>
	</div> <!-- form-group schedule -->
	<div class="form-group col-xs-6 col-sm-3">
		<label for="lname">Nombre de passagers (au total en dehors du pilote/FI):</label>
		<input type="number" min="1" max="3" class="form-control" name="pax_cnt" value="1">
	</div> <!-- form-group pax count -->

</div><!-- row -->

<div class="row">
	<div class="form-group col-xs-12 col-sm-6">
		<label for="reference">Référence (V-INIT-239999, IF-239999, ...) :</label>
		<input type="text" class="form-control" name="reference" id="idreference">
    </div><!-- form-group -->
</div><!-- row -->

<div class="row">
<h4>Contact principal</h4>
</div><!-- row -->

<div class="row">
	<!-- div class="form-group col-xs-12 col-sm-1">
		<label for="gender">Salutations:</label>
		<select class="form-control" name="gender">
			<option value="F">Mme</option>
			<option value="L">Melle</option>
			<option value="M">M.</option>
		</select>
	</div--><!-- form-group -->
	<div class="form-group col-xs-12 col-sm-6">
		<label for="lname">Nom:</label>
		<input type="text" class="form-control" name="lname">
	</div><!-- form-group -->
	<div class="form-group col-xs-12 col-sm-4">
		<label for="fname">Prénom:</label>
		<input type="text" class="form-control" name="fname">
	</div><!-- form-group -->
</div> <!-- row -->
<div class="row">
	<div class="form-group col-xs-12 col-sm-4">
		<label for="email">Adresse email:</label>
		<input type="email" class="form-control" name="email">
	</div><!-- form-group -->
	<div class="form-group col-xs-12 col-sm-4">
		<label for="phone">Téléphone:</label>
		<input type="tel" class="form-control" name="phone">
	</div><!-- form-group -->
</div> <!-- row -->
<div class="row">
	<div class="form-group col-xs-12 col-sm-4">
		<label for="phone">Rue:</label>
		<input type="tel" class="form-control" name="street">
	</div><!-- form-group -->
	<div class="form-group col-xs-4 col-sm-2">
		<label for="phone">CP:</label>
		<input type="tel" class="form-control" name="zip">
	</div><!-- form-group -->
	<div class="form-group col-xs-12 col-sm-4">
		<label for="phone">Ville:</label>
		<input type="tel" class="form-control" name="city">
	</div><!-- form-group -->
	<div class="form-group col-xs-12 col-sm-4">
		<label for="phone">Pays:</label>
		<input type="tel" class="form-control" name="country">
	</div><!-- form-group -->
</div> <!-- row -->

<?php // Don't ask age/weight on the contact form when the flight exists
if ($flight_id == '') {
?>
<div class="row">
	<div class="form-group col-xs-6 col-sm-2">
		<label for="weight">Poids:</label>
		<input type="number" min="0" max="150" class="form-control" name="weight" value="80">
	</div> <!-- form-group -->
	<div class="form-group col-xs-6 col-sm-3">
		<label for="age">Âge:</label>
			<select name="age" id="ageSelect">
				<option value="C" >< 12 ans</option>
				<option value="T">< 18 ans</option>
				<option value="A">>= 18 ans</option>
		</select>
	</div> <!-- form-group -->
	<div class="form-group col-xs-6 col-sm-3">
		<label for="role">Ce contact est:</label>
		<div class="checkbox">
			<label><input type="checkbox" name="pax" value="yes">passager (vol découverte)</label>
		</div><!-- checkbox-->
		<div class="checkbox">
			<label><input type="checkbox" name="student" value="yes">élève (vol initiation)</label>
		</div><!-- checkbox-->
	</div> <!-- form-group -->
</div><!-- row -->
<?php
}
?>

<div class="row">
	<div class="form-group col-xs-12">
		<label for="comment">Remarque client:</label>
		<textarea class="form-control" rows="5" name="comment"></textarea>
	</div><!-- form-group -->
</div><!-- row -->

<div class="row">
	<div class="form-group col-xs-12">
		<label for="notes">Notes club:</label>
		<textarea class="form-control" rows="5" name="notes"></textarea>
	</div><!-- form-group -->
</div><!-- row -->

<div class="row">
	<div class="btn-toolbar">
<?php
	if ($flight_id== 0)
		print('<button type="submit" class="btn btn-primary" name="create" value="create">Créer la demande</button>') ;
	if (isset($flight_id) and $flight_id != 0) {
		print('<input type="hidden" name="flight_id" value="' . $flight_id . '">') ;
		print('<button type="submit" class="btn btn-primary" name="modify" value="modify">Modifier la demande</button>') ;
		if ($row_flight['f_booking'] == '')
			print('<button type="submit" class="btn btn-danger" name="delete" value="delete">Annuler la demande</button>') ;
		$result = mysqli_query($mysqli_link, "SELECT * 
				FROM $table_flight JOIN $table_pax_role ON pr_flight = f_id LEFT JOIN $table_pax ON pr_pax = p_id
				WHERE f_id = $flight_id and pr_role = 'C'")
			or journalise($userId, "F", "Cannot retrieve contact role $flight_id: " . mysqli_error($mysqli_link)) ;
		$row_contact = mysqli_fetch_array($result) ;
		mysqli_free_result($result) ;
?>
	</div>
</div>
</form>
<button class="btn btn-info" onclick="location.href='flight_pdf.php?flight_id=<?=$flight_id?>';">Imprimer le dossier</button>

</div><!-- menu contact -->


<div id="menuPassenger" class="tab-pane fade <?=$pax_active?>">
<div class="page-header">
<h4>Liste des passagers</h4>
</div><!-- page-header -->

<!--div class="row">
<div class="col-sm-12"-->
<table class="table-responsive table-bordered table-striped col-xs-12 col-md-6">
<thead>
<tr><th>Passager n°</th><th>Rôle</th><th>Nom de famille</th><th>Prénom</th><th>Âge</th><th>Poids</th><th>Action(s)</th></tr>
</thead>
<tbody>
<?php
// Get all 'real' passengers, the ones being in the plane (this could also be the contact of course)
// Should use the pax_count data to display just the right amount of rows
$result_pax = mysqli_query($mysqli_link, "SELECT * FROM $table_pax_role JOIN $table_pax ON pr_pax = p_id
			WHERE pr_flight = $flight_id AND pr_role <> 'C'") 
			or journalise($userId, "F", "Cannot retrieve passengers list: " . mysqli_error($mysqli_link)) ;
$known_pax_count = 0 ;
while ($row_pax = mysqli_fetch_array($result_pax)) {
	$delete = " <a href=\"flight_create.php?pax_id=$row_pax[p_id]&delete_pax=true&pax_role=$row_pax[pr_role]&flight_id=$flight_id\"><span class=\"glyphicon glyphicon-trash text-danger\"></span></a>" ;
	$known_pax_count ++ ;
	switch ($row_pax['pr_role']) {
		case 'C': $role = 'Contact' ; $delete = '' ; break ; // Cannot delete the contact
		case 'S': $role = 'Elève' ; break ;
		case 'P': $role = 'Simple passager' ; break ;
	}
	print("<form id=\"form_$row_pax[p_id]\" action=\"$_SERVER[PHP_SELF]\">
		<input type=\"hidden\" name=\"pax_id\" value=\"$row_pax[p_id]\">
		<input type=\"hidden\" name=\"flight_id\" value=\"$flight_id\">
		<input type=\"hidden\" name=\"modify_pax\" value=\"modify_pax\">
		<input type=\"hidden\" name=\"pax_open\" value=\"true\">
		<tr><td>$known_pax_count</td><td>$role</td>
		<td><input type=\"text\" name=\"lname\" value=\"" . db2web($row_pax['p_lname']) . "\"></td>
		<td><input type=\"text\" name=\"fname\" value=\"" . db2web($row_pax['p_fname']) . "\"></td>
		<td><select name=\"age\">
			<option value=\"C\"" . (($row_pax['p_age'] == 'C') ? ' selected' : '') ." >< 12 ans</option>
			<option value=\"T\"" . (($row_pax['p_age'] == 'T') ? ' selected' : '') ." >< 18 ans</option>
			<option value=\"A\"" . (($row_pax['p_age'] == 'A') ? ' selected' : '') ." >>= 18 ans</option>
		</select>
		</td>
		<td><input type=\"text\" name=\"weight\" size=\"3\" value=\"" . db2web($row_pax['p_weight']) . "\"> kg</td>
		<td>
		<span class=\"glyphicon glyphicon-floppy-disk text-primary\" onclick=\"submitForm('form_$row_pax[p_id]');\"></span>$delete</td></tr></form>\n") ;
}

for ($i = $known_pax_count+1; $i <= $row_flight['f_pax_cnt']; $i++) {
	print("<form id=\"form_add_$i\" action=\"$_SERVER[PHP_SELF]\">
			<input type=\"hidden\" name=\"flight_id\" value=\"$flight_id\">
			<input type=\"hidden\" name=\"add_pax\" value=\"add_pax\">
			<input type=\"hidden\" name=\"open_pax\" value=\"true\">
			<tr><td>$i</td><td>$role</td>
			<td><input type=\"text\" name=\"lname\"></td>
			<td><input type=\"text\" name=\"fname\"></td>
			<td><select name=\"age\">
				<option value=\"C\">< 12 ans</option>
				<option value=\"T\">< 18 ans</option>
				<option value=\"A\" selected>>= 18 ans</option>
			</select>
			<td><input type=\"text\" name=\"weight\" size=\"3\"> kg</td>
			<td><span class=\"glyphicon glyphicon-floppy-disk text-primary\" onclick=\"submitForm('form_add_$i');\"></span></td></tr></form>\n") ;
	} // for
} // (isset($flight_id) and $flight_id != 0)
?>
</tbody>
</table>
<!--/div><!-- col -->
<!--/div><!-- row -->

</div> <!-- menu passenger -->

<div id="menuPayment" class="tab-pane fade <?=$payment_active?>">

<div class="row">
<h3>Historique des paiements</h3>
<table class="table table-striped table-responsive table-hover" id="ledgerTable">
	<thead>
		<tr><th></th><th>Date</th><th>Montant</th><th>Référence</th><th>Odoo Réf.</th><th>Par</th></tr>
	</thead>
	<tbody>
<?php
$result = mysqli_query($mysqli_link, "SELECT * FROM $table_flights_ledger JOIN $table_person ON fl_who=jom_id
								WHERE fl_flight = $flight_id ORDER BY fl_date ASC")
		or journalise($userId, "E", "Cannot read ledger: " . mysqli_error($mysqli_link)) ;
$total_paid = 0 ;
while ($row = mysqli_fetch_array($result)) {
	print("<tr>
		<td><button onclick=\"location.href='" . $_SERVER['PHP_SELF'] . "?deletePayment=$row[fl_id]&flight_id=$flight_id&pay_open=Y'\" class=\"btn btn-danger\" title=\"Enlever ce paiement\"><span class=\"glyphicon glyphicon-remove\"></span></button></td>
		<td>$row[fl_date]</td><td>$row[fl_amount]</td><td>" . db2web($row['fl_reference']) . "</td>
        <td>" . db2web($row['fl_odoo_payment_id']) . "</td>
		<td>" . db2web("$row[first_name] $row[last_name]") . "</td></tr>\n") ;
	$total_paid += $row['fl_amount'] ;
}
// add line to add payment
print("<form action=\"" . $_SERVER['PHP_SELF'] . "?\">
	<input type=\"hidden\" name=\"flight_id\" value=\"$flight_id\">
	<input type=\"hidden\" name=\"pay_open\" value=\"Y\">
	<tr>
    <td><button type=\"submit\" class=\"btn btn-success\" title=\"Ajouter un paiement\" name=\"addPayment\" value=\"Y\"><span class=\"glyphicon glyphicon-plus\"></span></button></td>
	<td><input type=\"date\" name=\"paymentDate\" value=\"" . date('Y-m-d') . "\"></td>
	<td><input type=\"text\" name=\"paymentAmount\"></td>
	<td><input type=\"text\" name=\"paymentReference\"></td>
	<td><input type=\"text\" name=\"odooPaymentReference\"></td>
	<td></td>
	</tr>
	</form>\n") ;
// Show the total
print("<tr class=\"bg-info\"><td></td><td></td><td><b>$total_paid</b></td><td><b>TOTAL</b></td><td></td><td></td></tr>\n") ;
?>
	</tbody>
</table>
</div><!-- row -->

</div> <!-- menu Payment -->

<div id="menuPilot" class="tab-pane fade <?=$pilot_active?>">

<div class="row text-info">
<?php
if ($row_flight['f_pilot'])
	print("<p>Le pilote a déjà été choisi pour ce vol mais peut être changé (y compris le choix de 'pas de pilote').</p>") ;
else
	print("<p>Le pilote est à choisir parmi les pilotes qualifiés pour ce type de vol.</p>") ;
?>
</div><!-- row -->

<form action="<?=$_SERVER['PHP_SELF']?>" method="GET" class="form-horizontal" >
<input type="hidden" name="flight_id" value="<?=$flight_id?>">
<input type="hidden" name="pilot_open" value="true">
	<div class="form-group">
		<label class="control-label col-xs-2 col-md-1" for="pilotSelect">Pilote:</label>
		<div class="col-xs-6 col-md-3 col-lg-2">
			<select class="form-control" id="pilotSelect" name="assignedPilot">
<?php
	if ($row_flight['f_type'] == 'D') 
		$condition = 'p_discovery <> 0' ;
	elseif ($row_flight['f_type'] == 'I') 
		$condition = 'p_initiation <> 0' ;
	if (isset($condition)) {
		$selected = ($row_flight['f_pilot'] == '') ? ' selected' : '' ;
		print("<option value=\"-1\"$selected>Pas de pilote</option>\n") ;
		$result_pilots = mysqli_query($mysqli_link, "SELECT *
			FROM $table_flights_pilots JOIN $table_person ON p_id=jom_id 
			WHERE $condition ORDER BY last_name ASC, first_name ASC")
			or journalise($userId, "F", "Cannot retrieve pilots: " . mysqli_error($mysqli_link)) ;
		while ($row_pilot = mysqli_fetch_array($result_pilots)) {
			$selected = ($row_pilot['p_id'] == $row_flight['f_pilot']) ? ' selected' : '' ;
			print("<option value=\"$row_pilot[p_id]\"$selected>" . db2web("$row_pilot[first_name] $row_pilot[last_name]") . "</option>\n") ;
		}
		mysqli_free_result($result_pilots) ;
	}
?>
			</select>
		</div><!-- col -->
	</div><!-- form-group-->
	<div class="form-group">
		<div class="col-xs-3 col-md-2">
			<input type="submit" class="btn btn-primary" name="assign_pilot" value="Sélectionner ce pilote"/>
   		</div><!-- col -->
	</div><!-- formgroup-->
</form>

</div><!-- menuPilot -->

<div id="menuPlane" class="tab-pane fade">

<div class="row">
<p>Voici les réservations existantes pour le ou les jours choisis . Pour rappel, la plage horaire souhaitée est <mark><?=$row_flight['f_schedule']?></mark>.</p>
</div><!-- row -->
<?php
function show_reservation($date, $header) {
	global $mysqli_link, $table_bookings, $table_person, $table_planes, $table_flight, $row_flight, $userId ;
	print("<div class=\"row\">
		<h4 class=\"text-center\">$header: $date</h4>
		</div><!-- row -->\n" ) ;
	print("<div class=\"row\">
		<table class=\"col-sm-12 table table-responsive table-striped\">
		<tr><th>De</th><th>A</th><th>Avion</th><th>Pilote</th><th>Commentaire</th><th>Action</th></tr>\n") ;
	$sql_date = date('Y-m-d') ;
	$result = mysqli_query($mysqli_link, "SELECT *, i.last_name as ilast_name, i.first_name as ifirst_name, i.cell_phone as icell_phone, i.jom_id as iid,
		pi.last_name as plast_name, pi.first_name as pfirst_name, pi.cell_phone as pcell_phone, pi.jom_id as pid, f.f_reference as reference
		FROM $table_bookings 
		JOIN $table_person pi ON pi.jom_id = r_pilot
		LEFT JOIN $table_person i ON i.jom_id = r_instructor		
		JOIN $table_planes p ON r_plane = p.id	
		LEFT JOIN $table_flight f ON f_booking = r_id	
		WHERE r_cancel_date IS NULL AND ressource = 0 AND actif = 1 AND DATE(r_start) <= '$date' AND '$date' <= DATE(r_stop)
		ORDER BY r_start ASC")
		or journalise($userId, "F", "Cannot retrieve bookings(: " . mysqli_error($mysqli_link)) ;
	while ($row = mysqli_fetch_array($result)) {
		$ptelephone = ($row['pcell_phone'] and ($userId > 0)) ? " <a href=\"tel:$row[pcell_phone]\"><span class=\"glyphicon glyphicon-earphone\"></span></a>" : '' ;
		$itelephone = ($row['icell_phone'] and ($userId > 0)) ? " <a href=\"tel:$row[icell_phone]\"><span class=\"glyphicon glyphicon-earphone\"></span></a>" : '' ;
		$instructor = ($row['ilast_name'] and $row['pid'] != $row['iid']) ? ' <i><span data-toggle="tooltip" data-placement="right" title="' .
			db2web($row['ifirst_name']) . ' ' . db2web($row['ilast_name']) . '">' .
			substr($row['ifirst_name'], 0, 1) . "." . substr($row['ilast_name'], 0, 1) . '. </span></i>' . $itelephone : '' ; 
		$class = ($row['r_type'] == BOOKING_MAINTENANCE) ? ' class="danger"' : '' ;
		if ($row['r_id'] == $row_flight['f_booking'])
			$class = ' class="success"' ;
		// Only display time for the current date
		if (strpos($row['r_start'], $date) === 0) 
			$row['r_start'] = substr($row['r_start'], 11) ;
		if (strpos($row['r_stop'], $date) === 0) 
			$row['r_stop'] = substr($row['r_stop'], 11) ;
		print("<tr$class><td>$row[r_start]</td><td>$row[r_stop]</td><td>$row[r_plane]</td><td><span class=\"hidden-xs\">" . db2web($row['pfirst_name']) . " </span><b>" . 
			db2web($row['plast_name']) . "</b>$ptelephone$instructor</td><td>". nl2br(db2web($row['r_comment'])) . "</td>\n") ;
		if ($row['r_type'] == BOOKING_MAINTENANCE) // Cannot link to a maintenance booking ;-)
			print("<td></td></tr>\n") ;
		else if ($row_flight['f_booking'] == $row['r_id']) // Is the flight already linked to this existing booking ?
			print("<td><a href=\"$_SERVER[PHP_SELF]?flight_id=$_REQUEST[flight_id]&unlink_from=$row[r_id]\"><span class=\"glyphicon glyphicon-scissors\" style=\"color: red;\" title=\"Découpler cette réservation de ce vol\"></span></a></td></tr>\n") ;
		else if ($row['f_reference'] != '')
			print("<td><span class=\"glyphicon glyphicon-link\" style=\"color: pink;\" title=\"Cette réservation est déjà associée à un vol\"></span></td></tr>\n") ;
		else // Flight is not linked yet to a booking
			print("<td><a href=\"$_SERVER[PHP_SELF]?flight_id=$_REQUEST[flight_id]&link_to=$row[r_id]\"><span class=\"glyphicon glyphicon-link\" title=\"Lier cette réservation à ce vol\"></span></a></td></tr>\n") ;
	}
	print("</table>
	</div><!-- row -->\n" ) ;
}

	if ($row_flight['f_date_1'] != '' and $row_flight['f_date_1'] != '0000-00-00s') show_reservation($row_flight['f_date_1'], 'Date préférée') ;
	if ($row_flight['f_date_2'] != '' and $row_flight['f_date_2'] != '0000-00-00') show_reservation($row_flight['f_date_2'], 'Date alternative') ;
	$booking_date = ($row_flight['r_start'] != '') ? substr($row_flight['r_start'], 0, 10) : null ;
	if ($booking_date and $booking_date != $row_flight['f_date_1'] and $booking_date != $row_flight['f_date_2']) 
		show_reservation($booking_date, 'Date de la réservation') ;
	// TODO also display the actual day of flight whenn the flight is booked by the pilot
?>
</div><!-- menuPlane -->


<div id="menuAudit" class="tab-pane fade">

<div class="row">
<?php if (! isset($row_flight['first_name']) or $row_flight['first_name'] == '') $row_flight['first_name'] = 'client via la page web' ; ?>
Ce vol a été créé le <?=$row_flight['f_date_created']?> par  par <?= id2name($row_flight['f_who_assigned'])?>.<br/>
<?php
if ($row_flight['f_date_cancelled']) print("Puis a été annulé le $row_flight[f_date_cancelled] par " . id2name($row_flight['f_who_cancelled']) . ".<br/>") ;
if ($row_flight['f_date_assigned']) print("Le pilote a été sélectionné le $row_flight[f_date_assigned] par " . id2name($row_flight['f_who_assigned']) . ".<br/>") ;
if ($row_flight['f_date_linked']) print("Une réservation a été liée à ce vol le $row_flight[f_date_linked] par " . id2name($row_flight['f_who_linked']) . ".<br/>") ;
if ($row_flight['f_date_flown']) print("Le vol a eu lieu le $row_flight[f_date_flown].<br/>") ;
?>
</div>

<h3>Historique des paiements</h3>
<div class="row">
	<?php
$result = mysqli_query($mysqli_link, "SELECT * FROM $table_flights_ledger JOIN $table_person ON fl_who=jom_id
	WHERE fl_flight = $flight_id ORDER BY fl_date ASC")
	or journalise($userId, "E", "Cannot read ledger: " . mysqli_error($mysqli_link)) ;
if (mysqli_num_rows($result) == 0)
	print("<p class=\"bg-danger\">Aucun paiement effectué.</p>") ;	
while ($row = mysqli_fetch_array($result))
	print("Paiement de $row[fl_amount] &euro; (" . db2web($row['fl_reference']) . ") effectué le $row[fl_date] par " . id2name($row['fl_who']) . ".<br/>") 
	?>
</div>
</div><!-- menuAudit -->

</div> <!-- tab-content-->

<script>
function giftChanged(flight_id) {
    var aGiftInput=document.getElementById("idgift");
    var aGift=aGiftInput.checked;
    var aReferenceInput=document.getElementById("idreference");
    var aPreviousReference=aReferenceInput.value;
    var aReference=aPreviousReference;
    if(aPreviousReference.indexOf("V-")==0) {
        if(!aGift) {
            aReference=aPreviousReference.substr(2);
            aReferenceInput.value=aReference;
        }
    }
    else {
        if(aGift) {
            aReference="V-"+aPreviousReference;
            aReferenceInput.value=aReference;
        }
    }
}

function flightTypeChanged(flight_id, gift) {
    var aReferenceInput=document.getElementById("idreference");
    var aPreviousReference=aReferenceInput.value;
    var aFlightTypeInput=document.getElementById("idFlightTypeSelect");
    var aType="";
    for (var i = 0; i < aFlightTypeInput.length; i++) {
    	var aFlightTypeOption=aFlightTypeInput[i];
    	if(aFlightTypeOption.selected==true) {
    	    aType=aFlightTypeOption.value;
            break;
    	}
    }
    var newReference=flight_id;
    if(aType=="decouverte") {
        newReference="IF-"+newReference;
        if(gift==1) {
            newReference="V-"+newReference;
        }
    }
    else if(aType == "initiation") {
        newReference="INIT-"+newReference;
        if(gift==1) {
            newReference="V-"+newReference;           
        }
    }
    else if (aType=="bon") {
        newReference="V-BON-"+newReference;
    }
    else if(aType=="dhf") {
        newReference="DHF-"+newReference;
    }
    if (confirm("Confirmer la nouvelle reference "+ newReference + " à la place de "+ aPreviousReference+".\nVoulez-vous vraiment introduire cette reference?")) {
        aReferenceInput.value=newReference;
		return ;
	}
}
function submitForm(id) {
	document.getElementById(id).submit() ;
}

function setValue(name, value) {
	document.getElementsByName(name)[0].value = value.replace(/<br\s*[\/]?>/gi, "\n") ;
}
//FlyType initialization
var aFlightType='<?=$row_flight['f_type']?>';
var aFlightTypeInput=document.getElementById("idFlightTypeSelect");
// Vol DHF
var aReference='<?=$row_flight['f_reference']?>';
if(aFlightType=="D" && aReference.indexOf("DHF-")==0) {
    aFlightType="DHF";
}
for (var i = 0; i < aFlightTypeInput.length; i++) {
	var aFlightTypeOption=aFlightTypeInput[i];
	if(aFlightTypeOption.value=="initiation" && aFlightType=="I") {
        var aFlightTypeInput=document.getElementById("idFlightTypeSelect");
		aFlightTypeOption.selected=true;
	}
	else if(aFlightTypeOption.value=="decouverte" && aFlightType=="D") {
		aFlightTypeOption.selected=true;
	}
	else if(aFlightTypeOption.value=="bon" && aFlightType=="B") {
		aFlightTypeOption.selected=true;
	}
	else if(aFlightTypeOption.value=="dhf" && aFlightType=="DHF") {
		aFlightTypeOption.selected=true;
	}
	else {
		aFlightTypeOption.selected=false;
	}
 }
 
 // Gift initialization
var aGiftInput=document.getElementById("idgift");
aGiftInput.checked=<?=$row_flight['f_gift']?>;
//document.getElementsByName('discovery_flight')[0].checked = ('<?=$row_flight['f_type']?>' == 'D') ;
//document.getElementsByName('initiation_flight')[0].checked = ('<?=$row_flight['f_type']?>' == 'I') ;
<?php
// Some fields do not exist when modifying a flight
if ($flight_id == '') {
?>
document.getElementsByName('student')[0].checked = ('<?=$row_contact['pr_role']?>' == 'S') ;
document.getElementsByName('pax')[0].checked = ('<?=$row_contact['pr_role']?>' == 'P') ;
document.getElementById('ageSelect').value = '<?=$row_contact['p_age']?>';
//setValue('birthdate', '<?=db2web($row_flight['p_birthdate'])?>') ;
setValue('weight', '<?=db2web($row_flight['p_weight'])?>') ;
<?php
}
?>
setValue('pax_cnt', '<?=db2web($row_flight['f_pax_cnt'])?>') ;
setValue('lname', '<?=db2web(addslashes($row_flight['p_lname']))?>') ;
setValue('fname', '<?=db2web(addslashes($row_flight['p_fname']))?>') ;
setValue('street', '<?=db2web(addslashes($row_flight['p_street']))?>') ;
setValue('zip', '<?=db2web($row_flight['p_zip'])?>') ;
setValue('city', '<?=db2web(addslashes($row_flight['p_city']))?>') ;
setValue('country', '<?=db2web(addslashes($row_flight['p_country']))?>') ;
setValue('email', '<?=db2web($row_flight['p_email'])?>') ;
setValue('phone', '<?=db2web($row_flight['p_tel'])?>') ;
setValue('comment', '<?=db2web(str_replace(array("\r\n", "\n", "\r"), "<br/>", addslashes($row_flight['f_description'])))?>') ;
setValue('reference', '<?=db2web($row_flight['f_reference'])?>') ;
setValue('notes', '<?=db2web(str_replace(array("\r\n", "\n", "\r"), "<br/>", addslashes($row_flight['f_notes'])))?>') ;
//for (var i = 0; i < document.getElementsByName("gender")[0].options.length; i++) {
//	if (document.getElementsByName("gender")[0].options[i].value == '<?=$row_flight['p_gender']?>')
//		document.getElementsByName("gender")[0].options.selectedIndex = i ;
//}
</script>

<?php
require_once 'flight_trailer.php' ;
?>
