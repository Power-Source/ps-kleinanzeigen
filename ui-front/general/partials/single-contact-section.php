<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! empty( $options['disable_contact_form'] ) ) {
	return;
}

global $current_user;
$is_logged_in = is_user_logged_in();
$ad_title = get_the_title();
$author_id = get_post_field( 'post_author', get_the_ID() );
$is_own_post = $is_logged_in && (int) $current_user->ID === (int) $author_id;
?>
<div class="cf-contact-section" id="cf-contact-section"<?php echo $open_contact_form ? '' : ' style="display:none;"'; ?>>
	<div class="cf-contact-card">
		<h3 class="cf-contact-title"><?php _e( 'Anbieter kontaktieren', $this->text_domain ); ?></h3>

		<?php if ( $is_own_post ) : ?>
			<p class="cf-notice"><?php _e( 'Das ist deine eigene Anzeige.', $this->text_domain ); ?></p>
		<?php elseif ( ! $is_logged_in ) : ?>
			<form method="post" action="#" class="cf-contact-form cf-contact-classic" id="cf-contact-form-classic">
				<div class="cf-form-row">
					<label for="cf-name"><?php _e( 'Dein Name', $this->text_domain ); ?></label>
					<input type="text" id="cf-name" name="name" value="<?php echo esc_attr( isset( $_POST['name'] ) ? $_POST['name'] : '' ); ?>" required autocomplete="name">
				</div>
				<div class="cf-form-row">
					<label for="cf-email"><?php _e( 'Deine E-Mail', $this->text_domain ); ?></label>
					<input type="email" id="cf-email" name="email" value="<?php echo esc_attr( isset( $_POST['email'] ) ? $_POST['email'] : '' ); ?>" required autocomplete="email">
				</div>
				<div class="cf-form-row">
					<label for="cf-subject"><?php _e( 'Betreff', $this->text_domain ); ?></label>
					<input type="text" id="cf-subject" name="subject" value="<?php echo esc_attr( isset( $_POST['subject'] ) ? $_POST['subject'] : sprintf( __( 'Anfrage zu: %s', $this->text_domain ), $ad_title ) ); ?>" required>
				</div>
				<div class="cf-form-row">
					<label for="cf-message"><?php _e( 'Deine Nachricht', $this->text_domain ); ?></label>
					<textarea id="cf-message" name="message" rows="5" required><?php echo esc_textarea( isset( $_POST['message'] ) ? $_POST['message'] : '' ); ?></textarea>
				</div>
				<div class="cf-form-row cf-captcha-row">
					<label for="cf_random_value"><?php _e( 'Sicherheitscode', $this->text_domain ); ?></label>
					<div class="cf-captcha-wrap">
						<img class="cf-captcha-img" src="<?php echo esc_url( admin_url( 'admin-ajax.php?action=cf-captcha' ) ); ?>" alt="Captcha" loading="lazy">
						<button type="button" class="cf-captcha-refresh" onclick="this.previousElementSibling.src=this.previousElementSibling.src+'?r='+Math.random()" title="<?php esc_attr_e( 'Neu laden', $this->text_domain ); ?>">&#x21BB;</button>
					</div>
					<input type="text" id="cf_random_value" name="cf_random_value" required placeholder="<?php esc_attr_e( 'Code eingeben', $this->text_domain ); ?>">
				</div>
				<div class="cf-form-actions">
					<?php wp_nonce_field( 'send_message' ); ?>
					<button type="submit" class="button cf-btn-primary" name="contact_form_send"><?php _e( 'Nachricht senden', $this->text_domain ); ?></button>
					<button type="button" class="button cf-btn-secondary" onclick="document.getElementById('cf-contact-section').style.display='none'"><?php _e( 'Abbrechen', $this->text_domain ); ?></button>
				</div>
			</form>
		<?php else : ?>
			<div class="cf-contact-logged-in">
				<div class="cf-contact-user-hint">
					<?php echo get_avatar( $current_user->ID, 32 ); ?>
					<span><?php printf( __( 'Senden als %s', $this->text_domain ), '<strong>' . esc_html( $current_user->display_name ) . '</strong>' ); ?></span>
				</div>
				<form class="cf-contact-form cf-contact-ajax" id="cf-contact-form-ajax"
				      data-post-id="<?php echo esc_attr( get_the_ID() ); ?>"
				      data-recipient-id="<?php echo esc_attr( $author_id ); ?>">
					<div class="cf-form-row">
						<label for="cf-subject-ajax"><?php _e( 'Betreff', $this->text_domain ); ?></label>
						<input type="text" id="cf-subject-ajax" name="subject" value="<?php echo esc_attr( sprintf( __( 'Anfrage zu: %s', $this->text_domain ), $ad_title ) ); ?>" required>
					</div>
					<div class="cf-form-row">
						<label for="cf-message-ajax"><?php _e( 'Deine Nachricht', $this->text_domain ); ?></label>
						<textarea id="cf-message-ajax" name="message" rows="5" required placeholder="<?php esc_attr_e( 'Wie kann dir der Anbieter helfen?', $this->text_domain ); ?>"></textarea>
					</div>
					<div class="cf-form-actions">
						<button type="submit" class="button cf-btn-primary cf-ajax-send"><?php _e( 'Nachricht senden', $this->text_domain ); ?></button>
						<button type="button" class="button cf-btn-secondary" onclick="document.getElementById('cf-contact-section').style.display='none'"><?php _e( 'Abbrechen', $this->text_domain ); ?></button>
					</div>
					<div class="cf-form-feedback" style="display:none;"></div>
				</form>
			</div>
		<?php endif; ?>
	</div>
</div>
