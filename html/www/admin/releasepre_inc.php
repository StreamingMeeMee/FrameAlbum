<?php
include_once '../inc/dbconfig.php';
include_once '../inc/config.php';
include_once '../inc/helpers.php';
include_once 'releasepre_inc.php';

#-----------------------
function releasePreReg($uid)
#-----------------------
{
    $msg = '';
    $ret = 0;

    $headers = 'From: ' . $GLOBALS['email_from'] . "\r\n" .
        'Reply-To: ' . $GLOBALS['email_reply_to'] . "\r\n";

    $sql = "SELECT * FROM users WHERE idusers=$uid";
    $result = mysql_query($sql);
    if (!$result) {
        die("[$sql]: Invalid query: " . mysql_error());
        $msg .= '#FAIL - An error occured during SELECT.';
    } else {
        if (mysql_num_rows( $result ) == 1) {
            $row = mysql_fetch_assoc( $result );
            $regurl = $GLOBALS['www_url_root'] . '/register.php?tok=' . $row['token'];

            $t = "Welcome to the FrameAlbum beta test!\n\n";
            $t .= "You are among the first to test the new FrameAlbum service for your digital photo frame.  Please be aware that the service is a beta test and will have bugs that may impact your ability to use the service.  We'll do our best to squash them before you encounter them but PLEASE DO let us know at " . $GLOBALS['email_from'] . " if you find them before we do.\n\n";
            $t .= "To activate your FrameAlbum account simply click on this link $regurl (or copy it into your browser's address bar) and complete the simple registration form.  We hope you enjoy FrameAlbum!\n\n";

#            $t .= "Some of you may have already received this message.  We are resending it to fix a problem with the registration link.  We're sorry for the confusion.\n\n";
            $t .= "\n\n\nThis email was sent to you because of your pre-registration at www.FrameAlbum.com.  If you believe you have received this message in error, or wish to be removed from our system, please reply and tell us what you would like to do.  You can contact us via our website at " . $GLOBALS['www_url_root'] . "/contact.php.\n\n";
            $t .= "No hortas were harmed in the sending of this message.\n";

            $ret = mail($row['email'], 'Welcome to FrameAlbum', $t, $headers);

            if (!$ret) {
                $msg = 'There was a problem sending a message to ' . $row['email'] . '.  They may not receive your Welcome email message.';
            } else {
                $msg = 'Email sent to userID:['.$uid.'] at ['.$row['email'].']';
                $sql = "UPDATE users SET active='R' WHERE idusers=$uid";
                $result = mysql_query($sql);
                if (!$result) {
                    die("[$sql]: Invalid query: " . mysql_error());
                    $msg .= '#FAIL - An error occured during UPDATE.';
                }
            }
        } else {
            $msg .= 'Unable to locate userID:['.$uid.']';
            $ret = 0;
        }
    }

    return array ($msg, $ret);
}

?>
