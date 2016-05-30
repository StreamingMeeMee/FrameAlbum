#!/usr/bin/perl -w

package FAFrame;

#---------------------------------------
# Frame Album 'frames' object
#
#---------------------------------------

#----------------------------
sub new
#----------------------------
{
my ( $class, $Pdbh, $id ) = @_;

my $this={};

    bless( $this, $class );

    $this->{dbh} = $Pdbh;
    $this->{idframes} = 0;
    $this->{frame_id} = '';
    $this->{user_id} = 0;
    $this->{user_nickname} = 0;
    $this->{active} = '?';
    $this->{product_id} = '';
    $this->{created} = '';
    $this->{last_seen} = '';
    $this->{last_seen_tm} = 0;
    $this->{feed_ttl} = 0;
    $this->{feed_pin} = 0;
    $this->{item_limit} = 0;
    $this->{shuffle_items} = 'N';
    $this->{security_key} = '';
    $this->{activation_key} = '';

    $this->{msg} = '';
    $this->{dirty} = 0;
    $this->{exists} = 0;

    if( $id  and $Pdbh ) {        # load an existing one
        $this->{idframes} = $id;
        $this->load( );
    }

    return $this;
}

#-----------------------------
sub stringify()
#-----------------------------
{
my $this = shift;

    $ret = 'ID:[' . $this->{idframes} . ']  frame_id:[' . $this->{frame_id} . ']';
    $ret .= '  user_id:['. $this->{user_id} . ']  nick:[' . $this->{user_nickname} . ']';
    $ret .= '  active:['. $this->{active} . ']  product_id:[' . $this->{product_id} . ']';
    $ret .= '  last_seen:['. $this->{last_seen} . ']  tm:[' . $this->{last_seen_tm} . ']';
    $ret .= '  exists:['. $this->{exists} . ']  dirty:[' . $this->{dirty} . ']';
    $ret .= '  msg:[' . $this->{msg} . ']';

    return $ret;
}

#-----------------------------
sub load( )
#-----------------------------
{
my $this = shift;

my $ret = 0;

    main::SysMsg($main::MSG_DEBUG, 'FAFrame:load:[' . $this->{idframes} . ']');

    if( ( $this->{dbh} ) and ( $this->{idframes} ) ) { 

            $sql = "SELECT * FROM `frames` WHERE `idframes` = ?";

            $sth = $this->{dbh}->prepare( $sql );
            if( $sth->execute( $this->{idframes} ) ) {
                if( $sth->rows > 0 ) {
                    $row = $sth->fetchrow_hashref;
                    $this->{frame_id} = $$row{'frame_id'};
                    $this->{user_id} = $$row{'user_id'};
                    $this->{user_nickname} = $$row{'user_nickname'};
                    $this->{active} = $$row{'active'};
                    $this->{product_id} = $$row{'product_id'};
                    $this->{created} = $$row{'created'};
                    $this->{last_seen} = $$row{'last_seen'};
                    $this->{last_seen_tm} = $$row{'last_seen_tm'};
                    $this->{feed_ttl} = $$row{'feed_ttl'};
                    $this->{feed_pin} = $$row{'feed_pin'};
                    $this->{item_limit} = $$row{'item_limit'};
                    $this->{shuffle_items} = $$row{'shuffle_items'};
                    $this->{security_key} = $$row{'security_key'};
                    $this->{activation_key} = $$row{'activation_key'};

                    $this->{dirty} = 0;
                    $this->{exists} = 1;
                } else {
                    $this->{dirty} = 0;
                }

                $this->{dirty} = 0;
            } else {
                $this->msg( '** Error in SELECT: ' );
            }
    }

    return $ret;
}

