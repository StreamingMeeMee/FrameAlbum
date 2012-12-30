<?php
include_once 'inc/dbconfig.php';
include_once 'inc/config.php';
include_once 'inc/helpers.php';
include_once 'inc/helper_user.php';
require_once 'inc/user_class.php';

    if (session_id() == '') { session_start(); }

    loginChk();

    $dbh = dbStart();

#----------------------------
function doGET($u, $action, $admin)
#----------------------------
{
    $msg = '';
    $html = '';
    $redir = '';

    if (($action == 'edit') or ($action == 'add') or ($action == 'delete') ) {
        $html = $u->htmlform( $admin );
    } else {
        $html = $u->htmlinfo( $admin );
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
    $uid = $_SESSION['uid'];

    if ( userIsAdmin( $_SESSION['username'] ) ) {
        if( isset( $_REQUEST['uid'] ) ) { $uid = $_REQUEST['uid']; }
        $admin = TRUE;
    } else {
        $admin = FALSE;
    }

    $u = new User( $dbh, $uid );

    $errs = 0;
    $body = '';
    $redir = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        list ($msg, $body, $redir) = doPOST($u, $admin);
    } else {
        list ($msg, $body, $redir) = doGET( $u, $action, $admin );
    }

    if ( strlen($redir) > 0 ) {
        header('Location: ' . $redir);
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>FrameAlbum (beta) - A FrameChannel service replacement</title>
<link href="<?php echo $GLOBALS['static_url_root'].'/' ?>style.css" rel="stylesheet" type="text/css" />
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
    <div class="body_title">User Account Info</div>
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
