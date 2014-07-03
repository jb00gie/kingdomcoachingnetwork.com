<?php
  if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}
  $mepr_options = MeprOptions::fetch();
?>

<div id="mepr-products-form">
  <table>
    <tr>
      <td><strong><?php _e('Price:', 'memberpress'); ?></strong>&nbsp;</td>
      <td><?php echo $mepr_options->currency_symbol; ?><input name="<?php echo MeprProduct::$price_str; ?>" id="<?php echo MeprProduct::$price_str; ?>" type="text" size="7" value="<?php echo MeprUtils::format_float($product->price); ?>" /></td>
    </tr>
    <tr>
      <td><strong><?php _e('Billing Type:', 'memberpress'); ?></strong>&nbsp;</td>
      <td>
        <select id="mepr-product-billing-type">
          <option value="recurring"<?php echo (($product->period_type!='lifetime')?' selected="selected"':''); ?>><?php _e('Recurring', 'memberpress'); ?></option>
          <option value="single"<?php echo (($product->period_type=='lifetime')?' selected="selected"':''); ?>><?php _e('One-Time', 'memberpress'); ?></option>
        </select>
      </td>
    </tr>
  </table>

  <input type="hidden"
         id="<?php echo MeprProduct::$period_str; ?>"
         name="<?php echo MeprProduct::$period_str; ?>"
         value="<?php echo $product->period; ?>">
  <input type="hidden"
         id="<?php echo MeprProduct::$period_type_str; ?>"
         name="<?php echo MeprProduct::$period_type_str; ?>"
         value="<?php echo $product->period_type; ?>">

  <div id="mepr-non-recurring-options" class="mepr-hidden">
    <table id="mepr-interval-options">
      <tr>
        <td class="mepr-interval-label"><strong><?php _e('Access:', 'memberpress'); ?></strong></td>
        <td>
          <select name="<?php echo MeprProduct::$expire_type_str; ?>" id="<?php echo MeprProduct::$expire_type_str; ?>">
            <option value="none"<?php selected($product->expire_type,"none"); ?>><?php _e('Lifetime', 'memberpress'); ?>&nbsp;</option>
            <option value="delay"<?php selected($product->expire_type,"delay"); ?>><?php _e('Expire', 'memberpress'); ?>&nbsp;</option>
            <option value="fixed"<?php selected($product->expire_type,"fixed"); ?>><?php _e('Fixed Expire', 'memberpress'); ?>&nbsp;</option>
          </select>
          <div class="mepr-product-expire-delay mepr-sub-option-arrow mepr-hidden">
            <span><?php _e('after', 'memberpress'); ?></span>
            <input type="text" size="2" 
                   name="<?php echo MeprProduct::$expire_after_str; ?>"
                   id="<?php echo MeprProduct::$expire_after_str; ?>"
                   value="<?php echo $product->expire_after; ?>" />
            <select name="<?php echo MeprProduct::$expire_unit_str; ?>"
                    id="<?php echo MeprProduct::$expire_unit_str; ?>">
              <option value="days"<?php selected($product->expire_unit,'days'); ?>><?php _e("days", 'memberpress'); ?></option>
              <option value="weeks"<?php selected($product->expire_unit,'weeks'); ?>><?php _e("weeks", 'memberpress'); ?></option>
              <option value="months"<?php selected($product->expire_unit,'months'); ?>><?php _e("months", 'memberpress'); ?></option>
              <option value="years"<?php selected($product->expire_unit,'years'); ?>><?php _e("years", 'memberpress'); ?></option>
            </select>
          </div>
          <div class="mepr-product-expire-fixed mepr-sub-option-arrow mepr-hidden">
            <span><?php _e('on', 'memberpress'); ?></span>
            <input type="text"
                   class="mepr-date-picker"
                   size="10"
                   name="<?php echo MeprProduct::$expire_fixed_str; ?>"
                   id="<?php echo MeprProduct::$expire_fixed_str; ?>"
                   value="<?php echo $product->expire_fixed; ?>" />
          </div>
        </td>
      </tr>
    </table>
  </div>

  <div id="mepr-recurring-options" class="mepr-hidden">
    <table id="mepr-interval-options">
      <tr>
        <td class="mepr-interval-label"><strong><?php _e('Interval:', 'memberpress'); ?></strong>&nbsp;</td>
        <td>
          <?php echo MeprProductsHelper::preset_period_dropdown( MeprProduct::$period_str,
                                                                 MeprProduct::$period_type_str
                                                               ); ?>
          <div id="mepr-product-custom-period" class="mepr-sub-option-arrow mepr-hidden">
            <input type="text" size="2"
                   id="<?php echo MeprProduct::$period_str; ?>-custom"
                   name="<?php echo MeprProduct::$period_str; ?>-custom" />
            <?php echo MeprProductsHelper::period_type_dropdown( MeprProduct::$period_type_str ); ?>
          </div>
        </td>
      </tr>
    </table>
    <div class="mepr-product-trial-box mepr-meta-sub-pane">
      <?php $checked = (isset($product->trial) && $product->trial)?'checked="checked"':''; ?>
      <input type="checkbox" name="<?php echo MeprProduct::$trial_str; ?>" id="<?php echo MeprProduct::$trial_str; ?>" <?php echo $checked; ?> /> <label for="_mepr_product_trial"><?php _e('Trial Period', 'memberpress'); ?></label>
      <div id="disable-trial-notice mepr-meta-sub-pane" data-value="<?php _e('Price must be greater than 0.00 to choose recurring subscriptions.', 'memberpress'); ?>" class="mepr_hidden"></div>
      <div class="mepr-product-trial-hidden mepr-sub-option mepr-hidden">
        <input name="<?php echo MeprProduct::$trial_days_str; ?>" id="<?php echo MeprProduct::$trial_days_str; ?>" type="text" size="2" value="<?php echo $product->trial_days; ?>" />
        <span><?php _e('days for', 'memberpress'); ?></span>
        <?php echo $mepr_options->currency_symbol; ?><input name="<?php echo MeprProduct::$trial_amount_str; ?>" id="<?php echo MeprProduct::$trial_amount_str; ?>" size="7" type="text" value="<?php echo MeprUtils::format_float($product->trial_amount); ?>" />
      </div>
    </div>
    <div class="mepr-product-cycles-box mepr-meta-sub-pane">
      <?php $checked = (isset($product->limit_cycles) && $product->limit_cycles)?'checked="checked"':''; ?>
      <input type="checkbox" name="<?php echo MeprProduct::$limit_cycles_str; ?>" id="<?php echo MeprProduct::$limit_cycles_str; ?>" <?php echo $checked; ?> /> <label for="_mepr_product_limit_cycles"><?php _e('Limit Payment Cycles', 'memberpress'); ?></label>
      <div class="mepr-product-limit-cycles-hidden mepr-hidden">
        <div class="mepr-sub-option">
          <?php printf( __('<b>Limit to</b> %s <b>payments</b>', 'memberpress'),
                        '<input name="'.MeprProduct::$limit_cycles_num_str.'" id="'.MeprProduct::$limit_cycles_num_str.'" type="text" size="2" value="'.$product->limit_cycles_num.'" />'); ?>
        </div>
        <div class="mepr-sub-option">
          <select name="<?php echo MeprProduct::$limit_cycles_action_str; ?>" id="<?php echo MeprProduct::$limit_cycles_action_str; ?>"> 
            <option value="expire" <?php selected('expire',$product->limit_cycles_action); ?>><?php _e('Expire Access','memberpress'); ?></option>
            <option value="lifetime" <?php selected('lifetime',$product->limit_cycles_action); ?>><?php _e('Lifetime Acess','memberpress'); ?></option>
          </select>
          <strong><?php _e('on last cycle', 'memberpress'); ?></strong>
        </div>
      </div>
    </div>
  </div>

  <!-- The NONCE below prevents post meta from being blanked on move to trash -->
  <input type="hidden" name="<?php echo MeprProduct::$nonce_str; ?>" value="<?php echo wp_create_nonce(MeprProduct::$nonce_str.wp_salt()); ?>" />
</div>
