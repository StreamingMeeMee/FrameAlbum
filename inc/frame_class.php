<?php
#---------------------------------------
# Frame class - a model of a single frame for a given user
#
# 2012-aug-4 - TimC
#   - First Go
#
#---------------------------------------

class Frame 
{
    protected $dbh = null;

    private $idframe = 0;

    private $frame_id = '';

    private $user_id = 0;

    public $nick = '';

    public $active = 'Y';

    private $product_id = '';

    private $created = 0;

    private $last_seen = 0;

    public $feed_ttl = 60;

    public $feed_pin = 0;

    public $item_limit = 0;

    private $shuffle = 'N';

    private $security_key = '';

    private $activation_key = '';

    private $dirty = 0;         # =1 - values changed, save required

#----------------------------
function __construct( $Pdbh, $fid=0, $user_id=0, $frameid='', $prodid=0, $nick='My Frame' )
#----------------------------
{
    if( isset( $fid ) and isset( $Pdbh ) and ( $fid > 0 ) ) {        # load an existing one
        $this->load( $Pdbh, $fid );
    } else {
        $this->dbh = $Pdbh;
        $this->idframe = 0;
        $this->nick = $nick;
        $this->user_id = $user_id;
        $this->frame_id = $frameid;
        $this->product_id = $prodid;
        $this->activation_key = $this->genActivationKey();

        $this->needsave( 1 );               # Force a save
    }

    return $this;
}

#-----------------------------
public function stringify()
#-----------------------------
{
    $ret = 'ID:[' . $this->idframe . ']  Nickname:[' . $this->nick . ']  Owner:[' . $this->user_id . ']  Active:[' . $this->active . ']';
    $ret .= '  ProductID:[' . $this->product_id . ']  Created:[' . $this->created . ']';
    $ret .= '  Item limit:[' . $this->item_limit . ']  Feed TTL:[' . $this->feed_ttl . ']  Feed PIN:[' . $this->feed_pin . ']';
    $ret .= '  Shuffle:[' . $this->shuffle . ']  SecKey:[' . $this->security_key . ']  Activate:[' . $this->activation_key . ']';
    $ret .= '  Last:['. $this->last_seen . ']  Dirty:['. $this->dirty . ']';

    return $ret;
}

#-----------------------------
public function load( $Pdbh, $fid )
#-----------------------------
{
$ret = false;

    if( isset( $fid ) and isset( $Pdbh ) and ( $fid > 0 ) ) {        # load an existing one
        $this->dbh = $Pdbh;

        $chanid = prepDBVal( $fid ); 
        $sql = "SELECT * FROM frames WHERE idframes=$fid";
        $sth = $this->dbh->prepare( $sql );
        if( $sth->execute() ) {
            $row = $sth->fetch( PDO::FETCH_ASSOC );
            $this->idframe = $row['idframes'];
            $this->user_id = $row['user_id'];
            $this->product_id = $row['product_id'];
            $this->nick = $row['user_nickname'];
            $this->active = $row['active'];
            $this->created = $row['created'];
            $this->item_limit =  $row['item_limit'];
            $this->feed_ttl = $row['feed_ttl'];
            $this->feed_pin = $row['feed_pin'];
            $this->shuffle = $row['shuffle_items'];
            $this->security_key = $row['security_key'];
            $this->activation_key = $row['activation_key'];
            $this->last_seen =  $row['last_seen'];
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
        $this->security_key = prepDBVal( $this->security_key );
        $this->activation_key = prepDBVal( $this->activation_key );
        $this->item_limit = prepDBVal( $this->item_limit );

        if( $this->idframe == 0 ) {
            $sql = "INSERT INTO frames
                (frame_id, user_id, user_nickname, active, product_id,
                created, last_seen, feed_ttl, feed_pin, item_limit,
                shuffle_items, security_key, activation_key)
                VALUES ('" . $this->frame_id . "', $this->user_id, '".$this->nick."', '".$this->active."', '".$this->product_id."', "
             .  "now(), now(), $this->feed_ttl, $this->feed_pin, $this->item_limit, "
             .  "'" . $this->shuffle . "', '" . $this->security_key . "', '" . $this->activation_key . "')";
        } else {
            $sql = "UPDATE frames SET
                frame_id='" . $this->frame_id . "', user_id=$this->user_id,
                user_nickname='" . $this->nick . "', active='" . $this->active . "', product_id='" . $this->product_id."', 
                created='" . $this->created . "', last_seen='" . $this->last_seen . "', feed_ttl=$this->feed_ttl, 
                feed_pin=$this->feed_pin, item_limit=$this->item_limit,
                shuffle_items='" .  $this->shuffle . "', security_key='" . $this->security_key . "', activation_key='" . 
                $this->activation_key . "' WHERE idframes=$this->idframe LIMIT 1";
        }

        $sth = $this->dbh->prepare( $sql );
        if( $sth->execute() ) {
            $this->dirty = 0;

            if( $this->idframe == 0 ) { $this->idframe = $this->dbh->lastInsertId(); }        # grab the id of a newly added user

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

    if( isset( $this->dbh ) and ( $this->idframe != 0 ) ) {
        $sql = "DELETE FROM frames WHERE idframes=" . $this->idframe . " AND user_id=" . $this->user_id . " LIMIT 1";

        $sth = $this->dbh->prepare( $sql );
        if( $sth->execute() ) {
            $this->dirty = 0;
            $this->idframe = 0;

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
public function isPIN( $testme )
#-----------------------------
{
$ret = false;

    if( isset( $testme ) ) {
        $ret = ( $testme == $this->feed_pin );
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
public function productID( $val )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->product_id ) ) {   # set it
        $this->product_id = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->product_id;
    }

    return $ret;
}

#-----------------------------
public function owner( )
#-----------------------------
{
    return $this->user_id;
}

#----------------------------
public function genActivationKey()
#----------------------------
# Generate a unique activation key by combining 2 entries in the 'words' table.
# If after 5 attempts it is unable to generate a unique key it will use the current epoch time as the key.
#----------------------------
{
    $tries = 0;
    $LIMIT = 5;
    $ret = '';

    $sql = 'SELECT word FROM words ORDER BY RAND() LIMIT 2';
    $sth = $this->dbh->prepare( $sql );

    do {
        $sth->execute();
        $row = $sth->fetch( PDO::FETCH_ASSOC );
        $key = $row['word'];

        $row = $sth->fetch( PDO::FETCH_ASSOC );
        $key .= $row['word'];

        $sql = "SELECT idframes FROM frames WHERE activation_key='$key'";

        $sth2 = $this->dbh->prepare( $sql );
        $sth2->execute();
        if( $sth2->rowCount() == 0 ) { $ret = $key; }
        $tries++;
    } while( ( $sth->rowCount() > 0 ) and ( strlen( $ret ) == 0 ) and ( $tries <= $LIMIT ) );

    if ( strlen( $ret ) == 0 ) { $ret = time(); }          # as a last resort use epoch time

    return $ret; 
}

} #-- class
?>
