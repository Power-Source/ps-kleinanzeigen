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

$cf_options = $cf->get_options( 'general' );
$favorite_ids = method_exists( $cf, 'get_favorite_ids' ) ? $cf->get_favorite_ids() : array();

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
		<a class="button" href="<?php echo esc_url( get_permalink( $cf->classifieds_page_id ) ); ?>"><?php _e( 'Zurücksetzen', CF_TEXT_DOMAIN ); ?></a>
	</div>

	<div class="cf-filter-tools">
		<button type="button" class="button cf-save-filter"><?php _e( 'Filter merken', CF_TEXT_DOMAIN ); ?></button>
		<select class="cf-saved-filter-select" aria-label="<?php esc_attr_e( 'Gespeicherte Filter', CF_TEXT_DOMAIN ); ?>">
			<option value=""><?php _e( 'Gespeicherten Filter laden', CF_TEXT_DOMAIN ); ?></option>
		</select>
		<button type="button" class="button cf-apply-saved-filter"><?php _e( 'Laden', CF_TEXT_DOMAIN ); ?></button>
		<button type="button" class="button cf-delete-saved-filter"><?php _e( 'Loeschen', CF_TEXT_DOMAIN ); ?></button>
	</div>
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
<?php while ( have_posts() ) : the_post(); ?>

<?php
$cost = get_post_meta( get_the_ID(), '_cf_cost', true );
$cost = is_numeric( $cost ) ? number_format_i18n( (float) $cost, 2 ) : $cost;
$gallery_ids   = get_post_meta( get_the_ID(), '_cf_gallery_ids', true );
$gallery_count = is_array( $gallery_ids ) ? count( array_filter( $gallery_ids ) ) : 0;
$is_favorite   = in_array( get_the_ID(), $favorite_ids, true );
?>
<div id="post-<?php the_ID(); ?>" <?php post_class( 'cf-listing-card-wrap' ); ?>>

	<div class="entry-content">
		<div class="cf-ad cf-listing-card">

			<div class="cf-image">
				<?php if ( $gallery_count > 0 ) : ?>
					<span class="cf-gallery-badge"><?php echo esc_html( sprintf( _n( '%d Bild', '%d Bilder', $gallery_count, CF_TEXT_DOMAIN ), $gallery_count ) ); ?></span>
				<?php endif; ?>
				<?php
				if ( '' == get_post_meta( get_the_ID(), '_thumbnail_id', true ) ) {
					if ( isset( $cf_options['field_image_def'] ) && '' != $cf_options['field_image_def'] )
					echo '<img width="150" height="150" title="no image" alt="no image" class="cf-no-image wp-post-image" src="' . $field_image . '">';
				} else {
					echo get_the_post_thumbnail( get_the_ID(), array( 200, 150 ) );
				}

				?>
			</div>
			<div class="cf-info">
				<table>
					<tr>
						<th><?php _e( 'Titel', CF_TEXT_DOMAIN ); ?></th>
						<td>
							<span class="cf-title"><a href="<?php the_permalink(); ?>"><?php echo $post->post_title; ?></a></span>
							<span class="cf-price"><?php echo $cost; ?></span>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Angeboten von', CF_TEXT_DOMAIN ); ?></th>

						<td>

							<span class="cf-author"><?php echo the_author_posts_link(); ?></a></span>

						</td>
					</tr>
					<tr>
						<th><?php _e( 'Kategorien', CF_TEXT_DOMAIN ); ?></th>
						<td><span class="cf-terms">
							<?php $taxonomies = get_object_taxonomies( 'classifieds', 'names' ); ?>
							<?php foreach ( $taxonomies as $taxonomy ): ?>
							<?php echo get_the_term_list( get_the_ID(), $taxonomy, '', ', ', '' ) . ' '; ?>
							<?php endforeach; ?>
						</span>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Läuft ab', CF_TEXT_DOMAIN ); ?></th>
					<td><span class="cf-expires"><?php echo $cf->get_expiration_date( get_the_ID() ); ?></span></td>
				</tr>
				<tr>
					<td colspan="2"><span class="cf-excerpt"><?php wp_kses(get_the_excerpt(), cf_wp_kses_allowed_html()); ?></span></td>
				</tr>
			</table>
			<div class="cf-card-footer">
				<div class="cf-card-meta-row">
					<?php if ( $cost ) : ?>
						<span class="cf-card-pill"><?php _e( 'Preis', CF_TEXT_DOMAIN ); ?>: <?php echo esc_html( $cost ); ?></span>
					<?php endif; ?>
					<span class="cf-card-pill"><?php _e( 'Inserat ansehen', CF_TEXT_DOMAIN ); ?></span>
				</div>
				<div class="cf-card-actions">
					<button type="button" class="button cf-card-secondary cf-favorite-toggle <?php echo $is_favorite ? 'is-active' : ''; ?>" data-post-id="<?php the_ID(); ?>">
						<span class="cf-favorite-label-default"><?php _e( 'Merken', CF_TEXT_DOMAIN ); ?></span>
						<span class="cf-favorite-label-active"><?php _e( 'Gemerkt', CF_TEXT_DOMAIN ); ?></span>
					</button>
					<button type="button" class="button cf-card-secondary cf-quickview-trigger" data-post-id="<?php the_ID(); ?>"><?php _e( 'Schnellansicht', CF_TEXT_DOMAIN ); ?></button>
					<a class="button cf-card-secondary cf-card-contact" href="<?php echo esc_url( add_query_arg( 'cf_contact', '1', get_permalink() ) . '#confirm-form' ); ?>"><?php _e( 'Kontakt', CF_TEXT_DOMAIN ); ?></a>
					<a class="button cf-card-cta" href="<?php the_permalink(); ?>"><?php _e( 'Mehr Details', CF_TEXT_DOMAIN ); ?></a>
				</div>
			</div>
		</div>

	</div>
</div><!-- .entry-content -->

</div><!-- #post-## -->

<?php endwhile; // End the loop. Whew. ?>

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