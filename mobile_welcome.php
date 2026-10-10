<?php
/*
   Copyright 2013-2026 Eric Vyncke

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
# HTTP/2 push of some JS scripts via header()
$additional_preload = '</' . SITE_PATH . '/js/mobile_metar_tools.js>;rel=preload;as=script,' .
	'</' . SITE_PATH . '/images/metar_rose.png>;rel=preload;as=image' ;
require_once 'mobile_header5.php' ;
require_once "mobile_metar_tools.php";
require_once 'dto.class.php' ;

$station = strtoupper(trim($_REQUEST['station'] ?? ''));
if (!preg_match('/^[A-Z0-9]{4}$/', $station)) $station = $default_metar_station; // Check for XSS attack

$color1="#FFF4F4";
$color2="#EAF7E7";
$style1='class="border border-3 border-secondary rounded-3 shadow p-2 m-1 text-light" style="background-color:LightSlateGrey;"';
$style2='class="border border-3 border-secondary rounded-3 shadow p-2 m-1 text-light" style="background-color:LightSlateGrey;"';
$style3='class="border border-3 border-secondary rounded-3 bg-muted shadow p-2 m-1 text-light"';
?> 
<div class="container-fluid">
<h2 class="border border-3 border-secondary rounded-3 shadow mx-auto text-center text-light" style="background-color:LightSlateGrey;">Bienvenue dans l'espace Membre du RAPCS</h2>
<?php
if($userId==0) {
	print("<h1 style=\"color: red;\">Vous devez d'abord vous connecter pour accéder à l'espace Membre du RAPCS</h1>");
}
?>
<div class="row">
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-6">
		<div <?= $style1 ?>>
			<div>
			<?php displayProfile(); ?>
			<?php displayFolio(); ?>
			</div>
		</div>
	</div>
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-6">
		<div <?= $style2 ?>>
			<div>
				<?php displayReservation(); ?>
			</div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-6" <?= $style2 ?>>
		<div <?= $style2 ?>>
			<div>
				<?php displayMETAR($station); ?>
			</div>
		</div>
	</div>
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-6" <?= $style1 ?>>
		<div <?= $style1 ?>>
			<div>
				<?php displayMeteo(); ?>
			</div>
		</div>
	</div>
</div>
<!---
<div class="row">
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" <?= $style1 ?>>
		<div <?= $style1 ?>>
			<div>
				<?php displayWebcam("EBSP"); ?>
			</div>
		</div>
	</div>
</div>
-->
<div class="row">
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-4" <?= $style1 ?>>
		<div <?= $style1 ?>>
			<div>
				<?php displayWebcam("EBSP"); ?>
			</div>
		</div>
	</div>
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-4" <?= $style2 ?>>
		<div <?= $style2 ?>>
			<div>
				<?php displayWebcam("apron"); ?>
			</div>
		</div>
	</div>
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-4" <?= $style1 ?>>
		<div <?= $style1 ?>>
			<div>
				<?php displayWebcam("hangar"); ?>
			</div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-6" <?= $style1 ?>>
		<div <?= $style1 ?>>
			<div>
				<?php displayDepart(); ?>
			</div>
		</div>
	</div>
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-6" <?= $style2 ?>>
		<div <?= $style2 ?>>
			<div>
				<?php displayEphemeride(); ?>
			</div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-12">
		<div <?= $style3?>>
			<div id="metarMessage"> ... fetching data over the Internet ...</div> 
		</div>
</div>
</div> <!-- row -->


<script>
	displayMobileMETAR('<?=$station?>', 'picture') ;
</script>
<h5>(<?= SITE_HOST ?><?=  SITE_ICON ?>)</h5>
</div> <!-- container-->
</body>
</html>

<?php
//==============================================
// Function: displayProfile
// Purpose: 
//==============================================
function displayProfile()
{
	global $userId;
	print('<h4 class="text-center">Mon Profil</h4>');
	global $mysqli_link,$table_person,$table_blocked,$table_user_usergroup_map,$userId;
	if($userId!=0) {
		$result = mysqli_query($mysqli_link, "SELECT * 
		FROM $table_person LEFT JOIN $table_blocked on jom_id=b_jom_id
		WHERE jom_id = $userId")
		or journalise($userId, 'F', "Impossible de lire le pilote $userId: " . mysqli_error($mysqli_link)) ;
		$pilot = mysqli_fetch_array($result) or journalise($originalUserId, 'F', "Pilote $userId inconnu") ;
		$userName = db2web("$pilot[first_name] $pilot[last_name]") ;
		$blocked_reason = db2web($pilot['b_reason'] ?? '') ;
		$blocked_when = substr($pilot['b_when'] ?? '',0,10) ;
		print('<p class="lead"><b>Nom : </b>'.$userName.'</p>');
		if($blocked_reason=="") {
			print('<p class="lead"><b>Statut : <span class="bg-light text-success">OK</span></b></p>');
		}
		else {
			print('<p class="lead"><b>Status :</b><i class="bi bi-sign-stop-fill text-danger"></i><b><span style="color:red;"> Bloqué ('.htmlspecialchars($blocked_reason).' depuis '.$blocked_when.')</span></b>.</p>');
		}

	// Find all Odoo IDs
		$sql = "SELECT * FROM  $table_user_usergroup_map WHERE user_id= $userId ";
		//print("SQL=$sql<br>");
		$result = mysqli_query($mysqli_link, $sql);
		print('<p class="lead"><b>Rôle:</b> ');
		$count=0;
		while ($row = mysqli_fetch_array($result)) {
			$groupId=$row['group_id'];
			if($groupId!="") {
				$count++;
				if($count>1){print("/");}
				print(getGroupName($groupId));
			}
		}
		print("</p>");
/*
	// Afficher les limit medical, sep, ...
		$member=new DTOMember();
		$member->getById($userId);
		var_dump($member);
		print("<br>");
*/
	}
}
//==============================================
// Function: getGroupName
// Purpose: 
//==============================================
function getGroupName($groupId)
{
	$groups = array(
		6=>"Gestionnaire Site",
		7=>"Administrateur Web",
		8=>"Super Utilisateur",
		13=>"Pilote",
		14=>"Instructeur Vol",
		15=>"Instructeur Théorique",
		16=>"Elève",
		17=>"Mecano",
		18=>"Membre",
		19=>"Pilote Vol Découverte",
		20=>"Gestion Vol Découverte",
		22=>"Membre de l'OA",
		24=>"Développeur Site",
		25=>"Membre Effectif",
		26=>"Elève Cours Théorique"
	);
	
	if (array_key_exists($groupId,$groups))
  	{
  		return $groups[$groupId];
 	}
	return "";
}

