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

#----------------------------
function userForm($uid)
#----------------------------
{
    $msg = '';
    $htmo = '';

    $html .= '<h3>User detail ['.$uid.']</h3>';

    return array ($msg, $html);
}

#----------------------------
function userList()
#----------------------------
{
    $msg = '';
    $htmo = '';

    $html .= '<h3>User List</h3>';

    return array ($msg, $html);
}

#----------------------------
function doGET($uid, $action)
#----------------------------
{
    $msg = '';
    $html = '';
    $redir = '';

    if (($action == 'edit') or ($action == 'add')) {
        list ($msg, $html) = userForm($uid);
    } else {
        list ($msg, $html) = userList();
    }

    return array ($msg, $html, $redir);
}

#----------------------------
function doPOST($id)
#----------------------------
{
    $msg = '';
    $html = '';
    $redir = '';

    if ($id != 0) {
        if ($_REQUEST['del_frame'] == 'delframe') {
            frameDel($id);
            $msg = 'Frame "' . $_REQUEST['nickname'] . '" deleted.';
        } else {
            list ($ret, $msg) = frameUpdate($id,$_REQUEST['frameid'], $_REQUEST['nickname'], $_REQUEST['prodid'], 'Y');
            if ( $ret ) {
                $msg = 'Frame Updated.';
                list ($d, $html, $redir) = doGET($id,'',0);
            }
        }
    } else {
        list ($ret, $msg) = frameAdd($_SESSION['uid'], $_REQUEST['frameid'], $_REQUEST['nickname'], $_REQUEST['prodid'],'Y', 0);
        if ( $ret > 0) {
            $msg = 'Frame Added.';
            list ($d, $html, $redir) = doGET($ret,'',0);
        }
    }

    return array ($msg, $html, $redir);
}

#---------------------------
# M A I N
#---------------------------
    if (isset($_REQUEST['action'])) { $action=$_REQUEST['action']; } else { $action = ''; }
    if (isset($_REQUEST['uid'])) { $fid=$_REQUEST['uid']; } else { $uid = 0; $action='add';}

    $errs = 0;
    $body = '';
    $redir = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        list ($msg, $body, $redir) = doPOST($fid);
    } else {
        list ($msg, $body, $redir) = doGET($fid, $action, $cid);
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

<!-- accordion menu scripts -->
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>

<script type="text/javascript" src="ddaccordion.js">

/***********************************************
* Accordion Content script- (c) Dynamic Drive DHTML code library (www.dynamicdrive.com)
* Visit http://www.dynamicDrive.com for hundreds of DHTML scripts
* This notice must stay intact for legal use
***********************************************/

</script>
<script type="text/javascript">


ddaccordion.init({
    headerclass: "expandable", //Shared CSS class name of headers group that are expandable
    contentclass: "categoryitems", //Shared CSS class name of contents group
    revealtype: "click", //Reveal content when user clicks or onmouseover the header? Valid value: "click", "clickgo", or "mouseover"
    mouseoverdelay: 200, //if revealtype="mouseover", set delay in milliseconds before header expands onMouseover
    collapseprev: true, //Collapse previous content (so only one open at any time)? true/false 
    defaultexpanded: [0], //index of content(s) open by default [index1, index2, etc]. [] denotes no content
    onemustopen: false, //Specify whether at least one header should be open always (so never all headers closed)
    animatedefault: false, //Should contents open by default be animated into view?
    persiststate: true, //persist state of opened contents within browser session?
    toggleclass: ["", "openheader"], //Two CSS classes to be applied to the header when it's collapsed and expanded, respectively ["class1", "class2"]
    togglehtml: ["prefix", "", ""], //Additional HTML added to the header when it's collapsed and expanded, respectively  ["position", "html1", "html2"] (see docs)
    animatespeed: "fast", //speed of animation: integer in milliseconds (ie: 200), or keywords "fast", "normal", or "slow"
    oninit:function(headers, expandedindices){ //custom code to run when headers have initalized
        //do nothing
    },
    onopenclose:function(header, index, state, isuseractivated){ //custom code to run whenever a header is opened or closed
        //do nothing
    }
})


</script>

<style type="text/css">

.arrowlistmenu{
width: 180px; /*width of accordion menu*/
}

.arrowlistmenu .menuheader{ /*CSS class for menu headers in general (expanding or not!)*/
font: bold 14px Arial;
color: white;
background: black url(titlebar.png) repeat-x center left;
margin-bottom: 10px; /*bottom spacing between header and rest of content*/
text-transform: uppercase;
padding: 4px 0 4px 10px; /*header text is indented 10px*/
cursor: hand;
cursor: pointer;
}

.arrowlistmenu .openheader{ /*CSS class to apply to expandable header when it's expanded*/
background-image: url(titlebar-active.png);
}

.arrowlistmenu ul{ /*CSS for UL of each sub menu*/
list-style-type: none;
margin: 0;
padding: 0;
margin-bottom: 8px; /*bottom spacing between each UL and rest of content*/
}

.arrowlistmenu ul li{
padding-bottom: 2px; /*bottom spacing between menu items*/
}

.arrowlistmenu ul li a{
color: #A70303;
background: url(arrowbullet.png) no-repeat center left; /*custom bullet list image*/
display: block;
padding: 2px 0;
padding-left: 19px; /*link text is indented 19px*/
text-decoration: none;
font-weight: bold;
border-bottom: 1px solid #dadada;
font-size: 90%;
}

.arrowlistmenu ul li a:visited{
color: #A70303;
}

.arrowlistmenu ul li a:hover{ /*hover state CSS*/
color: #A70303;
background-color: #F3F3F3;
}

</style>
<!-- end of accordion scripts -->
<?php
    include_once '../js.inc';
?>
</head>

<body onLoad="mpmetrics.track('AdminMain');">
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
    <div class="body_textarea">
      <div align="justify"><a href="/admin/releasepre.php">Release pre-registrants</a></div>

      <div align="justify"><a href="/admin/info.php">PHP Info.</a></div>
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
