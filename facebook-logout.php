<?php
require_once "dbi.php" ;

session_start() ;
unset($_SESSION['fb_access_token']);

header("Location: https://www.spa-aviation.be/resa/reservation_fb.php") ;
exit() ;
?>