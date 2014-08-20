<?php

/**
 * RoyalMail_Rate_International_Economy class.
 */
class RoyalMail_Rate_International_Economy extends RoyalMail_Rate {

	private $bands = array(
		'letter' => array(
	 		20  => 81,
	 		60  => 143,
	 		100 => 202
	 	),
	 	'packet' => array(
	 		100 => 280,
	 		250 => 365,
	 		500 => 510,
	 		750 => 655,
	 		1000 => 800,
	 		1250 => 945,
	 		1500 => 1090,
	 		1750 => 1235,
	 		2000 => 1380
	 	)
	);

	public $default_boxes = array(
		'letter' => array(
			'length'   => 240, // Max L in mm
			'width'    => 165, // Max W in mm
			'height'   => 5,   // Max H in mm
			'weight'   => 100  // Max Weight in grams
		),
		'long-parcel' => array(
			'length'   => 600,
			'width'    => 150,
			'height'   => 150,
			'weight'   => 500
		),
		'square-parcel' => array(
			'length'   => 300,
			'width'    => 300,
			'height'   => 300,
			'weight'   => 500
		),
		'parcel' => array(
			'length'   => 450,
			'width'    => 225,
			'height'   => 225,
			'weight'   => 500
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
		global $royal_mail_box_sizes;

		$class_quote  = false;

		if ( ! empty( $royal_mail_box_sizes ) ) {
			$this->boxes = array();

			foreach( $royal_mail_box_sizes as $key => $box ) {
				$this->boxes[ $key ] = array(
					'length'     => $box['inner_length'],
					'width'      => $box['inner_width'],
					'height'     => $box['inner_height'],
					'box_weight' => $box['box_weight'],
					'weight' => 500
				);
			}
		} else {
			$this->boxes = $this->default_boxes;
		}

		if ( in_array( $destination, $this->europe ) )
			unset( $this->bands['letter'], $this->boxes['letter'] );

		$zone     = $this->get_zone( $destination );
		$packages = $this->get_packages( $items, $packing_method );

		if ( $packages ) {
			foreach ( $packages as $package ) {

				if ( $package->id !== 'letter' ) {
					$package->id = 'packet';
				}

				if ( ! isset( $this->bands[ $package->id ] ) ) {
					return false; // unpacked item
				}

				$this->debug( __( 'Economy package:', 'woocommerce-shipping-royalmail' ) . ' <pre>' . print_r( $package, true ) . '</pre>' );

				$bands   = $this->bands[ $package->id ];
				$quote   = 0;
				$matched = false;

				foreach ( $bands as $band => $value ) {
					if ( $package->weight <= $band ) {
						$quote += $value;
						$matched = true;
						break;
					}
				}

				if ( ! $matched ) {
					return;
				}

				$class_quote += $quote;
			}
		}

		// Return pounds
		$quotes = array();
		$quotes['international-economy'] = $class_quote / 100;

		return $quotes;
	}
}