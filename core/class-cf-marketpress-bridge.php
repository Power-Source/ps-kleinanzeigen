<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access allowed!' );
}

class CF_MarketPress_Bridge {

	public $options_name = CF_OPTIONS_NAME;

	function __construct() {
		add_action( 'mp_order_order_paid', array( $this, 'handle_order_paid' ), 10, 1 );
		add_filter( 'affiliate_marketpress_should_process_order', array( $this, 'maybe_skip_generic_marketpress_affiliate' ), 10, 2 );
	}

	/**
	 * Sync MarketPress paid orders to Classifieds credits/one-time access.
	 *
	 * @param object $order MP_Order object.
	 * @return void
	 */
	function handle_order_paid( $order ) {
		if ( ! is_object( $order ) || ! isset( $order->ID ) ) {
			return;
		}

		$order_post_id = (int) $order->ID;
		if ( $order_post_id <= 0 ) {
			return;
		}

		if ( get_post_meta( $order_post_id, '_cf_mp_bridge_processed', true ) ) {
			return;
		}

		$options  = get_option( $this->options_name );
		$options  = is_array( $options ) ? $options : array();
		$settings = ( isset( $options['payments'] ) && is_array( $options['payments'] ) ) ? $options['payments'] : array();

		if ( empty( $settings['enable_marketpress_bridge'] ) ) {
			return;
		}

		$user_id = $this->resolve_user_id( $order, $order_post_id );
		if ( $user_id <= 0 ) {
			return;
		}

		$credits_product_id  = isset( $settings['mp_credits_product_id'] ) ? absint( $settings['mp_credits_product_id'] ) : 0;
		$one_time_product_id = isset( $settings['mp_one_time_product_id'] ) ? absint( $settings['mp_one_time_product_id'] ) : 0;
		$credits_meta_key    = ! empty( $settings['mp_credit_meta_key'] ) ? sanitize_key( $settings['mp_credit_meta_key'] ) : 'cf_credit_amount';
		$credit_packages     = ( ! empty( $settings['mp_credit_packages'] ) && is_array( $settings['mp_credit_packages'] ) ) ? $settings['mp_credit_packages'] : array();

		$package_product_credits = array();
		foreach ( $credit_packages as $package ) {
			$product_id = empty( $package['product_id'] ) ? 0 : absint( $package['product_id'] );
			$credits = empty( $package['credits'] ) ? 0 : absint( $package['credits'] );
			if ( $product_id > 0 && $credits > 0 ) {
				$package_product_credits[ $product_id ] = $credits;
			}
		}

		$cart_items = method_exists( $order, 'get_meta' ) ? $order->get_meta( 'mp_cart_items', array() ) : array();
		$cart_items = is_array( $cart_items ) ? $cart_items : array();

		$credits_to_add   = 0;
		$grant_one_time   = false;
		$one_time_quantity = 0;
		$purchased_credit_packages = array();
		$processed_detail = array(
			'credits_added' => 0,
			'one_time'      => false,
			'user_id'       => $user_id,
			'order_id'      => $order_post_id,
		);

		foreach ( $cart_items as $parent_product_id => $entries ) {
			if ( ! is_array( $entries ) ) {
				continue;
			}

			$parent_product_id = absint( $parent_product_id );

			foreach ( $entries as $entry_key => $entry ) {
				if ( ! is_array( $entry ) ) {
					continue;
				}

				$qty = isset( $entry['quantity'] ) ? absint( $entry['quantity'] ) : 0;
				if ( $qty <= 0 ) {
					continue;
				}

				$item_product_id = ( '0' === (string) $entry_key ) ? $parent_product_id : absint( $entry_key );

				if ( isset( $package_product_credits[ $item_product_id ] ) ) {
					$credits_to_add += ( absint( $package_product_credits[ $item_product_id ] ) * $qty );
					$purchased_credit_packages[] = $this->build_credit_package_entry( $credit_packages, $item_product_id, $qty );
				} elseif ( isset( $package_product_credits[ $parent_product_id ] ) ) {
					$credits_to_add += ( absint( $package_product_credits[ $parent_product_id ] ) * $qty );
					$purchased_credit_packages[] = $this->build_credit_package_entry( $credit_packages, $parent_product_id, $qty );
				}

				if ( $credits_product_id > 0 && $parent_product_id === $credits_product_id ) {
					$credits_per_item = (int) get_post_meta( $item_product_id, $credits_meta_key, true );
					if ( $credits_per_item <= 0 ) {
						$credits_per_item = (int) get_post_meta( $parent_product_id, $credits_meta_key, true );
					}

					if ( $credits_per_item > 0 ) {
						$credits_to_add += ( $credits_per_item * $qty );
					}
				}

				if ( $one_time_product_id > 0 && ( $parent_product_id === $one_time_product_id || $item_product_id === $one_time_product_id ) ) {
					$grant_one_time = true;
					$one_time_quantity += $qty;
				}
			}
		}

		$transactions = new CF_Transactions( $user_id );

		if ( $credits_to_add > 0 ) {
			$transactions->credits = $transactions->credits + $credits_to_add;
			$processed_detail['credits_added'] = $credits_to_add;
		}

		if ( $grant_one_time ) {
			$transactions->billing_type = 'one_time';
			$transactions->status = 'success';
			$transactions->order_info = array(
				'source'   => 'marketpress',
				'order_id' => $order_post_id,
				'time'     => time(),
			);
			$processed_detail['one_time'] = true;
		}

		$affiliate_settings = isset( $options['affiliate_settings'] ) && is_array( $options['affiliate_settings'] ) ? $options['affiliate_settings'] : array();

		$purchased_credit_packages = array_values( array_filter( $purchased_credit_packages ) );
		if ( ! empty( $purchased_credit_packages ) ) {
			do_action( 'classifieds_affiliate_credit_purchase', $affiliate_settings, $user_id, $order_post_id, $purchased_credit_packages );
			$processed_detail['affiliate_credit_packages'] = $purchased_credit_packages;
		}

		if ( $grant_one_time ) {
			$one_time_purchase = array(
				'product_id' => $one_time_product_id,
				'label'      => empty( $settings['one_time_txt'] ) ? '' : sanitize_text_field( $settings['one_time_txt'] ),
				'price'      => empty( $settings['one_time_cost'] ) ? '0.00' : (string) $settings['one_time_cost'],
				'quantity'   => max( 1, $one_time_quantity ),
			);
			do_action( 'classifieds_affiliate_one_time_purchase', $affiliate_settings, $user_id, $order_post_id, $one_time_purchase );
			$processed_detail['affiliate_one_time'] = $one_time_purchase;
		}

		update_post_meta( $order_post_id, '_cf_mp_bridge_processed', time() );
		update_post_meta( $order_post_id, '_cf_mp_bridge_result', $processed_detail );
	}

