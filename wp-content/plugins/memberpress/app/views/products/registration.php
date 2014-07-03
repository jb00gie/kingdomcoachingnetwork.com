<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<div class="product-options-panel">
  <div id="mepr-product-registration-button-text">
    <span><?php _e('Registration Button Text:', 'memberpress'); ?></span>
    <input name="<?php echo MeprProduct::$signup_button_text_str; ?>" id="<?php echo MeprProduct::$signup_button_text_str; ?>" type="text" value="<?php echo $product->signup_button_text; ?>" />
  </div>
  <div id="mepr-product-thank-you-message" class="mepr-product-adv-item">
    <input type="checkbox" name="<?php echo MeprProduct::$thank_you_page_enabled_str; ?>" id="<?php echo MeprProduct::$thank_you_page_enabled_str; ?>" <?php checked($product->thank_you_page_enabled); ?> />
    <label for="<?php echo MeprProduct::$thank_you_page_enabled_str; ?>"><?php _e('Enable custom thank you page message', 'memberpress'); ?></label>
    <?php
      MeprAppHelper::info_tooltip('mepr-product-custom-thank-you-page',
                                  __('Enable Custom Thank You Page Message', 'memberpress'),
                                  __('Enabling this option will reveal a new Compose form which you can use to provide a custom message to show on the Thank You page after a member purchases this product.', 'memberpress'));
    ?>

    <div id="mepr-product-thank-you-area">
      <?php wp_editor(stripslashes($product->thank_you_message), 'meprproductthankyoumessage'); ?>
    </div>
  </div>

  <div id="mepr-product-welcome-email">
    <?php MeprAppHelper::display_emails('MeprBaseProductEmail',array(array('product_id'=>$product->ID))); ?>
  </div>

  <div id="mepr-product-payment-methods-wrap">
    <input type="checkbox" name="<?php echo MeprProduct::$customize_payment_methods_str; ?>" id="<?php echo MeprProduct::$customize_payment_methods_str; ?>" <?php checked($product->customize_payment_methods); ?> />
    <label for="<?php echo MeprProduct::$customize_payment_methods_str; ?>"><?php _e('Customize Payment Methods', 'memberpress'); ?></label>
    <?php
      MeprAppHelper::info_tooltip( 'mepr-product-customize-payment-methods',
                                   __('Customize Payment Methods', 'memberpress'),
                                   __('Enabling this option will reveal a drag and drop list of the available payment methods. You can use this to re-order or even hide payment methods from the dropdown on this product registration page.', 'memberpress') );
    ?>
    <div id="mepr-product-payment-methods" class="mepr-options-pane mepr_hidden">
      <h4><?php _e('Active Payment Methods', 'memberpress'); ?></h4>
      <?php
        $mepr_options = MeprOptions::fetch();
        $pms = $mepr_options->payment_methods();
    
        unset($pms['free']);
        unset($pms['manual']);
    
        if(empty($pms)) {
          ?>
            <div><?php _e('No Payment Methods were found. Please go to the options page to configure some.','memberpress'); ?></div>
          <?php
        }
        else {
          $pmkeys = array_keys($pms);
          $active_pms = $product->payment_methods();
          $inactive_pms = array_diff( $pmkeys, $active_pms );
          ?>
          <ul id="mepr-product-active-payment-methods" class="mepr-sortable">
            <?php
            foreach( $active_pms as $active_pm ) {
              $pm = $pms[$active_pm];
              ?>
                <li><input type="checkbox" data-id="<?php echo $active_pm; ?>" checked="checked" /> <?php echo "{$pm->label} ({$pm->name})"; ?></li>
              <?php
            }
            ?>
          </ul>
            
          <h4 id="mepr-product-inactive-payment-methods-title" class="mepr_hidden"><?php _e('Inactive Payment Methods', 'memberpress'); ?></h4>
          <ul id="mepr-product-inactive-payment-methods" class="mepr_hidden">
            <?php
            foreach( $inactive_pms as $inactive_pm ) {
              $pm = $pms[$inactive_pm];
              ?>
                <li><input type="checkbox" data-id="<?php echo $inactive_pm; ?>" /> <?php echo "{$pm->label} ({$pm->name})"; ?></li>
              <?php
            }
            ?>
          </ul>
          <?php
        }
      ?>
      <textarea name="mepr-product-payment-methods-json" id="mepr-product-payment-methods-json" class="mepr_hidden"><?php echo json_encode($active_pms); ?></textarea>
    </div>
  </div>

  <div id="mepr-product-manually-place-form">
    <?php //Manually place the registration form on the page ?>
    <a href="#" data-target="#mepr-product-shortcodes" class="mepr-slide-toggle"><?php _e('Product Shortcodes', 'memberpress'); ?></a><br/><br/>
    <div id="mepr-product-shortcodes" class="mepr-radius-border mepr-hidden">
      <p class="description"><?php _e('You can use this shortcode anywhere on your site to quickly display a link to this product page. If the text inbetween the shortcode is not present, MemberPress will use the product title as the link text instead.', 'memberpress'); ?></p>
      [mepr-product-link id="<?php echo $product->ID; ?>"] <?php _e('Optional link label here...', 'memberpress'); ?> [/mepr-product-link]<br/><br/>
      <p class="description"><?php _e('Shortcode to be used on this product page to manually place the registration form.', 'memberpress'); ?></p>
      [mepr-product-registration-form]<br/><br/>
      <p class="description"><?php _e('Shortcode which can be used on any other WordPress page, post or custom post type to manually place the registration form for this product.', 'memberpress'); ?></p>
      [mepr-product-registration-form product_id="<?php echo $product->ID; ?>"]
    </div>
  </div>

  <?php do_action('mepr-product-registration-metabox', $product); ?>
</div>

