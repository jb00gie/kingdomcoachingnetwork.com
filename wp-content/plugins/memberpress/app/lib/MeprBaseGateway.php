<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

/***** Define Exceptions *****/
class MeprGatewayException extends Exception { }
class MeprHttpException extends Exception { }
class MeprRemoteException extends Exception { }

/** Lays down the interface for Gateways in MemberPress **/
abstract class MeprBaseGateway {
  
  /** Used in the view to identify the payment method */
  public $name;
  
  /** Used in the view to label the payment method */
  public $label;

  /** The public id of the payment method **/
  public $id;

  /** The recurrence type of the payment method 'manual' or 'automatic' */
  //public $recurrence_type;
  
  /** This will be where the gateway interface will store its settings */
  public $settings;

  /** Important to determine what this gateway is capable of **/
  public $capabilities;

  /** An array of callbacks to be called on 'init' ... these will be used to
    * impelement listeners for notifiers like PayPal IPN and/or Authorize.net Silent Posts.
    *
    * This should be an array in this format:
    *
    *  array( 'action' => 'callback', 'action2' => 'callback2' ... )
    *
    * An example of this is:
    *
    *  array( 'ipn' => 'listener' )
    */
  protected $notifiers;

  /** This works just like the notifiers but is rendered as a page for the end user.
    * This can be used to render cancellation, error and any other kind of page the
    * specific gateway we're working with at the time requires.
    */
  protected $message_pages;
  
  /** The system uses this to load this if there's a payment option configured for this */
  abstract public function load($settings);
  
  /** Sets the defaults for the settings, etc */
  abstract protected function set_defaults();

  /** Returns true if a capability exists ... false if not */
  public function can($cap) {
    return in_array(trim($cap),$this->capabilities);
  }
  
  /** Returns the array of notifiers for the specific gateway */
  public function notifiers() {
    return $this->notifiers;
  }

  public function notifier($action) {
    if( isset($this->notifiers[$action]) )
      return $this->notifiers[$action];

    return false;
  }

  /** Returns the array of notifiers for the specific gateway */
  public function message_pages() {
    return $this->message_pages;
  }

  public function message_page($action) {
    if( isset($this->message_pages[$action]) )
      return $this->message_pages[$action];

    return false;
  }

  /** Returns the url of a given notifier for the current gateway */
  public function notify_url($action) {
    if(isset($this->notifiers[$action]))
      return admin_url("admin-ajax.php?plugin=mepr&pmt={$this->id}&action={$action}");

    return false;
  }

  /** Returns the url of a given message page for the current product & gateway */
  public function message_page_url($product,$action) {
    if(isset($this->message_pages[$action]))
      return $product->url("?pmt={$this->id}&action={$action}");
    return false;
  }

  /** Used to send data to a given payment gateway. In gateways which redirect
    * before this step is necessary this method should just be left blank.
    */
  abstract public function process_payment($transaction);

  /** Used to record a successful payment by the given gateway. It should have
    * the ability to record a successful payment or a failure. It is this method
    * that should be used when receiving an IPN from PayPal or a Silent Post
    * from Authorize.net.
    */
  abstract public function record_payment();

  /** This method should be used by the class to push a request to to the gateway.
    */
  abstract public function process_refund(MeprTransaction $txn);

  /** This method should be used by the class to record a successful refund from
    * the gateway. This method should also be used by any IPN requests or Silent Posts.
    */
  abstract public function record_refund();
  
  /** Used to record a successful recurring payment by the given gateway. It
    * should have the ability to record a successful payment or a failure. It is
    * this method that should be used when receiving an IPN from PayPal or a
    * Silent Post from Authorize.net.
    */
  abstract public function record_subscription_payment();

  /** Used to record a declined payment. */
  abstract public function record_payment_failure();

  /** Used to send subscription data to a given payment gateway. In gateways
    * which redirect before this step is necessary this method should just be
    * left blank.
    */
  abstract public function process_create_subscription($transaction);

