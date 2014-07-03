<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<?php $products = $group->products(); ?>
<div class="mepr-price-menu">
  <div class="mepr-price-boxes mepr-<?php echo count($products); ?>-col">
  <?php
    if(!empty($products))
      foreach($products as $product)
          MeprGroupsHelper::group_page_item($product, $group);
  ?>
  </div>
</div>
