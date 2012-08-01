<script type="text/javascript">
function validEmail()
{
    var x=document.getElementById('reg_email').value
    var atpos=x.indexOf("@");
    var dotpos=x.lastIndexOf(".");
    if (atpos<1 || dotpos<atpos+2 || dotpos+2>=x.length) {
        document.getElementById('emailicon').src='/images/knobs/Attention.png';
        document.getElementById('emailmsg').innerHTML='Please enter a valid email address.';
        document.getElementById('emailmsg').style.display='inline';
        return false;
    } else {
        document.getElementById('emailicon').src='/images/knobs/Valid_Green.png';
        document.getElementById('emailmsg').innerHTML='';
        document.getElementById('emailmsg').style.display='none';
        return true;
    }
}

function validEmailAddr(obj)
{
    var divname = obj.id;
    divname = divname+"_msg";
    var x=obj.value
    var atpos=x.indexOf("@");
    var dotpos=x.lastIndexOf(".");
    if (atpos<1 || dotpos<atpos+2 || dotpos+2>=x.length) {
        document.getElementById(divname).src='/images/knobs/Attention.png';
        return false;
    } else {
        document.getElementById(divname).src='/images/knobs/Valid_Green.png';
        return true;
    }
}

function validNickname()
{
    var userRegex = /^[\w\.\ ]{2,32}$/;
    var validNick = document.getElementById('nickname').value.match(userRegex);

    if ( validNick ) {
        document.getElementById('nicknamemsg').src='/images/knobs/Valid_Green.png';
        return true;
    } else {
        document.getElementById('nicknamemsg').src='/images/knobs/Attention.png';
        return false;
    }
}

function validUsername()
{
    var userRegex = /^[\w\.]{6,64}$/;
    var validUname = document.getElementById('reg_username').value.match(userRegex);

    if (validUname) {
        document.getElementById('usernameicon').src='/images/knobs/Valid_Green.png';
        document.getElementById('usernamemsg').innerHTML='';
        document.getElementById('usernamemsg').style.display='none';
        return true;
    } else {
        document.getElementById('usernameicon').src='/images/knobs/Attention.png';
        document.getElementById('usernamemsg').innerHTML='Usernames must be at least 6 characters.';
        document.getElementById('usernamemsg').style.display='inline';
        return false;
    }
}

function validZip()
{
    var zipfield=document.getElementById('reg_zip');
    var zip=zipfield.value;
    if (zip.length == 0) {           // field is blank, OK
        document.getElementById('zipicon').src='/images/knobs/Valid_Green.png';
        document.getElementById('zipmsg').style.display='none';
        return true;
    }
    if (zip.match(/^[0-9]{5}$/)) {
        document.getElementById('zipicon').src='/images/knobs/Valid_Green.png';
        document.getElementById('zipmsg').style.display='none';
        return true;
    }
    zip=zip.toUpperCase();
    if (zip.match(/^[A-Z][0-9][A-Z][0-9][A-Z][0-9]$/)) {
        document.getElementById('zipicon').src='/images/knobs/Valid_Green.png';
        document.getElementById('zipmsg').innerHTML='';
        document.getElementById('zipmsg').style.display='none';
        return true;
    }
    if (zip.match(/^[A-Z][0-9][A-Z].[0-9][A-Z][0-9]$/)) {
        document.getElementById('zipicon').src='/images/knobs/Valid_Green.png';;
        document.getElementById('zipmsg').innerHTML='';
        document.getElementById('zipmsg').style.display='none';
        return true;
    }

    document.getElementById('zipicon').src='/images/knobs/Attention.png';;
    document.getElementById('zipmsg').innerHTML='Please enter a valid ZIP code.';
    document.getElementById('zipmsg').style.display='block';
    return false;
}

function validPasswd()
{
    var userRegex = /^[\w\.]{6,64}$/;
    var pwd1 =  document.getElementById('reg_passwd1').value;
    var pwd2 =  document.getElementById('reg_passwd2').value
    var validPwd1 = document.getElementById('reg_passwd1').value.match(userRegex);
    var validPwd2 = document.getElementById('reg_passwd2').value.match(userRegex);

    if ( validPwd1 && validPwd2 && (pwd1 == pwd2) ) {
        document.getElementById('passwdicon').src='/images/knobs/Valid_Green.png';
        document.getElementById('passwdmsg').innerHTML='';
        document.getElementById('passwdmsg').style.display='none';
        return true;
    } else if ( !validPwd1 || !validPwd2 ) {
        document.getElementById('passwdicon').src='/images/knobs/Attention.png';
        document.getElementById('passwdmsg').innerHTML='Passwords must be at least 6 characters.';
        document.getElementById('passwdmsg').style.display='inline';
        return false;
    } else if ( pwd1 != pwd2 ) {
        document.getElementById('passwdicon').src='/images/knobs/Attention.png';
        document.getElementById('passwdmsg').innerHTML='Passwords must match.';
        document.getElementById('passwdmsg').style.display='inline';
        return false;
    } else {
        document.getElementById('passwdicon').src='/images/knobs/Attention.png';
        document.getElementById('passwdmsg').innerHTML='Passwords must match and be at least 6 characters.';
        document.getElementById('passwdmsg').style.display='inline';
        return false;
    }
}

</script>
