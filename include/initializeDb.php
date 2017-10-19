<?php

require_once('secrets.php');

function initializeDb()
{

global $dbh, $dbhostname, $dbusername, $dbpassword;

    try 
    {
        if (!isset($dbh))
        {
            $dbh = new PDO("mysql:dbname=MoveM;host={$dbhostname}" , $dbusername, $dbpassword);
        }
    }
    catch (PDOException $e)
    {
        error_log("Database connection error: " . $e->getMessage());
        throw new Exception("An unknown error was encountered. Please try again.");
		exit();
    }
    catch(Exception $e)
    {
        error_log("Unspecified error while connecting to database: " . $e->getMessage());
        throw new Exception("An unknown error was encountered. Please try again.");
		exit();
    }

}


?>
