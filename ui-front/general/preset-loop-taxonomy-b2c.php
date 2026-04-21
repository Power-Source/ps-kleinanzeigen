<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require CF_PLUGIN_DIR . 'ui-front/general/partials/archive-bootstrap.php';
?>
<?php if ( ! have_posts() ) : ?>
<div id="post-0" class="post error404 not-found"><h1 class="entry-title"><?php _e( 'Nicht gefunden', CF_TEXT_DOMAIN ); ?></h1></div>
<?php else : ?>
<div class="cf-listing-grid cf-grid-cols-<?php echo esc_attr( $archive_columns ); ?> cf-archive-preset-b2c cf-archive-b2c-layout">
<?php while ( have_posts() ) : the_post();
	$cost = get_post_meta( get_the_ID(), '_cf_cost', true );
	$cost = is_numeric( $cost ) ? number_format_i18n( (float) $cost, 2 ) : $cost;
	$gallery_ids = get_post_meta( get_the_ID(), '_cf_gallery_ids', true );
	$gallery_count = is_array( $gallery_ids ) ? count( array_filter( $gallery_ids ) ) : 0;
	$is_favorite = in_array( get_the_ID(), $favorite_ids, true );
	$is_reserved = method_exists( $cf, 'is_reserved_post' ) ? $cf->is_reserved_post( get_the_ID() ) : ( '1' === (string) get_post_meta( get_the_ID(), '_cf_reserved', true ) );
	$is_featured = method_exists( $cf, 'is_featured' ) ? $cf->is_featured( get_the_ID() ) : ( '1' === (string) get_post_meta( get_the_ID(), '_cf_is_featured', true ) );
	$b2c_card_classes = 'cf-listing-card-wrap cf-b2c-card-wrap' . ( $is_featured ? ' is-featured' : '' );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( $b2c_card_classes ); ?>>
	<div class="cf-b2c-card">
		<a class="cf-b2c-thumb" href="<?php the_permalink(); ?>">
			<?php if ( $is_featured ) : ?><span class="cf-status-badge is-featured"><?php _e( 'Featured', CF_TEXT_DOMAIN ); ?></span><?php endif; ?>
			<?php if ( $archive_show_reserved_badge && $is_reserved ) : ?><span class="cf-status-badge is-reserved"><?php _e( 'Reserviert', CF_TEXT_DOMAIN ); ?></span><?php endif; ?>
			<?php if ( $gallery_count > 0 ) : ?><span class="cf-gallery-badge"><?php echo esc_html( sprintf( _n( '%d Bild', '%d Bilder', $gallery_count, CF_TEXT_DOMAIN ), $gallery_count ) ); ?></span><?php endif; ?>
			<?php
			if ( '' == get_post_meta( get_the_ID(), '_thumbnail_id', true ) ) {
				echo '<img class="cf-card-image" src="' . esc_url( $field_image ) . '" alt="no image">';
			} else {
				echo get_the_post_thumbnail( get_the_ID(), 'medium_large', array( 'class' => 'cf-card-image', 'loading' => 'lazy' ) );
			}
			?>
		</a>
		<div class="cf-b2c-body">
			<h3 class="cf-b2c-title"><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a></h3>
			<?php if ( ! empty( $cost ) ) : ?><div class="cf-b2c-price"><?php echo esc_html( $cost ); ?></div><?php endif; ?>
			<p class="cf-b2c-excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 14, ' ...' ) ); ?></p>
			<div class="cf-b2c-actions">
				<?php if ( $archive_show_favorites ) : ?><button type="button" class="button cf-btn-b2c-ghost cf-favorite-toggle <?php echo $is_favorite ? 'is-active' : ''; ?>" data-post-id="<?php the_ID(); ?>"><?php _e( 'Merken', CF_TEXT_DOMAIN ); ?></button><?php endif; ?>
				<?php if ( $archive_show_quickview ) : ?><a class="button cf-btn-b2c-ghost cf-quickview-trigger" href="<?php the_permalink(); ?>" data-post-id="<?php the_ID(); ?>"><?php _e( 'Schnellansicht', CF_TEXT_DOMAIN ); ?></a><?php endif; ?>
				<a class="button cf-btn-b2c-primary" href="<?php the_permalink(); ?>"><?php _e( 'Details', CF_TEXT_DOMAIN ); ?></a>
			</div>
		</div>
	</div>
</article>
<?php endwhile; ?>
</div>
<?php endif; ?>
<div class="cf-modal" id="cf-quickview-modal" aria-hidden="true"><div class="cf-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="cf-quickview-title"><button type="button" class="cf-modal-close" aria-label="<?php esc_attr_e( 'Schliessen', CF_TEXT_DOMAIN ); ?>">&times;</button><div class="cf-modal-content"><div class="cf-modal-loading"><?php _e( 'Wird geladen ...', CF_TEXT_DOMAIN ); ?></div></div></div></div>
<?php echo $cf->pagination( $cf->pagination_bottom ); ?>
