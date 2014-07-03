<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprSubscription extends MeprCptModel
{
  /** Instance Variables & Methods **/
  public static $subscr_id_str           = '_mepr_subscr_id';
  public static $response_str            = '_mepr_subscr_response'; // Response from gateway on creation
  public static $user_id_str             = '_mepr_subscr_user_id';
  public static $gateway_str             = '_mepr_subscr_gateway';
  public static $ip_addr_str             = '_mepr_subscr_ip_addr';
  public static $product_id_str          = '_mepr_subscr_product_id';
  public static $coupon_id_str           = '_mepr_subscr_coupon_id';
  public static $price_str               = '_mepr_subscr_price';
  public static $period_str              = '_mepr_subscr_period';
  public static $period_type_str         = '_mepr_subscr_period_type';
  public static $limit_cycles_str        = '_mepr_subscr_limit_cycles';
  public static $limit_cycles_num_str    = '_mepr_subscr_limit_cycles_num';
  public static $limit_cycles_action_str = '_mepr_subscr_limit_cycles_action';
  public static $prorated_trial_str      = '_mepr_subscr_prorated_trial';
  public static $trial_str               = '_mepr_subscr_trial';
  public static $trial_days_str          = '_mepr_subscr_trial_days';
  public static $trial_amount_str        = '_mepr_subscr_trial_amount';
  public static $status_str              = '_mepr_subscr_status';
  public static $created_at_str          = '_mepr_subscr_created_at';
  public static $cc_last4_str            = '_mepr_subscr_cc_last4';
  public static $cc_exp_month_str        = '_mepr_subscr_cc_month_exp';
  public static $cc_exp_year_str         = '_mepr_subscr_cc_year_exp';
  public static $cpt                     = 'mepr-subscriptions';
  
  public static $pending_str   = 'pending';
  public static $active_str    = 'active';
  public static $expired_str   = 'expired';
  public static $suspended_str = 'suspended';
  public static $cancelled_str = 'cancelled';
  
  /*** Instance Methods ***/
  public function __construct($id = null)
  {
    $this->attrs = array( "subscr_id",
                          "response",
                          "gateway",
                          "user_id",
                          "ip_addr",
                          "product_id",
                          "coupon_id",
                          "price",
                          "period",
                          "period_type",
                          "limit_cycles",
                          "limit_cycles_num",
                          "limit_cycles_action",
                          "prorated_trial",
                          "trial",
                          "trial_days",
                          "trial_amount",
                          "status",
                          "created_at",
                          "cc_last4",
                          "cc_exp_month",
                          "cc_exp_year" );
    
    if(null === ($this->rec = get_post($id)))
      $this->initialize_new_cpt();
    elseif($this->post_type != self::$cpt)
      $this->initialize_new_cpt();
    else
    {
      $this->rec->subscr_id = get_post_meta($id, MeprSubscription::$subscr_id_str, true);
      $this->rec->response = get_post_meta($id, MeprSubscription::$response_str, true);
      $this->rec->gateway = get_post_meta($id, MeprSubscription::$gateway_str, true);
      $this->rec->user_id = get_post_meta($id, MeprSubscription::$user_id_str, true);
      $this->rec->ip_addr = get_post_meta($id, MeprSubscription::$ip_addr_str, true);
      $this->rec->product_id = get_post_meta($id, MeprSubscription::$product_id_str, true);
      $this->rec->coupon_id = get_post_meta($id, MeprSubscription::$coupon_id_str, true);
      $this->rec->price = get_post_meta($id, MeprSubscription::$price_str, true);
      $this->rec->period = get_post_meta($id, MeprSubscription::$period_str, true);
      $this->rec->period_type = get_post_meta($id, MeprSubscription::$period_type_str, true);
      $this->rec->limit_cycles = get_post_meta($id, MeprSubscription::$limit_cycles_str, true);
      $this->rec->limit_cycles_num = get_post_meta($id, MeprSubscription::$limit_cycles_num_str, true);
      $this->rec->limit_cycles_action = get_post_meta($id, MeprSubscription::$limit_cycles_action_str, true);
      $this->rec->prorated_trial = get_post_meta($id, MeprSubscription::$prorated_trial_str, true);
      $this->rec->trial = get_post_meta($id, MeprSubscription::$trial_str, true);
      $this->rec->trial_days = get_post_meta($id, MeprSubscription::$trial_days_str, true);
      $this->rec->trial_amount = get_post_meta($id, MeprSubscription::$trial_amount_str, true);
      $this->rec->status = get_post_meta($id, MeprSubscription::$status_str, true);
      $this->rec->created_at = get_post_meta($id, MeprSubscription::$created_at_str, true);
      $this->rec->cc_last4 = get_post_meta($id, MeprSubscription::$cc_last4_str, true);
      $this->rec->cc_exp_month = get_post_meta($id, MeprSubscription::$cc_exp_month_str, true);
      $this->rec->cc_exp_year = get_post_meta($id, MeprSubscription::$cc_exp_year_str, true);
    }
  }
  
  public function store_meta() /*tested*/
  {
    $old_subscr = new self($this->ID);
    
    update_post_meta($this->ID, self::$subscr_id_str, $this->subscr_id);
    update_post_meta($this->ID, self::$response_str, $this->response);
    update_post_meta($this->ID, self::$gateway_str, $this->gateway);
    update_post_meta($this->ID, self::$user_id_str, $this->user_id);
    update_post_meta($this->ID, self::$ip_addr_str, $this->ip_addr);
    update_post_meta($this->ID, self::$product_id_str, $this->product_id);
    update_post_meta($this->ID, self::$coupon_id_str, $this->coupon_id);
    update_post_meta($this->ID, self::$price_str, $this->price);
    update_post_meta($this->ID, self::$period_str, $this->period);
    update_post_meta($this->ID, self::$period_type_str, $this->period_type);
    update_post_meta($this->ID, self::$limit_cycles_str, $this->limit_cycles);
    update_post_meta($this->ID, self::$limit_cycles_num_str, $this->limit_cycles_num);
    update_post_meta($this->ID, self::$limit_cycles_action_str, $this->limit_cycles_action);
    update_post_meta($this->ID, self::$prorated_trial_str, $this->prorated_trial);
    update_post_meta($this->ID, self::$trial_str, $this->trial);
    update_post_meta($this->ID, self::$trial_days_str, $this->trial_days);
    update_post_meta($this->ID, self::$trial_amount_str, $this->trial_amount);
    update_post_meta($this->ID, self::$status_str, $this->status);
    update_post_meta($this->ID, self::$created_at_str, $this->created_at);
    update_post_meta($this->ID, self::$cc_last4_str, $this->cc_last4);
    update_post_meta($this->ID, self::$cc_exp_month_str, $this->cc_exp_month);
    update_post_meta($this->ID, self::$cc_exp_year_str, $this->cc_exp_year);
    
    //Keep this hook at the bottom of this function
    do_action('mepr-subscr-transition-status', $old_subscr->status, $this->status, $this);
    do_action('mepr-subscr-store', $this);
    do_action('mepr-subscr-status-'.$this->status, $this);
  }
  
  public static function get_one_by_subscr_id($subscr_id) /*tested*/
  {
    global $wpdb;
    
    $sql = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s and meta_value = %s";
    $sql = $wpdb->prepare($sql, self::$subscr_id_str, $subscr_id);
    $post_id = $wpdb->get_var($sql);
    
    if($post_id)
      return new MeprSubscription($post_id);
    else
      return false;
  }

  public static function search_by_subscr_id($search) {
    global $wpdb; 
    $sql = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value LIKE %s";
    $sql = $wpdb->prepare($sql, self::$subscr_id_str, "{$search}%");
    $ids = $wpdb->get_col($sql);

    $subs = array();
    if(!empty($ids)) {
      foreach($ids as $id) {
        $subs[] = new MeprSubscription($id);
      }
    }

    return $subs;
  }

  public static function get_all_active_by_user_id($user_id, $order = "", $limit = "", $count = false)
  {
    global $wpdb;
    
    $order  = empty($order)?'':" ORDER BY {$order}";
    $limit  = empty($limit)?'':" LIMIT {$limit}";
    $fields = $count?'COUNT(*)':'p.*';
    
    $sql =  "SELECT {$fields} ".
              "FROM {$wpdb->posts} AS p ". 
              "JOIN {$wpdb->postmeta} AS pm ".
                "ON p.ID=pm.post_id ".
                  "AND pm.meta_key = %s ".
                  "AND pm.meta_value = %s ".
              "WHERE p.ID IN ( SELECT pm2.post_id ".
                                "FROM {$wpdb->postmeta} AS pm2 ".
                                "WHERE pm2.meta_key = %s ".
                                  "AND pm2.meta_value = %s )
            {$order}{$limit}";
    
    $sql = $wpdb->prepare($sql, self::$status_str, 'active', self::$user_id_str, $user_id);
    
    if($count)
      return $wpdb->get_var($sql);
    else
      return $wpdb->get_results($sql);
  }
  
  public static function get_all() /*dontTest*/
  {
    global $wpdb;
    
    $sql = "SELECT Meta.meta_value FROM {$wpdb->posts} Post, {$wpdb->postmeta} Meta WHERE Post.ID = Meta.post_id AND Post.post_type = %s AND Meta.meta_key = %s";
    $sql = $wpdb->prepare($sql, self::$cpt, self::$subscr_id_str);
    return $wpdb->get_col($sql);
  }
  
  public static function subscription_exists($subscr_id) /*tested*/
  {
    return is_object(self::get_one_by_subscr_id($subscr_id));
  }
  
  //Overriding base class method destroy() because we need to also remove txns
  public function destroy()
  {
    $txns = MeprTransaction::get_all_by_subscription_id($this->ID);
    
    if(!empty($txns))
      foreach($txns as $txn)
      {
        $kill_txn = new MeprTransaction($txn->id);
        $kill_txn->destroy();
      }
    
    wp_delete_post($this->ID, true);
  }
  
  //Sets product ID to 0 if for some reason a product is deleted
  public static function nullify_product_id_on_delete($id)
  {
    global $wpdb, $post_type;
    
    $q = "UPDATE {$wpdb->postmeta}
            SET meta_value = 0
            WHERE meta_value = %d AND
                  meta_key = %s";
    
    if($post_type == MeprProduct::$cpt)
      $wpdb->query($wpdb->prepare($q, $id, self::$product_id_str));
  }
  
  //Sets user id to 0 if for some reason a user is deleted
  public static function nullify_user_id_on_delete($id)
  {
    global $wpdb;
    
    $q = "UPDATE {$wpdb->postmeta}
            SET meta_value = 0
            WHERE meta_value = %d AND
                  meta_key = %s";
    
    $wpdb->query($wpdb->prepare($q, $id, self::$user_id_str));
  }

  public static function account_subscr_table( $order_by = '',
                                               $order = '',
                                               $paged = '',
                                               $search = '',
                                               $perpage = 10,
                                               $countonly = false,
                                               $params=null,
                                               $encols='all' )
  {
    global $wpdb;

    // Get the individual queries
    $lsql = self::lifetime_subscr_table( '', '', '', $search, 0,
                                         $countonly, $params, $encols, true );
    $sql = self::subscr_table( '', '', '', $search, 0,
                               $countonly, $params, $encols, true );

    /* -- Ordering parameters -- */
    //Parameters that are going to be used to order the result
    $order_by = (!empty($order_by) and !empty($order))?($order_by = ' ORDER BY '.$order_by.' '.$order):'';

    //Page Number
    if(empty($paged) or !is_numeric($paged) or $paged<=0) { $paged=1; }

    $limit = '';
    //adjust the query to take pagination into account
    if(!empty($paged) and !empty($perpage))
    {
      $offset=($paged - 1) * $perpage;
      $limit = ' LIMIT '.(int)$offset.','.(int)$perpage;
    }

    $wpdb->query("SET SQL_BIG_SELECTS=1");

    $asql = "({$lsql['query']}) UNION ({$sql['query']}){$order_by}{$limit}";
    $acsql = "SELECT (({$lsql['total_query']}) + ({$sql['total_query']}))";

    $results = $wpdb->get_results($asql);
    $count = $wpdb->get_var($acsql);

    return compact('results', 'count');
  }

  public static function subscr_table( $order_by = '',
                                       $order = '',
                                       $paged = '',
                                       $search = '',
                                       $perpage = 10,
                                       $countonly = false,
                                       $params=null,
                                       $encols='all',
                                       $queryonly = false
                                     )
  {
    global $wpdb;
    $mepr_options = MeprOptions::fetch();
    $pmt_methods = $mepr_options->payment_methods();
    $mepr_db = new MeprDb();
    $en = create_function('$c,$e', 'return (!is_array($e) || in_array($c,$e) );');

    if(is_null($params)) { $params=$_GET; }
    
    if(!empty($pmt_methods))
    {
      $gateway = '(SELECT CASE pm_gateway.meta_value';
      
      foreach($pmt_methods as $method)
        $gateway .= $wpdb->prepare(" WHEN %s THEN %s", $method->id, "{$method->label} ({$method->name})"); 
      
      $gateway .= $wpdb->prepare(" ELSE %s END)", __('Unknown', 'memberpress'));
    }
    else
      $gateway = 'pm_gateway.meta_value';

    // The transaction count
    $txn_count = $wpdb->prepare("(SELECT COUNT(*) FROM {$mepr_db->transactions} AS txn_cnt WHERE txn_cnt.subscription_id=pst.ID AND txn_cnt.status=%s)", MeprTransaction::$complete_str);

    $active = $wpdb->prepare('(SELECT CASE WHEN expiring_txn.expires_at = 0 OR expiring_txn.expires_at = \'0000-00-00 00:00:00\' THEN %s WHEN expiring_txn.expires_at IS NULL OR expiring_txn.expires_at < NOW() THEN %s WHEN expiring_txn.status = %s AND expiring_txn.txn_type = %s THEN %s ELSE %s END)', '<span class="mepr-active">' . __('Yes','memberpress') . '</span>', '<span class="mepr-inactive">' . __('No','memberpress') . '</span>', MeprTransaction::$confirmed_str, MeprTransaction::$subscription_confirmation_str, '<span class="mepr-active">' . __('Yes','memberpress') . '</span>', '<span class="mepr-active">' . __('Yes', 'memberpress') . '</span>');
    
    $fname = "(SELECT um_fname.meta_value FROM {$wpdb->usermeta} AS um_fname WHERE um_fname.user_id = u.ID AND um_fname.meta_key = 'first_name' LIMIT 1)";
    $lname = "(SELECT um_lname.meta_value FROM {$wpdb->usermeta} AS um_lname WHERE um_lname.user_id = u.ID AND um_lname.meta_key = 'last_name' LIMIT 1)";

    $cols = array( 'sub_type' => "'subscription'" );
    if( $en('ID',             $encols) ) { $cols['ID']             = 'pst.ID'; }
    if( $en('subscr_id',      $encols) ) { $cols['subscr_id']      = 'pm_subscr_id.meta_value'; }
    if( $en('user_id',        $encols) ) { $cols['user_id']        = 'pm_user_id.meta_value'; }
    if( $en('user_email',     $encols) ) { $cols['user_email']     = 'u.user_email'; }
    if( $en('gateway',        $encols) ) { $cols['gateway']        = $gateway; }
    if( $en('member',         $encols) ) { $cols['member']         = 'u.user_login'; }
    if( $en('fname',          $encols) ) { $cols['fname']          = $fname; }
    if( $en('lname',          $encols) ) { $cols['lname']          = $lname; }
    if( $en('product_id',     $encols) ) { $cols['product_id']     = 'pm_product_id.meta_value'; }
    if( $en('coupon_id',      $encols) ) { $cols['coupon_id']      = 'pm_coupon_id.meta_value'; }
    if( $en('product_name',   $encols) ) { $cols['product_name']   = 'prd.post_title'; }
    if( $en('price',          $encols) ) { $cols['price']          = 'pm_price.meta_value'; }
    if( $en('period',         $encols) ) { $cols['period']         = 'pm_period.meta_value'; }
    if( $en('period_type',    $encols) ) { $cols['period_type']    = 'pm_period_type.meta_value'; }
    if( $en('prorated_trial', $encols) ) { $cols['prorated_trial'] = 'pm_prorated_trial.meta_value'; }
    if( $en('trial',          $encols) ) { $cols['trial']          = 'pm_trial.meta_value'; }
    if( $en('trial_days',     $encols) ) { $cols['trial_days']     = 'pm_trial_days.meta_value'; }
    if( $en('trial_amount',   $encols) ) { $cols['trial_amount']   = 'pm_trial_amount.meta_value'; }
    if( $en('first_txn_id',   $encols) ) { $cols['first_txn_id']   = 'first_txn.id'; }
    if( $en('latest_txn_id',  $encols) ) { $cols['latest_txn_id']  = 'last_txn.id'; }
    if( $en('expiring_txn_id',$encols) ) { $cols['expiring_txn_id']= 'expiring_txn.id'; }
    if( $en('txn_count',      $encols) ) { $cols['txn_count']      = $txn_count; }
    if( $en('status',         $encols) ) { $cols['status']         = 'pm_status.meta_value'; }
    if( $en('ip_addr',        $encols) ) { $cols['ip_addr']        = 'pm_ip_addr.meta_value'; }
    if( $en('created_at',     $encols) ) { $cols['created_at']     = 'pm_created_at.meta_value'; }
    if( $en('expires_at',     $encols) ) { $cols['expires_at']     = 'expiring_txn.expires_at'; }
    if( $en('active',         $encols) ) { $cols['active']         = $active; }

    $args = array($wpdb->prepare("pst.post_type = %s", self::$cpt));

    if(isset($params['member']) && !empty($params['member']))
      $args[] = $wpdb->prepare("u.user_login = %s", $params['member']);

    if(isset($params['subscription']) && !empty($params['subscription']))
      $args[] = $wpdb->prepare("pst.ID = %d", $params['subscription']);

    if(isset($params['statuses']) && !empty($params['statuses'])) {
      $qry = array();
      foreach($params['statuses'] as $st)
        $qry[] = $wpdb->prepare('pm_status.meta_value=%s',$st);

      $args[] = '(' . implode( ' OR ', $qry ) . ')';
    }

    $joins = array();
    $joins[] = "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_user_id ON pm_user_id.post_id = pst.ID AND pm_user_id.meta_key = '".self::$user_id_str."'";
    $joins[] = "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_product_id ON pm_product_id.post_id = pst.ID AND pm_product_id.meta_key = '".self::$product_id_str."'";
    $joins[] = "LEFT OUTER JOIN {$wpdb->users} AS u ON u.ID = pm_user_id.meta_value";
    $joins[] = "LEFT OUTER JOIN {$wpdb->posts} AS prd ON prd.ID = pm_product_id.meta_value";

    // The first transaction
    $joins[] = $wpdb->prepare( "LEFT OUTER JOIN {$mepr_db->transactions} AS first_txn ON first_txn.id=(SELECT ft1.id FROM {$mepr_db->transactions} AS ft1 WHERE ft1.subscription_id=pst.ID AND ft1.status IN (%s,%s) ORDER BY ft1.id ASC LIMIT 1)",  MeprTransaction::$confirmed_str, MeprTransaction::$complete_str);

    // The last transaction made
    $joins[] = $wpdb->prepare("LEFT OUTER JOIN {$mepr_db->transactions} AS last_txn ON last_txn.id=(SELECT lt1.id FROM {$mepr_db->transactions} AS lt1 WHERE lt1.subscription_id=pst.ID AND lt1.status IN (%s,%s) ORDER BY lt1.id DESC LIMIT 1)",  MeprTransaction::$confirmed_str, MeprTransaction::$complete_str);

    // The transaction associated with this subscription with the latest expiration date
    $joins[] = $wpdb->prepare( "LEFT OUTER JOIN {$mepr_db->transactions} AS expiring_txn " .
                                 "ON expiring_txn.id = " .
                                   "(SELECT t.id " .
                                      "FROM {$mepr_db->transactions} AS t " .
                                     "WHERE t.subscription_id=pst.ID " .
                                       "AND t.status IN (%s,%s) " .
                                       "AND ( t.expires_at = '0000-00-00 00:00:00' " .
                                             "OR ( t.expires_at <> '0000-00-00 00:00:00' " .
                                                  "AND t.expires_at=( SELECT MAX(t2.expires_at) " .
                                                                        "FROM {$mepr_db->transactions} as t2 " .
                                                                       "WHERE t2.subscription_id=pst.ID " .
                                                                         "AND t2.status IN (%s,%s) " .
                                                                   ") " .
                                                ") " .
                                           ") " .
                                     // If there's a lifetime and an expires at, favor the lifetime
                                     "ORDER BY t.expires_at " . 
                                     "LIMIT 1)",
                                     MeprTransaction::$confirmed_str,
                                     MeprTransaction::$complete_str,
                                     MeprTransaction::$confirmed_str,
                                     MeprTransaction::$complete_str );

    if( $en( 'subscr_id', $encols ) ) { $joins[] = "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_subscr_id ON pm_subscr_id.post_id = pst.ID AND pm_subscr_id.meta_key = '".self::$subscr_id_str."'"; }
    if( $en( 'gateway', $encols ) ) { $joins[] = "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_gateway ON pm_gateway.post_id = pst.ID AND pm_gateway.meta_key = '".self::$gateway_str."'"; }
    if( $en( 'coupon_id', $encols ) ) { $joins[] = "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_coupon_id ON pm_coupon_id.post_id = pst.ID AND pm_coupon_id.meta_key = '".self::$coupon_id_str."'"; }
    if( $en( 'price', $encols ) ) { $joins[] = "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_price ON pm_price.post_id = pst.ID AND pm_price.meta_key = '".self::$price_str."'"; }
    if( $en( 'period', $encols ) ) { $joins[] = "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_period ON pm_period.post_id = pst.ID AND pm_period.meta_key = '".self::$period_str."'"; }
    if( $en( 'period_type', $encols ) ) { $joins[] = "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_period_type ON pm_period_type.post_id = pst.ID AND pm_period_type.meta_key = '".self::$period_type_str."'"; }
    if( $en( 'prorated_trial', $encols ) ) { $joins[] = "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_prorated_trial ON pm_prorated_trial.post_id = pst.ID AND pm_prorated_trial.meta_key = '".self::$prorated_trial_str."'"; }
    if( $en( 'trial', $encols ) ) { $joins[] = "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_trial ON pm_trial.post_id = pst.ID AND pm_trial.meta_key = '".self::$trial_str."'"; }
    if( $en( 'trial_days', $encols ) ) { $joins[] = "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_trial_days ON pm_trial_days.post_id = pst.ID AND pm_trial_days.meta_key = '".self::$trial_days_str."'"; }
    if( $en( 'trial_amount', $encols ) ) { $joins[] = "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_trial_amount ON pm_trial_amount.post_id = pst.ID AND pm_trial_amount.meta_key = '".self::$trial_amount_str."'"; }
    if( $en( 'status', $encols ) ) { $joins[] = "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_status ON pm_status.post_id = pst.ID AND pm_status.meta_key = '".self::$status_str."'"; }
    if( $en( 'created_at', $encols ) ) { $joins[] = "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_created_at ON pm_created_at.post_id = pst.ID AND pm_created_at.meta_key = '".self::$created_at_str."'"; }
    if( $en( 'cc_last4', $encols ) ) { $joins[] = "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_cc_last4 ON pm_cc_last4.post_id = pst.ID AND pm_cc_last4.meta_key = '".self::$cc_last4_str."'"; }
    if( $en( 'cc_exp_month', $encols ) ) { $joins[] = "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_cc_exp_month ON pm_cc_exp_month.post_id = pst.ID AND pm_cc_exp_month.meta_key = '".self::$cc_exp_month_str."'"; }
    if( $en( 'cc_exp_year', $encols ) ) { $joins[] = "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_cc_exp_year ON pm_cc_exp_year.post_id = pst.ID AND pm_cc_exp_year.meta_key = '".self::$cc_exp_year_str."'"; }
    if( $en( 'ip_addr', $encols ) ) { $joins[] = "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_ip_addr ON pm_ip_addr.post_id = pst.ID AND pm_ip_addr.meta_key = '".self::$ip_addr_str."'"; }
    
    return MeprDb::list_table( $cols, "{$wpdb->posts} AS pst", $joins,
                               $args, $order_by, $order, $paged, $search,
                               $perpage, $countonly, $queryonly );
  }

  // Okay, these are actually transactions but to the unwashed masses ... they're subscriptions
  public static function lifetime_subscr_table( $order_by = '',
                                                $order = '',
                                                $paged = '',
                                                $search = '',
                                                $perpage = 10,
                                                $countonly = false,
                                                $params = null,
                                                $encols = 'all',
                                                $queryonly = false )
  {
    global $wpdb;
    $mepr_options = MeprOptions::fetch();
    $pmt_methods = $mepr_options->payment_methods();
    $mepr_db = new MeprDb();
    $en = create_function('$c,$e', 'return (!is_array($e) || in_array($c,$e) );');

    if(is_null($params)) { $params=$_GET; }

    if(!empty($pmt_methods))
    {
      $gateway = '(SELECT CASE txn.gateway';
      
      foreach($pmt_methods as $method)
        $gateway .= $wpdb->prepare(" WHEN %s THEN %s", $method->id, "{$method->label} ({$method->name})"); 
      
      $gateway .= $wpdb->prepare(" ELSE %s END)", __('Unknown', 'memberpress'));
    }
    else
      $gateway = 'txn.gateway';
      
    $fname = "(SELECT um_fname.meta_value FROM {$wpdb->usermeta} AS um_fname WHERE um_fname.user_id = u.ID AND um_fname.meta_key = 'first_name' LIMIT 1)";
    $lname = "(SELECT um_lname.meta_value FROM {$wpdb->usermeta} AS um_lname WHERE um_lname.user_id = u.ID AND um_lname.meta_key = 'last_name' LIMIT 1)";

    $cols = array( 'sub_type' => "'transaction'" );
    if( $en('ID',$encols) ) { $cols['ID'] = 'txn.id'; }
    if( $en('subscr_id',$encols) ) { $cols['subscr_id'] = 'txn.trans_num'; }
    if( $en('user_id',$encols) ) { $cols['user_id'] = 'txn.user_id'; }
    if( $en('user_email',$encols) ) { $cols['user_email'] = 'u.user_email'; }
    if( $en('gateway',$encols) ) { $cols['gateway'] = $gateway; }
    if( $en('member',$encols) ) { $cols['member'] = 'u.user_login'; }
    if( $en('fname',$encols) ) { $cols['fname'] = $fname; }
    if( $en('lname',$encols) ) { $cols['lname'] = $lname; }
    if( $en('product_id',$encols) ) { $cols['product_id'] = 'txn.product_id'; }
    if( $en('coupon_id',$encols) ) { $cols['coupon_id'] = 'txn.coupon_id'; }
    if( $en('product_name',$encols) ) { $cols['product_name'] = 'prd.post_title'; }
    if( $en('price',$encols) ) { $cols['price'] = 'txn.amount'; }
    if( $en('period',$encols) ) { $cols['period'] = $wpdb->prepare('%d',1); }
    if( $en('period_type',$encols) ) { $cols['period_type'] = $wpdb->prepare('%s','lifetime'); }
    if( $en('prorated_trial',$encols) ) { $cols['prorated_trial'] = $wpdb->prepare('%d',0); }
    if( $en('trial',$encols) ) { $cols['trial'] = $wpdb->prepare('%d',0); }
    if( $en('trial_days',$encols) ) { $cols['trial_days'] = $wpdb->prepare('%d',0); }
    if( $en('trial_amount',$encols) ) { $cols['trial_amount'] = $wpdb->prepare('%f',0.00); }
    if( $en('latest_txn_id',$encols) ) { $cols['latest_txn_id'] = 'txn.id'; }
    if( $en('txn_count',$encols) ) { $cols['txn_count'] = $wpdb->prepare('%s',1); }
    if( $en('status',$encols) ) { $cols['status'] = $wpdb->prepare('%s',__('None','memberpress')); }
    if( $en('ip_addr',$encols) ) { $cols['ip_addr'] = 'txn.ip_addr'; }
    if( $en('created_at',$encols) ) { $cols['created_at'] = 'txn.created_at'; }
    if( $en('expires_at',$encols) ) { $cols['expires_at'] = 'txn.expires_at'; }
    if( $en('active',$encols) ) { $cols['active'] = $wpdb->prepare('(SELECT CASE WHEN txn.status IN (%s,%s) AND ( txn.expires_at = \'0000-00-00 00:00:00\' OR txn.expires_at >= NOW() ) THEN %s ELSE %s END)', MeprTransaction::$complete_str, MeprTransaction::$confirmed_str, '<span class="mepr-active">' . __('Yes','memberpress') . '</span>', '<span class="mepr-inactive">' . __('No', 'memberpress') . '</span>'); }

    $args = array('(txn.subscription_id IS NULL OR txn.subscription_id <= 0)');

    if(isset($params['member']) && !empty($params['member']))
      $args[] = $wpdb->prepare("u.user_login = %s", $params['member']);

    if(isset($params['subscription']) and !empty($params['subscription']))
      $args[] = $wpdb->prepare("txn.id = %d", $params['subscription']);

    if(isset($params['statuses']) && !empty($params['statuses'])) {
      $qry = array();
      foreach($params['statuses'] as $st) {
        // Map subscription status to transaction status
        $txn_status = MeprTransaction::map_subscr_status( $st );

        if( !$txn_status ) { continue; }

        if( !is_array( $txn_status ) ) { $txn_status = array( $txn_status ); }

        foreach( $txn_status as $txn_st ) {
          $qry[] = $wpdb->prepare( 'txn.status=%s', $txn_st );
        }
      }

      $args[] = '(' . implode( ' OR ', $qry ) . ')';
    }

    $joins = array();
    $joins[] = "LEFT OUTER JOIN {$wpdb->users} AS u ON u.ID = txn.user_id";
    $joins[] = "LEFT OUTER JOIN {$wpdb->posts} AS prd ON prd.ID = txn.product_id";
    if( $en('period_type',$encols) ) { $joins[] = $wpdb->prepare( "LEFT OUTER JOIN {$wpdb->postmeta} AS pm_period_type ON pm_period_type.post_id = prd.ID AND pm_period_type.meta_key = %s", MeprProduct::$period_type_str ); }

    return MeprDb::list_table( $cols, "{$mepr_db->transactions} AS txn",
                               $joins, $args, $order_by, $order, $paged,
                               $search, $perpage, $countonly, $queryonly );
  }

  public function user()
  {
    static $usr;
    
    if(!isset($usr) or !($usr instanceof MeprUser) or $usr->ID != $this->user_id)
      $usr = new MeprUser($this->user_id);
    
    return $usr;
  }
  
  public function product()
  {
    static $prd;
    
    if(!isset($prd) or !($prd instanceof MeprProduct) or $prd->ID != $this->product_id)
      $prd = new MeprProduct($this->product_id);
    
    return $prd;
  }

  // Has one through product
  public function group() {
    $prd = $this->product();
    return $prd->group();
  }
  
  public function coupon()
  {
    if(!isset($this->coupon_id) or empty($this->coupon_id))
      return false;
    
    static $cpn;
    
    if(!isset($cpn) or !($cpn instanceof MeprCoupon) or $cpn->ID != $this->coupon_id)
      $cpn = new MeprCoupon($this->coupon_id);
    
    return $cpn;
  }
  
  public function first_txn()
  {
    static $txn;
    
    if(!isset($txn) or !($txn instanceof MeprTransaction) or $txn->id != $this->first_txn_id)
      $txn = new MeprTransaction($this->first_txn_id);
    
    return $txn;
  }
  
  public function latest_txn()
  {
    static $txn;
    
    if(!isset($txn) or !($txn instanceof MeprTransaction) or $txn->id != $this->latest_txn_id)
      $txn = new MeprTransaction($this->latest_txn_id);
    
    return $txn;
  }
  
  public function expiring_txn()
  {
    static $txn;
    
    if(!isset($txn) or !($txn instanceof MeprTransaction) or $txn->id != $this->expiring_txn_id)
      $txn = new MeprTransaction($this->expiring_txn_id);
    
    return $txn;
  }
  
  public function transactions($return_objects = true, $where = "", $order = "created_at")
  {
    global $wpdb;
    $mepr_db = new MeprDb();
    
    if(!empty($where))
      $where = "AND {$where}";
    
    $query = "SELECT id FROM {$mepr_db->transactions} AS t 
                WHERE t.subscription_id = %d
                {$where}
              {$order}";
    $query = $wpdb->prepare($query, $this->ID);
    
    $res = $wpdb->get_col($query);
    
    if($return_objects and !empty($res))
    {
      $txns = array();
      
      foreach($res as $id)
        $txns[] = new MeprTransaction($id);
      
      return $txns;
    }
    
    return $res;
  }

  //Cancels a subscription is the limit_cycles_num >= txn_count
  //$trial_offset is used if a paid trial payment exists
  public function limit_payment_cycles() {
    //Check if limiting is even enabled
    if(!$this->limit_cycles) { return; }

    $pm = $this->payment_method();
    $trial_offset = (($this->trial && $this->trial_amount > 0.00)?1:0);

    //Cancel this subscription if the payment cycles are limited and have been reached
    if($this->status == MeprSubscription::$active_str && ($this->txn_count - $trial_offset) >= $this->limit_cycles_num) {
      $_REQUEST['expire']=true; // pass the expire
      $_REQUEST['silent']=true; // Don't want to send cancellation notices
      $pm->process_cancel_subscription($this->ID);
    }
  }

  // This should be called from process_cancel_subscription
  public function limit_reached_actions() {
    //Check if limiting is even enabled
    if(!$this->limit_cycles) { return; }

    if($this->limit_cycles_action == 'lifetime') {
      $txn = $this->latest_txn();
      $txn->expires_at = 0; // Zero for lifetime expiration
      $txn->store();
    }

    do_action('mepr-limit-payment-cycles-reached', $this);
  }

  public function expire_txns()
  {
    global $wpdb;
    $mepr_db = new MeprDb();
    $time = time();
    
    $q = "UPDATE {$mepr_db->transactions}
            SET expires_at = %s
            WHERE subscription_id = %d
              AND expires_at >= %s";
    
    // Set expiration 1 day in the past so it expires NOW
    $wpdb->query( $wpdb->prepare( $q,
                                  MeprUtils::ts_to_mysql_date($time-MeprUtils::days(1)),
                                  $this->ID,
                                  MeprUtils::ts_to_mysql_date($time) ) );
  }

  public function payment_method()
  {
    $mepr_options = MeprOptions::fetch();
    return $mepr_options->payment_method($this->gateway);
  }

  // Use this instead of just *->status so that we can make sure
  // the status is updated when you call it
  public function get_status()
  {
    $latest_txn = $this->latest_txn();
    $expires_at = strtotime($latest_txn->expires_at);
    $now = time();
    
    if( $latest_txn->status!=MeprTransaction::$complete_str or
        $latest_txn->status!=MeprTransaction::$confirmed_str or
        $expires_at<=$now )
    {
      $this->status = MeprSubscription::$expired_str;
      $this->store();
    }
    
    return $this->status;
  }

  public function in_free_trial() {
    return $this->in_trial('free');
  }

  public function in_paid_trial() {
    return $this->in_trial('paid');
  }

  /* Paid or Free trial ... it matters not ... this will return true */
  public function in_trial($type='all') {
    if($this->trial) {
      $trial_started = strtotime($this->created_at);
      $trial_ended   = $trial_started + MeprUtils::days($this->trial_days);

      if( ( $type=='paid' and (float)$this->trial_amount <= 0.00 ) or
          ( $type=='free' and (float)$this->trial_amount > 0.00 ) )
      {
        return false;
      }
      
      return (time() < $trial_ended);
    }

    return false;
  }
  
  public function days_till_expiration()
  {
    $mepr_options = MeprOptions::fetch();

    $now = time();
    $expiring_txn = $this->expiring_txn();
    $expires_at = strtotime($expiring_txn->expires_at) - MeprUtils::days($mepr_options->grace_expire_days);

    if( $expires_at<=$now or
        !in_array($expiring_txn->status,
                  array(MeprTransaction::$complete_str,
                        MeprTransaction::$confirmed_str)) )
    { return 0; }

    // round and provide an integer ... lest we screw everything up
    return intval(round((($expires_at-$now) / MeprUtils::days(1))));
  }
  
  public function days_in_this_period()
  {
    if($this->in_trial())
      $period_seconds = MeprUtils::days($this->trial_days);
    else {
      $latest_txn = $this->latest_txn();
      
      switch($this->period_type) {
        case 'weeks':
          $period_seconds = MeprUtils::weeks($this->period);
          break;
        case 'months':
          $period_seconds = MeprUtils::months($this->period, strtotime($latest_txn->created_at));
          break;
        case 'years':
          $period_seconds = MeprUtils::years($this->period, strtotime($latest_txn->created_at));
          break;
        default:
          return false;
      }
    }
    
    return intval(round($period_seconds / MeprUtils::days(1)));
  }
  
  public function trial_expires_at()
  {
    $created_at = strtotime($this->created_at);
    return ($created_at + MeprUtils::days($this->trial_days));
  }

  public function is_expired()
  {
    if(($expires_at = $this->expires_at) == '0000-00-00 00:00:00')
      return false;
    else {
      $expires_at = strtotime($expires_at);
      return ( $expires_at < time() );
    }
  }

  public function is_lifetime()
  {
    return ($this->expires_at == '0000-00-00 00:00:00');
  }

  public function is_active()
  {
    return !$this->is_expired();
  }

  public function cc_num()
  {
    return MeprUtils::cc_num($this->cc_last4);
  }

  public function is_upgrade()
  {
    return $this->is_upgrade_or_downgrade('upgrade');
  }

  public function is_downgrade()
  {
    return $this->is_upgrade_or_downgrade('downgrade');
  }

  public function is_upgrade_or_downgrade($type=false)
  {
    $prd = $this->product();
    $usr = $this->user();
    return ($usr->is_logged_in_and_current_user() and $prd->is_upgrade_or_downgrade($type));
  }

  // This doesn't store ... this just sets up the
  // prorated trial ... do what you will later on
  public function maybe_prorate() {
    $mepr_options = MeprOptions::fetch();
    $usr = $this->user();
    $this->prorated_trial=false;

    if( $usr->is_logged_in_and_current_user() and
        $this->is_upgrade_or_downgrade() and $mepr_options->pro_rated_upgrades )
    {
      $grp = $this->group();
      if( $old_sub = $usr->subscription_in_group($grp->ID) and
          $old_sub->ID != $this->ID and !$old_sub->in_free_trial() )
      {
        $r = MeprUtils::calculate_proration_by_subs( $old_sub, $this );

        // Prorations override the trial ... if there is one
        if( $r->days > 0 or $r->proration > 0.00 )
        {
          $this->prorated_trial=true;
          $this->trial=true;
          $this->trial_days=$r->days;
          $this->trial_amount=$r->proration;
        }
      }
    }
  }

  public function maybe_cancel_old_sub() {
    $mepr_options = MeprOptions::fetch();
    $usr = $this->user();

    if( $usr->is_logged_in_and_current_user() and
        $this->is_upgrade_or_downgrade() ) {
      $grp = $this->group();

      if( $old_sub = $usr->subscription_in_group($grp->ID) and
          $old_sub->ID != $this->ID ) {
        $old_sub->expire_txns(); //Expire associated transactions for the old subscription
        $_REQUEST['silent']=true; // Don't want to send cancellation notices
        $old_sub->cancel();
      }
      else if($old_lifetime_txn = $usr->lifetime_subscription_in_group($grp->ID)) {
        $old_lifetime_txn->expires_at = MeprUtils::ts_to_mysql_date(time()-MeprUtils::days(1));
        $old_lifetime_txn->store();
      }
    }
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
      default:
          $expires_at = false;
    }
    
    return $expires_at;
  }

  public function load_product_vars($prd, $coupon_code=null) {
    $mock_coupon = (object)array('post_title' => null, 'ID' => 0, 'trial' => 0);

    if(empty($coupon_code) || !MeprCoupon::is_valid_coupon_code($coupon_code, $prd->ID))
      $coupon = $mock_coupon;
    else {
      if(!($coupon = MeprCoupon::get_one_from_code($coupon_code)))
        $coupon = $mock_coupon;
    }

    $this->product_id = $prd->ID;
    $this->price = $prd->adjusted_price($coupon->post_title);
    $this->coupon_id = $coupon->ID;
    $this->period = $prd->period;
    $this->period_type = $prd->period_type;
    $this->limit_cycles = $prd->limit_cycles;
    $this->limit_cycles_num = $prd->limit_cycles_num;
    $this->limit_cycles_action = $prd->limit_cycles_action;
    $this->trial = $prd->trial;
    $this->trial_days = $prd->trial_days;
    $this->trial_amount = $prd->trial_amount;

    // This will only happen with a real coupon
    if($coupon->trial) {
      $this->trial = $coupon->trial;
      $this->trial_days = $coupon->trial_days;
      $this->trial_amount = $coupon->trial_amount;
    }
  }

  /** Convenience method to determine what we can do
    * with the gateway associated with the subscription
    */
  public function can($cap) {
    $pm = $this->payment_method();

    if($pm!=false and is_object($pm))
      return $pm->can($cap);

    return false;
  }

  public function suspend() {
    if($this->can('suspend-subscriptions')) {
      $pm = $this->payment_method();
      return $pm->process_suspend_subscription($this->ID);
    }

    return false;
  }

  public function resume() {
    if($this->can('resume-subscriptions')) {
      $pm = $this->payment_method();
      return $pm->process_resume_subscription($this->ID);
    }

    return false;
  }

  public function cancel() {
    if($this->can('cancel-subscriptions')) {
      $pm = $this->payment_method();
      return $pm->process_cancel_subscription($this->ID);
    }

    return false;
  }

  public function cc_expiring_before_next_payment()
  {
    if( $next_billing_at = $this->next_billing_at and
        $exp_month = $this->cc_exp_month and
        $exp_year = $this->cc_exp_year )
    {
      $cc_exp_ts = mktime( 0, 0, 0, $exp_month, 1, $exp_year );
      $next_billing_ts = strtotime( $next_billing_at );
      return ( $cc_exp_ts < $next_billing_ts );
    }

    return false;
  }

  public function update_url() {
    $mepr_options = MeprOptions::fetch();
    return $mepr_options->account_page_url("action=update&sub={$this->ID}");
  }

  public function upgrade_url() {
    $mepr_options = MeprOptions::fetch();
    if( $grp = $this->group() and $grp->is_upgrade_path )
      return $mepr_options->account_page_url("action=upgrade&sub={$this->ID}");

    return '';
  }
  
  //PRETTY MUCH ONLY FOR AUTHORIZE.NET CURRENTLY
  //But could be used for Manual Subscriptions / PayPal Reference txn's eventually
  public function calculate_catchup($type = 'proration') {
    /*
     * $types can be any of the following
     * 
     * none       = no payment
     * full       = from expiration date of last txn until next billing date
     * period     = full amount for current period -- regardless of date
     * proration  = prorated amount for current period only (default)
     *
     */
    
    //If type is none, or the subscription hasn't expired -- return false
    if($type == 'none' || !$this->is_expired())
      return false;
    
    $latest_txn = $this->latest_txn();
    
    // Calculate Next billing time
    $expired_at = strtotime($latest_txn->expires_at);
    $now = time();
    $time_elapsed = $now - $expired_at; 
    $periods_elapsed = (int)($time_elapsed / MeprUtils::days($this->days_in_this_period()));
    $next_billing = $now;
    $subscription_cost_per_day = (float)((float)$this->price / $this->days_in_this_period());
    
    switch( $this->period_type ) {
      case 'weeks':
        $next_billing = $expired_at + MeprUtils::weeks($periods_elapsed+1);
        break;
      case 'months':
        $next_billing = $expired_at + MeprUtils::months($periods_elapsed+1, $expired_at);
        break;
      case 'years':
        $next_billing = $expired_at + MeprUtils::years($periods_elapsed+1, $expired_at);
        break;
    }
    
    //Handle $type = period
    if($type == 'period') {
      $full_price = MeprUtils::format_float($this->price);
      
      return (object)array('proration' => $full_price, 'next_billing' => $next_billing);
    }
    
    //Handle $type = full
    if($type == 'full') {
      $total_time_elapsed = $next_billing - $expired_at;
      $full_days_till_billing = (int)($total_time_elapsed / MeprUtils::days(1));
      $full_proration = MeprUtils::format_float($subscription_cost_per_day * $full_days_till_billing);
      
      return (object)array('proration' => $full_proration, 'next_billing' => $next_billing);
    }
    
    //All other $types have been handled, so if we made it here just calculate $type = 'proration'
    $seconds_till_billing = $next_billing - $now;
    $days_till_billing = (int)($seconds_till_billing / MeprUtils::days(1));
    $proration = MeprUtils::format_float($subscription_cost_per_day * $days_till_billing);
    
    return (object)compact('proration', 'next_billing');
  }
  
  /***** MAGIC METHOD HANDLERS *****/
  protected function mgm_first_txn_id($mgm,$val='') {
    global $wpdb; 
    $mepr_db = new MeprDb();

    switch($mgm) {
      case 'get':
        $q = $wpdb->prepare( "SELECT t.id " .
                               "FROM {$mepr_db->transactions} AS t " .
                              "WHERE t.subscription_id=%d " .
                              "ORDER BY t.id ASC " .
                              "LIMIT 1",
                             $this->rec->ID );
        return $wpdb->get_var($q);
      default:
        return true;
    }
  }

  protected function mgm_latest_txn_id($mgm,$val='') {
    global $wpdb; 
    $mepr_db = new MeprDb();

    switch($mgm) {
      case 'get':
        $q = $wpdb->prepare( "SELECT t.id " .
                               "FROM {$mepr_db->transactions} AS t " .
                              "WHERE t.subscription_id=%d " .
                              "ORDER BY t.id DESC " .
                              "LIMIT 1",
                             $this->rec->ID );
        return $wpdb->get_var($q);
      default:
        return true;
    }
  }

  protected function mgm_expiring_txn_id($mgm,$val='') {
    global $wpdb; 
    $mepr_db = new MeprDb();

    switch($mgm) {
      case 'get':
        $q = $wpdb->prepare( "SELECT t.id " .
                               "FROM {$mepr_db->transactions} AS t " .
                              "WHERE t.subscription_id=%d " .
                                "AND t.status IN (%s,%s) " .
                                "AND ( t.expires_at = '0000-00-00 00:00:00' " .
                                      "OR ( t.expires_at <> '0000-00-00 00:00:00' " .
                                           "AND t.expires_at=( SELECT MAX(t2.expires_at) " .
                                                                 "FROM {$mepr_db->transactions} as t2 " .
                                                                "WHERE t2.subscription_id=%d " .
                                                                  "AND t2.status IN (%s,%s) " .
                                                             ") " .
                                         ") " .
                                    ") " .
                              // If there's a lifetime and an expires at, favor the lifetime
                              "ORDER BY t.expires_at " . 
                              "LIMIT 1",
                              $this->rec->ID,
                              MeprTransaction::$confirmed_str,
                              MeprTransaction::$complete_str,
                              $this->rec->ID,
                              MeprTransaction::$confirmed_str,
                              MeprTransaction::$complete_str );
        return $wpdb->get_var($q);
      default:
        return true;
    }
  }

  protected function mgm_txn_count($mgm,$val='') {
    global $wpdb; 
    $mepr_db = new MeprDb();

    switch($mgm) {
      case 'get':
        $q = $wpdb->prepare( "SELECT COUNT(*) " .
                               "FROM {$mepr_db->transactions} AS t " .
                              "WHERE t.subscription_id=%d " .
                                "AND t.status=%s",
                             $this->rec->ID,
                             MeprTransaction::$complete_str );
        return $wpdb->get_var($q);
      default:
        return true;
    }
  }

  protected function mgm_expires_at($mgm,$val='') {
    global $wpdb; 
    $mepr_db = new MeprDb();

    switch($mgm) {
      case 'get':
        $q = $wpdb->prepare( "SELECT t.expires_at " .
                               "FROM {$mepr_db->transactions} AS t " .
                              "WHERE t.subscription_id=%d " .
                                "AND t.status IN (%s,%s) " .
                                "AND ( t.expires_at = '0000-00-00 00:00:00' " .
                                      "OR ( t.expires_at <> '0000-00-00 00:00:00' " .
                                           "AND t.expires_at=( SELECT MAX(t2.expires_at) " .
                                                                 "FROM {$mepr_db->transactions} as t2 " .
                                                                "WHERE t2.subscription_id=%d " .
                                                                  "AND t2.status IN (%s,%s) " .
                                                             ") " .
                                         ") " .
                                    ") " .
                              // If there's a lifetime and an expires at, favor the lifetime
                              "ORDER BY t.expires_at " . 
                              "LIMIT 1",
                              $this->rec->ID,
                              MeprTransaction::$confirmed_str,
                              MeprTransaction::$complete_str,
                              $this->rec->ID,
                              MeprTransaction::$confirmed_str,
                              MeprTransaction::$complete_str );
        if(false===($expires_at = $wpdb->get_var($q)))
          return $this->get_expires_at();
        else
          return $expires_at;
      default:
        return true;
    }
  }

  protected function mgm_next_billing_at($mgm,$value='') {
    global $wpdb; 
    $mepr_db = new MeprDb();

    switch($mgm) {
      case 'get':
        if( $this->status == MeprSubscription::$active_str and
            !empty($this->expires_at) and
            $this->expires_at != '0000-00-00 00:00:00' and
            ( !$this->limit_cycles or
              ( $this->limit_cycles and
                $this->txn_count < $this->limit_cycles_num ) ) )
          return $this->expires_at;
        else
          return false;
      default:
        return true;
    }
  }
} //End class

