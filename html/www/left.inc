<!-- left nav -->
  <div class="left">
    <div class="left_menutop"></div>
    <div class="left_menu_area">
      <div align="right">
<?php
    if ( isset($_SESSION['loggedin']) and ( $_SESSION['loggedin'] == 'Y' ) ) {
        echo '          <a href="/usermain.php" id="leftnav_userhome" class="left_menu">Home</a><br />';
        if (isset($_SESSION['isadmin']) and ($_SESSION['isadmin'] == 'Y')) {
            echo '          <a href="/admin" id="leftnav_admin" class="left_menu">Admin</a><br />';
        }
    } else {
        echo '          <a href="/" id="leftnav_home" class="left_menu">Home</a><br />';
    }

    if (!isset($_COOKIE['betaregister']) AND (!isset($_SESSION['username'])) ) {
        echo '          <a href="/register.php" id="leftnav_register" class="left_menu">Register</a><br />';
    }

    if (  isset($_SESSION['loggedin']) and ( $_SESSION['loggedin'] == 'Y' ) ) {
        echo '          <a href="/logout.php" id="leftnav_logout" class="left_menu">Logout</a><br />';
    }
?>
          <a href="/activate.php" id="leftnav_activate" class="left_menu">Activate a Frame</a><br />
          <a href="/about.php" id="leftnav_about" class="left_menu">About us</a><br />
          <a href="/faq.php" id="leftnav_faq" class="left_menu">FAQ</a><br />
          <a href="/tos.php" id="leftnav_tos" class="left_menu">Terms of Use</a><br />
          <a href="/contact.php" id="leftnav_contact" class="left_menu">Contact us</a>
      </div>

      <div class="button_area">
        <g:plusone annotation="inline" width="" callback="FrameAlbum"></g:plusone>
      </div>

    </div>
<script type="text/javascript">
<?php
    if (  isset($_SESSION['loggedin']) and ( $_SESSION['loggedin'] == 'Y' ) ) {
        echo '   mpmetrics.track_links("#leftnav_userhome", "leftnav click", {"type": "userhome"});';
    } else {
        echo '   mpmetrics.track_links("#leftnav_home", "leftnav click", {"type": "home"});';
    }

    if (!isset($_COOKIE['betaregister']) AND (!isset($_SESSION['username'])) ) {
        echo '   mpmetrics.track_links("#leftnav_register", "leftnav click", {"type": "register"});';
    }

    if (  isset($_SESSION['loggedin']) and ( $_SESSION['loggedin'] == 'Y' ) ) {
        echo '   mpmetrics.track_links("#leftnav_logout", "leftnav click", {"type": "logout"});';
    }
?>
    mpmetrics.track_links("#leftnav_activate", "leftnav click", {"type": "activation"});
    mpmetrics.track_links("#leftnav_about", "leftnav click", {"type": "about"});
    mpmetrics.track_links("#leftnav_faq", "leftnav click", {"type": "faq"});
    mpmetrics.track_links("#leftnav_tos", "leftnav click", {"type": "tos"});
    mpmetrics.track_links("#leftnav_contact", "leftnav click", {"type": "contact"});
</script>

  </div>
<!-- end of left -->
