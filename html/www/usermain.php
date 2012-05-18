<?php
include_once 'inc/dbconfig.php';
include_once 'inc/config.php';
include_once 'inc/helpers.php';
include_once 'inc/frame_helpers.php';
include_once 'inc/chan_helpers.php';
include_once 'inc/helper_user.php';

    if (session_id() == '') { session_start(); }

    if (!(isset($_SESSION['username']))) {
        header('Location:/');
    }

    dbStart();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>FrameAlbum (beta) - A FrameChannel service replacement</title>
<link href="style.css" rel="stylesheet" type="text/css" />
<meta name="google-site-verification" content="USZZVnxSIdwFV1Pw7m2t41JqbFAHguWRvNGvzLDlvIM" />
<?php
    include_once 'js.inc';
?>
</head>

<body onLoad="mpmetrics.track('UserMain');">
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
<?php
    $uid = userFind($_SESSION['username']);

    echo '<div class="body_title">Your Frames</div>';
    echo '<div class="body_textarea">' . frameUserEnumHTML( $uid ) . '</div>';

    echo '<div class="body_title">Your Channels</div>';
    echo '<div class="body_textarea">' . channelUserEnumHTML( $uid ) . '</div>';
?>
  </div>
<!-- end of 'midarea' DIV -->

  <div class="right">
    <div class="comments_area"></div>
  </div>
</div>
<?php
    include_once 'footer.inc';
?>
</body>
</html>