  /** Used to record a successful subscription by the given gateway. It should have
    * the ability to record a successful subscription or a failure. It is this method
    * that should be used when receiving an IPN from PayPal or a Silent Post
    * from Authorize.net.
    */
  abstract public function record_create_subscription();

  abstract public function process_update_subscription($subscription_id);
  
  /** This method should be used by the class to record a successful cancellation
    * from the gateway. This method should also be used by any IPN requests or 
    * Silent Posts.
    */
  abstract public function record_update_subscription();

  /** Used to suspend a subscription by the given gateway.
    */
  abstract public function process_suspend_subscription($subscription_id);
  
  /** This method should be used by the class to record a successful suspension
    * from the gateway.
    */
  abstract public function record_suspend_subscription();

  /** Used to suspend a subscription by the given gateway.
    */
  abstract public function process_resume_subscription($subscription_id);
  
  /** This method should be used by the class to record a successful resuming of
    * as subscription from the gateway.
    */
  abstract public function record_resume_subscription();
  
  /** Used to cancel a subscription by the given gateway. This method should be used
    * by the class to record a successful cancellation from the gateway. This method
    * should also be used by any IPN requests or Silent Posts.
    */
  abstract public function process_cancel_subscription($subscription_id);
  
  /** This method should be used by the class to record a successful cancellation
    * from the gateway. This method should also be used by any IPN requests or 
    * Silent Posts.
    */
  abstract public function record_cancel_subscription();

  /** Gets called on the 'init' action after the signup form is submitted. If
    * we're using an offsite payment solution like PayPal then this method
    * will just redirect to it.
    */
  abstract public function process_signup_form($transaction);
  
  /** This gets called on wp_enqueue_script and enqueues a set of
    * scripts for use on the page containing the payment form
    */
  abstract public function enqueue_payment_form_scripts();

  /** This spits out html for the payment form on the registration / payment
    * page for the user to fill out for payment.
    */
  abstract public function display_payment_form($amount, $user, $product_id, $transaction_id);
  
  /** Validates the payment form before a payment is processed */
  abstract public function validate_payment_form($errors);

  /** This method can be overridden if necessary */
  public function process_payment_form($txn) {
    $mepr_options = MeprOptions::fetch();

    if(isset($txn) && $txn instanceof MeprTransaction) {
      $usr = $txn->user();
      $prd = $txn->product();
    }
    else
      return false;
    
    if($txn->amount <= 0.00) {
      MeprTransaction::create_free_transaction($txn);
      return true;
    }
    
    if($txn->gateway == $this->id) {
      //Even though this is a one time payment there may still be
      //a previous subscription that that was in an upgrade path
      //that needs to be cancelled. We need to look into this more here
      //and possibly create a new method that does upgrade_subscription_to_lifetime()
      //we also don't handle upgrade_lifetime_to_lifetime() right now either
      if( !$prd->is_one_time_payment() ) {
        if( $usr->is_logged_in_and_current_user() and
            ($usr->is_already_subscribed_to($prd->ID) and !$prd->simultaneous_subscriptions) ) {
          // Do nothing they're already subscribed
          $sub = $txn->subscription();
          // Blow these away ... who knows how we even got here
          $sub->destroy();
          $txn->destroy();
          return false; // TODO: We may want to throw a nice error message here at some point
        }
        else
          $this->process_create_subscription($txn);
      }
      else
        $this->process_payment($txn);

      return true;
    }
    
    return false;
  }
  
  /** Displays the form for the given payment gateway on the MemberPress Options page */
  abstract public function display_options_form();
  
  /** Validates the form for the given payment gateway on the MemberPress Options page */
  abstract public function validate_options_form($errors);

  /** This gets called on wp_enqueue_script and enqueues a set of
    * scripts for use on the front end user account page.
    * Can be overridden if custom scripts are necessary.
    */
  public function enqueue_user_account_scripts() {
  }

