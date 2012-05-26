<?php
include_once 'inc/dbconfig.php';
include_once 'inc/config.php';
include_once 'inc/helpers.php';

    if (session_id() == '') { session_start(); }

    if (!(isset($_SESSION['username']))) {
        header('Location:/');
    } else {
        unset($_SESSION['username']);
        unset($_SESSION['isadmin']);
        unset($_SESSION['uid']);
        session_unset();
        session_destroy();
    
        header('Location:/');
    }
?>
