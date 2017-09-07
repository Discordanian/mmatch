<?php

require_once('include/inisets.php');
require_once('include/secrets.php');

/* This page is so that AJAX requests from the page */
/* Can asynchronously trigger the verification email to be sent */

/* TODO: Needs serious security review */

if (isset($_GET["email"]) && isset($_GET["token"]) && isset($_GET["orgid"]))
{
    checkValidRequest();
    sendVerificationEmail();
}

function checkValidRequest()
{
	global $csrf_salt, $orgid, $email;

    $orgid = filter_var($_GET["orgid"], FILTER_VALIDATE_INT);
    $email = filter_var($_GET["email"], FILTER_SANITIZE_EMAIL);

	/* since this is not very security critical use case, (it just sends an email */
	/* perform basic verification that */
	/* the request came from a valid source and not a random person */
	/* also, this URL currently does not expire */
	$input = $_SERVER["SERVER_NAME"] . $_GET["email"] . $_GET["orgid"] . "sendverifyemail.php" . $csrf_salt;
	$token = hash("sha256", $input);
	/* verify that the token that we calculate equals the token that was passed in */
	/* they could only be equal for a process that knows the secret salt value */
	if ($token != $_GET["token"])
	{
		error_log("Possible parameter tampering encountered in sendverifyemail.php");
		http_response_code(500);
		exit();
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

function lookupEmail()
{

    try {

        global $dbh, $orgid, $email;

        $stmt = $dbh->prepare("SELECT orgid, email_unverified FROM org WHERE orgid = :orgid AND email_unverified = :email ;");
        $stmt->bindValue(':orgid', $orgid);
		$stmt->bindValue(':email', $email);
        $stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            echo "Error code:<br>";
            $erinf = $stmt->errorInfo();
			http_response_code(500);
            die("Insert failed<br>Error code:" . $stmt->errorCode() . "<br>" . $erinf[2]); /* the error message in the returned error info */
        }
		

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if (!isset($row))
		{
			error_log("Failed to look up valid email and orgid combination in sendverifyemail.php.");
			http_response_code(500);
			exit();
		}

		
		$stmt->closeCursor();
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


function sendVerificationEmail()
{
    global $email, $csrf_salt, $orgid;

    /* calculate the date 3 days into the future */
    /* TODO: use different time zones depending upon the locality of the organization ? */
    /* Links effectively expire at midnight */
    /* Or just use eastern time for everything, or UTC/GMT */
    $date = new DateTime(NULL, timezone_open("America/Chicago"));
    $din = new DateInterval("P3D");
    $expdate = $date->add($din);
    /* TODO: make expiration date of link parameter driven */
    $datetext = $expdate->format("d-M-Y");

    $input = $_SERVER["SERVER_NAME"] . $email . $datetext . $orgid . "emailverify.php" . $csrf_salt;
    $token = substr(hash("sha256", $input), 0, 18); /* pull out only the 1st 18 digits of the hash, make it a typable link? */
    $link = sprintf("http://%s/mmatch/emailverify.php?email=%s&token=%s&orgid=%d", $_SERVER["SERVER_NAME"], $email, $token, $orgid);

	//echo "<!-- $input -->\n"; /* TODO: This is a cheat and a security vulnerability. Remove it */
	echo "<!-- $link -->\n"; /* TODO: This is a cheat so I don't have to actually send/receive the email. Remove this */
	
    $message = sprintf("You apparently registered an account with movementmatch.org.\n" .
        "Click on the following link in order to verify that this was intended: \n" .
        "\t%s\n" .
        "This link will expire within 3 days. \n" .
        "If you did not intend to register an account, just ignore and delete this message. \n", $link);

	/* send the verification email */
    $res = mail($email, "Verify your account with movementmatch.org", $message, "From: admin@movementmatch.org");
	/* I think on CentOS or other SELinux enabled systems, this will not work until you run: #setsebool -P httpd_can_sendmail=1 */
	/* TODO: handle various return values here. At this point, I am not sure how to respond to various failures */
}
