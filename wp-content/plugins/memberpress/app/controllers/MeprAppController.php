<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprAppController extends MeprBaseController
{
  public function load_hooks()
  {
    add_action('manage_posts_custom_column', 'MeprAppController::custom_columns', 100, 2);
    add_action('manage_pages_custom_column', 'MeprAppController::custom_columns', 100, 2);
    add_action('registered_post_type', 'MeprAppController::setup_columns', 10, 2);
    add_filter('the_content', 'MeprAppController::page_route', 100);
    add_action('wp_enqueue_scripts', 'MeprAppController::load_scripts', 1);
    add_action('admin_enqueue_scripts', 'MeprAppController::load_admin_scripts', 1);
    add_action('init', 'MeprAppController::parse_standalone_request', 10);
    add_action('wp_dashboard_setup', 'MeprAppController::add_dashboard_widgets');
    add_action('widgets_init', 'MeprAppController::add_sidebar_widgets');
    add_action('custom_menu_order', 'MeprAppController::admin_menu_order');
    add_action('menu_order', 'MeprAppController::admin_menu_order');
    add_action('menu_order', 'MeprAppController::admin_submenu_order');
    add_action('widgets_init', 'MeprLoginWidget::register_widget');
    add_action('add_meta_boxes', 'MeprAppController::add_meta_boxes');
    add_action('save_post', 'MeprAppController::save_meta_boxes');
    add_action('admin_notices', 'MeprAppController::protected_notice');
    add_action('wp_ajax_mepr_todays_date', 'MeprAppController::todays_date');
    add_action('wp_ajax_mepr_close_about_notice', 'MeprAppController::close_about_notice');
    
    // DB upgrades/installs will happen here, as a non-blocking process hopefully
    add_action('init', 'MeprAppController::install');
    
    add_filter('months_dropdown_results', array($this, 'cleanup_list_table_month_dropdown'), 10, 2);
    
    register_deactivation_hook(__FILE__, 'MeprAppController::deactivate');
  }
  
  public static function add_meta_boxes()
  {
    global $post;

    $mepr_options = MeprOptions::fetch();

    if($post->ID == $mepr_options->login_page_id) { return; }

    $screens = array_merge( array_keys(get_post_types(array("public" => true, "_builtin" => false))),
                            array('post', 'page') );

    // This meta box shouldn't appear on the new/edit product screen
    $pos = array_search(MeprProduct::$cpt,$screens);
    if(isset($screens[$pos])) { unset($screens[$pos]); }

    $rules = MeprRule::get_rules($post);

    foreach($screens as $screen)
    {
      if( MeprGroup::$cpt == $screen ) {
        add_meta_box( 'mepr_unauthorized_message',
                      __('MemberPress Unauthorized Access on the Group Pricing Page', 'memberpress'),
                      'MeprAppController::unauthorized_meta_box',
                      $screen );
        if(!empty($rules)) {
          add_meta_box( 'mepr_rules',
                        __('This Group Pricing Page is Protected', 'memberpress'),
                        'MeprAppController::rules_meta_box',
                        $screen, 'normal', 'high' );
        }
      }
      elseif( in_array( $screen, array( 'post', 'page' ) ) ) {
        add_meta_box( 'mepr_unauthorized_message',
                      __('MemberPress Unauthorized Access', 'memberpress'),
                      'MeprAppController::unauthorized_meta_box',
                      $screen );
        if(!empty($rules)) {
          $obj = get_post_type_object( $screen );
          add_meta_box( 'mepr_rules',
                        sprintf( __('This %s is Protected', 'memberpress'),
                                 $obj->labels->singular_name ),
                        'MeprAppController::rules_meta_box',
                        $screen, 'normal', 'high' );
        }
      }
      else {
        $obj = get_post_type_object( $screen );
        add_meta_box( 'mepr_unauthorized_message',
                      sprintf( __('MemberPress Unauthorized Access to this %s', 'memberpress'),
                               $obj->labels->singular_name ),
                      'MeprAppController::unauthorized_meta_box',
                      $screen );
        if(!empty($rules)) {
          add_meta_box( 'mepr_rules',
                        sprintf( __('This %s is Protected', 'memberpress'),
                                 $obj->labels->singular_name ),
                        'MeprAppController::rules_meta_box',
                        $screen, 'normal', 'high' );
        }
      }
    }
  }

  public static function custom_columns($column, $post_id) {
    $post = get_post($post_id);
    if( $column=="mepr-access" ) {
      $product_ids = MeprRule::get_access_list($post);
      if(empty($product_ids)) {
        ?><div class="mepr-active"><?php _e("Public", 'memberpress'); ?></div><?php
      }
      else {
        $access_list = array();
        foreach( $product_ids as $product_id ) {
          $product = new MeprProduct($product_id);
          if(!is_null($product->ID))  {
            $access_list[] = stripslashes($product->post_title);
          }
        }

        ?>
        <div class="mepr-inactive">
          <?php echo implode(', ', $access_list); ?>
        </div>
        <?php
      }
    }
  }

  public static function setup_columns( $post_type, $args ) {
    $except = array( 'attachment', 'memberpressproduct' );
    if( $args->public and !in_array($post_type, $except) ) {
      if( $post_type == 'post' ) {
        add_filter("manage_posts_columns", 'MeprAppController::columns');
      }
      else if( $post_type == 'page' ) {
        add_filter("manage_pages_columns", 'MeprAppController::columns');
      }
      else {
        add_filter("manage_edit-{$post_type}_columns", 'MeprAppController::columns');
      }
    }
  }

  public static function columns($columns, $cpt=null) {
    $ak = array_keys($columns);
    MeprUtils::array_splice_assoc( $columns, $ak[2], $ak[2],
                                   array( "mepr-access" => __("Access", 'memberpress') ) );
    return $columns;
  }

  public static function rules_meta_box() {
    global $post;

    $rules = MeprRule::get_rules($post);
    $product_ids = MeprRule::get_access_list($post);

    require(MEPR_VIEWS_PATH . '/shared/rules_meta_box.php');
  }

  public static function protected_notice() {
    global $post, $pagenow;

    if( 'post.php' != $pagenow or
        !isset($_REQUEST['action']) or
        $_REQUEST['action']!='edit' )
    { return; }

    $rules = MeprRule::get_rules($post);
    $rule_count = count($rules);

    $message = '<strong>' .
               sprintf( _n( 'This Content is Protected by %s MemberPress Access Rule',
                            'This Content is Protected by %s MemberPress Access Rules',
                            $rule_count , 'memberpress'), $rule_count ) .
               '</strong>' .
               ' &ndash; <a href="#mepr_post_rules">' . __('Click here to view', 'memberpress') . '</a>';

    if(!empty($rules))
      require(MEPR_VIEWS_PATH . '/shared/errors.php');
  }

  public static function unauthorized_meta_box()
  {
    global $post;

    $mepr_options = MeprOptions::fetch();

    $_wpnonce = wp_create_nonce('mepr_unauthorized');

    if(!($unauthorized_message_type = get_post_meta($post->ID, '_mepr_unauthorized_message_type', true)))
      $unauthorized_message_type = 'default';

    if(!($unauthorized_message = get_post_meta($post->ID, '_mepr_unauthorized_message', true)))
      $unauthorized_message = '';

    $unauth_excerpt_type = get_post_meta($post->ID, '_mepr_unauth_excerpt_type', true);

    // Backwards compatibility here people
    if($unauthorized_message_type=='excerpt') {
      $unauthorized_message_type = 'hide';
      if(empty($unauth_excerpt_type)) {
        $unauth_excerpt_type = 'show';
      }
    }

    if(empty($unauth_excerpt_type)) {
      $unauth_excerpt_type = 'default';
    }

    $unauth_excerpt_size = get_post_meta($post->ID, '_mepr_unauth_excerpt_size', true);

    if($unauth_excerpt_size === '' or !is_numeric($unauth_excerpt_size)) {
      $unauth_excerpt_size = 100;
    }

    $unauth_login = get_post_meta($post->ID, '_mepr_unauth_login', true);

    if($unauth_login=='') {
      // backwards compatibility
      $hide_login = get_post_meta($post->ID, '_mepr_hide_login_form', true);
      $unauth_login = (empty($hide_login)?'default':'show');
    }

    require(MEPR_VIEWS_PATH . '/shared/unauthorized_meta_box.php');
  }
  
  public static function save_meta_boxes($post_id)
  {
    //Verify the Nonce First
    if( !isset($_REQUEST['mepr_custom_unauthorized_nonce']) ||
        !wp_verify_nonce($_REQUEST['mepr_custom_unauthorized_nonce'], 'mepr_unauthorized') )
      return $post_id;

    if((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || defined('DOING_AJAX'))
      return $post_id;

    // First we need to check if the current user is authorized to do this action. 
    if('page' == $_POST['post_type']) {
      if(!current_user_can('edit_page', $post_id)) { return; }
    }
    else {
      if(!current_user_can('edit_post', $post_id)) { return; }
    }

    //if saving in a custom table, get post_ID
    $post_ID = $_REQUEST['post_ID'];

    update_post_meta( $post_ID, '_mepr_unauthorized_message_type', $_REQUEST['_mepr_unauthorized_message_type'] );
    update_post_meta( $post_ID, '_mepr_unauthorized_message',      $_REQUEST['_mepr_unauthorized_message'] );
    update_post_meta( $post_ID, '_mepr_unauth_login',              $_REQUEST['_mepr_unauth_login'] );
    update_post_meta( $post_ID, '_mepr_unauth_excerpt_type',       $_REQUEST['_mepr_unauth_excerpt_type'] );
    update_post_meta( $post_ID, '_mepr_unauth_excerpt_size',       $_REQUEST['_mepr_unauth_excerpt_size'] );
  }

  public static function setup_menus() {
    add_action('admin_menu', 'MeprAppController::menu');
  }
  
  /** INSTALL PLUGIN
    * Handled in the same way wp-cron does it ...
    * fast, non-blocking post with an ignore_user_abort
    */
  public static function install() {
    $mepr_db = new MeprDb();

    if( defined('DOING_AJAX') && isset($_POST['action']) &&
        $_POST['action'] == 'mepr-db-install' ) {

      ignore_user_abort(true);
      set_time_limit(0);
 
      $mepr_db->upgrade();
      
      die(__('Database upgrade complete', 'memberpress'));
    }
    elseif(!defined('DOING_AJAX') && $mepr_db->do_upgrade()) {
      $args = array( 'method' => 'POST',
                     'timeout' => 0.01,
                     'blocking' => false,
                     'body' => array('action' => 'mepr-db-install')
                   );
      
      wp_remote_post(admin_url('admin-ajax.php'), $args);
    }
  }
  
  public static function help() {
    require MEPR_VIEWS_PATH . '/help/ui.php';
  }
  
  public static function menu() {
    $capability = apply_filters('mepr-menus-capability', 'administrator');
    
    self::admin_separator();

    $txn_ctrl = new MeprTransactionsController();
    $sub_ctrl = new MeprSubscriptionsController();

    add_menu_page(__('MemberPress', 'memberpress'), __('MemberPress', 'memberpress'), $capability, 'memberpress', 'MeprOptionsController::route', MEPR_IMAGES_URL."/memberpress-16@2x.png", 775677);
    add_submenu_page('memberpress', __('Subscriptions', 'memberpress'), __('Subscriptions', 'memberpress'), $capability, 'memberpress-subscriptions', array( $sub_ctrl, 'listing' ));
    add_submenu_page('memberpress', __('Transactions', 'memberpress'), __('Transactions', 'memberpress'), $capability, 'memberpress-trans', array( $txn_ctrl, 'listing' ));
    add_submenu_page('memberpress', __('Reports', 'memberpress'), __('Reports', 'memberpress'), $capability, 'memberpress-reports', 'MeprReportsController::main');
    add_submenu_page('memberpress', __('Options', 'memberpress'), __('Options', 'memberpress'), $capability, 'memberpress-options', 'MeprOptionsController::route');
    // Adding submenu page for help
    add_submenu_page('memberpress', __('Help', 'memberpress'), __('Help', 'memberpress'), $capability, 'memberpress-help', 'MeprAppController::help');
    
    if(!defined('MEMBERPRESS_LICENSE_KEY') and class_exists('MeprUpdateController')) {
      add_submenu_page( 'memberpress', __('Activate', 'memberpress'), __('Activate', 'memberpress'), $capability, 'memberpress-activate', 'MeprUpdateController::route');
    }
    
    do_action('mepr_menu');
  }
  
  /**
   * Add a separator to the WordPress admin menus
   */
  public static function admin_separator()
  {
    // Prevent duplicate separators when no core menu items exist
    if(!current_user_can('manage_options'))
      return;
    
    global $menu;
    $menu[] = array('', 'read', 'separator-memberpress', '', 'wp-menu-separator memberpress');
  }
  
  /**
   * Move our custom separator above our admin menu
   *
   * @param array $menu_order Menu Order
   * @return array Modified menu order
   */
  public static function admin_menu_order($menu_order)
  {
    if(!$menu_order)
      return true;
    
    if(!is_array($menu_order))
      return $menu_order;
    
    // Initialize our custom order array
    $new_menu_order = array();
    
    // Menu values
    $second_sep   = 'separator2';
    $custom_menus = array('separator-memberpress', 'memberpress');
    
    // Loop through menu order and do some rearranging
    foreach($menu_order as $item)
    {
      // Position MemberPress menus above appearance
      if($second_sep == $item)
      {
        // Add our custom menus
        foreach($custom_menus as $custom_menu)
          if(array_search($custom_menu, $menu_order))
            $new_menu_order[] = $custom_menu;
        
        // Add the appearance separator
        $new_menu_order[] = $second_sep;
      
      // Skip our menu items down below
      }
      elseif(!in_array($item, $custom_menus))
        $new_menu_order[] = $item;
    }
    
    // Return our custom order
    return $new_menu_order;
  }
  
  //Organize the CPT's in our submenu
  public static function admin_submenu_order($menu_order)
  {
    global $submenu;
    
    static $run = false;
    
    //no sense in running this everytime the hook gets called
    if($run) { return $menu_order; }

    //just return if there's no memberpress menu available for the current screen
    if(!isset($submenu['memberpress'])) { return $menu_order; }
    
    $run = true;
    $new_order = array();
    $i = 4;
    
    foreach($submenu['memberpress'] as $sub)
    {
      if($sub[0] == 'Products')
        $new_order[0] = $sub;
      elseif($sub[0] == 'Groups')
        $new_order[1] = $sub;
      elseif($sub[0] == 'Rules')
        $new_order[2] = $sub;
      elseif($sub[0] == 'Coupons')
        $new_order[3] = $sub;
      else
        $new_order[$i++] = $sub;
    }
    
    ksort($new_order);
    
    $submenu['memberpress'] = $new_order;
    
    return $menu_order;
  }
  
  // Routes for wordpress pages -- we're just replacing content here folks.
  public static function page_route($content)
  {
    $current_post = MeprUtils::get_current_post();
    
    //WARNING the_content CAN be run more than once per page load
    //so this static var prevents stuff from happening twice
    //like cancelling a subscr or resuming etc...
    static $already_run = array();
    static $new_content = array();
    //Init this posts static values
    $already_run[$current_post->ID] = false;
    $new_content[$current_post->ID] = '';
    
    if($already_run[$current_post->ID])
      return $new_content[$current_post->ID];
    
    $already_run[$current_post->ID] = true;
    
    $mepr_options = MeprOptions::fetch();

    switch($current_post->ID)
    {
      case $mepr_options->account_page_id:
        if( !MeprUser::manually_place_account_form($current_post) )
          $content = MeprAccountController::display_account_form($content);

        break;
      case $mepr_options->login_page_id:
        ob_start();

        $action = self::get_param('action');

        if($action and $action == 'forgot_password')
          MeprUsersController::display_forgot_password_form();
        else if($action and $action == 'mepr_process_forgot_password')
          MeprUsersController::process_forgot_password_form();
        else if($action and $action == 'reset_password')
          MeprUsersController::display_reset_password_form(self::get_param('mkey'),self::get_param('u'));
        else if($action and $action == 'mepr_process_reset_password_form')
          MeprUsersController::process_reset_password_form();
        else if(!($manual_login_form = get_post_meta($current_post->ID, '_mepr_manual_login_form', true)))
        {
          if($action and $action == 'mepr_unauthorized')
          {
            $resource = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : __('the requested resource.','memberpress');

            printf(__('<div class="mepr-unauthorized-error">You are unauthorized to view %s</div>','memberpress'), $resource);
            
            echo '<div class="mepr-unauthorized-message">' . wpautop(apply_filters('mepr-unauthorized-message', $mepr_options->unauthorized_message, $current_post)) . '</div>';
          }
          
          MeprUsersController::display_login_form();
        }
        
        $content .= ob_get_clean();
        break;
      case $mepr_options->thankyou_page_id:
        $message = MeprProductsController::maybe_get_thank_you_page_message();
        
        if($message != '') //If a custom message is set, only show that message
          $content = $message;
        break;
    }
    
    //See above notes
    $new_content[$current_post->ID] = $content;
    return $content;
  }  
  
  public static function load_scripts()
  {
    global $post;
    $wp_scripts = new WP_Scripts();
    $ui = $wp_scripts->query('jquery-ui-core');
    $url = "//ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.css";
    $pid = (isset($post->ID))?$post->ID:0;

    $is_product_page = ( false !== ( $prd = MeprProduct::is_product_page($post) ) );
    $is_group_page   = ( false !== ( $grp = MeprGroup::is_group_page($post) ) );
    $is_account_page = MeprUser::is_account_page($post);

    do_action('mepr_enqueue_scripts', $is_product_page, $is_group_page, $is_account_page);

    $mepr_options = MeprOptions::fetch();

    if( $pid == $mepr_options->login_page_id ||
        $pid == $mepr_options->thankyou_page_id ||
        $is_account_page || $is_product_page || $is_group_page)
    {
      wp_enqueue_style('mepr-ui-smoothness', $url);
      wp_enqueue_style('memberpress-css', MEPR_CSS_URL.'/memberpress.css', array(), MEPR_VERSION);
      wp_enqueue_script('mepr-date-picker-js', MEPR_JS_URL.'/date_picker.js', array('jquery-ui-datepicker'), MEPR_VERSION);
      wp_enqueue_script('memberpress-js', MEPR_JS_URL.'/memberpress.js', array('jquery','mepr-date-picker-js'), MEPR_VERSION);
    }

    if($is_product_page)
    {
      wp_enqueue_script('memberpress-ssl-js', MEPR_JS_URL.'/ssl.js', array('jquery'), MEPR_VERSION);
      
      $pms = $mepr_options->payment_methods();
      $fssl = array();

      foreach($pms as $pm) {
        if($pm instanceof MeprBaseRealGateway) {
          $fssl[$pm->id] = ($pm->force_ssl()?'true':'false');
        }
      }

      $http_url = apply_filters( 'mepr-http-product-url', $prd->url(), $prd );
      $ssl_url  = apply_filters( 'mepr-https-product-url', preg_replace('~^https?~', 'https', $prd->url()), $prd );

      wp_localize_script('memberpress-ssl-js', 'mepr_ssl', $fssl);
      wp_localize_script('memberpress-ssl-js', 'mepr_ssl_url', $ssl_url);
      wp_localize_script('memberpress-ssl-js', 'mepr_http_url', $http_url);
    }

    if($is_group_page and $grp->group_theme != 'custom')
      wp_enqueue_style('mepr-plans-css', MEPR_CSS_URL.'/plans/'.$grp->group_theme, array(), MEPR_VERSION);
  }

  public static function load_admin_scripts($hook)
  {
    global $wp_version;

    wp_enqueue_style( 'mepr-admin-shared-css',
                      MEPR_CSS_URL.'/admin-shared.css',
                      array('wp-pointer'), MEPR_VERSION );
    wp_enqueue_style( 'mepr-fontello-animation',
                      MEPR_VENDOR_LIB_URL.'/fontello/css/animation.css',
                      array(), MEPR_VERSION );
    wp_enqueue_style( 'mepr-fontello-memberpress',
                      MEPR_VENDOR_LIB_URL.'/fontello/css/memberpress.css',
                      array('mepr-fontello-animation'), MEPR_VERSION );

    // If we're in 3.8 now then use a font for the admin image
    if( version_compare( $wp_version, '3.8', '>=' ) ) {
      wp_enqueue_style( 'mepr-menu-styles', MEPR_CSS_URL.'/menu-styles.css',
                        array('mepr-fontello-memberpress'), MEPR_VERSION );
    }

    wp_enqueue_script('mepr-tooltip', MEPR_JS_URL.'/tooltip.js', array('jquery', 'wp-pointer'), MEPR_VERSION);
    wp_localize_script('mepr-tooltip', 'MeprTooltip', array( 'show_about_notice' => self::show_about_notice(),
                                                             'about_notice' => self::about_notice() ));
    wp_enqueue_script('mepr-admin-shared-js', MEPR_JS_URL.'/admin_shared.js', array('jquery'), MEPR_VERSION);
    
    //Widget in the dashboard stuff
    if($hook == 'index.php')
    {
      wp_enqueue_script('mepr-google-jsapi', 'https://www.google.com/jsapi', array(), MEPR_VERSION);
      wp_enqueue_script('mepr-widgets-js', MEPR_JS_URL.'/admin_widgets.js', array('jquery', 'mepr-google-jsapi'), MEPR_VERSION, true);
      wp_enqueue_style('mepr-widgets-css', MEPR_CSS_URL.'/admin-widgets.css', array(), MEPR_VERSION);
    }
  }
  
  // The tight way to process standalone requests dogg...
  public static function parse_standalone_request()
  {
    global $user_ID;
    
    $plugin     = (isset($_REQUEST['plugin']))?$_REQUEST['plugin']:'';
    $action     = (isset($_REQUEST['action']))?$_REQUEST['action']:'';
    $controller = (isset($_REQUEST['controller']))?$_REQUEST['controller']:'';
    
    $request_uri = $_SERVER['REQUEST_URI'];
    
    if( !empty($plugin) and $plugin == 'mepr' and 
        !empty($controller) and !empty($action) )
    {
      self::standalone_route($controller, $action);
      exit;
    }
    else if( !empty($plugin) and $plugin == 'mepr' and isset($_REQUEST['pmt']) and
             !empty($_REQUEST['pmt']) and !empty($action) )
    {
      $mepr_options = MeprOptions::fetch();
      if( $obj = $mepr_options->payment_method($_REQUEST['pmt']) and $obj instanceof MeprBaseRealGateway ) {
        $notifiers = $obj->notifiers();
        if( isset($notifiers[$action]) ) {
          call_user_func(array($obj,$notifiers[$action]));
          exit;
        }
      }
    }
    else if(isset($_POST) and isset($_POST['mepr_process_login_form']))
      MeprUsersController::process_login_form();
    else if(isset($_POST) and isset($_POST['mepr_process_signup_form']))
      MeprUsersController::process_signup_form();
    else if(isset($_GET) and isset($_GET['renew']) and isset($_GET['tid']) and isset($_GET['uid']))
      MeprUsersController::process_signup_form(true);
    else if(isset($_POST) and isset($_POST['mepr_process_payment_form']))
      MeprUsersController::process_payment_form();
    else if(isset($_POST['mepr-new-password']) && isset($_POST['mepr-confirm-password']))
      MeprUsersController::save_new_password($user_ID, $_POST['mepr-new-password'], $_POST['mepr-confirm-password']);
  }
  
  // Routes for standalone / ajax requests
  public static function standalone_route($controller, $action)
  {
    if($controller == 'coupons')
    {
      if($action == 'validate')
        MeprCouponsController::validate_coupon_ajax(MeprAppController::get_param('mepr_coupon_code'), MeprAppController::get_param('mpid'));
    }
    else if($controller == 'reports')
    {
      if($action == 'loadpie')
        MeprReportsController::load_pie();
      else if($action == 'loadmonth')
        MeprReportsController::load_monthly();
      else if($action == 'loadyear')
        MeprReportsController::load_yearly();
      else if($action == 'loadwidget')
        MeprReportsController::load_widget();
    }
  }
  
  public static function load_language()
  {
    /*
    * Allow add-ons and such to load .po/mo files from outside directories using this filter hook
    * WordPress will merge transalations if the textdomain is the same from multiple locations
    * so we should be good to do it this way
    */
    $paths = array();
    $paths[] = str_replace(WP_PLUGIN_DIR, '', MEPR_I18N_PATH);
    
    //Have to use WP_PLUGIN_DIR because load_plugin_textdomain doesn't accept abs paths
    if(!file_exists(WP_PLUGIN_DIR . '/' . 'mepr-i18n'))
    {
      @mkdir(WP_PLUGIN_DIR . '/' . 'mepr-i18n');
      
      if(file_exists(WP_PLUGIN_DIR . '/' . 'mepr-i18n'))
        $paths[] = '/mepr-i18n';
    }
    else
      $paths[] = '/mepr-i18n';
    
    $paths = apply_filters('mepr-textdomain-paths', $paths);
    
    foreach($paths as $path)
      load_plugin_textdomain('memberpress', false, $path);
  }
  
  // Utility function to grab the parameter whether it's a get or post
  public static function get_param($param, $default = '')
  {
    if((!isset($_POST) or empty($_POST)) and
       (!isset($_GET) or empty($_GET)))
      return $default;
    
    if(!isset($_POST[$param]) and !isset($_GET[$param]))
      return $default;
    
    return (isset($_POST[$param])?$_POST[$param]:(isset($_GET[$param])?$_GET[$param]:$default));
  }
  
  public static function get_param_delimiter_char($link)
  {
    return ((preg_match("#\?#",$link))?'&':'?');
  }
  
  public static function add_dashboard_widgets()
  {
    if(!MeprUtils::is_admin())
      return;
    
    wp_add_dashboard_widget('mepr_weekly_stats_widget', 'MemberPress Weekly Stats', 'MeprAppController::weekly_stats_widget');
    
    // Globalize the metaboxes array, this holds all the widgets for wp-admin
    global $wp_meta_boxes;
    
    // Get the regular dashboard widgets array 
    // (which has our new widget already but at the end)
    $normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
    
    // Backup and delete our new dashbaord widget from the end of the array
    $mepr_weekly_stats_widget_backup = array('mepr_weekly_stats_widget' => $normal_dashboard['mepr_weekly_stats_widget']);
    unset($normal_dashboard['mepr_weekly_stats_widget']);
    
    // Merge the two arrays together so our widget is at the beginning
    $sorted_dashboard = array_merge($mepr_weekly_stats_widget_backup, $normal_dashboard);
    
    // Save the sorted array back into the original metaboxes 
    $wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
  }
  
  public static function add_sidebar_widgets()
  {
    wp_register_sidebar_widget('mepr-account-links', 'MemberPress Account Links', 'MeprUsersController::account_links_widget');
  }
  
  public static function weekly_stats_widget()
  {
    $mepr_options = MeprOptions::fetch();
    $failed_transactions = $pending_transactions = $refunded_transactions = $completed_transactions = $revenue = $refunds = 0;
    $time = time();
    for($i = 0; $i < 7; $i++)
    {
      $ts = $time - MeprUtils::days($i);
      $month = date('n', $ts);
      $day = date('j', $ts);
      $year = date('Y', $ts);
      
      $pending_transactions += MeprReports::get_transactions_count(MeprTransaction::$pending_str, $day, $month, $year);
      $failed_transactions += MeprReports::get_transactions_count(MeprTransaction::$failed_str, $day, $month, $year);
      $refunded_transactions += MeprReports::get_transactions_count(MeprTransaction::$refunded_str, $day, $month, $year);
      $completed_transactions += MeprReports::get_transactions_count(MeprTransaction::$complete_str, $day, $month, $year);
      
      $revenue += MeprReports::get_revenue($month, $day, $year);
      $refunds += MeprReports::get_refunds($month, $day, $year);
    }
    
    require(MEPR_VIEWS_PATH . '/widgets/admin_stats_widget.php');
  }

  public static function todays_date() {
    if(isset($_REQUEST['datetime']))
      echo date('Y-m-d H:i:s');
    else
      echo date('Y-m-d');

    die;
  }

  public static function show_about_notice() {
    $last_shown_notice = get_option('mepr_about_notice_version');
    $version_str = preg_replace('/\./','-',MEPR_VERSION);
    return ( $last_shown_notice != MEPR_VERSION and
             file_exists( MEPR_VIEWS_PATH . "/about/{$version_str}.php" ) );
  }

  public static function about_notice() {
    $version_str  = preg_replace('/\./','-',MEPR_VERSION);
    $version_file = MEPR_VIEWS_PATH . "/about/{$version_str}.php";
    if( file_exists( $version_file ) ) {
      ob_start();
      require_once($version_file);
      return ob_get_clean();
    }

    return '';
  }

  public static function close_about_notice() {
    update_option('mepr_about_notice_version',MEPR_VERSION);
  }

  public static function cleanup_list_view($views) {
    if(isset($views['draft'])) { unset($views['draft']); }
    if(isset($views['publish'])) { unset($views['publish']); }
    return $views;
  }

  public function cleanup_list_table_month_dropdown( $months, $post_type ) {
    $ours = array( MeprProduct::$cpt, MeprRule::$cpt, MeprGroup::$cpt, MeprCoupon::$cpt );
    if( in_array( $post_type, $ours ) ) { $months = array(); }
    return $months;
  }
} //End class

