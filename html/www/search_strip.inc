<div id="search_strip">
<?php
    if (isset($_REQUEST['err'])) { echo "<div class='error'>".$_REQUEST['err']."</div>\n"; }
    if (isset($_REQUEST['warn'])) { echo "<div class='warning'>".$_REQUEST['warn']."</div>\n"; }
    if (isset($_REQUEST['succ'])) { echo "<div class='success'>".$_REQUEST['succ']."</div>\n"; }
    if (isset($_REQUEST['msg'])) { echo "<div class='infomsg'>".$_REQUEST['msg']."</div>\n"; }
    if ( (isset($msg)) and (strlen($msg) > 0) ) { echo "<div class='infomsg'>".$msg."</div>\n"; }
?>
</div>
