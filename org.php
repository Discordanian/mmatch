<?php

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
/* default section should be the org details page */
$goto_page = -1;


try
{
    $highlight_tab = "ORGS";

	initializeDb();
	
	if (isset($_POST["action"]))
	{
		/* Flow #2, #3, #4, or #5 */
		checkCsrfToken();
		
		if (!validatePostData())
		{
			/* #2 or #4 */
			buildEmptyArray();
			translatePostIntoArray();
			zipPostToArray();
			displayPostData();
		} elseif ($_POST["action"] != "U") /* adding a new org */
		{
			$goto_page = -1; /* show the identifier data instead of person data */
			/* #3 */
			updateUser();
			performInsert();
			buildEmptyArray();
			translatePostIntoArray();
			updateQuestionnaireData();
			zipPostToArray();
			zipArrayToDb();
			displayDbData();
			populateArray();
		}
		elseif ($_POST["action"] == "U") /* updating an org */
		{
			/* #5 */
			updateUser();
			performUpdate();
			//buildEmptyArray();
			populateArray();
			translatePostIntoArray();
			updateQuestionnaireData();
			zipPostToArray();
			zipArrayToDb();
			displayDbData();
			//populateArray();
		}
	}
	elseif (isset($_REQUEST["orgid"]))
	{
		/* #6 */
		$orgid = FILTER_VAR($_REQUEST["orgid"], FILTER_VALIDATE_INT);
		if (!array_key_exists("my_user_id", $_SESSION))
		{
			throw new Exception("USER_NOT_LOGGED_IN_ERROR");
		}

		displayDbData();
		buildEmptyArray();
		populateArray();
	}
	else
	{
		/* #1 */
		/* Build default screen data */
		$highlight_tab = "NEWORG";
		$orgid = 0;
		$person_name = "";
		$org_name = "";
		$email = "";
		$org_website = "";
		$money_url = "";
		$mission = "";
    	$goto_page = -1;
    	$user_id = filter_var($_GET["user_id"], FILTER_VALIDATE_INT);
   	    $action = "O"; /* This means inserting org only (user already exists) */
		$abbreviated_name = "";
		$active_ind = TRUE;
		$admin_contact = "";
		$customer_contact = "";
		$customer_notice = "";
		$zip_array = array(); /* empty array */

		if (!array_key_exists("my_user_id", $_SESSION))
		{
			throw new Exception("USER_NOT_LOGGED_IN_ERROR");
		}

		/* if this happens, then we are creating a new org
		tied to an existing user, so fill in the user data */
   	    getUserInfo();
		buildEmptyArray();
	}
	buildCsrfToken();
	//echo "<!-- \n ";
	//var_dump($_SESSION);
	//echo " --> \n";

}
catch (Exception $e)
{
	
	switch ($e->getMessage())
	{
	    case "DUPLICATE_EMAIL_ERROR" : 
	       $email_msg = "The email address entered was a duplicate.";
	       $goto_page = -2;
	    break;
	    case "DUPLICATE_ORG_NAME_ERROR" : 
	       $org_name_msg = "The organization name entered was a duplicate.";
	       $goto_page = -1;
	    break;
	    case "USER_NOT_LOGGED_IN_ERROR":
	       header("Location: login.php?errmsg=USER_NOT_LOGGED_IN_ERROR");
	       exit();
        break;
	    default:
	       header("Location: login.php?errmsg=true");
	       exit();
	    
	}

	buildEmptyArray();
	translatePostIntoArray();
	zipPostToArray();
	displayPostData();
    buildCsrfToken();
    
//	header("Location: login.php?errmsg=9"); /* the #9 means nothing, maybe at some point it will mean something */
//	exit();
}

/* end of global section, now fall through to HTML */