  /** This displays the subscription row buttons on the user account page. Can be overridden if necessary.
    */
  public function print_user_account_subscription_row_actions($sub_id) {
    global $post;

    $mepr_options = MeprOptions::fetch();
    $subscription = new MeprSubscription($sub_id);
    $product = new MeprProduct($subscription->product_id);

    // Assume we're either on the account page or some
    // page that is using the [mepr-account-form] shortcode
    $account_url   = get_permalink($post->ID); 
    $account_delim = ( preg_match( '~\?~', $account_url ) ? '&' : '?' );

    ?>
    <div class="mepr-account-row-actions">
      <?php if( $subscription->status != MeprSubscription::$pending_str and
                $subscription->status != MeprSubscription::$cancelled_str ): ?>
        <a href="<?php echo $this->https_url("{$account_url}{$account_delim}action=update&sub={$sub_id}"); ?>" class="mepr-account-row-action mepr-account-update"><?php _e('Update', 'memberpress'); ?></a>
        
        <?php if($product->group()): ?>
        <a href="<?php echo $this->http_url("{$account_url}{$account_delim}action=upgrade&sub={$sub_id}"); ?>" class="mepr-account-row-action mepr-account-upgrade"><?php _e('Upgrade', 'memberpress'); ?></a>
        <?php endif; ?>
        
        <?php if( $mepr_options->allow_suspend_subs and
                  $this->can('suspend-subscriptions') and
                  $subscription->status==MeprSubscription::$active_str ): ?>
          <a href="<?php echo $this->http_url("{$account_url}{$account_delim}action=suspend&sub={$sub_id}"); ?>" class="mepr-account-row-action mepr-account-suspend" onclick="return confirm('<?php _e('Are you sure you want to pause this subscription?', 'memberpress'); ?>');"><?php _e('Pause', 'memberpress'); ?></a>
        <?php elseif( $mepr_options->allow_suspend_subs and
                      $this->can('suspend-subscriptions') and
                      $subscription->status==MeprSubscription::$suspended_str ): ?>
          <a href="<?php echo $this->http_url("{$account_url}{$account_delim}action=resume&sub={$sub_id}"); ?>" class="mepr-account-row-action mepr-account-resume"><?php _e('Resume', 'memberpress'); ?></a>
        <?php endif; ?>

        <?php if($mepr_options->allow_cancel_subs and $this->can('cancel-subscriptions')): ?>
          <a href="<?php echo $this->http_url("{$account_url}{$account_delim}action=cancel&sub={$sub_id}"); ?>" class="mepr-account-row-action mepr-account-cancel" onclick="return confirm('<?php _e('Are you sure you want to cancel this subscription?', 'memberpress'); ?>');"><?php _e('Cancel', 'memberpress'); ?></a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <?php
  }
  
  protected function https_url($url) {
    return $this->force_ssl() ? preg_replace('!^https?:!','https:',$url) : $url;
  }

  protected function http_url($url) {
    return $this->force_ssl() ? preg_replace('!^https?:!','http:',$url) : $url;
  }

  /** Displays the update account form on the subscription account page **/
  abstract public function display_update_account_form($subscription_id, $errors=array(), $message="");
  
  /** Validates the payment form before a payment is processed */
  abstract public function validate_update_account_form($errors=array());

  /** Actually pushes the account update to the payment processor */
  abstract public function process_update_account_form($subscription_id);

  /** Returns boolean ... whether or not we should be sending in test mode or not */
  abstract public function is_test_mode();

  /** Returns boolean ... whether or not we should be forcing ssl */
  abstract public function force_ssl();
  
  protected function email_status($message, $debug)
  {
    if($debug) {
      // Send notification email to admin user (to and from the admin user)
      /* translators: In this string, %1$s is the Blog Name/Title and %2$s is the Name of the Payment Method */
      $subject = sprintf(__('[%1$s] %2$s Debug Email', 'memberpress'), get_option('blogname'), $this->name );
      MeprUtils::wp_mail_to_admin($subject, $message);
    }
  }

