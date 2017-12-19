<?php

require_once('include/inisets.php');
require_once('include/secrets.php');
require_once('include/initializeDb.php');

define("ERROR_TOKEN_EXPIRED", "Date token expired in passwordReset.php.");

/* Only one way this page goes, take input from GET request, validate */
/* If valid, then switch email to verified */
/* Otherwise, display an error message */

/* TODO: This code may be vulnerable to XSS 
and probably a bunch of other attacks
Needs serious security review */

try
{
	if (isset($_GET["email"]) && isset($_GET["token"]) && isset($_GET["user_id"]) && isset($_GET["date"]))
	{
		testToken();
	}
	else
	{
		$err_msg = "An unknown error (1) occurred trying to verify the email using that link. Please try to request another email link.";
	}
}
catch(Exception $e)
{
	if ($e->getMessage() == ERROR_TOKEN_EXPIRED)
	{
		$err_msg = "The email link appears to have expired. Try requesting another one.";
	}
	else
	{
		$err_msg = "An unknown error (1) occurred trying to verify the email using that link. Please try to request another email link.";
	}
}


function testToken()
{
    global $user_id, $email, $csrf_salt, $err_msg;

    /* sanitize these values brought in before I do any processing based upon them */
	//echo "<!-- getemail = " . $_GET["email"] . " --> \n";

    $user_id = filter_var($_GET["user_id"], FILTER_VALIDATE_INT);
    $email = filter_var($_GET["email"], FILTER_SANITIZE_EMAIL);
	$dateint = filter_var($_GET["date"], FILTER_VALIDATE_INT);

	/* echo "<!-- email = " . $email . " --> \n"; */
	/* echo "<!-- user_id = " . $user_id . " --> \n"; */
	/* echo "<!-- dateint = " . $dateint . " --> \n"; */

    if (($user_id <= 0) || (strlen($email) <= 0) || ($dateint == false))
    {
        $err_msg = "Unable to verify based upon the information provided (2). Please try to request another email.";
        throw new Exception("Unable to verify based upon the information provided (2). Please try to request another email");
        exit();
    }
    

	/* first checks to make sure the expiration date has not passed */
	$expdate = new DateTime("@" . $dateint, new DateTimeZone("UTC")); /* specify the @ sign to denote passing in a Unix TS integer */
	$today = new DateTime(NULL, new DateTimeZone("UTC"));
			
	if ($expdate < $today)
	{
		throw new Exception(ERROR_TOKEN_EXPIRED);
		exit(); /* this should not be run, but just in case, we do not want to continue */
	
	}

     
    $datetext = $expdate->format("U");

    $input = $_SERVER["SERVER_NAME"] . urlencode($email) . $datetext . $user_id . "passwordReset.php" . $csrf_salt;
    $token = substr(hash("sha256", $input), 0, 22); /* pull out only the 1st 22 digits of the hash */

	/* echo "<!-- input = " . $input . " --> \n"; */
	/* echo "<!-- token = " . $token . " --> \n"; */
	/* echo "<!-- gettoken = " . $_GET["token"] . " --> \n"; */

    if ($token == $_GET["token"])
    {
	    initializeDb();
       authenticate();
    }
    else 
    {
    	/* */
        $err_msg = "Unable to verify based upon the information provided (3). Please try to request another email link.";
        throw new Exception("Unable to verify based upon the information provided (3). Please try to request another email link.");
		exit(); /* this should not be run, but just in case, we do not want to continue */
    }


}




function authenticate()
{
    try
    {
        global $dbh, $user_id, $err_msg, $email;



        $stmt = $dbh->prepare("CALL selectLoginInfo(:email);");

        $stmt->bindValue(':email', $email);
	    $stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            $erinf = $stmt->errorInfo();
				error_log("Query failed in passwordReset.php: " . $stmt->errorCode() . " " . $erinf[2]);
            $err_msg = "An unknown error (4) occurred trying to verify the email using that link. Please try to request another email link.";
			throw new Exception("Query failed in passwordReset.php");
            exit();
        }

        /* Almost the only thing we can do here is to check to make sure the user_id passed in matches the user ID from the DB */
		if ($results = $stmt->fetchAll(PDO::FETCH_ASSOC))
		{
			if (count($results) > 0)
			{

				if ($user_id == $results[0]["user_id"])
				{
					/* set up the session just like the user had logged in */
					$orgs = array_column($results, "orgid");
					session_start();
					$_SESSION["user_id"] = $user_id;
					$_SESSION["orgids"] = $orgs;
				}
			}
		}
		
		
        $stmt->closeCursor();


    }
    catch (PDOException $e)
    {
        error_log("Database error during query in passwordReset.php: " . $e->getMessage());
        $err_msg = "An unknown error (5) occurred trying to verify the email using that link. Please try to request another email.";
        throw new Exception("Database error during query in passwordReset.php");
		exit();
    }
    catch(Exception $e)
    {
        error_log("Error during query in passwordReset.php: " . $e->getMessage());
        $err_msg = "An unknown error (6) occurred trying to verify the email using that link. Please try to request another email.";
		/* We most likely got here from the SQL error above, so just bubble up the exception */
        throw new Exception("Error during query in passwordReset.php");
		exit();
    }

    
}


?>
<!DOCTYPE html>
<html lang="en" >
<head>
    <meta charset="UTF-8">
    <title>Movement Match - Password reset</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
<?php 
	if (!isset($err_msg)) 
	{
		printf("\t<meta http-equiv='REFRESH' content='5;URL=orgList.php?user_id=%d' >\n", $user_id);
	}
?>
	<link rel="stylesheet prefetch" 
		href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css" 
		integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" 
		crossorigin="anonymous">    
	<link rel="stylesheet" href="css/style.css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js" 
		integrity="sha384-nrOSfDHtoPMzJHjVTdCopGqIqeYETSXhZDFyniQ8ZHcVy08QesyHcnOUpMpqnmWq" 
		crossorigin="anonymous"></script>        
	<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js" 
		integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" 
		crossorigin="anonymous"></script>
  
</head>

<body>

<div class="container-fluid">

<center>
<div class="page-header">
    <h1>Movement Match</h1>
    <h2>Reset Password</h2>
</div>
</center>


<div class="alert alert-success" <?php if (isset($err_msg)) echo "hidden='true'"; ?> id="success_msg" >
    You successfully logged in using the forgotten password email. 
	You will now be redirected to the application where you can set your password.
</div>

  
<div class="alert alert-danger" <?php if (!isset($err_msg)) echo "hidden='true'"; ?> id="err_msg" >
    <?php if (isset($err_msg)) echo $err_msg; ?>
</div>


</div> <!-- Container fluid -->


</body>
</html>

