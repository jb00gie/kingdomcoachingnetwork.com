<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}
/*
Integration of MailChimp into MemberPress
*/
class MeprMailChimpController extends MeprBaseController
{
  public function load_hooks()
  {
    add_action('mepr_display_autoresponders',   array($this,'display_option_fields'));
    add_action('mepr-process-options',          array($this,'store_option_fields'));
    add_action('mepr-user-signup-fields',       array($this,'display_signup_field'));
    add_action('mepr-process-signup',           array($this,'process_signup'), 10, 4);
    add_action('mepr-txn-store',                array($this,'process_status_changes'));
    add_action('mepr-subscr-store',             array($this,'process_status_changes'));
    add_action('mepr-product-advanced-metabox', array($this,'display_product_override'));
    add_action('mepr-product-save-meta',        array($this,'save_product_override'));
    
    //Regression settings as of 1.0.7 changes
    //This can probably be removed in at some point to cut down on queries run
    add_action('admin_init', array($this,'regression_enable'));

    // AJAX Endpoints
    add_action('wp_ajax_mepr_ping_apikey', array($this,'ajax_ping_apikey'));
    add_action('wp_ajax_mepr_get_lists',   array($this,'ajax_get_lists'));
  }
  
  public function regression_enable()
  {
    $mailchimp_enabled = get_option('meprmailchimp_enabled', 'not_set');
    
    if($mailchimp_enabled == 'not_set')
    {
      $mailchimp_api_key = get_option('meprmailchimp_api_key', '');
      $mailchimp_list_id = get_option('meprmailchimp_list_id', '');
      
      //If mailchimp was activated before our 1.0.7 changes, let's make sure it stays activated
      if(!empty($mailchimp_api_key) && !empty($mailchimp_list_id))
        update_option('meprmailchimp_enabled', true);
    }
  }
  
  public function display_option_fields()
  {
    ?>
    <div id="mepr-mailchimp">
      <input type="checkbox" name="meprmailchimp_enabled" id="meprmailchimp_enabled" <?php checked($this->is_enabled()); ?> />
      <label for="meprmailchimp_enabled"><?php _e('Enable MailChimp', 'memberpress'); ?></label>
    </div>
    <div id="mailchimp_hidden_area" class="mepr-options-sub-pane">
      <div id="mepr-mailchimp-error" class="mepr-hidden mepr-inactive"></div>
      <div id="mepr-mailchimp-message" class="mepr-hidden mepr-active"></div>
      <div id="meprmailchimp-api-key">
        <label>
          <span><?php _e('MailChimp API Key:', 'memberpress'); ?></span>
          <input type="text" name="meprmailchimp_api_key" id="meprmailchimp_api_key" value="<?php echo $this->apikey(); ?>" class="mepr-text-input form-field" size="20" />
          <span id="mepr-mailchimp-valid" class="mepr-active mepr-hidden"></span>
          <span id="mepr-mailchimp-invalid" class="mepr-inactive mepr-hidden"></span>
        </label>
        <div>
          <span class="description">
            <?php _e('You can find your API key under your Account settings at MailChimp.com.', 'memberpress'); ?>
          </span>
        </div>
      </div>
      <br/>
      <div id="meprmailchimp-options">
        <div id="meprmailchimp-list-id">
          <label>
            <span><?php _e('MailChimp List:', 'memberpress'); ?></span>
            <select name="meprmailchimp_list_id" id="meprmailchimp_list_id" data-listid="<?php echo $this->list_id(); ?>" class="mepr-text-input form-field"></select>
          </label>
        </div>
        <br/>
        <div id="meprmailchimp-double-optin">
          <label for="meprmailchimp_double_optin">
            <input type="checkbox" name="meprmailchimp_double_optin" id="meprmailchimp_double_optin" class="form-field" <?php checked($this->is_double_optin_enabled()); ?> />
            <span><?php _e('Enable Double Opt-in', 'memberpress'); ?></span>
          </label><br/>
          <span class="description">
            <?php _e("Members will have to click a confirmation link in an email before being added to your list.", 'memberpress'); ?>
          </span>
        </div>
        <br/>
        <div id="meprmailchimp-optin">
          <label>
            <input type="checkbox" name="meprmailchimp_optin" id="meprmailchimp_optin" <?php checked($this->is_optin_enabled()); ?> />
            <span><?php _e('Enable Opt-In Checkbox', 'memberpress'); ?></span>
          </label>
          <div>
            <span class="description">
              <?php _e('If checked, an opt-in checkbox will appear on all of your product registration pages.', 'memberpress'); ?>
            </span>
          </div>
        </div>
        <div id="meprmailchimp-optin-text" class="mepr-hidden mepr-options-panel">
          <label><?php _e('Signup Checkbox Label:', 'memberpress'); ?>
            <input type="text" name="meprmailchimp_text" id="meprmailchimp_text" value="<?php echo $this->optin_text(); ?>" class="form-field" size="75" />
          </label>
          <div><span class="description"><?php _e('This is the text that will display on the signup page next to your mailing list opt-in checkbox.', 'memberpress'); ?></span></div>
        </div>
      </div>
    </div>
    <?php
  }
  
