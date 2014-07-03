<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprAppHelper
{
  public static function info_tooltip($id, $title, $info)
  {
    ?>
    <span id="mepr-tooltip-<?php echo $id; ?>" class="mepr-tooltip">
      <span><i class="mp-icon mp-icon-info-circled mp-16"></i></span>
      <span class="mepr-data-title mepr-hidden"><?php echo $title; ?></span>
      <span class="mepr-data-info mepr-hidden"><?php echo $info; ?></span>
    </span>
    <?php
  }
  
  public static function format_currency($number, $show_symbol = true, $free_str = true, $truncate_zeroes = false)
  {
    global $wp_locale;

    $dp = $wp_locale->number_format['decimal_point'];
    $ts = $wp_locale->number_format['thousands_sep'];

    $mepr_options = MeprOptions::fetch();
    
    if((float)$number > 0.00 or !$free_str) {
      $rstr = (string)number_format( (float)$number, 2, $dp, $ts );
      if($show_symbol) { $rstr = $mepr_options->currency_symbol . $rstr; }
    }
    else
      $rstr = __('Free','memberpress');
    
    if($truncate_zeroes) { $rstr = preg_replace('/' . preg_quote($dp) . '00$/', '', $rstr); }

    return $rstr;
  }
  
  public static function format_date($datetime, $default = null, $format = 'Y-m-d')
  {
    if(is_null($default)) { $default = __('Unknown','memberpress'); }
    if(empty($datetime) or preg_match('#^0000-00-00#',$datetime)) { return $default; }
    $ts = strtotime($datetime);
    return date($format, $ts);
  }

  public static function page_template_dropdown($field_name, $field_value=null) {
    $templates = get_page_templates();
    //$field_value = isset($_POST[$field_name])?$_POST[$field_name]:null;
    ?>
    <select name="<?php echo $field_name; ?>" id="<?php echo $field_name; ?>" class="mepr-dropdown mepr-page-templates-dropdown">
    <?php
      foreach($templates as $template_name => $template_filename) {
        ?>
        <option value="<?php echo $template_filename; ?>" <?php selected($template_filename,$field_value); ?>><?php echo $template_name; ?>&nbsp;</option>
        <?php
      }
    ?>
    </select>
    <?php
  }

  public static function human_readable_status( $status, $type='transaction' ) {
    if( $type == 'transaction' ) {
      switch( $status ) {
        case MeprTransaction::$pending_str:
          return __('Pending','memberpress');
        case MeprTransaction::$failed_str:
          return __('Failed','memberpress');
        case MeprTransaction::$complete_str:
          return __('Complete','memberpress');
        case MeprTransaction::$refunded_str:
          return __('Refunded','memberpress');
        default:
          return __('Unknown','memberpress');
      }
    }
    elseif( $type == 'subscription' ) {
      switch( $status ) {
        case MeprSubscription::$pending_str:
          return __('Pending','memberpress');
        case MeprSubscription::$active_str:
          return __('Enabled','memberpress');
        case MeprSubscription::$cancelled_str:
          return __('Stopped','memberpress');
        case MeprSubscription::$suspended_str:
          return __('Paused','memberpress');
        default:
          return __('Unknown','memberpress');
      }
    }
  }

  public static function format_price_string( $obj, $price=0.00, $show_symbol=true, $coupon_code=null ) {
    $mepr_options = MeprOptions::fetch();
    $coupon = false;

    if(empty($coupon_code))
      $coupon_code = null;
    else
      $coupon = MeprCoupon::get_one_from_code($coupon_code);
      //We're not accounting for this coupon in the price below anywhere currently

    // Just truncate the zeros if it's an even dollar amount
    $fprice = MeprAppHelper::format_currency($price, $show_symbol);
    $fprice = preg_replace("#[\.,]00$#", '', (string)$fprice);

    $period = (int)$obj->period;
    $period_type = $obj->period_type;
    $period_type_str = strtolower( MeprUtils::period_type_name($period_type,$period) );

    if((float)$price <= 0.00) {
      if($period_type == 'lifetime')
        $price_str = __('Free', 'memberpress');
      elseif($period==1)
        $price_str = sprintf(__('Free for a %1$s', 'memberpress'), $period_type_str);
      else
        $price_str = sprintf(__('Free for %1$d %2$s', 'memberpress'), $period, $period_type_str);
    }
    elseif($period_type == 'lifetime') {
      $price_str = $fprice;
      if( $obj instanceof MeprProduct and $mepr_options->pro_rated_upgrades and $obj->is_upgrade_or_downgrade() ) {
        $price_str .= __(' (prorated)', 'memberpress');
      }
    }
    else {
      if( $obj->trial ) {
        if( $obj->trial_amount > 0.00 ) {
          $trial_str = MeprAppHelper::format_currency($obj->trial_amount, $show_symbol);
          $trial_str = preg_replace("#[\.,]00$#", '', (string)$trial_str);
        }
        else
          $trial_str = __('free', 'memberpress');

        if( ( $obj instanceof MeprSubscription and $obj->prorated_trial ) or
            ( $obj instanceof MeprProduct and $mepr_options->pro_rated_upgrades and $obj->is_upgrade_or_downgrade() ) ) {
          if( $obj instanceof MeprProduct ) {
            global $current_user;
            MeprUtils::get_currentuserinfo();
            $usr = new MeprUser($current_user->ID);
            $grp = $obj->group();

            if($old_sub = $usr->subscription_in_group($grp->ID) )
              $upgrade_str = __(' (proration)','memberpress');
            else
              $upgrade_str = '';
          }
          else
            $upgrade_str = __(' (proration)','memberpress');
        }
        else
          $upgrade_str = '';

        $sub_str = _n( '%1$s day for %2$s%3$s then ',
                       '%1$s days for %2$s%3$s then ',
                       $obj->trial_days, 'memberpress' );

        $price_str = sprintf( $sub_str, $obj->trial_days, $trial_str, $upgrade_str );
      }
      else
        $price_str = '';

      if( $obj->limit_cycles and $obj->limit_cycles_num==1 ) {
        $price_str .= $fprice;
        if( $obj->limit_cycles_action=='expire' )
          $price_str .= sprintf( __( ' for %1$d %2$s of access', 'memberpress' ), $period, $period_type_str );
      }
      elseif( $obj->limit_cycles ) // Prefix with payments count
        $price_str .= sprintf( _n( '%1$d payment of ', '%1$d payments of ',
                                   $obj->limit_cycles_num, 'memberpress' ),
                               $obj->limit_cycles_num );

      if( !$obj->limit_cycles or ( $obj->limit_cycles and $obj->limit_cycles_num > 1 ) ) {
        if( $period == 1 )
          $price_str .= sprintf(__('%1$s billed each %2$s', 'memberpress'), $fprice, $period_type_str);
        else
          $price_str .= sprintf(__('%1$s billed every %2$d %3$s', 'memberpress'), $fprice, $period, $period_type_str);
      }
    }

    if($period_type == 'lifetime') {
      if($obj->expire_type=='delay') {
        $expire_str = strtolower( MeprUtils::period_type_name($obj->expire_unit,$obj->expire_after) );
        $price_str .= sprintf( __( ' for %1$d %2$s of access', 'memberpress' ), $obj->expire_after, $expire_str );
      }
      else if($obj->expire_type=='fixed') {
        $expire_ts = strtotime( $obj->expire_fixed );
        $expire_str = date( __( 'D, M j, Y', 'memberpress' ), $expire_ts );
        $price_str .= sprintf( __( ' for access until %s', 'memberpress' ), $expire_str );
      }
    }

    if(!empty($coupon)) { $price_str .= sprintf(__(' with coupon %s','memberpress'), $coupon_code); }

    return apply_filters('mepr-price-string', $price_str, $obj, $show_symbol);
  }

  public static function display_emails($etype='MeprBaseEmail',$args=array()) {
    ?><div class="mepr-emails-wrap"><?php

    $emails = MeprEmailFactory::all($etype,$args);

    foreach( $emails as $email ) {
      if($email->show_form) { $email->display_form(); }
    }

    ?></div><?php
  }
} //End class
