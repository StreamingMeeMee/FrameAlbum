<?php
    if( session_id() == "" ) { session_start(); }
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

<body onLoad="mpmetrics.track('About');">
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
    <div class="head">About FrameAlbum</div>
    <div class="body_textarea">
      <div align="justify">We have created a open-source implementation of the FrameChannel service.</div>
    </div>
    <div class="body_textarea">
      <div align="justify">
        <p>If you are reading this then you are no doubt aware that the FrameChannel service shutdown at the end of June 2011.  This is an unfortunate turn of events and a decision that the folks at FrameChannel, I'm sure, did not take lightly.</p>
      </div>
    </div>
    <div class="body_textarea">
      <div align="justify">
        <p>I've grown quite fond of my Kodak W820 frames.  I have used the FrameChannel service to consolidate my Flickr and Picasa photos as well as provide weather information, news and the occasional drink recipe <grin>.</p>
      </div>
    </div>
    <div class="body_textarea">
      <div align="justify">
        <p>That all went away when FrameChannel shut down -- or did it?</p>
      </div>
    </div>

    <div class="body_textarea" align="justify">
        <p>Not being one to cry into his venti mocha (Mmm... Coffee. Good.) I said to myself "Self, you have some skills, you can build a replacement."  Once I explained to the other Starbucks customers that I wasn't off my meds. I sprinted home and set to work building FrameAlbum.</p>
    </div>

    <div class="body_textarea" align="justify">
        <p>The goal of FrameAlbum is to offer a viable FrameChannel replacement service and to open-source the resulting software.  The initial iteration is basic, supporting only a limited number of photo sharing sites.  With time, a little patience and many venti mochas I hope to be able to offer weather, news, stock info., photo uploads...  namely, all the things we loved about FrameChannel.</p>
    </div>

    <div class="body_textarea" align="justify">
        <p>I invite you to register for the service (on the left) and breath new life into your internet connected digital photo frame.</p>
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
