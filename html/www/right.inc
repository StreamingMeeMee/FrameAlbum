<? include_once 'inc/config.php'; ?>
 <div class="right">
<?php
    if (!(isset($_SESSION['username'])) ) {
        echo '
            <form id="loginform" name="loginform" method="post" action="loginproc.php">';
    if( isset( $_REQUEST['redir'] ) ) { echo '<input name="redir" type="hidden" value="' . $_REQUEST['redir'] . '"/>'; }
        echo '  <div class="login_area">
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
        echo '<input name="username" type="text" class="righttextbox" value="User Name" />';
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
                            <input type="checkbox" name="rememberme" value="Y" checked="' . ( isset($_COOKIE['registered']) ? 'checked' : '' ) . '"/>
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

        if ( ($_SERVER['REQUEST_URI'] != '/register.php') and (!isset($_COOKIE['betaregister'])) and ($GLOBALS['enable_register']) ) {
            echo '
            <form id="register" name="registerform" method="post" action="/register.php">
                <div class="login_area">
                    <div class="login_top"></div>
                    <div class="login_bodyarea">
                        <div class="right_head">
                            <div align="center"><strong>Register New User</strong></div>
                        </div>
                        <div class="right_textbox">
                            <label>
                            <input name="reg_email" type="text" class="righttextbox" value="Email Address" />
                            <input name="stage" type="hidden" value="1" />
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
