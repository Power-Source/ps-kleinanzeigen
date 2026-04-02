<?php if (!defined('ABSPATH')) die('No direct access allowed!');

$options = $this->get_options( 'frontend' );
$general_options = $this->get_options( 'general' );

$archive_intro = isset( $options['archive_intro'] ) ? $options['archive_intro'] : '';
$user_intro = isset( $options['user_intro'] ) ? $options['user_intro'] : '';
$trust_block_content = isset( $options['trust_block_content'] ) ? $options['trust_block_content'] : '';
if ( '' === trim( $trust_block_content ) && isset( $general_options['trust_block_content'] ) ) {
	$trust_block_content = $general_options['trust_block_content'];
}
$archive_auto_restore = isset( $options['archive_auto_restore'] ) ? (int) $options['archive_auto_restore'] : 1;
$archive_columns = isset( $options['archive_columns'] ) ? (int) $options['archive_columns'] : 3;
$archive_show_filter_tools = isset( $options['archive_show_filter_tools'] ) ? (int) $options['archive_show_filter_tools'] : 1;
$archive_show_quickview = isset( $options['archive_show_quickview'] ) ? (int) $options['archive_show_quickview'] : 1;
$archive_show_favorites = isset( $options['archive_show_favorites'] ) ? (int) $options['archive_show_favorites'] : 1;
$archive_show_contact_cta = isset( $options['archive_show_contact_cta'] ) ? (int) $options['archive_show_contact_cta'] : 1;
$archive_show_reserved_badge = isset( $options['archive_show_reserved_badge'] ) ? (int) $options['archive_show_reserved_badge'] : 1;

$single_show_gallery = isset( $options['single_show_gallery'] ) ? (int) $options['single_show_gallery'] : 1;
$single_show_seller_card = isset( $options['single_show_seller_card'] ) ? (int) $options['single_show_seller_card'] : 1;
$single_show_sticky_actions = isset( $options['single_show_sticky_actions'] ) ? (int) $options['single_show_sticky_actions'] : 1;
$single_show_trust_block = isset( $options['single_show_trust_block'] ) ? (int) $options['single_show_trust_block'] : 1;
$single_show_reserved_badge = isset( $options['single_show_reserved_badge'] ) ? (int) $options['single_show_reserved_badge'] : 1;

$user_show_favorites_tab = isset( $options['user_show_favorites_tab'] ) ? (int) $options['user_show_favorites_tab'] : 1;
$user_allow_reserve_toggle = isset( $options['user_allow_reserve_toggle'] ) ? (int) $options['user_allow_reserve_toggle'] : 1;
?>

