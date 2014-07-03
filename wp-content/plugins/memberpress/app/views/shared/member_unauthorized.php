<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<?php $mepr_options = MeprOptions::fetch(); ?>
<p><?php printf(__('You\'re unauthorized to view this page. Check or renew your %1$s and try again.', 'memberpress'), "<a href=\"" . $mepr_options->account_page_url('action=subscriptions') . "\">" . __('subscription(s)', 'memberpress') . "</a>"); ?></p>
