<? include_once 'inc/config.php'; ?>
<!-- topheader -->
 <div id="topheader">
   <div class="logo"></div>
<?php
    if ( !( isset( $_SESSION['loggedin'] ) ) or ( $_SESSION['loggedin'] != 'Y' ) ) {        # this is the 'logged out' top menu
        echo '<div class="menu_area">';
        if ( $GLOBALS['enable_register'] ) {
            echo '<a href="/register.php" class="ideas">register <span class="menu_text"><br />new users</span></a>';
        }
        echo '<a href="/activate.php" class="works">Activate <span class="menu_text"><br />activate a frame</span></a> <a href="/login.php" class="links">Login <span class="menu_text"><br />existing memebers</span></a></div>';
        echo '</div>
        <script type="text/javascript">
            mpmetrics.track_links(".ideas", "topnav click", {"type": "register"});
            mpmetrics.track_links(".works", "topnav click", {"type": "activate"});
            mpmetrics.track_links(".links", "topnav click", {"type": "login"});
        </script>';
    } else {                                                                                # this is the 'logged in' top menu
        echo '<div class="menu_area"><a href="/activate.php" class="works">Activate <span class="menu_text"><br />activate a frame</span></a> <a href="/logout.php" class="links">Logout <span class="menu_text"><br />existing memebers</span></a></div>
        </div>
        <script type="text/javascript">
            mpmetrics.track_links(".works", "topnav click", {"type": "activate"});
            mpmetrics.track_links(".links", "topnav click", {"type": "logout"});
        </script>';
    }
?>
<!-- end topheader -->
