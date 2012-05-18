<?php
#---------------------------
# set_frame_activation.php - Assign activation to codes to frames that are missing them.
#
# 2011-nov-9 - TimC
#   - First go
#--------------------------

require_once 'inc/dbconfig.php';
require_once 'inc/config.php';
require_once 'inc/helper_frame.php';
require_once 'inc/helpers.php';

#---------------------------
# M A I N
#---------------------------

    $opts = getopt("d");
    if (isset($opts['d'])) { $GLOBALS['DEBUG'] = 1; }
    if ( (isset($GLOBALS['DEBUG'])) and ($GLOBALS['DEBUG']) ) { $GLOBALS['MSG_PRINT_THRESHOLD'] = MSG_DEBUG; }
    SysMsg(MSG_DEBUG, 'DEBUG mode is set.');

    dbStart();

    $msg = '';

    $sql = "SELECT * FROM frames WHERE activation_key IS NULL AND frame_id!=''";

    $result = mysql_query($sql);
    if (!$result) {
        die("[$sql]: Invalid query: " . mysql_error());
        $msg = '#FAIL - An error occured.';
    } else {
        if (mysql_num_rows( $result ) > 0) {

            while( $row = mysql_fetch_assoc( $result ) ) {
                $fid = $row['idframes'];
                $key = frameGenActivationKey();
                SysMsg(MSG_INFO, 'Key for ['.$fid.']  is:['.$key.']');
                $sql = "UPDATE frames SET activation_key='$key' WHERE idframes=$fid";
                echo $sql . "\n";
                if ($GLOBALS['DEBUG'] != 1) {
                    $r = mysql_query($sql);
                    if (!$r) {
                        die("[$sql]: Invalid query: " . mysql_error());
                    }
                }

                $fn = $GLOBALS['image_path'] . '/'. $row['frame_id'].'-info.jpg';
                if ( file_exists($fn) ) {
                    SysMsg(MSG_INFO, 'removing existing info. panel ['.$fn.'] to force rebuild...');
                    if ($GLOBALS['DEBUG'] != 1) { unlink($fn); }
                }


            }
        } else {
            $msg = 'No frames missing activation key.';
        }
    }

?>