#-----------------------------
sub save( )
#-----------------------------
{
my $this = shift;

my $ret = 0;
my $sql = '';

    if( $this->{dbh} ) {
        if( $this->{idframes} ne 0 ) {

            if( $this->{exists} ) {
                $sql = 'UPDATE frames SET frame_id=?, user_id=?, user_nickname=?, active=?,
                            product_id=?, created=?, last_seen=?, last_seen_tm=?, feed_ttl=?,
                            feed_pin=?, item_limit=?, shuffle_items=?, security_key=?, activation_key=?
                        WHERE idframes = ?';

                $sth = $this->{dbh}->prepare( $sql ) or SysMsg($MSG_CRIT, 'FAFrames:save: ' . $DBI::errstr );
                $ret = $sth->execute( $this->{frame_id}, $this->{user_id}, $this->{user_nickname}, $this->{active},
                        $this->{product_id}, $this->{created}, $this->{last_seen}, $this->{last_seen_tm}, $this->{feed_ttl},
                        $this->{feed_pin}, $this->{item_limit}, $this->{shuffle_items}, $this->{security_key}, $this->{activation_key},
                        $this->{idframes} )
                            or SysMsg($MSG_CRIT, 'FAFrames:save: ' . $DBI::errstr );
            } else {
                $sql = "INSERT INTO frames (frame_id, user_id, user_nickname, active,
                            product_id, created, last_seen, last_seen_tm, feed_ttl,
                            feed_pin, item_limit, shuffle_items, security_key, activation_key )
                        VALUES ( ?,?,?,?,?,?,?,?,?,?,?,?,?,? )";

                $sth = $this->{dbh}->prepare( $sql ) or SysMsg($MSG_CRIT, 'FAFrames:save: ' . $DBI::errstr );
                $ret = $sth->execute( $this->{frame_id}, $this->{user_id}, $this->{user_nickname}, $this->{active},
                        $this->{product_id}, $this->{created}, $this->{last_seen}, $this->{last_seen_tm}, $this->{feed_ttl},
                        $this->{feed_pin}, $this->{item_limit}, $this->{shuffle_items}, $this->{security_key}, $this->{activation_key} )
                            or SysMsg($MSG_CRIT, 'FAFrames:save: ' . $DBI::errstr );
            }

            if( $ret) {
                $this->{dirty} = 0;
                $this->{exists} = 1;
                $ret = 1;
            }
        }
    } else {
        main::SysMsg($MSG_WARN, 'dbh not set.');
    }

    return $ret;
}

#-----------------------------
sub needsave( $ )
#-----------------------------
{
my ($this, $val) = @_;

    if( $val ) {   # set it
        $this->{dirty} = 1;
    }

    return $this->{dirty};
}

#-----------------------------
sub setattrib( $$ )
#-----------------------------
{

my ($this, $attrib, $val) = @_;

    if( !defined( $attrib ) or ( defined( $val ) and ( $val ne $$attrib ) ) ) {
        $$attrib = $val;
        $this->needsave( 1 );
     }

    return $$attrib;
}

#-----------------------------
sub setnumattrib( $$ )
#-----------------------------
{

my ($this, $attrib, $val) = @_;

    if( !defined( $$attrib ) or ( defined( $val ) and ( $val != $$attrib ) ) ) {
        $$attrib = $val;
        $this->needsave( 1 );
     }

    return $$attrib;
}

#-----------------------------
sub setboolattrib( $$ )
#-----------------------------
{
my ($this, $attrib, $val) = @_;

    if( defined( $val ) ) { $val = ( uc( $val ) eq 'Y') ? 'Y' : 'N'; }                    # 'Y' or 'N' only allowed values

    if( !defined( $$attrib ) or ( defined( $val ) and ( $val != $$attrib ) ) ) {
        $$attrib = $val;
        $this->needsave( 1 );
     }

    return $$attrib;
}
#-----------------------------
sub idframes( $ )
#-----------------------------
{
my ($this, $val) = @_;

    return $this->{idframes};
#    return $this->setattrib( \$this->{idframes}, $val );
}

#-----------------------------
sub frame_id( $ )
#-----------------------------
{
my ($this, $val) = @_;

    return $this->setattrib( \$this->{frame_id}, $val );
}

#-----------------------------
sub user_id( $ )
#-----------------------------
{
my ($this, $val) = @_;

    return $this->setnumattrib( \$this->{user_id}, $val );
}

#-----------------------------
sub user_nickname( $ )
#-----------------------------
{
my ($this, $val) = @_;

    return $this->setattrib( \$this->{user_nickname}, $val );

#-----------------------------
sub active( $ )
#-----------------------------
{
my ($this, $val) = @_;

    return $this->setboolattrib( \$this->{active}, $val );
}

#-----------------------------
sub last_seen_tm( $ )
#-----------------------------
{
my ($this, $val) = @_;

    return $this->setnumattrib( \$this->{last_seen_tm}, $val );
}

}

1;
