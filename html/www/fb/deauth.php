<?php
require_once 'inc/dbconfig.php';
require_once 'inc/config.php';
require_once 'inc/helper_user.php';
require_once 'inc/helper_fb.php';
require_once 'inc/user_class.php';
require_once 'inc/eventlog_class.php';

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
    $response = parse_signed_request($_REQUEST['signed_request'], $GLOBALS['fb_api_secret']);

    $dbh = dbStart();

    $l = new EventLog();

    $l->logSystemDebugEvent($uid, 'FB DEAUTH: [' . print_r( $response, true) . ']' );

    $uid = userFindFBUser( $response['user_id'] );

    $l->logSystemInfoEvent($uid, 'FB DEAUTH UID:['.$uid.']  FB UID:['.$response['user_id'].']' );

    if( $uid ) {            # link to an existing user
        $user = new User( $dbh, $uid );
        $user->fb_uid( 0 ); # unlink the user
        $user->fb_auth( 'xx' );
        $user->fb_auth_expire( 0 );
        $user->save();
        $msg = 'Your Facebook account has been un-linked from your FrameAlbum account.';
    } else {
        $l->logSystemInfoEvent( $uid, 'deauth: FB UID:[' . $response['user_id'] . '] - no matching FrameAlbum user found.' );
    }
    
} else {
    header('Location:/?msg=An error occured during de-authorization.');
}
?>