function buildCsrfToken()
{
    /* for csrf protection, the nonce will be formed from a hash of several variables 
        that make up the session, concatenated, but should be stable between requests,
        along with some random salt (defined above) */
    global $csrf_nonce, $csrf_salt, $action, $orgid, $csrf_expdate;
	$csrf_expdate = new DateTime(NULL, new DateTimeZone("UTC"));
	$csrf_expdate->add(new DateInterval("PT4H")); /* CSRF token expires in 4 hours */
    $token = $orgid . $action . $_SERVER['SERVER_SIGNATURE'] . $_SERVER['SCRIPT_FILENAME'] . $csrf_expdate->format('U') . session_id() . $csrf_salt;
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
	

    $token = $_REQUEST["orgid"] . $_POST["action"] . $_SERVER['SERVER_SIGNATURE'] . $_SERVER['SCRIPT_FILENAME'] .  $_POST['csrf_expdate'] . session_id() . 			$csrf_salt;
	
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
		|| !isset($_POST["password1"]) || !isset($_POST["password2"])
		|| !isset($_POST["org_name"]) || !isset($_POST["org_website"]) || !isset($_POST["money_url"]))
	{
        error_log("Parameter tampering detected (validatePostData) Something not posted.");
        throw new Exception(PARAMETER_TAMPERING);
        exit();		
	}
		
    global $email_msg, $orgid, $goto_page, $org_website_msg, $money_url_msg, $pwd_msg, $org_name_msg, $action;
    $orgid = filter_var($_REQUEST["orgid"], FILTER_VALIDATE_INT);
	$action = substr(strtoupper($_POST["action"]), 0, 1); /* trim action to 1 character */
	
    if (!($orgid >= 0))
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
		$goto_page = -2;
        return false;
    }

    if (strlen($email) > 128)
    {
        $email_msg = "Email address should not exceed 128 characters in length.";
		$goto_page = -2;
        return false;
    }

    if (strlen($email) < 1) 
    {
        $email_msg = "A valid email address is required.";
		$goto_page = -2;
        return false;
    }


    $org_name = $_POST["org_name"];
    if (strlen($org_name) < 4)
    {
        $org_name_msg = "The organization name must have at least 4 characters.";
        $goto_page = -1;
        return false;
    }

    if (strlen($org_name) > 128)
    {
        $org_name_msg = "The organization name exceeds the maximum length of 128 characters.";
        $goto_page = -1;
        return false;
    }

	$org_website = filter_var($_POST["org_website"], FILTER_SANITIZE_URL);
	
	if (strlen($org_website) > 0) /* web site is optional so skip check if its blank */
	{
		
		//echo "<!-- web site = $website -->\n"; 
		if (!filter_var($org_website, FILTER_VALIDATE_URL))
		{
			$org_website_msg = "The website URL does not follow the proper pattern for a valid URL.";
			$goto_page = -1;
			//echo "<!-- URL failed validation -->\n"; 
			return false;
		}

		if (strlen($org_website) > 255)
		{
			$org_website_msg = "Web site address should not exceed 255 characters in length.";
			$goto_page = -1;
			return false;
		}
	}

	$money_url = filter_var($_POST["money_url"], FILTER_SANITIZE_URL);
	//echo "<!-- money url = $money_url -->\n"; 
	if (strlen($money_url) > 0) /* donations site is optional so skip check if its blank */
	{
		
		if (!filter_var($money_url, FILTER_VALIDATE_URL))
		{
			$money_url_msg = "The website URL does not follow the proper pattern for a valid URL.";
			$goto_page = -1;
			//echo "<!-- failed validation -->\n"; 
			return false;
		}

		if (strlen($money_url) > 255)
		{
			$money_url_msg = "Web site address should not exceed 255 characters in length.";
			$goto_page = -1;
			return false;
		}
	}

	$pwd = $_POST["password1"];

	if ($pwd != $_POST["password2"])
	{
		$pwd_msg = "Passwords must match.";
		$goto_page = -2;
		return false;
	}
	
	if (strlen($pwd) > 128)
	{
		$pwd_msg = "Password exceeds the maximum length of 128 characters.";
		$goto_page = -2;
		return false;
	}

    if (strlen($pwd) < 1)
    {
        /* password was not specified so no reason to check anything else */
        return true;
    }
    	
	if (strlen($pwd) < 8)
	{
		$pwd_msg = "The password must be a minimum length of 8 characters.";
		$goto_page = -2;
		return false;
	}

	/* check complexity requirements */
	$hasUpperCase = ($pwd != strtolower($pwd) ? 1 : 0); 
	$hasLowerCase = ($pwd != strtoupper($pwd) ? 1 : 0); 
	$hasNumbers = preg_match('/[0-9]/', $pwd);
	$hasNonalphas = preg_match('/\W/', $pwd);

	if ($hasUpperCase + $hasLowerCase + $hasNumbers + $hasNonalphas < 3)
    {
        /* TODO: This code was used for debugging */
        //echo "<!-- Password = $pwd --> \n";
        //echo "<!-- Upper case = $hasUpperCase --> \n";
        //echo "<!-- Lower case = $hasLowerCase --> \n";
        //echo "<!-- Numerals = $hasNumbers --> \n";
        //echo "<!-- Non alphas = $hasNonalphas --> \n";
        
        $pwd_msg = "The password does not meet the complexity rules.";
		$goto_page = -2;
        return false;        
    }
	

	//echo "<!-- validatePost passed -->\n";
    return true;
}


function updateUser()
{
    try
    {
        /* no ability to create users on this page, only thing we can do is update */
        global $dbh, $user_id, $goto_page;

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
        
        /* For now, these values will not be updated from this page, so just set the parameters to NULL */
        $stmt->bindValue(':active_ind', NULL, PDO::PARAM_INT);
        $stmt->bindValue(':admin_user_ind', NULL, PDO::PARAM_INT);
        
	    $stmt->execute();

//		global $success_msg;
//		$success_msg = "Record successfully updated.";
//		$goto_page = 0;
		
        $stmt->closeCursor();

        /* TODO: detect email change and add verification email */
		/* TODO: check for parameter tampering in user ID */
		/* TODO: Add unique index on email */
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
            error_log("Database error during UPDATE query in org.php: " . $e->getMessage());
            throw new Exception("An unknown error was encountered (11). Please attempt to reauthenticate.");
    		exit();
        }
    }
    catch(Exception $e)
    {
        error_log("Error during database UPDATE query in org.php: " . $e->getMessage());
        throw new Exception("An unknown error was encountered (12). Please attempt to reauthenticate.");
		exit();
    }

}



