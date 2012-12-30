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

<body onLoad="mpmetrics.track('TOS');">
<?php
    include_once "topheader.inc";
    include_once "search_strip.inc";
?>
<div id="body_area">
<?php
    include_once "left.inc";
?>
<!-- end of 'left' DIV -->

  <div class="midarea_long">

    <div class="head">Web Site Terms of Use</div>
    <div class="body_textarea">
I wrestled with this for a long time... I looked at several sample policies and they were all peppered with lawyer-speak as to be incomprehensible to the average human.

So I wrote my own.
    </div>
<div class="body_textarea">
<h3>
    1. Terms
</h3>

By accessing this web site, you are agreeing to be bound by these 
web site Terms and Conditions of Use, all applicable laws and regulations. 
If you do not agree with any of these terms, you are prohibited from 
using or accessing this site. The materials contained in this web site are 
protected by applicable copyright and trade mark law.

<h3>
    2. Use License
</h3>

<ol type="a">
    <li>
FrameAlbum is a service by which it's users can control the content displayed on digital photo frames (and other means of reading/displaying Media RSS feeds).  The Service aggregates feeds ('channels') that have been requested and configured by it's users and makes those aggregated feeds available for consumption by media devices/software such as digital photo frames.
    </li>
    <li>
FrameAlbum is free for personal use.  If you have a commercial use in mind, let's talk about it <?php echo $GLOBALS['email_from'] ?>.
    </li>
    <li>
This license shall automatically terminate if you violate any of these restrictions and may be terminated or modified by Frame Album at any time.
    </li>
</ol>

<h3>
    3. Disclaimer
</h3>

The copyright of any material displayed on, or through, the FrameAlbum service remain with the original copyright holder.  FrameAlbum makes no provision to enforce copyright restrictions on material included in, or linked to, from feeds requested or provided by users of the FrameAlbum service.  Users of the FrameAlbum service are wholely responsible for what appears on thier frames; FrameAlbum will not be held liable for feeds added or requested by it's users.

<h3>
    4. Limitations
</h3>

In no event shall FrameAlbum or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption,) arising out of the use or inability to use the materials on FrameAlbum's Internet site or frame feeds.
            
<h3>
    5. Revisions and Errata
</h3>

Bugs Happen.  The materials appearing on FrameAlbum's web site and frame feeds could include technical, typographical, or photographic errors. Framex Album does not warrant that any of the materials on its web site are accurate, complete, or current. FrameAlbum may make changes to the materials contained on its web site or frame feeds at any time without notice.

<h3>
    6. Links
</h3>

FrameAlbum has not reviewed all of the content linked to by its Internet web site and/or the frame feeds and is not responsible for the contents of any such linked content.  Use of any such linked content is at the user's own risk.

<h3>
    7. Site Terms of Use Modifications
</h3>

FrameAlbum may revise these terms of use for its web site and feeds at any time without notice. By using this web site you are agreeing to be bound by the then current version of these Terms and Conditions.

<h3>
    8. Governing Law
</h3>
    Any claim relating to Frame Album's web site shall be governed by the laws of the State of Massachusetts without regard to its conflict of law provisions.
</div>
<div class="body_textarea">
<h2>
    Privacy Policy
</h2>
</div>
<div class="body_textarea">
Your privacy is very important to us. Accordingly, we have developed this Policy in order for you to understand how we collect, use, communicate and disclose and make use of personal information.

<ol type="a">
    <li>
        We will collect and use of personal information solely for the purpose of getting information on your frame.  Will will not release this information to third-parties unless we obtain the consent of the individual concerned or as required by law.       
    </li>
    <li>
        We will only retain personal information as long as necessary for the fulfillment of those purposes. 
    </li>
    <li>
        We will collect personal information by lawful and fair means and, where appropriate, with the knowledge or consent of the individual concerned. 
    </li>
    <li>
        Personal data should be relevant to the purposes for which it is to be used. 
    </li>
    <li>
        We will protect personal information by reasonable security safeguards against loss or theft, as well as unauthorized access, disclosure, copying, use or modification.
    </li>
    <li>
        We will make readily available to customers information about our policies and practices relating to the management of personal information. 
    </li>
    <li>
        We do use 'cookies' to retain information during and between login sessions.  We also use cookies to monitor how you use the site so that we may improve your experience.  We do not share this information with third-parties.
    </li>
</ol>
</div>
<div class="body_textarea">
That's it -- if you find this to be unclear, have a question, or harbor violent dissent, please contact us at <?php echo $GLOBALS['email_from']; ?>. 
</div>
<div class="body_textarea">
Last Update: 2011-July-10
</div>


</div> <!-- end of 'midarea' DIV -->

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