	/**
	 * Skip generic MarketPress affiliate commission for Classifieds credit-only orders.
	 *
	 * @param bool     $should_process
	 * @param MP_Order $order
	 * @return bool
	 */
	function maybe_skip_generic_marketpress_affiliate( $should_process, $order ) {
		if ( ! $should_process || ! is_object( $order ) || empty( $order->ID ) ) {
			return $should_process;
		}

		$order_analysis = $this->get_credit_order_analysis( $order );
		if ( ! empty( $order_analysis['has_classifieds_item'] ) && ! empty( $order_analysis['classifieds_only'] ) ) {
			return false;
		}

		return $should_process;
	}

	/**
	 * Build normalized package payload for affiliate payout logic.
	 *
	 * @param array $credit_packages
	 * @param int   $product_id
	 * @param int   $quantity
	 * @return array
	 */
	private function build_credit_package_entry( $credit_packages, $product_id, $quantity ) {
		$product_id = absint( $product_id );
		$quantity = absint( $quantity );
		if ( $product_id <= 0 || $quantity <= 0 ) {
			return array();
		}

		foreach ( $credit_packages as $package ) {
			$package_product_id = empty( $package['product_id'] ) ? 0 : absint( $package['product_id'] );
			if ( $package_product_id !== $product_id ) {
				continue;
			}

			return array(
				'product_id' => $product_id,
				'label'      => empty( $package['label'] ) ? '' : sanitize_text_field( $package['label'] ),
				'credits'    => empty( $package['credits'] ) ? 0 : absint( $package['credits'] ),
				'price'      => empty( $package['price'] ) ? '0.00' : (string) $package['price'],
				'quantity'   => $quantity,
			);
		}

		return array();
	}