  public function validate_option_fields($errors)
  {
    // Nothing to validate yet -- if ever
  }
  
  public function update_option_fields()
  {
    // Nothing to do yet -- if ever
  }

  public function store_option_fields()
  {
    update_option('meprmailchimp_enabled',      (isset($_POST['meprmailchimp_enabled'])));
    update_option('meprmailchimp_api_key',      stripslashes($_POST['meprmailchimp_api_key']));
    update_option('meprmailchimp_list_id',      (isset($_POST['meprmailchimp_list_id']))?stripslashes($_POST['meprmailchimp_list_id']):false);
    update_option('meprmailchimp_double_optin', (isset($_POST['meprmailchimp_double_optin'])));
    update_option('meprmailchimp_optin',        (isset($_POST['meprmailchimp_optin'])));
    update_option('meprmailchimp_text',         stripslashes($_POST['meprmailchimp_text']));
  }

  public function display_signup_field()
  {
    if($this->is_enabled_and_authorized() and $this->is_optin_enabled())
    {
      $optin = (MeprUtils::is_post_request()?isset($_POST['meprmailchimp_opt_in_set']):true);

      ?>
      <div class="mepr_signup_table_row">
        <div class="mepr-mailchimp-signup-field">
          <div id="mepr-mailchimp-checkbox">
            <input type="checkbox" name="meprmailchimp_opt_in" id="meprmailchimp_opt_in" class="mepr-form-checkbox" <?php checked($optin); ?> />
            <span class="mepr-mailchimp-message"><?php echo $this->optin_text(); ?></span>
          </div>
          <div id="mepr-mailchimp-privacy">
            <small>
              <a href="http://mailchimp.com/legal/privacy/" class="mepr-mailchimp-privacy-link" target="_blank"><?php _e('We Respect Your Privacy', 'memberpress'); ?></a>
            </small>
          </div>
        </div>
      </div>
      <?php
     }
  }
  
  public function process_signup($txn_amount, $user, $prod_id, $txn_id)
  {
    if( !$this->is_enabled_and_authorized() ) { return; }
     
    if( $this->is_optin_enabled() and isset($_POST['meprmailchimp_opt_in']) )
      update_user_meta( $user->ID, 'mepr-mailchimp-optin', true );

    if( !$this->is_optin_enabled() or
        ( $this->is_optin_enabled() and
          isset($_POST['meprmailchimp_opt_in']) ) )
      $this->add_subscriber( $user, $this->list_id() );
  }

  public function process_status_changes($obj)
  {
    //Let's not update on txn confirmation types, only on real payments
    if( $obj instanceof MeprTransaction &&
        $obj->txn_type == MeprTransaction::$subscription_confirmation_str)
    { return; }
    
    $user = new MeprUser($obj->user_id);
    
    //Member is active so let's not remove them
    if(in_array($obj->product_id, $user->active_product_subscriptions('ids', true)))
      $this->maybe_add_subscriber($obj, $user);
    else
      $this->maybe_delete_subscriber($obj, $user);
  }

