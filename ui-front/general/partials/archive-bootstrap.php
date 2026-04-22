<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $bp, $Classifieds_Core;
$cf = $Classifieds_Core;
$cf_options = $cf->get_options( 'general' );
$frontend_options = $cf->get_options( 'frontend' );
$favorite_ids = method_exists( $cf, 'get_favorite_ids' ) ? $cf->get_favorite_ids() : array();

$archive_columns = isset( $frontend_options['archive_columns'] ) ? (int) $frontend_options['archive_columns'] : 3;
if ( ! in_array( $archive_columns, array( 2, 3, 4 ), true ) ) {
	$archive_columns = 3;
}
$archive_show_filter_tools = ! isset( $frontend_options['archive_show_filter_tools'] ) || 1 === (int) $frontend_options['archive_show_filter_tools'];
$archive_show_quickview = ! isset( $frontend_options['archive_show_quickview'] ) || 1 === (int) $frontend_options['archive_show_quickview'];
$archive_show_favorites = ! isset( $frontend_options['archive_show_favorites'] ) || 1 === (int) $frontend_options['archive_show_favorites'];
$archive_show_contact_cta = ! isset( $frontend_options['archive_show_contact_cta'] ) || 1 === (int) $frontend_options['archive_show_contact_cta'];
$archive_show_reserved_badge = ! isset( $frontend_options['archive_show_reserved_badge'] ) || 1 === (int) $frontend_options['archive_show_reserved_badge'];

// Resolve active frontend preset for filter-bar styling
$_cf_filter_preset = '';
$_cf_filter_preset_raw = isset( $frontend_options['frontend_preset'] ) ? sanitize_key( (string) $frontend_options['frontend_preset'] ) : '';
if ( in_array( $_cf_filter_preset_raw, array( 'b2c', 'premium', 'community' ), true ) ) {
	$_cf_filter_preset = $_cf_filter_preset_raw;
}
// Inherit preset from template global (set by preset loop wrappers)
if ( '' === $_cf_filter_preset && isset( $GLOBALS['cf_frontend_template_preset'] ) ) {
	$_tmp = sanitize_key( (string) $GLOBALS['cf_frontend_template_preset'] );
	if ( in_array( $_tmp, array( 'b2c', 'premium', 'community' ), true ) ) {
		$_cf_filter_preset = $_tmp;
	}
}
$_cf_filter_bar_class   = 'cf-filter-bar' . ( '' !== $_cf_filter_preset ? ' cf-filter-bar--' . $_cf_filter_preset : '' );
$_cf_btn_primary_class  = '' !== $_cf_filter_preset ? 'button cf-btn-' . $_cf_filter_preset . '-primary' : 'button';
$_cf_btn_ghost_class    = '' !== $_cf_filter_preset ? 'button cf-btn-' . $_cf_filter_preset . '-ghost'   : 'button';
// premium has no ghost variant, use secondary instead
if ( 'premium' === $_cf_filter_preset ) {
	$_cf_btn_ghost_class = 'button cf-btn-premium-secondary';
}

$archive_intro = isset( $frontend_options['archive_intro'] ) ? trim( $frontend_options['archive_intro'] ) : '';
$field_image = ( empty( $cf_options['field_image_def'] ) ) ? $cf->plugin_url . 'ui-front/general/images/blank.gif' : $cf_options['field_image_def'];

$selected_q         = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
$selected_cat       = isset( $_GET['cat'] ) ? sanitize_title( wp_unslash( $_GET['cat'] ) ) : '';
$selected_region    = isset( $_GET['region'] ) ? sanitize_title( wp_unslash( $_GET['region'] ) ) : '';
$selected_min_price = isset( $_GET['min_price'] ) ? sanitize_text_field( wp_unslash( $_GET['min_price'] ) ) : '';
$selected_max_price = isset( $_GET['max_price'] ) ? sanitize_text_field( wp_unslash( $_GET['max_price'] ) ) : '';
$selected_sort      = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'newest';

$category_terms = get_terms(
	array(
		'taxonomy'   => 'kleinenanzeigen-cat',
		'hide_empty' => false,
	)
);

$region_terms = get_terms(
	array(
		'taxonomy'   => 'kleinanzeigen-region',
		'hide_empty' => false,
	)
);
?>

<?php if ( ! is_post_type_archive( 'classifieds' ) ) the_cf_breadcrumbs(); ?>

<?php if ( '' !== $archive_intro ) : ?>
<div class="cf-archive-intro">
	<?php echo wp_kses_post( wpautop( $archive_intro ) ); ?>
</div>
<?php endif; ?>

