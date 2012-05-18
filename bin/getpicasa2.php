<?php
#---------------------------
# getpicasa2.php - PicasaWeb feed retriever
#
# 2011-jul-27 - TimC v2.0
#   - This version pulls all public photos, not just shots in an album named 'Public'
#
# 2011-aug-2 - TimC
#   - convert whitespace in usernames to '_' so that the Zend calls don't puke
#   - Generate a 'invalid user -- please verify' text panel when user is not found
#
# 2011-aug-18 - TimC
#   - Update user_channels.status with fail messaging
#
# 2011-aug-29 - TimC
#   - set item.catgory to 'photo'
#   - convert 'echo' to SysMsg();
#   - support -d (debug) CLI option
#
# 2011-aug-30 - TimC
#   - save stats to grabber_stats
#
# 2011-sept-1 - TimC
#   - Increase default chan max to 300 items
#
# 2011-sept-18 - TimC
#   - use dbStart()
#   - modify for new directory structure
#--------------------------
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata_Photos');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_Photos_UserQuery');
Zend_Loader::loadClass('Zend_Gdata_Photos_PhotoQuery');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');

require_once 'inc/dbconfig.php';
require_once 'inc/config.php';
require_once 'inc/helpers.php';
require_once 'inc/mktextpanel.php';
require_once 'inc/helpers.php';

$GLOBALS['DEFAULT_MAX_ITEMS'] = 300;

#----------------------------
function grabPicasaChannel($cid, $attrib, $item_limit)
#----------------------------
{


    $sql = "DELETE from items WHERE user_channel_id=$cid";
    $r = mysql_query($sql);
    if (!$r) {
        die("[$sql]: Invalid query: " . mysql_error());
    }

    $attribs = preg_split("/\|/", $attrib);
    if (empty($item_limit)) { $limit = $GLOBALS['DEFAULT_MAX_ITEMS']; } else { $limit = $item_limit; }

    SysMsg(MSG_INFO, 'Retrieving Picasa content for ['.$attribs[0]."]  chanID:[".$cid."]  limit:[".$limit."]----------");
    $cnt = getPicasaRecent($service, $attribs[0], $max_age, $attribs[1], $cid, $limit);
    if ($cnt > $max_items) { $max_items = $cnt; };

    if ($cnt == -1) {
        SysMsg(MSG_WARN, 'UserID ['.$attribs[0].'] not found on Picasa.');
        $status = 'No matching PicasaWeb user found.';
        $attrib_valid = 'N';
    } else if ($cnt == 0) {
        SysMsg(MSG_WARN, 'No public Picasa photos found for this user.');
        $status = 'No public photos found for this PicasaWeb user.';
        $attrib_valid = 'Y';
    } else {
        SysMsg(MSG_INFO, 'Found ' . $cnt . " items.");
        $status = 'Last update found ' . $cnt . ' photos for this channel.';
        $attrib_valid = 'Y';
    }

    $sql = "UPDATE user_channels SET last_updated=now(),status='$status',attrib_valid='$attrib_valid' WHERE iduserchannels=$cid";
    $r = mysql_query($sql);
    if (!$r) {
        die("[$sql]: Invalid query: " . mysql_error());
    }

    return $cnt;
}

