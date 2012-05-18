#!/usr/bin/perl -w
#----------------------------------------
# getflickr.pl - Get items from a Flickr feed
#
# 2011-jun-16 - TimC
#   - First go
#
# 2011-aug-2 - TimC
#   - Display username/email in failed lookup messages
#   - Check for valid $thing->{url_l} before assinging it to DESC
#
# 2011-aug-6 - TimC
#   - '58986772@N00' is not an email address - duh
#
# 2011-aug-18 - TimC
#   - Update user_channels.status with fail messaging
#
# 2011-aug-28 - TimC
#   - Convert to use dbStart();
#   - Remove some dead code
#
# 2011-aug-29 - TimC
#   - Change item.category from 'Flickr' to 'photo'
#   - if no pubDate is specified on photo use time().
#   - Observe "attrib_valid !='Y'"
#   - Set attrib_valid appropriately
#
# 2011-nov-5 - TimC
#   - send metrics to leftronic
#
# DEPRECATED IN FAVOR OF getflickr2.pl
#----------------------------------------
use Flickr::API2;
use POSIX qw( strftime );
use Data::Dumper;
#use HTML::Entities;
use Getopt::Std;
#use FileHandle;
#use Digest::MD5 qw(md5_hex);

use DBI;

use strict;

require "inc/dbconnect.pl";
require "inc/helpers.pl";

#----------------------------------
our $PROGRAMNAME = 'getFlickr';       # Name of calling app
our $PROGRAMOWNER = 'tim@frontierdigital.com';
our $VERSIONSTRING = 'v2011-aug-29';

our $DEBUG = 0;

our $MSG_DEBUG = 5;
our $MSG_VERBOSE = 4;
our $MSG_INFO = 3;
our $MSG_WARN = 2;
our $MSG_ERR = 1;
our $MSG_CRIT = 99;     # CRIT messages will trigger a DIE() call!


## MSG_PRINT_THRESHOLD - Print messages of this severity and higher
our $MSG_PRINT_THRESHOLD = $MSG_INFO;
if ($DEBUG) { $MSG_PRINT_THRESHOLD = $MSG_DEBUG; }

## OPS_ALERT_THRESHOLD - Send alert to OPS_EMAIL for messages of this severity and higher
our $OPS_ALERT_THRESHOLD = $MSG_WARN;

## OPS_EMAIL - Where to send alerts
our $OPS_EMAIL = $PROGRAMOWNER;

## OPS_EMAIL_SUBJ - What is the subject of the email to ops
our $OPS_EMAIL_SUBJ = '';
if ($PROGRAMNAME) {
  $OPS_EMAIL_SUBJ = "$PROGRAMNAME has encountered a problem";
} else {
  $OPS_EMAIL_SUBJ = "A problem has been encountered.";
}

#----------------------------------
# G L O B A L S
#----------------------------------
my $API_KEY = 'API_KEY_GOES_HERE'
my $API_SECRET = 'API_KEY_SECRET_GOES_HERE';

my $BUDDY_ICON_URL = 'http://farm1.static.flickr.com/73/buddyicons/65966179@N00.jpg?1151161573#65966179@N00';
my $PHOTOSTREAM_URL = 'http://www.flickr.com/photos/streamingmeemee/';

my $LISC_URL =  'http://creativecommons.org/licenses/by-nc-nd/2.0/deed.en';

my $USER_EMAIL = '';

my $TTL = 2;

my $START_AGE = 7;
my $MAX_AGE = (365 * 5);
my $MAX_ITEMS = 100;

my %PHOTOS;                     # key is photo.id.  If present it indicates that this photo has already been added to this feed.

our $dbh;
my $sth_add_items;
my $sth_add_frame_items;
my $sth_touch_user_chan;
my $sth_clear_chan_items;
my $sth_find_item;

#---------------------------------
# S U B S
#---------------------------------

