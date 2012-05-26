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
 reprogram them to use FrameAlbum instead.  What we are left with is 'fooling' your frame into thinking it is talking to the FrameAlbum service.  There are three methods of getting your frame to 'talk' to the FrameAlbum service;
 two involve a fair bit of geekery the other on a feature present in some frames (Kodak for example); 'Photo RSS'.
    </div>
    <div class="body_faq_a"><strong>Photo RSS feed:</strong> Some frames, Kodak for example, include a feature to display a 'Photo RSS feed'.
  Some frames refer to it as a 'custom RSS feed'.  If your frame supports this feature it is the simplist method to get your frame to use the FrameAlbum service.
    </div>

    <div class="body_faq_a"><strong>Custom DNS:</strong> This involves a bit of geekery; if these next words don't make sense to you then please ask your 'geeky neighbor/friend/siblling/child' to translate for you.  The FrameAlbum service includes a custom DNS server that will fake your frame into connecting to our servers instead of FrameChannel.  This requires that you configure your frame with a static IP address and specify the FrameAlbum DNS rather than the one supplied by your ISP.  You can find the details of this setup on the blog at <a href="http://www.streamingmeemee.com/index.php/2011/11/04/the-framealbum-dns-server-is/">http://www.streamingmeemee.com/index.php/2011/11/04/the-framealbum-dns-server-is/</a>.
    </div>

    <div class="body_faq_a"><strong>DNS redirection</strong>Some WiFi routers/firewalls include a feature that allows 'DNS redirection'.  Basically this says 'if someone tries to connect to X, connect them to Y instead.'.  This does not require any changes on the frame itself.  This feature is rather rare on consumer level equipment and the least likely to be available to you.
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
