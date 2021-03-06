use MIME::Lite;
use Net::SMTP;
#--------------------------------------
# 2012-aug-2 - TimC
#   - rename getSysParms() -> getSysParm(); It only gets one parm at a time. ;-)
#   - in SendTextEMail(),SendHTMLEmail() reduce missing parm. msg severity to 'INFO' to reduce possibility of SysMsg loops
#
# 2012-aug-11 - TimC
#   - Add featureEnabled() to check feature status.
#
# 2016-may-30 - TimC
#   - code getSysParm() - was only a stub
#--------------------------------------
#==============================
# G L O B A L S
#==============================
our %GLOBALS;

our $DEBUG = 0;

our $dbh;

#--- SysMsg() Globals
our @SAVEMSGS;           # Collect messages for OPS report

our $PROGRAMNAME;       # Name of calling app
our $PROGRAMOWNER;
our $VERSIONSTRING;

our $MSG_DEBUG = 5;
our $MSG_VERBOSE = 4;
our $MSG_INFO = 3;
our $MSG_WARN = 2;
our $MSG_ERR = 1;
our $MSG_CRIT = 99;     # CRIT messages will trigger a DIE() call!

## SEVERITY - Severity label text for messsages
my %SEVERITY;
 $SEVERITY{$MSG_DEBUG} = '*Debug*';
 $SEVERITY{$MSG_VERBOSE} = '*Verbose*';
 $SEVERITY{$MSG_INFO} = '*Info*';
 $SEVERITY{$MSG_WARN} = '*Warn*';
 $SEVERITY{$MSG_ERR} = '*Error*';
 $SEVERITY{$MSG_CRIT} = '*Critical*';

our $MSG_FORMAT = 'TEXT';       # 'HTML'|'TEXT'

## MSG_PRINT_THRESHOLD - Print messages of this severity and higher
our $MSG_PRINT_THRESHOLD = $MSG_INFO;
if ($DEBUG) { $MSG_PRINT_THRESHOLD = $MSG_DEBUG; }

## OPS_ALERT_THRESHOLD - Send alert to OPS_EMAIL for messages of this severity and higher
our $OPS_ALERT_THRESHOLD = $MSG_WARN;

## OPS_EMAIL - Where to send alerts
our $OPS_EMAIL = 'ops@email.com';

## OPS_EMAIL_SUBJ - What is the subject of the email to ops
our $OPS_EMAIL_SUBJ = '';
if ($PROGRAMNAME) {
  $OPS_EMAIL_SUBJ = "$PROGRAMNAME has encountered a problem";
} else {
  $OPS_EMAIL_SUBJ = "A problem has been encountered.";
}

## SMTP_SERVER - SMTP server hostname
my $SMTP_SERVER = 'localhost';
my $HELO_HOST = '';
our $EMAIL_FROM = $PROGRAMOWNER;
if ($DEBUG) { $EMAIL_TO = $PROGRAMOWNER; }
our $BCC_EMAIL_ADDR = $PROGRAMOWNER;

our $EMAIL_CC = '';

my $sth_sys_parm;           # getSysParm()

#================================================
sub featureEnabled( $ )
#================================================
{
my $feat = shift;

my $ret = 0;

    $ret = ( exists( $GLOBALS{ $feat } ) and ( $GLOBALS{ $feat } == 'TRUE' ) );

    return $ret;
}

