<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require CF_PLUGIN_DIR . 'ui-front/general/partials/archive-bootstrap.php';
?>
<?php if ( ! have_posts() ) : ?>
<div id="post-0" class="post error404 not-found"><h1 class="entry-title"><?php _e( 'Nicht gefunden', CF_TEXT_DOMAIN ); ?></h1></div>
<?php else : ?>
<div class="cf-listing-grid cf-grid-cols-<?php echo esc_attr( $archive_columns ); ?> cf-archive-preset-community cf-archive-community-layout">
<?php while ( have_posts() ) : the_post();
	$cost = get_post_meta( get_the_ID(), '_cf_cost', true );
	$cost = is_numeric( $cost ) ? number_format_i18n( (float) $cost, 2 ) : $cost;
	$is_favorite = in_array( get_the_ID(), $favorite_ids, true );
	$is_reserved = method_exists( $cf, 'is_reserved_post' ) ? $cf->is_reserved_post( get_the_ID() ) : ( '1' === (string) get_post_meta( get_the_ID(), '_cf_reserved', true ) );
	$is_featured = method_exists( $cf, 'is_featured' ) ? $cf->is_featured( get_the_ID() ) : ( '1' === (string) get_post_meta( get_the_ID(), '_cf_is_featured', true ) );
	$community_card_classes = 'cf-listing-card-wrap cf-community-row-wrap' . ( $is_featured ? ' is-featured' : '' );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( $community_card_classes ); ?>>
	<div class="cf-community-row">
		<a class="cf-community-thumb" href="<?php the_permalink(); ?>">
			<?php
			if ( '' == get_post_meta( get_the_ID(), '_thumbnail_id', true ) ) {
				echo '<img class="cf-card-image" src="' . esc_url( $field_image ) . '" alt="no image">';
			} else {
				echo get_the_post_thumbnail( get_the_ID(), 'medium', array( 'class' => 'cf-card-image', 'loading' => 'lazy' ) );
			}
			?>
		</a>
		<div class="cf-community-main">
			<h3 class="cf-community-title"><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a></h3>
			<p class="cf-community-meta"><?php _e( 'Von', CF_TEXT_DOMAIN ); ?> <?php the_author(); ?> · <?php echo esc_html( $cf->get_expiration_date( get_the_ID() ) ); ?></p>
		</div>
		<div class="cf-community-side">
			<?php if ( $is_featured ) : ?><span class="cf-status-badge is-featured"><?php _e( 'Featured', CF_TEXT_DOMAIN ); ?></span><?php endif; ?>
			<?php if ( ! empty( $cost ) ) : ?><div class="cf-community-price"><?php echo esc_html( $cost ); ?></div><?php endif; ?>
			<?php if ( $archive_show_reserved_badge && $is_reserved ) : ?><span class="cf-status-badge is-reserved"><?php _e( 'Reserviert', CF_TEXT_DOMAIN ); ?></span><?php endif; ?>
			<div class="cf-community-actions">
				<?php if ( $archive_show_quickview ) : ?><a class="button cf-btn-community-secondary cf-quickview-trigger" href="<?php the_permalink(); ?>" data-post-id="<?php the_ID(); ?>"><?php _e( 'Quick', CF_TEXT_DOMAIN ); ?></a><?php endif; ?>
				<?php if ( $archive_show_favorites ) : ?><button type="button" class="button cf-btn-community-secondary cf-favorite-toggle <?php echo $is_favorite ? 'is-active' : ''; ?>" data-post-id="<?php the_ID(); ?>"><?php _e( 'Merken', CF_TEXT_DOMAIN ); ?></button><?php endif; ?>
				<a class="button cf-btn-community-primary" href="<?php the_permalink(); ?>"><?php _e( 'Thread oeffnen', CF_TEXT_DOMAIN ); ?></a>
			</div>
		</div>
		<p class="cf-community-excerpt cf-community-excerpt-full"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 20, ' ...' ) ); ?></p>
	</div>
</article>
<?php endwhile; ?>
</div>
<?php endif; ?>
<div class="cf-modal" id="cf-quickview-modal" aria-hidden="true"><div class="cf-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="cf-quickview-title"><button type="button" class="cf-modal-close" aria-label="<?php esc_attr_e( 'Schliessen', CF_TEXT_DOMAIN ); ?>">&times;</button><div class="cf-modal-content"><div class="cf-modal-loading"><?php _e( 'Wird geladen ...', CF_TEXT_DOMAIN ); ?></div></div></div></div>
<?php echo $cf->pagination( $cf->pagination_bottom ); ?>
