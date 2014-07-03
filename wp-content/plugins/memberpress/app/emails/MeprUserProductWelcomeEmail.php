<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprUserProductWelcomeEmail extends MeprBaseProductEmail {
  /** Set the default enabled, title, subject & body */
  public function set_defaults() {
    $mepr_options = MeprOptions::fetch();

    $this->title = __('Product-Specific Welcome Email to User','memberpress');
    $this->description = __('This email is sent when this product is purchased.', 'memberpress');
    $this->ui_order = 1;

    $enabled = false;
    $use_template = $this->show_form = true;
    $subject = __('** Thanks for Purchasing {$product_name}', 'memberpress');
    $body = $this->body_partial();

    $this->defaults = compact( 'enabled', 'subject', 'body', 'use_template' );
    $this->variables = MeprTransactionsHelper::get_email_vars();
  }

  public function body_partial() {
    ob_start();

?>
<div id="header" style="width: 680px; padding 0; margin: 0 auto; text-align: left;">
  <h1 style="font-size: 30px; margin-bottom: 4px;"><?php _e('Thanks for Purchasing {$product_name}', 'memberpress'); ?></h1>
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