#---------------------------
sub rssFlickrEntries($$$)
#---------------------------
{
my $photos = shift;         # is a ref. to the photos array
my $chan_id = shift;
my $item_limit = shift;

my $num_pages = 0;
my $cnt = 1;
my $desc = '';
my $pubDate;
my $guid;
my $id = 0;
my $info;

my $thing2;

    $cnt = (keys(%PHOTOS));     # how many do we have so far?

    SysMsg($MSG_DEBUG, "rssFlickrEntries: ChanID:[$chan_id]  limit:[$item_limit]  current:[$cnt]");

    unless ($sth_add_items) {
        $sth_add_items = $dbh->prepare("INSERT INTO items (title, link, category, user_channel_id, description, pubDate, guid, media_content_url, media_thumbnail_url, media_content_duration) VALUES (?,?,?,?,?,?,?,?,?,?)");
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

    foreach my $thing ( @$photos ) {
        if ($cnt >= $item_limit) {
            SysMsg($MSG_INFO, "Item limit reached - skipping the rest of the items.");
            last;
         }
        SysMsg($MSG_DEBUG, 'Adding to channel - ID:[' . $thing->{id} . ']');

        if (defined $PHOTOS{$thing->{id}} ) {
            SysMsg($MSG_DEBUG, 'Duplicate photo detected - ID:['. $PHOTOS{ $thing->{id} } . "] -- skipping.");
            next;
        }

        $PHOTOS{ $thing->{id} }++;

        $info = $thing->info;
#        SysMsg($MSG_DEBUG, Dumper $info);

        if ( $info->{photo}->{description}->{_content} ) { 
            $desc = '<p>' . $info->{photo}->{description}->{_content} . '</p>';
        } elsif ( $thing->{url_l} ) {
            $desc = '<img src="' . $thing->{url_l} . '" />';
        } else {
            $desc = '';
        }

        $info->{photo}->{title}->{_content} =~ s/\&/and/gi;

        SysMsg($MSG_DEBUG, 'Title:['. $info->{photo}->{title}->{_content} . ']  Views:['.$info->{photo}->{views} .']  Desc:['.$desc.']');

        my $url = 'http://farm' . $info->{photo}->{farm} . '.static.flickr.com/' . $info->{photo}->{server} . '/' . $info->{photo}->{id} . '_' . $info->{photo}->{secret} . '.jpg';

        my $thumb_url = $thing->{url_s};
        if ($thumb_url) { $thumb_url =~ s/https:/http:/; }

        if (defined $info->{photo}->{dateuploaded}) {
            $pubDate = strftime('%a, %d %b %Y %H:%M:%S PST', localtime( $info->{photo}->{dateuploaded} ) );
        } else {
            $pubDate = strftime('%a, %d %b %Y %H:%M:%S PST', localtime( time() ) );
        }

        SysMsg($MSG_DEBUG, 'url:[' . $url . ']  pub:[' . $pubDate . ']'); 

        $guid = sprintf("%08d-%s", $chan_id, $thing->{id});
        $sth_find_item->execute($guid) or  print "Unable to execute items SELECT statement: " . $dbh->errstr;

        if ($sth_find_item->rows() == 0) {
            $sth_add_items->execute($info->{photo}->{title}->{_content},        # title
#                $thing->{url_l},                # link
                $url,                           # link
                'photo',                       # category
                $chan_id,                       # channel_id
                $desc,                          # description
                $pubDate,                       # pubDate
                $guid,                          # guid
#                $thing->{url_l},                # media_content_url
                $url,                           # media_content_url
                $thumb_url,                     # media_thumbnail_url
                10)                             # media_content_duration
                or  SysMsg($MSG_CRIT, "Unable to execute items INSERT statement: " . $dbh->errstr);

            $id = $dbh->last_insert_id(undef, undef, qw(items iditems));
        } else {
            ($id) = $sth_find_item->fetchrow_array() or  print "Unable to fetchrow items SELECT statement: " . $dbh->errstr;
        }

        $cnt++;
    }

    return $cnt;
}

#----------------------------
sub rssFlickrRecent($$$$$$)
#----------------------------
{
my $api = shift;
my $nsid = shift;
my $max_age = shift;
my $tags = shift;
my $chan_id = shift;
my $item_limit = shift;

my $min_upload_time = (time() - ($max_age * (24*60*60)) );
my $cnt = 0;
my @photos;

    SysMsg($MSG_DEBUG, 'Item Limit:[' . $item_limit . "] nsid:[".$nsid."]  age:[".$max_age.']');

    if (($tags) && length($tags) > 0) {
        @photos = $api->photos->search(user_id=>$nsid,tags=>$tags,
                    'min_upload_date'=>$min_upload_time, 'extras'=>'date_upload,date_taken,owner_name,url_t,url_s,url_m,url_l,path_alias,geo,url_z,views', 'safe_search'=>1                   );
    } else {
        @photos = $api->photos->search(user_id=>$nsid, 
                    'min_upload_date'=>$min_upload_time, 'extras'=>'date_upload,date_taken,owner_name,url_t,url_s,url_m,url_l,path_alias,geo,url_z,views', 'safe_search'=>1                   );
    }

#    print Dumper @photos;

    SysMsg($MSG_DEBUG, 'rssFlickrRecent:Found: [' . @photos . "] matching photos");

    $cnt = rssFlickrEntries( \@photos, $chan_id, $item_limit );

    return $cnt;            # return number of photos added to feed
}

#----------------------------------
# M A I N
#----------------------------------
my $api = new Flickr::API2({'key'=>$API_KEY, 'secret'=>$API_SECRET});

my %opts=();

my $min_upload_time = (time() - ($MAX_AGE * (24*60*60)) );
my $resp;
my $fid = 0;
my $cid = 0;
my $email = '';
my $item_limit = 0;
my $sth;
my $row;
my $lastcnt = -1;
my $age = 0;
my $user;
my $st = time();
my $et = 0;
my $chn_cnt = 0;
my $item_cnt = 0;
my $max_items = 0;

    getopts("d",\%opts);          # Get CLI options

    if (defined $opts{d}) { $DEBUG = 1; }                     # Debug option?

    if ($DEBUG) {
        $resp = $api->test->echo(reply=>"this is an echo test.");

        unless ($resp->{stat} eq 'ok') {
            SysMsg($MSG_ERR, "Echo test FAILED." . Dumper $resp);
        } else {
            SysMsg($MSG_DEBUG, 'Echo test successful.');
        }
    }

    dbStart();

    $sth_touch_user_chan = $dbh->prepare("UPDATE user_channels SET last_updated=now(), status=?, attrib_valid=? WHERE iduserchannels=?");
    if (!defined $sth_touch_user_chan) {
        SysMsg($MSG_CRIT, "Unable to prepare user_channels UPDATE statement: " . $dbh->errstr);
        exit 1;
    }

    $sth_clear_chan_items = $dbh->prepare("DELETE from items WHERE user_channel_id=?");
    if (!defined $sth_clear_chan_items) {
        SysMsg($MSG_CRIT, "Unable to prepare frame_items DELETE statement: " . $dbh->errstr);
        exit 1;
    }

    $sth = $dbh->prepare("SELECT * FROM user_channels WHERE channel_type_id=1 AND active='Y' AND attrib_valid!='N'");
    if (!defined $sth) {
        SysMsg($MSG_CRIT, "Unable to prepare reports SELECT statement: " . $dbh->errstr);
        exit 1;
    }

    $sth->execute() or
        SysMsg($MSG_CRIT, "Unable to execute user_channels SELECT statement: " . $dbh->errstr);

    while ( $row = $sth->fetchrow_hashref() ) {
        $age = $START_AGE;                                # reset for next loop
        %PHOTOS = ();
        $lastcnt = -1;
        $chn_cnt++;

        my @attribs = split(/\|/, $$row{'attrib'});
        unless ($attribs[1]) { $attribs[1] = ''; }

        $user = undef;
        if ($attribs[0] =~ /\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/gi ) {
            SysMsg($MSG_DEBUG, "Lookup email:[".$attribs[0].']'); 
            eval { $user = $api->people->findByEmail($attribs[0]); }; warn "findByEmail($attribs[0]) failed: $@\n" if $@;
        } else {
            SysMsg($MSG_DEBUG, "Lookup Username:[".$attribs[0].']');
            eval { $user = $api->people->findByUsername($attribs[0]);  }; warn "findByUsername($attribs[0]) failed: $@\n" if $@;
        }

        $sth_clear_chan_items->execute($$row{'iduserchannels'})
            or SysMsg($MSG_CRIT, "Unable to execute items DELETE statement: " . $dbh->errstr);

        if ($user) {                        # did we find them?
            SysMsg($MSG_INFO, 'ChanID:['.$$row{'iduserchannels'}.'] Username:[' . $user->{username} . ']  NSID:['. $user->{NSID} . "]  tags:[".$attribs[1].']');

            if ( ($$row{'item_limit'}) && ($$row{'item_limit'} > 0) ) { $item_limit = $$row{'item_limit'}; } else { $item_limit = $MAX_ITEMS; }
            SysMsg($MSG_DEBUG, 'User Chan Item Limit:[' . $item_limit . ']');

            do {
                rssFlickrRecent($api, $user->{NSID}, $age, $attribs[1], $$row{'iduserchannels'}, $item_limit);

                SysMsg($MSG_DEBUG, "After 'recent' there are " . keys(%PHOTOS) . ' photos in the feed.');

                $age *= 2;

                if ( ($lastcnt == (keys(%PHOTOS))) && ($age > $MAX_AGE) ) { next; }          # prevent looping if we never reach the item limit
                $lastcnt = (keys(%PHOTOS));
            } while ( keys(%PHOTOS) < $item_limit);

            SysMsg($MSG_INFO, 'Found ' . keys(%PHOTOS) . ' photos for this channel.');
            $item_cnt += keys(%PHOTOS);
            $max_items = (keys(%PHOTOS) > $max_items) ? keys(%PHOTOS) : $max_items;

            SysMsg($MSG_INFO, 'Found ' . keys(%PHOTOS) . ' photos for this channel.');
            $sth_touch_user_chan->execute('Last update found ' . keys(%PHOTOS) . ' photos for this channel.',$$row{'iduserchannels'}, 'Y')
                or  SysMsg($MSG_CRIT, "Unable to execute user_channels UPDATE statement: " . $dbh->errstr);
        } else {
            SysMsg($MSG_INFO, 'Unable to find a Flickr user matching:['.$attribs[0].'] - skipping.');
            $sth_touch_user_chan->execute('Unable to find a matching Flickr user.', $$row{'iduserchannels'}, 'N')
                or  SysMsg($MSG_CRIT, "Unable to execute user_channels UPDATE statement: " . $dbh->errstr);
        }
    }

    SysMsg($MSG_INFO, $chn_cnt . ' channels were loaded with ' . $item_cnt . ' items; average of '. ($item_cnt/$chn_cnt) . ' per channel; max was ' . $max_items . '.');
    $et = (time() - $st);
    SysMsg($MSG_INFO, 'Elapsed time: ' . $et . 's  Time per channel: ' . ($et / $chn_cnt) . "s");

    my $cmd = "curl -k -i -X POST -d '" . '{"accessKey": "SmQC4vt62IyzBuDPzrzi", "streamName": "getflickr_channels", "point": ' .$chn_cnt . "}' https://beta.leftronic.com/customSend/";
    SysMsg($MSG_INFO, 'CMD:['.$cmd.']');
    system $cmd;

    $cmd = "curl -k -i -X POST -d '" . '{"accessKey": "SmQC4vt62IyzBuDPzrzi", "streamName": "getflickr_photos", "point": ' .$item_cnt . "}' https://beta.leftronic.com/customSend/";
    SysMsg($MSG_INFO, 'CMD:['.$cmd.']');
    system $cmd;

    $cmd = "curl -k -i -X POST -d '" . '{"accessKey": "SmQC4vt62IyzBuDPzrzi", "streamName": "getflickr_timeperchannel", "point": ' .($et / $chn_cnt) . "}' https://beta.leftronic.com/customSend/";
    SysMsg($MSG_INFO, 'CMD:['.$cmd.']');
    system $cmd;

    exit 0;
