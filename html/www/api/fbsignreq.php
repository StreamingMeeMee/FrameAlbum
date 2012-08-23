<?php

include_once 'inc/dbconfig.php';
include_once 'inc/config.php';
include_once 'inc/helpers.php';
include_once 'inc/helper_user.php';

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
  echo '<p>signed_request contents:</p>';
  $response = parse_signed_request($_REQUEST['signed_request'], $GLOBALS['fb_api_secret'] );
  echo '<pre>';
  print_r($response);
  echo '</pre>';

    echo '<pre>';
    echo 'name:[' . $response['registration']['name'] . ']';
    echo '</pre>';

} else {
  echo '$_REQUEST is empty';
}
?>
