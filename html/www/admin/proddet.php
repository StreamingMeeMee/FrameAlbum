<?php
include_once '../inc/dbconfig.php';
include_once '../inc/config.php';
include_once '../inc/helpers.php';
include_once '../inc/helper_user.php';
include_once '../inc/product_class.php';

    if (session_id() == '') { session_start(); }

    if (!(isset($_SESSION['loggedin'])) or ($_SESSION['loggedin'] != 'Y' )) {
        header('Location:/');
    }

    $dbh = dbStart();

    if (!isset($_SESSION['username']) or !userIsAdmin($_SESSION['username'])) {
        header('Location:/');
    }

#----------------------------
function doGET( $dbh,  $id, $act )
#----------------------------
{
    $msg = '';
    $html = '';
    $redir = '';
    $ret = 0;

    if ( $act == 'add' ) { 
        $p = new Product( $dbh, $id );
        $html .= '<h3>Adding a new frame product</h3>';
        $html .=  $p->htmlform( 'proddet.php' );
    } else if( $id > 0 ) {
        $p = new Product( $dbh, $id );
        $html .= '<h3>' . $p->manuf() . ' - ' . $p->model() . ' [' . $id . ']</h3>';
        $html .=  $p->htmlform( 'proddet.php' );
    } 

    $html .= '<p><a href="/admin/prodlist.php">Return to product list</a></p>';

    return array ($msg, $html, $redir);
}

#----------------------------
function doPOST( $dbh, $id )
#----------------------------
{
    $msg = '';
    $html = '';
    $redir = '';

    $p = new Product( $dbh, $_REQUEST['idproduct'] );

    list( $msg, $html, $redir) = $p->procform( $_REQUEST );

    $html .= '<p><a href="/admin/prodlist.php">Return to product list</a></p>';

    return array ($msg, $html, $redir);
}

#---------------------------
# M A I N
#---------------------------
    if (isset($_REQUEST['id'])) { $id=$_REQUEST['id']; } else { $id = 0; }
    if( isset( $_REQUEST['act'] ) ) { $act = $_REQUEST['act']; } else { $act = 'add'; }
    if( $id > 0 ) { $act = 'edit'; }

    $errs = 0;
    $body = '';
    $redir = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        list ($msg, $body, $redir) = doPOST( $dbh, $id );
    } else {
        list ($msg, $body, $redir) = doGET( $dbh, $id, $act );
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
<link href="/style.css" rel="stylesheet" type="text/css" />
<?php
#    include_once '../js.inc';
    include_once '../validate.inc';
?>
</head>

<body>
<?php
    include_once "../topheader.inc";
?>
 <div id="search_strip">
 </div>
<div id="body_area">
<?php
    include_once "../left.inc";
?>
<!-- end of 'left' DIV -->

  <div class="midarea">
<?php
    echo '<div class="body_title">Frame Products';

    if ( isset($msg) and (strlen($msg) > 0) ) { echo '<div class="body_message">' . $msg . '</div>'; }

    if ( isset($body) ) { echo '<div class="body_textarea"><div align="justify">' . $body . '</div></div>'; }
?>
  </div>
<!-- end of 'midarea' DIV -->

  <div class="right">
    <div class="comments_area"></div>
  </div>
<!--</div>-->
<?php
    include_once '../footer_home.inc';
?>
</body>
</html>