  /** Luhn Check Credit card algorithm */
  protected function is_credit_card_valid($number) {
    // short circuit if the cc# doesn't match any of the credit card types
    // if( $this->credit_card_type($number) ) //Need to add discover first
      // return false;

    // Strip any non-digits (useful for credit card numbers with spaces and hyphens)
    $number=preg_replace('/\D/', '', $number);
  
    // Set the string length and parity
    $number_length=strlen($number);
    $parity=$number_length % 2;
  
    // Loop through each digit and do the maths
    $total=0;
    for ($i=0; $i<$number_length; $i++) {
      $digit=$number[$i];
      // Multiply alternate digits by two
      if ($i % 2 == $parity) {
        $digit*=2;
        // If the sum is two digits, add them together (in effect)
        if ($digit > 9) {
          $digit-=9;
        }
      }
      // Total up the digits
      $total+=$digit;
    }
  
    // If the total mod 10 equals 0, the number is valid
    return ( ( $total % 10 ) == 0 );
  }

  protected function credit_card_type($number) {
    $cards = array(
        "visa" => "(4\d{12}(?:\d{3})?)",
        "amex" => "(3[47]\d{13})",
        "jcb" => "(35[2-8][89]\d\d\d{10})",
        "maestro" => "((?:5020|5038|6304|6579|6761)\d{12}(?:\d\d)?)",
        "solo" => "((?:6334|6767)\d{12}(?:\d\d)?\d?)",
        "mastercard" => "(5[1-5]\d{14})",
        "switch" => "(?:(?:(?:4903|4905|4911|4936|6333|6759)\d{12})|(?:(?:564182|633110)\d{10})(\d\d)?\d?)",
    );
    $names = array("Visa", "American Express", "JCB", "Maestro", "Solo", "Mastercard", "Switch");
    $matches = array();
    $pattern = "#^(?:".implode("|", $cards).")$#";
    $result = preg_match($pattern, str_replace(" ", "", $number), $matches);
    return ($result>0)?$names[sizeof($matches)-2]:false;
  }

  public function months_dropdown($name,$class,$selected='',$pad_zeros=false) {
    ?>
    <select <?php echo empty($name)?'':"name=\"{$name}\" "; ?>class="mepr-payment-form-select <?php echo empty($class)?'':$class; ?>">
    <?php
    for($i=1;$i<=12;$i++) {
      $i_str = $pad_zeros?sprintf("%02d",$i):$i;
      $selected_str = $selected==$i_str?' selected="selected"':''
      ?>
      <option value="<?php echo $i_str; ?>"<?php echo $selected_str; ?>><?php echo $i_str; ?></option>
      <?php
    }
    
    ?>
    </select>
    <?php
  }

  public function years_dropdown($name,$class,$selected='') {
    $year = date('Y', time());
    ?>
    <select <?php echo empty($name)?'':"name=\"{$name}\" "; ?>class="mepr-payment-form-select <?php echo empty($class)?'':$class; ?>">
    <?php
    for($i=$year;$i<=($year+9);$i++) {
      $selected_str = $selected==$i?' selected="selected"':''
      ?>
      <option value="<?php echo $i; ?>"<?php echo $selected_str; ?>><?php echo $i; ?></option>
      <?php
    }
    
    ?>
    </select>
    <?php
  }

  public function txn_count($sub=null) {
    if(!is_null($sub))
      return $sub->txn_count;
    else
      return false;
  }

  public function send_signup_notices( $txn ) {
    $params = MeprTransactionsHelper::get_email_params($txn);  
    $usr = $txn->user();

    if( !$usr->signup_notice_sent ) {
      $this->send_notices( $txn,
                           'MeprUserWelcomeEmail',
                           'MeprAdminSignupEmail' );

      $usr->signup_notice_sent = true;
      $usr->store();
    }
  }

  public function send_new_sub_notices($sub) {
    $this->send_notices( $sub, null, 'MeprAdminNewSubEmail' );
  }

  public function send_transaction_receipt_notices( $txn ) {
    $this->send_notices( $txn,
                         'MeprUserReceiptEmail',
                         'MeprAdminReceiptEmail' );
  }

  public function send_suspended_sub_notices($sub) {
    $this->send_notices( $sub,
                         'MeprUserSuspendedSubEmail',
                         'MeprAdminSuspendedSubEmail' );
  }

