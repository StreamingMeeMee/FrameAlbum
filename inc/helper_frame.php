<?php
#--------------------------------
# 2011-nov-5 - TimC
#   - check for zero length activation key and generate if necessary, in frameAdd()
#
# 2012-jan-15 - TimC
#   - define $ret & $newfid in frameCheckInFrameID2()
#----------------------------
function frameActivateKey($akey, $uid)
#----------------------------
{
    $akey = prepDBVal($akey);
    $uid = prepDBVal($uid);

    if (isset($akey) and (isset($uid)) ) {
        $res = mysql_query("UPDATE frames SET user_id=$uid,active='Y' WHERE activation_key='$akey' LIMIT 1");
        if (!$res) { die("Invalid query: " . mysql_error()); }
        if (mysql_affected_rows() == 1) {
            $msg = 'Frame activated.';
        } else {
            $msg = 'Something wonky happened -- frame likely not activated.  key:['.$akey.']  rows:['.mysql_affected_rows().']';
        }
    } else {
        $msg = 'Unable to activate frame -- data missing.';
    }

    return $msg;
}

#----------------------------
function frameCheckActivationKey($akey)
#----------------------------
{
    $akey = prepDBVal($akey);

    $res = mysql_query("SELECT * FROM frames WHERE activation_key='$akey'");
    if (!$res) { die("Invalid query: " . mysql_error()); }

    if (mysql_num_rows($res) == 1) {
        $row = mysql_fetch_assoc($res);
        $fid = $row['idframes'];
        $uid = $row['user_id'];
    } else {
        $fid = 0;
        $uid = 0;
    }

    return array ($fid, $uid);
}

#----------------------------
function frameGenActivationKey()
#----------------------------
{
    $tries = 0;
    $LIMIT = 5;

    do {
        $res = mysql_query('SELECT word FROM words ORDER BY RAND() LIMIT 2');
        if (!$res) { die("Invalid query: " . mysql_error()); }

        $row = mysql_fetch_assoc($res);     # get the first word
        $key = $row['word'];
        $row = mysql_fetch_assoc($res);     # get the 2nd word
        $key .= $row['word'];
        $res = mysql_query("SELECT idframes FROM frames WHERE activation_key='$key'");
        if (!$res) { die("Invalid query: " . mysql_error()); }    
        $tries++;
    } while ((mysql_num_rows($res) != 0) and ($tries < $LIMIT));
    
    if ($tries >= $LIMIT) { $ret = time(); }          # as a last resort use epoch time

    return $key;
}

#----------------------------
function frameAdd($uid, $frameid, $nick, $prodid, $acv, $pin, $akey)
#----------------------------
# Adds a new frame with the given attributes.
# Note: This function DOES NOT check for existing entries before attempting INSERT.
#
# Returns: idframes of new frames entry; 0 on error.
#============================
{
    $msg = '';

    if (!isset($nick))  { $nick = ''; }
    if (!isset($frameid))   { $frameid = ''; }
    if (!isset($prodid)) { $prodid = ''; }
    if (!isset($acv))   { $acv = 'N'; }                             # If not specified frame is NOT active
    if (!isset($uid))   { $uid = 'NULL'; }
    if (!isset($pin) or ($pin == 0)) { $pin = rand(1, 9999); }      # some frames only allow 4 digits for PIN 
    if (!isset($akey) or strlen($akey == 0) )  { $akey = frameGenActivationKey(); }        # Activation Key
    if (strlen($nick) == 0) { $nick = 'My Frame'; }                 # default framename

    if ( (strlen($frameid) != 0) and ($uid != 0) ) {                    # nothing to add
        $frameid = prepDBVal($frameid);
        $uid = prepDBVal($uid);
        $nick = prepDBVal($nick);
        $prodid = prepDBVal($prodid);
        $acv = prepDBVal($acv);
        $pin - prepDBVal($pin);
        $sql = "INSERT INTO frames (frame_id, user_id, user_nickname, active, product_id, feed_pin, created, last_seen, activation_key)
             VALUES ('$frameid', $uid, '$nick', '$acv', '$prodid', $pin, now(), now(), '$akey')";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg = 'An error occured during frame addition: ' . mysql_error();
        }

        if ($result) {              # insert was OK
            $ret = mysql_insert_id();
            $msg = 'FrameID ['.$ret.'] was added.';
        } else {                    # not so much
            $ret = 0;
            $msg = 'Hmmm...  something went wrong.';
        }
    } else {
        $ret = 0;
        $msg = "Sorry, parms are missing -- can't add.  frameid:[".$frameid.']  uid:['.$uid.']';
    }

    return array ($ret, $msg, $akey);
}

