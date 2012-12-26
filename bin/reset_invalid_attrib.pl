#!/usr/bin/perl -w
#----------------------------------------
# reset_invalid_attrib.pl - Reset the 'invalid attrib' flag
#                           on all user channels to force a re-validation.
#
# 2012-dec-26 - TimC
#   - First Go
#----------------------------------------
use POSIX qw( strftime );
use Data::Dumper;
use Getopt::Std;

use DBI;

use strict;

require "inc/config.inc";
require "inc/helpers.pl";
require "inc/dbconfig.inc";

#--------------------------------------
our %GLOBALS;
our $PROGRAMNAME = 'ResetInvalidAttrib';       # Name of calling app
our $PROGRAMOWNER = 'user@email.com';;
our $VERSIONSTRING = 'v2012-dec-26';

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

our $dbh;
my $sth_reset;

#---------------------------------
# S U B S
#---------------------------------

#----------------------------
sub resetChannelTypeAttribValid($)
#----------------------------
{
my $cid = shift;

my $cnt = 0;

    SysMsg($MSG_DEBUG, "resetChannelAttribValid: chanType:[$cid]");

    SysMsg($MSG_INFO, 'Resetting valid attrib. flags for channel type:['. $cid . ']');

    unless ($sth_reset) {
        $sth_reset = $dbh->prepare("UPDATE user_channels SET attrib_valid='?' WHERE channel_type_id=?");
        if (!defined $sth_reset) {
            SysMsg($MSG_CRIT, "Unable to prepare user_channels UPDATE statement: " . $dbh->errstr);
            exit 1;
        }
    }

    $sth_reset->execute( $cid )
        or SysMsg($MSG_CRIT, "Unable to execute user_channels UPDATE statement: " . $dbh->errstr);

    return;
}

#----------------------------------
# M A I N
#----------------------------------
my %opts=();

my $resp;
my $cid = 0;
my $sth;
my $sql = '';
my $cnt = 0;

    getopts('dDt:', \%opts);          # -d debug, -t # - reset specific channel type ID #

    if (defined $opts{D}) { $DEBUG = 1; }       # Debug option?
    if (defined $opts{d}) { $DEBUG = 1; }
    if ($DEBUG) { $MSG_PRINT_THRESHOLD = $MSG_DEBUG; }
    if (defined $opts{t}) { SysMsg($MSG_DEBUG, 'Reset channel type:['.$opts{t}.']'); $cid = $opts{t} }

    dbStart();

    if ($cid > 0) {
		resetChannelTypeAttribValid( $cid );
		$cnt = 1;
    } else {
		$sql = "SELECT idchanneltypes FROM channel_types WHERE active='Y'";

        $sth = $dbh->prepare( $sql );
        if (!defined $sth) {
            SysMsg($MSG_CRIT, "Unable to prepare channel_types SELECT statement: " . $dbh->errstr);
            exit 1;
        }

        $sth->execute()
            or SysMsg($MSG_CRIT, "Unable to execute channel_types SELECT statement: " . $dbh->errstr);

        while ( ($cid) = $sth->fetchrow_array() ) {

            resetChannelTypeAttribValid( $cid );

            $cnt++;
        }
    }

    SysMsg($MSG_INFO, 'Sucessfully reset ' . $cnt . ' channel types.');

    exit;
