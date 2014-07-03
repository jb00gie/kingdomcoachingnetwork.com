<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<form name="mepr_loginform" id="mepr_loginform" action="<?php echo $login_url; ?>" method="post">
  <p>
    <label><strong><?php echo ($mepr_options->username_is_email)?__('Username or E-mail', 'memberpress'):__('Username', 'memberpress'); ?></strong><br/>
    <input type="text" name="log" id="user_login" value="<?php echo (isset($_POST['log'])?$_POST['log']:''); ?>" /></label><br/>
    <label><strong><?php _e('Password', 'memberpress'); ?></strong><br/>
    <input type="password" name="pwd" id="user_pass" value="<?php echo (isset($_POST['pwd'])?$_POST['pwd']:''); ?>" /></label><br/>
    <label><input name="rememberme" type="checkbox" id="rememberme" value="forever"<?php echo (isset($_POST['rememberme'])?' checked="checked"':''); ?> /> <?php _e('Remember Me', 'memberpress'); ?></label>
  </p>
  <p class="submit">
    <input type="submit" name="wp-submit" id="wp-submit" class="button-primary mepr-share-button mepr_front_button" value="<?php _e('Log In', 'memberpress'); ?>" />
    <input type="hidden" name="redirect_to" value="<?php echo esc_html($redirect_to); ?>" />
    <input type="hidden" name="mepr_process_login_form" value="true" />
    <input type="hidden" name="mepr_is_login_page" value="<?php echo (is_page($login_page_id))?'true':'false'; ?>" />
  </p>
</form>
<p class="mepr-login-actions">
  <?php if(false): //Maybe we should rethink this a bit :) //if(get_option('users_can_register') && !$mepr_options->disable_wp_registration_form): ?>
    <a href="<?php echo $signup_url; ?>"><?php _e('Register', 'memberpress'); ?></a>&nbsp;|
  <?php endif; ?>
  <a href="<?php echo $forgot_password_url; ?>"><?php _e('Lost Password?', 'memberpress'); ?></a>
</p>
