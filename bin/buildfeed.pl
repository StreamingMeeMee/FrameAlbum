#!/usr/bin/perl -w
#------------------------------------
# buildfeed.pl - Build a FrameChannel'esque RSS feed
#
# 2011-aug-29 - TimC
#   - Convert to using dbStart();
#   - Convert to using SysMsg();
#   - Allow building a single frame with '-f' option
#
# 2011-aug-31 - TimC
#   - log runsstats to batch_stats
#   - prevent multiple instances
#
# 2011-sept-4 - TimC
#   - reformat pubDate fields as GMT
#   - add <pubDate> and <generator> to <channel>
#
# 2011-sept-5 - TimC
#   - Remove blank line after <channel>/before </channel>
#   - disable <generator> tag -- a bit too self-serving and not visible on frames
#   - use encode_entities on all content in feed
#
# 2011-sept-14 - TimC
#   - Change encode_entities to encode only '&<>' per RSS specs
#   - Set title to 'Untitled' if no title is present
#
# 2011-sept-17 - TimC
#   - Make sure that walltime is at least 1s
#   - Get path info from config.inc
#
# 2011-dec-18 - TimC
#   - convert to use inc/dbconfig.inc
#
# 2012-aug-2 - TimC
#   - Make sure rss directory exists before putting files there - DOH!
#   - try eliminating blank line after <channel> and before </channel>
#-------------------------------------
use DBI;

use Data::Dumper;
use URI::Escape;
use Getopt::Std;
use POSIX qw( strftime );
use File::Pid;
use File::Util;
use HTML::Entities;

use strict;

require "inc/helpers.pl";
require "inc/config.inc";
require "inc/dbconfig.inc";

#--------------------------------------
our $PROGRAMNAME = 'BuildFeed';       # Name of calling app
our $PROGRAMOWNER = 'user@email.com';
our $VERSIONSTRING = 'v2012-aug-2';

our $DEBUG = 0;

our $MSG_DEBUG;
our $MSG_VERBOSE;
our $MSG_INFO;
our $MSG_WARN;
our $MSG_ERR;
our $MSG_CRIT;     # CRIT messages will trigger a DIE() call!

our %GLOBALS;

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

my $PID_FN = '/var/run/' . $PROGRAMNAME . '.pid';

my $ENCODE_CHARS = '&<>';
#------------------
# G L O B A L
#------------------

our $dbh;

#my $RSS_ROOT = '/opt/framealbum/rss/00/00/';

my $sth_items;
my $sth_items_shuffle;

#------------------
# S U B S
#------------------

#--------
sub feedHead()
#--------
{
my $t = '';

    $t .= '<?xml version="1.0" encoding="utf-8" ?>'."\n";
    $t .= '<rss version="2.0"  xmlns:media="http://search.yahoo.com/mrss/" xmlns:frameuserinfo="http://www.framechannel.com/feeds/frameuserinfo/" xmlns:tsmx="http://www.thinkingscreenmedia.com/tsmx/" >'."\n";

    return $t;
}

#--------
sub feedTail()
#--------
{
my $t = '';

    $t .= "</rss>\n";

    return $t;
}

#--------
sub channelHead($$$)
#--------
{
my $user = shift;
my $ttl = shift;
my $fid = shift;

my $t = '';

    SysMsg($MSG_DEBUG, 'channelHead: user:['.$user.']  ttl:['.$ttl."]  fid:[".$fid."]");

#    $t .= "<channel>\n\n";
    $t .= "<channel>\n";
    $t .= "<title>FrameAlbum content for " . encode_entities($user, $ENCODE_CHARS) . "</title>\n";
    $t .= "<link>" . $GLOBALS{'www_url_root'} . "</link>\n";
    $t .= "<description>Channel for user " . encode_entities($user, $ENCODE_CHARS) . "</description>\n";
#    $t .= "<pubDate>" . strftime("%a, %d %b %Y %T GMT", gmtime()) . "</pubDate>\n";
#    $t .= "<lastBuildDate>" . strftime("%a, %d %b %Y %T GMT", gmtime()) . "</lastBuildDate>\n";
#    $t .= "<generator>http://www.framealbum.com</generator>\n";
    $t .= "<ttl>$ttl</ttl>\n";
    $t .= "<frameuserinfo:firstname>-</frameuserinfo:firstname>\n";
    $t .= "<frameuserinfo:lastname>-</frameuserinfo:lastname>\n";
    $t .= "<frameuserinfo:username>" . encode_entities($user, $ENCODE_CHARS) . "</frameuserinfo:username>\n";
    $t .= "<frameuserinfo:unregistered>FALSE</frameuserinfo:unregistered>\n";
#    $t .= "<frameuserinfo:frameid>$fid</frameuserinfo:frameid>\n";

    return $t;
}

#--------
sub channelTail()
#--------
{
my $t = '';

#    $t .= "\n</channel>\n";
    $t .= "</channel>\n";

    return $t;
}


