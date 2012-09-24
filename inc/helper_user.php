<?php
#--------------------------------
# 2012-jul-31 - TimC
#   - Add userRegForm(), userRegFormProc() and restore userSendWelcomeEmail()
#
# 2012-aug-13 - TimC
#   - Add userFindFBUser() to locate a user ID given a FB uid
#   - Add userFindEmail() to locate a user by email address
#
# 2012-aug-20 -TimC
#   - Add  userGetUIDByToken()
#
# 2012-sept-5 - TimC
#   - Add userFindPIN() to find a user by username + PIN
#
# 2012-sept24 - TimC
#   - Modify userFindPIN() to optionally search for match on username if PIN field is present but blank
#--------------------------------

#----------------------------
function userFindFBUser( $fbuid )
#----------------------------
{
    if( isset( $fbuid ) and $fbuid > 0 ) {
        $fbuid=prepDBVal( $fbuid );

        $sql="SELECT * FROM users WHERE fb_user_id = $fbuid AND (active='Y' or active='R')";
        $result=mysql_query($sql);
        $count=mysql_num_rows($result);

        if( $count==1 ) {
            $row = mysql_fetch_assoc($result);
            $_SESSION['username']=$row['username'];
            $_SESSION['uid'] = $row['idusers'];
            $_SESSION['useremail'] = $row['email'];
            $_SESSION['isadmin'] = $row['admin'];
            $_SESSION['loggedin'] = 'N';

            $sql="UPDATE users SET last_login=now() WHERE idusers=".$_SESSION['uid'];
            $result=mysql_query($sql);
        }
    }

    return $row['idusers'];             # returns NULL if nothing found
}

#----------------------------
function userSendWelcomeEmail($email)
#----------------------------
{
    $headers = 'From: ' . $GLOBALS['email_from'] . "\r\n" .
        'Reply-To: ' . $GLOBALS['email_reply_to'] . "\r\n";

    $txt = "Welcome to FrameAlbum.  You may now access your account at " . $GLOBALS['www_url_root'] . ".

Once you have logged in you may add your frame(s) and then define the channels that will be sent to your frame.

If you have any questions, drop me an email at " . $GLOBALS['email_from'] . ".";

    $ret = mail($email, 'Welcome to FrameAlbum', $txt, $headers);

    if (!ret) {
        $msg = '<p>There was a problem sending a message to ' . $email . '.  You may not receive your Welcome email message.</p>';
    }

    return array ($ret, $msg);
}

#----------------------------
function userRegForm( $tok, $username, $email, $zip )
#----------------------------
{
    $msg = '';
    $html = '';

    if (strlen($tok) > 0) {
        list ($username, $email, $zip) = userGetInfoByToken($tok);
        $html .= '<input type="hidden" name="tok" value="'.$tok.'">';
    }

    if ( featureEnabled( 'enable_fb_login' ) ) {
        $html .= '<iframe src="https://www.facebook.com/plugins/registration?';
        $html .= 'client_id=' . $GLOBALS['fb_api_key'] . '&';
        $html .= 'redirect_uri=' . $GLOBALS['www_url_root'] . '%2Fapi%2Ffbsignreq.php&fields=name,location,email"';
        $html .= 'scrolling="auto"
                frameborder="no"
                style="border:none"
                allowTransparency="true"
                width="100%"
                height="330">
            </iframe>';
    }

    $html .= '<input type="hidden" name="stage" value="2">';
    $html .= '<table border="0">';
    $html .= '<tr><td>Username:</td><td><input type="text" maxlength="64" size="32" name="reg_username" id="reg_username" value="'.$username.'" onblur="validUsername()"><br><p id="usernamemsg" class="validmsg" style="display:none">&nbsp;</p></td><td><div><img id="usernameicon" height="24" src="/images/knobs/Grey.png"</div></td></tr>';
    $html .= '<tr><td>Password:</td><td><input type="password" maxlength="64" size="32" name="reg_passwd1" id="reg_passwd1" value="" onchange="validPasswd()"></td><td><div><img id="passwdicon" height="24" src="/images/knobs/Grey.png"</div></td></tr>';
    $html .= '<tr><td>Confirm Password:</td><td><input type="password" maxlength="64" size="32" name="reg_passwd2" id="reg_passwd2" value="" onchange="validPasswd()"><br><p id="passwdmsg" class="validmsg" style="display:none">&nbsp;</p></td><td>&nbsp;</td></tr>';
    $html .= '<tr><td>Email address:</td><td><input type="text" maxlength="64" size="32" name="reg_email" id="reg_email" value="'.$email.'" onblur="validEmail()"><br><p id="emailmsg" class="validmsg" style="display:none">&nbsp;</p></td><td><div><img id="emailicon" height="24" src="/images/knobs/Grey.png"</div></td></tr>';
    $html .= '<tr><td>Home ZIP code:<br>(US & Canada only)</td><td><input type="text" maxlength="7" size="6" name="reg_zip" id="reg_zip" value="'.$zip.'" onblur="validZip()"><br><p id="zipmsg" class="validmsg" style="display:none">&nbsp;</p></td><td><div><img id="zipicon" height="24" src="/images/knobs/Grey.png"</div></td></tr>';
    $html .= '</table>';
    $html .= '<div align="center"><input type="submit" value=" Register " name="register" /></div>';

    return array ( $msg, $html );
}

