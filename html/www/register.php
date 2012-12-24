<?php
include_once 'inc/dbconfig.php';
include_once 'inc/config.php';
include_once 'inc/helpers.php';
include_once 'inc/helper_user.php';

    if (session_id() == '' ) { session_start(); }

    if ( isset( $_SESSION['loggedin'] ) and ( $_SESSION['loggedin'] == 'Y' ) ) {               # already logged in -- bail
        header("Location:/?msg=Already logged in");
    }

    list( $fbuser, $fb_btn ) =  loginInit();

    dbStart();

#---------------------------
function doGET($tok, $username, $email, $zip)
#---------------------------
{
    $msg = '';
    $html = '';

    if (strlen($tok) > 0) {
        list ($username, $email, $zip) = userGetInfoByToken($tok);
        $html .= '<input type="hidden" name="tok" value="'.$tok.'">';
    }

    $html .= '<input type="hidden" name="stage" value="2">';
    $html .= '<table border="0">';
    $html .= '<tr><td>Username:</td><td><input type="text" maxlength="64" size="32" name="reg_username" id="reg_username" value="'.$username.'" onblur="validUsername()"></td><td><div><img id="usernamemsg" height="24" src="/images/knobs/Grey.png"</div></td></tr>';
    $html .= '<tr><td>Password:</td><td><input type="password" maxlength="64" size="32" name="reg_passwd1" id="reg_passwd1" value="" onchange="validPasswd()"></td><td><div><img id="passwdmsg" height="24" src="/images/knobs/Grey.png"</div></td></tr>';
    $html .= '<tr><td>Confirm Password:</td><td><input type="password" maxlength="64" size="32" name="reg_passwd2" id="reg_passwd2" value="" onchange="validPasswd()"></td><td>&nbsp;</td></tr>';
    $html .= '<tr><td>&nbsp;</td><td><span id="passwdmsgtxt">&nbsp;</span></td></tr>';
    $html .= '<tr><td>Email address:</td><td><input type="text" maxlength="64" size="32" name="reg_email" id="reg_email" value="'.$email.'" onblur="validEmail()"></td><td><div><img id="emailmsg" height="24" src="/images/knobs/Grey.png"</div></td></tr>';
    $html .= '<tr><td>Home ZIP code:<br>(US & Canada only)</td><td><input type="text" maxlength="7" size="6" name="reg_zip" id="reg_zip" value="'.$zip.'" onblur="validZip()"></td><td><div><img id="zipmsg" height="24" src="/images/knobs/Grey.png"</div></td></tr>';
    $html .= '</table>';
    $html .= '<div align="center"><input type="submit" value=" Register " name="register" /></div>';

    if ( isset( $GLOBALS['enable_fb_register'] ) and ( $GLOBALS['enable_fb_register'] ) ) {
        if( isset( $_SESSION['fb_' . $GLOBALS['fb_api_key'] . '_user_id'] ) ) {
            $msg .= 'You are logged into Facebook in another browser window.  That Facebook user is already registered with FrameAlbum.';
        } else {
            $html .= '<p>OR</p>';
            $html .= '<iframe src="https://www.facebook.com/plugins/registration?
                 client_id=239342202765757&
                 redirect_uri=http%3A%2F%2Falpha.framealbum.com%2Ffb%2Fregreq.php%2F&
                 fields=name,location,email"
                scrolling="auto"
                frameborder="no"
                style="border:none"
                allowTransparency="true"
                width="100%"
                height="330">
                </iframe>';
        }
    }

    return array ($msg, $html);
}

