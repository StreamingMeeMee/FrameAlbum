<?php
#-------------------------------------------
# 2011-jun-28 - TimC
#   Basic channel helper functions
#
# 2011-aug-17 - TimC
#   Add verifyFlickrUser()
#
# 2011-sept-24 TimC
#   - Add support for chantype 10 (weather radar)
#
# 2011-oct-1 - TimC
#   - Activate 'tags' in flickr channel
#
# 2012-may-27 - TimC
#   - Update for new chan. type IDs
#
# 2012-aug-4 - TimC
#   - update channelUserForm(), channelUserInfoHTML() with correct channel_type_ids (Bug#68) 
#
# 2012-aug-6 - TimC
#   - fix missing quote mark to complete support for Flickr tags in user channels
#
# 2012-aug-7 - TimC
#   - Add 'edit' and 'delete' actions with icons to channel info page
#   - style channeltype icons & sample images
#   - add a cancel button when deleting a channel
#
# 2012-aug-29 - TimC
#   - add max age fields to Flickr and Picasa channel forms
#-------------------------------------------
require_once("phpFlickr/phpFlickr.php");

#------------------------------
function verifyFlickrUser($usr)
#------------------------------
{
    unset($person);
    $msg = '';

    $f = new phpFlickr( $GLOBALS['flickr_api_key'] );
#    $f->enableCache(
#        "db",
#        "mysql://$db_user:$db_pass@$db_host/$db"
#    );
 
    if (!empty($usr)) {
        // Find the NSID of the username inputted via the form
        $person = $f->people_findByUsername($usr);
        $msg = 'Found Flickr person:['.$person['id'].']';
    } else {
        $msg = 'Flickr username not entered.';
    }

    return array ('msg2', (isset($person)) );
}

#------------------------------
function mkTextPanelItem($cid, $bcolor, $fcolor, $size, $text, $hres, $vres)
#------------------------------
{
    if (!(isset($text))) { $text = 'Not_Supplied'; }

    $fn = $GLOBALS['image_path'] . '/ch' . $cid.'-info.jpg';
    $url = $GLOBALS['image_url_root'] . '/ch' . $cid . '-info.jpg';
    $guid = 'ch' . $cid . '-info.jpg';

#    if ( !(file_exists($fn)) ) {
        $fontName = 'Helvetica';
        $fontColor = $fcolor;
        $fontSize = $size;

        # make a transparent pallete
        $pallete = new Imagick;
        $pallete->newimage($hres, $vres, $bcolor);
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
#    }

    $sql = "INSERT INTO items (title, link, category, user_channel_id, description, pubDate, guid, media_content_url, media_thumbnail_url, media_content_duration) VALUES ('Text Panel','$url','Text Panel',$cid,'".mysql_real_escape_string($text)."',now(),'$guid','$url','$url',15)";

    $result = mysql_query($sql);
    if (!$result) {
        die("[$sql]: Invalid query: " . mysql_error());
    }

    $ret = mysql_insert_id();

    return ret;
}

#----------------------------
function channelUserUpdate($id,$nick,$attrib,$acv,$status)
#----------------------------
# Updates a user channel with the given attributes.
# Note: This function DOES NOT check for existing entries before attempting UPDATE.
#
#============================
{
    $ret = 0;
    $msg = '';
    $html = '';

    list ($ret, $msg) = channelUserUpdate2($id, $nick, $attrib, $acv, $status);

    return array ($msg, $html);
}

#----------------------------
function channelSample($id)
#----------------------------
# display a 4 x 4 grid of sample images from this channel
#----------------------------
{
    $msg = '';
    $html = '';

    if (!isset($id))  { $id = 0; }
    $id = prepDBVal($id);

    if ( $GLOBALS['enable_chan_samples'] ) {
        $sql = "SELECT * FROM items WHERE user_channel_id='$id' ORDER BY pubDate ASC LIMIT 16";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
        }

        if (mysql_num_rows( $result ) > 0) {
            $html .= '<div class="body_title">Sample images from this channel</div>';
            $html .= '<div class="body_textarea">';
            $html .= '<table border="0"><tr>';
            $rc = 1;
            while( $row = mysql_fetch_assoc( $result ) ) {
                $html .= '<td align="center"><a href="'.$row['media_content_url'].'" target="_blank"><img style="margin: 5px;" class="sampleImage" title="'.$row['title'].'" alt="'.$row['title'].'" src="'.$row['media_thumbnail_url'].'"/></a></td>';
                if ($rc > 3) {
                    $rc = 1;
                    $html .= '</tr><tr>';
                } else {
                    $rc++;
                }
            }
            $html .= '</tr></table>';
            $html .= '</div>';
        } else {
            $html = '<div class="body_textarea">No samples available for this channel.</div>';
        }

    } else {
        $html = 'Channel sample feature not enabled.';
    }

    return array ($msg, $html);
}
#----------------------------
function channelUserUpdate2($id,$nick,$attrib,$acv,$status)
#----------------------------
# Updates a user channel with the given attributes.
# Note: This function DOES NOT check for existing entries before attempting UPDATE.
#
#============================
{
    if (!isset($nick))  { $nick = ''; }
    if (!isset($id))   { $id = 0; }
    if (!isset($attrib))   { $attrib = ''; }
    if (!isset($status))   { $status = ''; }
    if (!isset($acv))   { $acv = 'N'; }                             # If not specified frame is NOT active

    if (strlen($nick) == 0) { $nick = 'Nickname'; }                 # default framename

    if ( $id != 0 ) {                    # nothing to update
        $id = prepDBVal($id);
        $nick = prepDBVal($nick);
        $attrib = prepDBVal($attrib);
        $acv = prepDBVal($acv);
        $status = prepDBVal($status);

        $d = explode('|', $attrib);
        $msg2 = $d[0];
        list ($msg2, $valid) = verifyFlickrUser($d[0]);

        $sql = "UPDATE user_channels SET  chan_nickname='$nick', active='$acv', attrib='$attrib', status='$status' WHERE iduserchannels=$id LIMIT 1";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
        }

        if ($result) {              # insert was OK
            $ret = true;
            $msg = 'Channel updated.';
        } else {                    # not so much
            $ret = 'false';
            $msg = 'Channel update failed. [sqlupdatefail]';
        }
    } else {
        $msg = 'Channel update failed. [nochanid]';
        $ret = false ;
    }

    if (!empty($msg2)) { $msg .= '<br>' . $msg2; }
    $msg .= '<br>' . $msg2;
    return array ($ret, $msg );
}

