<?php
#----------------------------------------------------
# 2012-jan-15 - TimC
#   - define $redir in doPOST()
#   - fix call to doGET() in doPOST() to include $fid
#   - check if $_REQUEST['del_chan'] is set before testing value in doPOST()
#----------------------------------------------------
include_once 'inc/dbconfig.php';
include_once 'inc/config.php';
include_once 'inc/helpers.php';
include_once 'inc/chan_helpers.php';
include_once 'inc/frame_helpers.php';
include_once 'inc/helper_user.php';
require_once 'inc/user_channel_class.php';

    if (session_id() == '') { session_start(); }

    if (!(isset($_SESSION['username']))) {
        header('Location:/');
    }

    $dbh = dbStart();

#----------------------------
function doGET($id, $ctid, $action, $fid, $showtest)
#----------------------------
{
    $msg = '';
    $html = '';

    if ($id == 0) {           # new channel
        if ($ctid == 0) {       # need to pick channel type;
            list ($msg, $html) = channelTypeEnumHTML($fid, $showtest);
        } else {
            list ($msg, $html) = channelUserForm($id, $ctid, $fid);
        }
    } else {
        if ($action == 'edit') {
            list ($msg, $html) = channelUserForm($id, $ctid, $fid);
        } else {
            list ($msg, $html) = channelUserInfoHTML($id);
        }
    }

    return array ($msg, $html);
}

#----------------------------
function doPOST($id, $fid, $showtest)
#----------------------------
{
    $msg = '';
    $html = '';
    $redir = '';

    if ($id != 0) {
        if ( isset( $_REQUEST['del_chan'] ) and ( $_REQUEST['del_chan'] == 'delchan' ) ) {
            $msg = 'Channel "' . $_REQUEST['nickname'] . '" deleted.';
            $ret = channelUserDel($id);
            $redir = '/?msg='.$msg;
        } else {
            list ($ret, $msg) = channelUserUpdate($id, $_REQUEST['nickname'], $_REQUEST['attrib'], 'Y', '');
            if ( $ret ) {
#                if($msg) { $msg = $msg . '<br>Channel Updated.'; } else { $msg = 'Channel Updated.'; }
                list ($d, $html) = doGET( $id, $_REQUEST['chantype'], '', $fid, $showtest );
            }
        }
    } else {
        list ($ret, $itm) = channelUserAdd($_SESSION['uid'], $_REQUEST['chantype'], $_REQUEST['nickname'], $_REQUEST['attrib'],'Y', 'New channel - It may take up to an hour before images are available on this channel.');
        if ( $ret > 0) {
            $msg = 'Channel Added.  It may take upto an hour before images are available on this channel.';
            if ($fid > 0) {             # we're adding a newly created channel to a frame
                list ($m, $h) = frameAddChan($fid, $ret);
                list ($m2, $r2) = frameAddItem($fid, $cid, $itm);
                $redir = '/frame.php?fid=' . $fid;
                $msg .= $m . $m2;
            } else {
                list ($d, $html) = doGET($ret, $_REQUEST['chantype'], '', $fid, $showtest);
            }
        } else {
            $msg .= 'Channel creation failed - bummer. fid:['.$fid.']';
        }
    }

    return array ($msg, $html, $redir);
}

#---------------------------
# M A I N
#---------------------------
    if (isset($_REQUEST['cid'])) { $id=$_REQUEST['cid']; } else { $id = 0; }
    if (isset($_REQUEST['fid'])) { $fid=$_REQUEST['fid']; } else { $fid = 0; }
    if (isset($_REQUEST['ctid'])) { $ctid=$_REQUEST['ctid']; } else { $ctid = 0; }
    if (isset($_REQUEST['action'])) { $action=$_REQUEST['action']; } else { $action = ''; }

    $chn = new UserChannel( $dbh, $id, $_SESSION['uid'] );      # Load the channel

    if( !$chn->isOwner( $_SESSION['uid'] ) ) {          # is the current user the owner?
        $msg='You are not the owner of that channel.';
        $body = '';
    } else {
        $errs = 0;
        $body = '';
        $redir = '';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            list ($msg, $body, $redir) = doPOST($id, $fid, userIsAdmin($_SESSION['uid']) );
        } else {
            list ($msg, $body) = doGET($id, $ctid, $action, $fid, userIsAdmin($_SESSION['uid']) );
        }

        if ( strlen($redir) > 0 ) {
            header('Location: ' . $redir);
        }
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>FrameAlbum (beta) - A FrameChannel service replacement</title>
<link href="style.css" rel="stylesheet" type="text/css" />
<?php
    include_once 'js.inc';
    include_once 'validate.inc';
