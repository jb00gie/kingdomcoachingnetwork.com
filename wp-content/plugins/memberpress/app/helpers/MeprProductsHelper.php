<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprProductsHelper
{
  public static function period_type_dropdown($id)
  {
    ?>
      <select id="<?php echo $id; ?>-custom"
              class="mepr-dropdown mepr-period-type-dropdown"
              data-period-type-id="<?php echo $id; ?>">
        <option value="months"><?php _e('months', 'memberpress'); ?>&nbsp;</option>
        <option value="weeks"><?php _e('weeks', 'memberpress'); ?>&nbsp;</option>
      </select>
    <?php
  }

  public static function preset_period_dropdown($period_str, $period_type_str)
  {
    ?>
    <select id="<?php echo $period_type_str; ?>-presets"
            data-period-id="<?php echo $period_str; ?>"
            data-period-type-id="<?php echo $period_type_str; ?>">
      <option value="monthly"><?php _e('Monthly', 'memberpress'); ?>&nbsp;</option>
      <option value="yearly"><?php _e('Yearly', 'memberpress'); ?>&nbsp;</option>
      <option value="weekly"><?php _e('Weekly', 'memberpress'); ?>&nbsp;</option>
      <option value="quarterly"><?php _e('Every 3 Months', 'memberpress'); ?>&nbsp;</option>
      <option value="semi-annually"><?php _e('Every 6 Months', 'memberpress'); ?>&nbsp;</option>
      <option value="custom"><?php _e('Custom', 'memberpress'); ?>&nbsp;</option>
    </select>
    <?php
  }

  public static function generate_pricing_benefits_list($benefits)
  {
    if(!empty($benefits))
      foreach($benefits as $b)
      {
      ?>
        <li class="benefit-item">
          <input type="text" name="<?php echo MeprProduct::$pricing_benefits_str; ?>[]" class="benefit-input" value="<?php echo stripslashes(htmlspecialchars($b, ENT_QUOTES)); ?>" />
          <span class="remove-span">
            <a href="" class="remove-benefit-item" title="<?php _e('Remove Benefit', 'memberpress'); ?>"><i class="mp-icon mp-icon-cancel-circled mp-16"></i></a>
          </span>
        </li>
      <?php
      }
    else
    {
    ?>
        <li class="benefit-item">
          <input type="text" name="<?php echo MeprProduct::$pricing_benefits_str; ?>[]" class="benefit-input" value="" />
          <span class="remove-span">
            <a href="" class="remove-benefit-item" title="<?php _e('Remove Benefit', 'memberpress'); ?>"><i class="mp-icon mp-icon-cancel-circled mp-16"></i></a>
          </span>
        </li>
    <?php
    }
  }
  
  public static function show_pricing_benefits_add_new()
  {
  ?>
    <a href="" class="add-new-benefit" title="<?php _e('Add Benefit', 'memberpress'); ?>"><i class="mp-icon mp-icon-plus-circled mp-24"></i></a>
  <?php
  }
  
  /** Especially for formatting a product's price */
  public static function format_currency($product, $show_symbol = true, $coupon_code = null) {
    return MeprAppHelper::format_price_string( $product,
                                               $product->adjusted_price($coupon_code),
                                               $show_symbol,
                                               $coupon_code );
  }
  
  public static function get_who_can_purchase_items($product)
  {
    $id = 1;
    ?>
      <?php if(!empty($product->who_can_purchase)): ?>
        <?php foreach($product->who_can_purchase as $who): ?>
        <?php if($who->user_type == 'members') {$class = '';} else {$class = 'who_have_purchased';} ?>
          <li>
            <?php self::get_user_types_dropdown($who->user_type, $id); ?>
            <span id="who_have_purchased-<?php echo $id; ?>" class="<?php echo $class; ?>">
              <?php _e('who have purchased', 'memberpress'); ?>
              <?php self::get_products_dropdown($who->product_id, $product->ID); ?>
            </span>
            <span class="remove-span">
              <a href="" class="remove-who-can-purchase-rule" title="Remove Rule"><i class="mp-icon mp-icon-cancel-circled mp-16"></i></a>
            </span>
          </li>
        <?php $id++; endforeach; ?>
      <?php else: ?>
        <?php self::get_blank_who_can_purchase_row($product); ?>
      <?php endif; ?>
    <?php
  }
  
  public static function get_blank_who_can_purchase_row($product)
  {
    $id = 1;
    ?>
      <li>
        <?php self::get_user_types_dropdown(null, $id); ?>
        <span id="who_have_purchased-<?php echo $id; ?>" class="who_have_purchased">
          <?php _e('who have purchased', 'memberpress'); ?>
          <?php self::get_products_dropdown(null, $product->ID); ?>
        </span>
        <span class="remove-span">
          <a href="" class="remove-who-can-purchase-rule" title="Remove Rule"><i class="mp-icon mp-icon-cancel-circled mp-16"></i></a>
        </span>
      </li>
    <?php
  }
  
  public static function get_user_types_dropdown($chosen = null, $id)
  {
    ?>
      <select name="<?php echo MeprProduct::$who_can_purchase_str.'-user_type'; ?>[]" class="user_types_dropdown" data-value="<?php echo $id; ?>">
        <option value="everyone" <?php selected('everyone', $chosen); ?>><?php _e('Everyone', 'memberpress'); ?></option>
        <option value="guests" <?php selected('guests', $chosen); ?>><?php _e('Guests', 'memberpress'); ?></option>
        <option value="members" <?php selected('members', $chosen); ?>><?php _e('Members', 'memberpress'); ?></option>
      </select>
    <?php
  }

  public static function get_products_dropdown($chosen = null, $my_ID = null)
  {
    $products = get_posts(array('numberposts' => -1, 'post_type' => MeprProduct::$cpt, 'post_status' => 'publish'));
    
    ?>
      <select name="<?php echo MeprProduct::$who_can_purchase_str.'-product_id'; ?>[]">
        <option value="nothing" <?php selected($chosen, 'nothing'); ?>><?php _e('nothing', 'memberpress'); ?></option>
        <option value="anything" <?php selected($chosen, 'anything'); ?>><?php _e('anything', 'memberpress'); ?></option>
        <?php foreach($products as $p): ?>
          <?php if($p->ID != $my_ID): ?>
            <option value="<?php echo $p->ID; ?>" <?php selected($p->ID, $chosen) ?>><?php echo $p->post_title; ?></option>
          <?php endif; ?>
        <?php endforeach; ?>
      </select>
    <?php
  }
  
  public static function generate_product_link_html($product, $content)
  {
    $permalink = get_permalink($product->ID);
    $title = ($content == '')?$product->post_title:$content;
    
    ob_start();
    ?>
      <a href="<?php echo $permalink; ?>" class="mepr_product_link mepr-product-link-<?php echo $product->ID; ?>"><?php echo $title; ?></a>
    <?php
    
    return ob_get_clean();
  }
} //End class
