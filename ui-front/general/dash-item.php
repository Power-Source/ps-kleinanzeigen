<?php
/**
 * Dashboard Item Card (für AJAX-Listing)
 */
$post_id = get_the_ID();
$status = get_post_status( $post_id );
$_cf_frontend_opts = method_exists( $GLOBALS['Classifieds_Core'], 'get_options' ) ? $GLOBALS['Classifieds_Core']->get_options( 'frontend' ) : array();
$_cf_allow_reserve = ! isset( $_cf_frontend_opts['user_allow_reserve_toggle'] ) || 1 === (int) $_cf_frontend_opts['user_allow_reserve_toggle'];
$is_reserved = method_exists( $GLOBALS['Classifieds_Core'], 'is_reserved_post' ) && $GLOBALS['Classifieds_Core']->is_reserved_post( $post_id );
$expiration = get_post_meta( $post_id, '_expiration_date', true );
$expired = $expiration && $expiration < time();
$price = get_post_meta( $post_id, '_cf_cost', true ) ?: get_post_meta( $post_id, 'cost', true );
$duration = get_post_meta( $post_id, '_cf_duration', true ) ?: get_post_meta( $post_id, 'duration', true );
$image_url = get_the_post_thumbnail_url( $post_id, 'medium' ) ?: $GLOBALS['Classifieds_Core']->plugin_url . 'ui-front/images/placeholder.png';
$payments_options = method_exists( $GLOBALS['Classifieds_Core'], 'get_options' ) ? $GLOBALS['Classifieds_Core']->get_options( 'payments' ) : array();
$featured_enabled = ! empty( $payments_options['enable_featured'] );
$is_featured = $featured_enabled && method_exists( $GLOBALS['Classifieds_Core'], 'is_featured' ) && $GLOBALS['Classifieds_Core']->is_featured( $post_id );
$featured_cost_type = isset( $payments_options['featured_cost_type'] ) ? sanitize_key( $payments_options['featured_cost_type'] ) : 'credits';
$featured_cost = 'credits' === $featured_cost_type
	? ( isset( $payments_options['featured_credit_cost'] ) ? absint( $payments_options['featured_credit_cost'] ) : 50 )
	: ( isset( $payments_options['featured_money_cost'] ) ? sanitize_text_field( $payments_options['featured_money_cost'] ) : '2.99' );
$cf_dashboard_tab = isset( $cf_dashboard_tab ) ? (string) $cf_dashboard_tab : 'active';

$status_labels = array(
	'publish'  => __( 'Aktiv', 'ps-kleinanzeigen' ),
	'expired'  => __( 'Abgelaufen', 'ps-kleinanzeigen' ),
	'draft'    => __( 'Entwurf', 'ps-kleinanzeigen' ),
	'pending'  => __( 'Entwurf', 'ps-kleinanzeigen' ),
	'private'  => __( 'Beendet', 'ps-kleinanzeigen' ),
);

$effective_status = ( 'publish' === $status && $expired ) ? 'expired' : $status;
$can_toggle_featured = $featured_enabled && 'favorites' !== $cf_dashboard_tab && 'publish' === $status && ! $expired;
$featured_cost_label = 'credits' === $featured_cost_type
	? sprintf( __( '%d Credits', 'ps-kleinanzeigen' ), (int) $featured_cost )
	: sprintf( __( '%s EUR', 'ps-kleinanzeigen' ), (string) $featured_cost );
