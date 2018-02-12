<?php

require_once('include/csp.php');
require_once('include/inisets.php');
require_once('include/secrets.php');
require_once('include/initializeDb.php');
require_once('include/utility_functions.php');

/* There are basically 6 possible flows to this page */
/* #1 Cold session, no incoming data, no data to retrieve, just show defaults on page */
/* #2 Insert attempted, validation errors found, display form with data populated from POST, along with error message */
/* #3 Insert attempted, no errors, insert successful, display form with data populated from DB, along with SUCCESS msg */
/* #4 Update attempted, validation errors found, display form with data populated from POST, along with error message */
/* #5 Update attempted, no errors, update successful, display form with data populated from DB, along with SUCCESS msg */ 
/* #6 Retrieve only, display form with data populated from DB */

/* TODO: This code may be vulnerable to XSS 
and probably a bunch of other attacks
Needs serious security review */

my_session_start();


try
{
	initializeDb();
	
	if (isset($_POST["action"]))
	{
		/* Flow #2, #3, #4, or #5 */
		checkCsrfToken();
		
		if (!validatePostData())
		{
			/* #2 or #4 */
			displayPostData();
		} elseif ($_POST["action"] != "U") /* adding a new user */
		{
			/* #3 */
			performInsert();
			displayDbData();
		}
		elseif ($_POST["action"] == "U") /* updating a user */
		{
			/* #5 */
			performUpdate();
			displayDbData();
		}
	}
	elseif (isset($_REQUEST["user_id"]))
	{
		/* #6 */
		$user_id = FILTER_VAR($_REQUEST["user_id"], FILTER_VALIDATE_INT);
		
		if (!array_key_exists("my_user_id", $_SESSION))
		{
			throw new Exception("USER_NOT_LOGGED_IN_ERROR");
		}

		
		displayDbData();
	}
	else
	{
		/* #1 */
		/* Build default screen data */
        $highlight_tab = "NEWUSER";
		$user_id = 0;
		$person_name = "";
		$email = "";
		$email_is_verified = TRUE;
   	    $action = "I"; /* This means inserting user only */
		$active_ind = TRUE;
        $admin_user_ind = FALSE;
        
		if (!array_key_exists("my_user_id", $_SESSION))
		{
			throw new Exception("USER_NOT_LOGGED_IN_ERROR");
		}

	}
	buildCsrfToken();

}
catch (Exception $e)
{
	
	switch ($e->getMessage())
	{
	    case "DUPLICATE_EMAIL_ERROR" : 
	       $email_msg = "The email address entered was a duplicate.";
	    break;
	    case "USER_NOT_LOGGED_IN_ERROR":
	       header("Location: login.php?errmsg=USER_NOT_LOGGED_IN_ERROR");
	       exit();
        break;
	    default:
	       header("Location: login.php?errmsg=true");
	       exit();
	    
	}

	displayPostData();
    buildCsrfToken();
    
}

/* end of global section, now fall through to HTML */

function buildCsrfToken()
{
    /* for csrf protection, the nonce will be formed from a hash of several variables 
        that make up the session, concatenated, but should be stable between requests,
        along with some random salt (defined above) */
    global $csrf_nonce, $csrf_salt, $action, $user_id, $csrf_expdate;
	$csrf_expdate = new DateTime(NULL, new DateTimeZone("UTC"));
	$csrf_expdate->add(new DateInterval("PT4H")); /* CSRF token expires in 4 hours */
    $token = $user_id . $action . $_SERVER['SERVER_SIGNATURE'] . $_SERVER['SCRIPT_FILENAME'] . $csrf_expdate->format('U') . session_id() . $csrf_salt;
    //echo "<!-- DEBUG token = $token -->\n";
    $csrf_nonce = hash("sha256", $token);
}


