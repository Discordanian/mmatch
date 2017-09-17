<?php

require_once('../include/inisets.php');
require_once('../include/secrets.php');

/* This program takes a zip code as a parameter, validates it,
	and either sends back the city name as feedback to the user,
	or returns an error message */
/* TODO: Should this function require authentication? It could be abused. */
if (array_key_exists("zip_code", $_GET) && isset($_GET["zip_code"]))
{
    getZipCodeData();
}
else
{
	header("HTTP/1.0 500 Server Error", true, 500);
    echo "An unknown error (1) occurred trying to look up the zip code.";
}


function getZipCodeData()
{
    global $dbh;

    /* sanitize these values brought in before I do any processing based upon them */
	/* this ensures the entered value is 5 numeric digits and that's it */
    sscanf($_GET["zip_code"], "%05u", $zipcode);

    if ($zipcode <= 0)
    {
		header("HTTP/1.0 500 Server Error", true, 500);
		echo "An unknown error (2) occurred trying to look up the zip code.";
    }
    else
    {

		initializeDb();
        $stmt = $dbh->prepare("SELECT zip_code, city, state FROM zip_code_ref WHERE zip_code = :zip_code ; ");

        $stmt->bindValue(':zip_code', $zipcode);

	    $stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            $erinf = $stmt->errorInfo();
            error_log("Query failed. Error code:" . $stmt->errorCode() . $erinf[2]); /* the error message in the returned error info */
			header("HTTP/1.0 500 Server Error", true, 500);
			echo "An unknown error (3) occurred trying to look up the zip code.";
        }
		else
		{
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if (isset($row) && ($row != false))
			{
				//printf("%05u - %s, %s", $row["zip_code"], $row["city"], $row["state"]); /* do we need to sanitize here?, asking for a friend */
				echo(json_encode($row));
			}
			else
			{
				header("HTTP/1.0 404 Not found", true, 404);
				echo(json_encode(array($zipcode, "Not found")));
				//printf("%05u - That zip code was not found.", $zipcode);;
			}
		}
		
        $stmt->closeCursor();
    }
}


function initializeDb()
{

    try 
    {
        if (!isset($dbh))
        {
            global $dbh, $dbhostname, $dbusername, $dbpassword;
            $dbh = new PDO("mysql:dbname=MoveM;host={$dbhostname}" , $dbusername, $dbpassword);
        }
    }
    catch (PDOException $e)
    {
        die("Database Connection Error: " . $e->getMessage());
        /* TODO: much better/cleaner handling of errors */
    }
    catch(Exception $e)
    {
        die($e->getMessage());
        /* TODO: much better/cleaner handling of errors */
    }

}



?>