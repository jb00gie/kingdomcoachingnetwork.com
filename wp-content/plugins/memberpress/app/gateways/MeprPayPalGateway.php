<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprPayPalGateway extends MeprBaseRealGateway {
  // This is stored with the user meta & the subscription meta
  public static $paypal_token_str = '_mepr_paypal_token';

  /** Used in the view to identify the gateway */
  public function __construct()
  {
    $this->name = __("PayPal", 'memberpress');
    $this->set_defaults();
    
    $this->capabilities = array(
      'process-payments',
      'process-refunds',
      'create-subscriptions',
      'cancel-subscriptions',
      'update-subscriptions',
      'suspend-subscriptions',
      'resume-subscriptions'
      //'send-cc-expirations'
    );

    // Setup the notification actions for this gateway
    $this->notifiers = array( 'ipn' => 'listener',
                              'cancel' => 'cancel_handler',
                              'return' => 'return_handler' );
    $this->message_pages = array( 'cancel' => 'cancel_message' );
  }

  public function load($settings)
  {
    $this->settings = (object)$settings;
    $this->set_defaults();
  }

  protected function set_defaults() {
    if(!isset($this->settings))
      $this->settings = array();

    $this->settings = (object)array_merge(array('gateway' => 'MeprPayPalGateway',
                                                'id' => time() . '-' . uniqid(),
                                                'label' => '',
                                                'api_username' => '',
                                                'api_password' => '',
                                                'signature' => '',
                                                'sandbox' => false,
                                                'debug' => false), (array)$this->settings);

    $this->id = $this->settings->id;
    $this->label = $this->settings->label;

    if($this->is_test_mode()) {
      $this->settings->url     = 'https://www.sandbox.paypal.com/webscr';
      $this->settings->api_url = 'https://api-3t.sandbox.paypal.com/nvp';
    }
    else {
      $this->settings->url = 'https://www.paypal.com/webscr';
      $this->settings->api_url = 'https://api-3t.paypal.com/nvp';
    }

    $this->settings->api_version = 69;

    // An attempt to correct people who paste in spaces along with their credentials
    $this->settings->api_username = trim($this->settings->api_username);
    $this->settings->api_password = trim($this->settings->api_password);
    $this->settings->signature    = trim($this->settings->signature);
  }

  /** Listens for an incoming connection from PayPal and then handles the request appropriately. */
  public function listener()
  {
    $_POST = stripslashes_deep($_POST);
    $this->email_status("PayPal IPN Recieved\n" . MeprUtils::object_to_string($_POST, true) . "\n", $this->settings->debug);

    if($this->validate_ipn()) { return $this->process_ipn(); }

    return false;
  }

  private function validate_ipn()
  {
    // Set the command that is used to validate the message
    $_POST['cmd'] = "_notify-validate";

    // We need to send the message back to PayPal just as we received it
    $params = array(
      'body'      => $_POST,
      'headers'   => array('connection' => 'close'),
      'httpversion' => 1.1,
      'sslverify' => false,
      'timeout'   => 30
    );

    if ( !function_exists('wp_remote_post') )
      require_once(ABSPATH . WPINC . '/http.php');

    $resp = wp_remote_post( $this->settings->url, $params );

    // Put the $_POST data back to how it was so we can pass it to the action
    unset($_POST['cmd']);

    // If the response was valid, check to see if the request was valid
    if ( !is_wp_error($resp) and
         $resp['response']['code'] >= 200 and
         $resp['response']['code'] < 300 and
         (strcmp( $resp['body'], "VERIFIED") == 0))
      return true;

    $this->email_status( "IPN Verification Just failed:\nIPN:\n" .
                          MeprUtils::object_to_string($_POST, true) .
                          "PayPal Response:\n" .
                          MeprUtils::object_to_string($resp),
                          $this->settings->debug );

    return false;
  }

  private function process_ipn() {
    if( isset($_POST['txn_type']) and strtolower($_POST['txn_type'])=='recurring_payment' )
      $this->record_subscription_payment();
    else if( ( isset($_POST['txn_type']) and
               in_array( strtolower( $_POST['txn_type'] ),
                         array( 'recurring_payment_skipped',
                                'subscr_failed' ) ) ) or
             ( isset($_POST['payment_status']) and
               in_array( strtolower($_POST['payment_status']),
                         array('denied','expired','failed') ) ) )
      $this->record_payment_failure();
    else if( isset($_POST['txn_type']) and
             strtolower($_POST['txn_type'])=='recurring_payment_profile_cancel' )
      $this->record_cancel_subscription();
    else if( isset($_POST['txn_type']) and
             strtolower($_POST['txn_type'])=='recurring_payment_suspended' )
      $this->record_suspend_subscription();
    else if(isset($_POST['parent_txn_id']) and !isset($_POST['txn_type'])) {
      if(in_array(strtolower($_POST['payment_status']),array('refunded','reversed','voided')))
        return $this->record_refund();
    }
  }

  /** Used to record a successful recurring payment by the given gateway. It
    * should have the ability to record a successful payment or a failure. It is
    * this method that should be used when receiving an IPN from PayPal or a
    * Silent Post from Authorize.net.
    */
  public function record_subscription_payment() {
    if(!isset($_POST['recurring_payment_id']))
      return;

    $sub = MeprSubscription::get_one_by_subscr_id($_POST['recurring_payment_id']);

    if($sub) {
      $first_txn = new MeprTransaction($sub->first_txn_id);

      $txn = new MeprTransaction();
      $txn->user_id    = $first_txn->user_id;
      $txn->product_id = $first_txn->product_id;
      $txn->coupon_id  = $first_txn->coupon_id;
      $txn->gateway    = $this->id;
      $txn->amount     = $_POST['mc_gross'];
      $txn->trans_num  = $_POST['txn_id'];
      $txn->txn_type   = MeprTransaction::$payment_str;
      $txn->status     = MeprTransaction::$complete_str;
      $txn->response   = json_encode($_POST);
      $txn->subscription_id = $sub->ID;
      //$txn->expires_at = MeprUtils::ts_to_mysql_date(strtotime($_POST['next_payment_date']));
      $txn->store();

      // We shouldn't need to do this now ... the status should only be modified when the sub is modified
      //$sub->status = MeprSubscription::$active_str;
      //$sub->gateway = $this->id;
      //$sub->store();

      // Not waiting for an IPN here bro ... just making it happen even though
      // the total occurrences is already capped in record_create_subscription()
      $sub->limit_payment_cycles();

      $this->email_status( "Subscription Transaction\n" .
                           MeprUtils::object_to_string($txn->rec, true),
                           $this->settings->debug );

      //Not sure why these are getting loaded here
      $prd = $txn->product();
      $usr = $txn->user();

      $this->send_transaction_receipt_notices( $txn );

      return $txn;
    }

    return false;
  }

  /** Used to record a declined payment. */
  public function record_payment_failure() {
    if( isset($_POST['txn_id']) and
        $txn_res = MeprTransaction::get_one_by_trans_num($_POST['txn_id']) and
        is_object($txn_res) and isset($txn_res->id) ) {
      $txn = new MeprTransaction($txn_res->id);
      $txn->status = MeprTransaction::$failed_str;
      $txn->store();
    }
    else if( isset($_POST['recurring_payment_id']) and
             $sub = MeprSubscription::get_one_by_subscr_id($_POST['recurring_payment_id']) ) {
      $first_txn = $sub->first_txn();
      $latest_txn = $sub->latest_txn();

      $txn = new MeprTransaction();
      $txn->amount = ( isset($_POST['mc_gross']) ? $_POST['mc_gross'] : ( isset($_POST['amount']) ? $_POST['amount'] : 0.00 ) );
      $txn->user_id = $sub->user_id;
      $txn->product_id = $sub->product_id;
      $txn->coupon_id = $first_txn->coupon_id;
      $txn->txn_type = MeprTransaction::$payment_str;
      $txn->status = MeprTransaction::$failed_str;
      $txn->subscription_id = $sub->ID;
      $txn->response = json_encode($_POST);
      // if txn_id isn't set then just use uniqid
      $txn->trans_num = ( isset($_POST['txn_id']) ? $_POST['txn_id'] : uniqid() );
      $txn->gateway = $this->id;
      $txn->store();

      // Don't need this ... it's redundant
      //$sub->status = MeprSubscription::$active_str;
      //$sub->gateway = $this->id;

      $sub->expire_txns(); //Expire associated transactions for the old subscription
      $sub->store();
    }
    else
      return false; // Nothing we can do here ... so we outta here

    $this->send_failed_txn_notices($txn);

    return $txn;
  }

  /** Used to send data to a given payment gateway. In gateways which redirect
    * before this step is necessary this method should just be left blank.
    */
  public function process_payment($txn) {
    $mepr_options = MeprOptions::fetch();
    $prd = $txn->product();
    $sub = $txn->subscription();
    $usr = $txn->user();
    $tkn = $_REQUEST['token'];
    $pid = $_REQUEST['PayerID'];
    $args = array( "TOKEN" => $tkn,
                   "PAYMENTREQUEST_0_PAYMENTACTION" => "Sale",
                   "PAYMENTREQUEST_0_AMT" => $txn->amount,
                   "PAYMENTREQUEST_0_CURRENCYCODE" => $mepr_options->currency_code,
                   "PAYERID" => $pid );

    $this->email_status("DoExpressCheckoutPayment Request:\n".MeprUtils::object_to_string($args,true)."\n", $this->settings->debug);

    $res = $this->send_nvp_request('DoExpressCheckoutPayment', $args);
    
    $this->email_status("DoExpressCheckoutPayment Response:\n".MeprUtils::object_to_string($res,true)."\n", $this->settings->debug);

    $_REQUEST['paypal_response'] = $res;
    $_REQUEST['transaction'] = $txn;

    return $this->record_payment();
  }

  /** Used to record a successful payment by the given gateway. It should have
    * the ability to record a successful payment or a failure. It is this method
    * that should be used when receiving an IPN from PayPal or a Silent Post
    * from Authorize.net.
    */
  public function record_payment() {
    if(!isset($_REQUEST['paypal_response']) or !isset($_REQUEST['transaction']))
      return false;

    $res = $_REQUEST['paypal_response'];
    $txn = $_REQUEST['transaction'];

    if( $txn->status == MeprTransaction::$complete_str )
      return false;

    if(isset($res['PAYMENTINFO_0_PAYMENTSTATUS'])) {
      if(strtolower($res['ACK'])=='success' and strtolower($res['PAYMENTINFO_0_PAYMENTSTATUS'])=='completed') {
        $txn->trans_num  = $res['PAYMENTINFO_0_TRANSACTIONID'];
        $txn->txn_type   = MeprTransaction::$payment_str;
        $txn->status     = MeprTransaction::$complete_str;
        $txn->response   = json_encode($res);

        // This will only work before maybe_cancel_old_sub is run
        $upgrade = $txn->is_upgrade();
        $downgrade = $txn->is_downgrade();

        $txn->maybe_cancel_old_sub();
        $txn->store();

        $this->email_status("Transaction\n" . MeprUtils::object_to_string($txn->rec, true) . "\n", $this->settings->debug);

        $prd = $txn->product();

        if( $prd->period_type=='lifetime' ) {
          if( $upgrade ) {
            $this->upgraded_sub($txn);
            $this->send_upgraded_txn_notices( $txn );
          }
          else if( $downgrade ) {
            $this->downgraded_sub($txn);
            $this->send_downgraded_txn_notices( $txn );
          }
          else {
            $this->new_sub($txn);
          }

          $this->send_product_welcome_notices($txn);
          $this->send_signup_notices( $txn );
        }

        $this->send_transaction_receipt_notices( $txn );

        return $txn;
      }
    }

    return false;
  }

  /** This method should be used by the class to record a successful refund from
    * the gateway. This method should also be used by any IPN requests or Silent Posts.
    */
  public function process_refund(MeprTransaction $txn) {
    $mepr_options = MeprOptions::fetch();

    $args = array( "TRANSACTIONID" => $txn->trans_num,
                   "REFUNDTYPE" => "Full",
                   "CURRENCYCODE" => $mepr_options->currency_code );

    $this->email_status("RefundTransaction Request:\n".MeprUtils::object_to_string($args,true)."\n", $this->settings->debug);
    $res = $this->send_nvp_request('RefundTransaction', $args);
    $this->email_status("RefundTransaction Response:\n".MeprUtils::object_to_string($res,true)."\n", $this->settings->debug);

    if( !isset($res['ACK']) or strtoupper($res['ACK']) != 'SUCCESS' )
      throw new MeprGatewayException( __('The refund was unsuccessful. Please login at PayPal and refund the transaction there.', 'memberpress') );

    $_POST['parent_txn_id'] = $txn->id;
    return $this->record_refund();
  }
  
  /** This method should be used by the class to record a successful refund from
    * the gateway. This method should also be used by any IPN requests or Silent Posts.
    */
  public function record_refund() {
    $obj = MeprTransaction::get_one_by_trans_num($_POST['parent_txn_id']);
    
    if(!is_null($obj) && (int)$obj->id > 0)
    {
      $txn = new MeprTransaction($obj->id);
      
      // Seriously ... if txn was already refunded what are we doing here?
      if($txn->status == MeprTransaction::$refunded_str) { return $txn; }
      
      $txn->status = MeprTransaction::$refunded_str;
      
      $this->email_status("Processing Refund: \n" . MeprUtils::object_to_string($_POST) . "\n Affected Transaction: \n" . MeprUtils::object_to_string($txn), $this->settings->debug);
      
      $txn->store();
      
      $this->send_refunded_txn_notices($txn);
      
      return $txn;
    }
    
    return false;
  }
  
  /** Used to send subscription data to a given payment gateway. In gateways
    * which redirect before this step is necessary this method should just be
    * left blank.
    */
  public function process_create_subscription($txn) {
    $mepr_options = MeprOptions::fetch();
    $prd = $txn->product();
    $sub = $txn->subscription();
    $usr = $txn->user();
    $tkn = get_post_meta($sub->ID, self::$paypal_token_str, true);
    
    //IMPORTANT - PayPal txn will fail if the descriptions do not match exactly
    //so if you change the description here you also need to mirror it
    //inside of process_signup_form().
    $desc = $this->paypal_desc($txn);
    
    // Default to 0 for infinite occurrences
    $total_occurrences = $sub->limit_cycles ? $sub->limit_cycles_num : 0;
    
    //Having issues with subscription start times for our friends in Australia and New Zeland
    //There doesn't appear to be any fixes available from PayPal -- so we'll have to allow them to modify
    //the start time via this filter if it comes to that.
    $gmt_utc_time = apply_filters('mepr-paypal-express-subscr-start-ts', current_time('timestamp', 1), $this);
    
    $args = array( "TOKEN" => $tkn,
                   "PROFILESTARTDATE" => gmdate('Y-m-d\TH:i:s\Z', $gmt_utc_time),
                   "DESC" => $desc,
                   "BILLINGPERIOD" => $this->paypal_period($prd->period_type),
                   "BILLINGFREQUENCY" => $prd->period,
                   "TOTALBILLINGCYCLES" => $total_occurrences,
                   "AMT" => MeprUtils::format_float($txn->amount),
                   "CURRENCYCODE" => $mepr_options->currency_code,
                   "EMAIL" => $usr->user_email,
                   "L_PAYMENTREQUEST_0_ITEMCATEGORY0" => 'Digital', // TODO: Assume this for now?
                   "L_PAYMENTREQUEST_0_NAME0" => $prd->post_title,
                   "L_PAYMENTREQUEST_0_AMT0" => MeprUtils::format_float($txn->amount),
                   "L_PAYMENTREQUEST_0_QTY0" => 1 );

    if($sub->trial) {
      $args = array_merge( array( "TRIALBILLINGPERIOD" => "Day",
                                  "TRIALBILLINGFREQUENCY" => $sub->trial_days,
                                  "TRIALAMT" => $sub->trial_amount,
                                  "TRIALTOTALBILLINGCYCLES" => 1 ),
                           $args );
    }

    $this->email_status("Paypal Create Subscription \$args:\n".MeprUtils::object_to_string($args,true)."\n", $this->settings->debug);

    $res = $this->send_nvp_request('CreateRecurringPaymentsProfile', $args);
    
    $_REQUEST['paypal_response'] = $res;
    $_REQUEST['transaction'] = $txn;
    $_REQUEST['subscription'] = $sub;

    return $this->record_create_subscription();
  }
  
  /** Used to record a successful subscription by the given gateway. It should have
    * the ability to record a successful subscription or a failure. It is this method
    * that should be used when receiving an IPN from PayPal or a Silent Post
    * from Authorize.net.
    */
  public function record_create_subscription() {
    $res = $_REQUEST['paypal_response'];
    $sub = $_REQUEST['subscription'];
    $this->email_status("Paypal Create Subscription Response \$res:\n".MeprUtils::object_to_string($res,true)."\n", $this->settings->debug);

    if( isset($res['L_ERRORCODE0']) and intval($res['L_ERRORCODE0'])==10004 ) {
      $this->send_digital_goods_error_message();
      return false;
    }

    if(isset($res['PROFILESTATUS']) and strtolower($res['PROFILESTATUS'])=='activeprofile') {
      $txn = $sub->first_txn();
      $old_amount = $txn->amount;
      $txn->trans_num  = $res['PROFILEID'];
      $txn->status     = MeprTransaction::$confirmed_str;
      $txn->txn_type   = MeprTransaction::$subscription_confirmation_str;
      $txn->amount     = 0.00; // Just a confirmation txn
      $txn->response   = (string)$sub;

      // At the very least the subscription confirmation transaction gives
      // the user a 24 hour grace period so they can log in even before the
      // paypal transaction goes through (paypal batches txns at night)
      $mepr_options = MeprOptions::fetch();

      $trial_days = ( $sub->trial ? $sub->trial_days : $mepr_options->grace_init_days );
      $txn->expires_at = MeprUtils::ts_to_mysql_date(time()+MeprUtils::days($trial_days));
      $txn->store();

      $sub->subscr_id=$res['PROFILEID'];
      $sub->status=MeprSubscription::$active_str;
      $sub->created_at = date('c');

      // This will only work before maybe_cancel_old_sub is run
      $upgrade = $sub->is_upgrade();
      $downgrade = $sub->is_downgrade();

      $sub->maybe_cancel_old_sub();
      $sub->store();

      $this->email_status( "Subscription Transaction\n" .
                           MeprUtils::object_to_string($txn->rec, true),
                           $this->settings->debug );

      $txn->amount = $old_amount; // Artificially set the old amount for notices

      if($upgrade) {
        $this->upgraded_sub($sub);
        $this->send_upgraded_sub_notices($sub);
      }
      else if($downgrade) {
        $this->downgraded_sub($sub);
        $this->send_downgraded_sub_notices($sub);
      }
      else {
        $this->new_sub($sub);
        $this->send_new_sub_notices($sub);
      }

      $this->send_product_welcome_notices($txn);
      $this->send_signup_notices( $txn );

      return array('subscription' => $sub, 'transaction' => $txn);
    }
  }
  
  /** Used to cancel a subscription by the given gateway. This method should be used
    * by the class to record a successful cancellation from the gateway. This method
    * should also be used by any IPN requests or Silent Posts.
    *
    * With PayPal, we bill the outstanding amount of the previous subscription,
    * cancel the previous subscription and create a new subscription
    */
  public function process_update_subscription($sub_id) {
    // Account info updated on PayPal.com
  }

  /** This method should be used by the class to record a successful cancellation
    * from the gateway. This method should also be used by any IPN requests or 
    * Silent Posts.
    */
  public function record_update_subscription() {
    // Account info updated on PayPal.com
  }

  /** Used to suspend a subscription by the given gateway.
    */
  public function process_suspend_subscription($sub_id) {
    $sub = new MeprSubscription($sub_id);
    $this->update_paypal_payment_profile($sub_id,'Suspend');

    $_REQUEST['recurring_payment_id'] = $sub->subscr_id;
    $this->record_suspend_subscription();
  }

  /** This method should be used by the class to record a successful suspension
    * from the gateway.
    */
  public function record_suspend_subscription() {
    $subscr_id = $_REQUEST['recurring_payment_id'];
    $sub = MeprSubscription::get_one_by_subscr_id($subscr_id);

    if(!$sub) { return false; }

    // Seriously ... if sub was already suspended what are we doing here?
    if($sub->status == MeprSubscription::$suspended_str) { return $sub; }

    $sub->status = MeprSubscription::$suspended_str;
    $sub->store();

    $this->send_suspended_sub_notices($sub);

    return $sub;
  }

  /** Used to suspend a subscription by the given gateway.
    */
  public function process_resume_subscription($sub_id) {
    $sub = new MeprSubscription($sub_id);
    $this->update_paypal_payment_profile($sub_id,'Reactivate');

    $_REQUEST['recurring_payment_id'] = $sub->subscr_id;
    $this->record_resume_subscription();
  }

  /** This method should be used by the class to record a successful resuming of
    * as subscription from the gateway.
    */
  public function record_resume_subscription() {
    $subscr_id = $_REQUEST['recurring_payment_id'];
    $sub = MeprSubscription::get_one_by_subscr_id($subscr_id);

    if(!$sub) { return false; }

    // Seriously ... if sub was already active what are we doing here?
    if($sub->status == MeprSubscription::$active_str) { return $sub; }

    $sub->status = MeprSubscription::$active_str;
    $sub->store();

    $txn = new MeprTransaction();
    $txn->subscription_id = $sub->ID;
    $txn->trans_num  = $sub->subscr_id . '-' . uniqid();
    $txn->status     = MeprTransaction::$confirmed_str;
    $txn->txn_type   = MeprTransaction::$subscription_confirmation_str;
    $txn->amount     = 0.00; // Just a confirmation txn
    $txn->response   = (string)$sub;
    $txn->expires_at = MeprUtils::ts_to_mysql_date(time()+MeprUtils::days(1));
    $txn->store();

    $this->send_resumed_sub_notices($sub);

    return $sub;
  }

  /** Used to cancel a subscription by the given gateway. This method should be used
    * by the class to record a successful cancellation from the gateway. This method
    * should also be used by any IPN requests or Silent Posts.
    */
  public function process_cancel_subscription($sub_id) {
    $sub = new MeprSubscription($sub_id);

    // Should already expire naturally at paypal so we have no need
    // to do this when we're "cancelling" because of a natural expiration
    if(!isset($_REQUEST['expire']))
      $this->update_paypal_payment_profile($sub_id,'Cancel');

    $_REQUEST['recurring_payment_id'] = $sub->subscr_id;
    $this->record_cancel_subscription();
  }

  /** This method should be used by the class to record a successful cancellation
    * from the gateway. This method should also be used by any IPN requests or 
    * Silent Posts.
    */
  public function record_cancel_subscription() {
    $subscr_id = $_REQUEST['recurring_payment_id'];
    $sub = MeprSubscription::get_one_by_subscr_id($subscr_id);

    if(!$sub) { return false; }

    // Seriously ... if sub was already cancelled what are we doing here?
    if($sub->status == MeprSubscription::$cancelled_str) { return $sub; }

    $sub->status = MeprSubscription::$cancelled_str;
    $sub->store();

    if(isset($_REQUEST['expire']))
      $sub->limit_reached_actions();

    if(!isset($_REQUEST['silent']) || ($_REQUEST['silent']==false))
      $this->send_cancelled_sub_notices($sub);

    return $sub;
  }
  
  /** This gets called on the 'init' hook when the signup form is processed ...
    * this is in place so that payment solutions like paypal can redirect
    * before any content is rendered.
  */
  public function process_signup_form($txn) {
    $mepr_options = MeprOptions::fetch();

    if(isset($txn) and $txn instanceof MeprTransaction) {
      $usr = $txn->user();
      $prd = $txn->product();
    }
    else
      return;
    
    if($txn->amount <= 0.00) {
      MeprTransaction::create_free_transaction($txn);
      return;
    }
    
    if($txn->gateway == $this->id) {
      $mepr_options = MeprOptions::fetch();
      $invoice      = $txn->id . '-' . time();
      $useraction = '';
      
      //IMPORTANT - PayPal txn will fail if the descriptions do not match exactly
      //so if you change the description here you also need to mirror it
      //inside of process_create_subscription().
      $desc = $this->paypal_desc($txn);
      
      $billing_type = (($prd->is_one_time_payment())?'MerchantInitiatedBilling':'RecurringPayments');
      $args = array( "PAYMENTREQUEST_0_AMT" => MeprUtils::format_float($txn->amount),
                     "PAYMENTREQUEST_0_PAYMENTACTION" => 'Sale', // Transaction or order? Transaction I assume?
                     "PAYMENTREQUEST_0_DESC" => $desc, //Better way to get description working on lifetimes
                     "L_BILLINGAGREEMENTDESCRIPTION0" => $desc,
                     "L_BILLINGTYPE0" => $billing_type,
                     "RETURNURL" => $this->notify_url('return'),
                     "CANCELURL" => $this->notify_url('cancel'),
                     "L_BILLINGAGREEMENTCUSTOM0" => $txn->id, // Ignored when RecurringPayments is the Type
                     "L_PAYMENTTYPE0" => "InstantOnly", // Ignored when RecurringPayments is the Type
                     "PAYMENTREQUEST_0_CURRENCYCODE" => $mepr_options->currency_code /*,
                     "SOLUTIONTYPE" => 'Sole',
                     "LANDINGPAGE" => 'Billing' */ );

      $this->email_status( "MemberPress PayPal Request: \n" . MeprUtils::object_to_string($args, true) . "\n",
                           $this->settings->debug );

      $res = $this->send_nvp_request("SetExpressCheckout", $args);

      $this->email_status( "PayPal Response Object: \n" . MeprUtils::object_to_string($res, true) . "\n",
                           $this->settings->debug );

      $token='';
      $ack = strtoupper($res['ACK']);
      if( $ack=="SUCCESS" or $ack=="SUCCESSWITHWARNING" ) {
        $txn->trans_num = $token = urldecode($res["TOKEN"]);
        $txn->store();

        if( !$prd->is_one_time_payment() )
          update_post_meta($txn->subscription_id, self::$paypal_token_str, $token);
        
        MeprUtils::wp_redirect( "{$this->settings->url}?cmd=_express-checkout&token={$token}{$useraction}" );
      }
    }
    
    return false;
  }
  
  /** This gets called on wp_enqueue_script and enqueues a set of
    * scripts for use on the page containing the payment form
    */
  public function enqueue_payment_form_scripts() {
    // No need, handled on the PayPal side
  }
  
  /** This gets called on the_content and just renders the payment form
    */
  public function display_payment_form($amount, $user, $product_id, $transaction_id) {
    // Handled on the PayPal site so we don't have a need for it here
  }
  
  /** Validates the payment form before a payment is processed */
  public function validate_payment_form($errors) {
    // PayPal does this on their own form
  }
  
  /** Displays the form for the given payment gateway on the MemberPress Options page */
  public function display_options_form() {
    $mepr_options = MeprOptions::fetch();
    
    $api_username = trim($this->settings->api_username);
    $api_password = trim($this->settings->api_password);
    $signature    = trim($this->settings->signature);
    $sandbox      = ($this->settings->sandbox=='on' or $this->settings->sandbox==true);
    $debug        = ($this->settings->debug=='on' or $this->settings->debug==true);
    
    ?>
    <div class="mepr-options-pane">
      <table>
        <tr>
          <td><?php _e('API Username:', 'memberpress'); ?></td>
          <td><input type="text" class="regular-text mepr-auto-trim" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id;?>][api_username]" width="100%" value="<?php echo $api_username; ?>" /></td>
        </tr>
        <tr>
          <td><?php _e('API Password:', 'memberpress'); ?></td>
          <td><input type="text" class="regular-text mepr-auto-trim" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id;?>][api_password]" width="100%" value="<?php echo $api_password; ?>" /></td>
        </tr>
        <tr> 
          <td><?php _e('Signature:', 'memberpress'); ?></td>
          <td><input type="text" class="regular-text mepr-auto-trim" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id;?>][signature]" width="100%" value="<?php echo $signature; ?>" /></td>
        </tr>
        <tr>
          <td colspan="2"><input type="checkbox" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id;?>][sandbox]"<?php echo checked($sandbox); ?> />&nbsp;<?php _e('Use PayPal Sandbox', 'memberpress'); ?></td>
        </tr>
        <tr>
          <td colspan="2"><input type="checkbox" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id;?>][debug]"<?php echo checked($debug); ?> />&nbsp;<?php _e('Send PayPal Debug Emails', 'memberpress'); ?></td>
        </tr>
        <tr>
          <td><?php _e('PayPal IPN URL:', 'memberpress'); ?></td>
          <td><input type="text" onfocus="this.select();" onclick="this.select();" readonly="true" class="clippy_input regular-text" value="<?php echo $this->notify_url('ipn'); ?>" /><span class="clippy"><?php echo $this->notify_url('ipn'); ?></span></td>
        </tr>
        <?php /*
        <tr>
          <td><?php _e('Time Zone Offset:', 'memberpress'); ?></td>
          <?php MeprAppHelper::info_tooltip('mepr-pp-tzoffset',
                  __('Time Zone Offset', 'memberpress'),
                  __('Almost all users should leave this disabled. However, in rare cases some international users have experienced an issue with timezone differences between their server and PayPal. Only increase this if your payments are not working and your debug emails indicate your subscription start dates must be before the current date. Start with the smallest and work your way up until a successful payment goes through. The smaller the offset, the better.', 'memberpress'));
          ?>
          <td>
            <select name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id;?>][tzoffset]">
              <option value="0"><?php _e('Disabled'); ?></option>
              <option value="3"><?php _e('+3 Hours'); ?></option>
              <option value="6"><?php _e('+6 Hours'); ?></option>
              <option value="9"><?php _e('+9 Hours'); ?></option>
              <option value="12"><?php _e('+12 Hours'); ?></option>
            </select>
          </td>
        </tr>
        */ ?>
        <?php do_action('mepr-paypal-express-options-form', $this); ?>
      </table>
    </div>
    <?php
  }
  
  /** Validates the form for the given payment gateway on the MemberPress Options page */
  public function validate_options_form($errors) {
    $mepr_options = MeprOptions::fetch();
    
    if( !isset($_POST[$mepr_options->integrations_str][$this->id]['api_username']) or
        empty($_POST[$mepr_options->integrations_str][$this->id]['api_username']) )
      $errors[] = __("PayPal API Username field can't be blank.", 'memberpress');
    else if( !isset($_POST[$mepr_options->integrations_str][$this->id]['api_password']) or
        empty($_POST[$mepr_options->integrations_str][$this->id]['api_password']) )
      $errors[] = __("PayPal API Password field can't be blank.", 'memberpress');
    else if( !isset($_POST[$mepr_options->integrations_str][$this->id]['signature']) or
        empty($_POST[$mepr_options->integrations_str][$this->id]['signature']) )
      $errors[] = __("PayPal Signature field can't be blank.", 'memberpress');
    
    return $errors;
  }

  /** Displays the update account form on the subscription account page **/
  public function display_update_account_form($sub_id, $errors=array(), $message='') {
    ?>
    <h3><?php _e('Updating your PayPal Account Information', 'memberpress'); ?></h3>
    <div><?php printf(__('To update your PayPal Account Information, please go to %sPayPal.com%s, login and edit your account information there.', 'memberpress'), '<a href="http://paypal.com" target="blank">', '</a>'); ?></div>
    <?php
  }
  
  /** Validates the payment form before a payment is processed */
  public function validate_update_account_form($errors=array()) {
    // We'll have them update their cc info on paypal.com
  }

  /** Actually pushes the account update to the payment processor */
  public function process_update_account_form($sub_id) {
    // We'll have them update their cc info on paypal.com
  }
  
  /** Returns boolean ... whether or not we should be sending in test mode or not */
  public function is_test_mode() {
    return (isset($this->settings->sandbox) and $this->settings->sandbox);
  }

  public function force_ssl() {
    return false; // redirects off site where ssl is installed
  }

  private function send_nvp_request($method_name, $args, $method='post', $blocking=true) {
    $args = array_merge( array( "METHOD"    => $method_name,
                                "VERSION"   => $this->settings->api_version,
                                "PWD"       => $this->settings->api_password,
                                "USER"      => $this->settings->api_username,
                                "SIGNATURE" => $this->settings->signature ), $args );
    
    $arg_array = array( 'method'    => strtoupper($method),
                        'body'      => $args,
                        'timeout'   => 15,
                        'httpversion' => '1.1', //PayPal is now requiring this
                        'blocking'  => $blocking,
                        'sslverify' => false, // We assume the cert on paypal is trusted
                        'headers'   => array()
                      );
    
    //$this->email_status("Sending Paypal Request\n" . MeprUtils::object_to_string($arg_array, true) . "\n", $this->settings->debug);
    $resp = wp_remote_request( $this->settings->api_url, $arg_array );
    //$this->email_status("Got Paypal Response\n" . MeprUtils::object_to_string($resp, true) . "\n", $this->settings->debug);

    // If we're not blocking then the response is irrelevant
    // So we'll just return true.
    if( $blocking==false )
      return true;

    if( is_wp_error( $resp ) )
      throw new MeprHttpException( sprintf( __( 'You had an HTTP error connecting to %s' , 'memberpress'), $this->name ) );
    else
      return wp_parse_args($resp['body']);

    return false;
  }

  public function return_handler() {
    // Handled with a GET REQUEST by PayPal
    $this->email_status("Paypal Return \$_REQUEST:\n".MeprUtils::object_to_string($_REQUEST,true)."\n", $this->settings->debug);

    $mepr_options = MeprOptions::fetch();

    if( ( isset($_REQUEST['token']) and ($token = $_REQUEST['token']) ) or
        ( isset($_REQUEST['TOKEN']) and ($token = $_REQUEST['TOKEN']) ) )
    {
      $obj = MeprTransaction::get_one_by_trans_num($token);

      $txn = new MeprTransaction();
      $txn->load_data($obj);

      $this->email_status("Paypal Transaction \$txn:\n".MeprUtils::object_to_string($txn,true)."\n", $this->settings->debug);

      try {
        $this->process_payment_form($txn);
        $txn = new MeprTransaction($txn->id); //Grab the txn again, now that we've updated it
        MeprUtils::wp_redirect($mepr_options->thankyou_page_url("trans_num={$txn->trans_num}"));
      } catch( Exception $e ) {
        $prd = $txn->product();
        MeprUtils::wp_redirect($prd->url( '?action=payment_form&txn='.$txn->trans_num.'&message='.$e->getMessage().'&_wpnonce='.wp_create_nonce('mepr_payment_form')));
      }
    }
  }

  public function cancel_handler() {
    // Handled with a GET REQUEST by PayPal
    $this->email_status("Paypal Cancel \$_REQUEST:\n".MeprUtils::object_to_string($_REQUEST,true)."\n", $this->settings->debug);

    if(isset($_REQUEST['token']) and ($token = $_REQUEST['token'])) {
      $txn = MeprTransaction::get_one_by_trans_num($token);
      $txn = new MeprTransaction($txn->id);

      // Make sure the txn status is pending
      $txn->status = MeprTransaction::$pending_str;

      if( $sub = $txn->subscription() ) {
        $sub->status = MeprSubscription::$cancelled_str;
        //$txn->subscription_id=0;
        $sub->store();
      }

      if($txn) {
        $prd = new MeprProduct($txn->product_id);
        // TODO: Send an abandonment email
        MeprUtils::wp_redirect($this->message_page_url($prd,'cancel'));
      }
      else
        MeprUtils::wp_redirect(home_url());
    }
  }

  public function cancel_message() {
    $mepr_options = MeprOptions::fetch();
    ?>
      <h4><?php _e('Your payment at PayPal was cancelled.', 'memberpress'); ?></h4>
      <p><?php echo apply_filters('mepr_paypal_cancel_message', sprintf(__('You can still %1$slogin%2$s and finish your transaction using the username & password you just setup.', 'memberpress'), "<a href=\"{$mepr_options->login_page_url()}\">", '</a>')); ?><br/></p>
    <?php
  }

  public function paypal_period($period_type) {
    if($period_type=='months')
      return 'Month';
    else if($period_type=='years')
      return 'Year';
    else if($period_type=='weeks')
      return 'Week';
    else
      return $period_type;
  }

  private function send_digital_goods_error_message() {
    $subject = sprintf( __( '** PayPal Payment ERROR on %s' , 'memberpress'), get_option('blogname') );
    $body = __( 'Your PayPal account isn\'t setup to sell Digital Goods.

Your recurring billing profiles and transactions won\'t complete properly until this problem is fixed.

Follow these instructions to enable Digital Goods:
1) Sign in to your PayPal account (must be signed in before visiting the link below)
2) Visit https://www.paypal.com/us/webapps/mpp/digital in your browser
3) Follow the steps PayPal provides here to add Digital Goods to your account