?>
<div class="cf-dashboard-item cf-card <?php echo $is_featured ? 'is-featured' : ''; ?>">
	<div class="cf-card-image">
		<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php the_title_attribute(); ?>">
		<div class="cf-card-overlay-badges">
			<?php if ( $is_featured ) : ?>
				<span class="cf-badge-featured"><?php _e( 'Featured', 'ps-kleinanzeigen' ); ?></span>
			<?php endif; ?>
			<?php if ( $is_reserved ) : ?>
				<span class="cf-badge-reserved"><?php _e( 'Reserviert', 'ps-kleinanzeigen' ); ?></span>
			<?php endif; ?>
			<span class="cf-badge-status cf-badge-<?php echo esc_attr( $effective_status ); ?>">
				<?php echo isset( $status_labels[ $effective_status ] ) ? esc_html( $status_labels[ $effective_status ] ) : esc_html( ucfirst( $effective_status ) ); ?>
			</span>
		</div>
		<?php if ( '' !== (string) $price ) : ?>
			<span class="cf-badge-price"><?php echo esc_html( $price ); ?> EUR</span>
		<?php endif; ?>
	</div>
	<div class="cf-card-body">
		<h3><?php the_title(); ?></h3>
		<?php if ( '' !== (string) $duration ) : ?>
			<p class="cf-dashboard-duration"><?php echo esc_html( $duration ); ?></p>
		<?php endif; ?>
		<?php if ( $expired ) : ?>
			<p class="cf-dashboard-expired"><?php _e( 'Abgelaufen', 'ps-kleinanzeigen' ); ?></p>
		<?php endif; ?>
	</div>
	<div class="cf-card-actions">
		<a href="<?php the_permalink(); ?>" class="cf-btn cf-btn-small"><?php _e( 'Ansehen', 'ps-kleinanzeigen' ); ?></a>
		<a href="<?php echo esc_url( add_query_arg( 'post_id', $post_id, get_permalink( $GLOBALS['Classifieds_Core']->edit_classified_page_id ) ) ); ?>" class="cf-btn cf-btn-small cf-btn-secondary"><?php _e( 'Bearbeiten', 'ps-kleinanzeigen' ); ?></a>
		<?php if ( $_cf_allow_reserve && 'publish' === $status ) : ?>
		<form method="post" action="<?php echo esc_url( get_permalink( $GLOBALS['Classifieds_Core']->my_classifieds_page_id ) ); ?>" class="cf-dashboard-inline-action">
			<input type="hidden" name="action" value="reserve">
			<input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
			<input type="hidden" name="confirm" value="1">
			<?php wp_nonce_field( 'verify' ); ?>
			<button type="submit"
			        class="cf-btn cf-btn-small cf-btn-reserve <?php echo $is_reserved ? 'is-reserved' : ''; ?>"
			        aria-pressed="<?php echo $is_reserved ? 'true' : 'false'; ?>">
				<span class="cf-btn-reserve-icon" aria-hidden="true"><?php echo $is_reserved ? '●' : '○'; ?></span>
				<span class="cf-btn-reserve-label"><?php echo $is_reserved ? esc_html__( 'Freigeben', 'ps-kleinanzeigen' ) : esc_html__( 'Reservieren', 'ps-kleinanzeigen' ); ?></span>
			</button>
		</form>
		<?php endif; ?>
		<?php if ( $can_toggle_featured ) : ?>
		<button type="button"
		        class="cf-btn cf-btn-small cf-btn-toggle-featured <?php echo $is_featured ? 'is-active' : ''; ?>"
		        data-post-id="<?php echo esc_attr( $post_id ); ?>"
		        aria-pressed="<?php echo $is_featured ? 'true' : 'false'; ?>">
			<span class="cf-featured-label-default"><?php _e( 'Hervorheben', 'ps-kleinanzeigen' ); ?></span>
			<span class="cf-featured-label-active"><?php _e( 'Hervorgehoben', 'ps-kleinanzeigen' ); ?></span>
		</button>
		<span class="cf-featured-hint"><?php echo $is_featured ? esc_html__( 'Laeuft aktuell als Featured.', 'ps-kleinanzeigen' ) : esc_html( sprintf( __( 'Preis: %s', 'ps-kleinanzeigen' ), $featured_cost_label ) ); ?></span>
		<?php endif; ?>
	</div>
</div>
