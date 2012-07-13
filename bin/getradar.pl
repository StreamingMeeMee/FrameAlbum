#!/usr/bin/perl -w
#----------------------------------------
# getradar.pl - Get a weather radar image from Wunderground
#
# 2011-aug-28 - TimC
#   - First Go
#   - ZIP code regex found at www.virtuosimedia.com/dev/php/37-tested-php-perl-and-javascript-regular-expressions+perl+verify+zip+regex
#
# 2011-sept-12 - TimC
#   - Add support for NWS radar images -- the quality of the WU images are not... umm... good.
#
# 2011-sept-17 - TimC
#   - Add epoch time to guid value to address refresh issue on Kodak frames
#   - Don't 'clear' the channel -- update existing entry
#
# 2011-dec-18 - TimC
#   - Move API key to config. include as $GLOBALS{'wu_api_key'} (Bug #40)
#
# 2012-jul-6 - TimC
#   - fix quoting of $PROGRAMOWNER email
#
# 2012-jul-13 - TimC
#   - Make sure channel image directory exists before putting files there - DOH!
#----------------------------------------
use POSIX qw( strftime );
use Data::Dumper;
use Getopt::Std;
use LWP::UserAgent;
use URI::Escape;
use XML::Simple;
use Image::Magick;
use DateTime;
use DBI;
use File::Util;

use strict;

require "inc/helpers.pl";
require "inc/dbconfig.inc";
require "../inc/config.inc";

#----------------------------------
our $PROGRAMNAME = 'getRadar';
our $VERSIONSTRING = 'v2012-jul-13';
my $PROGRAMOWNER = 'user@email.com';
$! = 1;

our $DEBUG = 0;

our $CHAN_TYPE = 10;	# channel_type_id of this grabber

our $MSG_DEBUG = 5;
our $MSG_VERBOSE = 4;
our $MSG_INFO = 3;
our $MSG_WARN = 2;
our $MSG_ERR = 1;
our $MSG_CRIT = 99;     # CRIT messages will trigger a DIE() call!

## MSG_PRINT_THRESHOLD - Print messages of this severity and higher
our $MSG_PRINT_THRESHOLD = $MSG_INFO;
if ($DEBUG) { $MSG_PRINT_THRESHOLD = $MSG_DEBUG; }  # force during debug mode

our $EMAIL_FROM = $PROGRAMOWNER;
our $EMAIL_TO = $PROGRAMOWNER;
if ($DEBUG) { $EMAIL_TO = $PROGRAMOWNER; }
our $BCC_EMAIL_ADDR = '';
unless ($DEBUG) { $BCC_EMAIL_ADDR = $PROGRAMOWNER; }

#----------------------------------
# G L O B A L S
#----------------------------------
my $TTL = 2;

my $MAX_ITEMS = 1;

our $dbh;

our %GLOBALS;

my $sth_add_items;
my $sth_add_frame_items;
my $sth_touch_user_chan;
my $sth_clear_chan_items;
my $sth_find_item;
my $sth_upd_items;

my $UA = LWP::UserAgent->new;
#---------------------------------
# S U B S
#---------------------------------
#---------------------------
sub getRadarStation($)
#---------------------------
{
my $loc = shift;

my $api_url ='';
my $rsp;
my $xs = XML::Simple->new;
my $xml;
my $ret = '';
my $tz = 'GMT';

    SysMsg($MSG_DEBUG, "getRadarStation: loc:[$loc]");

    $api_url = 'http://api.wunderground.com/auto/wui/geo/GeoLookupXML/index.xml?query=' . uri_escape($loc);
    SysMsg($MSG_DEBUG, 'API request:['.$api_url.']');

    $rsp = $UA->get($api_url);
    if ($rsp->code == 200) {

        eval { $xml = $xs->XMLin($rsp->content); }; SysMsg($MSG_INFO, 'Error during getRadarStation() XML parsing:' . $@);

        SysMsg($MSG_DEBUG, Dumper($xml));
        SysMsg($MSG_DEBUG, 'Radar URL:['.$xml->{'radar'}->{'url'}.']');
        if (defined($xml->{'radar'}->{'url'})) {
            $xml->{'radar'}->{'url'} =~ /p?ID=(.*)&regi/gi;
            $ret = $1;
		    $tz = $xml->{'tz_unix'};
            SysMsg($MSG_DEBUG, 'Station:['.$ret.']  timezone:['.$tz.']');
        } else {
            SysMsg($MSG_DEBUG, 'Unable to find a radar station for ['. $loc .']');
            $ret = 'NOMATCH';
        }

    } else {
        $ret = '';
    }

    return ($ret, $tz);
}

