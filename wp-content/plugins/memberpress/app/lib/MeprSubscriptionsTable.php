<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

if(!class_exists('WP_List_Table'))
  require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');

class MeprSubscriptionsTable extends WP_List_Table
{
  public $lifetime;
  public $periodic_count;
  public $lifetime_count;

  public function __construct($lifetime=false)
  {
    $this->lifetime = $lifetime;

    if($lifetime)
      $label = 'wp_list_mepr_lifetime_subscription';
    else
      $label = 'wp_list_mepr_subscription';

    parent::__construct(array('singular'=> $label, //Singular label
                              'plural' => "{$label}s", //plural label, also this well be one of the table css class
                              'ajax'  => false //We won't support Ajax for this table
                        ));
  }

  public function extra_tablenav($which)
  {
    if($which == "top") {
      $member = (isset($_GET['member']) && !empty($_GET['member']))?'&member='.stripslashes($_GET['member']):'';
      $search = (isset($_GET['search']) && !empty($_GET['search']))?'&search='.stripslashes($_GET['search']):'';
      $perpage = (isset($_GET['perpage']) && !empty($_GET['perpage']))?'&perpage='.stripslashes($_GET['perpage']):'';
      
      require MEPR_VIEWS_PATH . "/subscriptions/tabs.php"; 
      require MEPR_VIEWS_PATH . "/shared/table-controls.php";
    }

    if($which == "bottom") {
      if($this->lifetime) {
        $action = "mepr_lifetime_subscriptions";
      }
      else {
        $action = "mepr_subscriptions";
      }

      require MEPR_VIEWS_PATH . "/shared/table-footer.php";
    }
  }
  
  public function get_columns()
  {
    $cols = array( 'col_id' => __('Id', 'memberpress'),
                   'col_subscr_id' => __('Subscr Num', 'memberpress'),
                   'col_active' => __('Active', 'memberpress'),
                   'col_status' => __('Auto Rebill', 'memberpress'),
                   'col_product' => __('Product', 'memberpress'),
                   'col_product_meta' => __('Terms', 'memberpress'),
                   'col_propername' => __('Name', 'memberpress'),
                   'col_member' => __('User', 'memberpress'),
                   'col_gateway' => __('Pmt Method', 'memberpress'),
                   'col_txn_count' => __('Txns', 'memberpress'),
                   'col_ip_addr' => __('IP', 'memberpress'),
                   'col_created_at' => __('Created On', 'memberpress'),
                   'col_expires_at' => __('Expires On', 'memberpress') );
                   

    if($this->lifetime) {
      unset($cols['col_status']);
      unset($cols['col_delete_sub']);
      unset($cols['col_txn_count']);
      $cols['col_subscr_id'] = __('Txn Num', 'memberpress');
      $cols['col_product_meta'] = __('Price', 'memberpress');
    }

    return $cols; 
  }

  public function get_sortable_columns()
  {
    $cols = array( 'col_created_at' => array('created_at', true),
                   'col_id' => array('ID', true),
                   'col_member' => array('member', true),
                   'col_propername' => array('lname', true),
                   'col_product' => array('product_name', true),
                   'col_gateway' => array('gateway', true),
                   'col_subscr_id' => array('subscr_id', true),
                   'col_txn_count' => array('txn_count', true),
                   'col_expires_at' => array('expires_at', true),
                   'col_ip_addr' => array('ip_addr', true),
                   'col_status' => array('status', true),
                   'col_active' => array('active', true)
                 );

    if($this->lifetime) {
      unset($cols['col_txn_count']);
      unset($cols['col_status']);
    }

    return $cols;
  }

  public function prepare_items()
  {
    $orderby = !empty($_GET["orderby"]) ? esc_sql($_GET["orderby"]) : 'ID';
    $order   = !empty($_GET["order"])   ? esc_sql($_GET["order"])   : 'DESC';
    $paged   = !empty($_GET["paged"])   ? esc_sql($_GET["paged"])   : 1;
    $perpage = !empty($_GET["perpage"]) ? esc_sql($_GET["perpage"]) : 10;
    $search  = !empty($_GET["search"])  ? esc_sql($_GET["search"])  : '';

    $lifetime_table = MeprSubscription::lifetime_subscr_table($orderby, $order, $paged, $search, $perpage, (!$this->lifetime));
    $periodic_table = MeprSubscription::subscr_table($orderby, $order, $paged, $search, $perpage, ($this->lifetime));

    $list_table = $this->lifetime ? $lifetime_table : $periodic_table;

    $this->periodic_count = $periodic_table['count'];
    $this->lifetime_count = $lifetime_table['count'];

    $totalitems = $list_table['count'];

    //How many pages do we have in total?
    $totalpages = ceil($totalitems/$perpage);

    /* -- Register the pagination -- */
    $this->set_pagination_args( array( "total_items" => $totalitems,
                                       "total_pages" => $totalpages,
                                       "per_page" => $perpage ) );

    /* -- Register the Columns -- */
    $columns = $this->get_columns();
    $hidden = array();
    $sortable = $this->get_sortable_columns();
    $this->_column_headers = array($columns, $hidden, $sortable);

    /* -- Fetch the items -- */
    $this->items = $list_table['results'];
  }

  public function display_rows()
  {
    $mepr_options = MeprOptions::fetch();
    
    //Get the records registered in the prepare_items method
    $records = $this->items;
    
    //Get the columns registered in the get_columns and get_sortable_columns methods
    list($columns, $hidden) = $this->get_column_info();
    
    require MEPR_VIEWS_PATH.'/subscriptions/row.php';
  }

  public function get_items() {
    return $this->items;
  }
} //End class

