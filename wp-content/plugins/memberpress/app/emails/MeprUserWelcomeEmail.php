<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprUserWelcomeEmail extends MeprBaseOptionsUserEmail {
  /** Set the default enabled, title, subject & body */
  public function set_defaults() {
    $this->title = __('<b>Welcome Email</b>','memberpress');
    $this->description = __('This email is sent welcome a new user when she initially signs up for your membership site with a completed purchase.', 'memberpress');
    $this->ui_order = 0;

    $enabled = $use_template = $this->show_form = true;
    $subject = __('** Welcome to {$blog_name}', 'memberpress');
    $body = $this->body_partial();

    $this->defaults = compact( 'enabled', 'subject', 'body', 'use_template' );
    $this->variables = MeprTransactionsHelper::get_email_vars();
  }

  public function body_partial() {
    ob_start();

?>
<div id="header" style="width: 680px; padding 0; margin: 0 auto; text-align: left;">
  <h1 style="font-size: 30px; margin-bottom:4px;"><?php _e('Welcome {$user_first_name}!', 'memberpress'); ?></h1>
</div>
<div id="body" style="width: 600px; background: white; padding: 40px; margin: 0 auto; text-align: left;">
  <div id="receipt">
    <div class="section" style="display: block; margin-bottom: 24px;"><?php _e('You can login here: {$login_page}', 'memberpress'); ?></div>
    <div class="section" style="display: block; margin-bottom: 24px;"><?php _e('Using this username and password:', 'memberpress'); ?></div>
    <div class="section" style="display: block; margin-bottom: 24px;">
      <table style="clear: both;" class="transaction">
        <tr><th style="text-align: left;"><?php _e('Username:', 'memberpress'); ?></th><td>{$username}</td></tr>
        <tr><th style="text-align: left;"><?php _e('Password:', 'memberpress'); ?></th><td><?php _e('*** Password you set during signup ***', 'memberpress'); ?></td></tr>
      </table>
    </div>
    <div class="section" style="display: block; margin-bottom: 24px;"><?php _e('Cheers!', 'memberpress'); ?></div>
    <div class="section" style="display: block; margin-bottom: 24px;"><?php _e('The {$blog_name} Team', 'memberpress'); ?></div>
  </div>
</div>
<?php

    return ob_get_clean();
  }
}

