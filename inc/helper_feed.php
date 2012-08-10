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
#-----------------------------

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
    $t = '';

    $t .= "<channel>\n";
    $t .= "<title>FrameAlbum content for $user</title>\n";
    $t .= "<link>http://www.framealbum.com</link>\n";
    if (isset($desc)) { $t .= "<description>\n$desc\n</description>\n"; }
    if (isset($ttl)) { $t .= "<ttl>$ttl</ttl>\n"; }
    $t .= "<frameuserinfo:firstname>-</frameuserinfo:firstname>\n";
    $t .= "<frameuserinfo:lastname>-</frameuserinfo:lastname>\n";
    $t .= "<frameuserinfo:username>$user</frameuserinfo:username>\n";
    $t .= "<frameuserinfo:unregistered>" . ( $reg  ? 'FALSE' : 'TRUE' ) . "</frameuserinfo:unregistered>\n";

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
        <media:content url="'.$url.'" type="image/jpeg" height="480" width="800" duration="3" />
        <media:thumbnail  url="'.$url.'" height="60" width="60" />
        <tsmx:sourcelink>'.$url.'</tsmx:sourcelink>
</item>'."\n";

    $rss .= feedRssChannelTail();
    $rss .= feedRssTail();

    }

    feedSendRSS($rss);

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

    if ( !(file_exists($fn)) ) {
        if (($fid > 0) and ($akey == '')) {
            $fid = prepDBVal($fid);
            $res = mysql_query("SELECT activation_key FROM frames WHERE idframes=$fid");
            if (!$res) { die("Invalid query: " . mysql_error()); }
            $row = mysql_fetch_assoc($res);     # get the first word
            $akey = $row['activation_key'];
        }
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
        $pallete->annotateImage ( $draw,0 ,0, 0, $text );

        $pallete->writeImage($fn);
    }

    $rss = feedRssHead();
    $rss .= feedRssChannelHead('inactive_frame', 10, 'Channel for inactive frame', FALSE);

$rss .= '<item>
        <title>FrameAlbum Info</title>
        <link>'.$url.'</link>
        <category>FrameAlbum Info</category>
        <description>&lt;img src=&quot;'.$url.'&quot;&gt;</description>
        <pubDate>Thu, 23 Jun 2011 17:04:46 -0400</pubDate>
        <guid isPermaLink="false">30f8b8d1-80af-38e7-8616-b3e793dc289b</guid>
        <media:content url="'.$url.'" type="image/jpeg" height="480" width="800" duration="3" />
        <media:thumbnail  url="'.$url.'" height="60" width="60" />
        <tsmx:sourcelink>'.$url.'</tsmx:sourcelink>
</item>'."\n";

    $rss .= feedRssChannelTail();
    $rss .= feedRssTail();

    feedSendRss($rss);

    return;
}
?>
