<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprGroup extends MeprCptModel
{
  public static $pricing_page_disabled_str         = '_mepr_group_pricing_page_disabled';
  public static $is_upgrade_path_str               = '_mepr_group_is_upgrade_path';
  public static $group_theme_str                   = '_mepr_group_theme';
  public static $page_button_class_str             = '_mepr_page_button_class';
  public static $page_button_highlighted_class_str = '_mepr_page_button_highlighted_class';
  public static $page_button_disabled_class_str    = '_mepr_page_button_disabled_class';
  public static $products_str                      = '_mepr_products';
  public static $group_page_style_options_str      = '_mepr_group_page_style_options';
  public static $group_page_layout_str             = 'mepr-group-page-layout';
  public static $group_page_style_str              = 'mepr-group-page-style';
  public static $group_page_button_size_str        = 'mepr-group-page-button-size';
  public static $group_page_bullet_style_str       = 'mepr-group-page-bullet-style';
  public static $group_page_font_style_str         = 'mepr-group-page-font-style';
  public static $group_page_font_size_str          = 'mepr-group-page-font-size';
  public static $group_page_button_color_str       = 'mepr-group-page-button-color';
  public static $use_custom_template_str           = '_mepr_use_custom_template';
  public static $custom_template_str               = '_mepr_custom_template';
  
  public static $nonce_str    = 'mepr_groups_nonce';
  public static $last_run_str = 'mepr_groups_db_cleanup_last_run';
  
  public static $cpt = 'memberpressgroup';
  
  public function __construct($id = null)
  {
    $this->attrs = array( "pricing_page_disabled",
                          "is_upgrade_path",
                          "group_theme",
                          "page_button_class",
                          "page_button_highlighted_class",
                          "page_button_disabled_class",
                          "group_page_style_options",
                          "use_custom_template",
                          "custom_template" );
    
    if(null === ($this->rec = get_post($id)))
      $this->initialize_new_cpt();
    elseif($this->post_type != self::$cpt)
      $this->initialize_new_cpt();
    else
    {
      $this->rec->pricing_page_disabled         = get_post_meta($id, self::$pricing_page_disabled_str, true);
      $this->rec->is_upgrade_path               = (bool)get_post_meta($id, self::$is_upgrade_path_str, true);
      $this->rec->group_theme                   = get_post_meta($id, self::$group_theme_str, true);
      $this->rec->page_button_class             = get_post_meta($id, self::$page_button_class_str, true);
      $this->rec->page_button_highlighted_class = get_post_meta($id, self::$page_button_highlighted_class_str, true);
      $this->rec->page_button_disabled_class    = get_post_meta($id, self::$page_button_disabled_class_str, true);
      
      if(!$this->rec->group_theme)
        $this->rec->group_theme = 'minimal_gray_horizontal.css'; // default theme bro

      $this->rec->group_page_style_options  = array('layout'        => 'mepr-vertical',
                                                    'style'         => 'mepr-gray',
                                                    'button_size'   => 'mepr-medium',
                                                    'bullet_style'  => 'mepr-circles',
                                                    'font_style'    => 'custom',
                                                    'font_size'     => 'custom',
                                                    'button_color'  => 'mepr-button-gray');

      $this->group_page_style_options = array_merge($this->group_page_style_options, (array)get_post_meta($id, self::$group_page_style_options_str, true));

      $this->rec->use_custom_template = get_post_meta($id, self::$use_custom_template_str, true);
      $this->rec->custom_template     = get_post_meta($id, self::$custom_template_str, true);
    }
  }
  
  public function store_meta()
  {
    $id = $this->ID;
    
    update_post_meta($id, self::$pricing_page_disabled_str, $this->pricing_page_disabled);
    update_post_meta($id, self::$is_upgrade_path_str, $this->is_upgrade_path);
    update_post_meta($id, self::$group_theme_str, $this->group_theme);
    update_post_meta($id, self::$page_button_class_str, $this->page_button_class);
    update_post_meta($id, self::$page_button_highlighted_class_str, $this->page_button_highlighted_class);
    update_post_meta($id, self::$page_button_disabled_class_str, $this->page_button_disabled_class);
    update_post_meta($id, self::$group_page_style_options_str, $this->group_page_style_options);
    update_post_meta($id, self::$use_custom_template_str, $this->use_custom_template);
    update_post_meta($id, self::$custom_template_str, $this->custom_template);
  }
  
  //$return_type should be a string containing 'objects', 'ids', or 'titles'
  public function products($return_type = 'objects')
  {
    global $wpdb;
    
    $query = "SELECT ID FROM {$wpdb->posts} AS p " .
               "JOIN {$wpdb->postmeta} AS pm_group_id ".
                 "ON p.ID = pm_group_id.post_id ".
                "AND pm_group_id.meta_key = %s ".
                "AND pm_group_id.meta_value = %s ".
               "JOIN {$wpdb->postmeta} AS pm_group_order ".
                 "ON p.ID = pm_group_order.post_id ".
                "AND pm_group_order.meta_key = %s ".
              "ORDER BY pm_group_order.meta_value * 1"; // * 1 = easy way to cast strings as numbers in SQL
    
    $query = $wpdb->prepare($query, MeprProduct::$group_id_str, $this->ID, MeprProduct::$group_order_str);
    
    $res = $wpdb->get_col($query);
    
    $products = array();
    if(is_array($res))
    {
      foreach($res as $product_id)
      {
        $prd = new MeprProduct($product_id);
        
        if($return_type == 'objects')
          $products[] = $prd;
        elseif($return_type == 'ids')
          $products[] = $prd->ID;
        elseif($return_type == 'titles')
          $products[] = $prd->post_title;
      }
    }
    
    return $products;
  }
  
  //Gets the transaction related to a lifetime product in a group
  //For use during upgrades from lifetime to subscriptions
  public function get_old_lifetime_txn($new_prd_id, $user_id)
  {
    $txn_id = false;
    $grp_prds = $this->products('ids');
    $usr_txns = MeprTransaction::get_all_by_user_id($user_id, '', '', true);
    
    //Try and find the old txn and make sure it's not one belonging
    //to the product the user just signed up for
    foreach($usr_txns as $txn)
      if(in_array($txn->product_id, $grp_prds) && $txn->product_id != $new_prd_id)
        $txn_id = $txn->id;
    
    if($txn_id)
      return new MeprTransaction($txn_id);
    else
      return false;
  }
  
  public static function cleanup_db()
  {
    global $wpdb;
    $date = time();
    $last_run = get_option(self::$last_run_str, 0); //Prevents all this code from executing on every page load
    if(($date - $last_run) > 86400) //Runs at most once a day
    {
      $sq1 = "SELECT ID
                FROM {$wpdb->posts}
                WHERE post_type = '".self::$cpt."' AND
                      post_status = 'auto-draft'";
      $q1 = "DELETE
                FROM {$wpdb->postmeta}
                WHERE post_id IN ({$sq1})";
      $q2 = "DELETE
                FROM {$wpdb->posts}
                WHERE post_type = '".self::$cpt."' AND
                      post_status = 'auto-draft'";
      
      $wpdb->query($q1);
      $wpdb->query($q2);
      update_option(self::$last_run_str, $date);
    }
  }

  public function get_page_template() {
    if( $this->use_custom_template )
      return locate_template( $this->custom_template );
    else
      return locate_template( self::template_search_path() );
  }

  public static function template_search_path() {
    return array( 'page_memberpressgroup.php',
                  'single-memberpressgroup.php',
                  'page.php',
                  'custom_template.php',
                  'index.php' );
  }

  public function manual_append_price_boxes() {
    return preg_match('~\[mepr-group-price-boxes~',$this->post_content);
  }

  public static function is_group_page($post) {
    if( is_object($post) &&
        ( ( $post->post_type == MeprGroup::$cpt &&
            $grp = new MeprGroup($post->ID) ) ||
          ( preg_match( '~\[mepr-group-price-boxes\s+group_id=[\"\\\'](\d+)[\"\\\']~',
                        $post->post_content, $m ) &&
            isset($m[1]) &&
            $grp = new MeprGroup( $m[1] ) ) ) )
    {
      return $grp;
    }

    return false;
  }
} //End class
