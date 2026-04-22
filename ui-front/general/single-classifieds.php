<?php
/**
* The Template for displaying all single classifieds posts.
* You can override this file in your active theme.
*
* @package Classifieds
* @subpackage UI Front
* @since Classifieds 2.0
*/

global $post, $wp_query;
$options = $this->get_options( 'general' );
$frontend_options = $this->get_options( 'frontend' );
$field_image = (empty($options['field_image_def'])) ? $this->plugin_url . 'ui-front/general/images/blank.gif' : $options['field_image_def'];

$duration = get_post_meta( $post->ID, '_cf_duration', true );
$cost     = get_post_meta( $post->ID, '_cf_cost', true );
$cost_display = is_numeric( $cost ) ? number_format_i18n( (float) $cost, 2 ) : $cost;
$gallery_ids = get_post_meta( $post->ID, '_cf_gallery_ids', true );
if ( ! is_array( $gallery_ids ) ) {
	$gallery_ids = array();
}
$featured_image_url = has_post_thumbnail() ? wp_get_attachment_image_url( get_post_thumbnail_id( $post->ID ), 'large' ) : '';
$is_favorite = method_exists( $this, 'is_favorite_post' ) ? $this->is_favorite_post( $post->ID ) : false;
$author_id = (int) $post->post_author;
$author_display_name = get_the_author_meta( 'display_name', $author_id );
$author_registered_raw = get_the_author_meta( 'user_registered', $author_id );
$author_registered = ! empty( $author_registered_raw ) ? date_i18n( get_option( 'date_format' ), strtotime( $author_registered_raw ) ) : '';
$author_active_ads = count_user_posts( $author_id, 'classifieds', true );
$region_terms = get_the_terms( $post->ID, 'kleinanzeigen-region' );
$region_name = ( ! is_wp_error( $region_terms ) && ! empty( $region_terms ) ) ? implode( ', ', wp_list_pluck( $region_terms, 'name' ) ) : '';
$maps_options = (array) $this->get_options( 'maps' );
$show_single_region_map = ! isset( $maps_options['maps_show_single_region_map'] ) || 1 === (int) $maps_options['maps_show_single_region_map'];
$single_region_map_label = isset( $maps_options['maps_single_region_map_label'] ) && '' !== trim( (string) $maps_options['maps_single_region_map_label'] )
	? (string) $maps_options['maps_single_region_map_label']
	: __( 'Region auf der Karte', $this->text_domain );
$single_region_map_html = '';
if ( $show_single_region_map && ! is_wp_error( $region_terms ) && ! empty( $region_terms ) && shortcode_exists( 'cf_regions_map' ) ) {
	$region_ids = array_values( array_unique( array_filter( array_map( 'absint', wp_list_pluck( $region_terms, 'term_id' ) ) ) ) );
	if ( ! empty( $region_ids ) ) {
		$inline_map_html = do_shortcode( '[cf_regions_map regions="' . implode( ',', $region_ids ) . '"]' );
		if ( '' !== trim( (string) $inline_map_html ) ) {
			$single_region_map_html = '<p class="cf-single-region-map-title">' . esc_html( $single_region_map_label ) . '</p>' . $inline_map_html;
		}
	}
}

$single_show_gallery = ! isset( $frontend_options['single_show_gallery'] ) || 1 === (int) $frontend_options['single_show_gallery'];
$single_show_trust_block = ! isset( $frontend_options['single_show_trust_block'] ) || 1 === (int) $frontend_options['single_show_trust_block'];
$single_show_seller_card = ! isset( $frontend_options['single_show_seller_card'] ) || 1 === (int) $frontend_options['single_show_seller_card'];
$single_show_sticky_actions = isset( $frontend_options['single_show_sticky_actions'] ) && 1 === (int) $frontend_options['single_show_sticky_actions'];
$show_sticky_mobile_actions = $single_show_sticky_actions && is_user_logged_in();
$single_show_reserved_badge = ! isset( $frontend_options['single_show_reserved_badge'] ) || 1 === (int) $frontend_options['single_show_reserved_badge'];
$is_reserved = method_exists( $this, 'is_reserved_post' ) ? $this->is_reserved_post( $post->ID ) : ( '1' === (string) get_post_meta( $post->ID, '_cf_reserved', true ) );
$is_featured = method_exists( $this, 'is_featured' ) ? $this->is_featured( $post->ID ) : ( '1' === (string) get_post_meta( $post->ID, '_cf_is_featured', true ) );
$template_preset = isset( $GLOBALS['cf_frontend_template_preset'] ) ? sanitize_key( (string) $GLOBALS['cf_frontend_template_preset'] ) : '';
if ( ! in_array( $template_preset, array( 'b2c', 'premium', 'community' ), true ) ) {
	$template_preset = '';
}
$published_date = get_the_date();
$expiration_date = '';

