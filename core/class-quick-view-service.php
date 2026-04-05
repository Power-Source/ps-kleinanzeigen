<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CF_Quick_View_Service {
	/** @var Classifieds_Core */
	private $core;

	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Return quick-view markup for a classifieds post.
	 *
	 * @return void
	 */
	public function ajax_quick_view() {
		check_ajax_referer( 'cf_frontend_actions', 'nonce' );

		$post_id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0;

		if ( ! $post_id || 'classifieds' !== get_post_type( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Die Anzeige konnten wir nicht finden.', $this->core->text_domain ) ), 400 );
		}

		wp_send_json_success(
			array(
				'html' => $this->get_quick_view_markup( $post_id ),
			)
		);
	}

	/**
	 * Build quick-view markup.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	public function get_quick_view_markup( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || 'classifieds' !== $post->post_type ) {
			return '';
		}

		$cost         = get_post_meta( $post_id, '_cf_cost', true );
		$cost_display = is_numeric( $cost ) ? number_format_i18n( (float) $cost, 2 ) : $cost;
		$duration     = get_post_meta( $post_id, '_cf_duration', true );
		$gallery_ids  = get_post_meta( $post_id, '_cf_gallery_ids', true );
		$image_html   = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail( $post_id, 'large' ) : '';
		$excerpt      = has_excerpt( $post_id ) ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 );

		if ( empty( $image_html ) && is_array( $gallery_ids ) && ! empty( $gallery_ids[0] ) ) {
			$image_html = wp_get_attachment_image( (int) $gallery_ids[0], 'large' );
		}

		ob_start();
		?>
		<div class="cf-quickview-card">
			<div class="cf-quickview-media"><?php echo $image_html ? $image_html : '<div class="cf-quickview-placeholder">' . esc_html__( 'Kein Bild', $this->core->text_domain ) . '</div>'; ?></div>
			<div class="cf-quickview-body">
				<p class="cf-quickview-kicker"><?php esc_html_e( 'Schnellansicht', $this->core->text_domain ); ?></p>
				<h3><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
				<div class="cf-quickview-meta">
					<?php if ( '' !== $cost_display ) : ?>
						<span class="cf-meta-chip"><?php esc_html_e( 'Preis:', $this->core->text_domain ); ?> <?php echo esc_html( $cost_display ); ?></span>
					<?php endif; ?>
					<?php if ( '' !== $duration ) : ?>
						<span class="cf-meta-chip"><?php esc_html_e( 'Laufzeit:', $this->core->text_domain ); ?> <?php echo esc_html( $duration ); ?></span>
					<?php endif; ?>
				</div>
				<p class="cf-quickview-excerpt"><?php echo esc_html( $excerpt ); ?></p>
				<div class="cf-quickview-actions">
					<a class="button cf-card-secondary cf-card-contact" href="<?php echo esc_url( add_query_arg( 'cf_contact', '1', get_permalink( $post_id ) ) . '#confirm-form' ); ?>"><?php esc_html_e( 'Kontakt', $this->core->text_domain ); ?></a>
					<a class="button button-primary cf-card-cta" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php esc_html_e( 'Anzeige öffnen', $this->core->text_domain ); ?></a>
					<button type="button" class="button cf-favorite-toggle <?php echo $this->core->is_favorite_post( $post_id ) ? 'is-active' : ''; ?>" data-post-id="<?php echo esc_attr( $post_id ); ?>">
						<span class="cf-favorite-label-default"><?php esc_html_e( 'Merken', $this->core->text_domain ); ?></span>
						<span class="cf-favorite-label-active"><?php esc_html_e( 'Gemerkt', $this->core->text_domain ); ?></span>
					</button>
				</div>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}
