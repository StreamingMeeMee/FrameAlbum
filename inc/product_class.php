<?php
#---------------------------------------
# Product class - a model of a single frame product
#
# 2012-aug-16 - TimC
#   - First Go
#
#---------------------------------------

class Product 
{
    protected $dbh = null;

    private $idproduct = 0;

    private $productid = NULL;

    private $manuf = '';

    private $manuf_website = '';

    public $model = '';

    public $model_num = '';

    public $size = NULL;

    public $hres = NULL;

    private $vres = NULL; 

    private $custom_rss_support = '?';

    private $fc_support = '?';

    private $upnp_support = '?';

    private $internal_mem = NULL;

    private $mem_cards = '';

    private $pic_formats = '';

    private $vid_formats = '';

    private $aud_formats = '';
 
    private $active = 'T';      # test mode

    private $dirty = 0;         # =1 - values changed, save required

#----------------------------
function __construct( $Pdbh, $id=0, $prodid='' )
#----------------------------
{

    if( isset( $id ) and isset( $Pdbh ) and ( $id > 0 ) ) {        # load an existing one
        $this->load( $Pdbh, $id );
    } else {
        $this->dbh = $Pdbh;
        $this->idproduct = 0;
        $this->productid = $prodid;

        $this->needsave( 1 );               # Force a save
    }

    return $this;
}

#-----------------------------
public function stringify()
#-----------------------------
{
    $ret = 'ID:[' . $this->idproduct . ']  ProductID:[' . $this->productid . ']  Manuf:[' . $this->manuf . ']  Website:[' . $this->manuf_website . ']';
    $ret .= '  Model:[' . $this->model . ']  ModelNum:[' . $this->model_num . ']';
    $ret .= '  Size:[' . $this->size . ']  Hres:[' . $this->hres . ']  Vres:[' . $this->vres . ']';
    $ret .= '  Custom RSS:[' . $this->custom_rss_shuffle . ']  FC Support:[' . $this->fc_support . ']  UPNP:[' . $this->upnp_support . ']';
    $ret .= '  Mem:['. $this->internal_mem . ']  Cards:[' . $this->mem_cards . ']  Pic Formats:['. $this->pic_formats . ']  Vid Formats:[' . $this->vid_formats . ']';
    $ret .= '  Audio Formats:[' . $this->aud_formats . ']';
    $ret .= ' Dirty:['. $this->dirty . ']';

    return $ret;
}

#-----------------------------
public function load( $Pdbh, $id )
#-----------------------------
{
$ret = false;

    if( isset( $id ) and isset( $Pdbh ) and ( $id > 0 ) ) {        # load an existing one
        $this->dbh = $Pdbh;

        $id = prepDBVal( $id ); 
        $sql = "SELECT * FROM product_ids WHERE idproduct=$id";
        $sth = $this->dbh->prepare( $sql );
        if( !$sth ) { echo 'SQL PREPARE FAILED:['. print_r( $sth->errorInfo(), true ) . ']'; }
        if( $sth->execute() ) {
            $row = $sth->fetch( PDO::FETCH_ASSOC );
            if( !$row ) { echo 'FETCH FAILED:['. print_r($sth->errorInfo(), true) .']'; }
            $this->idproduct = $row['idproduct'];
            $this->productid = $row['productid'];
            $this->manuf = $row['manuf'];
            $this->manuf_website = $row['manuf_website'];
            $this->model = $row['model'];
            $this->model_num = $row['model_num'];
            $this->size = $row['size'];
            $this->hres = $row['hres'];
            $this->vres = $row['vres'];
            $this->custom_rss_support = $row['custom_rss_support'];
            $this->fc_support = $row['fc_support'];
            $this->upnp_support = $row['upnp_support'];
            $this->active = $row['active'];
            $this->internal_mem = $row['internal_mem'];
            $this->mem_cards =  $row['mem_cards'];
            $this->pic_formats = $row['pic_formats'];
            $this->vid_formats = $row['vid_formats'];
            $this->aud_formats = $row['aud_formats'];
            $this->dirty = 0;

            $ret = true;

        } else {
            echo 'SQL EXECUTE FAILED:';
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
        if( $this->idproduct == 0 ) {
            $sql = "INSERT INTO product_ids 
                (productid, manuf, manuf_website, model, model_num,
                size, hres, vres, custom_rss_support, fc_support,
                upnp_support, active, internal_mem, mem_cards,
                pic_formats, vid_formats, aud_formats)
                VALUES (" . q($this->productid) . ", " . q($this->manuf) . ", " . q($this->manuf_website) . ", " . q($this->model) . ", " . q($this->model_num) . ", " 
             .  q($this->size) . ", " . q($this->hres) . ", " . q($this->vres) . ", ". q($this->custom_rss_support) . ", " . q($this->fc_support) . ", " . q($this->upnp_support) . ", "
             .  q($this->active) . ", " . q($this->internal_mem) . ", " . q($this->mem_cards) . ", " . q($this->pic_formats) . ", "
             .  q($this->vid_formats) . ", " . q($this->aud_formats) . ")";
        } else {
            $sql = "UPDATE product_ids SET
                productid=" . q($this->productid) . ", manuf=" . q($this->manuf) . ", 
                manuf_website=" . q($this->manuf_website) . ", model=" . q($this->model) . ", model_num=" . q($this->model_num) .", 
                size=" . q($this->size) . ", hres=" . q($this->hres) . ", vres=" . q($this->vres) . ", 
                custom_rss_support=" . q($this->custom_rss_support) . ", fc_support=" . q($this->fc_support) . ",
                upnp_support=" .  q($this->upnp_support) . ", active=" . q($this->active) . ", internal_mem=" . q($this->internal_mem) . ", mem_cards=" . 
                q($this->mem_cards) . ", pic_formats=" . q($this->pic_formats) . ", vid_formats=" . q($this->vid_formats) . ", aud_formats=" . q($this->aud_formats) .
                " WHERE idproduct=$this->idproduct LIMIT 1";
        }

        $sth = $this->dbh->prepare( $sql );
        if( $sth->execute() ) {
            $this->dirty = 0;

            if( $this->idproduct == 0 ) { $this->idproduct = $this->dbh->lastInsertId(); }        # grab the id of a newly added user

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

    if( isset( $this->dbh ) and ( $this->idproduct != 0 ) ) {
        $sql = "DELETE FROM product_ids WHERE idproduct=$this->idproduct LIMIT 1";

        $sth = $this->dbh->prepare( $sql );
        if( $sth->execute() ) {
            $this->dirty = 0;
            $this->idproduct = 0;

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
public function htmlform( $val=NULL )
#-----------------------------
{
$ret = '';

    $ret .= '<form action="' . $val . '" method="post">';
    $ret .= '<input type="hidden" name="idproduct" value="' . $this->idproduct() . '" />';

    $ret .= '<table border="1">';
    $ret .= '<tr><td>Active:</td><td>' . optionActiveStatus( $this->active() , 'active', 'T') . '</td></tr>';
    $ret .= '<tr><td>Manuf:</td><td><input type="text" name="manuf" value="' . $this->manuf() . '" /></td></tr>';
    $ret .= '<tr><td>Model:</td><td><input type="text" name="model" value="' . $this->model() . '" /></td></tr>';
    $ret .= '<tr><td>Model Num:</td><td><input type="text" name="model_num" value="' . $this->model_num() . '" /></td></tr>';
    $ret .= '<tr><td>Website:</td><td><input type="text" name="manuf_website" value="' . $this->manuf_website() . '" /></td></tr>';
    $ret .= '<tr><td>Size:</td><td><input type="text" name="size" value="' . $this->size() . '" /></td></tr>';
    $ret .= '<tr><td>HRes:</td><td><input type="text" name="hres" value="' . $this->hres() . '" /></td></tr>';
    $ret .= '<tr><td>VRes:</td><td><input type="text" name="vres" value="' . $this->vres() . '" /></td></tr>';
    $ret .= '<tr><td>Custom RSS:</td><td>' . optionActiveStatus( $this->custom_rss() , 'custom_rss', '?') . '</td></tr>';
    $ret .= '<tr><td>FrameChannel:</td><td>' . optionActiveStatus( $this->fc_support() , 'fc_support', '?') . '</td></tr>';;
    $ret .= '<tr><td>UPNP:</td><td>' . optionActiveStatus( $this->upnp_support(), 'upnp_support', '?') . '</td></tr>';;
    $ret .= '<tr><td>Mem:</td><td><input type="text" name="internal_mem" value="' . $this->internal_mem() . '" /></td></tr>';
    $ret .= '<tr><td>Cards</td><td><input type="text" name="mem_cards" value="' . $this->mem_cards() . '" /></td></tr>';
    $ret .= '<tr><td>Pic Formats:</td><td><input type="text" name="pic_formats" value="' . $this->pic_formats() . '" /></td></tr>';
    $ret .= '<tr><td>Vid Formats:</td><td><input type="text" name="vid_formats" value="' . $this->vid_formats() . '" /></td></tr>';
    $ret .= '<tr><td>Aud Formats:</td><td><input type="text" name="aud_formats" value="' . $this->aud_formats() . '" /></td></tr>';
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

    if( $this->idproduct == $val['idproduct'] ) {       # yup, this form is for this product
        $this->manuf( $val['manuf'] );
        $this->model( $val['model'] );
        $this->model_num( $val['model_num'] );
        $this->manuf_website( $val['manuf_website'] );
        $this->size( $val['size'] );
        $this->hres( $val['hres'] );
        $this->vres( $val['vres'] );
        $this->internal_mem( $val['internal_mem'] );
        $this->mem_cards( $val['mem_cards'] );
        $this->pic_formats( $val['pic_formats'] );
        $this->vid_formats( $val['vid_formats'] );
        $this->aud_formats( $val['aud_formats'] );

#        $ret .= 'BeforeSave:<pre>' . $this->stringify() . '</pre>';
        if( $this->needsave() ) { $this->save(); $msg='Frame product data updated.'; }

    } else {
        $msg = 'Form data does not match loaded product data.';
    }

    return array( $msg, $ret, $redir);
}

#-----------------------------
public function idproduct( )
#-----------------------------
{
$ret = '';

    $ret = $this->idproduct;

    return $ret;
}

#-----------------------------
public function manuf( $val=NULL )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->manuf ) ) {   # set it
        $this->manuf = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->manuf;
    }

    return $ret;
}

#-----------------------------
public function manuf_website( $val=null )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->manuf_website ) ) {   # set it
        $this->manuf_website = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->manuf_website;
    }

    return $ret;
}

#-----------------------------
public function model( $val=null )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->model ) ) {   # set it
        $this->model = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->model;
    }

    return $ret;
}