  public function maybe_add_subscriber($obj, $user)
  {
    $enabled = (bool)get_post_meta($obj->product_id, '_meprmailchimp_list_override', true);
    $list_id = get_post_meta($obj->product_id, '_meprmailchimp_list_override_id', true);

    // If optin is enabled and the user didn't opt-in then we gonna bail
    if( $this->is_optin_enabled() and
        !get_user_meta( $user->ID, 'mepr-mailchimp-optin', true ) )
    { return false; }

    if( $enabled && !empty($list_id) &&
        $this->is_enabled_and_authorized() )
    { return $this->add_subscriber( $user, $list_id ); }

    return false;
  }

  public function maybe_delete_subscriber($obj, $user)
  {
    $enabled = (bool)get_post_meta($obj->product_id, '_meprmailchimp_list_override', true);
    $list_id = get_post_meta($obj->product_id, '_meprmailchimp_list_override_id', true);

    if( $enabled && !empty($list_id) &&
        $this->is_enabled_and_authorized() )
    { return $this->delete_subscriber( $user, $list_id ); }

    return false;
  }
  
  public function validate_signup_field($errors)
  {
    // Nothing to validate -- if ever
  }
  
  public function display_product_override($product)
  {
    if(!$this->is_enabled_and_authorized()) { return; }
    
    $override_list = (bool)get_post_meta($product->ID, '_meprmailchimp_list_override', true);
    $override_list_id = get_post_meta($product->ID, '_meprmailchimp_list_override_id', true);
    
    ?>
    <div id="mepr-mailchimp" class="mepr-product-adv-item">
      <input type="checkbox" name="meprmailchimp_list_override" id="meprmailchimp_list_override" data-apikey="<?php echo $this->apikey(); ?>" <?php checked($override_list); ?> />
      <label for="meprmailchimp_list_override"><?php _e('MailChimp list for this Product', 'memberpress'); ?></label>
      
      <?php MeprAppHelper::info_tooltip('meprmailchimp-list-override',
                                        __('Enable Product MailChimp List', 'memberpress'),
                                        __('If this is set the member will be added to this list when their payment is completed for this product. If the member cancels or you refund their subscription, they will be removed from the list automatically. You must have your MailChimp API key set in the Options before this will work.', 'memberpress'));
      ?>
      
      <div id="meprmailchimp_override_area" class="mepr-hidden product-options-panel">
        <label><?php _e('MailChimp List: ', 'memberpress'); ?></label>
        <select name="meprmailchimp_list_override_id" id="meprmailchimp_list_override_id" data-listid="<?php echo stripslashes($override_list_id); ?>" class="mepr-text-input form-field"></select>
      </div>
    </div>
    <?php
  }

  public function save_product_override($product)
  {
    if(!$this->is_enabled_and_authorized()) { return; }
    
    if(isset($_POST['meprmailchimp_list_override']))
    {
      update_post_meta($product->ID, '_meprmailchimp_list_override', true);
      update_post_meta($product->ID, '_meprmailchimp_list_override_id', stripslashes($_POST['meprmailchimp_list_override_id']));
    }
    else
      update_post_meta($product->ID, '_meprmailchimp_list_override', false);
  }

  public function ping_apikey() {
    return $this->call('/helper/ping');
  }

  public function ajax_ping_apikey() {
    // Validate nonce and user capabilities
    if( !isset($_POST['wpnonce']) or
        !wp_verify_nonce( $_POST['wpnonce'], MEPR_PLUGIN_SLUG ) or
        !current_user_can('manage_options') )
      die( json_encode( array( 'error' => __('Hey yo, why you creepin\'?', 'memberpress'),
                               'type' => 'memberpress' ) ) );

    // Validate inputs
    if( !isset( $_POST['apikey'] ) ) 
      die( json_encode( array( 'error' => __('No apikey code was sent', 'memberpress'),
                               'type' => 'memberpress' ) ) );

    die($this->call('/helper/ping',array(),$_POST['apikey']));
  }

  public function get_lists() {
    return $this->call('/lists/list');
  }

