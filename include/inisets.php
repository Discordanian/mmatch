<?php

ini_set('display_errors', 'On');
ini_set('session.cookie_httponly', 'On');
ini_set('session.cookie_lifetime', '14400');
ini_set('session.gc_maxlifetime', '86400');
ini_set('session.gc_probability', '1');
ini_set('session.cookie_secure', 'Off');

error_reporting(E_ALL | E_STRICT);

define("SUCCESSFULLY_LOGGED_OFF", "Successfully logged off. You can now log on again.");
define("LOGGED_OFF_INACTIVITY", "Due to inactivity you have been logged off. Please log on again");
define("DUPLICATE_ORG_NAME_ERROR", "The organization name entered was a duplicate.");
define("DUPLICATE_EMAIL_ERROR", "The email address entered was a duplicate.");
define("USER_NOT_LOGGED_IN_ERROR", "The user must log in to perform this function. Please log in.");
define("ERROR_TOKEN_EXPIRED", "Date token expired");
define("PARAMETER_TAMPERING", "An input error was encountered. Please log in.");


