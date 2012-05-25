#!/usr/bin/perl -w
#----------------------------------------
# loadframes.pl - Add items to a frame
#
# 2011-jun-16 - TimC
#   - First go
#
# 2011-jul-23 - TimC
#   - Remove some crufty globals
#
# 2011-aug-29 - TimC
#   - Convert to using dbStart() & SysMsg()
#   - observe -d and -f N command line options
#
# 2011-sept-17 - TimC
#   - Include walltime in summary msg.
#
# 2011-nov-5 - TimC
#   - Test some metrics to leftronics
#
# 2012-may-25 - TimC
#   - don't send Leftronic metrics if key is not defined
#----------------------------------------
use POSIX qw( strftime );
use Data::Dumper;
use Getopt::Std;

use DBI;

use strict;

require "inc/config.inc";
require "inc/helpers.pl";
require "inc/dbconnect.pl";

#--------------------------------------
our %GLOBALS;
our $PROGRAMNAME = 'LoadFrames';       # Name of calling app
our $PROGRAMOWNER = 'user@email.com';;
our $VERSIONSTRING = 'v2011-sept-17';

our $DEBUG = 0;

our $MSG_DEBUG;
our $MSG_VERBOSE;
our $MSG_INFO;
our $MSG_WARN;
our $MSG_ERR;
our $MSG_CRIT;     # CRIT messages will trigger a DIE() call!

## MSG_PRINT_THRESHOLD - Print messages of this severity and higher
our $MSG_PRINT_THRESHOLD = $MSG_INFO;
if ($DEBUG) { $MSG_PRINT_THRESHOLD = $MSG_DEBUG; }

## OPS_ALERT_THRESHOLD - Send alert to OPS_EMAIL for messages of this severity and higher
our $OPS_ALERT_THRESHOLD = $MSG_WARN;

## OPS_EMAIL - Where to send alerts
our $OPS_EMAIL = 'user@email.com';

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
my $MAX_ITEMS = 200;

our $dbh;
my $sth_add_items;
my $sth_chan_items;
my $sth_add_frame_items;
my $sth_touch_fram_chan;
my $sth_clear_fram_items;
my $sth_fc;

#---------------------------------
# S U B S
#---------------------------------

#----------------------------
sub populateFrame($)
#----------------------------
{
my $fid = shift;

my $sql = '';
my $cid = 0;
my $item_limit;
my $item_cnt = 0;

	
   $sql = "SELECT fc.user_channel_id,fc.item_limit FROM frames AS f,`frame_channels` AS fc,
        user_channels AS uc,channel_types AS c, users AS u
        WHERE f.idframes=? AND f.active='Y' AND fc.frame_id = f.idframes AND user_channel_id = iduserchannels AND
        channel_type_id=idchanneltypes AND fc.active='Y' AND (c.active='Y' OR c.active='T') AND
        f.user_id=u.idusers AND u.active='Y'";

    unless ($sth_fc) {
        $sth_fc = $dbh->prepare($sql);
        if (!defined $sth_fc) {
            SysMsg($MSG_CRIT,  "Unable to prepare populateFrame SELECT statement: " . $dbh->errstr);
            exit 1;
        }
    }

    $sth_fc->execute($fid)
        or SysMsg($MSG_CRIT, "Unable to execute populateFrame SELECT statement: " . $dbh->errstr);

    if ($sth_fc->rows() > 0) {
        while ( ($cid, $item_limit) = $sth_fc->fetchrow_array() ) {

            unless ( ($item_limit) && ($item_limit > 0) ) { $item_limit = $MAX_ITEMS; }

            $item_cnt += populateFrameChannel($fid, $cid, $item_limit);
        }
    } else {                                    # this frame has no channels - put up an info. panel instead
        $item_cnt += populateFrameChannel($fid, $GLOBALS{'no_frame_content_channel'}, 1);
    }

	return $item_cnt;
}

