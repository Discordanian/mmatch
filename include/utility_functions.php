<?php

function my_session_start()
{
    /* Must do this to keep the session cookie from expiring on the client */
    /* this is based off on an article on stackoverflow */
    session_start();
    $_sess_name = session_name();
    $_sess_id = session_id();
    /* Reissue cookie to expire in 4 hours */
    setcookie($_sess_name, $_sess_id, time() + (60 * 60 * 4), "/");    
}

?>
