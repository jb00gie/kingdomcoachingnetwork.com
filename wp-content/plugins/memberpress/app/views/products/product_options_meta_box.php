<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<h2 class="nav-tab-wrapper">
  <a class="nav-tab main-nav-tab nav-tab-active" href="#" id="registration"><?php _e('Registration', 'memberpress'); ?></a>
  <a class="nav-tab main-nav-tab" href="#" id="who-can-purchase"><?php _e('Permissions', 'memberpress'); ?></a>
  <a class="nav-tab main-nav-tab" href="#" id="group-layout"><?php _e('Price Box', 'memberpress'); ?></a>
  <a class="nav-tab main-nav-tab" href="#" id="advanced"><?php _e('Advanced', 'memberpress'); ?></a>
  <?php do_action('mepr-product-options-tabs', $product); ?>
</h2>

<div id="product_options_wrapper">
  <div class="product_options_page registration">
    <?php require(MEPR_VIEWS_PATH.'/products/registration.php'); ?>
  </div>
  <div class="product_options_page who-can-purchase">
    <?php require(MEPR_VIEWS_PATH.'/products/permissions.php'); ?>
  </div>
  <div class="product_options_page group-layout">
    <?php require(MEPR_VIEWS_PATH.'/products/price_box.php'); ?>
  </div>
  <div class="product_options_page advanced">
    <?php require(MEPR_VIEWS_PATH.'/products/advanced.php'); ?>
  </div>
  <?php do_action('mepr-product-options-pages', $product); ?>
</div>
