<?php

require_once('include/inisets.php');
require_once('include/secrets.php');

/* Only one way this page goes, take input from GET request, validate */
/* If valid, then switch email to verified */
/* Otherwise, display an error message */

/* TODO: This code may be vulnerable to XSS 
and probably a bunch of other attacks
Needs serious security review */

if (isset($_GET["email"]) && isset($_GET["token"]) && isset($_GET["orgid"]))
{
    testToken();
}
else
{
    $err_msg = "An unknown error (1) occurred trying to verify the email using that link. Please try to log in again and request another email.";
}


function testToken()
{
    global $orgid, $email, $csrf_salt, $err_msg;

    /* sanitize these values brought in before I do any processing based upon them */

    $orgid = filter_var($_GET["orgid"], FILTER_VALIDATE_INT);
    $email = filter_var($_GET["email"], FILTER_SANITIZE_EMAIL);

    if (($orgid <= 0) || (strlen($email) <= 0))
    {
        $err_msg="Unable to verify based upon the information provided (2). Please try to log in again and request another email.";
    }
    else
    {

        $err_msg="Unable to verify based upon the information provided (3). The link may have expired. Please try to log in again and request another email.";
        /* TODO: make time zone parameter driven */
        /* or use UTC/GMT or use Eastern Time */
        $date = new DateTime(NULL, timezone_open("America/Chicago"));
        $din = new DateInterval("P1D"); /* 1 day dateinterval */

        for ($i = 0; $i <= 3; $i++)
        {
     
            $datetext = $date->format("d-M-Y");

            $input = $_SERVER["SERVER_NAME"] . $email . $datetext . $orgid . "emailverify.php" . $csrf_salt;
            $token = substr(hash("sha256", $input), 0, 18); /* pull out only the 1st 18 digits of the hash */

            if ($token == $_GET["token"])
            {
                initializeDb();
                verifyEmail();
            }

            $date = $date->add($din);

        }
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

function verifyEmail()
{
    try
    {
        global $dbh, $orgid, $success_msg, $err_msg, $email;



        $stmt = $dbh->prepare("UPDATE org SET email_verified = :email, email_unverified = NULL WHERE orgid = :orgid AND email_unverified = :email ; ");

        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':orgid', $orgid);

	    $stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            $erinf = $stmt->errorInfo();
            error_log("Update failed. Error code:" . $stmt->errorCode() . "<br>" . $erinf[2]); /* the error message in the returned error info */
            $err_msg = "An unknown error (4) occurred trying to verify the email using that link. Please try to log in again and request another email.";
            $success_msg = NULL;
        }
		else
		{
			$success_msg = sprintf("Email address %s successfully verified.", htmlspecialchars($email));
            $err_msg = NULL;
		}
		
        $stmt->closeCursor();

        /* TODO: detect email change and add verification email */

    }
    catch (PDOException $e)
    {
        error_log("Database Query Error: " . $e->getMessage());
        $err_msg = "An unknown error (5) occurred trying to verify the email using that link. Please try to log in again and request another email.";
        $success_msg = NULL;
    }
    catch(Exception $e)
    {
        error_log($e->getMessage());
        $err_msg = "An unknown error (6) occurred trying to verify the email using that link. Please try to log in again and request another email.";
        $success_msg = NULL;
    }

    
}


?>
<!DOCTYPE html>
<html lang="en" >
<head>
    <meta charset="UTF-8">
    <title>Movement Match - Organization</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script src="js/login.js"></script>
  
</head>

<body>

<div class="container-fluid">

<center>
<div class="page-header">
    <h1>Movement Match</h1>
    <h2>Verify Email</h2>
</div>
</center>


<div class="alert alert-success" <?php if (!isset($success_msg)) echo "hidden='true'"; ?> id="success_msg" >
    <?php if (isset($success_msg)) echo $success_msg; ?>
</div>

  
<div class="alert alert-danger" <?php if (!isset($err_msg)) echo "hidden='true'"; ?> id="err_msg" >
    <?php if (isset($err_msg)) echo $err_msg; ?>
</div>


</div> <!-- Container fluid -->


</body>
</html>

