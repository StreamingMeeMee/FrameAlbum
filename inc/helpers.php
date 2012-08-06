<?php
#-------------------------------------------
# 2011-jun-20 - TimC
#   Basic helper functions
#
# 2011-jun-28 - TimC
#   Add prepDBVal(), frameAdd() and frameCheckIn()
#
# 2011-jul-24 - TimC
#   Add frameFindUsername()
#
# 2011-aug-6 - TimC
#   channelListFID(): Use frame_icon_url rather than channel_icon_url -- frames don't support PNG
#
# 2011-sept-18 - TimC
#   - Add SysMsg()
#
# 2011-sept-30 - TimC
#   - Add showActiveStatus() and optionActiveStatus()
#
# 2012-may-25 - TimC
#   - move frameIsActive(), frameIsActiveFID() to helper_frame.inc
#
# 2012-aug-5 - TimC
#   - Move prepDBVal() to dbconfig.php
#-------------------------------------------
$GLOBALS['PROGRAMNAME'] = '';
$GLOBALS['PROGRAMOWNER'] = 'user@email.com';
$GLOBALS['VERSIONSTRING'] = '';

$GLOBALS['DEBUG'] = 0;

define("MSG_DEBUG",   5);
define("MSG_VERBOSE", 4);
define("MSG_INFO",    3);
define("MSG_WARN",    2);
define("MSG_ERR",     1);
define("MSG_CRIT",    99);

## SEVERITY - Severity label text for messsages
$GLOBALS['SEVERITY'] = array(
 MSG_DEBUG => '*Debug*',
 MSG_VERBOSE =>'*Verbose*',
 MSG_INFO => '*Info*',
 MSG_WARN => '*Warn*',
 MSG_ERR => '*Error*',
 MSG_CRIT => '*Critical*');

$GLOBALS['MSG_FORMAT'] = 'TEXT';       # 'HTML'|'TEXT'

## MSG_PRINT_THRESHOLD - Print messages of this severity and higher
$GLOBALS['MSG_PRINT_THRESHOLD'] = MSG_INFO;
if ($GLOBALS['DEBUG']) { $GLOBALS['MSG_PRINT_THRESHOLD'] = MSG_DEBUG; }

## OPS_ALERT_THRESHOLD - Send alert to OPS_EMAIL for messages of this severity and higher
$GLOBALS['$OPS_ALERT_THRESHOLD'] = MSG_WARN;

## OPS_EMAIL - Where to send alerts
$GLOBALS['OPS_EMAIL'] = 'ops@email.com';

## OPS_EMAIL_SUBJ - What is the subject of the email to ops
if (!empty($GLOBALS['PROGRAMNAME'])) {
  $GLOBALS['OPS_EMAIL_SUBJ'] = $GLOBALS['PROGRAMNAME'] . ' has encountered a problem';
} else {
  $GLOBALS['OPS_EMAIL_SUBJ'] = "A problem has been encountered.";
}

## SMTP_SERVER - SMTP server hostname
$GLOBALS['SMTP_SERVER'] = 'localhost';
$GLOBALS['EMAIL_FROM'] = $GLOBALS['PROGRAMOWNER'];
if ($GLOBALS['DEBUG']) { $GLOBALS['EMAIL_TO'] = $GLOBALS['PROGRAMOWNER']; }

$GLOBALS['EMAIL_CC'] = '';

#---------------------------
function showActiveStatus($status)
#---------------------------
{
    $html = '';
    if ($status == 'Y') {
        $html .="<img src='/images/on.png' width='24' height='24'>&nbsp;&nbsp";
    } else {
        $html .="<img src='/images/off.png' width='24' height='24'>&nbsp;&nbsp";
    }

  return $html;
}

#---------------------------
function optionActiveStatus($selected_value, $name)
#---------------------------
{
  $html = '';
  $html .= "<input type='radio' name='$name' value='Y'";
  if ($selected_value == 'Y') { $html .= " checked"; }
  $html .="><img src='/images/on.png' width='16' height='16'>&nbsp;&nbsp;&nbsp;";
  $html .= "<input type='radio' name='$name' value='N'";
  if ($selected_value == 'N') { $html .= " checked"; }
  $html .="><img src='/images/off.png' width='16' height='16'>";

  return $html;
}

