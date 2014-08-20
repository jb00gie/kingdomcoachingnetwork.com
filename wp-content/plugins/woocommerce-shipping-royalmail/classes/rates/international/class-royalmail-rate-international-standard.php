<?php

/**
 * RoyalMail_Rate_International_Standard class.
 */
class RoyalMail_Rate_International_Standard extends RoyalMail_Rate {

	private $bands = array(
		'letter' => array(
	 		10  => array( 97, 97, 97 ),
	 		20  => array( 97, 128, 128 ),
	 		60  => array( 147, 215, 215 ),
	 		100 => array( 235, 348, 348 )
	 	),
	 	'packet' => array(
	 		100  => array( 320, 380, 400 ),
	 		250  => array( 370, 475, 505 ),
	 		500  => array( 515, 745, 790 ),
	 		750  => array( 660, 1015, 1075 ),
	 		1000 => array( 805, 1285, 1360 ),
	 		1250 => array( 950, 1555, 1645 ),
	 		1500 => array( 1095, 1825, 1930 ),
	 		1750 => array( 1240, 2095, 2215 ),
	 		2000 => array( 1385, 2365, 2500 )
	 	)
	);

	public $default_boxes = array(
		'letter' => array(
			'length'   => 600,
			'width'    => 250,
			'height'   => 50, // Assuming max letter hight is 5cm here
			'weight'   => 100
		),
		'long-parcel' => array(
			'length'   => 600,
			'width'    => 150,
			'height'   => 150,
			'weight'   => 2000
		),
		'square-parcel' => array(
			'length'   => 300,
			'width'    => 300,
			'height'   => 300,
			'weight'   => 2000
		),
		'parcel' => array(
			'length'   => 450,
			'width'    => 225,
			'height'   => 225,
			'weight'   => 2000
		)
	);

	private $signed_cost       = '500';
	private $compensation_cost = '250';

	/**
	 * Get quotes for this rate
	 * @param  array $items
	 * @param  string $packing_method
	 * @param  string $destination
	 * @return array
	 */
	public function get_quotes( $items, $packing_method, $destination ) {
		global $royal_mail_box_sizes;

		$standard_quote = false;
		$signed_quote   = false;

		if ( ! empty( $royal_mail_box_sizes ) ) {
			$this->boxes = array();

			foreach( $royal_mail_box_sizes as $key => $box ) {
				$this->boxes[ $key ] = array(
					'length'     => $box['inner_length'],
					'width'      => $box['inner_width'],
					'height'     => $box['inner_height'],
					'box_weight' => $box['box_weight'],
					'weight'     => 2000
				);
			}
		} else {
			$this->boxes = $this->default_boxes;
		}

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

				$this->debug( __( 'International package:', 'woocommerce-shipping-royalmail' ) . ' <pre>' . print_r( $package, true ) . '</pre>' );

				$bands   = $this->bands[ $package->id ];
				$quote   = 0;
				$matched = false;

				foreach ( $bands as $band => $value ) {
					if ( $package->weight <= $band ) {
						switch ( $zone ) {
							case 'EU' :
							case 'EUR' :
								$quote += $value[0];
							break;
							case '1' :
								$quote += $value[1];
							break;
							case '2' :
								$quote += $value[2];
							break;
						}
						$matched = true;
						break;
					}
				}

				if ( ! $matched ) {
					return;
				}

				$standard_quote += $quote;
				$signed_quote   += $quote + $this->signed_cost;

				if ( $package->value > 50 ) {
					$signed_quote += $this->compensation_cost;
				}
			}
		}

		// Return pounds
		$quotes = array();
		$quotes['international-standard'] = $standard_quote / 100;

		$allowed_countries = array_merge( $GLOBALS['woocommerce']->countries->get_european_union_countries(), array( 'AD', 'FO', 'IS', 'LI', 'MC', 'CH', 'AU', 'BR', 'CA', 'US', 'HK', 'IN', 'MY', 'SG', 'NZ', 'GR', 'HU', 'KR', 'TH' ) );

		if ( in_array( $destination, $allowed_countries ) ) {
			$quotes['international-tracked-signed'] = $signed_quote / 100;
		}

		return $quotes;
	}
}