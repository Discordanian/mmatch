<?php

ini_set('display_errors', 'On');
ini_set('session.cookie_httponly', 'On');
ini_set('session.cookie_lifetime', '14400');
ini_set('session.gc_maxlifetime', '86400');
ini_set('session.gc_probability', '1');

error_reporting(E_ALL | E_STRICT);

define("DUPLICATE_ORG_NAME_ERROR", "The organization name entered was a duplicate.");

?>

