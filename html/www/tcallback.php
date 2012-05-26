<?php
require_once("phpFlickr/phpFlickr.php");
include 'inc/dbconfig.php';

$API_KEY = 'FLICKR_API_KEY';
$API_SECRET = 'FLICKR_API_SECRET';

// Create new phpFlickr object
$f = new phpFlickr($API_KEY, $API_SECRET);
$f->enableCache(
    "db",
    "mysql://$db_user:$db_pass@$db_host/$db"
);


    if (empty($_GET['frob'])) {
        $f->auth('read');
    } else {
        $f->auth_getToken($_GET['frob']);
        echo 'frob:['.$_GET['frob'].']<br>';
        echo 'extra:['.$_GET['extra'].']<br>';
        echo 'id:['.$_SESSION['uid'].']<br>';
    }

$token = $f->auth_checkToken();
 
// Find the NSID of the authenticated user
$nsid = $token['user']['nsid'];
$username = $token['user']['username'];

    echo 'nsid:['.$nsid.']<br>';
    echo 'username:['.$username.']<br>';
 
// Get the friendly URL of the user's photos
$photos_url = $f->urls_getUserPhotos($nsid);
 
// Get the user's first 36 public photos
$photos = $f->photos_search(array("user_id" => $nsid, "per_page" => 36));
 
// Loop through the photos and output the html
$i=0;
foreach ((array)$photos['photo'] as $photo) {
    echo "<a href=$photos_url$photo[id]>";
    echo "<img border='0' alt='$photo[title]' ".
        "src=" . $f->buildPhotoURL($photo, "Square") . ">";
    echo "</a>";
    $i++;
    // If it reaches the sixth photo, insert a line break
    if ($i % 6 == 0) {
        echo "<br>\n";
    }
}
 
?>