#----------------------------
function isFrameActiveFrameID($frameid)
#----------------------------
# Takes raw 'frameID' as parm, NOT idframes value.
# Returns:
#   idframes value corresponding to the requested frameID
#   1 if frame is active, 0 otherwise
#============================
{
    if (!(isset($frameid))) { $frameid = ''; }

    if (strlen($frameid) != 0) {                    # nothing to lookup
        $frameid = prepDBVal($frameid);
        $sql = "SELECT idframes, active FROM frames WHERE frame_id='$frameid'";        # Is this a valid frame?
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
        }

        if (mysql_num_rows( $result ) == 1) {
            $tmp = mysql_fetch_row( $result );
            $ret = ($tmp[1] == 'Y') ? 1 : 0;
        } else {
            $tmp[0] = 0;
            $ret = 0;
        }
    } else {
        $ret = 0;
    }

    return array($tmp[0], $ret);
}

#----------------------------
function frameFindIDProd($prodid)
#----------------------------
# Returns idproduct of products with given product_id.  Yes, it is rather confusing.
# If the requested productID is not found or not specified, the idproduct for 'Unknown' is returned.
# 0 is returned on error.
#============================
{
    $prodid = prepDBVal($prodid);
    $ret = 0;

    if (strlen($prodid) != 0) {                    # nothing to lookup
        $sql = "SELECT * FROM product_ids WHERE productid='$prodid'";        # Is this a valid product?
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $ret = 0;
        }

        if (mysql_num_rows( $result ) == 1) {
            $row = mysql_fetch_assoc( $result );
            $ret = $row['idproduct'];
        } else {
            $ret = 15;
        }
    } else {
        $ret = 15;
    }

    return $ret;
}

#----------------------------
function frameCheckIn($fid)
#----------------------------
# 'touch's a frame.  Since this is using already assigned fid (rather than frameID) it will NOT add a missing frame.
#
# Returns: 0 on error, 1 if specified frame was touched.
#============================
{
    $fid = prepDBVal($fid);

    if ( $fid != 0 ) {                     # don't touch frames with no ID
        $sql = "UPDATE frames SET last_seen = now() WHERE idframes = '$fid' LIMIT 1";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $ret = 0;
            $frameid = '';
            $akey = '';
        } else {
            $ret = mysql_affected_rows();
            if ($ret == 1) {
                $sql = "SELECT * FROM frames WHERE idframes = '$fid' LIMIT 1";
                $result = mysql_query($sql);
                $row = mysql_fetch_assoc( $result );
                $frameid = $row['frame_id'];
                $akey = $row['activation_key'];
            } else {
                $frameid = '';
                $akey = '';
            }
        }
    } else {
        $ret = 0;
    }

    return array ($ret, $frameid, $akey);
}

#----------------------------
function frameCheckInFrameID($frameid, $prodid)
#----------------------------
# 'touch's a frame.  If it does not exist it is added.
#
# $frameid is the frameId value supplied by the frame, it is NOT idframes used internally (often called 'fid')
#
# Returns: 0 on error, 1 if specified frame was touched. $newfid is idframe of new frame. $akey is activationkey of new frame
#============================
{
    $frameid = prepDBVal($frameid);
    $prodid = prepDBVal($prodid);

    if (!(isset($prodid))) { $prodid = 'UKNW'; }
    $idproduct = frameFindIDProd($prodid);

    if ( (isset($frameid)) && (strlen($frameid) != 0) ) {                     # don't add frames with no ID
        if (isset($prodid) && (strlen($prodid) != 0) ) {
            $sql = "UPDATE frames SET product_id='$idproduct', last_seen = now() WHERE frame_id = '$frameid' LIMIT 1";
        } else {
            $sql = "UPDATE frames SET last_seen = now() WHERE frame_id = '$frameid' LIMIT 1";
        }
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $ret = 0;
            $newfid = 0;
            $akey = '';
        } else {
            $ret = mysql_affected_rows();
            $newfid = 0;
            if ($ret == 0) {   # no frame with this ID, add it
                list ($newid, $msg, $akey) = frameAdd(2, $frameid, NULL, $idproduct, 'N', 0, '');   # '2' is the 'Public Channels' user
            } else {
                $sql = "SELECT * FROM frames WHERE frame_id = '$frameid' LIMIT 1";
                $result = mysql_query($sql);
                $row = mysql_fetch_assoc( $result );
                $akey = $row['activation_key'];
            }
        }
    } else {
        $ret = 0;
        $newfid = 0;
        $akey = '';
    }

    return array ($ret, $newfid, $akey);
}

