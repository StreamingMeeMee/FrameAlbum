<?php
#-------------------------------------------
# 2011-jun-28 - TimC
#   Basic frame helper functions
#
# 2011-aug-21- TimC
#   - Reduce frame PIN to 4 digits - not all frames allow > 4 digit PINs
#-------------------------------------------

#----------------------------
function frameSample($id)
#----------------------------
# display a 5 x n grid of sample images from this frame 
#----------------------------
{
    $msg = '';
    $html = '';

    if (!isset($id))  { $id = 0; }
    $id = prepDBVal($id);

    if ( $GLOBALS['enable_frame_samples'] ) {
        $sql = "SELECT * FROM frame_items AS fi, items AS i WHERE fi.frame_id='$id' AND i.iditems=fi.item_id ORDER BY pubDate ASC";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
        }

        if (mysql_num_rows( $result ) > 0) {
            $html .= '<div class="body_title">Sample images from this frame</div>';
            $html .= '<div class="body_textarea">';
            $html .= '<table border="0"><tr>';
            $rc = 1;
            while( $row = mysql_fetch_assoc( $result ) ) {
                $html .= '<td align="center"><img style="margin: 5px;" width="118" height="118" title="'.$row['title'].'" alt="'.$row['title'].'" src="'.$row['media_thumbnail_url'].'"/></td>';
                if ($rc > 4) {
                    $rc = 1;
                    $html .= '</tr><tr>';
                } else {
                    $rc++;
                }
            }
            $html .= '</tr></table>';
            $html .= '</div>';
        } else {
            $html = '<div class="body_textarea">No samples available for this frame.</div>';
        }

    } else {
        $html = 'Frame sample feature not enabled.';
    }

    return array ($msg, $html);
}

#----------------------------
function frameDel($fid)
#----------------------------
# Removes the specified user frame and all associated data.
# Note: This function DOES NOT check for existing entries before attempting DELETE.
#============================
{
    if ( $fid != 0 ) {                    # nothing to update
        $fid = prepDBVal($fid);

        $sql = "DELETE FROM frame_channels WHERE frame_id='$fid'";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Error deleting frame_channels: " . mysql_error());
        }

        $sql = "DELETE FROM frame_items WHERE frame_id='$fid'";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Error deleting frame_items: " . mysql_error());
        }

        $sql = "DELETE FROM frames WHERE idframes='$fid' LIMIT 1";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Error deleting frame: " . mysql_error());
        }

    }

    return;
}

#-----------------------------------------
function getProdID($pid)
#-----------------------------------------
{
    $html = '';

    if (0 == $pid) {
        $html = '* Unspecified';
    } else { 
        $sql = "SELECT * FROM product_ids WHERE idproduct = $pid";
        $res = mysql_query($sql)or die("Product ID get failed");
        if ( mysql_num_rows($res) == 1) {
            $row = mysql_fetch_assoc( $res ); 
            $html = $row['manuf'] .': ' . $row['model'];
        } else {
            $html = '* Unknown';
        }
    }

  return $html;
}

#-----------------------------------------
function optionProdID($pid)
#-----------------------------------------
{
  $html = '';

  $sql = "SELECT * FROM product_ids WHERE active='Y' ORDER BY manuf, model ASC";
  $res = mysql_query($sql)or die("Product ID  population failed");

  $html = '';
  $html .= "<OPTION value=''";
  if (0 == $pid) { $html .= ' SELECTED '; }
  $html .= ">Select...</OPTION>";

  while(  $row = mysql_fetch_assoc( $res ) ) {
    $html .= "<OPTION value='" . $row['idproduct'] ."'";
    if ($row['idproduct'] == $pid) { $html .= " SELECTED "; }
    $html .= ">" . $row['manuf'] .': ' . $row['model'] . "</OPTION>";
  }

  return $html;
}

