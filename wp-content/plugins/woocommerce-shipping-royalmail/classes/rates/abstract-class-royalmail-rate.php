<?php
/**
 * RoyalMail Rate class
 */
abstract class RoyalMail_Rate {

	public $europe = array(
		'AL', 'AD', 'AM', 'AT', 'BY', 'BE', 'BA', 'BG', 'CH', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FO', 'FI', 'FR', 'GE', 'GI', 'GR', 'HU', 'HR', 'IE', 'IS', 'IT', 'LT', 'LU', 'LV', 'MC', 'MK', 'MT', 'NO', 'NL', 'PO', 'PT', 'RO', 'RU', 'SE', 'SI', 'SK', 'SM', 'TR', 'UA', 'VA'
	);

	public $world_zone_2 = array(
		'AU', 'PW', 'IO', 'CX', 'CC', 'CK', 'FJ', 'PF', 'TF', 'KI', 'MO', 'NR', 'NC', 'NZ', 'PG', 'NU', 'NF', 'LA', 'PN', 'TO', 'TV', 'WS', 'AS'
	);

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
	 * Get the zone for the package
	 *
	 * @param string $destination
	 * @return string
	 */
	public function get_zone( $destination ) {
		if ( $destination === 'GB' ) {
			return 'UK';
		} elseif ( in_array( $destination, $GLOBALS['woocommerce']->countries->get_european_union_countries() ) ) {
			return 'EU';
		} elseif ( in_array( $destination, $this->europe ) ) {
			return 'EUR';
		} elseif ( in_array( $destination, $this->world_zone_2 ) ) {
			return '2';
		} else {
			return '1';
		}
	}

	/**
	 * Pack items into boxes and return results
	 *
	 * @param array $items
	 * @param string $method
	 * @return array
	 */
	public function get_packages( $items, $method ) {

	  	$packages = array();
	  	$boxpack  = new WC_Boxpack();

	    // Define boxes
		foreach ( $this->boxes as $box_id => $box ) {
			$newbox = $boxpack->add_box( $box['length'], $box['width'], $box['height'], isset( $box['box_weight'] ) ? $box['box_weight'] : '' );
			$newbox->set_id( $box_id );

			if ( ! empty( $box['weight'] ) ) {
				$newbox->set_max_weight( $box['weight'] );
			}
		}

		if ( $items ) {
			if ( $method == 'per_item' ) {

				foreach ( $items as $item ) {
					$boxpack->clear_items();
					$boxpack->add_item(
						$item->length,
						$item->width,
						$item->height,
						$item->weight,
						$item->value
					);
					$boxpack->pack();
					$item_packages = $boxpack->get_packages();

					for ( $i = 0; $i < $item->qty; $i ++ ) {
						$packages = array_merge( $packages, $item_packages );
					}
				}
			} else {
				foreach ( $items as $item ) {
					for ( $i = 0; $i < $item->qty; $i ++ ) {
						$boxpack->add_item(
							$item->length,
							$item->width,
							$item->height,
							$item->weight,
							$item->value
						);
					}
				}

				// Pack it
				$boxpack->pack();

				// Get packages
				$packages = $boxpack->get_packages();
			}
		}

		return $packages;
	}
}