#----------------------------
function userRegFormProc( $tok, $username, $email, $zip, $passwd )
#----------------------------
{
    $html = '';
    $msg = '';
    $errs = 0;

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
                list ($d, $html) = userRegForm($tok, $username, $email, $zip);
            } else {
                $html .= "<p>Okey dokey -- you are ready to login and start using FrameAlbum.</p>";
            }
        } else {
            list ($d, $html) = userRegForm($tok, $username, $email, $zip);
        }
    } else {
        if (strlen($email) != 0) {
            if ( userFindByEmail($email) > 0 ) {
                $msg .= "<p>Sorry, that email address [" . $email . "] is already registered -- please enter a unique email address.</p>";
                $email = '';;
                $errs++;
            }

            if ( userFind($username) > 0 ) {
                $msg .= "<p>Sorry, that username [" . $username . "] is already registered -- please enter a unique username.</p>";
                $username = '';
                $errs++;
            }

            if ($errs == 0) {
                if ( userAdd($username, $passwd, $email, $zip, 'R') > 0 ) {
                    list ($d, $msg) = userSendWelcomeEmail($email);
                    $html .= "<p>Welcome!  You are now registered for the FrameAlbum service.  You will receive an email with details of the next step.</p>";
                    setcookie('registered', $username, time() + ( 60*60*24*120 ), '/', '.framealbum.com');
                } else {
                    $msg .= "<p>Sorry, something went pear-shaped with the registration.  Please try again with a different email address.</p>";
                    $errs++;
                }
            }
        } else {
            $msg .= "<p>Sorry, I didn't quite get that -- please enter an email address to register.</p>";
            $email = '';
            $errs++;
        }
    }

    if( $errs > 0 ) { list ($d, $html) = userRegForm($tok, $username, $email, $zip); }      # if there are errors redisplay the form.

    return array ( $msg, $html, $redir );
}

#----------------------------
function userUpdate($tok, $uname, $passwd, $email, $zip, $stat)
#----------------------------
# Updates and Existing user with the given attributes.
# Note: This function DOES NOT check for existing entries before attempting UPDATE.
#============================
{
    $ret = 0;

    if (!isset($uname) or (strlen($uname) == 0 )) { $uname = $email; }                               # default username to email address
    if (!isset($zip) or (strlen($zip) == 0) ) { $zip = ''; }
    if (!isset($passwd) or (strlen($passwd) == 0) ) { $passwd = ''; }
    if (!isset($stat) or (strlen($stat) == 0) ) { $stat = 'P'; }

    if ( (strlen($tok) != 0) and (strlen($email) != 0) and (strlen($uname) != 0) ) {                    # nothing to add
        $uname = prepDBVal($uname);
        $passwd = prepDBVal($passwd);
        $email = prepDBVal($email);
        $zip = prepDBVal($zip);
        $stat = prepDBVal($stat);

        $sql = "UPDATE users SET username='$uname', email='$email', passwd=AES_ENCRYPT('$passwd', '" . $GLOBALS['pwsalt'] . "'), ZIP='$zip', active='$stat' 
            WHERE token='$tok' LIMIT 1";
        $result = mysql_query($sql);
        if (!$result) {
            die("Invalid query: " . mysql_error());
        }

        if ($result) {              # insert was OK
            $ret = mysql_affected_rows();
        } else {
            $ret = 0;
        }
    } else {
        $ret = 0;
    }

    return $ret;
}

#----------------------------
function userAdd($uname, $passwd, $email, $zip, $stat)
#----------------------------
# Adds a new user with the given attributes.  The user is 'R'egistered but not active.
# Activation requires verification of the user's given email address.
# Note: This function DOES NOT check for existing entries before attempting INSERT.
#============================
{
    if (!isset($uname) or (strlen($uname) == 0 )) { $uname = $email; }                               # default username to email address
    if (!isset($zip) or (strlen($zip) == 0) ) { $zip = null; }
    if (!isset($stat) or (strlen($stat) == 0) ) { $stat = 'P'; }

    if ( (strlen($email) != 0) and (strlen($uname) != 0) ) {                    # nothing to add
        $uname = prepDBVal($uname);
        $passwd = prepDBVal($passwd);
        $email = prepDBVal($email);
        $zip = prepDBVal($zip);
        $stat = prepDBVal($stat);

        $sql = "INSERT INTO users (username, active, email, passwd, ZIP, token, date_registered)
             VALUES ('$uname', '$stat', '$email', AES_ENCRYPT('$passwd', '" . $GLOBALS['pwsalt'] . "'),'$zip', MD5(CONCAT('$email', '" . $GLOBALS['pwsalt'] . "')), now())";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
        }

        if ($result) {              # insert was OK
            $ret = mysql_insert_id();
        } else {
            $ret = '0';
        }
    } else {
        $ret = '0';
    }

    return $ret;
}

