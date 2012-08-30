<?php
    include_once "inc/config.php";
    include_once "inc/dbconfig.php";
    include_once "inc/helpers.php";
    include_once "inc/helper_user.php";
    include_once "inc/user_class.php";

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
<script language="JavaScript" type="text/JavaScript">
function validPasswd()
{
    var userRegex = /^[\w\.]{6,64}$/;
    var pwd1 =  document.getElementById('reg_passwd1').value;
    var pwd2 =  document.getElementById('reg_passwd2').value
    var validPwd1 = document.getElementById('reg_passwd1').value.match(userRegex);
    var validPwd2 = document.getElementById('reg_passwd2').value.match(userRegex);

    if ( validPwd1 && validPwd2 && (pwd1 == pwd2) ) {
        document.getElementById('passwdmsg').src='/images/knobs/Valid_Green.png';;
        document.getElementById('chgpass').disabled = false;
        return true;
    } else {
        document.getElementById('passwdmsg').src='/images/knobs/Attention.png';
        document.getElementById('chgpass').disabled = true;
        return false;
    }
}
</script>
</head>

<?php
#---------------------------
function doGET( $dbh, $tok )
#---------------------------
{
    $msg = '';
    $html = '';

    if ( strlen( $tok ) > 0 ) {
        $uid = userGetUIDByToken( $tok );
        if( $uid > 0 ) {
            $u = new User( $dbh, $uid );
            $html .= '<div class="body_textarea">';
            $html .= '<div align="justify"><p>Please enter your new password.  Passwords must be at least 6 characters long.</p></div>';
            $html .= '<form method="post" action="#">';
            $html .= '<input type="hidden" name="uid" value="' . $uid . '">';
            $html .= '<table border="0">';
            $html .= '<tr><td>Password:</td><td><input tabindex="1" type="password" maxlength="64" size="32" name="reg_passwd1" id="reg_passwd1" value="" onchange="validPasswd()"></td><td><div><img id="passwdmsg" height="24" src="/images/knobs/Grey.png"</div></td></tr>';
            $html .= '<tr><td>Confirm Password:</td><td><input tabindex="2" type="password" maxlength="64" size="32" name="reg_passwd2" id="reg_passwd2" value="" onchange="validPasswd()"></td><td>&nbsp;</td></tr>';
            $html .= '</table>';
            $html .= '<div align="center"><input tabindex="3" type="submit" disabled="disabled" value=" Change Password " id="chgpass" name="chgpass" /></div>';
            $html .= '</div>';
        } else {
            $msg = 'Invalid token.';
            $html .= '<form method="post" action="#">';
            $html .= '<div class="body_textarea">';
            $html .= '<div align="justify">Sooo...  You have lost/forgotten/dog ate your password eh?  No Worries!  It happens to all of us.</div></div>';
            $html .= '<div class="body_textarea">
                <div align="justify">Simply enter your FrameAlbum username, or your registered email address and we will send you a link to reset your password.</div>
                </div>';
            $html .= '<div class="body_textarea">
                <div align="justify">Username or Email address:<input type="text" id="user" name="user"></div>
                </div>';

            $html .= '<div align="center"><input type="submit" value=" Reset My Password " name="resetpasswd" /></div>';
        }
    } else {
        $html .= '<form method="post" action="#">';
        $html .= '<div class="body_textarea">';
        $html .= '<div align="justify">Sooo...  You have lost/forgotten/dog ate your password eh?  No Worries!  It happens to all of us.</div></div>';
        $html .= '<div class="body_textarea">
            <div align="justify">Simply enter your FrameAlbum username, or your registered email address and we will send you a link to reset your password.</div>
            </div>';
        $html .= '<div class="body_textarea">
            <div align="justify">Username or Email address:<input type="text" id="user" name="user"></div>
            </div>';

        $html .= '<div align="center"><input type="submit" value=" Reset My Password " name="resetpasswd" /></div>';
    }

    return array ($msg, $html);
}


#---------------------------
function doPOST( $dbh, $user='', $uid='' )
#---------------------------
{
    $msg = '';
    $html = '';

    $html .= '<div class="body_textarea">';

    if( $_REQUEST['resetpasswd'] ) {
        if( $user != '' ) {
            $uid = userFind( $user );
            if( $uid == 0 ) {                       # didn't find by username
                $uid = userFindEmail( $user );      # try email
            }

            if( $uid != 0 ) {                       # did we find it?
                $u = new User( $dbh, $uid );
                $html .= '<div align="justify"><p>An email has been sent to your registered address with further instructions.</p></div>';

                list( $ret, $msg ) = $u->passwordreset( );
            }
        }
    } else if( $_REQUEST['chgpass'] ) {
        if( $uid > 0 ) {
            $u = new User( $dbh, $uid );
            $u->token( 'xx' );                      # prevent re-use
            if( $u->password( $_REQUEST['reg_passwd1'] ) ) {
                $msg = 'Password changed.';
                $html .= '<p>You may now login normally with your new password.</p>';
            } else {
                $msg = 'Unable to change password.';
                $html .= '<p>Hmm...  Sorry, Something went pear-shaped.  Please <a href="/lostpass.php">try again</a>.</p>';
            }
        }
    } else {
        $html .= doGET( $dbh, '' );
    }

    $html .= '</div>';
 
    return array ($msg, $html);
}

#---------------------------
# M A I N
#---------------------------
    if (isset($_REQUEST['tok'])) { $tok=$_REQUEST['tok']; } else { $tok = ''; }
    if (isset($_REQUEST['user'])) { $user=$_REQUEST['user']; } else { $user = ''; }
    if (isset($_REQUEST['uid'])) { $uid=$_REQUEST['uid']; } else { $uid = ''; }

    $dbh = dbStart();

    $body = '';
    $msg = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        list ($msg, $body) = doPOST( $dbh, $user, $uid );
    } else {
        list ($msg, $body) = doGET( $dbh, $tok );
    }


?>
<body onLoad="mpmetrics.track('LostPass');">
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

    <div class="head">Password Recovery</div>
<?php
    echo $body;
?>
  </div>
<!-- end of 'midarea' DIV -->

<!-- right DIV -->
<?php
    if( $msg == 'Password changed.' ) { include_once "right.inc"; }     # wow, this is lame.
?>
<!-- end of 'right' DIV -->
<?php
    include_once 'footer.inc';
?>
</body>
</html>
