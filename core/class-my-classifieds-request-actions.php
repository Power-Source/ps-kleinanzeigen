<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CF_My_Classifieds_Request_Actions {
	/** @var Classifieds_Core */
	private $core;

	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Verarbeitet bestaetigte Aktionen aus dem Bereich "Meine Anzeigen".
	 *
	 * @param array $request
	 * @return void
	 */
	public function handle_confirm_action( $request ) {
		if ( empty( $request['confirm'] ) ) {
			return;
		}

		if ( empty( $request['_wpnonce'] ) || ! wp_verify_nonce( $request['_wpnonce'], 'verify' ) ) {
			die( __( 'Security check failed!', $this->core->text_domain ) );
		}

		$action = isset( $request['action'] ) ? sanitize_key( $request['action'] ) : '';
		$post_id = isset( $request['post_id'] ) ? (int) $request['post_id'] : 0;

		if ( $action === 'end' ) {
			$this->core->process_status( $post_id, 'private' );
			return;
		}

		if ( $action === 'renew' ) {
			$duration_key = isset( $this->core->custom_fields['duration'] ) ? $this->core->custom_fields['duration'] : 'duration';
			$duration = isset( $request[ $duration_key ] ) ? $request[ $duration_key ] : ( $request['duration'] ?? '' );
			$credits_required = $this->core->get_credits_from_duration( $duration );
			$payment_options = (array) $this->core->get_options( 'payments' );
			$restart_mode = isset( $payment_options['expired_restart_mode'] ) ? sanitize_key( $payment_options['expired_restart_mode'] ) : 'credits';
			if ( ! in_array( $restart_mode, array( 'none', 'free', 'credits' ), true ) ) {
				$restart_mode = 'credits';
			}

			if ( 'none' === $restart_mode ) {
				set_query_var( 'cf_error', __( 'Neustart nach Ablauf ist deaktiviert.', $this->core->text_domain ) );
				return;
			}

			$requires_credits = ( 'credits' === $restart_mode );
			$has_access = $this->core->is_full_access() || ! $requires_credits || ( $this->core->user_credits >= $credits_required );

			if ( $has_access ) {
				$this->core->process_status( $post_id, 'publish' );
				$this->core->save_expiration_date( $post_id, true );

				if ( ! $this->core->is_full_access() && $requires_credits ) {
					$this->core->transactions->credits -= $credits_required;
				} elseif ( $this->core->transactions->billing_type == 'one_time' ) {
					$this->core->transactions->status = 'used';
				}
			} else {
				$error = __( 'Du hast nicht genug Credits fuer die ausgewaehlte Laufzeit. Waehle, wenn moeglich, eine kuerzere Laufzeit oder kauf mehr Credits. Deine Anzeige wurde als Entwurf gespeichert.', $this->core->text_domain );
				set_query_var( 'cf_error', $error );
			}

			return;
		}

		if ( $action === 'delete' ) {
			wp_delete_post( $post_id );
			set_query_var( 'cf_action', 'my-classifieds' );
			return;
		}

		if ( $action === 'reserve' ) {
			$this->core->toggle_reserved_status( $post_id );
			set_query_var( 'cf_action', 'my-classifieds' );
		}
	}
}