function performUpdate()
{
    try
    {
        /* this is an update, so must do a save */
        global $dbh, $orgid, $goto_page, $user_id;

        /* make sure org ID requested is in the session variable with authorized orgs */
        /* or if the user is an admin, then that authorizes them as well */
        if (!in_array($orgid, $_SESSION["orgids"]) && $_SESSION["admin_user_ind"] == FALSE)
        {
            error_log("Unauthorized org ID requested. Possible parameter tampering.");
			throw new Exception("Unauthorized org ID requested. Possible parameter tampering.");
			exit();
        }

        /* make sure the user ID that we are putting on the org is authorized */
        if ($user_id != $_SESSION["my_user_id"] && $_SESSION["admin_user_ind"] == FALSE)
        {
            error_log("Unauthorized User ID requested. Possible parameter tampering.");
			throw new Exception("Unauthorized User ID requested. Possible parameter tampering.");
			exit();
        }


        $stmt = $dbh->prepare("CALL updateOrganization(:orgid, :org_name, :org_website, :money_url, " . 
			":mission, :active_ind, :abbreviated_name, :customer_contact, :customer_notice, :admin_contact, :user_id); " );

		$stmt->bindValue(':user_id', $user_id);
		$stmt->bindValue(':org_name', filter_var($_POST["org_name"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
            FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK), PDO::PARAM_STR);
        $stmt->bindValue(':org_website', filter_var($_POST["org_website"], FILTER_SANITIZE_URL), PDO::PARAM_STR);
        $stmt->bindValue(':money_url', filter_var($_POST["money_url"], FILTER_SANITIZE_URL), PDO::PARAM_STR);
            /* TODO: SANITIZE_URL lets some interesting things through. May not be a threat, but worth investigation */
        $stmt->bindValue(':orgid', $orgid, PDO::PARAM_INT);
        $stmt->bindValue(':mission', filter_var($_POST["mission"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
            FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK), PDO::PARAM_STR);

        $stmt->bindValue(':abbreviated_name', filter_var($_POST["abbreviated_name"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
            FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK), PDO::PARAM_STR);
        $stmt->bindValue(':customer_contact', filter_var($_POST["customer_contact"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
            FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK), PDO::PARAM_STR);
        $stmt->bindValue(':admin_contact', filter_var($_POST["admin_contact"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
            FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK), PDO::PARAM_STR);
        $stmt->bindValue(':customer_notice', filter_var($_POST["customer_notice"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
            FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK), PDO::PARAM_STR);

        /* have to check for this because browser does not send it if unchecked */
        if (array_key_exists("active_ind", $_POST))
        {
            $stmt->bindValue(':active_ind', ($_POST["active_ind"] == "on" ? 1 : 0), PDO::PARAM_INT);
        }
        else
        {
            $stmt->bindValue(':active_ind', 0, PDO::PARAM_INT);
        }

		//echo "<!-- performUpdate ";
		//var_dump($_SESSION);
		//echo " -->\n";
		
	    $stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            $erinf = $stmt->errorInfo();
			error_log("UPDATE failed in org.php: " . $stmt->errorCode() . " " . $erinf[2]);
			throw new Exception("An unknown error was encountered (10). Please attempt to reauthenticate.");
            exit();
        }
		else
		{
			global $success_msg;
			$success_msg = "Record successfully updated.";
			$goto_page = 0;
		}
		
        $stmt->closeCursor();

        /* TODO: detect email change and add verification email */

    }
    catch (PDOException $e)
    {
        if ($e->getCode() == "23000") /* integrity constraint violation, so I want to present a user friendly error message */
        {
            if (strpos($e->getMessage(), "for key 'ix_org_org_name_unique'") !== FALSE)
            {
                throw new Exception("DUPLICATE_ORG_NAME_ERROR");
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

        /* this is a new record, so do the insert */
        global $dbh, $orgid, $goto_page, $action, $success_msg, $email, $user_id;
        
        $stmt = $dbh->prepare("CALL insertOrganization(:org_name, :org_website, :money_url, " . 
			":mission, :active_ind, :abbreviated_name, :customer_contact, :customer_notice, :admin_contact, :user_id); " );

        $stmt->bindValue(':org_name', filter_var($_POST["org_name"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
            FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK), PDO::PARAM_STR);
        $stmt->bindValue(':org_website', filter_var($_POST["org_website"], FILTER_SANITIZE_URL), PDO::PARAM_STR);
        $stmt->bindValue(':money_url', filter_var($_POST["money_url"], FILTER_SANITIZE_URL), PDO::PARAM_STR);
 
        $stmt->bindValue(':mission', filter_var($_POST["mission"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
            FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK), PDO::PARAM_STR);

        $stmt->bindValue(':abbreviated_name', filter_var($_POST["abbreviated_name"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
            FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK), PDO::PARAM_STR);
        $stmt->bindValue(':customer_contact', filter_var($_POST["customer_contact"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
            FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK), PDO::PARAM_STR);
        $stmt->bindValue(':admin_contact', filter_var($_POST["admin_contact"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
            FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK), PDO::PARAM_STR);
        $stmt->bindValue(':customer_notice', filter_var($_POST["customer_notice"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
            FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK), PDO::PARAM_STR);
        if (array_key_exists("active_ind", $_POST))
        {
            $stmt->bindValue(':active_ind', ($_POST["active_ind"] == "on" ? 1 : 0), PDO::PARAM_INT);
        }
        else
        {
            $stmt->bindValue(':active_ind', 0, PDO::PARAM_INT);
        }


        /* make sure the user ID that we are putting on the org is authorized */
        if ($user_id != $_SESSION["my_user_id"] && $_SESSION["admin_user_ind"] == FALSE)
        {
            error_log("Unauthorized User ID requested. Possible parameter tampering.");
			throw new Exception("Unauthorized User ID requested. Possible parameter tampering.");
			exit();
        }

		$stmt->bindValue(':user_id', $user_id);
        
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
			$goto_page = 0;
		}
		
		$row = $stmt->fetch(PDO::FETCH_ASSOC); /* insert ID is returned as a row set from the SP */
		
        if ($row == FALSE) 
        {
			error_log("Failed to get the inserted ID# in org.php");
			throw new Exception("An unknown error was encountered (14). Please attempt to reauthenticate.");
			exit();
        }

		$orgid = $row["orgid"];
        /* place the org ID into session */
        array_push($_SESSION["orgids"], $orgid);
        //$_SESSION["user_id"] = $row["user_id"];
        

        $stmt->closeCursor();
		
        /* change the action to update, now that the record was successfully inserted */
        $action = "U";

    }
    catch (PDOException $e)
    {
        if ($e->getCode() == "23000") /* integrity constraint violation, so I want to present a user friendly error message */
        {
            if (strpos($e->getMessage(), "for key 'ix_org_org_name_unique'") !== FALSE)
            {
                throw new Exception("DUPLICATE_ORG_NAME_ERROR");
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

        global $dbh, $orgid;

        /* make sure orgid from session matches org ID requested */
        if (!in_array($orgid, $_SESSION["orgids"]) && $_SESSION["admin_user_ind"] == FALSE)
        {
            error_log("Unauthorized org ID requested. Possible parameter tampering.");
			throw new Exception("Unauthorized org ID requested. Possible parameter tampering.");
			exit();
        }

        $stmt = $dbh->prepare("CALL selectOrganization(:orgid);");
        $stmt->bindValue(':orgid', $orgid, PDO::PARAM_INT);

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
            global $org_name;
            global $person_name;

            global $email_is_verified;
            global $email;

            global $org_website;
            global $money_url;
            global $mission;
            global $action;
            global $zip_array;

            global $abbreviated_name, $customer_notice, $customer_contact, $admin_contact, $active_ind, $user_id;
            $org_name = htmlspecialchars($row["org_name"]);
            $person_name = htmlspecialchars($row["person_name"]);

            $email_is_verified = $row["email_is_verified"];
            $email = htmlspecialchars($row["email"]);
            

            $org_website = htmlspecialchars($row["org_website"]);
            $money_url = htmlspecialchars($row["money_url"]);
            $mission = htmlspecialchars($row["mission"]);

            $abbreviated_name = htmlspecialchars($row["abbreviated_name"]);
            $customer_notice = htmlspecialchars($row["customer_notice"]);
            $customer_contact = htmlspecialchars($row["customer_contact"]);
            $admin_contact = htmlspecialchars($row["admin_contact"]);
            $user_id = htmlspecialchars($row["user_id"]);
            
            $active_ind = $row["active_ind"];
            $action = "U";
            buildCsrfToken();
        }
        else
        {
			error_log("Failed to get the org record with that ID.");
			throw new Exception("An unknown error was encountered (18). Please attempt to reauthenticate.");
            exit();
        }
        $stmt->closeCursor();


        /* now get the zip codes from the database and put into an array */

        $stmt = $dbh->prepare("CALL selectZipcodesForOrganization(:orgid);");
        $stmt->bindValue(':orgid', $orgid, PDO::PARAM_INT);

        $stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            $erinf = $stmt->errorInfo();
			error_log("SELECT zip codes failed in org.php: " . $stmt->errorCode() . " " . $erinf[2]);
			throw new Exception("An unknown error was encountered (19). Please attempt to reauthenticate.");
            exit();
        }
		

        $zip_array = $stmt->fetchAll(PDO::FETCH_GROUP); /* put zip, city & state into an array */

		
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
    global $org_name;
    global $org_website;
    global $money_url;
    global $mission;
    global $action; 
    global $abbreviated_name, $customer_notice, $customer_contact, $admin_contact, $active_ind;
	global $user_id;

    $email = htmlspecialchars(filter_var($_POST["email"], FILTER_SANITIZE_EMAIL)); 
        /* TODO: don't know if the email is verified or not yet not sure how to handle this */
    $email_is_verified = 0;

    $person_name = htmlspecialchars(filter_var($_POST["person_name"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
            FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK));
    $org_name = htmlspecialchars(filter_var($_POST["org_name"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
            FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK));
    

    $org_website = htmlspecialchars($_POST["org_website"]);
    $money_url = htmlspecialchars($_POST["money_url"]);
    $mission = htmlspecialchars(filter_var($_POST["mission"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
            FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK));

    $abbreviated_name = htmlspecialchars(filter_var($_POST["abbreviated_name"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
        FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK));
    $customer_contact = htmlspecialchars(filter_var($_POST["customer_contact"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
        FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK));
    $admin_contact = htmlspecialchars(filter_var($_POST["admin_contact"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
        FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK));
    $customer_notice = htmlspecialchars(filter_var($_POST["customer_notice"], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES + 
        FILTER_FLAG_STRIP_LOW + FILTER_FLAG_STRIP_HIGH + FILTER_FLAG_STRIP_BACKTICK));
    $active_ind = checkPostForCheckbox("active_ind");
    $user_id = filter_var($_POST["user_id"], FILTER_VALIDATE_INT);
    $action = strtoupper(substr($_POST["action"], 0, 1)); /* on error, always retain the same action that was posted */

    buildCsrfToken();
}



function buildEmptyArray()
{
    try {

        global $dbh, $qu_aire;

		/* the zero is passed in there because the stored procedure returns questions & choices whether or not the org ID is valid */
        $stmt = $dbh->prepare("CALL selectQuestionsWithOrg(0)");

        $stmt->execute();
        if ($stmt->errorCode() != "00000") 
        {
            $erinf = $stmt->errorInfo();
			error_log("SELECT questions failed in org.php: " . $stmt->errorCode() . " " . $erinf[2]);
			throw new Exception("An unknown error was encountered (22). Please attempt to reauthenticate.");
            exit();
        }
		

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {

            /* construct an array of pages, which is an array of questions, which is an array of choices */
            /* this is a little hacky, but it is a pretty convenient way to automatically sort these */
            /* rows into their respective pages, questions, etc. without doing a */ 
            /* bunch of repetitive queries or complicated matching logic */

            $qu_aire[$row["page_num"]][$row["question_id"]][$row["choice_id"]] = $row;  

        }

        $stmt->closeCursor();

    }
    catch (PDOException $e)
    {
        error_log("Database error during SELECT query in org.php: " . $e->getMessage());
        throw new Exception("An unknown error was encountered (23). Please attempt to reauthenticate.");
		exit();
    }
    catch(Exception $e)
    {
        error_log("Error during database SELECT query in org.php: " . $e->getMessage());
		/* We most likely got here from the SQL error above, so just bubble up the exception */
        throw new Exception("An unknown error was encountered (24). Please attempt to reauthenticate.");
		exit();
    }

}

function populateArray()
{
    try {

        global $dbh, $orgid, $qu_aire;


		/* the NULL selects are there to put columns in the returned data set to hold data which will be used a little later */
        $stmt = $dbh->prepare("CALL selectQuestionsWithOrg(:orgid)");
        $stmt->bindValue(':orgid', $orgid, PDO::PARAM_INT);

        $stmt->execute();
        if ($stmt->errorCode() != "00000") 
        {
            $erinf = $stmt->errorInfo();
			error_log("SELECT questions failed in org.php: " . $stmt->errorCode() . " " . $erinf[2]);
			throw new Exception("An unknown error was encountered (25). Please attempt to reauthenticate.");
            exit();
        }

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {

            /* construct an array of pages, which is an array of questions, which is an array of choices */
            /* this is a little hacky, but it is a pretty convenient way to automatically sort these */
            /* rows into their respective pages, questions, etc. without doing a bunch of repetitive queries or complicated matching logic */

            $qu_aire[$row["page_num"]][$row["question_id"]][$row["choice_id"]] = $row;  

        }

        $stmt->closeCursor();
    }
    catch (PDOException $e)
    {
        error_log("Database error during SELECT query in org.php: " . $e->getMessage());
        throw new Exception("An unknown error was encountered (26). Please attempt to reauthenticate.");
		exit();
    }
    catch(Exception $e)
    {
        error_log("Error during database SELECT query in org.php: " . $e->getMessage());
		/* We most likely got here from the SQL error above, so just bubble up the exception */
        throw new Exception("An unknown error was encountered (27). Please attempt to reauthenticate.");
		exit();
    }

}

function arrayToHtml($pagenum)
{
    global $qu_aire, $goto_page;

    //echo "<!-- \n";
    //var_dump($row);
    //printf("<!-- QuestionID %d ChoiceID %d Selection %d -->\n", $choice["question_id"], $choice["choice_id"], $choice["org_id"]);
    //echo "--> \n";

    $page = $qu_aire[$pagenum];


    $row = current(current($page)); /* get the first choice from the first question, use that to pull the group text */
    $group_text = htmlspecialchars($row["group_text"]);
    echo "<div class='panel-group'>\n";
    echo "<div class='panel panel-default'>\n";
    echo "<div class='panel-heading'>\n";
    echo "<h4 class='panel-title'>\n";
    printf("<a data-toggle='collapse' href='#page%u'><span class='glyphicon glyphicon-plus'></span> %s </a>", $pagenum, $group_text);

    echo "</h4>\n";
    echo "</div> <!-- panel-heading -->\n";

    if ($goto_page == $pagenum)
    {
        $in = "in ";
    }
    else
    {
        $in = "";
    }

    printf("<div id='page%u' class='panel-collapse collapse %s' >\n", $pagenum, $in);
    echo "<div class='panel-body'>\n";

    foreach($page as $question_id => $question)
    {

        printf("\t<div class='form-group'>\n\t\t<label for='question-%u'>", $question_id);
        $row = current($question);
        echo htmlspecialchars($row["org_question_text"]);
        echo "</label>\n";

        if ($row["org_multi_select"])
        {
            $multi = sprintf(" multiple name='question-%u[]'", $question_id); /* add the brackets which force the browser to send an array */
        }
        else
        {
            $multi = sprintf(" name='question-%d'", $question_id);
        }
        printf("\t<select %s id='question-%u' class='form-control' >\n", $multi, $question_id);
        /* Must include the "<no selection>" value because otherwise user has no way to remove a previously selected option */
        echo "\t\t<option value='NULL'>&lt;No selection&gt;</option>\n";
        foreach($question as $choice_id => $choice)
        {
            /* the selected value could either come from the database or from the user agent POST */
            if ($choice["selected"] == "1" || $choice["new_selected"] == 1)
            {
                $selected = " selected ";
            }
            else
            {
                $selected = "";
            }
            printf("\t\t<option value='choice-%u' %s >%s</option>\n", $choice["choice_id"], $selected, htmlspecialchars($choice["choice_text"])) ;
        }
        
        echo "\t</select>\n\t</div>\n\n";
    }
    
    echo "\n"; /* this may be stupid but the tabs and LFs are for visually readable HTML source. Invisible Aesthetics FTW! */

    echo "</div> <!-- panel-body -->\n";
    echo "</div> <!-- page -->\n";
    echo "</div> <!-- panel-default -->\n";
    echo "</div> <!-- panel-group -->\n\n";

}

function translatePostIntoArray()
{

    global $qu_aire, $orgid;

    foreach($qu_aire as $page_num => $page)
    {
        foreach($page as $question_id => $question)
        {
            /* loop through each question that is in the database (array),
            look in the post for that question and any selections
            set the appropriate values in the array */
            $row = current($question);

            $question_name = sprintf("question-%u", $question_id);

            /* This method assumes that buildEmptyArray has already been called
                to construct the skeleton of the questionnaire array
                having questions and choices but no answers */

            /* there are 4 possible ways the $postval can be populated */

            /* 1) if the question was not answered, that means there will be
                nothing in the POST for that question ID
                nothing to do in that case because the value
                in the array is already 0 or null */

            /* 2) if the question was a single selection but the first, default
                "no selection" was chosen, then also nothing to do,
                because nothing to put into the array */

            /* 3) if the question was a single selection, then
                $postval will contain the choice # of the selection
                formatted as "choice-#'. In this case, look up the choice #
                in the array, and set it to selected */

            /* 4) if the question was a multiple selection, then
                $postval will contain an array of the choice #'s.
                Same action as #3, just must do it multiple times. */


            if (array_key_exists($question_name, $_POST)) /* check for a response to this question in the POST */
            {
                $postval = $_POST[$question_name];
                
                /* have to do a little weirdness here because of the way that http sends multiple select lists */

                if ($row["org_multi_select"])
                {
                    foreach($postval as $choice_str)
                    {
                        /* It's a multiple select, so http sent an array (even if only 1 selected) */
                        //printf("<!-- Question ID %s has the array response of choice id %s -->\n", $question_id, $choice_str);
                        if ($choice_str != "NULL") 
                        {
                            sscanf($choice_str, "choice-%u", $choice_id); /* scanf will only allow an integer to be returned */

                            /* pull the correct choice out of the question array, and set the selected flag in $qu_aire, 
                                which will cause a row to be inserted/updated with that ID later */
                            $qu_aire[$page_num][$question_id][$choice_id]["new_selected"] = "1";
                        }
                    }
                }
                else
                {
                    /* It's a single selection drop down, so the $postval is just the value of the "choice" (if any) */   
                    if ($postval != "NULL") 
                    {
                        sscanf($postval, "choice-%u", $choice_id); /* scanf will only allow an integer to be returned */

                        /* pull the correct choice out of the question array, and set the selected flag in the $qu_aire, 
                            which will cause a row to be inserted/updated with that ID later */
                        $qu_aire[$page_num][$question_id][$choice_id]["new_selected"] = "1";
                    }
                    //else 
                    //{
                    //    $qu_aire[$page_num][$question_id][$choice_id]["new_selected"] = "0";
                    //}
                }
            }

            
        }    
    }

//echo "<!-- translatePostIntoArray \n ";
//var_dump($qu_aire);
//echo " --> \n ";

}

function updateQuestionnaireData()
{
    global $qu_aire, $orgid, $dbh;

    $sql = "";

    foreach($qu_aire as $page_num => $page)
    {
        foreach($page as $question_id => $question)
        {
            foreach($question as $choice_id => $choice)
            {
                //echo "<!-- Selected: ";
                //var_dump($choice["selected"]);
                //echo "\n New Choice: ";
                //var_dump($choice["new_selected"]);
                //printf("org_response_id=%u QuestionID=%u ChoiceID=%u Selected=%u New=%u ", $choice["org_response_id"], $choice["question_id"], $choice["choice_id"], $choice["selected"], $choice["new_selected"]);
                //echo "--> \n";
                
                /* detect if this choice had been changed by the user */
                if ($choice["selected"] != $choice["new_selected"])
                {
                    $sql = sprintf("%s CALL updateOrgResponse(%u, %u, %u, %u);", 
                	   $sql, $choice["org_response_id"], $orgid, $choice_id, $choice["new_selected"]);
                }

               	$qu_aire[$page_num][$question_id][$choice_id]["selected"] = $choice["new_selected"]; /* set this so that it is redisplayed on the page later */

            }
        }
    }

    //echo "<!-- \n";
    //echo strtr($sql, ";", "\n");        
    //echo "--> \n";

    /* if there's nothing in the query, the user didn't change anything, so nothing to do */
    if (strlen(trim($sql)) == 0)
    {
        return;
    }
    
    try
    {

        //$dbh->beginTransaction();

        $stmt = $dbh->prepare($sql);
        //$stmt->bindValue(':orgid', $orgid);

        $stmt->execute();
        
        if ($stmt->errorCode() != "00000") 
        {
            //$dbh->rollBack();
            $erinf = $stmt->errorInfo();
			error_log("UPDATE/INSERT questions failed in org.php: " . $stmt->errorCode() . " " . $erinf[2]);
			throw new Exception("An unknown error was encountered (27). Please attempt to reauthenticate.");
            exit();
        }
		
        //echo "<!-- About to commit question responses" . $dbh->inTransaction() . " -->\n";
        //$dbh->commit();
        //echo "<!-- Just committed question responses" . $dbh->inTransaction() . " -->\n";

        
    }
    catch (PDOException $e)
    {
        //$dbh->rollBack();
        error_log("Database error during questionnaire INSERT query in org.php: " . $e->getMessage());
        throw new Exception("An unknown error was encountered (28). Please attempt to reauthenticate.");
		exit();
    }
    catch(Exception $e)
    {
        error_log("Error during database questionnaire INSERT query in org.php: " . $e->getMessage());
		/* We most likely got here from the SQL error above, so just bubble up the exception */
        throw new Exception("An unknown error was encountered (29). Please attempt to reauthenticate.");
		exit();
    }
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

function zipPostToArray()
{
    global $zip_array;


    if (array_key_exists("zip_list", $_POST) && isset($_POST["zip_list"]))
    {
        /* filter the array of zips to ensure it only contains ints */
        $zip_array = filter_var_array($_POST["zip_list"], FILTER_SANITIZE_NUMBER_INT);
    }
    else
    {
		/* Set to NULL because nothing was passed in the zips select box */
        $zip_array = NULL;
    }



    
}

function zipArrayToDb()
{
    global $zip_array, $orgid, $dbh;


    try
    {

        //echo "<!-- JSON array of zips \n";
        //echo json_encode($zip_array);
        //echo "-->\n";
        $json_array = json_encode($zip_array);
        /* the only valid characters to be in this array are digits, ", and [] */
        

        $stmt = $dbh->prepare("CALL updateOrgZipcodes(:orgid, :zipcodeArray);");
        $stmt->bindValue(':orgid', $orgid, PDO::PARAM_INT);
        /* The array of zips has been sanitized, but also */
        /* as long as this is a bound parameter it should be ok to pass to the DB */
        $stmt->bindValue(':zipcodeArray', $json_array, PDO::PARAM_STR);
        
        $stmt->execute();
        
        if ($stmt->errorCode() != "00000") 
        {
            //$dbh->rollBack();
            $erinf = $stmt->errorInfo();
			error_log("UPDATE/INSERT zip codes failed in org.php: " . $stmt->errorCode() . " " . $erinf[2]);
			throw new Exception("An unknown error was encountered (30). Please attempt to reauthenticate.");
            exit();
        }
		
        
    }
    catch (PDOException $e)
    {
        //$dbh->rollBack();
        error_log("Database error during zip code INSERT query in org.php: " . $e->getMessage());
        throw new Exception("An unknown error was encountered (31). Please attempt to reauthenticate.");
		exit();
    }
    catch(Exception $e)
    {
        error_log("Error during database zip code INSERT query in org.php: " . $e->getMessage());
		/* We most likely got here from the SQL error above, so just bubble up the exception */
        throw new Exception("An unknown error was encountered (32). Please attempt to reauthenticate.");
		exit();
    }
}



function getUserInfo()
{

    try {

        global $dbh, $user_id;
        
        $stmt = $dbh->prepare("CALL selectUserInfo(:user_id);");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

        $stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            $erinf = $stmt->errorInfo();
			error_log("SELECT user failed in org.php: " . $stmt->errorCode() . " " . $erinf[2]);
			throw new Exception("An unknown error was encountered (33). Please attempt to reauthenticate.");
            exit();
        }
		

        $row = $stmt->fetch(PDO::FETCH_ASSOC);


        if (isset($row))
        {
            global $person_name;

            global $email;
            global $email_is_verified;

            $person_name = htmlspecialchars($row["person_name"]);

            $email = htmlspecialchars($row["email"]);
            $email_is_verified = $row["email_is_verified"];
            

        }
        else
        {
			error_log("Failed to get the user record with that ID.");
			throw new Exception("An unknown error was encountered (34). Please attempt to reauthenticate.");
            exit();
        }
        $stmt->closeCursor();

        
    }
    catch (PDOException $e)
    {
        error_log("Database error during SELECT query in org.php: " . $e->getMessage());
        throw new Exception("An unknown error was encountered (35). Please attempt to reauthenticate.");
		exit();
    }
    catch(Exception $e)
    {
        error_log("Error during database SELECT query in org.php: " . $e->getMessage());
		/* We most likely got here from the SQL error above, so just bubble up the exception */
        throw new Exception("An unknown error was encountered (36). Please attempt to reauthenticate.");
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
    <script src="js/org.js"></script>
  
</head>

<body>

<?php require('include/admin_nav_bar.php'); ?>

<div class="container-fluid">

<center>
<div class="page-header">
    <h2>Organization Setup</h2>
</div>
</center>

<form method="POST" action="org.php" id="org_save_form" autocomplete="off" >
<input type="hidden" id="nonce" name="nonce" value="<?php echo $csrf_nonce; ?>" />
<input type="hidden" id="csrf_expdate" name="csrf_expdate" value="<?php echo $csrf_expdate->format('U'); ?>" />
<input type="hidden" id="action" name="action" value="<?php echo $action; ?>" />
<input type="hidden" id="orgid" name="orgid" value="<?php echo $orgid; ?>" />
<input type="hidden" id="user_id" name="user_id" value="<?php echo $user_id ?>" />

<div class="panel-group">
 <div class="panel panel-default">
  <div class="panel-heading">
   <h4 class="panel-title">
    <a data-toggle="collapse" href="#intro1"><span class="glyphicon glyphicon-plus"></span>Tell us a bit about yourself</a>
   </h4>
  </div> <!-- panel-heading -->

<div id="intro1" class="panel-collapse collapse <?php if ($goto_page == -2) echo "in"; ?> "  >

<div class="panel-body">
    
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
            if ($email_is_verified == 0)
            {
                echo "<div class='alert alert-info' id='email_unverified_msg' >The email address: $email has not been verified yet. \n";
				echo "<input type='button' id='generateVerificationEmail' value='Click here to request a new verification email.' /></div>\n";
				echo "<div hidden='true' id='generateVerficationEmailUrl' >";
				echo buildEmailVerificationUrl();
				echo "</div>\n";
				/* include the URL with the generated hash in a hidden div so that the URL is available to the AJAX/JS */
            }
        ?>
    </div> <!-- form-group -->
 
    <div class="alert alert-danger" <?php if (!isset($email_msg)) echo "hidden='true'"; ?> id="email_invalid_msg" >
        <?php if (isset($email_msg)) echo $email_msg; ?>
    </div>

   <div class="form-group">
        <label for="password1"><?php if ($action != "I") { echo "Update Password:"; } else { echo "Set Password:"; } ?></label>
        <input class="form-control" type="password" id="password1" maxlength="128" name="password1" value="" <?php if ($action == "I") { echo "required"; } ?> />
    </div> <!-- form-group -->

   <div class="form-group">
        <label for="password2">Verify Password:</label>
        <input class="form-control" type="password" id="password2" maxlength="128" name="password2" value="" <?php if ($action == "I") { echo "required"; } ?> />
    </div> <!-- form-group -->

    <div class="alert alert-danger" <?php if (!isset($pwd_msg)) echo "hidden='true'"; ?> id="pwd_msg" >
        <?php if (isset($pwd_msg)) echo $pwd_msg; ?>
    </div>
	

</div> <!-- panel-body -->
</div> <!-- page -->
</div> <!-- panel-default -->
</div> <!-- panel-group -->


<div class="panel-group">
 <div class="panel panel-default">
  <div class="panel-heading">
   <h4 class="panel-title">
    <a data-toggle="collapse" href="#intro2"><span class="glyphicon glyphicon-plus"></span> Tell us identifying information about the organization</a>
   </h4>
  </div> <!-- panel-heading -->

<div id="intro2" class="panel-collapse collapse <?php if (($goto_page == -1) || ($action == "I")) echo "in"; ?> "  >
<!--    <center><h3 id="header">Tell us identifying information about the organization</h3></center> -->
<div class="panel-body">

    <div class="form-group">
        <label for="org_name">Organization Name:</label>
        <input class="form-control" type="text" id="org_name" minlength="4" maxlength="128" name="org_name" value="<?php echo $org_name ?>" required />
    </div> <!-- form-group -->

    <div class="alert alert-danger" <?php if (!isset($org_name_msg)) echo "hidden='true'"; ?> id="org_name_msg" >
        <?php if (isset($org_name_msg)) echo $org_name_msg; ?>
    </div>

    <div class="form-group row">
        <div class="col-xs-5" for="active_ind"><p><strong>Set this Organization to Active:</strong></p><p><small class="text-muted">This is required in order to be shown to the public.</small></p></div>
        <div class="col-xs-1" ><input class="" type="checkbox" id="active_ind" name="active_ind" <?php echo ($active_ind == TRUE ? "checked" : ""); ?> /></div>
    </div> <!-- form-group -->

    <div class="form-group">
        <label for="abbreviated_name">Abbreviation:</label>
        <input class="form-control" type="text" id="abbreviated_name" maxlength="15" name="abbreviated_name" value="<?php echo $abbreviated_name ?>" />
    </div> <!-- form-group -->

    <div class="form-group">
        <label for="mission">Organizational Mission Statement:</label>
        <textarea class="form-control" id="mission" maxlength="2000" name="mission" rows="4" value="" ><?php echo $mission ?></textarea>
    </div> <!-- form-group -->

    <div class="form-group">
        <label for="customer_notice">Customer Informational Notice:</label>
        <textarea class="form-control" id="customer_notice" maxlength="255" name="customer_notice" rows="4" value="" ><?php echo $customer_notice ?></textarea>
		<small class="text-muted">This information is shown to the customer prior to final selection.</small>
    </div> <!-- form-group -->

    <div class="form-group">
        <label for="org_website">Organization website:</label>
        <input class="form-control" type="url" id="org_website" maxlength="255"  name="org_website" value="<?php echo $org_website ?>" />
    </div> <!-- form-group -->

    <div class="alert alert-danger" <?php if (!isset($org_website_msg)) echo "hidden='true'"; ?> id="org_website_msg" >
        <?php if (isset($org_website_msg)) echo $org_website_msg; ?>
    </div>

    <div class="form-group">
        <label for="money_url">Donations website:</label>
        <input class="form-control" type="url" id="money_url" maxlength="255" name="money_url" value="<?php echo $money_url ?>" />
    </div> <!-- form-group -->

    <div class="alert alert-danger" <?php if (!isset($money_url_msg)) echo "hidden='true'"; ?> id="money_url_msg" >
        <?php if (isset($money_url_msg)) echo $money_url_msg; ?>
    </div>

    <div class="form-group">
        <label for="admin_contact">Administrative Contact Instructions:</label>
        <input class="form-control" type="text" id="admin_contact" maxlength="255" name="admin_contact" value="<?php echo $admin_contact ?>" />
		<small class="text-muted">This text is not displayed to the public.</small>
    </div> <!-- form-group -->

    <div class="form-group">
        <label for="customer_contact">Customer Contact Instructions:</label>
        <input class="form-control" type="text" id="customer_contact" maxlength="255" name="customer_contact" value="<?php echo $customer_contact ?>" />
		<small class="text-muted">This text is displayed to the public.</small>
    </div> <!-- form-group -->

    <label for="zip_entry">In what zip codes do you expect to physically meet your volunteers and teammates?</label>
    <div class="form-group row">
        <div class="col-xs-3" >
            <input class="form-control" type="number" id="zip_entry" maxlength="5" name="zip_entry" /></div>
        <div class="col-xs-2" >
            <button class="btn btn-default" id="zip_select" type="button" alt="Select a zip code" >Add</button>
            <button class="btn btn-default" id="zip_unselect" type="button" alt="Unselect a zip code">Remove</button>
        </div>
        <div class="col-xs-7" >
            <select multiple name="zip_list[]" id="zip_list" class="form-control" > <!-- Must include the brackets in the name to force browser to send an array -->
<?php
            echo "<!-- \n";
            var_dump($zip_array);
            echo " --> \n";
                    if (count($zip_array) > 0)
                    {
                        foreach($zip_array as $zipnum => $cityState)
                        {
                            if (is_array($cityState)) /* the array looks different depending upon whether */
                            { /* it came from the DB from the fetchAll statement */
                                printf("\t\t\t\t<option value='%05u' >%05u - %s, %s</option>\n", $zipnum, $zipnum, $cityState[0][0], $cityState[0][1]);    
                            }
                            else 
                            { /* or it came from a postback, in which case we forgot the city/state */
                                printf("\t\t\t\t<option value='%05u' >%05u </option>\n", $cityState, $cityState);    
                            }
                        }
                    }
                    else
                    {
                        echo "\t\t\t\t<option value='NULL' >&lt;No zip codes selected&gt;</option>\n";
                    }
?>
            </select>
        </div>
    </div> <!-- form-group -->




</div> <!-- panel-body -->
</div> <!-- page -->
</div> <!-- panel-default -->
</div> <!-- panel-group -->




<?php
    foreach($qu_aire as $page_num => $page)
    { 
        arrayToHtml($page_num);
    }
?>

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

