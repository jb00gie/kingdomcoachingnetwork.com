<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprUser extends MeprBaseModel
{
  public static $id_str           = 'ID';
  public static $first_name_str   = 'first_name';
  public static $last_name_str    = 'last_name';
  public static $username_str     = 'user_login';
  public static $email_str        = 'user_email';
  public static $password_str     = 'user_pass';
  public static $user_message_str = 'mepr_user_message';
  public static $uuid_str         = 'uuid';
  public static $user_ip_str      = 'user_ip';
  
  public static $nonce_str        = 'mepr_users_nonce';
  
  // Used to prevent welcome notification from sending multiple times
  public static $signup_notice_sent_str = 'signup_notice_sent';
  
  /** Defaults to loading by id **/
  public function __construct($id = null)
  {
    $this->attrs = array();
    $this->initialize_new_user(); //A bit redundant I know - But this prevents a nasty error when Standards = STRICT in PHP
    $this->load_user_data_by_id($id);
  }
  
  public function load_user_data_by_id($id = null) /*tested*/
  {
    if(empty($id) or !is_numeric($id))
      $this->initialize_new_user();
    else
    {
      $wp_user_obj = MeprUtils::get_user_by('id', $id);
      $this->load_wp_user($wp_user_obj);
      $this->load_meta();
    }
    
    // This must be here to ensure that we don't pull an encrypted 
    // password, encrypt it a second time and store it
    unset($this->user_pass);
  }
  
  public function load_user_data_by_login($login = null) /*tested*/
  {
    if(empty($login))
      $this->initialize_new_user();
    else
    {
      $wp_user_obj = MeprUtils::get_user_by('login', $login);
      $this->load_wp_user($wp_user_obj);
      $this->load_meta($wp_user_obj);
    }
    
    // This must be here to ensure that we don't pull an encrypted 
    // password, encrypt it a second time and store it
    unset($this->user_pass);
  }
  
  public function load_user_data_by_email($email = null) /*tested*/
  {
    if(empty($email))
      $this->initialize_new_user();
    else
    {
      $wp_user_obj = MeprUtils::get_user_by('email', $email);
      $this->load_wp_user($wp_user_obj);
      $this->load_meta($wp_user_obj);
    }
    
    // This must be here to ensure that we don't pull an encrypted 
    // password, encrypt it a second time and store it
    unset($this->user_pass);
  }
  
  public function load_user_data_by_uuid($uuid = null)
  {
    global $wpdb;
    $query = "SELECT * FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1";
    $query = $wpdb->prepare($query, self::$uuid_str, $uuid);
    $row = $wpdb->get_row($query);
    
    if($row and isset($row->user_id) and is_numeric($row->user_id))
      return $this->load_user_data_by_id($row->user_id);
    else
      return false;
  }
  
  protected function initialize_new_user()
  {
    if(!isset($this->attrs) or !is_array($this->attrs))
      $this->attrs = array();
    
    $u = array( "ID"                  => null,
                "first_name"          => null,
                "last_name"           => null,
                "user_login"          => null,
                "user_nicename"       => null,
                "user_email"          => null,
                "user_url"            => null,
                "user_pass"           => null,
                "user_message"        => null,
                "user_registered"     => null,
                "user_activation_key" => null, 	
                "user_status"         => null,
                "user_ip"             => null,
                "signup_notice_sent"  => null,
                "display_name"        => null );
    
    // Initialize user_meta variables
    foreach($this->attrs as $var)
      $u[$var] = null;
    
    $this->rec = (object)$u;
    
    return $this->rec;
  }
  
  public function load_wp_user($wp_user_obj)
  {
    $this->rec->ID = $wp_user_obj->ID;
    $this->rec->user_login = $wp_user_obj->user_login;
    $this->rec->user_nicename = (isset($wp_user_obj->user_nicename))?$wp_user_obj->user_nicename:'';
    $this->rec->user_email = $wp_user_obj->user_email;
    $this->rec->user_url = (isset($wp_user_obj->user_url))?$wp_user_obj->user_url:'';
    $this->rec->user_pass = $wp_user_obj->user_pass;
    $this->rec->user_message = stripslashes($wp_user_obj->user_message);
    $this->rec->user_registered = $wp_user_obj->user_registered;
    $this->rec->user_activation_key = (isset($wp_user_obj->user_activation_key))?$wp_user_obj->user_activation_key:'';
    $this->rec->user_status = (isset($wp_user_obj->user_status))?$wp_user_obj->user_status:'';
    // We don't need this, and as of WP 3.9 -- this causes wp_update_user() to wipe users role/caps!!!
    // $this->rec->role = (isset($wp_user_obj->role))?$wp_user_obj->role:'';
    $this->rec->display_name = (isset($wp_user_obj->display_name))?$wp_user_obj->display_name:'';
  }
  
  public function load_meta()
  {
    $this->rec->first_name = get_user_meta($this->ID, self::$first_name_str, true);
    $this->rec->last_name = get_user_meta($this->ID, self::$last_name_str, true);
    $this->rec->signup_notice_sent = get_user_meta($this->ID, self::$signup_notice_sent_str, true);
    $this->rec->user_pass = get_user_meta($this->ID, self::$password_str, true);
    $this->rec->user_message = get_user_meta($this->ID, self::$user_message_str, true);
    $this->rec->user_ip = get_user_meta($this->ID, self::$user_ip_str, true);
    $this->rec->uuid = $this->load_uuid();
  }
  
  /** Retrieve or generate the uuid depending on whether its in the database or not */
  public function load_uuid($force=false)
  {
    $uuid = get_user_meta($this->ID, self::$uuid_str, true);
    
    if($force or empty($uuid))
    {
      $uuid = md5(base64_encode(uniqid()));
      update_user_meta($this->ID, self::$uuid_str, $uuid);
    }
    
    return $uuid;
  }
  
  public function is_active()
  {
    $subscriptions = $this->active_product_subscriptions();
    return !empty($subscriptions);
  }
  
  // Determines if a user is already subscribed to a product
  public function is_already_subscribed_to($product_id)
  {
    return in_array($product_id, $this->active_product_subscriptions());
  }
  
  // Retrieves the current subscription within a group (with upgrade paths enabled)
  public function subscription_in_group($group_id)
  {
    $subs = MeprSubscription::get_all_active_by_user_id($this->ID);
    
    if(empty($subs)) { return false; }
    
    foreach($subs as $sub_data)
    {
      $sub = new MeprSubscription($sub_data->ID);
      $prd = $sub->product();
      if($prd->group_id==$group_id) { return $sub; }
    }
    
    return false;
  }

  public function lifetime_subscription_in_group($group_id) {
    $txns = $this->active_product_subscriptions('transactions');

    if(empty($txns)) { return false; }

    foreach( $txns as $txn ) {
      $p = $txn->product();
      if($p->is_one_time_payment() and $p->group_id==$group_id) { return $txn; }
    }

    return false;
  }
  
  public function is_logged_in_and_current_user()
  {
    return MeprUtils::is_logged_in_and_current_user($this->ID);
  }
  
  public function is_logged_in()
  {
    return MeprUtils::is_logged_in($this->ID);
  }
  
  public function active_product_subscriptions($return_type = 'ids', $force = false)
  {
    static $items; //Prevents a butt load of queries on the front end

    $user_id = $this->ID;

    // Setup caching array
    if(!isset($items) or !is_array($items)) { $items = array(); }

    // Setup caching array for this user
    if(!isset($items[$user_id]) or !is_array($items[$user_id])) { $item[$user_id] = array(); }

    //I'm assuming we may run into instances where we need to force the query to run
    //so $force should allow that
    if( $force or !isset($items[$user_id][$return_type]) or
        !is_array($items[$user_id][$return_type]) )
    {
      $txns = MeprTransaction::get_all_complete_by_user_id( $user_id, // user_id
                                                            'product_id, created_at DESC', // order_by
                                                            '', // limit
                                                            false, // count
                                                            true, // exclude_expired
                                                            true ); // include_confirmations

      $items[$user_id][$return_type] = array();

      foreach($txns as $txn)
      {
        if($return_type == 'ids') {
          $items[$user_id][$return_type][] = $txn->product_id;
        }
        else if($return_type == 'products' or $return_type === true) {
          $items[$user_id][$return_type][] = new MeprProduct($txn->product_id);
        }
        else if($return_type == 'transactions') {
          $items[$user_id][$return_type][] = new MeprTransaction($txn->id);
        }
      }
    }
    
    return $items[$user_id][$return_type];
  }
  
  public function get_active_subscription_titles( $sep = ', ' )
  {
    $formatted_titles = '';
    $res = $this->active_product_subscriptions();

    if(!empty($res)) {
      // don't list the same name multiple times
      $products = array_values(array_unique( $res ));
      $titles = array();
      for($i = 0; $i < count($products); $i++) {
        $titles[] = get_the_title($products[$i]);
      }
      sort($titles);
      $formatted_titles = implode( $sep, $titles );
    }
    
    return $formatted_titles;
  }
  
  // $who should be 1 (row) object in the $product->who_can_purchase array.
  public function can_user_purchase($who)
  {
    $subscriptions = $this->active_product_subscriptions('ids');
    
    if(empty($subscriptions) && $who->product_id != 'nothing')
      return false; //User has no active subscriptions and $who->product_id is NOT "nothing"
    elseif(empty($subscriptions) && $who->product_id == 'nothing')
      return true; //User has no active subscriptions and $who->product_id IS "nothing"
    
    if($who->product_id == 'anything') //If we've made here the user has purchased something so let's return true
      return true;
    
    return in_array($who->product_id, $subscriptions); //Now let's check if the product ID is in the user's active subscriptions or not
  }
  
  public function get_full_name() {
    return $this->full_name();
  }

  public function full_name()
  {
    return "{$this->first_name} {$this->last_name}";
  }
  
  //Should make sure user is logged in before calling this function
  public static function get_current_users_registration_date()
  {
    global $user_ID, $wpdb;
    
    $q = "SELECT `user_registered`
            FROM {$wpdb->users}
            WHERE ID = %d";
    
    $result = $wpdb->get_var($wpdb->prepare($q, $user_ID));
    
    return ($result != null)?$result:time();
  }
  
  //Should make sure user is logged in before calling this function
  public static function get_ts_of_product_signup($product_id)
  {
    global $user_ID, $wpdb;
    $mepr_db = new MeprDb();
    
    $q = "SELECT `created_at`
            FROM {$mepr_db->transactions}
            WHERE `product_id` = %d
              AND `user_id` = %d
              AND (`txn_type` = %s OR `txn_type` = %s)
              AND (`status` = %s OR `status` = %s)
          ORDER BY `created_at` ASC
          LIMIT 1";
    
    $result = $wpdb->get_var($wpdb->prepare($q, $product_id, $user_ID, MeprTransaction::$payment_str, MeprTransaction::$subscription_confirmation_str, MeprTransaction::$complete_str, MeprTransaction::$confirmed_str));
    
    return ($result != null)?$result:false;
  }
  
  public function store()
  {
    if(isset($this->ID) and !is_null($this->ID))
      $id = wp_update_user((array)$this->rec);
    else {
      $id = wp_insert_user((array)$this->rec);
      $this->user_ip = $_SERVER['REMOTE_ADDR'];
    }
    
    if(empty($id) or is_wp_error($id))
      throw new MeprCreateException(sprintf(__( 'The user was unable to be saved.', 'memberpress')));
    else
      $this->rec->ID = $id;
    
    $this->store_meta();
    
    return $id;
  }
  
  // alias of store
  public function save()
  {
    return $this->store();
  }
  
  public function store_meta()
  {
    update_user_meta($this->ID, self::$first_name_str, $this->first_name);
    update_user_meta($this->ID, self::$last_name_str,  $this->last_name);
    update_user_meta($this->ID, self::$signup_notice_sent_str, $this->signup_notice_sent);
    update_user_meta($this->ID, self::$user_ip_str, $this->user_ip);
  }
  
  // alias of store_meta
  public function save_meta()
  {
    return $this->store_meta();
  }
  
  public function destroy()
  {
    wp_delete_user($this->ID);
  }
  
  public function reset_form_key_is_valid($key)
  {
    $stored_key = get_user_meta( $this->ID, 'mepr_reset_password_key', true);
    return (!empty($stored_key) and ($key == $stored_key));
  }
  
  public function send_reset_password_requested_notification()
  {
    $mepr_options = MeprOptions::fetch();
    $mepr_blogname = get_option('blogname');
    $mepr_blogurl = home_url();
    
    $key = md5(time().$this->ID);
    update_user_meta($this->ID, 'mepr_reset_password_key', $key);
    
    $reset_password_link = $this->reset_password_link($key);
    $recipient = $this->formatted_email();
    
    /* translators: In this string, %s is the Blog Name/Title */
    $subject = sprintf( __("[%s] Password Reset", 'memberpress'), $mepr_blogname);

    /* translators: In this string, %1$s is the user's username, %2$s is the blog's name/title, %3$s is the blog's url, %4$s the reset password link */
    $message = sprintf(__("Someone requested to reset your password for %1\$s on %2\$s at %3\$s\n\nTo reset your password visit the following address, otherwise just ignore this email and nothing will happen.\n\n%4\$s", 'memberpress'), $this->user_login, $mepr_blogname, $mepr_blogurl, $reset_password_link);

    MeprUtils::wp_mail($recipient, $subject, $message);
  }
  
  public function set_password_and_send_notifications($key, $password)
  {
    $mepr_options = MeprOptions::fetch();
    $mepr_blogname = get_option('blogname');
    $mepr_blogurl = home_url();
    
    if($this->reset_form_key_is_valid($key))
    {
      delete_user_meta($this->ID, 'mepr_reset_password_key');
      
      $this->rec->user_pass = $password;
      $this->store();
      
      /* translators: In this string, %s is the Blog Name/Title */
      $subject = sprintf(__("[%s] Password Lost/Changed", 'memberpress'), $mepr_blogname);
      
      /* translators: In this string, %1$s is the user's username */
      $message = sprintf(__("Password Lost and Changed for user: %1\$s", 'memberpress'), $this->user_login);
      
      MeprUtils::wp_mail_to_admin($subject, $message);
      
      $login_link = $mepr_options->login_page_url();
      
      // Send password email to new user
      $recipient = $this->formatted_email();
      
      /* translators: In this string, %s is the Blog Name/Title */
      $subject = sprintf(__("[%s] Your new Password",'memberpress'), $mepr_blogname);
      
      /* translators: In this string, %1$s is the user's first name, %2$s is the blog's name/title, %3$s is the user's username, %4$s is the user's password, and %5$s is the blog's URL... */
      $message = sprintf(__("%1\$s,\n\nYour password was successfully reset on %2\$s!\n\nUsername: %3\$s\nPassword: %4\$s\n\nYou can login here: %5\$s", 'memberpress'), (empty($this->first_name)?$this->user_login:$this->first_name), $mepr_blogname, $this->user_login, $password, $login_link);
      
      MeprUtils::wp_mail($recipient, $subject, $message);
      
      return true;
    }
    
    return false;
  }
  
  public static function validate_account($params, $errors = array())
  {
    $mepr_options = MeprOptions::fetch();
    
    extract($params);
    
    if($mepr_options->require_fname_lname && (empty($user_first_name) || empty($user_last_name)))
      $errors[] = __('You must enter both your First and Last name', 'memberpress');
    
    if(empty($user_email) || !is_email($user_email))
      $errors[] = __('You must enter a valid email address', 'memberpress');
    
    return $errors;
  }

  public static function validate_signup($params, $errors)
  {
    $mepr_options = MeprOptions::fetch();
    $custom_fields_errors = array();

    extract($params);

    if(!MeprUtils::is_user_logged_in())
    {
      //Set user_login to user_email if that option is enabled.
      if($mepr_options->username_is_email)
        $user_login = (isset($user_email) && is_email($user_email))?$user_email:'placeholderToPreventEmptyUsernameErrors';
      
      if(empty($user_login))
        $errors[] = __('Username must not be blank','memberpress');
      
      if(!preg_match('#^[a-zA-Z0-9_@\.\-]+$#', $user_login))
        $errors[] = __('Username must only contain letters, numbers and/or underscores', 'memberpress');
      
      if(username_exists($user_login))
      	$errors[] = __('Username is Already Taken.', 'memberpress');
      
      if(!is_email($user_email))
        $errors[] = __('Email must be a real and properly formatted email address', 'memberpress');
      
      if(email_exists($user_email))
      {
        $current_url = urlencode($_SERVER['REQUEST_URI']);
        $login_url = $mepr_options->login_page_url("redirect_to={$current_url}");
        
        $errors[] = sprintf(__('This email address has already been used. Please %sLogin%s to complete your purchase.', 'memberpress'), "<a href=\"{$login_url}\"><strong>", "</strong></a>");
      }
      
      if(empty($mepr_user_password))
        $errors[] = __('You must enter a Password.','memberpress');
      
      if(empty($mepr_user_password_confirm))
        $errors[] = __('You must enter a Password Confirmation.', 'memberpress');
      
      if($mepr_user_password != $mepr_user_password_confirm)
        $errors[] = __('Your Password and Password Confirmation don\'t match.', 'memberpress');
      
      //Honeypot
      if(!isset($mepr_no_val) || (isset($mepr_no_val) && !empty($mepr_no_val)))
        $errors[] = __('Only humans are allowed to register.', 'memberpress');
    }
    
    if(($mepr_options->show_fname_lname and $mepr_options->require_fname_lname) &&
       (empty($user_first_name) || empty($user_last_name)))
      $errors[] = __('You must enter both your First and Last name', 'memberpress');
    
    if(isset($mepr_coupon_code) && !empty($mepr_coupon_code) && !MeprCoupon::is_valid_coupon_code($mepr_coupon_code, $mepr_product_id))
      $errors[] = __('Your coupon code is invalid.', 'memberpress');
    
    if($mepr_options->require_tos && !isset($mepr_agree_to_tos) && !isset($logged_in_purchase)) //Make sure not logged in purchase
      $errors[] = __('You must agree to the Terms Of Service', 'memberpress');
    
    $product = new MeprProduct($mepr_product_id);
    $product_coupon_code = isset($mepr_coupon_code) ? $mepr_coupon_code : null;
    $product_price = $product->adjusted_price($product_coupon_code);
    $pms = $mepr_options->payment_methods();

    // Don't allow free payment method on non-free transactions
    // Don't allow manual payment method on the signup form
    unset($pms['free']); unset($pms['manual']);

    $pms = array_keys($pms);

    if((!isset($mepr_payment_method) or empty($mepr_payment_method)) and $product_price > 0.00) {
      $errors[] = __('There are no active Payment Methods right now ... please contact the system administrator for help.', 'memberpress');
    }

    // We only care what the payment_method is if the product isn't free
    // Don't allow payment methods not included in mepr option's pm's
    // Don't allow payment methods not included in custom pm's if we're customizing pm's
    if( isset($mepr_payment_method) and
        !empty($mepr_payment_method) and
        $product_price > 0.00 and
        ( !in_array( strtolower($mepr_payment_method), $pms ) or
          ( $product->customize_payment_methods and
            !in_array( strtolower($mepr_payment_method),
                       $product->custom_payment_methods ) ) ) )
    {
      $errors[] = __('Invalid Payment Method', 'memberpress');
    }

    //Make sure this isn't the logged in purchases form
    if(!isset($logged_in_purchase))
      $custom_fields_errors = MeprUsersController::validate_extra_profile_fields(null, null, null, true);

    //Maybe validate addresses on the Logged in signup form
    if( isset($logged_in_purchase) &&
        $mepr_options->show_address_fields &&
        $mepr_options->show_address_fields_logged_in )
      $custom_fields_errors = MeprUsersController::validate_address_fields();
    
    return array_merge($errors, $custom_fields_errors);
  }
  
  public static function validate_login($params, $errors)
  {
    extract($params);
    
    if(is_email($log)) {
      $user = get_user_by('email', $log);
      
      if($user !== false)
        $log = $user->user_login;
    }
    
    if(empty($log))
      $errors[] = __('Username must not be blank', 'memberpress');
    
    $logged_in_user = MeprUtils::wp_authenticate($log, $pwd);
    if(is_wp_error($logged_in_user))
      $errors[] = __('Your username or password was incorrect', 'memberpress');
    
    return $errors;
  }
  
  public static function validate_forgot_password($params, $errors)
  {
    extract($params);
    
    if(empty($mepr_user_or_email))
      $errors[] = __('You must enter a Username or Email', 'memberpress');
    else
    {
      $is_email = (is_email($mepr_user_or_email) and email_exists($mepr_user_or_email));
      $is_username = username_exists($mepr_user_or_email);
      
      if(!$is_email and !$is_username)
        $errors[] = __("That Username or Email wasn't found.", 'memberpress');
    }
    
    return $errors;    
  }
  
  public static function validate_reset_password($params, $errors)
  {
    extract($params);
    
    if(empty($mepr_user_password))
      $errors[] = __('You must enter a Password.', 'memberpress');
    
    if(empty($mepr_user_password_confirm))
      $errors[] = __('You must enter a Password Confirmation.', 'memberpress');
    
    if($mepr_user_password != $mepr_user_password_confirm)
      $errors[] = __("Your Password and Password Confirmation don't match.", 'memberpress');
    
    return $errors;
  }
  
  public function sent_renewal($txn_id)
  {
    return add_user_meta($this->ID, 'mepr_renewal', $txn_id);
  }
  
  public function get_renewals()
  {
    return get_user_meta($this->ID, 'mepr_renewal', false);
  }
  
  public function renewal_already_sent($txn_id)
  {
    $renewals = $this->get_renewals();
    return (!empty($renewals) and in_array($txn_id, $renewals));
  }
  
  public function transactions($where = null, $order = "created_at", $sort = "DESC")
  {
    global $wpdb;
    $conditions = $wpdb->prepare("WHERE user_id=%d", $this->ID);
    
    if(!is_null($where))
      $conditions = "{$conditions} AND {$where}";
      
    $query = "SELECT * FROM transactions {$conditions} {$order} {$sort}";
    
    return $wpdb->get_results($query);
  }
  
  public function transactions_for_product($product_id, $expired = false)
  {
    global $wpdb;
    $where = $wpdb->prepare("product_id=%d", $product_id);
    
    if($expired)
      $where .= " AND expired_at <= '".date('c')."'";
    
    return $this->transactions($where, "created_at", "DESC");               
  }
  
  public static function email_users_with_expiring_transactions()
  {
    $mepr_options = MeprOptions::fetch();
    
    if($mepr_options->user_renew_email == true)
    {
      $transactions = MeprTransaction::get_expiring_transactions();
      if(!empty($transactions) and is_array($transactions))
      {
        foreach($transactions as $transaction)
        {
          $user = new MeprUser($transaction->user_id);
          $product = new MeprProduct($transaction->product_id);
          
          $params = new stdClass();
          $params->user_first_name = $user->first_name;
          $params->user_last_name  = $user->last_name;
          $params->user_email      = $user->user_email;
          $params->to_email        = $user->user_email;
          $params->to_name         = "{$user->first_name} {$user->last_name}";
          $params->membership_type = $product->post_title;
          $params->business_name   = get_option('blogname');
          $params->blog_name       = get_option('blogname');
          $params->renewal_link    = $user->renewal_link($transaction->id);
          
          if(MeprUtils::send_user_renew_notification((array)$params))
            $user->sent_renewal($transaction->id);
        }
      }
    }
  }
  
  public function renewal_link($txn_id)
  {
    $txn = new MeprTransaction($txn_id);
    $product = new MeprProduct($txn->product_id); 

    return $product->url("?renew=true&uid={$this->uuid}&tid={$txn_id}");
  }

  public function reset_password_link($key)
  {
    $mepr_options = MeprOptions::fetch();
    $permalink = $mepr_options->login_page_url();
    $delim = MeprAppController::get_param_delimiter_char($permalink);
    return "{$permalink}{$delim}action=reset_password&mkey={$key}&u=".urlencode($this->user_login);
  }

  public function subscription_expirations($type='all', $exclude_stopped=false) {
    global $wpdb;
    $mepr_db = new MeprDb();

    $exp_op = (($type=='expired')?'<=':'>');

    // Get all recurring subscriptions that
    // are expired but still have an active status
    $query = "SELECT p.ID as ID, tr.expires_at AS expires_at " .
               "FROM {$wpdb->posts} AS p " .
               "JOIN {$wpdb->postmeta} AS pm_user_id " .
                 "ON pm_user_id.post_id = p.ID " .
                "AND pm_user_id.meta_key = %s " .
                "AND pm_user_id.meta_value = %s " .
               "JOIN {$wpdb->postmeta} AS pm_status " .
                 "ON pm_status.post_id = p.ID " .
                "AND pm_status.meta_key = %s " .
               "JOIN {$mepr_db->transactions} AS tr " .
                 "ON tr.id = ( CASE " .
                              // When 1 or more lifetime txns exist for this sub
                              "WHEN ( SELECT COUNT(*) " .
                                       "FROM {$mepr_db->transactions} AS etc " .
                                      "WHERE etc.subscription_id=p.ID " .
                                        "AND etc.status IN (%s,%s) " .
                                        "AND etc.expires_at='0000-00-00 00:00:00' ) > 0 " .
                              // Use the latest lifetime txn for expiring_txn
                              "THEN ( SELECT max(etl.id) " .
                                       "FROM {$mepr_db->transactions} AS etl " .
                                      "WHERE etl.subscription_id=p.ID " .
                                        "AND etl.status IN (%s,%s) " .
                                        "AND etl.expires_at='0000-00-00 00:00:00' ) " .
                              // Otherwise use the latest complete txn for expiring_txn
                              "ELSE ( SELECT etr.id " .
                                       "FROM {$mepr_db->transactions} AS etr " .
                                      "WHERE etr.subscription_id=p.ID " .
                                        "AND etr.status IN (%s,%s) " .
                                      "ORDER BY etr.expires_at DESC " .
                                      "LIMIT 1 ) " .
                              "END ) " .
              "WHERE p.post_type = %s " .
                "AND tr.expires_at IS NOT NULL " .
                "AND tr.expires_at > '0000-00-00 00:00:00' " .
                "AND tr.expires_at {$exp_op} NOW()";

    $query = $wpdb->prepare( $query,
                             MeprSubscription::$user_id_str,
                             $this->ID,
                             MeprSubscription::$status_str,
                             MeprTransaction::$complete_str,
                             MeprTransaction::$confirmed_str,
                             MeprTransaction::$complete_str,
                             MeprTransaction::$confirmed_str,
                             MeprTransaction::$complete_str,
                             MeprTransaction::$confirmed_str,
                             MeprSubscription::$cpt );

    if( $exclude_stopped )
      $query .= $wpdb->prepare( " AND pm_status.meta_value = %s", MeprSubscription::$active_str );

    $res = $wpdb->get_results( $query );

    return $res;
  }

  public function track_login_event() {
    $evt = new MeprEvent();
    $evt->event = MeprEvent::$login_event_str;
    $evt->evt_id = $this->ID;
    $evt->evt_id_type = MeprEvent::$users_str;
    $evt->store();
  }

  public function get_num_logins()
  {
    $mepr_db = new MeprDb();
    $args = array( 'evt_id_type' => MeprEvent::$users_str,
                   'evt_id' => $this->ID,
                   'event' => MeprEvent::$login_event_str );
    return $mepr_db->get_count( $mepr_db->events, $args );
  }

  public function get_last_login_data() {
    $mepr_db = new MeprDb();
    $args = array( 'evt_id_type' => MeprEvent::$users_str,
                   'evt_id' => $this->ID,
                   'event' => MeprEvent::$login_event_str );
    $rec = $mepr_db->get_records( $mepr_db->events, $args, '`created_at` DESC', 1 );
    return ( empty($rec) ? false : $rec[0] );
  }

  public function formatted_address() {
    $addr1   = get_user_meta( $this->ID, 'mepr-address-one',     true );
    $addr2   = get_user_meta( $this->ID, 'mepr-address-two',     true );
    $city    = get_user_meta( $this->ID, 'mepr-address-city',    true );
    $state   = get_user_meta( $this->ID, 'mepr-address-state',   true );
    $zip     = get_user_meta( $this->ID, 'mepr-address-zip',     true );
    $country = get_user_meta( $this->ID, 'mepr-address-country', true );

    if( empty($addr1) or empty($city) or
        empty($state) or empty($zip) ) {
      return '';
    }

    $addr = $addr1;

    if($addr2 and !empty($addr2)) { $addr .= "<br/>{$addr2}"; }
    if($country and !empty($country)) { $country = "<br/>{$country}"; } else { $country = ''; }

    $addr = sprintf( __('<br/>%1$s<br/>%2$s, %3$s %4$s%5$s<br/>', 'memberpress'), $addr, $city, $state, $zip, $country );

    return apply_filters( 'mepr-user-formatted-address', $addr, $this );
  }

  public function formatted_email() {
    return $this->full_name() . " <{$this->user_email}>";
  }

  public static function manually_place_account_form($post) {
    return ( $post instanceof WP_Post and preg_match( '~\[mepr-account-form~', $post->post_content ) );
  }

  public static function is_account_page($post) {
    $mepr_options = MeprOptions::fetch();
    return ( ( $post instanceof WP_Post and $post->ID == $mepr_options->account_page_id ) or
             self::manually_place_account_form($post) );
  }
} //End class