#---------------------------
sub grabNWSRadar($$$$)
#---------------------------
{
my $chan_id = shift;
my $zip = shift;
my $stat = shift;
my $item_limit = shift;

my $url = '';
my $rsp;
my $image;
my $over;
my $fn = '';
my $tz;
my $id;
my $isradar = 0;

    SysMsg($MSG_DEBUG, "grabNWSRadar: ChanID:[$chan_id]  ZIP:[$zip]  limit:[$item_limit]");

    unless ($sth_add_items) {
        $sth_add_items = $dbh->prepare("INSERT INTO items (title, link, category, user_channel_id, description, pubDate, guid, media_content_url,
             media_thumbnail_url, media_content_duration) VALUES (?,?,?,?,?,?,?,?,?,?)");
        if (!defined $sth_add_items) {
            SysMsg($MSG_CRIT, "Unable to prepare items INSERT statement: " . $dbh->errstr);
            exit 1;
        }
    }

    unless ($sth_upd_items) {
        $sth_upd_items = $dbh->prepare("UPDATE items SET title=?, link=?, category=?, user_channel_id=?, description=?, pubDate=?, guid=?, media_content_url=?,
             media_thumbnail_url=?, media_content_duration=? WHERE iditems=?");
        if (!defined $sth_upd_items) {
            SysMsg($MSG_CRIT, "Unable to prepare items UPDATE statement: " . $dbh->errstr);
            exit 1;
        }
    }

    unless ($sth_find_item) {
        $sth_find_item = $dbh->prepare("SELECT iditems FROM items WHERE user_channel_id=?");        # there is only one item for this channel so using the chanID is enough
        if (!defined $sth_find_item) {
            SysMsg($MSG_CRIT, "Unable to prepare frame_items SELECT statement: " . $dbh->errstr);
            exit 1;
        }
    }

    unless ($stat) { 
	    ($stat, $tz) = getRadarStation($zip);

        if ($stat eq 'NOMATCH') {
            SysMsg($MSG_INFO, 'Unable to find a radar station for [' . $zip . '] - skipping it.');
            $dbh->do("UPDATE user_channels SET attrib_valid='N' WHERE iduserchannels=$chan_id");
            return (0, 'No radar available for your US ZIP code.  Is your ZIP code valid?');
        }
    }

    $fn = $GLOBALS{'image_path'} . '/' . $CHAN_TYPE . '/' . $stat . '_Topo.jpg';
	unless (-e $fn) {
    	$url = 'http://radar.weather.gov/ridge/Overlays/Topo/Short/'.$stat.'_Topo_Short.jpg';
    	SysMsg($MSG_DEBUG, 'Topo URL:['.$url.']');
		$UA->get($url, ':content_file'=>$fn);
	}
    $image = Image::Magick->new;
    $image->Read($fn);

    $fn = $GLOBALS{'image_path'} . '/' .$CHAN_TYPE . '/' . $stat . '_Rivers.jpg';
    unless (-e $fn) {
        $url = 'http://radar.weather.gov/ridge/Overlays/Rivers/Short/'.$stat.'_Rivers_Short.gif';
        SysMsg($MSG_DEBUG, 'River URL:['.$url.']');
        $UA->get($url, ':content_file'=>$fn);
    }
    $over = Image::Magick->new;
    $over->Read($fn);
    $image->Composite(image=>$over,compose=>'over');
    undef $over;

    $fn = $GLOBALS{'image_path'} . '/' .$CHAN_TYPE . '/' . $stat . '_Boundry.jpg';
    unless (-e $fn) {
    	$url = 'http://radar.weather.gov/ridge/Overlays/County/Short/' . $stat . '_County_Short.gif';
    	SysMsg($MSG_DEBUG, 'Boundry URL:['.$url.']');
    	$UA->get($url, ':content_file'=>$fn);
	}
    $over = Image::Magick->new;
    $over->Read($fn);
    $image->Composite(image=>$over,compose=>'over');
    undef $over;

    $fn = $GLOBALS{'image_path'} . '/' . $CHAN_TYPE . '/' . $stat . '_Highways.jpg';
    unless (-e $fn) {
    	$url = 'http://radar.weather.gov/ridge/Overlays/Highways/Short/'.$stat.'_Highways_Short.gif';
    	SysMsg($MSG_DEBUG, 'Highways URL:['.$url.']');
    	$UA->get($url, ':content_file'=>$fn);
	}
    $over = Image::Magick->new;
    $over->Read($fn);
    $image->Composite(image=>$over,compose=>'over');
    undef $over;

    $url = 'http://radar.weather.gov/ridge/RadarImg/N0R/' . $stat . '_N0R_0.gif';
    $fn = $GLOBALS{'image_path'} . '/' . $CHAN_TYPE . '/' . $stat . '_N0R_0.gif';
    SysMsg($MSG_DEBUG, 'Radar URL:['.$url.']');
    $rsp = $UA->get($url, ':content_file'=>$fn);
    if ($rsp->is_success) { ;
        $over = Image::Magick->new;
        $over->Read($fn);
        $image->Composite(image=>$over,compose=>'over');
        undef $over;
        unlink $fn;

        $fn = $GLOBALS{'image_path'} . '/' . $CHAN_TYPE . '/' . $stat . '_RangeRing.gif';
        unless (-e $fn) {
            $url = 'http://radar.weather.gov/ridge/Overlays/RangeRings/Short/' . $stat . '_RangeRing_Short.gif';
            SysMsg($MSG_DEBUG, 'Range Ring URL:['.$url.']');
            $rsp = $UA->get($url, ':content_file'=>$fn);
        }
        if (-e $fn) {
            $over = Image::Magick->new;
            $over->Read($fn);
            $image->Composite(image=>$over,compose=>'over');
            undef $over;
        }
        $isradar = 1;
    } else {
        unlink $fn;
        $image->Annotate(font=>'DroidSans.ttf', pointsize=>50, fill=>'red', gravity=>'Center', x=>0, y=>-50, text=>'Radar not Available');
        $isradar = 0;
    }

    $fn = $GLOBALS{'image_path'} . '/' . $CHAN_TYPE . '/' . $stat . '_City.jpg';
    unless (-e $fn) {
    	$url = 'http://radar.weather.gov/ridge/Overlays/Cities/Short/'.$stat.'_City_Short.gif';
    	SysMsg($MSG_DEBUG, 'Cities URL:['.$url.']');
    	$UA->get($url, ':content_file'=>$fn);
	}
    $over = Image::Magick->new;
    $over->Read($fn);
    $image->Composite(image=>$over,compose=>'over');
    undef $over;

    $fn = $GLOBALS{'image_path'} . '/' . $CHAN_TYPE . '/' . $stat . '_Warn.gif';
   	$url = 'http://radar.weather.gov/ridge/Warnings/Short/'.$stat.'_Warnings_0.gif';
   	SysMsg($MSG_DEBUG, 'Warnings URL:['.$url.']');
   	$rsp = $UA->get($url, ':content_file'=>$fn);
    if ($rsp->is_success) { ;
    	$over = Image::Magick->new;
		$over->Read($fn);
    	$image->Composite(image=>$over,compose=>'over');
    	undef $over;
	} else {
        unlink $fn;
		$image->Annotate(font=>'DroidSans.ttf', pointsize=>32, fill=>'yellow', gravity=>'Center', geometry=>'0,50', text=>'Warnings not Available');
	}

    if ($isradar) {
        $url = 'http://radar.weather.gov/ridge/Legend/N0R/' . $stat . '_N0R_Legend_0.gif';
        $fn = $GLOBALS{'image_path'} . '/' . $CHAN_TYPE . '/' . $stat . '_Legend.gif';
        SysMsg($MSG_DEBUG, 'Legend URL:['.$url.']');
        $rsp = $UA->get($url, ':content_file'=>$fn);
        if ($rsp->is_success) { ;
            $over = Image::Magick->new;
            $over->Read($fn);
            $image->Composite(image=>$over,compose=>'over');
            undef $over;
            unlink $fn;
        }
    }

	my $tm = DateTime->now( time_zone=>'GMT' );
	if ($tz) { $tm->set_time_zone( $tz ); }
#    my $txt = 'Source: NWS  Generated: ' . $tm->strftime('%a %b %d %Y @ %I:%M %P %Z');
#	$image->Annotate(font=>'DroidSans.ttf', pointsize=>20, fill=>'black', gravity=>'South', geometry=>'0,0', text=>$txt);
	$image->Set(Gravity=>'Center');
	$image->Resize(y=>600);
#	$image->Extent(geometry=>'600x600');
    $fn = $GLOBALS{'image_path'} . '/' . $CHAN_TYPE . '/' . $stat . '_radar.jpg';
    SysMsg($MSG_DEBUG,'Image filename:['.$fn.']');
	$image->Write(filename=>$fn, quality=>90);

    $url = $GLOBALS{'image_url_root'} . '/' . $CHAN_TYPE . '/' . $stat . '_radar.jpg';
    SysMsg($MSG_DEBUG, 'Radar URL:['. $url . ']');

    my $thumb_url = $url;
    if ($thumb_url) { $thumb_url =~ s/https:/http:/; }

    my $link_url = $url;

    my $pubDate = strftime('%a, %d %b %Y %H:%M:%S +0:00', localtime( time() ) );
    SysMsg($MSG_DEBUG, 'pubdate:[' . $pubDate . ']');

    my $guid = sprintf("%08d-%s-%10d", $chan_id, $zip, time());             # add epoch time to GUID to make it unique on each update

    my $title = 'Weather Radar for ' . $zip;
    my $desc = $title;

    $sth_find_item->execute($chan_id)
        or SysMsg($MSG_CRIT, "Unable to execute items SELECT statement: " . $dbh->errstr);

    if ($sth_find_item->rows() == 0) {
        SysMsg($MSG_DEBUG, 'Inserting item...');
        $sth_add_items->execute($title,     # title
            $link_url,                      # link
            'Weather',                      # category
            $chan_id,                       # channel_id
            $desc,                          # description
            $pubDate,                       # pubDate
            $guid,                          # guid
            $url,                           # media_content_url
            $url,                           # media_thumbnail_url
            10)                             # media_content_duration
            or  print "Unable to execute items INSERT statement: " . $dbh->errstr;

        $id = $dbh->last_insert_id(undef, undef, qw(items iditems));
    } else {
        SysMsg($MSG_DEBUG, 'Updating item.');
        ($id) = $sth_find_item->fetchrow_array()
            or SysMsg($MSG_CRIT, "Unable to fetchrow items SELECT statement: " . $dbh->errstr);
        $sth_upd_items->execute($title,     # title
            $link_url,                      # link
            'Weather',                      # category
            $chan_id,                       # channel_id
            $desc,                          # description
            $pubDate,                       # pubDate
            $guid,                          # guid
            $url,                           # media_content_url
            $url,                           # media_thumbnail_url
            10,                             # media_content_duration
            $id)
            or  print "Unable to execute items UPDATE statement: " . $dbh->errstr;
    }

    return (1, '');
}

#---------------------------
sub grabWURadar($$$)
#---------------------------
{
my $chan_id = shift;
my $zip = shift;
my $item_limit = shift;


my $json = JSON->new->utf8;
my $api_url ='';
my $rsp;
my $rsp_text;
my $id;

    SysMsg($MSG_DEBUG, "grabWURadar: ChanID:[$chan_id]  ZIP:[$zip]  limit:[$item_limit]");

    unless ($sth_add_items) {
        $sth_add_items = $dbh->prepare("INSERT INTO items (title, link, category, user_channel_id, description, pubDate, guid, media_content_url,
			 media_thumbnail_url, media_content_duration) VALUES (?,?,?,?,?,?,?,?,?,?)");
        if (!defined $sth_add_items) {
            SysMsg($MSG_CRIT, "Unable to prepare items INSERT statement: " . $dbh->errstr);
            exit 1;
        }
    }

    unless ($sth_find_item) {
        $sth_find_item = $dbh->prepare("SELECT iditems FROM items WHERE guid=?");
        if (!defined $sth_find_item) {
            SysMsg($MSG_CRIT, "Unable to prepare frame_items SELECT statement: " . $dbh->errstr);
            exit 1;
        }
    }

    $api_url = 'http://api.wunderground.com/api/' . $GLOBALS{'wu_api_key'} . '/radar/q/' . $zip . '.json';
    SysMsg($MSG_DEBUG, 'API request:['.$api_url.']');

    $rsp = $UA->get($api_url);
    if ($rsp->code == 200) {
        $rsp_text = $json->allow_nonref->utf8->relaxed->escape_slash->loose->allow_singlequote->allow_barekey->decode( $rsp->decoded_content );

#        SysMsg($MSG_DEBUG, $json->pretty->encode($rsp_text));

        SysMsg($MSG_DEBUG, 'Radar URL:['. $rsp_text->{'radar'}->{'image_url'} . ']');

        my $url = $rsp_text->{'radar'}->{'image_url'};
        if ($url) { $url =~ s/https:/http:/; }

        my $thumb_url = $rsp_text->{'radar'}->{'image_url'};
        if ($thumb_url) { $thumb_url =~ s/https:/http:/; }

        my $link_url = $rsp_text->{'radar'}->{'url'};

        my $pubDate = strftime('%a, %d %b %Y %H:%M:%S EST', localtime( time() ) );
        SysMsg($MSG_DEBUG, 'pubdate:[' . $pubDate . ']'); 

        my $guid = sprintf("%08d-%s", $chan_id, $zip);

        my $title = 'Weather Radar for ' . $zip;
        my $desc = $title;
   
        $sth_find_item->execute($guid)
            or SysMsg($MSG_CRIT, "Unable to execute items SELECT statement: " . $dbh->errstr);
 
        if ($sth_find_item->rows() == 0) {
            SysMsg($MSG_DEBUG, 'Inserting item...');
            $sth_add_items->execute($title,     # title
                $link_url,                      # link
                'Weather',                      # category
                $chan_id,                       # channel_id
                $desc,                          # description
                $pubDate,                       # pubDate
                $guid,                          # guid
                $url,                           # media_content_url
                $url,                           # media_thumbnail_url
                10)                             # media_content_duration
                or  print "Unable to execute items INSERT statement: " . $dbh->errstr;

            $id = $dbh->last_insert_id(undef, undef, qw(items iditems));
        } else {
            SysMsg($MSG_DEBUG, 'Item exists - not added.');
            ($id) = $sth_find_item->fetchrow_array()
                or SysMsg($MSG_CRIT, "Unable to fetchrow items SELECT statement: " . $dbh->errstr);
        }

    } else {
        SysMsg($MSG_ERR, 'Unable to retrieve API response:['.$api_url.']  status:['.$rsp->code.']');
    }

    return (1, '');
}

#----------------------------------
# M A I N
#----------------------------------
my %opts=();

my $item_limit = 0;
my $sth;
my $row;
my $item_cnt = -1;

my $zip;
my $st = time();
my $et = 0;
my $chn_cnt = 0;
my $status = '';
my $stat = '';

    getopts("d",\%opts);          # Get CLI options

    if (defined $opts{d}) { $DEBUG = 1; }                     # Debug option?
	if ($DEBUG) { $MSG_PRINT_THRESHOLD = $MSG_DEBUG; }  # force during debug mode
	SysMsg($MSG_DEBUG, 'DEBUG mode enabled.');

    if ($DEBUG) {                                               # Is there a 'hello world' API test for this service?
        SysMsg($MSG_INFO, 'No echotest available for this grabber.');
    }

    unless( -e $GLOBALS{'image_path'} . '/' . $CHAN_TYPE . '/' ) {
        SysMsg($MSG_INFO, 'Image target dir, does not exists; making:[' . $GLOBALS{'image_path'} . '/' . $CHAN_TYPE . '/' . ']');
        my($f) = File::Util->new();
        unless( $f->make_dir( $GLOBALS{'image_path'} . '/' . $CHAN_TYPE . '/', 0755, '--if-not-exists' ) ) {
            SysMsg($MSG_CRIT, 'Unable to create image target directory:[' . $GLOBALS{'image_path'} . '/' . $CHAN_TYPE . '/' . ']');
        }
    }

    dbStart();

    $sth_touch_user_chan = $dbh->prepare("UPDATE user_channels SET last_updated=now(), status=? WHERE iduserchannels=?");
    if (!defined $sth_touch_user_chan) {
        SysMsg($MSG_CRIT, "Unable to prepare user_channels UPDATE statement: " . $dbh->errstr);
        exit 1;
    }

#    $sth_clear_chan_items = $dbh->prepare("DELETE from items WHERE user_channel_id=?");
#    if (!defined $sth_clear_chan_items) {
#        SysMsg($MSG_CRIT, "Unable to prepare frame_items DELETE statement: " . $dbh->errstr);
#        exit 1;
#    }

    $sth = $dbh->prepare("SELECT * FROM user_channels WHERE channel_type_id=$CHAN_TYPE AND active='Y' AND attrib_valid != 'N'");
    if (!defined $sth) {
        SysMsg($MSG_CRIT, "Unable to prepare reports SELECT statement: " . $dbh->errstr);
        exit 1;
    }

    $sth->execute() or
        SysMsg($MSG_CRIT, "Unable to execute reports SELECT statement: " . $dbh->errstr);

    while ( $row = $sth->fetchrow_hashref() ) {
        SysMsg($MSG_DEBUG, "Processing user_channel ID:[$$row{'iduserchannels'}]");
        $item_cnt = -1;

        my @attribs = split(/\|/, $$row{'attrib'});

        $zip = $attribs[0];
        if (defined($attribs[1])) { $stat = $attribs[1]; } else { $stat = ''; }

#        $sth_clear_chan_items->execute($$row{'iduserchannels'}) or die "Unable to execute items DELETE statement: " . $dbh->errstr;

        if ($zip =~ m/^([0-9]{5}(?:-[0-9]{4})?)*$/) {                        # did we find a valid ZIP?
            SysMsg($MSG_DEBUG, 'ChanID:['.$$row{'iduserchannels'}.'] ZIP:[' . $zip . ']');

            if ( ($$row{'item_limit'}) && ($$row{'item_limit'} > 0) ) { $item_limit = $$row{'item_limit'}; } else { $item_limit = $MAX_ITEMS; }
            SysMsg($MSG_DEBUG, 'User Chan Item Limit:[' . $item_limit . ']');

#            $item_cnt = grabWURadar($$row{'iduserchannels'}, $zip, $item_limit);
			($item_cnt, $status) = grabNWSRadar( $$row{'iduserchannels'}, $zip, $stat, $item_limit );
            unless ($status) { $status = 'Last update found ' . $item_cnt . ' items for this channel.'; }
            $sth_touch_user_chan->execute($status, $$row{'iduserchannels'})
                or SysMsg($MSG_CRIT, "Unable to execute user_channels UPDATE statement: " . $dbh->errstr);
        } else {
            SysMsg($MSG_INFO, 'Unable to find a ZIP code:['.$attribs[0]."] - skipping.");
            $sth_touch_user_chan->execute('Unable to find a ZIP code.', $$row{'iduserchannels'})
                or SysMsg($MSG_CRIT, "Unable to execute user_channels UPDATE statement: " . $dbh->errstr);
        }
		
		$chn_cnt++;
    }

    if( $chn_cnt > 0 ) {            # don't bother if no channels were processed and avoid /0 error
        $et = (time() - $st);
        SysMsg($MSG_INFO, 'Elapsed time: ' . $et . 's  Time per channel: ' . ($et / $chn_cnt) . "s");

        $dbh->do("INSERT INTO grabber_stats (channel_type_id, rundate, wall_time, stats) VALUES ($CHAN_TYPE, now(), $et, '" . $chn_cnt . "')")
            or SysMsg($MSG_CRIT, "Unable to execute grabber_stats INSERT statement: " . $dbh->errstr);
    }

#    $pidfile->remove();

	SysMsg($MSG_INFO, $chn_cnt . ' channels were updated in ' . $et . ' seconds.');

	exit 0;

