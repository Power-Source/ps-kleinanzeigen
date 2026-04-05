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

		if ( ! isset( $_POST['contact_form_send'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'send_message' ) ) {
			return;
		}

		$_POST = stripslashes_deep( $_POST );

		if ( ! isset( $_POST['name'] ) || '' == $_POST['name'] ) {
			return;
		}
		if ( ! isset( $_POST['email'] ) || '' == $_POST['email'] ) {
			return;
		}
		if ( ! isset( $_POST['subject'] ) || '' == $_POST['subject'] ) {
			return;
		}
		if ( ! isset( $_POST['message'] ) || '' == $_POST['message'] ) {
			return;
		}
		if ( ! $captcha || md5( strtoupper( $_POST['cf_random_value'] ) ) != $captcha ) {
			return;
		}

		global $post;
		$user_info = get_userdata( $post->post_author );
		$options   = $this->core->get_options( 'general' );

		$body       = nl2br( $this->replace_email_placeholders( $options['email_content'] ) );
		$tm_subject = $this->replace_email_placeholders( $options['email_subject'] );

		$to      = $user_info->user_email;
		$subject = $tm_subject;
		$message = $body;
		$headers = array();
		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'From: ' . $_POST['name'] . ' <' . $_POST['email'] . '>';
		$headers[] = 'Content-Type: text/html; charset="' . get_option( 'blog_charset' ) . '"';

		if ( isset( $options['cc_admin'] ) && $options['cc_admin'] == '1' ) {
			$headers[] = 'Cc: ' . get_bloginfo( 'admin_email' );
		}

		if ( isset( $options['cc_sender'] ) && $options['cc_sender'] == '1' ) {
			$headers[] = 'Cc: ' . $_POST['name'] . ' <' . $_POST['email'] . '>';
		}

		$sent = wp_mail( $to, $subject, $message, $headers ) ? '1' : '0';
		wp_redirect( get_permalink( $post->ID ) . '?sent=' . $sent );
		exit;
	}

	/**
	 * @param string $content
	 * @return string
	 */
	private function replace_email_placeholders( $content = '' ) {
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
							$_POST['name'],
							str_replace(
								'FROM_EMAIL',
								$_POST['email'],
								str_replace(
									'FROM_SUBJECT',
									$_POST['subject'],
									str_replace( 'FROM_MESSAGE', $_POST['message'], $content )
								)
							)
						)
					)
				)
			)
		);
	}
}
