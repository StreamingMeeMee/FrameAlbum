<?php
include_once '../inc/dbconfig.php';
include_once '../inc/config.php';
include_once '../inc/helpers.php';
include_once '../inc/helper_user.php';

    if (session_id() == '') { session_start(); }

    dbStart();

    if (!isset($_SESSION['username']) or !userIsAdmin($_SESSION['username'])) {
        header('Location:/');
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
?>
</head>

<body onLoad="mpmetrics.track('AdminMain');">
<?php
    include_once "../topheader.inc";
    include_once "../search_strip.inc";
?>
<div id="body_area">
<?php
    include_once "../left.inc";
?>
<!-- end of 'left' DIV -->

  <div class="midarea">
    <div class="body_textarea">
      <div align="justify"><a href="/admin/releasepre.php">Release pre-registrants</a></div>
    </div>
    <div class="body_textarea">
      <div align="justify"><a href="/admin/info.php">PHP Info.</a></div>
    </div>
    <div class="body_textarea">
      <div align="justify"><a href="/admin/zendchk.php">ZEND framework check.</a></div>
    </div>
    </div>
  </div>
<!-- end of 'midarea' DIV -->

  <div class="right">
    <div class="comments_area"></div>
  </div>
</div>
<?php
    include_once '../footer_home.inc';
?>
</body>
</html>