#-----------------------------
public function model_num( $val=null )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->model_num ) ) {   # set it
        $this->model_num = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->model_num;
    }

    return $ret;
}

#-----------------------------
public function productID( $val=null )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->productid ) ) {   # set it
        $this->productid = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->productid;
    }

    return $ret;
}

#-----------------------------
public function size( $val=null )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->size ) ) {   # set it
        $this->size = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->size;
    }

    return $ret;
}

#-----------------------------
public function hres( $val=null )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->hres ) ) {   # set it
        $this->hres = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->hres;
    }

    return $ret;
}

#-----------------------------
public function vres( $val=null )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->vres ) ) {   # set it
        $this->vres = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->vres;
    }

    return $ret;
}

#-----------------------------
public function custom_rss( $val=null )
#-----------------------------
{
$ret = '';

   if( isset( $val ) ) {
        if( ( $val != 'Y' ) and ( $val != 'N' ) ) { $val = '?'; }
        if( $val != $this->custom_rss_support ) {   # set it
            $this->custom_rss_support= $val;
            $this->needsave( 1 );
        }
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->custom_rss_support;
    }

    return $ret;
}

#-----------------------------
public function fc_support( $val=null )
#-----------------------------
{
$ret = '';

   if( isset( $val ) ) {
        if( ( $val != 'Y' ) and ( $val != 'N' ) ) { $val = '?'; }
        if( $val != $this->fc_support ) {   # set it
            $this->fc_support= $val;
            $this->needsave( 1 );
        }
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->fc_support;
    }

    return $ret;
}