#--------------------------
function SysMsg($sev, $msg)
#--------------------------
{

    if ($sev == MSG_CRIT) {
        echo $GLOBALS['SEVERITY'][$sev] . ' ' . $msg . "\n";
        exit;
    }

    if ($sev <= $GLOBALS['MSG_PRINT_THRESHOLD']) {
        echo $GLOBALS['SEVERITY'][$sev] . ' ' . $msg . "\n";
    }

}

#--------
function feedHead()
#--------
{
    $t = '';
    $t .= '<?xml version="1.0"  encoding="utf-8"  ?>'."\n";
    $t .= '<rss version="2.0"  xmlns:media="http://search.yahoo.com/mrss/" xmlns:frameuserinfo="http://www.framechannel.com/feeds/frameuserinfo/" xmlns:tsmx="http://www.thinkingscreenmedia.com/tsmx/" >'."\n";

    return $t;
}

#--------
function feedTail()
#--------
{
    $t = '';

    $t .= "</rss>\n";

    return $t;
}

#--------
function channelHead($user, $ttl, $desc)
#--------
{
    $t = '';

    $t .= "<channel>\n";
    $t .= "<title>FrameAlbum content for $user</title>\n";
    $t .= "<link>http://www.framealbum.com</link>\n";
    if (isset($desc)) { $t .= "<description>\n$desc\n</description>\n"; }
    if (isset($ttl)) { $t .= "<ttl>$ttl</ttl>\n"; }
    $t .= "<frameuserinfo:firstname>-</frameuserinfo:firstname>\n";
    $t .= "<frameuserinfo:lastname>-</frameuserinfo:lastname>\n";
    $t .= "<frameuserinfo:username>$user</frameuserinfo:username>\n";
    $t .= "<frameuserinfo:unregistered>FALSE</frameuserinfo:unregistered>\n";

    return $t;
}

#--------
function channelTail()
#--------
{
    $t = '';

    $t .= "</channel>\n";

    return $t;
}

#--------
function channelItem($title, $link, $category, $desc, $pubDate, $guid, $media_url, $thumb_url)
#--------
{
    $t = '';

    $t .= "<item>\n";

    $t .= "    " . '<title>' . $title . "</title>\n";
    if (isset($link)&$link) { $t .= "    " . '<link>' . $link . "</link>\n"; }
    $t .= "    " . '<category>' . $category . "</category>\n";
    if (isset($desc)&$desc) { $t .= "    " . '<description>' . $desc . "</description>\n"; }
    if (isset($pubDate)&$pubDate) { $t .= "    ".'<pubDate>' .  $pubDate . "</pubDate>\n"; }
    $t .= "    ".'<guid isPermaLink="false">' .  $guid . "</guid>\n";
    if ( preg_match('/.png/', $media_url) ) {
        $t .= "    ".'<media:content url="' .  $media_url . '" type="image/png" height="64" width="64" duration="10"/>'."\n";
    } else {
        $t .= "    ".'<media:content url="' .  $media_url . '" type="image/jpg" height="64" width="64" duration="10"/>'."\n";
    }
    if (isset($thumb_url)&$thumb_url) { $t .= "    ".'<media:thumbnail url="' .  $thumb_url . '" />'."\n"; }

    $t .= "</item>\n";

    return $t;
}

#-------------------------
function channelListFID($fid)
#-------------------------
{
    $rss = '';
    $fid = prepDBVal($fid);

    $sql = "SELECT * FROM frame_channels AS fc, frames AS f, channel_types AS ct, user_channels AS uc WHERE f.idframes='$fid' AND fc.frame_id = f.idframes AND uc.iduserchannels = fc.user_channel_id AND ct.idchanneltypes = uc.channel_type_id";
    $res = mysql_query($sql)or die("channelList lookup failed.");

    $rss = feedHead();
    $rss .= channelHead('', 15, 'Channel list for ' . userFindFID($fid));

    if ( mysql_num_rows($res) > 0 ) {
        while( $row = mysql_fetch_assoc( $res ) ) {
            $icon_url = $GLOBALS['www_url_root'] . $row['frame_icon_url'];
#print_r($row);
            $rss .= channelItem($row['chan_nickname'], '', $row['channel_category'], '', '', $row['iduserchannels'], $icon_url, '');
        }
    }

    $rss .= channelTail();
    $rss .= feedTail();

    return $rss;
}

