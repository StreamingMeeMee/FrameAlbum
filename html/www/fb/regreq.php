<?php
require_once 'inc/dbconfig.php';
require_once 'inc/config.php';
require_once 'inc/helper_user.php';
require_once 'inc/user_class.php';
require_once 'inc/eventlog_class.php';

#--------------------------------
function parse_signed_request($signed_request, $secret) {
#--------------------------------
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

#---------------------------------
function base64_url_decode($input) {
#---------------------------------
    return base64_decode(strtr($input, '-_', '+/'));
}

#---------------------------------
# M A I N
#---------------------------------
if ($_REQUEST) {

    $dbh = dbStart();

    $log = new EventLog( );

    $response = parse_signed_request($_REQUEST['signed_request'], $GLOBALS['fb_api_secret']);

    $log->logSystemDebugEvent( 0, 'RegReq: [' . print_r( $response, TRUE ) . ']' );

    $log->logSystemDebugEvent( 0, 'RegReq: Searching for match:['. $response['registration']['email'] . ']' );
    $uid = userFindEmail( $response['registration']['email'] );
    $log->logSystemDebugEvent( 0, 'found UID:[' . $uid . ']' );

    if( $uid ) {            # link to an existing user
        $user = new User( $dbh, $uid );

#echo '<pre>response:['. print_r($response, true) . ']</pre>';

        $user->active( 'Y' );
        $user->fb_uid( $response['user_id'] );
        $user->fb_auth( $response['oauth_token'] );
        $user->fb_auth_expire( $response['expires'] );
        $user->save();
        $msg = 'Your Facebook login has been linked to an existing FrameAlbum account with the same email address.';
        $log->logSystemInfoEvent( $uid, 'FA user:[' . $uid . '] linked to FB UID:[' . $response['user_id'] . ']');
    } else {
        $user = new User( $dbh, 0, $response['registration']['name'], $response['registration']['email'] );
        $user->active( 'Y' );;
        $user->fb_uid( $response['user_id'] );
        $user->fb_auth( $response['oauth_token'] );
        $user->fb_auth_expire( $response['expires'] );
        $user->save();
        $msg = 'Your FrameAlbum account has been created.  Your username is "' . $user->username . "'.";;
        $log->logSystemInfoEvent( $uid, 'FB UID:[' . $user->fb_uid() . '] added to FA as [' . $user->username . ']');
    }

    if (session_id() == '') { session_start(); }

    $_SESSION['username'] = $user->username();
    $_SESSION['uid'] = $user->iduser();
    $_SESSION['useremail'] = $user->email();
    $_SESSION['isadmin'] = $user->isAdmin();
    $_SESSION['loggedin'] = 'Y';

    header('Location:/usermain.php?msg=' . $msg);

} else {
    header('Location:/?msg=An error occured during registration.');
}
?>
