<?php
    include_once "inc/config.php";
    require_once "inc/dbconfig.php";
    include_once "inc/helpers.php";
    include_once "inc/user_class.php";

    list ( $fbuser, $fb_btn ) =  loginInit( );

    $dbh = dbStart();

#---------------------------
function doGET( $dbh, $tok )
#---------------------------
{
    $msg = '';
    $html = '';

    if (strlen($tok) > 0) {
        $uid = userGetUIDByToken( $tok );

        if( $uid > 0 ) {        # did we find it?
            $u = new User( $dbh, $uid );
            $u->email_conf( 'Y' );
            $u->token( '' );
            $u->save();
            $msg .= "Thanks for confirming your email address.";
            $html .= "<p>You can now login and start enjoying FrameAlbum!</p>";

            $l = new EventLog( 14 );
            $l->user_id( $uid );
            $l->event_msg( 'User confirmed email address.' );
            $l->save( );

        } else {                # nope
            $msg .= "Bzzzzzt!  Sorry, that confirmation token is not valid.";
            $html .= '<p>Parhaps you should login and confirm that your email address is recorded correctly.</p>';
        }
    } else {               # nope
        $msg .= "Bzzzzzt!  Sorry, that confirmation token is not valid.";
        $html .= '<p>Parhaps you should login and confirm that your email address is recorded correctly.</p>';
    }

    return array ($msg, $html);
}

#==============================
# M A I N
#==============================

    if (isset($_REQUEST['tok'])) { $tok=$_REQUEST['tok']; } else { $tok = ''; }

    $msg = '';
    $body = '';

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        list ($msg, $body) = doGET( $dbh, $tok );
    }

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>FrameAlbum (beta) - A FrameChannel service replacement</title>
<link href="<?php echo $GLOBALS['static_url_root'].'/' ?>style.css" rel="stylesheet" type="text/css" />
<?php
    include_once 'js.inc';
?>
</head>

<body onLoad="mpmetrics.track('Login');">
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

    <div class="head">Login</div>
<?php
    if ( isset( $body ) ) { echo '<div class="body_text">' . $body . '</div>'; }
?>
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