function checkCsrfToken()
{
    global $csrf_salt;

	if (!array_key_exists("csrf_expdate", $_POST) || !array_key_exists("nonce", $_POST))
	{
		error_log("POST parameters missing in org.php. Possible tampering detected.");
		throw new Exception("An unknown error occurred (1). Please attempt to authenticate again.");
		exit(); /* this should not be run, but just in case, we do not want to continue */
	}
	

    $token = $_POST["user_id"] . $_POST["action"] . $_SERVER['SERVER_SIGNATURE'] . $_SERVER['SCRIPT_FILENAME'] .  $_POST['csrf_expdate'] . session_id() . 			$csrf_salt;
	
	//echo "<!-- DEBUG Check Token = $token -->\n";
    if (hash("sha256", $token) != $_POST["nonce"])
    {
		error_log("csrf token mismatch in org.php. Possible tampering detected.");
		throw new Exception("An unknown error occurred (2). Please attempt to authenticate again.");
		exit(); /* this should not be run, but just in case, we do not want to continue */
    }

	$dateint = filter_var($_POST["csrf_expdate"], FILTER_VALIDATE_INT);
	
	if ($dateint == FALSE)
	{
		error_log("expdate does not follow proper format in org.php. Possible tampering detected.");
		throw new Exception("An unknown error occurred (3). Please attempt to authenticate again.");
		exit(); /* this should not be run, but just in case, we do not want to continue */
	}
	

	$expdate = new DateTime("@" . $dateint, new DateTimeZone("UTC")); /* specify the @ sign to denote passing in a Unix TS integer */
	$today = new DateTime(NULL, new DateTimeZone("UTC"));
		
	if ($expdate < $today)
	{
		error_log("CSRF token expired in org.php");
		throw new Exception("An unknown error occurred (4). Please attempt to authenticate again.");
		exit(); /* this should not be run, but just in case, we do not want to continue */
		
	}

}


function validatePostData()
{
	if (!isset($_REQUEST["user_id"]) || !isset($_POST["email"]) || !isset($_POST["person_name"]) 
		|| !isset($_POST["password1"]) || !isset($_POST["password2"]))
	{
        error_log("Parameter tampering detected (validatePostData) Something not posted.");
        throw new Exception(PARAMETER_TAMPERING);
        exit();		
	}
		
    global $email_msg, $user_id, $pwd_msg, $action;
    $user_id = FILTER_VAR($_REQUEST["user_id"], FILTER_VALIDATE_INT);
	$action = substr(strtoupper($_POST["action"]), 0, 1); /* trim action to 1 character */
	
    if (!($user_id >= 0))
    {
        error_log("Parameter tampering detected (validatePostData) orgid.");
        throw new Exception("Parameter tampering detected (validatePostData) orgid.");
        exit();
    }


    /* first do basic validations before accessing the database */
	$email = $_POST["email"]; 
	// check if e-mail address is well-formed

	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
	{
		$email_msg = "The email address does not appear to follow the proper form.";
		return false;
	}

	if (strlen($email) > 128)
	{
		$email_msg = "Email address should not exceed 128 characters in length.";
		return false;
	}

	if (strlen($email) < 1) 
	{
		$email_msg = "A valid email address is required.";
		return false;
	}

	$pwd = $_POST["password1"];
		
	if ($_POST["action"] == "U" && !array_key_exists("reset", $_GET) && strlen($pwd) < 1)
	{
		/* not specified and not required, so nothing to validate */
		return true;
	}

    /* check to make sure a password was specified if this is first time */
	/* or if the reset parameter was passed */
	/* on insert, the user must specify a password */
	if ((($_POST["action"] == "I") || array_key_exists("reset", $_GET)) && strlen($pwd) < 1)
	{
		$pwd_msg = "Password is required in order to continue.";
		return false;		
	}
	
	if ($pwd != $_POST["password2"])
	{
		$pwd_msg = "Passwords must match.";
		return false;
	}
	
	if (strlen($pwd) > 128)
	{
		$pwd_msg = "Password exceeds the maximum length of 128 characters.";
		return false;
	}
	
	if (strlen($pwd) < 8)
	{
		$pwd_msg = "The password must be a minimum length of 8 characters.";
		return false;
	}

	/* check complexity requirements */
	$hasUpperCase = ($pwd != strtolower($pwd) ? 1 : 0); 
	$hasLowerCase = ($pwd != strtoupper($pwd) ? 1 : 0); 
	$hasNumbers = preg_match('/[0-9]/', $pwd);
	$hasNonalphas = preg_match('/\W/', $pwd);

	if ($hasUpperCase + $hasLowerCase + $hasNumbers + $hasNonalphas < 3)
    {
        $pwd_msg = "The password does not meet the complexity rules.";
        return false;        
    }
	
	
	//echo "<!-- validatePost passed -->\n";
    return true;
}