#----------------------------
function channelUserDel($cid)
#----------------------------
# Removes the specified user channel and all associated data.
# Note: This function DOES NOT check for existing entries before attempting DELETE.
#============================
{
    if ( $cid != 0 ) {                    # nothing to update
        $cid = prepDBVal($cid);

        $sql = "DELETE FROM frame_channels WHERE user_channel_id=$cid";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Error deleting user_channels|frame_channels: " . mysql_error());
        }

        $sql = "DELETE FROM frame_items WHERE user_channel_id=$cid";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Error deleting user_channels|frame_items: " . mysql_error());
        }

        $sql = "DELETE FROM items WHERE user_channel_id=$cid";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Error deleting user_channels|items: " . mysql_error());
        }

        $sql = "DELETE FROM user_channels WHERE iduserchannels=$cid LIMIT 1";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Error deleting user_channels: " . mysql_error());
        }
    }

    return;
}

#----------------------------
function channelUserAdd($uid, $ctid, $nick, $attrib, $acv, $status)
#----------------------------
# Adds a new user channel with the given attributes. 
# Note: This function DOES NOT check for existing entries before attempting INSERT.
#
# Returns: iduserchannels of new user_channels entry; 0 on error.
#============================
{
    if (!isset($nick))  { $nick = ''; }
    if (!isset($ctid))   { $ctid = 0; }
    if (!isset($attrib)) { $attrib = ''; }
    if (!isset($uid))   { $uid = 'NULL'; }
    if (!isset($acv)) { $acv = 'N'; }
    if (!isset($status)) { $status = ''; }

    if (strlen($nick) == 0) { $nick = 'My Frame'; }                 # default framename

    if ( $uid != 0 ) {                    # nothing to add

        $ctid = prepDBVal($ctid);
        $uid = prepDBVal($uid);
        $nick = prepDBVal($nick);
        $attrib = prepDBVal($attrib);
        $acv = prepDBVal($acv);
        $status = prepDBVal($status);

        $sql = "INSERT INTO user_channels (user_id, channel_type_id, chan_nickname, active, attrib, status) VALUES ('$uid', '$ctid', '$nick', '$acv', '$attrib', '$status')";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
        }

        if ($result) {              # insert was OK
            $ret = mysql_insert_id();
            if ($ret > 0) {
                $itm = mkTextPanelItem($ret, 'blue', 'white', 35, $nick . "\nwill be updated shortly.\n\nPlease stand by.", 800, 480);
            }
        } else {                    # not so much
            $ret = 0;
        }
    } else {
        $ret = 0;
    }

    return array ($ret, $itm);
}

#----------------------------
function channelUserForm($cid, $ctid, $fid, $action)
#----------------------------
# Returns a HTML form to add/update a user channel
#
#============================
{
    $msg = '';
    $html = '';

    if ($ctid == 0) {
        $sql = "SELECT * FROM user_channels  WHERE iduserchannels=$cid";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg = '#FAIL - An error occured in channelUserForm.';
        } else {
            $row = mysql_fetch_assoc( $result );
            $ctid = $row['channel_type_id'];
        }
    }

    if ($ctid == 1) {
        list ($msg, $html) =  channelUserFormFlickr($cid, $ctid, $fid, $action);
    } else if ($ctid == 3) {
        list ($msg, $html) =  channelUserFormText($cid, $ctid, $fid, $action);
    } else if ($ctid == 8) {
        list ($msg, $html) =  channelUserFormPicasa($cid, $ctid, $fid, $action);
    } else if ($ctid == 10) {
        list ($msg, $html) =  channelUserFormRadar($cid, $ctid, $fidi, $action);
    } else {
        $msg = 'Unsupported channel type:['.$ctid.']';
        $html = '';
    }

    return array ($msg, $html);
}