//==============================================
// Function: displayFolio
// Purpose: 
//==============================================
function displayFolio()
{
	global $odoo_host, $odoo_db, $odoo_username, $odoo_password;
	global $mysqli_link,$table_person,$table_blocked,$userId;
	print('<h4 class="text-center">Mon Folio</h4>');
	if($userId!=0) {
		$result = mysqli_query($mysqli_link, "SELECT * 
		FROM $table_person LEFT JOIN $table_blocked on jom_id=b_jom_id
		WHERE jom_id = $userId")
		or journalise($userId, 'F', "Impossible de lire le pilote $userId: " . mysqli_error($mysqli_link)) ;
		$pilot = mysqli_fetch_array($result) or journalise($originalUserId, 'F', "Pilote $userId inconnu") ;
		//$userName = db2web("$pilot[first_name] $pilot[last_name]") ;
		//$userLastName = db2web($pilot['last_name'] ?? '') ;
		$odooId = $pilot['odoo_id'] ;

		if ($odooId != '') {
				$balance_text="?????";
			require_once 'odoo.class.php' ;
			$odooClient = new OdooClient($odoo_host, $odoo_db, $odoo_username, $odoo_password) ;
			$accounts = $odooClient->SearchRead('res.partner', array(array(
						array('id', '=', intval($odooId))
					)), 
					array('fields' => array('id', 'total_due'))) ;
			$balance = -1.0 * $accounts[0]['total_due'] ;
			if ($balance < 0) {
				$balance_text = number_format($balance,2,",",".");
			} 
			else {
				$balance_text = "+".number_format($balance,2,",",".");
			}
			print('<p class="lead">Solde Compte Pilote: <b class="text-danger bg-light">'.$balance_text.'&euro;</b></p>');
		} 
		else { // Odoo account does not exist
			$balance = 0 ;
			$invoice_total = 0 ;
			$invoice_reason = '' ;
			journalise($userId, "E", "No Odoo id associated to $userName") ;
			print("<p class=\"text-danger\">Vous n'avez pas encore de compte dans la comptabilité.</p>\n") ;
		}
	}
}
//==============================================
// Function: displayReservation
// Purpose: 
//==============================================
function displayReservation()
{
	global $mysqli_link,$table_person,$table_blocked,$table_bookings,$table_users;
	global $userId;
	print('<h4 class="text-center">Mes Réservations</h4>');
	if($userId!=0) {
		$id=$userId;
		$sql="SELECT * FROM $table_bookings WHERE r_pilot=$userId and r_start>=sysdate() and r_cancel_date is null";
		//print("SQL=$sql<br>");
		$result = mysqli_query($mysqli_link, $sql ) or die("Cannot access the booking #$id: " . mysqli_error($mysqli_link)) ;
	?>

		<div class="row">
		<table class="col-sm-12 table table-responsive table-striped p-2 m-2" width="90%">
			<thead>
				<tr><th>De</th><th>À</th><th>Avion</th><th>DC</th><th>Commentaire</th></tr>
			</thead>
			<tbody class="table-group-divider">
	<?php
				$count=0;
				while ($row = mysqli_fetch_array($result)) {
						$count++;
						$date=$row['r_start'];
						$plane=$row['r_plane'];
						$instructor=$row['r_instructor'];
						if($instructor!="") {$instructor= "DC";}
						$comment=$row['r_comment'];
						$class = ($row['r_type'] == BOOKING_MAINTENANCE) ? ' class="text-danger"' : '' ;
						$class = ' class="text-warning"' ;  // ERIC cela écrase la valeur ci-dessus ?
						$dateDe=substr($row['r_start'], 0,16) ;
						$dateA=substr($row['r_stop'], 0,16) ;
						print("<tr><td>$dateDe</td><td>$dateA</td><td>$plane</td><td>$instructor</td><td$class>". nl2br(htmlspecialchars(db2web($comment))) . "</td></tr>\n") ;
				}
				if($count==0) {
						print('<tr><td colspan="5" class="text-warning" >Aucune réservation prévue</td></tr>') ;
				}
	?>
			</tbody>
		</table>
		</div><!-- row -->
	<?php

	}

}

//==============================================
// Function: displayMETAR
// Purpose: 
//==============================================
function displayMETAR($station)
{
	print('<h4 class="text-center">Metar</h4>');
	?>
	<div class="row">
		<?php rapcs_display_metar($station, 'picture'); ?>
	</div> <!-- row -->

	<div class="row d-sm-none d-md-block">
		<footer class="blockquote-footer">Source <cite title="Source du METAR" id="sourceId"></cite></footer>
	</div> <!-- row -->
	<div class="row">
		<form class="form-inline" action="<?=$_SERVER['PHP_SELF']?>" method="GET">
			<div class="form-group">
				<label class="control-label col-4 col-md-4" for="stationMETARInput">Station METAR:</label>
				<div class="col-3 col-md-4">
					<input type="text" size="5" maxlength="4" class="form-control" id="stationMETARInput" placeholder="<?=$station?>" name="station">
				</div>
			</div>
			<div class="form-group">
				<div class="col-3 col-md-4">
	      			<input type="submit" class="btn btn-primary" value="Changer">
   				</div>
			</div><!-- formgroup-->
		</form>
	</div> <!-- row -->
	<?php
}

//==============================================
// Function: displayRAPCSNotam
// Purpose: 
//==============================================
function displayRAPCSNotam() 
{
	global $userId;
	global $mysqli_link,$table_news;
	print('<h4 class="text-center">Notam RAPCS</h4>');
	if($userId!=0) {
		$result_news = mysqli_query($mysqli_link, "SELECT * FROM $table_news
		WHERE n_stop >= CURRENT_DATE() and n_start <= CURRENT_DATE()
		ORDER BY n_id DESC
		LIMIT 0,3") or die("Cannot fetch news: " . mysqli_error($mysqli_link)) ;
		
		if (mysqli_num_rows($result_news)) {
			print('<ul>') ;
			while ($row_news = mysqli_fetch_array($result_news)) {
				$subject = db2web($row_news['n_subject']) ;
				$text = db2web(nl2br($row_news['n_text'])) ;
				print("<li><b>$subject</b>: $text</li>\n") ;
			}
			print('</ul>') ;
		}
	mysqli_free_result($result_news) ;
	}
}

//==============================================
// Function: displayWebcam
// Purpose: 
//==============================================
function displayWebcam($webcam)
{
	print('<h4 class="text-center">Webcam '.$webcam.'</h4>');
    if($webcam=="EBSP") {
?>
        <div style="text-align: center;">
            <iframe 
				style="aspect-ratio: 6 / 4; object-fit: cover;width: 100%;" 
				src="https://g0.ipcamlive.com/player/player.php?alias=camebspairside&autoplay=1&mute=1&disableautofullscreen=1&disablezoombutton=p;disableframecapture=1&disabletimelapseplayer=1&disablestorageplayer=1&disabledownloadbutton=1&disableplaybackspeedbutton=1&disablenavigation=1&disableuserpause=1" 
				frameborder="0" 
				loading="lazy"
				title="Webcam aire à signaux"
				allowfullscreen="allowfullscreen">
			</iframe>
        </div>
<?php
    }
    else if($webcam=="hangar") {
?>
        <div style="text-align: center;">
            <img style="aspect-ratio: 6/ 4; object-fit: cover;width: 100%;" src="https://nav.vyncke.org/rapcs/snapshot-hangars.jpg" />
        </div>
<?php
    }
    else {
		// Apron
?>
        <div style="text-align: center;">
            <img style="aspect-ratio: 6/ 4; object-fit: cover;width: 100%;" src="https://nav.vyncke.org/rapcs/snapshot-apron.jpg"/>
        </div>
<?php
    }
}

//==============================================
// Function: displayDepart
// Purpose: 
//==============================================
function displayDepart()
{
	global $userId;
	global $mysqli_link,$table_person,$table_blocked,$table_bookings,$table_users;
	print('<h4 class="text-center">Réservation du jour</h4>');

	if($userId!=0) {
		$id=$userId;
		$sql="SELECT * FROM $table_bookings WHERE r_start>sysdate() and r_cancel_date is null";
		//print("SQL=$sql<br>");
		$result = mysqli_query($mysqli_link, $sql ) or die("Cannot access the booking #$id: " . mysqli_error($mysqli_link)) ;
	?>

		<div class="row">
		<table class="col-sm-12 table table-responsive table-striped p-2 m-2" width="90%">
			<thead>
				<tr><th>Nom</th><th>De</th><th>À</th><th>Avion</th><th>DC</th><th>Commentaire</th></tr>
			</thead>
			<tbody class="table-group-divider">
	<?php
				$count=0;
				while ($row = mysqli_fetch_array($result)) {
						$count++;
						$plane=$row['r_plane'];
						$instructor=$row['r_instructor'];
						if($instructor!="") {$instructor= "DC";}
						$nom="To Do";
						$comment=$row['r_comment'];
						$class = ($row['r_type'] == BOOKING_MAINTENANCE) ? ' class="text-danger"' : '' ;
						$class = ' class="text-warning"' ;
						$dateDe=substr($row['r_start'], 0,16) ;
						$dateA=substr($row['r_stop'], 0,16) ;
						print("<tr><td>$nom</td><td>$dateDe</td><td>$dateA</td><td>$plane</td><td>$instructor</td><td$class>". nl2br(htmlspecialchars(db2web($comment))) . "</td></tr>\n") ;
				}
				if($count==0) {
						print('<tr><td colspan="6" class="text-warning">Aucune réservation prévue</td></tr>') ;
				}
	?>
			</tbody>
		</table>
		</div><!-- row -->
	<?php

	}
}

//==============================================
// Function: displayMeteo
// Purpose: 
//==============================================
function displayMeteo()
{
	print('<h4 class="text-center">Météo Windy</h4>');
?>
	<iframe 
		src="https://embed.windy.com/embed2.html?lat=$apt_latitude&lon=$apt_longitude&zoom=9&level=surface&overlay=radar&menu=false&theme=dark" 
		width="100%" 
		frameborder="0"
		loading="lazy" title="Windy" alt="Windy"
		style="aspect-ratio: 6/ 4; object-fit: cover;width: 100%;">
	</iframe><?php
	}

//==============================================
// Function: function displayEphemeride()

// Purpose: 
//==============================================
function displayEphemeride()
{
	print('<h4 class="text-center">Ephéméride</h4>');
	$fontSize = '1em' ;
	$default_airport="EBSP";
?> 
		<section class="row" style="font-size: <?=$fontSize?>">
			<dl class="row m-0 w-100">
				<dt class="col-md-4 col-8">Jour aéronautique:</dt>
				<dd id="aeroDay" class="col-md-2 col-4"></dd>
				
				<dt class="col-md-4 col-8">Nuit aéronautique:</dt>
				<dd id="aeroNight" class="col-md-2 col-4"></dd>
				
				<dt class="col-md-4 col-8">Lever du soleil:</dt>
				<dd id="civilDay" class="col-md-2 col-4"></dd>
				
				<dt class="col-md-4 col-8">Coucher du soleil:</dt>
				<dd id="civilNight" class="col-md-2 col-4"></dd>
				
				<dt class="col-md-4 col-8">Ouverture aéroport:</dt>
				<dd id="airportDay" class="col-md-2 col-4"></dd>
				
				<dt class="col-md-4 col-8">Fermeture aéroport:</dt>
				<dd id="airportNight" class="col-md-2 col-4"></dd>
			</dl>

			<aside class="col-sm-12">
				<em><strong>En heure locale de <?=$default_airport?> et pour info seulement.</strong></em>
			</aside>

			<dl class="row m-0 w-100">
				<dt class="col-md-4 col-8">Heure locale à <?=$default_airport?>:</dt>
				<dd class="col-md-2 col-4"><time id="hhmmLocal"></time></dd>
				
				<dt class="col-md-4 col-8">Heure universelle:</dt>
				<dd class="col-md-2 col-4"><time id="hhmmUTC"></time></dd>
			</dl>
		</section>

	<script>
		refreshEphemerides(<?=date('Y')?>, <?=date('m')?>, <?=date('d')?>) ;
		displayClock() ;
	</script>

	</div> <!-- container-->
<?php
}
?>