<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CF_Favorites_Manager {
	/** @var Classifieds_Core */
	private $core;

	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * @return array<int>
	 */
	public function get_favorite_ids() {
		$favorites = array();

		if ( is_user_logged_in() ) {
			$favorites = get_user_meta( get_current_user_id(), '_cf_favorites', true );
		} elseif ( ! empty( $_COOKIE['cf_favorites'] ) ) {
			$favorites = json_decode( wp_unslash( $_COOKIE['cf_favorites'] ), true );
		}

		if ( ! is_array( $favorites ) ) {
			$favorites = array();
		}

		$favorites = array_map( 'absint', $favorites );
		$favorites = array_filter( $favorites );

		return array_values( array_unique( $favorites ) );
	}

	/**
	 * @param array<int> $favorites
	 * @return void
	 */
	public function persist_favorite_ids( $favorites ) {
		$favorites = array_map( 'absint', (array) $favorites );
		$favorites = array_values( array_unique( array_filter( $favorites ) ) );

		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), '_cf_favorites', $favorites );
			return;
		}

		setcookie(
			'cf_favorites',
			wp_json_encode( $favorites ),
			array(
				'expires'  => time() + MONTH_IN_SECONDS,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);

		$_COOKIE['cf_favorites'] = wp_json_encode( $favorites );
	}

	/**
	 * @param int $post_id
	 * @return bool
	 */
	public function is_favorite_post( $post_id ) {
		return in_array( absint( $post_id ), $this->get_favorite_ids(), true );
	}

	/**
	 * @return void
	 */
	public function ajax_toggle_favorite() {
		check_ajax_referer( 'cf_frontend_actions', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! $post_id || 'classifieds' !== get_post_type( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Die Anzeige konnten wir nicht finden.', $this->core->text_domain ) ), 400 );
		}

		$favorites = $this->get_favorite_ids();
		$active    = false;

		if ( in_array( $post_id, $favorites, true ) ) {
			$favorites = array_values( array_diff( $favorites, array( $post_id ) ) );
		} else {
			$favorites[] = $post_id;
			$active      = true;
		}

		$this->persist_favorite_ids( $favorites );

		wp_send_json_success(
			array(
				'active' => $active,
				'count'  => count( $favorites ),
			)
		);
	}
}