global $Classifieds_Core;
if ( is_object( $Classifieds_Core ) && method_exists( $Classifieds_Core, 'get_expiration_date' ) ) {
	$expiration_date = $Classifieds_Core->get_expiration_date( get_the_ID() );
}

/**
* $content is already filled with the database html.
* This template just adds classifieds specfic code around it.
*/
?>

<?php $open_contact_form = isset( $_GET['cf_contact'] ) && '1' === wp_unslash( $_GET['cf_contact'] ); ?>

<?php if ( isset( $_POST['_wpnonce'] ) ): ?>
<br clear="all" />
<div id="cf-message-error">
	<?php _e( "Das Senden der Nachricht ist fehlgeschlagen: Du hast im Kontaktformular nicht alle erforderlichen Felder korrekt ausgefüllt!", $this->text_domain ); ?>
</div>
<br clear="all" />

<?php elseif ( isset( $_GET['sent'] ) && 1 == $_GET['sent'] ): ?>
<br clear="all" />
<div id="cf-message">
	<?php _e( 'Nachricht wird gesendet!', $this->text_domain ); ?>
</div>
<br clear="all" />

<?php elseif ( isset( $_GET['sent'] ) && 0 == $_GET['sent'] ): ?>
<br clear="all" />
<div id="cf-message-error">
	<?php _e( 'Der E-Mail-Dienst antwortet nicht!', $this->text_domain ); ?>
