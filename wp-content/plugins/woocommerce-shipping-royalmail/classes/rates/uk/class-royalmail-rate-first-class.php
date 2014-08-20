<?php

/**
 * RoyalMail_Rate_First_Class class.
 */
class RoyalMail_Rate_First_Class extends RoyalMail_Rate {

	private $bands = array(
		'letter' => array(
	 		100 => 62
	 	),
	 	'large-letter' => array(
	 		100 => 93,
	 		250 => 124,
	 		500 => 165,
	 		750 => 238
	 	),
	 	'small-parcel-wide' => array(
	 		1000 => 320,
	 		2000 => 545
	 	),
	 	'small-parcel-deep' => array(
	 		1000 => 320,
	 		2000 => 545
	 	),
	 	'medium-parcel' => array(
	 		1000 => 565,
	 		2000 => 890,
	 		5000 => 1585,
	 		10000 => 2190,
	 		20000 => 3340,
	 	)
	);

	public $boxes = array(
		'letter' => array(
			'length'   => 240, // Max L in mm
			'width'    => 165, // Max W in mm
			'height'   => 5,   // Max H in mm
			'weight'   => 100  // Max Weight in grams
		),
		'large-letter' => array(
			'length'   => 353,
			'width'    => 250,
			'height'   => 25,
			'weight'   => 750
		),
		'small-parcel-wide' => array( 
			'length' => 450, 
			'width'  => 350, 
			'height' => 80, 
			'weight' => 2000 
		), 
		'small-parcel-deep' => array( 
			'length' => 350, 
			'width'  => 250, 
			'height' => 160, 
			'weight' => 2000 
		), 
		'medium-parcel' => array(
			'length'   => 610,
			'width'    => 460,
			'height'   => 460,
			'weight'   => 20000
		)
	);

	private $signed_for_cost = '110';

	/**
	 * Get quotes for this rate
	 * @param  array $items
	 * @param  string $packing_method
	 * @param  string $destination
	 * @return array
	 */
	public function get_quotes( $items, $packing_method, $destination ) {
		$class_quote    = false;
		$recorded_quote = false;
		$packages       = $this->get_packages( $items, $packing_method );

		if ( $packages ) {
			foreach ( $packages as $package ) {

				$quote = 0;

				if ( ! isset( $this->bands[ $package->id ] ) ) {
					return false; // unpacked item
				}

				$bands = $this->bands[ $package->id ];

				foreach ( $bands as $band => $value ) {
					if ( is_numeric( $band ) && $package->weight <= $band ) {
						$quote += $value;
						$matched = true;
						break;
					}
				}

				if ( ! $matched ) {
					return;
				}

				$class_quote    += $quote;
				$recorded_quote += $quote + $this->signed_for_cost;
			}
		}

		// Return pounds
		$quotes                       = array();
		$quotes['first-class']        = $class_quote / 100;
		$quotes['first-class-signed'] = $recorded_quote / 100;

		return $quotes;
	}
}