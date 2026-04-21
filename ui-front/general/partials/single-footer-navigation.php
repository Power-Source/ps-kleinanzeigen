<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_post_id = get_the_ID();
$active_preset = isset( $template_preset ) ? sanitize_key( (string) $template_preset ) : '';
if ( '' === $active_preset ) {
	$frontend_options = $this->get_options( 'frontend' );
	$active_preset = isset( $frontend_options['frontend_preset'] ) ? sanitize_key( (string) $frontend_options['frontend_preset'] ) : 'b2c';
}
if ( ! in_array( $active_preset, array( 'b2c', 'premium', 'community' ), true ) ) {
	$active_preset = 'b2c';
}

$back_url = get_permalink( $this->classifieds_page_id );
$referrer = wp_get_referer();
if ( ! empty( $referrer ) && 0 === strpos( $referrer, home_url() ) ) {
	$back_url = $referrer;
}

$category_ids = wp_get_post_terms( $current_post_id, 'kleinenanzeigen-cat', array( 'fields' => 'ids' ) );
$region_ids = wp_get_post_terms( $current_post_id, 'kleinanzeigen-region', array( 'fields' => 'ids' ) );

$tax_query = array();
if ( ! is_wp_error( $category_ids ) && ! empty( $category_ids ) ) {
	$tax_query[] = array(
		'taxonomy' => 'kleinenanzeigen-cat',
		'field'    => 'term_id',
		'terms'    => array_map( 'absint', $category_ids ),
	);
}
if ( ! is_wp_error( $region_ids ) && ! empty( $region_ids ) ) {
	$tax_query[] = array(
		'taxonomy' => 'kleinanzeigen-region',
		'field'    => 'term_id',
		'terms'    => array_map( 'absint', $region_ids ),
	);
}
if ( count( $tax_query ) > 1 ) {
	$tax_query['relation'] = 'OR';
}

$related_args = array(
	'post_type'           => 'classifieds',
	'post_status'         => 'publish',
	'posts_per_page'      => 4,
	'post__not_in'        => array( $current_post_id ),
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
	'orderby'             => 'date',
	'order'               => 'DESC',
);

if ( ! empty( $tax_query ) ) {
	$related_args['tax_query'] = $tax_query;
}

$related_query = new WP_Query( $related_args );
?>

<section class="cf-single-footer-nav cf-single-footer-nav-<?php echo esc_attr( $active_preset ); ?>">
	<div class="cf-single-footer-head">
		<a class="button cf-single-back-link" href="<?php echo esc_url( $back_url ); ?>"><?php _e( 'Zurück zur Übersicht', $this->text_domain ); ?></a>
		<h3><?php _e( 'Weitere Anzeigen', $this->text_domain ); ?></h3>
	</div>

	<?php if ( $related_query->have_posts() ) : ?>
	<div class="cf-single-related-grid">
		<?php while ( $related_query->have_posts() ) : $related_query->the_post(); ?>
			<?php
			$related_id = get_the_ID();
			$related_cost = get_post_meta( $related_id, '_cf_cost', true );
			$related_cost_display = is_numeric( $related_cost ) ? number_format_i18n( (float) $related_cost, 2 ) : $related_cost;
			$related_thumb = get_the_post_thumbnail_url( $related_id, 'medium' );
			if ( empty( $related_thumb ) ) {
				$related_thumb = $field_image;
			}
			$related_is_featured = method_exists( $this, 'is_featured' ) ? $this->is_featured( $related_id ) : ( '1' === (string) get_post_meta( $related_id, '_cf_is_featured', true ) );
			?>
			<article class="cf-single-related-item<?php echo $related_is_featured ? ' is-featured' : ''; ?>">
				<a class="cf-single-related-thumb" href="<?php the_permalink(); ?>">
					<?php if ( $related_is_featured ) : ?>
						<span class="cf-status-badge is-featured"><?php _e( 'Featured', $this->text_domain ); ?></span>
					<?php endif; ?>
					<img src="<?php echo esc_url( $related_thumb ); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" />
				</a>
				<div class="cf-single-related-body">
					<h4><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a></h4>
					<?php if ( '' !== (string) $related_cost_display ) : ?>
						<span class="cf-single-related-price"><?php echo esc_html( $related_cost_display ); ?></span>
					<?php endif; ?>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
	<?php else : ?>
		<p class="cf-single-related-empty"><?php _e( 'Aktuell keine ähnlichen Anzeigen verfuegbar.', $this->text_domain ); ?></p>
	<?php endif; ?>
</section>

<?php wp_reset_postdata(); ?>