#----------------------------
function channelUserFormText($cid, $ctid, $fid, $action)
#----------------------------
# Returns a HTML form to add/update a generic text panel channel
#
#============================
{
    $msg = '';
    $html = '';

    if (!isset($cid))   { $cid =0; }

    if ( $action == 'delete' ) {
        $delcb = ' checked="yes" ';
        $msg .= 'Are you sure you want to delete this channel?';
    }

    if ($cid > 0) {                     # don't edit channels with no ID
        $cid = prepDBVal($cid);

        $sql = "SELECT * FROM user_channels AS uc, channel_types AS ct WHERE iduserchannels = '$cid' AND uc.channel_type_id=ct.idchanneltypes";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg = '#FAIL - An error occured.';
        } else {
            if (mysql_num_rows( $result ) == 1) {
                $row = mysql_fetch_assoc( $result );
                $nickname = $row['chan_nickname'];
                $attrib = $row['attrib'];
                $attribs = preg_split("/\|/", $row['attrib']);
                $chan_icon_url = $row['channel_icon_url'];
            } else {
                $msg = 'No info for channel ['.$cid.'].';
            }
        }
    } else {
        $nickname = 'My Text Panel';
        $attribs[0] = 'green';
        $attribs[1] = '#efefef';
        $attribs[2] = '36';
        $attribs[3] = 'Text Goes Here';
        $attrib = $attribs[0] . '|' . $attribs[1] . '|' . $attribs[1];
        $chan_icon_url = '';
        $sql = "SELECT * FROM channel_types WHERE idchanneltypes = '$ctid'";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg = '#FAIL - An error occured retrieving channel type icon.';
        } else {
            if (mysql_num_rows( $result ) == 1) {
                $row = mysql_fetch_assoc( $result );
                $chan_icon_url =  $row['channel_icon_url'];
            }
        }
    }
    $html = '<div class="body_title">Edit Channel details</div>';
    $html .= '<div class="body_textarea">';
    $html .= '<form id="userchannel" onsubmit="validateNickname();" name="userchannel" method="post" action="#">';
    if ($fid > 0) { $html .= '<input type="hidden" id="fid" name="fid" value="' . $fid . '">'; }           # remember if a frame was specified
    $html .= '<input type="hidden" id="attrib" name="attrib" value="' . $attrib . '">';
    $html .= '<input type="hidden" id="chantype" name="chantype" value='. $ctid . '">';
    $html .= '<table border="0">';
    $html .= '<tr><td rowspan="6"><img src="' . $chan_icon_url . '" class="channelTypeIcnLrg"/></td><td>Nickname:</td>';
    $html .= '<td><input type="text" maxlength="32" size="32" name="nickname" id="nickname" value="'.$nickname.'" onblur="validNickname()" onchange="constructAttrib()"></td><td><div><img id="nicknamemsg" height="24" src="/images/knobs/Grey.png"/></div></td></tr>';
    $html .= '<tr><td>Background Color:</td>';
    $html .= '<td><input type="text" maxlength="64" size="32" name="attrib0" id="attrib0" value="'. $attribs[0] . '" onchange="constructAttrib()"></td><td><div><img id="attrib0_msg" height="24" src="/images/blank.png"/></div></td></tr>';
    $html .= '<tr><td>Font Color:</td>';
    $html .= '<td><input type="text" maxlength="64" size="32" name="attrib1" id="attrib1" value="'. $attribs[1] . '" onchange="constructAttrib()"></td><td><div><img id="attrib1_msg" height="24" src="/images/blank.png"/></div></td></tr>';
    $html .= '<tr><td>Font Size:</td>';
    $html .= '<td><input type="text" maxlength="64" size="32" name="attrib2" id="attrib2" value="'. $attribs[2] . '" onchange="constructAttrib()"></td><td><div><img id="attrib1_msg" height="24" src="/images/blank.png"/></div></td></tr>';
    $html .= '<tr><td>Panel Text:</td>';
    $html .= '<td><input type="text" maxlength="64" size="32" name="attrib3" id="attrib3" value="'. $attribs[3] . '" onchange="constructAttrib()"></td><td><div><img id="attrib1_msg" height="24" src="/images/blank.png"/></div></td></tr>';
    if ($cid != 0) {
        $html .= '<tr><td>Delete channel</td>';
        $html .= '<td><input type="checkbox" name="del_chan" id="del_chan" value="delchan" onclick="setDelIcon();"' . $delcb . '></td><td><div><img id="del_chan_msg" height="24" src="/images/blank.png"/></div></tr>';
    }
    $html .= '</table>';

    $html .= '<div align="center">';
    $html .= '<input type="submit" value=" Submit " name="submit" />';
    if( $action == 'delete' ) { $html .= '&nbsp;<a href="/usermain.php"><input type="button" name="cancel" value=" Cancel " /></a>'; }
    $html .= '</div>';

    $html .= '</form>';
    $html .= '</div>';

    return array ($msg, $html);
}

#----------------------------
function channelUserFormRadar($cid, $ctid, $fid, $action)
#----------------------------
# Returns a HTML form to add/update a weather radar channel
#
#============================
{
    $msg = '';
    $html = '';

    if (!isset($cid))   { $cid =0; }

    if ( $action == 'delete' ) {
        $delcb = ' checked="yes" ';
        $msg .= 'Are you sure you want to delete this channel?';
    }

    if ($cid > 0) {                     # don't edit channels with no ID
        $cid = prepDBVal($cid);

        $sql = "SELECT * FROM user_channels AS uc, channel_types AS ct WHERE iduserchannels = '$cid' AND uc.channel_type_id=ct.idchanneltypes";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg = '#FAIL - An error occured.';
        } else {
            if (mysql_num_rows( $result ) == 1) {
                $row = mysql_fetch_assoc( $result );
                $nickname = $row['chan_nickname'];
                $attrib = $row['attrib'];
                $attribs = preg_split("/\|/", $row['attrib']);
                $chan_icon_url = $row['channel_icon_url'];
            } else {
                $msg = 'No info for channel ['.$cid.'].';
            }
        }
    } else {
        $nickname = 'My Weather Radar';
        $attribs[0] = $_SESSION['userzip'];
        $attribs[1] = '';
        $attrib = $attribs[0] . '|' . $attribs[1];
        $chan_icon_url = '';
        $sql = "SELECT * FROM channel_types WHERE idchanneltypes = '$ctid'";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg = '#FAIL - An error occured retrieving channel type icon.';
        } else {
            if (mysql_num_rows( $result ) == 1) {
                $row = mysql_fetch_assoc( $result );
                $chan_icon_url =  $row['channel_icon_url'];
            }
        }
    }

    $html = '<div class="body_title">Edit Channel details</div>';
    $html .= '<div class="body_textarea">';
    $html .= '<form id="userchannel" name="userchannel" method="post" action="#">';
    if ($fid > 0) { $html .= '<input type="hidden" id="fid" name="fid" value="' . $fid . '">'; }           # remember if a frame was specified
    $html .= '<input type="hidden" id="attrib" name="attrib" value="' . $attrib . '">';
    $html .= '<input type="hidden" id="chantype" name="chantype" value='. $ctid . '">';
    $html .= '<table border="0">';
    $html .= '<tr><td rowspan="4"><img src="' . $chan_icon_url . '" class="channelTypeIconLrg"/></td><td>Nickname:</td>';
    $html .= '<td><input type="text" maxlength="32" size="32" name="nickname" id="nickname" value="'.$nickname.'" onblur="validNickname()" onchange="constructAttrib1()"></td><td><div><img id="nicknamemsg" height="24" src="/images/knobs/Grey.png"/></div></td></tr>';
    $html .= '<tr><td>US ZIP code:</td>';
    $html .= '<td><input type="text" maxlength="64" size="32" name="attrib0" id="attrib0" value="'. $attribs[0] . '" onblur="validZIPCode()" onchange="constructAttrib1()"></td><td><div><img id="attrib0_msg" height="24" src="/images/knobs/Grey.png"/></div></td></tr>';