</div>
<br clear="all" />
<?php endif; ?>
<div class="cf-post cf-single-page<?php echo '' !== $template_preset ? ' cf-single-preset-' . esc_attr( $template_preset ) : ''; ?>">
	<section class="cf-single-hero">
		<div class="cf-single-media-card">
			<div class="cf-single-media-stage">
				<?php
				if(has_post_thumbnail()){
					$thumbnail = get_the_post_thumbnail( $post->ID, array( 900, 900 ) );
				} else {
					$thumbnail = '<img title="no image" alt="no image" class="cf-no-image wp-post-image" src="' . $field_image . '">';
				}
				?>
				<?php if ( ! empty( $featured_image_url ) ) : ?>
					<a href="<?php echo esc_url( $featured_image_url ); ?>" class="cf-lightbox-trigger cf-single-featured-link" data-lightbox-group="classifieds-gallery" data-lightbox-caption="<?php echo esc_attr( get_the_title() ); ?>"><?php echo $thumbnail; ?></a>
				<?php else : ?>
					<?php echo $thumbnail; ?>
				<?php endif; ?>
			</div>
			<?php if ( $single_show_gallery && ! empty( $gallery_ids ) ) : ?>
			<div class="cf-gallery-grid cf-single-gallery-grid">
				<?php foreach ( $gallery_ids as $gallery_id ) : ?>
					<?php $gallery_image = wp_get_attachment_image_src( (int) $gallery_id, 'thumbnail' ); ?>
					<?php if ( ! empty( $gallery_image[0] ) ) : ?>
						<a class="cf-gallery-item cf-lightbox-trigger" href="<?php echo esc_url( wp_get_attachment_url( (int) $gallery_id ) ); ?>" data-lightbox-group="classifieds-gallery" data-lightbox-caption="<?php echo esc_attr( get_the_title() ); ?>">
							<img src="<?php echo esc_url( $gallery_image[0] ); ?>" alt="<?php esc_attr_e( 'Galeriebild', $this->text_domain ); ?>" />
						</a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>

		<div class="cf-single-summary-card">
			<div class="cf-single-summary-head">
				<?php if ( $is_featured ) : ?>
					<span class="cf-status-badge is-featured"><?php _e( 'Featured', $this->text_domain ); ?></span>
				<?php endif; ?>
				<?php if ( $single_show_reserved_badge && $is_reserved ) : ?>
					<span class="cf-status-badge is-reserved"><?php _e( 'Reserviert', $this->text_domain ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $cost_display ) : ?>
					<div class="cf-single-price"><?php echo esc_html( $cost_display ); ?></div>
				<?php endif; ?>
			</div>

			<dl class="cf-single-facts">
				<div class="cf-single-fact">
					<dt><?php _e( 'Angeboten von', $this->text_domain ); ?></dt>
					<dd><?php echo the_author_classifieds_link(); ?></dd>
				</div>
				<div class="cf-single-fact">
					<dt><?php _e( 'Kategorien', $this->text_domain ); ?></dt>
					<dd>
						<?php $taxonomies = get_object_taxonomies( 'classifieds', 'names' ); ?>
						<?php foreach ( $taxonomies as $taxonomy ) : ?>
							<?php echo get_the_term_list( $post->ID, $taxonomy, '', ', ', '' ) . ' '; ?>
						<?php endforeach; ?>
					</dd>
				</div>
				<div class="cf-single-fact">
					<dt><?php _e( 'Veröffentlicht', $this->text_domain ); ?></dt>
					<dd><?php echo esc_html( $published_date ); ?></dd>
				</div>
				<div class="cf-single-fact">
					<dt><?php _e( 'Läuft aus am', $this->text_domain ); ?></dt>
					<dd><?php echo esc_html( $expiration_date ); ?></dd>
				</div>
				<?php if ( '' !== $duration ) : ?>
				<div class="cf-single-fact">
					<dt><?php _e( 'Laufzeit', $this->text_domain ); ?></dt>
					<dd><?php echo esc_html( $duration ); ?></dd>
				</div>
				<?php endif; ?>
				<?php if ( '' !== $region_name ) : ?>
				<div class="cf-single-fact">
					<dt><?php _e( 'Standort', $this->text_domain ); ?></dt>
					<dd><?php echo esc_html( $region_name ); ?></dd>
				</div>
				<?php endif; ?>
			</dl>
			<?php if ( '' !== $single_region_map_html ) : ?>
			<div class="cf-single-region-map"><?php echo $single_region_map_html; ?></div>
			<?php endif; ?>

			<div class="cf-quick-actions cf-single-actions">
				<div class="cf-quick-actions-main">
					<?php if ( empty( $options['disable_contact_form'] ) ) : ?>
					<button type="button" class="button button-primary cf-cta-contact" onclick="classifieds.toggle_contact_form(); return false;"><?php _e( 'Jetzt Anbieter kontaktieren', $this->text_domain ); ?></button>
					<?php endif; ?>
					<button type="button" class="button cf-favorite-toggle <?php echo $is_favorite ? 'is-active' : ''; ?>" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
						<span class="cf-favorite-label-default"><?php _e( 'Merken', $this->text_domain ); ?></span>
						<span class="cf-favorite-label-active"><?php _e( 'Gemerkt', $this->text_domain ); ?></span>
					</button>
					<button type="button" class="button cf-cta-share" data-copy-url="<?php echo esc_url( get_permalink() ); ?>"><?php _e( 'Link teilen', $this->text_domain ); ?></button>
				</div>
				<div class="cf-quick-meta">
					<?php if ( '' !== $cost_display ) : ?>
						<span class="cf-meta-chip"><?php _e( 'Preis:', $this->text_domain ); ?> <?php echo esc_html( $cost_display ); ?></span>
					<?php endif; ?>
					<?php if ( '' !== $duration ) : ?>
						<span class="cf-meta-chip"><?php _e( 'Laufzeit:', $this->text_domain ); ?> <?php echo esc_html( $duration ); ?></span>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>

	<?php
	$trust_block_content = '';
	if ( isset( $frontend_options['trust_block_content'] ) && '' !== trim( $frontend_options['trust_block_content'] ) ) {
		$trust_block_content = trim( $frontend_options['trust_block_content'] );
	} elseif ( isset( $options['trust_block_content'] ) ) {
		$trust_block_content = trim( $options['trust_block_content'] );
	}
	?>
	<div class="cf-single-content-grid">
		<div class="cf-single-main">
			<section class="cf-single-section cf-single-description-card">
				<div class="cf-single-section-head">
					<h2><?php _e( 'Beschreibung', $this->text_domain ); ?></h2>
				</div>
				<div class="cf-single-description-body">
					<?php
					//$content is already filled with the database text. This just add classified specfic code around it.
					echo wp_kses($content, cf_wp_kses_allowed_html());
					?>
				</div>
			</section>

	<?php if ( empty( $options['disable_contact_form'] ) ) :
		global $current_user;
		$is_logged_in   = is_user_logged_in();
		$c_name         = $is_logged_in && $current_user->display_name ? $current_user->display_name : '';
		$c_email        = $is_logged_in && $current_user->user_email ? $current_user->user_email : '';
		$ad_title       = get_the_title();
		$author_id      = get_post_field( 'post_author', get_the_ID() );
		$is_own_post    = $is_logged_in && (int) $current_user->ID === (int) $author_id;
	?>
	<div class="cf-contact-section" id="cf-contact-section"<?php echo $open_contact_form ? '' : ' style="display:none;"'; ?>>
		<div class="cf-contact-card">
			<h3 class="cf-contact-title"><?php _e( 'Anbieter kontaktieren', $this->text_domain ); ?></h3>

			<?php if ( $is_own_post ) : ?>
				<p class="cf-notice"><?php _e( 'Das ist deine eigene Anzeige.', $this->text_domain ); ?></p>
			<?php elseif ( ! $is_logged_in ) : ?>
				<?php /* Nicht eingeloggt: klassisches Formular mit Captcha */ ?>
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
				<?php /* Eingeloggt: AJAX-Formular, kein Captcha nötig */ ?>
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

	<?php if ( ! $open_contact_form ) : ?>
	<button type="button" class="button cf-btn-primary cf-contact-toggle" id="cf-contact-toggle"
	        onclick="document.getElementById('cf-contact-section').style.display='block'; this.style.display='none'; document.getElementById('cf-contact-section').scrollIntoView({behavior:'smooth',block:'nearest'});">
		<?php _e( 'Anbieter kontaktieren', $this->text_domain ); ?>
	</button>
	<?php endif; ?>

	<?php endif; ?>
		</div>

		<aside class="cf-single-sidebar">
			<div class="cf-single-sidebar-stack">
				<?php if ( $single_show_seller_card ) : ?>
				<div class="cf-seller-card">
					<h3><?php _e( 'Verkäuferprofil', $this->text_domain ); ?></h3>
					<p class="cf-seller-name"><?php echo esc_html( $author_display_name ); ?></p>
					<div class="cf-seller-meta">
						<span class="cf-meta-chip"><?php echo esc_html( sprintf( _n( '%d aktive Anzeige', '%d aktive Anzeigen', (int) $author_active_ads, $this->text_domain ), (int) $author_active_ads ) ); ?></span>
						<?php if ( '' !== $author_registered ) : ?>
							<span class="cf-meta-chip"><?php _e( 'Mitglied seit:', $this->text_domain ); ?> <?php echo esc_html( $author_registered ); ?></span>
						<?php endif; ?>
					</div>
					<p class="cf-seller-actions">
						<a class="button cf-card-secondary" href="<?php echo esc_url( home_url( '/cf-author/' . get_the_author_meta( 'user_login', $author_id ) . '/' ) ); ?>"><?php _e( 'Alle Anzeigen ansehen', $this->text_domain ); ?></a>
					</p>
				</div>
				<?php endif; ?>
				<?php if ( $single_show_trust_block && ( '' !== $trust_block_content || '' !== $region_name ) ) : ?>
				<div class="cf-trust-card">
					<h3><?php _e( 'Hinweise', $this->text_domain ); ?></h3>
					<?php if ( '' !== $trust_block_content ) : ?>
					<div class="cf-trust-content"><?php echo wp_kses_post( wpautop( $trust_block_content ) ); ?></div>
					<?php endif; ?>
					<?php if ( '' !== $region_name ) : ?>
					<p class="cf-trust-location"><strong><?php _e( 'Standort:', $this->text_domain ); ?></strong> <?php echo esc_html( $region_name ); ?></p>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			</div>
		</aside>
	</div>
