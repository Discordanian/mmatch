<?php

require_once('include/csp.php');
require_once('include/inisets.php');
require_once('include/secrets.php');
require_once('include/initializeDb.php');
require_once('include/utility_functions.php');


/* TODO: This code may be vulnerable to XSS 
and probably a bunch of other attacks
Needs serious security review */

try
{
    $highlight_tab = "ORGS";
    
    session_start();
    validatePostData();
    initializeDb();
}
catch(Exception $e)
{
	switch ($e->getMessage())
	{
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

    /* check for existence of proper session cookie */    
    if (!array_key_exists("my_user_id", $_SESSION))
    {
        throw new Exception(USER_NOT_LOGGED_IN_ERROR);
        exit;
    }
    
    if (!array_key_exists("user_id", $_GET) || strlen($_GET["user_id"]) == 0)
    {
        throw new Exception("Required user ID was not supplied.");
        exit;
    }
    
    $user_id = filter_var($_GET["user_id"], FILTER_SANITIZE_NUMBER_INT);
    
    if ($user_id != $_SESSION["my_user_id"] && $_SESSION["admin_user_ind"] == FALSE)
    {
        throw new Exception("Requested data for unauthorized user ID. Possible parameter tampering.");
        exit;
    }
}

function dumpResults()
{
    global $user_id, $dbh;
    
    $stmt = $dbh->prepare("CALL selectOrganizationList(:user_id);");

    /* if admin user, then don't pass user ID, show all orgs */
    if ($_SESSION["admin_user_ind"] == TRUE)
    {
        $stmt->bindValue(":user_id", NULL);
    }
    else
    {
        $stmt->bindValue(":user_id", $user_id);
    }
    
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
        printf("<tr>\n<td>%s</td>\n", htmlentities($row["org_name"]));
        printf("\t<td>%s<td>\n", htmlentities($row["abbreviated_name"]));
        printf("\t<td><a href='org.php?orgid=%u' class='btn btn-default btn-lg'><span class='glyphicon glyphicon-pencil'></span> Edit</a></td>\n", $row["orgid"]);
        printf("\t<td><a href='orgReport.php?orgid=%u' class='btn btn-default btn-lg' target='_blank' ><span class='glyphicon glyphicon-print'></span> Print</a></td>\n</tr>", $row["orgid"]);
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

<?php require('include/admin_nav_bar.php'); ?>

<div class="container-fluid">

<center>
<div class="page-header">
    <h2>Organization List</h2>
</div>
</center>

  <table class="table table-hover">
    <thead>
      <tr>
        <th>Organization Name</th>
        <th>Abbreviation</th>
        <th> </th>
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

