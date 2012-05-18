<?php
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
        $user = prepDBVal($user);
        $sql = "SELECT idusers,email FROM users where username='$user'";        # Is this a valid user?
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
