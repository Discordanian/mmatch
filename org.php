<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

require_once('include/secrets.php');
require_once('include/pwhashfx.php');


/* There are basically 6 possible flows to this page */
/* #1 Cold session, no incoming data, no data to retrieve, just show defaults on page */
/* #2 Insert attempted, validation errors found, display form with data populated from POST, along with error message */
/* #3 Insert attempted, no errors, insert successful, display form with data populated from DB, along with SUCCESS msg */
/* #4 Update attempted, validation errors found, display form with data populated from POST, along with error message */
/* #5 Update attempted, no errors, update successful, display form with data populated from DB, along with SUCCESS msg */ 
/* #6 Retrieve only, display form with data populated from DB */

/* TODO: This code is very vulnerable to XSS 
and probably a bunch of other attacks
Needs serious security review */

$goto_page = 1;
session_start();
//echo "<!-- ";
//var_dump($_SESSION);
//echo " -->\n";



if (isset($_POST["action"]))
{
    /* Flow #2, #3, #4, or #5 */
    checkCsrfToken();
    
    if (!validatePostData())
    {
        /* #2 or #4 */
        displayPostData();
    } elseif ($_POST["action"] == "INSERT")
    {
        /* #3 */
        initializeDb();
        performInsert();
        displayDbData();
        buildArray();
    }
    elseif ($_POST["action"] == "UPDATE")
    {
        /* #5 */
        initializeDb();
        performUpdate();
        displayDbData();
        buildArray();
    }
}
elseif (isset($_REQUEST["orgid"]))
{
    /* #6 */
    $orgid = FILTER_VAR($_REQUEST["orgid"], FILTER_VALIDATE_INT);
    initializeDb();
    displayDbData();
    buildArray();
}
else
{
    /* #1 */
    /* Build default screen data */
    $orgid = 0;
    $person_name = "";
    $org_name = "";
    $email_verified = "";
    $website = "";
    $money_url = "";
    $action = "INSERT";
    initializeDb();
    buildArray();
    buildCsrfToken();
}
/* end of global section, now fall through to HTML */

function buildCsrfToken()
{
    /* for csrf protection, the nonce will be formed from a hash of several variables 
        that make up the session, concatenated, but should be stable between requests,
        along with some random salt (defined above) */
    global $csrf_nonce, $csrf_salt, $action;
    $token = $action . '-' . $_SERVER['SERVER_SIGNATURE'] . '-' . $_SERVER['PHP_SELF'] . '-' . $csrf_salt;
    //echo "<!-- DEBUG token = $token -->\n";
    $csrf_nonce = hash("sha256", $token);
}


