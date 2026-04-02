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

$query_args = array(
'paged' => $paged,
'post_type' => 'classifieds',
'author' => $current_user->ID,
//'posts_per_page' => 1000,
);

if(isset($_GET['saved']) ) {
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

	<?php while ( have_posts() ) : the_post(); ?>
	<?php // cf_debug( $wp_query ); ?>

	<div id="post-<?php the_ID(); ?>" <?php post_class(); ?> >
		<div class="cf-ad">
			<div class="cf-pad">
				<?php
				$gallery_ids   = get_post_meta( get_the_ID(), '_cf_gallery_ids', true );
				$gallery_count = is_array( $gallery_ids ) ? count( array_filter( $gallery_ids ) ) : 0;
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
					<table>
						<tr>
							<th><?php _e( 'Titel', $this->text_domain ); ?></th>
							<td>
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Kategorien', $this->text_domain ); ?></th>
							<td>
								<?php $taxonomies = get_object_taxonomies( 'classifieds', 'names' ); ?>
								<?php foreach ( $taxonomies as $taxonomy ): ?>
								<?php echo get_the_term_list( get_the_ID(), $taxonomy, '', ', ', '' ) . ' '; ?>
								<?php endforeach; ?>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Läuft ab', $this->text_domain ); ?></th>
							<td><?php echo $this->get_expiration_date( get_the_ID() ); ?></td>
						</tr>
					</table>
				</div>

				<form action="#" method="post" id="action-form-<?php the_ID(); ?>" class="action-form">
					<?php wp_nonce_field('verify'); ?>
					<input type="hidden" name="post_id" value="<?php the_ID(); ?>" />
					<input type="hidden" name="url" value="<?php the_permalink(); ?>" />
					<?php
					if(current_user_can('edit_classified', get_the_ID())){
						echo do_shortcode('[cf_edit_classified_btn text="' . __('Kleinanzeige bearbeiten', $this->text_domain) . '" view="always" post="' . get_the_ID() . '"]');
					}
					?>

					<?php if ( isset( $sub ) && $sub == 'active' ): ?>
					<button type="submit" name="end" value="<?php _e('Kleinanzeige beenden', $this->text_domain ); ?>" onclick="classifieds.toggle_end('<?php the_ID(); ?>'); return false;" ><?php _e('Kleinanzeige beenden', $this->text_domain ); ?></button>
					<?php elseif ( isset( $sub ) && ( $sub == 'saved' || $sub == 'ended' ) ): ?>
					<button type="submit" name="renew" value="<?php _e('Kleinanzeige erneuern', $this->text_domain ); ?>" onclick="classifieds.toggle_renew('<?php the_ID(); ?>'); return false;" ><?php _e('Kleinanzeige erneuern', $this->text_domain ); ?></button>
					<?php endif; ?>

					<?php if(current_user_can( 'delete_classifieds' )): ?>
					<button type="submit" name="delete" value="<?php _e('Kleinanzeige löschen', $this->text_domain ); ?>" onclick="classifieds.toggle_delete('<?php the_ID(); ?>'); return false;" ><?php _e('Kleinanzeige löschen', $this->text_domain ); ?></button>
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
	<div class="clear"></div>

	<?php endwhile; ?>
	<?php /* Display navigation to next/previous pages when applicable */ ?>
	<?php echo $this->pagination( $this->pagination_bottom ); ?>
</div><!-- .cf_tab_container -->
<?php
if(is_object($wp_query)) $wp_query->post_count = 0;
?>
<!-- End my Classifieds -->
