<?php if (!defined('ABSPATH')) die('No direct access allowed!'); ?>
<?php
$payment_settings = $this->get_options( 'payments' );
$payment_settings = is_array( $payment_settings ) ? $payment_settings : array();
$credit_packages = ( ! empty( $payment_settings['mp_credit_packages'] ) && is_array( $payment_settings['mp_credit_packages'] ) ) ? $payment_settings['mp_credit_packages'] : array();
$one_time_enabled = ! empty( $payment_settings['enable_one_time'] );
$one_time_price = isset( $payment_settings['one_time_cost'] ) ? $payment_settings['one_time_cost'] : '0.00';
$one_time_label = empty( $payment_settings['one_time_txt'] ) ? __( 'Einmalzahlung', $this->text_domain ) : $payment_settings['one_time_txt'];
$affiliate_costs = $this->get_options( 'affiliate_settings' );
$affiliate_costs = is_array( $affiliate_costs ) ? $affiliate_costs : array();
$affiliate_settings = array(
	'credit_packages' => $credit_packages,
	'one_time'        => array(
		'enabled' => $one_time_enabled,
		'price'   => $one_time_price,
		'label'   => $one_time_label,
	),
	'cost'            => $affiliate_costs,
);

?>

<div class="wrap">

	<?php $this->render_admin( 'navigation', array( 'page' => 'classifieds_settings', 'tab' => 'affiliate' ) ); ?>
	<?php $this->render_admin( 'message' ); ?>

	<h1><?php _e( 'Affiliate-Einstellungen', $this->text_domain ); ?></h1>
	<p class="description">
		<?php _e( 'Hier legst du die Provision fuer Einmalzahlungen und Kleinanzeigen-Credit-Pakete fest.', $this->text_domain ) ?>
	</p>
	<div class="postbox">
		<h3 class='hndle'><span><?php _e( 'Affiliate', $this->text_domain ) ?></span></h3>
		<div class="inside">
			<?php if ( ! class_exists( 'affiliateadmin' ) || ! defined( 'AFF_KLEINANZEIGEN_ADDON' ) ): ?>
			<p>
				<?php _e( 'Diese Funktion ist erst verfuegbar, wenn das <b>Affiliate-Plugin</b> installiert und dort das <b>Kleinanzeigen-Add-on</b> aktiviert ist.', $this->text_domain ) ?>
				<br />
				<?php printf ( __( 'Mehr Infos zum Affiliate-Plugin findest du <a href="%s" target="_blank">hier</a>.', $this->text_domain ), 'http://premium.wpmudev.org/project/wordpress-mu-affiliate/' ); ?>
				<br /><br />

				<?php _e( 'Bitte aktiviere:', $this->text_domain ) ?>
				<br />
				<?php _e( '1. Das <b>Affiliate-Plugin</b>', $this->text_domain ) ?>
				<?php if ( class_exists( 'affiliate' ) ) _e( ' - <i>Erledigt</i>', $this->text_domain ); ?>
				<br />
				<?php _e( '2. Das <b>Kleinanzeigen-Add-on</b> im Affiliate-Plugin', $this->text_domain ) ?>
			</p>
			<?php endif;?>

			<p class="description" style="margin:0 0 12px 0;">
				<?php _e( 'Abo-Zahlungen werden jetzt ueber PS-Mitgliedschaften abgerechnet und dort auch affiliate-seitig behandelt. In Kleinanzeigen provisionieren wir deshalb nur noch Einmalzahlungen und Credit-Pakete.', $this->text_domain ); ?>
			</p>

			<?php do_action( 'classifieds_affiliate_settings', $affiliate_settings ); ?>

		</div>
	</div>

</div>
