use Image::Magick;

require '../inc/config.inc';

#------------------------------
sub mkTextPanel($$$$$$$)
#------------------------------
{
my $cid = shift;
my $bcolor = shift;
my $fcolor = shift;
my $fsize = shift;
my $text = shift;
my $hres = shift;
my $vres = shift;

    if (!($text)) { $text = 'Not_Supplied'; }

    $fn = $GLOBALS{'image_path'} . '/ch' . $cid.'-test.jpg';
    $url = $GLOBALS{'image_url_root'} . '/ch' . $cid . '-test.jpg';
    $guid = 'ch' . $cid . '-info.jpg';

#    if ( !(file_exists($fn)) ) {
        $fontName = 'Helvetica';

        # make a new image
        $image = Image::Magick->new;
        $image->Set(size=>$hres.'x'.$vres);
        $image->Set(background=>$bcolor);

        # make a draw object with settings
#        $draw = new imagickdraw();
#        $draw->setgravity(imagick::GRAVITY_CENTER);
#        $draw->setfont("$fontName");
        $image->Draw(primitive=>'text', font=>$fontName, fill=>$fontColor, pointsize=>$fontSize, text=>$text);

        # set font color
#        $draw->setfillcolor($fontColor);
        # center annotate on top of offset annotates
#        $pallete->annotateImage ( $draw,0 ,0, 0, $text );

        $image->Write($fn);
#    }

    if ($GLOBALS{'DEBUG'}) { print 'Text panel link: [' . $url . "]\n"; }

#    $sql = "INSERT INTO items (title, link, category, user_channel_id, description, pubDate, guid, media_content_url, media_thumbnail_url, media_content_duration) VALUES ('Text Panel','$url','Text Panel',$cid,'".mysql_real_escape_string($text)."',now(),'$guid','$url','$url',15)";

#    $result = mysql_query($sql);
#    $itemid = mysql_insert_id();
#    if ($GLOBALS['DEBUG']) { echo 'Text panel id: [' . $itemid . "]\n"; }

#    if (!$result) {
#        die("[$sql]: Invalid query: " . mysql_error());
#    }

    return ($url, $itemid);
}

1;
