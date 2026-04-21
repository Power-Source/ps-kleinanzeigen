<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CF_My_Classifieds_Ajax {
	/** @var Classifieds_Core */
	private $core;
	/** @var CF_My_Classifieds_Dashboard */
	private $dashboard;

	public function __construct( $core ) {
		$this->core = $core;
		require_once $this->core->plugin_dir . 'core/class-my-classifieds-dashboard.php';
		$this->dashboard = new CF_My_Classifieds_Dashboard( $this->core );
	}

	/**
	 * AJAX: Nachricht senden
	 */
	public function ajax_send_message() {
		check_ajax_referer( 'cf_send_message', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Du musst eingeloggt sein.', $this->core->text_domain ) ), 403 );
		}

		$post_id      = absint( $_POST['post_id'] ?? 0 );
		$recipient_id = absint( $_POST['recipient_id'] ?? 0 );
		$thread_id    = absint( $_POST['thread_id'] ?? 0 );
		$subject      = sanitize_text_field( $_POST['subject'] ?? '' );
		$message_text = sanitize_textarea_field( $_POST['message'] ?? '' );

		if ( ! $message_text ) {
			wp_send_json_error( array( 'message' => __( 'Nachricht darf nicht leer sein.', $this->core->text_domain ) ) );
		}

		if ( ! $recipient_id && $post_id ) {
			$post = get_post( $post_id );
			if ( $post ) {
				$recipient_id = (int) $post->post_author;
			}
		}

		if ( ! $recipient_id ) {
			wp_send_json_error( array( 'message' => __( 'Empfänger nicht gefunden.', $this->core->text_domain ) ) );
		}

		$sender_id = get_current_user_id();
		if ( $sender_id === $recipient_id ) {
			wp_send_json_error( array( 'message' => __( 'Du kannst dir selbst keine Nachricht schicken.', $this->core->text_domain ) ) );
		}

		// Rate limiting: max 10 Nachrichten pro Stunde
		$count_key = 'cf_msg_rate_' . $sender_id;
		$count     = (int) get_transient( $count_key );
		if ( $count >= 10 ) {
			wp_send_json_error( array( 'message' => __( 'Zu viele Nachrichten. Bitte warte etwas.', $this->core->text_domain ) ) );
		}
		set_transient( $count_key, $count + 1, HOUR_IN_SECONDS );

		$msg_id = wp_insert_post( array(
			'post_type'    => 'cf_message',
			'post_status'  => 'publish',
			'post_title'   => $subject ?: __( 'Anfrage zur Anzeige', $this->core->text_domain ),
			'post_content' => $message_text,
			'post_author'  => $sender_id,
		) );

		if ( is_wp_error( $msg_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Fehler beim Senden.', $this->core->text_domain ) ) );
		}

		update_post_meta( $msg_id, '_cf_msg_recipient', $recipient_id );
		update_post_meta( $msg_id, '_cf_msg_post_id', $post_id );
		update_post_meta( $msg_id, '_cf_msg_thread_id', $thread_id ?: $msg_id );

		if ( ! $thread_id ) {
			update_post_meta( $msg_id, '_cf_msg_thread_id', $msg_id );
		}

		$recipient = get_userdata( $recipient_id );
		$sender    = get_userdata( $sender_id );
		$ad_title  = $post_id ? get_the_title( $post_id ) : '';

		$email_subject = $ad_title
			? sprintf( __( 'Neue Nachricht zur Anzeige: %s', $this->core->text_domain ), $ad_title )
			: __( 'Du hast eine neue Nachricht', $this->core->text_domain );

		$email_body  = '<p>' . sprintf( __( 'Hallo %s,', $this->core->text_domain ), esc_html( $recipient->display_name ) ) . '</p>';
		$email_body .= '<p>' . sprintf( __( '%s hat dir eine Nachricht geschickt:', $this->core->text_domain ), esc_html( $sender->display_name ) ) . '</p>';
		$email_body .= '<blockquote>' . nl2br( esc_html( $message_text ) ) . '</blockquote>';
		if ( $post_id ) {
			$email_body .= '<p><a href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html( $ad_title ) . '</a></p>';
		}
		$email_body .= '<p><a href="' . esc_url( get_permalink( $this->core->my_classifieds_page_id ) . '?messages' ) . '">' . __( 'Zur Nachrichtenübersicht', $this->core->text_domain ) . '</a></p>';

		wp_mail(
			$recipient->user_email,
			$email_subject,
			$email_body,
			array( 'Content-Type: text/html; charset=UTF-8' )
		);

		wp_send_json_success( array(
			'message'   => __( 'Nachricht gesendet!', $this->core->text_domain ),
			'thread_id' => $thread_id ?: $msg_id,
			'msg_id'    => $msg_id,
		) );
	}

	/**
	 * AJAX: Dashboard-Tab laden
	 */
	public function ajax_load_dashboard_tab() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Du musst eingeloggt sein.', $this->core->text_domain ) ) );
		}

		check_ajax_referer( 'cf_frontend_actions', 'nonce' );

		$tab   = sanitize_key( $_POST['tab'] ?? 'active' );
		$paged = absint( $_POST['paged'] ?? 1 );

		if ( ! in_array( $tab, array( 'active', 'favorites', 'saved', 'ended', 'messages' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungültiger Tab.', $this->core->text_domain ) ) );
		}

		$content = $this->dashboard->render_tab( $tab, $paged );

		wp_send_json_success( array( 'html' => $content, 'tab' => $tab ) );
	}

	/**
	 * AJAX: Konversation laden
	 */
	public function ajax_get_conversation() {
		check_ajax_referer( 'cf_send_message', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Nicht eingeloggt.', $this->core->text_domain ) ), 403 );
		}

		$thread_id = absint( $_POST['thread_id'] ?? 0 );
		$user_id   = get_current_user_id();

		if ( ! $thread_id ) {
			wp_send_json_error( array( 'message' => __( 'Konversation nicht gefunden.', $this->core->text_domain ) ) );
		}

		$thread_root = get_post( $thread_id );
		if ( ! $thread_root ) {
			wp_send_json_error( array( 'message' => __( 'Konversation nicht gefunden.', $this->core->text_domain ) ) );
		}

		$root_recipient = (int) get_post_meta( $thread_id, '_cf_msg_recipient', true );
		$root_sender    = (int) $thread_root->post_author;

		if ( $user_id !== $root_sender && $user_id !== $root_recipient ) {
			wp_send_json_error( array( 'message' => __( 'Zugriff verweigert.', $this->core->text_domain ) ), 403 );
		}

		$messages = get_posts( array(
			'post_type'      => 'cf_message',
			'posts_per_page' => 50,
			'post_status'    => 'publish',
			'meta_key'       => '_cf_msg_thread_id',
			'meta_value'     => $thread_id,
			'orderby'        => 'date',
			'order'          => 'ASC',
		) );

		$output = array();
		foreach ( $messages as $msg ) {
			$sender_data = get_userdata( $msg->post_author );
			$output[] = array(
				'id'      => $msg->ID,
				'sender'  => $sender_data ? esc_html( $sender_data->display_name ) : __( 'Unbekannt', $this->core->text_domain ),
				'avatar'  => get_avatar_url( $msg->post_author, array( 'size' => 40 ) ),
				'message' => esc_html( $msg->post_content ),
				'date'    => get_the_date( 'd.m.Y H:i', $msg ),
				'is_mine' => $msg->post_author == $user_id,
				'unread'  => ! (int) get_post_meta( $msg->ID, '_cf_msg_read_' . $user_id, true ),
			);
			update_post_meta( $msg->ID, '_cf_msg_read_' . $user_id, 1 );
		}

		$post_id    = (int) get_post_meta( $thread_id, '_cf_msg_post_id', true );
		$other_id   = ( $user_id === $root_sender ) ? $root_recipient : $root_sender;
		$other_user = get_userdata( $other_id );

		wp_send_json_success( array(
			'messages'     => $output,
			'thread_id'    => $thread_id,
			'other_user'   => $other_user ? esc_html( $other_user->display_name ) : '',
			'other_avatar' => $other_user ? get_avatar_url( $other_id, array( 'size' => 40 ) ) : '',
			'ad_title'     => $post_id ? esc_html( get_the_title( $post_id ) ) : '',
			'ad_url'       => $post_id ? esc_url( get_permalink( $post_id ) ) : '',
			'recipient_id' => $other_id,
		) );
	}

	/**
	 * AJAX: Nachrichten als gelesen markieren
	 */
	public function ajax_mark_messages_read() {
		check_ajax_referer( 'cf_send_message', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( null, 403 );
		}

		$thread_id = absint( $_POST['thread_id'] ?? 0 );
		$user_id   = get_current_user_id();

		if ( ! $thread_id ) {
			wp_send_json_error();
		}

		$paged = 1;
		do {
			$messages = get_posts( array(
				'post_type'              => 'cf_message',
				'posts_per_page'         => 200,
				'paged'                  => $paged,
				'post_status'            => 'publish',
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array( 'key' => '_cf_msg_thread_id', 'value' => $thread_id ),
					array( 'key' => '_cf_msg_recipient', 'value' => $user_id ),
				),
			) );

			foreach ( $messages as $mid ) {
				update_post_meta( $mid, '_cf_msg_read_' . $user_id, 1 );
			}

			$paged++;
		} while ( ! empty( $messages ) );

		wp_send_json_success();
	}

	/**
	 * AJAX: Featured aktivieren/deaktivieren
	 */
	public function ajax_toggle_featured() {
		check_ajax_referer( 'cf_frontend_actions', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Du musst eingeloggt sein.', $this->core->text_domain ) ), 403 );
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Ungültige Anzeige ID.', $this->core->text_domain ) ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== $this->core->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Anzeige nicht gefunden.', $this->core->text_domain ) ) );
		}

		// Check permissions
		if ( get_current_user_id() !== $post->post_author && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Berechtigung verweigert.', $this->core->text_domain ) ), 403 );
		}

		$options = $this->core->get_options( 'payments' );
		if ( empty( $options['enable_featured'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Featured ist nicht aktiviert.', $this->core->text_domain ) ) );
		}

		$is_featured = $this->core->is_featured( $post_id );
		$duration_days = ! empty( $options['featured_duration_days'] ) ? absint( $options['featured_duration_days'] ) : 0;

		if ( $is_featured ) {
			// Deactivate featured
			$this->core->unset_featured( $post_id );
			wp_send_json_success( array(
				'message' => __( 'Featured wurde deaktiviert.', $this->core->text_domain ),
				'is_featured' => false,
			) );
		} else {
			// Try to activate featured

			// Check if user has enough credits or funds
			$cost_type = ! empty( $options['featured_cost_type'] ) ? $options['featured_cost_type'] : 'credits';

			if ( $cost_type === 'credits' ) {
				$cost = ! empty( $options['featured_credit_cost'] ) ? absint( $options['featured_credit_cost'] ) : 50;
				$transactions = new CF_Transactions( get_current_user_id() );
				$user_credits = (int) $transactions->credits;

				if ( $user_credits < $cost ) {
					wp_send_json_error( array(
						'message' => sprintf(
							__( 'Du hast nicht genug Credits. Benötigt: %d, Du hast: %d.', $this->core->text_domain ),
							$cost,
							$user_credits
						),
					) );
				}

				// Deduct credits (logged by CF_Transactions magic setter)
				$transactions->credits = max( 0, $user_credits - $cost );
			} else {
				wp_send_json_error(
					array(
						'message' => __( 'Featured im Geld-Modus bitte ueber den Checkout kaufen. Dashboard-Sofortaktivierung ist nur fuer Credits verfuegbar.', $this->core->text_domain ),
					)
				);
			}

			// Activate featured
			$this->core->set_featured( $post_id, $duration_days );

			$featured_until = $this->core->get_featured_until( $post_id );
			$until_text = $featured_until ? date_i18n( 'd.m.Y', $featured_until ) : __( 'unbegrenzt', $this->core->text_domain );

			wp_send_json_success( array(
				'message' => sprintf( __( 'Featured aktiviert bis: %s', $this->core->text_domain ), $until_text ),
				'is_featured' => true,
				'featured_until' => $featured_until,
			) );
		}
	}
}
