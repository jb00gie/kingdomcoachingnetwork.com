<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<form name="mepr_registerform" class="mepr-signup-form" id="mepr_registerform" action="<?php echo $product->url(); ?>" method="post">
  <input type="hidden" id="mepr_process_signup_form" name="mepr_process_signup_form" value="Y" />
  <input type="hidden" id="mepr_product_id" name="mepr_product_id" value="<?php echo $product->ID; ?>" />
  <div class="mepr_signup_table">
    <?php if( $product->register_price_action != 'hidden' ): ?>
      <div class="mepr_signup_table_row mepr_bold mepr_price">
        <label><?php _e('Price:', 'memberpress'); ?></label>
        <div class="mepr_price_cell">
        <?php
          if( $product->register_price_action == 'custom' ) {
            echo $product->register_price;
          }
          else if($product->is_one_time_payment()) {
            if(empty($mepr_coupon_code)) //We've already validated the coupon before including signup_form.php
              echo MeprProductsHelper::format_currency($product);
            else
              echo MeprProductsHelper::format_currency($product, true, $mepr_coupon_code);
          }
          else {
            global $current_user;
            MeprUtils::get_currentuserinfo();

            // Setup to possibly do a proration without actually creating a subscription record
            $tmp_sub = new MeprSubscription();
            $tmp_sub->ID = 0;
            $tmp_sub->user_id = $current_user->ID;
            $tmp_sub->load_product_vars($product, $mepr_coupon_code);
            $tmp_sub->maybe_prorate();

            echo MeprAppHelper::format_price_string($tmp_sub, $tmp_sub->price, true, $mepr_coupon_code);
          }
        ?>
        </div>
      </div>
    <?php endif; ?>
    <?php if($mepr_options->show_fname_lname): ?>
      <div class="mepr_signup_table_row mepr_first_name">
        <label><?php _e('First Name:', 'memberpress'); echo ($mepr_options->require_fname_lname)?'*':''; ?></label>
        <input type="text" name="user_first_name" id="user_first_name" class="mepr-form-input" value="<?php echo (isset($user_first_name))?esc_attr(stripslashes($user_first_name)):''; ?>" />
      </div>
      <div class="mepr_signup_table_row mepr_last_name">
        <label><?php _e('Last Name:', 'memberpress'); echo ($mepr_options->require_fname_lname)?'*':''; ?></label>
        <input type="text" name="user_last_name" id="user_last_name" class="mepr-form-input" value="<?php echo (isset($user_last_name))?esc_attr(stripslashes($user_last_name)):''; ?>" />
      </div>
    <?php else: /* this is here to avoid validation issues */ ?>
      <input type="hidden" name="user_first_name" id="user_first_name" value="" />
      <input type="hidden" name="user_last_name" id="user_last_name" value="" />
    <?php endif; ?>
    
    <?php
      $custom_fields = $mepr_options->custom_fields;
      
      //Maybe show the address fields too
      if($mepr_options->show_address_fields)
        $custom_fields = array_merge($mepr_options->address_fields, $custom_fields); //Genius
      
      foreach($custom_fields as $line)
      {
        $required = ($line->required)?'*':'';
        
        if(!$line->show_on_signup)
          continue;
        
        ?>
          <div class="mepr_signup_table_row mepr_<?php echo $line->field_key; ?>">
            <?php if($line->field_type != 'checkbox'): ?>
              <label for="<?php echo $line->field_key; ?>"><?php echo stripslashes($line->field_name).':'.$required; ?></label>
            <?php endif; ?>
            
            <?php
              switch($line->field_type)
              {
                case 'text':
                  $value = (isset($_POST[$line->field_key]) && !empty($_POST[$line->field_key]))?stripslashes($_POST[$line->field_key]):stripslashes($line->default_value);
                  echo '<input type="text" name="'.$line->field_key.'" id="'.$line->field_key.'" class="mepr-form-input" value="'.esc_attr(stripslashes($value)).'" />';
                  break;
                case 'textarea':
                  $value = (isset($_POST[$line->field_key]) && !empty($_POST[$line->field_key]))?stripslashes($_POST[$line->field_key]):stripslashes($line->default_value);
                  echo '<textarea name="'.$line->field_key.'" id="'.$line->field_key.'" class="mepr-form-textarea">'.esc_textarea(stripslashes($value)).'</textarea>';
                  break;
                case 'checkbox':
                  $checked = (isset($_POST[$line->field_key]))?true:(!isset($_POST['wp-submit']) && $line->default_value == 'checked');
                  echo '<input type="checkbox" name="'.$line->field_key.'" id="'.$line->field_key.'" class="mepr-form-checkbox" '.checked($checked, true, false).'/> <label for="'.$line->field_key.'" class="mepr-form-checkbox-label">'.stripslashes($line->field_name).$required.'</label>';
                  break;
                case 'date':
                  $value = (isset($_POST[$line->field_key]) && !empty($_POST[$line->field_key]))?stripslashes($_POST[$line->field_key]):stripslashes($line->default_value);
                  echo '<input type="text" name="'.$line->field_key.'" id="'.$line->field_key.'" value="'.esc_attr(stripslashes($value)).'" class="mepr-date-picker mepr-form-input" />';
                  break;
                case 'dropdown':
                  $value = (isset($_POST[$line->field_key]))?$_POST[$line->field_key]:$line->default_value;
                  ?>
                    <select name="<?php echo $line->field_key; ?>" id="<?php echo $line->field_key; ?>">
                  <?php
                    foreach($line->options as $o)
                    {
                      ?>
                        <option value="<?php echo $o->option_value; ?>" <?php selected($o->option_value, esc_attr($value)); ?>><?php echo stripslashes($o->option_name); ?></option>
                      <?php
                    }
                  ?>
                    </select>
                  <?php
                  break;
              }
            ?>
          </div>
        <?php
      }
    ?>
    <?php if(!$mepr_options->username_is_email): ?>
      <div class="mepr_signup_table_row mepr_username">
        <label><?php _e('Username:', 'memberpress'); ?>*</label>
        <input type="text" name="user_login" id="user_login" class="mepr-form-input" value="<?php echo (isset($user_login))?esc_attr(stripslashes($user_login)):''; ?>" />
      </div>
    <?php endif; ?>
    <div class="mepr_signup_table_row mepr_email">
      <label><?php _e('E-mail:', 'memberpress'); ?>*</label>
      <input type="text" name="user_email" id="user_email" class="mepr-form-input" value="<?php echo (isset($user_email))?esc_attr(stripslashes($user_email)):''; ?>" />
    </div>
    <div class="mepr_signup_table_row">
      <label><?php _e('Password:*', 'memberpress'); ?></label>
      <input type="password" name="mepr_user_password" id="mepr_user_password" class="mepr-form-input" value="<?php echo (isset($mepr_user_password))?esc_attr(stripslashes($mepr_user_password)):''; ?>" />
    </div>
    <div class="mepr_signup_table_row">
      <label><?php _e('Password Confirmation:*', 'memberpress'); ?></label>
      <input type="password" name="mepr_user_password_confirm" id="mepr_user_password_confirm" class="mepr-form-input" value="<?php echo (isset($mepr_user_password_confirm))?esc_attr(stripslashes($mepr_user_password_confirm)):''; ?>" />
    </div>
    <?php if($product->adjusted_price() > 0.00): ?>
      <?php if($mepr_options->coupon_field_enabled): ?>
        <div class="mepr_signup_table_row mepr_coupon">
          <label><?php _e('Coupon Code:', 'memberpress'); ?></label>
          <input type="text" name="mepr_coupon_code" id="mepr_coupon_code" class="mepr-form-input" value="<?php echo (isset($mepr_coupon_code))?esc_attr(stripslashes($mepr_coupon_code)):''; ?>" />
        </div>
      <?php else: ?>
        <input type="hidden" id="mepr_coupon_code" name="mepr_coupon_code" value="<?php echo (isset($mepr_coupon_code))?esc_attr(stripslashes($mepr_coupon_code)):''; ?>" />
      <?php endif; ?>
      <?php $active_pms = $product->payment_methods(); ?>
      <?php $pms = $product->payment_methods(); ?>
      <?php if(count($active_pms) > 1): ?>
        <div class="mepr_signup_table_row mepr_payment_method">
          <label><?php _e('Payment Method:', 'memberpress'); ?></label>
          <?php echo MeprOptionsHelper::payment_methods_dropdown('mepr_payment_method', $active_pms); ?>
        </div>
      <?php elseif($pm = $mepr_options->payment_method(array_shift($active_pms))): ?>
        <input type="hidden" id="mepr_payment_method" name="mepr_payment_method" value="<?php echo esc_attr(stripslashes($pm->id)); ?>" />
      <?php endif; ?>
    <?php endif; ?>
    <?php if($mepr_options->require_tos): ?>
      <div class="mepr_tos">
        <input type="checkbox" id="mepr_agree_to_tos" name="mepr_agree_to_tos" class="mepr-form-checkbox" <?php checked((isset($mepr_agree_to_tos))); ?> />
        <label class="mepr-form-checkbox-label"><a href="<?php echo stripslashes($mepr_options->tos_url); ?>" target="_blank"><?php echo stripslashes($mepr_options->tos_title); ?></a>*</label>
      </div>
    <?php endif; ?>
    
    <?php // This thing needs to be hidden in order for this to work so we do it explicitly as a style ?>
    <input type="text" id="mepr_no_val" name="mepr_no_val" class="mepr-form-input" />
    
    <?php do_action('mepr-user-signup-fields'); ?>
  </div>
  
  <br class="clear" />
  <input type="submit" name="wp-submit" id="wp-submit" class="submit-button mepr_front_button" value="<?php echo stripslashes($product->signup_button_text); ?>" />&nbsp;<img src="<?php echo admin_url('images/loading.gif'); ?>" style="display: none;" class="mepr-loading-gif" />
</form>

<p><small><?php _e('* Required field', 'memberpress'); ?></small></p>
