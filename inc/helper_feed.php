<?php
#-----------------------------
# 2011-nov-27 - TimC
#   - fix the SQL in feedChannelsListFID() to not return ALL users channesl -- DOH!
#
# 2012-aug-7 - TimC
#   - modify feedActiveFrameFeed() to send a skeleton feed if the requested feed is not available.
#
# 2012-aug-11 - TimC
#   - modify feedRssChannelHead() to accept 'registered' status and set <frameuserinfo:unregistered> appropriately.
#       Default is 'registered' state.
#   - set inactive frame feed TTL to 10m
#
# 2012-aug-24 - TimC
#   - for inactive frames set the item duration of the info pane to 30 (was 3).  DSM-210 (perhaps others) _refreshed_ the image at this interval rather than
#     simply moving the next image in the list.
#   - Modify the channel header for inactive frames to include the frameID in the channel name and description.  DSM-210 frames use this info in the 'getuserlist' call.
#
# 2012-aug-25 - TimC
#   - add feedChannelListUID( $uid, $uname ) to send a list of all channels defined by the specified users.
#   - add feedGetUserList( $parms ) to send a list of users associated with a given frame.
#
# 2012-sept-5 - TimC
#   - add feedActiveUserFeed($uid) to send all content for a given user
#
# 2012-sept-22 - TimC
#   - add feedShowSetupInfo( $fid ) to display setup/activation info
#   - move info panel creation to feedMakeInfoPanel()
#
# 2012-sept-24 - TimC
#   - feedGetUserList() now returns only 1 user.  It also puts 'username' into the 'frameuserinfo:username' field rather than frameID.
#-----------------------------a

#--------
function feedRssUserBody( $uid, $shuffle='N', $item_limit=999 )
#--------
{

$t = '';
$pubDate = '';
$title = '';
#echo "feedRssUserBody:[".$uid."]\n";
    if( $shuffle == 'Y' ) {
        $ord = ' ORDER BY  rand()';
    } else {
        $ord = ' ORDER BY iditems';
    }
    $uid =q( $uid );

    $sql = "SELECT *,DATE_FORMAT( `pubDate`, '%a, %d %b %Y %T GMT' ) AS pubDateF FROM items AS it, user_channels AS uc WHERE uc.user_id=$uid AND it.user_channel_id=uc.iduserchannels" . $ord;
#echo $sql;
    $items_result = mysql_query($sql);
    if (!$items_result) {
        die("[$sql]: Invalid query: " . mysql_error());
    }

    $row = mysql_fetch_assoc( $items_result );
#print_r( $row );
    while ( ($row) && ($item_limit > 0) ) {

        if ( $row{'pubDateF'} ) {
            $pubDate = $row{'pubDateF'};
        } else {
            $pubDate = strftime('%a, %d %b %Y %H:%M:%S GMT', time() );
        }

        if ( $row{'title'} ) {
            $title = $row{'title'};
        } else {
            $title = 'Untitled';
        }
#echo "title:[".$title."]\n";
        $t .= "<item>\n";

        $t .= "    ".'<title>' . htmlentities( $row{'title'}, ENT_COMPAT | ENT_XML1 ) . "</title>\n";
        $t .= "    ".'<link>' . htmlentities( $row{'link'}, ENT_COMPAT | ENT_XML1 ) . "</link>\n";
        $t .= "    ".'<category>' .  htmlentities( $row{'category'}, ENT_COMPAT | ENT_XML1) . "</category>\n";
#        if( $$ref{'description'} ) {
#            $t .= "    ".'<description>' . encode_entities( $$ref{'description'} ) . "</description>\n";
#        } else {
            $t .= "    ".'<description>' . htmlentities('<img src="' . $row{'link'} . '">', ENT_COMPAT | ENT_XML1) . "</description>\n";
#        }
        $t .= "    ".'<pubDate>' .  $pubDate . "</pubDate>\n";
        $t .= "    ".'<guid isPermaLink="false">' .  htmlentities( $row{'guid'}, ENT_COMPAT | ENT_XML1) . "</guid>\n";
        $t .= "    ".'<media:content url="' . $row{'media_content_url'} . '" type="image/jpeg" duration="10" />'."\n";
        $t .= "    ".'<media:thumbnail url="' . $row{'media_thumbnail_url'} . '" />'."\n";

        $t .= "    ".'<tsmx:sourcelink>' . $row{'media_content_url'} . '</tsmx:sourcelink>'."\n";

        $t .= "</item>\n";

        $item_limit--;

        $row = mysql_fetch_assoc( $items_result );

    }

    return $t;

}