<div class="wrap">

	<?php $this->render_admin( 'navigation', array( 'page' => 'classifieds_settings', 'tab' => 'frontend' ) ); ?>
	<?php $this->render_admin( 'message' ); ?>

	<h1><?php _e( 'Frontend-Einstellungen', $this->text_domain ); ?></h1>

	<div class="postbox">
		<h3 class="hndle"><span><?php _e( 'Szenario-Presets', $this->text_domain ); ?></span></h3>
		<div class="inside">
			<p><?php _e( 'Mit einem Klick die wichtigsten Frontend-Schalter passend fuer Deinen Einsatzfall vorbelegen.', $this->text_domain ); ?></p>
			<div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
				<label for="cf_frontend_preset"><strong><?php _e( 'Preset waehlen', $this->text_domain ); ?></strong></label>
				<select id="cf_frontend_preset">
					<option value=""><?php _e( 'Bitte auswaehlen', $this->text_domain ); ?></option>
					<option value="b2c"><?php _e( 'B2C Marktplatz (schnell, kompakt)', $this->text_domain ); ?></option>
					<option value="premium"><?php _e( 'Premium/hochpreisig (mehr Vertrauen)', $this->text_domain ); ?></option>
					<option value="community"><?php _e( 'Community/Forum (fokussiert, reduziert)', $this->text_domain ); ?></option>
				</select>
				<button type="button" class="button" id="cf_apply_frontend_preset"><?php _e( 'Preset anwenden', $this->text_domain ); ?></button>
			</div>
			<p class="description" style="margin-top:8px;"><?php _e( 'Hinweis: Es werden nur Schalter/Layouts gesetzt. Deine Textinhalte im Editor bleiben unveraendert.', $this->text_domain ); ?></p>
		</div>
	</div>

	<form action="#" method="post">
		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Archiv', $this->text_domain ); ?></span></h3>
			<div class="inside">
				<p><?php _e( 'Optionen fuer die Uebersichtsseite mit allen Anzeigen.', $this->text_domain ); ?></p>
				<table class="form-table">
					<tr>
						<th><label for="archive_intro"><?php _e( 'Info-Text ueber den Anzeigen', $this->text_domain ); ?></label></th>
						<td>
							<?php
							wp_editor(
								$archive_intro,
								'archive_intro',
								array(
									'textarea_name' => 'archive_intro',
									'media_buttons' => true,
									'teeny'         => false,
									'textarea_rows' => 5,
								)
							);
							?>
						</td>
					</tr>
					<tr>
						<th><label for="archive_auto_restore"><?php _e( 'Letzten Filter automatisch laden', $this->text_domain ); ?></label></th>
						<td>
							<input type="hidden" name="archive_auto_restore" value="0" />
							<label>
								<input type="checkbox" id="archive_auto_restore" name="archive_auto_restore" value="1" <?php checked( 1 === $archive_auto_restore ); ?> />
								<span class="description"><?php _e( 'Wenn aktiv, werden die zuletzt genutzten Filter beim naechsten Aufruf automatisch wiederhergestellt.', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="archive_columns"><?php _e( 'Spalten im Anzeigen-Grid (Desktop)', $this->text_domain ); ?></label></th>
						<td>
							<select id="archive_columns" name="archive_columns">
								<option value="2" <?php selected( 2, $archive_columns ); ?>><?php _e( '2 Spalten', $this->text_domain ); ?></option>
								<option value="3" <?php selected( 3, $archive_columns ); ?>><?php _e( '3 Spalten', $this->text_domain ); ?></option>
								<option value="4" <?php selected( 4, $archive_columns ); ?>><?php _e( '4 Spalten', $this->text_domain ); ?></option>
							</select>
							<p class="description"><?php _e( 'Hilft je nach Zielgruppe: kompakt (4), ausgewogen (3), gross (2).', $this->text_domain ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Filter-Tools anzeigen', $this->text_domain ); ?></th>
						<td>
							<input type="hidden" name="archive_show_filter_tools" value="0" />
							<label>
								<input type="checkbox" name="archive_show_filter_tools" value="1" <?php checked( 1 === $archive_show_filter_tools ); ?> />
								<span class="description"><?php _e( 'Zeigt Filter merken/laden/loeschen und Auto-Restore-Toggle.', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Schnellansicht aktivieren', $this->text_domain ); ?></th>
						<td>
							<input type="hidden" name="archive_show_quickview" value="0" />
							<label>
								<input type="checkbox" name="archive_show_quickview" value="1" <?php checked( 1 === $archive_show_quickview ); ?> />
								<span class="description"><?php _e( 'Wenn aus, wird nur die normale Detailseite genutzt.', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Merken-Button im Archiv', $this->text_domain ); ?></th>
						<td>
							<input type="hidden" name="archive_show_favorites" value="0" />
							<label>
								<input type="checkbox" name="archive_show_favorites" value="1" <?php checked( 1 === $archive_show_favorites ); ?> />
								<span class="description"><?php _e( 'Blendet die Merken-Funktion in Karten ein oder aus.', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Kontakt-CTA im Archiv', $this->text_domain ); ?></th>
						<td>
							<input type="hidden" name="archive_show_contact_cta" value="0" />
							<label>
								<input type="checkbox" name="archive_show_contact_cta" value="1" <?php checked( 1 === $archive_show_contact_cta ); ?> />
								<span class="description"><?php _e( 'Zeigt pro Karte den direkten Kontakt-Button.', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Reserviert-Badge im Archiv', $this->text_domain ); ?></th>
						<td>
							<input type="hidden" name="archive_show_reserved_badge" value="0" />
							<label>
								<input type="checkbox" name="archive_show_reserved_badge" value="1" <?php checked( 1 === $archive_show_reserved_badge ); ?> />
								<span class="description"><?php _e( 'Zeigt bei reservierten Anzeigen ein sichtbares Badge in der Karte.', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Single', $this->text_domain ); ?></span></h3>
			<div class="inside">
				<p><?php _e( 'Optionen fuer die Detailseite einer Anzeige.', $this->text_domain ); ?></p>
				<table class="form-table">
					<tr>
						<th><label for="trust_block_content"><?php _e( 'Freier Info-Block', $this->text_domain ); ?></label></th>
						<td>
							<?php
							wp_editor(
								$trust_block_content,
								'trust_block_content',
								array(
									'textarea_name' => 'trust_block_content',
									'media_buttons' => true,
									'teeny'         => false,
									'textarea_rows' => 8,
								)
							);
							?>
							<p class="description"><?php _e( 'Hier kannst Du Text, Bilder, Logo oder Grafiken einfuegen. Der Block wird auf der Anzeigen-Detailseite ausgegeben.', $this->text_domain ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Galerie anzeigen', $this->text_domain ); ?></th>
						<td>
							<input type="hidden" name="single_show_gallery" value="0" />
							<label>
								<input type="checkbox" name="single_show_gallery" value="1" <?php checked( 1 === $single_show_gallery ); ?> />
								<span class="description"><?php _e( 'Zusatzbilder unter dem Hauptbild anzeigen.', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Vertrauens-/Infoblock anzeigen', $this->text_domain ); ?></th>
						<td>
							<input type="hidden" name="single_show_trust_block" value="0" />
							<label>
								<input type="checkbox" name="single_show_trust_block" value="1" <?php checked( 1 === $single_show_trust_block ); ?> />
								<span class="description"><?php _e( 'Blendet den freien Textblock und Standortbereich ein/aus.', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Verkäuferbox anzeigen', $this->text_domain ); ?></th>
						<td>
							<input type="hidden" name="single_show_seller_card" value="0" />
							<label>
								<input type="checkbox" name="single_show_seller_card" value="1" <?php checked( 1 === $single_show_seller_card ); ?> />
								<span class="description"><?php _e( 'Zeigt das Verkaeuferprofil auf der Detailseite.', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Sticky Actions mobil', $this->text_domain ); ?></th>
						<td>
							<input type="hidden" name="single_show_sticky_actions" value="0" />
							<label>
								<input type="checkbox" name="single_show_sticky_actions" value="1" <?php checked( 1 === $single_show_sticky_actions ); ?> />
								<span class="description"><?php _e( 'Kontakt/Merken/Teilen als mobile Bottom-Bar.', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Reserviert-Badge auf Detailseite', $this->text_domain ); ?></th>
						<td>
							<input type="hidden" name="single_show_reserved_badge" value="0" />
							<label>
								<input type="checkbox" name="single_show_reserved_badge" value="1" <?php checked( 1 === $single_show_reserved_badge ); ?> />
								<span class="description"><?php _e( 'Blendet auf der Anzeige gut sichtbar den Status Reserviert ein.', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'User', $this->text_domain ); ?></span></h3>
			<div class="inside">
				<p><?php _e( 'Optionen fuer den Nutzerbereich (z. B. Meine Anzeigen).', $this->text_domain ); ?></p>
				<table class="form-table">
					<tr>
						<th><label for="user_intro"><?php _e( 'Info-Text im Userbereich', $this->text_domain ); ?></label></th>
						<td>
							<?php
							wp_editor(
								$user_intro,
								'user_intro',
								array(
									'textarea_name' => 'user_intro',
									'media_buttons' => true,
									'teeny'         => false,
									'textarea_rows' => 5,
								)
							);
							?>
							<p class="description"><?php _e( 'Optionaler Einleitungstext fuer den Userbereich.', $this->text_domain ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Tab "Gemerkte Anzeigen" anzeigen', $this->text_domain ); ?></th>
						<td>
							<input type="hidden" name="user_show_favorites_tab" value="0" />
							<label>
								<input type="checkbox" name="user_show_favorites_tab" value="1" <?php checked( 1 === $user_show_favorites_tab ); ?> />
								<span class="description"><?php _e( 'Nutzer sehen dann einen eigenen Bereich fuer gemerkte Anzeigen.', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Reservieren im Userbereich erlauben', $this->text_domain ); ?></th>
						<td>
							<input type="hidden" name="user_allow_reserve_toggle" value="0" />
							<label>
								<input type="checkbox" name="user_allow_reserve_toggle" value="1" <?php checked( 1 === $user_allow_reserve_toggle ); ?> />
								<span class="description"><?php _e( 'Nutzer koennen in Meine Anzeigen den Status Reserviert an/aus schalten.', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<p class="submit">
			<?php wp_nonce_field( 'verify' ); ?>
			<input type="hidden" name="key" value="frontend" />
			<input type="submit" class="button-primary" name="save" value="<?php _e( 'Aenderungen speichern', $this->text_domain ); ?>" />
		</p>
	</form>

	<script>
	(function() {
		const presetSelect = document.getElementById('cf_frontend_preset');
		const applyButton = document.getElementById('cf_apply_frontend_preset');

		if (!presetSelect || !applyButton) {
			return;
		}

		function setByName(name, value) {
			const field = document.querySelector('[name="' + name + '"]');
			if (!field) {
				return;
			}
			if (field.type === 'checkbox') {
				field.checked = !!value;
				return;
			}
			field.value = value;
		}

		const presets = {
			b2c: {
				archive_columns: '4',
				archive_auto_restore: true,
				archive_show_filter_tools: true,
				archive_show_quickview: true,
				archive_show_favorites: true,
				archive_show_contact_cta: true,
				archive_show_reserved_badge: true,
				single_show_gallery: true,
				single_show_trust_block: false,
				single_show_seller_card: true,
				single_show_sticky_actions: true,
				single_show_reserved_badge: true,
				user_allow_reserve_toggle: true,
				user_show_favorites_tab: true
			},
			premium: {
				archive_columns: '2',
				archive_auto_restore: true,
				archive_show_filter_tools: true,
				archive_show_quickview: true,
				archive_show_favorites: true,
				archive_show_contact_cta: true,
				archive_show_reserved_badge: true,
				single_show_gallery: true,
				single_show_trust_block: true,
				single_show_seller_card: true,
				single_show_sticky_actions: true,
				single_show_reserved_badge: true,
				user_allow_reserve_toggle: true,
				user_show_favorites_tab: true
			},
			community: {
				archive_columns: '3',
				archive_auto_restore: false,
				archive_show_filter_tools: true,
				archive_show_quickview: false,
				archive_show_favorites: true,
				archive_show_contact_cta: false,
				archive_show_reserved_badge: true,
				single_show_gallery: true,
				single_show_trust_block: false,
				single_show_seller_card: true,
				single_show_sticky_actions: false,
				single_show_reserved_badge: true,
				user_allow_reserve_toggle: true,
				user_show_favorites_tab: true
			}
		};

		applyButton.addEventListener('click', function() {
			const selected = presetSelect.value;
			if (!selected || !presets[selected]) {
				return;
			}

			Object.keys(presets[selected]).forEach(function(key) {
				setByName(key, presets[selected][key]);
			});
		});
	})();
	</script>

</div>