#----------------------------
function OLDframeFindUsername($username)
#----------------------------
{
    $ret = 0;
    $username = prepDBVal($username);

    $sql = "SELECT idframes FROM frames AS f, users AS u WHERE u.username='$username' AND f.user_id=u.idusers";        # Does this user have a frame?
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
function OLDuserFindFID($fid)
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

#----------------------------
function getProductInfo($prodid)
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
function findIDProd($prodid)
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
function OLDframeAdd($fid, $uid, $prodid, $acv)
#----------------------------
# Adds a frame to the system.
#
# Returns: idframe of added frame or 0 if error occured.
#============================
{
    $fid = prepDBVal($fid);
    $uid = prepDBVal($uid);
    $prodid = prepDBVal($prodid);
    $acv = prepDBVal($acv);

    if (!(isset($uid))) { $uid = 'NULL'; }

    if ( (isset($fid)) && (strlen($fid) != 0) ) {                     # don't add frames with no ID
        if (!(isset($acv))) { $acv = 'N'; }     # If not specified frame is NOT active

        $sql = "INSERT into frames (frame_id, user_id, active, product_id, last_seen, created) VALUES ('$fid', $uid, '$acv', '$prodid', now(), now())";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $ret = 0;
        } else {
            $ret = mysql_insert_id();
        }
    } else {
        $ret = 0;
    }

    return $ret;
}

#----------------------------
function OLDframeCheckInFrameID($fid, $prodid)
#----------------------------
# 'touch's a frame.  If it does not exist it is added.
#
# Returns: 0 on error, 1 if specified frame was touched else idframe of new frame.  Yes, I realized this is ambiguous if the idframe==1 <TODO> 
#============================
{
    $fid = prepDBVal($fid);
    $prodid = prepDBVal($prodid);
    if (!(isset($prodid))) { $prodid = 'UKNW'; }
    $idproduct = findIDProd($prodid);

    if ( (isset($fid)) && (strlen($fid) != 0) ) {                     # don't add frames with no ID
        $sql = "UPDATE frames SET product_id='$idproduct', last_seen = now() WHERE frame_id = '$fid' LIMIT 1";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $ret = 0;
        } else {
            $ret = mysql_affected_rows();
#            if ($ret == 0) {   # no frame with this ID, add it
#                $ret = frameAdd($fid,NULL,$idproduct,'N');
#            } 
        }
    } else {
        $ret = 0;
    }

    return $ret;
}

#----------------------------
function OLDframeCheckIn($fid)
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
        } else {
            $ret = mysql_affected_rows();
        }
    } else {
        $ret = 0;
    }

    return $ret;
}

#----------------------------
function userFindByEmail($email)
#----------------------------
# Returns iduser of user with given email address.  =0 if user is not found
#============================
{
    $email = prepDBVal($email);

    if (strlen($email) != 0) {                    # nothing to lookup
        $sql = "SELECT idusers,username FROM users where email='$email'";        # Is this a valid user?
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $ret = 0;
        }

        if (mysql_num_rows( $result ) > 0) {
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
function OLDuserAdd($uname,$passwd,$email,$zip, $stat)
#----------------------------
# Adds a new user with the given attributes.  The user is 'R'egistered but not active.
# Activation requires verification of the user's given email address.
# Note: This function DOES NOT check for existing entries before attempting INSERT.
#============================
{
    if (strlen($uname) == 0) { $uname = $email; }
    if (strlen($zip) == 0) { $zip = 0; }
    if (strlen($stat) == 0) { $stat = 'P'; }

    if ( (strlen($email) != 0) and (strlen($uname) != 0) ) {                    # nothing to add
        $sql = "INSERT INTO users (username, active, email, passwd, ZIP, date_registered) VALUES ('".mysql_real_escape_string($uname)."', '$stat', '".mysql_real_escape_string($email)."', '".mysql_real_escape_string($passwd)."', ".mysql_real_escape_string($zip).", now())";
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
?>