#----------------------------
sub populateFrameChannel($$$)
#----------------------------
{
my $fid = shift;
my $cid = shift;
my $item_limit = shift;

my $itemid = 0;

my $cnt = 0;

    SysMsg($MSG_DEBUG, "populate: chan:[$cid]  frame:[$fid]  limit:[$item_limit]");

    unless ($sth_touch_fram_chan) {
        $sth_touch_fram_chan = $dbh->prepare("UPDATE frame_channels SET last_updated=now() WHERE frame_id=? AND user_channel_id=?");
        if (!defined $sth_touch_fram_chan) {
            SysMsg($MSG_CRIT, "Unable to prepare frame_channels UPDATE statement: " . $dbh->errstr);
            exit 1;
        }
    }

    unless ($sth_clear_fram_items) {
        $sth_clear_fram_items = $dbh->prepare("DELETE from frame_items WHERE frame_id=? AND user_channel_id=?");
        if (!defined $sth_clear_fram_items) {
            SysMsg($MSG_CRIT, "Unable to prepare frame_items DELETE statement: " . $dbh->errstr);
            exit 1;
        }
    }

    unless ($sth_chan_items) {
        $sth_chan_items = $dbh->prepare("SELECT iditems FROM items WHERE user_channel_id=?");
        if (!defined $sth_chan_items) {
            SysMsg($MSG_CRIT,  "Unable to prepare chan_items SELECT statement: " . $dbh->errstr);
            exit 1;
        }
    }

    unless ($sth_add_frame_items) {
        $sth_add_frame_items = $dbh->prepare("INSERT INTO frame_items (frame_id, user_channel_id, item_id, feed_order) VALUES(?,?,?,0)");
        if (!defined $sth_add_frame_items) {
            SysMsg($MSG_CRIT, "Unable to prepare frame_items INSERT statement: " . $dbh->errstr);
            exit 1;
        }
    }

    $sth_clear_fram_items->execute($fid, $cid)
        or SysMsg($MSG_CRIT, "Unable to execute frame_items DELETE statement: " . $dbh->errstr);

    $sth_chan_items->execute($cid)
        or SysMsg($MSG_CRIT, "Unable to execute chan items SELECT statement: " . $dbh->errstr);

    $cnt = 0;
    while ( ( ($itemid) = $sth_chan_items->fetchrow_array() ) && ($cnt < $item_limit) ) { 
        SysMsg($MSG_DEBUG, "  Adding: item[$itemid] cnt:[$cnt]");
        $sth_add_frame_items->execute($fid, $cid, $itemid)
            or SysMsg($MSG_CRIT, "Unable to execute frame items INSERT statement: " . $dbh->errstr);
        $cnt++;
    }

    $sth_touch_fram_chan->execute($fid, $cid)
        or SysMsg($MSG_CRIT, "Unable to execute frame_channels UPDATE statement: " . $dbh->errstr);

    return $cnt;            # return number of photos added to feed
}

#----------------------------------
# M A I N
#----------------------------------
my %opts=();

my $resp;
my $fid = 0;
my $cid = 0;
my $item_limit = 0;
my $item_cnt = 0;
my $sth;
my $sql = '';
my $cnt = 0;
my $st = time();
my $et = 0;

    getopts('dDf:', \%opts);          # -d debug, -f # - build only frameID #

    if (defined $opts{D}) { $DEBUG = 1; }       # Debug option?
    if (defined $opts{d}) { $DEBUG = 1; }
    if ($DEBUG) { $MSG_PRINT_THRESHOLD = $MSG_DEBUG; }
    if (defined $opts{f}) { SysMsg($MSG_DEBUG, 'Build frameID:['.$opts{f}.']'); $fid = $opts{f} }

    dbStart();

    if ($fid > 0) {
		$sql = "SELECT f.idframes,fc.user_channel_id,fc.item_limit FROM frames AS f,`frame_channels` AS fc,
			user_channels AS uc,channel_types AS c, users AS u 
			WHERE f.idframes= 1 AND f.active='Y' AND fc.frame_id = f.idframes AND user_channel_id = iduserchannels AND
			channel_type_id=idchanneltypes AND fc.active='Y' AND (c.active='Y' OR c.active='T') AND
			f.user_id=u.idusers AND u.active='Y'";
		$item_cnt = populateFrame($fid);
		$cnt = 1;
    } else {
		$sql = "SELECT f.idframes FROM frames AS f, users AS u
            WHERE f.active='Y' AND  f.user_id=u.idusers AND u.active='Y'";
    }

    $sth = $dbh->prepare($sql);
    if (!defined $sth) {
        SysMsg($MSG_CRIT, "Unable to prepare reports SELECT statement: " . $dbh->errstr);
        exit 1;
    }

    $sth->execute()
        or SysMsg($MSG_CRIT, "Unable to execute reports SELECT statement: " . $dbh->errstr);

    while ( ($fid) = $sth->fetchrow_array() ) {

        $item_cnt += populateFrame($fid);

        $cnt++;
    }

    $et = time() - $st;
    SysMsg($MSG_INFO, 'Sucessfully loaded ' . $cnt . ' frames with ' . $item_cnt . ' items (' . $item_cnt/$cnt . ' items per frame.) in ' . $et . 's.');

    $dbh->do("INSERT INTO batch_stats (batch_id, rundate, wall_time, stats) VALUES (1, now(), $et, '" . $cnt . '|' . $item_cnt  . "')")
        or SysMsg($MSG_CRIT, "Unable to execute grabber_stats INSERT statement: " . $dbh->errstr);

    if ( exists( $GLOBALS{'leftronic_key'} ) ) {
        my $cmd = "curl -k -i -X POST -d '" . '{"accessKey": "' . $GLOBALS{'leftronic_key'} . '", "streamName": "buildfeed_frames", "point": ' .$cnt . "}' https://beta.leftronic.com/customSend/";
        SysMsg($MSG_INFO, 'CMD:['.$cmd.']');
        system $cmd;

        $cmd = "curl -k -i -X POST -d '" . '{"accessKey": "' . $GLOBALS{'leftronic_key'} . '", "streamName": "buildfeed_runtime", "point": ' .$et . "}' https://beta.leftronic.com/customSend/";
        SysMsg($MSG_INFO, 'CMD:['.$cmd.']');
        system $cmd;
    }

    exit;