#----------------------------
function frameAddItem($fid, $cid, $itm)
#----------------------------
{
    $msg = '';

    if (!isset($fid))   { $fid = 0; }
    if (!isset($cid))   { $cid = 0; }
    if (!isset($itm))   { $itm = 0; }

    if ( ($fid != 0) and ($cid != 0) and ($itm != 0) ) {                    # nothing to add
        $fid = prepDBVal($fid);
        $cid = prepDBVal($cid);
        $itm = prepDBVal($itm);

        $sql = "INSERT INTO frame_items (frame_id, user_channel_id, item_id) VALUES ($fid, $cid, $itm)";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg .= mysql_error();
        }

        if ($result) {              # insert was OK
            $ret = mysql_insert_id();
#            $msg .= 'Item added successfully.';
        } else {                    # not so much
            $ret = 0;
            $msg .= 'Item was NOT added.';
        }
    } else {
        $ret = 0;
        $msg .= 'FrameID, ChanID or ItemID missing fid:['.$fid.']  cid:['.$cid.']  itm:['.$itm.']';
    }

    return array ($ret, $html);
}

#----------------------------
function frameAddChan($fid, $cid)
#----------------------------
{
    $msg = '';
    $html = '';

    if (!isset($fid))   { $fid = 0; }
    if (!isset($cid))   { $cid = 0; }

    if ( ($fid != 0) and ($cid != 0) ) {                    # nothing to add
        $fid = prepDBVal($fid);
        $cid = prepDBVal($cid);

        $sql = "INSERT INTO frame_channels (frame_id, user_channel_id, active, last_updated) VALUES ($fid, $cid, 'Y', NULL)";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg .= mysql_error();
        }

        if ($result) {              # insert was OK
            $ret = mysql_insert_id();
            $msg .= 'Channel added successfully.';
        } else {                    # not so much
            $ret = 0;
            $msg .= 'Channel was NOT added.';
        }
    } else {
        $ret = 0;
        $msg .= 'FrameID or ChanID missing fid:['.$fid.']  cid:['.$cid.']';
    }

    return array ($msg, $html);
}

#----------------------------
function frameDelChan($fid, $cid)
#----------------------------
{
    $msg = '';
    $html ='';

    if (!isset($fid))   { $fid = 0; }
    if (!isset($cid))   { $cid = 0; }

    if ( ($fid != 0) and ($cid != 0) ) {                    # nothing to add
        $fid = prepDBVal($fid);
        $cid = prepDBVal($cid);

        $sql = "DELETE FROM frame_channels WHERE frame_id=$fid AND user_channel_id=$cid";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg .= mysql_error();
        }

        if ($result) {              # insert was OK
            $ret = mysql_insert_id();
            $msg .= 'Channel removed successfully.';
        } else {                    # not so much
            $ret = 0;
            $msg .= 'Channel was NOT removed.';
        }
    } else {
        $ret = 0;
    }

    return array ($msg, $html);
}

#----------------------------
function frameAdd($uid, $fid, $nick, $prodid, $acv, $shuffle, $pin)
#----------------------------
# Adds a new frame with the given attributes. 
# Note: This function DOES NOT check for existing entries before attempting INSERT.
#
# Returns: idframes of new frames entry; 0 on error.
#============================
{
    $msg = '';

    if (!isset($nick))  { $nick = ''; }
    if (!isset($fid))   { $fid = ''; }
    if (!isset($prodid)) { $prodid = ''; }
    if (!isset($acv))   { $acv = 'N'; }                             # If not specified frame is NOT active
    if (!isset($shuffle)) { $shuffle = 'N'; }
    if (!isset($uid))   { $uid = 'NULL'; }
    if (!isset($pin) or ($pin == 0)) { $pin = rand(1, 9999); }

    if (strlen($nick) == 0) { $nick = 'My Frame'; }                 # default framename

    if ( (strlen($fid) != 0) and ($uid != 0) ) {                    # nothing to add
        $fid = prepDBVal($fid);
        $uid = prepDBVal($uid);
        $nick = prepDBVal($nick);
        $prodid = prepDBVal($prodid);
        $acv = prepDBVal($acv);
        $shuffle = prepDBVal($shuffle);
        $pin - prepDBVal($pin);

        $sql = "INSERT INTO frames (frame_id, user_id, user_nickname, active, shuffle_items, product_id, feed_pin, created) VALUES ('$fid', $uid, '$nick', '$acv', '$shuffle', '$prodid', $pin, now())";
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
        $msg = "Sorry, parms are missing -- can't add.  fid:[".$fid.']  uid:['.$uid.']'; 
    }

    return array ($ret, $msg);
}

