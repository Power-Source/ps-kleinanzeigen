<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CF_Reserved_Status_Service {
	/** @var Classifieds_Core */
	private $core;

	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Check if reserved state can be changed by current user.
	 *
	 * @param int $post_id Post id.
	 * @return bool
	 */
	public function can_manage_reserved_status( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id || 'classifieds' !== get_post_type( $post_id ) ) {
			return false;
		}

		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( current_user_can( 'edit_post', $post_id ) || current_user_can( 'edit_classified', $post_id ) ) {
			return true;
		}

		return (int) get_post_field( 'post_author', $post_id ) === (int) get_current_user_id();
	}

	/**
	 * Check reserved state for classifieds post.
	 *
	 * @param int $post_id Post id.
	 * @return bool
	 */
	public function is_reserved_post( $post_id ) {
		return '1' === (string) get_post_meta( absint( $post_id ), '_cf_reserved', true );
	}

	/**
	 * Toggle reserved state for classifieds post.
	 *
	 * @param int $post_id Post id.
	 * @return bool New reserved state.
	 */
	public function toggle_reserved_status( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $this->can_manage_reserved_status( $post_id ) ) {
			return false;
		}

		$is_reserved = $this->is_reserved_post( $post_id );

		if ( $is_reserved ) {
			delete_post_meta( $post_id, '_cf_reserved' );
			return false;
		}

		update_post_meta( $post_id, '_cf_reserved', 1 );

		return true;
	}
}