#----------------------------
function frameCheckInFrameID2($frameid, $prodid)
#----------------------------
# 'touch's a frame.  If it does not exist it is added.
#
# $frameid is the frameId value supplied by the frame, it is NOT idframes used internally (often called 'fid')
#
# Returns: 0 on error, 1 if specified frame was touched. $newfid is idframe of new frame. $akey is activationkey of new frame
#============================
{
    $frameid = prepDBVal($frameid);
    $prodid = prepDBVal($prodid);
    $newfid = 0;
    $ret = 0;

    if (!(isset($prodid))) { $prodid = 'UKNW'; }
    $idproduct = frameFindIDProd($prodid);

    if ( (isset($frameid)) && (strlen($frameid) != 0) ) {                     # don't add frames with no ID
        $sql = 'SELECT * FROM frames WHERE frame_id="' . $frameid . '"';
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
        }

        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc( $result );
            $akey = $row['activation_key'];

            if (isset($prodid) && (strlen($prodid) != 0) ) {
                $sql = "UPDATE frames SET product_id='$idproduct', last_seen = now() WHERE frame_id = '$frameid' LIMIT 1";
            } else {
                $sql = "UPDATE frames SET last_seen = now() WHERE frame_id = '$frameid' LIMIT 1";
            }
            $result = mysql_query($sql);
            if (!$result) {
                die("[$sql]: Invalid query: " . mysql_error());
                $ret = 0;
                $newfid = 0;
                $akey = '';
            }
        } else {
            $newfid = 0;
            list ($newid, $msg, $akey) = frameAdd(2, $frameid, NULL, $idproduct, 'N', 0, '');   # '2' is the 'Public Channels' user
            $ret = 1;
        }
    } else {
        $ret = 0;
        $newfid = 0;
        $akey = '';
    }

    return array ($ret, $newfid, $akey);
}

#----------------------------
function frameFindUsername($username)
#----------------------------
{
    $ret = 0;
    $username = prepDBVal($username);

    $sql = "SELECT idframes FROM frames AS f, users AS u WHERE u.username='$username' AND f.user_id=u.idusers";        # Does
    $result = mysql_query($sql);
    if (!$result) {
        die("[$sql]: Invalid query: " . mysql_error());
    }

    if (mysql_num_rows( $result ) > 0) {
        $tmp = mysql_fetch_row( $result );      # just grab the first one for now.
        $ret = $tmp[0];
    } else {
        $ret = 0;
    }

    return $ret;
}

#----------------------------
function frameGetProductInfo($prodid)
#----------------------------
# Returns specs on a give product_id
#============================
{
    $prodid = prepDBVal($prodid);
    $ret = 0;

    if (strlen($prodid) != 0) {                    # nothing to lookup
        $sql = "SELECT * FROM product_ids WHERE productid='$prodid'";        # Is this a valid product?
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $ret = 0;
        }

        if (mysql_num_rows( $result ) == 1) {
            $row = mysql_fetch_assoc( $result );
        } else{
            $row['idproduct'] = 0;
            $row['manuf'] = $row['model'] = '';
            $row['hres'] = 800; $row['vres'] = 480;
        }
    } else {
        $row['idproduct'] = 0;;
        $row['manuf'] = $row['model'] = '';
        $row['hres'] = 800; $row['vres'] = 480;
    }

    return array ($row['idproduct'], $row['manuf'], $row['model'], $row['hres'], $row['vres']);
}

#----------------------------
function isFramePinActive($fid, $pin)
#----------------------------
# Takes idframes value and an associated PIN.
# Returns:
#   1 if frame is valid and active, 0 otherwise
#============================
{
    if (!(isset($fid))) { $fid = 0; }
    if (!(isset($pin))) { $pin = 0; }

    if ($fid != 0) {                    # nothing to lookup
        $fid = prepDBVal($fid);
        $pin = prepDBVal($pin);

        $sql = "SELECT active FROM frames WHERE idframes='$fid' AND feed_pin='$pin'";        # Is this a valid user?
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
        }

        if (mysql_num_rows( $result ) == 1) {
            $tmp = mysql_fetch_row( $result );
            $ret = ($tmp[0] == 'Y') ? 1 : 0;
        } else {
            $ret = 0;
        }
    } else {
        $ret = 0;
    }

    return $ret;
}

#-------------------------
function isFrameActive($fid)
#-------------------------
{
    $fid = prepDBVal($fid);

    $sql = "SELECT 'Y' FROM users AS u, frames AS f WHERE u.active='Y' AND u.idusers=f.user_id AND f.active='Y' and f.idframes=$fid";
    $res = mysql_query($sql)or die("Active frame lookup failed.");

    if ( mysql_num_rows($res) > 0 ) {
        return 1;
    } else {
        return 0;
    }
}

?>
