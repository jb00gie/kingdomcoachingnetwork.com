<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprUtils
{
  //Maybe this should be in MeprUser?
  public static function get_user_id_by_email($email) /*tested*/
  {
    $user = self::get_user_by('email', $email);
    if(is_object($user))
      return $user->ID;
    
    return '';
  }
  
  public static function is_image($filename) /*tested*/
  {
    if(!file_exists($filename))
      return false;
    
    $file_meta = @getimagesize($filename); //@ suppress errors if $filename is not an image
    
    $image_mimes = array("image/gif", "image/jpeg", "image/png");
    
    return in_array($file_meta['mime'], $image_mimes);
  }
  
  /** Looks up month names 
    * @parameter $abbreviations=false If true then will return month name abbreviations
    * @parameter $index If false then will return the full array of month names
    * @parameter $one_based_index If true then will grab the index of the months array as if the index were one based (meaning January = 1
    * @return mixed -- an array if $index=false and a string if $index=0-12
    */
  public static function month_names($abbreviations = true, $index = false, $one_based_index = false)
  {
    if($abbreviations)
    {
      $months = array( __('Jan', 'memberpress'), __('Feb', 'memberpress'), __('Mar', 'memberpress'), __('Apr', 'memberpress'), __('May', 'memberpress'), __('Jun', 'memberpress'), __('Jul', 'memberpress'), __('Aug', 'memberpress'), __('Sept', 'memberpress'), __('Oct', 'memberpress'), __('Nov', 'memberpress'), __('Dec', 'memberpress') );
    }
    else
    {
      $months = array( __('January', 'memberpress'), __('February', 'memberpress'), __('March', 'memberpress'), __('April', 'memberpress'), __('May', 'memberpress'), __('June', 'memberpress'), __('July', 'memberpress'), __('August', 'memberpress'), __('September', 'memberpress'), __('October', 'memberpress'), __('November', 'memberpress'), __('December', 'memberpress') );
    }
    
    if($index === false)
      return $months; // No index then return the full array
    
    $index = $one_based_index ? $index - 1 : $index;
    
    return $months[$index];
  }
  
  public static function period_type_name($period_type, $count)
  {
    switch($period_type)
    {
      case 'days':
        return _n('Day','Days',$count,'memberpress');
      case 'weeks':
        return _n('Week','Weeks',$count,'memberpress');
      case 'months':
        return _n('Month','Months',$count,'memberpress');
      case 'years':
        return _n('Year','Years',$count,'memberpress');
      default:
        return '';
    }
  }
  
  public static function rewriting_on() /*tested*/
  {
    $permalink_structure = get_option('permalink_structure');
    return ($permalink_structure and !empty($permalink_structure));
  }
  
  public static function is_logged_in_and_current_user($user_id) /*tested*/
  {
    global $current_user;
    self::get_currentuserinfo();
    
    return (self::is_user_logged_in() and (is_object($current_user) && $current_user->ID == $user_id));
  }
  
  public static function is_logged_in_and_an_admin() /*tested*/
  {
    return (self::is_user_logged_in() and self::is_admin());
  }
  
  public static function is_logged_in_and_a_subscriber() /*tested*/
  {
    return (self::is_user_logged_in() and self::is_subscriber());
  }
  
  public static function is_admin() /*wrapperTested*/
  {
    return current_user_can('administrator');
  }
  
  public static function is_subscriber() /*wrapperTested*/
  {
    return (current_user_can('subscriber'));
  }
  
  public static function minutes($n = 1) /*wrapperTested*/
  {
    return $n * 60;
  }
  
  public static function hours($n = 1) /*wrapperTested*/
  {
    return $n * self::minutes(60);
  }
  
  public static function days($n = 1) /*wrapperTested*/
  {
    return $n * self::hours(24);
  }
  
  public static function weeks($n = 1) /*tested*/
  {
    return $n * self::days(7);
  }
  
  public static function months($n, $month_timestamp) /*tested*/
  {
    $seconds = 0;
    
    for($i=0; $i < $n; $i++)
    {
      $month_seconds = self::days((int)date('t', $month_timestamp));
      $seconds += $month_seconds;
      $month_timestamp += $month_seconds;
    }
    
    return $seconds;
  }
  
  public static function years($n, $year_timestamp)
  {
    $seconds = 0;
    
    for($i=0; $i < $n; $i++)
    {
      $seconds += $year_seconds = self::days(365 + (int)date('L', $year_timestamp));
      $year_timestamp += $year_seconds;
    }
    
    return $seconds;
  }
  
  // convert timestamp into approximate minutes
  public static function tsminutes($ts) {
    return (int)($ts / 60);
  }
  
  // convert timestamp into approximate hours
  public static function tshours($ts) {
    return (int)(self::tsminutes($ts) / 60);
  }
  
  // convert timestamp into approximate days
  public static function tsdays($ts) {
    return (int)(self::tshours($ts) / 24);
  }

  // convert timestamp into approximate weeks
  public static function tsweeks($ts) {
    return (int)(self::tsdays($ts) / 7);
  }

  //Coupons rely on this be careful changing it
  public static function make_ts_date($month, $day, $year) /*tested*/
  {
    return mktime(23, 59, 59, $month, $day, $year);
  }
  
  //Coupons rely on this be careful changing it
  public static function get_date_from_ts($ts, $format = 'M d, Y') /*tested*/
  {
    if($ts > 0)
      return date($format, $ts);
    else
      return date($format, time());
  }
  
  public static function mysql_date_to_ts($mysql_date) /*tested*/
  {
    return strtotime($mysql_date);
  }
  
  public static function ts_to_mysql_date($ts, $format='Y-m-d H:i:s') /*tested*/
  {
    return date($format, $ts);
  }
  
  public static function array_to_string($my_array, $debug = false, $level = 0)
  {
    return self::object_to_string($my_array);
  }
  
  public static function object_to_string($object)
  {
    ob_start();
    print_r($object);
    
    return ob_get_clean();
  }
  
  // Drop in replacement for evil eval
  public static function replace_vals($content, $params, $start_token="\\\\{\\\\$", $end_token="\\\\}") /*tested*/
  {
    if(!is_array($params)) { return $content; }

    $callback = create_function('$k','return "/' . $start_token . '\w*{$k}\w*' . $end_token . '/";');
    $patterns = array_map( $callback, array_keys($params) );
    $replacements = array_values( $params );

    return preg_replace( $patterns, $replacements, $content );
  }

  public static function format_float($number, $num_decimals = 2) /*tested*/
  {
    return number_format($number, $num_decimals, '.', '');
  }

  public static function get_pages() /*dontTest*/ //not worth testing as we have to fabricate inputs and outputs
  {
    global $wpdb;
    
    $query = "SELECT * FROM {$wpdb->posts} WHERE post_status = %s AND post_type = %s";
    
    $query = $wpdb->prepare($query, "publish", "page");
    
    $results = $wpdb->get_results($query);
    
    if($results)
      return $results;
    else
      return array();
  }
  
  public static function is_product_page()
  {
    $current_post = self::get_current_post();
    
    return is_object($current_post) and $current_post->post_type == 'memberpressproduct';
  }
  
  public static function protocol()
  {
    return ((empty($_SERVER['HTTPS']) or $_SERVER['HTTPS']=='off')?'http':'https');
  }
  
  public static function get_property($className, $property)
  {
    if(!class_exists($className)) return null;
    if(!property_exists($className, $property)) return null;
    
    $vars = get_class_vars($className);
    return $vars[$property];
  }
  
  public static function random_string($length = 10, $lowercase = true, $uppercase = false, $symbols = false)
  {
    $characters = '0123456789';
    $characters .= $uppercase?'ABCDEFGHIJKLMNOPQRSTUVWXYZ':'';
    $characters .= $lowercase?'abcdefghijklmnopqrstuvwxyz':'';
    $characters .= $symbols?'@#*^%$&!':'';
    $string = '';
    $max_index = strlen($characters) - 1;
    
    for($p = 0; $p < $length; $p++)
      $string .= $characters[mt_rand(0, $max_index)];
    
    return $string;
  }
  
  public static function sanitize_string($string)
  {
    //Converts "Hey there buddy-boy!" to "hey_there_buddy_boy"
    return str_replace('-', '_', sanitize_title($string));
  }
  
  public static function flush_rewrite_rules()
  {
    // Load our controllers
    $controllers = @glob( MEPR_CONTROLLERS_PATH . '/*', GLOB_NOSORT );
    
    foreach( $controllers as $controller )
    {
      $class = preg_replace('#\.php#', '', basename($controller));
      
      if(preg_match('#Mepr.*Controller#', $class))
      {
        $obj = new $class;
        
        // Only act on MeprCptControllers
        if($obj instanceof MeprCptController)
          $obj->register_post_type();
      }
    }
    
    flush_rewrite_rules();
  }
  
  // Format a protected version of a cc num from the last 4 digits
  public static function cc_num($last4='****')
  {
    // If a full cc num happens to get here then it gets reduced to the last4 here
    $last4 = substr($last4, -4);
    return "************{$last4}";
  }
  
  public static function calculate_proration_by_subs($old_sub, $new_sub)
  {
    // find expiring_txn txn on old_sub
    $expiring_txn = $old_sub->expiring_txn();

    // If no money has changed hands then no proration
    if( $expiring_txn->txn_type == MeprTransaction::$subscription_confirmation_str )
      return (object)array('proration' => 0.00, 'days' => 0);

    $res = self::calculate_proration( $expiring_txn->amount,
                                      $new_sub->price,
                                      $old_sub->days_in_this_period(),
                                      $new_sub->days_in_this_period(),
                                      $old_sub->days_till_expiration() );
    
    return $res;
  }
  
  public static function calculate_proration( $old_amount,
                                              $new_amount,
                                              $old_period='lifetime',
                                              $new_period='lifetime',
                                              $days_left='lifetime' )
  {
    // sub to sub
    if(is_numeric($old_period) and is_numeric($new_period))
    {
      // calculate amount of money left on old sub
      $old_outstanding_amount = (($old_amount / $old_period) * $days_left);
    
      // calculate cost of same amount of time on new sub
      $new_outstanding_amount = (($new_amount / $new_period) * $days_left);
    
      // calculate the difference (amount owed to upgrade)
      $proration = self::format_float(max(($new_outstanding_amount - $old_outstanding_amount), 0.00));
      $days = $days_left;
    }
    else if(is_numeric($old_period) and is_numeric($days_left) and $new_period=='lifetime')
    { // sub to lifetime
      // apply outstanding amount to lifetime purchase
      // calculate amount of money left on old sub
      $old_outstanding_amount = (($old_amount / $old_period) * $days_left);
      
      $proration = self::format_float(max(($new_amount - $old_outstanding_amount), 0.00));
      $days = 0; // we just do this thing
    }
    //else if(is_numeric($new_period)) { // lifetime to sub
    //  // No proration
    //  $proration = 0.00;
    //  $days = 0;
    //}
    else if($old_period=='lifetime' and $new_period=='lifetime')
    { // lifetime to lifetime
      $proration = self::format_float(max(($new_amount - $old_amount), 0.00));
      $days = 0; // We be lifetime brah
    }
    else
    { // Default
      $proration = self::format_float(0);
      $days = 0;
    }
    
    return (object)compact('proration','days');
  }
  
  public static function is_associative_array($arr)
  {
    return array_keys($arr) !== range(0, count($arr) - 1);
  }
  
  public static function get_post_meta_with_default($post_id, $meta_key, $single = false, $default = null)
  {
    $pms = get_post_custom($post_id);
    $var = get_post_meta($post_id, $meta_key, $single);
    
    if(($single and $var=='') or (!$single and $var==array())) {
      // Since false bools are stored as empty string ('') we need
      // to see if the meta_key is actually stored in the db and
      // it's a bool value before we blindly return default
      if(isset($pms[$meta_key]) and is_bool($default))
        return false;
      else
        return $default;
    }
    else
      return $var;
  }

  public static function convert_to_plain_text($text) {
    $text = preg_replace('~<style[^>]*>[^<]*</style>~','',$text);
    $text = strip_tags($text);
    $text = trim($text);
    $text = preg_replace("~\r~",'',$text); // Make sure we're only dealint with \n's here
    $text = preg_replace("~\n\n+~","\n\n",$text); // reduce 1 or more blank lines to 1
    return $text;
  }

  public static function array_splice_assoc(&$input, $offset, $length, $replacement) {
    $replacement = (array) $replacement;
    $key_indices = array_flip(array_keys($input));

    if( isset($input[$offset]) && is_string($offset) ) {
      $offset = $key_indices[$offset];
    }
    if( isset($input[$length]) && is_string($length) ) {
      $length = $key_indices[$length] - $offset;
    }

    $input = array_slice($input, 0, $offset, true)
            + $replacement
            + array_slice($input, $offset + $length, null, true);
  }

  public static function get_sub_type($sub) {

    if( $sub instanceof MeprSubscription ) {
      return 'recurring';
    }
    elseif( $sub instanceof MeprTransaction ) {
      return 'single';
    }

    return false;
  }
  
  //Get the current post, and account for non-singular views
  public static function get_current_post() {
    global $post;
    
    if(in_the_loop())
      return get_post(get_the_ID());
    else
      return (isset($post) && $post instanceof WP_Post)?$post:false;
  }

  public static function render_json($struct,$filename='',$is_debug=false) {
    header('Content-Type: text/json');

    if(!$is_debug and !empty($filename))
      header("Content-Disposition: attachment; filename=\"{$filename}.json\"");

    die(json_encode($struct));
  }

  protected function render_xml($struct,$filename='',$is_debug=false) {
    header('Content-Type: text/xml');
    
    if(!$is_debug and !empty($filename))
      header("Content-Disposition: attachment; filename=\"{$filename}.xml\"");

    die(self::to_xml($struct));
  }

  public static function render_csv($struct,$filename='',$is_debug=false) {
    if(!$is_debug) {
      header('Content-Type: text/csv');

      if(!empty($filename))
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
    }

    header('Content-Type: text/plain');

    die(self::to_csv($struct));
  }

  public static function render_unauthorized($message) {
    header('WWW-Authenticate: Basic realm="' . get_option('blogname') . '"');
    header('HTTP/1.0 401 Unauthorized');
    die(sprintf(__('UNAUTHORIZED: %s', 'memberpress'),$message));
  }

  /**
   * The main function for converting to an XML document.
   * Pass in a multi dimensional array and this recrusively loops through and builds up an XML document.
   *
   * @param array $data
   * @param string $root_node_name - what you want the root node to be - defaultsto data.
   * @param SimpleXMLElement $xml - should only be used recursively
   * @return string XML
   */
  public static function to_xml($data, $root_node_name='memberpressData', $xml=null, $parent_node_name='') {
    // turn off compatibility mode as simple xml throws a wobbly if you don't.
    if(ini_get('zend.ze1_compatibility_mode') == 1)
      ini_set('zend.ze1_compatibility_mode', 0);

    if(is_null($xml))
      $xml = simplexml_load_string('<?xml version=\'1.0\' encoding=\'utf-8\'?'.'><'.$root_node_name.' />');

    // loop through the data passed in.
    foreach( $data as $key => $value ) {
      // no numeric keys in our xml please!
      if( is_numeric( $key ) ) {
        if( empty( $parent_node_name ) )
          $key = "unknownNode_". (string)$key; // make string key...
        else 
          $key = preg_replace( '/s$/', '', $parent_node_name ); // We assume that there's an 's' at the end of the string?
      }

      // replace anything not alpha numeric
      //$key = preg_replace('/[^a-z]/i', '', $key);
      $key = self::camelcase( $key );

      // if there is another array found recrusively call this function
      if(is_array($value)) {
        $node = $xml->addChild($key);
        // recrusive call.
        self::to_xml($value, $root_node_name, $node, $key);
      }
      else {
        // add single node.
        $value = htmlentities($value);
        $xml->addChild($key,$value);
      }
    }

    // pass back as string. or simple xml object if you want!
    return $xml->asXML();
  }

  /**
  * Formats an associative array as CSV and returns the CSV as a string.
  * Can handle nested arrays, headers are named by associative array keys.
  * Adapted from http://us3.php.net/manual/en/function.fputcsv.php#87120
  */
  public function to_csv( $struct,
                          $delimiter = ',',
                          $enclosure = '"',
                          $enclose_all = false,
                          $telescope = '.',
                          $null_to_mysql_null = false ) {
    $struct = self::deep_convert_to_associative_array($struct);

    if(self::is_associative_array($struct)) {
      $struct = array($struct);
    }

    $csv = '';
    $headers = array();
    $lines = array();

    foreach( $struct as $row ) {
      $last_path=''; // tracking for the header
      $lines[] = self::process_csv_row(
                   $row, $headers, $last_path, '', $delimiter,
                   $enclosure, $enclose_all,
                   $telescope, $null_to_mysql_null );
    }

    $csv .= implode( $delimiter, array_keys($headers) ) . "\n";

    foreach( $lines as $line ) { 
      $csv_line = array_merge($headers, $line);
      $csv .= implode( $delimiter, array_values($csv_line) ) . "\n";
    }

    return $csv;
  }

  /** Expects an associative array for a row of this data structure. Should
    * handle nested arrays by telescoping header values with the $telescope arg.
    */
  private static function process_csv_row( $row, &$headers, &$last_path, $path='',
                                           $delimiter = ',',
                                           $enclosure = '"',
                                           $enclose_all = false,
                                           $telescope = '.',
                                           $null_to_mysql_null=false ) {

    $output = array();

    foreach( $row as $label => $field ) {
      $new_path = ( empty($path) ? $label : $path.$telescope.$label );

      if( is_null($field) and $null_to_mysql_null ) {
        $headers = self::header_insert( $headers, $new_path, $last_path );
        $last_path = $new_path;

        $output[$new_path] = 'NULL';
        continue;
      }

      if( is_array( $field ) ) {
        $output += self::process_csv_row( $field, $headers, $last_path, $new_path, $delimiter,
          $enclosure, $enclose_all, $telescope, $null_to_mysql_null );
      }
      else {
        $delimiter_esc = preg_quote($delimiter, '/');
        $enclosure_esc = preg_quote($enclosure, '/');

        $headers = self::header_insert( $headers, $new_path, $last_path );
        $last_path = $new_path;

        // Enclose fields containing $delimiter, $enclosure or whitespace
        if( $enclose_all or preg_match( "/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field ) )
          $output[$new_path] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure;
        else
          $output[$new_path] = $field;
      }
    }

    return $output;
  }

  private static function header_insert( $headers, $new_path, $last_path ) {
    if(!isset($headers[$new_path])) {
      $headers = self::array_insert( $headers, $last_path, array( $new_path => '' ) );
    }
    return $headers;
  }

  public static function array_insert( $array, $index, $insert ) {
    $pos = array_search($index,array_keys($array));

    $pos = empty($pos) ? 0 : (int)$pos;

    $before = array_slice( $array, 0, $pos+1 );
    $after  = array_slice( $array, $pos ); 

    $array = $before + $insert + $after;

    return $array;
  }

  public static function camelcase($str) {
    // Level the playing field
    $str = strtolower($str);
    // Replace dashes and/or underscores with spaces to prepare for ucwords
    $str = preg_replace('/[-_]/', ' ', $str);
    // Ucwords bro ... uppercase the first letter of every word
    $str = ucwords($str);
    // Now get rid of the spaces
    $str = preg_replace('/ /', '', $str);
    // Lowercase the first character of the string
    $str{0} = strtolower($str{0});

    return $str;
  }
  
  public static function snakecase($str, $delim='_') {
    // Search for '_-' then just lowercase and ensure correct delim
    if( preg_match( '/[-_]/', $str ) ) {
      $str = preg_replace( '/[-_]/', $delim, $str );
    }
    else { // assume camel case
      $str = preg_replace('/([A-Z])/', $delim.'$1', $str);
      $str = preg_replace('/^'.preg_quote($delim).'/', '', $str);
    }

    return strtolower($str);
  }

  public static function kebabcase($str) {
    return self::snakecase($str,'-');
  }

  // Deep convert to associative array using JSON
  // TODO: Find some cleaner way to do a deep convert to an assoc array
  public static function deep_convert_to_associative_array($struct) {
    return json_decode(json_encode($struct),true);
  }

  public static function hex_encode($str, $delim="%") {
    $encoded = bin2hex($str);
    $encoded = chunk_split($encoded, 2, $delim);
    $encoded = $delim . substr($encoded, 0, strlen($encoded) - strlen($delim));
    return $encoded;
  }

/* PLUGGABLE FUNCTIONS AS TO NOT STEP ON OTHER PLUGINS' CODE */
  public static function get_currentuserinfo() /*dontTest*/
  {
    global $current_user;
    
    self::include_pluggables('get_currentuserinfo');
    get_currentuserinfo();
    
    if(isset($current_user->ID) && $current_user->ID > 0)
      return new MeprUser($current_user->ID);
    else
      return false;
  }
  
  public static function get_user_by($field = 'login', $value) /*dontTest*/
  {
    self::include_pluggables('get_user_by');
    return get_user_by($field, $value);
  }
  
  // Just sends to the emails configured in MP
  public static function wp_mail_to_admin($subject, $message, $header='')
  {
    $mepr_options = MeprOptions::fetch();
    $recipient = $mepr_options->admin_email_addresses;
    
    self::wp_mail($recipient, $subject, $message, $header);
  }
  
  public static function wp_mail($recipient, $subject, $message, $header='')
  {
    self::include_pluggables('wp_mail');
    
    add_filter('wp_mail_from_name', 'MeprUtils::set_mail_from_name');
    add_filter('wp_mail_from',      'MeprUtils::set_mail_from_email');
    
    // We just send individual emails
    $rs = explode(',', $recipient);
    foreach($rs as $to) {
      $to = trim($to);
      wp_mail($to, $subject, $message, $header);
    }
    
    remove_filter('wp_mail_from',      'MeprUtils::set_mail_from_name');
    remove_filter('wp_mail_from_name', 'MeprUtils::set_mail_from_email');
  }
  
  public static function set_mail_from_name($name) {
    $mepr_options = MeprOptions::fetch();
    
    return $mepr_options->mail_send_from_name;
  }
  
  public static function set_mail_from_email($email) {
    $mepr_options = MeprOptions::fetch();
    
    return $mepr_options->mail_send_from_email;
  }
  
  public static function is_user_logged_in() /*dontTest*/
  {
    self::include_pluggables('is_user_logged_in');
    return is_user_logged_in();
  }
  
  public static function get_avatar($id, $size) /*dontTest*/
  {
    self::include_pluggables('get_avatar');
    return get_avatar($id, $size);
  }
  
  public static function wp_hash_password($password_str) /*dontTest*/
  {
    self::include_pluggables('wp_hash_password');
    return wp_hash_password($password_str);
  }
  
  public static function wp_generate_password($length, $special_chars) /*dontTest*/
  {
    self::include_pluggables('wp_generate_password');
    return wp_generate_password($length, $special_chars);
  }
  
  public static function wp_redirect($location, $status=302) /*dontTest*/
  {
    self::include_pluggables('wp_redirect');
    
    //Don't cache redirects YO!
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    
    wp_redirect($location, $status);
    exit;
  }
  
  public static function wp_authenticate($username, $password) /*dontTest*/
  {
    self::include_pluggables('wp_authenticate');
    return wp_authenticate($username,$password);
  }
  
  public static function include_pluggables($function_name) /*dontTest*/
  {
    if(!function_exists($function_name))
      require_once(ABSPATH.WPINC.'/pluggable.php');
  }
  
  public static function login_url() /*dontTest*/ //These funcs are thin wrappers for WP funcs, no need to test.
  {
    $mepr_options = MeprOptions::fetch();
    
    if($mepr_options->login_page_id > 0)
      return $mepr_options->login_page_url();
    else            
      return wp_login_url($mepr_options->account_page_url());
  }

  public static function logout_url() /*dontTest*/
  {
    return apply_filters('mepr-logout-url', wp_logout_url(self::login_url()));
  }
  
  public static function site_domain()
  {
    return preg_replace('#^https?://(www\.)?([^\?\/]*)#','$2',home_url());
  }

  public static function is_curl_enabled() {
    return function_exists('curl_version');
  }

  public static function is_post_request() {
    return ( strtolower( $_SERVER['REQUEST_METHOD'] ) == 'post' );
  }

  public static function is_get_request() {
    return ( strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' );
  }

  /* Pieces together the current url like a champ */
  public static function request_url() {
    $url = 'http';

    if($_SERVER['HTTPS'] == 'on') { $url .= 's'; }

    $url .= '://';

    if($_SERVER['SERVER_PORT'] != '80')
      $url .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
    else
      $url .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

    return $url;
  }

  // purely for backwards compatibility (deprecated)
  public static function send_admin_signup_notification($params)
  {
    $txn = MeprTransaction::get_one_by_trans_num($params['trans_num']);
    $txn = new MeprTransaction($txn->id);
    $params = MeprTransactionsHelper::get_email_params($txn); // Yeah, re-set these
    $usr = $txn->user();
    try {
      $aemail = MeprEmailFactory::fetch('MeprAdminSignupEmail');
      $aemail->send($params); 
    }
    catch( Exception $e ) {
      // Fail silently for now
    }
  }

  public static function send_user_signup_notification($params)
  {
    $txn = MeprTransaction::get_one_by_trans_num($params['trans_num']);
    $txn = new MeprTransaction($txn->id);
    $params = MeprTransactionsHelper::get_email_params($txn); // Yeah, re-set these
    $usr = $txn->user();
    try {
      $uemail = MeprEmailFactory::fetch('MeprUserWelcomeEmail');
      $uemail->to = $usr->formatted_email();
      $uemail->send($params);
    }
    catch( Exception $e ) {
      // Fail silently for now
    }
  }

  public static function send_user_receipt_notification($params)
  {
    $txn = MeprTransaction::get_one_by_trans_num($params['trans_num']);
    $txn = new MeprTransaction($txn->id);
    $params = MeprTransactionsHelper::get_email_params($txn); // Yeah, re-set these
    $usr = $txn->user();
    try {
      $uemail = MeprEmailFactory::fetch('MeprUserReceiptEmail');
      $uemail->to = $usr->formatted_email();
      $uemail->send($params);

      $aemail = MeprEmailFactory::fetch('MeprAdminReceiptEmail');
      $aemail->send($params);
    }
    catch( Exception $e ) {
      // Fail silently for now
    }
  }
} // End class
