<?php
include_once 'inc/dbconfig.php';
include_once 'inc/config.php';

    if ( session_id() == '' ) { session_start(); $_SESSION['loggedin'] = 'N'; }

    if ( $_SERVER['REQUEST_METHOD'] != 'POST' ) {
        header("location:/"); 
    } else {

        dbStart();

        $count = 0;

        if ( isset($_POST['login']) ) {
            $username=prepDBVal($_POST['username']); 
            $password=prepDBVal($_POST['password']);

            $sql="SELECT * FROM users WHERE username='$username' AND active='Y' AND passwd=AES_ENCRYPT('$password', '".$GLOBALS['pwsalt']."')";
            $result=mysql_query($sql);

            $count=mysql_num_rows($result);
        }

        if( $count == 1 ) {
            $row = mysql_fetch_assoc($result);
            $_SESSION['username']= $row['username'];
            $_SESSION['uid'] = $row['idusers'];
            $_SESSION['useremail'] = $row['email'];
            $_SESSION['isadmin'] = $row['admin'];
            $_SESSION['loggedin'] = 'Y';
            if (isset($_POST['rememberme']) && $_POST['rememberme'] == 'Y') {
                setcookie('registered', $username, time()+60*60*24*120, '/', '.framealbum.com');
            } else {
                setcookie('registered', '', time()-3600, '/', '.framealbum.com');
            }

            $sql="UPDATE users SET last_login=now() WHERE idusers=".$_SESSION['uid'];
            $result=mysql_query($sql);
            if( isset( $_POST['redir'] ) ) {
                header("location:" . urlencode( $_POST['redir'] ) );
            } else {
                header("location:/usermain.php");
            }
        } else {
            $msg = 'Wrong Username or Password.';

            unset($_SESSION['username']);
            unset($_SESSION['uid']);;
            unset($_SESSION['useremail']);
            unset($_SESSION['isadmin']);
            unset($_SESSION['loggedin']);

            session_unset();
            session_destroy();
            header("location:/login.php?msg=" . urlencode($msg) );
        }
    }
?> 
