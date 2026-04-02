<?php
/**
* The template for displaying My Classifieds page.
* You can override this file in your active theme.
*
* @package Classifieds
* @subpackage UI Front
* @since Classifieds 2.0
*/

global $current_user, $wp_query;

$current_user = wp_get_current_user();
$error = get_query_var('cf_error');

$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

$options_general = $this->get_options( 'general' );
$options_frontend = $this->get_options( 'frontend' );
$user_intro = isset( $options_frontend['user_intro'] ) ? trim( $options_frontend['user_intro'] ) : '';
$user_show_favorites_tab = ! isset( $options_frontend['user_show_favorites_tab'] ) || 1 === (int) $options_frontend['user_show_favorites_tab'];
$user_allow_reserve_toggle = ! isset( $options_frontend['user_allow_reserve_toggle'] ) || 1 === (int) $options_frontend['user_allow_reserve_toggle'];

$favorite_ids = method_exists( $this, 'get_favorite_ids' ) ? $this->get_favorite_ids() : array();

$query_args = array(
'paged' => $paged,
'post_type' => 'classifieds',
'author' => $current_user->ID,
//'posts_per_page' => 1000,
);

if ( $user_show_favorites_tab && isset( $_GET['favorites'] ) ) {
	$sub = 'favorites';
	$query_args['post_status'] = 'publish';
	unset( $query_args['author'] );
	$query_args['post__in'] = ! empty( $favorite_ids ) ? array_map( 'absint', $favorite_ids ) : array( 0 );
} elseif(isset($_GET['saved']) ) {
	$query_args['post_status'] = array('draft', 'pending');
	$sub = 'saved';
}elseif(isset($_GET['ended'])){
	$query_args['post_status'] = 'private';
	$sub = 'ended';
}else{
	$query_args['post_status'] = 'publish';
	$sub = 'active';
}

query_posts($query_args);

$cf_path = get_permalink($this->my_classifieds_page_id);

remove_filter('the_content', array(&$this, 'my_classifieds_content'));

?>

<script type="text/javascript" src="<?php echo $this->plugin_url . 'ui-front/js/ui-front.js'; ?>" >
</script>


<?php if ( !empty( $error ) ): ?>
<br /><div class="error"><?php echo $error . '<br />'; ?></div>
<?php endif; ?>

<div class="clear"></div>
<?php if ( '' !== $user_intro ) : ?>
<div class="cf-user-intro"><?php echo wp_kses_post( wpautop( $user_intro ) ); ?></div>
<?php endif; ?>

<?php if ( $this->is_full_access() ): ?>
<div class="av-credits"><?php _e( 'Du hast die Möglichkeit, neue Kleinanzeigen zu erstellen', $this->text_domain ); ?></div>
<?php elseif($this->use_credits): ?>
<div class="av-credits"><?php _e( 'Verfügbare Credits:', $this->text_domain ); ?> <?php echo $this->transactions->credits; ?></div>
<?php else:
echo do_shortcode('[cf_checkout_btn text="' . __('Kleinanzeigen kaufen', $this->text_domain) . '" view="loggedin"]');
?>
<?php endif; ?>

<div >
	<?php echo do_shortcode('[cf_add_classified_btn text="' . __('Neue Kleinanzeige erstellen', $this->text_domain) . '" view="loggedin"]'); ?>
	<?php echo do_shortcode('[cf_my_credits_btn text="' . __('Meine Credits', $this->text_domain) . '" view="loggedin"]'); ?>
</div>

<ul class="cf_tabs">
	<li class="<?php if ( $sub == 'active') echo 'cf_active'; ?>"><a href="<?php echo $cf_path . '/?active'; ?>"><?php _e( 'Aktive Anzeigen', $this->text_domain ); ?></a></li>
	<?php if ( $user_show_favorites_tab ) : ?>
	<li class="<?php if (  $sub == 'favorites') echo 'cf_active'; ?>"><a href="<?php echo $cf_path . '/?favorites'; ?>"><?php _e( 'Gemerkte Anzeigen', $this->text_domain ); ?></a></li>
	<?php endif; ?>
	<li class="<?php if (  $sub == 'saved') echo 'cf_active'; ?>"><a href="<?php echo $cf_path . '/?saved'; ?>"><?php _e( 'Gespeicherte Anzeigen', $this->text_domain ); ?></a></li>
	<li class="<?php if (  $sub == 'ended') echo 'cf_active'; ?>"><a href="<?php echo $cf_path . '/?ended'; ?>"><?php _e( 'Beendete Anzeigen', $this->text_domain ); ?></a></li>
</ul>
<div class="clear"></div>
<?php if ( !have_posts() ): ?>
<br /><br />
<div class="info" id="message">
	<p><?php _e( 'Keine Kleinanzeigen gefunden.', $this->text_domain ); ?></p>
</div>
<?php endif; ?>

