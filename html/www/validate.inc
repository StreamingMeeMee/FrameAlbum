<script type="text/javascript">
function validEmail()
{
    var x=document.getElementById('reg_email').value
    var atpos=x.indexOf("@");
    var dotpos=x.lastIndexOf(".");
    if (atpos<1 || dotpos<atpos+2 || dotpos+2>=x.length) {
        document.getElementById('emailmsg').src='/images/knobs/Attention.png';
        return false;
    } else {
        document.getElementById('emailmsg').src='/images/knobs/Valid_Green.png';
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
</script>
