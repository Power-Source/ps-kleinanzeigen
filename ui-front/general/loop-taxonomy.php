<?php
/**
* The loop that displays posts.
* You can override this file in your active theme.
*
* The loop displays the posts and the post content.  See
* http://codex.wordpress.org/The_Loop to understand it and
* http://codex.wordpress.org/Template_Tags to understand
* the tags used in it.
*
* This can be overridden in child themes with loop.php or
* loop-template.php, where 'template' is the loop context
* requested by a template. For example, loop-index.php would
* be used if it exists and we ask for the loop with:
* <code>get_template_part( 'loop', 'index' );</code>
*
* @package Classifieds
* @subpackage Taxonomy
* @since Classifieds 2.0
*/
global $bp, $Classifieds_Core;
$cf = $Classifieds_Core; //shorthand

?>
<script type="text/javascript" src="<?php echo esc_url( $cf->plugin_url . 'ui-front/js/ui-front.js' ); ?>"></script>
<?php

$cf_options = $cf->get_options( 'general' );
$frontend_options = $cf->get_options( 'frontend' );
$favorite_ids = method_exists( $cf, 'get_favorite_ids' ) ? $cf->get_favorite_ids() : array();
$template_preset = isset( $GLOBALS['cf_frontend_template_preset'] ) ? sanitize_key( (string) $GLOBALS['cf_frontend_template_preset'] ) : '';
if ( ! in_array( $template_preset, array( 'b2c', 'premium', 'community' ), true ) ) {
	$template_preset = '';
}

$archive_columns = isset( $frontend_options['archive_columns'] ) ? (int) $frontend_options['archive_columns'] : 3;
if ( ! in_array( $archive_columns, array( 2, 3, 4 ), true ) ) {
	$archive_columns = 3;
}
$archive_show_filter_tools = ! isset( $frontend_options['archive_show_filter_tools'] ) || 1 === (int) $frontend_options['archive_show_filter_tools'];
$archive_show_quickview = ! isset( $frontend_options['archive_show_quickview'] ) || 1 === (int) $frontend_options['archive_show_quickview'];
$archive_show_favorites = ! isset( $frontend_options['archive_show_favorites'] ) || 1 === (int) $frontend_options['archive_show_favorites'];
$archive_show_contact_cta = ! isset( $frontend_options['archive_show_contact_cta'] ) || 1 === (int) $frontend_options['archive_show_contact_cta'];
$archive_show_reserved_badge = ! isset( $frontend_options['archive_show_reserved_badge'] ) || 1 === (int) $frontend_options['archive_show_reserved_badge'];

$archive_intro = isset( $frontend_options['archive_intro'] ) ? trim( $frontend_options['archive_intro'] ) : '';

$field_image = (empty($cf_options['field_image_def'])) ? $cf->plugin_url . 'ui-front/general/images/blank.gif' : $cf_options['field_image_def'];

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

<?php if(! is_post_type_archive('classifieds') ) the_cf_breadcrumbs(); ?>

<?php if ( '' !== $archive_intro ) : ?>
<div class="cf-archive-intro">
	<?php echo wp_kses_post( wpautop( $archive_intro ) ); ?>
</div>
<?php endif; ?>

<form method="get" class="cf-filter-bar" action="<?php echo esc_url( get_permalink( $cf->classifieds_page_id ) ); ?>">
	<div class="cf-filter-grid">
		<div class="cf-filter-field">
			<label for="cf_filter_q"><?php _e( 'Suchbegriff', CF_TEXT_DOMAIN ); ?></label>
			<input type="text" id="cf_filter_q" name="q" value="<?php echo esc_attr( $selected_q ); ?>" placeholder="<?php esc_attr_e( 'z. B. Fahrrad oder Sofa', CF_TEXT_DOMAIN ); ?>" />
		</div>

		<div class="cf-filter-field">
			<label for="cf_filter_cat"><?php _e( 'Kategorie', CF_TEXT_DOMAIN ); ?></label>
			<select id="cf_filter_cat" name="cat">
				<option value=""><?php _e( 'Alle Kategorien', CF_TEXT_DOMAIN ); ?></option>
				<?php if ( ! is_wp_error( $category_terms ) ) : ?>
					<?php foreach ( $category_terms as $term ) : ?>
						<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $selected_cat, $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
		</div>

		<div class="cf-filter-field">
			<label for="cf_filter_region"><?php _e( 'Region', CF_TEXT_DOMAIN ); ?></label>
			<select id="cf_filter_region" name="region">
				<option value=""><?php _e( 'Alle Regionen', CF_TEXT_DOMAIN ); ?></option>
				<?php if ( ! is_wp_error( $region_terms ) ) : ?>
					<?php foreach ( $region_terms as $term ) : ?>
						<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $selected_region, $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
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
		<button type="submit" class="button"><?php _e( 'Filtern', CF_TEXT_DOMAIN ); ?></button>
		<a class="button cf-filter-reset" href="<?php echo esc_url( get_permalink( $cf->classifieds_page_id ) ); ?>"><?php _e( 'Zurücksetzen', CF_TEXT_DOMAIN ); ?></a>
	</div>

	<?php if ( $archive_show_filter_tools ) : ?>
	<div class="cf-filter-tools">
		<button type="button" class="button cf-save-filter"><?php _e( 'Filter merken', CF_TEXT_DOMAIN ); ?></button>
		<select class="cf-saved-filter-select" aria-label="<?php esc_attr_e( 'Gespeicherte Filter', CF_TEXT_DOMAIN ); ?>">
			<option value=""><?php _e( 'Gespeicherten Filter laden', CF_TEXT_DOMAIN ); ?></option>
		</select>
		<button type="button" class="button cf-apply-saved-filter"><?php _e( 'Laden', CF_TEXT_DOMAIN ); ?></button>
		<button type="button" class="button cf-delete-saved-filter"><?php _e( 'Löschen', CF_TEXT_DOMAIN ); ?></button>
		<label class="cf-auto-restore-toggle" for="cf_filter_auto_restore">
			<input type="checkbox" id="cf_filter_auto_restore" class="cf-auto-restore-input" />
			<?php _e( 'Zuletzt genutzte Filter automatisch laden', CF_TEXT_DOMAIN ); ?>
		</label>
	</div>
	<?php endif; ?>
