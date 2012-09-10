<?php
#---------------------------------------
# EventLog class - a model of an event log
#
# 2012-aug-14 - TimC
#   - First Go
#
#---------------------------------------
require_once "dbconfig.php";

class EventLog
{
    protected $dbh = null;

    private $event_id = 0;

    private $user_id = NULL;

    private $event_msg = NULL;

    private $dirty = 0;         # =1 - values changed, save required

#----------------------------
function __construct( $eid=NULL )
#----------------------------
{
    $this->dbh = dbStart();

    if( isset( $eid ) ) { $this->event_id = $eid; }
    
    $this->needsave( 1 );

    return $this;
}

#-----------------------------
public function save( )
#-----------------------------
{
$ret = false;

    if( isset( $this->dbh ) ) {
        if( $this->event_id != 0 ) {

            $sql = "INSERT INTO event_log 
                (event_time, event_id, user_id, event_msg)
                VALUES ( now(), " . q($this->event_id) . ", " . q($this->user_id) . ", " . q($this->event_msg) .")";

            $sth = $this->dbh->prepare( $sql );
            if( $sth->execute() ) {
                $this->dirty = 0;

                $ret = true;
            }
        }
    }

    return $ret;
}

#-----------------------------
public function stringify()
#-----------------------------
{
    $ret = 'ID:[' . $this->event_id . ']  UID:[' . $this->user_id . ']';
    $ret .= '  Msg:[' . $this->event_msg . ']';
    $ret .= '  Dirty:['. $this->dirty . ']';

    return $ret;
}

#-----------------------------
public function needsave( $val=NULL )
#-----------------------------
{

    if( isset( $val ) ) {   # set it
        $this->dirty = 1;
    }

    return $this->dirty;
}

#-----------------------------
public function user_id( $val=NULL )
#-----------------------------
{
    if( isset( $val ) and ( $val != $this->user_id ) ) {
        $this->user_id = $val;
        $this->needsave( 1 );
        $ret = $val;
     }
 
    return $this->user_id;
}

#-----------------------------
public function event_id( $val=NULL )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->event_id ) ) {   # set it
        $this->event_id = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->event_id;
    }

    return $ret;
}

#-----------------------------
public function event_msg( $val=NULL )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->event_msg ) ) {   # set it
        $this->event_msg = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->event_msg;
    }

    return $ret;
}

#-----------------------------
public function logSystemEvent ( $eid, $uid=NULL, $msg=NULL )
#-----------------------------
{
$ret = false;

    if( isset( $eid ) ) {   # set it
        $this->event_id( $eid );
        $this->user_id( $uid ); 
        $this->event_msg( $msg );
        $ret = $this->save();
    } else {                                            # simply return the current val
        $ret = false; 
    }

    return $ret;
}

#-----------------------------
public function logSystemDebugEvent ( $uid=NULL, $msg='Debug Message' )
#-----------------------------
{
$ret = false;

    $ret = $this->logSystemEvent( 1, $uid, $msg );

    return $ret;
}

#-----------------------------
public function logSystemInfoEvent ( $uid=NULL, $msg='Info Message' )
#-----------------------------
{
$ret = false;

    $ret = $this->logSystemEvent( 2, $uid, $msg );

    return $ret;
}

} #-- class
?>
