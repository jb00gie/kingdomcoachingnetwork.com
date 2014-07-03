<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprTransactionsHelper {
  /** Especially for formatting a subscription's price */
  public static function format_currency($txn, $show_symbol = true) {
    $coupon_code = null;
    if($coupon = $txn->coupon()) { $coupon_code = $coupon->post_title; }

    if($obj = $txn->subscription())
      $price = $obj->price;
    else {
      $obj = $txn->product();
      $price = $txn->amount;
    }
 
    return MeprAppHelper::format_price_string( $obj, $price, $show_symbol, $coupon_code );
  }

  // For use in the new/edit transactions form
  public static function payment_methods_dropdown($field_name, $value='manual')
  {
    $mepr_options = MeprOptions::fetch();

    $pms = array_keys($mepr_options->integrations); 

    $value = isset($_POST[$field_name]) ? $_POST[$field_name] : $value;
    
    ?>
    <select name="<?php echo $field_name; ?>" id="<?php echo $field_name; ?>" class="mepr-multi-select mepr-payment-methods-select">
      <option value="manual" <?php selected($value,'manual'); ?>><?php _e('Manual', 'memberpress'); ?>&nbsp;</option>
      <?php
        foreach($pms as $pm_id):
          $obj = $mepr_options->payment_method($pm_id);
          if( $obj instanceof MeprBaseRealGateway ):
            ?>
            <option value="<?php echo $obj->id; ?>" <?php selected($value,$obj->id); ?>><?php printf(__('%1$s (%2$s)', 'memberpress'),$obj->label,$obj->name); ?>&nbsp;</option>
            <?php
          endif;
        endforeach;
      ?>
    </select>
    <?php
  }

  public static function get_email_vars() {
    return array( 'user_id',
                  'user_login',
                  'username',
                  'user_email',
                  'user_first_name',
                  'user_last_name',
                  'user_full_name',
                  'user_address',
                  'usermeta:*',
                  'membership_type',
                  'product_name',
                  'trans_num',
                  'trans_date',
                  'trans_gateway',
                  'payment_amount',
                  'blog_name',
                  'business_name',
                  'login_url',
                  'account_url' );
  }

  public static function get_email_params($txn) {
    $mepr_options = MeprOptions::fetch();
    $usr = $txn->user();
    $prd = $txn->product();
    $pm = $txn->payment_method();

    if( !isset($txn->created_at) or
        empty($txn->created_at) or
        $txn->created_at=='0000-00-00 00:00:00' )
      $ts = time();
    else
      $ts = MeprUtils::mysql_date_to_ts($txn->created_at);

    $txn_date = date(__("F j, Y, g:i a", 'memberpress'),$ts);

    $params = array( 'user_id'          => $usr->ID,
                     'user_login'       => $usr->user_login,
                     'username'         => $usr->user_login,
                     'user_email'       => $usr->user_email,
                     'user_first_name'  => $usr->first_name,
                     'user_last_name'   => $usr->last_name,
                     'user_full_name'   => $usr->full_name(),
                     'user_address'     => $usr->formatted_address(),
                     'membership_type'  => $prd->post_title,
                     'product_name'     => $prd->post_title,
                     'invoice_num'      => $txn->id,
                     'trans_num'        => $txn->trans_num,
                     'trans_date'       => $txn_date,
                     'trans_gateway'    => sprintf(__('%1$s (%2$s)', 'memberpress'), $pm->label, $pm->name),
                     'user_remote_addr' => $_SERVER['REMOTE_ADDR'],
                     'payment_amount'   => preg_replace('~\$~', '\\\$', sprintf('%s'.MeprUtils::format_float($txn->amount), stripslashes($mepr_options->currency_symbol))),
                     'blog_name'        => get_bloginfo('name'),
                     'business_name'    => get_bloginfo('name'),
                     'login_page'       => $mepr_options->login_page_url(),
                     'account_url'      => $mepr_options->account_page_url(),
                     'login_url'        => $mepr_options->login_page_url() );

    // When lifetime, include these subscription vars too
    if( $txn->expires_at == '0000-00-00 00:00:00' ) {
      $params['subscr_num']             = $txn->trans_num;
      $params['subscr_date']            = $txn_date;
      $params['subscr_gateway']         = $params['trans_gateway'];
      $params['subscr_next_billing_at'] = __('Never', 'memberpress');
      $params['subscr_expires_at']      = __('Never', 'memberpress');
      $params['subscr_terms']           = $params['payment_amount'];
      $params['subscr_cc_last4']        = '';
      $params['subscr_cc_month_exp']    = '';
      $params['subscr_cc_year_exp']     = '';
      $params['subscr_update_url']      = '';
      $params['subscr_upgrade_url']     = '';
    }

    $ums = get_user_meta( $usr->ID );
    if(isset($ums) and is_array($ums)) {
      foreach( $ums as $umkey => $um ) {
        // Only support first val for now and yes some of these will be serialized values
        $params["usermeta:{$umkey}"] = $um[0]; 
      }
    }

    // You know we're just going to lump the user record fields in here no problem
    foreach( (array)$usr->rec as $ukey => $uval ) {
      $params["usermeta:{$ukey}"] = $uval;
    }

    return apply_filters( 'mepr_gateway_notification_params', $params, $txn );
  }
}

