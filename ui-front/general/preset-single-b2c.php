<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require CF_PLUGIN_DIR . 'ui-front/general/partials/single-bootstrap.php';
?>
<div class="cf-post cf-single-page cf-single-preset-b2c cf-single-b2c-layout">
	<div class="cf-b2c-grid">
		<main class="cf-b2c-main">
			<section class="cf-single-media-card">
				<div class="cf-single-media-stage">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php $thumbnail = get_the_post_thumbnail( $post->ID, array( 900, 900 ) ); ?>
					<?php else : ?>
						<?php $thumbnail = '<img title="no image" alt="no image" class="cf-no-image wp-post-image" src="' . $field_image . '">'; ?>
					<?php endif; ?>
					<?php if ( ! empty( $featured_image_url ) ) : ?>
						<a href="<?php echo esc_url( $featured_image_url ); ?>" class="cf-lightbox-trigger cf-single-featured-link" data-lightbox-group="classifieds-gallery" data-lightbox-caption="<?php echo esc_attr( get_the_title() ); ?>"><?php echo $thumbnail; ?></a>
					<?php else : ?>
						<?php echo $thumbnail; ?>
					<?php endif; ?>
				</div>
			</section>

			<section class="cf-single-section cf-single-description-card cf-b2c-description-near-image">
				<div class="cf-single-section-head">
					<h2><?php _e( 'Beschreibung', $this->text_domain ); ?></h2>
				</div>
				<div class="cf-single-description-body">
					<?php echo wp_kses( $content, cf_wp_kses_allowed_html() ); ?>
				</div>
				<?php require CF_PLUGIN_DIR . 'ui-front/general/partials/single-gallery-section.php'; ?>
			</section>

			<?php require CF_PLUGIN_DIR . 'ui-front/general/partials/single-contact-section.php'; ?>
			<?php if ( empty( $options['disable_contact_form'] ) && ! $open_contact_form ) : ?>
			<button type="button" class="button cf-btn-b2c-primary cf-contact-toggle" id="cf-contact-toggle"
			        onclick="document.getElementById('cf-contact-section').style.display='block'; this.style.display='none'; document.getElementById('cf-contact-section').scrollIntoView({behavior:'smooth',block:'nearest'});">
				<?php _e( 'Anbieter kontaktieren', $this->text_domain ); ?>
			</button>
			<?php endif; ?>
		</main>

		<aside class="cf-b2c-side">
			<section class="cf-single-summary-card">
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
					<div class="cf-single-fact"><dt><?php _e( 'Anbieter', $this->text_domain ); ?></dt><dd><?php echo the_author_classifieds_link(); ?></dd></div>
					<div class="cf-single-fact"><dt><?php _e( 'Veröffentlicht', $this->text_domain ); ?></dt><dd><?php echo esc_html( $published_date ); ?></dd></div>
					<div class="cf-single-fact"><dt><?php _e( 'Läuft aus', $this->text_domain ); ?></dt><dd><?php echo esc_html( $expiration_date ); ?></dd></div>
					<?php if ( '' !== $duration ) : ?><div class="cf-single-fact"><dt><?php _e( 'Laufzeit', $this->text_domain ); ?></dt><dd><?php echo esc_html( $duration ); ?></dd></div><?php endif; ?>
					<?php if ( '' !== $region_name ) : ?><div class="cf-single-fact"><dt><?php _e( 'Standort', $this->text_domain ); ?></dt><dd><?php echo esc_html( $region_name ); ?></dd></div><?php endif; ?>
				</dl>
				<div class="cf-quick-actions cf-single-actions cf-b2c-actions">
					<div class="cf-quick-actions-main">
						<?php if ( empty( $options['disable_contact_form'] ) ) : ?>
						<button type="button" class="button cf-btn-b2c-primary cf-cta-contact" onclick="classifieds.toggle_contact_form(); return false;"><?php _e( 'Kontakt', $this->text_domain ); ?></button>
						<?php endif; ?>
						<button type="button" class="button cf-btn-b2c-ghost cf-favorite-toggle <?php echo $is_favorite ? 'is-active' : ''; ?>" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
							<span class="cf-favorite-label-default"><?php _e( 'Merken', $this->text_domain ); ?></span>
							<span class="cf-favorite-label-active"><?php _e( 'Gemerkt', $this->text_domain ); ?></span>
						</button>
						<button type="button" class="button cf-btn-b2c-ghost cf-cta-share" data-copy-url="<?php echo esc_url( get_permalink() ); ?>"><?php _e( 'Teilen', $this->text_domain ); ?></button>
					</div>
				</div>
			</section>
			<?php require CF_PLUGIN_DIR . 'ui-front/general/partials/single-sidebar-stack.php'; ?>
		</aside>
	</div>
</div>

<?php require CF_PLUGIN_DIR . 'ui-front/general/partials/single-footer-navigation.php'; ?>

<?php if ( $single_show_sticky_actions ) : ?>
<div class="cf-sticky-mobile-actions">
	<?php if ( empty( $options['disable_contact_form'] ) ) : ?>
	<button type="button" class="button cf-btn-b2c-primary cf-cta-contact" onclick="classifieds.toggle_contact_form(); return false;"><?php _e( 'Kontakt', $this->text_domain ); ?></button>
	<?php endif; ?>
	<button type="button" class="button cf-btn-b2c-ghost cf-favorite-toggle <?php echo $is_favorite ? 'is-active' : ''; ?>" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
		<span class="cf-favorite-label-default"><?php _e( 'Merken', $this->text_domain ); ?></span>
		<span class="cf-favorite-label-active"><?php _e( 'Gemerkt', $this->text_domain ); ?></span>
	</button>
</div>
<?php endif; ?>

<div class="cf-lightbox" id="cf-lightbox" aria-hidden="true">
	<button type="button" class="cf-lightbox-close" aria-label="<?php esc_attr_e( 'Schliessen', $this->text_domain ); ?>">&times;</button>
	<button type="button" class="cf-lightbox-nav cf-lightbox-prev" aria-label="<?php esc_attr_e( 'Vorheriges Bild', $this->text_domain ); ?>">&#10094;</button>
	<div class="cf-lightbox-stage"><img src="" alt="" class="cf-lightbox-image" /><p class="cf-lightbox-caption"></p></div>
	<button type="button" class="cf-lightbox-nav cf-lightbox-next" aria-label="<?php esc_attr_e( 'Naechstes Bild', $this->text_domain ); ?>">&#10095;</button>
</div>
