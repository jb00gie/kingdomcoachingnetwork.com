<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprRulesController extends MeprCptController
{
  public function load_hooks()
  {
    add_filter('bulk_actions-edit-memberpressrule', 'MeprRulesController::disable_bulk');
    add_filter('post_row_actions', 'MeprRulesController::disable_row', 10, 2);
    add_action('admin_enqueue_scripts', 'MeprRulesController::enqueue_scripts');
    add_action('admin_init', 'MeprRule::cleanup_db'); //Clear out all unused auto-save's
    add_action('init', 'MeprRulesController::rule_widgets');
    add_filter('the_content_feed', 'MeprRulesController::rule_content', 999999, 1);
    add_filter('the_content', 'MeprRulesController::rule_content', 999999, 1);
    add_action('template_redirect', 'MeprRulesController::rule_redirection', 1);
    add_action('admin_init', 'MeprRulesController::admin_rule_redirection', 1);
    add_filter('comments_template', 'MeprRulesController::rule_comments');
    add_action('manage_posts_custom_column', 'MeprRulesController::custom_columns', 10, 2);
    add_filter('manage_edit-memberpressrule_columns', 'MeprRulesController::columns');
    add_action('save_post', 'MeprRulesController::save_postdata');
    add_action('mod_rewrite_rules', 'MeprRulesController::mod_rewrite_rules');
    add_action('wp_ajax_mepr_show_content_dropdown', 'MeprRulesController::display_content_dropdown');
    add_action('wp_ajax_mepr_rule_content_search', 'MeprRulesController::ajax_content_search');
    add_filter('default_title', 'MeprRulesController::get_page_title_code');
    
    // Add virtual capabilities
    add_filter( 'user_has_cap', 'MeprRulesController::authorized_cap', 10, 3 );
    add_filter( 'user_has_cap', 'MeprRulesController::product_authorized_cap', 10, 3 );

    add_shortcode('mepr-rule', 'MeprRulesController::protect_shortcode_content');
    add_shortcode('mepr-unauthorized-message', 'MeprRulesController::unauthorized_message_shortcode');

    // Cleanup list view
    add_filter('views_edit-'.MeprRule::$cpt, 'MeprAppController::cleanup_list_view' );
  }
  
  public function register_post_type()
  {
    register_post_type( MeprRule::$cpt,
                        array('labels' => array('name' => __('Rules', 'memberpress'),
                                                'singular_name' => __('Rule', 'memberpress'),
                                                'add_new_item' => __('Add New Rule', 'memberpress'),
                                                'edit_item' => __('Edit Rule', 'memberpress'),
                                                'new_item' => __('New Rule', 'memberpress'),
                                                'view_item' => __('View Rule', 'memberpress'),
                                                'search_items' => __('Search Rules', 'memberpress'),
                                                'not_found' => __('No Rules found', 'memberpress'),
                                                'not_found_in_trash' => __('No Rules found in Trash', 'memberpress'),
                                                'parent_item_colon' => __('Parent Rule:', 'memberpress')
                                               ),
                              'public' => false,
                              'show_ui' => true,
                              'show_in_menu' => 'memberpress',
                              'capability_type' => 'post',
                              'hierarchical' => false,
                              'register_meta_box_cb' => 'MeprRulesController::add_meta_boxes',
                              'rewrite' => false,
                              'supports' => array('title')
                              )
                      );
  }
  
  //Set an initial page title
  public static function get_page_title_code($title)
  {
    global $current_screen;
    
    if(empty($title) && $current_screen->post_type == MeprRule::$cpt)
      return __('All Content: ', 'memberpress');
    else
      return $title;
  }
  
  public static function columns($columns)
  {
    $columns = array(
      "cb" => "<input type=\"checkbox\" />",
      "title" => __("Title", 'memberpress'),
      "rule-type" => __("Type", 'memberpress'),
      "rule-content" => __("Content", 'memberpress'),
      "rule-products" => __("Products", 'memberpress')
    );

    return $columns;
  }
  
  public static function custom_columns($column, $rule_id)
  {
    $rule = new MeprRule($rule_id);
    
    if($rule->ID !== null)
    {
      $rule_contents = MeprRule::get_contents_array($rule->mepr_type);

      $types = MeprRule::get_types();
      
      if("ID" == $column)
        echo $rule->ID;
      else if("rule-type" == $column and isset($types[$rule->mepr_type]))
        echo $types[$rule->mepr_type];
      else if("rule-content" == $column and $rule->mepr_type != 'custom' and
              isset($rule_contents[$rule->mepr_content]))
        echo $rule_contents[$rule->mepr_content];
      else if("rule-content" == $column and $rule->mepr_type == 'custom' and
              isset($rule->mepr_content))
        echo $rule->mepr_content;
      else if("rule-content" == $column and
              strstr($rule->mepr_type, 'all_') !== false and
              isset($rule->mepr_content))
        echo __('Except', 'memberpress') . ': ' . $rule->mepr_content;
      else if("rule-products" == $column)
        echo implode(', ', $rule->get_formatted_accesses());
    }
  }
  
  public static function rule_comments($template = '')
  {
    $current_post = MeprUtils::get_current_post();
    
    $mepr_options = MeprOptions::fetch();
    
    if(isset($current_post))
      if(MeprRule::is_locked($current_post))
        return MEPR_VIEWS_PATH.'/shared/unauthorized_comments.php';
    
    return $template;
  }
  
  /** Used to redirect unauthorized visitors if redirect_on_unauthorized is selected in MeprOptions or
  if we're protecting a WP controlled-URI. */
  public static function rule_redirection()
  {
    global $post;

    $uri = $_SERVER['REQUEST_URI'];
    $mepr_options = MeprOptions::fetch();
    $delim = MeprAppController::get_param_delimiter_char($mepr_options->unauthorized_redirect_url);
    
    //Add this filter to allow external resources
    //to control whether to redirect away from this content
    //if the resource sets the filter to FALSE then no redirect will occur
    if(!apply_filters('mepr-pre-run-rule-redirection', true, $uri, $delim))
      return;
    
    // Let's check the URI's first ok?
    // This is here to perform an unauthorized redirection based on the uri
    if(MeprRule::is_uri_locked($uri))
    {
      if($mepr_options->redirect_on_unauthorized) //Send to unauth page
        $redirect_to = "{$mepr_options->unauthorized_redirect_url}{$delim}action=mepr_unauthorized&redirect_to={$uri}";
      else //Send to login page
        $redirect_to = $mepr_options->login_page_url("action=mepr_unauthorized&redirect_to=".urlencode($uri));

      MeprUtils::wp_redirect($redirect_to);
      exit;
    }

    // If the URI isn't protected, let's check the other Rules
    if($mepr_options->redirect_on_unauthorized)
    {
      $do_redirect = apply_filters('mepr-rule-do-redirection', self::should_do_redirect());
      
      if((!is_singular() and $do_redirect) or ($do_redirect and isset($post) and MeprRule::is_locked($post)))
      {
        MeprUtils::wp_redirect("{$mepr_options->unauthorized_redirect_url}{$delim}mepr-unauth-page={$post->ID}&redirect_to={$uri}");
        exit;
      }
    }
  }
  
  //Allow control of the admin dashboard URL's too
  public static function admin_rule_redirection() {
    $uri = $_SERVER['REQUEST_URI'];
    $mepr_options = MeprOptions::fetch();
    $delim = MeprAppController::get_param_delimiter_char($mepr_options->unauthorized_redirect_url);
    
    // This performs an unauthorized redirection based on the uri
    if(MeprRule::is_uri_locked($uri))
    {
      if($mepr_options->redirect_on_unauthorized) //Send to unauth page
        $redirect_to = "{$mepr_options->unauthorized_redirect_url}{$delim}action=mepr_unauthorized&redirect_to={$uri}";
      else //Send to login page
        $redirect_to = $mepr_options->login_page_url("action=mepr_unauthorized&redirect_to=".urlencode($uri));
      
      MeprUtils::wp_redirect($redirect_to);
      exit;
    }
  }
  
  public static function should_do_redirect() {
    global $wp_query;
    $mepr_options = MeprOptions::fetch();
    
    if(!empty($wp_query->posts) && $mepr_options->redirect_non_singular) {
      //If even one post on this non-singular page is protected, let's redirect brotha
      foreach($wp_query->posts as $post)
        if(MeprRule::is_locked($post))
          return true;
    }
    
    return is_singular();
  }
  
  /** Used to replace content for unauthorized visitors if redirect_on_unauthorized is not selected in MeprOptions. */
  public static function rule_content($content)
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
    
    //Get the URI
    $uri = $_SERVER['REQUEST_URI'];
    
    //Add this filter to allow external resources
    //to control whether to show or hide this content
    //if the resource sets the filter to FALSE then it will not be hidden
    if(!apply_filters('mepr-pre-run-rule-content', true, $current_post, $uri)) {
      //See notes above
      $new_content[$current_post->ID] = $content;
      return $new_content[$current_post->ID];
    }
    
    if((isset($current_post) and MeprRule::is_locked($current_post)) or (MeprRule::is_uri_locked($uri)))
      $content = do_shortcode(self::unauthorized_message($current_post));
    
    //See notes above
    $new_content[$current_post->ID] = $content;
    return $content;
  }
  
  public static function rule_widgets()
  {
    global $wp_registered_widgets;
    
    if(!empty($wp_registered_widgets))
      foreach($wp_registered_widgets as $id => $data)
        if(MeprRule::is_widget_locked($id))
          wp_unregister_sidebar_widget($id);
  }
  
  public static function unauthorized_message_shortcode($atts = '')
  {
    $mepr_options = MeprOptions::fetch();
    
    if( isset($_REQUEST['mepr-unauth-page']) and 
        is_numeric($_REQUEST['mepr-unauth-page']) and
        $post = get_post(esc_html($_REQUEST['mepr-unauth-page'])) )
      return self::unauthorized_message($post);
    else if(isset($GLOBALS['post']))
      return self::unauthorized_message($GLOBALS['post']);
    else
      return wpautop($mepr_options->unauthorized_message);
  }
  
  public static function unauthorized_message($post)
  {
    $mepr_options = MeprOptions::fetch();
    $unauth = MeprRule::get_unauth_settings_for($post);

    static $login_form_shown = false;
    $show_login = ($unauth->show_login && !$login_form_shown);
    if($show_login) { $login_form_shown = true; }

    $form = apply_filters('mepr-unauthorized-login-form', MeprUsersController::render_login_form(null, null, true), $post);

    ob_start();
    require(MEPR_VIEWS_PATH . '/shared/unauthorized_message.php');
    $content = ob_get_clean();

    // TODO: oEmbed still not working for some strange reason
    return apply_filters('mepr-unauthorized-content', $content, $post);
  }
  
  public static function add_meta_boxes()
  {
    add_meta_box("memberpress-rule-meta", __("Rule Options", "memberpress"), "MeprRulesController::rule_meta_box", MeprRule::$cpt, "normal", "high");
    add_meta_box("memberpress-rule-drip", __("Drip / Expiration", "memberpress"), "MeprRulesController::rule_drip_meta_box", MeprRule::$cpt, "normal", "high");
    add_meta_box("memberpress-rule-unauth", __("Unauthorized Access", "memberpress"), "MeprRulesController::rule_unauth_meta_box", MeprRule::$cpt, "normal", "high");
  }
  
  public static function save_postdata($post_id)
  {
    $post = get_post($post_id);
    
    if(!wp_verify_nonce((isset($_POST[MeprRule::$mepr_nonce_str]))?$_POST[MeprRule::$mepr_nonce_str]:'', MeprRule::$mepr_nonce_str.wp_salt()))
      return $post_id; //Nonce prevents meta data from being wiped on move to trash
    
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
      return $post_id;
    
    if(defined('DOING_AJAX'))
      return;
    
    if(!empty($post) && $post->post_type == MeprRule::$cpt)
    {
      $rule = new MeprRule($post_id);
      $rule->mepr_type           = $_POST[MeprRule::$mepr_type_str];
      $rule->mepr_content        = $_POST[MeprRule::$mepr_content_str];
      $rule->mepr_access         = $_POST[MeprRule::$mepr_access_str];
      $rule->drip_enabled        = isset($_POST[MeprRule::$drip_enabled_str]);
      $rule->drip_amount         = $_POST[MeprRule::$drip_amount_str];
      $rule->drip_unit           = $_POST[MeprRule::$drip_unit_str];
      $rule->drip_after          = $_POST[MeprRule::$drip_after_str];
      $rule->drip_after_fixed    = $_POST[MeprRule::$drip_after_fixed_str];
      $rule->expires_enabled     = isset($_POST[MeprRule::$expires_enabled_str]);
      $rule->expires_amount      = $_POST[MeprRule::$expires_amount_str];
      $rule->expires_unit        = $_POST[MeprRule::$expires_unit_str];
      $rule->expires_after       = $_POST[MeprRule::$expires_after_str];
      $rule->expires_after_fixed = $_POST[MeprRule::$expires_after_fixed_str];
      $rule->unauth_excerpt_type = $_POST[MeprRule::$unauth_excerpt_type_str];
      $rule->unauth_excerpt_size = $_POST[MeprRule::$unauth_excerpt_size_str];
      $rule->unauth_message_type = $_POST[MeprRule::$unauth_message_type_str];
      $rule->unauth_message      = $_POST[MeprRule::$unauth_message_str];
      $rule->unauth_login        = $_POST[MeprRule::$unauth_login_str];
      $rule->auto_gen_title      = ($_POST[MeprRule::$auto_gen_title_str] == 'true');
      
      $rule->is_mepr_content_regexp = isset($_POST[MeprRule::$is_mepr_content_regexp_str]);
      
      $rule->store_meta();
      
      // Ensure that the rewrite rules are flushed & in place
      // No longer needed -- killing as of 1.1.4 due to a timing issue with other plugins not having added their rules yet
      // MeprUtils::flush_rewrite_rules();
    }
  }
  
  public static function rule_meta_box()
  {
    global $post_id;
    
    $rule = new MeprRule($post_id);
    $server = strtolower($_SERVER['SERVER_SOFTWARE']);
    
    if(preg_match('/apache/',$server))
    {
      $server = 'apache';
      $htaccess = ABSPATH . ".htaccess";
      $htaccess_writable = (file_exists($htaccess) and is_writable($htaccess));
    }
    else if(preg_match('/nginx/',$server))
      $server = 'nginx';
    else
      $server = 'unknown';
    
    require(MEPR_VIEWS_PATH.'/rules/form.php');
  }
  
  public static function rule_drip_meta_box()
  {
    global $post_id;
    
    $rule = new MeprRule($post_id);
    
    require(MEPR_VIEWS_PATH.'/rules/drip-form.php');
  }

  public static function rule_unauth_meta_box()
  {
    global $post_id;

    $rule = new MeprRule($post_id);

    require(MEPR_VIEWS_PATH.'/rules/unauth-meta-box.php');
  }

  public static function display_content_dropdown()
  {
    if(!isset($_POST['field_name']) || !isset($_POST['type']))
      die(__('Error', 'memberpress'));

    if(MeprUtils::is_logged_in_and_an_admin())
      MeprRulesHelper::content_dropdown($_POST['field_name'], '', $_POST['type']);

    die();
  }

  public static function disable_row($actions, $post)
  {
    global $current_screen;
    
    if($current_screen->post_type != MeprRule::$cpt)
      return $actions;
    
    unset($actions['inline hide-if-no-js']); //Hides quick-edit
    
    return $actions;
  }
  
  public static function disable_bulk($actions)
  {
    unset($actions['edit']); //disables bulk edit
    
    return $actions;
  }
  
  public static function enqueue_scripts($hook)
  {
    global $current_screen, $wp_scripts;
    
    $ui = $wp_scripts->query('jquery-ui-core');
    $url = "//ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.css";
    
    if($current_screen->post_type == MeprRule::$cpt)
    {
      $rules_json = array( 'mepr_no_products_message' => __('Please select at least one Product before saving.', 'memberpress'),
                           'types' => MeprRule::get_types() );
      wp_enqueue_style('mepr-jquery-ui-smoothness', $url);
      wp_enqueue_script('mepr-date-picker-js', MEPR_JS_URL.'/date_picker.js', array('jquery-ui-datepicker'), MEPR_VERSION);
      wp_dequeue_script('autosave'); //Disable auto-saving
      //Need mepr-rules-js to load in the footer since this script doesn't fully use document.ready()
      wp_enqueue_script('mepr-rules-js', MEPR_JS_URL.'/admin_rules.js', array('jquery','jquery-ui-autocomplete'), MEPR_VERSION, true);
      wp_enqueue_style('mepr-rules-css', MEPR_CSS_URL.'/admin-rules.css', array(), MEPR_VERSION);
      wp_localize_script('mepr-rules-js', 'MeprRule', $rules_json);
    }
  }
  
  public static function mod_rewrite_rules($rules)
  {
    $mepr_options = MeprOptions::fetch();
    
    //If disabled mod_rewrite is checked let's not go on
    if($mepr_options->disable_mod_rewrite)
      return $rules;
    
    $rule_uri = MEPR_URL . '/lock.php';
    $rule_path = preg_replace('#^https?://[^/]+#','',$rule_uri); // grab the root
    $subdir = preg_replace("#^https?://[^/]+#", '', site_url());
    $mepr_rules = "\n";
    $mepr_rules .= "# BEGIN MemberPress Rules\n";
    $mepr_rules .= "<IfModule mod_rewrite.c>\n\n";
    
    // Make sure there's been a cookie set for us to access the file
    $mepr_rules .= "RewriteCond %{HTTP_COOKIE} mplk=([a-zA-Z0-9]+)\n";
    
    // See if there's also a rule file for the rule hash
    $mepr_rules .= "RewriteCond " . MeprRule::rewrite_rule_file_dir() . "/%1 -f\n";
    // If rule hash exists in query string, there's a rule file and they match then short circuit to the actual url
    $mepr_rules .= "RewriteRule ^(.*)$ - [L]\n\n";
    // If the url is the lock url then don't lock it or we'll end up in an infinite redirect
    // Don't need this now that we're bypassing php files alltogether
    //$mepr_rules .= "RewriteRule memberpress\/lock\.php$ - [L]\n";
    
    // Eventually we can maybe make this configurable by the user ... but for now, letting 
    // them protect these assets would push the performance of their sites to pretty much nil
    $mepr_rules .= "RewriteCond %{REQUEST_URI} !^/(wp-admin|wp-includes)\n";
    $mepr_rules .= "RewriteCond %{REQUEST_URI} !\.(txt|php|phtml|jpg|jpeg|gif|css|png|js|ico|svg|woff|ttf|xml|TXT|PHP|PHTML|JPG|JPEG|GIF|CSS|PNG|JS|ICO|SVG|WOFF|TTF|XML)\n";
    
    // All else fails ... run it through lock.php to see if it's protected
    $mepr_rules .= "RewriteRule . {$rule_path} [L]\n\n";
    $mepr_rules .= "</IfModule>\n";
    $mepr_rules .= "# END MemberPress Rules\n";
    
    // Mepr rules must appear *AFTER* wp's rules because we
    // don't know how wp will handle the uri unless its a file
    return $rules.$mepr_rules;
  }
  
  public static function protect_shortcode_content($atts, $content = '')
  {
    //Allow single level shortcode nesting
    //This only works if the inner shortcode does NOT have an ending tag
    $content = do_shortcode($content);
    
    $hide_if_allowed = (isset($atts['ifallowed']) && $atts['ifallowed'] == 'hide');
    
    //Check if we've been given sanitary input, if not this shortcode
    //is no good so let's return the full content here
    if(!is_numeric($atts['id']) || (int)$atts['id'] <= 0)
      return $content;
    
    //Check if user is logged in if the user is not logged in and $hide_if_allowed is true,
    //then return the full content, otherwise return nothing
    if(!MeprUtils::is_user_logged_in())
      return ($hide_if_allowed)?$content:"";
    
    //No sense loading the rule until we know the user is logged in
    $rule = new MeprRule($atts['id']);
    
    //If rule doesn't exist, has no products associated with it, or
    //we're an Admin let's return the full content
    if(!isset($rule->ID) || (int)$rule->ID <= 0 || empty($rule->mepr_access) || is_super_admin())
      return ($hide_if_allowed)?"":$content;
    
    //Now we know the user is logged in and the rule is valid
    //let's see if they have purchased one of the products listed in this rule
    $user = MeprUtils::get_currentuserinfo();
    $subscriptions = $user->active_product_subscriptions();
    $intersect = array_intersect($rule->mepr_access, $subscriptions);
    
    //If intersection is empty, user has no access
    if(empty($intersect))
      return ($hide_if_allowed)?$content:"";
    
    //Now lets check for drips/expirations to make sure this user has access
    if($rule->has_dripped() && !$rule->has_expired())
      return ($hide_if_allowed)?"":$content;
    
    //If we made it here, the user doesn't have any access due to drips/expires
    return ($hide_if_allowed)?$content:"";
  }
  
  /* This will only work once $post is in place in the wp request flow */
  public static function authorized_cap($capabilities, $caps, $args)
  {
    global $post;
    
    $user_authorized_str = 'memberpress_authorized';
    $capabilities[$user_authorized_str] = 1;
    
    if(!in_array($user_authorized_str, $caps))
      return $capabilities;
    
    if((isset($current_post) and MeprRule::is_locked($current_post)) or (MeprRule::is_uri_locked($_SERVER['REQUEST_URI'])))
      unset($capabilities[$user_authorized_str]);
    
    return $capabilities;
  }
  
  /* Product based capabilities */
  public static function product_authorized_cap($capabilities, $caps, $args)
  {
    if(MeprUtils::is_user_logged_in())
    {
      $user = MeprUtils::get_currentuserinfo();
      $ids = $user->active_product_subscriptions();
      
      foreach($ids as $id) 
        $capabilities["memberpress_product_authorized_{$id}"] = 1;
    }
    
    return $capabilities;
  }

  public static function ajax_content_search() {
    //Array( [action] => mepr_rule_content_search [type] => single_post [term] => you) 

    $data = MeprRule::search_content( $_REQUEST['type'], $_REQUEST['term'] );
    die(json_encode($data));
  }
} //End class
