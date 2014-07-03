<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprArtificialGateway extends MeprBaseRealGateway {
  
  /** Used in the view to identify the gateway */
  public function __construct()
  {
    $this->name = __("Offline Payment", 'memberpress');
    $this->set_defaults();
    
    $this->capabilities = array(
      //'process-payments',
      //'create-subscriptions',
      //'process-refunds',
      //'cancel-subscriptions',
      //'update-subscriptions',
      //'suspend-subscriptions',
      //'send-cc-expirations'
    );

    // Setup the notification actions for this gateway
    $this->notifiers = array();
  }
  
  public function load($settings)
  {
    $this->settings = (object)$settings;
    $this->set_defaults();
  }
  
  protected function set_defaults() {
    if(!isset($this->settings))
      $this->settings = array();
    
    $this->settings = (object)array_merge( array( 'gateway' => 'MeprArtificialGateway',
                                                  'id' => time() . '-' . uniqid(),
                                                  'label' => '',
                                                  'manually_complete' => false,
                                                  'email' => '',
                                                  'sandbox' => false,
                                                  'debug' => false,
                                                ), (array)$this->settings );
    
    $this->id = $this->settings->id;
    $this->label = $this->settings->label;
    //$this->recurrence_type = $this->settings->recurrence_type;
  }
  
  /** Used to send data to a given payment gateway. In gateways which redirect
    * before this step is necessary this method should just be left blank.
    */
  public function process_payment($txn) {
    // Doesn't happen in test mode ... no need
  }
  
  /** Used to record a successful recurring payment by the given gateway. It
    * should have the ability to record a successful payment or a failure. It is
    * this method that should be used when receiving an IPN from PayPal or a
    * Silent Post from Authorize.net.
    */
  public function record_subscription_payment() {
    // Doesn't happen in test mode ... no need
  }

  /** Used to record a declined payment. */
  public function record_payment_failure() {
    // No need for this here
  }
  
  /** Used to record a successful payment by the given gateway. It should have
    * the ability to record a successful payment or a failure. It is this method
    * that should be used when receiving an IPN from PayPal or a Silent Post
    * from Authorize.net.
    */
  public function record_payment() {
    // This happens manually in test mode
  }

  /** This method should be used by the class to record a successful refund from
    * the gateway. This method should also be used by any IPN requests or Silent Posts.
    */
  public function process_refund(MeprTransaction $txn) {
    // This happens manually in test mode
  }

  /** This method should be used by the class to record a successful refund from
    * the gateway. This method should also be used by any IPN requests or Silent Posts.
    */
  public function record_refund() {
    // This happens manually in test mode
  }
  
  /** Used to send subscription data to a given payment gateway. In gateways
    * which redirect before this step is necessary this method should just be
    * left blank.
    */
  public function process_create_subscription($txn) {
    // This happens manually in test mode
  }
  
  /** Used to record a successful subscription by the given gateway. It should have
    * the ability to record a successful subscription or a failure. It is this method
    * that should be used when receiving an IPN from PayPal or a Silent Post
    * from Authorize.net.
    */
  public function record_create_subscription() {
    // This happens manually in test mode
  }

  public function process_update_subscription($sub_id) {
    // This happens manually in test mode
  }

  /** This method should be used by the class to record a successful cancellation
    * from the gateway. This method should also be used by any IPN requests or 
    * Silent Posts.
    */
  public function record_update_subscription() {
    // No need for this one with the artificial gateway
  }

  /** Used to suspend a subscription by the given gateway.
    */
  public function process_suspend_subscription($sub_id) {}
  
  /** This method should be used by the class to record a successful suspension
    * from the gateway.
    */
  public function record_suspend_subscription() {}

  /** Used to suspend a subscription by the given gateway.
    */
  public function process_resume_subscription($sub_id) {}
  
  /** This method should be used by the class to record a successful resuming of
    * as subscription from the gateway.
    */
  public function record_resume_subscription() {}
  
  /** Used to cancel a subscription by the given gateway. This method should be used
    * by the class to record a successful cancellation from the gateway. This method
    * should also be used by any IPN requests or Silent Posts.
    */
  public function process_cancel_subscription($sub_id) {
    // Nothing to do here
  }
  
  /** This method should be used by the class to record a successful cancellation
    * from the gateway. This method should also be used by any IPN requests or 
    * Silent Posts.
    */
  public function record_cancel_subscription() {
    // How can an offline payment be cancelled? A riddle for the ages.
  }
  
  /** This gets called on the 'init' hook when the signup form is processed ...
    * this is in place so that payment solutions like paypal can redirect
    * before any content is rendered.
  */
  public function process_signup_form($txn) {
    // Do transaction stuff
    if(isset($txn) && $txn instanceof MeprTransaction) {
      $user = new MeprUser($txn->user_id);
      $product = new MeprProduct($txn->product_id);
    }
    else
      return;

    if($txn->amount <= 0.00) {
      MeprTransaction::create_free_transaction($txn);
      return;
    }

    if($sub = $txn->subscription()) {
      // Not super thrilled about this but there are literally
      // no automated recurring profiles when paying offline
      $sub->status = MeprSubscription::$active_str;
      $sub->created_at = date('c');
      $sub->gateway = $this->id;
      $sub->subscr_id = 'ts_' . uniqid();
      $sub->store();
      $this->send_new_sub_notices($sub);
    }

    $txn->gateway = $this->id;
    $txn->trans_num = 't_' . uniqid();

    if( !$this->settings->manually_complete == 'on' and
        !$this->settings->manually_complete == true ) {
      $txn->status = MeprTransaction::$complete_str;
      $txn->store();
      $this->send_transaction_receipt_notices( $txn );
    }
    else {
      $txn->store();
      // if they're doing this manually they'll have to
      // manually change the status and send the receipt
    }

    $this->send_product_welcome_notices( $txn );
    $this->send_signup_notices( $txn );

    // Redirect to thank you page
    $mepr_options = MeprOptions::fetch();
    MeprUtils::wp_redirect($mepr_options->thankyou_page_url("trans_num={$txn->trans_num}"));
  }

  /** This gets called on wp_enqueue_script and enqueues a set of
    * scripts for use on the page containing the payment form
    */
  public function enqueue_payment_form_scripts() {
    // This happens manually in test mode
  }
  
  /** This gets called on the_content and just renders the payment form
    */
  public function display_payment_form($amount, $user, $product_id, $txn_id) {
    // This happens manually in test mode
  }
  
  /** Validates the payment form before a payment is processed */
  public function validate_payment_form($errors) {
    // This is done in the javascript with Stripe
  }
  
  /** Displays the form for the given payment gateway on the MemberPress Options page */
  public function display_options_form() {
    $mepr_options = MeprOptions::fetch();
    $manually_complete = ($this->settings->manually_complete == 'on' or $this->settings->manually_complete == true);
    ?>
    <div class="mepr-options-pane">
      <table>
        <tr>
          <td colspan="2"><input type="checkbox" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id;?>][manually_complete]"<?php echo checked($manually_complete); ?> />&nbsp;<?php _e('Admin Must Manually Complete Transactions', 'memberpress'); ?></td>
        </tr>
      </table>
    </div>
    <?php
  }
  
  /** Validates the form for the given payment gateway on the MemberPress Options page */
  public function validate_options_form($errors) {
    return $errors;
  }

  /** This gets called on wp_enqueue_script and enqueues a set of
    * scripts for use on the front end user account page.
    */
  public function enqueue_user_account_scripts() {
  }

  /** Displays the update account form on the subscription account page **/
  public function display_update_account_form($sub_id, $errors=array(), $message='') {
    // Handled Manually in test gateway
    ?>
    <p><b><?php _e('This action is not possible with the payment method used with this Subscription','memberpress'); ?></b></p>
    <?php
  }

  /** Validates the payment form before a payment is processed */
  public function validate_update_account_form($errors=array()) {
    return $errors;
  }

  /** Used to update the credit card information on a subscription by the given gateway.
    * This method should be used by the class to record a successful cancellation from
    * the gateway. This method should also be used by any IPN requests or Silent Posts.
    */
  public function process_update_account_form($sub_id) {
    // Handled Manually in test gateway
  }

  /** Returns boolean ... whether or not we should be sending in test mode or not */
  public function is_test_mode() {
    return false; // Why bother
  }

  public function force_ssl() {
    return false; // Why bother
  }

}