#    $html .= '<td><input style="background-color : #d9d9d9;" disabled type="text" maxlength="64" size="32" name="attrib1" id="attrib1" value="'. $attribs[1] . '"</td></tr>';
    if ($cid != 0) {
        $html .= '<tr><td>Delete channel</td>';
        $html .= '<td><input type="checkbox" name="del_chan" id="del_chan" value="delchan" onclick="setDelIcon();"' . $delcb . '></td><td><div><img id="del_chan_msg" height="24" src="/images/blank.png"/></div></tr>';

    }
    $html .= '</table>';

    $html .= '<div align="center">';
    $html .= '<input type="submit" value=" Submit " name="submit" />';
    if( $action == 'delete' ) { $html .= '&nbsp;<a href="/usermain.php"><input type="button" name="cancel" value=" Cancel " /></a>'; }

    $html .= '</form>';
    $html .= '</div>';

    return array ($msg, $html);
}

#----------------------------
function channelUserFormPicasa($cid, $ctid, $fid, $action)
#----------------------------
# Returns a HTML form to add/update a PicasaWeb channel
#
#============================
{
    $msg = '';
    $html = '';

    if (!isset($cid))   { $cid =0; }

    if ( $action == 'delete' ) {
        $delcb = ' checked="yes" ';
        $msg .= 'Are you sure you want to delete this channel?';
    }

    if ($cid > 0) {                     # don't edit channels with no ID
        $cid = prepDBVal($cid);

        $sql = "SELECT * FROM user_channels AS uc, channel_types AS ct WHERE iduserchannels = '$cid' AND uc.channel_type_id=ct.idchanneltypes";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg = '#FAIL - An error occured.';
        } else {
            if (mysql_num_rows( $result ) == 1) {
                $row = mysql_fetch_assoc( $result );
                $nickname = $row['chan_nickname'];
                $attrib = $row['attrib'];
                $attribs = preg_split("/\|/", $row['attrib']);
                $chan_icon_url = $row['channel_icon_url'];
            } else {
                $msg = 'No info for channel ['.$cid.'].';
            }
        }
    } else {
        $nickname = 'My PicasaWeb Public Folder';
        $attribs[0] = $_SESSION['useremail'];
        $attribs[1] = '';
        $attrib = $attribs[0] . '|' . $attribs[1];
        $chan_icon_url = '';
        $sql = "SELECT * FROM channel_types WHERE idchanneltypes = '$ctid'";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg = '#FAIL - An error occured retrieving channel type icon.';
        } else {
            if (mysql_num_rows( $result ) == 1) {
                $row = mysql_fetch_assoc( $result );
                $chan_icon_url =  $row['channel_icon_url'];
            }
        }
    }

    if( !isset( $attribs[2] ) ) { $attribs[2] = 5 * 365; }          # max age of 5 years is default

    $html = '<div class="body_title">Edit Channel details</div>';
    $html .= '<div class="body_textarea">';
    $html .= '<form id="userchannel" name="userchannel" method="post" action="#">';
    if ($fid > 0) { $html .= '<input type="hidden" id="fid" name="fid" value="' . $fid . '">'; }           # remember if a frame was specified
    $html .= '<input type="hidden" id="attrib" name="attrib" value="' . $attrib . '">';
    $html .= '<input type="hidden" id="chantype" name="chantype" value='. $ctid . '">';
    $html .= '<table border="0">';
    $html .= '<tr><td rowspan="5"><img src="' . $chan_icon_url . '" class="channelTypeIconLrg"/></td><td>Nickname:</td>';
    $html .= '<td><input type="text" maxlength="32" size="32" name="nickname" id="nickname" value="'.$nickname.'" onblur="validNickname()" onchange="constructAttrib2()"></td><td><div><img id="nicknamemsg" height="24" src="/images/knobs/Grey.png"/></div></td></tr>';
    $html .= '<tr><td>PicasaWeb User:</td>';
    $html .= '<td><input type="text" maxlength="64" size="32" name="attrib0" id="attrib0" value="'. $attribs[0] . '" onblur="validEmailAddr(this)" onchange="constructAttrib2()"></td><td><div><img id="attrib0_msg" height="24" src="/images/knobs/Grey.png"/></div></td></tr>';
    $html .= '<tr><td>Tags:</td>';
    $html .= '<td><input type="text" maxlength="64" size="32" name="attrib1" id="attrib1" value="'. $attribs[1] . '"</td></tr>';
    $html .= '<tr><td>Max Age:</td>';
    $html .= '<td>';
    $html .= '<input type="text" maxlength="3" size="2" name="maxage_yr" id="maxage_yr" value="'. intval( $attribs[2] / 365 ) . '" onblur="validMaxAge()" onchange="constructAttrib2()"> years&nbsp;';
    $html .= '<input type="text" maxlength="4" size="3" name="maxage_dy" id="maxage_dy" value="'. ( $attribs[2] % 365 ) . '" onblur="validMaxAge()" onchange="constructAttrib2()"> days';
    $html .= '<td><div><img id="attrib2_msg" height="24" src="/images/blank.png"/></div>';
    $html .= '</td></tr>';

    if ($cid != 0) {
        $html .= '<tr><td>Delete channel</td>';
        $html .= '<td><input type="checkbox" name="del_chan" id="del_chan" value="delchan" onclick="setDelIcon();"' . $delcb . '></td><td><div><img id="del_chan_msg" height="24" src="/images/blank.png"/></div></tr>';
    }
    $html .= '</table>';

    $html .= '<div align="center">';
    $html .= '<input type="submit" value=" Submit " name="submit" />';
    if( $action == 'delete' ) { $html .= '&nbsp;<a href="/usermain.php"><input type="button" name="cancel" value=" Cancel " /></a>'; }
    $html .= '</div>';

    $html .= '</form>';
    $html .= '</div>';

    return array ($msg, $html);
}

