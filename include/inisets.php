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

/* generate a nonce which should be completely unpredictable, for use by inline script tags */
$csp_nonce = substr(base64_encode(hash("sha256", $_SERVER["UNIQUE_ID"] . $_SERVER["REQUEST_TIME_FLOAT"] . openssl_random_pseudo_bytes(6), true)), 0, 20); 

$csp = "Content-Security-Policy: default-src 'none'; " .
    "connect-src 'self'; img-src 'self'; " .
    "font-src https://cdnjs.cloudflare.com; " .
    "script-src 'self' 'nonce-{$csp_nonce}'  https://cdnjs.cloudflare.com https://ajax.googleapis.com; " .
    "style-src 'self' https://cdnjs.cloudflare.com https://ajax.googleapis.com https://fonts.googleapis.com;";

header($csp);

?>

