<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CF_Contact_Form_Service {
	/** @var Classifieds_Core */
	private $core;

	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Handles the request for the contact form on the single{}.php template.
	 *
	 * @return void
	 */
	public function handle_contact_form_requests() {
		if ( get_post_type() != $this->core->post_type || ! is_single() ) {
			return;
		}

		$captcha = get_transient( CF_CAPTCHA . $_SERVER['REMOTE_ADDR'] );

		if ( ! isset( $_POST['contact_form_send'] ) || ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'send_message' ) ) {
			return;
		}

		// Sanitize inputs locally — never mutate $_POST, never trust raw values in headers.
		$name          = sanitize_text_field( wp_unslash( $_POST['name']           ?? '' ) );
		$email         = sanitize_email(      wp_unslash( $_POST['email']          ?? '' ) );
		$subject       = sanitize_text_field( wp_unslash( $_POST['subject']        ?? '' ) );
		$message       = sanitize_textarea_field( wp_unslash( $_POST['message']    ?? '' ) );
		$captcha_input = sanitize_text_field( wp_unslash( $_POST['cf_random_value'] ?? '' ) );

		// Header-injection guard: reject if any required field is empty or email is invalid.
		if ( '' === $name || '' === $subject || '' === $message ) {
			return;
		}
		if ( ! is_email( $email ) ) {
			return;
		}
		if ( ! $captcha || md5( strtoupper( $captcha_input ) ) !== $captcha ) {
			return;
		}

		global $post;
		$user_info = get_userdata( $post->post_author );
		$options   = $this->core->get_options( 'general' );

		$body       = nl2br( $this->replace_email_placeholders( $options['email_content'], $name, $email, $subject, $message ) );
		$tm_subject = $this->replace_email_placeholders( $options['email_subject'], $name, $email, $subject, $message );

		$to      = $user_info->user_email;
		$headers = array();
		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'From: ' . $name . ' <' . $email . '>';
		$headers[] = 'Content-Type: text/html; charset="' . get_option( 'blog_charset' ) . '"';

		if ( isset( $options['cc_admin'] ) && $options['cc_admin'] == '1' ) {
			$headers[] = 'Cc: ' . get_bloginfo( 'admin_email' );
		}

		if ( isset( $options['cc_sender'] ) && $options['cc_sender'] == '1' ) {
			$headers[] = 'Cc: ' . $name . ' <' . $email . '>';
		}

		$sent = wp_mail( $to, $tm_subject, $body, $headers ) ? '1' : '0';
		wp_safe_redirect( get_permalink( $post->ID ) . '?sent=' . $sent );
		exit;
	}

	/**
	 * @param string $content
	 * @param string $name    Sanitized sender name.
	 * @param string $email   Sanitized sender email.
	 * @param string $subject Sanitized subject line.
	 * @param string $message Sanitized message body.
	 * @return string
	 */
	private function replace_email_placeholders( $content = '', $name = '', $email = '', $subject = '', $message = '' ) {
		global $post;

		$user_info = get_userdata( $post->post_author );

		return str_replace(
			'SITE_NAME',
			get_bloginfo( 'name' ),
			str_replace(
				'POST_TITLE',
				$post->post_title,
				str_replace(
					'POST_LINK',
					make_clickable( get_permalink( $post->ID ) ),
					str_replace(
						'TO_NAME',
						$user_info->nicename,
						str_replace(
							'FROM_NAME',
							$name,
							str_replace(
								'FROM_EMAIL',
								$email,
								str_replace(
									'FROM_SUBJECT',
									$subject,
									str_replace( 'FROM_MESSAGE', $message, $content )
								)
							)
						)
					)
				)
			)
		);
	}
}