#================================================
sub getSysParm($$$)
#================================================
{
my $parm_name = $_[0];
my $parm_default = $_[1];
my $parm_scope = (defined $_[2]) ? $_[2] : 'global';

my $val = '';

    SysMsg($MSG_DEBUG, 'getSysParm: parm:[' . $parm_name . ']  scope:[' . $parm_scope . ']  default:[' . $parm_default . ']');

    unless ((@_ == 2) or (@_ == 3)) { return ''; }        # Bail if we didn't get the right parms

    unless ($sth_sys_parm) {
        $sth_sys_parm = $dbh->prepare("SELECT value FROM sys_parms WHERE sys_parms.key=? AND sys_parms.scope=?");
        if (!defined $sth_sys_parm) {
            SysMsg($MSG_CRIT, "Unable to prepare sys_parms SELECT statement: " . $dbh->errstr);
            exit 1;
        }
    }

    if ($parm_scope) {
        $sth_sys_parm->execute($parm_name, $parm_scope) or
            SysMsg($MSG_CRIT, "Unable to EXECUTE sys_parms SELECT: " .  $dbh->errstr);
    }

    if ($sth_sys_parm->rows == 0) {
        $sth_sys_parm->execute($parm_name, 'global') or
            SysMsg($MSG_CRIT, "Unable to EXECUTE sys_parms (global) SELECT: " .  $dbh->errstr);
    }

    if ($sth_sys_parm->rows == 0) {
        $val = $parm_default;
    } else {
        ($val) = $sth_sys_parm->fetchrow_array;
    }

#       return ($val, $parm_scope);
    return $val;
}

#================================================
sub SendHTMLEmail($$$$)
#================================================
{
my $from=shift;
my $to=shift;
my $subj=shift;
my $msg=shift;

my $mime;
my $msgtime = time();
my $msgtimestr = scalar(localtime($msgtime));
my $msg_html = '';
my $part;

    SysMsg($MSG_DEBUG, 'Sending to [' . $to . ']: [' . $msg . ']');

    unless ($from) {
        $from = getSysParm('MAIL_FROM', $PROGRAMNAME, 'postmaster@email.com');
        SysMsg($MSG_INFO, "FROM email address was not defined - using system default: [$from]");
    }

    unless ($SMTP_SERVER) {
        $SMTP_SERVER = getSysParm('SMTP_SERVER', $PROGRAMNAME, 'localhost');
        SysMsg($MSG_INFO, "SMTP server was not defined - using system default: [$SMTP_SERVER]");
    }

    unless ($to) {
        unless ($OPS_EMAIL) {
            $OPS_EMAIL = getSysParm('OPS_EMAIL', $PROGRAMNAME, 'postmaster@email.com');
            SysMsg($MSG_INFO, "OPS_EMAIL email address was not defined - using system default: [$OPS_EMAIL]");
        }
        $to = $OPS_EMAIL;
        SysMsg($MSG_INFO, "'to' email address was not defined - using system default(OPS_EMAIL): [$to]");
    }

    unless ($BCC_EMAIL_ADDR) { $BCC_EMAIL_ADDR = $to; }
    $mime = MIME::Lite->new(
        From => $from,
        To => $to,
        BCC => $BCC_EMAIL_ADDR,
        Subject => $subj,
        Type => 'multipart/mixed'
    ) or SysMsg($MSG_CRIT, 'Unable to create multipart email container: ' . $!);

#----- Attach the text
    $msg_html = $msg . "<br><br><br><font size='1' face='verdana,arial' color='gray'>Created by $PROGRAMNAME (v$VERSIONSTRING) at $msgtimestr.</font><br>\n";

    $part = MIME::Lite->new( Type => 'text/html', Data => $msg_html);
    $part->attr('content-type.charset' => 'UTF-8');
    $part->attr('Encoding' => 'quoted-printable');

    $mime->attach( $part )
        or SysMsg($MSG_CRIT, 'Unable to add the message text to the email: ' . $!);

#----- Send the message
    MIME::Lite->send('smtp', $SMTP_SERVER, Timeout=>60);
    $mime->send;

}

