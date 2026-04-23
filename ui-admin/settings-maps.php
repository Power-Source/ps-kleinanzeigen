<?php if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access allowed!' );
}

$options = (array) $this->get_options( 'maps' );
$maps_enable_regions_map = isset( $options['maps_enable_regions_map'] ) ? (int) $options['maps_enable_regions_map'] : 1;
$maps_show_single_region_map = isset( $options['maps_show_single_region_map'] ) ? (int) $options['maps_show_single_region_map'] : 1;
$maps_auto_geocode_regions = isset( $options['maps_auto_geocode_regions'] ) ? (int) $options['maps_auto_geocode_regions'] : 1;
$maps_region_preview_count = isset( $options['maps_region_preview_count'] ) ? (int) $options['maps_region_preview_count'] : 3;
$maps_region_preview_count = max( 1, min( 12, $maps_region_preview_count ) );
$maps_geocode_hint = isset( $options['maps_geocode_hint'] ) ? (string) $options['maps_geocode_hint'] : '';
$maps_single_region_map_label = isset( $options['maps_single_region_map_label'] ) && '' !== trim( (string) $options['maps_single_region_map_label'] )
	? (string) $options['maps_single_region_map_label']
	: __( 'Region auf der Karte', $this->text_domain );
$maps_default_zoom = isset( $options['maps_default_zoom'] ) ? (int) $options['maps_default_zoom'] : 6;
$maps_default_zoom = max( 2, min( 18, $maps_default_zoom ) );
// AgmMarkerReplacer wird in ps-maps nur im Frontend geladen (if !is_admin()).
$maps_available = class_exists( 'AgmMapModel' );
?>

<div class="wrap">

	<?php $this->render_admin( 'navigation', array( 'page' => 'classifieds_settings', 'tab' => 'maps' ) ); ?>
	<?php $this->render_admin( 'message' ); ?>

	<h1><?php _e( 'Karten-Einstellungen', $this->text_domain ); ?></h1>

	<?php if ( ! $maps_available ) : ?>
		<div class="notice notice-warning"><p><?php _e( 'PS Maps ist nicht aktiv oder nicht vollstaendig geladen. Bitte Plugin aktivieren, damit die Kartenfunktionen verfuegbar sind.', $this->text_domain ); ?></p></div>
	<?php endif; ?>

	<form action="#" method="post">
		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Regionen-Karte', $this->text_domain ); ?></span></h3>
			<div class="inside">
				<p><?php _e( 'Erzeugt eine Marker-Karte auf Basis der Taxonomie "Regionen" mit Anzeigenanzahl und Vorschau pro Marker.', $this->text_domain ); ?></p>
				<table class="form-table">
					<tr>
						<th><?php _e( 'Regionen-Karte aktivieren', $this->text_domain ); ?></th>
						<td>
							<input type="hidden" name="maps_enable_regions_map" value="0" />
							<label>
								<input type="checkbox" name="maps_enable_regions_map" value="1" <?php checked( 1 === $maps_enable_regions_map ); ?> />
								<span class="description"><?php _e( 'Aktiviert den Shortcode [cf_regions_map].', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Anzahl Anzeigen in Marker-Vorschau', $this->text_domain ); ?></th>
						<td>
							<input type="number" name="maps_region_preview_count" min="1" max="12" value="<?php echo esc_attr( $maps_region_preview_count ); ?>" />
							<p class="description"><?php _e( 'Wie viele Anzeigenlinks pro Region im Marker-Popup angezeigt werden.', $this->text_domain ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Karte in Single-Ansicht anzeigen', $this->text_domain ); ?></th>
						<td>
							<input type="hidden" name="maps_show_single_region_map" value="0" />
							<label>
								<input type="checkbox" name="maps_show_single_region_map" value="1" <?php checked( 1 === $maps_show_single_region_map ); ?> />
								<span class="description"><?php _e( 'Blendet die passende Regionskarte direkt in der Anzeigen-Detailseite bei Standort ein.', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Titel ueber Single-Karte', $this->text_domain ); ?></th>
						<td>
							<input type="text" name="maps_single_region_map_label" class="regular-text" value="<?php echo esc_attr( $maps_single_region_map_label ); ?>" />
							<p class="description"><?php _e( 'Wird direkt ueber der Karte in der Single-Ansicht angezeigt.', $this->text_domain ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Regionen automatisch geokodieren', $this->text_domain ); ?></th>
						<td>
							<input type="hidden" name="maps_auto_geocode_regions" value="0" />
							<label>
								<input type="checkbox" name="maps_auto_geocode_regions" value="1" <?php checked( 1 === $maps_auto_geocode_regions ); ?> />
								<span class="description"><?php _e( 'Wenn eine Region keine Koordinaten hat, wird beim Rendern versucht, sie ueber PS Maps zu geokodieren und als Term-Meta zu speichern.', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Geokodierungs-Zusatz', $this->text_domain ); ?></th>
						<td>
							<input type="text" name="maps_geocode_hint" class="regular-text" value="<?php echo esc_attr( $maps_geocode_hint ); ?>" placeholder="Deutschland" />
							<p class="description"><?php _e( 'Optionaler Zusatz fuer bessere Treffer, z.B. Land oder Bundesland.', $this->text_domain ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Standard-Zoom', $this->text_domain ); ?></th>
						<td>
							<input type="number" name="maps_default_zoom" min="2" max="18" value="<?php echo esc_attr( $maps_default_zoom ); ?>" />
							<p class="description"><?php _e( 'Default Zoom fuer die generierte Regionenkarte.', $this->text_domain ); ?></p>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Shortcode', $this->text_domain ); ?></span></h3>
			<div class="inside">
				<p><code>[cf_regions_map]</code></p>
				<p class="description"><?php _e( 'Optional: [cf_regions_map preview="5" zoom="7" regions="12,18"]', $this->text_domain ); ?></p>
			</div>
		</div>

		<p class="submit">
			<?php wp_nonce_field( 'verify' ); ?>
			<input type="hidden" name="key" value="maps" />
			<input type="hidden" name="page" value="classifieds_settings" />
			<input type="hidden" name="tab" value="maps" />
			<input type="submit" class="button-primary" name="save" value="<?php _e( 'Aenderungen speichern', $this->text_domain ); ?>" />
		</p>
	</form>

</div>