#----------------------------
function getPicasaRecent($service, $username, $max_age, $tags, $chan_id, $item_limit)
#----------------------------
{
    $query = new Zend_Gdata_Photos_UserQuery();
    $query->setUser(  preg_replace('/\s/i', '_', $username) );  # fix some invalid chars so the setUser call doesn't puke.
    $query->setType('feed');
    $query->setKind('photo');
    if (!empty($item_limit)) {
        SysMsg(MSG_DEBUG, "\tSetting MaxResults:[".$item_limit."]");
        $query->setMaxResults($item_limit);
    }

    try {
        $userFeed = $service->getUserFeed(null, $query);
    } catch (Zend_Gdata_App_Exception $e) {
        SysMsg(MSG_WARN, "Error: " . $e->getMessage());
#--- make an error panel and insert it into the items table
        list ($contentUrl, $id) = mkTextPanel($chan_id, 'gold', 'white', 36, wordwrap("PicasaWeb user '$username' not found -- please verify.", 30), 600, 480);
        SysMsg(MSG_DEBUG, "\tContent:[".$contentUrl."]");

        return -1;
    }

    $cnt = 0;

    foreach ($userFeed as $userEntry) {
        $guid = $userEntry->getGphotoId()->getText();
        $title = $userEntry->title->text;
        $pubDate = $userEntry->published->text;

        if ($userEntry->getMediaGroup()->getContent() != null) {
            $mediaContentArray = $userEntry->getMediaGroup()->getContent();
            $contentUrl = $mediaContentArray[0]->getUrl();
            $contentUrl = preg_replace('/^https:/', 'http:', $contentUrl, 1);                         # convert to non HTTP links for dumb frames.
        }

        if ($userEntry->getMediaGroup()->getThumbnail() != null) {
            $mediaThumbArray = $userEntry->getMediaGroup()->getThumbnail();
            $thumbUrl = $mediaThumbArray[0]->getUrl();
            $thumbUrl = preg_replace('/^https:/', 'http:', $thumbUrl, 1);                         # convert to non HTTP links for dumb frames.
        }

        $link = $contentUrl;

        SysMsg(MSG_DEBUG, "\tContent:[".$contentUrl."]");
#        SysMsg(MSG_DEBUG, "\tThumb:[".$thumbUrl."]";

        $title = prepDBVal($title);
        $link = prepDBVal($link);
        $chan_id = prepDBVal($chan_id);
        $pubDate = prepDBVal($pubDate);
        $guid = prepDBVal($guid);
        $contentUrl = prepDBVal($contentUrl);
        $thumbUrl = prepDBVal($thumbUrl);

        $sql = "INSERT INTO items (title,link,user_channel_id,description,pubDate,guid,media_content_url,media_thumbnail_url,media_content_duration,category) VALUES ('$title', '$link', $chan_id, '', '$pubDate','$guid','$contentUrl','$thumbUrl',15,'photo')";

        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg = '#FAIL - An error occured.';
        }
        $cnt++;
    }

    if ($cnt == 0) {
#--- make a message panel and insert it into the items table
        list ($url, $id) = mkTextPanel($chan_id, 'gold', 'white', 36, wordwrap("No public PicasaWeb photos found for '$username'", 30), 600, 480);
    }

    return $cnt;
}

