<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}
/*
Basic Integration of Aweber into MemberPress
*/
class MeprAWeberBasicController extends MeprBaseController
{
  public function load_hooks()
  {
    //Regression settings as of 1.0.7 changes
    //This can probably be removed in 1.1.0 to cut down on queries run
    add_action('admin_init', 'MeprAWeberBasicController::regression_enable', 1);
    
    // We are only allowing this option from here on out if the user has already got
    // this enabled ... so this is purely for backwards compatibility at this point
    $enabled = get_option('mepraweber_enabled', false);
    $adv_enabled = get_option('mepr_adv_aweber_enabled', false);

    if( $enabled and !$adv_enabled and !isset($_POST['adv-aweber-enabled']) ) {
      add_action('mepr_display_autoresponders', 'MeprAWeberBasicController::display_option_fields');
      add_action('mepr-process-options', 'MeprAWeberBasicController::store_option_fields');
      add_action('mepr-user-signup-fields', 'MeprAWeberBasicController::display_signup_field');
      add_action('mepr_signup_thankyou_message', 'MeprAWeberBasicController::thank_you_message');
      add_action('wp_enqueue_scripts', 'MeprAWeberBasicController::load_scripts');
    }
  }
  
  public static function regression_enable()
  {
    $aweber_enabled = get_option('mepraweber_enabled', 'not_set');
    
    if($aweber_enabled == 'not_set')
    {
      $aweber_listname = get_option('mepraweber_listname', '');
      
      //If aweber was activated before our 1.0.7 changes, let's make sure it stays activated
      if(!empty($aweber_listname))
        update_option('mepraweber_enabled', true);
    }
  }
  
  public static function display_option_fields()
  {
    $aweber_enabled = get_option('mepraweber_enabled', false);
    $aweber_listname = get_option('mepraweber_listname', '');
    $aweber_text = get_option('mepraweber_text', '');
    
    ?>
      <p>
        <input type="checkbox" name="mepraweber_enabled" id="mepraweber_enabled" <?php checked($aweber_enabled); ?> />
        <label for="mepraweber_enabled"><?php _e('Enable AWeber Basic (deprecated)', 'memberpress'); ?></label>
      </p>
      <div id="aweber_hidden_area" class="mepr-options-sub-pane">
        <p class="mepr-inactive"><?php _e('AWeber Basic Integration has been deprecated ... please setup your AWeber Integration from the "Enable AWeber" option below', 'memberpress'); ?></p>
        <p>
          <label><?php _e('AWeber List Name', 'memberpress'); ?>:&nbsp;
          <input type="text" name="mepraweber_listname" id="mepraweber_listname" value="<?php echo $aweber_listname; ?>" class="mepr-text-input form-field" size="20" tabindex="19" /></label><br/>
          <span class="description"><?php _e('Enter the AWeber mailing list name that you want users signed up for when they sign up for Memberpress.', 'memberpress'); ?></span>
        </p>
        <p>
          <label><?php _e('Signup Checkbox Label', 'memberpress'); ?>:&nbsp;
          <input type="text" name="mepraweber_text" id="mepraweber_text" value="<?php echo $aweber_text; ?>" class="form-field" size="75" tabindex="20" /></label><br/>
          <span class="description"><?php _e('This is the text that will display on the signup page next to your mailing list opt-out checkbox.', 'memberpress'); ?></span>
        </p>
      </div>
    <?php
  }
  
  public static function validate_option_fields($errors)
  {
    // Nothing to validate yet -- if ever
  }
  
  public static function update_option_fields()
  {
    // Nothing to do yet -- if ever
  }
  
  public static function store_option_fields()
  {
    update_option('mepraweber_enabled', (isset($_POST['mepraweber_enabled'])));
    update_option('mepraweber_listname', stripslashes($_POST['mepraweber_listname']));
    update_option('mepraweber_text', stripslashes($_POST['mepraweber_text']));
  }
  
  public static function display_signup_field()
  {
    global $mepr_user;
    
    $listname = get_option('mepraweber_listname');
    $enabled = get_option('mepraweber_enabled', false);

    if($enabled)
    {
      if(isset($_POST['mepraweber_opt_in_set']))
        $checked = isset($_POST['mepraweber_opt_in'])?' checked="checked"':'';
      else
        $checked = ' checked="checked"';
      
      $message = get_option('mepraweber_text');
      
      if(!$message or empty($message))
        $message = sprintf(__('Sign Up for the %s Newsletter', 'memberpress'), get_option('blogname'));
      
      ?>
      <div class="mepr_signup_table_row">
        <div class="mepr-aweber-signup-field">
          <input type="hidden" name="mepraweber_opt_in_set" value="Y" />
          <div id="mepr-aweber-checkbox"><input type="checkbox" name="mepraweber_opt_in" data-listname="<?php echo $listname; ?>" id="mepraweber_opt_in" class="mepr-form-checkbox"<?php echo $checked; ?>/> <span class="mepr-aweber-message"><?php echo $message; ?></span></div>
          <div id="mepr-aweber-privacy"><small><a href="http://www.aweber.com/permission.htm" class="mepr-aweber-privacy-link" target="_blank"><?php _e('We Respect Your Privacy', 'memberpress'); ?></a></small></div>
        </div>
      </div>
      <?php
     }
  }
  
  public static function validate_signup_field($errors)
  {
    // Nothing to validate -- if ever
  }
  
  public static function load_scripts()
  {
    if(MeprUtils::is_product_page())
    {
      $listname = get_option('mepraweber_listname');
      
      if(!empty($listname))
        wp_enqueue_script('mp-aweber', MEPR_JS_URL.'/aweber.js', array('jquery'));
    }
  }
  
  public static function thank_you_message()
  {
    if(isset($_POST['mepraweber_opt_in']))
    {
    ?>
      <h3><?php _e("You're Almost Done - Activate Your Newsletter Subscription!", 'memberpress'); ?></h3>
      <p><?php _e("You've just been sent an email that contains a <strong>confirm link</strong>.", 'memberpress'); ?></p>
      <p><?php _e("In order to activate your subscription, check your email and click on the link in that email.
         You will not receive your subscription until you <strong>click that link to activate it</strong>.", 'memberpress'); ?></p>
      <p><?php _e("If you don't see that email in your inbox shortly, fill out the form again to have another copy of it sent to you.", 'memberpress'); ?></p>
    <?php
    }
  }
} //END CLASS
