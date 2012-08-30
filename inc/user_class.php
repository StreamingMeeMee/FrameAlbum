<?php
#---------------------------------------
# User class - a model of a single user
#
# 2012-aug-14 - TimC
#   - First Go
#
# 2012-aug-25 - TimC
#   - Add user level PIN.  Use a default for new frames assigned to this user
#   - Allow '?' as a value for email_conf; email has been sent but not confirmed
#
# 2012-aug-29 - TimC
#   - fix some DB field names when loading a user
#---------------------------------------

class User 
{
    protected $dbh = null;

    private $iduser = 0;

    private $name = NULL;

    public $active = 'N';

    public $email = NULL;

    private $email_conf = 'N';

    private $pin = NULL;

    private $admin = 'N';

    public $zip = NULL;

    public $date_registered = '';

    public $last_login = NULL;

    private $token = NULL;

    private $fb_auth = NULL;

    private $fb_auth_expire = NULL;

    private $fb_uid = NULL;

    private $dirty = 0;         # =1 - values changed, save required

#----------------------------
function __construct( $Pdbh, $uid=0, $name='', $email='', $active='N', $zip='' )
#----------------------------
{
    if( isset( $uid ) and isset( $Pdbh ) and ( $uid > 0 ) ) {        # load an existing one
        $this->load( $Pdbh, $uid );
    } else {
        $this->dbh = $Pdbh;
        $this->iduser = 0;
        $this->name = $name;
        $this->active = $active;
        $this->email = $email;
        $this->pin = rand(1, 9999);         # some frames allow only 4 digit PINs
        $this->admin = 'N';
        $this->zip = $zip;
        $this->needsave( 1 );               # Force a save
    }

    return $this;
}

#-----------------------------
public function stringify()
#-----------------------------
{
    $ret = 'ID:[' . $this->iduser . ']  Name:[' . $this->name . ']';
    $ret .= '  Email:[' . $this->email . ']  Email-Conf:[' . $this->email_conf . ']';
    $ret .= '  PIN:[' . $this->pin . ']';
    $ret .= '  Active:[' . $this->active . ']';
    $ret .= '  Admin:[' . $this->admin . ']';
    $ret .= '  ZIP:[' . $this->zip . ']  Registered:[' . $this->registered . ']';
    $ret .= '  Last:[' . $this->last_login . ']  Token:['. $this->token . ']';
    $ret .= '  FB UID:[' . $this->fb_uid . ']  FB Auth:[' . $this->fb_auth . ']';
    $ret .= '  FB Auth Expire:[' . $this->fb_auth_expire . ']';
    $ret .= '  Dirty:['. $this->dirty . ']';

    return $ret;
}

#-----------------------------
public function load( $Pdbh, $uid )
#-----------------------------
{
$ret = false;

    if( isset( $uid ) and isset( $Pdbh ) and ( $uid > 0 ) ) {        # load an existing one
        $this->dbh = $Pdbh;

        $uid = prepDBVal( $uid ); 
        $sql = "SELECT * FROM users WHERE idusers=$uid";
        $sth = $this->dbh->prepare( $sql );
        if( $sth->execute() ) {
            $row = $sth->fetch( PDO::FETCH_ASSOC );
            $this->iduser = $row['idusers'];
            $this->name = $row['username'];
            $this->active = $row['active'];
            $this->email = $row['email'];
            $this->email_conf = $row['email_conf'];
            $this->pin = $row['pin'];
            $this->zip = $row['ZIP'];
            $this->date_registered = $row['date_registered'];
            $this->last_login =  $row['last_login'];
            $this->admin =  $row['admin'];
            $this->token =  $row['token'];
            $this->fb_auth =  $row['fb_auth'];
            $this->fb_auth_expire = $row['fb_auth_expire'];
            $this->fb_uid =  $row['fb_user_id'];
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

        if( $this->iduser == 0 ) {

            $sql = "INSERT INTO users
                (username, active, email, email_conf, ZIP, date_registered,
                admin, token, fb_auth, fb_auth_expire, fb_user_id, pin)
                VALUES (" . q($this->name) . ", " . q($this->active) . ", " . q($this->email) . ", " . q($this->email_conf) . ", "
             . q($this->zip) . ", now(), "
             . q($this->admin) . ", " . q($this->token) . ", " . q($this->fb_auth) . ", " . q($this->fb_auth_expire) . ", "
             . q($this->fb_uid) . ", " . q($this->pin) . ")"; 
        } else {
            $sql = "UPDATE users SET
                username=".q($this->name).", 
                active=".q($this->active). ', ZIP='.q($this->zip).', email='.q($this->email).', email_conf='.q($this->email_conf).', 
                last_login='.q($this->last_login).', admin='.q($this->admin).', token='.q($this->token).', 
                fb_auth='.q($this->fb_auth).', fb_auth_expire='.q($this->fb_auth_expire).', fb_user_id='.q($this->fb_uid).',
                pin='.q($this->pin).'  
                WHERE idusers='.q($this->iduser).' LIMIT 1';
        }

        $sth = $this->dbh->prepare( $sql );
        if( $sth->execute() ) {
            $this->dirty = 0;

            if( $this->iduser == 0 ) {
                $this->iduser = $this->dbh->lastInsertId();         # grab the id of a newly added user

                $l = new EventLog( 8 );                             # log the new user
                $l->user_id( $this->iduser );
                $l->event_msg( 'New user added. [' . $this->name . ']' );
                $l->save();
            } else {
                $l = new EventLog( 12 );                             # log the updated user
                $l->user_id( $this->iduser );
                $l->event_msg( 'User Updated. [' . $this->stringify() . ']' );
                $l->save();
            }

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

    if( isset( $this->dbh ) and ( $this->iduser != 0 ) ) {
        $sql = "DELETE FROM users WHERE idusers=" . q($this->iduser) . " LIMIT 1";
        $sth = $this->dbh->prepare( $sql );
        if( $sth->execute() ) {
            $this->dirty = 0;

            $l = new EventLog( 10 );                             # log the new user
            $l->user_id( $this->iduser );
            $l->event_msg( 'User Deleted. [' . $this->stringify() . ']' );
            $l->save();

            $this->iduser = 0;

            $ret = true;
        }
    }

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
public function iduser( )
#-----------------------------
{

    return $this->iduser;
}

#-----------------------------
public function date_registered( )
#-----------------------------
{

    return $this->date_registered;
}

#-----------------------------
public function last_login( )
#-----------------------------
{

    return $this->last_login;
}

#-----------------------------
public function username( $val=NULL )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->name ) ) {   # set it
        $this->name = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->name;
    }

    return $ret;
}

#-----------------------------
public function email( $val=NULL )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->email ) ) {   # set it
        $this->email = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->email;
    }

