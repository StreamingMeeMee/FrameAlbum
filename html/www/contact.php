<?php
    if (session_id() == "") { session_start(); }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>FrameAlbum (beta) - A FrameChannel service replacement</title>
<link href="style.css" rel="stylesheet" type="text/css" />
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
        <p>Until I can get a bit more of the instructure built the best way to get in touch with me is to join the conversation on my blog; <a href="http://www.streamingmeemee.com">Stream of Consciousness</a>.</p>
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
