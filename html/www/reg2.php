<?php
include_once 'inc/dbconfig.php';
include_once 'inc/config.php';
include_once 'inc/helpers.php';
include_once 'inc/helper_user.php';

    if (session_id() == '') { session_start(); }

    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        header("location:/"); 
    } else {
        dbStart();

        if ( isset($_POST['login']) ) {
//            ob_start();
            $username=prepDBVal($_POST['username']); 
            $password=prepDBVal($_POST['password']);

            $sql="SELECT * FROM users WHERE username='$username' and passwd=AES_ENCRYPT('$password', '".$GLOBALS['pwsalt']."')";
            $result=mysql_query($sql);

            $count=mysql_num_rows($result);

            if($count==1){
                $_SESSION['username']=$username;
//                if (isset($_POST['rememberme']) && $_POST['rememberme'] == 'Y') {
//                    setcookie('username', $username, mktime(time()+60*60*24*120), '/', '.framealbum.com');
//                }
                header("location:/usermain.php");
            } else {
                $body = "<p>Wrong Username or Password.</p>";
            }
//            ob_end_flush();
        } else if ( isset($_POST['signup']) ) {
            $email=$_REQUEST['reg_email'];
            if ($email == 'Email Id') { $email = ''; }

            if (strlen($email) != 0) {
                if ( userFindByEmail($email) > 0 ) {
                    $body = "<p>Sorry, that email address is already registered -- please enter a unique email address to register for the beta.</p>";
                } else {
                    if ( userAdd($email, md5('jammin425'), $email, '', 'P') ) {
                        $body = "<p>Welcome!  You are now registered for the FrameAlbum service beta.  We'll let you know when were open for early adopters.</p>";
                        setcookie('betaregister', $email, mktime(time()+60*60*24*120), '/', '.framealbum.com');
                    } else {
                        $body = "<p>Sorry, something went pear-shaped with the registration.  Please try again with a different email address.</p>";
                    }
                }
            } else {
                $body = "<p>Sorry, I didn't quite get that -- please enter an email address to register for the beta.</p>";
            }
        } else {
            header("location:/");
        }
    } 

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>FrameAlbum (beta) - A FrameChannel service replacement</title>
<link href="style.css" rel="stylesheet" type="text/css" />
<?php
    include_once 'js.inc';
?>
</head>

<body onLoad="mpmetrics.track('RegisterProcess');">
<form id="form1" name="form1" method="post" action="reg2.php">
<?php
    include_once "topheader.inc";
    include_once "search_strip.inc";
?>
<div id="body_area">
<?php
    include_once "left.inc";
?>
<!-- end of 'left' DIV -->

  <div class="midarea">
    <div class="head">Register </div>
    <div class="body_textarea">
        <div align="justify">

<?php        if ( isset($body) ) { echo $body; } ?>

        </div>
    </div>
  </div>
<!-- end of 'midarea' DIV -->

<?php
    include_once "right.inc";
?>
<?php
    include_once 'footer_home.inc';
?>

</body>
</html>
