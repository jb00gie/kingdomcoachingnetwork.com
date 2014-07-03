<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprOptionsController extends MeprBaseController
{
  public function load_hooks()
  {  
    add_action('wp_ajax_mepr_gateway_form', 'MeprOptionsController::gateway_form');
    add_action('admin_enqueue_scripts', 'MeprOptionsController::enqueue_scripts');
    add_action('admin_notices', 'MeprOptionsController::maybe_configure_options');
  }
  
  public static function maybe_configure_options()
  {
    $mepr_options = MeprOptions::fetch();
    
    if(!$mepr_options->setup_complete and
        (!isset($_REQUEST['page']) or
          $_REQUEST['page']!='memberpress-options'))
      require(MEPR_VIEWS_PATH.'/shared/must_configure.php');
  }
  
  public static function route()
  {
    $action = (isset($_REQUEST['action'])?$_REQUEST['action']:'');
    
    if($action == 'process-form')
      return self::process_form();
    else if($action == 'queue' and isset($_REQUEST['_wpnonce']) and
            wp_verify_nonce($_REQUEST['_wpnonce'],
                            'MeprUpdateController::manually_queue_update'))
      MeprUpdateController::manually_queue_update();
    else
      return self::display_form();
  }
  
  public static function display_form()
  {
    $mepr_options = MeprOptions::fetch();
    
    if(MeprUtils::is_logged_in_and_an_admin())
      require(MEPR_VIEWS_PATH.'/options/form.php');
  }
  
  public static function process_form()
  {
    $mepr_options = MeprOptions::fetch();
    
    if(MeprUtils::is_logged_in_and_an_admin())
    {
      $errors = array();
      
      $errors = apply_filters('mepr-validate-options', $mepr_options->validate($_POST, $errors));
      
      $mepr_options->update($_POST);
      
      if(count($errors) > 0)
        require(MEPR_VIEWS_PATH.'/shared/errors.php');
      else
      {
        // Ensure that the rewrite rules are flushed & in place
        MeprUtils::flush_rewrite_rules();
        
        do_action('mepr-process-options', $_POST);
        
        $mepr_options->store();
        require(MEPR_VIEWS_PATH.'/options/options_saved.php');
      }
      
      require(MEPR_VIEWS_PATH.'/options/form.php');
    }
  }
  
  public static function enqueue_scripts($hook)
  {
    if($hook == 'memberpress_page_memberpress-options')
    {
      $mepr_options = MeprOptions::fetch();

      wp_enqueue_style('mepr-options-css', MEPR_CSS_URL.'/admin-options.css', array(), MEPR_VERSION);
      wp_enqueue_style('mepr-emails-css', MEPR_CSS_URL.'/admin-emails.css', array(), MEPR_VERSION);
      wp_enqueue_script('jquery-clippy', MEPR_JS_URL.'/jquery.clippy.js', array('jquery'));
      
      $js_helpers = array('nameLabel'         => __('Name:', 'memberpress'),
                          'typeLabel'         => __('Type:', 'memberpress'),
                          'defaultLabel'      => __('Default Value:', 'memberpress'),
                          'signupLabel'       => __('Show at Signup', 'memberpress'),
                          'requiredLabel'     => __('Required', 'memberpress'),
                          'textOption'        => __('Text', 'memberpress'),
                          'textareaOption'    => __('Textarea', 'memberpress'),
                          'checkboxOption'    => __('Checkbox', 'memberpress'),
                          'dropdownOption'    => __('Dropdown', 'memberpress'),
                          'dateOption'        => __('Date', 'memberpress'),
                          'optionNameLabel'   => __('Option Name:', 'memberpress'),
                          'optionValueLabel'  => __('Option Value:', 'memberpress'),
                          'addOptionLabel'    => __('Add Option', 'memberpress'),
                          'show_fname_lname_id'    => "#{$mepr_options->show_fname_lname_str}",
                          'require_fname_lname_id' => "#{$mepr_options->require_fname_lname_str}",
                          'jsUrl'             => MEPR_JS_URL,
                          'confirmPMDelete'   => __('WARNING: Do not remove this Payment Method if you have active subscriptions using it. Doing so will prevent you from being notified of recurring payments for those subscriptions, which means your members will lose access to their paid content. Are you sure you want to delete this Payment Method?', 'memberpress'));
      wp_localize_script('jquery-clippy', 'MeprOptions', $js_helpers);
      
      wp_enqueue_script('mepr-options-js', MEPR_JS_URL.'/admin_options.js', array('jquery','jquery-clippy','mepr-admin-shared-js','jquery-ui-sortable'), MEPR_VERSION);
      wp_enqueue_script('mepr-emails-js', MEPR_JS_URL.'/admin_emails.js', array('mepr-options-js'), MEPR_VERSION);
    }
  }
  
  public static function gateway_form()
  {
    if(!is_admin())
      die(__('Unauthorized', 'memberpress'));

    $mepr_options = MeprOptions::fetch();

    if(!isset($_POST['g']) or empty($_POST['g']))
    {
      $gateways = array_keys(MeprGatewayFactory::all());

      if(empty($gateways))
        die(__('No gateways were found', 'memberpress'));

      // Artificially set the gateway to the first available
      $gateway = $gateways[0];
    }                                                                                                        
    else
      $gateway = $_POST['g'];

    try                                                                                                      
    {
      $obj = MeprGatewayFactory::fetch($gateway);
    }
    catch(Exception $e)
    {
      die($e->getMessage());
    }
    
    require(MEPR_VIEWS_PATH."/options/gateway.php");
    
    die();
  }
} //End class
