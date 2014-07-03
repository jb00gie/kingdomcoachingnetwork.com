<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprSubscriptionsController extends MeprCptController
{
  public function load_hooks()
  {
    add_action('admin_enqueue_scripts',               array($this, 'enqueue_scripts'));
    add_action('wp_ajax_mepr_subscr_num_search',      array($this, 'subscr_num_search'));
    add_action('wp_ajax_mepr_subscr_edit_status',     array($this, 'edit_subscr_status'));
    add_action('wp_ajax_mepr_delete_subscription',    array($this, 'delete_subscription'));
    add_action('wp_ajax_mepr_suspend_subscription',   array($this, 'suspend_subscription'));
    add_action('wp_ajax_mepr_resume_subscription',    array($this, 'resume_subscription'));
    add_action('wp_ajax_mepr_cancel_subscription',    array($this, 'cancel_subscription'));
    add_action('wp_ajax_mepr_subscriptions',          array($this, 'csv'));
    add_action('wp_ajax_mepr_lifetime_subscriptions', array($this, 'lifetime_csv'));
  }
  
  public function register_post_type()
  {
    register_post_type( MeprSubscription::$cpt,
                        array('labels' => array('name' => __( 'Subscriptions' , 'memberpress'),
                                                'singular_name' => __( 'Subscription' , 'memberpress'),
                                                'add_new_item' => __('Add New Subscription', 'memberpress'),
                                                'edit_item' => __('Edit Subscription', 'memberpress'),
                                                'new_item' => __('New Subscription', 'memberpress'),
                                                'view_item' => __('View Subscription', 'memberpress'),
                                                'search_items' => __('Search Subscription', 'memberpress'),
                                                'not_found' => __('No Subscription found', 'memberpress'),
                                                'not_found_in_trash' => __('No Subscription found in Trash', 'memberpress'),
                                                'parent_item_colon' => __('Parent Subscription:', 'memberpress')
                                                ),
                              'public' => false,
                              'show_ui' => false,
                              'capability_type' => 'post',
                              'hierarchical' => true,
                              'supports' => array('none')
                              )
                      );
  }
  
  public function listing()
  {
    $lifetime = (isset($_REQUEST['lifetime']) and intval($_REQUEST['lifetime'])==1);
    $sub_table = new MeprSubscriptionsTable($lifetime);
    $sub_table->prepare_items();

    require MEPR_VIEWS_PATH . '/subscriptions/list.php';
  }
  
  public function enqueue_scripts($hook)
  {
    if($hook == 'memberpress_page_memberpress-subscriptions')
    {
      $l10n = array( 'del_sub' => __('A Subscription should be cancelled (at the Gateway or here) by you, or by the Member on their Account page before being removed. Deleting an Active Subscription can cause future recurring payments not to be tracked properly. Are you sure you want to delete this Subscription?', 'memberpress'),
                     'del_sub_error' => __('The Subscription could not be deleted. Please try again later.', 'memberpress'),
                     'cancel_sub' => __('This will cancel all future payments for this subscription. Are you sure you want to cancel this Subscription?', 'memberpress'),
                     'cancel_sub_error' => __('The Subscription could not be cancelled here. Please login to your gateway\'s virtual terminal to cancel it.', 'memberpress'),
                     'cancel_sub_success' => __('The Subscription was successfully cancelled.', 'memberpress'),
                     'cancelled_text' => __('Stopped', 'memberpress'),
                     'suspend_sub' => __("This will stop all payments for this subscription until the user logs into their account and resumes.\n\nAre you sure you want to pause this Subscription?", 'memberpress'),
                     'suspend_sub_error' => __('The Subscription could not be paused here. Please login to your gateway\'s virtual terminal to pause it.', 'memberpress'),
                     'suspend_sub_success' => __('The Subscription was successfully paused.', 'memberpress'),
                     'suspend_text' => __('Paused', 'memberpress'),
                     'resume_sub' => __("This will resume payments for this subscription.\n\nAre you sure you want to resume this Subscription?", 'memberpress'),
                     'resume_sub_error' => __('The Subscription could not be resumed here. Please login to your gateway\'s virtual terminal to resume it.', 'memberpress'),
                     'resume_sub_success' => __('The Subscription was successfully resumed.', 'memberpress'),
                     'resume_text' => __('Enabled', 'memberpress')
                   );
      
      wp_enqueue_style('mepr-subscriptions-css', MEPR_CSS_URL.'/admin-subscriptions.css', array(), MEPR_VERSION);
      wp_enqueue_script('mepr-subscriptions-js', MEPR_JS_URL.'/admin_subscriptions.js', array('jquery'), MEPR_VERSION);
      wp_enqueue_script('mepr-table-controls-js', MEPR_JS_URL.'/table_controls.js', array('jquery'), MEPR_VERSION);
      wp_localize_script('mepr-subscriptions-js', 'MeprSub', $l10n);
    }
  }
  
  public function edit_subscr_status()
  {
    if( !isset($_POST['id']) || empty($_POST['id']) ||
        !isset($_POST['value']) || empty($_POST['value']) )
      die(__('Save Failed', 'memberpress'));
    
    $id = $_POST['id'];
    $value = $_POST['value'];
    
    $sub = new MeprSubscription($id);
    if( empty($sub->ID) )
      die(__('Save Failed', 'memberpress'));
    
    $sub->status = $value;
    $sub->store();
    
    echo MeprAppHelper::human_readable_status( $value, 'subscription' );
    die();
  }

  public function delete_subscription()
  {
    if(!is_super_admin())
      die(__('You do not have access.', 'memberpress'));
    
    if(!isset($_POST['id']) || empty($_POST['id']) || !is_numeric($_POST['id']))
      die(__('Could not delete subscription', 'memberpress'));
    
    $sub = new MeprSubscription($_POST['id']);
    $sub->destroy();
    die('true'); //don't localize this string
  }

  public function suspend_subscription()
  {
    if(!is_super_admin())
      die(__('You do not have access.', 'memberpress'));
    
    if(!isset($_POST['id']) || empty($_POST['id']) || !is_numeric($_POST['id']))
      die(__('Could not pause subscription', 'memberpress'));
    
    $sub = new MeprSubscription($_POST['id']);
    $sub->suspend();
    die('true'); //don't localize this string
  }

  public function resume_subscription()
  {
    if(!is_super_admin())
      die(__('You do not have access.', 'memberpress'));
    
    if(!isset($_POST['id']) || empty($_POST['id']) || !is_numeric($_POST['id']))
      die(__('Could not resume subscription', 'memberpress'));
    
    $sub = new MeprSubscription($_POST['id']);
    $sub->resume();
    die('true'); //don't localize this string
  }

  public function cancel_subscription()
  {
    if(!is_super_admin())
      die(__('You do not have access.', 'memberpress'));
    
    if(!isset($_POST['id']) || empty($_POST['id']) || !is_numeric($_POST['id']))
      die(__('Could not cancel subscription', 'memberpress'));
    
    $sub = new MeprSubscription($_POST['id']);
    $sub->cancel();
    die('true'); //don't localize this string
  }

  public function subscr_num_search()
  {
    if(!current_user_can('list_users'))
      die('-1');
    
    $s = $_GET['q']; // is this slashed already?
    
    $s = trim($s);
    if(strlen($s) < 5)
      die; // require 5 chars for matching
    
    $subs = MeprSubscription::search_by_subscr_id($s);
    require(MEPR_VIEWS_PATH.'/subscriptions/search.php');
    die;
  }

  public function csv($lifetime=false) {
    // Since we're running WP_List_Table headless we need to do this
	  $GLOBALS['hook_suffix'] = false;

    $subtab = new MeprSubscriptionsTable($lifetime);
    $subtab->prepare_items();
    $filename = ( $lifetime ? 'non-recurring-' : '' ) . 'subscriptions-'.time();
    MeprUtils::render_csv( $subtab->get_items(), $filename );
  }

  public function lifetime_csv() {
    $this->csv(true);
  }
} //End class
