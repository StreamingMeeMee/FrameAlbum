<?php
include_once 'inc/dbconfig.php';
include_once 'inc/config.php';
include_once 'inc/helpers.php';

    if (session_id() == '') { session_start(); }

    if( $_SESSION['loggedin'] == 'Y' ) {
        $msg = 'Logged out';
    } else {
        $msg = 'Not logged in.';
    }

    $_SESSION = array ();
    $sname = session_name();
#    unset( $_SESSION['username'] );
#    unset( $_SESSION['isadmin'] );
#    unset( $_SESSION['uid'] );
#    unset( $_SESSION['loggedin'] );
#    session_unset();
    session_destroy();

    if ( isset( $_COOKIE[ $sname ] ) ) {            # expire the session cookie
        setcookie( $sname, '', time()-3600, '/' );
    }
    
    header('Location:/?msg=' . $msg);
?>
