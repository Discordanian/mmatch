<?php

require_once('../include/inisets.php');
require_once('../include/secrets.php');
require_once('../include/initializeDb.php');

/* This page is so that AJAX requests from the page */
/* Can asynchronously trigger the verification email to be sent */

/* TODO: Needs serious security review */

try 
{
	
	if (isset($_GET["email"]) && isset($_GET["token"]) && isset($_GET["orgid"]) && isset($_GET["date"]))
	{
		checkValidRequest();
		initializeDb();
		lookupEmail();
		sendVerificationEmail();
	}
	else
	{
		throw new Exception("Insufficient parameters were passed into sendverifyemail.php");
	}

	
}
catch(Exception $e)
{
	error_log("Error in top level error handler of sendverifyemail.php: " . $e->getMessage());
	header("HTTP/1.0 500 Server Error", true, 500);
	exit();
}

function checkValidRequest()
{
	global $csrf_salt, $orgid, $email;

    $orgid = filter_var($_GET["orgid"], FILTER_VALIDATE_INT);
    $email = filter_var($_GET["email"], FILTER_SANITIZE_EMAIL);
	$dateint = filter_var($_GET["date"], FILTER_VALIDATE_INT);
	
	//echo "<!-- email = " . $email . " -->\n";
	
	if ($dateint == false) 
	{
		error_log("Encountered an invalid date int as part of the request to sendverifyemail.php.");
		throw new Exception("Encountered an invalid date int as part of the request to sendverifyemail.php.");
		exit();
	}
	/* first checks to make sure the expiration date has not passed */
	$expdate = new DateTime("@" . $dateint, new DateTimeZone("UTC")); /* specify the @ sign to denote passing in a Unix TS integer */
	$today = new DateTime(NULL, new DateTimeZone("UTC"));
		
	if ($expdate < $today)
	{
		error_log("Date token expired in sendverifyemail.php");
		throw new Exception("Date token expired in sendverifyemail.php. The user will need to try again.");
		exit(); /* this should not be run, but just in case, we do not want to continue */
		
	}
	
	/* since this is not very security critical use case, (it just sends an email */
	/* perform basic verification that */
	/* the request came from a valid source and not a random person */

	$input = $_SERVER["SERVER_NAME"] . $_GET["email"] . $_GET["orgid"] . 
		"sendverifyemail.php" . $dateint . $csrf_salt;
	$token = hash("sha256", $input);
	/* verify that the token that we calculate equals the token that was passed in */
	/* they could only be equal for a process that knows the secret salt value */
	if ($token != $_GET["token"])
	{
		error_log("Possible parameter tampering encountered in sendverifyemail.php");
		throw new Exception("Token validation failed. Possible parameter tampering in sendverifyemail.php.");
		exit();
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
            $erinf = $stmt->errorInfo();
			error_log("SELECT failed in sendverifyemail.php: " . $stmt->errorCode() . " " . $erinf[2]);
			throw new Exception("SELECT encountered an error in sendverifyemail.php");
            exit();
        }
		

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if (!isset($row))
		{
			error_log("Failed to look up valid email and orgid combination in sendverifyemail.php.");
			throw new Exception("Failed to look up valid email and orgid combination in sendverifyemail.php");
			exit();
		}

		
		$stmt->closeCursor();
    }
    catch (PDOException $e)
    {
        error_log("Database error during SELECT query in sendverifyemail.php: " . $e->getMessage());
        throw new Exception("Database error during SELECT query in sendverifyemail.php");
		exit();
    }
    catch(Exception $e)
    {
        error_log("Error during SELECT query in sendverifyemail.php: " . $e->getMessage());
		/* We most likely got here from the SQL error above, so just bubble up the exception */
        throw new Exception("Error during SELECT query in sendverifyemail.php");
		exit();
    }
}


function sendVerificationEmail()
{
    global $email, $csrf_salt, $orgid;

    /* calculate the date 3 days into the future */
    /* TODO: use different time zones depending upon the locality of the organization ? */
    /* Links effectively expire at midnight */
    /* Or just use eastern time for everything, or UTC/GMT */
    $expdate = new DateTime(NULL, new DateTimeZone("UTC"));
    $din = new DateInterval("P3D"); /* email verification link expires in 3 days */
    $expdate->add($din);
    /* TODO: make expiration date of link parameter driven */
    $datetext = $expdate->format("U");

    $input = $_SERVER["SERVER_NAME"] . urlencode($email) . $datetext . $orgid . "emailverify.php" . $csrf_salt;
    $token = substr(hash("sha256", $input), 0, 18); /* pull out only the 1st 18 digits of the hash, make it a typable link? */
    $link = sprintf("http://%s/mmatch/emailverify.php?email=%s&token=%s&orgid=%d&date=%s", $_SERVER["SERVER_NAME"], urlencode($email), $token, $orgid, $datetext);

	//echo "<!-- $input -->\n"; /* TODO: This is a cheat and a security vulnerability. Remove it */
	echo "<!-- $link -->\n"; /* TODO: This is a cheat so I don't have to actually send/receive the email. Remove this eventually */
	
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
