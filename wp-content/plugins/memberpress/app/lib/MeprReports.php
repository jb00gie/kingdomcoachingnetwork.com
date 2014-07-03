<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprReports
{
  public static function get_transactions_count($status, $day = false, $month = false, $year = false, $product = null)
  {
    global $wpdb;
    $mepr_db = new MeprDb();
    
    $andmonth = ($month)?" AND MONTH(created_at) = {$month}":"";
    $andday = ($day)?" AND DAY(created_at) = {$day}":"";
    $andyear = ($year)?" AND YEAR(created_at) = {$year}":"";
    $andproduct = (!isset($product) || $product == "all")?"":" AND product_id = {$product}";
    
    $q = "SELECT COUNT(*)
            FROM {$mepr_db->transactions}
            WHERE status = %s
              AND txn_type = %s
              {$andmonth}
              {$andday}
              {$andyear}
              {$andproduct}";
    
    return (int)$wpdb->get_var($wpdb->prepare($q, $status, MeprTransaction::$payment_str));
  }
  
  public static function get_revenue($month = false, $day = false, $year = false, $product = null)
  {
    global $wpdb;
    $mepr_db = new MeprDb();
    
    $andmonth = ($month)?" AND MONTH(created_at) = {$month}":"";
    $andday = ($day)?" AND DAY(created_at) = {$day}":"";
    $andyear = ($year)?" AND YEAR(created_at) = {$year}":"";
    $andproduct = (!isset($product) || $product == "all")?"":" AND product_id = {$product}";
    
    $q = "SELECT SUM(amount)
            FROM {$mepr_db->transactions}
            WHERE status = %s
              AND txn_type = %s
              {$andmonth}
              {$andday}
              {$andyear}
              {$andproduct}";
    
    return $wpdb->get_var($wpdb->prepare($q, MeprTransaction::$complete_str, MeprTransaction::$payment_str));
  }
  
  public static function get_refunds($month = false, $day = false, $year = false, $product = null)
  {
    global $wpdb;
    $mepr_db = new MeprDb();
    
    $andmonth = ($month)?" AND MONTH(created_at) = {$month}":"";
    $andday = ($day)?" AND DAY(created_at) = {$day}":"";
    $andyear = ($year)?" AND YEAR(created_at) = {$year}":"";
    $andproduct = (!isset($product) || $product == "all")?"":" AND product_id = {$product}";
    
    $q = "SELECT SUM(amount)
            FROM {$mepr_db->transactions}
            WHERE status = %s
              AND txn_type = %s
              {$andmonth}
              {$andday}
              {$andyear}
              {$andproduct}";
    
    return $wpdb->get_var($wpdb->prepare($q, MeprTransaction::$refunded_str, MeprTransaction::$payment_str));
  }
  
  public static function get_widget_data($type='amounts')
  {
    global $wpdb;
    $mepr_db = new MeprDb();
    
    $results = array();
    $time = time();

    $selecttype = ($type == 'amounts')?"SUM(amount)":"COUNT(*)";
    
    $q = "SELECT %s AS date,
          (SELECT {$selecttype}
            FROM {$mepr_db->transactions}
            WHERE YEAR(created_at) = %d
              AND MONTH(created_at) = %d
              AND DAY(created_at) = %d
              AND txn_type = '".MeprTransaction::$payment_str."'
              AND status = '".MeprTransaction::$pending_str."') as p,
          (SELECT {$selecttype}
            FROM {$mepr_db->transactions}
            WHERE YEAR(created_at) = %d
              AND MONTH(created_at) = %d
              AND DAY(created_at) = %d
              AND txn_type = '".MeprTransaction::$payment_str."'
              AND status = '".MeprTransaction::$failed_str."') as f,
          (SELECT {$selecttype}
            FROM {$mepr_db->transactions}
            WHERE YEAR(created_at) = %d
              AND MONTH(created_at) = %d
              AND DAY(created_at) = %d
              AND txn_type = '".MeprTransaction::$payment_str."'
              AND status = '".MeprTransaction::$complete_str."') as c,
          (SELECT {$selecttype}
            FROM {$mepr_db->transactions}
            WHERE YEAR(created_at) = %d
              AND MONTH(created_at) = %d
              AND DAY(created_at) = %d
              AND txn_type = '".MeprTransaction::$payment_str."'
              AND status = '".MeprTransaction::$refunded_str."') as r";
    
    for($i = 6; $i >= 0; $i--)
    {
      $ts = $time - MeprUtils::days($i);
      $date = date('M j', $ts);
      $year = date('Y', $ts);
      $month = date('n', $ts);
      $day = date('j', $ts);
      $results[$i] = $wpdb->get_row($wpdb->prepare($q, $date, $year, $month, $day, $year, $month, $day, $year, $month, $day, $year, $month, $day));
    }
    
    return $results;
  }
  
  public static function get_pie_data($year = false, $month = false)
  {
    global $wpdb;
    $mepr_db = new MeprDb();
    
    $andyear = ($year)?" AND YEAR(created_at) = {$year}":"";
    $andmonth = ($month)?" AND MONTH(created_at) = {$month}":"";
    
    $q = "SELECT p.post_title AS product, COUNT(t.id) AS transactions
            FROM {$mepr_db->transactions} AS t
              LEFT JOIN {$wpdb->posts} AS p
                ON t.product_id = p.ID
            WHERE t.status = %s
              AND txn_type = '".MeprTransaction::$payment_str."'
              {$andyear}
              {$andmonth}
          GROUP BY t.product_id";
    
    return $wpdb->get_results($wpdb->prepare($q, MeprTransaction::$complete_str));
  }
  
  public static function get_monthly_data($type, $month, $year, $product)
  {
    global $wpdb;
    $mepr_db = new MeprDb();
    
    $results = array();
    $days_in_month = date('t', mktime(0, 0, 0, $month, 1, $year));
    $andproduct = ($product == "all")?"":" AND product_id = {$product}";
    
    $selecttype = ($type == 'amounts')?"SUM(amount)":"COUNT(*)";
    
    $q = "SELECT %d AS day,
          (SELECT {$selecttype}
            FROM {$mepr_db->transactions}
            WHERE YEAR(created_at) = {$year}
              AND MONTH(created_at) = {$month}
              AND DAY(created_at) = %d
              AND txn_type = '".MeprTransaction::$payment_str."'
              AND status = '".MeprTransaction::$pending_str."'
              {$andproduct}) as p,
          (SELECT {$selecttype}
            FROM {$mepr_db->transactions}
            WHERE YEAR(created_at) = {$year}
              AND MONTH(created_at) = {$month}
              AND DAY(created_at) = %d
              AND txn_type = '".MeprTransaction::$payment_str."'
              AND status = '".MeprTransaction::$failed_str."'
              {$andproduct}) as f,
          (SELECT {$selecttype}
            FROM {$mepr_db->transactions}
            WHERE YEAR(created_at) = {$year}
              AND MONTH(created_at) = {$month}
              AND DAY(created_at) = %d
              AND txn_type = '".MeprTransaction::$payment_str."'
              AND status = '".MeprTransaction::$complete_str."'
              {$andproduct}) as c,
          (SELECT {$selecttype}
            FROM {$mepr_db->transactions}
            WHERE YEAR(created_at) = {$year}
              AND MONTH(created_at) = {$month}
              AND DAY(created_at) = %d
              AND txn_type = '".MeprTransaction::$payment_str."'
              AND status = '".MeprTransaction::$refunded_str."'
              {$andproduct}) as r";
    
    for($i = 1; $i <= $days_in_month; $i++)
      $results[$i] = $wpdb->get_row($wpdb->prepare($q, $i, $i, $i, $i, $i));
    
    return $results;
  }
  
  public static function get_yearly_data($type, $year, $product)
  {
    global $wpdb;
    $mepr_db = new MeprDb();
    
    $results = array();
    $andproduct = ($product == "all")?"":" AND product_id = {$product}";
    
    $selecttype = ($type == "amounts")?"SUM(amount)":"COUNT(*)";
    
    $q = "SELECT %d AS month,
          (SELECT {$selecttype}
            FROM {$mepr_db->transactions}
            WHERE YEAR(created_at) = {$year}
              AND MONTH(created_at) = %d
              AND txn_type = '".MeprTransaction::$payment_str."'
              AND status = '".MeprTransaction::$pending_str."'
              {$andproduct}) as p,
          (SELECT {$selecttype}
            FROM {$mepr_db->transactions}
            WHERE YEAR(created_at) = {$year}
              AND MONTH(created_at) = %d
              AND txn_type = '".MeprTransaction::$payment_str."'
              AND status = '".MeprTransaction::$failed_str."'
              {$andproduct}) as f,
          (SELECT {$selecttype}
            FROM {$mepr_db->transactions}
            WHERE YEAR(created_at) = {$year}
              AND MONTH(created_at) = %d
              AND txn_type = '".MeprTransaction::$payment_str."'
              AND status = '".MeprTransaction::$complete_str."'
              {$andproduct}) as c,
          (SELECT {$selecttype}
            FROM {$mepr_db->transactions}
            WHERE YEAR(created_at) = {$year}
              AND MONTH(created_at) = %d
              AND txn_type = '".MeprTransaction::$payment_str."'
              AND status = '".MeprTransaction::$refunded_str."'
              {$andproduct}) as r";
    
    for($i = 1; $i <= 12; $i++)
      $results[$i] = $wpdb->get_row($wpdb->prepare($q, $i, $i, $i, $i, $i));
    
    return $results;
  }
  
  public static function get_first_year()
  {
    global $wpdb;
    $mepr_db = new MeprDb();
    
    $q = "SELECT YEAR(created_at)
            FROM {$mepr_db->transactions}
            WHERE txn_type = '".MeprTransaction::$payment_str."'
          ORDER BY created_at
          LIMIT 1";
    
    $year = $wpdb->get_var($q);
    
    if($year)
      return $year;
    
    return date('Y');
  }
  
  public static function get_last_year()
  {
    global $wpdb;
    $mepr_db = new MeprDb();

    $q = $wpdb->prepare( "SELECT YEAR(created_at) " .
                           "FROM {$mepr_db->transactions} " .
                          "WHERE txn_type = %s " .
                          "ORDER BY created_at DESC " .
                          "LIMIT 1",
                         MeprTransaction::$payment_str );

    $year = $wpdb->get_var($q);

    if($year) { return $year; }

    return date('Y');
  }

  public static function get_total_members_count() {
    global $wpdb;
    $mepr_db = new MeprDb();

/*
    $query = "SELECT COUNT(*) " .
               "FROM ( SELECT t.user_id " .
                        "FROM {$mepr_db->transactions} AS t " .
                       "WHERE t.status IN (%s,%s) " .
                       "GROUP BY t.user_id ) AS member_ids";
*/
    $query = "SELECT COUNT(DISTINCT t.user_id) " .
               "FROM {$mepr_db->transactions} AS t " .
              "WHERE t.status IN (%s,%s)";

    $query = $wpdb->prepare( $query,
                             MeprTransaction::$complete_str,
                             MeprTransaction::$confirmed_str );

    return $wpdb->get_var( $query );
  }

  public static function get_active_members_count() {
    global $wpdb;
    $mepr_db = new MeprDb();

    $query = "SELECT COUNT(DISTINCT t.user_id) " .
               "FROM {$mepr_db->transactions} AS t " .
              "WHERE t.status IN (%s,%s) " .
                "AND ( t.expires_at = '0000-00-00 00:00:00' " .
                 "OR t.expires_at >= NOW() )";

    $query = $wpdb->prepare( $query,
                             MeprTransaction::$complete_str,
                             MeprTransaction::$confirmed_str );

    return $wpdb->get_var( $query );
  }

  public static function get_inactive_members_count() {
    global $wpdb;
    $mepr_db = new MeprDb();

    $query = "SELECT COUNT(DISTINCT u.ID) " .
                "FROM {$mepr_db->transactions} AS t " .
                  "JOIN {$wpdb->users} AS u " .
                    "ON t.user_id = u.ID " .
              "WHERE t.status IN (%s,%s) " .
                "AND t.expires_at IS NOT NULL " .
                "AND t.expires_at <> '0000-00-00 00:00:00' " .
                "AND t.expires_at < NOW()";

    $query = $wpdb->prepare( $query,
                             MeprTransaction::$complete_str,
                             MeprTransaction::$confirmed_str );

    return $wpdb->get_var( $query );
  }

  public static function get_free_active_members_count() {
    global $wpdb;
    $mepr_db = new MeprDb();

    $query = "SELECT COUNT(*) AS famc " .
               "FROM ( SELECT t.user_id AS user_id, SUM(t.amount) AS lv " .
                        "FROM {$mepr_db->transactions} AS t " .
                       "WHERE t.status IN (%s,%s) " .
                       "GROUP BY t.user_id ) as lvsums " .
              "WHERE lvsums.lv <= 0";

    $query = $wpdb->prepare( $query,
                             MeprTransaction::$complete_str,
                             MeprTransaction::$confirmed_str );

    return $wpdb->get_var( $query );
  }

  public static function get_average_lifetime_value() {
    global $wpdb;
    $mepr_db = new MeprDb();

    $query = "SELECT AVG(lv) AS alv " .
               "FROM ( SELECT SUM(t.amount) AS lv " .
                        "FROM {$mepr_db->transactions} AS t " .
                       "WHERE t.status IN (%s,%s) " .
                       "GROUP BY t.user_id ) as lvsums";

    // Gotta check for confirmed too ... we want all "members" included in the calculation
    $query = $wpdb->prepare( $query,
                             MeprTransaction::$confirmed_str,
                             MeprTransaction::$complete_str );

    return $wpdb->get_var( $query );
  }
  
  //Wrapper function
  public static function make_table_date($month, $day, $year, $format = 'm/d/Y')
  {
    $ts = mktime(0, 0, 1, $month, $day, $year);
    return MeprUtils::get_date_from_ts($ts, $format);
  }
} //End class

