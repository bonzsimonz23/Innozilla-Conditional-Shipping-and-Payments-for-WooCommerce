<?php
/**
 * WC_CSP_Condition_Package_Recurring_Item class
 *
 * @author   Innozilla
 * @package  Innozilla Conditional Shipping and Payments for WooCommerce
 * @since    1.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product on Subscription in Package Condition.
 *
 * @class    WC_CSP_Condition_Package_Recurring_Item
 * @version  1.8.5
 */
class WC_CSP_Condition_Package_Recurring_Item extends WC_CSP_Package_Condition {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                            = 'recurring_item_in_package';
		$this->title                         = __( 'Product on Subscription', 'woocommerce-conditional-shipping-and-payments' );
		$this->supported_global_restrictions = array( 'shipping_methods', 'shipping_countries' );
	}

	/**
	 * Return condition field-specific resolution message which is combined along with others into a single restriction "resolution message".
	 *
	 * @param  array  $data  Condition field data.
	 * @param  array  $args  Optional arguments passed by restriction.
	 * @return string|false
	 */
	public function get_condition_resolution( $data, $args ) {

		if ( empty( $args[ 'package' ] ) || empty( $args[ 'package' ][ 'contents' ] ) ) {
			return false;
		}

		$message                    = false;
		$package_count              = $this->get_package_count( $args );
		$chosen_periods_placeholder = sizeof( $data[ 'value' ] ) === 4 ? __( 'recurring', 'woocommerce-conditional-shipping-and-payments' ) : WC_CSP_Condition::merge_titles( $this->get_billing_period_adverb( $data[ 'value' ] ), array( 'rel' => 'or', 'quotes' => false ) );

		// Get the billing period if it's a recurring package.
		$billing_period = WC_CSP_Restriction::get_extra_package_variable( $args[ 'package' ], 'billing_period' );

		// Initial package.
		if ( ! $billing_period ) {

			if ( $this->modifier_is( $data[ 'modifier' ], array( 'in' ) ) ) {

				if ( 1 === $package_count ) {
					$message = sprintf( __( 'make sure that your cart does not contain any items shipped at %s intervals', 'woocommerce-conditional-shipping-and-payments' ), $chosen_periods_placeholder );
				} else {
					$message = sprintf( __( 'make sure it does not contain any items shipped at %s intervals', 'woocommerce-conditional-shipping-and-payments' ), $chosen_periods_placeholder );
				}

			} elseif ( $this->modifier_is( $data[ 'modifier' ], array( 'not-in' ) ) ) {

				if ( 1 === $package_count ) {
					$message = sprintf( __( 'make sure that your cart contains items shipped at %s intervals', 'woocommerce-conditional-shipping-and-payments' ), $chosen_periods_placeholder );
				} else {
					$message = sprintf( __( 'make sure it contains items shipped at %s intervals', 'woocommerce-conditional-shipping-and-payments' ), $chosen_periods_placeholder );
				}

			} elseif ( $this->modifier_is( $data[ 'modifier' ], array( 'all-in' ) ) ) {

				if ( 1 === $package_count ) {
					$message = sprintf( __( 'make sure that your cart does not only contain items shipped at %s intervals', 'woocommerce-conditional-shipping-and-payments' ), $chosen_periods_placeholder );
				} else {
					$message = sprintf( __( 'make sure it does not only contain items shipped at %s intervals', 'woocommerce-conditional-shipping-and-payments' ), $chosen_periods_placeholder );
				}

			} elseif ( $this->modifier_is( $data[ 'modifier' ], array( 'not-all-in' ) ) ) {

				if ( 1 === $package_count ) {
					$message = sprintf( __( 'make sure that your cart only contains items shipped at %s intervals', 'woocommerce-conditional-shipping-and-payments' ), $chosen_periods_placeholder );
				} else {
					$message = sprintf( __( 'make sure it only contains items shipped at %s intervals', 'woocommerce-conditional-shipping-and-payments' ), $chosen_periods_placeholder );
				}
			}

		// Recurring package.
		} else {
			$message = __( 'consider changing its shipping schedule', 'woocommerce-conditional-shipping-and-payments' );
		}

		return $message;
	}

	/**
	 * Evaluate if the condition is in effect or not.
	 *
	 * @param  array  $data  Condition field data.
	 * @param  array  $args  Optional arguments passed by restriction.
	 * @return boolean
	 */
	public function check_condition( $data, $args ) {

		if ( empty( $args[ 'package' ] ) || empty( $args[ 'package' ][ 'contents' ] ) ) {
			return true;
		}

		$package_billing_period = WC_CSP_Restriction::get_extra_package_variable( $args[ 'package' ], 'billing_period' );

		// Current package is recurring.
		if ( $package_billing_period ) {

			if ( $this->modifier_is( $data[ 'modifier' ], array( 'in', 'all-in' ) ) ) {

				if ( in_array( $package_billing_period, $data[ 'value' ] ) ) {
					return true;
				}

			} elseif ( $this->modifier_is( $data[ 'modifier' ], array( 'not-in', 'not-all-in' ) ) ) {

				if ( ! in_array( $package_billing_period, $data[ 'value' ] ) ) {
					return true;
				}
			}

		// Initial package.
		} else {

			$contents = $args[ 'package' ][ 'contents' ];

			if ( empty( $contents ) ) {
				return false;
			}

			// Processing a renewal?
			// Note: Renewal items can't co-exist with subcription items in the same cart.
			$renewal = wcs_cart_contains_renewal();

			if ( $renewal ) {

				$contains_renewals  = false;
				$all_items_renewals = true;

				// Fetch Subcription and renewal's billing period.
				$subscription_id = (int) $renewal[ 'subscription_renewal' ][ 'subscription_id' ];
				$subscription    = wcs_get_subscription( $subscription_id );
				$billing_period  = $subscription ? $subscription->get_billing_period() : false;

				$is_billing_period_matching = in_array( $billing_period, $data[ 'value' ] );

				foreach ( $contents as $cart_item ) {

					// Check for subscription renewal context.
					if ( isset( $cart_item[ 'subscription_renewal' ] ) && $is_billing_period_matching ) {

						$contains_renewals = true;

						if ( $this->modifier_is( $data[ 'modifier' ], array( 'in', 'not-in' ) ) ) {
							break;
						}

					} else {

						$all_items_renewals = false;

						if ( $this->modifier_is( $data[ 'modifier' ], array( 'all-in', 'not-all-in' ) ) ) {
							break;
						}
					}
				}

				if ( $this->modifier_is( $data[ 'modifier' ], array( 'in' ) ) && $contains_renewals ) {
					return true;
				} elseif ( $this->modifier_is( $data[ 'modifier' ], array( 'not-in' ) ) && ! $contains_renewals ) {
					return true;
				} elseif ( $this->modifier_is( $data[ 'modifier' ], array( 'all-in' ) ) && $all_items_renewals ) {
					return true;
				} elseif ( $this->modifier_is( $data[ 'modifier' ], array( 'not-all-in' ) ) && ! $all_items_renewals ) {
					return true;
				}

			// Search for subscriptions.
			} elseif ( WC_Subscriptions_Cart::cart_contains_subscription() ) {

				$contains_subscription     = false;
				$all_items_on_subscription = true;

				foreach ( $contents as $cart_item ) {

					$is_subscription          = WC_Subscriptions_Product::is_subscription( $cart_item[ 'data' ] );
					$is_matching_subscription = $is_subscription && in_array( WC_Subscriptions_Product::get_period( $cart_item[ 'data' ] ), $data[ 'value' ] );

					if ( $is_matching_subscription ) {

						if ( $this->modifier_is( $data[ 'modifier' ], array( 'in', 'not-in' ) ) ) {
							$contains_subscription = true;
							break;
						}

					} else {

						if ( $this->modifier_is( $data[ 'modifier' ], array( 'all-in', 'not-all-in' ) ) ) {
							$all_items_on_subscription = false;
							break;
						}
					}
				}

				if ( $this->modifier_is( $data[ 'modifier' ], array( 'in' ) ) && $contains_subscription ) {
					return true;
				} elseif ( $this->modifier_is( $data[ 'modifier' ], array( 'not-in' ) ) && ! $contains_subscription ) {
					return true;
				} elseif ( $this->modifier_is( $data[ 'modifier' ], array( 'all-in' ) ) && $all_items_on_subscription ) {
					return true;
				} elseif ( $this->modifier_is( $data[ 'modifier' ], array( 'not-all-in' ) ) && ! $all_items_on_subscription ) {
					return true;
				}

			} else {
				return $this->modifier_is( $data[ 'modifier' ], array( 'not-in', 'not-all-in' ) );
			}
		}

		return false;
	}

	/**
	 * Validate, process and return condition fields.
	 *
	 * @param  array  $posted_condition_data
	 * @return array
	 */
	public function process_admin_fields( $posted_condition_data ) {

		$processed_condition_data = array();

		if ( isset( $posted_condition_data[ 'value' ] ) ) {
			$processed_condition_data[ 'condition_id' ] = $this->id;
			$processed_condition_data[ 'modifier' ]     = stripslashes( $posted_condition_data[ 'modifier' ] );
			$processed_condition_data[ 'value' ]        = array_map( 'wc_clean', $posted_condition_data[ 'value' ] );
		}

		return $processed_condition_data;
	}

	/**
	 * Returns a readable form of the subcription periods.
	 *
	 * @since  1.4.0
	 *
	 * @param  array|String  $periods  Periods to format.
	 * @return array|String
	 */
	private function get_billing_period_adverb( $periods ) {

		$return_array = true;

		// Transform type if String is passed.
		if ( ! is_array( $periods ) ) {

			$return_array = false;
			$periods      = array( $periods );
		}

		$mapper = array(
			'day'   => __( 'daily', 'woocommerce-conditional-shipping-and-payments' ),
			'week'  => __( 'weekly', 'woocommerce-conditional-shipping-and-payments' ),
			'month' => __( 'monthly', 'woocommerce-conditional-shipping-and-payments' ),
			'year'  => __( 'yearly', 'woocommerce-conditional-shipping-and-payments' )
		);

		$formatted = array();

		foreach ( $periods as $period ) {
			if ( isset( $mapper[ $period ] ) ) {
				$formatted[] = $mapper[ $period ];
			} else {
				$formatted[] = $period;
			}
		}

		return $return_array ? $formatted : end( $formatted );
	}

	/**
	 * Get backorders-in-cart condition content for global restrictions.
	 *
	 * @param  int    $index
	 * @param  int    $condition_index
	 * @param  array  $condition_data
	 * @return str
	 */
	public function get_admin_fields_html( $index, $condition_index, $condition_data ) {

	$modifier = '';

	if ( ! empty( $condition_data[ 'modifier' ] ) ) {
		$modifier = $condition_data[ 'modifier' ];
	}

	$periods          = wcs_get_subscription_period_strings();
	$selected_periods = isset( $condition_data[ 'value' ] ) ? $condition_data[ 'value' ] : array();

	?>
	<input type="hidden" name="restriction[<?php echo $index; ?>][conditions][<?php echo $condition_index; ?>][condition_id]" value="<?php echo $this->id; ?>" />
	<div class="condition_row_inner">
		<div class="condition_modifier">
			<div class="sw-enhanced-select">
				<select name="restriction[<?php echo $index; ?>][conditions][<?php echo $condition_index; ?>][modifier]">
					<option value="in" <?php selected( $modifier, 'in', true ) ?>><?php echo __( 'in package', 'woocommerce-conditional-shipping-and-payments' ); ?></option>
					<option value="not-in" <?php selected( $modifier, 'not-in', true ) ?>><?php echo __( 'not in package', 'woocommerce-conditional-shipping-and-payments' ); ?></option>
					<option value="all-in" <?php selected( $modifier, 'all-in', true ) ?>><?php echo __( 'all package items', 'woocommerce-conditional-shipping-and-payments' ); ?></option>
					<option value="not-all-in" <?php selected( $modifier, 'not-all-in', true ) ?>><?php echo __( 'not all package items', 'woocommerce-conditional-shipping-and-payments' ); ?></option>
				</select>
			</div>
		</div>
		<div class="condition_value">
			<select name="restriction[<?php echo $index; ?>][conditions][<?php echo $condition_index; ?>][value][]" class="multiselect sw-select2" multiple="multiple" data-placeholder="<?php _e( 'Select billing period&hellip;', 'woocommerce-conditional-shipping-and-payments' ); ?>">

				<?php
					foreach ( $periods as $value => $label ) {
						echo '<option value="' . esc_attr( $value ) . '" ' . selected( in_array( $value, $selected_periods ), true, false ).'>' . esc_html( $this->get_billing_period_adverb( $value ) ) . '</option>';
					}
				?>

			</select>
		</div>
	</div><?php
	}
}
