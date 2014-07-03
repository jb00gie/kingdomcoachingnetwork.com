<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

$products = get_posts( array( 'numberposts' => -1,
                              'post_type' => 'memberpressproduct',
                              'post_status' => 'publish' ) );

if($products != null):
?>
  <div id="mepr-coupons-form">
    <div class="mepr-options-pane">
      <?php _e('Discount:', 'memberpress'); ?>
      <input type="text" size="5" name="<?php echo MeprCoupon::$discount_amount_str; ?>" value="<?php echo $c->discount_amount; ?>" />
      <select name="<?php echo MeprCoupon::$discount_type_str; ?>">
        <option value="percent" <?php echo ($c->discount_type == 'percent')?'selected="selected"':''; ?>><?php _e('%', 'memberpress'); ?></option>
        <option value="dollar" <?php echo ($c->discount_type == 'dollar')?'selected="selected"':''; ?>><?php echo $mepr_options->currency_code; ?></option>
      </select>
      <?php MeprAppHelper::info_tooltip( 'mepr-coupon-discount',
                                         __('Coupon Discount', 'memberpress'),
                                         __('<b>Recurring Products</b>: This discount will not apply to paid trials but will apply to all recurring transactions associated with the subscription.<br/><br/><b>Lifetime Products</b>: This discount will apply directly to the lifetime product\'s one-time payment.', 'memberpress')); ?>
    </div>
    <div class="mepr-options-pane">
      <span>
        <?php $usage_amount = (intval($c->usage_amount) <= 0) ? '∞' : $c->usage_amount; ?>
        <?php printf( __('This coupon can be used %s times.', 'memberpress'),
                      '<input type="text" maxlength="4" size="4" name="'.MeprCoupon::$usage_amount_str.'" value="'.$usage_amount.'" />' ); ?>
        <?php MeprAppHelper::info_tooltip( 'mepr-coupon-usage-amount',
                                           __('Number of Coupon Uses', 'memberpress'),
                                           __('This determines the number of times this coupon can be used by individual customers ... it does not indicate how many recurrances it will be used on.<br/><br/>Set to "0" for an infinite number of uses.', 'memberpress') ); ?>
      </span>
    </div>
    <div class="mepr-coupons-trial-box mepr-options-pane">
      <div>
        <?php $checked = (isset($c->trial) && $c->trial)?'checked="checked"':''; ?>
        <input type="checkbox" name="<?php echo MeprCoupon::$trial_str; ?>" id="<?php echo MeprCoupon::$trial_str; ?>" <?php echo $checked; ?> /> <label for="<?php echo MeprCoupon::$trial_str; ?>"><?php _e('Override Trial Period', 'memberpress'); ?></label>
        <?php MeprAppHelper::info_tooltip( 'mepr-coupon-custom-trial',
                                           __('Coupon Override Trial Settings', 'memberpress'),
                                           __('<b>Recurring Products</b>: When this coupon is used, if the product already has a trial then this will replace it ... if the product doesn\'t have a trial then this trial will be used.<br/><br/><b>Lifetime Products</b>: Because trials aren\'t available for lifetime products, these settings will be ignored when this coupon code is used with them.', 'memberpress') ); ?>
      </div>
      <div class="mepr-coupons-trial-hidden">
        <div><strong><?php _e('# of Days:', 'memberpress'); ?></strong> <input name="<?php echo MeprCoupon::$trial_days_str; ?>" id="<?php echo MeprCoupon::$trial_days_str; ?>" type="text" size="3" value="<?php echo $c->trial_days; ?>" /></div>
        <div><strong><?php _e('Trial Cost:', 'memberpress'); ?></strong> <?php echo $mepr_options->currency_symbol; ?><input name="<?php echo MeprCoupon::$trial_amount_str; ?>" id="<?php echo MeprCoupon::$trial_amount_str; ?>" size="7" type="text" value="<?php echo MeprUtils::format_float($c->trial_amount); ?>" /></div>
      </div>
    </div>
    <div class="mepr-options-pane">
      <input type="checkbox" name="<?php echo MeprCoupon::$should_expire_str; ?>" id="<?php echo MeprCoupon::$should_expire_str; ?>" class="should-expire" <?php echo ($c->should_expire)?'checked="checked"':''; ?> />
      <label for="<?php echo MeprCoupon::$should_expire_str; ?>"><?php _e('Coupon expiration', 'memberpress'); ?></label>
      <div class="mepr-coupon-expires">
        <span class="description"><small><?php _e('Month', 'memberpress'); ?></small></span>
        <select name="<?php echo MeprCoupon::$expires_on_month_str; ?>">
          <?php MeprCouponsHelper::months_options($c->expires_on); ?>
        </select>
        <span class="description"><small><?php _e('Day', 'memberpress'); ?></small></span>
        <input type="text" size="2" maxlength="2" name="<?php echo MeprCoupon::$expires_on_day_str; ?>" value="<?php echo MeprUtils::get_date_from_ts($c->expires_on, 'j'); ?>" />
        <span class="description"><small><?php _e('Year', 'memberpress'); ?></small></span>
        <input type="text" size="4" maxlength="4" name="<?php echo MeprCoupon::$expires_on_year_str; ?>" value="<?php echo MeprUtils::get_date_from_ts($c->expires_on, 'Y'); ?>" />
      </div>
    </div>
    <div class="mepr-options-pane">
      <?php _e('Apply coupon to the following Products:', 'memberpress'); ?><br/>
      <?php MeprCouponsHelper::products_dropdown(MeprCoupon::$valid_products_str, $c->valid_products); ?><br/>
      <span class="description"><?php _e('Hold the Control Key (Command Key on the Mac) in order to select or deselect multiple products', 'memberpress'); ?></span>
    </div>
    <!-- The NONCE below prevents post meta from being blanked on move to trash -->
    <input type="hidden" name="<?php echo MeprCoupon::$nonce_str; ?>" value="<?php echo wp_create_nonce(MeprCoupon::$nonce_str.wp_salt()); ?>" />
    <!-- jQuery i18n data -->
    <div id="save-coupon-helper" style="display:none;" data-value="<?php _e('Save Coupon', 'memberpress'); ?>"></div>
    <div id="coupon-message-helper" style="display:none;" data-value="<?php _e('Coupon Saved', 'memberpress'); ?>"></div>
  </div>
<?php
else:
?>
  <div id="mepr-coupons-form">
    <strong><?php _e('You cannot create coupons until you have added at least 1 Product.', 'memberpress'); ?></strong>
    <!-- jQuery i18n data -->
    <div id="save-coupon-helper" style="display:none;" data-value="<?php _e('Save Coupon', 'memberpress'); ?>"></div>
    <div id="coupon-message-helper" style="display:none;" data-value="<?php _e('Coupon Saved', 'memberpress'); ?>"></div>
  </div>
<?php
endif;

