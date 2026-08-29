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
?>