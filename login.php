<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

require_once('include/secrets.php');
require_once('include/pwhashfx.php');

/* #1 Cold session, no incoming data, no data to retrieve, clear all cookies, and show defaults on page */
/* #2 Credentials entered, authentication fails, redisplay blank form, along with error message */
/* #3 Credentials entered, authentication succeeds, setup session, redirect to org.php */

/* TODO: This code is very vulnerable to XSS 
and probably a bunch of other attacks
Needs serious security review */

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
    buildCsrfToken();

    if (isset($_GET["errmsg"]))
    {
        $auth_fail_msg = "An unknown error occurred. Please attempt to authenticate again.";
    }
}



/* end of global section, now fall through to HTML */

function buildCsrfToken()
{
    /* for csrf protection, the nonce will be formed from a hash of several variables
        that make up the session, concatenated, but should be stable between requests,
        along with some random salt (defined above) */
    global $csrf_nonce, $csrf_salt;
    $token = $_SERVER['SERVER_SIGNATURE'] . $_SERVER['SCRIPT_FILENAME'] . $csrf_salt;
    //echo "<!-- DEBUG build token = $token -->\n";
    $csrf_nonce = hash("sha256", $token);
}


function checkCsrfToken()
{
    global $csrf_salt;

    $token = $_SERVER['SERVER_SIGNATURE'] . $_SERVER['SCRIPT_FILENAME'] . $csrf_salt;
	
	//echo "<!-- DEBUG Check Token = $token -->\n";
    if (hash("sha256", $token) != $_POST["nonce"])
    {
        die("CSRF token mismatch. Just kill me now...");
    }
	/* else
	{
		echo "<!-- CSRF passed -->\n";
	} */
}


function validatePostData()
{

    global $email;

    $email = $_POST["email"];
    return true;
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


function authenticateCredentials()
{
    try
    {
        global $dbh, $orgid, $email, $pwhash;

        initializeDb();

        assert(isset($dbh));

        $stmt = $dbh->prepare("select orgid, email_verified, email_unverified, pwhash from org where email_verified = :email or email_unverified = :email ;" );
        $stmt->bindParam(':email', $email);
		
		//echo "<!-- ";
		//$stmt->debugDumpParams();
		//echo " -->\n";
		
	    $stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            $erinf = $stmt->errorInfo();
            die("Select failed<br>Error code:" . $stmt->errorCode() . "<br>" . $erinf[2]); /* the error message in the returned error info */
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);


        if (isset($row))
		{
            $pwhash = $row["pwhash"];

            //echo "<!-- Password hash from db = $pwhash -->\n";
            //echo "<!-- Password entered = " . $_POST["password"] . "-->\n";
            //echo "<!-- Password hash calculated = " . password_hash($_POST["password"], PASSWORD_BCRYPT) . " -->\n";

            if (password_hash($_POST["password"], PASSWORD_BCRYPT) == $pwhash)
            {
                $orgid = $row["orgid"];
                //$email = $row["email_verified"];
                return true;
            }
        }


    }
    catch (PDOException $e)
    {
        die("Database Query Error: " . $e->getMessage());
        /* TODO: much better/cleaner handling of errors */
    }
    catch(Exception $e)
    {
        die($e->getMessage());
        /* TODO: much better/cleaner handling of errors */
    }

}

function clearSession()
{
    /* The following was copied from php.net */
    /* The goal is to clear all cookies when the login page is shown
        which mitigates against some session hijacking threats */
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
<input type="hidden" id="nonce" name="nonce" value="<?php echo $csrf_nonce ?>" />


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

    <button type="submit" class="btn btn-default" id="submit">Submit</button>


</form>

</div> <!-- Container fluid -->


</body>
</html>

