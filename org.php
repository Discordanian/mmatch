<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

$dbhostname = "localhost";
$dbusername = "movem_usr";
$dbpassword = "v97N8BOL";
$csrf_salt = "9zZuoJqG5KlUSYjVjDRHg";

$orgid = 0;
$person_name = "";
$org_name = "";
$email_verified = "";
$website = "";
$money_url = "";

try 
{

    /* first do basic validations before opening the database */
    if (isset($_POST["email"])) 
    {
        $email = $_POST["email"];
        // check if e-mail address is well-formed

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
        {
            $email_msg = "The email address does not appear to follow the proper form.";
            $error_out = true;
        }

        if (strlen($email) > 128)
        {
            $email_msg = "Email address should not exceed 128 characters in length.";
            $error_out = true;
        }

        if (strlen($email) < 1) 
        {
            $email_msg = "A valid email address is required.";
            $error_out = true;
        }


    }



    $dbh = new PDO("mysql:dbname=MoveM;host={$dbhostname}" , $dbusername, $dbpassword);

    if (!isset($_REQUEST["action"]))
    {
        /* if this is not set, then this is a cold session, all we need to do is
            to display the form ready for filling out */

        $action = "INSERT";
        
        /* for csrf protection, the nonce will be formed from a hash of several variables that
            that make up the session, concatenated, along with some random salt (defined above) */
        $token = $action . "org.php" . $csrf_salt;
        $csrf_nonce = hash("sha256", $token);
    }
    else if (($_POST["action"] == "UPDATE") && !isset($error_out))
    {
        $orgid = $_POST["orgid"];

        /* this is an update, so must do a save */
        $action = "UPDATE";

        $stmt = $dbh->prepare("UPDATE org SET name = :org_name, person_name = :person_name, website = :website, money_url = :money_url WHERE orgid = :orgid; " .
            "UPDATE org SET email_unverified = :email WHERE orgid = :orgid AND email_verified IS NOT NULL AND email_verified != :email; " . 
            "UPDATE org SET email_unverified = :email WHERE orgid = :orgid AND email_verified IS NULL AND email_unverified != :email; " );
            /* first query doesn't update email because it might get updated in a different window/browser/device */
            /* if email is changed, and it was previously verified, then set it to unverified */
            /* OR if email is changed, and it was not previouly verified, then changed it and keep it unverified */
        $stmt->bindParam(':org_name', $_POST["org_name"]);
        $stmt->bindParam(':person_name', $_POST["person_name"]);
        $stmt->bindParam(':email', $_POST["email"]);
        //$stmt->bindParam(':pwhash', "asdf");
        $stmt->bindParam(':website', $_POST["org_website"]);
        $stmt->bindParam(':money_url', $_POST["money_url"]);
        $stmt->bindParam(':orgid', $orgid);
		$stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            echo "Error code:<br>";
            $erinf = $stmt->errorInfo();
            die("Insert failed<br>" . $erinf[2]); /* the error message in the returned error info */
        }

        /* TODO: detect email change and add verification email */
    }
    else if (!isset($error_out))
    {
        //$action = "INSERT";

        /* TODO: first check to see if a record under this email address already exists */

        /* this is a new record, so do the insert */
        $stmt = $dbh->prepare("INSERT INTO org (name, person_name, email_unverified, pwhash, website, money_url)" 
            . " VALUES (:org_name, :person_name, :email, 'asdf', :website, :money_url);");
        
        $stmt->bindParam(':org_name', $_POST["org_name"]);
        $stmt->bindParam(':person_name', $_POST["person_name"]);
        $stmt->bindParam(':email', $_POST["email"]);
        //$stmt->bindParam(':pwhash', "asdf");
        $stmt->bindParam(':website', $_POST["org_website"]);
        $stmt->bindParam(':money_url', $_POST["money_url"]);

        //$stmt->debugDumpParams();

		$stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            echo "Error code:<br>";
            $erinf = $stmt->errorInfo();
            die("Insert failed<br>" . $erinf[2]); /* the error message in the returned error info */
        }


        $orgid = $dbh->lastInsertId();

        if (!isset($orgid)) 
        {
            die("Oops...failed to get the insert Id.");
            /* TODO: much better/cleaner handling of errors */
        }

        /* TODO: add verification email */

    }

    if (($orgid != 0) && (!isset($error_out)))  /* fall through either from a retrieve or from an insert or an update
        to populate the fields in the form */
    {

        $stmt = $dbh->prepare("SELECT orgid, name, person_name, email_verified, email_unverified, website, money_url FROM org WHERE orgid = :orgid ;");
        $stmt->bindParam(':orgid', $orgid);

        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);


        if (isset($row))
        {
            $org_name = $row["name"];
            $person_name = $row["person_name"];
            

            $email_verified = $row["email_verified"];
            $email_unverified = $row["email_unverified"];
            

            $website = $row["website"];
            $money_url = $row["money_url"];
            $action = "UPDATE";

            $token = $action . "org.php" . $csrf_salt;
            $csrf_nonce = hash("sha256", $token);
        }
        else
        {
            die("Oops, no organization found with that Id.");
            /* TODO: much better/cleaner handling of errors */
        }
    }
    else if (isset($error_out))
    {
        /* an error was encountered, so repopulate the fields from the POST */
        $email_verified = $_POST["email"]; /* TODO: don't know if the email is verified or not yet */
        $person_name = $_POST["person_name"];
        $org_name = $_POST["org_name"];
        

        $website = $_POST["org_website"];
        $money_url = $_POST["money_url"];
        $action = "UPDATE";

        $token = $action . "org.php" . $csrf_salt;
        $csrf_nonce = hash("sha256", $token);

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

<div id="page1">
    <center>
        <h3 id="header">Tell us a bit about yourself first</h3>
    </center>

    <div class="alert alert-success" hidden="true" id="general_alert_msg" ></div>

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
            echo "<input class='form-control' type='email' id='email' maxlength='128' name='email' value='";
            if (isset($email_verified))
            {
                echo $email_verified; 
                echo "' />\n";
            }
            else if (isset($email_unverified))
            {
                echo $email_unverified; 
                echo "' />\n";
            }

            if (isset($email_unverified))
            {
                echo "<div class='alert alert-info' id='email_unverified_msg' >The email address: ";
                echo $email_unverified;
                echo " has not been verified yet.</div>\n";
            }
        ?>
    </div> <!-- form-group -->
 
    <div class="alert alert-danger" <?php if (!isset($email_msg)) echo "hidden='true'"; ?> id="email_invalid_msg" >
        <?php echo $email_msg; ?>
    </div>

    <ul class="pager">
        <!-- <li><a href="#" id="" >Save data</a></li> -->
        <li><a href="#" id="p1_goto_p2" >Next</a></li>
    </ul> 

</div> <!-- page 1 -->

<div id="page2" hidden="true" >
    <center><h3 id="header">Tell us about the organization</h3></center>

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

    <div class="alert alert-danger" hidden="true" id="website_invalid_msg" >
        The web site does not look like a valid URL.
    </div>

    <div class="form-group">
        <label for="money_url">Donations website:</label>
        <input class="form-control" type="url" id="money_url" maxlength="255" name="money_url" value="<?php echo $money_url ?>" />
    </div> <!-- form-group -->

    <div class="alert alert-danger" hidden="true" id="donations_invalid_msg" >
        The donations site does not look like a valid URL.
    </div>


    <ul class="pager">
        <li><a href="#" id="p2_goto_p1" >Previous</a></li>
        <li><a href="#" id="p2_goto_p3" >Next</a></li>
    </ul> 

</div> <!-- page 2 -->

<div id="page3" hidden="true" >
    <center><h3 id="header">Tell us about the types of people you are looking for</h3></center>

    <ul class="pager">
        <li><a href="#" id="p3_goto_p2" >Previous</a></li>
        <li><a id="save_data" href="#">Save data</a></li>
    </ul> 


</div> <!-- Page 3 -->


</form>

</div> <!-- Container fluid -->


</body>
</html>

