<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $single_show_gallery || empty( $gallery_ids ) ) {
	return;
}

$gallery_mode = in_array( $single_gallery_layout, array( 'image_only', 'slider', 'mosaic' ), true ) ? $single_gallery_layout : 'image_only';
$gallery_count = 0;
?>
<section class="cf-single-gallery-section cf-gallery-mode-<?php echo esc_attr( $gallery_mode ); ?> cf-single-section">
	<div class="cf-single-section-head">
		<h2><?php _e( 'Weitere Bilder', $this->text_domain ); ?></h2>
	</div>

	<?php if ( 'slider' === $gallery_mode ) : ?>
	<div class="cf-single-slider" data-cf-slider>
		<button type="button" class="cf-single-slider-nav is-prev" data-cf-slider-prev aria-label="<?php esc_attr_e( 'Vorheriges Bild', $this->text_domain ); ?>">&lsaquo;</button>
		<div class="cf-single-slider-track">
			<?php foreach ( $gallery_ids as $gallery_id ) : ?>
				<?php $gallery_image = wp_get_attachment_image_src( (int) $gallery_id, 'large' ); ?>
				<?php if ( ! empty( $gallery_image[0] ) ) : ?>
					<?php $gallery_count++; ?>
					<a class="cf-single-slider-slide cf-lightbox-trigger" href="<?php echo esc_url( wp_get_attachment_url( (int) $gallery_id ) ); ?>" data-lightbox-group="classifieds-gallery" data-lightbox-caption="<?php echo esc_attr( get_the_title() ); ?>">
						<img src="<?php echo esc_url( $gallery_image[0] ); ?>" alt="<?php esc_attr_e( 'Galeriebild', $this->text_domain ); ?>" />
					</a>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<button type="button" class="cf-single-slider-nav is-next" data-cf-slider-next aria-label="<?php esc_attr_e( 'Naechstes Bild', $this->text_domain ); ?>">&rsaquo;</button>
		<div class="cf-single-slider-dots">
			<?php for ( $dot = 0; $dot < $gallery_count; $dot++ ) : ?>
				<button type="button" class="cf-single-slider-dot" data-cf-slider-dot="<?php echo esc_attr( $dot ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Bild %d anzeigen', $this->text_domain ), $dot + 1 ) ); ?>"></button>
			<?php endfor; ?>
		</div>
	</div>
	<?php else : ?>
	<div class="cf-gallery-grid cf-single-gallery-grid <?php echo ( 'mosaic' === $gallery_mode ) ? 'cf-gallery-mosaic-grid' : 'cf-gallery-strip-grid'; ?>">
		<?php foreach ( $gallery_ids as $gallery_id ) : ?>
			<?php $gallery_image = wp_get_attachment_image_src( (int) $gallery_id, 'medium_large' ); ?>
			<?php if ( ! empty( $gallery_image[0] ) ) : ?>
				<a class="cf-gallery-item cf-lightbox-trigger" href="<?php echo esc_url( wp_get_attachment_url( (int) $gallery_id ) ); ?>" data-lightbox-group="classifieds-gallery" data-lightbox-caption="<?php echo esc_attr( get_the_title() ); ?>">
					<img src="<?php echo esc_url( $gallery_image[0] ); ?>" alt="<?php esc_attr_e( 'Galeriebild', $this->text_domain ); ?>" />
				</a>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
</section>
