<?php

require_once('../include/inisets.php');
require_once('../include/returnOrgsForZipcodeFunction.php');

/* This program takes a zip code and range as a parameter, validates it,
	and returns the list of orgs that are within range of that zip code
	along with their responses */
/* TODO: Should this function require authentication? It could be abused. */
if (array_key_exists("zip_code", $_GET) && isset($_GET["zip_code"]) 
	&& array_key_exists("range_miles", $_GET) && isset($_GET["range_miles"]))
{
    try 
    {
        echo getZipCodeData($_GET["zip_code"], $_GET["range_miles"]);
    }
    catch(Exception $e)
    {
        if ($e->getMessage() == "No data was found for that criteria.")
        {
    	header("HTTP/1.0 404 Not found", true, 404);
        }
        else 
        {
        error_log("Error while retrieving org data: " . $e->getMessage());
    	header("HTTP/1.0 500 Server Error", true, 500);
        }
    }
}
else
{
    error_log("Error while retrieving org data. Required parameters not properly supplied.");
	header("HTTP/1.0 500 Server Error", true, 500);
	exit();
}

?>
