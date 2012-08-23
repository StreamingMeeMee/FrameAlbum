<?php
include_once '../inc/dbconfig.php';
include_once '../inc/config.php';
include_once '../inc/helpers.php';
include_once '../inc/user_class.php';

#-----------------------
function releasePreReg($uid)
#-----------------------
{
    $msg = '';
    $ret = 0;

    if( !isset( $uid ) ) { return array( 'No user selected for release.', 0 ); }
    $dbh = dbStart();

    $u = new User( $dbh, $uid );

    if( $u ) {
        $u->active( 'R' );

        $regurl = $GLOBALS['www_url_root'] . '/register.php?tok=' . $u->token();

        $t = "Welcome to the FrameAlbum beta test!\n\n";
        $t .= "You are among the first to test the new FrameAlbum service for your digital photo frame.  Please be aware that the service is a beta test and will have bugs that may impact your ability to use the service.  We'll do our best to squash them before you encounter them but PLEASE DO let us know at " . $GLOBALS['email_from'] . " if you find them before we do.\n\n";
        $t .= "To activate your FrameAlbum account simply click on this link $regurl (or copy it into your browser's address bar) and complete the simple registration form.  We hope you enjoy FrameAlbum!\n\n";

        $t .= "\n\n\nThis email was sent to you because of your pre-registration at www.FrameAlbum.com.  If you believe you have received this message in error, or wish to be removed from our system, please reply and tell us what you would like to do.  You can contact us via our website at " . $GLOBALS['www_url_root'] . "/contact.php.\n\n";
        $t .= "No hortas were harmed in the sending of this message.\n";

        $ret = sendEmail( $GLOBALS['email_from'], $u->email(), 'Welcome to FrameAlbum', $t );

        if (!$ret) {
            $msg = 'There was a problem sending a message to ' . $u->email() . '.  They may not receive your Welcome email message.';
        } else {
            $u->active( 'R' );
            $u->save( );

            $l = new EventLog( 16 );
            $l->user_id( $uid );
            $l->event_msg( 'Released invite to ' . $u->email() . ' for UID:['. $uid . ']' );
            $l->save();

            $msg = 'Email sent to userID:['.$uid.'] at [' . $u->email() . ']';
        } 
    } else {
        $msg .= 'Unable to locate userID:['.$uid.']';
        $ret = 0;
    }

    return array ($msg, $ret);
}

?>
