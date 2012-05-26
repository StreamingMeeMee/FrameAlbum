<?php

    ob_start();
    session_start();
    unset($_SESSION['phpFlickr_auth_token']);
    $_SESSION['uid'] = 1;

    header("Location: /tcallback.php?id=".$_SESSION['uid']);
 
?>
