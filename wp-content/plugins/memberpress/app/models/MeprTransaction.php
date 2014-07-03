<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprTransaction extends MeprBaseModel
{
  /** INSTANCE VARIABLES & METHODS **/
  function __construct($id = null)
  {
    if(!is_null($id))
      $this->rec = (object)self::get_one($id);
    else
      $this->rec = (object)array( "id"              => 0,
                                  "amount"          => 0.00,
                                  "user_id"         => null,
                                  "product_id"      => null,
                                  "coupon_id"       => null,
                                  "trans_num"       => null,
                                  "status"          => self::$pending_str,
                                  "txn_type"        => self::$payment_str,
                                  "response"        => '',
                                  "gateway"         => null,
                                  "prorated"        => null,
                                  "ip_addr"         => null,
                                  "created_at"      => null,
                                  "expires_at"      => null, // 0 = lifetime, null = default expiration for product
                                  "subscription_id" => null );
  }
  
  public function load_data($tdata)
  {
    $this->rec = (object)$tdata;
  }
  
  // Transaction Types
  public static $payment_str = "payment";
  public static $subscription_confirmation_str = "subscription_confirmation";
  
  // Statuses
  public static $pending_str   = "pending";
  public static $failed_str    = "failed";
  public static $complete_str  = "complete";
  public static $confirmed_str = "confirmed";
  public static $refunded_str  = "refunded";
  
  // Static Gateways
  public static $free_gateway_str   = "free";
  public static $manual_gateway_str = "manual";

  /** STATIC CRUD METHODS **/
  public static function create($amount, $user_id, $product_id, $txn_type, $status, $coupon_id = 0, $response = '', $trans_num = 0, $subscription_id = 0, $gateway = 'MeprPayPalGateway', $created_at = null, $expires_at = null, $prorated = null, $ip_addr = null)
  {
    $mepr_db = new MeprDb();
    
    if(is_null($created_at))
      $created_at = MeprUtils::ts_to_mysql_date(time());
    
    if(is_null($expires_at))
    {
      if($subscription_id > 0)
        $obj = new MeprSubscription($subscription_id); 
      else
        $obj = new MeprProduct($product_id); 
      
      $expires_at_ts = $obj->get_expires_at(strtotime($created_at));
      
      if(is_null($expires_at_ts))
        $expires_at = '0000-00-00 00:00:00';
      else
        $expires_at = MeprUtils::ts_to_mysql_date($expires_at_ts);
    }
    
    if(is_null($prorated))
    {
      $prd = new MeprProduct($product_id); 
      $prorated = ( $prd->is_one_time_payment() and $prd->is_prorated() );
    }
    
    if(!empty($expires_at))
      $args = compact('amount', 'user_id', 'product_id', 'txn_type', 'coupon_id', 'gateway', 'trans_num', 'subscription_id', 'status', 'response', 'created_at', 'expires_at', 'prorated', 'ip_addr');
    else // NULL value for expires_at indicates no expiration
      $args = compact('amount', 'user_id', 'product_id', 'txn_type', 'coupon_id', 'gateway', 'trans_num', 'subscription_id', 'status', 'response', 'created_at', 'prorated', 'ip_addr');
    
    return apply_filters('mepr_create_transaction', $mepr_db->create_record($mepr_db->transactions, $args, false), $args, $user_id);
  }

  public static function update($id, $amount, $user_id, $product_id, $txn_type, $status, $coupon_id = 0, $response = '', $trans_num = 0, $subscription_id = 0, $gateway = 'MeprPayPalGateway', $created_at = null, $expires_at = null, $prorated = null, $ip_addr = null)
  {
    $mepr_db = new MeprDb();

    $args = compact('amount', 'user_id', 'product_id', 'txn_type', 'coupon_id', 'gateway', 'trans_num', 'subscription_id', 'status', 'response', 'created_at', 'expires_at', 'prorated', 'ip_addr');

    return apply_filters('mepr_update_transaction', $mepr_db->update_record($mepr_db->transactions, $id, $args), $args, $user_id);
  }

  public static function update_partial($id, $args)
  {
    $mepr_db = new MeprDb();
    $mepr_db->update_record($mepr_db->transactions, $id, $args);
  }
  
  public function destroy()
  {
    $mepr_db = new MeprDb();
    $id = $this->id;
    $args = compact('id');
    $transaction = self::get_one($id);
    return apply_filters('mepr_delete_transaction', $mepr_db->delete_records($mepr_db->transactions, $args), $args);
  }
  
  /*
  function delete_by_user_id($user_id)
  {
    $mepr_db = new MeprDb();
    $args = compact('user_id');
    return apply_filters('mepr_delete_transaction', $mepr_db->delete_records($mepr_db->transactions, $args), $args);
  }
  */
  
  public static function get_one($id, $return_type = OBJECT)
  {
    $mepr_db = new MeprDb();
    $args = compact('id');
    return $mepr_db->get_one_record($mepr_db->transactions, $args, $return_type);
  }
  
  public static function get_one_by_trans_num($trans_num)
  {
    $mepr_db = new MeprDb();
    $args = compact('trans_num');
    return $mepr_db->get_one_record($mepr_db->transactions, $args);
  }
  
  public static function get_one_by_subscription_id($subscription_id)
  {
    if(is_null($subscription_id) or empty($subscription_id) or !$subscription_id)
      return false;
    
    $mepr_db = new MeprDb();
    $args = compact('subscription_id');
    return $mepr_db->get_one_record($mepr_db->transactions, $args);
  }
  
  public static function get_all_by_subscription_id($subscription_id)
  {
    if(is_null($subscription_id) or empty($subscription_id) or !$subscription_id)
      return false;
    
    $mepr_db = new MeprDb();
    $args = compact('subscription_id');
    return $mepr_db->get_records($mepr_db->transactions, $args);
  }
  
  public static function get_first_subscr_transaction($subscription_id)
  {
    global $wpdb;
    $mepr_db = new MeprDb();
    
    $query = "SELECT * FROM {$mepr_db->transactions} WHERE subscription_id=%s ORDER BY created_at LIMIT 1";
    $query = $wpdb->prepare($query, $subscription_id);
    return $wpdb->get_row($query);
  }
  
  public static function get_count()
  {
    $mepr_db = new MeprDb();
    return $mepr_db->get_count($mepr_db->transactions);
  }
  
  public static function get_count_by_user_id($user_id)
  {
    $mepr_db = new MeprDb();
    return $mepr_db->get_count($mepr_db->transactions, compact('user_id'));
  }
  
  public static function get_all($order_by = '', $limit = '')
  {
    $mepr_db = new MeprDb();
    return $mepr_db->get_records($mepr_db->transactions, array(), $order_by, $limit);
  }
  
  public static function get_all_by_user_id($user_id, $order_by = '', $limit = '', $exclude_confirmations = false)
  {
    $mepr_db = new MeprDb();
    $args = array('user_id' => $user_id);
    
    if($exclude_confirmations)
      $args['txn_type'] = self::$payment_str;
    
    return $mepr_db->get_records($mepr_db->transactions, $args, $order_by, $limit);
  }
  
  public static function get_all_complete_by_user_id( $user_id,
                                                      $order_by = '',
                                                      $limit = '',
                                                      $count = false,
                                                      $exclude_expired = false,
                                                      $include_confirmations = false )
  {
    global $wpdb;
    $mepr_db = new MeprDb();
    
    $fields = $count?'COUNT(*)':'t.*, p.post_title, m.meta_value AS access_url';
    
    if(!empty($order_by))
      $order_by = "ORDER BY {$order_by}";
    
    if(!empty($limit))
      $limit = "LIMIT {$limit}";
    
    $where = $exclude_expired?"AND (t.expires_at > '".date('c')."' OR t.expires_at = '0000-00-00 00:00:00' OR t.expires_at IS NULL) ":'';
    
    if($include_confirmations)
    {
      $where .= $wpdb->prepare( 'AND (( t.txn_type=%s AND t.status=%s ) OR ( t.txn_type=%s AND t.status=%s ))',
                                self::$payment_str,
                                self::$complete_str,
                                self::$subscription_confirmation_str,
                                self::$confirmed_str );
    }
    else
    {
      $where .= $wpdb->prepare( "AND t.txn_type = %s AND t.status = %s ",
                                self::$payment_str,
                                self::$complete_str );
    }
    
    $query = "SELECT {$fields}
                FROM {$mepr_db->transactions} AS t
                  JOIN {$wpdb->posts} AS p
                    ON t.product_id = p.ID
                  LEFT JOIN {$wpdb->postmeta} AS m
                    ON t.product_id = m.post_id AND m.meta_key = %s
                WHERE user_id = %d
              {$where}
              {$order_by}
              {$limit}";
    
    $query = $wpdb->prepare($query, MeprProduct::$access_url_str, $user_id);
    
    if($count)
      return $wpdb->get_var($query);
    else
      return $wpdb->get_results($query);
  }
  
  public static function completed_transactions_by_date_range($start_date, $end_date)
  {
    global $wpdb;
    $mepr_db = new MeprDb();
    
    $products = get_posts(array('numberposts' => -1, 'post_type' => 'memberpressproduct', 'post_status' => 'publish'));
    
    $selects = array();
    
    foreach($products as $product)
    {
      $selects[] = $wpdb->prepare("SELECT * FROM {$mepr_db->transactions} WHERE status='complete' AND created_at >= %s AND created_at <= %s", $start_date, $end_date);
    }
    
    $query = implode(" UNION ", $selects);
    
    return $wpdb->get_results($query);
  }
  
  public static function get_all_ids_by_user_id($user_id, $order_by = '', $limit = '')
  {
    global $wpdb;
    $mepr_db = new MeprDb();
    
    $query = "SELECT id FROM {$mepr_db->transactions} WHERE user_id=%d {$order_by}{$limit}";
    $query = $wpdb->prepare($query, $user_id);
    
    return $wpdb->get_col($query);
  }
  
  public static function get_all_objects_by_user_id($user_id, $order_by = '', $limit = '')
  {
    $all_records = self::get_all_by_user_id($user_id, $order_by, $limit);
    $my_objects = array();
    
    foreach($all_records as $record)
      $my_objects[] = self::get_stored_object($record->id);
    
    return $my_objects;
  }
  
  public static function get_all_objects($order_by = '', $limit = '')
  {
    $all_records = self::get_all($order_by, $limit);
    $my_objects = array();
    
    foreach ($all_records as $record)
      $my_objects[] = self::get_stored_object($record->id);
    
    return $my_objects;
  }
  
  public static function get_stored_object($id)
  {
    static $my_objects;
    
    if(!isset($my_objects))
      $my_objects = array();
    
    if(!isset($my_objects[$id]) or
       empty($my_objects[$id]) or
       !is_object($my_objects[$id]))
      $my_objects[$id] = new MeprTransaction($id);
    
    return $my_objects[$id];
  }

  public function store()
  {
    $old_txn = new self($this->id);

    if(isset($this->id) and !is_null($this->id) and (int)$this->id > 0) {
      $this->id = self::update( $this->id, $this->amount, $this->user_id,
                                $this->product_id, $this->txn_type, $this->status,
                                $this->coupon_id, $this->response, $this->trans_num,
                                $this->subscription_id, $this->gateway, $this->created_at,
                                $this->expires_at, $this->prorated, $this->ip_addr );
    }
    else {
      $this->id = self::create( $this->amount, $this->user_id, $this->product_id,
                                $this->txn_type, $this->status, $this->coupon_id,
                                $this->response, $this->trans_num, $this->subscription_id,
                                $this->gateway, $this->created_at, $this->expires_at,
                                $this->prorated, $this->ip_addr );
    }

    //This should happen after everything is done processing including the subscr txn_count
    do_action('mepr-txn-transition-status', $old_txn->status, $this->status, $this);
    do_action('mepr-txn-store', $this);
    do_action('mepr-txn-status-'.$this->status, $this);

    return $this->id;
  }

  /** This method will return an array of transactions that are or have expired.  */
  public static function get_expiring_transactions()
  {
    global $wpdb;
    $mepr_options = MeprOptions::fetch();
    $mepr_db = new MeprDb();
    $pm_ids = array();
    
    $pms = $mepr_options->integrations;
    
    //foreach($pms as $pm)
    //  if(isset($pm['recurrence_type']) and $pm['recurrence_type']=='manual')
    //    $pm_ids[] = $pm['id'];
    
    $query = "SELECT txn.* FROM {$mepr_db->transactions} AS txn " .
              "WHERE txn.status='complete' AND txn.expires_at <= %s " .
                //"AND txn.gateway IN ('" . implode("','", $pm_ids) . "') " .
                "AND txn.id NOT IN ( SELECT CAST( meta.meta_value AS UNSIGNED INTEGER ) " .
                                      "FROM {$wpdb->usermeta} AS meta " .
                                     "WHERE meta.user_id=txn.user_id " .
                                       "AND meta.meta_key='mepr_renewal' )"; 
    
    $query = $wpdb->prepare($query, MeprUtils::ts_to_mysql_date(time()));
    
    return $wpdb->get_results($query);
  }
  
  public static function list_table( $order_by = '',
                                     $order = '',
                                     $paged = '',
                                     $search = '',
                                     $perpage = 10,
                                     $params = null )
  {
    global $wpdb;
    $mepr_db = new MeprDb();
    if(is_null($params)) { $params=$_GET; }

    $args = array();
    
    $mepr_options = MeprOptions::fetch();
    $pmt_methods = $mepr_options->payment_methods();
    
    if(!empty($pmt_methods))
    {
      $pmt_method = '(SELECT CASE tr.gateway';

      foreach($pmt_methods as $method)
        $pmt_method .= $wpdb->prepare(" WHEN %s THEN %s", $method->id, "{$method->label} ({$method->name})"); 

      $pmt_method .= $wpdb->prepare(" ELSE %s END)", __('Unknown', 'memberpress'));
    }
    else
      $pmt_method = 'tr.gateway';
    
    $cols = array('id' => 'tr.id',
                  'created_at' => 'tr.created_at',
                  'expires_at' => 'tr.expires_at',
                  'ip_addr' => 'tr.ip_addr',
                  'user_login' => 'm.user_login',
                  'user_email' => 'm.user_email',
                  'fname' => "(SELECT um_fname.meta_value FROM {$wpdb->usermeta} AS um_fname WHERE um_fname.user_id = m.ID AND um_fname.meta_key = 'first_name' LIMIT 1)",
                  'lname' => "(SELECT um_lname.meta_value FROM {$wpdb->usermeta} AS um_lname WHERE um_lname.user_id = m.ID AND um_lname.meta_key = 'last_name' LIMIT 1)",
                  'user_id' => 'm.ID',
                  'product_id' => 'tr.product_id',
                  'product_name' => 'p.post_title',
                  'gateway' => $pmt_method,
                  'subscr_id' => $wpdb->prepare('(SELECT CASE tr.subscription_id WHEN tr.subscription_id IS NULL OR tr.subscription_id=0 THEN %s ELSE ( SELECT mepr_subscr_id_pm.meta_value FROM '.$wpdb->postmeta.' AS mepr_subscr_id_pm WHERE mepr_subscr_id_pm.post_id=tr.subscription_id AND mepr_subscr_id_pm.meta_key=%s LIMIT 1 ) END)', __('None','memberpress'), MeprSubscription::$subscr_id_str),
                  'sub_id' => 'tr.subscription_id',
                  'trans_num' => 'tr.trans_num',
                  'amount' => 'tr.amount',
                  'status' => 'tr.status'
                  );
    
    if(isset($params['month']) && is_numeric($params['month']))
      $args[] = $wpdb->prepare("MONTH(tr.created_at) = %s",$params['month']);
    
    if(isset($params['day']) && is_numeric($params['day']))
      $args[] = $wpdb->prepare("DAY(tr.created_at) = %s",$params['day']);
    
    if(isset($params['year']) && is_numeric($params['year']))
      $args[] = $wpdb->prepare("YEAR(tr.created_at) = %s",$params['year']);
    
    if(isset($params['product']) && $params['product'] != 'all' && is_numeric($params['product']))
      $args[] = $wpdb->prepare("tr.product_id = %d",$params['product']);
    
    if(isset($params['subscription']) && is_numeric($params['subscription']))
      $args[] = $wpdb->prepare("tr.subscription_id = %d",$params['subscription']);
    
    if(isset($params['transaction']) && is_numeric($params['transaction']))
      $args[] = $wpdb->prepare("tr.id = %d",$params['transaction']);
    
    if(isset($params['member']) && !empty($params['member']))
      $args[] = $wpdb->prepare("m.user_login = %s",$params['member']);
    
    // Don't include any subscription confirmation transactions in the list table
    if(!isset($params['include-confirmations'])) {
      $args[] = $wpdb->prepare("tr.txn_type = %s", self::$payment_str);
      $args[] = $wpdb->prepare("tr.status <> %s", self::$confirmed_str);
    }

    if(isset($params['statuses'])) {
      $qry = array();
      foreach($params['statuses'] as $st)
        $qry[] = $wpdb->prepare('tr.status = %s', $st);
      $args[] = '('.implode(' OR ',$qry).')';
    }
    
    $joins = array( "LEFT JOIN {$wpdb->users} AS m ON tr.user_id = m.ID",
                    "LEFT JOIN {$wpdb->posts} AS p ON tr.product_id = p.ID"
                  );
    
    return MeprDb::list_table($cols, "{$mepr_db->transactions} AS tr", $joins, $args, $order_by, $order, $paged, $search, $perpage);
  }
  
  //Sets product ID to 0 if for some reason a product is deleted
  public static function nullify_product_id_on_delete($id)
  {
    global $wpdb, $post_type;
    $mepr_db = new MeprDb();
    
    $q = "UPDATE {$mepr_db->transactions}
            SET product_id = 0
            WHERE product_id = %d";
    
    if($post_type == MeprProduct::$cpt)
      $wpdb->query($wpdb->prepare($q, $id));
  }
  
  //Sets user id to 0 if for some reason a user is deleted
  public static function nullify_user_id_on_delete($id)
  {
    global $wpdb;
    $mepr_db = new MeprDb();
    
    $q = "UPDATE {$mepr_db->transactions}
            SET user_id = 0
            WHERE user_id = %d";
    
    $wpdb->query($wpdb->prepare($q, $id));
  }

  public static function map_subscr_status($status) {
    switch($status) {
      case MeprSubscription::$pending_str:
        return self::$pending_str;
      case MeprSubscription::$active_str:
        return array( self::$complete_str, self::$confirmed_str );
      case MeprSubscription::$expired_str:
      case MeprSubscription::$suspended_str:
      case MeprSubscription::$cancelled_str:
        return false; // These don't have an equivalent
    }
  }
  
  public function is_expired($offset = 0)
  {
    $todays_ts = time() + $offset; // use the offset to check when a txn will expire
    $expires_ts = strtotime($this->expires_at);
    return ($this->status == 'complete' and $expires_ts < $todays_ts);
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
  
  public function user()
  {
    static $usr;
    
    if(!isset($usr) or !($usr instanceof MeprUser) or $usr->ID != $this->user_id)
      $usr = new MeprUser($this->user_id);
    
    return $usr;
  }
  
  public function subscription()
  {
    if(!isset($this->subscription_id) or empty($this->subscription_id))
      return false;
    
    static $sub;
    
    if(!isset($sub) or !($sub instanceof MeprSubscription) or $sub->ID != $this->subscription_id)
      $sub = new MeprSubscription($this->subscription_id);
    
    //For some reason when the free gateway is invoked a subscription is temporarily created
    //then stored with the txn, then deleted, this causes issues so we need to check here
    //that the $sub actually still exists
    if(!$sub->ID)
      return false;
    
    return $sub;
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
  
  public function payment_method()
  {
    $mepr_options = MeprOptions::fetch();
    return $mepr_options->payment_method($this->gateway);
  }
  
  // Where the magic happens when creating a free transaction ... this is
  // usually called when the price of the product has been set to zero.
  public static function create_free_transaction($txn)
  {
    $mepr_options = MeprOptions::fetch();
    $mepr_blogname = get_option('blogname');
    
    // Just short circuit if the transaction has already completed
    if($txn->status == self::$complete_str)
      return;
    
    $product = new MeprProduct($txn->product_id);
    
    //Expires at is not more difficult to calculate with our new product terms
    $expires_at = $product->get_expires_at(strtotime($txn->created_at));
    
    if(is_null($expires_at))
      $expires_at = '0000-00-00 00:00:00';
    else
      $expires_at = MeprUtils::ts_to_mysql_date($expires_at);
    
    $user = new MeprUser($txn->user_id);
    $invoice = $txn->id . '-' . time();
    $txn->trans_num  = uniqid();
    $txn->status     = self::$complete_str;
    $txn->gateway    = self::$free_gateway_str;
    $txn->expires_at = $expires_at;
    $txn->store();
    
    // No such thing as a free subscription in MemberPress
    // So let's clean up this mess right now
    if(!empty($txn->subscription_id) and is_integer($txn->subscription_id))
    {
      $sub = new MeprSubscription($txn->subscription_id);
      
      $txn->subscription_id = 0;
      $txn->store(); //Store txn here, otherwise it will get deleted during $sub->destroy()
      
      $sub->destroy();
    }

    $params = MeprTransactionsHelper::get_email_params($txn);  
    $usr = $txn->user();

    if( !$usr->signup_notice_sent ) {
      try {
        $uemail = MeprEmailFactory::fetch('MeprUserWelcomeEmail');
        $uemail->to = $usr->formatted_email();
        $uemail->send_if_enabled($params);

        $aemail = MeprEmailFactory::fetch('MeprAdminSignupEmail');
        $aemail->send_if_enabled($params); 
      }
      catch( Exception $e ) {
        // Fail silently for now
      }

      $usr->signup_notice_sent = true;
      $usr->store();
    }

    try {
      $uemail = MeprEmailFactory::fetch( 'MeprUserProductWelcomeEmail', 'MeprBaseProductEmail',
                                         array(array('product_id'=>$txn->product_id)));
      $uemail->to = $usr->formatted_email();
      $uemail->send_if_enabled($params);
    }
    catch( Exception $e ) {
      // Fail silently for now
    }

    MeprUtils::wp_redirect($mepr_options->thankyou_page_url("trans_num={$txn->trans_num}"));
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
  
  public function is_one_time_payment()
  {
    $prd = $this->product();
    
    return ($prd->is_one_time_payment() or !$this->subscription());
  }
  
  /** Used by one-time payments **/
  public function maybe_cancel_old_sub()
  {
    $mepr_options = MeprOptions::fetch();
    
    if( $this->is_upgrade_or_downgrade() and
        $this->is_one_time_payment() and
        $mepr_options->pro_rated_upgrades )
    {
      $usr = $this->user();
      $grp = $this->group();
      
      if($old_sub = $usr->subscription_in_group($grp->ID))
      {
        $old_sub->expire_txns(); //Expire associated transactions for the old subscription
        $pm = $old_sub->payment_method();
        $_REQUEST['silent']=true; // Don't want to send cancellation notices
        $pm->process_cancel_subscription($old_sub->ID);
      }
      else if( $old_lifetime_txn = $usr->lifetime_subscription_in_group($grp->ID) and
               $old_lifetime_txn->id != $this->id ) {
        $old_lifetime_txn->expires_at = MeprUtils::ts_to_mysql_date(time()-MeprUtils::days(1));
        $old_lifetime_txn->store();
      }
    }
  }

  /** Convenience method to determine what we can do
    * with the gateway associated with the transaction
    */
  public function can($cap) {
    // if the status isn't complete then the refund can't happen
    if( $cap=='process-refunds' and
        $this->status!=MeprTransaction::$complete_str )
    {
      return false;
    }

    $pm = $this->payment_method();
    if(!($pm instanceof MeprBaseRealGateway)) { return false; }

    if( $cap=='process-refunds' and $pm instanceof MeprAuthorizeGateway )
      return ( $pm->can($cap) and
               !empty($this->response) and
               $res = json_decode($this->response) and
               isset($res->authorization_code) and
               ( ( $sub = $this->subscription() and 
                   !empty($sub->cc_last4) and
                   !empty($sub->cc_exp_month) and
                   !empty($sub->cc_exp_year) ) or
                 ( !empty($res->cc_last4) and
                   !empty($res->cc_exp_month) and
                   !empty($res->cc_exp_year) ) ) );

    return $pm->can($cap);
  }

  public function refund() {
    if($this->can('process-refunds')) {
      $pm = $this->payment_method();
      return $pm->process_refund($this);
    }

    return false;
  }
} //End class
