<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprProductsController extends MeprCptController
{
  public function load_hooks()
  {
    add_action('admin_enqueue_scripts', 'MeprProductsController::enqueue_scripts');
    add_action('manage_pages_custom_column', 'MeprProductsController::custom_columns', 10, 2);
    add_filter('manage_edit-memberpressproduct_columns', 'MeprProductsController::columns');
    add_filter('template_include', 'MeprProductsController::template_include');
    add_action('save_post', 'MeprProductsController::save_postdata');
    add_filter('the_content', 'MeprProductsController::display_registration_form');
    add_action('init', 'MeprProduct::cleanup_db');
    add_action('before_delete_post', 'MeprProductsController::nullify_records_on_delete');
    add_filter('login_redirect', 'MeprProductsController::track_and_override_login_redirect_wp', 999999, 3);
    add_filter('mepr-process-login-redirect-url', 'MeprProductsController::track_and_override_login_redirect_mepr', 10, 2);
    add_shortcode('mepr-product-link', 'MeprProductsController::shortcode_product_link');
    add_shortcode('mepr-product-registration-form', 'MeprProductsController::shortcode_registration_form');
    add_shortcode('mepr-product-purchased', 'MeprProductsController::shortcode_if_product_was_purchased');
    add_action('wp_ajax_mepr_get_product_price_str', 'MeprProductsController::get_price_str_ajax');

    // Cleanup list view
    add_filter('views_edit-'.MeprProduct::$cpt, 'MeprAppController::cleanup_list_view' );
  }
  
  public function register_post_type()
  {
    $mepr_options = MeprOptions::fetch();
    register_post_type( MeprProduct::$cpt,
                        array('labels' => array('name' => __('Products', 'memberpress'),
                                                'singular_name' => __('Product', 'memberpress'),
                                                'add_new_item' => __('Add New Product', 'memberpress'),
                                                'edit_item' => __('Edit Product', 'memberpress'),
                                                'new_item' => __('New Product', 'memberpress'),
                                                'view_item' => __('View Product', 'memberpress'),
                                                'search_items' => __('Search Product', 'memberpress'),
                                                'not_found' => __('No Product found', 'memberpress'),
                                                'not_found_in_trash' => __('No Product found in Trash', 'memberpress'),
                                                'parent_item_colon' => __('Parent Product:', 'memberpress')
                                                ),
                              'public' => true,
                              'show_ui' => true,
                              'show_in_menu' => 'memberpress',
                              'capability_type' => 'page',
                              'hierarchical' => true,
                              'register_meta_box_cb' => 'MeprProductsController::add_meta_boxes',
                              'rewrite' => array("slug" => $mepr_options->product_pages_slug, "with_front" => false),
                              'supports' => array('title', 'editor', 'page-attributes', 'comments')
                              )
                      );
  }
  
  public static function columns($columns)
  {
    $columns = array(
      "cb" => "<input type=\"checkbox\" />",
      "title" => __("Product Title", 'memberpress'),
      "terms" => __("Terms", 'memberpress'),
      "url" => __('URL', 'memberpress')
    );
    return $columns;
  }
  
  public static function custom_columns($column, $post_id)
  {
    $mepr_options = MeprOptions::fetch();
    $product = new MeprProduct($post_id);
    
    if($product->ID !== null)
    {
      if("ID" == $column) {
        echo $product->ID;
      }
      elseif("terms" == $column) {
        echo MeprProductsHelper::format_currency($product);
      }
      elseif("url" == $column) {
        echo $product->url();
      }
    }
  }
  
  // Template selection
  public static function template_include($template)
  {
    global $post, $wp_query;
    
    if(isset($post) && is_a($post, 'WP_Post') && $post->post_type == MeprProduct::$cpt) {
      $product = new MeprProduct($post->ID);
      $new_template = $product->get_page_template();
    }

    if(isset($new_template) && !empty($new_template))
      return $new_template;
    
    return $template;
  }
  
  public static function add_meta_boxes()
  {
    global $post_id;
    
    $product = new MeprProduct($post_id);
    
    add_meta_box("memberpress-product-meta", __('Product Terms', 'memberpress'), "MeprProductsController::product_meta_box", MeprProduct::$cpt, "side", "high", array('product' => $product));
    
    add_meta_box("memberpress-custom-template", __('Custom Page Template', 'memberpress'), "MeprProductsController::custom_page_template", MeprProduct::$cpt, "side", "default", array('product' => $product));
    
    add_meta_box("memberpress-product-options", __('Product Options', 'memberpress'), "MeprProductsController::product_options_meta_box", MeprProduct::$cpt, "normal", "high", array('product' => $product));

    do_action('mepr-product-meta-boxes', $product);
  }
  
  public static function save_postdata($post_id)
  {
    $post = get_post($post_id);
    
    if(!wp_verify_nonce((isset($_POST[MeprProduct::$nonce_str]))?$_POST[MeprProduct::$nonce_str]:'', MeprProduct::$nonce_str.wp_salt()))
      return $post_id; //Nonce prevents meta data from being wiped on move to trash
    
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
      return $post_id;

    if(defined('DOING_AJAX'))
      return;

    if(!empty($post) && $post->post_type == MeprProduct::$cpt)
    {
      $product = new MeprProduct($post_id);

      extract($_POST);

      $product->price = (isset($_mepr_product_price))?$_mepr_product_price:$product->attrs['price'];
      $product->period = (isset($_mepr_product_period))?$_mepr_product_period:$product->attrs['period'];
      $product->period_type = (isset($_mepr_product_period_type))?$_mepr_product_period_type:$product->attrs['period_type'];
      $product->signup_button_text = (isset($_mepr_product_signup_button_text))?$_mepr_product_signup_button_text:$product->attrs['signup_button_text'];
      $product->limit_cycles = isset($_mepr_product_limit_cycles);
      $product->limit_cycles_num = (isset($_mepr_product_limit_cycles_num))?$_mepr_product_limit_cycles_num:$product->attrs['limit_cycles_num'];
      $product->limit_cycles_action = (isset($_mepr_product_limit_cycles_action)?$_mepr_product_limit_cycles_action:$product->attrs['limit_cycles_action']);
      $product->trial = isset($_mepr_product_trial);
      $product->trial_days = (isset($_mepr_product_trial_days))?$_mepr_product_trial_days:$product->attrs['trial_days'];
      $product->trial_amount = (isset($_mepr_product_trial_amount))?$_mepr_product_trial_amount:$product->attrs['trial_amount'];
      $product->who_can_purchase = self::get_who_can_purchase_array();
      $product->is_highlighted = isset($_mepr_product_is_highlighted);
      $product->pricing_title = (isset($_mepr_product_pricing_title))?$_mepr_product_pricing_title:$product->attrs['pricing_title'];
      $product->pricing_show_price = isset($_mepr_product_pricing_show_price);
      $product->pricing_heading_txt = (isset($_mepr_product_pricing_heading_text))?$_mepr_product_pricing_heading_text:$product->attrs['pricing_heading_text'];
      $product->pricing_footer_txt = (isset($_mepr_product_pricing_footer_text))?$_mepr_product_pricing_footer_text:$product->attrs['pricing_footer_txt'];
      $product->pricing_button_txt = (isset($_mepr_product_pricing_button_text))?$_mepr_product_pricing_button_text:$product->attrs['pricing_button_txt'];
      $product->pricing_benefits = (isset($_mepr_product_pricing_benefits))?$_mepr_product_pricing_benefits:$product->attrs['pricing_benefits'];
      $product->register_price_action = (isset($_mepr_register_price_action))?$_mepr_register_price_action:$product->attrs['register_price_action'];
      $product->register_price = (isset($_mepr_register_price))?$_mepr_register_price:$product->attrs['register_price'];
      $product->thank_you_page_enabled = isset($_mepr_thank_you_page_enabled);
      $product->thank_you_message = (isset($meprproductthankyoumessage) && !empty($meprproductthankyoumessage))?stripslashes($meprproductthankyoumessage):$product->attrs['thank_you_message'];
      $product->simultaneous_subscriptions = isset($_mepr_allow_simultaneous_subscriptions);
      $product->use_custom_template = isset($_mepr_use_custom_template);
      $product->custom_template = isset($_mepr_custom_template)?$_mepr_custom_template:$product->attrs['custom_template'];
      $product->customize_payment_methods = isset($_mepr_customize_payment_methods);
      $product->custom_payment_methods = json_decode(stripslashes($_POST['mepr-product-payment-methods-json']));
      $product->custom_login_urls_enabled = isset($_mepr_custom_login_urls_enabled);
      $product->expire_type = $_POST[MeprProduct::$expire_type_str];
      $product->expire_after = $_POST[MeprProduct::$expire_after_str];
      $product->expire_unit = $_POST[MeprProduct::$expire_unit_str];
      $product->expire_fixed = $_POST[MeprProduct::$expire_fixed_str];
      $product->access_url = isset($_mepr_access_url)?stripslashes($_mepr_access_url):$product->attrs['access_url'];

      // Notification Settings
      $emails = array();
      foreach( $_POST[MeprProduct::$emails_str] as $email => $vals ) {
        $emails[$email] = array( 'enabled' => isset( $vals['enabled'] ),
                                          'use_template' => isset( $vals['use_template'] ),
                                          'subject' => stripslashes( $vals['subject'] ),
                                          'body' => stripslashes( $vals['body'] ) );
      }
      $product->emails = $emails;

      if($product->custom_login_urls_enabled)
        $product = self::set_custom_login_urls($product);
      
      $product = self::validate_product($product);
      $product->store_meta(); // only storing metadata here
      
      //Some themes rely on this meta key to be set to use the custom template, and they don't use locate_template
      if($product->use_custom_template && !empty($product->custom_template))
        update_post_meta($product->ID, '_wp_page_template', $product->custom_template);
      else
        update_post_meta($product->ID, '_wp_page_template', '');
      
      do_action('mepr-product-save-meta', $product);
    }
  }
  
  public static function set_custom_login_urls($product)
  {
    extract($_POST);
    
    $custom_login_urls = array();
    
    $product->custom_login_urls_default = (isset($_mepr_custom_login_urls_default) && !empty($_mepr_custom_login_urls_default))?stripslashes($_mepr_custom_login_urls_default):'';
    
    if(isset($_mepr_custom_login_urls) && !empty($_mepr_custom_login_urls))
      foreach($_mepr_custom_login_urls as $i => $url)
        $custom_login_urls[] = (object)array('url' => stripslashes($url), 'count' => (int)$_mepr_custom_login_urls_count[$i]);
    
    $product->custom_login_urls = $custom_login_urls;
    
    return $product;
  }
  
  public static function get_who_can_purchase_array()
  {
    $rows = array();
    
    if(empty($_POST[MeprProduct::$who_can_purchase_str.'-user_type']))
      return $rows;
    
    $count = count($_POST[MeprProduct::$who_can_purchase_str.'-user_type']) - 1;
    for($i = 0; $i < $count; $i++)
    {
      $user_type = $_POST[MeprProduct::$who_can_purchase_str.'-user_type'][$i];
      $product_id = $_POST[MeprProduct::$who_can_purchase_str.'-product_id'][$i];
      $rows[] = (object)array('user_type' => $user_type, 'product_id' => $product_id);
    }
    
    return $rows;
  }
  
  public static function validate_product($product)
  {
    //Validate Periods
    if($product->period_type == 'weeks' && $product->period > 52)
      $product->period = 52;
    
    if($product->period_type == 'months' && $product->period > 12)
      $product->period = 12;
    
    if(!is_numeric($product->period) || $product->period <= 0 || empty($product->period))
      $product->period = 1;
    
    if(!is_numeric($product->trial_days) || $product->trial_days <= 0 || empty($product->trial_days))
      $product->trial_days = 0;
    
    if($product->trial_days > 365)
      $product->trial_days = 365;
    
    //Validate Prices
    if(!is_numeric($product->price) || $product->price < 0.00)
      $product->price = 0.00;
    
    if(!is_numeric($product->trial_amount) || $product->trial_amount < 0.00)
      $product->trial_amount = 0.00;
    
    //Disable trial && cycles limit if lifetime is set and set period to 1
    if($product->period_type == 'lifetime')
    {
      $product->limit_cycles = false;
      $product->trial = false;
      $product->period = 1;
    }
    
    //Cycles limit must be positive
    if(empty($product->limit_cycles_num) || !is_numeric($product->limit_cycles_num) || $product->limit_cycles_num <= 0)
      $product->limit_cycles_num = 2;
    
    //If price = 0.00 and period type is not lifetime, we need to disable cycles and trials
    if($product->price == 0.00 && $product->period_type != 'lifetime')
    {
      $product->limit_cycles = false;
      $product->trial = false;
    }
    
    //Handle delayed expirations on one-time payments
    if($product->period_type == 'lifetime' && $product->expire_type == 'delay')
    {
      if(!is_numeric($product->expire_after) || $product->expire_after < 0)
        $product->expire_after = 1;
      
      if(!in_array($product->expire_unit, array('days', 'weeks', 'months', 'years')))
        $product->expire_unit = 'days';
      
      //We don't really need to validate these #'s down to their max -- give the user more flexibility
      /* if($product->expire_unit == 'days' && $product->expire_after > 365)
        $product->expire_after = 365;
      
      if($product->expire_unit == 'weeks' && $product->expire_after > 52)
        $product->expire_after = 52;
      
      if($product->expire_unit == 'months' && $product->expire_after > 12)
        $product->expire_after = 12; */
    }
    
    //Handle fixed expirations on one-time payments
    if($product->period_type == 'lifetime' && $product->expire_type == 'fixed')
    {
      if(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $product->expire_fixed, $datebit)) {
        if(!checkdate($datebit[2] , $datebit[3] , $datebit[1])) {
          $product->expire_type = 'none'; //an invalid date was set, so let's just make this a lifetime
        }
      } else {
        $product->expire_type = 'none'; //an invalid date was set, so let's just make this a lifetime
      }
    }
    
    return $product;
  }
  
  //Don't use $post here, it is null on new product - use args instead
  public static function product_meta_box($post, $args)
  {
    $product = $args['args']['product'];
    
    require(MEPR_VIEWS_PATH.'/products/form.php');
  }
  
  //Don't use $post here, it is null on new product - use args instead
  public static function product_options_meta_box($post, $args)
  {
    $product = $args['args']['product'];
    
    require(MEPR_VIEWS_PATH.'/products/product_options_meta_box.php');
  }
  
  //Don't use $post here, it is null on new product - use args instead
  public static function custom_page_template($post, $args)
  {
    $product = $args['args']['product'];
    
    require(MEPR_VIEWS_PATH.'/products/custom_page_template_form.php');
  }
  
  public static function display_registration_form($content, $manual = false)
  {
    global $user_ID;
    $mepr_options = MeprOptions::fetch();
    $current_post = MeprUtils::get_current_post();
    
    //WARNING the_content CAN be run more than once per page load
    //so this static var prevents stuff from happening twice
    //like cancelling a subscr or resuming etc...
    static $already_run = array();
    static $new_content = array();
    //Init this posts static values
    $already_run[$current_post->ID] = false;
    $new_content[$current_post->ID] = '';
    
    if($already_run[$current_post->ID] && !$manual) //Let shortcodes through -- $manual
      return $new_content[$current_post->ID];
    
    $already_run[$current_post->ID] = true;
    
    if(isset($current_post) && is_a($current_post, 'WP_Post') && $current_post->post_type == MeprProduct::$cpt)
    {
      if(post_password_required($current_post)) {
        //See notes above
        $new_content[$current_post->ID] = $content;
        return $content;
      }
      
      $prd = new MeprProduct($current_post->ID);
      
      //Short circuiting for any of the following reasons
      if( $prd->ID === null || //Bad product for some reason
          (!$manual && $prd->manual_append_signup()) || //the_content filter and show manually is enabled
          ($manual && !$prd->manual_append_signup()) ) //do_shortcode and show manually is disabled
      {
        //See notes above
        $new_content[$current_post->ID] = $content;
        return $content;
      }
      
      // We want to render this form after processing the signup form unless
      // there were errors and when trying to process the paymet form
      if(isset($_REQUEST) and
          ((isset($_POST['mepr_process_signup_form']) and !isset($_POST['errors'])) or
            isset($_POST['mepr_process_payment_form']) or
            (isset($_GET['renew']) and isset($_GET['tid']) and isset($_GET['uid']))))
      {
        ob_start();
        MeprUsersController::display_payment_form();
        //See notes above
        $new_content[$current_post->ID] = ob_get_clean();
        return $new_content[$current_post->ID];
      }
      
      $res = self::get_registration_form($prd);
      if($res->enabled)
        $content .= $res->content;
      else
        $content = $res->content;
    }
    
    //See notes above
    $new_content[$current_post->ID] = $content;
    return $content;
  }
  
  public static function get_registration_form($prd) {
    global $user_ID;
    $mepr_options = MeprOptions::fetch();

    //If the member has already purchased this product, and cannot purchase it again
    //Show the Access URL for the product if it's set
    if($user_ID && !$prd->simultaneous_subscriptions && !empty($prd->access_url))
    {
      $user = new MeprUser($user_ID);

      if($user->is_already_subscribed_to($prd->ID))
      {
        $access_url_str = '<p class="mepr-product-access-url">'.__('You have already purchased this item', 'memberpress').', <a href="'.stripslashes($prd->access_url).'">'.__('click here to access it', 'memberpress').'</a>.</p>';

        return (object)array( 'enabled' => false,
                              'content' => apply_filters( 'mepr-product-access-url-message',
                                                          $access_url_str, $user, $prd ) );
      }
    }

    ob_start();
    //If the user can't purchase this let's show a message
    if(!$prd->can_you_buy_me())
    {
      $enabled = false;
      echo '<p class="mepr-cant-purchase">'.__('You do not have access to purchase this item.', 'memberpress').'</p>';
    }
    else if(isset($_GET['pmt']) and
            isset($_GET['action']) and
            $pm = $mepr_options->payment_method($_GET['pmt']) and
            $msgp = $pm->message_page($_GET['action']))
    {
      $enabled = false;
      call_user_func(array($pm, $msgp));
    }
    else
    {
      $enabled = true;
      MeprUsersController::display_signup_form($prd);
    }

    $content = ob_get_clean();
    return (object)compact( 'enabled', 'content' );
  }

  public static function enqueue_scripts($hook)
  {
    global $current_screen, $wp_scripts;

    if($current_screen->post_type == MeprProduct::$cpt)
    {
      $ui = $wp_scripts->query('jquery-ui-core');
      $url = "//ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.css";

      wp_enqueue_style('mepr-jquery-ui-smoothness', $url);
      wp_enqueue_script('mepr-date-picker-js', MEPR_JS_URL.'/date_picker.js', array('jquery-ui-datepicker'), MEPR_VERSION);

      wp_enqueue_style('mepr-emails-css', MEPR_CSS_URL.'/admin-emails.css', array(), MEPR_VERSION);
      wp_enqueue_style('mepr-products-css', MEPR_CSS_URL.'/admin-products.css', array('mepr-emails-css'), MEPR_VERSION);
      wp_dequeue_script('autosave'); //Disable auto-saving
      wp_enqueue_script('mepr-products-js', MEPR_JS_URL.'/admin_products.js', array('jquery-ui-spinner','jquery-ui-datepicker','jquery-ui-sortable','mepr-admin-shared-js'), MEPR_VERSION);
      wp_enqueue_script('mepr-emails-js', MEPR_JS_URL.'/admin_emails.js', array('mepr-products-js'), MEPR_VERSION);

      $options = array( 'removeBenefitStr' => __('Remove Benefit', 'memberpress'),
                        'register_price_action_id' => '#'.MeprProduct::$register_price_action_str,
                        'register_price_id' => '#'.MeprProduct::$register_price_str,
                        'wpnonce' => wp_create_nonce( MEPR_PLUGIN_SLUG ) );
      wp_localize_script('mepr-products-js', 'MeprProducts', $options);

      do_action('mepr-product-admin-enqueue-script', $hook);
    }
  }
  
  public static function nullify_records_on_delete($id)
  {
    MeprTransaction::nullify_product_id_on_delete($id);
    MeprSubscription::nullify_product_id_on_delete($id);
    
    return $id;
  }
  
  public static function shortcode_product_link($atts, $content = '')
  {
    if(!isset($atts['id']) || !is_numeric($atts['id']))
      return $content;
    
    $product = new MeprProduct($atts['id']);
    
    if($product->ID === null)
      return $content;
    
    return MeprProductsHelper::generate_product_link_html($product, $content);
  }

  public static function shortcode_registration_form($atts, $content = '')
  {
    if(isset($atts['product_id']) and $prd = new MeprProduct($atts['product_id']))
    {
      $res = self::get_registration_form($prd);
      return $res->content;
    }
    else
    {
      //No need to validate anything as the below function already
      //does all the validations. This is just a wrapper
      return self::display_registration_form('', true);
    }
  }

  public static function shortcode_if_product_was_purchased($atts, $content = '')
  {
    //Let's keep the protected string hidden if we have garbage input
    if( !isset($atts['id']) or
        !is_numeric($atts['id']) or
        !isset($_REQUEST['trans_num']) )
    { return ''; }

    $txn = new MeprTransaction();
    $data = MeprTransaction::get_one_by_trans_num($_REQUEST['trans_num']);
    $txn->load_data($data);

    if(!$txn->id or $txn->product_id != $atts['id']) { return ''; }

    return $content;
  }
  
  public static function maybe_get_thank_you_page_message()
  {
    if(!isset($_REQUEST['trans_num']))
      return '';
    
    $txn = new MeprTransaction();
    $data = MeprTransaction::get_one_by_trans_num($_REQUEST['trans_num']);
    $txn->load_data($data);
    
    if(!$txn->id || !$txn->product_id)
      return '';
    
    $product = $txn->product();
    
    if($product->ID === null || !$product->thank_you_page_enabled || empty($product->thank_you_message))
      return '';
    
    $message = wpautop(stripslashes($product->thank_you_message));
    $message = do_shortcode($message);
    $message = apply_filters('mepr_custom_thankyou_message', $message);
    
    do_action('mepr-thank-you-page', $txn);
    
    return '<div id="mepr-thank-you-page-message">'.$message.'</div>';
  }
  
  //Just a wrapper for track_and_override_login_redirect_mepr()
  //this wrapper catches regular WP logins
  public static function track_and_override_login_redirect_wp($url, $request, $user)
  {
    return self::track_and_override_login_redirect_mepr($url, $user, true);
  }
  
  public static function track_and_override_login_redirect_mepr($url = '', $wp_user = false, $is_wp_login_page = false, $track = true)
  {
    $mepr_options = MeprOptions::fetch();
    
    if($wp_user === false || is_wp_error($wp_user))
      return $url;
    
    $is_login_page = ((isset($_POST['mepr_is_login_page']) && $_POST['mepr_is_login_page'] == 'true') || $is_wp_login_page);
    
    //Track this login, then get the num total logins for this user
    $user = new MeprUser($wp_user->ID);
    
    if($track)
      $user->track_login_event();
    
    // short circuit if user has expired subscriptions and is not an admin
    $exsubs = $user->subscription_expirations('expired',true);
    if( !empty($exsubs) && !$wp_user->has_cap('delete_users') ) { return $mepr_options->account_page_url(); }
    
    $num_logins = $user->get_num_logins();
    
    //Get users active products
    $products = $user->active_product_subscriptions('ids');
    //If no active products, send them to wherever they were already going.
    if(empty($products))
      return $url;
    
    //Just grab the first product the user is subscribed to
    $product = new MeprProduct(array_shift($products));
    
    if($product->custom_login_urls_enabled && (!empty($product->custom_login_urls_default) || !empty($product->custom_login_urls)))
    {
      if(!empty($product->custom_login_urls))
      {
        foreach($product->custom_login_urls as $custom_url)
          if($custom_url->count == $num_logins)
            return stripslashes($custom_url->url);
      }
      
      return (!empty($product->custom_login_urls_default) && $is_login_page)?$product->custom_login_urls_default:$url;
    }
    
    return $url;
  }
  
  //Get's the price string via ajax for the price box in the dashboard
  public static function get_price_str_ajax() {
    if(!isset($_POST['product_id']) || !is_numeric($_POST['product_id']))
      die(__('An unknown error has occurred', 'memberpress'));
    
    $product = new MeprProduct($_POST['product_id']);
    
    if(!isset($product->ID) || (int)$product->ID <= 0)
      die(__('Please save product first to see the Price here.', 'memberpress'));
    
    die(MeprAppHelper::format_price_string($product, $product->price));
  }
} //End class
