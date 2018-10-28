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
    $highlight_tab = "USERS";
    
    session_start();
    validateAuthorization();
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
           error_log("Error getting user list: " . $e->getMessage());
	       header("Location: login.php?errmsg=true");
	       exit();
	    
	}
}


function validateAuthorization()
{
    
    if (!array_key_exists("admin_user_ind", $_SESSION))
    {
        throw new Exception(USER_NOT_LOGGED_IN_ERROR);
        exit;
    }
        
    if ($_SESSION["admin_user_ind"] == FALSE)
    {
        throw new Exception("Requested data for unauthorized user ID. Possible parameter tampering.");
        exit;
    }
}

function dumpResults()
{
    global $dbh;
    
    $stmt = $dbh->prepare("CALL selectUserList();");
    
    $stmt->execute();
    
    if ($stmt->errorCode() != "00000") 
    {
        $erinf = $stmt->errorInfo();
		error_log("Query failed in userList.php: " . $stmt->errorCode() . " " . $erinf[2]);
		throw new Exception("An unknown error was encountered (10). Please attempt to reauthenticate.");
        exit();
    }
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
    {
        printf("<tr>\n<td>%s</td>\n", htmlentities($row["person_name"]));
        printf("<td>%s</td>", htmlentities($row["email"]));
        printf("<td>%s</td>",($row["admin_user_ind"] ? "Yes" : "No"));
        printf("<td>%s</td>",($row["active_ind"] ? "Yes" : "No"));
        printf("<td><a href='user.php?user_id=%u' class='btn btn-default btn-lg'>", $row["user_id"]);
        printf("<span class='glyphicon glyphicon-pencil'></span> Edit</a></td>\n</tr>\n");
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
    <title><?php echo $site_brand, "&nbsp-&nbsp;", LOCALITY_NAME; ?> - Users</title>
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
    <h2>User List</h2>
</div>
</center>

  <table class="table table-hover">
    <thead>
      <tr>
        <th>Name</th>
        <th>Email Address</th>
        <th>Administrator?</th>
        <th>Enabled?</th>
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

