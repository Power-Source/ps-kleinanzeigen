<?php
/**
 * Dashboard Item Card (für AJAX-Listing)
 */
$post_id = get_the_ID();
$status = get_post_status( $post_id );
$is_reserved = method_exists( $GLOBALS['Classifieds_Core'], 'is_reserved_post' ) && $GLOBALS['Classifieds_Core']->is_reserved_post( $post_id );
$expiration = get_post_meta( $post_id, '_expiration_date', true );
$expired = $expiration && $expiration < time();
$price = get_post_meta( $post_id, '_cf_cost', true ) ?: get_post_meta( $post_id, 'cost', true );
$duration = get_post_meta( $post_id, '_cf_duration', true ) ?: get_post_meta( $post_id, 'duration', true );
$image_url = get_the_post_thumbnail_url( $post_id, 'medium' ) ?: $GLOBALS['Classifieds_Core']->plugin_url . 'ui-front/images/placeholder.png';

$status_labels = array(
	'publish'  => __( 'Aktiv', 'ps-kleinanzeigen' ),
	'draft'    => __( 'Entwurf', 'ps-kleinanzeigen' ),
	'pending'  => __( 'Entwurf', 'ps-kleinanzeigen' ),
	'private'  => __( 'Beendet', 'ps-kleinanzeigen' ),
);
?>
<div class="cf-dashboard-item cf-card">
	<div class="cf-card-image">
		<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php the_title_attribute(); ?>">
		<?php if ( $is_reserved ) : ?>
			<span class="cf-badge-reserved"><?php _e( 'Reserviert', 'ps-kleinanzeigen' ); ?></span>
		<?php endif; ?>
		<span class="cf-badge-status cf-badge-<?php echo esc_attr( $status ); ?>">
			<?php echo isset( $status_labels[ $status ] ) ? esc_html( $status_labels[ $status ] ) : esc_html( ucfirst( $status ) ); ?>
		</span>
	</div>
	<div class="cf-card-body">
		<h3><?php the_title(); ?></h3>
		<p class="cf-price"><?php echo esc_html( $price ); ?> EUR</p>
		<p class="cf-duration"><?php echo esc_html( $duration ); ?></p>
		<?php if ( $expired ) : ?>
			<p class="cf-expired"><?php _e( 'Abgelaufen', 'ps-kleinanzeigen' ); ?></p>
		<?php endif; ?>
	</div>
	<div class="cf-card-actions">
		<a href="<?php the_permalink(); ?>" class="cf-btn cf-btn-small"><?php _e( 'Ansehen', 'ps-kleinanzeigen' ); ?></a>
		<a href="<?php echo esc_url( add_query_arg( 'post_id', $post_id, get_permalink( $GLOBALS['Classifieds_Core']->edit_classified_page_id ) ) ); ?>" class="cf-btn cf-btn-small cf-btn-secondary"><?php _e( 'Bearbeiten', 'ps-kleinanzeigen' ); ?></a>
	</div>
</div>