    return $ret;
}

#-----------------------------
public function email_conf ( $val=NULL )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ( strtoupper($val) != $this->email_conf ) ) {   # set it
        $val = strtoupper($val);
        if ( $val != 'Y' and $val != '?' ) { $val = 'N'; }
        $this->email_conf = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->email_conf;
    }

    return $ret;
}

#-----------------------------
public function pin( $val=NULL )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->pin ) ) {   # set it
        $this->pin = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->pin;
    }

    return $ret;
}

#-----------------------------
public function active ( $val=NULL )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ( strtoupper($val) != $this->active ) ) {   # set it
        $val = strtoupper($val);
        if ( $val != 'Y' and $val != 'N' and $val != 'R' ) { $val = 'N'; }
        $this->active = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->active;
    }

    return $ret;
}

#-----------------------------
public function isAdmin ( $val=NULL )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ( strtoupper($val) != $this->admin ) ) {   # set it
        $val = strtoupper($val);
        if ( $val != 'Y' and $val != 'N' ) { $val = 'N'; }
        $this->admin = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = ( ( $this->admin == 'Y' ) AND ( $this->active == 'Y' ) ) ? 'Y' : 'N';
    }

    return $ret;
}

#-----------------------------
public function token( $val=NULL )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->token ) ) {   # set it
        if( $val == 'xx' ) { $val = NULL; }             # set to NULL if magic value is passed
        $this->token= $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->token;
    }

    return $ret;
}

#-----------------------------
public function fb_uid( $val=NULL )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->fb_uid ) ) {   # set it
        $this->fb_uid = $val;
        $this->fb_auth = null;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->fb_uid;
    }

    return $ret;
}

#-----------------------------
public function fb_auth( $val=NULL )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->fb_auth ) ) {   # set it
        $this->fb_auth = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->fb_auth;
    }

    return $ret;
}

#-----------------------------
public function fb_auth_expire( $val=NULL )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->fb_auth_expire ) ) {   # set it
        $this->fb_auth_expire = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->fb_auth_expire;
    }

    return $ret;
}