	/**
	 * Analyze whether an order only contains Classifieds items.
	 *
	 * @param MP_Order $order
	 * @return array
	 */
	private function get_credit_order_analysis( $order ) {
		$options  = get_option( $this->options_name );
		$options  = is_array( $options ) ? $options : array();
		$settings = ( isset( $options['payments'] ) && is_array( $options['payments'] ) ) ? $options['payments'] : array();
		$credit_packages = ( ! empty( $settings['mp_credit_packages'] ) && is_array( $settings['mp_credit_packages'] ) ) ? $settings['mp_credit_packages'] : array();
		$one_time_product_id = isset( $settings['mp_one_time_product_id'] ) ? absint( $settings['mp_one_time_product_id'] ) : 0;

		$product_ids = array();
		foreach ( $credit_packages as $package ) {
			$product_id = empty( $package['product_id'] ) ? 0 : absint( $package['product_id'] );
			if ( $product_id > 0 ) {
				$product_ids[] = $product_id;
			}
		}

		$product_ids = array_unique( $product_ids );
		if ( $one_time_product_id > 0 ) {
			$product_ids[] = $one_time_product_id;
		}

		if ( empty( $product_ids ) || ! method_exists( $order, 'get_meta' ) ) {
			return array(
				'has_classifieds_item' => false,
				'classifieds_only'     => false,
			);
		}

		$cart_items = $order->get_meta( 'mp_cart_items', array() );
		$cart_items = is_array( $cart_items ) ? $cart_items : array();
		$has_classifieds_item = false;
		$has_non_classifieds_item = false;

		foreach ( $cart_items as $parent_product_id => $entries ) {
			if ( ! is_array( $entries ) ) {
				continue;
			}

			$parent_product_id = absint( $parent_product_id );
			foreach ( $entries as $entry_key => $entry ) {
				if ( ! is_array( $entry ) ) {
					continue;
				}

				$qty = isset( $entry['quantity'] ) ? absint( $entry['quantity'] ) : 0;
				if ( $qty <= 0 ) {
					continue;
				}

				$item_product_id = ( '0' === (string) $entry_key ) ? $parent_product_id : absint( $entry_key );
				if ( in_array( $item_product_id, $product_ids, true ) || in_array( $parent_product_id, $product_ids, true ) ) {
					$has_classifieds_item = true;
				} else {
					$has_non_classifieds_item = true;
				}
			}
		}

		return array(
			'has_classifieds_item' => $has_classifieds_item,
			'classifieds_only'     => ( $has_classifieds_item && ! $has_non_classifieds_item ),
		);
	}

	/**
	 * Resolve WordPress user for the MarketPress order.
	 *
	 * @param object $order
	 * @param int    $order_post_id
	 * @return int
	 */
	private function resolve_user_id( $order, $order_post_id ) {
		$post = get_post( $order_post_id );
		if ( $post && ! empty( $post->post_author ) ) {
			return (int) $post->post_author;
		}

		if ( method_exists( $order, 'get_meta' ) ) {
			$email = $order->get_meta( 'mp_billing_info->email', '' );
			if ( ! empty( $email ) ) {
				$user = get_user_by( 'email', sanitize_email( $email ) );
				if ( $user && ! empty( $user->ID ) ) {
					return (int) $user->ID;
				}
			}
		}

		return 0;
	}
}
