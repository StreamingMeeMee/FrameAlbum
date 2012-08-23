<?php
include_once '../inc/dbconfig.php';
include_once '../inc/config.php';
include_once '../inc/helpers.php';
include_once '../inc/helper_user.php';
include_once 'releasepre_inc.php';

    if (session_id() == '') { session_start(); }

    if (!(isset($_SESSION['loggedin'])) or ($_SESSION['loggedin'] != 'Y' )) {
        header('Location:/');
    }

    $dbh = dbStart();

    if (!isset($_SESSION['username']) or !userIsAdmin($_SESSION['username'])) {
        header('Location:/');
    }

#-----------------------
function enumProds()
#-----------------------
{
    $r = '';

    $sql = "SELECT * FROM product_ids ORDER BY manuf, model";
    $result = mysql_query($sql);
    if (!$result) {
        die("[$sql]: Invalid query: " . mysql_error());
        $r = '<p>#FAIL - An error occured.</p>';
    } else {
        if (mysql_num_rows( $result ) > 0) {
            $r .= '<table border="0">';
            while( $row = mysql_fetch_assoc( $result ) ) {
                $r .= '<tr><td>' . $row['manuf'] . '<td><a href="/admin/proddet.php?id='.$row['idproduct'].'">' . $row['model'] . '</td></tr>';
            }
            $r .= '</table>';
        } else {
            $r = '<p>No frame products on file.<p>';
        }
    }

    return $r;
}

#----------------------------
function doGET($uid)
#----------------------------
{
    $msg = '';
    $html = '';
    $redir = '';
    $ret = 0;

    $html = enumProds();

    return array ($msg, $html, $redir);
}

#----------------------------
function doPOST($id)
#----------------------------
{
    $msg = '';
    $html = '';
    $redir = '';

    return array ($msg, $html, $redir);
}

#---------------------------
# M A I N
#---------------------------
    if (isset($_REQUEST['uid'])) { $uid=$_REQUEST['uid']; } else { $uid = 0; }

    $errs = 0;
    $body = '';
    $redir = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        list ($msg, $body, $redir) = doPOST($uid);
    } else {
        list ($msg, $body, $redir) = doGET($uid);
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
    include_once '../js.inc';
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
    echo '<div class="body_title">Frame Products</div>';
    echo '<div><a href="proddet.php"><img src="/images/knobs/Add.png" alt="add icon">Add a new frame product</a></div>';

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