#----------------------------
function frameUpdate($fid,$frameid,$nick,$prodid,$acv, $shuffle)
#----------------------------
# Updates a frame with the given attributes.
# Note: This function DOES NOT check for existing entries before attempting UPDATE.
#
#============================
{
    if (!isset($nick))  { $nick = ''; }
    if (!isset($fid))   { $fid = 0; }
    if (!isset($frameid))   { $frameid = ''; }
    if (!isset($prodid)) { $prodid = ''; }
    if (!isset($acv))   { $acv = 'N'; }                             # If not specified frame is NOT active
    if (!isset($shuffle)) { $shuffle = 'N'; }

    if (strlen($nick) == 0) { $nick = 'My Frame'; }                 # default framename

    if ( $fid != 0 ) {                    # nothing to update
        $fid = prepDBVal($fid);
        $frameid= prepDBVal($frameid);
        $nick = prepDBVal($nick);
        $prodid = prepDBVal($prodid);
        $acv = prepDBVal($acv);
        $shuffle = prepDBVal($shuffle);

        $sql = "UPDATE frames SET frame_id='$frameid', user_nickname='$nick', active='$acv', shuffle_items='$shuffle', product_id='$prodid' WHERE idframes=$fid LIMIT 1";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
        }

        if ($result) {              # insert was OK
            $ret = true;
        } else {                    # not so much
            $ret = 'false';
            $msg = 'Update failed.';
        }
    } else {
        $ret = false ;
    }

    return array ($ret, $msg);
}

#----------------------------
function frameCheckIn($fid, $prodid)
#----------------------------
# 'touch's a frame.  If it does not exist it is added.
#
# Returns: 0 on error, 1 if specified frame was touched else idframe of new frame.  Yes, I realized this is ambiguous if the idframe==1 <TODO>
#============================
{
    if (!isset($fid))   { $fid = ''; }
    if (!isset($prodid)) { $prodid = 'NULL'; }

    if ( (isset($fid)) && (strlen($fid) != 0) ) {                     # don't add frames with no ID
        $fid = prepDBVal($fid);
        $prodid = prepDBVal($prodid);

        $sql = "UPDATE frames SET product_id='$prodid', last_seen = now() WHERE frame_id = '$fid'";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $ret = 0;
        } else {
            $ret = mysql_affected_rows();
            if ($ret == 0) {   # no frame with this ID, add it
                $ret = frameAdd(NULL,$fid,NULL,$prodid,'N');
            }
        }
    } else {
        $ret = 0;
    }

    return $ret;
}

