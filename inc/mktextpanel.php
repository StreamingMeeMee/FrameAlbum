<?php

#------------------------------
function mkTextPanel($cid, $bcolor, $fcolor, $size, $text, $hres, $vres)
#------------------------------
{
    if (!(isset($text))) { $text = 'Not_Supplied'; }

    $fn = $GLOBALS['image_path'] . '/ch' . $cid.'-info.jpg';
    $url = $GLOBALS['image_url_root'] . '/ch' . $cid . '-info.jpg';
    $guid = 'ch' . $cid . '-info.jpg';

#    if ( !(file_exists($fn)) ) {
        $fontName = 'Helvetica';
        $fontColor = $fcolor;
        $fontSize = $size;

        # make a transparent pallete
        $pallete = new Imagick;
        $pallete->newimage($hres, $vres, $bcolor);
        $pallete->setimageformat("jpg");

        # make a draw object with settings
        $draw = new imagickdraw();
        $draw->setgravity(imagick::GRAVITY_CENTER);
        $draw->setfont("$fontName");
        $draw->setfontsize($fontSize);

        # set font color
        $draw->setfillcolor($fontColor);
        # center annotate on top of offset annotates
        $pallete->annotateImage ( $draw,0 ,0, 0, trim($text) );

        $pallete->writeImage($fn);

        $guid = 'ch' . $cid . '-info-' . time();            # set the GUID for the new panel

#    }

    if ($GLOBALS['DEBUG']) { echo 'Text panel link: [' . $url . "]\n"; }

    $sql = "INSERT INTO items (title, link, category, user_channel_id, description, pubDate, guid, media_content_url, media_thumbnail_url, media_content_duration) VALUES ('Text Panel','$url','info',$cid,'".mysql_real_escape_string($text)."',now(),'$guid','$url','$url',15)";

    $result = mysql_query($sql);
    $itemid = mysql_insert_id();
    if ($GLOBALS['DEBUG']) { echo 'Text panel id: [' . $itemid . "]\n"; }

    if (!$result) {
        die("[$sql]: Invalid query: " . mysql_error());
    }

    return array ($url, $itemid);
}

?>
