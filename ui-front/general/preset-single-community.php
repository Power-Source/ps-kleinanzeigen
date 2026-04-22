<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require CF_PLUGIN_DIR . 'ui-front/general/partials/single-bootstrap.php';
?>
<div class="cf-post cf-single-page cf-single-preset-community cf-single-community-layout">
	<section class="cf-community-topline cf-single-section">
		<div class="cf-community-title-wrap">
			<h1 class="cf-community-title"><?php echo esc_html( get_the_title() ); ?></h1>
			<div class="cf-community-meta-inline">
				<?php if ( $is_featured ) : ?><span class="cf-status-badge is-featured"><?php _e( 'Featured', $this->text_domain ); ?></span><?php endif; ?>
				<span class="cf-meta-chip"><?php _e( 'Von', $this->text_domain ); ?>: <?php echo wp_kses_post( the_author_classifieds_link() ); ?></span>
				<?php if ( '' !== $cost_display ) : ?><span class="cf-meta-chip"><?php _e( 'Preis', $this->text_domain ); ?>: <?php echo esc_html( $cost_display ); ?></span><?php endif; ?>
				<?php if ( '' !== $region_name ) : ?><span class="cf-meta-chip"><?php echo esc_html( $region_name ); ?></span><?php endif; ?>
			</div>
		</div>
		<div class="cf-community-actions">
			<?php if ( empty( $options['disable_contact_form'] ) ) : ?><button type="button" class="button cf-btn-community-primary cf-cta-contact" onclick="classifieds.toggle_contact_form(); return false;"><?php _e( 'Kontakt', $this->text_domain ); ?></button><?php endif; ?>
			<button type="button" class="button cf-btn-community-secondary cf-favorite-toggle <?php echo $is_favorite ? 'is-active' : ''; ?>" data-post-id="<?php echo esc_attr( $post->ID ); ?>"><?php _e( 'Merken', $this->text_domain ); ?></button>
		</div>
	</section>

	<?php if ( '' !== $single_region_map_html ) : ?>
	<section class="cf-community-region-map cf-single-section">
		<div class="cf-single-region-map"><?php echo $single_region_map_html; ?></div>
	</section>
	<?php endif; ?>

	<section class="cf-community-media-wrap cf-single-section">
		<div class="cf-single-media-stage">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php $thumbnail = get_the_post_thumbnail( $post->ID, array( 1000, 700 ) ); ?>
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

	<section class="cf-community-description cf-single-section">
		<div class="cf-single-section-head"><h2><?php _e( 'Beschreibung', $this->text_domain ); ?></h2></div>
		<div class="cf-single-description-body"><?php echo wp_kses( $content, cf_wp_kses_allowed_html() ); ?></div>
		<?php require CF_PLUGIN_DIR . 'ui-front/general/partials/single-gallery-section.php'; ?>
	</section>

	<section class="cf-community-facts cf-single-section">
		<dl class="cf-single-facts">
			<div class="cf-single-fact"><dt><?php _e( 'Veröffentlicht', $this->text_domain ); ?></dt><dd><?php echo esc_html( $published_date ); ?></dd></div>
			<div class="cf-single-fact"><dt><?php _e( 'Läuft aus', $this->text_domain ); ?></dt><dd><?php echo esc_html( $expiration_date ); ?></dd></div>
			<?php if ( '' !== $duration ) : ?><div class="cf-single-fact"><dt><?php _e( 'Laufzeit', $this->text_domain ); ?></dt><dd><?php echo esc_html( $duration ); ?></dd></div><?php endif; ?>
		</dl>
	</section>

	<?php require CF_PLUGIN_DIR . 'ui-front/general/partials/single-contact-section.php'; ?>
	<?php if ( empty( $options['disable_contact_form'] ) && ! $open_contact_form ) : ?>
	<button type="button" class="button cf-btn-community-primary cf-contact-toggle" id="cf-contact-toggle" onclick="document.getElementById('cf-contact-section').style.display='block'; this.style.display='none'; document.getElementById('cf-contact-section').scrollIntoView({behavior:'smooth',block:'nearest'});">
		<?php _e( 'Anbieter kontaktieren', $this->text_domain ); ?>
	</button>
	<?php endif; ?>

	<aside class="cf-community-aside">
		<?php require CF_PLUGIN_DIR . 'ui-front/general/partials/single-sidebar-stack.php'; ?>
	</aside>
</div>

<?php require CF_PLUGIN_DIR . 'ui-front/general/partials/single-footer-navigation.php'; ?>

<div class="cf-lightbox" id="cf-lightbox" aria-hidden="true">
	<button type="button" class="cf-lightbox-close" aria-label="<?php esc_attr_e( 'Schliessen', $this->text_domain ); ?>">&times;</button>
	<button type="button" class="cf-lightbox-nav cf-lightbox-prev" aria-label="<?php esc_attr_e( 'Vorheriges Bild', $this->text_domain ); ?>">&#10094;</button>
	<div class="cf-lightbox-stage"><img src="" alt="" class="cf-lightbox-image" /><p class="cf-lightbox-caption"></p></div>
	<button type="button" class="cf-lightbox-nav cf-lightbox-next" aria-label="<?php esc_attr_e( 'Naechstes Bild', $this->text_domain ); ?>">&#10095;</button>
</div>