#----------------------------
function channelUserFormFlickr($cid, $ctid, $fid, $action)
#----------------------------
# Returns a HTML form to add/update a Flickr channel
#
#============================
{
    $msg = '';
    $html = '';
    $delcb = '';

    if (!isset($cid))   { $cid =0; }

    if ( $action == 'delete' ) {
        $delcb = ' checked="yes" ';
        $msg .= 'Are you sure you want to delete this channel?';
    }

    if ($cid > 0) {                     # don't edit channels with no ID
        $cid = prepDBVal($cid);

        $sql = "SELECT * FROM user_channels AS uc, channel_types AS ct WHERE iduserchannels = '$cid' AND uc.channel_type_id=ct.idchanneltypes";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg = '#FAIL - An error occured.';
        } else {
            if (mysql_num_rows( $result ) == 1) {
                $row = mysql_fetch_assoc( $result );
                $nickname = $row['chan_nickname'];
                $attrib = $row['attrib'];
                $attribs = preg_split("/\|/", $row['attrib']);
                $chan_icon_url = $row['channel_icon_url'];
            } else {
                $msg = 'No info for channel ['.$cid.'].';
            }
        }
    } else {
        $nickname = 'My Photostream';
        $attribs[0] = $_SESSION['useremail'];
        $attribs[1] = '';
        $attrib = $attribs[0] . '|' . $attribs[1]; 
        $chan_icon_url = '';
        $sql = "SELECT * FROM channel_types WHERE idchanneltypes = '$ctid'";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg = '#FAIL - An error occured retrieving channel type icon.';
        } else {
            if (mysql_num_rows( $result ) == 1) {
                $row = mysql_fetch_assoc( $result );
                $chan_icon_url =  $row['channel_icon_url'];
            }
        }
    }

    if( !isset( $attribs[2] ) ) { $attribs[2] = 5 * 365; }          # max age of 5 years is default

    $html = '<div class="body_title">Edit Channel details</div>';
    $html .= '<div class="body_textarea">';
    $html .= '<form id="userchannel" onsubmit="validateForm();" name="userchannel" method="post" action="#">';
    if ($fid > 0) { $html .= '<input type="hidden" id="fid" name="fid" value="' . $fid . '">'; }           # remember if a frame was specified
    $html .= '<input type="hidden" id="attrib" name="attrib" value="' . $attrib . '">';
    $html .= '<input type="hidden" id="chantype" name="chantype" value='. $ctid . '">';
    $html .= '<table border="0">';
    $html .= '<tr><td rowspan="5"><img src="' . $chan_icon_url . '" class="channelTypeIconLrg"/></td><td>Nickname:</td>';
    $html .= '<td><input type="text" maxlength="32" size="32" name="nickname" id="nickname" value="'.$nickname.'" onblur="validNickname()" onchange="constructAttribFlickr()"></td><td><div><img id="nicknamemsg" height="24" src="/images/knobs/Grey.png"/></div></td></tr>';
    $html .= '<tr><td>Flickr Screename:</td>';
#    $html .= '<td><input type="text" maxlength="64" size="32" name="reg_email" id="reg_email" value="'. $attribs[0] . '" onblur="validEmail()" onchange="constructAttribFlickr()"></td><td><div><img id="emailmsg" height="24" src="/images/knobs/Grey.png"/></div></td></tr>';

    $html .= '<td><input type="text" maxlength="64" size="32" name="flickr_user" id="flickr_user" value="'. $attribs[0] . '" onblur="validFlickrUser()" onchange="constructAttribFlickr()"></td><td><div><img id="flickr_user_msg" height="24" src="/images/knobs/Grey.png"/></div></td></tr>';
    $html .= '<tr><td>Tags:</td>';
    $html .= '<td><input type="text" maxlength="64" size="32" name="f_tags" id="f_tags" value="'. $attribs[1] . '" onchange="constructAttribFlickr()"></td></tr>';
    $html .= '<tr><td>Max Age:</td>';
    $html .= '<td>';
	$html .= '<input type="text" maxlength="3" size="2" name="maxage_yr" id="maxage_yr" value="'. intval( $attribs[2] / 365 ) . '" onblur="validMaxAge()" onchange="constructAttribFlickr()"> years&nbsp;';
    $html .= '<input type="text" maxlength="4" size="3" name="maxage_dy" id="maxage_dy" value="'. ( $attribs[2] % 365 ) . '" onblur="validMaxAge()" onchange="constructAttribFlickr()"> days';
    $html .= '<td><div><img id="attrib2_msg" height="24" src="/images/blank.png"/></div>';
	$html .= '</td></tr>';

    if ($cid != 0) {
        $html .= '<tr><td>Delete channel</td>';
        $html .= '<td><input type="checkbox" name="del_chan" id="del_chan" value="delchan" onclick="setDelIcon();"' . $delcb . '></td><td><div><img id="del_chan_msg" height="24" src="/images/blank.png"/></div></td></tr>';
    }
    $html .= '</table>';

    $html .= '<div align="center">';
    $html .= '<input type="submit" value=" Submit " name="submit" />';
    if( $action == 'delete' ) { $html .= '&nbsp;<a href="/usermain.php"><input type="button" name="cancel" value=" Cancel " /></a>'; }
    $html .= '</div>';

    $html .= '</form>';
    $html .= '</div>';

    return array ($msg, $html);
}