<div class="cf_tab_container">

	<?php /* Display navigation to next/previous pages when applicable */ ?>
	<?php echo $this->pagination( $this->pagination_top ); ?>

	<div class="cf-listing-grid cf-my-listing-grid">
	<?php while ( have_posts() ) : the_post(); ?>
	<?php // cf_debug( $wp_query ); ?>

	<div id="post-<?php the_ID(); ?>" <?php post_class( 'cf-listing-card-wrap' ); ?> >
		<div class="cf-ad cf-listing-card">
				<?php
				$gallery_ids   = get_post_meta( get_the_ID(), '_cf_gallery_ids', true );
				$gallery_count = is_array( $gallery_ids ) ? count( array_filter( $gallery_ids ) ) : 0;
				$cost_value    = get_post_meta( get_the_ID(), '_cf_cost', true );
				$cost_display  = is_numeric( $cost_value ) ? number_format_i18n( (float) $cost_value, 2 ) : $cost_value;
				$cat_list      = get_the_term_list( get_the_ID(), 'kleinenanzeigen-cat', '', ', ', '' );
				$region_list   = get_the_term_list( get_the_ID(), 'kleinanzeigen-region', '', ', ', '' );
				$is_favorite   = method_exists( $this, 'is_favorite_post' ) && $this->is_favorite_post( get_the_ID() );
				$is_reserved   = method_exists( $this, 'is_reserved_post' ) && $this->is_reserved_post( get_the_ID() );
				?>
				<div class="cf-image">
					<?php if ( $gallery_count > 0 ) : ?>
						<span class="cf-gallery-badge"><?php echo esc_html( sprintf( _n( '%d Bild', '%d Bilder', $gallery_count, $this->text_domain ), $gallery_count ) ); ?></span>
					<?php endif; ?>
					<?php
					if ( '' == get_post_meta( get_the_ID(), '_thumbnail_id', true ) ) {
						if ( ! empty( $options_general['field_image_def'] ) )
						echo '<img width="150" height="150" title="no image" alt="no image" class="cf-no-image wp-post-image" src="' . $options_general['field_image_def'] . '">';
					} else {
						echo get_the_post_thumbnail( get_the_ID(), array( 200, 150 ) );
					}
					?>
				</div>
				<div class="cf-info">
					<div class="cf-card-headline">
						<h3 class="cf-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<?php if ( '' !== (string) $cost_display ) : ?>
							<span class="cf-price"><?php echo esc_html( $cost_display ); ?></span>
						<?php endif; ?>
					</div>

					<div class="cf-card-meta-compact">
						<?php if ( $is_reserved ) : ?>
							<span class="cf-status-badge is-reserved"><?php _e( 'Reserviert', $this->text_domain ); ?></span>
						<?php endif; ?>
						<span class="cf-card-pill"><?php _e( 'Läuft ab', $this->text_domain ); ?>: <?php echo esc_html( $this->get_expiration_date( get_the_ID() ) ); ?></span>
						<?php if ( ! empty( $cat_list ) ) : ?>
							<span class="cf-card-pill"><?php echo wp_kses_post( $cat_list ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $region_list ) ) : ?>
							<span class="cf-card-pill"><?php echo wp_kses_post( $region_list ); ?></span>
						<?php endif; ?>
					</div>

					<p class="cf-excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 16, ' ...' ) ); ?></p>

					<form action="#" method="post" id="action-form-<?php the_ID(); ?>" class="action-form cf-card-actions-form">
					<?php wp_nonce_field('verify'); ?>
					<input type="hidden" name="post_id" value="<?php the_ID(); ?>" />
					<input type="hidden" name="url" value="<?php the_permalink(); ?>" />
					<?php
					if(current_user_can('edit_classified', get_the_ID())){
						echo do_shortcode('[cf_edit_classified_btn text="' . __('Kleinanzeige bearbeiten', $this->text_domain) . '" view="always" post="' . get_the_ID() . '"]');
					}
					?>

					<?php if ( isset( $sub ) && $sub == 'favorites' ): ?>
					<a class="button cf-card-secondary" href="<?php the_permalink(); ?>"><?php _e( 'Anzeige ansehen', $this->text_domain ); ?></a>
					<button type="button" class="button cf-card-secondary cf-favorite-toggle <?php echo $is_favorite ? 'is-active' : ''; ?>" data-post-id="<?php the_ID(); ?>">
						<span class="cf-favorite-label-default"><?php _e( 'Merken', $this->text_domain ); ?></span>
						<span class="cf-favorite-label-active"><?php _e( 'Gemerkt', $this->text_domain ); ?></span>
					</button>
					<?php elseif ( isset( $sub ) && $sub == 'active' ): ?>
					<a class="button cf-card-secondary" href="<?php the_permalink(); ?>"><?php _e( 'Anzeige ansehen', $this->text_domain ); ?></a>
					<?php if ( $user_allow_reserve_toggle ) : ?>
					<button type="submit" class="button cf-card-secondary" name="reserve" value="<?php echo $is_reserved ? esc_attr__( 'Reservierung aufheben', $this->text_domain ) : esc_attr__( 'Als reserviert markieren', $this->text_domain ); ?>" onclick="classifieds.toggle_reserve('<?php the_ID(); ?>'); return false;" ><?php echo $is_reserved ? esc_html__( 'Reservierung aufheben', $this->text_domain ) : esc_html__( 'Als reserviert markieren', $this->text_domain ); ?></button>
					<?php endif; ?>
					<button type="submit" class="button cf-card-secondary" name="end" value="<?php _e('Kleinanzeige beenden', $this->text_domain ); ?>" onclick="classifieds.toggle_end('<?php the_ID(); ?>'); return false;" ><?php _e('Kleinanzeige beenden', $this->text_domain ); ?></button>
					<?php elseif ( isset( $sub ) && ( $sub == 'saved' || $sub == 'ended' ) ): ?>
					<a class="button cf-card-secondary" href="<?php the_permalink(); ?>"><?php _e( 'Anzeige ansehen', $this->text_domain ); ?></a>
					<button type="submit" class="button cf-card-secondary" name="renew" value="<?php _e('Kleinanzeige erneuern', $this->text_domain ); ?>" onclick="classifieds.toggle_renew('<?php the_ID(); ?>'); return false;" ><?php _e('Kleinanzeige erneuern', $this->text_domain ); ?></button>
					<?php endif; ?>

					<?php if(current_user_can( 'delete_classifieds' ) && 'favorites' !== $sub): ?>
					<button type="submit" class="button cf-card-secondary" name="delete" value="<?php _e('Kleinanzeige löschen', $this->text_domain ); ?>" onclick="classifieds.toggle_delete('<?php the_ID(); ?>'); return false;" ><?php _e('Kleinanzeige löschen', $this->text_domain ); ?></button>
					<?php endif; ?>
				</form>

				<form action="#" method="post" id="confirm-form-<?php the_ID(); ?>" class="confirm-form">
					<?php wp_nonce_field('verify'); ?>
					<input type="hidden" name="action" />
					<input type="hidden" name="post_id" value="<?php the_ID(); ?>" />
					<input type="hidden" name="post_title" value="<?php the_title(); ?>" />

					<span id="cf-delete-<?php the_ID(); ?>"><?php _e('Kleinanzeige löschen', $this->text_domain ); ?></span>
					<span id="cf-renew-<?php the_ID(); ?>"><?php _e('Kleinanzeige erneuern', $this->text_domain ); ?></span>
					<span id="cf-end-<?php the_ID(); ?>"><?php _e('Kleinanzeige beenden', $this->text_domain ); ?></span>
					<span id="cf-reserve-<?php the_ID(); ?>"><?php echo $is_reserved ? esc_html__( 'Reservierung aufheben', $this->text_domain ) : esc_html__( 'Als reserviert markieren', $this->text_domain ); ?></span>
					<?php if ( isset( $sub ) && ( $sub == 'saved' || $sub == 'ended' ) ):
					$cf_payments = $this->get_options('payments');

					//Get the duration options
					$_cf_opts  = get_option( CF_OPTIONS_NAME );
					$durations = isset( $_cf_opts['general']['duration_options'] ) ? $_cf_opts['general']['duration_options'] : array( '1 Woche', '2 Wochen', '4 Wochen', '8 Wochen' );
					?>
					<select name="duration">
						<?php
						//make duration options
						foreach ( $durations as $key => $field_option ):
						if( empty($field_option ) ) continue;
						if($this->use_credits):
						?>
						<option value="<?php echo $field_option; ?>"><?php echo sprintf(__('%s für %s Credits', $this->text_domain), $field_option, round($field_option + 0) * $cf_payments['credits_per_week']); ?></option>
						<?php else: ?>
						<option value="<?php echo $field_option; ?>"><?php echo $field_option; ?></option>
						<?php endif; ?>
						<?php endforeach; ?>
					</select>
					<?php endif; ?>
					<input type="submit" class="button confirm" value="<?php _e( 'Bestätigen', $this->text_domain ); ?>" name="confirm" />
					<input type="submit" class="button cancel"  value="<?php _e( 'Abbrechen', $this->text_domain ); ?>" onclick="classifieds.cancel('<?php the_ID(); ?>'); return false;" />
				</form>
				</div>
		</div>
	</div><!-- #post-## -->

	<?php endwhile; ?>
	</div>
	<?php /* Display navigation to next/previous pages when applicable */ ?>
	<?php echo $this->pagination( $this->pagination_bottom ); ?>
</div><!-- .cf_tab_container -->
<?php
if(is_object($wp_query)) $wp_query->post_count = 0;
?>
<!-- End my Classifieds -->