</form>

<?php /* Display navigation to next/previous pages when applicable */ ?>
<?php echo $cf->pagination( $cf->pagination_top ); ?>
<div class="clear"></div>
<?php /* If there are no posts to display, such as an empty archive page */ ?>
<?php if ( ! have_posts() ) : ?>
<div id="post-0" class="post error404 not-found">
	<h1 class="entry-title"><?php _e( 'Nicht gefunden', CF_TEXT_DOMAIN ); ?></h1>
	<div class="entry-content">
		<p><?php _e( 'Entschuldigung, aber für die angeforderten Kleinanzeigen wurden keine Ergebnisse gefunden. Vielleicht hilft die Suche dabei, eine entsprechende Kleinanzeige zu finden.', CF_TEXT_DOMAIN ); ?></p>
		<?php get_search_form(); ?>
	</div><!-- .entry-content -->
</div><!-- #post-0 -->
<?php endif; ?>

<?php
/* Start the Loop.
*
* In Twenty Ten we use the same loop in multiple contexts.
* It is broken into three main parts: when we're displaying
* posts that are in the gallery category, when we're displaying
* posts in the asides category, and finally all other posts.
*
* Additionally, we sometimes check for whether we are on an
* archive page, a search page, etc., allowing for small differences
* in the loop on each template without actually duplicating
* the rest of the loop that is shared.
*
* Without further ado, the loop:
*/  ?>
<div class="cf-listing-grid cf-grid-cols-<?php echo esc_attr( $archive_columns ); ?><?php echo '' !== $template_preset ? ' cf-archive-preset-' . esc_attr( $template_preset ) : ''; ?>">
<?php while ( have_posts() ) : the_post(); ?>

