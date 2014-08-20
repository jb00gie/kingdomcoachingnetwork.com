<?php
/**
 * WC_Shipping_Royalmail class.
 *
 * @extends WC_Shipping_Method
 */
class WC_Shipping_Royalmail extends WC_Shipping_Method {

	private $services = array(
		'first-class'                  => '1st Class',
		'first-class-signed'           => 'Signed For&reg; 1st Class',
		'second-class'                 => '2nd Class',
		'second-class-signed'          => 'Signed For&reg; 2nd Class',
		
		'special-delivery-9am'         => 'Special Delivery Guaranteed by 9am&reg;',
		'special-delivery-1pm'         => 'Special Delivery Guaranteed by 1pm&reg;',
		
		'international-standard'       => 'International Standard',
		'international-tracked-signed' => 'International Tracked &amp; Signed',
		'international-tracked'        => 'International Tracked',
		'international-signed'         => 'International Signed',
		'international-economy'        => 'International Economy'
	);

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->id                 = 'royal_mail';
		$this->method_title       = __( 'Royal Mail', 'woocommerce-shipping-royalmail' );
		$this->method_description = __( 'Offer Royal Mail shipping rates automatically to your customers. Prices according to <a href="http://www.royalmail.com/sites/default/files/RM_OurPrices_Mar2014a.pdf">the 2014 price guide</a>.', 'woocommerce-shipping-royalmail' );
		$this->init();
	}

    /**
     * init function.
     *
     * @access public
     * @return void
     */
    private function init() {
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title           = isset( $this->settings['title'] ) ? $this->settings['title'] : $this->method_title;
		$this->availability    = isset( $this->settings['availability'] ) ? $this->settings['availability'] : 'all';
		$this->countries       = isset( $this->settings['countries'] ) ? $this->settings['countries'] : array();
		$this->packing_method  = isset( $this->settings['packing_method'] ) ? $this->settings['packing_method'] : 'per_item';
		$this->offer_rates     = isset( $this->settings['offer_rates'] ) ? $this->settings['offer_rates'] : 'all';
		$this->debug           = isset( $this->settings['debug_mode'] ) && $this->settings['debug_mode'] == 'yes' ? true : false;
		$this->custom_services = isset( $this->settings['services'] ) ? $this->settings['services'] : array();
		$this->boxes           = isset( $this->settings['boxes'] ) ? $this->settings['boxes'] : array();

		$GLOBALS['royal_mail_box_sizes'] = $this->boxes;

		if ( ! defined( 'WC_ROYALMAIL_DEBUG' ) ) {
			define( 'WC_ROYALMAIL_DEBUG', $this->debug );
		}

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * environment_check function.
	 *
	 * @access public
	 * @return void
	 */
	private function environment_check() {
		global $woocommerce;

		if ( get_woocommerce_currency() != "GBP" ) {
			echo '<div class="error">
				<p>' . __( 'Royal Mail requires that the currency is set to British Pounds.', 'woocommerce-shipping-royalmail' ) . '</p>
			</div>';
		}

		elseif ( $woocommerce->countries->get_base_country() != "GB" ) {
			echo '<div class="error">
				<p>' . __( 'Royal Mail requires that the base country/region is set to United Kingdom.', 'woocommerce-shipping-royalmail' ) . '</p>
			</div>';
		}
	}

	/**
	 * admin_options function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_options() {
		// Check users environment supports this method
		$this->environment_check();

		// Show settings
		parent::admin_options();
	}

	/**
	 * generate_services_html function.
	 *
	 * @access public
	 * @return void
	 */
	function generate_services_html() {
		global $woocommerce;
		ob_start();
		?>
		<tr valign="top" id="service_options">
			<th scope="row" class="titledesc"><?php _e( 'Services', 'woocommerce-shipping-royalmail' ); ?></th>
			<td class="forminp">
				<table class="royal_mail_services widefat">
					<thead>
						<th class="sort">&nbsp;</th>
						<th width="1%">&nbsp;</th>
						<th><?php _e( 'Name', 'woocommerce-shipping-royalmail' ); ?></th>
						<th><?php _e( 'Enabled', 'woocommerce-shipping-royalmail' ); ?></th>
						<th><?php echo sprintf( __( 'Price Adjustment (%s)', 'woocommerce-shipping-royalmail' ), get_woocommerce_currency_symbol() ); ?></th>
						<th><?php _e( 'Price Adjustment (%)', 'woocommerce-shipping-royalmail' ); ?></th>
					</thead>
					<tbody>
						<?php
							$sort = 0;
							$this->ordered_services = array();

							foreach ( $this->services as $code => $name ) {

								if ( isset( $this->custom_services[ $code ]['order'] ) ) {
									$sort = $this->custom_services[ $code ]['order'];
								}

								while ( isset( $this->ordered_services[ $sort ] ) )
									$sort++;

								$this->ordered_services[ $sort ] = array( $code, $name );

								$sort++;
							}

							ksort( $this->ordered_services );

							foreach ( $this->ordered_services as $value ) {
								$code = $value[0];
								$name = $value[1];
								?>
								<tr>
									<td class="sort"><input type="hidden" class="order" name="royal_mail_service[<?php echo $code; ?>][order]" value="<?php echo isset( $this->custom_services[ $code ]['order'] ) ? $this->custom_services[ $code ]['order'] : ''; ?>" /></td>
									<td width="1%"><strong><?php echo '<img class="help_tip" data-tip="' . esc_attr( $code ) . '" src="' . esc_url( $woocommerce->plugin_url() ) . '/assets/images/help.png" height="16" width="16" />'; ?></strong></td>
									<td><input type="text" name="royal_mail_service[<?php echo $code; ?>][name]" placeholder="<?php echo $this->title; ?> <?php echo $name; ?>" value="<?php echo isset( $this->custom_services[ $code ]['name'] ) ? $this->custom_services[ $code ]['name'] : ''; ?>" size="50" /></td>
									<td><input type="checkbox" name="royal_mail_service[<?php echo $code; ?>][enabled]" <?php checked( ( ! isset( $this->custom_services[ $code ]['enabled'] ) || ! empty( $this->custom_services[ $code ]['enabled'] ) ), true ); ?> /></td>
									<td><input type="text" name="royal_mail_service[<?php echo $code; ?>][adjustment]" placeholder="N/A" value="<?php echo isset( $this->custom_services[ $code ]['adjustment'] ) ? $this->custom_services[ $code ]['adjustment'] : ''; ?>" size="4" /></td>
									<td><input type="text" name="royal_mail_service[<?php echo $code; ?>][adjustment_percent]" placeholder="N/A" value="<?php echo isset( $this->custom_services[ $code ]['adjustment_percent'] ) ? $this->custom_services[ $code ]['adjustment_percent'] : ''; ?>" size="4" /></td>
								</tr>
								<?php
							}
						?>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * generate_box_packing_html function.
	 *
	 * @access public
	 * @return void
	 */
	public function generate_box_packing_html() {
		ob_start();
		?>
		<tr valign="top" id="packing_options">
			<th scope="row" class="titledesc"><?php _e( 'Standard/International Parcel Sizes', 'woocommerce-shipping-royalmail' ); ?></th>
			<td class="forminp">
				<style type="text/css">
					.royal_mail_boxes td, .royal_mail_services td {
						vertical-align: middle;
						padding: 4px 7px;
					}
					.royal_mail_boxes th, .royal_mail_services th {
						padding: 9px 7px;
					}
					.royal_mail_boxes td input {
						margin-right: 4px;
					}
					.royal_mail_boxes .check-column {
						vertical-align: middle;
						text-align: left;
						padding: 0 7px;
					}
					.royal_mail_services th.sort {
						width: 16px;
					}
					.royal_mail_services td.sort {
						cursor: move;
						width: 16px;
						padding: 0 16px;
						cursor: move;
						background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAHUlEQVQYV2O8f//+fwY8gJGgAny6QXKETRgEVgAAXxAVsa5Xr3QAAAAASUVORK5CYII=) no-repeat center;
					}
				</style>
				<table class="royal_mail_boxes widefat">
					<thead>
						<tr>
							<th class="check-column"><input type="checkbox" /></th>
							<th><?php _e( 'Length', 'woocommerce-shipping-royalmail' ); ?></th>
							<th><?php _e( 'Width', 'woocommerce-shipping-royalmail' ); ?></th>
							<th><?php _e( 'Height', 'woocommerce-shipping-royalmail' ); ?></th>
							<th><?php _e( 'Box Weight', 'woocommerce-shipping-royalmail' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th colspan="2">
								<a href="#" class="button plus insert"><?php _e( 'Add Box', 'woocommerce-shipping-royalmail' ); ?></a>
								<a href="#" class="button minus remove"><?php _e( 'Remove selected box(es)', 'woocommerce-shipping-royalmail' ); ?></a>
							</th>
							<th colspan="3">
								<small class="description"><?php _e( 'When calculating rates for international mail, items will be packed into these boxes depending on item dimensions and volume. The boxes will then be quoted accordingly.', 'woocommerce-shipping-royalmail' ); ?></small>
								<br/><br/>
								<small class="description"><?php _e( 'The parcels length, width and depth combined must not be no greater than 900mm. The greatest single dimension must not exceed 600mm', 'woocommerce-shipping-royalmail' ); ?></small>
							</th>
						</tr>
					</tfoot>
					<tbody id="rates">
						<?php
							if ( $this->boxes ) {
								foreach ( $this->boxes as $key => $box ) {
									?>
									<tr>
										<td class="check-column"><input type="checkbox" /></td>
										<td><input type="text" size="5" name="boxes_inner_length[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['inner_length'] ); ?>" />mm</td>
										<td><input type="text" size="5" name="boxes_inner_width[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['inner_width'] ); ?>" />mm</td>
										<td><input type="text" size="5" name="boxes_inner_height[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['inner_height'] ); ?>" />mm</td>
										<td><input type="text" size="5" name="boxes_box_weight[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['box_weight'] ); ?>" />g</td>
									</tr>
									<?php
								}
							}
						?>
					</tbody>
				</table>
				<script type="text/javascript">

					jQuery(window).load(function(){

						jQuery('.royal_mail_boxes .insert').click( function() {
							var $tbody = jQuery('.royal_mail_boxes').find('tbody');
							var size = $tbody.find('tr').size();
							var code = '<tr class="new">\
									<td class="check-column"><input type="checkbox" /></td>\
									<td><input type="text" size="5" name="boxes_inner_length[' + size + ']" />mm</td>\
									<td><input type="text" size="5" name="boxes_inner_width[' + size + ']" />mm</td>\
									<td><input type="text" size="5" name="boxes_inner_height[' + size + ']" />mm</td>\
									<td><input type="text" size="5" name="boxes_box_weight[' + size + ']" />g</td>\
								</tr>';

							$tbody.append( code );

							return false;
						} );

						jQuery('.royal_mail_boxes .remove').click(function() {
							var $tbody = jQuery('.royal_mail_boxes').find('tbody');

							$tbody.find('.check-column input:checked').each(function() {
								jQuery(this).closest('tr').hide().find('input').val('');
							});

							return false;
						});

						// Ordering
						jQuery('.royal_mail_services tbody').sortable({
							items:'tr',
							cursor:'move',
							axis:'y',
							handle: '.sort',
							scrollSensitivity:40,
							forcePlaceholderSize: true,
							helper: 'clone',
							opacity: 0.65,
							placeholder: 'wc-metabox-sortable-placeholder',
							start:function(event,ui){
								ui.item.css('background-color','#f6f6f6');
							},
							stop:function(event,ui){
								ui.item.removeAttr('style');
								royal_mail_services_row_indexes();
							}
						});

						function royal_mail_services_row_indexes() {
							jQuery('.royal_mail_services tbody tr').each(function(index, el){
								jQuery('input.order', el).val( parseInt( jQuery(el).index('.royal_mail_services tr') ) );
							});
						};

					});

				</script>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * validate_box_packing_field function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function validate_box_packing_field( $key ) {
		$boxes_inner_length = isset( $_POST['boxes_inner_length'] ) ? $_POST['boxes_inner_length'] : array();
		$boxes_inner_width  = isset( $_POST['boxes_inner_width'] ) ? $_POST['boxes_inner_width'] : array();
		$boxes_inner_height = isset( $_POST['boxes_inner_height'] ) ? $_POST['boxes_inner_height'] : array();
		$boxes_box_weight   = isset( $_POST['boxes_box_weight'] ) ? $_POST['boxes_box_weight'] : array();

		$boxes = array();

		for ( $i = 0; $i < sizeof( $boxes_inner_length ); $i ++ ) {

			if ( $boxes_inner_length[ $i ] && $boxes_inner_width[ $i ] && $boxes_inner_height[ $i ] ) {

				$boxes[] = array(
					'inner_length' => floatval( $boxes_inner_length[ $i ] ),
					'inner_width'  => floatval( $boxes_inner_width[ $i ] ),
					'inner_height' => floatval( $boxes_inner_height[ $i ] ),
					'box_weight'   => floatval( $boxes_box_weight[ $i ] ),
				);
			}
		}

		return $boxes;
	}

	/**
	 * validate_services_field function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function validate_services_field( $key ) {
		$services         = array();
		$posted_services  = $_POST['royal_mail_service'];

		foreach ( $posted_services as $code => $settings ) {

			$services[ $code ] = array(
				'name'               => woocommerce_clean( $settings['name'] ),
				'order'              => woocommerce_clean( $settings['order'] ),
				'enabled'            => isset( $settings['enabled'] ) ? true : false,
				'adjustment'         => woocommerce_clean( $settings['adjustment'] ),
				'adjustment_percent' => str_replace( '%', '', woocommerce_clean( $settings['adjustment_percent'] ) )
			);
		}

		return $services;
	}

    /**
     * init_form_fields function.
     *
     * @access public
     * @return void
     */
    public function init_form_fields() {
	    global $woocommerce;

    	$this->form_fields  = array(
			'enabled'          => array(
				'title'           => __( 'Enable/Disable', 'woocommerce-shipping-royalmail' ),
				'type'            => 'checkbox',
				'label'           => __( 'Enable this shipping method', 'woocommerce-shipping-royalmail' ),
				'default'         => 'no'
			),
			'title'            => array(
				'title'           => __( 'Method Title', 'woocommerce-shipping-royalmail' ),
				'type'            => 'text',
				'description'     => __( 'This controls the title which the user sees during checkout.', 'woocommerce-shipping-royalmail' ),
				'default'         => __( 'Royal Mail', 'woocommerce-shipping-royalmail' )
			),
		    'availability'  => array(
				'title'           => __( 'Method Availability', 'woocommerce-shipping-royalmail' ),
				'type'            => 'select',
				'default'         => 'all',
				'class'           => 'availability',
				'options'         => array(
					'all'            => __( 'All Countries', 'woocommerce-shipping-royalmail' ),
					'specific'       => __( 'Specific Countries', 'woocommerce-shipping-royalmail' ),
				),
			),
			'countries'        => array(
				'title'           => __( 'Specific Countries', 'woocommerce-shipping-royalmail' ),
				'type'            => 'multiselect',
				'class'           => 'chosen_select',
				'css'             => 'width: 450px;',
				'default'         => '',
				'options'         => $woocommerce->countries->get_allowed_countries(),
			),
			'debug_mode'  => array(
				'title'           => __( 'Debug Mode', 'wc_australia_post' ),
				'label'           => __( 'Enable debug mode', 'wc_australia_post' ),
				'type'            => 'checkbox',
				'default'         => 'yes',
				'description'     => __( 'Enable debug mode to show debugging information on your cart/checkout.', 'wc_australia_post' )
			),
		    'rates'           => array(
				'title'           => __( 'Rates and Services', 'woocommerce-shipping-royalmail' ),
				'type'            => 'title',
				'description'     => __( 'The following settings determine the rates you offer your customers.', 'woocommerce-shipping-royalmail' ),
		    ),
			'packing_method'  => array(
				'title'           => __( 'Parcel Packing Method', 'woocommerce-shipping-royalmail' ),
				'type'            => 'select',
				'default'         => '',
				'class'           => 'packing_method',
				'options'         => array(
					'per_item'       => __( 'Default: Pack items individually', 'woocommerce-shipping-royalmail' ),
					'box_packing'    => __( 'Recommended: Pack items into boxes together', 'woocommerce-shipping-royalmail' ),
				),
			),
			'boxes'  => array(
				'type'            => 'box_packing'
			),
			'offer_rates'   => array(
				'title'           => __( 'Offer Rates', 'woocommerce-shipping-royalmail' ),
				'type'            => 'select',
				'description'     => '',
				'default'         => 'all',
				'options'         => array(
				    'all'         => __( 'Offer the customer all returned rates', 'woocommerce-shipping-royalmail' ),
				    'cheapest'    => __( 'Offer the customer the cheapest rate only, anonymously', 'woocommerce-shipping-royalmail' ),
				),
		    ),
			'services'  => array(
				'type'            => 'services'
			),
		);
    }

    /**
     * calculate_shipping function.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping( $package ) {
    	global $woocommerce;

    	include_once( 'class-wc-shipping-royalmail-rates.php' );

    	$rates     = array();
    	$rates_api = new WC_Shipping_Royalmail_Rates( $package, $this->packing_method );
    	$quotes    = $rates_api->get_quotes();

    	if ( $quotes ) {

	    	foreach ( $quotes as $rate_code => $cost ) {

				$rate_id   = $this->id . ':' . $rate_code;
				$rate_name = $this->title . ' ' . $this->services[ $rate_code ];
				$rate_cost = $cost;

				// Name adjustment
				if ( ! empty( $this->custom_services[ $rate_code ]['name'] ) )
					$rate_name = $this->custom_services[ $rate_code ]['name'];

				// Cost adjustment %
				if ( ! empty( $this->custom_services[ $rate_code ]['adjustment_percent'] ) )
					$rate_cost = $rate_cost + ( $rate_cost * ( floatval( $this->custom_services[ $rate_code ]['adjustment_percent'] ) / 100 ) );
				// Cost adjustment
				if ( ! empty( $this->custom_services[ $rate_code ]['adjustment'] ) )
					$rate_cost = $rate_cost + floatval( $this->custom_services[ $rate_code ]['adjustment'] );

				// Enabled check
				if ( isset( $this->custom_services[ $rate_code ] ) && empty( $this->custom_services[ $rate_code ]['enabled'] ) )
					continue;

				// Sort
				if ( isset( $this->custom_services[ $rate_code ]['order'] ) ) {
					$sort = $this->custom_services[ $rate_code ]['order'];
				} else {
					$sort = 999;
				}

				$rates[ $rate_id ] = array(
					'id' 	=> $rate_id,
					'label' => $rate_name,
					'cost' 	=> $rate_cost,
					'sort'  => $sort
				);
			}
		}

		// Add rates
		if ( $rates ) {

			if ( $this->offer_rates == 'all' ) {

				uasort( $rates, array( $this, 'sort_rates' ) );

				foreach ( $rates as $key => $rate ) {
					$this->add_rate( $rate );
				}

			} else {

				$cheapest_rate = '';

				foreach ( $rates as $key => $rate ) {
					if ( ! $cheapest_rate || $cheapest_rate['cost'] > $rate['cost'] )
						$cheapest_rate = $rate;
				}

				$cheapest_rate['label'] = $this->title;

				$this->add_rate( $cheapest_rate );
			}
		}

    }

    /**
     * sort_rates function.
     *
     * @access public
     * @param mixed $a
     * @param mixed $b
     * @return void
     */
    public function sort_rates( $a, $b ) {
		if ( $a['sort'] == $b['sort'] ) return 0;
		return ( $a['sort'] < $b['sort'] ) ? -1 : 1;
    }
}