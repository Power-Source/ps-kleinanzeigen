<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cf-single-sidebar-stack">
	<?php if ( $single_show_seller_card ) : ?>
	<div class="cf-seller-card">
		<h3><?php _e( 'Verkaeuferprofil', $this->text_domain ); ?></h3>
		<p class="cf-seller-name"><?php echo esc_html( $author_display_name ); ?></p>
		<div class="cf-seller-meta">
			<span class="cf-meta-chip"><?php echo esc_html( sprintf( _n( '%d aktive Anzeige', '%d aktive Anzeigen', (int) $author_active_ads, $this->text_domain ), (int) $author_active_ads ) ); ?></span>
			<?php if ( '' !== $author_registered ) : ?>
				<span class="cf-meta-chip"><?php _e( 'Mitglied seit:', $this->text_domain ); ?> <?php echo esc_html( $author_registered ); ?></span>
			<?php endif; ?>
		</div>
		<p class="cf-seller-actions">
			<a class="button cf-card-secondary" href="<?php echo esc_url( home_url( '/cf-author/' . get_the_author_meta( 'user_login', $author_id ) . '/' ) ); ?>"><?php _e( 'Alle Anzeigen ansehen', $this->text_domain ); ?></a>
		</p>
	</div>
	<?php endif; ?>

	<?php if ( $single_show_trust_block && ( '' !== $trust_block_content || '' !== $region_name ) ) : ?>
	<div class="cf-trust-card">
		<h3><?php _e( 'Hinweise', $this->text_domain ); ?></h3>
		<?php if ( '' !== $trust_block_content ) : ?>
		<div class="cf-trust-content"><?php echo wp_kses_post( wpautop( $trust_block_content ) ); ?></div>
		<?php endif; ?>
		<?php if ( '' !== $region_name ) : ?>
		<p class="cf-trust-location"><strong><?php _e( 'Standort:', $this->text_domain ); ?></strong> <?php echo esc_html( $region_name ); ?></p>
		<?php endif; ?>
	</div>
	<?php endif; ?>
</div>