  public function send_resumed_sub_notices($sub) {
    $this->send_notices( $sub,
                         'MeprUserResumedSubEmail',
                         'MeprAdminResumedSubEmail' );
  }

  public function send_cancelled_sub_notices($sub) {
    $this->send_notices( $sub,
                         'MeprUserCancelledSubEmail',
                         'MeprAdminCancelledSubEmail' );
  }

  public function send_upgraded_txn_notices($txn) {
    $this->send_upgraded_sub_notices($txn);
  }

  public function send_upgraded_sub_notices($sub) {
    $this->send_notices( $sub,
                         'MeprUserUpgradedSubEmail',
                         'MeprAdminUpgradedSubEmail' );
  }

  public function send_downgraded_txn_notices($txn) {
    $this->send_downgraded_sub_notices($txn);
  }

  public function send_downgraded_sub_notices($sub) {
    $this->send_notices( $sub,
                         'MeprUserDowngradedSubEmail',
                         'MeprAdminDowngradedSubEmail' );
  }

  public function send_refunded_txn_notices($txn) {
    $this->send_notices( $txn,
                         'MeprUserRefundedTxnEmail',
                         'MeprAdminRefundedTxnEmail' );
  }

  public function send_failed_txn_notices($txn) {
    $this->send_notices( $txn,
                         'MeprUserFailedTxnEmail',
                         'MeprAdminFailedTxnEmail' );
  }

  public function send_product_welcome_notices($txn) {
    // Notifications
    $params = MeprTransactionsHelper::get_email_params($txn);
    $usr = $txn->user();

    try {
      $uemail = MeprEmailFactory::fetch( 'MeprUserProductWelcomeEmail', 'MeprBaseProductEmail',
                                         array(array('product_id'=>$txn->product_id)));
      $uemail->to = $usr->formatted_email();
      $uemail->send_if_enabled($params);
    }
    catch( Exception $e ) {
      // Fail silently for now
    }
  }

  public function send_cc_expiration_notices( $txn ) {
    $sub = $txn->subscription();

    if( $sub instanceof MeprSubscription and
        $sub->cc_expiring_before_next_payment() )
    {
      $this->send_notices( $sub,
                           'MeprUserCcExpiringEmail',
                           'MeprAdminCcExpiringEmail' );
    }
  }

  private function send_notices($obj, $user_class=null, $admin_class=null) {
    if( $obj instanceof MeprSubscription ) {
      $params = MeprSubscriptionsHelper::get_email_params($obj);
    }
    elseif( $obj instanceof MeprTransaction )
      $params = MeprTransactionsHelper::get_email_params($obj);
    else
      return false;

    $usr = $obj->user();

    try {
      if( !is_null($user_class) ) {
        $uemail = MeprEmailFactory::fetch($user_class);
        $uemail->to = $usr->formatted_email();
        $uemail->send_if_enabled($params);
      }

      if( !is_null($admin_class) ) {
        $aemail = MeprEmailFactory::fetch($admin_class);
        $aemail->send_if_enabled($params);
      }
    }
    catch( Exception $e ) {
      // Fail silently for now
    }
  }

  protected function upgraded_sub($sub) {
    $type = MeprUtils::get_sub_type($sub);
    if( $type !== false ) {
      do_action("mepr-upgraded-{$type}-sub", $sub);
      do_action("mepr-upgraded-sub", $type, $sub);
      do_action("mepr-sub-created", $type, $sub, 'upgraded');
    }
  }
  
  protected function downgraded_sub($sub) {
    $type = MeprUtils::get_sub_type($sub);
    if( $type !== false ) {
      do_action("mepr-downgraded-{$type}-sub", $sub);
      do_action("mepr-downgraded-sub", $type, $sub);
      do_action("mepr-sub-created", $type, $sub, 'downgraded');
    }
  }
  
  protected function new_sub($sub) {
    $type = MeprUtils::get_sub_type($sub);
    if( $type !== false ) {
      do_action("mepr-new-{$type}-sub", $sub);
      do_action("mepr-new-sub", $type, $sub);
      do_action("mepr-sub-created", $type, $sub, 'new');
    }
  }
}
