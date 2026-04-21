<?php if (!defined('ABSPATH')) die('No direct access allowed!'); ?>

<?php $options = $this->get_options('payments'); ?>
<?php
$mp_one_time_product_id = empty( $options['mp_one_time_product_id'] ) ? 0 : absint( $options['mp_one_time_product_id'] );
$mp_credit_packages = ( ! empty( $options['mp_credit_packages'] ) && is_array( $options['mp_credit_packages'] ) ) ? $options['mp_credit_packages'] : array();
$marketpress_active = function_exists( 'mp_store_page_url' ) || class_exists( 'MarketPress' );
$memberships_active = class_exists( 'MS_Model_Membership' ) && class_exists( 'MS_Model_Member' );
$psource_manager_url = 'https://psource.eimen.net/psource-manager/';
$required_membership_ids = isset( $options['required_membership_ids'] ) && is_array( $options['required_membership_ids'] ) ? array_map( 'absint', $options['required_membership_ids'] ) : array();
$memberships_admin_url = '';
$memberships_create_url = '';
$memberships_create_step = 'new';

if ( class_exists( 'MS_Controller_Membership' ) ) {
	$memberships_create_step = MS_Controller_Membership::STEP_ADD_NEW;
}

if ( $memberships_active ) {
	if ( class_exists( 'MS_Controller_Plugin' ) && method_exists( 'MS_Controller_Plugin', 'get_admin_url' ) ) {
		$memberships_admin_url = MS_Controller_Plugin::get_admin_url();
		$memberships_create_url = MS_Controller_Plugin::get_admin_url( false, array( 'step' => $memberships_create_step ) );
	} else {
		$memberships_admin_url = admin_url( 'admin.php?page=membership2' );
		$memberships_create_url = admin_url( 'admin.php?page=membership2&step=' . rawurlencode( $memberships_create_step ) );
	}
}
$available_memberships = array();
if ( $memberships_active ) {
	$available_memberships = MS_Model_Membership::get_memberships( array(
		'include_base'  => false,
		'include_guest' => false,
		'active'        => true,
	) );
}
$payment_type_options = $this->get_options( 'payment_types' );
$use_free_checked = ! empty( $options['use_free'] ) || ( is_array( $payment_type_options ) && ! empty( $payment_type_options['use_free'] ) );
$expired_restart_mode = isset( $options['expired_restart_mode'] ) ? sanitize_key( $options['expired_restart_mode'] ) : 'credits';
if ( ! in_array( $expired_restart_mode, array( 'none', 'free', 'credits' ), true ) ) {
	$expired_restart_mode = 'credits';
}
?>