#----------------------------
function channelUserAvailFrame($uid, $fid)
#----------------------------
{
$h = '';
$m = '';
$item_cnt = 0;

    if (!isset($uid))   { $uid =0; }
    if (!isset($fid))   { $fid =0; }

    if ( ($uid > 0) and ($fid > 0) ){                     # don't add frames with no ID
        $uid = prepDBVal($uid);
        $fid = prepDBVal($fid);

        $sql = "SELECT * FROM `user_channels` AS uc, channel_types AS ct  WHERE user_id=$uid AND ct.idchanneltypes=uc.channel_type_id AND NOT EXISTS (SELECT user_channel_id FROM frame_channels AS fc WHERE frame_id=$fid AND fc.user_channel_id=uc.iduserchannels)";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $m = '#FAIL - An error occured.';
        } else {
            $h .= '<table border="0"><tr>';
            if (mysql_num_rows( $result ) > 0) {
                while( $row = mysql_fetch_assoc( $result ) ) {
                    if ($item_cnt > $GLOBALS['row_item_limit']) { $h .= "</tr>\n<tr>";  $item_cnt = 0; }
                    $h .= '<td align="center"><a href="channel.php?cid='.$row['iduserchannels'].'" title="View channel details" alt="View channel details"><img src="'.$row['channel_icon_url'].'" class="channelTypeIconLrg" title="View channel details" alt="View channel details"/><br/>' . $row['chan_nickname'] . '</a>&nbsp;<a href="/frame.php?fid='.$fid.'&action=adch&cid='.$row['iduserchannels'].'"><img height="18" src="/images/knobs/Add.png" title="Add this channel to this frame" alt="Add this channel to this frame"/></a></td>';
                    $item_cnt++;
                }
            }
            if ($item_cnt > $GLOBALS['row_item_limit']) { $h .= "</tr>\n<tr>";  $item_cnt = 0; }
            $h .= '<td align="center"><a href="channel.php?fid='.$fid.'" title="Add a new channel"><img src="/images/add_channel.png" class="channelTypeIconLrg" title="Add a new channel" alt="Add a channel"/><br/>Add a new channel</a></td>';
            $h .= '</tr></table>';
        }
    } else {
        $h = "&nbsp;uid:[$uid]  fid:[$fid]";
    }

    return array ($m, $h);
}

#----------------------------
function channelTypeEnumHTML($fid, $showtest)
#----------------------------
# Returns a HTML formated list of a channel types
#
#============================
{
$item_cnt = 0;
$msg = '';

    $ret = '<div class="body_title">Choose a channel type</div>';

    if ($fid > 0) { $fl = "&fid=$fid"; } else { $fl = ''; }             # remember if a frame was specified

    if ($showtest == 1) {
        $sql = "SELECT * FROM channel_types AS ct WHERE ct.active='Y' OR ct.active='T'";
    } else {
        $sql = "SELECT * FROM channel_types AS ct WHERE ct.active='Y'";
    }

    $result = mysql_query($sql);
    if (!$result) {
        die("[$sql]: Invalid query: " . mysql_error());
        $msg = '#FAIL - An error occured.';
    } else {
        if (mysql_num_rows( $result ) > 0) {
            $ret .= '<table border="0"><tr>';
            while( $row = mysql_fetch_assoc( $result ) ) {
                if ($item_cnt > $GLOBALS['row_item_limit']) { $ret .= "</tr>\n<tr>";  $item_cnt = 0; }
                $ret .= '<td align="center"><a href="channel.php?ctid='.$row['idchanneltypes'].$fl.'"><img src="'.$row['channel_icon_url'].'" class="channelTypeIconLrg" /><br/>' . $row['channel_name'] . '</a></td>';
                $item_cnt++;
                }
            $ret .= '</tr></table>';
        } else {
            $msg = 'No active channel types.';
            $ret = '<p>&nbsp;</p>';
        }
    }

    return array ($msg, $ret);
}

#----------------------------
function channelUserEnumHTML($uid)
#----------------------------
# Returns a HTML formated list of a user's channels 
#
#============================
{
$ret = '';
$item_cnt = 0;

    if (!isset($uid))   { $uid =0; }

    if ($uid > 0) {                     # don't add frames with no ID
        $uid = prepDBVal($uid);

        $sql = "SELECT * FROM user_channels AS uc, channel_types AS ch WHERE uc.user_id = '$uid' AND uc.channel_type_id=ch.idchanneltypes";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $ret = '<p>#FAIL - An error occured.</p>';
        } else {
            $ret .= '<table border="0"><tr>'."\n";

            if (mysql_num_rows( $result ) > 0) {
                while( $row = mysql_fetch_assoc( $result ) ) {
                    if ($item_cnt > $GLOBALS['row_item_limit']) { $ret .= "</tr>\n<tr>";  $item_cnt = 0; }
                    $ret .= '<td align="center"><a href="channel.php?cid='.$row['iduserchannels'].'"><img src="'.$row['channel_icon_url'].'" class="channelTypeIconLrg" title="View channel details" alt="View channel details"/><br/>' . $row['chan_nickname'] . "</a></td>\n";
                    $item_cnt++;
                }
            }

            if ($item_cnt > $GLOBALS['row_item_limit']) { $ret .= '</tr><tr>';  $item_cnt = 0; }
#            $ret .= '<td align="center"><a href="channel.php"><img src="/images/add_channel.png" class="channelTypeIconMed" title="Add a channel" alt="Add a new channel"/><br/>Add a new channel</a></td>';
            $ret .= '<td align="center"><a href="channel.php"><img src="/images/add_channel.png" class="channelTypeIconLrg" title="View channel details" alt="View channel details"/><br/>Add a new channel</a></td>';
            $ret .= "\n</tr></table>\n";

        }
    } else {
        $ret = '<p>&nbsp;</p>';
    }

    return $ret;
}

