<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<h3><?php _e('Request a Password Reset', 'memberpress'); ?></h3>
<form name="mepr_forgot_password_form" id="mepr_forgot_password_form" action="" method="post">
	<p>
		<label><?php _e('Enter Your Username or Email Address', 'memberpress'); ?><br/>
		<input type="text" name="mepr_user_or_email" id="mepr_user_or_email" class="input" value="<?php echo isset($mepr_user_or_email)?$mepr_user_or_email:''; ?>" tabindex="600" style="width: auto; min-width: 250px; font-size: 12px; padding: 4px;" /></label>
	</p>
	<p class="submit">
		<input type="submit" name="wp-submit" id="wp-submit" class="button-primary mepr-share-button mepr_front_button" value="<?php _e('Request Password Reset', 'memberpress'); ?>" tabindex="610" />
		<input type="hidden" name="mepr_process_forgot_password_form" value="true" />
	</p>
</form>