function performUpdate()
{
    try
    {
        /* this is an update, so must do a save */
        global $dbh, $user_id;

        $user_id = filter_var($_POST["user_id"], FILTER_VALIDATE_INT);
        
        if ($user_id != $_SESSION["my_user_id"] && $_SESSION["admin_user_ind"] == FALSE)
        {
            error_log("Unauthorized user ID passed. Possible parameter tampering.");
			throw new Exception("Unauthorized user ID. Possible parameter tampering.");
			exit();
        }

        $stmt = $dbh->prepare("CALL updateUser(:user_id, :person_name, :email, :pwhash, :active_ind, :admin_user_ind); ");
		
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

		$stmt->bindValue(':person_name', filter_var($_POST["person_name"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
            FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK), PDO::PARAM_STR);

        $stmt->bindValue(':email', filter_var(strtolower($_POST["email"]), FILTER_SANITIZE_EMAIL), PDO::PARAM_STR);
		
        /* udpate password if a new one specified */
        if (strlen($_POST["password1"]) > 0)
        {
            $pwhash = password_hash($_POST["password1"], PASSWORD_BCRYPT);
        }
        else
        {
            $pwhash = null;
        }
 
        $stmt->bindValue(':pwhash', $pwhash, PDO::PARAM_STR);
        

        $admin_user_ind = checkPostForCheckbox("admin_user_ind");
        $active_ind = checkPostForCheckbox("active_ind");
        $stmt->bindValue(':active_ind', $active_ind, PDO::PARAM_INT);

        /* If user is an admin, set the values to administration parameters, otherwise, set those to NULL */
        
        if ($_SESSION["admin_user_ind"] == TRUE)
        {
            $stmt->bindValue(':admin_user_ind', $admin_user_ind, PDO::PARAM_INT);
        }
        else /* the user is editing their own profile */
        {
            $stmt->bindValue(':admin_user_ind', NULL, PDO::PARAM_INT); /* this line is key, otherwise users will be able to make themselves admin :-) */
        }
        
	    $stmt->execute();

		global $success_msg;
		$success_msg = "Record successfully updated.";
		
        $stmt->closeCursor();

    }
    catch (PDOException $e)
    {
        if ($e->getCode() == "23000") /* integrity constraint violation, so I want to present a user friendly error message */
        {
            if (strpos($e->getMessage(), "for key 'ix_app_user_email_unique'") !== FALSE)
            {
                throw new Exception("DUPLICATE_EMAIL_ERROR");
            }
        }
        else 
        {
            error_log("Database error during UPDATE query in org.php: " . $e->getMessage());
            throw new Exception("An unknown error was encountered (11). Please attempt to reauthenticate.");
    		exit();
        }
    }
    catch(Exception $e)
    {
        error_log("Error during database UPDATE query in org.php: " . $e->getMessage());
		/* We most likely got here from the SQL error above, so just bubble up the exception */
        throw new Exception("An unknown error was encountered (12). Please attempt to reauthenticate.");
		exit();
    }

}


function performInsert()
{

    try
    {
        /* TODO: first check to see if a record under this email address already exists? */

        /* Shouldn't happen, but double-check authorization to create users */
        if ($_SESSION["admin_user_ind"] == FALSE)
        {
            throw new Exception("Insufficient authorization to perform the requested action.");
        }
        
        /* this is a new record, so do the insert */
        global $dbh, $action, $success_msg, $email, $user_id;
        
        $stmt = $dbh->prepare("CALL insertUser(:person_name, :email, :pwhash, :active_ind, :admin_user_ind); ");

		$stmt->bindValue(':person_name', filter_var($_POST["person_name"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
            FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK), PDO::PARAM_STR);

        $stmt->bindValue(':email', filter_var(strtolower($_POST["email"]), FILTER_SANITIZE_EMAIL), PDO::PARAM_STR);
		
        $pwhash = password_hash($_POST["password1"], PASSWORD_BCRYPT);
        $stmt->bindValue(':pwhash', $pwhash, PDO::PARAM_STR);
       

        $admin_user_ind = checkPostForCheckbox("admin_user_ind");
        $active_ind = checkPostForCheckbox("active_ind");

        
        $stmt->bindValue(':active_ind', $active_ind, PDO::PARAM_INT); 
        $stmt->bindValue(':admin_user_ind', $admin_user_ind, PDO::PARAM_INT);
        
        $stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            $erinf = $stmt->errorInfo();
			error_log("INSERT failed in org.php: " . $stmt->errorCode() . " " . $erinf[2]);
			throw new Exception("An unknown error was encountered (13). Please attempt to reauthenticate.");
            exit();
        }
		else
		{
			$success_msg = "Record successfully inserted.";
		}
		
		$row = $stmt->fetch(PDO::FETCH_ASSOC); /* insert ID is returned as a row set from the SP */
		
        if ($row == FALSE) 
        {
			error_log("Failed to get the inserted ID# in org.php");
			throw new Exception("An unknown error was encountered (14). Please attempt to reauthenticate.");
			exit();
        }

		$user_id = $row["user_id"];

        $stmt->closeCursor();
		
        /* change the action to update, now that the record was successfully inserted */
        $action = "U";

    }
    catch (PDOException $e)
    {
        if ($e->getCode() == "23000") /* integrity constraint violation, so I want to present a user friendly error message */
        {
            if (strpos($e->getMessage(), "for key 'ix_app_user_email_unique'") != FALSE)
            {
                throw new Exception("DUPLICATE_EMAIL_ERROR");
            }
        }
        else 
        {
            error_log("Database error during INSERT query in org.php: " . $e->getMessage());
            throw new Exception("An unknown error was encountered (15). Please attempt to reauthenticate.");
    		exit();
    	}
    }
    catch(Exception $e)
    {
        error_log("Error during database INSERT query in org.php: " . $e->getMessage());
		/* We most likely got here from the SQL error above, so just bubble up the exception */
        throw new Exception("An unknown error was encountered (16). Please attempt to reauthenticate.");
		exit();
    }
    

}


function displayDbData()
{

    try {

        global $dbh, $user_id, $highlight_tab;
        
		if ($_REQUEST['user_id'] == $_SESSION['my_user_id'])
		{
		    $highlight_tab = "PROFILE";
		}
		else 
		{
		    $highlight_tab = "USERS";
		}
		
        /* make sure orgid from session matches org ID requested */
        if ($user_id != $_SESSION["my_user_id"] && $_SESSION["admin_user_ind"] == FALSE)
        {
            error_log("Unauthorized User ID requested. Possible parameter tampering.");
			throw new Exception("Unauthorized User ID requested. Possible parameter tampering.");
			exit();
        }

        $stmt = $dbh->prepare("CALL selectUserInfo(:user_id);");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

        $stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            $erinf = $stmt->errorInfo();
			error_log("SELECT failed in org.php: " . $stmt->errorCode() . " " . $erinf[2]);
			throw new Exception("An unknown error was encountered (17). Please attempt to reauthenticate.");
            exit();
        }
		

        $row = $stmt->fetch(PDO::FETCH_ASSOC);


        if (isset($row))
        {
            global $user_id;
            global $person_name;

            global $email_is_verified;
            global $email;

            global $action;
            global $active_ind;
            global $admin_user_ind;
            
            $person_name = htmlspecialchars($row["person_name"]);

            $email_is_verified = $row["email_is_verified"];
            $email = htmlspecialchars($row["email"]);
            
            $active_ind = $row["active_ind"];
            $admin_user_ind = $row["admin_user_ind"];
            $action = "U";
            buildCsrfToken();
        }
        else
        {
			error_log("Failed to get the user record with that ID.");
			throw new Exception("An unknown error was encountered (18). Please attempt to reauthenticate.");
            exit();
        }
        $stmt->closeCursor();


        
    }
    catch (PDOException $e)
    {
        error_log("Database error during SELECT query in org.php: " . $e->getMessage());
        throw new Exception("An unknown error was encountered (20). Please attempt to reauthenticate.");
		exit();
    }
    catch(Exception $e)
    {
        error_log("Error during database SELECT query in org.php: " . $e->getMessage());
		/* We most likely got here from the SQL error above, so just bubble up the exception */
        throw new Exception("An unknown error was encountered (21). Please attempt to reauthenticate.");
		exit();
    }

}


