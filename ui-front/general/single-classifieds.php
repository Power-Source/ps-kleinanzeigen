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

$single_show_gallery = ! isset( $frontend_options['single_show_gallery'] ) || 1 === (int) $frontend_options['single_show_gallery'];
$single_show_trust_block = ! isset( $frontend_options['single_show_trust_block'] ) || 1 === (int) $frontend_options['single_show_trust_block'];
$single_show_seller_card = ! isset( $frontend_options['single_show_seller_card'] ) || 1 === (int) $frontend_options['single_show_seller_card'];
$single_show_sticky_actions = ! isset( $frontend_options['single_show_sticky_actions'] ) || 1 === (int) $frontend_options['single_show_sticky_actions'];
$single_show_reserved_badge = ! isset( $frontend_options['single_show_reserved_badge'] ) || 1 === (int) $frontend_options['single_show_reserved_badge'];
$is_reserved = method_exists( $this, 'is_reserved_post' ) ? $this->is_reserved_post( $post->ID ) : ( '1' === (string) get_post_meta( $post->ID, '_cf_reserved', true ) );

/**
* $content is already filled with the database html.
* This template just adds classifieds specfic code around it.
*/
?>

<script type="text/javascript" src="<?php echo $this->plugin_url . 'ui-front/js/ui-front.js'; ?>" >
</script>

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
<div class="cf-post">

	<div class="cf-image">
		<?php
		if(has_post_thumbnail()){
			$thumbnail = get_the_post_thumbnail( $post->ID, array( 300, 300 ) );
		} else {
			$thumbnail = '<img title="no image" alt="no image" class="cf-no-image wp-post-image" src="' . $field_image . '">';
		}
		?>
		<?php if ( ! empty( $featured_image_url ) ) : ?>
			<a href="<?php echo esc_url( $featured_image_url ); ?>" class="cf-lightbox-trigger" data-lightbox-group="classifieds-gallery" data-lightbox-caption="<?php echo esc_attr( get_the_title() ); ?>"><?php echo $thumbnail; ?></a>
		<?php else : ?>
			<?php echo $thumbnail; ?>
		<?php endif; ?>
	</div>
	<?php if ( $single_show_gallery && ! empty( $gallery_ids ) ) : ?>
	<div class="cf-gallery-grid">
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
	<div class="clear"></div>
	<div class="cf-ad-info">
		<table>
			<tr>
				<th><?php _e( 'Angeboten von', $this->text_domain ); ?></th>
				<td>
					<?php echo the_author_classifieds_link(); ?>
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Kategorien', $this->text_domain ); ?></th>
				<td>
					<?php $taxonomies = get_object_taxonomies( 'classifieds', 'names' ); ?>
					<?php foreach ( $taxonomies as $taxonomy ): ?>
					<?php echo get_the_term_list( $post->ID, $taxonomy, '', ', ', '' ) . ' '; ?>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Kategorisiert in', $this->text_domain ); ?></th>
				<td><?php the_date(); ?></td>
			</tr>
			<tr>
				<th><?php _e( 'Läuft aus am', $this->text_domain ); ?></th>
				<td><?php if ( class_exists('Classifieds_Core') ) echo Classifieds_Core::get_expiration_date( get_the_ID() ); ?></td>
			</tr>
		</table>
		<div class="clear"></div>
		<div class="cf-custom-block">
			<table>
				<?php if ( '' !== $duration ): ?>
				<tr>
					<th><?php _e( 'Laufzeit', $this->text_domain ); ?></th>
					<td><?php echo esc_html( $duration ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( '' !== $cost_display ): ?>
				<tr>
					<th><?php _e( 'Preis', $this->text_domain ); ?></th>
					<td><?php echo esc_html( $cost_display ); ?></td>
				</tr>
				<?php endif; ?>
			</table>
		</div>
	</div>

	<div class="cf-quick-actions">
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
			<?php if ( $single_show_reserved_badge && $is_reserved ) : ?>
				<span class="cf-meta-chip cf-meta-chip-reserved"><?php _e( 'Reserviert', $this->text_domain ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $cost_display ) : ?>
				<span class="cf-meta-chip"><?php _e( 'Preis:', $this->text_domain ); ?> <?php echo esc_html( $cost_display ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $duration ) : ?>
				<span class="cf-meta-chip"><?php _e( 'Laufzeit:', $this->text_domain ); ?> <?php echo esc_html( $duration ); ?></span>
			<?php endif; ?>
		</div>
	</div>
	<div class="clear"></div>

	<?php
	$trust_block_content = '';
	if ( isset( $frontend_options['trust_block_content'] ) && '' !== trim( $frontend_options['trust_block_content'] ) ) {
		$trust_block_content = trim( $frontend_options['trust_block_content'] );
	} elseif ( isset( $options['trust_block_content'] ) ) {
		$trust_block_content = trim( $options['trust_block_content'] );
	}
	?>
	<?php if ( $single_show_trust_block || $single_show_seller_card ) : ?>
	<div class="cf-trust-layout">
		<?php if ( $single_show_trust_block && ( '' !== $trust_block_content || '' !== $region_name ) ) : ?>
		<div class="cf-trust-card">
			<?php if ( '' !== $trust_block_content ) : ?>
			<div class="cf-trust-content"><?php echo wp_kses_post( wpautop( $trust_block_content ) ); ?></div>
			<?php endif; ?>
			<?php if ( '' !== $region_name ) : ?>
			<p class="cf-trust-location"><strong><?php _e( 'Standort:', $this->text_domain ); ?></strong> <?php echo esc_html( $region_name ); ?></p>
			<?php endif; ?>
		</div>
		<?php endif; ?>
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
				<a class="button cf-card-secondary" href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>"><?php _e( 'Alle Anzeigen ansehen', $this->text_domain ); ?></a>
			</p>
		</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<?php if( empty( $options['disable_contact_form'] ) ): ?>
	<form method="post" action="#" class="contact-user-btn action-form" id="action-form">
		<input type="submit" name="contact_user" value="<?php _e('Kontaktiere den Anbieter', $this->text_domain ); ?>" onclick="classifieds.toggle_contact_form(); return false;" />
	</form>
	<div class="clear"></div>

	<form method="post" action="#" class="standard-form base cf-contact-form" id="confirm-form"<?php echo $open_contact_form ? ' data-open-on-load="1"' : ''; ?>>
		<?php
		global $current_user;

		$name   = ( isset( $current_user->display_name ) && '' != $current_user->display_name ) ? $current_user->display_name :
		( ( isset( $current_user->first_name ) && '' != $current_user->first_name ) ? $current_user->first_name : '' );
		$email  = ( isset( $current_user->user_email ) && '' != $current_user->user_email ) ? $current_user->user_email : '';
		?>
		<div class="editfield">
			<label for="name"><?php _e( 'Name', $this->text_domain ); ?> (<?php _e( 'erforderlich', $this->text_domain ); ?>)</label>
			<input type="text" id="name" name ="name" value="<?php echo ( isset( $_POST['name'] ) ) ? $_POST['name'] : $name; ?>" />
			<p class="description"><?php _e( 'Gib Deinen vollständigen Namen ein.', $this->text_domain ); ?></p>
		</div>
		<div class="editfield">
			<label for="email"><?php _e( 'Email', $this->text_domain ); ?> (<?php _e( 'erforderlich', $this->text_domain ); ?>)</label>
			<input type="text" id="email" name ="email" value="<?php echo ( isset( $_POST['email'] ) ) ? $_POST['email'] : $email; ?>" />
			<p class="description"><?php _e( 'Gib hier eine gültige E-Mail-Adresse ein.', $this->text_domain ); ?></p>
		</div>
		<div class="editfield">
			<label for="subject"><?php _e( 'Betreff', $this->text_domain ); ?> (<?php _e( 'erforderlich', $this->text_domain ); ?>)</label>
			<input type="text" id="subject" name ="subject" value="<?php echo ( isset( $_POST['subject'] ) ) ? $_POST['subject'] : ''; ?>" />
			<p class="description"><?php _e( 'Gib den Betreff Deiner Anfrage ein.', $this->text_domain ); ?></p>
		</div>
		<div class="editfield">
			<label for="message"><?php _e( 'Nachricht', $this->text_domain ); ?> (<?php _e( 'erforderlich', $this->text_domain ); ?>)</label>
			<textarea id="message" name="message"><?php echo ( isset( $_POST['message'] ) ) ? $_POST['message'] : ''; ?></textarea>
			<p class="description"><?php _e( 'Gib den Grund Deiner Anfrage ein.', $this->text_domain ); ?></p>
		</div>

		<div class="editfield">
			<label for="cf_random_value"><?php _e( 'Sicherheitsbild', $this->text_domain ); ?> (<?php _e( 'erforderlich', $this->text_domain ); ?>)</label>
			<img class="captcha" src="<?php echo admin_url('admin-ajax.php?action=cf-captcha');?>" />
			<input type="text" id="cf_random_value" name ="cf_random_value" value="" size="8" />
			<p class="description"><?php _e( 'Gib die Zeichen aus dem Bild ein.', $this->text_domain ); ?></p>
		</div>

		<div class="submit">
			<p>
				<?php wp_nonce_field( 'send_message' ); ?>
				<input type="submit" class="button confirm" value="<?php _e( 'Senden', $this->text_domain ); ?>" name="contact_form_send" />
				<input type="submit" class="button cancel"  value="<?php _e( 'Abbrechen', $this->text_domain ); ?>" onclick="classifieds.cancel_contact_form(); return false;" />
			</p>
		</div>
	</form>

	<?php endif; ?>
<?php
//print_r( session_get_cookie_params()  );

?>
	<div class="clear"></div>

	<table class="cf-description">
		<thead>
			<tr>
				<th><?php _e( 'Beschreibung', $this->text_domain ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					<?php
					//$content is already filled with the database text. This just add classified specfic code around it.
					echo wp_kses($content, cf_wp_kses_allowed_html());
					?>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<?php if ( $single_show_sticky_actions ) : ?>
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
