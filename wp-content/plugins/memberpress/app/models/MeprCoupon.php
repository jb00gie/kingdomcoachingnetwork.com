<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprCoupon extends MeprCptModel
{
  public static $should_expire_str    = '_mepr_coupons_should_expire';
  public static $expires_on_str       = '_mepr_coupons_expires_on';
  public static $usage_count_str      = '_mepr_coupons_usage_count';
  public static $usage_amount_str     = '_mepr_coupons_usage_amount';
  public static $discount_type_str    = '_mepr_coupons_discount_type';
  public static $discount_amount_str  = '_mepr_coupons_discount_amount';
  public static $valid_products_str   = '_mepr_coupons_valid_products';
  public static $no_recurring_str     = '_mepr_coupons_no_recurring';
  public static $trial_str            = '_mepr_coupons_trial';
  public static $trial_days_str       = '_mepr_coupons_trial_days';
  public static $trial_amount_str     = '_mepr_coupons_trial_amount';
  
  public static $last_run_str         = 'mepr_coupons_expire_last_run';
  public static $nonce_str            = 'mepr_coupons_nonce';
  public static $expires_on_month_str = 'mepr_coupons_ex_month';
  public static $expires_on_day_str   = 'mepr_coupons_ex_day';
  public static $expires_on_year_str  = 'mepr_coupons_ex_year';
  
  public static $cpt = 'memberpresscoupon';
  
  /*** Instance Methods ***/
  public function __construct($id = null)
  {
    $this->attrs = array( "should_expire",
                          "expires_on",
                          "usage_count",
                          "usage_amount",
                          "discount_type",
                          "discount_amount",
                          "valid_products",
                          "no_recurring",
                          "trial",
                          "trial_days",
                          "trial_amount" );
    
    if(null === ($this->rec = get_post($id)))
      $this->initialize_new_cpt();
    elseif($this->post_type != self::$cpt)
      $this->initialize_new_cpt();
    else
    {
      $this->rec->should_expire   = (bool)get_post_meta($id, self::$should_expire_str, true);
      $this->rec->expires_on      = (int)get_post_meta($id, self::$expires_on_str, true);
      $this->rec->usage_count     = (int)get_post_meta($id, self::$usage_count_str, true);
      $this->rec->usage_amount    = (int)get_post_meta($id, self::$usage_amount_str, true);
      $this->rec->discount_type   = (string)get_post_meta($id, self::$discount_type_str, true);
      $this->rec->discount_amount = (float)get_post_meta($id, self::$discount_amount_str, true);
      $this->rec->valid_products  = get_post_meta($id, self::$valid_products_str, true);
      $this->rec->no_recurring    = (bool)get_post_meta($id, self::$no_recurring_str, true);
      $this->rec->trial           = (bool)get_post_meta($id, self::$trial_str, true);
      $this->rec->trial_days      = (int)get_post_meta($id, self::$trial_days_str, true);
      $this->rec->trial_amount    = (float)get_post_meta($id, self::$trial_amount_str, true);
    }
  }
  
  public function get_formatted_products() /*tested*/
  {
    $formatted_array = array();
    
    if(!empty($this->valid_products))
      foreach($this->valid_products as $p)
      {
        $product = get_post($p);
        
        if($product)
          $formatted_array[] = $product->post_title;
      }
    else
      $formatted_array[] = __('None Selected', 'memberpress');
    
    return $formatted_array;
  }
  
  public static function get_all_active_coupons() /*tested*/
  {
    return get_posts(array('numberposts' => -1, 'post_type' => self::$cpt, 'post_status' => 'publish'));
  }
  
  public static function get_one_from_code($code) /*tested*/
  {
    global $wpdb;
    
    $q = "SELECT ID
            FROM {$wpdb->posts}
            WHERE post_title = %s
              AND post_type = %s
              AND post_status = 'publish'";
    $id = $wpdb->get_var($wpdb->prepare($q, $code, self::$cpt));
    
    if(!$id)
      return false;
    else
      return new MeprCoupon($id);
  }
  
  public function is_valid($product_id)
  {
    //Coupon has reached its usage limit (remember 0 = unlimited)
    if($this->usage_amount > 0 and $this->usage_count >= $this->usage_amount)
      return false;
    
    //Coupon has expired
    //This doesn't really need to be here but will be more accurate
    //than waiting every 12 hours for the expiring cron to run
    if($this->should_expire and $this->expires_on <= time())
      return false;
    
    //Coupon code is not valid for this product
    if(!in_array($product_id, $this->valid_products))
      return false;
    
    return true; // If we made it here, the coupon is good
  }
  
  //Hmmm...maybe this method should be moved to the Coupon Controller instead
  public static function is_valid_coupon_code($code, $product_id) /*tested*/
  {
    $c = self::get_one_from_code($code);
    
    //Coupon does not exist or has expired
    if($c === false)
      return false;
    
    return $c->is_valid($product_id);
  }
  
  public function apply_discount($price) /*tested*/
  {
    $value = 0;
    
    if($this->discount_type == 'percent')
      $value = ((1 - ($this->discount_amount / 100)) * $price);
    else
      $value = ($price - $this->discount_amount);
    
    if($value <= 0)
      return MeprUtils::format_float(0);
    else
      return MeprUtils::format_float($value); // must only be precise to 2 points
  }
  
  public static function expire_old_coupons_and_cleanup_db() /*dontTest*/
  {
    global $wpdb;
    $date = time();
    $last_run = get_option(self::$last_run_str, 0); //Prevents all this code from executing on every page load
    
    if(($date - $last_run) > 43200) //Runs twice a day just to be sure
    {
      $coupons = self::get_all_active_coupons();
      if(!empty($coupons))
      {
        foreach($coupons as $c)
        {
          $coupon = new MeprCoupon($c->ID);
          
          if($coupon->should_expire && $date > $coupon->expires_on)
            $coupon->mark_as_expired();
        }
      }
      update_option(self::$last_run_str, $date);
      
      //While we're in here we should consider deleting auto-draft coupons, waste of db space
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
    }
  }
  
  public function mark_as_expired() /*tested*/
  {
    $post = array( 'ID' => $this->ID,
                   'post_status' => 'trash' );
    wp_update_post($post);
  }
  
  public function update_usage_count()
  {
    global $wpdb;
    $mepr_db = new MeprDb();
    $tcount = 0;
    
    //Query unique subscriptions first and get a count
    //Prevents counting a coupon code on multiple payments
    //for the same subscription_id
    $sub = "SELECT post_id
              FROM {$wpdb->postmeta}
              WHERE meta_key = %s
                AND meta_value IN (%s, %s)";
    
    $sub = $wpdb->prepare($sub, MeprSubscription::$status_str, MeprSubscription::$active_str, MeprSubscription::$expired_str);
    
    $sq = "SELECT COUNT(DISTINCT subscription_id)
            FROM {$mepr_db->transactions}
            WHERE coupon_id = %d
              AND subscription_id > 0
              AND subscription_id IN ({$sub})
              AND status IN (%s, %s)";
    
    $sq = $wpdb->prepare($sq, $this->ID, MeprTransaction::$complete_str, MeprTransaction::$confirmed_str);
    
    if($sqcount = $wpdb->get_var($sq))
      $tcount += $sqcount;
    
    //Query lifetime payments next
    $lq = "SELECT COUNT(*)
            FROM {$mepr_db->transactions}
            WHERE coupon_id = %d
              AND (subscription_id = 0 || subscription_id IS NULL)
              AND status = %s";
    
    $lq = $wpdb->prepare($lq, $this->ID, MeprTransaction::$complete_str);
    
    if($lqcount = $wpdb->get_var($lq))
      $tcount += $lqcount;
    
    //Update and store
    $this->usage_count = $tcount;
    $this->store();
  }
  
  public function store_meta() /*tested*/
  {
    update_post_meta($this->ID, self::$should_expire_str, $this->should_expire);
    update_post_meta($this->ID, self::$expires_on_str, $this->expires_on);
    update_post_meta($this->ID, self::$usage_count_str, $this->usage_count);
    update_post_meta($this->ID, self::$usage_amount_str, $this->usage_amount);
    update_post_meta($this->ID, self::$discount_type_str, $this->discount_type);
    update_post_meta($this->ID, self::$discount_amount_str, $this->discount_amount);
    update_post_meta($this->ID, self::$valid_products_str, $this->valid_products);
    update_post_meta($this->ID, self::$no_recurring_str, $this->no_recurring);
    update_post_meta($this->ID, self::$trial_str, $this->trial);
    update_post_meta($this->ID, self::$trial_days_str, $this->trial_days);
    update_post_meta($this->ID, self::$trial_amount_str, $this->trial_amount);
  }
} //End class
