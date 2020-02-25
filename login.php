<?php
// Get information from Joomla
define( '_JEXEC', 1 );
define( 'JPATH_BASE', realpath(dirname(__FILE__) . '/..' ));
require_once ( JPATH_BASE . '/includes/defines.php' );
require_once ( JPATH_BASE . '/includes/framework.php' );
$mainframe = JFactory::getApplication('site');
$mainframe->initialise();
$joomla_session = JFactory::getSession() ;
jimport('joomla.user.helper');
print("Password = " . JUserHelper::hashPassword('rita')) ;
print("<hr>\n") ;
print("Verify = " . JUserHelper::verifyPassword('ritas', '', 62)) ;
exit ;

print("<pre>\n<h2>mainframe</h2>\n") ;
print_r($mainframe) ;
print("\n<h2>session</h2>\n") ;
print_r($joomla_session) ;
print("\n</pre>\n") ;

// Check whether I can login ?

require_once 'dbi.php' ;

print("<h1>session_name</h1>\n") ;
print(session_name()) ;
print("<h1>session_get_cookie_params</h1>\n") ;
print_r(session_get_cookie_params()) ;
print("\n<h1>_SESSION</h1>\n") ;
print_r($_SESSION) ;
if (isset($_SESSION['joomla']))
	print("<h2>unserliazed</h2>\n" . unserialize($_SESSION['joomla']) . "<hr>\n") ;
$cookie_name = bin2hex(random_bytes(16)) ;
$session_id = bin2hex(random_bytes(16)) ;
$timestamp = sprintf("%d", time()) ;
setcookie($cookie_name, $session_id, time() + 600, '/', 'www.spa-aviation.be') ;
mysqli_query($mysqli_link, "insert into $table_session(session_id, client_id, guest, time, data, userid, username)
	values('$session_id', 1, 0, '$timestamp', NULL, 62, 'evyncke')")
	or die("Erreur SQL: " . mysqli_error($mysqli_link)) ;
print("Logged in, cookie = $cookie_name, session_id = $session_id<hr>") ;

?>