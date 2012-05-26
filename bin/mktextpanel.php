<?php
#------------------------------------
# 2011-sept-18 - TimC
#   - modify for new dir. structure
#   - use dbStart()
#
#------------------------------------
include_once 'inc/dbconfig.php';
include_once 'inc/config.php';
include_once 'inc/helpers.php';
include_once 'inc/mktextpanel.php';

$GLOBALS['DEBUG'] = 1;

$CHAN_TYPE = 3;

#---------------------------
# M A I N
#---------------------------

    dbStart();

    $msg = '';

    $sql = "SELECT * FROM user_channels WHERE channel_type_id=$CHAN_TYPE AND active='Y'";

    $result = mysql_query($sql);
    if (!$result) {
        die("[$sql]: Invalid query: " . mysql_error());
        $msg = '#FAIL - An error occured.';
    } else {
        if (mysql_num_rows( $result ) > 0) {
            while( $row = mysql_fetch_assoc( $result ) ) {
                $cid = $row['iduserchannels'];
                $sql = "DELETE from items WHERE user_channel_id=$cid";
                $r = mysql_query($sql);
                if (!$r) {
                    die("[$sql]: Invalid query: " . mysql_error());
                }
                $cid = $row['iduserchannels'];
                $attribs = preg_split("/\|/", $row['attrib']);
                
                mkTextPanel($cid, $attribs[0], $attribs[1], $attribs[2], wordwrap($attribs[3], 45), 800, 480);

                $sql = "UPDATE user_channels SET last_updated=now() WHERE iduserchannels=$cid";
                $r = mysql_query($sql);
                if (!$r) {
                    die("[$sql]: Invalid query: " . mysql_error());
                }
            }
        } else {
            $msg = 'No active text panel channels.';
        }
    }

    echo $msg;
?>
