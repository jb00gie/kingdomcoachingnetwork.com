<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}
$member_login = (isset($_GET['member']))?__('for', 'memberpress').' '.urldecode($_GET['member']):'';
?>
<div class="wrap">
  <div class="icon32"></div>
  <h2><?php _e('Transactions', 'memberpress'); ?> <?php echo $member_login; ?> <a href="<?php echo admin_url('admin.php?page=memberpress-trans&action=new'); ?>" class="add-new-h2"><?php _e('Add New', 'memberpress'); ?></a></h2>
  <input type="hidden" name="mepr-update-transactions" value="Y" />
  <?php $list_table->display(); ?>
</div>