?>
<script type="text/javascript">
function validFlickrUser()
{
    var x=document.getElementById('flickr_user').value
    if (x.length < 6) {
        document.getElementById('flickr_user_msg').src='/images/knobs/Attention.png';
        return false;
    } else {
        document.getElementById('flickr_user_msg').src='/images/knobs/Valid_Green.png';
        return true;
    }
}

function setDelIcon()
{
    if (document.getElementById('del_chan').checked) {
        document.getElementById('del_chan_msg').src='/images/knobs/Remove_Red.png';
    } else {
        document.getElementById('del_chan_msg').src='/images/blank.png';
    }

}

function constructAttribFlickr()
{
    var e=document.getElementById('flickr_user').value;
    var t=document.getElementById('f_tags').value;
    var a=e + "|" + t;

    document.getElementById('attrib').value=a;

    return true;
}

function constructAttrib1()
{
    var a0=document.getElementById('attrib0').value;
    var a=(a0 + '|');

    document.getElementById('attrib').value=a;

    return true;
}

function constructAttrib2()
{
    var a0=document.getElementById('attrib0').value;
    var a1=document.getElementById('attrib1').value;
    var a=(a0 + '|' + a1);

    document.getElementById('attrib').value=a;

    return true;
}

function constructAttrib()
{
    var a0=document.getElementById('attrib0').value;
    var a1=document.getElementById('attrib1').value;
    var a2=document.getElementById('attrib2').value;
    var a3=document.getElementById('attrib3').value;
    var a=(a0 + '|' + a1 + '|' + a2 + '|' + a3);

    document.getElementById('attrib').value=a;

    return true;
}

function validateForm()
{
    var valid = validNickname();

    valid = (valid && validEmail() );

    return valid;
}

function validZIPCode()
{
    var field=document.getElementById('attrib0').value;
    var valid = "0123456789-";
    var hyphencount = 0;

    if (field.length!=5 && field.length!=10) {
        alert("Please enter your 5 digit or 5 digit+4 US ZIP code.");
        document.getElementById('attrib0_msg').src='/images/knobs/Attention.png';
        return false;
    }

    for (var i=0; i < field.length; i++) {
        temp = "" + field.substring(i, i+1);
        if (temp == "-") hyphencount++;
        if (valid.indexOf(temp) == "-1") {
            alert("Invalid characters in your ZIP code.  Please try again.");
            document.getElementById('attrib0_msg').src='/images/knobs/Attention.png';
            return false;
        }

        if ((hyphencount > 1) || ((field.length==10) && ""+field.charAt(5)!="-")) {
            alert("The hyphen character should be used with a properly formatted 5 digit+four US ZIP code, like '12345-6789'.   Please try again.");
            document.getElementById('attrib0_msg').src='/images/knobs/Attention.png';
            return false;
        }
    }

    document.getElementById('attrib0_msg').src='/images/knobs/Valid_Green.png';

    return true;
}

</script>
</head>

<body onLoad="mpmetrics.track('UserMain');">
<?php
    include_once "topheader.inc";
    include_once "search_strip.inc";
?>
<div id="body_area">
<?php
    include_once "left.inc";
?>
<!-- end of 'left' DIV -->

  <div class="midarea">
<?php
#    if ( isset($msg) and (strlen($msg) > 0) ) { echo '<div class="body_message">' . $msg . '</div>'; }

    if ( isset($body) ) { echo $body; }
?>
  </div>
<!-- end of 'midarea' DIV -->

<?php
    include_once 'footer_home.inc';
?>
</body>
</html>