function checkCsrfToken()
{
    global $csrf_salt;

    $token = $_POST["action"] . '-' . $_SERVER['SERVER_SIGNATURE'] . '-' . $_SERVER['PHP_SELF'] . '-' . $csrf_salt;
	
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
    global $email_msg, $orgid, $goto_page, $website_msg, $pwd_msg;
    $orgid = FILTER_VAR($_REQUEST["orgid"], FILTER_VALIDATE_INT);

    /* first do basic validations before accessing the database */
    if (isset($_POST["email"])) 
    {
        $email = $_POST["email"];
        // check if e-mail address is well-formed

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
        {
            $email_msg = "The email address does not appear to follow the proper form.";
			$goto_page = 1;
            return false;
        }

        if (strlen($email) > 128)
        {
            $email_msg = "Email address should not exceed 128 characters in length.";
			$goto_page = 1;
            return false;
        }

        if (strlen($email) < 1) 
        {
            $email_msg = "A valid email address is required.";
			$goto_page = 1;
            return false;
        }
    }
	else
	{
		/* Weird situation because this data field was not even posted.
		Should probably log it. */
		$email_msg = "A valid email address is required.";
		$goto_page = 1;
		return false;
	}

	if ((isset($_POST["password1"]) && (isset($_POST["password2"]))))
	{
		if ($_POST["password1"] != $_POST["password2"])
		{
			$pwd_msg = "Passwords must match.";
			$goto_page = 1;
			return false;
		}
		
		if (strlen($_POST["password1"]) > 128)
		{
			$pwd_msg = "Password exceeds the maximum length of 128 characters.";
			$goto_page = 1;
			return false;
		}
	}
	else
	{
		/* Weird situation because this data field was not even posted.
		Should probably log it. */
		$pwd_msg = "Passwords must match.";
		$goto_page = 1;
		return false;
	}
	
	if (isset($_POST["org_website"]))
	{
		$website = $_POST["org_website"];
		
		if (strlen($website) > 0) /* web site is optional so skip check if its blank */
		{
			
			//echo "<!-- web site = $website -->\n"; 
			if (!filter_var($website, FILTER_VALIDATE_URL))
			{
				$website_msg = "The website URL does not follow the proper pattern for a valid URL.";
				$goto_page = 2;
				//echo "<!-- URL failed validation -->\n"; 
				return false;
			}

			if (strlen($website) > 255)
			{
				$website_msg = "Web site address should not exceed 255 characters in length.";
				$goto_page = 2;
				return false;
			}
		}
	}
	else
	{
		/* Weird situation because this data field was not even posted.
		Should probably log it. */
		$website_msg = "An unknown error occurred. Please try again.";
		$goto_page = 2;
		return false;
	}

	if (isset($_POST["money_url"]))
	{
		$money_url = $_POST["money_url"];
		//echo "<!-- money url = $money_url -->\n"; 
		if (strlen($money_url) > 0) /* donations site is optional so skip check if its blank */
		{
			
			if (!filter_var($money_url, FILTER_VALIDATE_URL))
			{
				$donations_msg = "The website URL does not follow the proper pattern for a valid URL.";
				$goto_page = 2;
				//echo "<!-- failed validation -->\n"; 
				return false;
			}

			if (strlen($money_url) > 255)
			{
				$donations_msg = "Web site address should not exceed 255 characters in length.";
				$goto_page = 2;
				return false;
			}
		}
	}
	else
	{
		/* Weird situation because this data field was not even posted.
		Should probably log it. */
		$donations_msg = "An unknown error occurred. Please try again.";
		$goto_page = 2;
		return false;
	}

	//echo "<!-- validatePost passed -->\n";
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


function performUpdate()
{
    try
    {
        /* this is an update, so must do a save */
        global $dbh, $orgid, $goto_page;

        assert(isset($dbh));

        /* make sure orgid from session matches org ID requested */
        if ($_SESSION["orgid"] != $orgid)
        {
            die("Parameter tampering detected. Requested org ID which is not authorized.");
        }

        $stmt = $dbh->prepare("UPDATE org SET name = :org_name, person_name = :person_name, website = :website, money_url = :money_url WHERE orgid = :orgid; " .
            "UPDATE org SET pwhash = :pwhash WHERE orgid = :orgid AND :pwhash IS NOT NULL; " .
            "UPDATE org SET email_unverified = :email WHERE orgid = :orgid AND email_verified IS NOT NULL AND email_verified != :email; " . 
            "UPDATE org SET email_unverified = :email WHERE orgid = :orgid AND email_verified IS NULL AND email_unverified != :email; " );
            /* first query doesn't update email because it might get updated in a different window/browser/device */
            /* next query only updates password if a new one was entered */
            /* if email is changed, and it was previously verified, then set it to unverified */
            /* OR if email is changed, and it was not previously verified, then changed it and keep it unverified */
            /* should this query be moved to a stored procedure? */
        $stmt->bindParam(':org_name', $_POST["org_name"]);
        $stmt->bindParam(':person_name', $_POST["person_name"]);
        $stmt->bindParam(':email', $_POST["email"]);
        $stmt->bindParam(':website', $_POST["org_website"]);
        $stmt->bindParam(':money_url', $_POST["money_url"]);
        $stmt->bindParam(':orgid', $orgid);


        /* udpate password if a new one specified */
        if (strlen($_POST["password1"]) > 0)
        {
            $pwhash = password_hash($_POST["password1"], PASSWORD_BCRYPT);
        }
        else
        {
            $pwhash = null;
        }

        $stmt->bindParam(':pwhash', $pwhash);
		
		//echo "<!-- ";
		//$stmt->debugDumpParams();
		//echo " -->\n";
		
	    $stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            $erinf = $stmt->errorInfo();
            die("Update failed<br>Error code:" . $stmt->errorCode() . "<br>" . $erinf[2]); /* the error message in the returned error info */
        }
		else
		{
			global $success_msg;
			$success_msg = "Record successfully updated.";
			$goto_page = 8;
		}
		

        /* TODO: detect email change and add verification email */

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


function performInsert()
{

    try
    {
        /* TODO: first check to see if a record under this email address already exists */

        /* this is a new record, so do the insert */
        global $dbh, $orgid, $goto_page;
        $stmt = $dbh->prepare("INSERT INTO org (name, person_name, email_unverified, pwhash, website, money_url)" 
            . " VALUES (:org_name, :person_name, :email, :pwhash, :website, :money_url);");

        $stmt->bindParam(':org_name', $_POST["org_name"]);
        $stmt->bindParam(':person_name', $_POST["person_name"]);
        $stmt->bindParam(':email', $_POST["email"]);
        $stmt->bindParam(':website', $_POST["org_website"]);
        $stmt->bindParam(':money_url', $_POST["money_url"]);
        $pwhash = password_hash($_POST["password1"], PASSWORD_BCRYPT);
        $stmt->bindParam(':pwhash', $pwhash);

        //$stmt->debugDumpParams();

        $stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            echo "Error code:<br>";
            $erinf = $stmt->errorInfo();
            die("Insert failed<br>Error code:" . $stmt->errorCode() . "<br>" . $erinf[2]); /* the error message in the returned error info */
        }
		else
		{
			global $success_msg;
			$success_msg = "Record successfully inserted. An email has been sent to validate the email.";
			$goto_page = 8;
		}
		

        /* change the action to update, now that the record was successfully inserted */
        global $action;
        $action = "UPDATE";
        $orgid = $dbh->lastInsertId();

        if (!isset($orgid)) 
        {
            die("Oops...failed to get the insert Id. That sucks big time...");
            /* TODO: much better/cleaner handling of errors */
        }

        /* place the org ID into session */
        $_SESSION["orgid"] = $orgid;

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
    
    /* TODO: add verification email */

}


function displayDbData()
{

    try {

        global $dbh, $orgid;

        assert($orgid != false); /* TODO: more error handling needed */

        /* make sure orgid from session matches org ID requested */
        if ($_SESSION["orgid"] != $orgid)
        {
            die("Parameter tampering detected. Requested org ID which is not authorized.");
        }

        $stmt = $dbh->prepare("SELECT orgid, name, person_name, email_verified, email_unverified, website, money_url FROM org WHERE orgid = :orgid ;");
        $stmt->bindParam(':orgid', $orgid);

        $stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            echo "Error code:<br>";
            $erinf = $stmt->errorInfo();
            die("Insert failed<br>Error code:" . $stmt->errorCode() . "<br>" . $erinf[2]); /* the error message in the returned error info */
        }
		

        $row = $stmt->fetch(PDO::FETCH_ASSOC);


        if (isset($row))
        {
            global $org_name;
            global $person_name;

            global $email_verified;
            global $email_unverified;
            

            global $website;
            global $money_url;
            global $action;

            $org_name = $row["name"];
            $person_name = $row["person_name"];

            $email_verified = $row["email_verified"];
            $email_unverified = $row["email_unverified"];
            

            $website = $row["website"];
            $money_url = $row["money_url"];
            $action = "UPDATE";
            buildCsrfToken();
        }
        else
        {
            die("Oops, no organization found with that Id.");
            /* TODO: much better/cleaner handling of errors */
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


function displayPostData()

{
    /* an error was encountered, so repopulate the fields from the POST */
    global $email_verified; 
    global $person_name;
    global $org_name;
    global $website;
    global $money_url;
    global $action; 

    $email_verified = $_POST["email"]; /* TODO: don't know if the email is verified or not yet
                                        not sure how to handle this */
    $person_name = $_POST["person_name"];
    $org_name = $_POST["org_name"];
    

    $website = $_POST["org_website"];
    $money_url = $_POST["money_url"];
    $action = $_POST["action"]; /* on error, always retain the same action that was posted */

    buildCsrfToken();
}


function buildArray()
{
    try {

        global $dbh, $orgid, $qu_aire;


        $stmt = $dbh->prepare("SELECT gg.group_id, gg.group_text, gg.page_num, qq.question_id, qq.question_text, " .
        " qq.org_multi_select, qq.sort_order, qc.choice_id, qc.choice_text, qc.sort_order, res.org_id " .
        " FROM question_group gg INNER JOIN question qq " .
        " ON gg.group_id = qq.question_group_id " .
        " INNER JOIN question_choice qc " .
        " ON qc.question_id = qq.question_id " .
        " LEFT OUTER JOIN org_response res " .
        " ON res.choice_id = qc.choice_id " .
        " WHERE res.org_id IS NULL OR res.org_id = :orgid " .
        " ORDER BY gg.group_id, gg.page_num, qq.question_id, qq.sort_order, qc.choice_id, qc.sort_order;");
        $stmt->bindParam(':orgid', $orgid);

        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {

            /* construct an array of pages, which is an array of questions, which is an array of choices */
            /* this is a little hacky, but it is a pretty convenient way to automatically sort these */
            /* rows into their respective pages, questions, etc. without doing a bunch of repetitive queries or complicated matching logic */

            $qu_aire[$row["page_num"]][$row["question_id"]][$row["choice_id"]] = $row;  

          
        }

        echo "<!-- " . count($qu_aire) . " -->\n";


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

function arrayToHtml($pagenum)
{
    global $qu_aire;

    $page = $qu_aire[$pagenum];


    foreach($page as $question_id => $question)
    {

        echo "\t<div class='form-group'>\n\t\t<label for='question-$question_id'>";
        $row = current($question);
        echo $row["question_text"];
        echo "</label>\n";

        echo "\t<select name='question-$question_id' id='question-$question_id' class='form-control' >\n";
        echo "\t\t<option value='NULL'>&lt;No selection&gt;</option>\n";
        foreach($question as $choice_id => $choice)
        {
            echo "\t\t<option value='choice-" . $choice["choice_id"] . "' >" . $choice["choice_text"] . "</option>\n";
        }
        
        echo "\t</select>\n\t</div>\n";
    }
    
    echo "\n";
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
    <script src="js/org.js"></script>
  
</head>

<body>

<div class="container-fluid">

<center>
<div class="page-header">
    <h1>Movement Match</h1>
    <h2>Organization Setup</h2>
</div>
</center>

<form method="POST" action="org.php" id="org_save_form" >
<input type="hidden" id="nonce" name="nonce" value="<?php echo $csrf_nonce ?>" />
<input type="hidden" id="action" name="action" value="<?php echo $action ?>" />
<input type="hidden" id="orgid" name="orgid" value="<?php echo $orgid ?>" />

<div id="page1" <?php if ($goto_page != 1) echo "hidden='true'"; ?> >
    <center>
        <h3 id="header">Tell us a bit about yourself first</h3>
    </center>

    <div class="form-group">
        <label for="person_name">Name:</label>
        <input class="form-control" type="text" id="person_name" maxlength="128" name="person_name" value="<?php echo $person_name ?>" />
    </div> <!-- form-group -->

    <div class="alert alert-danger" hidden="true" id="person_name_msg" >
        The name must contain at least 4 characters.
    </div>

    <div class="form-group">
        <label for="email">Email address:</label>
        <?php 
            if (isset($email_verified))
            {
                echo "<input class='form-control' type='email' id='email' maxlength='128' name='email' value='$email_verified' />\n";
            }
            elseif (isset($email_unverified))
            {
                echo "<input class='form-control' type='email' id='email' maxlength='128' name='email' value='$email_unverified' />\n";
            }
            else
            {
                echo "<input class='form-control' type='email' id='email' maxlength='128' name='email' value='' />\n";
            }

            if (isset($email_unverified))
            {
                echo "<div class='alert alert-info' id='email_unverified_msg' >The email address: $email_unverified has not been verified yet.</div>\n";
            }
        ?>
    </div> <!-- form-group -->
 
    <div class="alert alert-danger" <?php if (!isset($email_msg)) echo "hidden='true'"; ?> id="email_invalid_msg" >
        <?php if (isset($email_msg)) echo $email_msg; ?>
    </div>

   <div class="form-group">
        <label for="password1"><?php if ($action == "UPDATE") { echo "Update Password:"; } else { echo "Set Password:"; } ?></label>
        <input class="form-control" type="password" id="password1" maxlength="128" name="password1" value="" />
    </div> <!-- form-group -->

   <div class="form-group">
        <label for="password2">Verify Password:</label>
        <input class="form-control" type="password" id="password2" maxlength="128" name="password2" value="" />
    </div> <!-- form-group -->

    <div class="alert alert-danger" <?php if (!isset($pwd_msg)) echo "hidden='true'"; ?> id="pwd_msg" >
        <?php if (isset($pwd_msg)) echo $pwd_msg; ?>
    </div>
	
    <ul class="pager">
        <!-- <li><a href="#" id="" >Save data</a></li> -->
        <li><a href="#" id="p1_goto_p2" >Next</a></li>
    </ul> 

</div> <!-- page 1 -->

<div id="page2" <?php if ($goto_page != 2) echo "hidden='true'"; ?> >
    <center><h3 id="header">Tell us identifying information about the organization</h3></center>

    <div class="form-group">
        <label for="org_name">Organization Name:</label>
        <input class="form-control" type="text" id="org_name" maxlength="128" name="org_name" value="<?php echo $org_name ?>" />
    </div> <!-- form-group -->

    <div class="alert alert-danger" hidden="true" id="org_name_msg" >
        The name must contain at least 4 characters.
    </div>

    <div class="form-group">
        <label for="org_website">Organization website:</label>
        <input class="form-control" type="url" id="org_website" maxlength="255"  name="org_website" value="<?php echo $website ?>" />
    </div> <!-- form-group -->

    <div class="alert alert-danger" <?php if (!isset($website_msg)) echo "hidden='true'"; ?> id="website_invalid_msg" >
        <?php if (isset($website_msg)) echo $website_msg; ?>
    </div>

    <div class="form-group">
        <label for="money_url">Donations website:</label>
        <input class="form-control" type="url" id="money_url" maxlength="255" name="money_url" value="<?php echo $money_url ?>" />
    </div> <!-- form-group -->

    <div class="alert alert-danger" <?php if (!isset($donations_msg)) echo "hidden='true'"; ?> id="donations_invalid_msg" >
        <?php if (isset($donations_msg)) echo $donations_msg; ?>
    </div>



    <ul class="pager">
        <li><a href="#" id="p2_goto_p1" >Previous</a></li>
        <li><a href="#" id="p2_goto_p3" >Next</a></li>
    </ul> 

</div> <!-- page 2 -->

<div id="page3" <?php if ($goto_page != 3) echo "hidden='true'"; ?> >
    <center><h3 id="header">Tell us about the issues you are working on</h3></center>

<?php
    arrayToHtml(1);
?>

	<ul class="pager">
        <li><a href="#" id="p3_goto_p2" >Previous</a></li>
        <li><a href="#" id="p3_goto_p4" >Next</a></li>
    </ul> 


</div> <!-- Page 3 -->

<div id="page4" <?php if ($goto_page != 4) echo "hidden='true'"; ?> >
    <center><h3 id="header">Tell us about the type of organization this is</h3></center>


<?php
    arrayToHtml(2);
?>
	
	
    <ul class="pager">
        <li><a href="#" id="p4_goto_p3" >Previous</a></li>
        <li><a href="#" id="p4_goto_p5" >Next</a></li>
    </ul> 


</div> <!-- Page 4 -->

<div id="page5" <?php if ($goto_page != 5) echo "hidden='true'"; ?> >
    <center><h3 id="header">Tell us about the types of people you are looking for</h3></center>


<?php
    arrayToHtml(3);
?>
	
    <ul class="pager">
        <li><a href="#" id="p5_goto_p4" >Previous</a></li>
        <li><a href="#" id="p5_goto_p6" >Next</a></li>
    </ul> 


</div> <!-- Page 5 -->

<div id="page6" <?php if ($goto_page != 6) echo "hidden='true'"; ?> >
    <center><h3 id="header">Tell us about the skills you need</h3></center>


<?php
    arrayToHtml(4);
?>

    <ul class="pager">
        <li><a href="#" id="p6_goto_p5" >Previous</a></li>
        <li><a href="#" id="p6_goto_p7" >Next</a></li>
    </ul> 


</div> <!-- Page 6 -->

<div id="page7" <?php if ($goto_page != 7) echo "hidden='true'"; ?> >
    <center><h3 id="header">Tell us about the benefits you may offer</h3></center>


    <div class="form-group">
        <label for="benefit-1">What benefits might a volunteer see from working for you:</label>
		<select name="benefit-1" id="benefit-1" class="form-control">
			<option value="NULL">&lt;No selection&gt;</option>
		</select>
    </div> <!-- form-group -->

	
    <ul class="pager">
        <li><a href="#" id="p7_goto_p6" >Previous</a></li>
        <li><a href="#" id="p7_goto_p8" >Next</a></li>
    </ul> 


</div> <!-- Page 7 -->

<div id="page8" <?php if ($goto_page != 8) echo "hidden='true'"; ?> >
    <center><h3 id="header">Summary</h3></center>

    <ul class="pager">
        <li><a href="#" id="p8_goto_p7" >Previous</a></li>
        <li><a id="save_data" href="#">Save data</a></li>
    </ul> 

	<?php if (isset($success_msg))
	{
		echo "<div class='alert alert-success alert-dismissable' id='general_alert_msg' >\n";
		echo "<a href='#' class='close' data-dismiss='alert' aria-label='close'>×</a>\n";
		echo "$success_msg\n</div>";
	}
	?>
	
</div> <!-- Page 8 -->

</form>

</div> <!-- Container fluid -->


</body>
</html>

