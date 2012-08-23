<?php
#--------------------------------
# helper_fb.php - Facebook helper functions
#
# 2012-aug-16 - TimC
#   - First Go
#
#--------------------------------
include_once 'inc/config.php';
require_once 'inc/user_class.php';

#------------------------------
function fbInit( )
#------------------------------
{
    if( !$facebook ) {
        $facebook = new Facebook(array(
            'appId'  => $GLOBALS['fb_api_key'],
            'secret' => $GLOBALS['fb_api_secret'],
            'cookie' => TRUE
        ));
    }

    $fbuser = $facebook->getUser();       # Get FB user ID

    $_SESSION['fbuid'] = $fbuser;

    if ( $fbuser ) {
        try {
            $fbuser_profile = $facebook->api('/me');
        } catch (FacebookApiException $e) {
            error_log($e);
#            $fbuser = null;
        }
    }

#echo '<pre>fbuser:['. $fbuser . "]</pre>\n";
#echo '<pre>fbprofile:['. print_r($fbuser_profile, true) . "]</pre>\n";
    if ( $fbuser and $fbuser_profile ) {        # if we get here, then the user is logged into FB and
                                                # has already authorized FA
        $dbh = dbstart();
        $uid = userFindFBUser( $_SESSION['fb_' . $GLOBALS['fb_api_key'] . '_user_id'] );
        if( $uid > 0 ) {
            $u = new User( $dbh, $uid );
            $_SESSION['username'] = $u->username();
            $_SESSION['uid'] = $uid;
            $_SESSION['fblogin'] = 'Y';
       }
        $fb_btn = '';

    } else {

        $fb_btn = '<div id="fb-root"></div>
        <script>
            window.fbAsyncInit = function() {
            FB.init({
            appId      : "' . $GLOBALS['fb_api_key'] . '",
            channelUrl : "' . $GLOBALS['www_url_root'] . '/fbchannel.php", // Channel File
        status     : true, // check login status
        cookie     : true, // enable cookies to allow the server to access the session
        xfbml      : true  // parse XFBML
      });
      // Additional initialization code here
    };
    // Load the SDK Asynchronously
    (function(d){
       var js, id = "facebook-jssdk", ref = d.getElementsByTagName("script")[0];
       if (d.getElementById(id)) {return;}
       js = d.createElement("script"); js.id = id; js.async = true;
       js.src = "//connect.facebook.net/en_US/all.js";
       ref.parentNode.insertBefore(js, ref);
     }(document));
  </script>';
        $fb_btn .= '<div class="fb-login-button"
    scope="email,user_photos,user_videos"
    registration_url="' . $GLOBALS['www_url_root'] . '/register.php"
    state="' . session_id() . '"
    response_type="token">
    </div>';
    }

    return array ( $fbuser, $fb_btn );
}

?>