#----------------------------
function userGetInfoByToken($tok)
#----------------------------
{
    $username = '';
    $email = '';
    $zip = 0;

    if (!(isset($tok))) { return array ('', '', 0); }

    if (strlen($tok) != 0) {                    # nothing to lookup
        $tok = prepDBVal($tok);
        $sql = "SELECT username,email,zip FROM users WHERE token='$tok'";        # Is this a valid user?
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
        }

        if (mysql_num_rows( $result ) == 1) {
            list ($username, $email, $zip) = mysql_fetch_row( $result );
        }
    }

    return array ($username, $email, $zip);
}

#----------------------------
function userGetUIDByToken( $tok )
#----------------------------
{
    $ret = 0;

    if (!(isset($tok))) { return $ret; }

    if ( strlen( $tok ) != 0) {                    # nothing to lookup
        $tok = prepDBVal( $tok );
        $sql = "SELECT idusers FROM users WHERE token='$tok'";        # Is this a valid user?
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
        }

        if (mysql_num_rows( $result ) == 1) {
            list ($ret) = mysql_fetch_row( $result );
        }
    }

    return $ret;
}

#----------------------------
function userIsAdmin($uname)
#----------------------------
# Returns 1 if user has admin flag set, 0 otherwise
#============================
{
    if (!(isset($uname))) { $uname = ''; }

    if (strlen($uname) != 0) {                    # nothing to lookup
        $uname = prepDBVal($uname);
        $sql = "SELECT idusers,admin FROM users where username='$uname'";        # Is this a valid user?
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
        }

        if (mysql_num_rows( $result ) == 1) {
            $tmp = mysql_fetch_row( $result );
            $ret = ($tmp[1] == 'Y') ? 1 : 0;
        } else {
            $ret = 0;
        }
    } else {
        $ret = 0;
    }

    return $ret;
}

#----------------------------
function userFind($user)
#----------------------------
# Returns iduser of user with given username.  =0 if user is not found
#============================
{
    if (!(isset($user))) { return 0; }

    if (strlen($user) != 0) {                    # nothing to lookup
        $user = q($user);
        $sql = "SELECT idusers,email FROM users where username=$user AND active!='N'";        # Is this a valid user?
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
        }

        if (mysql_num_rows( $result ) == 1) {
            $tmp = mysql_fetch_row( $result );
            $ret = $tmp[0];
        } else {
            $ret = 0;
        }
    } else {
        $ret = 0;
    }

    return $ret;
}

#----------------------------
function userFindPIN($user, $pin, $lax=FALSE)
#----------------------------
# Returns iduser of user with given username and PIN.  =0 if user is not found
#============================
{
    if (!(isset($user))) { return 0; }
    if (!(isset($pin))) { return 0; }
    if ( ( strlen($user) != 0 ) )  {                    # nothing to lookup
        $user = q($user);
#        $pin = q($pin);                                # don't quote it here as it breaks the strlen check below
        $sql = "SELECT idusers FROM users where username=$user AND pin=".q($pin)." AND active!='N'";        # Is this a valid user?
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
        }
        if (mysql_num_rows( $result ) == 1) {
            $tmp = mysql_fetch_row( $result );
            $ret = $tmp[0];
        } else {
            if( $lax and ( strlen($pin) == 0 ) ) {                                              # lookup on username only
                $sql = "SELECT idusers FROM users where username=$user AND active!='N'";        # Is this a valid user?
                $result = mysql_query($sql);
                if (!$result) {
                    die("[$sql]: Invalid query: " . mysql_error());
                }
                if (mysql_num_rows( $result ) == 1) {
                    $tmp = mysql_fetch_row( $result );
                    $ret = $tmp[0];
                }
            } else {
                $ret = 0;
            }
        }
    } else {
        $ret = 0;
    }

    return $ret;
}

#----------------------------
function userFindEmail($em)
#----------------------------
# Returns iduser of user with given email.  =0 if user is not found
#============================
{
    if ( ! ( isset( $em ) ) ) { return 0; }

    if ( strlen( $em ) != 0 ) {                    # nothing to lookup
        $em = prepDBVal( $em );
        $sql = "SELECT idusers FROM users where lower(email)=lower('$em')";        # Is this a valid user?
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
        }

        if (mysql_num_rows( $result ) == 1) {
            $tmp = mysql_fetch_row( $result );
            $ret = $tmp[0];
        } else {
            $ret = '0';
        }
    } else {
        $ret = '0';
    }

    return $ret;
}

#----------------------------
function userFindFID($fid)
#----------------------------
{
    $ret = '';
    $fid = prepDBVal($fid);

    $sql = "SELECT user_id,username FROM frames AS f, users AS u WHERE f.idframes=$fid AND f.user_id=u.idusers";        # Does this user have a frame?
    $result = mysql_query($sql);
    if (!$result) {
        die("[$sql]: Invalid query: " . mysql_error());
    }

    if (mysql_num_rows( $result ) > 0) {
        $tmp = mysql_fetch_row( $result );      # just grab the first one for now.
        $ret = $tmp[1];
    } else {
        $ret = '';
    }

    return $ret;
}

?>
