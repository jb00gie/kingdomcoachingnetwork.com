<?php

/**
 * RoyalMail_Rate_International_Tracked class.
 */
class RoyalMail_Rate_International_Tracked extends RoyalMail_Rate {

	// EU, NON EU, ZONE 1, Zone 2
	private $bands = array(
		'letter' => array(
	 		20  => array( 716, 597, 628, 628 ),
	 		60  => array( 776, 647, 715, 715 ),
	 		100 => array( 882, 735, 848, 848 )
	 	),
	 	'packet' => array(
	 		100  => array( 984, 820, 880, 900 ),
	 		250  => array( 1044, 870, 975, 1005 ),
	 		500  => array( 1218, 1015, 1245, 1290 ),
	 		750  => array( 1392, 1160, 1515, 1575 ),
	 		1000 => array( 1566, 1305, 1785, 1860 ),
	 		1250 => array( 1740, 1450, 2055, 2145 ),
	 		1500 => array( 1914, 1595, 2325, 2430 ),
	 		1750 => array( 2088, 1740, 2595, 2715 ),
	 		2000 => array( 2262, 1885, 2865, 3000 )
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

	private $compensation    = '250';
	private $compensation_eu = '300';

	/**
	 * Get quotes for this rate
	 * @param  array $items
	 * @param  string $packing_method
	 * @param  string $destination
	 * @return array
	 */
	public function get_quotes( $items, $packing_method, $destination ) {
		global $royal_mail_box_sizes;

		$allowed_countries = array_merge( $GLOBALS['woocommerce']->countries->get_european_union_countries(), array( 'AD', 'FO', 'IS', 'LI', 'MC', 'CH', 'AU', 'BR', 'CA', 'US', 'HK', 'IN', 'MY', 'SG', 'NZ', 'GR', 'HU', 'KR', 'TH' ) );

		if ( ! in_array( $destination, $allowed_countries ) ) {
			return;
		}

		$class_quote = false;

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

				$this->debug( __( 'International tracked package:', 'woocommerce-shipping-royalmail' ) . ' <pre>' . print_r( $package, true ) . '</pre>' );

				$bands   = $this->bands[ $package->id ];
				$quote   = 0;
				$matched = false;

				foreach ( $bands as $band => $value ) {
					if ( $package->weight <= $band ) {
						switch ( $zone ) {
							case 'EU' :
								$quote += $value[0];
								break;
							case 'EUR' :
								$quote += $value[1];
								break;
							case '1' :
								$quote += $value[2];
								break;
							case '2' :
								$quote += $value[3];
								break;
						}
						$matched = true;
						break;
					}
				}

				if ( ! $matched ) {
					return;
				}

				$class_quote  += $quote;

				if ( $package->value > 50 ) {
					switch ( $zone ) {
						case 'EU' :
							$class_quote += $this->compensation_eu;
						break;
						default :
							$class_quote += $this->compensation;
						break;
					}
				}
			}
		}

		// Return pounds
		$quotes = array();
		$quotes['international-tracked'] = $class_quote / 100;

		return $quotes;
	}
}