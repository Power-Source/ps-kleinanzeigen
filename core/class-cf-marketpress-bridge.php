<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access allowed!' );
}

class CF_MarketPress_Bridge {

	public $options_name = CF_OPTIONS_NAME;

	function __construct() {
		add_action( 'mp_order_order_paid', array( $this, 'handle_order_paid' ), 10, 1 );
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
				} elseif ( isset( $package_product_credits[ $parent_product_id ] ) ) {
					$credits_to_add += ( absint( $package_product_credits[ $parent_product_id ] ) * $qty );
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

		update_post_meta( $order_post_id, '_cf_mp_bridge_processed', time() );
		update_post_meta( $order_post_id, '_cf_mp_bridge_result', $processed_detail );
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
