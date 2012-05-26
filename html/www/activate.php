<?php
include_once 'inc/dbconfig.php';
include_once 'inc/config.php';
include_once 'inc/helpers.php';
include_once 'inc/helper_frame.php';

    if (session_id() == '') { session_start(); }

    if (!(isset($_SESSION['username']))) {
        header('Location:/login.php?msg=You must be logged in to activate a frame.');
#        header('Location:/login.php');
    }

    dbStart();

#---------------------------
function doGET($akey)
#---------------------------
{
    $msg = '';
    $html = '';
    $redir = '';

    $html .= '<div class="body_textarea"><p>The frame activation key is displayed on your frame after it has connected to the FrameAlbum service.</p></div>';
    $html .= '<div class="body_textarea"><p>If your frame is not displaying a FrameAlbum activation key you will need to modify your DNS server settings to connect to the FrameAlbum service.</p></div>';
    $html .= '<input type="hidden" name="stage" value="2">';
    $html .= '<table border="0">';
    $html .= '<tr><td>Activation Key:</td><td><input type="text" maxlength="64" size="32" name="akey" id="akey" value="'.$akey.'"></td><td><div><img id="akeymsg" height="24" src="/images/knobs/Grey.png"</div></td></tr>';
    $html .= '</table>';
    $html .= '<div align="center"><input type="submit" value=" Activate " name="activate" /></div>';

    return array ($msg, $html, $redir);
}

#---------------------------
function doPOST()
#---------------------------
{
    if (isset($_POST['akey'])) { $akey = $_POST['akey']; } else { $akey = ''; }

    $msg = '';
    $html = '';
    $redir = '';
    $errs = 0;

    list ($fid, $uid) = frameCheckActivationKey($akey);

    if ( $fid == 0 ) {               # no frame with that key
        list ($msg, $html) = doGet($akey);
        $msg = 'Sorry, that activation key is not valid.';
        $errs++;
    } else if ( ($uid != 0) and ($uid != $GLOBALS['PUB_CHAN_USERID']) ) {               # already claimed?
        list ($msg, $html) = doGet($akey);
        $msg = 'Sorry, that frame has already been activated.';
        $errs++;
    } else {
        $msg = frameActivateKey($akey, $_SESSION['uid']);
        $redir = '/frame.php?fid='.$fid.'&msg='.$msg;
    }

    return array ($msg, $html, $redir);
}

#---------------------------
# M A I N
#---------------------------
    if (isset($_REQUEST['akey'])) { $akey=$_REQUEST['akey']; } else { $akey = ''; }
    if (isset($_REQUEST['msg'])) { $msg=$_REQUEST['msg']; } else { $msg = ''; }

    $errs = 0;
    $body = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        list ($msg, $body, $redir) = doPOST();
    } else {
        list ($msg, $body, $redir) = doGET($akey);
    }

    if ( isset($redir) ) {
        header('Location:'.$redir);
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
<script language="JavaScript" type="text/JavaScript">
function validPasswd()
{
    var userRegex = /^[\w\.]{6,64}$/;
    var pwd1 =  document.getElementById('reg_passwd1').value;
    var pwd2 =  document.getElementById('reg_passwd2').value
    var validPwd1 = document.getElementById('reg_passwd1').value.match(userRegex);
    var validPwd2 = document.getElementById('reg_passwd2').value.match(userRegex);

    if ( validPwd1 && validPwd2 && (pwd1 == pwd2) ) {
        document.getElementById('passwdmsg').src='/images/knobs/Valid_Green.png';
        return true;
    } else {
        document.getElementById('passwdmsg').src='/images/knobs/Attention.png';
        return false;
    }
}

function validUsername()
{
    var userRegex = /^[\w\.]{6,64}$/;
    var validUname = document.getElementById('reg_username').value.match(userRegex);
  
    if (validUname) {
        document.getElementById('usernamemsg').src='/images/knobs/Valid_Green.png';
        return true;
    } else {
        document.getElementById('usernamemsg').src='/images/knobs/Attention.png';
        return false;
    }
}

function validZip()
{
    var zipfield=document.getElementById('reg_zip');
    var zip=zipfield.value;
    if (zip.length == 0) {           // field is blank, OK
        return true;
    }
    if (zip.match(/^[0-9]{5}$/)) {
        document.getElementById('zipmsg').src='/images/knobs/Valid_Green.png';
        return true;
    }
    zip=zip.toUpperCase();
    if (zip.match(/^[A-Z][0-9][A-Z][0-9][A-Z][0-9]$/)) {
        document.getElementById('zipmsg').src='/images/knobs/Valid_Green.png';
        return true;
    }
    if (zip.match(/^[A-Z][0-9][A-Z].[0-9][A-Z][0-9]$/)) {
        document.getElementById('zipmsg').src='/images/knobs/Valid_Green.png';
        return true;
    }
    document.getElementById('zipmsg').src='/images/knobs/Attention.png';    
    return false;
}

function validateForm()
{
    var valid = validUsername();

    valid = (valid && validPassword() );
    valid = (valid && validZip() );
    valid = (valid && validEmail() );

    return valid;
}
</script>
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

<form id="register" onsubmit="validateForm();" name="register" method="post" action="#">

<?php
#    if ( isset($msg) and (strlen($msg) > 0) ) { echo '<div class="body_message">' . $msg . '</div>'; }

    if ( isset($body) ) { echo '<div class="body_textarea"><div align="justify">' . $body . '</div></div>'; }
?>
</form>
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