If you still have issues getting this to work, please contact customer support at http://memberpress.com/support.

Thanks,

The MemberPress Team
' , 'memberpress');

    MeprUtils::wp_mail_to_admin($subject, $body);
  }

  private function paypal_desc($txn) {
    $prd = new MeprProduct($txn->product_id);
    return "{$prd->post_title} - " . MeprTransactionsHelper::format_currency($txn);
  }

  private function update_paypal_payment_profile($sub_id,$action='cancel') {
    $sub = new MeprSubscription($sub_id);

    $args = array( "PROFILEID" => $sub->subscr_id, "ACTION" => $action );

    $this->email_status( "PayPal Cancel subscription request: \n" . MeprUtils::object_to_string($args, true) . "\n",
                           $this->settings->debug );

    $res = $this->send_nvp_request('ManageRecurringPaymentsProfileStatus', $args);

    $this->email_status( "PayPal Cancel subscription response: \n" . MeprUtils::object_to_string($res, true) . "\n",
                           $this->settings->debug );

    if( strtolower($res['ACK']) != 'success' )
      throw new MeprGatewayException( __('There was a problem cancelling, try logging in directly at PayPal to update the status of your recurring profile.', 'memberpress') );


    $_REQUEST['recurring_payment_id'] = $sub->subscr_id;
  }
}