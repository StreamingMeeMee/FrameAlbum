<?php
include_once '../inc/dbconfig.php';
include_once '../inc/config.php';
include_once '../inc/helpers.php';
include_once 'releasepre_inc.php';

    $con = mysql_connect($db_host,$db_user,$db_pass);
    @mysql_select_db($db) or die( "Unable to select database");

#-----------------------
function enumPreReg()
#-----------------------
{

    $sql = "SELECT * FROM users WHERE active='R' ORDER BY date_registered";
    $result = mysql_query($sql);

    if (!$result) {
        die("[$sql]: Invalid query: " . mysql_error());
        print '#FAIL - An error occured: ' . mysql_error();
    } else {
        print "cnt:[".mysql_num_rows( $result )."]\n";
        if (mysql_num_rows( $result ) > 0) {
            while( $row = mysql_fetch_assoc( $result ) ) {
                list ($msg, $ret) = releasePreReg($row['idusers']);
                print 'Msg;['.$msg.']  ret:['.$ret."]\n";
                sleep(2);
            }
        } else {
            print 'No pre-registered users awaiting release.';
            $ret = 0;
        }
    }

    return $ret;
}

#---------------------------
# M A I N
#---------------------------

    enumPreReg();