#---------------------------
function doPOST()
#---------------------------
{
    if (isset($_POST['tok'])) { $tok = $_POST['tok']; } else { $tok = ''; }
    if (isset($_POST['reg_passwd1'])) { $passwd = $_POST['reg_passwd1']; }  else { $passwd = '?'; }
    if (isset($_POST['reg_email']))  { $email = $_POST['reg_email']; } else { $email = '?'; }
    if (isset($_POST['reg_username'])) { $username = $_POST['reg_username']; } else { $username = $email; }
    if (isset($_POST['reg_zip']))    { $zip = $_POST['reg_zip'];     } else { $zip = ''; }

    $msg = '';
    $html = '';
    $errs = 0;

    if (!isset($_POST['stage']) or ($_POST['stage'] == 1) ) {               # we only have email during stage1
        list ($msg, $html) = doGet($tok, $username, $email, $zip);
    } else {
        if (strlen($tok) > 0) {                             # existing user
            list ($o_username, $o_email, $o_zip) = userGetInfoByToken($tok);    # what was the original values?
            if ( ($o_username != $username) && (userFind($username) > 0) ) {
                $msg .= "<p>Sorry, that username is already registered -- please enter a unique username.</p>";
                $username = '';
                $errs++;
            }

            if ( ($o_email != $email) && (userFindByEmail($email) > 0 ) ) {
                $msg .= "<p>Sorry, that email address is already registered -- please enter a unique email address.</p>";
                $email = '';
                $errs++;
            }

            if ($errs == 0) {
                $ret =  userUpdate($tok, $username, $passwd, $email, $zip, 'Y');
                if ( $ret != 1) {
                    $msg .= "<p>Sorry, something went pear-shaped with the registration.  Please try again with a different username.</p>";
                    list ($d, $html) = doGet($tok, $username, $email, $zip);
                } else {
                    $html .= "<p>Okey dokey -- you are ready to login and start using FrameAlbum.</p>";
                }
            } else {
                list ($d, $html) = doGet($tok, $username, $email, $zip);
            }
        } else {
            if (strlen($email) != 0) {
                if ( userFindByEmail($email) > 0 ) {
                    $msg .= "<p>Sorry, that email address is already registered -- please enter a unique email address.</p>";
                    $email = '';;
                    list ($d, $html) = doGet($tok, $username, $email, $zip);
                    $errs++;
                }

                if ( userFind($username) > 0 ) {
                    $msg .= "<p>Sorry, that username is already registered -- please enter a unique username.</p>";
                    $username = '';
                    list ($d, $html) = doGet($tok, $username, $email, $zip);
                    $errs++;
                }

                if ($errs == 0) {
                    if ( userAdd($username, $passwd, $email, $zip, 'R') > 0 ) {
                        list ($d, $msg) = userSendWelcomeEmail($email);
                        $html .= "<p>Welcome!  You are now registered for the FrameAlbum service.  You will receive an email with details of the next step.</p>";
                        setcookie('registered', $username, ( time() + (60*60*24*120) ), '/', '.framealbum.com');
                    } else {
                        $msg .= "<p>Sorry, something went pear-shaped with the registration.  Please try again with a different email address.</p>";
                        list ($d, $html) = doGet($tok, $username, $email, $zip);
                    }
                }
            } else {
                $msg .= "<p>Sorry, I didn't quite get that -- please enter an email address to register.</p>";
                $email = '';
                list ($d, $html) = doGet($tok, $username, $email, $zip);
                $errs++;
            }
        }
    }

    return array ($msg, $html);
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

    if ( ( strlen( $tok ) == 0 ) and ( !$GLOBALS['enable_register'] ) ) {
        header("Location:/?msg=Registrations are currently disabled.");     # registrations are disabled
    }

    $errs = 0;
    $body = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        list ($msg, $body) = doPOST();
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
function validPasswd()
{
    var userRegex = /^[\w\.]{6,64}$/;
    var pwd1 =  document.getElementById('reg_passwd1').value;
    var pwd2 =  document.getElementById('reg_passwd2').value
    var validPwd1 = document.getElementById('reg_passwd1').value.match(userRegex);
    var validPwd2 = document.getElementById('reg_passwd2').value.match(userRegex);

    if ( validPwd1 && validPwd2 && (pwd1 == pwd2) ) {
        document.getElementById('passwdmsg').src='/images/knobs/Valid_Green.png';
        document.getElementById('passwdmsgtxt').innerText='';
        return true;
    } else {
        document.getElementById('passwdmsg').src='/images/knobs/Attention.png';
        if ( pwd1 == pwd2 ) {
            document.getElementById('passwdmsgtxt').innerText='Passwords are invalid; they must be at least 6 characters long.';
        } else {
            document.getElementById('passwdmsgtxt').innerText='Passwords do not match.';
        }
        return false;
    }
}

function validUsername()
{
    var userRegex = /^[\s\.]{6,64}$/;
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
