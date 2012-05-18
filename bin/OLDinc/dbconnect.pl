#!/usr/bin/perl -w

#---------------------------
# G L O B A L S
#---------------------------

our $dbh;

my $dsn = 'DBI:mysql:openchannel:localhost';
my $db_user_name = 'fcrss_site';
my $db_password = 'w3b3j4mn!';

#---------------------------
sub dbStart()
#---------------------------
{
    $dbh = DBI->connect($dsn, $db_user_name, $db_password);
    if (!defined $dbh) {
        SysMsg($MSG_CRIT, "Unable to connect to the DB: " . $DBI::errstr);
        exit 1;
    }

    $dbh->{'mysql_enable_utf8'} = 1;

    $dbh->do('SET NAMES utf8');

    binmode(STDOUT, ":utf8");               # correctly display UTF8 on console

    return;
}

1;
