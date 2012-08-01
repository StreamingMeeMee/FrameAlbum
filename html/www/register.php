<?php
include_once 'inc/dbconfig.php';
include_once 'inc/config.php';
include_once 'inc/helpers.php';
include_once 'inc/helper_user.php';

    if (session_id() == '' ) { session_start(); }

    if (isset($_SESSION['username']) ) {               # already logged in -- bail
        header("Location:/");
    }

    dbStart();

#---------------------------
function doGET($tok, $username, $email, $zip)
#---------------------------
{
    $msg = '';
    $html = '';

    list ( $msg, $html ) = userRegForm( $tok, $username, $email, $zip );

    return array ( $msg, $html );
}

#---------------------------
function doPOST()
#---------------------------
{
    if (isset($_POST['tok'])) { $tok = $_POST['tok']; } else { $tok = ''; }
    if (isset($_POST['reg_username'])) { $username = $_POST['reg_username']; } else { $username = '?'; }
    if (isset($_POST['reg_passwd1'])) { $passwd = $_POST['reg_passwd1']; }  else { $passwd = '?'; }
    if (isset($_POST['reg_email']))  { $email = $_POST['reg_email']; } else { $email = '?'; }
    if (isset($_POST['reg_zip']))    { $zip = $_POST['reg_zip'];     } else { $zip = '?'; }

    $msg = '';
    $html = '';
    $redir = '';

    if (!isset($_POST['stage']) or ($_POST['stage'] == 1) ) {               # we only have email during stage1
        list( $msg, $html ) = userRegForm( $tok, $username, $email, $zip );
    } else {
        list ($msg, $html, $redir ) = userRegFormProc( $tok, $username, $email, $zip, $passwd );
    }

    return array ($msg, $html, $redir);
}

#---------------------------
# M A I N
#---------------------------
    if (isset($_REQUEST['tok'])) { $tok=$_REQUEST['tok']; } else { $tok = ''; }
    if (isset($_REQUEST['reg_email'])) { $email=$_REQUEST['reg_email']; } else { $email = ''; }
    if (isset($_COOKIE['betaregister']) and ($email == '')) { $email = $_COOKIE['betaregister']; }
    if (isset($_REQUEST['reg_username'])) { $username=$_REQUEST['reg_username']; } else { $username = ''; }
    if (isset($_REQUEST['reg_zip'])) { $zip=$_REQUEST['reg_zip']; } else { $zip = ''; }
    if (isset($_REQUEST['msg'])) { $msg=$_REQUEST['msg']; } else { $msg = ''; }

    if ((strlen($tok) == 0) and (!$GLOBALS['enable_register'])) {
        header("Location:/?msg=" . urlencode( "Registrations are currently disabled." ) );      # registrations are disabled
    }

    $body = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        list ($msg, $body, $redir) = doPOST();
    } else {
        list ($msg, $body) = doGET($tok, $username, $email, $zip);
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

<body onLoad="mpmetrics.track('RegisterProcess');">
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

<form id="register" onsubmit="validateForm();" name="register" method="post" action="#">
    <div class="head">Register </div>

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
