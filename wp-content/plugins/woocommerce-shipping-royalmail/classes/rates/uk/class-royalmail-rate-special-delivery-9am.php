<?php

/**
 * RoyalMail_Rate_Special_Delivery_9am class.
 */
class RoyalMail_Rate_Special_Delivery_9am extends RoyalMail_Rate {

	private $bands = array(
		500 => array(
			100   => 1818,
			500   => 2052,
			1000  => 2226,
			2000  => 2694
		),
		1000 => array(
			100   => 2038,
			500   => 2272,
			1000  => 2446,
			2000  => 2914
		),
		'more' => array(
			100   => 2388,
			500   => 2622,
			1000  => 2796,
			2000  => 3264
		)
	);

	public $boxes = array(
		'packet' => array(
			'length'   => 610,
			'width'    => 460,
			'height'   => 460,
			'weight'   => 20000
		)
	);

	/**
	 * Get quotes for this rate
	 * @param  array $items
	 * @param  string $packing_method
	 * @param  string $destination
	 * @return array
	 */
	public function get_quotes( $items, $packing_method, $destination ) {
		$quote    = false;
		$packages = $this->get_packages( $items, $packing_method );

		if ( $packages ) {
			foreach ( $packages as $package ) {
				if ( empty( $package->id ) ) {
					// Try a tube or fail
					if ( $package->length < 900 && $package->length + ( $package->width * 2 ) < 1040 ) {
						$package->id = 'packet';
					} else {
						return false; // unpacked item
					}
				}

				$this->debug( __( 'Special Delivery package:', 'woocommerce-shipping-royalmail' ) . ' <pre>' . print_r( $package, true ) . '</pre>' );

				$bands   = $this->bands;
				$matched = false;

				foreach ( $bands as $coverage => $weight_bands ) {
					if ( is_numeric( $coverage ) && $package->value > $coverage ) {
						continue;
					}
					foreach ( $weight_bands as $weight => $value ) {

						if ( is_numeric( $weight ) && $package->weight <= $weight ) {
							$quote += $value;
							$matched = true;
							break 2;
						}

					}
				}

				if ( ! $matched ) {
					return;
				}
			}
		}

		// Rates include 20% VAT
		$quote = $quote / 1.2;
		$quote = $quote / 100;

		$quotes                         = array();
		$quotes['special-delivery-9am'] = $quote;

		return $quotes;
	}

}