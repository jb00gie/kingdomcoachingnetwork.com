<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprTransactionsController extends MeprBaseController
{
  public function load_hooks()
  {
    add_action('wp_ajax_edit_status',             array($this, 'edit_trans_status'));
    add_action('wp_ajax_delete_transaction',      array($this, 'delete_transaction'));
    add_action('wp_ajax_refund_transaction',      array($this, 'refund_transaction'));
    add_action('wp_ajax_resend_txn_email',        array($this, 'resend_txn_email'));
    add_action('wp_ajax_mepr_default_expiration', array($this, 'default_expiration'));
    add_action('admin_enqueue_scripts',           array($this, 'enqueue_scripts'));
    add_action('wp_ajax_mepr_transactions',       array($this, 'csv'));
  }
  
  public function listing()
  {
    $action = (isset($_REQUEST['action']) and !empty($_REQUEST['action']))?$_REQUEST['action']:false;
    if($action == 'new')
      $this->new_trans();
    else if($action == 'edit')
      $this->edit_trans();
    else
      $this->display_list();
  }
  
  public function new_trans($errors = array())
  {
    $mepr_options = MeprOptions::fetch();
    $txn = new MeprTransaction();
    $user_login = '';
    $subscr_num = '';
    
    if(empty($errors) && strtolower($_SERVER['REQUEST_METHOD']) == 'post')
      $this->create_trans($txn);
    else
    {
      if(isset($_GET['subscription']) and is_numeric($_GET['subscription']))
      {
        $sub = new MeprSubscription($_GET['subscription']);
        $usr = $sub->user();
        $prd = $sub->product();
        $user_login = $usr->user_login;
        $subscr_num = $sub->subscr_id;
        $txn->product_id = $sub->product_id;
      }
      
      require(MEPR_VIEWS_PATH.'/transactions/new_trans.php');
    }
  }
  
  public function edit_trans()
  {
    $mepr_options = MeprOptions::fetch();
    
    if(isset($_REQUEST['id']))
    {
      $txn = new MeprTransaction($_REQUEST['id']);
      $usr = $txn->user();
      $user_login = $usr->user_login;
      $subscr_num = '';
      
      if($sub = $txn->subscription())
        $subscr_num = $sub->subscr_id;
      
      if(strtolower($_SERVER['REQUEST_METHOD']) == 'post')
        $this->update_trans($txn);
      else
        require(MEPR_VIEWS_PATH.'/transactions/edit_trans.php');
    }
    else
      $this->new_trans();
  }
  
  public function create_trans($txn)
  {
    $mepr_options = MeprOptions::fetch();
    if(!isset($_POST['_wpnonce']) or !wp_verify_nonce($_POST['_wpnonce'],'memberpress-trans'))
      wp_die(__("Why you creepin'?", 'memberpress'));
    
    $errors = $this->validate_trans();
    
    $usr = new MeprUser();
    $usr->load_user_data_by_login($_POST['user_login']);
    $user_login = $usr->user_login;
    $subscr_id = '';
    
    $txn->trans_num  = (isset($_POST['trans_num']) && !empty($_POST['trans_num']))?stripslashes($_POST['trans_num']):uniqid();
    $txn->user_id    = $usr->ID;
    $txn->product_id = $_POST['product_id'];
    $txn->amount     = $_POST['amount'];
    $txn->status     = $_POST['status'];
    $txn->gateway    = $_POST['gateway'];
    
    if(isset($_POST['subscr_num']) and !empty($_POST['subscr_num']))
    {
      if($sub = MeprSubscription::get_one_by_subscr_id($_POST['subscr_num']))
      {
        $txn->subscription_id = $sub->ID;
        $subscr_num = $sub->subscr_id;
        $sub->store();
      }
    }
    
    if(isset($_POST['created_at']) and ($_POST['created_at'] == '' or is_null($_POST['created_at'])))
      $txn->created_at = MeprUtils::ts_to_mysql_date(time()); // This crap is due to mysql craziness
    else
      $txn->created_at = MeprUtils::ts_to_mysql_date(strtotime($_POST['created_at']));
    
    if(isset($_POST['expires_at']) and ($_POST['expires_at'] == '' or is_null($_POST['expires_at'])))
      $txn->expires_at = '0000-00-00 00:00:00'; // This crap is due to mysql craziness
    else
      $txn->expires_at = MeprUtils::ts_to_mysql_date(strtotime($_POST['expires_at']));
    
    // Only save to the database if there aren't any errors
    if(empty($errors))
    {
      $txn->response = json_encode($_POST);
      $txn->store();
      
      $message = __("A transaction was created successfully.", 'memberpress');
      $_REQUEST['action'] = 'edit';
      $txn = new MeprTransaction($txn->id); // refresh the txn obj to get all generated fields
      require(MEPR_VIEWS_PATH.'/transactions/edit_trans.php');
    }
    else
      $this->new_trans($errors);
  }
  
  public function update_trans($txn)
  {
    $mepr_options = MeprOptions::fetch();
    if(!isset($_POST['_wpnonce']) or !wp_verify_nonce($_POST['_wpnonce'],'memberpress-trans'))
      wp_die(__("Why you creepin'?", 'memberpress'));
    
    $errors = $this->validate_trans();
    
    $usr = new MeprUser();
    $usr->load_user_data_by_login($_POST['user_login']);
    $user_login = $usr->user_login;
    $subscr_num = '';

    $txn->trans_num  = stripslashes($_POST['trans_num']);
    $txn->user_id    = $usr->ID;
    $txn->product_id = $_POST['product_id'];
    $txn->amount     = $_POST['amount'];
    $txn->status     = $_POST['status'];
    $txn->gateway    = $_POST['gateway'];
    
    if(isset($_POST['subscr_num']) and !empty($_POST['subscr_num']))
    {
      if($sub = MeprSubscription::get_one_by_subscr_id($_POST['subscr_num']))
      {
        $txn->subscription_id = $sub->ID;
        $subscr_num = $sub->subscr_id;
        $sub->store();
      }
    }
    
    if(isset($_POST['created_at']) and ($_POST['created_at'] == '' or is_null($_POST['created_at'])))
      $txn->created_at = MeprUtils::ts_to_mysql_date(time()); // This crap is due to mysql craziness
    else
      $txn->created_at = MeprUtils::ts_to_mysql_date(strtotime($_POST['created_at']));
    
    if(isset($_POST['expires_at']) and ($_POST['expires_at'] == '' or is_null($_POST['expires_at'])))
      $txn->expires_at = '0000-00-00 00:00:00'; // This crap is due to mysql craziness
    else
      $txn->expires_at = MeprUtils::ts_to_mysql_date(strtotime($_POST['expires_at']));
    
    // Only save to the database if there aren't any errors
    if(empty($errors))
    {
      $txn->store();
      $message = __("The transaction was successfully updated.", 'memberpress');
    }
    
    require(MEPR_VIEWS_PATH.'/transactions/edit_trans.php');
  }
  
  public function validate_trans()
  {
    $errors = array();
    
    if(!isset($_POST['user_login']) or empty($_POST['user_login']))
      $errors[] = __("The username must be set.", 'memberpress');
    else
    {
      $usr = new MeprUser();
      $usr->load_user_data_by_login($_POST['user_login']);
      
      if($usr->ID == 0)
        $errors[] = __("You must enter a valid username", 'memberpress');
    }
    
    // Simple validation here
    if(!isset($_POST['amount']) or empty($_POST['amount'])) 
      $errors[] = __("The amount must be set.", 'memberpress');
    
    if(!is_numeric($_POST['amount'])) 
      $errors[] = __("The amount must be a number.", 'memberpress');
    
    if(isset($_POST['subscr_num']) and !empty($_POST['subscr_num']))
    {
      if($sub = MeprSubscription::get_one_by_subscr_id($_POST['subscr_num']))
      {
        if($sub->product_id != $_POST['product_id'])
        {
          $prd = new MeprProduct($_POST['product_id']);
          $sub_prd = $sub->product();
          $errors[] = sprintf( __( "This is not a subscription for product '%s' but for '%s'" , 'memberpress'), $prd->post_title, $sub_prd->post_title );
        }
        
        $usr = new MeprUser();
        $usr->load_user_data_by_login($_POST['user_login']);
        $sub_usr = $sub->user();
        
        if($usr->ID != $sub_usr->ID)
        {
          $errors[] = sprintf( __( "This is not a subscription for user '%s' but for '%s'" , 'memberpress'), $usr->user_login, $sub_usr->user_login );
        }
        
        /** don't enforce this for now */
        /*
        if($sub->gateway != $_POST['gateway']) {
          if( $sub->gateway == MeprTransaction::$free_gateway_str or
              $sub->gateway == MeprTransaction::$manual_gateway_str ) {
            $sub_gateway = $sub->gateway;
          }
          else {
            $pm = $sub->payment_method();
            $sub_gateway = sprintf( __( '%s (%s)' ), $pm->label, $pm->name );
          }

          $errors[] = sprintf( __( "This subscription is using a different payment gateway: %s" ), $sub_gateway );
        }
        */
      }
      else
        $errors[] = __("This subscription was not found.", 'memberpress');
    }
    
    if(empty($_POST['trans_num']) || preg_match("#[^a-zA-z0-9_\-]#", $_POST['trans_num']))
      $errors[] = __("The Transaction Number is required, and must contain only letters, numbers, underscores and hyphens.", 'memberpress');
    
    return $errors;
  }
  
  public function enqueue_scripts($hook)
  {
    global $wp_scripts;
    $ui = $wp_scripts->query('jquery-ui-core');
    $url = "//ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.css";
    
    if($hook == 'memberpress_page_memberpress-trans' or $hook == 'memberpress_page_memberpress-new-trans')
    {
      $l10n = array( 'del_txn' => __('Deleting Transactions could cause the associated member to lose access to protected content. Are you sure you want to delete this Transaction?', 'memberpress'),
                     'del_txn_error' => __('The Transaction could not be deleted. Please try again later.', 'memberpress'),
                     'refund_txn' => __('This will refund the transaction at the gateway level. This action is not reversable. Are you sure you want to refund this Transaction?', 'memberpress'),
                     'refunded_text' => __('refunded','memberpress'),
                     'refund_txn_success' => __('Your transaction was successfully refunded.','memberpress'),
                     'refund_txn_error' => __('The Transaction could not be refunded. Please issue the refund by logging into your gateway\'s virtual terminal','memberpress')
                   );
      
      wp_enqueue_style('mepr-jquery-ui-smoothness', $url);
      wp_enqueue_script('mepr-table-controls-js', MEPR_JS_URL.'/table_controls.js', array('jquery'), MEPR_VERSION);
      wp_enqueue_script('mepr-date-picker-js', MEPR_JS_URL.'/date_picker.js', array('jquery-ui-datepicker'), MEPR_VERSION);
      wp_enqueue_script('mepr-transactions-js', MEPR_JS_URL.'/admin_transactions.js', array('jquery','suggest','mepr-date-picker-js'), MEPR_VERSION);
      wp_enqueue_style('mepr-transactions-css', MEPR_CSS_URL.'/admin-transactions.css', array(), MEPR_VERSION);
      wp_localize_script('mepr-transactions-js', 'MeprTxn', $l10n);
    }
  }
  
  public function edit_trans_status()
  {
    global $wpdb;
    
    if(!is_super_admin())
      die(__('You do not have access.', 'memberpress'));
    
    if(!isset($_POST['id']) || empty($_POST['id']) ||
       !isset($_POST['value']) || empty($_POST['value']))
      die(__('Save Failed', 'memberpress'));
    
    $id = $_POST['id'];
    $value = $_POST['value'];
    $tdata = MeprTransaction::get_one($id, ARRAY_A);
    
    if(!empty($tdata))
    {
      $txn = new MeprTransaction();
      $txn->load_data($tdata);
      $txn->status = esc_sql($value); //escape the input this way since $wpdb->escape() is depracated
      $txn->store();
      die($txn->status);
    }
    else
      die(__('Save Failed', 'memberpress'));
  }
  
  public function refund_transaction()
  {
    if(!is_super_admin())
      die(__('You do not have access.', 'memberpress'));

    if(!isset($_POST['id']) || empty($_POST['id']) || !is_numeric($_POST['id']))
      die(__('Could not refund transaction', 'memberpress'));

    $txn = new MeprTransaction($_POST['id']);

    try {
      $txn->refund();
    }
    catch( Exception $e ) {
      die($e->getMessage());
    }

    die('true'); //don't localize this string
  }
  
  public function delete_transaction()
  {
    if(!is_super_admin())
      die(__('You do not have access.', 'memberpress'));
    
    if(!isset($_POST['id']) || empty($_POST['id']) || !is_numeric($_POST['id']))
      die(__('Could not delete transaction', 'memberpress'));
    
    $txn = new MeprTransaction($_POST['id']);
    $txn->destroy();
    die('true'); //don't localize this string
  }
  
  public function display_list()
  {
    $list_table = new MeprTransactionsTable();
    $list_table->prepare_items();
    
    require MEPR_VIEWS_PATH.'/transactions/list.php';
  }
  
  public function resend_txn_email()
  {
    $mepr_options = MeprOptions::fetch();
    
    if(!is_super_admin())
      die(__('You do not have access.', 'memberpress'));
    
    if(!isset($_POST['id']) || empty($_POST['id']) || !is_numeric($_POST['id']))
      die(__('Could not send email. Please try again later.', 'memberpress'));
    
    $txn = new MeprTransaction($_POST['id']);

    $params = MeprTransactionsHelper::get_email_params($txn);  
    $usr = $txn->user();

    try {
      $uemail = MeprEmailFactory::fetch('MeprUserReceiptEmail');
      $uemail->to = $usr->formatted_email();
      $uemail->send($params);
      die(__('Email sent', 'memberpress'));
    }
    catch( Exception $e ) {
      die(__('There was an issue sending the email', 'memberpress'));
    }
  }

  public function default_expiration() {
    if( isset($_REQUEST['product_id']) and
        isset($_REQUEST['created_at']) and
        $prd = MeprProduct::get_one($_REQUEST['product_id']) and
        !$prd->is_one_time_payment() and
        ( preg_match('/\d\d\d\d-\d\d-\d\d/', $_REQUEST['created_at']) or
          preg_match('/\d\d\d\d-\d\d-\d\d \d\d-\d\d-\d\d/', $_REQUEST['created_at']) or
          empty($_REQUEST['created_at']) ) )
    {
      $expires_at_ts = $prd->get_expires_at(strtotime($_REQUEST['created_at']));
      echo date('Y-m-d', (int)$expires_at_ts);
      die;
    }
  
    die; 
  }

  public function csv() {
    // Since we're running WP_List_Table headless we need to do this
	  $GLOBALS['hook_suffix'] = false;

    $txntab = new MeprTransactionsTable();
    $txntab->prepare_items();
    $filename = 'transactions-'.time();
    MeprUtils::render_csv( $txntab->get_items(), $filename );
  }
} //End class