#--------
sub feedBody($$$)
#--------
{
my $fid = shift;
my $item_limit = shift;
my $shuffle = shift;

my $t = '';
my $ref;
my $pubDate = '';
my $title = '';

    SysMsg($MSG_DEBUG, 'feedBody:['.$fid.']  Shuffle:['.$shuffle.']');

    unless ($sth_items) {
        $sth_items = $dbh->prepare("SELECT *,DATE_FORMAT( `pubDate`, '%a, %d %b %Y %T GMT' ) AS pubDateF FROM frame_items AS fi, items AS it WHERE fi.frame_id=? AND it.iditems=fi.item_id ORDER BY fi.feed_order");
        if (!defined $sth_items) {
            SysMsg($MSG_CRIT, 'Unable to prepare frame_items SELECT statement: ' . $dbh->errstr);
            exit 1;
        }
    }

    unless ($sth_items_shuffle) {
        $sth_items_shuffle = $dbh->prepare("SELECT *,DATE_FORMAT( `pubDate`, '%a, %d %b %Y %T GMT' ) AS pubDateF FROM frame_items AS fi, items AS it WHERE fi.frame_id=? AND it.iditems=fi.item_id ORDER BY rand()");
        if (!defined $sth_items_shuffle) {
            SysMsg($MSG_CRIT, 'Unable to prepare frame_items (shuffle) SELECT statement: ' . $dbh->errstr);
            exit 1;
        }
    }

    if ($shuffle eq 'Y') {
        SysMsg($MSG_INFO, 'Building a shuffled feed for FID:['.$fid.']');
        $sth_items_shuffle->execute($fid) or
            SysMsg($MSG_CRIT, 'Unable to execute frame_items (shuffle)(shuffle)  SELECT statement: ' . $dbh->errstr);
        $ref = $sth_items_shuffle->fetchrow_hashref()
    } else {
        $sth_items->execute($fid) or
            SysMsg($MSG_CRIT, 'Unable to execute frame_items SELECT statement: ' . $dbh->errstr);
        $ref = $sth_items->fetchrow_hashref()
    }

     
    while ( ($ref) && ($item_limit > 0) ) {
        SysMsg($MSG_DEBUG, "$$ref{'item_id'} \t $$ref{'link'}");

        if ( $$ref{'pubDateF'} ) {
            $pubDate = $$ref{'pubDateF'};
        } else {
            $pubDate = strftime('%a, %d %b %Y %H:%M:%S GMT', gmtime() );
        }

        if ( $$ref{'title'} ) {
            $title = $$ref{'title'};
        } else {
            $title = 'Untitled';
        }

        $t .= "<item>\n";

        $t .= "    ".'<title>' . encode_entities($$ref{'title'}, $ENCODE_CHARS) . "</title>\n";
        $t .= "    ".'<link>' . encode_entities($$ref{'link'}, $ENCODE_CHARS) . "</link>\n";
        $t .= "    ".'<category>' . encode_entities($$ref{'category'}, $ENCODE_CHARS) . "</category>\n";
#        $t .= "    ".'<description>' . uri_escape( $$ref{'description'} ) . "</description>\n";
        $t .= "    ".'<description>' . encode_entities($$ref{'link'}, $ENCODE_CHARS) . "</description>\n";
        $t .= "    ".'<pubDate>' .  $pubDate . "</pubDate>\n";
        $t .= "    ".'<guid isPermaLink="false">' .  encode_entities($$ref{'guid'}, $ENCODE_CHARS) . "</guid>\n";
        $t .= "    ".'<media:content url="' . $$ref{'media_content_url'} . '" type="image/jpg" duration="10" />'."\n";
        $t .= "    ".'<media:thumbnail url="' . $$ref{'media_thumbnail_url'} . '" />'."\n";

        $t .= "</item>\n";

        $item_limit--;

        if ($shuffle eq 'Y') {
            $ref = $sth_items_shuffle->fetchrow_hashref()
        } else {
            $ref = $sth_items->fetchrow_hashref()
        }

    }

    return $t;
}