  public function ajax_get_lists() {
    // Validate nonce and user capabilities
    if( !isset($_POST['wpnonce']) or
        !wp_verify_nonce( $_POST['wpnonce'], MEPR_PLUGIN_SLUG ) or
        !current_user_can('manage_options') )
      die( json_encode( array( 'error' => __('Hey yo, why you creepin\'?', 'memberpress'),
                               'type' => 'memberpress' ) ) );

    // Validate inputs
    if( !isset( $_POST['apikey'] ) ) 
      die( json_encode( array( 'error' => __('No apikey code was sent', 'memberpress'),
                               'type' => 'memberpress' ) ) );

    die($this->call('/lists/list',array(),$_POST['apikey']));
  }

  public function add_subscriber(MeprUser $contact, $list_id) {
    $args = array(
      'id' => $list_id,
      'email' => array( 'email' => $contact->user_email ),
      'double_optin' => (int)$this->is_double_optin_enabled(),
      'update_existing' => true,
      'merge_vars' => array(
        'optin_ip' => $contact->user_ip,
        'fname' => $contact->first_name,
        'lname' => $contact->last_name
      )
    ); 

    $addr = array();

    $addr_field = get_user_meta( $contact->ID, 'mepr-address-one', true );
    if( !empty($addr_field) ) { $addr['addr1'] = $addr_field; $addr_field = ''; }

    $addr_field = get_user_meta( $contact->ID, 'mepr-address-two', true );
    if( !empty($addr_field) ) { $addr['addr2'] = $addr_field; $addr_field = ''; }

    $addr_field = get_user_meta( $contact->ID, 'mepr-address-city', true );
    if( !empty($addr_field) ) { $addr['city'] = $addr_field; $addr_field = ''; }

    $addr_field = get_user_meta( $contact->ID, 'mepr-address-state', true );
    if( !empty($addr_field) ) { $addr['state'] = $addr_field; $addr_field = ''; }

    $addr_field = get_user_meta( $contact->ID, 'mepr-address-zip', true );
    if( !empty($addr_field) ) { $addr['zip'] = $addr_field; $args['merge_vars']['zip'] = $addr_field; $addr_field = ''; }

    $addr_field = get_user_meta( $contact->ID, 'mepr-address-country', true );
    if( !empty($addr_field) ) { $addr['country'] = $addr_field; $addr_field = ''; }

    if(!empty($addr)) { $args['merge_vars']['address'] = $addr; }

    $res = (array)json_decode($this->call('/lists/subscribe',$args));

    return !isset($res['error']);
  }

  public function delete_subscriber(MeprUser $contact, $list_id) {
    $args = array(
      'id' => $list_id,
      'email' => array( $contact->user_email )
    ); 

    $res = (array)json_decode($this->call('/lists/unsubscribe',$args));
    return !isset($res['error']);
  }

  private function call($endpoint,$args=array(),$apikey=null) {
    if(is_null($apikey)) { $apikey = $this->apikey(); }
    $dc = $this->get_datacenter($apikey);
    $dc = (empty($dc) ? '' : "{$dc}.");
    $url = "https://{$dc}api.mailchimp.com/2.0{$endpoint}.json";

    $args['apikey'] = $apikey;
    $args = array( 'body' => json_encode( $args ) );
    $res = wp_remote_post( $url, $args );
    
    if(!is_wp_error($res))
      return $res['body'];
    else
      return false;
  }

  public function get_datacenter($apikey) {
    $dc = explode('-', $apikey);
    return isset($dc[1]) ? $dc[1] : '';
  }

  // I realize these are more like model methods
  // but we want everything centralized here people
  private function is_enabled() {
    return get_option('meprmailchimp_enabled', false);
  }

  private function is_authorized() {
    $apikey = get_option('meprmailchimp_api_key', '');
    return !empty($apikey);
  }

  private function is_enabled_and_authorized() {
    return ($this->is_enabled() and $this->is_authorized());
  }

  private function apikey() {
    return get_option('meprmailchimp_api_key', '');
  }

  private function list_id() {
    return get_option('meprmailchimp_list_id', false);
  }

  private function is_double_optin_enabled() {
    return get_option('meprmailchimp_double_optin', true);
  }

  private function is_optin_enabled() {
    return get_option('meprmailchimp_optin', true);
  }

  private function optin_text() {
    $default = sprintf(__('Sign Up for the %s Newsletter', 'memberpress'), get_option('blogname'));
    return get_option('meprmailchimp_text', $default);
  }
} //END CLASS