#--------
function feedRssHead()
#--------
{
    $t = '';
    $t .= '<?xml version="1.0"  encoding="utf-8"  ?>'."\n";
    $t .= '<rss version="2.0"  xmlns:media="http://search.yahoo.com/mrss/" xmlns:frameuserinfo="http://www.framechannel.com/feeds/frameuserinfo/" xmlns:tsmx="http://www.thinkingscreenmedia.com/tsmx/" >'."\n";

    return $t;
}

#--------
function feedRssTail()
#--------
{
    $t = '';

    $t .= "</rss>\n";

    return $t;
}

#--------
function feedRssChannelHead($user, $ttl, $desc, $reg=TRUE)
#--------
{
#echo 'user:['.$user.'] ['.$ttl.'] ['.$desc.'] ['.$reg.']';
    $t = '';

    $t .= "<channel>\n";
    $t .= "<title>FrameAlbum content for $user</title>\n";
    $t .= "<link>http://www.framealbum.com</link>\n";
    if (isset($desc)) { $t .= "<description>\n$desc\n</description>\n"; }
    if (isset($ttl)) { $t .= "<ttl>$ttl</ttl>\n"; }
    $t .= "<frameuserinfo:firstname>-</frameuserinfo:firstname>\n";
    $t .= "<frameuserinfo:lastname>-</frameuserinfo:lastname>\n";
    $t .= "<frameuserinfo:username>$user</frameuserinfo:username>\n";
    $t .= "<frameuserinfo:unregistered>" . ( $reg ? 'FALSE' : 'TRUE' ) . "</frameuserinfo:unregistered>\n";

    return $t;
}

#--------
function feedRssChannelListItem($title, $link, $category, $desc, $pubDate, $guid, $media_url, $thumb_url)
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

#--------
function feedRssChannelTail()
#--------
{
    $t = '';

    $t .= "</channel>\n";

    return $t;
}

#------------------------------
function feedSendRSS($rss)
#------------------------------
{
    header("Content-Type: application/rss+xml; charset=utf-8");

    echo $rss;

    echo "\n";

    return;
}

#-------------------------
function feedShowSetupInfo( $frameid, $fid )
#-------------------------
{
    $rss = '';
    $frameid = prepDBVal($frameid);

    $sql = "SELECT * FROM frames AS f, users AS u WHERE f.frame_id='$frameid' AND f.user_id=u.idusers";
    $res = mysql_query($sql)or die("ShowSetupInfo lookup failed.");

    $rss = feedRssHead();

    if ( mysql_num_rows($res) > 0 ) {
        $row = mysql_fetch_assoc( $res );
        list ($ret, $active) = frameIsActiveFID( $frameid );
        $rss .= feedRssChannelHead($frameid, 5, 'Setup Info for [' . $frameid . ']', $active);
    } else {
        $rss .= feedRssChannelHead($frameid, 15, 'Setup Info for [' . $frameid . ']', FALSE);
        $active = 0;
    }

    $fn = $GLOBALS['image_path'] . '/'. $frameid.'-info.jpg';
    $url = $GLOBALS['image_url_root'] . '/' . $frameid . '-info.jpg';

    feedMakeInfoPanel( $fid, $fn );

    $icon_url = $GLOBALS['image_url_root'] . '/frame_icon.jpg';
    $iname = ($active ? $row['user_nickname'] : 'Inactive Frame');
    $rss .= feedRssChannelListItem( $iname, '', 'user', 'FrameAlbum user', '', 0,
             $url, $icon_url);

    $rss .= feedRssChannelTail();
    $rss .= feedRssTail();

    return $rss;
}

#-------------------------
function feedGetUserList( $frameid, $fid)
#-------------------------
{
    $rss = '';

    $fid = prepDBVal($fid);

    $sql = "SELECT * FROM frames AS f, users AS u
        WHERE f.idframes='$fid' AND f.user_id=u.idusers";
    $res = mysql_query($sql)or die("GetUserList lookup failed.");

    $rss = feedRssHead();

    if ( mysql_num_rows($res) > 0 ) {
        $row = mysql_fetch_assoc( $res )
        $rss .= feedRssChannelHead($row['username'], 15, 'User list for [' . $frameid . ']', FALSE);
        $icon_url = $GLOBALS['image_url_root'] . '/frame_icon.jpg'; 
        $rss .= feedRssChannelListItem($row['username'], '', 'user', '', '', $row['idusers'],
                 $icon_url, '');
    } else {
        $rss .= feedRssChannelHead('', 5, 'User list for [' . $frameid . ']', TRUE);
        $icon_url = $GLOBALS['image_url_root'] . '/frame_icon.jpg';
        $rss .= feedRssChannelListItem( 'Inactive Frame', '', 'user', 'FrameAlbum user', '', 0,
             $GLOBALS['image_url_root'] . '/' . $frameid . '-info.jpg', $GLOBALS['image_url_root'] . '/unknown-user.png');
    }

    $rss .= feedRssChannelTail();
    $rss .= feedRssTail();

    return $rss;
}