#------------------
sub buildFrameFeed($$$)
#------------------
{
my $fid = shift;
my $uname = shift;
my $limit = shift;

my $t = '';
my $fn = '';

    $t = feedHead();
    $t .= channelHead($uname, 30, $fid);

    unless ($limit) { $limit = 99999; }         # if no limit defined set it crazy high
    SysMsg($MSG_DEBUG, "item limit:[".$limit."]");

    $t .= feedBody($fid, $limit, 'N');

    $t .= channelTail();
    $t .= feedTail();

    SysMsg($MSG_DEBUG, 'Checking for RSS path:[' . $GLOBALS{'rss_path'} . '/00/00/' . ']');
    unless( -e $GLOBALS{'rss_path'} . '/00/00/' ) {
        SysMsg($MSG_WARN, 'RSS file target dir, does not exists; making:[' . $GLOBALS{'rss_path'} . '/00/00/' . ']');
        my($f) = File::Util->new();
        unless( $f->make_dir( $GLOBALS{'rss_path'} . '/00/00/', 0755, '--if-not-exists' ) ) {
            SysMsg($MSG_CRIT, 'Unable to create RSS target directory:[' . $GLOBALS{'rss_path'} . '/00/00/' . ']');
        }
    }

    $fn = $GLOBALS{'rss_path'} . '/00/00/' . $fid . '.rss';
    SysMsg($MSG_DEBUG, 'Writing feed to:['.$fn.']');

    open(FEED, '>', $fn) or die $!;

    print FEED $t;

    close(FEED);

    return;
}
#------------------
# M A I N
#------------------
my $t = '';
my $fn = '';
my $fid = 0;
my $sth_frames;
my $ref;
my $item_limit;
my $cnt = 0;
my $st = time();
my $et = 0;
my %opts;

my $pidfile='';
my $pid;

    getopts('dDf:', \%opts);          # -d debug, -f # - build only frameID #

    if (defined $opts{D}) { $DEBUG = 1; }       # Debug option?
    if (defined $opts{d}) { $DEBUG = 1; }
    if ($DEBUG == 1) { $MSG_PRINT_THRESHOLD = $MSG_DEBUG; }
    if (defined $opts{f}) { SysMsg($MSG_DEBUG, 'Build frameID:['.$opts{f}.']'); $fid = $opts{f} }
    SysMsg($MSG_DEBUG, 'DEBUG mode');

    SysMsg($MSG_DEBUG, 'PID FN:['.$PID_FN.']');
    $pidfile = File::Pid->new({file=>$PID_FN});

    SysMsg($MSG_DEBUG, "pidfile: [" . $pidfile->file . "]");

    $pid = $pidfile->running();
    if ( $pid ){
        SysMsg($MSG_CRIT, "Process already running (pid: $pid) - this instance aborted.");
    } else {
        $pidfile->write();          # Create the PID file
    }

    dbStart();

    unless ($fid > 0) {
        unless ($sth_frames) {
            $sth_frames = $dbh->prepare("SELECT * FROM frames AS fr, users AS u WHERE fr.active='Y' AND u.idusers=fr.user_id AND u.active='Y'");
            if (!defined $sth_frames) {
                SysMsg($MSG_CRIT, "Unable to prepare frames SELECT statement: " . $dbh->errstr);
                exit 1;
            }
        }

        $sth_frames->execute() or
            SysMsg($MSG_CRIT, "Unable to execute frames SELECT statement: " . $dbh->errstr);

        while ( ($ref = $sth_frames->fetchrow_hashref()) ) {

            $t = '';
            $t = feedHead();
            $t .= channelHead($$ref{'username'}, 30, $$ref{'idframes'});

            unless ($$ref{'item_limit'}) { $$ref{'item_limit'} = 99999; }         # if no limit defined set it crazy high
            SysMsg($MSG_DEBUG, "item limit:[".$$ref{'item_limit'}.']');

            $t .= feedBody($$ref{'idframes'}, $$ref{'item_limit'}, $$ref{'shuffle_items'});

            $t .= channelTail();
            $t .= feedTail();

            SysMsg($MSG_DEBUG, 'Checking for RSS path:[' . $GLOBALS{'rss_path'} . '/00/00/' . ']');
            unless( -e $GLOBALS{'rss_path'} . '/00/00/' ) {
                SysMsg($MSG_WARN, 'RSS file target dir, does not exists; making:[' . $GLOBALS{'rss_path'} . '/00/00/' . ']');
                my($f) = File::Util->new();
                unless( $f->make_dir( $GLOBALS{'rss_path'} . '/00/00/', 0755, '--if-not-exists' ) ) {
                    SysMsg($MSG_CRIT, 'Unable to create RSS target directory:[' . $GLOBALS{'rss_path'} . '/00/00/' . ']');
                }
            }

            $fn = $GLOBALS{'rss_path'} . '/00/00/' . $$ref{'idframes'} . '.rss';
            SysMsg($MSG_DEBUG, 'Writing feed to:[' . $fn . ']');

            open(FEED, '>', $fn) or die $!;

            print FEED $t;

            close(FEED);

            $cnt++;
        }
    } else {
        buildFrameFeed($fid,'',0);
        $cnt = 1;
    }

    $et = time() - $st + 1;
    SysMsg($MSG_INFO, 'Built feeds for ' . $cnt . ' frames in ' . $et . 's');

    $dbh->do("INSERT INTO batch_stats (batch_id, rundate, wall_time, stats) VALUES (2, now(), $et, '" . $cnt . '|' . "')")
        or SysMsg($MSG_CRIT, "Unable to execute grabber_stats INSERT statement: " . $dbh->errstr);

    $pidfile->remove();

    exit;