<div class="wrap">

	<?php $this->render_admin( 'navigation', array( 'page' => 'classifieds_settings', 'tab' => 'payments' ) ); ?>

	<?php $this->render_admin( 'message' ); ?>

	<h1><?php _e( 'Zahlungs-Einstellungen', $this->text_domain ); ?></h1>

	<p class="description" style="margin:8px 0 16px 0; padding:10px 12px; background:#f6f7f7; border-left:4px solid #2271b1;">
		<?php _e( 'Hinweis zu Gateways: Wiederkehrende Zahlungen nutzen PS-Mitgliedschaften, Einmalzahlung und Credits nutzen MarketPress. Beide Plugins gehoeren zum PSOURCE Oekosystem.', $this->text_domain ); ?>
		<a href="<?php echo esc_url( $psource_manager_url ); ?>" target="_blank" rel="noopener noreferrer"><?php _e( 'PSOURCE Manager oeffnen', $this->text_domain ); ?></a>
		<?php _e( '(Installation und Verwaltung).', $this->text_domain ); ?>
	</p>

	<form action="#" method="post">
		<div class="postbox">
			<h3 class='hndle'><span><?php _e( 'Grundmodell', $this->text_domain ) ?></span></h3>
			<div class="inside">
				<?php if ( ! $marketpress_active ) : ?>
					<p class="description" style="color:#b71c1c; margin:0 0 12px 0;">
						<?php _e( 'MarketPress ist nicht installiert/aktiv. Kostenpflichtige Optionen koennen erst nach Aktivierung genutzt werden.', $this->text_domain ); ?>
						<a href="<?php echo esc_url( $psource_manager_url ); ?>" target="_blank" rel="noopener noreferrer"><?php _e( 'Jetzt ueber den PSOURCE Manager installieren/verwalten', $this->text_domain ); ?></a>
					</p>
				<?php endif; ?>
				<table class="form-table">
					<tr>
						<th><label for="use_free"><?php _e( 'Kostenlose Anzeigen', $this->text_domain ); ?></label></th>
						<td>
							<label>
								<input type="checkbox" id="use_free" name="use_free" value="1" <?php checked( $use_free_checked ); ?> />
								<?php _e( 'Aktiv: Angemeldete User koennen kostenlos Anzeigen erstellen.', $this->text_domain ); ?>
							</label>
							<br /><span class="description"><?php _e( 'Wenn aktiv, sind kostenpflichtige Checkout-Modelle deaktiviert.', $this->text_domain ); ?></span>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<div class="postbox">
			<h3 class='hndle'><span><?php _e( 'Wiederkehrende Zahlungen', $this->text_domain ) ?></span></h3>
			<div class="inside">
				<table class="form-table" id="recurring_table">
					<tr id="enable_recurring_tr">
						<th>
							<label for "enable_recurring"><?php _e( 'Wiederkehrende Zahlungen aktivieren', $this->text_domain ) ?></label>
						</th>
						<td>
							<input type="checkbox" id="enable_recurring" name="enable_recurring" value="1" <?php checked( ! empty($options['enable_recurring'] ) ); ?> <?php disabled( ! $memberships_active ); ?> />
							<label for="enable_recurring"><?php _e('Mitgliedschaften als Voraussetzung fuer Kleinanzeigen nutzen', $this->text_domain) ?></label>
							<?php if ( ! $memberships_active ) : ?>
								<br /><span class="description" style="color:#b71c1c;"><?php _e( 'PS-Mitgliedschaften ist nicht aktiv. Diese Option ist erst dann verfuegbar.', $this->text_domain ); ?> <a href="<?php echo esc_url( $psource_manager_url ); ?>" target="_blank" rel="noopener noreferrer"><?php _e( 'Jetzt ueber den PSOURCE Manager installieren/verwalten', $this->text_domain ); ?></a></span>
							<?php else : ?>
								<br /><span class="description"><?php _e( 'Nur User mit mindestens einer ausgewaehlten aktiven Mitgliedschaft duerfen Kleinanzeigen nutzen.', $this->text_domain ); ?></span>
								<?php if ( ! empty( $memberships_admin_url ) ) : ?>
									<br /><br />
									<a class="button button-secondary" href="<?php echo esc_url( $memberships_admin_url ); ?>"><?php _e( 'Mitgliedschaften verwalten', $this->text_domain ); ?></a>
									<?php if ( ! empty( $memberships_create_url ) ) : ?>
										<a class="button button-link" href="<?php echo esc_url( $memberships_create_url ); ?>"><?php _e( 'Neue Mitgliedschaft anlegen', $this->text_domain ); ?></a>
									<?php endif; ?>
								<?php endif; ?>
							<?php endif; ?>
						</td>
					</tr>
					<?php if ( $memberships_active ) : ?>
					<tr>
						<th><?php _e( 'Erforderliche Mitgliedschaften', $this->text_domain ); ?></th>
						<td>
							<?php if ( empty( $available_memberships ) ) : ?>
								<span class="description" style="color:#b71c1c;"><?php _e( 'Keine aktiven Mitgliedschaften gefunden. Bitte zuerst in PS-Mitgliedschaften anlegen/aktivieren.', $this->text_domain ); ?></span>
							<?php else : ?>
								<?php foreach ( $available_memberships as $membership ) : ?>
									<?php
									if ( ! empty( $membership->active ) && method_exists( $membership, 'is_system' ) && $membership->is_system() ) {
										continue;
									}
									$membership_id = absint( $membership->id );
									?>
									<p>
										<label>
											<input type="checkbox" name="required_membership_ids[]" value="<?php echo esc_attr( $membership_id ); ?>" <?php checked( in_array( $membership_id, $required_membership_ids, true ) ); ?> />
											<?php echo esc_html( $membership->name ); ?>
										</label>
									</p>
								<?php endforeach; ?>
							<?php endif; ?>
						</td>
					</tr>
					<?php endif; ?>
				</table>
			</div>
		</div>
		<div class="postbox">
			<h3 class='hndle'><span><?php _e( 'Einmalzahlung', $this->text_domain ) ?></span></h3>
			<div class="inside">

				<table class="form-table">
					<tr>
						<th><label for="enable_one_time"><?php _e( 'Einmalzahlung aktivieren', $this->text_domain ); ?></label></th>
						<td>
							<label>
								<input type="checkbox" id="enable_one_time" name="enable_one_time" value="1" <?php checked( ! empty( $options['enable_one_time'] ) );  ?> />
								<?php _e( 'Einmalzahlung fuer das Veroeffentlichen aktivieren.', $this->text_domain ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th>
							<label for="one_time_cost"><?php _e( 'Einmalzahlungs-Option', $this->text_domain ) ?></label>
						</th>
						<td>
							<input type="text" id="one_time_cost" class="small-text" name="one_time_cost" value="<?php echo ( empty( $options['one_time_cost'] ) ) ? '0' : $options['one_time_cost']; ?>" />
							<span class="description"><?php _e( 'Preis fuer die Einmalzahlung.', $this->text_domain ); ?></span>
							<br /><br />
							<input class="cf-full" type="text" name="one_time_txt" value="<?php echo (empty( $options['one_time_txt'] ) ) ? '' : $options['one_time_txt']; ?>" />
							<span class="description"><?php _e( 'Text fuer die Einmalzahlungs-Option.', $this->text_domain ); ?></span>
							<input type="hidden" name="mp_one_time_product_id" value="<?php echo esc_attr( $mp_one_time_product_id ); ?>" />
							<br /><br />
							<?php if ( ! empty( $options['enable_one_time'] ) ) : ?>
								<span class="description"><?php _e( 'Das zugehoerige MarketPress-Produkt wird beim Speichern automatisch erstellt oder aktualisiert.', $this->text_domain ); ?></span>
							<?php endif; ?>
							<?php if ( $mp_one_time_product_id > 0 && get_post( $mp_one_time_product_id ) ) : ?>
								<br /><a class="button button-secondary" href="<?php echo esc_url( get_edit_post_link( $mp_one_time_product_id ) ); ?>"><?php _e( 'Einmalzahlung-Produkt bearbeiten', $this->text_domain ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<div class="postbox">
			<h3 class='hndle'><span><?php _e( 'Credits verwenden', $this->text_domain ) ?></span></h3>
			<div class="inside">
				<table class="form-table">
					<tr>
						<th><label for="enable_credits"><?php _e( 'Credits aktivieren', $this->text_domain ); ?></label></th>
						<td>
							<label>
								<input type="checkbox" id="enable_credits" name="enable_credits" value="1" <?php checked( ! empty( $options['enable_credits'] ) );  ?> />
								<?php _e( 'Credits fuer das Veroeffentlichen aktivieren.', $this->text_domain ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="cost_credit"><?php _e( 'Kosten pro Credit', $this->text_domain ); ?></label></th>
						<td>
							<input type="text" id="cost_credit" name="cost_credit" value="<?php echo ( empty( $options['cost_credit'] ) ) ? '0' : $options['cost_credit']; ?>" class="small-text" />
							<span class="description"><?php _e( 'Preis pro Credit.', $this->text_domain ); ?></span>
						</td>
					</tr>
					<tr>
						<th><label for="credits_per_week"><?php _e( 'Credits pro Woche', $this->text_domain ); ?></label></th>
						<td>
							<input type="text" id="credits_per_week" name="credits_per_week" value="<?php echo ( empty( $options['credits_per_week'] ) ) ? '0' : $options['credits_per_week']; ?>" class="small-text" />
							<span class="description"><?php _e( 'So viele Credits brauchst du fuer eine Woche Anzeige.', $this->text_domain ); ?></span>
						</td>
					</tr>
					<tr>
						<th><label for="signup_credits"><?php _e( 'Start-Credits', $this->text_domain ); ?></label></th>
						<td>
							<input type="text" id="signup_credits" name="signup_credits" value="<?php echo ( empty( $options['signup_credits'] ) ) ? '0' : $options['signup_credits']; ?>" class="small-text" />
							<span class="description"><?php _e( 'So viele Credits bekommt ein User bei der Registrierung.', $this->text_domain ); ?></span>
						</td>
					</tr>
					<tr>
						<th><label for="description"><?php _e( 'Beschreibung', $this->text_domain ); ?></label></th>
						<td>
							<textarea class="cf-full" id="description" name="description" rows="1" ><?php echo ( empty( $options['description'] ) ) ? '' : sanitize_text_field($options['description']); ?></textarea>
							<br />
							<span class="description"><?php _e( 'Beschreibung von Kosten und Laufzeiten fuer Anzeigen. Wird im Admin-Bereich angezeigt.', $this->text_domain ); ?></span>
						</td>
					</tr>
					<tr>
						<th><label for="mp_credit_meta_key"><?php _e( 'Credits-Meta-Key', $this->text_domain ); ?></label></th>
						<td>
							<input type="text" id="mp_credit_meta_key" name="mp_credit_meta_key" value="<?php echo empty( $options['mp_credit_meta_key'] ) ? 'cf_credit_amount' : esc_attr( $options['mp_credit_meta_key'] ); ?>" class="regular-text" />
							<span class="description"><?php _e( 'Interner Meta-Key fuer Credits pro Paketprodukt.', $this->text_domain ); ?></span>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Credit-Pakete', $this->text_domain ); ?></th>
						<td>
							<table class="widefat" id="cf-credit-packages-table" style="max-width:900px;">
								<thead>
									<tr>
										<th><?php _e( 'Paketname', $this->text_domain ); ?></th>
										<th style="width:120px;"><?php _e( 'Credits', $this->text_domain ); ?></th>
										<th style="width:140px;"><?php _e( 'Preis', $this->text_domain ); ?></th>
										<th style="width:240px;"><?php _e( 'Shop-Produkt', $this->text_domain ); ?></th>
										<th style="width:80px;"></th>
									</tr>
								</thead>
								<tbody>
									<?php if ( empty( $mp_credit_packages ) ) : ?>
										<tr class="cf-credit-package-row">
											<td><input type="text" name="mp_credit_packages[label][]" value="" class="regular-text" placeholder="<?php esc_attr_e( 'z. B. Poweruser', $this->text_domain ); ?>" /></td>
											<td><input type="number" min="1" name="mp_credit_packages[credits][]" value="" class="small-text" /></td>
											<td><input type="text" name="mp_credit_packages[price][]" value="" class="small-text" placeholder="19.90" /></td>
											<td>
												<input type="hidden" name="mp_credit_packages[product_id][]" value="0" />
												<span class="description"><?php _e( 'Wird automatisch erstellt', $this->text_domain ); ?></span>
											</td>
											<td><button type="button" class="button cf-remove-credit-package"><?php _e( 'Entfernen', $this->text_domain ); ?></button></td>
										</tr>
									<?php else : ?>
										<?php foreach ( $mp_credit_packages as $package ) : ?>
											<?php $package_product_id = empty( $package['product_id'] ) ? 0 : absint( $package['product_id'] ); ?>
											<tr class="cf-credit-package-row">
												<td><input type="text" name="mp_credit_packages[label][]" value="<?php echo esc_attr( $package['label'] ); ?>" class="regular-text" /></td>
												<td><input type="number" min="1" name="mp_credit_packages[credits][]" value="<?php echo esc_attr( $package['credits'] ); ?>" class="small-text" /></td>
												<td><input type="text" name="mp_credit_packages[price][]" value="<?php echo esc_attr( $package['price'] ); ?>" class="small-text" /></td>
												<td>
													<input type="hidden" name="mp_credit_packages[product_id][]" value="<?php echo esc_attr( $package_product_id ); ?>" />
													<?php if ( $package_product_id > 0 && get_post( $package_product_id ) ) : ?>
														<a class="button button-secondary" href="<?php echo esc_url( get_edit_post_link( $package_product_id ) ); ?>"><?php _e( 'Produkt bearbeiten', $this->text_domain ); ?></a>
													<?php else : ?>
														<span class="description"><?php _e( 'Wird beim Speichern erstellt', $this->text_domain ); ?></span>
													<?php endif; ?>
												</td>
												<td><button type="button" class="button cf-remove-credit-package"><?php _e( 'Entfernen', $this->text_domain ); ?></button></td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
							<p style="margin-top:10px;">
								<button type="button" class="button" id="cf-add-credit-package"><?php _e( 'Paket hinzufuegen', $this->text_domain ); ?></button>
							</p>
							<span class="description"><?php _e( 'Jedes Paket erzeugt ein eigenes Shop-Produkt. So kannst Du Preis und Credits im Kleinanzeigen-Dashboard pflegen.', $this->text_domain ); ?></span>
						</td>
					</tr>
				</table>
				<script type="text/javascript">
					(function($){
						$(function(){
							var tableBody = $('#cf-credit-packages-table tbody');
							$('#cf-add-credit-package').on('click', function(){
								var row = '' +
								'<tr class="cf-credit-package-row">' +
								'<td><input type="text" name="mp_credit_packages[label][]" value="" class="regular-text" placeholder="z. B. Poweruser" /></td>' +
								'<td><input type="number" min="1" name="mp_credit_packages[credits][]" value="" class="small-text" /></td>' +
								'<td><input type="text" name="mp_credit_packages[price][]" value="" class="small-text" placeholder="19.90" /></td>' +
								'<td><input type="hidden" name="mp_credit_packages[product_id][]" value="0" /><span class="description"><?php echo esc_js( __( 'Wird beim Speichern erstellt', $this->text_domain ) ); ?></span></td>' +
								'<td><button type="button" class="button cf-remove-credit-package"><?php echo esc_js( __( 'Entfernen', $this->text_domain ) ); ?></button></td>' +
								'</tr>';
								tableBody.append(row);
							});

							tableBody.on('click', '.cf-remove-credit-package', function(){
								$(this).closest('tr').remove();
								if (!tableBody.find('tr').length) {
									$('#cf-add-credit-package').trigger('click');
								}
							});
						});
					})(jQuery);
				</script>
			</div>
		</div>

		<div class="postbox">
			<h3 class='hndle'><span><?php _e( 'Frontend-Ansicht (User-Dashboard)', $this->text_domain ) ?></span></h3>
			<div class="inside">
				<table class="form-table">
					<tr>
						<th><label for="dashboard_show_credit_status"><?php _e( 'Credit-Status anzeigen', $this->text_domain ); ?></label></th>
						<td>
							<label>
								<input type="checkbox" id="dashboard_show_credit_status" name="dashboard_show_credit_status" value="1" <?php checked( ! empty( $options['dashboard_show_credit_status'] ) ); ?> />
								<?php _e( 'Zeige verfuegbare Credits in einer Card im Dashboard (Meine Anzeigen).', $this->text_domain ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="dashboard_credit_warning_threshold"><?php _e( 'Credit-Warnung ab', $this->text_domain ); ?></label></th>
						<td>
							<input type="number" min="0" id="dashboard_credit_warning_threshold" name="dashboard_credit_warning_threshold" value="<?php echo empty( $options['dashboard_credit_warning_threshold'] ) ? '5' : absint( $options['dashboard_credit_warning_threshold'] ); ?>" class="small-text" />
							<span class="description"><?php _e( 'Zeige eine rote Warnung wenn User weniger als X Credits hat.', $this->text_domain ); ?></span>
						</td>
					</tr>
					<tr>
						<th><label for="featured_credit_package_id"><?php _e( 'Featured Credit-Paket', $this->text_domain ); ?></label></th>
						<td>
							<select id="featured_credit_package_id" name="featured_credit_package_id" class="regular-text">
								<option value="0"><?php _e( '- Keines -', $this->text_domain ); ?></option>
								<?php if ( ! empty( $mp_credit_packages ) ) : ?>
									<?php foreach ( $mp_credit_packages as $pkg ) : ?>
										<?php $pkg_id = empty( $pkg['product_id'] ) ? 0 : absint( $pkg['product_id'] ); ?>
										<?php if ( $pkg_id > 0 ) : ?>
											<option value="<?php echo esc_attr( $pkg_id ); ?>" <?php selected( ! empty( $options['featured_credit_package_id'] ) && $options['featured_credit_package_id'] == $pkg_id ); ?>><?php echo esc_html( $pkg['label'] ); ?> (<?php echo esc_html( $pkg['credits'] ); ?> Credits)</option>
										<?php endif; ?>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
							<span class="description"><?php _e( 'Dieses Paket wird hervorgehoben und prominent im User-Dashboard angezeigt.', $this->text_domain ); ?></span>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<div class="postbox">
			<h3 class='hndle'><span><?php _e( 'Ablauf-Neustart', $this->text_domain ) ?></span></h3>
			<div class="inside">
				<table class="form-table">
					<tr>
						<th><label for="expired_restart_mode"><?php _e( 'Erneutes Starten nach Ablauf', $this->text_domain ); ?></label></th>
						<td>
							<select id="expired_restart_mode" name="expired_restart_mode">
								<option value="none" <?php selected( $expired_restart_mode, 'none' ); ?>><?php _e( 'Nicht erlaubt', $this->text_domain ); ?></option>
								<option value="free" <?php selected( $expired_restart_mode, 'free' ); ?>><?php _e( 'Gratis', $this->text_domain ); ?></option>
								<option value="credits" <?php selected( $expired_restart_mode, 'credits' ); ?>><?php _e( 'Credit-pflichtig', $this->text_domain ); ?></option>
							</select>
							<br /><span class="description"><?php _e( 'Steuert, wie abgelaufene Anzeigen wieder aktiviert werden koennen.', $this->text_domain ); ?></span>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<div class="postbox">
			<h3 class='hndle'><span><?php _e( 'AGB-Text', $this->text_domain ) ?></span></h3>
			<div class="inside">

				<table class="form-table">
					<tr>
						<th>
							<label for="tos_txt"><?php _e('AGB-Text', $this->text_domain ) ?></label>
						</th>
						<td>
							<?php
							wp_editor(
								empty( $options['tos_txt'] ) ? '' : wp_kses_post( $options['tos_txt'] ),
								'cf_tos_txt_editor',
								array(
									'textarea_name' => 'tos_txt',
									'textarea_rows' => 12,
									'media_buttons' => false,
									'teeny' => false,
									'tinymce' => array(
										'toolbar1' => 'bold,italic,underline,bullist,numlist,link,unlink,undo,redo',
										'toolbar2' => '',
										'block_formats' => 'Absatz=p;Ueberschrift 3=h3;Ueberschrift 4=h4',
									),
									'quicktags' => array(
										'buttons' => 'strong,em,link,ul,ol,li,close',
									),
								)
							);
							?>
							<br />
							<span class="description"><?php _e( 'Text fuer die AGB.', $this->text_domain ); ?></span>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<p class="submit">
			<?php wp_nonce_field('verify'); ?>
			<input type="hidden" name="key" value="payments" />
			<input type="submit" class="button-primary" name="save" value="<?php _e( 'Aenderungen speichern', $this->text_domain ); ?>" />
		</p>

	</form>

</div>