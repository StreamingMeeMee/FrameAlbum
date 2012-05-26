<?php
#---------------------------
function prepDBVal($val)
#---------------------------
{
    $val = stripslashes($val);
    $val = mysql_real_escape_string($val);

    return $val;
}

?>
