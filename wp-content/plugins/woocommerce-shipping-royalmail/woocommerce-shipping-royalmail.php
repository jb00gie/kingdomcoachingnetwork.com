<?php
/*
	Plugin Name: WooCommerce Royal Mail
	Plugin URI: http://www.woothemes.com/products/royal-mail/
	Description: Offer Royal Mail shipping rates automatically to your customers. Prices according to <a href="http://www.royalmail.com/sites/default/files/RM_OurPrices_Mar2014a.pdf">the 2014 price guide</a>.
	Version: 2.1.1
	Author: WooThemes / Mike Jolley
	Author URI: http://mikejolley.com

	Copyright: 2009-2014 WooThemes.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '03839cca1a16c4488fcb669aeb91a056', '182719' );

/**
 * Only load the plugin if WooCommerce is activated
 */
if ( is_woocommerce_active() ) {

	/**
	 * Main Royal Mail class
	 */
	class WC_RoyalMail {

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
			add_action( 'woocommerce_shipping_init', array( $this, 'shipping_init' ) );
			add_filter( 'woocommerce_shipping_methods', array( $this, 'shipping_methods' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		}

		/**
		 * Localisation
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'woocommerce-shipping-royalmail', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Add plugin action links to the plugins page
		 * @param  array $links
		 * @return array
		 */
		public function plugin_action_links( $links ) {
			$plugin_links = array(
				'<a href="http://support.woothemes.com/">' . __( 'Support', 'woocommerce-shipping-royalmail' ) . '</a>',
				'<a href="http://docs.woothemes.com/document/royal-mail/">' . __( 'Docs', 'woocommerce-shipping-royalmail' ) . '</a>',
			);
			return array_merge( $plugin_links, $links );
		}

		/**
		 * Load our shipping class
		 */
		public function shipping_init() {
			include_once( 'classes/class-wc-shipping-royalmail.php' );
		}

		/**
		 * Add our shipping method to woocommerce
		 * @param  array $methods
		 * @return array
		 */
		public function shipping_methods( $methods ) {
			$methods[] = 'WC_Shipping_Royalmail';
			return $methods;
		}

		/**
		 * Load scripts in admin
		 */
		public function admin_enqueue_scripts() {
			wp_enqueue_script( 'jquery-ui-sortable' );
		}

	}

	new WC_RoyalMail();
}