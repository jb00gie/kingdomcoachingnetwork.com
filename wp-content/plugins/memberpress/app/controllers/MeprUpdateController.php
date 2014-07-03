<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprUpdateController extends MeprBaseController
{
  public function load_hooks()
  {
    add_filter('pre_set_site_transient_update_plugins', 'MeprUpdateController::queue_update');
    add_filter('plugins_api', 'MeprUpdateController::plugin_info', 11, 3);
    add_action('admin_enqueue_scripts', 'MeprUpdateController::enqueue_scripts');
    add_action('admin_notices', 'MeprUpdateController::activation_warning');
    //add_action('mepr_display_options', 'MeprUpdateController::queue_button');
    add_action('admin_init', 'MeprUpdateController::activate_from_define');
    add_action('wp_ajax_mepr_edge_updates', 'MeprUpdateController::mepr_edge_updates');
  }

  public static function route()
  {
    if(strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
      return self::process_form();
    }
    else {
      if( isset($_GET['action']) and
          $_GET['action'] == 'deactivate' and
          isset($_GET['_wpnonce']) and
          wp_verify_nonce($_GET['_wpnonce'], 'memberpress_deactivate') ) {
        return self::deactivate();
      }
      else {
        return self::display_form();
      }
    }
  }

  public static function display_form($message='', $errors=array())
  {
    $mepr_options = MeprOptions::fetch();

    // We just force the queue to update when this page is visited
    // that way we ensure the license info transient is set
    self::manually_queue_update();

    if(!empty($mepr_options->mothership_license) and empty($errors)) {
      $li = get_site_transient( 'mepr_license_info' );
    }

    require( MEPR_VIEWS_PATH.'/update/ui.php' );
  }
  
  public static function process_form()
  {
    if(!isset($_POST['_wpnonce']) or !wp_verify_nonce($_POST['_wpnonce'],'activation_form'))
      wp_die(_e('Why you creepin\'?', 'memberpress'));
    
    $mepr_options = MeprOptions::fetch();
    
    if(!isset($_POST[$mepr_options->mothership_license_str]))
    {
      self::display_form();
      return;
    }
    
    $message = '';
    $errors = array();
    $mepr_options->mothership_license = stripslashes($_POST[$mepr_options->mothership_license_str]);
    $domain = urlencode(MeprUtils::site_domain());
    
    try
    {
      $args = compact('domain');
      $act = self::send_mothership_request("/license_keys/activate/{$mepr_options->mothership_license}", $args, 'post');
      self::manually_queue_update();
      $mepr_options->store(false);
      $message = $act['message'];
    }
    catch(Exception $e)
    {
      $errors[] = $e->getMessage();
    }
    
    self::display_form($message, $errors);
  }

  public static function activate_from_define() {
    $mepr_options = MeprOptions::fetch();

    if( defined('MEMBERPRESS_LICENSE_KEY') and
        $mepr_options->mothership_license != MEMBERPRESS_LICENSE_KEY ) {
      $message = '';
      $errors = array();
      $mepr_options->mothership_license = stripslashes(MEMBERPRESS_LICENSE_KEY);
      $domain = urlencode(MeprUtils::site_domain());

      try {
        $args = compact('domain');

        if(!empty($mepr_options->mothership_license))
          $act = self::send_mothership_request("/license_keys/deactivate/{$mepr_options->mothership_license}", $args, 'post');

        $act = self::send_mothership_request("/license_keys/activate/".MEMBERPRESS_LICENSE_KEY, $args, 'post');

        self::manually_queue_update();

        // If we're using defines then we have to do this with defines too
        $mepr_options->edge_updates = false;
        $mepr_options->store(false);

        $message = $act['message'];
        $callback = create_function( '', '$message = "'.$message.'"; ' .
                                     'require( MEPR_VIEWS_PATH . "/shared/errors.php" );' );
      }
      catch(Exception $e) {
        $callback = create_function( '', '$error = "'.$e->getMessage().'"; ' .
                                     'require( MEPR_VIEWS_PATH . "/update/activation_warning.php" );' );
      }

      add_action( 'admin_notices', $callback ); 
    }
  }

  public static function deactivate()
  {
    $mepr_options = MeprOptions::fetch();
    $domain = urlencode(MeprUtils::site_domain());
    
    try
    {
      $args = compact('domain');
      $act = self::send_mothership_request("/license_keys/deactivate/{$mepr_options->mothership_license}", $args, 'post');
      self::manually_queue_update();
      $mepr_options->mothership_license = '';
      $mepr_options->store(false);
      $message = $act['message'];
    }
    catch(Exception $e)
    {
      $errors[] = $e->getMessage();
    }
    
    self::display_form($message);
  }
  
  public static function queue_update($transient, $force=false) {
    $mepr_options = MeprOptions::fetch();

    if( $force or ( false === ( $update_info = get_site_transient('mepr_update_info') ) ) ) {
      if(empty($mepr_options->mothership_license))
      {
        // Just here to query for the current version
        $args = array();
        if( $mepr_options->edge_updates or ( defined( "MEMBERPRESS_EDGE" ) and MEMBERPRESS_EDGE ) )
          $args['edge'] = 'true';

        $version_info = self::send_mothership_request( "/versions/latest/developer", $args );
        $curr_version = $version_info['version'];
        $download_url = '';
      }
      else
      {
        try
        {
          $domain = urlencode(MeprUtils::site_domain());
          $args = compact('domain');

          if( $mepr_options->edge_updates or ( defined( "MEMBERPRESS_EDGE" ) and MEMBERPRESS_EDGE ) )
            $args['edge'] = 'true';

          $license_info = self::send_mothership_request("/versions/info/{$mepr_options->mothership_license}", $args, 'post');
          $curr_version = $license_info['version'];
          $download_url = $license_info['url'];
          set_site_transient( 'mepr_license_info',
                              $license_info,
                              MeprUtils::hours(12) );
        }
        catch(Exception $e)
        {
          try
          {
            // Just here to query for the current version
            $args = array();
            if( $mepr_options->edge_updates or ( defined( "MEMBERPRESS_EDGE" ) and MEMBERPRESS_EDGE ) )
              $args['edge'] = 'true';

            $version_info = self::send_mothership_request("/versions/latest/developer", $args);
            $curr_version = $version_info['version'];
            $download_url = '';
          }
          catch(Exception $e)
          {
            if(isset($transient->response[MEPR_PLUGIN_SLUG]))
              unset($transient->response[MEPR_PLUGIN_SLUG]);
            
            return $transient;
          }
        }
      }

      set_site_transient( 'mepr_update_info',
                          compact( 'curr_version', 'download_url' ),
                          MeprUtils::hours(12) );
    }
    else
      extract( $update_info );

    if(isset($curr_version) and version_compare($curr_version, MEPR_VERSION, '>'))
    {
      $transient->response[MEPR_PLUGIN_SLUG] = (object)array(
        'id'          => $curr_version,
        'slug'        => MEPR_PLUGIN_SLUG,
        'new_version' => $curr_version,
        'url'         => 'http://memberpress.com',
        'package'     => $download_url
      );
    }
    else
      unset( $transient->response[MEPR_PLUGIN_SLUG] );
    
    return $transient;
  }
  
  public static function manually_queue_update()
  {
    $transient = get_site_transient("update_plugins");
    set_site_transient("update_plugins", self::queue_update($transient, true));
  }
  
  public static function queue_button()
  {
    ?>
    <a href="<?php echo admin_url('admin.php?page=memberpress-options&action=queue&_wpnonce=' . wp_create_nonce('MeprUpdateController::manually_queue_update')); ?>" class="button"><?php _e('Check for Update', 'memberpress')?></a>
    <?php
  }
  
  public static function plugin_info($false, $action, $args)
  {
    global $wp_version;
    
    if(!isset($action) or $action != 'plugin_information')
      return false;
    
    if(isset( $args->slug) and !preg_match("#.*".$args->slug.".*#", MEPR_PLUGIN_SLUG))
      return false;
    
    $mepr_options = MeprOptions::fetch();
    
    if(empty($mepr_options->mothership_license))
    {
      // Just here to query for the current version
      $args = array();
      if( $mepr_options->edge_updates or ( defined( "MEMBERPRESS_EDGE" ) and MEMBERPRESS_EDGE ) )
        $args['edge'] = 'true';

      $version_info = self::send_mothership_request("/versions/latest/developer", $args);
      $curr_version = $version_info['version'];
      $version_date = $version_info['version_date'];
      $download_url = '';
    }
    else
    {
      try
      {
        $domain = urlencode(MeprUtils::site_domain());
        $args = compact('domain');

        if( $mepr_options->edge_updates or ( defined( "MEMBERPRESS_EDGE" ) and MEMBERPRESS_EDGE ) )
          $args['edge'] = 'true';

        $license_info = self::send_mothership_request("/versions/info/{$mepr_options->mothership_license}", $args, 'post');
        $curr_version = $license_info['version'];
        $version_date = $license_info['version_date'];
        $download_url = $license_info['url'];
      }
      catch(Exception $e)
      {
        try
        {
          $args = array();
          if( $mepr_options->edge_updates or ( defined( "MEMBERPRESS_EDGE" ) and MEMBERPRESS_EDGE ) )
            $args['edge'] = 'true';

          // Just here to query for the current version
          $version_info = self::send_mothership_request("/versions/latest/developer", $args);
          $curr_version = $version_info['version'];
          $version_date = $version_info['version_date'];
          $download_url = '';
        }
        catch(Exception $e)
        {
          if(isset($transient->response[MEPR_PLUGIN_SLUG]))
            unset($transient->response[MEPR_PLUGIN_SLUG]);
          
          return $transient;
        }
      }
    }
    
    return (object) array("slug" => MEPR_PLUGIN_NAME,
                          "name" => MEPR_DISPLAY_NAME,
                          "author" => '<a href="http://blairwilliams.com">' . MEPR_AUTHOR . '</a>',
                          "author_profile" => "http://blairwilliams.com",
                          "contributors" => array("Caseproof" => "http://caseproof.com"),
                          "homepage" => "http://memberpress.com",
                          "version" => $curr_version,
                          "new_version" => $curr_version,
                          "requires" => $wp_version,
                          "tested" => $wp_version,
                          "compatibility" => array($wp_version => array($curr_version => array( 100, 0, 0))),
                          "rating" => "100.00",
                          "num_ratings" => "1",
                          "downloaded" => "1000",
                          "added" => "2012-12-02",
                          "last_updated" => $version_date,
                          "tags" => array("membership" => __("Membership", 'memberpress'),
                                          "membership software" => __("Membership Software", 'memberpress'),
                                          "members" => __("Members", 'memberpress'),
                                          "payment" => __("Payment", 'memberpress'),
                                          "protection" => __("Protection", 'memberpress'),
                                          "rule" => __("Rule", 'memberpress'),
                                          "lock" => __("Lock", 'memberpress'),
                                          "access" => __("Access", 'memberpress'),
                                          "community" => __("Community", 'memberpress'),
                                          "admin" => __("Admin", 'memberpress'),
                                          "pages" => __("Pages", 'memberpress'),
                                          "posts" => __("Posts", 'memberpress'),
                                          "plugin" => __("Plugin", 'memberpress')),
                          "sections" => array("description" => "<p>" . MEPR_DESCRIPTION . "</p>",
                                              "faq" => "<p>" . sprintf(__('You can access in-depth information about MemberPress at %1$sthe MemberPress User Manual%2$s.', 'memberpress'), "<a href=\"http://memberpress.com/user-manual\">", "</a>") . "</p>", "changelog" => "<p>".__('No Additional information right now', 'memberpress')."</p>"),
                          "download_link" => $download_url );
  }
  
  public static function send_mothership_request( $endpoint,
                                                  $args=array(),
                                                  $method='get',
                                                  $domain='http://mothership.caseproof.com',
                                                  $blocking=true )
  {
    $uri = "{$domain}{$endpoint}";
    
    $arg_array = array( 'method'    => strtoupper($method),
                        'body'      => $args,
                        'timeout'   => 15,
                        'blocking'  => $blocking,
                        'sslverify' => false
                      );
    
    $resp = wp_remote_request($uri, $arg_array);
    
    // If we're not blocking then the response is irrelevant
    // So we'll just return true.
    if($blocking == false)
      return true;
    
    if(is_wp_error($resp))
      throw new Exception(__('You had an HTTP error connecting to Caseproof\'s Mothership API', 'memberpress'));
    else
    {
      if(null !== ($json_res = json_decode($resp['body'], true)))
      {
        if(isset($json_res['error']))
          throw new Exception($json_res['error']);
        else
          return $json_res;
      }
      else
        throw new Exception(__( 'Your License Key was invalid', 'memberpress'));
    }
    
    return false;
  }
  
  public static function enqueue_scripts($hook)
  {
    if($hook == 'memberpress_page_memberpress-activate')
    {
      wp_enqueue_style('mepr-activate-css', MEPR_CSS_URL.'/admin-activate.css', array(), MEPR_VERSION);
      wp_enqueue_script('mepr-activate-js', MEPR_JS_URL.'/admin_activate.js', array(), MEPR_VERSION);
    }
  }
  
  public static function activation_warning()
  {
    $mepr_options = MeprOptions::fetch();
    
    if(empty($mepr_options->mothership_license) and
       (!isset($_REQUEST['page']) or
         $_REQUEST['page']!='memberpress-activate'))
      require(MEPR_VIEWS_PATH.'/update/activation_warning.php');  
  }

  public static function mepr_edge_updates()
  {
    if(!is_super_admin() or !wp_verify_nonce($_POST['wpnonce'],'wp-edge-updates'))
      die(json_encode(array('error' => __('You do not have access.', 'memberpress'))));
    
    if(!isset($_POST['edge']))
      die(json_encode(array('error' => __('Edge updates couldn\'t be updated.', 'memberpress'))));

    $mepr_options = MeprOptions::fetch();
    $mepr_options->edge_updates = ($_POST['edge']=='true');
    $mepr_options->store(false);

    // Re-queue updates when this is checked
    self::manually_queue_update();

    die(json_encode(array('state' => ($mepr_options->edge_updates ? 'true' : 'false'))));
  }
} //End class