#-----------------------------
public function upnp_support( $val=NULL )
#-----------------------------
{
$ret = '';

    if( isset( $val ) ) {
        if( ( $val != 'Y' ) and ( $val != 'N' ) ) { $val = '?'; }
        if( $val != $this->upnp_support ) {   # set it
            $this->upnp_support = $val;
            $this->needsave( 1 );
        }
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->upnp_support;
    }

    return $ret;
}

#-----------------------------
public function active( $val=NULL )
#-----------------------------
{
$ret = '';

    if( isset( $val ) ) {
        if( ( $val != 'Y' ) and ( $val != 'N' ) ) { $val = 'T'; }
        if( $val != $this->active ) {   # set it
            $this->active = $val;
            $this->needsave( 1 );
        }
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->active;
    }

    return $ret;
}

#-----------------------------
public function internal_mem( $val=NULL )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->internal_mem ) ) {   # set it
        $this->internal_mem = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->internal_mem;
    }

    return $ret;
}

#-----------------------------
public function mem_cards( $val=null )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->mem_cards ) ) {   # set it
        $this->mem_cards = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->mem_cards;
    }

    return $ret;
}

#-----------------------------
public function pic_formats( $val=null )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->pic_formats ) ) {   # set it
        $this->pic_formats = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->pic_formats;
    }

    return $ret;
}

#-----------------------------
public function vid_formats( $val=null )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->vid_formats ) ) {   # set it
        $this->vid_formats = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->vid_formats;
    }

    return $ret;
}


#-----------------------------
public function aud_formats( $val=null )
#-----------------------------
{
$ret = '';

    if( isset( $val ) and ($val != $this->aud_formats ) ) {   # set it
        $this->aud_formats = $val;
        $this->needsave( 1 );
        $ret = $val;
    } else {                                            # simply return the current val
        $ret = $this->aud_formats;
    }

    return $ret;
}

} #-- class
?>
