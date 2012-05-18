#!/usr/bin/perl -w
#----------------------------------------
# getflickr-group.pl - Get items from a Flickr group feed
#
# 2011-jun-16 - TimC
#   - First go
#   - NOT YET READY FOR PRODUCTION
#----------------------------------------
use Flickr::API2;
use POSIX qw( strftime );
use Data::Dumper;
use Getopt::Std;

use DBI;

use strict;

#----------------------------------
# G L O B A L S
#----------------------------------

my $BUDDY_ICON_URL = 'http://farm1.static.flickr.com/73/buddyicons/65966179@N00.jpg?1151161573#65966179@N00';
my $PHOTOSTREAM_URL = 'http://www.flickr.com/photos/streamingmeemee/';

my $LISC_URL =  'http://creativecommons.org/licenses/by-nc-nd/2.0/deed.en';

my $USER_EMAIL = '';

my $TTL = 2;

my $DEBUG = 1;

my $START_AGE = 7;
my $MAX_AGE = 365;
my $MAX_ITEMS = 25;
#my $MIN_PICS = 25;              # Add 30 days to MAX_AGE if we are not yet above this number of qualifing pics

my $RSSOUT;
my %PHOTOS;                     # key is photo.id.  If present it indicates that this photo has already been added to this feed.

my $dbh;
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

    if ($DEBUG) { print "rssFlickrEntries: ChanID:[$chan_id]  limit:[$item_limit]  current:[$cnt]\n"; }

    unless ($sth_add_items) {
        $sth_add_items = $dbh->prepare("INSERT INTO items (title, link, category, user_channel_id, description, pubDate, guid, media_content_url, media_thumbnail_url, media_content_duration) VALUES (?,?,?,?,?,?,?,?,?,?)");
        if (!defined $sth_add_items) {
            print "Unable to prepare items INSERT statement: " . $dbh->errstr;
            exit 1;
        }
    }

    unless ($sth_find_item) {
        $sth_find_item = $dbh->prepare("SELECT iditems FROM items WHERE guid=?");
        if (!defined $sth_find_item) {
            print "Unable to prepare frame_items SELECT statement: " . $dbh->errstr;
            exit 1;
        }
    }

    foreach my $thing ( @$photos ) {
        if ($cnt >= $item_limit) {
            if ($DEBUG) { print "Item limit reached - skipping the rest of the items.\n"; }
            last;
         }
        if ($DEBUG) { print 'Adding to channel - ID:[' . $thing->{id} . "]\n"; }

        if (defined $PHOTOS{$thing->{id}} ) {
            print 'Duplicate photo detected - ID:['. $PHOTOS{ $thing->{id} } . "] -- skipping.\n";
            next;
        }

        $PHOTOS{ $thing->{id} }++;

        $info = $thing->info;
#        if ($DEBUG) { print Dumper $info; }

        if ( $info->{photo}->{description}->{_content} ) { 
            $desc = '<p>' . $info->{photo}->{description}->{_content} . '</p>';
        } else {
            $desc = '<img src="' . $thing->{url_l} . '" />';
        }

        $info->{photo}->{title}->{_content} =~ s/\&/and/gi;

        if ($DEBUG) { print 'Title:['. $info->{photo}->{title}->{_content} . ']  Views:['.$info->{photo}->{views} .']  Desc:['.$desc."]\n"; }

        my $url = 'http://farm' . $info->{photo}->{farm} . '.static.flickr.com/' . $info->{photo}->{server} . '/' . $info->{photo}->{id} . '_' . $info->{photo}->{secret} . '.jpg';

        if (defined $info->{photo}->{dateuploaded}) {
            $pubDate = strftime('%a, %d %b %Y %H:%M:%S PST', localtime( $info->{photo}->{dateuploaded} ) );
        } else {
            $pubDate = '';
        }

        if ($DEBUG) { print 'url:[' . $url . ']  pub:[' . $pubDate . "]\n"; }

        $guid = sprintf("%08d-%s", $chan_id, $thing->{id});
        $sth_find_item->execute($guid) or  print "Unable to execute items SELECT statement: " . $dbh->errstr;

        if ($sth_find_item->rows() == 0) {
            $sth_add_items->execute($info->{photo}->{title}->{_content},        # title
#                   $thing->{url_l},                # link
                $url,                           # link
                'Flickr',                       # category
                $chan_id,                       # channel_id
                $desc,                          # description
                $pubDate,                       # pubDate
                $guid,                          # guid
#                $thing->{url_l},                # media_content_url
                $url,
                $thing->{url_s},                # media_thumbnail_url
                10)                             # media_content_duration
                or  print "Unable to execute items INSERT statement: " . $dbh->errstr;

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

    if ($DEBUG) { print 'Item Limit:[' . $item_limit . "] nsid:[".$nsid."]  age:[".$max_age."]\n"; }

    if (($tags) && length($tags) > 0) {
        @photos = $api->photos->search(user_id=>$nsid,tags=>$tags,
                    'min_upload_date'=>$min_upload_time, 'extras'=>'date_upload,date_taken,owner_name,url_t,url_s,url_m,url_l,path_alias,geo,url_z,views', 'safe_search'=>1                   );
    } else {
        @photos = $api->photos->search(user_id=>$nsid, 
                    'min_upload_date'=>$min_upload_time, 'extras'=>'date_upload,date_taken,owner_name,url_t,url_s,url_m,url_l,path_alias,geo,url_z,views', 'safe_search'=>1                   );
    }

print Dumper @photos;

    print 'rssFlickrRecent:Found: [' . @photos . "] matching photos\n";

    $cnt = rssFlickrEntries( \@photos, $chan_id, $item_limit );

    return $cnt;            # return number of photos added to feed
}

#----------------------------
sub rssFlickrGroupRecent($$$$$$)
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

    if ($DEBUG) { print 'Item Limit:[' . $item_limit . "] nsid:[".$nsid."]  age:[".$max_age."]\n"; }

    if (($tags) && length($tags) > 0) {
        @photos = $api->photos->search('group_id'=>$nsid,tags=>$tags,
                    'min_upload_date'=>$min_upload_time, 'extras'=>'date_upload,date_taken,owner_name,url_t,url_s,url_m,url_l,path_alias,geo,url_z,views', 'safe_search'=>1                   );
    } else {
        @photos = $api->photos->search('group_id'=>$nsid,
                    'min_upload_date'=>$min_upload_time, 'extras'=>'date_upload,date_taken,owner_name,url_t,url_s,url_m,url_l,path_alias,geo,url_z,views', 'safe_search'=>1                   );
    }

print Dumper @photos;

    print 'rssFlickrRecent:Found: [' . @photos . "] matching photos\n";

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

    getopts("d",\%opts);          # Get CLI options

    if (defined $opts{d}) { $DEBUG = 1; }                     # Debug option?

    if ($DEBUG) {
        $resp = $api->test->echo(reply=>"this is an echo test.");

        unless ($resp->{stat} eq 'ok') {
            print "Echo test FAILED.\n";
            print Dumper $resp;
        } else {
            print "Echo test successful.\n";
        }
    }

    $dbh = DBI->connect($dsn, $db_user_name, $db_password);
    if (!defined $dbh) {
        print "Unable to connect to the DB: " . $DBI::errstr;
        exit 1;
    }

    $sth_touch_user_chan = $dbh->prepare("UPDATE user_channels SET last_updated=now() WHERE iduserchannels=?");
    if (!defined $sth_touch_user_chan) {
        print "Unable to prepare user_channels UPDATE statement: " . $dbh->errstr;
        exit 1;
    }

    $sth_clear_chan_items = $dbh->prepare("DELETE from items WHERE user_channel_id=?");
    if (!defined $sth_clear_chan_items) {
        print "Unable to prepare frame_items DELETE statement: " . $dbh->errstr;
        exit 1;
    }

    $sth = $dbh->prepare("SELECT * FROM user_channels WHERE channel_type_id=7 AND active='Y'");
    if (!defined $sth) {
        print "Unable to prepare reports SELECT statement: " . $dbh->errstr;
        exit 1;
    }

    $sth->execute() or
        print "Unable to execute reports SELECT statement: " . $dbh->errstr;

    while ( $row = $sth->fetchrow_hashref() ) {
        $age = $START_AGE;                                # reset for next loop
        %PHOTOS = ();
        $lastcnt = -1;

        my @attribs = split(/\|/, $$row{'attrib'});
        unless ($attribs[1]) { $attribs[1] = ''; }

        $user = undef;

        $sth_clear_chan_items->execute($$row{'iduserchannels'}) or die "Unable to execute items DELETE statement: " . $dbh->errstr;

            if ($DEBUG) {
                print 'ChanID:['.$$row{'iduserchannels'}.'] NSID:['. $attribs[0] . "]  tags:[".$attribs[1]."]\n"; }


            if ( ($$row{'item_limit'}) && ($$row{'item_limit'} > 0) ) { $item_limit = $$row{'item_limit'}; } else { $item_limit = $MAX_ITEMS; }
            if ($DEBUG) { print 'User Chan Item Limit:[' . $item_limit . "]\n"; }

            do {
                rssFlickrGroupRecent($api, $attribs[0], $age, $attribs[1], $$row{'iduserchannels'}, $item_limit);

                if ($DEBUG) { print "After 'recent' there are " . keys(%PHOTOS) . " photos in the feed.\n"; }

                $age *= 2;

                if ( ($lastcnt == (keys(%PHOTOS))) && ($age > $MAX_AGE) ) { next; }          # prevent looping if we never reach the item limit
                $lastcnt = (keys(%PHOTOS));
            } while ( keys(%PHOTOS) < $item_limit);

            $sth_touch_user_chan->execute($$row{'iduserchannels'}) or  print "Unable to execute user_channels UPDATE statement: " . $dbh->errstr;
    }

