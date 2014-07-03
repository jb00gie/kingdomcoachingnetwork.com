<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprProduct extends MeprCptModel
{
  public static $price_str                      = '_mepr_product_price';
  public static $period_str                     = '_mepr_product_period';
  public static $period_type_str                = '_mepr_product_period_type';
  public static $signup_button_text_str         = '_mepr_product_signup_button_text';
  public static $limit_cycles_str               = '_mepr_product_limit_cycles';
  public static $limit_cycles_num_str           = '_mepr_product_limit_cycles_num';
  public static $limit_cycles_action_str        = '_mepr_product_limit_cycles_action';
  public static $trial_str                      = '_mepr_product_trial';
  public static $trial_days_str                 = '_mepr_product_trial_days';
  public static $trial_amount_str               = '_mepr_product_trial_amount';
  public static $group_id_str                   = '_mepr_group_id'; // Only one group at a time dude
  public static $group_order_str                = '_mepr_group_order'; // Position in group
  public static $is_highlighted_str             = '_mepr_product_is_highlighted';
  public static $who_can_purchase_str           = '_mepr_product_who_can_purchase';
  public static $pricing_title_str              = '_mepr_product_pricing_title';
  public static $pricing_show_price_str         = '_mepr_product_pricing_show_price';
  public static $pricing_heading_txt_str        = '_mepr_product_pricing_heading_text';
  public static $pricing_footer_txt_str         = '_mepr_product_pricing_footer_text';
  public static $pricing_button_txt_str         = '_mepr_product_pricing_button_text';
  public static $pricing_benefits_str           = '_mepr_product_pricing_benefits';
  public static $register_price_action_str      = '_mepr_register_price_action';
  public static $register_price_str             = '_mepr_register_price';
  public static $thank_you_page_enabled_str     = '_mepr_thank_you_page_enabled';
  public static $thank_you_message_str          = '_mepr_product_thank_you_message';
  public static $simultaneous_subscriptions_str = '_mepr_allow_simultaneous_subscriptions';
  public static $use_custom_template_str        = '_mepr_use_custom_template';
  public static $custom_template_str            = '_mepr_custom_template';
  public static $customize_payment_methods_str  = '_mepr_customize_payment_methods';
  public static $custom_payment_methods_str     = '_mepr_custom_payment_methods';
  public static $custom_login_urls_enabled_str  = '_mepr_custom_login_urls_enabled';
  public static $custom_login_urls_default_str  = '_mepr_custom_login_urls_default';
  public static $custom_login_urls_str          = '_mepr_custom_login_urls';
  public static $expire_type_str                = '_mepr_expire_type';
  public static $expire_after_str               = '_mepr_expire_after';
  public static $expire_unit_str                = '_mepr_expire_unit';
  public static $expire_fixed_str               = '_mepr_expire_fixed';
  public static $access_url_str                 = '_mepr_access_url';
  public static $emails_str                     = '_mepr_emails';
  
  public static $nonce_str                      = 'mepr_products_nonce';
  public static $last_run_str                   = 'mepr_products_db_cleanup_last_run';
  
  public static $cpt                            = 'memberpressproduct';
  
  public function __construct($id = null)
  {
    $this->load_cpt( $id, self::$cpt,
                     array( "price" => 0.00,
                            "period" => 1,
                            "period_type" => 'lifetime', //Default to lifetime to simplify new Product form
                            "signup_button_text" => __('Sign Up','memberpress'),
                            "limit_cycles" => false,
                            "limit_cycles_num" => 2,
                            "limit_cycles_action" => 'expire',
                            "trial" => false,
                            "trial_days" => 0,
                            "trial_amount" => 0.00,
                            "group_id" => 0,
                            "group_order" => 0,
                            "is_highlighted" => false,
                            //who_can_purchase should be an array of OBJECTS
                            "who_can_purchase" => null,
                            "pricing_title" => '',
                            "pricing_show_price" => true,
                            "pricing_heading_txt" => '',
                            "pricing_footer_txt" => '',
                            "pricing_button_txt" => '',
                            //Pricing benefits should be an array of strings
                            "pricing_benefits" => array(),
                            "register_price_action" => 'default',
                            "register_price" => '',
                            "thank_you_page_enabled" => null,
                            "thank_you_message" => '',
                            "custom_login_urls_enabled" => null,
                            "custom_login_urls_default" => null,
                            //An array of objects ->url and ->count
                            "custom_login_urls" => null,
                            "expire_type" => 'none',
                            "expire_after" => 1,
                            "expire_unit" => 'days',
                            "expire_fixed" => '',
                            "access_url" => '',
                            "emails" => array(),
                            "simultaneous_subscriptions" => false,
                            "use_custom_template" => false,
                            "custom_template" => '',
                            "customize_payment_methods" => false,
                            "custom_payment_methods" => null ) );
  }
  
  public function store_meta()
  {
    $id = $this->ID;

    update_post_meta($id, self::$price_str, MeprUtils::format_float($this->price));
    update_post_meta($id, self::$period_str, $this->period);
    update_post_meta($id, self::$period_type_str, $this->period_type);
    update_post_meta($id, self::$signup_button_text_str, $this->signup_button_text);
    update_post_meta($id, self::$limit_cycles_str, $this->limit_cycles);
    update_post_meta($id, self::$limit_cycles_num_str, $this->limit_cycles_num);
    update_post_meta($id, self::$limit_cycles_action_str, $this->limit_cycles_action);
    update_post_meta($id, self::$trial_str, $this->trial);
    update_post_meta($id, self::$trial_days_str, $this->trial_days);
    update_post_meta($id, self::$trial_amount_str, $this->trial_amount);
    update_post_meta($id, self::$group_id_str, $this->group_id);
    update_post_meta($id, self::$group_order_str, $this->group_order);
    update_post_meta($id, self::$who_can_purchase_str, $this->who_can_purchase);
    update_post_meta($id, self::$is_highlighted_str, $this->is_highlighted);
    update_post_meta($id, self::$pricing_title_str, $this->pricing_title);
    update_post_meta($id, self::$pricing_show_price_str, $this->pricing_show_price);
    update_post_meta($id, self::$pricing_heading_txt_str, $this->pricing_heading_txt);
    update_post_meta($id, self::$pricing_footer_txt_str, $this->pricing_footer_txt);
    update_post_meta($id, self::$pricing_button_txt_str, $this->pricing_button_txt);
    update_post_meta($id, self::$pricing_benefits_str, $this->pricing_benefits);
    update_post_meta($id, self::$register_price_action_str, $this->register_price_action);
    update_post_meta($id, self::$register_price_str, $this->register_price);
    update_post_meta($id, self::$thank_you_page_enabled_str, $this->thank_you_page_enabled);
    update_post_meta($id, self::$thank_you_message_str, $this->thank_you_message);
    update_post_meta($id, self::$custom_login_urls_enabled_str, $this->custom_login_urls_enabled);
    update_post_meta($id, self::$custom_login_urls_default_str, $this->custom_login_urls_default);
    update_post_meta($id, self::$custom_login_urls_str, $this->custom_login_urls);
    update_post_meta($id, self::$expire_type_str, $this->expire_type);
    update_post_meta($id, self::$expire_after_str, $this->expire_after);
    update_post_meta($id, self::$expire_unit_str, $this->expire_unit);
    update_post_meta($id, self::$expire_fixed_str, $this->expire_fixed);
    update_post_meta($id, self::$access_url_str, $this->access_url);
    update_post_meta($id, self::$emails_str, $this->emails);
    update_post_meta($id, self::$simultaneous_subscriptions_str, $this->simultaneous_subscriptions);
    update_post_meta($id, self::$use_custom_template_str, $this->use_custom_template);
    update_post_meta($id, self::$custom_template_str, $this->custom_template);
    update_post_meta($id, self::$customize_payment_methods_str, $this->customize_payment_methods);

    if($this->customize_payment_methods)
      update_post_meta($id, self::$custom_payment_methods_str, $this->custom_payment_methods);
    else
      delete_post_meta($id, self::$custom_payment_methods_str);
  }

  public function is_prorated() {
    $mepr_options = MeprOptions::fetch();
    return( $mepr_options->pro_rated_upgrades and $this->is_upgrade_or_downgrade() );
  }

  public static function get_one($id) {
    $post = get_post($id);

    if(is_null($post))
      return false;
    else
      return new MeprProduct($post->ID);
  }

  /** This presents the price as a float, based on the information contained in 
    * $this, the user_id and $coupon_code passed to it.
    *
    * If a user_id and a coupon code is present just adjust the price based on
    * the user first (if any) and then apply the coupon to the remaining price.
    *
    * Coupon code needs to be validated using MeprCoupon::is_valid_coupon_code()
    * before passing a code to this method
    */
  public function adjusted_price($coupon_code = null)
  {
    global $current_user;
    MeprUtils::get_currentuserinfo();

    $product_price = $this->price;
    $mepr_options = MeprOptions::fetch();

    if( $this->is_one_time_payment() and $this->is_prorated() ) {
      $grp = $this->group();
      $usr = new MeprUser($current_user->ID);

      if($old_sub = $usr->subscription_in_group($grp->ID))
      {
        $lt = $old_sub->latest_txn();
        $r = MeprUtils::calculate_proration( $lt->amount, $product_price, 
                                             $old_sub->days_in_this_period(),
                                             'lifetime',
                                             $old_sub->days_till_expiration() );
        $product_price = $r->proration;
      }
      else if($txn = $usr->lifetime_subscription_in_group($grp->ID))
      {
        $r = MeprUtils::calculate_proration( $txn->amount, $product_price );
        $product_price = $r->proration;
      }
    }

    //Note to future self, we do not want to validate the coupon
    //here as it causes major issues if the coupon has expired
    //or has reached its usage count max. See notes above this method.
    if(!empty($coupon_code))
    {
      $coupon = MeprCoupon::get_one_from_code($coupon_code);
      
      if($coupon !== false)
        $product_price = $coupon->apply_discount($product_price);
    }      

    return MeprUtils::format_float($product_price);
  }
  
  /** Gets the value for 'expires_at' for the given created_at time for this product. */
  public function get_expires_at($created_at = null)
  {
    $mepr_options = MeprOptions::fetch();
    
    if(is_null($created_at)) { $created_at = time(); }
    
    $expires_at = $created_at;
    $period = $this->period;
    
    switch($this->period_type)
    {
      case 'days':
          $expires_at += MeprUtils::days($period) + MeprUtils::days($mepr_options->grace_expire_days);
          break;
      case 'weeks':
          $expires_at += MeprUtils::weeks($period) + MeprUtils::days($mepr_options->grace_expire_days);
          break;
      case 'months':
          $expires_at += MeprUtils::months($period, $created_at) + MeprUtils::days($mepr_options->grace_expire_days);
          break;
      case 'years':
          $expires_at += MeprUtils::years($period, $created_at) + MeprUtils::days($mepr_options->grace_expire_days);
          break;
      default: // one-time payment
          if($this->expire_type=='delay') {
            switch($this->expire_unit)
            {
              case 'days':
                $expires_at += MeprUtils::days($this->expire_after);
                break;
              case 'weeks':
                $expires_at += MeprUtils::weeks($this->expire_after);
                break;
              case 'months':
                $expires_at += MeprUtils::months($this->expire_after, $created_at);
                break;
              case 'years':
                $expires_at += MeprUtils::years($this->expire_after, $created_at);
            }
          }
          else if($this->expire_type=='fixed') {
            $expires_at = strtotime( $this->expire_fixed );
          }
          else { // lifetime
            $expires_at = null;
          }
    }
    
    return $expires_at;
  }
  
  public static function get_pricing_page_product_ids()
  {
    global $wpdb;
    
    $q = "SELECT p.ID, p.menu_order
            FROM {$wpdb->postmeta} AS m INNER JOIN {$wpdb->posts} AS p
              ON p.ID = m.post_id
            WHERE m.meta_key = %s
              AND m.meta_value = 1
          ORDER BY p.menu_order, p.ID";
    
    return $wpdb->get_col($wpdb->prepare($q, self::$show_on_pricing_str));
  }
  
  public function is_one_time_payment()
  {
    return ($this->period_type == 'lifetime' || $this->price == 0.00);
  }
  
  public function can_you_buy_me()
  {
    global $user_ID;
    
    if(MeprUtils::is_user_logged_in())
      $user = new MeprUser($user_ID);
    
    //Make sure user hasn't already subscribed to this product first
    if(MeprUtils::is_user_logged_in() && $user->is_already_subscribed_to($this->ID) && !$this->simultaneous_subscriptions)
      return false;
    
    if(empty($this->who_can_purchase))
      return true; //No rules exist so everyone can purchase
    
    foreach($this->who_can_purchase as $who)
    {
      if($who->user_type == 'everyone')
        return true;
      
      if($who->user_type == 'guests' && !MeprUtils::is_user_logged_in())
        return true; //If not a logged in member they can purchase
      
      if($who->user_type == 'members' && MeprUtils::is_user_logged_in())
        if($user->can_user_purchase($who))
          return true;
    }
    
    return false; //If we make it here, nothing applied so let's return false
  }
  
  public function group()
  {
    if(!isset($this->group_id) or empty($this->group_id))
      return false;
    
    static $grp;
    
    if(!isset($grp) or !($grp instanceof MeprGroup) or $grp->ID != $this->group_id)
      $grp = new MeprGroup($this->group_id);
    
    return $grp;
  }
  
  // Determines if this is a product upgrade
  public function is_upgrade()
  {
    return $this->is_upgrade_or_downgrade('upgrade');
  }
  
  // Determines if this is a product downgrade
  public function is_downgrade()
  {
    return $this->is_upgrade_or_downgrade('downgrade');
  }
  
  // Determines if this is a product upgrade for a certain user
  public function is_upgrade_for($user_id)
  {
    return $this->is_upgrade_or_downgrade_for($user_id,'upgrade');
  }
  
  // Determines if this is a product downgrade for a certain user
  public function is_downgrade_for($user_id)
  {
    return $this->is_upgrade_or_downgrade_for($user_id,'downgrade');
  }
  
  public function is_upgrade_or_downgrade($type=false)
  {
    global $current_user;
    MeprUtils::get_currentuserinfo();

    return ( $usr = new MeprUser($current_user->ID) and
             $usr->is_logged_in_and_current_user() and // Can only upgrade if logged in
             $this->is_upgrade_or_downgrade_for($usr->ID, $type) ); // Must be an upgrade/downgrade for the user
  }

  // Determines if this is a product upgrade
  public function is_upgrade_or_downgrade_for($user_id, $type=false)
  {
    $usr = new MeprUser($user_id);
    $grp = $this->group();

    // not part of a group ... not an upgrade
    if(!$grp) { return false; }

    // no upgrade path here ... not an upgrade
    if(!$grp->is_upgrade_path) { return false; }

    $prds = $usr->active_product_subscriptions('products', true);

    if(!empty($prds)) {
      foreach($prds as $p) {
        if( $g = $p->group() and $g instanceof MeprGroup and
            $g->ID == $grp->ID and $this->ID != $p->ID ) {
          if( $type===false )
            return true;
          else if( $type == 'upgrade' )
            return $this->group_order > $p->group_order;
          else if( $type == 'downgrade' )
            return $this->group_order < $p->group_order;
        }
      }
    }

    return false;
  }

  public static function cleanup_db()
  {
    global $wpdb;
    $date = time();
    $last_run = get_option(self::$last_run_str, 0); //Prevents all this code from executing on every page load
    
    if(($date - $last_run) > 86400) //Runs once at most once a day
    {
      $sq1 = "SELECT ID
                FROM {$wpdb->posts}
                WHERE post_type = '".self::$cpt."' AND
                      post_status = 'auto-draft'";
      $q1 = "DELETE
                FROM {$wpdb->postmeta}
                WHERE post_id IN ({$sq1})";
      $q2 = "DELETE
                FROM {$wpdb->posts}
                WHERE post_type = '".self::$cpt."' AND
                      post_status = 'auto-draft'";
      
      $wpdb->query($q1);
      $wpdb->query($q2);
      update_option(self::$last_run_str, $date);
    }
  }
  
  public function get_page_template()
  {
    if($this->use_custom_template)
      return locate_template($this->custom_template);
    else
      return locate_template(self::template_search_path());
  }
  
  public static function template_search_path()
  {
    return array( 'page_memberpressproduct.php',
                  'single-memberpressproduct.php',
                  'page.php',
                  'custom_template.php',
                  'single.php',
                  'index.php' );
  }
  
  public function payment_methods()
  {
    $mepr_options = MeprOptions::fetch();
    
    $pms = $mepr_options->payment_methods();
    
    unset($pms['free']);
    unset($pms['manual']);
    
    $pmkeys = array_keys($pms);
    
    if( $this->customize_payment_methods and
        isset($this->custom_payment_methods) and
        !is_null($this->custom_payment_methods) and
        is_array($this->custom_payment_methods) )
      return array_intersect($this->custom_payment_methods, $pmkeys);
    
    return $pmkeys;
  }
  
  public function url($args = '')
  {
    if(isset($this->ID))
      return get_permalink($this->ID).$args; 
    else
      return '';
  }

  public function manual_append_signup() {
    return preg_match('~\[\s*mepr-product-registration-form\s*\]~',$this->post_content);
  }

  public static function is_product_page($post) {
    if( is_object($post) &&
        ( ( $post->post_type == MeprProduct::$cpt &&
            $prd = new MeprProduct($post->ID) ) ||
          ( preg_match( '~\[mepr-product-registration-form\s+product_id=[\"\\\'](\d+)[\"\\\']~',
                        $post->post_content, $m ) &&
            isset($m[1]) &&
            $prd = new MeprProduct( $m[1] ) ) ) )
    {
      return $prd;
    }

    return false;
  }
} //End class