#-------------------------
function feedChannelListFID($fid)
#-------------------------
{
    $rss = '';
    $fid = prepDBVal($fid);

    $sql = "SELECT * FROM frames AS f, frame_channels AS fc, user_channels AS uc, channel_types AS ct
        WHERE f.idframes='$fid' AND fc.frame_id=f.idframes AND uc.iduserchannels=fc.user_channel_id
        AND ct.idchanneltypes=uc.channel_type_id";
    $res = mysql_query($sql)or die("channelList lookup failed.");

    $rss = feedRssHead();
    $rss .= feedRssChannelHead('', 15, 'Channel list for ' . userFindFID($fid) . ' [FID:'. $fid . ']');

    if ( mysql_num_rows($res) > 0 ) {
        while( $row = mysql_fetch_assoc( $res ) ) {
            $icon_url = $GLOBALS['www_url_root'] . $row['frame_icon_url'];
            $rss .= feedRssChannelListItem($row['chan_nickname'], '', $row['channel_category'], '', '', $row['iduserchannels'],
                 $icon_url, '');
        }
    }

    $rss .= feedRssChannelTail();
    $rss .= feedRssTail();

    return $rss;
}

#-------------------------
function feedChannelListUID( $uid, $uname )
#-------------------------
{
    $rss = '';
    $uid = q( $uid );

    $sql = "SELECT * FROM user_channels AS uc, channel_types AS ct
        WHERE uc.iduserchannels=" . $uid . "
        AND ct.idchanneltypes=uc.channel_type_id";
    $res = mysql_query($sql)or die("channelList lookup failed.");

    $rss = feedRssHead();
    $rss .= feedRssChannelHead('', 15, 'Channel list for ' . $uname);

    if ( mysql_num_rows($res) > 0 ) {
        while( $row = mysql_fetch_assoc( $res ) ) {
            $icon_url = $GLOBALS['www_url_root'] . $row['frame_icon_url'];
            $rss .= feedRssChannelListItem($row['chan_nickname'], '', $row['channel_category'], '', '', $row['iduserchannels'],
                 $icon_url, '');
        }
    }

    $rss .= feedRssChannelTail();
    $rss .= feedRssTail();

    return $rss;
}

#---------------------------
function feedActiveFrameFeed($fid)
#---------------------------
{

    $fn = $GLOBALS['rss_path'] . '/00/00/'.$fid.'.rss';

    if ( is_readable($fn) ) {
        $fh = fopen($fn, 'r');
        $rss = fread($fh, filesize($fn));
        fclose($fh);
    } else {                # feed file is missing -- send a skeleton
        $d = 'RSS file not found or not readable:['.$fn.']';
        $url = $GLOBALS['image_url_root'] . '/Feed_Not_Avail.jpg';

        $rss = feedRssHead();
        $rss .= feedRssChannelHead('public', 60, 'Frame Feed Is Unavailable');

        $rss .= '<item>
        <title>FrameAlbum Info</title>
        <link>'.$url.'</link>
        <category>FrameAlbum Info</category>
        <description>&lt;img src=&quot;'.$url.'&quot;&gt;</description>
        <pubDate>Thu, 23 Jun 2011 17:04:46 -0400</pubDate>
        <guid isPermaLink="false">30f8b8d1-80af-38e7-8616-b3e793dc289b</guid>
        <media:content url="'.$url.'" type="image/jpeg" height="480" width="800" duration="180" />
        <media:thumbnail  url="'.$url.'" height="60" width="60" />
        <tsmx:sourcelink>'.$url.'</tsmx:sourcelink>
</item>'."\n";

    $rss .= feedRssChannelTail();
    $rss .= feedRssTail();

    }

    feedSendRSS($rss);

    return;
}

