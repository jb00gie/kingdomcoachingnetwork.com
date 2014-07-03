<h2 class="nav-tab-wrapper">
  <a class="nav-tab main-nav-tab <?php if(!$this->lifetime) { echo 'nav-tab-active'; } ?>" href="<?php echo admin_url('admin.php?page=memberpress-subscriptions'.$member.$search.$perpage); ?>" id="mepr-subscriptions"><?php printf(__('Recurring (%d)','memberpress'), $this->periodic_count); ?></a>
  <a class="nav-tab main-nav-tab <?php if($this->lifetime) { echo 'nav-tab-active'; } ?>" href="<?php echo admin_url('admin.php?page=memberpress-subscriptions&lifetime=1'.$member.$search.$perpage); ?>" id="mepr-lifetime-subscriptions"><?php printf(__('Non-Recurring (%d)','memberpress'), $this->lifetime_count); ?></a>
</h2>
<div>&nbsp;</div>
