<?php
include_once 'inc/config.php';

    if (session_id() == "") { session_start(); }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>FrameAlbum (beta) - A FrameChannel service replacement</title>
<link href="<?php echo $GLOBALS['static_url_root'].'/' ?>style.css" rel="stylesheet" type="text/css" />
<?php
    include_once "js.inc";
?>
</head>

<body onLoad="mpmetrics.track('Contact');">
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
    <div class="head">Contact Us</div>
    <div class="body_textarea">
      <div align="justify">And by 'Us' I really mean me. ;-).</div>
    </div>
    <div class="body_textarea">
      <div align="justify">
        <p>If you questions or a problem with the service you can contact us/me at info@framealbum.com  You can also join the conversation on my blog; <a href="http://www.streamingmeemee.com">Stream of Consciousness</a>.</p>
      </div>
    </div>
  </div>
<!-- end of 'midarea' DIV -->

<!-- right DIV -->
<?php
    include_once "right.inc";
?>
<!-- end of 'right' DIV -->
<?php
    include_once 'footer.inc';
?>
</body>
</html>
