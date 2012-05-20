<?php
    if (session_id() == "") { session_start(); }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>FrameAlbum (beta) - A FrameChannel service replacement</title>
<link href="style.css" rel="stylesheet" type="text/css" />
<?php
    include_once 'js.inc';
?>
</head>

<body onLoad="mpmetrics.track('FAQ');">
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
    <div class="head">FAQ </div>
    <div class="body_faq_q">How do I configure my frame to use the FrameAlbum service?
    </div>
    <div class="body_faq_a">Most frames that include a WiFi feature are 'hard wired' to use the old FrameChannel service.  Unfortunatly, it is nigh impossible to
 reprogram them to use FrameAlbum instead.  What we are left with is 'fooling' your frame into thinking it is talking to the FrameAlbum service.  There are three metho
 two involve a fair bit of geekery the other on a feature present in some frames (Kodak for example).
    </div>
    <div class="body_faq_a"><strong>Photo RSS feed:</strong> Some frames, Kodak for example, include a feature to display a 'Photo RSS feed'.
  Some frames refer to it as a 'custom RSS feed'.  If your frame supports this feature it is the simplist method to get your frame to use the FrameAlbum service.
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
