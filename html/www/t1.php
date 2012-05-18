<?php
$req_url = 'http://www.flickr.com/services/oauth/request_token';
$authurl = 'https://fireeagle.yahoo.net/oauth/authorize';
$acc_url = 'https://fireeagle.yahooapis.com/oauth/access_token';
$api_url = 'https://fireeagle.yahooapis.com/api/0.1';
$conskey = '609d3fc00119cea82d2e91552f20a5cc';
$conssec = '4dfee5f5dacff577';

session_start();

// In state=1 the next request should include an oauth_token.
// If it doesn't go back to 0
if(!isset($_GET['oauth_token']) && $_SESSION['state']==1) $_SESSION['state'] = 0;
try {
  $oauth = new OAuth($conskey,$conssec,OAUTH_SIG_METHOD_HMACSHA1,OAUTH_AUTH_TYPE_URI);
  $oauth->enableDebug();
  if(!isset($_GET['oauth_token']) && !$_SESSION['state']) {
    $request_token_info = $oauth->getRequestToken($req_url);
    $_SESSION['secret'] = $request_token_info['oauth_token_secret'];
    $_SESSION['state'] = 1;
    header('Location: '.$authurl.'?oauth_token='.$request_token_info['oauth_token']);
    exit;
  } else if($_SESSION['state']==1) {
    $oauth->setToken($_GET['oauth_token'],$_SESSION['secret']);
    $access_token_info = $oauth->getAccessToken($acc_url);
    $_SESSION['state'] = 2;
    $_SESSION['token'] = $access_token_info['oauth_token'];
    $_SESSION['secret'] = $access_token_info['oauth_token_secret'];
  } 
  $oauth->setToken($_SESSION['token'],$_SESSION['secret']);
  $oauth->fetch("$api_url/user.json");
  $json = json_decode($oauth->getLastResponse());
  print_r($json);
} catch(OAuthException $E) {
  print_r($E);
}
?>
