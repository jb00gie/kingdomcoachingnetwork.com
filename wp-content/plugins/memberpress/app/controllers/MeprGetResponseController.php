<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}
/*
Integration of GetResponse into MemberPress
*/

class MeprGetResponseController extends MeprBaseController
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

    // AJAX Endpoints
    add_action('wp_ajax_mepr_gr_ping_apikey', array($this,'ajax_mepr_gr_ping_apikey'));
    add_action('wp_ajax_mepr_get_campaigns',   array($this,'ajax_mepr_get_campaigns'));

  }
  
  public function display_option_fields()
  {
    ?>
    <div id="mepr-getresponse">
      <input type="checkbox" name="meprgetresponse_enabled" id="meprgetresponse_enabled" <?php checked($this->is_enabled()); ?> />
      <label for="meprgetresponse_enabled"><?php _e('Enable GetResponse', 'memberpress'); ?></label>
    </div>
    <div id="getresponse_hidden_area" class="mepr-options-sub-pane">
      <div id="mepr-getresponse-error" class="mepr-hidden mepr-inactive"></div>
      <div id="mepr-getresponse-message" class="mepr-hidden mepr-active"></div>
      <div id="meprgetresponse-api-key">
        <label>
          <span><?php _e('GetResponse API Key:', 'memberpress'); ?></span>
          <input type="text" name="meprgetresponse_api_key" id="meprgetresponse_api_key" value="<?php echo $this->apikey(); ?>" class="mepr-text-input form-field" size="20" />
          <span id="mepr-getresponse-valid" class="mepr-active mepr-hidden"></span>
          <span id="mepr-getresponse-invalid" class="mepr-inactive mepr-hidden"></span>
        </label>
        <div>
          <span class="description">
            <?php _e('You can find your API key under your Account settings at GetResponse.com.', 'memberpress'); ?>
          </span>
        </div>
      </div>
      <br/>
      <div id="meprgetresponse-options">
        <div id="meprgetresponse-list-id">
          <label>
            <span><?php _e('GetResponse List:', 'memberpress'); ?></span>
            <select name="meprgetresponse_list_id" id="meprgetresponse_list_id" data-listid="<?php echo $this->list_id(); ?>" class="mepr-text-input form-field"></select>
          </label>
        </div>
        <br/>
        <div id="meprgetresponse-optin">
          <label>
            <input type="checkbox" name="meprgetresponse_optin" id="meprgetresponse_optin" <?php checked($this->is_optin_enabled()); ?> />
            <span><?php _e('Enable Opt-In Checkbox', 'memberpress'); ?></span>
          </label>
          <div>
            <span class="description">
              <?php _e('If checked, an opt-in checkbox will appear on all of your product registration pages.', 'memberpress'); ?>
            </span>
          </div>
        </div>
        <div id="meprgetresponse-optin-text" class="mepr-hidden mepr-options-panel">
          <label><?php _e('Signup Checkbox Label:', 'memberpress'); ?>
            <input type="text" name="meprgetresponse_text" id="meprgetresponse_text" value="<?php echo $this->optin_text(); ?>" class="form-field" size="75" />
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
    update_option('meprgetresponse_enabled',      (isset($_POST['meprgetresponse_enabled'])));
    update_option('meprgetresponse_api_key',      stripslashes($_POST['meprgetresponse_api_key']));
    update_option('meprgetresponse_list_id',      (isset($_POST['meprgetresponse_list_id']))?stripslashes($_POST['meprgetresponse_list_id']):false);
    update_option('meprgetresponse_double_optin', (isset($_POST['meprgetresponse_double_optin'])));
    update_option('meprgetresponse_optin',        (isset($_POST['meprgetresponse_optin'])));
    update_option('meprgetresponse_text',         stripslashes($_POST['meprgetresponse_text']));
  }

  public function display_signup_field()
  {
    if($this->is_enabled_and_authorized() and $this->is_optin_enabled())
    {
      $optin = (MeprUtils::is_post_request()?isset($_POST['meprgetresponse_opt_in_set']):true);

      ?>
      <div class="mepr_signup_table_row">
        <div class="mepr-getresponse-signup-field">
          <div id="mepr-getresponse-checkbox">
            <input type="checkbox" name="meprgetresponse_opt_in" id="meprgetresponse_opt_in" class="mepr-form-checkbox" <?php checked($optin); ?> />
            <span class="mepr-getresponse-message"><?php echo $this->optin_text(); ?></span>
          </div>
          <div id="mepr-getresponse-privacy">
            <small>
              <a href="http://www.getresponse.com/legal/privacy.html" class="mepr-getresponse-privacy-link" target="_blank"><?php _e('We Respect Your Privacy', 'memberpress'); ?></a>
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
     
    if( $this->is_optin_enabled() and isset($_POST['meprgetresponse_opt_in']) )
      update_user_meta( $user->ID, 'mepr-getresponse-optin', true );

    if( !$this->is_optin_enabled() or
        ( $this->is_optin_enabled() and
          isset($_POST['meprgetresponse_opt_in']) ) )
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
    $enabled = (bool)get_post_meta($obj->product_id, '_meprgetresponse_list_override', true);
    $list_id = get_post_meta($obj->product_id, '_meprgetresponse_list_override_id', true);

    // If optin is enabled and the user didn't opt-in then we gonna bail
    if( $this->is_optin_enabled() and
        !get_user_meta( $user->ID, 'mepr-getresponse-optin', true ) )
    { return false; }

    if( $enabled && !empty($list_id) &&
        $this->is_enabled_and_authorized() )
    { return $this->add_subscriber( $user, $list_id ); }

    return false;
  }

  public function maybe_delete_subscriber($obj, $user)
  {
    $enabled = (bool)get_post_meta($obj->product_id, '_meprgetresponse_list_override', true);
    $list_id = get_post_meta($obj->product_id, '_meprgetresponse_list_override_id', true);

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
    
    $override_list = (bool)get_post_meta($product->ID, '_meprgetresponse_list_override', true);
    $override_list_id = get_post_meta($product->ID, '_meprgetresponse_list_override_id', true);
    
    ?>
    <div id="mepr-getresponse" class="mepr-product-adv-item">
      <input type="checkbox" name="meprgetresponse_list_override" id="meprgetresponse_list_override" data-apikey="<?php echo $this->apikey(); ?>" <?php checked($override_list); ?> />
      <label for="meprgetresponse_list_override"><?php _e('GetResponse list for this Product', 'memberpress'); ?></label>
      
      <?php MeprAppHelper::info_tooltip('meprgetresponse-list-override',
                                        __('Enable Product GetResponse List', 'memberpress'),
                                        __('If this is set the member will be added to this list when their payment is completed for this product. If the member cancels or you refund their subscription, they will be removed from the list automatically. You must have your GetResponse API key set in the Options before this will work.', 'memberpress'));
      ?>
      
      <div id="meprgetresponse_override_area" class="mepr-hidden product-options-panel">
        <label><?php _e('GetResponse List: ', 'memberpress'); ?></label>
        <select name="meprgetresponse_list_override_id" id="meprgetresponse_list_override_id" data-listid="<?php echo stripslashes($override_list_id); ?>" class="mepr-text-input form-field"></select>
      </div>
    </div>
    <?php
  }

  public function save_product_override($product)
  {
    if(!$this->is_enabled_and_authorized()) { return; }
    
    if(isset($_POST['meprgetresponse_list_override']))
    {
      update_post_meta($product->ID, '_meprgetresponse_list_override', true);
      update_post_meta($product->ID, '_meprgetresponse_list_override_id', stripslashes($_POST['meprgetresponse_list_override_id']));
    }
    else
      update_post_meta($product->ID, '_meprgetresponse_list_override', false);
  }

  public function ping_apikey() {
    return $this->call('ping');
  }

  public function ajax_mepr_gr_ping_apikey() {
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
    
    die($this->call('ping',array(),$_POST['apikey']));
  }

  public function get_lists() {
    return $this->call('getCampaigns');
  }

  public function ajax_mepr_get_campaigns() {
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

    die($this->call('getCampaigns',array(),$_POST['apikey']));
  }

  public function add_subscriber(MeprUser $contact, $list_id) {
    $args = array(
      'campaign' => $list_id,
      'email' => $contact->user_email,
      'ip' => $contact->user_ip,
      'name' => $contact->first_name.' '.$contact->last_name
    ); 

    $res = $this->call('addContact',$args);

    return !isset($res->error);
  }

  public function delete_subscriber(MeprUser $contact, $list_id) {
    $args = array(
      'id' => $contact->user_email,
      'list_id' => $list_id
    ); 

    $res = $this->call('deleteContact',$args);
    return !isset($res->error);
  }

  private function call($endpoint,$args=array(),$apikey=null) {
    if(is_null($apikey)) { $apikey = $this->apikey(); }

    require_once(MEPR_VENDOR_LIB_PATH.'/getresponse/GetResponseAPI.class.php');
    $api = new GetResponse($apikey);
    
    if( $endpoint == 'addContact' ) {
        $response = $api->$endpoint($args['campaign'], $args['name'], $args['email']);
    }elseif( $endpoint == 'getCampaigns' ){
        $response = $api->$endpoint();
        $responsearray = array('total' => count( $response ), 'data' => array() );  

        foreach($response AS $key => $val){
            $responsearray['data'][] = array('list_id' => $key, 'list_name' => $val->name);
        }
        
        $response = json_encode($responsearray);
    }elseif( $endpoint == 'deleteContact' ){
        $response = $api->getContactsByEmail( $args['id'] );
        foreach( $response AS $key => $val ) {
            if( $val->email == $args['id'] && $val->campaign == $args['list_id'] ) $api->$endpoint($key);
        }
    }elseif( $endpoint == 'ping' ){
        $response = $api->ping();
    }else{
        $response = $api->$endpoint();
    }

    if($response)
      return $response;
    else
      return 'error';
  }

  private function is_enabled() {
    return get_option('meprgetresponse_enabled', false);
  }

  private function is_authorized() {
    $apikey = get_option('meprgetresponse_api_key', '');
    return !empty($apikey);
  }

  private function is_enabled_and_authorized() {
    return ($this->is_enabled() and $this->is_authorized());
  }

  private function apikey() {
    return get_option('meprgetresponse_api_key', '');
  }

  private function list_id() {
    return get_option('meprgetresponse_list_id', false);
  }

  private function is_double_optin_enabled() {
    return get_option('meprgetresponse_double_optin', true);
  }

  private function is_optin_enabled() {
    return get_option('meprgetresponse_optin', true);
  }

  private function optin_text() {
    $default = sprintf(__('Sign Up for the %s Newsletter', 'memberpress'), get_option('blogname'));
    return get_option('meprgetresponse_text', $default);
  }
} //END CLASS
