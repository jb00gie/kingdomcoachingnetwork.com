<?php

/**
 * RoyalMail_Rate_Special_Delivery_1pm class.
 */
class RoyalMail_Rate_Special_Delivery_1pm extends RoyalMail_Rate {

	private $bands = array(
		500 => array(
			100   => 640,
			500   => 715,
			1000  => 845,
			2000  => 1100,
			10000 => 2660,
			20000 => 4120
		),
		1000 => array(
			100   => 740,
			500   => 815,
			1000  => 945,
			2000  => 1200,
			10000 => 2760,
			15000 => 4220
		),
		'more' => array(
			100   => 940,
			500   => 1015,
			1000  => 1145,
			2000  => 1400,
			10000 => 2960,
			15000 => 4420
		)
	);

	public $boxes = array(
		'packet' => array(
			'length'   => 610,
			'width'    => 460,
			'height'   => 460,
			'weight'   => 10000
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

		// Return pounds
		$quotes                         = array();
		$quotes['special-delivery-1pm'] = $quote / 100;

		return $quotes;
	}

}