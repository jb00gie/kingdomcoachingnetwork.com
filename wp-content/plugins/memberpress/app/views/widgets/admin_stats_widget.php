<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<!-- <img src="<?php echo MEPR_IMAGES_URL; ?>/memberpress-36.png" id="mepr-widget-logo" /> -->

<div id="widget-info-blocks">
  <img src="<?php echo MEPR_URL; ?>/images/memberpress-48@2x.png" id="mepr-stats-logo" />
  <p><?php _e('Your 7-Day membership activity:', 'memberpress'); ?></span></p>
  
  <div class="widget_info_block">
    <span class="info_block_title"><?php _e('Pending Transactions', 'memberpress'); ?></span>
    <h4><?php echo $pending_transactions; ?></h4>
  </div>
  
  <div class="widget_info_block">
    <span class="info_block_title"><?php _e('Failed Transactions', 'memberpress'); ?></span>
    <h4><?php echo $failed_transactions; ?></h4>
  </div>
  
  <div class="widget_info_block">
    <span class="info_block_title"><?php _e('Refunded Transactions', 'memberpress'); ?></span>
    <h4><?php echo $refunded_transactions; ?></h4>
  </div>
  
  <div class="widget_info_block">
    <span class="info_block_title"><?php _e('Completed Transactions', 'memberpress'); ?></span>
    <h4><?php echo $completed_transactions; ?></h4>
  </div>
  
  <div class="widget_info_block">
    <span class="info_block_title"><?php _e('Amount Collected', 'memberpress'); ?></span><br/><br/>
    <h4><?php echo MeprAppHelper::format_currency(($revenue + $refunds), true, false); ?></h4>
  </div>
  
  <div class="widget_info_block">
    <span class="info_block_title"><?php _e('Amount Refunded', 'memberpress'); ?></span><br/><br/>
    <h4><?php echo MeprAppHelper::format_currency($refunds, true, false); ?></h4>
  </div>
  
  <div class="widget_info_block">
    <span class="info_block_title"><?php _e('Total Income', 'memberpress'); ?></span><br/><br/>
    <h4><?php echo MeprAppHelper::format_currency($revenue, true, false); ?></h4>
  </div>
</div>

<div style="clear:both;height:10px;"></div>

<div id="mepr-widget-report">
  <img src="<?php echo admin_url('images/wpspin_light-2x.gif'); ?>" width="16px" height="16px" />
</div>

<div class="alignright">
  <a href="<?php echo admin_url( "admin-ajax.php?action=mepr_export_report&export=widget" ); ?>"><?php _e('Export as CSV', 'memberpress'); ?></a>
</div>

<div>
  <a href="<?php echo admin_url('admin.php?page=memberpress-reports'); ?>" class="button"><?php _e('View More MemberPress Reports', 'memberpress'); ?></a>
</div>

<!-- Widget JS Helpers -->
<div id="mepr-widget-loadurl" data-value="<?php echo home_url('/index.php?plugin=mepr&controller=reports&action=', MeprUtils::protocol()); ?>"></div>
<div id="mepr-widget-currency-symbol" data-value="<?php echo $mepr_options->currency_symbol; ?>"></div>
