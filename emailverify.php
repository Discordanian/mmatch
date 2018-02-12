<?php

require_once('include/csp.php');
require_once('include/inisets.php');
require_once('include/secrets.php');
require_once('include/initializeDb.php');

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
		$err_msg = "An unknown error (1) occurred trying to verify the email using that link. Please try to log in again and request another email.";
	}
}
catch(Exception $e)
{
	error_log("Error in top level error handler of emailverify.php: " . $e->getMessage());
	//header("HTTP/1.0 500 Server Error", true, 500);
	exit();
}


function testToken()
{
    global $user_id, $email, $csrf_salt, $err_msg;

    /* sanitize these values brought in before I do any processing based upon them */
	//echo "<!-- getemail = " . $_GET["email"] . " --> \n";

    $user_id = filter_var($_GET["user_id"], FILTER_VALIDATE_INT);
    $email = filter_var($_GET["email"], FILTER_SANITIZE_EMAIL);
	$dateint = filter_var($_GET["date"], FILTER_VALIDATE_INT);

	//echo "<!-- email = " . $email . " --> \n";

    if (($user_id <= 0) || (strlen($email) <= 0) || ($dateint == false))
    {
        $err_msg = "Unable to verify based upon the information provided (2). Please try to log in again and request another email.";
        throw new Exception("Unable to verify based upon the information provided (2). Please try to log in again and request another email");
        exit();
    }
    

	/* first checks to make sure the expiration date has not passed */
	$expdate = new DateTime("@" . $dateint, new DateTimeZone("UTC")); /* specify the @ sign to denote passing in a Unix TS integer */
	$today = new DateTime(NULL, new DateTimeZone("UTC"));
			
	if ($expdate < $today)
	{
		$err_msg = "The email verification link has expired. Please try to log in again and request another email.";
		throw new Exception("Date token expired in emailverify.php.");
		exit(); /* this should not be run, but just in case, we do not want to continue */
	
	}

     
    $datetext = $expdate->format("U");

    $input = $_SERVER["SERVER_NAME"] . urlencode($email) . $datetext . $user_id . "emailverify.php" . $csrf_salt;
    $token = substr(hash("sha256", $input), 0, 18); /* pull out only the 1st 18 digits of the hash */

	//echo "<!-- input = " . $input . " --> \n";
	//echo "<!-- token = " . $token . " --> \n";
	//echo "<!-- gettoken = " . $_GET["token"] . " --> \n";

    if ($token == $_GET["token"])
    {
	    initializeDb();
       verifyEmail();
    }
    else 
    {
    	/* */
        $err_msg = "Unable to verify based upon the information provided (3). Please try to log in again and request another email.";
        throw new Exception("Unable to verify based upon the information provided (3). Please try to log in again and request another email");
		exit(); /* this should not be run, but just in case, we do not want to continue */
    }


}




function verifyEmail()
{
    try
    {
        global $dbh, $user_id, $success_msg, $err_msg, $email;



        $stmt = $dbh->prepare("CALL verifyEmail(:user_id, :email);");

        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':user_id', $user_id);

	    $stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            $erinf = $stmt->errorInfo();
				error_log("UPDATE failed in emailverify.php: " . $stmt->errorCode() . " " . $erinf[2]);
            $err_msg = "An unknown error (4) occurred trying to verify the email using that link. Please try to log in again and request another email.";
            $success_msg = NULL;
				throw new Exception("UPDATE failed in emailverify.php");
            exit();
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
        error_log("Database error during UPDATE query in emailverify.php: " . $e->getMessage());
        $err_msg = "An unknown error (5) occurred trying to verify the email using that link. Please try to log in again and request another email.";
        $success_msg = NULL;
        throw new Exception("Database error during UPDATE query in emailverify.php");
		exit();
    }
    catch(Exception $e)
    {
        error_log("Error during UPDATE query in emailverify.php: " . $e->getMessage());
        $err_msg = "An unknown error (6) occurred trying to verify the email using that link. Please try to log in again and request another email.";
        $success_msg = NULL;
		/* We most likely got here from the SQL error above, so just bubble up the exception */
        throw new Exception("Error during UPDATE query in emailverify.php");
		exit();
    }

    
}


?>
<!DOCTYPE html>
<html lang="en" >
<head>
    <meta charset="UTF-8">
    <title>Movement Match - Organization</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

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
<!--    <script src="js/login.js"></script> -->
  
</head>

<body>
<?php require('include/unauth_nav_bar.php'); ?>
<div class="container-fluid">

<center>
<div class="page-header">
    <h2>Verify Email</h2>
</div>
</center>


<div class="alert alert-success" <?php if (!isset($success_msg)) echo "hidden='true'"; ?> id="success_msg" >
    <?php if (isset($success_msg)) echo $success_msg; ?>
</div>

  
<div class="alert alert-danger" <?php if (!isset($err_msg)) echo "hidden='true'"; ?> id="err_msg" >
    <?php if (isset($err_msg)) echo $err_msg; ?>
</div>

<?php require('include/footer.php'); ?>

</div> <!-- Container fluid -->


</body>
</html>