#----------------------------
function frameUserEnumHTML($uid)
#----------------------------
# Returns a HTML formated list of a user's frames
#
#============================
{
$ret = '';
$item_cnt = 0;

    if (!isset($uid))   { $uid =0; }

    if ($uid > 0) {                     # don't add frames with no ID
        $uid = prepDBVal($uid);

        $sql = "SELECT * FROM frames WHERE user_id = '$uid'";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $ret = '<p>#FAIL - An error occured.</p>';
        } else {
            $ret .= '<table border="0"><tr>';
            if (mysql_num_rows( $result ) > 0 ) {
                while( $row = mysql_fetch_assoc( $result ) ) {
                    if ($item_cnt > $GLOBALS['row_item_limit']) { $ret .= "</tr>\n<tr>";  $item_cnt = 0; }
                    $ret .= '<td align="center"><a href="frame.php?fid='.$row['idframes'].'" title="View frame details"><img src="/images/frame.png" title="View frame details" alt="View frame details"/><br/>' . $row['user_nickname'] . '</a></td>';
                    $item_cnt++;
                }
            }
            if ($item_cnt > $GLOBALS['row_item_limit']) { $ret .= "</tr>\n<tr>";  $item_cnt = 0; }
            $ret .= '<td align="center"><a href="frame.php" title="Add a frame"><img src="/images/add_frame.png" title="Add a frame" alt="Add a frame"/><br/>Add a new frame</a></td>';
            $ret .= '</tr></table>';
        }
    } else {
        $ret = '<p>&nbsp;</p>';
    }

    return $ret;
}

#----------------------------
function frameInfoHTML($uid, $fid)
#----------------------------
# Returns a HTML formated detail of a given frame
#
#============================
{
    $msg = '';
    $ret = '';
    $feed_pin = '';
    $item_cnt = 0;

    if (!isset($fid))   { $fid =0; }
    if (!isset($fid))   { $uid = 0;} 

    $ret = '<div class="body_title">Frame details <a href="/frame.php?fid='.$fid.'&action=edit">(edit)</a></div>';
    $ret .= '<div class="body_textarea">';

    if ($fid > 0) {                     # don't add frames with no ID
        $fid = prepDBVal($fid);

        $sql = "SELECT * FROM frames WHERE idframes = '$fid'";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg = '#FAIL - An error occured.';
        } else {
            if (mysql_num_rows( $result ) == 1) {
                $row = mysql_fetch_assoc( $result );
                $ret .= '<table border="0" width="100%">'."\n";
                $ret .= '<tr><td rowspan="4" valign="top"><a href="/frame.php?fid=' . $fid . '&action=edit" title="Edit frame details"><img src="/images/frame.png" width="128" title="Edit frame details" alt="Edit frame details"/></a></td>';
                $ret .= '<td>Nickname:</td><td>' . $row['user_nickname'] . "</td></tr>\n";
#                $ret .= '<tr><td>Frame ID:</td><td>' . $row['frame_id'] . "</td></tr>\n";
                $ret .= '<tr><td>Model:</td><td>' .  getProdID( $row['product_id'] ) . "</td></tr>\n";
                $ret .= '<tr><td>Shuffle images:</td><td>' . showActiveStatus( $row['shuffle_items'] ) . "</td></tr>\n";
#                $ret .= '<tr><td>Last Seen:</td><td>' . $row['last_seen'] . "</td></tr>\n";
                $feed_url =  $GLOBALS['rss_feed_url'] . '?fid='.$fid.'&pin='. $row['feed_pin'];
                $ret .= '<tr><td>Feed URL:</td><td><a type="application/rss+xml" href="'.$feed_url.'">' . $feed_url . ' <img src="/images/rss_icon.png" alt="RSS feed" title="RSS feed" height="16"/></a></td></tr>'."\n";
                $ret .= "</table>\n";

                $sql = "SELECT * from frame_channels AS fc, user_channels AS uc, channel_types AS ch WHERE fc.frame_id=$fid AND uc.iduserchannels=fc.user_channel_id AND uc.channel_type_id=ch.idchanneltypes";
                $result = mysql_query($sql);
                if (!$result) {
                    die("[$sql]: Invalid query: " . mysql_error());
                    $msg .= '#FAIL - An error occured.';
                } else {
                    if (mysql_num_rows( $result ) > 0) {
                        $ret .=  '<div class="body_title">Channels on this Frame</div>';
                        $ret .= '<table border="0"><tr>'."\n";
                        while( $row = mysql_fetch_assoc( $result ) ) {
                            if ($item_cnt > $GLOBALS['row_item_limit']) { $ret .= "</tr>\n<tr>";  $item_cnt = 0; }
                            $ret .= '<td align="center"><a href="channel.php?cid='.$row['iduserchannels'].'"><img src="'.$row['channel_icon_url'].'" width="128" title="View channel details" alt="View channel details"/><br/>' . $row['chan_nickname'] . '</a>&nbsp;<a href="/frame.php?fid='.$fid.'&action=rmch&cid='.$row['iduserchannels'].'"><img height="18" src="/images/knobs/Remove_Red.png" alt="Remove this channel from this frame" title="Remove this channel from this frame"/></a></td>'."\n";
                            $item_cnt++;
                        }
                        $ret .= "</tr></table>\n";
                    } else {
                        $msg .= 'No channels on this frame -- you should add one.';
                    }
                }
                $ret .= '<br/>';
                $ret .= '<div class="body_title">Add a channel</div>';
#                list ($m, $h) = channelTypeEnumHTML();
                list ($m, $h) = channelUserAvailFrame($uid, $fid);
                $msg .= $m;
                $ret .=  '<div class="body_textarea"><div align="justify">' . $h . '</div></div>';
            } else {
                $msg = 'No info for frame ['.$fid.'].';
            }
        }
    } else {
        $msg = 'No info for frame ['.$fid.'].';
    }

    return array ($msg, $ret);
}

