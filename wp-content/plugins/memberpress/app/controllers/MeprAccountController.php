<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprAccountController extends MeprBaseController
{
  public function load_hooks()
  {
    add_action('wp_enqueue_scripts', 'MeprAccountController::enqueue_scripts');
    add_shortcode('mepr-account-form', 'MeprAccountController::account_form_shortcode');
  }
  
  public static function enqueue_scripts()
  {
    global $post;
    $mepr_options = MeprOptions::fetch();
    
    if(MeprUser::is_account_page($post))
    {
      if(isset($_REQUEST['action']) and $_REQUEST['action']=='update')
        wp_enqueue_style('mepr-signup', MEPR_CSS_URL . '/signup.css', array());

      wp_enqueue_script('jquery-clippy', MEPR_JS_URL.'/jquery.clippy.js', array('jquery'));
      wp_enqueue_script('memberpress-account', MEPR_JS_URL.'/account.js', array('jquery','jquery-clippy'), MEPR_VERSION);
      wp_localize_script('memberpress-account', 'clippy', array( 'url' => MEPR_JS_URL.'/clippy.swf' ));
      
      $pms = $mepr_options->payment_methods();
      if($pms)
        foreach($pms as $pm)
          if($pm instanceof MeprBaseRealGateway)
            $pm->enqueue_user_account_scripts();
    }
  }
  
  public static function render()
  {
    global $post;

    if(!isset($post) or !($post instanceof WP_Post)) { return; }

    $mepr_current_user = MeprUtils::get_currentuserinfo();
    $expired_subs = $mepr_current_user->subscription_expirations('expired',true);
    $mepr_options = MeprOptions::fetch();
    $account_url = get_permalink($post->ID); //$mepr_options->account_page_url();
    $delim = MeprAppController::get_param_delimiter_char($account_url);
    ?>
    <div id="mepr-member-account-wrapper">
    <?php
    include(MEPR_VIEWS_PATH."/account/nav.php");

    $action = (isset($_REQUEST['action']))?$_REQUEST['action']:false;
    switch($action)
    {
      case 'payments':
        self::payments();
        break;
      case 'subscriptions':
        self::subscriptions();
        break;
      case 'newpassword':
        self::password();
        break;
      case 'cancel':
        self::cancel();
        break;
      case 'suspend':
        self::suspend();
        break;
      case 'resume':
        self::resume();
        break;
      case 'update':
        self::update();
        break;
      case 'upgrade':
        self::upgrade();
        break;
      default:
        // Allows you to override the content for a nav tab
        ob_start();
        do_action( 'mepr_account_nav_content', $action );
        $custom_content = ob_get_clean();

        if( empty($custom_content) ) {
          self::home();
        }
        else {
          echo $custom_content;
        }
    }
    ?>
    </div>
    <?php
  }
  
  public static function home()
  {
    $mepr_current_user = MeprUtils::get_currentuserinfo();
    $mepr_options = MeprOptions::fetch();
    $account_url = $mepr_options->account_page_url();
    $delim = MeprAppController::get_param_delimiter_char($account_url);
    $errors = array();
    $saved = false;
    $welcome_message = wpautop(stripslashes($mepr_options->custom_message));
    
    if(isset($_POST['mepr-process-account']) && $_POST['mepr-process-account'] == 'Y')
    {
      $errors = MeprUsersController::validate_extra_profile_fields();
      $errors = MeprUser::validate_account($_POST, $errors);
      $errors = apply_filters('mepr-validate-account', $errors, $mepr_current_user);
      
      if(empty($errors))
      {
        //Need to find a better way to do this eventually but for now update the user's email
        $mepr_current_user->user_email = stripslashes($_POST['user_email']);
        $mepr_current_user->store();
        
        //Save the usermeta
        $saved = MeprUsersController::save_extra_profile_fields($mepr_current_user->ID, true);
        do_action('mepr-save-account', $mepr_current_user);
      }
      else
        require(MEPR_VIEWS_PATH.'/shared/errors.php');
    }
    
    //Load user last in case we saved above, we want the saved info to show up
    $mepr_current_user = new MeprUser($mepr_current_user->ID);
    
    require(MEPR_VIEWS_PATH."/account/home.php");
  }
  
  public static function password()
  {
    $mepr_current_user = MeprUtils::get_currentuserinfo();
    $mepr_options = MeprOptions::fetch();
    $account_url = $mepr_options->account_page_url();
    $delim = MeprAppController::get_param_delimiter_char($account_url);
    require(MEPR_VIEWS_PATH."/account/password.php");
  }
  
  public static function payments()
  {
    global $wpdb;
    $mepr_current_user = MeprUtils::get_currentuserinfo();
    $mepr_options = MeprOptions::fetch();
    $account_url = $mepr_options->account_page_url();
    $delim = MeprAppController::get_param_delimiter_char($account_url);
    $perpage = 10;
    $curr_page = (isset($_GET['currpage']) && is_numeric($_GET['currpage']))?$_GET['currpage']:1;
    $start = ($curr_page - 1) * $perpage;
    $end = $start + $perpage;
    $list_table = MeprTransaction::list_table( 'created_at', 'DESC',
                                               $curr_page, '', $perpage,
                                               array( 'member' => $mepr_current_user->user_login,
                                                      'statuses' => array( MeprTransaction::$complete_str ) ) );
    $payments = $list_table['results'];
    $all = $list_table['count'];
    $next_page = (($curr_page * $perpage) >= $all)?false:$curr_page+1;
    $prev_page = ($curr_page > 1)?$curr_page - 1:false;
    
    require(MEPR_VIEWS_PATH."/account/payments.php");
  }
  
  public static function subscriptions($message='',$errors=array())
  {
    global $wpdb;

    $mepr_current_user = MeprUtils::get_currentuserinfo();
    $mepr_options = MeprOptions::fetch();
    $account_url = $mepr_options->account_page_url();
    $delim = MeprAppController::get_param_delimiter_char($account_url);
    $perpage = 10;
    $curr_page = (isset($_GET['currpage']) && is_numeric($_GET['currpage']))?$_GET['currpage']:1;
    $start = ($curr_page - 1) * $perpage;
    $end = $start + $perpage;

    // This is necessary to optimize the queries ... only query what we need
    $sub_cols = array('ID','subscr_id','status','created_at','expires_at','active',);

    $table = MeprSubscription::account_subscr_table( 'created_at', 'DESC',
                                                     $curr_page, '', $perpage, false,
                                                     array(
                                                       'member' => $mepr_current_user->user_login,
                                                       'statuses' => array( MeprSubscription::$active_str,
                                                                            MeprSubscription::$suspended_str,
                                                                            MeprSubscription::$cancelled_str )
                                                     ),
                                                     $sub_cols
                                                   );

    $subscriptions = $table['results'];
    $all = $table['count'];
    $next_page = (($curr_page * $perpage) >= $all)?false:$curr_page + 1;
    $prev_page = ($curr_page > 1)?$curr_page - 1:false;

    require(MEPR_VIEWS_PATH."/shared/errors.php");
    require(MEPR_VIEWS_PATH."/account/subscriptions.php");
  }
  
  public static function suspend()
  {
    $mepr_current_user = MeprUtils::get_currentuserinfo();
    $sub = new MeprSubscription($_GET['sub']);
    $errors = array();
    $message = '';
    
    if($sub->user_id == $mepr_current_user->ID)
    {
      $pm = $sub->payment_method();

      if($pm->can('suspend-subscriptions')) {
        try {
          $pm->process_suspend_subscription($sub->ID);
          $message = __('Your subscription was successfully paused.', 'memberpress');
        }
        catch( Exception $e ) {
          $errors[] = $e->getMessage();
        }
      }
    }
    
    self::subscriptions($message, $errors);
  }

  public static function resume()
  {
    $mepr_current_user = MeprUtils::get_currentuserinfo();
    $sub = new MeprSubscription($_GET['sub']);
    $errors = array();
    $message = '';
    
    if($sub->user_id == $mepr_current_user->ID)
    {
      $pm = $sub->payment_method();

      if($pm->can('suspend-subscriptions')) {
        try {
          $pm->process_resume_subscription($sub->ID);
          $message = __('You successfully resumed your subscription.', 'memberpress');
        }
        catch( Exception $e ) {
          $errors[] = $e->getMessage();
        }
      }
    }
    
    self::subscriptions($message, $errors);
  }

  public static function cancel()
  {
    $mepr_current_user = MeprUtils::get_currentuserinfo();
    $sub = new MeprSubscription($_GET['sub']);
    $errors = array();
    $message = '';
    
    if($sub->user_id == $mepr_current_user->ID)
    {
      $pm = $sub->payment_method();

      if($pm->can('cancel-subscriptions')) {
        try {
          $pm->process_cancel_subscription($sub->ID);
          $message = __('Your subscription was successfully cancelled.', 'memberpress');
        }
        catch( Exception $e ) {
          $errors[] = $e->getMessage();
        }
      }
    }
    
    self::subscriptions($message, $errors);
  }
  
  public static function update()
  {
    $mepr_current_user = MeprUtils::get_currentuserinfo();
    $sub = new MeprSubscription($_REQUEST['sub']);
    
    if($sub->user_id == $mepr_current_user->ID)
    {
      $pm = $sub->payment_method();
      
      if(strtoupper($_SERVER['REQUEST_METHOD']=='GET')) // DISPLAY FORM
        $pm->display_update_account_form($sub->ID, array());
      else if(strtoupper($_SERVER['REQUEST_METHOD']=='POST'))
      { // PROCESS FORM
        $errors = $pm->validate_update_account_form(array());
        $message='';

        if(empty($errors)) {
          try {
            $pm->process_update_account_form($sub->ID);
            $message = __('Your account information was successfully updated.', 'memberpress');
          }
          catch( Exception $e ) {
            $errors[] = $e->getMessage();
          }
        }

        $pm->display_update_account_form($sub->ID, $errors, $message);
      }
    }
  }
  
  public static function upgrade()
  {
    $sub = new MeprSubscription($_GET['sub']);
    $prd = $sub->product();
    $grp = $prd->group();  
    
    // TODO: Uyeah, we may want to come up with a more elegant solution here
    //       for now we have to do a js redirect because we're in mid-page render
    ?>
    <script>
      top.window.location = '<?php echo $grp->url(); ?>';
    </script>
    <?php
  }

  public static function account_form_shortcode($atts, $content='') {
    //No need to validate anything as the below function already
    //does all the validations. This is just a wrapper
    return self::display_account_form($content);
  }
  
  public static function display_account_form($content = '') {
    global $post;
    
    if(MeprUtils::is_user_logged_in())
    {
      ob_start();
      MeprAccountController::render();
      $content .= ob_get_clean();
    }
    else
      $content = MeprRulesController::unauthorized_message($post);
    
    return $content;
  }
}
