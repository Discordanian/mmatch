<nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
      <img src="img/mmlogo.png" alt="Movement Match Logo" width="55" height="55" ></img>
    </div>
    <div class="navbar-header">
      <p class="navbar-brand">&nbsp;Movement Match</p>
    </div>
    <ul class="nav navbar-nav">
    
<?php

    /* the calling page should have initialized a variable called "highlight_tab" to
    tell the nav bar which tab to highlight
    In case it's not set, initialize it */
    
    if (!isset($highlight_tab))
    {
        $highlight_tab = "";
    }
    
    $my_user_id = $_SESSION["my_user_id"];

    
    echo "<li" . ( $highlight_tab == "ORGS" ? " class='active' " : "");
    echo "><a href='orgList.php?user_id=$my_user_id'>Organizations</a></li>\n";
    echo "<li" . ( $highlight_tab == "NEWORG" ? " class='active' " : "");
    echo "><a href='org.php?user_id=$my_user_id'>New Organization</a></li>\n";
      
    if ($_SESSION["admin_user_ind"] == TRUE) 
    {
        echo "<li" . ( $highlight_tab == "USERS" ? " class='active' " : "");
        echo "><a href='userList.php'>Users</a></li>\n";
        echo "<li" . ( $highlight_tab == "NEWUSER" ? " class='active' " : "");
        echo "><a href='user.php'>New User</a></li>\n";
    } 
?>
    </ul>      

    <ul class="nav navbar-nav navbar-right">
        <li><a href="index.php">Public Site</a></li>
      <li <?php echo ($highlight_tab == "PROFILE" ? " class='active' " : ""); ?> >
      <a href="user.php?user_id=<?php echo $my_user_id; ?>"><span class="glyphicon glyphicon-user"></span>Profile</a></li>
      <li><a href="login.php?errmsg=SUCCESSFULLY_LOGGED_OFF"><span class="glyphicon glyphicon-log-out"></span>&nbsp;Log Out</a></li>
    </ul>
  </div>
</nav>
