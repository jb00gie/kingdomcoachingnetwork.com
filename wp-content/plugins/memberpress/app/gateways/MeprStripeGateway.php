<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprStripeGateway extends MeprBaseRealGateway {
  public static $stripe_plan_id_str = '_mepr_stripe_plan_id';
  
  /** Used in the view to identify the gateway */
  public function __construct()
  {
    $this->name = __("Stripe", 'memberpress');
    $this->set_defaults();
    
    $this->capabilities = array(
      'process-credit-cards',
      'process-payments',
      'process-refunds',
      'create-subscriptions',
      'cancel-subscriptions',
      'update-subscriptions',
      'suspend-subscriptions',
      'resume-subscriptions',
      'send-cc-expirations'
    );
    
    // Setup the notification actions for this gateway
    $this->notifiers = array( 'whk' => 'listener' );
  }
  
  public function load($settings)
  {
    $this->settings = (object)$settings;
    $this->set_defaults();
  }
  
  protected function set_defaults() {
    if(!isset($this->settings))
      $this->settings = array();
    
    $this->settings = (object)array_merge(array('gateway' => 'MeprStripeGateway',
                                                'id' => time() . '-' . uniqid(),
                                                'label' => '',
                                                'email' => '',
                                                'sandbox' => false,
                                                'force_ssl' => false,
                                                'debug' => false,
                                                'test_mode' => false,
                                                'api_keys' => array('test' => array('public' => '', 'secret' => ''),
                                                                    'live' => array('public' => '', 'secret' => ''))), (array)$this->settings);
    
    $this->id = $this->settings->id;
    $this->label = $this->settings->label;
    //$this->recurrence_type = $this->settings->recurrence_type;
    
    if($this->is_test_mode()) {
      $this->settings->public_key = $this->settings->api_keys['test']['public'];
      $this->settings->secret_key = $this->settings->api_keys['test']['secret'];
    }
    else {
      $this->settings->public_key = $this->settings->api_keys['live']['public'];
      $this->settings->secret_key = $this->settings->api_keys['live']['secret'];
    }

    // An attempt to correct people who paste in spaces along with their credentials
    $this->settings->api_keys['test']['secret'] = trim($this->settings->api_keys['test']['secret']);
    $this->settings->api_keys['test']['public'] = trim($this->settings->api_keys['test']['public']);
    $this->settings->api_keys['live']['secret'] = trim($this->settings->api_keys['live']['secret']);
    $this->settings->api_keys['live']['public'] = trim($this->settings->api_keys['live']['public']);
  }
  
  /** Used to send data to a given payment gateway. In gateways which redirect
    * before this step is necessary this method should just be left blank.
    */
  public function process_payment($txn) {
    if(isset($txn) and $txn instanceof MeprTransaction) {
      $usr = $txn->user();
      $prd = $txn->product();
    }
    else
      throw new MeprGatewayException( __('Payment was unsuccessful, please check your payment details and try again.', 'memberpress') );

    $mepr_options = MeprOptions::fetch();

    // create the charge on Stripe's servers - this will charge the user's card
    $args = array( "amount" => MeprUtils::format_float(($txn->amount*100),0),
                   "currency" => $mepr_options->currency_code,
                   "description" => sprintf(__("%s (transaction: %s)", 'memberpress'), $prd->post_title, $txn->id ) );

    // get the credit card details submitted by the form
    if(isset($_REQUEST['stripe_token']))
      $args['card'] = $_REQUEST['stripe_token'];
    else if(isset($_REQUEST['stripe_customer']))
      $args['customer'] = $_REQUEST['stripe_customer'];
    else if(isset($_REQUEST['mepr_cc_num'])) {
      $args['card'] = array( 'number'    => $_REQUEST['mepr_cc_num'],
                             'exp_month' => $_REQUEST['mepr_cc_exp_month'],
                             'exp_year'  => $_REQUEST['mepr_cc_exp_year'],
                             'cvc'       => $_REQUEST['mepr_cvv_code'] );
    }
    else
      throw new MeprGatewayException( __('There was a problem sending your credit card details to the processor. Please try again later.', 'memberpress') );

    $usr = $txn->user();

    $this->email_status( "Stripe Charge Happening Now ... " . MeprUtils::object_to_string($args), $this->settings->debug );

    $charge = (object)$this->send_stripe_request( 'charges', $args, 'post' );
    $this->email_status( "Stripe Charge: " . MeprUtils::object_to_string($charge), $this->settings->debug );

    $txn->trans_num = $charge->id;
    $txn->response = json_encode($charge);
    $txn->store();

    $this->email_status( "Stripe Charge Happening Now ... 2", $this->settings->debug );

    $_REQUEST['data'] = $charge;

    return $this->record_payment();
  }
  
  /** Used to record a successful recurring payment by the given gateway. It
    * should have the ability to record a successful payment or a failure. It is
    * this method that should be used when receiving an IPN from PayPal or a
    * Silent Post from Authorize.net.
    */
  public function record_subscription_payment() {
    if(isset($_REQUEST['data'])) {
      $charge = (object)$_REQUEST['data'];

      // Make sure there's a valid subscription for this request and this payment hasn't already been recorded
      if( !isset($charge) or !isset($charge->customer) or
          !($sub = MeprSubscription::get_one_by_subscr_id($charge->customer)) ) {
        return false;
      }

      $first_txn = $txn = $sub->first_txn(); 

      $this->email_status( "record_subscription_payment:" .
                           "\nSubscription: " . MeprUtils::object_to_string($sub, true) .
                           "\nTransaction: " . MeprUtils::object_to_string($txn, true),
                           $this->settings->debug);

      $txn = new MeprTransaction();
      $txn->amount     = (float)($charge->amount / 100); 
      $txn->user_id    = $sub->user_id;
      $txn->product_id = $sub->product_id;
      $txn->status     = MeprTransaction::$complete_str;
      $txn->coupon_id  = $first_txn->coupon_id;
      $txn->response   = json_encode($charge);
      $txn->trans_num  = $charge->id;
      $txn->gateway    = $this->id;
      $txn->subscription_id = $sub->ID;

      $sdata = $this->send_stripe_request("customers/{$sub->subscr_id}", array(), 'get');

      //$txn->expires_at = MeprUtils::ts_to_mysql_date($sdata['subscription']['current_period_end']);

      $this->email_status( "/customers/{$sub->subscr_id}\n" .
                           MeprUtils::object_to_string($sdata, true) .
                           MeprUtils::object_to_string($txn, true),
                           $this->settings->debug );

      $txn->store();

      $sub->status        = MeprSubscription::$active_str;
      $sub->cc_exp_month  = $charge->card['exp_month'];
      $sub->cc_exp_year   = $charge->card['exp_year'];
      $sub->cc_last4      = $charge->card['last4'];
      $sub->gateway       = $this->id;
      $sub->store();
      // If a limit was set on the recurring cycles we need
      // to cancel the subscr if the txn_count >= limit_cycles_num
      // This is not possible natively with Stripe so we 
      // just cancel the subscr when limit_cycles_num is hit
      $sub->limit_payment_cycles();

      $this->email_status( "Subscription Transaction\n" .
                           MeprUtils::object_to_string($txn->rec, true),
                           $this->settings->debug );

      $this->send_transaction_receipt_notices( $txn );
      $this->send_cc_expiration_notices( $txn );

      return $txn;
    }
    
    return false;
  }

  /** Used to record a declined payment. */
  public function record_payment_failure() {
    if(isset($_REQUEST['data'])) 
    {
      $charge = (object)$_REQUEST['data'];
      $txn_res = MeprTransaction::get_one_by_trans_num($charge->id);
      if(is_object($txn_res) and isset($txn_res->id)) {
        $txn = new MeprTransaction($txn_res->id);
        $txn->status = MeprTransaction::$failed_str;
        $txn->store();
      }
      else if( isset($charge) and isset($charge->customer) and
               $sub = MeprSubscription::get_one_by_subscr_id($charge->customer) ) {
        $first_txn = $sub->first_txn();
        $latest_txn = $sub->latest_txn();

        $txn = new MeprTransaction();
        $txn->amount = (float)($charge->amount / 100); 
        $txn->user_id = $sub->user_id;
        $txn->product_id = $sub->product_id;
        $txn->coupon_id = $first_txn->coupon_id;
        $txn->txn_type = MeprTransaction::$payment_str;
        $txn->status = MeprTransaction::$failed_str;
        $txn->subscription_id = $sub->ID;
        $txn->response = json_encode($_REQUEST);
        $txn->trans_num = $charge->id;
        $txn->gateway = $this->id;
        $txn->store();

        $sub->status = MeprSubscription::$active_str;
        $sub->gateway = $this->id;
        $sub->expire_txns(); //Expire associated transactions for the old subscription
        $sub->store();
      }
      else
        return false; // Nothing we can do here ... so we outta here

      $this->send_failed_txn_notices($txn);

      return $txn;
    }
    
    return false;
  }
  
  /** Used to record a successful payment by the given gateway. It should have
    * the ability to record a successful payment or a failure. It is this method
    * that should be used when receiving an IPN from PayPal or a Silent Post
    * from Authorize.net.
    */
  public function record_payment() {
    $this->email_status( "Starting record_payment: " . MeprUtils::object_to_string($_REQUEST), $this->settings->debug );
    if(isset($_REQUEST['data'])) {
      $charge = (object)$_REQUEST['data'];
      $this->email_status("record_payment: \n" . MeprUtils::object_to_string($charge, true) . "\n", $this->settings->debug);
      $obj = MeprTransaction::get_one_by_trans_num($charge->id);
      
      if(is_object($obj) and isset($obj->id)) {
        $txn = new MeprTransaction();
        $txn->load_data($obj);
        $usr = $txn->user();

        // Just short circuit if the txn has already completed
        if($txn->status == MeprTransaction::$complete_str)
          return;

        $txn->status    = MeprTransaction::$complete_str;
        $txn->response  = json_encode($charge);

        // This will only work before maybe_cancel_old_sub is run
        $upgrade = $txn->is_upgrade();
        $downgrade = $txn->is_downgrade();

        $txn->maybe_cancel_old_sub();
        $txn->store();

        $this->email_status("Standard Transaction\n" . MeprUtils::object_to_string($txn->rec, true) . "\n", $this->settings->debug);

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
        $this->send_cc_expiration_notices( $txn );
      }
    }

    return false;
  }

  /** This method should be used by the class to record a successful refund from
    * the gateway. This method should also be used by any IPN requests or Silent Posts.
    */
  public function process_refund(MeprTransaction $txn) {
    $refund = (object)$this->send_stripe_request( "charges/{$txn->trans_num}/refund" );
    $this->email_status( "Stripe Refund: " . MeprUtils::object_to_string($refund), $this->settings->debug );
    $_REQUEST['data'] = $refund;
    return $this->record_refund();
  }
  
  /** This method should be used by the class to record a successful refund from
    * the gateway. This method should also be used by any IPN requests or Silent Posts.
    */
  public function record_refund() {
    if(isset($_REQUEST['data'])) 
    {
      $charge = (object)$_REQUEST['data'];
      $obj = MeprTransaction::get_one_by_trans_num($charge->id);
      
      if(!is_null($obj) && (int)$obj->id > 0) {
        $txn = new MeprTransaction($obj->id);
        
        // Seriously ... if txn was already refunded what are we doing here?
        if($txn->status == MeprTransaction::$refunded_str) { return $txn->id; }
        
        $txn->status = MeprTransaction::$refunded_str;
        $txn->store();
        
        $this->send_refunded_txn_notices($txn);
        
        return $txn->id;
      }
    }
    
    return false;
  }
  
  /** Used to send subscription data to a given payment gateway. In gateways
    * which redirect before this step is necessary this method should just be
    * left blank.
    */
  public function process_create_subscription($txn) {
    if(isset($txn) and $txn instanceof MeprTransaction) {
      $usr = $txn->user();
      $prd = $txn->product();
    }
    else
      throw new MeprGatewayException( __('Payment was unsuccessful, please check your payment details and try again.', 'memberpress') );

    $mepr_options = MeprOptions::fetch();
    $sub = $txn->subscription();

    // get the credit card details submitted by the form
    if(isset($_REQUEST['stripe_token']))
      $card = $_REQUEST['stripe_token'];
    else if(isset($_REQUEST['mepr_cc_num'])) {
      $card = array( 'number'    => $_REQUEST['mepr_cc_num'],
                     'exp_month' => $_REQUEST['mepr_cc_exp_month'],
                     'exp_year'  => $_REQUEST['mepr_cc_exp_year'],
                     'cvc'       => $_REQUEST['mepr_cvv_code'] );
    }
    else {
      throw new MeprGatewayException( __('There was a problem sending your credit card details to the processor. Please try again later.', 'memberpress') );
    }

    $customer = $this->stripe_customer($txn->subscription_id, $card);
    $plan     = $this->stripe_plan($txn->subscription(), true);

    $args = array( "plan" => $plan->id );

    // TODO: Don't think we need this in place until we implement coupons that are non-recurring
    //if( $txn->coupon_id )
    //  $args['coupon'] = $this->stripe_coupon($txn->coupon_id, $txn->amount)->id;

    $this->email_status("process_create_subscription: \n" . MeprUtils::object_to_string($txn, true) . "\n", $this->settings->debug);

    $subscr = $this->send_stripe_request( "customers/{$customer->id}/subscription", $args, 'post' );
    $sub->subscr_id = $customer->id;
    $sub->store();

    $_REQUEST['data'] = $customer;
    return $this->record_create_subscription();
  }
  
  /** Used to record a successful subscription by the given gateway. It should have
    * the ability to record a successful subscription or a failure. It is this method
    * that should be used when receiving an IPN from PayPal or a Silent Post
    * from Authorize.net.
    */
  public function record_create_subscription() {
    if(isset($_REQUEST['data'])) {
      $sdata = (object)$_REQUEST['data'];
      $sub = MeprSubscription::get_one_by_subscr_id($sdata->id);
      $sub->response=$sdata;
      $sub->status=MeprSubscription::$active_str;
      
      if( $card = $this->get_default_card($sdata) ) {
        $sub->cc_last4 = $card['last4'];
        $sub->cc_exp_month = $card['exp_month'];
        $sub->cc_exp_year = $card['exp_year'];
      }

      $sub->created_at = date('c');
      $sub->store();

      // This will only work before maybe_cancel_old_sub is run
      $upgrade = $sub->is_upgrade();
      $downgrade = $sub->is_downgrade();

      $sub->maybe_cancel_old_sub();

      $txn = $sub->first_txn();
      $old_amount = $txn->amount;

      $mepr_options = MeprOptions::fetch();

      // If there's a paid trial then we know this txn object has been
      // processed as a one off txn so we don't need a confirmation txn
      if($sub->trial and $sub->trial_amount > 0.00) {
        $txn->amount=$sub->trial_amount;

        $trial_days = ( $sub->trial ? $sub->trial_days : $mepr_options->grace_init_days );
        $txn->expires_at = MeprUtils::ts_to_mysql_date(time()+MeprUtils::days($trial_days));

        $txn->store();
        unset($_REQUEST['stripe_token']); 
        $_REQUEST['stripe_customer'] = $sub->subscr_id;
        $this->process_payment($txn);
      } // Stripe doesn't support a trial amount so this is how we do it people...
      else {
        // Turn this into a confirmation subscription
        $txn->trans_num  = $sub->subscr_id;
        $txn->status     = MeprTransaction::$confirmed_str;
        $txn->txn_type   = MeprTransaction::$subscription_confirmation_str;
        $txn->amount     = 0.00; // Just a confirmation txn
        $txn->response   = (string)$sub;

        // At the very least the subscription confirmation transaction gives
        // the user a 24 hour grace period so they can log in even before the
        // stripe transaction goes through (stripe could send the txn later --
        // and in the case where we have a free trial that's a guarantee)
        $trial_days = ( $sub->trial ? $sub->trial_days : $mepr_options->grace_init_days );
        $txn->expires_at = MeprUtils::ts_to_mysql_date(time()+MeprUtils::days($trial_days));
        $txn->store();
      }

      $txn->amount = $old_amount; // Artificially set the subscription amount

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

    return false;
  }

  public function process_update_subscription($sub_id) {
    $mepr_options = MeprOptions::fetch();
    $sub = new MeprSubscription($sub_id);

    if(!isset($_REQUEST['stripe_token']))
      throw new MeprGatewayException( __('There was a problem sending your credit card details to the processor. Please try again later.', 'memberpress') );

    // get the credit card details submitted by the form
    $token    = $_REQUEST['stripe_token'];
    $customer = $this->stripe_customer($sub_id, $token);

    $usr = $sub->user();

    $args = array( "card" => $token );

    $subscr = (object)$this->send_stripe_request( "customers/{$customer->id}", $args, 'post' );
    $sub->subscr_id = $subscr->id;

    if( $card = $this->get_default_card( $subscr ) ) {
      $sub->cc_last4 = $card['last4'];
      $sub->cc_exp_month = $card['exp_month'];
      $sub->cc_exp_year = $card['exp_year'];
    }

    $sub->response = $subscr;
    $sub->store();

    return $subscr;
  }

  /** This method should be used by the class to record a successful cancellation
    * from the gateway. This method should also be used by any IPN requests or 
    * Silent Posts.
    */
  public function record_update_subscription() {
    // No need for this one with stripe
  }

  /** Used to suspend a subscription by the given gateway.
    */
  public function process_suspend_subscription($sub_id) {
    $mepr_options = MeprOptions::fetch();
    $sub = new MeprSubscription($sub_id);

    // If there's not already a customer then we're done here
    if(!($customer = $this->stripe_customer($sub_id))) { return false; }

    // Yeah ... we're cancelling here bro ... with stripe we should be able to restart again
    $res = $this->send_stripe_request( "customers/{$customer->id}/subscription", array(), 'delete' );
    $_REQUEST['data'] = $res;

    return $this->record_suspend_subscription();
  }

  /** This method should be used by the class to record a successful suspension
    * from the gateway.
    */
  public function record_suspend_subscription() {
    if(isset($_REQUEST['data'])) 
    {
      $sdata = (object)$_REQUEST['data'];
      if( $sub = MeprSubscription::get_one_by_subscr_id($sdata->customer) ) {
        // Seriously ... if sub was already cancelled what are we doing here?
        if($sub->status == MeprSubscription::$suspended_str) { return $sub; }

        $sub->status = MeprSubscription::$suspended_str;
        $sub->store();

        $this->send_suspended_sub_notices($sub);
      }
    }

    return false;
  }

  /** Used to suspend a subscription by the given gateway.
    */
  public function process_resume_subscription($sub_id) {
    $mepr_options = MeprOptions::fetch();
    $sub = new MeprSubscription($sub_id);

    $customer = $this->stripe_customer($sub_id);

    //Set enough of the $customer data here to get this resumed
    if(empty($customer)) { $customer = (object)array('id' => $sub->subscr_id); }

    $orig_trial        = $sub->trial;
    $orig_trial_days   = $sub->trial_days;
    $orig_trial_amount = $sub->trial_amount;

    if( $sub->is_expired() and !$sub->is_lifetime()) {
      $exptxn = $sub->expiring_txn();

      // if it's already expired with a real transaction
      // then we want to resume immediately
      if($exptxn->status!=MeprTransaction::$confirmed_str) {
        $sub->trial = false;
        $sub->trial_days = 0;
        $sub->trial_amount = 0.00;
        $sub->store();
      }
    }
    else {
      $sub->trial = true;
      $sub->trial_days = MeprUtils::tsdays(strtotime($sub->expires_at) - time());
      $sub->trial_amount = 0.00;
      $sub->store();
    }

    // Create new plan with optional trial in place ...
    $plan = $this->stripe_plan($sub,true);

    $sub->trial        = $orig_trial;
    $sub->trial_days   = $orig_trial_days;
    $sub->trial_amount = $orig_trial_amount;
    $sub->store();

    $args = array( "plan" => $plan->id );

    $this->email_status( "process_resume_subscription: \n" .
                         MeprUtils::object_to_string($sub, true) . "\n",
                         $this->settings->debug );

    $subscr = $this->send_stripe_request( "customers/{$sub->subscr_id}/subscription", $args, 'post' );

    $_REQUEST['data'] = $customer;
    return $this->record_resume_subscription();
  }

  /** This method should be used by the class to record a successful resuming of
    * as subscription from the gateway.
    */
  public function record_resume_subscription() {
    if(isset($_REQUEST['data'])) {
      $mepr_options = MeprOptions::fetch();

      $sdata = (object)$_REQUEST['data'];
      $sub = MeprSubscription::get_one_by_subscr_id($sdata->id);
      $sub->response=$sdata;
      $sub->status=MeprSubscription::$active_str;

      if( $card = $this->get_default_card($sdata) ) {
        $sub->cc_last4 = $card['last4'];
        $sub->cc_exp_month = $card['exp_month'];
        $sub->cc_exp_year = $card['exp_year'];
      }

      $sub->store();

      $txn = new MeprTransaction();
      $txn->subscription_id = $sub->ID;
      $txn->trans_num  = $sub->subscr_id . '-' . uniqid();
      $txn->status     = MeprTransaction::$confirmed_str;
      $txn->txn_type   = MeprTransaction::$subscription_confirmation_str;
      $txn->amount     = 0.00; // Just a confirmation txn
      $txn->response   = (string)$sub;
      $txn->expires_at = MeprUtils::ts_to_mysql_date(time()+MeprUtils::days(0));
      $txn->store();

      $this->send_resumed_sub_notices($sub);

      return array('subscription' => $sub, 'transaction' => $txn);
    }

    return false;
  }

  /** Used to cancel a subscription by the given gateway. This method should be used
    * by the class to record a successful cancellation from the gateway. This method
    * should also be used by any IPN requests or Silent Posts.
    */
  public function process_cancel_subscription($sub_id) {
    $mepr_options = MeprOptions::fetch();
    $sub = new MeprSubscription($sub_id);

    // If there's not already a customer then we're done here
    if(!($customer = $this->stripe_customer($sub_id))) { return false; }

    $res = $this->send_stripe_request( "customers/{$customer->id}/subscription", array(), 'delete' );
    $_REQUEST['data'] = $res;

    return $this->record_cancel_subscription();
  }

  /** This method should be used by the class to record a successful cancellation
    * from the gateway. This method should also be used by any IPN requests or 
    * Silent Posts.
    */
  public function record_cancel_subscription() {
    if(isset($_REQUEST['data'])) 
    {
      $sdata = (object)$_REQUEST['data'];
      if( $sub = MeprSubscription::get_one_by_subscr_id($sdata->customer) ) {
        // Seriously ... if sub was already cancelled what are we doing here?
        // Also, for stripe, since a suspension is only slightly different
        // than a cancellation, we kick it into high gear and check for that too
        if($sub->status == MeprSubscription::$cancelled_str or
           $sub->status == MeprSubscription::$suspended_str) { return $sub; }

        $sub->status = MeprSubscription::$cancelled_str;
        $sub->store();

        if(isset($_REQUEST['expire']))
          $sub->limit_reached_actions();

        if(!isset($_REQUEST['silent']) || ($_REQUEST['silent']==false))
          $this->send_cancelled_sub_notices($sub);
      }
    }

    return false;
  }

  /** This gets called on the 'init' hook when the signup form is processed ...
    * this is in place so that payment solutions like paypal can redirect
    * before any content is rendered.
  */
  public function process_signup_form($txn) {
    if($txn->amount <= 0.00) {
      MeprTransaction::create_free_transaction($txn);
      return;
    }
  }
  
  /** This gets called on wp_enqueue_script and enqueues a set of
    * scripts for use on the page containing the payment form
    */
  public function enqueue_payment_form_scripts() {
    wp_enqueue_script('stripe-js', 'https://js.stripe.com/v1/', array(), MEPR_VERSION);
    wp_enqueue_script('stripe-create-token', MEPR_GATEWAYS_URL . '/stripe/create_token.js', array('stripe-js'), MEPR_VERSION);
    wp_localize_script('stripe-create-token', 'MeprStripeGateway', array( 'public_key' => $this->settings->public_key ));
  }
  
  /** This gets called on the_content and just renders the payment form
    */
  public function display_payment_form($amount, $user, $product_id, $txn_id) {
    $mepr_options = MeprOptions::fetch();
    $prd = new MeprProduct($product_id);
    $coupon = false;

    $txn = new MeprTransaction($txn_id);
    
    //Artifically set the price of the $prd in case a coupon was used
    if($prd->price != $amount)
    {
      $coupon = true;
      $prd->price = $amount;
    }
    ?>
    <div class="mepr_signup_table">
      <form action="" method="post" id="payment-form">
        <input type="hidden" name="mepr_process_payment_form" value="Y" />
        <input type="hidden" name="mepr_transaction_id" value="<?php echo $txn_id; ?>" />
        <input type="hidden" class="card-name" value="<?php echo $user->get_full_name(); ?>" />

        <?php if($mepr_options->show_address_fields): ?>
          <input type="hidden" class="card-address-1" value="<?php echo get_user_meta($user->ID, 'mepr-address-one', true); ?>" />
          <input type="hidden" class="card-address-2" value="<?php echo get_user_meta($user->ID, 'mepr-address-two', true); ?>" />
          <input type="hidden" class="card-city" value="<?php echo get_user_meta($user->ID, 'mepr-address-city', true); ?>" />
          <input type="hidden" class="card-state" value="<?php echo get_user_meta($user->ID, 'mepr-address-state', true); ?>" />
          <input type="hidden" class="card-zip" value="<?php echo get_user_meta($user->ID, 'mepr-address-zip', true); ?>" />
          <input type="hidden" class="card-country" value="<?php echo get_user_meta($user->ID, 'mepr-address-country', true); ?>" />
        <?php endif; ?>

        <strong><?php _e('Please enter your Credit Card information below', 'memberpress'); ?></strong><br/><br/>
        <div class="errors"></div>
        
        <div class="mepr_signup_table_row">
          <label><?php _e('Price', 'memberpress'); ?></label>
          <?php echo MeprTransactionsHelper::format_currency($txn); ?>
        </div>
        
        <div class="mepr_signup_table_row">
          <label><?php _e('Credit Card #', 'memberpress'); ?></label>
          <input type="text" class="mepr-form-input card-number" autocomplete="off" />
        </div>

        <div class="mepr_signup_table_row">
          <label><?php _e('Expires', 'memberpress'); ?></label>
          <?php $this->months_dropdown('','card-expiry-month',isset($_REQUEST['card-expiry-month'])?$_REQUEST['card-expiry-month']:'',true); ?>
          <?php $this->years_dropdown('','card-expiry-year',isset($_REQUEST['card-expiry-year'])?$_REQUEST['card-expiry-year']:''); ?>
        </div>

        <div class="mepr_signup_table_row">
          <label><?php _e('CVV Code', 'memberpress'); ?></label>
          <input type="text" class="mepr-form-input card-cvc" autocomplete="off" size="4" />
        </div>

        <button type="submit" class="submit-button mepr_front_button"><?php _e('Submit', 'memberpress'); ?></button>&nbsp;<img src="<?php echo admin_url('images/loading.gif'); ?>" style="display: none;" class="stripe-loading-gif" />
        <noscript><p class="mepr_nojs"><?php _e('JavaScript is disabled in your browser. You will not be able to complete your purchase until you either enable JavaScript in your browser, or switch to a browser that supports it.', 'memberpress'); ?></p></noscript>
      </form>
    </div>
    <?php
  }
  
  /** Validates the payment form before a payment is processed */
  public function validate_payment_form($errors) {
    // This is done in the javascript with Stripe
  }
  
  /** Displays the form for the given payment gateway on the MemberPress Options page */
  public function display_options_form() {
    $mepr_options = MeprOptions::fetch();
    
    $test_secret_key = trim($this->settings->api_keys['test']['secret']);
    $test_public_key = trim($this->settings->api_keys['test']['public']);
    $live_secret_key = trim($this->settings->api_keys['live']['secret']);
    $live_public_key = trim($this->settings->api_keys['live']['public']);
    $force_ssl       = ($this->settings->force_ssl == 'on' or $this->settings->force_ssl == true);
    $debug           = ($this->settings->debug == 'on' or $this->settings->debug == true);
    $test_mode       = ($this->settings->test_mode == 'on' or $this->settings->test_mode == true);
    
    ?>
    <div class="mepr-options-pane">
      <table>
        <tr>
          <td><?php _e('Test Secret Key:', 'memberpress'); ?></td>
          <td><input type="text" class="regular-text mepr-auto-trim" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id;?>][api_keys][test][secret]" width="100%" value="<?php echo $test_secret_key; ?>" /></td>
        </tr>
        <tr>
          <td><?php _e('Test Publishable Key:', 'memberpress'); ?></td>
          <td><input type="text" class="regular-text mepr-auto-trim" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id;?>][api_keys][test][public]" width="100%" value="<?php echo $test_public_key; ?>" /></td>
        </tr>
        <tr>
          <td><?php _e('Live Secret Key:', 'memberpress'); ?></td>
          <td><input type="text" class="regular-text mepr-auto-trim" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id;?>][api_keys][live][secret]" width="100%" value="<?php echo $live_secret_key; ?>" /></td>
        </tr>
        <tr>
          <td><?php _e('Live Publishable Key:', 'memberpress'); ?></td>
          <td><input type="text" class="regular-text mepr-auto-trim" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id;?>][api_keys][live][public]" width="100%" value="<?php echo $live_public_key; ?>" /></td>
        </tr>

        <tr>
          <td colspan="2"><input type="checkbox" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id;?>][test_mode]"<?php echo checked($test_mode); ?> />&nbsp;<?php _e('Test Mode', 'memberpress'); ?></td>
        </tr>
        <tr>
          <td colspan="2"><input type="checkbox" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id;?>][force_ssl]"<?php echo checked($force_ssl); ?> />&nbsp;<?php _e('Force SSL', 'memberpress'); ?></td>
        </tr>
        <tr>
          <td colspan="2"><input type="checkbox" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id;?>][debug]"<?php echo checked($debug); ?> />&nbsp;<?php _e('Send Debug Emails', 'memberpress'); ?></td>
        </tr>
        <tr>
          <td><?php _e('Stripe Webhook URL:', 'memberpress'); ?></td>
          <td><input type="text" onfocus="this.select();" onclick="this.select();" readonly="true" class="clippy_input regular-text" value="<?php echo $this->notify_url('whk'); ?>" /><span class="clippy"><?php echo $this->notify_url('whk'); ?></span></td>
        </tr>
      </table>
    </div>
    <?php
  }
  
  /** Validates the form for the given payment gateway on the MemberPress Options page */
  public function validate_options_form($errors) {
    $mepr_options = MeprOptions::fetch();
    
    if( !isset($_REQUEST[$mepr_options->integrations_str][$this->id]['api_keys']['test']['secret']) or
         empty($_REQUEST[$mepr_options->integrations_str][$this->id]['api_keys']['test']['secret']) or
        !isset($_REQUEST[$mepr_options->integrations_str][$this->id]['api_keys']['test']['public']) or
         empty($_REQUEST[$mepr_options->integrations_str][$this->id]['api_keys']['test']['public']) or
        !isset($_REQUEST[$mepr_options->integrations_str][$this->id]['api_keys']['live']['secret']) or
         empty($_REQUEST[$mepr_options->integrations_str][$this->id]['api_keys']['live']['secret']) or
        !isset($_REQUEST[$mepr_options->integrations_str][$this->id]['api_keys']['live']['public']) or
         empty($_REQUEST[$mepr_options->integrations_str][$this->id]['api_keys']['live']['public']) )
      $errors[] = __("All Stripe keys must be filled in.", 'memberpress');
    
    return $errors;
  }

  /** This gets called on wp_enqueue_script and enqueues a set of
    * scripts for use on the front end user account page.
    */
  public function enqueue_user_account_scripts() {
    wp_enqueue_script('stripe-js', 'https://js.stripe.com/v1/', array(), MEPR_VERSION);
    wp_enqueue_script('stripe-create-token', MEPR_GATEWAYS_URL . '/stripe/create_token.js', array('stripe-js'), MEPR_VERSION);
    wp_localize_script('stripe-create-token', 'MeprStripeGateway', array( 'public_key' => $this->settings->public_key ));
  }

  /** Displays the update account form on the subscription account page **/
  public function display_update_account_form($sub_id, $errors=array(), $message='') {
    $mepr_options = MeprOptions::fetch();
    $customer = $this->stripe_customer($sub_id);
    $sub = new MeprSubscription($sub_id);
    $usr = $sub->user();

    $cc_exp_month = isset($_REQUEST['card-expiry-month'])?$_REQUEST['card-expiry-month']:$sub->cc_exp_month;
    $cc_exp_year = isset($_REQUEST['card-expiry-year'])?$_REQUEST['card-expiry-year']:$sub->cc_exp_year;

    if( $card = $this->get_default_card($customer) ) {
      $card_num = MeprUtils::cc_num($card['last4']);
      $card_name = ( isset($card['name']) and $card['name']!='undefined' ) ? $card['name'] : $usr->get_full_name();
    }
    else {
      $card_num = $sub->cc_num();
      $card_name = $usr->get_full_name();
    }

    require( MEPR_VIEWS_PATH . "/shared/errors.php" );

    ?>
    <div class="mepr_update_account_table">
      <form action="" method="post" id="payment-form">
        <input type="hidden" name="_mepr_nonce" value="<?php echo wp_create_nonce('mepr_process_update_account_form'); ?>" />
        <input type="hidden" class="card-name" value="<?php echo $card_name; ?>" />

        <?php if($mepr_options->show_address_fields): ?>
          <input type="hidden" class="card-address-1" value="<?php echo get_user_meta($usr->ID, 'mepr-address-one', true); ?>" />
          <input type="hidden" class="card-address-2" value="<?php echo get_user_meta($usr->ID, 'mepr-address-two', true); ?>" />
          <input type="hidden" class="card-city" value="<?php echo get_user_meta($usr->ID, 'mepr-address-city', true); ?>" />
          <input type="hidden" class="card-state" value="<?php echo get_user_meta($usr->ID, 'mepr-address-state', true); ?>" />
          <input type="hidden" class="card-zip" value="<?php echo get_user_meta($usr->ID, 'mepr-address-zip', true); ?>" />
          <input type="hidden" class="card-country" value="<?php echo get_user_meta($usr->ID, 'mepr-address-country', true); ?>" />
        <?php endif; ?>

        <strong><?php _e('Please enter your Credit Card information below', 'memberpress'); ?></strong><br/><br/>
        <div class="errors"></div>

        <div class="mepr_update_account_table_row">
          <label><?php _e('Credit Card #', 'memberpress'); ?></label>
          <input type="text" class="mepr-form-input card-number" autocomplete="off" value="<?php echo $card_num; ?>" />
        </div>

        <div class="mepr_update_account_table_row">
          <label><?php _e('Expires', 'memberpress'); ?></label>
          <?php $this->months_dropdown('','card-expiry-month',$cc_exp_month,true); ?>
          <?php $this->years_dropdown('','card-expiry-year',$cc_exp_year); ?>
        </div>

        <div class="mepr_update_account_table_row">
          <label><?php _e('CVV Code', 'memberpress'); ?></label>
          <input type="text" class="mepr-form-input card-cvc" autocomplete="off" size="4" />
        </div>

        <button type="submit" class="submit-button mepr_front_button"><?php _e('Submit', 'memberpress'); ?></button>
      </form>
    </div>
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
    $this->process_update_subscription($sub_id);
  }

  /** Returns boolean ... whether or not we should be sending in test mode or not */
  public function is_test_mode() {
    return (isset($this->settings->test_mode) and $this->settings->test_mode);
  }

  public function force_ssl() {
    return (isset($this->settings->force_ssl) and ($this->settings->force_ssl == 'on' or $this->settings->force_ssl == true));
  }

  /** STRIPE SPECIFIC METHODS **/

  public function listener() {
    // retrieve the request's body and parse it as JSON
    $body = @file_get_contents('php://input');
    $event_json = (object)json_decode($body,true);

    if(!isset($event_json->id)) return;

    // Use the id to pull the event directly from the API (purely a security measure)
    try {
      $event = (object)$this->send_stripe_request( "events/{$event_json->id}", array(), 'get' );
    }
    catch( Exception $e ) {
      return; // Do nothing
    }
    //$event = $event_json;

    $_REQUEST['data'] = $obj = (object)$event->data['object'];

    if($event->type=='charge.succeeded') {
      $this->email_status("###Event: {$event->type}\n" . MeprUtils::object_to_string($event, true)."\n", $this->settings->debug);

      // Description only gets set with the txn id in a standard charge
      if(isset($obj->description)) {
        //$this->record_payment(); // done on page
      }
      elseif(isset($obj->customer))
        $this->record_subscription_payment();
    }
    else if($event->type=='charge.failed') {
      $this->record_payment_failure();
    }
    else if($event->type=='charge.refunded') {
      $this->record_refund();
    }
    else if($event->type=='charge.disputed') {
      // Not worried about this right now
    }
    else if($event->type=='customer.subscription.created') {
      //$this->record_create_subscription(); // done on page
    }
    else if($event->type=='customer.subscription.updated') {
      //$this->record_update_subscription(); // done on page
    }
    else if($event->type=='customer.subscription.deleted') {
      $this->record_cancel_subscription();
    }
    else if($event->type=='customer.subscription.trial_will_end') {
      // We may want to implement this feature at some point 
    }
  }

  // Originally I thought these should be associated with
  // our product objects but now I realize they should be
  // associated with our subscription objects
  public function stripe_plan($sub, $is_new = false) {
    $mepr_options = MeprOptions::fetch();
    $prd = $sub->product();
    
    try {
      if($is_new)
        $plan_id = $this->create_new_plan_id($sub);
      else
        $plan_id = $this->get_plan_id($sub);
      
      $stripe_plan = $this->send_stripe_request( "plans/{$plan_id}", array(), 'get' );
    }
    catch( Exception $e ) {
      // The call resulted in an error ... meaning that
      // there's no plan like that so let's create one
      if( $sub->period_type == 'months' )
        $interval = 'month';
      else if( $sub->period_type == 'years' )
        $interval = 'year';
      else if( $sub->period_type == 'weeks' )
        $interval = 'week';
      
      //Setup a new plan ID and store the meta with this subscription
      $new_plan_id = $this->create_new_plan_id($sub);
      
      $args = array( "amount" => MeprUtils::format_float(($sub->price*100),0),
                     "interval" => $interval,
                     "interval_count" => $sub->period,
                     "name" => $prd->post_title,
                     "currency" => $mepr_options->currency_code,
                     "id" => $new_plan_id );

      if( $sub->trial )
        $args = array_merge( array( "trial_period_days" => $sub->trial_days ), $args );

      // Don't enclose this in try/catch ... we want any errors to bubble up
      $stripe_plan = $this->send_stripe_request( 'plans', $args );
    }

    return (object)$stripe_plan; 
  }
  
  public function get_plan_id($sub) {
    $meta_plan_id = get_post_meta($sub->ID, self::$stripe_plan_id_str, true);
    
    if($meta_plan_id == '')
      return $sub->ID;
    else
      return $meta_plan_id;
  }
  
  public function create_new_plan_id($sub) {
    $parse = parse_url(home_url());
    $new_plan_id = $sub->ID . '-' . $parse['host'] . '-' . uniqid();
    update_post_meta($sub->ID, self::$stripe_plan_id_str, $new_plan_id);
    return $new_plan_id;
  }
  
  public function stripe_customer( $sub_id, $cc_token=null ) {
    $mepr_options = MeprOptions::fetch();
    $sub = new MeprSubscription($sub_id);
    $user = $sub->user();

    $stripe_customer = (object)$sub->response;

    $uid = uniqid();
    $this->email_status("###{$uid} Stripe Customer (should be blank at this point): \n" . MeprUtils::object_to_string($stripe_customer, true) . "\n", $this->settings->debug);

    if( !$stripe_customer or empty($stripe_customer->id) ) {
      if( empty($cc_token) )
        return false;
      else {
        $stripe_args = array( "card" => $cc_token,
                              "email" => $user->user_email,
                              "description" => $user->get_full_name() );
        $stripe_customer = (object)$this->send_stripe_request( 'customers', $stripe_args );
        $sub->subscr_id = $stripe_customer->id;
        $sub->response  = $stripe_customer;
        $sub->store();
      }
    }

    $this->email_status("###{$uid} Stripe Customer (should not be blank at this point): \n" . MeprUtils::object_to_string($stripe_customer, true) . "\n", $this->settings->debug);

    return (object)$stripe_customer; 
  }

  // Only works with subscriptions
  public function stripe_coupon( $coupon_id, $price ) {
    $mepr_options = MeprOptions::fetch();
    $coupon = new MeprCoupon($coupon_id);

    try {
      $stripe_coupon = $this->send_stripe_request( "coupons/{$coupon_id}", array(), 'get' );
    }
    catch( Exception $e ) {
      // The call resulted in an error
      if( $coupon->discount_type == 'percent' )
        $percent_off = (int)$coupon->discount_amount;
      else
        $percent_off = (int)( ( ( $price - $coupon->discount_amount ) / $price ) * 100 );

      $stripe_args = array( "percent_off" => $percent_off,
                            "duration" => "forever", // We handle this in Memberpress
                            "id" => $coupon_id );

      try {
        $stripe_coupon = $this->send_stripe_request( 'coupons', $stripe_args );
      }
      catch( Exception $e ) {
        // Do nothing for now
        $stripe_coupon = false;
      }
    }

    return (object)$stripe_coupon; 
  }

  public function send_stripe_request( $endpoint,
                                       $args=array(),
                                       $method='post',
                                       $domain='https://api.stripe.com/v1/',
                                       $blocking=true ) {
    $uri = "{$domain}{$endpoint}";

    $arg_array = array( 'method'    => strtoupper($method),
                        'body'      => $args,
                        'timeout'   => 15,
                        'blocking'  => $blocking,
                        'sslverify' => false, // We assume the cert on stripe is trusted
                        'headers'   => array(
                          'Authorization' => "Basic " . base64_encode("{$this->settings->secret_key}:")
                        )
                      );

    $uid = uniqid();
    //$this->email_status("###{$uid} Stripe Call to {$uri} API Key: {$this->settings->secret_key}\n" . MeprUtils::object_to_string($arg_array, true) . "\n", $this->settings->debug);

    $resp = wp_remote_request( $uri, $arg_array );
    
    // If we're not blocking then the response is irrelevant
    // So we'll just return true.
    if( $blocking==false )
      return true;

    if( is_wp_error( $resp ) ) {
      throw new MeprHttpException( sprintf( __( 'You had an HTTP error connecting to %s' , 'memberpress'), $this->name ) );
    }
    else {
      if( null !== ( $json_res = json_decode( $resp['body'], true ) ) ) {
        //$this->email_status("###{$uid} Stripe Response from {$uri}\n" . MeprUtils::object_to_string($json_res, true) . "\n", $this->settings->debug);
        if( isset($json_res['error']) )
          throw new MeprRemoteException( "{$json_res['error']['message']} ({$json_res['error']['type']})" );
        else
          return $json_res;
      }
      else // Un-decipherable message
        throw new MeprRemoteException( sprintf( __( 'There was an issue with the credit card processor. Try again later.', 'memberpress'), $this->name ) );
    }
    
    return false;
  }

  public function get_default_card($sdata) {
    if( isset($sdata->active_card) ) { // Removed in version 2013-07-05 of stripe's API
      return $sdata->active_card;
    }
    else if( isset($sdata->default_card) ) { // Added in version 2013-07-05 of stripe's API
      foreach( $sdata->cards['data'] as $card ) {
        if($card['id']==$sdata->default_card) {
          return $card;
        }
      }
    }

    return false;
  }
}
