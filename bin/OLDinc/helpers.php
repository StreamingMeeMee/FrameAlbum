<?php
$GLOBALS['PROGRAMNAME'] = '';
$GLOBALS['PROGRAMOWNER'] = 'tim@frontierdigital.com';
$GLOBALS['VERSIONSTRING'] = '';

$GLOBALS['DEBUG'] = 0;

define("MSG_DEBUG",   5);
define("MSG_VERBOSE", 4);
define("MSG_INFO",    3);
define("MSG_WARN",    2);
define("MSG_ERR",     1);
define("MSG_CRIT",    99);

## SEVERITY - Severity label text for messsages
$GLOBALS['SEVERITY'] = array(
 MSG_DEBUG => '*Debug*',
 MSG_VERBOSE =>'*Verbose*',
 MSG_INFO => '*Info*',
 MSG_WARN => '*Warn*',
 MSG_ERR => '*Error*',
 MSG_CRIT => '*Critical*');

$GLOBALS['MSG_FORMAT'] = 'TEXT';       # 'HTML'|'TEXT'

## MSG_PRINT_THRESHOLD - Print messages of this severity and higher
$GLOBALS['MSG_PRINT_THRESHOLD'] = MSG_INFO;
if ($GLOBALS['DEBUG']) { $GLOBALS['MSG_PRINT_THRESHOLD'] = MSG_DEBUG; }

## OPS_ALERT_THRESHOLD - Send alert to OPS_EMAIL for messages of this severity and higher
$GLOBALS['$OPS_ALERT_THRESHOLD'] = MSG_WARN;

## OPS_EMAIL - Where to send alerts
$GLOBALS['OPS_EMAIL'] = 'tim@frontierdigital.com';

## OPS_EMAIL_SUBJ - What is the subject of the email to ops
if (!empty($GLOBALS['PROGRAMNAME'])) {
  $GLOBALS['OPS_EMAIL_SUBJ'] = $GLOBALS['PROGRAMNAME'] . ' has encountered a problem';
} else {
  $GLOBALS['OPS_EMAIL_SUBJ'] = "A problem has been encountered.";
}

## SMTP_SERVER - SMTP server hostname
$GLOBALS['SMTP_SERVER'] = 'localhost';
$GLOBALS['EMAIL_FROM'] = $GLOBALS['PROGRAMOWNER'];
if ($GLOBALS['DEBUG']) { $GLOBALS['EMAIL_TO'] = $GLOBALS['PROGRAMOWNER']; }

$GLOBALS['EMAIL_CC'] = '';

#--------------------------
function SysMsg($sev, $msg)
#--------------------------
{

    if ($sev == MSG_CRIT) {
        echo $GLOBALS['SEVERITY'][$sev] . ' ' . $msg . "\n";
        exit;
    }

    if ($sev <= $GLOBALS['MSG_PRINT_THRESHOLD']) {
        echo $GLOBALS['SEVERITY'][$sev] . ' ' . $msg . "\n";
    }

}

?>