<form method="get" class="<?php echo esc_attr( $_cf_filter_bar_class ); ?>" action="<?php echo esc_url( get_permalink( $cf->classifieds_page_id ) ); ?>">
	<div class="cf-filter-grid">
		<div class="cf-filter-field">
			<label for="cf_filter_q"><?php _e( 'Suchbegriff', CF_TEXT_DOMAIN ); ?></label>
			<input type="text" id="cf_filter_q" name="q" value="<?php echo esc_attr( $selected_q ); ?>" placeholder="<?php esc_attr_e( 'z. B. Fahrrad oder Sofa', CF_TEXT_DOMAIN ); ?>" />
		</div>
		<div class="cf-filter-field">
			<label for="cf_filter_cat"><?php _e( 'Kategorie', CF_TEXT_DOMAIN ); ?></label>
			<select id="cf_filter_cat" name="cat">
				<option value=""><?php _e( 'Alle Kategorien', CF_TEXT_DOMAIN ); ?></option>
				<?php if ( ! is_wp_error( $category_terms ) ) : foreach ( $category_terms as $term ) : ?>
					<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $selected_cat, $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
				<?php endforeach; endif; ?>
			</select>
		</div>
		<div class="cf-filter-field">
			<label for="cf_filter_region"><?php _e( 'Region', CF_TEXT_DOMAIN ); ?></label>
			<select id="cf_filter_region" name="region">
				<option value=""><?php _e( 'Alle Regionen', CF_TEXT_DOMAIN ); ?></option>
				<?php if ( ! is_wp_error( $region_terms ) ) : foreach ( $region_terms as $term ) : ?>
					<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $selected_region, $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
				<?php endforeach; endif; ?>
			</select>
		</div>
		<div class="cf-filter-field cf-filter-price">
			<label><?php _e( 'Preisrahmen', CF_TEXT_DOMAIN ); ?></label>
			<div class="cf-filter-inline">
				<input type="text" name="min_price" value="<?php echo esc_attr( $selected_min_price ); ?>" placeholder="Min" inputmode="decimal" />
				<input type="text" name="max_price" value="<?php echo esc_attr( $selected_max_price ); ?>" placeholder="Max" inputmode="decimal" />
			</div>
		</div>
		<div class="cf-filter-field">
			<label for="cf_filter_sort"><?php _e( 'Sortierung', CF_TEXT_DOMAIN ); ?></label>
			<select id="cf_filter_sort" name="sort">
				<option value="newest" <?php selected( $selected_sort, 'newest' ); ?>><?php _e( 'Neueste zuerst', CF_TEXT_DOMAIN ); ?></option>
				<option value="price_asc" <?php selected( $selected_sort, 'price_asc' ); ?>><?php _e( 'Preis aufsteigend', CF_TEXT_DOMAIN ); ?></option>
				<option value="price_desc" <?php selected( $selected_sort, 'price_desc' ); ?>><?php _e( 'Preis absteigend', CF_TEXT_DOMAIN ); ?></option>
			</select>
		</div>
	</div>
	<div class="cf-filter-actions">
		<button type="submit" class="<?php echo esc_attr( $_cf_btn_primary_class ); ?>"><?php _e( 'Filtern', CF_TEXT_DOMAIN ); ?></button>
		<a class="<?php echo esc_attr( $_cf_btn_ghost_class ); ?> cf-filter-reset" href="<?php echo esc_url( get_permalink( $cf->classifieds_page_id ) ); ?>"><?php _e( 'Zuruecksetzen', CF_TEXT_DOMAIN ); ?></a>
	</div>
	<?php if ( $archive_show_filter_tools ) : ?>
	<div class="cf-filter-tools">
		<button type="button" class="<?php echo esc_attr( $_cf_btn_ghost_class ); ?> cf-save-filter"><?php _e( 'Filter merken', CF_TEXT_DOMAIN ); ?></button>
		<select class="cf-saved-filter-select" aria-label="<?php esc_attr_e( 'Gespeicherte Filter', CF_TEXT_DOMAIN ); ?>">
			<option value=""><?php _e( 'Gespeicherten Filter laden', CF_TEXT_DOMAIN ); ?></option>
		</select>
		<button type="button" class="<?php echo esc_attr( $_cf_btn_ghost_class ); ?> cf-apply-saved-filter"><?php _e( 'Laden', CF_TEXT_DOMAIN ); ?></button>
		<button type="button" class="<?php echo esc_attr( $_cf_btn_ghost_class ); ?> cf-delete-saved-filter"><?php _e( 'Loeschen', CF_TEXT_DOMAIN ); ?></button>
		<label class="cf-auto-restore-toggle" for="cf_filter_auto_restore">
			<input type="checkbox" id="cf_filter_auto_restore" class="cf-auto-restore-input" />
			<?php _e( 'Zuletzt genutzte Filter automatisch laden', CF_TEXT_DOMAIN ); ?>
		</label>
	</div>
	<?php endif; ?>
</form>

<?php echo $cf->pagination( $cf->pagination_top ); ?>
<div class="clear"></div>