<?php
$cost = get_post_meta( get_the_ID(), '_cf_cost', true );
$cost = is_numeric( $cost ) ? number_format_i18n( (float) $cost, 2 ) : $cost;
$gallery_ids   = get_post_meta( get_the_ID(), '_cf_gallery_ids', true );
$gallery_count = is_array( $gallery_ids ) ? count( array_filter( $gallery_ids ) ) : 0;
$is_favorite   = in_array( get_the_ID(), $favorite_ids, true );
$is_reserved   = method_exists( $cf, 'is_reserved_post' ) ? $cf->is_reserved_post( get_the_ID() ) : ( '1' === (string) get_post_meta( get_the_ID(), '_cf_reserved', true ) );
$is_featured   = method_exists( $cf, 'is_featured' ) ? $cf->is_featured( get_the_ID() ) : ( '1' === (string) get_post_meta( get_the_ID(), '_cf_is_featured', true ) );
$listing_card_classes = 'cf-listing-card-wrap' . ( $is_featured ? ' is-featured' : '' );
?>
<div id="post-<?php the_ID(); ?>" <?php post_class( $listing_card_classes ); ?>>

	<div class="entry-content">
		<div class="cf-ad cf-listing-card">
			<div class="cf-image">
				<?php if ( $is_featured ) : ?>
					<span class="cf-status-badge is-featured"><?php _e( 'Featured', CF_TEXT_DOMAIN ); ?></span>
				<?php endif; ?>
				<?php if ( $archive_show_reserved_badge && $is_reserved ) : ?>
					<span class="cf-status-badge is-reserved"><?php _e( 'Reserviert', CF_TEXT_DOMAIN ); ?></span>
				<?php endif; ?>
				<?php if ( $gallery_count > 0 ) : ?>
					<span class="cf-gallery-badge"><?php echo esc_html( sprintf( _n( '%d Bild', '%d Bilder', $gallery_count, CF_TEXT_DOMAIN ), $gallery_count ) ); ?></span>
				<?php endif; ?>
				<?php
				if ( '' == get_post_meta( get_the_ID(), '_thumbnail_id', true ) ) {
					if ( isset( $cf_options['field_image_def'] ) && '' != $cf_options['field_image_def'] ) {
						echo '<img width="960" height="720" title="no image" alt="no image" class="cf-no-image wp-post-image cf-card-image" src="' . esc_url( $field_image ) . '">';
					}
				} else {
					echo get_the_post_thumbnail(
						get_the_ID(),
						'medium_large',
						array(
							'class'    => 'wp-post-image cf-card-image',
							'loading'  => 'lazy',
							'decoding' => 'async',
							'sizes'    => '(max-width: 700px) 100vw, (max-width: 1100px) 50vw, 33vw',
						)
					);
				}

				?>
			</div>
			<div class="cf-info">
				<?php
				$cat_list    = get_the_term_list( get_the_ID(), 'kleinenanzeigen-cat', '', ', ', '' );
				$region_list = get_the_term_list( get_the_ID(), 'kleinanzeigen-region', '', ', ', '' );
				?>

				<div class="cf-card-headline">
					<h3 class="cf-title"><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a></h3>
					<?php if ( ! empty( $cost ) ) : ?>
						<span class="cf-price"><?php echo esc_html( $cost ); ?></span>
					<?php endif; ?>
				</div>

				<div class="cf-card-meta-compact">
					<span class="cf-card-pill"><?php _e( 'Von', CF_TEXT_DOMAIN ); ?>: <?php the_author(); ?></span>
					<span class="cf-card-pill"><?php _e( 'Läuft ab', CF_TEXT_DOMAIN ); ?>: <?php echo esc_html( $cf->get_expiration_date( get_the_ID() ) ); ?></span>
					<?php if ( ! empty( $cat_list ) ) : ?>
						<span class="cf-card-pill"><?php echo wp_kses_post( $cat_list ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $region_list ) ) : ?>
						<span class="cf-card-pill"><?php echo wp_kses_post( $region_list ); ?></span>
					<?php endif; ?>
				</div>

				<p class="cf-excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 18, ' ...' ) ); ?></p>

			<div class="cf-card-footer">
				<div class="cf-card-meta-row"></div>
				<div class="cf-card-actions">
					<?php if ( $archive_show_favorites ) : ?>
					<button type="button" class="button cf-card-secondary cf-favorite-toggle <?php echo $is_favorite ? 'is-active' : ''; ?>" data-post-id="<?php the_ID(); ?>">
						<span class="cf-favorite-label-default"><?php _e( 'Merken', CF_TEXT_DOMAIN ); ?></span>
						<span class="cf-favorite-label-active"><?php _e( 'Gemerkt', CF_TEXT_DOMAIN ); ?></span>
					</button>
					<?php endif; ?>
					<?php if ( $archive_show_quickview ) : ?>
								<a class="button cf-card-secondary cf-quickview-trigger" href="<?php the_permalink(); ?>" data-post-id="<?php the_ID(); ?>"><?php _e( 'Schnellansicht', CF_TEXT_DOMAIN ); ?></a>
					<?php endif; ?>
					<?php if ( $archive_show_contact_cta ) : ?>
					<a class="button cf-card-secondary cf-card-contact" href="<?php echo esc_url( add_query_arg( 'cf_contact', '1', get_permalink() ) . '#confirm-form' ); ?>"><?php _e( 'Kontakt', CF_TEXT_DOMAIN ); ?></a>
					<?php endif; ?>
					<a class="button cf-card-cta" href="<?php the_permalink(); ?>"><?php _e( 'Mehr Details', CF_TEXT_DOMAIN ); ?></a>
				</div>
			</div>
		</div>

	</div>
</div><!-- .entry-content -->

</div><!-- #post-## -->

<?php endwhile; // End the loop. Whew. ?>
</div>

<div class="cf-modal" id="cf-quickview-modal" aria-hidden="true">
	<div class="cf-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="cf-quickview-title">
		<button type="button" class="cf-modal-close" aria-label="<?php esc_attr_e( 'Schliessen', CF_TEXT_DOMAIN ); ?>">&times;</button>
		<div class="cf-modal-content">
			<div class="cf-modal-loading"><?php _e( 'Wird geladen ...', CF_TEXT_DOMAIN ); ?></div>
		</div>
	</div>
</div>

<?php /* Display navigation to next/previous pages when applicable */ ?>
<?php echo $cf->pagination( $cf->pagination_bottom ); ?>