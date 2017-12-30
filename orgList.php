<?php

require_once('include/inisets.php');
require_once('include/secrets.php');
require_once('include/initializeDb.php');


/* TODO: This code may be vulnerable to XSS 
and probably a bunch of other attacks
Needs serious security review */

try
{
    session_start();
    validatePostData();
    initializeDb();
}
catch(Exception $e)
{
	switch ($e->getMessage())
	{
	    case DUPLICATE_ORG_NAME_ERROR : 
	       $org_name_msg = "The organization name entered was a duplicate.";
	       $goto_page = -1;
	    break;
	    case USER_NOT_LOGGED_IN_ERROR:
	       header("Location: login.php?errmsg=USER_NOT_LOGGED_IN_ERROR");
	       exit();
        break;
	    default:
           error_log("Error getting organization list: " . $e->getMessage());
	       header("Location: login.php?errmsg=true");
	       exit();
	    
	}
}


function validatePostData()
{
    global $user_id;
    
    if (!array_key_exists("user_id", $_GET) || !isset($_GET["user_id"]))
    {
        throw new Exception("Required user ID was not supplied.");
        exit;
    }
    
    $user_id = filter_var($_GET["user_id"], FILTER_SANITIZE_NUMBER_INT);
    
    if (!array_key_exists("user_id", $_SESSION))
    {
        throw new Exception(USER_NOT_LOGGED_IN_ERROR);
        exit;
    }
    
    if ($user_id != $_SESSION["user_id"])
    {
        throw new Exception("Requested data for unauthorized user ID. Possible parameter tampering.");
        exit;
    }
}

function dumpResults()
{
    global $user_id, $dbh;
    
    $stmt = $dbh->prepare("CALL selectOrganizationList(:user_id);");
    $stmt->bindValue(":user_id", $user_id);
    
    $stmt->execute();
    
    if ($stmt->errorCode() != "00000") 
    {
        $erinf = $stmt->errorInfo();
		error_log("Query failed in orgList.php: " . $stmt->errorCode() . " " . $erinf[2]);
		throw new Exception("An unknown error was encountered (10). Please attempt to reauthenticate.");
        exit();
    }
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
    {
        echo "<tr>\n";
        echo "<td>";
        echo htmlentities($row["org_name"]);
        echo "</td>\n<td>";
        echo htmlentities($row["abbreviated_name"]);
        echo "</td>\n<td><a href='org.php?orgid=";
        echo $row["orgid"];
        echo "' class='btn btn-default btn-lg'>Edit</a></td>\n</tr>\n";
 
    }
    
    $stmt->closeCursor();
    
}

//echo "<!-- \n";
//var_dump($_SESSION["orgids"]);
//echo "\n --> \n";

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
    <!-- <script src="js/orgList.js"></script> -->
  
</head>

<body>

<div class="container-fluid">

<center>
<div class="page-header">
    <h1>Movement Match</h1>
    <h2>Organization List</h2>
</div>
</center>
<a href="org.php" class="btn btn-default btn-lg" >Create a new Organization record</a>
<a href="login.php?errmsg=SUCCESSFULLY_LOGGED_OFF" class="btn btn-default btn-lg" >Log Off</a>

  <table class="table table-hover">
    <thead>
      <tr>
        <th>Organization Name</th>
        <th>Abbreviation</th>
        <th> </th>
      </tr>
    </thead>
    <tbody>
    
    <?php dumpResults(); ?>
    
    </tbody>
  </table>

  <?php require('include/footer.php'); ?>

</div> <!-- Container fluid -->


</body>
</html>

