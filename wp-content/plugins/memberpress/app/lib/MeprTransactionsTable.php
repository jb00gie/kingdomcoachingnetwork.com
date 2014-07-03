<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

if(!class_exists('WP_List_Table'))
  require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');

class MeprTransactionsTable extends WP_List_Table
{
  public function __construct()
  {
    parent::__construct(array('singular'=> 'wp_list_mepr_transaction', //Singular label
                              'plural' => 'wp_list_mepr_transactions', //plural label, also this will be one of the table css class
                              'ajax'  => false //We won't support Ajax for this table
                              )
                        );
  }
  
  public function extra_tablenav($which)
  {
    if($which == "top") {
      require MEPR_VIEWS_PATH . "/shared/table-controls.php";
    }

    if($which == "bottom") {
      $action = 'mepr_transactions';
      require MEPR_VIEWS_PATH . "/shared/table-footer.php";
    }
  }
  
  public function get_columns()
  {
    return $columns= array( 'col_id' => __('Id', 'memberpress'),
                            'col_trans_num' => __('Txn Num', 'memberpress'),
                            'col_subscr_id' => __('Subscr Num', 'memberpress'),
                            'col_status' => __('Status', 'memberpress'),
                            'col_product' => __('Product', 'memberpress'),
                            'col_total_amount' => __('Amount', 'memberpress'),
                            'col_propername' => __('Name', 'memberpress'),
                            'col_user_login' => __('User', 'memberpress'),
                            'col_payment_system' => __('Pmt Method', 'memberpress'),
                            'col_created_at' => __('Created On', 'memberpress'),
                            'col_expires_at' => __('Expires On', 'memberpress') );
                            
  }
  
  public function get_sortable_columns()
  {
    return $sortable= array(
      'col_id' => array('ID', true),
      'col_trans_num' => array('trans_num', true),
      'col_subscr_id' => array('subscr_id', true),
      'col_product' => array('product_name', true),
      'col_total_amount' => array('amount', true),
      'col_propername' => array('lname', true),
      'col_user_login' => array('user_login', true),
      'col_status' => array('status', true),
      'col_payment_system' => array('gateway', true),
      'col_created_at' => array('created_at', true),
      'col_expires_at' => array('expires_at', true)
    );
  }
  
  public function prepare_items()
  {
    $orderby = !empty($_GET["orderby"]) ? esc_sql($_GET["orderby"]) : 'created_at';
    $order   = !empty($_GET["order"])   ? esc_sql($_GET["order"])   : 'DESC';
    $paged   = !empty($_GET["paged"])   ? esc_sql($_GET["paged"])   : 1;
    $perpage = !empty($_GET["perpage"]) ? esc_sql($_GET["perpage"]) : 10;
    $search  = !empty($_GET["search"])  ? esc_sql($_GET["search"])  : '';
    
    $list_table = MeprTransaction::list_table($orderby, $order, $paged, $search, $perpage);
    $totalitems = $list_table['count'];
    
    //How many pages do we have in total?
    $totalpages = ceil($totalitems/$perpage);
    
    /* -- Register the pagination -- */
    $this->set_pagination_args(array("total_items" => $totalitems,
                                     "total_pages" => $totalpages,
                                     "per_page" => $perpage));
    
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
    //Get the records registered in the prepare_items method
    $records = $this->items;
    
    //Get the columns registered in the get_columns and get_sortable_columns methods
    list( $columns, $hidden ) = $this->get_column_info();
    
    require MEPR_VIEWS_PATH.'/transactions/row.php';
  }

  public function get_items() {
    return $this->items;
  }
}
