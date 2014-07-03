<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<div class="wrap">
  <div class="icon32"></div>
  <h2><?php _e('Options', 'memberpress'); ?><a href="http://www.memberpress.com/user-manual/" class="add-new-h2" target="_blank"><?php _e('User Manual', 'memberpress'); ?></a></h2>
  
  <form name="mepr_options_form" id="mepr_options_form" method="post" action="">
    <input type="hidden" name="action" value="process-form">
    <?php wp_nonce_field('update-options'); ?>
    
    <h2 id="mepr-reports-column-selector" class="nav-tab-wrapper">
      <a class="nav-tab nav-tab-active" id="pages" href="#"><?php _e('Pages', 'memberpress'); ?></a>
      <a class="nav-tab" id="accounts" href="#"><?php _e('Account', 'memberpress'); ?></a>
      <a class="nav-tab" id="fields" href="#"><?php _e('Fields', 'memberpress'); ?></a>
      <a class="nav-tab" id="integration" href="#"><?php _e('Payments', 'memberpress'); ?></a>
      <a class="nav-tab" id="emails" href="#"><?php _e('Emails', 'memberpress'); ?></a>
      <a class="nav-tab" id="marketing" href="#"><?php _e('Marketing', 'memberpress'); ?></a>
      <a class="nav-tab" id="general" href="#"><?php _e('General', 'memberpress'); ?></a>
      <?php do_action('mepr_display_options_tabs'); ?>
    </h2>
    
    <div id="pages" class="mepr-options-hidden-pane">
      <h3><?php _e('MemberPress Reserved Pages', 'memberpress'); ?></h3>
      <table class="mepr-options-pane">
        <tr>
          <td><?php _e('MemberPress Thank You Page', 'memberpress'); ?>*:</td>
          <td><?php MeprOptionsHelper::wp_pages_dropdown($mepr_options->thankyou_page_id_str, $mepr_options->thankyou_page_id, __('Thank You', 'memberpress')); ?></td>
        </tr>
        <tr>
          <td><?php _e('MemberPress Account Page', 'memberpress'); ?>*:</td>
          <td><?php MeprOptionsHelper::wp_pages_dropdown($mepr_options->account_page_id_str, $mepr_options->account_page_id, __('Account', 'memberpress')); ?></td>
        </tr>
        <tr>
          <td><?php _e('MemberPress Login Page', 'memberpress'); ?>*:</td>
          <td><?php MeprOptionsHelper::wp_pages_dropdown($mepr_options->login_page_id_str, $mepr_options->login_page_id, __('Login', 'memberpress')); ?></td>
        </tr>
      </table>

      <h3 class="mepr-field-label">
        <?php _e('Group and Product Pages Slugs:', 'memberpress'); ?>
        <?php MeprAppHelper::info_tooltip( 'mepr-group-and-product-pages-slugs',
                                           __('Group and Product Pages Slugs', 'memberpress'),
                                           __('Use these fields to customize the base slug of urls for your groups and products.', 'memberpress') . "<br/><br/>" .
                                           __('Note: It isn\'t recommended that you change these values if you already have existing groups and product pages on a production membership site because all your urls for them will change (WordPress will attempt to redirect from old urls to new urls).', 'memberpress') ); ?>
      </h3>
      <table class="mepr-options-pane">
        <tbody>
          <tr valign="top">
            <td><label for="<?php echo $mepr_options->group_pages_slug_str; ?>"><?php _e("Group Pages Slug:", 'memberpress'); ?></td>
            <td>
              <input type="text" id="<?php echo $mepr_options->group_pages_slug_str; ?>" name="<?php echo $mepr_options->group_pages_slug_str; ?>" class="regular-text" value="<?php echo stripslashes($mepr_options->group_pages_slug); ?>" />
            </td>
          </tr>
          <tr valign="top">
            <td><label for="<?php echo $mepr_options->product_pages_slug_str; ?>"><?php _e("Product Pages Slug:", 'memberpress'); ?></td>
            <td>
              <input type="text" id="<?php echo $mepr_options->product_pages_slug_str; ?>" name="<?php echo $mepr_options->product_pages_slug_str; ?>" class="regular-text" value="<?php echo stripslashes($mepr_options->product_pages_slug); ?>" />
            </td>
          </tr>
        </tbody>
      </table>

      <h3 class="mepr-field-label"><?php _e('Unauthorized Access', 'memberpress'); ?></h3>
      <div class="mepr-options-pane">
        <input type="checkbox" name="<?php echo $mepr_options->redirect_on_unauthorized_str; ?>" id="<?php echo $mepr_options->redirect_on_unauthorized_str; ?>" <?php checked($mepr_options->redirect_on_unauthorized); ?> />
        <label for="<?php echo $mepr_options->redirect_on_unauthorized_str; ?>"><?php _e('Redirect unauthorized visitors to a specific URL', 'memberpress'); ?></label>
        <?php MeprAppHelper::info_tooltip( 'mepr-unauthorized-handling',
                                           __('Redirect Unauthorized Access', 'memberpress'),
                                           __("MemberPress allows you to handle unauthorized access by replacing the content on page or via a redirection to a specific url.<br/><br/>When this is checked, unauthorized visits will be redirected to a url otherwise the unauthorized message will appear on page.", 'memberpress') ); ?>

        <div id="mepr-unauthorized-redirect" class="mepr_hidden mepr-options-sub-pane">
          <input type="checkbox" name="<?php echo $mepr_options->redirect_non_singular_str; ?>" id="<?php echo $mepr_options->redirect_non_singular_str; ?>" <?php checked($mepr_options->redirect_non_singular); ?> />
          <label for="<?php echo $mepr_options->redirect_non_singular; ?>"><?php _e('Redirect non-singular views:', 'memberpress'); ?></label>
          <?php MeprAppHelper::info_tooltip( 'mepr-unauthorized-redirect-non-singular',
                                             __('Redirect Non-Singular Views', 'memberpress'),
                                             __('If any post in a non-singular view (EX: Blog page, category pages, archive pages etc) is protected, then do not allow the unauthorized members to see this non-singular view at all.', 'memberpress') ); ?>
          <br/><br/>
          <label for="<?php echo $mepr_options->unauthorized_redirect_url_str; ?>"><?php _e('URL to direct unauthorized visitors to:', 'memberpress'); ?></label>
          <?php MeprAppHelper::info_tooltip( 'mepr-unauthorized-redirect-url',
                                             __('Unauthorized Redirection URL', 'memberpress'),
                                             __('This is the URL that visitors will be redirected to when trying to access unauthorized content.', 'memberpress') ); ?>
          <input type="text" id="<?php echo $mepr_options->unauthorized_redirect_url_str; ?>" name="<?php echo $mepr_options->unauthorized_redirect_url_str; ?>" class="regular-text" value="<?php echo stripslashes($mepr_options->unauthorized_redirect_url); ?>" />
          <br/>
          <span class="description"><?php _e('You can use the <strong>[mepr-unauthorized-message]</strong> shortcode on this unauthorized page (assuming this url points to a page on this site).', 'memberpress'); ?></span>
        </div>
        <div>&nbsp;</div>
        <div id="mepr-unauthorized-show-excerpts">
          <input type="checkbox" name="<?php echo $mepr_options->unauth_show_excerpts_str; ?>" id="<?php echo $mepr_options->unauth_show_excerpts_str; ?>" <?php checked($mepr_options->unauth_show_excerpts); ?> />
          <label for="<?php echo $mepr_options->unauth_show_excerpts_str; ?>"><?php _e('Show an excerpt to unauthorized visitors', 'memberpress'); ?></label>
        </div>

        <div id="mepr-unauthorized-show-excerpts-type" class="mepr-options-sub-pane mepr-hidden">
          <?php
            MeprOptionsHelper::display_show_excerpts_dropdown( $mepr_options->unauth_excerpt_type_str,
                                                               $mepr_options->unauth_excerpt_type,
                                                               $mepr_options->unauth_excerpt_size_str,
                                                               $mepr_options->unauth_excerpt_size,
                                                               true
                                                             );
          ?>
        </div>

        <div>&nbsp;</div>
        <div id="mepr-unauthorized-show-login">
          <input type="checkbox" name="<?php echo $mepr_options->unauth_show_login_str; ?>" id="<?php echo $mepr_options->unauth_show_login_str; ?>" <?php checked($mepr_options->unauth_show_login); ?> />
          <label for="<?php echo $mepr_options->unauth_show_login_str; ?>"><?php _e('Show a login form on pages containing unauthorized content', 'memberpress'); ?></label>
        </div>

        <br/>
        <div class="mepr-field-label">
          <a href="" class="mp-toggle-unauthorized-message"><?php _e('Default Unauthorized Message:', 'memberpress'); ?></a>
            <?php MeprAppHelper::info_tooltip( 'mepr-default-unauthorized-message',
                                               __('Default Unauthorized Message', 'memberpress'),
                                               __('This is the default message that will show up when a user is not allowed to access the content on a page.', 'memberpress') ); ?>
        </div>
        <div class="mepr-hidden mepr-options-sub-pane mp-unauthorized-message">
          <?php wp_editor($mepr_options->unauthorized_message, $mepr_options->unauthorized_message_str); ?>
        </div>
      </div>
    </div>
    
    <div id="accounts" class="mepr-options-hidden-pane">
      <h3><?php _e('Permissions:', 'memberpress'); ?></h3>
      <div class="mepr-options-pane">
        <div class="mp-row">
          <div class="mp-col-4">
            <label for="<?php echo $mepr_options->disable_wp_admin_bar_str; ?>">
              <input type="checkbox" name="<?php echo $mepr_options->disable_wp_admin_bar_str; ?>" id="<?php echo $mepr_options->disable_wp_admin_bar_str; ?>" <?php checked($mepr_options->disable_wp_admin_bar); ?> />
              <span><?php _e('Disable the WordPress admin bar for members', 'memberpress'); ?></span>
            </label>
          </div>
          <div class="mp-col-5">
            <label for="<?php echo $mepr_options->lock_wp_admin_str; ?>">
              <input type="checkbox" name="<?php echo $mepr_options->lock_wp_admin_str; ?>" id="<?php echo $mepr_options->lock_wp_admin_str; ?>" <?php checked($mepr_options->lock_wp_admin); ?> />
              <span><?php _e('Keep members out of the WordPress Dashboard', 'memberpress'); ?></span>
            </label>
          </div>
        </div>
        <div class="mp-row">
          <div class="mp-col-4">
            <label for="<?php echo $mepr_options->allow_cancel_subs_str; ?>">
              <input type="checkbox" name="<?php echo $mepr_options->allow_cancel_subs_str; ?>" id="<?php echo $mepr_options->allow_cancel_subs_str; ?>" <?php checked($mepr_options->allow_cancel_subs); ?> />
              <span><?php _e('Allow Members to Cancel their own subscriptions', 'memberpress'); ?></span>
            </label>
          </div>
          <div class="mp-col-5">
            <label for="<?php echo $mepr_options->allow_suspend_subs_str; ?>">
              <input type="checkbox" name="<?php echo $mepr_options->allow_suspend_subs_str; ?>" id="<?php echo $mepr_options->allow_suspend_subs_str; ?>" <?php checked($mepr_options->allow_suspend_subs); ?> />
              <span>
                <?php _e('Allow Members to Pause &amp; Resume their own subscriptions', 'memberpress'); ?>
                <?php MeprAppHelper::info_tooltip( 'mepr-suspend-resume',
                                                   __('Pausing &amp; Resuming Subscriptions', 'memberpress'),
                                                   __('This option will only be available if this is enabled and the user purchased their subsciption using PayPal or Stripe.', 'memberpress') ); ?>
              </span>
            </label>
          </div>
        </div>
      </div>
 
      <h3><?php _e('Registration:', 'memberpress'); ?></h3>
      <div class="mepr-options-pane">
        <div class="mp-row">
          <div class="mp-col-4">
            <label for="<?php echo $mepr_options->disable_wp_registration_form_str; ?>">
              <input type="checkbox" name="<?php echo $mepr_options->disable_wp_registration_form_str; ?>" id="<?php echo $mepr_options->disable_wp_registration_form_str; ?>" <?php checked($mepr_options->disable_wp_registration_form); ?> />
              <span><?php _e('Disable the standard WordPress registration form', 'memberpress'); ?></span>
            </label>
          </div>
          <div class="mp-col-5">
            <label for="<?php echo $mepr_options->coupon_field_enabled_str; ?>">
              <input type="checkbox" name="<?php echo $mepr_options->coupon_field_enabled_str; ?>" id="<?php echo $mepr_options->coupon_field_enabled_str; ?>" <?php checked($mepr_options->coupon_field_enabled); ?> />
              <span><?php _e('Enable Coupon Field on product registration forms', 'memberpress'); ?></span>
            </label>
          </div>
        </div>
        <div class="mp-row">
          <div class="mp-col-4">
            <label for="<?php echo $mepr_options->username_is_email_str; ?>">
              <input type="checkbox" name="<?php echo $mepr_options->username_is_email_str; ?>" id="<?php echo $mepr_options->username_is_email_str; ?>" <?php checked($mepr_options->username_is_email); ?> />
              <span><?php _e('Members must use their email address for their Username', 'memberpress'); ?></span>
            </label>
          </div>
          <div class="mp-col-5">
            <label for="<?php echo $mepr_options->pro_rated_upgrades_str; ?>">
              <input type="checkbox" name="<?php echo $mepr_options->pro_rated_upgrades_str; ?>" id="<?php echo $mepr_options->pro_rated_upgrades_str; ?>" <?php checked($mepr_options->pro_rated_upgrades); ?> />
              <span><?php _e('Pro-rate subscription prices when a member upgrades', 'memberpress'); ?></span>
            </label>
          </div>
        </div>
        <div class="mp-row">
          <div class="mp-col-9">
            <label for="<?php echo $mepr_options->require_tos_str; ?>">
              <input type="checkbox" name="<?php echo $mepr_options->require_tos_str; ?>" id="<?php echo $mepr_options->require_tos_str; ?>" <?php checked($mepr_options->require_tos); ?> />
              <span><?php _e('Require Terms of Service on product registration forms', 'memberpress'); ?></span>
            </label>
          </div>
        </div>
        <div id="mepr_tos_hidden" class="mepr-options-sub-pane mepr-hidden">
          <div class="mp-row">
            <div class="mp-col-3">
              <label for="<?php echo $mepr_options->tos_url_str; ?>"><?php _e('URL to your Terms of Service page:', 'memberpress'); ?></label>
            </div>
            <div class="mp-col-4">
              <input type="text" id="<?php echo $mepr_options->tos_url_str; ?>" name="<?php echo $mepr_options->tos_url_str; ?>" class="regular-text" value="<?php echo stripslashes($mepr_options->tos_url); ?>" />
            </div>
          </div>
          <div class="mp-row">
            <div class="mp-col-3">
              <label for="<?php echo $mepr_options->tos_title_str; ?>"><?php _e('Terms of Service Checkbox Title:', 'memberpress'); ?></label>
            </div>
            <div class="mp-col-4">
              <input type="text" id="<?php echo $mepr_options->tos_title_str; ?>" name="<?php echo $mepr_options->tos_title_str; ?>" class="regular-text" value="<?php echo stripslashes($mepr_options->tos_title); ?>" />
            </div>
          </div>
        </div>
      </div>

      <h3><?php _e('Login & Logout:', 'memberpress'); ?></h3>
      <div class="mepr-options-pane">
        <label for="<?php echo $mepr_options->force_login_page_url_str; ?>">
          <input type="checkbox" name="<?php echo $mepr_options->force_login_page_url_str; ?>" id="<?php echo $mepr_options->force_login_page_url_str; ?>" <?php checked($mepr_options->force_login_page_url); ?> />
          <span><?php _e('Force WordPress to use the MemberPress login page', 'memberpress'); ?></span>
        </label>
        <?php MeprAppHelper::info_tooltip( 'mepr-force-login-page-url',
                                           __('Force Login Page URL', 'memberpress'),
                                           __('Use this option to override WordPress links to /wp-login.php and instead use the Login page you have specified for MemberPress. If you have other plugins that use their own Login pages too you may want to leave this option disabled.', 'memberpress') ); ?>
        <br/><br/>
        <label for="<?php echo $mepr_options->login_redirect_url_str; ?>"><?php _e('URL to direct member to after login:', 'memberpress'); ?></label>&nbsp;&nbsp;&nbsp;
        <?php MeprAppHelper::info_tooltip( 'mepr-login-redirect-message',
                                           __('Login Redirect URL', 'memberpress'),
                                           __('For this to work you must have the Login page set in the MemberPress options. You can also override this option on a per-product basis in the Advanced box when creating/editing a Product.', 'memberpress') ); ?>
        <input type="text" id="<?php echo $mepr_options->login_redirect_url_str; ?>" name="<?php echo $mepr_options->login_redirect_url_str; ?>" class="regular-text" value="<?php echo stripslashes($mepr_options->login_redirect_url); ?>" /><br/><br/>
        <label for="<?php echo $mepr_options->logout_redirect_url_str; ?>"><?php _e('URL to direct member to after logout:', 'memberpress'); ?></label>
        <?php MeprAppHelper::info_tooltip( 'mepr-logout-redirect-message',
                                           __('Logout Redirect URL', 'memberpress'),
                                           __('Set what URL you want the member to be taken to when they logout on your site. This setting applies to Administrators as well.', 'memberpress') ); ?>
        <input type="text" id="<?php echo $mepr_options->logout_redirect_url_str; ?>" name="<?php echo $mepr_options->logout_redirect_url_str; ?>" class="regular-text" value="<?php echo stripslashes($mepr_options->logout_redirect_url); ?>" /><br/><br/>
      </div>

      <h3 class="mepr-field-label"><?php _e('Account Page Welcome Message', 'memberpress'); ?></h3>
      <div class="mepr-options-pane">
        <?php wp_editor($mepr_options->custom_message, $mepr_options->custom_message_str); ?>
        <p class="description"><?php _e('This text will appear below the navigation on the Account Page.', 'memberpress'); ?></p>
      </div>
    </div>

    <div id="fields" class="mepr-options-hidden-pane">
      <h3><?php _e('Extended User Information Fields:', 'memberpress'); ?></h3>
      <div class="mepr-options-pane">
        <div class="mp-row">
          <div class="mp-col-4">
            <strong><?php _e('Name Fields:', 'memberpress'); ?></strong>
          </div>
          <div class="mp-col-2">
            <label>
              <input type="checkbox" name="<?php echo $mepr_options->show_fname_lname_str; ?>" id="<?php echo $mepr_options->show_fname_lname_str; ?>" <?php checked($mepr_options->show_fname_lname); ?> />
              <span>&nbsp;<?php _e('Show', 'memberpress'); ?></span>
            </label>
          </div>
          <div class="mp-col-2">
            <label>
              <input type="checkbox" name="<?php echo $mepr_options->require_fname_lname_str; ?>" id="<?php echo $mepr_options->require_fname_lname_str; ?>" <?php checked($mepr_options->require_fname_lname); ?> />
              <span>&nbsp;<?php _e('Require', 'memberpress'); ?></span>
            </label>
          </div>
        </div>
        <div class="mp-row">
          <div class="mp-col-4">
            <strong><?php _e('Show & Require Address Fields:', 'memberpress'); ?></strong>
          </div>
          <div class="mp-col-2">
            <label>
              <input type="checkbox" name="<?php echo $mepr_options->show_address_fields_str; ?>" id="<?php echo $mepr_options->show_address_fields_str; ?>" <?php checked($mepr_options->show_address_fields); ?> />
              <span>&nbsp;<?php _e('New Customers', 'memberpress'); ?></span>
            </label>
          </div>
          <div class="mp-col-2">
            <label>
              <input type="checkbox" name="<?php echo $mepr_options->show_address_fields_logged_in_str; ?>" id="<?php echo $mepr_options->show_address_fields_logged_in_str; ?>" <?php checked($mepr_options->show_address_fields_logged_in); ?> />
              <span><?php _e('Logged-in Users', 'memberpress'); ?></span>
            </label>
          </div>
        </div>
      </div>
      <h3>
        <?php _e('Custom User Information Fields:', 'memberpress'); ?>
        <?php MeprAppHelper::info_tooltip( 'mepr-custom-fields',
                                           __('Custom User Information Fields', 'memberpress'),
                                           __('You can specify custom fields to be used with your users\' account. Just click the \'plus\' button below to add your first field.', 'memberpress') ); ?>
      </h3>
      <ol id="custom_profile_fields" class="mepr-sortable">
        <?php MeprOptionsHelper::show_existing_custom_fields(); ?>
        <a href="" id="mepr-add-new-custom-field" title="<?php _e('Add new Custom Field', 'memberpress'); ?>"><i class="mp-icon mp-icon-plus-circled mp-24"></i></a>
      </ol>
      <br/>
    </div>

    <div id="integration" class="mepr-options-hidden-pane">
      <h3><?php _e('Payment Methods', 'memberpress'); ?></h3>
      <div id="integrations-list">
        <?php
        $objs = $mepr_options->payment_methods();
        foreach( $objs as $pm_id => $obj ) {
          if( $obj instanceof MeprBaseRealGateway )
            require(MEPR_VIEWS_PATH . "/options/gateway.php");
        }
        ?>
      </div>
      <a href="" id="mepr-add-integration" title="<?php _e('Add a Payment Method', 'memberpress'); ?>"><i class="mp-icon mp-icon-plus-circled mp-24"></i></a>
      <?php //do_action('mepr-integration-options'); ?>
      <?php //MeprOptionsHelper::gateways_dropdown('gateway[' . time() . ']', ''); ?>
      <?php unset($objs['free']); unset($objs['manual']); ?>
      <div id="no_saved_pms" data-value="<?php echo (empty($objs))?'true':'false'; ?>"></div>
    </div>
    
    <div id="emails" class="mepr-options-hidden-pane">
      <h3><?php _e('Send Mail From', 'memberpress'); ?></h3>
      <div class="mepr-options-pane">
        <label for="<?php echo $mepr_options->mail_send_from_name_str; ?>"><?php _e('From Name:', 'memberpress'); ?></label>
        <input type="text" id="<?php echo $mepr_options->mail_send_from_name_str; ?>" name="<?php echo $mepr_options->mail_send_from_name_str; ?>" class="regular-text" value="<?php echo stripslashes($mepr_options->mail_send_from_name); ?>" />
        <br/>
        <label for="<?php echo $mepr_options->mail_send_from_email_str; ?>"><?php _e('From Email:', 'memberpress'); ?>&nbsp;</label>
        <input type="text" id="<?php echo $mepr_options->mail_send_from_email_str; ?>" name="<?php echo $mepr_options->mail_send_from_email_str; ?>" class="regular-text" value="<?php echo stripslashes($mepr_options->mail_send_from_email); ?>" />
      </div>
      <h3><?php _e('Member Notices', 'memberpress'); ?>
          <?php MeprAppHelper::info_tooltip( 'mepr-member-notices',
                                             __('Member Notices', 'memberpress'),
                                             __('These are notices that will be sent to your members when events happen in MemberPress.', 'memberpress') ); ?>
      </h3>
      <div class="mepr-options-pane">
        <?php MeprAppHelper::display_emails('MeprBaseOptionsUserEmail'); ?>
      </div>
      <h3><?php _e('Admin Emails &amp; Notices', 'memberpress'); ?>
          <?php MeprAppHelper::info_tooltip( 'mepr-member-notices',
                                             __('Admin Notices', 'memberpress'),
                                             __('These are notices that will be sent to the addresses you\'ve set below when events happen in MemberPress.', 'memberpress') ); ?>
      </h3>
      <div class="mepr-options-pane">
        <label for="<?php echo $mepr_options->admin_email_addresses_str; ?>"><?php _e('Admin Email Addresses:', 'memberpress'); ?>
          <?php MeprAppHelper::info_tooltip( 'mepr-admin-email-addresses',
                                             __('Notification Email Addresses', 'memberpress'),
                                             __('This is a comma separated list of email addresses that will recieve admin notifications. This defaults to your admin email set in "Settings" -> "General" -> "E-mail Address"', 'memberpress') ); ?>
        </label>
        <input type="text" id="<?php echo $mepr_options->admin_email_addresses_str; ?>" name="<?php echo $mepr_options->admin_email_addresses_str; ?>" class="regular-text" value="<?php echo stripslashes($mepr_options->admin_email_addresses); ?>" />
      </div>
      <div class="mepr-options-pane">
        <?php MeprAppHelper::display_emails('MeprBaseOptionsAdminEmail'); ?>
      </div>
    </div>

    <div id="marketing" class="mepr-options-hidden-pane">
      <h3><?php _e('Auto Responders', 'memberpress'); ?></h3>
      <div class="mepr-options-pane">
        <?php do_action('mepr_display_autoresponders'); ?>
      </div>
    </div>

    <div id="general" class="mepr-options-hidden-pane">
      <h3><?php _e('Internationalization', 'memberpress'); ?></h3>
      <div class="mepr-options-pane">
        <table>
          <tr>
            <td>
              <span class="mepr-field-label"><?php _e('Language Code:', 'memberpress'); ?></span>
            </td>
            <td>
              <?php MeprOptionsHelper::payment_language_code_dropdown($mepr_options->language_code_str, $mepr_options->language_code); ?>
            </td>
          </tr>
          <tr>
            <td>
              <span class="mepr-field-label"><?php _e('Currency Code:', 'memberpress'); ?></span>
            </td>
            <td>
              <?php MeprOptionsHelper::payment_currency_code_dropdown($mepr_options->currency_code_str, $mepr_options->currency_code); ?>
            </td>
          </tr>
          <tr>
            <td>
              <span class="mepr-field-label"><?php _e('Currency Symbol:', 'memberpress'); ?></span>
            </td>
            <td>
              <?php MeprOptionsHelper::payment_currencies_dropdown($mepr_options->currency_symbol_str, $mepr_options->currency_symbol); ?>
            </td>
          </tr>
        </table>
      </div>
      
      <h3><?php _e('Rewrite Rules', 'memberpress'); ?></h3>
      <div class="mepr-options-pane">
        <input type="checkbox" name="<?php echo $mepr_options->disable_mod_rewrite_str; ?>" id="<?php echo $mepr_options->disable_mod_rewrite_str; ?>" <?php checked($mepr_options->disable_mod_rewrite); ?> />
        <label for="<?php echo $mepr_options->disable_mod_rewrite_str; ?>"><?php _e('Disable mod_rewrite (.htaccess) Rules', 'memberpress'); ?></label>
        <?php MeprAppHelper::info_tooltip( 'mepr-disable-mod-rewrite',
                                           __('Disable mod_rewrite Rules', 'memberpress'),
                                           __("If you are having problems getting other 3rd party applications such as phpBB or phpList to work along side MemberPress, you may need to check this option. Disabling mod_rewrite will mean that individual files cannot be protected with the Custom URI Rules.", 'memberpress') ); ?>
      </div>

      <?php do_action('mepr_display_general_options'); ?>
    </div>
    
    <?php do_action('mepr_display_options'); ?>
    
    <p class="submit">
      <input type="submit" class="button button-primary" name="Submit" value="<?php _e('Update Options', 'memberpress') ?>" />
    </p>
    
  </form>
</div>
