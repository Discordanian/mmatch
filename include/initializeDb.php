<?php

require_once('secrets.php');

function initializeDb()
{

global $dbh;

    try 
    {
        if (!isset($dbh))
        {
            $dbh = new PDO(DATABASE_DSN , DATABASE_USER, DATABASE_PASSWORD,
				array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
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
