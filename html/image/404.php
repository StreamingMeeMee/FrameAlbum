<?php

    header('Content-Type: image/jpeg');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize( '404.jpg' ));

    readfile( '404.jpg' );

?>

    
