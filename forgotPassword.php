<?php

require_once('include/csp.php');
require_once('include/inisets.php');
require_once('include/secrets.php');
require_once('include/initializeDb.php');

/* #1 Cold session, no incoming data, no data to retrieve, clear all cookies, and show defaults on page */
/* #2 Email entered, email not found, log and display error message */
/* #3 Email entered, email found, send email and display confirmation message */

/* TODO: This code may be vulnerable to XSS 
and probably a bunch of other attacks
Needs serious security review */



try
{
	buildCsrfToken(); /* We need this built in most cases, so go ahead and build it */

	if (isset($_POST["email"])) /* email address submitted */
	{
		checkCsrfToken();
		
		if (validatePostData())
		{
			if ($user_id = verifyEmail())
			{
				/* flow #3 */
				sendPasswordResetLink($user_id);
    		}
			else
			{
				/* set the auth error message */
				$fail_msg = "That email address was not found.";
			}
		}

	}

}
catch (Exception $e)
{
	$fail_msg = $e->getMessage();
}


/* end of global section, now fall through to HTML */

function buildCsrfToken()
{
    /* for csrf protection, the nonce will be formed from a hash of several variables
        that make up the session, concatenated, but should be stable between requests,
        along with an expiration date and some random salt (defined in secrets.php), 
		which makes the hash impossible to predict for anyone who does not know the salt */
    global $csrf_nonce, $csrf_salt, $csrf_expdate;
	
	$csrf_expdate = new DateTime(NULL, new DateTimeZone("UTC"));
	$csrf_expdate->add(new DateInterval("PT30M")); /* CSRF token expires in 30 minutes */
    $token = $_SERVER['SERVER_SIGNATURE'] . $_SERVER['SCRIPT_FILENAME'] . $csrf_expdate->format('U') . $csrf_salt;
    $csrf_nonce = hash("sha256", $token);
}


function checkCsrfToken()
{
    global $csrf_salt;

	if (!array_key_exists("csrf_expdate", $_POST) || !array_key_exists("nonce", $_POST))
	{
		error_log("POST parameters missing in forgotPassword.php. Possible tampering detected.");
		throw new Exception("An unknown error occurred (1). Please attempt to authenticate again.");
		exit(); /* this should not be run, but just in case, we do not want to continue */
	}
	
    $token = $_SERVER['SERVER_SIGNATURE'] . $_SERVER['SCRIPT_FILENAME'] . $_POST['csrf_expdate'] . $csrf_salt;
	
	//echo "<!-- DEBUG Check Token = $token -->\n";
    if (hash("sha256", $token) != $_POST["nonce"])
    {
		error_log("csrf token mismatch in forgotPassword.php. Possible tampering detected.");
		throw new Exception("An unknown error occurred (2). Please attempt to authenticate again.");
		exit(); /* this should not be run, but just in case, we do not want to continue */
    }
	
	$dateint = filter_var($_POST["csrf_expdate"], FILTER_VALIDATE_INT);
	
	if ($dateint == FALSE)
	{
		error_log("expdate does not follow proper format in forgotPassword.php. Possible tampering detected.");
		throw new Exception("An unknown error occurred (3). Please attempt to authenticate again.");
		exit(); /* this should not be run, but just in case, we do not want to continue */
	}
	

	$expdate = new DateTime("@" . $dateint, new DateTimeZone("UTC")); /* specify the @ sign to denote passing in a Unix TS integer */
	$today = new DateTime(NULL, new DateTimeZone("UTC"));
		
	if ($expdate < $today)
	{
		error_log("CSRF token expired in forgotPassword.php");
		throw new Exception("An unknown error occurred (4). Please attempt to authenticate again.");
		exit(); /* this should not be run, but just in case, we do not want to continue */
		
	}
	
}


function validatePostData()
{

    global $email;

    $email = strtolower($_POST["email"]);

    return filter_var($email, FILTER_VALIDATE_EMAIL);
}




function verifyEmail()
{
	global $dbh, $email, $user_id;

	initializeDb(); /* I want this outside the try since it has its own exception handler, which I want bubbled up */

    try
    {

        $stmt = $dbh->prepare("CALL selectLoginInfo(:email);" );
        $stmt->bindParam(':email', $email);
		
		
	    $stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            $erinf = $stmt->errorInfo();
			error_log("Query failed: " . $stmt->errorCode() . " " . $erinf[2]);
			throw new Exception("An unknown error was encountered (8). Please attempt to reauthenticate.");
            exit();
        }

        /* if any rows are returned at all, that means that the email is recognized, so proceed with sending the email */
		/* TODO: only send password resets to "verified" email addresses */
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			return $row["user_id"];
		}
		else
		{
			return false;
		}
		
 
    }
    catch (PDOException $e)
    {
        error_log("Database error during query: " . $e->getMessage());
        throw new Exception("An unknown error was encountered (7). Please attempt to reauthenticate.");
		exit();
    }
    catch(Exception $e)
    {
        error_log("Error during database query: " . $e->getMessage());
		/* We most likely got here from the SQL error above, so just bubble up the exception */
        throw new Exception("An unknown error was encountered (8). Please attempt to reauthenticate.");
		exit();
    }

}

