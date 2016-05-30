#!/usr/bin/perl -w
use strict;

use DBI;

require '../dbconfig.inc';
require '../helpers.pl';
require '../fa-frame.pm';

our $PROGRAMNAME = 'TestFA-Frame';
our $PROGRAMOWNER = 'TimC';
our $VERSIONSTRING = '2016-may-30';

our $MSG_DEBUG;
our $MSG_VERBOSE;
our $MSG_INFO;
our $MSG_WARN;
our $MSG_ERR;
our $MSG_CRIT;     # CRIT messages will trigger a DIE() call!

my $dbh;
my $o;
my $dt;

    $dbh = dbStart();

    SysMsg($MSG_INFO, "--------------------------------------");
    SysMsg($MSG_INFO, "- FA Frame");
    SysMsg($MSG_INFO, "--------------------------------------");

    $o = new FAFrame( $dbh, 15 );

    SysMsg($MSG_INFO, 'Loaded:[' . $o->stringify() . "]");

    $o->last_seen_tm( time() );

    SysMsg($MSG_INFO, 'Changed:[' . $o->stringify() . "]");

    $o->save();

    SysMsg($MSG_INFO, 'Saved:[' . $o->stringify() . "]");

