<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>
<div id="mepr-integration-<?php echo $obj->id; ?>" class="mepr-integration">
  <div class="mepr-integration-delete">
    <a href=""><i class="mp-icon mp-icon-cancel-circled mp-16"></i></a>
  </div>
  <div class="mp-row">
    <div class="mp-col-3">
      <div class="mepr-integration-setup-form">
        <input type="hidden" value="<?php echo $obj->id; ?>" name="<?php echo "{$mepr_options->integrations_str}[{$obj->id}][id]"; ?>" />
        <input type="hidden" value="1" name="<?php echo "{$mepr_options->integrations_str}[{$obj->id}][saved]"; ?>" />
        <div class="mp-row">
          <strong><?php _e('Gateway:', 'memberpress'); ?></strong>
          <?php
          if(isset($obj->settings->gateway) and isset($obj->settings->saved) and $obj->settings->saved): ?>
            <input type="hidden" value="<?php echo $obj->settings->gateway; ?>" name="<?php echo "{$mepr_options->integrations_str}[{$obj->id}][gateway]"; ?>" />
            <span><?php echo $obj->name; ?></span>
          <?php
          else:
            MeprOptionsHelper::gateways_dropdown("{$mepr_options->integrations_str}[{$obj->id}][gateway]", isset($obj->settings->gateway)?$obj->settings->gateway:'', $obj->id);
          endif; ?>
        </div>
        <div class="mp-row">
          <strong><?php _e('Label:', 'memberpress'); ?></strong>
          <input type="text" id="<?php echo "{$mepr_options->integrations_str}-{$obj->id}-label"; ?>" name="<?php echo "{$mepr_options->integrations_str}[{$obj->id}][label]"; ?>" value="<?php echo $obj->label; ?>" />
        </div>
        <div class="mp-row">
          <strong><?php _e('ID:', 'memberpress'); ?></strong>
          <?php echo $obj->id; ?>
        </div>
      </div>
    </div>
    <div class="mp-col-7">
      <div class="mepr-integration-gateway-form">
        <?php
          if($obj instanceof MeprBaseRealGateway)
            $obj->display_options_form();
        ?>
      </div>
    </div>
  </div>
</div>

