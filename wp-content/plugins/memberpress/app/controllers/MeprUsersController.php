<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprUsersController extends MeprBaseController
{
  public function load_hooks()
  {
    //Shortcodes
    add_shortcode('mepr-logout-link', 'MeprUsersController::logout_link');
    add_shortcode('mepr-login-link', 'MeprUsersController::logout_link');
    add_shortcode('logout_link', 'MeprUsersController::logout_link'); // For legacy purposes
    add_shortcode('mepr-login-form', 'MeprUsersController::render_login_form');
    add_shortcode('mepr-account-link', 'MeprUsersController::get_account_links');
    add_shortcode('mepr_account_link', 'MeprUsersController::get_account_links'); // Deprecated
    add_shortcode('mepr-account-info', 'MeprUsersController::output_account_meta');

    //Login page meta box
    add_action('add_meta_boxes', 'MeprUsersController::login_page_meta_box');
    add_action('save_post', 'MeprUsersController::save_postdata');
    
    add_action('wp_ajax_mepr_resend_welcome_email', 'MeprUsersController::resend_welcome_email_callback');
    add_action('delete_user', 'MeprUsersController::nullify_records_on_delete');
    add_action('admin_enqueue_scripts', 'MeprUsersController::enqueue_scripts');
    add_action('wp_enqueue_scripts', 'MeprUsersController::enqueue_front_end_scripts');
    
    //Login & Registration handling
    add_action('admin_init', 'MeprUsersController::maybe_redirect_member_from_admin');
    add_action('register_post', 'MeprUsersController::maybe_disable_wp_registration_form', 10, 3);
    add_action('plugins_loaded', 'MeprUsersController::maybe_disable_admin_bar');
    add_action('wp_logout', 'MeprUsersController::logout_redirect_override', 99999);
    add_filter('login_url', 'MeprUsersController::override_wp_login_url', 999999, 2);
    
    //Profile fields show/save
    add_action('show_user_profile', 'MeprUsersController::extra_profile_fields');
    add_action('edit_user_profile', 'MeprUsersController::extra_profile_fields');
    add_action('personal_options_update', 'MeprUsersController::save_extra_profile_fields');
    add_action('edit_user_profile_update', 'MeprUsersController::save_extra_profile_fields');
    add_action('user_profile_update_errors', 'MeprUsersController::validate_extra_profile_fields', 10, 3);
    add_action('wp_ajax_mepr_user_search', 'MeprUsersController::user_search');
    
    //User page extra columns
    add_filter('manage_users_columns', 'MeprUsersController::add_extra_user_columns');
    add_filter('manage_users_sortable_columns', 'MeprUsersController::sortable_extra_user_columns');
    add_filter('manage_users_custom_column', 'MeprUsersController::manage_extra_user_columns', 10, 3);
    add_action('pre_user_query', 'MeprUsersController::extra_user_columns_query_override');
  }
  
  public static function override_wp_login_url($url, $redirect) {
    $mepr_options = MeprOptions::fetch();
    
    if(is_admin() or !$mepr_options->force_login_page_url)
      return $url;
    
    if(!empty($redirect))
      $new_login_url = $mepr_options->login_page_url('redirect_to=' . $redirect);
    else
      $new_login_url = $mepr_options->login_page_url();
    
    return $new_login_url;
  }
  
  public static function logout_link($atts)
  {
    $current_post = MeprUtils::get_current_post();
    $mepr_options = MeprOptions::fetch();
    $permalink = get_permalink($current_post->ID);
    $arglist = '';
    
    if(isset($atts) and !empty($atts))
      foreach($atts as $att_key => $att_val)
        $arglist .= " {$att_key}=\"{$att_val}\"";
    
    ob_start();
    
    if(MeprUtils::is_user_logged_in())
    {
      ?>
      <a href="<?php echo apply_filters('mepr-logout-url', wp_logout_url($mepr_options->login_page_url("redirect_to=".urlencode($permalink)))); ?>"<?php echo $arglist; ?>><?php _e('Logout', 'memberpress'); ?></a>
      <?php
    }
    else
    {
      ?>
      <a href="<?php echo $mepr_options->login_page_url("redirect_to=".urlencode($permalink)); ?>"<?php echo $arglist; ?>><?php _e('Login', 'memberpress'); ?></a>
      <?php
    }
    
    return ob_get_clean();
  }
  
  public static function display_login_form($shortcode = false)
  {
    global $user_ID;
    $current_post = MeprUtils::get_current_post();
    $mepr_options = MeprOptions::fetch();
    $mepr_blogurl = home_url();
    $signup_url = $mepr_blogurl.'/wp-login.php?action=register'; //Needs to be changed eventually, just not sure what to?
    $login_page_id = (!empty($mepr_options->login_page_id) and $mepr_options->login_page_id > 0)?$mepr_options->login_page_id:0;
    
    extract($_REQUEST);
    
    $redirect_to = ((isset($redirect_to) and !empty($redirect_to))?$redirect_to:$mepr_options->login_redirect_url);
    $redirect_to = (empty($_REQUEST['redirect_to']) && $shortcode && !is_page($login_page_id))?get_permalink($current_post->ID):$redirect_to;
    $redirect_to = (isset($_REQUEST['mepr-unauth-page'])?get_permalink($_REQUEST['mepr-unauth-page']):$redirect_to);
    $redirect_to = apply_filters('mepr-login-redirect-url', $redirect_to);
    
    if($login_page_id)
    {
      $login_url = $mepr_options->login_page_url();
      $login_delim = MeprAppController::get_param_delimiter_char($login_url);
      $forgot_password_url = "{$login_url}{$login_delim}action=forgot_password";
    }
    else
    {
      $login_url = "{$mepr_blogurl}/wp-login.php";
      $forgot_password_url = "{$mepr_blogurl}/wp-login.php?action=lostpassword";
    }
    
    if(MeprUtils::is_user_logged_in())
    {
      $wp_user = get_user_by('id', $user_ID);
      //Need to override $redirect_to here if a per-product login redirect URL is set (but do not track a login event)
      $redirect_to = MeprProductsController::track_and_override_login_redirect_mepr($redirect_to, $wp_user, true, false);
      require(MEPR_VIEWS_PATH.'/shared/already_logged_in.php');
      return;
    }
    
    if(!empty($mepr_process_login_form) and !empty($errors))
      require(MEPR_VIEWS_PATH."/shared/errors.php");
    
    require(MEPR_VIEWS_PATH.'/shared/login_form.php');
  }
  
  public static function process_login_form()
  {
    $mepr_options = MeprOptions::fetch();
    
    $errors = MeprUser::validate_login($_POST, array());
    $errors = apply_filters('mepr-validate-login', $errors);
    
    extract($_POST);
      
    if(is_email($log)) {
      $user = get_user_by('email', $log);
      
      if($user !== false)
        $log = $user->user_login;
    }
    
    if(empty($errors))
    {
      $creds = array();
      $creds['user_login'] = $log;
      $creds['user_password'] = $pwd;
      $creds['remember'] = isset($rememberme);
      
      if(!function_exists('wp_signon'))
        require_once(ABSPATH . WPINC . '/user.php');
      
      $wp_user = wp_signon($creds);
      
      if(!isset($_POST['redirect_to']))
        $redirect_to = $mepr_options->login_redirect_url;
      else
        $redirect_to = $_POST['redirect_to'];
      
      $redirect_to = apply_filters('mepr-process-login-redirect-url', $redirect_to, $wp_user);
      
      MeprUtils::wp_redirect($redirect_to);
    }
    else
      $_REQUEST['errors'] = $errors;
  }
  
  public static function logout_redirect_override() {
    $mepr_options = MeprOptions::fetch();
    
    if(isset($mepr_options->logout_redirect_url) && !empty($mepr_options->logout_redirect_url)) {
      MeprUtils::wp_redirect($mepr_options->logout_redirect_url);
      exit;
    }
  }
  
  public static function display_signup_form($product)
  {
    $mepr_options = MeprOptions::fetch();
    $mepr_blogurl = home_url();
    $mepr_coupon_code = '';
    
    extract($_POST);
    
    //See if Coupon was passed via GET
    if(isset($_GET['coupon']) && !empty($_GET['coupon']))
      if(MeprCoupon::is_valid_coupon_code($_GET['coupon'], $product->ID))
        $mepr_coupon_code = $_GET['coupon'];
    
    if(isset($errors) and !empty($errors))
      require(MEPR_VIEWS_PATH."/shared/errors.php");
    
    if(MeprUtils::is_user_logged_in()) {
      $mepr_current_user = MeprUtils::get_currentuserinfo();
      require(MEPR_VIEWS_PATH.'/shared/logged_in_purchases.php');
    }
    else
      require(MEPR_VIEWS_PATH.'/shared/signup_form.php');
  }
  
  /** Gets called on the 'init' hook ... used for processing aspects of the signup
    * form before the logic progresses on to 'the_content' ...
    */
  public static function process_signup_form($renew = false)
  {
    $mepr_options = MeprOptions::fetch();

    if($renew)
      self::process_renewal();
    elseif(!MeprUtils::is_user_logged_in())
    {
      extract($_POST);
      
      $errors = MeprUser::validate_signup($_POST, array());
      $errors = apply_filters('mepr-validate-signup', $errors);
      
      if(empty($errors))
      {
        $user = new MeprUser();
        $user->user_login = ($mepr_options->username_is_email)?$user_email:$user_login;
        $user->first_name = $user_first_name;
        $user->last_name = $user_last_name;
        $user->user_email = $user_email;
        $user->rec->user_pass  = $mepr_user_password; //Have to use rec here because we unset user_pass on __contruct
        $user_id = $user->store();
        
        if(!is_a($user_id, 'WP_Error'))
        {
          self::save_extra_profile_fields($user_id, true);
          $creds = array('user_login' => $user->user_login, 'user_password' => $mepr_user_password);
          
          //if false isn't specified https will be used by the wp_signon func if https is the active protocol
          //so let's make sure non-secure cookie is the option here -- let's see how this goes
          wp_signon($creds, false);
          
          $coupon = (object)array('ID' => 0);
          $product = new MeprProduct($mepr_product_id);
          $product_price = $product->adjusted_price(); //Set price, adjust it later if coupon applies
          
          if(isset($mepr_coupon_code) && !empty($mepr_coupon_code))
          {
            //Coupon object has to be loaded here or else txn create will record a 0 for coupon_id
            $coupon = MeprCoupon::get_one_from_code($mepr_coupon_code);
            
            if($coupon !== false)
              $product_price = $product->adjusted_price($coupon->post_title);
            else
              $coupon = (object)array('ID' => 0);
          }
          
          // At this point we assume that the transaction is free ...
          // otherwise the validation would have failed miserably by now
          $pm = (!isset($mepr_payment_method) or empty($mepr_payment_method))?MeprTransaction::$free_gateway_str:$mepr_payment_method;
          $transaction_id = MeprTransaction::create( $product_price, $user_id, $mepr_product_id,
                                                     MeprTransaction::$payment_str,
                                                     MeprTransaction::$pending_str,
                                                     $coupon->ID, '', 0, 0, $pm,
                                                     null, null, null, 
                                                     $_SERVER['REMOTE_ADDR'] );

          do_action('mepr-track-signup', $product_price, $user, $mepr_product_id, $transaction_id);
          do_action('mepr-process-signup', $product_price, $user, $mepr_product_id, $transaction_id);

          self::process_signup_form_payment_method($pm, $product_price, $user, $mepr_product_id, $transaction_id);
        }
        else
          foreach($user_id->errors as $errors)
            foreach($errors as $error)
              $_POST['errors'][] = $error;
      }
      else
        $_POST['errors'] = $errors;
    }
    else // Just go for it if the user is logged in
    {
      $mepr_current_user = MeprUtils::get_currentuserinfo();
      $coupon = (object)array('ID' => 0);
      extract($_REQUEST);

      $errors = MeprUser::validate_signup($_POST, array());
      $errors = apply_filters('mepr-validate-signup', $errors);

      if(empty($errors))
      {
        //Save the address fields if shown (already validated above)
        if($mepr_options->show_address_fields && $mepr_options->show_address_fields_logged_in)
          self::save_address_fields($mepr_current_user->ID);
        
        $product = new MeprProduct($mepr_product_id);
        $product_price = $product->adjusted_price(); //Set price, adjust it later if coupon applies

        if(isset($mepr_coupon_code) && !empty($mepr_coupon_code))
        {
          //Coupon object has to be loaded here or else txn create will record a 0 for coupon_id
          $coupon = MeprCoupon::get_one_from_code($mepr_coupon_code);

          if($coupon !== false)
            $product_price = $product->adjusted_price($coupon->post_title);
          else
            $coupon = (object)array('ID' => 0);
        }

        // At this point we assume that the transaction is free ...
        // otherwise the validation would have failed miserably by now
        $pm = (!isset($mepr_payment_method) or empty($mepr_payment_method))?MeprTransaction::$free_gateway_str:$mepr_payment_method;
        $transaction_id = MeprTransaction::create( $product_price,
                                                   $mepr_current_user->ID,
                                                   $mepr_product_id,
                                                   MeprTransaction::$payment_str,
                                                   MeprTransaction::$pending_str,
                                                   $coupon->ID, '', 0, 0, $pm,
                                                   null, null, null,
                                                   $_SERVER['REMOTE_ADDR'] );

        do_action('mepr-track-signup',   $product_price, $mepr_current_user, $mepr_product_id, $transaction_id);
        do_action('mepr-process-signup', $product_price, $mepr_current_user, $mepr_product_id, $transaction_id);

        self::process_signup_form_payment_method($pm, $product_price, $mepr_current_user, $mepr_product_id, $transaction_id);
      }
      else
        $_POST['errors'] = $errors;
    }
  }

  public static function process_renewal()
  {
    $mepr_options = MeprOptions::fetch();
    
    try
    {
      // extract user id & previous transaction id
      if(!isset($_GET['uid']) or !isset($_GET['tid']))
        throw new Exception(__("uid or tid wasn't present", 'memberpress'));
      
      $user = new MeprUser();
      $user->load_user_data_by_uuid(esc_html($_GET['uid']));
      
      if(!isset($user->ID) or !is_numeric($user->ID))
        throw new Exception(__("\$user->ID wasn't set or wasn't an actual id", 'memberpress'));
      
      $txn = new MeprTransaction(esc_html($_GET['tid']));
      $product = new MeprProduct($txn->product_id);
      $pm = $mepr_options->payment_method($txn->gateway);
      
      if(!($pm instanceof MeprBaseRealGateway))
        throw new Exception(__('Transaction Payment Method is invalid', 'memberpress'));
      
      //if( isset($pm->recurrence_type) and $pm->recurrence_type == 'automatic' )
      //  throw new Exception(__('Recurrence type is not supported'));
      
      $txn->id = null; // we're just going to re-purpose this object
      
      // When renewing, we'll base the created_at on the
      // expired_at date until after the transaction expires
      if($txn->is_expired())
        $txn->created_at = MeprUtils::ts_to_mysql_date(time());
      else
        $txn->created_at = $txn->expired_at;
      
      //DO NOT USE THIS METHOD TO CALCULATE THE RECURRING PRICE
      //THE RECURRING PRICE SHOULD BE BASED OFF OF THE SUBSCR PRICE
      //OTHERWISE WE COULD GET INVALID PRICES BECAUSE COUPONS
      //CAN CHANGE OVER TIME
      // if(isset($txn->coupon_id) and !empty($txn->coupon_id))
      // {
        // $coupon = new MeprCoupon($txn->coupon_id);
        // $txn->amount = $product->adjusted_price($coupon->post_title);
      // }
      // else
        // $txn->amount = $product->adjusted_price();
      
      $txn->response   = null;
      //$txn->gateway    = $_GET['pm'];
      $txn->expired_at = null;
      $txn->trans_num  = null;
      $txn->status     = 'pending-renewal'; // This txn is pending-renewal now

      // figure out some way to pass the type (renewal) on to the PayPal Gateway
      do_action('mepr-track-signup', $txn->amount, $user, $product->ID, $txn->id);
      do_action('mepr-process-signup', $txn->amount, $user, $product->ID, $txn->id);
      
      self::process_signup_form_payment_method($txn->gateway, $txn->amount, $user->ID, $product->ID, $txn->id);
    }
    catch (Exception $e)
    {
      $error = __("You're unauthorized to view this resource", 'memberpress');
      $_POST['errors'][] = $error;
    }
  }
  
  public static function process_signup_form_payment_method($method, $amount, $user, $product_id, $transaction_id)
  {
    $mepr_options = MeprOptions::fetch();

    if($method == MeprTransaction::$free_gateway_str) {
      $txn = new MeprTransaction($transaction_id);
      MeprTransaction::create_free_transaction($txn);
    }
    else if($pm = $mepr_options->payment_method($method) and $pm instanceof MeprBaseRealGateway)
    {
      $prd = new MeprProduct($product_id);
      $txn = new MeprTransaction($transaction_id);

      //Fake-set a coupon code
      $coupon = (object)array('ID' => 0, 'post_title' => null);
      if($txn->coupon_id)
        $coupon = new MeprCoupon($txn->coupon_id);

      // Create a new subscription
      if(!$prd->is_one_time_payment())
      {
        $sub = new MeprSubscription();
        $sub->user_id = $user->ID;
        $sub->ip_addr = $_SERVER['REMOTE_ADDR'];
        $sub->product_id = $prd->ID;
        //$sub->product_meta = (array)$prd->rec;
        $sub->price = $prd->adjusted_price($coupon->post_title);
        $sub->coupon_id = $coupon->ID;
        $sub->period = $prd->period;
        $sub->period_type = $prd->period_type;
        $sub->limit_cycles = $prd->limit_cycles;
        $sub->limit_cycles_num = $prd->limit_cycles_num;
        $sub->limit_cycles_action = $prd->limit_cycles_action;
        $sub->trial = $prd->trial;
        $sub->trial_days = $prd->trial_days;
        $sub->trial_amount = $prd->trial_amount;
        $sub->status = MeprSubscription::$pending_str;
        $sub->gateway = $pm->id;

        // Override subscription trial if there's one set in the coupon
        if( $coupon instanceof MeprCoupon and
            $coupon->is_valid( $prd->ID ) and
            $coupon->trial ) {
          $sub->trial = $coupon->trial;
          $sub->trial_days = $coupon->trial_days;
          $sub->trial_amount = $coupon->trial_amount;
        }

        $sub->maybe_prorate(); // sub to sub
        $sub->store();

        $txn->subscription_id = $sub->ID;
        $txn->store();
      }

      $pm->process_signup_form($txn);
    }
    
    // Artificially set the payment method params so we can use them downstream
    // when display_payment_form is called in the 'the_content' action. Yeah,
    // I know this isn't the best solution ever but in this case it will save us
    // so much headache that I think its the best solution here for now.
    $_REQUEST['payment_method_params'] = compact('method', 'amount', 'user', 'product_id', 'transaction_id');
  }
  
  public static function process_payment_form()
  {
    if( isset($_POST['mepr_process_payment_form']) and
        isset($_POST['mepr_transaction_id']) and
        is_numeric($_POST['mepr_transaction_id']) )
    {
      $txn = new MeprTransaction($_POST['mepr_transaction_id']);
      
      if($txn->rec != false)
      {
        $mepr_options = MeprOptions::fetch();
        if( $pm = $mepr_options->payment_method($txn->gateway) and
            $pm instanceof MeprBaseRealGateway )
        {
          $errors = $pm->validate_payment_form(array());
          
          if(empty($errors))
          {
            // process_payment_form either returns true
            // for success or an array of $errors on failure
            try {
              $pm->process_payment_form($txn);
            }
            catch( Exception $e ) {
              do_action('mepr_payment_failure', $txn);
              $errors = array( $e->getMessage() );
            }
          }
          
          if(empty($errors))
          {
            //Reload the txn now that it should have a proper trans_num set
            $txn = new MeprTransaction($txn->id);
            MeprUtils::wp_redirect($mepr_options->thankyou_page_url("trans_num={$txn->trans_num}"));
          }
          else
          {
            // Artificially set the payment method params so we can use them downstream
            // when display_payment_form is called in the 'the_content' action. Yeah,
            // I know this isn't the best solution ever but in this case it will save us
            // so much headache that I think its the best solution here for now.
            $_REQUEST['payment_method_params'] = array( 'method' => $pm->id,
                                                        'amount' => $txn->amount,
                                                        'user' => new MeprUser($txn->user_id),
                                                        'product_id' => $txn->product_id,
                                                        'transaction_id' => $txn->id );
            $_REQUEST['mepr_payment_method'] = $pm->id;
            $_POST['errors'] = $errors;
          }
        }
      }
    }
  }
  
  // Called in the 'the_content' hook ... used to display a signup form
  public static function display_payment_form()
  {
    $mepr_options = MeprOptions::fetch();
    
    if(isset($_REQUEST['payment_method_params']))
    {
      extract($_REQUEST['payment_method_params']);
      
      if(isset($_POST['errors']) and !empty($_POST['errors'])) {
        $errors = $_POST['errors'];
        include(MEPR_VIEWS_PATH . "/shared/errors.php");
      }
      
      if($intg = $mepr_options->payment_method($method) and $intg instanceof MeprBaseRealGateway)
        $intg->display_payment_form($amount, $user, $product_id, $transaction_id);
    }
  }
  
  public static function display_forgot_password_form()
  {
    $mepr_options = MeprOptions::fetch();
    $mepr_blogurl = home_url();
    
    $process = MeprAppController::get_param('mepr_process_forgot_password_form');
    
    if(empty($process))
      require(MEPR_VIEWS_PATH.'/users/forgot_password.php');
    else
      self::process_forgot_password_form();
  }
  
  public static function process_forgot_password_form()
  {
    $mepr_options = MeprOptions::fetch();
    
    $errors = MeprUser::validate_forgot_password($_POST,array());
    
    extract($_POST);
    
    if(empty($errors))
    {
      $is_email = (is_email($mepr_user_or_email) and email_exists($mepr_user_or_email));

      $is_username = username_exists($mepr_user_or_email);
      
      $user = new MeprUser();
      
      // If the username & email are identical then let's rely on it as a username first and foremost
      if($is_username)
        $user->load_user_data_by_login($mepr_user_or_email);
      else if($is_email)
        $user->load_user_data_by_email($mepr_user_or_email);
      
      if($user->ID)
      {
        $user->send_reset_password_requested_notification();
        
        require(MEPR_VIEWS_PATH."/users/forgot_password_requested.php");
      }
      else
        require(MEPR_VIEWS_PATH."/shared/unknown_error.php");
    }
    else
    {
      require(MEPR_VIEWS_PATH."/shared/errors.php");
      require(MEPR_VIEWS_PATH.'/users/forgot_password.php');
    }
  }
  
  public static function display_reset_password_form($mepr_key, $mepr_screenname)
  {
    $user = new MeprUser();
    $user->load_user_data_by_login($mepr_screenname);
    
    if($user->ID)
    {
      if($user->reset_form_key_is_valid($mepr_key))
        require(MEPR_VIEWS_PATH.'/users/reset_password.php');
      else
        require(MEPR_VIEWS_PATH.'/shared/unauthorized.php');
    }
    else
      require(MEPR_VIEWS_PATH.'/shared/unauthorized.php');
  }
  
  public static function process_reset_password_form()
  {
    $mepr_options = MeprOptions::fetch();
    $errors = MeprUser::validate_reset_password($_POST,array());
    
    extract($_POST);
    
    if(empty($errors))
    {
      $user = new MeprUser();
      $user->load_user_data_by_login($mepr_screenname);
      
      if($user->ID)
      {
        $user->set_password_and_send_notifications($mepr_key, $mepr_user_password);
        
        require(MEPR_VIEWS_PATH."/users/reset_password_thankyou.php");
      }
      else
        require(MEPR_VIEWS_PATH."/shared/unknown_error.php");
    }
    else
    {
      require(MEPR_VIEWS_PATH."/shared/errors.php");
      require(MEPR_VIEWS_PATH.'/users/reset_password.php');
    }
  }
  
  public static function display_unauthorized_page()
  {
    if(MeprUtils::is_user_logged_in())
      require(MEPR_VIEWS_PATH.'/shared/member_unauthorized.php');
    else
      require(MEPR_VIEWS_PATH.'/shared/unauthorized.php');
  }
  
  public static function resend_welcome_email_callback()
  {
    $mepr_options = MeprOptions::fetch();
    
    if(wp_verify_nonce($_REQUEST['_mepr_nonce'], 'mepr-resend-welcome-email'))
    {
      if(MeprUtils::is_logged_in_and_an_admin())
      {
        $usr = new MeprUser($_REQUEST['uid']);
        
        // Get the most recent transaction
        $txns = MeprTransaction::get_all_complete_by_user_id( $usr->ID,
                                                             'created_at DESC', /* $order_by='' */
                                                             '1', /* $limit='' */
                                                             false, /* $count=false */
                                                             false, /* $exclude_expired=false */
                                                             true /* $include_confirmations=false */ );
        
        if(count($txns) <= 0)
          die(__('This user hasn\'t purchased any products - so no email will be sent.', 'memberpress'));
        
        $txn = new MeprTransaction($txns[0]->id);

        $params = MeprTransactionsHelper::get_email_params($txn);  
        $usr = $txn->user();

        try {
          $uemail = MeprEmailFactory::fetch('MeprUserWelcomeEmail');
          $uemail->to = $usr->formatted_email();
          $uemail->send($params);
          die(__('Message Sent', 'memberpress'));
        }
        catch( Exception $e ) {
          die(__('There was an issue sending the email', 'memberpress'));
        }
      }
      die(__('Why you creepin\'?', 'memberpress'));
    }
    die(__('Cannot resend message', 'memberpress'));
  }
  
  public static function nullify_records_on_delete($id)
  {
    MeprTransaction::nullify_user_id_on_delete($id);
    MeprSubscription::nullify_user_id_on_delete($id);
    
    return $id;
  }
  
  public static function email_users_with_expiring_transactions()
  {
    return MeprUser::email_users_with_expiring_transactions();
  }
  
  //public static function unschedule_email_users_with_expiring_transactions()
  //{
  //  if($t = wp_next_scheduled('mepr_schedule_renew_emails'))
  //    wp_unschedule_event($t, 'mepr_schedule_renew_emails');
  //}
  
  public static function enqueue_scripts($hook)
  {
    global $wp_scripts;
    $ui = $wp_scripts->query('jquery-ui-core');
    $url = "//ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.css";
    
    if($hook == 'user-edit.php' || $hook == 'profile.php')
    {
      wp_enqueue_style('mepr-jquery-ui-smoothness', $url);
      wp_enqueue_script('mepr-date-picker-js', MEPR_JS_URL.'/date_picker.js', array('jquery-ui-datepicker'), MEPR_VERSION);
      wp_enqueue_script('jquery-clippy', MEPR_JS_URL.'/jquery.clippy.js', array('jquery'));
      wp_enqueue_script('memberpress-edit-user', MEPR_JS_URL.'/admin_profile.js', array('jquery', 'jquery-clippy', 'suggest'), MEPR_VERSION);
      wp_localize_script('memberpress-edit-user', 'clippy', array('url' => MEPR_JS_URL.'/clippy.swf'));
    }
  }
  
  public static function enqueue_front_end_scripts()
  {
    global $post;
    $mepr_options = MeprOptions::fetch();
    
    if(MeprProduct::is_product_page($post))
    {
      wp_enqueue_style('memberpress-signup',  MEPR_CSS_URL.'/signup.css', array());
      wp_enqueue_script('memberpress-signup', MEPR_JS_URL.'/signup.js', array('jquery'));
      
      if( isset($_REQUEST['mepr_payment_method']) and
          $pm = $mepr_options->payment_method($_REQUEST['mepr_payment_method']) and
          $pm instanceof MeprBaseRealGateway )
        $pm->enqueue_payment_form_scripts();
    }
  }
  
  public static function extra_profile_fields($wpuser)
  {
    $mepr_options = MeprOptions::fetch();
    $user = new MeprUser($wpuser->ID);
    
    require(MEPR_VIEWS_PATH."/users/extra_profile_fields.php");
  }
  
  public static function save_extra_profile_fields($user_id, $validated = false)
  {
    $mepr_options = MeprOptions::fetch();
    
    if(isset($_POST[MeprUser::$user_message_str]) && !empty($_POST[MeprUser::$user_message_str]))
      update_user_meta($user_id, MeprUser::$user_message_str, $_POST[MeprUser::$user_message_str]);
    
    $custom_fields = $mepr_options->custom_fields;
    $errors = array();
    
    //Since we use user_* for these, we need to artifically set the $_POST keys correctly for this to work
    if(!isset($_POST['first_name']) || empty($_POST['first_name']))
      $_POST['first_name'] = (isset($_POST['user_first_name']))?stripslashes($_POST['user_first_name']):'';
    if(!isset($_POST['last_name']) || empty($_POST['last_name']))
      $_POST['last_name'] = (isset($_POST['user_last_name']))?stripslashes($_POST['user_last_name']):'';
    $custom_fields[] = (object)array('field_key' => 'first_name', 'field_type' => 'text');
    $custom_fields[] = (object)array('field_key' => 'last_name', 'field_type' => 'text');
    
    if($mepr_options->show_address_fields)
      $custom_fields = array_merge($mepr_options->address_fields, $custom_fields);
    
    //Even though the validate_extra_profile_fields function will show an error on the
    //dashboard profile. It doesn't prevent the profile from saving for some reason.
    //So let's take care of that here. $validated should ALWAYS be true, except in this one case
    if(!$validated)
      $errors = self::validate_extra_profile_fields();
    
    if(empty($errors))
    {
      foreach($custom_fields as $line)
        if(isset($_POST[$line->field_key]) && !empty($_POST[$line->field_key]))
          update_user_meta($user_id, $line->field_key, $_POST[$line->field_key]);
        else
          update_user_meta($user_id, $line->field_key, '');
      
      return true;
    }
    
    return false;
  }
  
  //Should be moved to the Model eventually
  //This should be run before MeprUsersController::save_extra_profile_fields is run
  public static function validate_extra_profile_fields($errors = null, $update = null, $user  = null, $is_signup = false)
  {
    //Prevent checking when adding a new user via WP's New User system in the dashboard
    if($update === false)
      return;
    
    $mepr_options = MeprOptions::fetch();
    $custom_fields = $mepr_options->custom_fields;
    $errs = array();
    
    if($mepr_options->show_address_fields)
      $custom_fields = array_merge($mepr_options->address_fields, $custom_fields);
    
    foreach($custom_fields as $line)
    {
      //If we're processing a signup and the custom field is not set to show on signup
      //we need to make sure it isn't required
      if($is_signup && $line->required && !$line->show_on_signup)
        $line->required = false;
      
      if((!isset($_POST[$line->field_key]) || empty($_POST[$line->field_key])) && $line->required)
      {
        $errs[] = stripslashes($line->field_name).' '.__('is required.', 'memberpress');
        
        //This allows us to run this on dashboard profile fields as well as front end
        if(is_object($errors))
          $errors->add($line->field_key, stripslashes($line->field_name).' '.__('is required.', 'memberpress'));
      }
    }
    
    return $errs;
  }
  
  //Should be moved to the Model eventually
  public static function validate_address_fields()
  {
    $mepr_options = MeprOptions::fetch();
    $custom_fields = $mepr_options->address_fields;
    $errs = array();
    
    foreach($custom_fields as $line)
      if((!isset($_POST[$line->field_key]) || empty($_POST[$line->field_key])) && $line->required)
        $errs[] = stripslashes($line->field_name).' '.__('is required.', 'memberpress');
    
    return $errs;
  }
  
  public static function save_address_fields($user_id) {
    $mepr_options = MeprOptions::fetch();
    
    foreach($mepr_options->address_fields as $line) {
      if(isset($_POST[$line->field_key]) && !empty($_POST[$line->field_key]))
        update_user_meta($user_id, $line->field_key, $_POST[$line->field_key]);
      else
        update_user_meta($user_id, $line->field_key, '');
    }
  }
  
  public static function save_new_password($user_id, $new_pass, $new_pass_confirm)
  {
    $mepr_options = MeprOptions::fetch();
    $account_url = $mepr_options->account_page_url();
    $delim = MeprAppController::get_param_delimiter_char($account_url);
    
    if($user_id)
      if(($new_pass == $new_pass_confirm) && !empty($new_pass))
      {
        $user = new MeprUser($user_id);
        $user->rec->user_pass = $new_pass;
        $user->store();
        MeprUtils::wp_redirect($account_url.$delim.'action=newpassword&message=success');
      }
    
    MeprUtils::wp_redirect($account_url.$delim.'action=newpassword&message=failed');
  }
  
  public static function get_account_links()
  {
    $mepr_options = MeprOptions::fetch();
    ob_start();
    if(MeprUtils::is_user_logged_in())
    {
      $account_url = $mepr_options->account_page_url();
      $logout_url = MeprUtils::logout_url();
      require(MEPR_VIEWS_PATH."/users/logged_in_template.php");
    }
    else
    {
      $login_url = MeprUtils::login_url();
      require(MEPR_VIEWS_PATH."/users/logged_out_template.php");
    }
    return ob_get_clean();
  }
  
  public static function account_links_widget($args)
  {
    $mepr_options = MeprOptions::fetch();
    
    extract($args);
    
    echo $before_widget;
    echo $before_title.__('Account', 'memberpress').$after_title;
    if(MeprUtils::is_user_logged_in())
    {
      $account_url = $mepr_options->account_page_url();
      $logout_url = MeprUtils::logout_url();
      require(MEPR_VIEWS_PATH."/users/logged_in_widget.php");
    }
    else
    {
      $login_url = MeprUtils::login_url();
      require(MEPR_VIEWS_PATH."/users/logged_out_widget.php");
    }
    echo $after_widget;
  }

  public static function user_search()
  {
    if(!current_user_can('list_users'))
      die('-1');
    
    $s = $_GET['q']; // is this slashed already?
    
    $s = trim($s);
    if(strlen($s) < 2)
      die; // require 2 chars for matching
    
    $users = get_users(array('search' => "*$s*"));
    require(MEPR_VIEWS_PATH.'/users/search.php');
    die;
  }
  
  //Add extra columns to the Users list table
  public static function add_extra_user_columns($columns)
  {
    $columns['mepr_products'] = __('Active Products', 'memberpress');
    $columns['mepr_registered'] = __('Registered', 'memberpress');
    $columns['mepr_last_login'] = __('Last Login', 'memberpress');
    $columns['mepr_num_logins'] = __('# Logins', 'memberpress');
    
    return $columns;
  }
  
  //Tells WP which columns should be sortable
  public static function sortable_extra_user_columns($cols)
  {
    $cols['mepr_registered'] = 'user_registered';
    $cols['mepr_last_login'] = 'last_login';
    $cols['mepr_num_logins'] = 'num_logins';
    
    return $cols;
  }
  
  //This allows us to sort the column properly behind the scenes
  public static function extra_user_columns_query_override($query)
  {
    global $wpdb;
    $vars = $query->query_vars;
    $mepr_db = new MeprDb();
    
    if(isset($vars['orderby']) && $vars['orderby'] == 'last_login')
    {
      $query->query_fields .= ", (SELECT e.created_at FROM {$mepr_db->events} AS e WHERE {$wpdb->users}.ID = e.evt_id AND e.evt_id_type='" . MeprEvent::$users_str . "' AND e.event = '" . MeprEvent::$login_event_str . "' ORDER BY e.created_at DESC LIMIT 1) AS last_login";
      $query->query_orderby = "ORDER BY last_login {$vars['order']}";
    }
    
    if(isset($vars['orderby']) && $vars['orderby'] == 'num_logins')
    {
      $query->query_fields .= ", (SELECT count(*) FROM {$mepr_db->events} AS e WHERE {$wpdb->users}.ID = e.evt_id AND e.evt_id_type='" . MeprEvent::$users_str . "' AND e.event = '" . MeprEvent::$login_event_str . "') AS num_logins";
      $query->query_orderby = "ORDER BY num_logins {$vars['order']}";
    }
  }
  
  //This actually shows the content in the table HTML output
  public static function manage_extra_user_columns($value, $column_name, $user_id)
  {
    $user = new MeprUser($user_id);
    
    if($column_name == 'mepr_registered')
    {
      $registered = strtotime($user->user_registered);
      return date_i18n('M j, Y', $registered) . '<br/>' . date_i18n('g:i A', $registered);
    }
    
    if($column_name == 'mepr_products')
    {
      $titles = $user->get_active_subscription_titles("<br/>");
      
      if(!empty($titles))
        return $titles;
      else
        return __('None', 'memberpress');
    }
    
    if($column_name == 'mepr_last_login')
    {
      $login = $user->get_last_login_data();
      
      if(!empty($login))
        return date_i18n('M j, Y', strtotime($login->created_at)) . '<br/>' . date_i18n('g:i A', strtotime($login->created_at));
      else
        return __('Never', 'memberpress');
    }
    
    if($column_name == 'mepr_num_logins')
      return (int)$user->get_num_logins();
    
    return $value;
  }
  
  public static function maybe_redirect_member_from_admin()
  {
    $mepr_options = MeprOptions::fetch();
    
    if(defined('DOING_AJAX'))
      return;
    
    if($mepr_options->lock_wp_admin && !current_user_can('edit_posts'))
      if(isset($mepr_options->login_redirect_url) && !empty($mepr_options->login_redirect_url))
        MeprUtils::wp_redirect($mepr_options->login_redirect_url);
      else
        MeprUtils::wp_redirect(home_url());
  }
  
  public static function maybe_disable_wp_registration_form($login, $email, $errors)
  {
    $mepr_options = MeprOptions::fetch();
    
    if($mepr_options->disable_wp_registration_form)
    {
      $message = __('You cannot register with this form. Please use the registration page found on the website instead.', 'memberpress');
      $errors->add('mepr_disabled_error', $message);
    }
  }
  
  public static function maybe_disable_admin_bar()
  {
    $mepr_options = MeprOptions::fetch();
    
    if(!current_user_can('edit_posts') && $mepr_options->disable_wp_admin_bar)
      show_admin_bar(false);
  }
  
  public static function login_page_meta_box()
  {
    global $post;
    $mepr_options = MeprOptions::fetch();
    
    if(isset($post) && $post instanceof WP_Post && $post->ID == $mepr_options->login_page_id)
    {
      add_meta_box('mepr_login_page_meta_box', __('MemberPress Settings', 'memberpress'), 'MeprUsersController::show_login_page_meta_box', 'page', 'normal', 'high');
    }
  }
  
  public static function show_login_page_meta_box()
  {
    global $post;
    $mepr_options = MeprOptions::fetch();
    
    if(isset($post) && $post->ID)
    {
      $manual_login_form = get_post_meta($post->ID, '_mepr_manual_login_form', true);
      
      require(MEPR_VIEWS_PATH.'/users/login_page_meta_box.php');
    }
  }

  public static function save_postdata($post_id)
  {
    $post = get_post($post_id);
    $mepr_options = MeprOptions::fetch();
    
    if(!wp_verify_nonce((isset($_POST[MeprUser::$nonce_str]))?$_POST[MeprUser::$nonce_str]:'', MeprUser::$nonce_str.wp_salt()))
      return $post_id; //Nonce prevents meta data from being wiped on move to trash
    
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
      return $post_id;
    
    if(defined('DOING_AJAX'))
      return;
    
    if(!empty($post) && $post->ID == $mepr_options->login_page_id)
    {
      $manual_login_form = (isset($_POST['_mepr_manual_login_form']));
      update_post_meta($post->ID, '_mepr_manual_login_form', $manual_login_form);
    }
  }

  public static function render_login_form($atts=array(), $content = '', $shortcode = true)
  {
    //No need to validate anything here as it's done in the function below
    //This is just a wrapper for the shortcode to use
    ob_start();
    
    self::display_login_form($shortcode);
    
    return ob_get_clean();
  }
  
  public static function output_account_meta($atts = array(), $content = '') {
    global $mepr_options, $user_ID;
    
    if((int)$user_ID < 1 || !isset($atts['field']))
      return '';
    
    static $usermeta;
    
    if(!isset($usermeta) || !empty($usermeta)) {
      $userdata = get_userdata($user_ID);
      $usermeta = get_user_meta($user_ID);
    }
    
    foreach($userdata->data as $key => $value) {
      $usermeta[$key] = array($value);
    }
    
    //We can begin to define more custom return cases in here...
    switch($atts['field']) {
      case 'full_name':
        return ucfirst($usermeta['first_name'][0]) . ' ' . ucfirst($usermeta['last_name'][0]);
        break;
      case 'full_name_last_first':
        return ucfirst($usermeta['last_name'][0]) . ', ' . ucfirst($usermeta['first_name'][0]);
        break;
      case 'first_name_last_initial':
        return ucfirst($usermeta['first_name'][0]) . ' ' . ucfirst($usermeta['last_name'][0][0]) . '.';
        break;
      case 'last_name_first_initial':
        return ucfirst($usermeta['last_name'][0]) . ', ' . ucfirst($usermeta['first_name'][0][0]) . '.';
        break;
      default:
        return $usermeta[$atts['field']][0];
        break;
    }
  }
} //End class