#-----------------------------
public function htmlform( $val=NULL )
#-----------------------------
{
$ret = '';

    $ret .= '<form action="' . $val . '" method="post">';
    $ret .= '<input type="hidden" name="iduser" value="' . $this->iduser() . '" />';

    $ret .= '<table border="1">';
    $ret .= '<tr><td>Active:</td><td>' . optionActiveStatus( $this->active() , 'active') . '</td></tr>';
    $ret .= '<tr><td>Login:</td><td><input type="text" name="username" value="' . $this->username() . '" /></td></tr>';
    $ret .= '<tr><td>EMail:</td><td><input type="text" name="email" value="' . $this->email() . '" /></td></tr>';
    $ret .= '<tr><td>Email Conf:</td><td>' . optionActiveStatus( $this->email_conf, 'email_conf' ) . '</td></tr>';
    $ret .= '<tr><td>PIN:</td><td><input type="text" name="pin" value="' . $this->pin() . '" /></td></tr>';
    $ret .= '<tr><td>Admin:</td><td>' . optionActiveStatus( $this->admin, 'admin' ) . '</td></tr>';
    $ret .= '<tr><td>Registered:</td><td>' . $this->date_registered() . '</td></tr>';
    $ret .= '<tr><td>Last Login:</td><td>' . $this->last_login() . '</td></tr>';
    $ret .= '<tr><td>Token:</td><td>' . $this->token() . '</td></tr>';
    $ret .= '<tr><td>FB UID:</td><td><input type="text" name="fb_uid" value="' . $this->fb_uid() . '" /></td></tr>';
    $ret .= '<tr><td>FB Auth:</td><td><input type="text" name="fb_auth" value="' . $this->fb_auth() . '" /></td></tr>';
    $ret .= '</table>';

    $ret .= '<input type="submit" value="Submit" />';
    $ret .= '</form>';

    return $ret;
}

#-----------------------------
public function procform( $val=NULL )
#-----------------------------
{
$ret = '';
$msg = '';
$redir = '';

    if( $this->iduser == $val['iduser'] ) {       # yup, this form is for this user 
        $this->active( $val['active'] );
        $this->username( $val['username'] );
        $this->email( $val['email'] );
        $this->email_conf( $val['email_conf'] );
        $this->pin( $val['pin'] );
        $this->admin( $val['admin'] );
        $this->fb_id( $val['fb_id'] );
        $this->fb_auth( $val['fb_auth'] );

#        $ret .= 'BeforeSave:<pre>' . $this->stringify() . '</pre>';
        if( $this->needsave() ) { $this->save(); $msg='User data updated.'; }

    } else {
        $msg = 'Form data does not match loaded user data.';
    }

    return array( $msg, $ret, $redir);
}

#-----------------------------
public function passwordreset( )
#-----------------------------
{
$ret = false;
$msg = '';

    $this->token( $this->genUserToken() );
    $this->save( );                             # force a save now

    list($ret, $msg) = $this->sendPwdResetMsg( $this->token() );

    $l = new EventLog( 19 );                             # log the new user
    $l->user_id( $this->iduser );
    $l->event_msg( 'Password reset requested.  Sent to:[' . $this->email . ']' );
    $l->save();

    return array ($ret, $msg);

}

#----------------------------
public function sendPwdResetMsg( $val = NULL )
#----------------------------
{
$ret = true;
$msg = '';

    $txt = "Hi there.  Someone requested a reset of your FrameAlbum password.  

You can reset your password by visiting " . $GLOBALS['www_url_root'] . '/lostpass.php?tok=' . $val . ".

If you did not request a password reset you can ignore this message and login with your existing password.

If you have any questions, drop me an email at " . $GLOBALS['email_from'] . ".";

    list ($ret, $msg) = sendEmail( $GLOBALS['email_from'], $this->email, 'FrameAlbum password reset request', $txt, $this->iduser );

    if (!ret) {
        $msg = 'There was a problem sending a message to ' . $this->email . '.  You may not receive your Welcome email message.';
    }

    return array ($ret, $msg);
}

#----------------------------
private function genUserToken( )
#----------------------------
{
    $tok = md5( $this->email() . time() . $GLOBALS['pwsalt'] );

    return $tok;
}

#----------------------------
public function password( $val=NULL )
#----------------------------
# This function forces a save to ensure that we have a valid user ID then performs an update on that ID
#----------------------------
{
$ret = FALSE;

    if( isset( $val ) ) {
        if( $this->needsave( ) ) { $this->save(); }

        $sql = "UPDATE users SET
                passwd=AES_ENCRYPT('$val', '" . $GLOBALS['pwsalt'] . "') " .
                ' WHERE idusers='.q($this->iduser).' LIMIT 1';

        $sth = $this->dbh->prepare( $sql );
        if( $sth->execute() ) {
            $this->dirty = 0;

            $l = new EventLog( 17 );                             # log the new user
            $l->user_id( $this->iduser );
            $l->event_msg( 'Password changed. [' . $sql . ']' );
            $l->save();

            $ret = TRUE;
        }
    }

    return $ret;
}

#----------------------------
public function sendWelcomeMsg()
#----------------------------
{
    $txt = "Welcome to FrameAlbum.  You may now access your account at " . $GLOBALS['www_url_root'] . ".

Once you have logged in you may add your frame(s) and then define the channels that will be sent to your frame.

If you have any questions, drop me an email at " . $GLOBALS['email_from'] . ".";

    list ($ret, $msg) = sendEmail( $GLOBALS['email_from'], $this->email, 'Welcome to FrameAlbum', $txt, $this->iduser );

    if (!ret) {
        $msg = 'There was a problem sending a message to ' . $this->email . '.  You may not receive your Welcome email message.';
    }

    return array ($ret, $msg);
}

} #-- class
?>