#---------------------------
function feedActiveUserFeed( $uid )
#---------------------------
{
$rss = '';
    $url = $GLOBALS['image_url_root'] . '/Feed_Not_Avail.jpg';

    $rss = feedRssHead();
    $rss .= feedRssChannelHead($uid, 60, 'Feed for ['.$uid.']');

#    $rss .= '<item>
#    <title>FrameAlbum Info</title>
#    <link>'.$url.'</link>
#    <category>FrameAlbum Info</category>
#    <description>&lt;img src=&quot;'.$url.'&quot;&gt;</description>
#    <pubDate>Thu, 23 Jun 2011 17:04:46 -0400</pubDate>
#    <guid isPermaLink="false">30f8b8d1-80af-38e7-8616-b3e793dc289b</guid>
#    <media:content url="'.$url.'" type="image/jpeg" height="480" width="800" duration="180" />
#    <media:thumbnail  url="'.$url.'" height="60" width="60" />
#    <tsmx:sourcelink>'.$url.'</tsmx:sourcelink>
#</item>'."\n";

    $rss .= feedRssUserBody( $uid );
    $rss .= feedRssChannelTail();
    $rss .= feedRssTail();

    feedSendRSS($rss);

    return;
}

#------------------------------
function feedMakeInfoPanel( $fid, $fn )
#------------------------------
{
#echo 'MakeInfoPanel:['.$fid.'] ['.$fn.']';

    if ( isset($fid) and ( strlen( $fid ) > 0 ) ) {
        $fid = prepDBVal($fid);
        $res = mysql_query("SELECT activation_key,frame_id FROM frames WHERE idframes='$fid'");
        if (!$res) { die("Invalid query: " . mysql_error()); }
        $row = mysql_fetch_assoc($res);     # get the first row
        $akey = $row['activation_key'];
        $prodid = $row['product_id'];
        $frameid = $row['frame_id'];
        list ($idproduct, $manuf, $model, $hres, $vres) = frameGetProductInfo($prodid);
    } else {
        $fid = 'Unknown';
        $akey = '**ERROR**';
        $prodid = 'Unknown';
        $frameid = 'Unknown';
        $hres = 640; $vres=480;
    }

    list ($idproduct, $manuf, $model, $hres, $vres) = frameGetProductInfo($prodid);

    $fontName = 'Helvetica';
    $fontColor = '#efefef';
    $fontSize = 36;
    $text = "FrameChannel have ceased operation.

You can register for the new FrameAlbum
service at www.framealbum.com.

Your Activation Key is $akey

Your frameID is $frameid";

    # make a transparent pallete
    $pallete = new Imagick;
    $pallete->newimage($hres, $vres, "green");
    $pallete->setimageformat("jpg");

    # make a draw object with settings
    $draw = new imagickdraw();
    $draw->setgravity(imagick::GRAVITY_CENTER);
    $draw->setfont("$fontName");
    $draw->setfontsize($fontSize);

    # set font color
    $draw->setfillcolor($fontColor);
    # center annotate on top of offset annotates
    $pallete->annotateImage ( $draw, 0 ,0, 0, $text );

    $pallete->writeImage($fn);

    return;
}

#------------------------------
function feedInactiveFrameFeed($fid, $frameid, $prodid, $akey)
#------------------------------
{
    if (!(isset($prodid))) { $prodid = 'Not_Supplied'; }
    list ($idproduct, $manuf, $model, $hres, $vres) = frameGetProductInfo($prodid);

    $fn = $GLOBALS['image_path'] . '/'. $frameid.'-info.jpg';
    $url = $GLOBALS['image_url_root'] . '/' . $frameid . '-info.jpg';

#    if ( !(file_exists($fn)) ) {
        feedMakeInfoPanel( $fid, $fn );
#    }

    $rss = feedRssHead();
    $rss .= feedRssChannelHead('inactive_frame' . $frameid, 10, 'Inactive Frame (' . $frameid . ')', FALSE);

$rss .= '<item>
        <title>FrameAlbum Info</title>
        <link>'.$url.'</link>
        <category>FrameAlbum Info</category>
        <description>&lt;img src=&quot;'.$url.'&quot;&gt;</description>
        <pubDate>Thu, 23 Jun 2011 17:04:46 -0400</pubDate>
        <guid isPermaLink="false">30f8b8d1-80af-38e7-8616-b3e793dc289b</guid>
        <media:content url="'.$url.'" type="image/jpeg" height="480" width="800" duration="30" />
        <media:thumbnail  url="'.$url.'" height="60" width="60" />
        <tsmx:sourcelink>'.$url.'</tsmx:sourcelink>
</item>'."\n";

    $rss .= feedRssChannelTail();
    $rss .= feedRssTail();

    feedSendRss($rss);

    return;
}
?>
