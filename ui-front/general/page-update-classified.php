<?php
/**
* The template for displaying the Add/edit classified page.
* You can override this file in your active theme.
*
* @license GNU General Public License (Version 2 - GPLv2) {@link http://www.gnu.org/licenses/gpl-2.0.html}
*/
if (!defined('ABSPATH')) die('No direct access allowed!');

global $post, $post_ID;

$classified_data   = '';
$selected_cats  = '';
$error = get_query_var('cf_error');
$inline_errors = get_query_var( 'cf_inline_errors' );
if ( ! is_array( $inline_errors ) ) {
	$inline_errors = array();
}
$post_statuses = get_post_statuses(); // get the wp post status list

$options = $this->get_options('general');

$allowed_statuses['moderation'] = (empty($options['moderation']) ) ? array('publish' => 1, 'draft'=> 1 ) : $options['moderation']; // Get the ones we allow
$allowed_statuses = array_reverse(array_intersect_key($post_statuses, $allowed_statuses['moderation']) ); //return the reduced list

//Are we adding a Classified?
if(! isset($_REQUEST['post_id']) ){

	//Make an auto-draft so we have a post id to connect attachments to. Set global $post_ID so media editor can hook up. Watch the case
	$post_ID = wp_insert_post( array( 'post_title' => __( 'Auto Draft' ), 'post_type' => 'classifieds', 'post_status' => 'auto-draft', 'comment_status' => 'closed', 'ping_status' => 'closed'), true );
	$classified_data = get_post($post_ID, ARRAY_A );
	$classified_data['post_title'] = ''; //Have to have a title to insert the auto-save but we don't want it as final.
	$editing = false;
}

//Or are we editing a Classified?
elseif( isset($_REQUEST['post_id']) ) {
	$classified_data = get_post(  $_REQUEST['post_id'], ARRAY_A );
	$post_ID = $classified_data['ID'];
	$editing = true;
}
$post = get_post($post_ID);

if ( isset( $_POST['classified_data'] ) ) $classified_data = $_POST['classified_data'];

require_once(ABSPATH . 'wp-admin/includes/template.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/post.php');

$classified_content = (empty( $classified_data['post_content'] ) ) ? '' : $classified_data['post_content'];
$existing_gallery_ids = get_post_meta( (int) $post_ID, '_cf_gallery_ids', true );
if ( ! is_array( $existing_gallery_ids ) ) {
	$existing_gallery_ids = array();
}
$existing_gallery_ids = array_values( array_filter( array_map( 'absint', $existing_gallery_ids ) ) );

wp_enqueue_script('set-post-thumbnail');
?>

<!-- Begin Update Classifieds -->
<script type="text/javascript" src="<?php echo esc_url( $this->plugin_url . 'ui-front/js/cf-tagsinput.js' ); ?>" ></script>
<script type="text/javascript" src="<?php echo $this->plugin_url . 'ui-front/js/media-post.js'; ?>" ></script>
<script type="text/javascript">
window.cfGalleryEditor = <?php echo wp_json_encode( array(
	'ajaxUrl' => admin_url( 'admin-ajax.php' ),
	'nonce'   => wp_create_nonce( 'cf_manage_gallery' ),
	'postId'  => (int) $post_ID,
) ); ?>;
</script>
<script type="text/javascript" src="<?php echo $this->plugin_url . 'ui-front/js/ui-front.js'; ?>" >
</script>

<?php if ( !empty( $error ) ): ?>
<br /><div class="error"><?php echo $error . '<br />'; ?></div>
<?php endif; ?>


