<? include_once 'inc/config.php'; ?>
 <div class="right">
<?php
    if (!(isset($_SESSION['username'])) ) {
        echo '
<form id="loginform" name="loginform" method="post" action="loginproc.php">
            <div class="login_area">
                <div class="login_top"></div>
                <div class="login_bodyarea">
                    <div class="right_head">
                        <div align="center">Login</div>
                    </div>
                    <div class="right_textbox">
                        <label>';

    if ( isset($_COOKIE['registered']) ) {
        echo '<input name="username" type="text" class="righttextbox" value="' . $_COOKIE['registered'] . '" />';
    } else {
        echo '<input name="username" type="text" class="righttextbox" value="Name" />';
    }

        echo '          </label>
                    </div>
                    <div class="right_textbox">
                        <label>
                            <input name="password" type="password" class="righttextbox" value="" />
                        </label>
                    </div>
                    <div class="right_text">
                        <label>
                            <input type="checkbox" name="rememberme" value="Y" />
                        </label>
                        Remember Me
                    </div>
                    <div class="right_text">
                        <div align="center"><input type="submit" value="Login" name="login" /></div>
                    </div>
                </div>
                <div class="login_bottom"></div>
            </div>
        </form>';

        if ( (!isset($_COOKIE['registered'])) and (!isset($_COOKIE['betaregister'])) and ($GLOBALS['enable_preregister']) ) {
            echo '
<form id="preregister" name="preregisterform" method="post" action="reg2.php">
                <div class="login_area">
                    <div class="login_top"></div>
                    <div class="login_bodyarea">
                        <div class="right_head">
                            <div align="center"><strong>Register for beta </strong></div>
                        </div>
                        <div class="right_textbox">
                            <label>
                            <input name="reg_email" type="text" class="righttextbox" value="Email Id" />
                            </label>
                        </div>
                        <div class="right_text">
                            <div align="center"><input type="submit" value="Signup" name="signup"/></div>
                        </div>
                    </div>
                    <div class="login_bottom"></div>
                </div>
</form>
        ';
        }
    }
?>
</div>
