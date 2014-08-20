<?php
/**
 * WC_Shipping_Royalmail_Rates class.
 *
 * @extends WC_Shipping_Method
 */
class WC_Shipping_Royalmail_Rates {

	private $quotes;

	private $items;

	private $destination;

	private $services = array(
		'uk' => array(
			'first-class',
			'second-class',
			'special-delivery-9am',
			'special-delivery-1pm'
		),
		'international' => array(
			'international-tracked',
		    'international-standard',
		    'international-economy',
		    'international-signed'
		)
	);

	/**
	 * Constructor
	 */
	public function __construct( $package, $packing_method ) {

		if ( ! class_exists( 'RoyalMail_Rate' ) ) {
			include_once 'rates/abstract-class-royalmail-rate.php';
		}

		if ( ! class_exists( 'WC_Boxpack' ) ) {
	  		include_once 'box-packer/class-wc-boxpack.php';
		}

		$this->items          = $this->get_items( $package );
		$this->destination    = $package['destination']['country'];
		$this->packing_method = $packing_method;
	}

    /**
     * Output a message
     */
    public function debug( $message, $type = 'notice' ) {
    	if ( WC_ROYALMAIL_DEBUG ) {
    		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
    			wc_add_notice( $message, $type );
    		} else {
    			$GLOBALS['woocommerce']->add_message( $message );
    		}
		}
    }

	/**
	 * Get the plugin path.
	 *
	 * @access public
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) );
	}

	/**
	 * get_quotes function.
	 *
	 * @access public
	 * @return array
	 */
	public function get_quotes() {
		if ( empty( $this->items ) ) {
			return;
		}

		$quotes = array();

		if ( $this->destination == 'GB' ) {
			$services = $this->services['uk'];
			$type     = 'uk';
		} else {
			$services = $this->services['international'];
			$type     = 'international';
		}

		foreach ( $services as $service ) {
			$class = 'RoyalMail_Rate_' . str_replace( '-', '_', $service );
			$path  = $this->plugin_path() . '/classes/rates/' . $type . '/';
			$file  = 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';

			if ( file_exists( $path . $file ) ) {
				include_once( $path . $file );
			}

			if ( class_exists( $class ) ) {
				$service_class = new $class();
				$quotes        = array_merge( $quotes, (array) $service_class->get_quotes( $this->items, $this->packing_method, $this->destination ) );
			}

		}

		return array_filter( $quotes );
	}

    /**
     * get_items function.
     *
     * @access private
     * @param mixed $package
     * @return array
     */
    private function get_items( $package ) {
	    $requests = array();

    	foreach ( $package['contents'] as $item_id => $values ) {

    		if ( ! $values['data']->needs_shipping() ) {
    			$this->debug( sprintf( __( 'Product #%d is virtual. Skipping.', 'woocommerce-shipping-royalmail' ), $item_id ) );
    			continue;
    		}

    		if ( ! $values['data']->get_weight() ) {
	    		$this->debug( sprintf( __( 'Product #%d is missing weight. Aborting.', 'woocommerce-shipping-royalmail' ), $item_id ), 'error' );
	    		return;
    		}

    		$dimensions = array( $values['data']->length, $values['data']->height, $values['data']->width );

			sort( $dimensions );

			$item            = new stdClass();
			$item->weight    = woocommerce_get_weight( $values['data']->get_weight(), 'g' );
			$item->length    = woocommerce_get_dimension( $dimensions[2], 'mm' );
    		$item->width     = woocommerce_get_dimension( $dimensions[1], 'mm' );
    		$item->height    = woocommerce_get_dimension( $dimensions[0], 'mm' );
    		$item->qty       = $values['quantity'];
    		$item->value     = $values['data']->get_price();

    		$requests[] = $item;
    	}

		return $requests;
    }
}