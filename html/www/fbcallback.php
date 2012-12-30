<?php
include_once 'inc/config.php';

#---------------------------
function doGET($cd, $er, $er_rn, $er_desc)
#---------------------------
{
    $msg = '';
    $html = '';
    $redir = '';

    $html .= 'Code:['.$cd.']<br>';
    $html .= 'Er:['.$er.']<br>';
    $html .= 'Rn:['.$er_rn.']<br>';
    $html .= 'Desc:['.$er_desc.']<br>';

    return array ($msg, $html, $redir);
}

#---------------------------
function doPOST()
#---------------------------
{
    $msg = '';
    $html = '';
    $redir = '';

    return array ($msg, $html, $redir);
}

#---------------------------
# M A I N
#---------------------------
    if (isset($_REQUEST['error_reason'])) { $er_rn=$_REQUEST['error_reason']; } else { $er_rn = ''; }
    if (isset($_REQUEST['error'])) { $er=$_REQUEST['error']; } else { $er='';}
    if (isset($_REQUEST['error_description'])) { $er_desc=$_REQUEST['error_description']; } else { $er_desc = ''; }
    if (isset($_REQUEST['code']))   { $cd=$_REQUEST['code']; } else { $cd=''; }
    $errs = 0;
    $body = '';
    $redir = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        list ($msg, $body, $redir) = doPOST($fid);
    } else {
        list ($msg, $body, $redir) = doGET($cd, $er, $er_rn, $er_desc);
    }

    if ( strlen($redir) > 0 ) {
        header('Location: ' . $redir);
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>FrameAlbum (beta) - A FrameChannel service replacement</title>
<link href="<?php echo $GLOBALS['static_url_root'].'/' ?>style.css" rel="stylesheet" type="text/css" />
</head>

<body>
<?php echo $body; ?>
</body>
</html>
