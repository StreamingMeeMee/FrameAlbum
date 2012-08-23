<?php
    include_once "inc/config.php";
    include_once "inc/helpers.php";

    list ( $fbuser, $fb_btn ) =  loginInit( );

    if( featureEnabled( 'enable_fb_login' ) and ( $_SESSION['fblogin'] == 'Y' ) ) {
        $_SESSION['loggedin'] = 'Y';
        header( "Location:/usermain.php" );
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>FrameAlbum (beta) - A FrameChannel service replacement</title>
<link href="style.css" rel="stylesheet" type="text/css" />
<?php
    include_once 'js.inc';
?>
</head>

<body onLoad="mpmetrics.track('Login');">
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

    <div class="head">Login</div>
<?php
#    if (isset($_REQUEST['msg'])) { echo '<div class="body_message"><p>' . $_REQUEST['msg'] . '</p></div>'; }
?>
    <div class="body_textarea">
      <div align="justify">Login by entering your username and password to the right and then hit 'Login'.</div>
   </div>
   <div class="body_textarea">
      <div align="justify">If you are new to FrameAlbum, enter your email address in the 'Register' section on the right and hit 'Signup'.</div>
    </div>
  </div>
<!-- end of 'midarea' DIV -->

<!-- right DIV -->
<?php
    include_once "right.inc";
?>
<!-- end of 'right' DIV -->
<?php
    include_once 'footer.inc';
?>
</body>
</html>