#----------------------------
function frameForm($fid)
#----------------------------
# Returns a HTML form to add/update a frame
#
#============================
{
    $msg = '';
    $html = '';

    if (!isset($fid))   { $fid =0; }

    if ($fid > 0) {                     # get info for existing frame
        $fid = prepDBVal($fid);

        $sql = "SELECT * FROM frames WHERE idframes = $fid";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg = '#FAIL - An error occured.';
        } else {
            if (mysql_num_rows( $result ) == 1) {
                $row = mysql_fetch_assoc( $result );
                $nickname = $row['user_nickname'];
                $frameid = $row['frame_id'];
                $prodid = $row['product_id'];
                $shuffle = $row['shuffle_items'];
            } else {
                $msg = 'frameForm:No info for frame ['.$fid.']. ['.($fid > 0).']';
            }
        }
    } else {
        $nickname = 'My Frame';
        $frameid = 'Unknown';
        $prodid = '';
        $shuffle = 'N';
    }

    $html = '<div class="body_title">Edit Frame details</div>';
    $html .= '<div class="body_textarea">';
    $html .= '<form id="frame" onsubmit="validateForm();" name="frame" method="post" action="#">';
    $html .= '<table border="0">';
    $html .= '<tr><td rowspan="5"><img src="/images/frame.png" width="128"/></td><td>Nickname:</td>';
    $html .= '<td><input type="text" maxlength="32" size="32" name="nickname" id="nickname" value="'.$nickname.'" onblur="validNickname()"></td><td><div><img id="nicknamemsg" height="24" src="/images/knobs/Grey.png"/></div></td></tr>';
    $html .= '<tr><td>Frame ID:</td>';
    $html .= '<td><input type="text" maxlength="64" size="32" name="frameid" id="frameid" value="'. $frameid . '"</td></tr>';
    $html .= '<tr><td>Model:</td><td><SELECT NAME = "prodid" STYLE = "Width: 50">' . optionProdID($prodid) . '</select></td></tr>';
    $html .= '<tr><td>Shuffle Images:</td><td>' . optionActiveStatus($shuffle, 'shuffle_images') . '</td></tr>';
    $html .= '<tr><td>Delete frame</td>';
    $html .= '<td><input type="checkbox" name="del_frame" id="del_frame" value="delframe" onclick="setDelIcon();"></td><td><div><img id="del_frame_msg" height="24" src="/images/blank.png"/></div></tr>';
    $html .= '</table>';
    $html .= '<div align="center"><input type="submit" value=" Submit " name="submit" /></div>';
    $html .= '</form>';
    $html .= '</div>';

    return array ($msg, $html);
}

?>