#----------------------------
function channelUserInfoHTML($cid)
#----------------------------
# Returns a HTML formated detail of a single user's channel
#
#============================
{
    $sql = "SELECT * FROM user_channels  WHERE iduserchannels=$cid";
    $result = mysql_query($sql);
    if (!$result) {
        die("[$sql]: Invalid query: " . mysql_error());
        $msg = '#FAIL - An error occured in channelUserInfoHTML.';
    } else {
        $row = mysql_fetch_assoc( $result );
        $ctid = $row['channel_type_id'];
    }

    if ($ctid == 1) {
        list ($msg, $html) = channelUserInfoFlickrHTML($cid);
    } else if ($ctid == 3) {
        list ($msg, $html) = channelUserInfoTextHTML($cid);
    } else if ($ctid == 8) {
        list ($msg, $html) = channelUserInfoPicasaHTML($cid);
    } else if ($ctid == 10) {
        list ($msg, $html) = channelUserInfoRadarHTML($cid);
    } else {
        $msg = 'Unsupported channel type:['.$ctid.']';
        $html = '';
    }

    if ( $GLOBALS['enable_chan_samples'] ) {
        list ($m, $h) = channelSample($cid);
        $html .= $h;
        $msg .= $m;
    }

    return array ($msg, $html);
}

#----------------------------
function channelUserInfoTextHTML($cid)
#----------------------------
# Returns a HTML formated detail of a single Text panel users channel
#
#============================
{
    $msg = '';

    $ret = '<div class="body_title">Channel details ';
    $ret .= '<a href="/channel.php?cid='.$cid.'&action=edit"><img src="/images/edit.png" alt="edit channel" title="Edit Channel" class="actionIcon"></a>';
    $ret .= '&nbsp;<a href="/channel.php?cid='.$cid.'&action=delete"><img src="/images/delete.png" alt="delete channel" title="Delete Channel" class="actionIcon"></a>';
    $ret .= '</div>';

    $ret .= '<div class="body_textarea">';
    if (!isset($cid))   { $cid =0; }

    if ($cid > 0) {                     # don't display channels with no ID
        $cid = prepDBVal($cid);

        $sql = "SELECT * FROM user_channels AS uc, channel_types AS ch WHERE uc.iduserchannels = '$cid' AND ch.idchanneltypes=uc.channel_type_id";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg = '#FAIL - An error occured.';
        } else {
            if (mysql_num_rows( $result ) == 1) {
                $row = mysql_fetch_assoc( $result );
                $attribs = preg_split("/\|/", $row['attrib']);
                if (strlen($attribs[1]) == 0) { $attribs[1] = '-'; }

                $ret .= '<table border="0">';
                $ret .= '<tr><td rowspan="6"><img src="' . $row['channel_icon_url']. '" class="channelTypeIconLrg"/></td><td>Nickname:</td><td>' . $row['chan_nickname'] . '</td></tr>';
                $ret .= '<tr><td>Background Color:</td><td>' . $attribs[0] . '</td></tr>';
                $ret .= '<tr><td>Font Color:</td><td>' . $attribs[1] . '</td></tr>';
                $ret .= '<tr><td>Font Size:</td><td>' . $attribs[2] . '</td></tr>';
                $ret .= '<tr><td>Text:</td><td>' . $attribs[3] . '</td></tr>';
                $ret .= '<tr><td>Last updated:</td><td>' . $row['last_updated'] . ' GMT</td></tr>';
                $ret .= '</table>';
                $msg = $row['status'];
            } else {
                $msg = 'No info for channel ['. $cid.'].';
            }
        }
    } else {
        $ret .= 'No info for channel ['.$cid.'].';
    }

    $ret .= '</div>';
    return array ($msg, $ret);
}