function sendPasswordResetLink()
{
    global $email, $csrf_salt, $user_id, $success_msg, $fail_msg;

    /* calculate the date 1 hour into the future */
    /* TODO: use different time zones depending upon the locality of the user/organization ? */
    /* Or just use eastern time for everything, or UTC/GMT */
    $expdate = new DateTime(NULL, new DateTimeZone("UTC"));
    $din = new DateInterval("PT1H"); /* email verification link expires in 1 hour */
    $expdate->add($din);
    /* TODO: make expiration date of link parameter driven */
    $datetext = $expdate->format("U");

    $input = $_SERVER["SERVER_NAME"] . urlencode($email) . $datetext . $user_id . "passwordReset.php" . $csrf_salt;
    $token = substr(hash("sha256", $input), 0, 22); /* pull out only the 1st 22 digits of the hash, make it a typable link? */
	/* echo "<!-- input = " . $input . " --> \n"; This is a cheat and a security vulnerability */
	/* echo "<!-- token = " . $token . " --> \n"; This is a cheat and a security vulnerability */
	
    /* have to do some gymnastics here to make sure to get a nice fully qualified absolute URL */
    
    $path = str_replace("/forgotPassword.php", "/passwordReset.php", $_SERVER["PHP_SELF"]);

    $link = sprintf("%s://%s%s?email=%s&token=%s&user_id=%d&date=%s", 
        $_SERVER["REQUEST_SCHEME"], $_SERVER["HTTP_HOST"], $path, urlencode($email), $token, $user_id, $datetext);

	//echo "<!-- $link -->\n"; /* TODO: This is a cheat so I don't have to actually send/receive the email. Remove this eventually */
	/* This is another cheat in case I don't have the email. The link will be in the log which is only available to superusers */
    error_log("Password reset requested: " . $link);	
	
    $message = sprintf("You apparently requested for your password to be reset on the site movementmatch.org.\n" .
        "Click on the following link in order to verify that this is correct and reset your password: \n" .
        "\t%s\n" .
        "This link will expire within 1 hour. \n" .
        "If you did not initiate this reset, please ignore and delete this message. \n", $link);

	/* send the verification email */
    $res = mail($email, "Reset your account with movementmatch.org", $message, "From: admin@movementmatch.org");
	/* I think on CentOS or other SELinux enabled systems, this will not work until you run: #setsebool -P httpd_can_sendmail=1 */
	/* TODO: handle various return values here. At this point, I am not sure how to respond to various failures */
	
	if ($res == FALSE)
	{
		$fail_msg = "An unknown error was encountered while attempting to send the email.";
	}
	else
	{
		$success_msg = "A password reset email was sent to the email address entered. " .
			"Please check your email and navigate to the link supplied in the email in order to reset your password.";			
	}
		
	
}


?>
<!DOCTYPE html>
<html lang="en" >
<head>
    <meta charset="UTF-8">
    <title>Movement Match - Forgot Password</title>
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
    <script src="js/forgotPassword.js"></script>
  
</head>

<body>

<?php 
$highlight_tab = "FORGOT";
require('include/unauth_nav_bar.php'); ?>

<div class="container-fluid">

<center>
<div class="page-header">
    <h2>Forgot Password</h2>
</div>
</center>

<form method="POST" action="forgotPassword.php" id="login_form" autocomplete="off" >
<input type="hidden" id="nonce" name="nonce" value="<?php echo $csrf_nonce; ?>" />
<input type="hidden" id="csrf_expdate" name="csrf_expdate" value="<?php echo $csrf_expdate->format('U'); ?>" />


    <div class="form-group">
        <label for="email">Email address:</label>
        <input class="form-control" type="email" id="email" maxlength="255" name="email" value="" />
    </div> <!-- form-group -->

  
    <div class="alert alert-danger" <?php if (!isset($fail_msg)) echo "hidden='true'"; ?> id="fail_msg" >
        <?php if (isset($fail_msg)) echo $fail_msg; ?>
    </div>
	

	<div class="alert alert-success" <?php if (!isset($success_msg)) echo "hidden='true'"; ?> id="success_msg" >
		<?php if (isset($success_msg)) echo $success_msg; ?>
	</div>

    <button type="submit" class="btn btn-default btn-lg" id="submit">Submit</button>


</form>

<?php require('include/footer.php'); ?>

</div> <!-- Container fluid -->


</body>
</html>

