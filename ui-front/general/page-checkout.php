<?php
/**
* The template for displaying the Checkout page.
* You can override this file in your active theme.
*
* @package Classifieds
* @subpackage UI Front
* @since Classifieds 2.0
*/

$options = $this->get_options();

$step = get_query_var('checkout_step');
$step = (empty($step)) ? 'terms' : $step;

$step = (empty($_GET['step'])) ? $step : $_GET['step'];

$error = get_query_var('checkout_error');
$error = (empty($error)) ? '' : $error;

$mp_bridge_enabled = ! empty( $options['payments']['enable_marketpress_bridge'] );
$mp_one_time_product_id  = empty( $options['payments']['mp_one_time_product_id'] ) ? 0 : absint( $options['payments']['mp_one_time_product_id'] );
$mp_credit_packages      = ( ! empty( $options['payments']['mp_credit_packages'] ) && is_array( $options['payments']['mp_credit_packages'] ) ) ? $options['payments']['mp_credit_packages'] : array();
$mp_one_time_product_url = ( $mp_one_time_product_id > 0 ) ? get_permalink( $mp_one_time_product_id ) : '';
$show_shop_hub           = $mp_bridge_enabled && ( ! empty( $mp_credit_packages ) || $mp_one_time_product_url );

if ( $step === 'disabled' ) : ?>
	<div class="invalid-login">
		<?php _e( 'Diese Funktion ist derzeit vom Systemadministrator deaktiviert.', $this->text_domain ); ?>
	</div>
<?php endif; ?>

<?php if ( ! empty( $error ) && ! is_array( $error ) ) : ?>
	<div class="invalid-login"><?php echo esc_html( $error ); ?></div>
<?php endif; ?>

<?php if ( $step === 'api_call_error' && is_array( $error ) ) : ?>
	<ul class="cf-checkout-errors">
		<li><?php echo esc_html( sprintf( __( '%s API-Aufruf fehlgeschlagen.', $this->text_domain ), isset( $error['error_call'] ) ? $error['error_call'] : '' ) ); ?></li>
		<li><?php echo esc_html( sprintf( __( 'Detaillierte Fehlermeldung: %s', $this->text_domain ), isset( $error['error_long_msg'] ) ? $error['error_long_msg'] : '' ) ); ?></li>
		<li><?php echo esc_html( sprintf( __( 'Kurze Fehlermeldung: %s', $this->text_domain ), isset( $error['error_short_msg'] ) ? $error['error_short_msg'] : '' ) ); ?></li>
		<li><?php echo esc_html( sprintf( __( 'Fehlercode: %s', $this->text_domain ), isset( $error['error_code'] ) ? $error['error_code'] : '' ) ); ?></li>
		<li><?php echo esc_html( sprintf( __( 'Fehlerschweregrad: %s', $this->text_domain ), isset( $error['error_severity_code'] ) ? $error['error_severity_code'] : '' ) ); ?></li>
	</ul>
<?php endif; ?>

<?php if ( $step === 'success' || $step === 'free_success' ) : ?>
	<div class="dp-thank-you"><?php _e( 'Vielen Dank! Die Zahlung wurde erfolgreich verarbeitet.', $this->text_domain ); ?></div>
	<span class="dp-submit-txt"><?php _e( 'Du kannst jetzt direkt zu Deinen Kleinanzeigen wechseln.', $this->text_domain ); ?></span>
	<br /><br />
	<?php echo do_shortcode( '[cf_my_classifieds_btn text="' . esc_attr__( 'Weiter zu Deinen Kleinanzeigen', $this->text_domain ) . '" view="always"]' ); ?>
<?php endif; ?>

<?php if ( $show_shop_hub ) : ?>
	<div class="cf-marketpress-checkout-banner">
		<div class="cf-banner-content">
			<div class="cf-banner-icon">Shop</div>
			<div class="cf-banner-text">
				<h3><?php _e( 'Credits kaufen', $this->text_domain ); ?></h3>
				<p><?php _e( 'Waehle dein passendes Paket und schliesse den Kauf sicher ab.', $this->text_domain ); ?></p>
			</div>
		</div>

		<?php if ( $this->is_full_access() ) : ?>
			<div class="cf-info-box">
				<?php _e( 'Du hast bereits vollen Zugriff zum Erstellen von Anzeigen.', $this->text_domain ); ?>
			</div>
		<?php endif; ?>

		<div class="cf-banner-packages">
			<?php if ( ! empty( $mp_credit_packages ) ) : ?>
				<div class="cf-packages-grid">
					<?php foreach ( $mp_credit_packages as $credit_package ) : ?>
						<?php
						$package_product_id = empty( $credit_package['product_id'] ) ? 0 : absint( $credit_package['product_id'] );
						$package_url        = $package_product_id > 0 ? get_permalink( $package_product_id ) : '';
						if ( empty( $package_url ) ) {
							continue;
						}
						$package_label   = empty( $credit_package['label'] ) ? __( 'Credit-Paket', $this->text_domain ) : $credit_package['label'];
						$package_credits = empty( $credit_package['credits'] ) ? 0 : absint( $credit_package['credits'] );
						$package_price   = empty( $credit_package['price'] ) ? '0' : $credit_package['price'];
						?>
						<div class="cf-package-card">
							<div class="cf-package-header">
								<strong><?php echo esc_html( $package_label ); ?></strong>
							</div>
							<div class="cf-package-body">
								<div class="cf-package-credit-count"><?php echo esc_html( sprintf( __( '%d Credits', $this->text_domain ), $package_credits ) ); ?></div>
								<div class="cf-package-price"><?php echo esc_html( sprintf( __( '%s EUR', $this->text_domain ), $package_price ) ); ?></div>
							</div>
							<div class="cf-package-footer">
								<a class="cf-btn cf-btn-package" href="<?php echo esc_url( $package_url ); ?>"><?php _e( 'Jetzt kaufen', $this->text_domain ); ?></a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $mp_one_time_product_url ) : ?>
				<div class="cf-onetime-section">
					<a class="cf-btn cf-btn-onetime" href="<?php echo esc_url( $mp_one_time_product_url ); ?>"><?php _e( 'Einmalzahlung kaufen', $this->text_domain ); ?></a>
				</div>
			<?php endif; ?>
		</div>
	</div>
<?php else : ?>
	<div class="cf-info-box">
		<?php _e( 'Aktuell sind keine kaufbaren Produkte hinterlegt. Bitte wende Dich an den Support.', $this->text_domain ); ?>
	</div>
<?php endif; ?>

<div class="clear"></div><br />

<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Seiten:', $this->text_domain ), 'after' => '</div>' ) ); ?>
<?php edit_post_link( __( 'Bearbeiten', $this->text_domain ), '<span class="edit-link">', '</span>' ); ?>
<script type="text/javascript">jQuery('.checkout').validate();</script>