#---------------------------
# M A I N
#---------------------------

    $opts = getopt("dc:");
    if (isset($opts['d'])) { $GLOBALS['DEBUG'] = 1; }
    if ( (isset($GLOBALS['DEBUG'])) and ($GLOBALS['DEBUG']) ) { $GLOBALS['MSG_PRINT_THRESHOLD'] = MSG_DEBUG; }
    SysMsg(MSG_DEBUG, 'DEBUG mode is set.');

    $start = time();

    dbStart();

    $msg = '';

    $max_age = 365;

    $chn_cnt = 0;
    $item_cnt = 0;
    $max_items = 0;

    $sql = "SELECT * FROM user_channels WHERE channel_type_id=8 AND active='Y' AND attrib_valid!='N'";

    $result = mysql_query($sql);
    if (!$result) {
        die("[$sql]: Invalid query: " . mysql_error());
        $msg = '#FAIL - An error occured.';
    } else {
        if (mysql_num_rows( $result ) > 0) {
            $serviceName = Zend_Gdata_Photos::AUTH_SERVICE_NAME;
            $user = "PICASAUSER@gmail.com";
            $pass = "PICASAPASSWORD";
#            $client = Zend_Gdata_ClientLogin::getHttpClient($user, $pass, $serviceName);
#            $service = new Zend_Gdata_Photos($client);
            $service = new Zend_Gdata_Photos();

            while( $row = mysql_fetch_assoc( $result ) ) {
                $cid = $row['iduserchannels'];
                $sql = "DELETE from items WHERE user_channel_id=$cid";
                $r = mysql_query($sql);
                if (!$r) {
                    die("[$sql]: Invalid query: " . mysql_error());
                }
                $cid = $row['iduserchannels'];
                $attribs = preg_split("/\|/", $row['attrib']);
                if (empty($row['item_limit'])) { $limit = $GLOBALS['DEFAULT_MAX_ITEMS']; } else { $limit = $row['item_limit']; }

                SysMsg(MSG_INFO, 'Retrieving content for ['.$attribs[0]."]  chanID:[".$cid."]  limit:[".$limit."]----------");
                $cnt = getPicasaRecent($service, $attribs[0], $max_age, $attribs[1], $cid, $limit);
                if ($cnt > $max_items) { $max_items = $cnt; };

                if ($cnt == -1) {
                    SysMsg(MSG_WARN, "UserID not found on Picasa");
                    $status = 'No matching PicasaWeb user found.';
                    $attrib_valid = 'N';
                } else if ($cnt == 0) {
                    SysMsg(MSG_WARN, 'No public photos found for this user.');
                    $status = 'No public photos found for this PicasaWeb user.';
                    $attrib_valid = 'Y';
                    $chn_cnt++;
                } else {
                    SysMsg(MSG_INFO, 'Found ' . $cnt . " items.");
                    $status = 'Last update found ' . $cnt . ' photos for this channel.';
                    $attrib_valid = 'Y';
                    $chn_cnt++;
                    $item_cnt += $cnt;
                }
                $sql = "UPDATE user_channels SET last_updated=now(),status='$status',attrib_valid='$attrib_valid' WHERE iduserchannels=$cid";
                $r = mysql_query($sql);
                if (!$r) {
                    die("[$sql]: Invalid query: " . mysql_error());
                }
            }
        } else {
            $msg = 'No active PicasaWeb channels.';
        }
        $msg = $chn_cnt . ' channels were loaded with ' . $item_cnt . ' items; average of '. ($item_cnt/$chn_cnt) . ' per channel; max was ' . $max_items . '.';
    }

    SysMsg(MSG_INFO, $msg);
    $et = (time() - $start);
    SysMsg(MSG_INFO, 'Elapsed time: ' . $et . 's  Time per channel: ' . ($et / $chn_cnt) . "s");

    $sql = "INSERT INTO grabber_stats (channel_type_id, rundate, wall_time, stats) VALUES (8, now(), $et, '" . $chn_cnt.'|'.$item_cnt.'|'.$et . "')";
    $r = mysql_query($sql);
    if (!$r) {
        SysMsg(MSG_CRIT, "[$sql]: Invalid query: " . mysql_error());
    }

    $cmd = "curl -k -i -X POST -d '" . '{"accessKey": "SmQC4vt62IyzBuDPzrzi", "streamName": "getpicasa_channels", "point": ' .$chn_cnt . "}' https://beta.leftronic.com/customSend/";
    SysMsg(MSG_INFO, 'CMD:['.$cmd.']');
    system($cmd);

    $cmd = "curl -k -i -X POST -d '" . '{"accessKey": "SmQC4vt62IyzBuDPzrzi", "streamName": "getpicasa_photos", "point": ' .$item_cnt . "}' https://beta.leftronic.com/customSend/";
    SysMsg(MSG_INFO, 'CMD:['.$cmd.']');
    system($cmd);

    $cmd = "curl -k -i -X POST -d '" . '{"accessKey": "SmQC4vt62IyzBuDPzrzi", "streamName": "getpicasa_timeperchannel", "point": ' .($et / $chn_cnt) . "}' https://beta.leftronic.com/customSend/";
    SysMsg(MSG_INFO, 'CMD:['.$cmd.']');
    system($cmd);

?>
