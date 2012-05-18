<?php
include_once 'inc/dbconfig.php';
include_once 'inc/config.php';
include_once 'inc/helper_gen.php';
include_once 'inc/helper_feed.php';
include_once 'inc/helper_frame.php';
include_once 'inc/helper_user.php';

#---------------------------
# M A I N
#---------------------------
#--- Parse URL into the $parms[] array
# For example; '/productId=ABC123/frameId=12345678/language=en' translates to:
#   $parms['productid'] = 'ABC123';
#   $parms['frameid'] = '12345678';
#   $parms['language'] = 'en';
#
#   The complete URL is stored in $parms['path']
#
# A typical request would look like this:
#   /productId=TODMF802X/frameId=00:22:43:XX:XX:XX:XX/firmware=2.05/channelList=true
#------------------------------------------------------------------------------
    $uri = parse_url($_SERVER['REQUEST_URI']);
    $p = explode('/', $uri['path']);
    $parms['path'] = $uri['path'];      # to define the $parms array and to save the URL for later

    for ($x=0;$x<count($p);$x++) {
        $p2 = explode('=',$p[$x]);
        if (count($p2) == 2) {
            $parms[strtolower($p2[0])] = $p2[1];
        }
    }
#--- URL parse complete

    if (isset($_REQUEST['productId']))  { $parms['productid'] = $_REQUEST['productId']; }
    if (isset($_REQUEST['language']))   { $parms['language'] = $_REQUEST['language']; }
    if (isset($_REQUEST['category']))   { $parms['category'] = $_REQUEST['category']; }
    if (isset($_REQUEST['frameId']))    { $parms['frameid']  = $_REQUEST['frameId']; }
    if (isset($_REQUEST['channelList']))    { $parms['channellist'] = $_REQUEST['channelList']; }
    if (isset($_REQUEST['channellList']))   { $parms['channellist'] = $_REQUEST['channellList']; }
    if (isset($_REQUEST['user']))       { $parms['user'] = $_REQUEST['user']; }     # DLink frames only so far
    if (isset($_REQUEST['fid']))        { $parms['fid'] = $_REQUEST['fid']; }
    if (isset($_REQUEST['pin']))        { $parms['pin'] =  $_REQUEST['pin']; }

    dbStart();

#print_r($parms);
    if ( (isset($parms['fid'])) and ($parms['fid'] == 999999) ) { $parms['fid'] = 15; }                       # old demo feed frame id

     if ( isset($parms['fid']) and isset($parms['pin']) ) {                                     # 'old school' or custom URL request
        list ($ret, $parms['frameid'], $akey) = frameCheckIn($parms['fid']);
        $active = isFramePinActive($parms['fid'], $parms['pin']);
    } else if ((isset($parms['frameid']))) {
        list($ret, $parms['fid'], $akey) = frameCheckInFrameID2($parms['frameid'], $parms['productid']);
        list ($parms['fid'], $active) = isFrameActiveFrameID($parms['frameid']);

    } else if (isset($parms['user'])) {
        $parms['fid'] = frameFindUsername($parms['user']);
        if ($parms['fid'] != 0) {
            list ($ret, $parms['frameid'], $akey) = frameCheckIn($parms['fid'], $parms['productid']);
            $active = frameIsActiveFID($parms['fid']);
            $active = TRUE;
        } else {
            $active = FALSE;
        }
    } else {
        $parms['frameid'] = 'Not_Supplied';
        $parms['productid'] = 'Not_Supplied';
        $parms['fid'] = 0;
        $active = FALSE;
        $akey = '';
    }

    if ($active) {
        if (isset($parms['channellist']) and ($parms['channellist'])) {
            $rss = feedChannelListFID($parms['fid']);
            feedSendRSS($rss);
        } else {
            if (!isset($parms['frameid'])) { $parms['frameid']=''; }
            feedActiveFrameFeed($parms['fid'], $parms['frameid']);
        }
    } else {
        feedInactiveFrameFeed($parms['fid'], $parms['frameid'], $parms['productid'], $akey);
    }

?>