function displayPostData()

{
    /* an error was encountered, so repopulate the fields from the POST */
    global $email; 
    global $email_is_verified;
    global $person_name;
    global $action; 
    global $active_ind;
    global $admin_user_ind;
    global $highlight_tab;

	if ($_REQUEST['user_id'] == $_SESSION['my_user_id'])
	{
	    $highlight_tab = "PROFILE";
	}
	else 
	{
	    $highlight_tab = "USERS";
	}

    $email = htmlspecialchars(filter_var($_POST["email"], FILTER_SANITIZE_EMAIL)); 
        /* TODO: don't know if the email is verified or not yet not sure how to handle this */
    $email_is_verified = 0;

    $person_name = htmlspecialchars(filter_var($_POST["person_name"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
            FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK));
    $admin_user_ind = checkPostForCheckbox("admin_user_ind");
    $active_ind = checkPostForCheckbox("active_ind");

    $action = strtoupper(substr($_POST["action"], 0, 1)); /* on error, always retain the same action that was posted */

    buildCsrfToken();
}




function buildEmailVerificationUrl()
{
	global $csrf_salt, $email, $user_id;
	//$user_id = $_SESSION["user_id"];
	
	/* use a hash in the URL to ensure the sendverifyemail.php isn't called maliciously */
	$link_expdate = new DateTime(NULL, new DateTimeZone("UTC"));
	$link_expdate->add(new DateInterval("PT4H")); /* the window to send the email expires in 4 hours */
	/* that should be fine since this is done almost completely programmatically */
	/* either when the record is first added, or when the button on page 1 is clicked */
	
	$input = $_SERVER["SERVER_NAME"] . $email . $user_id . 
		"sendverifyemail.php" . $link_expdate->format('U') . $csrf_salt;
	$token = hash("sha256", $input);

	//$url = sprintf("http://%s/mmatch/service/sendverifyemail.php?email=%s&token=%s&orgid=%d&date=%s", 
	//	$_SERVER["SERVER_NAME"], urlencode($email_unverified), $token, $orgid, $link_expdate->format('U'));
	$url = sprintf("service/sendverifyemail.php?email=%s&token=%s&user_id=%d&date=%s", 
		urlencode($email), $token, $user_id, $link_expdate->format('U'));
	return $url;
}



?>
<!DOCTYPE html>
<html lang="en" >
<head>
    <meta charset="UTF-8">
    <title>Movement Match - User Profile</title>
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
    <script src="js/user.js"></script>
    <link rel="stylesheet" href="css/style.css" >
    
</head>

<body>

<?php require('include/admin_nav_bar.php'); ?>


<div class="container-fluid">

<center>
<div class="page-header">
    <h2>User Profile</h2>
</div>
</center>

<form method="POST" action="user.php" id="user_save_form" autocomplete="off" >
<input type="hidden" id="nonce" name="nonce" value="<?php echo $csrf_nonce; ?>" />
<input type="hidden" id="csrf_expdate" name="csrf_expdate" value="<?php echo $csrf_expdate->format('U'); ?>" />
<input type="hidden" id="action" name="action" value="<?php echo $action; ?>" />
<input type="hidden" id="user_id" name="user_id" value="<?php echo $user_id ?>" />

    
    <div class="form-group">
        <label for="person_name">Name:</label>
        <input class="form-control" type="text" id="person_name" maxlength="128" name="person_name" value="<?php echo $person_name ?>"  required />
    </div> <!-- form-group -->

    <div class="alert alert-danger" hidden="true" id="person_name_msg" >
        The name must contain at least 4 characters.
    </div>

    <div class="form-group">
        <label for="email">Email address:</label>
        <input class="form-control" type="email" id="email" maxlength="255" name="email" value="<?php echo $email; ?>" required />

        <?php
            if ($email_is_verified == 0) { ?>
            <div class='alert alert-info' id='email_unverified_msg' >The email address: <?php echo $email; ?> has not been verified yet.
			<input type='button' id='generateVerificationEmail' value='Click here to request a new verification email.' form='' action='' /></div>
			<input type='hidden' id='generateVerficationEmailUrl' form='' action='' value="<?php 
                /* include the URL with the generated hash in a hidden field so that the URL is available to the AJAX/JS */
				echo buildEmailVerificationUrl(); ?>" />
        <?php } /*end if */ ?>
    </div> <!-- form-group -->
 
    <div class="alert alert-danger" <?php if (!isset($email_msg)) echo "hidden='true'"; ?> id="email_invalid_msg" >
        <?php if (isset($email_msg)) echo $email_msg; ?>
    </div>

   <div class="form-group">
        <label for="password1"><?php if ($action != "I") { echo "Update Password:"; } else { echo "Set Password:"; } ?></label>
        <input class="form-control" type="password" id="password1" maxlength="128" name="password1" value="" 
			<?php echo (($action == "I") || (array_key_exists("reset", $_GET)) ? "required" : ""); ?> />
    </div> <!-- form-group -->

   <div class="form-group">
        <label for="password2">Verify Password:</label>
        <input class="form-control" type="password" id="password2" maxlength="128" name="password2" value="" 
			<?php echo (($action == "I") || (array_key_exists("reset", $_GET)) ? "required" : ""); ?> />
    </div> <!-- form-group -->

    <div class="alert alert-danger" <?php if (!isset($pwd_msg)) echo "hidden='true'"; ?> id="pwd_msg" >
        <?php if (isset($pwd_msg)) echo $pwd_msg; ?>
    </div>

    <div class="form-group row">
        <div class="col-xs-5" for="admin_user_ind"><p><strong>Administrative User:</strong></p><p><small>Administrative users are allowed to create and modify other users.</small></p></div>
        <div class="col-xs-1" ><input class="" type="checkbox" id="admin_user_ind" name="admin_user_ind" 
            <?php echo ($admin_user_ind == TRUE ? "checked" : ""); echo ($_SESSION["admin_user_ind"] != TRUE ? "disabled readonly" : ""); ?> /></div>
    </div> <!-- form-group -->
	
    <div class="form-group row">
        <div class="col-xs-5" for="active_ind"><p><strong>Allow this user to log in:</strong></p></div>
        <div class="col-xs-1" ><input class="" type="checkbox" id="active_ind" name="active_ind" <?php echo ($active_ind == TRUE ? "checked" : ""); ?> /></div>
    </div> <!-- form-group -->


<button id="save_data" type="submit" class="btn btn-default btn-lg">Save data</button>



</form>

<?php require('include/footer.php'); ?>

<?php if (isset($success_msg))
{
	echo "<div class='alert alert-success alert-dismissable' id='general_alert_msg' >\n";
	echo "<a href='#' class='close' data-dismiss='alert' aria-label='close'>×</a>\n";
	echo "$success_msg\n</div>";
}
?>

</div> <!-- Container fluid -->

</body>
</html>

