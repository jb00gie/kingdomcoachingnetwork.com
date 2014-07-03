<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprRule extends MeprCptModel
{
  public static $mepr_type_str                = '_mepr_rules_type';
  public static $mepr_content_str             = '_mepr_rules_content';
  public static $is_mepr_content_regexp_str   = '_is_mepr_rules_content_regexp';
  public static $mepr_access_str              = '_mepr_rules_access';
  public static $drip_enabled_str             = '_mepr_rules_drip_enabled';
  public static $drip_amount_str              = '_mepr_rules_drip_amount';
  public static $drip_unit_str                = '_mepr_rules_drip_unit';
  public static $drip_after_str               = '_mepr_rules_drip_after';
  public static $drip_after_fixed_str         = '_mepr_rules_drip_after_fixed';
  public static $expires_enabled_str          = '_mepr_rules_expires_enabled';
  public static $expires_amount_str           = '_mepr_rules_expires_amount';
  public static $expires_unit_str             = '_mepr_rules_expires_unit';
  public static $expires_after_str            = '_mepr_rules_expires_after';
  public static $expires_after_fixed_str      = '_mepr_rules_expires_after_fixed';
  public static $unauth_excerpt_type_str      = '_mepr_rules_unauth_excerpt_type';
  public static $unauth_excerpt_size_str      = '_mepr_rules_unauth_excerpt_size';
  public static $unauth_message_type_str      = '_mepr_rules_unauth_message_type';
  public static $unauth_message_str           = '_mepr_rules_unath_message';
  public static $unauth_login_str             = '_mepr_rules_unath_login';
  public static $auto_gen_title_str           = '_mepr_auto_gen_title';

  public static $mepr_nonce_str               = 'mepr_rules_nonce';
  public static $last_run_str                 = 'mepr_rules_db_cleanup_last_run';

  public static $cpt                          = 'memberpressrule';

  /*** Instance Methods ***/
  function __construct($id = null)
  {
    $this->load_cpt( $id, self::$cpt,
                     array( "mepr_type" => 'all',
                            "mepr_content" => '',
                            "is_mepr_content_regexp" => false,
                            "mepr_access" => array(),
                            "drip_enabled" => false,
                            "drip_amount" => 0,
                            "drip_unit" => 'days',
                            "drip_after" => 'registers',
                            "drip_after_fixed" => '',
                            "expires_enabled" => false,
                            "expires_amount" => 0,
                            "expires_unit" => 'days',
                            "expires_after" => 'registers',
                            "expires_after_fixed" => '',
                            "unauth_excerpt_type" => 'default',
                            "unauth_excerpt_size" => 100,
                            "unauth_message_type" => 'default',
                            "unauth_message" => '',
                            "unauth_login" => 'default',
                            "auto_gen_title" => true ) );
  }

  public static function get_types() /*tested*/
  {
    global $wp_taxonomies, $wp_post_types;

    $mepr_options = MeprOptions::fetch();
    
    static $types;
    
    if(!isset($types) or empty($types))
    {
      $types = array( 'all' => array(),
                      'post' => array( 'all_posts'   => __('All Posts', 'memberpress'),
                                       'single_post' => __('A Single Post', 'memberpress'),
                                       'category'    => __('Posts Categorized', 'memberpress'),
                                       'tag'         => __('Posts Tagged', 'memberpress') ),
                      'page' => array( 'all_pages'   => __('All Pages', 'memberpress'),
                                       'single_page' => __('A Single Page', 'memberpress'),
                                       'parent_page' => __('Child Pages of', 'memberpress') ) );

      $cpts = get_post_types(array("public" => true, "_builtin" => false), 'objects');
      unset($cpts['memberpressproduct']);

      $cpts = apply_filters('mepr-rules-cpts', $cpts);

      foreach($cpts as $type_name => $cpt)
      {
        $types[$type_name] = array(
          "all_{$type_name}" => sprintf(__('All %s', 'memberpress'), $cpt->labels->name),
          "single_{$type_name}" => sprintf(__('A Single %s', 'memberpress'), $cpt->labels->singular_name)
        );

        if($cpt->hierarchical)
          $types[$type_name]["parent_{$type_name}"] = sprintf(__('Child %s of', 'memberpress'), $cpt->labels->name);
      }

      $txs = array(
        'category' => $wp_taxonomies['category'],
        'post_tag' => $wp_taxonomies['post_tag']
      );

      $txs = array_merge( $txs, get_taxonomies( array( 'public' => true, '_builtin' => false ), 'objects' ) );

      $cpts['post'] = $wp_post_types['post'];
      $cpts['page'] = $wp_post_types['page'];

      foreach( $txs as $tax_name => $tx ) {
        if($tax_name=='post_tag')
          $types['all']["all_tax_{$tax_name}"] = __('All Content Tagged', 'memberpress');
        else if($tax_name=='category')
          $types['all']["all_tax_{$tax_name}"] = __('All Content Categorized', 'memberpress');
        else
          $types['all']["all_tax_{$tax_name}"] = sprintf(__('All Content with %1$s', 'memberpress'), $tx->labels->singular_name);

        foreach( $tx->object_type as $cpt_slug ) {
          if( $cpt_slug == 'memberpressproduct' ) { continue; }

          $cpt = $cpts[$cpt_slug];

          if($tax_name=='post_tag') {
            if( $cpt_slug != 'post' ) // Already setup for post
              $types[$cpt_slug]["tax_{$tax_name}||cpt_{$cpt_slug}"] = sprintf(__('%1$s Tagged', 'memberpress'), $cpt->labels->name);
          }
          else if($tax_name=='category') {
            if( $cpt_slug != 'post' ) // Already setup for post
              $types[$cpt_slug]["tax_{$tax_name}||cpt_{$cpt_slug}"] = sprintf(__('%1$s Categorized', 'memberpress'), $cpt->labels->name);
          }
          else
            $types[$cpt_slug]["tax_{$tax_name}||cpt_{$cpt_slug}"] = sprintf(__('%1$s with %2$s', 'memberpress'), $cpt->labels->name, $tx->labels->singular_name);
        }
      }

      $all_types = array('all' => __('All Content', 'memberpress'));
      foreach( $types as $type_array ) { $all_types = array_merge( $all_types, $type_array ); }

      $all_types = array_merge(
        $all_types,
        array( 'partial' => __('Partial', 'memberpress'),
               'custom'  => __('Custom URI', 'memberpress') )
      );

      $types = $all_types;
    }
    
    return $types;
  }

  public static function get_contents_array($type) /*tested*/
  {
    if($type == 'single_widget')
      return self::get_widgets_array();
    elseif(preg_match('#^single_(.*?)$#', $type, $matches))
      return self::get_single_array($matches[1]);
    elseif(preg_match('#^parent_(.*?)$#', $type, $matches))
      return self::get_parent_array($matches[1]); 
    elseif($type == 'category')
      return self::get_category_array();
    elseif($type == 'tag')
      return self::get_tag_array();
    elseif( preg_match('#^tax_(.*?)\|\|cpt_(.*?)$#', $type, $matches) or
            preg_match('#^all_tax_(.*?)$#', $type, $matches) )
      return self::get_tax_array($matches[1]);
    
    return false;
  }

  public static function search_content($type,$search='') /*tested*/
  {
    if($type == 'single_widget')
      return self::search_widgets($search);
    elseif(preg_match('#^single_(.*?)$#', $type, $matches))
      return self::search_singles($matches[1],$search);
    elseif(preg_match('#^parent_(.*?)$#', $type, $matches))
      return self::search_parents($matches[1],$search); 
    elseif($type == 'category')
      return self::search_categories($search);
    elseif($type == 'tag')
      return self::search_tags($search);
    elseif( preg_match('#^tax_(.*?)\|\|cpt_(.*?)$#', $type, $matches) or
            preg_match('#^all_tax_(.*?)$#', $type, $matches) )
      return self::search_taxs($matches[1],$search);

    return false;
  }

  public static function get_content($type,$id) /*tested*/
  {
    if($type == 'single_widget')
      return self::get_widget($id);
    elseif(preg_match('#^single_(.*?)$#', $type, $matches))
      return self::get_single($matches[1],$id);
    elseif(preg_match('#^parent_(.*?)$#', $type, $matches))
      return self::get_parent($matches[1],$id); 
    elseif($type == 'category')
      return self::get_category($id);
    elseif($type == 'tag')
      return self::get_tag($id);
    elseif( preg_match('#^tax_(.*?)\|\|cpt_(.*?)$#', $type, $matches) or
            preg_match('#^all_tax_(.*?)$#', $type, $matches) )
      return self::get_tax($matches[1],$id);
    
    return false;
  }

  public static function type_has_contents($type) {
    if($type == 'single_widget')
      return self::widgets_have_contents();
    elseif(preg_match('#^single_(.*?)$#', $type, $matches))
      return self::singles_have_contents($matches[1]);
    elseif(preg_match('#^parent_(.*?)$#', $type, $matches))
      return self::parents_have_contents($matches[1]); 
    elseif($type == 'category')
      return self::categories_have_contents();
    elseif($type == 'tag')
      return self::tags_have_contents();
    elseif( preg_match('#^tax_(.*?)\|\|cpt_(.*?)$#', $type, $matches) or
            preg_match('#^all_tax_(.*?)$#', $type, $matches) )
      return self::taxs_have_contents($matches[1]);
    
    return false;
  }
  
  public static function widgets_have_contents() {
    global $wp_registered_widgets;
    return !empty($wp_registered_widgets);
  }

  public static function get_widgets_array()
  {
    global $wp_registered_widgets;
    
    $contents = array();
    
    if(!empty($wp_registered_widgets))
      foreach($wp_registered_widgets as $id => $data)
        $contents[$id] = $data['name'];
    
    return $contents;
  }

  // TODO: Not sure how / if we'll be doing search here on Widgets
  public static function search_widgets($search,$limit=25) {
    return array();
  }

  public static function get_widget($id) {
    return false;
  }

  public static function singles_have_contents($type) {
    //global $wpdb;
    //$query = "SELECT COUNT(*) " .
    //           "FROM {$wpdb->posts} AS p " .
    //          "WHERE p.post_type=%s";
    //$query = $wpdb->prepare( $query, $type );
    //return ( $wpdb->get_var($query) > 0 );

    $counts = wp_count_posts($type);
    return ( $counts->publish > 0 );
  }
  
  public static function get_single_array($type) /*wrapperTested*/
  {
    $post_contents = get_posts(array('numberposts' => -1, 'post_type' => $type));
    $contents = array();
    
    foreach($post_contents as $post)
      $contents[$post->ID] = $post->post_title;

    return $contents;
  }

  public static function search_singles($type,$search='',$limit=25) {
    global $wpdb;
    $query = "SELECT p.ID AS id, p.post_title AS label " .
               "FROM {$wpdb->posts} AS p " .
              "WHERE p.post_type=%s " .
                "AND p.post_status=%s ";

    if(!empty($search)) {
      $query .= "AND ( p.ID LIKE %s OR p.post_title LIKE %s ) ";
      $query .= "LIMIT {$limit}";
      $query = $wpdb->prepare( $query, $type, 'publish', "%{$search}%", "%{$search}%" );
    }
    else {
      $query .= "LIMIT {$limit}";
      $query = $wpdb->prepare( $query, $type, 'publish' );
    }

    return array_map( create_function( '$i', '$i->slug=preg_replace(\'!\'.preg_quote(home_url(),\'!\').\'!\',\'\',get_permalink($i->id)); $i->desc="ID: {$i->id} | Slug: {$i->slug}"; return $i;' ),
                      $wpdb->get_results($query) );
  }

  public static function get_single($type,$id) {
    global $wpdb;
    $query = "SELECT p.ID AS id, p.post_title AS label " .
               "FROM {$wpdb->posts} AS p " .
              "WHERE p.post_type=%s " .
                "AND p.post_status=%s " .
                "AND p.ID=%d " .
              "LIMIT 1";

    $query = $wpdb->prepare( $query, $type, 'publish', $id );

    $i = $wpdb->get_row($query);
    if($i==false) { return false; }

    $i->slug=preg_replace('!'.preg_quote(home_url(),'!').'!','',get_permalink($i->id));
    $i->desc="ID: {$i->id} | Slug: {$i->slug}";

    return $i;
  }

  public static function parents_have_contents($type='page') {
    return self::singles_have_contents($type);
  }

  public static function get_parent_array($type='page') /*wrapperTested*/
  {
    $post_contents = get_posts(array('numberposts' => -1, 'post_type' => $type));
    $contents = array();
    
    foreach($post_contents as $post)
      $contents[$post->ID] = $post->post_title;
    
    return $contents;
  }

  public static function search_parents($type='page',$search='',$limit=25) {
    return self::search_singles($type,$search,$limit);
  }

  public static function get_parent($type,$id) {
    return self::get_single($type,$id);
  }

  public static function categories_have_contents() {
    return ( wp_count_terms( 'category', array('hide_empty' => 0) ) > 0 );
  }

  public static function get_category_array() /*wrapperTested*/
  {
    $category_contents = get_categories(array('hide_empty' => 0));
    $contents = array();
    
    foreach($category_contents as $category)
      $contents[$category->term_id] = $category->name;
    
    return $contents;
  }

  public static function search_terms( $tax, $search='', $limit=25 ) {
    global $wpdb;
    $query = "SELECT t.term_id AS id, t.name AS label, t.slug AS slug " .
               "FROM {$wpdb->terms} AS t " .
               "JOIN {$wpdb->term_taxonomy} AS tx ".
                 "ON t.term_id=tx.term_id " .
                "AND tx.taxonomy=%s ";

    if(!empty($search)) {
      $query .= "WHERE ( t.term_id LIKE %s OR t.name LIKE %s OR t.slug LIKE %s OR tx.description LIKE %s ) ";
      $query .= "LIMIT {$limit}";
      $s = "%{$search}%";
      $query = $wpdb->prepare( $query, $tax, $s, $s, $s, $s );
    }
    else {
      $query .= "LIMIT {$limit}";
      $query = $wpdb->prepare( $query, $tax );
    }

    return array_map( create_function( '$i', '$i->desc = "ID: {$i->id} | Slug: {$i->slug}"; return $i;' ),
                      $wpdb->get_results($query) );
  }

  public static function get_term( $tax, $id ) {
    global $wpdb;
    $query = "SELECT t.term_id AS id, t.name AS label, t.slug AS slug " .
               "FROM {$wpdb->terms} AS t " .
               "JOIN {$wpdb->term_taxonomy} AS tx ".
                 "ON t.term_id=tx.term_id " .
                "AND tx.taxonomy=%s " .
              "WHERE t.term_id=%d " .
              "LIMIT 1";

    $query = $wpdb->prepare( $query, $tax, $id );
    $i = $wpdb->get_row($query);
    if($i==false) { return false; }

    $i->desc = "ID: {$i->id} | Slug: {$i->slug}";

    return $i;
  }

  public static function search_categories($search,$limit=25) {
    return self::search_terms( 'category', $search, $limit );
  }

  public static function get_category($id) {
    return self::get_term( 'category', $id );
  }

  public static function tags_have_contents() {
    return ( wp_count_terms( 'post_tag', array('get' => 'all') ) > 0 );
  }

  public static function get_tag_array() /*wrapperTested*/
  {
    $tag_contents = get_tags(array('get' => 'all'));
    $contents = array();
    
    foreach($tag_contents as $tag)
      $contents[$tag->term_id] = $tag->name;
    
    return $contents;
  }

  public static function search_tags($search,$limit=25) {
    return self::search_terms( 'post_tag', $search, $limit );
  }

  public static function get_tag($id) {
    return self::get_term( 'post_tag', $id );
  }

  public static function taxs_have_contents($tax) {
    return ( wp_count_terms( $tax, array('get' => 'all') ) > 0 );
  }

  public static function get_tax_array($tax) /*wrapperTested*/
  {
    $tax_contents = get_terms($tax,array('get' => 'all'));
    $contents = array();
    
    foreach($tax_contents as $tax)
      $contents[$tax->term_id] = $tax->name;
    
    return $contents;
  }

  public static function search_taxs($tax, $search,$limit=25) {
    return self::search_terms( $tax, $search, $limit );
  }

  public static function get_tax($tax, $id) {
    return self::get_term( $tax, $id );
  }

  // We just assume this will only be called on posts that are the correct type
  public static function is_exception_to_rule( $post, $rule ) {
    $exceptions = explode( ',', preg_replace( '#\s#', '', $rule->mepr_content ) );
    return in_array( $post->ID, $exceptions );
  }
  
  //Make sure that we don't lock down the unauthorized URL if redirect on unauthorized is selected
  // public static function is_unauthorized_url() {
    // global $post;
    // $mepr_options = MeprOptions::fetch();
    
    // if($mepr_options->redirect_on_unauthorized) {
      // $current_url = get_permalink($post->ID);
      
      // if(stristr($current_url, $mepr_options->unauthorized_redirect_url) !== false) {
        // return true;
      // }
    // }
    
    // return false;
  // }
  
  // TODO: Create a convenience function calling this in MeprProduct once it's in place
  public static function get_rules($post = false, $uri = false, $widget = false) /*tested*/
  {
    //Saves a ton of queries
    static $all_rules;
    $post_rules = array();
    
    //Check that we're not protecting the page set in Options for redirect on unauthorized
    //Otherwise we end up in a loop.
    // if(is_singular() && self::is_unauthorized_url())
      // return $post_rules;
    
    if(!isset($all_rules)) {
      $all_rule_posts = get_posts(array('numberposts' => -1, 'post_type' => self::$cpt, 'post_status' => 'publish'));

      $all_rules = array();

      foreach($all_rule_posts as $curr_post)
        if($curr_post->post_type == self::$cpt) //pre_get_posts filter can override our post_type above in get_posts()
          $all_rules[] = new MeprRule($curr_post->ID);
    }
    
    foreach($all_rules as $curr_rule)
    {
      //if($post !== false and $curr_rule->mepr_type == 'single_widget')
      //{
      //  //$post is the widget's ID in this case
      //  if($post == $curr_rule->mepr_content)
      //    $post_rules[] = $curr_rule;
      //}
      
      if(is_object($post) and $curr_rule->mepr_type != 'custom') // and $curr_rule->mepr_type != 'single_widget')
      {
        if( $curr_rule->mepr_type == 'all' )
        {
          // We're going to add this rule immediately if it's set to all and it's not an exception
          if( !self::is_exception_to_rule( $post, $curr_rule ) ) { $post_rules[] = $curr_rule; }
        }
        elseif(preg_match('#^all_tax_(.*?)$#', $curr_rule->mepr_type, $matches))
        {
          if( has_term( $curr_rule->mepr_content, $matches[1], $post->ID ) )
            $post_rules[] = $curr_rule;
        }
        elseif(preg_match('#^all_(.*?)$#', $curr_rule->mepr_type, $matches))
        {
          if( preg_match('#^'.preg_quote($post->post_type).'s?$#', $matches[1]) and
              !self::is_exception_to_rule( $post, $curr_rule ) )
          {
            $post_rules[] = $curr_rule;
          }
        }
        elseif(preg_match('#^single_(.*?)$#', $curr_rule->mepr_type, $matches))
        {
          if( $post->post_type == $matches[1] and
              $post->ID == $curr_rule->mepr_content )
          {
            $post_rules[] = $curr_rule;
          }
        }
        elseif(preg_match('#^parent_(.*?)$#', $curr_rule->mepr_type, $matches))
        {
          if( $post->post_type == $matches[1] and
              $post->post_parent == $curr_rule->mepr_content )
          {
            $post_rules[] = $curr_rule;
          }
        }
        elseif($curr_rule->mepr_type == 'category')
        {
          if(in_category($curr_rule->mepr_content, $post->ID))
            $post_rules[] = $curr_rule;
        }
        elseif($curr_rule->mepr_type == 'tag')
        {
          if(has_tag($curr_rule->mepr_content, $post->ID))
            $post_rules[] = $curr_rule;
        }
        elseif(preg_match('#^tax_(.*?)\|\|cpt_(.*?)$#', $curr_rule->mepr_type, $matches))
        {
          if( $post->post_type == $matches[2] and
              has_term( $curr_rule->mepr_content, $matches[1], $post->ID ) )
          {
            $post_rules[] = $curr_rule;
          }
        }
      }
      
      //Check if URI is not false
      if($uri !== false and $curr_rule->mepr_type == 'custom')
      {
        $uri = ($uri !== true and !empty($uri))?$uri:$_SERVER['REQUEST_URI'];
        
        if( ( $curr_rule->is_mepr_content_regexp && preg_match("!{$curr_rule->mepr_content}!", $uri) ) ||
            ( !$curr_rule->is_mepr_content_regexp && strpos($uri, $curr_rule->mepr_content) === 0 ) ) {
          $post_rules[] = $curr_rule;
        }
      }
    } //End foreach
    
    return $post_rules;
  }
  
  // TODO: Move to MeprProduct once it's in place
  public static function get_access_list($post = false, $uri = false) /*tested*/
  {
    $product_access_array = array();
    $rules = MeprRule::get_rules($post, $uri);
    
    foreach($rules as $rule)
      if($rule->mepr_access)
        $product_access_array = array_merge($product_access_array, $rule->mepr_access);
    
    return array_unique($product_access_array);
  }
  
  public static function is_uri_locked_for_user($user, $uri)
  {
    // the content is not locked regardless of whether or not
    // a user is logged in so let's just return here okay?
    $rules = MeprRule::get_rules(false, $uri);
    
    if(empty($rules))
      return false;
    
    // TODO: We may want to move this into the MeprUser model as an is_authorized function or something
    $product_access_array = MeprRule::get_access_list(false, $uri);
    $user = MeprUtils::get_currentuserinfo();
    $subscriptions = $user->active_product_subscriptions();
    $intersect = array_intersect($product_access_array, $subscriptions);
    
    //Uhhh yeah let's be efficient here and not bother with the drips
    //If the user hasn't purchased a product for this uri
    if(empty($intersect))
      return true;
    
    //We've already checked if the $rules are not empty so let's go ahead and use them
    $dripped = self::has_an_active_rule_dripped($rules, $intersect);
    $expired = self::has_an_active_rule_expired($rules, $intersect);
    
    return !($dripped && !$expired);
  }
  
  public static function is_uri_locked($uri) /*tested*/
  {
    $mepr_options = MeprOptions::fetch();
    
    if(MeprUtils::is_logged_in_and_an_admin())
      return false; //If user is an admin, let's not go on.
    
    $rules = MeprRule::get_rules(false, $uri);
    
    // the content is not locked regardless of whether or not
    // a user is logged in so let's just return here okay?
    if(empty($rules)) { return false; }
    
    if(MeprUtils::is_user_logged_in())
    {
      $user = MeprUtils::get_currentuserinfo();
      return self::is_uri_locked_for_user($user,$uri);
    }
    else
      return true; // If there are rules on this content and the user isn't logged in -- it's locked
  }
  
  public static function is_locked_for_user($user, $post)
  {
    $rules = MeprRule::get_rules($post);

    if(empty($rules)) { return false; }

    $product_access_array = MeprRule::get_access_list($post);
    $subscriptions = $user->active_product_subscriptions();
    $intersect = array_intersect($product_access_array, $subscriptions);
    
    //Uhhh yeah let's be efficient here and not bother with the drips
    //If the user hasn't purchased a product for this page/post
    if(empty($intersect)) { return true; }
    
    //We've already checked if the $rules are not empty so let's go ahead and use them
    $dripped = self::has_an_active_rule_dripped($rules, $intersect);
    $expired = self::has_an_active_rule_expired($rules, $intersect);
    
    return !($dripped && !$expired);
  }
  
  // TODO: Move to MeprProduct once it's in place
  public static function is_locked($post) /*tested*/
  {
    $mepr_options = MeprOptions::fetch();
    
    if(MeprUtils::is_logged_in_and_an_admin())
      return false; //If user is an admin, let's not go on.
    
    // Can't rule the login page lest we end up in an infinite loop
    if($post->ID == $mepr_options->login_page_id) { return false; }
    
    $rules = MeprRule::get_rules($post);
    
    // the content is not locked regardless of wether or not
    // a user is logged in so let's just return here okay?
    if(empty($rules)) { return false; }
    
    if(MeprUtils::is_user_logged_in())
    {
      $user = MeprUtils::get_currentuserinfo();
      return self::is_locked_for_user($user, $post);
    }
    else
      return true; // If there are rules on this content and the user isn't logged in -- it's locked
  }
  
  // TODO: Move to MeprProduct once it's in place
  public static function is_widget_locked($widget_id) /*tested*/
  {
    $mepr_options = MeprOptions::fetch();
    
    if(MeprUtils::is_logged_in_and_an_admin())
      return false; //If user is an admin, let's not go on.
    
    $rules = MeprRule::get_rules($widget_id, false, true);
    
    // the widget is not locked regardless of wether or not
    // a user is logged in so let's just return here okay?
    if(empty($rules))
      return false;
    
    if(MeprUtils::is_user_logged_in())
    {
      // TODO: We may want to move this into the MeprUser model as an is_authorized function or something
      $product_access_array = MeprRule::get_access_list($post);
      
      $user = MeprUtils::get_currentuserinfo();
      $subscriptions = $user->active_product_subscriptions();
      $intersect = array_intersect($product_access_array, $subscriptions);
      
      //Uhhh yeah let's be efficient here and not bother with the drips
      //If the user hasn't purchased a product for this page/post
      if(empty($intersect))
        return true;
      
      //We've already checked if the $rules are not empty so let's go ahead and use them
      $dripped = self::has_an_active_rule_dripped($rules, $intersect);
      $expired = self::has_an_active_rule_expired($rules, $intersect);
      
      return !($dripped && !$expired);
    }
    else
      return true; // If there are rules on this content and the user isn't logged in -- it's locked
  }
  
  public static function has_an_active_rule_dripped($rules, $valid_prod_ids = array())
  {
    foreach($rules as $rule)
    {
      //If the member hasn't purchased any products associated with this Rule, skip it
      $intersect = array_intersect($valid_prod_ids, $rule->mepr_access);
      if(empty($intersect))
        continue;
      
      if($rule->has_dripped())
        return true;
    }
    
    return false;
  }
  
  public function has_dripped()
  {
    if(!$this->drip_enabled)
      return true; //If the drip is disabled, then let's kill this thing
    
    if($this->drip_after == 'registers')
    {
      $registered_ts = MeprUtils::mysql_date_to_ts(MeprUser::get_current_users_registration_date());
      return $this->has_time_passed($registered_ts, $this->drip_unit, $this->drip_amount);
    }
    
    if($this->drip_after == 'fixed' && !empty($this->drip_after_fixed))
    {
      $fixed_ts = strtotime($this->drip_after_fixed);
      return $this->has_time_passed($fixed_ts, $this->drip_unit, $this->drip_amount);
    }
    
    $product = new MeprProduct($this->drip_after);
    if(!isset($product->ID) || (int)$product->ID <= 0)
      return true; //The product doesn't exist anymore, so let's ignore this rule
    
    $purchased_ts = MeprUtils::mysql_date_to_ts(MeprUser::get_ts_of_product_signup($this->drip_after));
    if(!$purchased_ts) //This should never happen now that we're filtering these out
      return false; //User hasn't purchased this product
    
    return $this->has_time_passed($purchased_ts, $this->drip_unit, $this->drip_amount);
  }
  
  public static function has_an_active_rule_expired($rules, $valid_prod_ids = array())
  {
    $has_expired = false;
    
    foreach($rules as $rule)
    {
      //If the member hasn't purchased any products associated with this Rule, skip it
      $intersect = array_intersect($valid_prod_ids, $rule->mepr_access);
      if(empty($intersect))
        continue;
      
      if($rule->has_expired())
        $has_expired = true;
      else //If at least one rule hasn't expired let's return false.
        return false;
    }
    
    return $has_expired;
  }
  
  public function has_expired()
  {
    if(!$this->expires_enabled)
      return false; //If the expiration is disabled, then let's kill this thing
    
    if($this->expires_after == 'registers')
    {
      $registered_ts = MeprUtils::mysql_date_to_ts(MeprUser::get_current_users_registration_date());
      return $this->has_time_passed($registered_ts, $this->expires_unit, $this->expires_amount);
    }
    
    if($this->expires_after == 'fixed' && !empty($this->expires_after_fixed))
    {
      $fixed_ts = strtotime($this->expires_after_fixed);
      return $this->has_time_passed($fixed_ts, $this->drip_unit, $this->drip_amount);
    }
    
    $product = new MeprProduct($this->expires_after);
    if(!isset($product->ID) || (int)$product->ID <= 0)
      return false; //The product doesn't exist anymore, so let's ignore this rule
    
    $purchased_ts = MeprUtils::mysql_date_to_ts(MeprUser::get_ts_of_product_signup($this->expires_after));
    if(!$purchased_ts) //This should never happen now that we're filtering these out
      return false; //User hasn't purchased this product ?????
    
    return $this->has_time_passed($purchased_ts, $this->expires_unit, $this->expires_amount);
  }
  
  //Should probably put this in Utils at some point
  public function has_time_passed($ts, $unit, $amount)
  {
    switch($unit)
    {
      case 'days':
        $days_ts = MeprUtils::days($amount);
        if((time() - $ts) > $days_ts)
          return true;
        break;
      case 'weeks':
        $weeks_ts = MeprUtils::weeks($amount);
        if((time() - $ts) > $weeks_ts)
          return true;
       break;
      case 'months':
        $months_ts = MeprUtils::months($amount, $ts);
        if((time() - $ts) > $months_ts)
          return true;
        break;
      case 'years':
        $years_ts = MeprUtils::years($amount, $ts);
        if((time() - $ts) > $years_ts)
          return true;
        break;
    }
    
    return false;
  }
  
  public function get_formatted_accesses() /*tested*/
  {
    $formatted_array = array();
    
    if(isset($this->mepr_access) and is_array($this->mepr_access))
    {
      foreach($this->mepr_access as $access)
      {
        $product = get_post($access);
        
        if($product)
          $formatted_array[] = $product->post_title;
      }
    }
    
    return $formatted_array;
  }
  
  public function store_meta() /*tested*/
  {
    update_post_meta($this->ID, self::$mepr_type_str, $this->mepr_type);
    update_post_meta($this->ID, self::$mepr_content_str, $this->mepr_content);
    update_post_meta($this->ID, self::$is_mepr_content_regexp_str, $this->is_mepr_content_regexp);
    update_post_meta($this->ID, self::$mepr_access_str, $this->mepr_access);
    update_post_meta($this->ID, self::$drip_enabled_str, $this->drip_enabled);
    update_post_meta($this->ID, self::$drip_amount_str, $this->drip_amount);
    update_post_meta($this->ID, self::$drip_unit_str, $this->drip_unit);
    update_post_meta($this->ID, self::$drip_after_fixed_str, $this->drip_after_fixed);
    update_post_meta($this->ID, self::$drip_after_str, $this->drip_after);
    update_post_meta($this->ID, self::$expires_enabled_str, $this->expires_enabled);
    update_post_meta($this->ID, self::$expires_amount_str, $this->expires_amount);
    update_post_meta($this->ID, self::$expires_unit_str, $this->expires_unit);
    update_post_meta($this->ID, self::$expires_after_str, $this->expires_after);
    update_post_meta($this->ID, self::$expires_after_fixed_str, $this->expires_after_fixed);
    update_post_meta($this->ID, self::$unauth_excerpt_type_str, $this->unauth_excerpt_type);
    update_post_meta($this->ID, self::$unauth_excerpt_size_str, $this->unauth_excerpt_size);
    update_post_meta($this->ID, self::$unauth_message_type_str, $this->unauth_message_type);
    update_post_meta($this->ID, self::$unauth_message_str, $this->unauth_message);
    update_post_meta($this->ID, self::$unauth_login_str, $this->unauth_login);
    update_post_meta($this->ID, self::$auto_gen_title_str, $this->auto_gen_title);
  }
  
  public static function cleanup_db() /*dontTest*/
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
  
  /** This returns the directory where rule files will be stored
    * for use with the rewrite (via .htaccess) system.
    */
  public static function rewrite_rule_file_dir()
  {
    $rule_file_path_array = wp_upload_dir();
    $rule_file_path = $rule_file_path_array['basedir'];
    $rule_file_dir = "{$rule_file_path}/mepr/rules";
    
    if(!is_dir($rule_file_dir)) // Make sure it exists
    {
      @mkdir($rule_file_dir, 0777, true);
    }
    
    return $rule_file_dir;
  }
  
  //THESE TWO FUNCTIONS SHOULD PROBABLY BE DEPRECATED AT SOME POINT
  //IN FAVOR OF THE current_user_can() SYSTEM BLAIR PUT IN PLACE INSTEAD
  //PHP Snippet wrapper (returns opposite of is_protected_by_rule()
  public static function is_allowed_by_rule($rule_id)
  {
    return !(self::is_protected_by_rule($rule_id));
  }
  
  //PHP Snippet Code
  public static function is_protected_by_rule($rule_id) {
    $current_post = MeprUtils::get_current_post();
    
    //Check if we've been given sanitary input, if not this snippet
    //is no good so let's return false here
    if(!is_numeric($rule_id) || (int)$rule_id <= 0)
      return false;
    
    //Check if user is logged in
    if(!MeprUtils::is_user_logged_in())
      return true;
    
    //No sense loading the rule until we know the user is logged in
    $rule = new MeprRule($rule_id);
    
    //If rule doesn't exist, has no products associated with it, or
    //we're an Admin let's return the full content
    if(!isset($rule->ID) || (int)$rule->ID <= 0 || empty($rule->mepr_access) || is_super_admin())
      return false;
    
    //Make sure this page/post/cpt is not in the "except" list of an all_* Rule
    //TODO -- really need to take the "except" list into consideration here using $current_post if it's set
    
    //Now we know the user is logged in and the rule is valid
    //let's see if they have purchased one of the products listed in this rule
    $user = MeprUtils::get_currentuserinfo();
    $subscriptions = $user->active_product_subscriptions();
    $intersect = array_intersect($rule->mepr_access, $subscriptions);
    
    //If intersection is empty, user has no access
    if(empty($intersect))
      return true;
    
    //Uhhh ... if we've made it here the user should be validated
    //for this snippet protection, so let's return the full content
    return false;
  }
  
  public static function get_global_unauth_settings() {
    $mepr_options = MeprOptions::fetch();

    return (object)array(
      'excerpt_type'  => ($mepr_options->unauth_show_excerpts?$mepr_options->unauth_excerpt_type:'hide'),
      'excerpt_size'  => $mepr_options->unauth_excerpt_size,
      'excerpt'       => '',
      'message_type'  => 'custom',
      'message'       => $mepr_options->unauthorized_message,
      'unauth_login'  => $mepr_options->unauth_show_login,
      'show_login'    => ($mepr_options->unauth_show_login == 'show')
    );
  }

  public static function get_post_unauth_settings($post) {
    // Get values
    $unauth_message_type  = get_post_meta( $post->ID, '_mepr_unauthorized_message_type', true );
    $unauth_message       = get_post_meta( $post->ID, '_mepr_unauthorized_message',      true );
    $unauth_login         = get_post_meta( $post->ID, '_mepr_unauth_login',              true );
    $unauth_excerpt_type  = get_post_meta( $post->ID, '_mepr_unauth_excerpt_type',       true );
    $unauth_excerpt_size  = get_post_meta( $post->ID, '_mepr_unauth_excerpt_size',       true );

    // Get defaults
    $unauth_message_type  = (($unauth_message_type != '')?$unauth_message_type:'default');
    $unauth_message       = (($unauth_message != '')?$unauth_message:'');
    $unauth_login         = (($unauth_login != '')?$unauth_login:'default');
    $unauth_excerpt_type  = (($unauth_excerpt_type != '')?$unauth_excerpt_type:'default');
    $unauth_excerpt_size  = (($unauth_excerpt_size != '')?$unauth_excerpt_size:100);

    return (object)compact(
      'unauth_message_type', 'unauth_message', 'unauth_login',
      'unauth_excerpt_type', 'unauth_excerpt_size' 
    );
  }

  public static function get_unauth_settings_for($post) {
    $unauth = (object)array();
    $global_settings = self::get_global_unauth_settings();
    $post_settings = self::get_post_unauth_settings($post);

    $rules = MeprRule::get_rules($post);

    if(empty($rules)) { return $global_settings; } //If we're gonna return global settings, let's make sure they're all there and that they match what would've been returned in by the $unauth object below. Should probaly fix $post_settings to use the same var names as well, but since we don't return $post_settings ever, we should be ok for now.

    // TODO: Make this a bit more sophisticated? For now just pick the first rule.
    $rule = $rules[0];

    // - Excerpts
    if($post_settings->unauth_excerpt_type!='default') {
      $unauth->excerpt_type = $post_settings->unauth_excerpt_type;
      $unauth->excerpt_size = $post_settings->unauth_excerpt_size;
    }
    else if($rule->unauth_excerpt_type!='default') {
      $unauth->excerpt_type = $rule->unauth_excerpt_type;
      $unauth->excerpt_size = $rule->unauth_excerpt_size;
    }
    else {
      $unauth->excerpt_type = $global_settings->excerpt_type;
      $unauth->excerpt_size = $global_settings->excerpt_size;
    }

    // Set the actual Excerpt based on the type & size
    if( $unauth->excerpt_type=='custom' ) {
      $unauth->excerpt = do_shortcode($post->post_content);
      
      if($unauth->excerpt_size) { //if set to 0, return the whole post -- though why protect it all in this case?
        $unauth->excerpt = strip_tags($unauth->excerpt);
        $unauth->excerpt = substr($unauth->excerpt, 0, $unauth->excerpt_size);
        //Re-add <p>'s back in below to preserve some formatting at least
        $unauth->excerpt = wpautop($unauth->excerpt . "...");
      }
    }
    else if( $unauth->excerpt_type=='excerpt' )
      $unauth->excerpt = wpautop( $post->post_excerpt );
    else
      $unauth->excerpt = '';

    // - Messages
    if($post_settings->unauth_message_type!='default') {
      $unauth->message_type = $post_settings->unauth_message_type;
      $unauth->message      = $post_settings->unauth_message;
    }
    else if($rule->unauth_message_type!='default') {
      $unauth->message_type = $rule->unauth_message_type;
      $unauth->message      = $rule->unauth_message;
    }
    else {
      $unauth->message_type = $global_settings->message_type;
      $unauth->message      = $global_settings->message;
    }

    if( $unauth->message_type == 'hide' )
      $unauth->message = ''; // Reset the message if it's not shown
    else
      $unauth->message = wpautop( $unauth->message );

    // - Login Form
    if($post_settings->unauth_login!='default') {
      $unauth->show_login = ($post_settings->unauth_login=='show');
    }
    else if($rule->unauth_login!='default') {
      $unauth->show_login = ($rule->unauth_login=='show');
    }
    else {
      $unauth->show_login = ($global_settings->unauth_login=='show');
    }

    $unauth->excerpt = apply_filters('mepr-unauthorized-excerpt', $unauth->excerpt, $post, $unauth);
    $unauth->message = apply_filters('mepr-unauthorized-message', $unauth->message, $post, $unauth);
    $unauth->show_login = apply_filters('mepr-unauthorized-show-login', $unauth->show_login, $post, $unauth);

    return $unauth;
  }

  /** This is an instance method that fetches all of
    *  the current content associated with this rule.
    */
  public function get_matched_content( $count=false, $type='objects',
                                       $order="p.post_date", $fields="p.*" ) {
    global $wpdb;

    if($count) {
      $fields="COUNT(*)";
    }
    elseif($type=='ids') {
      $fields="p.ID";
    }

    if($this->mepr_type != 'custom')
    {
      if( $this->mepr_type == 'all' )
      {
        $query = "SELECT {$fields} FROM {$wpdb->posts} AS p " .
                  "WHERE p.post_status='publish'";

        if(!empty($this->mepr_content)) {
          $query .= " AND p.ID NOT IN (" . preg_replace('/ /', '', $this->mepr_content) . ")";
        }
      }
      elseif(preg_match('#^all_tax_(.*?)$#', $this->mepr_type, $matches))
      {
        // Custom Taxonomies
        $query = $wpdb->prepare( "SELECT {$fields} FROM {$wpdb->posts} AS p " .
                                  "INNER JOIN {$wpdb->terms} AS t " .
                                     "ON t.slug=%s " .
                                  "INNER JOIN {$wpdb->term_taxonomy} AS x " .
                                     "ON x.term_id=t.term_id " .
                                    "AND x.taxonomy=%s " .
                                  "INNER JOIN {$wpdb->term_relationships} AS r " .
                                     "ON r.object_id=p.ID " .
                                    "AND r.term_taxonomy_id=x.term_taxonomy_id " .
                                  "WHERE p.post_status='publish'",
                                 $this->mepr_content, $matches[1] );
      }
      elseif(preg_match('#^all_(.*?)$#', $this->mepr_type, $matches))
      {
        // Custom Post Types
        $query = $wpdb->prepare( "SELECT {$fields} FROM {$wpdb->posts} AS p " .
                                  "WHERE p.post_status='publish' " .
                                    "AND p.post_type=%s",
                                 $matches[1] );

        if(!empty($this->mepr_content)) {
          $query .= " AND p.ID NOT IN (" . preg_replace('/ /', '', $this->mepr_content) . ")";
        }
      }
      elseif(preg_match('#^single_(.*?)$#', $this->mepr_type, $matches))
      {
        // Custom Post Type
        $query = $wpdb->prepare( "SELECT {$fields} FROM {$wpdb->posts} AS p " .
                                  "WHERE p.post_status='publish' " .
                                    "AND p.ID=%d " .
                                    "AND p.post_type=%s",
                                 $this->mepr_content, $matches[1] );
      }
      elseif(preg_match('#^parent_(.*?)$#', $this->mepr_type, $matches))
      {
        // Custom Post Type
        $query = $wpdb->prepare( "SELECT {$fields} FROM {$wpdb->posts} AS p " .
                                  "WHERE p.post_status='publish' " .
                                    "AND p.post_parent=%s " .
                                    "AND p.post_type=%s",
                                 $this->mepr_content, $matches[1] );
      }
      elseif($this->mepr_type == 'category')
      {
        // Posts Categorized
        $query = $wpdb->prepare( "SELECT {$fields} FROM {$wpdb->posts} AS p " .
                                  "INNER JOIN {$wpdb->terms} AS t " .
                                     "ON t.slug=%s " .
                                  "INNER JOIN {$wpdb->term_taxonomy} AS x " .
                                     "ON x.term_id=t.term_id " .
                                    "AND x.taxonomy='category' " .
                                  "INNER JOIN {$wpdb->term_relationships} AS r " .
                                     "ON r.object_id=p.ID " .
                                    "AND r.term_taxonomy_id=x.term_taxonomy_id " .
                                  "WHERE p.post_status='publish' " .
                                    "AND p.post_type='post'",
                                 $this->mepr_content );
      }
      elseif($this->mepr_type == 'tag')
      {
        // Posts Tagged
        $query = $wpdb->prepare( "SELECT {$fields} FROM {$wpdb->posts} AS p " .
                                  "INNER JOIN {$wpdb->terms} AS t " .
                                     "ON t.slug=%s " .
                                  "INNER JOIN {$wpdb->term_taxonomy} AS x " .
                                     "ON x.term_id=t.term_id " .
                                    "AND x.taxonomy='post_tag' " .
                                  "INNER JOIN {$wpdb->term_relationships} AS r " .
                                     "ON r.object_id=p.ID " .
                                    "AND r.term_taxonomy_id=x.term_taxonomy_id " .
                                  "WHERE p.post_status='publish' " .
                                    "AND p.post_type='post'",
                                 $this->mepr_content );
      }
      elseif(preg_match('#^tax_(.*?)\|\|cpt_(.*?)$#', $this->mepr_type, $matches))
      {
        // Custom Taxonomies and Post Types
        $query = $wpdb->prepare( "SELECT {$fields} FROM {$wpdb->posts} AS p " .
                                  "INNER JOIN {$wpdb->terms} AS t " .
                                     "ON t.slug=%s " .
                                  "INNER JOIN {$wpdb->term_taxonomy} AS x " .
                                     "ON x.term_id=t.term_id " .
                                    "AND x.taxonomy=%s " .
                                  "INNER JOIN {$wpdb->term_relationships} AS r " .
                                     "ON r.object_id=p.ID " .
                                    "AND r.term_taxonomy_id=x.term_taxonomy_id " .
                                  "WHERE p.post_status='publish' " .
                                    "AND p.post_type=%s",
                                 $this->mepr_content, $matches[1], $matches[2] );
      }
    }

    if(!$count and !empty($order)) {
      $query .= " ORDER BY {$order}";
    }

    if($type=='sql') {
      return $query;
    }
    else if($count) {
      return $wpdb->get_var($query); 
    }
    else if($type=='ids') {
      return $wpdb->get_col($query); 
    }
    else {
      return $wpdb->get_results($query); 
    }
  }
} //End class

