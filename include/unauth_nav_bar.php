<?php

    /* the calling page should have initialized a variable called "highlight_tab" to
    tell the nav bar which tab to highlight
    In case it's not set, initialize it */
    
    if (!isset($highlight_tab))
    {
        $highlight_tab = "";
    }
?>

<nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
      <img src="img/mmlogo.png" alt="Movement Match Logo" width="55" height="55" ></img>
    </div>
    <div class="navbar-header">
      <p class="navbar-brand">&nbsp;Movement Match</p>
    </div>
    <ul class="nav navbar-nav navbar-right">
        <li><a href="index.php">Public Site</a></li>
      <li <?php echo ($highlight_tab == "FORGOT" ? "class='active'" : ""); ?> ><a href="forgotPassword.php">Forgot Password?</a></li>
      <li <?php echo ($highlight_tab == "LOGIN" ? "class='active'" : ""); ?>><a href="login.php"><span class="glyphicon glyphicon-log-in"></span>&nbsp;Log In</a></li>
    </ul>
  </div>
</nav>