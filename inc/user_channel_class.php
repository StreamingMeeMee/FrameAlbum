<?php
#---------------------------------------
# UserChannel class - a model of a single feed channel for a given user
#
# 2012-aug-4 - TimC
#   - First Go
#
#---------------------------------------

class UserChannel 
{
    protected $dbh = null;

    private $iduserchannel = 0;

    public $user_id = 0;

    private $chan_type_id = 0;

    public $nick = '';

    public $active = 'N';

    public $attrib = '';

    private $attrib_valid = '?';

    public $item_limit = 0;

    public $status = '';

    private $last_updated = '';

    public $chan_ttl = 60;

    private $dirty = 0;         # =1 - values changed, save required

#----------------------------
function __construct( $Pdbh, $chanid=0, $user_id=0, $chan_type_id=0, $nick='My channel', $attrib='' )
#----------------------------
{
    if( isset( $chanid ) and isset( $Pdbh ) and ( $chanid > 0 ) ) {        # load an existing one
        $this->load( $Pdbh, $chanid );
    } else {
        $this->iduserchannel = 0;
        $this->nick = $nick;
        $this->user_id = $user_id;
        $this->chan_type_id = $chan_type_id;
        $this->attrib = $attrib;
        $this->needsave( 1 );               # Force a save
    }

    return $this;
}

#-----------------------------
public function stringify()
#-----------------------------
{
    $ret = 'ID:[' . $this->iduserchannel . ']  Nickname:[' . $this->nick . ']  Owner:[' . $this->user_id . ']  Attrib:[' . $this->attrib . ']';
    $ret .= '  Valid:[' . $this->attrib_valid . ']';
    $ret .= '  Item limit:[' . $this->item_limit . ']  TTL:[' . $this->chan_ttl . ']';
    $ret .= '  Status:[' . $this->status . ']  Last:['. $this->last_updated . ']  Dirty:['. $this->dirty . ']';

    return $ret;
}

#-----------------------------
public function load( $Pdbh, $chanid )
#-----------------------------
{
$ret = false;

    if( isset( $chanid ) and isset( $Pdbh ) and ( $chanid > 0 ) ) {        # load an existing one
        $this->dbh = $Pdbh;

        $chanid = prepDBVal( $chanid ); 
        $sql = "SELECT * FROM user_channels WHERE iduserchannels=$chanid";
        $sth = $this->dbh->prepare( $sql );
        if( $sth->execute() ) {
            $row = $sth->fetch( PDO::FETCH_ASSOC );
            $this->iduserchannel = $row['iduserchannels'];
            $this->user_id = $row['user_id'];
            $this->chan_type_id = $row['channel_type_id'];
            $this->nick = $row['chan_nickname'];
            $this->active = $row['active'];
            $this->attrib = $row['attrib'];
            $this->attrib_valid =  $row['attrib_valid'];
            $this->item_limit =  $row['item_limit'];
            $this->status =  $row['status'];
            $this->last_updated =  $row['last_updated'];
            $this->chan_ttl =  $row['channel_ttl'];
            $this->dirty = 0;

            $ret = true;

        }
    }

    return $ret;
}

#-----------------------------
public function save( )
#-----------------------------
{
$ret = false;

    if( isset( $this->dbh ) ) {
        $this->nick = prepDBVal( $this->nick );
        $this->attrib = prepDBval( $this->attrib );
        $this->status = prepDBVal( $this->status );

        if( $this->iduserchannel == 0 ) {
            $sql = "INSERT INTO user_channels
                (iduserchannels, user_id, channel_type_id, chan_nickname, active, attrib,
                attrib_valid, item_limit, status, channel_ttl)
                VALUES ($this->iduserchannel, $this->user_id, $this->chan_type_id, '".$this->nick."', '".$this->active."', '".$this->attrib."', '"
             . $this->attrib_valid ."', $this->item_limit, '". $this->status ."', $this->chan_ttl )";
        } else {
            $sql = "UPDATE user_channels SET
                user_id=$this->user_id, channel_type_id=$this->chan_type_id, chan_nickname='$this->nick',
                active='$this->active', attrib='$this->attrib', attrib_valid='$this->attrib_valid',
                item_limit=$this->item_limit, status='$this->status', channel_ttl=$this->chan_ttl 
                WHERE iduserchannels=$this->iduserchannel LIMIT 1";
        }

        $sth = $this->dbh->prepare( $sql );
        if( $sth->execute() ) {
            $this->dirty = 0;

            $ret = true;
        }
    }

    return $ret;
}

#-----------------------------
public function delete( )
#-----------------------------
{
$ret = false;

    if( isset( $this->dbh ) and ( $this->iduserchannel != 0 ) ) {
        $sql = "DELETE FROM user_channels WHERE iduserchannels=" . $this->iduserchannel . " AND user_id=" . $this->user_id . " LIMIT 1";

        $sth = $this->dbh->prepare( $sql );
        if( $sth->execute() ) {
            $this->dirty = 0;
            $this->iduserchannel = 0;

            $ret = true;
        }
    }

    return $ret;
}

#-----------------------------
public function needsave( $val )
#-----------------------------
{

    if( isset( $val ) ) {   # set it
        $this->dirty = 1;
    }

    return $this->dirty;
}

#-----------------------------
public function isOwner( $testme )
#-----------------------------
{
$ret = false;

    if( isset( $testme ) ) {
        $ret = ( $testme == $this->user_id );
    }

    return $ret;
}

#-----------------------------
public function nickname( $val )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->nick ) ) {   # set it
        $this->nick = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->nick;
    }

    return $ret;
}

#-----------------------------
public function attribute( $val )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->attrib ) ) {   # set it
        $this->attrib = $val;
        $this->attribute_valid( '?' );
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->attrib;
    }

    return $ret;
}

#-----------------------------
public function attribute_valid( $val )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->attrib_valid ) ) {   # set it
        if ( $val != 'Y' and $val != 'N' and $val != '?' ) { $val = '?'; }
        $this->attrib_valid = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->attrib_valid;
    }

    return $ret;
}

#-----------------------------
public function status( $val )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->status ) ) {   # set it
        $this->status = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->status;
    }

    return $ret;
}

#-----------------------------
public function owner( )
#-----------------------------
{
    return $this->user_id;
}


} #-- class
?>
