<?php
#-------------------------------------------------
# feed.php - Handle a RSS feed request for a specified frame
#
# This script expects the internal frame ID value (idframes) to be supplied rather than the typical
# 'frameId' value that is supplied by the frame.  It is designed to support the 'custom feed URL' method of
# access a frame's feed.
#
# The general purpose 'FrameChannel' compatable feed is supplied by index.php
#
#-------------------------------------------------
## 2011-aug-9 - TimC
#   - Check for existance of RSS file before sending it - duh
#
# 2011-sept-3 - TimC
#   - Fork from Feed.php; parse URL here rather than using rewrite rules
#
# 2011-sept-4 - TimC
#   - Better handle requests with no parms
#   - Specify 'charset=utf-8' in Content-Type header
#
# 2011-sept-12 - TimC
#   - modify as necessary for reorganized dir. structure and shared includes
##-------------------------------------------------
include_once 'inc/dbconfig.php';
include_once 'inc/config.php';
include_once 'inc/helper_gen.php';
include_once 'inc/helper_frame.php';
include_once 'inc/helper_feed.php';

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
#   /feed.php/fid=123/pin=9876
# or
#   /feed.php?fid=123&pin=9876
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

    if (isset($_REQUEST['fid'])) { $parms['fid']=$_REQUEST['fid']; }        # this covers the case where the parms are passed as '?' arguments
    if (isset($_REQUEST['pin'])) { $parms['pin']=$_REQUEST['pin']; }

    dbStart();

echo  print_r $parms;

    if ( (isset($parms['fid'])) and ($parms['fid'] == 999999) ) { $parms['fid'] = 15; }                       # old demo feed frame id

    if ( isset($parms['fid']) and isset($parms['pin']) ) {
        frameCheckIn($parms['fid']);
        $active = isFramePinActive($parms['fid'], $parms['pin']);
    } else {
        $active = 0;
        $fid = 0;
    }

    if ($active) {
        feedActiveFrameFeed($parms['fid']);
    } else {
        if (isset($parms['fid'])) {
            feedInactiveFrameFeed($parms['fid'], '**', 'UKNW');
        } else {
            feedInactiveFrameFeed('Not supplied', '**', 'UKNW');
        }
    }
?>