#================================================
sub SendTextEmail($$$$)
#================================================
{
my $from=shift;
my $to=shift;
my $subj=shift;
my $msg=shift;

my $mime;
my $msgtime = time();
my $msgtimestr = scalar(localtime($msgtime));
my $msg_html = '';
my $part;

    SysMsg($MSG_DEBUG, 'Sending to [' . $to . ']: [' . $msg . ']');

    unless ($from) {
        $from = getSysParm('MAIL_FROM', $PROGRAMNAME, 'postmaster@email.com');
        SysMsg($MSG_INFO, "FROM email address was not defined - using system default: [$from]");
    }

    unless ($SMTP_SERVER) {
        $SMTP_SERVER = getSysParm('SMTP_SERVER', $PROGRAMNAME, 'localhost');
        SysMsg($MSG_INFO, "SMTP server was not defined - using system default: [$SMTP_SERVER]");
    }

    unless ($to) {
        unless ($OPS_EMAIL) {
            $OPS_EMAIL = getSysParm('OPS_EMAIL', $PROGRAMNAME, 'postmaster@email.com');
            SysMsg($MSG_INFO, "OPS_EMAIL email address was not defined - using system default: [$OPS_EMAIL]");
        }
        $to = $OPS_EMAIL;
        SysMsg($MSG_INFO, "'to' email address was not defined - using system default(OPS_EMAIL): [$to]");
    }

    unless ($BCC_EMAIL_ADDR) { $BCC_EMAIL_ADDR = $to; }
#----- Append the footer
    $msg_html = $msg . "\n\nCreated by $PROGRAMNAME (v$VERSIONSTRING) at $msgtimestr.\n";

    $mime = MIME::Lite->new(
        From => $from,
        To => $to,
        BCC => $BCC_EMAIL_ADDR,
        Subject => $subj,
        Data => $msg_html
    ) or SysMsg($MSG_CRIT, 'Unable to create email container: ' . $!);

#----- Send the message
    MIME::Lite->send('smtp', $SMTP_SERVER, Timeout=>60);
    $mime->send;
}

#================================================
sub AlertOps($)
#------------------------------------------------
# Since this sub is called by SysMsg() don't call it because a recursion loop may be created
#================================================
{
my $msg;
my $msglog = '';

    if ($DEBUG) { print 'AlertOps: Generating OPS Alert to [' . $OPS_EMAIL . ']...' . "\n"; }

    while ($msg = pop @SAVEMSGS) {
        $msglog = $msglog . "\n" . $msg;
    }

    $msglog = $msglog . "\n" . $_[0];

    if ($DEBUG) { print "AlertOps: Message Text: [ $msglog ]\n"; }

    if ($MSG_FORMAT eq 'HTML') {
        SendHTMLEmail($EMAIL_FROM, $OPS_EMAIL, $OPS_EMAIL_SUBJ, $_[0]);
    } else {
        SendTextEmail($EMAIL_FROM, $OPS_EMAIL, $OPS_EMAIL_SUBJ, $_[0]);
    }
}

#================================================
# SysMsg(severity, msg) - Handle a system message.
#
# msg - Message text
#
# severity - Message severity
#------------------------------------------------
#
#    EXECUTION WILL 'DIE' ON A CRIT MESSAGE
#
#------------------------------------------------
sub SysMsg($$)
#================================================
{
my $sev = shift;
my $msg = shift;

my $msgtime = time();
my $msgtimestr = scalar(localtime($msgtime));

  $| = 1;     # Make sure to flush the buffer after each print

  if( !defined $sev ) {
    $sev = $MSG_WARN;
  }

  if( (defined $SEVERITY{$sev} ) ) {
    $msg =  "$msgtimestr:$PROGRAMNAME:$SEVERITY{$sev} $msg\n";
  } else {
    $msg =  "$msgtimestr:$PROGRAMNAME:*Severity[" . ($sev ? $sev : 'n/a') . "]* $msg\n";
  }
  push @SAVEMSGS, $msg;             # Save for later OPS msg if necessary

  if ($sev <= $MSG_PRINT_THRESHOLD)
  {
    print $msg;
  }

  if ($sev <= $OPS_ALERT_THRESHOLD)
  {
    AlertOps($msg)
  }

  if ($sev eq $MSG_CRIT) { die };

} # SysMsg()

1;
