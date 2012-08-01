<?php
include_once 'inc/dbconfig.php';
include_once 'inc/config.php';
include_once 'inc/helpers.php';
include_once 'inc/helper_frame.php';

    if (session_id() == '') { session_start(); }

    if ( !( isset( $_SESSION['username'] ) ) ) {
        header('Location:/login.php?msg=' . urlencode("You must be logged in to activate a frame.") . '&redir=' . urlencode( 'activate.php' ) );
    }

    dbStart();

#---------------------------
function doGET( $msg, $akey )
#---------------------------
{
    $html = '';
    $redir = '';

    $html .= '<div class="body_textarea"><p>The frame activation key is displayed on your frame after it has connected to the FrameAlbum service.</p></div>';
    $html .= '<div class="body_textarea"><p>If your frame is not displaying a FrameAlbum activation key you will need to modify your DNS server settings to connect to the FrameAlbum service.</p></div>';
    $html .= '<div class="body_textarea">';
    $html .= '<form id="register" onsubmit="validateForm();" name="register" method="post" action="#">';
    $html .= '<input type="hidden" name="stage" value="2">';
    $html .= '<table border="0">';
    $html .= '<tr><td>Activation Key:</td><td><input type="text" maxlength="64" size="32" name="akey" id="akey" value="'.$akey.'"></td></tr>';
    $html .= '</table>';
    $html .= '</div>';
    $html .= '<div align="center"><input type="submit" value=" Activate " name="activate" /></div>';
    $html .= '</form>';

    return array ($msg, $html, $redir);
}

#---------------------------
function doPOST( $msg )
#---------------------------
{
    if (isset($_POST['akey'])) { $akey = $_POST['akey']; } else { $akey = ''; }

    $html = '';
    $redir = '';
    $errs = 0;

    list ($fid, $uid) = frameCheckActivationKey( $akey );

    if ( $fid == 0 ) {               # no frame with that key
        list ( $msg, $html, $redir ) = doGet( $msg, $akey );
        $msg = 'Sorry, that activation key is not valid.';
        $errs++;
    } else if ( ( $uid != 0 ) and ( $uid != $GLOBALS['PUB_CHAN_USERID'] ) ) {               # already claimed?
        list ( $msg, $html, $redir ) = doGet( $msg, $akey );
        $msg = 'Sorry, that frame has already been activated.';
        $errs++;
    } else {
        $msg = frameActivateKey( $akey, $_SESSION['uid'] );
        $redir = urlencode( '/frame.php?fid=' . $fid . '&msg=' . $msg );
    }

    return array ( $msg, $html, $redir );
}

#---------------------------
# M A I N
#---------------------------
    if ( isset($_REQUEST['akey']) ) { $akey=$_REQUEST['akey']; } else { $akey = ''; }
    if ( isset($_REQUEST['msg']) ) { $msg=$_REQUEST['msg']; } else { $msg = ''; }

    $msg = '';

    $errs = 0;
    $body = '';

    if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
        list ( $msg, $body, $redir ) = doPOST( $msg );
    } else {
        list ( $msg, $body, $redir ) = doGET($msg, $akey);
    }

    if ( isset( $redir ) and ( strlen( $redir ) > 0 ) ) {
        header( 'Location:'.$redir );
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
    include_once 'validate.inc';
?>
</head>

<body onload="mpmetrics.track('ActivationProcess');">
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

    <div class="head">Activate a Frame </div>

<?php
    if ( isset( $body ) ) { echo '<div class="body_textarea"><div align="justify">' . $body . '</div></div>'; }
?>
  </div>
<!-- end of 'midarea' DIV -->

<?php
    include_once "right.inc";
?>
<!-- end of 'right' DIV -->

<?php
    include_once 'footer.inc';
?>

</body>
</html>
