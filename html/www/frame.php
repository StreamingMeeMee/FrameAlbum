<?php
include_once 'inc/dbconfig.php';
include_once 'inc/config.php';
include_once 'inc/helpers.php';
include_once 'inc/frame_helpers.php';
include_once 'inc/chan_helpers.php';
require_once 'inc/frame_class.php';

    if (session_id() == '') { session_start(); }

    loginChk();

    $dbh = dbStart();

#----------------------------
function doGET($fid, $action, $cid)
#----------------------------
{
    $msg = '';
    $html = '';
    $redir = '';

    if (($action == 'edit') or ($action == 'add') or ($action == 'delete') ) {
        list ($msg, $html) = frameForm($fid, $action);
    } elseif ($action == 'adch') {
        list ($msg, $html) = frameAddChan($fid, $cid);
        list ($m, $html, $r) = doGET($fid, '', '');             # redraw the page with the added channel
    } elseif ($action == 'rmch') {
        list ($msg, $html) = frameDelChan($fid, $cid);
        list ($m, $html, $r) = doGET($fid, '', '');             # redraw the page with the channel removed
    } else {
        list ($msg, $html) = frameInfoHTML($_SESSION['uid'], $fid, $action);
        if ( $GLOBALS['enable_frame_samples'] ) {
            list ($m, $h) = frameSample($fid);
            $msg .= $m;
            $html .= $h;
        }
    }

    return array ($msg, $html, $redir);
}

#----------------------------
function doPOST($id)
#----------------------------
{
    $msg = '';
    $html = '';
    $redir = '';

    if ($id != 0) {
        if ( isset( $_REQUEST['del_frame']) and ( $_REQUEST['del_frame'] == 'delframe' ) ) {
            frameDel($id);
            $msg = 'Frame "' . $_REQUEST['nickname'] . '" deleted.';
        } else {
            list ($ret, $msg) = frameUpdate($id,$_REQUEST['frameid'], $_REQUEST['nickname'], $_REQUEST['prodid'], 'Y', $_REQUEST['shuffle_images']);
            if ( $ret ) {
                $msg = 'Frame Updated.';
                list ($d, $html, $redir) = doGET($id,'',0);
            }
        }
    } else {
        list ($ret, $msg) = frameAdd($_SESSION['uid'], $_REQUEST['frameid'], $_REQUEST['nickname'], $_REQUEST['prodid'],'Y', $_REQUEST['shuffle_images'], 0);
        if ( $ret > 0) {
            $msg = 'Frame Added.';
            list ($d, $html, $redir) = doGET($ret,'',0);
        }
    }

    return array ($msg, $html, $redir);
}

#---------------------------
# M A I N
#---------------------------
    if (isset($_REQUEST['action'])) { $action=$_REQUEST['action']; } else { $action = ''; }
    if (isset($_REQUEST['fid'])) { $fid=$_REQUEST['fid']; } else { $fid = 0; $action='add';}
    if (isset($_REQUEST['cid'])) { $cid=$_REQUEST['cid']; } else { $cid = 0; }

    $fr = new Frame( $dbh, $fid, $_SESSION['uid'] );

    if( !$fr->isOwner( $_SESSION['uid'] ) ) {          # is the current user the owner?
        $msg='You are not the owner of that frame.';
        $body = '';
    } else {
        $errs = 0;
        $body = '';
        $redir = '';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            list ($msg, $body, $redir) = doPOST($fid);
        } else {
            list ($msg, $body, $redir) = doGET($fid, $action, $cid);
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
function setDelIcon()
{
    if (document.getElementById('del_frame').checked) {
        document.getElementById('del_frame_msg').src='/images/knobs/Remove_Red.png';
    } else {
        document.getElementById('del_frame_msg').src='/images/blank.png';
    }

}
</script>
</head>

<body onLoad="mpmetrics.track('FrameDetail');">
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

    if ( isset($body) ) { echo $body; }
?>
  </div>
<!-- end of 'midarea' DIV -->

<?php
    include_once 'footer_home.inc';
?>
</body>
</html>
