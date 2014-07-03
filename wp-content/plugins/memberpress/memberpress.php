<?php
/*
Plugin Name: MemberPress Business Edition
Plugin URI: http://www.memberpress.com/
Description: The membership plugin that makes it easy to accept payments for access to your content and digital products.
Version: 1.1.6
Author: Caseproof, LLC
Author URI: http://caseproof.com/
Text Domain: memberpress
Copyright: 2004-2014, Caseproof, LLC
*/

if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

define('MEPR_PLUGIN_SLUG',plugin_basename(__FILE__));
define('MEPR_PLUGIN_NAME',dirname(MEPR_PLUGIN_SLUG));
define('MEPR_PATH',WP_PLUGIN_DIR.'/'.MEPR_PLUGIN_NAME);
define('MEPR_IMAGES_PATH',MEPR_PATH.'/images');
define('MEPR_CSS_PATH',MEPR_PATH.'/css');
define('MEPR_JS_PATH',MEPR_PATH.'/js');
define('MEPR_I18N_PATH',MEPR_PATH.'/i18n');
define('MEPR_LIB_PATH',MEPR_PATH.'/app/lib');
define('MEPR_VENDOR_LIB_PATH',MEPR_PATH.'/vendor/lib');
define('MEPR_APIS_PATH',MEPR_PATH.'/app/apis');
define('MEPR_MODELS_PATH',MEPR_PATH.'/app/models');
define('MEPR_CONTROLLERS_PATH',MEPR_PATH.'/app/controllers');
define('MEPR_GATEWAYS_PATH',MEPR_PATH.'/app/gateways');
define('MEPR_EMAILS_PATH',MEPR_PATH.'/app/emails');
define('MEPR_VIEWS_PATH',MEPR_PATH.'/app/views');
define('MEPR_WIDGETS_PATH',MEPR_PATH.'/app/widgets');
define('MEPR_HELPERS_PATH',MEPR_PATH.'/app/helpers');
define('MEPR_URL',plugins_url($path = '/'.MEPR_PLUGIN_NAME));
define('MEPR_VIEWS_URL',MEPR_URL.'/app/views');
define('MEPR_IMAGES_URL',MEPR_URL.'/images');
define('MEPR_CSS_URL',MEPR_URL.'/css');
define('MEPR_JS_URL',MEPR_URL.'/js');
define('MEPR_GATEWAYS_URL',MEPR_URL.'/app/gateways');
define('MEPR_VENDOR_LIB_URL',MEPR_URL.'/vendor/lib');
define('MEPR_SCRIPT_URL',get_option('home').'/index.php?plugin=mepr');
define('MEPR_OPTIONS_SLUG', 'mepr_options');
define('MEPR_EDITION', 'business');

/**
 * Returns current plugin version.
 *
 * @return string Plugin version
 */
function mepr_plugin_info($field) {
  static $plugin_folder, $plugin_file;

  if( !isset($plugin_folder) or !isset($plugin_file) ) {
    if( ! function_exists( 'get_plugins' ) )
      require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
    
    $plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
    $plugin_file = basename( ( __FILE__ ) );
  }

  if(isset($plugin_folder[$plugin_file][$field]))
    return $plugin_folder[$plugin_file][$field];

  return '';
}

// Plugin Information from the plugin header declaration
define('MEPR_VERSION', mepr_plugin_info('Version'));
define('MEPR_DISPLAY_NAME', mepr_plugin_info('Name'));
define('MEPR_AUTHOR', mepr_plugin_info('Author'));
define('MEPR_AUTHOR_URI', mepr_plugin_info('AuthorURI'));
define('MEPR_DESCRIPTION', mepr_plugin_info('Description'));

// Autoload all the requisite classes
function mepr_autoloader($class_name)
{
  // Only load MemberPress classes here
  if(preg_match('/^Mepr.+$/', $class_name))
  {
    if(preg_match('/^Mepr(Base|Cpt).+$/', $class_name)) // Base classes are in lib
      $filepath = MEPR_LIB_PATH."/{$class_name}.php";
    else if(preg_match('/^.+Controller$/', $class_name))
      $filepath = MEPR_CONTROLLERS_PATH."/{$class_name}.php";
    else if(preg_match('/^.+Helper$/', $class_name))
      $filepath = MEPR_HELPERS_PATH."/{$class_name}.php";
    else if(preg_match('/^.+Exception$/', $class_name))
      $filepath = MEPR_LIB_PATH."/MeprExceptions.php";
    else if(preg_match('/^.+Gateway$/', $class_name)) {
      foreach( MeprGatewayFactory::paths() as $path ) {
        $filepath = $path."/{$class_name}.php";
        if( file_exists($filepath) ) {
          include_once($filepath); return;
        }
      }
      return;
    }
    else if(preg_match('/^.+Email$/', $class_name)) {
      foreach( MeprEmailFactory::paths() as $path ) {
        $filepath = $path."/{$class_name}.php";
        if( file_exists($filepath) ) {
          include_once($filepath); return;
        }
      }
      return;
    }
    else {
      $filepath = MEPR_MODELS_PATH."/{$class_name}.php";
    
      // Now let's try the lib dir if its not a model
      if(!file_exists($filepath))
        $filepath = MEPR_LIB_PATH."/{$class_name}.php";
    }
    
    if(file_exists($filepath))
      include_once($filepath);
  }
}

// if __autoload is active, put it on the spl_autoload stack
if(is_array(spl_autoload_functions()) and in_array('__autoload', spl_autoload_functions()))
  spl_autoload_register('__autoload');

// Add the autoloader
spl_autoload_register('mepr_autoloader');

// Gotta load the language before everything else
MeprAppController::load_language();

// Load our controllers
$controllers = @glob( MEPR_CONTROLLERS_PATH . '/*', GLOB_NOSORT );
foreach( $controllers as $controller ) {
  $class = preg_replace( '#\.php#', '', basename($controller) );
  if( preg_match( '#Mepr.*Controller#', $class ) )
    $obj = new $class;
}

// Setup screens
MeprAppController::setup_menus();

// Include Widgets

// Register Widgets

// Include APIs

// Template Tags
function mepr_account_link()
{
  echo MeprUsersController::get_account_links();
}

register_activation_hook( MEPR_PLUGIN_SLUG, create_function( '', 'require_once( MEPR_LIB_PATH . "/activation.php");' ) );
register_deactivation_hook( MEPR_PLUGIN_SLUG, create_function( '', 'require_once( MEPR_LIB_PATH . "/deactivation.php");' ) );
//register_uninstall_hook( MEPR_PLUGIN_SLUG, create_function( '', 'require_once( MEPR_PATH . "/uninstall.php");' ) );

