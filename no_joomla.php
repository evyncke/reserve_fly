<?php
/*
   Copyright 2014-2026 Eric Vyncke

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
/**
 * Authenticate against Joomla 3 user tables without loading Joomla.
 *
 * The caller must provide an already connected mysqli connection. The Joomla
 * tables are expected to use the standard `jom_` prefix used by this site.
 */

/**
 * Check Joomla credentials and create the application session.
 *
 */

DEFINE('PSEUDO_JOOMLA_KEY', 'no_joomla_user') ;

ini_set('display_errors', 1) ; // extensive error reporting for debugging

// Ensure sessions are kept across browsers reloads.

DEFINE('SESSION_COOKIE_LIFETIME', 60 * 60 * 24 * 30) ; // 30 days
// How long the data is considered 'valid' on the server
ini_set('session.gc_maxlifetime', SESSION_COOKIE_LIFETIME);
// How long the cookie lives in the browser
ini_set('session.cookie_lifetime', SESSION_COOKIE_LIFETIME);

function startSessionIfRequired(): bool {
	if (session_status() !== PHP_SESSION_ACTIVE) {
		 session_set_cookie_params([
		 	'lifetime' => SESSION_COOKIE_LIFETIME,
		 	'path' => '/',
		 	'domain' => $_SERVER['HTTP_HOST'],
		 	'secure' => true,     // Recommended: only send over HTTPS
		 	'httponly' => true,   // Recommended: prevents JS access to the cookie
		 	'samesite' => 'Lax'   // Recommended: protects against CSRF
        ]);
		if (session_start()) {
			return true;
		}
		journalise(0, "E", "Failed to start session: " . session_status()) ;
		return false ;
	}
	return true ;
}

function authenticateJoomlaUser(string $username, string $password): object|false {
    global $table_user_usergroup_map, $table_users, $mysqli_link ;

//	if (session_status() === PHP_SESSION_ACTIVE)
//		journalise(0, "W", "authenticateJoomlaUser(): Session is already active when trying to authenticate Joomla user") ;

	if (!startSessionIfRequired()) {
		return false;
	} ;

	$sql = "SELECT id, username, name, email, password
			FROM $table_users
			WHERE username = ? AND block = 0 AND (activation IS NULL OR activation = '')
			LIMIT 1";
	$statement = mysqli_prepare($mysqli_link, $sql);
	if ($statement === false || !mysqli_stmt_bind_param($statement, 's', $username)
		|| !mysqli_stmt_execute($statement)) {
		if ($statement !== false) {
			mysqli_stmt_close($statement);
		}
		return false;
	}

	mysqli_stmt_bind_result($statement, $userId, $storedUsername, $name, $email, $storedPassword);
	$found = mysqli_stmt_fetch($statement);
	mysqli_stmt_close($statement);

	if (!$found || !is_string($storedPassword)) {
		return false;
	}

	$passwordMatches = false;
	if (str_starts_with($storedPassword, '$2')) {
		$passwordMatches = password_verify($password, $storedPassword);
	} elseif (preg_match('/^([a-f0-9]{32}):([a-f0-9]+)$/i', $storedPassword, $parts)) {
		// Joomla 3's legacy md5(password + salt) format.
		$passwordMatches = hash_equals($parts[1], md5($password . $parts[2]));
	} 

	if (!$passwordMatches) {
		return false;
	}

	$statement = mysqli_prepare(
		$mysqli_link,
		"SELECT group_id FROM $table_user_usergroup_map WHERE user_id = ? ORDER BY group_id"
	);
	if ($statement === false || !mysqli_stmt_bind_param($statement, 'i', $userId)
		|| !mysqli_stmt_execute($statement)) {
		if ($statement !== false) {
			mysqli_stmt_close($statement);
		}
		return false;
	}

	mysqli_stmt_bind_result($statement, $groupId);
	$groups = [];
	while (mysqli_stmt_fetch($statement)) {
		# or ? $groups[$groupId] = $groupId;
	    # $groups[(int) $groupId] = (int) $groupId;
	    $groups[(int) $groupId] = $groupId;
	}
	mysqli_stmt_close($statement);

// TODO: analyse why the line below is called everytime in mobile_login
//	session_regenerate_id(true);
	$_SESSION['jom_id'] = (int) $userId;
	$pseudo_joomla_user = (object) [
		'id' => (int) $userId,
		'username' => $storedUsername,
		'name' => $name,
		'email' => $email,
        'guest' => false,
	];
	$pseudo_joomla_user->groups = $groups;
    $_SESSION[PSEUDO_JOOMLA_KEY] = json_encode($pseudo_joomla_user, JSON_FORCE_OBJECT) ;

	return $pseudo_joomla_user ;
}

/**
 * Read the Joomla user id and group ids belonging to the current session.
 *
 */
function getJoomlaSessionUser(): object|false{
//	if (session_status() === PHP_SESSION_ACTIVE)
//		journalise(0, "W", "getJoomlaSessionUser(): Session is already active when trying to get Joomla session user") ;
	if (!startSessionIfRequired()) {
		return false;
	}

    if (empty($_SESSION) || $_SESSION === false || !isset($_SESSION['jom_id']) || !isset($_SESSION[PSEUDO_JOOMLA_KEY])) {
        return false ;
    }
    if (isset($_SESSION[PSEUDO_JOOMLA_KEY])) {
        try {
        if (json_validate($_SESSION[PSEUDO_JOOMLA_KEY], 512, false)) {
            return json_decode($_SESSION[PSEUDO_JOOMLA_KEY], false, 512) ; # Decode and return an object
        } else {
            return $_SESSION[PSEUDO_JOOMLA_KEY] ;
        }
        } catch (Exception $e) {
            journalise(0, "E", "Exception in JSON handling: " . $e->getMessage()) ;
            return false ;
        }
    } else {
        return false ;
    }
}
