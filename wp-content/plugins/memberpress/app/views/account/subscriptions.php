<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

do_action('mepr_before_account_subscriptions', $mepr_current_user);

if(!empty($subscriptions)) {
  $alt = false;
?>
  <table id="mepr-account-subscriptions-table">
    <thead>
    <tr>
      <th><?php _e('Product', 'memberpress'); ?></th>
      <th><?php _e('Subscription', 'memberpress'); ?></th>
      <th><?php _e('Active', 'memberpress'); ?></th>
      <th><?php _e('Created', 'memberpress'); ?></th>
      <th><?php _e('Expires', 'memberpress'); ?></th>
      <th><?php _e('Card Exp', 'memberpress'); ?></th>
      <th> </th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach($subscriptions as $s)
    {
      if(trim($s->sub_type) == 'transaction') {
        $is_sub = false;
        $txn = $sub = new MeprTransaction($s->ID);
        $pm  = $txn->payment_method();
        $prd = $txn->product();
        $default = __('Never','memberpress');
      }
      else {
        $is_sub = true;
        $sub = new MeprSubscription($s->ID);
        $txn = $sub->latest_txn();
        $pm  = $sub->payment_method();
        $prd = $sub->product();
        if(trim($txn->expires_at) == '0000-00-00 00:00:00' or empty($txn->expires_at))
          $default = __('Never','memberpress');
        else
          $default = __('Unknown','memberpress');
      }

      $mepr_options = MeprOptions::fetch();
      $alt = !$alt; // Facilitiates the alternating lines

      ?>
      <tr id="mepr-subscription-row-<?php echo $s->ID; ?>" class="mepr-subscription-row <?php echo (isset($alt) && !$alt)?'mepr-alt-row':''; ?>">
        <td>
          <!-- PRODUCT ACCESS URL -->
          <?php if(isset($prd->access_url) && !empty($prd->access_url)): ?>
            <div class="mepr-account-product"><a href="<?php echo stripslashes($prd->access_url); ?>"><?php echo $prd->post_title; ?></a></div>
          <?php else: ?>
            <div class="mepr-account-product"><?php echo $prd->post_title; ?></div>
          <?php endif; ?>

          <div class="mepr-account-subscr-id"><?php echo $s->subscr_id; ?></div>
        </td>
        <td>
          <div class="mepr-account-auto-rebill">
            <?php
              if($is_sub):
                echo ($s->status == MeprSubscription::$active_str)?__('Enabled', 'memberpress'):MeprAppHelper::human_readable_status($s->status, 'subscription');
              elseif( $s->expires_at=='0000-00-00 00:00:00' ): 
                _e('Lifetime', 'memberpress');
              else:
                _e('None', 'memberpress');
              endif;
            ?></div>
          <div class="mepr-account-terms"><?php echo MeprTransactionsHelper::format_currency($txn); ?></div>

          <?php if( $is_sub and ( $nba = $sub->next_billing_at ) ): ?>
            <div class="mepr-account-rebill"><?php printf(__('Next Billing: %s','memberpress'), MeprAppHelper::format_date($nba)); ?></div>
          <?php endif; ?>

        </td>
        <td><div class="mepr-account-active"><?php echo $s->active; ?></div></td>
        <td><div class="mepr-account-created-at"><?php echo MeprAppHelper::format_date($s->created_at); ?></div></td>
        <td><div class="mepr-account-expires-at"><?php echo MeprAppHelper::format_date($s->expires_at, $default); ?></div></td>
        <td>
          <?php if( $exp_mo = $sub->cc_exp_month and
                    $exp_yr = $sub->cc_exp_year ): ?>
            <?php $cc_class = ( ( $sub->cc_expiring_before_next_payment() ) ? ' mepr-inactive' : '' ); ?>
            <div class="mepr-account-cc-exp<?php echo $cc_class; ?>"><?php printf(__('%1$02d-%2$d','memberpress'), $exp_mo, $exp_yr); ?></div>
          <?php endif; ?>
        </td>
        <td><div class="mepr-account-actions"><?php ($is_sub and $pm instanceof MeprBaseRealGateway and ($s->active or $s->status==MeprSubscription::$active_str))?$pm->print_user_account_subscription_row_actions($s->ID):(!$is_sub and !empty($prd->ID)?(($prd->group() !== false)?MeprAccountHelper::group_link($prd):''):MeprAccountHelper::purchase_link($prd)); ?></div></td>
      </tr>
    <?php
    }
    do_action('mepr-account-subscriptions-table', $mepr_current_user, $subscriptions);
    ?>
    </tbody>
  </table>
  <div id="mepr-subscriptions-paging">
    <?php if($prev_page) { ?>
      <a href="<?php echo "{$account_url}{$delim}action=subscriptions&currpage={$prev_page}"; ?>">&lt;&lt; <?php _e('Previous Page', 'memberpress'); ?></a>
    <?php } if($next_page) { ?>
      <a href="<?php echo "{$account_url}{$delim}action=subscriptions&currpage={$next_page}"; ?>" style="float:right;"><?php _e('Next Page', 'memberpress'); ?> &gt;&gt;</a>
    <?php } ?>
  </div><div style="clear:both"></div>
<?php
}
else {
  _e('You have no active subscriptions to display.', 'memberpress');
}

do_action('mepr_account_subscriptions', $mepr_current_user);
