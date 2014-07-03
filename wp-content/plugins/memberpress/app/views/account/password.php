<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

if(isset($_GET['message']) && !empty($_GET['message']))
  if($_GET['message'] == 'success')
    echo '<div id="mepr-success-new-password">'.__('Your password has been updated.', 'memberpress').'</div>';
  else
    echo '<div id="mepr-failed-new-password">'.__('Password update failed, please be sure your passwords match and try again.', 'memberpress').'</div>';
?>

<form action="" method="post">
  <label for="mepr-new-password"><?php _e('New Password', 'memberpress'); ?><br/>
  <input type="password" name="mepr-new-password" /><br/><br/>
  
  <label for="mepr-confirm-password"><?php _e('Confirm New Password', 'memberpress'); ?><br/>
  <input type="password" name="mepr-confirm-password" /><br/><br/>
  
  <input type="submit" name="new-password-submit" value="<?php _e('Update Password', 'memberpress'); ?>" class="mepr_front_button" /> <?php _e('or', 'memberpress'); ?> <a href="<?php echo $mepr_options->account_page_url(); ?>"><?php _e('Cancel', 'memberpress'); ?></a>
</form>
<?php do_action('mepr_account_password', $mepr_current_user);
