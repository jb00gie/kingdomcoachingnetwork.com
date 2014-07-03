<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprAdminReceiptEmail extends MeprBaseOptionsAdminEmail {
  /** Set the default enabled, title, subject & body */
  public function set_defaults() {
    $mepr_options = MeprOptions::fetch();
    $this->to = $mepr_options->admin_email_addresses;

    $this->title = __('<b>Payment Receipt</b> Notice','memberpress');
    $this->description = __('This email is sent to you when a payment comes through on your membership site', 'memberpress'); 
    $this->ui_order = 2;

    $enabled = $use_template = $this->show_form = true;
    $subject = __('** Payment of {$payment_amount} from {$user_full_name}', 'memberpress');
    $body = $this->body_partial();

    $this->defaults = compact( 'enabled', 'subject', 'body', 'use_template' );
    $this->variables = MeprTransactionsHelper::get_email_vars();
  }

  public function body_partial() {
    ob_start();

?>
<div id="header" style="width: 680px; padding 0; margin: 0 auto; text-align: left;">
  <h1 style="font-size: 30px; margin-bottom: 0;"><?php _e('Payment from {$user_full_name}', 'memberpress'); ?></h1>
  <h2 style="margin-top: 0; color: #999; font-weight: normal;"><?php _e('{$product_name} &ndash; {$trans_num}', 'memberpress'); ?></h2>
</div>
<div id="body" style="width: 600px; background: white; padding: 40px; margin: 0 auto; text-align: left;">
  <table style="clear: both;" class="transaction">
    <tr><th style="text-align: left;"><?php _e('Payment Amount:', 'memberpress'); ?></th><td>{$payment_amount}</td></tr>
    <tr><th style="text-align: left;"><?php _e('Invoice Number:', 'memberpress'); ?></th><td>{$invoice_num}</td></tr>
    <tr><th style="text-align: left;"><?php _e('Invoice Date:', 'memberpress'); ?></th><td>{$trans_date}</td></tr>
    <tr><th style="text-align: left;"><?php _e('Transaction:', 'memberpress'); ?></th><td>{$trans_num}</td></tr>
    <tr><th style="text-align: left;"><?php _e('Payment System:', 'memberpress'); ?></th><td>{$trans_gateway}</td></tr>
  </table>
  <table style="clear: both; width: 100%;" class="labels">
    <tr>
      <td style="vertical-align: top;">
        <fieldset style="border: none; border-top: 1px solid #dedede; margin: 20px 40px 20px 0;" class="billing">
          <legend style="display: block; font-weight: bold; color: #999;"><?php _e('Billed to', 'memberpress'); ?></legend>
          <address style="font-style: normal;">
            <div class="address_name" style="display: block; font-size: 115%;"><big>{$user_full_name}</big></div>
            <div class="address_email" style="display: block;">{$user_email} (<b>{$user_login}</b>)</div>
            <div class="address_address" style="display: block;">{$user_address}</div>
          </address>
        </fieldset>
      </td>
    </tr>
  </table>
</div>
<?php

    return ob_get_clean();
  }
}

