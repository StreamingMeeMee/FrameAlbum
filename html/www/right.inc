<?php
    include_once 'inc/config.php';
    include_once "inc/helpers.php";
?>
 <div class="right">
<?php
    if (!(isset($_SESSION['loggedin'])) or ($_SESSION['loggedin'] != 'Y' ) ) {
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
                    </div>';
        echo '      <div class="fotter_links">
                        <div align="center"><a href="/lostpass.php">Forgot password?</a></div>
                    </div>';
        echo '  </div>
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
                        </div>';

#            if( $fbuser ) { echo $fb_btn; }

            echo '        </div>
                    <div class="login_bottom"></div>
                </div>
            </form>
        ';
        }

    }

    if ( ($_SERVER['REQUEST_URI'] != '/register.php') and featureEnabled( 'enable_fb_login' ) and ( $_SESSION['loggedin'] != 'Y' ) ) {
        echo '<div class="login_area">';
            echo '<div class="login_top"></div>';
            echo '<div class="login_bodyarea">';
                echo '<div class="right_head">';
                    echo '<div align="center"><strong>via Facebook</strong></div>';
                echo '</div>';
                echo '<div align="center">';
                    if ( isset( $_SESSION['fb_' . $GLOBALS['fb_api_key'] . '_user_id'] ) ) {
                        echo '<a href="/login.php">login as ' . $_SESSION['username'] . '</a>';
                    } else {
                        echo $fb_btn;
                    }
                echo '</div>';
            echo '</div>';

            echo '<div class="login_bottom"></div>';
        echo '</div>';
    }

?>
</div>
