<?php
/**
 * Native WordPress Taxonomies Handler

 * 
 * @package PS_Kleinanzeigen
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PS_Native_Taxonomies {
	
	/**
	 * Initialize taxonomies
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 1 );
		add_action( 'kleinanzeigen-region_add_form_fields', array( __CLASS__, 'render_region_location_add_fields' ) );
		add_action( 'kleinanzeigen-region_edit_form_fields', array( __CLASS__, 'render_region_location_edit_fields' ), 10, 2 );
		add_action( 'created_kleinanzeigen-region', array( __CLASS__, 'save_region_location_fields' ), 10, 1 );
		add_action( 'edited_kleinanzeigen-region', array( __CLASS__, 'save_region_location_fields' ), 10, 1 );
	}
	
	/**
	 * Register native WordPress taxonomies
	
	 */
	public static function register_taxonomies() {
		self::register_categories_taxonomy();
		self::register_regions_taxonomy();
	}
	
	/**
	 * Register categories taxonomy
	 */
	private static function register_categories_taxonomy() {
		$args = array(
			'labels'            => array(
				'name'                       => __( 'Kategorien', 'ps-kleinanzeigen' ),
				'singular_name'              => __( 'Kategorie', 'ps-kleinanzeigen' ),
				'menu_name'                  => __( 'Kategorien', 'ps-kleinanzeigen' ),
				'all_items'                  => __( 'Alle Kategorien', 'ps-kleinanzeigen' ),
				'add_new_item'               => __( 'Neue Kategorie', 'ps-kleinanzeigen' ),
				'edit_item'                  => __( 'Kategorie bearbeiten', 'ps-kleinanzeigen' ),
				'new_item_name'              => __( 'Neue Kategorie Name', 'ps-kleinanzeigen' ),
				'search_items'               => __( 'Kategorien durchsuchen', 'ps-kleinanzeigen' ),
				'parent_item'                => __( 'Übergeordnete Kategorie', 'ps-kleinanzeigen' ),
				'parent_item_colon'          => __( 'Übergeordnete Kategorie:', 'ps-kleinanzeigen' ),
				'not_found'                  => __( 'Keine Kategorien gefunden', 'ps-kleinanzeigen' ),
				'popular_items'              => __( 'Beliebte Kategorien', 'ps-kleinanzeigen' ),
				'back_to_items'              => __( 'Zurück zu Kategorien', 'ps-kleinanzeigen' ),
			),
			'hierarchical'      => true,
			'labels_description' => __( 'Kategorien für Kleinanzeigen', 'ps-kleinanzeigen' ),
			'public'            => true,
			'publicly_queryable' => true,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'show_in_rest'      => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'kleinanzeigen-kategorie' ),
		);
		
		register_taxonomy( 'kleinenanzeigen-cat', array( 'classifieds' ), $args );
	}
	
	/**
	 * Register regions taxonomy
	 */
	private static function register_regions_taxonomy() {
		$args = array(
			'labels'            => array(
				'name'                       => __( 'Regionen', 'ps-kleinanzeigen' ),
				'singular_name'              => __( 'Region', 'ps-kleinanzeigen' ),
				'menu_name'                  => __( 'Regionen', 'ps-kleinanzeigen' ),
				'all_items'                  => __( 'Alle Regionen', 'ps-kleinanzeigen' ),
				'add_new_item'               => __( 'Neue Region', 'ps-kleinanzeigen' ),
				'edit_item'                  => __( 'Region bearbeiten', 'ps-kleinanzeigen' ),
				'new_item_name'              => __( 'Neue Region Name', 'ps-kleinanzeigen' ),
				'search_items'               => __( 'Regionen durchsuchen', 'ps-kleinanzeigen' ),
				'parent_item'                => __( 'Übergeordnete Region', 'ps-kleinanzeigen' ),
				'parent_item_colon'          => __( 'Übergeordnete Region:', 'ps-kleinanzeigen' ),
				'not_found'                  => __( 'Keine Regionen gefunden', 'ps-kleinanzeigen' ),
				'back_to_items'              => __( 'Zurück zu Regionen', 'ps-kleinanzeigen' ),
			),
			'hierarchical'      => true,
			'labels_description' => __( 'Regionen für Kleinanzeigen', 'ps-kleinanzeigen' ),
			'public'            => true,
			'publicly_queryable' => true,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'show_in_rest'      => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'region' ),
		);
		
		register_taxonomy( 'kleinanzeigen-region', array( 'classifieds' ), $args );
	}
	
	/**
	 * Get all registered taxonomies (for migration compatibility)

	 */
	public static function get_taxonomies() {
		return array(
			'kleinenanzeigen-cat' => array(
				'name'        => 'kleinenanzeigen-cat',
				'label'       => __( 'Kategorien', 'ps-kleinanzeigen' ),
				'description' => __( 'Kategorien für Kleinanzeigen', 'ps-kleinanzeigen' ),
				'hierarchical' => true,
			),
			'kleinanzeigen-region' => array(
				'name'        => 'kleinanzeigen-region',
				'label'       => __( 'Regionen', 'ps-kleinanzeigen' ),
				'description' => __( 'Regionen für Kleinanzeigen', 'ps-kleinanzeigen' ),
				'hierarchical' => true,
			),
		);
	}
	
	/**
	 * Check if taxonomy exists

	 */
	public static function taxonomy_exists( $taxonomy ) {
		$taxonomies = self::get_taxonomies();
		return isset( $taxonomies[ $taxonomy ] );
	}

	/**
	 * Add fields for PLZ/Ort on the "new region" form.
	 *
	 * @return void
	 */
	public static function render_region_location_add_fields() {
		?>
		<div class="form-field term-cf-region-postcode-wrap">
			<label for="cf_region_postcode"><?php esc_html_e( 'PLZ', 'ps-kleinanzeigen' ); ?></label>
			<input type="text" name="cf_region_postcode" id="cf_region_postcode" value="" maxlength="12" />
			<p><?php esc_html_e( 'Optional: Postleitzahl fuer diese Region.', 'ps-kleinanzeigen' ); ?></p>
		</div>
		<div class="form-field term-cf-region-city-wrap">
			<label for="cf_region_city"><?php esc_html_e( 'Ort', 'ps-kleinanzeigen' ); ?></label>
			<input type="text" name="cf_region_city" id="cf_region_city" value="" maxlength="120" />
			<p><?php esc_html_e( 'Optional: Ortsname fuer die Karten-Geokodierung.', 'ps-kleinanzeigen' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Add fields for PLZ/Ort on the "edit region" form.
	 *
	 * @param WP_Term $term
	 * @return void
	 */
	public static function render_region_location_edit_fields( $term ) {
		$postcode = (string) get_term_meta( $term->term_id, '_cf_region_postcode', true );
		$city = (string) get_term_meta( $term->term_id, '_cf_region_city', true );
		?>
		<tr class="form-field term-cf-region-postcode-wrap">
			<th scope="row"><label for="cf_region_postcode"><?php esc_html_e( 'PLZ', 'ps-kleinanzeigen' ); ?></label></th>
			<td>
				<input type="text" name="cf_region_postcode" id="cf_region_postcode" value="<?php echo esc_attr( $postcode ); ?>" maxlength="12" />
				<p class="description"><?php esc_html_e( 'Optional: Postleitzahl fuer diese Region.', 'ps-kleinanzeigen' ); ?></p>
			</td>
		</tr>
		<tr class="form-field term-cf-region-city-wrap">
			<th scope="row"><label for="cf_region_city"><?php esc_html_e( 'Ort', 'ps-kleinanzeigen' ); ?></label></th>
			<td>
				<input type="text" name="cf_region_city" id="cf_region_city" value="<?php echo esc_attr( $city ); ?>" maxlength="120" />
				<p class="description"><?php esc_html_e( 'Optional: Ortsname fuer die Karten-Geokodierung.', 'ps-kleinanzeigen' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Persist PLZ/Ort fields for a region term.
	 *
	 * @param int $term_id
	 * @return void
	 */
	public static function save_region_location_fields( $term_id ) {
		$postcode = isset( $_POST['cf_region_postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['cf_region_postcode'] ) ) : '';
		$city = isset( $_POST['cf_region_city'] ) ? sanitize_text_field( wp_unslash( $_POST['cf_region_city'] ) ) : '';

		if ( '' !== $postcode ) {
			update_term_meta( $term_id, '_cf_region_postcode', $postcode );
		} else {
			delete_term_meta( $term_id, '_cf_region_postcode' );
		}

		if ( '' !== $city ) {
			update_term_meta( $term_id, '_cf_region_city', $city );
		} else {
			delete_term_meta( $term_id, '_cf_region_city' );
		}
	}
}

// Initialize
PS_Native_Taxonomies::init();
