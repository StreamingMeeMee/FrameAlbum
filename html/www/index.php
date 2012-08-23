<?php
include_once 'inc/config.php';
include_once 'inc/helpers.php';

    if (session_id() == "") { session_start(); }

    if ( isset( $_SESSION['loggedin'] ) and ( $_SESSION['loggedin'] == 'Y' ) ) {
        header('Location:/usermain.php');
    }

    list ( $fbuser, $fb_btn ) =  loginInit( );

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>FrameAlbum (beta) - A FrameChannel service replacement</title>
<link href="style.css" rel="stylesheet" type="text/css" />
<LINK REL="SHORTCUT ICON" HREF="/favicon.ico" />
<html itemscope itemtype="http://schema.org/Product">
<meta itemprop="name" content="FrameAlbum">
<meta itemprop="description" content="FrameAlbum feeds your WiFi enabled digital photo frame all your photos from around the web.  FrameAlbum is an open-source replacement for FrameChannel.">
<?php
    if ( isset( $GLOBALS['mixpanel_key'] ) ) {
        $key = $GLOBALS['mixpanel_key'];
        echo <<<JS

<script type=\"text/javascript\">
    var mp_protocol = ((\"https:\" == document.location.protocol) ? \"https://\" : \"http://\");
    document.write(unescape(\"%3Cscript src='\" + mp_protocol + \"api.mixpanel.com/site_media/js/api/mixpanel.js' type='text/javascript'%3E%3C/script%3E\"));
</script>

<script type=\"text/javascript\">
try {
        var mpmetrics = new MixpanelLib( '$key' );
} catch(err) {
    var null_fn = function () {};
    var mpmetrics = {
        track: null_fn,
        track_funnel: null_fn,
        register: null_fn,
        register_once: null_fn,
        register_funnel: null_fn,
        identify: null_fn
    };
}
</script>

JS;

    }       # mixpanel api

    if ( isset( $GLOBALS['enable_google_plus_one'] ) and ($GLOBALS['enable_google_plus_one']) ) {
#        echo '<script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script>';
        echo '<link href="https://plus.google.com/107079019841451172444" rel="publisher" />';
        echo '<script type="text/javascript">';
        echo '(function() ';
        echo '{var po = document.createElement("script");';
        echo 'po.type = "text/javascript"; po.async = true;po.src = "https://apis.google.com/js/plusone.js";';
        echo 'var s = document.getElementsByTagName("script")[0];';
        echo 's.parentNode.insertBefore(po, s);';
        echo '})();</script>';
    }       # google +1 api
?>
</head>

<body onLoad="mpmetrics.track('Home');">
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
    if (isset($_SESSION['username'])) {
        echo '<div class="body_textarea">Welcome ' . $_SESSION['username'] . '</div>';
    } else {
        echo '<div class="head">Welcome to FrameAlbum</div>';
    }
?>
    <div class="body_textarea">
      <div align="justify">We have created a open-source implementation of the FrameChannel service.  With FrameAlbum you can collect all your photos from around the internet and feed them into your internet enabled digital photo frame.</div>
    </div>
    <div class="body_textarea">
      <div align="justify">At present FrameAlbum supports Flickr and Picasa photo sharing services as well as weather radar feeds of US and Canada.  In the near future will add Facebook photos as well as standard Media-RSS channels to your frame's feed.</div>
    </div>
    <div class="body_textarea">
      <div align="justify">We also maintain a public database of WiFi enabled photo frames that includes specifications, links to the manufactures and downloads of manuals and firmware updates.  You can access this free database at <a href="http://wiki.framealbum.com">wiki.framealbum.com</a>.</div>
    </div>
    <div class="body_textarea">
      <div align="center"><a href="/about.php" class="readmore">Read More </a></div>
    </div>
  </div>
<!-- end of 'midarea' DIV -->

<!-- right DIV -->
<?php
    include_once "right.inc";
?>
<?php
    include_once 'footer_home.inc';
?>
</body>
</html>
