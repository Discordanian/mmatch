<?php

require_once('include/inisets.php');
require_once('include/secrets.php');
require_once('include/initializeDb.php');

/* #1 Cold session, no incoming data, no data to retrieve, clear all cookies, and show defaults on page */
/* #2 Credentials entered, authentication fails, redisplay blank form, along with error message */
/* #3 Credentials entered, authentication succeeds, setup session, redirect to org.php */

/* TODO: This code may be vulnerable to XSS 
and probably a bunch of other attacks
Needs serious security review */



try
{
	buildCsrfToken(); /* We need this built in most cases, so go ahead and build it */

	if (isset($_POST["password"])) /* credentials were submitted */
	{
		checkCsrfToken();
		
		if (validatePostData())
		{
			if (authenticateCredentials())
			{
				/* flow #3 */
				redirectToPage();
			}
			else
			{
				/* set the auth error message */
				$auth_fail_msg = "Those credentials are not valid. Please try again.";
			}
		}

	}

	if ((!isset($_POST["password"])) || (isset($auth_fail_msg)))

	{
		/* Flow #1, or #2, display blank form */

		clearSession();

		if (isset($_GET["errmsg"]))
		{
		    switch ($_GET["errmsg"])
		    {
		        /* TODO: make these codes more organized. Right now they are just random numbers and
		        there's not much rhyme or reason as to what means what */
		        case "8" : $auth_fail_msg = "Due to inactivity you have been logged off. Please log on again";
		          break;
		        default : $auth_fail_msg = "An unknown error occurred. Please attempt to log on again";
		    }
		}
	}
}
catch (Exception $e)
{
	$auth_fail_msg = $e->getMessage();
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
    //echo "<!-- DEBUG build token = $token -->\n";
    $csrf_nonce = hash("sha256", $token);
}


function checkCsrfToken()
{
    global $csrf_salt;

	if (!array_key_exists("csrf_expdate", $_POST) || !array_key_exists("nonce", $_POST))
	{
		error_log("POST parameters missing in login.php. Possible tampering detected.");
		throw new Exception("An unknown error occurred (1). Please attempt to authenticate again.");
		exit(); /* this should not be run, but just in case, we do not want to continue */
	}
	
    $token = $_SERVER['SERVER_SIGNATURE'] . $_SERVER['SCRIPT_FILENAME'] . $_POST['csrf_expdate'] . $csrf_salt;
	
	//echo "<!-- DEBUG Check Token = $token -->\n";
    if (hash("sha256", $token) != $_POST["nonce"])
    {
		error_log("csrf token mismatch in login.php. Possible tampering detected.");
		throw new Exception("An unknown error occurred (2). Please attempt to authenticate again.");
		exit(); /* this should not be run, but just in case, we do not want to continue */
    }
	
	$dateint = filter_var($_POST["csrf_expdate"], FILTER_VALIDATE_INT);
	
	if ($dateint == FALSE)
	{
		error_log("expdate does not follow proper format in login.php. Possible tampering detected.");
		throw new Exception("An unknown error occurred (3). Please attempt to authenticate again.");
		exit(); /* this should not be run, but just in case, we do not want to continue */
	}
	

	$expdate = new DateTime("@" . $dateint, new DateTimeZone("UTC")); /* specify the @ sign to denote passing in a Unix TS integer */
	$today = new DateTime(NULL, new DateTimeZone("UTC"));
		
	if ($expdate < $today)
	{
		error_log("CSRF token expired in login.php");
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




function authenticateCredentials()
{
	global $dbh, $orgid, $email, $pwhash;

	initializeDb(); /* I want this outside the try since it has its own exception handler, which I want bubbled up */

    try
    {

        $stmt = $dbh->prepare("CALL selectLoginInfo(:email);" );
        $stmt->bindParam(':email', $email);
		
		//echo "<!-- ";
		//$stmt->debugDumpParams();
		//echo " -->\n";
		
	    $stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            $erinf = $stmt->errorInfo();
			error_log("SELECT failed: " . $stmt->errorCode() . " " . $erinf[2]);
			throw new Exception("An unknown error was encountered (8). Please attempt to reauthenticate.");
            exit();
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);


        if (isset($row))
		{
            $pwhash = $row["pwhash"];


            if (password_verify($_POST["password"], $pwhash))
            {
                $orgid = $row["orgid"];
                //$email = $row["email_verified"];
                return filter_var($orgid, FILTER_VALIDATE_INT); /* just double check the org ID is a valid int */
            }
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

function clearSession()
{
    /* The following was copied from php.net */
    /* The goal is to clear all cookies when the login page is shown
        which mitigates against some session hijacking and fixation threats */
    // Initialize the session.
    // If you are using session_name("something"), don't forget it now!
    session_start();

    // Unset all of the session variables. (server side)
    $_SESSION = array();

    // If it's desired to kill the session, also delete the session cookie.
    // Note: This will destroy the session, and not just the session data!
    if (ini_get("session.use_cookies")) 
    {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finally, destroy the session.
    session_destroy();
}


function redirectToPage()
{
    session_start();
    global $orgid;
    $_SESSION["orgid"] = $orgid;
	header("Location: org.php?orgid=$orgid");
    
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
    <h2>Organization Login</h2>
</div>
</center>

<form method="POST" action="login.php" id="login_form" >
<input type="hidden" id="nonce" name="nonce" value="<?php echo $csrf_nonce; ?>" />
<input type="hidden" id="csrf_expdate" name="csrf_expdate" value="<?php echo $csrf_expdate->format('U'); ?>" />


    <div class="form-group">
        <label for="email">Email address:</label>
        <input class="form-control" type="email" id="email" maxlength="128" name="email" value="" />
    </div> <!-- form-group -->

   <div class="form-group">
        <label for="password">Password:</label>
        <input class="form-control" type="password" id="password" maxlength="128" name="password" value="" />
    </div> <!-- form-group -->

  
    <div class="alert alert-danger" <?php if (!isset($auth_fail_msg)) echo "hidden='true'"; ?> id="auth_fail_msg" >
        <?php if (isset($auth_fail_msg)) echo $auth_fail_msg; ?>
    </div>

    <button type="submit" class="btn btn-default btn-lg" id="submit">Submit</button>


</form>

</div> <!-- Container fluid -->


</body>
</html>

