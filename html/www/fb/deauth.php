<?php
require_once 'inc/dbconfig.php';
require_once 'inc/config.php';
require_once 'inc/helper_user.php';
require_once 'inc/helper_fb.php';
require_once 'inc/user_class.php';

function parse_signed_request($signed_request, $secret) {
  list($encoded_sig, $payload) = explode('.', $signed_request, 2); 

  // decode the data
  $sig = base64_url_decode($encoded_sig);
  $data = json_decode(base64_url_decode($payload), true);

  if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
    error_log('Unknown algorithm. Expected HMAC-SHA256');
    return null;
  }

  // check sig
  $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
  if ($sig !== $expected_sig) {
    error_log('Bad Signed JSON signature!');
    return null;
  }

  return $data;
}

function base64_url_decode($input) {
    return base64_decode(strtr($input, '-_', '+/'));
}

if ($_REQUEST) {
//  echo '<p>signed_request contents:</p>';
    $response = parse_signed_request($_REQUEST['signed_request'], $GLOBALS['fb_api_secret']);

    error_log( 'deauth:[' . print_r( $response, TRUE ) . ']' );
    error_log( 'deauth: FB userID:[' . $response['user_id'] . ']');

    $dbh = dbStart();

//    echo 'Searching for match:['. $response['registration']['email'] .']';
    $uid = userFindFBUser( $response['user_id'] );
//    echo 'found UID:[' . $uid . ']';

    if( $uid ) {            # link to an existing user
        $user = new User( $dbh, $uid );
        $user->fb_uid( 0 ); # unlink the user
        $user->fb_auth( 'xx' );
        $user->fb_auth_expire( 0 );
        $user->save();
        $msg = 'Your Facebook account has been un-linked from your FrameAlbum account.';
        error_log( 'deauth: FrameAlbum userID:[' . $uid . '] has been deauthorized by FaceBook userID:['. $response['user_id'] . ']' );
    }

    $l = new EventLog();
    $l->logSystemInfoEvent($uid, 'FB DEAUTH' );

#    echo $msg;

#    echo $user->stringify();
    
} else {
    header('Location:/?msg=An error occured during registration.');
}
?>
