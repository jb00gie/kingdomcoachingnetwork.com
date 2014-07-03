<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprSubscriptionsHelper {
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
                  'signup_url',
                  'subscr_num',
                  'subscr_date',
                  'subscr_gateway',
                  'subscr_next_billing_at',
                  'subscr_expires_at',
                  'subscr_terms',
                  'subscr_cc_last4',
                  'subscr_cc_month_exp',
                  'subscr_cc_year_exp',
                  'subscr_update_url',
                  'subscr_upgrade_url',
                  'blog_name',
                  'business_name',
                  'login_url',
                  'account_url',
                  'login_page' );
  }

  public static function get_email_params($sub) {
    $mepr_options = MeprOptions::fetch();
    $usr = $sub->user();
    $prd = $sub->product();
    $pm = $sub->payment_method();

    if( !isset($sub->created_at) or empty($sub->created_at) or
        $sub->created_at=='0000-00-00 00:00:00' )
      $ts = time();
    else
      $ts = MeprUtils::mysql_date_to_ts($sub->created_at);

    $sub_date = date(__("F j, Y, g:i a", 'memberpress'), $ts);

    if( $sub->expires_at == '0000-00-00 00:00:00' )
      $expires_at = __('Never', 'memberpress'); 
    else
      $expires_at = MeprAppHelper::format_date( $sub->expires_at, '', 'F j, Y, g:i a' );

    $params = array( 'user_id'                => $usr->ID,
                     'user_login'             => $usr->user_login,
                     'username'               => $usr->user_login,
                     'user_email'             => $usr->user_email,
                     'user_first_name'        => $usr->first_name,
                     'user_last_name'         => $usr->last_name,
                     'user_full_name'         => $usr->full_name(),
                     'user_address'           => $usr->formatted_address(),
                     'membership_type'        => preg_replace('~\$~', '\\\$', $prd->post_title),
                     'product_name'           => preg_replace('~\$~', '\\\$', $prd->post_title),
                     'signup_url'             => $prd->url(),
                     'subscr_num'             => $sub->subscr_id,
                     'subscr_date'            => $sub_date,
                     'subscr_gateway'         => sprintf(__('%1$s (%2$s)', 'memberpress'), $pm->label, $pm->name),
                     'subscr_next_billing_at' => MeprAppHelper::format_date( $sub->next_billing_at, '', 'F j, Y, g:i a' ),
                     'subscr_expires_at'      => $expires_at,
                     'subscr_terms'           => preg_replace('~\$~', '\\\$', MeprSubscriptionsHelper::format_currency($sub)),
                     'subscr_cc_num'          => $sub->cc_num(),
                     'subscr_cc_month_exp'    => sprintf( '%02d', $sub->cc_exp_month ),
                     'subscr_cc_year_exp'     => $sub->cc_exp_year,
                     'subscr_update_url'      => $mepr_options->login_page_url( 'redirect_to=' . urlencode($sub->update_url() ) ),
                     'subscr_upgrade_url'     => $mepr_options->login_page_url( 'redirect_to=' . urlencode($sub->upgrade_url() ) ),
                     'blog_name'              => get_bloginfo('name'),
                     'business_name'          => get_bloginfo('name'),
                     'login_page'             => $mepr_options->login_page_url(),
                     'account_url'            => $mepr_options->account_page_url(),
                     'login_url'              => $mepr_options->login_page_url() );

    $ums = get_user_meta( $usr->ID );
    foreach( $ums as $umkey => $um ) {
      // Only support first val for now and yes some of these will be serialized values so deal with it :)
      $params["usermeta:{$umkey}"] = $um[0]; 
    }

    // You know we're just going to lump the user record fields in here no problem
    foreach( (array)$usr->rec as $ukey => $uval ) {
      $params["usermeta:{$ukey}"] = $uval;
    }

    return apply_filters( 'mepr-subscription-email-params', $params, $sub );
  }

  /** Especially for formatting a subscription's price */
  public static function format_currency($sub, $show_symbol = true) {
    $coupon_code = null;
    if($coupon = $sub->coupon()) { $coupon_code = $coupon->post_title; }
    return MeprAppHelper::format_price_string( $sub, $sub->price, $show_symbol, $coupon_code );
  }
}