<div class="cf_update_form">

	<?php if ( isset( $msg ) ): ?>
	<div class="<?php echo $class; ?>" id="message">
		<p><?php echo $msg; ?></p>
	</div>
	<?php endif; ?>

	<form class="standard-form base" method="post" action="#" enctype="multipart/form-data" id="cf_update_form" >
		<input type="hidden" id="post_ID" name="classified_data[ID]" value="<?php echo ( empty( $classified_data['ID'] ) ) ? '' : $classified_data['ID']; ?>" />
		<input type="hidden" name="post_id" value="<?php echo ( empty( $classified_data['ID'] ) ) ? '' : $classified_data['ID']; ?>" />

		<?php if(post_type_supports('classifieds','title') ): ?>
		<div class="editfield <?php echo ! empty( $inline_errors['post_title'] ) ? 'cf-invalid-field' : ''; ?>">
			<label for="title"><?php _e( 'Titel', $this->text_domain ); ?></label>
			<input class="required" type="text" id="title" name="classified_data[post_title]" value="<?php echo ( empty( $classified_data['post_title'] ) ) ? '' : esc_attr($classified_data['post_title']); ?>" />
			<?php if ( ! empty( $inline_errors['post_title'] ) ) : ?>
			<p class="cf-inline-error"><?php echo esc_html( $inline_errors['post_title'] ); ?></p>
			<?php endif; ?>
			<p class="description"><?php _e( 'Gib hier den Titel ein.', $this->text_domain ); ?></p>
		</div>
		<?php endif; ?>

		<?php if(post_type_supports('classifieds','thumbnail') && current_theme_supports('post-thumbnails') ): ?>
		<div class="editfield <?php echo ! empty( $inline_errors['feature_image'] ) ? 'cf-invalid-field' : ''; ?>">
			<label for="image"><?php _e( 'Beitragsbild', $this->text_domain ); ?></label>

			<?php if(empty($options['media_manager']) ): ?>

			<?php if(has_post_thumbnail()) the_post_thumbnail('thumbnail'); ?><br />
			<script type="text/javascript">js_translate.image_chosen = '<?php _e("Kleinanzeigen-Bild ausgewählt", $this->text_domain); ?>';</script>
			<script type="text/javascript">js_translate.image_preview_ready = '<?php _e("Vorschau aktualisiert", $this->text_domain); ?>';</script>
			<span class="upload-button">

				<?php $class = ( empty($options['field_image_req']) && !has_post_thumbnail() ) ? 'required' : ''; ?>

				<input type="file" name="feature_image" size="1" id="image" class="<?php echo $class; ?>" accept="image/*" />
				<button type="button" class="button"><?php _e('Kleinanzeigen-Bild festlegen', $this->text_domain); ?></button>
			</span>
			<p class="description"><?php _e( 'Zieh ein Bild hier rein oder wähle eins aus. Formate: JPG, PNG, WebP.', $this->text_domain ); ?></p>
			<div class="cf-image-preview" data-for="image"></div>
			<div class="cf-gallery-upload">
				<label for="feature_gallery"><?php _e( 'Weitere Bilder', $this->text_domain ); ?></label>
				<input type="file" id="feature_gallery" class="cf-gallery-picker" name="feature_gallery[]" accept="image/*" multiple />
				<p class="description"><?php _e( 'Du kannst mehrere Bilder auf einmal oder nacheinander (z. B. Kamera) hinzufuegen.', $this->text_domain ); ?></p>
				<div class="cf-gallery-preview" data-for="feature_gallery"></div>
			</div>

			<?php if ( ! empty( $existing_gallery_ids ) ) : ?>
			<div class="cf-existing-gallery">
				<label><?php _e( 'Bereits hochgeladene Bilder', $this->text_domain ); ?></label>
				<p class="description"><?php _e( 'Bilder verwalten.', $this->text_domain ); ?></p>
				<div class="cf-existing-gallery-grid">
					<?php foreach ( $existing_gallery_ids as $existing_gallery_id ) : ?>
						<?php $existing_gallery_image = wp_get_attachment_image_src( (int) $existing_gallery_id, 'thumbnail' ); ?>
						<?php if ( empty( $existing_gallery_image[0] ) ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<div class="cf-existing-gallery-item" data-attachment-id="<?php echo esc_attr( $existing_gallery_id ); ?>">
							<img src="<?php echo esc_url( $existing_gallery_image[0] ); ?>" alt="<?php esc_attr_e( 'Galeriebild', $this->text_domain ); ?>" class="cf-gallery-preview-img" />
							<button type="button" class="cf-gallery-remove-existing" aria-label="<?php esc_attr_e( 'Bild entfernen', $this->text_domain ); ?>" data-attachment-id="<?php echo esc_attr( $existing_gallery_id ); ?>">&#128465;</button>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>
			<?php if ( ! empty( $inline_errors['feature_image'] ) ) : ?>
			<p class="cf-inline-error"><?php echo esc_html( $inline_errors['feature_image'] ); ?></p>
			<?php endif; ?>
			<br />

			<?php else: ?>

			<div id="postimagediv">
				<div class="inside">
					<?php
					$thumbnail_id = get_post_meta( $post_ID, '_thumbnail_id', true );
					echo _wp_post_thumbnail_html($thumbnail_id, $post_ID);
					?>
				</div>
			</div>
			<?php endif; ?>

		</div>
		<?php endif; ?>

		<?php if(post_type_supports('classifieds','editor') ): ?>
		<div class="editfield cf-editor-field <?php echo ! empty( $inline_errors['post_content'] ) ? 'cf-invalid-field' : ''; ?>">
		<label for="classifiedcontent"><?php _e( 'Deine Kleinanzeige', $this->text_domain ); ?></label>
		<div class="cf-simple-editor-toolbar" data-markup-target="classifiedcontent">
			<button type="button" class="button" data-markup-action="bold"><strong>B</strong></button>
			<button type="button" class="button" data-markup-action="italic"><em>I</em></button>
			<button type="button" class="button" data-markup-action="ul"><?php _e( 'Liste', $this->text_domain ); ?></button>
			<button type="button" class="button" data-markup-action="link"><?php _e( 'Link', $this->text_domain ); ?></button>
		</div>
		<textarea id="classifiedcontent" name="classified_data[post_content]" rows="12" class="required cf-simple-editor-textarea"><?php echo esc_textarea( $classified_content ); ?></textarea>
		<?php if ( ! empty( $inline_errors['post_content'] ) ) : ?>
		<p class="cf-inline-error"><?php echo esc_html( $inline_errors['post_content'] ); ?></p>
		<?php endif; ?>

		<p class="description"><?php _e( 'Beschreibe Deine Kleinanzeige im Detail.', $this->text_domain ); ?></p>
		</div>
		<?php endif; ?>

		<?php if(post_type_supports('classifieds','excerpt') ): ?>
		<div class="editfield alt">
			<label for="excerpt"><?php _e( 'Auszug', $this->text_domain ); ?></label>
			<textarea id="excerpt" name="classified_data[post_excerpt]" rows="2" ><?php echo (empty( $classified_data['post_excerpt'] ) ) ? '' : esc_textarea($classified_data['post_excerpt']); ?></textarea>
			<p class="description"><?php _e( 'Ein kurzer Auszug aus Deiner Kleinanzeige.', $this->text_domain ); ?></p>
		</div>
		<?php endif; ?>

		<?php
		//get related hierarchical taxonomies
		$taxonomies = get_object_taxonomies('classifieds', 'objects');
		//Loop through the taxonomies that apply
		foreach($taxonomies as $taxonomy):
		if( ! $taxonomy->hierarchical) continue;
		$tax_name = $taxonomy->name;
		$labels = $taxonomy->labels;
		//Get this Taxonomies terms
		if ( isset( $_POST['tax_input'][ $tax_name ] ) && is_array( $_POST['tax_input'][ $tax_name ] ) ) {
			$selected_cats = array_values( array_filter( array_map( 'absint', (array) $_POST['tax_input'][ $tax_name ] ) ) );
		} else {
			$selected_cats = array_values( wp_get_post_terms($classified_data['ID'], $tax_name, array('fields' => 'ids') ) );
		}
		$all_terms = get_terms(
			array(
				'taxonomy'   => $tax_name,
				'hide_empty' => false,
			)
		);
		$parent_terms = array();
		$children_by_parent = array();
		if ( ! is_wp_error( $all_terms ) && ! empty( $all_terms ) ) {
			foreach ( $all_terms as $term_obj ) {
				if ( (int) $term_obj->parent === 0 ) {
					$parent_terms[] = $term_obj;
				} else {
					$children_by_parent[ (int) $term_obj->parent ][] = array(
						'id'   => (int) $term_obj->term_id,
						'name' => (string) $term_obj->name,
					);
				}
			}
		}
		$selected_parent_id = 0;
		$selected_child_id = 0;
		foreach ( $selected_cats as $selected_term_id ) {
			$selected_term = get_term( (int) $selected_term_id, $tax_name );
			if ( ! $selected_term || is_wp_error( $selected_term ) ) {
				continue;
			}
			if ( (int) $selected_term->parent === 0 ) {
				$selected_parent_id = (int) $selected_term->term_id;
			} else {
				$selected_child_id = (int) $selected_term->term_id;
				if ( $selected_parent_id <= 0 ) {
					$selected_parent_id = (int) $selected_term->parent;
				}
			}
			if ( $selected_parent_id > 0 && $selected_child_id > 0 ) {
				break;
			}
		}

		?>

		<?php $tax_error_key = 'tax_' . $tax_name; ?>
		<div id="taxonomy-<?php echo $tax_name; ?>" class="cf_taxonomydiv <?php echo ! empty( $inline_errors[ $tax_error_key ] ) ? 'cf-invalid-field' : ''; ?>">
			<label><?php echo $labels->all_items; ?></label>

			<div id="<?php echo $tax_name; ?>_all" class="cf_tax_panel cf-taxonomy-panel-modern">
				<?php
				$name = ( $tax_name == 'category' ) ? 'post_category' : 'tax_input[' . $tax_name . ']';
				echo "<input type='hidden' name='{$name}[]' value='0' />"; 		// Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
				?>
				<div class="cf-taxonomy-select-pair"
					 data-taxonomy="<?php echo esc_attr( $tax_name ); ?>"
					 data-child-map="<?php echo esc_attr( wp_json_encode( $children_by_parent ) ); ?>">
					<select id="cf-<?php echo esc_attr( $tax_name ); ?>-parent" class="cf-tax-parent-select" required>
						<option value=""><?php _e( 'Auswaehlen', $this->text_domain ); ?></option>
						<?php foreach ( $parent_terms as $parent_term ) : ?>
							<option value="<?php echo esc_attr( $parent_term->term_id ); ?>" <?php selected( (int) $selected_parent_id, (int) $parent_term->term_id ); ?>><?php echo esc_html( $parent_term->name ); ?></option>
						<?php endforeach; ?>
					</select>

					<select id="cf-<?php echo esc_attr( $tax_name ); ?>-child" class="cf-tax-child-select" <?php disabled( $selected_parent_id <= 0 ); ?>>
						<option value=""><?php _e( 'Auswaehlen', $this->text_domain ); ?></option>
						<?php if ( $selected_parent_id > 0 && ! empty( $children_by_parent[ $selected_parent_id ] ) ) : ?>
							<?php foreach ( $children_by_parent[ $selected_parent_id ] as $child_term ) : ?>
								<option value="<?php echo esc_attr( $child_term['id'] ); ?>" <?php selected( (int) $selected_child_id, (int) $child_term['id'] ); ?>><?php echo esc_html( $child_term['name'] ); ?></option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>

					<input type="hidden" class="cf-tax-input-parent" name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( $selected_parent_id ); ?>" />
					<input type="hidden" class="cf-tax-input-child" name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( $selected_child_id ); ?>" />
				</div>
			</div>
			<?php if ( ! empty( $inline_errors[ $tax_error_key ] ) ) : ?>
			<p class="cf-inline-error"><?php echo esc_html( $inline_errors[ $tax_error_key ] ); ?></p>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>

		<?php
		//Loop through the taxonomies that apply
		foreach($taxonomies as $tag):
		if( $tag->hierarchical) continue;

		$tag_name = $tag->name;
		$labels = $tag->labels;

		//Get this Taxonomies terms
		$tag_list = strip_tags(get_the_term_list( $classified_data['ID'], $tag_name, '', ',', '' ));

		?>

		<div class="cf_taxonomy">
			<div id="<?php echo $tag_name; ?>-checklist" class="tagchecklist">
				<label><?php echo $labels->name; ?>
					<input id="tag_<?php echo $tag_name; ?>" name="tag_input[<?php echo $tag_name; ?>]" type="text" value="<?php echo $tag_list?>" />
				</label>
				<span class="description"><?php echo $labels->add_or_remove_items; ?></span>
			</div>

			<script type="text/javascript" > jQuery('#tag_<?php echo $tag_name; ?>').tagsInput({width:'auto', height:'150px', defaultText: '<?php _e("Füge einen Tag hinzu", $this->text_domain); ?>'}); </script>
		</div>
		<?php endforeach; ?>

		<div class="clear"><br /></div>

		<div class="editfield cf-status-field" >
			<label for="title"><?php _e( 'Status', $this->text_domain ); ?></label>
			<div id="status-box">
				<select name="classified_data[post_status]" id="classified_data[post_status]">
					<?php
					foreach($allowed_statuses as $key => $value): ?>

					<option value="<?php echo $key; ?>" <?php selected( ! empty($classified_data['post_status'] ) && $key == $classified_data['post_status'] ); ?> ><?php echo $value; ?></option>

					<?php endforeach; ?>
				</select>
			</div>
			<p class="description"><?php _e( 'Wähle einen Status für Deine Kleinanzeige.', $this->text_domain ); ?></p>
		</div>

		<!-- Anzeigen-Details (Laufzeit & Preis) -->
		<?php if ( isset( $post ) && $post->ID ) : ?>
			<?php
			$duration = get_post_meta( $post->ID, '_cf_duration', true );
			$cost     = get_post_meta( $post->ID, '_cf_cost', true );
				$expiration_timestamp = (int) get_post_meta( $post->ID, '_expiration_date', true );
				$now_timestamp = current_time( 'timestamp' );
				$days_remaining = ( $expiration_timestamp > $now_timestamp ) ? (int) ceil( ( $expiration_timestamp - $now_timestamp ) / DAY_IN_SECONDS ) : 0;
				$has_remaining_runtime = ( $days_remaining > 0 );
			$options  = get_option( CF_OPTIONS_NAME );
				$payments_options = isset( $options['payments'] ) && is_array( $options['payments'] ) ? $options['payments'] : array();
				$expired_restart_mode = isset( $payments_options['expired_restart_mode'] ) ? sanitize_key( (string) $payments_options['expired_restart_mode'] ) : 'credits';
				if ( ! in_array( $expired_restart_mode, array( 'none', 'free', 'credits' ), true ) ) {
					$expired_restart_mode = 'credits';
				}
				$is_expired_listing = ( $expiration_timestamp > 0 && $days_remaining <= 0 );
				$duration_label = $is_expired_listing ? __( 'Anzeige erneut starten fuer', $this->text_domain ) : __( 'Laufzeit', $this->text_domain );
			$dur_opts = isset( $options['general']['duration_options'] ) ? $options['general']['duration_options'] : array( '1 Woche', '2 Wochen', '4 Wochen', '8 Wochen' );
				if ( empty( $duration ) || '0' === (string) $duration ) {
					$duration = ! empty( $dur_opts ) ? reset( $dur_opts ) : '';
				}
			?>
			<div class="classified-custom-fields">
				<h3><?php _e( 'Anzeigen-Details', $this->text_domain ); ?></h3>

				<?php if ( $has_remaining_runtime ) : ?>
				<div class="field">
					<label><?php _e( 'Restlaufzeit', $this->text_domain ); ?></label>
					<p class="description"><?php printf( __( '%d Tage.', $this->text_domain ), $days_remaining ); ?></p>
				</div>
				<div class="field cf-listing-actions">
					<label><?php _e( 'Anzeige verwalten', $this->text_domain ); ?></label>
					<div class="cf-listing-action-buttons">
						<button type="submit" name="cf_end_listing" value="1" class="button cf-listing-action-end"><?php _e( 'Anzeige beenden', $this->text_domain ); ?></button>
						<button type="submit" name="cf_delete_listing" value="1" class="button cf-listing-action-delete" onclick="return window.confirm('<?php echo esc_js( __( 'Anzeige wirklich loeschen?', $this->text_domain ) ); ?>');"><?php _e( 'Anzeige loeschen', $this->text_domain ); ?></button>
					</div>
				</div>
				<?php else : ?>
				<div class="field">
					<label><?php echo esc_html( $duration_label ); ?></label>
					<select name="duration">
						<?php foreach ( $dur_opts as $opt ) : ?>
						<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $duration, $opt ); ?>><?php echo esc_html( $opt ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ( $expiration_timestamp > 0 ) : ?>
						<?php if ( $days_remaining > 0 ) : ?>
							<p class="description"><?php printf( __( 'Restlaufzeit: %d Tage.', $this->text_domain ), $days_remaining ); ?></p>
						<?php else : ?>
							<p class="description"><?php _e( 'Anzeige ist abgelaufen.', $this->text_domain ); ?></p>
							<?php if ( 'free' === $expired_restart_mode ) : ?>
								<p class="description"><?php _e( 'Die Anzeige kann gratis mit der gewaehlten Laufzeit erneut gestartet werden.', $this->text_domain ); ?></p>
							<?php elseif ( 'credits' === $expired_restart_mode ) : ?>
								<p class="description"><?php _e( 'Die Anzeige wird mit der gewaehlten Laufzeit erneut gestartet und dabei nach deinem Credit-Modell berechnet.', $this->text_domain ); ?></p>
							<?php else : ?>
								<p class="description"><?php _e( 'Ein erneutes Starten nach Ablauf ist aktuell deaktiviert.', $this->text_domain ); ?></p>
							<?php endif; ?>
						<?php endif; ?>
					<?php endif; ?>
				</div>
				<div class="field cf-listing-actions">
					<label><?php _e( 'Anzeige verwalten', $this->text_domain ); ?></label>
					<div class="cf-listing-action-buttons">
						<button type="submit" name="cf_delete_listing" value="1" class="button cf-listing-action-delete" onclick="return window.confirm('<?php echo esc_js( __( 'Anzeige wirklich loeschen?', $this->text_domain ) ); ?>');"><?php _e( 'Anzeige loeschen', $this->text_domain ); ?></button>
					</div>
				</div>
				<?php endif; ?>

				<div class="field <?php echo ! empty( $inline_errors['cost'] ) ? 'cf-invalid-field' : ''; ?>">
					<label><?php _e( 'Preis', $this->text_domain ); ?></label>
					<input type="text" name="cost" value="<?php echo esc_attr( $cost ); ?>" />
					<?php if ( ! empty( $inline_errors['cost'] ) ) : ?>
					<p class="cf-inline-error"><?php echo esc_html( $inline_errors['cost'] ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( !empty( $error ) ): ?>
		<br /><div class="error"><?php echo $error . '<br />'; ?></div>
		<?php endif; ?>

		<div class="submit cf-submit-row">
			<?php wp_nonce_field( 'verify' ); ?>
			<input type="submit" value="<?php _e( 'Änderungen speichern', $this->text_domain ); ?>" name="update_classified" class="cf-btn-primary">

			<input type="button" value="<?php _e( 'Abbrechen', $this->text_domain ); ?>" class="cf-btn-secondary" onclick="location.href='<?php echo get_permalink($this->my_classifieds_page_id); ?>'">
		</div>
	</form>
</div><!-- .cf_update_form -->
<!-- End Update Classifieds -->
<script type="text/javascript">
	jQuery(function($) {
		function initDependentTaxonomySelects() {
			$('.cf-taxonomy-select-pair').each(function() {
				var $pair = $(this);
				var $parentSelect = $pair.find('.cf-tax-parent-select');
				var $childSelect = $pair.find('.cf-tax-child-select');
				var $parentInput = $pair.find('.cf-tax-input-parent');
				var $childInput = $pair.find('.cf-tax-input-child');
				var childMapRaw = $pair.attr('data-child-map') || '{}';
				var childMap = {};

				try {
					childMap = JSON.parse(childMapRaw);
				} catch (e) {
					childMap = {};
				}

				function renderChildren(parentId, selectedChildId) {
					var key = String(parentId || '');
					var childOptions = childMap[key] || [];
					var hasParent = !!parentId;

					$childSelect.empty();
					if (!hasParent) {
						$childSelect.append($('<option />').val('').text('<?php echo esc_js( __( 'Auswaehlen', $this->text_domain ) ); ?>'));
						$childSelect.prop('disabled', true);
						$childSelect.prop('required', false);
						$childInput.val('');
						return;
					}

					$childSelect.append($('<option />').val('').text('<?php echo esc_js( __( 'Auswaehlen', $this->text_domain ) ); ?>'));
					$.each(childOptions, function(_, child) {
						$childSelect.append($('<option />').val(String(child.id)).text(child.name));
					});
					$childSelect.prop('disabled', false);
					$childSelect.prop('required', childOptions.length > 0);

					if (selectedChildId) {
						$childSelect.val(String(selectedChildId));
						$childInput.val(String(selectedChildId));
					} else {
						$childSelect.val('');
						$childInput.val('');
					}
				}

				$parentSelect.on('change', function() {
					var parentVal = $(this).val();
					$parentInput.val(parentVal || '');
					renderChildren(parentVal, '');
				});

				$childSelect.on('change', function() {
					$childInput.val($(this).val() || '');
				});

				renderChildren($parentSelect.val(), $childInput.val());
				$parentInput.val($parentSelect.val() || '');
			});
		}

		initDependentTaxonomySelects();

		function clearInlineValidationState() {
			$('.cf-inline-error.cf-inline-error-dynamic').remove();
			$('.cf-invalid-field').removeClass('cf-invalid-field');
		}

		function addInlineFieldError($container, message) {
			if (!$container || !$container.length || !message) {
				return;
			}
			$container.addClass('cf-invalid-field');
			$container.append($('<p class="cf-inline-error cf-inline-error-dynamic" />').text(message));
		}

		function hasAnySelectedImage() {
			var hasFeaturedUpload = ($('#image').length && $('#image').val());
			var hasGalleryUpload = ($('#feature_gallery').length && $('#feature_gallery').val());
			var hasExistingFeatured = $('.cf_update_form img.wp-post-image').length > 0;
			var hasExistingGallery = $('.cf-existing-gallery-item').length > 0;
			return !!(hasFeaturedUpload || hasGalleryUpload || hasExistingFeatured || hasExistingGallery);
		}

		function validateUpdateFormInline() {
			clearInlineValidationState();

			var isValid = true;
			var firstInvalid = null;
			var publishValue = ($('#classified_data\\[post_status\\]').val() || '').toLowerCase();

			var $titleField = $('#title');
			if ($titleField.length && !$.trim($titleField.val())) {
				isValid = false;
				addInlineFieldError($titleField.closest('.editfield'), '<?php echo esc_js( __( 'Bitte gib einen Titel ein.', $this->text_domain ) ); ?>');
				firstInvalid = firstInvalid || $titleField;
			}

			var $contentField = $('#classifiedcontent');
			if ($contentField.length && !$.trim($contentField.val())) {
				isValid = false;
				addInlineFieldError($contentField.closest('.editfield'), '<?php echo esc_js( __( 'Bitte gib eine Beschreibung ein.', $this->text_domain ) ); ?>');
				firstInvalid = firstInvalid || $contentField;
			}

			var $costField = $('input[name="cost"]');
			var rawCost = $costField.length ? String($costField.val() || '').replace(',', '.').trim() : '';
			if (!rawCost || isNaN(Number(rawCost)) || Number(rawCost) <= 0) {
				isValid = false;
				addInlineFieldError($costField.closest('.field'), '<?php echo esc_js( __( 'Bitte gib einen gueltigen Preis ein.', $this->text_domain ) ); ?>');
				firstInvalid = firstInvalid || $costField;
			}

			$('.cf-taxonomy-select-pair').each(function() {
				var $pair = $(this);
				var $parent = $pair.find('.cf-tax-parent-select');
				var $child = $pair.find('.cf-tax-child-select');
				var childHasOptions = $child.find('option').length > 1;

				if (!$parent.val()) {
					isValid = false;
					addInlineFieldError($pair.closest('.cf_taxonomydiv'), '<?php echo esc_js( __( 'Bitte waehle eine Hauptkategorie aus.', $this->text_domain ) ); ?>');
					firstInvalid = firstInvalid || $parent;
					return;
				}

				if (childHasOptions && !$child.val()) {
					isValid = false;
					addInlineFieldError($pair.closest('.cf_taxonomydiv'), '<?php echo esc_js( __( 'Bitte waehle eine Unterkategorie aus.', $this->text_domain ) ); ?>');
					firstInvalid = firstInvalid || $child;
				}
			});

			if (publishValue === 'publish' && !hasAnySelectedImage()) {
				isValid = false;
				addInlineFieldError($('#image').closest('.editfield'), '<?php echo esc_js( __( 'Bitte lade mindestens ein Bild hoch, um zu veroeffentlichen.', $this->text_domain ) ); ?>');
				firstInvalid = firstInvalid || $('#image');
			}

			if (!isValid && firstInvalid && firstInvalid.length) {
				$('html, body').animate({ scrollTop: firstInvalid.offset().top - 90 }, 250);
				firstInvalid.trigger('focus');
			}

			return isValid;
		}

		$('#cf_update_form').on('submit', function(e) {
			if (!validateUpdateFormInline()) {
				e.preventDefault();
			}
		});

		function wrapText($field, openTag, closeTag) {
			var el = $field.get(0);
			if (!el) {
				return;
			}

			var start = el.selectionStart || 0;
			var end = el.selectionEnd || 0;
			var value = $field.val();
			var selected = value.substring(start, end);
			var replacement = openTag + selected + closeTag;
			$field.val(value.substring(0, start) + replacement + value.substring(end));
			el.focus();
			el.selectionStart = start + openTag.length;
			el.selectionEnd = start + openTag.length + selected.length;
		}

		$(document).on('click', '.cf-simple-editor-toolbar [data-markup-action]', function() {
			var action = $(this).data('markup-action');
			var target = $(this).closest('.cf-simple-editor-toolbar').data('markup-target');
			var $field = $('#' + target);
			if (!$field.length) {
				return;
			}

			if (action === 'bold') {
				wrapText($field, '<strong>', '</strong>');
			}
			if (action === 'italic') {
				wrapText($field, '<em>', '</em>');
			}
			if (action === 'ul') {
				wrapText($field, '<ul>\n<li>', '</li>\n</ul>');
			}
			if (action === 'link') {
				var url = window.prompt('<?php echo esc_js( __( 'Link-URL eingeben (inkl. https://):', $this->text_domain ) ); ?>');
				if (!url) {
					return;
				}
				wrapText($field, '<a href="' + url.replace(/"/g, '&quot;') + '">', '</a>');
			}
		});

	});
</script>