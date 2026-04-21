<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require CF_PLUGIN_DIR . 'ui-front/general/partials/single-bootstrap.php';
?>
<div class="cf-post cf-single-page cf-single-preset-premium cf-single-premium-layout">
	<section class="cf-premium-hero-card">
		<div class="cf-premium-hero-media">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php $thumbnail = get_the_post_thumbnail( $post->ID, array( 1200, 800 ) ); ?>
			<?php else : ?>
				<?php $thumbnail = '<img title="no image" alt="no image" class="cf-no-image wp-post-image" src="' . $field_image . '">'; ?>
			<?php endif; ?>
			<?php if ( ! empty( $featured_image_url ) ) : ?>
				<a href="<?php echo esc_url( $featured_image_url ); ?>" class="cf-lightbox-trigger cf-single-featured-link" data-lightbox-group="classifieds-gallery" data-lightbox-caption="<?php echo esc_attr( get_the_title() ); ?>"><?php echo $thumbnail; ?></a>
			<?php else : ?>
				<?php echo $thumbnail; ?>
			<?php endif; ?>
		</div>
		<div class="cf-premium-hero-meta">
			<?php if ( $single_show_reserved_badge && $is_reserved ) : ?><span class="cf-status-badge is-reserved"><?php _e( 'Reserviert', $this->text_domain ); ?></span><?php endif; ?>
			<h1 class="cf-premium-title"><?php echo esc_html( get_the_title() ); ?></h1>
			<?php if ( '' !== $cost_display ) : ?><div class="cf-single-price"><?php echo esc_html( $cost_display ); ?></div><?php endif; ?>
		</div>
	</section>

	<div class="cf-premium-grid">
		<article class="cf-premium-story cf-single-section">
			<div class="cf-single-section-head"><h2><?php _e( 'Beschreibung', $this->text_domain ); ?></h2></div>
			<div class="cf-single-description-body"><?php echo wp_kses( $content, cf_wp_kses_allowed_html() ); ?></div>
			<?php require CF_PLUGIN_DIR . 'ui-front/general/partials/single-gallery-section.php'; ?>
			<?php require CF_PLUGIN_DIR . 'ui-front/general/partials/single-contact-section.php'; ?>
			<?php if ( empty( $options['disable_contact_form'] ) && ! $open_contact_form ) : ?>
			<button type="button" class="button cf-btn-premium-primary cf-contact-toggle" id="cf-contact-toggle" onclick="document.getElementById('cf-contact-section').style.display='block'; this.style.display='none'; document.getElementById('cf-contact-section').scrollIntoView({behavior:'smooth',block:'nearest'});">
				<?php _e( 'Anbieter kontaktieren', $this->text_domain ); ?>
			</button>
			<?php endif; ?>
		</article>

		<aside class="cf-premium-aside">
			<section class="cf-single-summary-card">
				<dl class="cf-single-facts">
					<div class="cf-single-fact"><dt><?php _e( 'Anbieter', $this->text_domain ); ?></dt><dd><?php echo the_author_classifieds_link(); ?></dd></div>
					<div class="cf-single-fact"><dt><?php _e( 'Kategorie', $this->text_domain ); ?></dt><dd><?php echo wp_kses_post( get_the_term_list( $post->ID, 'kleinenanzeigen-cat', '', ', ', '' ) ); ?></dd></div>
					<div class="cf-single-fact"><dt><?php _e( 'Veroeffentlicht', $this->text_domain ); ?></dt><dd><?php echo esc_html( $published_date ); ?></dd></div>
					<div class="cf-single-fact"><dt><?php _e( 'Laeuft aus', $this->text_domain ); ?></dt><dd><?php echo esc_html( $expiration_date ); ?></dd></div>
					<?php if ( '' !== $region_name ) : ?><div class="cf-single-fact"><dt><?php _e( 'Standort', $this->text_domain ); ?></dt><dd><?php echo esc_html( $region_name ); ?></dd></div><?php endif; ?>
				</dl>
				<div class="cf-quick-actions cf-single-actions cf-premium-actions">
					<?php if ( empty( $options['disable_contact_form'] ) ) : ?><button type="button" class="button cf-btn-premium-primary cf-cta-contact" onclick="classifieds.toggle_contact_form(); return false;"><?php _e( 'Kontakt', $this->text_domain ); ?></button><?php endif; ?>
					<button type="button" class="button cf-btn-premium-secondary cf-favorite-toggle <?php echo $is_favorite ? 'is-active' : ''; ?>" data-post-id="<?php echo esc_attr( $post->ID ); ?>"><?php _e( 'Merken', $this->text_domain ); ?></button>
					<button type="button" class="button cf-btn-premium-secondary cf-cta-share" data-copy-url="<?php echo esc_url( get_permalink() ); ?>"><?php _e( 'Link teilen', $this->text_domain ); ?></button>
				</div>
			</section>

			<?php require CF_PLUGIN_DIR . 'ui-front/general/partials/single-sidebar-stack.php'; ?>
		</aside>
	</div>
</div>

<div class="cf-lightbox" id="cf-lightbox" aria-hidden="true">
	<button type="button" class="cf-lightbox-close" aria-label="<?php esc_attr_e( 'Schliessen', $this->text_domain ); ?>">&times;</button>
	<button type="button" class="cf-lightbox-nav cf-lightbox-prev" aria-label="<?php esc_attr_e( 'Vorheriges Bild', $this->text_domain ); ?>">&#10094;</button>
	<div class="cf-lightbox-stage"><img src="" alt="" class="cf-lightbox-image" /><p class="cf-lightbox-caption"></p></div>
	<button type="button" class="cf-lightbox-nav cf-lightbox-next" aria-label="<?php esc_attr_e( 'Naechstes Bild', $this->text_domain ); ?>">&#10095;</button>
</div>