</div>

<?php require CF_PLUGIN_DIR . 'ui-front/general/partials/single-footer-navigation.php'; ?>

<?php if ( $show_sticky_mobile_actions ) : ?>
<div class="cf-sticky-mobile-actions">
	<?php if ( empty( $options['disable_contact_form'] ) ) : ?>
	<button type="button" class="button button-primary cf-cta-contact" onclick="classifieds.toggle_contact_form(); return false;"><?php _e( 'Kontakt', $this->text_domain ); ?></button>
	<?php endif; ?>
	<button type="button" class="button cf-favorite-toggle <?php echo $is_favorite ? 'is-active' : ''; ?>" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
		<span class="cf-favorite-label-default"><?php _e( 'Merken', $this->text_domain ); ?></span>
		<span class="cf-favorite-label-active"><?php _e( 'Gemerkt', $this->text_domain ); ?></span>
	</button>
	<button type="button" class="button cf-cta-share" data-copy-url="<?php echo esc_url( get_permalink() ); ?>"><?php _e( 'Teilen', $this->text_domain ); ?></button>
</div>
<?php endif; ?>

<div class="cf-lightbox" id="cf-lightbox" aria-hidden="true">
	<button type="button" class="cf-lightbox-close" aria-label="<?php esc_attr_e( 'Schliessen', $this->text_domain ); ?>">&times;</button>
	<button type="button" class="cf-lightbox-nav cf-lightbox-prev" aria-label="<?php esc_attr_e( 'Vorheriges Bild', $this->text_domain ); ?>">&#10094;</button>
	<div class="cf-lightbox-stage">
		<img src="" alt="" class="cf-lightbox-image" />
		<p class="cf-lightbox-caption"></p>
	</div>
	<button type="button" class="cf-lightbox-nav cf-lightbox-next" aria-label="<?php esc_attr_e( 'Naechstes Bild', $this->text_domain ); ?>">&#10095;</button>
</div>
