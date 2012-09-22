<?php
#-------------------------------------------------
# 2012-jul-10 - TimC
#   - Bug60 - productId not specified in some calls by DLink frames. Assign it a value to prevent warnings
#
# 2012-jul-20 - TimC
#   - feedActiveFrameFeed() has only one parm
#
# 2012-aug-2 - TimC
#   - add support for 'user=' request parms (ViewSonic frames)
#   - replace feed.php with this code via Apache URL Rewrite
#
# 2012-aug-27 - TimC
#   - add support for 'route' parms
#   - a 'user' request now gets all content for all channels owned by the specified user.
#
# 2012-sep-22 - TimC
#   - support showSetupInfo
#-------------------------------------------------
include_once 'inc/dbconfig.php';
include_once 'inc/config.php';
include_once 'inc/helpers.php';
include_once 'inc/helper_feed.php';
include_once 'inc/helper_frame.php';
include_once 'inc/helper_user.php';

#--------------------------
function handleRoute( $parms )
#--------------------------
{
#echo "handleRoute()\n";

    switch( $parms['route'] ) {
        case 'getUserList':
            $rss = feedGetUserList( $parms['frameid'] );
            feedSendRSS( $rss );
            break;

        case 'showSetupInfo':
            $rss = feedShowSetupInfo( $parms['frameid'] );
            feedSendRSS( $rss );
            break;

        default:
            handleFrame( $parms );
    }
}

#--------------------------
function handleUser( $parms )
#--------------------------
{
#echo "handleUser() user:[".$parms['user']." pin:[".$parms['pin']."]\n";

    if ( isset( $parms['user'] ) and isset( $parms['pin'] ) ) {
        $parms['fid'] = frameFindUsernamePin( $parms['user'], $parms['pin'] );          # is it a specific frame?
#echo "fid:[".$fid."]\n";
        if( $parms['fid'] > 0 ) {
            list ($ret, $parms['frameid'], $akey) = frameCheckIn( $parms['fid'] );
            if( isFrameActive( $parms['fid'] ) ) {
                feedActiveFrameFeed( $parms['fid'] );
            } else {
                feedInactiveFrameFeed($parms['fid'], $parms['frameid'], $parms['productid'], $akey);
            }

        } else {                                                                        # is it all content for this user? 
            $uid = userFindPIN( $parms['user'], $parms['pin'] );
#echo "uid:[".$uid."]\n";
            if( $uid != 0 ) { feedActiveUserFeed( $uid ); }
        }

    } else {
        handleFrame( $parms );
    }
}

#--------------------------
function handleFrame( $parms )
#--------------------------
{

    if ( isset( $parms['user'] ) and isset( $parms['pin'] ) ) {
        $parms['fid'] = frameFindUsernamePin( $parms['user'], $parms['pin'] );
        if( $parms['fid'] > 0 ) {
            frameCheckIn( $parms['fid'] );
            $active = isFrameActive( $parms['fid'] );
        } else {
            $active = 0;
        }
    } else if ( isset($parms['fid']) and isset($parms['pin']) ) {                                     # 'old school' or custom URL request
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
            feedActiveFrameFeed( $parms['fid'] );
        }
    } else {
        feedInactiveFrameFeed($parms['fid'], $parms['frameid'], $parms['productid'], $akey);
    }


}
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
            $p2[0] = strtolower( $p2[0] );
            if( $p2[0] == 'username' ) { $p2[0] = 'user'; }
            $parms[ $p2[0] ] = urldecode( $p2[1] );
        }
    }
#--- URL parse complete

# This is for conventional parms via query string
    if (isset($_REQUEST['productId']))  { $parms['productid'] = $_REQUEST['productId']; } else { $parms['productid'] = ''; }
    if (isset($_REQUEST['language']))   { $parms['language'] = $_REQUEST['language']; }
    if (isset($_REQUEST['category']))   { $parms['category'] = $_REQUEST['category']; }
    if (isset($_REQUEST['frameId']))    { $parms['frameid']  = $_REQUEST['frameId']; }
    if (isset($_REQUEST['channelList']))    { $parms['channellist'] = $_REQUEST['channelList']; }
    if (isset($_REQUEST['channellList']))   { $parms['channellist'] = $_REQUEST['channellList']; }
    if (isset($_REQUEST['user']))       { $parms['user'] = $_REQUEST['user']; }     # DLink & Viewsonic frames only so far
    if (isset($_REQUEST['username']))   { $parms['user'] = $_REQUEST['username']; } # map the 'username' parm to 'user' -- not sure about this
    if (isset($_REQUEST['fid']))        { $parms['fid'] = $_REQUEST['fid']; }       # = idframe.frames
    if (isset($_REQUEST['pin']))        { $parms['pin'] = $_REQUEST['pin']; }
    if (isset($_REQUEST['route']))      { $parms['route'] = $_REQUEST['route']; }

    dbStart();

#print_r($parms);
    if ( (isset($parms['fid'])) and ($parms['fid'] == 999999) ) { $parms['fid'] = 15; }                       # old demo feed frame id

    if( featureEnabled('enable_handle_route') and isset( $parms['route'] ) ) {
        handleRoute( $parms );
    } else if( featureEnabled('enable_handle_user') and isset( $parms['user'] ) ){
        handleUser( $parms );
    } else {
        handleFrame( $parms );
    }

?>