#----------------------------
function channelUserInfoPicasaHTML($cid)
#----------------------------
# Returns a HTML formated detail of a single PicasaWeb users channel
#
#============================
{
    $msg = '';

    $ret = '<div class="body_title">Channel details ';
    $ret .= '<a href="/channel.php?cid='.$cid.'&action=edit"><img src="/images/edit.png" alt="edit channel" title="Edit Channel" class="actionIcon"></a>';
    $ret .= '&nbsp;<a href="/channel.php?cid='.$cid.'&action=delete"><img src="/images/delete.png" alt="delete channel" title="Delete Channel" class="actionIcon"></a>';
    $ret .= '</div>';

    $ret .= '<div class="body_textarea">';
    if (!isset($cid))   { $cid =0; }

    if ($cid > 0) {                     # don't display channels with no ID
        $cid = prepDBVal($cid);

        $sql = "SELECT * FROM user_channels AS uc, channel_types AS ch WHERE uc.iduserchannels = '$cid' AND ch.idchanneltypes=uc.channel_type_id";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg = '#FAIL - An error occured.';
        } else {
            if (mysql_num_rows( $result ) == 1) {
                $row = mysql_fetch_assoc( $result );
                $attribs = preg_split("/\|/", $row['attrib']);
                if (strlen($attribs[1]) == 0) { $attribs[1] = '-'; }

                $ret .= '<table border="0">';
                $ret .= '<tr><td rowspan="3"><img src="' . $row['channel_icon_url']. '" class="channelTypeIconLrg"/></td><td>Nickname:</td><td>' . $row['chan_nickname'] . '</td></tr>';
                $ret .= '<tr><td>Picasa User:</td><td>' . $attribs[0] . '</td></tr>';
#                $ret .= '<tr><td>Tags:</td><td>' . $attribs[1] . '</td></tr>';
                $ret .= '<tr><td>Last updated:</td><td>' . $row['last_updated'] . ' GMT</td></tr>';
                $ret .= '</table>';
                $msg = $row['status'];
            } else {
                $msg = 'No info for channel ['. $cid.'].';
            }
        }
    } else {
        $ret .= 'No info for channel ['.$cid.'].';
    }

    $ret .= '</div>';
    return array ($msg, $ret);
}

#----------------------------
function channelUserInfoRadarHTML($cid)
#----------------------------
# Returns a HTML formated detail of a single weather radar channel
#
#============================
{
    $msg = '';

    $ret = '<div class="body_title">Channel details ';
    $ret .= '<a href="/channel.php?cid='.$cid.'&action=edit"><img src="/images/edit.png" alt="edit channel" title="Edit Channel" class="actionIcon"></a>';
    $ret .= '&nbsp;<a href="/channel.php?cid='.$cid.'&action=delete"><img src="/images/delete.png" alt="delete channel" title="Delete Channel" class="actionIcon"></a>';
    $ret .= '</div>';

    $ret .= '<div class="body_textarea">';
    if (!isset($cid))   { $cid =0; }

    if ($cid > 0) {                     # don't display channels with no ID
        $cid = prepDBVal($cid);

        $sql = "SELECT * FROM user_channels AS uc, channel_types AS ch WHERE uc.iduserchannels = '$cid' AND ch.idchanneltypes=uc.channel_type_id";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg = '#FAIL - An error occured.';
        } else {
            if (mysql_num_rows( $result ) == 1) {
                $row = mysql_fetch_assoc( $result );
                $attribs = preg_split("/\|/", $row['attrib']);
                if (strlen($attribs[1]) == 0) { $attribs[1] = '-'; }

                $ret .= '<table border="0">';
                $ret .= '<tr><td rowspan="3"><img src="' . $row['channel_icon_url']. '" class="channelTypeIconLrg"/></td><td>Nickname:</td><td>' . $row['chan_nickname'] . '</td></tr>';
                $ret .= '<tr><td>US ZIP code:</td><td>' . $attribs[0] . '</td></tr>';
#                $ret .= '<tr><td>Tags:</td><td>' . $attribs[1] . '</td></tr>';
                $ret .= '<tr><td>Last updated:</td><td>' . $row['last_updated'] . ' GMT</td></tr>';
                $ret .= '</table>';
                $msg = $row['status'];
            } else {
                $msg = 'No info for channel ['. $cid.'].';
            }
        }
    } else {
        $ret .= 'No info for channel ['.$cid.'].';
    }

    $ret .= '</div>';
    return array ($msg, $ret);
}

#----------------------------
function channelUserInfoFlickrHTML($cid)
#----------------------------
# Returns a HTML formated detail of a single Flickr users channel
#
#============================
{
    $ret = '<div class="body_title">Channel details ';
    $ret .= '<a href="/channel.php?cid='.$cid.'&action=edit"><img src="/images/edit.png" alt="edit channel" title="Edit Channel" class="actionIcon"></a>';
    $ret .= '&nbsp;<a href="/channel.php?cid='.$cid.'&action=delete"><img src="/images/delete.png" alt="delete channel" title="Delete Channel" class="actionIcon"></a>';
    $ret .= '</div>';

    $ret .= '<div class="body_textarea">';
    if (!isset($cid))   { $cid =0; }

    if ($cid > 0) {                     # don't display channels with no ID
        $cid = prepDBVal($cid);

        $sql = "SELECT * FROM user_channels AS uc, channel_types AS ch WHERE uc.iduserchannels = '$cid' AND ch.idchanneltypes=uc.channel_type_id";
        $result = mysql_query($sql);
        if (!$result) {
            die("[$sql]: Invalid query: " . mysql_error());
            $msg = '#FAIL - An error occured.';
        } else {
            if (mysql_num_rows( $result ) == 1) {
                $row = mysql_fetch_assoc( $result );
                $attribs = preg_split("/\|/", $row['attrib']);
                if (strlen($attribs[1]) == 0) { $attribs[1] = '-'; }

                $ret .= '<table border="0">';
                $ret .= '<tr><td rowspan="4"><img src="' . $row['channel_icon_url']. '" class="channelTypeIconLrg"/></td><td>Nickname:</td><td>' . $row['chan_nickname'] . '</td></tr>';
                $ret .= '<tr><td>Flickr Screename:</td><td>' . $attribs[0] . '</td></tr>';
                $ret .= '<tr><td>Tags:</td><td>' . $attribs[1] . '</td></tr>';
                $ret .= '<tr><td>Last updated:</td><td>' . $row['last_updated'] . ' GMT</td></tr>';
                $ret .= '</table>';
                $msg = $row['status'];
            } else {
                $msg = 'No info for channel ['. $cid.'].';
            }
        }
    } else {
        $ret .= 'No info for channel ['.$cid.'].';
    }

    $ret .= '</div>';
    return array ($msg, $ret);
}

?>
